<?php
// halaman_admin.php — Panel administrasi buku (CRUD + Search + Upload Gambar)
session_start();

// Cek login & role admin
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: sign_in.php");
    exit;
}

require_once "db.php";

$page_title = "Admin Panel – AKSA NOVA";
$msg        = "";
$msg_type   = "";

// ─── Profil admin dari DB ───
$profil_res  = mysqli_query($conn, "SELECT * FROM admin_profile LIMIT 1");
$profil      = $profil_res ? mysqli_fetch_assoc($profil_res) : null;
$admin_name  = $profil["display_name"] ?? ($_SESSION["user_name"] ?? "Admin");
$admin_foto  = $profil["foto"] ?? "";

// Pesan setelah simpan profil
if (isset($_GET["profil_saved"])) {
    $msg = "Profil berhasil diperbarui!"; $msg_type = "success";
}

// ─────────────────────────────────────────────
//  HELPER: upload gambar
// ─────────────────────────────────────────────
function uploadGambar($file): string {
    if (!isset($file) || $file["error"] !== UPLOAD_ERR_OK) return "";
    $dir = "uploads/gambar/";
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $ext      = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowed  = ["jpg","jpeg","png","webp","gif"];
    if (!in_array($ext, $allowed)) return "";
    $filename = uniqid("buku_") . "." . $ext;
    move_uploaded_file($file["tmp_name"], $dir . $filename);
    return $dir . $filename;
}

// ─────────────────────────────────────────────
//  AKSI CRUD
// ─────────────────────────────────────────────
$action = $_POST["action"] ?? "";

// TAMBAH
if ($action === "tambah") {
    $judul   = trim(mysqli_real_escape_string($conn, $_POST["judul"]   ?? ""));
    $penulis = trim(mysqli_real_escape_string($conn, $_POST["penulis"] ?? ""));
    $isbn    = trim(mysqli_real_escape_string($conn, $_POST["isbn"]     ?? ""));
    $genre   = trim(mysqli_real_escape_string($conn, $_POST["genre"]    ?? ""));
    $sinopsis = trim(mysqli_real_escape_string($conn, $_POST["sinopsis"] ?? ""));
    $stok    = max(1, (int)($_POST["stok"] ?? 1));
    $gambar  = uploadGambar($_FILES["gambar"] ?? null);

    if ($judul === "") {
        $msg = "Judul buku tidak boleh kosong."; $msg_type = "error";
    } else {
        mysqli_query($conn,
            "INSERT INTO buku (judul, penulis, isbn, genre, sinopsis, stok, gambar)
             VALUES ('$judul','$penulis','$isbn','$genre','$sinopsis',$stok,'$gambar')"
        );
        $msg = "Buku berhasil ditambahkan!"; $msg_type = "success";
    }
}

// UPDATE
if ($action === "update") {
    $id      = (int)($_POST["id"] ?? 0);
    $judul   = trim(mysqli_real_escape_string($conn, $_POST["judul"]   ?? ""));
    $penulis = trim(mysqli_real_escape_string($conn, $_POST["penulis"] ?? ""));
    $isbn    = trim(mysqli_real_escape_string($conn, $_POST["isbn"]    ?? ""));
    $genre    = trim(mysqli_real_escape_string($conn, $_POST["genre"]    ?? ""));
    $sinopsis = trim(mysqli_real_escape_string($conn, $_POST["sinopsis"] ?? ""));
    $stok    = max(1, (int)($_POST["stok"] ?? 1));
    $gambar_lama = trim(mysqli_real_escape_string($conn, $_POST["gambar_lama"] ?? ""));

    $gambar_baru = uploadGambar($_FILES["gambar"] ?? null);
    $gambar_final = $gambar_baru !== "" ? $gambar_baru : $gambar_lama;

    if ($judul === "" || $id === 0) {
        $msg = "Data tidak valid."; $msg_type = "error";
    } else {
        mysqli_query($conn,
            "UPDATE buku SET judul='$judul', penulis='$penulis', isbn='$isbn',
             genre='$genre', sinopsis='$sinopsis', stok=$stok, gambar='$gambar_final'
             WHERE id=$id"
        );
        $msg = "Buku berhasil diperbarui!"; $msg_type = "success";
    }
}

// HAPUS
if ($action === "hapus") {
    $id = (int)($_POST["id"] ?? 0);
    if ($id > 0) {
        // Hapus file gambar jika ada
        $r = mysqli_query($conn, "SELECT gambar FROM buku WHERE id=$id");
        if ($r && $row = mysqli_fetch_assoc($r)) {
            if ($row["gambar"] && file_exists($row["gambar"])) {
                @unlink($row["gambar"]);
            }
        }
        // Cek apakah buku masih punya riwayat peminjaman
        $cek_pinjam = mysqli_query($conn, "SELECT COUNT(*) AS c FROM peminjaman WHERE buku_id=$id");
        $jml_pinjam = (int)(mysqli_fetch_assoc($cek_pinjam)['c'] ?? 0);

        if ($jml_pinjam > 0) {
            $msg = "Buku tidak bisa dihapus karena masih ada $jml_pinjam riwayat peminjaman. Hapus semua riwayat peminjaman buku ini terlebih dahulu di halaman Telah Dipinjam.";
            $msg_type = "error";
        } else {
            mysqli_query($conn, "DELETE FROM buku WHERE id=$id");
            $msg = "Buku berhasil dihapus."; $msg_type = "success";
        }
    }
}

// ─────────────────────────────────────────────
//  AKSI CRUD — BANNER (Kelola Banner)
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

// Tab aktif (dipakai JS untuk otomatis buka tab yang relevan setelah submit form,
// atau saat diakses lewat link ?tab=banner dari halaman lain)
$active_tab = ($_GET["tab"] ?? "") === "banner" ? "banner" : "buku";

// TAMBAH BANNER
if ($action === "banner_tambah") {
    $active_tab = "banner";
    $judul    = trim(mysqli_real_escape_string($conn, $_POST["judul"]    ?? ""));
    $subjudul = trim(mysqli_real_escape_string($conn, $_POST["subjudul"] ?? ""));
    $link_url = trim(mysqli_real_escape_string($conn, $_POST["link_url"] ?? ""));
    $gambar   = uploadGambarBanner($_FILES["gambar"] ?? null);

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

// UPDATE BANNER
if ($action === "banner_update") {
    $active_tab = "banner";
    $id       = (int)($_POST["id"] ?? 0);
    $judul    = trim(mysqli_real_escape_string($conn, $_POST["judul"]    ?? ""));
    $subjudul = trim(mysqli_real_escape_string($conn, $_POST["subjudul"] ?? ""));
    $link_url = trim(mysqli_real_escape_string($conn, $_POST["link_url"] ?? ""));
    $gambar_lama = trim(mysqli_real_escape_string($conn, $_POST["gambar_lama"] ?? ""));

    $gambar_baru  = uploadGambarBanner($_FILES["gambar"] ?? null);
    $gambar_final = $gambar_baru !== "" ? $gambar_baru : $gambar_lama;

    if ($id === 0) {
        $msg = "Data banner tidak valid."; $msg_type = "error";
    } else {
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

// HAPUS BANNER
if ($action === "banner_hapus") {
    $active_tab = "banner";
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

// TOGGLE AKTIF/NONAKTIF BANNER
if ($action === "banner_toggle") {
    $active_tab = "banner";
    $id = (int)($_POST["id"] ?? 0);
    if ($id > 0) {
        mysqli_query($conn, "UPDATE banner SET aktif = 1 - aktif WHERE id=$id");
        $msg = "Status banner diperbarui."; $msg_type = "success";
    }
}

// UBAH URUTAN BANNER (naik / turun)
if ($action === "banner_geser") {
    $active_tab = "banner";
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

$banner_list = [];
$res_banner = mysqli_query($conn, "SELECT * FROM banner ORDER BY urutan ASC, id ASC");
while ($row = mysqli_fetch_assoc($res_banner)) {
    $banner_list[] = $row;
}
$total_banner = count($banner_list);

// ─────────────────────────────────────────────
//  AMBIL DATA
// ─────────────────────────────────────────────
$search = trim($_GET["q"] ?? "");
$where  = "";
if ($search !== "") {
    $s     = mysqli_real_escape_string($conn, $search);
    $where = "WHERE judul LIKE '%$s%' OR isbn LIKE '%$s%' OR penulis LIKE '%$s%'";
}

$buku_list = [];
$res = mysqli_query($conn, "SELECT * FROM buku $where ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($res)) {
    $buku_list[] = $row;
}
$total = count($buku_list);

// Warna placeholder berputar
$colors = ["col-a","col-b","col-c","col-d","col-e","col-f","col-g","col-h"];
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

    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      display: flex;
      min-height: 100vh;
      animation: bodyIn .4s ease both;
    }
    @keyframes bodyIn { from { opacity:0; } to { opacity:1; } }

    /* ── SIDEBAR ── */
    .sidebar {
      width: var(--sidebar-w);
      background: linear-gradient(180deg, #5a5a6e 0%, #2e2e3a 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 28px 20px 16px;
      position: fixed;
      top: 0; left: 0; bottom: 0;
      z-index: 100;
      transition: transform var(--trans);
      overflow: hidden; /* header tetap diam, hanya .sidebar-nav yang scroll */
    }
    .sidebar-header { flex-shrink: 0; display:flex; flex-direction:column; align-items:center; width:100%; }
    .sidebar-nav {
      width: 100%;
      flex: 1 1 auto;
      min-height: 0;           /* kunci supaya flex child boleh menyusut & scroll */
      overflow-y: auto;
      overflow-x: hidden;
      display: flex;
      flex-direction: column;
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
    #avatar-input { display:none; }

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
      cursor:pointer; margin-bottom:7px;
      transition:background var(--trans);
      text-align:left; text-decoration:none;
      flex-shrink:0;
    }
    .sidebar-btn:hover { background:rgba(255,255,255,.22); }
    .sidebar-btn.active { background:rgba(255,255,255,.3); }
    .sidebar-btn svg { width:16px; height:16px; flex-shrink:0; }

    /* ── MAIN ── */
    .main { margin-left:var(--sidebar-w); flex:1; padding:26px 24px; transition:margin-left var(--trans); }

    /* Alert */
    .alert {
      padding:12px 18px; border-radius:8px; font-size:.82rem;
      font-weight:700; margin-bottom:16px; animation:fadeUp .4s both;
    }
    .alert-success { background:#e8f5e9; color:#1a8a4a; border:1px solid #c8e6c9; }
    .alert-error   { background:#fce4ec; color:#c0392b; border:1px solid #f8bbd0; }

    /* Topbar */
    .topbar {
      display:flex; align-items:center;
      background:#fff; border-radius:50px;
      padding:0 18px; height:46px; gap:10px;
      margin-bottom:24px; box-shadow:var(--shadow);
      animation:fadeUp .5s .05s both;
    }
    .topbar svg { width:18px; height:18px; color:#aaa; flex-shrink:0; }
    .topbar input {
      flex:1; border:none; outline:none;
      font-family:'Nunito',sans-serif; font-size:.85rem;
      color:var(--text); background:transparent;
    }
    .topbar input::placeholder { color:#bbb; }
    .btn-search {
      background:var(--btn-primary); color:#fff;
      border:none; border-radius:20px;
      padding:6px 16px; font-family:'Nunito',sans-serif;
      font-size:.78rem; font-weight:700; cursor:pointer;
      transition:background var(--trans);
    }
    .btn-search:hover { background:#222; }

    /* Header */
    .content-header {
      display:flex; align-items:center;
      justify-content:space-between;
      margin-bottom:16px; animation:fadeUp .5s .08s both;
    }
    .content-title { font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:700; color:var(--text); }
    .btn-add {
      display:flex; align-items:center; gap:6px;
      padding:9px 18px; border-radius:8px; border:none;
      background:var(--btn-primary); color:#fff;
      font-family:'Nunito',sans-serif; font-size:.82rem;
      font-weight:700; cursor:pointer;
      transition:background var(--trans), box-shadow var(--trans);
    }
    .btn-add:hover { background:#222; box-shadow:0 4px 14px rgba(0,0,0,.2); }
    .btn-add svg { width:15px; height:15px; }

    /* Books grid */
    .books-grid {
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(140px, 1fr));
      gap:14px; animation:fadeUp .5s .12s both;
    }
    .book-card {
      background:var(--card); border-radius:10px;
      overflow:hidden; box-shadow:var(--shadow);
      transition:box-shadow var(--trans), transform var(--trans);
      animation:fadeUp .4s both;
    }
    .book-card:hover { box-shadow:0 6px 18px rgba(0,0,0,.13); transform:translateY(-3px); }

    .book-img {
      width:100%; aspect-ratio:2/3;
      display:flex; align-items:center; justify-content:center;
      overflow:hidden; position:relative;
    }
    .book-img img { width:100%; height:100%; object-fit:cover; display:block; }
    .book-img svg { width:28px; height:28px; color:#bbb; }
    .col-a { background:linear-gradient(135deg,#f5a623,#d4820a); }
    .col-b { background:linear-gradient(135deg,#e74c3c,#922b21); }
    .col-c { background:linear-gradient(135deg,#9b59b6,#6c3483); }
    .col-d { background:linear-gradient(135deg,#2ecc71,#1a8a4a); }
    .col-e { background:linear-gradient(135deg,#3498db,#1a5276); }
    .col-f { background:linear-gradient(135deg,#e91e63,#880e4f); }
    .col-g { background:linear-gradient(135deg,#ff5722,#bf360c); }
    .col-h { background:linear-gradient(135deg,#607d8b,#263238); }

    .book-info { padding:10px; }
    .book-title { font-size:.75rem; font-weight:800; color:var(--text); margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .book-isbn  { font-size:.6rem; color:var(--muted); margin-bottom:8px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .book-actions { display:flex; gap:6px; }
    .btn-detail, .btn-hapus {
      flex:1; padding:5px 0; border-radius:6px; border:none;
      font-family:'Nunito',sans-serif; font-size:.65rem;
      font-weight:700; cursor:pointer;
      transition:opacity var(--trans), transform .12s;
    }
    .btn-detail { background:var(--btn-primary); color:#fff; }
    .btn-hapus  { background:#e74c3c; color:#fff; }
    .btn-detail:hover, .btn-hapus:hover { opacity:.85; }
    .btn-detail:active, .btn-hapus:active { transform:scale(.96); }

    /* Empty state */
    .empty-state {
      text-align:center; padding:60px 20px;
      color:var(--muted); animation:fadeUp .5s both;
    }
    .empty-state svg { width:56px; height:56px; margin-bottom:12px; opacity:.35; }
    .empty-state p { font-size:.88rem; font-weight:600; }

    /* ── MODAL ── */
    .modal-overlay {
      display:none; position:fixed; inset:0;
      background:rgba(0,0,0,.5); z-index:500;
      align-items:center; justify-content:center;
    }
    .modal-overlay.open { display:flex; }

    .modal {
      background:#fff; border-radius:14px;
      width:100%; max-width:480px;
      max-height:90vh; overflow-y:auto;
      box-shadow:0 20px 60px rgba(0,0,0,.25);
      animation:modalIn .25s cubic-bezier(.22,1,.36,1) both;
      padding:28px 28px 24px;
      margin:16px;
    }
    @keyframes modalIn {
      from { opacity:0; transform:scale(.94) translateY(10px); }
      to   { opacity:1; transform:scale(1) translateY(0); }
    }
    .modal-header {
      display:flex; align-items:center;
      justify-content:space-between;
      margin-bottom:20px;
    }
    .modal-title { font-family:'Cormorant Garamond',serif; font-size:1.3rem; font-weight:700; color:var(--text); }
    .modal-close {
      width:32px; height:32px; border-radius:50%;
      border:none; background:#f0f0f5;
      cursor:pointer; display:flex; align-items:center; justify-content:center;
      transition:background var(--trans);
    }
    .modal-close:hover { background:#e0e0ea; }
    .modal-close svg { width:16px; height:16px; color:var(--muted); }

    /* Image preview in modal */
    .img-preview-wrap {
      width:100%; aspect-ratio:2/1;
      border-radius:10px; overflow:hidden;
      border:2px dashed #d0d0e0;
      display:flex; align-items:center; justify-content:center;
      background:#f8f9ff; margin-bottom:16px;
      cursor:pointer; transition:border-color var(--trans);
      position:relative;
    }
    .img-preview-wrap:hover { border-color:var(--btn-primary); }
    .img-preview-wrap img { width:100%; height:100%; object-fit:cover; display:none; border-radius:8px; }
    .img-preview-wrap .upload-placeholder {
      display:flex; flex-direction:column;
      align-items:center; gap:6px; color:var(--muted);
      font-size:.78rem; font-weight:600;
    }
    .img-preview-wrap .upload-placeholder svg { width:32px; height:32px; opacity:.5; }

    /* Tombol kamera di pojok preview */
    .img-source-btns {
      position:absolute; bottom:8px; right:8px;
      display:flex; gap:6px; z-index:5;
    }
    .img-source-btn {
      display:flex; align-items:center; gap:5px;
      background:rgba(20,20,30,.72); color:#fff;
      border:none; border-radius:20px;
      padding:6px 12px; font-family:'Nunito',sans-serif;
      font-size:.68rem; font-weight:700; cursor:pointer;
      backdrop-filter:blur(3px);
      transition:background .15s;
    }
    .img-source-btn:hover { background:rgba(20,20,30,.9); }
    .img-source-btn svg { width:13px; height:13px; }

    /* ── MODAL KAMERA ── */
    .camera-overlay {
      display:none; position:fixed; inset:0;
      background:rgba(0,0,0,.75); z-index:700;
      align-items:center; justify-content:center;
    }
    .camera-overlay.open { display:flex; }
    .camera-box {
      background:#111; border-radius:16px; overflow:hidden;
      width:100%; max-width:480px; margin:16px;
      box-shadow:0 24px 70px rgba(0,0,0,.4);
    }
    .camera-video-wrap {
      width:100%; aspect-ratio:4/3; background:#000;
      display:flex; align-items:center; justify-content:center;
      position:relative; overflow:hidden;
    }
    .camera-video-wrap video, .camera-video-wrap canvas {
      width:100%; height:100%; object-fit:cover;
    }
    .camera-video-wrap canvas { display:none; }
    .camera-hint {
      position:absolute; top:0; left:0; right:0; bottom:0;
      display:flex; align-items:center; justify-content:center;
      color:#999; font-family:'Nunito',sans-serif; font-size:.8rem;
      text-align:center; padding:20px;
    }
    .camera-controls {
      display:flex; align-items:center; justify-content:center;
      gap:14px; padding:16px;
    }
    .cam-btn {
      border:none; border-radius:50px; cursor:pointer;
      font-family:'Nunito',sans-serif; font-weight:700;
      transition:opacity .15s, transform .12s;
    }
    .cam-btn:active { transform:scale(.95); }
    .cam-btn-shoot {
      width:58px; height:58px; border-radius:50%;
      background:#fff; border:4px solid #666;
    }
    .cam-btn-shoot:hover { border-color:#999; }
    .cam-btn-secondary {
      padding:10px 18px; font-size:.8rem;
      background:rgba(255,255,255,.12); color:#fff;
    }
    .cam-btn-secondary:hover { background:rgba(255,255,255,.2); }
    .cam-btn-use {
      padding:10px 22px; font-size:.8rem;
      background:#2ecc71; color:#fff;
    }
    .cam-btn-use:hover { opacity:.88; }
    .form-group { margin-bottom:14px; }
    .form-label { font-size:.76rem; font-weight:700; color:var(--muted); margin-bottom:5px; display:block; text-transform:uppercase; letter-spacing:.05em; }
    .form-input, .form-select {
      width:100%; padding:10px 14px; border-radius:8px;
      border:1.5px solid #e4e5f0;
      font-family:'Nunito',sans-serif; font-size:.85rem;
      color:var(--text); background:#fff;
      outline:none; transition:border-color var(--trans);
    }
    .form-input:focus, .form-select:focus { border-color:var(--btn-primary); }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }

    .modal-footer { display:flex; gap:10px; margin-top:20px; }
    .btn-submit {
      flex:1; padding:11px; border-radius:8px; border:none;
      background:var(--btn-primary); color:#fff;
      font-family:'Nunito',sans-serif; font-size:.85rem;
      font-weight:700; cursor:pointer;
      transition:background var(--trans);
    }
    .btn-submit:hover { background:#222; }
    .btn-cancel {
      padding:11px 20px; border-radius:8px;
      border:1.5px solid #e4e5f0; background:#fff;
      font-family:'Nunito',sans-serif; font-size:.85rem;
      font-weight:700; color:var(--muted); cursor:pointer;
      transition:all var(--trans);
    }
    .btn-cancel:hover { border-color:var(--btn-primary); color:var(--btn-primary); }

    /* Animations */
    @keyframes fadeUp {
      from { opacity:0; transform:translateY(14px); }
      to   { opacity:1; transform:translateY(0); }
    }
    .book-card:nth-child(1)  { animation-delay:.06s; }
    .book-card:nth-child(2)  { animation-delay:.10s; }
    .book-card:nth-child(3)  { animation-delay:.14s; }
    .book-card:nth-child(4)  { animation-delay:.18s; }
    .book-card:nth-child(5)  { animation-delay:.22s; }
    .book-card:nth-child(6)  { animation-delay:.26s; }
    .book-card:nth-child(n+7){ animation-delay:.30s; }

    /* ── Tab switcher (Kelola Buku / Kelola Banner) ── */
    .admin-tabs {
      display:flex; gap:6px; background:#e8e8ef; border-radius:12px;
      padding:4px; margin-bottom:20px; width:fit-content;
    }
    .admin-tab-btn {
      border:none; background:transparent; color:var(--muted);
      font-family:'Nunito',sans-serif; font-weight:700; font-size:.82rem;
      padding:9px 18px; border-radius:9px; cursor:pointer;
      display:flex; align-items:center; gap:7px;
      transition:background var(--trans), color var(--trans);
    }
    .admin-tab-btn svg { width:15px; height:15px; }
    .admin-tab-btn.active { background:#fff; color:var(--text); box-shadow:0 2px 8px rgba(0,0,0,.08); }
    .tab-panel { display:none; }
    .tab-panel.active { display:block; }

    /* ── Kelola Banner: grid & kartu ── */
    .banner-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(270px,1fr)); gap:16px; }
    .banner-card { background:var(--card); border-radius:var(--radius); box-shadow:0 2px 12px rgba(0,0,0,.07); overflow:hidden; display:flex; flex-direction:column; }
    .banner-thumb { width:100%; aspect-ratio:16/7; background:#e4e4ee; position:relative; overflow:hidden; }
    .banner-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
    .banner-status { position:absolute; top:8px; left:8px; font-size:.62rem; font-weight:800; padding:3px 10px; border-radius:20px; text-transform:uppercase; letter-spacing:.03em; }
    .banner-status.on  { background:#1c7a4c; color:#fff; }
    .banner-status.off { background:#7a7a9a; color:#fff; }
    .banner-order { position:absolute; top:8px; right:8px; background:rgba(0,0,0,.55); color:#fff; font-size:.68rem; font-weight:800; padding:3px 9px; border-radius:20px; }
    .banner-body { padding:12px 14px; display:flex; flex-direction:column; gap:3px; flex:1; }
    .banner-judul { font-weight:800; font-size:.85rem; color:var(--text); }
    .banner-sub { font-size:.74rem; color:var(--muted); line-height:1.4; }
    .banner-link { font-size:.68rem; color:#2b4fff; word-break:break-all; }
    .banner-actions { display:flex; gap:6px; padding:10px 14px; border-top:1px solid #f0f0f5; flex-wrap:wrap; }
    .banner-icon-btn {
      border:none; background:#f0f0f5; color:var(--text); width:30px; height:30px; border-radius:8px;
      display:flex; align-items:center; justify-content:center; cursor:pointer; transition:background var(--trans);
    }
    .banner-icon-btn svg { width:14px; height:14px; }
    .banner-icon-btn:hover { background:#e0e0ec; }
    .banner-icon-btn.danger:hover { background:#fdecec; color:#c0392b; }
    .banner-icon-btn.grow { flex:1; width:auto; gap:5px; font-size:.72rem; font-weight:700; }

    /* ── MODAL DETAIL BUKU ── */
    .detail-overlay {
      display:none; position:fixed; inset:0;
      background:rgba(0,0,0,.55); z-index:600;
      align-items:center; justify-content:center;
    }
    .detail-overlay.open { display:flex; }
    .detail-modal {
      background:#fff; border-radius:16px;
      width:100%; max-width:520px;
      max-height:92vh; overflow-y:auto;
      box-shadow:0 24px 70px rgba(0,0,0,.28);
      animation:modalIn .25s cubic-bezier(.22,1,.36,1) both;
      margin:16px;
    }
    .detail-cover {
      width:100%; aspect-ratio:16/9;
      border-radius:16px 16px 0 0;
      overflow:hidden; position:relative;
      background:#2e2e3a;
    }
    .detail-cover img { width:100%; height:100%; object-fit:cover; display:block; }
    .detail-cover-placeholder {
      width:100%; height:100%;
      display:flex; align-items:center; justify-content:center;
    }
    .detail-cover-placeholder svg { width:56px; height:56px; color:rgba(255,255,255,.3); }
    .detail-cover-badge {
      position:absolute; top:12px; right:12px;
      background:rgba(0,0,0,.55); color:#fff;
      font-size:.65rem; font-weight:800;
      padding:4px 10px; border-radius:20px;
      letter-spacing:.05em; text-transform:uppercase;
      backdrop-filter:blur(4px);
    }
    .detail-close-btn {
      position:absolute; top:12px; left:12px;
      width:32px; height:32px; border-radius:50%;
      background:rgba(0,0,0,.5); border:none;
      display:flex; align-items:center; justify-content:center;
      cursor:pointer; backdrop-filter:blur(4px);
      transition:background .2s;
    }
    .detail-close-btn:hover { background:rgba(0,0,0,.75); }
    .detail-close-btn svg { width:16px; height:16px; color:#fff; }
    .detail-body { padding:22px 24px 26px; }
    .detail-genre-chip {
      display:inline-block;
      background:#eef0ff; color:#2b4fff;
      font-size:.65rem; font-weight:800;
      padding:3px 10px; border-radius:20px;
      letter-spacing:.05em; text-transform:uppercase;
      margin-bottom:10px;
    }
    .detail-title {
      font-family:'Cormorant Garamond',serif;
      font-size:1.5rem; font-weight:700; color:var(--text);
      line-height:1.2; margin-bottom:4px;
    }
    .detail-author {
      font-size:.82rem; color:var(--muted); font-weight:600;
      margin-bottom:16px;
    }
    .detail-meta-row {
      display:flex; gap:8px; flex-wrap:wrap;
      margin-bottom:18px;
    }
    .detail-meta-chip {
      display:flex; align-items:center; gap:5px;
      background:#f8f9ff; border:1px solid #eef0fc;
      border-radius:8px; padding:6px 12px;
      font-size:.72rem; font-weight:700; color:var(--muted);
    }
    .detail-meta-chip svg { width:13px; height:13px; flex-shrink:0; }
    .detail-meta-chip span { color:var(--text); }
    .detail-section-label {
      font-size:.68rem; font-weight:800; color:var(--muted);
      text-transform:uppercase; letter-spacing:.08em;
      margin-bottom:7px;
    }
    .detail-sinopsis {
      font-size:.83rem; line-height:1.7; color:#3a3a5a;
      background:#f8f9ff; border-radius:10px;
      padding:14px 16px; border-left:3px solid var(--btn-primary);
    }
    .detail-sinopsis-empty {
      font-size:.82rem; color:var(--muted); font-style:italic;
    }
    .detail-footer-btns {
      display:flex; gap:10px; margin-top:20px;
    }
    .detail-btn-edit {
      flex:1; padding:10px; border-radius:8px; border:none;
      background:var(--btn-primary); color:#fff;
      font-family:'Nunito',sans-serif; font-size:.82rem;
      font-weight:700; cursor:pointer; transition:background .2s;
      display:flex; align-items:center; justify-content:center; gap:6px;
    }
    .detail-btn-edit:hover { background:#222; }
    .detail-btn-edit svg { width:14px; height:14px; }
    .detail-stat-row {
      display:flex; gap:16px; margin-bottom:18px;
    }
    .detail-stat {
      display:flex; align-items:center; gap:6px;
      font-size:.78rem; font-weight:700;
    }
    .detail-stat svg { width:15px; height:15px; }
    .detail-stat.likes { color:#e74c3c; }
    .detail-stat.favs  { color:#f39c12; }
    /* Loading spinner */
    .detail-loading {
      display:flex; align-items:center; justify-content:center;
      padding:60px; color:var(--muted); font-size:.85rem;
      flex-direction:column; gap:12px;
    }
    .spinner {
      width:32px; height:32px; border:3px solid #eee;
      border-top-color:var(--btn-primary);
      border-radius:50%; animation:spin .7s linear infinite;
    }
    @keyframes spin { to { transform:rotate(360deg); } }
    .book-img { cursor:pointer; }

    /* Responsive */
    @media (max-width:860px) {
      :root { --sidebar-w:170px; }
      .avatar-circle { width:76px; height:76px; }
      .books-grid { grid-template-columns:repeat(auto-fill, minmax(120px,1fr)); }
    }
    @media (max-width:620px) {
      .sidebar { transform:translateX(-100%); width:220px; }
      .sidebar.open { transform:translateX(0); }
      .sidebar-toggle { display:flex; }
      .main { margin-left:0; padding:70px 14px 24px; }
      .books-grid { grid-template-columns:repeat(auto-fill, minmax(130px,1fr)); gap:10px; }
      .form-row { grid-template-columns:1fr; }
    }
    @media (max-width:380px) {
      .books-grid { grid-template-columns:repeat(2,1fr); }
    }
  </style>
</head>
<body>

<!-- Mobile toggle -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <line x1="3" y1="6" x2="21" y2="6"/>
    <line x1="3" y1="12" x2="21" y2="12"/>
    <line x1="3" y1="18" x2="21" y2="18"/>
  </svg>
</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">

  <div class="sidebar-header">
    <!-- Avatar — klik buka modal edit profil -->
    <div class="avatar-wrap" onclick="openProfilModal()" title="Edit Profil">
      <div class="avatar-circle">
        <?php if ($admin_foto && file_exists($admin_foto)): ?>
          <img src="<?= htmlspecialchars($admin_foto) ?>?v=<?= filemtime($admin_foto) ?>" alt="Admin"/>
        <?php else: ?>
          <svg class="default-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
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
    <a class="sidebar-btn active" href="halaman_admin.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Perbarui Buku
    </a>
    <button type="button" class="sidebar-btn" onclick="switchTab('banner')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><path d="M21 15l-5-5L5 19"/></svg>
      Kelola Banner
    </button>
    <a class="sidebar-btn" href="daftar_anggota.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Daftar Anggota
      <?php
        $pending_res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='member' AND status='pending'");
        $pending_count = $pending_res ? (int)(mysqli_fetch_assoc($pending_res)['c'] ?? 0) : 0;
        if ($pending_count > 0):
      ?>
        <span style="margin-left:auto;background:#e74c3c;color:#fff;font-size:.65rem;font-weight:800;padding:2px 7px;border-radius:20px;"><?= $pending_count ?></span>
      <?php endif; ?>
    </a>
    <button class="sidebar-btn" onclick="openProfilModal()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Edit Profil
    </button>
    <a class="sidebar-btn" href="pinjam_buku.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
      </svg>
      Pinjam Buku
    </a>
    <a class="sidebar-btn" href="telah_dipinjam.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 11l3 3L22 4"/>
        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
      </svg>
      Telah Dipinjam
    </a>
    <a class="sidebar-btn" href="pengaturan_denda.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      Pengaturan Denda
    </a>
    <a class="sidebar-btn" href="beranda.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      Kembali
    </a>
  </nav>

</aside>

<!-- MAIN -->
<main class="main">

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type ?>">
    <?= $msg_type === "success" ? "✅" : "❌" ?> <?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>

  <!-- Tab switcher -->
  <div class="admin-tabs">
    <button type="button" class="admin-tab-btn" id="tabBtnBuku" onclick="switchTab('buku')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      Kelola Buku
    </button>
    <button type="button" class="admin-tab-btn" id="tabBtnBanner" onclick="switchTab('banner')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><path d="M21 15l-5-5L5 19"/></svg>
      Kelola Banner
    </button>
  </div>

  <!-- ═══════════ TAB: KELOLA BUKU ═══════════ -->
  <div class="tab-panel" id="tabPanelBuku">

  <!-- Search -->
  <form method="GET" action="">
    <div class="topbar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
      </svg>
      <input type="text" name="q" placeholder="Cari buku berdasarkan judul, penulis, atau ISBN…"
             value="<?= htmlspecialchars($search) ?>"/>
      <button type="submit" class="btn-search">Cari</button>
      <?php if ($search): ?>
      <a href="halaman_admin.php" style="font-size:.75rem;color:var(--muted);text-decoration:none;white-space:nowrap;">✕ Reset</a>
      <?php endif; ?>
    </div>
  </form>

  <!-- Header -->
  <div class="content-header">
    <div class="content-title">
      Daftar Buku
      <?php if ($search): ?><span style="font-size:.9rem;color:var(--muted);font-family:'Nunito',sans-serif;"> — hasil: "<?= htmlspecialchars($search) ?>"</span><?php endif; ?>
    </div>
    <button class="btn-add" onclick="openModal('tambah')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Tambah Buku
    </button>
  </div>

  <!-- Grid buku -->
  <?php if (empty($buku_list)): ?>
  <div class="empty-state">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
      <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
    </svg>
    <p>Belum ada buku<?= $search ? " yang cocok dengan pencarian." : ". Tambahkan buku pertama!" ?></p>
  </div>
  <?php else: ?>
  <div class="books-grid">
    <?php foreach ($buku_list as $i => $buku):
      $col = $colors[$i % count($colors)];
      $buku_json = htmlspecialchars(json_encode($buku), ENT_QUOTES);
    ?>
    <div class="book-card">
      <div class="book-img <?= $buku["gambar"] ? "" : $col ?>" onclick="bukaDetailBuku(<?= $buku['id'] ?>)" title="Lihat detail buku">
        <?php if ($buku["gambar"] && file_exists($buku["gambar"])): ?>
          <img src="<?= htmlspecialchars($buku["gambar"]) ?>" alt="<?= htmlspecialchars($buku["judul"]) ?>">
        <?php else: ?>
          <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1" opacity=".5">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
          </svg>
        <?php endif; ?>
      </div>
      <div class="book-info">
        <div class="book-title" title="<?= htmlspecialchars($buku["judul"]) ?>"><?= htmlspecialchars($buku["judul"]) ?></div>
        <div class="book-isbn"><?= $buku["isbn"] ? "ISBN " . htmlspecialchars($buku["isbn"]) : "Tanpa ISBN" ?></div>
        <div class="book-actions">
          <button class="btn-detail" onclick='openModal("edit", <?= $buku_json ?>)'>Detail</button>
          <button class="btn-hapus" onclick="konfirmasiHapus(<?= $buku["id"] ?>, '<?= htmlspecialchars(addslashes($buku["judul"])) ?>')">Hapus</button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  </div>
  <!-- ═══════════ /TAB: KELOLA BUKU ═══════════ -->

  <!-- ═══════════ TAB: KELOLA BANNER ═══════════ -->
  <div class="tab-panel" id="tabPanelBanner">

    <div class="content-header">
      <div class="content-title">
        Kelola Banner
        <span style="font-size:.9rem;color:var(--muted);font-family:'Nunito',sans-serif;"> — carousel di halaman beranda (<?= $total_banner ?> banner)</span>
      </div>
      <button class="btn-add" onclick="bukaModalBanner('tambah')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Banner
      </button>
    </div>

    <?php if (empty($banner_list)): ?>
    <div class="empty-state">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><path d="M21 15l-5-5L5 19"/>
      </svg>
      <p>Belum ada banner. Tambahkan banner pertama untuk tampil di carousel beranda.</p>
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
            <input type="hidden" name="action" value="banner_geser">
            <input type="hidden" name="id" value="<?= $b["id"] ?>">
            <button type="submit" name="arah" value="naik" class="banner-icon-btn" title="Naikkan urutan" <?= $i === 0 ? "disabled style='opacity:.35;cursor:default;'" : "" ?>>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
            </button>
            <button type="submit" name="arah" value="turun" class="banner-icon-btn" title="Turunkan urutan" <?= $i === count($banner_list)-1 ? "disabled style='opacity:.35;cursor:default;'" : "" ?>>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
          </form>
          <form method="post" style="display:contents;">
            <input type="hidden" name="action" value="banner_toggle">
            <input type="hidden" name="id" value="<?= $b["id"] ?>">
            <button type="submit" class="banner-icon-btn" title="<?= $b["aktif"] ? "Nonaktifkan" : "Aktifkan" ?>">
              <?php if ($b["aktif"]): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.5 18.5 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              <?php endif; ?>
            </button>
          </form>
          <button type="button" class="banner-icon-btn grow" onclick='bukaModalBanner("edit", <?= json_encode($b, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit
          </button>
          <form method="post" style="display:contents;" onsubmit="return confirm('Hapus banner ini?');">
            <input type="hidden" name="action" value="banner_hapus">
            <input type="hidden" name="id" value="<?= $b["id"] ?>">
            <button type="submit" class="banner-icon-btn danger" title="Hapus">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            </button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
  <!-- ═══════════ /TAB: KELOLA BANNER ═══════════ -->

</main>

<!-- ═══════════ MODAL EDIT PROFIL ADMIN ═══════════ -->
<div class="modal-overlay" id="profilModalOverlay">
  <div class="modal" style="max-width:380px;">
    <div class="modal-header">
      <div class="modal-title">Edit Profil</div>
      <button class="modal-close" onclick="closeProfilModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="POST" action="update_profil_admin.php" enctype="multipart/form-data">
      <input type="hidden" name="redirect" value="halaman_admin.php"/>

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
        <button type="submit" class="btn-submit">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══════════ MODAL TAMBAH / EDIT ═══════════ -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modalTitle">Tambah Buku</div>
      <button class="modal-close" onclick="closeModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <form method="POST" enctype="multipart/form-data" id="bookForm">
      <input type="hidden" name="action" id="formAction" value="tambah"/>
      <input type="hidden" name="id"     id="formId"     value=""/>
      <input type="hidden" name="gambar_lama" id="formGambarLama" value=""/>

      <!-- Preview gambar -->
      <div class="img-preview-wrap" id="previewWrap" onclick="document.getElementById('inputGambar').click()">
        <img id="previewImg" src="" alt="Preview"/>
        <div class="upload-placeholder" id="uploadPlaceholder">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/>
            <polyline points="21 15 16 10 5 21"/>
          </svg>
          <span>Klik untuk upload gambar</span>
          <span style="font-size:.65rem;opacity:.6;">JPG, PNG, WEBP — maks 5 MB</span>
        </div>
        <div class="img-source-btns">
          <button type="button" class="img-source-btn" onclick="event.stopPropagation(); bukaKamera();">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
            Ambil Foto
          </button>
        </div>
      </div>
      <input type="file" id="inputGambar" name="gambar" accept="image/*" style="display:none"/>

      <div class="form-group">
        <label class="form-label">Judul Buku *</label>
        <input class="form-input" type="text" name="judul" id="formJudul" placeholder="Masukkan judul buku" required/>
      </div>

      <div class="form-group">
        <label class="form-label">Penulis</label>
        <input class="form-input" type="text" name="penulis" id="formPenulis" placeholder="Nama penulis"/>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">ISBN</label>
          <input class="form-input" type="text" name="isbn" id="formIsbn" placeholder="978-x-xxx-xxxxx-x"/>
        </div>
        <div class="form-group">
          <label class="form-label">Stok</label>
          <input class="form-input" type="number" name="stok" id="formStok" placeholder="1" min="0" value="1"/>
        </div>
      </div>

      <div class="form-group" style="position:relative;">
        <label class="form-label">Genre / Kategori</label>        <input class="form-input" type="text" name="genre" id="formGenre"
               placeholder="Ketik atau pilih genre…"
               autocomplete="off"
               oninput="filterGenre(this.value)"
               onfocus="filterGenre(this.value)"
               onblur="setTimeout(()=>closeGenreDropdown(),180)"/>
        <div id="genreDropdown" style="
          display:none; position:absolute; left:0; right:0; top:100%; z-index:600;
          background:#fff; border:1.5px solid #e4e5f0; border-top:none;
          border-radius:0 0 10px 10px; max-height:210px; overflow-y:auto;
          box-shadow:0 8px 24px rgba(0,0,0,.10);
        "></div>
      </div>

      <div class="form-group">
        <label class="form-label">Sinopsis / Ringkasan</label>
        <textarea class="form-input" name="sinopsis" id="formSinopsis"
                  placeholder="Tulis sinopsis atau ringkasan buku…"
                  rows="4" style="resize:vertical;line-height:1.6;"></textarea>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn-submit" id="btnSubmit">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Form hapus tersembunyi -->
<form method="POST" id="formHapus" style="display:none">
  <input type="hidden" name="action" value="hapus"/>
  <input type="hidden" name="id" id="hapusId"/>
</form>

<!-- ═══════════ MODAL DETAIL BUKU ═══════════ -->
<div class="detail-overlay" id="detailOverlay">
  <div class="detail-modal" id="detailModal">
    <div id="detailContent">
      <div class="detail-loading">
        <div class="spinner"></div>
        <span>Memuat detail buku…</span>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════ MODAL KAMERA (ambil foto buku) ═══════════ -->
<div class="camera-overlay" id="cameraOverlay">
  <div class="camera-box">
    <div class="camera-video-wrap" id="cameraVideoWrap">
      <video id="cameraVideo" autoplay playsinline muted></video>
      <canvas id="cameraCanvas"></canvas>
      <div class="camera-hint" id="cameraHint">Meminta izin akses kamera…</div>
    </div>
    <div class="camera-controls" id="cameraControlsShoot">
      <button type="button" class="cam-btn cam-btn-secondary" onclick="tutupKamera()">Batal</button>
      <button type="button" class="cam-btn cam-btn-shoot" onclick="jepretFoto()" title="Jepret"></button>
      <div style="width:74px;"></div>
    </div>
    <div class="camera-controls" id="cameraControlsReview" style="display:none;">
      <button type="button" class="cam-btn cam-btn-secondary" onclick="ulangiFoto()">Ulangi</button>
      <button type="button" class="cam-btn cam-btn-use" onclick="gunakanFoto()">Gunakan Foto</button>
    </div>
  </div>
</div>

<!-- ═══════════ MODAL TAMBAH / EDIT BANNER ═══════════ -->
<div class="modal-overlay" id="bannerModalOverlay">
  <div class="modal" style="max-width:460px;">
    <div class="modal-header">
      <div class="modal-title" id="bannerModalTitle">Tambah Banner</div>
      <button type="button" class="modal-close" onclick="tutupModalBanner()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="post" enctype="multipart/form-data" id="formBanner">
      <input type="hidden" name="action" id="bannerFormAction" value="banner_tambah">
      <input type="hidden" name="id" id="bannerFormId" value="">
      <input type="hidden" name="gambar_lama" id="bannerFormGambarLama" value="">

      <div class="img-preview-wrap" onclick="document.getElementById('bannerInputGambar').click()">
        <img id="bannerPreviewImg">
        <div class="upload-placeholder" id="bannerUploadPlaceholder">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <span>Klik untuk unggah gambar banner<br>(disarankan lebar, mis. 1200×500px)</span>
        </div>
      </div>
      <input type="file" name="gambar" id="bannerInputGambar" accept=".jpg,.jpeg,.png,.webp,.gif" style="display:none;">

      <div class="form-group">
        <label class="form-label">Judul (opsional)</label>
        <input type="text" class="form-input" name="judul" id="bannerFormJudul" placeholder="Contoh: Promo Baca Bulan Ini">
      </div>
      <div class="form-group">
        <label class="form-label">Sub-judul / Deskripsi (opsional)</label>
        <input type="text" class="form-input" name="subjudul" id="bannerFormSubjudul" placeholder="Teks pendukung di bawah judul">
      </div>
      <div class="form-group">
        <label class="form-label">Link tujuan saat banner diklik (opsional)</label>
        <input type="text" class="form-input" name="link_url" id="bannerFormLinkUrl" placeholder="mis. daftar_buku.php atau https://...">
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="tutupModalBanner()" style="flex:1;padding:11px;border-radius:8px;border:none;background:#f0f0f5;color:var(--text);font-weight:800;font-size:.85rem;cursor:pointer;">Batal</button>
        <button type="submit" class="btn-submit">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
// ─── Tab switcher (Kelola Buku / Kelola Banner) ───
function switchTab(tab) {
  const isBanner = tab === 'banner';
  document.getElementById('tabPanelBuku').classList.toggle('active', !isBanner);
  document.getElementById('tabPanelBanner').classList.toggle('active', isBanner);
  document.getElementById('tabBtnBuku').classList.toggle('active', !isBanner);
  document.getElementById('tabBtnBanner').classList.toggle('active', isBanner);
}
// Buka tab yang relevan saat halaman dimuat (mis. setelah submit form banner)
switchTab('<?= $active_tab ?>');

// ─── Modal Tambah/Edit Banner ───
function bukaModalBanner(mode, data) {
  const img = document.getElementById('bannerPreviewImg');
  const placeholder = document.getElementById('bannerUploadPlaceholder');
  document.getElementById('bannerInputGambar').value = '';

  if (mode === 'edit' && data) {
    document.getElementById('bannerModalTitle').textContent = 'Edit Banner';
    document.getElementById('bannerFormAction').value = 'banner_update';
    document.getElementById('bannerFormId').value = data.id;
    document.getElementById('bannerFormGambarLama').value = data.gambar || '';
    document.getElementById('bannerFormJudul').value = data.judul || '';
    document.getElementById('bannerFormSubjudul').value = data.subjudul || '';
    document.getElementById('bannerFormLinkUrl').value = data.link_url || '';
    if (data.gambar) {
      img.src = data.gambar; img.style.display = 'block'; placeholder.style.display = 'none';
    } else {
      img.style.display = 'none'; placeholder.style.display = 'flex';
    }
  } else {
    document.getElementById('bannerModalTitle').textContent = 'Tambah Banner';
    document.getElementById('bannerFormAction').value = 'banner_tambah';
    document.getElementById('bannerFormId').value = '';
    document.getElementById('bannerFormGambarLama').value = '';
    document.getElementById('bannerFormJudul').value = '';
    document.getElementById('bannerFormSubjudul').value = '';
    document.getElementById('bannerFormLinkUrl').value = '';
    img.style.display = 'none'; placeholder.style.display = 'flex';
  }
  document.getElementById('bannerModalOverlay').classList.add('open');
}

function tutupModalBanner() {
  document.getElementById('bannerModalOverlay').classList.remove('open');
}

document.getElementById('bannerModalOverlay').addEventListener('click', function(e) {
  if (e.target === this) tutupModalBanner();
});

document.getElementById('bannerInputGambar').addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = ev => {
    const img = document.getElementById('bannerPreviewImg');
    img.src = ev.target.result;
    img.style.display = 'block';
    document.getElementById('bannerUploadPlaceholder').style.display = 'none';
  };
  reader.readAsDataURL(file);
});
</script>

<script>
// ─── Modal Detail Buku ───
function bukaDetailBuku(id) {
  const overlay = document.getElementById('detailOverlay');
  const content = document.getElementById('detailContent');
  content.innerHTML = `<div class="detail-loading"><div class="spinner"></div><span>Memuat detail buku…</span></div>`;
  overlay.classList.add('open');

  fetch('buku_detail.php?id=' + id)
    .then(r => r.json())
    .then(data => {
      if (!data.ok) { content.innerHTML = '<div class="detail-loading">Gagal memuat data.</div>'; return; }
      renderDetail(data.buku);
    })
    .catch(() => { content.innerHTML = '<div class="detail-loading">Gagal memuat data.</div>'; });
}

function tutupDetailBuku() {
  document.getElementById('detailOverlay').classList.remove('open');
}

document.getElementById('detailOverlay').addEventListener('click', function(e) {
  if (e.target === this) tutupDetailBuku();
});

// Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    tutupDetailBuku();
    closeModal();
    closeProfilModal();
    if (typeof tutupKamera === 'function') tutupKamera();
  }
});

function renderDetail(b) {
  const content = document.getElementById('detailContent');
  const stokLabel = b.stok == 0 ? 'Habis' : (b.stok <= 3 ? 'Terbatas' : 'Tersedia');
  const stokColor = b.stok == 0 ? '#e74c3c' : (b.stok <= 3 ? '#f39c12' : '#2ecc71');
  const tglInput = b.created_at
    ? new Date(b.created_at).toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit'})
    : '—';
  const tglUpdate = b.updated_at && b.updated_at !== b.created_at
    ? new Date(b.updated_at).toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'})
    : null;

  const coverHTML = b.gambar
    ? `<img src="${escHTML(b.gambar)}" alt="${escHTML(b.judul)}">`
    : `<div class="detail-cover-placeholder">
         <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
           <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
         </svg>
       </div>`;

  content.innerHTML = `
    <div class="detail-cover">
      ${coverHTML}
      <button class="detail-close-btn" onclick="tutupDetailBuku()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
      ${b.genre ? `<span class="detail-cover-badge">${escHTML(b.genre)}</span>` : ''}
    </div>
    <div class="detail-body">
      ${b.genre ? `<div class="detail-genre-chip">${escHTML(b.genre)}</div>` : ''}
      <div class="detail-title">${escHTML(b.judul)}</div>
      <div class="detail-author">${b.penulis ? '✍️ ' + escHTML(b.penulis) : 'Penulis tidak diketahui'}</div>

      <div class="detail-stat-row">
        <div class="detail-stat likes">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          ${b.jumlah_like} Suka
        </div>
        <div class="detail-stat favs">
          <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          ${b.jumlah_favorit} Favorit
        </div>
      </div>

      <div class="detail-meta-row">
        ${b.isbn ? `<div class="detail-meta-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>ISBN: <span>${escHTML(b.isbn)}</span></div>` : ''}
        <div class="detail-meta-chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
          Stok: <span style="color:${stokColor};font-weight:800;">${escHTML(String(b.stok))} (${stokLabel})</span>
        </div>
        <div class="detail-meta-chip">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          Ditambah: <span>${tglInput}</span>
        </div>
        ${tglUpdate ? `<div class="detail-meta-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Diperbarui: <span>${tglUpdate}</span></div>` : ''}
      </div>

      <div class="detail-section-label">Sinopsis / Ringkasan</div>
      ${b.sinopsis
        ? `<div class="detail-sinopsis">${escHTML(b.sinopsis).replace(/\n/g,'<br>')}</div>`
        : `<div class="detail-sinopsis"><span class="detail-sinopsis-empty">Belum ada sinopsis untuk buku ini. Edit buku untuk menambahkan ringkasan.</span></div>`}

      <div class="detail-footer-btns">
        <button class="detail-btn-edit" onclick="tutupDetailBuku(); openModal('edit', ${JSON.stringify(b)})">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Edit Buku
        </button>
      </div>
    </div>`;
}

function escHTML(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<script>
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

// ─── Sidebar toggle ───
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

// ─── Modal ───
function openModal(mode, buku = null) {
  document.getElementById('modalOverlay').classList.add('open');
  const previewImg       = document.getElementById('previewImg');
  const uploadPlaceholder = document.getElementById('uploadPlaceholder');

  if (mode === 'tambah') {
    document.getElementById('modalTitle').textContent  = 'Tambah Buku';
    document.getElementById('formAction').value        = 'tambah';
    document.getElementById('formId').value            = '';
    document.getElementById('formGambarLama').value    = '';
    document.getElementById('formJudul').value         = '';
    document.getElementById('formPenulis').value       = '';
    document.getElementById('formIsbn').value          = '';
    document.getElementById('formStok').value          = '0';
    document.getElementById('formGenre').value         = '';
    document.getElementById('formSinopsis').value      = '';
    document.getElementById('btnSubmit').textContent   = 'Simpan';
    previewImg.style.display = 'none';
    uploadPlaceholder.style.display = 'flex';
  } else {
    document.getElementById('modalTitle').textContent  = 'Detail / Edit Buku';
    document.getElementById('formAction').value        = 'update';
    document.getElementById('formId').value            = buku.id;
    document.getElementById('formGambarLama').value    = buku.gambar;
    document.getElementById('formJudul').value         = buku.judul;
    document.getElementById('formPenulis').value       = buku.penulis;
    document.getElementById('formIsbn').value          = buku.isbn;
    document.getElementById('formStok').value          = buku.stok;
    document.getElementById('formGenre').value         = buku.genre;
    document.getElementById('formSinopsis').value      = buku.sinopsis || '';
    document.getElementById('btnSubmit').textContent   = 'Perbarui';

    if (buku.gambar) {
      previewImg.src = buku.gambar;
      previewImg.style.display = 'block';
      uploadPlaceholder.style.display = 'none';
    } else {
      previewImg.style.display = 'none';
      uploadPlaceholder.style.display = 'flex';
    }
  }
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('open');
  document.getElementById('inputGambar').value = '';
}

// Tutup modal kalau klik di luar
document.getElementById('modalOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

// Preview gambar saat pilih file
document.getElementById('inputGambar').addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = ev => {
    const img = document.getElementById('previewImg');
    img.src = ev.target.result;
    img.style.display = 'block';
    document.getElementById('uploadPlaceholder').style.display = 'none';
  };
  reader.readAsDataURL(file);
});

// ─── Ambil Foto Buku dari Kamera ───
let cameraStream = null;

function bukaKamera() {
  document.getElementById('cameraOverlay').classList.add('open');
  document.getElementById('cameraHint').textContent = 'Meminta izin akses kamera…';
  document.getElementById('cameraHint').style.display = 'flex';
  document.getElementById('cameraControlsShoot').style.display = 'flex';
  document.getElementById('cameraControlsReview').style.display = 'none';
  document.getElementById('cameraVideo').style.display = 'block';
  document.getElementById('cameraCanvas').style.display = 'none';

  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    document.getElementById('cameraHint').textContent =
      'Browser ini tidak mendukung akses kamera. Gunakan tombol upload gambar biasa.';
    return;
  }

  navigator.mediaDevices.getUserMedia({
    video: { facingMode: { ideal: 'environment' } },
    audio: false
  }).then(stream => {
    cameraStream = stream;
    const video = document.getElementById('cameraVideo');
    video.srcObject = stream;
    document.getElementById('cameraHint').style.display = 'none';
  }).catch(err => {
    document.getElementById('cameraHint').textContent =
      'Tidak bisa mengakses kamera (izin ditolak atau tidak tersedia). Gunakan tombol upload gambar biasa.';
    console.error('Camera error:', err);
  });
}

function hentikanStreamKamera() {
  if (cameraStream) {
    cameraStream.getTracks().forEach(track => track.stop());
    cameraStream = null;
  }
}

function tutupKamera() {
  hentikanStreamKamera();
  document.getElementById('cameraOverlay').classList.remove('open');
}

function jepretFoto() {
  const video  = document.getElementById('cameraVideo');
  const canvas = document.getElementById('cameraCanvas');
  if (!video.videoWidth) return; // kamera belum siap

  canvas.width  = video.videoWidth;
  canvas.height = video.videoHeight;
  canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

  video.style.display  = 'none';
  canvas.style.display = 'block';
  document.getElementById('cameraControlsShoot').style.display = 'none';
  document.getElementById('cameraControlsReview').style.display = 'flex';
}

function ulangiFoto() {
  document.getElementById('cameraVideo').style.display  = 'block';
  document.getElementById('cameraCanvas').style.display = 'none';
  document.getElementById('cameraControlsShoot').style.display = 'flex';
  document.getElementById('cameraControlsReview').style.display = 'none';
}

function gunakanFoto() {
  const canvas = document.getElementById('cameraCanvas');
  canvas.toBlob(blob => {
    if (!blob) return;
    const file = new File([blob], 'foto-buku-' + Date.now() + '.jpg', { type: 'image/jpeg' });

    // Masukkan hasil jepretan ke input file yang sama supaya ikut ter-upload saat form disubmit
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(file);
    document.getElementById('inputGambar').files = dataTransfer.files;

    // Update preview
    const img = document.getElementById('previewImg');
    img.src = canvas.toDataURL('image/jpeg', 0.92);
    img.style.display = 'block';
    document.getElementById('uploadPlaceholder').style.display = 'none';

    tutupKamera();
  }, 'image/jpeg', 0.92);
}

// Tutup kamera kalau klik di luar kotak
document.getElementById('cameraOverlay').addEventListener('click', function(e) {
  if (e.target === this) tutupKamera();
});

// ─── Konfirmasi hapus ───
function konfirmasiHapus(id, judul) {
  if (confirm(`Hapus buku "${judul}"? Tindakan ini tidak bisa dibatalkan.`)) {
    document.getElementById('hapusId').value = id;
    document.getElementById('formHapus').submit();
  }
}

// ─── Genre Autocomplete ───
const GENRE_LIST = [
  // Fiksi
  "Novel", "Novel Romansa", "Novel Horor", "Novel Thriller", "Novel Misteri",
  "Novel Fantasi", "Novel Fiksi Ilmiah", "Novel Sejarah", "Novel Petualangan",
  "Cerpen", "Dongeng", "Fabel", "Legenda", "Mitologi",
  // Non-fiksi & Edukasi
  "Edukasi", "Pendidikan", "Buku Pelajaran", "Buku Teks", "Ensiklopedia",
  "Ilmu Pengetahuan", "Sains", "Matematika", "Fisika", "Kimia", "Biologi",
  "Sejarah", "Geografi", "Sosial & Budaya", "Politik", "Hukum", "Ekonomi",
  // Pengembangan Diri
  "Self-Help", "Motivasi", "Pengembangan Diri", "Kepemimpinan", "Bisnis",
  "Kewirausahaan", "Keuangan Pribadi", "Produktivitas", "Psikologi",
  // Agama & Spiritualitas
  "Agama", "Islam", "Kristen", "Spiritualitas", "Filsafat",
  // Seni & Hobi
  "Seni & Desain", "Fotografi", "Musik", "Memasak & Kuliner", "Olahraga",
  "Travelling", "Kesehatan", "Parenting", "Humor & Komedi",
  // Teknologi
  "Teknologi", "Pemrograman", "Komputer", "Kecerdasan Buatan",
  // Anak & Remaja
  "Buku Anak", "Remaja", "Komik", "Manga", "Biografi",
];

function filterGenre(val) {
  const dd = document.getElementById('genreDropdown');
  const q  = val.trim().toLowerCase();
  const matches = q === ""
    ? GENRE_LIST
    : GENRE_LIST.filter(g => g.toLowerCase().includes(q));

  if (matches.length === 0) { dd.style.display = 'none'; return; }

  // Dibangun lewat DOM API (bukan string innerHTML) supaya event klik
  // tidak pernah rusak/hilang meskipun nama genre punya tanda kutip dsb.
  dd.innerHTML = '';
  matches.forEach(g => {
    const item = document.createElement('div');
    item.style.cssText = "padding:9px 14px; font-size:.83rem; font-family:'Nunito',sans-serif; cursor:pointer; color:#1a1a2e; transition:background .15s; border-bottom:1px solid #f2f2f8;";
    item.innerHTML = highlightMatch(escHTML(g), q);
    item.addEventListener('mousedown', (e) => e.preventDefault()); // cegah blur menutup dropdown sebelum klik terbaca
    item.addEventListener('click', () => pilihGenre(g));
    item.addEventListener('mouseover', () => { item.style.background = '#f0f2ff'; item.style.color = '#2b4fff'; });
    item.addEventListener('mouseout',  () => { item.style.background = '';        item.style.color = '#1a1a2e'; });
    dd.appendChild(item);
  });

  dd.style.display = 'block';

  // Sesuaikan radius input saat dropdown terbuka
  document.getElementById('formGenre').style.borderRadius = '8px 8px 0 0';
  document.getElementById('formGenre').style.borderBottomColor = 'transparent';
}

function highlightMatch(text, q) {
  if (!q) return text;
  const idx = text.toLowerCase().indexOf(q);
  if (idx === -1) return text;
  return text.slice(0, idx)
    + `<strong style="color:#2b4fff;">${text.slice(idx, idx + q.length)}</strong>`
    + text.slice(idx + q.length);
}

function pilihGenre(val) {
  document.getElementById('formGenre').value = val;
  closeGenreDropdown();
}

function closeGenreDropdown() {
  const dd = document.getElementById('genreDropdown');
  dd.style.display = 'none';
  const inp = document.getElementById('formGenre');
  inp.style.borderRadius = '8px';
  inp.style.borderBottomColor = '';
}
</script>
</body>
</html>