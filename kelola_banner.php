<?php
// kelola_banner.php — Panel admin untuk mengatur banner carousel di beranda
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: sign_in.php");
    exit;
}

require_once "db.php";

$page_title = "Kelola Banner – AKSA NOVA";
$msg        = "";
$msg_type   = "";

// ─── Profil admin (untuk sidebar, sama seperti halaman_admin.php) ───
$profil_res  = mysqli_query($conn, "SELECT * FROM admin_profile LIMIT 1");
$profil      = $profil_res ? mysqli_fetch_assoc($profil_res) : null;
$admin_name  = $profil["display_name"] ?? ($_SESSION["user_name"] ?? "Admin");
$admin_foto  = $profil["foto"] ?? "";

// ─────────────────────────────────────────────
//  HELPER: upload gambar banner
// ─────────────────────────────────────────────
function uploadGambarBanner($file): string {
    if (!isset($file) || $file["error"] !== UPLOAD_ERR_OK) return "";
    $dir = "uploads/banner/";
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $ext     = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowed = ["jpg", "jpeg", "png", "webp", "gif"];
    if (!in_array($ext, $allowed)) return "";
    $filename = uniqid("banner_") . "." . $ext;
    move_uploaded_file($file["tmp_name"], $dir . $filename);
    return $dir . $filename;
}

// ─────────────────────────────────────────────
//  AKSI CRUD
// ─────────────────────────────────────────────
$action = $_POST["action"] ?? "";

// TAMBAH
if ($action === "tambah") {
    $judul    = trim(mysqli_real_escape_string($conn, $_POST["judul"]    ?? ""));
    $subjudul = trim(mysqli_real_escape_string($conn, $_POST["subjudul"] ?? ""));
    $link_url = trim(mysqli_real_escape_string($conn, $_POST["link_url"] ?? ""));
    $gambar   = uploadGambarBanner($_FILES["gambar"] ?? null);

    // Urutan otomatis: taruh di paling akhir
    $r = mysqli_query($conn, "SELECT COALESCE(MAX(urutan),0) AS m FROM banner");
    $urutan_baru = ((int)(mysqli_fetch_assoc($r)["m"] ?? 0)) + 1;

    if ($gambar === "") {
        $msg = "Gambar banner wajib diunggah (format: jpg, jpeg, png, webp, gif)."; $msg_type = "error";
    } else {
        mysqli_query($conn,
            "INSERT INTO banner (judul, subjudul, gambar, link_url, urutan, aktif)
             VALUES ('$judul','$subjudul','$gambar','$link_url',$urutan_baru,1)"
        );
        $msg = "Banner berhasil ditambahkan!"; $msg_type = "success";
    }
}

// UPDATE
if ($action === "update") {
    $id       = (int)($_POST["id"] ?? 0);
    $judul    = trim(mysqli_real_escape_string($conn, $_POST["judul"]    ?? ""));
    $subjudul = trim(mysqli_real_escape_string($conn, $_POST["subjudul"] ?? ""));
    $link_url = trim(mysqli_real_escape_string($conn, $_POST["link_url"] ?? ""));
    $gambar_lama = trim(mysqli_real_escape_string($conn, $_POST["gambar_lama"] ?? ""));

    $gambar_baru  = uploadGambarBanner($_FILES["gambar"] ?? null);
    $gambar_final = $gambar_baru !== "" ? $gambar_baru : $gambar_lama;

    if ($id === 0) {
        $msg = "Data tidak valid."; $msg_type = "error";
    } else {
        // Hapus file lama jika diganti gambar baru
        if ($gambar_baru !== "" && $gambar_lama !== "" && file_exists($gambar_lama)) {
            @unlink($gambar_lama);
        }
        mysqli_query($conn,
            "UPDATE banner SET judul='$judul', subjudul='$subjudul',
             link_url='$link_url', gambar='$gambar_final' WHERE id=$id"
        );
        $msg = "Banner berhasil diperbarui!"; $msg_type = "success";
    }
}

// HAPUS
if ($action === "hapus") {
    $id = (int)($_POST["id"] ?? 0);
    if ($id > 0) {
        $r = mysqli_query($conn, "SELECT gambar FROM banner WHERE id=$id");
        if ($r && $row = mysqli_fetch_assoc($r)) {
            if ($row["gambar"] && file_exists($row["gambar"])) {
                @unlink($row["gambar"]);
            }
        }
        mysqli_query($conn, "DELETE FROM banner WHERE id=$id");
        $msg = "Banner berhasil dihapus."; $msg_type = "success";
    }
}

// TOGGLE AKTIF/NONAKTIF
if ($action === "toggle") {
    $id = (int)($_POST["id"] ?? 0);
    if ($id > 0) {
        mysqli_query($conn, "UPDATE banner SET aktif = 1 - aktif WHERE id=$id");
        $msg = "Status banner diperbarui."; $msg_type = "success";
    }
}

// UBAH URUTAN (naik / turun)
if ($action === "geser") {
    $id   = (int)($_POST["id"]   ?? 0);
    $arah = $_POST["arah"] ?? "";
    $cur  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, urutan FROM banner WHERE id=$id"));
    if ($cur) {
        $op  = $arah === "naik" ? "<" : ">";
        $ord = $arah === "naik" ? "DESC" : "ASC";
        $tetangga = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id, urutan FROM banner WHERE urutan $op {$cur['urutan']} ORDER BY urutan $ord LIMIT 1"
        ));
        if ($tetangga) {
            mysqli_query($conn, "UPDATE banner SET urutan={$tetangga['urutan']} WHERE id={$cur['id']}");
            mysqli_query($conn, "UPDATE banner SET urutan={$cur['urutan']} WHERE id={$tetangga['id']}");
        }
    }
}

// ─────────────────────────────────────────────
//  AMBIL DATA
// ─────────────────────────────────────────────
$banner_list = [];
$res = mysqli_query($conn, "SELECT * FROM banner ORDER BY urutan ASC, id ASC");
while ($row = mysqli_fetch_assoc($res)) {
    $banner_list[] = $row;
}
$total = count($banner_list);
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
      --accent:      #2b4fff;
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
    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      display: flex;
      min-height: 100vh;
      animation: bodyIn .4s ease both;
    }
    @keyframes bodyIn { from { opacity:0; } to { opacity:1; } }

    .sidebar {
      width: var(--sidebar-w);
      background: linear-gradient(180deg, #5a5a6e 0%, #2e2e3a 100%);
      display: flex; flex-direction: column; align-items: center;
      padding: 28px 20px 16px;
      position: fixed; top: 0; left: 0; bottom: 0; z-index: 100;
    }
    .sidebar-header { flex-shrink:0; display:flex; flex-direction:column; align-items:center; width:100%; margin-bottom:14px; }
    .logo-mini { width:46px; height:46px; border-radius:12px; background:rgba(255,255,255,.1); display:flex; align-items:center; justify-content:center; margin-bottom:10px; }
    .logo-mini svg { width:24px; height:24px; color:#fff; }
    .admin-name-label { color:#fff; font-weight:700; font-size:.85rem; text-align:center; }
    .sidebar-nav { width:100%; display:flex; flex-direction:column; gap:4px; }
    .sidebar-btn {
      display:flex; align-items:center; gap:10px; width:100%;
      padding:10px 14px; border-radius:10px; border:none; background:transparent;
      color:rgba(255,255,255,.8); font-family:'Nunito',sans-serif; font-size:.82rem; font-weight:600;
      text-decoration:none; cursor:pointer; transition:background var(--trans), color var(--trans);
    }
    .sidebar-btn svg { width:17px; height:17px; flex-shrink:0; }
    .sidebar-btn:hover { background:rgba(255,255,255,.1); color:#fff; }
    .sidebar-btn.active { background:#fff; color:var(--btn-primary); }

    .main { margin-left:var(--sidebar-w); flex:1; padding:28px 32px; }
    .page-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:12px; }
    .page-title { font-family:'Cormorant Garamond',serif; font-size:1.7rem; font-weight:700; color:var(--text); }
    .page-sub { color:var(--muted); font-size:.85rem; margin-top:2px; }
    .btn-add {
      background:var(--accent); color:#fff; border:none; padding:11px 20px; border-radius:10px;
      font-weight:800; font-size:.85rem; cursor:pointer; display:flex; align-items:center; gap:8px;
      box-shadow:0 4px 14px rgba(43,79,255,.3); transition:transform var(--trans);
    }
    .btn-add:hover { transform:translateY(-2px); }
    .btn-add svg { width:16px; height:16px; }

    .alert { padding:12px 16px; border-radius:10px; font-size:.85rem; font-weight:600; margin-bottom:18px; }
    .alert.success { background:#e6f9ee; color:#1c7a4c; border:1px solid #b6ecce; }
    .alert.error   { background:#fdecec; color:#c0392b; border:1px solid #f5b7b1; }

    .banner-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:18px; }
    .banner-card { background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; display:flex; flex-direction:column; }
    .banner-thumb { width:100%; aspect-ratio:16/7; background:#e4e4ee; position:relative; overflow:hidden; }
    .banner-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
    .banner-status { position:absolute; top:8px; left:8px; font-size:.65rem; font-weight:800; padding:3px 10px; border-radius:20px; text-transform:uppercase; letter-spacing:.03em; }
    .banner-status.on  { background:#1c7a4c; color:#fff; }
    .banner-status.off { background:#7a7a9a; color:#fff; }
    .banner-order { position:absolute; top:8px; right:8px; background:rgba(0,0,0,.55); color:#fff; font-size:.7rem; font-weight:800; padding:3px 9px; border-radius:20px; }
    .banner-body { padding:14px 16px; display:flex; flex-direction:column; gap:4px; flex:1; }
    .banner-judul { font-weight:800; font-size:.92rem; color:var(--text); }
    .banner-sub { font-size:.78rem; color:var(--muted); line-height:1.4; }
    .banner-link { font-size:.72rem; color:var(--accent); word-break:break-all; }
    .banner-actions { display:flex; gap:6px; padding:12px 16px; border-top:1px solid #f0f0f5; flex-wrap:wrap; }
    .icon-btn {
      border:none; background:#f0f0f5; color:var(--text); width:32px; height:32px; border-radius:8px;
      display:flex; align-items:center; justify-content:center; cursor:pointer; transition:background var(--trans);
    }
    .icon-btn svg { width:15px; height:15px; }
    .icon-btn:hover { background:#e0e0ec; }
    .icon-btn.danger:hover { background:#fdecec; color:#c0392b; }
    .icon-btn.grow { flex:1; width:auto; gap:6px; font-size:.75rem; font-weight:700; }

    .empty-state { text-align:center; padding:60px 20px; color:var(--muted); }
    .empty-state svg { width:48px; height:48px; opacity:.4; margin-bottom:12px; }

    /* Modal */
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(20,20,30,.55); z-index:300; align-items:center; justify-content:center; padding:20px; }
    .modal-overlay.open { display:flex; }
    .modal-box { background:#fff; border-radius:16px; width:100%; max-width:460px; max-height:90vh; overflow-y:auto; padding:24px; }
    .modal-title { font-family:'Cormorant Garamond',serif; font-size:1.3rem; font-weight:700; margin-bottom:16px; }
    .form-group { margin-bottom:14px; }
    .form-group label { display:block; font-size:.78rem; font-weight:700; color:var(--text); margin-bottom:6px; }
    .form-group input[type=text], .form-group input[type=url] {
      width:100%; padding:10px 12px; border-radius:8px; border:1.5px solid #e0e0ec; font-family:'Nunito',sans-serif; font-size:.85rem;
    }
    .form-group input:focus { outline:none; border-color:var(--accent); }
    .upload-box { border:2px dashed #d0d0e0; border-radius:10px; padding:16px; text-align:center; cursor:pointer; position:relative; }
    .upload-box img { max-width:100%; max-height:140px; border-radius:8px; display:none; margin:0 auto; }
    .upload-box .hint { font-size:.75rem; color:var(--muted); }
    .modal-footer { display:flex; gap:10px; margin-top:18px; }
    .btn-cancel, .btn-save {
      flex:1; padding:11px; border-radius:10px; border:none; font-weight:800; font-size:.85rem; cursor:pointer;
    }
    .btn-cancel { background:#f0f0f5; color:var(--text); }
    .btn-save { background:var(--accent); color:#fff; }

    /* Modal Penyesuaian Gambar (crop) */
    .modal-box.crop-box { max-width:480px; }
    .crop-desc { font-size:.78rem; color:var(--muted); margin-bottom:14px; line-height:1.4; }
    .crop-stage {
      width:400px; height:175px; max-width:100%; margin:0 auto;
      border-radius:10px; overflow:hidden; position:relative; background:#1a1a2e;
      cursor:grab; touch-action:none; box-shadow:0 0 0 1px #e0e0ec;
    }
    .crop-stage.dragging { cursor:grabbing; }
    .crop-stage img { position:absolute; left:0; top:0; user-select:none; -webkit-user-drag:none; pointer-events:none; transform-origin:top left; }
    .crop-controls { display:flex; align-items:center; gap:10px; margin-top:16px; }
    .crop-controls svg { width:16px; height:16px; color:var(--muted); flex-shrink:0; }
    .crop-controls input[type=range] { flex:1; accent-color:var(--accent); }
    .crop-hint-small { font-size:.7rem; color:var(--muted); text-align:center; margin-top:8px; }
  </style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-header">
    <div class="logo-mini">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
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
    <a class="sidebar-btn active" href="kelola_banner.php">
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
    <a class="sidebar-btn" href="telah_dipinjam.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      Telah Dipinjam
    </a>
    <a class="sidebar-btn" href="pengaturan_denda.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      Pengaturan Denda
    </a>
    <a class="sidebar-btn" href="beranda.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      Kembali
    </a>
  </nav>
</aside>

<main class="main">
  <div class="page-head">
    <div>
      <div class="page-title">Kelola Banner</div>
      <div class="page-sub">Atur banner carousel yang tampil di paling atas halaman Beranda (<?= $total ?> banner)</div>
    </div>
    <button class="btn-add" onclick="bukaModalTambah()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Tambah Banner
    </button>
  </div>

  <?php if ($msg): ?>
    <div class="alert <?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <?php if (empty($banner_list)): ?>
    <div class="empty-state">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><path d="M21 15l-5-5L5 19"/></svg>
      <div>Belum ada banner. Tambahkan banner pertama untuk tampil di carousel beranda.</div>
    </div>
  <?php else: ?>
    <div class="banner-grid">
      <?php foreach ($banner_list as $i => $b): ?>
      <div class="banner-card">
        <div class="banner-thumb">
          <?php if ($b["gambar"] && file_exists($b["gambar"])): ?>
            <img src="<?= htmlspecialchars($b["gambar"]) ?>" alt="<?= htmlspecialchars($b["judul"]) ?>">
          <?php endif; ?>
          <span class="banner-status <?= $b["aktif"] ? "on" : "off" ?>"><?= $b["aktif"] ? "Aktif" : "Nonaktif" ?></span>
          <span class="banner-order">#<?= $i + 1 ?></span>
        </div>
        <div class="banner-body">
          <div class="banner-judul"><?= $b["judul"] ? htmlspecialchars($b["judul"]) : "<em style='color:#aaa;'>(tanpa judul)</em>" ?></div>
          <?php if ($b["subjudul"]): ?><div class="banner-sub"><?= htmlspecialchars($b["subjudul"]) ?></div><?php endif; ?>
          <?php if ($b["link_url"]): ?><div class="banner-link">🔗 <?= htmlspecialchars($b["link_url"]) ?></div><?php endif; ?>
        </div>
        <div class="banner-actions">
          <form method="post" style="display:contents;">
            <input type="hidden" name="action" value="geser">
            <input type="hidden" name="id" value="<?= $b["id"] ?>">
            <button type="submit" name="arah" value="naik" class="icon-btn" title="Naikkan urutan" <?= $i === 0 ? "disabled style='opacity:.35;cursor:default;'" : "" ?>>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
            </button>
            <button type="submit" name="arah" value="turun" class="icon-btn" title="Turunkan urutan" <?= $i === count($banner_list)-1 ? "disabled style='opacity:.35;cursor:default;'" : "" ?>>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
          </form>
          <form method="post" style="display:contents;">
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="<?= $b["id"] ?>">
            <button type="submit" class="icon-btn" title="<?= $b["aktif"] ? "Nonaktifkan" : "Aktifkan" ?>">
              <?php if ($b["aktif"]): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.5 18.5 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              <?php endif; ?>
            </button>
          </form>
          <button type="button" class="icon-btn grow" onclick='bukaModalEdit(<?= json_encode($b, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit
          </button>
          <form method="post" style="display:contents;" onsubmit="return confirm('Hapus banner ini?');">
            <input type="hidden" name="action" value="hapus">
            <input type="hidden" name="id" value="<?= $b["id"] ?>">
            <button type="submit" class="icon-btn danger" title="Hapus">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            </button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<!-- ─── Modal Tambah/Edit Banner ─── -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal-box">
    <div class="modal-title" id="modalTitle">Tambah Banner</div>
    <form method="post" enctype="multipart/form-data" id="formBanner">
      <input type="hidden" name="action" id="formAction" value="tambah">
      <input type="hidden" name="id" id="formId" value="">
      <input type="hidden" name="gambar_lama" id="formGambarLama" value="">

      <div class="form-group">
        <label>Gambar Banner *</label>
        <div class="upload-box" onclick="document.getElementById('inputGambar').click()">
          <img id="previewImg">
          <div class="hint" id="uploadHint">Klik untuk unggah gambar — akan diminta menyesuaikan (crop) ke rasio 16:7 sebelum disimpan<br>Format: JPG, PNG, WEBP, GIF</div>
        </div>
        <input type="file" name="gambar" id="inputGambar" accept=".jpg,.jpeg,.png,.webp,.gif" style="display:none;">
      </div>

      <div class="form-group">
        <label>Judul (opsional)</label>
        <input type="text" name="judul" id="formJudul" placeholder="Contoh: Promo Baca Bulan Ini">
      </div>
      <div class="form-group">
        <label>Sub-judul / Deskripsi (opsional)</label>
        <input type="text" name="subjudul" id="formSubjudul" placeholder="Teks pendukung di bawah judul">
      </div>
      <div class="form-group">
        <label>Link tujuan saat banner diklik (opsional)</label>
        <input type="text" name="link_url" id="formLinkUrl" placeholder="mis. daftar_buku.php atau https://...">
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="tutupModal()">Batal</button>
        <button type="submit" class="btn-save">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- ─── Modal Penyesuaian Gambar (Crop) ─── -->
<div class="modal-overlay" id="modalCropOverlay">
  <div class="modal-box crop-box">
    <div class="modal-title">Sesuaikan Gambar</div>
    <div class="crop-desc">Geser gambar untuk mengatur posisi, dan gunakan slider untuk memperbesar/memperkecil. Area yang terlihat di kotak inilah yang akan tampil di carousel banner.</div>
    <div class="crop-stage" id="cropStage">
      <img id="cropImg" alt="">
    </div>
    <div class="crop-controls">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
      <input type="range" id="cropZoom" min="100" max="300" value="100">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
    </div>
    <div class="crop-hint-small">Rasio hasil: 16:7 (sesuai tampilan carousel di beranda)</div>
    <div class="modal-footer">
      <button type="button" class="btn-cancel" onclick="batalCrop()">Batal</button>
      <button type="button" class="btn-save" onclick="terapkanCrop()">Gunakan Gambar Ini</button>
    </div>
  </div>
</div>

<script>
function bukaModalTambah() {
  document.getElementById('modalTitle').textContent = 'Tambah Banner';
  document.getElementById('formAction').value = 'tambah';
  document.getElementById('formId').value = '';
  document.getElementById('formGambarLama').value = '';
  document.getElementById('formJudul').value = '';
  document.getElementById('formSubjudul').value = '';
  document.getElementById('formLinkUrl').value = '';
  document.getElementById('previewImg').style.display = 'none';
  document.getElementById('uploadHint').style.display = 'block';
  document.getElementById('inputGambar').value = '';
  document.getElementById('modalOverlay').classList.add('open');
}

function bukaModalEdit(b) {
  document.getElementById('modalTitle').textContent = 'Edit Banner';
  document.getElementById('formAction').value = 'update';
  document.getElementById('formId').value = b.id;
  document.getElementById('formGambarLama').value = b.gambar || '';
  document.getElementById('formJudul').value = b.judul || '';
  document.getElementById('formSubjudul').value = b.subjudul || '';
  document.getElementById('formLinkUrl').value = b.link_url || '';
  const img = document.getElementById('previewImg');
  if (b.gambar) {
    img.src = b.gambar;
    img.style.display = 'block';
    document.getElementById('uploadHint').style.display = 'none';
  } else {
    img.style.display = 'none';
    document.getElementById('uploadHint').style.display = 'block';
  }
  document.getElementById('inputGambar').value = '';
  document.getElementById('modalOverlay').classList.add('open');
}

function tutupModal() {
  document.getElementById('modalOverlay').classList.remove('open');
}

document.getElementById('modalOverlay').addEventListener('click', function(e) {
  if (e.target === this) tutupModal();
});

document.getElementById('modalCropOverlay').addEventListener('click', function(e) {
  if (e.target === this) batalCrop();
});

document.getElementById('inputGambar').addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = ev => bukaModalCrop(ev.target.result, file.name);
  reader.readAsDataURL(file);
});

/* ─────────────────────────────────────────────
   PENYESUAIAN GAMBAR (CROP) SEBELUM UPLOAD
   Rasio tetap 16:7, sesuai .banner-thumb
───────────────────────────────────────────── */
const CROP_W = 400, CROP_H = 175;   // ukuran kotak crop di layar (16:7)
const OUT_W  = 1600, OUT_H  = 700;  // resolusi hasil akhir yang disimpan

let cropState = null;   // { scaleMin, scale, x, y, naturalW, naturalH }
let cropDrag  = null;   // { startX, startY, origX, origY }
let prevPreviewState = null; // simpan kondisi preview sebelum modal crop dibuka, utk 'Batal'

function bukaModalCrop(dataUrl, fileName) {
  // simpan kondisi preview saat ini supaya bisa dikembalikan jika 'Batal'
  const previewImg = document.getElementById('previewImg');
  prevPreviewState = {
    src: previewImg.src,
    display: previewImg.style.display,
    hintDisplay: document.getElementById('uploadHint').style.display
  };

  const img = document.getElementById('cropImg');
  img.onload = () => {
    const naturalW = img.naturalWidth, naturalH = img.naturalHeight;
    const scaleMin = Math.max(CROP_W / naturalW, CROP_H / naturalH);
    cropState = {
      scaleMin, scale: scaleMin, naturalW, naturalH,
      x: (CROP_W - naturalW * scaleMin) / 2,
      y: (CROP_H - naturalH * scaleMin) / 2
    };
    document.getElementById('cropZoom').value = 100;
    terapkanTransformCrop();
    document.getElementById('modalCropOverlay').classList.add('open');
  };
  img.src = dataUrl;
  img.dataset.filename = fileName || 'banner.jpg';
}

function terapkanTransformCrop() {
  const img = document.getElementById('cropImg');
  img.style.width  = (cropState.naturalW * cropState.scale) + 'px';
  img.style.height = (cropState.naturalH * cropState.scale) + 'px';
  img.style.transform = `translate(${cropState.x}px, ${cropState.y}px)`;
}

function clampCropPosition() {
  const w = cropState.naturalW * cropState.scale;
  const h = cropState.naturalH * cropState.scale;
  const minX = Math.min(0, CROP_W - w), maxX = 0;
  const minY = Math.min(0, CROP_H - h), maxY = 0;
  cropState.x = Math.max(minX, Math.min(maxX, cropState.x));
  cropState.y = Math.max(minY, Math.min(maxY, cropState.y));
}

// Drag (mouse & touch) untuk menggeser posisi gambar
const cropStage = document.getElementById('cropStage');

function cropDragStart(clientX, clientY) {
  if (!cropState) return;
  cropDrag = { startX: clientX, startY: clientY, origX: cropState.x, origY: cropState.y };
  cropStage.classList.add('dragging');
}
function cropDragMove(clientX, clientY) {
  if (!cropDrag || !cropState) return;
  cropState.x = cropDrag.origX + (clientX - cropDrag.startX);
  cropState.y = cropDrag.origY + (clientY - cropDrag.startY);
  clampCropPosition();
  terapkanTransformCrop();
}
function cropDragEnd() {
  cropDrag = null;
  cropStage.classList.remove('dragging');
}

cropStage.addEventListener('mousedown', e => { e.preventDefault(); cropDragStart(e.clientX, e.clientY); });
window.addEventListener('mousemove', e => cropDragMove(e.clientX, e.clientY));
window.addEventListener('mouseup', cropDragEnd);

cropStage.addEventListener('touchstart', e => {
  const t = e.touches[0]; cropDragStart(t.clientX, t.clientY);
}, { passive: true });
cropStage.addEventListener('touchmove', e => {
  const t = e.touches[0]; cropDragMove(t.clientX, t.clientY);
}, { passive: true });
cropStage.addEventListener('touchend', cropDragEnd);

// Slider zoom
document.getElementById('cropZoom').addEventListener('input', function() {
  if (!cropState) return;
  const newScale = cropState.scaleMin * (this.value / 100);
  // pertahankan titik tengah kotak crop tetap pada bagian gambar yang sama
  const cx = (CROP_W / 2 - cropState.x) / cropState.scale;
  const cy = (CROP_H / 2 - cropState.y) / cropState.scale;
  cropState.scale = newScale;
  cropState.x = CROP_W / 2 - cx * newScale;
  cropState.y = CROP_H / 2 - cy * newScale;
  clampCropPosition();
  terapkanTransformCrop();
});

function batalCrop() {
  document.getElementById('modalCropOverlay').classList.remove('open');
  document.getElementById('inputGambar').value = '';
  if (prevPreviewState) {
    const previewImg = document.getElementById('previewImg');
    previewImg.src = prevPreviewState.src;
    previewImg.style.display = prevPreviewState.display;
    document.getElementById('uploadHint').style.display = prevPreviewState.hintDisplay;
  }
}

function terapkanCrop() {
  if (!cropState) return;
  const img = document.getElementById('cropImg');

  // Hitung area sumber (dalam koordinat gambar asli) yang terlihat di kotak crop
  const sx = -cropState.x / cropState.scale;
  const sy = -cropState.y / cropState.scale;
  const sw = CROP_W / cropState.scale;
  const sh = CROP_H / cropState.scale;

  const canvas = document.createElement('canvas');
  canvas.width = OUT_W;
  canvas.height = OUT_H;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(img, sx, sy, sw, sh, 0, 0, OUT_W, OUT_H);

  canvas.toBlob(blob => {
    const namaAsli = (img.dataset.filename || 'banner.jpg').replace(/\.[^.]+$/, '');
    const file = new File([blob], namaAsli + '_disesuaikan.jpg', { type: 'image/jpeg' });

    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('inputGambar').files = dt.files;

    const previewImg = document.getElementById('previewImg');
    previewImg.src = canvas.toDataURL('image/jpeg', 0.92);
    previewImg.style.display = 'block';
    document.getElementById('uploadHint').style.display = 'none';

    document.getElementById('modalCropOverlay').classList.remove('open');
  }, 'image/jpeg', 0.92);
}
</script>
</body>
</html>