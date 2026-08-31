<?php
// cari_isbn.php — Endpoint AJAX untuk fitur "Tambah Buku Otomatis via ISBN"
// Admin cukup mengetik ISBN di form Tambah Buku, lalu endpoint ini akan
// mencarikan judul, penulis, genre, sinopsis, dan cover buku secara otomatis
// dari layanan Google Books API, dengan fallback ke Open Library API kalau
// datanya tidak ditemukan di Google Books.
session_start();
header("Content-Type: application/json; charset=utf-8");

// Hanya admin yang login yang boleh memakai fitur ini
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    echo json_encode(["ok" => false, "message" => "Akses ditolak. Silakan login sebagai admin."]);
    exit;
}

require_once "db.php";

// Ganti ke true sementara kalau mau lihat detail error API di response JSON
// (mis. saat development / lagi debug kenapa pencarian gagal).
// WAJIB matikan lagi (false) di server produksi.
const ISBN_DEBUG = false;

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

        if ($res === false || $err) {
            return [null, "cURL error: $err", $code ?: null];
        }
        if ($code >= 400) {
            // Sertakan potongan body respons — biasanya berisi alasan error
            // dari Google/Open Library (mis. "unknownLocation").
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
            CURLOPT_USERAGENT      => "AksaNova-Library/1.0",
        ]);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($data === false || $code >= 400) return "";
    } else {
        if (!ini_get("allow_url_fopen")) return "";
        $ctx = stream_context_create(["http" => [
            "timeout" => 10,
            "header"  => "User-Agent: AksaNova-Library/1.0\r\n",
        ]]);
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
    echo json_encode([
        "ok"      => false,
        "message" => "Format ISBN tidak valid. ISBN harus terdiri dari 10 atau 13 digit.",
    ]);
    exit;
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

$judul = ""; $penulis = ""; $genre = ""; $sinopsis = ""; $gambar_url = ""; $sumber = "";

// ─────────────────────────────────────────────
//  1) COBA GOOGLE BOOKS API
//  &country=ID ditambahkan karena tanpa parameter ini, Google Books API
//  sering gagal menampilkan hasil (atau melempar error "unknownLocation")
//  saat server tidak bisa dideteksi lokasinya — kasus umum di shared hosting.
// ─────────────────────────────────────────────
[$g, $errG] = httpGetJson(
    "https://www.googleapis.com/books/v1/volumes?q=isbn:" . urlencode($isbn) . "&country=ID"
);
if ($errG) $api_errors["google_books"] = $errG;

if ($g && !empty($g["totalItems"]) && !empty($g["items"][0]["volumeInfo"])) {
    $info = $g["items"][0]["volumeInfo"];

    $judul = $info["title"] ?? "";
    if (!empty($info["subtitle"])) $judul .= " - " . $info["subtitle"];

    $penulis  = !empty($info["authors"])   ? implode(", ", $info["authors"]) : "";
    $genre    = !empty($info["categories"]) ? $info["categories"][0]         : "";
    $sinopsis = $info["description"] ?? "";

    if (!empty($info["imageLinks"]["thumbnail"])) {
        $gambar_url = str_replace("http://", "https://", $info["imageLinks"]["thumbnail"]);
    } elseif (!empty($info["imageLinks"]["smallThumbnail"])) {
        $gambar_url = str_replace("http://", "https://", $info["imageLinks"]["smallThumbnail"]);
    }
    $sumber = "Google Books";
}

// ─────────────────────────────────────────────
//  2) FALLBACK: OPEN LIBRARY API — bibkeys (kalau Google Books tidak ketemu)
// ─────────────────────────────────────────────
if ($judul === "") {
    $key = "ISBN:" . $isbn;
    [$ol, $errOL] = httpGetJson(
        "https://openlibrary.org/api/books?bibkeys=" . urlencode($key) . "&format=json&jscmd=data"
    );
    if ($errOL) $api_errors["open_library_bibkeys"] = $errOL;

    if ($ol && !empty($ol[$key])) {
        $info = $ol[$key];

        $judul   = $info["title"] ?? "";
        $penulis = !empty($info["authors"]) ? implode(", ", array_column($info["authors"], "name")) : "";
        $genre   = !empty($info["subjects"]) ? ($info["subjects"][0]["name"] ?? "") : "";

        if (!empty($info["notes"])) {
            $sinopsis = is_array($info["notes"]) ? ($info["notes"]["value"] ?? "") : $info["notes"];
        } elseif (!empty($info["excerpts"][0]["text"])) {
            $sinopsis = $info["excerpts"][0]["text"];
        }

        if (!empty($info["cover"]["large"])) {
            $gambar_url = $info["cover"]["large"];
        } elseif (!empty($info["cover"]["medium"])) {
            $gambar_url = $info["cover"]["medium"];
        }
        $sumber = "Open Library";
    }
}

// ─────────────────────────────────────────────
//  3) FALLBACK KEDUA: OPEN LIBRARY — endpoint search.json
//  Cakupannya kadang lebih luas daripada endpoint bibkeys di atas,
//  terutama untuk edisi/cetakan yang datanya tidak lengkap.
// ─────────────────────────────────────────────
if ($judul === "") {
    [$os, $errOS] = httpGetJson(
        "https://openlibrary.org/search.json?isbn=" . urlencode($isbn) . "&limit=1"
    );
    if ($errOS) $api_errors["open_library_search"] = $errOS;

    if ($os && !empty($os["docs"][0])) {
        $info = $os["docs"][0];

        $judul   = $info["title"] ?? "";
        $penulis = !empty($info["author_name"]) ? implode(", ", $info["author_name"]) : "";
        $genre   = !empty($info["subject"]) ? $info["subject"][0] : "";
        // Endpoint ini tidak menyediakan sinopsis.

        if (!empty($info["cover_i"])) {
            $gambar_url = "https://covers.openlibrary.org/b/id/" . $info["cover_i"] . "-L.jpg";
        }
        $sumber = "Open Library (search)";
    }
}

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
    if (ISBN_DEBUG) $response["debug_errors"] = $api_errors;

    echo json_encode($response);
    exit;
}

// ─────────────────────────────────────────────
//  UNDUH COVER (kalau ada) & KIRIM HASIL
// ─────────────────────────────────────────────
$gambar_local = downloadCover($gambar_url, $isbn);

echo json_encode([
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