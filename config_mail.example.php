<?php
declare(strict_types=1);

/**
 * Konfigurasi Email & OTP — AKSA NOVA
 * Sistem Perpustakaan Digital SMK Negeri 1 Rongga
 *
 * Salin file ini menjadi 'config_mail.php' dan sesuaikan isinya dengan kredensial SMTP aktif Anda.
 */

// Konfigurasi SMTP Server
defined('MAIL_HOST')        || define('MAIL_HOST', 'smtp.gmail.com');
defined('MAIL_PORT')        || define('MAIL_PORT', 587); // 587 untuk TLS, 465 untuk SSL
defined('MAIL_ENCRYPTION')  || define('MAIL_ENCRYPTION', 'tls'); // 'tls' atau 'ssl'
defined('MAIL_USERNAME')    || define('MAIL_USERNAME', 'perpustakaan@smkn1rongga.sch.id');
defined('MAIL_PASSWORD')    || define('MAIL_PASSWORD', 'your-app-password-here');
defined('MAIL_FROM_EMAIL')  || define('MAIL_FROM_EMAIL', 'perpustakaan@smkn1rongga.sch.id');
defined('MAIL_FROM_NAME')   || define('MAIL_FROM_NAME', 'Perpustakaan AKSA NOVA');

// Kunci Rahasia HMAC OTP (Wajib diganti dengan nilai acak yang aman di lingkungan produksi)
defined('OTP_SECRET')       || define('OTP_SECRET', 'aksa-nova-change-this-secret-key-32chars!!');

// Mode Pengembang (OTP_DEV_MODE):
// Bila bernilai true DAN REMOTE_ADDR bernilai 127.0.0.1 atau ::1 (localhost),
// OTP akan dicatat ke storage/otp_dev.log sebagai simulasi pengiriman email lokal.
// Fitur ini otomatis menolak aktif jika diakses di luar localhost demi keamanan.
defined('OTP_DEV_MODE')     || define('OTP_DEV_MODE', false);
