<?php
// telah_dipinjam.php — Halaman Buku yang Sedang / Sudah Dipinjam
// PHP 8.3 — dengan fitur denda keterlambatan

declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: sign_in.php');
    exit;
}

require_once 'db.php';

assert($conn instanceof mysqli);
mysqli_set_charset($conn, 'utf8mb4');

// Samakan zona waktu PHP & MySQL supaya waktu pinjam/sisa waktu tidak meleset dari WIB
date_default_timezone_set('Asia/Jakarta');
mysqli_query($conn, "SET time_zone = '+07:00'");

$page_title = 'Telah Dipinjam – AKSA NOVA';
$msg        = '';
$msg_type   = '';

// ─── Profil admin ───
$profil_res = mysqli_query($conn, 'SELECT * FROM admin_profile LIMIT 1');
$profil     = $profil_res ? mysqli_fetch_assoc($profil_res) : null;
$admin_name = $profil['display_name'] ?? ($_SESSION['user_name'] ?? 'Admin');
$admin_foto = $profil['foto'] ?? '';

// ─── Helper: ambil pengaturan ───
function getSetting(mysqli $db, string $kunci, string $default = ''): string {
    $k   = mysqli_real_escape_string($db, $kunci);
    $res = mysqli_query($db, "SELECT nilai FROM pengaturan WHERE kunci='$k' LIMIT 1");
    $row = $res ? mysqli_fetch_assoc($res) : null;
    return $row ? $row['nilai'] : $default;
}

// Ambil config denda
$denda_aktif    = getSetting($conn, 'denda_aktif',    '1') === '1';
$denda_per_hari = (int)getSetting($conn, 'denda_per_hari', '1000');
$grace_period   = (int)getSetting($conn, 'denda_grace_period', '0');

// ─── Helper: hitung denda ───
function hitungDenda(string $batas_kembali, string $waktu_kembali, bool $aktif, int $per_hari, int $grace): array {
    if (!$aktif) return ['hari' => 0, 'total' => 0];
    $batas  = new DateTimeImmutable($batas_kembali);
    $kembali = new DateTimeImmutable($waktu_kembali);
    if ($kembali <= $batas) return ['hari' => 0, 'total' => 0];
    $diff  = $batas->diff($kembali);
    $hari  = $diff->days;
    $hari_denda = max(0, $hari - $grace);
    return ['hari' => $hari, 'total' => $hari_denda * $per_hari];
}

// ─────────────────────────────────────────────
//  AKSI: Kembalikan Buku
// ─────────────────────────────────────────────
$action = $_POST['action'] ?? '';

if ($action === 'kembalikan') {
    $pem_id = (int)($_POST['pem_id'] ?? 0);

    if ($pem_id > 0) {
        $cek_res = mysqli_query($conn,
            "SELECT p.buku_id, p.status, p.batas_kembali FROM peminjaman p
             INNER JOIN buku b ON b.id = p.buku_id
             WHERE p.id=$pem_id LIMIT 1"
        );
        $cek = $cek_res ? mysqli_fetch_assoc($cek_res) : null;

        if ($cek && $cek['status'] === 'dipinjam') {
            $buku_id = (int)$cek['buku_id'];
            $now     = date('Y-m-d H:i:s');

            // Hitung denda
            $denda_info   = hitungDenda($cek['batas_kembali'], $now, $denda_aktif, $denda_per_hari, $grace_period);
            $terlambat_hari = $denda_info['hari'];
            $total_denda    = $denda_info['total'];
            $status_denda   = $total_denda > 0 ? 'belum_bayar' : 'tidak_ada';

            mysqli_query($conn,
                "UPDATE peminjaman
                 SET status='dikembalikan',
                     waktu_kembali='$now',
                     terlambat_hari=$terlambat_hari,
                     denda=$total_denda,
                     status_denda='$status_denda'
                 WHERE id=$pem_id"
            );
            mysqli_query($conn, "UPDATE buku SET stok = stok + 1 WHERE id=$buku_id");

            if ($total_denda > 0) {
                $msg = "Buku berhasil dikembalikan! Terlambat $terlambat_hari hari → Denda: Rp " . number_format($total_denda, 0, ',', '.') . ". Harap segera diselesaikan.";
                $msg_type = 'warning';
            } else {
                $msg = 'Buku berhasil dikembalikan! Stok otomatis bertambah.';
                $msg_type = 'success';
            }
        } else {
            $msg = 'Buku ini sudah dikembalikan atau bukunya telah dihapus.';
            $msg_type = 'error';
        }
    }
}

// Hapus riwayat
if ($action === 'hapus_riwayat') {
    $pem_id = (int)($_POST['pem_id'] ?? 0);
    if ($pem_id > 0) {
        mysqli_query($conn, "DELETE FROM peminjaman WHERE id=$pem_id AND status='dikembalikan'");
        $msg = 'Riwayat peminjaman berhasil dihapus.';
        $msg_type = 'success';
    }
}

// Tandai pengingat WA sudah dikirim hari ini (dicatat di reminder_log)
if ($action === 'tandai_wa') {
    $pem_id = (int)($_POST['pem_id'] ?? 0);
    if ($pem_id > 0) {
        $today = date('Y-m-d');
        mysqli_query($conn,
            "INSERT INTO reminder_log (peminjaman_id, tanggal, wa_terkirim, wa_terkirim_at)
             VALUES ($pem_id, '$today', 1, NOW())
             ON DUPLICATE KEY UPDATE wa_terkirim = 1, wa_terkirim_at = NOW()"
        );
        $msg = 'Ditandai sudah mengirim pengingat WA hari ini.';
        $msg_type = 'success';
    }
}

// Batalkan tanda sudah kirim WA
if ($action === 'batal_wa') {
    $pem_id = (int)($_POST['pem_id'] ?? 0);
    if ($pem_id > 0) {
        $today = date('Y-m-d');
        mysqli_query($conn,
            "UPDATE reminder_log SET wa_terkirim = 0, wa_terkirim_at = NULL
             WHERE peminjaman_id = $pem_id AND tanggal = '$today'"
        );
    }
}

// ─────────────────────────────────────────────
//  Filter tab
// ─────────────────────────────────────────────
$tab = match($_GET['tab'] ?? 'dipinjam') {
    'dikembalikan' => 'dikembalikan',
    'riwayat'      => 'riwayat',
    default        => 'dipinjam',
};
$search  = trim($_GET['q'] ?? '');
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

$where_status = match($tab) {
    'dipinjam'     => "p.status = 'dipinjam'",
    'dikembalikan' => "p.status = 'dikembalikan'",
    default        => '1=1',
};

$where_search = '';
if ($search !== '') {
    $s = mysqli_real_escape_string($conn, $search);
    $where_search = " AND (b.judul LIKE '%$s%' OR p.nama_peminjam LIKE '%$s%')";
}

$sql = "SELECT p.*, b.judul, b.penulis, b.gambar, b.genre,
               u.no_hp AS anggota_no_hp, u.email AS anggota_email,
               rl.wa_terkirim AS wa_terkirim_hari_ini
        FROM peminjaman p
        INNER JOIN buku b ON b.id = p.buku_id
        LEFT JOIN users u ON u.id = p.user_id
        LEFT JOIN reminder_log rl ON rl.peminjaman_id = p.id AND rl.tanggal = CURDATE()
        WHERE $where_status $where_search
        ORDER BY p.waktu_pinjam DESC";

$pem_list = [];
$res = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($res)) {
    $pem_list[] = $row;
}
$total_tab = count($pem_list);

$cnt = [];
foreach (['dipinjam', 'dikembalikan'] as $st) {
    $r = mysqli_query($conn,
        "SELECT COUNT(*) AS c FROM peminjaman p
         INNER JOIN buku b ON b.id = p.buku_id
         WHERE p.status='$st'"
    );
    $cnt[$st] = (int)(mysqli_fetch_assoc($r)['c'] ?? 0);
}
$cnt['riwayat'] = $cnt['dipinjam'] + $cnt['dikembalikan'];

// Hitung denda aktif (belum dibayar)
$denda_pending_res = mysqli_query($conn,
    "SELECT COUNT(*) AS c, COALESCE(SUM(denda),0) AS t FROM peminjaman WHERE status_denda='belum_bayar'"
);
$denda_pending = mysqli_fetch_assoc($denda_pending_res);
$cnt_denda_pending = (int)$denda_pending['c'];
$total_denda_pending = (int)$denda_pending['t'];

$colors = ['col-a','col-b','col-c','col-d','col-e','col-f','col-g','col-h'];

function durasiHuman(string $dari, string $ke): string {
    $dt1 = new DateTimeImmutable($dari);
    $dt2 = new DateTimeImmutable($ke);
    $diff = $dt1->diff($dt2);
    $parts = [];
    if ($diff->days >= 1) $parts[] = $diff->days . ' hari';
    if ($diff->h   >= 1) $parts[] = $diff->h . ' jam';
    if ($diff->i   >= 1) $parts[] = $diff->i . ' menit';
    return implode(' ', $parts) ?: 'Kurang dari 1 menit';
}

function sisaWaktu(string $batas): array {
    $now   = new DateTimeImmutable();
    $batas = new DateTimeImmutable($batas);
    if ($now > $batas) {
        $diff = $now->diff($batas);
        return ['terlambat' => true, 'label' => $diff->days . ' hari terlambat', 'hari' => $diff->days];
    }
    $diff = $now->diff($batas);
    $parts = [];
    if ($diff->days >= 1) $parts[] = $diff->days . ' hari';
    if ($diff->h >= 1)    $parts[] = $diff->h    . ' jam';
    return ['terlambat' => false, 'label' => implode(' ', $parts) ?: 'Kurang dari 1 jam', 'hari' => 0];
}

// Preview denda untuk peminjaman yang masih dipinjam
function previewDenda(string $batas_kembali, bool $aktif, int $per_hari, int $grace): int {
    if (!$aktif) return 0;
    $now   = new DateTimeImmutable();
    $batas = new DateTimeImmutable($batas_kembali);
    if ($now <= $batas) return 0;
    $diff = $batas->diff($now);
    $hari_terlambat = $diff->days;
    $hari_denda = max(0, $hari_terlambat - $grace);
    return $hari_denda * $per_hari;
}

// ─── Helper: rapikan no HP ke format internasional untuk link wa.me ───
function formatNoWa(?string $no): string {
    $no = preg_replace('/\D/', '', $no ?? ''); // buang semua selain angka
    if ($no === '') return '';
    if (str_starts_with($no, '0')) {
        $no = '62' . substr($no, 1);
    } elseif (!str_starts_with($no, '62')) {
        $no = '62' . $no;
    }
    return $no;
}

// ─── Helper: susun pesan pengingat WA ───
function pesanPengingatWa(string $nama, string $judul, string $batasKembali, int $hariTerlambat, int $denda): string {
    $tgl = date('d M Y', strtotime($batasKembali));
    $msg  = "Halo $nama, ini pengingat dari Perpustakaan AKSA NOVA.\n\n";
    $msg .= "Buku \"$judul\" yang kamu pinjam sudah melewati batas pengembalian ($tgl) dan sudah terlambat $hariTerlambat hari.\n";
    if ($denda > 0) {
        $msg .= 'Estimasi denda saat ini: Rp ' . number_format($denda, 0, ',', '.') . ".\n";
    }
    $msg .= 'Mohon segera dikembalikan ke perpustakaan ya. Terima kasih 🙏';
    return $msg;
}

// Buffer seluruh output halaman. Untuk request AJAX (live search), buffer ini
// dibuang sepenuhnya sebelum kita kirim hanya fragmen hasil pencarian.
ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Cormorant+Garamond:wght@700&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --sidebar-bg:  #4a4a5a;
      --sidebar-dark:#2e2e3a;
      --btn-primary: #3a3a4a;
      --btn-pinjam:  #2563eb;
      --btn-kembali: #059669;
      --text:        #1a1a2e;
      --muted:       #7a7a9a;
      --bg:          #f0f0f0;
      --card:        #ffffff;
      --sidebar-w:   204px;
      --trans:       .2s cubic-bezier(.22,1,.36,1);
      --shadow:      0 2px 12px rgba(0,0,0,.07);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Nunito', sans-serif; background: var(--bg); display: flex; min-height: 100vh; animation: bodyIn .4s ease both; }
    @keyframes bodyIn { from { opacity:0; } to { opacity:1; } }

    .sidebar { width: var(--sidebar-w); background: linear-gradient(180deg, #5a5a6e 0%, #2e2e3a 100%); display: flex; flex-direction: column; align-items: center; padding: 36px 20px 28px; position: fixed; top: 0; left: 0; bottom: 0; z-index: 100; transition: transform var(--trans); }
    .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:90; }
    .sidebar-overlay.open { display:block; }
    .sidebar-toggle { display:none; position:fixed; top:14px; left:14px; z-index:200; width:40px; height:40px; border-radius:10px; border:none; background:#fff; box-shadow:0 2px 10px rgba(0,0,0,.15); cursor:pointer; align-items:center; justify-content:center; }
    .sidebar-toggle svg { width:20px; height:20px; }
    .avatar-wrap { position:relative; margin-bottom:14px; cursor:pointer; }
    .avatar-circle { width:96px; height:96px; border-radius:50%; background:#c0c0c8; overflow:hidden; border:3px solid rgba(255,255,255,.25); display:flex; align-items:center; justify-content:center; transition:border-color var(--trans); }
    .avatar-wrap:hover .avatar-circle { border-color:rgba(255,255,255,.55); }
    .avatar-circle img { width:100%; height:100%; object-fit:cover; display:block; }
    .avatar-circle .default-icon { width:52px; height:52px; color:#888; }
    .avatar-overlay { position:absolute; inset:0; border-radius:50%; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity .2s; }
    .avatar-wrap:hover .avatar-overlay { opacity:1; }
    .avatar-overlay svg { width:24px; height:24px; color:#fff; }
    .modal-close { border:none; background:#f0f0f5; width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text); }
    .modal-close svg { width:16px; height:16px; }
    .img-preview-wrap { position:relative; border:2px dashed #d8d8e4; overflow:hidden; cursor:pointer; }
    .img-preview-wrap img { width:100%; height:100%; object-fit:cover; }
    .upload-placeholder { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; color:var(--muted); }
    .upload-placeholder svg { width:26px; height:26px; }
    .form-group { margin-bottom:14px; }
    .form-label { display:block; font-size:.78rem; font-weight:700; color:var(--text); margin-bottom:6px; }
    .form-input { width:100%; padding:10px 12px; border-radius:8px; border:1px solid #e0e0ea; font-family:'Nunito',sans-serif; font-size:.85rem; }
    .form-input:focus { outline:none; border-color:var(--btn-kembali); }
    .btn-save { flex:1; padding:11px; border-radius:8px; border:none; background:var(--btn-kembali); color:#fff; font-family:'Nunito',sans-serif; font-size:.85rem; font-weight:700; cursor:pointer; transition:background var(--trans); }
    .btn-save:hover { background:#047857; }
    .admin-name-label { color:#fff; font-size:.95rem; font-weight:700; margin-bottom:8px; text-align:center; }
    .total-badge { background:rgba(255,255,255,.18); color:#fff; font-size:.72rem; font-weight:700; padding:4px 12px; border-radius:50px; margin-bottom:8px; text-align:center; }
    .total-badge.danger { background:rgba(239,68,68,.35); }
    .sidebar-btn { width:100%; display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:8px; border:none; background:rgba(255,255,255,.12); color:#fff; font-family:'Nunito',sans-serif; font-size:.82rem; font-weight:700; cursor:pointer; margin-bottom:8px; transition:background var(--trans); text-align:left; text-decoration:none; }
    .sidebar-btn:hover { background:rgba(255,255,255,.22); }
    .sidebar-btn.active { background:rgba(255,255,255,.3); }
    .sidebar-btn svg { width:16px; height:16px; flex-shrink:0; }

    .main { margin-left:var(--sidebar-w); flex:1; padding:26px 24px; }
    .alert { padding:12px 18px; border-radius:8px; font-size:.82rem; font-weight:700; margin-bottom:16px; animation:fadeUp .4s both; }
    .alert-success { background:#e8f5e9; color:#1a8a4a; border:1px solid #c8e6c9; }
    .alert-warning { background:#fff9e6; color:#92400e; border:1px solid #fcd34d; }
    .alert-error   { background:#fce4ec; color:#c0392b; border:1px solid #f8bbd0; }

    /* Denda banner */
    .denda-banner {
      background:linear-gradient(135deg,#fef3c7,#fff9e6);
      border:1.5px solid #f59e0b;
      border-radius:10px;
      padding:12px 16px;
      margin-bottom:16px;
      display:flex; align-items:center; gap:12px;
      animation:fadeUp .4s both; font-size:.8rem; font-weight:700; color:#92400e;
    }
    .denda-banner a { color:#b45309; text-decoration:underline; margin-left:auto; white-space:nowrap; font-size:.75rem; }

    .topbar { display:flex; align-items:center; background:#fff; border-radius:50px; padding:0 18px; height:46px; gap:10px; margin-bottom:18px; box-shadow:var(--shadow); animation:fadeUp .5s .05s both; }
    .topbar svg { width:18px; height:18px; color:#aaa; flex-shrink:0; }
    .topbar input { flex:1; border:none; outline:none; font-family:'Nunito',sans-serif; font-size:.85rem; color:var(--text); background:transparent; }
    .topbar input::placeholder { color:#bbb; }
    #searchResultArea { transition: opacity .15s ease; }
    #searchResultArea.loading-search { opacity: .55; }
    .btn-search { background:var(--btn-primary); color:#fff; border:none; border-radius:20px; padding:6px 16px; font-family:'Nunito',sans-serif; font-size:.78rem; font-weight:700; cursor:pointer; transition:background var(--trans); }
    .btn-search:hover { background:#222; }

    .tab-bar { display:flex; gap:8px; margin-bottom:20px; animation:fadeUp .5s .07s both; flex-wrap:wrap; }
    .tab-btn { padding:7px 16px; border-radius:20px; border:1.5px solid #e0e0ee; font-family:'Nunito',sans-serif; font-size:.78rem; font-weight:700; cursor:pointer; color:var(--muted); background:#fff; transition:all var(--trans); text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
    .tab-btn:hover { border-color:var(--btn-primary); color:var(--btn-primary); }
    .tab-btn.active { background:var(--btn-primary); color:#fff; border-color:var(--btn-primary); }
    .tab-count { background:rgba(255,255,255,.25); color:inherit; font-size:.65rem; padding:1px 6px; border-radius:10px; }
    .tab-btn:not(.active) .tab-count { background:#f0f0f8; }

    .content-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; animation:fadeUp .5s .1s both; }
    .content-title { font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:700; color:var(--text); }

    .pem-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:14px; animation:fadeUp .5s .13s both; }
    .pem-card { background:#fff; border-radius:12px; box-shadow:var(--shadow); overflow:hidden; transition:box-shadow var(--trans), transform var(--trans); animation:fadeUp .4s both; }
    .pem-card:hover { box-shadow:0 6px 18px rgba(0,0,0,.11); transform:translateY(-2px); }
    .pem-card.terlambat { border-left:4px solid #ef4444; }
    .pem-card.dikembalikan { border-left:4px solid #10b981; opacity:.88; }
    .pem-card.ada-denda { border-left:4px solid #f59e0b; }

    .pem-card-top { display:flex; gap:14px; padding:14px 14px 0; }
    .pem-thumb { width:60px; flex-shrink:0; aspect-ratio:2/3; border-radius:6px; overflow:hidden; background:#e0e0e8; display:flex; align-items:center; justify-content:center; }
    .pem-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
    .pem-thumb svg { width:22px; height:22px; color:#bbb; }
    .pem-info { flex:1; min-width:0; }
    .pem-judul { font-size:.82rem; font-weight:800; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:2px; }
    .pem-penulis { font-size:.68rem; color:var(--muted); font-weight:600; margin-bottom:8px; }
    .pem-peminjam { font-size:.75rem; font-weight:700; color:var(--text); display:flex; align-items:center; gap:5px; background:#f5f5fb; border-radius:6px; padding:5px 8px; margin-bottom:4px; }
    .pem-peminjam svg { width:13px; height:13px; color:var(--muted); flex-shrink:0; }

    .pem-meta { padding:10px 14px; display:flex; flex-direction:column; gap:4px; }
    .pem-meta-row { display:flex; align-items:center; gap:6px; font-size:.7rem; color:var(--muted); font-weight:600; }
    .pem-meta-row svg { width:12px; height:12px; flex-shrink:0; }
    .pem-meta-val { color:var(--text); font-weight:700; }

    /* Denda info box */
    .denda-box { margin:0 14px 10px; background:#fff9e6; border:1.5px solid #fcd34d; border-radius:8px; padding:9px 12px; }
    .denda-box .denda-title { font-size:.68rem; font-weight:800; color:#92400e; text-transform:uppercase; letter-spacing:.04em; margin-bottom:3px; }
    .denda-box .denda-nominal { font-size:1rem; font-weight:800; color:#dc2626; }
    .denda-box .denda-sub { font-size:.65rem; color:#b45309; margin-top:1px; }
    /* Denda box merah jika preview (belum dikembalikan) */
    .denda-box.preview { background:#fef2f2; border-color:#fca5a5; }
    .denda-box.preview .denda-title { color:#991b1b; }
    .denda-box.preview .denda-sub { color:#dc2626; }
    /* Denda lunas */
    .denda-box.lunas { background:#f0fdf4; border-color:#86efac; }
    .denda-box.lunas .denda-title { color:#166534; }
    .denda-box.lunas .denda-nominal { color:#15803d; }
    .denda-box.lunas .denda-sub { color:#16a34a; }

    .status-chip { display:inline-flex; align-items:center; gap:4px; font-size:.65rem; font-weight:800; padding:3px 9px; border-radius:20px; letter-spacing:.04em; text-transform:uppercase; margin-bottom:8px; }
    .chip-dipinjam   { background:#dbeafe; color:#1d4ed8; }
    .chip-terlambat  { background:#fee2e2; color:#b91c1c; }
    .chip-kembali    { background:#d1fae5; color:#065f46; }

    .sisa-badge { font-size:.7rem; font-weight:700; padding:3px 10px; border-radius:6px; display:inline-block; margin-top:2px; }
    .sisa-ok       { background:#eff6ff; color:#2563eb; }
    .sisa-terlambat{ background:#fef2f2; color:#dc2626; }

    .pem-footer { padding:0 14px 14px; }
    .btn-kembalikan { display:block; width:100%; padding:8px 0; border-radius:7px; border:none; background:var(--btn-kembali); color:#fff; font-family:'Nunito',sans-serif; font-size:.72rem; font-weight:700; cursor:pointer; text-align:center; transition:opacity var(--trans), transform .12s; letter-spacing:.02em; }
    .btn-kembalikan:hover  { opacity:.87; }
    .btn-kembalikan:active { transform:scale(.96); }
    .btn-kembalikan.has-denda { background:#dc2626; }
    .returned-label { display:block; width:100%; padding:8px 0; text-align:center; font-size:.72rem; font-weight:700; color:#065f46; background:#d1fae5; border-radius:7px; letter-spacing:.02em; margin-bottom:6px; }
    .btn-hapus-riwayat { display:block; width:100%; padding:7px 0; border-radius:7px; border:1.5px solid #fca5a5; background:#fff; color:#dc2626; font-family:'Nunito',sans-serif; font-size:.68rem; font-weight:700; cursor:pointer; text-align:center; transition:all var(--trans); }
    .btn-hapus-riwayat:hover { background:#fef2f2; }

    /* Tombol kirim pengingat WA */
    .btn-wa {
      display:flex; align-items:center; justify-content:center; gap:6px;
      width:100%; padding:8px 0; border-radius:7px; border:none;
      background:#25D366; color:#fff; text-decoration:none;
      font-family:'Nunito',sans-serif; font-size:.72rem; font-weight:700;
      cursor:pointer; transition:opacity var(--trans), transform .12s;
      letter-spacing:.02em; margin-bottom:6px;
    }
    .btn-wa:hover  { opacity:.88; }
    .btn-wa:active { transform:scale(.96); }
    .btn-wa svg { width:14px; height:14px; flex-shrink:0; }
    .btn-wa-disabled {
      display:block; width:100%; padding:8px 0; border-radius:7px;
      background:#f3f4f6; color:#9ca3af; text-align:center;
      font-family:'Nunito',sans-serif; font-size:.68rem; font-weight:700;
      margin-bottom:6px; border:1.5px dashed #d1d5db;
    }
    .wa-status-row {
      display:flex; align-items:center; justify-content:space-between;
      margin-bottom:8px; font-size:.66rem; font-weight:700;
    }
    .wa-status-sent { color:#059669; }
    .btn-tandai-wa {
      background:none; border:none; color:var(--muted);
      font-family:'Nunito',sans-serif; font-size:.66rem; font-weight:700;
      cursor:pointer; text-decoration:underline; padding:0;
    }
    .btn-tandai-wa:hover { color:var(--btn-pinjam); }

    /* Chip status denda */
    .denda-status-chip { display:inline-flex; align-items:center; gap:3px; font-size:.62rem; font-weight:800; padding:2px 7px; border-radius:12px; margin-left:4px; }
    .denda-chip-belum { background:#fee2e2; color:#b91c1c; }
    .denda-chip-lunas { background:#d1fae5; color:#065f46; }

    .empty-state { text-align:center; padding:60px 20px; color:var(--muted); }
    .empty-state svg { width:56px; height:56px; margin-bottom:12px; opacity:.35; }
    .empty-state p { font-size:.88rem; font-weight:600; }

    /* MODAL */
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:500; align-items:center; justify-content:center; }
    .modal-overlay.open { display:flex; }
    .modal { background:#fff; border-radius:14px; width:100%; max-width:400px; box-shadow:0 20px 60px rgba(0,0,0,.25); animation:modalIn .25s cubic-bezier(.22,1,.36,1) both; padding:28px 28px 24px; margin:16px; }
    @keyframes modalIn { from { opacity:0; transform:scale(.94) translateY(10px); } to { opacity:1; transform:scale(1) translateY(0); } }
    .modal-title { font-family:'Cormorant Garamond',serif; font-size:1.25rem; font-weight:700; color:var(--text); margin-bottom:8px; }
    .modal-desc  { font-size:.82rem; color:var(--muted); font-weight:600; margin-bottom:14px; line-height:1.5; }
    /* Denda info di modal */
    .modal-denda-box { background:#fff9e6; border:1.5px solid #fcd34d; border-radius:8px; padding:12px 14px; margin-bottom:18px; }
    .modal-denda-box.no-denda { background:#f0fdf4; border-color:#86efac; }
    .modal-denda-label { font-size:.7rem; font-weight:800; color:#92400e; text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
    .modal-denda-label.no-denda { color:#166534; }
    .modal-denda-val { font-size:1.1rem; font-weight:800; color:#dc2626; }
    .modal-denda-val.no-denda { color:#16a34a; }
    .modal-denda-sub { font-size:.68rem; color:#b45309; margin-top:2px; }
    .modal-footer { display:flex; gap:10px; }
    .btn-confirm { flex:1; padding:11px; border-radius:8px; border:none; background:var(--btn-kembali); color:#fff; font-family:'Nunito',sans-serif; font-size:.85rem; font-weight:700; cursor:pointer; transition:background var(--trans); }
    .btn-confirm:hover { background:#047857; }
    .btn-confirm.denda { background:#dc2626; }
    .btn-confirm.denda:hover { background:#b91c1c; }
    .btn-cancel-modal { padding:11px 18px; border-radius:8px; border:1.5px solid #e4e5f0; background:#fff; font-family:'Nunito',sans-serif; font-size:.85rem; font-weight:700; color:var(--muted); cursor:pointer; transition:all var(--trans); }
    .btn-cancel-modal:hover { border-color:#777; color:#333; }

    @keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:translateY(0); } }
    .pem-card:nth-child(1)  { animation-delay:.06s; }
    .pem-card:nth-child(2)  { animation-delay:.10s; }
    .pem-card:nth-child(3)  { animation-delay:.14s; }
    .pem-card:nth-child(n+4){ animation-delay:.18s; }

    @media (max-width:860px) {
      :root { --sidebar-w:170px; }
      .avatar-circle { width:76px; height:76px; }
    }
    @media (max-width: 640px) {
      .sidebar { transform:translateX(-100%); }
      .sidebar.open { transform:translateX(0); }
      .sidebar-toggle { display:flex; }
      .main { margin-left:0; padding:16px; padding-top:60px; }
      .pem-grid { grid-template-columns:1fr; }
    }

    .col-a { background:linear-gradient(135deg,#f5a623,#d4820a); }
    .col-b { background:linear-gradient(135deg,#e74c3c,#922b21); }
    .col-c { background:linear-gradient(135deg,#9b59b6,#6c3483); }
    .col-d { background:linear-gradient(135deg,#2ecc71,#1a8a4a); }
    .col-e { background:linear-gradient(135deg,#3498db,#1a5276); }
    .col-f { background:linear-gradient(135deg,#e91e63,#880e4f); }
    .col-g { background:linear-gradient(135deg,#ff5722,#bf360c); }
    .col-h { background:linear-gradient(135deg,#607d8b,#263238); }
  </style>
</head>
<body>

<button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
  </svg>
</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
  <div class="avatar-wrap" onclick="openProfilModal()" title="Edit Profil">
    <div class="avatar-circle">
      <?php if ($admin_foto && file_exists($admin_foto)): ?>
        <img src="<?= htmlspecialchars($admin_foto) ?>?v=<?= filemtime($admin_foto) ?>" alt="Admin"/>
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
    Daftar Anggota
  </a>

  <a class="sidebar-btn" href="pinjam_buku.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
    Pinjam Buku
  </a>
  <a class="sidebar-btn active" href="telah_dipinjam.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    Telah Dipinjam
  </a>
  <a class="sidebar-btn" href="pengaturan_denda.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
    Pengaturan Denda
  </a>
  <a class="sidebar-btn" href="beranda.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Kembali
  </a>
</aside>

<main class="main">

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type ?>">
    <?= $msg_type === 'success' ? '✅' : ($msg_type === 'warning' ? '⚠️' : '❌') ?> <?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>

  <?php if ($cnt_denda_pending > 0): ?>
  <div class="denda-banner">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    <span><?= $cnt_denda_pending ?> peminjam belum membayar denda — Total: <strong>Rp <?= number_format($total_denda_pending, 0, ',', '.') ?></strong></span>
    <a href="pengaturan_denda.php">Kelola Denda →</a>
  </div>
  <?php endif; ?>

  <!-- Search -->
  <form method="GET" action="" id="searchForm">
    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>"/>
    <div class="topbar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" name="q" id="searchInput" autocomplete="off" placeholder="Cari buku atau nama peminjam…" value="<?= htmlspecialchars($search) ?>"/>
      <?php if ($search): ?>
      <a href="telah_dipinjam.php?tab=<?= $tab ?>" id="searchResetBtn" style="font-size:.75rem;color:var(--muted);text-decoration:none;white-space:nowrap;">✕ Reset</a>
      <?php endif; ?>
    </div>
  </form>

  <div id="searchResultArea">
  <?php ob_start(); ?>
  <!-- Tab bar -->
  <div class="tab-bar">
    <?php
    $tabs = [
      'dipinjam'     => ['label' => '📖 Sedang Dipinjam'],
      'dikembalikan' => ['label' => '✅ Dikembalikan'],
      'riwayat'      => ['label' => '📋 Semua Riwayat'],
    ];
    foreach ($tabs as $key => $data):
      $active = $tab === $key ? 'active' : '';
      $href   = 'telah_dipinjam.php?tab=' . $key . ($search ? '&q=' . urlencode($search) : '');
    ?>
    <a href="<?= $href ?>" class="tab-btn <?= $active ?>">
      <?= $data['label'] ?>
      <span class="tab-count"><?= $cnt[$key] ?></span>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="content-header">
    <div class="content-title">
      <?= match($tab) { 'dipinjam' => 'Sedang Dipinjam', 'dikembalikan' => 'Sudah Dikembalikan', default => 'Semua Riwayat' } ?>
      <?php if ($search): ?><span style="font-size:.9rem;color:var(--muted);font-family:'Nunito',sans-serif;"> — "<?= htmlspecialchars($search) ?>"</span><?php endif; ?>
    </div>
    <span style="font-size:.78rem;color:var(--muted);font-weight:700;"><?= $total_tab ?> entri</span>
  </div>

  <?php if (empty($pem_list)): ?>
  <div class="empty-state">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    <p><?= $search ? 'Tidak ada data yang cocok.' : 'Belum ada data peminjaman.' ?></p>
  </div>
  <?php else: ?>
  <div class="pem-grid">
    <?php foreach ($pem_list as $i => $pem):
      $isDipinjam  = $pem['status'] === 'dipinjam';
      $sisa        = $isDipinjam ? sisaWaktu($pem['batas_kembali']) : null;
      $isTerlambat = $sisa['terlambat'] ?? false;

      // Denda preview untuk yang masih dipinjam
      $preview_denda = 0;
      if ($isDipinjam && $isTerlambat) {
          $preview_denda = previewDenda($pem['batas_kembali'], $denda_aktif, $denda_per_hari, $grace_period);
      }

      // Data pengingat WA — hanya relevan untuk peminjaman aktif yang terlambat
      $waNomor = '';
      $waLink  = '';
      $waSudahDikirim = (int)($pem['wa_terkirim_hari_ini'] ?? 0) === 1;
      if ($isDipinjam && $isTerlambat) {
          $waNomor = formatNoWa($pem['anggota_no_hp'] ?? '');
          if ($waNomor !== '') {
              $pesanWa = pesanPengingatWa(
                  $pem['nama_peminjam'],
                  $pem['judul'],
                  $pem['batas_kembali'],
                  $sisa['hari'] ?? 0,
                  $preview_denda
              );
              $waLink = 'https://wa.me/' . $waNomor . '?text=' . urlencode($pesanWa);
          }
      }

      // Status denda untuk yang sudah dikembalikan
      $denda_sudah    = (int)($pem['denda'] ?? 0);
      $status_denda   = $pem['status_denda'] ?? 'tidak_ada';
      $terlambat_hari_sudah = (int)($pem['terlambat_hari'] ?? 0);

      // Tentukan class card
      $cardClass = '';
      if ($isDipinjam) {
          $cardClass = $isTerlambat ? 'terlambat' : '';
      } else {
          if ($denda_sudah > 0 && $status_denda === 'belum_bayar') $cardClass = 'ada-denda';
          else $cardClass = 'dikembalikan';
      }

      $col     = $colors[$i % count($colors)];
      $wKembali = $pem['waktu_kembali'] ?? date('Y-m-d H:i:s');
      $durasi  = durasiHuman($pem['waktu_pinjam'], $isDipinjam ? date('Y-m-d H:i:s') : $wKembali);
    ?>
    <div class="pem-card <?= $cardClass ?>">
      <div class="pem-card-top">
        <div class="pem-thumb <?= ($pem['gambar'] && file_exists($pem['gambar'])) ? '' : $col ?>">
          <?php if ($pem['gambar'] && file_exists($pem['gambar'])): ?>
            <img src="<?= htmlspecialchars($pem['gambar']) ?>" alt="cover"/>
          <?php else: ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.2" opacity=".6"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          <?php endif; ?>
        </div>
        <div class="pem-info">
          <?php if ($isDipinjam && $isTerlambat): ?>
            <span class="status-chip chip-terlambat">⚠ Terlambat</span>
          <?php elseif ($isDipinjam): ?>
            <span class="status-chip chip-dipinjam">📖 Dipinjam</span>
          <?php else: ?>
            <span class="status-chip chip-kembali">✅ Dikembalikan</span>
            <?php if ($denda_sudah > 0 && $status_denda === 'belum_bayar'): ?>
              <span class="denda-status-chip denda-chip-belum">⚠ Belum Bayar</span>
            <?php elseif ($denda_sudah > 0 && $status_denda === 'lunas'): ?>
              <span class="denda-status-chip denda-chip-lunas">✅ Lunas</span>
            <?php endif; ?>
          <?php endif; ?>

          <div class="pem-judul" title="<?= htmlspecialchars($pem['judul']) ?>"><?= htmlspecialchars($pem['judul']) ?></div>
          <div class="pem-penulis"><?= htmlspecialchars($pem['penulis'] ?? '-') ?></div>
          <div class="pem-peminjam">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <?= htmlspecialchars($pem['nama_peminjam']) ?>
          </div>
        </div>
      </div>

      <div class="pem-meta">
        <div class="pem-meta-row">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          Dipinjam:&nbsp;<span class="pem-meta-val"><?= date('d M Y, H:i', strtotime($pem['waktu_pinjam'])) ?></span>
        </div>
        <div class="pem-meta-row">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          Batas:&nbsp;<span class="pem-meta-val"><?= date('d M Y, H:i', strtotime($pem['batas_kembali'])) ?></span>
        </div>

        <?php if ($isDipinjam && $sisa): ?>
        <div class="pem-meta-row" style="margin-top:2px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
          Sisa:&nbsp;<span class="sisa-badge <?= $isTerlambat ? 'sisa-terlambat' : 'sisa-ok' ?>"><?= htmlspecialchars($sisa['label']) ?></span>
        </div>
        <?php elseif (!$isDipinjam && $pem['waktu_kembali']): ?>
        <div class="pem-meta-row">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/></svg>
          Dikembalikan:&nbsp;<span class="pem-meta-val"><?= date('d M Y, H:i', strtotime($pem['waktu_kembali'])) ?></span>
        </div>
        <div class="pem-meta-row">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          Durasi:&nbsp;<span class="pem-meta-val"><?= htmlspecialchars($durasi) ?></span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Denda Info Box -->
      <?php if ($isDipinjam && $isTerlambat && $preview_denda > 0 && $denda_aktif): ?>
      <div class="denda-box preview">
        <div class="denda-title">⚠ Estimasi Denda Saat Ini</div>
        <div class="denda-nominal">Rp <?= number_format($preview_denda, 0, ',', '.') ?></div>
        <div class="denda-sub">Bertambah Rp <?= number_format($denda_per_hari, 0, ',', '.') ?>/hari sampai dikembalikan</div>
      </div>
      <?php elseif (!$isDipinjam && $denda_sudah > 0): ?>
      <div class="denda-box <?= $status_denda === 'lunas' ? 'lunas' : '' ?>">
        <div class="denda-title <?= $status_denda === 'lunas' ? 'no-denda' : '' ?>">
          <?= $status_denda === 'lunas' ? '✅ Denda Lunas' : '💰 Denda Keterlambatan' ?>
          <?= $terlambat_hari_sudah > 0 ? "($terlambat_hari_sudah hari)" : '' ?>
        </div>
        <div class="denda-nominal <?= $status_denda === 'lunas' ? 'no-denda' : '' ?>">
          Rp <?= number_format($denda_sudah, 0, ',', '.') ?>
        </div>
        <?php if ($status_denda !== 'lunas'): ?>
        <div class="denda-sub">Harap segera diselesaikan</div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="pem-footer">
        <?php if ($isDipinjam): ?>
          <?php if ($isTerlambat): ?>
            <?php if ($waSudahDikirim): ?>
            <div class="wa-status-row">
              <span class="wa-status-sent">✅ WA sudah dikirim hari ini</span>
              <button type="button" class="btn-tandai-wa" onclick="tandaiWa(<?= $pem['id'] ?>, 0)">Batalkan</button>
            </div>
            <?php endif; ?>

            <?php if ($waLink): ?>
            <a class="btn-wa" href="<?= htmlspecialchars($waLink) ?>" target="_blank" rel="noopener"
               onclick="setTimeout(function(){ tandaiWa(<?= $pem['id'] ?>, 1); }, 400);">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20zm4.4-5.7c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.5.1s-.6.8-.7.9-.3.2-.5.1a6.5 6.5 0 0 1-1.9-1.2 7 7 0 0 1-1.3-1.6c-.1-.2 0-.3.1-.5l.4-.4c.1-.1.1-.2.2-.4a.4.4 0 0 0 0-.4c0-.1-.5-1.3-.7-1.8s-.4-.4-.5-.4h-.5a.9.9 0 0 0-.6.3 2.7 2.7 0 0 0-.9 2 4.7 4.7 0 0 0 1 2.5 10.7 10.7 0 0 0 4.1 3.6c.6.2 1 .4 1.4.5a3.3 3.3 0 0 0 1.5.1 2.5 2.5 0 0 0 1.6-1.2 2.1 2.1 0 0 0 .2-1.2c-.1-.1-.3-.2-.5-.3z"/></svg>
              Kirim Pengingat WA
            </a>
            <?php elseif (!$waSudahDikirim): ?>
            <div class="btn-wa-disabled" title="No HP anggota belum tercatat di akunnya">📵 No HP anggota belum diisi</div>
            <?php endif; ?>
          <?php endif; ?>
        <button class="btn-kembalikan <?= ($isTerlambat && $preview_denda > 0) ? 'has-denda' : '' ?>"
          onclick="konfirmasiKembali(
            <?= $pem['id'] ?>,
            '<?= htmlspecialchars(addslashes($pem['judul'])) ?>',
            '<?= htmlspecialchars(addslashes($pem['nama_peminjam'])) ?>',
            <?= $isTerlambat ? 1 : 0 ?>,
            <?= $preview_denda ?>,
            '<?= htmlspecialchars(addslashes($sisa['label'] ?? '')) ?>'
          )">
          <?= ($isTerlambat && $preview_denda > 0) ? '⚠ Kembalikan + Catat Denda' : '✅ Kembalikan Buku' ?>
        </button>
        <?php else: ?>
        <span class="returned-label">✅ Buku telah dikembalikan</span>
        <button class="btn-hapus-riwayat"
          onclick="konfirmasiHapusRiwayat(<?= $pem['id'] ?>, '<?= htmlspecialchars(addslashes($pem['judul'])) ?>', '<?= htmlspecialchars(addslashes($pem['nama_peminjam'])) ?>')">
          🗑 Hapus Riwayat
        </button>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php
  $search_result_html = ob_get_clean();
  if ($is_ajax) {
      ob_end_clean();
      header('Content-Type: text/html; charset=utf-8');
      echo $search_result_html;
      exit;
  }
  echo $search_result_html;
  ?>
  </div>

</main>

<!-- ═══ MODAL KONFIRMASI KEMBALIKAN ═══ -->
<div class="modal-overlay" id="kembaliModalOverlay">
  <div class="modal">
    <div class="modal-title">Konfirmasi Pengembalian</div>
    <div class="modal-desc" id="kembaliDesc"></div>

    <!-- Info denda di modal -->
    <div id="dendaInfoBox" class="modal-denda-box" style="display:none;">
      <div class="modal-denda-label" id="dendaInfoLabel"></div>
      <div class="modal-denda-val" id="dendaInfoVal"></div>
      <div class="modal-denda-sub" id="dendaInfoSub"></div>
    </div>

    <form method="POST" action="telah_dipinjam.php?tab=<?= $tab ?><?= $search ? '&q=' . urlencode($search) : '' ?>">
      <input type="hidden" name="action"  value="kembalikan"/>
      <input type="hidden" name="pem_id"  id="inputPemId"/>
      <div class="modal-footer">
        <button type="submit" class="btn-confirm" id="btnKonfirmasi">✅ Ya, Kembalikan</button>
        <button type="button" class="btn-cancel-modal" onclick="tutupKembaliModal()">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL KONFIRMASI HAPUS RIWAYAT ═══ -->
<div class="modal-overlay" id="hapusRiwayatModalOverlay">
  <div class="modal">
    <div class="modal-title">Hapus Riwayat?</div>
    <div class="modal-desc" id="hapusRiwayatDesc"></div>
    <form method="POST" action="telah_dipinjam.php?tab=<?= $tab ?><?= $search ? '&q=' . urlencode($search) : '' ?>">
      <input type="hidden" name="action"  value="hapus_riwayat"/>
      <input type="hidden" name="pem_id"  id="inputHapusRiwayatId"/>
      <div class="modal-footer">
        <button type="submit" class="btn-confirm" style="background:#dc2626;">🗑 Ya, Hapus</button>
        <button type="button" class="btn-cancel-modal" onclick="tutupHapusRiwayatModal()">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══════════ MODAL EDIT PROFIL ADMIN ═══════════ -->
<div class="modal-overlay" id="profilModalOverlay">
  <div class="modal" style="max-width:380px;">
    <div class="modal-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
      <div class="modal-title" style="margin-bottom:0;">Edit Profil</div>
      <button class="modal-close" onclick="closeProfilModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="POST" action="update_profil_admin.php" enctype="multipart/form-data">
      <input type="hidden" name="redirect" value="telah_dipinjam.php"/>

      <!-- Preview foto -->
      <div class="img-preview-wrap" style="aspect-ratio:1/1;max-width:160px;margin:0 auto 18px;border-radius:50%;" onclick="document.getElementById('inputFotoAdmin').click()">
        <?php if ($admin_foto && file_exists($admin_foto)): ?>
          <img id="profilPreviewImg" src="<?= htmlspecialchars($admin_foto) ?>" alt="Foto" style="display:block;border-radius:50%;"/>
          <div class="upload-placeholder" id="profilUploadPlaceholder" style="display:none;">
        <?php else: ?>
          <img id="profilPreviewImg" src="" alt="Foto" style="display:none;border-radius:50%;"/>
          <div class="upload-placeholder" id="profilUploadPlaceholder">
        <?php endif; ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
              <circle cx="12" cy="13" r="4"/>
            </svg>
            <span style="font-size:.7rem;">Upload Foto</span>
          </div>
      </div>
      <input type="file" id="inputFotoAdmin" name="foto_admin" accept="image/*" style="display:none"/>

      <div class="form-group">
        <label class="form-label">Nama Tampilan</label>
        <input class="form-input" type="text" name="display_name"
               value="<?= htmlspecialchars($admin_name) ?>"
               placeholder="Nama yang ditampilkan" required/>
      </div>

      <div class="modal-footer">
        <button type="submit" class="btn-save">Simpan</button>
        <button type="button" class="btn-cancel-modal" onclick="closeProfilModal()">Batal</button>
      </div>
    </form>
  </div>
</div>

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

// ─── Modal Edit Profil ───
function openProfilModal() {
  document.getElementById('profilModalOverlay').classList.add('open');
}
function closeProfilModal() {
  document.getElementById('profilModalOverlay').classList.remove('open');
  document.getElementById('inputFotoAdmin').value = '';
}
document.getElementById('profilModalOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeProfilModal();
});
document.getElementById('inputFotoAdmin').addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = ev => {
    const img = document.getElementById('profilPreviewImg');
    img.src = ev.target.result;
    img.style.display = 'block';
    document.getElementById('profilUploadPlaceholder').style.display = 'none';
  };
  reader.readAsDataURL(file);
});

function formatRupiah(n) {
  return 'Rp ' + n.toLocaleString('id-ID');
}

function konfirmasiKembali(pemId, judul, peminjam, terlambat, dendaEstimasi, sisaLabel) {
  document.getElementById('inputPemId').value = pemId;
  document.getElementById('kembaliDesc').textContent =
    `Kembalikan buku "${judul}" yang dipinjam oleh ${peminjam}?`;

  const box   = document.getElementById('dendaInfoBox');
  const label = document.getElementById('dendaInfoLabel');
  const val   = document.getElementById('dendaInfoVal');
  const sub   = document.getElementById('dendaInfoSub');
  const btn   = document.getElementById('btnKonfirmasi');

  if (terlambat && dendaEstimasi > 0) {
    box.style.display = 'block';
    box.className = 'modal-denda-box';
    label.className = 'modal-denda-label';
    label.textContent = '⚠ Denda akan dicatat: ' + sisaLabel;
    val.className = 'modal-denda-val';
    val.textContent = formatRupiah(dendaEstimasi);
    sub.textContent = 'Denda akan tersimpan dengan status "Belum Bayar". Tandai lunas di halaman Pengaturan Denda.';
    btn.textContent = '⚠ Kembalikan & Catat Denda';
    btn.className = 'btn-confirm denda';
  } else {
    box.style.display = 'block';
    box.className = 'modal-denda-box no-denda';
    label.className = 'modal-denda-label no-denda';
    label.textContent = '✅ Tidak ada denda';
    val.className = 'modal-denda-val no-denda';
    val.textContent = 'Rp 0';
    sub.textContent = 'Buku dikembalikan tepat waktu.';
    btn.textContent = '✅ Ya, Kembalikan';
    btn.className = 'btn-confirm';
  }

  document.getElementById('kembaliModalOverlay').classList.add('open');
}

function tutupKembaliModal() {
  document.getElementById('kembaliModalOverlay').classList.remove('open');
}
document.getElementById('kembaliModalOverlay').addEventListener('click', function(e) {
  if (e.target === this) tutupKembaliModal();
});

function konfirmasiHapusRiwayat(pemId, judul, peminjam) {
  document.getElementById('inputHapusRiwayatId').value = pemId;
  document.getElementById('hapusRiwayatDesc').textContent =
    `Hapus riwayat peminjaman buku "${judul}" oleh ${peminjam}? Data tidak bisa dipulihkan.`;
  document.getElementById('hapusRiwayatModalOverlay').classList.add('open');
}
function tutupHapusRiwayatModal() {
  document.getElementById('hapusRiwayatModalOverlay').classList.remove('open');
}
document.getElementById('hapusRiwayatModalOverlay').addEventListener('click', function(e) {
  if (e.target === this) tutupHapusRiwayatModal();
});

// ─── Tandai status kirim WA (dipanggil otomatis saat tombol "Kirim Pengingat WA" diklik) ───
function tandaiWa(pemId, status) {
  const body = new URLSearchParams();
  body.set('action', status ? 'tandai_wa' : 'batal_wa');
  body.set('pem_id', pemId);
  fetch('telah_dipinjam.php', { method: 'POST', body })
    .then(() => { window.location.reload(); })
    .catch(() => { /* biarkan diam-diam gagal, tidak mengganggu pengiriman WA */ });
}

// ─── Live Search (ketik langsung cari, tanpa tombol) ───
(function initLiveSearch() {
  const CURRENT_TAB = <?= json_encode($tab) ?>;
  const form   = document.getElementById('searchForm');
  const input  = document.getElementById('searchInput');
  const result = document.getElementById('searchResultArea');
  if (!form || !input || !result) return;

  let debounceTimer = null;
  let currentRequest = null;

  form.addEventListener('submit', e => e.preventDefault());

  function buildParams(query) {
    const params = new URLSearchParams();
    params.set('tab', CURRENT_TAB);
    if (query) params.set('q', query);
    return params;
  }

  function runSearch(query) {
    if (currentRequest) currentRequest.abort();
    const controller = new AbortController();
    currentRequest = controller;

    const params = buildParams(query);
    params.set('ajax', '1');
    result.classList.add('loading-search');

    fetch('telah_dipinjam.php?' + params.toString(), { signal: controller.signal })
      .then(r => r.text())
      .then(html => {
        result.innerHTML = html;
        result.classList.remove('loading-search');
        const qs = buildParams(query).toString();
        history.replaceState(null, '', 'telah_dipinjam.php?' + qs);
      })
      .catch(err => {
        if (err.name !== 'AbortError') result.classList.remove('loading-search');
      });
  }

  input.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    const query = input.value;
    debounceTimer = setTimeout(() => runSearch(query), 300);
  });

  document.addEventListener('click', e => {
    const resetBtn = e.target.closest('#searchResetBtn');
    if (!resetBtn) return;
    e.preventDefault();
    input.value = '';
    runSearch('');
  });
})();
</script>
</body>
</html>
<?php ob_end_flush(); ?>