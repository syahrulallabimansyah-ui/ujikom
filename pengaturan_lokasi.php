<?php
// pengaturan_lokasi.php — Pengaturan Peta Google Maps & Jam Operasional Perpustakaan (AKSA NOVA)
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek autentikasi dan role admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: sign_in.php');
    exit;
}

require_once 'db.php';
assert($conn instanceof mysqli);
mysqli_set_charset($conn, 'utf8mb4');

// Setel zona waktu WIB untuk konsistensi jadwal operasional
date_default_timezone_set('Asia/Jakarta');
mysqli_query($conn, "SET time_zone = '+07:00'");

// ─── Hitung badge sidebar (pending anggota & pengajuan buku) ───
$cnt_p_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='member' AND status='pending'");
$pending_count = $cnt_p_q ? (int)(mysqli_fetch_assoc($cnt_p_q)['c'] ?? 0) : 0;
$cnt_a_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'");
$cnt_aju_badge = $cnt_a_q ? (int)(mysqli_fetch_assoc($cnt_a_q)['c'] ?? 0) : 0;
$sidebar_badges_loaded = true;

$page_title = 'Pengaturan Peta & Jam Buka – AKSA NOVA';
$msg        = '';
$msg_type   = '';

// ─── Profil admin untuk sidebar ───
$profil_res = mysqli_query($conn, 'SELECT * FROM admin_profile LIMIT 1');
$profil     = $profil_res ? mysqli_fetch_assoc($profil_res) : null;
$admin_name = $profil['display_name'] ?? ($_SESSION['user_name'] ?? 'Admin');
$admin_foto = $profil['foto'] ?? '';

// ─────────────────────────────────────────────
//  Helper: Ambil dan Simpan Pengaturan
// ─────────────────────────────────────────────
if (!function_exists('getSetting')) {
    function getSetting(mysqli $db, string $kunci, string $default = ''): string {
        $k   = mysqli_real_escape_string($db, $kunci);
        $res = mysqli_query($db, "SELECT nilai FROM pengaturan WHERE kunci='$k' LIMIT 1");
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row ? (string)$row['nilai'] : $default;
    }
}

if (!function_exists('setSetting')) {
    function setSetting(mysqli $db, string $kunci, string $nilai, string $keterangan = ''): void {
        $k = mysqli_real_escape_string($db, $kunci);
        $v = mysqli_real_escape_string($db, $nilai);
        $ket = mysqli_real_escape_string($db, $keterangan);
        $cek = mysqli_query($db, "SELECT id FROM pengaturan WHERE kunci='$k' LIMIT 1");
        if ($cek && mysqli_num_rows($cek) > 0) {
            mysqli_query($db, "UPDATE pengaturan SET nilai='$v', updated_at=NOW() WHERE kunci='$k'");
        } else {
            mysqli_query($db, "INSERT INTO pengaturan (kunci, nilai, keterangan, updated_at) VALUES ('$k', '$v', '$ket', NOW())");
        }
    }
}

// ─────────────────────────────────────────────
//  AKSI POST: Simpan Pengaturan
// ─────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'simpan_lokasi_jam') {
    $is_ajax = (isset($_POST['is_ajax']) && $_POST['is_ajax'] === '1') ||
               (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if (!csrf_verify()) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Sesi keamanan formulir kedaluwarsa. Silakan muat ulang halaman.']);
            exit;
        }
        $msg = 'Sesi keamanan formulir kedaluwarsa. Silakan muat ulang halaman.';
        $msg_type = 'error';
    } else {
        // 1. Lokasi & Peta (Disederhanakan 1 Input Cerdas)
    $raw_map_input = trim($_POST['lokasi_map_input'] ?? '');
    if ($raw_map_input === '') {
        $raw_map_input = 'SMK Negeri 1 Rongga, Bandung Barat';
    }

    $lokasi_map_mode = 'query';
    $lokasi_map_query = $raw_map_input;
    $lokasi_map_embed_custom = '';

    // Deteksi jika pengguna menempelkan tag iframe atau URL embed
    if (preg_match('/src="([^"]+)"/i', $raw_map_input, $m_emb) || preg_match("/src='([^']+)'/i", $raw_map_input, $m_emb)) {
        $lokasi_map_embed_custom = $m_emb[1];
        $lokasi_map_mode = 'embed';
    } elseif (strpos($raw_map_input, 'google.com/maps/embed') !== false) {
        $lokasi_map_embed_custom = $raw_map_input;
        $lokasi_map_mode = 'embed';
    } else {
        $lokasi_map_query = $raw_map_input;
        $lokasi_map_mode = 'query';
    }

    // 2. Data Instansi & Kontak
    $lokasi_sekolah   = trim($_POST['lokasi_sekolah'] ?? '');
    $lokasi_alamat    = trim($_POST['lokasi_alamat'] ?? '');
    $lokasi_telepon   = trim($_POST['lokasi_telepon'] ?? '');
    $lokasi_email     = trim($_POST['lokasi_email'] ?? '');

    // 3. Jam Buka-Tutup & Status Operasional
    $jam_buka_senin_kamis  = trim($_POST['jam_buka_senin_kamis'] ?? '07:00');
    $jam_tutup_senin_kamis = trim($_POST['jam_tutup_senin_kamis'] ?? '15:00');
    $jam_buka_jumat        = trim($_POST['jam_buka_jumat'] ?? '07:00');
    $jam_tutup_jumat       = trim($_POST['jam_tutup_jumat'] ?? '14:30');
    $jam_status_sabtu      = ($_POST['jam_status_sabtu'] ?? 'libur') === 'buka' ? 'buka' : 'libur';
    $jam_buka_sabtu        = trim($_POST['jam_buka_sabtu'] ?? '08:00');
    $jam_tutup_sabtu       = trim($_POST['jam_tutup_sabtu'] ?? '12:00');
    $jam_status_minggu     = ($_POST['jam_status_minggu'] ?? 'libur') === 'buka' ? 'buka' : 'libur';
    $jam_buka_minggu       = trim($_POST['jam_buka_minggu'] ?? '08:00');
    $jam_tutup_minggu      = trim($_POST['jam_tutup_minggu'] ?? '12:00');

    $operasional_status    = in_array($_POST['operasional_status'] ?? 'otomatis', ['otomatis', 'buka_manual', 'tutup_libur'], true) ? $_POST['operasional_status'] : 'otomatis';
    $operasional_catatan   = trim($_POST['operasional_catatan'] ?? '');

    // Otomatis bangun format ringkasan jam dari rentang jam harian
    $f_bkSK = str_replace(':', '.', $jam_buka_senin_kamis);
    $f_tpSK = str_replace(':', '.', $jam_tutup_senin_kamis);
    $f_bkJ  = str_replace(':', '.', $jam_buka_jumat);
    $f_tpJ  = str_replace(':', '.', $jam_tutup_jumat);

    if ($f_bkSK === $f_bkJ && $f_tpSK === $f_tpJ) {
        $lokasi_jam_auto = "Senin – Jumat, $f_bkSK – $f_tpSK WIB";
    } else {
        $lokasi_jam_auto = "Senin – Kamis: $f_bkSK – $f_tpSK WIB | Jumat: $f_bkJ – $f_tpJ WIB";
    }

    if ($jam_status_sabtu === 'buka') {
        $f_bkS = str_replace(':', '.', $jam_buka_sabtu);
        $f_tpS = str_replace(':', '.', $jam_tutup_sabtu);
        $lokasi_jam_auto .= " | Sabtu: $f_bkS – $f_tpS WIB";
    }
    if ($jam_status_minggu === 'buka') {
        $f_bkM = str_replace(':', '.', $jam_buka_minggu);
        $f_tpM = str_replace(':', '.', $jam_tutup_minggu);
        $lokasi_jam_auto .= " | Minggu: $f_bkM – $f_tpM WIB";
    }

    $lokasi_jam = trim($_POST['lokasi_jam'] ?? '');
    if ($lokasi_jam === '') {
        $lokasi_jam = $lokasi_jam_auto;
    }

    if ($lokasi_sekolah === '' || $lokasi_alamat === '') {
        $msg = 'Nama sekolah/instansi dan alamat perpustakaan wajib diisi.';
        $msg_type = 'error';
    } else {
        setSetting($conn, 'lokasi_sekolah', $lokasi_sekolah, 'Nama sekolah / perpustakaan');
        setSetting($conn, 'lokasi_alamat', $lokasi_alamat, 'Alamat lengkap perpustakaan');
        setSetting($conn, 'lokasi_telepon', $lokasi_telepon, 'Nomor telepon / WhatsApp perpustakaan');
        setSetting($conn, 'lokasi_email', $lokasi_email, 'Alamat email resmi perpustakaan');

        setSetting($conn, 'lokasi_map_mode', $lokasi_map_mode, 'Mode peta Google Maps');
        setSetting($conn, 'lokasi_map_query', $lokasi_map_query, 'Query pencarian Google Maps');
        setSetting($conn, 'lokasi_map_embed_custom', $lokasi_map_embed_custom, 'URL embed iframe Google Maps');

        setSetting($conn, 'jam_buka_senin_kamis', $jam_buka_senin_kamis, 'Jam buka layanan Senin sampai Kamis');
        setSetting($conn, 'jam_tutup_senin_kamis', $jam_tutup_senin_kamis, 'Jam tutup layanan Senin sampai Kamis');
        setSetting($conn, 'jam_buka_jumat', $jam_buka_jumat, 'Jam buka layanan hari Jumat');
        setSetting($conn, 'jam_tutup_jumat', $jam_tutup_jumat, 'Jam tutup layanan hari Jumat');
        setSetting($conn, 'jam_status_sabtu', $jam_status_sabtu, 'Status layanan hari Sabtu');
        setSetting($conn, 'jam_buka_sabtu', $jam_buka_sabtu, 'Jam buka layanan hari Sabtu');
        setSetting($conn, 'jam_tutup_sabtu', $jam_tutup_sabtu, 'Jam tutup layanan hari Sabtu');
        setSetting($conn, 'jam_status_minggu', $jam_status_minggu, 'Status layanan hari Minggu');
        setSetting($conn, 'jam_buka_minggu', $jam_buka_minggu, 'Jam buka layanan hari Minggu');
        setSetting($conn, 'jam_tutup_minggu', $jam_tutup_minggu, 'Jam tutup layanan hari Minggu');

        setSetting($conn, 'operasional_status', $operasional_status, 'Status operasional');
        setSetting($conn, 'operasional_catatan', $operasional_catatan, 'Catatan operasional tambahan');
        setSetting($conn, 'lokasi_jam', $lokasi_jam, 'Ringkasan jam operasional perpustakaan');

        $msg = 'Pengaturan peta dan jam operasional berhasil disimpan!';
        $msg_type = 'success';
    }

    // Jika permintaan via AJAX (Penyimpanan Otomatis / Instant Auto-Save)
    if (!empty($_POST['is_ajax'])) {
        header('Content-Type: application/json; charset=utf-8');
        if ($msg_type === 'error') {
            echo json_encode([
                'success' => false,
                'message' => $msg
            ]);
        } else {
            $embed_url = ($lokasi_map_mode === 'embed' && $lokasi_map_embed_custom !== '')
                ? $lokasi_map_embed_custom
                : ('https://www.google.com/maps?q=' . urlencode($lokasi_map_query) . '&output=embed');
            $maps_link = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($lokasi_map_query);

            // Hitung status realtime terbaru untuk direspons seketika ke halaman tanpa reload
            $sekarang_wib = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
            $hari_num = (int)$sekarang_wib->format('N');
            $jam_sekarang = $sekarang_wib->format('H:i');
            $rt_is_open = false;
            $rt_badge_text = 'Sedang Tutup';
            $rt_badge_class = 'status-closed';
            $rt_title = 'Perpustakaan Sedang Tutup';
            $rt_desc = '';

            if ($operasional_status === 'buka_manual') {
                $rt_is_open = true;
                $rt_badge_text = 'Buka Sekarang';
                $rt_badge_class = 'status-open';
                $rt_title = 'Perpustakaan Sedang Terbuka';
                $rt_desc = 'Status layanan dibuka manual oleh pustakawan (selalu buka).';
            } elseif ($operasional_status === 'tutup_libur') {
                $rt_is_open = false;
                $rt_badge_text = 'Tutup / Libur';
                $rt_badge_class = 'status-closed';
                $rt_title = 'Perpustakaan Sedang Tutup';
                $rt_desc = 'Status layanan ditutup manual oleh pustakawan (sedang libur/tutup).';
            } else {
                $hari_map = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                $nama_hari_ini = $hari_map[$hari_num] ?? '';
                if ($hari_num >= 1 && $hari_num <= 4) {
                    if ($jam_sekarang >= $jam_buka_senin_kamis && $jam_sekarang <= $jam_tutup_senin_kamis) {
                        $rt_is_open = true;
                        $rt_badge_text = 'Sedang Buka';
                        $rt_badge_class = 'status-open';
                        $rt_title = 'Perpustakaan Sedang Terbuka';
                        $rt_desc = "Hari ini $nama_hari_ini, jam operasional: $jam_buka_senin_kamis – $jam_tutup_senin_kamis WIB.";
                    } else {
                        $rt_badge_text = 'Sedang Tutup';
                        $rt_badge_class = 'status-closed';
                        $rt_title = 'Perpustakaan Sedang Tutup';
                        $rt_desc = "Hari ini $nama_hari_ini, jam operasional: $jam_buka_senin_kamis – $jam_tutup_senin_kamis WIB (di luar jam pelayanan).";
                    }
                } elseif ($hari_num === 5) {
                    if ($jam_sekarang >= $jam_buka_jumat && $jam_sekarang <= $jam_tutup_jumat) {
                        $rt_is_open = true;
                        $rt_badge_text = 'Sedang Buka';
                        $rt_badge_class = 'status-open';
                        $rt_title = 'Perpustakaan Sedang Terbuka';
                        $rt_desc = "Hari ini Jumat, jam operasional: $jam_buka_jumat – $jam_tutup_jumat WIB.";
                    } else {
                        $rt_badge_text = 'Sedang Tutup';
                        $rt_badge_class = 'status-closed';
                        $rt_title = 'Perpustakaan Sedang Tutup';
                        $rt_desc = "Hari ini Jumat, jam operasional: $jam_buka_jumat – $jam_tutup_jumat WIB (di luar jam pelayanan).";
                    }
                } elseif ($hari_num === 6) {
                    if ($jam_status_sabtu === 'buka') {
                        if ($jam_sekarang >= $jam_buka_sabtu && $jam_sekarang <= $jam_tutup_sabtu) {
                            $rt_is_open = true;
                            $rt_badge_text = 'Sedang Buka';
                            $rt_badge_class = 'status-open';
                            $rt_title = 'Perpustakaan Sedang Terbuka';
                            $rt_desc = "Hari ini Sabtu, jam operasional: $jam_buka_sabtu – $jam_tutup_sabtu WIB.";
                        } else {
                            $rt_badge_text = 'Sedang Tutup';
                            $rt_badge_class = 'status-closed';
                            $rt_title = 'Perpustakaan Sedang Tutup';
                            $rt_desc = "Hari ini Sabtu, jam operasional: $jam_buka_sabtu – $jam_tutup_sabtu WIB (di luar jam pelayanan).";
                        }
                    } else {
                        $rt_badge_text = 'Libur Akhir Pekan';
                        $rt_badge_class = 'status-closed';
                        $rt_title = 'Perpustakaan Sedang Tutup';
                        $rt_desc = 'Hari Sabtu perpustakaan tidak beroperasi (libur rutin).';
                    }
                } else {
                    if ($jam_status_minggu === 'buka') {
                        if ($jam_sekarang >= $jam_buka_minggu && $jam_sekarang <= $jam_tutup_minggu) {
                            $rt_is_open = true;
                            $rt_badge_text = 'Sedang Buka';
                            $rt_badge_class = 'status-open';
                            $rt_title = 'Perpustakaan Sedang Terbuka';
                            $rt_desc = "Hari ini Minggu, jam operasional: $jam_buka_minggu – $jam_tutup_minggu WIB.";
                        } else {
                            $rt_badge_text = 'Sedang Tutup';
                            $rt_badge_class = 'status-closed';
                            $rt_title = 'Perpustakaan Sedang Tutup';
                            $rt_desc = "Hari ini Minggu, jam operasional: $jam_buka_minggu – $jam_tutup_minggu WIB (di luar jam pelayanan).";
                        }
                    } else {
                        $rt_badge_text = 'Libur Akhir Pekan';
                        $rt_badge_class = 'status-closed';
                        $rt_title = 'Perpustakaan Sedang Tutup';
                        $rt_desc = 'Hari Minggu perpustakaan tidak beroperasi (libur rutin).';
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Perubahan otomatis tersimpan.',
                'timestamp' => date('H:i:s'),
                'lokasi_maps_embed' => $embed_url,
                'lokasi_maps_link'  => $maps_link,
                'status_info' => [
                    'is_open' => $rt_is_open,
                    'badge_text' => $rt_badge_text,
                    'badge_class' => $rt_badge_class,
                    'title' => $rt_title,
                    'note' => $rt_desc
                ]
            ]);
        }
        exit;
    }
    }
}

// ─────────────────────────────────────────────
//  Ambil Data Pengaturan Saat Ini
// ─────────────────────────────────────────────
$lokasi_sekolah          = getSetting($conn, 'lokasi_sekolah', 'SMK Negeri 1 Rongga');
$lokasi_alamat           = getSetting($conn, 'lokasi_alamat', 'Jl. Situ Gede / Jl. Raya Rongga (RT 01/RW 04), Desa Cibedug, Kec. Rongga, Kab. Bandung Barat, Jawa Barat 40565');
$lokasi_telepon          = getSetting($conn, 'lokasi_telepon', '083829165209');
$lokasi_email            = getSetting($conn, 'lokasi_email', 'perpustakaan@smkn1rongga.sch.id');

$lokasi_map_mode         = getSetting($conn, 'lokasi_map_mode', 'query');
$lokasi_map_query        = getSetting($conn, 'lokasi_map_query', 'SMK Negeri 1 Rongga, Bandung Barat');
$lokasi_map_embed_custom = getSetting($conn, 'lokasi_map_embed_custom', '');

$tampilan_input_peta     = $lokasi_map_embed_custom !== '' ? $lokasi_map_embed_custom : $lokasi_map_query;

$jam_buka_senin_kamis    = getSetting($conn, 'jam_buka_senin_kamis', '07:00');
$jam_tutup_senin_kamis   = getSetting($conn, 'jam_tutup_senin_kamis', '15:00');
$jam_buka_jumat          = getSetting($conn, 'jam_buka_jumat', '07:00');
$jam_tutup_jumat         = getSetting($conn, 'jam_tutup_jumat', '14:30');
$jam_status_sabtu        = getSetting($conn, 'jam_status_sabtu', 'libur');
$jam_buka_sabtu          = getSetting($conn, 'jam_buka_sabtu', '08:00');
$jam_tutup_sabtu         = getSetting($conn, 'jam_tutup_sabtu', '12:00');
$jam_status_minggu       = getSetting($conn, 'jam_status_minggu', 'libur');
$jam_buka_minggu         = getSetting($conn, 'jam_buka_minggu', '08:00');
$jam_tutup_minggu        = getSetting($conn, 'jam_tutup_minggu', '12:00');

$operasional_status      = getSetting($conn, 'operasional_status', 'otomatis');
$operasional_catatan     = getSetting($conn, 'operasional_catatan', 'Istirahat Shalat Jumat pukul 11.30 – 12.45 WIB. Layanan sirkulasi tutup sementara saat waktu istirahat.');
$lokasi_jam              = getSetting($conn, 'lokasi_jam', 'Senin – Jumat, 07.00 – 15.00 WIB');

// ─── Hitung Realtime Status WIB ───
$sekarang_wib = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$nama_hari_id = [
    1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
    5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
];
$nama_bulan_id = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$hari_num = (int)$sekarang_wib->format('N');
$hari_nama = $nama_hari_id[$hari_num] ?? 'Hari Ini';
$tgl_str = $sekarang_wib->format('j') . ' ' . ($nama_bulan_id[(int)$sekarang_wib->format('n')] ?? '') . ' ' . $sekarang_wib->format('Y');
$jam_str = $sekarang_wib->format('H:i') . ' WIB';

$is_open = false;
$status_badge_text = 'Sedang Tutup';
$status_badge_class = 'status-closed';
$status_desc = '';

if ($operasional_status === 'buka_manual') {
    $is_open = true;
    $status_badge_text = 'Buka (Mode Manual)';
    $status_badge_class = 'status-open';
    $status_desc = 'Status saat ini dipaksa selalu BUKA oleh pengaturan admin.';
} elseif ($operasional_status === 'tutup_libur') {
    $is_open = false;
    $status_badge_text = 'Tutup / Libur Sementara';
    $status_badge_class = 'status-closed';
    $status_desc = 'Status saat ini dipaksa TUTUP / LIBUR oleh pengaturan admin.';
} else {
    $jam_sekarang = $sekarang_wib->format('H:i');
    if ($hari_num >= 1 && $hari_num <= 4) { // Senin - Kamis
        if ($jam_sekarang >= $jam_buka_senin_kamis && $jam_sekarang <= $jam_tutup_senin_kamis) {
            $is_open = true;
            $status_badge_text = 'Sedang Buka';
            $status_badge_class = 'status-open';
            $status_desc = "Hari ini $hari_nama, jam operasional: $jam_buka_senin_kamis – $jam_tutup_senin_kamis WIB.";
        } else {
            $status_badge_text = 'Sedang Tutup';
            $status_badge_class = 'status-closed';
            $status_desc = "Hari ini $hari_nama, jam operasional: $jam_buka_senin_kamis – $jam_tutup_senin_kamis WIB (di luar jam pelayanan).";
        }
    } elseif ($hari_num === 5) { // Jumat
        if ($jam_sekarang >= $jam_buka_jumat && $jam_sekarang <= $jam_tutup_jumat) {
            $is_open = true;
            $status_badge_text = 'Sedang Buka';
            $status_badge_class = 'status-open';
            $status_desc = "Hari ini Jumat, jam operasional: $jam_buka_jumat – $jam_tutup_jumat WIB.";
        } else {
            $status_badge_text = 'Sedang Tutup';
            $status_badge_class = 'status-closed';
            $status_desc = "Hari ini Jumat, jam operasional: $jam_buka_jumat – $jam_tutup_jumat WIB (di luar jam pelayanan).";
        }
    } elseif ($hari_num === 6) { // Sabtu
        if ($jam_status_sabtu === 'buka') {
            if ($jam_sekarang >= $jam_buka_sabtu && $jam_sekarang <= $jam_tutup_sabtu) {
                $is_open = true;
                $status_badge_text = 'Sedang Buka';
                $status_badge_class = 'status-open';
                $status_desc = "Hari ini Sabtu, jam operasional: $jam_buka_sabtu – $jam_tutup_sabtu WIB.";
            } else {
                $status_badge_text = 'Sedang Tutup';
                $status_badge_class = 'status-closed';
                $status_desc = "Hari ini Sabtu, jam operasional: $jam_buka_sabtu – $jam_tutup_sabtu WIB (di luar jam pelayanan).";
            }
        } else {
            $status_badge_text = 'Libur Akhir Pekan';
            $status_badge_class = 'status-closed';
            $status_desc = 'Hari Sabtu perpustakaan tidak beroperasi (libur rutin).';
        }
    } else { // Minggu
        if ($jam_status_minggu === 'buka') {
            if ($jam_sekarang >= $jam_buka_minggu && $jam_sekarang <= $jam_tutup_minggu) {
                $is_open = true;
                $status_badge_text = 'Sedang Buka';
                $status_badge_class = 'status-open';
                $status_desc = "Hari ini Minggu, jam operasional: $jam_buka_minggu – $jam_tutup_minggu WIB.";
            } else {
                $status_badge_text = 'Sedang Tutup';
                $status_badge_class = 'status-closed';
                $status_desc = "Hari ini Minggu, jam operasional: $jam_buka_minggu – $jam_tutup_minggu WIB (di luar jam pelayanan).";
            }
        } else {
            $status_badge_text = 'Libur Akhir Pekan';
            $status_badge_class = 'status-closed';
            $status_desc = 'Hari Minggu perpustakaan tidak beroperasi (libur rutin).';
        }
    }
}

// ─── Tautan & Embed Peta untuk Pratinjau ───
$query_peta = !empty($lokasi_map_query) ? $lokasi_map_query : ($lokasi_sekolah . ", " . $lokasi_alamat);
$lokasi_maps_link = "https://www.google.com/maps/search/?api=1&query=" . urlencode($query_peta);

if ($lokasi_map_mode === 'embed' && !empty($lokasi_map_embed_custom)) {
    $lokasi_maps_embed = $lokasi_map_embed_custom;
} else {
    $lokasi_maps_embed = "https://maps.google.com/maps?q=" . urlencode($query_peta) . "&t=&z=16&ie=UTF8&iwloc=&output=embed";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Cormorant+Garamond:wght@700&display=swap" rel="stylesheet"/>
  <?php require_once 'settings_include.php'; ?>
  <style>
    :root {
      --sidebar-bg:  #10151b;
      --accent:      #d8b878;
      --accent2:     #f0d9a8;
      --btn-primary: linear-gradient(135deg,#d8b878,#c8a060);
      --text:        #eef3f4;
      --muted:       rgba(238,243,244,.72);
      --bg:          #090c10;
      --card:        #121820;
      --card-border: rgba(216,184,120,.18);
      --border-color:rgba(216,184,120,.22);
      --radius:      12px;
      --sidebar-w:   220px;
      --trans:       .2s cubic-bezier(.22,1,.36,1);
      --shadow:      0 4px 18px rgba(0,0,0,.35);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      color: var(--text);
      display: flex;
      min-height: 100vh;
      animation: bodyIn .4s ease both;
    }
    @keyframes bodyIn { from { opacity:0; } to { opacity:1; } }

    /* ── SIDEBAR ── */
    .sidebar {
      width: var(--sidebar-w);
      background: var(--sidebar-bg, #10151b);
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 28px 20px 16px;
      position: fixed;
      top: 0; left: 0; bottom: 0;
      z-index: 100;
      transition: transform var(--trans);
      overflow: hidden;
    }
    .sidebar-header { flex-shrink: 0; display:flex; flex-direction:column; align-items:center; width:100%; }
    .sidebar-nav {
      width: 100%; flex: 1 1 auto; min-height: 0;
      overflow-y: auto; overflow-x: hidden;
      display: flex; flex-direction: column;
      padding-right: 2px;
      scrollbar-width: thin;
      scrollbar-color: rgba(255,255,255,.35) transparent;
    }
    .sidebar-nav::-webkit-scrollbar { width: 5px; }
    .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.3); border-radius: 10px; }
    .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
    .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:90; }
    .sidebar-overlay.open { display:block; }
    .sidebar-toggle {
      display:none; position:fixed; top:14px; left:14px; z-index:200;
      width:42px; height:42px; border-radius:10px; cursor:pointer;
      align-items:center; justify-content:center;
      background:#161e27; border:1.5px solid var(--accent,#d8b878);
      box-shadow:0 4px 16px rgba(0,0,0,.45); color:var(--accent,#d8b878);
      transition:all .2s;
    }
    .sidebar-toggle svg { width:22px; height:22px; stroke:var(--accent,#d8b878); color:var(--accent,#d8b878); stroke-width:2.3px; }
    .sidebar-toggle:hover { background:rgba(216,184,120,.18); border-color:var(--accent2,#f0d9a8); }
    .sidebar-toggle:active { transform:scale(0.96); }

    .avatar-wrap { cursor:pointer; position:relative; }
    .avatar-circle {
      width:72px; height:72px; max-width:72px; max-height:72px; border-radius:50%;
      background:#c0c0c8; overflow:hidden;
      border:3px solid rgba(255,255,255,.25);
      display:flex; align-items:center; justify-content:center;
      margin-bottom: 14px;
    }
    .avatar-circle img { width:100%; height:100%; object-fit:cover; display:block; }
    .avatar-circle .default-icon { width:52px; height:52px; color:#888; }
    .avatar-overlay {
      position:absolute; inset:0; border-radius:50%; background:rgba(0,0,0,.4);
      display:flex; align-items:center; justify-content:center; opacity:0;
      transition:opacity .2s;
    }
    .avatar-wrap:hover .avatar-overlay { opacity:1; }
    .avatar-overlay svg { width:24px; height:24px; stroke:#fff; }

    .admin-name-label { color:var(--text,#eef3f4); font-size:.95rem; font-weight:700; margin-bottom:8px; text-align:center; }

    .sidebar-btn {
      width:100%; display:flex; align-items:center; gap:9px; padding:7.5px 12px; border-radius:8px;
      border:1px solid rgba(216,184,120,.16);
      background:rgba(255,255,255,.05); color:var(--text,#eef3f4);
      font-family:inherit; font-size:.82rem; font-weight:700; cursor:pointer; margin-bottom:4px;
      transition:all var(--trans);
      text-align:left; text-decoration:none;
      flex-shrink:0; box-sizing:border-box;
      line-height:1.25;
    }
    .sidebar-btn svg { width:16px; height:16px; flex-shrink:0; color:var(--accent,#d8b878); stroke:var(--accent,#d8b878); stroke-width:2px; transition:stroke .2s, transform .2s; }
    .sidebar-btn:hover { background:rgba(216,184,120,.16); border-color:var(--accent,#d8b878); color:#fff; }
    .sidebar-btn:hover svg { color:#fff; stroke:#fff; }
    .sidebar-btn.active {
      background:linear-gradient(135deg,#d8b878,#c8a060);
      border-color:var(--accent,#d8b878);
      color:#121820; font-weight:800;
      box-shadow:0 4px 14px rgba(216,184,120,.35);
    }
    .sidebar-btn.active svg { color:#121820; stroke:#121820; stroke-width:2.2px; }

    .sidebar-btn.sidebar-btn-logout {
      color: #e74c3c !important;
      margin-top: 10px;
    }
    .sidebar-btn.sidebar-btn-logout svg {
      color: #e74c3c !important;
      stroke: #e74c3c !important;
    }
    .sidebar-btn.sidebar-btn-logout:hover {
      background: rgba(231,76,60,.14) !important;
      border-color: #e74c3c !important;
      color: #ff6b6b !important;
    }
    .sidebar-btn.sidebar-btn-logout:hover svg {
      color: #ff6b6b !important;
      stroke: #ff6b6b !important;
      transform: translateX(2px);
    }

    /* ── MOBILE TOPBAR ── */
    .mobile-topbar {
      display:none; position:fixed; top:0; left:0; right:0; height:56px;
      background:#10151b; border-bottom:1px solid rgba(216,184,120,.18);
      z-index:95; align-items:center; padding:0 16px; gap:12px;
    }
    .mobile-topbar-brand { display:flex; align-items:center; gap:8px; font-weight:800; font-size:.9rem; color:var(--text); }
    .mobile-topbar-brand svg { width:18px; height:18px; color:var(--accent); stroke:var(--accent); }
    .mobile-topbar-divider { width:1px; height:22px; background:rgba(255,255,255,.15); }

    /* ── MAIN CONTENT ── */
    .main {
      view-transition-name: app-main;
      margin-left: var(--sidebar-w);
      flex: 1;
      padding: 28px 32px 50px;
      transition: margin-left var(--trans);
      max-width: 1040px;
    }

    .alert {
      padding: 14px 18px; border-radius: 9px; font-size: .88rem;
      font-weight: 700; margin-bottom: 22px; animation: fadeUp .35s both;
      display: flex; align-items: center; gap: 10px;
    }
    .alert svg { width: 18px; height: 18px; flex-shrink: 0; }
    .alert-success { background: rgba(16,185,129,.14); color: #34d399; border: 1px solid rgba(16,185,129,.35); }
    .alert-error   { background: rgba(239,68,68,.14); color: #f87171; border: 1px solid rgba(239,68,68,.35); }
    @keyframes fadeUp { from { opacity:0; transform:translateY(8px);} to { opacity:1; transform:translateY(0);} }

    .content-header { margin-bottom: 24px; animation: fadeUp .4s .04s both; }
    .content-title { font-family:'Cormorant Garamond',serif; font-size:1.85rem; font-weight:700; color:var(--text); letter-spacing:.01em; }
    .content-sub { font-size:.88rem; color:var(--muted); margin-top:5px; line-height: 1.5; font-weight: 500; }

    /* ── REALTIME STATUS CARD ── */
    .realtime-card {
      background: var(--card);
      border: 1px solid var(--card-border);
      border-radius: var(--radius);
      padding: 20px 24px;
      margin-bottom: 24px;
      box-shadow: var(--shadow);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      flex-wrap: wrap;
      animation: fadeUp .4s .08s both;
    }
    .realtime-left { display: flex; align-items: center; gap: 16px; }
    .realtime-icon-wrap {
      width: 48px; height: 48px; border-radius: 50%;
      background: rgba(216,184,120,.12);
      border: 1.5px solid rgba(216,184,120,.35);
      display: flex; align-items: center; justify-content: center;
      color: var(--accent); flex-shrink: 0;
    }
    .realtime-icon-wrap svg { width: 24px; height: 24px; }
    .realtime-meta .current-time { font-size: .84rem; color: var(--muted); font-weight: 600; }
    .realtime-meta .status-title { font-size: 1.08rem; font-weight: 800; color: var(--text); margin-top: 2px; }
    .realtime-meta .status-note { font-size: .8rem; color: var(--muted); margin-top: 3px; font-weight: 500; }

    .status-badge {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 7px 16px; border-radius: 50px;
      font-size: .8rem; font-weight: 800; letter-spacing: .03em;
      text-transform: uppercase;
    }
    .status-open {
      background: rgba(16,185,129,.18); color: #34d399;
      border: 1px solid rgba(16,185,129,.4);
      box-shadow: 0 0 12px rgba(16,185,129,.2);
    }
    .status-open::before {
      content: ''; width: 8px; height: 8px; border-radius: 50%;
      background: #34d399; box-shadow: 0 0 8px #34d399;
    }
    .status-closed {
      background: rgba(239,68,68,.16); color: #f87171;
      border: 1px solid rgba(239,68,68,.4);
      box-shadow: 0 0 12px rgba(239,68,68,.15);
    }
    .status-closed::before {
      content: ''; width: 8px; height: 8px; border-radius: 50%;
      background: #f87171; box-shadow: 0 0 8px #f87171;
    }

    /* ── CARD SECTIONS ── */
    .card {
      background: var(--card);
      border-radius: var(--radius);
      border: 1px solid var(--card-border);
      box-shadow: var(--shadow);
      padding: 24px 26px;
      margin-bottom: 24px;
      animation: fadeUp .45s .12s both;
    }
    .card-header {
      display: flex; align-items: flex-start; justify-content: space-between;
      gap: 16px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color);
      padding-bottom: 14px;
    }
    .card-title-group { display: flex; align-items: center; gap: 10px; }
    .card-icon {
      width: 32px; height: 32px; border-radius: 8px;
      background: rgba(216,184,120,.12); color: var(--accent);
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .card-icon svg { width: 18px; height: 18px; }
    .card h2 {
      font-family:'Cormorant Garamond',serif; font-size:1.35rem; font-weight:700;
      color: var(--text);
    }
    .card .hint { font-size:.84rem; color: var(--muted); margin-top: 3px; font-weight: 500; }

    /* ── BESPOKE STATUS SELECTOR (TIDAK LAGI DROPDOWN DEFAULT) ── */
    .status-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-bottom: 22px;
    }
    .status-opt-card {
      position: relative;
      display: flex;
      flex-direction: column;
      padding: 14px 16px;
      border-radius: 10px;
      border: 1.5px solid var(--border-color);
      background: var(--bg);
      cursor: pointer;
      transition: all .2s ease;
    }
    .status-opt-card:hover {
      border-color: var(--accent);
      transform: translateY(-2px);
      box-shadow: 0 4px 14px rgba(0,0,0,.08);
    }
    .status-opt-card.active {
      border-color: var(--accent);
      background: rgba(216,184,120,.12);
      box-shadow: 0 4px 16px rgba(216,184,120,.2);
    }
    .status-opt-card input[type="radio"] {
      position: absolute;
      opacity: 0;
      pointer-events: none;
    }
    .status-opt-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 6px;
    }
    .status-opt-indicator {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: .88rem;
      font-weight: 800;
      color: var(--text);
    }
    .status-dot {
      width: 9px;
      height: 9px;
      border-radius: 50%;
      flex-shrink: 0;
    }
    .dot-auto   { background: #3b82f6; box-shadow: 0 0 6px rgba(59,130,246,.6); }
    .dot-open   { background: #10b981; box-shadow: 0 0 6px rgba(16,185,129,.6); }
    .dot-closed { background: #ef4444; box-shadow: 0 0 6px rgba(239,68,68,.6); }
    .status-opt-desc {
      font-size: .76rem;
      color: var(--muted);
      line-height: 1.4;
      font-weight: 500;
    }
    .status-chip-tag {
      font-size: .62rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .04em;
      padding: 2px 7px;
      border-radius: 20px;
      background: rgba(216,184,120,.2);
      color: var(--accent);
      border: 1px solid rgba(216,184,120,.4);
    }

    /* ── BESPOKE SCHEDULE LIST & TIME CAPSULES (TIDAK LAGI BARIS POLOS) ── */
    .schedule-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 22px;
    }
    .schedule-card {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 14px 18px;
      border-radius: 11px;
      background: var(--bg);
      border: 1.5px solid var(--border-color);
      transition: all .2s;
    }
    .schedule-card:hover {
      border-color: rgba(216,184,120,.45);
      box-shadow: 0 4px 14px rgba(0,0,0,.06);
    }
    .day-identity {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .day-badge-icon {
      width: 38px;
      height: 38px;
      border-radius: 9px;
      background: rgba(216,184,120,.12);
      border: 1.5px solid rgba(216,184,120,.28);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--accent);
      flex-shrink: 0;
    }
    .day-badge-icon svg { width: 18px; height: 18px; }
    .day-title-wrap h3 {
      font-size: .92rem;
      font-weight: 800;
      color: var(--text);
      margin-bottom: 2px;
      line-height: 1.2;
    }
    .day-tagline {
      font-size: .74rem;
      color: var(--muted);
      font-weight: 600;
    }

    /* Time Range Capsule */
    .time-capsule {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }
    .time-pill {
      display: flex;
      flex-direction: column;
      background: var(--card);
      border: 1.5px solid var(--border-color);
      border-radius: 8px;
      padding: 5px 12px;
      transition: all .18s;
      min-width: 110px;
    }
    .time-pill:hover,
    .time-pill:focus-within {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(216,184,120,.2);
    }
    .time-pill-label {
      font-size: .64rem;
      font-weight: 800;
      letter-spacing: .06em;
      color: var(--muted);
      text-transform: uppercase;
      margin-bottom: 2px;
    }
    .time-input-wrap {
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .time-input-wrap svg {
      width: 14px;
      height: 14px;
      color: var(--accent);
      flex-shrink: 0;
    }
    .custom-time-input {
      border: none !important;
      background: transparent !important;
      font-family: inherit;
      font-size: .92rem !important;
      font-weight: 800 !important;
      color: var(--text) !important;
      outline: none;
      padding: 0 !important;
      width: 100%;
      cursor: pointer;
    }
    .time-arrow {
      color: var(--muted);
      display: flex;
      align-items: center;
      padding: 0 2px;
    }
    .time-arrow svg { width: 16px; height: 16px; }
    .time-zone-badge {
      font-size: .72rem;
      font-weight: 800;
      padding: 5px 9px;
      border-radius: 6px;
      background: rgba(216,184,120,.14);
      color: var(--accent);
      border: 1px solid rgba(216,184,120,.3);
    }

    /* Weekend Segmented Toggle */
    .weekend-toggle-wrap {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }
    .weekend-toggle-group {
      display: inline-flex;
      padding: 3px;
      background: var(--card);
      border: 1.5px solid var(--border-color);
      border-radius: 9px;
    }
    .weekend-toggle-btn {
      padding: 6px 14px;
      font-size: .78rem;
      font-weight: 700;
      font-family: inherit;
      border-radius: 6px;
      border: none;
      background: transparent;
      color: var(--muted);
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all .18s;
    }
    .weekend-toggle-btn:hover { color: var(--text); }
    .weekend-toggle-btn.active-libur {
      background: rgba(239,68,68,.18);
      color: #f87171;
      font-weight: 800;
    }
    .weekend-toggle-btn.active-buka {
      background: rgba(16,185,129,.18);
      color: #34d399;
      font-weight: 800;
    }

        /* ── LIVE SCHEDULE SUMMARY (OTOMATIS DARI JADWAL HARIAN) ── */
    .schedule-auto-summary {
      border: 1.5px solid var(--border-color);
      border-radius: 12px;
      padding: 16px 20px;
      background: var(--bg);
      margin-bottom: 20px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .auto-summary-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
    }
    .auto-summary-title {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: .78rem;
      font-weight: 800;
      color: var(--text);
      text-transform: uppercase;
      letter-spacing: .05em;
    }
    .auto-summary-title svg {
      width: 16px;
      height: 16px;
      color: var(--accent);
    }
    .auto-summary-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: .72rem;
      font-weight: 700;
      background: rgba(16, 185, 129, 0.12);
      color: #059669;
      border: 1px solid rgba(16, 185, 129, 0.28);
    }
    body.dark .auto-summary-badge {
      background: rgba(16, 185, 129, 0.18);
      color: #34d399;
      border-color: rgba(16, 185, 129, 0.35);
    }
    .auto-summary-capsule {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      background: var(--card);
      border: 1.5px solid var(--border-color);
      border-radius: 10px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .auto-summary-capsule svg {
      width: 18px;
      height: 18px;
      color: var(--accent);
      flex-shrink: 0;
    }
    .auto-summary-text {
      font-size: .94rem;
      font-weight: 700;
      color: var(--text);
      letter-spacing: .01em;
    }
    .auto-summary-hint {
      font-size: .75rem;
      color: var(--muted);
      line-height: 1.45;
    }


    /* Form Fields General */
    .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
    .col-span-2 { grid-column: span 2; }
    .field { margin-bottom: 18px; }
    .field label {
      display: flex; align-items: center; justify-content: space-between;
      font-size:.78rem; font-weight:800; color:var(--text);
      margin-bottom: 6px; text-transform:uppercase; letter-spacing:.04em;
    }
    .field label .label-opt { font-size: .72rem; color: var(--muted); text-transform: none; font-weight: 600; }
    .field input[type="text"],
    .field input[type="email"],
    .field select,
    .field textarea {
      width:100%; padding:11px 14px; border-radius:8px;
      border:1.5px solid var(--border-color);
      font-family:'Nunito',sans-serif; font-size:.9rem; font-weight:600;
      color: var(--text); background: var(--bg);
      outline: none; transition: all .18s ease;
    }
    .field input:focus,
    .field select:focus,
    .field textarea:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(216,184,120,.2);
    }
    .field textarea { resize: vertical; min-height: 72px; line-height: 1.5; }
    .field .field-help { font-size: .8rem; color: var(--muted); margin-top: 5px; line-height: 1.45; font-weight: 500; }

    /* Map Search Input & Preview */
    .map-input-group {
      display: flex;
      align-items: stretch;
      gap: 10px;
    }
    .map-input-group input {
      flex: 1;
    }
    .btn-test-map {
      display: inline-flex; align-items: center; gap: 7px;
      background: var(--bg); border: 1.5px solid var(--border-color);
      color: var(--text); font-family: inherit; font-size: .84rem; font-weight: 700;
      padding: 0 16px; border-radius: 8px; text-decoration: none;
      transition: all .2s; white-space: nowrap;
    }
    .btn-test-map:hover { background: rgba(216,184,120,.15); border-color: var(--accent); color: #fff; }
    .btn-test-map svg { width: 16px; height: 16px; color: var(--accent); stroke: var(--accent); }

    .map-preview-wrap {
      margin-top: 14px;
      border-radius: 11px; overflow: hidden;
      border: 1.5px solid var(--border-color);
      height: 260px; position: relative; background: #1a1a24;
    }
    .map-preview-wrap iframe { width: 100%; height: 100%; border: 0; }

    /* Live Preview Section */
    .preview-info-card {
      border-radius: 11px;
      border: 1.5px solid var(--border-color);
      background: var(--bg);
      padding: 20px;
    }
    .preview-info-item { display: flex; gap: 12px; margin-bottom: 14px; }
    .preview-info-icon {
      width: 32px; height: 32px; border-radius: 8px;
      background: rgba(216,184,120,.12); color: var(--accent);
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .preview-info-icon svg { width: 16px; height: 16px; }
    .preview-info-text h4 { font-size: .78rem; text-transform: uppercase; color: var(--muted); letter-spacing: .04em; margin-bottom: 2px; font-weight: 700; }
    .preview-info-text p { font-size: .88rem; color: var(--text); line-height: 1.45; }
.btn-simpan {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--btn-primary); border: 0;
      color: #121820; font-family: inherit; font-size: .92rem; font-weight: 800;
      padding: 12px 28px; border-radius: 8px; cursor: pointer;
      box-shadow: 0 4px 16px rgba(216,184,120,.35);
      transition: all .2s ease;
    }
    .btn-simpan:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(216,184,120,.45);
      filter: brightness(1.05);
    }
    .btn-simpan svg { width: 18px; height: 18px; stroke: #121820; stroke-width: 2.2px; }

    /* ══════════════════════════════════════════════════════════════
       HIGH CONTRAST LIGHT MODE (PERBAIKAN TOTAL TEKS & KONTROL UI)
       ══════════════════════════════════════════════════════════════ */
    html.light,
    html.theme-light,
    html:not(.dark) {
      --bg: #f5f7fa;
      --text: #111827;
      --muted: #4b5563;
      --card: #ffffff;
      --card-border: #e2e8f0;
      --border-color: #cbd5e1;
      --shadow: 0 4px 18px rgba(0,0,0,0.06);
    }

    html.light body,
    html.theme-light body,
    html:not(.dark) body {
      background-color: #f5f7fa !important;
      color: #111827 !important;
    }

    html.light .content-title,
    html.theme-light .content-title,
    html:not(.dark) .content-title {
      color: #111827 !important;
    }
    html.light .content-sub,
    html.theme-light .content-sub,
    html:not(.dark) .content-sub {
      color: #4b5563 !important;
      font-weight: 600 !important;
    }

    html.light .card,
    html.theme-light .card,
    html:not(.dark) .card {
      background: #ffffff !important;
      border: 1px solid #e2e8f0 !important;
      box-shadow: 0 4px 16px rgba(0,0,0,0.06) !important;
    }
    html.light .card h2,
    html.theme-light .card h2,
    html:not(.dark) .card h2 {
      color: #111827 !important;
    }
    html.light .card .hint,
    html.theme-light .card .hint,
    html:not(.dark) .card .hint {
      color: #4b5563 !important;
      font-weight: 500 !important;
    }

    /* Light Mode Status Cards */
    html.light .status-opt-card,
    html.theme-light .status-opt-card,
    html:not(.dark) .status-opt-card {
      background: #f8fafc !important;
      border: 1.5px solid #cbd5e1 !important;
    }
    html.light .status-opt-card.active,
    html.theme-light .status-opt-card.active,
    html:not(.dark) .status-opt-card.active {
      background: #fefce8 !important;
      border-color: #ca8a04 !important;
      box-shadow: 0 4px 16px rgba(202,138,4,0.18) !important;
    }
    html.light .status-opt-indicator,
    html.theme-light .status-opt-indicator,
    html:not(.dark) .status-opt-indicator {
      color: #111827 !important;
    }
    html.light .status-opt-desc,
    html.theme-light .status-opt-desc,
    html:not(.dark) .status-opt-desc {
      color: #4b5563 !important;
    }

    /* Light Mode Schedule Card */
    html.light .schedule-card,
    html.theme-light .schedule-card,
    html:not(.dark) .schedule-card {
      background: #f8fafc !important;
      border: 1.5px solid #cbd5e1 !important;
    }
    html.light .day-title-wrap h3,
    html.theme-light .day-title-wrap h3,
    html:not(.dark) .day-title-wrap h3 {
      color: #111827 !important;
    }
    html.light .day-tagline,
    html.theme-light .day-tagline,
    html:not(.dark) .day-tagline {
      color: #4b5563 !important;
    }

    /* Light Mode Time Capsule */
    html.light .time-pill,
    html.theme-light .time-pill,
    html:not(.dark) .time-pill {
      background: #ffffff !important;
      border: 1.5px solid #cbd5e1 !important;
    }
    html.light .time-pill-label,
    html.theme-light .time-pill-label,
    html:not(.dark) .time-pill-label {
      color: #6b7280 !important;
    }
    html.light .custom-time-input,
    html.theme-light .custom-time-input,
    html:not(.dark) .custom-time-input {
      color: #111827 !important;
    }
    html.light .time-arrow,
    html.theme-light .time-arrow,
    html:not(.dark) .time-arrow {
      color: #6b7280 !important;
    }
    html.light .time-zone-badge,
    html.theme-light .time-zone-badge,
    html:not(.dark) .time-zone-badge {
      background: #fef3c7 !important;
      color: #92400e !important;
      border-color: #fde68a !important;
    }

    /* Light Mode Weekend Toggle */
    html.light .weekend-toggle-group,
    html.theme-light .weekend-toggle-group,
    html:not(.dark) .weekend-toggle-group {
      background: #f1f5f9 !important;
      border: 1.5px solid #cbd5e1 !important;
    }
    html.light .weekend-toggle-btn,
    html.theme-light .weekend-toggle-btn,
    html:not(.dark) .weekend-toggle-btn {
      color: #4b5563 !important;
    }
    html.light .weekend-toggle-btn.active-libur,
    html.theme-light .weekend-toggle-btn.active-libur,
    html:not(.dark) .weekend-toggle-btn.active-libur {
      background: #fee2e2 !important;
      color: #991b1b !important;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08) !important;
    }
    html.light .weekend-toggle-btn.active-buka,
    html.theme-light .weekend-toggle-btn.active-buka,
    html:not(.dark) .weekend-toggle-btn.active-buka {
      background: #d1fae5 !important;
      color: #065f46 !important;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08) !important;
    }

    /* Light Mode Summary Box */
    html.light .summary-box,
    html.theme-light .summary-box,
    html:not(.dark) .summary-box {
      background: #f8fafc !important;
      border: 1.5px solid #cbd5e1 !important;
    }
    html.light .summary-label,
    html.theme-light .summary-label,
    html:not(.dark) .summary-label {
      color: #111827 !important;
    }
    html.light .summary-sub,
    html.theme-light .summary-sub,
    html:not(.dark) .summary-sub {
      color: #4b5563 !important;
    }
    html.light .summary-text-input,
    html.theme-light .summary-text-input,
    html:not(.dark) .summary-text-input {
      background: #ffffff !important;
      border: 1.5px solid #cbd5e1 !important;
      color: #111827 !important;
    }
    html.light .summary-preview-strip,
    html.theme-light .summary-preview-strip,
    html:not(.dark) .summary-preview-strip {
      background: #fef9c3 !important;
      border-color: #fde047 !important;
    }
    html.light .preview-badge-mini,
    html.theme-light .preview-badge-mini,
    html:not(.dark) .preview-badge-mini {
      color: #854d0e !important;
    }
    html.light .preview-text-live,
    html.theme-light .preview-text-live,
    html:not(.dark) .preview-text-live {
      color: #1e293b !important;
    }
    html.light .btn-refresh-schedule,
    html.theme-light .btn-refresh-schedule,
    html:not(.dark) .btn-refresh-schedule {
      background: rgba(184,147,69,0.12) !important;
      border: 1.5px solid rgba(184,147,69,0.35) !important;
      color: #785a19 !important;
    }
    html.light .btn-refresh-schedule:hover,
    html.theme-light .btn-refresh-schedule:hover,
    html:not(.dark) .btn-refresh-schedule:hover {
      background: rgba(184,147,69,0.22) !important;
      color: #553e0d !important;
    }

    /* Form Fields in Light Mode */
    html.light .field label,
    html.theme-light .field label,
    html:not(.dark) .field label {
      color: #111827 !important;
      font-weight: 800 !important;
    }
    html.light .field .field-help,
    html.theme-light .field .field-help,
    html:not(.dark) .field .field-help {
      color: #4b5563 !important;
      font-weight: 600 !important;
    }
    html.light .field input[type="text"],
    html.light .field input[type="email"],
    html.light .field select,
    html.light .field textarea,
    html.theme-light .field input[type="text"],
    html.theme-light .field input[type="email"],
    html.theme-light .field select,
    html.theme-light .field textarea,
    html:not(.dark) .field input[type="text"],
    html:not(.dark) .field input[type="email"],
    html:not(.dark) .field select,
    html:not(.dark) .field textarea {
      background: #ffffff !important;
      color: #111827 !important;
      border: 1.5px solid #cbd5e1 !important;
      font-weight: 600 !important;
    }
    html.light .btn-test-map,
    html.theme-light .btn-test-map,
    html:not(.dark) .btn-test-map {
      background: #f1f5f9 !important;
      border: 1.5px solid #cbd5e1 !important;
      color: #1e293b !important;
    }
    html.light .btn-test-map:hover,
    html.theme-light .btn-test-map:hover,
    html:not(.dark) .btn-test-map:hover {
      background: #e2e8f0 !important;
      color: #0f172a !important;
    }

    /* Realtime Card in Light Mode */
    html.light .realtime-card,
    html.theme-light .realtime-card,
    html:not(.dark) .realtime-card {
      background: #ffffff !important;
      border: 1px solid #e2e8f0 !important;
      box-shadow: 0 4px 16px rgba(0,0,0,0.06) !important;
    }
    html.light .realtime-meta .current-time,
    html.theme-light .realtime-meta .current-time,
    html:not(.dark) .realtime-meta .current-time {
      color: #4b5563 !important;
      font-weight: 600 !important;
    }
    html.light .realtime-meta .status-title,
    html.theme-light .realtime-meta .status-title,
    html:not(.dark) .realtime-meta .status-title {
      color: #111827 !important;
      font-weight: 800 !important;
    }
    html.light .realtime-meta .status-note,
    html.theme-light .realtime-meta .status-note,
    html:not(.dark) .realtime-meta .status-note {
      color: #4b5563 !important;
      font-weight: 500 !important;
    }

    /* Badges in Light Mode */
    html.light .status-badge.status-open,
    html.theme-light .status-badge.status-open,
    html:not(.dark) .status-badge.status-open {
      background: #ecfdf5 !important;
      color: #047857 !important;
      border: 1.5px solid #10b981 !important;
      font-weight: 800;
    }
    html.light .status-badge.status-open::before,
    html.theme-light .status-badge.status-open::before,
    html:not(.dark) .status-badge.status-open::before {
      background: #059669 !important;
      box-shadow: 0 0 6px #059669 !important;
    }
    html.light .status-badge.status-closed,
    html.theme-light .status-badge.status-closed,
    html:not(.dark) .status-badge.status-closed {
      background: #fef2f2 !important;
      color: #b91c1c !important;
      border: 1.5px solid #ef4444 !important;
      font-weight: 800;
    }
    html.light .status-badge.status-closed::before,
    html.theme-light .status-badge.status-closed::before,
    html:not(.dark) .status-badge.status-closed::before {
      background: #dc2626 !important;
      box-shadow: 0 0 6px #dc2626 !important;
    }

    /* Preview Card in Light Mode */
    html.light .preview-info-card,
    html.theme-light .preview-info-card,
    html:not(.dark) .preview-info-card {
      background: #f8fafc !important;
      border: 1.5px solid #cbd5e1 !important;
    }
    html.light .preview-info-text h4,
    html.theme-light .preview-info-text h4,
    html:not(.dark) .preview-info-text h4 {
      color: #4b5563 !important;
      font-weight: 700 !important;
    }
    html.light .preview-info-text p,
    html.theme-light .preview-info-text p,
    html:not(.dark) .preview-info-text p {
      color: #111827 !important;
    }

    /* Submit Bar in Light Mode */
    


    /* Responsive */
    @media (max-width: 900px) {
      :root { --sidebar-w: 0px; }
      body { flex-direction: column; }
      .mobile-topbar { display: flex; }
      .sidebar {
        transform: translateX(-100%);
        width: 260px; z-index: 150;
      }
      .sidebar.open { transform: translateX(0); }
      .sidebar-toggle { display: flex; }
      .main { margin-left: 0; padding: 76px 18px 40px; }
      .form-grid { grid-template-columns: 1fr; }
      .col-span-2 { grid-column: auto; }
      .status-grid { grid-template-columns: 1fr; }
      .schedule-card { flex-direction: column; align-items: flex-start; gap: 12px; }
      .time-capsule { width: 100%; }
      .btn-simpan { justify-content: center; }
    }
    /* ── AUTOSAVE STATUS BADGES & TOAST ── */
    .autosave-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 14px;
      border-radius: 999px;
      font-size: .8rem;
      font-weight: 600;
      transition: all .25s ease;
      background: rgba(16, 185, 129, 0.12);
      color: #059669;
      border: 1px solid rgba(16, 185, 129, 0.3);
    }
    body.dark .autosave-pill.saved {
      background: rgba(16, 185, 129, 0.16);
      color: #34d399;
      border-color: rgba(16, 185, 129, 0.32);
    }
    .autosave-pill.saving {
      background: rgba(245, 158, 11, 0.14);
      color: #d97706;
      border: 1px solid rgba(245, 158, 11, 0.35);
    }
    body.dark .autosave-pill.saving {
      background: rgba(245, 158, 11, 0.18);
      color: #fbbf24;
      border-color: rgba(245, 158, 11, 0.35);
    }
    .autosave-pill.error {
      background: rgba(239, 68, 68, 0.12);
      color: #dc2626;
      border: 1px solid rgba(239, 68, 68, 0.3);
    }
    body.dark .autosave-pill.error {
      background: rgba(239, 68, 68, 0.18);
      color: #f87171;
      border-color: rgba(239, 68, 68, 0.35);
    }
    .autosave-icon-check, .autosave-icon-spin {
      width: 14px;
      height: 14px;
      flex-shrink: 0;
    }
    .autosave-icon-spin {
      display: none;
      animation: autoSpin 0.8s linear infinite;
    }
    .autosave-pill.saving .autosave-icon-spin {
      display: inline-block;
    }
    .autosave-pill.saving .autosave-icon-check {
      display: none;
    }
    @keyframes autoSpin {
      100% { transform: rotate(360deg); }
    }

    .autosave-pill-bottom {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: .84rem;
      font-weight: 600;
      color: var(--text);
    }
    .autosave-dot {
      width: 9px;
      height: 9px;
      border-radius: 50%;
      background: #10b981;
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
      transition: all .25s ease;
      flex-shrink: 0;
    }
    .autosave-pill-bottom.saving .autosave-dot {
      background: #f59e0b;
      box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25);
      animation: dotPulse 1s infinite alternate;
    }
    .autosave-pill-bottom.error .autosave-dot {
      background: #ef4444;
      box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.25);
    }
    @keyframes dotPulse {
      0% { opacity: 0.5; transform: scale(0.9); }
      100% { opacity: 1; transform: scale(1.1); }
    }

    /* Floating Toast Notification */
    .autosave-toast {
      position: fixed;
      top: 24px;
      right: 28px;
      z-index: 9999;
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 18px;
      border-radius: 12px;
      background: #0f172a;
      color: #ffffff;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
      font-size: .84rem;
      font-weight: 600;
      opacity: 0;
      transform: translateY(-12px);
      transition: opacity .25s ease, transform .25s ease;
      pointer-events: none;
    }
    body.dark .autosave-toast {
      background: #1e293b;
      color: #f1f5f9;
      border: 1px solid rgba(255, 255, 255, 0.15);
    }
    .autosave-toast.show {
      opacity: 1;
      transform: translateY(0);
    }
    .toast-icon-wrap {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 20px;
      height: 20px;
      flex-shrink: 0;
      position: relative;
    }
    .toast-icon-wrap svg {
      width: 16px;
      height: 16px;
    }
    .toast-icon-wrap .toast-icon-check {
      display: block !important;
      color: #10b981;
    }
    .toast-icon-wrap .toast-icon-warn {
      display: none !important;
      color: #ef4444;
    }
    .autosave-toast.toast-error {
      background: #7f1d1d;
      color: #fef2f2;
    }
    .autosave-toast.toast-error .toast-icon-wrap .toast-icon-check {
      display: none !important;
    }
    .autosave-toast.toast-error .toast-icon-wrap .toast-icon-warn {
      display: block !important;
    }
    
  </style>
</head>
<body>

<!-- Mobile Topbar -->
<header class="mobile-topbar" id="mobileTopbar">
  <button class="sidebar-toggle" id="sidebarToggle" aria-label="Buka Menu Navigasi" onclick="toggleSidebar()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  </button>
  <div class="mobile-topbar-divider"></div>
  <div class="mobile-topbar-brand">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M12 21s-7-6.1-7-11a7 7 0 1 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>
    </svg>
    <span>AKSA NOVA • Peta & Jam Buka</span>
  </div>
</header>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Sidebar Admin -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="avatar-wrap" onclick="openProfilModal()" title="Edit Profil">
      <div class="avatar-circle">
        <?php if ($admin_foto && file_exists($admin_foto)): ?>
          <img src="<?= htmlspecialchars($admin_foto) ?>?v=<?= filemtime($admin_foto) ?>" alt="Admin" width="72" height="72" loading="eager" decoding="sync"/>
        <?php else: ?>
          <svg class="default-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
          </svg>
        <?php endif; ?>
      </div>
      <div class="avatar-overlay">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
        </svg>
      </div>
    </div>
    <div class="admin-name-label">Halo, <?= htmlspecialchars($admin_name) ?></div>
  </div>

  <nav class="sidebar-nav">
    <a class="sidebar-btn" href="dashboard.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
      Dashboard
    </a>
    <a class="sidebar-btn" href="halaman_admin.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Perbarui Buku
    </a>
    <a class="sidebar-btn" href="kelola_banner.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><path d="M21 15l-5-5L5 19"/></svg>
      Kelola Banner
    </a>
    <a class="sidebar-btn" href="daftar_anggota.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Daftar Anggota<?php if (!empty($pending_count) && $pending_count > 0): ?><span style="margin-left:auto;background:#e74c3c;color:#fff;font-size:.65rem;font-weight:800;padding:2px 7px;border-radius:20px;"><?= $pending_count ?></span><?php endif; ?>
    </a>
    <a class="sidebar-btn" href="pinjam_buku.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
      </svg>
      Pinjam Buku
    </a>
    <a class="sidebar-btn" href="pengajuan_buku.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
      Pengajuan Pinjam
      <?php if ($cnt_aju_badge > 0): ?>
        <span class="aksa-notif-badge"><?= $cnt_aju_badge ?></span>
      <?php endif; ?>
    </a>
    <a class="sidebar-btn" href="telah_dipinjam.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
      </svg>
      Telah Dipinjam
    </a>
    <a class="sidebar-btn" href="pengaturan_denda.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      Pengaturan Denda
    </a>
    <a class="sidebar-btn" href="pengaturan_musik.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>
      </svg>
      Pengaturan Musik
    </a>
    <a class="sidebar-btn active" href="pengaturan_lokasi.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 21s-7-6.1-7-11a7 7 0 1 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>
      </svg>
      Peta & Jam Buka
    </a>

    <button class="sidebar-btn btn-settings-nav" onclick="if(typeof bukaSettings==='function')bukaSettings()" title="Tema & Pengaturan Tampilan">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
      Tema & Tampilan
    </button>
    <a class="sidebar-btn sidebar-btn-logout" href="logout.php" onclick="return window.konfirmasiLogoutAksa ? window.konfirmasiLogoutAksa(event, this) : confirm('Apakah Anda yakin ingin keluar (logout)?');">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      Keluar
    </a>
  </nav>
</aside>

<main class="main">

  <!-- Floating Toast Notifikasi Auto-Save -->
  <div id="autoSaveToast" class="autosave-toast" aria-live="polite">
    <div class="toast-icon-wrap">
      <svg class="toast-icon-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      <svg class="toast-icon-warn" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <span class="toast-text">Perubahan tersimpan otomatis</span>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type ?>">
    <?php if ($msg_type === 'success'): ?>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    <?php else: ?>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <?php endif; ?>
    <span><?= htmlspecialchars($msg) ?></span>
  </div>
  <?php endif; ?>

  <div class="content-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px;">
    <div>
      <div class="content-title">Pengaturan Peta & Jam Perpustakaan</div>
      <div class="content-sub">Kelola titik lokasi Google Maps dan jam buka-tutup perpustakaan untuk ditampilkan di halaman utama secara praktis.</div>
    </div>
    <div id="autoSaveHeaderBadge" class="autosave-pill saved" title="Status penyimpanan otomatis">
      <svg class="autosave-icon-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      <svg class="autosave-icon-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
      <span class="autosave-text">Otomatis Tersimpan</span>
    </div>
  </div>

  <!-- Status Buka / Tutup Hari Ini (Realtime) -->
  <div class="realtime-card">
    <div class="realtime-left">
      <div class="realtime-icon-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
      </div>
      <div class="realtime-meta">
        <div class="current-time"><?= htmlspecialchars($hari_nama . ', ' . $tgl_str . ' • ' . $jam_str) ?></div>
        <div class="status-title" id="realtimeStatusTitle"><?= $is_open ? 'Perpustakaan Sedang Terbuka' : 'Perpustakaan Sedang Tutup' ?></div>
        <div class="status-note" id="realtimeStatusNote"><?= htmlspecialchars($status_desc) ?></div>
      </div>
    </div>
    <div>
      <span class="status-badge <?= $status_badge_class ?>" id="realtimeStatusBadge">
        <?= htmlspecialchars($status_badge_text) ?>
      </span>
    </div>
  </div>

  <form method="POST" id="formPengaturanLokasi">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="simpan_lokasi_jam">

    <!-- KARTU 1: PETA GOOGLE MAPS -->
    <div class="card">
      <div class="card-header">
        <div class="card-title-group">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 21s-7-6.1-7-11a7 7 0 1 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>
            </svg>
          </div>
          <div>
            <h2>Peta Google Maps (Halaman Utama)</h2>
            <div class="hint">Cukup masukkan nama sekolah, alamat, atau tautan Google Maps. Sistem akan otomatis menampilkan petanya.</div>
          </div>
        </div>
      </div>

      <div class="field">
        <label for="lokasi_map_input">Lokasi / Alamat di Google Maps</label>
        <div class="map-input-group">
          <input type="text" id="lokasi_map_input" name="lokasi_map_input" value="<?= htmlspecialchars($tampilan_input_peta) ?>" required placeholder="Contoh: SMK Negeri 1 Rongga, Bandung Barat">
          <a href="<?= htmlspecialchars($lokasi_maps_link) ?>" target="_blank" rel="noopener" class="btn-test-map" id="btnTestMapLink">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-6.1-7-11a7 7 0 1 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
            <span>Uji Buka di Google Maps &rsaquo;</span>
          </a>
        </div>
        <div class="field-help" style="margin-top: 8px; line-height: 1.55;">
          <strong>Cara agar jarum/pin Google Maps tepat di lokasi sekolah:</strong>
          <div style="display:flex; flex-direction:column; gap:4px; margin-top:5px; font-size:.8rem; color:var(--muted);">
            <div>&bull; <strong>Nama Sekolah &amp; Wilayah:</strong> Ketik <code>SMK Negeri 1 Rongga, Bandung Barat</code> (Google Maps otomatis mengarahkan pin merah ke lokasi sekolah).</div>
            <div>&bull; <strong>Titik Koordinat GPS (Paling Akurat):</strong> Ketik angka koordinat seperti <code>-6.98412, 107.24567</code> (jarum GPS menancap tepat di titik tersebut).</div>
            <div>&bull; <strong>Kode Sematan Iframe:</strong> Di Google Maps, klik <em>Bagikan</em> &rsaquo; <em>Sematkan peta</em> &rsaquo; <em>Salin HTML</em>, lalu tempelkan langsung ke kolom ini.</div>
          </div>
        </div>
      </div>

      <!-- Pratinjau Peta Langsung di Bawah Input -->
      <div class="map-preview-wrap">
        <iframe id="mapPreviewIframe" src="<?= htmlspecialchars($lokasi_maps_embed) ?>" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Pratinjau Peta Google Maps"></iframe>
      </div>
    </div>

    <!-- KARTU 2: JAM BUKA & TUTUP PERPUSTAKAAN (DESAIN BARU PREMIUM, BEBAS DARI KESAN DEFAULT) -->
    <div class="card">
      <div class="card-header">
        <div class="card-title-group">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
          </div>
          <div>
            <h2>Jam Buka & Tutup Perpustakaan</h2>
            <div class="hint">Pilih mode status operasional dan atur rentang jam layanan untuk setiap hari.</div>
          </div>
        </div>
      </div>

      <!-- STATUS SELECTOR BENTUK KARTU SEGMENTED (BUKAN DROPDOWN DEFAULT) -->
      <div class="field" style="margin-bottom: 8px;">
        <label>Status Layanan Saat Ini</label>
      </div>
      <div class="status-grid">
        <label class="status-opt-card <?= $operasional_status === 'otomatis' ? 'active' : '' ?>" id="cardOptOtomatis">
          <input type="radio" name="operasional_status" value="otomatis" <?= $operasional_status === 'otomatis' ? 'checked' : '' ?> onchange="updateStatusCard(this)">
          <div class="status-opt-header">
            <div class="status-opt-indicator">
              <span class="status-dot dot-auto"></span>
              Otomatis (Jadwal)
            </div>
            <span class="status-chip-tag">Disarankan</span>
          </div>
          <div class="status-opt-desc">Buka & tutup realtime mengikuti jam operasional harian WIB tanpa perlu diubah manual.</div>
        </label>

        <label class="status-opt-card <?= $operasional_status === 'buka_manual' ? 'active' : '' ?>" id="cardOptBuka">
          <input type="radio" name="operasional_status" value="buka_manual" <?= $operasional_status === 'buka_manual' ? 'checked' : '' ?> onchange="updateStatusCard(this)">
          <div class="status-opt-header">
            <div class="status-opt-indicator">
              <span class="status-dot dot-open"></span>
              Selalu Buka
            </div>
          </div>
          <div class="status-opt-desc">Paksa status BUKA sekarang (misal: acara literasi khusus atau kegiatan sore).</div>
        </label>

        <label class="status-opt-card <?= $operasional_status === 'tutup_libur' ? 'active' : '' ?>" id="cardOptTutup">
          <input type="radio" name="operasional_status" value="tutup_libur" <?= $operasional_status === 'tutup_libur' ? 'checked' : '' ?> onchange="updateStatusCard(this)">
          <div class="status-opt-header">
            <div class="status-opt-indicator">
              <span class="status-dot dot-closed"></span>
              Tutup / Libur
            </div>
          </div>
          <div class="status-opt-desc">Paksa status TUTUP (misal: libur semester sekolah atau renovasi ruangan).</div>
        </label>
      </div>

      <!-- DAFTAR JADWAL HARIAN DENGAN TIME CAPSULES ELEGAN -->
      <div class="field" style="margin-bottom: 8px;">
        <label>Rentang Jam Layanan Harian</label>
      </div>

      <div class="schedule-list">
        <!-- Senin - Kamis -->
        <div class="schedule-card">
          <div class="day-identity">
            <div class="day-badge-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="day-title-wrap">
              <h3>Senin – Kamis</h3>
              <div class="day-tagline">Hari Pembelajaran Reguler</div>
            </div>
          </div>

          <div class="time-capsule">
            <div class="time-pill">
              <span class="time-pill-label">Buka Layanan</span>
              <div class="time-input-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <input type="time" id="jam_buka_senin_kamis" name="jam_buka_senin_kamis" value="<?= htmlspecialchars($jam_buka_senin_kamis) ?>" required class="custom-time-input" onchange="autoSyncText()">
              </div>
            </div>

            <span class="time-arrow">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </span>

            <div class="time-pill">
              <span class="time-pill-label">Tutup Layanan</span>
              <div class="time-input-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <input type="time" id="jam_tutup_senin_kamis" name="jam_tutup_senin_kamis" value="<?= htmlspecialchars($jam_tutup_senin_kamis) ?>" required class="custom-time-input" onchange="autoSyncText()">
              </div>
            </div>

            <span class="time-zone-badge">WIB</span>
          </div>
        </div>

        <!-- Jumat -->
        <div class="schedule-card">
          <div class="day-identity">
            <div class="day-badge-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="day-title-wrap">
              <h3>Jumat</h3>
              <div class="day-tagline">Layanan Pagi & Literasi Keagamaan</div>
            </div>
          </div>

          <div class="time-capsule">
            <div class="time-pill">
              <span class="time-pill-label">Buka Layanan</span>
              <div class="time-input-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <input type="time" id="jam_buka_jumat" name="jam_buka_jumat" value="<?= htmlspecialchars($jam_buka_jumat) ?>" required class="custom-time-input" onchange="autoSyncText()">
              </div>
            </div>

            <span class="time-arrow">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </span>

            <div class="time-pill">
              <span class="time-pill-label">Tutup Layanan</span>
              <div class="time-input-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <input type="time" id="jam_tutup_jumat" name="jam_tutup_jumat" value="<?= htmlspecialchars($jam_tutup_jumat) ?>" required class="custom-time-input" onchange="autoSyncText()">
              </div>
            </div>

            <span class="time-zone-badge">WIB</span>
          </div>
        </div>

        <!-- Sabtu (Toggle Segmented Modern) -->
        <div class="schedule-card">
          <div class="day-identity">
            <div class="day-badge-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="day-title-wrap">
              <h3>Sabtu</h3>
              <div class="day-tagline" id="taglineSabtu"><?= $jam_status_sabtu === 'buka' ? 'Buka Layanan Terbatas' : 'Libur Akhir Pekan' ?></div>
            </div>
          </div>

          <div class="weekend-toggle-wrap">
            <div class="weekend-toggle-group">
              <button type="button" class="weekend-toggle-btn <?= $jam_status_sabtu === 'libur' ? 'active-libur' : '' ?>" id="btnSabtuLibur" onclick="setWeekendStatus('sabtu', 'libur')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:13px;height:13px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                Libur Rutin
              </button>
              <button type="button" class="weekend-toggle-btn <?= $jam_status_sabtu === 'buka' ? 'active-buka' : '' ?>" id="btnSabtuBuka" onclick="setWeekendStatus('sabtu', 'buka')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:13px;height:13px;"><polyline points="20 6 9 17 4 12"/></svg>
                Buka Layanan
              </button>
            </div>
            <input type="hidden" name="jam_status_sabtu" id="jam_status_sabtu" value="<?= htmlspecialchars($jam_status_sabtu) ?>">

            <!-- Time inputs jika Sabtu buka -->
            <div id="sabtuJamWrap" style="<?= $jam_status_sabtu === 'buka' ? 'display:inline-flex;' : 'display:none;' ?> align-items:center; gap:8px;">
              <div class="time-capsule">
                <div class="time-pill">
                  <span class="time-pill-label">Buka</span>
                  <div class="time-input-wrap">
                    <input type="time" id="jam_buka_sabtu" name="jam_buka_sabtu" value="<?= htmlspecialchars($jam_buka_sabtu) ?>" class="custom-time-input" onchange="autoSyncText()">
                  </div>
                </div>
                <span class="time-arrow">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </span>
                <div class="time-pill">
                  <span class="time-pill-label">Tutup</span>
                  <div class="time-input-wrap">
                    <input type="time" id="jam_tutup_sabtu" name="jam_tutup_sabtu" value="<?= htmlspecialchars($jam_tutup_sabtu) ?>" class="custom-time-input" onchange="autoSyncText()">
                  </div>
                </div>
                <span class="time-zone-badge">WIB</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Minggu (Toggle Segmented Modern) -->
        <div class="schedule-card">
          <div class="day-identity">
            <div class="day-badge-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="day-title-wrap">
              <h3>Minggu</h3>
              <div class="day-tagline" id="taglineMinggu"><?= $jam_status_minggu === 'buka' ? 'Buka Layanan Khusus' : 'Libur Akhir Pekan' ?></div>
            </div>
          </div>

          <div class="weekend-toggle-wrap">
            <div class="weekend-toggle-group">
              <button type="button" class="weekend-toggle-btn <?= $jam_status_minggu === 'libur' ? 'active-libur' : '' ?>" id="btnMingguLibur" onclick="setWeekendStatus('minggu', 'libur')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:13px;height:13px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                Libur Rutin
              </button>
              <button type="button" class="weekend-toggle-btn <?= $jam_status_minggu === 'buka' ? 'active-buka' : '' ?>" id="btnMingguBuka" onclick="setWeekendStatus('minggu', 'buka')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:13px;height:13px;"><polyline points="20 6 9 17 4 12"/></svg>
                Buka Layanan
              </button>
            </div>
            <input type="hidden" name="jam_status_minggu" id="jam_status_minggu" value="<?= htmlspecialchars($jam_status_minggu) ?>">

            <!-- Time inputs jika Minggu buka -->
            <div id="mingguJamWrap" style="<?= $jam_status_minggu === 'buka' ? 'display:inline-flex;' : 'display:none;' ?> align-items:center; gap:8px;">
              <div class="time-capsule">
                <div class="time-pill">
                  <span class="time-pill-label">Buka</span>
                  <div class="time-input-wrap">
                    <input type="time" id="jam_buka_minggu" name="jam_buka_minggu" value="<?= htmlspecialchars($jam_buka_minggu) ?>" class="custom-time-input" onchange="autoSyncText()">
                  </div>
                </div>
                <span class="time-arrow">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </span>
                <div class="time-pill">
                  <span class="time-pill-label">Tutup</span>
                  <div class="time-input-wrap">
                    <input type="time" id="jam_tutup_minggu" name="jam_tutup_minggu" value="<?= htmlspecialchars($jam_tutup_minggu) ?>" class="custom-time-input" onchange="autoSyncText()">
                  </div>
                </div>
                <span class="time-zone-badge">WIB</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RINGKASAN JADWAL HALAMAN UTAMA (OTOMATIS TERSINKRON DARI JADWAL HARIAN) -->
      <div class="schedule-auto-summary">
        <div class="auto-summary-top">
          <div class="auto-summary-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>Tampilan Jadwal di Halaman Utama (Tersinkron Otomatis)</span>
          </div>
          <span class="auto-summary-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:12px;height:12px;"><polyline points="20 6 9 17 4 12"/></svg>
            100% Otomatis dari Jam Harian
          </span>
        </div>

        <div class="auto-summary-capsule">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          <span class="auto-summary-text" id="prevSummaryLive"><?= htmlspecialchars($lokasi_jam) ?></span>
        </div>

        <div class="auto-summary-hint">
          Teks jadwal di atas otomatis dihasilkan dari input <strong>Rentang Jam Layanan Harian</strong> di atas untuk ditampilkan pada Slide 3 dan Slide 5 etalase utama (landing page). Anda tidak perlu mengetik atau mengatur ulang secara manual.
        </div>
        <input type="hidden" id="lokasi_jam" name="lokasi_jam" value="<?= htmlspecialchars($lokasi_jam) ?>">
      </div>

      <div class="field">
        <label for="operasional_catatan">Catatan Tambahan Jam Layanan <span class="label-opt">(Opsional)</span></label>
        <textarea id="operasional_catatan" name="operasional_catatan" rows="2" placeholder="Contoh: Istirahat Shalat Jumat pukul 11.30 – 12.45 WIB. Layanan sirkulasi tutup sementara saat waktu istirahat."><?= htmlspecialchars($operasional_catatan) ?></textarea>
        <div class="field-help">Informasi penting tambahan seperti waktu jeda istirahat shalat atau aturan saat masa ujian sekolah.</div>
      </div>
    </div>

    <!-- KARTU 3: INFORMASI SEKOLAH & KONTAK -->
    <div class="card">
      <div class="card-header">
        <div class="card-title-group">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
          </div>
          <div>
            <h2>Informasi Kontak & Alamat Sekolah</h2>
            <div class="hint">Data ini tampil pada kartu 'Kunjungi Kami' di Slide 5 halaman utama.</div>
          </div>
        </div>
      </div>

      <div class="form-grid">
        <div class="field">
          <label for="lokasi_sekolah">Nama Sekolah / Instansi</label>
          <input type="text" id="lokasi_sekolah" name="lokasi_sekolah" value="<?= htmlspecialchars($lokasi_sekolah) ?>" required placeholder="Contoh: SMK Negeri 1 Rongga">
          <div class="field-help">Nama resmi sekolah atau perpustakaan.</div>
        </div>

        <div class="field">
          <label for="lokasi_telepon">Nomor Telepon / WhatsApp</label>
          <input type="text" id="lokasi_telepon" name="lokasi_telepon" value="<?= htmlspecialchars($lokasi_telepon) ?>" placeholder="Contoh: 083829165209">
          <div class="field-help">Kontak perpustakaan yang dapat dihubungi peminjam.</div>
        </div>

        <div class="field col-span-2">
          <label for="lokasi_alamat">Alamat Lengkap</label>
          <textarea id="lokasi_alamat" name="lokasi_alamat" rows="2" required placeholder="Jl. Situ Gede / Jl. Raya Rongga..."><?= htmlspecialchars($lokasi_alamat) ?></textarea>
          <div class="field-help">Alamat fisik lengkap perpustakaan sekolah.</div>
        </div>

        <div class="field col-span-2">
          <label for="lokasi_email">Email Resmi Perpustakaan <span class="label-opt">(Opsional)</span></label>
          <input type="email" id="lokasi_email" name="lokasi_email" value="<?= htmlspecialchars($lokasi_email) ?>" placeholder="Contoh: perpustakaan@smkn1rongga.sch.id">
          <div class="field-help">Surel resmi untuk keperluan korespondensi.</div>
        </div>
      </div>
    </div>

    <!-- KARTU 4: PRATINJAU KARTU HALAMAN UTAMA -->
    <div class="card">
      <div class="card-header">
        <div class="card-title-group">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </div>
          <div>
            <h2>Pratinjau Kartu Halaman Utama</h2>
            <div class="hint">Simulasi tampilan kartu informasi yang dilihat oleh siswa dan pengunjung di Slide 5.</div>
          </div>
        </div>
      </div>

      <div class="preview-info-card">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px;">
          <div>
            <span style="font-size:.72rem; text-transform:uppercase; color:var(--accent); letter-spacing:.08em; font-weight:800;">TAMPILAN DI HALAMAN UTAMA</span>
            <h3 style="font-family:'Cormorant Garamond',serif; font-size:1.35rem; font-weight:700; color:var(--text); margin-top:2px;">Lokasi Perpustakaan</h3>
          </div>
          <span class="status-badge <?= $status_badge_class ?>" style="font-size:.72rem; padding:5px 12px;" id="prevStatusBadge">
            <?= htmlspecialchars($status_badge_text) ?>
          </span>
        </div>

        <div class="preview-info-item">
          <div class="preview-info-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-6.1-7-11a7 7 0 1 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
          </div>
          <div class="preview-info-text">
            <h4>Alamat</h4>
            <p><strong><span id="prevSekolah"><?= htmlspecialchars($lokasi_sekolah) ?></span></strong><br><span id="prevAlamat"><?= htmlspecialchars($lokasi_alamat) ?></span></p>
          </div>
        </div>

        <div class="preview-info-item">
          <div class="preview-info-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
          </div>
          <div class="preview-info-text">
            <h4>Jam Operasional</h4>
            <p><span id="prevJam"><?= htmlspecialchars($lokasi_jam) ?></span></p>
            <?php if ($operasional_catatan): ?>
              <p style="font-size:.78rem; color:var(--muted); margin-top:3px; font-style:italic;" id="prevCatatan"><?= htmlspecialchars($operasional_catatan) ?></p>
            <?php endif; ?>
          </div>
        </div>

        <div class="preview-info-item">
          <div class="preview-info-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7a2 2 0 0 1 1.72 2.03z"/></svg>
          </div>
          <div class="preview-info-text">
            <h4>Kontak</h4>
            <p><span id="prevTelepon"><?= htmlspecialchars($lokasi_telepon) ?></span></p>
            <?php if ($lokasi_email): ?>
              <p style="font-size:.82rem; color:var(--muted); margin-top:2px;" id="prevEmail"><?= htmlspecialchars($lokasi_email) ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>



  </form>

</main>

<script>
  // ─────────────────────────────────────────────
  //  MESIN PENYIMPANAN OTOMATIS (REAL-TIME AUTO-SAVE)
  // ─────────────────────────────────────────────
  let autoSaveTimer = null;
  let isSaving = false;
  let hasPendingSave = false;

  function setAutoSaveState(state, text) {
    const topBadge = document.getElementById('autoSaveHeaderBadge');
    const topText = topBadge ? topBadge.querySelector('.autosave-text') : null;

    if (state === 'saving') {
      if (topBadge) topBadge.className = 'autosave-pill saving';
      if (topText) topText.textContent = text || 'Menyimpan...';
    } else if (state === 'saved') {
      if (topBadge) topBadge.className = 'autosave-pill saved';
      if (topText) topText.textContent = text || 'Otomatis Tersimpan';
    } else if (state === 'error') {
      if (topBadge) topBadge.className = 'autosave-pill error';
      if (topText) topText.textContent = text || 'Gagal Menyimpan';
    }
  }

  let toastTimer = null;
  function showAutoSaveToast(msg, isError = false) {
    const toast = document.getElementById('autoSaveToast');
    if (!toast) return;
    toast.querySelector('.toast-text').textContent = msg;
    toast.classList.toggle('toast-error', isError);
    toast.classList.add('show');
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      toast.classList.remove('show');
    }, 2800);
  }

  function triggerAutoSave(immediate = false) {
    if (autoSaveTimer) {
      clearTimeout(autoSaveTimer);
      autoSaveTimer = null;
    }
    if (immediate) {
      executeSave();
    } else {
      setAutoSaveState('saving', 'Perubahan terdeteksi...');
      autoSaveTimer = setTimeout(executeSave, 700);
    }
  }

  function executeSave() {
    if (isSaving) {
      hasPendingSave = true;
      return;
    }
    isSaving = true;
    setAutoSaveState('saving', 'Menyimpan perubahan...');

    const form = document.getElementById('formPengaturanLokasi');
    if (!form) {
      isSaving = false;
      return;
    }

    const formData = new FormData(form);
    formData.set('is_ajax', '1');

    fetch('pengaturan_lokasi.php', {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(res => res.json())
    .then(data => {
      isSaving = false;
      if (data.success) {
        const timeStr = data.timestamp || new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        setAutoSaveState('saved', 'Otomatis Tersimpan (' + timeStr + ' WIB)');
        showAutoSaveToast('Perubahan tersimpan otomatis (' + timeStr + ' WIB)');

        if (data.lokasi_maps_embed) {
          const iframe = document.getElementById('mapPreviewIframe');
          if (iframe && iframe.src !== data.lokasi_maps_embed) {
            iframe.src = data.lokasi_maps_embed;
          }
        }
        if (data.lokasi_maps_link) {
          const btn = document.getElementById('btnTestMapLink');
          if (btn) btn.href = data.lokasi_maps_link;
        }

        // Terapkan pembaruan status realtime dari server seketika tanpa reload
        if (data.status_info) {
          const elTitle = document.getElementById('realtimeStatusTitle');
          const elNote  = document.getElementById('realtimeStatusNote');
          const elBadge = document.getElementById('realtimeStatusBadge');
          const elPrev  = document.getElementById('prevStatusBadge');
          if (elTitle) elTitle.textContent = data.status_info.title;
          if (elNote)  elNote.textContent  = data.status_info.note;
          if (elBadge) {
            elBadge.textContent = data.status_info.badge_text;
            elBadge.className = 'status-badge ' + data.status_info.badge_class;
          }
          if (elPrev) {
            elPrev.textContent = data.status_info.badge_text;
            elPrev.className = 'status-badge ' + data.status_info.badge_class;
          }
        }
      } else {
        setAutoSaveState('error', data.message || 'Gagal menyimpan');
        showAutoSaveToast(data.message || 'Gagal menyimpan perubahan', true);
      }

      if (hasPendingSave) {
        hasPendingSave = false;
        executeSave();
      }
    })
    .catch(err => {
      isSaving = false;
      setAutoSaveState('error', 'Koneksi terputus saat menyimpan');
      showAutoSaveToast('Koneksi terputus, perubahan belum tersimpan', true);
      if (hasPendingSave) {
        hasPendingSave = false;
      }
    });
  }

  // ── Update Card Status Mode (Segmented Control) ──
  function updateStatusCard(radio) {
    document.querySelectorAll('.status-opt-card').forEach(c => c.classList.remove('active'));
    if (radio.checked) {
      radio.closest('.status-opt-card').classList.add('active');
    }

    // Pembaruan seketika di layar (Optimistic UI Update tanpa perlu refresh)
    const elTitle = document.getElementById('realtimeStatusTitle');
    const elNote  = document.getElementById('realtimeStatusNote');
    const elBadge = document.getElementById('realtimeStatusBadge');
    const elPrev  = document.getElementById('prevStatusBadge');

    if (radio.value === 'buka_manual') {
      if (elTitle) elTitle.textContent = 'Perpustakaan Sedang Terbuka';
      if (elNote)  elNote.textContent  = 'Status layanan dibuka manual oleh pustakawan (selalu buka).';
      if (elBadge) {
        elBadge.textContent = 'Buka Sekarang';
        elBadge.className = 'status-badge status-open';
      }
      if (elPrev) {
        elPrev.textContent = 'Buka Sekarang';
        elPrev.className = 'status-badge status-open';
      }
    } else if (radio.value === 'tutup_libur') {
      if (elTitle) elTitle.textContent = 'Perpustakaan Sedang Tutup';
      if (elNote)  elNote.textContent  = 'Status layanan ditutup manual oleh pustakawan (sedang libur/tutup).';
      if (elBadge) {
        elBadge.textContent = 'Tutup / Libur';
        elBadge.className = 'status-badge status-closed';
      }
      if (elPrev) {
        elPrev.textContent = 'Tutup / Libur';
        elPrev.className = 'status-badge status-closed';
      }
    }

    triggerAutoSave(true);
  }

  // ── Toggle Weekend Segmented Button (Sabtu & Minggu) ──
  function setWeekendStatus(day, status) {
    const input = document.getElementById('jam_status_' + day);
    if (!input) return;
    input.value = status;

    const btnLibur = document.getElementById(day === 'sabtu' ? 'btnSabtuLibur' : 'btnMingguLibur');
    const btnBuka  = document.getElementById(day === 'sabtu' ? 'btnSabtuBuka' : 'btnMingguBuka');
    const wrap     = document.getElementById(day + 'JamWrap');
    const tag      = document.getElementById(day === 'sabtu' ? 'taglineSabtu' : 'taglineMinggu');

    if (status === 'libur') {
      btnLibur.classList.add('active-libur');
      btnBuka.classList.remove('active-buka');
      if (wrap) wrap.style.display = 'none';
      if (tag) tag.textContent = 'Libur Akhir Pekan';
    } else {
      btnBuka.classList.add('active-buka');
      btnLibur.classList.remove('active-libur');
      if (wrap) wrap.style.display = 'inline-flex';
      if (tag) tag.textContent = day === 'sabtu' ? 'Buka Layanan Terbatas' : 'Buka Layanan Khusus';
    }
    generateRingkasanJam();
    triggerAutoSave(true);
  }

  // ── Format Jam "07:00" -> "07.00" ──
  function formatJam(val) {
    if (!val) return '07.00';
    return val.replace(':', '.');
  }

  // ── Buat Ringkasan Jam Otomatis ──
  function generateRingkasanJam() {
    const bkSK = formatJam(document.getElementById('jam_buka_senin_kamis').value);
    const tpSK = formatJam(document.getElementById('jam_tutup_senin_kamis').value);
    const bkJ  = formatJam(document.getElementById('jam_buka_jumat').value);
    const tpJ  = formatJam(document.getElementById('jam_tutup_jumat').value);
    const stSab = document.getElementById('jam_status_sabtu').value;

    let res = '';
    if (bkSK === bkJ && tpSK === tpJ) {
      res = `Senin – Jumat, ${bkSK} – ${tpSK} WIB`;
    } else {
      res = `Senin – Kamis: ${bkSK} – ${tpSK} WIB | Jumat: ${bkJ} – ${tpJ} WIB`;
    }

    if (stSab === 'buka') {
      const bkS = formatJam(document.getElementById('jam_buka_sabtu').value);
      const tpS = formatJam(document.getElementById('jam_tutup_sabtu').value);
      res += ` | Sabtu: ${bkS} – ${tpS} WIB`;
    }

    const stMingInput = document.getElementById('jam_status_minggu');
    const stMing = stMingInput ? stMingInput.value : 'libur';
    if (stMing === 'buka') {
      const elBkM = document.getElementById('jam_buka_minggu');
      const elTpM = document.getElementById('jam_tutup_minggu');
      const bkM = formatJam(elBkM ? elBkM.value : '08:00');
      const tpM = formatJam(elTpM ? elTpM.value : '12:00');
      res += ` | Minggu: ${bkM} – ${tpM} WIB`;
    }

    const inputJam = document.getElementById('lokasi_jam');
    inputJam.value = res;
    document.getElementById('prevJam').textContent = res;
    document.getElementById('prevSummaryLive').textContent = res;
  }

  function autoSyncText() {
    generateRingkasanJam();
    triggerAutoSave(true);
  }

  // ── Realtime Update Teks Pratinjau & Auto-Save ──
  document.getElementById('lokasi_sekolah').addEventListener('input', function() {
    document.getElementById('prevSekolah').textContent = this.value || 'Nama Sekolah';
    triggerAutoSave(false);
  });
  document.getElementById('lokasi_sekolah').addEventListener('blur', function() {
    triggerAutoSave(true);
  });

  document.getElementById('lokasi_alamat').addEventListener('input', function() {
    document.getElementById('prevAlamat').textContent = this.value || 'Alamat Sekolah';
    triggerAutoSave(false);
  });
  document.getElementById('lokasi_alamat').addEventListener('blur', function() {
    triggerAutoSave(true);
  });



  document.getElementById('lokasi_telepon').addEventListener('input', function() {
    document.getElementById('prevTelepon').textContent = this.value || '-';
    triggerAutoSave(false);
  });
  document.getElementById('lokasi_telepon').addEventListener('blur', function() {
    triggerAutoSave(true);
  });

  const emailInput = document.getElementById('lokasi_email');
  if (emailInput) {
    emailInput.addEventListener('input', function() {
      const el = document.getElementById('prevEmail');
      if (el) el.textContent = this.value;
      triggerAutoSave(false);
    });
    emailInput.addEventListener('blur', function() {
      triggerAutoSave(true);
    });
  }

  const catatanInput = document.getElementById('operasional_catatan');
  if (catatanInput) {
    catatanInput.addEventListener('input', function() {
      const el = document.getElementById('prevCatatan');
      if (el) el.textContent = this.value;
      triggerAutoSave(false);
    });
    catatanInput.addEventListener('blur', function() {
      triggerAutoSave(true);
    });
  }

  // Update dynamic test map link on typing location
  const mapInput = document.getElementById('lokasi_map_input');
  if (mapInput) {
    mapInput.addEventListener('input', function() {
      const val = this.value.trim();
      const btn = document.getElementById('btnTestMapLink');
      if (btn && val) {
        btn.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(val);
      }
      triggerAutoSave(false);
    });
    mapInput.addEventListener('blur', function() {
      triggerAutoSave(true);
    });
  }

  // Intercept Form Submit: Simpan via AJAX tanpa reload halaman
  const formLokasi = document.getElementById('formPengaturanLokasi');
  if (formLokasi) {
    formLokasi.addEventListener('submit', function(e) {
      e.preventDefault();
      triggerAutoSave(true);
    });
  }

  // Listen to all time input changes
  document.querySelectorAll('input[type="time"]').forEach(el => {
    el.addEventListener('change', () => {
      autoSyncText();
    });
  });
</script>

<?php
require_once 'modal_profil_admin.php';
require_once 'pengaturan_panel.php';
?>
</body>
</html>
