<?php
// update_profil_admin.php — Simpan nama & foto profil admin, serta ganti kata sandi (opsional)
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: sign_in.php");
    exit;
}

require_once "db.php";

$allowed_redirects = [
    "halaman_admin.php", "dashboard.php", "daftar_anggota.php",
    "pinjam_buku.php", "telah_dipinjam.php", "pengaturan_denda.php",
    "kelola_banner.php", "pengaturan_musik.php", "pengajuan_buku.php"
];
$redirect = $_POST["redirect"] ?? "halaman_admin.php";
if (!in_array($redirect, $allowed_redirects, true)) {
    $redirect = "halaman_admin.php";
}

$user_id = (int)$_SESSION["user_id"];

// ─────────────────────────────────────────────
//  GANTI KATA SANDI (opsional — hanya diproses kalau salah satu kolom diisi)
// ─────────────────────────────────────────────
$current_password = trim($_POST["current_password"] ?? "");
$new_password      = trim($_POST["new_password"] ?? "");
$confirm_password  = trim($_POST["confirm_new_password"] ?? "");
$want_change_password = $current_password !== "" || $new_password !== "" || $confirm_password !== "";

if ($want_change_password) {
    $pw_error = "";

    if ($current_password === "" || $new_password === "" || $confirm_password === "") {
        $pw_error = "Untuk ganti kata sandi, kolom sandi saat ini, sandi baru, dan konfirmasi harus diisi semua.";
    } elseif (strlen($new_password) < 8) {
        $pw_error = "Kata sandi baru minimal 8 karakter.";
    } elseif (!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
        $pw_error = "Kata sandi baru harus mengandung huruf dan angka.";
    } elseif ($new_password !== $confirm_password) {
        $pw_error = "Konfirmasi kata sandi baru tidak cocok.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ? AND role = 'admin'");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$row) {
            $pw_error = "Akun admin tidak ditemukan.";
        } elseif (!password_verify($current_password, $row["password"])) {
            $pw_error = "Kata sandi saat ini salah.";
        } elseif ($current_password === $new_password) {
            $pw_error = "Kata sandi baru tidak boleh sama dengan kata sandi lama.";
        }
    }

    if ($pw_error !== "") {
        header("Location: $redirect?profil_err=" . urlencode($pw_error));
        exit;
    }

    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt   = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ? AND role = 'admin'");
    mysqli_stmt_bind_param($stmt, "si", $hashed, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// ─────────────────────────────────────────────
//  SIMPAN NAMA & FOTO PROFIL (seperti semula)
// ─────────────────────────────────────────────

// ── Ambil nama baru ──
$display_name = trim(mysqli_real_escape_string($conn, $_POST["display_name"] ?? ""));
if ($display_name === "") $display_name = "Admin";

// ── Upload foto baru (opsional) ──
$foto_baru = "";
if (isset($_FILES["foto_admin"]) && $_FILES["foto_admin"]["error"] === UPLOAD_ERR_OK) {
    $dir = "uploads/profil/";
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $ext     = strtolower(pathinfo($_FILES["foto_admin"]["name"], PATHINFO_EXTENSION));
    $allowed = ["jpg","jpeg","png","webp","gif"];
    $mime    = function_exists("mime_content_type") ? mime_content_type($_FILES["foto_admin"]["tmp_name"]) : "";
    $allowed_mimes = ["image/jpeg", "image/png", "image/webp", "image/gif"];

    if (in_array($ext, $allowed) && ($mime === "" || in_array($mime, $allowed_mimes, true)) && $_FILES["foto_admin"]["size"] <= 5 * 1024 * 1024) {
        // Hapus foto lama
        $r = mysqli_query($conn, "SELECT foto FROM admin_profile LIMIT 1");
        if ($r && $row = mysqli_fetch_assoc($r)) {
            if ($row["foto"] && file_exists($row["foto"])) @unlink($row["foto"]);
        }
        $filename = "admin_" . uniqid() . "." . $ext;
        move_uploaded_file($_FILES["foto_admin"]["tmp_name"], $dir . $filename);
        $foto_baru = $dir . $filename;
    }
}

// ── Upsert ke tabel admin_profile ──
$exists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM admin_profile LIMIT 1"));

if ($exists) {
    if ($foto_baru !== "") {
        mysqli_query($conn, "UPDATE admin_profile SET display_name='$display_name', foto='$foto_baru' WHERE id={$exists['id']}");
    } else {
        mysqli_query($conn, "UPDATE admin_profile SET display_name='$display_name' WHERE id={$exists['id']}");
    }
} else {
    mysqli_query($conn, "INSERT INTO admin_profile (display_name, foto) VALUES ('$display_name', '$foto_baru')");
}

// Update nama di session juga
$_SESSION["user_name"] = $display_name;

header("Location: $redirect?profil_saved=1");
exit;