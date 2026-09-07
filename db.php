<?php


$host   = "localhost"; // host MySQL kamu
$dbuser = "root";       // username MySQL kamu
$dbpass = "";           // password MySQL kamu
$dbname = "aksa_nova";  // nama database

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Set charset agar aman & mendukung seluruh karakter Unicode / emoji
mysqli_set_charset($conn, "utf8mb4");

// Pastikan tabel pilihan kelas tersedia
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `kelas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_kelas` VARCHAR(50) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
?>