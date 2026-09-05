<?php
// sign_in.php — Halaman masuk pengguna
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

$error = "";

// Proses login saat form dikirim
if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
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
                // Login berhasil — simpan data ke session
                $_SESSION["user_id"]   = $user["id"];
                $_SESSION["user_name"] = $user["full_name"];
                $_SESSION["username"]  = $user["username"];
                $_SESSION["role"]      = $user["role"];

                header("Location: beranda.php");
                exit;
            }
        } else {
            $error = "Email atau kata sandi salah.";
        }
    }
}

$page_title = "Sign In – AKSA NOVA";
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
      color: rgba(238,243,244,.55);
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
      color: rgba(238,243,244,.35);
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

    .input-wrap input::placeholder { color: rgba(238,243,244,.35); }

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
      color: rgba(238,243,244,.35);
      cursor: pointer;
      pointer-events: auto;
      transition: color var(--trans);
    }
    .toggle-eye:hover { color: #d8b878; }
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
      color: rgba(238,243,244,.5);
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
      color: rgba(238,243,244,.45);
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
      50%      { transform: translateY(-18px) scale(1.08); }
    }

    .right-content {
      position: relative;
      text-align: center;
      animation: riseIn .9s .15s cubic-bezier(.22,1,.36,1) both;
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
      font-weight: 300;
      color: rgba(238,243,244,.68);
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
    html.theme-light .input-wrap input {
      background: #fdfbf7;
      border-color: rgba(154,115,40,.22);
      color: #1a1714;
    }
    html.theme-light .input-wrap input::placeholder {
      color: #9c9489;
    }
    html.theme-light .input-wrap input:focus {
      background: #ffffff;
      border-color: #9a7328;
      box-shadow: 0 0 0 3px rgba(154,115,40,.18);
    }
    html.theme-light .input-wrap > svg {
      color: #9c9489;
    }
    html.theme-light .input-wrap:focus-within svg {
      color: #9a7328;
    }
    html.theme-light .toggle-eye {
      color: #9c9489;
    }
    html.theme-light .toggle-eye:hover {
      color: #9a7328;
    }
    html.theme-light .forgot {
      color: #6b645b;
    }
    html.theme-light .forgot:hover {
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
    html.theme-light .btn-guest {
      color: #6b645b;
    }
    html.theme-light .btn-guest:hover {
      color: #9a7328;
    }
    html.theme-light .right {
      background: linear-gradient(148deg, #f8f4ec 0%, #ece1ce 50%, #f4ede0 100%);
    }
    html.theme-light .right-title {
      color: #8a6323;
    }
    html.theme-light .right-sub {
      color: #6b645b;
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

  <!-- LEFT — Sign In Form -->
  <div class="left">
    <h1 class="form-title">Masuk</h1>
    <p class="form-sub">Selamat datang kembali, silakan masuk</p>

    <!-- Tampilkan pesan error jika ada -->
    <?php if ($error): ?>
      <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="sign_in.php" style="width:100%;display:contents;">
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

      <button type="submit" class="btn-primary">Sign In</button>
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
      <a href="sign_up.php" class="btn-outline">Sign up</a>
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