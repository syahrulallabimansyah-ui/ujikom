<?php
// cari_isbn.php — Endpoint AJAX untuk fitur "Tambah Buku Otomatis via ISBN"
// Admin cukup mengetik ISBN di form Tambah Buku, lalu endpoint ini akan
// mencarikan judul, penulis, genre, sinopsis, dan cover buku secara otomatis
// dari layanan Google Books API, dengan fallback ke Open Library API kalau
// datanya tidak ditemukan di Google Books.
//
// PENTING: kita tangkap SEMUA output lewat output buffering (ob_start di
// bawah) supaya kalau ada warning/notice PHP (mis. dari db.php, dari
// koneksi mysqli yang deprecated, dsb) yang biasanya "menyelip" sebelum
// JSON, itu tidak ikut terkirim ke browser dan merusak JSON. Kalau body
// respons tercampur HTML warning, JS di halaman_admin.php gagal parsing
// JSON dan admin cuma melihat field tetap kosong tanpa pesan yang jelas —
// itulah gejala "isi otomatis kok gak muncul apa-apa".
ob_start();
session_start();
header("Content-Type: application/json; charset=utf-8");

// Kirim JSON bersih: buang dulu apapun yang ke-buffer (warning/notice dsb),
// baru kirim JSON asli, lalu keluar. Dipakai di SEMUA titik keluar endpoint
// ini supaya responsnya selalu JSON valid, tidak pernah HTML/blank.
function kirimJson(array $data): void {
    if (ob_get_length() !== false) {
        ob_end_clean();
    }
    echo json_encode($data);
    exit;
}

// Hanya admin yang login yang boleh memakai fitur ini
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    kirimJson(["ok" => false, "message" => "Akses ditolak. Silakan login sebagai admin."]);
}

require_once "db.php";

// API key Google Books (dibuat & dibatasi khusus untuk Books API di Google
// Cloud Console). Dengan key ini, kuota harian jauh lebih besar daripada
// kuota anonim bersama yang sebelumnya bikin error "429 Quota exceeded".
// CATATAN KEAMANAN: jangan commit file ini ke repo GitHub publik. Kalau
// suatu saat repo-nya mau di-publish, pindahkan baris di bawah ini ke file
// config terpisah yang di-gitignore, lalu require file itu dari sini.
define("GOOGLE_BOOKS_API_KEY", "AIzaSyAGnEXPAvJtnKpSkkInJ3oAzp-PXfgHZbw");

// Debug bisa diaktifkan sementara lewat ?debug=1 di URL (aman — endpoint ini
// sudah mengharuskan login sebagai admin di atas, jadi tidak bisa diintip
// orang lain). Pakai ini untuk melihat error ASLI dari Google Books / Open
// Library saat pencarian ISBN gagal, tanpa perlu buka error_log server.
// Contoh: cari_isbn.php?isbn=9786020412079&debug=1
define("ISBN_DEBUG", isset($_GET["debug"]) && $_GET["debug"] == "1");

// Kumpulan pesan error mentah dari tiap sumber, untuk logging/debug.
$api_errors = [];

// ─────────────────────────────────────────────
//  HELPER: ambil JSON dari URL eksternal
//  Return array [data|null, error_message|null, http_code|null]
// ─────────────────────────────────────────────
function httpGetJson(string $url, int $timeout = 8): array {
    if (function_exists("curl_init")) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => "AksaNova-Library/1.0",
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        // SSL fallback: jika gagal karena SSL (umum di Laragon/XAMPP Windows),
        // coba lagi tanpa verifikasi SSL
        if (($res === false || $err) && stripos($err, 'SSL') !== false) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_USERAGENT      => "AksaNova-Library/1.0",
            ]);
            $res  = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);
        }

        if ($res === false || $err) {
            return [null, "cURL error: $err", $code ?: null];
        }
        if ($code === 404) {
            return [null, null, 404];
        }
        if ($code >= 400) {
            $snippet = substr((string)$res, 0, 300);
            return [null, "HTTP $code dari $url — $snippet", $code];
        }
        $data = json_decode($res, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [null, "JSON tidak valid dari $url: " . json_last_error_msg(), $code];
        }
        return [$data, null, $code];
    }

    // Fallback kalau ekstensi cURL tidak tersedia di server
    if (!ini_get("allow_url_fopen")) {
        return [null, "cURL tidak aktif dan allow_url_fopen juga dimatikan di server.", null];
    }
    $ctx = stream_context_create(["http" => [
        "timeout" => $timeout,
        "header"  => "User-Agent: AksaNova-Library/1.0\r\n",
        "ignore_errors" => true,
    ]]);
    $res = @file_get_contents($url, false, $ctx);
    if ($res === false) {
        $err = error_get_last();
        return [null, "file_get_contents gagal: " . ($err["message"] ?? "unknown"), null];
    }
    $data = json_decode($res, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [null, "JSON tidak valid dari $url: " . json_last_error_msg(), null];
    }
    return [$data, null, null];
}

// ─────────────────────────────────────────────
//  HELPER: unduh gambar cover & simpan ke server
// ─────────────────────────────────────────────
function downloadCover(string $url, string $isbn): string {
    if ($url === "") return "";

    if (function_exists("curl_init")) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => "AksaNova-Library/1.0",
        ]);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        // Fallback retry dengan SSL disabled jika gagal
        if (($data === false || $code >= 400 || $err) && stripos($err, 'SSL') !== false) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_USERAGENT      => "AksaNova-Library/1.0",
            ]);
            $data = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }

        if ($data === false || $code >= 400) return "";
    } else {
        if (!ini_get("allow_url_fopen")) return "";
        $ctx = stream_context_create([
            "http" => [
                "timeout" => 10,
                "header"  => "User-Agent: AksaNova-Library/1.0\r\n",
            ],
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ]
        ]);
        $data = @file_get_contents($url, false, $ctx);
        if ($data === false) return "";
    }

    // Cover placeholder "tidak ada gambar" biasanya berukuran sangat kecil
    if (strlen($data) < 500) return "";

    $dir = "uploads/gambar/";
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    $isbn_bersih = preg_replace('/[^0-9Xx]/', '', $isbn);
    $filename    = uniqid("buku_isbn_{$isbn_bersih}_") . ".jpg";
    if (@file_put_contents($dir . $filename, $data) === false) return "";

    return $dir . $filename;
}

// ─────────────────────────────────────────────
//  VALIDASI INPUT ISBN
// ─────────────────────────────────────────────
$isbn_raw = trim($_GET["isbn"] ?? "");
$isbn     = preg_replace('/[^0-9Xx]/', '', $isbn_raw); // buang spasi, strip, dll

if (strlen($isbn) !== 10 && strlen($isbn) !== 13) {
    kirimJson([
        "ok"      => false,
        "message" => "Format ISBN tidak valid. ISBN harus terdiri dari 10 atau 13 digit.",
    ]);
}

// ─────────────────────────────────────────────
//  CEK APAKAH ISBN SUDAH ADA DI DATABASE (cegah duplikat)
// ─────────────────────────────────────────────
$duplikat  = null;
$isbn_esc  = mysqli_real_escape_string($conn, $isbn);
$cek       = mysqli_query($conn, "SELECT id, judul, stok FROM buku WHERE isbn = '$isbn_esc' LIMIT 1");
if ($cek && $row = mysqli_fetch_assoc($cek)) {
    $duplikat = [
        "id"    => (int)$row["id"],
        "judul" => $row["judul"],
        "stok"  => (int)$row["stok"],
    ];
}

$judul = ""; $penulis = ""; $genre = ""; $sinopsis = ""; $gambar_url = "";
$sumber_list = []; // bisa lebih dari satu sumber kalau datanya digabung dari beberapa API

// ─────────────────────────────────────────────
//  1) COBA GOOGLE BOOKS API
//  &country=ID ditambahkan karena tanpa parameter ini, Google Books API
//  sering gagal menampilkan hasil (atau melempar error "unknownLocation")
//  saat server tidak bisa dideteksi lokasinya — kasus umum di shared hosting.
// ─────────────────────────────────────────────
[$g, $errG] = httpGetJson(
    "https://www.googleapis.com/books/v1/volumes?q=isbn:" . urlencode($isbn) . "&country=ID&key=" . urlencode(GOOGLE_BOOKS_API_KEY)
);
if ($errG) $api_errors["google_books"] = $errG;

if ($g && !empty($g["totalItems"]) && !empty($g["items"][0]["volumeInfo"])) {
    $info = $g["items"][0]["volumeInfo"];

    $judul_gb = $info["title"] ?? "";
    if (!empty($info["subtitle"])) $judul_gb .= " - " . $info["subtitle"];

    if ($judul_gb !== "") { $judul = $judul_gb; $sumber_list[] = "Google Books"; }
    if (!empty($info["authors"]))    $penulis  = implode(", ", $info["authors"]);
    if (!empty($info["categories"])) $genre    = $info["categories"][0];
    if (!empty($info["description"])) $sinopsis = $info["description"];

    if (!empty($info["imageLinks"]["thumbnail"])) {
        $gambar_url = str_replace("http://", "https://", $info["imageLinks"]["thumbnail"]);
    } elseif (!empty($info["imageLinks"]["smallThumbnail"])) {
        $gambar_url = str_replace("http://", "https://", $info["imageLinks"]["smallThumbnail"]);
    }
}

// ─────────────────────────────────────────────
//  2) OPEN LIBRARY API — bibkeys
//  Dipanggil bukan cuma kalau Google Books gagal total, tapi juga kalau
//  Google Books ketemu judulnya tapi genre/sinopsis/covernya kosong —
//  supaya kolom yang masih kosong bisa dilengkapi dari sumber lain, tanpa
//  menimpa data yang sudah didapat dari Google Books.
// ─────────────────────────────────────────────
if ($judul === "" || $genre === "" || $sinopsis === "" || $gambar_url === "") {
    $key = "ISBN:" . $isbn;
    [$ol, $errOL] = httpGetJson(
        "https://openlibrary.org/api/books?bibkeys=" . urlencode($key) . "&format=json&jscmd=data"
    );
    if ($errOL) $api_errors["open_library_bibkeys"] = $errOL;

    if ($ol && !empty($ol[$key])) {
        $info = $ol[$key];

        if ($judul === "" && !empty($info["title"])) {
            $judul = $info["title"];
            $sumber_list[] = "Open Library";
        }
        if ($penulis === "" && !empty($info["authors"])) {
            $penulis = implode(", ", array_column($info["authors"], "name"));
        }
        if ($genre === "" && !empty($info["subjects"])) {
            $genre = $info["subjects"][0]["name"] ?? "";
            if ($genre !== "") $sumber_list[] = "Open Library (genre)";
        }
        if ($sinopsis === "") {
            if (!empty($info["notes"])) {
                $sinopsis = is_array($info["notes"]) ? ($info["notes"]["value"] ?? "") : $info["notes"];
            } elseif (!empty($info["excerpts"][0]["text"])) {
                $sinopsis = $info["excerpts"][0]["text"];
            }
            if ($sinopsis !== "") $sumber_list[] = "Open Library (sinopsis)";
        }
        if ($gambar_url === "") {
            if (!empty($info["cover"]["large"])) {
                $gambar_url = $info["cover"]["large"];
            } elseif (!empty($info["cover"]["medium"])) {
                $gambar_url = $info["cover"]["medium"];
            }
        }
    }
}

// ─────────────────────────────────────────────
//  3) FALLBACK KEDUA: OPEN LIBRARY — endpoint search.json
//  Cakupannya kadang lebih luas daripada endpoint bibkeys di atas,
//  terutama untuk edisi/cetakan yang datanya tidak lengkap. Sama seperti
//  di atas, cuma dipakai untuk melengkapi kolom yang MASIH kosong.
// ─────────────────────────────────────────────
if ($judul === "" || $genre === "" || $gambar_url === "") {
    [$os, $errOS] = httpGetJson(
        "https://openlibrary.org/search.json?isbn=" . urlencode($isbn) . "&limit=1"
    );
    if ($errOS) $api_errors["open_library_search"] = $errOS;

    if ($os && !empty($os["docs"][0])) {
        $info = $os["docs"][0];

        if ($judul === "" && !empty($info["title"])) {
            $judul = $info["title"];
            $sumber_list[] = "Open Library (search)";
        }
        if ($penulis === "" && !empty($info["author_name"])) {
            $penulis = implode(", ", $info["author_name"]);
        }
        if ($genre === "" && !empty($info["subject"])) {
            $genre = $info["subject"][0];
            $sumber_list[] = "Open Library (search, genre)";
        }
        // Endpoint ini tidak menyediakan sinopsis.
        if ($gambar_url === "" && !empty($info["cover_i"])) {
            $gambar_url = "https://covers.openlibrary.org/b/id/" . $info["cover_i"] . "-L.jpg";
        }
    }
}

// ─────────────────────────────────────────────
//  4) OPEN LIBRARY — data WORK (khusus untuk genre & sinopsis)
//  Ini penyebab paling umum kenapa genre/sinopsis sering kosong padahal
//  judul & cover sudah ketemu: di Open Library, field "subjects" (genre)
//  dan "description" (sinopsis) itu nempel di level WORK (karya induk),
//  BUKAN di level EDITION (cetakan spesifik per ISBN). Endpoint bibkeys &
//  search.json di atas cuma mengembalikan data Edition, jadi subjects &
//  description-nya memang jarang terisi di situ.
//  Di sini kita ambil dulu data Edition via /isbn/{isbn}.json untuk tahu
//  "works" (key karya induknya), lalu ambil /works/{key}.json yang berisi
//  subjects & description lengkap.
// ─────────────────────────────────────────────
if ($genre === "" || $sinopsis === "") {
    [$ed, $errEd] = httpGetJson("https://openlibrary.org/isbn/" . urlencode($isbn) . ".json");
    if ($errEd) $api_errors["open_library_edition"] = $errEd;

    if ($ed && !empty($ed["works"][0]["key"])) {
        $workKey = $ed["works"][0]["key"]; // contoh: "/works/OL45804W"
        [$wk, $errWk] = httpGetJson("https://openlibrary.org" . $workKey . ".json");
        if ($errWk) $api_errors["open_library_work"] = $errWk;

        if ($wk) {
            if ($genre === "" && !empty($wk["subjects"])) {
                $genre = $wk["subjects"][0];
                $sumber_list[] = "Open Library (work, genre)";
            }
            if ($sinopsis === "" && !empty($wk["description"])) {
                $sinopsis = is_array($wk["description"]) ? ($wk["description"]["value"] ?? "") : $wk["description"];
                if ($sinopsis !== "") $sumber_list[] = "Open Library (work, sinopsis)";
            }
        }
    }
}

$sumber = implode(" + ", array_unique($sumber_list));

// ─────────────────────────────────────────────
//  TIDAK DITEMUKAN DI SEMUA SUMBER

// ─────────────────────────────────────────────
if ($judul === "") {
    // Catat error asli ke log server supaya bisa ditelusuri (tidak "tertelan"
    // diam-diam seperti sebelumnya). Cek error_log server untuk detailnya.
    if (!empty($api_errors)) {
        error_log("[cari_isbn] ISBN $isbn tidak ditemukan. Error API: " . json_encode($api_errors));
    }

    // Kalau semua sumber gagal dihubungi (bukan cuma "tidak ada datanya"),
    // beri pesan yang berbeda supaya admin tahu ini masalah koneksi/API,
    // bukan berarti bukunya memang tidak terdaftar di database manapun.
    $semua_gagal_konek = count($api_errors) >= 2; // google_books + minimal 1 open library gagal total

    $pesan = $semua_gagal_konek
        ? "Gagal menghubungi layanan pencarian buku online. Periksa koneksi internet server, lalu coba lagi."
        : "Buku dengan ISBN tersebut tidak ditemukan di database online. Silakan isi data secara manual.";

    $response = [
        "ok"       => false,
        "message"  => $pesan,
        "duplikat" => $duplikat,
    ];
    // Selalu sertakan ringkasan error asli (bukan hanya saat ?debug=1) —
    // endpoint ini sudah dikunci untuk admin saja lewat cek role di atas,
    // jadi aman, dan ini bikin admin langsung lihat penyebabnya di kotak
    // status pada form, tanpa perlu buka DevTools atau error_log server.
    if (!empty($api_errors)) $response["debug_errors"] = $api_errors;
    if (ISBN_DEBUG) $response["debug_errors"] = $api_errors;

    kirimJson($response);
}

// ─────────────────────────────────────────────
//  UNDUH COVER (kalau ada) & KIRIM HASIL
// ─────────────────────────────────────────────
$gambar_local = downloadCover($gambar_url, $isbn);

kirimJson([
    "ok"       => true,
    "sumber"   => $sumber,
    "judul"    => trim($judul),
    "penulis"  => trim($penulis),
    "genre"    => trim($genre),
    "sinopsis" => trim(strip_tags($sinopsis)),
    "gambar"   => $gambar_local,
    "isbn"     => $isbn,
    "duplikat" => $duplikat,
]);