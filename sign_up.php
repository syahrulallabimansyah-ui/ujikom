<?php
// sign_up.php — Pendaftaran anggota mandiri + pembuatan kartu anggota otomatis
session_start();
require_once "db.php";

$errors = [];
$old = [
    "full_name" => "",
    "kelas"     => "",
    "nik"       => "",
    "no_hp"     => "",
    "email"     => "",
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $old["full_name"] = trim($_POST["full_name"] ?? "");
    $old["kelas"]     = trim($_POST["kelas"] ?? "");
    $old["nik"]       = trim($_POST["nik"] ?? "");
    $old["no_hp"]     = trim($_POST["no_hp"] ?? "");
    $old["email"]     = trim($_POST["email"] ?? "");

    // ── Validasi ──
    if ($old["full_name"] === "") {
        $errors[] = "Nama lengkap wajib diisi.";
    }
    if ($old["kelas"] === "") {
        $errors[] = "Kelas wajib diisi.";
    }
    if ($old["nik"] === "" || !preg_match('/^\d{6,20}$/', $old["nik"])) {
        $errors[] = "NIK wajib diisi dan hanya boleh berupa angka (6–20 digit).";
    }
    if ($old["email"] === "" || !filter_var($old["email"], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Alamat email tidak valid.";
    }
    if ($old["no_hp"] !== "" && !preg_match('/^[\d+\-\s]{6,20}$/', $old["no_hp"])) {
        $errors[] = "Nomor HP tidak valid.";
    }

    // Cek email & NIK belum terdaftar
    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? OR nik = ?");
        mysqli_stmt_bind_param($stmt, "ss", $old["email"], $old["nik"]);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Email atau NIK sudah terdaftar sebagai anggota.";
        }
        mysqli_stmt_close($stmt);
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

        // ── Generate password acak (8 karakter, aman & mudah dibaca) ──
        $chars_upper = "ABCDEFGHJKLMNPQRSTUVWXYZ"; // tanpa I, O agar tidak rancu
        $chars_lower = "abcdefghijkmnpqrstuvwxyz"; // tanpa l, o
        $chars_num   = "23456789";                 // tanpa 0, 1
        $all_chars   = $chars_upper . $chars_lower . $chars_num;

        $password = $chars_upper[random_int(0, strlen($chars_upper) - 1)]
                  . $chars_lower[random_int(0, strlen($chars_lower) - 1)]
                  . $chars_num[random_int(0, strlen($chars_num) - 1)];
        for ($i = 0; $i < 5; $i++) {
            $password .= $all_chars[random_int(0, strlen($all_chars) - 1)];
        }
        $password = str_shuffle($password);

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

        // ── Simpan ke database ──
        $stmt = mysqli_prepare($conn,
            "INSERT INTO users (full_name, nik, kelas, no_hp, no_anggota, username, email, password, role, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'member', 'pending')"
        );
        mysqli_stmt_bind_param(
            $stmt, "ssssssss",
            $old["full_name"], $old["nik"], $old["kelas"], $old["no_hp"],
            $no_anggota, $username, $old["email"], $hashed
        );

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Simpan sementara di session HANYA untuk ditampilkan sekali di kartu_anggota.php
            // (password asli tidak pernah disimpan di database dalam bentuk plain text)
            $_SESSION["kartu_data"] = [
                "id"         => $new_id,
                "full_name"  => $old["full_name"],
                "nik"        => $old["nik"],
                "kelas"      => $old["kelas"],
                "no_hp"      => $old["no_hp"],
                "email"      => $old["email"],
                "no_anggota" => $no_anggota,
                "username"   => $username,
                "password"   => $password,
            ];

            header("Location: kartu_anggota.php");
            exit;
        } else {
            $errors[] = "Gagal menyimpan data. Silakan coba lagi.";
            mysqli_stmt_close($stmt);
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
      font-family: 'Outfit', sans-serif;
      background: linear-gradient(135deg, #d4d4e0 0%, #c2c2cf 50%, #d8d8e4 100%);
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
      animation: riseIn .9s cubic-bezier(.22,1,.36,1) both;
    }

    @keyframes riseIn {
      from { opacity:0; transform:translateY(36px) scale(.97); }
      to   { opacity:1; transform:translateY(0) scale(1); }
    }

    .left {
      flex: 0 0 58%;
      background: var(--surface);
      padding: 48px 52px;
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
      margin-bottom: 6px;
    }

    .form-sub {
      font-size: .82rem;
      color: var(--ghost);
      font-weight: 300;
      margin-bottom: 24px;
    }

    .error-box {
      background: #fff0f0;
      border: 1px solid #f5c6cb;
      color: #c0392b;
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
      color: var(--dim);
      margin-bottom: 6px;
      font-weight: 500;
    }

    .field input {
      width: 100%;
      padding: 12px 16px;
      border: 1.5px solid transparent;
      border-radius: var(--radius-md);
      background: var(--field);
      font-family: 'Outfit', sans-serif;
      font-size: .86rem;
      color: var(--ink);
      outline: none;
      transition: background var(--trans), border-color var(--trans), box-shadow var(--trans);
    }

    .field input:focus {
      background: var(--field-foc);
      border-color: var(--accent);
      box-shadow: 0 0 0 3px var(--ring);
    }

    .hint { font-size: .68rem; color: var(--ghost); margin-top: 4px; }

    .btn-primary {
      width: 100%;
      max-width: 240px;
      padding: 13px 0;
      margin-top: 8px;
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

    .back-link {
      display: inline-block;
      margin-top: 16px;
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
    .orb-1 { width:160px;height:160px;background:#fff;top:10%;left:5%; }
    .orb-2 { width:100px;height:100px;background:#ccc;bottom:15%;right:8%; animation-delay:3s; }

    @keyframes orbFloat {
      0%,100% { transform: translateY(0) scale(1); }
      50%      { transform: translateY(-18px) scale(1.08); }
    }

    .right-content { position: relative; text-align: center; }

    .right-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 2.3rem;
      font-weight: 700;
      color: #1a1a26;
      line-height: 1.15;
      margin-bottom: 14px;
    }

    .right-sub {
      font-size: .84rem;
      font-weight: 300;
      color: #2e2e3a;
      line-height: 1.8;
      margin-bottom: 8px;
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
      .card { flex-direction: column; border-radius: 18px; }
      .left { padding: 36px 26px; }
      .right { padding: 32px 26px; order: -1; }
      .row2 { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<div class="card">

  <div class="left">
    <h1 class="form-title">Buat Kartu Anggota</h1>
    <p class="form-sub">Isi data diri kamu, username &amp; password akan dibuat otomatis</p>

    <?php if (!empty($errors)): ?>
      <div class="error-box">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="sign_up.php">
      <div class="field">
        <label>Nama Lengkap</label>
        <input type="text" name="full_name" placeholder="Contoh: Budi Santoso"
               value="<?= htmlspecialchars($old['full_name']) ?>" required>
      </div>

      <div class="row2">
        <div class="field">
          <label>Kelas</label>
          <input type="text" name="kelas" placeholder="Contoh: XII IPA 1"
                 value="<?= htmlspecialchars($old['kelas']) ?>" required>
        </div>
        <div class="field">
          <label>NIK</label>
          <input type="text" name="nik" placeholder="16 digit NIK" inputmode="numeric"
                 value="<?= htmlspecialchars($old['nik']) ?>" required>
        </div>
      </div>

      <div class="row2">
        <div class="field">
          <label>Nomor HP <span style="font-weight:300;">(opsional)</span></label>
          <input type="text" name="no_hp" placeholder="08xxxxxxxxxx"
                 value="<?= htmlspecialchars($old['no_hp']) ?>">
        </div>
        <div class="field">
          <label>Email</label>
          <input type="email" name="email" placeholder="nama@email.com"
                 value="<?= htmlspecialchars($old['email']) ?>" required>
        </div>
      </div>

      <p class="hint">Username dan password akan dibuat otomatis oleh sistem dan ditampilkan pada kartu anggota kamu setelah pendaftaran. Simpan baik-baik karena password hanya ditampilkan satu kali.</p>

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

</body>
</html>