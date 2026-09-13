<?php
// ganti_sandi_admin.php — Proses ganti kata sandi akun admin yang sedang login
session_start();
require_once "db.php";

// Halaman tujuan setelah proses selesai (default dashboard.php).
// Dibatasi hanya boleh nama file .php polos, tidak boleh URL luar/path aneh,
// supaya parameter "redirect" tidak bisa disalahgunakan untuk open-redirect.
$redirect = basename($_POST["redirect"] ?? "dashboard.php");
if (!preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $redirect)) {
    $redirect = "dashboard.php";
}

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: sign_in.php");
    exit;
}

if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
    header("Location: $redirect");
    exit;
}

$user_id          = (int)$_SESSION["user_id"];
$current_password = trim($_POST["current_password"] ?? "");
$new_password      = trim($_POST["new_password"] ?? "");
$confirm_password  = trim($_POST["confirm_new_password"] ?? "");

$error = "";

if ($current_password === "" || $new_password === "" || $confirm_password === "") {
    $error = "Semua kolom wajib diisi.";
} elseif (strlen($new_password) < 8) {
    $error = "Kata sandi baru minimal 8 karakter.";
} elseif (!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
    $error = "Kata sandi baru harus mengandung huruf dan angka.";
} elseif ($new_password !== $confirm_password) {
    $error = "Konfirmasi kata sandi baru tidak cocok.";
}

if ($error === "") {
    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ? AND role = 'admin'");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$row) {
        $error = "Akun admin tidak ditemukan.";
    } elseif (!password_verify($current_password, $row["password"])) {
        $error = "Kata sandi saat ini salah.";
    } elseif ($current_password === $new_password) {
        $error = "Kata sandi baru tidak boleh sama dengan kata sandi lama.";
    }
}

if ($error === "") {
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt   = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ? AND role = 'admin'");
    mysqli_stmt_bind_param($stmt, "si", $hashed, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: $redirect?pw_ok=1");
    exit;
}

header("Location: $redirect?pw_err=" . urlencode($error));
exit;