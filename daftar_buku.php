<?php
// daftar_buku.php — Katalog buku (dinamis dari database)
session_start();

require_once "db.php";

// ─── Mode tamu: pengunjung boleh melihat katalog tanpa login ───
// Aksi seperti suka, rating, dan simpan tetap butuh login (lihat bagian JS di bawah).
$is_guest   = !isset($_SESSION["user_id"]);
$user_name  = $is_guest ? "Tamu" : ($_SESSION["user_name"] ?? "Pengguna");
$role       = $is_guest ? "guest" : ($_SESSION["role"] ?? "member");
$is_admin   = (!$is_guest && $role === "admin");
$admin_role = $is_admin ? "Admin" : "Member";
$page_title = "Daftar Buku – AKSA NOVA";

// ─── Pagination ───
$per_page   = 10;
$page       = max(1, (int)($_GET["page"] ?? 1));
$offset     = ($page - 1) * $per_page;

// ─── Search & Filter Kategori ───
$search   = trim($_GET["q"] ?? "");
$kategori = trim($_GET["kategori"] ?? "");
$is_ajax  = isset($_GET["ajax"]) && $_GET["ajax"] == "1";

$conditions = [];
if ($search !== "") {
    $s = mysqli_real_escape_string($conn, $search);
    $conditions[] = "(judul LIKE '%$s%' OR penulis LIKE '%$s%' OR isbn LIKE '%$s%')";
}
if ($kategori !== "") {
    $k = mysqli_real_escape_string($conn, $kategori);
    $conditions[] = "genre = '$k'";
}
$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// ─── Daftar kategori (genre) unik untuk dropdown filter ───
$kategori_list = [];
$res_kategori = mysqli_query($conn,
    "SELECT DISTINCT genre FROM buku WHERE genre IS NOT NULL AND genre != '' ORDER BY genre ASC"
);
if ($res_kategori) {
    while ($row = mysqli_fetch_assoc($res_kategori)) $kategori_list[] = $row["genre"];
}

// ─── Total & data ───
$total_res  = mysqli_query($conn, "SELECT COUNT(*) AS total FROM buku $where");
$total_row  = mysqli_fetch_assoc($total_res);
$total_buku = (int)$total_row["total"];
$total_pages = max(1, ceil($total_buku / $per_page));
$page = min($page, $total_pages);

$buku_list = [];
$res = mysqli_query($conn,
    "SELECT * FROM buku $where ORDER BY id ASC LIMIT $per_page OFFSET $offset"
);
while ($row = mysqli_fetch_assoc($res)) $buku_list[] = $row;

// ─── Likes & Favorites untuk user ini ───
$user_id   = $is_guest ? 0 : (int)$_SESSION["user_id"];
$liked_ids = [];
$fav_ids   = [];
$like_counts = [];
$fav_counts  = [];
$rating_counts = [];

if (!empty($buku_list)) {
    $ids_str = implode(",", array_column($buku_list, "id"));

    // Like count per buku
    $r = mysqli_query($conn, "SELECT buku_id, COUNT(*) AS total FROM buku_likes WHERE buku_id IN ($ids_str) GROUP BY buku_id");
    while ($row = mysqli_fetch_assoc($r)) $like_counts[$row["buku_id"]] = (int)$row["total"];

    // Fav count per buku
    $r = mysqli_query($conn, "SELECT buku_id, COUNT(*) AS total FROM buku_favorites WHERE buku_id IN ($ids_str) GROUP BY buku_id");
    while ($row = mysqli_fetch_assoc($r)) $fav_counts[$row["buku_id"]] = (int)$row["total"];

    if (!$is_admin && !$is_guest) {
        // Status like user ini
        $r = mysqli_query($conn, "SELECT buku_id FROM buku_likes WHERE user_id=$user_id AND buku_id IN ($ids_str)");
        while ($row = mysqli_fetch_assoc($r)) $liked_ids[] = $row["buku_id"];

        // Status fav user ini
        $r = mysqli_query($conn, "SELECT buku_id FROM buku_favorites WHERE user_id=$user_id AND buku_id IN ($ids_str)");
        while ($row = mysqli_fetch_assoc($r)) $fav_ids[] = $row["buku_id"];
    }

    // Rating rata-rata per buku
    $rating_counts = [];
    $r = mysqli_query($conn, "SELECT buku_id, ROUND(AVG(rating),1) AS avg_r, COUNT(*) AS total FROM buku_ratings WHERE buku_id IN ($ids_str) GROUP BY buku_id");
    while ($row = mysqli_fetch_assoc($r)) $rating_counts[$row["buku_id"]] = ["avg" => (float)$row["avg_r"], "total" => (int)$row["total"]];
}

// Warna cover berputar
$cover_cls = ["c1","c2","c3","c4","c5","c6","c7","c8"];

// Status stok
if (!function_exists('getStatus')) {
    function getStatus(int $stok): array {
        if ($stok <= 0) return ["habis",   "Kosong"];
        return ["tersedia","Ada"];
    }
}

// ─── Pengaturan musik latar ───
$musik_aktif = 0;
$musik_file  = "";
$musik_judul = "Musik Latar";
$mgt = @mysqli_query($conn, "SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('musik_aktif','musik_file','musik_judul')");
if ($mgt) {
    while ($m = mysqli_fetch_assoc($mgt)) {
        if ($m["kunci"] === "musik_aktif") $musik_aktif = (int)$m["nilai"];
        if ($m["kunci"] === "musik_file")  $musik_file  = $m["nilai"];
        if ($m["kunci"] === "musik_judul") $musik_judul = $m["nilai"] ?: $musik_judul;
    }
}
$musik_tampil = ($musik_aktif === 1 && $musik_file !== "" && file_exists($musik_file));

// Buffer seluruh output halaman. Untuk request AJAX (live search), buffer ini
// akan dibuang sepenuhnya sebelum kita kirim hanya fragmen hasil pencarian —
// supaya tidak ada error "headers already sent" dan tidak ada HTML dobel.
ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Nunito:wght@300;400;600;700;800&family=Cormorant+Garamond:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --bg:         #090c10;
      --sidebar-bg: #10151b;
      --accent:     #d8b878;
      --accent2:    #f0d9a8;
      --text:       #eef3f4;
      --muted:      rgba(238,243,244,.65);
      --card:       #121820;
      --radius:     14px;
      --sidebar-w:  204px;
      --shadow-sm:  0 2px 12px rgba(0,0,0,.25);
      --shadow-md:  0 4px 20px rgba(0,0,0,.45);
      --card-border:rgba(216,184,120,.14);
      --border-color:rgba(216,184,120,.16);
      --book-card:  #161e27;
      --trans:      .2s cubic-bezier(.22,1,.36,1);
    }
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    html { overflow-x:hidden; }
    body { font-family:var(--font-family,'Outfit',sans-serif); background:var(--bg); color:var(--text); min-height:100vh; width:100%; max-width:100vw; overflow-x:hidden; display:flex; }

    /* ── SIDEBAR ── */
    .sidebar {
      width:var(--sidebar-w); height:100vh; height:100dvh; background:var(--sidebar-bg);
      display:flex; flex-direction:column; padding:24px 0 20px;
      border-right:1px solid var(--border-color, rgba(216,184,120,.15));
      position:fixed; top:0; left:0; bottom:0; z-index:170; transition:transform var(--trans);
      overflow-y:auto; -webkit-overflow-scrolling:touch; scrollbar-width:thin;
      box-shadow:2px 0 24px rgba(0,0,0,.35);
    }
    .sidebar-toggle { display:none; position:fixed; top:14px; left:14px; z-index:200; width:42px; height:42px; border-radius:12px; border:1px solid var(--border-color, rgba(216,184,120,.2)); background:var(--card, #121820); box-shadow:0 4px 16px rgba(0,0,0,.3); cursor:pointer; align-items:center; justify-content:center; transition:transform .15s ease; }
    .sidebar-toggle:active { transform:scale(.9); }
    .sidebar-toggle svg { width:20px; height:20px; color:var(--accent); }
    .sidebar-overlay { position:fixed; inset:0; background:rgba(9,12,16,.65); backdrop-filter:blur(3px); z-index:165; opacity:0; visibility:hidden; transition:opacity var(--trans), visibility var(--trans); }
    .sidebar-overlay.open { opacity:1; visibility:visible; }
    .logo-wrap { display:flex; flex-direction:column; align-items:center; padding:0 18px 24px; border-bottom:1px solid var(--border-color, rgba(216,184,120,.15)); }
    .logo-icon { width:52px; height:52px; background:linear-gradient(135deg, rgba(216,184,120,.18) 0%, rgba(216,184,120,.05) 100%); border:1px solid rgba(216,184,120,.3); border-radius:14px; display:flex; align-items:center; justify-content:center; margin-bottom:8px; box-shadow:0 4px 16px rgba(0,0,0,.3); }
    .logo-icon svg { width:28px; height:28px; color:var(--accent); }
    .logo-name { font-family:'Cormorant Garamond',serif; font-size:1.05rem; font-weight:700; color:var(--accent); letter-spacing:.1em; text-align:center; }
    .logo-sub  { font-size:.58rem; color:var(--muted); letter-spacing:.12em; text-transform:uppercase; text-align:center; margin-top:2px; }
    .nav { flex:1; display:flex; flex-direction:column; gap:2px; padding:16px 10px; }
    .nav-item { position:relative; display:flex; align-items:center; gap:10px; padding:11px 14px; border-radius:10px; font-size:.82rem; font-weight:600; color:var(--muted); cursor:pointer; text-decoration:none; transition:background var(--trans), color var(--trans); }
    .nav-item:hover  { background:rgba(216,184,120,.10); color:var(--accent); }
    .nav-item.active { background:rgba(216,184,120,.16); color:var(--accent); }
    .nav-item.active::before { content:''; position:absolute; left:-10px; top:50%; transform:translateY(-50%); width:3px; height:60%; border-radius:0 4px 4px 0; background:var(--accent); }
    .nav-item svg { width:17px; height:17px; flex-shrink:0; }
    .nav-item.admin-only { color:#e67e22; }
    .nav-item.admin-only:hover { background:rgba(230,126,34,.12); color:#f39c12; }
    .nav-bottom { padding:10px 10px 0; border-top:1px solid var(--border-color, rgba(216,184,120,.15)); display:flex; flex-direction:column; gap:2px; flex-shrink:0; }

    /* ── MAIN ── */
    .main { margin-left:var(--sidebar-w); flex:1; min-width:0; max-width:100%; overflow-x:hidden; padding:24px 24px 32px; min-height:100vh; transition:margin-left var(--trans); }

    /* Topbar */
    .topbar { display:flex; gap:10px; margin-bottom:22px; align-items:center; }
    .search-wrap { display:flex; align-items:center; background:var(--card); border-radius:50px; padding:0 16px; gap:10px; height:42px; border:1.5px solid var(--border-color); flex:1; max-width:420px; box-shadow:var(--shadow-sm); transition:border-color var(--trans), box-shadow var(--trans); }
    .search-wrap:focus-within { border-color:var(--accent); box-shadow:0 0 0 3px rgba(216,184,120,.2); }
    .search-wrap input { border:none; outline:none; font-family:var(--font-family,'Outfit',sans-serif); font-size:.82rem; color:var(--text); background:transparent; flex:1; }
    .search-wrap input::placeholder { color:var(--muted); }
    .search-wrap svg { width:16px; height:16px; color:var(--muted); }
    .tab-btn { padding:9px 18px; border-radius:50px; border:1.5px solid var(--border-color); background:var(--card); font-family:var(--font-family,'Outfit',sans-serif); font-size:.8rem; font-weight:600; color:var(--muted); cursor:pointer; transition:all var(--trans); text-decoration:none; }
    .tab-btn:hover { border-color:var(--accent); color:var(--accent); background:rgba(216,184,120,.1); }

    .btn-topbar-mode {
      appearance: none;
      cursor: pointer;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 1.5px solid var(--border-color, rgba(216,184,120,.3));
      background: var(--card, rgba(18,24,32,.85));
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--accent, #d8b878);
      transition: all var(--trans);
      box-shadow: 0 4px 16px rgba(0,0,0,.35);
      flex-shrink: 0;
    }
    .btn-topbar-mode:hover {
      border-color: var(--accent);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(216,184,120,.35);
    }
    .btn-topbar-mode svg { width: 18px; height: 18px; transition: transform .4s cubic-bezier(.22,1,.36,1); }
    .btn-topbar-mode .icon-sun { display: none; }
    html.theme-light .btn-topbar-mode .icon-moon,
    html.light .btn-topbar-mode .icon-moon { display: none; }
    html.theme-light .btn-topbar-mode .icon-sun,
    html.light .btn-topbar-mode .icon-sun { display: block; }

    .btn-musik {
      appearance: none;
      cursor: pointer;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 1.5px solid var(--accent, #d8b878);
      background: rgba(18,24,32,.85);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--accent, #d8b878);
      transition: all var(--trans);
      box-shadow: 0 4px 16px rgba(0,0,0,.35);
      flex-shrink: 0;
    }
    .btn-musik:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(216,184,120,.35);
    }
    .btn-musik svg { width: 18px; height: 18px; }
    .btn-musik .icon-off { display: block; }
    .btn-musik .icon-eq  { display: none; align-items: flex-end; gap: 2.5px; height: 16px; }
    .btn-musik .icon-eq span {
      display: block;
      width: 3px;
      background: var(--accent, #d8b878);
      border-radius: 2px;
      animation: eqBar 1s ease-in-out infinite;
    }
    .btn-musik .icon-eq span:nth-child(1) { height: 40%; animation-delay: -.6s; }
    .btn-musik .icon-eq span:nth-child(2) { height: 100%; animation-delay: -.2s; }
    .btn-musik .icon-eq span:nth-child(3) { height: 65%; animation-delay: -.9s; }
    @keyframes eqBar { 0%,100% { transform: scaleY(.35); } 50% { transform: scaleY(1); } }
    .btn-musik.playing {
      background: rgba(216,184,120,.18);
      box-shadow: 0 0 16px rgba(216,184,120,.3);
    }
    .btn-musik.playing .icon-off { display: none; }
    .btn-musik.playing .icon-eq  { display: flex; }

    /* ══════════ NAVBAR ATAS (HUD): dudukan tombol mode & musik ══════════ */
    .mobile-topbar { display: contents; }
    .mobile-topbar-divider,
    .mobile-topbar-brand { display: none; }
    .page-hud-controls {
      position: fixed; top: 16px; right: 20px; z-index: 150;
      display: flex; align-items: center; gap: 10px;
    }
    /* Dropdown kategori bergaya chip */
    .kategori-dropdown { position:relative; flex-shrink:0; }
    .kategori-trigger {
      display:flex; align-items:center; gap:8px; height:42px; padding:0 16px; border-radius:50px;
      border:1.5px solid var(--border-color); background:var(--card); font-family:var(--font-family,'Outfit',sans-serif); font-size:.8rem; font-weight:700;
      color:var(--muted); cursor:pointer; white-space:nowrap; box-shadow:var(--shadow-sm);
      transition:border-color var(--trans), color var(--trans), background var(--trans), box-shadow var(--trans);
    }
    .kategori-trigger svg { width:16px; height:16px; flex-shrink:0; }
    .kategori-trigger .kategori-chevron { width:13px; height:13px; margin-left:1px; transition:transform var(--trans); }
    .kategori-trigger span { max-width:130px; overflow:hidden; text-overflow:ellipsis; }
    .kategori-trigger:hover { border-color:var(--accent); color:var(--accent); }
    .kategori-trigger.open { border-color:var(--accent); color:var(--accent); box-shadow:0 4px 16px rgba(216,184,120,.2); }
    .kategori-trigger.open .kategori-chevron { transform:rotate(180deg); }
    .kategori-trigger.has-value { background:rgba(216,184,120,.15); border-color:var(--accent); color:var(--accent); }

    .kategori-panel {
      position:absolute; top:calc(100% + 10px); right:0; z-index:150; background:var(--card); border:1px solid var(--card-border); border-radius:16px;
      box-shadow:var(--shadow-md); padding:12px; display:flex; flex-wrap:wrap; gap:7px; width:max-content;
      max-width:300px; opacity:0; visibility:hidden; pointer-events:none;
      transform:translateY(-8px) scale(.97); transform-origin:top right;
      transition:opacity .18s cubic-bezier(.22,1,.36,1), transform .18s cubic-bezier(.22,1,.36,1), visibility .18s;
    }
    .kategori-panel.open { opacity:1; visibility:visible; pointer-events:auto; transform:translateY(0) scale(1); }
    .kategori-chip {
      padding:7px 14px; border-radius:50px; border:1px solid var(--border-color); background:rgba(255,255,255,.04);
      font-family:var(--font-family,'Outfit',sans-serif); font-size:.74rem; font-weight:700; color:var(--muted);
      cursor:pointer; transition:all var(--trans); white-space:nowrap;
    }
    .kategori-chip:hover { border-color:var(--accent); color:var(--accent); background:rgba(216,184,120,.12); }
    .kategori-chip.active { background:linear-gradient(135deg, #d8b878, #f0d9a8); border-color:var(--accent); color:#090c10; font-weight:800; box-shadow:0 3px 10px rgba(216,184,120,.35); }

    /* Select asli tetap ada untuk aksesibilitas (navigasi keyboard) tapi disembunyikan secara visual */
    .kategori-select-sr {
      position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden;
      clip:rect(0,0,0,0); white-space:nowrap; border:0;
    }
    .td-genre-badge {
      display:inline-block; margin-top:3px; padding:2px 9px; border-radius:20px; background:rgba(216,184,120,.12); color:var(--accent);
      border:1px solid rgba(216,184,120,.2);
      font-size:.62rem; font-weight:800; letter-spacing:.03em; text-transform:uppercase;
    }
    .mb-genre-badge {
      display:inline-block; padding:2px 9px; border-radius:20px; background:rgba(216,184,120,.12); color:var(--accent);
      border:1px solid rgba(216,184,120,.2);
      font-size:.6rem; font-weight:800; letter-spacing:.03em; text-transform:uppercase; margin-top:3px;
    }

    /* Page header */
    .page-header { margin-bottom:18px; }
    .page-title { font-family:'Cormorant Garamond',serif; font-size:1.5rem; font-weight:700; color:var(--text); }
    .page-subtitle { font-size:.78rem; color:var(--muted); margin-top:2px; }

    /* Live search loading state */
    #searchResultArea { transition: opacity .15s ease; }
    #searchResultArea.loading-search { opacity: .55; }

    /* ── Grid buku (menggantikan tabel — reflow alami di semua lebar layar, tanpa geser ke samping) ── */
    .books-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:16px; animation:fadeUp .5s cubic-bezier(.22,1,.36,1) both; }
    @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

    .book-card {
      background:var(--card); border:1px solid var(--card-border, rgba(216,184,120,.12)); border-radius:var(--radius);
      box-shadow:var(--shadow-sm); overflow:hidden; min-width:0;
      cursor:pointer; transition:box-shadow var(--trans), transform var(--trans), border-color var(--trans);
      display:flex; flex-direction:column; height:100%;
    }
    .book-card:hover { box-shadow:var(--shadow-md); transform:translateY(-3px); border-color:var(--accent); }
    .book-cover-wrap { aspect-ratio:2/3; overflow:hidden; position:relative; flex-shrink:0; }
    .book-cover-wrap img { width:100%; height:100%; object-fit:cover; display:block; }
    .c1 { background:linear-gradient(135deg,#f5a623,#d4820a); }
    .c2 { background:linear-gradient(135deg,#9b59b6,#6c3483); }
    .c3 { background:linear-gradient(135deg,#e74c3c,#922b21); }
    .c4 { background:linear-gradient(135deg,#2ecc71,#1a8a4a); }
    .c5 { background:linear-gradient(135deg,#3498db,#1a5276); }
    .c6 { background:linear-gradient(135deg,#e91e63,#880e4f); }
    .c7 { background:linear-gradient(135deg,#ff5722,#bf360c); }
    .c8 { background:linear-gradient(135deg,#607d8b,#263238); }
    .cover-initial { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:1.3rem; font-weight:800; color:rgba(255,255,255,.8); }
    .cover-rating { position:absolute; bottom:6px; right:6px; background:rgba(0,0,0,.7); border:1px solid rgba(216,184,120,.3); color:#d8b878; font-size:.6rem; font-weight:800; padding:2px 7px; border-radius:20px; display:flex; align-items:center; gap:2px; backdrop-filter:blur(3px); }
    .cover-rating svg { width:9px; height:9px; }
    .cover-num-badge { position:absolute; top:6px; left:6px; width:22px; height:22px; border-radius:50%; background:rgba(0,0,0,.7); border:1px solid rgba(216,184,120,.3); color:var(--accent); font-size:.62rem; font-weight:800; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(3px); }

    .book-info { padding:10px 12px 12px; flex:1; display:flex; flex-direction:column; gap:4px; min-width:0; }
    .bk-title { font-size:.83rem; font-weight:800; color:var(--text); line-height:1.35; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:2.25rem; }
    .bk-author { font-size:.7rem; color:var(--muted); font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .bk-badges-row { display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-top:2px; min-height:22px; }
    .bk-genre { font-size:.6rem; font-weight:700; color:var(--accent); background:rgba(216,184,120,.12); border:1px solid rgba(216,184,120,.2); padding:2px 8px; border-radius:50px; white-space:nowrap; }

    /* Status badge */
    .status-badge { display:inline-flex; align-items:center; gap:5px; font-size:.62rem; font-weight:700; padding:3px 9px; border-radius:50px; white-space:nowrap; }
    .status-badge .dot { width:6px; height:6px; border-radius:50%; flex-shrink:0; }
    .status-tersedia { background:rgba(46,204,113,.12); color:#2ecc71; border:1px solid rgba(46,204,113,.25); }
    .status-tersedia .dot { background:#2ecc71; }
    .status-habis    { background:rgba(231,76,60,.12); color:#e74c3c; border:1px solid rgba(231,76,60,.25); }
    .status-habis .dot { background:#e74c3c; }

    .bk-stock { font-size:.65rem; font-weight:700; color:var(--muted); }

    .bk-actions { display:flex; align-items:center; gap:8px; margin-top:auto; padding-top:10px; border-top:1px solid var(--border-color, rgba(216,184,120,.15)); flex-wrap:nowrap; }

    /* Empty state */
    .empty-state { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:70px 20px; gap:14px; color:var(--muted); grid-column:1/-1; text-align:center; }
    .empty-state svg { width:52px; height:52px; opacity:.3; color:var(--accent); }
    .empty-state .empty-title { font-size:.95rem; font-weight:800; color:var(--text); }
    .empty-state .empty-sub { font-size:.8rem; }

    /* Pagination */
    .pagination-wrap { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; padding:18px 4px 4px; margin-top:6px; border-top:1px solid var(--border-color); }
    .pagination-info { font-size:.75rem; color:var(--muted); font-weight:600; }
    .pagination-btns { display:flex; gap:4px; flex-wrap:wrap; }
    .page-btn { width:30px; height:30px; border-radius:8px; border:1px solid var(--border-color); background:var(--card); font-family:var(--font-family,'Outfit',sans-serif); font-size:.78rem; font-weight:700; color:var(--muted); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all var(--trans); text-decoration:none; }
    .page-btn:hover { border-color:var(--accent); color:var(--accent); background:rgba(216,184,120,.1); }
    .page-btn.active { background:linear-gradient(135deg, #d8b878, #f0d9a8); color:#090c10; border-color:var(--accent); font-weight:800; }
    .page-btn svg { width:13px; height:13px; }

    /* ── RESPONSIVE ── */

    /* Tablet landscape & small desktop */
    @media (max-width:900px) {
      .main { padding:24px 18px 32px; }
    }

    /* Tablet portrait & Mobile */
    @media (max-width:768px) {
      /* Sidebar slide-in */
      .sidebar {
        transform:translateX(-100%);
        width:min(calc(var(--sidebar-w) + 60px), 260px);
        padding-bottom:max(20px, env(safe-area-inset-bottom));
      }
      .sidebar.open { transform:translateX(0); }
      .nav-item { padding:13px 14px; font-size:.86rem; }
      .nav-item svg { width:18px; height:18px; }

      /* Navbar atas mobile: satu bilah utuh, bukan tombol lepas mengambang */
      .mobile-topbar {
        display:flex; align-items:center; gap:12px;
        position:fixed; top:0; left:0; right:0; height:60px;
        padding:0 14px; padding-top:env(safe-area-inset-top,0);
        background:var(--sidebar-bg);
        border-bottom:1px solid var(--border-color, rgba(216,184,120,.15));
        box-shadow:0 2px 18px rgba(0,0,0,.35);
        z-index:160;
        transition:opacity var(--trans), visibility var(--trans);
      }
      body.sidebar-open .mobile-topbar {
        opacity:0;
        visibility:hidden;
        pointer-events:none;
      }
      .mobile-topbar .sidebar-toggle {
        display:flex; position:static; box-shadow:none; flex-shrink:0;
      }
      .mobile-topbar-divider {
        display:block; width:1px; height:26px; flex-shrink:0;
        background:linear-gradient(180deg, transparent, var(--border-color, rgba(216,184,120,.35)) 50%, transparent);
      }
      .mobile-topbar-brand {
        display:flex; align-items:center; gap:7px; min-width:0; overflow:hidden;
      }
      .mobile-topbar-brand svg { width:19px; height:19px; color:var(--accent); flex-shrink:0; }
      .mobile-topbar-brand span {
        font-family:'Cormorant Garamond',serif; font-weight:700; font-size:.92rem;
        color:var(--accent); letter-spacing:.04em; white-space:nowrap;
        overflow:hidden; text-overflow:ellipsis;
      }
      .mobile-topbar .page-hud-controls {
        position:static; top:auto; right:auto; margin-left:auto;
      }

      /* Main content shift for navbar */
      .main { margin-left:0; padding:78px 14px 28px; }

      /* Topbar: stack search + buttons */
      .topbar { flex-wrap:wrap; gap:8px; }
      .search-wrap { max-width:100%; flex:1 1 100%; height:44px; }
      .tab-btn { flex-shrink:0; padding:10px 18px; min-height:44px; display:inline-flex; align-items:center; }

      /* Dropdown kategori full-width & panel jadi lembar di bawah trigger */
      .kategori-dropdown { flex:1 1 100%; }
      .kategori-trigger { width:100%; height:44px; justify-content:space-between; }
      .kategori-trigger span { max-width:none; flex:1; text-align:left; }
      .kategori-panel {
        left:0; right:0; top:calc(100% + 8px); width:auto; max-width:none;
        max-height:55vh; overflow-y:auto;
        transform-origin:top center; transform:translateY(-8px) scale(.98);
      }
      .kategori-panel.open { transform:translateY(0) scale(1); }

      /* Page header */
      .page-title { font-size:1.25rem; }
      .page-sub { font-size:.78rem; }

      /* Grid buku tetap reflow, tanpa perlu geser ke samping */
      .books-grid { grid-template-columns:repeat(auto-fill,minmax(145px,1fr)); }

      /* Pagination: stack info above buttons */
      .pagination-wrap { flex-direction:column; align-items:flex-start; gap:8px; }
      .page-btn { min-width:40px; min-height:40px; }

      /* Modal full-width on tablet */
      .detail-modal { max-width:100%; margin:8px; border-radius:12px; }
    }

    /* Mobile portrait (≤480px) */
    @media (max-width:480px) {
      .main { padding:74px 10px 24px; }

      .books-grid { grid-template-columns:repeat(auto-fill,minmax(135px,1fr)); gap:12px; }
      .bk-title { font-size:.78rem; min-height:2.1rem; }
      .bk-actions { flex-wrap:nowrap; gap:8px; margin-top:auto; }

      /* Pagination info & tombol lebih ringkas tapi tetap ≥44px touch */
      .pagination-info { font-size:.7rem; }
      .page-btn { width:36px; height:36px; font-size:.72rem; }

      /* Modal detail — bottom sheet feel */
      .detail-overlay { align-items:flex-end; padding:0; }
      .detail-modal {
        margin:0; border-radius:16px 16px 0 0;
        max-height:92dvh;
        position:relative; bottom:auto; left:auto; right:auto; width:100%;
      }
      .detail-title { font-size:1.15rem; }
      .detail-body { padding:16px 14px 22px; }
      .detail-meta-row { gap:5px; flex-wrap:wrap; }
      .detail-meta-chip { font-size:.64rem; padding:4px 8px; }
      .detail-rating-row { gap:6px; flex-wrap:wrap; }
      .stars-input svg { width:22px; height:22px; }
      .detail-footer-btns { gap:8px; flex-wrap:wrap; }
      .detail-stat-row { gap:8px; flex-wrap:wrap; }
    }

    /* Layar super kecil (≤375px) */
    @media (max-width:375px) {
      .main { padding:72px 8px 22px; }
      .books-grid { grid-template-columns:repeat(2, 1fr); gap:10px; }
      .bk-title { font-size:.75rem; min-height:2.05rem; }
      .page-title { font-size:1.1rem; }
      .topbar { gap:6px; }
    }


    /* Like & Simpan — tombol ikon bulat berukuran sama, tanpa teks, supaya selalu simetris & tidak pernah tembus di layar kecil */
    .btn-like, .btn-save {
      display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto;
      position:relative; width:34px; height:34px; padding:0; border-radius:50%;
      border:1.5px solid var(--border-color); background:rgba(255,255,255,.04);
      color:var(--muted); cursor:pointer; transition:all .18s; user-select:none;
    }
    .btn-like svg, .btn-save svg { width:15px; height:15px; flex-shrink:0; transition:transform .2s; pointer-events:none; }
    .btn-like:hover  { border-color:#e74c3c; color:#e74c3c; background:rgba(231,76,60,.1); }
    .btn-save:hover  { border-color:var(--accent); color:var(--accent); background:rgba(216,184,120,.12); }
    .btn-like.aktif  { border-color:#e74c3c; color:#e74c3c; background:rgba(231,76,60,.15); }
    .btn-save.aktif  { border-color:var(--accent); color:var(--accent); background:rgba(216,184,120,.18); }
    .btn-like.aktif svg { fill:#e74c3c; color:#e74c3c; }
    .btn-save.aktif  svg { fill:var(--accent); color:var(--accent); }
    .btn-like.pop svg, .btn-save.pop svg { transform:scale(1.4); }
    .btn-like .like-count-badge {
      position:absolute; top:-5px; right:-5px; min-width:15px; height:15px; padding:0 3px;
      border-radius:20px; background:var(--card); border:1px solid var(--border-color);
      color:var(--muted); font-size:.55rem; font-weight:800; line-height:13px; text-align:center;
      pointer-events:none;
    }
    .btn-like.aktif .like-count-badge { border-color:#e74c3c; color:#e74c3c; }

    /* ── MODAL DETAIL BUKU ── */
    .detail-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.65); backdrop-filter:blur(4px); z-index:500; align-items:center; justify-content:center; }
    .detail-overlay.open { display:flex; }
    .detail-modal { background:var(--card,#121820); border:1px solid rgba(216,184,120,.2); border-radius:16px; width:100%; max-width:500px; max-height:92vh; overflow-y:auto; box-shadow:0 24px 70px rgba(0,0,0,.5); animation:modalIn .25s cubic-bezier(.22,1,.36,1) both; margin:16px; }
    @keyframes modalIn { from{opacity:0;transform:scale(.94) translateY(10px)} to{opacity:1;transform:scale(1) translateY(0)} }
    .detail-cover { width:100%; aspect-ratio:16/9; border-radius:16px 16px 0 0; overflow:hidden; position:relative; background:#10151b; }
    .detail-cover img { width:100%; height:100%; object-fit:cover; display:block; }
    .detail-cover-placeholder { width:100%; height:100%; display:flex; align-items:center; justify-content:center; }
    .detail-cover-placeholder svg { width:56px; height:56px; color:rgba(216,184,120,.35); }
    .detail-cover-badge { position:absolute; top:12px; right:12px; background:rgba(0,0,0,.65); color:var(--accent); border:1px solid rgba(216,184,120,.3); font-size:.65rem; font-weight:800; padding:4px 10px; border-radius:20px; letter-spacing:.05em; text-transform:uppercase; backdrop-filter:blur(4px); }
    .detail-close-btn { position:absolute; top:12px; left:12px; width:32px; height:32px; border-radius:50%; background:rgba(0,0,0,.65); border:1px solid rgba(216,184,120,.3); display:flex; align-items:center; justify-content:center; cursor:pointer; backdrop-filter:blur(4px); transition:background .2s; }
    .detail-close-btn:hover { background:rgba(0,0,0,.85); }
    .detail-close-btn svg { width:16px; height:16px; color:var(--accent); }
    .detail-body { padding:20px 22px 24px; }
    .detail-genre-chip { display:inline-block; background:rgba(216,184,120,.12); color:var(--accent); border:1px solid rgba(216,184,120,.25); font-size:.65rem; font-weight:800; padding:3px 10px; border-radius:20px; letter-spacing:.05em; text-transform:uppercase; margin-bottom:8px; }
    .detail-title { font-family:'Cormorant Garamond',serif; font-size:1.45rem; font-weight:700; color:var(--text); line-height:1.2; margin-bottom:4px; }
    .detail-author { font-size:.82rem; color:var(--muted); font-weight:600; margin-bottom:14px; }
    .detail-stat-row { display:flex; gap:16px; margin-bottom:16px; }
    .detail-stat { display:flex; align-items:center; gap:6px; font-size:.78rem; font-weight:700; }
    .detail-stat svg { width:15px; height:15px; }
    .detail-stat.likes { color:#e74c3c; }
    .detail-stat.favs  { color:#f39c12; }
    .detail-body .btn-save {
      display:inline-flex; align-items:center; gap:5px;
      padding:6px 14px; border-radius:50px; border:1.5px solid var(--border-color);
      background:rgba(255,255,255,.04); font-family:var(--font-family,'Outfit',sans-serif); font-size:.75rem;
      font-weight:700; color:var(--muted); cursor:pointer; transition:all .18s;
      user-select:none;
    }
    .detail-body .btn-save svg { width:14px; height:14px; flex-shrink:0; transition:transform .2s; }
    .detail-body .btn-save:hover { border-color:var(--accent); color:var(--accent); background:rgba(216,184,120,.12); }
    .detail-body .btn-save.aktif { border-color:var(--accent); color:var(--accent); background:rgba(216,184,120,.18); }
    .detail-body .btn-save.aktif svg { fill:var(--accent); color:var(--accent); }
    .detail-body .btn-save.pop svg { transform:scale(1.4); }
    .detail-meta-row { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
    .detail-meta-chip { display:flex; align-items:center; gap:5px; background:rgba(255,255,255,.04); border:1px solid var(--card-border, rgba(216,184,120,.14)); border-radius:8px; padding:6px 11px; font-size:.71rem; font-weight:700; color:var(--muted); }
    .detail-meta-chip svg { width:13px; height:13px; flex-shrink:0; }
    .detail-meta-chip span { color:var(--text); }
    .detail-section-label { font-size:.68rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; margin-bottom:7px; }
    .detail-sinopsis { font-size:.83rem; line-height:1.7; color:var(--text); background:rgba(255,255,255,.03); border-radius:10px; padding:14px 16px; border-left:3px solid var(--accent); border:1px solid var(--card-border, rgba(216,184,120,.12)); }
    .detail-sinopsis-empty { color:var(--muted); font-style:italic; }

    /* Rating bintang */
    .detail-rating-row { display:flex; align-items:center; gap:10px; margin-bottom:16px; flex-wrap:wrap; }
    .stars-display { display:flex; gap:2px; }
    .stars-display svg { width:15px; height:15px; }
    .rating-text { font-size:.75rem; font-weight:700; color:var(--muted); }
    .stars-input { display:flex; gap:3px; cursor:pointer; }
    .stars-input svg { width:22px; height:22px; color:#e0e0e0; transition:color .15s, transform .15s; cursor:pointer; }
    .stars-input svg.hover { color:#f5a623; transform:scale(1.15); }
    .stars-input svg.aktif { color:#f5a623; }
    .detail-loading { display:flex; align-items:center; justify-content:center; padding:60px; color:var(--muted); font-size:.85rem; flex-direction:column; gap:12px; }
    .spinner { width:32px; height:32px; border:3px solid #eee; border-top-color:var(--accent); border-radius:50%; animation:spin .7s linear infinite; }
    @keyframes spin { to { transform:rotate(360deg); } }
    tbody tr { cursor:pointer; }
    /* Tombol Like & Simpan di modal detail (user only) */
    .detail-footer-btns { display:flex; gap:10px; margin-top:16px; }
    .detail-btn-like, .detail-btn-save {
      flex:1; display:flex; align-items:center; justify-content:center; gap:6px;
      padding:9px 14px; border-radius:8px; border:1.5px solid #e4e5f0;
      background:#fff; font-family:'Nunito',sans-serif;
      font-size:.8rem; font-weight:700; color:var(--muted);
      cursor:pointer; transition:all var(--trans);
    }
    .detail-btn-like svg, .detail-btn-save svg { width:15px; height:15px; }
    .detail-btn-like:hover { border-color:#e74c3c; color:#e74c3c; }
    .detail-btn-like.aktif { background:#fef2f2; border-color:#e74c3c; color:#e74c3c; }
    .detail-btn-save:hover { border-color:var(--accent); color:var(--accent); }
    .detail-btn-save.aktif { background:#eef0ff; border-color:var(--accent); color:var(--accent); }
    .detail-btn-pinjam {
      width:100%; display:inline-flex; align-items:center; justify-content:center; gap:8px;
      padding:11px 16px; border-radius:10px; border:none;
      background:linear-gradient(135deg, #d8b878 0%, #b89758 100%);
      color:#090c10; font-family:var(--font-family,'Outfit',sans-serif);
      font-size:.85rem; font-weight:800; cursor:pointer;
      box-shadow:0 4px 16px rgba(216,184,120,.25);
      transition:all .2s cubic-bezier(.22,1,.36,1);
    }
    .detail-btn-pinjam svg { width:17px; height:17px; flex-shrink:0; }
    .detail-btn-pinjam:hover:not(:disabled) {
      transform:translateY(-2px);
      box-shadow:0 6px 22px rgba(216,184,120,.45);
    }
    .detail-btn-pinjam:disabled, .detail-btn-pinjam.disabled {
      opacity:.45; cursor:not-allowed; background:rgba(255,255,255,.08); color:var(--muted);
      box-shadow:none; transform:none;
    }
  </style>
  <?php require_once "settings_include.php"; ?>
</head>
<body>

<header class="mobile-topbar" id="mobileTopbar">
  <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="3" y1="6" x2="21" y2="6"/>
      <line x1="3" y1="12" x2="21" y2="12"/>
      <line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  </button>
  <span class="mobile-topbar-divider" aria-hidden="true"></span>
  <div class="mobile-topbar-brand">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
      <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
      <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
    </svg>
    <span>AKSA NOVA</span>
  </div>

  <div class="page-hud-controls" id="pageHudControls">
    <?php if ($musik_tampil): ?>
    <button type="button" class="btn-musik" id="btnMusik" aria-label="Musik Latar" title="<?= htmlspecialchars($musik_judul) ?>">
      <svg class="icon-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>
      </svg>
      <span class="icon-eq" aria-hidden="true"><span></span><span></span><span></span></span>
    </button>
    <audio id="audioLatar" loop autoplay muted preload="auto">
      <source src="<?= htmlspecialchars($musik_file) ?>">
    </audio>
    <?php endif; ?>
  </div>
</header>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
  <div class="logo-wrap">
    <div class="logo-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
      </svg>
    </div>
    <div class="logo-name">AKSA NOVA</div>
    <div class="logo-sub">Library Catalog App</div>
  </div>

  <nav class="nav">
    <a href="beranda.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Beranda
    </a>
    <?php if (!$is_admin && !$is_guest): ?>
    <a href="dashboard_user.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
      Dashboard
    </a>
    <?php endif; ?>
    <a href="daftar_buku.php" class="nav-item active">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
      Daftar Buku
    </a>
    <?php if (!$is_admin && !$is_guest): ?>
    <a href="pengajuan_peminjaman.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
      Ajukan Pinjam
    </a>
    <a href="buku_simpan.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
      Buku Simpan
    </a>
    <a href="edit_kartu.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      Edit Profil
    </a>
    <?php endif; ?>
    <?php if ($is_admin): ?>
    <a href="halaman_admin.php" class="nav-item admin-only">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Action Admin
    </a>
    <?php endif; ?>
  </nav>

  <div class="nav-bottom">
    <a href="#" class="nav-item"  onclick="bukaSettings(); return false;" title="Pengaturan Tampilan">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
      Pengaturan
    </a>
    <?php if ($is_guest): ?>
    <a href="sign_in.php" class="nav-item" style="color:var(--accent);">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
      Masuk
    </a>
    <a href="sign_up.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="17" y1="11" x2="23" y2="11"/></svg>
      Daftar
    </a>
    <?php else: ?>
    <a href="logout.php" class="nav-item" style="color:#e74c3c;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Keluar
    </a>
    <?php endif; ?>
  </div>
</aside>

<main class="main">

  <!-- Search bar -->
  <form method="GET" action="daftar_buku.php" id="searchForm">
    <div class="topbar">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" name="q" id="searchInput" placeholder="Cari berdasarkan judul, penulis, atau ISBN…" value="<?= htmlspecialchars($search) ?>" autocomplete="off"/>
      </div>
      <div class="kategori-dropdown" id="kategoriDropdown">
        <button type="button" class="kategori-trigger <?= $kategori !== "" ? "has-value" : "" ?>" id="kategoriTrigger" aria-haspopup="listbox" aria-expanded="false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41 11 3.83A2 2 0 0 0 9.58 3.24H4a1 1 0 0 0-1 1v5.58a2 2 0 0 0 .59 1.42l9.58 9.58a2 2 0 0 0 2.83 0l7.59-7.59a2 2 0 0 0 0-2.82Z"/><circle cx="7.5" cy="7.5" r="1"/></svg>
          <span id="kategoriTriggerLabel"><?= $kategori !== "" ? htmlspecialchars($kategori) : "Semua Kategori" ?></span>
          <svg class="kategori-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="kategori-panel" id="kategoriPanel" role="listbox">
          <button type="button" class="kategori-chip <?= $kategori === "" ? "active" : "" ?>" data-value="">Semua Kategori</button>
          <?php foreach ($kategori_list as $g): ?>
            <button type="button" class="kategori-chip <?= $kategori === $g ? "active" : "" ?>" data-value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></button>
          <?php endforeach; ?>
        </div>
        <select name="kategori" id="kategoriFilter" class="kategori-select-sr" aria-label="Filter kategori buku">
          <option value="">Semua Kategori</option>
          <?php foreach ($kategori_list as $g): ?>
            <option value="<?= htmlspecialchars($g) ?>" <?= $kategori === $g ? "selected" : "" ?>><?= htmlspecialchars($g) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($search || $kategori): ?><a href="daftar_buku.php" class="tab-btn" id="searchResetBtn">✕ Reset</a><?php endif; ?>
    </div>
  </form>

  <div id="searchResultArea">
  <?php ob_start(); ?>
  <div class="page-header">
    <div class="page-title">Daftar Buku</div>
    <div class="page-subtitle">
      <?php if ($search && $kategori): ?>
        <?= $total_buku ?> buku cocok dengan "<strong><?= htmlspecialchars($search) ?></strong>" dalam kategori <strong><?= htmlspecialchars($kategori) ?></strong>
      <?php elseif ($search): ?>
        <?= $total_buku ?> buku cocok dengan "<strong><?= htmlspecialchars($search) ?></strong>"
      <?php elseif ($kategori): ?>
        <?= $total_buku ?> buku dalam kategori <strong><?= htmlspecialchars($kategori) ?></strong>
      <?php else: ?>
        <?= $total_buku ?> buku tersedia di katalog perpustakaan
      <?php endif; ?>
    </div>
  </div>

  <?php if (empty($buku_list)): ?>
    <div class="empty-state">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      <div class="empty-title">Tidak ada buku ditemukan</div>
      <div class="empty-sub">
        <?php if ($search): ?>
          Tidak ada buku yang cocok dengan "<?= htmlspecialchars($search) ?>"<?= $kategori ? " dalam kategori \"" . htmlspecialchars($kategori) . "\"" : "" ?>.
        <?php elseif ($kategori): ?>
          Belum ada buku dalam kategori "<?= htmlspecialchars($kategori) ?>".
        <?php else: ?>
          Belum ada buku di katalog.
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="books-grid">
      <?php foreach ($buku_list as $i => $buku):
        $num   = $offset + $i + 1;
        $col   = $cover_cls[$i % count($cover_cls)];
        [$status_cls, $status_label] = getStatus((int)$buku["stok"]);
        $words   = preg_split('/\s+/', trim($buku["judul"]));
        $initial = mb_strtoupper(mb_substr($words[0], 0, 1)) . (isset($words[1]) ? mb_strtoupper(mb_substr($words[1], 0, 1)) : "");
        $rat = $rating_counts[$buku["id"]] ?? null;
      ?>
      <div class="book-card" onclick="bukaDetailBuku(<?= $buku['id'] ?>)" id="card_buku_<?= $buku['id'] ?>">
        <div class="book-cover-wrap <?= $col ?>">
          <?php if ($buku["gambar"] && file_exists($buku["gambar"])): ?>
            <img src="<?= htmlspecialchars($buku["gambar"]) ?>" alt="<?= htmlspecialchars($buku["judul"]) ?>">
          <?php else: ?>
            <div class="cover-initial"><?= htmlspecialchars($initial) ?></div>
          <?php endif; ?>
          <span class="cover-num-badge"><?= str_pad($num, 2, "0", STR_PAD_LEFT) ?></span>
          <?php if ($rat && $rat["total"] > 0): ?>
          <div class="cover-rating">
            <svg viewBox="0 0 24 24" fill="#ffb800" stroke="#ffb800" stroke-width="1"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <?= $rat["avg"] ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="book-info">
          <div class="bk-title"><?= htmlspecialchars($buku["judul"]) ?></div>
          <div class="bk-author"><?= htmlspecialchars($buku["penulis"] ?: "—") ?></div>
          <div class="bk-badges-row">
            <?php if ($buku["genre"]): ?><span class="bk-genre"><?= htmlspecialchars($buku["genre"]) ?></span><?php endif; ?>
            <span class="status-badge status-<?= $status_cls ?>"><span class="dot"></span><?= $status_label ?> · <?= (int)$buku["stok"] ?></span>
          </div>
          <?php if (!$is_admin):
            $sudah_like = in_array($buku["id"], $liked_ids);
            $sudah_fav  = in_array($buku["id"], $fav_ids);
            $jml_like   = $like_counts[$buku["id"]] ?? 0;
          ?>
          <div class="bk-actions" onclick="event.stopPropagation()">
            <button class="btn-like <?= $sudah_like ? 'aktif' : '' ?>"
                    data-buku-id="<?= $buku['id'] ?>"
                    onclick="toggleAksi(this, <?= $buku['id'] ?>, 'like')"
                    title="Suka" aria-label="Suka">
              <svg viewBox="0 0 24 24" fill="<?= $sudah_like ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
              </svg>
              <span class="like-count-badge like-count-<?= $buku['id'] ?>"><?= $jml_like ?></span>
            </button>
            <button class="btn-save <?= $sudah_fav ? 'aktif' : '' ?>"
                    data-buku-id="<?= $buku['id'] ?>"
                    onclick="toggleAksi(this, <?= $buku['id'] ?>, 'favorite')"
                    title="Simpan" aria-label="Simpan">
              <svg viewBox="0 0 24 24" fill="<?= $sudah_fav ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2">
                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
              </svg>
            </button>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

    <!-- Pagination -->
    <?php
    $from = $total_buku === 0 ? 0 : $offset + 1;
    $to   = min($offset + $per_page, $total_buku);
    $qs   = "";
    if ($search)   $qs .= "&q=" . urlencode($search);
    if ($kategori) $qs .= "&kategori=" . urlencode($kategori);
    ?>
    <?php if ($total_buku > 0): ?>
    <div class="pagination-wrap">
      <div class="pagination-info">Menampilkan <?= $from ?>–<?= $to ?> dari <?= $total_buku ?> buku</div>
      <div class="pagination-btns">
        <!-- Prev -->
        <?php if ($page > 1): ?>
          <a href="?page=<?= $page - 1 . $qs ?>" class="page-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
          </a>
        <?php endif; ?>

        <?php
        // Show a window of pages
        $start = max(1, $page - 2);
        $end   = min($total_pages, $page + 2);
        for ($p = $start; $p <= $end; $p++):
        ?>
          <a href="?page=<?= $p . $qs ?>" class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>

        <!-- Next -->
        <?php if ($page < $total_pages): ?>
          <a href="?page=<?= $page + 1 . $qs ?>" class="page-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
          </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  <?php
  $search_result_html = ob_get_clean(); // buffer dalam (khusus area hasil pencarian)
  if ($is_ajax) {
      ob_end_clean(); // buang seluruh buffer luar (DOCTYPE, sidebar, dll — belum sempat dikirim ke browser)
      header("Content-Type: text/html; charset=utf-8");
      echo $search_result_html;
      exit;
  }
  echo $search_result_html;
  ?>
  </div>

</main>

<!-- ═══════════ MODAL DETAIL BUKU ═══════════ -->
<div class="detail-overlay" id="detailOverlay">
  <div class="detail-modal">
    <div id="detailContent">
      <div class="detail-loading">
        <div class="spinner"></div>
        <span>Memuat detail buku…</span>
      </div>
    </div>
  </div>
</div>

<script>
  const toggle  = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  if (toggle && sidebar && overlay) {
    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      overlay.classList.toggle('open');
      document.body.classList.toggle('sidebar-open', sidebar.classList.contains('open'));
    });
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('open');
      overlay.classList.remove('open');
      document.body.classList.remove('sidebar-open');
    });
  }

  const IS_ADMIN = <?= json_encode($is_admin) ?>;
  const IS_GUEST = <?= json_encode($is_guest) ?>;

  // Aksi yang butuh login (suka, simpan, rating, pinjam) — arahkan tamu ke form login
  function butuhLogin() {
    window.location.href = 'sign_in.php';
    return true;
  }

  // ─── Modal Detail Buku ───
  function bukaDetailBuku(id) {
    const detailOverlay = document.getElementById('detailOverlay');
    const content = document.getElementById('detailContent');
    content.innerHTML = `<div class="detail-loading"><div class="spinner"></div><span>Memuat detail buku…</span></div>`;
    detailOverlay.classList.add('open');

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

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') tutupDetailBuku();
  });

  function renderDetail(b) {
    const content = document.getElementById('detailContent');
    const stokLabel = b.stok == 0 ? 'Habis' : (b.stok <= 3 ? 'Terbatas' : 'Tersedia');
    const stokColor = b.stok == 0 ? '#e74c3c' : (b.stok <= 3 ? '#f39c12' : '#27ae60');
    const tglInput = b.created_at
      ? new Date(b.created_at).toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'})
      : '—';
    const tglUpdate = b.updated_at && b.updated_at !== b.created_at
      ? new Date(b.updated_at).toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'})
      : null;

    const coverHTML = b.gambar
      ? `<img src="${escHTML(b.gambar)}" alt="${escHTML(b.judul)}">`
      : `<div class="detail-cover-placeholder"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>`;

    // Tombol aksi bawah modal — hanya untuk user (bukan admin)
    const btnPinjamHTML = b.stok > 0
      ? `<button class="detail-btn-pinjam" onclick="ajukanPinjamBuku(${b.id})">
           <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
           Ajukan Peminjaman
         </button>`
      : `<button class="detail-btn-pinjam disabled" disabled title="Stok buku habis">
           <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
           Stok Buku Habis
         </button>`;

    const userActionBtns = IS_ADMIN ? '' : `
      <div style="margin-top:16px;">
        ${btnPinjamHTML}
        <div class="detail-footer-btns" style="margin-top:8px;">
          <button id="modalLikeBtn_${b.id}" class="detail-btn-like ${b.user_like ? 'aktif' : ''}" onclick="toggleLikeModal(this, ${b.id})">
            <svg viewBox="0 0 24 24" fill="${b.user_like ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <span id="modalLikeLabel_${b.id}">${b.user_like ? 'Disukai' : 'Suka'}</span>
          </button>
          <button id="modalSaveBtn_${b.id}" class="detail-btn-save ${b.user_favorit ? 'aktif' : ''}" onclick="toggleSimpanModal(this, ${b.id})">
            <svg viewBox="0 0 24 24" fill="${b.user_favorit ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
            <span id="modalSaveLabel_${b.id}">${b.user_favorit ? 'Tersimpan' : 'Simpan'}</span>
          </button>
        </div>
      </div>`;

    content.innerHTML = `
      <div class="detail-cover">
        ${coverHTML}
        <button class="detail-close-btn" onclick="tutupDetailBuku()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        ${b.genre ? `<span class="detail-cover-badge">${escHTML(b.genre)}</span>` : ''}
      </div>
      <div class="detail-body">
        ${b.genre ? `<div class="detail-genre-chip">${escHTML(b.genre)}</div>` : ''}
        <div class="detail-title">${escHTML(b.judul)}</div>
        <div class="detail-author">${b.penulis ? '✍️ ' + escHTML(b.penulis) : 'Penulis tidak diketahui'}</div>

        <div class="detail-stat-row">
          <div class="detail-stat likes" id="statLike_${b.id}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <span id="likeCount_${b.id}">${b.jumlah_like}</span> Suka
          </div>
          <div class="detail-stat favs">
            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            ${b.jumlah_favorit} Favorit
          </div>
        </div>

        <div class="detail-rating-row" id="ratingRow_${b.id}">
          ${renderStarsDisplay(b.rating_avg, b.rating_total)}
          <div style="border-left:1px solid var(--border-color,rgba(216,184,120,.2));height:16px;"></div>
          <span style="font-size:.72rem;font-weight:700;color:var(--muted);">Nilai kamu:</span>
          ${renderStarsInput(b.id, b.user_rating)}
        </div>

        <div class="detail-meta-row">
          ${b.isbn ? `<div class="detail-meta-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>ISBN: <span>${escHTML(b.isbn)}</span></div>` : ''}
          <div class="detail-meta-chip">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            Stok: <span style="color:${stokColor};font-weight:800;">${escHTML(String(b.stok))} — ${stokLabel}</span>
          </div>
          <div class="detail-meta-chip">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Ditambahkan: <span>${tglInput}</span>
          </div>
          ${tglUpdate ? `<div class="detail-meta-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Diperbarui: <span>${tglUpdate}</span></div>` : ''}
        </div>

        <div class="detail-section-label">Sinopsis / Ringkasan</div>
        ${b.sinopsis
          ? `<div class="detail-sinopsis">${escHTML(b.sinopsis).replace(/\n/g,'<br>')}</div>`
          : `<div class="detail-sinopsis"><span class="detail-sinopsis-empty">Sinopsis belum tersedia untuk buku ini.</span></div>`}
        ${userActionBtns}
      </div>`;
  }

  function toggleLikeModal(btn, bukuId) {
    if (IS_GUEST) return butuhLogin();
    const fd = new FormData();
    fd.append('buku_id', bukuId);
    fd.append('type', 'like');
    fetch('like_handler.php', {method:'POST', body:fd})
      .then(r => r.json())
      .then(data => {
        if (!data.ok) return;
        btn.classList.toggle('aktif', data.aktif);
        btn.querySelector('svg').setAttribute('fill', data.aktif ? 'currentColor' : 'none');
        const label = document.getElementById(`modalLikeLabel_${bukuId}`);
        if (label) label.textContent = data.aktif ? 'Disukai' : 'Suka';
        const countEl = document.getElementById(`likeCount_${bukuId}`);
        if (countEl) countEl.textContent = data.total;
        // Sinkron tombol di tabel
        const tblBtn = document.querySelector(`.btn-like[data-buku-id="${bukuId}"]`);
        if (tblBtn) {
          tblBtn.classList.toggle('aktif', data.aktif);
          tblBtn.querySelector('svg').setAttribute('fill', data.aktif ? 'currentColor' : 'none');
          const sp = tblBtn.querySelector(`span.like-count-${bukuId}`);
          if (sp) sp.textContent = data.total;
        }
      });
  }

  function ajukanPinjamBuku(bukuId) {
    if (IS_GUEST) {
      if (typeof butuhLogin === 'function') {
        butuhLogin();
      } else {
        window.location.href = 'sign_in.php?redirect=' + encodeURIComponent('pengajuan_peminjaman.php?buku_id=' + bukuId);
      }
      return;
    }
    window.location.href = 'pengajuan_peminjaman.php?buku_id=' + bukuId;
  }

  // ─── Live Search (ketik langsung cari, tanpa tombol) ───
  (function initLiveSearch() {
    const form    = document.getElementById('searchForm');
    const input   = document.getElementById('searchInput');
    const select  = document.getElementById('kategoriFilter');
    const result  = document.getElementById('searchResultArea');
    const ddWrap  = document.getElementById('kategoriDropdown');
    const trigger = document.getElementById('kategoriTrigger');
    const label   = document.getElementById('kategoriTriggerLabel');
    const panel   = document.getElementById('kategoriPanel');
    if (!form || !input || !result) return;

    let debounceTimer = null;
    let currentRequest = null;

    // Cegah submit form biasa (fallback lama), live search yang ambil alih
    form.addEventListener('submit', e => e.preventDefault());

    function runSearch(query, kategori) {
      // Batalkan request sebelumnya yang belum selesai, biar hasil tidak tertukar
      if (currentRequest) currentRequest.abort();
      const controller = new AbortController();
      currentRequest = controller;

      const params = new URLSearchParams({ ajax: '1' });
      if (query)    params.set('q', query);
      if (kategori) params.set('kategori', kategori);

      result.classList.add('loading-search');

      fetch('daftar_buku.php?' + params.toString(), { signal: controller.signal })
        .then(r => r.text())
        .then(html => {
          result.innerHTML = html;
          result.classList.remove('loading-search');
          // Perbarui URL browser tanpa reload halaman
          const viewParams = new URLSearchParams();
          if (query)    viewParams.set('q', query);
          if (kategori) viewParams.set('kategori', kategori);
          const qs = viewParams.toString();
          history.replaceState(null, '', 'daftar_buku.php' + (qs ? '?' + qs : ''));
        })
        .catch(err => {
          if (err.name !== 'AbortError') result.classList.remove('loading-search');
        });
    }

    input.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      const query = input.value;
      debounceTimer = setTimeout(() => runSearch(query, select ? select.value : ''), 300);
    });

    // ─── Dropdown kategori bergaya chip ───
    function closeKategoriPanel() {
      if (!panel || !trigger) return;
      panel.classList.remove('open');
      trigger.classList.remove('open');
      trigger.setAttribute('aria-expanded', 'false');
    }
    function openKategoriPanel() {
      if (!panel || !trigger) return;
      panel.classList.add('open');
      trigger.classList.add('open');
      trigger.setAttribute('aria-expanded', 'true');
    }
    // Sumber kebenaran tunggal: set nilai kategori, sinkronkan tampilan trigger + chip, lalu cari (opsional)
    function setKategori(value, opts) {
      opts = opts || {};
      if (select) select.value = value;
      if (label)  label.textContent = value === '' ? 'Semua Kategori' : value;
      if (trigger) trigger.classList.toggle('has-value', value !== '');
      if (panel) {
        panel.querySelectorAll('.kategori-chip').forEach(function (c) {
          c.classList.toggle('active', c.dataset.value === value);
        });
      }
      if (opts.search !== false) runSearch(input.value, value);
    }

    if (trigger && panel && ddWrap) {
      trigger.addEventListener('click', () => {
        panel.classList.contains('open') ? closeKategoriPanel() : openKategoriPanel();
      });
      panel.querySelectorAll('.kategori-chip').forEach(chip => {
        chip.addEventListener('click', () => {
          setKategori(chip.dataset.value);
          closeKategoriPanel();
        });
      });
      // Klik di luar dropdown / tombol Escape menutup panel
      document.addEventListener('click', e => {
        if (!ddWrap.contains(e.target)) closeKategoriPanel();
      });
      document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeKategoriPanel();
      });
    }

    // Dukungan keyboard/aksesibilitas: <select> tersembunyi tetap bisa dioperasikan
    if (select) {
      select.addEventListener('change', () => setKategori(select.value));
    }

    // Tombol reset (jika ada) juga langsung mengosongkan hasil tanpa reload
    document.addEventListener('click', e => {
      const resetBtn = e.target.closest('#searchResetBtn');
      if (!resetBtn) return;
      e.preventDefault();
      input.value = '';
      setKategori('', { search: false });
      runSearch('', '');
    });
  })();

  function escHTML(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ─── Rating helpers ───
  function starSVG(filled) {
    const f = filled ? '#f5a623' : 'none';
    return `<svg viewBox="0 0 24 24" fill="${f}" stroke="#f5a623" stroke-width="2" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>`;
  }

  function renderStarsDisplay(avg, total) {
    let stars = '';
    for (let i = 1; i <= 5; i++) stars += starSVG(i <= Math.round(avg));
    const label = total > 0 ? `${avg} (${total} ulasan)` : 'Belum ada rating';
    return `<div class="stars-display">${stars}</div><span class="rating-text">${label}</span>`;
  }

  function renderStarsInput(bukuId, userRating) {
    let html = `<div class="stars-input" id="starsInput_${bukuId}">`;
    for (let i = 1; i <= 5; i++) {
      html += `<svg viewBox="0 0 24 24" fill="${i <= userRating ? '#f5a623' : 'none'}" stroke="#f5a623" stroke-width="2" stroke-linejoin="round"
        class="${i <= userRating ? 'aktif' : ''}"
        onmouseover="hoverStar(${bukuId},${i})"
        onmouseout="resetStarHover(${bukuId})"
        onclick="submitRating(${bukuId},${i})">
        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
      </svg>`;
    }
    return html + '</div>';
  }

  function hoverStar(bukuId, n) {
    const stars = document.querySelectorAll(`#starsInput_${bukuId} svg`);
    stars.forEach((s, i) => {
      s.setAttribute('fill', i < n ? '#f5a623' : 'none');
      s.classList.toggle('hover', i < n);
    });
  }

  function resetStarHover(bukuId) {
    const stars = document.querySelectorAll(`#starsInput_${bukuId} svg`);
    stars.forEach(s => {
      s.classList.remove('hover');
      s.setAttribute('fill', s.classList.contains('aktif') ? '#f5a623' : 'none');
    });
  }

  function submitRating(bukuId, rating) {
    if (IS_GUEST) return butuhLogin();
    const fd = new FormData();
    fd.append('buku_id', bukuId);
    fd.append('rating', rating);
    fetch('rating_handler.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (!data.ok) return;
        const stars = document.querySelectorAll(`#starsInput_${bukuId} svg`);
        stars.forEach((s, i) => {
          const on = i < data.user_rating;
          s.setAttribute('fill', on ? '#f5a623' : 'none');
          s.classList.toggle('aktif', on);
        });
        const row = document.getElementById(`ratingRow_${bukuId}`);
        if (row) {
          const disp = row.querySelector('.stars-display');
          const txt  = row.querySelector('.rating-text');
          if (disp && txt) {
            let s = '';
            for (let i = 1; i <= 5; i++) s += starSVG(i <= Math.round(data.avg));
            disp.innerHTML = s;
            txt.textContent = `${data.avg} (${data.total} ulasan)`;
          }
        }
      });
  }
  function toggleSimpanModal(btn, buku_id) {
    if (IS_GUEST) return butuhLogin();
    const fd = new FormData();
    fd.append('buku_id', buku_id);
    fd.append('type', 'favorite');
    fetch('like_handler.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (!data.ok) return;
        btn.classList.toggle('aktif', data.aktif);
        btn.querySelector('svg').setAttribute('fill', data.aktif ? 'currentColor' : 'none');
        const label = document.getElementById(`modalSaveLabel_${buku_id}`);
        if (label) label.textContent = data.aktif ? 'Tersimpan' : 'Simpan';
        // Sinkronkan tombol di tabel jika ada
        const tableBtn = document.querySelector(`.btn-save[data-buku-id="${buku_id}"]`);
        if (tableBtn) {
          tableBtn.classList.toggle('aktif', data.aktif);
          const tSvg = tableBtn.querySelector('svg');
          if (tSvg) tSvg.setAttribute('fill', data.aktif ? 'currentColor' : 'none');
        }
      });
  }
  function toggleAksi(btn, buku_id, type) {
    if (IS_GUEST) return butuhLogin();
    const fd = new FormData();
    fd.append('buku_id', buku_id);
    fd.append('type', type);

    fetch('like_handler.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (!data.ok) return;
        btn.classList.toggle('aktif', data.aktif);
        const svg = btn.querySelector('svg');
        svg.setAttribute('fill', data.aktif ? 'currentColor' : 'none');
        btn.classList.add('pop');
        setTimeout(() => btn.classList.remove('pop'), 200);
        const span = btn.querySelector('span');
        if (span) span.textContent = data.total;

        // Sinkronkan ke modal jika sedang terbuka
        if (type === 'like') {
          const modalBtn = document.getElementById(`modalLikeBtn_${buku_id}`);
          if (modalBtn) {
            modalBtn.classList.toggle('aktif', data.aktif);
            modalBtn.querySelector('svg').setAttribute('fill', data.aktif ? 'currentColor' : 'none');
            const lbl = document.getElementById(`modalLikeLabel_${buku_id}`);
            if (lbl) lbl.textContent = data.aktif ? 'Disukai' : 'Suka';
          }
          const countEl = document.getElementById(`likeCount_${buku_id}`);
          if (countEl) countEl.textContent = data.total;
        }
        if (type === 'favorite') {
          const modalBtn = document.getElementById(`modalSaveBtn_${buku_id}`);
          if (modalBtn) {
            modalBtn.classList.toggle('aktif', data.aktif);
            modalBtn.querySelector('svg').setAttribute('fill', data.aktif ? 'currentColor' : 'none');
            const lbl = document.getElementById(`modalSaveLabel_${buku_id}`);
            if (lbl) lbl.textContent = data.aktif ? 'Tersimpan' : 'Simpan';
          }
        }
      });
  }



  // ─── Musik Latar (Dikelola terpusat oleh AksaAudio di settings_include.php) ───
  if (window.AksaAudio) window.AksaAudio.init();
</script>
<?php require_once "pengaturan_panel.php"; ?>
</body>
</html>
<?php ob_end_flush(); ?>