<?php
// daftar_anggota.php — Panel admin untuk kelola anggota (approve/tolak/reset sandi)
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: sign_in.php");
    exit;
}

require_once "db.php";

$page_title = "Daftar Anggota – AKSA NOVA";
$msg        = "";
$msg_type   = "";
$reset_info = null; // dipakai untuk tampilkan password baru sekali saja setelah reset

$profil_res  = mysqli_query($conn, "SELECT * FROM admin_profile LIMIT 1");
$profil      = $profil_res ? mysqli_fetch_assoc($profil_res) : null;
$admin_name  = $profil["display_name"] ?? ($_SESSION["user_name"] ?? "Admin");
$admin_foto  = $profil["foto"] ?? "";

$total_res  = mysqli_query($conn, "SELECT COUNT(*) AS c FROM buku");
$total_buku = $total_res ? (int)(mysqli_fetch_assoc($total_res)['c'] ?? 0) : 0;

// ─────────────────────────────────────────────
//  AKSI
// ─────────────────────────────────────────────
$action = $_POST["action"] ?? "";

if ($action === "approve") {
    $id = (int)($_POST["id"] ?? 0);
    if ($id > 0) {
        mysqli_query($conn, "UPDATE users SET status='approved' WHERE id=$id AND role='member'");
        $msg = "Akun anggota berhasil diaktifkan."; $msg_type = "success";
    }
}

if ($action === "unfreeze") {
    $id = (int)($_POST["id"] ?? 0);
    if ($id > 0) {
        mysqli_query($conn, "UPDATE users SET card_status='active' WHERE id=$id AND role='member'");
        $msg = "Kartu anggota berhasil dicairkan kembali."; $msg_type = "success";
    }
}

if ($action === "reject") {
    $id = (int)($_POST["id"] ?? 0);
    if ($id > 0) {
        mysqli_query($conn, "UPDATE users SET status='rejected' WHERE id=$id AND role='member'");
        $msg = "Akun anggota berhasil dinonaktifkan."; $msg_type = "success";
    }
}

if ($action === "hapus") {
    $id = (int)($_POST["id"] ?? 0);
    if ($id > 0) {
        $cek = mysqli_query($conn, "SELECT COUNT(*) AS c FROM peminjaman WHERE user_id=$id");
        $jml = (int)(mysqli_fetch_assoc($cek)['c'] ?? 0);
        if ($jml > 0) {
            $msg = "Anggota tidak bisa dihapus karena masih punya $jml riwayat peminjaman.";
            $msg_type = "error";
        } else {
            // Hapus file foto anggota jika ada
            $r_foto = mysqli_query($conn, "SELECT foto FROM users WHERE id=$id AND role='member'");
            if ($r_foto && $row_f = mysqli_fetch_assoc($r_foto)) {
                if (!empty($row_f["foto"]) && file_exists($row_f["foto"])) {
                    @unlink($row_f["foto"]);
                }
            }
            mysqli_query($conn, "DELETE FROM users WHERE id=$id AND role='member'");
            $msg = "Anggota berhasil dihapus."; $msg_type = "success";
        }
    }
}

if ($action === "reset_password") {
    $id = (int)($_POST["id"] ?? 0);
    if ($id > 0) {
        $r = mysqli_query($conn, "SELECT full_name, username FROM users WHERE id=$id AND role='member'");
        $u = $r ? mysqli_fetch_assoc($r) : null;

        if ($u) {
            // ── Generate password baru (sama seperti saat pendaftaran) ──
            $chars_upper = "ABCDEFGHJKLMNPQRSTUVWXYZ";
            $chars_lower = "abcdefghijkmnpqrstuvwxyz";
            $chars_num   = "23456789";
            $all_chars   = $chars_upper . $chars_lower . $chars_num;

            $new_password = $chars_upper[random_int(0, strlen($chars_upper) - 1)]
                           . $chars_lower[random_int(0, strlen($chars_lower) - 1)]
                           . $chars_num[random_int(0, strlen($chars_num) - 1)];
            for ($i = 0; $i < 5; $i++) {
                $new_password .= $all_chars[random_int(0, strlen($all_chars) - 1)];
            }
            $new_password = str_shuffle($new_password);
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=? AND role='member'");
            mysqli_stmt_bind_param($stmt, "si", $hashed, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $reset_info = [
                "full_name" => $u["full_name"],
                "username"  => $u["username"],
                "password"  => $new_password,
            ];
            $msg = "Sandi berhasil diubah."; $msg_type = "success";
        }
    }
}

if ($action === "tambah_kelas") {
    $nama_kelas_baru = trim($_POST["nama_kelas"] ?? "");
    if ($nama_kelas_baru !== "") {
        $stmt = mysqli_prepare($conn, "INSERT INTO kelas (nama_kelas) VALUES (?)");
        mysqli_stmt_bind_param($stmt, "s", $nama_kelas_baru);
        if (@mysqli_stmt_execute($stmt)) {
            $msg = "Kelas \"$nama_kelas_baru\" berhasil ditambahkan."; $msg_type = "success";
        } else {
            $msg = "Kelas \"$nama_kelas_baru\" sudah ada atau gagal ditambahkan."; $msg_type = "error";
        }
        mysqli_stmt_close($stmt);
    } else {
        $msg = "Nama kelas tidak boleh kosong."; $msg_type = "error";
    }
}

if ($action === "hapus_kelas") {
    $id_k = (int)($_POST["id_kelas"] ?? 0);
    if ($id_k > 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM kelas WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_k);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $msg = "Kelas berhasil dihapus."; $msg_type = "success";
    }
}

if ($action === "naik_kelas_masal") {
    $kelas_asal   = trim($_POST["kelas_asal"] ?? "");
    $kelas_tujuan = trim($_POST["kelas_tujuan"] ?? "");

    if ($kelas_asal === "" || $kelas_tujuan === "") {
        $msg = "Kelas asal dan kelas tujuan wajib dipilih.";
        $msg_type = "error";
    } elseif (strcasecmp($kelas_asal, $kelas_tujuan) === 0) {
        $msg = "Kelas asal dan kelas tujuan tidak boleh sama.";
        $msg_type = "error";
    } else {
        // Cek jumlah siswa yang ada di kelas asal
        $stmt_c = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM users WHERE kelas = ? AND role = 'member'");
        mysqli_stmt_bind_param($stmt_c, "s", $kelas_asal);
        mysqli_stmt_execute($stmt_c);
        $res_c = mysqli_stmt_get_result($stmt_c);
        $count = $res_c ? (int)(mysqli_fetch_assoc($res_c)['c'] ?? 0) : 0;
        mysqli_stmt_close($stmt_c);

        if ($count === 0) {
            $msg = "Tidak ada anggota ditemukan pada kelas \"$kelas_asal\".";
            $msg_type = "error";
        } else {
            // Jika kelas tujuan adalah 'Alumni', pastikan 'Alumni' juga terdaftar di tabel kelas
            if (strcasecmp($kelas_tujuan, "Alumni") === 0) {
                mysqli_query($conn, "INSERT IGNORE INTO kelas (nama_kelas) VALUES ('Alumni')");
            }

            // Eksekusi update masal ke seluruh siswa di kelas asal
            $stmt_u = mysqli_prepare($conn, "UPDATE users SET kelas = ? WHERE kelas = ? AND role = 'member'");
            mysqli_stmt_bind_param($stmt_u, "ss", $kelas_tujuan, $kelas_asal);
            if (mysqli_stmt_execute($stmt_u)) {
                $affected = mysqli_stmt_affected_rows($stmt_u);
                $msg = "Berhasil memindahkan $affected siswa dari kelas \"$kelas_asal\" ke \"$kelas_tujuan\".";
                $msg_type = "success";
            } else {
                $msg = "Gagal memproses kenaikan kelas masal. Silakan coba lagi.";
                $msg_type = "error";
            }
            mysqli_stmt_close($stmt_u);
        }
    }
}

if ($action === "naik_semua_kelas") {
    $target_kelas = $_POST["target_kelas"] ?? [];
    $pilih_kelas  = $_POST["pilih_kelas"] ?? [];

    if (empty($target_kelas) || empty($pilih_kelas)) {
        $msg = "Tidak ada kelas yang dipilih untuk dinaikkan.";
        $msg_type = "error";
    } else {
        $migrasi_list = [];
        foreach ($pilih_kelas as $asal) {
            $asal = trim($asal);
            $tujuan = trim($target_kelas[$asal] ?? "");
            if ($asal !== "" && $tujuan !== "" && strcasecmp($asal, $tujuan) !== 0) {
                // Cek siswa aktif di kelas asal
                $stmt_c = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM users WHERE kelas = ? AND role = 'member'");
                mysqli_stmt_bind_param($stmt_c, "s", $asal);
                mysqli_stmt_execute($stmt_c);
                $res_c = mysqli_stmt_get_result($stmt_c);
                $cnt = $res_c ? (int)(mysqli_fetch_assoc($res_c)['c'] ?? 0) : 0;
                mysqli_stmt_close($stmt_c);

                if ($cnt > 0) {
                    $migrasi_list[] = [
                        "asal"   => $asal,
                        "tujuan" => $tujuan,
                        "jml"    => $cnt,
                    ];
                }
            }
        }

        if (empty($migrasi_list)) {
            $msg = "Tidak ada kelas yang memiliki siswa aktif untuk dinaikkan.";
            $msg_type = "error";
        } else {
            // METODE 2-FASE (100% ZERO-COLLISION GUARANTEE)
            // Fase 1: Pindahkan setiap kelas asal ke token temporer unik
            $fase1_ok = true;
            $token_prefix = "__PROMO_" . time() . "_";
            foreach ($migrasi_list as $idx => $m) {
                $temp_token = $token_prefix . $idx;
                $stmt_1 = mysqli_prepare($conn, "UPDATE users SET kelas = ? WHERE kelas = ? AND role = 'member'");
                mysqli_stmt_bind_param($stmt_1, "ss", $temp_token, $m["asal"]);
                if (!mysqli_stmt_execute($stmt_1)) {
                    $fase1_ok = false;
                }
                mysqli_stmt_close($stmt_1);
            }

            if ($fase1_ok) {
                $total_siswa_pindah = 0;
                $kelas_diperbarui = 0;
                foreach ($migrasi_list as $idx => $m) {
                    $temp_token = $token_prefix . $idx;
                    $tujuan = $m["tujuan"];

                    // Pastikan kelas tujuan terdaftar di tabel `kelas`
                    $stmt_ins_k = mysqli_prepare($conn, "INSERT IGNORE INTO kelas (nama_kelas) VALUES (?)");
                    mysqli_stmt_bind_param($stmt_ins_k, "s", $tujuan);
                    mysqli_stmt_execute($stmt_ins_k);
                    mysqli_stmt_close($stmt_ins_k);

                    $stmt_2 = mysqli_prepare($conn, "UPDATE users SET kelas = ? WHERE kelas = ? AND role = 'member'");
                    mysqli_stmt_bind_param($stmt_2, "ss", $tujuan, $temp_token);
                    mysqli_stmt_execute($stmt_2);
                    $aff = mysqli_stmt_affected_rows($stmt_2);
                    mysqli_stmt_close($stmt_2);

                    $total_siswa_pindah += $aff;
                    $kelas_diperbarui++;
                }

                $msg = "Berhasil menaikkan seluruh kelas serentak! Sebanyak $total_siswa_pindah siswa dari $kelas_diperbarui kelas berhasil dipindahkan.";
                $msg_type = "success";
            } else {
                $msg = "Terjadi kendala saat memproses kenaikan kelas serentak.";
                $msg_type = "error";
            }
        }
    }
}

// ─────────────────────────────────────────────
//  AMBIL DATA KELAS
// ─────────────────────────────────────────────
$list_kelas = [];
$res_kelas = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
if ($res_kelas) {
    while ($rk = mysqli_fetch_assoc($res_kelas)) {
        $list_kelas[] = $rk;
    }
}

// Ambil daftar kelas asal beserta jumlah anggotanya
$kelas_asal_list = [];
$res_ka = mysqli_query($conn, "SELECT kelas, COUNT(*) as jml FROM users WHERE role = 'member' AND kelas IS NOT NULL AND kelas != '' GROUP BY kelas ORDER BY kelas ASC");
if ($res_ka) {
    while ($r = mysqli_fetch_assoc($res_ka)) {
        $k_name = trim($r['kelas']);
        if ($k_name !== "") {
            $kelas_asal_list[$k_name] = (int)$r['jml'];
        }
    }
}
// Sertakan pula kelas dari tabel kelas yang saat ini belum punya siswa (0 siswa)
foreach ($list_kelas as $lk) {
    $k_name = trim($lk['nama_kelas']);
    if (!isset($kelas_asal_list[$k_name])) {
        $kelas_asal_list[$k_name] = 0;
    }
}
ksort($kelas_asal_list, SORT_NATURAL | SORT_FLAG_CASE);

if (!function_exists('hitungNextKelas')) {
    function hitungNextKelas($name) {
        $trimmed = trim($name);
        if (preg_match('/(^|[\s\-_])(12|xii)($|[\s\-_])/i', $trimmed) || preg_match('/^(12|xii)/i', $trimmed)) {
            return 'Alumni';
        }
        if (preg_match('/(^|[\s\-_])11($|[\s\-_])/i', $trimmed) || preg_match('/^11/i', $trimmed)) {
            return preg_replace('/11/i', '12', $trimmed, 1);
        }
        if (preg_match('/(^|[\s\-_])xi($|[\s\-_])/i', $trimmed) || preg_match('/^xi/i', $trimmed)) {
            return preg_replace('/xi/i', 'XII', $trimmed, 1);
        }
        if (preg_match('/(^|[\s\-_])10($|[\s\-_])/i', $trimmed) || preg_match('/^10/i', $trimmed)) {
            return preg_replace('/10/i', '11', $trimmed, 1);
        }
        if (preg_match('/(^|[\s\-_])x($|[\s\-_])/i', $trimmed) || preg_match('/^x/i', $trimmed)) {
            return preg_replace('/x/i', 'XI', $trimmed, 1);
        }
        return $trimmed;
    }
}

// Ambil daftar kelas untuk kenaikan kelas serentak (semua kelas)
$semua_kelas_promo = [];
$sudah_terdata = [];

// 1. Ambil dari siswa yang ada di tabel users
$res_skp = mysqli_query($conn, "SELECT kelas, COUNT(*) as jml FROM users WHERE role = 'member' AND kelas IS NOT NULL AND kelas != '' AND LOWER(kelas) != 'alumni' GROUP BY kelas");
if ($res_skp) {
    while ($row = mysqli_fetch_assoc($res_skp)) {
        $k_asal = trim($row['kelas']);
        if ($k_asal !== "") {
            $sudah_terdata[strtolower($k_asal)] = true;
            $semua_kelas_promo[] = [
                "asal"   => $k_asal,
                "jml"    => (int)$row['jml'],
                "tujuan" => hitungNextKelas($k_asal),
            ];
        }
    }
}

// 2. Sertakan pula kelas dari tabel kelas yang saat ini belum ada siswanya
foreach ($list_kelas as $lk) {
    $k_name = trim($lk['nama_kelas']);
    if (strcasecmp($k_name, 'Alumni') !== 0 && !isset($sudah_terdata[strtolower($k_name)])) {
        $semua_kelas_promo[] = [
            "asal"   => $k_name,
            "jml"    => 0,
            "tujuan" => hitungNextKelas($k_name),
        ];
    }
}

// 3. Urutkan: Kelas 12 / XII di atas, lalu Kelas 11 / XI, lalu Kelas 10 / X
usort($semua_kelas_promo, function($a, $b) {
    $rankA = (preg_match('/(^|[\s\-_])(12|xii)($|[\s\-_])/i', $a['asal']) || preg_match('/^(12|xii)/i', $a['asal'])) ? 3
           : ((preg_match('/(^|[\s\-_])(11|xi)($|[\s\-_])/i', $a['asal']) || preg_match('/^(11|xi)/i', $a['asal'])) ? 2 : 1);
    $rankB = (preg_match('/(^|[\s\-_])(12|xii)($|[\s\-_])/i', $b['asal']) || preg_match('/^(12|xii)/i', $b['asal'])) ? 3
           : ((preg_match('/(^|[\s\-_])(11|xi)($|[\s\-_])/i', $b['asal']) || preg_match('/^(11|xi)/i', $b['asal'])) ? 2 : 1);
    if ($rankA !== $rankB) return $rankB - $rankA;
    return strcasecmp($a['asal'], $b['asal']);
});

// ─────────────────────────────────────────────
//  AMBIL DATA
// ─────────────────────────────────────────────
$search      = trim($_GET["q"] ?? "");
$is_ajax     = isset($_GET["ajax"]) && $_GET["ajax"] == "1";
$filter      = $_GET["status"] ?? "all";
$kelas_filter = trim($_GET["kelas"] ?? "");

$where = ["role = 'member'"];
if ($search !== "") {
    $s = mysqli_real_escape_string($conn, $search);
    $where[] = "(full_name LIKE '%$s%' OR nik LIKE '%$s%' OR kelas LIKE '%$s%' OR username LIKE '%$s%' OR email LIKE '%$s%' OR no_anggota LIKE '%$s%')";
}
if (in_array($filter, ["pending", "approved", "rejected"], true)) {
    $where[] = "status = '" . $filter . "'";
}
if ($kelas_filter !== "") {
    $kf = mysqli_real_escape_string($conn, $kelas_filter);
    $where[] = "kelas = '$kf'";
}
$where_sql = "WHERE " . implode(" AND ", $where);

$anggota_list = [];
$res = mysqli_query($conn, "SELECT * FROM users $where_sql ORDER BY (status='pending') DESC, id DESC");
while ($row = mysqli_fetch_assoc($res)) {
    $anggota_list[] = $row;
}
$total_anggota = count($anggota_list);

$count_res = mysqli_query($conn, "SELECT status, COUNT(*) AS c FROM users WHERE role='member' GROUP BY status");
$counts = ["pending" => 0, "approved" => 0, "rejected" => 0];
while ($row = mysqli_fetch_assoc($count_res)) {
    $counts[$row["status"]] = (int)$row["c"];
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
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Cormorant+Garamond:wght@700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --sidebar-bg:  #4a4a5a;
      --sidebar-dark:#2e2e3a;
      --accent:      #5a5a6e;
      --btn-primary: #3a3a4a;
      --text:        #1a1a2e;
      --muted:       #7a7a9a;
      --bg:          #f0f0f0;
      --card:        #ffffff;
      --radius:      10px;
      --sidebar-w:   204px;
      --trans:       .2s cubic-bezier(.22,1,.36,1);
      --shadow:      0 2px 12px rgba(0,0,0,.07);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { overflow-x: hidden; max-width: 100%; }

    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      display: flex;
      min-height: 100vh;
      animation: bodyIn .4s ease both;
    }
    @keyframes bodyIn { from { opacity:0; } to { opacity:1; } }

    /* ── SIDEBAR (identik dengan halaman_admin.php) ── */
    .sidebar {
      width: var(--sidebar-w);
      background: linear-gradient(180deg, #5a5a6e 0%, #2e2e3a 100%);
      display: flex; flex-direction: column; align-items: center;
      padding: 36px 20px 28px;
      position: fixed; top:0; left:0; bottom:0; z-index: 100;
      transition: transform var(--trans);
    }
    .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:90; }
    .sidebar-overlay.open { display:block; }
    .sidebar-toggle {
      display:none; position:fixed; top:14px; left:14px; z-index:200;
      width:40px; height:40px; border-radius:10px; border:none;
      background:#fff; box-shadow:0 2px 10px rgba(0,0,0,.15);
      cursor:pointer; align-items:center; justify-content:center;
    }
    .sidebar-toggle svg { width:20px; height:20px; }

    .avatar-wrap { position:relative; margin-bottom:14px; cursor:pointer; }
    .avatar-circle {
      width:96px; height:96px; border-radius:50%;
      background:#c0c0c8; overflow:hidden;
      border:3px solid rgba(255,255,255,.25);
      display:flex; align-items:center; justify-content:center;
      transition:border-color var(--trans);
    }
    .avatar-wrap:hover .avatar-circle { border-color:rgba(255,255,255,.55); }
    .avatar-circle img { width:100%; height:100%; object-fit:cover; display:block; }
    .avatar-circle .default-icon { width:52px; height:52px; color:#888; }
    .avatar-overlay {
      position:absolute; inset:0; border-radius:50%;
      background:rgba(0,0,0,.45); display:flex;
      align-items:center; justify-content:center;
      opacity:0; transition:opacity .2s;
    }
    .avatar-wrap:hover .avatar-overlay { opacity:1; }
    .avatar-overlay svg { width:24px; height:24px; color:#fff; }

    /* ── Modal Edit Profil ── */
    .modal-overlay {
      display:none; position:fixed; inset:0;
      background:rgba(20,20,30,.55); z-index:500;
      align-items:center; justify-content:center; padding:20px;
    }
    .modal-overlay.open { display:flex; }
    .modal-box {
      background:#fff; border-radius:16px; width:100%; max-width:380px;
      max-height:90vh; overflow-y:auto; padding:24px;
      box-shadow:0 20px 60px rgba(0,0,0,.25);
      animation:modalIn .25s cubic-bezier(.22,1,.36,1) both;
    }
    @keyframes modalIn {
      from { opacity:0; transform:scale(.94) translateY(10px); }
      to   { opacity:1; transform:scale(1) translateY(0); }
    }
    .modal-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
    .modal-title { font-family:'Cormorant Garamond',serif; font-size:1.3rem; font-weight:700; color:var(--text); }
    .modal-close { border:none; background:#f0f0f5; width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text); }
    .modal-close svg { width:16px; height:16px; }
    .img-preview-wrap { position:relative; border:2px dashed #d8d8e4; overflow:hidden; cursor:pointer; }
    .img-preview-wrap img { width:100%; height:100%; object-fit:cover; }
    .upload-placeholder { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; color:var(--muted); }
    .upload-placeholder svg { width:26px; height:26px; }
    .form-group { margin-bottom:14px; }
    .form-label { display:block; font-size:.78rem; font-weight:700; color:var(--text); margin-bottom:6px; }
    .form-input { width:100%; padding:10px 12px; border-radius:8px; border:1px solid #e0e0ea; font-family:'Nunito',sans-serif; font-size:.85rem; }
    .form-input:focus { outline:none; border-color:var(--accent); }
    .modal-footer { display:flex; gap:10px; margin-top:18px; }
    .btn-cancel, .btn-save {
      flex:1; padding:11px; border-radius:8px; border:none;
      font-family:'Nunito',sans-serif; font-size:.85rem; font-weight:700; cursor:pointer;
      transition:opacity var(--trans);
    }
    .btn-cancel:hover, .btn-save:hover { opacity:.85; }
    .btn-cancel { background:#f0f0f5; color:var(--text); }
    .btn-save { background:var(--accent); color:#fff; }

    .admin-name-label { color:#fff; font-size:.95rem; font-weight:700; margin-bottom:8px; text-align:center; }
    .total-badge {
      background: rgba(255,255,255,.18); color:#fff;
      font-size:.72rem; font-weight:700;
      padding:4px 12px; border-radius:50px;
      margin-bottom:24px; text-align:center;
    }

    .sidebar-btn {
      width:100%; display:flex; align-items:center; gap:10px;
      padding:10px 14px; border-radius:8px; border:none;
      background:rgba(255,255,255,.12); color:#fff;
      font-family:'Nunito',sans-serif; font-size:.82rem; font-weight:700;
      cursor:pointer; margin-bottom:8px;
      transition:background var(--trans);
      text-align:left; text-decoration:none;
    }
    .sidebar-btn:hover { background:rgba(255,255,255,.22); }
    .sidebar-btn.active { background:rgba(255,255,255,.3); }
    .sidebar-btn svg { width:16px; height:16px; flex-shrink:0; }

    /* ── MAIN ── */
    .main { margin-left:var(--sidebar-w); flex:1; padding:26px 24px; transition:margin-left var(--trans); }

    .alert {
      padding:12px 18px; border-radius:8px; font-size:.82rem;
      font-weight:700; margin-bottom:16px; animation:fadeUp .4s both;
    }
    .alert-success { background:#e8f5e9; color:#1a8a4a; border:1px solid #c8e6c9; }
    .alert-error   { background:#fce4ec; color:#c0392b; border:1px solid #f8bbd0; }

    /* Reset password banner */
    .reset-banner {
      background: linear-gradient(135deg, #1c1c28, #3a3a52);
      color: #fff; border-radius: 12px;
      padding: 18px 22px; margin-bottom: 20px;
      display: flex; align-items: center; justify-content: space-between;
      flex-wrap: wrap; gap: 14px;
      animation: fadeUp .4s both;
    }
    .reset-banner .rb-left { font-size: .82rem; line-height:1.7; }
    .reset-banner .rb-left b { font-size: .92rem; }
    .reset-banner .rb-cred {
      font-family: 'JetBrains Mono', monospace;
      background: rgba(255,255,255,.12);
      padding: 8px 14px; border-radius: 8px;
      font-size: .84rem; display: flex; gap: 16px; flex-wrap: wrap;
    }

    .topbar {
      display:flex; align-items:center;
      background:#fff; border-radius:50px;
      padding:0 18px; height:46px; gap:10px;
      margin-bottom:18px; box-shadow:var(--shadow);
      animation:fadeUp .5s .05s both;
    }
    .topbar svg { width:18px; height:18px; color:#aaa; flex-shrink:0; }
    .topbar input {
      flex:1; border:none; outline:none;
      font-family:'Nunito',sans-serif; font-size:.85rem;
      color:var(--text); background:transparent;
    }
    .topbar input::placeholder { color:#bbb; }
    #searchResultArea { transition: opacity .15s ease; }
    #searchResultArea.loading-search { opacity: .55; }
    .btn-search {
      background:var(--btn-primary); color:#fff;
      border:none; border-radius:20px;
      padding:6px 16px; font-family:'Nunito',sans-serif;
      font-size:.78rem; font-weight:700; cursor:pointer;
      transition:background var(--trans);
    }
    .btn-search:hover { background:#222; }

    .kelas-filter-bar {
      display:flex; align-items:center; gap:10px; margin-bottom:14px;
      animation:fadeUp .5s .07s both;
    }
    .kelas-filter-bar label {
      font-size:.76rem; font-weight:700; color:var(--muted);
    }
    .kelas-filter-bar select {
      font-family:'Nunito',sans-serif; font-size:.82rem; color:var(--text);
      background:#fff; border:1px solid #e0e0ea; border-radius:8px;
      padding:7px 12px; cursor:pointer; box-shadow:var(--shadow);
    }
    .kelas-filter-bar select:focus { outline:none; border-color:var(--accent); }

    .content-header {
      display:flex; align-items:center; justify-content:space-between;
      margin-bottom:14px; animation:fadeUp .5s .08s both; flex-wrap:wrap; gap:10px;
    }
    .content-title { font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:700; color:var(--text); }
    .btn-manage-kelas {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 7px 15px; border-radius: 8px; font-size: .78rem; font-weight: 700;
      font-family: 'Nunito', sans-serif; cursor: pointer;
      color: #fff; background: var(--btn-primary);
      border: 1px solid rgba(0,0,0,.08); box-shadow: var(--shadow);
      transition: all var(--trans);
    }
    .btn-manage-kelas:hover {
      transform: translateY(-1px);
      background: #2c2c38;
      box-shadow: 0 6px 18px rgba(0,0,0,.18);
    }
    .btn-naik-kelas {
      background: linear-gradient(135deg, #10b981, #059669) !important;
      color: #ffffff !important;
      border: 1px solid rgba(16,185,129,.4) !important;
      box-shadow: 0 4px 14px rgba(16,185,129,.25) !important;
    }
    .btn-naik-kelas:hover {
      background: linear-gradient(135deg, #059669, #047857) !important;
      box-shadow: 0 6px 18px rgba(16,185,129,.35) !important;
      color: #ffffff !important;
    }
    .badge-alumni {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 3px 10px;
      border-radius: 6px;
      font-size: .72rem;
      font-weight: 700;
      background: rgba(16,185,129,.15);
      color: #34d399;
      border: 1px solid rgba(16,185,129,.3);
    }
    .tab-naik-btn {
      flex: 1;
      padding: 10px 14px;
      border-radius: 8px;
      font-size: .8rem;
      font-weight: 700;
      font-family: 'Nunito', sans-serif;
      cursor: pointer;
      border: 1px solid var(--border-color, rgba(216,184,120,.18));
      background: rgba(255,255,255,.04);
      color: var(--muted);
      transition: all var(--trans);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }
    .tab-naik-btn:hover {
      color: var(--text);
      background: rgba(255,255,255,.08);
    }
    .tab-naik-btn.active {
      background: linear-gradient(135deg, rgba(16,185,129,.22), rgba(16,185,129,.08));
      color: #34d399;
      border-color: rgba(16,185,129,.45);
      box-shadow: 0 2px 10px rgba(16,185,129,.15);
    }
    .promo-row {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #f4f4f8;
      border: 1px solid rgba(90,90,110,.15);
      padding: 9px 12px;
      border-radius: 8px;
      margin-bottom: 7px;
      transition: all var(--trans);
    }
    .promo-row:hover {
      border-color: rgba(90,90,110,.3);
      background: #ececf2;
    }
    .promo-row.disabled {
      opacity: .4;
      background: #e4e4ea;
    }
    .promo-badge-count {
      display: inline-block;
      padding: 2px 7px;
      border-radius: 12px;
      font-size: .7rem;
      font-weight: 700;
      background: rgba(216,184,120,.12);
      color: var(--accent, #d8b878);
      white-space: nowrap;
    }

    .status-tabs { display:flex; gap:8px; margin-bottom:18px; flex-wrap:wrap; animation:fadeUp .5s .1s both; }
    .status-tab {
      padding:7px 16px; border-radius:50px; font-size:.78rem; font-weight:700;
      text-decoration:none; color:var(--muted); background:#fff; box-shadow:var(--shadow);
      transition:all var(--trans);
    }
    .status-tab .count { opacity:.7; margin-left:4px; }
    .status-tab.active { background:var(--btn-primary); color:#fff; }
    .status-tab:hover:not(.active) { color:var(--text); }

    /* Table */
    .table-wrap {
      background:var(--card); border-radius:var(--radius);
      box-shadow:var(--shadow); overflow-x:auto;
      animation:fadeUp .5s .14s both;
    }
    table { width:100%; border-collapse:collapse; min-width:820px; }
    thead th {
      text-align:left; font-size:.68rem; text-transform:uppercase; letter-spacing:.06em;
      color:var(--muted); font-weight:800; padding:12px 16px;
      border-bottom:1.5px solid #eee; white-space:nowrap;
    }
    tbody td {
      padding:12px 16px; font-size:.82rem; color:var(--text);
      border-bottom:1px solid #f2f2f6; vertical-align:middle;
    }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover { background:#fafafe; }
    .cell-name { font-weight:700; }
    .cell-sub  { font-size:.7rem; color:var(--muted); }
    .cell-mono { font-family:'JetBrains Mono', monospace; font-size:.76rem; }

    .cell-anggota { display:flex; align-items:center; gap:10px; }
    .member-avatar {
      width:38px; height:38px; border-radius:50%; flex-shrink:0;
      overflow:hidden; background:linear-gradient(135deg,#3498db,#1a5276);
      color:#fff; font-weight:800; font-size:.82rem;
      display:flex; align-items:center; justify-content:center;
    }
    .member-avatar img { width:100%; height:100%; object-fit:cover; display:block; }

    .badge {
      display:inline-block; padding:3px 11px; border-radius:20px;
      font-size:.68rem; font-weight:800; white-space:nowrap;
    }
    .badge-pending  { background:#fff3cd; color:#8a6100; }
    .badge-approved { background:#e8f5e9; color:#1a8a4a; }
    .badge-rejected { background:#fce4ec; color:#c0392b; }
    .badge-frozen   { background:#e0e7ff; color:#3730a3; margin-left:6px; }

    .row-actions { display:flex; gap:6px; flex-wrap:wrap; }
    .act-btn {
      border:none; border-radius:6px; padding:6px 12px;
      font-family:'Nunito',sans-serif; font-size:.7rem; font-weight:800;
      cursor:pointer; transition:opacity var(--trans), transform .12s; white-space:nowrap;
    }
    .act-btn:hover { opacity:.85; }
    .act-btn:active { transform:scale(.95); }
    .act-approve { background:#1a8a4a; color:#fff; }
    .act-reject  { background:#e67e22; color:#fff; }
    .act-reset   { background:var(--btn-primary); color:#fff; }
    .act-hapus   { background:#e74c3c; color:#fff; }

    .empty-state {
      text-align:center; padding:60px 20px; color:var(--muted);
    }
    .empty-state svg { width:56px; height:56px; margin-bottom:12px; opacity:.35; }
    .empty-state p { font-size:.88rem; font-weight:600; }

    @keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:translateY(0); } }

    @media (max-width:860px) {
      :root { --sidebar-w:170px; }
      .avatar-circle { width:76px; height:76px; }
    }
    @media (max-width:620px) {
      .sidebar { transform:translateX(-100%); width:220px; }
      .sidebar.open { transform:translateX(0); }
      .sidebar-toggle { display:flex; }
      .main { margin-left:0; padding:70px 14px 24px; max-width:100vw; }

      /* Search bar: full width, rapi menumpuk kalau ada tombol reset */
      .topbar {
        border-radius:14px; height:auto; padding:10px 14px;
        flex-wrap:wrap; row-gap:6px;
      }
      .topbar input { min-width:0; }
      #searchResetBtn { width:100%; text-align:right; }

      /* Filter kelas: label di atas, dropdown full width */
      .kelas-filter-bar {
        flex-direction:column; align-items:stretch; gap:6px;
      }
      .kelas-filter-bar select { width:100%; }

      /* Header konten: judul di atas, tombol kelola kelas full width di bawah */
      .content-header { flex-direction:column; align-items:stretch; }
      .content-title { font-size:1.35rem; }
      .btn-manage-kelas { width:100%; justify-content:center; }

      /* Tab status: scroll horizontal HANYA di dalam baris tab ini, bukan seluruh halaman */
      .status-tabs {
        flex-wrap:nowrap; overflow-x:auto; -webkit-overflow-scrolling:touch;
        padding-bottom:4px; scrollbar-width:none;
      }
      .status-tabs::-webkit-scrollbar { display:none; }
      .status-tab { flex-shrink:0; }

      .reset-banner { flex-direction:column; align-items:stretch; }
      .reset-banner .rb-cred { word-break:break-all; }

      /* Tabel anggota jadi kartu bertumpuk di layar kecil, biar tombol aksi
         langsung kelihatan tanpa perlu geser ke samping */
      .table-wrap { overflow-x:visible; box-shadow:none; background:transparent; }
      table { min-width:0; width:100%; border-collapse:separate; border-spacing:0 16px; }
      thead { display:none; }
      tbody tr {
        display:block; background:var(--card); border-radius:var(--radius);
        box-shadow:var(--shadow); overflow:hidden;
      }
      tbody tr:hover { background:var(--card); }
      tbody td {
        display:flex; align-items:flex-start; justify-content:space-between; gap:10px;
        padding:12px 14px; border-bottom:1px solid #f2f2f6; text-align:right;
      }
      tbody tr td:last-child { border-bottom:none; }
      tbody td::before {
        content:attr(data-label); font-size:.68rem; font-weight:800; color:var(--muted);
        text-transform:uppercase; letter-spacing:.05em; text-align:left; flex-shrink:0;
        padding-top:2px;
      }
      /* Nilai sel dibungkus rapi, tidak memicu geser ke samping walau teksnya panjang
         (email, username, no. anggota) */
      tbody td > *:not(.cell-anggota):not(.row-actions) { min-width:0; }
      tbody td:not([data-label="Anggota"]):not([data-label="Aksi"]) {
        word-break:break-word; overflow-wrap:anywhere;
      }
      .cell-mono { word-break:break-all; text-align:right; }

      tbody td[data-label="Anggota"] { flex-direction:column; align-items:flex-start; text-align:left; }
      tbody td[data-label="Anggota"]::before { margin-bottom:6px; }
      .cell-anggota { width:100%; }
      .cell-anggota > div { min-width:0; }
      .cell-name, .cell-sub { word-break:break-word; overflow-wrap:anywhere; }

      /* Status: badge dibungkus rapi, tidak mepet ke label */
      tbody td[data-label="Status"] { flex-wrap:wrap; justify-content:flex-end; }
      .badge-frozen { white-space:normal; text-align:right; }

      /* Aksi: tombol ditumpuk vertikal full-width, jelas dan mudah diketuk */
      tbody td[data-label="Aksi"] { flex-direction:column; align-items:stretch; }
      tbody td[data-label="Aksi"]::before { margin-bottom:8px; }
      .row-actions { width:100%; flex-direction:column; }
      .act-btn { width:100%; padding:10px 12px; font-size:.76rem; white-space:normal; }
    }
    @media (max-width:380px) {
      .content-title { font-size:1.2rem; }
      .reset-banner .rb-cred { font-size:.76rem; gap:10px; }
    }
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
  <a class="sidebar-btn active" href="daftar_anggota.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    Daftar Anggota
    <?php if ($counts["pending"] > 0): ?>
      <span style="margin-left:auto;background:#e74c3c;color:#fff;font-size:.65rem;font-weight:800;padding:2px 7px;border-radius:20px;"><?= $counts["pending"] ?></span>
    <?php endif; ?>
  </a>
  <a class="sidebar-btn" href="pinjam_buku.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
    </svg>
    Pinjam Buku
  </a>
  <?php
    $cnt_aju_badge = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'"))['c'] ?? 0);
  ?>
  <a class="sidebar-btn" href="pengajuan_buku.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
    Pengajuan Buku
    <?php if ($cnt_aju_badge > 0): ?>
      <span style="margin-left:auto;background:rgba(245,158,11,.2);color:#fbbf24;border:1px solid rgba(245,158,11,.4);font-size:.65rem;font-weight:800;padding:2px 7px;border-radius:20px;"><?= $cnt_aju_badge ?></span>
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
  <a class="sidebar-btn" href="beranda.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Kembali
  </a>
</aside>

<main class="main">

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type ?>">
    <?= $msg_type === "success" ? "✅" : "❌" ?> <?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>

  <?php if ($reset_info): ?>
  <div class="reset-banner">
    <div class="rb-left">
      Sandi baru untuk <b><?= htmlspecialchars($reset_info["full_name"]) ?></b> berhasil dibuat.<br>
      Sampaikan ke anggota — sandi ini hanya tampil sekali di sini.
    </div>
    <div class="rb-cred">
      <span>👤 <?= htmlspecialchars($reset_info["username"]) ?></span>
      <span>🔑 <?= htmlspecialchars($reset_info["password"]) ?></span>
    </div>
  </div>
  <?php endif; ?>

  <form method="GET" action="" id="searchForm">
    <?php if ($filter !== "all"): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filter) ?>"><?php endif; ?>
    <div class="topbar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" name="q" id="searchInput" autocomplete="off" placeholder="Cari anggota berdasarkan nama, kelas, username, atau email…"
             value="<?= htmlspecialchars($search) ?>"/>
      <?php if ($search): ?>
      <?php
        $reset_qs = [];
        if ($filter !== 'all') $reset_qs[] = 'status=' . urlencode($filter);
        if ($kelas_filter !== '') $reset_qs[] = 'kelas=' . urlencode($kelas_filter);
      ?>
      <a href="daftar_anggota.php<?= $reset_qs ? '?' . implode('&', $reset_qs) : '' ?>" id="searchResetBtn" style="font-size:.75rem;color:var(--muted);text-decoration:none;white-space:nowrap;">✕ Reset</a>
      <?php endif; ?>
    </div>
  </form>

  <div class="kelas-filter-bar">
    <label for="kelasFilterSelect">Filter Kelas</label>
    <select id="kelasFilterSelect">
      <option value="">Semua Kelas</option>
      <?php foreach ($list_kelas as $k): ?>
        <option value="<?= htmlspecialchars($k['nama_kelas']) ?>" <?= $kelas_filter === $k['nama_kelas'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($k['nama_kelas']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div id="searchResultArea">
  <?php ob_start(); ?>
  <div class="content-header">
    <div class="content-title">
      Daftar Anggota
      <?php if ($search): ?><span style="font-size:.9rem;color:var(--muted);font-family:'Nunito',sans-serif;"> — hasil: "<?= htmlspecialchars($search) ?>"</span><?php endif; ?>
    </div>
    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
      <button type="button" class="btn-manage-kelas btn-naik-kelas" onclick="openNaikKelasModal()">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="17 11 12 6 7 11"></polyline>
          <polyline points="17 18 12 13 7 18"></polyline>
        </svg>
        Naikkan Kelas Masal
      </button>
      <button type="button" class="btn-manage-kelas" onclick="openKelasModal()">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
          <path d="M6 6h10M6 10h10"/>
        </svg>
        Kelola Pilihan Kelas (<?= count($list_kelas) ?>)
      </button>
    </div>
  </div>

  <div class="status-tabs">
    <?php
      $qstr = $search !== "" ? "&q=" . urlencode($search) : "";
      $qstr .= $kelas_filter !== "" ? "&kelas=" . urlencode($kelas_filter) : "";
      $tabs = [
        "all"      => "Semua (" . array_sum($counts) . ")",
        "approved" => "Aktif (" . $counts["approved"] . ")",
        "rejected" => "Nonaktif (" . $counts["rejected"] . ")",
      ];
      // Tab "Menunggu" hanya ditampilkan jika masih ada data lama berstatus pending
      // (akun baru sekarang langsung aktif tanpa perlu persetujuan admin).
      if ($counts["pending"] > 0) {
          $tabs = ["all" => $tabs["all"]] + ["pending" => "Menunggu (" . $counts["pending"] . ")"] + array_slice($tabs, 1, null, true);
      }
      foreach ($tabs as $key => $label):
        $active = $filter === $key ? "active" : "";
    ?>
      <a class="status-tab <?= $active ?>" href="daftar_anggota.php?status=<?= $key ?><?= $qstr ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($anggota_list)): ?>
  <div class="empty-state">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
    </svg>
    <p>Belum ada anggota<?= $search ? " yang cocok dengan pencarian." : " di kategori ini." ?></p>
  </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Anggota</th>
          <th>Kelas</th>
          <th>No. Anggota</th>
          <th>Username</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($anggota_list as $a): ?>
        <tr>
          <td data-label="Anggota">
            <div class="cell-anggota">
              <div class="member-avatar">
                <?php $foto_anggota = $a["foto"] ?? ""; ?>
                <?php if ($foto_anggota !== "" && file_exists($foto_anggota)): ?>
                  <img src="<?= htmlspecialchars($foto_anggota) ?>" alt="Foto <?= htmlspecialchars($a["full_name"]) ?>">
                <?php else: ?>
                  <?= htmlspecialchars(mb_strtoupper(mb_substr($a["full_name"], 0, 1))) ?>
                <?php endif; ?>
              </div>
              <div>
                <div class="cell-name"><?= htmlspecialchars($a["full_name"]) ?></div>
                <div class="cell-sub"><?= htmlspecialchars($a["email"]) ?></div>
              </div>
            </div>
          </td>
          <td data-label="Kelas">
            <?php if (strcasecmp($a["kelas"], "Alumni") === 0): ?>
              <span class="badge-alumni">🎓 Alumni</span>
            <?php else: ?>
              <?= htmlspecialchars($a["kelas"]) ?>
            <?php endif; ?>
          </td>
          <td class="cell-mono" data-label="No. Anggota"><?= htmlspecialchars($a["no_anggota"]) ?></td>
          <td class="cell-mono" data-label="Username"><?= htmlspecialchars($a["username"]) ?></td>
          <td data-label="Status">
            <?php if ($a["status"] === "pending"): ?>
              <span class="badge badge-pending">Menunggu</span>
            <?php elseif ($a["status"] === "approved"): ?>
              <span class="badge badge-approved">Aktif</span>
            <?php else: ?>
              <span class="badge badge-rejected">Nonaktif</span>
            <?php endif; ?>
            <?php if (($a["card_status"] ?? "active") === "frozen"): ?>
              <span class="badge badge-frozen">🔒 Dibekukan (proses Lupa Kartu)</span>
            <?php endif; ?>
          </td>
          <td data-label="Aksi">
            <div class="row-actions">
              <?php if ($a["status"] === "pending"): ?>
                <button class="act-btn act-approve" onclick="kirimAksi('approve', <?= $a['id'] ?>)">Aktifkan</button>
                <button class="act-btn act-reject" onclick="kirimAksi('reject', <?= $a['id'] ?>)">Tolak</button>
              <?php elseif ($a["status"] === "approved"): ?>
                <button class="act-btn act-reject" onclick="konfirmasiNonaktifkan(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['full_name'])) ?>')">Nonaktifkan</button>
                <button class="act-btn act-reset" onclick="konfirmasiReset(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['full_name'])) ?>')">Ubah Sandi</button>
              <?php else: ?>
                <button class="act-btn act-approve" onclick="kirimAksi('approve', <?= $a['id'] ?>)">Aktifkan Kembali</button>
              <?php endif; ?>
              <?php if (($a["card_status"] ?? "active") === "frozen"): ?>
                <button class="act-btn act-reset" onclick="konfirmasiCairkan(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['full_name'])) ?>')">Cairkan Kartu</button>
              <?php endif; ?>
              <button class="act-btn act-hapus" onclick="konfirmasiHapusAnggota(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['full_name'])) ?>')">Hapus</button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
  <?php
  $search_result_html = ob_get_clean();
  if ($is_ajax) {
      ob_end_clean();
      header("Content-Type: text/html; charset=utf-8");
      echo $search_result_html;
      exit;
  }
  echo $search_result_html;
  ?>
  </div>

</main>

<!-- Form aksi tersembunyi -->
<form method="POST" id="formAksi" style="display:none">
  <input type="hidden" name="action" id="aksiAction"/>
  <input type="hidden" name="id" id="aksiId"/>
</form>

<!-- ═══════════ MODAL EDIT PROFIL ADMIN ═══════════ -->
<div class="modal-overlay" id="profilModalOverlay">
  <div class="modal-box" style="max-width:380px;">
    <div class="modal-header">
      <div class="modal-title">Edit Profil</div>
      <button class="modal-close" onclick="closeProfilModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="POST" action="update_profil_admin.php" enctype="multipart/form-data">
      <input type="hidden" name="redirect" value="daftar_anggota.php"/>

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
        <button type="button" class="btn-cancel" onclick="closeProfilModal()">Batal</button>
        <button type="submit" class="btn-save">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══════════ MODAL KELOLA PILIHAN KELAS ═══════════ -->
<div class="modal-overlay" id="kelasModalOverlay">
  <div class="modal-box" style="max-width:480px; width:92%;">
    <div class="modal-header">
      <div class="modal-title" style="display:flex;align-items:center;gap:8px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--accent);">
          <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
          <path d="M6 6h10M6 10h10"/>
        </svg>
        Kelola Pilihan Kelas
      </div>
      <button type="button" class="modal-close" onclick="closeKelasModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <p style="font-size:.8rem; color:var(--muted); margin-bottom:16px; line-height:1.5;">
      Setiap kelas yang dibuat di sini akan otomatis muncul sebagai opsi pilihan (dropdown) pada form <b>Sign Up</b> siswa baru.
    </p>

    <!-- Form Tambah Kelas -->
    <form method="POST" action="daftar_anggota.php" style="display:flex; gap:8px; margin-bottom:18px;">
      <input type="hidden" name="action" value="tambah_kelas"/>
      <input class="form-input" type="text" name="nama_kelas" placeholder="Ketik nama kelas baru (contoh: XII RPL 1)" required style="flex:1; padding:10px 12px; font-size:.84rem;"/>
      <button type="submit" class="btn-save" style="width:auto; padding:10px 18px; font-size:.82rem; white-space:nowrap; flex-shrink:0;">
        + Tambah
      </button>
    </form>

    <!-- Daftar Kelas -->
    <div style="font-size:.76rem; font-weight:800; color:var(--text); margin-bottom:10px; text-transform:uppercase; letter-spacing:.05em; display:flex; justify-content:space-between; align-items:center;">
      <span>Daftar Kelas Aktif</span>
      <span style="color:var(--accent); font-size:.74rem;"><?= count($list_kelas) ?> kelas terdaftar</span>
    </div>

    <div style="max-height:260px; overflow-y:auto; display:flex; flex-direction:column; gap:7px; padding-right:4px;">
      <?php if (empty($list_kelas)): ?>
        <div style="text-align:center; color:var(--muted); font-size:.82rem; padding:24px 0; background:rgba(90,90,110,.05); border-radius:8px; border:1px dashed rgba(90,90,110,.25);">
          Belum ada data kelas. Tambahkan kelas pertama di atas.
        </div>
      <?php else: ?>
        <?php foreach ($list_kelas as $k): ?>
          <div style="display:flex; align-items:center; justify-content:space-between; background:var(--sidebar-dark); border:1px solid rgba(255,255,255,.1); padding:8px 12px; border-radius:8px;">
            <span style="font-size:.84rem; font-weight:600; color:#f0f0f5;"><?= htmlspecialchars($k['nama_kelas']) ?></span>
            <button type="button" class="act-btn act-hapus" style="padding:4px 10px; font-size:.7rem; border-radius:6px;"
                    onclick="konfirmasiHapusKelas(<?= $k['id'] ?>, '<?= htmlspecialchars(addslashes($k['nama_kelas'])) ?>')">
              Hapus
            </button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="modal-footer" style="margin-top:16px;">
      <button type="button" class="btn-cancel" onclick="closeKelasModal()">Tutup</button>
    </div>
  </div>
</div>

<!-- ═══════════ MODAL NAIKKAN KELAS MASAL ═══════════ -->
<div class="modal-overlay" id="naikKelasModalOverlay">
  <div class="modal-box" style="max-width:620px; width:94%;">
    <div class="modal-header">
      <div class="modal-title" style="display:flex;align-items:center;gap:8px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="17 11 12 6 7 11"></polyline>
          <polyline points="17 18 12 13 7 18"></polyline>
        </svg>
        Kenaikan Kelas Siswa
      </div>
      <button type="button" class="modal-close" onclick="closeNaikKelasModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <!-- Navigation Tabs -->
    <div style="display:flex; gap:8px; margin-bottom:14px; border-bottom:1px solid rgba(90,90,110,.15); padding-bottom:12px;">
      <button type="button" class="tab-naik-btn active" id="tabBtnSemua" onclick="switchNaikTab('semua')">
        ⚡ Naikkan Semua Kelas Sekaligus
      </button>
      <button type="button" class="tab-naik-btn" id="tabBtnManual" onclick="switchNaikTab('manual')">
        🎯 Pindah Per Satu Kelas
      </button>
    </div>

    <!-- PANEL 1: NAIKKAN SEMUA KELAS SEKALIGUS -->
    <div id="panelTabSemua">
      <p style="font-size:.8rem; color:var(--muted); margin-bottom:12px; line-height:1.5;">
        Kenaikan masal seluruh kelas secara serentak (Akhir Tahun Ajaran). Sistem otomatis mendeteksi pola kelas: <b>10 ➔ 11</b>, <b>11 ➔ 12</b>, dan <b>12 / XII ➔ Alumni</b>.
      </p>

      <?php if (empty($semua_kelas_promo)): ?>
        <div style="background:rgba(216,184,120,.06); border:1px solid rgba(90,90,110,.15); border-radius:10px; padding:24px; text-align:center; color:var(--muted); font-size:.84rem; margin-bottom:14px;">
          Tidak ada kelas aktif yang memiliki siswa (selain Alumni).
        </div>
        <div class="modal-footer" style="margin-top:0;">
          <button type="button" class="btn-cancel" onclick="closeNaikKelasModal()">Tutup</button>
        </div>
      <?php else: ?>
        <form method="POST" action="daftar_anggota.php" id="formNaikSemua" onsubmit="return handleKonfirmasiSemua(event)">
          <input type="hidden" name="action" value="naik_semua_kelas"/>

          <!-- Toolbar: Select All & Total Info -->
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; font-size:.78rem; padding:0 2px;">
            <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-weight:700; color:var(--text); margin:0;">
              <input type="checkbox" id="chkSemuaMaster" checked onchange="toggleCheckAllSemua(this)" style="cursor:pointer; accent-color:#10b981; width:15px; height:15px;">
              Pilih Semua Kelas (<?= count($semua_kelas_promo) ?> kelas)
            </label>
            <span style="color:var(--muted); font-size:.72rem;">Bisa sesuaikan tujuan bila perlu</span>
          </div>

          <!-- List Kelas Scrollable -->
          <div style="max-height:280px; overflow-y:auto; border:1px solid rgba(90,90,110,.15); border-radius:10px; padding:8px; background:rgba(0,0,0,.15); margin-bottom:12px;">
            <?php foreach ($semua_kelas_promo as $idx => $item): ?>
              <div class="promo-row" id="row_promo_<?= $idx ?>">
                <div style="display:flex; align-items:center; gap:8px; min-width:140px; flex:1;">
                  <input type="checkbox" name="pilih_kelas[]" value="<?= htmlspecialchars($item['asal']) ?>" checked id="chk_promo_<?= $idx ?>" onchange="handleRowCheckboxChange(<?= $idx ?>)" style="cursor:pointer; width:16px; height:16px; accent-color:#10b981;">
                  <label for="chk_promo_<?= $idx ?>" style="cursor:pointer; font-size:.82rem; font-weight:700; color:var(--text); margin:0;">
                    <?= htmlspecialchars($item['asal']) ?>
                  </label>
                  <span class="promo-badge-count" data-count="<?= $item['jml'] ?>"><?= $item['jml'] ?> siswa</span>
                </div>

                <div style="display:flex; align-items:center; gap:6px; flex:1.2;">
                  <span style="color:var(--accent); font-size:.85rem; font-weight:bold;">➔</span>
                  <select name="target_kelas[<?= htmlspecialchars($item['asal']) ?>]" id="target_promo_<?= $idx ?>" class="form-input" style="padding:5px 8px; font-size:.78rem; height:32px; cursor:pointer; margin-bottom:0;" onchange="hitungTotalSemua()">
                    <option value="Alumni" <?= strcasecmp($item['tujuan'], 'Alumni') === 0 ? 'selected' : '' ?>>🎓 Alumni (Lulus)</option>
                    <optgroup label="Pilihan Kelas Lanjutan">
                      <?php
                      $found_in_list = false;
                      foreach ($list_kelas as $k) {
                          if (strcasecmp($k['nama_kelas'], $item['tujuan']) === 0) { $found_in_list = true; break; }
                      }
                      if (!$found_in_list && strcasecmp($item['tujuan'], 'Alumni') !== 0 && $item['tujuan'] !== '') {
                          echo '<option value="' . htmlspecialchars($item['tujuan']) . '" selected>✨ ' . htmlspecialchars($item['tujuan']) . ' (Baru)</option>';
                      }
                      foreach ($list_kelas as $k):
                          if (strcasecmp($k['nama_kelas'], 'Alumni') === 0) continue;
                          $sel = (strcasecmp($k['nama_kelas'], $item['tujuan']) === 0) ? 'selected' : '';
                      ?>
                        <option value="<?= htmlspecialchars($k['nama_kelas']) ?>" <?= $sel ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                      <?php endforeach; ?>
                    </optgroup>
                  </select>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Counter Summary Card -->
          <div style="background:rgba(216,184,120,.06); border:1px solid rgba(90,90,110,.15); border-radius:8px; padding:9px 14px; margin-bottom:14px; font-size:.78rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
            <div>
              Total Kelas: <b id="sumTotalKelas" style="color:var(--text);">0</b>
              <span style="color:var(--muted); margin:0 4px;">|</span>
              Total Siswa: <b id="sumTotalSiswa" style="color:var(--accent);">0</b>
            </div>
            <div>
              Lulus Jadi Alumni: <b id="sumTotalAlumni" style="color:#34d399;">0</b>
            </div>
          </div>

          <div class="modal-footer" style="margin-top:0;">
            <button type="button" class="btn-cancel" onclick="closeNaikKelasModal()">Batal</button>
            <button type="submit" class="btn-save btn-naik-kelas" id="btnSubmitSemua" style="flex:1.5;">
              ⚡ Naikkan Semua Kelas Terpilih
            </button>
          </div>
        </form>
      <?php endif; ?>
    </div>

    <!-- PANEL 2: PINDAH PER SATU KELAS (MANUAL) -->
    <div id="panelTabManual" style="display:none;">
      <p style="font-size:.8rem; color:var(--muted); margin-bottom:14px; line-height:1.5;">
        Pindahkan seluruh anggota dari satu kelas tertentu ke kelas tujuan. Cocok untuk penyesuaian khusus atau mutasi satu rombel.
      </p>

      <form method="POST" action="daftar_anggota.php" id="formNaikKelas" onsubmit="return handleKonfirmasiNaikKelas(event)">
        <input type="hidden" name="action" value="naik_kelas_masal"/>

        <!-- Kelas Asal -->
        <div class="form-group" style="margin-bottom:14px;">
          <label class="form-label">Pilih Kelas Asal</label>
          <select class="form-input" name="kelas_asal" id="selectKelasAsal" required onchange="handleKelasAsalChange()" style="cursor:pointer;">
            <option value="" disabled selected>-- Pilih Kelas Asal --</option>
            <?php foreach ($kelas_asal_list as $ka_name => $ka_jml): ?>
              <option value="<?= htmlspecialchars($ka_name) ?>" data-count="<?= $ka_jml ?>">
                <?= htmlspecialchars($ka_name) ?> (<?= $ka_jml ?> siswa)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Arrow indicator -->
        <div style="display:flex; justify-content:center; align-items:center; gap:8px; margin:4px 0 14px; color:var(--accent);">
          <div style="height:1px; flex:1; background:rgba(216,184,120,.2);"></div>
          <span style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; display:flex; align-items:center; gap:4px; color:var(--muted);">
            Pindahkan Ke
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
          </span>
          <div style="height:1px; flex:1; background:rgba(216,184,120,.2);"></div>
        </div>

        <!-- Kelas Tujuan -->
        <div class="form-group" style="margin-bottom:16px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <label class="form-label" style="margin-bottom:0;">Pilih Kelas Baru / Tujuan</label>
            <button type="button" onclick="setTujuanAlumni()" style="background:none; border:none; color:#10b981; font-size:.72rem; font-weight:700; cursor:pointer; text-decoration:underline;">
              🎓 Set Jadi Alumni
            </button>
          </div>
          <select class="form-input" name="kelas_tujuan" id="selectKelasTujuan" required onchange="updateNaikSummary()" style="cursor:pointer;">
            <option value="" disabled selected>-- Pilih Kelas Tujuan --</option>
            <optgroup label="Khusus Kelulusan">
              <option value="Alumni">🎓 Alumni (Lulus Sekolah)</option>
            </optgroup>
            <optgroup label="Daftar Kelas Tingkat Lanjutan">
              <?php foreach ($list_kelas as $k): ?>
                <?php if (strcasecmp($k['nama_kelas'], 'Alumni') !== 0): ?>
                  <option value="<?= htmlspecialchars($k['nama_kelas']) ?>">
                    <?= htmlspecialchars($k['nama_kelas']) ?>
                  </option>
                <?php endif; ?>
              <?php endforeach; ?>
            </optgroup>
          </select>
        </div>

        <!-- Live Summary Preview Box -->
        <div id="naikKelasPreview" style="background:rgba(216,184,120,.06); border:1px solid rgba(90,90,110,.15); border-radius:10px; padding:12px 14px; margin-bottom:18px; font-size:.8rem; line-height:1.5;">
          <div style="font-weight:700; color:var(--text); margin-bottom:4px; display:flex; align-items:center; gap:6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            Ringkasan Pemindahan:
          </div>
          <div id="naikKelasPreviewText" style="color:var(--muted);">
            Silakan pilih kelas asal dan kelas tujuan di atas untuk melihat pratinjau.
          </div>
        </div>

        <div class="modal-footer" style="margin-top:0;">
          <button type="button" class="btn-cancel" onclick="closeNaikKelasModal()">Batal</button>
          <button type="submit" class="btn-save btn-naik-kelas" id="btnSubmitNaikKelas" style="flex:1.5;">
            🚀 Pindahkan Kelas
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

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

function kirimAksi(action, id) {
  document.getElementById('aksiAction').value = action;
  document.getElementById('aksiId').value = id;
  document.getElementById('formAksi').submit();
}

function konfirmasiReset(id, nama) {
  if (confirm(`Ubah sandi untuk "${nama}"? Sandi lama akan langsung tidak berlaku.`)) {
    kirimAksi('reset_password', id);
  }
}

function konfirmasiCairkan(id, nama) {
  if (confirm(`Cairkan kartu "${nama}"? Gunakan ini hanya jika anggota meninggalkan proses "Lupa Kartu" di tengah jalan tanpa selesai.`)) {
    kirimAksi('unfreeze', id);
  }
}

function konfirmasiNonaktifkan(id, nama) {
  if (confirm(`Nonaktifkan akun "${nama}"? Anggota tidak akan bisa login sampai diaktifkan kembali oleh admin.`)) {
    kirimAksi('reject', id);
  }
}

function konfirmasiHapusAnggota(id, nama) {
  if (confirm(`Hapus anggota "${nama}"? Tindakan ini tidak bisa dibatalkan.`)) {
    kirimAksi('hapus', id);
  }
}

// ─── Modal Kelola Pilihan Kelas ───
function openKelasModal() {
  document.getElementById('kelasModalOverlay').classList.add('open');
}
function closeKelasModal() {
  document.getElementById('kelasModalOverlay').classList.remove('open');
}
document.getElementById('kelasModalOverlay')?.addEventListener('click', function(e) {
  if (e.target === this) closeKelasModal();
});
function konfirmasiHapusKelas(id, nama) {
  if (confirm(`Hapus kelas "${nama}" dari daftar pilihan Sign Up?`)) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'daftar_anggota.php';
    const act = document.createElement('input');
    act.type = 'hidden'; act.name = 'action'; act.value = 'hapus_kelas';
    const idF = document.createElement('input');
    idF.type = 'hidden'; idF.name = 'id_kelas'; idF.value = id;
    form.appendChild(act);
    form.appendChild(idF);
    document.body.appendChild(form);
    form.submit();
  }
}

// ─── Modal Naikkan Kelas Masal ───
function openNaikKelasModal() {
  document.getElementById('naikKelasModalOverlay').classList.add('open');
  switchNaikTab('semua');
}
function closeNaikKelasModal() {
  document.getElementById('naikKelasModalOverlay').classList.remove('open');
}
document.getElementById('naikKelasModalOverlay')?.addEventListener('click', function(e) {
  if (e.target === this) closeNaikKelasModal();
});

function switchNaikTab(tab) {
  const tabSemua = document.getElementById('tabBtnSemua');
  const tabManual = document.getElementById('tabBtnManual');
  const panelSemua = document.getElementById('panelTabSemua');
  const panelManual = document.getElementById('panelTabManual');
  if (!tabSemua || !tabManual || !panelSemua || !panelManual) return;

  if (tab === 'semua') {
    tabSemua.classList.add('active');
    tabManual.classList.remove('active');
    panelSemua.style.display = 'block';
    panelManual.style.display = 'none';
    hitungTotalSemua();
  } else {
    tabManual.classList.add('active');
    tabSemua.classList.remove('active');
    panelManual.style.display = 'block';
    panelSemua.style.display = 'none';
    updateNaikSummary();
  }
}

function toggleCheckAllSemua(masterChk) {
  const checkboxes = document.querySelectorAll('#panelTabSemua input[name="pilih_kelas[]"]');
  checkboxes.forEach((chk) => {
    chk.checked = masterChk.checked;
    const row = chk.closest('.promo-row');
    if (row) {
      if (chk.checked) row.classList.remove('disabled');
      else row.classList.add('disabled');
    }
    const select = row ? row.querySelector('select') : null;
    if (select) select.disabled = !chk.checked;
  });
  hitungTotalSemua();
}

function handleRowCheckboxChange(idx) {
  const chk = document.getElementById('chk_promo_' + idx);
  const row = document.getElementById('row_promo_' + idx);
  const select = document.getElementById('target_promo_' + idx);
  if (chk && row) {
    if (chk.checked) row.classList.remove('disabled');
    else row.classList.add('disabled');
  }
  if (select && chk) {
    select.disabled = !chk.checked;
  }
  const allChks = document.querySelectorAll('#panelTabSemua input[name="pilih_kelas[]"]');
  const masterChk = document.getElementById('chkSemuaMaster');
  if (masterChk && allChks.length > 0) {
    const checkedCount = document.querySelectorAll('#panelTabSemua input[name="pilih_kelas[]"]:checked').length;
    masterChk.checked = (checkedCount === allChks.length);
    masterChk.indeterminate = (checkedCount > 0 && checkedCount < allChks.length);
  }
  hitungTotalSemua();
}

function hitungTotalSemua() {
  const checkedBoxes = document.querySelectorAll('#panelTabSemua input[name="pilih_kelas[]"]:checked');
  let totalKelas = checkedBoxes.length;
  let totalSiswa = 0;
  let totalAlumni = 0;

  checkedBoxes.forEach(chk => {
    const row = chk.closest('.promo-row');
    if (!row) return;
    const countBadge = row.querySelector('.promo-badge-count');
    const count = countBadge ? parseInt(countBadge.getAttribute('data-count') || '0', 10) : 0;
    totalSiswa += count;

    const select = row.querySelector('select');
    if (select && select.value.toLowerCase() === 'alumni') {
      totalAlumni += count;
    }
  });

  const sumKelasEl = document.getElementById('sumTotalKelas');
  const sumSiswaEl = document.getElementById('sumTotalSiswa');
  const sumAlumniEl = document.getElementById('sumTotalAlumni');
  const submitBtn = document.getElementById('btnSubmitSemua');

  if (sumKelasEl) sumKelasEl.textContent = totalKelas + ' kelas';
  if (sumSiswaEl) sumSiswaEl.textContent = totalSiswa + ' siswa';
  if (sumAlumniEl) sumAlumniEl.textContent = totalAlumni + ' siswa';

  if (submitBtn) {
    submitBtn.disabled = (totalKelas === 0);
  }
}

function handleKonfirmasiSemua(e) {
  const checkedBoxes = document.querySelectorAll('#panelTabSemua input[name="pilih_kelas[]"]:checked');
  if (checkedBoxes.length === 0) {
    alert('Pilih minimal satu kelas yang akan dinaikkan.');
    e.preventDefault();
    return false;
  }

  let totalKelas = checkedBoxes.length;
  let totalSiswa = 0;
  let totalAlumni = 0;
  let ringkasan = [];

  checkedBoxes.forEach(chk => {
    const asal = chk.value;
    const row = chk.closest('.promo-row');
    const countBadge = row ? row.querySelector('.promo-badge-count') : null;
    const count = countBadge ? parseInt(countBadge.getAttribute('data-count') || '0', 10) : 0;
    const select = row ? row.querySelector('select') : null;
    const tujuan = select ? select.value : '';
    totalSiswa += count;
    if (tujuan.toLowerCase() === 'alumni') totalAlumni += count;
    ringkasan.push(`• ${asal} (${count} siswa) ➔ ${tujuan}`);
  });

  let pesan = `KONFIRMASI KENAIKAN SEMUA KELAS SERENTAK:\n\n` +
              `Total ${totalKelas} kelas (${totalSiswa} siswa) akan diproses:\n` +
              ringkasan.slice(0, 8).join('\n') + (ringkasan.length > 8 ? `\n...dan ${ringkasan.length - 8} kelas lainnya` : '') +
              `\n\n- Siswa yang lulus ke Alumni: ${totalAlumni} siswa` +
              `\n\nApakah Anda yakin ingin mengeksekusi kenaikan seluruh kelas ini?`;

  if (!confirm(pesan)) {
    e.preventDefault();
    return false;
  }
  return true;
}

function handleKelasAsalChange() {
  const asalSelect = document.getElementById('selectKelasAsal');
  const tujuanSelect = document.getElementById('selectKelasTujuan');
  const selectedOpt = asalSelect.options[asalSelect.selectedIndex];
  if (!selectedOpt || !asalSelect.value) return;

  const namaAsal = asalSelect.value.trim();

  // Otomatis deteksi target:
  // 1. Jika mengandung 12 atau XII -> otomatis set ke Alumni
  if (/(^|\b|\s)(12|xii)($|\b|\s)/i.test(namaAsal) || /12rpl|12tkj|12pplg|12dkv/i.test(namaAsal)) {
    tujuanSelect.value = 'Alumni';
  }
  // 2. Jika mengandung 11 atau XI -> coba cari padanan 12 atau XII
  else if (/(^|\b|\s)(11)($|\b|\s)/i.test(namaAsal) || /11rpl|11tkj|11pplg/i.test(namaAsal)) {
    const candidate = namaAsal.replace(/11/i, '12');
    trySelectTarget(candidate);
  } else if (/(^|\b|\s)(xi)($|\b|\s)/i.test(namaAsal)) {
    const candidate = namaAsal.replace(/xi/i, 'XII');
    trySelectTarget(candidate);
  }
  // 3. Jika mengandung 10 atau X -> coba cari padanan 11 atau XI
  else if (/(^|\b|\s)(10)($|\b|\s)/i.test(namaAsal) || /10rpl|10tkj|10pplg/i.test(namaAsal)) {
    const candidate = namaAsal.replace(/10/i, '11');
    trySelectTarget(candidate);
  } else if (/(^|\b|\s)(x)($|\b|\s)/i.test(namaAsal)) {
    const candidate = namaAsal.replace(/\bx\b/i, 'XI');
    trySelectTarget(candidate);
  }

  updateNaikSummary();
}

function trySelectTarget(candidate) {
  const tujuanSelect = document.getElementById('selectKelasTujuan');
  const cleanCand = candidate.toLowerCase().replace(/[\s\-_]+/g, '');
  for (let i = 0; i < tujuanSelect.options.length; i++) {
    const optVal = (tujuanSelect.options[i].value || '').toLowerCase().replace(/[\s\-_]+/g, '');
    if (optVal === cleanCand) {
      tujuanSelect.selectedIndex = i;
      return true;
    }
  }
  return false;
}

function setTujuanAlumni() {
  const tujuanSelect = document.getElementById('selectKelasTujuan');
  tujuanSelect.value = 'Alumni';
  updateNaikSummary();
}

function updateNaikSummary() {
  const asalSelect   = document.getElementById('selectKelasAsal');
  const tujuanSelect = document.getElementById('selectKelasTujuan');
  const previewDiv   = document.getElementById('naikKelasPreview');
  const previewText  = document.getElementById('naikKelasPreviewText');
  const submitBtn    = document.getElementById('btnSubmitNaikKelas');

  const asalVal   = asalSelect.value;
  const tujuanVal = tujuanSelect.value;
  const selectedOpt = asalSelect.options[asalSelect.selectedIndex];
  const count = selectedOpt ? parseInt(selectedOpt.getAttribute('data-count') || '0', 10) : 0;

  if (!asalVal || !tujuanVal) {
    previewText.innerHTML = 'Silakan pilih kelas asal dan kelas tujuan di atas untuk melihat pratinjau.';
    previewDiv.style.background = 'rgba(216,184,120,.06)';
    previewDiv.style.borderColor = 'rgba(90,90,110,.15)';
    if (submitBtn) submitBtn.disabled = false;
    return;
  }

  if (asalVal.toLowerCase() === tujuanVal.toLowerCase()) {
    previewText.innerHTML = '<span style="color:#ef4444;font-weight:700;">❌ Kelas asal dan kelas tujuan tidak boleh sama!</span>';
    previewDiv.style.background = 'rgba(239,68,68,.1)';
    previewDiv.style.borderColor = 'rgba(239,68,68,.3)';
    if (submitBtn) submitBtn.disabled = true;
    return;
  }

  if (count === 0) {
    previewText.innerHTML = '<span style="color:#f59e0b;font-weight:600;">⚠️ Kelas <b>' + escapeHtmlText(asalVal) + '</b> saat ini belum memiliki siswa terdaftar. Tidak ada siswa yang akan dipindahkan.</span>';
    previewDiv.style.background = 'rgba(245,158,11,.1)';
    previewDiv.style.borderColor = 'rgba(245,158,11,.3)';
    if (submitBtn) submitBtn.disabled = true;
    return;
  }

  if (submitBtn) submitBtn.disabled = false;
  if (tujuanVal === 'Alumni') {
    previewText.innerHTML = '🎓 Sebanyak <b style="color:#34d399;font-size:.92rem;">' + count + ' siswa</b> dari kelas <b>' + escapeHtmlText(asalVal) + '</b> akan resmi diubah statusnya menjadi <b>Alumni (Lulus)</b>.';
    previewDiv.style.background = 'rgba(16,185,129,.12)';
    previewDiv.style.borderColor = 'rgba(16,185,129,.35)';
  } else {
    previewText.innerHTML = '🚀 Sebanyak <b style="color:var(--accent);font-size:.92rem;">' + count + ' siswa</b> dari kelas <b>' + escapeHtmlText(asalVal) + '</b> akan dipindahkan ke kelas <b>' + escapeHtmlText(tujuanVal) + '</b>.';
    previewDiv.style.background = 'rgba(216,184,120,.08)';
    previewDiv.style.borderColor = 'rgba(90,90,110,.15)';
  }
}

function handleKonfirmasiNaikKelas(e) {
  const asalSelect   = document.getElementById('selectKelasAsal');
  const tujuanSelect = document.getElementById('selectKelasTujuan');
  const asalVal   = asalSelect.value;
  const tujuanVal = tujuanSelect.value;
  const selectedOpt = asalSelect.options[asalSelect.selectedIndex];
  const count = selectedOpt ? parseInt(selectedOpt.getAttribute('data-count') || '0', 10) : 0;

  if (!asalVal || !tujuanVal) {
    alert('Silakan lengkapi pilihan kelas asal dan kelas tujuan.');
    e.preventDefault();
    return false;
  }
  if (asalVal.toLowerCase() === tujuanVal.toLowerCase()) {
    alert('Kelas asal dan kelas tujuan tidak boleh sama.');
    e.preventDefault();
    return false;
  }
  if (count === 0) {
    alert('Kelas asal tidak memiliki siswa.');
    e.preventDefault();
    return false;
  }

  let pesan = `Konfirmasi Kenaikan Kelas Masal:\n\nApakah Anda yakin ingin memindahkan ${count} siswa dari "${asalVal}" ke "${tujuanVal}"?`;
  if (tujuanVal === 'Alumni') {
    pesan = `Konfirmasi Kelulusan Siswa:\n\nApakah Anda yakin ingin mengubah seluruh (${count}) siswa di kelas "${asalVal}" menjadi "Alumni"? Tindakan ini akan memperbarui data kelas mereka secara permanen.`;
  }

  if (!confirm(pesan)) {
    e.preventDefault();
    return false;
  }
  return true;
}

function escapeHtmlText(str) {
  return String(str).replace(/[&<>"']/g, function(m) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
  });
}

// ─── Live Search (ketik langsung cari, tanpa tombol) ───
(function initLiveSearch() {
  const CURRENT_FILTER = <?= json_encode($filter) ?>;
  const form        = document.getElementById('searchForm');
  const input       = document.getElementById('searchInput');
  const result      = document.getElementById('searchResultArea');
  const kelasSelect = document.getElementById('kelasFilterSelect');
  if (!form || !input || !result) return;

  let debounceTimer = null;
  let currentRequest = null;

  form.addEventListener('submit', e => e.preventDefault());

  function buildParams(query) {
    const params = new URLSearchParams();
    if (query) params.set('q', query);
    if (CURRENT_FILTER && CURRENT_FILTER !== 'all') params.set('status', CURRENT_FILTER);
    if (kelasSelect && kelasSelect.value) params.set('kelas', kelasSelect.value);
    return params;
  }

  function runSearch(query) {
    if (currentRequest) currentRequest.abort();
    const controller = new AbortController();
    currentRequest = controller;

    const params = buildParams(query);
    params.set('ajax', '1');
    result.classList.add('loading-search');

    fetch('daftar_anggota.php?' + params.toString(), { signal: controller.signal })
      .then(r => r.text())
      .then(html => {
        result.innerHTML = html;
        result.classList.remove('loading-search');
        const viewParams = buildParams(query);
        const qs = viewParams.toString();
        history.replaceState(null, '', 'daftar_anggota.php' + (qs ? '?' + qs : ''));
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
    if (resetBtn) {
      e.preventDefault();
      input.value = '';
      runSearch(input.value);
    }
  });

  if (kelasSelect) {
    kelasSelect.addEventListener('change', () => runSearch(input.value));
  }
})();
</script>
</body>
</html>
<?php ob_end_flush(); ?>