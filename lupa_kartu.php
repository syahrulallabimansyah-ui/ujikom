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
if ($action === "verify" && $_SERVER["REQUEST_METHOD"] === "POST") {
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
if ($action === "cancel" && $_SERVER["REQUEST_METHOD"] === "POST") {
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
if ($action === "set_password" && $_SERVER["REQUEST_METHOD"] === "POST") {
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
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --ink:       #0f0f14;
      --dim:       #6b6b80;
      --ghost:     #a8a8b8;
      --surface:   #ffffff;
      --field:     #f3f3f6;
      --field-foc: #eaeaef;
      --panel-bg:  linear-gradient(148deg, #c8c8d4 0%, #8a8a9a 50%, #3e3e50 100%);
      --accent:    #3e3e50;
      --ring:      rgba(62,62,80,.28);
      --radius-lg: 26px;
      --radius-md: 10px;
      --shadow:    0 32px 80px rgba(0,0,0,.45);
      --trans:     .25s cubic-bezier(.22,1,.36,1);
    }

    html, body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Outfit', sans-serif;
      background: linear-gradient(135deg, #d4d4e0 0%, #c2c2cf 50%, #d8d8e4 100%);
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
      animation: riseIn .9s cubic-bezier(.22,1,.36,1) both;
    }

    @keyframes riseIn {
      from { opacity:0; transform:translateY(36px) scale(.97); }
      to   { opacity:1; transform:translateY(0) scale(1); }
    }

    .left {
      flex: 0 0 54%;
      background: var(--surface);
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
      height: 4px;
      background: linear-gradient(90deg, #3e3e50, #8a8a9a, #3e3e50);
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
      color: var(--ink);
      margin-bottom: 8px;
      letter-spacing: -0.5px;
    }

    .form-sub {
      font-size: .82rem;
      color: var(--dim);
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
      background: var(--field);
    }
    .step-dot.active { background: var(--ink); }

    .error-msg {
      width: 100%;
      background: #fff0f0;
      border: 1px solid #f5c6cb;
      color: #c0392b;
      font-size: .8rem;
      padding: 10px 14px;
      border-radius: var(--radius-md);
      margin-bottom: 14px;
      line-height: 1.5;
    }

    .frozen-notice {
      background: #eef2ff;
      border: 1px solid #c7d2fe;
      color: #3730a3;
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
      color: var(--dim);
      margin-bottom: 6px;
      font-weight: 500;
    }

    .input-wrap { position: relative; display: flex; align-items: center; width: 100%; }

    .input-wrap input {
      width: 100%;
      padding: 13px 44px 13px 16px;
      border: 1.5px solid transparent;
      border-radius: var(--radius-md);
      background: var(--field);
      font-family: 'Outfit', sans-serif;
      font-size: .86rem;
      color: var(--ink);
      outline: none;
      transition: background var(--trans), border-color var(--trans), box-shadow var(--trans);
    }

    .input-wrap input:focus {
      background: var(--field-foc);
      border-color: var(--accent);
      box-shadow: 0 0 0 3px var(--ring);
    }

    .toggle-eye {
      position: absolute;
      right: 14px;
      width: 18px;
      height: 18px;
      color: var(--ghost);
      cursor: pointer;
      transition: color var(--trans);
    }
    .toggle-eye:hover { color: var(--accent); }
    .toggle-eye svg { width: 100%; height: 100%; }
    .toggle-eye .eye-off { display: none; }
    .input-wrap.pw-visible .eye-on  { display: none; }
    .input-wrap.pw-visible .eye-off { display: block; }

    .hint { font-size: .68rem; color: var(--ghost); margin-top: 4px; line-height: 1.5; }

    .btn-primary {
      width: 100%;
      max-width: 240px;
      padding: 13px 0;
      margin-top: 6px;
      border: none;
      border-radius: 50px;
      background: var(--ink);
      color: #fff;
      font-family: 'Outfit', sans-serif;
      font-size: .88rem;
      font-weight: 600;
      letter-spacing: .04em;
      cursor: pointer;
      transition: background var(--trans), transform .15s, box-shadow var(--trans);
    }
    .btn-primary:hover { background: #1a1a2a; box-shadow: 0 8px 24px rgba(0,0,0,.28); }
    .btn-primary:active { transform: scale(.97); }

    .btn-text {
      background: none;
      border: none;
      font-family: 'Outfit', sans-serif;
      font-size: .78rem;
      color: var(--dim);
      cursor: pointer;
      text-decoration: none;
      padding: 0;
    }
    .btn-text:hover { color: #c0392b; text-decoration: underline; }

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
      color: var(--dim);
      text-decoration: none;
    }
    .back-link:hover { color: var(--accent); text-decoration: underline; }

    .right {
      flex: 1;
      background: var(--panel-bg);
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
    .orb-1 { width:160px;height:160px;background:#fff;top:10%;left:5%; }
    .orb-2 { width:100px;height:100px;background:#ccc;bottom:15%;right:8%; animation-delay:3s; }

    @keyframes orbFloat {
      0%,100% { transform: translateY(0) scale(1); }
      50%      { transform: translateY(-18px) scale(1.08); }
    }

    .right-content { position: relative; text-align: center; }

    .right-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 2.2rem;
      font-weight: 700;
      color: #1a1a26;
      line-height: 1.15;
      margin-bottom: 14px;
    }

    .right-sub {
      font-size: .84rem;
      font-weight: 300;
      color: #2e2e3a;
      line-height: 1.85;
    }

    .right-list {
      list-style: none;
      text-align: left;
      margin-top: 22px;
      font-size: .8rem;
      color: #2e2e3a;
      line-height: 2.1;
    }
    .right-list li::before { content: '✓  '; font-weight: 600; }

    @media (max-width: 760px) {
      .card { flex-direction: column; border-radius: 18px; min-height: unset; }
      .left { padding: 36px 26px; }
      .right { padding: 32px 26px; order: -1; }
    }
  </style>
</head>
<body>

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
</script>
</body>
</html>
