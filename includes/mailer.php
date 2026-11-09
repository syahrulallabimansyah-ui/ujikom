<?php
declare(strict_types=1);

/**
 * includes/mailer.php — Pengirim Email via PHPMailer (SMTP)
 * Proyek: AKSA NOVA (Perpustakaan Digital SMKN 1 Rongga)
 */

if (file_exists(__DIR__ . '/../config_mail.php')) {
    require_once __DIR__ . '/../config_mail.php';
} else {
    require_once __DIR__ . '/../config_mail.example.php';
}

require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Menyamarkan alamat email untuk pencatatan log keamanan (misal: h***@gmail.com)
 */
function mailer_mask_email(string $email): string {
    $parts = explode('@', $email, 2);
    if (count($parts) !== 2) {
        return '***';
    }
    $local  = $parts[0];
    $domain = $parts[1];
    $prefix = $local !== '' ? mb_substr($local, 0, 1) : '';
    return $prefix . '***@' . $domain;
}

/**
 * Mengirim email menggunakan protokol SMTP terenkripsi (Gmail STARTTLS)
 *
 * @param string $to_email Alamat email tujuan
 * @param string $to_name Nama penerima
 * @param string $subject Subjek email
 * @param string $body_html Konten email format HTML
 * @param string $body_text Konten teks alternatif (plain text)
 * @return array{success: bool, message: string}
 */
function kirim_email(string $to_email, string $to_name, string $subject, string $body_html, string $body_text = ''): array {
    $mail = new PHPMailer(true);

    try {
        // Konfigurasi SMTP
        $mail->isSMTP();
        $mail->SMTPDebug   = SMTP::DEBUG_OFF; // Tanpa output debug langsung ke browser
        $mail->Host        = MAIL_HOST;
        $mail->SMTPAuth    = true;
        $mail->Username    = MAIL_USERNAME;
        $mail->Password    = MAIL_PASSWORD;

        $enc = strtolower((string)MAIL_ENCRYPTION);
        if ($enc === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->Port       = (int)MAIL_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 10;

        // Pada Gmail SMTP, alamat From wajib sama dengan MAIL_USERNAME
        $from_email = MAIL_USERNAME;
        $from_name  = (defined('MAIL_FROM_NAME') && MAIL_FROM_NAME !== '') ? MAIL_FROM_NAME : 'Perpustakaan AKSA NOVA';
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($to_email, $to_name);

        // Konten Email
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body_html;
        $mail->AltBody = $body_text !== '' ? $body_text : strip_tags($body_html);

        $mail->send();
        return [
            'success' => true,
            'message' => 'Email berhasil dikirim.',
        ];
    } catch (Exception $e) {
        // 1. Sanitasi pesan kesalahan (ErrorInfo tanpa password dan tanpa kode OTP)
        $raw_error = $mail->ErrorInfo !== '' ? $mail->ErrorInfo : $e->getMessage();
        if (defined('MAIL_PASSWORD') && MAIL_PASSWORD !== '') {
            $raw_error = str_replace(MAIL_PASSWORD, '***', $raw_error);
        }
        $sanitized_error = (string)preg_replace('/\b\d{6}\b/', '******', $raw_error);

        // 2. Catat ke storage/mail_error.log dengan email tersamar
        $log_dir = dirname(__DIR__) . '/storage';
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        $log_path = $log_dir . '/mail_error.log';
        $log_line = sprintf(
            "[%s] Gagal kirim ke %s: %s\n",
            date('Y-m-d H:i:s'),
            mailer_mask_email($to_email),
            $sanitized_error
        );
        @file_put_contents($log_path, $log_line, FILE_APPEND | LOCK_EX);

        // 3. Batalkan/hapus baris OTP yang baru dibuat agar tidak memicu jeda 60 detik atau counter salah
        if (isset($GLOBALS['conn']) && ($GLOBALS['conn'] instanceof \mysqli)) {
            $db = $GLOBALS['conn'];
            $stmt_user = mysqli_prepare($db, "SELECT id FROM users WHERE email = ? LIMIT 1");
            if ($stmt_user) {
                mysqli_stmt_bind_param($stmt_user, "s", $to_email);
                mysqli_stmt_execute($stmt_user);
                $res_u = mysqli_stmt_get_result($stmt_user);
                if ($row_u = mysqli_fetch_assoc($res_u)) {
                    $uid = (int)$row_u['id'];
                    $stmt_del = mysqli_prepare(
                        $db,
                        "DELETE FROM password_otp WHERE user_id = ? AND used_at IS NULL ORDER BY id DESC LIMIT 1"
                    );
                    if ($stmt_del) {
                        mysqli_stmt_bind_param($stmt_del, "i", $uid);
                        mysqli_stmt_execute($stmt_del);
                        mysqli_stmt_close($stmt_del);
                    }
                }
                mysqli_stmt_close($stmt_user);
            }
        }

        // 4. Pesan umum ramah dan aman tanpa membocorkan status email
        return [
            'success' => false,
            'message' => 'Kode OTP belum dapat dikirim, coba lagi beberapa saat.',
        ];
    }
}
