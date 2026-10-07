<?php
// notifikasi_pengajuan_admin.php — Modal Pop-up Besar & Realtime Notifikasi Pengajuan Buku untuk Admin
// Berfungsi di SEMUA halaman admin secara konsisten & realtime
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hanya aktif untuk role admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    return;
}
?>

<style>
/* ═══════════════════════════════════════════════════════════════════
   ── NOTIFIKASI BESAR: PENGAJUAN PINJAMAN BUKU (ADMIN REALTIME) ──
═══════════════════════════════════════════════════════════════════ */
.notif-pengajuan-overlay {
  position: fixed;
  inset: 0;
  z-index: 999999;
  background: rgba(9, 12, 16, 0.86);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  display: none;
  align-items: center;
  justify-content: center;
  padding: 18px;
  box-sizing: border-box;
}

.notif-pengajuan-overlay.show {
  display: flex !important;
}

@keyframes notifPopIn {
  0% {
    opacity: 0;
    transform: scale(0.88) translateY(24px);
  }
  100% {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

@keyframes notifPulse {
  0%   { transform: scale(1);   opacity: 1; }
  50%  { transform: scale(1.4); opacity: 0.45; }
  100% { transform: scale(1);   opacity: 1; }
}

@keyframes notifGlowBorder {
  0%, 100% {
    box-shadow: 0 25px 75px rgba(0,0,0,0.85), 0 0 35px rgba(216,184,120,0.22);
    border-color: rgba(216,184,120,0.8);
  }
  50% {
    box-shadow: 0 30px 90px rgba(0,0,0,0.92), 0 0 55px rgba(216,184,120,0.48);
    border-color: rgba(245,158,11,1);
  }
}

.notif-pengajuan-card {
  background: linear-gradient(150deg, #161e27 0%, #10151c 65%, #0d1117 100%);
  border: 2px solid var(--accent, #d8b878);
  border-radius: 24px;
  width: 100%;
  max-width: 530px;
  padding: 28px;
  box-sizing: border-box;
  color: var(--text, #eef3f4);
  font-family: var(--font-family, 'Outfit', sans-serif);
  animation: notifPopIn 0.32s cubic-bezier(0.22, 1, 0.36, 1) both, notifGlowBorder 3s infinite ease-in-out;
  position: relative;
}

.notif-top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.notif-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 5px 13px;
  border-radius: 30px;
  background: rgba(245, 158, 11, 0.16);
  border: 1px solid rgba(245, 158, 11, 0.4);
  color: #fbbf24;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.6px;
  text-transform: uppercase;
}

.notif-pulse-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #fbbf24;
  box-shadow: 0 0 8px #fbbf24;
  animation: notifPulse 1.3s infinite;
}

.notif-close-btn {
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.12);
  color: var(--muted, rgba(238,243,244,0.7));
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.notif-close-btn:hover {
  background: rgba(220, 38, 38, 0.2);
  border-color: rgba(220, 38, 38, 0.45);
  color: #f87171;
  transform: rotate(90deg);
}

.notif-heading {
  font-family: 'Cormorant Garamond', 'Outfit', serif;
  font-size: 1.65rem;
  font-weight: 700;
  color: #fff;
  margin: 0 0 6px 0;
  line-height: 1.25;
}

.notif-subheading {
  font-size: 0.88rem;
  color: var(--muted, rgba(238,243,244,0.7));
  margin: 0 0 20px 0;
  line-height: 1.45;
}

.notif-book-box {
  background: rgba(255, 255, 255, 0.035);
  border: 1px solid var(--border-color, rgba(216,184,120,0.18));
  border-radius: 16px;
  padding: 16px;
  display: flex;
  gap: 16px;
  align-items: flex-start;
  margin-bottom: 16px;
}

.notif-cover-wrap {
  width: 82px;
  height: 116px;
  flex-shrink: 0;
  border-radius: 10px;
  overflow: hidden;
  background: var(--book-card, #161e27);
  border: 1px solid rgba(216,184,120,0.25);
  box-shadow: 0 6px 18px rgba(0,0,0,0.55);
  display: flex;
  align-items: center;
  justify-content: center;
}

.notif-cover-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.notif-cover-wrap svg {
  color: var(--accent, #d8b878);
  opacity: 0.7;
}

.notif-details {
  flex: 1;
  min-width: 0;
}

.notif-book-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--accent, #d8b878);
  margin-bottom: 4px;
  line-height: 1.35;
  word-break: break-word;
}

.notif-book-author {
  font-size: 0.8rem;
  color: var(--muted, rgba(238,243,244,0.65));
  margin-bottom: 12px;
}

.notif-grid-info {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px 12px;
  font-size: 0.82rem;
}

.notif-info-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.notif-info-item.full {
  grid-column: 1 / -1;
}

.notif-label {
  font-size: 0.7rem;
  color: var(--muted, rgba(238,243,244,0.55));
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.notif-value {
  font-weight: 600;
  color: var(--text, #eef3f4);
  word-break: break-word;
}

.notif-value.highlight {
  color: #4ade80;
}

.notif-badge-waktu {
  display: inline-block;
  padding: 2px 7px;
  border-radius: 6px;
  font-size: 0.74rem;
  font-weight: 700;
  width: fit-content;
}
.notif-badge-waktu.sekarang {
  background: rgba(216,184,120,0.15);
  color: var(--accent, #d8b878);
  border: 1px solid rgba(216,184,120,0.3);
}
.notif-badge-waktu.nanti {
  background: rgba(59, 130, 246, 0.15);
  color: #93c5fd;
  border: 1px solid rgba(59, 130, 246, 0.3);
}

.notif-antrean-badge {
  background: rgba(216, 184, 120, 0.08);
  border: 1px dashed rgba(216, 184, 120, 0.28);
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 0.82rem;
  color: var(--accent, #d8b878);
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 20px;
}

.notif-btn-group {
  display: flex;
  gap: 12px;
}

.notif-btn-primary {
  flex: 1.6;
  background: linear-gradient(135deg, #d8b878 0%, #c8a060 100%);
  color: #0d1117 !important;
  font-weight: 800;
  font-size: 0.94rem;
  padding: 14px 20px;
  border-radius: 12px;
  border: none;
  text-decoration: none !important;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  box-shadow: 0 6px 20px rgba(216,184,120,0.35);
  cursor: pointer;
  transition: transform 0.18s, box-shadow 0.18s, filter 0.18s;
  text-align: center;
}
.notif-btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 26px rgba(216,184,120,0.5);
  filter: brightness(1.06);
}

.notif-btn-secondary {
  flex: 1;
  background: rgba(255,255,255,0.06);
  border: 1.5px solid var(--border-color, rgba(216,184,120,0.22));
  color: var(--muted, rgba(238,243,244,0.75));
  font-weight: 600;
  font-size: 0.9rem;
  padding: 14px 18px;
  border-radius: 12px;
  cursor: pointer;
  transition: background 0.2s, color 0.2s, border-color 0.2s;
  text-align: center;
}
.notif-btn-secondary:hover {
  background: rgba(255,255,255,0.12);
  color: var(--text, #eef3f4);
  border-color: var(--accent, #d8b878);
}

@media (max-width: 540px) {
  .notif-pengajuan-card {
    padding: 20px;
    border-radius: 18px;
  }
  .notif-heading {
    font-size: 1.4rem;
  }
  .notif-book-box {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }
  .notif-grid-info {
    text-align: left;
  }
  .notif-btn-group {
    flex-direction: column;
  }
}
</style>

<!-- ═══════════ MODAL OVERLAY BESAR PENGAJUAN BUKU ═══════════ -->
<div id="modalNotifPengajuan" class="notif-pengajuan-overlay" aria-hidden="true">
  <div class="notif-pengajuan-card" role="dialog" aria-modal="true" aria-labelledby="notifPengajuanHeading">
    <!-- Top Bar -->
    <div class="notif-top-bar">
      <div class="notif-pill">
        <span class="notif-pulse-dot"></span>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <span>Pengajuan Baru Masuk!</span>
      </div>
      <button type="button" class="notif-close-btn" onclick="tutupNotifPengajuan()" title="Tutup Notifikasi">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <!-- Heading -->
    <h2 class="notif-heading" id="notifPengajuanHeading">Anggota Mengajukan Pinjaman Buku!</h2>
    <p class="notif-subheading">
      Ada anggota perpustakaan yang baru saja mengajukan peminjaman buku dan menunggu konfirmasi Anda.
    </p>

    <!-- Detail Box -->
    <div class="notif-book-box">
      <div class="notif-cover-wrap">
        <img id="notifImgCover" src="" alt="Cover Buku" style="display:none;" />
        <div id="notifCoverPlaceholder">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        </div>
      </div>
      <div class="notif-details">
        <div class="notif-book-title" id="notifBookTitle">Memuat judul buku...</div>
        <div class="notif-book-author" id="notifBookAuthor">Memuat penulis...</div>

        <div class="notif-grid-info">
          <div class="notif-info-item">
            <span class="notif-label">Nama Peminjam</span>
            <span class="notif-value" id="notifNamaPeminjam">-</span>
          </div>
          <div class="notif-info-item">
            <span class="notif-label">Kelas / No. HP</span>
            <span class="notif-value" id="notifKelasHp">-</span>
          </div>
          <div class="notif-info-item">
            <span class="notif-label">Jumlah Buku</span>
            <span class="notif-value highlight" id="notifTotalBuku">1 Eksemplar</span>
          </div>
          <div class="notif-info-item">
            <span class="notif-label">Batas Pengembalian</span>
            <span class="notif-value" id="notifBatasKembali">-</span>
          </div>
          <div class="notif-info-item full">
            <span class="notif-label">Waktu Pengambilan</span>
            <div id="notifWaktuAmbil" style="margin-top:2px;">-</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Antrean Banner (jika > 1) -->
    <div id="notifAntreanWrap" class="notif-antrean-badge" style="display:none;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <span id="notifAntreanText">Terdapat pengajuan lain yang juga menunggu tindakan Anda.</span>
    </div>

    <!-- Actions -->
    <div class="notif-btn-group">
      <a href="pengajuan_buku.php" class="notif-btn-primary" id="notifBtnAction" onclick="handleBukaPengajuan(event)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
        <span>Buka & Proses Pengajuan</span>
      </a>
      <button type="button" class="notif-btn-secondary" onclick="tutupNotifPengajuan()">
        Nanti Saja
      </button>
    </div>
  </div>
</div>

<script>
/**
 * ── AksaNotif: Pengelola Notifikasi Pop-up Realtime Pengajuan Buku ──
 * Memantau setiap pengajuan baru dari anggota dan menampilkan modal besar
 * di seluruh halaman admin, dilengkapi audio chime & link ke pengajuan_buku.php.
 */
(function initAksaNotifPengajuan() {
  var STORAGE_KEY = 'aksa_notif_pengajuan_last_seen_id';
  var POLL_INTERVAL = 4000; // Cek setiap 4 detik
  var activeNotifId = 0;
  var isOverlayOpen = false;

  // ── Web Audio Chime (Suara Lonceng Merdu Tanpa File Eksternal) ──
  function mainkanLoncengNotifikasi() {
    try {
      var AudioContext = window.AudioContext || window.webkitAudioContext;
      if (!AudioContext) return;
      var ctx = new AudioContext();
      if (ctx.state === 'suspended') {
        ctx.resume();
      }
      // Rangkaian melodi 4 nada: C5 -> E5 -> G5 -> C6
      var notes = [523.25, 659.25, 783.99, 1046.50];
      notes.forEach(function(freq, idx) {
        var osc = ctx.createOscillator();
        var gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, ctx.currentTime + idx * 0.11);
        gain.gain.setValueAtTime(0, ctx.currentTime + idx * 0.11);
        gain.gain.linearRampToValueAtTime(0.18, ctx.currentTime + idx * 0.11 + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + idx * 0.11 + 0.38);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(ctx.currentTime + idx * 0.11);
        osc.stop(ctx.currentTime + idx * 0.11 + 0.4);
      });
    } catch (e) {
      // Browser autoplay policy graceful fallback
    }
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function updateSidebarBadge(count) {
    var links = document.querySelectorAll('a.sidebar-btn[href*="pengajuan_buku.php"], .sidebar a[href*="pengajuan_buku.php"], a.nav-item[href*="halaman_admin.php"]');
    links.forEach(function(link) {
      var existingBadge = link.querySelector('span');
      if (count > 0) {
        if (existingBadge) {
          existingBadge.textContent = count;
          existingBadge.style.display = 'inline-block';
        } else {
          var span = document.createElement('span');
          span.style.cssText = "margin-left:auto;background:rgba(245,158,11,.2);color:#fbbf24;border:1px solid rgba(245,158,11,.4);font-size:.65rem;font-weight:800;padding:2px 7px;border-radius:20px;";
          span.textContent = count;
          link.appendChild(span);
        }
      } else {
        if (existingBadge) {
          existingBadge.style.display = 'none';
        }
      }
    });
  }

  function renderModalContent(data, countMenunggu) {
    var imgCover = document.getElementById('notifImgCover');
    var phCover  = document.getElementById('notifCoverPlaceholder');
    var bookTitle= document.getElementById('notifBookTitle');
    var bookAuth = document.getElementById('notifBookAuthor');
    var namaPem  = document.getElementById('notifNamaPeminjam');
    var kelasHp  = document.getElementById('notifKelasHp');
    var totalBuku= document.getElementById('notifTotalBuku');
    var batasKem = document.getElementById('notifBatasKembali');
    var waktuAmb = document.getElementById('notifWaktuAmbil');
    var antreanWrap = document.getElementById('notifAntreanWrap');
    var antreanText = document.getElementById('notifAntreanText');

    if (!data) return;

    if (data.gambar) {
      imgCover.src = data.gambar;
      imgCover.style.display = 'block';
      phCover.style.display = 'none';
    } else {
      imgCover.style.display = 'none';
      phCover.style.display = 'flex';
    }

    bookTitle.textContent = data.judul || 'Judul Buku Tidak Diketahui';
    var authText = data.penulis ? ('Penulis: ' + data.penulis) : '-';
    if (data.rak) {
      bookAuth.innerHTML = escapeHtml(authText) + ' &middot; <span style="color:var(--accent,#d8b878);font-weight:700;">📍 ' + escapeHtml(data.rak) + '</span>';
    } else {
      bookAuth.textContent = authText;
    }
    namaPem.textContent   = data.nama_peminjam || '-';

    var infoKelas = data.kelas ? data.kelas : '';
    var infoHp    = data.no_hp ? data.no_hp : '';
    kelasHp.textContent   = (infoKelas && infoHp) ? (infoKelas + ' • ' + infoHp) : (infoKelas || infoHp || '-');

    totalBuku.textContent = (data.total_buku || 1) + ' Eksemplar';
    batasKem.textContent  = data.batas_kembali_fmt || data.batas_kembali || '-';

    if (data.waktu_pengambilan === 'nanti') {
      waktuAmb.innerHTML = '<span class="notif-badge-waktu nanti">📅 Ambil Nanti</span>' + (data.catatan_pengambilan ? ' <span style="font-size:.78rem;color:var(--muted);margin-left:4px;">(' + escapeHtml(data.catatan_pengambilan) + ')</span>' : '');
    } else {
      waktuAmb.innerHTML = '<span class="notif-badge-waktu sekarang">⚡ Ambil Sekarang</span>';
    }

    if (countMenunggu > 1) {
      var sisa = countMenunggu - 1;
      antreanText.textContent = 'Masih ada ' + sisa + ' pengajuan peminjaman lain yang juga menunggu tindakan Anda.';
      antreanWrap.style.display = 'flex';
    } else {
      antreanWrap.style.display = 'none';
    }
  }

  function tampilkanNotifPengajuan(data, countMenunggu) {
    activeNotifId = parseInt(data.id, 10) || 0;
    renderModalContent(data, countMenunggu);

    var overlay = document.getElementById('modalNotifPengajuan');
    if (overlay && !isOverlayOpen) {
      overlay.classList.add('show');
      overlay.setAttribute('aria-hidden', 'false');
      isOverlayOpen = true;
      mainkanLoncengNotifikasi();
    }
  }

  window.tutupNotifPengajuan = function() {
    var overlay = document.getElementById('modalNotifPengajuan');
    if (overlay) {
      overlay.classList.remove('show');
      overlay.setAttribute('aria-hidden', 'true');
      isOverlayOpen = false;
    }
    if (activeNotifId > 0) {
      localStorage.setItem(STORAGE_KEY, String(activeNotifId));
    }
  };

  window.handleBukaPengajuan = function(e) {
    if (activeNotifId > 0) {
      localStorage.setItem(STORAGE_KEY, String(activeNotifId));
    }
    var currentPage = window.location.pathname.split('/').pop() || '';
    if (currentPage === 'pengajuan_buku.php') {
      e.preventDefault();
      tutupNotifPengajuan();
      window.location.reload();
    }
  };

  // Sinkronisasi antar-tab
  window.addEventListener('storage', function(e) {
    if (e.key === STORAGE_KEY && isOverlayOpen) {
      var dismissedId = parseInt(e.newValue || '0', 10);
      if (dismissedId >= activeNotifId) {
        tutupNotifPengajuan();
      }
    }
  });

  // Tombol ESC untuk tutup
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && isOverlayOpen) {
      tutupNotifPengajuan();
    }
  });

  // Klik backdrop di luar modal untuk menutup
  document.addEventListener('click', function(e) {
    var overlay = document.getElementById('modalNotifPengajuan');
    if (isOverlayOpen && e.target === overlay) {
      tutupNotifPengajuan();
    }
  });

  // ── Polling Server ──
  function periksaPengajuan() {
    fetch('api_cek_pengajuan_admin.php', { cache: 'no-store' })
      .then(function(res) {
        if (!res.ok) throw new Error('Status ' + res.status);
        return res.json();
      })
      .then(function(resp) {
        if (!resp || !resp.success) return;

        var countMenunggu = resp.count_menunggu || 0;
        updateSidebarBadge(countMenunggu);

        if (countMenunggu === 0) {
          // Jika pengajuan sudah disetujui/ditolak oleh admin di tab lain
          if (isOverlayOpen) {
            tutupNotifPengajuan();
          }
          return;
        }

        var latestId = parseInt(resp.latest_id || '0', 10);
        var lastSeenId = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);

        // Jika ada ID pengajuan baru yang belum ditutup/dilihat
        if (latestId > 0 && latestId > lastSeenId && resp.latest) {
          tampilkanNotifPengajuan(resp.latest, countMenunggu);
        }
      })
      .catch(function(err) {
        // Abaikan error koneksi jaringan agar konsol tidak berisik
      });
  }

  // Mulai polling setelah DOM siap
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      setTimeout(periksaPengajuan, 800);
      setInterval(periksaPengajuan, POLL_INTERVAL);
    });
  } else {
    setTimeout(periksaPengajuan, 800);
    setInterval(periksaPengajuan, POLL_INTERVAL);
  }
})();
</script>
