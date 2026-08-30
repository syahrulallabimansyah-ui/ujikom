<?php
// index.php — Landing / welcome page gaya "slide scroll" (referensi: Wuthering Waves)
// Slide 1: Hero live wallpaper (video)   Slide 2: Buku terbaru   Slide 3: Tata tertib perpustakaan
require_once "db.php";

$page_title = "Selamat Datang – AKSA NOVA";

/* ───────────────────────── Ambil data buku terbaru ───────────────────────── */
$buku_list = [];
$res = @mysqli_query($conn, "SELECT id, judul, penulis, genre, sinopsis, gambar FROM buku ORDER BY created_at DESC LIMIT 5");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $buku_list[] = $row;
    }
}
$buku_utama = $buku_list[0] ?? null;

function format_genre($genre) {
    if (!$genre) return "";
    $parts = explode(":", $genre);
    $label = end($parts);
    $label = str_replace("_", " ", $label);
    return ucwords($label);
}

function ringkas($teks, $panjang = 220) {
    $teks = trim(strip_tags($teks ?? ""));
    if ($teks === "") return "Sinopsis belum tersedia untuk buku ini.";
    if (mb_strlen($teks) <= $panjang) return $teks;
    return mb_substr($teks, 0, $panjang) . "…";
}

/* ───────────────────────── Poster fallback untuk hero (dari banner aktif) ───────────────────────── */
$hero_poster = "";
$banner_res = @mysqli_query($conn, "SELECT gambar FROM banner WHERE aktif = 1 ORDER BY urutan ASC LIMIT 1");
if ($banner_res && ($b = mysqli_fetch_assoc($banner_res))) {
    $hero_poster = $b["gambar"];
}

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

  html, body {
    transition: background-color .35s ease, color .35s ease;
  }
  .frame, .ticker-bar, .hero-overlay, .slide-hero, .slide-book, .slide-rules,
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
    overflow-y: scroll;
    scroll-snap-type: y mandatory;
    scrollbar-width: none;
  }
  .slides::-webkit-scrollbar { display: none; }

  .slide {
    position: relative;
    min-height: 100vh;
    min-height: 100dvh;
    height: auto;
    width: 100%;
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
    color: var(--text);
    text-shadow: 0 2px 12px rgba(0,0,0,.6);
    pointer-events: auto;
  }
  .hud-top .brand span { color: var(--teal); }

  .btn-masuk-top {
    pointer-events: auto;
    font-family: var(--sans);
    font-size: .72rem;
    font-weight: 500;
    letter-spacing: .16em;
    text-transform: uppercase;
    text-decoration: none;
    color: var(--bg);
    background: var(--teal);
    padding: 10px 26px;
    border-radius: 999px;
    box-shadow: 0 0 0 1px var(--teal-dim), 0 8px 24px rgba(216,184,120,.25);
    transition: transform .18s ease, box-shadow .18s ease;
  }
  .btn-masuk-top:hover {
    transform: translateY(-1px);
    box-shadow: 0 0 0 1px var(--teal), 0 10px 30px rgba(216,184,120,.4);
  }

  .hud-actions { display: flex; align-items: center; gap: 12px; pointer-events: none; }

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
    left: 30px;
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

  /* ══════════════════ SLIDE 1 — HERO / LIVE WALLPAPER ══════════════════ */
  .slide-hero {
    background: radial-gradient(circle at 30% 20%, #1f1912 0%, var(--bg) 60%);
  }
  .bg-video, .bg-poster {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    z-index: 0;
  }
  /* Ganti sumber wallpaper video di tag <source> pada .bg-video di bawah */
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
    padding: 44px clamp(20px, 6vw, 72px);
    max-width: min(900px, 92vw);
    animation: riseIn 1s cubic-bezier(.22,1,.36,1) both;
    /* panel lembut di belakang teks agar tidak "menyatu" dengan gambar/video apa pun di baliknya */
    background: radial-gradient(ellipse 100% 100% at 50% 50%, rgba(6,8,11,.55) 0%, rgba(6,8,11,.28) 55%, transparent 82%);
    border-radius: 32px;
    backdrop-filter: blur(6px) saturate(1.05);
    -webkit-backdrop-filter: blur(6px) saturate(1.05);
  }
  .hero-content h1 {
    font-family: var(--serif);
    font-weight: 700;
    font-size: clamp(2.6rem, 9vw, 6.4rem);
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
  @keyframes bob { 0%,100% { transform: translateY(0);} 50% { transform: translateY(6px);} }
  @keyframes riseIn { from { opacity:0; transform:translateY(28px);} to { opacity:1; transform:translateY(0);} }

  /* ══════════════════ SLIDE 2 — BUKU TERBARU ══════════════════ */
  .slide-book {
    position: relative;
    background:
      radial-gradient(ellipse 60% 50% at 78% 18%, rgba(216,184,120,.10), transparent 60%),
      radial-gradient(ellipse 50% 40% at 10% 90%, rgba(216,184,120,.05), transparent 60%),
      linear-gradient(165deg, #101823 0%, #0a0e14 55%, #090c10 100%);
    padding: 90px clamp(20px, 6vw, 80px) 120px;
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
    padding: 52px clamp(24px, 4.5vw, 68px);
    display: flex;
    flex-direction: column;
    gap: 46px;
  }
  /* varian bingkai beraksen emas khusus untuk kartu buku, mengikuti palet referensi */
  .frame-gold { border-color: rgba(216,184,120,.32); }
  .frame-gold .frame-label { color: var(--gold); }
  .frame-gold .corner::before,
  .frame-gold .corner::after { background: var(--gold); }

  .book-layout {
    display: grid;
    grid-template-columns: 1.08fr .92fr;
    gap: 60px;
    align-items: center;
  }
  .book-info .author {
    font-size: .86rem;
    color: var(--text-dim);
    margin-bottom: 20px;
    font-weight: 300;
    letter-spacing: .02em;
  }
  .book-info h2 {
    font-family: var(--serif);
    font-weight: 700;
    font-size: clamp(2.1rem, 3.8vw, 3.1rem);
    line-height: 1.06;
    margin-bottom: 14px;
    color: var(--text);
    text-shadow: 0 2px 24px rgba(0,0,0,.4);
  }
  .book-info .author strong { color: var(--gold); font-weight: 500; }

  /* baris meta ala "rating / durasi" pada referensi, memakai data yang tersedia */
  .meta-row { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
  .meta-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: .78rem;
    font-weight: 400;
    color: var(--text-dim);
    padding: 8px 16px 8px 12px;
    border-radius: 999px;
    background: rgba(255,255,255,.045);
    border: 1px solid rgba(255,255,255,.09);
    letter-spacing: .01em;
  }
  .meta-chip svg { width: 15px; height: 15px; flex: 0 0 auto; color: var(--gold); }
  .meta-chip--status svg { color: #7fe0a8; }

  .book-info .sinopsis {
    font-size: .95rem;
    line-height: 1.8;
    color: var(--text-dim);
    font-weight: 300;
    margin-bottom: 34px;
    max-width: 54ch;
  }

  .book-actions { display: flex; flex-wrap: wrap; gap: 16px; }
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
    padding: 15px 34px;
    color: #241a0a;
    background: linear-gradient(135deg, #ecd19f, var(--gold) 55%, #c89f5e);
    box-shadow: 0 14px 32px -6px rgba(216,184,120,.55);
  }
  .cta-solid:hover { transform: translateY(-2px); box-shadow: 0 18px 40px -6px rgba(216,184,120,.7); }
  .cta-outline {
    padding: 14px 30px;
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
    min-height: 320px;
  }
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
    max-width: 310px;
    margin: 0 auto;
    aspect-ratio: 3 / 4.35;
    border-radius: 14px;
    transform: rotate(-2deg);
    transition: transform .5s cubic-bezier(.22,1,.36,1);
  }
  .book-cover-wrap:hover .book-cover { transform: rotate(0deg) translateY(-8px); }
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
      14px 18px 36px 2px rgba(0,0,0,.5),
      0 30px 70px rgba(0,0,0,.5);
    transition: box-shadow .5s cubic-bezier(.22,1,.36,1);
    z-index: -1;
  }
  /* shadow membesar & melembut ketika kursor mengarah ke cover, memberi kesan buku terangkat */
  .book-cover-wrap:hover .book-cover::before {
    box-shadow:
      3px 4px 0 0 #eee6d3,
      6px 8px 0 0 #ddd3ba,
      9px 12px 0 0 #cbc0a3,
      18px 26px 54px 6px rgba(0,0,0,.55),
      0 46px 100px rgba(0,0,0,.6),
      0 0 70px rgba(216,184,120,.3);
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
    /* kilau tipis di permukaan cover agar terasa glossy seperti hardcover */
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: linear-gradient(120deg, rgba(255,255,255,.28) 0%, rgba(255,255,255,0) 26%, rgba(255,255,255,0) 72%, rgba(0,0,0,.18) 100%);
    pointer-events: none;
  }

  /* ── carousel "buku serupa" — kartu lebih besar & jenuh warnanya ── */
  .similar-section { border-top: 1px solid rgba(255,255,255,.09); padding-top: 32px; }
  .similar-label {
    font-size: .7rem;
    letter-spacing: .3em;
    text-transform: uppercase;
    color: var(--text-faint);
    font-weight: 500;
    margin-bottom: 20px;
  }
  .book-thumbs {
    display: flex;
    gap: 22px;
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
    width: 118px;
    display: flex;
    flex-direction: column;
    gap: 10px;
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
    padding: 90px 20px 120px;
    overflow: hidden;
  }
  .slide-rules .frame {
    width: 100%;
    max-width: 820px;
    padding: 56px clamp(24px, 5vw, 68px);
  }
  .slide-rules h2 {
    font-family: var(--serif);
    font-weight: 700;
    font-size: clamp(1.9rem, 3.4vw, 2.5rem);
    margin-bottom: 8px;
  }
  .slide-rules .lead {
    font-size: .86rem;
    color: var(--text-dim);
    font-weight: 300;
    margin-bottom: 34px;
    max-width: 56ch;
  }
  .rules-list { list-style: none; display: flex; flex-direction: column; }
  .rules-list li {
    display: grid;
    grid-template-columns: 44px 1fr;
    gap: 18px;
    padding: 18px 0;
    border-top: 1px solid rgba(255,255,255,.08);
  }
  .rules-list li:last-child { border-bottom: 1px solid rgba(255,255,255,.08); }
  .rules-list .num {
    font-family: var(--serif);
    font-weight: 700;
    font-size: 1.3rem;
    color: var(--teal);
  }
  .rules-list h3 {
    font-size: .92rem;
    font-weight: 500;
    letter-spacing: .01em;
    margin-bottom: 4px;
    color: var(--text);
  }
  .rules-list p {
    font-size: .8rem;
    font-weight: 300;
    color: var(--text-dim);
    line-height: 1.6;
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
    border-top: 1px solid var(--teal-dim);
    box-shadow: 0 -18px 40px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.03);
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
  html.theme-light .eyebrow,
  html.theme-light .hero-content .tagline { text-shadow: 0 2px 12px rgba(0,0,0,.4); color: rgba(255,255,255,.82); }
  html.theme-light .hero-glow span { opacity: .16; filter: blur(70px); }
  html.theme-light .slide-book {
    background:
      radial-gradient(ellipse 60% 50% at 78% 18%, rgba(169,120,47,.10), transparent 60%),
      radial-gradient(ellipse 50% 40% at 10% 90%, rgba(169,120,47,.07), transparent 60%),
      linear-gradient(165deg, #fdfaf2 0%, #f5efdf 55%, #f6f2e8 100%);
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
    .book-layout { gap: 44px; }
  }

  @media (max-width: 900px) {
    .book-layout { grid-template-columns: 1fr; gap: 32px; }
    .book-cover { max-width: 240px; }
    .book-info { order: 2; text-align: center; }
    .book-cover-wrap { order: 1; }
    .meta-row, .book-actions { justify-content: center; }
    .book-info .sinopsis { margin-left: auto; margin-right: auto; }
    .slide-book .frame { padding-top: 40px; padding-bottom: 40px; gap: 28px; }
    .hero-content { padding: 36px clamp(16px, 6vw, 48px); }
  }

  @media (max-width: 680px) {
    .dot-nav { display: none; }
    .hud-top { padding: 14px 16px; }
    .hud-top .brand { font-size: .78rem; letter-spacing: .16em; }
    .hud-actions { gap: 8px; }
    .btn-masuk-top { padding: 8px 16px; font-size: .64rem; }
    .btn-musik, .btn-mode { width: 34px; height: 34px; }
    .btn-musik svg, .btn-mode svg { width: 15px; height: 15px; }
    .slide-hero .hero-content h1 { font-size: clamp(2.4rem, 12vw, 3.4rem); }
    .hero-content { padding: 28px 18px; border-radius: 22px; }
    .hero-glow span { filter: blur(40px); }
    .slide-book, .slide-rules { padding-left: 14px; padding-right: 14px; padding-top: 76px; }
    .slide-book { padding-bottom: 96px; }
    .slide-rules { padding-bottom: 96px; }
    .book-cover { max-width: 190px; }
    .book-info h2 { font-size: clamp(1.5rem, 6vw, 2rem); }
    .book-actions { flex-direction: column; align-items: stretch; }
    .cta-solid, .cta-outline { justify-content: center; }
    .thumb { width: 92px; }
    .ticker-bar { padding: 12px 14px 12px 42px; gap: 12px; }
    .ticker-brand, .ticker-divider { display: none; }
    .ticker-icon { display: none; }
    .ticker-text { font-size: .68rem; }
    .ticker-cta { padding: 8px 16px; font-size: .64rem; }
    .rules-list li { grid-template-columns: 30px 1fr; }
    .slide-rules .frame { padding: 40px 22px; }
  }

  @media (max-width: 420px) {
    .book-cover { max-width: 155px; }
    .ticker-cta { display: none; }
    .hud-top .brand { font-size: .7rem; letter-spacing: .1em; }
    .hero-content .tagline { font-size: .82rem; }
  }

  @media (max-width: 360px) {
    .hud-top { padding: 12px 12px; }
    .btn-masuk-top { padding: 7px 13px; font-size: .6rem; }
    .btn-musik, .btn-mode { width: 30px; height: 30px; }
    .cta-glow { padding: 12px 32px; font-size: .7rem; }
  }

  /* layar pendek / landscape ponsel — kecilkan hero agar CTA tetap terlihat */
  @media (max-height: 480px) and (orientation: landscape) {
    .hero-content { padding: 20px clamp(16px, 5vw, 40px); }
    .slide-hero .hero-content h1 { font-size: clamp(1.8rem, 7vw, 3rem); }
    .cta-glow { margin-top: 18px; padding: 10px 30px; }
    .scroll-hint { bottom: 20px; }
    .hero-glow { display: none; }
  }

  body.ticker-hidden .slide-book { padding-bottom: 40px; }
  body.ticker-hidden .slide-rules { padding-bottom: 40px; }
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
    <audio id="audioLatar" loop preload="none">
      <source src="<?= htmlspecialchars($musik_file) ?>">
    </audio>
    <?php endif; ?>
    <button type="button" class="btn-mode" id="btnMode" aria-label="Ganti mode gelap/terang" title="Mode Gelap / Terang" aria-pressed="false">
      <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/>
      </svg>
      <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="4.2"/>
        <path d="M12 2.5v2.4M12 19.1v2.4M4.9 4.9l1.7 1.7M17.4 17.4l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.9 19.1l1.7-1.7M17.4 6.6l1.7-1.7"/>
      </svg>
    </button>
    <a href="sign_in.php" class="btn-masuk-top">Masuk</a>
  </div>
</div>

<nav class="dot-nav">
  <button class="dot active" data-target="slide-1" aria-label="Beranda"><span></span></button>
  <button class="dot" data-target="slide-2" aria-label="Buku Terbaru"><span></span></button>
  <button class="dot" data-target="slide-3" aria-label="Tata Tertib"><span></span></button>
</nav>

<main class="slides">

  <!-- ═══════════ SLIDE 1 — HERO / LIVE WALLPAPER ═══════════ -->
  <section id="slide-1" class="slide slide-hero">
    <!-- Ganti sumber video di src bawah ini dengan file wallpaper Anda -->
    <video class="bg-video" autoplay muted loop playsinline
      <?php if ($hero_poster): ?>poster="<?= htmlspecialchars($hero_poster) ?>"<?php endif; ?>>
      <source src="uploads/wallpaper/hero.mp4" type="video/mp4">
    </video>
    <div class="hero-overlay"></div>
    <div class="hero-glow" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="hero-content">
      <p class="eyebrow">Perpustakaan Digital</p>
      <h1>AKSA NOVA</h1>
      <p class="tagline">Setiap halaman menyimpan dunia baru — masuk dan mulai jelajahi koleksinya.</p>
      <a href="sign_in.php" class="cta-glow">Masuk &amp; Jelajahi</a>
    </div>
    <div class="scroll-hint">Gulir untuk melihat lebih<span class="chevron">⌄</span></div>
  </section>

  <!-- ═══════════ SLIDE 2 — BUKU TERBARU ═══════════ -->
  <section id="slide-2" class="slide slide-book">
    <div class="frame frame-gold reveal">
      <span class="corner corner-tl"></span><span class="corner corner-tr"></span>
      <span class="corner corner-bl"></span><span class="corner corner-br"></span>
      <span class="frame-label">Buku Terbaru</span>

      <div class="book-layout">
        <div class="book-info">
          <p class="eyebrow">Koleksi Baru</p>
          <h2 id="bukuJudul"><?= $buku_utama ? htmlspecialchars($buku_utama["judul"]) : "Segera Hadir" ?></h2>
          <p class="author">
            oleh <strong id="bukuPenulis"><?= $buku_utama && $buku_utama["penulis"] ? htmlspecialchars($buku_utama["penulis"]) : "Belum diketahui" ?></strong>
          </p>

          <div class="meta-row">
            <span class="meta-chip">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v17H6.5A2.5 2.5 0 0 0 4 21.5v-17Z"/><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/></svg>
              <span id="bukuGenre"><?= $buku_utama ? htmlspecialchars(format_genre($buku_utama["genre"]) ?: "Umum") : "Umum" ?></span>
            </span>
            <span class="meta-chip meta-chip--status">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
              Tersedia Dipinjam
            </span>
          </div>

          <p class="sinopsis" id="bukuSinopsis"><?= $buku_utama ? htmlspecialchars(ringkas($buku_utama["sinopsis"])) : "Koleksi buku terbaru akan segera ditambahkan oleh admin perpustakaan." ?></p>

          <div class="book-actions">
            <a href="sign_in.php" class="cta-solid">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 5v14l11-7z"/></svg>
              Pinjam Sekarang
            </a>
            <a href="sign_in.php" class="cta-outline">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 1 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/></svg>
              Tambah Wishlist
            </a>
          </div>
        </div>

        <div class="book-cover-wrap">
          <span class="cover-glow"></span>
          <div class="book-cover">
            <img id="bukuGambar"
                 src="<?= $buku_utama && $buku_utama["gambar"] ? htmlspecialchars($buku_utama["gambar"]) : "https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=700&q=80" ?>"
                 alt="Sampul buku">
          </div>
        </div>
      </div>

      <?php if (count($buku_list) > 1): ?>
      <div class="similar-section">
        <p class="similar-label">Buku Serupa Lainnya</p>
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
      <span class="frame-label">Tata Tertib</span>

      <h2>Aturan Perpustakaan</h2>
      <p class="lead">Mohon perhatikan ketentuan berikut sebelum meminjam koleksi di AKSA NOVA.</p>

      <ul class="rules-list reveal-stagger">
        <li class="reveal-item">
          <span class="num">01</span>
          <div>
            <h3>Jam Operasional</h3>
            <p>Perpustakaan melayani peminjaman setiap Senin–Jumat, pukul 07.00–15.00 WIB.</p>
          </div>
        </li>
        <li class="reveal-item">
          <span class="num">02</span>
          <div>
            <h3>Kartu Anggota</h3>
            <p>Peminjaman hanya dapat dilakukan oleh anggota terdaftar menggunakan akun masing-masing.</p>
          </div>
        </li>
        <li class="reveal-item">
          <span class="num">03</span>
          <div>
            <h3>Batas Peminjaman</h3>
            <p>Setiap buku dipinjamkan maksimal 7 hari dan dapat diperpanjang jika tidak ada antrean.</p>
          </div>
        </li>
        <li class="reveal-item">
          <span class="num">04</span>
          <div>
            <h3>Keterlambatan</h3>
            <p><?= htmlspecialchars($denda_text) ?></p>
          </div>
        </li>
        <li class="reveal-item">
          <span class="num">05</span>
          <div>
            <h3>Menjaga Koleksi</h3>
            <p>Buku yang dipinjam wajib dijaga kebersihan dan kelengkapannya; kerusakan atau kehilangan menjadi tanggung jawab peminjam.</p>
          </div>
        </li>
        <li class="reveal-item">
          <span class="num">06</span>
          <div>
            <h3>Ketertiban Ruangan</h3>
            <p>Jaga ketenangan di area baca dan kembalikan buku ke rak/petugas sesuai tempatnya.</p>
          </div>
        </li>
      </ul>
    </div>
  </section>

</main>

<div class="ticker-bar" id="tickerBar">
  <button type="button" class="ticker-close" id="tickerClose" aria-label="Tutup notifikasi">✕</button>
  <div class="ticker-icon" aria-hidden="true">📖</div>
  <div class="ticker-brand">AKSA<br>NOVA</div>
  <div class="ticker-divider"></div>
  <div class="ticker-text"><span class="ticker-dot" aria-hidden="true"></span>Selamat datang di perpustakaan digital AKSA NOVA — masuk untuk mulai meminjam buku.</div>
  <a href="sign_in.php" class="ticker-cta">Masuk</a>
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

  // Musik latar — bisa dinyalakan/dimatikan pengunjung, sumbernya diatur admin
  (function () {
    const audio  = document.getElementById('audioLatar');
    const btn    = document.getElementById('btnMusik');
    if (!audio || !btn) return;

    const STORAGE_KEY = 'aksanova_musik_aktif';
    audio.volume = 0.55;

    function setPlaying(isPlaying) {
      btn.classList.toggle('playing', isPlaying);
      btn.setAttribute('aria-pressed', isPlaying ? 'true' : 'false');
    }

    function play() {
      audio.play().then(() => {
        setPlaying(true);
        sessionStorage.setItem(STORAGE_KEY, '1');
      }).catch(() => setPlaying(false));
    }

    function pause() {
      audio.pause();
      setPlaying(false);
      sessionStorage.setItem(STORAGE_KEY, '0');
    }

    btn.addEventListener('click', () => {
      if (audio.paused) play(); else pause();
    });

    // Coba lanjutkan otomatis jika pengunjung sebelumnya menyalakan musik di sesi ini
    if (sessionStorage.getItem(STORAGE_KEY) === '1') {
      play();
    }
  })();

  // Animasi scroll-reveal untuk konten slide 2 & 3
  const revealTargets = document.querySelectorAll('.reveal');
  if (revealTargets.length) {
    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('in-view');
          revealObserver.unobserve(entry.target);
        }
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