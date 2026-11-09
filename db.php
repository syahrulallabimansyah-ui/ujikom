<?php
/**
 * db.php — Koneksi Database & Inisialisasi Layanan AKSA NOVA
 */
require_once __DIR__ . '/config.php';

$host   = "localhost"; // host MySQL
$dbuser = "root";       // username MySQL
$dbpass = "";           // password MySQL
$dbname = "aksa_nova";  // nama database

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset utf8mb4 agar aman dari encoding-based injection & mendukung seluruh karakter
mysqli_set_charset($conn, "utf8mb4");
@mysqli_query($conn, "SET time_zone = '+07:00'");
?>
