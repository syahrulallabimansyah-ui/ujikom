<?php
// kartu_clear.php — Membersihkan data kartu sementara dari session setelah ditampilkan
session_start();
unset($_SESSION["kartu_data"]);
