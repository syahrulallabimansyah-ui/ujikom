<?php
// daftar_anggota.php — Panel admin untuk kelola anggota (approve/tolak/reset sandi)
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: sign_in.php");
    exit;
}

require_once "db.php";

$page_title = "Daftar Anggota – AKSA NOVA";
$msg        = "";
$msg_type   = "";
$reset_info = null; // dipakai untuk tampilkan password baru sekali saja setelah reset

$profil_res  = mysqli_query($conn, "SELECT * FROM admin_profile LIMIT 1");
$profil      = $profil_res ? mysqli_fetch_assoc($profil_res) : null;
$admin_name  = $profil["display_name"] ?? ($_SESSION["user_name"] ?? "Admin");
$admin_foto  = $profil["foto"] ?? "";

$total_res  = mysqli_query($conn, "SELECT COUNT(*) AS c FROM buku");
$total_buku = $total_res ? (int)(mysqli_fetch_assoc($total_res)['c'] ?? 0) : 0;

// ─────────────────────────────────────────────
//  AKSI
// ─────────────────────────────────────────────
$action = $_POST["action"] ?? "";

if ($action === "approve") {
    $id = (int)($_POST["id"] ?? 0);
    if ($id > 0) {
        mysqli_query($conn, "UPDATE users SET status='approved' WHERE id=$id AND role='member'");
        $msg = "Anggota berhasil disetujui."; $msg_type = "success";
    }
}

if ($action === "unfreeze") {
    $id = (int)($_POST["id"] ?? 0);
    if ($id > 0) {
        mysqli_query($conn, "UPDATE users SET card_status='active' WHERE id=$id AND role='member'");
        $msg = "Kartu anggota berhasil dicairkan kembali."; $msg_type = "success";
    }
}

if ($action === "reject") {
    $id = (int)($_POST["id"] ?? 0);
    if ($id > 0) {
        mysqli_query($conn, "UPDATE users SET status='rejected' WHERE id=$id AND role='member'");
        $msg = "Pendaftaran anggota ditolak."; $msg_type = "success";
    }
}

if ($action === "hapus") {
    $id = (int)($_POST["id"] ?? 0);
    if ($id > 0) {
        $cek = mysqli_query($conn, "SELECT COUNT(*) AS c FROM peminjaman WHERE user_id=$id");
        $jml = (int)(mysqli_fetch_assoc($cek)['c'] ?? 0);
        if ($jml > 0) {
            $msg = "Anggota tidak bisa dihapus karena masih punya $jml riwayat peminjaman.";
            $msg_type = "error";
        } else {
            mysqli_query($conn, "DELETE FROM users WHERE id=$id AND role='member'");
            $msg = "Anggota berhasil dihapus."; $msg_type = "success";
        }
    }
}

if ($action === "reset_password") {
    $id = (int)($_POST["id"] ?? 0);
    if ($id > 0) {
        $r = mysqli_query($conn, "SELECT full_name, username FROM users WHERE id=$id AND role='member'");
        $u = $r ? mysqli_fetch_assoc($r) : null;

        if ($u) {
            // ── Generate password baru (sama seperti saat pendaftaran) ──
            $chars_upper = "ABCDEFGHJKLMNPQRSTUVWXYZ";
            $chars_lower = "abcdefghijkmnpqrstuvwxyz";
            $chars_num   = "23456789";
            $all_chars   = $chars_upper . $chars_lower . $chars_num;

            $new_password = $chars_upper[random_int(0, strlen($chars_upper) - 1)]
                           . $chars_lower[random_int(0, strlen($chars_lower) - 1)]
                           . $chars_num[random_int(0, strlen($chars_num) - 1)];
            for ($i = 0; $i < 5; $i++) {
                $new_password .= $all_chars[random_int(0, strlen($all_chars) - 1)];
            }
            $new_password = str_shuffle($new_password);
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=? AND role='member'");
            mysqli_stmt_bind_param($stmt, "si", $hashed, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $reset_info = [
                "full_name" => $u["full_name"],
                "username"  => $u["username"],
                "password"  => $new_password,
            ];
            $msg = "Sandi berhasil diubah."; $msg_type = "success";
        }
    }
}

// ─────────────────────────────────────────────
//  AMBIL DATA
// ─────────────────────────────────────────────
$search  = trim($_GET["q"] ?? "");
$is_ajax = isset($_GET["ajax"]) && $_GET["ajax"] == "1";
$filter = $_GET["status"] ?? "all";

$where = ["role = 'member'"];
if ($search !== "") {
    $s = mysqli_real_escape_string($conn, $search);
    $where[] = "(full_name LIKE '%$s%' OR nik LIKE '%$s%' OR kelas LIKE '%$s%' OR username LIKE '%$s%' OR email LIKE '%$s%' OR no_anggota LIKE '%$s%')";
}
if (in_array($filter, ["pending", "approved", "rejected"], true)) {
    $where[] = "status = '" . $filter . "'";
}
$where_sql = "WHERE " . implode(" AND ", $where);

$anggota_list = [];
$res = mysqli_query($conn, "SELECT * FROM users $where_sql ORDER BY (status='pending') DESC, id DESC");
while ($row = mysqli_fetch_assoc($res)) {
    $anggota_list[] = $row;
}
$total_anggota = count($anggota_list);

$count_res = mysqli_query($conn, "SELECT status, COUNT(*) AS c FROM users WHERE role='member' GROUP BY status");
$counts = ["pending" => 0, "approved" => 0, "rejected" => 0];
while ($row = mysqli_fetch_assoc($count_res)) {
    $counts[$row["status"]] = (int)$row["c"];
}

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
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Cormorant+Garamond:wght@700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --sidebar-bg:  #4a4a5a;
      --sidebar-dark:#2e2e3a;
      --accent:      #5a5a6e;
      --btn-primary: #3a3a4a;
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

    /* ── SIDEBAR (identik dengan halaman_admin.php) ── */
    .sidebar {
      width: var(--sidebar-w);
      background: linear-gradient(180deg, #5a5a6e 0%, #2e2e3a 100%);
      display: flex; flex-direction: column; align-items: center;
      padding: 36px 20px 28px;
      position: fixed; top:0; left:0; bottom:0; z-index: 100;
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

    /* ── Modal Edit Profil ── */
    .modal-overlay {
      display:none; position:fixed; inset:0;
      background:rgba(20,20,30,.55); z-index:500;
      align-items:center; justify-content:center; padding:20px;
    }
    .modal-overlay.open { display:flex; }
    .modal-box {
      background:#fff; border-radius:16px; width:100%; max-width:380px;
      max-height:90vh; overflow-y:auto; padding:24px;
      box-shadow:0 20px 60px rgba(0,0,0,.25);
      animation:modalIn .25s cubic-bezier(.22,1,.36,1) both;
    }
    @keyframes modalIn {
      from { opacity:0; transform:scale(.94) translateY(10px); }
      to   { opacity:1; transform:scale(1) translateY(0); }
    }
    .modal-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
    .modal-title { font-family:'Cormorant Garamond',serif; font-size:1.3rem; font-weight:700; color:var(--text); }
    .modal-close { border:none; background:#f0f0f5; width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text); }
    .modal-close svg { width:16px; height:16px; }
    .img-preview-wrap { position:relative; border:2px dashed #d8d8e4; overflow:hidden; cursor:pointer; }
    .img-preview-wrap img { width:100%; height:100%; object-fit:cover; }
    .upload-placeholder { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; color:var(--muted); }
    .upload-placeholder svg { width:26px; height:26px; }
    .form-group { margin-bottom:14px; }
    .form-label { display:block; font-size:.78rem; font-weight:700; color:var(--text); margin-bottom:6px; }
    .form-input { width:100%; padding:10px 12px; border-radius:8px; border:1px solid #e0e0ea; font-family:'Nunito',sans-serif; font-size:.85rem; }
    .form-input:focus { outline:none; border-color:var(--accent); }
    .modal-footer { display:flex; gap:10px; margin-top:18px; }
    .btn-cancel, .btn-save {
      flex:1; padding:11px; border-radius:8px; border:none;
      font-family:'Nunito',sans-serif; font-size:.85rem; font-weight:700; cursor:pointer;
      transition:opacity var(--trans);
    }
    .btn-cancel:hover, .btn-save:hover { opacity:.85; }
    .btn-cancel { background:#f0f0f5; color:var(--text); }
    .btn-save { background:var(--accent); color:#fff; }

    .admin-name-label { color:#fff; font-size:.95rem; font-weight:700; margin-bottom:8px; text-align:center; }
    .total-badge {
      background: rgba(255,255,255,.18); color:#fff;
      font-size:.72rem; font-weight:700;
      padding:4px 12px; border-radius:50px;
      margin-bottom:24px; text-align:center;
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

    .alert {
      padding:12px 18px; border-radius:8px; font-size:.82rem;
      font-weight:700; margin-bottom:16px; animation:fadeUp .4s both;
    }
    .alert-success { background:#e8f5e9; color:#1a8a4a; border:1px solid #c8e6c9; }
    .alert-error   { background:#fce4ec; color:#c0392b; border:1px solid #f8bbd0; }

    /* Reset password banner */
    .reset-banner {
      background: linear-gradient(135deg, #1c1c28, #3a3a52);
      color: #fff; border-radius: 12px;
      padding: 18px 22px; margin-bottom: 20px;
      display: flex; align-items: center; justify-content: space-between;
      flex-wrap: wrap; gap: 14px;
      animation: fadeUp .4s both;
    }
    .reset-banner .rb-left { font-size: .82rem; line-height:1.7; }
    .reset-banner .rb-left b { font-size: .92rem; }
    .reset-banner .rb-cred {
      font-family: 'JetBrains Mono', monospace;
      background: rgba(255,255,255,.12);
      padding: 8px 14px; border-radius: 8px;
      font-size: .84rem; display: flex; gap: 16px; flex-wrap: wrap;
    }

    .topbar {
      display:flex; align-items:center;
      background:#fff; border-radius:50px;
      padding:0 18px; height:46px; gap:10px;
      margin-bottom:18px; box-shadow:var(--shadow);
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

    .content-header {
      display:flex; align-items:center; justify-content:space-between;
      margin-bottom:14px; animation:fadeUp .5s .08s both; flex-wrap:wrap; gap:10px;
    }
    .content-title { font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:700; color:var(--text); }

    .status-tabs { display:flex; gap:8px; margin-bottom:18px; flex-wrap:wrap; animation:fadeUp .5s .1s both; }
    .status-tab {
      padding:7px 16px; border-radius:50px; font-size:.78rem; font-weight:700;
      text-decoration:none; color:var(--muted); background:#fff; box-shadow:var(--shadow);
      transition:all var(--trans);
    }
    .status-tab .count { opacity:.7; margin-left:4px; }
    .status-tab.active { background:var(--btn-primary); color:#fff; }
    .status-tab:hover:not(.active) { color:var(--text); }

    /* Table */
    .table-wrap {
      background:var(--card); border-radius:var(--radius);
      box-shadow:var(--shadow); overflow-x:auto;
      animation:fadeUp .5s .14s both;
    }
    table { width:100%; border-collapse:collapse; min-width:820px; }
    thead th {
      text-align:left; font-size:.68rem; text-transform:uppercase; letter-spacing:.06em;
      color:var(--muted); font-weight:800; padding:12px 16px;
      border-bottom:1.5px solid #eee; white-space:nowrap;
    }
    tbody td {
      padding:12px 16px; font-size:.82rem; color:var(--text);
      border-bottom:1px solid #f2f2f6; vertical-align:middle;
    }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover { background:#fafafe; }
    .cell-name { font-weight:700; }
    .cell-sub  { font-size:.7rem; color:var(--muted); }
    .cell-mono { font-family:'JetBrains Mono', monospace; font-size:.76rem; }

    .cell-anggota { display:flex; align-items:center; gap:10px; }
    .member-avatar {
      width:38px; height:38px; border-radius:50%; flex-shrink:0;
      overflow:hidden; background:linear-gradient(135deg,#3498db,#1a5276);
      color:#fff; font-weight:800; font-size:.82rem;
      display:flex; align-items:center; justify-content:center;
    }
    .member-avatar img { width:100%; height:100%; object-fit:cover; display:block; }

    .badge {
      display:inline-block; padding:3px 11px; border-radius:20px;
      font-size:.68rem; font-weight:800; white-space:nowrap;
    }
    .badge-pending  { background:#fff3cd; color:#8a6100; }
    .badge-approved { background:#e8f5e9; color:#1a8a4a; }
    .badge-rejected { background:#fce4ec; color:#c0392b; }
    .badge-frozen   { background:#e0e7ff; color:#3730a3; margin-left:6px; }

    .row-actions { display:flex; gap:6px; flex-wrap:wrap; }
    .act-btn {
      border:none; border-radius:6px; padding:6px 12px;
      font-family:'Nunito',sans-serif; font-size:.7rem; font-weight:800;
      cursor:pointer; transition:opacity var(--trans), transform .12s; white-space:nowrap;
    }
    .act-btn:hover { opacity:.85; }
    .act-btn:active { transform:scale(.95); }
    .act-approve { background:#1a8a4a; color:#fff; }
    .act-reject  { background:#e67e22; color:#fff; }
    .act-reset   { background:var(--btn-primary); color:#fff; }
    .act-hapus   { background:#e74c3c; color:#fff; }

    .empty-state {
      text-align:center; padding:60px 20px; color:var(--muted);
    }
    .empty-state svg { width:56px; height:56px; margin-bottom:12px; opacity:.35; }
    .empty-state p { font-size:.88rem; font-weight:600; }

    @keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:translateY(0); } }

    @media (max-width:860px) {
      :root { --sidebar-w:170px; }
      .avatar-circle { width:76px; height:76px; }
    }
    @media (max-width:620px) {
      .sidebar { transform:translateX(-100%); width:220px; }
      .sidebar.open { transform:translateX(0); }
      .sidebar-toggle { display:flex; }
      .main { margin-left:0; padding:70px 14px 24px; }
      .topbar { border-radius:14px; height:auto; padding:10px 14px; flex-wrap:wrap; }
      .btn-search { flex-shrink:0; }
      .reset-banner { flex-direction:column; align-items:stretch; }

      /* Tabel anggota jadi kartu bertumpuk di layar kecil, biar tombol aksi
         langsung kelihatan tanpa perlu geser ke samping */
      .table-wrap { overflow-x:visible; box-shadow:none; background:transparent; }
      table { min-width:0; width:100%; border-collapse:separate; border-spacing:0 16px; }
      thead { display:none; }
      tbody tr {
        display:block; background:var(--card); border-radius:var(--radius);
        box-shadow:var(--shadow); overflow:hidden;
      }
      tbody tr:hover { background:var(--card); }
      tbody td {
        display:flex; align-items:center; justify-content:space-between; gap:12px;
        padding:12px 14px; border-bottom:1px solid #f2f2f6; text-align:right;
      }
      tbody tr td:last-child { border-bottom:none; }
      tbody td::before {
        content:attr(data-label); font-size:.68rem; font-weight:800; color:var(--muted);
        text-transform:uppercase; letter-spacing:.05em; text-align:left; flex-shrink:0;
      }
      tbody td[data-label="Anggota"] { flex-direction:column; align-items:flex-start; text-align:left; }
      tbody td[data-label="Anggota"]::before { margin-bottom:4px; }
      tbody td[data-label="Aksi"] { flex-direction:column; align-items:stretch; }
      tbody td[data-label="Aksi"]::before { margin-bottom:6px; }
      .row-actions { width:100%; justify-content:flex-start; }
      .act-btn { flex:1; min-width:0; }
    }
  </style>
</head>
<body>

<button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
  </svg>
</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
  <div class="avatar-wrap" onclick="openProfilModal()" title="Edit Profil">
    <div class="avatar-circle">
      <?php if ($admin_foto && file_exists($admin_foto)): ?>
        <img src="<?= htmlspecialchars($admin_foto) ?>?v=<?= filemtime($admin_foto) ?>" alt="Admin"/>
      <?php else: ?>
        <svg class="default-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
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
  <a class="sidebar-btn active" href="daftar_anggota.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    Daftar Anggota
    <?php if ($counts["pending"] > 0): ?>
      <span style="margin-left:auto;background:#e74c3c;color:#fff;font-size:.65rem;font-weight:800;padding:2px 7px;border-radius:20px;"><?= $counts["pending"] ?></span>
    <?php endif; ?>
  </a>
  <a class="sidebar-btn" href="pinjam_buku.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
    </svg>
    Pinjam Buku
  </a>
  <a class="sidebar-btn" href="telah_dipinjam.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
    </svg>
    Telah Dipinjam
  </a>
  <a class="sidebar-btn" href="pengaturan_denda.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      Pengaturan Denda
    </a>
  <a class="sidebar-btn" href="beranda.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Kembali
  </a>
</aside>

<main class="main">

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type ?>">
    <?= $msg_type === "success" ? "✅" : "❌" ?> <?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>

  <?php if ($reset_info): ?>
  <div class="reset-banner">
    <div class="rb-left">
      Sandi baru untuk <b><?= htmlspecialchars($reset_info["full_name"]) ?></b> berhasil dibuat.<br>
      Sampaikan ke anggota — sandi ini hanya tampil sekali di sini.
    </div>
    <div class="rb-cred">
      <span>👤 <?= htmlspecialchars($reset_info["username"]) ?></span>
      <span>🔑 <?= htmlspecialchars($reset_info["password"]) ?></span>
    </div>
  </div>
  <?php endif; ?>

  <form method="GET" action="" id="searchForm">
    <?php if ($filter !== "all"): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filter) ?>"><?php endif; ?>
    <div class="topbar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" name="q" id="searchInput" autocomplete="off" placeholder="Cari anggota berdasarkan nama, NIK, kelas, username, atau email…"
             value="<?= htmlspecialchars($search) ?>"/>
      <?php if ($search): ?>
      <a href="daftar_anggota.php<?= $filter !== 'all' ? '?status=' . urlencode($filter) : '' ?>" id="searchResetBtn" style="font-size:.75rem;color:var(--muted);text-decoration:none;white-space:nowrap;">✕ Reset</a>
      <?php endif; ?>
    </div>
  </form>

  <div id="searchResultArea">
  <?php ob_start(); ?>
  <div class="content-header">
    <div class="content-title">
      Daftar Anggota
      <?php if ($search): ?><span style="font-size:.9rem;color:var(--muted);font-family:'Nunito',sans-serif;"> — hasil: "<?= htmlspecialchars($search) ?>"</span><?php endif; ?>
    </div>
  </div>

  <div class="status-tabs">
    <?php
      $qstr = $search !== "" ? "&q=" . urlencode($search) : "";
      $tabs = [
        "all"      => "Semua (" . array_sum($counts) . ")",
        "pending"  => "Menunggu (" . $counts["pending"] . ")",
        "approved" => "Disetujui (" . $counts["approved"] . ")",
        "rejected" => "Ditolak (" . $counts["rejected"] . ")",
      ];
      foreach ($tabs as $key => $label):
        $active = $filter === $key ? "active" : "";
    ?>
      <a class="status-tab <?= $active ?>" href="daftar_anggota.php?status=<?= $key ?><?= $qstr ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($anggota_list)): ?>
  <div class="empty-state">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
    </svg>
    <p>Belum ada anggota<?= $search ? " yang cocok dengan pencarian." : " di kategori ini." ?></p>
  </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Anggota</th>
          <th>Kelas</th>
          <th>NIK</th>
          <th>No. Anggota</th>
          <th>Username</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($anggota_list as $a): ?>
        <tr>
          <td data-label="Anggota">
            <div class="cell-anggota">
              <div class="member-avatar">
                <?php $foto_anggota = $a["foto"] ?? ""; ?>
                <?php if ($foto_anggota !== "" && file_exists($foto_anggota)): ?>
                  <img src="<?= htmlspecialchars($foto_anggota) ?>" alt="Foto <?= htmlspecialchars($a["full_name"]) ?>">
                <?php else: ?>
                  <?= htmlspecialchars(mb_strtoupper(mb_substr($a["full_name"], 0, 1))) ?>
                <?php endif; ?>
              </div>
              <div>
                <div class="cell-name"><?= htmlspecialchars($a["full_name"]) ?></div>
                <div class="cell-sub"><?= htmlspecialchars($a["email"]) ?></div>
              </div>
            </div>
          </td>
          <td data-label="Kelas"><?= htmlspecialchars($a["kelas"]) ?></td>
          <td class="cell-mono" data-label="NIK"><?= htmlspecialchars($a["nik"]) ?></td>
          <td class="cell-mono" data-label="No. Anggota"><?= htmlspecialchars($a["no_anggota"]) ?></td>
          <td class="cell-mono" data-label="Username"><?= htmlspecialchars($a["username"]) ?></td>
          <td data-label="Status">
            <?php if ($a["status"] === "pending"): ?>
              <span class="badge badge-pending">Menunggu</span>
            <?php elseif ($a["status"] === "approved"): ?>
              <span class="badge badge-approved">Disetujui</span>
            <?php else: ?>
              <span class="badge badge-rejected">Ditolak</span>
            <?php endif; ?>
            <?php if (($a["card_status"] ?? "active") === "frozen"): ?>
              <span class="badge badge-frozen">🔒 Dibekukan (proses Lupa Kartu)</span>
            <?php endif; ?>
          </td>
          <td data-label="Aksi">
            <div class="row-actions">
              <?php if ($a["status"] === "pending"): ?>
                <button class="act-btn act-approve" onclick="kirimAksi('approve', <?= $a['id'] ?>)">Setujui</button>
                <button class="act-btn act-reject" onclick="kirimAksi('reject', <?= $a['id'] ?>)">Tolak</button>
              <?php elseif ($a["status"] === "approved"): ?>
                <button class="act-btn act-reject" onclick="kirimAksi('reject', <?= $a['id'] ?>)">Tolak</button>
                <button class="act-btn act-reset" onclick="konfirmasiReset(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['full_name'])) ?>')">Ubah Sandi</button>
              <?php else: ?>
                <button class="act-btn act-approve" onclick="kirimAksi('approve', <?= $a['id'] ?>)">Setujui</button>
              <?php endif; ?>
              <?php if (($a["card_status"] ?? "active") === "frozen"): ?>
                <button class="act-btn act-reset" onclick="konfirmasiCairkan(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['full_name'])) ?>')">Cairkan Kartu</button>
              <?php endif; ?>
              <button class="act-btn act-hapus" onclick="konfirmasiHapusAnggota(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['full_name'])) ?>')">Hapus</button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
  <?php
  $search_result_html = ob_get_clean();
  if ($is_ajax) {
      ob_end_clean();
      header("Content-Type: text/html; charset=utf-8");
      echo $search_result_html;
      exit;
  }
  echo $search_result_html;
  ?>
  </div>

</main>

<!-- Form aksi tersembunyi -->
<form method="POST" id="formAksi" style="display:none">
  <input type="hidden" name="action" id="aksiAction"/>
  <input type="hidden" name="id" id="aksiId"/>
</form>

<!-- ═══════════ MODAL EDIT PROFIL ADMIN ═══════════ -->
<div class="modal-overlay" id="profilModalOverlay">
  <div class="modal-box" style="max-width:380px;">
    <div class="modal-header">
      <div class="modal-title">Edit Profil</div>
      <button class="modal-close" onclick="closeProfilModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="POST" action="update_profil_admin.php" enctype="multipart/form-data">
      <input type="hidden" name="redirect" value="daftar_anggota.php"/>

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
        <button type="button" class="btn-cancel" onclick="closeProfilModal()">Batal</button>
        <button type="submit" class="btn-save">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

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

function kirimAksi(action, id) {
  document.getElementById('aksiAction').value = action;
  document.getElementById('aksiId').value = id;
  document.getElementById('formAksi').submit();
}

function konfirmasiReset(id, nama) {
  if (confirm(`Ubah sandi untuk "${nama}"? Sandi lama akan langsung tidak berlaku.`)) {
    kirimAksi('reset_password', id);
  }
}

function konfirmasiCairkan(id, nama) {
  if (confirm(`Cairkan kartu "${nama}"? Gunakan ini hanya jika anggota meninggalkan proses "Lupa Kartu" di tengah jalan tanpa selesai.`)) {
    kirimAksi('unfreeze', id);
  }
}

function konfirmasiHapusAnggota(id, nama) {
  if (confirm(`Hapus anggota "${nama}"? Tindakan ini tidak bisa dibatalkan.`)) {
    kirimAksi('hapus', id);
  }
}

// ─── Live Search (ketik langsung cari, tanpa tombol) ───
(function initLiveSearch() {
  const CURRENT_FILTER = <?= json_encode($filter) ?>;
  const form   = document.getElementById('searchForm');
  const input  = document.getElementById('searchInput');
  const result = document.getElementById('searchResultArea');
  if (!form || !input || !result) return;

  let debounceTimer = null;
  let currentRequest = null;

  form.addEventListener('submit', e => e.preventDefault());

  function buildParams(query) {
    const params = new URLSearchParams();
    if (query) params.set('q', query);
    if (CURRENT_FILTER && CURRENT_FILTER !== 'all') params.set('status', CURRENT_FILTER);
    return params;
  }

  function runSearch(query) {
    if (currentRequest) currentRequest.abort();
    const controller = new AbortController();
    currentRequest = controller;

    const params = buildParams(query);
    params.set('ajax', '1');
    result.classList.add('loading-search');

    fetch('daftar_anggota.php?' + params.toString(), { signal: controller.signal })
      .then(r => r.text())
      .then(html => {
        result.innerHTML = html;
        result.classList.remove('loading-search');
        const viewParams = buildParams(query);
        const qs = viewParams.toString();
        history.replaceState(null, '', 'daftar_anggota.php' + (qs ? '?' + qs : ''));
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