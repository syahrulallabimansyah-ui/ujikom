<?php
// api_cek_pengajuan_admin.php — Endpoint polling pengajuan peminjaman buku untuk admin
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit;
}

require_once 'db.php';
assert($conn instanceof mysqli);

date_default_timezone_set('Asia/Jakarta');

// Hitung total pengajuan yang berstatus 'menunggu'
$cnt_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status = 'menunggu'");
$count_menunggu = $cnt_q ? (int)(mysqli_fetch_assoc($cnt_q)['c'] ?? 0) : 0;

$latest = null;
$latest_id = 0;

if ($count_menunggu > 0) {
    $sql = "SELECT p.id, p.user_id, p.buku_id, p.nama_peminjam, p.total_buku, p.batas_kembali, 
                   p.waktu_pengambilan, p.catatan_pengambilan, p.created_at,
                   b.judul, b.penulis, b.gambar,
                   u.kelas, u.no_hp
            FROM pengajuan_peminjaman p
            LEFT JOIN buku b ON b.id = p.buku_id
            LEFT JOIN users u ON u.id = p.user_id
            WHERE p.status = 'menunggu'
            ORDER BY p.id DESC
            LIMIT 1";

    $q = mysqli_query($conn, $sql);
    if ($q && $row = mysqli_fetch_assoc($q)) {
        $latest_id = (int)$row['id'];
        
        $ts = strtotime($row['created_at'] ?? 'now');
        $row['formatted_time'] = date('d M Y, H:i', $ts) . ' WIB';
        
        if (!empty($row['batas_kembali'])) {
            $ts_bk = strtotime($row['batas_kembali']);
            $row['batas_kembali_fmt'] = date('d M Y', $ts_bk);
        } else {
            $row['batas_kembali_fmt'] = '-';
        }

        if (empty($row['gambar']) || !file_exists(__DIR__ . '/' . $row['gambar'])) {
            $row['gambar'] = '';
        }

        $latest = $row;
    }
}

echo json_encode([
    'success'        => true,
    'count_menunggu' => $count_menunggu,
    'latest_id'      => $latest_id,
    'latest'         => $latest
]);
