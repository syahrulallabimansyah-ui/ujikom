<?php
// sign_up.php — Pendaftaran anggota mandiri + pembuatan kartu anggota otomatis
session_start();
require_once "db.php";

// Jika sudah login, langsung arahkan ke beranda
if (isset($_SESSION["user_id"])) {
    header("Location: beranda.php");
    exit;
}

$errors = [];
$old = [
    "tipe_anggota" => "siswa",
    "full_name"    => "",
    "nik"          => "",
    "kelas"        => "",
    "no_hp"        => "",
    "email"        => "",
    "saved_foto"   => "",
];

// Ambil daftar kelas dari tabel kelas untuk opsi dropdown (khusus rombel siswa)
$daftar_kelas = [];
$res_k = mysqli_query($conn, "SELECT nama_kelas FROM kelas ORDER BY nama_kelas ASC");
if ($res_k) {
    while ($row_k = mysqli_fetch_assoc($res_k)) {
        if (strcasecmp($row_k["nama_kelas"], "Guru") !== 0) {
            $daftar_kelas[] = $row_k["nama_kelas"];
        }
    }
}

// Folder tempat menyimpan foto profil anggota
$foto_dir = __DIR__ . "/uploads/anggota";
$foto_web_dir = "uploads/anggota"; // path relatif yang disimpan ke DB & dipakai di <img src>

// Pembersihan otomatis foto temp pendaftaran yang berusia lebih dari 2 jam
if (is_dir($foto_dir)) {
    foreach (glob($foto_dir . "/temp_*") ?: [] as $tf) {
        if (is_file($tf) && filemtime($tf) < (time() - 7200)) {
            @unlink($tf);
        }
    }
}

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {

    $tipe_anggota        = trim($_POST["tipe_anggota"] ?? "siswa");
    $old["tipe_anggota"] = ($tipe_anggota === "guru") ? "guru" : "siswa";
    $old["full_name"]    = trim($_POST["full_name"] ?? "");
    $old["nik"]          = trim($_POST["nik"] ?? "");
    $old["kelas"]        = ($old["tipe_anggota"] === "guru") ? "Guru" : trim($_POST["kelas"] ?? "");
    $old["no_hp"]        = trim($_POST["no_hp"] ?? "");
    $old["email"]        = strtolower(trim($_POST["email"] ?? ""));
    $old["saved_foto"]   = trim($_POST["saved_foto"] ?? "");
    $password            = $_POST["password"] ?? "";
    $password_confirm    = $_POST["password_confirm"] ?? "";

    // ── Validasi ──
    if ($old["full_name"] === "") {
        $errors[] = "Nama lengkap wajib diisi.";
    }

    if ($old["tipe_anggota"] === "guru") {
        if ($old["nik"] === "") {
            $errors[] = "NIP / NUPTK / NIK Guru wajib diisi.";
        } elseif (strlen($old["nik"]) < 3) {
            $errors[] = "NIP / NIK Guru minimal 3 karakter.";
        }
    } else {
        if ($old["nik"] === "") {
            $errors[] = "Nomor Induk Siswa (NIS) wajib diisi.";
        } elseif (!preg_match('/^[0-9]{3,20}$/', $old["nik"])) {
            $errors[] = "NIS harus berupa angka (minimal 3 digit).";
        }
        if ($old["kelas"] === "") {
            $errors[] = "Pilihan kelas wajib dipilih.";
        }
    }

    if ($old["no_hp"] === "") {
        $errors[] = "Nomor HP / WhatsApp wajib diisi.";
    } elseif (!preg_match('/^[\d+\-\s]{8,20}$/', $old["no_hp"])) {
        $errors[] = "Nomor HP tidak valid (harus berupa angka minimal 8 digit).";
    }

    if ($old["email"] === "" || !filter_var($old["email"], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Alamat email tidak valid.";
    } else {
        $email_lower = strtolower($old["email"]);
        $is_student  = str_ends_with($email_lower, "@student.smkn1rongga.sch.id");
        $is_school   = str_ends_with($email_lower, "@smkn1rongga.sch.id");
        $is_gmail    = str_ends_with($email_lower, "@gmail.com");
        if (!$is_student && !$is_school && !$is_gmail) {
            $errors[] = "Email dapat menggunakan akun sekolah (@student.smkn1rongga.sch.id / @smkn1rongga.sch.id) atau akun Gmail (@gmail.com).";
        }
    }

    if (strlen($password) < 8) {
        $errors[] = "Kata sandi minimal 8 karakter.";
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = "Kata sandi harus mengandung huruf dan angka.";
    }
    if ($password !== $password_confirm) {
        $errors[] = "Konfirmasi kata sandi tidak sama.";
    }

    // ── Penanganan Foto Profil (Opsional & Tidak Hilang Saat Validasi Gagal) ──
    $allowed_mimes = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
    $foto_temp_path = "";

    // A. Cek apakah ada file foto baru yang diunggah
    if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] === UPLOAD_ERR_OK) {
        $foto_tmp  = $_FILES["foto"]["tmp_name"];
        $foto_size = $_FILES["foto"]["size"];
        $mime      = function_exists("mime_content_type") ? mime_content_type($foto_tmp) : $_FILES["foto"]["type"];

        if (!isset($allowed_mimes[$mime])) {
            $errors[] = "Format foto harus JPG, PNG, atau WEBP.";
        } elseif ($foto_size > 5 * 1024 * 1024) {
            $errors[] = "Ukuran foto maksimal 5MB.";
        } else {
            if (!is_dir($foto_dir)) {
                mkdir($foto_dir, 0755, true);
            }
            // Hapus file temp lama jika ada
            if ($old["saved_foto"] !== "" && str_starts_with($old["saved_foto"], "uploads/anggota/temp_") && file_exists(__DIR__ . "/" . $old["saved_foto"])) {
                @unlink(__DIR__ . "/" . $old["saved_foto"]);
            }
            $ext = $allowed_mimes[$mime];
            $temp_filename = "temp_" . bin2hex(random_bytes(8)) . "." . $ext;
            if (move_uploaded_file($foto_tmp, $foto_dir . "/" . $temp_filename)) {
                $foto_temp_path = $foto_web_dir . "/" . $temp_filename;
                $old["saved_foto"] = $foto_temp_path;
            } else {
                $errors[] = "Gagal mengunggah foto. Silakan coba lagi.";
            }
        }
    } elseif (isset($_FILES["foto"]) && $_FILES["foto"]["error"] !== UPLOAD_ERR_NO_FILE) {
        // Ada error upload selain tidak memilih file (misal file melebihi upload_max_filesize)
        $errors[] = "Gagal mengunggah foto. Pastikan ukuran file maksimal 5MB.";
    } else {
        // B. Tidak ada file baru, cek apakah sebelumnya sudah ada foto temp tersimpan
        if ($old["saved_foto"] !== "" && str_starts_with($old["saved_foto"], "uploads/anggota/temp_") && file_exists(__DIR__ . "/" . $old["saved_foto"])) {
            $foto_temp_path = $old["saved_foto"];
        } else {
            $old["saved_foto"] = "";
        }
    }

    // Cek email dan NIS belum terdaftar
    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $old["email"]);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Email tersebut sudah terdaftar sebagai anggota.";
        }
        mysqli_stmt_close($stmt);

        if ($old["nik"] !== "") {
            $stmt_nik = mysqli_prepare($conn, "SELECT id FROM users WHERE nik = ?");
            mysqli_stmt_bind_param($stmt_nik, "s", $old["nik"]);
            mysqli_stmt_execute($stmt_nik);
            mysqli_stmt_store_result($stmt_nik);
            if (mysqli_stmt_num_rows($stmt_nik) > 0) {
                $errors[] = ($old["tipe_anggota"] === "guru") ? "NIP / NIK tersebut sudah terdaftar sebagai anggota." : "Nomor Induk Siswa (NIS) tersebut sudah terdaftar.";
            }
            mysqli_stmt_close($stmt_nik);
        }
    }

    if (empty($errors)) {

        // ── Pindahkan foto temp ke nama permanen jika ada foto ──
        $foto_relative_path = "";
        if ($foto_temp_path !== "" && file_exists(__DIR__ . "/" . $foto_temp_path)) {
            $ext = pathinfo($foto_temp_path, PATHINFO_EXTENSION);
            $foto_filename = "anggota_" . bin2hex(random_bytes(8)) . "." . $ext;
            if (rename(__DIR__ . "/" . $foto_temp_path, $foto_dir . "/" . $foto_filename)) {
                $foto_relative_path = $foto_web_dir . "/" . $foto_filename;
            } else {
                $foto_relative_path = $foto_temp_path;
            }
        }

        // ── Generate username unik dari nama ──
        $base = strtolower(preg_replace('/[^a-z0-9]/i', '', $old["full_name"]));
        if ($base === "") $base = "anggota";
        $username = $base;
        $suffix = 0;
        while (true) {
            $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
            mysqli_stmt_bind_param($chk, "s", $username);
            mysqli_stmt_execute($chk);
            mysqli_stmt_store_result($chk);
            $exists = mysqli_stmt_num_rows($chk) > 0;
            mysqli_stmt_close($chk);
            if (!$exists) break;
            $suffix++;
            $username = $base . $suffix;
        }

        // ── Password dibuat sendiri oleh user (lihat validasi di atas) ──
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // ── Generate nomor anggota ──
        $no_anggota = "AN-" . date("Y") . "-" . str_pad((string)random_int(1, 99999), 5, "0", STR_PAD_LEFT);
        // pastikan unik
        while (true) {
            $chk2 = mysqli_prepare($conn, "SELECT id FROM users WHERE no_anggota = ?");
            mysqli_stmt_bind_param($chk2, "s", $no_anggota);
            mysqli_stmt_execute($chk2);
            mysqli_stmt_store_result($chk2);
            $exists2 = mysqli_stmt_num_rows($chk2) > 0;
            mysqli_stmt_close($chk2);
            if (!$exists2) break;
            $no_anggota = "AN-" . date("Y") . "-" . str_pad((string)random_int(1, 99999), 5, "0", STR_PAD_LEFT);
        }

        // ── Simpan ke database (langsung approved, tanpa perlu approval admin) ──
        $stmt = mysqli_prepare($conn,
            "INSERT INTO users (full_name, nik, kelas, no_hp, no_anggota, username, email, password, plain_password, foto, role, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'member', 'approved')"
        );
        mysqli_stmt_bind_param(
            $stmt, "ssssssssss",
            $old["full_name"], $old["nik"], $old["kelas"], $old["no_hp"],
            $no_anggota, $username, $old["email"], $hashed, $password, $foto_relative_path
        );

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Simpan sementara di session untuk pembuatan kartu pertama di kartu_anggota.php
            $_SESSION["kartu_data"] = [
                "id"                => $new_id,
                "full_name"         => $old["full_name"],
                "nik"               => $old["nik"],
                "kelas"             => $old["kelas"],
                "no_hp"             => $old["no_hp"],
                "email"             => $old["email"],
                "no_anggota"        => $no_anggota,
                "username"          => $username,
                "password"          => $password,
                "foto"              => $foto_relative_path,
                "status"            => "approved",
                "reissued"          => false,
                "is_first_creation" => true,
            ];

            header("Location: kartu_anggota.php");
            exit;
        } else {
            $errors[] = "Gagal menyimpan data. Silakan coba lagi.";
            mysqli_stmt_close($stmt);
            if ($foto_relative_path !== "" && file_exists(__DIR__ . "/" . $foto_relative_path)) {
                @unlink(__DIR__ . "/" . $foto_relative_path);
            }
        }
    }
}

$page_title = "Daftar Anggota – AKSA NOVA";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <script>
    if (localStorage.getItem('aksanova_theme') === 'light') {
      document.documentElement.classList.add('theme-light');
    }
  </script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --ink:       #eef3f4;
      --dim:       rgba(238,243,244,.68);
      --ghost:     rgba(238,243,244,.45);
      --surface:   #10151b;
      --field:     rgba(255,255,255,.06);
      --field-foc: rgba(255,255,255,.10);
      --panel-bg:  linear-gradient(148deg, #0c1008 0%, #16200d 50%, #090c10 100%);
      --accent:    #d8b878;
      --ring:      rgba(216,184,120,.28);
      --radius-lg: 26px;
      --radius-md: 10px;
      --shadow:    0 32px 80px rgba(0,0,0,.75);
      --trans:     .25s cubic-bezier(.22,1,.36,1);
    }

    html, body {
      min-height: 100vh;
      font-family: 'Outfit', sans-serif;
      background: linear-gradient(135deg, #090c10 0%, #0e1318 50%, #090c10 100%);
      background-size: 300% 300%;
      animation: bgShift 10s ease infinite;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 0;
    }

    @keyframes bgShift {
      0%,100% { background-position: 0% 50%; }
      50%      { background-position: 100% 50%; }
    }

    .card {
      width: 960px;
      max-width: calc(100vw - 24px);
      border-radius: var(--radius-lg);
      overflow: hidden;
      display: flex;
      box-shadow: var(--shadow);
      border: 1px solid rgba(216,184,120,.18);
      animation: riseIn .65s cubic-bezier(0.16, 1, 0.3, 1) both;
      will-change: transform, opacity;
    }

    @keyframes riseIn {
      from { opacity:0; transform:translateY(16px) scale(.985); }
      to   { opacity:1; transform:translateY(0) scale(1); }
    }

    .left {
      flex: 0 0 58%;
      background: #10151b;
      padding: 48px 52px;
      position: relative;
    }

    .left::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: linear-gradient(90deg, transparent, #d8b878, #f0d9a8, #d8b878, transparent);
      background-size: 200% 100%;
      animation: shimmer 3s linear infinite;
    }

    @keyframes shimmer {
      0%   { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }

    .form-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 2.2rem;
      font-weight: 700;
      color: #d8b878;
      margin-bottom: 6px;
    }

    .form-sub {
      font-size: .82rem;
      color: rgba(238,243,244,.55);
      font-weight: 300;
      margin-bottom: 24px;
    }

    .error-box {
      background: rgba(192,57,43,.15);
      border: 1px solid rgba(192,57,43,.5);
      color: #e07070;
      font-size: .8rem;
      padding: 12px 14px;
      border-radius: var(--radius-md);
      margin-bottom: 16px;
      line-height: 1.6;
    }
    .error-box ul { padding-left: 18px; }

    .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

    .field { margin-bottom: 12px; }

    .field > label {
      display: block;
      font-size: .76rem;
      color: rgba(238,243,244,.72);
      margin-bottom: 6px;
      font-weight: 500;
      letter-spacing: .01em;
    }

    .badge-opt {
      font-size: .72rem;
      font-weight: 400;
      color: rgba(238,243,244,.45);
      margin-left: 4px;
    }

    .badge-req {
      font-size: .72rem;
      font-weight: 600;
      color: #d8b878;
      margin-left: 4px;
    }

    .field input,
    .field select {
      width: 100%;
      height: 46px;
      padding: 11px 15px;
      border: 1.5px solid rgba(216,184,120,.18);
      border-radius: var(--radius-md);
      background: rgba(255,255,255,.05);
      font-family: 'Outfit', sans-serif;
      font-size: .86rem;
      color: #eef3f4;
      outline: none;
      transition: background var(--trans), border-color var(--trans), box-shadow var(--trans);
    }

    .select-wrap { position: relative; width: 100%; }

    .field select {
      cursor: pointer;
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      padding-right: 40px;
    }
    .field select::-ms-expand { display: none; }
    .select-arrow {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      width: 16px;
      height: 16px;
      color: #d8b878;
      pointer-events: none;
    }

    .field select option {
      background: #121820;
      color: #eef3f4;
      padding: 8px;
    }

    .field input:focus,
    .field select:focus {
      background-color: rgba(216,184,120,.08);
      border-color: #d8b878;
      box-shadow: 0 0 0 3px rgba(216,184,120,.2);
    }

    .field input::placeholder { color: rgba(238,243,244,.38); }

    .hint {
      font-size: .72rem;
      color: rgba(238,243,244,.62);
      margin-top: 6px;
      line-height: 1.45;
    }

    .hint-email {
      font-size: .73rem;
      color: rgba(216,184,120,.9);
      margin-top: 6px;
      line-height: 1.45;
    }
    .hint-email b {
      color: #f0d9a8;
      font-weight: 600;
    }

    /* ── Switcher Jenis Anggota (Siswa vs Guru) ── */
    .tipe-field { margin-bottom: 14px; }
    .tipe-label {
      display: block;
      font-size: .76rem;
      font-weight: 600;
      color: rgba(238,243,244,.78);
      margin-bottom: 6px;
    }
    .tipe-segmented {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 6px;
      background: rgba(8, 12, 16, 0.72);
      border: 1.5px solid rgba(216, 184, 120, 0.22);
      border-radius: 12px;
      padding: 5px;
      transition: border-color var(--trans), background var(--trans);
    }
    .field .tipe-segmented label.tipe-btn,
    .tipe-btn {
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      text-align: center;
      gap: 8px;
      height: 42px;
      padding: 0 14px;
      margin-bottom: 0 !important;
      border-radius: 8px;
      cursor: pointer;
      font-size: .86rem;
      font-weight: 500;
      color: rgba(238, 243, 244, 0.6);
      background: transparent;
      border: 1.5px solid transparent;
      transition: all .2s cubic-bezier(.22, 1, .36, 1);
      user-select: none;
    }
    .tipe-btn span {
      display: inline-flex;
      align-items: center;
      line-height: 1;
    }
    .tipe-btn input[type="radio"] {
      position: absolute;
      opacity: 0;
      pointer-events: none;
      width: 0;
      height: 0;
      margin: 0;
    }
    .tipe-btn .tipe-icon {
      width: 18px;
      height: 18px;
      flex-shrink: 0;
      color: rgba(238, 243, 244, 0.45);
      transition: color .2s, transform .2s;
    }
    .tipe-btn:hover:not(.active) {
      color: #eef3f4;
      background: rgba(255, 255, 255, 0.05);
    }
    .tipe-btn:hover:not(.active) .tipe-icon {
      color: rgba(238, 243, 244, 0.85);
    }
    .tipe-btn:active {
      transform: scale(0.98);
    }
    .field .tipe-segmented label.tipe-btn.active,
    .tipe-btn.active {
      background: linear-gradient(135deg, #1e2834 0%, #151d27 100%) !important;
      border: 1.5px solid #d8b878 !important;
      color: #ffd88a !important;
      font-weight: 700 !important;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.55), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
    }
    .field .tipe-segmented label.tipe-btn.active .tipe-icon,
    .tipe-btn.active .tipe-icon {
      color: #ffd88a !important;
      transform: scale(1.08);
    }
    .field .tipe-segmented label.tipe-btn.active span,
    .tipe-btn.active span {
      color: #ffd88a !important;
      font-weight: 700 !important;
    }

    /* ── Box Info Status Keanggotaan Guru ── */
    .guru-status-pill {
      height: 46px;
      padding: 0 14px;
      background: rgba(216,184,120,.12);
      border: 1.5px solid rgba(216,184,120,.32);
      border-radius: var(--radius-md);
      font-size: .84rem;
      color: #d8b878;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .guru-pill-icon {
      width: 18px;
      height: 18px;
      flex-shrink: 0;
      color: #d8b878;
    }

    /* ── Upload Foto Profil ── */
    .foto-field { display: flex; align-items: center; gap: 16px; margin-bottom: 18px; }
    .foto-preview-wrap {
      position: relative;
      width: 84px; height: 84px;
      border-radius: 50%;
      overflow: hidden;
      background: rgba(255,255,255,.05);
      border: 1.5px dashed rgba(216,184,120,.35);
      cursor: pointer;
      flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      transition: border-color var(--trans);
    }
    .foto-preview-wrap:hover { border-color: #d8b878; }
    .foto-preview-wrap.has-photo { border-style: solid; border-color: #d8b878; }
    .foto-preview-wrap img { width: 100%; height: 100%; object-fit: cover; display: none; }
    .foto-placeholder { display: flex; flex-direction: column; align-items: center; gap: 4px; color: rgba(238,243,244,.4); }
    .foto-placeholder svg { width: 26px; height: 26px; }
    .foto-placeholder span { font-size: .58rem; text-align: center; line-height: 1.3; padding: 0 6px; }
    .foto-info-text { font-size: .78rem; color: rgba(238,243,244,.65); font-weight: 500; margin-bottom: 4px; }
    .foto-hint { font-size: .68rem; color: rgba(238,243,244,.4); line-height: 1.5; }
    .foto-error { font-size: .7rem; color: #e07070; margin-top: 4px; display: none; }
    .foto-error.show { display: block; }

    .input-wrap { position: relative; display: flex; align-items: center; width: 100%; }
    .input-wrap input { padding-right: 44px; }
    .toggle-eye {
      position: absolute;
      right: 14px;
      width: 18px;
      height: 18px;
      color: rgba(238,243,244,.35);
      cursor: pointer;
      transition: color var(--trans);
    }
    .toggle-eye:hover { color: #d8b878; }
    .toggle-eye svg { width: 100%; height: 100%; }
    .toggle-eye .eye-off { display: none; }
    .input-wrap.pw-visible .eye-on  { display: none; }
    .input-wrap.pw-visible .eye-off { display: block; }

    .btn-primary {
      width: 100%;
      max-width: 240px;
      padding: 13px 0;
      margin-top: 8px;
      border: none;
      border-radius: 50px;
      background: linear-gradient(135deg, #d8b878, #f0d9a8);
      color: #090c10;
      font-family: 'Outfit', sans-serif;
      font-size: .88rem;
      font-weight: 700;
      letter-spacing: .06em;
      cursor: pointer;
      transition: box-shadow var(--trans), transform .15s;
    }
    .btn-primary:hover { box-shadow: 0 8px 28px rgba(216,184,120,.45); transform: translateY(-1px); }
    .btn-primary:active { transform: scale(.97); }

    .back-link {
      display: inline-block;
      margin-top: 16px;
      font-size: .78rem;
      color: rgba(238,243,244,.45);
      text-decoration: none;
    }
    .back-link:hover { color: #d8b878; text-decoration: underline; }

    .right {
      flex: 1;
      background: linear-gradient(148deg, #0c1008 0%, #16200d 50%, #090c10 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 48px 40px;
      position: relative;
      overflow: hidden;
    }

    .right::before {
      content: '';
      position: absolute; inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)' opacity='0.08'/%3E%3C/svg%3E");
      opacity: .22;
      pointer-events: none;
    }

    .orb {
      position: absolute;
      border-radius: 50%;
      filter: blur(40px);
      opacity: .18;
      animation: orbFloat 8s ease-in-out infinite;
    }
    .orb-1 { width:160px;height:160px;background:#d8b878;top:10%;left:5%; }
    .orb-2 { width:100px;height:100px;background:#a07840;bottom:15%;right:8%; animation-delay:3s; }

    @keyframes orbFloat {
      0%,100% { transform: translateY(0) scale(1); }
      50%      { transform: translateY(-10px) scale(1.04); }
    }

    .right-content { position: relative; text-align: center; }

    .right-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 2.3rem;
      font-weight: 700;
      color: #d8b878;
      line-height: 1.15;
      margin-bottom: 14px;
    }

    .right-sub {
      font-size: .84rem;
      font-weight: 300;
      color: rgba(238,243,244,.68);
      line-height: 1.8;
      margin-bottom: 8px;
    }

    .right-list {
      list-style: none;
      text-align: left;
      margin-top: 22px;
      font-size: .8rem;
      color: rgba(238,243,244,.68);
      line-height: 2.1;
    }
    .right-list li::before { content: '✓  '; font-weight: 600; color: #d8b878; }

    /* ══════════════════ RESPONSIVE MOBILE ══════════════════ */
    @media (max-width: 900px) {
      html, body {
        padding: 24px 14px;
        min-height: 100vh;
      }
      .card {
        flex-direction: column;
        width: 100%;
        max-width: 600px;
        border-radius: 22px;
        margin: 0 auto;
        position: relative;
      }
      .card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, #d8b878, #f0d9a8, #d8b878, transparent);
        background-size: 200% 100%;
        animation: shimmer 3s linear infinite;
        z-index: 10;
        pointer-events: none;
      }
      /* "Semua Akses" dan kata-kata berada di bagian ATAS */
      .right {
        order: -1;
        flex: none;
        padding: 32px 24px 24px;
        border-bottom: 1px solid rgba(216,184,120,.16);
      }
      .right-title {
        font-size: 1.85rem;
        margin-bottom: 8px;
      }
      .right-title br {
        display: none;
      }
      .right-sub {
        font-size: .84rem;
        margin-bottom: 12px;
        line-height: 1.5;
      }
      .right-sub br {
        display: none;
      }
      .right-list {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 8px 12px;
        margin-top: 14px;
        font-size: .78rem;
        line-height: 1.4;
      }
      .right-list li {
        display: inline-flex;
        align-items: center;
        background: rgba(216,184,120,.08);
        border: 1px solid rgba(216,184,120,.2);
        padding: 5px 12px;
        border-radius: 20px;
      }
      .right-list li::before {
        content: '✓ ';
        font-weight: 700;
        color: #d8b878;
        margin-right: 4px;
      }
      /* Form berada di bagian BAWAH kata-kata */
      .left {
        flex: none;
        padding: 32px 26px 36px;
      }
      .left::before {
        display: none;
      }
      .form-title {
        font-size: 1.8rem;
      }
      .form-sub {
        font-size: .8rem;
        margin-bottom: 20px;
      }
      .btn-primary {
        max-width: 100%;
        width: 100%;
        padding: 13px 0;
      }
      .back-link {
        display: block;
        text-align: center;
        margin-top: 18px;
      }
    }

    @media (max-width: 640px) {
      html, body {
        padding: 16px 10px;
      }
      .card {
        border-radius: 18px;
      }
      .right {
        padding: 24px 18px 20px;
      }
      .right-title {
        font-size: 1.6rem;
      }
      .right-sub {
        font-size: .78rem;
      }
      .right-list {
        flex-direction: column;
        align-items: center;
        gap: 6px;
      }
      .right-list li {
        width: 100%;
        max-width: 280px;
        justify-content: center;
        font-size: .75rem;
      }
      .left {
        padding: 22px 16px 28px;
      }
      .form-title {
        font-size: 1.55rem;
      }
      .form-sub {
        font-size: .76rem;
        margin-bottom: 18px;
      }
      /* Di layar mobile, jadikan 1 kolom agar input lega dan tidak acak-acakan */
      .row2 {
        grid-template-columns: 1fr;
        gap: 12px;
      }
      .foto-field {
        gap: 12px;
      }
      .foto-preview-wrap {
        width: 72px;
        height: 72px;
      }
      .foto-info-text {
        font-size: .76rem;
      }
      .foto-hint {
        font-size: .66rem;
      }
      .tipe-btn {
        padding: 0 8px;
        font-size: .8rem;
        gap: 6px;
      }
      .tipe-btn .tipe-icon {
        width: 16px;
        height: 16px;
      }
    }

    @media (max-width: 400px) {
      html, body {
        padding: 12px 6px;
      }
      .card {
        border-radius: 16px;
      }
      .right {
        padding: 20px 14px 16px;
      }
      .right-title {
        font-size: 1.35rem;
      }
      .left {
        padding: 18px 12px 24px;
      }
      .form-title {
        font-size: 1.35rem;
      }
      .field input,
      .field select {
        padding: 10px 12px;
        font-size: .82rem;
        height: 44px;
      }
      .guru-status-pill {
        height: 44px;
        font-size: .8rem;
      }
      .tipe-btn {
        height: 36px;
        padding: 0 6px;
        font-size: .76rem;
        gap: 5px;
      }
      .tipe-btn .tipe-icon {
        width: 15px;
        height: 15px;
      }
    }

    /* ══════════════════ TOMBOL MODE GELAP / TERANG ══════════════════ */
    .btn-mode {
      position: fixed;
      top: 18px;
      right: 18px;
      z-index: 999;
      appearance: none;
      cursor: pointer;
      width: 42px;
      height: 42px;
      border-radius: 50%;
      border: 1px solid rgba(216,184,120,.3);
      background: rgba(16,21,27,.75);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #d8b878;
      transition: background .2s ease, border-color .2s ease, transform .18s ease, box-shadow .2s ease;
      box-shadow: 0 6px 20px rgba(0,0,0,.4);
    }
    .btn-mode:hover {
      transform: translateY(-2px);
      border-color: #d8b878;
      box-shadow: 0 8px 24px rgba(216,184,120,.3);
    }
    .btn-mode svg { width: 19px; height: 19px; transition: transform .4s cubic-bezier(.22,1,.36,1); }
    .btn-mode .icon-sun { display: none; }

    /* ══════════════════ LIGHT THEME OVERRIDES ══════════════════ */
    html.theme-light {
      --ink:       #1a1714;
      --dim:       #524b42;
      --ghost:     #7a7267;
      --surface:   #ffffff;
      --field:     #fdfbf7;
      --field-foc: #ffffff;
      --panel-bg:  linear-gradient(148deg, #f5efe6 0%, #ece2d0 50%, #f9f5ee 100%);
      --accent:    #9a7328;
      --ring:      rgba(154,115,40,.28);
      --shadow:    0 32px 80px rgba(70,50,20,.14);
    }
    html.theme-light, html.theme-light body {
      background: linear-gradient(135deg, #f6f2e9 0%, #ece4d4 50%, #f7f3ec 100%);
      color: #1a1714;
    }
    html.theme-light .btn-mode {
      background: rgba(255,255,255,.9);
      border-color: rgba(154,115,40,.35);
      color: #9a7328;
      box-shadow: 0 6px 20px rgba(60,45,20,.12);
    }
    html.theme-light .btn-mode:hover {
      border-color: #9a7328;
      box-shadow: 0 8px 24px rgba(154,115,40,.22);
    }
    html.theme-light .btn-mode .icon-moon { display: none; }
    html.theme-light .btn-mode .icon-sun  { display: block; }
    html.theme-light .card {
      background: #ffffff;
      border-color: rgba(154,115,40,.22);
      box-shadow: 0 24px 60px rgba(60,45,20,.12);
    }
    html.theme-light .left {
      background: #ffffff;
    }
    html.theme-light .form-title {
      color: #8a6323;
    }
    html.theme-light .form-sub {
      color: #524b42;
    }
    html.theme-light .field > label {
      color: #2e2820;
      font-weight: 600;
    }
    html.theme-light .tipe-label {
      color: #2e2820;
      font-weight: 600;
    }
    html.theme-light .badge-req {
      color: #8a6323;
    }
    html.theme-light .badge-opt {
      color: #7a7267;
    }
    html.theme-light .field input,
    html.theme-light .field select {
      background: #fdfbf7;
      border-color: rgba(154,115,40,.26);
      color: #1a1714;
    }
    html.theme-light .select-arrow {
      color: #9a7328;
    }
    html.theme-light .field select option {
      background: #ffffff;
      color: #1a1714;
    }
    html.theme-light .field input::placeholder {
      color: #8c8479;
    }
    html.theme-light .field input:focus,
    html.theme-light .field select:focus {
      background: #ffffff;
      border-color: #9a7328;
      box-shadow: 0 0 0 3px rgba(154,115,40,.18);
    }
    html.theme-light .field-icon {
      color: #8c8479;
    }
    html.theme-light .field:focus-within .field-icon {
      color: #9a7328;
    }
    html.theme-light .toggle-eye {
      color: #8c8479;
    }
    html.theme-light .toggle-eye:hover {
      color: #9a7328;
    }
    html.theme-light .foto-info-text {
      color: #2e2820;
    }
    html.theme-light .foto-hint {
      color: #524b42;
    }
    html.theme-light .hint {
      color: #524b42;
    }
    html.theme-light .hint-email {
      color: #7d5b1d;
    }
    html.theme-light .hint-email b {
      color: #553c0e;
    }
    html.theme-light .foto-error {
      color: #c0392b;
    }
    html.theme-light .foto-placeholder {
      background: #fbf8f2;
      border-color: rgba(154,115,40,.3);
      color: #8a6323;
    }
    html.theme-light .foto-preview-wrap {
      border-color: rgba(154,115,40,.3);
    }
    html.theme-light .foto-preview-wrap:hover {
      border-color: #9a7328;
    }
    html.theme-light .guru-status-pill {
      background: #fbf8f2;
      border-color: rgba(154,115,40,.35);
      color: #8a6323;
    }
    html.theme-light .guru-pill-icon {
      color: #8a6323;
    }
    html.theme-light .tipe-segmented {
      background: #eee8dc;
      border-color: rgba(154, 115, 40, 0.28);
    }
    html.theme-light .field .tipe-segmented label.tipe-btn,
    html.theme-light .tipe-btn {
      color: #635b50;
      margin-bottom: 0 !important;
      border: 1.5px solid transparent;
      background: transparent;
      font-weight: 500;
    }
    html.theme-light .tipe-btn .tipe-icon {
      color: #7a7267;
    }
    html.theme-light .tipe-btn:hover:not(.active) {
      color: #1a1714;
      background: rgba(154, 115, 40, 0.08);
    }
    html.theme-light .tipe-btn:hover:not(.active) .tipe-icon {
      color: #1a1714;
    }
    html.theme-light .field .tipe-segmented label.tipe-btn.active,
    html.theme-light .tipe-btn.active {
      background: #ffffff !important;
      border: 1.5px solid #b88b36 !important;
      color: #8a6323 !important;
      font-weight: 700 !important;
      box-shadow: 0 3px 10px rgba(154, 115, 40, 0.22) !important;
    }
    html.theme-light .field .tipe-segmented label.tipe-btn.active .tipe-icon,
    html.theme-light .tipe-btn.active .tipe-icon {
      color: #8a6323 !important;
      transform: scale(1.08);
    }
    html.theme-light .field .tipe-segmented label.tipe-btn.active span,
    html.theme-light .tipe-btn.active span {
      color: #8a6323 !important;
      font-weight: 700 !important;
    }
    html.theme-light .btn-submit {
      background: linear-gradient(135deg, #d8b878, #caa055);
      color: #1a1205;
      box-shadow: 0 6px 20px rgba(154,115,40,.25);
    }
    html.theme-light .btn-submit:hover {
      box-shadow: 0 8px 28px rgba(154,115,40,.38);
    }
    html.theme-light .right {
      background: linear-gradient(148deg, #f8f4ec 0%, #ece1ce 50%, #f4ede0 100%);
      border-bottom-color: rgba(154,115,40,.2);
    }
    html.theme-light .right-title {
      color: #8a6323;
    }
    html.theme-light .right-sub {
      color: #524b42;
    }
    html.theme-light .right-list {
      color: #4a433a;
    }
    html.theme-light .right-list li {
      background: rgba(154,115,40,.08);
      border-color: rgba(154,115,40,.22);
      color: #4a433a;
    }
    html.theme-light .right-list li::before {
      color: #8a6323;
    }
    html.theme-light .btn-primary {
      background: linear-gradient(135deg, #d8b878, #caa055);
      color: #1a1205;
      box-shadow: 0 6px 20px rgba(154,115,40,.25);
    }
    html.theme-light .btn-primary:hover {
      box-shadow: 0 8px 28px rgba(154,115,40,.38);
    }
    html.theme-light .back-link {
      color: #524b42;
    }
    html.theme-light .back-link:hover {
      color: #8a6323;
    }
    html.theme-light .btn-outline {
      border-color: rgba(154,115,40,.45);
      color: #8a6323;
    }
    html.theme-light .btn-outline:hover {
      background: rgba(154,115,40,.12);
      border-color: #8a6323;
      box-shadow: 0 6px 20px rgba(154,115,40,.2);
    }
    html.theme-light .error-box {
      background: rgba(220,38,38,.08);
      border-color: rgba(220,38,38,.25);
      color: #b91c1c;
    }
  </style>
</head>
<body>

<!-- Tombol Ganti Mode Gelap / Terang -->
<button type="button" class="btn-mode" id="btnMode" aria-label="Ganti mode gelap/terang" title="Mode Gelap / Terang" aria-pressed="false">
  <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/>
  </svg>
  <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="4.2"/>
    <path d="M12 2.5v2.4M12 19.1v2.4M4.9 4.9l1.7 1.7M17.4 17.4l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.9 19.1l1.7-1.7M17.4 6.6l1.7-1.7"/>
  </svg>
</button>

<div class="card">

  <div class="left">
    <h1 class="form-title">Buat Kartu Anggota</h1>
    <p class="form-sub">Isi data diri kamu, username dibuat otomatis &amp; password kamu tentukan sendiri</p>

    <?php if (!empty($errors)): ?>
      <div class="error-box">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="sign_up.php" enctype="multipart/form-data" id="signupForm">
      <div class="foto-field">
        <input type="hidden" name="saved_foto" id="savedFotoInput" value="<?= htmlspecialchars($old['saved_foto']) ?>">
        <?php $has_saved_foto = (!empty($old['saved_foto']) && file_exists(__DIR__ . '/' . $old['saved_foto'])); ?>
        <div class="foto-preview-wrap <?= $has_saved_foto ? 'has-photo' : '' ?>" id="fotoPreviewWrap" onclick="document.getElementById('fotoInput').click()">
          <img id="fotoPreviewImg" src="<?= $has_saved_foto ? htmlspecialchars($old['saved_foto']) : '' ?>" style="<?= $has_saved_foto ? 'display:block;' : 'display:none;' ?>" alt="Pratinjau foto profil">
          <div class="foto-placeholder" id="fotoPlaceholder" style="<?= $has_saved_foto ? 'display:none;' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
              <circle cx="12" cy="13" r="4"/>
            </svg>
            <span>Foto Profil</span>
          </div>
        </div>
        <div>
          <div class="foto-info-text">Foto Profil untuk Kartu Anggota <span class="badge-opt">(Opsional)</span></div>
          <p class="foto-hint" id="fotoHintText"><?= $has_saved_foto ? 'Foto tersimpan! Ketuk lingkaran untuk mengganti foto.' : 'Ketuk lingkaran di samping untuk ambil foto dari kamera atau pilih dari galeri (opsional).' ?></p>
          <div id="wrapHapusFoto" style="<?= $has_saved_foto ? 'display:block;margin-top:6px;' : 'display:none;margin-top:6px;' ?>">
            <button type="button" id="btnHapusFoto" class="btn-hapus-foto" style="background:rgba(220,38,38,.12);border:1px solid rgba(220,38,38,.3);color:#f87171;font-size:.72rem;padding:3px 10px;border-radius:6px;cursor:pointer;">
              &times; Hapus Foto (Daftar Tanpa Foto)
            </button>
          </div>
          <p class="foto-error" id="fotoError"></p>
        </div>
        <input type="file" name="foto" id="fotoInput" accept="image/*" style="display:none">
      </div>

      <!-- Pilihan Jenis Anggota: Siswa vs Guru -->
      <div class="field tipe-field">
        <label class="tipe-label">Jenis Anggota</label>
        <div class="tipe-segmented" role="radiogroup" aria-label="Pilihan Jenis Anggota">
          <label class="tipe-btn <?= ($old['tipe_anggota'] ?? 'siswa') === 'siswa' ? 'active' : '' ?>" id="btnTipeSiswa">
            <input type="radio" name="tipe_anggota" value="siswa" id="radioSiswa" <?= ($old['tipe_anggota'] ?? 'siswa') === 'siswa' ? 'checked' : '' ?>>
            <svg class="tipe-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
              <path d="M6 12v5c3 3 9 3 12 0v-5"/>
            </svg>
            <span>Siswa</span>
          </label>
          <label class="tipe-btn <?= ($old['tipe_anggota'] ?? 'siswa') === 'guru' ? 'active' : '' ?>" id="btnTipeGuru">
            <input type="radio" name="tipe_anggota" value="guru" id="radioGuru" <?= ($old['tipe_anggota'] ?? 'siswa') === 'guru' ? 'checked' : '' ?>>
            <svg class="tipe-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
              <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
              <circle cx="12" cy="8" r="2.5"/>
              <path d="M9 13a3 3 0 0 1 6 0"/>
            </svg>
            <span>Guru / Pendidik</span>
          </label>
        </div>
      </div>

      <div class="field">
        <label>Nama Lengkap</label>
        <input type="text" name="full_name" placeholder="Contoh: Budi Santoso"
               value="<?= htmlspecialchars($old['full_name']) ?>" required>
      </div>

      <div class="row2">
        <div class="field">
          <label id="lblNik"><?= ($old['tipe_anggota'] ?? 'siswa') === 'guru' ? 'NIP / NUPTK / NIK Guru' : 'Nomor Induk Siswa (NIS)' ?></label>
          <input type="text" name="nik" id="inputNik" placeholder="<?= ($old['tipe_anggota'] ?? 'siswa') === 'guru' ? 'Contoh: 198503152010011005' : 'Contoh: 212210045' ?>"
                 pattern="<?= ($old['tipe_anggota'] ?? 'siswa') === 'guru' ? '.{3,30}' : '[0-9]{3,20}' ?>"
                 title="<?= ($old['tipe_anggota'] ?? 'siswa') === 'guru' ? 'NIP / NIK minimal 3 karakter' : 'NIS harus berupa angka (minimal 3 digit)' ?>"
                 value="<?= htmlspecialchars($old['nik']) ?>" required>
        </div>
        <div class="field" id="fieldKelasWrap" style="<?= ($old['tipe_anggota'] ?? 'siswa') === 'guru' ? 'display:none;' : '' ?>">
          <label>Pilihan Kelas</label>
          <div class="select-wrap">
            <select name="kelas" id="selectKelas" <?= ($old['tipe_anggota'] ?? 'siswa') === 'guru' ? '' : 'required' ?>>
              <option value="" disabled <?= empty($old['kelas']) || $old['kelas'] === 'Guru' ? 'selected' : '' ?>>-- Pilih Kelas --</option>
              <?php foreach ($daftar_kelas as $k): ?>
                <option value="<?= htmlspecialchars($k) ?>" <?= $old['kelas'] === $k ? 'selected' : '' ?>>
                  <?= htmlspecialchars($k) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <svg class="select-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="m6 9 6 6 6-6"/>
            </svg>
          </div>
        </div>
        <div class="field" id="fieldGuruInfoWrap" style="<?= ($old['tipe_anggota'] ?? 'siswa') === 'guru' ? 'display:block;' : 'display:none;' ?>">
          <label>Status Keanggotaan</label>
          <div class="guru-status-pill">
            <svg class="guru-pill-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
            <span>Tenaga Pendidik / Guru</span>
          </div>
        </div>
      </div>

      <div class="field">
        <label>Nomor HP / WhatsApp <span class="badge-req">(Wajib)</span></label>
        <input type="tel" name="no_hp" placeholder="Contoh: 081234567890"
               pattern="[\d+\-\s]{8,20}" title="Nomor HP minimal 8 digit angka"
               value="<?= htmlspecialchars($old['no_hp']) ?>" required>
      </div>

      <div class="field">
        <label id="lblEmail"><?= ($old['tipe_anggota'] ?? 'siswa') === 'guru' ? 'Email Guru' : 'Email Siswa' ?></label>
        <input type="email" name="email" id="inputEmail" placeholder="<?= ($old['tipe_anggota'] ?? 'siswa') === 'guru' ? 'guru@smkn1rongga.sch.id atau nama@gmail.com' : 'nama@student.smkn1rongga.sch.id atau nama@gmail.com' ?>"
               pattern="[a-zA-Z0-9._%+\-]+@((student\.)?smkn1rongga\.sch\.id|gmail\.com)$"
               title="Email dapat menggunakan akun @student.smkn1rongga.sch.id, @smkn1rongga.sch.id, atau @gmail.com"
               value="<?= htmlspecialchars($old['email']) ?>" required>
        <p class="hint-email" id="hintEmail">
          Dapat menggunakan akun resmi sekolah (<b>@student.smkn1rongga.sch.id</b> / <b>@smkn1rongga.sch.id</b>) atau <b>@gmail.com</b>
        </p>
      </div>

      <div class="row2">
        <div class="field">
          <label>Buat Kata Sandi</label>
          <div class="input-wrap" id="pwWrap1">
            <input type="password" name="password" id="pwInput1" placeholder="Min. 8 karakter (huruf &amp; angka)" autocomplete="new-password" required>
            <span class="toggle-eye" data-target="pwInput1" data-wrap="pwWrap1" role="button" tabindex="0" aria-label="Tampilkan/sembunyikan kata sandi">
              <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a20.3 20.3 0 0 1-2.61 3.61M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
            </span>
          </div>
        </div>
        <div class="field">
          <label>Konfirmasi Kata Sandi</label>
          <div class="input-wrap" id="pwWrap2">
            <input type="password" name="password_confirm" id="pwInput2" placeholder="Ulangi kata sandi" autocomplete="new-password" required>
            <span class="toggle-eye" data-target="pwInput2" data-wrap="pwWrap2" role="button" tabindex="0" aria-label="Tampilkan/sembunyikan kata sandi">
              <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a20.3 20.3 0 0 1-2.61 3.61M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
            </span>
          </div>
        </div>
      </div>

      <p class="hint">Username dibuat otomatis oleh sistem. Kata sandi kamu tentukan sendiri &mdash; ingat baik-baik, karena kata sandi ini juga tercetak pada kartu anggota yang bisa kamu unduh/cetak setelah pendaftaran.</p>

      <button type="submit" class="btn-primary">Daftar &amp; Buat Kartu</button>
    </form>

    <a href="sign_in.php" class="back-link">&larr; Sudah punya akun? Masuk di sini</a>
  </div>

  <div class="right">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="right-content">
      <h2 class="right-title">Satu Kartu,<br>Semua Akses</h2>
      <p class="right-sub">Kartu anggota digital yang bisa<br>langsung diunduh dan dipakai.</p>
      <ul class="right-list">
        <li>Login ke akun anggota</li>
        <li>Pinjam buku perpustakaan</li>
        <li>Kartu bisa diunduh sebagai gambar</li>
      </ul>
    </div>
  </div>

</div>

<script>
  // ─── Preview foto profil (opsional, dari kamera atau galeri) ───
  var fotoInput       = document.getElementById('fotoInput');
  var savedFotoInput  = document.getElementById('savedFotoInput');
  var fotoPreviewWrap = document.getElementById('fotoPreviewWrap');
  var fotoPreviewImg  = document.getElementById('fotoPreviewImg');
  var fotoPlaceholder = document.getElementById('fotoPlaceholder');
  var fotoHintText    = document.getElementById('fotoHintText');
  var wrapHapusFoto   = document.getElementById('wrapHapusFoto');
  var btnHapusFoto    = document.getElementById('btnHapusFoto');
  var fotoError       = document.getElementById('fotoError');

  fotoInput.addEventListener('change', function (e) {
    var file = e.target.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
      if (fotoError) {
        fotoError.textContent = 'Ukuran foto maksimal 5MB.';
        fotoError.classList.add('show');
      }
      fotoInput.value = '';
      return;
    }
    if (fotoError) fotoError.classList.remove('show');
    var reader = new FileReader();
    reader.onload = function (ev) {
      fotoPreviewImg.src = ev.target.result;
      fotoPreviewImg.style.display = 'block';
      fotoPlaceholder.style.display = 'none';
      fotoPreviewWrap.classList.add('has-photo');
      if (wrapHapusFoto) wrapHapusFoto.style.display = 'block';
      if (fotoHintText) fotoHintText.textContent = 'Foto terpilih! Ketuk lingkaran untuk mengganti.';
    };
    reader.readAsDataURL(file);
  });

  if (btnHapusFoto) {
    btnHapusFoto.addEventListener('click', function (e) {
      e.stopPropagation();
      fotoInput.value = '';
      if (savedFotoInput) savedFotoInput.value = '';
      fotoPreviewImg.src = '';
      fotoPreviewImg.style.display = 'none';
      fotoPlaceholder.style.display = 'flex';
      fotoPreviewWrap.classList.remove('has-photo');
      wrapHapusFoto.style.display = 'none';
      if (fotoError) fotoError.classList.remove('show');
      if (fotoHintText) fotoHintText.textContent = 'Ketuk lingkaran di samping untuk ambil foto dari kamera atau pilih dari galeri (opsional).';
    });
  }

  // Dynamic switch antara Siswa dan Guru
  var radioSiswa   = document.getElementById('radioSiswa');
  var radioGuru    = document.getElementById('radioGuru');
  var btnTipeSiswa = document.getElementById('btnTipeSiswa');
  var btnTipeGuru  = document.getElementById('btnTipeGuru');
  var lblNik       = document.getElementById('lblNik');
  var inputNik     = document.getElementById('inputNik');
  var wrapKelas    = document.getElementById('fieldKelasWrap');
  var selectKelas  = document.getElementById('selectKelas');
  var wrapGuru     = document.getElementById('fieldGuruInfoWrap');
  var lblEmail     = document.getElementById('lblEmail');
  var inputEmail   = document.getElementById('inputEmail');
  var hintEmail    = document.getElementById('hintEmail');

  function updateTipeUI() {
    if (!radioSiswa || !radioGuru) return;
    var isGuru = radioGuru.checked;
    if (isGuru) {
      if (btnTipeGuru) btnTipeGuru.classList.add('active');
      if (btnTipeSiswa) btnTipeSiswa.classList.remove('active');

      if (lblNik) lblNik.textContent = 'NIP / NUPTK / NIK Guru';
      if (inputNik) {
        inputNik.placeholder = 'Contoh: 198503152010011005';
        inputNik.title = 'NIP / NIK Guru minimal 3 karakter';
        inputNik.pattern = '.{3,30}';
      }
      if (wrapKelas) wrapKelas.style.display = 'none';
      if (selectKelas) selectKelas.removeAttribute('required');
      if (wrapGuru) wrapGuru.style.display = 'block';
      if (lblEmail) lblEmail.textContent = 'Email Guru';
      if (inputEmail) inputEmail.placeholder = 'guru@smkn1rongga.sch.id atau nama@gmail.com';
      if (hintEmail) hintEmail.innerHTML = 'Dapat menggunakan akun resmi sekolah (<b>@smkn1rongga.sch.id</b>) atau <b>@gmail.com</b>';
    } else {
      if (btnTipeSiswa) btnTipeSiswa.classList.add('active');
      if (btnTipeGuru) btnTipeGuru.classList.remove('active');

      if (lblNik) lblNik.textContent = 'Nomor Induk Siswa (NIS)';
      if (inputNik) {
        inputNik.placeholder = 'Contoh: 212210045';
        inputNik.title = 'NIS harus berupa angka (minimal 3 digit)';
        inputNik.pattern = '[0-9]{3,20}';
      }
      if (wrapKelas) wrapKelas.style.display = 'block';
      if (selectKelas) selectKelas.setAttribute('required', 'required');
      if (wrapGuru) wrapGuru.style.display = 'none';
      if (lblEmail) lblEmail.textContent = 'Email Siswa';
      if (inputEmail) inputEmail.placeholder = 'nama@student.smkn1rongga.sch.id atau nama@gmail.com';
      if (hintEmail) hintEmail.innerHTML = 'Dapat menggunakan akun resmi sekolah (<b>@student.smkn1rongga.sch.id</b>) atau <b>@gmail.com</b>';
    }
  }

  if (radioSiswa && radioGuru) {
    radioSiswa.addEventListener('change', updateTipeUI);
    radioGuru.addEventListener('change', updateTipeUI);
    if (btnTipeSiswa) btnTipeSiswa.addEventListener('click', function() { radioSiswa.checked = true; updateTipeUI(); });
    if (btnTipeGuru) btnTipeGuru.addEventListener('click', function() { radioGuru.checked = true; updateTipeUI(); });
    updateTipeUI();
  }

  // Validasi sebelum submit agar input salah tidak langsung merefresh halaman dan menghilangkan foto
  document.getElementById('signupForm').addEventListener('submit', function (e) {
    var pw1 = document.getElementById('pwInput1').value;
    var pw2 = document.getElementById('pwInput2').value;
    var noHp = document.querySelector('input[name="no_hp"]').value.trim();

    if (!noHp) {
      e.preventDefault();
      alert('Nomor HP / WhatsApp wajib diisi.');
      document.querySelector('input[name="no_hp"]').focus();
      return;
    }
    if (pw1.length < 8 || !/[A-Za-z]/.test(pw1) || !/[0-9]/.test(pw1)) {
      e.preventDefault();
      alert('Kata sandi minimal 8 karakter dan harus mengandung kombinasi huruf serta angka.');
      document.getElementById('pwInput1').focus();
      return;
    }
    if (pw1 !== pw2) {
      e.preventDefault();
      alert('Konfirmasi kata sandi tidak sama dengan kata sandi.');
      document.getElementById('pwInput2').focus();
      return;
    }
  });

  document.querySelectorAll('.toggle-eye').forEach(function (toggle) {
    var input = document.getElementById(toggle.dataset.target);
    var wrap  = document.getElementById(toggle.dataset.wrap);
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

  // Mode gelap / terang
  (function () {
    var btn  = document.getElementById('btnMode');
    if (!btn) return;
    var root = document.documentElement;
    var STORAGE_KEY = 'aksanova_theme';

    function updatePressed() {
      btn.setAttribute('aria-pressed', root.classList.contains('theme-light') ? 'true' : 'false');
    }
    updatePressed();

    btn.addEventListener('click', function () {
      root.classList.toggle('theme-light');
      var isLight = root.classList.contains('theme-light');
      try { localStorage.setItem(STORAGE_KEY, isLight ? 'light' : 'dark'); } catch (e) {}
      updatePressed();
    });
  })();
</script>
</body>
</html>