<?php
// cron_cek_terlambat.php
// Dijalankan otomatis tiap hari lewat cron job di server (BUKAN diakses lewat browser).
// Tugasnya: cari semua peminjaman yang masih 'dipinjam' tapi sudah lewat batas_kembali,
// lalu catat ke tabel reminder_log supaya halaman telah_dipinjam.php tahu siapa saja
// yang perlu diingatkan hari ini (dan admin tinggal klik tombol "Kirim Pengingat WA").
//
// ─── Cara pasang cron job ───
// 1. Upload file ini ke folder yang sama dengan pinjam_buku.php / db.php di server.
// 2. Di cPanel / hosting kamu, cari menu "Cron Jobs".
// 3. Tambahkan jadwal baru, contoh jalan tiap hari jam 07:00:
//      Minute: 0   Hour: 7   Day: *   Month: *   Weekday: *
//    Command (sesuaikan path & versi PHP di hosting kamu):
//      /usr/bin/php8.3 /home/USERNAME/public_html/cron_cek_terlambat.php
// 4. Kalau hosting pakai cPanel, biasanya ada juga opsi "via URL" — TAPI itu tidak
//    disarankan untuk file ini karena tidak butuh diakses lewat browser dan supaya
//    tidak sengaja bisa dipanggil publik. Gunakan mode command line (CLI) di atas.

declare(strict_types=1);

// Cegah file ini diakses lewat browser biasa (harus dijalankan via CLI oleh cron)
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: skrip ini hanya untuk dijalankan lewat cron/CLI.');
}

require_once __DIR__ . '/db.php';
assert($conn instanceof mysqli);
mysqli_set_charset($conn, 'utf8mb4');

$today = date('Y-m-d');

$res = mysqli_query($conn,
    "SELECT id, batas_kembali FROM peminjaman
     WHERE status = 'dipinjam' AND batas_kembali < NOW()"
);

$jumlah = 0;
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $pid   = (int)$row['id'];
        $batas = new DateTimeImmutable($row['batas_kembali']);
        $now   = new DateTimeImmutable();
        $hari  = $batas->diff($now)->days;

        mysqli_query($conn,
            "INSERT INTO reminder_log (peminjaman_id, tanggal, terlambat_hari)
             VALUES ($pid, '$today', $hari)
             ON DUPLICATE KEY UPDATE terlambat_hari = $hari"
        );
        $jumlah++;
    }
}

echo '[' . date('Y-m-d H:i:s') . "] Cek keterlambatan selesai. $jumlah peminjaman terlambat terdeteksi hari ini.\n";
