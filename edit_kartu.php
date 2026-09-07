<?php
// edit_kartu.php — Update Data Diri (nama, kelas, no HP, email, foto) untuk anggota yang SUDAH LOGIN
// ── PERUBAHAN: akses dipindah dari halaman Sign In ke Beranda (kartu "Profil Saya") ──
// Karena diakses dari dalam dashboard, halaman ini langsung memakai sesi login yang sedang aktif
// (tidak perlu lagi memasukkan ulang email & password, dan TIDAK ADA LAGI konfirmasi kata sandi
// saat ini sebelum menyimpan — sesi login yang aktif sudah cukup sebagai otorisasi).
// Username, NIK, dan nomor anggota TIDAK bisa diubah lewat halaman ini. Ganti kata sandi baru
// tetap opsional (kosongkan jika tidak ingin mengganti).
session_start();
require_once "db.php";

// ─── Halaman ini khusus anggota (member) yang sudah login ───
if (!isset($_SESSION["user_id"])) {
    header("Location: sign_in.php");
    exit;
}
if (($_SESSION["role"] ?? "") !== "member") {
    header("Location: beranda.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

// ─── Ambil data terbaru milik akun yang sedang login ───
$stmt = mysqli_prepare($conn,
    "SELECT id, full_name, nik, kelas, no_hp, email, no_anggota, username, password, foto, role, status, card_status
     FROM users WHERE id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    session_destroy();
    header("Location: sign_in.php");
    exit;
}

$error   = "";
$blocked = "";

if ($user["status"] === "pending") {
    $blocked = "Akun kamu masih menunggu persetujuan admin, belum bisa memakai fitur ini.";
} elseif ($user["status"] === "rejected") {
    $blocked = "Pendaftaran kamu ditolak oleh admin. Silakan hubungi petugas perpustakaan.";
} elseif (($user["card_status"] ?? "active") === "frozen") {
    $blocked = "Kartu kamu sedang dibekukan (proses Lupa Kartu belum selesai). Selesaikan itu dulu sebelum mengedit data.";
}

// Ambil daftar kelas dari tabel kelas untuk pilihan dropdown
$daftar_kelas = [];
$res_k = mysqli_query($conn, "SELECT nama_kelas FROM kelas ORDER BY nama_kelas ASC");
if ($res_k) {
    while ($row_k = mysqli_fetch_assoc($res_k)) {
        $daftar_kelas[] = $row_k["nama_kelas"];
    }
}

$form_data = [
    "full_name" => $user["full_name"],
    "kelas"     => $user["kelas"],
    "no_hp"     => $user["no_hp"],
    "email"     => $user["email"],
];

// ─────────────────────────────────────────────
//  Simpan perubahan data
// ─────────────────────────────────────────────
if ($blocked === "" && ($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && ($_POST["action"] ?? "") === "update_data") {

    $new_full_name = trim($_POST["full_name"] ?? "");
    $new_kelas     = trim($_POST["kelas"] ?? "");
    $new_no_hp     = trim($_POST["no_hp"] ?? "");
    $new_email     = strtolower(trim($_POST["email"] ?? ""));
    $new_password  = trim($_POST["new_password"] ?? "");
    $new_password2 = trim($_POST["new_password_confirm"] ?? "");
    $foto_path     = $user["foto"] ?? "";
    $foto_dir      = __DIR__ . "/uploads/anggota";
    $foto_web_dir  = "uploads/anggota";

    $form_data = [
        "full_name" => $new_full_name,
        "kelas"     => $new_kelas,
        "no_hp"     => $new_no_hp,
        "email"     => $new_email,
    ];

    // Ganti password bersifat opsional — kosongkan berarti password tidak berubah
    $want_change_password = $new_password !== "" || $new_password2 !== "";

    if ($new_full_name === "") {
        $error = "Nama lengkap wajib diisi.";
    } elseif ($new_kelas === "") {
        $error = "Pilihan kelas wajib dipilih.";
    } elseif ($new_email === "" || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Alamat email tidak valid.";
    } elseif (!str_ends_with(strtolower($new_email), "@student.smkn1rongga.sch.id")) {
        $error = "Email wajib menggunakan akun siswa resmi (@student.smkn1rongga.sch.id).";
    } elseif ($new_no_hp !== "" && !preg_match('/^[\d+\-\s]{6,20}$/', $new_no_hp)) {
        $error = "Nomor HP tidak valid.";
    } elseif ($want_change_password && strlen($new_password) < 8) {
        $error = "Kata sandi baru minimal 8 karakter.";
    } elseif ($want_change_password && (!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password))) {
        $error = "Kata sandi baru harus mengandung huruf dan angka.";
    } elseif ($want_change_password && $new_password !== $new_password2) {
        $error = "Konfirmasi kata sandi baru tidak cocok.";
    } else {
        // Cek email tidak dipakai anggota lain
        $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id <> ?");
        mysqli_stmt_bind_param($chk, "si", $new_email, $user_id);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            $error = "Email tersebut sudah dipakai akun lain.";
        }
        mysqli_stmt_close($chk);
    }

    // ── Ganti foto profil (opsional) — bisa ambil foto langsung atau dari galeri ──
    $new_foto_uploaded = "";
    if ($error === "" && isset($_FILES["foto"]) && $_FILES["foto"]["error"] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES["foto"]["error"] !== UPLOAD_ERR_OK) {
            $error = "Gagal mengunggah foto baru. Silakan coba lagi.";
        } else {
            $foto_tmp  = $_FILES["foto"]["tmp_name"];
            $foto_size = $_FILES["foto"]["size"];
            $mime      = function_exists("mime_content_type") ? mime_content_type($foto_tmp) : $_FILES["foto"]["type"];
            $allowed_mimes = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];

            if (!isset($allowed_mimes[$mime])) {
                $error = "Format foto harus JPG, PNG, atau WEBP.";
            } elseif ($foto_size > 5 * 1024 * 1024) {
                $error = "Ukuran foto maksimal 5MB.";
            } else {
                if (!is_dir($foto_dir)) {
                    mkdir($foto_dir, 0755, true);
                }
                $ext = $allowed_mimes[$mime];
                $new_filename = "anggota_" . bin2hex(random_bytes(8)) . "." . $ext;
                if (move_uploaded_file($foto_tmp, $foto_dir . "/" . $new_filename)) {
                    $new_foto_uploaded = $foto_web_dir . "/" . $new_filename;
                } else {
                    $error = "Gagal menyimpan foto baru. Silakan coba lagi.";
                }
            }
        }
    }

    if ($error === "") {
        $old_foto_relative = "";
        $set_parts = ["full_name = ?", "kelas = ?", "no_hp = ?", "email = ?"];
        $types  = "ssss";
        $params = [$new_full_name, $new_kelas, $new_no_hp, $new_email];

        if ($new_foto_uploaded !== "") {
            $old_foto_relative = $foto_path; // foto lama sebelum diganti
            $foto_path = $new_foto_uploaded;
            $set_parts[] = "foto = ?";
            $types      .= "s";
            $params[]    = $foto_path;
        }

        if ($want_change_password) {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $set_parts[] = "password = ?";
            $types      .= "s";
            $params[]    = $new_password_hash;
        }

        $sql    = "UPDATE users SET " . implode(", ", $set_parts) . " WHERE id = ?";
        $types .= "i";
        $params[] = $user_id;

        $stmt = mysqli_prepare($conn, $sql);
        $bind_refs = [$stmt, $types];
        foreach ($params as $k => $v) { $bind_refs[] = &$params[$k]; }
        call_user_func_array("mysqli_stmt_bind_param", $bind_refs);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Hapus file foto lama supaya tidak menumpuk
        if ($old_foto_relative !== "") {
            $old_foto_full = __DIR__ . "/" . $old_foto_relative;
            if (file_exists($old_foto_full)) {
                @unlink($old_foto_full);
            }
        }

        // Selaraskan sesi supaya nama di dashboard ikut ter-update
        $_SESSION["user_name"] = $new_full_name;

        // Tampilkan kartu dengan data terbaru. Password yang ditampilkan di kartu SELALU
        // yang asli/valid: kalau diganti, pakai password baru; kalau tidak, pakai password
        // saat ini yang sudah dikonfirmasi & diverifikasi di atas — bukan versi disensor.
        $_SESSION["kartu_data"] = [
            "id"                 => $user_id,
            "full_name"          => $new_full_name,
            "nik"                => $user["nik"],
            "kelas"              => $new_kelas,
            "no_hp"              => $new_no_hp,
            "email"              => $new_email,
            "no_anggota"         => $user["no_anggota"],
            "username"           => $user["username"],
            "password"           => $want_change_password ? $new_password : "",
            "foto"               => $foto_path,
            "status"             => "approved",
            "reissued"           => false,
            "data_updated_only"  => true,
            "password_changed"   => $want_change_password,
        ];

        header("Location: kartu_anggota.php");
        exit;
    }
}

$page_title = "Edit Profil Anggota – AKSA NOVA";
$user_name  = $_SESSION["user_name"] ?? "Anggota";

// ─── Musik latar (dari tabel pengaturan) ───
$musik_res    = mysqli_query($conn, "SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('musik_aktif','musik_file','musik_judul')");
$musik_cfg    = [];
while ($m = mysqli_fetch_assoc($musik_res)) { $musik_cfg[$m["kunci"]] = $m["nilai"]; }
$musik_aktif  = ($musik_cfg["musik_aktif"] ?? "0") === "1";
$musik_file   = $musik_cfg["musik_file"]  ?? "";
$musik_judul  = $musik_cfg["musik_judul"] ?? "Musik Latar";
$musik_tampil = $musik_aktif && $musik_file !== "" && file_exists($musik_file);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>

  <?php require_once "settings_include.php"; ?>
  <script>
    if (localStorage.getItem('aksanova_theme') === 'light') {
      document.documentElement.classList.add('theme-light');
    }
  </script>

  <style>
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }

    :root {
      --bg:           #090c10;
      --sidebar-bg:   #10151b;
      --card:         #121820;
      --accent:       #d8b878;
      --accent2:      #f0d9a8;
      --text:         #eef3f4;
      --muted:        rgba(238,243,244,.65);
      --border-color: rgba(216,184,120,.16);
      --card-border:  rgba(216,184,120,.14);
      --book-card:    #161e27;
      --radius:       14px;
      --sidebar-w:    204px;
      --shadow-sm:    0 2px 12px rgba(0,0,0,.25);
      --shadow-md:    0 4px 20px rgba(0,0,0,.45);
      --trans:        .2s cubic-bezier(.22,1,.36,1);
    }

    html.theme-light {
      --bg:           #f6f2e9;
      --sidebar-bg:   #ffffff;
      --card:         #ffffff;
      --accent:       #9a7328;
      --accent2:      #8a6323;
      --text:         #1a1714;
      --muted:        #6b645b;
      --border-color: rgba(154,115,40,.2);
      --card-border:  rgba(154,115,40,.15);
      --book-card:    #fdfbf7;
      --shadow-sm:    0 2px 12px rgba(60,45,20,.08);
      --shadow-md:    0 6px 24px rgba(60,45,20,.14);
    }

    body {
      font-family:var(--font-family,'Outfit',sans-serif);
      background:var(--bg);
      color:var(--text);
      min-height:100vh;
      display:flex;
      transition:background var(--trans), color var(--trans);
    }

    /* Scrollbar */
    ::-webkit-scrollbar { width:6px; height:6px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:rgba(216,184,120,.3); border-radius:3px; }
    ::-webkit-scrollbar-thumb:hover { background:var(--accent); }

    /* ── SIDEBAR ── */
    .sidebar {
      width:var(--sidebar-w); height:100vh; height:100dvh; background:var(--sidebar-bg);
      display:flex; flex-direction:column; padding:24px 0 20px;
      border-right:1px solid var(--border-color);
      position:fixed; top:0; left:0; bottom:0; z-index:170; transition:transform var(--trans);
      overflow-y:auto; -webkit-overflow-scrolling:touch; scrollbar-width:thin;
      box-shadow:2px 0 24px rgba(0,0,0,.35);
    }
    .logo-wrap { display:flex; flex-direction:column; align-items:center; padding:0 18px 24px; border-bottom:1px solid var(--border-color); }
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

    .nav-bottom { padding:10px 10px 0; border-top:1px solid var(--border-color); display:flex; flex-direction:column; gap:2px; flex-shrink:0; }

    .sidebar-toggle { display:none; position:fixed; top:14px; left:14px; z-index:200; width:42px; height:42px; border-radius:12px; border:1px solid var(--border-color, rgba(216,184,120,.2)); background:var(--card, #121820); box-shadow:0 4px 16px rgba(0,0,0,.3); cursor:pointer; align-items:center; justify-content:center; transition:transform .15s ease; }
    .sidebar-toggle:active { transform:scale(.9); }
    .sidebar-toggle svg { width:20px; height:20px; color:var(--accent); }
    .sidebar-overlay { position:fixed; inset:0; background:rgba(9,12,16,.65); backdrop-filter:blur(3px); z-index:165; opacity:0; visibility:hidden; transition:opacity var(--trans), visibility var(--trans); }
    .sidebar-overlay.open { opacity:1; visibility:visible; }

    /* ── MOBILE TOPBAR (Default hidden di desktop; kontrol tema/musik tetap tampil sebagai HUD) ── */
    .mobile-topbar { display: contents; }
    .mobile-topbar-divider, .mobile-topbar-brand { display: none; }
    .page-hud-controls { position: fixed; top: 16px; right: 20px; z-index: 150; display: flex; align-items: center; gap: 10px; }

    .btn-topbar-mode {
      appearance: none; cursor: pointer; width: 40px; height: 40px; border-radius: 50%;
      border: 1.5px solid var(--border-color, rgba(216,184,120,.3)); background: var(--card, rgba(18,24,32,.85));
      backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center;
      color: var(--accent, #d8b878); transition: all var(--trans); box-shadow: 0 4px 16px rgba(0,0,0,.35); flex-shrink: 0;
    }
    .btn-topbar-mode:hover { border-color: var(--accent); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(216,184,120,.35); }
    .btn-topbar-mode svg { width: 18px; height: 18px; }
    .btn-topbar-mode .icon-sun { display: none; }
    html.theme-light .btn-topbar-mode .icon-moon { display: none; }
    html.theme-light .btn-topbar-mode .icon-sun  { display: block; }

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

    /* ── MAIN CONTENT ── */
    .main {
      margin-left:var(--sidebar-w);
      flex:1; padding:32px 36px 60px;
      min-height:100vh;
      max-width:1100px;
    }

    /* Page Header */
    .page-head {
      display:flex; align-items:center; justify-content:space-between;
      margin-bottom:28px; flex-wrap:wrap; gap:14px;
    }
    .btn-back {
      display:inline-flex; align-items:center; gap:8px;
      padding:8px 16px; border-radius:50px;
      border:1.5px solid var(--border-color);
      background:rgba(255,255,255,.03);
      font-size:.78rem; font-weight:700; color:var(--muted);
      text-decoration:none; transition:all var(--trans);
    }
    .btn-back svg { width:15px; height:15px; }
    .btn-back:hover { color:var(--accent); border-color:var(--accent); background:rgba(216,184,120,.08); }

    .head-info { flex:1; min-width:240px; }
    .page-title {
      font-family:'Cormorant Garamond',serif;
      font-size:1.85rem; font-weight:700; color:var(--accent);
      line-height:1.2; margin-bottom:4px;
    }
    .page-sub { font-size:.82rem; color:var(--muted); font-weight:400; }

    /* Alerts */
    .alert {
      padding:12px 16px; border-radius:10px; font-size:.82rem; line-height:1.5;
      margin-bottom:20px; display:flex; align-items:center; gap:10px;
    }
    .alert-error {
      background:rgba(220,38,38,.12); border:1px solid rgba(220,38,38,.3);
      color:#f87171;
    }
    .alert-info {
      background:rgba(216,184,120,.08); border:1px solid rgba(216,184,120,.25);
      color:var(--accent2);
    }

    /* Profile Edit Card */
    .profile-card {
      background:var(--card);
      border:1px solid var(--border-color);
      border-radius:18px;
      padding:32px 34px;
      box-shadow:var(--shadow-md);
      position:relative;
      overflow:hidden;
    }
    .profile-card::before {
      content:''; position:absolute; top:0; left:0; right:0; height:3px;
      background:linear-gradient(90deg, transparent, var(--accent) 50%, transparent);
    }

    /* Section Divider */
    .form-section-title {
      display:flex; align-items:center; gap:8px;
      font-size:.76rem; font-weight:800; color:var(--accent);
      letter-spacing:.08em; text-transform:uppercase;
      margin:26px 0 16px; padding-bottom:8px;
      border-bottom:1px solid var(--card-border);
    }
    .form-section-title:first-of-type { margin-top:0; }
    .form-section-title svg { width:15px; height:15px; }

    /* Foto Profile Uploader */
    .foto-uploader-wrap {
      display:flex; align-items:center; gap:20px;
      padding:16px; background:var(--book-card);
      border:1px solid var(--card-border); border-radius:14px;
      margin-bottom:24px;
    }
    .foto-circle-wrap {
      position:relative; width:86px; height:86px; border-radius:50%;
      overflow:hidden; flex-shrink:0; cursor:pointer;
      background:var(--card);
      border:2px dashed var(--accent);
      display:flex; align-items:center; justify-content:center;
      transition:all var(--trans);
    }
    .foto-circle-wrap:hover {
      box-shadow:0 0 0 4px rgba(216,184,120,.2);
      transform:scale(1.02);
    }
    .foto-circle-wrap img {
      width:100%; height:100%; object-fit:cover; display:none;
    }
    .foto-circle-wrap.has-photo img { display:block; }
    .foto-circle-wrap.has-photo { border-style:solid; }
    .foto-circle-wrap.has-photo .foto-placeholder-icon { display:none; }
    .foto-placeholder-icon {
      display:flex; flex-direction:column; align-items:center; gap:4px;
      color:var(--muted); font-size:.6rem; text-align:center;
    }
    .foto-placeholder-icon svg { width:24px; height:24px; color:var(--accent); }
    .foto-overlay-badge {
      position:absolute; bottom:0; left:0; right:0; height:24px;
      background:rgba(0,0,0,.65); display:flex; align-items:center; justify-content:center;
      backdrop-filter:blur(2px);
    }
    .foto-overlay-badge svg { width:12px; height:12px; color:var(--accent); }

    .foto-text-wrap { flex:1; min-width:0; }
    .foto-title { font-size:.88rem; font-weight:700; color:var(--text); margin-bottom:4px; }
    .foto-desc { font-size:.74rem; color:var(--muted); line-height:1.5; margin-bottom:10px; }
    .btn-choose-foto {
      display:inline-flex; align-items:center; gap:6px;
      padding:6px 14px; border-radius:50px;
      border:1px solid var(--border-color); background:rgba(216,184,120,.1);
      font-size:.72rem; font-weight:700; color:var(--accent); cursor:pointer;
      transition:all var(--trans);
    }
    .btn-choose-foto:hover { background:rgba(216,184,120,.2); border-color:var(--accent); }

    /* Form Fields */
    .row2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
    .field { margin-bottom:16px; }
    .field label {
      display:block; font-size:.76rem; font-weight:700;
      color:var(--muted); margin-bottom:6px;
    }
    .field-input-wrap { position:relative; display:flex; align-items:center; width:100%; }
    .field input,
    .field select {
      width:100%; padding:12px 16px;
      border:1.5px solid var(--border-color);
      border-radius:10px;
      background:var(--book-card);
      font-family:var(--font-family,'Outfit',sans-serif);
      font-size:.88rem; color:var(--text);
      outline:none; transition:all var(--trans);
    }
    .field select {
      cursor: pointer;
      appearance: none;
      -webkit-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23d8b878' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 14px center;
      padding-right: 40px;
    }
    .field select option {
      background: var(--card, #121820);
      color: var(--text, #eef3f4);
    }
    .field input:focus,
    .field select:focus {
      border-color:var(--accent);
      background:var(--card);
      box-shadow:0 0 0 3px rgba(216,184,120,.18);
    }
    .field-hint { font-size:.7rem; color:var(--muted); margin-top:5px; line-height:1.4; }

    /* Readonly / Locked badges */
    .readonly-grid {
      display:grid; grid-template-columns:repeat(3, 1fr); gap:12px;
      margin-bottom:20px;
    }
    .readonly-item {
      background:var(--book-card); border:1px solid var(--card-border);
      border-radius:10px; padding:12px 14px; display:flex; flex-direction:column; gap:4px;
    }
    .readonly-label {
      font-size:.68rem; font-weight:700; color:var(--muted);
      display:flex; align-items:center; justify-content:space-between;
    }
    .readonly-label svg { width:12px; height:12px; opacity:.6; }
    .readonly-value {
      font-size:.88rem; font-weight:700; color:var(--text);
      overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
    }

    /* Password Eye Toggle */
    .toggle-eye {
      position:absolute; right:14px; width:18px; height:18px;
      color:var(--muted); cursor:pointer; transition:color var(--trans);
    }
    .toggle-eye:hover { color:var(--accent); }
    .toggle-eye svg { width:100%; height:100%; }
    .toggle-eye .eye-off { display:none; }
    .field-input-wrap.pw-visible .eye-on  { display:none; }
    .field-input-wrap.pw-visible .eye-off { display:block; }
    .field-input-wrap input[type="password"],
    .field-input-wrap input[type="text"] { padding-right:42px; }

    /* Form Actions */
    .form-actions {
      display:flex; align-items:center; gap:14px;
      margin-top:28px; padding-top:20px;
      border-top:1px solid var(--card-border);
      flex-wrap:wrap;
    }
    .btn-save-profile {
      display:inline-flex; align-items:center; justify-content:center; gap:8px;
      padding:13px 32px; border-radius:50px; border:none;
      background:linear-gradient(135deg,#d8b878 0%,#f0d9a8 100%);
      color:#090c10; font-family:var(--font-family,'Outfit',sans-serif);
      font-size:.88rem; font-weight:800; cursor:pointer;
      box-shadow:0 6px 20px rgba(216,184,120,.3);
      transition:all var(--trans);
    }
    .btn-save-profile:hover { transform:translateY(-1px); box-shadow:0 8px 26px rgba(216,184,120,.45); }
    .btn-save-profile:active { transform:scale(.98); }

    .btn-cancel {
      display:inline-flex; align-items:center; justify-content:center;
      padding:12px 24px; border-radius:50px;
      border:1.5px solid var(--border-color); background:transparent;
      color:var(--muted); font-size:.85rem; font-weight:700;
      text-decoration:none; transition:all var(--trans);
    }
    .btn-cancel:hover { color:var(--text); border-color:var(--accent); background:rgba(216,184,120,.06); }

    /* ── RESPONSIVE ── */
    @media (max-width: 992px) {
      .main { padding:28px 24px 50px; }
    }

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
        box-shadow:0 2px 18px rgba(0,0,0,.35);
        z-index:160;
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

      .main { margin-left:0; padding:78px 14px 36px; }
      .profile-card { padding:22px 18px 26px; border-radius:14px; }
      .row2 { grid-template-columns:1fr; gap:12px; margin-bottom:12px; }
      .readonly-grid { grid-template-columns:1fr; gap:8px; }
      .btn-save-profile { width:100%; }
      .btn-cancel { width:100%; }
    }

    @media (max-width: 480px) {
      .main { padding:74px 10px 30px; }
      .page-title { font-size:1.55rem; }
      .foto-uploader-wrap { flex-direction:column; text-align:center; gap:12px; }
      .foto-circle-wrap { width:76px; height:76px; margin:0 auto; }
      .btn-choose-foto { margin:0 auto; }
    }

    @media (max-width: 375px) {
      .main { padding:72px 8px 24px; }
      .profile-card { padding:18px 12px; }
    }
  </style>
</head>
<body>

<!-- Mobile Topbar -->
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
    <?php if ($musik_tampil): ?>
    <button type="button" class="btn-musik" id="btnMusik" aria-label="Musik Latar" title="<?= htmlspecialchars($musik_judul) ?>">
      <svg class="icon-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
      <span class="icon-eq" aria-hidden="true"><span></span><span></span><span></span></span>
    </button>
    <audio id="audioLatar" loop autoplay muted preload="auto">
      <source src="<?= htmlspecialchars($musik_file) ?>">
    </audio>
    <?php endif; ?>
  </div>
</header>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar Menu -->
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
    <a href="pengajuan_peminjaman.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
      Ajukan Pinjam
    </a>
    <a href="buku_simpan.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
      Buku Simpan
    </a>
    <a href="edit_kartu.php" class="nav-item active">
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

<!-- Main Area -->
<main class="main">

  <div class="page-head">
    <div class="head-info">
      <h1 class="page-title">Edit Profil Anggota</h1>
      <p class="page-sub">Perbarui informasi akun, foto profil, dan kata sandi kamu</p>
    </div>
    <a href="dashboard_user.php" class="btn-back">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      <span>Dashboard</span>
    </a>
  </div>

  <div class="profile-card">
    <?php if ($blocked): ?>
      <div class="alert alert-error">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span><?= htmlspecialchars($blocked) ?></span>
      </div>
      <a href="dashboard_user.php" class="btn-cancel">Kembali ke Dashboard</a>
    <?php else: ?>

      <?php if ($error): ?>
        <div class="alert alert-error">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <?php
        $current_foto = $user["foto"] ?? "";
        $current_foto_exists = $current_foto !== "" && file_exists(__DIR__ . "/" . $current_foto);
      ?>

      <form method="POST" action="edit_kartu.php" enctype="multipart/form-data" id="editForm">
        <input type="hidden" name="action" value="update_data">

        <!-- 1. Foto Profil -->
        <div class="form-section-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
          Foto Profil Kartu
        </div>

        <div class="foto-uploader-wrap">
          <div class="foto-circle-wrap <?= $current_foto_exists ? 'has-photo' : '' ?>" id="fotoPreviewWrap" onclick="document.getElementById('fotoInput').click()" title="Ketuk untuk ubah foto">
            <img id="fotoPreviewImg" src="<?= $current_foto_exists ? htmlspecialchars($current_foto) . '?v=' . filemtime(__DIR__ . '/' . $current_foto) : '' ?>" alt="Foto profil">
            <div class="foto-placeholder-icon" id="fotoPlaceholder">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
              <span>Ubah Foto</span>
            </div>
            <div class="foto-overlay-badge" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </div>
          </div>
          <div class="foto-text-wrap">
            <div class="foto-title">Foto Kartu Anggota</div>
            <p class="foto-desc">Format JPG, PNG, atau WEBP (maks. 5MB). Foto ini akan langsung diperbarui pada kartu anggota digital kamu.</p>
            <button type="button" class="btn-choose-foto" onclick="document.getElementById('fotoInput').click()">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              Pilih / Ambil Foto
            </button>
            <input type="file" name="foto" id="fotoInput" accept="image/*" style="display:none">
          </div>
        </div>

        <!-- 2. Data Anggota -->
        <div class="form-section-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Informasi Anggota
        </div>

        <div class="row2">
          <div class="field">
            <label for="fullNameInput">Nama Lengkap</label>
            <input type="text" id="fullNameInput" name="full_name" value="<?= htmlspecialchars($form_data['full_name']) ?>" required>
          </div>
          <div class="field">
            <label for="kelasInput">Pilihan Kelas</label>
            <select id="kelasInput" name="kelas" required>
              <option value="" disabled <?= empty($form_data['kelas']) ? 'selected' : '' ?>>-- Pilih Kelas --</option>
              <?php foreach ($daftar_kelas as $k): ?>
                <option value="<?= htmlspecialchars($k) ?>" <?= $form_data['kelas'] === $k ? 'selected' : '' ?>>
                  <?= htmlspecialchars($k) ?>
                </option>
              <?php endforeach; ?>
              <?php if (!empty($form_data['kelas']) && !in_array($form_data['kelas'], $daftar_kelas, true)): ?>
                <option value="<?= htmlspecialchars($form_data['kelas']) ?>" selected><?= htmlspecialchars($form_data['kelas']) ?></option>
              <?php endif; ?>
            </select>
          </div>
        </div>

        <div class="row2">
          <div class="field">
            <label for="noHpInput">Nomor WhatsApp / HP</label>
            <input type="text" id="noHpInput" name="no_hp" placeholder="Contoh: 081234567890" value="<?= htmlspecialchars($form_data['no_hp']) ?>">
          </div>
          <div class="field">
            <label for="emailInput">Alamat Email Siswa</label>
            <input type="email" id="emailInput" name="email" value="<?= htmlspecialchars($form_data['email']) ?>"
                   pattern="[a-zA-Z0-9._%+\-]+@student\.smkn1rongga\.sch\.id$"
                   title="Email harus menggunakan domain @student.smkn1rongga.sch.id" required>
            <p class="field-hint" style="color:var(--accent,#d8b878);">Wajib menggunakan akun resmi berakhiran @student.smkn1rongga.sch.id</p>
          </div>
        </div>

        <!-- 3. Identitas Permanen -->
        <div class="form-section-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          Identitas Resmi (Terkunci)
        </div>

        <div class="readonly-grid">
          <div class="readonly-item">
            <div class="readonly-label">
              <span>Nomor Anggota</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <div class="readonly-value"><?= htmlspecialchars($user["no_anggota"] ?: "—") ?></div>
          </div>
          <div class="readonly-item">
            <div class="readonly-label">
              <span>Username Login</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <div class="readonly-value">@<?= htmlspecialchars($user["username"]) ?></div>
          </div>
          <div class="readonly-item">
            <div class="readonly-label">
              <span>NIK Terdaftar</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <div class="readonly-value"><?= htmlspecialchars($user["nik"] ?: "—") ?></div>
          </div>
        </div>

        <!-- 4. Ganti Kata Sandi -->
        <div class="form-section-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-1.5 1.5L14 9M19 8l-7 7m4-7l-7 7m1 1l-2 2H4v-4l2-2 7-7"/></svg>
          Ubah Kata Sandi (Opsional)
        </div>

        <div class="row2">
          <div class="field">
            <label for="newPwInput">Kata Sandi Baru</label>
            <div class="field-input-wrap" id="wrapPw1">
              <input type="password" id="newPwInput" name="new_password" placeholder="Minimal 8 karakter, huruf & angka" autocomplete="new-password">
              <span class="toggle-eye" data-target="newPwInput" data-wrap="wrapPw1" role="button" tabindex="0" aria-label="Tampilkan kata sandi">
                <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a20.3 20.3 0 0 1-2.61 3.61M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
              </span>
            </div>
          </div>
          <div class="field">
            <label for="confirmPwInput">Konfirmasi Kata Sandi Baru</label>
            <div class="field-input-wrap" id="wrapPw2">
              <input type="password" id="confirmPwInput" name="new_password_confirm" placeholder="Ulangi kata sandi baru" autocomplete="new-password">
              <span class="toggle-eye" data-target="confirmPwInput" data-wrap="wrapPw2" role="button" tabindex="0" aria-label="Tampilkan kata sandi">
                <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a20.3 20.3 0 0 1-2.61 3.61M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
              </span>
            </div>
          </div>
        </div>
        <p class="field-hint" style="margin-top:-6px; margin-bottom:12px;">Kosongkan kedua kolom di atas jika kamu tidak ingin mengganti kata sandi.</p>

        <!-- Form Actions -->
        <div class="form-actions">
          <button type="submit" class="btn-save-profile">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
            Simpan Perubahan
          </button>
          <a href="dashboard_user.php" class="btn-cancel">Batal</a>
        </div>
      </form>

    <?php endif; ?>
  </div>

</main>

<script>
  // ─── Sidebar Mobile Toggle ───
  (function () {
    var toggle  = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    if (!toggle || !sidebar) return;

    function openSidebar() {
      sidebar.classList.add('open');
      document.body.classList.add('sidebar-open');
      if (overlay) overlay.classList.add('open');
    }
    function closeSidebar() {
      sidebar.classList.remove('open');
      document.body.classList.remove('sidebar-open');
      if (overlay) overlay.classList.remove('open');
    }

    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
    if (overlay) overlay.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && sidebar.classList.contains('open')) closeSidebar();
    });
  })();

  // ─── Preview Foto Profil Baru ───
  <?php if (!$blocked): ?>
  (function () {
    var fotoInput       = document.getElementById('fotoInput');
    var fotoPreviewWrap = document.getElementById('fotoPreviewWrap');
    var fotoPreviewImg  = document.getElementById('fotoPreviewImg');
    var fotoPlaceholder = document.getElementById('fotoPlaceholder');
    if (!fotoInput || !fotoPreviewImg) return;

    fotoInput.addEventListener('change', function (e) {
      var file = e.target.files[0];
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function (ev) {
        fotoPreviewImg.src = ev.target.result;
        fotoPreviewImg.style.display = 'block';
        if (fotoPlaceholder) fotoPlaceholder.style.display = 'none';
        fotoPreviewWrap.classList.add('has-photo');
      };
      reader.readAsDataURL(file);
    });
  })();

  // ─── Toggle Visibility Kata Sandi ───
  document.querySelectorAll('.toggle-eye').forEach(function (toggle) {
    var input = document.getElementById(toggle.dataset.target);
    var wrap  = document.getElementById(toggle.dataset.wrap);
    if (!input || !wrap) return;

    function togglePassword() {
      var isVisible = input.type === 'text';
      input.type = isVisible ? 'password' : 'text';
      wrap.classList.toggle('pw-visible', !isVisible);
    }
    toggle.addEventListener('click', togglePassword);
    toggle.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); togglePassword(); }
    });
  });
  <?php endif; ?>



  // ─── Musik Latar (Dikelola terpusat oleh AksaAudio di settings_include.php) ───
  if (window.AksaAudio) window.AksaAudio.init();
</script>

<?php require_once "pengaturan_panel.php"; ?>
</body>
</html>