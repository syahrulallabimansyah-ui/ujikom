<?php
// dashboard.php — Dashboard admin: ringkasan & rekap bulanan peminjaman
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: sign_in.php");
    exit;
}

require_once "db.php";

$page_title = "Dashboard – AKSA NOVA";

// ─── Profil admin dari DB ───
$profil_res  = mysqli_query($conn, "SELECT * FROM admin_profile LIMIT 1");
$profil      = $profil_res ? mysqli_fetch_assoc($profil_res) : null;
$admin_name  = $profil["display_name"] ?? ($_SESSION["user_name"] ?? "Admin");
$admin_foto  = $profil["foto"] ?? "";

// ─────────────────────────────────────────────
//  HELPER: deteksi struktur tabel secara aman
//  (nama kolom tabel "peminjaman" bisa berbeda-beda,
//   jadi dashboard menyesuaikan otomatis alih-alih menebak)
// ─────────────────────────────────────────────
function tabelAda($conn, $table) {
    $t = mysqli_real_escape_string($conn, $table);
    $r = mysqli_query($conn, "SHOW TABLES LIKE '$t'");
    return $r && mysqli_num_rows($r) > 0;
}

function kolomTersedia($conn, $table, $kandidat) {
    static $cache = [];
    if (!isset($cache[$table])) {
        $cols = [];
        $t = mysqli_real_escape_string($conn, $table);
        $r = mysqli_query($conn, "SHOW COLUMNS FROM `$t`");
        if ($r) { while ($row = mysqli_fetch_assoc($r)) $cols[] = $row["Field"]; }
        $cache[$table] = $cols;
    }
    foreach ($kandidat as $c) {
        if (in_array($c, $cache[$table], true)) return $c;
    }
    return null;
}

$ada_peminjaman = tabelAda($conn, "peminjaman");

$col_user         = $ada_peminjaman ? kolomTersedia($conn, "peminjaman", ["user_id","anggota_id","id_user","id_anggota","member_id"]) : null;
$col_buku         = $ada_peminjaman ? kolomTersedia($conn, "peminjaman", ["buku_id","id_buku"]) : null;
$col_pinjam       = $ada_peminjaman ? kolomTersedia($conn, "peminjaman", ["waktu_pinjam","tgl_pinjam","tanggal_pinjam","created_at","tgl_peminjaman"]) : null;
$col_jatuh_tempo  = $ada_peminjaman ? kolomTersedia($conn, "peminjaman", ["batas_kembali","tgl_kembali","tanggal_kembali","due_date"]) : null;
$col_dikembalikan = $ada_peminjaman ? kolomTersedia($conn, "peminjaman", ["waktu_kembali","tgl_dikembalikan","tanggal_dikembalikan","returned_at","tgl_pengembalian"]) : null;
$col_status       = $ada_peminjaman ? kolomTersedia($conn, "peminjaman", ["status"]) : null;

// Skema minimal yang dibutuhkan supaya rekap peminjaman bisa dihitung
$skema_lengkap = $ada_peminjaman && $col_user && $col_buku && $col_pinjam;

// ─────────────────────────────────────────────
//  KARTU RINGKASAN
// ─────────────────────────────────────────────
$buku_res   = mysqli_query($conn, "SELECT COUNT(*) AS c, COALESCE(SUM(stok),0) AS s FROM buku");
$buku_row   = $buku_res ? mysqli_fetch_assoc($buku_res) : ["c" => 0, "s" => 0];
$total_buku = (int)$buku_row["c"];
$total_stok = (int)$buku_row["s"];

$anggota_res    = mysqli_query($conn, "SELECT status, COUNT(*) AS c FROM users WHERE role='member' GROUP BY status");
$anggota_counts = ["pending" => 0, "approved" => 0, "rejected" => 0];
if ($anggota_res) {
    while ($row = mysqli_fetch_assoc($anggota_res)) {
        $anggota_counts[$row["status"]] = (int)$row["c"];
    }
}
$total_anggota_aktif = $anggota_counts["approved"];

$sedang_dipinjam          = null; // null = tidak bisa dihitung dari skema yang ada
$terlambat                = null;
$peminjaman_bulan_ini     = 0;
$anggota_pinjam_bulan_ini = 0;
$buku_terpopuler          = [];

if ($skema_lengkap) {
    // Sedang dipinjam
    if ($col_status) {
        $q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM peminjaman WHERE LOWER($col_status) NOT IN ('dikembalikan','selesai','returned','kembali','ditolak','rejected')");
        $sedang_dipinjam = (int)(mysqli_fetch_assoc($q)['c'] ?? 0);
    } elseif ($col_dikembalikan) {
        $q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM peminjaman WHERE $col_dikembalikan IS NULL");
        $sedang_dipinjam = (int)(mysqli_fetch_assoc($q)['c'] ?? 0);
    }

    // Terlambat kembali (pinjaman aktif yang sudah melewati batas waktu kembali)
    if ($col_status && $col_jatuh_tempo) {
        $q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM peminjaman WHERE LOWER($col_status) NOT IN ('dikembalikan','selesai','returned','kembali','ditolak','rejected') AND $col_jatuh_tempo < NOW()");
        $terlambat = (int)(mysqli_fetch_assoc($q)['c'] ?? 0);
    } elseif ($col_jatuh_tempo && $col_dikembalikan) {
        $q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM peminjaman WHERE $col_dikembalikan IS NULL AND $col_jatuh_tempo < NOW()");
        $terlambat = (int)(mysqli_fetch_assoc($q)['c'] ?? 0);
    } elseif ($col_status) {
        $q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM peminjaman WHERE LOWER($col_status) = 'terlambat'");
        $terlambat = (int)(mysqli_fetch_assoc($q)['c'] ?? 0);
    }

    // Aktivitas bulan berjalan
    $q = mysqli_query($conn, "SELECT COUNT(*) AS c, COUNT(DISTINCT $col_user) AS a
                               FROM peminjaman
                               WHERE YEAR($col_pinjam) = YEAR(CURDATE()) AND MONTH($col_pinjam) = MONTH(CURDATE())");
    $row = $q ? mysqli_fetch_assoc($q) : null;
    $peminjaman_bulan_ini     = (int)($row['c'] ?? 0);
    $anggota_pinjam_bulan_ini = (int)($row['a'] ?? 0);

    // Buku paling banyak dipinjam (top 5)
    $q = mysqli_query($conn, "SELECT b.judul, COUNT(*) AS jml
                               FROM peminjaman p
                               JOIN buku b ON b.id = p.$col_buku
                               GROUP BY p.$col_buku, b.judul
                               ORDER BY jml DESC
                               LIMIT 5");
    if ($q) { while ($row = mysqli_fetch_assoc($q)) $buku_terpopuler[] = $row; }
}

// ─────────────────────────────────────────────
//  RINGKASAN DENDA (untuk bagian Laporan)
// ─────────────────────────────────────────────
$denda_tersedia    = $ada_peminjaman
    && kolomTersedia($conn, "peminjaman", ["status_denda"])
    && kolomTersedia($conn, "peminjaman", ["denda"]);
$denda_belum_count = 0;
$denda_lunas_count = 0;
$denda_belum_rp    = 0;
$denda_lunas_rp    = 0;
if ($denda_tersedia) {
    $q = mysqli_query($conn, "SELECT status_denda, COUNT(*) AS c, COALESCE(SUM(denda),0) AS t
                               FROM peminjaman WHERE denda > 0 GROUP BY status_denda");
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            if ($row["status_denda"] === "lunas") {
                $denda_lunas_count = (int)$row["c"];
                $denda_lunas_rp    = (int)$row["t"];
            } else {
                $denda_belum_count += (int)$row["c"];
                $denda_belum_rp    += (int)$row["t"];
            }
        }
    }
}

// ─────────────────────────────────────────────
//  REKAP BULANAN — per tahun kalender (Jan–Des)
//  Tahun bisa dinavigasi bebas maju/mundur lewat ?tahun=,
//  jadi rekap TIDAK pernah mentok di satu tahun tertentu —
//  setiap tahun baru berjalan otomatis tersedia untuk dipilih.
// ─────────────────────────────────────────────
$tahun_sekarang = (int)date('Y');
$tahun = isset($_GET["tahun"]) ? (int)$_GET["tahun"] : $tahun_sekarang;
if ($tahun < 2000 || $tahun > 2100) $tahun = $tahun_sekarang; // guard nilai aneh dari URL

$nama_bulan = [
    1 => "Januari", 2 => "Februari", 3 => "Maret",     4 => "April",
    5 => "Mei",     6 => "Juni",     7 => "Juli",      8 => "Agustus",
    9 => "September",10 => "Oktober",11 => "November", 12 => "Desember",
];

$rekap_per_bulan = []; // bulan_num => data
if ($skema_lengkap) {
    $sql = "SELECT MONTH($col_pinjam) AS bulan_num,
                   COUNT(*) AS total_pinjam,
                   COUNT(DISTINCT $col_user) AS anggota_pinjam";
    if ($col_dikembalikan) {
        $sql .= ", SUM(CASE WHEN $col_dikembalikan IS NOT NULL THEN 1 ELSE 0 END) AS total_kembali";
    }
    $sql .= " FROM peminjaman
              WHERE YEAR($col_pinjam) = $tahun
              GROUP BY bulan_num";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $rekap_per_bulan[(int)$row["bulan_num"]] = $row;
        }
    }
}

$rekap_tampil        = [];
$total_tahun_pinjam  = 0;
$total_tahun_kembali = 0;
for ($m = 1; $m <= 12; $m++) {
    $data = $rekap_per_bulan[$m] ?? null;
    $tp   = (int)($data["total_pinjam"] ?? 0);
    $ap   = (int)($data["anggota_pinjam"] ?? 0);
    $tk   = $col_dikembalikan ? (int)($data["total_kembali"] ?? 0) : null;

    $total_tahun_pinjam += $tp;
    if ($tk !== null) $total_tahun_kembali += $tk;

    $rekap_tampil[] = [
        "label"          => $nama_bulan[$m] . " " . $tahun,
        "is_sekarang"    => ($tahun === $tahun_sekarang && $m === (int)date('n')),
        "total_pinjam"   => $tp,
        "anggota_pinjam" => $ap,
        "total_kembali"  => $tk,
    ];
}

// Anggota unik yang meminjam sepanjang tahun terpilih (bukan sekadar jumlah kolom bulanan,
// karena anggota yang sama bisa meminjam di beberapa bulan berbeda)
$anggota_unik_tahun = 0;
if ($skema_lengkap) {
    $q = mysqli_query($conn, "SELECT COUNT(DISTINCT $col_user) AS c FROM peminjaman WHERE YEAR($col_pinjam) = $tahun");
    $anggota_unik_tahun = (int)(mysqli_fetch_assoc($q)['c'] ?? 0);
}

// Warna badge nav (dipakai juga di sidebar)
$pending_count = $anggota_counts["pending"];

// ─────────────────────────────────────────────
//  TEKS LAPORAN UNTUK DIKIRIM VIA WHATSAPP
// ─────────────────────────────────────────────
$wa_lines   = [];
$wa_lines[] = "*Laporan Perpustakaan – AKSA NOVA*";
$wa_lines[] = "Tanggal: " . date('d F Y, H:i');
$wa_lines[] = "";
$wa_lines[] = "*Koleksi Buku*";
$wa_lines[] = "- Total Judul Buku: $total_buku";
$wa_lines[] = "- Total Stok Fisik: $total_stok";
$wa_lines[] = "- Sedang Dipinjam: " . ($sedang_dipinjam !== null ? $sedang_dipinjam : '-');
$wa_lines[] = "";
$wa_lines[] = "*Keanggotaan*";
$wa_lines[] = "- Anggota Aktif: {$anggota_counts['approved']}";
$wa_lines[] = "- Menunggu Persetujuan: {$anggota_counts['pending']}";
$wa_lines[] = "- Ditolak: {$anggota_counts['rejected']}";
$wa_lines[] = "";
$wa_lines[] = "*Aktivitas Peminjaman*";
$wa_lines[] = "- Peminjaman Bulan Ini: $peminjaman_bulan_ini";
$wa_lines[] = "- Total Tahun $tahun: $total_tahun_pinjam";
$wa_lines[] = "- Terlambat Kembali: " . ($terlambat !== null ? $terlambat : '-');
if ($denda_tersedia) {
    $wa_lines[] = "";
    $wa_lines[] = "*Denda*";
    $wa_lines[] = "- Belum Dibayar ($denda_belum_count): Rp " . number_format($denda_belum_rp, 0, ',', '.');
    $wa_lines[] = "- Lunas ($denda_lunas_count): Rp " . number_format($denda_lunas_rp, 0, ',', '.');
}
$laporan_text = implode("\n", $wa_lines);
$wa_link      = "https://wa.me/?text=" . urlencode($laporan_text);
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

    /* ── SIDEBAR (identik dengan halaman lain) ── */
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

    .content-header {
      display:flex; align-items:center; justify-content:space-between;
      margin-bottom:18px; animation:fadeUp .5s .08s both; flex-wrap:wrap; gap:10px;
    }
    .content-title { font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:700; color:var(--text); }
    .content-sub   { font-size:.8rem; color:var(--muted); margin-top:2px; }

    .notice {
      background:#fff3cd; color:#8a6100; border:1px solid #ffe8a1;
      padding:10px 16px; border-radius:8px; font-size:.78rem; font-weight:700;
      margin-bottom:18px; animation:fadeUp .4s both;
    }

    /* Stat cards */
    .stat-grid {
      display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
      gap:14px; margin-bottom:26px;
      animation:fadeUp .5s .1s both;
    }
    .stat-card {
      background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow);
      padding:18px 20px; display:flex; flex-direction:column; gap:6px;
      border-left:4px solid var(--btn-primary);
    }
    .stat-card .stat-icon { font-size:1.3rem; }
    .stat-card .stat-value { font-size:1.7rem; font-weight:800; color:var(--text); font-family:'Cormorant Garamond',serif; }
    .stat-card .stat-label { font-size:.74rem; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
    .stat-card.c-anggota  { border-left-color:#2b7de9; }
    .stat-card.c-buku     { border-left-color:#8a5cf6; }
    .stat-card.c-dipinjam { border-left-color:#1a8a4a; }
    .stat-card.c-telat    { border-left-color:#e74c3c; }
    .stat-card.c-pending  { border-left-color:#e67e22; }

    /* Section */
    .section { margin-bottom:28px; animation:fadeUp .5s .16s both; }
    .section-head {
      display:flex; align-items:center; justify-content:space-between;
      margin-bottom:12px; flex-wrap:wrap; gap:8px;
    }
    .section-title { font-family:'Cormorant Garamond',serif; font-size:1.25rem; font-weight:700; color:var(--text); }
    .section-sub { font-size:.76rem; color:var(--muted); }

    .two-col { display:grid; grid-template-columns:2fr 1fr; gap:18px; align-items:start; }

    /* Table */
    .table-wrap {
      background:var(--card); border-radius:var(--radius);
      box-shadow:var(--shadow); overflow-x:auto;
    }
    table { width:100%; border-collapse:collapse; min-width:520px; }
    thead th {
      text-align:left; font-size:.68rem; text-transform:uppercase; letter-spacing:.06em;
      color:var(--muted); font-weight:800; padding:12px 16px;
      border-bottom:1.5px solid #eee; white-space:nowrap;
    }
    tbody td {
      padding:11px 16px; font-size:.82rem; color:var(--text);
      border-bottom:1px solid #f2f2f6; vertical-align:middle;
    }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover { background:#fafafe; }
    tbody tr.row-now td { background:#f0f4ff; font-weight:700; }
    .cell-num { font-family:'JetBrains Mono', monospace; }
    .cell-dash { color:#c8c8d4; }

    /* Buku terpopuler */
    .pop-list {
      background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow);
      padding:6px 0;
    }
    .pop-item {
      display:flex; align-items:center; gap:10px;
      padding:10px 18px; border-bottom:1px solid #f2f2f6;
    }
    .pop-item:last-child { border-bottom:none; }
    .pop-rank {
      width:24px; height:24px; border-radius:50%; background:var(--btn-primary); color:#fff;
      font-size:.72rem; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0;
    }
    .pop-title { flex:1; font-size:.82rem; font-weight:700; color:var(--text); }
    .pop-count { font-size:.74rem; color:var(--muted); font-weight:700; white-space:nowrap; }

    .empty-mini { padding:24px 18px; text-align:center; color:var(--muted); font-size:.82rem; }

    /* Laporan ringkasan */
    .laporan-grid {
      display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
      gap:14px;
    }
    .laporan-card {
      background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow);
      padding:16px 18px;
    }
    .laporan-card h4 {
      font-size:.7rem; text-transform:uppercase; letter-spacing:.05em;
      color:var(--muted); font-weight:800; margin-bottom:10px;
    }
    .laporan-row {
      display:flex; align-items:center; justify-content:space-between;
      padding:6px 0; font-size:.82rem; color:var(--text);
      border-bottom:1px solid #f2f2f6;
    }
    .laporan-row:last-child { border-bottom:none; }
    .laporan-row span:last-child { font-weight:800; font-family:'JetBrains Mono', monospace; }
    .laporan-row.highlight span:last-child { color:#dc2626; }
    .laporan-row.ok span:last-child { color:#1a8a4a; }

    /* Navigasi tahun + cetak */
    .year-nav {
      display:flex; align-items:center; gap:8px; flex-wrap:wrap;
    }
    .year-nav .nav-btn {
      width:30px; height:30px; border-radius:8px; border:none;
      background:#fff; box-shadow:var(--shadow); color:var(--text);
      display:flex; align-items:center; justify-content:center;
      text-decoration:none; font-weight:800; font-size:.9rem;
      transition:background var(--trans);
    }
    .year-nav .nav-btn:hover { background:#ececf4; }
    .year-nav .year-label {
      font-family:'Cormorant Garamond',serif; font-size:1.1rem; font-weight:700;
      color:var(--text); min-width:52px; text-align:center;
    }
    .year-nav .year-reset {
      font-size:.72rem; font-weight:700; color:var(--muted);
      text-decoration:none; padding:4px 10px; border-radius:20px;
      background:#fff; box-shadow:var(--shadow);
    }
    .year-nav .year-reset:hover { color:var(--text); }
    .btn-print {
      display:flex; align-items:center; gap:6px;
      background:var(--btn-primary); color:#fff; border:none;
      padding:7px 16px; border-radius:20px; cursor:pointer;
      font-family:'Nunito',sans-serif; font-size:.76rem; font-weight:800;
      transition:background var(--trans);
    }
    .btn-print:hover { background:#222; }
    .btn-whatsapp {
      display:flex; align-items:center; gap:6px;
      background:#25D366; color:#fff; border:none;
      padding:7px 16px; border-radius:20px; cursor:pointer;
      font-family:'Nunito',sans-serif; font-size:.76rem; font-weight:800;
      text-decoration:none; transition:background var(--trans);
    }
    .btn-whatsapp:hover { background:#1ebc59; }

    tfoot td {
      padding:12px 16px; font-size:.8rem; font-weight:800; color:var(--text);
      border-top:1.5px solid #eee; background:#fafafe;
    }

    .print-header { display:none; }

    @keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:translateY(0); } }

    @media (max-width:960px) {
      .two-col { grid-template-columns:1fr; }
    }
    @media (max-width:860px) {
      :root { --sidebar-w:170px; }
      .avatar-circle { width:76px; height:76px; }
    }
    @media (max-width:620px) {
      .sidebar { transform:translateX(-100%); width:220px; }
      .sidebar.open { transform:translateX(0); }
      .sidebar-toggle { display:flex; }
      .main { margin-left:0; padding:70px 14px 24px; }
      .year-nav { width:100%; }
      .stat-grid { grid-template-columns:1fr 1fr; }

      /* Tabel rekap jadi kartu bertumpuk supaya tidak perlu geser ke samping */
      .table-wrap { overflow-x:visible; box-shadow:none; background:transparent; }
      table { min-width:0; width:100%; border-collapse:separate; border-spacing:0 16px; }
      thead { display:none; }
      tbody tr {
        display:block; background:var(--card); border-radius:var(--radius);
        box-shadow:var(--shadow); overflow:hidden;
      }
      tbody tr:hover { background:var(--card); }
      tbody tr.row-now { background:var(--card); }
      tbody tr.row-now td { background:#f0f4ff; }
      tbody td {
        display:flex; align-items:center; justify-content:space-between; gap:12px;
        padding:11px 14px; border-bottom:1px solid #f2f2f6; text-align:right;
      }
      tbody tr td:last-child { border-bottom:none; }
      tbody td::before {
        content:attr(data-label); font-size:.68rem; font-weight:800; color:var(--muted);
        text-transform:uppercase; letter-spacing:.05em; text-align:left; flex-shrink:0;
      }
      tfoot { display:block; }
      tfoot tr {
        display:block; background:#fafafe; border-radius:var(--radius);
        box-shadow:var(--shadow); margin-top:16px; overflow:hidden;
      }
      tfoot td {
        display:flex; align-items:center; justify-content:space-between; gap:12px;
        padding:11px 14px; border-top:none; border-bottom:1px solid #eee; background:transparent;
      }
      tfoot td:last-child { border-bottom:none; }
      tfoot td::before {
        content:attr(data-label); font-size:.68rem; font-weight:800; color:var(--muted);
        text-transform:uppercase; letter-spacing:.05em;
      }
    }
    @media (max-width:400px) {
      .stat-grid { grid-template-columns:1fr; }
    }

    /* ── MODE CETAK ── */
    @media print {
      .sidebar, .sidebar-toggle, .sidebar-overlay,
      .btn-print, .btn-whatsapp, .year-nav .nav-btn, .year-nav .year-reset,
      .content-sub, .notice { display:none !important; }
      body { background:#fff; animation:none; }
      .main { margin-left:0; padding:0; }
      .print-header { display:block; margin-bottom:20px; }
      .print-header h1 { font-family:'Cormorant Garamond',serif; font-size:1.5rem; color:#000; }
      .print-header p { font-size:.8rem; color:#444; margin-top:2px; }
      .stat-card, .table-wrap, .pop-list, .laporan-card { box-shadow:none; border:1px solid #ddd; }
      .two-col { grid-template-columns:1fr; }
      .section { break-inside:avoid; }
      tbody tr:hover { background:transparent; }
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

<!-- Header ini hanya tampil saat dicetak -->
<div class="print-header">
  <h1>Laporan Perpustakaan — AKSA NOVA</h1>
  <p>Tahun: <?= $tahun ?> &nbsp;·&nbsp; Dicetak oleh: <?= htmlspecialchars($admin_name) ?> &nbsp;·&nbsp; Tanggal cetak: <?= date('d F Y, H:i') ?></p>
</div>

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

  <a class="sidebar-btn active" href="dashboard.php">
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
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
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

<main class="main">

  <div class="content-header">
    <div>
      <div class="content-title">Dashboard</div>
      <div class="content-sub">Ringkasan aktivitas perpustakaan &amp; rekap peminjaman bulanan</div>
    </div>
  </div>

  <?php if (!$skema_lengkap): ?>
  <div class="notice">
    ⚠️ Sebagian data peminjaman tidak dapat ditampilkan karena tabel <code>peminjaman</code> belum terdeteksi lengkap (kolom anggota/buku/tanggal pinjam). Statistik anggota &amp; buku tetap ditampilkan seperti biasa.
  </div>
  <?php endif; ?>

  <!-- Kartu ringkasan -->
  <div class="stat-grid">
    <div class="stat-card c-anggota">
      <span class="stat-icon">👥</span>
      <span class="stat-value"><?= $total_anggota_aktif ?></span>
      <span class="stat-label">Anggota Aktif</span>
    </div>
    <div class="stat-card c-buku">
      <span class="stat-icon">📚</span>
      <span class="stat-value"><?= $total_buku ?></span>
      <span class="stat-label">Judul Buku (<?= $total_stok ?> stok)</span>
    </div>
    <div class="stat-card c-dipinjam">
      <span class="stat-icon">📖</span>
      <span class="stat-value"><?= $sedang_dipinjam !== null ? $sedang_dipinjam : "–" ?></span>
      <span class="stat-label">Sedang Dipinjam</span>
    </div>
    <div class="stat-card c-telat">
      <span class="stat-icon">⏰</span>
      <span class="stat-value"><?= $terlambat !== null ? $terlambat : "–" ?></span>
      <span class="stat-label">Terlambat Kembali</span>
    </div>
    <div class="stat-card c-pending">
      <span class="stat-icon">🕓</span>
      <span class="stat-value"><?= $pending_count ?></span>
      <span class="stat-label">Pendaftar Menunggu</span>
    </div>
  </div>

  <div class="two-col">
    <!-- Rekap bulanan -->
    <div class="section">
      <div class="section-head">
        <div>
          <div class="section-title">Rekap Bulanan Peminjaman</div>
          <div class="section-sub">Bulan berjalan: <?= $peminjaman_bulan_ini ?> peminjaman oleh <?= $anggota_pinjam_bulan_ini ?> anggota</div>
        </div>
        <div class="year-nav">
          <a class="nav-btn" href="?tahun=<?= $tahun - 1 ?>" title="Tahun sebelumnya">◀</a>
          <span class="year-label"><?= $tahun ?></span>
          <a class="nav-btn" href="?tahun=<?= $tahun + 1 ?>" title="Tahun berikutnya">▶</a>
          <?php if ($tahun !== $tahun_sekarang): ?>
            <a class="year-reset" href="?tahun=<?= $tahun_sekarang ?>">Tahun ini</a>
          <?php endif; ?>
          <button class="btn-print" onclick="window.print()">
            🖨️ Cetak Laporan
          </button>
        </div>
      </div>

      <?php if (!$skema_lengkap): ?>
        <div class="table-wrap"><div class="empty-mini">Rekap belum tersedia — struktur tabel peminjaman belum terdeteksi.</div></div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Bulan</th>
              <th>Total Peminjaman</th>
              <th>Anggota Meminjam</th>
              <th>Buku Dikembalikan</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rekap_tampil as $r): ?>
            <tr class="<?= $r["is_sekarang"] ? 'row-now' : '' ?>">
              <td data-label="Bulan"><?= htmlspecialchars($r["label"]) ?><?= $r["is_sekarang"] ? ' <span style="color:var(--muted);font-weight:400;">(bulan ini)</span>' : '' ?></td>
              <td class="cell-num" data-label="Total Peminjaman"><?= $r["total_pinjam"] ?></td>
              <td class="cell-num" data-label="Anggota Meminjam"><?= $r["anggota_pinjam"] ?></td>
              <td class="cell-num" data-label="Buku Dikembalikan"><?= $r["total_kembali"] !== null ? $r["total_kembali"] : '<span class="cell-dash">–</span>' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td data-label="Bulan">Total Tahun <?= $tahun ?></td>
              <td class="cell-num" data-label="Total Peminjaman"><?= $total_tahun_pinjam ?></td>
              <td class="cell-num" data-label="Anggota Meminjam"><?= $anggota_unik_tahun ?> anggota unik</td>
              <td class="cell-num" data-label="Buku Dikembalikan"><?= $col_dikembalikan ? $total_tahun_kembali : '<span class="cell-dash">–</span>' ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Buku terpopuler -->
    <div class="section">
      <div class="section-head">
        <div>
          <div class="section-title">Buku Terpopuler</div>
          <div class="section-sub">Paling sering dipinjam sepanjang waktu</div>
        </div>
      </div>
      <div class="pop-list">
        <?php if (empty($buku_terpopuler)): ?>
          <div class="empty-mini">Belum ada data peminjaman.</div>
        <?php else: ?>
          <?php foreach ($buku_terpopuler as $i => $b): ?>
          <div class="pop-item">
            <span class="pop-rank"><?= $i + 1 ?></span>
            <span class="pop-title"><?= htmlspecialchars($b["judul"]) ?></span>
            <span class="pop-count"><?= (int)$b["jml"] ?>x dipinjam</span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Laporan ringkasan perpustakaan -->
  <div class="section" id="laporan">
    <div class="section-head">
      <div>
        <div class="section-title">Laporan Ringkasan</div>
        <div class="section-sub">Ringkasan koleksi, keanggotaan &amp; denda per hari ini</div>
      </div>
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a class="btn-whatsapp" href="<?= $wa_link ?>" target="_blank" rel="noopener">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.29-1.39a9.87 9.87 0 0 0 4.75 1.21h.01c5.46 0 9.9-4.44 9.9-9.9s-4.44-9.9-9.91-9.9zm5.8 14.06c-.24.68-1.4 1.3-1.93 1.38-.49.08-1.11.11-1.79-.11-.41-.13-.94-.3-1.62-.6-2.85-1.23-4.71-4.1-4.85-4.29-.14-.19-1.16-1.54-1.16-2.94s.73-2.09.99-2.37c.26-.29.56-.36.75-.36l.53.01c.17.01.4-.06.62.48.24.58.81 2 .88 2.14.07.15.12.32.02.52-.09.19-.14.31-.28.48-.14.16-.29.36-.42.48-.14.13-.28.28-.12.55.16.28.71 1.17 1.53 1.9 1.05.94 1.94 1.23 2.21 1.37.28.14.44.12.6-.07.16-.19.68-.79.87-1.06.18-.27.36-.22.6-.13.24.09 1.55.73 1.82.86.27.13.44.2.5.31.07.12.07.66-.17 1.35z"/></svg>
          Kirim via WhatsApp
        </a>
        <button class="btn-print" onclick="window.print()">🖨️ Cetak Laporan</button>
      </div>
    </div>

    <div class="laporan-grid">
      <div class="laporan-card">
        <h4>📚 Koleksi Buku</h4>
        <div class="laporan-row"><span>Total Judul Buku</span><span><?= $total_buku ?></span></div>
        <div class="laporan-row"><span>Total Stok Fisik</span><span><?= $total_stok ?></span></div>
        <div class="laporan-row"><span>Sedang Dipinjam</span><span><?= $sedang_dipinjam !== null ? $sedang_dipinjam : '–' ?></span></div>
      </div>

      <div class="laporan-card">
        <h4>👥 Keanggotaan</h4>
        <div class="laporan-row"><span>Anggota Aktif</span><span><?= $anggota_counts["approved"] ?></span></div>
        <div class="laporan-row"><span>Menunggu Persetujuan</span><span><?= $anggota_counts["pending"] ?></span></div>
        <div class="laporan-row"><span>Ditolak</span><span><?= $anggota_counts["rejected"] ?></span></div>
      </div>

      <div class="laporan-card">
        <h4>📖 Aktivitas Peminjaman</h4>
        <div class="laporan-row"><span>Peminjaman Bulan Ini</span><span><?= $peminjaman_bulan_ini ?></span></div>
        <div class="laporan-row"><span>Total Tahun <?= $tahun ?></span><span><?= $total_tahun_pinjam ?></span></div>
        <div class="laporan-row <?= ($terlambat ?? 0) > 0 ? 'highlight' : '' ?>"><span>Terlambat Kembali</span><span><?= $terlambat !== null ? $terlambat : '–' ?></span></div>
      </div>

      <div class="laporan-card">
        <h4>💰 Denda</h4>
        <?php if (!$denda_tersedia): ?>
          <div class="empty-mini" style="padding:8px 0;">Data denda belum tersedia.</div>
        <?php else: ?>
          <div class="laporan-row <?= $denda_belum_count > 0 ? 'highlight' : '' ?>">
            <span>Belum Dibayar (<?= $denda_belum_count ?>)</span><span>Rp <?= number_format($denda_belum_rp, 0, ',', '.') ?></span>
          </div>
          <div class="laporan-row ok">
            <span>Lunas (<?= $denda_lunas_count ?>)</span><span>Rp <?= number_format($denda_lunas_rp, 0, ',', '.') ?></span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</main>

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
      <input type="hidden" name="redirect" value="dashboard.php"/>

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
</script>
</body>
</html>