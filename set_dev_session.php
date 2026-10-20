<?php
// Proteksi keamanan: hanya izinkan akses dari lingkungan lokal (localhost / CLI)
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($client_ip, ['127.0.0.1', '::1', 'localhost']) && php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Akses ditolak: File dev session hanya dapat diakses pada localhost.');
}

session_start();
$role = $_GET['role'] ?? 'member';
if ($role === 'admin') {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Administrator';
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    echo "SESSION_ADMIN_OK";
} else {
    $_SESSION['user_id'] = 26;
    $_SESSION['user_name'] = 'Tsaritsa Anastasia';
    $_SESSION['username'] = 'tsaritsaanastasia';
    $_SESSION['role'] = 'member';
    echo "SESSION_MEMBER_OK";
}
