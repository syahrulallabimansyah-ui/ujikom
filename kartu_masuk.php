<?php
// kartu_masuk.php — Menyelesaikan penerbitan kartu dan langsung masuk ke akun pengguna di beranda.php
session_start();

if (isset($_SESSION["kartu_data"]["id"])) {
    require_once "db.php";
    $kid = (int)$_SESSION["kartu_data"]["id"];
    $stmt = mysqli_prepare($conn, "SELECT id, full_name, username, role, status, card_status FROM users WHERE id = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $kid);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res && ($user = mysqli_fetch_assoc($res))) {
            if (($user["status"] ?? "approved") === "approved" && ($user["card_status"] ?? "active") !== "frozen") {
                session_regenerate_id(true);
                $_SESSION["user_id"]   = $user["id"];
                $_SESSION["user_name"] = $user["full_name"];
                $_SESSION["username"]  = $user["username"];
                $_SESSION["role"]      = $user["role"] ?? "member";
            }
        }
        mysqli_stmt_close($stmt);
    }
    unset($_SESSION["kartu_data"]);
}

header("Location: beranda.php");
exit;
