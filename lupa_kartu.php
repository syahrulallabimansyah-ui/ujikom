<?php
declare(strict_types=1);

/**
 * lupa_kartu.php — Alur Pemulihan Akun / Lupa Kartu via One-Time Password (OTP)
 * Sistem Perpustakaan Digital AKSA NOVA — SMK Negeri 1 Rongga
 *
 * Alur 3 Langkah:
 * 1. Minta OTP: Masukkan email terdaftar. Sistem mengirimkan kode OTP 6-digit (atau dev log jika lokal).
 * 2. Verifikasi OTP: Masukkan 6 digit OTP. Setelah valid, sesi diamankan & kartu LAMA baru dibekukan (frozen).
 * 3. Sandi Baru: Buat kata sandi baru (<=10 menit sejak OTP verified). Kartu dicairkan kembali (active)
 *    dan kartu pengganti resmi langsung diterbitkan ke kartu_anggota.php.
 */

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/otp.php';

// Inisialisasi langkah aktif (1, 2, atau 3)
$current_step = (int)($_SESSION["lupa_kartu"]["step"] ?? 1);
if (!in_array($current_step, [1, 2, 3], true)) {
    $current_step = 1;
}

$error   = "";
$success = "";

// Tangani notifikasi sesi kedaluwarsa via query parameter
if (isset($_GET['expired']) && $_GET['expired'] === '1') {
    $error = "Sesi verifikasi OTP Anda telah kedaluwarsa (melebihi 10 menit). Silakan ulangi dari awal.";
}

// Verifikasi CSRF untuk seluruh aksi POST
$action = $_POST["action"] ?? "";
if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && !csrf_verify()) {
    $action = "";
    $error  = "Token sesi keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.";
}

// ─────────────────────────────────────────────────────────────
//  BATALKAN PROSES (Cancel Action)
// ─────────────────────────────────────────────────────────────
if ($action === "cancel" && ($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    // Jika kartu sudah sempat dibekukan (langkah 3), cairkan kembali sebelum keluar
    if (!empty($_SESSION["lupa_kartu"]["user_id"])) {
        $uid = (int)$_SESSION["lupa_kartu"]["user_id"];
        $unfreeze = mysqli_prepare($conn, "UPDATE users SET card_status='active' WHERE id=?");
        if ($unfreeze) {
            mysqli_stmt_bind_param($unfreeze, "i", $uid);
            mysqli_stmt_execute($unfreeze);
            mysqli_stmt_close($unfreeze);
        }
    }
    unset($_SESSION["lupa_kartu"]);
    header("Location: sign_in.php");
    exit;
}

// ─────────────────────────────────────────────────────────────
//  GANTI EMAIL (Reset ke Langkah 1)
// ─────────────────────────────────────────────────────────────
if ($action === "change_email" && ($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    unset($_SESSION["lupa_kartu"]);
    header("Location: lupa_kartu.php");
    exit;
}

// ─────────────────────────────────────────────────────────────
//  LANGKAH 1 — MINTA KODE OTP KE EMAIL
// ─────────────────────────────────────────────────────────────
if ($action === "request_otp" && ($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    $email = trim((string)($_POST["email"] ?? ""));
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Alamat email terdaftar wajib diisi dengan format yang valid.";
    } else {
        // Cek rate limiting per IP terlebih dahulu
        $rate_check_ip = otp_batas_permintaan($conn, null, $client_ip);
        if (!$rate_check_ip['allowed']) {
            $error = $rate_check_ip['message'];
        } else {
            // Cari pengguna aktif dengan role=member dan status=approved
            $stmt = mysqli_prepare(
                $conn,
                "SELECT id, full_name, nik, kelas, no_hp, email, no_anggota, username, foto, role, status
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $res  = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            // Validasi kelayakan akun anggota
            $is_eligible = false;
            if ($user && $user['role'] === 'member' && $user['status'] === 'approved') {
                $is_eligible = true;
            }

            if ($is_eligible) {
                // Periksa batas frekuensi untuk akun pengguna ini
                $rate_check_user = otp_batas_permintaan($conn, (int)$user['id'], $client_ip);
                if (!$rate_check_user['allowed']) {
                    $error = $rate_check_user['message'];
                } else {
                    // Buat OTP baru dan kirim via email (atau dev log)
                    $otp_code = otp_buat($conn, (int)$user['id'], $client_ip);
                    $kirim    = otp_kirim($user['email'], $user['full_name'], $otp_code);

                    if (!$kirim['success']) {
                        $error = "Sistem gagal mengirimkan email OTP. Silakan periksa koneksi atau hubungi admin perpustakaan.";
                    } else {
                        // JANGAN membekukan kartu di sini! Kartu hanya dibekukan setelah OTP valid.
                        $_SESSION["lupa_kartu"] = [
                            "step"        => 2,
                            "user_id"     => (int)$user["id"],
                            "email"       => $user["email"],
                            "full_name"   => $user["full_name"],
                            "nik"         => $user["nik"],
                            "kelas"       => $user["kelas"],
                            "no_hp"       => $user["no_hp"],
                            "no_anggota"  => $user["no_anggota"],
                            "username"    => $user["username"],
                            "foto"        => $user["foto"],
                            "otp_sent_at" => time(),
                            "is_dummy"    => false,
                        ];
                        header("Location: lupa_kartu.php");
                        exit;
                    }
                }
            } else {
                // Perlindungan Timing Attack & Enumerasi:
                // Berikan jeda waktu acak (150ms - 250ms) serupa proses hashing/email,
                // lalu arahkan ke langkah 2 dengan sesi dummy agar penyerang tidak dapat menebak email.
                usleep(random_int(150000, 250000));

                $_SESSION["lupa_kartu"] = [
                    "step"        => 2,
                    "email"       => $email,
                    "full_name"   => "Anggota Perpustakaan",
                    "otp_sent_at" => time(),
                    "is_dummy"    => true,
                ];
                header("Location: lupa_kartu.php");
                exit;
            }
        }
    }
}

// ─────────────────────────────────────────────────────────────
//  LANGKAH 2 — VERIFIKASI KODE OTP
// ─────────────────────────────────────────────────────────────
if ($action === "verify_otp" && ($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    if ($current_step !== 2 || empty($_SESSION["lupa_kartu"]["email"])) {
        header("Location: lupa_kartu.php");
        exit;
    }

    // Ambil input OTP (mendukung input gabungan maupun 6 kotak terpisah)
    $input_otp = trim((string)($_POST["otp"] ?? ""));
    if ($input_otp === "" && isset($_POST["otp_digits"]) && is_array($_POST["otp_digits"])) {
        $input_otp = implode('', array_map('trim', $_POST["otp_digits"]));
    }

    $is_dummy = !empty($_SESSION["lupa_kartu"]["is_dummy"]) || empty($_SESSION["lupa_kartu"]["user_id"]);

    if ($is_dummy) {
        // Simulasi verifikasi gagal untuk email yang tidak terdaftar/tidak aktif
        usleep(random_int(100000, 200000));
        $error = "Kode OTP tidak valid atau telah kedaluwarsa. Silakan periksa kembali email Anda.";
    } else {
        $uid = (int)$_SESSION["lupa_kartu"]["user_id"];
        $verif = otp_verifikasi($conn, $uid, $input_otp);

        if ($verif['success']) {
            // Regenerasi ID sesi untuk mencegah Session Fixation
            session_regenerate_id(true);

            // BARU SEKARANG: Bekukan kartu lama karena kepemilikan email telah terbukti
            $freeze = mysqli_prepare($conn, "UPDATE users SET card_status='frozen' WHERE id=?");
            if ($freeze) {
                mysqli_stmt_bind_param($freeze, "i", $uid);
                mysqli_stmt_execute($freeze);
                mysqli_stmt_close($freeze);
            }

            // Catat timestamp verifikasi OTP (berlaku maks 10 menit untuk membuat sandi baru)
            $_SESSION["lupa_kartu"]["step"] = 3;
            $_SESSION["lupa_kartu"]["otp_verified_at"] = time();

            header("Location: lupa_kartu.php");
            exit;
        } else {
            $error = $verif['message'];
        }
    }
}

// ─────────────────────────────────────────────────────────────
//  LANGKAH 2 — KIRIM ULANG KODE OTP (Resend OTP)
// ─────────────────────────────────────────────────────────────
if ($action === "resend_otp" && ($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    if ($current_step !== 2 || empty($_SESSION["lupa_kartu"]["email"])) {
        header("Location: lupa_kartu.php");
        exit;
    }

    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $last_sent = (int)($_SESSION["lupa_kartu"]["otp_sent_at"] ?? 0);
    $elapsed   = time() - $last_sent;

    if ($elapsed < 60) {
        $wait  = 60 - $elapsed;
        $error = "Mohon tunggu {$wait} detik sebelum meminta kirim ulang kode OTP.";
    } else {
        $is_dummy = !empty($_SESSION["lupa_kartu"]["is_dummy"]) || empty($_SESSION["lupa_kartu"]["user_id"]);

        if ($is_dummy) {
            usleep(random_int(150000, 250000));
            $_SESSION["lupa_kartu"]["otp_sent_at"] = time();
            $success = "Jika email terdaftar, kode OTP baru telah dikirimkan ke kotak masuk Anda.";
        } else {
            $uid = (int)$_SESSION["lupa_kartu"]["user_id"];

            // Cek batas permintaan
            $limit_check = otp_batas_permintaan($conn, $uid, $client_ip);
            if (!$limit_check['allowed']) {
                $error = $limit_check['message'];
            } else {
                $otp_baru = otp_buat($conn, $uid, $client_ip);
                $kirim = otp_kirim($_SESSION["lupa_kartu"]["email"], $_SESSION["lupa_kartu"]["full_name"], $otp_baru);

                if ($kirim['success']) {
                    $_SESSION["lupa_kartu"]["otp_sent_at"] = time();
                    $success = "Kode OTP baru telah berhasil dikirimkan ke email Anda.";
                } else {
                    $error = "Gagal mengirimkan kode OTP baru: " . htmlspecialchars($kirim['message']);
                }
            }
        }
    }
}

// ─────────────────────────────────────────────────────────────
//  LANGKAH 3 — BUAT KATA SANDI BARU & TERBITKAN KARTU PENGGANTI
// ─────────────────────────────────────────────────────────────
if ($action === "set_password" && ($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    // Validasi prasyarat: harus di langkah 3 dan otp_verified_at ada
    $sess = $_SESSION["lupa_kartu"] ?? [];
    if (empty($sess["user_id"]) || ($sess["step"] ?? 0) !== 3 || empty($sess["otp_verified_at"])) {
        header("Location: lupa_kartu.php");
        exit;
    }

    // Validasi masa aktif sesi verifikasi (maksimal 10 menit)
    $verified_at = (int)$sess["otp_verified_at"];
    if ((time() - $verified_at) > 600) {
        // Kedaluwarsa: Cairkan kembali kartu lama jika ada
        $uid = (int)$sess["user_id"];
        $unfreeze = mysqli_prepare($conn, "UPDATE users SET card_status='active' WHERE id=?");
        if ($unfreeze) {
            mysqli_stmt_bind_param($unfreeze, "i", $uid);
            mysqli_stmt_execute($unfreeze);
            mysqli_stmt_close($unfreeze);
        }
        unset($_SESSION["lupa_kartu"]);
        header("Location: lupa_kartu.php?expired=1");
        exit;
    }

    $new_password     = (string)($_POST["password"] ?? "");
    $confirm_password = (string)($_POST["password_confirm"] ?? "");

    if (strlen($new_password) < 8) {
        $error = "Kata sandi baru minimal 8 karakter.";
    } elseif (!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
        $error = "Kata sandi baru harus mengandung kombinasi huruf dan angka.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Konfirmasi kata sandi baru tidak sama dengan kata sandi baru.";
    } else {
        $uid = (int)$sess["user_id"];
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $empty_plain = "";

        // UPDATE password baru, kosongkan plain_password, dan aktifkan kembali kartu
        $stmt_up = mysqli_prepare(
            $conn,
            "UPDATE users SET password = ?, plain_password = ?, card_status = 'active' WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt_up, "ssi", $hashed, $empty_plain, $uid);
        mysqli_stmt_execute($stmt_up);
        mysqli_stmt_close($stmt_up);

        // Siapkan data sesi penerbitan kartu anggota pengganti (reissued)
        $_SESSION["kartu_data"] = [
            "id"         => $sess["user_id"],
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

        // Hapus seluruh sesi lupa_kartu setelah berhasil
        unset($_SESSION["lupa_kartu"]);

        // Arahkan ke kartu_anggota.php untuk mengunduh kartu pengganti
        header("Location: kartu_anggota.php");
        exit;
    }
}

// Hitung sisa jeda kirim ulang untuk langkah 2
$resend_wait_seconds = 0;
if ($current_step === 2 && !empty($_SESSION["lupa_kartu"]["otp_sent_at"])) {
    $time_since_sent = time() - (int)$_SESSION["lupa_kartu"]["otp_sent_at"];
    if ($time_since_sent < 60) {
        $resend_wait_seconds = 60 - $time_since_sent;
    }
}

// Hitung sisa waktu kedaluwarsa sesi verifikasi pada langkah 3 (maks 10 menit)
$step3_remaining_seconds = 600;
if ($current_step === 3 && !empty($_SESSION["lupa_kartu"]["otp_verified_at"])) {
    $elapsed_verif = time() - (int)$_SESSION["lupa_kartu"]["otp_verified_at"];
    $step3_remaining_seconds = max(0, 600 - $elapsed_verif);
    if ($step3_remaining_seconds <= 0) {
        $error = "Sesi pembuatan kata sandi telah kedaluwarsa. Silakan ulangi proses dari awal.";
    }
}

$page_title = "Pemulihan Akun & Lupa Kartu — AKSA NOVA";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
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
      padding: 24px 0;
    }

    @keyframes bgShift {
      0%,100% { background-position: 0% 50%; }
      50%      { background-position: 100% 50%; }
    }

    .card {
      width: 920px;
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
      flex: 0 0 56%;
      background: #10151b;
      display: flex;
      flex-direction: column;
      justify-content: center;
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
      font-size: 2.15rem;
      font-weight: 700;
      color: #d8b878;
      margin-bottom: 6px;
      letter-spacing: -0.5px;
    }

    .form-sub {
      font-size: .84rem;
      color: rgba(238,243,244,.78);
      font-weight: 300;
      margin-bottom: 20px;
      line-height: 1.6;
    }

    /* ── Indikator Langkah 1-2-3 Berurutan ── */
    .steps-indicator {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 22px;
    }
    .step-pill {
      flex: 1;
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 6px 12px;
      border-radius: 20px;
      background: rgba(255,255,255,.05);
      border: 1px solid rgba(255,255,255,.08);
      font-size: .72rem;
      color: rgba(238,243,244,.5);
      transition: all var(--trans);
    }
    .step-pill .step-num {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: rgba(255,255,255,.1);
      font-weight: 700;
      font-size: .7rem;
    }
    .step-pill.active {
      background: rgba(216,184,120,.14);
      border-color: rgba(216,184,120,.4);
      color: #d8b878;
      font-weight: 600;
    }
    .step-pill.active .step-num {
      background: #d8b878;
      color: #10151b;
    }
    .step-pill.completed {
      background: rgba(39,174,96,.12);
      border-color: rgba(39,174,96,.3);
      color: #4ade80;
    }
    .step-pill.completed .step-num {
      background: #27ae60;
      color: #fff;
    }

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

    .success-msg {
      width: 100%;
      background: rgba(39,174,96,.15);
      border: 1px solid rgba(39,174,96,.45);
      color: #4ade80;
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
      color: rgba(238,243,244,.65);
      margin-bottom: 6px;
      font-weight: 500;
    }

    .input-wrap { position: relative; display: flex; align-items: center; width: 100%; }

    .input-wrap input,
    .field input {
      width: 100%;
      box-sizing: border-box;
      padding: 13px 16px;
      border: 1.5px solid rgba(216,184,120,.15);
      border-radius: var(--radius-md);
      background: rgba(255,255,255,.05);
      font-family: 'Outfit', sans-serif;
      font-size: .86rem;
      color: #eef3f4;
      outline: none;
      transition: background var(--trans), border-color var(--trans), box-shadow var(--trans);
    }

    .input-wrap input[type="password"] {
      padding-right: 44px;
    }

    .input-wrap input::placeholder,
    .field input::placeholder { color: rgba(238,243,244,.45); }

    .input-wrap input:focus,
    .field input:focus {
      background: rgba(216,184,120,.08);
      border-color: #d8b878;
      box-shadow: 0 0 0 3px rgba(216,184,120,.2);
    }

    /* ── Kotak 6-Digit OTP ── */
    .otp-grid-wrap {
      display: flex;
      gap: 10px;
      justify-content: space-between;
      margin: 16px 0 12px;
    }
    .otp-digit-box {
      flex: 1;
      height: 54px;
      text-align: center;
      font-size: 1.5rem;
      font-weight: 700;
      color: #f0d9a8;
      background: rgba(255,255,255,.06);
      border: 1.5px solid rgba(216,184,120,.2);
      border-radius: var(--radius-md);
      outline: none;
      transition: all var(--trans);
      font-family: 'Outfit', monospace;
    }
    .otp-digit-box:focus {
      background: rgba(216,184,120,.1);
      border-color: #d8b878;
      box-shadow: 0 0 0 3px rgba(216,184,120,.25);
      transform: translateY(-2px);
    }

    .otp-meta-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: .75rem;
      color: rgba(238,243,244,.7);
      margin-bottom: 16px;
      flex-wrap: wrap;
      gap: 8px;
    }
    .otp-timer-box {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      color: #d8b878;
      font-weight: 600;
    }

    .toggle-eye {
      position: absolute;
      right: 14px;
      width: 18px;
      height: 18px;
      color: #d8b878;
      cursor: pointer;
      transition: color var(--trans);
    }
    .toggle-eye:hover { color: #f0d9a8; }
    .toggle-eye svg { width: 100%; height: 100%; }
    .toggle-eye .eye-off { display: none; }
    .input-wrap.pw-visible .eye-on  { display: none; }
    .input-wrap.pw-visible .eye-off { display: block; }

    .hint { font-size: .72rem; color: rgba(238,243,244,.72); margin-top: 4px; line-height: 1.5; }

    .btn-primary {
      width: 100%;
      max-width: 260px;
      padding: 13px 0;
      margin-top: 6px;
      border: none;
      border-radius: 50px;
      background: linear-gradient(135deg, #d8b878, #f0d9a8);
      color: #090c10;
      font-family: 'Outfit', sans-serif;
      font-size: .88rem;
      font-weight: 700;
      letter-spacing: .04em;
      cursor: pointer;
      transition: box-shadow var(--trans), transform .15s;
    }
    .btn-primary:hover { box-shadow: 0 8px 28px rgba(216,184,120,.45); transform: translateY(-1px); }
    .btn-primary:active { transform: scale(.97); }

    .btn-secondary {
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(216,184,120,.2);
      color: #eef3f4;
      font-family: 'Outfit', sans-serif;
      font-size: .8rem;
      font-weight: 600;
      padding: 8px 16px;
      border-radius: 20px;
      cursor: pointer;
      transition: all var(--trans);
    }
    .btn-secondary:hover:not(:disabled) {
      background: rgba(216,184,120,.14);
      border-color: #d8b878;
      color: #d8b878;
    }
    .btn-secondary:disabled {
      opacity: .45;
      cursor: not-allowed;
      border-color: rgba(255,255,255,.1);
    }

    .btn-text {
      background: none;
      border: none;
      font-family: 'Outfit', sans-serif;
      font-size: .78rem;
      color: rgba(238,243,244,.55);
      cursor: pointer;
      text-decoration: none;
      padding: 0;
    }
    .btn-text:hover { color: #e07070; text-decoration: underline; }

    .actions-row {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-top: 14px;
      flex-wrap: wrap;
    }

    .back-link {
      display: inline-block;
      margin-top: 18px;
      font-size: .78rem;
      color: rgba(238,243,244,.75); font-weight: 600;
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
      font-weight: 400;
      color: rgba(238,243,244,.85);
      line-height: 1.85;
    }

    .right-list {
      list-style: none;
      text-align: left;
      margin-top: 22px;
      font-size: .8rem;
      color: rgba(238,243,244,.85); font-weight: 500;
      line-height: 2.1;
    }
    .right-list li::before { content: '✓  '; font-weight: 600; color: #d8b878; }

    /* ══════════════════ RESPONSIVE MOBILE ══════════════════ */
    @media (max-width: 860px) {
      html, body { padding: 24px 14px; min-height: 100vh; }
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
        padding: 34px 28px 26px;
        border-bottom: 1px solid rgba(216,184,120,.16);
      }
      .right-title { font-size: 1.85rem; margin-bottom: 8px; }
      .right-title br { display: none; }
      .right-sub { font-size: .84rem; margin-bottom: 12px; line-height: 1.5; }
      .right-sub br { display: none; }
      .right-list {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 8px 12px;
        margin-top: 14px;
        font-size: .78rem;
      }
      .right-list li {
        display: inline-flex;
        align-items: center;
        background: rgba(216,184,120,.08);
        border: 1px solid rgba(216,184,120,.2);
        padding: 5px 12px;
        border-radius: 20px;
      }
      .left { flex: none; padding: 32px 28px 38px; }
      .left::before { display: none; }
      .form-title { font-size: 1.8rem; }
      .form-sub { font-size: .8rem; margin-bottom: 18px; }
      .btn-primary { max-width: 100%; width: 100%; padding: 13px 0; }
      .back-link { display: block; text-align: center; margin-top: 18px; }
    }

    @media (max-width: 600px) {
      html, body { padding: 16px 10px; }
      .card { border-radius: 18px; }
      .left { padding: 24px 18px 30px; }
      .right { padding: 26px 18px 22px; }
      .right-title { font-size: 1.55rem; }
      .form-title { font-size: 1.55rem; }
      .otp-digit-box { height: 46px; font-size: 1.25rem; }
      .steps-indicator { gap: 6px; }
      .step-pill { padding: 5px 8px; font-size: .65rem; }
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
    .btn-mode svg { width: 19px; height: 19px; }
    .btn-mode .icon-sun { display: none; }

    /* ══════════════════ LIGHT THEME OVERRIDES (HIGH CONTRAST & ACCESSIBILITY) ══════════════════ */
    html.theme-light, html.light {
      --ink:       #1a140d;
      --dim:       #4a4237;
      --ghost:     #6b6154;
      --surface:   #ffffff;
      --field:     #fdfbf7;
      --field-foc: #ffffff;
      --panel-bg:  linear-gradient(148deg, #f5efe6 0%, #ebe2d2 50%, #f6f0e7 100%);
      --accent:    #8a5815;
      --ring:      rgba(138,88,21,.25);
      --shadow:    0 24px 60px rgba(60,40,15,.12);
    }
    html.theme-light, html.theme-light body,
    html.light, html.light body {
      background: linear-gradient(135deg, #f5efe6 0%, #ebe2d2 50%, #f6f0e7 100%) !important;
      color: #1a140d !important;
    }
    html.theme-light .btn-mode, html.light .btn-mode {
      background: #ffffff !important;
      border-color: rgba(138,88,21,.38) !important;
      color: #784807 !important;
      box-shadow: 0 4px 16px rgba(60,40,15,.12) !important;
    }
    html.theme-light .btn-mode:hover, html.light .btn-mode:hover {
      border-color: #8a5815 !important;
      box-shadow: 0 6px 20px rgba(138,88,21,.25) !important;
    }
    html.theme-light .btn-mode .icon-moon, html.light .btn-mode .icon-moon { display: none !important; }
    html.theme-light .btn-mode .icon-sun, html.light .btn-mode .icon-sun   { display: block !important; }

    html.theme-light .card, html.light .card {
      background: #ffffff !important;
      border-color: rgba(138,88,21,.22) !important;
      box-shadow: 0 24px 60px rgba(60,40,15,.12) !important;
    }
    html.theme-light .left, html.light .left { background: #ffffff !important; }
    html.theme-light .form-title, html.light .form-title {
      color: #784807 !important;
      font-weight: 700 !important;
    }
    html.theme-light .form-sub, html.light .form-sub {
      color: #4a4237 !important;
      font-weight: 400 !important;
    }
    html.theme-light .form-sub b, html.light .form-sub b {
      color: #1a140d !important;
      font-weight: 700 !important;
    }
    html.theme-light .field label, html.light .field label {
      color: #241c14 !important;
      font-weight: 700 !important;
    }
    html.theme-light .field input, html.theme-light .input-wrap input,
    html.light .field input, html.light .input-wrap input {
      background: #fdfbf7 !important;
      border-color: rgba(138,88,21,.32) !important;
      color: #1a140d !important;
      font-weight: 600 !important;
    }
    html.theme-light .field input:focus, html.theme-light .input-wrap input:focus,
    html.light .field input:focus, html.light .input-wrap input:focus {
      background: #ffffff !important;
      border-color: #8a5815 !important;
      box-shadow: 0 0 0 3px rgba(138,88,21,.2) !important;
    }
    html.theme-light .input-wrap input::placeholder,
    html.theme-light .field input::placeholder,
    html.light .input-wrap input::placeholder,
    html.light .field input::placeholder {
      color: #7d7265 !important;
      opacity: 1 !important;
      font-weight: 400 !important;
    }
    html.theme-light .hint, html.light .hint {
      color: #5c5245 !important;
      font-weight: 500 !important;
      font-size: 0.74rem !important;
    }
    html.theme-light .otp-digit-box, html.light .otp-digit-box {
      background: #ffffff !important;
      border-color: rgba(138,88,21,.35) !important;
      color: #452b06 !important;
      font-weight: 800 !important;
      box-shadow: 0 2px 6px rgba(60,40,15,.06) !important;
    }
    html.theme-light .otp-digit-box:focus, html.light .otp-digit-box:focus {
      background: #ffffff !important;
      border-color: #8a5815 !important;
      box-shadow: 0 0 0 3px rgba(138,88,21,.25) !important;
      color: #2b1800 !important;
    }
    html.theme-light .otp-meta-bar, html.light .otp-meta-bar {
      color: #4a4237 !important;
      font-weight: 500 !important;
    }
    html.theme-light .otp-timer-box, html.light .otp-timer-box {
      color: #854d0e !important;
      font-weight: 700 !important;
      background: rgba(138,88,21,.08) !important;
      border: 1px solid rgba(138,88,21,.22) !important;
      padding: 4px 10px !important;
      border-radius: 14px !important;
    }
    html.theme-light .otp-timer-box svg, html.light .otp-timer-box svg {
      stroke: #854d0e !important;
    }
    html.theme-light .toggle-eye, html.light .toggle-eye {
      color: #8a5815 !important;
    }
    html.theme-light .toggle-eye:hover, html.light .toggle-eye:hover {
      color: #452b06 !important;
    }
    html.theme-light .step-pill, html.light .step-pill {
      background: rgba(138,88,21,.07) !important;
      border-color: rgba(138,88,21,.22) !important;
      color: #5c5245 !important;
      font-weight: 600 !important;
    }
    html.theme-light .step-pill .step-num, html.light .step-pill .step-num {
      background: rgba(138,88,21,.14) !important;
      color: #524738 !important;
      border: 1px solid rgba(138,88,21,.28) !important;
      font-weight: 700 !important;
    }
    html.theme-light .step-pill.active, html.light .step-pill.active {
      background: rgba(138,88,21,.16) !important;
      border-color: #8a5815 !important;
      color: #6e460a !important;
      font-weight: 700 !important;
    }
    html.theme-light .step-pill.active .step-num, html.light .step-pill.active .step-num {
      background: #8a5815 !important;
      color: #ffffff !important;
      border: none !important;
    }
    html.theme-light .step-pill.completed, html.light .step-pill.completed {
      background: #eefaf0 !important;
      border-color: rgba(21,128,61,.35) !important;
      color: #15803d !important;
      font-weight: 600 !important;
    }
    html.theme-light .step-pill.completed .step-num, html.light .step-pill.completed .step-num {
      background: #16a34a !important;
      color: #ffffff !important;
      border: none !important;
    }
    html.theme-light .btn-primary, html.light .btn-primary {
      background: linear-gradient(135deg, #d4a34b, #b8832a) !important;
      color: #ffffff !important;
      box-shadow: 0 6px 20px rgba(138,88,21,.35) !important;
      text-shadow: 0 1px 2px rgba(0,0,0,.25);
    }
    html.theme-light .btn-primary:hover, html.light .btn-primary:hover {
      box-shadow: 0 8px 26px rgba(138,88,21,.45) !important;
      filter: brightness(1.04);
    }
    html.theme-light .btn-secondary, html.light .btn-secondary {
      background: #fbf5eb !important;
      border-color: rgba(138,88,21,.35) !important;
      color: #784807 !important;
      font-weight: 600 !important;
    }
    html.theme-light .btn-secondary:hover:not(:disabled), html.light .btn-secondary:hover:not(:disabled) {
      background: rgba(138,88,21,.15) !important;
      border-color: #8a5815 !important;
      color: #452b06 !important;
    }
    html.theme-light .btn-secondary:disabled, html.light .btn-secondary:disabled {
      background: #f3eee6 !important;
      border-color: rgba(138,88,21,.2) !important;
      color: #7d7265 !important;
      opacity: 0.8 !important;
    }
    html.theme-light .btn-text, html.light .btn-text {
      color: #5c5245 !important;
      font-weight: 600 !important;
    }
    html.theme-light .btn-text:hover, html.light .btn-text:hover {
      color: #b91c1c !important;
    }
    html.theme-light .back-link, html.light .back-link {
      color: #635849 !important;
      font-weight: 600 !important;
    }
    html.theme-light .back-link:hover, html.light .back-link:hover {
      color: #8a5815 !important;
    }
    html.theme-light .frozen-notice, html.light .frozen-notice {
      background: #fef8ee !important;
      border-color: rgba(217,119,6,.38) !important;
      color: #854d0e !important;
    }
    html.theme-light .frozen-notice b, html.light .frozen-notice b {
      color: #451a03 !important;
      font-weight: 800 !important;
    }
    html.theme-light .right, html.light .right {
      background: linear-gradient(148deg, #f8f4ec 0%, #ece1ce 50%, #f4ede0 100%) !important;
      border-left: 1px solid rgba(138,88,21,.18) !important;
    }
    @media (max-width: 860px) {
      html.theme-light .right, html.light .right {
        border-left: none !important;
        border-bottom: 1px solid rgba(138,88,21,.18) !important;
      }
    }
    html.theme-light .right-title, html.light .right-title {
      color: #6c4207 !important;
      font-weight: 700 !important;
    }
    html.theme-light .right-sub, html.light .right-sub {
      color: #3d352a !important;
      font-weight: 400 !important;
    }
    html.theme-light .right-list, html.light .right-list {
      color: #2c251d !important;
    }
    html.theme-light .right-list li, html.light .right-list li {
      background: rgba(138,88,21,.07) !important;
      border-color: rgba(138,88,21,.2) !important;
      color: #2c251d !important;
      font-weight: 500 !important;
    }
    html.theme-light .right-list li::before, html.light .right-list li::before {
      color: #8a5815 !important;
      font-weight: 700 !important;
    }
    html.theme-light .error-msg, html.light .error-msg {
      background: #fef2f2 !important;
      border-color: rgba(220,38,38,.35) !important;
      color: #991b1b !important;
    }
    html.theme-light .success-msg, html.light .success-msg {
      background: #f0fdf4 !important;
      border-color: rgba(22,163,74,.35) !important;
      color: #166534 !important;
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

    <!-- Indikator Langkah 1 - 2 - 3 -->
    <div class="steps-indicator">
      <div class="step-pill <?= $current_step === 1 ? 'active' : ($current_step > 1 ? 'completed' : '') ?>">
        <span class="step-num"><?= $current_step > 1 ? '✓' : '1' ?></span>
        <span>Minta OTP</span>
      </div>
      <div class="step-pill <?= $current_step === 2 ? 'active' : ($current_step > 2 ? 'completed' : '') ?>">
        <span class="step-num"><?= $current_step > 2 ? '✓' : '2' ?></span>
        <span>Verifikasi OTP</span>
      </div>
      <div class="step-pill <?= $current_step === 3 ? 'active' : '' ?>">
        <span class="step-num">3</span>
        <span>Sandi Baru</span>
      </div>
    </div>

    <?php if ($error !== ""): ?>
      <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
      <div class="success-msg"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!--  LANGKAH 1 — MINTA KODE OTP EMAIL                           -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <?php if ($current_step === 1): ?>
      <h1 class="form-title">Lupa Kartu / Sandi</h1>
      <p class="form-sub">Masukkan alamat email anggota yang terdaftar di perpustakaan. Kami akan mengirimkan 6 digit kode OTP verifikasi.</p>

      <form method="POST" action="lupa_kartu.php" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="request_otp">
        <div class="field">
          <label for="emailInput">Alamat Email Terdaftar</label>
          <div class="input-wrap">
            <input type="email" name="email" id="emailInput" placeholder="nama@email.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus style="padding-right:16px;">
          </div>
          <p class="hint">Kode One-Time Password (OTP) akan dikirimkan ke alamat email ini.</p>
        </div>

        <button type="submit" class="btn-primary">Kirim Kode OTP</button>
      </form>

      <a href="sign_in.php" class="back-link">&larr; Kembali ke halaman masuk</a>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!--  LANGKAH 2 — MASUKKAN 6 DIGIT KODE OTP                     -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <?php elseif ($current_step === 2): ?>
      <h1 class="form-title">Verifikasi OTP</h1>
      <p class="form-sub">
        Jika email terdaftar, kode OTP telah dikirim ke <b><?= htmlspecialchars($_SESSION["lupa_kartu"]["email"] ?? "email Anda") ?></b>. Masukkan 6 digit kode di bawah:
      </p>

      <form method="POST" action="lupa_kartu.php" id="formOtp">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="verify_otp">
        <input type="hidden" name="otp" id="otpCombined" value="">

        <div class="field">
          <label>Kode Verifikasi 6 Digit</label>
          <div class="otp-grid-wrap" id="otpContainer">
            <input type="text" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" autofocus data-idx="0">
            <input type="text" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-idx="1">
            <input type="text" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-idx="2">
            <input type="text" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-idx="3">
            <input type="text" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-idx="4">
            <input type="text" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-idx="5">
          </div>
        </div>

        <div class="otp-meta-bar">
          <div class="otp-timer-box" id="expiryTimerBox">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span id="expiryTimerText">Berlaku: 10:00</span>
          </div>

          <div>
            <button type="button" class="btn-secondary" id="btnResend" <?= $resend_wait_seconds > 0 ? 'disabled' : '' ?>>
              Kirim Ulang (<span id="resendCountdown"><?= $resend_wait_seconds > 0 ? $resend_wait_seconds : '60' ?></span>s)
            </button>
          </div>
        </div>

        <div class="actions-row">
          <button type="submit" class="btn-primary" id="btnSubmitOtp">Verifikasi Kode OTP</button>
        </div>
      </form>

      <!-- Form Tersembunyi Kirim Ulang OTP -->
      <form method="POST" action="lupa_kartu.php" id="formResendOtp" style="display:none;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="resend_otp">
      </form>

      <!-- Form Ganti Email -->
      <form method="POST" action="lupa_kartu.php" style="margin-top:14px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="change_email">
        <button type="submit" class="btn-text">&larr; Salah memasukkan email? Ganti alamat email</button>
      </form>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!--  LANGKAH 3 — BUAT SANDI BARU & TERBITKAN KARTU PENGGANTI     -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <?php else: ?>
      <h1 class="form-title">Buat Kata Sandi Baru</h1>
      <p class="form-sub">
        Kode OTP berhasil diverifikasi untuk akun <b><?= htmlspecialchars($_SESSION["lupa_kartu"]["full_name"] ?? "") ?></b>. Kartu lama telah dibekukan demi keamanan. Silakan buat kata sandi baru untuk menerbitkan kartu pengganti.
      </p>

      <div class="frozen-notice">
        Kartu lama sedang dibekukan sementara. Selesaikan pembuatan sandi baru ini dalam waktu <b id="step3TimerText">10:00</b> untuk menerbitkan kartu pengganti aktif.
      </div>

      <form method="POST" action="lupa_kartu.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="set_password">
        <div class="field">
          <label>Kata Sandi Baru</label>
          <div class="input-wrap" id="pwWrap2">
            <input type="password" name="password" id="pwInput2" placeholder="Minimal 8 karakter, huruf &amp; angka" autocomplete="new-password" required autofocus>
            <span class="toggle-eye" data-target="pwInput2" data-wrap="pwWrap2" role="button" tabindex="0" aria-label="Tampilkan/sembunyikan kata sandi">
              <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a20.3 20.3 0 0 1-2.61 3.61M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
            </span>
          </div>
          <p class="hint">Minimal 8 karakter dan wajib memuat kombinasi huruf serta angka.</p>
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
          <button type="submit" class="btn-primary">Simpan Sandi &amp; Terbitkan Kartu</button>
        </div>
      </form>

      <form method="POST" action="lupa_kartu.php" style="margin-top:12px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="cancel">
        <button type="submit" class="btn-text">Batalkan, cairkan kembali kartu lama</button>
      </form>
    <?php endif; ?>

  </div>

  <div class="right">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="right-content">
      <h2 class="right-title">Pemulihan Aman<br>Dengan Kode OTP</h2>
      <p class="right-sub">Verifikasi identitas anggota kini lebih aman<br>langsung melalui email terdaftar.</p>
      <ul class="right-list">
        <li>Verifikasi aman 6-digit One-Time Password</li>
        <li>Kartu lama baru dibekukan setelah OTP valid</li>
        <li>Buat sendiri kata sandi baru akun Anda</li>
        <li>Kartu anggota pengganti langsung diterbitkan</li>
      </ul>
    </div>
  </div>
</div>

<script>
  // Toggle Visibility Password
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

  // Skrip Interaktif Kotak 6-Digit OTP (Langkah 2)
  var otpInputs = document.querySelectorAll('.otp-digit-box');
  if (otpInputs.length === 6) {
    var formOtp = document.getElementById('formOtp');
    var otpCombined = document.getElementById('otpCombined');

    function syncOtpValue() {
      var val = '';
      otpInputs.forEach(function (inp) { val += inp.value; });
      otpCombined.value = val;
      return val;
    }

    otpInputs.forEach(function (input, idx) {
      input.addEventListener('input', function (e) {
        var val = this.value.replace(/[^0-9]/g, '');
        this.value = val.slice(0, 1);

        if (this.value !== '' && idx < 5) {
          otpInputs[idx + 1].focus();
        }
        var code = syncOtpValue();
        if (code.length === 6) {
          // Otomatis submit ketika 6 digit terisi penuh
          formOtp.requestSubmit ? formOtp.requestSubmit() : formOtp.submit();
        }
      });

      input.addEventListener('keydown', function (e) {
        if (e.key === 'Backspace' && this.value === '' && idx > 0) {
          otpInputs[idx - 1].focus();
        }
      });

      // Dukungan Paste 6-Digit ke dalam kotak mana saja
      input.addEventListener('paste', function (e) {
        e.preventDefault();
        var pasteData = (e.clipboardData || window.clipboardData).getData('text');
        var digits = pasteData.replace(/[^0-9]/g, '').slice(0, 6);
        if (digits.length > 0) {
          digits.split('').forEach(function (d, i) {
            if (otpInputs[i]) {
              otpInputs[i].value = d;
            }
          });
          syncOtpValue();
          var nextFocus = Math.min(digits.length, 5);
          otpInputs[nextFocus].focus();
          if (digits.length === 6) {
            formOtp.requestSubmit ? formOtp.requestSubmit() : formOtp.submit();
          }
        }
      });
    });

    formOtp.addEventListener('submit', function (e) {
      var val = syncOtpValue();
      if (val.length !== 6) {
        e.preventDefault();
        alert('Mohon lengkapi 6 digit kode OTP sebelum melanjutkan.');
        var firstEmpty = Array.from(otpInputs).find(function (inp) { return inp.value === ''; });
        if (firstEmpty) firstEmpty.focus();
      }
    });

    // Hitung Mundur Jeda Kirim Ulang (60 detik)
    var resendBtn = document.getElementById('btnResend');
    var resendCountdownSpan = document.getElementById('resendCountdown');
    var resendWait = <?= (int)$resend_wait_seconds ?>;

    if (resendBtn) {
      function updateResendTimer() {
        if (resendWait > 0) {
          resendBtn.disabled = true;
          resendBtn.innerHTML = 'Kirim Ulang (' + resendWait + 's)';
          resendWait--;
          setTimeout(updateResendTimer, 1000);
        } else {
          resendBtn.disabled = false;
          resendBtn.innerHTML = 'Kirim Ulang OTP';
        }
      }
      updateResendTimer();

      resendBtn.addEventListener('click', function () {
        if (!this.disabled) {
          document.getElementById('formResendOtp').submit();
        }
      });
    }

    // Hitung Mundur Masa Berlaku OTP (10 Menit = 600 Detik)
    var expiryTimerText = document.getElementById('expiryTimerText');
    if (expiryTimerText) {
      var sentAt = <?= (int)($_SESSION["lupa_kartu"]["otp_sent_at"] ?? time()) ?>;
      var nowEpoch = Math.floor(Date.now() / 1000);
      var remainingSec = Math.max(0, 600 - (nowEpoch - sentAt));

      function updateExpiryTimer() {
        if (remainingSec > 0) {
          var m = Math.floor(remainingSec / 60);
          var s = remainingSec % 60;
          expiryTimerText.textContent = 'Berlaku: ' + (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
          remainingSec--;
          setTimeout(updateExpiryTimer, 1000);
        } else {
          expiryTimerText.textContent = 'Kode OTP Telah Kedaluwarsa';
          expiryTimerText.style.color = '#e07070';
        }
      }
      updateExpiryTimer();
    }
  }

  // Hitung Mundur Langkah 3 (Sandi Baru, maks 10 menit)
  var step3TimerText = document.getElementById('step3TimerText');
  if (step3TimerText) {
    var step3Sec = <?= (int)$step3_remaining_seconds ?>;
    function updateStep3Timer() {
      if (step3Sec > 0) {
        var m = Math.floor(step3Sec / 60);
        var s = step3Sec % 60;
        step3TimerText.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        step3Sec--;
        setTimeout(updateStep3Timer, 1000);
      } else {
        step3TimerText.textContent = 'Kedaluwarsa';
        alert('Sesi verifikasi OTP Anda telah berakhir. Anda akan diarahkan kembali ke langkah awal.');
        window.location.href = 'lupa_kartu.php?expired=1';
      }
    }
    updateStep3Timer();
  }

  // Pengaturan Mode Gelap / Terang
  (function () {
    var btn = document.getElementById('btnMode');
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
