<?php
// index.php — Landing / welcome page gaya "slide scroll" (referensi: Wuthering Waves)
// Slide 1: Hero (gambar diatur admin via Kelola Banner)   Slide 2: Buku terbaru   Slide 3: Tata tertib perpustakaan
require_once "db.php";

$page_title = "Selamat Datang – AKSA NOVA";

/* ───────────────────────── Ambil data buku terbaru ───────────────────────── */
$buku_list = [];
$res = @mysqli_query($conn, "SELECT id, judul, penulis, genre, sinopsis, gambar FROM buku ORDER BY created_at DESC LIMIT 4");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $buku_list[] = $row;
    }
}
$buku_utama = $buku_list[0] ?? null;

// Kutipan literasi landing page (QTE)
$kutipan_list = [
    ["teks" => "Sebuah kamar tanpa buku adalah seperti tubuh tanpa jiwa.", "oleh" => "Marcus Tullius Cicero"],
    ["teks" => "Membaca adalah jendela dunia yang bisa dibuka kapan saja.", "oleh" => "Pramoedya Ananta Toer"],
    ["teks" => "Buku yang baik adalah teman terbaik, hari ini dan selamanya.", "oleh" => "Anonim"],
    ["teks" => "Hari ini seorang pembaca, esok seorang pemimpin.", "oleh" => "Margaret Fuller"],
    ["teks" => "Tidak ada teman sesetia buku.", "oleh" => "Ernest Hemingway"],
    ["teks" => "Buku adalah kapal pikiran yang mengembara di lautan waktu.", "oleh" => "Francis Bacon"],
    ["teks" => "Satu buku, satu pena, satu anak, dan satu guru dapat mengubah dunia.", "oleh" => "Malala Yousafzai"],
];
$kutipan_hari_ini = $kutipan_list[(int)date("z") % count($kutipan_list)];


if (!function_exists('format_genre')) {
    function format_genre($genre) {
        if (!$genre) return "";
        $parts = explode(":", $genre);
        $label = end($parts);
        $label = str_replace("_", " ", $label);
        return ucwords($label);
    }
}

if (!function_exists('ringkas')) {
    function ringkas($teks, $panjang = 220) {
        $teks = trim(strip_tags($teks ?? ""));
        if ($teks === "") return "Sinopsis belum tersedia untuk buku ini.";
        if (mb_strlen($teks) <= $panjang) return $teks;
        return mb_substr($teks, 0, $panjang) . "…";
    }
}

/* ───────────────────────── Gambar Slide 1 (dipilih admin lewat "Jadikan Latar Beranda" di Kelola Banner) ───────────────────────── */
$banner_aktif_list = [];
$banner_all_res = @mysqli_query($conn, "SELECT id, judul, subjudul, gambar, link_url FROM banner WHERE aktif = 1 ORDER BY urutan ASC");
if ($banner_all_res) {
    while ($row = mysqli_fetch_assoc($banner_all_res)) {
        if ($row["gambar"] && file_exists($row["gambar"])) {
            $banner_aktif_list[] = $row;
        }
    }
}

$banner_background_id = 0;
$bgres = @mysqli_query($conn, "SELECT nilai FROM pengaturan WHERE kunci = 'banner_background_id' LIMIT 1");
if ($bgres && ($bg = mysqli_fetch_assoc($bgres))) {
    $banner_background_id = (int) $bg["nilai"];
}

// Gambar yang dipakai sebagai latar Slide 1: prioritaskan banner yang dipilih admin, jika tidak ada pakai banner aktif pertama
$hero_image = null;
if ($banner_background_id > 0) {
    foreach ($banner_aktif_list as $bimg) {
        if ((int) $bimg["id"] === $banner_background_id) { $hero_image = $bimg; break; }
    }
}
if (!$hero_image && !empty($banner_aktif_list)) {
    $hero_image = $banner_aktif_list[0];
}
$hero_images = $hero_image ? [$hero_image] : [];

/* ───────────────────────── Pengaturan denda (untuk konten Tata Tertib) ───────────────────────── */
$denda_per_hari = 5000;
$denda_aktif    = 0;
$pgt = @mysqli_query($conn, "SELECT kunci, nilai FROM pengaturan");
if ($pgt) {
    while ($p = mysqli_fetch_assoc($pgt)) {
        if ($p["kunci"] === "denda_per_hari") $denda_per_hari = (int) $p["nilai"];
        if ($p["kunci"] === "denda_aktif")    $denda_aktif    = (int) $p["nilai"];
    }
}
$denda_text = $denda_aktif
    ? "Denda keterlambatan berlaku sebesar Rp " . number_format($denda_per_hari, 0, ",", ".") . " per hari."
    : "Saat ini denda keterlambatan sedang tidak diaktifkan, namun keterlambatan tetap tercatat di sistem.";
$denda_text_en = $denda_aktif
    ? "A late fee of Rp " . number_format($denda_per_hari, 0, ",", ".") . " per day applies."
    : "Late fees are currently not active, but late returns are still recorded in the system.";

/* ───────────────────────── Pengaturan musik latar (diatur admin) ───────────────────────── */
$musik_aktif = 0;
$musik_file  = "";
$musik_judul = "Musik Latar";
$mgt = @mysqli_query($conn, "SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('musik_aktif','musik_file','musik_judul')");
if ($mgt) {
    while ($m = mysqli_fetch_assoc($mgt)) {
        if ($m["kunci"] === "musik_aktif") $musik_aktif = (int) $m["nilai"];
        if ($m["kunci"] === "musik_file")  $musik_file  = $m["nilai"];
        if ($m["kunci"] === "musik_judul") $musik_judul = $m["nilai"] ?: $musik_judul;
    }
}
// Musik hanya tampil jika admin mengaktifkannya DAN filenya benar-benar ada
$musik_tampil = ($musik_aktif === 1 && $musik_file !== "" && file_exists($musik_file));

/* ───────────────────────── Pengaturan lokasi & jam operasional ─────────────────────────
   Dikelola melalui panel admin (pengaturan_lokasi.php) dan tersimpan di tabel `pengaturan`. */
$lokasi_sekolah          = "SMK Negeri 1 Rongga";
$lokasi_alamat           = "Jl. Situ Gede / Jl. Raya Rongga (RT 01/RW 04), Desa Cibedug, Kec. Rongga, Kab. Bandung Barat, Jawa Barat 40565";
$lokasi_jam              = "Senin – Jumat, 07.00 – 15.00 WIB";
$lokasi_telepon          = "083829165209";
$lokasi_email            = "perpustakaan@smkn1rongga.sch.id";
$lokasi_map_mode         = "query";
$lokasi_map_query        = "SMK Negeri 1 Rongga, Bandung Barat";
$lokasi_map_embed_custom = "";
$operasional_status      = "otomatis";
$operasional_catatan     = "";
$jam_buka_senin_kamis    = "07:00";
$jam_tutup_senin_kamis   = "15:00";
$jam_buka_jumat          = "07:00";
$jam_tutup_jumat         = "14:30";
$jam_status_sabtu        = "libur";
$jam_buka_sabtu          = "08:00";
$jam_tutup_sabtu         = "12:00";
$jam_status_minggu       = "libur";
$jam_buka_minggu         = "08:00";
$jam_tutup_minggu        = "12:00";

$lgt = @mysqli_query($conn, "SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('lokasi_sekolah','lokasi_alamat','lokasi_jam','lokasi_telepon','lokasi_email','lokasi_map_mode','lokasi_map_query','lokasi_map_embed_custom','operasional_status','operasional_catatan','jam_buka_senin_kamis','jam_tutup_senin_kamis','jam_buka_jumat','jam_tutup_jumat','jam_status_sabtu','jam_buka_sabtu','jam_tutup_sabtu','jam_status_minggu','jam_buka_minggu','jam_tutup_minggu')");
if ($lgt) {
    while ($l = mysqli_fetch_assoc($lgt)) {
        if ($l["kunci"] === "lokasi_sekolah"          && $l["nilai"] !== "") $lokasi_sekolah          = $l["nilai"];
        if ($l["kunci"] === "lokasi_alamat"           && $l["nilai"] !== "") $lokasi_alamat           = $l["nilai"];
        if ($l["kunci"] === "lokasi_jam"              && $l["nilai"] !== "") $lokasi_jam              = $l["nilai"];
        if ($l["kunci"] === "lokasi_telepon"          && $l["nilai"] !== "") $lokasi_telepon          = $l["nilai"];
        if ($l["kunci"] === "lokasi_email"            && $l["nilai"] !== "") $lokasi_email            = $l["nilai"];
        if ($l["kunci"] === "lokasi_map_mode"         && $l["nilai"] !== "") $lokasi_map_mode         = $l["nilai"];
        if ($l["kunci"] === "lokasi_map_query"        && $l["nilai"] !== "") $lokasi_map_query        = $l["nilai"];
        if ($l["kunci"] === "lokasi_map_embed_custom" && $l["nilai"] !== "") $lokasi_map_embed_custom = $l["nilai"];
        if ($l["kunci"] === "operasional_status"      && $l["nilai"] !== "") $operasional_status      = $l["nilai"];
        if ($l["kunci"] === "operasional_catatan")                           $operasional_catatan     = $l["nilai"];
        if ($l["kunci"] === "jam_buka_senin_kamis"    && $l["nilai"] !== "") $jam_buka_senin_kamis    = $l["nilai"];
        if ($l["kunci"] === "jam_tutup_senin_kamis"   && $l["nilai"] !== "") $jam_tutup_senin_kamis   = $l["nilai"];
        if ($l["kunci"] === "jam_buka_jumat"          && $l["nilai"] !== "") $jam_buka_jumat          = $l["nilai"];
        if ($l["kunci"] === "jam_tutup_jumat"         && $l["nilai"] !== "") $jam_tutup_jumat         = $l["nilai"];
        if ($l["kunci"] === "jam_status_sabtu"        && $l["nilai"] !== "") $jam_status_sabtu        = $l["nilai"];
        if ($l["kunci"] === "jam_buka_sabtu"          && $l["nilai"] !== "") $jam_buka_sabtu          = $l["nilai"];
        if ($l["kunci"] === "jam_tutup_sabtu"         && $l["nilai"] !== "") $jam_tutup_sabtu         = $l["nilai"];
        if ($l["kunci"] === "jam_status_minggu"       && $l["nilai"] !== "") $jam_status_minggu       = $l["nilai"];
        if ($l["kunci"] === "jam_buka_minggu"         && $l["nilai"] !== "") $jam_buka_minggu         = $l["nilai"];
        if ($l["kunci"] === "jam_tutup_minggu"        && $l["nilai"] !== "") $jam_tutup_minggu        = $l["nilai"];
    }
}

$query_peta       = !empty($lokasi_map_query) ? $lokasi_map_query : ($lokasi_sekolah . ", " . $lokasi_alamat);
$lokasi_maps_link = "https://www.google.com/maps/search/?api=1&query=" . urlencode($query_peta);

if ($lokasi_map_mode === "embed" && !empty($lokasi_map_embed_custom)) {
    if (preg_match('~src=["\']([^"\']+)["\']~i', $lokasi_map_embed_custom, $m_emb)) {
        $lokasi_maps_embed = $m_emb[1];
    } else {
        $lokasi_maps_embed = $lokasi_map_embed_custom;
    }
} else {
    $lokasi_maps_embed = "https://maps.google.com/maps?q=" . urlencode($query_peta) . "&t=&z=16&ie=UTF8&iwloc=&output=embed";
}

// Hitung status buka/tutup perpustakaan realtime WIB
$sekarang_wib = new DateTime("now", new DateTimeZone("Asia/Jakarta"));
$hari_ke = (int)$sekarang_wib->format("N"); // 1=Senin..7=Minggu
$jam_sekarang = $sekarang_wib->format("H:i");
$perpustakaan_buka = false;
$status_badge_text = "Sedang Tutup";
$status_badge_type = "tutup";

if ($operasional_status === "buka_manual") {
    $perpustakaan_buka = true;
    $status_badge_text = "Buka Sekarang";
    $status_badge_type = "buka";
} elseif ($operasional_status === "tutup_libur") {
    $perpustakaan_buka = false;
    $status_badge_text = "Tutup / Libur";
    $status_badge_type = "libur";
} else {
    if ($hari_ke >= 1 && $hari_ke <= 4) {
        if ($jam_sekarang >= $jam_buka_senin_kamis && $jam_sekarang <= $jam_tutup_senin_kamis) {
            $perpustakaan_buka = true;
        }
    } elseif ($hari_ke === 5) {
        if ($jam_sekarang >= $jam_buka_jumat && $jam_sekarang <= $jam_tutup_jumat) {
            $perpustakaan_buka = true;
        }
    } elseif ($hari_ke === 6 && $jam_status_sabtu === "buka") {
        if ($jam_sekarang >= $jam_buka_sabtu && $jam_sekarang <= $jam_tutup_sabtu) {
            $perpustakaan_buka = true;
        }
    } elseif ($hari_ke === 7 && $jam_status_minggu === "buka") {
        if ($jam_sekarang >= $jam_buka_minggu && $jam_sekarang <= $jam_tutup_minggu) {
            $perpustakaan_buka = true;
        }
    }
    if ($perpustakaan_buka) {
        $status_badge_text = "Buka Sekarang";
        $status_badge_type = "buka";
    } else {
        $is_weekend_closed = ($hari_ke === 6 && $jam_status_sabtu === "libur") || ($hari_ke === 7 && $jam_status_minggu === "libur");
        $status_badge_text = $is_weekend_closed ? "Libur Akhir Pekan" : "Sedang Tutup";
        $status_badge_type = "tutup";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= htmlspecialchars($page_title) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet"/>
<script>
  // Terapkan tema tersimpan sedini mungkin supaya tidak ada "kedip" warna saat halaman dimuat
  (function () {
    try {
      if (localStorage.getItem('aksanova_theme') === 'light') {
        document.documentElement.classList.add('theme-light');
      }
      if (localStorage.getItem('aksanova_lite') === '1') {
        document.documentElement.classList.add('lite');
      }
    } catch (e) {}
  })();
</script>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:        #090c10;
    --bg-2:      #10151b;
    --panel:     rgba(12, 17, 22, .58);
    --line:      rgba(216, 184, 120, .28);
    --teal:      #d8b878;
    --teal-dim:  rgba(216, 184, 120, .35);
    --gold:      #d8b878;
    --accent:    #d8b878;
    --text:      #eef3f4;
    --text-dim:  rgba(238, 243, 244, .75);
    --text-faint:rgba(238, 243, 244, .55);
    --muted:     rgba(238, 243, 244, .72);
    --serif:     'Cormorant Garamond', serif;
    --sans:      'Outfit', sans-serif;
  }

  /* ══════════════════ MODE TERANG (KONTRAST TINGGI & JERNIH) ══════════════════ */
  html.theme-light {
    --bg:        #f6f2e8;
    --bg-2:      #ffffff;
    --panel:     rgba(255, 255, 255, .90);
    --line:      rgba(150, 110, 45, .30);
    --teal:      #8a6323;
    --teal-dim:  rgba(138, 99, 35, .32);
    --gold:      #8a6323;
    --accent:    #8a6323;
    --text:      #1a1610;
    --text-dim:  #3e372d;
    --text-faint:#5a5144;
    --muted:     #524b40;
  }

  /* ══════════════════ MODE TAMPILAN RINGAN ══════════════════
     Matikan animasi dekoratif, efek blur (backdrop-filter) dan bayangan berat
     supaya halaman terasa lebih ringan & lancar di perangkat/koneksi lemah. */
  html.lite * {
    animation: none !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
  }
  html.lite .hero-glow,
  html.lite .footer-glow {
    display: none !important;
  }
  html.lite .reveal,
  html.lite .reveal-item {
    opacity: 1 !important;
    transform: none !important;
  }
  html.lite .hero-bg-slide,
  html.lite .hero-bg-slide.active,
  html.lite .book-cover-wrap.opening .book-cover,
  html.lite .book-cover-wrap.opening .cover-icon {
    transform: none !important;
  }
  html.lite .btn-musik:hover,
  html.lite .btn-mode:hover,
  html.lite .btn-lite:hover,
  html.lite .cta-glow:hover {
    transform: none !important;
    box-shadow: none !important;
  }
  html.lite .hud-top .brand,
  html.lite .btn-musik,
  html.lite .btn-mode,
  html.lite .btn-lite,
  html.lite .btn-sosial,
  html.lite .hero-content,
  html.lite .sosial-dropdown,
  html.lite .ticker-bar {
    box-shadow: none !important;
  }
  html.lite * {
    transition-duration: .01s !important;
    transition-delay: 0s !important;
  }
  html, body {
    transition: background-color .35s ease, color .35s ease;
  }
  html.lite, html.lite body {
    transition: none !important;
  }
  .frame, .ticker-bar, .hero-overlay, .slide-hero, .slide-book, .slide-search, .slide-rules,
  .btn-musik, .btn-mode, .dot span, .meta-chip, .ticker-icon, .ticker-reopen {
    transition-property: background, background-color, border-color, box-shadow, color;
    transition-duration: .35s;
    transition-timing-function: ease;
  }

  html {
    scroll-behavior: smooth;
  }

  html, body {
    height: 100%;
    background: var(--bg);
    color: var(--text);
    font-family: var(--sans);
    overflow: hidden;
  }

  a { color: inherit; }

  /* ══════════════════ SLIDE CONTAINER ══════════════════ */
  .slides {
    height: 100vh;
    height: 100dvh;
    width: 100vw;
    overflow-y: scroll;
    overflow-x: hidden;
    scroll-snap-type: y mandatory;
    scrollbar-width: none;
    -ms-overflow-style: none;
  }
  .slides::-webkit-scrollbar { display: none; width: 0; height: 0; }
  /* jaga-jaga: kalau ada browser/webview yang tetap memaksa render scrollbar
     bawaan meski sudah di-hide di atas, dorong keluar layar supaya tidak
     muncul sebagai garis gelap di pinggir kanan */
  @supports not (scrollbar-width: none) {
    .slides {
      width: calc(100vw + 20px);
    }
  }

  .slide {
    position: relative;
    min-height: 100vh;
    min-height: 100dvh;
    height: auto;
    width: 100vw;
    scroll-snap-align: start;
    scroll-snap-stop: always;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow-x: hidden;
    overflow-y: visible;
  }

  /* ══════════════════ TOP BAR ══════════════════ */
  .hud-top {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 22px clamp(20px, 4vw, 56px);
    pointer-events: none;
  }
  .hud-top .brand {
    font-family: var(--serif);
    font-weight: 700;
    font-size: 1.05rem;
    letter-spacing: .28em;
    text-transform: uppercase;
    pointer-events: auto;
    /* Chip beku (frosted) sendiri, agar teks selalu kontras terlepas dari apa yang ada di baliknya
       (foto hero maupun latar terang/gelap saat scroll) — bukan lagi mengandalkan var(--text) polos */
    padding: 8px 18px;
    border-radius: 999px;
    background: rgba(9,12,16,.45);
    border: 1px solid var(--teal-dim);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    color: #eef3f4;
    text-shadow: 0 2px 12px rgba(0,0,0,.6);
    transition: background .35s ease, color .35s ease, border-color .35s ease;
  }
  .hud-top .brand span { color: var(--teal); }
  html.theme-light .hud-top .brand {
    background: rgba(255,255,255,.65);
    color: #221d14;
    text-shadow: none;
  }

  .btn-share-top {
    appearance: none;
    cursor: pointer;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 1px solid var(--teal-dim);
    background: rgba(12,17,22,.55);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--teal);
    transition: background .2s ease, border-color .2s ease, transform .18s ease, box-shadow .2s ease;
    box-shadow: 0 6px 18px rgba(0,0,0,.3);
    pointer-events: auto;
  }
  .btn-share-top:hover { transform: translateY(-1px); border-color: var(--teal); box-shadow: 0 8px 22px rgba(216,184,120,.25); }
  .btn-share-top svg { width: 18px; height: 18px; }
  html.theme-light .btn-share-top { background: rgba(255,255,255,.6); }

  
  /* ═══════════ SLIDE SEARCH KATALOG & KUTIPAN LITERASI ═══════════ */
  .slide-search {
    position: relative;
    background:
      radial-gradient(ellipse 60% 50% at 20% 20%, rgba(216,184,120,.09), transparent 60%),
      radial-gradient(ellipse 50% 40% at 85% 85%, rgba(216,184,120,.06), transparent 60%),
      linear-gradient(175deg, #090c10 0%, #0e151e 50%, #0a0e14 100%);
    padding: 64px 20px 80px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .slide-search::before {
    content: '';
    position: absolute;
    top: -50px;
    left: 20%;
    width: 360px;
    height: 360px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(216,184,120,.12), transparent 70%);
    pointer-events: none;
    animation: driftGlow 22s ease-in-out infinite;
  }
  html.theme-light .slide-search {
    background:
      radial-gradient(ellipse 60% 50% at 20% 20%, rgba(169,120,47,.08), transparent 60%),
      radial-gradient(ellipse 50% 40% at 85% 85%, rgba(169,120,47,.06), transparent 60%),
      linear-gradient(175deg, #f6f2e8 0%, #fcfaf4 50%, #f4eee1 100%);
  }
  html.theme-light .slide-search::before {
    background: radial-gradient(circle, rgba(169,120,47,.12), transparent 70%);
  }

  .slide-search .frame {
    width: 100%;
    max-width: 980px;
    padding: 38px clamp(20px, 4vw, 50px);
    display: flex;
    flex-direction: column;
    text-align: center;
    align-items: center;
  }
  .search-frame-content {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  .slide-search h2 {
    font-family: var(--serif);
    font-weight: 700;
    font-size: clamp(1.6rem, 2.6vw, 2.2rem);
    margin-bottom: 8px;
    color: var(--text);
  }
  .slide-search .lead {
    font-size: .88rem;
    color: var(--text-dim);
    font-weight: 300;
    margin-bottom: 20px;
    max-width: 58ch;
    line-height: 1.6;
  }

  /* Catalog Search Box */
  .landing-search-box {
    width: 100%;
    max-width: 680px;
    margin: 4px auto 0;
    display: flex;
    flex-direction: column;
    gap: 16px;
  }
  .landing-search-input-wrap {
    display: flex;
    align-items: center;
    background: rgba(18, 24, 32, 0.75);
    border: 1.5px solid rgba(216,184,120,.35);
    border-radius: 999px;
    padding: 6px 8px 6px 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,.45);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    transition: all .25s ease;
  }
  .landing-search-input-wrap:focus-within {
    border-color: var(--teal);
    box-shadow: 0 12px 35px rgba(216,184,120,.25);
    transform: translateY(-2px);
  }
  html.theme-light .landing-search-input-wrap {
    background: rgba(255, 255, 255, 0.9);
    border-color: rgba(169, 120, 47, 0.35);
    box-shadow: 0 8px 25px rgba(0,0,0,.08);
  }
  .landing-search-icon {
    width: 20px;
    height: 20px;
    color: var(--teal);
    margin-right: 12px;
    flex-shrink: 0;
  }
  .landing-search-input {
    flex: 1;
    background: transparent;
    border: none;
    outline: none;
    font-family: inherit;
    font-size: .95rem;
    color: #eef3f4;
  }
  html.theme-light .landing-search-input {
    color: #1e293b;
  }
  .landing-search-input::placeholder {
    color: rgba(238,243,244,.45);
  }
  html.theme-light .landing-search-input::placeholder {
    color: #8c8479;
  }
  .landing-search-btn {
    appearance: none;
    border: none;
    background: linear-gradient(135deg, #d8b878, #b8860b);
    color: #0b0f14;
    font-weight: 700;
    font-size: .84rem;
    letter-spacing: .03em;
    padding: 10px 22px;
    border-radius: 999px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: transform .2s ease, filter .2s ease;
    white-space: nowrap;
    flex-shrink: 0;
  }
  .landing-search-btn svg {
    width: 15px;
    height: 15px;
    flex-shrink: 0;
  }
  .landing-search-btn:hover {
    transform: scale(1.04);
    filter: brightness(1.1);
  }
  .btn-search-text-short {
    display: none;
  }
  .btn-search-text-full {
    display: inline;
  }

  /* Quick Genre Pills */
  .landing-genre-pills {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
    font-size: .78rem;
  }
  .genre-pill-label {
    color: var(--muted);
    font-weight: 600;
    font-size: .75rem;
  }
  .genre-pill {
    padding: 6px 14px;
    border-radius: 999px;
    background: rgba(216,184,120,.08);
    border: 1px solid rgba(216,184,120,.22);
    color: var(--text);
    text-decoration: none;
    font-size: .75rem;
    font-weight: 600;
    transition: all .18s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
  }
  .genre-pill:hover {
    background: rgba(216,184,120,.2);
    border-color: var(--teal);
    color: var(--teal);
    transform: translateY(-1px);
  }
  .genre-pill.genre-pill-all {
    color: var(--teal);
    border-color: var(--teal);
    background: rgba(216,184,120,.12);
  }
  html.theme-light .genre-pill {
    background: rgba(154,115,40,.07);
    border-color: rgba(154,115,40,.22);
    color: #4a433a;
  }
  html.theme-light .genre-pill:hover {
    background: rgba(154,115,40,.15);
    color: #8a6323;
  }

  /* QTE Card in Search Slide */
  .landing-qte-card {
    width: 100%;
    max-width: 680px;
    margin-top: 22px;
    background: rgba(15, 20, 27, 0.6);
    border: 1px solid rgba(216,184,120,.22);
    border-left: 3.5px solid var(--teal);
    border-radius: 12px;
    padding: 16px 22px;
    text-align: left;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    box-shadow: 0 8px 24px rgba(0,0,0,.25);
  }
  html.theme-light .landing-qte-card {
    background: rgba(255, 255, 255, 0.75);
    border-color: rgba(169, 120, 47, 0.22);
    border-left-color: #a9782f;
    box-shadow: 0 8px 20px rgba(0,0,0,.06);
  }
  .qte-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--teal);
    margin-bottom: 6px;
  }
  html.theme-light .qte-badge {
    color: #8a6323;
  }
  .qte-badge svg {
    width: 13px;
    height: 13px;
  }
  .qte-body {
    font-family: var(--serif);
    font-size: 1.05rem;
    font-style: italic;
    color: var(--text);
    line-height: 1.5;
    margin: 0;
  }
  .qte-author {
    display: block;
    margin-top: 5px;
    font-size: .78rem;
    font-style: normal;
    font-weight: 600;
    color: var(--text-dim);
  }


  .hud-actions { display: flex; align-items: center; gap: 12px; pointer-events: none; }

  /* ══════════════════ TOAST "LINK DISALIN" ══════════════════ */
  .share-toast {
    position: fixed;
    left: 50%;
    bottom: 28px;
    transform: translateX(-50%) translateY(16px);
    z-index: 200;
    padding: 12px 22px;
    border-radius: 999px;
    background: rgba(12,17,22,.9);
    border: 1px solid var(--teal-dim);
    color: #eef3f4;
    font-family: var(--sans);
    font-size: .8rem;
    font-weight: 500;
    letter-spacing: .02em;
    box-shadow: 0 14px 34px rgba(0,0,0,.35);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    opacity: 0;
    pointer-events: none;
    transition: opacity .25s ease, transform .25s ease;
  }
  html.theme-light .share-toast { background: rgba(255,255,255,.92); color: #221d14; }
  .share-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

  /* ══════════════════ TOMBOL MUSIK ══════════════════ */
  .btn-musik {
    pointer-events: auto;
    appearance: none;
    cursor: pointer;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 1px solid var(--teal-dim);
    background: rgba(12,17,22,.55);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--teal);
    transition: background .2s ease, border-color .2s ease, transform .18s ease, box-shadow .2s ease;
    box-shadow: 0 6px 18px rgba(0,0,0,.3);
  }
  .btn-musik:hover { transform: translateY(-1px); border-color: var(--teal); box-shadow: 0 8px 22px rgba(216,184,120,.25); }
  .btn-musik svg { width: 18px; height: 18px; }
  .btn-musik .icon-off { display: block; }
  .btn-musik .icon-eq  { display: none; align-items: flex-end; gap: 2px; height: 16px; }
  .btn-musik .icon-eq span {
    display: block;
    width: 3px;
    background: var(--teal);
    border-radius: 2px;
    animation: eqBar 1s ease-in-out infinite;
  }
  .btn-musik .icon-eq span:nth-child(1) { height: 40%; animation-delay: -.6s; }
  .btn-musik .icon-eq span:nth-child(2) { height: 100%; animation-delay: -.2s; }
  .btn-musik .icon-eq span:nth-child(3) { height: 65%; animation-delay: -.9s; }
  @keyframes eqBar { 0%,100% { transform: scaleY(.35); } 50% { transform: scaleY(1); } }
  html.theme-light .btn-musik { background: rgba(255,255,255,.6); }
  html.theme-light .btn-musik.playing { background: rgba(169,120,47,.14); }
  .btn-musik.playing { background: rgba(216,184,120,.16); border-color: var(--teal); }
  .btn-musik.playing .icon-off { display: none; }
  .btn-musik.playing .icon-eq  { display: flex; }
  @media (prefers-reduced-motion: reduce) {
    .btn-musik .icon-eq span { animation: none; transform: scaleY(.8); }
  }

  /* ══════════════════ TOMBOL MODE GELAP / TERANG ══════════════════ */
  .btn-mode {
    pointer-events: auto;
    appearance: none;
    cursor: pointer;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 1px solid var(--teal-dim);
    background: rgba(12,17,22,.55);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--teal);
    transition: background .2s ease, border-color .2s ease, transform .18s ease, box-shadow .2s ease;
    box-shadow: 0 6px 18px rgba(0,0,0,.3);
  }
  .btn-mode:hover { transform: translateY(-1px); border-color: var(--teal); box-shadow: 0 8px 22px rgba(216,184,120,.25); }
  .btn-mode svg { width: 18px; height: 18px; transition: transform .4s cubic-bezier(.22,1,.36,1), opacity .25s ease; }
  .btn-mode .icon-sun { display: none; }
  html.theme-light .btn-mode .icon-moon { display: none; }
  html.theme-light .btn-mode .icon-sun  { display: block; }
  html.theme-light .btn-mode { background: rgba(255,255,255,.6); }


  /* ══════════════════ TOMBOL TAMPILAN RINGAN ══════════════════ */
  .btn-lite {
    pointer-events: auto;
    appearance: none;
    cursor: pointer;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 1px solid var(--teal-dim);
    background: rgba(12,17,22,.55);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--teal);
    transition: background .2s ease, border-color .2s ease, transform .18s ease, box-shadow .2s ease;
    box-shadow: 0 6px 18px rgba(0,0,0,.3);
  }
  .btn-lite:hover { transform: translateY(-1px); border-color: var(--teal); box-shadow: 0 8px 22px rgba(216,184,120,.25); }
  .btn-lite svg { width: 18px; height: 18px; }
  html.theme-light .btn-lite { background: rgba(255,255,255,.6); }
  .btn-lite.active { background: rgba(216,184,120,.16); border-color: var(--teal); }
  html.theme-light .btn-lite.active { background: rgba(169,120,47,.14); }


  /* ══════════════════ SIDE DOT NAV ══════════════════ */
  .dot-nav {
    position: fixed;
    right: clamp(16px, 3vw, 34px);
    top: 50%;
    transform: translateY(-50%);
    z-index: 50;
    display: flex;
    flex-direction: column;
    gap: 18px;
  }
  .dot {
    appearance: none;
    background: none;
    border: none;
    width: 16px;
    height: 16px;
    padding: 0;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .dot span {
    display: block;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: rgba(238,243,244,.35);
    border: 1px solid rgba(238,243,244,.4);
    transition: all .3s ease;
  }
  .dot.active span {
    width: 9px;
    height: 9px;
    background: var(--teal);
    border-color: var(--teal);
    box-shadow: 0 0 10px var(--teal);
  }

  /* ══════════════════ CORNER FRAME (signature element) ══════════════════ */
  .frame {
    position: relative;
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 22px;
    box-shadow: 0 30px 80px -20px rgba(0,0,0,.5);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
  }
  /* aksen bracket digeser sedikit ke dalam & ujungnya dibulatkan agar menyatu halus dengan frame yang kini melengkung */
  .corner {
    position: absolute;
    width: 20px;
    height: 20px;
    pointer-events: none;
  }
  .corner::before, .corner::after { content:''; position:absolute; background: var(--teal); border-radius: 3px; }
  .corner-tl { top: 14px; left: 14px; }
  .corner-tl::before { width: 100%; height: 2px; top:0; left:0; }
  .corner-tl::after  { width: 2px; height: 100%; top:0; left:0; }
  .corner-tr { top: 14px; right: 14px; }
  .corner-tr::before { width: 100%; height: 2px; top:0; right:0; }
  .corner-tr::after  { width: 2px; height: 100%; top:0; right:0; }
  .corner-bl { bottom: 14px; left: 14px; }
  .corner-bl::before { width: 100%; height: 2px; bottom:0; left:0; }
  .corner-bl::after  { width: 2px; height: 100%; bottom:0; left:0; }
  .corner-br { bottom: 14px; right: 14px; }
  .corner-br::before { width: 100%; height: 2px; bottom:0; right:0; }
  .corner-br::after  { width: 2px; height: 100%; bottom:0; right:0; }

  .frame-label {
    position: absolute;
    top: -13px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--bg);
    padding: 0 12px;
    border-radius: 5px;
    font-size: .68rem;
    letter-spacing: .32em;
    color: var(--teal);
    font-weight: 500;
    text-transform: uppercase;
  }

  .eyebrow {
    font-size: .72rem;
    letter-spacing: .3em;
    text-transform: uppercase;
    color: var(--teal);
    font-weight: 500;
    margin-bottom: 14px;
    text-shadow: 0 2px 14px rgba(0,0,0,.6);
  }

  /* ══════════════════ SCROLL REVEAL (slide 2 & 3) ══════════════════ */
  .reveal {
    opacity: 0;
    transform: translateY(34px);
    transition: opacity .8s cubic-bezier(.22,1,.36,1), transform .8s cubic-bezier(.22,1,.36,1);
  }
  .reveal.in-view { opacity: 1; transform: translateY(0); }
  .reveal-stagger .reveal-item { opacity: 0; }
  .reveal-stagger.in-view .reveal-item {
    animation: revealItem .7s cubic-bezier(.22,1,.36,1) both;
  }
  .reveal-stagger.in-view .reveal-item:nth-child(1) { animation-delay: .05s; }
  .reveal-stagger.in-view .reveal-item:nth-child(2) { animation-delay: .12s; }
  .reveal-stagger.in-view .reveal-item:nth-child(3) { animation-delay: .19s; }
  .reveal-stagger.in-view .reveal-item:nth-child(4) { animation-delay: .26s; }
  .reveal-stagger.in-view .reveal-item:nth-child(5) { animation-delay: .33s; }
  .reveal-stagger.in-view .reveal-item:nth-child(6) { animation-delay: .40s; }
  @keyframes revealItem { from { opacity:0; transform:translateY(18px); } to { opacity:1; transform:translateY(0); } }
  @media (prefers-reduced-motion: reduce) {
    .reveal, .reveal-item { opacity: 1 !important; transform: none !important; animation: none !important; transition: none !important; }
  }

  /* ══════════════════ SLIDE 1 — HERO ══════════════════ */
  .slide-hero {
    background: radial-gradient(circle at 30% 20%, #1f1912 0%, var(--bg) 60%);
  }
  .hero-overlay {
    position: absolute;
    inset: 0;
    z-index: 1;
    background:
      linear-gradient(180deg, rgba(9,12,16,.68) 0%, rgba(9,12,16,.34) 26%, rgba(9,12,16,.42) 50%, rgba(9,12,16,.7) 74%, rgba(9,12,16,.97) 100%),
      linear-gradient(90deg, rgba(9,12,16,.6) 0%, transparent 42%, transparent 58%, rgba(9,12,16,.6) 100%);
  }
  /* orbs cahaya lembut yang melayang perlahan — mempercantik hero tanpa mengganggu keterbacaan */
  .hero-glow {
    position: absolute;
    inset: 0;
    z-index: 1;
    pointer-events: none;
    overflow: hidden;
  }
  .hero-glow span {
    position: absolute;
    border-radius: 50%;
    filter: blur(60px);
    opacity: .35;
    background: radial-gradient(circle, rgba(216,184,120,.9), transparent 70%);
    animation: driftGlow 14s ease-in-out infinite;
  }
  .hero-glow span:nth-child(1) { width: 340px; height: 340px; top: 8%;  left: 8%;  animation-duration: 16s; }
  .hero-glow span:nth-child(2) { width: 260px; height: 260px; bottom: 12%; right: 10%; animation-duration: 19s; animation-delay: -4s; }
  .hero-glow span:nth-child(3) { width: 200px; height: 200px; top: 45%; right: 22%; animation-duration: 13s; animation-delay: -8s; opacity: .22; }
  @keyframes driftGlow {
    0%, 100% { transform: translate(0,0) scale(1); }
    50%      { transform: translate(24px,-18px) scale(1.12); }
  }
  @media (prefers-reduced-motion: reduce) {
    .hero-glow span { animation: none; }
  }

  .hero-content {
    position: relative;
    z-index: 2;
    text-align: center;
    padding: 38px clamp(17px, 5vw, 60px);
    max-width: min(780px, 88vw);
    /* panel lembut di belakang teks agar tidak "menyatu" dengan gambar/video apa pun di baliknya */
    background: radial-gradient(ellipse 100% 100% at 50% 50%, rgba(6,8,11,.55) 0%, rgba(6,8,11,.28) 55%, transparent 82%);
    border-radius: 32px;
    backdrop-filter: blur(6px) saturate(1.05);
    -webkit-backdrop-filter: blur(6px) saturate(1.05);
  }
  .hero-content h1 {
    font-family: var(--serif);
    font-weight: 700;
    font-size: clamp(2.3rem, 7.5vw, 5.4rem);
    letter-spacing: .04em;
    line-height: 1;
    color: var(--text);
    background: linear-gradient(180deg, #fff 30%, var(--teal) 130%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 0 20px 60px rgba(0,0,0,.5);
  }
  @supports not ((-webkit-background-clip: text) or (background-clip: text)) {
    .hero-content h1 { color: var(--text); background: none; }
  }
  .hero-content .tagline {
    margin-top: 18px;
    font-size: clamp(.9rem, 1.6vw, 1.1rem);
    font-weight: 300;
    letter-spacing: .04em;
    color: var(--text-dim);
    text-shadow: 0 2px 16px rgba(0,0,0,.6);
  }
  .cta-glow {
    display: inline-block;
    margin-top: 40px;
    padding: 14px 46px;
    border-radius: 999px;
    text-decoration: none;
    font-size: .78rem;
    font-weight: 500;
    letter-spacing: .2em;
    text-transform: uppercase;
    color: var(--bg);
    background: linear-gradient(135deg, var(--teal), #f0d9a8);
    box-shadow: 0 10px 34px rgba(216,184,120,.35);
    transition: transform .18s ease, box-shadow .18s ease;
  }
  .cta-glow:hover { transform: translateY(-2px); box-shadow: 0 14px 40px rgba(216,184,120,.5); }

  .scroll-hint {
    position: absolute;
    bottom: 96px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 2;
    text-align: center;
    font-size: .68rem;
    letter-spacing: .2em;
    text-transform: uppercase;
    color: var(--text-faint);
  }
  .scroll-hint .chevron {
    display: block;
    margin-top: 6px;
    font-size: 1rem;
    animation: bob 1.8s ease-in-out infinite;
    color: var(--teal);
  }

  /* ══════════════════ GAMBAR LATAR SLIDE 1 (statis / dinamis, diatur di Kelola Banner) ══════════════════ */
  .hero-bg {
    position: absolute;
    inset: 0;
    z-index: 0;
    overflow: hidden; /* kunci efek zoom background biar tidak melebar keluar slide dan merusak scroll-snap */
  }
  .hero-bg-slide {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    opacity: 0;
    transform: scale(1.08);
    transition: opacity 1.4s ease;
  }
  /* GIF & VIDEO banner: gunakan <img>/<video> agar animasi/video tetap jalan */
  .hero-bg-slide.is-gif,
  .hero-bg-slide.is-video { background-image: none !important; }
  .hero-bg-gif,
  .hero-bg-video {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
  }
  /* GIF & VIDEO tidak perlu efek zoom */
  .hero-bg-slide.is-gif.active,
  .hero-bg-slide.is-video.active { animation: none; }
  .hero-bg-slide.active {
    opacity: 1;
    animation: heroZoom 16s ease-out forwards;
  }

  @keyframes bob { 0%,100% { transform: translateY(0);} 50% { transform: translateY(6px);} }
  @keyframes riseIn { from { opacity:0; transform:translateY(28px);} to { opacity:1; transform:translateY(0);} }
  @keyframes heroZoom { from { transform: scale(1.08); } to { transform: scale(1); } }
  @media (prefers-reduced-motion: reduce) {
    .hero-bg-slide, .hero-bg-slide.active { animation: none; transform: none; }
  }

  /* ══════════════════ SLIDE 2 — BUKU TERBARU ══════════════════ */
  .slide-book {
    position: relative;
    background:
      radial-gradient(ellipse 60% 50% at 78% 18%, rgba(216,184,120,.10), transparent 60%),
      radial-gradient(ellipse 50% 40% at 10% 90%, rgba(216,184,120,.05), transparent 60%),
      linear-gradient(165deg, #101823 0%, #0a0e14 55%, #090c10 100%);
    padding: 40px clamp(20px, 6vw, 80px) 64px;
    overflow: hidden;
  }
  .slide-book::before,
  .slide-rules::before {
    content: '';
    position: absolute;
    width: 380px;
    height: 380px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(216,184,120,.14), transparent 70%);
    filter: blur(50px);
    pointer-events: none;
    z-index: 0;
    animation: driftGlow 20s ease-in-out infinite;
  }
  .slide-book::before  { top: -60px; right: -60px; }
  .slide-rules::before { bottom: -80px; left: -60px; animation-duration: 24s; }
  @media (prefers-reduced-motion: reduce) {
    .slide-book::before, .slide-rules::before { animation: none; }
  }
  .slide-book .frame {
    width: 100%;
    max-width: 1220px;
    padding: 26px clamp(18px, 4vw, 48px);
    display: flex;
    flex-direction: column;
    gap: 20px;
  }
  /* varian bingkai beraksen emas khusus untuk kartu buku, mengikuti palet referensi */
  .frame-gold { border-color: rgba(216,184,120,.32); }
  .frame-gold .frame-label { color: var(--gold); }
  .frame-gold .corner::before,
  .frame-gold .corner::after { background: var(--gold); }

  .book-layout {
    display: grid;
    grid-template-columns: 1.08fr .92fr;
    gap: 30px;
    align-items: center;
  }
  .book-info .author {
    font-size: .82rem;
    color: var(--text-dim);
    margin-bottom: 10px;
    font-weight: 300;
    letter-spacing: .02em;
  }
  .book-info h2 {
    font-family: var(--serif);
    font-weight: 700;
    font-size: clamp(1.1rem, 1.9vw, 1.4rem);
    line-height: 1.06;
    margin-bottom: 10px;
    color: var(--text);
    text-shadow: 0 2px 24px rgba(0,0,0,.4);
  }
  .book-info .author strong { color: var(--gold); font-weight: 500; }

  /* baris meta ala "rating / durasi" pada referensi, memakai data yang tersedia */
  .meta-row { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
  .meta-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: .78rem;
    font-weight: 400;
    color: var(--text-dim);
    padding: 6px 14px 6px 10px;
    border-radius: 999px;
    background: rgba(255,255,255,.045);
    border: 1px solid rgba(255,255,255,.09);
    letter-spacing: .01em;
  }
  .meta-chip svg { width: 15px; height: 15px; flex: 0 0 auto; color: var(--gold); }
  .meta-chip--status svg { color: #7fe0a8; }

  .book-info .sinopsis {
    font-size: .9rem;
    line-height: 1.55;
    color: var(--text-dim);
    font-weight: 300;
    margin-bottom: 14px;
    max-width: 54ch;
  }

  .book-actions { display: flex; flex-wrap: wrap; gap: 12px; }
  .cta-solid,
  .cta-outline {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    font-size: .76rem;
    font-weight: 600;
    letter-spacing: .14em;
    text-transform: uppercase;
    border-radius: 999px;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease;
  }
  .cta-solid svg, .cta-outline svg { width: 15px; height: 15px; }
  .cta-solid {
    padding: 12px 30px;
    color: #241a0a;
    background: linear-gradient(135deg, #ecd19f, var(--gold) 55%, #c89f5e);
    box-shadow: 0 14px 32px -6px rgba(216,184,120,.55);
  }
  .cta-solid:hover { transform: translateY(-2px); box-shadow: 0 18px 40px -6px rgba(216,184,120,.7); }
  .cta-outline {
    padding: 11px 26px;
    font-weight: 500;
    border: 1.4px solid rgba(216,184,120,.55);
    color: var(--gold);
    background: rgba(216,184,120,.05);
  }
  .cta-outline:hover { background: rgba(216,184,120,.16); border-color: var(--gold); color: #f4e3bb; box-shadow: 0 10px 26px rgba(216,184,120,.22); }

  /* ── cover buku bergaya hardcover 3D, meniru mockup pada referensi ── */
  .book-cover-wrap {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 220px;
    cursor: pointer;
  }
  .book-cover-wrap:focus-visible { outline: 2px solid var(--teal); outline-offset: 6px; border-radius: 10px; }
  .cover-glow {
    position: absolute;
    width: 78%;
    aspect-ratio: 1;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(216,184,120,.38) 0%, rgba(216,184,120,0) 68%);
    filter: blur(46px);
    opacity: .85;
    transition: opacity .5s ease, transform .5s ease;
    z-index: 0;
  }
  .book-cover-wrap:hover .cover-glow { opacity: 1; transform: scale(1.15); }
  .book-cover {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 172px;
    margin: 0 auto;
    aspect-ratio: 3 / 4.35;
    /* sudut kiri (arah punggung buku) rata, sudut kanan sedikit membulat seperti tumpukan halaman */
    border-radius: 3px 10px 10px 3px;
    transform: rotate(-2deg) perspective(900px) rotateY(8deg);
    transform-style: preserve-3d;
    transition: transform .5s cubic-bezier(.22,1,.36,1);
  }
  .book-cover-wrap:hover .book-cover { transform: rotate(0deg) translateY(-8px) perspective(900px) rotateY(4deg); }

  /* Animasi sederhana saat cover diklik — buku miring sedikit, lalu ikon-ikon minat baca "keluar" dari cover
     satu per satu secara acak (urutan & jeda diatur lewat JavaScript, lihat script di bawah) */
  .book-cover-wrap.opening .book-cover {
    animation: miringBuku 2.4s cubic-bezier(.22,1,.36,1) both;
  }
  @keyframes miringBuku {
    0%   { transform: rotate(-2deg) perspective(900px) rotateY(8deg); }
    15%  { transform: rotate(-13deg) perspective(900px) rotateY(20deg) translateY(-6px); }
    75%  { transform: rotate(-13deg) perspective(900px) rotateY(20deg) translateY(-6px); }
    100% { transform: rotate(-2deg) perspective(900px) rotateY(8deg); }
  }

  /* Ikon/gambar (lampu, pantai, gunung, kaset, pensil) yang muncul satu-satu di sekitar cover saat diklik.
     Ukuran dibuat lebih besar & memakai emoji sebagai "gambar" supaya lebih hidup & berwarna. */
  .cover-icon {
    position: absolute;
    top: 50%; left: 50%;
    width: 62px; height: 62px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(9,12,16,.62);
    border: 1.5px solid var(--teal-dim);
    font-size: 32px;
    line-height: 1;
    opacity: 0;
    transform: translate(-50%,-50%) scale(.2);
    pointer-events: none;
    z-index: 3;
    box-shadow: 0 10px 24px rgba(0,0,0,.4);
    transition: opacity .45s ease, transform .45s ease;
    /* jeda kemunculan diisi lewat JS (inline style) agar urutannya bisa diacak setiap klik */
    animation-delay: 0s;
  }
  html.theme-light .cover-icon {
    background: rgba(255,255,255,.9);
    box-shadow: 0 10px 24px rgba(120,100,60,.25);
  }
  .icon-lampu  { --ix: -128px; --iy: -80px; }
  .icon-pantai { --ix: 128px;  --iy: -80px; }
  .icon-gunung { --ix: -148px; --iy: 52px; }
  .icon-kaset  { --ix: 148px;  --iy: 52px; }
  .icon-pensil { --ix: 0px;    --iy: 152px; }

  @keyframes munculIkonBuku {
    0%   { opacity: 0; transform: translate(-50%,-50%) scale(.2) rotate(-18deg); }
    55%  { opacity: 1; }
    100% { opacity: 1; transform: translate(calc(-50% + var(--ix)), calc(-50% + var(--iy))) scale(1) rotate(0deg); }
  }
  .book-cover-wrap.opening .cover-icon {
    animation-name: munculIkonBuku;
    animation-duration: .8s;
    animation-timing-function: cubic-bezier(.22,1,.36,1);
    animation-fill-mode: forwards;
    /* animation-delay masing-masing ikon diberikan lewat JS secara acak, lihat script "Animasi buka buku" */
  }

  @media (prefers-reduced-motion: reduce) {
    .book-cover-wrap.opening .book-cover { animation: none; }
    .book-cover-wrap.opening .cover-icon { animation: none; }
  }
  .book-cover::before {
    /* tumpukan halaman di sisi kanan-bawah, memberi kesan buku tebal & nyata */
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    box-shadow:
      3px 4px 0 0 #eee6d3,
      6px 8px 0 0 #ddd3ba,
      9px 12px 0 0 #cbc0a3,
      10px 14px 26px 1px rgba(0,0,0,.4),
      0 20px 46px rgba(0,0,0,.38);
    transition: box-shadow .5s cubic-bezier(.22,1,.36,1);
    z-index: -1;
  }
  /* shadow sedikit membesar & melembut ketika kursor mengarah ke cover, tanpa berlebihan */
  .book-cover-wrap:hover .book-cover::before {
    box-shadow:
      3px 4px 0 0 #eee6d3,
      6px 8px 0 0 #ddd3ba,
      9px 12px 0 0 #cbc0a3,
      12px 17px 32px 2px rgba(0,0,0,.42),
      0 26px 56px rgba(0,0,0,.4),
      0 0 40px rgba(216,184,120,.18);
  }
  .book-cover img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
    border-radius: inherit;
    filter: saturate(1.32) contrast(1.1) brightness(1.03);
    transition: opacity .35s ease, filter .35s ease;
  }
  .book-cover::after {
    /* kilau permukaan + bayangan punggung buku (spine) di tepi kiri agar terasa 3D seperti hardcover asli */
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background:
      linear-gradient(90deg, rgba(0,0,0,.55) 0%, rgba(0,0,0,.28) 2.5%, rgba(0,0,0,0) 7%),
      linear-gradient(90deg, rgba(255,255,255,.22) 7.5%, rgba(255,255,255,.06) 9%, rgba(255,255,255,0) 11%),
      linear-gradient(120deg, rgba(255,255,255,.24) 0%, rgba(255,255,255,0) 30%, rgba(255,255,255,0) 72%, rgba(0,0,0,.2) 100%);
    pointer-events: none;
  }

  /* ── carousel "buku serupa" — kartu lebih besar & jenuh warnanya ── */
  .similar-section { border-top: 1px solid rgba(255,255,255,.09); padding-top: 14px; }
  .similar-label {
    font-size: .7rem;
    letter-spacing: .3em;
    text-transform: uppercase;
    color: var(--text-faint);
    font-weight: 500;
    margin-bottom: 8px;
  }
  .book-thumbs {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    padding: 4px 2px 10px;
  }
  .thumb {
    appearance: none;
    border: none;
    background: none;
    padding: 0;
    cursor: pointer;
    flex: 0 0 auto;
    width: 96px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    opacity: .82;
    transition: opacity .25s ease, transform .3s ease;
  }
  .thumb-cover {
    position: relative;
    width: 100%;
    aspect-ratio: 3 / 4.35;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid transparent;
    box-shadow: 0 16px 34px -8px rgba(0,0,0,.55);
    transition: box-shadow .3s ease, border-color .3s ease, transform .3s ease;
  }
  .thumb:hover .thumb-cover { box-shadow: 0 22px 44px -8px rgba(0,0,0,.6); }
  .thumb img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
    filter: saturate(1.25) contrast(1.08);
    transition: transform .45s ease, filter .3s ease;
  }
  .thumb-title {
    font-size: .72rem;
    font-weight: 400;
    color: var(--text-dim);
    text-align: center;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: color .25s ease;
  }
  .thumb:hover { opacity: 1; transform: translateY(-5px); }
  .thumb:hover img { transform: scale(1.05); }
  .thumb.active { opacity: 1; }
  .thumb.active .thumb-cover {
    border-color: var(--gold);
    box-shadow: 0 0 0 1px rgba(216,184,120,.5), 0 18px 38px -6px rgba(216,184,120,.4);
  }
  .thumb.active .thumb-title { color: var(--gold); }

  /* ══════════════════ SLIDE 3 — TATA TERTIB ══════════════════ */
  .slide-rules {
    position: relative;
    background: radial-gradient(circle at 70% 30%, #131b21 0%, var(--bg) 65%);
    padding: 56px 20px 72px;
    overflow: hidden;
  }
  .slide-rules .frame {
    width: 100%;
    max-width: 980px;
    padding: 32px clamp(20px, 4vw, 44px);
  }
  .slide-rules h2 {
    font-family: var(--serif);
    font-weight: 700;
    font-size: clamp(1.6rem, 2.6vw, 2.05rem);
    margin-bottom: 6px;
  }
  .slide-rules .lead {
    font-size: .82rem;
    color: var(--text-dim);
    font-weight: 300;
    margin-bottom: 18px;
    max-width: 56ch;
  }
  .rules-list {
    list-style: none;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
  }
  @media (max-width: 900px) {
    .rules-list { grid-template-columns: repeat(2, 1fr); }
  }
  .rules-list li {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 18px;
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 14px;
    background: rgba(255,255,255,.02);
  }
  .rules-list .num {
    font-family: var(--serif);
    font-weight: 700;
    font-size: 1.2rem;
    color: var(--teal);
  }
  .rules-list h3 {
    font-size: .96rem;
    font-weight: 500;
    letter-spacing: .01em;
    margin-bottom: 2px;
    color: var(--text);
  }
  .rules-list p {
    font-size: .82rem;
    font-weight: 300;
    color: var(--text-dim);
    line-height: 1.5;
  }

  /* ══════════════════ SLIDE 4 — TENTANG KATALOG (kartu ikon, sengaja berbeda dari Tata Tertib) ══════════════════ */
  .slide-about {
    position: relative;
    background:
      radial-gradient(ellipse 55% 45% at 12% 12%, rgba(216,184,120,.10), transparent 62%),
      radial-gradient(ellipse 60% 50% at 88% 92%, rgba(216,184,120,.07), transparent 60%),
      linear-gradient(200deg, #101823 0%, #0a0e14 55%, #090c10 100%);
    padding: 64px clamp(20px, 6vw, 80px) 72px;
    overflow: hidden;
  }
  .about-wrap {
    width: 100%;
    max-width: 1180px;
    margin: 0 auto;
  }
  .about-head {
    text-align: center;
    max-width: 640px;
    margin: 0 auto 32px;
  }
  .about-head h2 { margin-bottom: 10px; }
  .about-head .lead { margin: 0 auto; }
  .about-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
  }
  .about-card {
    position: relative;
    padding: 22px 20px;
    border-radius: 18px;
    background: var(--panel);
    border: 1px solid rgba(255,255,255,.08);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    transition: transform .3s cubic-bezier(.22,1,.36,1), border-color .3s ease, box-shadow .3s ease;
  }
  .about-card:hover {
    transform: translateY(-6px);
    border-color: rgba(216,184,120,.4);
    box-shadow: 0 20px 44px -14px rgba(0,0,0,.5);
  }
  .about-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    margin-bottom: 12px;
    background: rgba(216,184,120,.12);
    border: 1px solid rgba(216,184,120,.3);
    color: var(--gold);
  }
  .about-icon svg { width: 20px; height: 20px; }
  .about-card h3 {
    font-size: .92rem;
    font-weight: 500;
    margin-bottom: 6px;
    color: var(--text);
  }
  .about-card p {
    font-size: .8rem;
    font-weight: 300;
    color: var(--text-dim);
    line-height: 1.55;
  }
  html.theme-light .about-card { border-color: rgba(0,0,0,.07); }
  html.theme-light .about-card:hover { border-color: rgba(169,120,47,.4); }
  html.theme-light .about-icon { background: rgba(169,120,47,.1); border-color: rgba(169,120,47,.32); }

  /* ══════════════════ SLIDE 5 — LOKASI PERPUSTAKAAN ══════════════════ */
  .slide-location {
    position: relative;
    background: radial-gradient(circle at 22% 78%, #131b21 0%, var(--bg) 65%);
    padding: 56px 20px 72px;
    overflow: hidden;
  }
  .slide-location .frame {
    width: 100%;
    max-width: 1080px;
    padding: 34px clamp(20px, 4vw, 46px);
  }
  .location-grid {
    display: grid;
    grid-template-columns: 1.05fr 1fr;
    gap: 36px;
    align-items: stretch;
  }
  .location-info h2 {
    font-family: var(--serif);
    font-weight: 700;
    font-size: clamp(1.6rem, 2.6vw, 2.05rem);
    margin-bottom: 6px;
  }
  .location-info .lead {
    font-size: .85rem;
    color: var(--text-dim);
    font-weight: 300;
    margin-bottom: 22px;
    max-width: 46ch;
  }
  .location-list { list-style: none; display: flex; flex-direction: column; gap: 16px; margin-bottom: 26px; }
  .location-list li { display: flex; gap: 14px; align-items: flex-start; }
  .location-icon {
    flex: 0 0 auto;
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 11px;
    background: rgba(216,184,120,.12);
    border: 1px solid rgba(216,184,120,.3);
    color: var(--gold);
  }
  .location-icon svg { width: 18px; height: 18px; }
  .location-list h4 { font-size: .85rem; font-weight: 500; color: var(--text); margin-bottom: 2px; }
  .location-list p { font-size: .82rem; font-weight: 300; color: var(--text-dim); line-height: 1.5; }
  .status-live-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: .65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    padding: 2px 7px;
    border-radius: 50px;
    margin-left: 6px;
    vertical-align: middle;
  }
  .status-live-chip.chip-open {
    background: rgba(16,185,129,.18);
    color: #34d399;
    border: 1px solid rgba(16,185,129,.4);
  }
  .status-live-chip.chip-open::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #34d399;
    box-shadow: 0 0 6px #34d399;
  }
  .status-live-chip.chip-closed {
    background: rgba(239,68,68,.16);
    color: #f87171;
    border: 1px solid rgba(239,68,68,.4);
  }
  .status-live-chip.chip-closed::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #f87171;
    box-shadow: 0 0 6px #f87171;
  }
  html.theme-light .status-live-chip.chip-open {
    background: #ecfdf5 !important;
    color: #065f46 !important;
    border: 1px solid #10b981 !important;
  }
  html.theme-light .status-live-chip.chip-open::before {
    background: #059669 !important;
    box-shadow: 0 0 6px #059669 !important;
  }
  html.theme-light .status-live-chip.chip-closed {
    background: #fef2f2 !important;
    color: #991b1b !important;
    border: 1px solid #ef4444 !important;
  }
  html.theme-light .status-live-chip.chip-closed::before {
    background: #dc2626 !important;
    box-shadow: 0 0 6px #dc2626 !important;
  }
  .location-map {
    position: relative;
    min-height: 280px;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid var(--line);
    filter: saturate(.4) brightness(.85) contrast(1.05);
    transition: filter .3s ease;
  }
  .location-map:hover { filter: saturate(.85) brightness(.96) contrast(1); }
  .location-map iframe { position: absolute; inset: 0; width: 100%; height: 100%; }
  html.theme-light .slide-location { background: radial-gradient(circle at 22% 78%, #f5efdf 0%, var(--bg) 65%); }
  html.theme-light .location-map { filter: saturate(.75) brightness(1.02); border-color: rgba(0,0,0,.09); }
  html.theme-light .location-map:hover { filter: none; }

  @media (max-width: 900px) {
    .location-grid { grid-template-columns: 1fr; }
    .location-map { min-height: 240px; }
  }
  @media (max-width: 680px) {
    .slide-location { padding-left: 14px; padding-right: 14px; padding-top: 76px; padding-bottom: 96px; }
  }
  body.ticker-hidden .slide-location { padding-bottom: 40px; }

  /* ══════════════════ FOOTER (slide 5) ══════════════════ */
  /* ══════════════════ SLIDE 5 — FOOTER ══════════════════ */
  .slide-footer {
    background: linear-gradient(180deg, #0a0e14 0%, #070a0d 100%);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100vh;
    height: 100dvh;
    min-height: 100vh;
    min-height: 100dvh;
    overflow: hidden;
    position: relative;
    box-sizing: border-box;
    padding-top: clamp(28px, 5vh, 56px);
  }

  /* orbs cahaya lembut, senada dengan hero, agar area atas footer tidak terasa kosong/polos */
  .footer-glow {
    position: absolute;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    overflow: hidden;
  }
  .footer-glow span {
    position: absolute;
    border-radius: 50%;
    filter: blur(70px);
    opacity: .28;
    background: radial-gradient(circle, rgba(216,184,120,.9), transparent 70%);
    animation: driftGlow 18s ease-in-out infinite;
  }
  .footer-glow span:nth-child(1) { width: 320px; height: 320px; top: 6%; left: 10%; }
  .footer-glow span:nth-child(2) { width: 260px; height: 260px; top: 14%; right: 12%; animation-duration: 22s; animation-delay: -6s; }
  @media (prefers-reduced-motion: reduce) {
    .footer-glow span { animation: none; }
  }

  /* ── ajakan bertindak di atas footer, mengisi ruang atas slide terakhir ── */
  .footer-cta {
    position: relative;
    z-index: 1;
    max-width: 760px;
    margin: 0 auto;
    padding: clamp(16px, 3vh, 32px) clamp(20px, 5vw, 60px) clamp(12px, 2vh, 20px);
    text-align: center;
    width: 100%;
    box-sizing: border-box;
  }
  .footer-cta h2 {
    font-family: var(--serif);
    font-weight: 700;
    font-size: clamp(1.35rem, 2.5vw + 0.8vh, 2.1rem);
    line-height: 1.2;
    color: var(--text);
    margin-bottom: 8px;
    text-shadow: 0 2px 24px rgba(0,0,0,.4);
  }
  .footer-cta .lead {
    font-size: clamp(.8rem, 1.2vw, .88rem);
    font-weight: 300;
    line-height: 1.55;
    color: var(--text-dim);
    max-width: 52ch;
    margin: 0 auto clamp(12px, 2vh, 18px);
  }
  .footer-highlights {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 8px;
    margin-bottom: clamp(14px, 2vh, 20px);
  }
  .footer-cta-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 12px;
  }
  html.theme-light .slide-footer {
    background: linear-gradient(180deg, #ffffff 0%, #f6f2e8 100%);
  }
  html.theme-light .footer-glow span { opacity: .16; }

  .site-footer {
    position: relative;
    width: 100%;
    border-top: 1px solid rgba(216,184,120,.18);
    padding: clamp(16px, 2.5vh, 26px) clamp(20px, 5vw, 80px) clamp(62px, 9vh, 82px);
    overflow: hidden;
    margin-top: auto;
    box-sizing: border-box;
  }
  .footer-top {
    max-width: 1220px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1.6fr 1fr 1fr 1fr;
    gap: clamp(16px, 3vw, 40px);
    padding-bottom: clamp(12px, 2vh, 24px);
    border-bottom: 1px solid rgba(255,255,255,.08);
  }
  .footer-brand .brand {
    font-family: var(--serif);
    font-size: 1.3rem;
    font-weight: 600;
    letter-spacing: .04em;
    color: var(--text);
    margin-bottom: 8px;
  }
  .footer-brand .brand span { color: var(--gold); }
  .footer-brand p {
    font-size: .78rem;
    font-weight: 300;
    line-height: 1.5;
    color: var(--text-dim);
    max-width: 320px;
  }
  .footer-col h4 {
    font-family: var(--sans);
    font-size: .68rem;
    font-weight: 500;
    letter-spacing: .2em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 10px;
  }
  .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 7px; }
  .footer-col a {
    font-size: .82rem;
    font-weight: 300;
    color: var(--text-dim);
    text-decoration: none;
    transition: color .25s ease, padding-left .25s ease;
  }
  .footer-col a:hover { color: var(--gold); padding-left: 3px; }
  .footer-social-links { display: flex; gap: 12px; }
  .footer-social-links a {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(216,184,120,.22);
    color: var(--text-dim);
    transition: color .25s ease, border-color .25s ease, transform .25s ease, background .25s ease;
  }
  .footer-social-links a svg { width: 16px; height: 16px; }
  .footer-social-links a:hover {
    color: var(--gold);
    border-color: var(--gold);
    background: rgba(216,184,120,.1);
    transform: translateY(-3px);
  }
  .footer-bottom {
    max-width: 1220px;
    margin: 0 auto;
    padding-top: clamp(10px, 1.6vh, 18px);
    text-align: center;
  }
  .footer-bottom p {
    font-size: .72rem;
    font-weight: 300;
    color: var(--text-faint);
    letter-spacing: .02em;
  }
  html.theme-light .site-footer {
    border-top-color: rgba(150,110,45,.22);
  }
  html.theme-light .footer-top { border-bottom-color: rgba(0,0,0,.08); }
  html.theme-light .footer-social-links a { background: rgba(0,0,0,.04); }
  @media (max-width: 900px) {
    .footer-top { grid-template-columns: 1fr 1fr; row-gap: 20px; column-gap: 24px; }
    .footer-brand { grid-column: 1 / -1; }
    .footer-brand p { max-width: 100%; }
  }
  @media (max-width: 680px) {
    .slide-footer {
      height: auto !important;
      min-height: 100vh;
      min-height: 100dvh;
      overflow-y: visible !important;
      justify-content: flex-start !important;
      padding-top: 50px;
      padding-bottom: 24px;
    }
    .footer-cta {
      padding: 20px 16px 20px;
    }
    .footer-cta h2 {
      font-size: clamp(1.25rem, 5.5vw, 1.65rem);
      margin-bottom: 8px;
      line-height: 1.25;
    }
    .footer-cta .lead {
      font-size: .8rem;
      line-height: 1.5;
      margin-bottom: 14px;
      max-width: 100%;
    }
    .footer-highlights {
      gap: 6px;
      margin-bottom: 16px;
    }
    .footer-highlights .meta-chip {
      font-size: .68rem;
      padding: 4px 10px;
    }
    .footer-cta-actions {
      gap: 10px;
      width: 100%;
    }
    .footer-cta-actions .cta-solid,
    .footer-cta-actions .cta-outline {
      padding: 11px 20px;
      font-size: .82rem;
    }
    .site-footer {
      padding: 24px 18px clamp(68px, 11vh, 88px);
      margin-top: 24px;
    }
    body.ticker-hidden .site-footer {
      padding-bottom: 24px;
    }
    .footer-top {
      grid-template-columns: 1fr 1fr;
      grid-template-areas:
        "brand brand"
        "nav   akun"
        "nav   social";
      row-gap: 18px;
      column-gap: 20px;
      padding-bottom: 18px;
      text-align: left;
    }
    .footer-brand {
      grid-area: brand;
      text-align: center;
      margin-bottom: 2px;
    }
    .footer-brand .brand {
      margin-bottom: 4px;
      font-size: 1.15rem;
    }
    .footer-brand p {
      margin: 0 auto;
      font-size: .74rem;
      line-height: 1.45;
      max-width: 360px;
    }
    .footer-nav { grid-area: nav; }
    .footer-acc { grid-area: akun; }
    .footer-social { grid-area: social; }
    .footer-col h4 {
      font-size: .66rem;
      letter-spacing: .15em;
      margin-bottom: 8px;
    }
    .footer-col ul {
      gap: 6px;
      align-items: flex-start;
    }
    .footer-col a {
      font-size: .78rem;
      padding: 2px 0;
    }
    .footer-social-links {
      justify-content: flex-start;
      gap: 8px;
    }
    .footer-social-links a {
      width: 32px;
      height: 32px;
    }
    .footer-bottom {
      padding-top: 14px;
    }
    .footer-bottom p {
      font-size: .68rem;
      line-height: 1.45;
    }
  }
  @media (max-width: 440px) {
    .footer-cta-actions {
      flex-direction: column;
      align-items: stretch;
      max-width: 290px;
      margin: 0 auto;
    }
    .footer-cta-actions .cta-solid,
    .footer-cta-actions .cta-outline {
      width: 100%;
      justify-content: center;
    }
  }

  /* ══════════════════ BOTTOM TICKER BAR ══════════════════ */
  .ticker-bar {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 13px clamp(18px, 3vw, 40px) 13px 54px;
    background: linear-gradient(180deg, rgba(13,19,25,.72) 0%, rgba(9,12,16,.92) 100%);
    box-shadow: 0 -12px 28px rgba(0,0,0,.22), inset 0 1px 0 rgba(255,255,255,.03);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    transition: transform .35s cubic-bezier(.22,1,.36,1), opacity .3s ease;
  }
  .ticker-bar::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--teal) 50%, transparent);
    opacity: .6;
  }
  body.ticker-hidden .ticker-bar {
    transform: translateY(110%);
    opacity: 0;
    pointer-events: none;
  }
  .ticker-close {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    appearance: none;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,.14);
    background: rgba(255,255,255,.05);
    color: var(--text-dim);
    font-size: .74rem;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .18s ease;
  }
  .ticker-close:hover {
    color: var(--bg);
    background: var(--teal);
    border-color: var(--teal);
    box-shadow: 0 0 12px rgba(216,184,120,.4);
  }
  .ticker-icon {
    flex: 0 0 auto;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    background: rgba(216,184,120,.1);
    border: 1px solid var(--teal-dim);
  }
  .ticker-brand {
    font-family: var(--serif);
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .16em;
    line-height: 1.15;
    text-transform: uppercase;
    color: var(--text-dim);
    white-space: nowrap;
  }
  .ticker-divider {
    width: 1px; height: 22px;
    background: rgba(255,255,255,.15);
    flex: 0 0 auto;
  }
  .ticker-text {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: .78rem;
    font-weight: 300;
    color: var(--text-dim);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
  }
  .ticker-dot {
    flex: 0 0 auto;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--teal);
    box-shadow: 0 0 0 0 rgba(216,184,120,.6);
    animation: tickerPulse 2s ease-out infinite;
  }
  @keyframes tickerPulse {
    0%   { box-shadow: 0 0 0 0 rgba(216,184,120,.55); }
    70%  { box-shadow: 0 0 0 8px rgba(216,184,120,0); }
    100% { box-shadow: 0 0 0 0 rgba(216,184,120,0); }
  }
  .ticker-cta {
    flex: 0 0 auto;
    text-decoration: none;
    font-size: .72rem;
    font-weight: 500;
    letter-spacing: .16em;
    text-transform: uppercase;
    color: var(--bg);
    background: linear-gradient(135deg, var(--teal), #f0d9a8);
    padding: 9px 24px;
    border-radius: 999px;
    white-space: nowrap;
    box-shadow: 0 6px 18px rgba(216,184,120,.3);
    transition: transform .18s ease, box-shadow .18s ease;
  }
  .ticker-cta:hover { transform: translateY(-1px); box-shadow: 0 8px 22px rgba(216,184,120,.45); }

  /* reopen pill shown after the ticker is closed */
  .ticker-reopen {
    position: fixed;
    bottom: 18px;
    left: 18px;
    z-index: 50;
    display: none;
    align-items: center;
    gap: 8px;
    padding: 9px 16px 9px 12px;
    border-radius: 999px;
    background: rgba(12,17,22,.75);
    border: 1px solid var(--teal-dim);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    color: var(--text-dim);
    font-size: .72rem;
    letter-spacing: .04em;
    cursor: pointer;
    box-shadow: 0 10px 26px rgba(0,0,0,.35);
    transition: all .18s ease;
  }
  .ticker-reopen span.dot { width:6px; height:6px; border-radius:50%; background: var(--teal); box-shadow: 0 0 8px var(--teal); }
  .ticker-reopen:hover { color: var(--text); border-color: var(--teal); }
  body.ticker-hidden .ticker-reopen { display: flex; }

  /* ══════════════════ OVERRIDE WARNA UNTUK MODE TERANG ══════════════════ */
  html.theme-light .slide-hero {
    background: radial-gradient(circle at 30% 20%, #f2e6c8 0%, var(--bg) 60%);
  }
  html.theme-light .hero-overlay {
    background:
      linear-gradient(180deg, rgba(46,36,20,.22) 0%, rgba(46,36,20,.08) 26%, rgba(46,36,20,.10) 50%, rgba(46,36,20,.28) 74%, rgba(246,242,232,.90) 100%),
      linear-gradient(90deg, rgba(46,36,20,.14) 0%, transparent 42%, transparent 58%, rgba(46,36,20,.14) 100%);
  }
  html.theme-light .hero-content {
    background: radial-gradient(ellipse 100% 100% at 50% 50%, rgba(26,20,11,.58) 0%, rgba(26,20,11,.32) 55%, transparent 84%);
    backdrop-filter: blur(6px) saturate(1.05);
    -webkit-backdrop-filter: blur(6px) saturate(1.05);
  }
  html.theme-light .hero-content h1 {
    background: linear-gradient(180deg, #fff 20%, var(--teal) 140%);
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    text-shadow: 0 10px 34px rgba(0,0,0,.35);
  }
  html.theme-light .hero-content .eyebrow,
  html.theme-light .hero-content .tagline { text-shadow: 0 2px 12px rgba(0,0,0,.4); color: rgba(255,255,255,.82); }
  /* Di luar hero (mis. label "Koleksi Baru" pada Buku Terbaru), pakai warna emas yang lebih gelap
     saat mode terang supaya kontras dengan latar krem/putih tetap jelas dan tidak "menyatu" */
  html.theme-light .eyebrow { color: #8a6323; font-weight: 700; }
  html.theme-light .hero-glow span { opacity: .16; filter: blur(70px); }
  html.theme-light .slide-book {
    background:
      radial-gradient(ellipse 60% 50% at 78% 18%, rgba(169,120,47,.10), transparent 60%),
      radial-gradient(ellipse 50% 40% at 10% 90%, rgba(169,120,47,.07), transparent 60%),
      linear-gradient(165deg, #fdfaf2 0%, #f5efdf 55%, #f6f2e8 100%);
  }
  html.theme-light .slide-about {
    background:
      radial-gradient(ellipse 55% 45% at 12% 12%, rgba(169,120,47,.10), transparent 62%),
      radial-gradient(ellipse 60% 50% at 88% 92%, rgba(169,120,47,.07), transparent 60%),
      linear-gradient(200deg, #fdfaf2 0%, #f5efdf 55%, #f6f2e8 100%);
  }
  html.theme-light .slide-rules {
    background: radial-gradient(circle at 70% 30%, #f5efdf 0%, var(--bg) 65%);
  }
  html.theme-light .slide-book::before,
  html.theme-light .slide-rules::before {
    background: radial-gradient(circle, rgba(169,120,47,.16), transparent 70%);
  }
  html.theme-light .meta-chip { background: rgba(0,0,0,.045); border-color: rgba(0,0,0,.09); }
  html.theme-light .similar-section { border-top-color: rgba(0,0,0,.09); }
  html.theme-light .rules-list li { border-top-color: rgba(0,0,0,.09); }
  html.theme-light .rules-list li:last-child { border-bottom-color: rgba(0,0,0,.09); }
  html.theme-light .ticker-bar {
    background: linear-gradient(180deg, rgba(255,255,255,.85) 0%, rgba(246,242,232,.96) 100%);
    box-shadow: 0 -18px 40px rgba(0,0,0,.08), inset 0 1px 0 rgba(255,255,255,.6);
  }
  html.theme-light .ticker-close { border-color: rgba(0,0,0,.12); background: rgba(0,0,0,.045); }
  html.theme-light .ticker-close:hover { color: #fff; }
  html.theme-light .ticker-icon { background: rgba(169,120,47,.12); }
  html.theme-light .ticker-divider { background: rgba(0,0,0,.12); }
  html.theme-light .ticker-reopen { background: rgba(255,255,255,.82); }
  html.theme-light .dot span { background: rgba(34,29,20,.28); border-color: rgba(34,29,20,.32); }
  html.theme-light .book-cover::before {
    box-shadow:
      3px 4px 0 0 #f1ead5,
      6px 8px 0 0 #e6dcc0,
      9px 12px 0 0 #d8caa4,
      14px 18px 36px 2px rgba(0,0,0,.16),
      0 30px 70px rgba(0,0,0,.14);
  }

  /* ══════════════════ RESPONSIVE ══════════════════ */
  @media (max-width: 1024px) {
    .slide-book .frame { max-width: 100%; }
  }

  @media (max-width: 900px) {
    .book-layout { grid-template-columns: 1fr; gap: 32px; }
    .book-cover { max-width: 185px; }
    .book-info { order: 2; text-align: center; }
    .book-cover-wrap { order: 1; }
    .meta-row, .book-actions { justify-content: center; }
    .book-info .sinopsis { margin-left: auto; margin-right: auto; }
    .slide-book .frame { padding-top: 40px; padding-bottom: 40px; gap: 28px; }
    .hero-content { padding: 36px clamp(16px, 6vw, 48px); }
    .about-grid { grid-template-columns: 1fr 1fr; }
    .cover-icon { width: 56px; height: 56px; font-size: 28px; }
    .icon-lampu  { --ix: -120px; --iy: -78px; }
    .icon-pantai { --ix: 120px;  --iy: -78px; }
    .icon-gunung { --ix: -135px; --iy: 50px; }
    .icon-kaset  { --ix: 135px;  --iy: 50px; }
    .icon-pensil { --ix: 0px;    --iy: 150px; }
  }

  @media (max-width: 680px) {
    .dot-nav { display: none; }
    .hud-top { padding: 14px 16px; }
    .hud-top .brand { font-size: .78rem; letter-spacing: .16em; }
    .hud-actions { gap: 8px; }
    .btn-musik, .btn-mode, .btn-lite, .btn-share-top { width: 34px; height: 34px; }
    .btn-musik svg, .btn-mode svg, .btn-lite svg, .btn-sosial svg { width: 15px; height: 15px; }
    .slide-hero .hero-content h1 { font-size: clamp(2.15rem, 9.5vw, 3rem); }
    .hero-content { padding: 28px 18px; border-radius: 22px; }
    .hero-glow span { filter: blur(40px); }
    .slide-book, .slide-search, .slide-rules, .slide-about { padding-left: 12px; padding-right: 12px; padding-top: 72px; }
    .slide-book { padding-bottom: 96px; }
    .slide-search { padding-bottom: 96px; }
    .slide-rules { padding-bottom: 96px; }
    .slide-about { padding-bottom: 96px; }

    /* Slide Search Responsive (Mobile <= 680px) */
    .slide-search .frame {
      padding: 28px 14px 24px;
      max-width: 100%;
      border-radius: 18px;
    }
    .slide-search h2 {
      font-size: clamp(1.35rem, 5.2vw, 1.75rem);
      line-height: 1.25;
      margin-bottom: 6px;
    }
    .slide-search .lead {
      font-size: .82rem;
      line-height: 1.5;
      margin-bottom: 16px;
      padding: 0 4px;
    }
    .landing-search-box {
      gap: 13px;
    }
    .landing-search-input-wrap {
      padding: 4px 6px 4px 14px;
      min-height: 46px;
    }
    .landing-search-icon {
      width: 18px;
      height: 18px;
      margin-right: 8px;
    }
    .landing-search-input {
      font-size: .9rem;
    }
    .landing-search-btn {
      padding: 9px 15px;
      font-size: .8rem;
      gap: 6px;
    }
    .genre-pill-label {
      width: 100%;
      text-align: center;
      margin-bottom: 2px;
      font-size: .72rem;
      letter-spacing: .06em;
      text-transform: uppercase;
      opacity: .9;
    }
    .landing-genre-pills {
      gap: 7px;
    }
    .genre-pill {
      padding: 6px 13px;
      font-size: .74rem;
      min-height: 32px;
    }
    .landing-qte-card {
      padding: 14px 16px;
      margin-top: 18px;
      border-radius: 12px;
    }
    .qte-body {
      font-size: .94rem;
      line-height: 1.55;
    }
    .qte-author {
      font-size: .74rem;
      margin-top: 5px;
    }
    .book-cover { max-width: 145px; }
    .cover-icon { width: 46px; height: 46px; font-size: 22px; }
    .icon-lampu  { --ix: -95px; --iy: -60px; }
    .icon-pantai { --ix: 95px;  --iy: -60px; }
    .icon-gunung { --ix: -105px; --iy: 38px; }
    .icon-kaset  { --ix: 105px;  --iy: 38px; }
    .icon-pensil { --ix: 0px;   --iy: 120px; }
    .book-info h2 { font-size: clamp(1.05rem, 3.8vw, 1.3rem); }
    .book-actions { flex-direction: column; align-items: stretch; }
    .cta-solid, .cta-outline { justify-content: center; }
    .thumb { width: 92px; }
    .ticker-bar { padding: 12px 14px 12px 42px; gap: 12px; }
    .ticker-brand, .ticker-divider { display: none; }
    .ticker-icon { display: none; }
    .ticker-text { font-size: .68rem; }
    .ticker-cta { padding: 8px 16px; font-size: .64rem; }
    .rules-list { grid-template-columns: 1fr; }
    .slide-rules .frame { padding: 36px 20px; }
    .about-grid { grid-template-columns: 1fr; }
    .about-head { margin-bottom: 36px; }
  }

  @media (max-width: 520px) {
    .btn-search-text-full { display: none; }
    .btn-search-text-short { display: inline; }
    .landing-search-btn {
      padding: 8px 13px;
      font-size: .78rem;
      gap: 5px;
    }
    .landing-search-btn svg {
      width: 13px;
      height: 13px;
    }
    .landing-search-input-wrap {
      padding: 4px 5px 4px 13px;
    }
  }

  @media (max-width: 420px) {
    /* Slide Search on Small Mobile (<= 420px) */
    .slide-search {
      padding-left: 8px;
      padding-right: 8px;
    }
    .slide-search .frame {
      padding: 24px 10px 20px;
    }
    .slide-search .corner {
      width: 18px;
      height: 18px;
    }
    .slide-search .corner-tl, .slide-search .corner-tr { top: 8px; }
    .slide-search .corner-bl, .slide-search .corner-br { bottom: 8px; }
    .slide-search .corner-tl, .slide-search .corner-bl { left: 8px; }
    .slide-search .corner-tr, .slide-search .corner-br { right: 8px; }
    .landing-search-input-wrap {
      padding: 3px 4px 3px 11px;
    }
    .landing-search-icon {
      width: 16px;
      height: 16px;
      margin-right: 6px;
    }
    .landing-search-input {
      font-size: .84rem;
    }
    .landing-search-btn {
      padding: 7px 11px;
      font-size: .76rem;
    }
    .landing-genre-pills {
      gap: 6px;
    }
    .genre-pill {
      padding: 5px 11px;
      font-size: .72rem;
    }
    .landing-qte-card {
      padding: 12px 14px;
      margin-top: 15px;
    }
    .qte-badge {
      font-size: .68rem;
    }
    .qte-body {
      font-size: .88rem;
      line-height: 1.5;
    }
    .book-cover { max-width: 120px; }
    .ticker-cta { display: none; }
    .hud-top .brand { font-size: .7rem; letter-spacing: .1em; }
    .hero-content .tagline { font-size: .82rem; }
  }

  @media (max-width: 360px) {
    .slide-search {
      padding-left: 6px;
      padding-right: 6px;
    }
    .slide-search .frame {
      padding: 20px 8px 16px;
    }
    .landing-search-input-wrap {
      padding: 3px 3px 3px 9px;
    }
    .landing-search-btn {
      padding: 6px 9px;
      font-size: .72rem;
    }
    .landing-search-btn svg {
      display: none;
    }
    .genre-pill {
      padding: 4px 9px;
      font-size: .7rem;
    }
    .hud-top { padding: 12px 12px; }
    .btn-musik, .btn-mode, .btn-lite, .btn-share-top { width: 30px; height: 30px; }
    .cta-glow { padding: 12px 32px; font-size: .7rem; }
  }

  /* layar pendek / landscape ponsel — kecilkan hero agar CTA tetap terlihat */
  @media (max-height: 480px) and (orientation: landscape) {
    .hero-content { padding: 20px clamp(16px, 5vw, 40px); }
    .slide-hero .hero-content h1 { font-size: clamp(1.7rem, 6vw, 2.6rem); }
    .cta-glow { margin-top: 18px; padding: 10px 30px; }
    .scroll-hint { bottom: 20px; }
    .hero-glow { display: none; }
  }

  body.ticker-hidden .slide-book { padding-bottom: 40px; }
  body.ticker-hidden .slide-rules { padding-bottom: 40px; }
  body.ticker-hidden .slide-about { padding-bottom: 50px; }
  body.ticker-hidden .site-footer { padding-bottom: 28px; }

  /* Layar sangat pendek (misal ponsel mode landscape atau browser dengan devtools tinggi) */
  @media (max-height: 600px) and (min-width: 681px) {
    .slide-footer {
      height: auto !important;
      min-height: 100vh;
      min-height: 100dvh;
      overflow-y: visible !important;
      padding-top: 36px;
      padding-bottom: 20px;
    }
    .footer-cta { padding: 14px 20px 10px; }
    .footer-cta h2 { font-size: 1.35rem; margin-bottom: 4px; }
    .footer-cta .lead { margin-bottom: 10px; font-size: .78rem; }
    .footer-highlights { display: none; }
    .site-footer { padding-top: 14px; margin-top: 16px; }
    .footer-top { padding-bottom: 10px; row-gap: 12px; }
  }

  /* ═══════════ QTE INTERACTIVE PROMPT & FLOW POPUP STYLES ═══════════ */
  .qte-book-banner {
    position: absolute;
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(18, 24, 32, 0.92);
    border: 1.5px solid var(--teal, #d8b878);
    box-shadow: 0 6px 18px rgba(0,0,0,.45), 0 0 14px rgba(216,184,120,.22);
    border-radius: 999px;
    padding: 4px 12px;
    cursor: pointer;
    pointer-events: none;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    transition: transform .22s cubic-bezier(.22,1,.36,1), box-shadow .22s ease, border-color .22s ease;
    white-space: nowrap;
    animation: qteFloat 3.2s ease-in-out infinite;
  }
  @keyframes qteFloat {
    0%, 100% { transform: translateX(-50%) translateY(0); }
    50% { transform: translateX(-50%) translateY(-5px); }
  }
  .qte-click-icon {
    color: var(--teal, #d8b878);
    flex-shrink: 0;
  }
  html.theme-light .qte-click-icon {
    color: #a9782f;
  }
  .qte-text {
    font-size: .74rem;
    font-weight: 700;
    color: #eef3f4;
    letter-spacing: .02em;
  }
  html.theme-light .qte-text {
    color: #1e293b;
  }
  html.theme-light .qte-book-banner {
    background: rgba(255, 255, 255, 0.95);
    border-color: #a9782f;
    box-shadow: 0 6px 16px rgba(0,0,0,.08), 0 0 12px rgba(169,120,47,.18);
  }
  .qte-pulse {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 6px #10b981;
    animation: qtePulseDot 1.8s infinite;
  }
  @keyframes qtePulseDot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: .4; transform: scale(1.3); }
  }

  /* ── Kartu Interaktif Bersih & Rapi (Tanpa Menggeser/Mendorong Grid) ── */
  .rules-list li.flow-card,
  .about-card.flow-card {
    position: relative;
    cursor: pointer;
    transition: transform .28s cubic-bezier(.22,1,.36,1),
                border-color .25s ease,
                box-shadow .28s ease,
                background .25s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }
  .rules-list li.flow-card:hover,
  .about-card.flow-card:hover {
    transform: translateY(-4px);
    border-color: var(--teal, #d8b878);
    box-shadow: 0 10px 24px rgba(0,0,0,.4), 0 0 18px rgba(216,184,120,.18);
  }
  html.theme-light .rules-list li.flow-card:hover,
  html.theme-light .about-card.flow-card:hover {
    box-shadow: 0 8px 22px rgba(0,0,0,.08), 0 0 16px rgba(169,120,47,.16);
  }

  /* Pill Tombol Pemicu di Bagian Bawah Kartu */
  .card-trigger-bar {
    margin-top: 12px;
    display: flex;
    align-items: center;
  }
  .flow-pill-btn {
    appearance: none;
    border: 1px solid rgba(216,184,120,.24);
    background: rgba(216,184,120,.08);
    color: var(--teal, #d8b878);
    font-family: inherit;
    font-size: .74rem;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 999px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all .2s ease;
  }
  .flow-card:hover .flow-pill-btn {
    background: var(--teal, #d8b878);
    color: #0b0f14;
    border-color: var(--teal, #d8b878);
    box-shadow: 0 4px 12px rgba(216,184,120,.3);
  }
  .flow-pill-tag {
    background: rgba(216,184,120,.22);
    color: var(--teal, #d8b878);
    font-size: .62rem;
    font-weight: 800;
    padding: 1px 5px;
    border-radius: 4px;
    letter-spacing: .04em;
  }
  .flow-card:hover .flow-pill-tag {
    background: #0b0f14;
    color: var(--teal, #d8b878);
  }
  html.theme-light .flow-pill-btn {
    background: rgba(169,120,47,.08);
    border-color: rgba(169,120,47,.28);
    color: #8a6323;
  }
  html.theme-light .flow-card:hover .flow-pill-btn {
    background: #8a6323;
    color: #ffffff;
    border-color: #8a6323;
  }
  html.theme-light .flow-card:hover .flow-pill-tag {
    background: #ffffff;
    color: #8a6323;
  }

  /* ═══════════ MODAL POP-UP DETAIL ALUR (COMPACT & INDAH) ═══════════ */
  .flow-popup-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(4, 7, 10, 0.72);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 9990;
    opacity: 0;
    visibility: hidden;
    transition: opacity .25s ease, visibility .25s;
  }
  .flow-popup-backdrop.open {
    opacity: 1;
    visibility: visible;
  }

  .flow-popup {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -46%) scale(0.94);
    width: 92%;
    max-width: 490px;
    max-height: 86vh;
    background: var(--card, #121820);
    border: 1px solid var(--teal, #d8b878);
    box-shadow: 0 20px 50px rgba(0,0,0,.65), 0 0 30px rgba(216,184,120,.2);
    border-radius: 18px;
    z-index: 9995;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    opacity: 0;
    visibility: hidden;
    transition: all .25s cubic-bezier(.22,1,.36,1);
    font-family: var(--font-family, 'Outfit', sans-serif);
  }
  .flow-popup.open {
    opacity: 1;
    visibility: visible;
    transform: translate(-50%, -50%) scale(1);
  }
  html.theme-light .flow-popup {
    background: #ffffff;
    border-color: #a9782f;
    box-shadow: 0 16px 42px rgba(0,0,0,.15), 0 0 24px rgba(169,120,47,.2);
  }

  .flow-popup-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid var(--border-color, rgba(216,184,120,.18));
    background: rgba(18, 24, 32, 0.96);
  }
  html.theme-light .flow-popup-header {
    background: #ffffff;
    border-bottom-color: rgba(169,120,47,.16);
  }
  .flow-popup-badge {
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--teal, #d8b878);
  }
  html.theme-light .flow-popup-badge {
    color: #8a6323;
  }
  .flow-popup-close {
    appearance: none;
    border: none;
    background: rgba(255,255,255,.06);
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--muted, #8c8479);
    cursor: pointer;
    transition: all .18s ease;
  }
  .flow-popup-close svg { width: 14px; height: 14px; }
  .flow-popup-close:hover {
    background: rgba(220,38,38,.15);
    color: #f87171;
    transform: scale(1.08);
  }

  .flow-popup-body {
    padding: 20px 22px;
    overflow-y: auto;
    flex: 1;
  }
  .flow-popup-title {
    font-family: var(--serif, 'Cormorant Garamond', serif);
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--text, #eef3f4);
    margin: 0 0 4px;
    line-height: 1.25;
  }
  .flow-popup-desc {
    font-size: .82rem;
    color: var(--muted, rgba(238,243,244,.65));
    margin: 0 0 18px;
    line-height: 1.45;
  }

  /* Timeline Stepper */
  .flow-popup-timeline {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .popup-step-item {
    display: flex;
    gap: 12px;
    background: rgba(255,255,255,.025);
    border: 1px solid rgba(216,184,120,.14);
    border-radius: 12px;
    padding: 12px 14px;
    align-items: flex-start;
  }
  html.theme-light .popup-step-item {
    background: #fdfbf7;
    border-color: rgba(169,120,47,.18);
  }
  .popup-step-num {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: linear-gradient(135deg, #18222d, #0d1217);
    border: 1.5px solid var(--teal, #d8b878);
    color: var(--teal, #d8b878);
    font-size: .76rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  html.theme-light .popup-step-num {
    background: #ffffff;
    border-color: #8a6323;
    color: #8a6323;
  }
  .popup-step-text strong {
    display: block;
    font-size: .84rem;
    font-weight: 700;
    color: var(--text, #eef3f4);
    margin-bottom: 2px;
  }
  .popup-step-text p {
    font-size: .78rem !important;
    color: var(--muted, rgba(238,243,244,.7)) !important;
    line-height: 1.4 !important;
    margin: 0 !important;
  }

  .flow-popup-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 12px 20px;
    border-top: 1px solid var(--border-color, rgba(216,184,120,.18));
    background: rgba(18, 24, 32, 0.96);
  }
  html.theme-light .flow-popup-footer {
    background: #ffffff;
    border-top-color: rgba(169,120,47,.16);
  }
  .flow-popup-btn-sec {
    appearance: none;
    border: 1px solid var(--border-color, rgba(216,184,120,.25));
    background: transparent;
    color: var(--muted, #8c8479);
    font-family: inherit;
    font-size: .76rem;
    font-weight: 700;
    padding: 7px 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: all .18s;
  }
  .flow-popup-btn-sec:hover {
    background: rgba(255,255,255,.05);
    color: var(--text, #eef3f4);
  }
  .flow-popup-btn-pri {
    appearance: none;
    border: none;
    background: linear-gradient(135deg, #d8b878, #b8860b);
    color: #0b0f14;
    font-family: inherit;
    font-size: .76rem;
    font-weight: 800;
    padding: 7px 18px;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: transform .18s, filter .18s;
  }
  .flow-popup-btn-pri:hover {
    transform: translateY(-1px);
    filter: brightness(1.1);
  }

  /* ═══════════ PERBAIKAN KONTRAS TEKS LENGKAP (MODE TERANG & GELAP) ═══════════ */
  
  /* ── 1. Pop-up Modal Detail Alur ── */
  .flow-popup-title {
    color: var(--text, #eef3f4) !important;
  }
  .flow-popup-desc {
    color: var(--muted, rgba(238, 243, 244, 0.75)) !important;
  }
  .popup-step-text strong {
    color: var(--text, #eef3f4) !important;
  }
  .popup-step-text p {
    color: var(--muted, rgba(238, 243, 244, 0.78)) !important;
  }
  .flow-popup-btn-sec {
    color: var(--text-dim, #8c8479) !important;
    border-color: rgba(216, 184, 120, 0.3) !important;
  }
  .flow-popup-btn-sec:hover {
    background: rgba(255, 255, 255, 0.08) !important;
    color: var(--text) !important;
  }

  /* Pop-up di Mode Terang: Teks Hitam Pekat & Abu Gelap, Sangat Kontras */
  html.theme-light .flow-popup {
    background: #ffffff !important;
    border-color: #8a6323 !important;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.16), 0 0 28px rgba(138, 99, 35, 0.22) !important;
  }
  html.theme-light .flow-popup-header {
    background: #ffffff !important;
    border-bottom-color: rgba(138, 99, 35, 0.18) !important;
  }
  html.theme-light .flow-popup-badge {
    color: #8a6323 !important;
    font-weight: 800 !important;
  }
  html.theme-light .flow-popup-close {
    background: rgba(0, 0, 0, 0.05) !important;
    color: #433c32 !important;
  }
  html.theme-light .flow-popup-close:hover {
    background: rgba(220, 38, 38, 0.15) !important;
    color: #dc2626 !important;
  }
  html.theme-light .flow-popup-title {
    color: #1a1610 !important;
    font-weight: 700 !important;
  }
  html.theme-light .flow-popup-desc {
    color: #4a433a !important;
    font-weight: 400 !important;
  }
  html.theme-light .popup-step-item {
    background: #fdfbf7 !important;
    border-color: rgba(138, 99, 35, 0.22) !important;
  }
  html.theme-light .popup-step-num {
    background: #ffffff !important;
    border-color: #8a6323 !important;
    color: #8a6323 !important;
    font-weight: 800 !important;
  }
  html.theme-light .popup-step-text strong {
    color: #1a1610 !important;
    font-weight: 700 !important;
  }
  html.theme-light .popup-step-text p {
    color: #3e372d !important;
    font-weight: 400 !important;
  }
  html.theme-light .flow-popup-footer {
    background: #ffffff !important;
    border-top-color: rgba(138, 99, 35, 0.18) !important;
  }
  html.theme-light .flow-popup-btn-sec {
    color: #4a433a !important;
    border-color: rgba(138, 99, 35, 0.35) !important;
  }
  html.theme-light .flow-popup-btn-sec:hover {
    background: rgba(0, 0, 0, 0.06) !important;
    color: #1a1610 !important;
  }

  /* ── 2. Kartu Tata Tertib & Tentang Katalog di Mode Terang ── */
  html.theme-light .rules-list li.flow-card,
  html.theme-light .about-card.flow-card {
    background: rgba(255, 255, 255, 0.92) !important;
    border: 1.5px solid rgba(138, 99, 35, 0.24) !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04) !important;
  }
  html.theme-light .rules-list li.flow-card:hover,
  html.theme-light .about-card.flow-card:hover {
    background: #ffffff !important;
    border-color: #8a6323 !important;
    box-shadow: 0 10px 26px rgba(138, 99, 35, 0.18) !important;
  }
  html.theme-light .rules-list h3,
  html.theme-light .about-card h3 {
    color: #1a1610 !important;
    font-weight: 600 !important;
  }
  html.theme-light .rules-list p,
  html.theme-light .about-card p {
    color: #3e372d !important;
    font-weight: 400 !important;
  }
  html.theme-light .rules-list .num {
    color: #8a6323 !important;
    font-weight: 800 !important;
  }
  html.theme-light .about-icon {
    background: rgba(138, 99, 35, 0.12) !important;
    border-color: rgba(138, 99, 35, 0.35) !important;
    color: #8a6323 !important;
  }
  html.theme-light .flow-pill-btn {
    background: rgba(138, 99, 35, 0.08) !important;
    border-color: rgba(138, 99, 35, 0.3) !important;
    color: #8a6323 !important;
    font-weight: 700 !important;
  }
  html.theme-light .flow-pill-tag {
    background: rgba(138, 99, 35, 0.16) !important;
    color: #8a6323 !important;
    font-weight: 800 !important;
  }
  html.theme-light .flow-card:hover .flow-pill-btn {
    background: #8a6323 !important;
    color: #ffffff !important;
    border-color: #8a6323 !important;
  }
  html.theme-light .flow-card:hover .flow-pill-tag {
    background: #ffffff !important;
    color: #8a6323 !important;
  }

  /* ── 3. Detail Buku Terbaru (Slide 2) di Mode Terang ── */
  html.theme-light .book-info h2 {
    color: #1a1610 !important;
    text-shadow: none !important;
  }
  html.theme-light .book-info .author {
    color: #4a433a !important;
  }
  html.theme-light .book-info .author strong {
    color: #8a6323 !important;
    font-weight: 600 !important;
  }
  html.theme-light .book-info .sinopsis {
    color: #332d24 !important;
  }
  html.theme-light .meta-chip {
    background: rgba(255, 255, 255, 0.88) !important;
    border-color: rgba(138, 99, 35, 0.28) !important;
    color: #332d24 !important;
    font-weight: 500 !important;
  }
  html.theme-light .meta-chip svg {
    color: #8a6323 !important;
  }
  html.theme-light .meta-chip--status {
    color: #15803d !important;
    font-weight: 600 !important;
    border-color: rgba(21, 128, 61, 0.35) !important;
    background: rgba(21, 128, 61, 0.08) !important;
  }
  html.theme-light .meta-chip--status svg {
    color: #15803d !important;
  }
  html.theme-light .cta-outline {
    border-color: rgba(138, 99, 35, 0.5) !important;
    color: #8a6323 !important;
    background: rgba(138, 99, 35, 0.06) !important;
    font-weight: 600 !important;
  }
  html.theme-light .cta-outline:hover {
    background: #8a6323 !important;
    border-color: #8a6323 !important;
    color: #ffffff !important;
    box-shadow: 0 8px 24px rgba(138, 99, 35, 0.3) !important;
  }
  html.theme-light .similar-section {
    border-top-color: rgba(138, 99, 35, 0.22) !important;
  }
  html.theme-light .similar-label {
    color: #8a6323 !important;
    font-weight: 700 !important;
  }
  html.theme-light .thumb-title {
    color: #332d24 !important;
    font-weight: 500 !important;
  }
  html.theme-light .thumb.active .thumb-title {
    color: #8a6323 !important;
    font-weight: 700 !important;
  }
  html.theme-light .cover-icon {
    background: rgba(255, 255, 255, 0.96) !important;
    border: 1.5px solid rgba(138, 99, 35, 0.4) !important;
    box-shadow: 0 10px 24px rgba(120, 100, 60, 0.22) !important;
    color: #8a6323 !important;
  }
  html.theme-light .cover-icon svg {
    color: #8a6323 !important;
  }

  /* ── 4. Prompt Klik Aku di Mode Terang ── */
  html.theme-light .qte-book-banner {
    background: rgba(255, 255, 255, 0.96) !important;
    border-color: #8a6323 !important;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1), 0 0 14px rgba(138, 99, 35, 0.2) !important;
  }
  html.theme-light .qte-text {
    color: #1a1610 !important;
    font-weight: 700 !important;
  }
  html.theme-light .qte-click-icon {
    color: #8a6323 !important;
  }

  /* ── 5. Slide Pencarian Katalog di Mode Terang ── */
  html.theme-light .slide-search h2 {
    color: #1a1610 !important;
  }
  html.theme-light .slide-search .lead {
    color: #4a433a !important;
  }
  html.theme-light .genre-pill-label {
    color: #524b40 !important;
    font-weight: 600 !important;
  }
  html.theme-light .genre-pill {
    color: #332d24 !important;
    background: rgba(255, 255, 255, 0.88) !important;
    border-color: rgba(138, 99, 35, 0.3) !important;
    font-weight: 500 !important;
  }
  html.theme-light .genre-pill:hover {
    color: #8a6323 !important;
    background: #ffffff !important;
    border-color: #8a6323 !important;
  }
  html.theme-light .genre-pill-all {
    color: #8a6323 !important;
    font-weight: 700 !important;
    border-color: #8a6323 !important;
  }
  html.theme-light .qte-body {
    color: #1a1610 !important;
  }
  html.theme-light .qte-author {
    color: #524b40 !important;
    font-weight: 600 !important;
  }

  /* ── 6. Frame Label & Judul Seluruh Slide di Mode Terang ── */
  html.theme-light .frame-label {
    background: #fdfaf2 !important;
    color: #8a6323 !important;
    font-weight: 700 !important;
    border: 1px solid rgba(138, 99, 35, 0.3) !important;
  }
  html.theme-light .slide-rules h2,
  html.theme-light .about-head h2,
  html.theme-light .location-info h2,
  html.theme-light .footer-cta h2 {
    color: #1a1610 !important;
    text-shadow: none !important;
  }
  html.theme-light .slide-rules .lead,
  html.theme-light .about-head .lead,
  html.theme-light .location-info .lead,
  html.theme-light .footer-cta .lead {
    color: #4a433a !important;
  }
  html.theme-light .location-list h4 {
    color: #1a1610 !important;
    font-weight: 600 !important;
  }
  html.theme-light .location-list p {
    color: #3e372d !important;
  }
  html.theme-light .location-icon {
    background: rgba(138, 99, 35, 0.12) !important;
    border-color: rgba(138, 99, 35, 0.35) !important;
    color: #8a6323 !important;
  }

  /* ── 7. Footer di Mode Terang ── */
  html.theme-light .footer-brand .brand {
    color: #1a1610 !important;
  }
  html.theme-light .footer-brand p {
    color: #4a433a !important;
  }
  html.theme-light .footer-col h4 {
    color: #8a6323 !important;
    font-weight: 700 !important;
  }
  html.theme-light .footer-col a {
    color: #4a433a !important;
  }
  html.theme-light .footer-col a:hover {
    color: #8a6323 !important;
  }
  html.theme-light .footer-bottom p {
    color: #5a5144 !important;
  }
  html.theme-light .footer-social-links a {
    color: #5a5144 !important;
    border-color: rgba(138, 99, 35, 0.28) !important;
  }
  html.theme-light .footer-social-links a:hover {
    color: #8a6323 !important;
    border-color: #8a6323 !important;
    background: rgba(138, 99, 35, 0.12) !important;
  }

  /* ── 8. Ticker Bar di Mode Terang ── */
  html.theme-light .ticker-text {
    color: #241d14 !important;
    font-weight: 500 !important;
  }
  html.theme-light .ticker-brand {
    color: #8a6323 !important;
    font-weight: 800 !important;
  }
  html.theme-light .ticker-reopen {
    background: rgba(255, 255, 255, 0.94) !important;
    color: #241d14 !important;
    border-color: rgba(138, 99, 35, 0.35) !important;
  }

  /* ── 9. Scroll Hint di Hero Slide Mode Terang ── */
  html.theme-light .slide-hero .scroll-hint {
    color: rgba(255, 255, 255, 0.92) !important;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.75) !important;
  }

  /* ── 10. Tombol HUD Top di Mode Terang ── */
  html.theme-light .btn-share-top,
  html.theme-light .btn-musik,
  html.theme-light .btn-mode,
  html.theme-light .btn-lite {
    background: rgba(255, 255, 255, 0.88) !important;
    border-color: rgba(138, 99, 35, 0.35) !important;
    color: #8a6323 !important;
  }
  html.theme-light .btn-share-top:hover,
  html.theme-light .btn-musik:hover,
  html.theme-light .btn-mode:hover,
  html.theme-light .btn-lite:hover {
    background: #ffffff !important;
    border-color: #8a6323 !important;
    box-shadow: 0 6px 18px rgba(138, 99, 35, 0.22) !important;
  }

</style>
<script src="js/aksa_audio.js?v=20261109"></script>
</head>
<body>

<div class="hud-top">
  <div class="brand">AKSA <span>NOVA</span></div>
  <div class="hud-actions">
    <?php if ($musik_tampil): ?>
    <button type="button" class="btn-musik" id="btnMusik" aria-label="Putar / hentikan musik latar" title="<?= htmlspecialchars($musik_judul) ?>" aria-pressed="false">
      <svg class="icon-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>
      </svg>
      <span class="icon-eq" aria-hidden="true"><span></span><span></span><span></span></span>
    </button>
    <audio id="audioLatar" loop preload="auto" src="<?= htmlspecialchars($musik_file) ?>">
      <source src="<?= htmlspecialchars($musik_file) ?>" type="audio/mpeg">
    </audio>
    <?php endif; ?>
    <button type="button" class="btn-mode" id="btnMode" aria-label="Ganti mode gelap/terang" data-id-aria="Ganti mode gelap/terang" data-en-aria="Toggle dark/light mode" title="Mode Gelap / Terang" data-id-title="Mode Gelap / Terang" data-en-title="Dark / Light Mode" aria-pressed="false">
      <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/>
      </svg>
      <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="4.2"/>
        <path d="M12 2.5v2.4M12 19.1v2.4M4.9 4.9l1.7 1.7M17.4 17.4l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.9 19.1l1.7-1.7M17.4 6.6l1.7-1.7"/>
      </svg>
    </button>
    <button type="button" class="btn-lite" id="btnLite" aria-label="Aktifkan tampilan ringan" data-id-aria="Aktifkan tampilan ringan" data-en-aria="Enable lite display" title="Tampilan Ringan" data-id-title="Tampilan Ringan (matikan animasi)" data-en-title="Lite Display (disable animations)" aria-pressed="false">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M13 2 4 14h6l-1 8 9-12h-6l1-8Z"/>
      </svg>
    </button>

    <button type="button" class="btn-share-top" id="btnShare" aria-label="Bagikan tautan perpustakaan" data-id-aria="Bagikan tautan perpustakaan" data-en-aria="Share library link" title="Bagikan Website (Salin Tautan)" data-id-title="Bagikan Website (Salin Tautan)" data-en-title="Share Website (Copy Link)">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
        <line x1="8.6" y1="10.6" x2="15.4" y2="6.4"/><line x1="8.6" y1="13.4" x2="15.4" y2="17.6"/>
      </svg>
    </button>
  </div>
</div>

<div class="share-toast" id="shareToast">Link disalin ke clipboard!</div>

<nav class="dot-nav">
  <button class="dot active" data-target="slide-1" aria-label="Beranda"><span></span></button>
  <button class="dot" data-target="slide-2" aria-label="Buku Terbaru"><span></span></button>
  <button class="dot" data-target="slide-search" aria-label="Cari Buku di Katalog"><span></span></button>
  <button class="dot" data-target="slide-3" aria-label="Aturan Perpustakaan"><span></span></button>
  <button class="dot" data-target="slide-4" aria-label="Tentang Katalog Perpustakaan"><span></span></button>
  <button class="dot" data-target="slide-5" aria-label="Lokasi Perpustakaan"><span></span></button>
  <button class="dot" data-target="slide-6" aria-label="Footer"><span></span></button>
</nav>

<main class="slides">

  <!-- ═══════════ SLIDE 1 — HERO ═══════════ -->
  <section id="slide-1" class="slide slide-hero">
    <!-- Gambar latar diatur admin lewat menu Kelola Banner ("Jadikan Latar Beranda") -->
    <div class="hero-bg" id="heroBg" aria-hidden="true">
      <?php foreach ($hero_images as $i => $hb): ?>
        <?php
          $hb_ext    = strtolower(pathinfo($hb['gambar'], PATHINFO_EXTENSION));
          $is_gif    = $hb_ext === 'gif';
          $is_video  = in_array($hb_ext, ['mp4','webm']);
          $is_static = !$is_gif && !$is_video;
          $bg_style  = $is_static ? 'style="background-image:url(\'' . htmlspecialchars($hb['gambar']) . '\')"' : '';
          $cls_extra = ($is_gif ? ' is-gif' : '') . ($is_video ? ' is-video' : '');
        ?>
        <div class="hero-bg-slide<?= $i === 0 ? ' active' : '' ?><?= $cls_extra ?>" <?= $bg_style ?>>
          <?php if ($is_gif): ?>
            <img src="<?= htmlspecialchars($hb['gambar']) ?>" alt="<?= htmlspecialchars($hb['judul'] ?? 'Banner') ?>" class="hero-bg-gif">
          <?php elseif ($is_video): ?>
            <video class="hero-bg-video" autoplay muted loop playsinline preload="auto">
              <source src="<?= htmlspecialchars($hb['gambar']) ?>" type="video/<?= $hb_ext ?>">
            </video>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="hero-overlay"></div>
    <div class="hero-glow" aria-hidden="true"><span></span><span></span><span></span></div>

    <div class="hero-content reveal reveal-stagger">
      <p class="eyebrow reveal-item" data-id="Perpustakaan Digital" data-en="Digital Library">Perpustakaan Digital</p>
      <h1 class="reveal-item">AKSA NOVA</h1>
      <p class="tagline reveal-item" data-id="Setiap halaman menyimpan dunia baru — masuk dan mulai jelajahi koleksinya." data-en="Every page holds a new world — sign in and start exploring the collection.">Setiap halaman menyimpan dunia baru — masuk dan mulai jelajahi koleksinya.</p>
      <a href="beranda.php" class="cta-glow reveal-item" data-id="Masuk & Jelajahi" data-en="Sign In & Explore">Masuk &amp; Jelajahi</a>
    </div>
    <div class="scroll-hint"><span data-id="Gulir untuk melihat lebih" data-en="Scroll to see more">Gulir untuk melihat lebih</span><span class="chevron">⌄</span></div>
  </section>

  <!-- ═══════════ SLIDE 2 — BUKU TERBARU ═══════════ -->
  <section id="slide-2" class="slide slide-book">
    <div class="frame frame-gold reveal">
      <span class="corner corner-tl"></span><span class="corner corner-tr"></span>
      <span class="corner corner-bl"></span><span class="corner corner-br"></span>
      <span class="frame-label" data-id="Buku Terbaru" data-en="Latest Books">Buku Terbaru</span>

      <div class="book-layout">
        <div class="book-info">
          <p class="eyebrow" data-id="Koleksi Baru" data-en="New Collection">Koleksi Baru</p>
          <h2 id="bukuJudul"><?= $buku_utama ? htmlspecialchars($buku_utama["judul"]) : "Segera Hadir" ?></h2>
          <p class="author">
            <span data-id="oleh" data-en="by">oleh</span> <strong id="bukuPenulis"><?= $buku_utama && $buku_utama["penulis"] ? htmlspecialchars($buku_utama["penulis"]) : "Belum diketahui" ?></strong>
          </p>

          <div class="meta-row">
            <span class="meta-chip">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v17H6.5A2.5 2.5 0 0 0 4 21.5v-17Z"/><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/></svg>
              <span id="bukuGenre"><?= $buku_utama ? htmlspecialchars(format_genre($buku_utama["genre"]) ?: "Umum") : "Umum" ?></span>
            </span>
            <span class="meta-chip meta-chip--status">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
              <span data-id="Tersedia Dipinjam" data-en="Available to Borrow">Tersedia Dipinjam</span>
            </span>
          </div>

          <p class="sinopsis" id="bukuSinopsis"><?= $buku_utama ? htmlspecialchars(ringkas($buku_utama["sinopsis"])) : "Koleksi buku terbaru akan segera ditambahkan oleh admin perpustakaan." ?></p>

          <div class="book-actions">
            <a href="sign_in.php" class="cta-solid">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 5v14l11-7z"/></svg>
              <span data-id="Rating Sekarang" data-en="Rate Now">Rating Sekarang</span>
            </a>
            <a href="sign_in.php" class="cta-outline">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 1 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/></svg>
              <span data-id="Tambah Wishlist" data-en="Add to Wishlist">Tambah Wishlist</span>
            </a>
          </div>
        </div>

        <div class="book-cover-wrap" id="bookCoverWrap" role="button" tabindex="0" aria-label="Buka cover buku" data-id-aria="Buka cover buku" data-en-aria="Open book cover">
          <span class="cover-glow"></span>

          <!-- QTE Prompt untuk Animasi Buku (Simple: Klik Aku!) -->
          <div class="qte-book-banner" id="qteBookBanner">
            <svg class="qte-click-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 3 7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/><path d="m13 13 6 6"/></svg>
            <span class="qte-text" id="qteBookText">Klik Aku!</span>
            <span class="qte-pulse"></span>
          </div>

          <span class="cover-icon icon-lampu" aria-hidden="true" title="Lampu" style="color:var(--accent,#d8b878);"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-7 7c0 2.5 1.5 4.5 3 6h8c1.5-1.5 3-3.5 3-6a7 7 0 0 0-7-7z"/></svg></span>
          <span class="cover-icon icon-pantai" aria-hidden="true" title="Pantai" style="color:var(--accent,#d8b878);"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg></span>
          <span class="cover-icon icon-gunung" aria-hidden="true" title="Gunung" style="color:var(--accent,#d8b878);"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m8 3 4 8 5-5 5 15H2L8 3z"/></svg></span>
          <span class="cover-icon icon-kaset" aria-hidden="true" title="Kaset" style="color:var(--accent,#d8b878);"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><circle cx="8" cy="12" r="2"/><circle cx="16" cy="12" r="2"/><path d="M8 12h8M6 18h12"/></svg></span>
          <span class="cover-icon icon-pensil" aria-hidden="true" title="Pensil" style="color:var(--accent,#d8b878);"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg></span>

          <div class="book-cover" id="bookCover">
            <img id="bukuGambar"
                 src="<?= $buku_utama && $buku_utama["gambar"] ? htmlspecialchars($buku_utama["gambar"]) : "https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=700&q=80" ?>"
                 alt="Sampul buku">
          </div>
        </div>
      </div>

      <?php if (count($buku_list) > 1): ?>
      <div class="similar-section">
        <p class="similar-label" data-id="Buku Serupa Lainnya" data-en="Other Similar Books">Buku Serupa Lainnya</p>
        <div class="book-thumbs">
          <?php foreach ($buku_list as $i => $b): ?>
            <button class="thumb<?= $i === 0 ? " active" : "" ?>"
              data-judul="<?= htmlspecialchars($b["judul"]) ?>"
              data-penulis="<?= htmlspecialchars($b["penulis"] ?: "Belum diketahui") ?>"
              data-genre="<?= htmlspecialchars(format_genre($b["genre"]) ?: "Umum") ?>"
              data-sinopsis="<?= htmlspecialchars(ringkas($b["sinopsis"])) ?>"
              data-gambar="<?= htmlspecialchars($b["gambar"]) ?>">
              <span class="thumb-cover"><img src="<?= htmlspecialchars($b["gambar"]) ?>" alt="<?= htmlspecialchars($b["judul"]) ?>"></span>
              <span class="thumb-title"><?= htmlspecialchars($b["judul"]) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- ═══════════ SLIDE BARU: PENCARIAN KATALOG & KUTIPAN LITERASI ═══════════ -->
  <section id="slide-search" class="slide slide-search">
    <div class="frame frame-gold reveal">
      <span class="corner corner-tl"></span><span class="corner corner-tr"></span>
      <span class="corner corner-bl"></span><span class="corner corner-br"></span>
      <span class="frame-label" data-id="Pencarian Katalog" data-en="Catalog Search">Pencarian Katalog</span>

      <div class="search-frame-content">
        <p class="eyebrow" data-id="Eksplorasi Koleksi" data-en="Explore Collection">Eksplorasi Koleksi</p>
        <h2 data-id="Cari Buku di Katalog Perpustakaan" data-en="Search Books in Library Catalog">Cari Buku di Katalog Perpustakaan</h2>
        <p class="lead" data-id="Temukan judul buku favorit, referensi materi pelajaran, fiksi, teknologi, dan sains yang tersedia di AKSA NOVA." data-en="Find favorite book titles, subject reference materials, fiction, technology, and science available at AKSA NOVA.">Temukan judul buku favorit, referensi materi pelajaran, fiksi, teknologi, dan sains yang tersedia di AKSA NOVA.</p>

        <!-- Search Bar yang Luas & Nyaman -->
        <form action="daftar_buku.php" method="GET" class="landing-search-box" role="search">
          <input type="hidden" name="from" value="landing">
          <div class="landing-search-input-wrap">
            <svg class="landing-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" name="q" class="landing-search-input" placeholder="Cari judul buku, penulis, topik..." autocomplete="off" aria-label="Cari koleksi buku">
            <button type="submit" class="landing-search-btn" aria-label="Temukan Buku">
              <span class="btn-search-text-full">Cari Katalog</span>
              <span class="btn-search-text-short">Cari</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
          </div>

          <!-- Pilihan Kategori Cepat -->
          <div class="landing-genre-pills">
            <span class="genre-pill-label">Kategori Pilihan:</span>
            <a href="daftar_buku.php?kategori=Pelajaran&from=landing" class="genre-pill">Pelajaran</a>
            <a href="daftar_buku.php?kategori=Teknologi&from=landing" class="genre-pill">Teknologi</a>
            <a href="daftar_buku.php?kategori=Fiksi&from=landing" class="genre-pill">Fiksi</a>
            <a href="daftar_buku.php?kategori=Sains&from=landing" class="genre-pill">Sains</a>
            <a href="daftar_buku.php?kategori=Sejarah&from=landing" class="genre-pill">Sejarah</a>
            <a href="daftar_buku.php?from=landing" class="genre-pill genre-pill-all">Lihat Semua Buku &rarr;</a>
          </div>
        </form>

        <!-- Wadah Kutipan Literasi (QTE) Hari Ini -->
        <div class="landing-qte-card">
          <div class="qte-badge">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/></svg>
            <span>Kutipan Literasi Hari Ini</span>
          </div>
          <blockquote class="qte-body">&ldquo;<?= htmlspecialchars($kutipan_hari_ini["teks"]) ?>&rdquo;</blockquote>
          <cite class="qte-author">&mdash; <?= htmlspecialchars($kutipan_hari_ini["oleh"]) ?></cite>
        </div>

      </div>
    </div>
  </section>


  <!-- ═══════════ SLIDE 3 — ATURAN PERPUSTAKAAN ═══════════ -->
  <section id="slide-3" class="slide slide-rules">
    <div class="frame reveal">
      <span class="corner corner-tl"></span><span class="corner corner-tr"></span>
      <span class="corner corner-bl"></span><span class="corner corner-br"></span>
      <span class="frame-label" data-id="Aturan Perpustakaan" data-en="Library Rules">Aturan Perpustakaan</span>

      <h2 data-id="Aturan & Tata Tertib Perpustakaan" data-en="Library Rules & Regulations">Aturan & Tata Tertib Perpustakaan</h2>
      <p class="lead" data-id="Panduan dan tata tertib peminjaman koleksi buku di perpustakaan digital AKSA NOVA." data-en="Guidelines and regulations for borrowing books at the AKSA NOVA digital library.">Panduan dan tata tertib peminjaman koleksi buku di perpustakaan digital AKSA NOVA.</p>

      <ul class="rules-list reveal-stagger">
        <li class="reveal-item flow-card" onclick="bukaFlowPopup('tata-tertib-1')" tabindex="0" role="button" aria-label="Buka Alur Jam Pelayanan">
          <div>
            <span class="num">01</span>
            <h3 data-id="Jam Pelayanan" data-en="Service Hours">Jam Pelayanan</h3>
            <p data-id="Layanan sirkulasi peminjaman dan pengembalian buku buka setiap <?= htmlspecialchars($lokasi_jam) ?>." data-en="Book circulation and return services are open every <?= htmlspecialchars($lokasi_jam) ?>.">Layanan sirkulasi peminjaman dan pengembalian buku buka setiap <?= htmlspecialchars($lokasi_jam) ?>.</p>
          </div>
          <div class="card-trigger-bar">
            <span class="flow-pill-btn"><span class="flow-pill-tag">ALUR</span> Lihat Rincian &rsaquo;</span>
          </div>
        </li>

        <li class="reveal-item flow-card" onclick="bukaFlowPopup('tata-tertib-2')" tabindex="0" role="button" aria-label="Buka Alur Kartu Anggota Digital">
          <div>
            <span class="num">02</span>
            <h3 data-id="Kartu Anggota Digital" data-en="Digital Member Card">Kartu Anggota Digital</h3>
            <p data-id="Peminjaman koleksi wajib menggunakan akun dan kartu anggota digital resmi yang masih aktif." data-en="Borrowing library collections requires an active official digital membership card and account.">Peminjaman koleksi wajib menggunakan akun dan kartu anggota digital resmi yang masih aktif.</p>
          </div>
          <div class="card-trigger-bar">
            <span class="flow-pill-btn"><span class="flow-pill-tag">ALUR</span> Lihat Rincian &rsaquo;</span>
          </div>
        </li>

        <li class="reveal-item flow-card" onclick="bukaFlowPopup('tata-tertib-3')" tabindex="0" role="button" aria-label="Buka Alur Batas & Durasi Pinjam">
          <div>
            <span class="num">03</span>
            <h3 data-id="Batas & Durasi Pinjam" data-en="Borrowing Limit & Duration">Batas & Durasi Pinjam</h3>
            <p data-id="Buku dipinjamkan maksimal 7 hari kalender dan dapat diperpanjang sebelum jatuh tempo jika tidak diantrekan." data-en="Books can be borrowed for a maximum of 7 calendar days and may be renewed before due date if not reserved.">Buku dipinjamkan maksimal 7 hari kalender dan dapat diperpanjang sebelum jatuh tempo jika tidak diantrekan.</p>
          </div>
          <div class="card-trigger-bar">
            <span class="flow-pill-btn"><span class="flow-pill-tag">ALUR</span> Lihat Rincian &rsaquo;</span>
          </div>
        </li>

        <li class="reveal-item flow-card" onclick="bukaFlowPopup('tata-tertib-4')" tabindex="0" role="button" aria-label="Buka Alur Keterlambatan & Denda">
          <div>
            <span class="num">04</span>
            <h3 data-id="Keterlambatan & Denda" data-en="Late Returns & Fines">Keterlambatan & Denda</h3>
            <p data-id="<?= htmlspecialchars($denda_text) ?>" data-en="<?= htmlspecialchars($denda_text_en) ?>"><?= htmlspecialchars($denda_text) ?></p>
          </div>
          <div class="card-trigger-bar">
            <span class="flow-pill-btn"><span class="flow-pill-tag">ALUR</span> Lihat Rincian &rsaquo;</span>
          </div>
        </li>

        <li class="reveal-item flow-card" onclick="bukaFlowPopup('tata-tertib-5')" tabindex="0" role="button" aria-label="Buka Alur Merawat Koleksi Buku">
          <div>
            <span class="num">05</span>
            <h3 data-id="Merawat Koleksi Buku" data-en="Caring for Books">Merawat Koleksi Buku</h3>
            <p data-id="Peminjam wajib menjaga kebersihan dan keutuhan buku fisik; kerusakan atau kehilangan menjadi tanggung jawab peminjam." data-en="Borrowers must maintain the cleanliness and condition of books; damage or loss is the borrower's responsibility.">Peminjam wajib menjaga kebersihan dan keutuhan buku fisik; kerusakan atau kehilangan menjadi tanggung jawab peminjam.</p>
          </div>
          <div class="card-trigger-bar">
            <span class="flow-pill-btn"><span class="flow-pill-tag">ALUR</span> Lihat Rincian &rsaquo;</span>
          </div>
        </li>

        <li class="reveal-item flow-card" onclick="bukaFlowPopup('tata-tertib-6')" tabindex="0" role="button" aria-label="Buka Alur Ketertiban Ruang Baca">
          <div>
            <span class="num">06</span>
            <h3 data-id="Ketertiban Ruang Baca" data-en="Reading Room Etiquette">Ketertiban Ruang Baca</h3>
            <p data-id="Jaga ketenangan di area baca, letakkan buku yang selesai dibaca pada tempatnya, dan jaga kebersihan ruangan." data-en="Maintain quiet in the reading area, return finished books to their designated places, and keep the room clean.">Jaga ketenangan di area baca, letakkan buku yang selesai dibaca pada tempatnya, dan jaga kebersihan ruangan.</p>
          </div>
          <div class="card-trigger-bar">
            <span class="flow-pill-btn"><span class="flow-pill-tag">ALUR</span> Lihat Rincian &rsaquo;</span>
          </div>
        </li>
      </ul>
    </div>
  </section>

  <!-- ═══════════ SLIDE 4 — TENTANG KATALOG PERPUSTAKAAN ═══════════ -->
  <section id="slide-4" class="slide slide-about">
    <div class="about-wrap reveal">
      <div class="about-head">
        <span class="eyebrow" data-id="Katalog Digital" data-en="Digital Catalog">Katalog Digital</span>
        <h2 data-id="Tentang Katalog Perpustakaan" data-en="About the Library Catalog">Tentang Katalog Perpustakaan</h2>
        <p class="lead" data-id="AKSA NOVA adalah katalog perpustakaan digital terintegrasi untuk mencari buku, mengecek stok fisik secara realtime, dan mengajukan peminjaman dengan mudah." data-en="AKSA NOVA is an integrated digital library catalog to search books, check physical stock in real time, and request loans easily.">AKSA NOVA adalah katalog perpustakaan digital terintegrasi untuk mencari buku, mengecek stok fisik secara realtime, dan mengajukan peminjaman dengan mudah.</p>
      </div>

      <div class="about-grid reveal-stagger">
        <div class="about-card reveal-item flow-card" onclick="bukaFlowPopup('tentang-katalog-1')" tabindex="0" role="button" aria-label="Buka Alur Cek Ketersediaan Realtime">
          <div>
            <span class="about-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            <h3 data-id="Cek Ketersediaan Realtime" data-en="Real-Time Availability Check">Cek Ketersediaan Realtime</h3>
            <p data-id="Pantau jumlah stok eksemplar fisik dan status ketersediaannya secara langsung sebelum berkunjung atau mengajukan pinjaman." data-en="Monitor physical copy counts and real-time availability status before visiting or submitting a loan request.">Pantau jumlah stok eksemplar fisik dan status ketersediaannya secara langsung sebelum berkunjung atau mengajukan pinjaman.</p>
          </div>
          <div class="card-trigger-bar">
            <span class="flow-pill-btn"><span class="flow-pill-tag">ALUR</span> Lihat Rincian &rsaquo;</span>
          </div>
        </div>

        <div class="about-card reveal-item flow-card" onclick="bukaFlowPopup('tentang-katalog-2')" tabindex="0" role="button" aria-label="Buka Alur Akses dengan Kartu Anggota">
          <div>
            <span class="about-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="5" width="19" height="14" rx="2.5"/><circle cx="8.5" cy="12" r="2"/><path d="M13.5 10.5h5M13.5 13.5h3.5"/></svg>
            </span>
            <h3 data-id="Akses dengan Kartu Anggota" data-en="Access with Member Card">Akses dengan Kartu Anggota</h3>
            <p data-id="Peminjaman buku dilayani untuk seluruh anggota perpustakaan yang memiliki akun dan kartu anggota digital aktif." data-en="Book borrowing is available for all library members with an active account and digital membership card.">Peminjaman buku dilayani untuk seluruh anggota perpustakaan yang memiliki akun dan kartu anggota digital aktif.</p>
          </div>
          <div class="card-trigger-bar">
            <span class="flow-pill-btn"><span class="flow-pill-tag">ALUR</span> Lihat Rincian &rsaquo;</span>
          </div>
        </div>

        <div class="about-card reveal-item flow-card" onclick="bukaFlowPopup('tentang-katalog-3')" tabindex="0" role="button" aria-label="Buka Alur Pengajuan & Sirkulasi Buku">
          <div>
            <span class="about-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M4 21V9l8-5 8 5v12"/><path d="M9 21v-7h6v7"/></svg>
            </span>
            <h3 data-id="Pengajuan & Sirkulasi Buku" data-en="Book Request & Circulation">Pengajuan & Sirkulasi Buku</h3>
            <p data-id="Anggota dapat mengajukan peminjaman secara online melalui web, lalu mengambil buku fisik di meja sirkulasi perpustakaan." data-en="Members can request book loans online via the website, then pick up the physical books at the circulation desk.">Anggota dapat mengajukan peminjaman secara online melalui web, lalu mengambil buku fisik di meja sirkulasi perpustakaan.</p>
          </div>
          <div class="card-trigger-bar">
            <span class="flow-pill-btn"><span class="flow-pill-tag">ALUR</span> Lihat Rincian &rsaquo;</span>
          </div>
        </div>

        <div class="about-card reveal-item flow-card" onclick="bukaFlowPopup('tentang-katalog-4')" tabindex="0" role="button" aria-label="Buka Alur Pendaftaran Anggota Mandiri">
          <div>
            <span class="about-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 21v-8a3 3 0 0 0-6 0v8"/><circle cx="12" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
            </span>
            <h3 data-id="Pendaftaran Anggota Mandiri" data-en="Self Member Registration">Pendaftaran Anggota Mandiri</h3>
            <p data-id="Belum memiliki akun? Daftarkan diri secara online melalui menu Registrasi untuk mendapatkan kartu anggota digital resmi ber-barcode." data-en="Don't have an account? Register online through the Sign Up menu to receive an official barcoded digital membership card.">Belum memiliki akun? Daftarkan diri secara online melalui menu Registrasi untuk mendapatkan kartu anggota digital resmi ber-barcode.</p>
          </div>
          <div class="card-trigger-bar">
            <span class="flow-pill-btn"><span class="flow-pill-tag">ALUR</span> Lihat Rincian &rsaquo;</span>
          </div>
        </div>

        <div class="about-card reveal-item flow-card" onclick="bukaFlowPopup('tentang-katalog-5')" tabindex="0" role="button" aria-label="Buka Alur Fitur Simpan Buku (Wishlist)">
          <div>
            <span class="about-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9a2 2 0 0 1 2 2v16l-6.5-4L4 21V5a2 2 0 0 1 2-2Z"/></svg>
            </span>
            <h3 data-id="Fitur Simpan Buku (Wishlist)" data-en="Save Book Feature (Wishlist)">Fitur Simpan Buku (Wishlist)</h3>
            <p data-id="Tandai buku yang ingin kamu baca ke daftar Buku Disimpan untuk mempermudah pencarian dan penelusuran lokasi rak saat berkunjung." data-en="Bookmark books you want to read to your Saved Books list for quick retrieval and shelf location tracking when you visit.">Tandai buku yang ingin kamu baca ke daftar Buku Disimpan untuk mempermudah pencarian dan penelusuran lokasi rak saat berkunjung.</p>
          </div>
          <div class="card-trigger-bar">
            <span class="flow-pill-btn"><span class="flow-pill-tag">ALUR</span> Lihat Rincian &rsaquo;</span>
          </div>
        </div>

        <div class="about-card reveal-item flow-card" onclick="bukaFlowPopup('tentang-katalog-6')" tabindex="0" role="button" aria-label="Buka Alur Data Terintegrasi & Akurat">
          <div>
            <span class="about-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.6-6.35"/><path d="M21 3v5h-5"/></svg>
            </span>
            <h3 data-id="Data Terintegrasi & Akurat" data-en="Integrated & Accurate Data">Data Terintegrasi & Akurat</h3>
            <p data-id="Informasi stok fisik, status pinjam, dan penambahan judul baru tersinkronisasi langsung dengan sistem perpustakaan sekolah." data-en="Physical stock info, loan statuses, and new collection additions are directly synchronized with the school library system.">Informasi stok fisik, status pinjam, dan penambahan judul baru tersinkronisasi langsung dengan sistem perpustakaan sekolah.</p>
          </div>
          <div class="card-trigger-bar">
            <span class="flow-pill-btn"><span class="flow-pill-tag">ALUR</span> Lihat Rincian &rsaquo;</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══════════ SLIDE 5 — LOKASI PERPUSTAKAAN ═══════════ -->
  <section id="slide-5" class="slide slide-location">
    <div class="frame reveal">
      <span class="corner corner-tl"></span><span class="corner corner-tr"></span>
      <span class="corner corner-bl"></span><span class="corner corner-br"></span>
      <span class="frame-label" data-id="Lokasi Kami" data-en="Our Location">Lokasi Kami</span>

      <div class="location-grid">
        <div class="location-info reveal-stagger">
          <p class="eyebrow reveal-item" data-id="Kunjungi Kami" data-en="Visit Us">Kunjungi Kami</p>
          <h2 class="reveal-item" data-id="Lokasi Perpustakaan" data-en="Library Location">Lokasi Perpustakaan</h2>
          <p class="lead reveal-item" data-id="Datang langsung ke perpustakaan kami untuk meminjam buku dan menikmati ruang baca yang nyaman." data-en="Visit our library in person to borrow books and enjoy a comfortable reading space.">Datang langsung ke perpustakaan kami untuk meminjam buku dan menikmati ruang baca yang nyaman.</p>

          <ul class="location-list reveal-item">
            <li>
              <span class="location-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.1-7-11a7 7 0 1 1 14 0c0 4.9-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
              </span>
              <div>
                <h4 data-id="Alamat" data-en="Address">Alamat</h4>
                <p><strong><?= htmlspecialchars($lokasi_sekolah) ?></strong><br><?= htmlspecialchars($lokasi_alamat) ?></p>
              </div>
            </li>
            <li>
              <span class="location-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
              </span>
              <div>
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;">
                  <h4 data-id="Jam Operasional" data-en="Operating Hours" style="margin:0;">Jam Operasional</h4>
                  <span class="status-live-chip <?= $status_badge_type === 'buka' ? 'chip-open' : 'chip-closed' ?>"><?= htmlspecialchars($status_badge_text) ?></span>
                </div>
                <p><?= htmlspecialchars($lokasi_jam) ?></p>
                <?php if (!empty($operasional_catatan)): ?>
                  <p style="font-size:.76rem;opacity:.78;margin-top:3px;font-style:italic;line-height:1.4;"><?= htmlspecialchars($operasional_catatan) ?></p>
                <?php endif; ?>
              </div>
            </li>
            <li>
              <span class="location-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7a2 2 0 0 1 1.72 2.03Z"/></svg>
              </span>
              <div>
                <h4 data-id="Kontak" data-en="Contact">Kontak</h4>
                <p><?= htmlspecialchars($lokasi_telepon) ?></p>
                <?php if (!empty($lokasi_email)): ?>
                  <p style="font-size:.8rem;opacity:.8;margin-top:2px;"><?= htmlspecialchars($lokasi_email) ?></p>
                <?php endif; ?>
              </div>
            </li>
          </ul>

          <a href="<?= htmlspecialchars($lokasi_maps_link) ?>" target="_blank" rel="noopener" class="cta-solid reveal-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.1-7-11a7 7 0 1 1 14 0c0 4.9-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
            <span data-id="Buka di Google Maps" data-en="Open in Google Maps">Buka di Google Maps</span>
          </a>
        </div>

        <div class="location-map reveal-item">
          <iframe src="<?= htmlspecialchars($lokasi_maps_embed) ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Peta Lokasi Perpustakaan"></iframe>
        </div>
      </div>
    </div>
  </section>

  <section id="slide-6" class="slide slide-footer">

  <div class="footer-glow" aria-hidden="true"><span></span><span></span></div>

  <div class="footer-cta reveal reveal-stagger">
    <p class="eyebrow reveal-item" data-id="Mulai Sekarang" data-en="Get Started">Mulai Sekarang</p>
    <h2 class="reveal-item" data-id="Siap Menjelajahi Rak Digital Kami?" data-en="Ready to Explore Our Digital Shelves?">Siap Menjelajahi Rak Digital Kami?</h2>
    <p class="lead reveal-item" data-id="Masuk sebagai anggota untuk memberi rating, menyimpan wishlist, dan memantau riwayat peminjamanmu." data-en="Sign in as a member to rate books, save your wishlist, and track your borrowing history.">Masuk sebagai anggota untuk memberi rating, menyimpan wishlist, dan memantau riwayat peminjamanmu.</p>

    <div class="footer-highlights reveal-item">
      <span class="meta-chip">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="5" width="19" height="14" rx="2.5"/><circle cx="8.5" cy="12" r="2"/><path d="M13.5 10.5h5M13.5 13.5h3.5"/></svg>
        <span data-id="Kartu anggota dibuatkan admin" data-en="Membership card made by admin">Kartu anggota dibuatkan admin</span>
      </span>
      <span class="meta-chip">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v17H6.5A2.5 2.5 0 0 0 4 21.5v-17Z"/><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/></svg>
        <span data-id="Katalog selalu diperbarui" data-en="Catalog always up to date">Katalog selalu diperbarui</span>
      </span>
      <span class="meta-chip">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17.3 6.2 21l1.6-6.6L2.5 9.9l6.8-.6L12 3l2.7 6.3 6.8.6-5.3 4.5 1.6 6.6Z"/></svg>
        <span data-id="Rating & wishlist tersimpan" data-en="Ratings & wishlist saved">Rating &amp; wishlist tersimpan</span>
      </span>
    </div>

    <div class="footer-cta-actions reveal-item">
      <a href="sign_in.php" class="cta-solid">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 5v14l11-7z"/></svg>
        <span data-id="Masuk & Jelajahi" data-en="Sign In & Explore">Masuk &amp; Jelajahi</span>
      </a>
      <a href="#slide-2" class="cta-outline">
        <span data-id="Lihat Buku Terbaru" data-en="See Latest Books">Lihat Buku Terbaru</span>
      </a>
    </div>
  </div>

  <footer class="site-footer reveal">
    <div class="footer-top reveal-stagger">
      <div class="footer-brand reveal-item">
        <div class="brand">AKSA <span>NOVA</span></div>
        <p data-id="Katalog perpustakaan digital untuk mengecek koleksi dan ketersediaan buku sebelum berkunjung langsung." data-en="A digital library catalog to check the collection and book availability before visiting in person.">Katalog perpustakaan digital untuk mengecek koleksi dan ketersediaan buku sebelum berkunjung langsung.</p>
      </div>

      <div class="footer-col footer-nav reveal-item">
        <h4 data-id="Navigasi" data-en="Navigation">Navigasi</h4>
        <ul>
          <li><a href="#slide-1" data-id="Beranda" data-en="Home">Beranda</a></li>
          <li><a href="#slide-2" data-id="Buku Terbaru" data-en="Latest Books">Buku Terbaru</a></li>
          <li><a href="#slide-3" data-id="Aturan Perpustakaan" data-en="Library Rules">Aturan Perpustakaan</a></li>
          <li><a href="#slide-4" data-id="Tentang Katalog" data-en="About the Catalog">Tentang Katalog</a></li>
          <li><a href="#slide-5" data-id="Lokasi Perpustakaan" data-en="Library Location">Lokasi Perpustakaan</a></li>
        </ul>
      </div>

      <div class="footer-col footer-acc reveal-item">
        <h4 data-id="Akun" data-en="Account">Akun</h4>
        <ul>
          <li><a href="sign_in.php" data-id="Masuk Anggota" data-en="Member Sign In">Masuk Anggota</a></li>
          <li><a href="sign_in.php" data-id="Wishlist Saya" data-en="My Wishlist">Wishlist Saya</a></li>
        </ul>
      </div>

      <div class="footer-col footer-social reveal-item">
        <h4 data-id="Ikuti Kami" data-en="Follow Us">Ikuti Kami</h4>
        <div class="footer-social-links">
          <a href="https://www.tiktok.com/@jinggasmkn1rongga?is_from_webapp=1&sender_device=pc" target="_blank" rel="noopener" title="TikTok SMKN 1 Rongga">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.5 2c.4 2.2 1.8 3.9 4 4.4v3.1c-1.4 0-2.8-.4-4-1.2v6.4c0 3.5-2.8 6.3-6.3 6.3S3.9 18.2 3.9 14.7s2.8-6.3 6.3-6.3c.3 0 .6 0 .9.1v3.2c-.3-.1-.6-.2-.9-.2-1.8 0-3.2 1.4-3.2 3.2s1.4 3.2 3.2 3.2 3.3-1.4 3.3-3.2V2h3z"/></svg>
          </a>
          <a href="https://www.instagram.com/smkn1rongga/" target="_blank" rel="noopener" title="Instagram SMKN 1 Rongga">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="18" height="18" rx="5"/>
              <circle cx="12" cy="12" r="4"/>
              <circle cx="17.3" cy="6.7" r="0.6" fill="currentColor" stroke="none"/>
            </svg>
          </a>
          <a href="https://share.google/U4hL457xrbEKTbj31" target="_blank" rel="noopener" title="Facebook SMKN 1 Rongga">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M13.6 21v-7.6h2.6l.4-3h-3V8.4c0-.9.2-1.5 1.5-1.5H16.7V4.2c-.3 0-1.2-.2-2.3-.2-2.3 0-3.9 1.4-3.9 4v2.4H7.9v3h2.6V21h3.1z"/></svg>
          </a>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <p><span data-id="&copy; <?= date("Y") ?> AKSA NOVA — Katalog Perpustakaan Digital. Seluruh hak cipta dilindungi." data-en="&copy; <?= date("Y") ?> AKSA NOVA — Digital Library Catalog. All rights reserved.">&copy; <?= date("Y") ?> AKSA NOVA — Katalog Perpustakaan Digital. Seluruh hak cipta dilindungi.</span></p>
    </div>
  </footer>
  </section>

</main>

<div class="ticker-bar" id="tickerBar">
  <button type="button" class="ticker-close" id="tickerClose" aria-label="Tutup notifikasi"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
  <div class="ticker-divider"></div>
  <div class="ticker-text"><span class="ticker-dot" aria-hidden="true"></span><span data-id="Selamat datang di perpustakaan digital AKSA NOVA — masuk untuk mulai meminjam buku." data-en="Welcome to the AKSA NOVA digital library — sign in to start borrowing books.">Selamat datang di perpustakaan digital AKSA NOVA — masuk untuk mulai meminjam buku.</span></div>
  <a href="sign_in.php" class="ticker-cta" data-id="Masuk" data-en="Sign In">Masuk</a>
</div>
<button type="button" class="ticker-reopen" id="tickerReopen" aria-label="Tampilkan notifikasi">
  <span class="dot"></span> Info
</button>

<script>
  // Tombol tutup / buka kembali ticker bar bawah
  (function () {
    const body        = document.body;
    const closeBtn    = document.getElementById('tickerClose');
    const reopenBtn   = document.getElementById('tickerReopen');
    const STORAGE_KEY = 'aksanova_ticker_hidden';

    if (sessionStorage.getItem(STORAGE_KEY) === '1') {
      body.classList.add('ticker-hidden');
    }

    closeBtn?.addEventListener('click', () => {
      body.classList.add('ticker-hidden');
      sessionStorage.setItem(STORAGE_KEY, '1');
    });

    reopenBtn?.addEventListener('click', () => {
      body.classList.remove('ticker-hidden');
      sessionStorage.setItem(STORAGE_KEY, '0');
    });
  })();

  // Mode gelap / terang — pilihan pengunjung disimpan permanen di perangkat ini
  (function () {
    const btn  = document.getElementById('btnMode');
    if (!btn) return;
    const root = document.documentElement;
    const STORAGE_KEY = 'aksanova_theme';

    function updatePressed() {
      btn.setAttribute('aria-pressed', root.classList.contains('theme-light') ? 'true' : 'false');
    }
    updatePressed();

    btn.addEventListener('click', () => {
      root.classList.toggle('theme-light');
      const isLight = root.classList.contains('theme-light');
      const newTheme = isLight ? 'light' : 'dark';
      try {
        localStorage.setItem(STORAGE_KEY, newTheme);
        const s = JSON.parse(localStorage.getItem('aksanova_settings') || '{}');
        s.mode = newTheme;
        localStorage.setItem('aksanova_settings', JSON.stringify(s));
      } catch (e) {}
      updatePressed();
    });
  })();

  // Tampilan ringan — matikan animasi/blur agar halaman lebih ringan & lancar,
  // terutama untuk perangkat atau koneksi yang lebih lemah. Pilihan disimpan permanen.
  (function () {
    const btn  = document.getElementById('btnLite');
    if (!btn) return;
    const root = document.documentElement;
    const STORAGE_KEY = 'aksanova_lite';

    function updatePressed() {
      const aktif = root.classList.contains('lite');
      btn.classList.toggle('active', aktif);
      btn.setAttribute('aria-pressed', aktif ? 'true' : 'false');
    }
    updatePressed();

    btn.addEventListener('click', () => {
      root.classList.toggle('lite');
      const aktif = root.classList.contains('lite');
      try { localStorage.setItem(STORAGE_KEY, aktif ? '1' : '0'); } catch (e) {}
      updatePressed();
    });
  })();





  // Tombol Bagikan Website — Web Share API bawaan / salin link ke clipboard + toast
  (function () {
    const btn   = document.getElementById('btnShare');
    const toast = document.getElementById('shareToast');
    if (!btn) return;

    let toastTimer = null;
    function tampilkanToast(teks) {
      if (!toast) return;
      toast.textContent = teks;
      toast.classList.add('show');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(() => toast.classList.remove('show'), 2400);
    }

    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const shareUrl = window.location.origin + window.location.pathname;
      const dataBagikan = {
        title: 'AKSA NOVA — Perpustakaan Digital SMKN 1 Rongga',
        text: 'Jelajahi koleksi buku dan literasi digital di AKSA NOVA',
        url: shareUrl
      };
      if (navigator.share) {
        try {
          await navigator.share(dataBagikan);
          return;
        } catch (err) {
          if (err.name === 'AbortError') return;
        }
      }
      try {
        await navigator.clipboard.writeText(shareUrl);
        tampilkanToast('Tautan website berhasil disalin!');
      } catch (err) {
        const temp = document.createElement('input');
        temp.value = shareUrl;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        tampilkanToast('Tautan website berhasil disalin!');
      }
    });

    // Keyboard shortcut universal: Alt + D atau Ctrl + Shift + D untuk toggle mode gelap/terang
    document.addEventListener('keydown', (e) => {
      if ((e.altKey && (e.key === 'd' || e.key === 'D')) || ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'd' || e.key === 'D'))) {
        e.preventDefault();
        const btnMode = document.getElementById('btnMode');
        if (btnMode) {
          btnMode.click();
          tampilkanToast(document.documentElement.classList.contains('theme-light') ? 'Mode Terang diaktifkan (Alt+D)' : 'Mode Gelap diaktifkan (Alt+D)');
        }
      }
    });
  })();

  // Musik latar — sinkron persisten dan dikelola terpusat oleh AksaAudio di settings_include.php
  if (window.AksaAudio && window.AksaAudio.init) {
    window.AksaAudio.init();
  }

  // Animasi scroll-reveal untuk konten tiap slide — diputar ulang setiap kali
  // slide tersebut masuk ke layar (baik scroll ke bawah maupun ke atas), supaya
  // transisi antar slide terasa smooth setiap saat, bukan cuma sekali di awal.
  const revealTargets = document.querySelectorAll('.reveal, .reveal-stagger');
  if (revealTargets.length) {
    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        entry.target.classList.toggle('in-view', entry.isIntersecting);
      });
    }, { threshold: 0.18 });
    revealTargets.forEach(el => revealObserver.observe(el));
  }

  // Scrollspy untuk dot navigation
  const slides = document.querySelectorAll('.slide');
  const dots   = document.querySelectorAll('.dot');

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        dots.forEach(d => d.classList.remove('active'));
        const target = document.querySelector(`.dot[data-target="${entry.target.id}"]`);
        if (target) target.classList.add('active');
      }
    });
  }, { threshold: 0.6 });

  slides.forEach(s => observer.observe(s));

  dots.forEach(dot => {
    dot.addEventListener('click', () => {
      document.getElementById(dot.dataset.target).scrollIntoView({ behavior: 'smooth' });
    });
  });

  // Animasi cover buku di slide 2 saat diklik — ikon minat baca bermunculan
  (function () {
    const wrap  = document.getElementById('bookCoverWrap');
    const cover = document.getElementById('bookCover');
    const ikonList = Array.from(wrap ? wrap.querySelectorAll('.cover-icon') : []);
    if (!wrap || !cover) return;

    let sedangBuka = false;
    let timerReset = null;
    const JEDA_AWAL   = 0.15;
    const JEDA_ANTARA = 0.22;

    function acakUrutanIkon() {
      const acak = [...ikonList];
      for (let i = acak.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [acak[i], acak[j]] = [acak[j], acak[i]];
      }
      acak.forEach((el, i) => {
        el.style.animationDelay = (JEDA_AWAL + i * JEDA_ANTARA) + 's';
      });
    }

    function resetAnimasi() {
      wrap.classList.remove('opening');
      sedangBuka = false;
      if (timerReset) clearTimeout(timerReset);
      const bText = document.getElementById('qteBookText');
      if (bText) bText.textContent = 'Klik Aku!';
    }

    cover.addEventListener('animationend', resetAnimasi);

    function mainkanAnimasi() {
      if (sedangBuka) return;
      sedangBuka = true;
      acakUrutanIkon();
      wrap.classList.add('opening');
      const bText = document.getElementById('qteBookText');
      if (bText) bText.textContent = 'Terbuka!';
      if (timerReset) clearTimeout(timerReset);
      timerReset = setTimeout(resetAnimasi, 2600);
    }

    wrap.addEventListener('click', mainkanAnimasi);
    wrap.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        mainkanAnimasi();
      }
    });
  })();

  // Ganti detail buku utama saat thumbnail diklik
  document.querySelectorAll('.thumb').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
      btn.classList.add('active');

      document.getElementById('bukuJudul').textContent    = btn.dataset.judul;
      document.getElementById('bukuPenulis').textContent  = btn.dataset.penulis;
      document.getElementById('bukuGenre').textContent    = btn.dataset.genre;
      document.getElementById('bukuSinopsis').textContent = btn.dataset.sinopsis;

      const img = document.getElementById('bukuGambar');
      img.style.opacity = 0;
      setTimeout(() => {
        img.src = btn.dataset.gambar;
        img.style.opacity = 1;
      }, 180);
    });
  });

  // ═══════════ DATA & CONTROLLER POP-UP ALUR / ATURAN ═══════════
  const flowPopupData = {
    'tata-tertib-1': {
      badge: 'ATURAN PERPUSTAKAAN #01',
      title: 'Alur Jam Layanan & Kunjungan',
      desc: 'Jadwal pelayanan ruang baca dan sirkulasi peminjaman SMKN 1 Rongga.',
      actionText: 'Lihat Lokasi & Jam',
      actionHref: '#slide-5',
      steps: [
        { num: '1', title: '07.00 – 09.30 WIB: Layanan Pagi & Literasi', desc: 'Pembukaan ruang baca, sirkulasi materi pelajaran pagi, dan penyiapan buku pegangan kelas untuk guru.' },
        { num: '2', title: 'Jam Istirahat 1 & 2: Sirkulasi Kilat Siswa', desc: 'Puncak peminjaman buku bacaan mandiri dan pengembalian kilat tanpa antrean berbelit.' },
        { num: '3', title: '13.00 – 15.00 WIB: Rekapitulasi & Penutupan Meja', desc: 'Penyusunan eksemplar ke rak katalog, pengembalian buku paket kelas, dan rekonsiliasi harian.' }
      ]
    },
    'tata-tertib-2': {
      badge: 'ATURAN PERPUSTAKAAN #02',
      title: 'Alur Kartu Anggota Digital',
      desc: 'Tahapan kepemilikan kartu anggota digital terintegrasi barcode untuk akses sirkulasi perpustakaan.',
      actionText: 'Daftar Akun Anggota',
      actionHref: 'sign_up.php',
      steps: [
        { num: '1', title: 'Pendaftaran Online Mandiri', desc: 'Buka halaman registrasi mandiri (sign_up.php), isi NIS/NIP, WhatsApp, email, dan unggah pas foto profil.' },
        { num: '2', title: 'Penerbitan Kartu Digital Instan', desc: 'Kartu anggota otomatis terbit dilengkapi barcode/QR unik dan penyamaran sandi demi privasi.' },
        { num: '3', title: 'Verifikasi di Meja Sirkulasi', desc: 'Tunjukkan kartu digital di layar ponsel saat meminjam buku fisik di perpustakaan sekolah.' }
      ]
    },
    'tata-tertib-3': {
      badge: 'ATURAN PERPUSTAKAAN #03',
      title: 'Alur Batas & Durasi Peminjaman',
      desc: 'Ketentuan kuantitas buku dan mekanisme perpanjangan masa pinjam resmi.',
      actionText: 'Jelajahi Koleksi Buku',
      actionHref: 'daftar_buku.php',
      steps: [
        { num: '1', title: 'Hak Pinjam Siswa & Guru', desc: 'Siswa dapat meminjam hingga 3 buku secara mandiri; Guru pengampu dapat meminjam hingga 40 buku paket pelajaran.' },
        { num: '2', title: 'Masa Pinjam Standar 7 Hari', desc: 'Buku dipinjamkan selama 7 hari kalender dengan tanggal jatuh tempo tercatat di akun dan kartu anggota.' },
        { num: '3', title: 'Perpanjangan Sebelum Jatuh Tempo', desc: 'Perpanjangan dapat diajukan ke petugas sirkulasi sebelum tempo berakhir selama buku tidak diantrekan.' }
      ]
    },
    'tata-tertib-4': {
      badge: 'ATURAN PERPUSTAKAAN #04',
      title: 'Alur Penanganan Keterlambatan & Denda',
      desc: 'Mekanisme pencatatan keterlambatan dan penyelesaian denda secara transparan.',
      actionText: 'Masuk Cek Tagihan',
      actionHref: 'sign_in.php',
      steps: [
        { num: '1', title: 'Masa Toleransi (Grace Period)', desc: 'Sistem memberikan kelonggaran masa tenggang setelah batas kembali sebelum denda aktif terhitung.' },
        { num: '2', title: '<?= $denda_aktif ? "Kalkulasi Otomatis Rp " . number_format($denda_per_hari, 0, ",", ".") . " / Hari" : "Pencatatan Keterlambatan Otomatis" ?>', desc: '<?= $denda_aktif ? "Denda bertambah otomatis per hari keterlambatan per buku sesuai tarif aktif yang ditetapkan sekolah." : "Keterlambatan tercatat otomatis oleh sistem perpustakaan pada riwayat akun anggota." ?>' },
        { num: '3', title: 'Pengingat WhatsApp & Pelunasan', desc: 'Pemberitahuan tagihan dapat dikirimkan ke WhatsApp anggota; pelunasan diselesaikan di meja sirkulasi.' }
      ]
    },
    'tata-tertib-5': {
      badge: 'ATURAN PERPUSTAKAAN #05',
      title: 'Alur Tanggung Jawab Menjaga Koleksi',
      desc: 'Panduan menjaga keutuhan buku fisik selama berada di tangan peminjam.',
      actionText: 'Aturan Perpustakaan',
      actionHref: '#slide-3',
      steps: [
        { num: '1', title: 'Pemeriksaan Awal Saat Serah Terima', desc: 'Pastikan buku dalam kondisi baik, halaman lengkap, dan bersih sebelum meninggalkan meja petugas.' },
        { num: '2', title: 'Penyimpanan & Penggunaan Aman', desc: 'Gunakan pembatas buku resmi, hindari melipat ujung lembar (dog-ear), dan simpan terlindung dari air.' },
        { num: '3', title: 'Lapor & Ganti Jika Terjadi Kerusakan', desc: 'Jika terjadi kehilangan atau kerusakan tidak terduga, segera laporkan ke pustakawan untuk penyesuaian.' }
      ]
    },
    'tata-tertib-6': {
      badge: 'ATURAN PERPUSTAKAAN #06',
      title: 'Alur Ketertiban Ruang Baca',
      desc: 'Tata krama dan etika menjaga kenyamanan ruang belajar bersama.',
      actionText: 'Kunjungi Lokasi Kami',
      actionHref: '#slide-5',
      steps: [
        { num: '1', title: 'Penitipan Barang di Loker', desc: 'Simpan tas, jaket, dan botol minum pada loker di dekat pintu sebelum masuk ke area koleksi buku.' },
        { num: '2', title: 'Zona Hening (Silent Reading)', desc: 'Atur ponsel ke mode getar/senyap dan berbicara dengan suara berbisik demi kenyamanan rekan belajar.' },
        { num: '3', title: 'Letakkan di Tempat Pengembalian', desc: 'Setelah membaca di tempat, taruh buku di troli/meja pengembalian agar dirapikan kembali oleh pustakawan.' }
      ]
    },

    'tentang-katalog-1': {
      badge: 'TENTANG KATALOG #01',
      title: 'Alur Cek Ketersediaan Buku Realtime',
      desc: 'Cara menelusuri ketersediaan eksemplar buku secara online sebelum berkunjung.',
      actionText: 'Cari di Katalog Sekarang',
      actionHref: 'daftar_buku.php',
      steps: [
        { num: '1', title: 'Ketik Judul, Penulis, atau Genre', desc: 'Gunakan kotak pencarian cepat di katalog atau landing page untuk menemukan judul buku yang dicari.' },
        { num: '2', title: 'Periksa Label Status Ketersediaan', desc: 'Label hijau Tersedia menunjukkan stok buku fisik siap dipinjam; label merah Kosong jika sedang dipinjam.' },
        { num: '3', title: 'Catat Kode Penomoran Rak Fisik', desc: 'Lihat info kode rak fisik (contoh: Rak B-1) yang tertera pada detail buku untuk langsung menuju rak tanpa bingung.' }
      ]
    },
    'tentang-katalog-2': {
      badge: 'TENTANG KATALOG #02',
      title: 'Alur Akses dengan Kartu Anggota',
      desc: 'Pentingnya kartu anggota sebagai identitas resmi terintegrasi sistem perpustakaan.',
      actionText: 'Masuk Profil Anggota',
      actionHref: 'sign_in.php',
      steps: [
        { num: '1', title: 'Nomor Identitas Unik AN-YYYY', desc: 'Setiap kartu memiliki nomor unik yang tersinkronisasi otomatis dengan akun basis data Anda.' },
        { num: '2', title: 'Barcode & QR Code Terintegrasi', desc: 'Kartu dilengkapi kode barcode/QR untuk pemindaian cepat di meja sirkulasi perpustakaan.' },
        { num: '3', title: 'Pencegahan Penyalahgunaan Akun', desc: 'Peminjaman wajib memverifikasi kartu aktif guna mencegah peminjaman tanpa izin oleh pihak lain.' }
      ]
    },
    'tentang-katalog-3': {
      badge: 'TENTANG KATALOG #03',
      title: 'Alur Pengajuan & Peminjaman Buku',
      desc: 'Langkah peminjaman buku baik melalui pengajuan online maupun langsung di perpustakaan.',
      actionText: 'Jelajahi Daftar Buku',
      actionHref: 'daftar_buku.php',
      steps: [
        { num: '1', title: 'Pilih Buku & Ajukan Pinjam', desc: 'Anggota dapat mengajukan peminjaman buku melalui tombol Ajukan Pinjam di web atau mencari nomor rak di lokasi.' },
        { num: '2', title: 'Tunjukkan Kartu di Meja Sirkulasi', desc: 'Kunjungi perpustakaan dan tunjukkan kartu anggota digital di layar ponsel kepada petugas sirkulasi.' },
        { num: '3', title: 'Validasi Sistem & Serah Terima Buku', desc: 'Petugas memvalidasi transaksi di sistem, stok buku otomatis terpotong, dan buku fisik diserahkan.' }
      ]
    },
    'tentang-katalog-4': {
      badge: 'TENTANG KATALOG #04',
      title: 'Alur Pendaftaran Kartu Anggota Mandiri',
      desc: 'Langkah mudah mendapatkan kartu anggota digital SMKN 1 Rongga.',
      actionText: 'Daftar Kartu Sekarang',
      actionHref: 'sign_up.php',
      steps: [
        { num: '1', title: 'Buka Halaman Sign Up (Daftar)', desc: 'Akses menu pendaftaran mandiri (sign_up.php) melalui ponsel pintar atau komputer Anda.' },
        { num: '2', title: 'Lengkapi Data Diri & Pas Foto', desc: 'Isi NIS/NIP, nama lengkap, kontak WhatsApp, email sekolah, buat sandi aman, dan unggah pas foto profilmu.' },
        { num: '3', title: 'Penerbitan Kartu Digital Resmi', desc: 'Setelah disetujui petugas, kartu anggota digital ber-barcode siap diunduh dan digunakan untuk meminjam buku.' }
      ]
    },
    'tentang-katalog-5': {
      badge: 'TENTANG KATALOG #05',
      title: 'Alur Penggunaan Fitur Simpan Buku',
      desc: 'Cara menyimpan buku impian agar mudah diakses saat berkunjung ke sekolah.',
      actionText: 'Buka Buku Disimpan',
      actionHref: 'buku_simpan.php',
      steps: [
        { num: '1', title: 'Klik Ikon Bookmark (Simpan)', desc: 'Saat menjelajahi katalog atau melihat buku terbaru di beranda, klik tombol Simpan Buku.' },
        { num: '2', title: 'Tersimpan Otomatis di Akun Pribadi', desc: 'Buku masuk ke daftar koleksi pribadi pada tab menu Buku Disimpan secara real-time.' },
        { num: '3', title: 'Akses Cepat di Ruang Perpustakaan', desc: 'Tiba di sekolah, buka daftar buku tersimpan untuk langsung melihat nomor rak tanpa harus mencari ulang.' }
      ]
    },
    'tentang-katalog-6': {
      badge: 'TENTANG KATALOG #06',
      title: 'Alur Data Terintegrasi & Akurat',
      desc: 'Transparansi informasi stok fisik dan penambahan koleksi buku baru.',
      actionText: 'Lihat Buku Terbaru',
      actionHref: '#slide-2',
      steps: [
        { num: '1', title: 'Sinkronisasi Stok Realtime', desc: 'Setiap transaksi sirkulasi (pinjam/kembali/pengajuan) memperbarui sisa eksemplar di database otomatis.' },
        { num: '2', title: 'Input Cepat ISBN Google Books API', desc: 'Pustakawan menambah koleksi buku baru secara efisien dengan pemindai barcode ISBN yang menarik metadata otomatis.' },
        { num: '3', title: 'Katalog Selalu Akurat 100%', desc: 'Informasi yang kamu baca adalah cerminan langsung kondisi fisik buku di ruang perpustakaan sekolah.' }
      ]
    }
  };

  window.bukaFlowPopup = function(id) {
    const data = flowPopupData[id];
    if (!data) return;

    document.getElementById('flowPopupBadge').textContent = data.badge;
    document.getElementById('flowPopupTitle').textContent = data.title;
    document.getElementById('flowPopupDesc').textContent = data.desc;

    const actionBtn = document.getElementById('flowPopupActionBtn');
    if (actionBtn) {
      actionBtn.textContent = data.actionText;
      actionBtn.href = data.actionHref;
    }

    const timeline = document.getElementById('flowPopupTimeline');
    timeline.innerHTML = '';

    data.steps.forEach(st => {
      const item = document.createElement('div');
      item.className = 'popup-step-item';
      item.innerHTML = `
        <div class="popup-step-num">${st.num}</div>
        <div class="popup-step-text">
          <strong>${st.title}</strong>
          <p>${st.desc}</p>
        </div>
      `;
      timeline.appendChild(item);
    });

    document.getElementById('flowPopupBackdrop').classList.add('open');
    document.getElementById('flowPopup').classList.add('open');
  };

  window.tutupFlowPopup = function() {
    const backdrop = document.getElementById('flowPopupBackdrop');
    const popup = document.getElementById('flowPopup');
    if (backdrop) backdrop.classList.remove('open');
    if (popup) popup.classList.remove('open');
  };

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      tutupFlowPopup();
    }
  });
</script>





<!-- ═══════════ POP-UP DETAIL ALUR / ATURAN (COMPACT & RINGAN) ═══════════ -->
<div class="flow-popup-backdrop" id="flowPopupBackdrop" onclick="tutupFlowPopup()"></div>
<div class="flow-popup" id="flowPopup" role="dialog" aria-modal="true" aria-labelledby="flowPopupTitle">
  <div class="flow-popup-header">
    <div class="flow-popup-badge" id="flowPopupBadge">ALUR RINCI</div>
    <button type="button" class="flow-popup-close" onclick="tutupFlowPopup()" aria-label="Tutup Rincian" title="Tutup">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <div class="flow-popup-body">
    <h3 class="flow-popup-title" id="flowPopupTitle">Judul Alur</h3>
    <p class="flow-popup-desc" id="flowPopupDesc">Deskripsi tahapan alur secara rinci.</p>
    <div class="flow-popup-timeline" id="flowPopupTimeline"></div>
  </div>
  <div class="flow-popup-footer">
    <button type="button" class="flow-popup-btn-sec" onclick="tutupFlowPopup()">Tutup</button>
    <a href="daftar_buku.php" class="flow-popup-btn-pri" id="flowPopupActionBtn">Lihat Rincian</a>
  </div>
</div>

</body>
</html>
