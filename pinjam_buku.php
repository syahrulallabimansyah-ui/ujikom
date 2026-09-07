<?php
// pinjam_buku.php — Halaman Pinjam Buku
// PHP 8.3 — Desain mengikuti halaman_admin.php

declare(strict_types=1);
session_start();

// Cek login & role admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: sign_in.php');
    exit;
}

require_once 'db.php';

assert($conn instanceof mysqli);
mysqli_set_charset($conn, 'utf8mb4');

// Samakan zona waktu PHP & MySQL supaya waktu pinjam tidak meleset dari WIB
date_default_timezone_set('Asia/Jakarta');
mysqli_query($conn, "SET time_zone = '+07:00'");

$page_title = 'Pinjam Buku – AKSA NOVA';
$msg        = '';
$msg_type   = '';

// ─── Profil admin ───
$profil_res = mysqli_query($conn, 'SELECT * FROM admin_profile LIMIT 1');
$profil     = $profil_res ? mysqli_fetch_assoc($profil_res) : null;
$admin_name = $profil['display_name'] ?? ($_SESSION['user_name'] ?? 'Admin');
$admin_foto = $profil['foto'] ?? '';

// ─────────────────────────────────────────────
//  AKSI: Proses Peminjaman
// ─────────────────────────────────────────────
$action = $_POST['action'] ?? '';

if ($action === 'pinjam') {
    $buku_id       = (int)($_POST['buku_id']       ?? 0);
    $user_id       = (int)($_POST['user_id']        ?? 0);
    $batas_kembali = trim($_POST['batas_kembali']   ?? '');

    if ($buku_id <= 0 || $user_id <= 0 || $batas_kembali === '') {
        $msg = 'Pilih anggota dan isi tanggal pengembalian terlebih dahulu.';
        $msg_type = 'error';
    } else {
        // Pastikan yang dipilih benar-benar anggota (punya akun) yang sudah disetujui
        $anggota_res = mysqli_query($conn,
            "SELECT full_name FROM users
             WHERE id=$user_id AND role='member' AND status='approved' LIMIT 1"
        );
        $anggota_row = $anggota_res ? mysqli_fetch_assoc($anggota_res) : null;

        if (!$anggota_row) {
            $msg = 'Anggota tidak ditemukan atau akunnya belum disetujui.';
            $msg_type = 'error';
        } else {
            // Cek stok — hanya dari buku yang masih ada
            $stok_res = mysqli_query($conn, "SELECT stok FROM buku WHERE id=$buku_id LIMIT 1");
            $stok_row = $stok_res ? mysqli_fetch_assoc($stok_res) : null;

            if (!$stok_row || (int)$stok_row['stok'] <= 0) {
                $msg = 'Stok buku habis atau buku tidak ditemukan.';
                $msg_type = 'error';
            } else {
                // Nama peminjam diambil dari data akun anggota, bukan input manual
                $np  = mysqli_real_escape_string($conn, $anggota_row['full_name']);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $batas_kembali)) {
                    $batas_kembali .= ' 23:59:59';
                }
                $bk  = mysqli_real_escape_string($conn, $batas_kembali);
                $wp  = date('Y-m-d H:i:s'); // Waktu pinjam otomatis dari server

                // Kurangi stok secara aman (atomic)
                mysqli_query($conn, "UPDATE buku SET stok = stok - 1 WHERE id = $buku_id AND stok > 0");

                if (mysqli_affected_rows($conn) > 0) {
                    // Insert peminjaman (tertaut ke user_id anggota)
                    mysqli_query($conn,
                        "INSERT INTO peminjaman (buku_id, user_id, nama_peminjam, waktu_pinjam, batas_kembali)
                         VALUES ($buku_id, $user_id, '$np', '$wp', '$bk')"
                    );

                    $msg = 'Buku berhasil dipinjam!';
                    $msg_type = 'success';
                } else {
                    $msg = 'Stok buku habis atau baru saja dipinjam oleh peminjam lain.';
                    $msg_type = 'error';
                }
            }
        }
    }
}

// ─────────────────────────────────────────────
//  AMBIL BUKU YANG STOK > 0
// ─────────────────────────────────────────────
$search  = trim($_GET['q'] ?? '');
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == '1';
$where  = 'WHERE stok > 0';
if ($search !== '') {
    $s     = mysqli_real_escape_string($conn, $search);
    $where .= " AND (judul LIKE '%$s%' OR penulis LIKE '%$s%' OR isbn LIKE '%$s%')";
}

$buku_list = [];
$res = mysqli_query($conn, "SELECT * FROM buku $where ORDER BY judul ASC");
while ($row = mysqli_fetch_assoc($res)) {
    $buku_list[] = $row;
}
$total = count($buku_list);

// Total dipinjam aktif — hanya buku yang masih ada di tabel buku
$total_dipinjam_res = mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM peminjaman p
     INNER JOIN buku b ON b.id = p.buku_id
     WHERE p.status='dipinjam'"
);
$total_dipinjam = (int)(mysqli_fetch_assoc($total_dipinjam_res)['c'] ?? 0);

// ─────────────────────────────────────────────
//  AMBIL DAFTAR ANGGOTA (yang punya akun & sudah disetujui)
//  Dipakai untuk fitur "Pilih Anggota" di modal pinjam
// ─────────────────────────────────────────────
$anggota_list = [];
$anggota_res  = mysqli_query($conn,
    "SELECT id, full_name, no_anggota, kelas, username, foto
     FROM users
     WHERE role = 'member' AND status = 'approved'
     ORDER BY full_name ASC"
);
if ($anggota_res) {
    while ($row = mysqli_fetch_assoc($anggota_res)) {
        // Simpan status keberadaan file supaya JS tidak perlu menebak-nebak
        $row['foto_ok'] = ($row['foto'] ?? '') !== '' && file_exists($row['foto']);
        $anggota_list[] = $row;
    }
}

$colors = ['col-a','col-b','col-c','col-d','col-e','col-f','col-g','col-h'];

// Buffer seluruh output halaman. Untuk request AJAX (live search), buffer ini
// dibuang sepenuhnya sebelum kita kirim hanya fragmen hasil pencarian.
ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Cormorant+Garamond:wght@700&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --sidebar-bg:  #4a4a5a;
      --sidebar-dark:#2e2e3a;
      --accent:      #5a5a6e;
      --btn-primary: #3a3a4a;
      --btn-pinjam:  #2563eb;
      --text:        #1a1a2e;
      --muted:       #7a7a9a;
      --bg:          #f0f0f0;
      --card:        #ffffff;
      --radius:      10px;
      --sidebar-w:   204px;
      --trans:       .2s cubic-bezier(.22,1,.36,1);
      --shadow:      0 2px 12px rgba(0,0,0,.07);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      display: flex;
      min-height: 100vh;
      animation: bodyIn .4s ease both;
    }
    @keyframes bodyIn { from { opacity:0; } to { opacity:1; } }

    /* ── SIDEBAR ── */
    .sidebar {
      width: var(--sidebar-w);
      background: linear-gradient(180deg, #5a5a6e 0%, #2e2e3a 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 36px 20px 28px;
      position: fixed;
      top: 0; left: 0; bottom: 0;
      z-index: 100;
      transition: transform var(--trans);
    }
    .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:90; }
    .sidebar-overlay.open { display:block; }
    .sidebar-toggle {
      display:none; position:fixed; top:14px; left:14px; z-index:200;
      width:40px; height:40px; border-radius:10px; border:none;
      background:#fff; box-shadow:0 2px 10px rgba(0,0,0,.15);
      cursor:pointer; align-items:center; justify-content:center;
    }
    .sidebar-toggle svg { width:20px; height:20px; }

    .avatar-wrap { position:relative; margin-bottom:14px; cursor:pointer; }
    .avatar-circle {
      width:96px; height:96px; border-radius:50%;
      background:#c0c0c8; overflow:hidden;
      border:3px solid rgba(255,255,255,.25);
      display:flex; align-items:center; justify-content:center;
      transition:border-color var(--trans);
    }
    .avatar-wrap:hover .avatar-circle { border-color:rgba(255,255,255,.55); }
    .avatar-circle img { width:100%; height:100%; object-fit:cover; display:block; }
    .avatar-circle .default-icon { width:52px; height:52px; color:#888; }
    .avatar-overlay {
      position:absolute; inset:0; border-radius:50%;
      background:rgba(0,0,0,.45); display:flex;
      align-items:center; justify-content:center;
      opacity:0; transition:opacity .2s;
    }
    .avatar-wrap:hover .avatar-overlay { opacity:1; }
    .avatar-overlay svg { width:24px; height:24px; color:#fff; }
    .img-preview-wrap { position:relative; border:2px dashed #d8d8e4; overflow:hidden; cursor:pointer; }
    .img-preview-wrap img { width:100%; height:100%; object-fit:cover; }
    .upload-placeholder { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; color:var(--muted); }
    .upload-placeholder svg { width:26px; height:26px; }
    .btn-save { flex:1; padding:11px; border-radius:8px; border:none; background:var(--btn-pinjam); color:#fff; font-family:'Nunito',sans-serif; font-size:.85rem; font-weight:700; cursor:pointer; transition:background var(--trans); }
    .btn-save:hover { background:#1d4ed8; }

    .admin-name-label { color:#fff; font-size:.95rem; font-weight:700; margin-bottom:8px; text-align:center; }
    .total-badge {
      background: rgba(255,255,255,.18); color:#fff;
      font-size:.72rem; font-weight:700;
      padding:4px 12px; border-radius:50px;
      margin-bottom:8px; text-align:center;
    }

    .sidebar-btn {
      width:100%; display:flex; align-items:center; gap:10px;
      padding:10px 14px; border-radius:8px; border:none;
      background:rgba(255,255,255,.12); color:#fff;
      font-family:'Nunito',sans-serif; font-size:.82rem; font-weight:700;
      cursor:pointer; margin-bottom:8px;
      transition:background var(--trans);
      text-align:left; text-decoration:none;
    }
    .sidebar-btn:hover { background:rgba(255,255,255,.22); }
    .sidebar-btn.active { background:rgba(255,255,255,.3); }
    .sidebar-btn svg { width:16px; height:16px; flex-shrink:0; }

    /* ── MAIN ── */
    .main { margin-left:var(--sidebar-w); flex:1; padding:26px 24px; transition:margin-left var(--trans); }

    /* Alert */
    .alert {
      padding:12px 18px; border-radius:8px; font-size:.82rem;
      font-weight:700; margin-bottom:16px; animation:fadeUp .4s both;
    }
    .alert-success { background:#e8f5e9; color:#1a8a4a; border:1px solid #c8e6c9; }
    .alert-error   { background:#fce4ec; color:#c0392b; border:1px solid #f8bbd0; }

    /* Topbar */
    .topbar {
      display:flex; align-items:center;
      background:#fff; border-radius:50px;
      padding:0 18px; height:46px; gap:10px;
      margin-bottom:24px; box-shadow:var(--shadow);
      animation:fadeUp .5s .05s both;
    }
    .topbar svg { width:18px; height:18px; color:#aaa; flex-shrink:0; }
    .topbar input {
      flex:1; border:none; outline:none;
      font-family:'Nunito',sans-serif; font-size:.85rem;
      color:var(--text); background:transparent;
    }
    .topbar input::placeholder { color:#bbb; }
    #searchResultArea { transition: opacity .15s ease; }
    #searchResultArea.loading-search { opacity: .55; }
    .btn-search {
      background:var(--btn-primary); color:#fff;
      border:none; border-radius:20px;
      padding:6px 16px; font-family:'Nunito',sans-serif;
      font-size:.78rem; font-weight:700; cursor:pointer;
      transition:background var(--trans);
    }
    .btn-search:hover { background:#222; }

    /* Header */
    .content-header {
      display:flex; align-items:center;
      justify-content:space-between;
      margin-bottom:16px; animation:fadeUp .5s .08s both;
    }
    .content-title { font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:700; color:var(--text); }

    /* Books grid */
    .books-grid {
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(140px, 1fr));
      gap:14px; animation:fadeUp .5s .12s both;
    }
    .book-card {
      background:var(--card); border-radius:10px;
      overflow:hidden; box-shadow:var(--shadow);
      transition:box-shadow var(--trans), transform var(--trans);
      animation:fadeUp .4s both;
    }
    .book-card:hover { box-shadow:0 6px 18px rgba(0,0,0,.13); transform:translateY(-3px); }

    .book-img {
      width:100%; aspect-ratio:2/3;
      display:flex; align-items:center; justify-content:center;
      overflow:hidden; position:relative;
    }
    .book-img img { width:100%; height:100%; object-fit:cover; display:block; }
    .book-img svg { width:28px; height:28px; color:#bbb; }
    .stok-badge {
      position:absolute; bottom:7px; right:7px;
      background:rgba(0,0,0,.55); color:#fff;
      font-size:.6rem; font-weight:800;
      padding:2px 8px; border-radius:20px;
      backdrop-filter:blur(3px); letter-spacing:.03em;
    }
    .stok-habis { background:rgba(220,38,38,.8) !important; }
    .btn-habis {
      background:#d1d5db !important; color:#6b7280 !important;
      cursor:not-allowed !important; opacity:.8;
    }
    .col-a { background:linear-gradient(135deg,#f5a623,#d4820a); }
    .col-b { background:linear-gradient(135deg,#e74c3c,#922b21); }
    .col-c { background:linear-gradient(135deg,#9b59b6,#6c3483); }
    .col-d { background:linear-gradient(135deg,#2ecc71,#1a8a4a); }
    .col-e { background:linear-gradient(135deg,#3498db,#1a5276); }
    .col-f { background:linear-gradient(135deg,#e91e63,#880e4f); }
    .col-g { background:linear-gradient(135deg,#ff5722,#bf360c); }
    .col-h { background:linear-gradient(135deg,#607d8b,#263238); }

    .book-info { padding:10px; }
    .book-title { font-size:.75rem; font-weight:800; color:var(--text); margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .book-author { font-size:.62rem; color:var(--muted); margin-bottom:8px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

    /* Tombol Pinjam — full lebar */
    .btn-pinjam {
      display:block; width:100%;
      padding:7px 0; border-radius:6px; border:none;
      background:var(--btn-pinjam); color:#fff;
      font-family:'Nunito',sans-serif; font-size:.68rem;
      font-weight:700; cursor:pointer; text-align:center;
      transition:opacity var(--trans), transform .12s;
      letter-spacing:.02em;
    }
    .btn-pinjam:hover  { opacity:.88; }
    .btn-pinjam:active { transform:scale(.96); }

    /* Empty state */
    .empty-state {
      text-align:center; padding:60px 20px;
      color:var(--muted); animation:fadeUp .5s both;
    }
    .empty-state svg { width:56px; height:56px; margin-bottom:12px; opacity:.35; }
    .empty-state p { font-size:.88rem; font-weight:600; }

    /* ── MODAL PINJAM ── */
    .modal-overlay {
      display:none; position:fixed; inset:0;
      background:rgba(0,0,0,.5); z-index:500;
      align-items:center; justify-content:center;
    }
    .modal-overlay.open { display:flex; }
    .modal {
      background:#fff; border-radius:14px;
      width:100%; max-width:440px;
      max-height:90vh; overflow-y:auto;
      box-shadow:0 20px 60px rgba(0,0,0,.25);
      animation:modalIn .25s cubic-bezier(.22,1,.36,1) both;
      padding:28px 28px 24px; margin:16px;
    }
    @keyframes modalIn {
      from { opacity:0; transform:scale(.94) translateY(10px); }
      to   { opacity:1; transform:scale(1) translateY(0); }
    }
    .modal-header {
      display:flex; align-items:center;
      justify-content:space-between; margin-bottom:6px;
    }
    .modal-title { font-family:'Cormorant Garamond',serif; font-size:1.3rem; font-weight:700; color:var(--text); }
    .modal-book-info { font-size:.78rem; color:var(--muted); font-weight:600; margin-bottom:20px; }
    .modal-close {
      width:32px; height:32px; border-radius:50%;
      border:none; background:#f0f0f5;
      cursor:pointer; display:flex; align-items:center; justify-content:center;
      transition:background var(--trans); flex-shrink:0;
    }
    .modal-close:hover { background:#e0e0ea; }
    .modal-close svg { width:16px; height:16px; color:var(--muted); }

    .form-group { margin-bottom:14px; }
    .form-label { font-size:.76rem; font-weight:700; color:var(--muted); margin-bottom:5px; display:block; text-transform:uppercase; letter-spacing:.05em; }
    .form-input {
      width:100%; padding:10px 14px; border-radius:8px;
      border:1.5px solid #e4e5f0;
      font-family:'Nunito',sans-serif; font-size:.85rem;
      color:var(--text); background:#fff;
      outline:none; transition:border-color var(--trans);
    }
    .form-input:focus { border-color:var(--btn-pinjam); }

    /* ── PILIH ANGGOTA ── */
    .anggota-picker { display:flex; gap:8px; }
    .anggota-picker .form-input { background:#f6f7fb; cursor:default; }
    .btn-pilih-anggota {
      flex-shrink:0; white-space:nowrap;
      padding:0 14px; border-radius:8px; border:1.5px solid var(--btn-pinjam);
      background:#fff; color:var(--btn-pinjam);
      font-family:'Nunito',sans-serif; font-size:.78rem; font-weight:700;
      cursor:pointer; transition:all var(--trans);
    }
    .btn-pilih-anggota:hover { background:var(--btn-pinjam); color:#fff; }
    .anggota-hint { font-size:.7rem; color:var(--muted); margin-top:4px; }
    .anggota-hint.warn { color:#c0392b; font-weight:700; }

    .modal-anggota { max-width:400px; }
    .anggota-search {
      display:flex; align-items:center; gap:8px;
      border:1.5px solid #e4e5f0; border-radius:8px;
      padding:8px 12px; margin-bottom:14px;
    }
    .anggota-search svg { width:16px; height:16px; color:#aaa; flex-shrink:0; }
    .anggota-search input {
      flex:1; border:none; outline:none;
      font-family:'Nunito',sans-serif; font-size:.85rem; color:var(--text);
    }
    .anggota-list { max-height:340px; overflow-y:auto; display:flex; flex-direction:column; gap:6px; }
    .anggota-item {
      display:flex; align-items:center; gap:10px;
      padding:9px 10px; border-radius:8px; border:1.5px solid #eceef5;
      background:#fff; cursor:pointer; text-align:left;
      transition:all var(--trans); width:100%;
      font-family:'Nunito',sans-serif;
    }
    .anggota-item:hover { border-color:var(--btn-pinjam); background:#f4f8ff; }
    .anggota-avatar {
      width:34px; height:34px; border-radius:50%; flex-shrink:0;
      background:linear-gradient(135deg,#3498db,#1a5276);
      color:#fff; font-weight:800; font-size:.78rem;
      display:flex; align-items:center; justify-content:center;
      overflow:hidden;
    }
    .anggota-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
    .anggota-info { min-width:0; flex:1; }
    .anggota-nama { font-size:.82rem; font-weight:700; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .anggota-meta { font-size:.68rem; color:var(--muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .anggota-empty { text-align:center; padding:30px 10px; color:var(--muted); font-size:.8rem; font-weight:600; }

    /* Avatar kecil di sebelah input "Nama Peminjam" setelah anggota dipilih */
    .anggota-picker { align-items:center; }
    .selected-avatar {
      width:38px; height:38px; border-radius:50%; flex-shrink:0;
      overflow:hidden; display:none;
      background:linear-gradient(135deg,#3498db,#1a5276);
      color:#fff; font-weight:800; font-size:.85rem;
      align-items:center; justify-content:center;
    }
    .selected-avatar.show { display:flex; }
    .selected-avatar img { width:100%; height:100%; object-fit:cover; display:block; }

    .modal-footer { display:flex; gap:10px; margin-top:20px; }
    .btn-submit {
      flex:1; padding:11px; border-radius:8px; border:none;
      background:var(--btn-pinjam); color:#fff;
      font-family:'Nunito',sans-serif; font-size:.85rem;
      font-weight:700; cursor:pointer;
      transition:background var(--trans);
    }
    .btn-submit:hover { background:#1d4ed8; }
    .btn-cancel {
      padding:11px 20px; border-radius:8px;
      border:1.5px solid #e4e5f0; background:#fff;
      font-family:'Nunito',sans-serif; font-size:.85rem;
      font-weight:700; color:var(--muted); cursor:pointer;
      transition:all var(--trans);
    }
    .btn-cancel:hover { border-color:var(--btn-pinjam); color:var(--btn-pinjam); }

    /* Animations */
    @keyframes fadeUp {
      from { opacity:0; transform:translateY(14px); }
      to   { opacity:1; transform:translateY(0); }
    }
    .book-card:nth-child(1)  { animation-delay:.06s; }
    .book-card:nth-child(2)  { animation-delay:.10s; }
    .book-card:nth-child(3)  { animation-delay:.14s; }
    .book-card:nth-child(4)  { animation-delay:.18s; }
    .book-card:nth-child(5)  { animation-delay:.22s; }
    .book-card:nth-child(6)  { animation-delay:.26s; }
    .book-card:nth-child(n+7){ animation-delay:.30s; }

    @media (max-width:860px) {
      :root { --sidebar-w:170px; }
      .avatar-circle { width:76px; height:76px; }
    }
    @media (max-width: 640px) {
      .sidebar { transform:translateX(-100%); }
      .sidebar.open { transform:translateX(0); }
      .sidebar-toggle { display:flex; }
      .main { margin-left:0; padding:16px; padding-top:60px; }
    }
  </style>
</head>
<body>

<button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <line x1="3" y1="6" x2="21" y2="6"/>
    <line x1="3" y1="12" x2="21" y2="12"/>
    <line x1="3" y1="18" x2="21" y2="18"/>
  </svg>
</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="avatar-wrap" onclick="openProfilModal()" title="Edit Profil">
    <div class="avatar-circle">
      <?php if ($admin_foto && file_exists($admin_foto)): ?>
        <img src="<?= htmlspecialchars($admin_foto) ?>?v=<?= filemtime($admin_foto) ?>" alt="Admin"/>
      <?php else: ?>
        <svg class="default-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
      <?php endif; ?>
    </div>
    <div class="avatar-overlay">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
      </svg>
    </div>
  </div>

  <div class="admin-name-label">Halo, <?= htmlspecialchars($admin_name) ?></div>

  <a class="sidebar-btn" href="dashboard.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
    Dashboard
  </a>
  <a class="sidebar-btn" href="halaman_admin.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Perbarui Buku
  </a>
  <a class="sidebar-btn" href="kelola_banner.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><path d="M21 15l-5-5L5 19"/></svg>
    Kelola Banner
  </a>
  <a class="sidebar-btn" href="daftar_anggota.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    Daftar Anggota
  </a>
  <a class="sidebar-btn active" href="pinjam_buku.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
      <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
    </svg>
    Pinjam Buku
  </a>
  <?php
    $cnt_aju_badge = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'"))['c'] ?? 0);
  ?>
  <a class="sidebar-btn" href="pengajuan_buku.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
    Pengajuan Buku
    <?php if ($cnt_aju_badge > 0): ?>
      <span style="margin-left:auto;background:rgba(245,158,11,.2);color:#fbbf24;border:1px solid rgba(245,158,11,.4);font-size:.65rem;font-weight:800;padding:2px 7px;border-radius:20px;"><?= $cnt_aju_badge ?></span>
    <?php endif; ?>
  </a>
  <a class="sidebar-btn" href="telah_dipinjam.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M9 11l3 3L22 4"/>
      <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
    </svg>
    Telah Dipinjam
  </a>
  <a class="sidebar-btn" href="pengaturan_denda.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    Pengaturan Denda
  </a>
  <a class="sidebar-btn" href="pengaturan_musik.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>
      </svg>
      Pengaturan Musik
    </a>
  <a class="sidebar-btn" href="beranda.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Kembali
  </a>
</aside>

<!-- MAIN -->
<main class="main">

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type ?>">
    <?= $msg_type === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>

  <!-- Search -->
  <form method="GET" action="" id="searchForm">
    <div class="topbar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
      </svg>
      <input type="text" name="q" id="searchInput" autocomplete="off" placeholder="Cari buku untuk dipinjam…"
             value="<?= htmlspecialchars($search) ?>"/>
      <?php if ($search): ?>
      <a href="pinjam_buku.php" id="searchResetBtn" style="font-size:.75rem;color:var(--muted);text-decoration:none;white-space:nowrap;">✕ Reset</a>
      <?php endif; ?>
    </div>
  </form>

  <div id="searchResultArea">
  <?php ob_start(); ?>
  <!-- Header -->
  <div class="content-header">
    <div class="content-title">
      Pinjam Buku
      <?php if ($search): ?><span style="font-size:.9rem;color:var(--muted);font-family:'Nunito',sans-serif;"> — "<?= htmlspecialchars($search) ?>"</span><?php endif; ?>
    </div>
  </div>

  <!-- Grid buku -->
  <?php if (empty($buku_list)): ?>
  <div class="empty-state">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
      <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
    </svg>
    <p><?= $search ? 'Tidak ada buku yang cocok.' : 'Belum ada buku yang ditambahkan.' ?></p>
  </div>
  <?php else: ?>
  <div class="books-grid">
    <?php foreach ($buku_list as $i => $buku):
      $col = $colors[$i % count($colors)];
      $judul_js = htmlspecialchars(addslashes($buku['judul']), ENT_QUOTES);
      $penulis_js = htmlspecialchars(addslashes($buku['penulis'] ?? '-'), ENT_QUOTES);
    ?>
    <div class="book-card">
      <div class="book-img <?= $buku['gambar'] ? '' : $col ?>">
        <?php if ($buku['gambar'] && file_exists($buku['gambar'])): ?>
          <img src="<?= htmlspecialchars($buku['gambar']) ?>" alt="<?= htmlspecialchars($buku['judul']) ?>">
        <?php else: ?>
          <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1" opacity=".5">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
          </svg>
        <?php endif; ?>
        <div class="stok-badge <?= (int)$buku['stok'] <= 0 ? 'stok-habis' : '' ?>">Stok: <?= (int)$buku['stok'] ?></div>
      </div>
      <div class="book-info">
        <div class="book-title" title="<?= htmlspecialchars($buku['judul']) ?>"><?= htmlspecialchars($buku['judul']) ?></div>
        <div class="book-author"><?= htmlspecialchars($buku['penulis'] ?? '-') ?></div>
        <button class="btn-pinjam"
          onclick="bukaPinjamModal(<?= $buku['id'] ?>, '<?= $judul_js ?>', '<?= $penulis_js ?>')">
          📖 Pinjam Buku
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php
  $search_result_html = ob_get_clean();
  if ($is_ajax) {
      ob_end_clean();
      header('Content-Type: text/html; charset=utf-8');
      echo $search_result_html;
      exit;
  }
  echo $search_result_html;
  ?>
  </div>

</main>

<!-- ═══ MODAL PINJAM BUKU ═══ -->
<div class="modal-overlay" id="pinjamModalOverlay">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Pinjam Buku</div>
      <button class="modal-close" onclick="tutupPinjamModal()" aria-label="Tutup">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <div class="modal-book-info" id="modalBookInfo"></div>

    <form method="POST" action="pinjam_buku.php<?= $search ? '?q=' . urlencode($search) : '' ?>">
      <input type="hidden" name="action" value="pinjam"/>
      <input type="hidden" name="buku_id" id="inputBukuId"/>

      <div class="form-group">
        <label class="form-label" for="inputNama">Nama Peminjam</label>
        <div class="anggota-picker">
          <div class="selected-avatar" id="selectedAvatar"></div>
          <input type="text" id="inputNama" class="form-input"
                 placeholder="Belum ada anggota dipilih…" readonly/>
          <button type="button" class="btn-pilih-anggota" onclick="bukaAnggotaModal()">👤 Pilih Anggota</button>
        </div>
        <input type="hidden" name="user_id" id="inputUserId"/>
        <div class="anggota-hint" id="anggotaHint">Hanya anggota yang sudah punya akun yang bisa dipilih.</div>
      </div>

      <div class="form-group">
        <label class="form-label" for="inputBatasKembali">Tanggal Pengembalian</label>
        <input type="date" id="inputBatasKembali" name="batas_kembali" class="form-input" required/>
        <div style="font-size:.7rem;color:var(--muted);margin-top:4px;">Tanggal mulai pinjam dicatat otomatis saat ini.</div>
      </div>

      <div class="modal-footer">
        <button type="submit" class="btn-submit">📖 Konfirmasi Pinjam</button>
        <button type="button" class="btn-cancel" onclick="tutupPinjamModal()">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL PILIH ANGGOTA ═══ -->
<div class="modal-overlay" id="anggotaModalOverlay">
  <div class="modal modal-anggota">
    <div class="modal-header">
      <div class="modal-title">Pilih Anggota</div>
      <button class="modal-close" onclick="tutupAnggotaModal()" aria-label="Tutup">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>

    <div class="anggota-search">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
      </svg>
      <input type="text" id="anggotaSearchInput" placeholder="Cari nama atau no. anggota…" oninput="filterAnggota()"/>
    </div>

    <div class="anggota-list" id="anggotaListWrap"></div>
  </div>
</div>

<!-- ═══════════ MODAL EDIT PROFIL ADMIN ═══════════ -->
<div class="modal-overlay" id="profilModalOverlay">
  <div class="modal" style="max-width:380px;">
    <div class="modal-header">
      <div class="modal-title">Edit Profil</div>
      <button class="modal-close" onclick="closeProfilModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="POST" action="update_profil_admin.php" enctype="multipart/form-data">
      <input type="hidden" name="redirect" value="pinjam_buku.php"/>

      <!-- Preview foto -->
      <div class="img-preview-wrap" style="aspect-ratio:1/1;max-width:160px;margin:0 auto 18px;border-radius:50%;" onclick="document.getElementById('inputFotoAdmin').click()">
        <?php if ($admin_foto && file_exists($admin_foto)): ?>
          <img id="profilPreviewImg" src="<?= htmlspecialchars($admin_foto) ?>" alt="Foto" style="display:block;border-radius:50%;"/>
          <div class="upload-placeholder" id="profilUploadPlaceholder" style="display:none;">
        <?php else: ?>
          <img id="profilPreviewImg" src="" alt="Foto" style="display:none;border-radius:50%;"/>
          <div class="upload-placeholder" id="profilUploadPlaceholder">
        <?php endif; ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
              <circle cx="12" cy="13" r="4"/>
            </svg>
            <span style="font-size:.7rem;">Upload Foto</span>
          </div>
      </div>
      <input type="file" id="inputFotoAdmin" name="foto_admin" accept="image/*" style="display:none"/>

      <div class="form-group">
        <label class="form-label">Nama Tampilan</label>
        <input class="form-input" type="text" name="display_name"
               value="<?= htmlspecialchars($admin_name) ?>"
               placeholder="Nama yang ditampilkan" required/>
      </div>

      <div class="modal-footer">
        <button type="submit" class="btn-save">Simpan</button>
        <button type="button" class="btn-cancel" onclick="closeProfilModal()">Batal</button>
      </div>
    </form>
  </div>
</div>

<script>
// ─── Data anggota (dari akun yang terdaftar & disetujui) ───
const anggotaData = <?= json_encode($anggota_list, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

// ─── Sidebar toggle ───
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

// ─── Modal Pinjam ───
function bukaPinjamModal(bukuId, judul, penulis) {
  document.getElementById('inputBukuId').value = bukuId;
  document.getElementById('modalBookInfo').textContent = `"${judul}" oleh ${penulis}`;

  // Batas kembali default 7 hari dari sekarang (format date)
  const tujuhHari = new Date();
  tujuhHari.setDate(tujuhHari.getDate() + 7);
  const localDate = tujuhHari.toISOString().slice(0, 10);
  document.getElementById('inputBatasKembali').value = localDate;

  // Reset pilihan anggota tiap kali modal dibuka
  document.getElementById('inputNama').value = '';
  document.getElementById('inputUserId').value = '';
  const selectedAvatar = document.getElementById('selectedAvatar');
  selectedAvatar.classList.remove('show');
  selectedAvatar.innerHTML = '';
  const hint = document.getElementById('anggotaHint');
  hint.textContent = 'Hanya anggota yang sudah punya akun yang bisa dipilih.';
  hint.classList.remove('warn');

  document.getElementById('pinjamModalOverlay').classList.add('open');
}

function tutupPinjamModal() {
  document.getElementById('pinjamModalOverlay').classList.remove('open');
}

document.getElementById('pinjamModalOverlay').addEventListener('click', function(e) {
  if (e.target === this) tutupPinjamModal();
});

// Validasi: wajib pilih anggota sebelum submit form pinjam
document.querySelector('#pinjamModalOverlay form').addEventListener('submit', function(e) {
  const userId = document.getElementById('inputUserId').value;
  if (!userId) {
    e.preventDefault();
    const hint = document.getElementById('anggotaHint');
    hint.textContent = '⚠️ Pilih anggota terlebih dahulu sebelum konfirmasi pinjam.';
    hint.classList.add('warn');
  }
});

// ─── Modal Pilih Anggota ───
function renderAnggotaList(list) {
  const wrap = document.getElementById('anggotaListWrap');

  if (!list.length) {
    wrap.innerHTML = `<div class="anggota-empty">Tidak ada anggota yang cocok.</div>`;
    return;
  }

  wrap.innerHTML = list.map(a => {
    const inisial = (a.full_name || '?').trim().charAt(0).toUpperCase();
    const meta = [a.no_anggota, a.kelas].filter(Boolean).join(' • ');
    const avatarInner = (a.foto_ok && a.foto)
      ? `<img src="${escapeAttr(a.foto)}" alt="">`
      : inisial;
    return `
      <button type="button" class="anggota-item" onclick="pilihAnggota(${a.id}, '${escapeAttr(a.full_name)}', '${escapeAttr(a.foto_ok ? a.foto : '')}')">
        <div class="anggota-avatar">${avatarInner}</div>
        <div class="anggota-info">
          <div class="anggota-nama">${escapeHtml(a.full_name)}</div>
          <div class="anggota-meta">${escapeHtml(meta || a.username || '')}</div>
        </div>
      </button>
    `;
  }).join('');
}

function escapeHtml(str) {
  const d = document.createElement('div');
  d.textContent = str ?? '';
  return d.innerHTML;
}
function escapeAttr(str) {
  return (str ?? '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

function filterAnggota() {
  const q = document.getElementById('anggotaSearchInput').value.trim().toLowerCase();
  if (!q) { renderAnggotaList(anggotaData); return; }
  const filtered = anggotaData.filter(a =>
    (a.full_name || '').toLowerCase().includes(q) ||
    (a.no_anggota || '').toLowerCase().includes(q) ||
    (a.username || '').toLowerCase().includes(q)
  );
  renderAnggotaList(filtered);
}

function bukaAnggotaModal() {
  document.getElementById('anggotaSearchInput').value = '';
  renderAnggotaList(anggotaData);
  document.getElementById('anggotaModalOverlay').classList.add('open');
  setTimeout(() => document.getElementById('anggotaSearchInput').focus(), 120);
}

function tutupAnggotaModal() {
  document.getElementById('anggotaModalOverlay').classList.remove('open');
}

function pilihAnggota(id, nama, foto) {
  document.getElementById('inputUserId').value = id;
  document.getElementById('inputNama').value = nama;

  const avatarEl = document.getElementById('selectedAvatar');
  if (foto) {
    avatarEl.innerHTML = `<img src="${foto.replace(/"/g, '&quot;')}" alt="">`;
  } else {
    avatarEl.textContent = (nama || '?').trim().charAt(0).toUpperCase();
  }
  avatarEl.classList.add('show');

  const hint = document.getElementById('anggotaHint');
  hint.textContent = '✅ Anggota terpilih, siap dipinjamkan.';
  hint.classList.remove('warn');
  tutupAnggotaModal();
}

document.getElementById('anggotaModalOverlay').addEventListener('click', function(e) {
  if (e.target === this) tutupAnggotaModal();
});

// ─── Modal Edit Profil ───
function openProfilModal() {
  document.getElementById('profilModalOverlay').classList.add('open');
}
function closeProfilModal() {
  document.getElementById('profilModalOverlay').classList.remove('open');
  document.getElementById('inputFotoAdmin').value = '';
}
document.getElementById('profilModalOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeProfilModal();
});
document.getElementById('inputFotoAdmin').addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = ev => {
    const img = document.getElementById('profilPreviewImg');
    img.src = ev.target.result;
    img.style.display = 'block';
    document.getElementById('profilUploadPlaceholder').style.display = 'none';
  };
  reader.readAsDataURL(file);
});

// ─── Live Search (ketik langsung cari, tanpa tombol) ───
(function initLiveSearch() {
  const form   = document.getElementById('searchForm');
  const input  = document.getElementById('searchInput');
  const result = document.getElementById('searchResultArea');
  if (!form || !input || !result) return;

  let debounceTimer = null;
  let currentRequest = null;

  form.addEventListener('submit', e => e.preventDefault());

  function runSearch(query) {
    if (currentRequest) currentRequest.abort();
    const controller = new AbortController();
    currentRequest = controller;

    const url = 'pinjam_buku.php?ajax=1&q=' + encodeURIComponent(query);
    result.classList.add('loading-search');

    fetch(url, { signal: controller.signal })
      .then(r => r.text())
      .then(html => {
        result.innerHTML = html;
        result.classList.remove('loading-search');
        const newUrl = 'pinjam_buku.php' + (query ? '?q=' + encodeURIComponent(query) : '');
        history.replaceState(null, '', newUrl);
      })
      .catch(err => {
        if (err.name !== 'AbortError') result.classList.remove('loading-search');
      });
  }

  input.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    const query = input.value;
    debounceTimer = setTimeout(() => runSearch(query), 300);
  });

  document.addEventListener('click', e => {
    const resetBtn = e.target.closest('#searchResetBtn');
    if (!resetBtn) return;
    e.preventDefault();
    input.value = '';
    runSearch('');
  });
})();
</script>
</body>
</html>
<?php ob_end_flush(); ?>