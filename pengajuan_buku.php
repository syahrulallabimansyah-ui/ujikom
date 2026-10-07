<?php
// pengajuan_buku.php — Halaman Admin Kelola Pengajuan Peminjaman Buku dari Anggota
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

date_default_timezone_set('Asia/Jakarta');
mysqli_query($conn, "SET time_zone = '+07:00'");

$page_title = 'Pengajuan Peminjaman – AKSA NOVA';
$msg        = '';
$msg_type   = '';

// ─── Profil admin ───
$profil_res = mysqli_query($conn, 'SELECT * FROM admin_profile LIMIT 1');
$profil     = $profil_res ? mysqli_fetch_assoc($profil_res) : null;
$admin_name = $profil['display_name'] ?? ($_SESSION['user_name'] ?? 'Admin');
$admin_foto = $profil['foto'] ?? '';

// Jumlah anggota berstatus pending (badge sidebar, konsisten dengan halaman_admin.php)
$pending_res   = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='member' AND status='pending'");
$pending_count = $pending_res ? (int)(mysqli_fetch_assoc($pending_res)['c'] ?? 0) : 0;

// ─────────────────────────────────────────────
//  AKSI: Setujui / Tolak Pengajuan
// ─────────────────────────────────────────────
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pengajuan_id = (int)($_POST['pengajuan_id'] ?? 0);

    if ($action === 'setujui' && $pengajuan_id > 0) {
        $p_stmt = mysqli_prepare($conn, "SELECT p.*, b.stok, b.judul FROM pengajuan_peminjaman p LEFT JOIN buku b ON b.id = p.buku_id WHERE p.id = ? AND p.status = 'menunggu' LIMIT 1");
        mysqli_stmt_bind_param($p_stmt, "i", $pengajuan_id);
        mysqli_stmt_execute($p_stmt);
        $p_res = mysqli_stmt_get_result($p_stmt);
        $pengajuan = mysqli_fetch_assoc($p_res);
        mysqli_stmt_close($p_stmt);

        if (!$pengajuan) {
            $msg = 'Pengajuan tidak ditemukan atau sudah diproses sebelumnya.';
            $msg_type = 'error';
        } else {
            $buku_id    = (int)$pengajuan['buku_id'];
            $user_id    = (int)$pengajuan['user_id'];
            $total_buku = max(1, (int)$pengajuan['total_buku']);
            $current_stok = (int)($pengajuan['stok'] ?? 0);

            if ($current_stok < $total_buku) {
                $msg = 'Stok buku "' . htmlspecialchars($pengajuan['judul']) . '" tidak mencukupi (sisa: ' . $current_stok . ', diminta: ' . $total_buku . '). Pengajuan tidak dapat disetujui.';
                $msg_type = 'error';
            } else {
                // Kurangi stok buku secara aman (atomic)
                mysqli_query($conn, "UPDATE buku SET stok = stok - $total_buku WHERE id = $buku_id AND stok >= $total_buku");

                if (mysqli_affected_rows($conn) > 0) {
                    $nama_peminjam = mysqli_real_escape_string($conn, $pengajuan['nama_peminjam']);
                    $batas_kembali = $pengajuan['batas_kembali'] . ' 23:59:59';
                    $waktu_pinjam  = date('Y-m-d H:i:s');

                    // Masukkan ke tabel peminjaman sebanyak total_buku eksemplar
                    for ($i = 0; $i < $total_buku; $i++) {
                        mysqli_query($conn,
                            "INSERT INTO peminjaman (buku_id, user_id, nama_peminjam, waktu_pinjam, batas_kembali, status)
                             VALUES ($buku_id, $user_id, '$nama_peminjam', '$waktu_pinjam', '$batas_kembali', 'dipinjam')"
                        );
                    }

                    // Update status pengajuan
                    $now = date('Y-m-d H:i:s');
                    mysqli_query($conn, "UPDATE pengajuan_peminjaman SET status = 'disetujui', approved_at = '$now' WHERE id = $pengajuan_id");

                    $msg = 'Pengajuan peminjaman berhasil disetujui! ' . $total_buku . ' eksemplar buku otomatis masuk ke <a href="telah_dipinjam.php" style="color:var(--accent, #d8b878);font-weight:700;text-decoration:underline;">Telah Dipinjam</a>.';
                    $msg_type = 'success';
                } else {
                    $msg = 'Gagal mengurangi stok buku. Kemungkinan stok baru saja berkurang oleh transaksi lain.';
                    $msg_type = 'error';
                }
            }
        }
    } elseif ($action === 'tolak' && $pengajuan_id > 0) {
        $alasan = trim($_POST['alasan_penolakan'] ?? 'Pengajuan ditolak oleh petugas perpustakaan.');
        if ($alasan === '') $alasan = 'Pengajuan ditolak oleh petugas perpustakaan.';

        $now = date('Y-m-d H:i:s');
        $t_stmt = mysqli_prepare($conn, "UPDATE pengajuan_peminjaman SET status = 'ditolak', alasan_penolakan = ?, approved_at = ? WHERE id = ? AND status = 'menunggu'");
        mysqli_stmt_bind_param($t_stmt, "ssi", $alasan, $now, $pengajuan_id);
        mysqli_stmt_execute($t_stmt);

        if (mysqli_stmt_affected_rows($t_stmt) > 0) {
            $msg = 'Pengajuan peminjaman telah ditolak.';
            $msg_type = 'success';
        } else {
            $msg = 'Gagal memproses penolakan pengajuan.';
            $msg_type = 'error';
        }
        mysqli_stmt_close($t_stmt);
    }
}

// ─────────────────────────────────────────────
//  HITUNG STATISTIK PENGAJUAN
// ─────────────────────────────────────────────
$cnt_total    = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM pengajuan_peminjaman"))['c'] ?? 0);
$cnt_menunggu = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'"))['c'] ?? 0);
$cnt_disetujui= (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='disetujui'"))['c'] ?? 0);
$cnt_ditolak  = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='ditolak'"))['c'] ?? 0);

// ─────────────────────────────────────────────
//  FILTER & AMBIL DATA PENGAJUAN
// ─────────────────────────────────────────────
$tab    = $_GET['tab'] ?? 'menunggu';
$search = trim($_GET['q'] ?? '');

$where = "WHERE 1=1";
if ($tab === 'menunggu') {
    $where .= " AND p.status = 'menunggu'";
} elseif ($tab === 'disetujui') {
    $where .= " AND p.status = 'disetujui'";
} elseif ($tab === 'ditolak') {
    $where .= " AND p.status = 'ditolak'";
}

if ($search !== '') {
    $s = mysqli_real_escape_string($conn, $search);
    $where .= " AND (p.nama_peminjam LIKE '%$s%' OR b.judul LIKE '%$s%' OR u.kelas LIKE '%$s%' OR u.no_anggota LIKE '%$s%')";
}

$pengajuan_list = [];
$q_res = mysqli_query($conn,
    "SELECT p.*, b.judul, b.penulis, b.gambar as buku_gambar, b.stok as buku_stok, b.rak as buku_rak, u.kelas, u.no_anggota, u.email, u.no_hp, u.foto as user_foto
     FROM pengajuan_peminjaman p
     LEFT JOIN buku b ON b.id = p.buku_id
     LEFT JOIN users u ON u.id = p.user_id
     $where
     ORDER BY p.id DESC"
);
if ($q_res) {
    while ($r = mysqli_fetch_assoc($q_res)) {
        $pengajuan_list[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --sidebar-bg:  #10151b;
      --accent:      #d8b878;
      --btn-primary: linear-gradient(135deg,#d8b878,#c8a060);
      --text:        #eef3f4;
      --muted:       rgba(238,243,244,.65);
      --bg:          #090c10;
      --card:        #121820;
      --book-card:   #161e27;
      --border-color:rgba(216,184,120,.18);
      --card-border: rgba(216,184,120,.12);
      --radius:      10px;
      --sidebar-w:   204px;
      --trans:       .2s cubic-bezier(.22,1,.36,1);
      --shadow:      0 2px 12px rgba(0,0,0,.3);
    }

    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    body {
      font-family: var(--font-family, 'Outfit', sans-serif);
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
    }

    /* ── SIDEBAR (identik dengan halaman_admin.php) ── */
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

    .avatar-wrap { position:relative; margin-bottom:14px; }
    .avatar-circle {
      width:80px; height:80px; max-width:80px; max-height:80px; border-radius:50%;
      background:#c0c0c8; overflow:hidden;
      border:3px solid rgba(255,255,255,.25);
      display:flex; align-items:center; justify-content:center;
    }
    .avatar-circle img { width:100%; height:100%; object-fit:cover; display:block; }
    .avatar-circle .default-icon { width:40px; height:40px; color:#888; }

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

    .badge-nav {
      margin-left:auto; background:rgba(245,158,11,.2); color:#fbbf24;
      border:1px solid rgba(245,158,11,.35); font-size:.65rem; font-weight:800;
      padding:2px 7px; border-radius:12px; line-height:1;
    }

    /* ── MAIN ── */
    .main { view-transition-name:app-main; margin-left:var(--sidebar-w); flex:1; min-width:0; padding:28px 36px 60px; }

    .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; gap:16px; flex-wrap:wrap; }
    .page-title { font-family:'Cormorant Garamond',serif; font-size:2rem; font-weight:700; color:var(--text); line-height:1.1; }
    .page-sub { font-size:.82rem; color:var(--muted); margin-top:4px; }

    /* Alert */
    .alert { padding:14px 18px; border-radius:10px; margin-bottom:24px; font-size:.85rem; display:flex; align-items:center; gap:12px; }
    .alert-success { background:rgba(5,150,105,.15); color:#4ade80; border:1px solid rgba(5,150,105,.3); }
    .alert-error   { background:rgba(220,38,38,.15); color:#f87171; border:1px solid rgba(220,38,38,.3); }

    /* Stats Grid */
    .stats-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; margin-bottom:26px; }
    .stat-card {
      background:var(--card); border:1px solid var(--card-border); border-radius:var(--radius);
      padding:18px 20px; display:flex; align-items:center; gap:16px; box-shadow:var(--shadow);
    }
    .stat-icon { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .stat-icon svg { width:22px; height:22px; }
    .stat-icon.gold  { background:#eef0f5; color:var(--btn-primary); border:1px solid var(--border-color); }
    .stat-icon.warn  { background:#fff3e0; color:#c67c0f; border:1px solid #ffe0b2; }
    .stat-icon.green { background:#e8f5e9; color:#1a8a4a; border:1px solid #c8e6c9; }
    .stat-icon.red   { background:#fce4ec; color:#c0392b; border:1px solid #f8bbd0; }
    .stat-val { font-size:1.6rem; font-weight:800; color:var(--text); line-height:1; }
    .stat-lbl { font-size:.74rem; font-weight:600; color:var(--muted); margin-top:4px; text-transform:uppercase; letter-spacing:.05em; }

    /* Top Filter & Search */
    .filter-bar {
      display:flex; align-items:center; justify-content:space-between; gap:14px;
      margin-bottom:20px; flex-wrap:wrap;
    }
    .tabs-wrap { display:flex; gap:6px; background:var(--card); padding:4px; border-radius:10px; border:1px solid var(--card-border); }
    .tab-btn {
      padding:7px 14px; border-radius:7px; font-size:.76rem; font-weight:700; color:var(--muted);
      text-decoration:none; transition:all var(--trans); display:flex; align-items:center; gap:6px;
    }
    .tab-btn:hover { color:var(--text); }
    .tab-btn.active { background:linear-gradient(135deg,#d8b878,#c8a060); color:#121820; border-color:#d8b878; }
    .tab-count { font-size:.65rem; padding:1px 6px; border-radius:10px; background:rgba(255,255,255,.08); color:var(--muted); }

    .search-box { display:flex; align-items:center; gap:8px; background:var(--card); border:1px solid var(--card-border); border-radius:10px; padding:6px 12px; }
    .search-box svg { width:15px; height:15px; color:var(--muted); }
    .search-box input { background:transparent; border:none; color:var(--text); font-family:inherit; font-size:.82rem; outline:none; width:220px; }

    /* Table */
    .table-card {
      background:var(--card); border:1px solid var(--card-border); border-radius:var(--radius);
      overflow:hidden; box-shadow:var(--shadow);
    }
    table { width:100%; border-collapse:collapse; text-align:left; font-size:.8rem; }
    thead tr { background:#f7f7fb; border-bottom:1px solid var(--border-color); }
    th { padding:14px 16px; font-weight:700; color:var(--accent); font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; }
    tbody tr { border-bottom:1px solid var(--card-border); transition:background var(--trans); }
    tbody tr:hover { background:rgba(255,255,255,.04); }
    td { padding:14px 16px; vertical-align:middle; color:var(--text); }

    /* Borrower info */
    .borrower-wrap { display:flex; align-items:center; gap:10px; }
    .borrower-avatar { width:34px; height:34px; border-radius:50%; object-fit:cover; border:1px solid var(--border-color); flex-shrink:0; }
    .borrower-name { font-weight:700; color:var(--text); font-size:.82rem; }
    .borrower-sub { font-size:.7rem; color:var(--muted); }

    /* Book Info */
    .book-cell { display:flex; align-items:center; gap:10px; max-width:260px; }
    .book-thumb { width:38px; height:50px; border-radius:6px; object-fit:cover; border:1px solid var(--card-border); flex-shrink:0; background:var(--book-card); }
    .book-thumb-empty { width:38px; height:50px; border-radius:6px; border:1px solid var(--card-border); background:var(--book-card); display:flex; align-items:center; justify-content:center; color:#c9cadb; flex-shrink:0; }
    .book-title-cell { font-weight:700; font-size:.8rem; color:var(--text); line-height:1.3; }
    .book-author-cell { font-size:.7rem; color:var(--muted); }

    /* Member Card Thumbnail Trigger */
    .card-thumb-btn {
      display:inline-flex; align-items:center; gap:6px; padding:5px 9px; border-radius:6px;
      background:#eef0ff; border:1px solid #dbe0ff;
      color:#2b4fff; font-size:.72rem; font-weight:700; cursor:pointer; transition:all var(--trans);
    }
    .card-thumb-btn:hover { background:rgba(216,184,120,.2); border-color:var(--accent,#d8b878); }
    .card-thumb-btn svg { width:13px; height:13px; }

    /* Badges */
    .badge { display:inline-flex; align-items:center; gap:5px; padding:4px 9px; border-radius:6px; font-size:.7rem; font-weight:700; }
    .badge-menunggu { background:#fff3e0; color:#c67c0f; border:1px solid #ffe0b2; }
    .badge-disetujui{ background:#e8f5e9; color:#1a8a4a; border:1px solid #c8e6c9; }
    .badge-ditolak  { background:#fce4ec; color:#c0392b; border:1px solid #f8bbd0; }

    .badge-pickup { display:inline-flex; align-items:center; gap:4px; font-size:.68rem; font-weight:700; padding:3px 7px; border-radius:4px; margin-top:3px; }
    .badge-pickup.sekarang { background:rgba(216,184,120,.12); color:var(--btn-primary); }
    .badge-pickup.nanti { background:rgba(59,130,246,.12); color:#93c5fd; }

    /* Action Buttons */
    .action-group { display:flex; align-items:center; gap:6px; }
    .btn-action-approve {
      padding:6px 12px; border-radius:6px; border:none;
      background:#e8f5e9; color:#1a8a4a; border:1px solid #c8e6c9;
      font-family:inherit; font-size:.72rem; font-weight:800; cursor:pointer;
      display:inline-flex; align-items:center; gap:5px; transition:all var(--trans);
    }
    .btn-action-approve:hover { background:rgba(5,150,105,.25); border-color:rgba(5,150,105,.5); }
    .btn-action-reject {
      padding:6px 12px; border-radius:6px; border:none;
      background:#fce4ec; color:#c0392b; border:1px solid #f8bbd0;
      font-family:inherit; font-size:.72rem; font-weight:800; cursor:pointer;
      display:inline-flex; align-items:center; gap:5px; transition:all var(--trans);
    }
    .btn-action-reject:hover { background:rgba(220,38,38,.25); border-color:rgba(220,38,38,.5); }

    /* Modals */
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.75); backdrop-filter:blur(4px); z-index:300; align-items:center; justify-content:center; padding:20px; }
    .modal-overlay.open { display:flex; }
    .modal-box { width:100%; max-width:520px; background:var(--card); border:1px solid var(--border-color); border-radius:14px; overflow:hidden; box-shadow:0 24px 70px rgba(0,0,0,.6); }
    .modal-head { padding:16px 20px; border-bottom:1px solid var(--card-border); display:flex; align-items:center; justify-content:space-between; }
    .modal-head-title { font-size:.95rem; font-weight:700; color:var(--text); }
    .modal-close { background:none; border:none; color:var(--muted); font-size:1.1rem; cursor:pointer; padding:4px; line-height:1; }
    .modal-body { padding:20px; }
    .modal-foot { padding:14px 20px; border-top:1px solid var(--card-border); display:flex; justify-content:flex-end; gap:10px; }

    .textarea-reject {
      width:100%; height:90px; padding:10px 12px; border-radius:8px; border:1px solid var(--card-border);
      background:var(--book-card); color:var(--text); font-family:inherit; font-size:.82rem;
      outline:none; resize:none; margin-top:8px;
    }
    .textarea-reject:focus { border-color:var(--btn-primary); }

    /* Sidebar Toggle & Overlay */
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

    .sidebar-overlay {
      position:fixed; inset:0; background:rgba(9,12,16,.65);
      backdrop-filter:blur(3px); z-index:165;
      opacity:0; visibility:hidden; transition:opacity var(--trans), visibility var(--trans);
    }
    .sidebar-overlay.open { opacity:1; visibility:visible; }

    /* Responsive */
    @media (max-width:1100px) {
      .stats-grid { grid-template-columns:repeat(2, 1fr); }
    }
    @media (max-width:768px) {
      .main { margin-left:0; padding:20px 16px; padding-top:68px; }
      .sidebar {
        transform:translateX(-100%);
        z-index:170;
      }
      .sidebar.open { transform:translateX(0); }
      .sidebar-toggle { display:flex; }
      .stats-grid { grid-template-columns:1fr; }
      .table-card { overflow-x:auto; }
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
  <?php require_once 'settings_include.php'; ?>
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
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
    <span>AKSA NOVA • Pengajuan Buku</span>
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

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">

  <div class="sidebar-header">
    <div class="avatar-wrap" onclick="openProfilModal()" title="Edit Profil">
      <div class="avatar-circle">
        <?php if ($admin_foto && file_exists($admin_foto)): ?>
          <img src="<?= htmlspecialchars($admin_foto) ?>?v=<?= filemtime($admin_foto) ?>" alt="Admin" width="80" height="80" loading="eager" decoding="sync"/>
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
      Daftar Anggota
      <?php if ($pending_count > 0): ?>
        <span style="margin-left:auto;background:#e74c3c;color:#fff;font-size:.65rem;font-weight:800;padding:2px 7px;border-radius:20px;"><?= $pending_count ?></span>
      <?php endif; ?>
    </a>
    <a class="sidebar-btn" href="pinjam_buku.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      Pinjam Buku
    </a>
    <a class="sidebar-btn active" href="pengajuan_buku.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
      Pengajuan Buku
      <?php if ($cnt_menunggu > 0): ?>
        <span class="badge-nav"><?= $cnt_menunggu ?></span>
      <?php endif; ?>
    </a>
    <a class="sidebar-btn" href="telah_dipinjam.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      Telah Dipinjam
    </a>
    <a class="sidebar-btn" href="pengaturan_denda.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
      Pengaturan Denda
    </a>
    <a class="sidebar-btn" href="pengaturan_musik.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
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

  <div class="page-header">
    <div>
      <h1 class="page-title">Kelola Pengajuan Peminjaman</h1>
      <p class="page-sub">Tinjau permohonan peminjaman buku dari anggota perpustakaan, verifikasi kartu anggota, dan setujui peminjaman.</p>
    </div>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
      <div><?= $msg ?></div>
    </div>
  <?php endif; ?>

  <!-- Ringkasan Statistik -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon warn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <div>
        <div class="stat-val" style="color:#c67c0f;"><?= $cnt_menunggu ?></div>
        <div class="stat-lbl">Menunggu Persetujuan</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon green">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <div>
        <div class="stat-val" style="color:#1a8a4a;"><?= $cnt_disetujui ?></div>
        <div class="stat-lbl">Disetujui</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon red">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      </div>
      <div>
        <div class="stat-val" style="color:#c0392b;"><?= $cnt_ditolak ?></div>
        <div class="stat-lbl">Ditolak</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon gold">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
      </div>
      <div>
        <div class="stat-val"><?= $cnt_total ?></div>
        <div class="stat-lbl">Total Semua Pengajuan</div>
      </div>
    </div>
  </div>

  <!-- Filter & Search Bar -->
  <div class="filter-bar">
    <div class="tabs-wrap">
      <a href="pengajuan_buku.php?tab=menunggu<?= $search ? '&q=' . urlencode($search) : '' ?>" class="tab-btn <?= $tab === 'menunggu' ? 'active' : '' ?>">
        Menunggu
        <span class="tab-count"><?= $cnt_menunggu ?></span>
      </a>
      <a href="pengajuan_buku.php?tab=disetujui<?= $search ? '&q=' . urlencode($search) : '' ?>" class="tab-btn <?= $tab === 'disetujui' ? 'active' : '' ?>">
        Disetujui
        <span class="tab-count"><?= $cnt_disetujui ?></span>
      </a>
      <a href="pengajuan_buku.php?tab=ditolak<?= $search ? '&q=' . urlencode($search) : '' ?>" class="tab-btn <?= $tab === 'ditolak' ? 'active' : '' ?>">
        Ditolak
        <span class="tab-count"><?= $cnt_ditolak ?></span>
      </a>
      <a href="pengajuan_buku.php?tab=semua<?= $search ? '&q=' . urlencode($search) : '' ?>" class="tab-btn <?= $tab === 'semua' ? 'active' : '' ?>">
        Semua
        <span class="tab-count"><?= $cnt_total ?></span>
      </a>
    </div>

    <form method="GET" action="pengajuan_buku.php" style="display:flex;gap:8px;">
      <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="q" placeholder="Cari nama, buku, kelas…" value="<?= htmlspecialchars($search) ?>">
      </div>
      <?php if ($search): ?>
        <a href="pengajuan_buku.php?tab=<?= $tab ?>" style="display:flex;align-items:center;padding:0 10px;color:var(--muted);text-decoration:none;font-size:.8rem;">✕</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Data Table -->
  <div class="table-card">
    <table>
      <thead>
        <tr>
          <th>Tanggal & Peminjam</th>
          <th>Kartu Anggota</th>
          <th>Buku yang Diajukan</th>
          <th>Jumlah & Jadwal Ambil</th>
          <th>Batas Kembali</th>
          <th>Status</th>
          <th style="text-align:center;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pengajuan_list)): ?>
          <tr>
            <td colspan="7" style="text-align:center;padding:40px 16px;color:var(--muted);">
              Tidak ada data pengajuan peminjaman pada kategori ini.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($pengajuan_list as $row):
            $tgl_input = date('d M Y, H:i', strtotime($row['created_at']));
            $batas_k   = date('d M Y', strtotime($row['batas_kembali']));
            $status    = $row['status'];
            $kartu_url = $row['file_kartu'] ?? '';
            $kartu_ada = !empty($kartu_url) && file_exists(__DIR__ . '/' . $kartu_url);
          ?>
            <tr>
              <!-- Tanggal & Peminjam -->
              <td>
                <div class="borrower-wrap">
                  <?php if (!empty($row['user_foto']) && file_exists(__DIR__ . '/' . $row['user_foto'])): ?>
                    <img src="<?= htmlspecialchars($row['user_foto']) ?>" class="borrower-avatar" alt="Avatar">
                  <?php else: ?>
                    <div class="borrower-avatar" style="background:var(--book-card);display:flex;align-items:center;justify-content:center;color:var(--accent);">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
                    </div>
                  <?php endif; ?>
                  <div>
                    <div class="borrower-name"><?= htmlspecialchars($row['nama_peminjam']) ?></div>
                    <div class="borrower-sub">
                      <?= htmlspecialchars($row['kelas'] ?: 'Umum') ?> · No: <?= htmlspecialchars($row['no_anggota'] ?: '—') ?>
                    </div>
                    <div style="font-size:.68rem;color:var(--muted);margin-top:2px;"><?= $tgl_input ?></div>
                  </div>
                </div>
              </td>

              <!-- Kartu Anggota (Trigger Modal Preview) -->
              <td>
                <?php if ($kartu_ada): ?>
                  <button type="button" class="card-thumb-btn" onclick="previewKartu('<?= htmlspecialchars($kartu_url) ?>', '<?= htmlspecialchars($row['nama_peminjam'], ENT_QUOTES) ?>')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="7" y1="8" x2="17" y2="8"/><line x1="7" y1="12" x2="13" y2="12"/></svg>
                    Lihat Kartu
                  </button>
                <?php else: ?>
                  <span style="font-size:.72rem;color:var(--muted);font-style:italic;">Kartu tidak ditemukan</span>
                <?php endif; ?>
              </td>

              <!-- Buku -->
              <td>
                <div class="book-cell">
                  <?php if (!empty($row['buku_gambar']) && file_exists(__DIR__ . '/' . $row['buku_gambar'])): ?>
                    <img src="<?= htmlspecialchars($row['buku_gambar']) ?>" class="book-thumb" alt="Cover">
                  <?php else: ?>
                    <div class="book-thumb-empty">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    </div>
                  <?php endif; ?>
                  <div>
                    <div class="book-title-cell"><?= htmlspecialchars($row['judul'] ?? 'Buku Perpustakaan') ?></div>
                    <div class="book-author-cell">✍️ <?= htmlspecialchars($row['penulis'] ?: '—') ?></div>
                    <?php if (!empty($row['buku_rak'])): ?>
                      <div style="font-size:.68rem;color:var(--accent,#d8b878);margin-top:2px;font-weight:600;">
                        📍 <?= htmlspecialchars($row['buku_rak']) ?>
                      </div>
                    <?php endif; ?>
                    <div style="font-size:.68rem;color:<?= (int)($row['buku_stok'] ?? 0) > 0 ? '#1a8a4a' : '#c0392b' ?>;margin-top:2px;">
                      Sisa stok katalog: <strong><?= (int)($row['buku_stok'] ?? 0) ?></strong>
                    </div>
                  </div>
                </div>
              </td>

              <!-- Jumlah & Waktu Ambil -->
              <td>
                <div style="font-weight:700;font-size:.82rem;color:var(--text);">
                  <?= (int)$row['total_buku'] ?> Eksemplar
                </div>
                <div>
                  <?php if ($row['waktu_pengambilan'] === 'sekarang'): ?>
                    <span class="badge-pickup sekarang">⚡ Ambil Sekarang</span>
                  <?php else: ?>
                    <span class="badge-pickup nanti">🕒 Ambil Nanti</span>
                    <?php if (!empty($row['catatan_pengambilan'])): ?>
                      <div style="font-size:.68rem;color:var(--muted);margin-top:3px;max-width:180px;">
                        "<?= htmlspecialchars($row['catatan_pengambilan']) ?>"
                      </div>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </td>

              <!-- Batas Pengembalian -->
              <td>
                <div style="font-weight:700;font-size:.8rem;color:var(--text);"><?= $batas_k ?></div>
                <div style="font-size:.68rem;color:var(--muted);">Maks. 23:59 WIB</div>
              </td>

              <!-- Status -->
              <td>
                <?php if ($status === 'menunggu'): ?>
                  <span class="badge badge-menunggu">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="10"/></svg>
                    Menunggu
                  </span>
                <?php elseif ($status === 'disetujui'): ?>
                  <span class="badge badge-disetujui">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    Disetujui
                  </span>
                <?php else: ?>
                  <span class="badge badge-ditolak" title="<?= htmlspecialchars($row['alasan_penolakan'] ?? '') ?>">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Ditolak
                  </span>
                  <?php if (!empty($row['alasan_penolakan'])): ?>
                    <div style="font-size:.68rem;color:#c0392b;margin-top:2px;max-width:140px;word-break:break-word;">
                      "<?= htmlspecialchars($row['alasan_penolakan']) ?>"
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              </td>

              <!-- Aksi -->
              <td style="text-align:center;">
                <?php if ($status === 'menunggu'): ?>
                  <div class="action-group" style="justify-content:center;">
                    <!-- Setujui Form -->
                    <form method="POST" action="pengajuan_buku.php?tab=<?= $tab ?>" onsubmit="return confirm('Setujui pengajuan buku oleh <?= htmlspecialchars($row['nama_peminjam'], ENT_QUOTES) ?>? Stok buku akan otomatis terpotong dan masuk ke Telah Dipinjam.');">
                      <input type="hidden" name="action" value="setujui">
                      <input type="hidden" name="pengajuan_id" value="<?= (int)$row['id'] ?>">
                      <button type="submit" class="btn-action-approve" title="Setujui Pengajuan">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        Setujui
                      </button>
                    </form>

                    <!-- Tolak Button -->
                    <button type="button" class="btn-action-reject" onclick="bukaModalTolak(<?= (int)$row['id'] ?>, '<?= htmlspecialchars($row['nama_peminjam'], ENT_QUOTES) ?>')">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                      Tolak
                    </button>
                  </div>
                <?php elseif ($status === 'disetujui'): ?>
                  <span style="font-size:.72rem;color:#1a8a4a;font-weight:600;">✓ Masuk Peminjaman</span>
                <?php else: ?>
                  <span style="font-size:.72rem;color:var(--muted);">Ditolak</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</main>

<!-- Modal Preview Kartu Anggota -->
<div class="modal-overlay" id="modalKartuPreview" onclick="if(event.target === this) tutupModalKartu()">
  <div class="modal-box" style="max-width:560px;">
    <div class="modal-head">
      <div class="modal-head-title" id="previewKartuTitle">Kartu Anggota</div>
      <button type="button" class="modal-close" onclick="tutupModalKartu()">✕</button>
    </div>
    <div class="modal-body" style="text-align:center;padding:16px;">
      <img src="" id="imgKartuPreview" style="max-width:100%;max-height:65vh;border-radius:10px;border:1px solid var(--border-color);box-shadow:0 8px 30px rgba(0,0,0,.6);" alt="Kartu Anggota">
    </div>
    <div class="modal-foot">
      <a href="" id="btnDownloadKartu" download class="card-thumb-btn" style="padding:8px 16px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Unduh Berkas Kartu
      </a>
      <button type="button" class="card-thumb-btn" onclick="tutupModalKartu()" style="background:rgba(255,255,255,.05);color:var(--text);border-color:var(--card-border);">Tutup</button>
    </div>
  </div>
</div>

<!-- Modal Alasan Penolakan -->
<div class="modal-overlay" id="modalTolakPengajuan" onclick="if(event.target === this) tutupModalTolak()">
  <div class="modal-box">
    <form method="POST" action="pengajuan_buku.php?tab=<?= $tab ?>">
      <input type="hidden" name="action" value="tolak">
      <input type="hidden" name="pengajuan_id" id="inputTolakId" value="0">

      <div class="modal-head">
        <div class="modal-head-title">Tolak Pengajuan Peminjaman</div>
        <button type="button" class="modal-close" onclick="tutupModalTolak()">✕</button>
      </div>
      <div class="modal-body">
        <p style="font-size:.82rem;color:var(--muted);line-height:1.5;">
          Berikan alasan penolakan untuk pengajuan atas nama <strong id="namaPeminjamTolak" style="color:var(--text);"></strong>. Alasan ini akan tampil di riwayat dashboard peminjam.
        </p>
        <textarea name="alasan_penolakan" class="textarea-reject" placeholder="Contoh: Foto kartu anggota tidak jelas/buram, atau Buku fisik sedang dalam perbaikan..." required></textarea>
      </div>
      <div class="modal-foot">
        <button type="button" class="card-thumb-btn" onclick="tutupModalTolak()" style="background:rgba(255,255,255,.05);color:var(--text);border-color:var(--card-border);">Batal</button>
        <button type="submit" class="btn-action-reject" style="padding:8px 16px;">Konfirmasi Tolak</button>
      </div>
    </form>
  </div>
</div>

<script>
  function previewKartu(url, nama) {
    document.getElementById('imgKartuPreview').src = url;
    document.getElementById('btnDownloadKartu').href = url;
    document.getElementById('previewKartuTitle').textContent = 'Kartu Anggota – ' + nama;
    document.getElementById('modalKartuPreview').classList.add('open');
  }

  function tutupModalKartu() {
    document.getElementById('modalKartuPreview').classList.remove('open');
  }

  function bukaModalTolak(id, nama) {
    document.getElementById('inputTolakId').value = id;
    document.getElementById('namaPeminjamTolak').textContent = nama;
    document.getElementById('modalTolakPengajuan').classList.add('open');
  }

  function tutupModalTolak() {
    document.getElementById('modalTolakPengajuan').classList.remove('open');
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      tutupModalKartu();
      tutupModalTolak();
    }
  });

  // Sidebar mobile toggle dikelola terpusat oleh settings_include.php
</script>

<?php require_once 'modal_profil_admin.php';
require_once 'pengaturan_panel.php'; ?>
</body>
</html>