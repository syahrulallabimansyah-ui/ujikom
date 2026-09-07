<?php
// pengajuan_peminjaman.php — Halaman Pengajuan Peminjaman Buku oleh Anggota
declare(strict_types=1);
session_start();

require_once 'db.php';

// ─── Verifikasi login anggota ───
if (!isset($_SESSION['user_id'])) {
    $redirect_url = urlencode('pengajuan_peminjaman.php' . (!empty($_GET['buku_id']) ? '?buku_id=' . (int)$_GET['buku_id'] : ''));
    header('Location: sign_in.php?redirect=' . $redirect_url);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'member';

// Ambil data user terkini
$u_stmt = mysqli_prepare($conn, "SELECT id, full_name, kelas, no_anggota, email, no_hp, foto, status, card_status FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($u_stmt, "i", $user_id);
mysqli_stmt_execute($u_stmt);
$u_res = mysqli_stmt_get_result($u_stmt);
$user = mysqli_fetch_assoc($u_res);
mysqli_stmt_close($u_stmt);

if (!$user) {
    session_destroy();
    header('Location: sign_in.php');
    exit;
}

$error_msg = '';
$success_msg = '';

// Ambil buku yang dipilih jika ada param buku_id
$selected_buku_id = (int)($_POST['buku_id'] ?? ($_GET['buku_id'] ?? 0));
$buku_terpilih = null;

if ($selected_buku_id > 0) {
    $b_stmt = mysqli_prepare($conn, "SELECT id, judul, penulis, isbn, genre, stok, gambar, sinopsis FROM buku WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($b_stmt, "i", $selected_buku_id);
    mysqli_stmt_execute($b_stmt);
    $b_res = mysqli_stmt_get_result($b_stmt);
    $buku_terpilih = mysqli_fetch_assoc($b_res);
    mysqli_stmt_close($b_stmt);
}

// Ambil daftar seluruh buku yang stoknya tersedia untuk opsi ganti buku
$daftar_buku_tersedia = [];
$buku_all_res = mysqli_query($conn, "SELECT id, judul, penulis, genre, stok, gambar FROM buku WHERE stok > 0 ORDER BY judul ASC");
if ($buku_all_res) {
    while ($rb = mysqli_fetch_assoc($buku_all_res)) {
        $daftar_buku_tersedia[] = $rb;
    }
}

// Jika belum ada buku terpilih dan ada buku tersedia, default ke buku pertama
if (!$buku_terpilih && !empty($daftar_buku_tersedia)) {
    $buku_terpilih = $daftar_buku_tersedia[0];
    $selected_buku_id = (int)$buku_terpilih['id'];
}

// ─── Proses Formulir POST ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajukan') {
    $post_buku_id    = (int)($_POST['buku_id'] ?? 0);
    $total_buku      = max(1, (int)($_POST['total_buku'] ?? 1));
    $batas_kembali   = trim($_POST['batas_kembali'] ?? '');
    $waktu_ambil     = trim($_POST['waktu_pengambilan'] ?? 'sekarang');
    if (!in_array($waktu_ambil, ['sekarang', 'nanti'], true)) {
        $waktu_ambil = 'sekarang';
    }

    // Format catatan pengambilan berdasarkan pilihan
    if ($waktu_ambil === 'nanti') {
        $tgl_nanti   = trim($_POST['tanggal_ambil_nanti'] ?? '');
        $jam_pilihan = trim($_POST['jam_ambil_pilihan'] ?? '');
        $catatan_usr = trim($_POST['catatan_pengambilan'] ?? '');

        $parts_nanti = [];
        if (!empty($tgl_nanti) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_nanti)) {
            $parts_nanti[] = 'Tgl: ' . date('d/m/Y', strtotime($tgl_nanti));
        }
        if (!empty($jam_pilihan) && $jam_pilihan !== 'Lainnya (Tulis di Catatan)') {
            $parts_nanti[] = $jam_pilihan;
        }
        if (!empty($catatan_usr)) {
            $parts_nanti[] = $catatan_usr;
        }

        $catatan_ambil = !empty($parts_nanti) ? implode(' · ', $parts_nanti) : 'Diambil saat jam luang / hari berikutnya';
    } else {
        $catatan_ambil = 'Diambil langsung di perpustakaan hari ini';
    }

    // Ambil data buku yang diajukan
    $cek_b_stmt = mysqli_prepare($conn, "SELECT id, judul, stok FROM buku WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($cek_b_stmt, "i", $post_buku_id);
    mysqli_stmt_execute($cek_b_stmt);
    $cek_b_res = mysqli_stmt_get_result($cek_b_stmt);
    $buku_cek = mysqli_fetch_assoc($cek_b_res);
    mysqli_stmt_close($cek_b_stmt);

    if (!$buku_cek) {
        $error_msg = 'Buku yang dipilih tidak ditemukan dalam katalog.';
    } elseif ((int)$buku_cek['stok'] <= 0) {
        $error_msg = 'Maaf, stok buku "' . htmlspecialchars($buku_cek['judul']) . '" saat ini sedang habis.';
    } elseif ($total_buku > (int)$buku_cek['stok']) {
        $error_msg = 'Jumlah buku yang diajukan (' . $total_buku . ') melebihi stok yang tersedia (' . $buku_cek['stok'] . ').';
    } elseif ($total_buku > 3) {
        $error_msg = 'Maksimal peminjaman adalah 3 buku sekaligus.';
    } elseif (empty($batas_kembali) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $batas_kembali)) {
        $error_msg = 'Format tanggal batas pengembalian tidak valid.';
    } elseif (strtotime($batas_kembali) < strtotime(date('Y-m-d'))) {
        $error_msg = 'Tanggal batas pengembalian tidak boleh tanggal yang sudah lewat.';
    } elseif (!isset($_FILES['file_kartu']) || $_FILES['file_kartu']['error'] === UPLOAD_ERR_NO_FILE) {
        $error_msg = 'Foto/gambar kartu anggota wajib diunggah sebagai bukti validasi.';
    } elseif ($_FILES['file_kartu']['error'] !== UPLOAD_ERR_OK) {
        $err_c = (int)$_FILES['file_kartu']['error'];
        if ($err_c === UPLOAD_ERR_INI_SIZE || $err_c === UPLOAD_ERR_FORM_SIZE) {
            $error_msg = 'Ukuran berkas kartu terlalu besar (maksimal 5MB).';
        } else {
            $error_msg = 'Terjadi kendala saat mengunggah berkas kartu (Kode error: ' . $err_c . '). Silakan coba unggah ulang.';
        }
    } else {
        // Validasi Berkas Unggahan
        $file_tmp  = $_FILES['file_kartu']['tmp_name'];
        $file_size = (int)$_FILES['file_kartu']['size'];
        $file_name = $_FILES['file_kartu']['name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        $max_size = 5 * 1024 * 1024; // 5 MB

        if (!in_array($file_ext, $allowed_exts, true)) {
            $error_msg = 'Format berkas kartu harus berupa gambar (JPG, JPEG, PNG, atau WEBP).';
        } elseif ($file_size > $max_size) {
            $error_msg = 'Ukuran berkas kartu maksimal 5MB.';
        } else {
            // Verifikasi gambar asli menggunakan getimagesize
            $img_info = @getimagesize($file_tmp);
            if ($img_info === false) {
                $error_msg = 'Berkas yang diunggah bukan file gambar yang valid atau rusak.';
            } else {
                $target_dir = __DIR__ . '/uploads/kartu_pengajuan/';
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                $new_filename = 'kartu_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
                $target_path = $target_dir . $new_filename;
                $db_file_path = 'uploads/kartu_pengajuan/' . $new_filename;

                if (move_uploaded_file($file_tmp, $target_path)) {
                    $nama_peminjam = $user['full_name'];
                    $ins_stmt = mysqli_prepare($conn,
                        "INSERT INTO pengajuan_peminjaman 
                         (user_id, buku_id, nama_peminjam, file_kartu, total_buku, batas_kembali, waktu_pengambilan, catatan_pengambilan, status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'menunggu')"
                    );
                    mysqli_stmt_bind_param($ins_stmt, "iissssss",
                        $user_id, $post_buku_id, $nama_peminjam, $db_file_path,
                        $total_buku, $batas_kembali, $waktu_ambil, $catatan_ambil
                    );

                    if (mysqli_stmt_execute($ins_stmt)) {
                        $success_msg = 'Pengajuan peminjaman berhasil dikirim! Silakan tunggu persetujuan dari petugas perpustakaan.';
                    } else {
                        $error_msg = 'Gagal menyimpan pengajuan ke database: ' . mysqli_error($conn);
                    }
                    mysqli_stmt_close($ins_stmt);
                } else {
                    $error_msg = 'Gagal menyimpan berkas kartu ke server. Pastikan folder dapat ditulisi.';
                }
            }
        }
    }

    // Refresh data buku terpilih jika form disubmit
    if ($post_buku_id > 0) {
        $selected_buku_id = $post_buku_id;
        $b_stmt = mysqli_prepare($conn, "SELECT id, judul, penulis, isbn, genre, stok, gambar, sinopsis FROM buku WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($b_stmt, "i", $selected_buku_id);
        mysqli_stmt_execute($b_stmt);
        $b_res = mysqli_stmt_get_result($b_stmt);
        $buku_terpilih = mysqli_fetch_assoc($b_res);
        mysqli_stmt_close($b_stmt);
    }
}

$page_title = 'Ajukan Peminjaman Buku – AKSA NOVA';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,400&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg:           #090c10;
      --sidebar-bg:   #10151b;
      --card:         #121820;
      --accent:       #d8b878;
      --accent2:      #f0d9a8;
      --accent-rgb:   216,184,120;
      --text:         #eef3f4;
      --muted:        rgba(238,243,244,.65);
      --border-color: rgba(216,184,120,.18);
      --card-border:  rgba(216,184,120,.12);
      --book-card:    #161e27;
      --radius:       14px;
      --sidebar-w:    204px;
      --trans:        .2s cubic-bezier(.22,1,.36,1);
      --shadow:       0 8px 32px rgba(0,0,0,.45);
    }

    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    html { overflow-x:hidden; }
    body {
      font-family: var(--font-family, 'Outfit', sans-serif);
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
    }

    /* ── SIDEBAR ── */
    .sidebar {
      width:var(--sidebar-w); height:100vh; height:100dvh; background:var(--sidebar-bg);
      display:flex; flex-direction:column; padding:24px 0 20px;
      border-right:1px solid var(--border-color);
      position:fixed; top:0; left:0; bottom:0; z-index:170; transition:transform var(--trans);
      overflow-y:auto; scrollbar-width:thin;
      box-shadow:2px 0 24px rgba(0,0,0,.35);
    }
    .logo-wrap { display:flex; flex-direction:column; align-items:center; padding:0 18px 24px; border-bottom:1px solid var(--border-color); }
    .logo-icon { width:50px; height:50px; background:linear-gradient(135deg, rgba(216,184,120,.2) 0%, rgba(216,184,120,.05) 100%); border:1px solid rgba(216,184,120,.3); border-radius:14px; display:flex; align-items:center; justify-content:center; margin-bottom:8px; }
    .logo-icon svg { width:26px; height:26px; color:var(--accent); }
    .logo-name { font-family:'Cormorant Garamond',serif; font-size:1.1rem; font-weight:700; color:var(--accent); letter-spacing:.1em; text-align:center; }
    .logo-sub  { font-size:.58rem; color:var(--muted); letter-spacing:.12em; text-transform:uppercase; text-align:center; margin-top:2px; }

    .nav { flex:1; display:flex; flex-direction:column; gap:2px; padding:16px 10px; }
    .nav-item { position:relative; display:flex; align-items:center; gap:10px; padding:11px 14px; border-radius:10px; font-size:.82rem; font-weight:600; color:var(--muted); cursor:pointer; text-decoration:none; transition:background var(--trans), color var(--trans); }
    .nav-item:hover  { background:rgba(216,184,120,.10); color:var(--accent); }
    .nav-item.active { background:rgba(216,184,120,.16); color:var(--accent); }
    .nav-item.active::before { content:''; position:absolute; left:-10px; top:50%; transform:translateY(-50%); width:3px; height:60%; border-radius:0 4px 4px 0; background:var(--accent); }
    .nav-item svg { width:17px; height:17px; flex-shrink:0; }
    .nav-bottom { padding:10px 10px 0; border-top:1px solid var(--border-color); display:flex; flex-direction:column; gap:2px; flex-shrink:0; }

    .sidebar-toggle { display:none; position:fixed; top:14px; left:14px; z-index:200; width:42px; height:42px; border-radius:12px; border:1px solid var(--border-color); background:var(--card); box-shadow:0 4px 16px rgba(0,0,0,.3); cursor:pointer; align-items:center; justify-content:center; }
    .sidebar-toggle svg { width:20px; height:20px; color:var(--accent); }
    .sidebar-overlay { position:fixed; inset:0; background:rgba(9,12,16,.65); backdrop-filter:blur(3px); z-index:165; opacity:0; visibility:hidden; transition:opacity var(--trans), visibility var(--trans); }
    .sidebar-overlay.open { opacity:1; visibility:visible; }

    /* ── MAIN CONTENT ── */
    .main {
      margin-left:var(--sidebar-w); flex:1; min-width:0; padding:32px 36px 60px;
      display:flex; flex-direction:column; align-items:center;
    }
    .container { width:100%; max-width:980px; }

    /* Breadcrumbs & Header */
    .header-nav { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; gap:16px; flex-wrap:wrap; }
    .back-link { display:inline-flex; align-items:center; gap:8px; font-size:.82rem; font-weight:600; color:var(--muted); text-decoration:none; padding:8px 14px; border-radius:8px; border:1px solid var(--card-border); background:var(--card); transition:all var(--trans); }
    .back-link:hover { color:var(--accent); border-color:var(--accent); transform:translateX(-3px); }
    .back-link svg { width:16px; height:16px; }

    .page-title-wrap { margin-bottom:28px; }
    .page-badge { display:inline-flex; align-items:center; gap:6px; font-size:.68rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; color:var(--accent); background:rgba(216,184,120,.12); padding:4px 12px; border-radius:20px; border:1px solid rgba(216,184,120,.25); margin-bottom:8px; }
    .page-title { font-family:'Cormorant Garamond',serif; font-size:2.2rem; font-weight:700; color:var(--text); line-height:1.15; }
    .page-subtitle { font-size:.85rem; color:var(--muted); margin-top:4px; }

    /* Alerts */
    .alert { display:flex; align-items:flex-start; gap:12px; padding:16px 18px; border-radius:12px; margin-bottom:24px; font-size:.85rem; line-height:1.5; }
    .alert-error { background:rgba(220,38,38,.12); border:1px solid rgba(220,38,38,.3); color:#fca5a5; }
    .alert-success { background:rgba(5,150,105,.12); border:1px solid rgba(5,150,105,.3); color:#86efac; }
    .alert svg { width:20px; height:20px; flex-shrink:0; margin-top:1px; }

    /* Layout 2 Kolom */
    .form-grid { display:grid; grid-template-columns:340px 1fr; gap:26px; align-items:start; }

    /* Card Box */
    .box-card {
      background:var(--card); border:1px solid var(--card-border);
      border-radius:var(--radius); padding:24px; box-shadow:var(--shadow);
    }
    .box-title {
      font-size:.92rem; font-weight:700; color:var(--text); margin-bottom:16px;
      display:flex; align-items:center; justify-content:space-between;
      border-bottom:1px solid var(--card-border); padding-bottom:12px;
    }
    .box-title span { display:flex; align-items:center; gap:8px; }
    .box-title svg { width:17px; height:17px; color:var(--accent); }

    /* Book Summary Preview */
    .book-preview { display:flex; flex-direction:column; align-items:center; text-align:center; }
    .book-cover-box {
      width:150px; aspect-ratio:3/4; border-radius:10px; overflow:hidden;
      background:#10151b; border:1px solid var(--border-color);
      box-shadow:0 12px 30px rgba(0,0,0,.5); margin-bottom:16px; position:relative;
    }
    .book-cover-box img { width:100%; height:100%; object-fit:cover; display:block; }
    .book-cover-placeholder { width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:var(--muted); }
    .book-cover-placeholder svg { width:44px; height:44px; color:rgba(216,184,120,.3); }
    .book-preview-title { font-family:'Cormorant Garamond',serif; font-size:1.35rem; font-weight:700; color:var(--text); line-height:1.25; margin-bottom:4px; }
    .book-preview-author { font-size:.8rem; color:var(--muted); margin-bottom:14px; }
    
    .book-meta-chips { display:flex; flex-wrap:wrap; gap:8px; justify-content:center; margin-bottom:16px; width:100%; }
    .book-chip { font-size:.7rem; padding:4px 10px; border-radius:6px; background:var(--book-card); border:1px solid var(--card-border); color:var(--muted); }
    .book-chip.stock-ok { color:#4ade80; border-color:rgba(74,222,128,.3); background:rgba(74,222,128,.08); font-weight:700; }
    .book-chip.stock-low { color:#f87171; border-color:rgba(248,113,113,.3); background:rgba(248,113,113,.08); font-weight:700; }

    .btn-change-book {
      width:100%; padding:9px 14px; border-radius:9px; border:1px dashed var(--border-color);
      background:rgba(216,184,120,.05); color:var(--accent); font-size:.78rem; font-weight:700;
      cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:6px;
      transition:all var(--trans);
    }
    .btn-change-book:hover { background:rgba(216,184,120,.12); border-style:solid; }

    /* Form Fields */
    .form-group { margin-bottom:20px; }
    .form-group:last-child { margin-bottom:0; }
    .form-label { display:block; font-size:.78rem; font-weight:700; color:var(--text); margin-bottom:8px; }
    .form-label span.req { color:#f87171; }
    .form-label-desc { font-size:.72rem; color:var(--muted); font-weight:400; margin-top:2px; }

    .form-input {
      width:100%; padding:11px 14px; border-radius:10px; border:1px solid var(--card-border);
      background:var(--book-card); color:var(--text); font-family:inherit; font-size:.85rem;
      outline:none; transition:border-color var(--trans), box-shadow var(--trans);
    }
    .form-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(216,184,120,.12); }
    .form-input[readonly] { background:rgba(255,255,255,.03); border-color:rgba(255,255,255,.08); cursor:not-allowed; color:var(--muted); }

    .user-info-row { display:flex; align-items:center; gap:10px; margin-top:8px; }
    .user-badge-chip { font-size:.7rem; font-weight:600; padding:4px 9px; border-radius:6px; background:rgba(216,184,120,.1); color:var(--accent); border:1px solid rgba(216,184,120,.2); }

    /* Upload Card Area */
    .upload-area {
      position:relative; border:2px dashed var(--border-color); border-radius:12px;
      padding:22px 18px; text-align:center; background:rgba(216,184,120,.03);
      cursor:pointer; transition:all var(--trans); overflow:hidden;
    }
    .upload-area:hover, .upload-area.dragover { border-color:var(--accent); background:rgba(216,184,120,.08); }
    .upload-area.has-file { padding:10px; border-style:solid; border-color:var(--accent); background:rgba(216,184,120,.05); }
    .upload-area.has-error { border-color:#f87171 !important; background:rgba(220,38,38,.06) !important; animation:shake .3s ease; }
    @keyframes shake { 0%,100%{transform:translateX(0)} 25%{transform:translateX(-4px)} 75%{transform:translateX(4px)} }

    .upload-icon { width:44px; height:44px; border-radius:50%; background:rgba(216,184,120,.12); color:var(--accent); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; }
    .upload-icon svg { width:22px; height:22px; }
    .upload-text-main { font-size:.85rem; font-weight:700; color:var(--text); margin-bottom:4px; }
    .upload-text-sub { font-size:.72rem; color:var(--muted); }
    
    .btn-browse-file {
      display:inline-flex; align-items:center; gap:6px; margin-top:10px;
      padding:7px 16px; border-radius:8px; border:1px solid var(--border-color);
      background:rgba(216,184,120,.12); color:var(--accent); font-family:inherit;
      font-size:.76rem; font-weight:700; cursor:pointer; transition:all var(--trans);
    }
    .btn-browse-file:hover { background:var(--accent); color:#090c10; }

    /* Upload Preview */
    .upload-preview-wrap { display:none; position:relative; border-radius:10px; overflow:hidden; border:1px solid var(--border-color); background:#0c0f14; }
    .upload-preview-img { width:100%; max-height:220px; object-fit:contain; display:block; background:#000; }
    .upload-preview-info { padding:10px 14px; display:flex; align-items:center; justify-content:space-between; background:var(--card); font-size:.75rem; border-top:1px solid var(--card-border); }
    .btn-remove-file {
      background:rgba(216,184,120,.15); color:var(--accent); border:1px solid var(--border-color);
      border-radius:6px; padding:6px 12px; font-size:.72rem; font-weight:700; cursor:pointer;
      display:inline-flex; align-items:center; gap:5px; transition:all var(--trans);
    }
    .btn-remove-file:hover { background:var(--accent); color:#090c10; }

    .help-hint { font-size:.75rem; color:var(--muted); margin-top:8px; display:flex; align-items:center; gap:6px; }
    .help-hint a { color:var(--accent); font-weight:600; text-decoration:none; transition:text-decoration .15s; }
    .help-hint a:hover { text-decoration:underline; }

    /* Quantity stepper */
    .stepper-wrap { display:flex; align-items:center; gap:8px; max-width:170px; }
    .btn-step {
      width:38px; height:38px; border-radius:8px; border:1px solid var(--card-border);
      background:var(--book-card); color:var(--text); font-size:1.1rem; font-weight:700;
      cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all var(--trans);
    }
    .btn-step:hover:not(:disabled) { border-color:var(--accent); color:var(--accent); background:rgba(216,184,120,.1); }
    .btn-step:disabled { opacity:.4; cursor:not-allowed; }
    .step-input {
      flex:1; text-align:center; padding:9px 6px; border-radius:8px;
      border:1px solid var(--card-border); background:var(--book-card);
      color:var(--text); font-size:.9rem; font-weight:700;
    }

    /* Pickup options (sekarang vs nanti) */
    .pickup-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .pickup-card {
      position:relative; border:1.5px solid var(--card-border); border-radius:12px;
      padding:14px 16px; background:var(--book-card); cursor:pointer;
      display:flex; flex-direction:column; gap:6px; transition:all var(--trans); user-select:none;
    }
    .pickup-card:hover { border-color:rgba(216,184,120,.35); transform:translateY(-1px); }
    .pickup-card.selected {
      border-color:var(--accent); background:rgba(216,184,120,.09);
      box-shadow:0 4px 20px rgba(216,184,120,.12);
    }
    .pickup-card input[type="radio"] { position:absolute; opacity:0; pointer-events:none; }
    .pickup-card-head { display:flex; align-items:center; justify-content:space-between; }
    .pickup-card-title { font-size:.82rem; font-weight:700; color:var(--text); display:flex; align-items:center; gap:6px; }
    .pickup-card-title svg { width:16px; height:16px; color:var(--accent); }
    .pickup-radio-dot {
      width:16px; height:16px; border-radius:50%; border:1.5px solid var(--card-border);
      display:flex; align-items:center; justify-content:center; transition:border-color var(--trans);
    }
    .pickup-card.selected .pickup-radio-dot { border-color:var(--accent); }
    .pickup-card.selected .pickup-radio-dot::after {
      content:''; width:8px; height:8px; border-radius:50%; background:var(--accent);
    }
    .pickup-card-desc { font-size:.7rem; color:var(--muted); line-height:1.4; }

    /* Nanti Details Box */
    .nanti-details-box {
      display:none; margin-top:14px; padding:16px 18px; border-radius:12px;
      background:rgba(216,184,120,.06); border:1px solid var(--border-color);
      animation:fadeIn .25s ease both;
    }
    @keyframes fadeIn { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:translateY(0)} }
    .nanti-details-title {
      font-size:.82rem; font-weight:700; color:var(--accent); margin-bottom:12px;
      display:flex; align-items:center; gap:8px;
    }
    .nanti-details-title svg { width:16px; height:16px; }
    .form-sub-label {
      display:block; font-size:.74rem; font-weight:700; color:var(--text); margin-bottom:6px;
    }
    .form-sub-label span.req { color:#f87171; }
    .quick-jam-chips { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
    .chip-jam {
      font-size:.68rem; font-weight:600; padding:4px 9px; border-radius:6px;
      background:var(--book-card); border:1px solid var(--card-border); color:var(--muted);
      cursor:pointer; transition:all var(--trans);
    }
    .chip-jam:hover, .chip-jam.active {
      border-color:var(--accent); color:var(--accent); background:rgba(216,184,120,.12);
    }

    /* Submit Button */
    .btn-submit-loan {
      width:100%; padding:14px 20px; border-radius:12px; border:none;
      background:linear-gradient(135deg, #d8b878 0%, #b89758 100%);
      color:#090c10; font-family:inherit; font-size:.9rem; font-weight:800;
      letter-spacing:.02em; cursor:pointer; display:inline-flex; align-items:center;
      justify-content:center; gap:10px; box-shadow:0 6px 24px rgba(216,184,120,.35);
      transition:transform var(--trans), box-shadow var(--trans); margin-top:28px;
    }
    .btn-submit-loan:hover { transform:translateY(-2px); box-shadow:0 10px 30px rgba(216,184,120,.5); }
    .btn-submit-loan:active { transform:translateY(0); }
    .btn-submit-loan svg { width:18px; height:18px; }

    /* Modal Ganti Buku */
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.7); backdrop-filter:blur(5px); z-index:300; align-items:center; justify-content:center; padding:20px; }
    .modal-overlay.open { display:flex; }
    .modal-box { width:100%; max-width:540px; max-height:85vh; background:var(--card); border:1px solid var(--border-color); border-radius:16px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 24px 70px rgba(0,0,0,.6); }
    .modal-header { padding:18px 20px; border-bottom:1px solid var(--card-border); display:flex; align-items:center; justify-content:space-between; }
    .modal-title { font-size:1rem; font-weight:700; color:var(--text); }
    .modal-close { width:32px; height:32px; border-radius:50%; background:rgba(255,255,255,.05); border:1px solid var(--card-border); color:var(--muted); display:flex; align-items:center; justify-content:center; cursor:pointer; }
    .modal-close:hover { color:var(--text); background:rgba(255,255,255,.1); }
    .modal-search { padding:14px 20px; border-bottom:1px solid var(--card-border); }
    .modal-body { flex:1; overflow-y:auto; padding:12px 20px 20px; display:flex; flex-direction:column; gap:8px; }
    .book-picker-item { display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:10px; border:1px solid var(--card-border); background:var(--book-card); cursor:pointer; transition:all var(--trans); }
    .book-picker-item:hover { border-color:var(--accent); background:rgba(216,184,120,.08); }
    .book-picker-item.active { border-color:var(--accent); background:rgba(216,184,120,.14); }
    .book-picker-cover { width:42px; height:56px; border-radius:6px; background:#10151b; overflow:hidden; flex-shrink:0; border:1px solid var(--card-border); }
    .book-picker-cover img { width:100%; height:100%; object-fit:cover; }
    .book-picker-info { flex:1; min-width:0; }
    .book-picker-title { font-size:.82rem; font-weight:700; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .book-picker-author { font-size:.72rem; color:var(--muted); }
    .book-picker-stock { font-size:.68rem; font-weight:700; color:#4ade80; padding:2px 6px; border-radius:4px; background:rgba(74,222,128,.1); }

    /* Success Screen */
    .success-card { text-align:center; padding:44px 28px; }
    .success-icon { width:70px; height:70px; border-radius:50%; background:rgba(74,222,128,.15); color:#4ade80; border:2px solid rgba(74,222,128,.3); display:flex; align-items:center; justify-content:center; margin:0 auto 20px; }
    .success-icon svg { width:36px; height:36px; }
    .success-title { font-family:'Cormorant Garamond',serif; font-size:1.8rem; font-weight:700; color:var(--text); margin-bottom:8px; }
    .success-desc { font-size:.88rem; color:var(--muted); max-width:440px; margin:0 auto 24px; line-height:1.6; }
    .success-actions { display:flex; justify-content:center; gap:12px; flex-wrap:wrap; }
    .btn-action-sec { padding:10px 18px; border-radius:10px; border:1px solid var(--border-color); background:var(--card); color:var(--text); font-weight:600; font-size:.82rem; text-decoration:none; transition:all var(--trans); }
    .btn-action-sec:hover { border-color:var(--accent); color:var(--accent); }
    .btn-action-pri { padding:10px 20px; border-radius:10px; border:none; background:var(--accent); color:#090c10; font-weight:700; font-size:.82rem; text-decoration:none; transition:all var(--trans); }
    .btn-action-pri:hover { opacity:.9; transform:translateY(-1px); }

    /* Responsive */
    @media (max-width:880px) {
      .form-grid { grid-template-columns:1fr; }
      .book-preview { flex-direction:row; text-align:left; gap:16px; align-items:center; }
      .book-cover-box { width:90px; margin-bottom:0; flex-shrink:0; }
      .book-meta-chips { justify-content:flex-start; }
      .main { margin-left:0; padding:70px 16px 40px; }
      .sidebar { transform:translateX(-100%); }
      .sidebar.open { transform:translateX(0); }
      .sidebar-toggle { display:flex; }
    }
    @media (max-width:520px) {
      .pickup-grid { grid-template-columns:1fr; }
      .page-title { font-size:1.7rem; }
      .book-preview { flex-direction:column; text-align:center; }
      .book-cover-box { width:120px; margin-bottom:12px; }
      .book-meta-chips { justify-content:center; }
    }
  </style>
  <?php require_once 'settings_include.php'; ?>
</head>
<body>

<button class="sidebar-toggle" id="sidebarToggle" aria-label="Buka Menu">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <line x1="3" y1="6" x2="21" y2="6"/>
    <line x1="3" y1="12" x2="21" y2="12"/>
    <line x1="3" y1="18" x2="21" y2="18"/>
  </svg>
</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar Navigasi -->
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
    <a href="dashboard_user.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
      Dashboard
    </a>
    <a href="daftar_buku.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
      Daftar Buku
    </a>
    <a href="pengajuan_peminjaman.php" class="nav-item active">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
      Ajukan Pinjam
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
    <a href="#" class="nav-item" onclick="bukaSettings(); return false;">
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
  <div class="container">
    
    <div class="header-nav">
      <a href="daftar_buku.php" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali ke Katalog Buku
      </a>
      <a href="dashboard_user.php" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
        Lihat Riwayat di Dashboard
      </a>
    </div>

    <div class="page-title-wrap">
      <div class="page-badge">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        Layanan Sirkulasi Mandiri
      </div>
      <h1 class="page-title">Formulir Pengajuan Peminjaman</h1>
      <p class="page-subtitle">Ajukan peminjaman buku perpustakaan secara online dan verifikasi dengan kartu anggota resmi kamu.</p>
    </div>

    <?php if ($error_msg !== ''): ?>
      <div class="alert alert-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div><?= htmlspecialchars($error_msg) ?></div>
      </div>
    <?php endif; ?>

    <?php if ($success_msg !== ''): ?>
      <div class="box-card success-card">
        <div class="success-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <h2 class="success-title">Pengajuan Berhasil Dikirim!</h2>
        <p class="success-desc">
          <?= htmlspecialchars($success_msg) ?><br>
          Petugas perpustakaan akan memeriksa permohonan peminjaman kamu. Setelah disetujui, buku dapat kamu ambil sesuai waktu yang kamu pilih.
        </p>
        <div class="success-actions">
          <a href="dashboard_user.php" class="btn-action-pri">Buka Dashboard Saya</a>
          <a href="daftar_buku.php" class="btn-action-sec">Jelajahi Buku Lain</a>
        </div>
      </div>
    <?php else: ?>

      <form method="POST" action="pengajuan_peminjaman.php" enctype="multipart/form-data" id="formPengajuan">
        <input type="hidden" name="action" value="ajukan">
        <input type="hidden" name="buku_id" id="inputBukuId" value="<?= (int)($buku_terpilih['id'] ?? 0) ?>">

        <div class="form-grid">
          
          <!-- Kolom Kiri: Ringkasan Buku Terpilih -->
          <div class="box-card">
            <div class="box-title">
              <span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                Buku yang Dipilih
              </span>
            </div>

            <?php if ($buku_terpilih): ?>
              <div class="book-preview" id="bookPreviewCard">
                <div class="book-cover-box">
                  <?php if (!empty($buku_terpilih['gambar']) && file_exists(__DIR__ . '/' . $buku_terpilih['gambar'])): ?>
                    <img src="<?= htmlspecialchars($buku_terpilih['gambar']) ?>" alt="<?= htmlspecialchars($buku_terpilih['judul']) ?>">
                  <?php else: ?>
                    <div class="book-cover-placeholder">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="book-preview-title" id="previewJudul"><?= htmlspecialchars($buku_terpilih['judul']) ?></div>
                <div class="book-preview-author" id="previewPenulis">✍️ <?= htmlspecialchars($buku_terpilih['penulis'] ?: 'Penulis tidak diketahui') ?></div>

                <div class="book-meta-chips">
                  <?php if (!empty($buku_terpilih['genre'])): ?>
                    <div class="book-chip" id="previewGenre"><?= htmlspecialchars($buku_terpilih['genre']) ?></div>
                  <?php endif; ?>
                  <?php if (!empty($buku_terpilih['isbn'])): ?>
                    <div class="book-chip" id="previewIsbn">ISBN: <?= htmlspecialchars($buku_terpilih['isbn']) ?></div>
                  <?php endif; ?>
                  <div class="book-chip <?= (int)$buku_terpilih['stok'] > 0 ? 'stock-ok' : 'stock-low' ?>" id="previewStok">
                    Sisa Stok: <?= (int)$buku_terpilih['stok'] ?> buku
                  </div>
                </div>

                <button type="button" class="btn-change-book" onclick="bukaModalPilihBuku()">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 4v6h6M23 20v-6h-6"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>
                  Ganti Pilihan Buku
                </button>
              </div>
            <?php else: ?>
              <div style="text-align:center; padding:30px 10px; color:var(--muted);">
                <p style="font-size:.85rem; margin-bottom:14px;">Belum ada buku yang dipilih atau stok semua buku sedang kosong.</p>
                <button type="button" class="btn-change-book" onclick="bukaModalPilihBuku()">Pilih Buku dari Katalog</button>
              </div>
            <?php endif; ?>
          </div>

          <!-- Kolom Kanan: Formulir Pemohon -->
          <div class="box-card">
            <div class="box-title">
              <span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Data Peminjam & Jadwal
              </span>
            </div>

            <!-- 1. Nama Peminjam (Auto-filled dari sesi) -->
            <div class="form-group">
              <label class="form-label">
                Nama Lengkap Peminjam <span class="req">*</span>
                <span class="form-label-desc">Otomatis terisi dari identitas akun anggota kamu</span>
              </label>
              <input type="text" class="form-input" value="<?= htmlspecialchars($user['full_name']) ?>" readonly>
              <div class="user-info-row">
                <span class="user-badge-chip">Kelas: <?= htmlspecialchars($user['kelas'] ?: 'Umum') ?></span>
                <span class="user-badge-chip">No. Anggota: <?= htmlspecialchars($user['no_anggota'] ?: '—') ?></span>
              </div>
            </div>

            <!-- 2. Upload Kartu Anggota -->
            <div class="form-group">
              <label class="form-label">
                Unggah Kartu Anggota (Card) <span class="req">*</span>
                <span class="form-label-desc">Lampirkan foto/gambar kartu anggota resmi kamu sebagai bukti keanggotaan aktif</span>
              </label>
              
              <!-- Hidden input file yang tidak memakai display:none agar tidak memblokir form submission -->
              <input type="file" name="file_kartu" id="fileKartu" accept=".jpg,.jpeg,.png,.webp,image/*" 
                     style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;pointer-events:none;" 
                     onchange="handleFileSelect(this)">

              <div class="upload-area" id="uploadArea" onclick="document.getElementById('fileKartu').click();">
                <div id="uploadPrompt">
                  <div class="upload-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                  </div>
                  <div class="upload-text-main" id="uploadMainText">Pilih atau Seret Foto Kartu ke Sini</div>
                  <div class="upload-text-sub">Mendukung format PNG, JPG, JPEG, atau WEBP (Maks. 5MB)</div>
                  <button type="button" class="btn-browse-file" onclick="event.stopPropagation(); document.getElementById('fileKartu').click();">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    Pilih File Gambar
                  </button>
                </div>

                <div class="upload-preview-wrap" id="uploadPreviewWrap" onclick="event.stopPropagation();">
                  <img src="" alt="Pratinjau Kartu" id="uploadPreviewImg" class="upload-preview-img">
                  <div class="upload-preview-info">
                    <div style="text-align:left;">
                      <div id="uploadFileName" style="font-weight:700;color:var(--text);font-size:.8rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">kartu-anggota.png</div>
                      <div id="uploadFileSize" style="font-size:.68rem;color:var(--muted);margin-top:2px;">0 KB</div>
                    </div>
                    <button type="button" class="btn-remove-file" onclick="event.stopPropagation(); gantiFileKartu();">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 4v6h6M23 20v-6h-6"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>
                      Ganti Foto
                    </button>
                  </div>
                </div>
              </div>

              <div class="help-hint">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>Belum punya kartu? <a href="kartu_anggota.php" target="_blank" rel="noopener">Buka kartu kamu di sini</a> untuk melihat dan mengunduh gambar kartu.</span>
              </div>
            </div>

            <!-- 3. Total Buku & Batas Pengembalian -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
              <div class="form-group">
                <label class="form-label">
                  Total Buku Dipinjam <span class="req">*</span>
                  <span class="form-label-desc">Maksimal 3 eksemplar</span>
                </label>
                <div class="stepper-wrap">
                  <button type="button" class="btn-step" onclick="ubahJumlah(-1)">−</button>
                  <input type="number" name="total_buku" id="inputTotalBuku" class="step-input" value="1" min="1" max="<?= max(1, min(3, (int)($buku_terpilih['stok'] ?? 3))) ?>" readonly>
                  <button type="button" class="btn-step" onclick="ubahJumlah(1)">+</button>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">
                  Batas Pengembalian <span class="req">*</span>
                  <span class="form-label-desc">Tanggal wajib kembali</span>
                </label>
                <input type="date" name="batas_kembali" id="inputBatasKembali" class="form-input" 
                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>" 
                       value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
              </div>
            </div>

            <!-- 4. Kapan Pengambilan Buku (Sekarang / Nanti) -->
            <div class="form-group">
              <label class="form-label">
                Kapan Pengambilan Buku ke Perpustakaan? <span class="req">*</span>
                <span class="form-label-desc">Tentukan rencana kehadiran kamu ke perpustakaan</span>
              </label>

              <div class="pickup-grid">
                <div class="pickup-card selected" id="pickupCardSekarang" onclick="pilihWaktuPengambilan('sekarang')">
                  <input type="radio" name="waktu_pengambilan" value="sekarang" id="radioSekarang" checked>
                  <div class="pickup-card-head">
                    <div class="pickup-card-title">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                      Ambil Sekarang
                    </div>
                    <div class="pickup-radio-dot"></div>
                  </div>
                  <div class="pickup-card-desc">Saya sudah berada di perpustakaan atau akan mengambil buku hari ini.</div>
                </div>

                <div class="pickup-card" id="pickupCardNanti" onclick="pilihWaktuPengambilan('nanti')">
                  <input type="radio" name="waktu_pengambilan" value="nanti" id="radioNanti">
                  <div class="pickup-card-head">
                    <div class="pickup-card-title">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                      Ambil Nanti
                    </div>
                    <div class="pickup-radio-dot"></div>
                  </div>
                  <div class="pickup-card-desc">Saya akan mengambil buku nanti saat jam luang / hari berikutnya.</div>
                </div>
              </div>

              <!-- Input Rencana Detail Jika Ambil Nanti -->
              <div id="catatanNantiWrap" class="nanti-details-box">
                <div class="nanti-details-title">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                  Rencana Jadwal Pengambilan Buku
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                  <div>
                    <label class="form-sub-label">Tanggal Pengambilan <span class="req">*</span></label>
                    <input type="date" name="tanggal_ambil_nanti" id="inputTanggalAmbil" class="form-input" 
                           min="<?= date('Y-m-d') ?>" 
                           value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                  </div>
                  <div>
                    <label class="form-sub-label">Pilihan Waktu / Jam</label>
                    <select name="jam_ambil_pilihan" id="selectJamAmbil" class="form-input">
                      <option value="Istirahat ke-1 (09:45 - 10:15 WIB)">Istirahat ke-1 (09:45 - 10:15 WIB)</option>
                      <option value="Istirahat ke-2 (12:00 - 12:45 WIB)" selected>Istirahat ke-2 (12:00 - 12:45 WIB)</option>
                      <option value="Sepulang Sekolah (15:00 - 16:00 WIB)">Sepulang Sekolah (15:00 - 16:00 WIB)</option>
                      <option value="Pagi sebelum Bel (06:45 - 07:15 WIB)">Pagi sebelum Bel (06:45 - 07:15 WIB)</option>
                      <option value="Lainnya (Tulis di Catatan)">Lainnya (Tulis di Catatan)</option>
                    </select>
                  </div>
                </div>

                <div>
                  <label class="form-sub-label">Catatan Tambahan (Opsional)</label>
                  <input type="text" name="catatan_pengambilan" id="inputCatatanAmbil" class="form-input" 
                         placeholder="Contoh: Titip ke petugas meja 2, atau akan diambil oleh teman sekelas">
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-submit-loan" id="btnSubmit">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
              Kirim Pengajuan Peminjaman
            </button>

          </div>

        </div>
      </form>
    <?php endif; ?>

  </div>
</main>

<!-- Modal Pilih / Ganti Buku -->
<div class="modal-overlay" id="modalPilihBuku" onclick="if(event.target === this) tutupModalPilihBuku()">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-title">Pilih Buku yang Ingin Dipinjam</div>
      <button type="button" class="modal-close" onclick="tutupModalPilihBuku()">✕</button>
    </div>
    <div class="modal-search">
      <input type="text" id="cariBukuModal" class="form-input" placeholder="Ketik judul, penulis, atau kategori…" oninput="filterBukuModal(this.value)">
    </div>
    <div class="modal-body" id="modalBukuList">
      <?php foreach ($daftar_buku_tersedia as $b): ?>
        <div class="book-picker-item <?= $b['id'] == $selected_buku_id ? 'active' : '' ?>" 
             data-id="<?= (int)$b['id'] ?>"
             data-judul="<?= htmlspecialchars($b['judul'], ENT_QUOTES) ?>"
             data-penulis="<?= htmlspecialchars($b['penulis'] ?: 'Penulis tidak diketahui', ENT_QUOTES) ?>"
             data-genre="<?= htmlspecialchars($b['genre'] ?: '', ENT_QUOTES) ?>"
             data-stok="<?= (int)$b['stok'] ?>"
             data-gambar="<?= htmlspecialchars($b['gambar'] ?: '', ENT_QUOTES) ?>"
             onclick="pilihBukuDariModal(this)">
          <div class="book-picker-cover">
            <?php if (!empty($b['gambar']) && file_exists(__DIR__ . '/' . $b['gambar'])): ?>
              <img src="<?= htmlspecialchars($b['gambar']) ?>" alt="<?= htmlspecialchars($b['judul']) ?>">
            <?php else: ?>
              <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:rgba(216,184,120,.4);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
              </div>
            <?php endif; ?>
          </div>
          <div class="book-picker-info">
            <div class="book-picker-title"><?= htmlspecialchars($b['judul']) ?></div>
            <div class="book-picker-author"><?= htmlspecialchars($b['penulis'] ?: 'Penulis tidak diketahui') ?></div>
          </div>
          <div class="book-picker-stock">Stok: <?= (int)$b['stok'] ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
  // ─── Sidebar Toggle Mobile ───
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');

  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      sidebarOverlay.classList.toggle('open');
    });
  }
  if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', () => {
      sidebar.classList.remove('open');
      sidebarOverlay.classList.remove('open');
    });
  }

  // ─── Upload Kartu Image Preview & Drag-and-Drop ───
  const uploadArea = document.getElementById('uploadArea');
  const fileInput  = document.getElementById('fileKartu');

  function handleFileSelect(input) {
    if (input.files && input.files[0]) {
      const file = input.files[0];
      const fileName = file.name.toLowerCase();
      const validExts = ['.jpg', '.jpeg', '.png', '.webp'];
      const isExtValid = validExts.some(ext => fileName.endsWith(ext));

      if (!isExtValid && file.type && !file.type.startsWith('image/')) {
        alert('Harap pilih file gambar dengan format JPG, JPEG, PNG, atau WEBP.');
        input.value = '';
        return;
      }

      if (file.size > 5 * 1024 * 1024) {
        alert('Ukuran gambar kartu terlalu besar (maksimal 5MB).');
        input.value = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('uploadPreviewImg').src = e.target.result;
        document.getElementById('uploadFileName').textContent = file.name;
        
        // Format filesize
        const kb = (file.size / 1024).toFixed(1);
        const mb = (file.size / (1024 * 1024)).toFixed(2);
        document.getElementById('uploadFileSize').textContent = file.size > 1024 * 1024 ? mb + ' MB' : kb + ' KB';

        document.getElementById('uploadPrompt').style.display = 'none';
        document.getElementById('uploadPreviewWrap').style.display = 'block';
        uploadArea.classList.add('has-file');
        uploadArea.classList.remove('has-error');
      };
      reader.readAsDataURL(file);
    }
  }

  function gantiFileKartu() {
    fileInput.click();
  }

  // Setup Drag & Drop
  if (uploadArea && fileInput) {
    ['dragenter', 'dragover'].forEach(evt => {
      uploadArea.addEventListener(evt, (e) => {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.classList.add('dragover');
      });
    });

    ['dragleave', 'dragend'].forEach(evt => {
      uploadArea.addEventListener(evt, (e) => {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.classList.remove('dragover');
      });
    });

    uploadArea.addEventListener('drop', (e) => {
      e.preventDefault();
      e.stopPropagation();
      uploadArea.classList.remove('dragover');
      if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        handleFileSelect(fileInput);
      }
    });
  }

  // Form Submit Validation
  const formPengajuan = document.getElementById('formPengajuan');
  if (formPengajuan) {
    formPengajuan.addEventListener('submit', function(e) {
      if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        e.preventDefault();
        uploadArea.classList.add('has-error');
        uploadArea.scrollIntoView({ behavior: 'smooth', block: 'center' });
        alert('Foto/gambar kartu anggota wajib diunggah sebagai bukti validasi.');
        return false;
      }
      const btn = document.getElementById('btnSubmit');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner" style="width:16px;height:16px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:8px;"></span> Mengirim Pengajuan...`;
      }
    });
  }

  // ─── Stepper Jumlah Buku ───
  function ubahJumlah(delta) {
    const inp = document.getElementById('inputTotalBuku');
    const minVal = parseInt(inp.min) || 1;
    const maxVal = parseInt(inp.max) || 3;
    let currVal = parseInt(inp.value) || 1;
    currVal += delta;
    if (currVal < minVal) currVal = minVal;
    if (currVal > maxVal) currVal = maxVal;
    inp.value = currVal;
  }

  // ─── Radio Pilihan Waktu Pengambilan ───
  function pilihWaktuPengambilan(tipe) {
    const radio = document.querySelector(`input[name="waktu_pengambilan"][value="${tipe}"]`);
    if (radio) radio.checked = true;

    const cardSekarang = document.getElementById('pickupCardSekarang');
    const cardNanti = document.getElementById('pickupCardNanti');
    const wrapCatatan = document.getElementById('catatanNantiWrap');

    if (tipe === 'sekarang') {
      cardSekarang.classList.add('selected');
      cardNanti.classList.remove('selected');
      wrapCatatan.style.display = 'none';
    } else {
      cardNanti.classList.add('selected');
      cardSekarang.classList.remove('selected');
      wrapCatatan.style.display = 'block';
      const tglInput = document.getElementById('inputTanggalAmbil');
      if (tglInput) tglInput.focus();
    }
  }

  // ─── Modal Ganti Buku ───
  function bukaModalPilihBuku() {
    document.getElementById('modalPilihBuku').classList.add('open');
    document.getElementById('cariBukuModal').value = '';
    filterBukuModal('');
  }

  function tutupModalPilihBuku() {
    document.getElementById('modalPilihBuku').classList.remove('open');
  }

  function filterBukuModal(query) {
    query = query.toLowerCase().trim();
    const items = document.querySelectorAll('#modalBukuList .book-picker-item');
    items.forEach(it => {
      const text = (it.dataset.judul + ' ' + it.dataset.penulis + ' ' + it.dataset.genre).toLowerCase();
      it.style.display = text.includes(query) ? 'flex' : 'none';
    });
  }

  function pilihBukuDariModal(el) {
    const id = el.dataset.id;
    const judul = el.dataset.judul;
    const penulis = el.dataset.penulis;
    const genre = el.dataset.genre;
    const stok = parseInt(el.dataset.stok) || 1;
    const gambar = el.dataset.gambar;

    // Update input hidden
    document.getElementById('inputBukuId').value = id;

    // Update card display
    const prevJudul = document.getElementById('previewJudul');
    if (prevJudul) prevJudul.textContent = judul;
    const prevPenulis = document.getElementById('previewPenulis');
    if (prevPenulis) prevPenulis.textContent = '✍️ ' + penulis;
    const prevGenre = document.getElementById('previewGenre');
    if (prevGenre) prevGenre.textContent = genre;
    const prevStok = document.getElementById('previewStok');
    if (prevStok) {
      prevStok.textContent = 'Sisa Stok: ' + stok + ' buku';
      prevStok.className = 'book-chip ' + (stok > 0 ? 'stock-ok' : 'stock-low');
    }

    // Cover
    const coverBox = document.querySelector('.book-cover-box');
    if (coverBox) {
      if (gambar) {
        coverBox.innerHTML = `<img src="${gambar}" alt="${judul}">`;
      } else {
        coverBox.innerHTML = `<div class="book-cover-placeholder"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>`;
      }
    }

    // Update max stepper
    const stepInp = document.getElementById('inputTotalBuku');
    if (stepInp) {
      const maxAllowed = Math.min(3, stok);
      stepInp.max = Math.max(1, maxAllowed);
      if (parseInt(stepInp.value) > maxAllowed) stepInp.value = Math.max(1, maxAllowed);
    }

    // Highlight item di modal
    document.querySelectorAll('#modalBukuList .book-picker-item').forEach(it => it.classList.remove('active'));
    el.classList.add('active');

    tutupModalPilihBuku();
  }

  // Audio Latar
  if (window.AksaAudio) window.AksaAudio.init();
</script>

<?php require_once 'pengaturan_panel.php'; ?>
</body>
</html>