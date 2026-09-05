<?php
// lupa_kartu.php — Alur "Lupa Kartu": membekukan kartu/akun lama lalu membuat
// password baru sendiri, kemudian menerbitkan kartu anggota pengganti.
//
// Alasan memakai EMAIL (bukan username) + password lama sebagai verifikasi:
// username, NIK, dan password lama semuanya TERCETAK di kartu fisik, jadi kalau
// hanya mengandalkan itu, orang yang menemukan kartu juga bisa memakainya.
// Email terdaftar tidak dicetak di kartu, jadi dipakai sebagai faktor tambahan.
session_start();
require_once "db.php";

$step  = ($_SESSION["lupa_kartu"]["step"] ?? 0) === 2 ? 2 : 1;
$error = "";
$info  = $_SESSION["lupa_kartu"]["full_name"] ?? "";

$action = $_POST["action"] ?? "";

// ─────────────────────────────────────────────
//  STEP 1 — verifikasi pemilik akun & bekukan kartu lama
// ─────────────────────────────────────────────
if ($action === "verify" && ($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    $email    = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($email === "" || $password === "") {
        $error = "Email dan kata sandi lama wajib diisi.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, full_name, nik, kelas, no_hp, email, no_anggota, username, password, foto, role, status FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$user || $user["role"] !== "member" || !password_verify($password, $user["password"])) {
            $error = "Email atau kata sandi lama tidak cocok.";
        } elseif ($user["status"] === "pending") {
            $error = "Akun kamu masih menunggu persetujuan admin, belum bisa memakai fitur ini.";
        } elseif ($user["status"] === "rejected") {
            $error = "Pendaftaran kamu ditolak oleh admin. Silakan hubungi petugas perpustakaan.";
        } else {
            // Bekukan kartu/akun lama supaya kartu yang hilang tidak bisa dipakai login
            $freeze = mysqli_prepare($conn, "UPDATE users SET card_status='frozen' WHERE id=?");
            mysqli_stmt_bind_param($freeze, "i", $user["id"]);
            mysqli_stmt_execute($freeze);
            mysqli_stmt_close($freeze);

            $_SESSION["lupa_kartu"] = [
                "step"       => 2,
                "id"         => $user["id"],
                "full_name"  => $user["full_name"],
                "nik"        => $user["nik"],
                "kelas"      => $user["kelas"],
                "no_hp"      => $user["no_hp"],
                "email"      => $user["email"],
                "no_anggota" => $user["no_anggota"],
                "username"   => $user["username"],
                "foto"       => $user["foto"],
            ];
            header("Location: lupa_kartu.php");
            exit;
        }
    }
}

// ─────────────────────────────────────────────
//  Batalkan proses — cairkan kembali kartu lama
// ─────────────────────────────────────────────
if ($action === "cancel" && ($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    if (isset($_SESSION["lupa_kartu"]["id"])) {
        $uid = (int)$_SESSION["lupa_kartu"]["id"];
        $unfreeze = mysqli_prepare($conn, "UPDATE users SET card_status='active' WHERE id=?");
        mysqli_stmt_bind_param($unfreeze, "i", $uid);
        mysqli_stmt_execute($unfreeze);
        mysqli_stmt_close($unfreeze);
    }
    unset($_SESSION["lupa_kartu"]);
    header("Location: sign_in.php");
    exit;
}

// ─────────────────────────────────────────────
//  STEP 2 — buat password baru sendiri & terbitkan kartu pengganti
// ─────────────────────────────────────────────
if ($action === "set_password" && ($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    if (!isset($_SESSION["lupa_kartu"]["id"]) || $_SESSION["lupa_kartu"]["step"] !== 2) {
        header("Location: lupa_kartu.php");
        exit;
    }

    $new_password    = $_POST["password"] ?? "";
    $confirm_password = $_POST["password_confirm"] ?? "";

    if (strlen($new_password) < 8) {
        $error = "Kata sandi baru minimal 8 karakter.";
    } elseif (!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
        $error = "Kata sandi baru harus mengandung huruf dan angka.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Konfirmasi kata sandi baru tidak sama.";
    } else {
        $sess   = $_SESSION["lupa_kartu"];
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($conn, "UPDATE users SET password = ?, card_status = 'active' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $hashed, $sess["id"]);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Tampilkan kartu anggota pengganti (sekali tampil), pakai halaman yang sama
        // dengan kartu hasil pendaftaran.
        $_SESSION["kartu_data"] = [
            "id"         => $sess["id"],
            "full_name"  => $sess["full_name"],
            "nik"        => $sess["nik"],
            "kelas"      => $sess["kelas"],
            "no_hp"      => $sess["no_hp"],
            "email"      => $sess["email"],
            "no_anggota" => $sess["no_anggota"],
            "username"   => $sess["username"],
            "password"   => $new_password,
            "foto"       => $sess["foto"],
            "status"     => "approved",
            "reissued"   => true,
        ];

        unset($_SESSION["lupa_kartu"]);
        header("Location: kartu_anggota.php");
        exit;
    }
}

$page_title = "Lupa Kartu – AKSA NOVA";
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
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Outfit', sans-serif;
      background: linear-gradient(135deg, #090c10 0%, #0e1318 50%, #090c10 100%);
      background-size: 300% 300%;
      animation: bgShift 10s ease infinite;
      padding: 24px 0;
    }

    @keyframes bgShift {
      0%,100% { background-position: 0% 50%; }
      50%      { background-position: 100% 50%; }
    }

    .card {
      width: 880px;
      max-width: calc(100vw - 24px);
      min-height: 540px;
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
      flex: 0 0 54%;
      background: #10151b;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 52px 56px;
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
      margin-bottom: 8px;
      letter-spacing: -0.5px;
    }

    .form-sub {
      font-size: .82rem;
      color: rgba(238,243,244,.55);
      font-weight: 300;
      margin-bottom: 22px;
      line-height: 1.6;
    }

    .steps {
      display: flex;
      gap: 8px;
      margin-bottom: 22px;
    }
    .step-dot {
      flex: 1;
      height: 4px;
      border-radius: 4px;
      background: rgba(255,255,255,.1);
    }
    .step-dot.active { background: #d8b878; }

    .error-msg {
      width: 100%;
      background: rgba(192,57,43,.15);
      border: 1px solid rgba(192,57,43,.5);
      color: #e07070;
      font-size: .8rem;
      padding: 10px 14px;
      border-radius: var(--radius-md);
      margin-bottom: 14px;
      line-height: 1.5;
    }

    .frozen-notice {
      background: rgba(55,48,163,.18);
      border: 1px solid rgba(99,102,241,.35);
      color: #a5b4fc;
      font-size: .8rem;
      padding: 10px 14px;
      border-radius: var(--radius-md);
      margin-bottom: 18px;
      line-height: 1.6;
    }

    .field { margin-bottom: 14px; }

    .field label {
      display: block;
      font-size: .75rem;
      color: rgba(238,243,244,.55);
      margin-bottom: 6px;
      font-weight: 500;
    }

    .input-wrap { position: relative; display: flex; align-items: center; width: 100%; }

    .input-wrap input {
      width: 100%;
      padding: 13px 44px 13px 16px;
      border: 1.5px solid rgba(216,184,120,.15);
      border-radius: var(--radius-md);
      background: rgba(255,255,255,.05);
      font-family: 'Outfit', sans-serif;
      font-size: .86rem;
      color: #eef3f4;
      outline: none;
      transition: background var(--trans), border-color var(--trans), box-shadow var(--trans);
    }

    .input-wrap input::placeholder { color: rgba(238,243,244,.35); }

    .input-wrap input:focus {
      background: rgba(216,184,120,.08);
      border-color: #d8b878;
      box-shadow: 0 0 0 3px rgba(216,184,120,.2);
    }

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

    .hint { font-size: .68rem; color: rgba(238,243,244,.4); margin-top: 4px; line-height: 1.5; }

    .btn-primary {
      width: 100%;
      max-width: 240px;
      padding: 13px 0;
      margin-top: 6px;
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

    .btn-text {
      background: none;
      border: none;
      font-family: 'Outfit', sans-serif;
      font-size: .78rem;
      color: rgba(238,243,244,.5);
      cursor: pointer;
      text-decoration: none;
      padding: 0;
    }
    .btn-text:hover { color: #e07070; text-decoration: underline; }

    .actions-row {
      display: flex;
      align-items: center;
      gap: 18px;
      margin-top: 14px;
      flex-wrap: wrap;
    }

    .back-link {
      display: inline-block;
      margin-top: 18px;
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
      font-size: 2.2rem;
      font-weight: 700;
      color: #d8b878;
      line-height: 1.15;
      margin-bottom: 14px;
    }

    .right-sub {
      font-size: .84rem;
      font-weight: 300;
      color: rgba(238,243,244,.68);
      line-height: 1.85;
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
        min-height: unset;
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
      .btn-secondary {
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
      .input-wrap input {
        padding: 11px 13px 11px 40px;
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
    html.theme-light .field input {
      background: #fdfbf7;
      border-color: rgba(154,115,40,.22);
      color: #1a1714;
    }
    html.theme-light .field input::placeholder {
      color: #9c9489;
    }
    html.theme-light .field input:focus {
      background: #ffffff;
      border-color: #9a7328;
      box-shadow: 0 0 0 3px rgba(154,115,40,.18);
    }
    html.theme-light .hint {
      color: #7b7163;
    }
    html.theme-light .toggle-eye {
      color: #9c9489;
    }
    html.theme-light .toggle-eye:hover {
      color: #9a7328;
    }
    html.theme-light .back-link {
      color: #6b645b;
    }
    html.theme-light .back-link:hover {
      color: #9a7328;
    }
    html.theme-light .btn-primary {
      background: linear-gradient(135deg, #d8b878, #caa055);
      color: #1a1205;
      box-shadow: 0 6px 20px rgba(154,115,40,.25);
    }
    html.theme-light .btn-primary:hover {
      box-shadow: 0 8px 28px rgba(154,115,40,.38);
    }
    html.theme-light .btn-text {
      color: #7b7163;
    }
    html.theme-light .btn-text:hover {
      color: #b91c1c;
    }
    html.theme-light .step-dot {
      background: rgba(0,0,0,.08);
    }
    html.theme-light .step-dot.active {
      background: #9a7328;
    }
    html.theme-light .frozen-notice {
      background: rgba(245,158,11,.1);
      border-color: rgba(245,158,11,.3);
      color: #92400e;
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
    html.theme-light .error-msg {
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
    <div class="steps">
      <div class="step-dot active"></div>
      <div class="step-dot <?= $step === 2 ? 'active' : '' ?>"></div>
    </div>

    <?php if ($step === 1): ?>
      <h1 class="form-title">Lupa Kartu</h1>
      <p class="form-sub">Kartu anggota hilang atau ditemukan orang lain? Verifikasi dulu identitasmu, kartu lama akan langsung dibekukan supaya tidak bisa dipakai login.</p>

      <?php if ($error): ?><div class="error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <form method="POST" action="lupa_kartu.php">
        <input type="hidden" name="action" value="verify">
        <div class="field">
          <label>Email Terdaftar</label>
          <input type="email" name="email" placeholder="nama@email.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="field">
          <label>Kata Sandi Lama</label>
          <div class="input-wrap" id="pwWrap1">
            <input type="password" name="password" id="pwInput1" placeholder="Kata sandi yang tertera di kartu lama" autocomplete="current-password" required>
            <span class="toggle-eye" data-target="pwInput1" data-wrap="pwWrap1" role="button" tabindex="0" aria-label="Tampilkan/sembunyikan kata sandi">
              <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a20.3 20.3 0 0 1-2.61 3.61M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
            </span>
          </div>
          <p class="hint">Kamu tetap perlu tahu kata sandi lama sebagai bukti pemilik akun.</p>
        </div>

        <button type="submit" class="btn-primary">Bekukan Kartu Lama &amp; Lanjut</button>
      </form>

      <a href="sign_in.php" class="back-link">&larr; Kembali ke halaman masuk</a>

    <?php else: ?>
      <h1 class="form-title">Buat Kata Sandi Baru</h1>
      <p class="form-sub">Kartu lama atas nama <b><?= htmlspecialchars($info) ?></b> sudah dibekukan. Buat kata sandi baru sendiri, lalu kartu penggantimu akan langsung diterbitkan.</p>

      <div class="frozen-notice">🔒 Kartu lama sedang dibekukan sementara sampai kamu menyelesaikan langkah ini.</div>

      <?php if ($error): ?><div class="error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <form method="POST" action="lupa_kartu.php">
        <input type="hidden" name="action" value="set_password">
        <div class="field">
          <label>Kata Sandi Baru</label>
          <div class="input-wrap" id="pwWrap2">
            <input type="password" name="password" id="pwInput2" placeholder="Minimal 8 karakter, huruf & angka" autocomplete="new-password" required>
            <span class="toggle-eye" data-target="pwInput2" data-wrap="pwWrap2" role="button" tabindex="0" aria-label="Tampilkan/sembunyikan kata sandi">
              <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a20.3 20.3 0 0 1-2.61 3.61M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
            </span>
          </div>
        </div>
        <div class="field">
          <label>Konfirmasi Kata Sandi Baru</label>
          <div class="input-wrap" id="pwWrap3">
            <input type="password" name="password_confirm" id="pwInput3" placeholder="Ulangi kata sandi baru" autocomplete="new-password" required>
            <span class="toggle-eye" data-target="pwInput3" data-wrap="pwWrap3" role="button" tabindex="0" aria-label="Tampilkan/sembunyikan kata sandi">
              <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a20.3 20.3 0 0 1-2.61 3.61M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
            </span>
          </div>
        </div>

        <div class="actions-row">
          <button type="submit" class="btn-primary">Simpan &amp; Terbitkan Kartu Baru</button>
        </div>
      </form>

      <form method="POST" action="lupa_kartu.php" style="margin-top:10px;">
        <input type="hidden" name="action" value="cancel">
        <button type="submit" class="btn-text">Batalkan, cairkan kembali kartu lama</button>
      </form>
    <?php endif; ?>
  </div>

  <div class="right">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="right-content">
      <h2 class="right-title">Kartu Hilang?<br>Aman Kok.</h2>
      <p class="right-sub">Kartu lama langsung dibekukan begitu<br>kamu memulai proses ini.</p>
      <ul class="right-list">
        <li>Kartu lama tidak bisa dipakai login lagi</li>
        <li>Kamu buat sendiri password barunya</li>
        <li>Kartu pengganti langsung bisa diunduh &amp; dicetak</li>
      </ul>
    </div>
  </div>
</div>

<script>
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
