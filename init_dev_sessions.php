<?php
// init_dev_sessions.php — Pre-configure PHP sessions for automated screen captures
// Proteksi keamanan: hanya izinkan akses dari lingkungan lokal (localhost / CLI)
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($client_ip, ['127.0.0.1', '::1', 'localhost']) && php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Akses ditolak: File dev session hanya dapat diakses pada localhost.\n");
}
require_once "db.php";

// 1. Admin Session
session_id("aksasessionadmin999");
session_start();
$_SESSION["user_id"]   = 1;
$_SESSION["user_name"] = "Administrator";
$_SESSION["username"]  = "admin";
$_SESSION["role"]      = "admin";
session_write_close();

// 2. Member Session (Siswa)
session_id("aksasessionmember999");
session_start();
$_SESSION["user_id"]   = 26; // Tsaritsa Anastasia (member approved)
$_SESSION["user_name"] = "Tsaritsa Anastasia";
$_SESSION["username"]  = "tsaritsaanastasia";
$_SESSION["role"]      = "member";
session_write_close();

echo "Dev sessions initialized successfully: aksasessionadmin999 & aksasessionmember999\n";
