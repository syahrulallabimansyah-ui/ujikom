<?php
// pengaturan_denda.php — Pengaturan Tarif Denda Keterlambatan
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: sign_in.php');
    exit;
}

require_once 'db.php';
// ─── Hitung badge sidebar (pending anggota & pengajuan buku) ───
$cnt_p_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='member' AND status='pending'");
$pending_count = $cnt_p_q ? (int)(mysqli_fetch_assoc($cnt_p_q)['c'] ?? 0) : 0;
$cnt_a_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'");
$cnt_aju_badge = $cnt_a_q ? (int)(mysqli_fetch_assoc($cnt_a_q)['c'] ?? 0) : 0;
$sidebar_badges_loaded = true;

assert($conn instanceof mysqli);
mysqli_set_charset($conn, 'utf8mb4');

// Samakan zona waktu PHP & MySQL supaya konsisten dengan WIB
date_default_timezone_set('Asia/Jakarta');
mysqli_query($conn, "SET time_zone = '+07:00'");

$page_title = 'Pengaturan Denda – AKSA NOVA';
$msg        = '';
$msg_type   = '';

// ─── Profil admin ───
$profil_res = mysqli_query($conn, 'SELECT * FROM admin_profile LIMIT 1');
$profil     = $profil_res ? mysqli_fetch_assoc($profil_res) : null;
$admin_name = $profil['display_name'] ?? ($_SESSION['user_name'] ?? 'Admin');
$admin_foto = $profil['foto'] ?? '';

// ─────────────────────────────────────────────
//  Helper: ambil nilai pengaturan
// ─────────────────────────────────────────────
if (!function_exists('getSetting')) {
    function getSetting(mysqli $db, string $kunci, string $default = ''): string {
        $k   = mysqli_real_escape_string($db, $kunci);
        $res = mysqli_query($db, "SELECT nilai FROM pengaturan WHERE kunci='$k' LIMIT 1");
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row ? $row['nilai'] : $default;
    }
}

if (!function_exists('setSetting')) {
    function setSetting(mysqli $db, string $kunci, string $nilai): void {
        $k = mysqli_real_escape_string($db, $kunci);
        $v = mysqli_real_escape_string($db, $nilai);
        mysqli_query($db,
            "INSERT INTO pengaturan (kunci, nilai) VALUES ('$k','$v')
             ON DUPLICATE KEY UPDATE nilai='$v', updated_at=NOW()"
        );
    }
}

// ─────────────────────────────────────────────
//  AKSI: Simpan pengaturan
// ─────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'simpan_pengaturan') {
    $denda_per_hari    = (int)($_POST['denda_per_hari']    ?? 1000);
    $denda_aktif       = isset($_POST['denda_aktif']) ? '1' : '0';
    $grace_period      = max(0, (int)($_POST['grace_period'] ?? 0));

    if ($denda_per_hari < 0) {
        $msg = 'Tarif denda tidak boleh negatif.';
        $msg_type = 'error';
    } else {
        setSetting($conn, 'denda_per_hari',        (string)$denda_per_hari);
        setSetting($conn, 'denda_aktif',            $denda_aktif);
        setSetting($conn, 'denda_grace_period',     (string)$grace_period);

        $msg = 'Pengaturan denda berhasil disimpan!';
        $msg_type = 'success';
    }
}

// AKSI: Tandai denda lunas
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'tandai_lunas') {
    $pem_id = (int)($_POST['pem_id'] ?? 0);
    if ($pem_id > 0) {
        mysqli_query($conn,
            "UPDATE peminjaman SET status_denda='lunas' WHERE id=$pem_id AND status_denda='belum_bayar'"
        );
        $msg = 'Denda ditandai sebagai lunas.';
        $msg_type = 'success';
    }
}

// Ambil pengaturan saat ini
$denda_per_hari  = (int)getSetting($conn, 'denda_per_hari',    '1000');
$denda_aktif     = getSetting($conn, 'denda_aktif',    '1') === '1';
$grace_period    = (int)getSetting($conn, 'denda_grace_period', '0');

// Statistik denda
$stat_belum = (int)(mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM peminjaman WHERE status_denda='belum_bayar'"))['c'] ?? 0);
$stat_lunas = (int)(mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM peminjaman WHERE status_denda='lunas'"))['c'] ?? 0);
$total_denda_belum = (int)(mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(denda),0) AS t FROM peminjaman WHERE status_denda='belum_bayar'"))['t'] ?? 0);
$total_denda_lunas = (int)(mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(denda),0) AS t FROM peminjaman WHERE status_denda='lunas'"))['t'] ?? 0);

// Daftar peminjaman yang punya denda
$search = trim($_GET['q'] ?? '');
$where_search = '';
if ($search !== '') {
    $s = mysqli_real_escape_string($conn, $search);
    $where_search = " AND (b.judul LIKE '%$s%' OR p.nama_peminjam LIKE '%$s%')";
}

$denda_list = [];
$res = mysqli_query($conn,
    "SELECT p.*, b.judul, b.penulis
     FROM peminjaman p
     INNER JOIN buku b ON b.id = p.buku_id
     WHERE p.denda > 0 $where_search
     ORDER BY p.waktu_kembali DESC, p.batas_kembali DESC"
);
while ($row = mysqli_fetch_assoc($res)) {
    $denda_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Cormorant+Garamond:wght@700&display=swap" rel="stylesheet"/>
  <?php require_once 'settings_include.php'; ?>
  <style>
    :root {
      --sidebar-bg:  #10151b;
      --accent:      #d8b878;
      --btn-primary: linear-gradient(135deg,#d8b878,#c8a060);
      --text:        #eef3f4;
      --muted:       rgba(238,243,244,.65);
      --bg:          #090c10;
      --card:        #121820;
      --border-color:rgba(216,184,120,.18);
      --card-border: rgba(216,184,120,.12);
      --radius:      10px;
      --sidebar-w:   204px;
      --trans:       .2s cubic-bezier(.22,1,.36,1);
      --shadow:      0 2px 12px rgba(0,0,0,.3);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Nunito', sans-serif; background: var(--bg); display: flex; min-height: 100vh; animation: bodyIn .4s ease both; }
    @keyframes bodyIn { from { opacity:0; } to { opacity:1; } }

    /* SIDEBAR */
    /* ── SIDEBAR ── */
    .sidebar {
      width: var(--sidebar-w);
      background: var(--sidebar-bg, #10151b);
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 28px 20px 16px;
      position: fixed;
      top: 0; left: 0; bottom: 0;
      z-index: 100;
      transition: transform var(--trans);
      overflow: hidden;
    }
    .sidebar-header { flex-shrink: 0; display:flex; flex-direction:column; align-items:center; width:100%; }
    .sidebar-nav {
      width: 100%;
      flex: 1 1 auto;
      min-height: 0;
      overflow-y: auto;
      overflow-x: hidden;
      display: flex;
      flex-direction: column;
      padding-right: 2px;
      scrollbar-width: thin;
      scrollbar-color: rgba(255,255,255,.35) transparent;
    }
    .sidebar-nav::-webkit-scrollbar { width: 5px; }
    .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.3); border-radius: 10px; }
    .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
    .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:90; }
    .sidebar-overlay.open { display:block; }
    .sidebar-toggle {
      display:none; position:fixed; top:14px; left:14px; z-index:200;
      width:42px; height:42px; border-radius:10px; cursor:pointer;
      align-items:center; justify-content:center;
      background:#161e27; border:1.5px solid var(--accent,#d8b878);
      box-shadow:0 4px 16px rgba(0,0,0,.45); color:var(--accent,#d8b878);
      transition:all .2s;
    }
    .sidebar-toggle svg { width:22px; height:22px; stroke:var(--accent,#d8b878); color:var(--accent,#d8b878); stroke-width:2.3px; }
    .sidebar-toggle:hover { background:rgba(216,184,120,.18); border-color:var(--accent2,#f0d9a8); transform:scale(1.05); }
    .sidebar-toggle:active { transform:scale(0.92); }
    .avatar-wrap { margin-bottom:14px; }
    .avatar-circle { width:80px; height:80px; max-width:80px; max-height:80px; border-radius:50%; background:#c0c0c8; overflow:hidden; border:3px solid rgba(255,255,255,.25); display:flex; align-items:center; justify-content:center; }
    .avatar-circle img { width:100%; height:100%; object-fit:cover; display:block; }
    .avatar-circle .default-icon { width:52px; height:52px; color:#888; }
    .admin-name-label { color:var(--text,#eef3f4); font-size:.95rem; font-weight:700; margin-bottom:8px; text-align:center; }
    .total-badge {
      background: rgba(216,184,120,.16); color:var(--accent,#d8b878);
      border: 1px solid rgba(216,184,120,.3);
      font-size:.72rem; font-weight:700;
      padding:4px 12px; border-radius:50px;
      margin-bottom:20px; text-align:center;
    }

    .sidebar-btn {
      width:100%; display:flex; align-items:center; gap:11px;
      padding:10px 14px; border-radius:9px;
      border:1px solid rgba(216,184,120,.16);
      background:rgba(255,255,255,.05); color:var(--text,#eef3f4);
      font-family:inherit; font-size:.83rem; font-weight:700;
      cursor:pointer; margin-bottom:7px;
      transition:all var(--trans);
      text-align:left; text-decoration:none;
      flex-shrink:0; box-sizing:border-box;
    }
    .sidebar-btn svg { width:17px; height:17px; flex-shrink:0; color:var(--accent,#d8b878); stroke:var(--accent,#d8b878); stroke-width:2px; transition:stroke .2s, transform .2s; }
    .sidebar-btn:hover { background:rgba(216,184,120,.16); border-color:var(--accent,#d8b878); color:#fff; transform:translateX(3px); }
    .sidebar-btn:hover svg { color:#fff; stroke:#fff; transform:scale(1.1); }
    .sidebar-btn.active {
      background:linear-gradient(135deg,#d8b878,#c8a060);
      border-color:var(--accent,#d8b878);
      color:#121820; font-weight:800;
      box-shadow:0 4px 14px rgba(216,184,120,.35);
    }
    .sidebar-btn.active svg { color:#121820; stroke:#121820; stroke-width:2.2px; }

    /* MAIN */
    .main { view-transition-name:app-main; margin-left:var(--sidebar-w); flex:1; padding:26px 24px; }
    .alert { padding:12px 18px; border-radius:8px; font-size:.82rem; font-weight:700; margin-bottom:16px; animation:fadeUp .4s both; }
    .alert-success { background:rgba(5,150,105,.15); color:#4ade80; border:1px solid rgba(5,150,105,.3); }
    .alert-error   { background:rgba(220,38,38,.15); color:#f87171; border:1px solid rgba(220,38,38,.3); }

    .page-title { font-family:'Cormorant Garamond',serif; font-size:1.7rem; font-weight:700; color:var(--text); margin-bottom:20px; animation:fadeUp .4s both; }

    /* STAT CARDS */
    .stat-row { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:14px; margin-bottom:24px; animation:fadeUp .4s .05s both; }
    .stat-card { background:var(--card,#121820); border:1px solid var(--card-border,rgba(216,184,120,.12)); border-radius:12px; padding:16px 18px; box-shadow:var(--shadow); }
    .stat-card .stat-label { font-size:.68rem; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px; }
    .stat-card .stat-val { font-size:1.4rem; font-weight:800; color:var(--text); }
    .stat-card .stat-sub { font-size:.7rem; color:var(--muted); font-weight:600; margin-top:2px; }
    .stat-card.red { border-left:4px solid #ef4444; }
    .stat-card.green { border-left:4px solid #10b981; }
    .stat-card.blue { border-left:4px solid #3b82f6; }
    .stat-card.purple { border-left:4px solid #8b5cf6; }

    /* FORM PENGATURAN */
    .settings-card { background:var(--card,#121820); border:1px solid var(--card-border,rgba(216,184,120,.12)); border-radius:14px; padding:24px; box-shadow:var(--shadow); margin-bottom:24px; animation:fadeUp .4s .08s both; }
    .settings-title { font-size:1rem; font-weight:800; color:var(--text); margin-bottom:16px; display:flex; align-items:center; gap:8px; }
    .form-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:14px; }
    .form-group { display:flex; flex-direction:column; gap:5px; }
    .form-label { font-size:.72rem; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; }
    .form-input { padding:10px 14px; border-radius:8px; border:1px solid var(--border-color,rgba(216,184,120,.2)); font-family:'Nunito',sans-serif; font-size:.88rem; color:var(--text,#eef3f4); background:rgba(255,255,255,.05); outline:none; transition:border-color var(--trans); }
    .form-input:focus { border-color:#3b82f6; }
    .form-hint { font-size:.67rem; color:var(--muted); }

    /* Toggle switch */
    .toggle-wrap { display:flex; align-items:center; gap:10px; padding:10px 0; }
    .toggle-label { font-size:.85rem; font-weight:700; color:var(--text); }
    .toggle { position:relative; width:44px; height:24px; flex-shrink:0; }
    .toggle input { opacity:0; width:0; height:0; }
    .toggle-slider { position:absolute; inset:0; border-radius:12px; background:rgba(255,255,255,.15); cursor:pointer; transition:.2s; }
    .toggle-slider::before { content:''; position:absolute; width:18px; height:18px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.2s; }
    .toggle input:checked + .toggle-slider { background:#059669; }
    .toggle input:checked + .toggle-slider::before { transform:translateX(20px); }

    .btn-save { padding:10px 24px; border-radius:8px; border:none; background:var(--btn-primary); color:#fff; font-family:'Nunito',sans-serif; font-size:.82rem; font-weight:700; cursor:pointer; transition:background var(--trans); margin-top:16px; }
    .btn-save:hover { background:#222; }

    /* DENDA TABLE */
    .denda-section-title { font-size:1rem; font-weight:800; color:var(--text); margin-bottom:14px; display:flex; align-items:center; gap:8px; }
    .topbar { display:flex; align-items:center; background:var(--card,#121820); border:1px solid var(--border-color,rgba(216,184,120,.18)); border-radius:50px; padding:0 18px; height:46px; gap:10px; margin-bottom:16px; box-shadow:var(--shadow); animation:fadeUp .5s .1s both; }
    .topbar svg { width:18px; height:18px; color:#aaa; flex-shrink:0; }
    .topbar input { flex:1; border:none; outline:none; font-family:'Nunito',sans-serif; font-size:.85rem; color:var(--text); background:transparent; }
    .topbar input::placeholder { color:#bbb; }
    .btn-search { background:var(--btn-primary); color:#fff; border:none; border-radius:20px; padding:6px 16px; font-family:'Nunito',sans-serif; font-size:.78rem; font-weight:700; cursor:pointer; transition:background var(--trans); }
    .btn-search:hover { background:#222; }

    .denda-table-wrap { background:var(--card,#121820); border:1px solid var(--card-border,rgba(216,184,120,.12)); border-radius:14px; box-shadow:var(--shadow); overflow:hidden; animation:fadeUp .4s .12s both; }
    .denda-table { width:100%; border-collapse:collapse; }
    .denda-table th { background:rgba(255,255,255,.03); font-size:.7rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; padding:12px 16px; text-align:left; border-bottom:1.5px solid var(--border-color,rgba(216,184,120,.18)); }
    .denda-table td { padding:12px 16px; font-size:.78rem; color:var(--text,#eef3f4); font-weight:600; border-bottom:1px solid var(--border-color,rgba(216,184,120,.1)); vertical-align:middle; }
    .denda-table tr:last-child td { border-bottom:none; }
    .denda-table tr:hover td { background:rgba(255,255,255,.03); }

    .chip { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:.65rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
    .chip-belum  { background:rgba(220,38,38,.15); color:#f87171; border:1px solid rgba(220,38,38,.3); }
    .chip-lunas  { background:rgba(5,150,105,.15); color:#4ade80; border:1px solid rgba(5,150,105,.3); }
    .chip-tidak  { background:rgba(255,255,255,.08); color:var(--muted); }

    .denda-amount { font-weight:800; color:#dc2626; }

    .btn-lunas { padding:5px 12px; border-radius:6px; border:none; background:#059669; color:#fff; font-family:'Nunito',sans-serif; font-size:.68rem; font-weight:700; cursor:pointer; transition:background var(--trans); white-space:nowrap; }
    .btn-lunas:hover { background:#047857; }

    .empty-state { text-align:center; padding:48px 20px; color:var(--muted); }
    .empty-state svg { width:48px; height:48px; margin-bottom:12px; opacity:.3; }
    .empty-state p { font-size:.85rem; font-weight:600; }

    @keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:translateY(0); } }

    @media (max-width: 640px) {
      .sidebar { transform:translateX(-100%); }
      .sidebar.open { transform:translateX(0); }
      .sidebar-toggle { display:flex; }
      .main { margin-left:0; padding:16px; padding-top:60px; }
      .form-grid { grid-template-columns:1fr; }
      .stat-row { grid-template-columns:1fr 1fr; }
      .denda-table-wrap { overflow-x:auto; }
    }
      .avatar-wrap { position:relative; margin-bottom:14px; cursor:pointer; }
    .avatar-circle {
      width:80px; height:80px; max-width:80px; max-height:80px; border-radius:50%;
      background:#161e27; overflow:hidden;
      border:3px solid rgba(216,184,120,.25);
      display:flex; align-items:center; justify-content:center;
      transition:border-color .2s;
    }
    .avatar-wrap:hover .avatar-circle { border-color:var(--accent,#d8b878); }
    .avatar-circle img { width:100%; height:100%; object-fit:cover; display:block; }
    .avatar-circle .default-icon { width:52px; height:52px; color:#888; }
    .avatar-overlay {
      position:absolute; inset:0; border-radius:50%;
      background:rgba(0,0,0,.5); display:flex;
      align-items:center; justify-content:center;
      opacity:0; transition:opacity .2s;
    }
    .avatar-wrap:hover .avatar-overlay { opacity:1; }
    .avatar-overlay svg { width:24px; height:24px; color:#fff; }
  </style>
</head>
<body>

<!-- Mobile Topbar -->
<header class="mobile-topbar" id="mobileTopbar">
  <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu" onclick="toggleSidebar()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  </button>
  <div class="mobile-topbar-divider"></div>
  <div class="mobile-topbar-brand">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <span>AKSA NOVA • Pengaturan Denda</span>
  </div>
  <div class="mobile-topbar-actions">
    <a href="halaman_admin.php" title="Kelola Buku">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
    </a>
    <a href="beranda.php" title="Lihat Beranda">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    </a>
  </div>
</header>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="avatar-wrap" onclick="openProfilModal()" title="Edit Profil">
    <div class="avatar-circle">
      <?php if ($admin_foto && file_exists($admin_foto)): ?>
        <img src="<?= htmlspecialchars($admin_foto) ?>?v=<?= filemtime($admin_foto) ?>" alt="Admin" width="80" height="80" loading="eager" decoding="sync"/>
      <?php else: ?>
        <svg class="default-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
        </svg>
      <?php endif; ?>
        <div class="avatar-overlay">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
      </svg>
    </div>
  </div>
  </div>
  <div class="admin-name-label">Halo, <?= htmlspecialchars($admin_name) ?></div>
  </div>
  <nav class="sidebar-nav">
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
    Daftar Anggota<?php if (!empty($pending_count) && $pending_count > 0): ?><span style="margin-left:auto;background:#e74c3c;color:#fff;font-size:.65rem;font-weight:800;padding:2px 7px;border-radius:20px;"><?= $pending_count ?></span><?php endif; ?>
  </a>
  <a class="sidebar-btn" href="pinjam_buku.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
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
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    Telah Dipinjam
  </a>
  <a class="sidebar-btn active" href="pengaturan_denda.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
    Pengaturan Denda
  </a>
  <a class="sidebar-btn" href="pengaturan_musik.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>
      </svg>
      Pengaturan Musik
    </a>
  
    <button class="sidebar-btn btn-settings-nav" onclick="if(typeof bukaSettings==='function')bukaSettings()" title="Buka Pengaturan">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
      Pengaturan
    </button>
    <a class="sidebar-btn" href="beranda.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Kembali
  </a>
  </nav>
</aside>

<main class="main">

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type ?>">
    <?= $msg_type === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>

  <div class="page-title">⚙️ Pengaturan Denda</div>

  <!-- STATISTIK -->
  <div class="stat-row">
    <div class="stat-card red">
      <div class="stat-label">Belum Dibayar</div>
      <div class="stat-val"><?= $stat_belum ?></div>
      <div class="stat-sub">peminjaman</div>
    </div>
    <div class="stat-card green">
      <div class="stat-label">Sudah Lunas</div>
      <div class="stat-val"><?= $stat_lunas ?></div>
      <div class="stat-sub">peminjaman</div>
    </div>
    <div class="stat-card blue">
      <div class="stat-label">Total Tagihan</div>
      <div class="stat-val">Rp <?= number_format($total_denda_belum, 0, ',', '.') ?></div>
      <div class="stat-sub">belum dibayar</div>
    </div>
    <div class="stat-card purple">
      <div class="stat-label">Total Terkumpul</div>
      <div class="stat-val">Rp <?= number_format($total_denda_lunas, 0, ',', '.') ?></div>
      <div class="stat-sub">sudah lunas</div>
    </div>
  </div>

  <!-- FORM PENGATURAN TARIF -->
  <div class="settings-card">
    <div class="settings-title">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
      Konfigurasi Tarif Denda
    </div>

    <form method="POST" action="pengaturan_denda.php<?= $search ? '?q='.urlencode($search) : '' ?>">
      <input type="hidden" name="action" value="simpan_pengaturan"/>

      <div class="toggle-wrap" style="margin-bottom:16px;">
        <label class="toggle">
          <input type="checkbox" name="denda_aktif" id="dendaAktif" <?= $denda_aktif ? 'checked' : '' ?>/>
          <span class="toggle-slider"></span>
        </label>
        <div>
          <div class="toggle-label">Fitur Denda Aktif</div>
          <div style="font-size:.7rem;color:var(--muted);margin-top:1px;">Nonaktifkan untuk menangguhkan perhitungan denda</div>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label" for="dendaPerHari">Tarif Denda per Hari (Rp)</label>
          <input type="number" id="dendaPerHari" name="denda_per_hari" class="form-input"
                 value="<?= $denda_per_hari ?>" min="0" step="500" required/>
          <div class="form-hint">Jumlah rupiah yang dikenakan per hari keterlambatan</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="gracePeriod">Toleransi (hari)</label>
          <input type="number" id="gracePeriod" name="grace_period" class="form-input"
                 value="<?= $grace_period ?>" min="0" max="30" required/>
          <div class="form-hint">Denda tidak dihitung jika terlambat di bawah toleransi ini (0 = langsung denda)</div>
        </div>
      </div>

      <div style="margin-top:10px;padding:12px 14px;background:#fffbeb;border-radius:8px;border:1px solid #fcd34d;font-size:.76rem;color:#92400e;font-weight:600;">
        💡 Preview: Terlambat <strong>3 hari</strong> → Denda
        <strong>Rp <?= number_format(max(0, 3 - $grace_period) * $denda_per_hari, 0, ',', '.') ?></strong>
        <?= $grace_period > 0 ? "(toleransi $grace_period hari)" : '' ?>
      </div>

      <button type="submit" class="btn-save">💾 Simpan Pengaturan</button>
    </form>
  </div>

  <!-- DAFTAR DENDA -->
  <div style="animation:fadeUp .4s .14s both;">
    <div class="denda-section-title">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      Riwayat Denda Peminjam
    </div>

    <form method="GET" action="">
      <div class="topbar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" name="q" placeholder="Cari nama peminjam atau judul buku…" value="<?= htmlspecialchars($search) ?>"/>
        <button type="submit" class="btn-search">Cari</button>
        <?php if ($search): ?>
        <a href="pengaturan_denda.php" style="font-size:.75rem;color:var(--muted);text-decoration:none;white-space:nowrap;">✕ Reset</a>
        <?php endif; ?>
      </div>
    </form>

    <?php if (empty($denda_list)): ?>
    <div class="empty-state">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      <p><?= $search ? 'Tidak ada data yang cocok.' : 'Belum ada denda yang tercatat.' ?></p>
    </div>
    <?php else: ?>
    <div class="denda-table-wrap">
      <table class="denda-table">
        <thead>
          <tr>
            <th>Peminjam</th>
            <th>Buku</th>
            <th>Batas Kembali</th>
            <th>Dikembalikan</th>
            <th>Terlambat</th>
            <th>Denda</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($denda_list as $d): ?>
          <tr>
            <td><strong><?= htmlspecialchars($d['nama_peminjam']) ?></strong></td>
            <td style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <?= htmlspecialchars($d['judul']) ?>
            </td>
            <td><?= date('d M Y', strtotime($d['batas_kembali'])) ?></td>
            <td>
              <?= $d['waktu_kembali']
                ? date('d M Y', strtotime($d['waktu_kembali']))
                : '<span style="color:#dc2626;font-weight:700;">Belum</span>' ?>
            </td>
            <td><strong style="color:#dc2626;"><?= (int)$d['terlambat_hari'] ?> hari</strong></td>
            <td class="denda-amount">Rp <?= number_format((int)$d['denda'], 0, ',', '.') ?></td>
            <td>
              <?php if ($d['status_denda'] === 'belum_bayar'): ?>
                <span class="chip chip-belum">⚠ Belum Bayar</span>
              <?php elseif ($d['status_denda'] === 'lunas'): ?>
                <span class="chip chip-lunas">✅ Lunas</span>
              <?php else: ?>
                <span class="chip chip-tidak">–</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($d['status_denda'] === 'belum_bayar'): ?>
              <form method="POST" action="pengaturan_denda.php<?= $search ? '?q='.urlencode($search) : '' ?>" style="display:inline;">
                <input type="hidden" name="action" value="tandai_lunas"/>
                <input type="hidden" name="pem_id" value="<?= (int)$d['id'] ?>"/>
                <button type="submit" class="btn-lunas"
                  onclick="return confirm('Tandai denda peminjaman ini sebagai lunas?')">
                  ✅ Tandai Lunas
                </button>
              </form>
              <?php else: ?>
              <span style="font-size:.7rem;color:var(--muted);">–</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</main>

<script>
// Sidebar mobile toggle dikelola terpusat oleh settings_include.php

// Live preview denda
const rateInput  = document.getElementById('dendaPerHari');
const graceInput = document.getElementById('gracePeriod');
const preview    = document.querySelector('.settings-card [style*="fffbeb"] strong:last-child');
function updatePreview() {
  const rate  = parseInt(rateInput?.value || 0);
  const grace = parseInt(graceInput?.value || 0);
  const days  = Math.max(0, 3 - grace);
  const total = days * rate;
  if (preview) preview.textContent = 'Rp ' + total.toLocaleString('id-ID');
}
rateInput?.addEventListener('input', updatePreview);
graceInput?.addEventListener('input', updatePreview);
</script>
<?php require_once 'modal_profil_admin.php';
require_once 'pengaturan_panel.php'; ?>
</body>
</html>
