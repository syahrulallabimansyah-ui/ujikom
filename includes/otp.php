<?php
declare(strict_types=1);

/**
 * includes/otp.php — Manajemen Siklus Hidup & Keamanan One-Time Password (OTP)
 * Proyek: AKSA NOVA (Perpustakaan Digital SMKN 1 Rongga)
 */

if (file_exists(__DIR__ . '/../config_mail.php')) {
    require_once __DIR__ . '/../config_mail.php';
} else {
    require_once __DIR__ . '/../config_mail.example.php';
}

require_once __DIR__ . '/mailer.php';

/**
 * Memeriksa batas frekuensi permintaan OTP (Rate Limiting)
 * Aturan:
 * - Jeda kirim ulang minimal 60 detik per user
 * - Maksimal 5 permintaan per jam per user
 * - Maksimal 10 permintaan per jam per alamat IP
 *
 * @param mysqli $conn
 * @param int|null $user_id
 * @param string|null $ip
 * @return array{allowed: bool, reason?: string, wait?: int, message: string}
 */
function otp_batas_permintaan(mysqli $conn, ?int $user_id, ?string $ip): array {
    $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

    // 1. Cek batasan per IP (maksimal 10 per jam)
    $stmt_ip = mysqli_prepare($conn, "SELECT COUNT(*) FROM password_otp WHERE request_ip = ? AND created_at >= NOW() - INTERVAL 1 HOUR");
    if ($stmt_ip) {
        mysqli_stmt_bind_param($stmt_ip, "s", $ip);
        mysqli_stmt_execute($stmt_ip);
        mysqli_stmt_bind_result($stmt_ip, $count_ip);
        mysqli_stmt_fetch($stmt_ip);
        mysqli_stmt_close($stmt_ip);

        if ($count_ip >= 10) {
            return [
                'allowed' => false,
                'reason'  => 'ip_limit',
                'message' => 'Terlalu banyak permintaan OTP dari jaringan Anda (maksimal 10 per jam). Silakan coba lagi nanti.'
            ];
        }
    }

    // 2. Jika user_id diketahui, cek jeda 60 detik dan batas 5 per jam
    if ($user_id !== null && $user_id > 0) {
        $stmt_user = mysqli_prepare(
            $conn,
            "SELECT COUNT(*), MAX(last_sent_at) FROM password_otp WHERE user_id = ? AND created_at >= NOW() - INTERVAL 1 HOUR"
        );
        if ($stmt_user) {
            mysqli_stmt_bind_param($stmt_user, "i", $user_id);
            mysqli_stmt_execute($stmt_user);
            mysqli_stmt_bind_result($stmt_user, $count_user, $last_sent_str);
            mysqli_stmt_fetch($stmt_user);
            mysqli_stmt_close($stmt_user);

            if ($last_sent_str !== null) {
                $last_sent_time = strtotime($last_sent_str);
                $elapsed = time() - $last_sent_time;
                if ($elapsed < 60) {
                    $wait = 60 - $elapsed;
                    return [
                        'allowed' => false,
                        'reason'  => 'cooldown',
                        'wait'    => $wait,
                        'message' => "Mohon tunggu {$wait} detik sebelum meminta kode OTP kembali."
                    ];
                }
            }

            if ($count_user >= 5) {
                return [
                    'allowed' => false,
                    'reason'  => 'user_limit',
                    'message' => 'Permintaan OTP untuk akun ini telah melebihi batas (maksimal 5 kali per jam). Silakan coba lagi nanti.'
                ];
            }
        }
    }

    return ['allowed' => true, 'message' => 'Permintaan diperbolehkan.'];
}

/**
 * Menghasilkan OTP 6-digit baru, membatalkan OTP lama, dan menyimpan hash HMAC ke database
 *
 * @param mysqli $conn
 * @param int $user_id
 * @param string|null $request_ip
 * @return string Plain OTP 6-digit (HANYA untuk dikirimkan melalui email, jangan disimpan di log/db)
 */
function otp_buat(mysqli $conn, int $user_id, ?string $request_ip = null): string {
    $ip = $request_ip ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

    // Pembersihan oportunistik: Hapus baris OTP yang sudah kedaluwarsa > 1 hari
    @mysqli_query($conn, "DELETE FROM password_otp WHERE expires_at < NOW() - INTERVAL 1 DAY");

    // Batalkan seluruh OTP lama yang masih aktif milik pengguna ini
    $stmt_cancel = mysqli_prepare($conn, "UPDATE password_otp SET expires_at = NOW() WHERE user_id = ? AND used_at IS NULL AND expires_at > NOW()");
    if ($stmt_cancel) {
        mysqli_stmt_bind_param($stmt_cancel, "i", $user_id);
        mysqli_stmt_execute($stmt_cancel);
        mysqli_stmt_close($stmt_cancel);
    }

    // Buat kode OTP 6-digit acak kriptografis (000000 - 999999)
    $otp_raw = sprintf('%06d', random_int(0, 999999));

    // Simpan HANYA hash HMAC-SHA256 dengan OTP_SECRET
    $otp_hash = hash_hmac('sha256', $otp_raw, OTP_SECRET);

    // Berlaku 10 menit
    $expires_at = date('Y-m-d H:i:s', time() + 600);

    $stmt_ins = mysqli_prepare(
        $conn,
        "INSERT INTO password_otp (user_id, otp_hash, expires_at, attempts, used_at, last_sent_at, request_ip)
         VALUES (?, ?, ?, 0, NULL, NOW(), ?)"
    );
    if ($stmt_ins) {
        mysqli_stmt_bind_param($stmt_ins, "isss", $user_id, $otp_hash, $expires_at, $ip);
        mysqli_stmt_execute($stmt_ins);
        mysqli_stmt_close($stmt_ins);
    }

    return $otp_raw;
}

/**
 * Mengirimkan kode OTP ke email anggota (atau menulis ke file dev log jika OTP_DEV_MODE aktif di localhost)
 *
 * @param string $email
 * @param string $nama
 * @param string $otp_raw
 * @return array{success: bool, message: string, dev_mode?: bool}
 */
function otp_kirim(string $email, string $nama, string $otp_raw): array {
    $remote_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $is_localhost = in_array($remote_ip, ['127.0.0.1', '::1', 'localhost'], true) || (php_sapi_name() === 'cli');

    // 1. Cek Mode Pengembang (OTP_DEV_MODE): Hanya boleh aktif di localhost
    if (defined('OTP_DEV_MODE') && OTP_DEV_MODE === true) {
        if ($is_localhost) {
            $log_dir = dirname(__DIR__) . '/storage';
            if (!is_dir($log_dir)) {
                @mkdir($log_dir, 0755, true);
            }
            $log_path = $log_dir . '/otp_dev.log';
            $log_entry = sprintf(
                "[%s] OTP: %s | Akun: %s (%s) | Berlaku 10 menit s.d %s | IP: %s\n",
                date('Y-m-d H:i:s'),
                $otp_raw,
                $nama,
                $email,
                date('Y-m-d H:i:s', time() + 600),
                $remote_ip
            );
            @file_put_contents($log_path, $log_entry, FILE_APPEND | LOCK_EX);

            return [
                'success'  => true,
                'message'  => 'Kode OTP berhasil dicatat ke storage/otp_dev.log (Mode Pengembang).',
                'dev_mode' => true,
            ];
        }
        // Jika bukan localhost, otomatis abaikan OTP_DEV_MODE dan lanjutkan ke SMTP asli
    }

    // 2. Format Pesan Email Resmi Berbahasa Indonesia
    $subject = "Kode OTP Pemulihan Akun — AKSA NOVA";

    $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f7f3ec; margin: 0; padding: 24px; color: #2e2820; }
    .container { max-width: 540px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e5d8c5; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
    .header { background: linear-gradient(135deg, #10151b 0%, #1e1810 100%); padding: 32px 28px; text-align: center; border-bottom: 3px solid #d8b878; }
    .header h1 { font-size: 24px; color: #d8b878; margin: 0; font-weight: 700; letter-spacing: 1px; }
    .header p { color: rgba(238,243,244,0.7); font-size: 13px; margin: 6px 0 0; }
    .content { padding: 32px 28px; }
    .greeting { font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #1a1714; }
    .text { font-size: 14px; line-height: 1.6; color: #524b42; margin-bottom: 24px; }
    .otp-box { background: #faf6ef; border: 2px dashed #d8b878; border-radius: 12px; padding: 20px; text-align: center; margin: 24px 0; }
    .otp-code { font-family: monospace, Courier, sans-serif; font-size: 36px; font-weight: 800; letter-spacing: 10px; color: #8a6323; margin: 0; }
    .otp-expiry { font-size: 12px; color: #8c7853; margin-top: 8px; font-weight: 500; }
    .warning { background: #fff8f0; border-left: 4px solid #f59e0b; padding: 14px 16px; font-size: 12px; color: #92400e; line-height: 1.5; border-radius: 0 8px 8px 0; margin-bottom: 24px; }
    .footer { text-align: center; padding: 20px 28px; font-size: 11px; color: #8c8273; border-top: 1px solid #f0e7d8; background: #faf7f2; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>AKSA NOVA</h1>
      <p>Sistem Perpustakaan Digital SMK Negeri 1 Rongga</p>
    </div>
    <div class="content">
      <div class="greeting">Halo, {$nama}!</div>
      <div class="text">
        Kami menerima permintaan pengaturan ulang kata sandi / penerbitan kartu anggota baru untuk akun perpustakaan Anda. Gunakan kode One-Time Password (OTP) berikut untuk melanjutkan:
      </div>
      <div class="otp-box">
        <div class="otp-code">{$otp_raw}</div>
        <div class="otp-expiry">Masa berlaku kode: 10 menit</div>
      </div>
      <div class="warning">
        <strong>PENTING &amp; RAHASIA:</strong><br>
        Jangan pernah membagikan kode OTP ini kepada siapa pun, termasuk pihak yang mengaku sebagai petugas perpustakaan atau admin sekolah. Petugas AKSA NOVA tidak pernah meminta kode OTP Anda.
      </div>
      <div class="text" style="font-size: 13px; color: #7a7267;">
        Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini. Kartu anggota dan akun Anda tetap aman.
      </div>
    </div>
    <div class="footer">
      Perpustakaan SMK Negeri 1 Rongga &bull; Jl. Raya Rongga, Desa Cibedug, Kec. Rongga<br>
      Email ini dikirim secara otomatis oleh sistem, mohon tidak membalas ke alamat ini.
    </div>
  </div>
</body>
</html>
HTML;

    $text = "Halo {$nama},\n\n"
          . "Kode OTP pemulihan akun AKSA NOVA Anda adalah: {$otp_raw}\n\n"
          . "Kode ini berlaku selama 10 menit.\n"
          . "PENTING: Jangan bagikan kode ini kepada siapa pun.\n\n"
          . "Jika Anda tidak merasa meminta kode ini, abaikan email ini.\n"
          . "Perpustakaan SMKN 1 Rongga";

    return kirim_email($email, $nama, $subject, $html, $text);
}

/**
 * Memverifikasi kode OTP 6-digit yang dimasukkan pengguna
 * Aturan:
 * - Mengambil baris OTP aktif terbaru milik user
 * - Cek apakah sudah pernah digunakan (used_at)
 * - Cek apakah sudah kedaluwarsa (expires_at)
 * - Cek apakah sudah melebihi 5 kali percobaan (attempts >= 5)
 * - Naikkan counter percobaan jika salah
 * - Bandingkan menggunakan hash_equals
 *
 * @param mysqli $conn
 * @param int $user_id
 * @param string $input_otp
 * @return array{success: bool, status: string, message: string, remaining?: int}
 */
function otp_verifikasi(mysqli $conn, int $user_id, string $input_otp): array {
    $clean_otp = trim($input_otp);
    if (!preg_match('/^[0-9]{6}$/', $clean_otp)) {
        return [
            'success' => false,
            'status'  => 'invalid_format',
            'message' => 'Kode OTP harus berupa 6 digit angka.'
        ];
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, otp_hash, expires_at, attempts, used_at FROM password_otp WHERE user_id = ? ORDER BY id DESC LIMIT 1"
    );
    if (!$stmt) {
        return [
            'success' => false,
            'status'  => 'db_error',
            'message' => 'Terjadi kesalahan sistem saat memproses verifikasi.'
        ];
    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$row) {
        return [
            'success' => false,
            'status'  => 'not_found',
            'message' => 'Kode OTP tidak ditemukan. Silakan minta kode baru.'
        ];
    }

    if ($row['used_at'] !== null) {
        return [
            'success' => false,
            'status'  => 'already_used',
            'message' => 'Kode OTP ini sudah pernah digunakan. Silakan minta kode baru.'
        ];
    }

    if (strtotime($row['expires_at']) < time()) {
        return [
            'success' => false,
            'status'  => 'expired',
            'message' => 'Kode OTP telah kedaluwarsa (lebih dari 10 menit). Silakan minta kode baru.'
        ];
    }

    if ((int)$row['attempts'] >= 5) {
        return [
            'success' => false,
            'status'  => 'max_attempts',
            'message' => 'Kode OTP ini telah hangus karena melebihi batas 5 kali percobaan. Silakan minta kode baru.'
        ];
    }

    // Hitung hash masukan
    $input_hash = hash_hmac('sha256', $clean_otp, OTP_SECRET);

    if (hash_equals($row['otp_hash'], $input_hash)) {
        // Berhasil! Tandai terpakai dan update attempts
        $stmt_up = mysqli_prepare($conn, "UPDATE password_otp SET used_at = NOW(), attempts = attempts + 1 WHERE id = ?");
        if ($stmt_up) {
            mysqli_stmt_bind_param($stmt_up, "i", $row['id']);
            mysqli_stmt_execute($stmt_up);
            mysqli_stmt_close($stmt_up);
        }

        return [
            'success' => true,
            'status'  => 'valid',
            'message' => 'Kode OTP berhasil diverifikasi.'
        ];
    }

    // Gagal: Naikkan counter percobaan
    $new_attempts = (int)$row['attempts'] + 1;
    $stmt_fail = mysqli_prepare($conn, "UPDATE password_otp SET attempts = ? WHERE id = ?");
    if ($stmt_fail) {
        mysqli_stmt_bind_param($stmt_fail, "ii", $new_attempts, $row['id']);
        mysqli_stmt_execute($stmt_fail);
        mysqli_stmt_close($stmt_fail);
    }

    $remaining = 5 - $new_attempts;
    if ($remaining <= 0) {
        return [
            'success'   => false,
            'status'    => 'burned',
            'remaining' => 0,
            'message'   => 'Kode OTP salah. Batas 5 kali percobaan habis, kode telah hangus. Silakan minta kode baru.'
        ];
    }

    return [
        'success'   => false,
        'status'    => 'mismatch',
        'remaining' => $remaining,
        'message'   => "Kode OTP salah. Sisa kesempatan mencoba: {$remaining} kali."
    ];
}
