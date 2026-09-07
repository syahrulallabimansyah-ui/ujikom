<?php
// sign_up.php — Pendaftaran anggota mandiri + pembuatan kartu anggota otomatis
session_start();
require_once "db.php";

// Jika sudah login, langsung arahkan ke halaman utama yang sesuai
if (isset($_SESSION["user_id"])) {
    if (($_SESSION["role"] ?? "") === "admin") {
        header("Location: halaman_admin.php");
    } else {
        header("Location: beranda.php");
    }
    exit;
}

$errors = [];
$old = [
    "full_name" => "",
    "kelas"     => "",
    "no_hp"     => "",
    "email"     => "",
];

// Ambil daftar kelas dari tabel kelas untuk opsi dropdown
$daftar_kelas = [];
$res_k = mysqli_query($conn, "SELECT nama_kelas FROM kelas ORDER BY nama_kelas ASC");
if ($res_k) {
    while ($row_k = mysqli_fetch_assoc($res_k)) {
        $daftar_kelas[] = $row_k["nama_kelas"];
    }
}

// Folder tempat menyimpan foto profil anggota
$foto_dir = __DIR__ . "/uploads/anggota";
$foto_web_dir = "uploads/anggota"; // path relatif yang disimpan ke DB & dipakai di <img src>

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {

    $old["full_name"] = trim($_POST["full_name"] ?? "");
    $old["kelas"]     = trim($_POST["kelas"] ?? "");
    $old["no_hp"]     = trim($_POST["no_hp"] ?? "");
    $old["email"]     = strtolower(trim($_POST["email"] ?? ""));
    $password         = $_POST["password"] ?? "";
    $password_confirm = $_POST["password_confirm"] ?? "";

    // ── Validasi ──
    if ($old["full_name"] === "") {
        $errors[] = "Nama lengkap wajib diisi.";
    }
    if ($old["kelas"] === "") {
        $errors[] = "Pilihan kelas wajib dipilih.";
    }
    if ($old["email"] === "" || !filter_var($old["email"], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Alamat email tidak valid.";
    } elseif (!str_ends_with(strtolower($old["email"]), "@student.smkn1rongga.sch.id")) {
        $errors[] = "Email wajib menggunakan akun siswa resmi (@student.smkn1rongga.sch.id).";
    }
    if ($old["no_hp"] !== "" && !preg_match('/^[\d+\-\s]{6,20}$/', $old["no_hp"])) {
        $errors[] = "Nomor HP tidak valid.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Kata sandi minimal 8 karakter.";
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = "Kata sandi harus mengandung huruf dan angka.";
    }
    if ($password !== $password_confirm) {
        $errors[] = "Konfirmasi kata sandi tidak sama.";
    }

    // ── Validasi foto profil (wajib, bisa dari kamera langsung atau galeri) ──
    $foto_relative_path = "";
    $foto_error = "";
    if (!isset($_FILES["foto"]) || $_FILES["foto"]["error"] === UPLOAD_ERR_NO_FILE) {
        $foto_error = "Foto profil wajib diunggah (ambil foto langsung atau pilih dari galeri).";
    } elseif ($_FILES["foto"]["error"] !== UPLOAD_ERR_OK) {
        $foto_error = "Gagal mengunggah foto. Silakan coba lagi.";
    } else {
        $foto_tmp  = $_FILES["foto"]["tmp_name"];
        $foto_size = $_FILES["foto"]["size"];
        $mime      = function_exists("mime_content_type") ? mime_content_type($foto_tmp) : $_FILES["foto"]["type"];
        $allowed_mimes = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];

        if (!isset($allowed_mimes[$mime])) {
            $foto_error = "Format foto harus JPG, PNG, atau WEBP.";
        } elseif ($foto_size > 5 * 1024 * 1024) {
            $foto_error = "Ukuran foto maksimal 5MB.";
        }
    }
    if ($foto_error !== "") {
        $errors[] = $foto_error;
    }

    // Cek email belum terdaftar
    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $old["email"]);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Email tersebut sudah terdaftar sebagai anggota.";
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($errors)) {

        // ── Simpan foto profil ke folder uploads ──
        if (!is_dir($foto_dir)) {
            mkdir($foto_dir, 0755, true);
        }
        $ext = $allowed_mimes[$mime];
        $foto_filename = "anggota_" . bin2hex(random_bytes(8)) . "." . $ext;
        if (!move_uploaded_file($foto_tmp, $foto_dir . "/" . $foto_filename)) {
            $errors[] = "Gagal menyimpan foto profil. Silakan coba lagi.";
        } else {
            $foto_relative_path = $foto_web_dir . "/" . $foto_filename;
        }
    }

    if (empty($errors)) {

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
        $empty_nik = "";
        $stmt = mysqli_prepare($conn,
            "INSERT INTO users (full_name, nik, kelas, no_hp, no_anggota, username, email, password, foto, role, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'member', 'approved')"
        );
        mysqli_stmt_bind_param(
            $stmt, "sssssssss",
            $old["full_name"], $empty_nik, $old["kelas"], $old["no_hp"],
            $no_anggota, $username, $old["email"], $hashed, $foto_relative_path
        );

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Simpan sementara di session HANYA untuk ditampilkan sekali di kartu_anggota.php
            // (password asli tidak pernah disimpan di database dalam bentuk plain text)
            $_SESSION["kartu_data"] = [
                "id"         => $new_id,
                "full_name"  => $old["full_name"],
                "nik"        => "",
                "kelas"      => $old["kelas"],
                "no_hp"      => $old["no_hp"],
                "email"      => $old["email"],
                "no_anggota" => $no_anggota,
                "username"   => $username,
                "password"   => $password,
                "foto"       => $foto_relative_path,
                "status"     => "approved",
                "reissued"   => false,
            ];

            header("Location: kartu_anggota.php");
            exit;
        } else {
            $errors[] = "Gagal menyimpan data. Silakan coba lagi.";
            mysqli_stmt_close($stmt);
            if ($foto_relative_path !== "" && file_exists($foto_dir . "/" . basename($foto_relative_path))) {
                unlink($foto_dir . "/" . basename($foto_relative_path));
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
      animation: riseIn .9s cubic-bezier(.22,1,.36,1) both;
    }

    @keyframes riseIn {
      from { opacity:0; transform:translateY(36px) scale(.97); }
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

    .field label {
      display: block;
      font-size: .75rem;
      color: rgba(238,243,244,.55);
      margin-bottom: 6px;
      font-weight: 500;
    }

    .field input,
    .field select {
      width: 100%;
      padding: 12px 16px;
      border: 1.5px solid rgba(216,184,120,.15);
      border-radius: var(--radius-md);
      background: rgba(255,255,255,.05);
      font-family: 'Outfit', sans-serif;
      font-size: .86rem;
      color: #eef3f4;
      outline: none;
      transition: background var(--trans), border-color var(--trans), box-shadow var(--trans);
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

    .field input::placeholder { color: rgba(238,243,244,.35); }

    .hint { font-size: .68rem; color: rgba(238,243,244,.4); margin-top: 4px; }

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
      50%      { transform: translateY(-18px) scale(1.08); }
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
    @media (max-width: 860px) {
      html, body {
        padding: 24px 14px;
        min-height: 100vh;
      }
      .card {
        flex-direction: column;
        width: 100%;
        max-width: 580px;
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
        padding: 36px 28px 28px;
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
        padding: 32px 28px 38px;
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

    @media (max-width: 600px) {
      html, body {
        padding: 16px 10px;
      }
      .card {
        border-radius: 18px;
      }
      .right {
        padding: 26px 18px 22px;
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
        padding: 24px 18px 30px;
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
        gap: 10px;
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
    }

    @media (max-width: 380px) {
      html, body {
        padding: 12px 6px;
      }
      .card {
        border-radius: 16px;
      }
      .right {
        padding: 22px 14px 18px;
      }
      .right-title {
        font-size: 1.4rem;
      }
      .left {
        padding: 20px 14px 26px;
      }
      .form-title {
        font-size: 1.4rem;
      }
      .field input {
        padding: 11px 13px;
        font-size: .84rem;
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
      --dim:       #6b645b;
      --ghost:     #9c9489;
      --surface:   #ffffff;
      --field:     rgba(0,0,0,.04);
      --field-foc: rgba(0,0,0,.07);
      --panel-bg:  linear-gradient(148deg, #f5efe6 0%, #ece2d0 50%, #f9f5ee 100%);
      --accent:    #9a7328;
      --ring:      rgba(154,115,40,.28);
      --shadow:    0 32px 80px rgba(70,50,20,.14);
    }
    html.theme-light, html.theme-light body {
      background: linear-gradient(135deg, #f6f2e9 0%, #ece4d4 50%, #f7f3ec 100%);
    }
    html.theme-light .btn-mode {
      background: rgba(255,255,255,.85);
      border-color: rgba(154,115,40,.3);
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
      border-color: rgba(154,115,40,.2);
      box-shadow: 0 24px 60px rgba(60,45,20,.12);
    }
    html.theme-light .left {
      background: #ffffff;
    }
    html.theme-light .form-title {
      color: #8a6323;
    }
    html.theme-light .form-sub {
      color: #6b645b;
    }
    html.theme-light .field label {
      color: #3b352b;
    }
    html.theme-light .field input,
    html.theme-light .field select {
      background: #fdfbf7;
      border-color: rgba(154,115,40,.22);
      color: #1a1714;
    }
    html.theme-light .field select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239a7328' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    }
    html.theme-light .field select option {
      background: #ffffff;
      color: #1a1714;
    }
    html.theme-light .field input::placeholder {
      color: #9c9489;
    }
    html.theme-light .field input:focus,
    html.theme-light .field select:focus {
      background: #ffffff;
      border-color: #9a7328;
      box-shadow: 0 0 0 3px rgba(154,115,40,.18);
    }
    html.theme-light .field-icon {
      color: #9c9489;
    }
    html.theme-light .field:focus-within .field-icon {
      color: #9a7328;
    }
    html.theme-light .toggle-eye {
      color: #9c9489;
    }
    html.theme-light .toggle-eye:hover {
      color: #9a7328;
    }
    html.theme-light .foto-info-text {
      color: #3b352b;
    }
    html.theme-light .foto-hint {
      color: #6b645b;
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
      color: #6b645b;
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
      color: #6b645b;
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
        <div class="foto-preview-wrap" id="fotoPreviewWrap" onclick="document.getElementById('fotoInput').click()">
          <img id="fotoPreviewImg" src="" alt="Pratinjau foto profil">
          <div class="foto-placeholder" id="fotoPlaceholder">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
              <circle cx="12" cy="13" r="4"/>
            </svg>
            <span>Foto Profil</span>
          </div>
        </div>
        <div>
          <div class="foto-info-text">Foto Profil untuk Kartu Anggota</div>
          <p class="foto-hint">Ketuk lingkaran di samping untuk ambil foto langsung dari kamera atau pilih dari galeri. Foto ini akan tampil di kartu anggota kamu.</p>
          <p class="foto-error" id="fotoError">Foto profil wajib diunggah.</p>
        </div>
        <input type="file" name="foto" id="fotoInput" accept="image/*" style="display:none" required>
      </div>

      <div class="field">
        <label>Nama Lengkap</label>
        <input type="text" name="full_name" placeholder="Contoh: Budi Santoso"
               value="<?= htmlspecialchars($old['full_name']) ?>" required>
      </div>

      <div class="row2">
        <div class="field">
          <label>Pilihan Kelas</label>
          <select name="kelas" required>
            <option value="" disabled <?= empty($old['kelas']) ? 'selected' : '' ?>>-- Pilih Kelas --</option>
            <?php foreach ($daftar_kelas as $k): ?>
              <option value="<?= htmlspecialchars($k) ?>" <?= $old['kelas'] === $k ? 'selected' : '' ?>>
                <?= htmlspecialchars($k) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Nomor HP <span style="font-size:.72rem;font-weight:400;color:var(--dim,#9c9489);">(Opsional)</span></label>
          <input type="text" name="no_hp" placeholder="08xxxxxxxxxx"
                 value="<?= htmlspecialchars($old['no_hp']) ?>">
        </div>
      </div>

      <div class="field">
        <label>Email Siswa (@student.smkn1rongga.sch.id)</label>
        <input type="email" name="email" placeholder="nama@student.smkn1rongga.sch.id"
               pattern="[a-zA-Z0-9._%+\-]+@student\.smkn1rongga\.sch\.id$"
               title="Email harus menggunakan domain @student.smkn1rongga.sch.id"
               value="<?= htmlspecialchars($old['email']) ?>" required>
        <p class="hint" style="color:var(--accent,#d8b878); margin-top:5px; font-size:.72rem;">
          Wajib menggunakan akun email resmi sekolah berakhiran <b>@student.smkn1rongga.sch.id</b>
        </p>
      </div>

      <div class="row2">
        <div class="field">
          <label>Buat Kata Sandi</label>
          <div class="input-wrap" id="pwWrap1">
            <input type="password" name="password" id="pwInput1" placeholder="Minimal 8 karakter, huruf & angka" autocomplete="new-password" required>
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
  // ─── Preview foto profil (dari kamera atau galeri) ───
  var fotoInput       = document.getElementById('fotoInput');
  var fotoPreviewWrap = document.getElementById('fotoPreviewWrap');
  var fotoPreviewImg  = document.getElementById('fotoPreviewImg');
  var fotoPlaceholder = document.getElementById('fotoPlaceholder');
  var fotoError       = document.getElementById('fotoError');

  fotoInput.addEventListener('change', function (e) {
    var file = e.target.files[0];
    if (!file) return;
    fotoError.classList.remove('show');
    var reader = new FileReader();
    reader.onload = function (ev) {
      fotoPreviewImg.src = ev.target.result;
      fotoPreviewImg.style.display = 'block';
      fotoPlaceholder.style.display = 'none';
      fotoPreviewWrap.classList.add('has-photo');
    };
    reader.readAsDataURL(file);
  });

  document.getElementById('signupForm').addEventListener('submit', function (e) {
    if (!fotoInput.files || fotoInput.files.length === 0) {
      e.preventDefault();
      fotoError.classList.add('show');
      fotoPreviewWrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
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