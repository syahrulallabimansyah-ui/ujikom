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
  ::view-transition-group(app-sidebar) {
    animation-duration: 0s;
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
  var TIME_KEY   = 'aksanova_audio_time';
  var STATUS_KEY = 'aksanova_audio_status';
  var SRC_KEY    = 'aksanova_audio_src';
  var inited     = false;

  function getAudio() { return document.getElementById('audioLatar'); }
  function getBtn()   { return document.getElementById('btnMusik'); }

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
    var audio = getAudio();
    var btn   = getBtn();
    if (!audio) return;

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

    // Jika lagu berubah dari admin, mulai dari awal. Jika lagu sama, lanjutkan waktu terakhir.
    if (currentSrc && currentSrc !== savedSrc) {
      savedTime = 0;
      try {
        localStorage.setItem(SRC_KEY, currentSrc);
        localStorage.setItem(TIME_KEY, '0');
      } catch(e) {}
    }

    // Terapkan posisi detik pemutaran terakhir sedini mungkin
    function applySavedTime() {
      if (savedTime > 0 && isFinite(savedTime)) {
        try {
          if (Math.abs(audio.currentTime - savedTime) > 0.3) {
            audio.currentTime = savedTime;
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

    audio.addEventListener('play',  function() { setBtnUI(true); });
    audio.addEventListener('pause', function() { setBtnUI(false); });
    audio.addEventListener('ended', function() {
      try { localStorage.setItem(TIME_KEY, '0'); } catch(e) {}
    });

    // Simpan posisi sebelum halaman ditutup/berpindah
    window.addEventListener('beforeunload', saveState);
    window.addEventListener('pagehide', saveState);

    // Tangani interaksi klik tombol musik
    if (btn && !btn._aksaBound) {
      btn._aksaBound = true;
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        if (audio.paused) {
          userPaused = false;
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

    function playAudio() {
      applySavedTime();
      var p = audio.play();
      if (p !== undefined) {
        p.then(function() {
          audio.muted = false;
          setBtnUI(true);
        }).catch(function() {
          // Autoplay fallback: jika browser butuh interaksi user pertama
          audio.muted = true;
          audio.play().then(function() {
            setBtnUI(true);
          }).catch(function() {
            setBtnUI(false);
          });
          var userGesture = function() {
            audio.muted = false;
            if (audio.paused && !userPaused) {
              audio.play();
            }
            ['click','touchstart','keydown','scroll'].forEach(function(ev) {
              document.removeEventListener(ev, userGesture);
            });
          };
          ['click','touchstart','keydown','scroll'].forEach(function(ev) {
            document.addEventListener(ev, userGesture, { once: true, passive: true });
          });
        });
      }
    }

    // Jalankan pemutaran jika tidak dipause oleh user
    if (!userPaused) {
      playAudio();
    } else {
      setBtnUI(false);
    }

    inited = true;
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
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startModules);
  } else {
    startModules();
  }
})();
</script>