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
    ? "Denda keterlambatan berlaku sebesar Rp" . number_format($denda_per_hari, 0, ",", ".") . " per hari."
    : "Saat ini denda keterlambatan sedang tidak diaktifkan, namun keterlambatan tetap tercatat di sistem.";
$denda_text_en = $denda_aktif
    ? "A late fee of Rp" . number_format($denda_per_hari, 0, ",", ".") . " per day applies."
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
    /* --teal kini dipakai sebagai warna aksen utama (emas), nama variabel dipertahankan
       agar seluruh komponen yang sudah mereferensikannya otomatis ikut berubah */
    --teal:      #d8b878;
    --teal-dim:  rgba(216, 184, 120, .35);
    --gold:      #d8b878;
    --text:      #eef3f4;
    --text-dim:  rgba(238, 243, 244, .68);
    --text-faint:rgba(238, 243, 244, .42);
    --serif:     'Cormorant Garamond', serif;
    --sans:      'Outfit', sans-serif;
  }

  /* ══════════════════ MODE TERANG ══════════════════ */
  html.theme-light {
    --bg:        #f6f2e8;
    --bg-2:      #ffffff;
    --panel:     rgba(255, 255, 255, .70);
    --line:      rgba(150, 110, 45, .30);
    --teal:      #a9782f;
    --teal-dim:  rgba(169, 120, 47, .32);
    --gold:      #a9782f;
    --text:      #221d14;
    --text-dim:  rgba(34, 29, 20, .68);
    --text-faint:rgba(34, 29, 20, .46);
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
  html.lite .btn-lang:hover,
  html.lite .btn-lite:hover,
  html.lite .cta-glow:hover {
    transform: none !important;
    box-shadow: none !important;
  }
  html.lite .hud-top .brand,
  html.lite .btn-musik,
  html.lite .btn-mode,
  html.lite .btn-lang,
  html.lite .btn-lite,
  html.lite .btn-sosial,
  html.lite .hero-content,
  html.lite .lang-dropdown,
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
  .frame, .ticker-bar, .hero-overlay, .slide-hero, .slide-book, .slide-rules,
  .btn-musik, .btn-mode, .btn-lang, .dot span, .meta-chip, .ticker-icon, .ticker-reopen {
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

  /* ══════════════════ TOMBOL MEDIA SOSIAL ══════════════════ */
  .sosial-wrap { position: relative; pointer-events: auto; }
  .btn-sosial {
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
  .btn-sosial:hover { transform: translateY(-1px); border-color: var(--teal); box-shadow: 0 8px 22px rgba(216,184,120,.25); }
  .btn-sosial svg { width: 18px; height: 18px; }
  html.theme-light .btn-sosial { background: rgba(255,255,255,.6); }
  .btn-sosial.open { background: rgba(216,184,120,.16); border-color: var(--teal); }
  html.theme-light .btn-sosial.open { background: rgba(169,120,47,.14); }

  .sosial-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    z-index: 60;
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 8px;
    border-radius: 18px;
    background: rgba(12,17,22,.78);
    border: 1px solid var(--teal-dim);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    box-shadow: 0 14px 34px rgba(0,0,0,.35);
    opacity: 0;
    transform: translateY(-8px) scale(.94);
    pointer-events: none;
    transition: opacity .2s ease, transform .2s ease;
  }
  html.theme-light .sosial-dropdown { background: rgba(255,255,255,.85); }
  .sosial-dropdown.open {
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: auto;
  }
  .sosial-link {
    appearance: none;
    border: none;
    padding: 0;
    cursor: pointer;
    font: inherit;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bg);
    background: var(--teal);
    box-shadow: 0 4px 12px rgba(0,0,0,.25);
    transition: background .18s ease, transform .18s ease, box-shadow .18s ease;
  }
  .sosial-link svg { width: 17px; height: 17px; }
  .sosial-link:hover { background: #f0d9a8; transform: translateY(-1px) scale(1.06); box-shadow: 0 6px 16px rgba(216,184,120,.4); }

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

  /* ══════════════════ TOMBOL BAHASA (GLOBE) ══════════════════ */
  .lang-wrap { position: relative; pointer-events: auto; }
  .btn-lang {
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
  .btn-lang:hover { transform: translateY(-1px); border-color: var(--teal); box-shadow: 0 8px 22px rgba(216,184,120,.25); }
  .btn-lang svg { width: 18px; height: 18px; }
  html.theme-light .btn-lang { background: rgba(255,255,255,.6); }
  .btn-lang.open { background: rgba(216,184,120,.16); border-color: var(--teal); }
  html.theme-light .btn-lang.open { background: rgba(169,120,47,.14); }

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

  .lang-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    z-index: 60;
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 128px;
    padding: 5px;
    border-radius: 12px;
    background: rgba(12,17,22,.78);
    border: 1px solid var(--teal-dim);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    box-shadow: 0 14px 34px rgba(0,0,0,.35);
    opacity: 0;
    transform: translateY(-8px) scale(.94);
    pointer-events: none;
    transition: opacity .2s ease, transform .2s ease;
  }
  html.theme-light .lang-dropdown { background: rgba(255,255,255,.85); }
  .lang-dropdown.open {
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: auto;
  }
  .lang-option {
    appearance: none;
    border: none;
    cursor: pointer;
    font: inherit;
    display: flex;
    align-items: center;
    gap: 6px;
    width: 100%;
    padding: 5px 8px;
    border-radius: 8px;
    background: transparent;
    color: var(--text);
    font-size: .72rem;
    text-align: left;
    white-space: nowrap;
    transition: background .18s ease, color .18s ease;
  }
  .lang-option .flag { font-size: .82rem; line-height: 1; }
  .lang-option:hover { background: rgba(216,184,120,.14); }
  .lang-option.active { background: var(--teal); color: var(--bg); font-weight: 600; }
  html.theme-light .lang-option:hover { background: rgba(169,120,47,.12); }

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

  /* ══════════════════ FOOTER (slide 5) ══════════════════ */
  /* ══════════════════ SLIDE 5 — FOOTER ══════════════════ */
  .slide-footer {
    background: linear-gradient(180deg, #0a0e14 0%, #070a0d 100%);
    display: flex;
    flex-direction: column;
    justify-content: center;
    /* dikunci pas satu layar penuh — bukan lagi height:auto seperti slide lain,
       supaya slide 5 tidak jadi lebih tinggi dari viewport dan memicu scroll
       internal (itu penyebab "garis hitam" scrollbar yang dikeluhkan) */
    height: 100vh;
    height: 100dvh;
    min-height: 100vh;
    min-height: 100dvh;
    overflow: hidden;
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

  /* ── ajakan bertindak di atas footer, mengisi ruang kosong slide terakhir ── */
  .footer-cta {
    position: relative;
    z-index: 1;
    max-width: 720px;
    margin: 0 auto;
    padding: clamp(10px, 2vh, 28px) clamp(20px, 6vw, 80px) clamp(6px, 1vh, 14px);
    text-align: center;
  }
  .footer-cta h2 {
    font-family: var(--serif);
    font-weight: 700;
    font-size: clamp(1.25rem, 2.2vw + 1vh, 2rem);
    line-height: 1.12;
    color: var(--text);
    margin-bottom: 6px;
    text-shadow: 0 2px 24px rgba(0,0,0,.4);
  }
  .footer-cta .lead {
    font-size: .8rem;
    font-weight: 300;
    line-height: 1.5;
    color: var(--text-dim);
    max-width: 52ch;
    margin: 0 auto clamp(8px, 1.4vh, 14px);
  }
  .footer-highlights {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 8px;
    margin-bottom: clamp(8px, 1.4vh, 14px);
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
  @media (max-width: 520px) {
    .footer-cta { padding-top: 14px; padding-bottom: 8px; }
  }

  .site-footer {
    position: relative;
    width: 100%;
    border-top: 1px solid rgba(216,184,120,.18);
    padding: clamp(12px, 2vh, 22px) clamp(20px, 6vw, 80px) clamp(60px, 9vh, 80px);
    overflow: hidden;
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
    .footer-top { grid-template-columns: 1fr 1fr; row-gap: 16px; }
    .footer-brand { grid-column: 1 / -1; }
    .footer-brand p { max-width: 100%; }
  }
  @media (max-width: 520px) {
    .site-footer { padding: clamp(10px, 2vh, 18px) 18px clamp(60px, 10vh, 80px); }
    body.ticker-hidden .site-footer { padding-bottom: 18px; }
    .footer-top { grid-template-columns: 1fr; text-align: center; row-gap: 12px; }
    .footer-brand p { margin: 0 auto; }
    .footer-col ul { align-items: center; }
    .footer-social-links { justify-content: center; }
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
    .btn-musik, .btn-mode, .btn-lang, .btn-lite, .btn-sosial { width: 34px; height: 34px; }
    .btn-musik svg, .btn-mode svg, .btn-lang svg, .btn-lite svg, .btn-sosial svg { width: 15px; height: 15px; }
    .slide-hero .hero-content h1 { font-size: clamp(2.15rem, 9.5vw, 3rem); }
    .hero-content { padding: 28px 18px; border-radius: 22px; }
    .hero-glow span { filter: blur(40px); }
    .slide-book, .slide-rules, .slide-about { padding-left: 14px; padding-right: 14px; padding-top: 76px; }
    .slide-book { padding-bottom: 96px; }
    .slide-rules { padding-bottom: 96px; }
    .slide-about { padding-bottom: 96px; }
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

  @media (max-width: 420px) {
    .book-cover { max-width: 120px; }
    .ticker-cta { display: none; }
    .hud-top .brand { font-size: .7rem; letter-spacing: .1em; }
    .hero-content .tagline { font-size: .82rem; }
  }

  @media (max-width: 360px) {
    .hud-top { padding: 12px 12px; }
    .btn-musik, .btn-mode, .btn-lang, .btn-lite, .btn-sosial { width: 30px; height: 30px; }
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

  /* layar dengan tinggi umum (laptop 13"–14", browser dengan toolbar) — rapikan
     konten slide 5 lebih awal supaya tidak terpotong overflow:hidden */
  @media (max-height: 860px) {
    .footer-cta { padding-top: 10px; padding-bottom: 6px; }
    .footer-cta h2 { font-size: clamp(1.1rem, 4vw, 1.6rem); margin-bottom: 4px; }
    .footer-cta .lead {
      font-size: .76rem;
      line-height: 1.4;
      margin-bottom: 8px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .footer-highlights { display: none; }
    .footer-cta-actions { gap: 8px; }
    .footer-cta-actions .cta-solid,
    .footer-cta-actions .cta-outline { padding-top: 9px; padding-bottom: 9px; }
    .site-footer { padding-top: 8px; padding-bottom: 56px; }
    .footer-top { row-gap: 8px; padding-bottom: 8px; }
    .footer-brand .brand { margin-bottom: 4px; }
    .footer-brand p {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .footer-col h4 { margin-bottom: 6px; }
    .footer-col ul { gap: 4px; }
    .footer-bottom { padding-top: 6px; }
  }
  @media (max-height: 520px) {
    .footer-cta .lead, .footer-brand p { display: none; }
    .footer-top { grid-template-columns: 1fr 1fr; }
    .footer-social { grid-column: 1 / -1; }
  }
</style>
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
    <audio id="audioLatar" loop autoplay muted preload="auto">
      <source src="<?= htmlspecialchars($musik_file) ?>">
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
    <div class="lang-wrap">
      <button type="button" class="btn-lang" id="btnLang" aria-label="Ganti bahasa / Change language" aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="9"/>
          <path d="M3 12h18"/>
          <path d="M12 3c2.5 2.6 3.8 5.7 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.7-3.8-9s1.3-6.4 3.8-9Z"/>
        </svg>
      </button>
      <div class="lang-dropdown" id="langDropdown">
        <button type="button" class="lang-option" data-lang="id">
           Bahasa Indonesia
        </button>
        <button type="button" class="lang-option" data-lang="en">
           English
        </button>
      </div>
    </div>
    <div class="sosial-wrap">
      <button type="button" class="btn-sosial" id="btnSosial" aria-label="Media sosial" data-id-aria="Media sosial" data-en-aria="Social media" aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
          <line x1="8.6" y1="10.6" x2="15.4" y2="6.4"/><line x1="8.6" y1="13.4" x2="15.4" y2="17.6"/>
        </svg>
      </button>
      <div class="sosial-dropdown" id="sosialDropdown">
        <a href="#" target="_blank" rel="noopener" class="sosial-link" title="Instagram">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="5"/>
            <circle cx="12" cy="12" r="4"/>
            <circle cx="17.3" cy="6.7" r="0.6" fill="currentColor" stroke="none"/>
          </svg>
        </a>
        <a href="#" target="_blank" rel="noopener" class="sosial-link" title="TikTok">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.5 2c.4 2.2 1.8 3.9 4 4.4v3.1c-1.4 0-2.8-.4-4-1.2v6.4c0 3.5-2.8 6.3-6.3 6.3S3.9 18.2 3.9 14.7s2.8-6.3 6.3-6.3c.3 0 .6 0 .9.1v3.2c-.3-.1-.6-.2-.9-.2-1.8 0-3.2 1.4-3.2 3.2s1.4 3.2 3.2 3.2 3.3-1.4 3.3-3.2V2h3z"/></svg>
        </a>
        <a href="#" target="_blank" rel="noopener" class="sosial-link" title="Facebook">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M13.6 21v-7.6h2.6l.4-3h-3V8.4c0-.9.2-1.5 1.5-1.5H16.7V4.2c-.3 0-1.2-.2-2.3-.2-2.3 0-3.9 1.4-3.9 4v2.4H7.9v3h2.6V21h3.1z"/></svg>
        </a>
        <button type="button" class="sosial-link" id="btnShare" aria-label="Bagikan halaman ini" title="Bagikan">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3v12"/><path d="M7.5 7.5 12 3l4.5 4.5"/><path d="M5 13v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6"/>
          </svg>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="share-toast" id="shareToast">Link disalin ke clipboard!</div>

<nav class="dot-nav">
  <button class="dot active" data-target="slide-1" aria-label="Beranda"><span></span></button>
  <button class="dot" data-target="slide-2" aria-label="Buku Terbaru"><span></span></button>
  <button class="dot" data-target="slide-3" aria-label="Tata Tertib"><span></span></button>
  <button class="dot" data-target="slide-4" aria-label="Tentang Katalog"><span></span></button>
  <button class="dot" data-target="slide-5" aria-label="Footer"><span></span></button>
</nav>

<main class="slides">

  <!-- ═══════════ SLIDE 1 — HERO ═══════════ -->
  <section id="slide-1" class="slide slide-hero">
    <!-- Gambar latar diatur admin lewat menu Kelola Banner ("Jadikan Latar Beranda") -->
    <div class="hero-bg" id="heroBg" aria-hidden="true">
      <?php foreach ($hero_images as $i => $hb): ?>
        <div class="hero-bg-slide<?= $i === 0 ? " active" : "" ?>" style="background-image:url('<?= htmlspecialchars($hb["gambar"]) ?>')"></div>
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

          <span class="cover-icon icon-lampu" aria-hidden="true" title="Lampu">💡</span>
          <span class="cover-icon icon-pantai" aria-hidden="true" title="Pantai">🏖️</span>
          <span class="cover-icon icon-gunung" aria-hidden="true" title="Gunung">🏔️</span>
          <span class="cover-icon icon-kaset" aria-hidden="true" title="Kaset">📼</span>
          <span class="cover-icon icon-pensil" aria-hidden="true" title="Pensil">✏️</span>

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

  <!-- ═══════════ SLIDE 3 — TATA TERTIB PERPUSTAKAAN ═══════════ -->
  <section id="slide-3" class="slide slide-rules">
    <div class="frame reveal">
      <span class="corner corner-tl"></span><span class="corner corner-tr"></span>
      <span class="corner corner-bl"></span><span class="corner corner-br"></span>
      <span class="frame-label" data-id="Tata Tertib" data-en="Library Rules">Tata Tertib</span>

      <h2 data-id="Aturan Perpustakaan" data-en="Library Regulations">Aturan Perpustakaan</h2>
      <p class="lead" data-id="Mohon perhatikan ketentuan berikut sebelum meminjam koleksi di AKSA NOVA." data-en="Please review the following terms before borrowing from the AKSA NOVA collection.">Mohon perhatikan ketentuan berikut sebelum meminjam koleksi di AKSA NOVA.</p>

      <ul class="rules-list reveal-stagger">
        <li class="reveal-item">
          <span class="num">01</span>
          <div>
            <h3 data-id="Jam Operasional" data-en="Operating Hours">Jam Operasional</h3>
            <p data-id="Perpustakaan melayani peminjaman setiap Senin–Jumat, pukul 07.00–15.00 WIB." data-en="The library is open for borrowing Monday–Friday, 07:00–15:00 (WIB).">Perpustakaan melayani peminjaman setiap Senin–Jumat, pukul 07.00–15.00 WIB.</p>
          </div>
        </li>
        <li class="reveal-item">
          <span class="num">02</span>
          <div>
            <h3 data-id="Kartu Anggota" data-en="Membership Card">Kartu Anggota</h3>
            <p data-id="Peminjaman hanya dapat dilakukan oleh anggota terdaftar menggunakan akun masing-masing." data-en="Borrowing may only be done by registered members using their own account.">Peminjaman hanya dapat dilakukan oleh anggota terdaftar menggunakan akun masing-masing.</p>
          </div>
        </li>
        <li class="reveal-item">
          <span class="num">03</span>
          <div>
            <h3 data-id="Batas Peminjaman" data-en="Borrowing Limit">Batas Peminjaman</h3>
            <p data-id="Setiap buku dipinjamkan maksimal 7 hari dan dapat diperpanjang jika tidak ada antrean." data-en="Each book may be borrowed for a maximum of 7 days and can be extended if there is no waiting list.">Setiap buku dipinjamkan maksimal 7 hari dan dapat diperpanjang jika tidak ada antrean.</p>
          </div>
        </li>
        <li class="reveal-item">
          <span class="num">04</span>
          <div>
            <h3 data-id="Keterlambatan" data-en="Late Returns">Keterlambatan</h3>
            <p data-id="<?= htmlspecialchars($denda_text) ?>" data-en="<?= htmlspecialchars($denda_text_en) ?>"><?= htmlspecialchars($denda_text) ?></p>
          </div>
        </li>
        <li class="reveal-item">
          <span class="num">05</span>
          <div>
            <h3 data-id="Menjaga Koleksi" data-en="Caring for the Collection">Menjaga Koleksi</h3>
            <p data-id="Buku yang dipinjam wajib dijaga kebersihan dan kelengkapannya; kerusakan atau kehilangan menjadi tanggung jawab peminjam." data-en="Borrowed books must be kept clean and complete; any damage or loss is the borrower's responsibility.">Buku yang dipinjam wajib dijaga kebersihan dan kelengkapannya; kerusakan atau kehilangan menjadi tanggung jawab peminjam.</p>
          </div>
        </li>
        <li class="reveal-item">
          <span class="num">06</span>
          <div>
            <h3 data-id="Ketertiban Ruangan" data-en="Room Etiquette">Ketertiban Ruangan</h3>
            <p data-id="Jaga ketenangan di area baca dan kembalikan buku ke rak/petugas sesuai tempatnya." data-en="Keep the reading area quiet and return books to the shelf or staff as appropriate.">Jaga ketenangan di area baca dan kembalikan buku ke rak/petugas sesuai tempatnya.</p>
          </div>
        </li>
      </ul>
    </div>
  </section>

  <section id="slide-4" class="slide slide-about">
    <div class="about-wrap reveal">
      <div class="about-head">
        <span class="eyebrow" data-id="Tentang Katalog" data-en="About the Catalog">Tentang Katalog</span>
        <h2 data-id="Tentang Katalog Perpustakaan" data-en="About the Library Catalog">Tentang Katalog Perpustakaan</h2>
        <p class="lead" data-id="AKSA NOVA adalah katalog digital untuk membantu kamu mengecek koleksi buku sebelum datang langsung ke perpustakaan." data-en="AKSA NOVA is a digital catalog that helps you check the book collection before visiting the library in person.">AKSA NOVA adalah katalog digital untuk membantu kamu mengecek koleksi buku sebelum datang langsung ke perpustakaan.</p>
      </div>

      <div class="about-grid reveal-stagger">
        <div class="about-card reveal-item">
          <span class="about-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
          </span>
          <h3 data-id="Cek Ketersediaan Buku" data-en="Check Book Availability">Cek Ketersediaan Buku</h3>
          <p data-id="Website ini hanya menampilkan katalog dan status ketersediaan buku secara online, bukan tempat peminjaman langsung." data-en="This website only shows the catalog and online availability status of books; it is not a place to borrow books directly.">Website ini hanya menampilkan katalog dan status ketersediaan buku secara online, bukan tempat peminjaman langsung.</p>
        </div>

        <div class="about-card reveal-item">
          <span class="about-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="5" width="19" height="14" rx="2.5"/><circle cx="8.5" cy="12" r="2"/><path d="M13.5 10.5h5M13.5 13.5h3.5"/></svg>
          </span>
          <h3 data-id="Wajib Punya Kartu Anggota" data-en="Membership Card Required">Wajib Punya Kartu Anggota</h3>
          <p data-id="Peminjaman buku hanya bisa dilakukan oleh anggota yang sudah memiliki kartu anggota perpustakaan yang aktif." data-en="Books can only be borrowed by members who already hold an active library membership card.">Peminjaman buku hanya bisa dilakukan oleh anggota yang sudah memiliki kartu anggota perpustakaan yang aktif.</p>
        </div>

        <div class="about-card reveal-item">
          <span class="about-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M4 21V9l8-5 8 5v12"/><path d="M9 21v-7h6v7"/></svg>
          </span>
          <h3 data-id="Pinjam Langsung di Perpustakaan" data-en="Borrow In Person at the Library">Pinjam Langsung di Perpustakaan</h3>
          <p data-id="Setelah memastikan buku tersedia di katalog, proses peminjaman tetap harus dilakukan dengan datang langsung ke perpustakaan." data-en="After confirming a book is available in the catalog, the borrowing process must still be done by visiting the library in person.">Setelah memastikan buku tersedia di katalog, proses peminjaman tetap harus dilakukan dengan datang langsung ke perpustakaan.</p>
        </div>

        <div class="about-card reveal-item">
          <span class="about-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 21v-8a3 3 0 0 0-6 0v8"/><circle cx="12" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
          </span>
          <h3 data-id="Belum Punya Kartu Anggota?" data-en="Don't Have a Membership Card Yet?">Belum Punya Kartu Anggota?</h3>
          <p data-id="Daftarkan diri terlebih dahulu di bagian administrasi perpustakaan dengan membawa identitas diri yang masih berlaku." data-en="Register first at the library administration desk, bringing a valid form of identification.">Daftarkan diri terlebih dahulu di bagian administrasi perpustakaan dengan membawa identitas diri yang masih berlaku.</p>
        </div>

        <div class="about-card reveal-item">
          <span class="about-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9a2 2 0 0 1 2 2v16l-6.5-4L4 21V5a2 2 0 0 1 2-2Z"/></svg>
          </span>
          <h3 data-id="Gunakan Fitur Wishlist" data-en="Use the Wishlist Feature">Gunakan Fitur Wishlist</h3>
          <p data-id="Simpan buku yang ingin dipinjam ke Wishlist agar lebih mudah diingat dan dicari saat berkunjung ke perpustakaan." data-en="Save books you want to borrow to your Wishlist so they're easier to remember and find when you visit the library.">Simpan buku yang ingin dipinjam ke Wishlist agar lebih mudah diingat dan dicari saat berkunjung ke perpustakaan.</p>
        </div>

        <div class="about-card reveal-item">
          <span class="about-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.6-6.35"/><path d="M21 3v5h-5"/></svg>
          </span>
          <h3 data-id="Info Selalu Diperbarui" data-en="Always Up-to-Date Info">Info Selalu Diperbarui</h3>
          <p data-id="Data koleksi dan status buku dikelola oleh admin perpustakaan dan diperbarui secara berkala agar tetap akurat." data-en="Collection data and book status are managed by library admins and updated regularly to stay accurate.">Data koleksi dan status buku dikelola oleh admin perpustakaan dan diperbarui secara berkala agar tetap akurat.</p>
        </div>
      </div>
    </div>
  </section>

  <section id="slide-5" class="slide slide-footer">

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

  <br>
  <footer class="site-footer reveal">
    <div class="footer-top reveal-stagger">
      <div class="footer-brand reveal-item">
        <div class="brand">AKSA <span>NOVA</span></div>
        <p data-id="Katalog perpustakaan digital untuk mengecek koleksi dan ketersediaan buku sebelum berkunjung langsung." data-en="A digital library catalog to check the collection and book availability before visiting in person.">Katalog perpustakaan digital untuk mengecek koleksi dan ketersediaan buku sebelum berkunjung langsung.</p>
      </div>

      <div class="footer-col reveal-item">
        <h4 data-id="Navigasi" data-en="Navigation">Navigasi</h4>
        <ul>
          <li><a href="#slide-1" data-id="Beranda" data-en="Home">Beranda</a></li>
          <li><a href="#slide-2" data-id="Buku Terbaru" data-en="Latest Books">Buku Terbaru</a></li>
          <li><a href="#slide-3" data-id="Tata Tertib" data-en="Library Rules">Tata Tertib</a></li>
          <li><a href="#slide-4" data-id="Tentang Katalog" data-en="About the Catalog">Tentang Katalog</a></li>
        </ul>
      </div>

      <div class="footer-col reveal-item">
        <h4 data-id="Akun" data-en="Account">Akun</h4>
        <ul>
          <li><a href="sign_in.php" data-id="Masuk Anggota" data-en="Member Sign In">Masuk Anggota</a></li>
          <li><a href="sign_in.php" data-id="Wishlist Saya" data-en="My Wishlist">Wishlist Saya</a></li>
        </ul>
      </div>

      <div class="footer-col footer-social reveal-item">
        <h4 data-id="Ikuti Kami" data-en="Follow Us">Ikuti Kami</h4>
        <div class="footer-social-links">
          <a href="#" target="_blank" rel="noopener" title="TikTok">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.5 2c.4 2.2 1.8 3.9 4 4.4v3.1c-1.4 0-2.8-.4-4-1.2v6.4c0 3.5-2.8 6.3-6.3 6.3S3.9 18.2 3.9 14.7s2.8-6.3 6.3-6.3c.3 0 .6 0 .9.1v3.2c-.3-.1-.6-.2-.9-.2-1.8 0-3.2 1.4-3.2 3.2s1.4 3.2 3.2 3.2 3.3-1.4 3.3-3.2V2h3z"/></svg>
          </a>
          <a href="#" target="_blank" rel="noopener" title="Instagram">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="18" height="18" rx="5"/>
              <circle cx="12" cy="12" r="4"/>
              <circle cx="17.3" cy="6.7" r="0.6" fill="currentColor" stroke="none"/>
            </svg>
          </a>
          <a href="#" target="_blank" rel="noopener" title="Facebook">
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
  <button type="button" class="ticker-close" id="tickerClose" aria-label="Tutup notifikasi">✕</button>
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
      try { localStorage.setItem(STORAGE_KEY, isLight ? 'light' : 'dark'); } catch (e) {}
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

  // Ganti bahasa (Indonesia / English) — pilihan pengunjung disimpan permanen di perangkat ini
  (function () {
    const btn        = document.getElementById('btnLang');
    const dropdown   = document.getElementById('langDropdown');
    const STORAGE_KEY = 'aksanova_lang';

    function terapkanBahasa(lang) {
      document.querySelectorAll('[data-id][data-en]').forEach((el) => {
        const teks = lang === 'en' ? el.getAttribute('data-en') : el.getAttribute('data-id');
        if (teks !== null) el.textContent = teks;
      });
      document.querySelectorAll('[data-id-title][data-en-title]').forEach((el) => {
        const teks = lang === 'en' ? el.getAttribute('data-en-title') : el.getAttribute('data-id-title');
        if (teks !== null) el.setAttribute('title', teks);
      });
      document.querySelectorAll('[data-id-aria][data-en-aria]').forEach((el) => {
        const teks = lang === 'en' ? el.getAttribute('data-en-aria') : el.getAttribute('data-id-aria');
        if (teks !== null) el.setAttribute('aria-label', teks);
      });
      document.documentElement.setAttribute('lang', lang);
      document.querySelectorAll('.lang-option').forEach((opt) => {
        opt.classList.toggle('active', opt.dataset.lang === lang);
      });
    }

    function bahasaTersimpan() {
      try { return localStorage.getItem(STORAGE_KEY) || 'id'; } catch (e) { return 'id'; }
    }

    terapkanBahasa(bahasaTersimpan());

    if (!btn || !dropdown) return;

    function tutup() {
      dropdown.classList.remove('open');
      btn.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
    }

    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const buka = dropdown.classList.toggle('open');
      btn.classList.toggle('open', buka);
      btn.setAttribute('aria-expanded', buka ? 'true' : 'false');
    });

    document.addEventListener('click', (e) => {
      if (!dropdown.contains(e.target) && e.target !== btn) tutup();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') tutup();
    });

    dropdown.querySelectorAll('.lang-option').forEach((opt) => {
      opt.addEventListener('click', () => {
        const lang = opt.dataset.lang;
        try { localStorage.setItem(STORAGE_KEY, lang); } catch (e) {}
        terapkanBahasa(lang);
        tutup();
      });
    });
  })();

  // Dropdown media sosial di navbar atas
  (function () {
    const btn      = document.getElementById('btnSosial');
    const dropdown = document.getElementById('sosialDropdown');
    if (!btn || !dropdown) return;

    function tutup() {
      dropdown.classList.remove('open');
      btn.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
    }

    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const buka = dropdown.classList.toggle('open');
      btn.classList.toggle('open', buka);
      btn.setAttribute('aria-expanded', buka ? 'true' : 'false');
    });

    document.addEventListener('click', (e) => {
      if (!dropdown.contains(e.target) && e.target !== btn) tutup();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') tutup();
    });
  })();

  // Tombol Bagikan — pakai Web Share API bawaan perangkat jika tersedia, kalau tidak salin link ke clipboard
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
      e.stopPropagation();
      const dropdown = document.getElementById('sosialDropdown');
      const btnSosial = document.getElementById('btnSosial');
      if (dropdown) dropdown.classList.remove('open');
      if (btnSosial) { btnSosial.classList.remove('open'); btnSosial.setAttribute('aria-expanded', 'false'); }

      const dataBagikan = {
        title: document.title,
        text: 'Kunjungi perpustakaan digital AKSA NOVA',
        url: window.location.href
      };
      if (navigator.share) {
        try { await navigator.share(dataBagikan); } catch (e) { /* dibatalkan pengunjung, abaikan */ }
        return;
      }
      try {
        await navigator.clipboard.writeText(window.location.href);
        tampilkanToast('Link disalin ke clipboard!');
      } catch (e) {
        tampilkanToast('Gagal menyalin link.');
      }
    });
  })();

  // Musik latar — otomatis diputar saat halaman dibuka, sumbernya diatur admin
  (function () {
    const audio  = document.getElementById('audioLatar');
    const btn    = document.getElementById('btnMusik');
    if (!audio || !btn) return;

    audio.volume = 0.55;

    let userPaused        = false; // true kalau pengguna SENGAJA menekan tombol musik untuk mematikan
    let pausedByHidden     = false; // true kalau musik dihentikan otomatis karena tab/halaman sedang tidak aktif
    let autoplaySucceeded  = false; // true kalau autoplay awal sudah berhasil (jaring pengaman tidak diperlukan lagi)

    function setPlaying(isPlaying) {
      btn.classList.toggle('playing', isPlaying);
      btn.setAttribute('aria-pressed', isPlaying ? 'true' : 'false');
    }

    function removeAutoplayFallback() {
      document.removeEventListener('click', autoplayFallback);
      document.removeEventListener('touchstart', autoplayFallback);
      document.removeEventListener('keydown', autoplayFallback);
      document.removeEventListener('scroll', autoplayFallback);
    }

    // Browser hanya mengizinkan audio autoplay bersuara jika dimulai dalam kondisi "muted".
    // Trik: putar dalam keadaan muted (selalu diizinkan tanpa interaksi), lalu langsung
    // nyalakan suaranya (unmute) setelah berhasil — hasilnya musik terdengar otomatis
    // begitu halaman dibuka, tanpa perlu pengunjung mengklik apa pun dulu.
    function play() {
      audio.play().then(() => {
        audio.muted = false;
        autoplaySucceeded = true;
        removeAutoplayFallback(); // autoplay sudah jalan, jaring pengaman tidak diperlukan lagi
        setPlaying(true);
      }).catch(() => setPlaying(false));
    }

    function pause() {
      audio.pause();
      setPlaying(false);
    }

    btn.addEventListener('click', () => {
      if (audio.paused) {
        userPaused = false;
        pausedByHidden = false;
        play();
      } else {
        userPaused = true; // tandai bahwa pengguna sendiri yang mematikan musiknya
        pause();
      }
    });

    // Putar otomatis begitu halaman dimuat
    play();

    // Jaring pengaman: pada browser yang tetap memblokir autoplay meski sudah di-mute,
    // musik akan langsung menyala begitu pengunjung berinteraksi apa pun dengan halaman.
    // Fungsi ini HANYA boleh menyalakan musik selama autoplay awal belum berhasil DAN
    // pengguna belum pernah mematikannya sendiri — supaya menekan tombol lain setelah
    // musik dimatikan manual tidak menyalakannya kembali.
    function autoplayFallback() {
      if (!autoplaySucceeded && !userPaused && (audio.paused || audio.muted)) {
        audio.muted = false;
        play();
      }
      removeAutoplayFallback();
    }
    document.addEventListener('click', autoplayFallback, { once: true, passive: true });
    document.addEventListener('touchstart', autoplayFallback, { once: true, passive: true });
    document.addEventListener('keydown', autoplayFallback, { once: true });
    document.addEventListener('scroll', autoplayFallback, { once: true, passive: true });

    // Musik otomatis berhenti saat pengunjung pindah tab / minimize jendela / keluar dari web ini,
    // dan otomatis lanjut lagi saat kembali ke tab ini — kecuali pengguna sendiri yang
    // mematikannya lewat tombol musik (userPaused tetap dihormati).
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        if (!audio.paused) {
          pausedByHidden = true;
          audio.pause();
          setPlaying(false);
        }
      } else if (pausedByHidden && !userPaused) {
        pausedByHidden = false;
        play();
      }
    });

    // Pastikan musik benar-benar berhenti saat halaman ditinggalkan (pindah halaman/menutup tab)
    window.addEventListener('pagehide', () => {
      audio.pause();
    });
  })();

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

  // Animasi sederhana "buka buku" saat cover buku di slide 2 diklik —
  // ikon-ikon (lampu, pantai, gunung, kaset, pensil) muncul satu per satu dengan urutan acak setiap klik
  (function () {
    const wrap  = document.getElementById('bookCoverWrap');
    const cover = document.getElementById('bookCover');
    const ikonList = Array.from(wrap ? wrap.querySelectorAll('.cover-icon') : []);
    if (!wrap || !cover) return;

    let sedangBuka = false;

    const JEDA_AWAL   = 0.15; // detik sebelum ikon pertama mulai muncul
    const JEDA_ANTARA = 0.22; // jeda antar kemunculan tiap ikon berikutnya

    function acakUrutanIkon() {
      // Fisher–Yates shuffle supaya urutan kemunculan ikon berbeda setiap kali diklik
      const acak = [...ikonList];
      for (let i = acak.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [acak[i], acak[j]] = [acak[j], acak[i]];
      }
      acak.forEach((el, i) => {
        el.style.animationDelay = (JEDA_AWAL + i * JEDA_ANTARA) + 's';
      });
    }

    function mainkanAnimasi() {
      if (sedangBuka) return;
      sedangBuka = true;
      acakUrutanIkon();
      wrap.classList.add('opening');
    }

    cover.addEventListener('animationend', () => {
      wrap.classList.remove('opening');
      sedangBuka = false;
    });

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
</script>

</body>
</html>