<?php
// beranda.php — Halaman dashboard utama AKSA NOVA (dinamis & responsif)
session_start();

require_once "db.php";

// ─── Mode tamu: pengunjung boleh langsung melihat beranda tanpa login ───
$is_guest    = !isset($_SESSION["user_id"]);
$user_id_int = $is_guest ? 0 : (int)$_SESSION["user_id"];
$user_name   = $is_guest ? "Tamu" : ($_SESSION["user_name"] ?? "Pengguna");
$role        = $is_guest ? "guest" : ($_SESSION["role"] ?? "member");
$is_admin    = (!$is_guest && $role === "admin");
$admin_role  = $is_admin ? "Admin" : "Member";
$page_title  = "Dashboard – AKSA NOVA";

// ─── Trending (5 buku dengan like terbanyak) ───
$trending = [];
$res = mysqli_query($conn,
    "SELECT b.*, COUNT(l.id) AS jumlah_like
     FROM buku b
     LEFT JOIN buku_likes l ON l.buku_id = b.id
     GROUP BY b.id
     ORDER BY jumlah_like DESC, b.id ASC
     LIMIT 5"
);
while ($row = mysqli_fetch_assoc($res)) $trending[] = $row;

// ─── New (10 buku terbaru) ───
$new_books = [];
$res = mysqli_query($conn, "SELECT * FROM buku ORDER BY created_at DESC, id DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($res)) $new_books[] = $row;

// ─── Total buku ───
$total_res  = mysqli_query($conn, "SELECT COUNT(*) AS total FROM buku");
$total_row  = mysqli_fetch_assoc($total_res);
$total_buku = (int)($total_row["total"] ?? 0);

// ─── Kategori / genre populer ───
$genre_populer = [];
$res_genre = mysqli_query($conn,
    "SELECT genre, COUNT(*) AS jumlah FROM buku
     WHERE genre IS NOT NULL AND genre != ''
     GROUP BY genre ORDER BY jumlah DESC LIMIT 6"
);
if ($res_genre) { while ($row = mysqli_fetch_assoc($res_genre)) $genre_populer[] = $row; }

// ─── Sapaan dinamis berdasarkan jam ───
$jam = (int)date("H");
if ($jam >= 4 && $jam < 11)      { $sapaan = "Selamat pagi"; }
elseif ($jam >= 11 && $jam < 15) { $sapaan = "Selamat siang"; }
elseif ($jam >= 15 && $jam < 18) { $sapaan = "Selamat sore"; }
else                              { $sapaan = "Selamat malam"; }

// ─── Kutipan literasi hari ini ───
$kutipan_list = [
    ["teks" => "Sebuah kamar tanpa buku adalah seperti tubuh tanpa jiwa.", "oleh" => "Marcus Tullius Cicero"],
    ["teks" => "Membaca adalah jendela dunia yang bisa dibuka kapan saja.", "oleh" => "Pramoedya Ananta Toer"],
    ["teks" => "Buku yang baik adalah teman terbaik, hari ini dan selamanya.", "oleh" => "Anonim"],
    ["teks" => "Hari ini seorang pembaca, esok seorang pemimpin.", "oleh" => "Margaret Fuller"],
    ["teks" => "Tidak ada teman sesetia buku.", "oleh" => "Ernest Hemingway"],
    ["teks" => "Buku adalah kapal pikiran yang mengembara di lautan waktu.", "oleh" => "Francis Bacon"],
    ["teks" => "Satu buku, satu pena, satu anak, dan satu guru dapat mengubah dunia.", "oleh" => "Malala Yousafzai"],
];
$kutipan_hari_ini = $kutipan_list[(int)date("z") % count($kutipan_list)];

$thumb_colors = ["c1","c2","c3","c4","c5","c6","c7","c8"];

// ─── Rating rata-rata untuk trending & new books ───
$rating_avg = [];
$all_ids = array_unique(array_merge(
    array_column($trending,  "id"),
    array_column($new_books, "id")
));
if (!empty($all_ids)) {
    $ids_str = implode(",", array_map("intval", $all_ids));
    $rr = mysqli_query($conn,
        "SELECT buku_id, ROUND(AVG(rating),1) AS avg_r, COUNT(*) AS total
         FROM buku_ratings WHERE buku_id IN ($ids_str) GROUP BY buku_id"
    );
    if ($rr) {
        while ($row = mysqli_fetch_assoc($rr)) {
            $rating_avg[$row["buku_id"]] = ["avg" => (float)$row["avg_r"], "total" => (int)$row["total"]];
        }
    }
}

// ─── Banner carousel (diunggah admin) ───
$banners = [];
$res_banner = mysqli_query($conn, "SELECT * FROM banner WHERE aktif = 1 ORDER BY urutan ASC, id ASC");
if ($res_banner) { while ($row = mysqli_fetch_assoc($res_banner)) $banners[] = $row; }

// ─── Profil admin (untuk semua user) ───
$profil_res  = mysqli_query($conn, "SELECT * FROM admin_profile LIMIT 1");
$profil      = $profil_res ? mysqli_fetch_assoc($profil_res) : null;
$profil_nama = $profil["display_name"] ?? $user_name;
$profil_foto = $profil["foto"] ?? "";

// ─── Profil user yang sedang login ───
$my_profile = null;
if (!$is_guest && !$is_admin) {
    $res_my = mysqli_query($conn, "SELECT full_name, foto, no_anggota, kelas FROM users WHERE id = $user_id_int");
    if ($res_my) $my_profile = mysqli_fetch_assoc($res_my);
}

// ─── Buku yang disimpan user (favorites) ───
$saved_books = [];
if (!$is_admin && !$is_guest) {
    $res_saved = mysqli_query($conn,
        "SELECT b.* FROM buku b
         INNER JOIN buku_favorites f ON f.buku_id = b.id
         WHERE f.user_id = $user_id_int
         ORDER BY f.id DESC LIMIT 6"
    );
    if ($res_saved) {
        while ($row = mysqli_fetch_assoc($res_saved)) $saved_books[] = $row;
    }
}

// ─── Statistik peminjaman user ───
$total_pinjam_user        = 0;
$sedang_dipinjam_user     = 0;
$belum_dikembalikan_user  = 0;
if (!$is_admin && !$is_guest) {
    $res_total_pinjam = mysqli_query($conn, "SELECT COUNT(*) AS total FROM peminjaman WHERE user_id = $user_id_int");
    if ($res_total_pinjam) $total_pinjam_user = (int)(mysqli_fetch_assoc($res_total_pinjam)["total"] ?? 0);

    $res_sedang = mysqli_query($conn, "SELECT COUNT(*) AS total FROM peminjaman WHERE user_id = $user_id_int AND status = 'dipinjam' AND batas_kembali >= NOW()");
    if ($res_sedang) $sedang_dipinjam_user = (int)(mysqli_fetch_assoc($res_sedang)["total"] ?? 0);

    $res_belum = mysqli_query($conn, "SELECT COUNT(*) AS total FROM peminjaman WHERE user_id = $user_id_int AND status = 'dipinjam' AND batas_kembali < NOW()");
    if ($res_belum) $belum_dikembalikan_user = (int)(mysqli_fetch_assoc($res_belum)["total"] ?? 0);
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
  <title><?= htmlspecialchars($page_title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Nunito:wght@300;400;600;700;800&family=Cormorant+Garamond:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet"/>
  <?php require_once "settings_include.php"; ?>

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
    
    html, body {
      width:100%;
      max-width:100%;
      overflow-x:hidden;
      position:relative;
    }

    body {
      font-family:var(--font-family,'Outfit',sans-serif);
      background:
        radial-gradient(circle at 100% 0%, rgba(216,184,120,.05) 0%, transparent 45%),
        radial-gradient(circle at 0% 100%, rgba(216,184,120,.03) 0%, transparent 40%),
        var(--bg);
      color:var(--text);
      min-height:100vh;
      display:flex;
    }

    /* ── SIDEBAR ── */
    .sidebar {
      width:var(--sidebar-w); height:100vh; height:100dvh;
      background:var(--sidebar-bg);
      display:flex; flex-direction:column;
      padding:24px 0 20px;
      border-right:1px solid var(--border-color, rgba(216,184,120,.15));
      position:fixed; top:0; left:0; bottom:0;
      z-index:170; transition:transform var(--trans);
      overflow-y:auto; -webkit-overflow-scrolling:touch; scrollbar-width:thin;
      box-shadow:2px 0 24px rgba(0,0,0,.35);
    }
    .sidebar-toggle {
      display:none; position:fixed;
      top:14px; left:14px; z-index:200;
      width:42px; height:42px; border-radius:12px;
      border:1px solid var(--border-color, rgba(216,184,120,.2)); background:var(--card, #121820);
      box-shadow:0 4px 16px rgba(0,0,0,.3);
      cursor:pointer; align-items:center; justify-content:center;
      transition:transform .15s ease;
    }
    .sidebar-toggle:active { transform:scale(.9); }
    .sidebar-toggle svg { width:20px; height:20px; color:var(--accent); }

    .sidebar-overlay {
      position:fixed; inset:0;
      background:rgba(9,12,16,.65); backdrop-filter:blur(3px);
      z-index:165; opacity:0; visibility:hidden;
      transition:opacity var(--trans), visibility var(--trans);
    }
    .sidebar-overlay.open { opacity:1; visibility:visible; }

    .logo-wrap {
      display:flex; flex-direction:column; align-items:center;
      padding:0 18px 24px; border-bottom:1px solid var(--border-color, rgba(216,184,120,.15));
    }
    .logo-icon {
      width:52px; height:52px;
      background:linear-gradient(135deg, rgba(216,184,120,.18) 0%, rgba(216,184,120,.05) 100%);
      border:1px solid rgba(216,184,120,.3);
      border-radius:14px; display:flex; align-items:center; justify-content:center;
      margin-bottom:8px; box-shadow:0 4px 16px rgba(0,0,0,.3);
    }
    .logo-icon svg { width:28px; height:28px; color:var(--accent); }
    .logo-name {
      font-family:'Cormorant Garamond',serif;
      font-size:1.05rem; font-weight:700; color:var(--accent);
      letter-spacing:.1em; text-align:center;
    }
    .logo-sub { font-size:.58rem; color:var(--muted); letter-spacing:.12em; text-transform:uppercase; text-align:center; margin-top:2px; }
    
    .nav { flex:1; display:flex; flex-direction:column; gap:2px; padding:16px 10px; }
    .nav-item {
      position:relative;
      display:flex; align-items:center; gap:10px;
      padding:11px 14px; border-radius:10px;
      font-size:.82rem; font-weight:600; color:var(--muted);
      cursor:pointer; text-decoration:none;
      transition:background var(--trans), color var(--trans);
    }
    .nav-item:hover  { background:rgba(216,184,120,.10); color:var(--accent); }
    .nav-item.active { background:rgba(216,184,120,.16); color:var(--accent); }
    .nav-item.active::before {
      content:''; position:absolute; left:-10px; top:50%; transform:translateY(-50%);
      width:3px; height:60%; border-radius:0 4px 4px 0; background:var(--accent);
    }
    .nav-item svg    { width:17px; height:17px; flex-shrink:0; }
    .nav-item.admin-only { color:#e67e22; }
    .nav-item.admin-only:hover { background:rgba(230,126,34,.12); color:#f39c12; }
    
    .nav-bottom { padding:10px 10px 0; border-top:1px solid var(--border-color, rgba(216,184,120,.15)); display:flex; flex-direction:column; gap:2px; flex-shrink:0; }

    /* ── MAIN WRAPPER ── */
    .main {
      margin-left:var(--sidebar-w);
      flex:1;
      min-width:0;
      max-width:100%;
      overflow-x:hidden;
      padding:24px 24px 32px;
      min-height:100vh;
      transition:margin-left var(--trans);
    }

    /* ══════════════════ HUD TOP CONTROLS (MUSIK & TEMA) ══════════════════ */
    .beranda-hud-controls {
      position: fixed;
      top: 16px;
      right: 20px;
      z-index: 150;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    /* Bungkus navbar mobile: transparan di desktop (display:contents) — anak-anaknya
       (tombol hamburger & hud-controls) tetap posisi masing-masing seperti semula.
       Baru di breakpoint mobile ia berubah jadi satu bilah navbar sungguhan dengan
       tiang pemisah, supaya ikon musik & mode tidak lagi terasa melayang sendirian. */
    .mobile-topbar { display: contents; }
    .mobile-topbar-divider,
    .mobile-topbar-brand { display: none; }
    .btn-hud-mode {
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
    }
    .btn-hud-mode:hover {
      transform: translateY(-2px);
      border-color: var(--accent);
      box-shadow: 0 6px 20px rgba(216,184,120,.35);
    }
    .btn-hud-mode svg { width: 18px; height: 18px; transition: transform .4s cubic-bezier(.22,1,.36,1); }
    .btn-hud-mode .icon-sun { display: none; }
    html.theme-light .btn-hud-mode .icon-moon,
    html.light .btn-hud-mode .icon-moon { display: none; }
    html.theme-light .btn-hud-mode .icon-sun,
    html.light .btn-hud-mode .icon-sun { display: block; }
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

    /* ─── Hero Carousel & Welcome Card ─── */
    .hero-carousel-wrap {
      --hero-h: 232px;
      width: 100%;
      max-width: 100%;
      position: relative;
      margin-bottom: 22px;
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: var(--shadow-md);
      background: var(--card);
      border: 1px solid var(--border-color);
    }
    .hero-carousel {
      position: relative;
      width: 100%;
      max-width: 100%;
      height: var(--hero-h);
      overflow: hidden;
      user-select: none;
      touch-action: pan-y;
    }
    .hero-track {
      display: flex;
      width: 100%;
      height: 100%;
      transition: transform .4s cubic-bezier(.22,1,.36,1);
    }
    .hero-slide {
      flex: 0 0 100%;
      width: 100%;
      height: 100%;
      min-width: 100%;
      max-width: 100%;
      position: relative;
      box-sizing: border-box;
    }
    
    /* Slide 1: Welcome — tinggi disamakan dengan slide banner (--hero-h)
       supaya tidak ada celah putih ketika kartu ini lebih pendek. */
    .hero-welcome-card {
      position: relative;
      overflow: hidden;
      background: linear-gradient(135deg, #10151b 0%, #161e27 60%, #1e1810 100%);
      padding: 22px 56px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      height: 100%;
      box-sizing: border-box;
    }
    .hero-welcome-left {
      position: relative;
      z-index: 2;
      max-width: 620px;
      min-width: 0;
      width: 100%;
    }
    .hero-greet {
      font-size: .72rem;
      font-weight: 700;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 6px;
    }
    .hero-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.65rem;
      font-weight: 700;
      color: var(--text);
      line-height: 1.25;
      margin-bottom: 8px;
      word-break: break-word;
      display: -webkit-box;
      -webkit-box-orient: vertical;
      -webkit-line-clamp: 2;
      overflow: hidden;
    }
    .hero-quote-box {
      border-left: 2.5px solid var(--accent);
      padding-left: 12px;
      font-size: .82rem;
      font-style: italic;
      color: rgba(238,243,244,.85);
      line-height: 1.5;
    }
    .hero-quote-text {
      display: -webkit-box;
      -webkit-box-orient: vertical;
      -webkit-line-clamp: 2;
      overflow: hidden;
    }
    .hero-quote-author {
      display: block;
      margin-top: 4px;
      font-size: .7rem;
      font-style: normal;
      font-weight: 700;
      color: var(--accent);
    }
    .hero-guest-actions {
      display: flex;
      gap: 10px;
      margin-top: 12px;
      flex-wrap: wrap;
    }
    .btn-hero-primary {
      background: linear-gradient(135deg, #d8b878, #f0d9a8);
      color: #090c10;
      font-weight: 800;
      font-size: .76rem;
      padding: 7px 18px;
      border-radius: 50px;
      text-decoration: none;
      box-shadow: 0 4px 14px rgba(216,184,120,.35);
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: opacity var(--trans);
    }
    .btn-hero-primary:hover { opacity: .9; }
    .btn-hero-secondary {
      background: rgba(216,184,120,.12);
      color: var(--accent);
      font-weight: 800;
      font-size: .76rem;
      padding: 7px 18px;
      border-radius: 50px;
      text-decoration: none;
      border: 1.5px solid rgba(216,184,120,.4);
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: background var(--trans);
    }
    .btn-hero-secondary:hover { background: rgba(216,184,120,.2); }
    .hero-welcome-right {
      position: relative;
      z-index: 1;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .hero-welcome-right svg {
      width: 86px;
      height: 86px;
      color: var(--accent);
      opacity: .8;
    }

    /* Slides 2+: Dynamic Image Banners — tinggi 100% mengikuti --hero-h,
       supaya selalu identik dengan tinggi slide welcome (tidak ada lagi
       celah putih di bawah saat tingginya beda). */
    .hero-banner-slide {
      position: relative;
      width: 100%;
      height: 100%;
      overflow: hidden;
    }
    .hero-banner-slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      pointer-events: none;
    }
    .hero-banner-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(0deg, rgba(9,12,16,.85) 0%, rgba(9,12,16,.3) 50%, transparent 100%);
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 22px 52px 18px;
      color: #fff;
    }
    .hero-banner-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.4rem;
      font-weight: 700;
      color: #fff;
      line-height: 1.25;
    }
    .hero-banner-sub {
      font-size: .8rem;
      color: rgba(255,255,255,.8);
      margin-top: 4px;
      max-width: 550px;
    }

    /* Navigation Arrows & Dots */
    .hero-nav-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      z-index: 10;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      border: 1px solid rgba(216,184,120,.3);
      background: rgba(18,24,32,.55);
      color: var(--accent);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(4px);
      opacity: .8;
      transition: all var(--trans);
    }
    .hero-carousel:hover .hero-nav-arrow { opacity: 1; }
    .hero-nav-arrow:hover { background: var(--accent); color: #090c10; opacity: 1; }
    .hero-nav-arrow.prev { left: 10px; }
    .hero-nav-arrow.next { right: 10px; }
    .hero-nav-arrow svg { width: 15px; height: 15px; }

    .hero-dots-wrap {
      position: absolute;
      bottom: 10px;
      right: 16px;
      z-index: 10;
      display: flex;
      gap: 6px;
    }
    .hero-dot-btn {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: rgba(255,255,255,.4);
      border: none;
      cursor: pointer;
      padding: 0;
      transition: all var(--trans);
    }
    .hero-dot-btn.active {
      background: var(--accent);
      width: 18px;
      border-radius: 4px;
    }

    /* ─── GRID LAYOUT ─── */
    .grid {
      display: grid;
      grid-template-columns: 1fr 290px;
      gap: 20px;
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
    }
    .col-left, .col-right {
      min-width: 0;
      max-width: 100%;
      display: flex;
      flex-direction: column;
      gap: 20px;
      box-sizing: border-box;
    }

    /* Section Cards */
    .section-card {
      background: var(--card);
      border: 1px solid var(--card-border);
      border-radius: var(--radius);
      padding: 18px 20px;
      box-shadow: var(--shadow-sm);
      box-sizing: border-box;
      min-width: 0;
      max-width: 100%;
    }
    .section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 14px;
      gap: 10px;
    }
    .section-title {
      font-size: .88rem;
      font-weight: 800;
      color: var(--text);
      display: flex;
      align-items: center;
      gap: 7px;
    }
    .section-title svg { width: 16px; height: 16px; color: var(--accent); }
    .view-all-link {
      font-size: .72rem;
      color: var(--accent);
      cursor: pointer;
      font-weight: 700;
      text-decoration: none;
      white-space: nowrap;
    }
    .view-all-link:hover { text-decoration: underline; }

    /* Spotlight Book */
    .spotlight-card {
      display: flex;
      gap: 18px;
      align-items: center;
      background: linear-gradient(120deg, rgba(216,184,120,.08) 0%, var(--card) 100%);
      border: 1px solid rgba(216,184,120,.22);
      border-radius: var(--radius);
      padding: 16px 18px;
      box-shadow: var(--shadow-sm);
      cursor: pointer;
      transition: all var(--trans);
      min-width: 0;
      max-width: 100%;
    }
    .spotlight-card:hover {
      box-shadow: var(--shadow-md);
      transform: translateY(-2px);
      border-color: var(--accent);
    }
    .spotlight-thumb {
      width: 90px;
      height: 126px;
      border-radius: 8px;
      flex-shrink: 0;
      overflow: hidden;
      box-shadow: 0 4px 14px rgba(0,0,0,.4);
      position: relative;
    }
    .spotlight-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .spotlight-info { flex: 1; min-width: 0; }
    .spotlight-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: linear-gradient(135deg, #d8b878, #f0d9a8);
      color: #090c10;
      font-size: .62rem;
      font-weight: 800;
      padding: 3px 9px;
      border-radius: 20px;
      letter-spacing: .05em;
      text-transform: uppercase;
      margin-bottom: 6px;
    }
    .spotlight-badge svg { width: 10px; height: 10px; }
    .spotlight-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.3rem;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 2px;
      line-height: 1.25;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .spotlight-author {
      font-size: .74rem;
      color: var(--muted);
      font-weight: 600;
      margin-bottom: 6px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .spotlight-desc {
      font-size: .76rem;
      color: var(--muted);
      line-height: 1.5;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    /* Trending Books List */
    .trending-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      width: 100%;
    }
    .book-card-item {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 10px 12px;
      border-radius: 12px;
      background: var(--book-card, #161e27);
      border: 1px solid var(--card-border);
      cursor: pointer;
      transition: all var(--trans);
      min-width: 0;
      max-width: 100%;
    }
    .book-card-item:hover {
      box-shadow: var(--shadow-md);
      transform: translateY(-2px);
      border-color: var(--accent);
    }
    .book-thumb-box {
      width: 68px;
      height: 96px;
      border-radius: 8px;
      flex-shrink: 0;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,.35);
      position: relative;
    }
    .book-thumb-box img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .book-rank-chip {
      position: absolute;
      top: 4px;
      left: 4px;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: rgba(0,0,0,.7);
      color: var(--accent);
      border: 1px solid rgba(216,184,120,.3);
      font-size: .6rem;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .book-info-box {
      flex: 1;
      min-width: 0;
    }
    .bk-title {
      font-size: .84rem;
      font-weight: 800;
      color: var(--text);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: 2px;
    }
    .bk-author {
      font-size: .72rem;
      color: var(--muted);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: 4px;
    }
    .bk-rating-mini {
      display: inline-flex;
      align-items: center;
      gap: 3px;
      font-size: .65rem;
      font-weight: 800;
      color: #d8b878;
    }
    .bk-rating-mini svg { width: 11px; height: 11px; }
    .bk-badge-row {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-shrink: 0;
      margin-left: auto;
    }
    .bk-genre-tag {
      font-size: .62rem;
      font-weight: 700;
      color: var(--accent);
      background: rgba(216,184,120,.12);
      border: 1px solid rgba(216,184,120,.2);
      padding: 3px 8px;
      border-radius: 50px;
      white-space: nowrap;
    }
    .bk-like-tag {
      font-size: .62rem;
      font-weight: 700;
      color: #e74c3c;
      display: flex;
      align-items: center;
      gap: 3px;
    }
    .bk-like-tag svg { width: 11px; height: 11px; }

    /* New Books Shelf Grid */
    .new-books-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(74px, 1fr));
      gap: 10px;
      width: 100%;
    }
    .new-book-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      cursor: pointer;
      min-width: 0;
    }
    .new-book-cover {
      width: 100%;
      aspect-ratio: 2/3;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,.35);
      border: 1px solid var(--card-border);
      transition: all var(--trans);
      position: relative;
    }
    .new-book-cover:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 20px rgba(0,0,0,.5);
      border-color: var(--accent);
    }
    .new-book-cover img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .new-book-fallback {
      font-size: .56rem;
      font-weight: 700;
      color: #fff;
      text-align: center;
      position: absolute;
      bottom: 4px;
      left: 0; right: 0;
      padding: 0 4px;
      text-shadow: 0 1px 3px rgba(0,0,0,.7);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .new-book-rating {
      position: absolute;
      bottom: 4px;
      right: 4px;
      background: rgba(0,0,0,.7);
      color: #d8b878;
      border: 1px solid rgba(216,184,120,.3);
      font-size: .56rem;
      font-weight: 800;
      padding: 2px 5px;
      border-radius: 20px;
      display: flex;
      align-items: center;
      gap: 2px;
      backdrop-filter: blur(3px);
    }
    .new-book-rating svg { width: 8px; height: 8px; }

    /* Category Chips */
    .genre-cloud {
      display: flex;
      flex-wrap: wrap;
      gap: 7px;
      width: 100%;
    }
    .genre-pill {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 12px;
      border-radius: 50px;
      background: rgba(255,255,255,.04);
      border: 1px solid var(--card-border);
      font-size: .73rem;
      font-weight: 700;
      color: var(--text);
      transition: all var(--trans);
    }
    .genre-pill:hover {
      background: rgba(216,184,120,.14);
      border-color: var(--accent);
      color: var(--accent);
    }
    .genre-pill .pill-count {
      color: var(--muted);
      font-size: .65rem;
    }

    /* Mini Stat Grid */
    .mini-stat-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 8px;
      width: 100%;
    }
    .stat-mini-card {
      border-radius: 10px;
      padding: 12px 8px;
      text-align: center;
      background: rgba(255,255,255,.03);
      border: 1px solid var(--card-border);
      min-width: 0;
    }
    .stat-mini-card.warning {
      background: rgba(220,38,38,.1);
      border-color: rgba(220,38,38,.3);
    }
    .stat-mini-num {
      font-size: 1.25rem;
      font-weight: 800;
      color: var(--accent);
    }
    .stat-mini-card.warning .stat-mini-num { color: #f87171; }
    .stat-mini-label {
      font-size: .64rem;
      font-weight: 700;
      color: var(--muted);
      margin-top: 2px;
    }
    .stat-alert-text {
      font-size: .7rem;
      color: #f87171;
      margin-top: 8px;
      line-height: 1.4;
      padding: 6px 10px;
      background: rgba(220,38,38,.08);
      border-radius: 6px;
    }

    /* Right Column Widgets */
    .shelf-row {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      width: 100%;
    }
    .shelf-cover {
      width: 58px;
      height: 82px;
      border-radius: 6px;
      overflow: hidden;
      box-shadow: 0 3px 10px rgba(0,0,0,.35);
      border: 1px solid var(--card-border);
      cursor: pointer;
      transition: all var(--trans);
      position: relative;
    }
    .shelf-cover:hover {
      transform: translateY(-3px);
      border-color: var(--accent);
    }
    .shelf-cover img { width: 100%; height: 100%; object-fit: cover; display: block; }

    /* Profile Widget */
    .profile-row {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
    }
    .profile-pic {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: rgba(216,184,120,.12);
      border: 2px solid var(--accent);
      flex-shrink: 0;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .profile-pic img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .profile-pic svg { width: 24px; height: 24px; color: var(--accent); }
    .profile-details { flex: 1; min-width: 0; }
    .p-name { font-size: .88rem; font-weight: 800; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .p-meta { font-size: .7rem; color: var(--muted); margin-top: 2px; }
    .btn-edit-data {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      margin-top: 12px;
      padding: 8px 12px;
      border-radius: 8px;
      background: rgba(216,184,120,.1);
      color: var(--accent);
      font-size: .75rem;
      font-weight: 800;
      border: 1px solid rgba(216,184,120,.25);
      text-decoration: none;
      transition: all var(--trans);
    }
    .btn-edit-data:hover { background: var(--accent); color: #090c10; }
    .btn-edit-data svg { width: 13px; height: 13px; }

    /* Admin Widget */
    .admin-profile-box {
      display: flex;
      align-items: center;
      gap: 12px;
      background: var(--card);
      border: 1px solid var(--card-border);
      border-radius: var(--radius);
      padding: 16px;
      box-shadow: var(--shadow-sm);
      min-width: 0;
    }
    .admin-pic {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: rgba(216,184,120,.12);
      border: 1.5px solid rgba(216,184,120,.3);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      overflow: hidden;
    }
    .admin-pic img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .admin-pic svg { width: 24px; height: 24px; color: var(--accent); }

    /* Fallback Cover Colors */
    .c1 { background:linear-gradient(135deg,#f5a623,#d4820a); }
    .c2 { background:linear-gradient(135deg,#9b59b6,#6c3483); }
    .c3 { background:linear-gradient(135deg,#e74c3c,#922b21); }
    .c4 { background:linear-gradient(135deg,#2ecc71,#1a8a4a); }
    .c5 { background:linear-gradient(135deg,#3498db,#1a5276); }
    .c6 { background:linear-gradient(135deg,#e91e63,#880e4f); }
    .c7 { background:linear-gradient(135deg,#ff5722,#bf360c); }
    .c8 { background:linear-gradient(135deg,#607d8b,#263238); }

    /* ─── SITE FOOTER ─── */
    .site-footer {
      margin-top: 36px;
      padding: 24px 4px 12px;
      border-top: 1px solid var(--border-color);
      width: 100%;
      box-sizing: border-box;
    }
    .footer-grid {
      display: grid;
      grid-template-columns: 1.3fr 1fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
      width: 100%;
    }
    .footer-brand {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.15rem;
      font-weight: 700;
      color: var(--accent);
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 6px;
    }
    .footer-brand svg { width: 20px; height: 20px; color: var(--accent); }
    .footer-text { font-size: .74rem; color: var(--muted); line-height: 1.55; }
    .footer-title { font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--text); margin-bottom: 8px; }
    .footer-links { list-style: none; display: flex; flex-direction: column; gap: 6px; }
    .footer-links a { font-size: .76rem; color: var(--muted); text-decoration: none; font-weight: 600; transition: color var(--trans); }
    .footer-links a:hover { color: var(--accent); }
    .footer-row-hours { display: flex; justify-content: space-between; font-size: .74rem; color: var(--muted); padding: 2px 0; }
    .footer-row-hours span:last-child { color: var(--text); font-weight: 700; }
    .footer-bottom-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
      padding-top: 14px;
      border-top: 1px solid rgba(216,184,120,.1);
      font-size: .7rem;
      color: var(--muted);
    }
    .footer-social-btns { display: flex; gap: 8px; }
    .footer-social-btns a {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: rgba(255,255,255,.05);
      border: 1px solid var(--card-border);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--accent);
      text-decoration: none;
      transition: all var(--trans);
    }
    .footer-social-btns a:hover { background: var(--accent); color: #090c10; }
    .footer-social-btns svg { width: 13px; height: 13px; }

    /* ─── MODAL DETAIL BUKU ─── */
    .detail-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.7);
      backdrop-filter: blur(5px);
      -webkit-backdrop-filter: blur(5px);
      z-index: 500;
      align-items: center;
      justify-content: center;
      padding: 12px;
      box-sizing: border-box;
    }
    .detail-overlay.open { display: flex; }
    .detail-modal {
      background: var(--card, #121820);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      width: 100%;
      max-width: 480px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 24px 70px rgba(0,0,0,.6);
      animation: modalIn .25s cubic-bezier(.22,1,.36,1) both;
      box-sizing: border-box;
    }
    @keyframes modalIn { from{opacity:0;transform:scale(.95) translateY(10px)} to{opacity:1;transform:scale(1) translateY(0)} }
    .detail-cover {
      width: 100%;
      aspect-ratio: 16/9;
      max-height: 200px;
      overflow: hidden;
      position: relative;
      background: #10151b;
    }
    .detail-cover img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .detail-cover-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
    .detail-cover-placeholder svg { width: 48px; height: 48px; color: rgba(216,184,120,.35); }
    .detail-cover-badge {
      position: absolute; top: 10px; right: 10px;
      background: rgba(0,0,0,.75); color: var(--accent);
      border: 1px solid rgba(216,184,120,.3);
      font-size: .62rem; font-weight: 800;
      padding: 3px 8px; border-radius: 20px;
      text-transform: uppercase; backdrop-filter: blur(4px);
    }
    .detail-close-btn {
      position: absolute; top: 10px; left: 10px;
      width: 30px; height: 30px; border-radius: 50%;
      background: rgba(0,0,0,.75); border: 1px solid rgba(216,184,120,.3);
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; backdrop-filter: blur(4px); transition: background .2s;
    }
    .detail-close-btn:hover { background: rgba(0,0,0,.9); }
    .detail-close-btn svg { width: 15px; height: 15px; color: var(--accent); }
    .detail-body { padding: 18px 20px 22px; }
    .detail-genre-chip {
      display: inline-block;
      background: rgba(216,184,120,.12); color: var(--accent);
      border: 1px solid rgba(216,184,120,.25);
      font-size: .62rem; font-weight: 800;
      padding: 2px 9px; border-radius: 20px;
      text-transform: uppercase; margin-bottom: 6px;
    }
    .detail-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.35rem; font-weight: 700;
      color: var(--text); line-height: 1.25; margin-bottom: 2px;
      word-break: break-word;
    }
    .detail-author { font-size: .78rem; color: var(--muted); font-weight: 600; margin-bottom: 12px; }
    .detail-stat-row { display: flex; gap: 14px; margin-bottom: 14px; flex-wrap: wrap; }
    .detail-stat { display: flex; align-items: center; gap: 5px; font-size: .74rem; font-weight: 700; }
    .detail-stat svg { width: 14px; height: 14px; }
    .detail-stat.likes { color: #e74c3c; }
    .detail-stat.favs  { color: #f39c12; }
    .detail-meta-row { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 14px; }
    .detail-meta-chip {
      display: flex; align-items: center; gap: 4px;
      background: rgba(255,255,255,.04);
      border: 1px solid var(--card-border);
      border-radius: 6px;
      padding: 5px 9px;
      font-size: .68rem; font-weight: 700; color: var(--muted);
    }
    .detail-meta-chip svg { width: 12px; height: 12px; flex-shrink: 0; }
    .detail-meta-chip span { color: var(--text); }
    .detail-section-label { font-size: .66rem; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; margin-bottom: 6px; }
    .detail-footer-btns { display: flex; gap: 10px; margin-top: 14px; }
    .detail-btn-like, .detail-btn-save {
      flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px;
      padding: 8px 12px; border-radius: 8px; border: 1.5px solid var(--card-border);
      background: rgba(255,255,255,.04); font-family: var(--font-family,'Outfit',sans-serif);
      font-size: .78rem; font-weight: 700; color: var(--muted);
      cursor: pointer; transition: all var(--trans);
    }
    .detail-btn-like svg, .detail-btn-save svg { width: 14px; height: 14px; }
    .detail-btn-like:hover { border-color: #e74c3c; color: #e74c3c; background: rgba(231,76,60,.1); }
    .detail-btn-like.aktif { background: rgba(231,76,60,.15); border-color: #e74c3c; color: #e74c3c; }
    .detail-btn-like.aktif svg { fill: currentColor; }
    .detail-btn-save:hover { border-color: var(--accent); color: var(--accent); background: rgba(216,184,120,.1); }
    .detail-btn-save.aktif { background: rgba(216,184,120,.18); border-color: var(--accent); color: var(--accent); }
    .detail-btn-save.aktif svg { fill: currentColor; }
    .detail-btn-pinjam {
      width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
      padding: 11px 16px; border-radius: 10px; border: none;
      background: linear-gradient(135deg, #d8b878 0%, #b89758 100%);
      color: #090c10; font-family: var(--font-family,'Outfit',sans-serif);
      font-size: .85rem; font-weight: 800; cursor: pointer;
      box-shadow: 0 4px 16px rgba(216,184,120,.25);
      transition: all .2s cubic-bezier(.22,1,.36,1);
    }
    .detail-btn-pinjam svg { width: 17px; height: 17px; flex-shrink: 0; }
    .detail-btn-pinjam:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 6px 22px rgba(216,184,120,.45);
    }
    .detail-btn-pinjam:disabled, .detail-btn-pinjam.disabled {
      opacity: .45; cursor: not-allowed; background: rgba(255,255,255,.08); color: var(--muted);
      box-shadow: none; transform: none;
    }
    .detail-sinopsis {
      font-size: .8rem; line-height: 1.65; color: var(--text);
      background: rgba(255,255,255,.03); border-radius: 8px;
      padding: 12px 14px; border-left: 3px solid var(--accent);
      border: 1px solid var(--card-border);
      word-break: break-word;
    }
    .detail-rating-row { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
    .stars-display { display: flex; gap: 2px; }
    .stars-display svg { width: 14px; height: 14px; }
    .rating-text { font-size: .72rem; font-weight: 700; color: var(--muted); }
    .stars-input { display: flex; gap: 3px; cursor: pointer; }
    .stars-input svg { width: 20px; height: 20px; color: rgba(255,255,255,.2); transition: color .15s; }
    .stars-input svg.hover, .stars-input svg.aktif { color: #d8b878; }
    .detail-loading { display: flex; align-items: center; justify-content: center; padding: 50px 20px; color: var(--muted); font-size: .82rem; flex-direction: column; gap: 10px; }
    .spinner { width: 28px; height: 28px; border: 3px solid rgba(216,184,120,.2); border-top-color: var(--accent); border-radius: 50%; animation: spin .7s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ═══════════════════════════════════════════
       RESPONSIVE BREAKPOINTS (NO HORIZONTAL SCROLL)
       ═══════════════════════════════════════════ */
    @media (max-width: 960px) {
      .grid {
        grid-template-columns: 1fr;
      }
      .col-right {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 16px;
      }
      .col-right .admin-profile-box { grid-column: 1 / -1; }
    }

    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
        width: min(calc(var(--sidebar-w) + 60px), 260px);
        padding-bottom: max(20px, env(safe-area-inset-bottom));
      }
      .sidebar.open { transform: translateX(0); }
      .main {
        margin-left: 0;
        padding: 78px 14px 28px;
      }

      /* Navbar atas mobile: satu bilah utuh (musik + mode + hamburger),
         bukan lagi tombol-tombol bulat lepas yang mengambang di atas konten */
      .mobile-topbar {
        display: flex;
        align-items: center;
        gap: 12px;
        position: fixed;
        top: 0; left: 0; right: 0;
        height: 60px;
        padding: 0 14px;
        padding-top: env(safe-area-inset-top, 0);
        background: var(--sidebar-bg);
        border-bottom: 1px solid var(--border-color, rgba(216,184,120,.15));
        box-shadow: 0 2px 18px rgba(0,0,0,.35);
        z-index: 160;
        transition: opacity var(--trans), visibility var(--trans);
      }
      body.sidebar-open .mobile-topbar {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
      }
      .mobile-topbar .sidebar-toggle {
        display: flex;
        position: static;
        box-shadow: none;
        flex-shrink: 0;
      }
      .mobile-topbar-divider {
        display: block;
        width: 1px;
        height: 26px;
        flex-shrink: 0;
        background: linear-gradient(180deg, transparent, var(--border-color, rgba(216,184,120,.35)) 50%, transparent);
      }
      .mobile-topbar-brand {
        display: flex;
        align-items: center;
        gap: 7px;
        min-width: 0;
        overflow: hidden;
      }
      .mobile-topbar-brand svg { width: 19px; height: 19px; color: var(--accent); flex-shrink: 0; }
      .mobile-topbar-brand span {
        font-family: 'Cormorant Garamond', serif;
        font-weight: 700;
        font-size: .92rem;
        color: var(--accent);
        letter-spacing: .04em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      .mobile-topbar .beranda-hud-controls {
        position: static;
        top: auto;
        right: auto;
        margin-left: auto;
      }
      .footer-grid {
        grid-template-columns: 1fr;
        gap: 18px;
      }
    }


    @media (max-width: 540px) {
      .main { padding: 74px 10px 24px; }
      
      .hero-carousel-wrap { --hero-h: 235px; }
      .hero-welcome-card {
        padding: 14px 40px;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        gap: 6px;
      }
      .hero-welcome-right { display: none; }
      .hero-title { font-size: 1.2rem; -webkit-line-clamp: 2; }
      .hero-quote-box { font-size: .73rem; }
      .hero-quote-text { -webkit-line-clamp: 2; }
      .hero-guest-actions { margin-top: 8px; }
      .hero-banner-overlay { padding: 12px 40px 10px; }
      .hero-banner-title { font-size: 1rem; }
      .hero-banner-sub { font-size: .72rem; }

      .spotlight-card {
        padding: 12px 12px;
        gap: 10px;
      }
      .spotlight-thumb { width: 68px; height: 96px; }
      .spotlight-title { font-size: 1.1rem; }
      
      .book-card-item {
        padding: 8px 10px;
        gap: 10px;
      }
      .book-thumb-box { width: 52px; height: 74px; }
      .bk-title { font-size: .78rem; }
      .bk-author { font-size: .66rem; }
      .bk-genre-tag { padding: 2px 6px; font-size: .55rem; }
      
      .new-books-grid {
        grid-template-columns: repeat(auto-fill, minmax(68px, 1fr));
        gap: 8px;
      }

      .col-right {
        display: flex;
        flex-direction: column;
        gap: 14px;
        width: 100%;
      }

      /* Shelf cover lebih compact */
      .shelf-cover { width: 50px; height: 70px; }

      .footer-bottom-row {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 8px;
      }

      /* Modal detail buku — bottom-sheet, konsisten */
      .detail-overlay { align-items: flex-end; padding: 0; }
      .detail-modal {
        max-width: 100%; width: 100%; margin: 0;
        border-radius: 16px 16px 0 0;
        max-height: 92dvh;
      }
    }

    /* Layar 375px — iPhone 13 mini / SE size */
    @media (max-width: 375px) {
      .main { padding: 72px 8px 22px; }
      .hero-title { font-size: 1.1rem; }
      .hero-carousel-wrap { --hero-h: 225px; }
      .hero-welcome-card { padding: 12px 34px; }
      .hero-nav-arrow { width: 28px; height: 28px; }
      .hero-nav-arrow.prev { left: 6px; }
      .hero-nav-arrow.next { right: 6px; }
      .hero-banner-overlay { padding-left: 34px; padding-right: 34px; }
      .new-books-grid { grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 7px; }
      .mini-stat-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
      .shelf-cover { width: 46px; height: 64px; }
      .spotlight-thumb { width: 60px; height: 86px; }
    }

    @media (max-width: 360px) {
      .main { padding-left: 8px; padding-right: 8px; }
      .mini-stat-grid { grid-template-columns: 1fr; }
      .new-books-grid { grid-template-columns: repeat(3, 1fr); }
    }
  </style>
</head>
<body>

<!-- Navbar Atas Mobile: Hamburger + Tiang Pemisah + Kontrol Musik/Mode -->
<header class="mobile-topbar" id="mobileTopbar">
  <button class="sidebar-toggle" id="sidebarToggle" aria-label="Buka Menu">
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

  <div class="beranda-hud-controls">
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

<!-- Sidebar Menu -->
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
    <a href="beranda.php" class="nav-item active">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Beranda
    </a>
    <?php if (!$is_admin && !$is_guest): ?>
    <a href="dashboard_user.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
      Dashboard
    </a>
    <?php endif; ?>
    <a href="daftar_buku.php" class="nav-item">
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
    <a href="#" class="nav-item" onclick="bukaSettings(); return false;" title="Pengaturan Tampilan">
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

<!-- Main Container -->
<main class="main">

  <!-- ─── Hero Carousel / Banner Card ─── -->
  <?php $total_slides = 1 + count($banners); ?>
  <div class="hero-carousel-wrap">
    <div class="hero-carousel" id="heroCarousel">
      <div class="hero-track" id="heroTrack">

        <!-- Slide 1: Welcome & Quotes -->
        <div class="hero-slide">
          <div class="hero-welcome-card">
            <div class="hero-welcome-left">
              <div class="hero-greet"><?= htmlspecialchars($sapaan) ?>, <?= htmlspecialchars($user_name) ?> 👋</div>
              <div class="hero-title">Selamat datang di AKSA NOVA</div>
              <div class="hero-quote-box">
                <span class="hero-quote-text">"<?= htmlspecialchars($kutipan_hari_ini["teks"]) ?>"</span>
                <span class="hero-quote-author">— <?= htmlspecialchars($kutipan_hari_ini["oleh"]) ?></span>
              </div>
              <?php if ($is_guest): ?>
              <div class="hero-guest-actions">
                <a href="sign_in.php" class="btn-hero-primary">Masuk Akun</a>
                <a href="sign_up.php" class="btn-hero-secondary">Daftar Anggota</a>
              </div>
              <?php endif; ?>
            </div>
            <div class="hero-welcome-right">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
              </svg>
            </div>
          </div>
        </div>

        <!-- Slides 2+: Admin Banners -->
        <?php foreach ($banners as $b): ?>
        <div class="hero-slide">
          <div class="hero-banner-slide">
            <?php if ($b["link_url"]): ?><a href="<?= htmlspecialchars($b["link_url"]) ?>" style="position:absolute;inset:0;z-index:2;"></a><?php endif; ?>
            <img src="<?= htmlspecialchars($b["gambar"]) ?>" alt="<?= htmlspecialchars($b["judul"] ?: "Banner") ?>" draggable="false">
            <?php if ($b["judul"] || $b["subjudul"]): ?>
            <div class="hero-banner-overlay">
              <?php if ($b["judul"]): ?><div class="hero-banner-title"><?= htmlspecialchars($b["judul"]) ?></div><?php endif; ?>
              <?php if ($b["subjudul"]): ?><div class="hero-banner-sub"><?= htmlspecialchars($b["subjudul"]) ?></div><?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>

      </div>

      <?php if ($total_slides > 1): ?>
      <button type="button" class="hero-nav-arrow prev" onclick="heroGo(-1)" aria-label="Sebelumnya">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <button type="button" class="hero-nav-arrow next" onclick="heroGo(1)" aria-label="Berikutnya">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
      <div class="hero-dots-wrap" id="heroDots">
        <?php for ($i = 0; $i < $total_slides; $i++): ?>
          <button type="button" class="hero-dot-btn <?= $i === 0 ? "active" : "" ?>" onclick="heroGoTo(<?= $i ?>)" aria-label="Slide <?= $i + 1 ?>"></button>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ─── Main Content Grid ─── -->
  <div class="grid">
    
    <!-- LEFT COLUMN -->
    <div class="col-left">

      <!-- Spotlight Book -->
      <?php if (!empty($trending)): $sp = $trending[0]; ?>
      <div class="spotlight-card" onclick="bukaDetailBuku(<?= $sp['id'] ?>)">
        <div class="spotlight-thumb <?= ($sp["gambar"] && file_exists($sp["gambar"])) ? "" : $thumb_colors[0] ?>">
          <?php if ($sp["gambar"] && file_exists($sp["gambar"])): ?>
            <img src="<?= htmlspecialchars($sp["gambar"]) ?>" alt="<?= htmlspecialchars($sp["judul"]) ?>">
          <?php endif; ?>
        </div>
        <div class="spotlight-info">
          <span class="spotlight-badge">
            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            Buku Pilihan
          </span>
          <div class="spotlight-title"><?= htmlspecialchars($sp["judul"]) ?></div>
          <div class="spotlight-author"><?= htmlspecialchars($sp["penulis"] ?: "Penulis tidak diketahui") ?></div>
          <div class="spotlight-desc"><?= htmlspecialchars($sp["sinopsis"] ?: "Buku paling populer di katalog AKSA NOVA saat ini.") ?></div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Trending Books List -->
      <div class="section-card">
        <div class="section-header">
          <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            Trending Books
          </div>
          <a href="daftar_buku.php" class="view-all-link">Lihat Semua &rsaquo;</a>
        </div>
        <?php if (empty($trending)): ?>
          <div style="text-align:center;padding:24px 0;color:var(--muted);font-size:.8rem;">Belum ada buku di katalog.</div>
        <?php else: ?>
        <div class="trending-list">
          <?php foreach ($trending as $i => $buku):
            $col = $thumb_colors[$i % count($thumb_colors)];
          ?>
          <div class="book-card-item" onclick="bukaDetailBuku(<?= $buku['id'] ?>)">
            <div class="book-thumb-box <?= $col ?>">
              <div class="book-rank-chip"><?= $i + 1 ?></div>
              <?php if ($buku["gambar"] && file_exists($buku["gambar"])): ?>
                <img src="<?= htmlspecialchars($buku["gambar"]) ?>" alt="<?= htmlspecialchars($buku["judul"]) ?>">
              <?php endif; ?>
            </div>
            <div class="book-info-box">
              <div class="bk-title"><?= htmlspecialchars($buku["judul"]) ?></div>
              <div class="bk-author"><?= htmlspecialchars($buku["penulis"] ?: "—") ?></div>
              <?php $rat = $rating_avg[$buku["id"]] ?? null; if ($rat && $rat["total"] > 0): ?>
              <div class="bk-rating-mini">
                <svg viewBox="0 0 24 24" fill="#f5a623" stroke="#f5a623" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <?= $rat["avg"] ?> <span style="color:var(--muted);font-weight:400;">(<?= $rat["total"] ?>)</span>
              </div>
              <?php endif; ?>
            </div>
            <div class="bk-badge-row">
              <?php if ($buku["genre"]): ?>
                <span class="bk-genre-tag"><?= htmlspecialchars($buku["genre"]) ?></span>
              <?php endif; ?>
              <?php if (($buku["jumlah_like"] ?? 0) > 0): ?>
                <span class="bk-like-tag">
                  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                  <?= $buku["jumlah_like"] ?>
                </span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- New Books Grid -->
      <div class="section-card">
        <div class="section-header">
          <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Buku Terbaru
          </div>
          <a href="daftar_buku.php" class="view-all-link">Lihat Semua &rsaquo;</a>
        </div>
        <?php if (empty($new_books)): ?>
          <div style="text-align:center;padding:24px 0;color:var(--muted);font-size:.8rem;">Belum ada buku terbaru.</div>
        <?php else: ?>
        <div class="new-books-grid">
          <?php foreach ($new_books as $i => $buku):
            $col = $thumb_colors[$i % count($thumb_colors)];
          ?>
          <div class="new-book-item" onclick="bukaDetailBuku(<?= $buku['id'] ?>)">
            <div class="new-book-cover <?= ($buku["gambar"] && file_exists($buku["gambar"])) ? "" : $col ?>">
              <?php if ($buku["gambar"] && file_exists($buku["gambar"])): ?>
                <img src="<?= htmlspecialchars($buku["gambar"]) ?>" alt="<?= htmlspecialchars($buku["judul"]) ?>">
              <?php else: ?>
                <div class="new-book-fallback"><?= htmlspecialchars(mb_substr($buku["judul"], 0, 10)) ?></div>
              <?php endif; ?>
              <?php $rat2 = $rating_avg[$buku["id"]] ?? null; if ($rat2 && $rat2["total"] > 0): ?>
              <div class="new-book-rating">
                <svg viewBox="0 0 24 24" fill="#ffb800" stroke="#ffb800" stroke-width="1"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <?= $rat2["avg"] ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Kategori Populer -->
      <?php if (!empty($genre_populer)): ?>
      <div class="section-card">
        <div class="section-header">
          <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 9h16M4 15h16M10 3L8 21M16 3l-2 18"/></svg>
            Kategori Populer
          </div>
        </div>
        <div class="genre-cloud">
          <?php foreach ($genre_populer as $g): ?>
            <span class="genre-pill">
              <?= htmlspecialchars($g["genre"]) ?>
              <span class="pill-count">· <?= (int)$g["jumlah"] ?></span>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /col-left -->

    <!-- RIGHT COLUMN -->
    <div class="col-right">

      <!-- Statistik Peminjaman / Member Overview -->
      <?php if ($is_admin): ?>
      <div class="section-card">
        <div class="section-header">
          <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            Total Katalog
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:14px;padding:6px 0;">
          <div style="width:48px;height:48px;border-radius:12px;background:rgba(216,184,120,.12);border:1px solid rgba(216,184,120,.25);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--accent);"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          </div>
          <div>
            <div style="font-size:1.4rem;font-weight:800;color:var(--accent);"><?= $total_buku ?></div>
            <div style="font-size:.72rem;color:var(--muted);">Judul buku terdaftar</div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Buku Simpan (Member Only) -->
      <?php if (!$is_admin && !$is_guest): ?>
      <div class="section-card">
        <div class="section-header">
          <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
            Buku Disimpan
          </div>
          <a href="buku_simpan.php" class="view-all-link">Lihat Semua &rsaquo;</a>
        </div>
        <?php if (empty($saved_books)): ?>
          <div style="text-align:center;padding:14px 0;color:var(--muted);font-size:.75rem;">Belum ada buku yang disimpan.</div>
        <?php else: ?>
        <div class="shelf-row">
          <?php foreach ($saved_books as $i => $buku):
            $grad = ["linear-gradient(135deg,#f5a623,#d4820a)","linear-gradient(135deg,#2ecc71,#1a8a4a)","linear-gradient(135deg,#9b59b6,#6c3483)","linear-gradient(135deg,#e74c3c,#922b21)"][$i % 4];
          ?>
          <div class="shelf-cover" style="background:<?= $grad ?>" onclick="bukaDetailBuku(<?= $buku['id'] ?>)" title="<?= htmlspecialchars($buku['judul']) ?>">
            <?php if ($buku["gambar"] && file_exists($buku["gambar"])): ?>
              <img src="<?= htmlspecialchars($buku["gambar"]) ?>" alt="">
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Informasi Perpustakaan & Jam Layanan -->
      <div class="section-card">
        <div class="section-header">
          <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Informasi Layanan
          </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;font-size:.78rem;color:var(--muted);">
          <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--card-border);padding-bottom:7px;">
            <span>Senin – Jumat</span>
            <span style="color:var(--text);font-weight:700;">08.00 – 20.00</span>
          </div>
          <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--card-border);padding-bottom:7px;">
            <span>Sabtu</span>
            <span style="color:var(--text);font-weight:700;">09.00 – 17.00</span>
          </div>
          <div style="display:flex;justify-content:space-between;">
            <span>Minggu / Libur</span>
            <span style="color:#f87171;font-weight:700;">Tutup</span>
          </div>
        </div>
      </div>

      <!-- Admin Profile Box -->
      <div class="admin-profile-box">
        <div class="admin-pic">
          <?php if ($profil_foto && file_exists($profil_foto)): ?>
            <img src="<?= htmlspecialchars($profil_foto) ?>?v=<?= filemtime($profil_foto) ?>" alt="Admin"/>
          <?php else: ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <?php endif; ?>
        </div>
        <div style="min-width:0;">
          <div style="font-size:.92rem;font-weight:800;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($profil_nama) ?></div>
          <div style="font-size:.7rem;color:var(--muted);margin-top:2px;">Administrator Perpustakaan</div>
        </div>
      </div>

    </div><!-- /col-right -->

  </div><!-- /grid -->

  <!-- ─── Site Footer ─── -->
  <footer class="site-footer">
    <div class="footer-grid">
      <div>
        <div class="footer-brand">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          AKSA NOVA
        </div>
        <p class="footer-text">Katalog perpustakaan modern yang menghubungkan pembaca dengan beragam pilihan buku berkualitas.</p>
      </div>
      <div>
        <div class="footer-title">Tautan Cepat</div>
        <ul class="footer-links">
          <li><a href="beranda.php">Beranda</a></li>
          <li><a href="daftar_buku.php">Daftar Buku</a></li>
          <?php if (!$is_admin): ?><li><a href="buku_simpan.php">Buku Simpan</a></li><?php endif; ?>
          <?php if ($is_admin): ?><li><a href="halaman_admin.php">Panel Admin</a></li><?php endif; ?>
        </ul>
      </div>
      <div>
        <div class="footer-title">Jam Operasional</div>
        <div class="footer-row-hours"><span>Senin – Jumat</span><span>08.00 – 20.00</span></div>
        <div class="footer-row-hours"><span>Sabtu</span><span>09.00 – 17.00</span></div>
        <div class="footer-row-hours"><span>Minggu</span><span>Tutup</span></div>
      </div>
    </div>
    <div class="footer-bottom-row">
      <span>© <?= date("Y") ?> AKSA NOVA Library Catalog App. Semua hak dilindungi.</span>
      <div class="footer-social-btns">
        <a href="#" title="Instagram" onclick="return false;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><line x1="17.5" y1="6.5" x2="17.5" y2="6.5"/></svg></a>
        <a href="#" title="Email" onclick="return false;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg></a>
        <a href="#" title="Lokasi" onclick="return false;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></a>
      </div>
    </div>
  </footer>

</main>

<!-- Modal Detail Buku -->
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

<script>
  // ─── Sidebar Toggle ───
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
  function tutupDetailBuku() { document.getElementById('detailOverlay').classList.remove('open'); }
  document.getElementById('detailOverlay').addEventListener('click', function(e) { if (e.target === this) tutupDetailBuku(); });
  document.addEventListener('keydown', function(e) { if (e.key === 'Escape') tutupDetailBuku(); });

  const IS_ADMIN = <?= json_encode($is_admin) ?>;
  const IS_GUEST = <?= json_encode($is_guest) ?>;

  function butuhLogin() {
    window.location.href = 'sign_in.php';
    return true;
  }

  function renderDetail(b) {
    const content = document.getElementById('detailContent');
    const stokLabel = b.stok == 0 ? 'Habis' : (b.stok <= 3 ? 'Terbatas' : 'Tersedia');
    const stokColor = b.stok == 0 ? '#e74c3c' : (b.stok <= 3 ? '#f39c12' : '#27ae60');
    const tglInput = b.created_at ? new Date(b.created_at).toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'}) : '—';
    const tglUpdate = b.updated_at && b.updated_at !== b.created_at ? new Date(b.updated_at).toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'}) : null;
    const coverHTML = b.gambar ? `<img src="${escHTML(b.gambar)}" alt="${escHTML(b.judul)}">` : `<div class="detail-cover-placeholder"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>`;

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
      <div class="detail-cover">${coverHTML}
        <button class="detail-close-btn" onclick="tutupDetailBuku()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        ${b.genre ? `<span class="detail-cover-badge">${escHTML(b.genre)}</span>` : ''}
      </div>
      <div class="detail-body">
        ${b.genre ? `<div class="detail-genre-chip">${escHTML(b.genre)}</div>` : ''}
        <div class="detail-title">${escHTML(b.judul)}</div>
        <div class="detail-author">${b.penulis ? '✍️ ' + escHTML(b.penulis) : 'Penulis tidak diketahui'}</div>
        <div class="detail-stat-row">
          <div class="detail-stat likes" id="statLike_${b.id}"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg><span id="likeCount_${b.id}">${b.jumlah_like}</span> Suka</div>
          <div class="detail-stat favs"><svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>${b.jumlah_favorit} Favorit</div>
        </div>
        <div class="detail-rating-row" id="ratingRow_${b.id}">
          ${renderStarsDisplay(b.rating_avg, b.rating_total)}
          <div style="border-left:1px solid var(--border-color, rgba(216,184,120,.2));height:16px;"></div>
          <span style="font-size:.72rem;font-weight:700;color:var(--muted);">Nilai kamu:</span>
          ${renderStarsInput(b.id, b.user_rating)}
        </div>
        <div class="detail-meta-row">
          ${b.isbn ? `<div class="detail-meta-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>ISBN: <span>${escHTML(b.isbn)}</span></div>` : ''}
          <div class="detail-meta-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>Stok: <span style="color:${stokColor};font-weight:800;">${escHTML(String(b.stok))} — ${stokLabel}</span></div>
          <div class="detail-meta-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Ditambahkan: <span>${tglInput}</span></div>
          ${tglUpdate ? `<div class="detail-meta-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Diperbarui: <span>${tglUpdate}</span></div>` : ''}
        </div>
        <div class="detail-section-label">Sinopsis / Ringkasan</div>
        ${b.sinopsis ? `<div class="detail-sinopsis">${escHTML(b.sinopsis).replace(/\n/g,'<br>')}</div>` : `<div class="detail-sinopsis"><span style="color:var(--muted);font-style:italic;">Sinopsis belum tersedia untuk buku ini.</span></div>`}
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
      });
  }

  function toggleSimpanModal(btn, bukuId) {
    if (IS_GUEST) return butuhLogin();
    const fd = new FormData();
    fd.append('buku_id', bukuId);
    fd.append('type', 'favorite');
    fetch('like_handler.php', {method:'POST', body:fd})
      .then(r => r.json())
      .then(data => {
        if (!data.ok) return;
        btn.classList.toggle('aktif', data.aktif);
        btn.querySelector('svg').setAttribute('fill', data.aktif ? 'currentColor' : 'none');
        const label = document.getElementById(`modalSaveLabel_${bukuId}`);
        if (label) label.textContent = data.aktif ? 'Tersimpan' : 'Simpan';
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

  // ─── Hero Carousel Script (Zero Page Overflow) ───
  (function () {
    const track = document.getElementById('heroTrack');
    if (!track) return;

    const slides = track.children.length;
    let current = 0;
    let autoplayTimer = null;

    window.heroGoTo = function (idx) {
      current = ((idx % slides) + slides) % slides;
      track.style.transform = `translateX(${-current * 100}%)`;
      document.querySelectorAll('#heroDots .hero-dot-btn').forEach((d, i) => d.classList.toggle('active', i === current));
      restartAutoplay();
    };
    window.heroGo = function (dir) { heroGoTo(current + dir); };

    function restartAutoplay() {
      if (slides <= 1) return;
      clearInterval(autoplayTimer);
      autoplayTimer = setInterval(() => heroGoTo(current + 1), 5500);
    }

    // Navigasi carousel hanya lewat tombol panah & titik (dot) — sengaja tanpa
    // swipe/geser sentuh, supaya gerakan jari pengguna di layar tidak pernah
    // "membajak" carousel maupun men-scroll halaman ke samping.
    restartAutoplay();
  })();

  function escHTML(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function starSVG(filled) {
    return `<svg viewBox="0 0 24 24" fill="${filled?'#f5a623':'none'}" stroke="#f5a623" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>`;
  }
  function renderStarsDisplay(avg, total) {
    let stars = '';
    for (let i = 1; i <= 5; i++) stars += starSVG(i <= Math.round(avg));
    return `<div class="stars-display">${stars}</div><span class="rating-text">${total > 0 ? avg + ' (' + total + ' ulasan)' : 'Belum ada rating'}</span>`;
  }
  function renderStarsInput(bukuId, userRating) {
    let html = `<div class="stars-input" id="starsInput_${bukuId}">`;
    for (let i = 1; i <= 5; i++) {
      html += `<svg viewBox="0 0 24 24" fill="${i<=userRating?'#f5a623':'none'}" stroke="#f5a623" stroke-width="2" class="${i<=userRating?'aktif':''}" onmouseover="hoverStar(${bukuId},${i})" onmouseout="resetStarHover(${bukuId})" onclick="submitRating(${bukuId},${i})"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>`;
    }
    return html + '</div>';
  }
  function hoverStar(bukuId, n) {
    document.querySelectorAll(`#starsInput_${bukuId} svg`).forEach((s,i) => { s.setAttribute('fill', i<n?'#f5a623':'none'); s.classList.toggle('hover', i<n); });
  }
  function resetStarHover(bukuId) {
    document.querySelectorAll(`#starsInput_${bukuId} svg`).forEach(s => { s.classList.remove('hover'); s.setAttribute('fill', s.classList.contains('aktif')?'#f5a623':'none'); });
  }
  function submitRating(bukuId, rating) {
    if (IS_GUEST) return butuhLogin();
    const fd = new FormData();
    fd.append('buku_id', bukuId); fd.append('rating', rating);
    fetch('rating_handler.php', {method:'POST',body:fd}).then(r=>r.json()).then(data => {
      if (!data.ok) return;
      document.querySelectorAll(`#starsInput_${bukuId} svg`).forEach((s,i) => { const on=i<data.user_rating; s.setAttribute('fill',on?'#f5a623':'none'); s.classList.toggle('aktif',on); });
      const row = document.getElementById(`ratingRow_${bukuId}`);
      if (row) { const disp=row.querySelector('.stars-display'),txt=row.querySelector('.rating-text'); if(disp&&txt){ let s=''; for(let i=1;i<=5;i++) s+=starSVG(i<=Math.round(data.avg)); disp.innerHTML=s; txt.textContent=`${data.avg} (${data.total} ulasan)`; } }
    });
  }

  // ─── Musik Latar (Dikelola terpusat oleh AksaAudio di settings_include.php) ───
  if (window.AksaAudio) window.AksaAudio.init();


</script>

<?php require_once "pengaturan_panel.php"; ?>
</body>
</html>