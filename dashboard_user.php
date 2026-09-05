<?php
// dashboard_user.php — Dashboard khusus anggota (member) AKSA NOVA
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: sign_in.php");
    exit;
}

if (($_SESSION["role"] ?? "") === "admin") {
    header("Location: halaman_admin.php");
    exit;
}

require_once "db.php";

$user_id   = (int)$_SESSION["user_id"];
$user_name = $_SESSION["user_name"] ?? "Anggota";
$is_guest  = false;
$is_admin  = false;
$page_title = "Dashboard Anggota – AKSA NOVA";

// ─── Ambil data profil lengkap pengguna ───
$user_stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_res = mysqli_stmt_get_result($user_stmt);
$user     = mysqli_fetch_assoc($user_res);
mysqli_stmt_close($user_stmt);

if (!$user) {
    session_destroy();
    header("Location: sign_in.php");
    exit;
}

// ─── Pengaturan denda ───
$denda_per_hari = 5000;
$denda_aktif    = 0;
$pgt = @mysqli_query($conn, "SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('denda_per_hari','denda_aktif')");
if ($pgt) {
    while ($p = mysqli_fetch_assoc($pgt)) {
        if ($p["kunci"] === "denda_per_hari") $denda_per_hari = (int)$p["nilai"];
        if ($p["kunci"] === "denda_aktif")    $denda_aktif    = (int)$p["nilai"];
    }
}

// ─── Statistik peminjaman anggota ───
$total_pinjam = 0;
$sedang_dipinjam = 0;
$terlambat_pinjam = 0;
$total_denda_belum = 0;

$r1 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM peminjaman WHERE user_id = $user_id");
if ($r1) $total_pinjam = (int)(mysqli_fetch_assoc($r1)["total"] ?? 0);

// Denda yang belum dibayar dari riwayat
$r_denda = mysqli_query($conn, "SELECT SUM(denda) AS denda_sisa FROM peminjaman WHERE user_id = $user_id AND status_denda = 'belum_bayar'");
if ($r_denda) {
    $row_d = mysqli_fetch_assoc($r_denda);
    if (!empty($row_d["denda_sisa"])) {
        $total_denda_belum = (float)$row_d["denda_sisa"];
    }
}

// ─── Buku yang sedang dipinjam (Aktif) ───
$pinjaman_aktif = [];
$r_aktif = mysqli_query($conn,
    "SELECT p.*, b.judul, b.penulis, b.gambar, b.genre, b.isbn
     FROM peminjaman p
     LEFT JOIN buku b ON b.id = p.buku_id
     WHERE p.user_id = $user_id AND p.status = 'dipinjam'
     ORDER BY p.batas_kembali ASC"
);
if ($r_aktif) {
    while ($row = mysqli_fetch_assoc($r_aktif)) $pinjaman_aktif[] = $row;
}

$sedang_dipinjam  = count($pinjaman_aktif);
$terlambat_pinjam = 0;
$denda_berjalan   = 0;
foreach ($pinjaman_aktif as $pa) {
    if (strtotime($pa["batas_kembali"]) < time()) {
        $terlambat_pinjam++;
        $days_late = (int)ceil((time() - strtotime($pa["batas_kembali"])) / 86400);
        if ($denda_aktif && $days_late > 0) {
            $denda_berjalan += ($days_late * $denda_per_hari);
        }
    }
}
$total_denda_semua = $total_denda_belum + $denda_berjalan;

// ─── Riwayat pengembalian buku ───
$riwayat_kembali = [];
$r_hist = mysqli_query($conn,
    "SELECT p.*, b.judul, b.penulis, b.gambar
     FROM peminjaman p
     LEFT JOIN buku b ON b.id = p.buku_id
     WHERE p.user_id = $user_id AND p.status = 'dikembalikan'
     ORDER BY p.waktu_kembali DESC LIMIT 6"
);
if ($r_hist) {
    while ($row = mysqli_fetch_assoc($r_hist)) $riwayat_kembali[] = $row;
}

// ─── Buku yang disimpan ───
$saved_books = [];
$r_saved = mysqli_query($conn,
    "SELECT b.* FROM buku b
     INNER JOIN buku_favorites f ON f.buku_id = b.id
     WHERE f.user_id = $user_id
     ORDER BY f.id DESC LIMIT 4"
);
if ($r_saved) {
    while ($row = mysqli_fetch_assoc($r_saved)) $saved_books[] = $row;
}

// ─── Pengaturan musik latar ───
$musik_aktif = 0;
$musik_file  = "";
$musik_judul = "Musik Latar";
$mgt = @mysqli_query($conn, "SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('musik_aktif','musik_file','musik_judul')");
if ($mgt) {
    while ($m = mysqli_fetch_assoc($mgt)) {
        if ($m["kunci"] === "musik_aktif") $musik_aktif = (int)$m["nilai"];
        if ($m["kunci"] === "musik_file")  $musik_file  = $m["nilai"];
        if ($m["kunci"] === "musik_judul") $musik_judul = $m["nilai"] ?: $musik_judul;
    }
}
$musik_tampil = ($musik_aktif === 1 && $musik_file !== "" && file_exists($musik_file));

// QR code anggota
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=0&data=" . urlencode($user["no_anggota"] ?: $user["username"]);

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
  <title><?= htmlspecialchars($page_title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Nunito:wght@300;400;600;700;800&family=Cormorant+Garamond:ital,wght@0,600;0,700;1,400&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet"/>
  <?php require_once "settings_include.php"; ?>

  <style>
    :root {
      --bg:           #090c10;
      --sidebar-bg:   #10151b;
      --accent:       #d8b878;
      --accent2:      #f0d9a8;
      --text:         #eef3f4;
      --muted:        rgba(238,243,244,.65);
      --card:         #121820;
      --radius:       14px;
      --sidebar-w:    170px;
      --shadow-sm:    0 2px 12px rgba(0,0,0,.25);
      --shadow-md:    0 4px 20px rgba(0,0,0,.45);
      --card-border:  rgba(216,184,120,.14);
      --border-color: rgba(216,184,120,.16);
      --book-card:    #161e27;
      --trans:        .2s cubic-bezier(.22,1,.36,1);
    }
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    html { overflow-x:hidden; }
    body {
      font-family: var(--font-family,'Outfit',sans-serif);
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      width: 100%;
      max-width: 100vw;
      overflow-x: hidden;
      display: flex;
    }

    /* ── SIDEBAR ── */
    .sidebar {
      width:var(--sidebar-w); height:100vh; height:100dvh; background:var(--sidebar-bg);
      display:flex; flex-direction:column; padding:24px 0 20px;
      border-right:1px solid var(--border-color, rgba(216,184,120,.15));
      position:fixed; top:0; left:0; bottom:0; z-index:170; transition:transform var(--trans);
      overflow-y:auto; -webkit-overflow-scrolling:touch; scrollbar-width:thin;
      box-shadow:2px 0 24px rgba(0,0,0,.35);
    }
    .sidebar-toggle { display:none; position:fixed; top:14px; left:14px; z-index:200; width:42px; height:42px; border-radius:12px; border:1px solid var(--border-color, rgba(216,184,120,.2)); background:var(--card, #121820); box-shadow:0 4px 16px rgba(0,0,0,.3); cursor:pointer; align-items:center; justify-content:center; transition:transform .15s ease; }
    .sidebar-toggle:active { transform:scale(.9); }
    .sidebar-toggle svg { width:20px; height:20px; color:var(--accent); }
    .sidebar-overlay { position:fixed; inset:0; background:rgba(9,12,16,.65); backdrop-filter:blur(3px); z-index:165; opacity:0; visibility:hidden; transition:opacity var(--trans), visibility var(--trans); }
    .sidebar-overlay.open { opacity:1; visibility:visible; }
    .logo-wrap { display:flex; flex-direction:column; align-items:center; padding:0 18px 24px; border-bottom:1px solid var(--border-color, rgba(216,184,120,.15)); }
    .logo-icon { width:52px; height:52px; background:linear-gradient(135deg, rgba(216,184,120,.18) 0%, rgba(216,184,120,.05) 100%); border:1px solid rgba(216,184,120,.3); border-radius:14px; display:flex; align-items:center; justify-content:center; margin-bottom:8px; box-shadow:0 4px 16px rgba(0,0,0,.3); }
    .logo-icon svg { width:28px; height:28px; color:var(--accent); }
    .logo-name { font-family:'Cormorant Garamond',serif; font-size:1.05rem; font-weight:700; color:var(--accent); letter-spacing:.1em; text-align:center; }
    .logo-sub  { font-size:.58rem; color:var(--muted); letter-spacing:.12em; text-transform:uppercase; text-align:center; margin-top:2px; }
    .nav { flex:1; display:flex; flex-direction:column; gap:2px; padding:16px 10px; }
    .nav-item { position:relative; display:flex; align-items:center; gap:10px; padding:11px 14px; border-radius:10px; font-size:.82rem; font-weight:600; color:var(--muted); cursor:pointer; text-decoration:none; transition:background var(--trans), color var(--trans); }
    .nav-item:hover  { background:rgba(216,184,120,.10); color:var(--accent); }
    .nav-item.active { background:rgba(216,184,120,.16); color:var(--accent); }
    .nav-item.active::before { content:''; position:absolute; left:-10px; top:50%; transform:translateY(-50%); width:3px; height:60%; border-radius:0 4px 4px 0; background:var(--accent); }
    .nav-item svg { width:17px; height:17px; flex-shrink:0; }
    .nav-bottom { padding:10px 10px 0; border-top:1px solid var(--border-color, rgba(216,184,120,.15)); display:flex; flex-direction:column; gap:2px; flex-shrink:0; }

    /* ── MAIN CONTENT ── */
    .main { margin-left:var(--sidebar-w); flex:1; min-width:0; max-width:100%; overflow-x:hidden; padding:24px 28px 40px; min-height:100vh; transition:margin-left var(--trans); }

    /* HUD Buttons */
    .btn-topbar-mode {
      appearance: none; cursor: pointer; width: 40px; height: 40px; border-radius: 50%;
      border: 1.5px solid var(--border-color, rgba(216,184,120,.3)); background: var(--card, rgba(18,24,32,.85));
      backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center;
      color: var(--accent, #d8b878); transition: all var(--trans); box-shadow: 0 4px 16px rgba(0,0,0,.35); flex-shrink: 0;
    }
    .btn-topbar-mode:hover { border-color: var(--accent); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(216,184,120,.35); }
    .btn-topbar-mode svg { width: 18px; height: 18px; }
    .btn-topbar-mode .icon-sun { display: none; }
    html.theme-light .btn-topbar-mode .icon-moon, html.light .btn-topbar-mode .icon-moon { display: none; }
    html.theme-light .btn-topbar-mode .icon-sun, html.light .btn-topbar-mode .icon-sun { display: block; }

    .btn-musik {
      appearance: none; cursor: pointer; width: 40px; height: 40px; border-radius: 50%;
      border: 1.5px solid var(--accent, #d8b878); background: rgba(18,24,32,.85);
      backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center;
      color: var(--accent, #d8b878); transition: all var(--trans); box-shadow: 0 4px 16px rgba(0,0,0,.35); flex-shrink: 0;
    }
    .btn-musik:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(216,184,120,.35); }
    .btn-musik svg { width: 18px; height: 18px; }
    .btn-musik .icon-off { display: block; }
    .btn-musik .icon-eq  { display: none; align-items: flex-end; gap: 2.5px; height: 16px; }
    .btn-musik .icon-eq span { display: block; width: 3px; background: var(--accent, #d8b878); border-radius: 2px; animation: eqBar 1s ease-in-out infinite; }
    .btn-musik .icon-eq span:nth-child(1) { height: 40%; animation-delay: -.6s; }
    .btn-musik .icon-eq span:nth-child(2) { height: 100%; animation-delay: -.2s; }
    .btn-musik .icon-eq span:nth-child(3) { height: 65%; animation-delay: -.9s; }
    @keyframes eqBar { 0%,100% { transform: scaleY(.35); } 50% { transform: scaleY(1); } }
    .btn-musik.playing { background: rgba(216,184,120,.18); box-shadow: 0 0 16px rgba(216,184,120,.3); }
    .btn-musik.playing .icon-off { display: none; }
    .btn-musik.playing .icon-eq  { display: flex; }

    .mobile-topbar { display: contents; }
    .mobile-topbar-divider, .mobile-topbar-brand { display: none; }
    .page-hud-controls { position: fixed; top: 16px; right: 20px; z-index: 150; display: flex; align-items: center; gap: 10px; }

    /* ── DASHBOARD HEADER BANNER ── */
    .user-hero {
      background: linear-gradient(135deg, rgba(216,184,120,.12) 0%, rgba(18,24,32,.9) 100%);
      border: 1px solid var(--border-color);
      border-radius: var(--radius);
      padding: 24px 28px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      box-shadow: var(--shadow-sm);
      position: relative;
      overflow: hidden;
    }
    .user-hero::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -10%;
      width: 250px;
      height: 250px;
      background: radial-gradient(circle, rgba(216,184,120,.15) 0%, transparent 70%);
      pointer-events: none;
    }
    .hero-profile-wrap { display: flex; align-items: center; gap: 18px; min-width: 0; }
    .hero-avatar {
      width: 64px; height: 64px; border-radius: 50%;
      border: 2px solid var(--accent); background: var(--book-card);
      overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 16px rgba(0,0,0,.4);
    }
    .hero-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .hero-avatar svg { width: 30px; height: 30px; color: var(--accent); }
    .hero-text { min-width: 0; }
    .hero-greeting { font-size: .8rem; color: var(--muted); letter-spacing: .05em; text-transform: uppercase; font-weight: 700; }
    .hero-name { font-family: 'Cormorant Garamond', serif; font-size: 1.65rem; font-weight: 700; color:#b89758; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .hero-sub { font-size: .78rem; color: var(--muted); margin-top: 4px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .hero-badge { display: inline-flex; align-items: center; gap: 4px; background: rgba(39,174,96,.18); color: #4ade80; border: 1px solid rgba(39,174,96,.3); font-size: .68rem; font-weight: 700; padding: 2px 8px; border-radius: 20px; }
    .hero-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
    .btn-hero-action {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 9px 16px; border-radius: 50px; font-size: .78rem; font-weight: 700;
      text-decoration: none; transition: all var(--trans); cursor: pointer;
    }
    .btn-hero-gold {
      background: linear-gradient(135deg, var(--accent) 0%, #b89758 100%);
      color: #090c10; box-shadow: 0 4px 14px rgba(216,184,120,.3);
    }
    .btn-hero-gold:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(216,184,120,.4); }
    .btn-hero-outline {
      background: rgba(18,24,32,.7); border: 1px solid var(--border-color); color: var(--text);
    }
    .btn-hero-outline:hover { border-color: var(--accent); color: var(--accent); transform: translateY(-2px); }

    /* ── STATS CARDS ── */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }
    .stat-card {
      background: var(--card);
      border: 1px solid var(--card-border);
      border-radius: var(--radius);
      padding: 18px 20px;
      display: flex;
      align-items: center;
      gap: 16px;
      box-shadow: var(--shadow-sm);
      transition: transform var(--trans), border-color var(--trans);
    }
    .stat-card:hover { transform: translateY(-2px); border-color: var(--accent); }
    .stat-icon {
      width: 48px; height: 48px; border-radius: 12px;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .stat-icon.gold { background: rgba(216,184,120,.14); border: 1px solid rgba(216,184,120,.28); color: var(--accent); }
    .stat-icon.green { background: rgba(39,174,96,.14); border: 1px solid rgba(39,174,96,.28); color: #4ade80; }
    .stat-icon.red { background: rgba(231,76,60,.14); border: 1px solid rgba(231,76,60,.28); color: #f87171; }
    .stat-icon svg { width: 22px; height: 22px; }
    .stat-value { font-size: 1.45rem; font-weight: 800; color: var(--text); line-height: 1.1; }
    .stat-label { font-size: .74rem; color: var(--muted); margin-top: 3px; }
    .stat-desc { font-size: .65rem; color: var(--accent); margin-top: 2px; font-weight: 600; }

    /* ── TWO COLUMN MAIN LAYOUT ── */
    .dash-grid {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 22px;
      align-items: start;
    }

    /* Section Card */
    .section-card {
      background: var(--card);
      border: 1px solid var(--card-border);
      border-radius: var(--radius);
      padding: 20px 22px;
      margin-bottom: 22px;
      box-shadow: var(--shadow-sm);
    }
    .section-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 16px; padding-bottom: 12px;
      border-bottom: 1px solid var(--card-border);
    }
    .section-title {
      font-size: .95rem; font-weight: 800; color: var(--text);
      display: flex; align-items: center; gap: 8px;
    }
    .section-title svg { width: 17px; height: 17px; color: var(--accent); }
    .view-all-link { font-size: .75rem; color: var(--accent); text-decoration: none; font-weight: 700; transition: color var(--trans); }
    .view-all-link:hover { text-decoration: underline; }

    /* List Peminjaman Aktif */
    .loan-list { display: flex; flex-direction: column; gap: 12px; }
    .loan-item {
      display: flex; align-items: center; gap: 14px;
      padding: 12px 14px; border-radius: 10px;
      background: var(--book-card); border: 1px solid var(--border-color);
      transition: border-color var(--trans), transform var(--trans);
    }
    .loan-item:hover { border-color: var(--accent); transform: translateX(3px); }
    .loan-thumb {
      width: 42px; height: 60px; border-radius: 6px; overflow: hidden;
      flex-shrink: 0; background: linear-gradient(135deg, rgba(216,184,120,.2), rgba(0,0,0,.4));
      display: flex; align-items: center; justify-content: center;
    }
    .loan-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .loan-thumb svg { width: 20px; height: 20px; color: var(--accent); }
    .loan-info { flex: 1; min-width: 0; }
    .loan-title { font-size: .84rem; font-weight: 800; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .loan-author { font-size: .72rem; color: var(--muted); margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .loan-dates { display: flex; align-items: center; gap: 10px; font-size: .68rem; color: var(--muted); margin-top: 5px; flex-wrap: wrap; }
    .loan-status-badge {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: .68rem; font-weight: 700; padding: 4px 10px; border-radius: 20px;
      white-space: nowrap; flex-shrink: 0;
    }
    .loan-status-badge.ontime { background: rgba(39,174,96,.15); color: #4ade80; border: 1px solid rgba(39,174,96,.3); }
    .loan-status-badge.late { background: rgba(231,76,60,.15); color: #f87171; border: 1px solid rgba(231,76,60,.3); }

    /* Riwayat Table / Timeline */
    .history-item {
      display: flex; align-items: center; justify-content: space-between;
      padding: 10px 0; border-bottom: 1px solid rgba(216,184,120,.08);
      font-size: .78rem; gap: 10px;
    }
    .history-item:last-child { border-bottom: none; }
    .history-left { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .history-dot { width: 8px; height: 8px; border-radius: 50%; background: #4ade80; flex-shrink: 0; }
    .history-title { font-weight: 700; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .history-date { font-size: .7rem; color: var(--muted); white-space: nowrap; }

    /* Digital Card Widget — meniru desain kartu_anggota.php */
    .mc-card {
      background: linear-gradient(150deg, #1c1c28 0%, #2e2e40 55%, #46465c 100%);
      border-radius: 16px;
      padding: 18px 18px 16px;
      position: relative;
      overflow: hidden;
      color: #fff;
      box-shadow: 0 10px 28px rgba(0,0,0,.4);
    }
    .mc-card::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image:
        radial-gradient(circle at 85% 10%, rgba(255,255,255,.10), transparent 45%),
        radial-gradient(circle at 5% 95%, rgba(255,255,255,.06), transparent 40%);
      pointer-events: none;
    }
    .mc-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; position: relative; }
    .mc-brand { display: flex; align-items: center; gap: 8px; }
    .mc-mark {
      width: 28px; height: 28px; border-radius: 8px;
      background: linear-gradient(135deg, #fff, #a8a8c0);
      display: flex; align-items: center; justify-content: center;
      font-family: 'Cormorant Garamond', serif; font-weight: 700; color: #1c1c28; font-size: .95rem;
      flex-shrink: 0;
    }
    .mc-brandtext { font-family: 'Cormorant Garamond', serif; font-weight: 700; font-size: .95rem; letter-spacing: .04em; color: #fff; line-height: 1.2; }
    .mc-tag { font-size: .56rem; letter-spacing: .16em; color: #b8b8cc; text-transform: uppercase; margin-top: 1px; }
    .mc-label { font-size: .56rem; letter-spacing: .12em; text-transform: uppercase; color: #c8c8dc; text-align: right; line-height: 1.4; }

    .mc-body { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; position: relative; }
    .mc-avatar {
      width: 52px; height: 52px; border-radius: 12px;
      background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18);
      display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;
    }
    .mc-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .mc-avatar svg { width: 24px; height: 24px; color: #d8d8e8; }
    .mc-name { font-size: 1rem; font-weight: 700; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mc-meta { font-size: .72rem; color: #e0ac78; margin-top: 3px; font-weight: 500; }

    .mc-divider { height: 1px; background: rgba(255,255,255,.14); margin-bottom: 14px; position: relative; }

    .mc-foot { display: flex; align-items: center; justify-content: space-between; gap: 12px; position: relative; }
    .mc-cred { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
    .mc-credrow { display: flex; align-items: baseline; gap: 6px; }
    .mc-credlabel { font-size: .56rem; letter-spacing: .08em; text-transform: uppercase; color: #adadc7; width: 56px; flex-shrink: 0; }
    .mc-credvalue { font-family: 'JetBrains Mono', monospace; font-size: .76rem; font-weight: 600; color: #fff; letter-spacing: .02em; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mc-credvalue.muted { color: #e0ac78; font-weight: 500; font-size: .7rem; }

    .mc-qrwrap { flex-shrink: 0; text-align: center; }
    .mc-qrbox { background: #fff; padding: 5px; border-radius: 8px; line-height: 0; }
    .mc-qrbox img { display: block; width: 52px; height: 52px; }
    .mc-noanggota { font-family: 'JetBrains Mono', monospace; font-size: .6rem; color: #d8d8e8; margin-top: 5px; letter-spacing: .03em; }

    /* Shelf Mini */
    .shelf-mini { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
    .shelf-cover {
      aspect-ratio: 2/3; border-radius: 6px; overflow: hidden; position: relative;
      cursor: pointer; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);
      transition: transform var(--trans), border-color var(--trans);
      display: flex; align-items: center; justify-content: center;
    }
    .shelf-cover:hover { transform: translateY(-3px); border-color: var(--accent); }
    .shelf-cover img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .shelf-cover svg { width: 22px; height: 22px; color: rgba(255,255,255,.7); }

    /* Modal Detail Buku */
    .detail-overlay { position:fixed; inset:0; z-index:300; background:rgba(0,0,0,.7); backdrop-filter:blur(4px); display:none; align-items:center; justify-content:center; padding:16px; }
    .detail-overlay.open { display:flex; }
    .detail-modal { background:var(--card); border:1px solid var(--card-border); border-radius:16px; width:100%; max-width:540px; max-height:90vh; overflow-y:auto; box-shadow:var(--shadow-md); position:relative; }
    .detail-loading { padding:40px; text-align:center; color:var(--muted); font-size:.85rem; }
    .detail-close-btn { position:absolute; top:12px; right:12px; width:34px; height:34px; border-radius:50%; border:none; background:rgba(0,0,0,.45); color:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; }
    .detail-cover { width:100%; height:210px; background:var(--book-card); overflow:hidden; position:relative; }
    .detail-cover img { width:100%; height:100%; object-fit:cover; }
    .detail-body { padding:20px; }
    .detail-title { font-size:1.15rem; font-weight:800; color:var(--text); line-height:1.3; }
    .detail-author { font-size:.78rem; color:var(--muted); margin-top:4px; }
    .detail-desc { font-size:.78rem; color:var(--muted); line-height:1.6; margin-top:12px; }

    /* ── RESPONSIVE ── */

    /* Tablet landscape */
    @media (max-width: 992px) {
      .dash-grid { grid-template-columns: 1fr; }
    }

    /* Tablet portrait & Mobile */
    @media (max-width: 768px) {
      .sidebar {
        transform:translateX(-100%);
        width:min(calc(var(--sidebar-w) + 60px), 260px);
        padding-bottom:max(20px, env(safe-area-inset-bottom));
      }
      .sidebar.open { transform:translateX(0); }
      .mobile-topbar {
        display:flex; align-items:center; gap:12px;
        position:fixed; top:0; left:0; right:0; height:60px;
        padding:0 14px; padding-top:env(safe-area-inset-top,0);
        background:var(--sidebar-bg);
        border-bottom:1px solid var(--border-color);
        box-shadow:0 2px 18px rgba(0,0,0,.35); z-index:160;
        transition:opacity var(--trans), visibility var(--trans);
      }
      body.sidebar-open .mobile-topbar { opacity:0; visibility:hidden; pointer-events:none; }
      .mobile-topbar .sidebar-toggle { display:flex; position:static; box-shadow:none; flex-shrink:0; }
      .mobile-topbar-divider {
        display:block; width:1px; height:26px; flex-shrink:0;
        background:linear-gradient(180deg, transparent, var(--border-color) 50%, transparent);
      }
      .mobile-topbar-brand { display:flex; align-items:center; gap:7px; min-width:0; overflow:hidden; }
      .mobile-topbar-brand svg { width:19px; height:19px; color:var(--accent); flex-shrink:0; }
      .mobile-topbar-brand span {
        font-family:'Cormorant Garamond',serif; font-weight:700; font-size:.92rem;
        color:var(--accent); letter-spacing:.04em; white-space:nowrap;
      }
      .mobile-topbar .page-hud-controls { position:static; top:auto; right:auto; margin-left:auto; }
      .main { margin-left:0; padding:78px 14px 28px; }

      .user-hero { flex-direction:column; align-items:flex-start; gap:14px; padding:18px 16px; }
      .hero-actions { width:100%; gap:8px; }
      .btn-hero-action { flex:1; justify-content:center; padding:9px 12px; font-size:.76rem; }

      .stats-grid { grid-template-columns: repeat(2, 1fr); gap:12px; }
      .stat-card { padding:14px 14px; gap:12px; }
      .stat-icon { width:40px; height:40px; border-radius:10px; }
      .stat-icon svg { width:18px; height:18px; }
      .stat-value { font-size:1.25rem; }

      .section-card { padding:16px 14px; margin-bottom:16px; }
      .section-title { font-size:.88rem; }

      .loan-item { padding:10px 12px; gap:10px; }
      .loan-thumb { width:36px; height:52px; }
      .loan-title { font-size:.8rem; }
      .loan-author { font-size:.68rem; }
      .loan-dates { font-size:.64rem; gap:6px; }
      .loan-status-badge { font-size:.64rem; padding:3px 8px; }

      .mc-card { padding:16px; }
      .mc-name { font-size:.9rem; }

      .shelf-mini { grid-template-columns: repeat(4, 1fr); gap:8px; }

      .detail-overlay { align-items:flex-end; padding:0; }
      .detail-modal {
        max-width:100%; width:100%; margin:0;
        border-radius:18px 18px 0 0;
        max-height:88dvh;
      }
    }

    /* Mobile portrait (≤540px) */
    @media (max-width: 540px) {
      .main { padding:74px 10px 28px; }
      .user-hero { padding:14px 12px; }
      .hero-avatar { width:52px; height:52px; }
      .hero-avatar svg { width:24px; height:24px; }
      .hero-name { font-size:1.2rem; }
      .hero-greeting { font-size:.7rem; }
      .hero-sub { font-size:.68rem; gap:5px; }
      .hero-badge { font-size:.62rem; padding:2px 6px; }

      .stats-grid { grid-template-columns: 1fr; gap:10px; margin-bottom:18px; }
      .stat-card { padding:14px 16px; }

      .loan-item { flex-wrap:wrap; }
      .loan-info { flex:1; min-width:0; }
      .loan-status-badge { margin-top:4px; align-self:flex-start; }

      .mc-card { padding:14px 12px; }
      .mc-head { margin-bottom:12px; }
      .mc-mark { width:24px; height:24px; }
      .mc-body { gap:10px; margin-bottom:12px; }
      .mc-avatar { width:44px; height:44px; border-radius:10px; }
      .mc-name { font-size:.82rem; }
      .mc-meta { font-size:.66rem; }
      .mc-credvalue { font-size:.7rem; }
      .mc-qrbox img { width:44px; height:44px; }

      .shelf-mini { grid-template-columns: repeat(3, 1fr); gap:8px; }
    }

    /* Mobile kecil (≤480px) */
    @media (max-width: 480px) {
      .hero-name { font-size:1.1rem; }
      .hero-actions { flex-direction:column; gap:8px; }
      .btn-hero-action { width:100%; flex:none; }
      .stat-value { font-size:1.1rem; }
      .stat-label { font-size:.7rem; }
      .section-header { flex-direction:column; align-items:flex-start; gap:6px; }
      .loan-thumb { width:32px; height:46px; }
      .loan-title { font-size:.76rem; }
    }

    /* Layar super kecil (≤375px) */
    @media (max-width: 375px) {
      .main { padding:72px 8px 24px; }
      .user-hero { padding:12px 10px; }
      .hero-name { font-size:1rem; }
      .stats-grid { gap:8px; }
      .stat-card { padding:12px 12px; gap:10px; }
      .stat-icon { width:36px; height:36px; }
      .section-card { padding:12px 10px; }
      .shelf-mini { grid-template-columns: repeat(2, 1fr); gap:8px; }
      .loan-item { padding:8px 10px; }
      .loan-thumb { width:30px; height:44px; }
    }
  </style>
</head>
<body>

<header class="mobile-topbar" id="mobileTopbar">
  <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="3" y1="6" x2="21" y2="6"/>
      <line x1="3" y1="12" x2="21" y2="12"/>
      <line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  </button>
  <span class="mobile-topbar-divider" aria-hidden="true"></span>
  <div class="mobile-topbar-brand">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
      <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
      <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
    </svg>
    <span>AKSA NOVA</span>
  </div>

  <div class="page-hud-controls" id="pageHudControls">
    <button type="button" class="btn-topbar-mode" id="btnMode" aria-label="Ganti mode gelap/terang" title="Mode Gelap / Terang" aria-pressed="false">
      <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/>
      </svg>
      <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="4.2"/>
        <path d="M12 2.5v2.4M12 19.1v2.4M4.9 4.9l1.7 1.7M17.4 17.4l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.9 19.1l1.7-1.7M17.4 6.6l1.7-1.7"/>
      </svg>
    </button>

    <?php if ($musik_tampil): ?>
    <button type="button" class="btn-musik" id="btnMusik" aria-label="Musik Latar" title="<?= htmlspecialchars($musik_judul) ?>">
      <svg class="icon-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>
      </svg>
      <span class="icon-eq" aria-hidden="true"><span></span><span></span><span></span></span>
    </button>
    <audio id="audioLatar" loop autoplay muted preload="auto">
      <source src="<?= htmlspecialchars($musik_file) ?>">
    </audio>
    <?php endif; ?>
  </div>
</header>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
  <div class="logo-wrap">
    <div class="logo-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
      </svg>
    </div>
    <div class="logo-name">AKSA NOVA</div>
    <div class="logo-sub">Library Catalog App</div>
  </div>

  <nav class="nav">
    <a href="beranda.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Beranda
    </a>
    <a href="dashboard_user.php" class="nav-item active">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
      Dashboard
    </a>
    <a href="daftar_buku.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
      Daftar Buku
    </a>
    <a href="buku_simpan.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
      Buku Simpan
    </a>
    <a href="edit_kartu.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      Edit Profil
    </a>
  </nav>

  <div class="nav-bottom">
    <a href="#" class="nav-item" onclick="bukaSettings(); return false;" title="Pengaturan Tampilan">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
      Pengaturan
    </a>
    <a href="logout.php" class="nav-item" style="color:#e74c3c;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Keluar
    </a>
  </div>
</aside>

<main class="main">

  <!-- Welcome Hero -->
  <div class="user-hero">
    <div class="hero-profile-wrap">
      <div class="hero-avatar">
        <?php if (!empty($user["foto"]) && file_exists($user["foto"])): ?>
          <img src="<?= htmlspecialchars($user["foto"]) ?>?v=<?= filemtime($user["foto"]) ?>" alt="Foto profil"/>
        <?php else: ?>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <?php endif; ?>
      </div>
      <div class="hero-text">
        <div class="hero-greeting">Ruang Anggota Perpustakaan</div>
        <h1 class="hero-name"><?= htmlspecialchars($user["full_name"]) ?></h1>
        <div class="hero-sub">
          <span><?= htmlspecialchars($user["no_anggota"] ?: "—") ?></span>
          <span>·</span>
          <span><?= htmlspecialchars($user["kelas"] ?: "Umum") ?></span>
          <span class="hero-badge">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Anggota Aktif
          </span>
        </div>
      </div>
    </div>
    <div class="hero-actions">
      <a href="kartu_anggota.php" class="btn-hero-action btn-hero-gold">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="7" y1="8" x2="17" y2="8"/><line x1="7" y1="12" x2="13" y2="12"/></svg>
        Kartu Anggota
      </a>
    </div>
  </div>

  <!-- Statistik Ringkasan -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon gold">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      </div>
      <div>
        <div class="stat-value"><?= $total_pinjam ?></div>
        <div class="stat-label">Total Peminjaman</div>
        <div class="stat-desc">Keseluruhan peminjaman</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon green">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <div>
        <div class="stat-value"><?= $sedang_dipinjam ?></div>
        <div class="stat-label">Sedang Dipinjam</div>
        <div class="stat-desc">Buku aktif di tangan kamu</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon red">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </div>
      <div>
        <div class="stat-value" style="<?= $terlambat_pinjam > 0 ? 'color:#f87171;' : '' ?>"><?= $terlambat_pinjam ?></div>
        <div class="stat-label">Terlambat Kembali</div>
        <div class="stat-desc" style="<?= $total_denda_semua > 0 ? 'color:#f87171;' : '' ?>">
          <?= $total_denda_semua > 0 ? "Denda: Rp " . number_format($total_denda_semua, 0, ",", ".") : "Bebas denda" ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Dua Kolom Detail -->
  <div class="dash-grid">

    <!-- KOLOM KIRI: Buku Dipinjam & Riwayat -->
    <div class="col-left">

      <!-- Buku Sedang Dipinjam -->
      <div class="section-card">
        <div class="section-header">
          <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
            Buku yang Sedang Kamu Pinjam
          </div>
          <span style="font-size:.72rem;color:var(--muted);"><?= count($pinjaman_aktif) ?> buku</span>
        </div>

        <?php if (empty($pinjaman_aktif)): ?>
          <div style="text-align:center;padding:32px 14px;color:var(--muted);">
            <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="margin-bottom:8px;opacity:.4;color:var(--accent);"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            <div style="font-size:.84rem;font-weight:700;color:var(--text);">Tidak ada peminjaman buku aktif</div>
            <div style="font-size:.74rem;margin-top:4px;">Kamu sedang tidak meminjam buku apa pun saat ini. Silakan kunjungi katalog untuk meminjam buku.</div>
            <a href="daftar_buku.php" class="btn-hero-action btn-hero-gold" style="margin-top:14px;display:inline-flex;">Jelajahi Buku</a>
          </div>
        <?php else: ?>
          <div class="loan-list">
            <?php foreach ($pinjaman_aktif as $p):
              $tgl_pinjam = date("d M Y", strtotime($p["waktu_pinjam"]));
              $tgl_batas  = date("d M Y", strtotime($p["batas_kembali"]));
              $is_late    = (strtotime($p["batas_kembali"]) < time());
              $diff_days  = abs((int)round((time() - strtotime($p["batas_kembali"])) / 86400));
            ?>
            <div class="loan-item" onclick="bukaDetailBuku(<?= (int)$p['buku_id'] ?>)" style="cursor:pointer;" title="Klik untuk melihat detail buku">
              <div class="loan-thumb">
                <?php if (!empty($p["gambar"]) && file_exists($p["gambar"])): ?>
                  <img src="<?= htmlspecialchars($p["gambar"]) ?>" alt="<?= htmlspecialchars($p["judul"] ?? "Buku") ?>"/>
                <?php else: ?>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                <?php endif; ?>
              </div>
              <div class="loan-info">
                <div class="loan-title"><?= htmlspecialchars($p["judul"] ?? "Buku Perpustakaan") ?></div>
                <div class="loan-author">✍️ <?= htmlspecialchars($p["penulis"] ?? "Penulis tidak diketahui") ?></div>
                <div class="loan-dates">
                  <span>Pinjam: <strong><?= $tgl_pinjam ?></strong></span>
                  <span>·</span>
                  <span>Jatuh Tempo: <strong style="<?= $is_late ? 'color:#f87171;' : '' ?>"><?= $tgl_batas ?></strong></span>
                </div>
              </div>
              <div>
                <?php if ($is_late): ?>
                  <span class="loan-status-badge late" style="flex-direction:column;align-items:flex-end;gap:1px;text-align:right;">
                    <span>⚠ Terlambat <?= $diff_days ?> Hari</span>
                    <?php if ($denda_aktif && $diff_days > 0): ?>
                      <span style="font-size:.62rem;opacity:.9;">Denda: Rp <?= number_format($diff_days * $denda_per_hari, 0, ',', '.') ?></span>
                    <?php endif; ?>
                  </span>
                <?php else: ?>
                  <span class="loan-status-badge ontime">
                    ✓ Aktif Dipinjam
                  </span>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Riwayat Peminjaman Selesai -->
      <div class="section-card">
        <div class="section-header">
          <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            Riwayat Buku yang Pernah Dipinjam
          </div>
          <span style="font-size:.72rem;color:var(--muted);"><?= count($riwayat_kembali) ?> terakhir</span>
        </div>

        <?php if (empty($riwayat_kembali)): ?>
          <div style="text-align:center;padding:18px 0;color:var(--muted);font-size:.75rem;">Belum ada riwayat pengembalian buku.</div>
        <?php else: ?>
          <div>
            <?php foreach ($riwayat_kembali as $h):
              $tgl_kembali = $h["waktu_kembali"] ? date("d M Y", strtotime($h["waktu_kembali"])) : "—";
            ?>
            <div class="history-item">
              <div class="history-left">
                <div class="history-dot"></div>
                <div class="history-title"><?= htmlspecialchars($h["judul"] ?? "Buku Perpustakaan") ?></div>
              </div>
              <div class="history-date">Dikembalikan: <?= $tgl_kembali ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div><!-- /col-left -->

    <!-- KOLOM KANAN: Kartu Anggota, Buku Disimpan, Aturan -->
    <div class="col-right">

      <!-- Kartu Anggota Digital -->
      <div class="section-card" style="padding:16px;">
        <div class="mc-card">
          <div class="mc-head">
            <div class="mc-brand">
              <div class="mc-mark">A</div>
              <div>
                <div class="mc-brandtext">AKSA NOVA</div>
                <div class="mc-tag">Kartu Anggota</div>
              </div>
            </div>
            <div class="mc-label">Member<br>Card</div>
          </div>

          <div class="mc-body">
            <div class="mc-avatar">
              <?php if (!empty($user["foto"]) && file_exists($user["foto"])): ?>
                <img src="<?= htmlspecialchars($user["foto"]) ?>?v=<?= filemtime($user["foto"]) ?>" alt="Foto"/>
              <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
              <?php endif; ?>
            </div>
            <div style="min-width:0;">
              <div class="mc-name"><?= htmlspecialchars($user["full_name"]) ?></div>
              <div class="mc-meta">Kelas <?= htmlspecialchars($user["kelas"] ?: "Umum") ?></div>
            </div>
          </div>

          <div class="mc-divider"></div>

          <div class="mc-foot">
            <div class="mc-cred">
              <div class="mc-credrow">
                <span class="mc-credlabel">Username</span>
                <span class="mc-credvalue"><?= htmlspecialchars($user["username"]) ?></span>
              </div>
              <div class="mc-credrow">
                <span class="mc-credlabel">Password</span>
                <span class="mc-credvalue muted">Tidak berubah</span>
              </div>
            </div>
            <div class="mc-qrwrap">
              <div class="mc-qrbox">
                <img src="<?= htmlspecialchars($qr_url) ?>" alt="QR anggota"/>
              </div>
              <div class="mc-noanggota"><?= htmlspecialchars($user["no_anggota"] ?: "MEMBER-AKSA") ?></div>
            </div>
          </div>
        </div>

        <div style="display:flex;gap:8px;margin-top:14px;">
          <a href="kartu_anggota.php" class="btn-hero-action btn-hero-gold" style="flex:1;justify-content:center;font-size:.74rem;padding:8px 12px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Unduh Kartu
          </a>
          <a href="edit_kartu.php" class="btn-hero-action btn-hero-outline" style="flex:1;justify-content:center;font-size:.74rem;padding:8px 12px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit Profil
          </a>
        </div>
      </div>

      <!-- Buku Disimpan Ringkas -->
      <div class="section-card">
        <div class="section-header">
          <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
            Buku Disimpan
          </div>
          <a href="buku_simpan.php" class="view-all-link">Lihat Semua &rsaquo;</a>
        </div>
        <?php if (empty($saved_books)): ?>
          <div style="text-align:center;padding:12px 0;color:var(--muted);font-size:.74rem;">Belum ada buku yang kamu simpan.</div>
        <?php else: ?>
          <div class="shelf-mini">
            <?php foreach ($saved_books as $sb): ?>
            <div class="shelf-cover" onclick="bukaDetailBuku(<?= $sb['id'] ?>)" title="<?= htmlspecialchars($sb['judul']) ?>">
              <?php if (!empty($sb["gambar"]) && file_exists($sb["gambar"])): ?>
                <img src="<?= htmlspecialchars($sb["gambar"]) ?>" alt="<?= htmlspecialchars($sb["judul"]) ?>"/>
              <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Informasi Tata Tertib Ringkas -->
      <div class="section-card">
        <div class="section-header">
          <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            Ketentuan Peminjaman
          </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;font-size:.74rem;color:var(--muted);line-height:1.5;">
          <div>• Masa pinjam buku maksimal <strong>7 hari kalender</strong> sejak tanggal peminjaman.</div>
          <div>• <?= $denda_aktif ? "Denda keterlambatan sebesar <strong>Rp " . number_format($denda_per_hari, 0, ",", ".") . " / hari</strong>." : "Keterlambatan tetap dicatat oleh sistem perpustakaan." ?></div>
          <div>• Harap menjaga keutuhan buku dan mengembalikan tepat waktu.</div>
        </div>
      </div>

    </div><!-- /col-right -->

  </div><!-- /dash-grid -->

</main>

<!-- Modal Detail Buku -->
<div class="detail-overlay" id="detailOverlay">
  <div class="detail-modal">
    <button class="detail-close-btn" onclick="tutupDetailBuku()">✕</button>
    <div id="detailContent">
      <div class="detail-loading">Memuat detail buku…</div>
    </div>
  </div>
</div>

<script>
  // Sidebar Toggle
  const toggle  = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  if (toggle && sidebar && overlay) {
    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      overlay.classList.toggle('open');
      document.body.classList.toggle('sidebar-open', sidebar.classList.contains('open'));
    });
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('open');
      overlay.classList.remove('open');
      document.body.classList.remove('sidebar-open');
    });
  }

  // Modal Detail Buku
  function bukaDetailBuku(id) {
    const detailOverlay = document.getElementById('detailOverlay');
    const content = document.getElementById('detailContent');
    content.innerHTML = `<div class="detail-loading">Memuat detail buku…</div>`;
    detailOverlay.classList.add('open');

    fetch('buku_detail.php?id=' + id)
      .then(r => r.json())
      .then(data => {
        if (!data.ok) { content.innerHTML = '<div class="detail-loading">Gagal memuat detail buku.</div>'; return; }
        const b = data.buku;
        content.innerHTML = `
          <div class="detail-cover">
            ${b.gambar ? `<img src="${b.gambar}" alt="${b.judul}">` : ''}
          </div>
          <div class="detail-body">
            <div class="detail-title">${b.judul}</div>
            <div class="detail-author">✍️ ${b.penulis || 'Penulis tidak diketahui'} · Kategori: ${b.genre || 'Umum'}</div>
            <div class="detail-desc">${b.sinopsis ? b.sinopsis.replace(/\\n/g, '<br>') : 'Sinopsis belum tersedia.'}</div>
          </div>
        `;
      })
      .catch(() => { content.innerHTML = '<div class="detail-loading">Gagal memuat detail buku.</div>'; });
  }

  function tutupDetailBuku() {
    document.getElementById('detailOverlay').classList.remove('open');
  }
  document.getElementById('detailOverlay').addEventListener('click', function(e) {
    if (e.target === this) tutupDetailBuku();
  });
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') tutupDetailBuku();
  });

  // Mode Gelap / Terang
  (function () {
    const btn  = document.getElementById('btnMode');
    if (!btn) return;
    const root = document.documentElement;

    function updatePressed() {
      const isLight = root.classList.contains('theme-light') || root.classList.contains('light') || (localStorage.getItem('aksanova_theme') === 'light');
      btn.setAttribute('aria-pressed', isLight ? 'true' : 'false');
    }
    updatePressed();

    btn.addEventListener('click', () => {
      const isCurrentlyLight = root.classList.contains('theme-light') || root.classList.contains('light') || (localStorage.getItem('aksanova_theme') === 'light');
      const targetMode = isCurrentlyLight ? 'dark' : 'light';
      if (typeof window.setMode === 'function') {
        window.setMode(targetMode);
      } else {
        root.classList.toggle('theme-light', targetMode === 'light');
        root.classList.toggle('light', targetMode === 'light');
        root.classList.toggle('dark', targetMode === 'dark');
        try { localStorage.setItem('aksanova_theme', targetMode); } catch (e) {}
      }
      updatePressed();
    });
  })();

  // Musik Latar
  (function () {
    const btn   = document.getElementById('btnMusik');
    const audio = document.getElementById('audioLatar');
    if (!btn || !audio) return;

    audio.volume = 0.55;
    let userPaused = false;
    let autoplaySucceeded = false;

    function setPlaying(isPlaying) {
      btn.classList.toggle('playing', isPlaying);
      btn.setAttribute('aria-pressed', isPlaying ? 'true' : 'false');
    }

    function removeFallback() {
      ['click','touchstart','keydown','scroll'].forEach(ev => document.removeEventListener(ev, fallback));
    }

    function play() {
      audio.play().then(() => {
        audio.muted = false;
        autoplaySucceeded = true;
        removeFallback();
        setPlaying(true);
      }).catch(() => setPlaying(false));
    }

    function pause() {
      audio.pause();
      setPlaying(false);
    }

    function fallback() {
      if (userPaused || autoplaySucceeded) return;
      audio.muted = false;
      play();
    }

    audio.play().then(() => {
      audio.muted = false;
      autoplaySucceeded = true;
      setPlaying(true);
    }).catch(() => {
      audio.muted = true;
      audio.play().then(() => {
        setPlaying(true);
        ['click','touchstart','keydown','scroll'].forEach(ev => {
          document.addEventListener(ev, fallback, { once: true, passive: true });
        });
      }).catch(() => setPlaying(false));
    });

    btn.addEventListener('click', () => {
      if (audio.paused) {
        userPaused = false;
        audio.muted = false;
        play();
      } else {
        userPaused = true;
        pause();
      }
    });

    audio.addEventListener('play',  () => setPlaying(true));
    audio.addEventListener('pause', () => setPlaying(false));
    audio.addEventListener('ended', () => setPlaying(false));
  })();
</script>
<?php require_once "pengaturan_panel.php"; ?>
</body>
</html>
<?php ob_end_flush(); ?>