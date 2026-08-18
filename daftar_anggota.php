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
$search = trim($_GET["q"] ?? "");
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

    .avatar-circle {
      width:96px; height:96px; border-radius:50%;
      background:#c0c0c8; overflow:hidden;
      border:3px solid rgba(255,255,255,.25);
      display:flex; align-items:center; justify-content:center;
      margin-bottom:14px;
    }
    .avatar-circle img { width:100%; height:100%; object-fit:cover; display:block; }
    .avatar-circle .default-icon { width:52px; height:52px; color:#888; }

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
      font-size: .84rem; display: flex; gap: 16px;
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

    .badge {
      display:inline-block; padding:3px 11px; border-radius:20px;
      font-size:.68rem; font-weight:800; white-space:nowrap;
    }
    .badge-pending  { background:#fff3cd; color:#8a6100; }
    .badge-approved { background:#e8f5e9; color:#1a8a4a; }
    .badge-rejected { background:#fce4ec; color:#c0392b; }

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
  <div class="avatar-circle">
    <?php if ($admin_foto && file_exists($admin_foto)): ?>
      <img src="<?= htmlspecialchars($admin_foto) ?>?v=<?= filemtime($admin_foto) ?>" alt="Admin"/>
    <?php else: ?>
      <svg class="default-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
    <?php endif; ?>
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

  <form method="GET" action="">
    <?php if ($filter !== "all"): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filter) ?>"><?php endif; ?>
    <div class="topbar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" name="q" placeholder="Cari anggota berdasarkan nama, NIK, kelas, username, atau email…"
             value="<?= htmlspecialchars($search) ?>"/>
      <button type="submit" class="btn-search">Cari</button>
      <?php if ($search): ?>
      <a href="daftar_anggota.php<?= $filter !== 'all' ? '?status=' . urlencode($filter) : '' ?>" style="font-size:.75rem;color:var(--muted);text-decoration:none;white-space:nowrap;">✕ Reset</a>
      <?php endif; ?>
    </div>
  </form>

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
          <td>
            <div class="cell-name"><?= htmlspecialchars($a["full_name"]) ?></div>
            <div class="cell-sub"><?= htmlspecialchars($a["email"]) ?></div>
          </td>
          <td><?= htmlspecialchars($a["kelas"]) ?></td>
          <td class="cell-mono"><?= htmlspecialchars($a["nik"]) ?></td>
          <td class="cell-mono"><?= htmlspecialchars($a["no_anggota"]) ?></td>
          <td class="cell-mono"><?= htmlspecialchars($a["username"]) ?></td>
          <td>
            <?php if ($a["status"] === "pending"): ?>
              <span class="badge badge-pending">Menunggu</span>
            <?php elseif ($a["status"] === "approved"): ?>
              <span class="badge badge-approved">Disetujui</span>
            <?php else: ?>
              <span class="badge badge-rejected">Ditolak</span>
            <?php endif; ?>
          </td>
          <td>
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
              <button class="act-btn act-hapus" onclick="konfirmasiHapusAnggota(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['full_name'])) ?>')">Hapus</button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

</main>

<!-- Form aksi tersembunyi -->
<form method="POST" id="formAksi" style="display:none">
  <input type="hidden" name="action" id="aksiAction"/>
  <input type="hidden" name="id" id="aksiId"/>
</form>

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

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

function konfirmasiHapusAnggota(id, nama) {
  if (confirm(`Hapus anggota "${nama}"? Tindakan ini tidak bisa dibatalkan.`)) {
    kirimAksi('hapus', id);
  }
}
</script>
</body>
</html>