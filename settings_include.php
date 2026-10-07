<?php
// settings_include.php — Sertakan di <head> semua halaman (sebelum </head>)
// Terapkan preferensi tema dari localStorage via inline script agar tidak flash
?>
<script>
(function(){
  // Baca settings dari localStorage
  var s;
  try { s = JSON.parse(localStorage.getItem('aksanova_settings') || '{}'); } catch(e){ s={}; }

  var root = document.documentElement;

  // ── Tema / Mode ──
  // Periksa apakah user punya setting di localStorage, atau sinkron dengan tema index.php
  var indexTheme  = localStorage.getItem('aksanova_theme'); // 'light' atau null/dark
  var defaultMode = (indexTheme === 'light') ? 'light' : 'dark';
  var mode     = s.mode     || defaultMode;      // light | dark
  var ui       = s.ui       || 'default';        // default | minimal | modern | kuno | gradasi
  var grad     = s.gradient || 'emas';           // default emas matching index.php
  var fontFam  = s.font     || 'Outfit';         // Outfit | Nunito | Merriweather | Poppins | Playfair | Roboto Mono
  var animation= s.animation!==undefined ? s.animation : true;

  // Palettenya
  var palettes = {
    emas:       { accent:'#d8b878', accent2:'#f0d9a8', accentRgb:'216,184,120' },
    biru:       { accent:'#2b4fff', accent2:'#ffb800', accentRgb:'43,79,255' },
    ungu:       { accent:'#7c3aed', accent2:'#f59e0b', accentRgb:'124,58,237' },
    hijau:      { accent:'#059669', accent2:'#fbbf24', accentRgb:'5,150,105' },
    merah:      { accent:'#dc2626', accent2:'#f59e0b', accentRgb:'220,38,38' },
    merah_muda: { accent:'#db2777', accent2:'#7c3aed', accentRgb:'219,39,119' },
  };
  var pal = palettes[grad] || palettes.emas;

  // ── Mode gelap/terang & UI ──
  var uiVars = {
    default: {
      light: { bg:'#f6f2e8', sidebarBg:'#ffffff', card:'#ffffff', text:'#221d14', muted:'#7a7060', border:'rgba(150,110,45,.20)', cardBorder:'rgba(150,110,45,.15)', bookCard:'#fdfbf7' },
      dark:  { bg:'#090c10', sidebarBg:'#10151b', card:'#121820', text:'#eef3f4', muted:'rgba(238,243,244,.65)', border:'rgba(216,184,120,.18)', cardBorder:'rgba(216,184,120,.12)', bookCard:'#161e27' },
    },
    minimal: {
      light: { bg:'#fafafa', sidebarBg:'#f5f5f5', card:'#ffffff', text:'#111111', muted:'#999999', border:'#e0e0e0', cardBorder:'#ebebeb', bookCard:'#f5f5f5' },
      dark:  { bg:'#111111', sidebarBg:'#191919', card:'#222222', text:'#eeeeee', muted:'#888888', border:'#333333', cardBorder:'#2a2a2a', bookCard:'#1e1e1e' },
    },
    modern: {
      light: { bg:'#f0f2ff', sidebarBg:'#ffffff', card:'#ffffff', text:'#1a1a35', muted:'#6b7280', border:'#dde0ff', cardBorder:'#e8eaff', bookCard:'#f5f6ff' },
      dark:  { bg:'#0d0d1f', sidebarBg:'#12122a', card:'#18183a', text:'#e0e0ff', muted:'#7777aa', border:'#25254a', cardBorder:'#20203e', bookCard:'#1c1c38' },
    },
    kuno: {
      light: { bg:'#f5efe6', sidebarBg:'#fdf6eb', card:'#fffdf5', text:'#2c1810', muted:'#8b7355', border:'#d4c09a', cardBorder:'#e8dcc0', bookCard:'#f9f0dc' },
      dark:  { bg:'#1a1008', sidebarBg:'#221508', card:'#2a1a08', text:'#f0e0c0', muted:'#a08050', border:'#4a3020', cardBorder:'#3a2510', bookCard:'#251508' },
    },
    gradasi: {
      light: { bg:'linear-gradient(135deg,#f0f4ff 0%,#fdf0ff 100%)', sidebarBg:'#ffffff', card:'#ffffff', text:'#1a1a2e', muted:'#7a7a9a', border:'#e8e9f0', cardBorder:'#eef0fc', bookCard:'rgba(255,255,255,.7)' },
      dark:  { bg:'linear-gradient(135deg,#0a0a20 0%,#1a0a2e 100%)', sidebarBg:'#14142a', card:'rgba(26,26,50,.95)', text:'#e8e8f5', muted:'#8888aa', border:'#2a2a45', cardBorder:'#25254a', bookCard:'rgba(30,30,55,.9)' },
    },
  };

  var uiKey   = uiVars[ui] ? ui : 'default';
  var modeKey = mode === 'dark' ? 'dark' : 'light';
  var colors  = uiVars[uiKey][modeKey];

  // Apply background (gradient atau solid)
  if (colors.bg.startsWith('linear')) {
    root.style.setProperty('--bg', colors.bg);
    document.documentElement.style.background = colors.bg;
  } else {
    root.style.setProperty('--bg', colors.bg);
  }
  root.style.setProperty('--sidebar-bg', colors.sidebarBg);
  root.style.setProperty('--card', colors.card);
  root.style.setProperty('--text', colors.text);
  root.style.setProperty('--muted', colors.muted);
  root.style.setProperty('--border-color', colors.border);
  root.style.setProperty('--card-border', colors.cardBorder);
  root.style.setProperty('--book-card', colors.bookCard);

  // Accent warna
  root.style.setProperty('--accent', pal.accent);
  root.style.setProperty('--accent2', pal.accent2);
  root.style.setProperty('--accent-rgb', pal.accentRgb);

  // Font keluarga
  var fontMap = {
    'Outfit':           "'Outfit', sans-serif",
    'Nunito':           "'Nunito', sans-serif",
    'Merriweather':     "'Merriweather', serif",
    'Poppins':          "'Poppins', sans-serif",
    'Playfair':         "'Playfair Display', serif",
    'Playfair Display': "'Playfair Display', serif",
    'Roboto Mono':      "'Roboto Mono', monospace",
  };
  root.style.setProperty('--font-family', fontMap[fontFam] || fontMap['Outfit']);

  // Animation
  root.style.setProperty('--trans-speed', animation ? '.2s' : '0s');

  // Class pada html
  root.className = [mode, 'ui-' + uiKey].join(' ');
})();
</script>
<style>
  /* ── Variabel dasar yang selalu ada (fallback) ── */
  :root {
    --font-family:      'Outfit', sans-serif;
    --font-size-base:   14px;
    --font-weight-base: 400;
    --radius:           14px;
    --spacing:          1rem;
    --trans-speed:      .2s;
    --accent:           #d8b878;
    --accent2:          #f0d9a8;
    --accent-rgb:       216,184,120;
    --bg:               #090c10;
    --sidebar-bg:       #10151b;
    --card:             #121820;
    --text:             #eef3f4;
    --muted:            rgba(238,243,244,.65);
    --border-color:     rgba(216,184,120,.18);
    --card-border:      rgba(216,184,120,.12);
    --book-card:        #161e27;
  }
  /* Paksa font & size ke body */
  body {
    font-family: var(--font-family) !important;
    font-size:   var(--font-size-base) !important;
    font-weight: var(--font-weight-base) !important;
  }
  /* Sidebar border */
  .sidebar { border-right: 1px solid var(--border-color) !important; }
  /* Card border */
  .book-card { background: var(--book-card) !important; border-color: var(--card-border) !important; }
  .section-card, .stats-card, .admin-card { background: var(--card) !important; }

  /* ── Mode gelap — override warna utama ── */
  html.dark body         { background: var(--bg) !important; color: var(--text) !important; }
  html.dark .sidebar     { background: var(--sidebar-bg) !important; }
  html.dark .section-card,
  html.dark .stats-card,
  html.dark .admin-card  { background: var(--card) !important; box-shadow: 0 2px 12px rgba(0,0,0,.3) !important; }
  html.dark .search-wrap { background: var(--card) !important; border-color: var(--border-color) !important; }
  html.dark .tab-btn     { background: var(--card) !important; border-color: var(--border-color) !important; color: var(--text) !important; }
  html.dark input        { color: var(--text) !important; background: transparent !important; }
  html.dark .book-card   { background: var(--book-card) !important; border-color: var(--card-border) !important; }
  html.dark .detail-modal{ background: var(--card) !important; }
  html.dark .detail-sinopsis { background: rgba(255,255,255,.05) !important; color: var(--text) !important; }
  html.dark .detail-meta-chip{ background: rgba(255,255,255,.07) !important; border-color: var(--card-border) !important; color: var(--muted) !important; }
  html.dark .stat-box    { background: rgba(255,255,255,.08) !important; }
  html.dark .nav-item    { color: var(--muted) !important; }
  html.dark .nav-item:hover, html.dark .nav-item.active { background: rgba(255,255,255,.08) !important; color: var(--accent) !important; }
  html.dark .logo-name   { color: var(--text) !important; }
  html.dark .section-title{ color: var(--text) !important; }
  html.dark .book-title  { color: var(--text) !important; }
  html.dark .admin-name  { color: var(--text) !important; }
  html.dark .stat-num    { color: var(--text) !important; }
  html.dark .detail-title{ color: var(--text) !important; }

  /* ── UI Minimal ── */
  html.ui-minimal .section-card,
  html.ui-minimal .stats-card,
  html.ui-minimal .admin-card { box-shadow: none !important; border: 1px solid var(--border-color) !important; }
  html.ui-minimal .book-card  { border-radius: 6px !important; }
  html.ui-minimal .book-cover { border-radius: 4px !important; }
  html.ui-minimal .sidebar    { box-shadow: none !important; }
  html.ui-minimal .logo-icon  { box-shadow: none !important; border: 1px solid var(--border-color); }

  /* ── UI Modern ── */
  html.ui-modern .section-card,
  html.ui-modern .stats-card  { border-radius: 20px !important; }
  html.ui-modern .book-card   { border-radius: 16px !important; }
  html.ui-modern .sidebar     { border-radius: 0 24px 24px 0 !important; }
  html.ui-modern .nav-item    { border-radius: 14px !important; }
  html.ui-modern .search-wrap { border-radius: 16px !important; }

  /* ── UI Kuno ── */
  html.ui-kuno body           { background: var(--bg) !important; }
  html.ui-kuno .section-card,
  html.ui-kuno .stats-card,
  html.ui-kuno .admin-card    { border: 2px solid var(--border-color) !important; border-radius: 4px !important; box-shadow: 4px 4px 0 var(--border-color) !important; }
  html.ui-kuno .book-card     { border-radius: 4px !important; border: 1px solid var(--border-color) !important; }
  html.ui-kuno .sidebar       { border-radius: 0 !important; border-right: 2px solid var(--border-color) !important; }
  html.ui-kuno .logo-name     { letter-spacing: .2em; font-size: .85rem !important; }
  html.ui-kuno .book-cover    { border-radius: 2px !important; border: 1px solid var(--border-color) !important; }
  html.ui-kuno .tab-btn       { border-radius: 2px !important; }
  html.ui-kuno .nav-item      { border-radius: 0 !important; border-left: 3px solid transparent; }
  html.ui-kuno .nav-item.active,
  html.ui-kuno .nav-item:hover { border-left-color: var(--accent) !important; background: rgba(0,0,0,.04) !important; }

  /* ── UI Gradasi ── */
  html.ui-gradasi body  { background: var(--bg) !important; min-height: 100vh; }
  html.ui-gradasi .section-card,
  html.ui-gradasi .stats-card  {
    background: rgba(255,255,255,.7) !important;
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.4) !important;
  }
  html.ui-gradasi.dark .section-card,
  html.ui-gradasi.dark .stats-card {
    background: rgba(30,30,60,.7) !important;
    border-color: rgba(255,255,255,.1) !important;
  }
  html.ui-gradasi .sidebar {
    background: rgba(255,255,255,.8) !important;
    backdrop-filter: blur(16px);
  }
  html.ui-gradasi.dark .sidebar {
    background: rgba(20,20,42,.85) !important;
  }

  /* ── Sidebar icon mode ── */
  html.sidebar-icon .logo-name,
  html.sidebar-icon .logo-sub,
  html.sidebar-icon .nav-item span,
  html.sidebar-icon .nav-bottom .nav-text { display: none !important; }
  html.sidebar-icon .nav-item { justify-content: center; padding: 10px !important; }
  html.sidebar-icon .logo-wrap { padding: 0 8px 20px; }
  html.sidebar-icon .logo-icon { margin: 0 auto; }

  /* ── Spacing compact/relaxed ── */
  html body .section-card, html body .stats-card { padding: calc(var(--spacing) * 1.1) !important; }

  /* Transitions */
  body, .sidebar, .section-card, .stats-card, .admin-card, .book-card, .nav-item {
    transition: background var(--trans-speed) ease, color var(--trans-speed) ease !important;
  }

  /* ── Stabilitas Tata Letak & Scrollbar (Cegah Loncat Antar Halaman) ── */
  html {
    scrollbar-gutter: stable;
    overflow-y: scroll;
  }

  /* ── Native Cross-Document View Transitions (Chromium 126+) ── */
  @view-transition {
    navigation: auto;
  }
  ::view-transition-group(app-sidebar),
  ::view-transition-old(app-sidebar),
  ::view-transition-new(app-sidebar) {
    animation: none !important;
    animation-duration: 0s !important;
  }
  .sidebar {
    view-transition-name: app-sidebar;
  }
  .main {
    view-transition-name: app-main;
  }
  ::view-transition-old(app-main) {
    animation: 0.12s cubic-bezier(0.4, 0, 1, 1) both pageFadeOut;
  }
  ::view-transition-new(app-main) {
    animation: 0.22s cubic-bezier(0.16, 1, 0.3, 1) both pageFadeIn;
  }
  @keyframes pageFadeOut {
    from { opacity: 1; transform: translateY(0); }
    to   { opacity: 0; transform: translateY(-4px); }
  }
  @keyframes pageFadeIn {
    from { opacity: 0.15; transform: translateY(4px); }
    to   { opacity: 1;    transform: translateY(0); }
  }

  /* ── Hilangkan flash/blink pada body, transisikan hanya konten (.main) ── */
  body {
    animation: none !important;
  }
  @media (prefers-reduced-motion: no-preference) {
    .main {
      animation: pageFadeIn 0.22s cubic-bezier(0.16, 1, 0.3, 1) both;
    }
  }

  /* ══════════════════════════════════════════════════════════════
     KONSISTENSI WARNA SIDEBAR, TEXT, BUTTON & MENU BURGER (GLOBAL)
     ══════════════════════════════════════════════════════════════ */

  /* ── 1. Tombol Menu Burger (.sidebar-toggle) ── */
  .sidebar-toggle {
    width: 42px !important;
    height: 42px !important;
    border-radius: 10px !important;
    cursor: pointer !important;
    align-items: center !important;
    justify-content: center !important;
    transition: border-color .2s ease, box-shadow .2s ease !important;
    background: #161e27 !important;
    border: 1.5px solid var(--accent, #d8b878) !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, .45) !important;
    color: var(--accent, #d8b878) !important;
  }
  .sidebar-toggle svg {
    width: 22px !important;
    height: 22px !important;
    stroke: var(--accent, #d8b878) !important;
    color: var(--accent, #d8b878) !important;
    stroke-width: 2.3px !important;
    display: block !important;
    transition: stroke .2s ease, transform .2s ease !important;
  }
  .sidebar-toggle:hover {
    background: rgba(216, 184, 120, 0.18) !important;
    border-color: var(--accent2, #f0d9a8) !important;
    transform: scale(1.05) !important;
  }
  .sidebar-toggle:hover svg {
    stroke: var(--accent2, #f0d9a8) !important;
    color: var(--accent2, #f0d9a8) !important;
  }
  .sidebar-toggle:active {
    transform: scale(0.92) !important;
  }

  /* Menu Burger pada Mode Terang (Light Mode) */
  html:not(.dark) .sidebar-toggle,
  html.light .sidebar-toggle {
    background: #ffffff !important;
    border: 1.5px solid #b8860b !important;
    box-shadow: 0 4px 14px rgba(184, 134, 11, 0.18) !important;
    color: #8a6100 !important;
  }
  html:not(.dark) .sidebar-toggle svg,
  html.light .sidebar-toggle svg {
    stroke: #8a6100 !important;
    color: #8a6100 !important;
    stroke-width: 2.5px !important;
  }
  html:not(.dark) .sidebar-toggle:hover,
  html.light .sidebar-toggle:hover {
    background: #fcf8ee !important;
    border-color: #694a00 !important;
  }
  html:not(.dark) .sidebar-toggle:hover svg,
  html.light .sidebar-toggle:hover svg {
    stroke: #694a00 !important;
    color: #694a00 !important;
  }

  /* ── 2. Tombol Navigasi Sidebar Admin (.sidebar-btn) ── */
  .sidebar .sidebar-btn {
    width: 100% !important;
    display: flex !important;
    align-items: center !important;
    gap: 11px !important;
    padding: 10px 14px !important;
    border-radius: 9px !important;
    border: 1px solid rgba(216, 184, 120, 0.16) !important;
    background: rgba(255, 255, 255, 0.05) !important;
    color: var(--text, #eef3f4) !important;
    font-size: .83rem !important;
    font-weight: 700 !important;
    cursor: pointer !important;
    margin-bottom: 7px !important;
    transition: border-color .2s ease, box-shadow .2s ease !important;
    text-align: left !important;
    text-decoration: none !important;
    flex-shrink: 0 !important;
    box-sizing: border-box !important;
  }
  .sidebar .sidebar-btn svg {
    width: 17px !important;
    height: 17px !important;
    flex-shrink: 0 !important;
    color: var(--accent, #d8b878) !important;
    stroke: var(--accent, #d8b878) !important;
    stroke-width: 2px !important;
    transition: stroke .2s ease, transform .2s ease !important;
  }
  .sidebar .sidebar-btn:hover {
    background: rgba(216, 184, 120, 0.16) !important;
    border-color: var(--accent, #d8b878) !important;
    color: #ffffff !important;
    transform: translateX(3px) !important;
  }
  .sidebar .sidebar-btn:hover svg {
    stroke: #ffffff !important;
    color: #ffffff !important;
    transform: scale(1.1) !important;
  }
  .sidebar .sidebar-btn.active {
    background: linear-gradient(135deg, #d8b878, #c8a060) !important;
    border-color: #d8b878 !important;
    color: #121820 !important;
    font-weight: 800 !important;
    box-shadow: 0 4px 14px rgba(216, 184, 120, 0.35) !important;
  }
  .sidebar .sidebar-btn.active svg {
    color: #121820 !important;
    stroke: #121820 !important;
    stroke-width: 2.2px !important;
  }

  /* .sidebar-btn pada Mode Terang (Light Mode) */
  html:not(.dark) .sidebar .sidebar-btn,
  html.light .sidebar .sidebar-btn {
    background: rgba(0, 0, 0, 0.04) !important;
    border: 1px solid rgba(150, 110, 45, 0.22) !important;
    color: #221d14 !important;
  }
  html:not(.dark) .sidebar .sidebar-btn svg,
  html.light .sidebar .sidebar-btn svg {
    color: #8a6100 !important;
    stroke: #8a6100 !important;
  }
  html:not(.dark) .sidebar .sidebar-btn:hover,
  html.light .sidebar .sidebar-btn:hover {
    background: rgba(216, 184, 120, 0.2) !important;
    border-color: #8a6100 !important;
    color: #000000 !important;
  }
  html:not(.dark) .sidebar .sidebar-btn:hover svg,
  html.light .sidebar .sidebar-btn:hover svg {
    color: #5c4100 !important;
    stroke: #5c4100 !important;
  }
  html:not(.dark) .sidebar .sidebar-btn.active,
  html.light .sidebar .sidebar-btn.active {
    background: linear-gradient(135deg, #d8b878, #c8a060) !important;
    border-color: #c8a060 !important;
    color: #121820 !important;
    box-shadow: 0 3px 12px rgba(184, 134, 11, 0.28) !important;
  }
  html:not(.dark) .sidebar .sidebar-btn.active svg,
  html.light .sidebar .sidebar-btn.active svg {
    color: #121820 !important;
    stroke: #121820 !important;
  }

  /* Tombol Pengaturan di Sidebar Admin (.btn-settings-nav) */
  .sidebar .sidebar-btn.btn-settings-nav {
    background: rgba(216, 184, 120, 0.12) !important;
    border: 1px solid rgba(216, 184, 120, 0.3) !important;
    color: var(--accent2, #f0d9a8) !important;
  }
  .sidebar .sidebar-btn.btn-settings-nav svg {
    color: var(--accent, #d8b878) !important;
    stroke: var(--accent, #d8b878) !important;
  }
  .sidebar .sidebar-btn.btn-settings-nav:hover {
    background: rgba(216, 184, 120, 0.22) !important;
    border-color: var(--accent, #d8b878) !important;
    color: #ffffff !important;
  }
  html:not(.dark) .sidebar .sidebar-btn.btn-settings-nav,
  html.light .sidebar .sidebar-btn.btn-settings-nav {
    background: rgba(184, 134, 11, 0.12) !important;
    border: 1px solid rgba(184, 134, 11, 0.3) !important;
    color: #8a6100 !important;
  }
  html:not(.dark) .sidebar .sidebar-btn.btn-settings-nav svg,
  html.light .sidebar .sidebar-btn.btn-settings-nav svg {
    color: #8a6100 !important;
    stroke: #8a6100 !important;
  }
  html:not(.dark) .sidebar .sidebar-btn.btn-settings-nav:hover,
  html.light .sidebar .sidebar-btn.btn-settings-nav:hover {
    background: rgba(184, 134, 11, 0.22) !important;
    border-color: #694a00 !important;
    color: #000000 !important;
  }

  /* ── 3. Tombol Navigasi Sidebar Anggota/User (.nav-item) ── */
  .sidebar .nav-item {
    color: var(--text, #eef3f4) !important;
    font-weight: 600 !important;
    transition: all .2s ease !important;
  }
  .sidebar .nav-item svg {
    color: var(--accent, #d8b878) !important;
    stroke: var(--accent, #d8b878) !important;
    stroke-width: 2px !important;
    transition: transform .2s ease, stroke .2s ease !important;
  }
  .sidebar .nav-item:hover {
    background: rgba(216, 184, 120, 0.15) !important;
    color: var(--accent, #d8b878) !important;
    transform: translateX(3px) !important;
  }
  .sidebar .nav-item:hover svg {
    transform: scale(1.1) !important;
  }
  .sidebar .nav-item.active {
    background: rgba(216, 184, 120, 0.22) !important;
    color: var(--accent2, #f0d9a8) !important;
    font-weight: 800 !important;
  }
  .sidebar .nav-item.active svg {
    color: var(--accent2, #f0d9a8) !important;
    stroke: var(--accent2, #f0d9a8) !important;
  }

  /* .nav-item pada Mode Terang (Light Mode) */
  html:not(.dark) .sidebar .nav-item,
  html.light .sidebar .nav-item {
    color: #221d14 !important;
  }
  html:not(.dark) .sidebar .nav-item svg,
  html.light .sidebar .nav-item svg {
    color: #8a6100 !important;
    stroke: #8a6100 !important;
  }
  html:not(.dark) .sidebar .nav-item:hover,
  html.light .sidebar .nav-item:hover {
    background: rgba(216, 184, 120, 0.18) !important;
    color: #694a00 !important;
  }
  html:not(.dark) .sidebar .nav-item:hover svg,
  html.light .sidebar .nav-item:hover svg {
    color: #5c4100 !important;
    stroke: #5c4100 !important;
  }
  html:not(.dark) .sidebar .nav-item.active,
  html.light .sidebar .nav-item.active {
    background: rgba(216, 184, 120, 0.26) !important;
    color: #5c4100 !important;
    font-weight: 800 !important;
  }
  html:not(.dark) .sidebar .nav-item.active svg,
  html.light .sidebar .nav-item.active svg {
    color: #5c4100 !important;
    stroke: #5c4100 !important;
  }

  /* ── 4. Label Nama & Badge Sidebar ── */
  .sidebar .admin-name-label {
    color: var(--text, #eef3f4) !important;
    font-size: .95rem !important;
    font-weight: 700 !important;
    margin-bottom: 8px !important;
    text-align: center !important;
  }
  html:not(.dark) .sidebar .admin-name-label,
  html.light .sidebar .admin-name-label {
    color: #1a1a2e !important;
    font-weight: 800 !important;
  }
  .sidebar .total-badge {
    background: rgba(216, 184, 120, 0.16) !important;
    border: 1px solid rgba(216, 184, 120, 0.3) !important;
    color: var(--accent, #d8b878) !important;
    font-weight: 700 !important;
  }
  html:not(.dark) .sidebar .total-badge,
  html.light .sidebar .total-badge {
    background: rgba(184, 134, 11, 0.12) !important;
    border-color: rgba(184, 134, 11, 0.25) !important;
    color: #8a6100 !important;
  }
  .sidebar .logo-name {
    color: var(--accent, #d8b878) !important;
  }
  html:not(.dark) .sidebar .logo-name,
  html.light .sidebar .logo-name {
    color: #8a6100 !important;
  }
  .sidebar .logo-sub {
    color: var(--muted, rgba(238, 243, 244, 0.65)) !important;
  }
  html:not(.dark) .sidebar .logo-sub,
  html.light .sidebar .logo-sub {
    color: #7a7060 !important;
  }


  /* ── 5. Avatar Profil Admin di Sidebar (Ukuran Pas & Terkunci) ── */
  .sidebar .sidebar-header {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    width: 100% !important;
    margin-bottom: 12px !important;
  }
  .sidebar .avatar-wrap {
    position: relative !important;
    width: 80px !important;
    height: 80px !important;
    max-width: 80px !important;
    max-height: 80px !important;
    margin: 0 auto 10px auto !important;
    cursor: pointer !important;
    display: block !important;
    flex-shrink: 0 !important;
  }
  .sidebar .avatar-circle {
    width: 80px !important;
    height: 80px !important;
    max-width: 80px !important;
    max-height: 80px !important;
    border-radius: 50% !important;
    background: #161e27 !important;
    overflow: hidden !important;
    border: 2.5px solid rgba(216, 184, 120, 0.35) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: border-color .2s ease, box-shadow .2s ease !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, .45) !important;
    position: relative !important;
  }
  .sidebar .avatar-wrap:hover .avatar-circle {
    border-color: var(--accent, #d8b878) !important;
    transform: scale(1.04) !important;
    box-shadow: 0 0 20px rgba(216, 184, 120, 0.4) !important;
  }
  .sidebar .avatar-circle img {
    width: 100% !important;
    height: 100% !important;
    max-width: 100% !important;
    max-height: 100% !important;
    object-fit: cover !important;
    object-position: center !important;
    display: block !important;
    border-radius: 50% !important;
  }
  .sidebar .avatar-circle svg,
  .sidebar .avatar-circle .default-icon {
    width: 44px !important;
    height: 44px !important;
    max-width: 44px !important;
    max-height: 44px !important;
    color: var(--muted, #888) !important;
    display: block !important;
  }
  .sidebar .avatar-overlay {
    position: absolute !important;
    inset: 0 !important;
    border-radius: 50% !important;
    background: rgba(0, 0, 0, .55) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    opacity: 0 !important;
    transition: opacity .2s ease !important;
    pointer-events: none !important;
  }
  .sidebar .avatar-wrap:hover .avatar-overlay {
    opacity: 1 !important;
  }
  .sidebar .avatar-overlay svg {
    width: 22px !important;
    height: 22px !important;
    color: #ffffff !important;
    stroke: #ffffff !important;
    stroke-width: 2px !important;
  }


  /* ── Kunci Sidebar Statis Sempurna (Cegah Geser/Jumping) ── */
  .sidebar {
    width: var(--sidebar-w, 204px) !important;
    padding: 28px 20px 16px !important;
    box-sizing: border-box !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    overflow: hidden !important;
  }
  .sidebar .sidebar-header {
    flex-shrink: 0 !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    width: 100% !important;
    margin-bottom: 0 !important;
    padding: 0 !important;
  }
  .sidebar .sidebar-nav {
    width: 100% !important;
    flex: 1 1 auto !important;
    min-height: 0 !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    display: flex !important;
    flex-direction: column !important;
    padding-right: 2px !important;
  }
  .sidebar .avatar-wrap {
    position: relative !important;
    width: 80px !important;
    height: 80px !important;
    max-width: 80px !important;
    max-height: 80px !important;
    margin: 0 auto 10px auto !important;
    cursor: pointer !important;
    display: block !important;
    flex-shrink: 0 !important;
    transition: none !important;
  }
  .sidebar .avatar-circle {
    width: 80px !important;
    height: 80px !important;
    max-width: 80px !important;
    max-height: 80px !important;
    border-radius: 50% !important;
    background: #161e27 !important;
    overflow: hidden !important;
    border: 2.5px solid rgba(216, 184, 120, 0.35) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: border-color .2s ease, box-shadow .2s ease !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, .45) !important;
    position: relative !important;
  }
  .sidebar .avatar-circle img {
    width: 80px !important;
    height: 80px !important;
    max-width: 80px !important;
    max-height: 80px !important;
    object-fit: cover !important;
    object-position: center !important;
    display: block !important;
    border-radius: 50% !important;
    transition: none !important;
  }
  .sidebar .admin-name-label {
    margin-top: 0 !important;
    margin-bottom: 14px !important;
    font-size: .92rem !important;
    text-align: center !important;
    line-height: 1.3 !important;
    transition: none !important;
  }

  /* ═══════════════════════════════════════════════════════════════════
     ── 6. PERBAIKAN KONTRAS: Tombol, Tab, Modal, Dropdown, Badge ──
     Elemen-elemen ini hardcoded #fff / #f0f0f5 — tidak terlihat di dark mode
  ═══════════════════════════════════════════════════════════════════ */

  /* --- Tombol Batal / Cancel (banyak file admin pakai #fff / #f0f0f5) --- */
  html.dark .btn-cancel,
  html.dark .btn-cancel-modal {
    background: var(--book-card, #161e27) !important;
    border-color: var(--border-color, rgba(216,184,120,.18)) !important;
    color: var(--muted, rgba(238,243,244,.65)) !important;
  }
  html.dark .btn-cancel:hover,
  html.dark .btn-cancel-modal:hover {
    background: rgba(216,184,120,.12) !important;
    border-color: var(--accent, #d8b878) !important;
    color: var(--accent, #d8b878) !important;
  }

  /* --- Tombol Close / X Modal --- */
  html.dark .modal-close {
    background: rgba(255,255,255,.08) !important;
    color: var(--muted, rgba(238,243,244,.65)) !important;
    border: 1px solid rgba(255,255,255,.08) !important;
  }
  html.dark .modal-close:hover {
    background: rgba(220,38,38,.18) !important;
    color: #f87171 !important;
    border-color: rgba(220,38,38,.3) !important;
  }
  html.dark .modal-close svg {
    color: var(--muted, rgba(238,243,244,.65)) !important;
    stroke: var(--muted, rgba(238,243,244,.65)) !important;
  }

  /* --- Tab Button (telah_dipinjam, daftar_buku, pengajuan_buku) --- */
  html.dark .tab-btn:not(.active) {
    background: var(--card, #121820) !important;
    border-color: var(--border-color, rgba(216,184,120,.18)) !important;
    color: var(--muted, rgba(238,243,244,.65)) !important;
  }
  html.dark .tab-btn:not(.active):hover {
    background: rgba(216,184,120,.1) !important;
    border-color: var(--accent, #d8b878) !important;
    color: var(--accent, #d8b878) !important;
  }
  html.dark .tab-btn.active {
    background: linear-gradient(135deg, #d8b878, #c8a060) !important;
    color: #121820 !important;
    border-color: #d8b878 !important;
    font-weight: 800 !important;
  }

  /* --- Tab Count Badge --- */
  html.dark .tab-count {
    background: rgba(255,255,255,.08) !important;
    color: var(--muted, rgba(238,243,244,.65)) !important;
  }
  html.dark .tab-btn.active .tab-count {
    background: rgba(0,0,0,.25) !important;
    color: #121820 !important;
  }

  /* --- Status Tab (daftar_anggota.php filter tab) --- */
  html.dark .status-tab:not(.active) {
    background: var(--card, #121820) !important;
    border: 1px solid var(--border-color, rgba(216,184,120,.18)) !important;
    color: var(--muted, rgba(238,243,244,.65)) !important;
  }
  html.dark .status-tab:not(.active):hover {
    background: rgba(216,184,120,.1) !important;
    color: var(--accent, #d8b878) !important;
  }
  html.dark .status-tab.active {
    background: linear-gradient(135deg, #d8b878, #c8a060) !important;
    color: #121820 !important;
    border-color: #d8b878 !important;
  }

  /* --- Year Nav Buttons (dashboard.php) --- */
  html.dark .year-nav .nav-btn,
  html.dark .year-nav .year-reset {
    background: var(--book-card, #161e27) !important;
    border: 1px solid var(--border-color, rgba(216,184,120,.18)) !important;
    color: var(--muted, rgba(238,243,244,.65)) !important;
  }
  html.dark .year-nav .nav-btn:hover,
  html.dark .year-nav .year-reset:hover {
    background: rgba(216,184,120,.12) !important;
    color: var(--accent, #d8b878) !important;
  }

  /* --- Icon Buttons (kelola_banner.php) --- */
  html.dark .icon-btn {
    background: var(--book-card, #161e27) !important;
    border: 1px solid var(--border-color, rgba(216,184,120,.18)) !important;
    color: var(--muted, rgba(238,243,244,.65)) !important;
  }
  html.dark .icon-btn:hover {
    background: rgba(216,184,120,.14) !important;
    border-color: var(--accent, #d8b878) !important;
    color: var(--accent, #d8b878) !important;
  }
  html.dark .icon-btn.danger:hover {
    background: rgba(220,38,38,.18) !important;
    border-color: rgba(220,38,38,.4) !important;
    color: #f87171 !important;
  }

  /* --- Btn Pilih Anggota (pinjam_buku.php) --- */
  html.dark .btn-pilih-anggota {
    background: var(--book-card, #161e27) !important;
    border: 1.5px solid var(--border-color, rgba(216,184,120,.18)) !important;
    color: var(--text, #eef3f4) !important;
  }
  html.dark .btn-pilih-anggota:hover {
    background: rgba(216,184,120,.15) !important;
    border-color: var(--accent, #d8b878) !important;
    color: var(--accent, #d8b878) !important;
  }

  /* --- Btn Hapus Riwayat (telah_dipinjam.php) --- */
  html.dark .btn-hapus-riwayat {
    background: rgba(220,38,38,.1) !important;
    border-color: rgba(220,38,38,.35) !important;
    color: #f87171 !important;
  }
  html.dark .btn-hapus-riwayat:hover {
    background: rgba(220,38,38,.2) !important;
    border-color: rgba(220,38,38,.5) !important;
  }

  /* --- Btn WA Disabled (telah_dipinjam.php) --- */
  html.dark .btn-wa-disabled {
    background: rgba(255,255,255,.05) !important;
    border: 1px solid rgba(255,255,255,.08) !important;
    color: var(--muted, rgba(238,243,244,.45)) !important;
  }

  /* --- Action Buttons Pengajuan (pengajuan_buku.php) --- */
  html.dark .btn-action-approve {
    background: rgba(5,150,105,.15) !important;
    border-color: rgba(5,150,105,.3) !important;
    color: #4ade80 !important;
  }
  html.dark .btn-action-approve:hover {
    background: rgba(5,150,105,.25) !important;
    border-color: rgba(5,150,105,.5) !important;
  }
  html.dark .btn-action-reject {
    background: rgba(220,38,38,.15) !important;
    border-color: rgba(220,38,38,.3) !important;
    color: #f87171 !important;
  }
  html.dark .btn-action-reject:hover {
    background: rgba(220,38,38,.25) !important;
    border-color: rgba(220,38,38,.5) !important;
  }

  /* --- Card Thumb Button (pengajuan_buku.php) --- */
  html.dark .card-thumb-btn {
    background: rgba(216,184,120,.1) !important;
    border: 1px solid rgba(216,184,120,.22) !important;
    color: var(--accent, #d8b878) !important;
  }
  html.dark .card-thumb-btn:hover {
    background: rgba(216,184,120,.2) !important;
    border-color: var(--accent, #d8b878) !important;
  }

  /* --- Badge Anggota (daftar_anggota.php) --- */
  html.dark .badge-pending {
    background: rgba(245,158,11,.15) !important;
    color: #fbbf24 !important;
    border: 1px solid rgba(245,158,11,.28) !important;
  }
  html.dark .badge-approved {
    background: rgba(5,150,105,.15) !important;
    color: #4ade80 !important;
    border: 1px solid rgba(5,150,105,.28) !important;
  }
  html.dark .badge-rejected {
    background: rgba(220,38,38,.15) !important;
    color: #f87171 !important;
    border: 1px solid rgba(220,38,38,.28) !important;
  }
  html.dark .badge-frozen {
    background: rgba(59,130,246,.15) !important;
    color: #93c5fd !important;
    border: 1px solid rgba(59,130,246,.28) !important;
  }

  /* --- Badge Pengajuan (pengajuan_buku.php) --- */
  html.dark .badge-menunggu {
    background: rgba(245,158,11,.15) !important;
    color: #fbbf24 !important;
    border: 1px solid rgba(245,158,11,.25) !important;
  }
  html.dark .badge-disetujui {
    background: rgba(5,150,105,.15) !important;
    color: #4ade80 !important;
    border: 1px solid rgba(5,150,105,.25) !important;
  }
  html.dark .badge-ditolak {
    background: rgba(220,38,38,.15) !important;
    color: #f87171 !important;
    border: 1px solid rgba(220,38,38,.25) !important;
  }
  html.dark .badge-pickup {
    background: rgba(59,130,246,.12) !important;
    color: #93c5fd !important;
    border: 1px solid rgba(59,130,246,.22) !important;
  }

  /* --- Dropdown Genre (halaman_admin.php) --- */
  html.dark #genreDropdown {
    background: var(--card, #121820) !important;
    border-color: var(--border-color, rgba(216,184,120,.25)) !important;
    color: var(--text, #eef3f4) !important;
    box-shadow: 0 8px 24px rgba(0,0,0,.5) !important;
  }

  /* --- Image Preview Wrap (halaman_admin.php upload area) --- */
  html.dark .img-preview-wrap {
    background: var(--book-card, #161e27) !important;
    border-color: var(--border-color, rgba(216,184,120,.2)) !important;
  }
  html.dark .img-preview-wrap:hover {
    border-color: var(--accent, #d8b878) !important;
  }
  html.dark .img-preview-wrap .upload-placeholder {
    color: var(--muted, rgba(238,243,244,.55)) !important;
  }

  /* --- Like / Save buttons in detail modal (daftar_buku.php, beranda.php) --- */
  html.dark .detail-btn-like,
  html.dark .detail-btn-save {
    background: rgba(255,255,255,.04) !important;
    border-color: var(--border-color, rgba(216,184,120,.18)) !important;
    color: var(--muted, rgba(238,243,244,.65)) !important;
  }

  /* --- Hapus File Musik (pengaturan_musik.php) --- */
  html.dark .btn-hapus-file {
    background: rgba(220,38,38,.1) !important;
    border: 1.5px solid rgba(220,38,38,.28) !important;
    color: #f87171 !important;
  }
  html.dark .btn-hapus-file:hover {
    background: rgba(220,38,38,.2) !important;
    border-color: rgba(220,38,38,.45) !important;
  }

  /* --- Hover #222 pada tombol (mencegah teks gelap di latar gelap) --- */
  html.dark .btn-submit:hover,
  html.dark .btn-search:hover,
  html.dark .btn-print:hover,
  html.dark .btn-simpan:hover {
    background: linear-gradient(135deg, #c8a060, #b88e48) !important;
    color: #121820 !important;
  }
  html.dark .btn-save:hover {
    opacity: .88 !important;
  }

  /* ── Mode Terang: kembalikan warna natural terang ── */
  html:not(.dark) .btn-cancel,
  html.light .btn-cancel,
  html:not(.dark) .btn-cancel-modal,
  html.light .btn-cancel-modal {
    background: #f0f0f5 !important;
    border-color: #d0d0e0 !important;
    color: #4b5563 !important;
  }
  html:not(.dark) .btn-cancel:hover,
  html.light .btn-cancel:hover,
  html:not(.dark) .btn-cancel-modal:hover,
  html.light .btn-cancel-modal:hover {
    background: #e5e5ed !important;
    color: #221d14 !important;
  }
  html:not(.dark) .modal-close,
  html.light .modal-close {
    background: #f0f0f5 !important;
    color: #4b5563 !important;
  }
  html:not(.dark) .tab-btn:not(.active),
  html.light .tab-btn:not(.active) {
    background: #f0f0f7 !important;
    border-color: #d0d0e4 !important;
    color: #6b7280 !important;
  }
  html:not(.dark) .tab-btn.active,
  html.light .tab-btn.active {
    background: linear-gradient(135deg, #d8b878, #c8a060) !important;
    color: #1a1205 !important;
    border-color: #c8a060 !important;
  }
  html:not(.dark) .status-tab:not(.active),
  html.light .status-tab:not(.active) {
    background: #f0f0f7 !important;
    color: #6b7280 !important;
  }
  html:not(.dark) .icon-btn,
  html.light .icon-btn {
    background: #f0f0f5 !important;
    color: #4b5563 !important;
    border: 1px solid #d0d0e0 !important;
  }
  html:not(.dark) .badge-pending,
  html.light .badge-pending {
    background: #fff3cd !important;
    color: #8a6100 !important;
    border: 1px solid #ffe08a !important;
  }
  html:not(.dark) .badge-approved,
  html.light .badge-approved {
    background: #e8f5e9 !important;
    color: #1a8a4a !important;
    border: 1px solid #a8d5b5 !important;
  }
  html:not(.dark) .badge-rejected,
  html.light .badge-rejected {
    background: #fce4ec !important;
    color: #c0392b !important;
    border: 1px solid #f5a0b0 !important;
  }
  html:not(.dark) .badge-frozen,
  html.light .badge-frozen {
    background: #e0e7ff !important;
    color: #3730a3 !important;
    border: 1px solid #a5b4fc !important;
  }
  html:not(.dark) .btn-action-approve,
  html.light .btn-action-approve {
    background: #e8f5e9 !important;
    color: #1a8a4a !important;
    border-color: #a8d5b5 !important;
  }
  html:not(.dark) .btn-action-reject,
  html.light .btn-action-reject {
    background: #fce4ec !important;
    color: #c0392b !important;
    border-color: #f5a0b0 !important;
  }
  html:not(.dark) .btn-hapus-riwayat,
  html.light .btn-hapus-riwayat {
    background: #fff5f5 !important;
    border-color: #fca5a5 !important;
    color: #dc2626 !important;
  }
  html:not(.dark) .img-preview-wrap,
  html.light .img-preview-wrap {
    background: #f8f9ff !important;
    border-color: #d0d0e0 !important;
  }
  html:not(.dark) .sidebar-toggle,
  html.light .sidebar-toggle {
    background: #ffffff !important;
    border-color: #d0d0e0 !important;
    color: #221d14 !important;
    box-shadow: 0 2px 10px rgba(0,0,0,.15) !important;
  }
  html:not(.dark) .sidebar-toggle svg,
  html.light .sidebar-toggle svg {
    stroke: #221d14 !important;
    color: #221d14 !important;
  }



  /* Hide mobile topbar on desktop */
  .mobile-topbar { display: none; }

  /* ═══════════════════════════════════════════════════════════════════
     ── 7. RESPONSIVE MOBILE SIDEBAR & BURGER MENU (UNIVERSAL) ──
     Memastikan tombol burger selalu terlihat, dapat diklik, dan
     sidebar muncul di atas semua elemen di mobile/tablet (<= 768px).
  ═══════════════════════════════════════════════════════════════════ */
  @media (max-width: 768px) {
    .sidebar-toggle {
      display: flex !important;
      visibility: visible !important;
      opacity: 1 !important;
      position: fixed !important;
      top: 14px !important;
      left: 14px !important;
      z-index: 10005 !important;
      width: 42px !important;
      height: 42px !important;
      border-radius: 12px !important;
      border: 1.5px solid var(--accent, #d8b878) !important;
      background: var(--card, #121820) !important;
      color: var(--accent, #d8b878) !important;
      box-shadow: 0 4px 16px rgba(0,0,0,.55) !important;
      align-items: center !important;
      justify-content: center !important;
      cursor: pointer !important;
      pointer-events: auto !important;
      transition: transform .15s ease, background .2s ease !important;
    }
    .mobile-topbar .sidebar-toggle {
      position: static !important;
      box-shadow: none !important;
    }
    .sidebar-toggle:active {
      transform: scale(0.92) !important;
    }
    .sidebar-toggle svg {
      width: 22px !important;
      height: 22px !important;
      stroke: var(--accent, #d8b878) !important;
      color: var(--accent, #d8b878) !important;
      stroke-width: 2.3px !important;
    }

    .sidebar {
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      bottom: 0 !important;
      width: 220px !important;
      max-width: 80vw !important;
      height: 100vh !important;
      height: 100dvh !important;
      z-index: 10010 !important;
      transform: translateX(-100%) !important;
      transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1), visibility 0.28s, box-shadow 0.28s !important;
      box-shadow: none !important;
      visibility: hidden !important;
      pointer-events: none !important;
    }
    .sidebar.open {
      transform: translateX(0) !important;
      box-shadow: 4px 0 35px rgba(0,0,0,0.7) !important;
      visibility: visible !important;
      pointer-events: auto !important;
    }

    .sidebar-overlay {
      display: block !important;
      position: fixed !important;
      inset: 0 !important;
      background: rgba(9,12,16,.75) !important;
      backdrop-filter: blur(5px) !important;
      -webkit-backdrop-filter: blur(5px) !important;
      z-index: 10008 !important;
      opacity: 0 !important;
      visibility: hidden !important;
      pointer-events: none !important;
      transition: opacity 0.25s ease, visibility 0.25s ease !important;
    }
    .sidebar-overlay.open {
      opacity: 1 !important;
      visibility: visible !important;
      pointer-events: auto !important;
    }

    .main {
      margin-left: 0 !important;
      padding-top: 72px !important;
    }

    /* ── MOBILE TOPBAR (reusable untuk semua halaman) ── */
    .mobile-topbar {
      display: flex !important;
      align-items: center !important;
      gap: 12px !important;
      position: fixed !important;
      top: 0 !important; left: 0 !important; right: 0 !important;
      height: 60px !important;
      padding: 0 14px !important;
      padding-top: env(safe-area-inset-top, 0) !important;
      background: var(--sidebar-bg, #10151b) !important;
      border-bottom: 1px solid var(--border-color, rgba(216,184,120,.15)) !important;
      box-shadow: 0 2px 18px rgba(0,0,0,.35) !important;
      z-index: 10005 !important;
      transition: opacity var(--trans, .2s), visibility var(--trans, .2s) !important;
    }
    body.sidebar-open .mobile-topbar {
      opacity: 0 !important;
      visibility: hidden !important;
      pointer-events: none !important;
    }
    .mobile-topbar .sidebar-toggle {
      position: static !important;
      box-shadow: none !important;
      flex-shrink: 0 !important;
    }
    .mobile-topbar-divider {
      display: block !important;
      width: 1px !important;
      height: 26px !important;
      flex-shrink: 0 !important;
      background: linear-gradient(180deg, transparent, var(--border-color, rgba(216,184,120,.35)) 50%, transparent) !important;
    }
    .mobile-topbar-brand {
      display: flex !important;
      align-items: center !important;
      gap: 7px !important;
      min-width: 0 !important;
      overflow: hidden !important;
    }
    .mobile-topbar-brand svg {
      width: 19px !important; height: 19px !important;
      color: var(--accent, #d8b878) !important;
      flex-shrink: 0 !important;
    }
    .mobile-topbar-brand span {
      font-family: 'Cormorant Garamond', serif !important;
      font-weight: 700 !important;
      font-size: .92rem !important;
      color: var(--accent, #d8b878) !important;
      letter-spacing: .04em !important;
      white-space: nowrap !important;
      overflow: hidden !important;
      text-overflow: ellipsis !important;
    }
    .mobile-topbar-actions {
      margin-left: auto !important;
      display: flex !important;
      align-items: center !important;
      gap: 8px !important;
      flex-shrink: 0 !important;
    }
    .mobile-topbar-actions a,
    .mobile-topbar-actions button {
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      width: 36px !important; height: 36px !important;
      border-radius: 10px !important;
      border: 1px solid var(--border-color, rgba(216,184,120,.18)) !important;
      background: rgba(216,184,120,.08) !important;
      color: var(--accent, #d8b878) !important;
      cursor: pointer !important;
      transition: background .2s !important;
      text-decoration: none !important;
    }
    .mobile-topbar-actions a:hover,
    .mobile-topbar-actions button:hover {
      background: rgba(216,184,120,.18) !important;
    }
    .mobile-topbar-actions svg {
      width: 18px !important; height: 18px !important;
    }
  }
</style>

<script>
/**
 * ── AksaAudio: Pengelola Musik Latar Persisten & Berkelanjutan ──
 * Memastikan musik tidak terputus/mengulang dari 00:00 saat berpindah halaman
 * (Beranda, Dashboard, Daftar Buku, Buku Simpan, Edit Profil).
 * Posisi detik pemutaran dan status play/pause disimpan di localStorage,
 * dan langsung dilanjutkan dari posisi terakhir di halaman tujuan.
 */
window.AksaAudio = (function() {
  var TIME_KEY       = 'aksanova_audio_time';
  var STATUS_KEY     = 'aksanova_audio_status';
  var SRC_KEY        = 'aksanova_audio_src';
  var inited         = false;
  var gestureBound   = false;
  var pausedByHidden = false;

  function getAudio() { return document.getElementById('audioLatar'); }
  function getBtn()   { return document.getElementById('btnMusik'); }

  function getSongKey(src) {
    if (!src) return '';
    try {
      var clean = src.split('?')[0].split('#')[0];
      return clean.substring(clean.lastIndexOf('/') + 1).toLowerCase();
    } catch(e) { return src.toLowerCase(); }
  }

  function saveState() {
    var audio = getAudio();
    if (!audio) return;
    try {
      if (!isNaN(audio.currentTime) && audio.currentTime > 0) {
        localStorage.setItem(TIME_KEY, audio.currentTime.toString());
      }
      localStorage.setItem(STATUS_KEY, audio.paused ? 'paused' : 'playing');
    } catch(e) {}
  }

  function setBtnUI(isPlaying) {
    var btn = getBtn();
    if (!btn) return;
    btn.classList.toggle('playing', isPlaying);
    btn.setAttribute('aria-pressed', isPlaying ? 'true' : 'false');
  }

  function init() {
    if (inited) return;
    var audio = getAudio();
    var btn   = getBtn();
    if (!audio) return;

    inited = true;
    audio.volume = 0.55;

    // Cek sumber audio saat ini
    var srcTag = audio.querySelector('source');
    var currentSrc = srcTag ? srcTag.getAttribute('src') : (audio.src || '');
    var savedSrc = '';
    try { savedSrc = localStorage.getItem(SRC_KEY) || ''; } catch(e) {}

    var savedTime = 0;
    try { savedTime = parseFloat(localStorage.getItem(TIME_KEY) || '0'); } catch(e) {}

    var userPaused = false;
    try { userPaused = (localStorage.getItem(STATUS_KEY) === 'paused'); } catch(e) {}

    // Jika file lagu berubah di pengaturan admin, mulai dari awal
    var curKey   = getSongKey(currentSrc);
    var savedKey = getSongKey(savedSrc);
    if (curKey && savedKey && curKey !== savedKey) {
      savedTime = 0;
      try {
        localStorage.setItem(SRC_KEY, currentSrc);
        localStorage.setItem(TIME_KEY, '0');
      } catch(e) {}
    } else if (currentSrc && !savedSrc) {
      try { localStorage.setItem(SRC_KEY, currentSrc); } catch(e) {}
    }

    // Terapkan posisi detik pemutaran terakhir sedini mungkin
    function applySavedTime() {
      if (savedTime > 0 && isFinite(savedTime)) {
        try {
          if (!audio.duration || savedTime < audio.duration) {
            if (Math.abs(audio.currentTime - savedTime) > 0.3) {
              audio.currentTime = savedTime;
            }
          }
        } catch(e) {}
      }
    }

    applySavedTime();
    audio.addEventListener('loadedmetadata', applySavedTime);
    audio.addEventListener('canplay', function() {
      if (savedTime > 0 && Math.abs(audio.currentTime - savedTime) > 0.5) {
        applySavedTime();
      }
    });

    // Sinkronisasi posisi detik pemutaran secara berkala
    audio.addEventListener('timeupdate', function() {
      if (!audio.paused && audio.currentTime > 0) {
        try {
          localStorage.setItem(TIME_KEY, audio.currentTime.toString());
        } catch(e) {}
      }
    });

    audio.addEventListener('play', function() {
      if (!audio.muted) setBtnUI(true);
    });
    audio.addEventListener('pause', function() {
      if (!pausedByHidden) setBtnUI(false);
    });
    audio.addEventListener('ended', function() {
      try { localStorage.setItem(TIME_KEY, '0'); } catch(e) {}
      if (!userPaused) {
        audio.currentTime = 0;
        var p = audio.play();
        if (p !== undefined) {
          p.then(function() {
            audio.muted = false;
            setBtnUI(true);
          }).catch(function() { setBtnUI(false); });
        }
      }
    });

    // Simpan posisi sebelum halaman ditutup/berpindah
    window.addEventListener('beforeunload', saveState);
    window.addEventListener('pagehide', saveState);

    // Tangani interaksi klik tombol musik
    if (btn && !btn._aksaBound) {
      btn._aksaBound = true;
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (audio.paused || audio.muted) {
          userPaused = false;
          pausedByHidden = false;
          audio.muted = false;
          try { localStorage.setItem(STATUS_KEY, 'playing'); } catch(e) {}
          playAudio();
        } else {
          userPaused = true;
          audio.pause();
          try {
            localStorage.setItem(STATUS_KEY, 'paused');
            saveState();
          } catch(e) {}
          setBtnUI(false);
        }
      });
    }

    function removeGestureListeners() {
      if (!gestureBound) return;
      gestureBound = false;
      ['click', 'keydown', 'touchstart'].forEach(function(ev) {
        document.removeEventListener(ev, onUserGesture);
      });
    }

    var onUserGesture = function(ev) {
      if (ev && ev.target && btn && (ev.target === btn || btn.contains(ev.target))) return;
      if (!userPaused) {
        audio.muted = false;
        applySavedTime();
        var p = audio.play();
        if (p !== undefined) {
          p.then(function() {
            setBtnUI(true);
            try { localStorage.setItem(STATUS_KEY, 'playing'); } catch(e) {}
          }).catch(function() {});
        }
      }
      removeGestureListeners();
    };

    function attachGestureListeners() {
      if (gestureBound) return;
      gestureBound = true;
      ['click', 'keydown', 'touchstart'].forEach(function(ev) {
        document.addEventListener(ev, onUserGesture, { once: false, passive: true });
      });
    }

    function playAudio() {
      applySavedTime();
      var p = audio.play();
      if (p !== undefined) {
        p.then(function() {
          audio.muted = false;
          setBtnUI(true);
          removeGestureListeners();
        }).catch(function() {
          // Autoplay bersuara dicegah browser: putar mode muted agar stream siap, tunggu interaksi pertama
          audio.muted = true;
          audio.play().then(function() {
            setBtnUI(false);
          }).catch(function() {
            setBtnUI(false);
          });
          attachGestureListeners();
        });
      }
    }

    // Tangani tab diminimalkan / pindah tab
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) {
        if (!audio.paused) {
          pausedByHidden = true;
          audio.pause();
          setBtnUI(false);
        }
      } else if (pausedByHidden && !userPaused) {
        pausedByHidden = false;
        playAudio();
      }
    });

    // Jalankan pemutaran jika tidak dipause oleh user
    if (userPaused) {
      audio.pause();
      audio.removeAttribute('autoplay');
      setBtnUI(false);
    } else {
      playAudio();
    }
  }

  return {
    init: init,
    saveState: saveState,
    getAudio: getAudio,
    getBtn: getBtn
  };
})();

/**
 * ── Smooth Sidebar Navigation Handler ──
 * Membuat perpindahan antar halaman via sidebar terasa mulus.
 * Mencegah reload ulang jika sudah di halaman yang sama, memberikan feedback instan,
 * menyimpan posisi audio sebelum berpindah, dan menghaluskan transisi drawer di mobile.
 */
(function() {
  window.toggleSidebar = function(forceState) {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.querySelector('.sidebar-overlay') || document.getElementById('sidebarOverlay');
    if (!sidebar) return;
    var willOpen = (typeof forceState === 'boolean') ? forceState : !sidebar.classList.contains('open');
    if (willOpen) {
      sidebar.classList.add('open');
      if (overlay) overlay.classList.add('open');
      document.body.classList.add('sidebar-open');
    } else {
      sidebar.classList.remove('open');
      if (overlay) overlay.classList.remove('open');
      document.body.classList.remove('sidebar-open');
    }
  };

  function initSidebarToggle() {
    var toggle = document.getElementById('sidebarToggle');
    var overlay = document.querySelector('.sidebar-overlay') || document.getElementById('sidebarOverlay');

    if (toggle) {
      toggle.onclick = null;
      // Gunakan capture phase (true) dan stopImmediatePropagation untuk mencegah double-toggle
      toggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (e.stopImmediatePropagation) e.stopImmediatePropagation();
        window.toggleSidebar();
        return false;
      }, true);
    }

    if (overlay) {
      overlay.onclick = null;
      overlay.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (e.stopImmediatePropagation) e.stopImmediatePropagation();
        window.toggleSidebar(false);
        return false;
      }, true);
    }

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        window.toggleSidebar(false);
      }
    });
  }
  function initSmoothSidebarNav() {
    var sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    var links = sidebar.querySelectorAll('a[href]');
    
    var currentFile = (window.location.pathname.split('/').pop() || 'beranda.php').toLowerCase();
    if (!currentFile || currentFile === '') currentFile = 'beranda.php';

    links.forEach(function(link) {
      link.addEventListener('click', function(e) {
        var rawHref = link.getAttribute('href');
        if (!rawHref || rawHref.startsWith('#') || rawHref.startsWith('javascript:') || link.target === '_blank') {
          return;
        }
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
          return;
        }

        // Simpan posisi lagu tepat detik ini sebelum halaman berpindah
        if (window.AksaAudio && window.AksaAudio.saveState) {
          window.AksaAudio.saveState();
        }

        var targetFile = rawHref.split('?')[0].split('#')[0].toLowerCase();

        // Jika klik menu yang sedang aktif di halaman ini, cegah reload/loncat
        if (targetFile === currentFile && !rawHref.includes('?')) {
          e.preventDefault();
          return;
        }

        // Feedback visual instan
        links.forEach(function(l) { l.classList.remove('active'); });
        link.classList.add('active');

        var isMobile = window.innerWidth <= 768;
        var overlay = document.querySelector('.sidebar-overlay') || document.getElementById('sidebarOverlay');
        var main = document.querySelector('.main');

        if (main && !document.startViewTransition) {
          main.style.transition = 'opacity 0.14s ease, transform 0.14s ease';
          main.style.opacity = '0.4';
          main.style.transform = 'translateY(-3px)';
        }

        if (isMobile && sidebar.classList.contains('open')) {
          e.preventDefault();
          sidebar.classList.remove('open');
          if (overlay) overlay.classList.remove('open');
          document.body.classList.remove('sidebar-open');
          setTimeout(function() {
            window.location.href = rawHref;
          }, 90);
        }
      });
    });
  }

  function startModules() {
    if (window.AksaAudio && window.AksaAudio.init) {
      window.AksaAudio.init();
    }
    initSmoothSidebarNav();
    initSidebarToggle();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startModules);
  } else {
    startModules();
  }
})();
</script>