/**
 * aksa_audio.js — Pengelola Musik Latar Persisten & Berkelanjutan (AKSA NOVA)
 * Menangani pemutaran musik latar di:
 * - index.php (Landing Page)
 * - beranda.php, daftar_buku.php, dashboard_user.php, buku_simpan.php,
 *   edit_kartu.php, pengajuan_peminjaman.php, pinjam_buku.php
 *
 * Fitur:
 * 1. Autoplay cerdas: coba putar unmuted; bila browser membatasi (Autoplay Policy),
 *    buffer audio dan pasang gesture listener sekali (click/pointerdown/keydown/touchstart)
 *    agar musik langsung bersuara saat pengguna berinteraksi pertama kali.
 * 2. Status interaksi pengguna dipisah dari blokir peramban (PAUSED_KEY 'aksanova_audio_user_paused'):
 *    - '1' = Pengguna sengaja mematikan musik (tidak akan diputar otomatis).
 *    - '0' atau null = Musik aktif memutar (default saat musik_aktif = 1).
 * 3. Persistensi posisi detik pemutaran (TIME_KEY 'aksanova_audio_time'):
 *    - Posisi tersimpan dilanjutkan mulus saat berpindah halaman tanpa mengulang dari 00:00.
 * 4. Sinkronisasi ganti lagu di admin (SRC_KEY 'aksanova_audio_src'):
 *    - Bila berkas audio diganti oleh admin, posisi direset ke 00:00.
 * 5. Deteksi loop mulus tanpa jeda (timeupdate & ended).
 * 6. UI Equalizer & ikon audio tersinkron otomatis (.playing).
 */
window.AksaAudio = (function() {
  'use strict';

  var TIME_KEY       = 'aksanova_audio_time';
  var STATUS_KEY     = 'aksanova_audio_status'; // 'playing' | 'paused'
  var PAUSED_KEY     = 'aksanova_audio_user_paused'; // '1' = user explicitly paused, '0' = user unpaused
  var SRC_KEY        = 'aksanova_audio_src';

  var inited         = false;
  var gestureBound   = false;
  var pausedByHidden = false;
  var timeApplied    = false;

  function getAudio() {
    return document.getElementById('audioLatar');
  }

  function getBtn() {
    return document.getElementById('btnMusik');
  }

  function getSongKey(src) {
    if (!src) return '';
    try {
      var clean = src.split('?')[0].split('#')[0];
      return clean.substring(clean.lastIndexOf('/') + 1).toLowerCase();
    } catch(e) {
      return src.toLowerCase();
    }
  }

  function isExplicitlyPaused() {
    try {
      if (localStorage.getItem(PAUSED_KEY) === '1') return true;
      if (localStorage.getItem(PAUSED_KEY) === '0') return false;
    } catch(e) {}
    return false; // Default: aktif memutar bila musik_aktif = 1
  }

  function saveState() {
    var audio = getAudio();
    if (!audio) return;
    try {
      if (!isNaN(audio.currentTime) && audio.currentTime > 0) {
        localStorage.setItem(TIME_KEY, audio.currentTime.toString());
      }
      if (isExplicitlyPaused()) {
        localStorage.setItem(STATUS_KEY, 'paused');
      } else {
        localStorage.setItem(STATUS_KEY, 'playing');
      }
    } catch(e) {}
  }

  function setBtnUI(isPlaying) {
    var btn = getBtn();
    if (!btn) return;
    btn.classList.toggle('playing', !!isPlaying);
    btn.setAttribute('aria-pressed', isPlaying ? 'true' : 'false');
  }

  function init() {
    var audio = getAudio();
    var btn   = getBtn();
    if (!audio) return;
    if (inited) return;
    inited = true;

    audio.volume = 0.55;
    audio.loop = true;

    // Cek file lagu saat ini
    var srcTag = audio.querySelector('source');
    var currentSrc = (srcTag && srcTag.getAttribute('src')) ? srcTag.getAttribute('src') : (audio.currentSrc || audio.src || '');
    var savedSrc = '';
    try { savedSrc = localStorage.getItem(SRC_KEY) || ''; } catch(e) {}

    var savedTime = 0;
    try { savedTime = parseFloat(localStorage.getItem(TIME_KEY) || '0'); } catch(e) {}

    // Bila admin mengganti lagu di database/pengaturan_musik, reset posisi ke 0
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

    function applySavedTime() {
      if (timeApplied) return;
      if (savedTime > 0 && isFinite(savedTime)) {
        try {
          if (audio.duration && isFinite(audio.duration) && audio.duration > 0) {
            // Bila tersisa kurang dari 1.5 detik, mulai dari awal
            if (savedTime >= audio.duration - 1.5) {
              savedTime = 0;
              audio.currentTime = 0;
              try { localStorage.setItem(TIME_KEY, '0'); } catch(e) {}
              timeApplied = true;
              return;
            }
            if (Math.abs(audio.currentTime - savedTime) > 0.3) {
              audio.currentTime = savedTime;
              timeApplied = true;
            }
          }
        } catch(e) {}
      }
    }

    applySavedTime();
    audio.addEventListener('loadedmetadata', applySavedTime);
    audio.addEventListener('canplay', applySavedTime);
    audio.addEventListener('play', function() {
      if (!audio.muted) setBtnUI(true);
    });
    audio.addEventListener('pause', function() {
      if (!pausedByHidden) setBtnUI(false);
    });
    audio.addEventListener('volumechange', function() {
      if (audio.muted) {
        setBtnUI(false);
      } else if (!audio.paused) {
        setBtnUI(true);
      }
    });

    // Deteksi loop tak terhingga secara proaktif (0.35 detik sebelum berkas selesai)
    audio.addEventListener('timeupdate', function() {
      if (audio.duration && isFinite(audio.duration) && audio.duration > 1) {
        if (audio.currentTime >= audio.duration - 0.35) {
          audio.currentTime = 0;
          try { localStorage.setItem(TIME_KEY, '0'); } catch(e) {}
          if (!isExplicitlyPaused() && audio.paused) {
            audio.play().catch(function() {});
          }
          return;
        }
      }
      if (!audio.paused && audio.currentTime > 0) {
        try {
          localStorage.setItem(TIME_KEY, audio.currentTime.toString());
        } catch(e) {}
      }
    });

    audio.addEventListener('ended', function() {
      try { localStorage.setItem(TIME_KEY, '0'); } catch(e) {}
      audio.currentTime = 0;
      if (!isExplicitlyPaused()) {
        audio.play().catch(function() {});
      }
    });

    audio.addEventListener('stalled', function() {
      if (!isExplicitlyPaused() && audio.duration && audio.currentTime >= audio.duration - 0.5) {
        audio.currentTime = 0;
        audio.play().catch(function() {});
      }
    });

    // Simpan posisi sebelum halaman ditutup/berpindah
    window.addEventListener('beforeunload', saveState);
    window.addEventListener('pagehide', saveState);

    // Gesture listener untuk membuka kunci autoplay dengan suara pada interaksi pertama
    function removeGestureListeners() {
      if (!gestureBound) return;
      gestureBound = false;
      ['click', 'pointerdown', 'keydown', 'touchstart'].forEach(function(ev) {
        window.removeEventListener(ev, onUserGesture, true);
        document.removeEventListener(ev, onUserGesture, true);
      });
    }

    function onUserGesture(ev) {
      if (ev && ev.target && btn && (ev.target === btn || btn.contains(ev.target))) {
        return; // Ditangani oleh listener tombol langsung
      }
      if (!isExplicitlyPaused()) {
        audio.muted = false;
        applySavedTime();
        var p = audio.play();
        if (p !== undefined) {
          p.then(function() {
            setBtnUI(true);
            try {
              localStorage.setItem(PAUSED_KEY, '0');
              localStorage.setItem(STATUS_KEY, 'playing');
            } catch(e) {}
          }).catch(function() {});
        }
      }
      removeGestureListeners();
    }

    function attachGestureListeners() {
      if (gestureBound) return;
      gestureBound = true;
      ['click', 'pointerdown', 'keydown', 'touchstart'].forEach(function(ev) {
        window.addEventListener(ev, onUserGesture, { capture: true, once: false, passive: true });
        document.addEventListener(ev, onUserGesture, { capture: true, once: false, passive: true });
      });
    }

    function playAudio() {
      applySavedTime();
      audio.muted = false;
      var p = audio.play();
      if (p !== undefined) {
        p.then(function() {
          // Berhasil memutar dengan suara
          setBtnUI(true);
          removeGestureListeners();
          try {
            localStorage.setItem(PAUSED_KEY, '0');
            localStorage.setItem(STATUS_KEY, 'playing');
          } catch(e) {}
        }).catch(function() {
          // Dicegah kebijakan autoplay browser: mulai buffer dalam keadaan muted
          // dan tunggu interaksi pengguna pertama di dokumen
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

    // Klik tombol musik langsung oleh pengguna
    if (btn && !btn._aksaBound) {
      btn._aksaBound = true;
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        removeGestureListeners();

        if (audio.paused || audio.muted) {
          // Nyalakan musik dengan suara
          try {
            localStorage.setItem(PAUSED_KEY, '0');
            localStorage.setItem(STATUS_KEY, 'playing');
          } catch(e) {}
          audio.muted = false;
          applySavedTime();
          var p = audio.play();
          if (p !== undefined) {
            p.then(function() {
              setBtnUI(true);
            }).catch(function() {
              setBtnUI(false);
            });
          }
        } else {
          // Hentikan musik
          try {
            localStorage.setItem(PAUSED_KEY, '1');
            localStorage.setItem(STATUS_KEY, 'paused');
            saveState();
          } catch(e) {}
          audio.pause();
          setBtnUI(false);
        }
      });
    }

    // Tangani perubahan visibilitas tab
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) {
        if (!audio.paused) {
          pausedByHidden = true;
          audio.pause();
          setBtnUI(false);
        }
      } else if (pausedByHidden && !isExplicitlyPaused()) {
        pausedByHidden = false;
        playAudio();
      }
    });

    // Jalankan pemutaran berdasarkan preferensi
    if (isExplicitlyPaused()) {
      audio.pause();
      setBtnUI(false);
    } else {
      playAudio();
    }
  }

  // Auto-init saat DOM siap
  function autoStart() {
    if (getAudio() && getBtn()) {
      init();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoStart);
  } else {
    setTimeout(autoStart, 0);
  }

  return {
    init: init,
    saveState: saveState,
    getAudio: getAudio,
    getBtn: getBtn,
    isExplicitlyPaused: isExplicitlyPaused
  };
})();
