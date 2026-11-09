<?php
// settings_include.php — Sertakan di <head> semua halaman (sebelum </head>)
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Nunito:wght@300;400;600;700;800&family=Cormorant+Garamond:ital,wght@0,600;0,700;1,400&family=Merriweather:wght@300;400;700&family=Poppins:wght@300;400;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
<script>
(function(){
  // Baca settings dari localStorage
  var s;
  try { s = JSON.parse(localStorage.getItem('aksanova_settings') || '{}'); } catch(e){ s={}; }

  var root = document.documentElement;

  // ── Tema / Mode ──
  // Periksa apakah user punya setting di localStorage, atau sinkron dengan tema index.php
  var indexTheme  = localStorage.getItem('aksanova_theme'); // 'light' atau 'dark'
  // Tema global: aksanova_theme adalah sumber kebenaran tunggal untuk tema aktif (sinkron dengan landing page)
  var mode = (indexTheme === 'light' || indexTheme === 'dark') ? indexTheme : (s.mode || 'dark');
  if (s.mode !== mode) {
    s.mode = mode;
    try { localStorage.setItem('aksanova_settings', JSON.stringify(s)); } catch(e){}
  }
  try { localStorage.setItem('aksanova_theme', mode); } catch(e){}
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

  // Apply background (gradient atau solid) langsung ke root agar tidak pernah glitch putih
  if (colors.bg.startsWith('linear')) {
    root.style.setProperty('--bg', colors.bg);
    root.style.background = colors.bg;
  } else {
    root.style.setProperty('--bg', colors.bg);
    root.style.backgroundColor = colors.bg;
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

  // Animation & Mode Lite
  root.style.setProperty('--trans-speed', animation ? '.28s' : '0s');
  root.style.setProperty('--ease-smooth', 'cubic-bezier(0.22, 1, 0.36, 1)');
  root.style.setProperty('--ease-out', 'cubic-bezier(0.16, 1, 0.3, 1)');
  root.style.setProperty('--trans', '0.28s cubic-bezier(0.22, 1, 0.36, 1)');

  // Class pada html
  var classList = [mode, 'ui-' + uiKey];
  if (mode === 'light') classList.push('theme-light');
  if (!animation) classList.push('mode-lite');
  root.className = classList.join(' ');
})();

/**
 * ── AksaDialog: Modal Konfirmasi & Notifikasi Responsif Universal ──
 * Menggantikan dialog native bawaan peramban (confirm() & alert())
 * dengan antarmuka kustom yang konsisten, modern, responsif, dan adaptif tema.
 */
window.AksaDialog = (function() {
  var overlay = null;
  var currentResolve = null;

  function createElements() {
    if (overlay) return overlay;

    overlay = document.createElement('div');
    overlay.className = 'aksa-dialog-overlay';
    overlay.id = 'aksaDialogOverlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.innerHTML = [
      '<div class="aksa-dialog-card" id="aksaDialogCard">',
      '  <div class="aksa-dialog-icon-wrap" id="aksaDialogIconWrap"></div>',
      '  <div class="aksa-dialog-content">',
      '    <h3 class="aksa-dialog-title" id="aksaDialogTitle"></h3>',
      '    <div class="aksa-dialog-message" id="aksaDialogMessage"></div>',
      '  </div>',
      '  <div class="aksa-dialog-actions" id="aksaDialogActions">',
      '    <button type="button" class="aksa-dialog-btn aksa-dialog-btn-cancel" id="aksaDialogBtnCancel">Batal</button>',
      '    <button type="button" class="aksa-dialog-btn aksa-dialog-btn-confirm" id="aksaDialogBtnConfirm">Lanjutkan</button>',
      '  </div>',
      '</div>'
    ].join('\n');

    document.body.appendChild(overlay);

    var btnCancel = overlay.querySelector('#aksaDialogBtnCancel');
    var btnConfirm = overlay.querySelector('#aksaDialogBtnConfirm');

    btnCancel.addEventListener('click', function() { close(false); });
    btnConfirm.addEventListener('click', function() { close(true); });

    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) close(false);
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && overlay.classList.contains('active')) {
        close(false);
      }
    });

    return overlay;
  }

  var DIALOG_ICONS = {
    trash: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>',
    logout: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
    'user-x': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="18" y1="8" x2="23" y2="13"/><line x1="23" y1="8" x2="18" y2="13"/></svg>',
    unlock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/><circle cx="12" cy="16" r="1.5"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
    denda: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><circle cx="7" cy="15" r="1"/><circle cx="17" cy="15" r="1"/></svg>',
    'bookmark-remove': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
    music: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>',
    warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
  };

  function getIconSvg(type, iconKey, title, message) {
    if (iconKey && typeof iconKey === 'string') {
      if (iconKey.indexOf('<svg') !== -1) return iconKey;
      if (DIALOG_ICONS[iconKey]) return DIALOG_ICONS[iconKey];
    }
    // Deteksi konteks otomatis dari teks agar ikon tepat sasaran dan bukan selalu tong sampah
    var text = ((title || '') + ' ' + (message || '')).toLowerCase();
    if (text.indexOf('keluar') !== -1 || text.indexOf('logout') !== -1) return DIALOG_ICONS.logout;
    if (text.indexOf('cairkan') !== -1 || text.indexOf('buka blokir') !== -1 || text.indexOf('pulihkan') !== -1) return DIALOG_ICONS.unlock;
    if (text.indexOf('nonaktif') !== -1 || text.indexOf('blokir') !== -1) return DIALOG_ICONS['user-x'];
    if (text.indexOf('lunas') !== -1 || text.indexOf('denda') !== -1 || text.indexOf('bayar') !== -1) return DIALOG_ICONS.denda;
    if (text.indexOf('setuju') !== -1 || text.indexOf('persetujuan') !== -1 || text.indexOf('terima') !== -1) return DIALOG_ICONS.check;
    if (text.indexOf('lepas') !== -1 || text.indexOf('simpan') !== -1) return DIALOG_ICONS['bookmark-remove'];
    if (text.indexOf('musik') !== -1 || text.indexOf('audio') !== -1 || text.indexOf('lagu') !== -1) return DIALOG_ICONS.music;
    if (text.indexOf('hapus') !== -1 || text.indexOf('bersihkan') !== -1 || text.indexOf('buang') !== -1) return DIALOG_ICONS.trash;

    // Fallback semantik
    if (type === 'danger') return DIALOG_ICONS.warning;
    if (type === 'warning') return DIALOG_ICONS.warning;
    if (type === 'success') return DIALOG_ICONS.check;
    return DIALOG_ICONS.info;
  }

  function show(options) {
    options = options || {};
    createElements();

    var title = options.title || 'Konfirmasi Tindakan';
    var message = options.message || 'Apakah Anda yakin ingin melanjutkan?';
    var confirmText = options.confirmText || 'Ya, Lanjutkan';
    var cancelText = options.cancelText || 'Batal';
    var type = options.type || (message.toLowerCase().indexOf('hapus') !== -1 ? 'danger' : 'info');
    var isAlert = !!options.isAlert;

    var card = overlay.querySelector('#aksaDialogCard');
    var iconWrap = overlay.querySelector('#aksaDialogIconWrap');
    var titleEl = overlay.querySelector('#aksaDialogTitle');
    var msgEl = overlay.querySelector('#aksaDialogMessage');
    var btnCancel = overlay.querySelector('#aksaDialogBtnCancel');
    var btnConfirm = overlay.querySelector('#aksaDialogBtnConfirm');

    card.className = 'aksa-dialog-card type-' + type;
    iconWrap.className = 'aksa-dialog-icon-wrap ' + type;
    iconWrap.innerHTML = getIconSvg(type, options.icon, title, message);

    titleEl.textContent = title;
    msgEl.innerHTML = message;

    btnConfirm.textContent = confirmText;
    btnConfirm.className = 'aksa-dialog-btn aksa-dialog-btn-confirm ' + type;

    if (isAlert) {
      btnCancel.style.display = 'none';
      btnConfirm.textContent = options.btnText || 'Mengerti';
    } else {
      btnCancel.style.display = 'inline-flex';
      btnCancel.textContent = cancelText;
    }

    overlay.classList.add('active');
    document.body.classList.add('aksa-dialog-open');

    setTimeout(function() {
      if (type === 'danger' && !isAlert) {
        btnCancel.focus();
      } else {
        btnConfirm.focus();
      }
    }, 50);

    return new Promise(function(resolve) {
      currentResolve = resolve;
    });
  }

  function close(result) {
    if (!overlay) return;
    overlay.classList.remove('active');
    document.body.classList.remove('aksa-dialog-open');
    if (typeof currentResolve === 'function') {
      var res = currentResolve;
      currentResolve = null;
      res(result);
    }
  }

  return {
    confirm: function(options) {
      if (typeof options === 'string') {
        options = { message: options };
      }
      return show(options);
    },
    alert: function(options) {
      if (typeof options === 'string') {
        options = { message: options };
      }
      options = options || {};
      options.isAlert = true;
      return show(options);
    }
  };
})();

window.AksaConfirm = window.AksaDialog.confirm;
window.AksaAlert   = window.AksaDialog.alert;

// Fungsi global & capture delegator untuk tombol Keluar / Logout di seluruh halaman (Admin & Anggota)
// Menjamin pengguna TIDAK AKAN keluar sebelum mengklik tombol "Ya, Keluar".
window._aksaLogoutDialogActive = false;
window.eksekusiLogoutAksa = function(rawHref) {
  if (window.AksaAudio && typeof window.AksaAudio.saveState === 'function') {
    try { window.AksaAudio.saveState(); } catch (err) {}
  }
  var baseUrl = (rawHref || 'logout.php').split('?')[0].split('#')[0] || 'logout.php';
  var form = document.createElement('form');
  form.method = 'POST';
  form.action = baseUrl;
  form.style.display = 'none';
  var inp = document.createElement('input');
  inp.type = 'hidden';
  inp.name = 'confirm_logout';
  inp.value = '1';
  form.appendChild(inp);
  document.body.appendChild(form);
  form.submit();
};

window.konfirmasiLogoutAksa = function(e, btnEl) {
  if (e) {
    e.preventDefault();
    e.stopPropagation();
    if (typeof e.stopImmediatePropagation === 'function') {
      e.stopImmediatePropagation();
    }
  }
  if (window._aksaLogoutDialogActive) {
    return false;
  }
  var href = (btnEl && btnEl.getAttribute) ? (btnEl.getAttribute('href') || 'logout.php') : 'logout.php';

  if (document.body && typeof window.AksaConfirm === 'function') {
    window._aksaLogoutDialogActive = true;
    window.AksaConfirm({
      title: 'Konfirmasi Logout',
      message: 'Apakah Anda yakin ingin keluar (logout) dari sistem AKSA NOVA?',
      confirmText: 'Ya, Keluar',
      cancelText: 'Tidak, Batal',
      type: 'danger',
      icon: 'logout'
    }).then(function(confirmed) {
      window._aksaLogoutDialogActive = false;
      if (confirmed === true) {
        window.eksekusiLogoutAksa(href);
      }
    });
  } else {
    if (window.confirm('Apakah Anda yakin ingin keluar (logout) dari sistem AKSA NOVA?')) {
      window.eksekusiLogoutAksa(href);
    }
  }
  return false;
};

document.addEventListener('click', function(e) {
  var targetEl = (e.target && e.target.nodeType === 3) ? e.target.parentElement : e.target;
  var logoutBtn = (targetEl && targetEl.closest)
    ? targetEl.closest('.sidebar-btn-logout, a[href="logout.php"], a[href$="/logout.php"], a[href*="logout.php"]')
    : null;
  if (logoutBtn && !logoutBtn.dataset.noConfirm) {
    window.konfirmasiLogoutAksa(e, logoutBtn);
  }
}, true);

</script>
<style>
  /* ── Mode Lite (Saat Animasi & Transisi Dimatikan) — Performa Ringan & Bebas Glitch ── */
  html.mode-lite,
  html.mode-lite body {
    view-transition-name: none !important;
    animation: none !important;
    background-color: var(--bg, #090c10) !important;
  }
  html.mode-lite *,
  html.mode-lite *::before,
  html.mode-lite *::after {
    animation-duration: 0.001ms !important;
    animation-iteration-count: 1 !important;
    animation-delay: 0s !important;
    transition-duration: 0s !important;
    transition-delay: 0s !important;
    scroll-behavior: auto !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
  }
  html.mode-lite ::view-transition,
  html.mode-lite ::view-transition-group(*),
  html.mode-lite ::view-transition-image-pair(*),
  html.mode-lite ::view-transition-old(*),
  html.mode-lite ::view-transition-new(*) {
    display: none !important;
    animation: none !important;
    animation-duration: 0s !important;
  }
  html.mode-lite .main,
  html.mode-lite body {
    animation: none !important;
  }

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
  /* Cegah glitch putih saat memuat halaman / navigasi */
  html, body {
    background-color: var(--bg, #090c10) !important;
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

  /* ── Batasi panjang sinopsis ── */
  .detail-sinopsis.sinopsis-collapsed {
    display: -webkit-box;
    -webkit-line-clamp: 5;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .sinopsis-toggle-btn {
    display: inline-block;
    margin-top: 6px;
    background: none;
    border: none;
    color: var(--accent, #d8b878);
    font-size: .78rem;
    font-weight: 700;
    cursor: pointer;
    padding: 0;
    text-decoration: underline dotted;
    letter-spacing: .02em;
  }
  .sinopsis-toggle-btn:hover { opacity: .8; }
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

  /* ── Variabel & Transisi Halus Global (Silky Smooth Motion Suite) ── */
  :root {
    --ease-out:         cubic-bezier(0.16, 1, 0.3, 1);
    --ease-smooth:      cubic-bezier(0.22, 1, 0.36, 1);
    --ease-spring:      cubic-bezier(0.34, 1.25, 0.64, 1);
    --ease-in-out:      cubic-bezier(0.4, 0, 0.2, 1);
    --trans-fast:       0.18s cubic-bezier(0.22, 1, 0.36, 1);
    --trans-normal:     0.28s cubic-bezier(0.22, 1, 0.36, 1);
    --trans-slow:       0.42s cubic-bezier(0.16, 1, 0.3, 1);
    --trans-bounce:     0.36s cubic-bezier(0.34, 1.25, 0.64, 1);
  }

  /* Smooth Scrolling */
  html {
    scroll-behavior: smooth;
  }

  /* Transisi warna & background yang luwes tanpa kedipan */
  body, .sidebar, .section-card, .stats-card, .admin-card, .book-card, .nav-item {
    transition: background var(--trans-normal), color var(--trans-normal), border-color var(--trans-normal) !important;
  }

  /* ── Stabilitas Tata Letak & Scrollbar (Cegah Loncat Antar Halaman) ── */
  html {
    scrollbar-gutter: stable;
    overflow-y: scroll;
  }

  /* ── Desain Scrollbar Global Konsisten di Seluruh Halaman ── */
  html, body, * {
    scrollbar-width: thin !important;
    scrollbar-color: rgba(255, 255, 255, 0.22) transparent !important;
  }
  html.theme-light, html.theme-light body, html.theme-light * {
    scrollbar-color: rgba(0, 0, 0, 0.22) transparent !important;
  }
  ::-webkit-scrollbar {
    width: 6px !important;
    height: 6px !important;
  }
  ::-webkit-scrollbar-track {
    background: transparent !important;
  }
  ::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.22) !important;
    border-radius: 10px !important;
    transition: background .25s ease !important;
  }
  ::-webkit-scrollbar-thumb:hover {
    background: var(--accent, #d8b878) !important;
  }
  html.theme-light ::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.22) !important;
  }
  html.theme-light ::-webkit-scrollbar-thumb:hover {
    background: var(--accent, #b8860b) !important;
  }
  ::-webkit-scrollbar-corner {
    background: transparent !important;
  }

  /* ── Native Cross-Document View Transitions (Chromium 126+) ── */
  @view-transition {
    navigation: auto;
  }
  html.mode-lite {
    view-transition-name: none !important;
  }
  html.mode-lite,
  html.mode-lite * {
    view-transition-name: none !important;
  }
  html:not(.mode-lite) .sidebar {
    view-transition-name: app-sidebar;
  }
  html:not(.mode-lite) .main {
    view-transition-name: app-main;
  }
  ::view-transition-group(app-sidebar),
  ::view-transition-old(app-sidebar),
  ::view-transition-new(app-sidebar) {
    animation: none !important;
    animation-duration: 0s !important;
  }
  ::view-transition-old(app-main) {
    animation: 0.18s cubic-bezier(0.4, 0, 1, 1) both pageFadeOut;
  }
  ::view-transition-new(app-main) {
    animation: 0.32s cubic-bezier(0.16, 1, 0.3, 1) both pageFadeIn;
  }
  @keyframes pageFadeOut {
    from { opacity: 1; transform: translateY(0) scale(1); }
    to   { opacity: 0; transform: translateY(-8px) scale(0.995); }
  }
  @keyframes pageFadeIn {
    from { opacity: 0; transform: translateY(12px) scale(0.995); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
  }

  /* ── Transisi konten (.main) saat halaman dimuat ── */
  body {
    animation: none !important;
  }
  @media (prefers-reduced-motion: no-preference) {
    .main {
      animation: pageFadeIn 0.32s cubic-bezier(0.16, 1, 0.3, 1) both;
    }
  }

  /* ── Perhalus Interaksi Kartu (Cards) di Seluruh Halaman ── */
  .book-card,
  .book-card-item,
  .shelf-cover,
  .spotlight-card,
  .section-card,
  .stats-card,
  .admin-card {
    will-change: transform, box-shadow;
    transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1),
                box-shadow 0.28s cubic-bezier(0.22, 1, 0.36, 1),
                border-color 0.24s ease,
                background-color 0.24s ease !important;
  }
  .book-card:hover,
  .book-card-item:hover,
  .shelf-cover:hover {
    transform: translateY(-3.5px) !important;
  }
  .book-card:active,
  .book-card-item:active,
  .shelf-cover:active {
    transform: translateY(-1px) scale(0.988) !important;
    transition-duration: 0.1s !important;
  }

  /* ── Perhalus Tombol & Elemen Interaktif (Buttons & Controls) ── */
  .btn,
  .btn-primary,
  .btn-secondary,
  .btn-submit,
  .btn-add,
  .btn-search,
  .tab-btn,
  .filter-btn,
  .btn-detail,
  .btn-mode,
  .btn-musik {
    will-change: transform;
    transition: transform 0.24s cubic-bezier(0.22, 1, 0.36, 1),
                box-shadow 0.24s cubic-bezier(0.22, 1, 0.36, 1),
                background 0.2s ease,
                border-color 0.2s ease,
                color 0.2s ease !important;
  }
  html.theme-light .btn-musik, html.light .btn-musik {
    background: rgba(255,255,255,.9) !important;
    border-color: rgba(138,99,35,.35) !important;
    color: #8a6323 !important;
    box-shadow: 0 4px 16px rgba(138,99,35,.15) !important;
  }
  html.theme-light .btn-musik:hover, html.light .btn-musik:hover {
    border-color: #8a6323 !important;
    box-shadow: 0 6px 20px rgba(138,99,35,.25) !important;
  }
  html.theme-light .btn-musik.playing, html.light .btn-musik.playing {
    background: rgba(138,99,35,.15) !important;
    box-shadow: 0 0 16px rgba(138,99,35,.25) !important;
  }
  html.theme-light .btn-musik .icon-eq span, html.light .btn-musik .icon-eq span {
    background: #8a6323 !important;
  }
  .btn:hover,
  .btn-primary:hover,
  .btn-secondary:hover,
  .btn-submit:hover,
  .btn-add:hover,
  .btn-search:hover,
  .tab-btn:hover,
  .filter-btn:hover {
    transform: translateY(-1.5px);
  }
  .btn:active,
  .btn-primary:active,
  .btn-secondary:active,
  .btn-submit:active,
  .btn-add:active,
  .btn-search:active,
  .tab-btn:active,
  .filter-btn:active {
    transform: scale(0.975) !important;
    transition-duration: 0.1s !important;
  }

  /* ── Perhalus Modal & Dialog di Seluruh Halaman ── */
  .modal-overlay,
  .overlay,
  .detail-modal-overlay,
  .sidebar-overlay,
  .modal-backdrop {
    transition: opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1),
                backdrop-filter 0.32s ease !important;
  }
  .modal,
  .detail-modal,
  .modal-box,
  .modal-content,
  .modal-profil {
    animation-duration: 0.34s !important;
    animation-timing-function: cubic-bezier(0.16, 1, 0.3, 1) !important;
    will-change: transform, opacity;
  }
  @keyframes modalIn {
    from {
      opacity: 0;
      transform: scale(0.97) translateY(10px);
    }
    to {
      opacity: 1;
      transform: scale(1) translateY(0);
    }
  }
  @keyframes modalSlideUp {
    from {
      opacity: 0;
      transform: translateY(24px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* ── Perhalus Input, Select & Form Focus ── */
  input,
  select,
  textarea {
    transition: border-color 0.24s cubic-bezier(0.22, 1, 0.36, 1),
                box-shadow 0.24s cubic-bezier(0.22, 1, 0.36, 1),
                background-color 0.2s ease !important;
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
    transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1), border-color 0.22s ease, box-shadow 0.25s ease, background-color 0.22s ease !important;
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
    transition: stroke .2s ease, transform 0.25s cubic-bezier(0.22, 1, 0.36, 1) !important;
  }
  .sidebar-toggle:hover {
    background: rgba(216, 184, 120, 0.18) !important;
    border-color: var(--accent2, #f0d9a8) !important;
    transform: scale(1.04) !important;
  }
  .sidebar-toggle:hover svg {
    stroke: var(--accent2, #f0d9a8) !important;
    color: var(--accent2, #f0d9a8) !important;
  }
  .sidebar-toggle:active {
    transform: scale(0.96) !important;
    transition-duration: 0.1s !important;
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
    gap: 9px !important;
    padding: 7.5px 12px !important;
    border-radius: 8px !important;
    border: 1px solid rgba(216, 184, 120, 0.16) !important;
    background: rgba(255, 255, 255, 0.05) !important;
    color: var(--text, #eef3f4) !important;
    font-size: .82rem !important;
    font-weight: 700 !important;
    cursor: pointer !important;
    margin-bottom: 4px !important;
    transition: border-color .2s ease, box-shadow .2s ease !important;
    text-align: left !important;
    text-decoration: none !important;
    flex-shrink: 0 !important;
    box-sizing: border-box !important;
    font-family: var(--font-family, 'Outfit', sans-serif) !important;
    letter-spacing: normal !important;
    line-height: 1.25 !important;
  }
  .sidebar .sidebar-btn svg {
    width: 16px !important;
    height: 16px !important;
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
    transform: none !important;
  }
  .sidebar .sidebar-btn:hover svg {
    stroke: #ffffff !important;
    color: #ffffff !important;
    transform: none !important;
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
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
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
    transform: none !important;
  }
  .sidebar .nav-item:hover svg {
    transform: none !important;
  }
  .sidebar .nav-item.active {
    background: rgba(216, 184, 120, 0.22) !important;
    color: var(--accent2, #f0d9a8) !important;
    font-weight: 600 !important;
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
    font-weight: 600 !important;
  }
  html:not(.dark) .sidebar .nav-item.active svg,
  html.light .sidebar .nav-item.active svg {
    color: #5c4100 !important;
    stroke: #5c4100 !important;
  }

  /* ── 4. Label Nama & Badge Sidebar ── */
  .sidebar .admin-name-label {
    color: var(--text, #eef3f4) !important;
    font-size: .88rem !important;
    font-weight: 700 !important;
    margin-bottom: 6px !important;
    text-align: center !important;
    line-height: 1.2 !important;
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
    margin-bottom: 10px !important;
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
    width: 72px !important;
    height: 72px !important;
    max-width: 72px !important;
    max-height: 72px !important;
    margin: 0 auto 6px auto !important;
    cursor: pointer !important;
    display: block !important;
    flex-shrink: 0 !important;
  }
  .sidebar .avatar-circle {
    width: 72px !important;
    height: 72px !important;
    max-width: 72px !important;
    max-height: 72px !important;
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
    width: var(--sidebar-w, 220px) !important;
    box-sizing: border-box !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
  }
  /* Admin Sidebar */
  .sidebar:has(.sidebar-header),
  .sidebar:has(.sidebar-nav) {
    padding: 14px 14px 10px !important;
    align-items: center !important;
  }
  /* User Sidebar (Beranda, Dashboard User, Daftar Buku, Ajukan Pinjam, Buku Simpan, Edit Profil) */
  .sidebar:has(.logo-wrap),
  .sidebar:has(.nav) {
    padding: 24px 0 20px !important;
    align-items: stretch !important;
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
    scrollbar-width: thin !important;
    scrollbar-color: rgba(216, 184, 120, 0.35) transparent !important;
  }
  /* Sama untuk halaman user yang pakai .nav (bukan .sidebar-nav) */
  .sidebar .nav {
    width: 100% !important;
    flex: 1 1 auto !important;
    min-height: 0 !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 2px !important;
    padding: 16px 10px !important;
    box-sizing: border-box !important;
    scrollbar-width: thin !important;
    scrollbar-color: rgba(216, 184, 120, 0.35) transparent !important;
  }
  .sidebar .nav-bottom {
    width: 100% !important;
    flex-shrink: 0 !important;
    box-sizing: border-box !important;
    padding: 10px 10px 0 !important;
    border-top: 1px solid var(--border-color, rgba(216,184,120,.18)) !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 2px !important;
  }
  .sidebar .nav .nav-item,
  .sidebar .nav-bottom .nav-item {
    width: 100% !important;
    box-sizing: border-box !important;
    font-family: var(--font-family, 'Outfit', sans-serif) !important;
    font-size: .82rem !important;
    font-weight: 600 !important;
    letter-spacing: normal !important;
    line-height: 1.3 !important;
    padding: 11px 14px !important;
    border-radius: 10px !important;
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
  }
  .sidebar .nav .nav-item svg,
  .sidebar .nav-bottom .nav-item svg {
    width: 17px !important;
    height: 17px !important;
    flex-shrink: 0 !important;
  }
  /* Logo wrap untuk halaman user */
  .sidebar .logo-wrap {
    width: 100% !important;
    flex-shrink: 0 !important;
    box-sizing: border-box !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    padding: 0 18px 24px !important;
    border-bottom: 1px solid var(--border-color, rgba(216,184,120,.15)) !important;
  }
  .sidebar::-webkit-scrollbar,
  .sidebar-nav::-webkit-scrollbar {
    width: 4px !important;
    height: 4px !important;
  }
  .sidebar::-webkit-scrollbar-track,
  .sidebar-nav::-webkit-scrollbar-track {
    background: transparent !important;
  }
  .sidebar::-webkit-scrollbar-thumb,
  .sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(216, 184, 120, 0.3) !important;
    border-radius: 10px !important;
  }
  .sidebar::-webkit-scrollbar-thumb:hover,
  .sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: var(--accent, #d8b878) !important;
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

  /* --- Tombol Utama Admin (.btn-add, .btn-save form admin, .btn-submit) --- */
  html:not(.dark) .btn-add,
  html.light .btn-add,
  html:not(.dark) .modal-box .btn-save,
  html.light .modal-box .btn-save,
  html:not(.dark) .modal-footer .btn-save,
  html.light .modal-footer .btn-save,
  html:not(.dark) .card .btn-save,
  html.light .card .btn-save,
  html:not(.dark) form .btn-save:not(.bk-actions .btn-save),
  html.light form .btn-save:not(.bk-actions .btn-save),
  html:not(.dark) .btn-submit,
  html.light .btn-submit {
    background: linear-gradient(135deg, #d8b878, #c8a060) !important;
    color: #121820 !important;
    font-weight: 800 !important;
    border: 1px solid rgba(180, 140, 60, 0.4) !important;
    box-shadow: 0 3px 12px rgba(180, 140, 60, 0.28) !important;
  }
  html:not(.dark) .btn-add svg,
  html.light .btn-add svg,
  html:not(.dark) .modal-box .btn-save svg,
  html.light .modal-box .btn-save svg,
  html:not(.dark) .modal-footer .btn-save svg,
  html.light .modal-footer .btn-save svg,
  html:not(.dark) .card .btn-save svg,
  html.light .card .btn-save svg,
  html:not(.dark) form .btn-save:not(.bk-actions .btn-save) svg,
  html.light form .btn-save:not(.bk-actions .btn-save) svg,
  html:not(.dark) .btn-submit svg,
  html.light .btn-submit svg {
    stroke: #121820 !important;
    color: #121820 !important;
  }
  html:not(.dark) .btn-add:hover,
  html.light .btn-add:hover,
  html:not(.dark) .modal-box .btn-save:hover,
  html.light .modal-box .btn-save:hover,
  html:not(.dark) .modal-footer .btn-save:hover,
  html.light .modal-footer .btn-save:hover,
  html:not(.dark) .card .btn-save:hover,
  html.light .card .btn-save:hover,
  html:not(.dark) form .btn-save:not(.bk-actions .btn-save):hover,
  html.light form .btn-save:not(.bk-actions .btn-save):hover,
  html:not(.dark) .btn-submit:hover,
  html.light .btn-submit:hover {
    background: linear-gradient(135deg, #e4c585, #d2aa6b) !important;
    box-shadow: 0 5px 18px rgba(180, 140, 60, 0.42) !important;
    color: #121820 !important;
  }

  /* --- Feedback Klik Responsif (Tactile Active) untuk Semua Tombol --- */
  .btn-add:active,
  .btn-save:active,
  .btn-submit:active,
  .btn-search:active,
  .btn-detail:active,
  .btn-hapus:active,
  .btn-print:active,
  .btn-whatsapp:active,
  .btn-excel:active,
  .icon-btn:active,
  .act-btn:active,
  .tab-btn:active,
  .status-btn:active,
  .sidebar-btn:active {
    transform: scale(0.96) !important;
    transition: transform 0.08s ease !important;
  }

  /* --- Icon Buttons (kelola_banner.php) Light & Dark --- */
  html:not(.dark) .icon-btn,
  html.light .icon-btn {
    background: #ffffff !important;
    border: 1px solid rgba(180, 140, 60, 0.25) !important;
    color: #4a3b22 !important;
    box-shadow: 0 1px 4px rgba(0,0,0,.04) !important;
  }
  html:not(.dark) .icon-btn:hover,
  html.light .icon-btn:hover {
    background: rgba(216, 184, 120, 0.25) !important;
    border-color: #8a6100 !important;
    color: #000000 !important;
  }
  html:not(.dark) .icon-btn.danger,
  html.light .icon-btn.danger {
    color: #dc2626 !important;
    border-color: rgba(220, 38, 38, 0.3) !important;
  }
  html:not(.dark) .icon-btn.danger:hover,
  html.light .icon-btn.danger:hover {
    background: #dc2626 !important;
    color: #ffffff !important;
    box-shadow: 0 2px 8px rgba(220, 38, 38, 0.35) !important;
  }
  html:not(.dark) .icon-btn.grow,
  html.light .icon-btn.grow {
    background: rgba(180, 140, 60, 0.12) !important;
    border-color: rgba(180, 140, 60, 0.32) !important;
    color: #5a3e04 !important;
  }
  html:not(.dark) .icon-btn.grow:hover,
  html.light .icon-btn.grow:hover {
    background: linear-gradient(135deg, #d8b878, #c8a060) !important;
    border-color: #d8b878 !important;
    color: #121820 !important;
  }
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
      transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.35s, box-shadow 0.35s !important;
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
      transition: opacity 0.32s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.32s !important;
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

  /* ═══════════════════════════════════════════════════════════════════
     ── 8. SUITE KONTRAS PENUH MODE TERANG & GELAP (ANTI-INVISIBLE UI) ──
     Menjamin seluruh teks, badge, input, tombol, dan ikon terbaca jelas
     (WCAG AA >= 4.5:1) baik di Mode Terang maupun Mode Gelap.
  ═══════════════════════════════════════════════════════════════════ */

  /* Konsistensi penuh tombol & badge sidebar Admin di seluruh 10 halaman */
  .sidebar,
  aside.sidebar {
    padding-left: 12px !important;
    padding-right: 12px !important;
  }
  .sidebar-nav .sidebar-btn,
  .sidebar .sidebar-btn {
    padding: 7.5px 10px !important;
    gap: 7px !important;
    font-size: 0.81rem !important;
    white-space: nowrap !important;
    box-sizing: border-box !important;
  }

  /* Geometri & warna seragam untuk badge angka Pengajuan Pinjam (.aksa-notif-badge / .badge-nav) */
  .sidebar .aksa-notif-badge,
  .sidebar .badge-nav {
    margin-left: auto !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-width: 22px !important;
    height: 20px !important;
    padding: 0 6px !important;
    border-radius: 999px !important;
    font-size: 0.68rem !important;
    font-weight: 800 !important;
    line-height: 1 !important;
    flex-shrink: 0 !important;
    box-sizing: border-box !important;
    text-align: center !important;
    font-variant-numeric: tabular-nums !important;
    background: rgba(245, 158, 11, 0.2) !important;
    color: #fbbf24 !important;
    border: 1px solid rgba(245, 158, 11, 0.45) !important;
    box-shadow: none !important;
  }
  /* Mode Terang (saat tombol sidebar tidak aktif) */
  html:not(.dark) .sidebar .aksa-notif-badge,
  html.light .sidebar .aksa-notif-badge,
  html.theme-light .sidebar .aksa-notif-badge,
  html:not(.dark) .sidebar .badge-nav,
  html.light .sidebar .badge-nav,
  html.theme-light .sidebar .badge-nav {
    background: #fef3c7 !important;
    color: #92400e !important;
    border: 1px solid #f59e0b !important;
  }
  /* Saat tombol sidebar sedang aktif (latar emas), bentuk pill tetap identik & kontras tinggi */
  .sidebar .sidebar-btn.active .aksa-notif-badge,
  .sidebar .sidebar-btn.active .badge-nav,
  html:not(.dark) .sidebar .sidebar-btn.active .aksa-notif-badge,
  html.light .sidebar .sidebar-btn.active .aksa-notif-badge,
  html.theme-light .sidebar .sidebar-btn.active .aksa-notif-badge,
  html:not(.dark) .sidebar .sidebar-btn.active .badge-nav,
  html.light .sidebar .sidebar-btn.active .badge-nav,
  html.theme-light .sidebar .sidebar-btn.active .badge-nav {
    margin-left: auto !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-width: 22px !important;
    height: 20px !important;
    padding: 0 6px !important;
    border-radius: 999px !important;
    font-size: 0.68rem !important;
    font-weight: 800 !important;
    line-height: 1 !important;
    flex-shrink: 0 !important;
    box-sizing: border-box !important;
    text-align: center !important;
    font-variant-numeric: tabular-nums !important;
    background: #121820 !important;
    color: #fde68a !important;
    border: 1px solid rgba(18, 24, 32, 0.45) !important;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.25) !important;
  }
  /* Geometri pill seragam juga untuk badge pendaftaran anggota di sidebar */
  .sidebar-nav .sidebar-btn > span[style*="#e74c3c"] {
    margin-left: auto !important;
    display: inline-flex;
    align-items: center !important;
    justify-content: center !important;
    min-width: 22px !important;
    height: 20px !important;
    padding: 0 6px !important;
    border-radius: 999px !important;
    font-size: 0.68rem !important;
    font-weight: 800 !important;
    line-height: 1 !important;
    flex-shrink: 0 !important;
    box-sizing: border-box !important;
    font-variant-numeric: tabular-nums !important;
  }

  /* Avatar Admin di Sidebar saat Mode Terang */
  html:not(.dark) .sidebar .avatar-circle,
  html.light .sidebar .avatar-circle {
    background: #f8f3e8 !important;
    border-color: rgba(180, 130, 30, 0.4) !important;
    box-shadow: 0 4px 12px rgba(138, 97, 0, 0.14) !important;
  }
  html:not(.dark) .sidebar .avatar-circle svg,
  html.light .sidebar .avatar-circle svg,
  html:not(.dark) .sidebar .avatar-circle .default-icon,
  html.light .sidebar .avatar-circle .default-icon {
    color: #8a6100 !important;
  }

  /* Tombol Mode Terang/Gelap HUD & Topbar Mobile di Mode Terang */
  html:not(.dark) .btn-topbar-mode,
  html.light .btn-topbar-mode,
  html.theme-light .btn-topbar-mode {
    background: rgba(255, 255, 255, 0.94) !important;
    border-color: rgba(138, 99, 35, 0.38) !important;
    color: #8a6100 !important;
    box-shadow: 0 4px 14px rgba(138, 99, 35, 0.15) !important;
  }
  html:not(.dark) .btn-topbar-mode:hover,
  html.light .btn-topbar-mode:hover {
    border-color: #8a6100 !important;
    background: #fffdf7 !important;
  }
  @media (max-width: 768px) {
    html:not(.dark) .sidebar-toggle,
    html.light .sidebar-toggle {
      background: #ffffff !important;
      border-color: rgba(180, 130, 30, 0.45) !important;
      color: #8a6100 !important;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
    }
    html:not(.dark) .sidebar-toggle svg,
    html.light .sidebar-toggle svg {
      stroke: #8a6100 !important;
      color: #8a6100 !important;
    }
    html:not(.dark) .mobile-topbar-brand svg,
    html.light .mobile-topbar-brand svg,
    html:not(.dark) .mobile-topbar-brand span,
    html.light .mobile-topbar-brand span {
      color: #8a6100 !important;
    }
    html:not(.dark) .mobile-topbar-actions a,
    html.light .mobile-topbar-actions a,
    html:not(.dark) .mobile-topbar-actions button,
    html.light .mobile-topbar-actions button,
    html:not(.dark) .mobile-btn-back,
    html.light .mobile-btn-back {
      background: rgba(184, 134, 11, 0.1) !important;
      border-color: rgba(184, 134, 11, 0.3) !important;
      color: #8a6100 !important;
    }
  }

  /* Helper teks merah adaptif (terlambat / denda / error) */
  .text-danger-adaptive { color: #f87171 !important; }
  html:not(.dark) .text-danger-adaptive,
  html.light .text-danger-adaptive,
  html.theme-light .text-danger-adaptive { color: #dc2626 !important; }

  /* Badge & Chip Status di Seluruh Halaman (Mode Terang) */
  html:not(.dark) .badge-menunggu,
  html.light .badge-menunggu,
  html:not(.dark) .chip-belum,
  html.light .chip-belum,
  html:not(.dark) .sisa-chip.soon,
  html.light .sisa-chip.soon {
    background: #fef3c7 !important;
    color: #92400e !important;
    border: 1px solid #f59e0b !important;
  }
  html:not(.dark) .badge-disetujui,
  html.light .badge-disetujui,
  html:not(.dark) .chip-lunas,
  html.light .chip-lunas,
  html:not(.dark) .loan-status-badge.ontime,
  html.light .loan-status-badge.ontime,
  html:not(.dark) .hero-badge,
  html.light .hero-badge,
  html:not(.dark) .book-chip.stock-ok,
  html.light .book-chip.stock-ok,
  html:not(.dark) .book-picker-stock,
  html.light .book-picker-stock,
  html:not(.dark) .returned-label,
  html.light .returned-label,
  html:not(.dark) .sisa-chip.safe,
  html.light .sisa-chip.safe,
  html:not(.dark) .success-icon,
  html.light .success-icon {
    background: #dcfce7 !important;
    color: #166534 !important;
    border-color: #86efac !important;
  }
  html:not(.dark) .badge-ditolak,
  html.light .badge-ditolak,
  html:not(.dark) .loan-status-badge.late,
  html.light .loan-status-badge.late,
  html:not(.dark) .book-chip.stock-low,
  html.light .book-chip.stock-low,
  html:not(.dark) .bk-stock.stok-habis,
  html.light .bk-stock.stok-habis,
  html:not(.dark) .sisa-chip.late,
  html.light .sisa-chip.late {
    background: #fee2e2 !important;
    color: #b91c1c !important;
    border-color: #fca5a5 !important;
  }
  html:not(.dark) .badge-pickup,
  html.light .badge-pickup,
  html:not(.dark) .badge-alumni,
  html.light .badge-alumni {
    background: #dbeafe !important;
    color: #1e40af !important;
    border: 1px solid #93c5fd !important;
  }
  html:not(.dark) .chip-tidak,
  html.light .chip-tidak {
    background: #f1f5f9 !important;
    color: #475569 !important;
    border: 1px solid #cbd5e1 !important;
  }

  /* Ikon kartu statistik & titik riwayat di Mode Terang */
  html:not(.dark) .stat-icon.gold,
  html.light .stat-icon.gold {
    background: #fef3c7 !important;
    border-color: #f59e0b !important;
    color: #92400e !important;
  }
  html:not(.dark) .stat-icon.green,
  html.light .stat-icon.green {
    background: #dcfce7 !important;
    border-color: #86efac !important;
    color: #15803d !important;
  }
  html:not(.dark) .stat-icon.red,
  html.light .stat-icon.red {
    background: #fee2e2 !important;
    border-color: #fca5a5 !important;
    color: #b91c1c !important;
  }
  html:not(.dark) .history-dot,
  html.light .history-dot {
    background: #16a34a !important;
  }

  /* Alert & Notice di Mode Terang */
  html:not(.dark) .alert-success,
  html.light .alert-success,
  html:not(.dark) .notice-success,
  html.light .notice-success,
  html:not(.dark) .profil-alert-ok,
  html.light .profil-alert-ok {
    background: #ecfdf5 !important;
    border-color: #6ee7b7 !important;
    color: #065f46 !important;
  }
  html:not(.dark) .alert-error,
  html.light .alert-error,
  html:not(.dark) .notice-error,
  html.light .notice-error,
  html:not(.dark) .profil-alert-err,
  html.light .profil-alert-err {
    background: #fef2f2 !important;
    border-color: #fca5a5 !important;
    color: #991b1b !important;
  }
  html:not(.dark) .alert-warning,
  html.light .alert-warning {
    background: #fffbeb !important;
    border-color: #fcd34d !important;
    color: #92400e !important;
  }
  html:not(.dark) .alert-info,
  html.light .alert-info {
    background: #fffbeb !important;
    border-color: #fcd34d !important;
    color: #78350f !important;
  }

  /* Teks & Ikon Aksen Emas agar Tajam di Atas Kartu Putih / Krem (Mode Terang) */
  html:not(.dark) .view-all-link,
  html.light .view-all-link,
  html:not(.dark) .sinopsis-toggle-btn,
  html.light .sinopsis-toggle-btn,
  html:not(.dark) .stat-desc:not(.text-danger-adaptive),
  html.light .stat-desc:not(.text-danger-adaptive),
  html:not(.dark) .section-title svg,
  html.light .section-title svg,
  html:not(.dark) .box-title svg,
  html.light .box-title svg,
  html:not(.dark) .nanti-details-title,
  html.light .nanti-details-title,
  html:not(.dark) .help-hint a,
  html.light .help-hint a,
  html:not(.dark) .form-section-title,
  html.light .form-section-title,
  html:not(.dark) .head-info .page-title,
  html.light .head-info .page-title {
    color: #8a6100 !important;
  }
  html:not(.dark) .page-badge,
  html.light .page-badge,
  html:not(.dark) .user-badge-chip,
  html.light .user-badge-chip,
  html:not(.dark) .detail-genre-chip,
  html.light .detail-genre-chip,
  html:not(.dark) .btn-choose-foto,
  html.light .btn-choose-foto,
  html:not(.dark) .btn-browse-file,
  html.light .btn-browse-file,
  html:not(.dark) .btn-remove-file,
  html.light .btn-remove-file,
  html:not(.dark) .btn-change-book,
  html.light .btn-change-book,
  html:not(.dark) .card-thumb-btn,
  html.light .card-thumb-btn,
  html:not(.dark) .mode-btn.aktif,
  html.light .mode-btn.aktif,
  html:not(.dark) .font-btn.aktif,
  html.light .font-btn.aktif,
  html:not(.dark) .chip-btn.aktif,
  html.light .chip-btn.aktif {
    background: rgba(184, 134, 11, 0.12) !important;
    border-color: rgba(184, 134, 11, 0.35) !important;
    color: #785008 !important;
  }

  /* Modal Detail Buku, Tombol Like/Simpan, Form Readonly & Tabel di Mode Terang */
  html:not(.dark) .detail-modal,
  html.light .detail-modal {
    background: #ffffff !important;
    border-color: rgba(150, 110, 45, 0.25) !important;
    box-shadow: 0 20px 60px rgba(34, 29, 20, 0.18) !important;
  }
  html:not(.dark) .detail-sinopsis,
  html.light .detail-sinopsis {
    background: #faf7f0 !important;
    color: #221d14 !important;
    border-color: rgba(150, 110, 45, 0.2) !important;
    border-left: 3px solid #b8860b !important;
  }
  html:not(.dark) .detail-meta-chip,
  html.light .detail-meta-chip {
    background: #f8f5ee !important;
    border-color: rgba(150, 110, 45, 0.2) !important;
    color: #5c5243 !important;
  }
  html:not(.dark) .detail-close-btn,
  html.light .detail-close-btn,
  html:not(.dark) .settings-close-btn,
  html.light .settings-close-btn {
    background: rgba(255, 255, 255, 0.95) !important;
    border-color: #9a6b16 !important;
    color: #785008 !important;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18) !important;
  }
  html:not(.dark) .detail-close-btn svg,
  html.light .detail-close-btn svg,
  html:not(.dark) .settings-close-btn svg,
  html.light .settings-close-btn svg {
    color: #785008 !important;
    stroke: #785008 !important;
  }
  html:not(.dark) .btn-like:not(.aktif),
  html.light .btn-like:not(.aktif),
  html:not(.dark) .bk-actions .btn-save:not(.aktif),
  html.light .bk-actions .btn-save:not(.aktif),
  html:not(.dark) .detail-btn-like:not(.aktif),
  html.light .detail-btn-like:not(.aktif),
  html:not(.dark) .detail-btn-save:not(.aktif),
  html.light .detail-btn-save:not(.aktif),
  html:not(.dark) .detail-body .btn-save:not(.aktif),
  html.light .detail-body .btn-save:not(.aktif) {
    background: #f8f5ee !important;
    border-color: rgba(150, 110, 45, 0.25) !important;
    color: #5c5243 !important;
  }
  html:not(.dark) .bk-actions .btn-save.aktif,
  html.light .bk-actions .btn-save.aktif,
  html:not(.dark) .detail-btn-save.aktif,
  html.light .detail-btn-save.aktif,
  html:not(.dark) .detail-body .btn-save.aktif,
  html.light .detail-body .btn-save.aktif {
    background: rgba(184, 134, 11, 0.18) !important;
    border-color: #8a6100 !important;
    color: #8a6100 !important;
  }
  html:not(.dark) .bk-actions .btn-save.aktif svg,
  html.light .bk-actions .btn-save.aktif svg,
  html:not(.dark) .detail-btn-save.aktif svg,
  html.light .detail-btn-save.aktif svg {
    fill: #8a6100 !important;
    stroke: #8a6100 !important;
    color: #8a6100 !important;
  }
  html:not(.dark) .detail-btn-pinjam:disabled,
  html.light .detail-btn-pinjam:disabled,
  html:not(.dark) .detail-btn-pinjam.disabled,
  html.light .detail-btn-pinjam.disabled,
  html:not(.dark) .btn-disabled-stok,
  html.light .btn-disabled-stok {
    background: #e5e7eb !important;
    color: #6b7280 !important;
    border: 1px solid #d1d5db !important;
    opacity: 1 !important;
  }
  html:not(.dark) .form-input[readonly],
  html.light .form-input[readonly] {
    background: #f3efe6 !important;
    border-color: rgba(150, 110, 45, 0.24) !important;
    color: #5c5243 !important;
  }
  html:not(.dark) .btn-back,
  html.light .btn-back,
  html:not(.dark) .back-link,
  html.light .back-link {
    background: #ffffff !important;
    border-color: rgba(150, 110, 45, 0.25) !important;
    color: #4a3f2f !important;
  }
  html:not(.dark) .tab-count,
  html.light .tab-count {
    background: rgba(0, 0, 0, 0.08) !important;
    color: #374151 !important;
  }
  html:not(.dark) .tab-btn.active .tab-count,
  html.light .tab-btn.active .tab-count {
    background: rgba(0, 0, 0, 0.16) !important;
    color: #1a1205 !important;
  }
  html:not(.dark) thead th,
  html.light thead th,
  html:not(.dark) .denda-table th,
  html.light .denda-table th {
    background: #faf6ed !important;
    color: #5c4f3a !important;
    border-bottom-color: rgba(150, 110, 45, 0.2) !important;
  }
  html:not(.dark) tbody tr:hover td,
  html.light tbody tr:hover td,
  html:not(.dark) .denda-table tr:hover td,
  html.light .denda-table tr:hover td {
    background: rgba(184, 134, 11, 0.06) !important;
  }
  html:not(.dark) .denda-box:not(.lunas),
  html.light .denda-box:not(.lunas),
  html:not(.dark) .modal-denda-box:not(.no-denda),
  html.light .modal-denda-box:not(.no-denda) {
    background: #fff5f5 !important;
    border-color: #fca5a5 !important;
    color: #b91c1c !important;
  }
  html:not(.dark) .modal-denda-box:not(.no-denda) .modal-denda-label,
  html.light .modal-denda-box:not(.no-denda) .modal-denda-label,
  html:not(.dark) .modal-denda-box:not(.no-denda) .modal-denda-val,
  html.light .modal-denda-box:not(.no-denda) .modal-denda-val {
    color: #b91c1c !important;
  }
  html:not(.dark) .modal-denda-box.no-denda,
  html.light .modal-denda-box.no-denda {
    background: #f0fdf4 !important;
    border-color: #86efac !important;
  }
  html:not(.dark) .modal-denda-box.no-denda .modal-denda-label,
  html.light .modal-denda-box.no-denda .modal-denda-label,
  html:not(.dark) .modal-denda-box.no-denda .modal-denda-val,
  html.light .modal-denda-box.no-denda .modal-denda-val {
    color: #15803d !important;
  }
  html:not(.dark) .btn-tandai-lunas,
  html.light .btn-tandai-lunas {
    background: #ecfdf5 !important;
    border-color: #6ee7b7 !important;
    color: #047857 !important;
  }
  html:not(.dark) .btn-wa-disabled,
  html.light .btn-wa-disabled {
    background: #f1f5f9 !important;
    border-color: #cbd5e1 !important;
    color: #64748b !important;
  }

  /* Autosave Pill, Total Badge, Audio Preview & File Drop (Light Mode) */
  html.light .autosave-pill {
    background: #dcfce7 !important;
    color: #15803d !important;
    border-color: #86efac !important;
  }
  html.light .autosave-pill .dot {
    background: #16a34a !important;
  }
  html.light .autosave-pill.saving {
    background: #fef3c7 !important;
    color: #92400e !important;
    border-color: #fcd34d !important;
  }
  html.light .autosave-pill.saving .dot {
    background: #d97706 !important;
  }
  html.light .btn-hapus-file {
    background: #fef2f2 !important;
    color: #b91c1c !important;
    border-color: #fca5a5 !important;
  }
  html.light .btn-hapus-file:hover {
    background: #dc2626 !important;
    color: #ffffff !important;
    border-color: #dc2626 !important;
  }
  html.light .total-badge {
    background: rgba(180, 125, 20, 0.14) !important;
    color: #785008 !important;
    border-color: rgba(150, 110, 45, 0.32) !important;
  }
  html.light .current-preview .icon-wrap {
    background: rgba(180, 125, 20, 0.14) !important;
    border-color: rgba(150, 110, 45, 0.32) !important;
    color: #8a6100 !important;
  }
  html.light .current-preview .icon-wrap svg {
    color: #8a6100 !important;
    stroke: #8a6100 !important;
  }
  html.light .field .file-drop {
    background: #fdfaf3 !important;
    border-color: rgba(150, 110, 45, 0.35) !important;
  }
  html.light .field .file-drop svg {
    color: #8a6100 !important;
  }
  .btn-cancel-modal {
    background: rgba(255, 255, 255, 0.06) !important;
    color: var(--text, #eef3f4) !important;
    border: 1px solid var(--card-border, rgba(216, 184, 120, 0.18)) !important;
  }
  .btn-cancel-modal:hover {
    background: rgba(255, 255, 255, 0.12) !important;
  }
  html.light .btn-cancel-modal {
    background: #f1f5f9 !important;
    color: #334155 !important;
    border-color: #cbd5e1 !important;
  }
  html.light .btn-cancel-modal:hover {
    background: #e2e8f0 !important;
    color: #0f172a !important;
  }

  /* Inline Accent Text, Location Status Badges & ID Tetap Pill (Light Mode) */
  html.light .main [style*="color:var(--accent"],
  html.light .main [style*="color: var(--accent"],
  html.light .sidebar .nav-item[style*="color:var(--accent"],
  html.light .modal-overlay [style*="color:var(--accent"],
  html.light .modal-overlay [style*="color: var(--accent"],
  html.light .detail-overlay [style*="color:var(--accent"] {
    color: #8a6100 !important;
  }
  html.light .status-open {
    background: #dcfce7 !important;
    color: #15803d !important;
    border-color: #86efac !important;
    box-shadow: none !important;
  }
  html.light .status-open::before {
    background: #16a34a !important;
    box-shadow: none !important;
  }
  html.light .status-closed {
    background: #fee2e2 !important;
    color: #b91c1c !important;
    border-color: #fca5a5 !important;
    box-shadow: none !important;
  }
  html.light .status-closed::before {
    background: #dc2626 !important;
    box-shadow: none !important;
  }
  html.light .realtime-icon-wrap,
  html.light .preview-info-icon {
    background: rgba(180, 125, 20, 0.14) !important;
    border-color: rgba(150, 110, 45, 0.32) !important;
    color: #8a6100 !important;
  }
  html.light .btn-hapus-foto {
    background: #fef2f2 !important;
    border-color: #fca5a5 !important;
    color: #b91c1c !important;
  }
  html.light .id-tetap-box {
    background: #faf5e8 !important;
    border-color: rgba(150, 110, 45, 0.32) !important;
  }
  html.light .id-tetap-pill {
    background: #efe6d2 !important;
    color: #5c4213 !important;
    border: 1px solid rgba(150, 110, 45, 0.25) !important;
  }

  /* ═══ Universal Aksa Dialog & Confirmation Modal ═══ */
  .aksa-dialog-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(8, 11, 15, 0.72);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px 16px;
    opacity: 0;
    visibility: hidden;
    transition: opacity .25s ease, visibility .25s ease;
  }
  .aksa-dialog-overlay.active {
    opacity: 1;
    visibility: visible;
  }
  body.aksa-dialog-open {
    overflow: hidden !important;
  }

  .aksa-dialog-card {
    width: 100%;
    max-width: 440px;
    background: var(--card, #121820);
    border: 1px solid var(--card-border, rgba(216, 184, 120, 0.18));
    border-radius: 18px;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.45), 0 4px 12px rgba(0, 0, 0, 0.2);
    padding: 26px 24px 22px;
    display: flex;
    flex-direction: column;
    transform: scale(0.93) translateY(12px);
    transition: transform .25s cubic-bezier(0.22, 1, 0.36, 1);
    box-sizing: border-box;
  }
  html.light .aksa-dialog-card {
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.1);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15), 0 4px 12px rgba(0, 0, 0, 0.08);
  }
  .aksa-dialog-overlay.active .aksa-dialog-card {
    transform: scale(1) translateY(0);
  }

  .aksa-dialog-icon-wrap {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    flex-shrink: 0;
  }
  .aksa-dialog-icon-wrap svg {
    width: 26px;
    height: 26px;
  }
  .aksa-dialog-icon-wrap.danger {
    background: rgba(220, 38, 38, 0.12);
    color: #ef4444;
    border: 1.5px solid rgba(220, 38, 38, 0.25);
  }
  .aksa-dialog-icon-wrap.warning {
    background: rgba(245, 158, 11, 0.12);
    color: #f59e0b;
    border: 1.5px solid rgba(245, 158, 11, 0.25);
  }
  .aksa-dialog-icon-wrap.success {
    background: rgba(5, 150, 105, 0.12);
    color: #10b981;
    border: 1.5px solid rgba(5, 150, 105, 0.25);
  }
  .aksa-dialog-icon-wrap.info {
    background: rgba(216, 184, 120, 0.15);
    color: var(--accent, #d8b878);
    border: 1.5px solid rgba(216, 184, 120, 0.3);
  }

  .aksa-dialog-title {
    font-family: var(--font-family, 'Outfit', sans-serif);
    font-size: 1.22rem;
    font-weight: 800;
    color: var(--text, #eef3f4);
    margin: 0 0 8px 0;
    line-height: 1.35;
  }
  html.light .aksa-dialog-title {
    color: #1e293b;
  }

  .aksa-dialog-message {
    font-size: .88rem;
    line-height: 1.6;
    color: var(--muted, rgba(238, 243, 244, 0.7));
    margin: 0 0 24px 0;
    word-break: break-word;
  }
  html.light .aksa-dialog-message {
    color: #475569;
  }
  .aksa-dialog-message strong {
    color: var(--text, #eef3f4);
    font-weight: 700;
  }
  html.light .aksa-dialog-message strong {
    color: #0f172a;
  }

  .aksa-dialog-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    width: 100%;
  }

  .aksa-dialog-btn {
    font-family: inherit;
    font-size: .84rem;
    font-weight: 700;
    padding: 10px 20px;
    border-radius: 9px;
    cursor: pointer;
    transition: all .2s ease;
    min-height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    text-decoration: none;
  }
  .aksa-dialog-btn-cancel {
    background: rgba(255, 255, 255, 0.08);
    color: var(--text, #eef3f4);
    border: 1px solid var(--border-color, rgba(216, 184, 120, 0.18));
  }
  html.light .aksa-dialog-btn-cancel {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
  }
  .aksa-dialog-btn-cancel:hover {
    background: rgba(255, 255, 255, 0.14);
    color: #ffffff;
  }
  html.light .aksa-dialog-btn-cancel:hover {
    background: #e2e8f0;
    color: #1e293b;
  }

  .aksa-dialog-btn-confirm {
    border: none;
    color: #ffffff;
    font-weight: 800;
  }
  .aksa-dialog-btn-confirm.danger {
    background: #dc2626;
    box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);
  }
  .aksa-dialog-btn-confirm.danger:hover {
    background: #b91c1c;
    box-shadow: 0 6px 18px rgba(220, 38, 38, 0.5);
    transform: translateY(-1px);
  }
  .aksa-dialog-btn-confirm.warning {
    background: #d97706;
    box-shadow: 0 4px 14px rgba(217, 119, 6, 0.35);
  }
  .aksa-dialog-btn-confirm.warning:hover {
    background: #b45309;
    box-shadow: 0 6px 18px rgba(217, 119, 6, 0.5);
    transform: translateY(-1px);
  }
  .aksa-dialog-btn-confirm.success {
    background: #059669;
    box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
  }
  .aksa-dialog-btn-confirm.success:hover {
    background: #047857;
    box-shadow: 0 6px 18px rgba(5, 150, 105, 0.5);
    transform: translateY(-1px);
  }
  .aksa-dialog-btn-confirm.info {
    background: var(--btn-primary, linear-gradient(135deg,#d8b878,#c8a060));
    color: #121820;
    box-shadow: 0 4px 14px rgba(216, 184, 120, 0.35);
  }
  .aksa-dialog-btn-confirm.info:hover {
    background: var(--accent, #d8b878);
    box-shadow: 0 6px 18px rgba(216, 184, 120, 0.5);
    transform: translateY(-1px);
  }

  /* Responsif Mobile untuk Dialog Modal */
  @media (max-width: 520px) {
    .aksa-dialog-overlay {
      padding: 16px 12px;
      align-items: flex-end;
    }
    .aksa-dialog-card {
      border-radius: 20px 20px 16px 16px;
      padding: 22px 18px 18px;
      max-width: 100%;
      transform: translateY(20px);
    }
    .aksa-dialog-overlay.active .aksa-dialog-card {
      transform: translateY(0);
    }
    .aksa-dialog-actions {
      flex-direction: column-reverse;
      gap: 8px;
    }
    .aksa-dialog-btn {
      width: 100%;
      min-height: 46px;
      font-size: .88rem;
    }
  }

  /* ── Universal Responsive Modal Enhancements ── */
  @media (max-width: 540px) {
    .modal-overlay,
    .detail-modal-overlay {
      padding: 12px !important;
      align-items: flex-end !important;
    }
    .modal,
    .modal-box,
    .detail-modal,
    .profil-modal-box {
      width: 100% !important;
      max-width: 100% !important;
      margin: 0 !important;
      border-radius: 18px 18px 14px 14px !important;
      max-height: 88vh !important;
      overflow-y: auto !important;
      -webkit-overflow-scrolling: touch !important;
    }
    .modal-footer,
    .modal-foot {
      flex-direction: column-reverse !important;
      gap: 8px !important;
      width: 100% !important;
    }
    .modal-footer button,
    .modal-footer a,
    .modal-foot button,
    .modal-foot a {
      width: 100% !important;
      min-height: 44px !important;
      justify-content: center !important;
      text-align: center !important;
    }
  }

</style>

<script src="js/aksa_audio.js?v=20261109"></script>
<script>
/**
 * ── AksaAudio: Pengelola Musik Latar Persisten & Berkelanjutan ──
 * Memastikan musik tidak terputus/mengulang dari 00:00 saat berpindah halaman
 * (Beranda, Dashboard, Daftar Buku, Buku Simpan, Edit Profil).
 * Posisi detik pemutaran dan status play/pause disimpan di localStorage,
 * dan langsung dilanjutkan dari posisi terakhir di halaman tujuan.
 */
// window.AksaAudio dikelola terpusat oleh js/aksa_audio.js

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
        // Jangan pernah jalankan navigasi otomatis / efek redup pada tombol Logout!
        if (link.classList.contains('sidebar-btn-logout') || rawHref.toLowerCase().indexOf('logout.php') !== -1) {
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

/* ── Helper toggle sinopsis modal ── */
window.toggleSinopsis = function(elId, btn) {
  var el = document.getElementById(elId);
  if (!el) return;
  var collapsed = el.classList.toggle('sinopsis-collapsed');
  btn.textContent = collapsed ? 'Lihat Selengkapnya ▾' : 'Sembunyikan ▴';
};

</script>