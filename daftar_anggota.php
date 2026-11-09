<?php
// daftar_anggota.php — Panel admin untuk kelola anggota (approve/tolak/reset sandi)
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: sign_in.php");
    exit;
}

require_once "db.php";
require_once "config.php";
// ─── Hitung badge sidebar (pending anggota & pengajuan buku) ───
$cnt_p_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='member' AND status='pending'");
$pending_count = $cnt_p_q ? (int)(mysqli_fetch_assoc($cnt_p_q)['c'] ?? 0) : 0;
$cnt_a_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'");
$cnt_aju_badge = $cnt_a_q ? (int)(mysqli_fetch_assoc($cnt_a_q)['c'] ?? 0) : 0;
$sidebar_badges_loaded = true;


// ─── Bantu format nomor HP anggota jadi format wa.me (62xxxxxxxxxx) ───
if (!function_exists('format_nomor_wa')) {
    function format_nomor_wa($no_hp) {
        $digits = preg_replace('/\D/', '', $no_hp ?? '');
        if ($digits === '') return '';
        if (substr($digits, 0, 1) === '0') {
            $digits = '62' . substr($digits, 1);
        } elseif (substr($digits, 0, 2) !== '62') {
            $digits = '62' . $digits;
        }
        return $digits;
    }
}

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
if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && !csrf_verify()) {
    $action   = "";
    $msg      = "Token sesi keamanan tidak valid atau kedaluwarsa. Silakan muat ulang halaman.";
    $msg_type = "error";
}

if ($action === "approve") {
    $id = (int)($_POST["id"] ?? 0);
    if ($id > 0) {
        mysqli_query($conn, "UPDATE users SET status='approved' WHERE id=$id AND role='member'");
        $msg = "Akun anggota berhasil diaktifkan."; $msg_type = "success";
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
        $msg = "Akun anggota berhasil dinonaktifkan."; $msg_type = "success";
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
            // Hapus file foto anggota jika ada
            $r_foto = mysqli_query($conn, "SELECT foto FROM users WHERE id=$id AND role='member'");
            if ($r_foto && $row_f = mysqli_fetch_assoc($r_foto)) {
                if (!empty($row_f["foto"]) && file_exists($row_f["foto"])) {
                    @unlink($row_f["foto"]);
                }
            }
            mysqli_query($conn, "DELETE FROM users WHERE id=$id AND role='member'");
            $msg = "Anggota berhasil dihapus."; $msg_type = "success";
        }
    }
}

if ($action === "reset_password") {
    $id = (int)($_POST["id"] ?? 0);
    $custom_pw = trim($_POST["custom_password"] ?? "");
    if ($id > 0) {
        $r = mysqli_query($conn, "SELECT full_name, username FROM users WHERE id=$id AND role='member'");
        $u = $r ? mysqli_fetch_assoc($r) : null;

        if ($u) {
            if ($custom_pw !== "") {
                $new_password = $custom_pw;
            } else {
                // ── Generate password baru acak ──
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
            }
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            $empty_plain = "";
            $stmt = mysqli_prepare($conn, "UPDATE users SET password=?, plain_password=? WHERE id=? AND role='member'");
            mysqli_stmt_bind_param($stmt, "ssi", $hashed, $empty_plain, $id);
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
//  AMBIL DATA & TAB FILTER
// ─────────────────────────────────────────────
$search  = trim($_GET["q"] ?? "");
$is_ajax = isset($_GET["ajax"]) && $_GET["ajax"] == "1";

// Tab filter: semua, siswa, guru, approved (aktif), rejected (nonaktif)
$tab = $_GET["tab"] ?? ($_GET["status"] ?? ($_GET["tipe"] ?? "all"));
if ($tab === "aktif") $tab = "approved";
if ($tab === "nonaktif") $tab = "rejected";
if ($tab === "menunggu") $tab = "pending";

$where = ["role = 'member'"];
if ($search !== "") {
    $s = mysqli_real_escape_string($conn, $search);
    $where[] = "(full_name LIKE '%$s%' OR nik LIKE '%$s%' OR username LIKE '%$s%' OR email LIKE '%$s%' OR no_anggota LIKE '%$s%')";
}

if ($tab === "siswa") {
    $where[] = "LOWER(TRIM(kelas)) != 'guru'";
} elseif ($tab === "guru") {
    $where[] = "LOWER(TRIM(kelas)) = 'guru'";
} elseif (in_array($tab, ["approved", "rejected", "pending"], true)) {
    $where[] = "status = '" . mysqli_real_escape_string($conn, $tab) . "'";
}
$where_sql = "WHERE " . implode(" AND ", $where);

$anggota_list = [];
$res = mysqli_query($conn, "SELECT * FROM users $where_sql ORDER BY (status='pending') DESC, id DESC");
while ($row = mysqli_fetch_assoc($res)) {
    $anggota_list[] = $row;
}
$total_anggota = count($anggota_list);

// 1. Total Seluruh Anggota (role = member)
$total_semua_res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='member'");
$total_semua = $total_semua_res ? (int)(mysqli_fetch_assoc($total_semua_res)['c'] ?? 0) : 0;

// 2. Total Guru & Siswa
$total_guru_res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='member' AND LOWER(TRIM(kelas)) = 'guru'");
$total_guru = $total_guru_res ? (int)(mysqli_fetch_assoc($total_guru_res)['c'] ?? 0) : 0;
$total_siswa = max(0, $total_semua - $total_guru);

// 3. Total Status (approved / aktif, rejected / nonaktif, pending)
$count_status_res = mysqli_query($conn, "SELECT status, COUNT(*) AS c FROM users WHERE role='member' GROUP BY status");
$status_counts = ["pending" => 0, "approved" => 0, "rejected" => 0];
if ($count_status_res) {
    while ($row = mysqli_fetch_assoc($count_status_res)) {
        $status_counts[$row["status"]] = (int)$row["c"];
    }
}
$counts = &$status_counts;
$total_aktif    = $status_counts["approved"];
$total_nonaktif = $status_counts["rejected"];
$total_pending  = $status_counts["pending"];
$pending_count  = $total_pending;

// Urutan 5 Tombol Tab: Semua, Siswa, Guru / Pendidik, Aktif, Nonaktif
$tabs = [
    "all"      => "Semua (" . $total_semua . ")",
    "siswa"    => "Siswa (" . $total_siswa . ")",
    "guru"     => "Guru / Pendidik (" . $total_guru . ")",
    "approved" => "Aktif (" . $total_aktif . ")",
    "rejected" => "Nonaktif (" . $total_nonaktif . ")",
];
if ($total_pending > 0) {
    $tabs["pending"] = "Menunggu (" . $total_pending . ")";
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
      --sidebar-w:   220px;
      --trans:       .2s cubic-bezier(.22,1,.36,1);
      --shadow:      0 2px 12px rgba(0,0,0,.3);
      /* Paksa semua kontrol native (dropdown select, checkbox, scrollbar, dll)
         mengikuti tema gelap situs ini, terlepas dari setelan light/dark mode
         browser/OS pengguna. Tanpa ini, beberapa browser menampilkan popup
         opsi <select> dengan latar terang bawaan sistem, sehingga teks
         berwarna terang (var(--text)) jadi tidak terbaca. */
      color-scheme: dark;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { overflow-x: hidden; max-width: 100%; }

    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      display: flex;
      min-height: 100vh;
      animation: bodyIn .4s ease both;
    }
    @keyframes bodyIn { from { opacity:0; } to { opacity:1; } }

    /* ── SIDEBAR (identik dengan halaman_admin.php) ── */
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
    .sidebar-toggle:hover { background:rgba(216,184,120,.18); border-color:var(--accent2,#f0d9a8); }
    .sidebar-toggle:active { transform:scale(0.96); }

    .avatar-wrap { position:relative; margin-bottom:14px; cursor:pointer; }
    .avatar-circle {
      width:72px; height:72px; max-width:72px; max-height:72px; border-radius:50%;
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
      background:var(--card,#121820); border:1px solid var(--border-color,rgba(216,184,120,.18)); border-radius:16px; width:100%; max-width:380px;
      max-height:90vh; overflow-y:auto; padding:24px;
      box-shadow:0 20px 60px rgba(0,0,0,.25);
      animation:modalIn .32s cubic-bezier(.16,1,.3,1) both;
    }
    @keyframes modalIn {
      from { opacity:0; transform:scale(.975) translateY(10px); }
      to   { opacity:1; transform:scale(1) translateY(0); }
    }
    .modal-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
    .modal-title { font-family:'Cormorant Garamond',serif; font-size:1.3rem; font-weight:700; color:var(--text); }
    .modal-close { border:none; background:var(--book-card,#161e27); width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text); }
    .modal-close svg { width:16px; height:16px; }
    .img-preview-wrap { position:relative; border:2px dashed #d8d8e4; overflow:hidden; cursor:pointer; }
    .img-preview-wrap img { width:100%; height:100%; object-fit:cover; }
    .upload-placeholder { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; color:var(--muted); }
    .upload-placeholder svg { width:26px; height:26px; }
    .form-group { margin-bottom:14px; }
    .form-label { display:block; font-size:.78rem; font-weight:700; color:var(--text); margin-bottom:6px; }
    .form-input { width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border-color,rgba(216,184,120,.2)); font-family:'Nunito',sans-serif; font-size:.85rem; background:rgba(255,255,255,.05); color:var(--text,#eef3f4); }
    .form-input:focus { outline:none; border-color:var(--accent); }
    /* Opsi dropdown (mis. daftar kelas asal/tujuan & target kelas pada kenaikan
       kelas serentak) perlu latar solid eksplisit — popup <option> dirender
       lapisan native oleh browser dan tidak selalu mewarisi background
       transparan di atas, sehingga teks terang bisa hilang di atas putih. */
    .form-input option, select.form-input option { background:var(--card,#121820); color:var(--text,#eef3f4); }
    .modal-footer { display:flex; gap:10px; margin-top:18px; }
    .btn-cancel, .btn-save {
      flex:1; padding:11px; border-radius:8px; border:none;
      font-family:'Nunito',sans-serif; font-size:.85rem; font-weight:700; cursor:pointer;
      transition:opacity var(--trans);
    }
    .btn-cancel:hover, .btn-save:hover { opacity:.85; }
    .btn-cancel { background:var(--book-card,#161e27); color:var(--muted); }
    .btn-save { background:var(--accent); color:#fff; }

    .admin-name-label { color:var(--text,#eef3f4); font-size:.95rem; font-weight:700; margin-bottom:8px; text-align:center; }
    .total-badge {
      background: rgba(216,184,120,.16); color:var(--accent,#d8b878);
      border: 1px solid rgba(216,184,120,.3);
      font-size:.72rem; font-weight:700;
      padding:4px 12px; border-radius:50px;
      margin-bottom:10px; text-align:center;
    }

    .sidebar-btn {
      width:100%; display:flex; align-items:center; gap:9px; padding:7.5px 12px; border-radius:8px;
      border:1px solid rgba(216,184,120,.16);
      background:rgba(255,255,255,.05); color:var(--text,#eef3f4);
      font-family:inherit; font-size:.82rem; font-weight:700; cursor:pointer; margin-bottom:4px;
      transition:all var(--trans);
      text-align:left; text-decoration:none;
      flex-shrink:0; box-sizing:border-box;
      line-height:1.25;
    }
    .sidebar-btn svg { width:16px; height:16px; flex-shrink:0; color:var(--accent,#d8b878); stroke:var(--accent,#d8b878); stroke-width:2px; transition:stroke .2s, transform .2s; }
    .sidebar-btn:hover { background:rgba(216,184,120,.16); border-color:var(--accent,#d8b878); color:#fff; }
    .sidebar-btn.sidebar-btn-logout {
      color: #e74c3c !important;
      margin-top: 10px;
    }
    .sidebar-btn.sidebar-btn-logout svg {
      color: #e74c3c !important;
      stroke: #e74c3c !important;
    }
    .sidebar-btn.sidebar-btn-logout:hover {
      background: rgba(231,76,60,.14) !important;
      border-color: #e74c3c !important;
      color: #ff6b6b !important;
    }
    .sidebar-btn.sidebar-btn-logout:hover svg {
      color: #ff6b6b !important;
      stroke: #ff6b6b !important;
      transform: translateX(2px);
    }
    .sidebar-btn:hover svg { color:#fff; stroke:#fff; }
    .sidebar-btn.active {
      background:linear-gradient(135deg,#d8b878,#c8a060);
      border-color:var(--accent,#d8b878);
      color:#121820; font-weight:800;
      box-shadow:0 4px 14px rgba(216,184,120,.35);
    }
    .sidebar-btn.active svg { color:#121820; stroke:#121820; stroke-width:2.2px; }

    /* ── MAIN ── */
    .main { view-transition-name:app-main; margin-left:var(--sidebar-w); flex:1; padding:26px 24px; transition:margin-left var(--trans); }

    .alert {
      padding:12px 18px; border-radius:8px; font-size:.82rem;
      font-weight:700; margin-bottom:16px; animation:fadeUp .4s both;
    }
    .alert-success { background:rgba(5,150,105,.15); color:#4ade80; border:1px solid rgba(5,150,105,.3); }
    .alert-error   { background:rgba(220,38,38,.15); color:#f87171; border:1px solid rgba(220,38,38,.3); }

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
      background:var(--card,#121820); border:1px solid var(--border-color,rgba(216,184,120,.18)); border-radius:50px;
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
      background:linear-gradient(135deg,#d8b878,#c8a060);
      color:#121820; font-family:'Nunito',sans-serif;
      font-size:.78rem; font-weight:800; cursor:pointer;
      border:none; border-radius:20px; padding:7px 18px;
      transition:all .18s ease;
      box-shadow:0 2px 8px rgba(216,184,120,.22);
    }
    .btn-search:hover {
      background:linear-gradient(135deg,#e4c585,#d2aa6b);
      box-shadow:0 4px 14px rgba(216,184,120,.38);
      color:#121820;
    }
    .btn-search:active { transform:scale(.96); }


    .filter-kelas-badge {
      display: inline-flex; align-items: center; gap: 5px;
      background: rgba(216,184,120,.14); color: var(--accent, #d8b878);
      border: 1px solid rgba(216,184,120,.3); padding: 3px 10px;
      border-radius: 20px; font-size: .8rem; font-weight: 700;
      margin-left: 8px; vertical-align: middle;
    }
    .filter-kelas-badge svg { width: 13px; height: 13px; flex-shrink: 0; }
    .filter-badge-guru {
      background: rgba(99,102,241,.18) !important;
      color: #818cf8 !important;
      border-color: rgba(99,102,241,.35) !important;
    }

    .content-header {
      display:flex; align-items:center; justify-content:space-between;
      margin-bottom:14px; animation:fadeUp .5s .08s both; flex-wrap:wrap; gap:10px;
    }
    .content-title { font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:700; color:var(--text); }
    
    
    
    
    .badge-alumni {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 3px 10px;
      border-radius: 6px;
      font-size: .72rem;
      font-weight: 700;
      background: rgba(16,185,129,.15);
      color: #34d399;
      border: 1px solid rgba(16,185,129,.3);
    }
    .tab-naik-btn {
      flex: 1;
      padding: 10px 14px;
      border-radius: 8px;
      font-size: .8rem;
      font-weight: 700;
      font-family: 'Nunito', sans-serif;
      cursor: pointer;
      border: 1px solid var(--border-color, rgba(216,184,120,.18));
      background: rgba(255,255,255,.04);
      color: var(--muted);
      transition: all var(--trans);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }
    .tab-naik-btn:hover {
      color: var(--text);
      background: rgba(255,255,255,.08);
    }
    .tab-naik-btn.active {
      background: linear-gradient(135deg, rgba(16,185,129,.22), rgba(16,185,129,.08));
      color: #34d399;
      border-color: rgba(16,185,129,.45);
      box-shadow: 0 2px 10px rgba(16,185,129,.15);
    }
    .promo-row {
      display: flex;
      align-items: center;
      gap: 10px;
      background: rgba(255,255,255,.04);
      border: 1px solid rgba(90,90,110,.15);
      padding: 9px 12px;
      border-radius: 8px;
      margin-bottom: 7px;
      transition: all var(--trans);
    }
    .promo-row:hover {
      border-color: rgba(90,90,110,.3);
      background: rgba(255,255,255,.08);
    }
    .promo-row.disabled {
      opacity: .4;
      background: rgba(255,255,255,.02);
    }
    .promo-badge-count {
      display: inline-block;
      padding: 2px 7px;
      border-radius: 12px;
      font-size: .7rem;
      font-weight: 700;
      background: rgba(216,184,120,.12);
      color: var(--accent, #d8b878);
      white-space: nowrap;
    }

    .status-tabs { display:flex; gap:8px; margin-bottom:18px; flex-wrap:wrap; animation:fadeUp .5s .1s both; }
    .status-tab {
      padding:7px 16px; border-radius:50px; font-size:.78rem; font-weight:700;
      text-decoration:none; color:var(--muted); background:var(--card,#121820); box-shadow:var(--shadow);
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
      border-bottom:1.5px solid var(--border-color,rgba(216,184,120,.18)); white-space:nowrap;
    }
    tbody td {
      padding:12px 16px; font-size:.82rem; color:var(--text);
      border-bottom:1px solid var(--border-color,rgba(216,184,120,.1)); vertical-align:middle;
    }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover { background:rgba(255,255,255,.04); }
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
    .badge-approved { background:rgba(5,150,105,.15); color:#4ade80; border:1px solid rgba(5,150,105,.28); }
    .badge-rejected { background:rgba(220,38,38,.15); color:#f87171; border:1px solid rgba(220,38,38,.28); }
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
    .act-kartu   { background:#2f80ed; color:#fff; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; text-align:center; box-sizing:border-box; }
    .act-wa      { background:#25d366; color:#fff; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; text-align:center; box-sizing:border-box; }
    .act-wa.disabled { background:#c9ccd3; color:#fff; cursor:not-allowed; pointer-events:none; }

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
      .main { margin-left:0; padding:70px 14px 24px; max-width:100vw; }

      /* Search bar: full width, rapi menumpuk kalau ada tombol reset */
      .topbar {
        border-radius:14px; height:auto; padding:10px 14px;
        flex-wrap:wrap; row-gap:6px;
      }
      .topbar input { min-width:0; }
      #searchResetBtn { width:100%; text-align:right; }

      /* Filter kelas: label di atas, dropdown full width */
      
      

      /* Header konten: judul di atas, tombol kelola kelas full width di bawah */
      .content-header { flex-direction:column; align-items:stretch; }
      .content-title { font-size:1.35rem; }
      

      /* Tab status: scroll horizontal HANYA di dalam baris tab ini, bukan seluruh halaman */
      .status-tabs {
        flex-wrap:nowrap; overflow-x:auto; -webkit-overflow-scrolling:touch;
        padding-bottom:4px; scrollbar-width:none;
      }
      .status-tabs::-webkit-scrollbar { display:none; }
      .status-tab { flex-shrink:0; }

      .reset-banner { flex-direction:column; align-items:stretch; }
      .reset-banner .rb-cred { word-break:break-all; }

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
        display:flex; align-items:flex-start; justify-content:space-between; gap:10px;
        padding:12px 14px; border-bottom:1px solid var(--border-color,rgba(216,184,120,.1)); text-align:right;
      }
      tbody tr td:last-child { border-bottom:none; }
      tbody td::before {
        content:attr(data-label); font-size:.68rem; font-weight:800; color:var(--muted);
        text-transform:uppercase; letter-spacing:.05em; text-align:left; flex-shrink:0;
        padding-top:2px;
      }
      /* Nilai sel dibungkus rapi, tidak memicu geser ke samping walau teksnya panjang
         (email, username, no. anggota) */
      tbody td > *:not(.cell-anggota):not(.row-actions) { min-width:0; }
      tbody td:not([data-label="Anggota"]):not([data-label="Aksi"]) {
        word-break:break-word; overflow-wrap:anywhere;
      }
      .cell-mono { word-break:break-all; text-align:right; }

      tbody td[data-label="Anggota"] { flex-direction:column; align-items:flex-start; text-align:left; }
      tbody td[data-label="Anggota"]::before { margin-bottom:6px; }
      .cell-anggota { width:100%; }
      .cell-anggota > div { min-width:0; }
      .cell-name, .cell-sub { word-break:break-word; overflow-wrap:anywhere; }

      /* Status: badge dibungkus rapi, tidak mepet ke label */
      tbody td[data-label="Status"] { flex-wrap:wrap; justify-content:flex-end; }
      .badge-frozen { white-space:normal; text-align:right; }

      /* Aksi: tombol ditumpuk vertikal full-width, jelas dan mudah diketuk */
      tbody td[data-label="Aksi"] { flex-direction:column; align-items:stretch; }
      tbody td[data-label="Aksi"]::before { margin-bottom:8px; }
      .row-actions { width:100%; flex-direction:column; }
      .act-btn { width:100%; padding:10px 12px; font-size:.76rem; white-space:normal; }
    }
    @media (max-width:380px) {
      .content-title { font-size:1.2rem; }
      .reset-banner .rb-cred { font-size:.76rem; gap:10px; }
    }
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
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    <span>AKSA NOVA • Daftar Anggota</span>
  </div>
</header>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="avatar-wrap" onclick="openProfilModal()" title="Edit Profil">
    <div class="avatar-circle">
      <?php if ($admin_foto && file_exists($admin_foto)): ?>
        <img src="<?= htmlspecialchars($admin_foto) ?>?v=<?= filemtime($admin_foto) ?>" alt="Admin" width="72" height="72" loading="eager" decoding="sync"/>
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
  <a class="sidebar-btn active" href="daftar_anggota.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    Daftar Anggota
    <?php if (!empty($pending_count) && $pending_count > 0): ?>
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
    Pengajuan Pinjam
    <?php if ($cnt_aju_badge > 0): ?>
      <span class="aksa-notif-badge"><?= $cnt_aju_badge ?></span>
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
    <a class="sidebar-btn" href="pengaturan_lokasi.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 21s-7-6.1-7-11a7 7 0 1 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>
      </svg>
      Peta & Jam Buka
    </a>
  
    <button class="sidebar-btn btn-settings-nav" onclick="if(typeof bukaSettings==='function')bukaSettings()" title="Tema & Pengaturan Tampilan">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
      Tema & Tampilan
    </button>
    <a class="sidebar-btn sidebar-btn-logout" href="logout.php" onclick="return window.konfirmasiLogoutAksa ? window.konfirmasiLogoutAksa(event, this) : confirm('Apakah Anda yakin ingin keluar (logout)?');">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      Keluar
    </a>
  </nav>
</aside>

<main class="main">

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type ?>">
    <span style="font-weight:bold;margin-right:4px;"><?= $msg_type === "success" ? "✓" : "!" ?></span> <?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>

  <?php if ($reset_info): ?>
  <div class="reset-banner">
    <div class="rb-left">
      Sandi baru untuk <b><?= htmlspecialchars($reset_info["full_name"]) ?></b> berhasil dibuat.<br>
      Sampaikan ke anggota — sandi ini hanya tampil sekali di sini.
    </div>
    <div class="rb-cred">
      <span style="display:inline-flex;align-items:center;gap:6px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <?= htmlspecialchars($reset_info["username"]) ?>
      </span>
      <span style="display:inline-flex;align-items:center;gap:6px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3"/></svg>
        <?= htmlspecialchars($reset_info["password"]) ?>
      </span>
    </div>
  </div>
  <?php endif; ?>

  <form method="GET" action="" id="searchForm">
    <input type="hidden" name="tab" id="searchTabHidden" value="<?= htmlspecialchars($tab) ?>"/>
    <div class="topbar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" name="q" id="searchInput" autocomplete="off" placeholder="Cari anggota berdasarkan nama, NIS/NIP, username, atau email…"
             value="<?= htmlspecialchars($search) ?>"/>
      <?php if ($search || $tab !== 'all'): ?>
      <a href="daftar_anggota.php" id="searchResetBtn" style="font-size:.75rem;color:var(--muted);text-decoration:none;white-space:nowrap;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block;vertical-align:-1px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Reset</a>
      <?php endif; ?>
    </div>
  </form>

  <div id="searchResultArea">
  <?php ob_start(); ?>
  <div class="content-header">
    <div class="content-title">
      Daftar Anggota
      <?php if ($search): ?>
        <span style="font-size:.9rem;color:var(--muted);font-family:'Nunito',sans-serif;"> — hasil: "<?= htmlspecialchars($search) ?>"</span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Baris tombol filter: Semua, Siswa, Guru / Pendidik, Aktif, Nonaktif -->
  <div class="status-tabs">
    <?php
      $qstr = $search !== "" ? "&q=" . urlencode($search) : "";
      foreach ($tabs as $key => $label):
        $active = $tab === $key ? "active" : "";
    ?>
      <a class="status-tab <?= $active ?>" data-status="<?= $key ?>" href="daftar_anggota.php?tab=<?= $key ?><?= $qstr ?>"><?= $label ?></a>
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
                <div class="cell-name">
                <?= htmlspecialchars($a["full_name"]) ?>
                <?php if (strcasecmp($a["kelas"] ?? "", "Guru") === 0): ?>
                  <span class="badge" style="background:rgba(99,102,241,.18);color:#818cf8;border:1px solid rgba(99,102,241,.35);font-weight:700;padding:2px 7px;border-radius:6px;font-size:.70rem;display:inline-flex;align-items:center;gap:3px;margin-left:6px;vertical-align:middle;">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Guru / Pendidik
                  </span>
                <?php endif; ?>
              </div>
              <div class="cell-sub">
                <?= htmlspecialchars($a["email"]) ?>
                <?php if (!empty($a["nik"])): ?>
                  • <?= strcasecmp($a["kelas"] ?? "", "Guru") === 0 ? "NIP: " : "NIS: " ?><?= htmlspecialchars($a["nik"]) ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </td>
          <td class="cell-mono" data-label="No. Anggota"><?= htmlspecialchars($a["no_anggota"]) ?></td>
          <td class="cell-mono" data-label="Username"><?= htmlspecialchars($a["username"]) ?></td>
          <td data-label="Status">
            <?php if ($a["status"] === "pending"): ?>
              <span class="badge badge-pending">Menunggu</span>
            <?php elseif ($a["status"] === "approved"): ?>
              <span class="badge badge-approved">Aktif</span>
            <?php else: ?>
              <span class="badge badge-rejected">Nonaktif</span>
            <?php endif; ?>
            <?php if (($a["card_status"] ?? "active") === "frozen"): ?>
              <span class="badge badge-frozen" style="display:inline-flex;align-items:center;gap:4px;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Dibekukan (proses Lupa Kartu)
              </span>
            <?php endif; ?>
          </td>
          <td data-label="Aksi">
            <div class="row-actions">
              <?php if ($a["status"] === "pending"): ?>
                <button class="act-btn act-approve" onclick="kirimAksi('approve', <?= $a['id'] ?>)">Aktifkan</button>
                <button class="act-btn act-reject" onclick="kirimAksi('reject', <?= $a['id'] ?>)">Tolak</button>
              <?php elseif ($a["status"] === "approved"): ?>
                <button class="act-btn act-reject" onclick="konfirmasiNonaktifkan(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['full_name'])) ?>')">Nonaktifkan</button>
                <button class="act-btn act-reset" onclick="bukaModalUbahSandi(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['full_name'])) ?>', '<?= htmlspecialchars(addslashes($a['username'])) ?>')">Ubah Sandi</button>
              <?php else: ?>
                <button class="act-btn act-approve" onclick="kirimAksi('approve', <?= $a['id'] ?>)">Aktifkan Kembali</button>
              <?php endif; ?>
              <?php if (($a["card_status"] ?? "active") === "frozen"): ?>
                <button class="act-btn act-reset" onclick="konfirmasiCairkan(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['full_name'])) ?>')">Cairkan Kartu</button>
              <?php endif; ?>
              <a class="act-btn act-kartu" href="kartu_anggota.php?id=<?= mask_id($a['id']) ?>" target="_blank" rel="noopener">Lihat Kartu</a>
              <?php $wa_number = format_nomor_wa($a["no_hp"] ?? ""); ?>
              <?php if ($wa_number !== ""): ?>
                <a class="act-btn act-wa" href="https://wa.me/<?= $wa_number ?>" target="_blank" rel="noopener">Chat WA</a>
              <?php else: ?>
                <span class="act-btn act-wa disabled" title="Anggota belum mengisi nomor HP">Chat WA</span>
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
  <?= csrf_field() ?>
  <input type="hidden" name="action" id="aksiAction"/>
  <input type="hidden" name="id" id="aksiId"/>
</form>





<!-- ═══════════ MODAL UBAH KATA SANDI ANGGOTA ═══════════ -->
<div class="modal-overlay" id="ubahSandiModalOverlay">
  <div class="modal-box" style="max-width:440px; width:92%;">
    <div class="modal-header">
      <div class="modal-title" style="display:flex;align-items:center;gap:8px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--accent);">
          <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        Ubah Kata Sandi Anggota
      </div>
      <button type="button" class="modal-close" onclick="closeUbahSandiModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <form method="POST" action="daftar_anggota.php" id="formUbahSandiModal" style="margin-top:14px;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="reset_password"/>
      <input type="hidden" name="id" id="ubahSandiUserId" value=""/>

      <div style="margin-bottom:14px; background:rgba(216,184,120,.07); border:1px solid rgba(216,184,120,.2); padding:10px 14px; border-radius:8px;">
        <div style="font-size:.76rem; color:var(--muted); margin-bottom:2px;">Anggota:</div>
        <div id="ubahSandiNamaDisplay" style="font-weight:700; font-size:.92rem; color:var(--text);"></div>
        <div id="ubahSandiUserDisplay" style="font-size:.78rem; color:var(--accent); font-family:monospace; margin-top:2px;"></div>
      </div>

      <div style="margin-bottom:18px;">
        <label style="display:block; font-size:.8rem; font-weight:700; color:var(--text); margin-bottom:6px;">
          Kata Sandi Baru:
        </label>
        <div style="display:flex; gap:8px;">
          <input type="text" name="custom_password" id="inputCustomPassword" class="form-input" placeholder="Ketik sandi baru atau klik Acak" required style="flex:1; padding:9px 12px; font-size:.88rem; font-family:monospace;"/>
          <button type="button" class="act-btn" onclick="generateRandomSandi()" title="Buat Sandi Acak Otomatis" style="background:rgba(216,184,120,.15); color:var(--accent); border:1px solid rgba(216,184,120,.3); padding:8px 12px; font-size:.78rem; font-weight:700; white-space:nowrap; border-radius:8px; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
            Acak
          </button>
        </div>
        <p style="font-size:.74rem; color:var(--muted); margin-top:6px; line-height:1.4;">
          Sandi lama akan langsung diganti. Sampaikan sandi baru ini kepada anggota yang bersangkutan.
        </p>
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" class="btn-cancel" onclick="closeUbahSandiModal()" style="padding:9px 16px; font-size:.82rem; border-radius:8px;">Batal</button>
        <button type="submit" class="btn-save" style="padding:9px 20px; font-size:.82rem; border-radius:8px;">Simpan Sandi</button>
      </div>
    </form>
  </div>
</div>



<script>
// Sidebar mobile toggle & modal profil dikelola terpusat oleh settings_include.php & modal_profil_admin.php

function kirimAksi(action, id) {
  document.getElementById('aksiAction').value = action;
  document.getElementById('aksiId').value = id;
  document.getElementById('formAksi').submit();
}

// ─── Modal Ubah Sandi Anggota ───
function bukaModalUbahSandi(id, nama, username) {
  document.getElementById('ubahSandiUserId').value = id;
  document.getElementById('ubahSandiNamaDisplay').textContent = nama;
  document.getElementById('ubahSandiUserDisplay').textContent = '@' + username;
  document.getElementById('inputCustomPassword').value = '';
  document.getElementById('ubahSandiModalOverlay').classList.add('open');
  setTimeout(() => {
    const inp = document.getElementById('inputCustomPassword');
    if (inp) inp.focus();
  }, 100);
}

function closeUbahSandiModal() {
  document.getElementById('ubahSandiModalOverlay').classList.remove('open');
}

document.getElementById('ubahSandiModalOverlay')?.addEventListener('click', function(e) {
  if (e.target === this) closeUbahSandiModal();
});

function generateRandomSandi() {
  const chars = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789";
  let pw = "";
  for (let i = 0; i < 8; i++) {
    pw += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  document.getElementById('inputCustomPassword').value = pw;
}

function togglePeekPw(id, plainPw) {
  const el = document.getElementById('pwText_' + id);
  if (!el) return;
  if (el.getAttribute('data-shown') === '1') {
    el.textContent = '••••••••';
    el.removeAttribute('data-shown');
  } else {
    el.textContent = plainPw;
    el.setAttribute('data-shown', '1');
  }
}

function konfirmasiReset(id, nama) {
  bukaModalUbahSandi(id, nama, '');
}

function konfirmasiCairkan(id, nama) {
  AksaConfirm({
    title: 'Cairkan Kartu Anggota?',
    icon: 'unlock',
    message: `Cairkan kartu anggota "<strong>${escapeHtmlText(nama)}</strong>"? Gunakan ini hanya jika anggota meninggalkan proses "Lupa Kartu" di tengah jalan tanpa selesai.`,
    confirmText: 'Ya, Cairkan Kartu',
    cancelText: 'Batal',
    type: 'warning'
  }).then(function(confirmed) {
    if (confirmed) kirimAksi('unfreeze', id);
  });
}

function konfirmasiNonaktifkan(id, nama) {
  AksaConfirm({
    title: 'Nonaktifkan Akun Anggota?',
    icon: 'user-x',
    message: `Nonaktifkan akun "<strong>${escapeHtmlText(nama)}</strong>"? Anggota tidak akan bisa masuk sampai diaktifkan kembali oleh admin.`,
    confirmText: 'Ya, Nonaktifkan',
    cancelText: 'Batal',
    type: 'danger'
  }).then(function(confirmed) {
    if (confirmed) kirimAksi('reject', id);
  });
}

function konfirmasiHapusAnggota(id, nama) {
  AksaConfirm({
    title: 'Hapus Anggota?',
    icon: 'trash',
    message: `Hapus anggota "<strong>${escapeHtmlText(nama)}</strong>"? Tindakan ini bersifat permanen dan tidak dapat dibatalkan.`,
    confirmText: 'Ya, Hapus Anggota',
    cancelText: 'Batal',
    type: 'danger'
  }).then(function(confirmed) {
    if (confirmed) kirimAksi('hapus', id);
  });
}

function escapeHtmlText(str) {
  return String(str).replace(/[&<>"']/g, function(m) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
  });
}

// ─── Live Search & Filter Tabs ───
(function initLiveSearch() {
  let currentFilterTab = <?= json_encode($tab) ?>;
  const form   = document.getElementById('searchForm');
  const input  = document.getElementById('searchInput');
  const result = document.getElementById('searchResultArea');
  if (!form || !input || !result) return;

  let debounceTimer = null;
  let currentRequest = null;

  form.addEventListener('submit', e => {
    e.preventDefault();
    runSearch(input.value);
  });

  function buildParams(query) {
    const params = new URLSearchParams();
    if (query) params.set('q', query);
    if (currentFilterTab && currentFilterTab !== 'all') params.set('tab', currentFilterTab);
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
    if (resetBtn) {
      e.preventDefault();
      input.value = '';
      currentFilterTab = 'all';
      const hiddenTab = document.getElementById('searchTabHidden');
      if (hiddenTab) hiddenTab.value = 'all';
      runSearch('');
      return;
    }

    const tabLink = e.target.closest('.status-tab');
    if (tabLink && result.contains(tabLink)) {
      e.preventDefault();
      const st = tabLink.dataset.status || 'all';
      currentFilterTab = st;
      const hiddenTab = document.getElementById('searchTabHidden');
      if (hiddenTab) hiddenTab.value = st;
      runSearch(input.value);
    }
  });
})();
</script>
<?php require_once 'modal_profil_admin.php';
require_once 'pengaturan_panel.php'; ?>
</body>
</html>
<?php ob_end_flush(); ?>