<?php
// sign_in.php — Halaman masuk pengguna
session_start();
require_once "db.php";

// Validasi parameter redirect internal (aman dari Open Redirect)
$raw_redirect  = trim($_POST["redirect"] ?? ($_GET["redirect"] ?? ""));
$safe_redirect = "";
if ($raw_redirect !== "" && preg_match('/^[a-zA-Z0-9_]+\.php(\?[a-zA-Z0-9_=&%-]*)?$/', $raw_redirect)) {
    $redir_base = strtolower(strtok($raw_redirect, '?'));
    if (!in_array($redir_base, ["sign_in.php", "sign_up.php", "logout.php"], true)) {
        $safe_redirect = $raw_redirect;
    }
}

// Jika sudah login, langsung arahkan ke dashboard/beranda masing-masing role
if (isset($_SESSION["user_id"])) {
    if (($_SESSION["role"] ?? "") === "admin") {
        header("Location: dashboard.php");
    } else {
        header("Location: " . ($safe_redirect !== "" ? $safe_redirect : "beranda.php"));
    }
    exit;
}

$error = "";

// Proses login saat form dikirim
if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    if (!csrf_verify()) {
        $error = "Token sesi keamanan tidak valid atau kedaluwarsa. Silakan muat ulang halaman.";
    } else {
        $identity = trim($_POST["email"] ?? "");
        $password = trim($_POST["password"] ?? "");

    if (empty($identity) || empty($password)) {
        $error = "Username/Email dan kata sandi wajib diisi.";
    } else {
        // Cari user berdasarkan email ATAU username (sesuai yang tertera di kartu anggota)
        $stmt = mysqli_prepare($conn, "SELECT id, full_name, username, password, role, status, card_status FROM users WHERE email = ? OR username = ?");
        mysqli_stmt_bind_param($stmt, "ss", $identity, $identity);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user["password"])) {
            if ($user["role"] === "member" && $user["status"] === "pending") {
                $error = "Akun kamu masih menunggu persetujuan admin. Silakan coba lagi nanti.";
            } elseif ($user["role"] === "member" && $user["status"] === "rejected") {
                $error = "Pendaftaran kamu ditolak oleh admin. Silakan hubungi petugas perpustakaan.";
            } elseif (($user["card_status"] ?? "active") === "frozen") {
                $error = "Kartu anggota ini sedang dibekukan karena ada proses \"Lupa Kartu\" yang belum selesai. Selesaikan proses tersebut untuk mendapatkan kartu baru.";
            } else {
                // Login berhasil — regenerasi ID sesi & simpan data ke session
                session_regenerate_id(true);
                $_SESSION["user_id"]   = $user["id"];
                $_SESSION["user_name"] = $user["full_name"];
                $_SESSION["username"]  = $user["username"];
                $_SESSION["role"]      = $user["role"];

                if ($user["role"] === "admin") {
                    header("Location: dashboard.php");
                } else {
                    header("Location: " . ($safe_redirect !== "" ? $safe_redirect : "beranda.php"));
                }
                exit;
            }
        } else {
            $error = "Username/Email atau kata sandi salah.";
        }
    }
    }
}

$page_title = "Masuk – AKSA NOVA";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <script>
    (function() {
      try {
        var t = localStorage.getItem('aksanova_theme');
        if (!t) {
          var s = JSON.parse(localStorage.getItem('aksanova_settings') || '{}');
          t = s.mode;
        }
        if (t === 'light') {
          document.documentElement.classList.add('theme-light', 'light');
          document.documentElement.classList.remove('dark');
        } else {
          document.documentElement.classList.add('dark');
          document.documentElement.classList.remove('theme-light', 'light');
        }
      } catch(e) {}
    })();
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
    }

    @keyframes bgShift {
      0%,100% { background-position: 0% 50%; }
      50%      { background-position: 100% 50%; }
    }

    .card {
      width: 880px;
      max-width: calc(100vw - 24px);
      min-height: 560px;
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
      flex: 0 0 54%;
      background: #10151b;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 52px 56px;
      gap: 0;
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
      font-size: 2.4rem;
      font-weight: 700;
      color: #d8b878;
      margin-bottom: 10px;
      letter-spacing: -0.5px;
      animation: fadeSlide .7s .1s both;
    }

    .form-sub {
      font-size: .82rem;
      color: rgba(238,243,244,.78);
      font-weight: 300;
      margin-bottom: 30px;
      animation: fadeSlide .7s .18s both;
    }

    @keyframes fadeSlide {
      from { opacity:0; transform:translateY(12px); }
      to   { opacity:1; transform:translateY(0); }
    }

    /* Pesan error */
    .error-msg {
      width: 100%;
      background: rgba(192,57,43,.15);
      border: 1px solid rgba(192,57,43,.5);
      color: #e07070;
      font-size: .8rem;
      padding: 10px 14px;
      border-radius: var(--radius-md);
      margin-bottom: 14px;
      animation: fadeSlide .4s both;
    }

    .input-group {
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 12px;
      animation: fadeSlide .7s .26s both;
    }

    .input-wrap {
      position: relative;
      display: flex;
      align-items: center;
      width: 100%;
    }

    .input-wrap > svg {
      position: absolute;
      left: 16px;
      width: 17px;
      height: 17px;
      color: #d8b878;
      pointer-events: none;
      transition: color var(--trans);
    }

    .input-wrap input {
      width: 100%;
      padding: 14px 18px 14px 44px;
      border: 1.5px solid rgba(216,184,120,.15);
      border-radius: var(--radius-md);
      background: rgba(255,255,255,.05);
      font-family: 'Outfit', sans-serif;
      font-size: .88rem;
      color: #eef3f4;
      outline: none;
      transition: background var(--trans), border-color var(--trans), box-shadow var(--trans);
    }

    .input-wrap input::placeholder { color: rgba(238,243,244,.55); }

    .input-wrap input:focus {
      background: rgba(216,184,120,.08);
      border-color: #d8b878;
      box-shadow: 0 0 0 3px rgba(216,184,120,.2);
    }

    .input-wrap:focus-within svg { color: #d8b878; }

    /* Toggle lihat password */
    .toggle-eye {
      position: absolute;
      right: 14px;
      left: auto;
      width: 19px;
      height: 19px;
      color: #d8b878;
      cursor: pointer;
      pointer-events: auto;
      transition: color var(--trans);
    }
    .toggle-eye:hover { color: #f0d9a8; }
    .toggle-eye svg { position: static; width: 100%; height: 100%; }
    .toggle-eye .eye-off { display: none; }
    .input-wrap.pw-visible .eye-on  { display: none; }
    .input-wrap.pw-visible .eye-off { display: block; }

    .forgot-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      width: 100%;
      margin-bottom: 24px;
      animation: fadeSlide .7s .32s both;
    }

    .forgot {
      font-size: .8rem;
      color: rgba(238,243,244,.75); font-weight: 500;
      font-weight: 300;
      text-decoration: none;
      transition: color var(--trans);
      white-space: nowrap;
    }
    .forgot:hover { color: #d8b878; text-decoration: underline; }

    .btn-primary {
      width: 100%;
      max-width: 200px;
      padding: 13px 0;
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
      animation: fadeSlide .7s .38s both;
    }

    .btn-primary:hover { box-shadow: 0 8px 28px rgba(216,184,120,.45); transform: translateY(-1px); }
    .btn-primary:active { transform: scale(.97); }

    .btn-guest {
      display: block;
      margin-top: 14px;
      font-size: .78rem;
      color: rgba(238,243,244,.75); font-weight: 600;
      font-weight: 500;
      text-decoration: none;
      text-align: center;
      transition: color var(--trans);
      animation: fadeSlide .7s .42s both;
    }
    .btn-guest:hover { color: #d8b878; text-decoration: underline; }

    .right {
      flex: 1;
      background: linear-gradient(148deg, #0c1008 0%, #16200d 50%, #090c10 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 52px 44px;
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
    .orb-1 { width:160px;height:160px;background:#d8b878;top:10%;left:5%; animation-delay:0s; }
    .orb-2 { width:100px;height:100px;background:#a07840;bottom:15%;right:8%; animation-delay:3s; }
    .orb-3 { width:80px;height:80px;background:#e8c88a;top:55%;left:20%; animation-delay:5s; }

    @keyframes orbFloat {
      0%,100% { transform: translateY(0) scale(1); }
      50%      { transform: translateY(-10px) scale(1.04); }
    }

    .right-content {
      position: relative;
      text-align: center;
      animation: riseIn .65s .1s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    .right-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 2.5rem;
      font-weight: 700;
      color: #d8b878;
      line-height: 1.1;
      margin-bottom: 14px;
    }

    .right-sub {
      font-size: .86rem;
      font-weight: 400;
      color: rgba(238,243,244,.85);
      line-height: 1.8;
      margin-bottom: 38px;
    }

    .btn-outline {
      display: inline-block;
      padding: 11px 38px;
      border: 1.5px solid rgba(216,184,120,.45);
      border-radius: 50px;
      background: transparent;
      color: #d8b878;
      font-family: 'Outfit', sans-serif;
      font-size: .76rem;
      font-weight: 500;
      letter-spacing: .16em;
      text-transform: uppercase;
      text-decoration: none;
      transition: background var(--trans), border-color var(--trans), box-shadow var(--trans), transform .15s;
    }

    .btn-outline:hover {
      background: rgba(216,184,120,.12);
      border-color: rgba(216,184,120,.8);
      box-shadow: 0 6px 20px rgba(216,184,120,.2);
      transform: translateY(-1px);
    }

    .btn-outline:active { transform: scale(.97); }

    @media (max-width: 700px) {
      .card { flex-direction: column; min-height: unset; border-radius: 18px; }
      .left { padding: 40px 28px 36px; flex: none; }
      .right { flex: none; padding: 40px 28px; order: -1; min-height: 200px; }
      .right-title { font-size: 1.8rem; }
      .right-sub   { margin-bottom: 22px; font-size: .82rem; }
      .form-title  { font-size: 1.9rem; }
      .left::before { height: 3px; }
    }

        /* ══════════════════ TOMBOL KEMBALI KE BERANDA ══════════════════ */
    .btn-back-home {
      position: fixed;
      top: 18px;
      left: 18px;
      z-index: 999;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      height: 42px;
      padding: 0 16px;
      border-radius: 50px;
      border: 1px solid rgba(216,184,120,.3);
      background: rgba(16,21,27,.75);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      color: #d8b878;
      text-decoration: none;
      font-size: .84rem;
      font-weight: 600;
      box-shadow: 0 6px 20px rgba(0,0,0,.4);
      transition: background .2s ease, border-color .2s ease, transform .18s ease, box-shadow .2s ease;
    }
    .btn-back-home:hover {
      transform: translateX(-2px);
      border-color: #d8b878;
      background: rgba(216,184,120,.15);
      box-shadow: 0 8px 24px rgba(216,184,120,.3);
      color: #f0d9a8;
    }
    .btn-back-home svg {
      width: 17px;
      height: 17px;
      flex-shrink: 0;
    }
    @media (max-width: 500px) {
      .btn-back-home {
        width: 42px;
        height: 42px;
        padding: 0;
        justify-content: center;
        border-radius: 50%;
      }
      .btn-back-home span {
        display: none;
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
    html.theme-light, html.light {
      --ink:       #1a1714;
      --dim:       #524b42;
      --ghost:     #7a7267;
      --surface:   #ffffff;
      --field:     #fdfbf7;
      --field-foc: #ffffff;
      --panel-bg:  linear-gradient(148deg, #f5efe6 0%, #ece2d0 50%, #f9f5ee 100%);
      --accent:    #8a6323;
      --ring:      rgba(138,99,35,.25);
      --shadow:    0 32px 80px rgba(70,50,20,.14);
    }
    html.theme-light, html.theme-light body,
    html.light, html.light body {
      background: linear-gradient(135deg, #f6f2e9 0%, #ece4d4 50%, #f7f3ec 100%) !important;
      color: #1a1714 !important;
    }
    html.theme-light .btn-back-home,
    html.light .btn-back-home {
      background: rgba(255,255,255,.9) !important;
      border-color: rgba(138,99,35,.35) !important;
      color: #8a6323 !important;
      box-shadow: 0 4px 16px rgba(138,99,35,.15) !important;
    }
    html.theme-light .btn-back-home svg,
    html.light .btn-back-home svg {
      color: #8a6323 !important;
    }
    html.theme-light .btn-back-home:hover,
    html.light .btn-back-home:hover {
      background: rgba(138,99,35,.12) !important;
      border-color: #8a6323 !important;
      color: #5a3c08 !important;
    }
    html.theme-light .btn-mode,
    html.light .btn-mode {
      background: rgba(255,255,255,.9) !important;
      border-color: rgba(138,99,35,.35) !important;
      color: #8a6323 !important;
      box-shadow: 0 6px 20px rgba(60,45,20,.12) !important;
    }
    html.theme-light .btn-mode svg,
    html.light .btn-mode svg {
      color: #8a6323 !important;
    }
    html.theme-light .btn-mode:hover,
    html.light .btn-mode:hover {
      border-color: #8a6323 !important;
      box-shadow: 0 8px 24px rgba(138,99,35,.22) !important;
    }
    html.theme-light .btn-mode .icon-moon,
    html.light .btn-mode .icon-moon { display: none !important; }
    html.theme-light .btn-mode .icon-sun,
    html.light .btn-mode .icon-sun  { display: block !important; }

    html.theme-light .card,
    html.light .card {
      background: #ffffff !important;
      border-color: rgba(138,99,35,.22) !important;
      box-shadow: 0 24px 60px rgba(60,45,20,.12) !important;
    }
    html.theme-light .left,
    html.light .left {
      background: #ffffff !important;
    }
    html.theme-light .form-title,
    html.light .form-title {
      color: #8a6323 !important;
    }
    html.theme-light .form-sub,
    html.light .form-sub {
      color: #524b42 !important;
      font-weight: 400 !important;
    }
    html.theme-light .input-wrap input,
    html.light .input-wrap input {
      background: #fdfbf7 !important;
      border-color: rgba(138,99,35,.28) !important;
      color: #1a1714 !important;
      font-weight: 500 !important;
    }
    html.theme-light .input-wrap input::placeholder,
    html.light .input-wrap input::placeholder {
      color: #6d6457 !important;
      opacity: 1 !important;
    }
    html.theme-light .input-wrap input:focus,
    html.light .input-wrap input:focus {
      background: #ffffff !important;
      border-color: #8a6323 !important;
      box-shadow: 0 0 0 3px rgba(138,99,35,.18) !important;
    }
    html.theme-light .input-wrap > svg,
    html.light .input-wrap > svg {
      color: #8a6323 !important;
      opacity: 1 !important;
    }
    html.theme-light .input-wrap:focus-within > svg,
    html.light .input-wrap:focus-within > svg {
      color: #5a3c08 !important;
    }
    html.theme-light .toggle-eye,
    html.light .toggle-eye {
      color: #8a6323 !important;
      opacity: 1 !important;
    }
    html.theme-light .toggle-eye:hover,
    html.light .toggle-eye:hover {
      color: #5a3c08 !important;
    }
    html.theme-light .forgot,
    html.light .forgot {
      color: #524b42 !important;
      font-weight: 600 !important;
    }
    html.theme-light .forgot:hover,
    html.light .forgot:hover {
      color: #8a6323 !important;
    }
    html.theme-light .btn-primary,
    html.light .btn-primary {
      background: linear-gradient(135deg, #d8b878, #caa055) !important;
      color: #1a1205 !important;
      font-weight: 700 !important;
      box-shadow: 0 6px 20px rgba(138,99,35,.25) !important;
    }
    html.theme-light .btn-primary:hover,
    html.light .btn-primary:hover {
      box-shadow: 0 8px 28px rgba(138,99,35,.38) !important;
    }
    html.theme-light .btn-guest,
    html.light .btn-guest {
      color: #524b42 !important;
      font-weight: 600 !important;
    }
    html.theme-light .btn-guest:hover,
    html.light .btn-guest:hover {
      color: #8a6323 !important;
    }
    html.theme-light .right,
    html.light .right {
      background: linear-gradient(148deg, #f8f4ec 0%, #ece1ce 50%, #f4ede0 100%) !important;
    }
    html.theme-light .right-title,
    html.light .right-title {
      color: #8a6323 !important;
    }
    html.theme-light .right-sub,
    html.light .right-sub {
      color: #524b42 !important;
      font-weight: 400 !important;
    }
    html.theme-light .btn-outline,
    html.light .btn-outline {
      border-color: rgba(138,99,35,.45) !important;
      color: #8a6323 !important;
      font-weight: 600 !important;
    }
    html.theme-light .btn-outline:hover,
    html.light .btn-outline:hover {
      background: rgba(138,99,35,.12) !important;
      border-color: #8a6323 !important;
      color: #5a3c08 !important;
      box-shadow: 0 6px 20px rgba(138,99,35,.2) !important;
    }
    html.theme-light .error-msg,
    html.light .error-msg {
      background: rgba(220,38,38,.08) !important;
      border-color: rgba(220,38,38,.25) !important;
      color: #b91c1c !important;
    }
  </style>
  <?php require_once 'settings_include.php'; ?>
</head>
<body>

<!-- Tombol Kembali ke Halaman Utama -->
<a href="index.php" class="btn-back-home" title="Kembali ke Halaman Utama" aria-label="Kembali ke Halaman Utama">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
    <line x1="19" y1="12" x2="5" y2="12"/>
    <polyline points="12 19 5 12 12 5"/>
  </svg>
  <span>Halaman Utama</span>
</a>

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

  <!-- LEFT — Sign In Form -->
  <div class="left">
    <h1 class="form-title">Masuk</h1>
    <p class="form-sub">Selamat datang kembali, silakan masuk</p>

    <!-- Tampilkan pesan error jika ada -->
    <?php if ($error): ?>
      <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="sign_in.php" style="width:100%;display:contents;">
      <?= csrf_field() ?>
      <?php if ($safe_redirect !== ""): ?>
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($safe_redirect) ?>"/>
      <?php endif; ?>
      <div class="input-group">
        <!-- Email -->
        <div class="input-wrap">
          <input type="text" name="email" placeholder="Username atau Email" autocomplete="username"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required/>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <rect x="2" y="4" width="20" height="16" rx="2"/>
            <path d="M22 7l-10 7L2 7"/>
          </svg>
        </div>

        <!-- Password -->
        <div class="input-wrap" id="pwWrap">
          <input type="password" name="password" id="pwInput" placeholder="Kata Sandi" autocomplete="current-password"
                 style="padding-left:44px;padding-right:44px;" required/>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <rect x="3" y="11" width="18" height="11" rx="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          <span class="toggle-eye" id="pwToggle" role="button" tabindex="0" aria-label="Tampilkan/sembunyikan kata sandi">
            <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a20.3 20.3 0 0 1-2.61 3.61M14.12 14.12a3 3 0 1 1-4.24-4.24"/>
              <path d="M1 1l22 22"/>
            </svg>
          </span>
        </div>
      </div>

      <div class="forgot-row">
        <a href="lupa_kartu.php" class="forgot">Kartu hilang / lupa kartu?</a>
      </div>

      <button type="submit" class="btn-primary">Masuk</button>
    </form>

    <a href="beranda.php" class="btn-guest">Lihat sebagai Tamu &rarr;</a>
  </div>

  <!-- RIGHT — Info Panel -->
  <div class="right">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="right-content">
      <h2 class="right-title">Halo,<br>Teman</h2>
      <p class="right-sub">
        Daftarkan diri Anda dan mulai<br>
        gunakan layanan kami segera
      </p>
      <a href="sign_up.php" class="btn-outline">Daftar Sekarang</a>
    </div>
  </div>

</div>


<script>
  (function () {
    var wrap   = document.getElementById('pwWrap');
    var input  = document.getElementById('pwInput');
    var toggle = document.getElementById('pwToggle');

    function togglePassword() {
      var isVisible = input.type === 'text';
      input.type = isVisible ? 'password' : 'text';
      wrap.classList.toggle('pw-visible', !isVisible);
    }

    toggle.addEventListener('click', togglePassword);
    toggle.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        togglePassword();
      }
    });
  })();

  // Mode gelap / terang
  (function () {
    var btn  = document.getElementById('btnMode');
    if (!btn) return;
    var root = document.documentElement;
    var STORAGE_KEY = 'aksanova_theme';

    function updateThemeUI() {
      var isLight = root.classList.contains('theme-light') || root.classList.contains('light');
      btn.setAttribute('aria-pressed', isLight ? 'true' : 'false');
      var iconMoon = btn.querySelector('.icon-moon');
      var iconSun  = btn.querySelector('.icon-sun');
      if (iconMoon) iconMoon.style.display = isLight ? 'none' : 'block';
      if (iconSun)  iconSun.style.display  = isLight ? 'block' : 'none';
      if (isLight) {
        root.style.backgroundColor = '#f6f2e9';
      } else {
        root.style.backgroundColor = '#090c10';
      }
    }
    updateThemeUI();

    btn.addEventListener('click', function () {
      var isCurrentlyLight = root.classList.contains('theme-light') || root.classList.contains('light');
      var newMode = isCurrentlyLight ? 'dark' : 'light';

      if (newMode === 'light') {
        root.classList.remove('dark');
        root.classList.add('light', 'theme-light');
      } else {
        root.classList.remove('light', 'theme-light');
        root.classList.add('dark');
      }

      try {
        localStorage.setItem(STORAGE_KEY, newMode);
        var s = JSON.parse(localStorage.getItem('aksanova_settings') || '{}');
        s.mode = newMode;
        localStorage.setItem('aksanova_settings', JSON.stringify(s));
      } catch (e) {}

      updateThemeUI();
    });

    document.addEventListener('keydown', function (e) {
      var tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
      if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
      if ((e.altKey && (e.key === 'd' || e.key === 'D')) || ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'd' || e.key === 'D'))) {
        e.preventDefault();
        btn.click();
      }
    });
  })();
</script>
</body>
</html>