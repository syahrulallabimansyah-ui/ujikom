<?php
// beranda.php — Halaman dashboard utama (dinamis dari database)
// ── PERUBAHAN: settings_include.php + pengaturan_panel.php ditambahkan ──
session_start();

require_once "db.php";

// ─── Mode tamu: pengunjung boleh langsung melihat beranda tanpa login ───
// Aksi seperti suka, rating, dan simpan tetap butuh login (lihat bagian JS di bawah).
$is_guest    = !isset($_SESSION["user_id"]);
$user_id_int = $is_guest ? 0 : (int)$_SESSION["user_id"];
$user_name   = $is_guest ? "Tamu" : $_SESSION["user_name"];
$role        = $is_guest ? "guest" : $_SESSION["role"];
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
$total_buku = $total_row["total"];

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

// ─── Kutipan literasi hari ini (berganti tiap hari) ───
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
    while ($row = mysqli_fetch_assoc($rr)) {
        $rating_avg[$row["buku_id"]] = ["avg" => (float)$row["avg_r"], "total" => (int)$row["total"]];
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

// ─── Buku yang disimpan user (favorites) ───
$saved_books = [];
if (!$is_admin && !$is_guest) {
    $res_saved = mysqli_query($conn,
        "SELECT b.* FROM buku b
         INNER JOIN buku_favorites f ON f.buku_id = b.id
         WHERE f.user_id = $user_id_int
         ORDER BY f.id DESC LIMIT 6"
    );
    while ($row = mysqli_fetch_assoc($res_saved)) $saved_books[] = $row;
}

// ─── Statistik peminjaman user (untuk dashboard di beranda) ───
$total_pinjam_user        = 0;
$sedang_dipinjam_user     = 0;
$belum_dikembalikan_user  = 0;
if (!$is_admin && !$is_guest) {
    // Total Pinjam: seluruh riwayat peminjaman milik user (aktif maupun sudah dikembalikan)
    $res_total_pinjam = mysqli_query($conn,
        "SELECT COUNT(*) AS total FROM peminjaman WHERE user_id = $user_id_int"
    );
    if ($res_total_pinjam) $total_pinjam_user = (int)(mysqli_fetch_assoc($res_total_pinjam)["total"] ?? 0);

    // Sedang Dipinjam: masih dipinjam & belum melewati batas waktu kembali
    $res_sedang = mysqli_query($conn,
        "SELECT COUNT(*) AS total FROM peminjaman
         WHERE user_id = $user_id_int AND status = 'dipinjam' AND batas_kembali >= NOW()"
    );
    if ($res_sedang) $sedang_dipinjam_user = (int)(mysqli_fetch_assoc($res_sedang)["total"] ?? 0);

    // Belum Dikembalikan: sudah melewati batas waktu kembali tapi belum dikembalikan (terlambat)
    $res_belum = mysqli_query($conn,
        "SELECT COUNT(*) AS total FROM peminjaman
         WHERE user_id = $user_id_int AND status = 'dipinjam' AND batas_kembali < NOW()"
    );
    if ($res_belum) $belum_dikembalikan_user = (int)(mysqli_fetch_assoc($res_belum)["total"] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&family=Cormorant+Garamond:wght@700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"/>

  <!-- ════════════════════════════════════════════════════════
       PENGATURAN: Sertakan settings_include.php di sini
       Harus sebelum </head> agar tidak ada flash of unstyled
  ════════════════════════════════════════════════════════ -->
  <?php require_once "settings_include.php"; ?>

  <style>
    :root {
      --bg:         #f4f5f7;
      --sidebar-bg: #ffffff;
      --accent:     #2b4fff;
      --accent2:    #ffb800;
      --text:       #1a1a2e;
      --muted:      #7a7a9a;
      --card:       #ffffff;
      --radius:     14px;
      --sidebar-w:  170px;
      --shadow-sm:  0 2px 12px rgba(0,0,0,.05);
      --shadow-md:  0 4px 20px rgba(0,0,0,.10);
      --trans:      .2s cubic-bezier(.22,1,.36,1);
    }
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    body {
      font-family:var(--font-family,'Nunito',sans-serif);
      background:
        radial-gradient(circle at 100% 0%, rgba(43,79,255,.05) 0%, transparent 45%),
        radial-gradient(circle at 0% 100%, rgba(255,184,0,.06) 0%, transparent 40%),
        var(--bg);
      color:var(--text);
      min-height:100vh; display:flex;
      animation:bodyIn .5s ease both;
    }
    @keyframes bodyIn { from{opacity:0} to{opacity:1} }

    /* ── SIDEBAR ── */
    .sidebar {
      width:var(--sidebar-w); min-height:100vh;
      background:var(--sidebar-bg);
      display:flex; flex-direction:column;
      padding:24px 0 20px;
      border-right:1px solid var(--border-color,#e8e9f0);
      position:fixed; top:0; left:0; bottom:0;
      z-index:100; transition:transform var(--trans);
    }
    .sidebar-toggle {
      display:none; position:fixed;
      top:14px; left:14px; z-index:200;
      width:40px; height:40px; border-radius:10px;
      border:none; background:#fff;
      box-shadow:0 2px 10px rgba(0,0,0,.12);
      cursor:pointer; align-items:center; justify-content:center;
    }
    .sidebar-toggle svg { width:20px; height:20px; color:var(--text); }
    .sidebar-overlay {
      display:none; position:fixed; inset:0;
      background:rgba(0,0,0,.4); z-index:90;
    }
    .logo-wrap {
      display:flex; flex-direction:column; align-items:center;
      padding:0 18px 24px; border-bottom:1px solid var(--border-color,#f0f0f5);
    }
    .logo-icon {
      width:52px; height:52px;
      background:linear-gradient(135deg,#f0f0f8 0%,#fff 100%);
      border-radius:14px; display:flex; align-items:center; justify-content:center;
      margin-bottom:8px; box-shadow:0 4px 16px rgba(20,20,20,.15);
    }
    .logo-icon svg { width:28px; height:28px; color:var(--accent); }
    .logo-name {
      font-family:'Cormorant Garamond',serif;
      font-size:1rem; font-weight:700; color:var(--text);
      letter-spacing:.08em; text-align:center;
    }
    .logo-sub { font-size:.58rem; color:var(--muted); letter-spacing:.12em; text-transform:uppercase; text-align:center; margin-top:2px; }
    .nav { flex:1; display:flex; flex-direction:column; gap:2px; padding:16px 10px; }
    .nav-item {
      display:flex; align-items:center; gap:10px;
      padding:10px 14px; border-radius:10px;
      font-size:.82rem; font-weight:600; color:var(--muted);
      cursor:pointer; text-decoration:none;
      transition:background var(--trans), color var(--trans);
    }
    .nav-item:hover  { background:#f0f2ff; color:var(--accent); }
    .nav-item.active { background:#eef0ff; color:var(--accent); }
    .nav-item svg    { width:17px; height:17px; flex-shrink:0; }
    .nav-item.admin-only { color:#e67e22; }
    .nav-item.admin-only:hover { background:#fff4e6; color:#d35400; }
    .nav-bottom { padding:10px 10px 0; border-top:1px solid var(--border-color,#f0f0f5); display:flex; flex-direction:column; gap:2px; }

    /* ── MAIN ── */
    .main { margin-left:var(--sidebar-w); flex:1; padding:24px 24px 32px; min-height:100vh; transition:margin-left var(--trans); }

    /* ── Hero / Welcome banner (slide pertama carousel — sambutan & tombol login) ── */
    .hero-banner {
      position:relative; overflow:hidden;
      background:linear-gradient(120deg,#2b4fff 0%,#4d6bff 55%,#6c3ce8 100%);
      border-radius:18px; padding:26px 28px;
      color:#fff; box-shadow:0 10px 30px rgba(43,79,255,.25);
      display:flex; align-items:center; justify-content:space-between; gap:18px;
      width:100%; height:100%;
    }
    .hero-decor {
      position:absolute; top:-30px; right:-20px; opacity:.16;
      width:190px; height:190px; pointer-events:none;
    }
    .hero-decor2 {
      position:absolute; bottom:-40px; right:110px; opacity:.12;
      width:120px; height:120px; pointer-events:none;
    }
    .hero-left { position:relative; z-index:1; max-width:600px; }
    .hero-greet { font-size:.72rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; opacity:.85; margin-bottom:6px; }
    .hero-title { font-family:'Cormorant Garamond',serif; font-size:1.7rem; font-weight:700; line-height:1.25; margin-bottom:12px; }
    .hero-quote { font-size:.83rem; font-style:italic; line-height:1.6; opacity:.95; border-left:2.5px solid rgba(255,255,255,.55); padding-left:12px; }
    .hero-quote-by { display:block; margin-top:6px; font-size:.7rem; font-weight:700; font-style:normal; opacity:.8; letter-spacing:.03em; }
    .hero-right { position:relative; z-index:1; flex-shrink:0; display:none; }
    .hero-right svg { width:96px; height:96px; opacity:.9; }
    @media (min-width:701px) { .hero-right { display:flex; } }

    /* ── Hero Carousel (banner yang diunggah admin) ── */
    .hero-carousel {
      position:relative; border-radius:18px; overflow:hidden;
      margin-bottom:22px; box-shadow:0 10px 30px rgba(43,79,255,.18);
      animation:fadeUp .5s cubic-bezier(.22,1,.36,1) both;
      height:260px; user-select:none; touch-action:pan-y;
    }
    .hero-track {
      display:flex; height:100%; transition:transform .45s cubic-bezier(.22,1,.36,1);
      cursor:grab;
    }
    .hero-carousel.dragging .hero-track { transition:none; cursor:grabbing; }
    .hero-slide { flex:0 0 100%; height:100%; position:relative; overflow:hidden; }
    .hero-slide img {
      width:100%; height:100%; object-fit:cover; display:block; pointer-events:none; -webkit-user-drag:none;
    }
    .hero-slide-link { position:absolute; inset:0; z-index:2; cursor:pointer; }
    .hero-slide-overlay {
      position:absolute; left:0; right:0; bottom:0; z-index:1;
      padding:22px 26px 20px;
      background:linear-gradient(0deg, rgba(0,0,0,.62) 0%, rgba(0,0,0,.25) 55%, transparent 100%);
      color:#fff;
    }
    .hero-slide-title { font-family:'Cormorant Garamond',serif; font-size:1.45rem; font-weight:700; line-height:1.25; }
    .hero-slide-sub { font-size:.82rem; opacity:.9; margin-top:4px; max-width:600px; }
    .hero-arrow {
      position:absolute; top:50%; transform:translateY(-50%); z-index:3;
      width:34px; height:34px; border-radius:50%; border:none;
      background:rgba(255,255,255,.28); color:#fff; cursor:pointer;
      display:flex; align-items:center; justify-content:center;
      backdrop-filter:blur(2px);
      opacity:0; transition:opacity var(--trans), background var(--trans);
    }
    .hero-carousel:hover .hero-arrow { opacity:1; }
    .hero-arrow:hover { background:rgba(255,255,255,.5); }
    .hero-arrow svg { width:16px; height:16px; }
    .hero-arrow.prev { left:12px; }
    .hero-arrow.next { right:12px; }
    @media (hover:none) {
      /* Perangkat sentuh tidak punya hover — tampilkan panah samar-samar agar tetap terlihat ada */
      .hero-arrow { opacity:.55; }
    }
    .hero-dots {
      position:absolute; bottom:12px; right:18px; z-index:3;
      display:flex; gap:6px;
    }
    .hero-dot {
      width:7px; height:7px; border-radius:50%; background:rgba(255,255,255,.5);
      border:none; cursor:pointer; padding:0; transition:background var(--trans), width var(--trans);
    }
    .hero-dot.active { background:#fff; width:20px; border-radius:4px; }
    @media (max-width:700px) {
      .hero-carousel { height:200px; }
      .hero-slide-title { font-size:1.15rem; }
      .hero-arrow { width:30px; height:30px; }
    }

    /* ── Spotlight (buku unggulan) ── */
    .spotlight-card {
      display:flex; gap:20px; align-items:center;
      background:linear-gradient(120deg,#fff8ec,#fff);
      border:1px solid #f5e2ba; border-radius:var(--radius);
      padding:18px; box-shadow:var(--shadow-sm); cursor:pointer;
      transition:box-shadow var(--trans), transform var(--trans);
    }
    .spotlight-card:hover { box-shadow:var(--shadow-md); transform:translateY(-2px); }
    .spotlight-thumb { width:96px; height:134px; border-radius:10px; flex-shrink:0; overflow:hidden; box-shadow:0 6px 18px rgba(0,0,0,.22); position:relative; }
    .spotlight-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
    .spotlight-badge {
      display:inline-flex; align-items:center; gap:5px;
      background:#ffb800; color:#fff; font-size:.62rem; font-weight:800;
      padding:3px 10px; border-radius:20px; letter-spacing:.06em; text-transform:uppercase;
      margin-bottom:8px;
    }
    .spotlight-badge svg { width:11px; height:11px; }
    .spotlight-title { font-family:'Cormorant Garamond',serif; font-size:1.3rem; font-weight:700; color:var(--text); margin-bottom:3px; line-height:1.2; }
    .spotlight-author { font-size:.78rem; color:var(--muted); font-weight:600; margin-bottom:8px; }
    .spotlight-desc { font-size:.78rem; color:#4a4a68; line-height:1.55; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }

    /* ── Footer ── */
    .site-footer {
      margin-top:30px; padding:26px 4px 10px;
      border-top:1px solid var(--border-color,#e4e5f0);
      animation:fadeUp .5s cubic-bezier(.22,1,.36,1) both;
    }
    .footer-grid { display:grid; grid-template-columns:1.4fr 1fr 1fr; gap:24px; margin-bottom:20px; }
    .footer-brand-name { font-family:'Cormorant Garamond',serif; font-size:1.15rem; font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px; margin-bottom:8px; }
    .footer-brand-name svg { width:22px; height:22px; color:var(--accent); }
    .footer-tagline { font-size:.76rem; color:var(--muted); line-height:1.6; max-width:280px; }
    .footer-heading { font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:var(--text); margin-bottom:10px; }
    .footer-list { list-style:none; display:flex; flex-direction:column; gap:7px; }
    .footer-list li a { font-size:.78rem; color:var(--muted); text-decoration:none; font-weight:600; transition:color var(--trans); }
    .footer-list li a:hover { color:var(--accent); }
    .footer-hours { display:flex; justify-content:space-between; font-size:.76rem; color:var(--muted); font-weight:600; padding:2px 0; }
    .footer-hours span:last-child { color:var(--text); font-weight:700; }
    .footer-bottom { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; padding-top:16px; border-top:1px solid var(--border-color,#eef0f5); font-size:.72rem; color:var(--muted); font-weight:600; }
    .footer-socials { display:flex; gap:8px; }
    .footer-socials a {
      width:30px; height:30px; border-radius:50%; background:#f0f2ff;
      display:flex; align-items:center; justify-content:center; color:var(--accent);
      text-decoration:none; transition:all var(--trans);
    }
    .footer-socials a:hover { background:var(--accent); color:#fff; transform:translateY(-2px); }
    .footer-socials svg { width:14px; height:14px; }
    @media (max-width:700px) { .footer-grid { grid-template-columns:1fr; gap:20px; } }
    .genre-chip-cloud { display:flex; flex-wrap:wrap; gap:8px; }
    .genre-chip {
      display:flex; align-items:center; gap:6px;
      padding:7px 13px; border-radius:50px;
      background:#f8f9ff; border:1px solid var(--card-border,#eef0fc);
      font-size:.75rem; font-weight:700; color:var(--text);
      transition:all var(--trans); cursor:default;
    }
    .genre-chip:hover { background:#eef0ff; border-color:var(--accent); color:var(--accent); transform:translateY(-1px); }
    .genre-chip .count { color:var(--muted); font-weight:600; font-size:.68rem; }
    .genre-chip:hover .count { color:var(--accent); }

    /* ── Statistik pribadi user (mini stat) ── */
    .mini-stat-row { display:flex; gap:10px; flex-wrap:wrap; }
    .mini-stat-box {
      flex:1; min-width:90px; border-radius:12px; padding:14px 12px;
      background:linear-gradient(135deg,#f8f9ff,#eef0ff);
      border:1px solid var(--card-border,#eef0fc);
    }
    .mini-stat-box.warn {
      background:linear-gradient(135deg,#fff1f1,#ffe4e4);
      border-color:#ffd1d1;
    }
    .mini-stat-box.warn .mini-stat-num { color:#dc2626; }
    .mini-stat-num { font-size:1.4rem; font-weight:800; color:var(--accent); }
    .mini-stat-label { font-size:.68rem; font-weight:700; color:var(--muted); margin-top:2px; }
    .mini-stat-note { font-size:.72rem; color:#b91c1c; margin-top:10px; line-height:1.4; }

    /* Grid layout */
    .grid { display:grid; grid-template-columns:1fr 280px; gap:18px; }
    .col-left  { display:flex; flex-direction:column; gap:18px; }
    .col-right { display:flex; flex-direction:column; gap:18px; }

    /* Section card */
    .section-card { background:var(--card); border-radius:var(--radius); padding:18px 18px 20px; box-shadow:var(--shadow-sm); }
    .section-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
    .section-title { font-size:.88rem; font-weight:800; color:var(--text); }
    .view-all { font-size:.72rem; color:var(--accent); cursor:pointer; font-weight:600; text-decoration:none; }

    /* Search results banner */
    .search-banner {
      background:#eef0ff; border-radius:10px;
      padding:10px 16px; font-size:.8rem;
      font-weight:700; color:var(--accent);
      margin-bottom:4px;
      display:flex; align-items:center; gap:8px;
    }
    .search-banner a { color:var(--muted); text-decoration:none; font-size:.75rem; margin-left:auto; }

    /* Trending / book card */
    .trending-list { display:flex; flex-direction:column; gap:14px; }
    .book-card {
      display:flex; align-items:center; gap:16px;
      padding:12px 14px; border-radius:12px;
      background:var(--book-card,#f8f9ff); border:1px solid var(--card-border,#eef0fc);
      cursor:pointer; transition:box-shadow var(--trans), transform var(--trans);
    }
    .book-card:hover { box-shadow:var(--shadow-md); transform:translateY(-2px); }
    .book-thumb {
      width:92px; height:128px; border-radius:8px; flex-shrink:0;
      overflow:hidden; box-shadow:0 4px 14px rgba(0,0,0,.22);
      position:relative;
    }
    .book-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
    .c1 { background:linear-gradient(135deg,#f5a623,#d4820a); }
    .c2 { background:linear-gradient(135deg,#9b59b6,#6c3483); }
    .c3 { background:linear-gradient(135deg,#e74c3c,#922b21); }
    .c4 { background:linear-gradient(135deg,#2ecc71,#1a8a4a); }
    .c5 { background:linear-gradient(135deg,#3498db,#1a5276); }
    .c6 { background:linear-gradient(135deg,#e91e63,#880e4f); }
    .c7 { background:linear-gradient(135deg,#ff5722,#bf360c); }
    .c8 { background:linear-gradient(135deg,#607d8b,#263238); }
    .book-rank {
      position:absolute; top:5px; left:5px;
      width:20px; height:20px; border-radius:50%;
      background:rgba(0,0,0,.5); color:#fff;
      font-size:.62rem; font-weight:800;
      display:flex; align-items:center; justify-content:center;
    }
    .book-info { flex:1; min-width:0; }
    .book-title { font-size:.86rem; font-weight:800; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:3px; }
    .book-author { font-size:.73rem; color:var(--muted); margin-bottom:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .book-genre {
      margin-left:auto; flex-shrink:0;
      font-size:.62rem; font-weight:700;
      color:var(--accent); background:#eef0ff;
      padding:3px 8px; border-radius:50px;
    }
    .book-stok {
      font-size:.62rem; font-weight:700;
      color:#1a8a4a; background:#e8f5e9;
      padding:3px 8px; border-radius:50px;
    }
    .book-rating-mini {
      display:inline-flex; align-items:center; gap:3px;
      font-size:.65rem; font-weight:800; color:#d4820a;
      margin-top:4px;
    }
    .book-rating-mini svg { width:11px; height:11px; }
    .cover-rating-badge {
      position:absolute; bottom:5px; right:5px;
      background:rgba(0,0,0,.62); color:#ffb800;
      font-size:.58rem; font-weight:800;
      padding:2px 6px; border-radius:20px;
      display:flex; align-items:center; gap:2px;
      backdrop-filter:blur(3px);
    }
    .cover-rating-badge svg { width:9px; height:9px; }

    /* New books row */
    .books-row { display:grid; grid-template-columns:repeat(auto-fill, minmax(76px, 1fr)); gap:10px; }
    .book-item { display:flex; flex-direction:column; align-items:center; gap:6px; cursor:pointer; }
    .book-cover {
      width:100%; aspect-ratio:2/3; border-radius:10px;
      overflow:hidden; box-shadow:0 5px 18px rgba(0,0,0,.18);
      transition:transform .2s, box-shadow .2s; position:relative;
    }
    .book-cover:hover { transform:translateY(-5px); box-shadow:0 10px 28px rgba(0,0,0,.24); }
    .book-cover img { width:100%; height:100%; object-fit:cover; display:block; }
    .book-cover-label {
      font-size:.58rem; font-weight:700; color:#fff; text-align:center;
      position:absolute; bottom:6px; left:0; right:0;
      padding:0 5px; text-shadow:0 1px 3px rgba(0,0,0,.6);
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }

    /* Stats card */
    .stats-card { background:var(--card); border-radius:var(--radius); padding:18px; box-shadow:var(--shadow-sm); }
    .stats-inner { display:flex; align-items:center; gap:12px; margin-top:10px; }
    .stats-avatar { width:56px; height:56px; background:#e0e1ec; border-radius:50%; display:flex; align-items:center; justify-content:center; }
    .stats-avatar svg { width:28px; height:28px; color:#aaa; }
    .stat-box { flex:1; padding:10px 12px; border-radius:10px; text-align:center; }
    .stat-box.dark { background:#2b2b2b; }
    .stat-label { font-size:.72rem; font-weight:600; color:var(--muted); margin-bottom:4px; }
    .stat-box.dark .stat-label { color:#aaa; }
    .stat-num { font-size:1.6rem; font-weight:800; color:var(--text); }
    .stat-box.dark .stat-num { color:#fff; }

    /* Right col */
    .history-row { display:flex; gap:8px; flex-wrap:wrap; }
    .history-cover {
      width:68px; height:96px; border-radius:8px;
      overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,.16);
      cursor:pointer; transition:transform var(--trans);
    }
    .history-cover:hover { transform:translateY(-3px); }
    .history-cover img { width:100%; height:100%; object-fit:cover; display:block; }
    .admin-card { background:var(--card); border-radius:var(--radius); padding:20px; display:flex; align-items:center; gap:16px; box-shadow:var(--shadow-sm); }
    .admin-avatar { width:56px; height:56px; border-radius:50%; background:#e0e1ec; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .admin-avatar svg { width:30px; height:30px; color:#aaa; }
    .admin-name { font-size:1.1rem; font-weight:800; color:var(--text); }
    .admin-role { font-size:.72rem; color:var(--muted); margin-top:2px; }

    /* Empty placeholder */
    .empty-row { color:var(--muted); font-size:.8rem; font-weight:600; padding:20px 0; text-align:center; }

    /* Animations */
    .section-card, .stats-card, .admin-card { animation:fadeUp .5s cubic-bezier(.22,1,.36,1) both; }
    @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
    .col-left  .section-card:nth-child(1) { animation-delay:.05s; }
    .col-left  .section-card:nth-child(2) { animation-delay:.10s; }
    .col-left  .stats-card                { animation-delay:.15s; }
    .col-right .section-card:nth-child(1) { animation-delay:.12s; }
    .col-right .section-card:nth-child(2) { animation-delay:.17s; }
    .admin-card                           { animation-delay:.22s; }

    /* ── MODAL DETAIL BUKU ── */
    .detail-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:500; align-items:center; justify-content:center; }
    .detail-overlay.open { display:flex; }
    .detail-modal { background:var(--card,#fff); border-radius:16px; width:100%; max-width:500px; max-height:92vh; overflow-y:auto; box-shadow:0 24px 70px rgba(0,0,0,.28); animation:modalIn .25s cubic-bezier(.22,1,.36,1) both; margin:16px; }
    @keyframes modalIn { from{opacity:0;transform:scale(.94) translateY(10px)} to{opacity:1;transform:scale(1) translateY(0)} }
    .detail-cover { width:100%; aspect-ratio:16/9; border-radius:16px 16px 0 0; overflow:hidden; position:relative; background:#1a1a2e; }
    .detail-cover img { width:100%; height:100%; object-fit:cover; display:block; }
    .detail-cover-placeholder { width:100%; height:100%; display:flex; align-items:center; justify-content:center; }
    .detail-cover-placeholder svg { width:56px; height:56px; color:rgba(255,255,255,.25); }
    .detail-cover-badge { position:absolute; top:12px; right:12px; background:rgba(0,0,0,.55); color:#fff; font-size:.65rem; font-weight:800; padding:4px 10px; border-radius:20px; letter-spacing:.05em; text-transform:uppercase; backdrop-filter:blur(4px); }
    .detail-close-btn { position:absolute; top:12px; left:12px; width:32px; height:32px; border-radius:50%; background:rgba(0,0,0,.5); border:none; display:flex; align-items:center; justify-content:center; cursor:pointer; backdrop-filter:blur(4px); transition:background .2s; }
    .detail-close-btn:hover { background:rgba(0,0,0,.75); }
    .detail-close-btn svg { width:16px; height:16px; color:#fff; }
    .detail-body { padding:20px 22px 24px; }
    .detail-genre-chip { display:inline-block; background:#eef0ff; color:var(--accent); font-size:.65rem; font-weight:800; padding:3px 10px; border-radius:20px; letter-spacing:.05em; text-transform:uppercase; margin-bottom:8px; }
    .detail-title { font-family:'Cormorant Garamond',serif; font-size:1.45rem; font-weight:700; color:var(--text); line-height:1.2; margin-bottom:4px; }
    .detail-author { font-size:.82rem; color:var(--muted); font-weight:600; margin-bottom:14px; }
    .detail-stat-row { display:flex; gap:16px; margin-bottom:16px; }
    .detail-stat { display:flex; align-items:center; gap:6px; font-size:.78rem; font-weight:700; }
    .detail-stat svg { width:15px; height:15px; }
    .detail-stat.likes { color:#e74c3c; }
    .detail-stat.favs  { color:#f39c12; }
    .detail-meta-row { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
    .detail-meta-chip { display:flex; align-items:center; gap:5px; background:#f8f9ff; border:1px solid var(--card-border,#eef0fc); border-radius:8px; padding:6px 11px; font-size:.71rem; font-weight:700; color:var(--muted); }
    .detail-meta-chip svg { width:13px; height:13px; flex-shrink:0; }
    .detail-meta-chip span { color:var(--text); }
    .detail-section-label { font-size:.68rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; margin-bottom:7px; }
    /* Tombol Like & Simpan di modal detail (user only) */
    .detail-footer-btns { display:flex; gap:10px; margin-top:16px; }
    .detail-btn-like, .detail-btn-save {
      flex:1; display:flex; align-items:center; justify-content:center; gap:6px;
      padding:9px 14px; border-radius:8px; border:1.5px solid #e4e5f0;
      background:#fff; font-family:var(--font-family,'Nunito',sans-serif);
      font-size:.8rem; font-weight:700; color:var(--muted);
      cursor:pointer; transition:all var(--trans);
    }
    .detail-btn-like svg, .detail-btn-save svg { width:15px; height:15px; }
    .detail-btn-like:hover { border-color:#e74c3c; color:#e74c3c; }
    .detail-btn-like.aktif { background:#fef2f2; border-color:#e74c3c; color:#e74c3c; }
    .detail-btn-like.aktif svg { fill:currentColor; }
    .detail-btn-save:hover { border-color:var(--accent); color:var(--accent); }
    .detail-btn-save.aktif { background:#eef0ff; border-color:var(--accent); color:var(--accent); }
    .detail-btn-save.aktif svg { fill:currentColor; }

    .detail-sinopsis { font-size:.83rem; line-height:1.7; color:#3a3a5a; background:#f8f9ff; border-radius:10px; padding:14px 16px; border-left:3px solid var(--accent); }
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

    /* Responsive */
    @media (max-width:900px)  { .grid { grid-template-columns:1fr; } .col-right { flex-direction:row; flex-wrap:wrap; } .col-right .section-card { flex:1 1 200px; } .col-right .admin-card { width:100%; } }
    @media (max-width:700px)  { .sidebar { transform:translateX(-100%); } .sidebar.open { transform:translateX(0); } .sidebar-overlay.open { display:block; } .sidebar-toggle { display:flex; } .main { margin-left:0; padding:70px 14px 24px; } .topbar { flex-wrap:wrap; } .search-wrap { max-width:100%; } }
    @media (max-width:480px)  { .grid { gap:12px; } .col-right { flex-direction:column; } .col-right .section-card { flex:none; } }
  </style>
</head>
<body>

<button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <line x1="3" y1="6" x2="21" y2="6"/>
    <line x1="3" y1="12" x2="21" y2="12"/>
    <line x1="3" y1="18" x2="21" y2="18"/>
  </svg>
</button>
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
    <a href="beranda.php" class="nav-item active">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Beranda
    </a>
    <a href="daftar_buku.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
      Daftar buku
    </a>
    <?php if (!$is_admin): ?>
    <a href="buku_simpan.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
      Buku Simpan
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
    <!-- pengaturan rul-->
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

<main class="main">

  <!-- ─── Hero Carousel / Welcome Banner ─── -->
  <?php $total_slides = 1 + count($banners); ?>
  <div class="hero-carousel" id="heroCarousel">
    <div class="hero-track" id="heroTrack">

      <!-- Slide 1: Selamat datang / sambutan (tetap ada, sekarang jadi bagian carousel) -->
      <div class="hero-slide">
        <div class="hero-banner">
          <svg class="hero-decor" viewBox="0 0 24 24" fill="currentColor"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          <svg class="hero-decor2" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <div class="hero-left">
            <div class="hero-greet"><?= htmlspecialchars($sapaan) ?>, <?= htmlspecialchars($user_name) ?> 👋</div>
            <div class="hero-title">Selamat datang kembali di AKSA NOVA</div>
            <div class="hero-quote">
              "<?= htmlspecialchars($kutipan_hari_ini["teks"]) ?>"
              <span class="hero-quote-by">— <?= htmlspecialchars($kutipan_hari_ini["oleh"]) ?></span>
            </div>
            <?php if ($is_guest): ?>
            <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;">
              <a href="sign_in.php" style="background:#fff;color:var(--accent);font-weight:800;font-size:.78rem;padding:8px 16px;border-radius:50px;text-decoration:none;">Masuk</a>
              <a href="sign_up.php" style="background:rgba(255,255,255,.15);color:#fff;font-weight:800;font-size:.78rem;padding:8px 16px;border-radius:50px;text-decoration:none;border:1.5px solid rgba(255,255,255,.6);">Daftar Akun</a>
            </div>
            <?php endif; ?>
          </div>
          <div class="hero-right">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
          </div>
        </div>
      </div>

      <!-- Slide berikutnya: banner yang diunggah admin -->
      <?php foreach ($banners as $b): ?>
        <div class="hero-slide">
          <?php if ($b["link_url"]): ?><a class="hero-slide-link" href="<?= htmlspecialchars($b["link_url"]) ?>"></a><?php endif; ?>
          <img src="<?= htmlspecialchars($b["gambar"]) ?>" alt="<?= htmlspecialchars($b["judul"] ?: "Banner AKSA NOVA") ?>" draggable="false">
          <?php if ($b["judul"] || $b["subjudul"]): ?>
          <div class="hero-slide-overlay">
            <?php if ($b["judul"]): ?><div class="hero-slide-title"><?= htmlspecialchars($b["judul"]) ?></div><?php endif; ?>
            <?php if ($b["subjudul"]): ?><div class="hero-slide-sub"><?= htmlspecialchars($b["subjudul"]) ?></div><?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

    </div>
    <?php if ($total_slides > 1): ?>
      <button type="button" class="hero-arrow prev" onclick="heroGo(-1)" aria-label="Sebelumnya">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <button type="button" class="hero-arrow next" onclick="heroGo(1)" aria-label="Berikutnya">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
      <div class="hero-dots" id="heroDots">
        <?php for ($i = 0; $i < $total_slides; $i++): ?>
          <button type="button" class="hero-dot <?= $i === 0 ? "active" : "" ?>" onclick="heroGoTo(<?= $i ?>)" aria-label="Slide <?= $i + 1 ?>"></button>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="grid">
    <div class="col-left">

      <!-- ─── Spotlight Buku ─── -->
      <?php if (!empty($trending)): $sp = $trending[0]; ?>
      <div class="spotlight-card" onclick="bukaDetailBuku(<?= $sp['id'] ?>)">
        <div class="spotlight-thumb <?= ($sp["gambar"] && file_exists($sp["gambar"])) ? "" : $thumb_colors[0] ?>">
          <?php if ($sp["gambar"] && file_exists($sp["gambar"])): ?>
            <img src="<?= htmlspecialchars($sp["gambar"]) ?>" alt="<?= htmlspecialchars($sp["judul"]) ?>">
          <?php endif; ?>
        </div>
        <div style="min-width:0;">
          <span class="spotlight-badge"><svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>Buku Pilihan</span>
          <div class="spotlight-title"><?= htmlspecialchars($sp["judul"]) ?></div>
          <div class="spotlight-author"><?= htmlspecialchars($sp["penulis"] ?: "Penulis tidak diketahui") ?></div>
          <div class="spotlight-desc"><?= htmlspecialchars($sp["sinopsis"] ?: "Buku paling banyak disukai pembaca AKSA NOVA saat ini. Klik untuk melihat detail selengkapnya.") ?></div>
        </div>
      </div>
      <?php endif; ?>

      <!-- ─── Trending Books ─── -->
      <div class="section-card">
        <div class="section-header">
          <span class="section-title">Trending Books</span>
        </div>
        <?php if (empty($trending)): ?>
          <div class="empty-row">Belum ada buku di katalog.</div>
        <?php else: ?>
        <div class="trending-list">
          <?php foreach ($trending as $i => $buku):
            $col = $thumb_colors[$i % count($thumb_colors)];
          ?>
          <div class="book-card" onclick="bukaDetailBuku(<?= $buku['id'] ?>)">
            <div class="book-thumb <?= $col ?>">
              <div class="book-rank"><?= $i + 1 ?></div>
              <?php if ($buku["gambar"] && file_exists($buku["gambar"])): ?>
                <img src="<?= htmlspecialchars($buku["gambar"]) ?>" alt="<?= htmlspecialchars($buku["judul"]) ?>">
              <?php endif; ?>
            </div>
            <div class="book-info">
              <div class="book-title"><?= htmlspecialchars($buku["judul"]) ?></div>
              <div class="book-author"><?= htmlspecialchars($buku["penulis"] ?: "—") ?></div>
              <?php $rat = $rating_avg[$buku["id"]] ?? null; if ($rat && $rat["total"] > 0): ?>
              <div class="book-rating-mini">
                <?php for ($s=1;$s<=5;$s++): ?>
                  <svg viewBox="0 0 24 24" fill="<?= $s<=floor($rat["avg"])?'#f5a623':'none' ?>" stroke="#f5a623" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <?php endfor; ?>
                <?= $rat["avg"] ?>
              </div>
              <?php endif; ?>
            </div>
            <?php if ($buku["genre"]): ?>
              <span class="book-genre"><?= htmlspecialchars($buku["genre"]) ?></span>
            <?php endif; ?>
            <?php if (($buku["jumlah_like"] ?? 0) > 0): ?>
              <span style="margin-left:auto;flex-shrink:0;font-size:.62rem;font-weight:700;color:#e74c3c;display:flex;align-items:center;gap:3px;">
                <svg viewBox="0 0 24 24" fill="currentColor" width="11" height="11"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                <?= $buku["jumlah_like"] ?>
              </span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- ─── New Books ─── -->
      <div class="section-card">
        <div class="section-header">
          <span class="section-title">New</span>
        </div>
        <?php if (empty($new_books)): ?>
          <div class="empty-row">Belum ada buku.</div>
        <?php else: ?>
        <div class="books-row">
          <?php foreach ($new_books as $i => $buku):
            $col = $thumb_colors[$i % count($thumb_colors)];
          ?>
          <div class="book-item" onclick="bukaDetailBuku(<?= $buku['id'] ?>)">
            <div class="book-cover <?= $buku["gambar"] && file_exists($buku["gambar"]) ? "" : $col ?>">
              <?php if ($buku["gambar"] && file_exists($buku["gambar"])): ?>
                <img src="<?= htmlspecialchars($buku["gambar"]) ?>" alt="<?= htmlspecialchars($buku["judul"]) ?>">
              <?php else: ?>
                <div class="book-cover-label"><?= htmlspecialchars(mb_substr($buku["judul"], 0, 12)) ?></div>
              <?php endif; ?>
              <?php $rat2 = $rating_avg[$buku["id"]] ?? null; if ($rat2 && $rat2["total"] > 0): ?>
              <div class="cover-rating-badge">
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

      <!-- ─── Kategori Populer ─── -->
      <?php if (!empty($genre_populer)): ?>
      <div class="section-card">
        <div class="section-header">
          <span class="section-title">Kategori Populer</span>
        </div>
        <div class="genre-chip-cloud">
          <?php foreach ($genre_populer as $g): ?>
            <span class="genre-chip"><?= htmlspecialchars($g["genre"]) ?> <span class="count">· <?= (int)$g["jumlah"] ?></span></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- ─── Statistik ─── -->
      <?php if ($is_admin): ?>
      <div class="stats-card">
        <div class="section-header">
          <span class="section-title">Total Buku</span>
        </div>
        <div class="stats-inner">
          <div class="stats-avatar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
            </svg>
          </div>
          <div class="stat-box dark">
            <div class="stat-label">judul buku dalam katalog</div>
            <div class="stat-num"><?= $total_buku ?></div>
          </div>
        </div>
      </div>
      <?php elseif ($is_guest): ?>
      <div class="section-card">
        <div class="section-header">
          <span class="section-title">Statistik Kamu</span>
        </div>
        <div class="empty-row" style="padding:10px 0;">
          Masuk untuk melihat buku yang kamu simpan &amp; sukai.<br>
          <a href="sign_in.php" style="color:var(--accent);font-weight:700;">Masuk</a> ·
          <a href="sign_up.php" style="color:var(--accent);font-weight:700;">Daftar</a>
        </div>
      </div>
      <?php else: ?>
      <div class="section-card">
        <div class="section-header">
          <span class="section-title">Peminjaman Kamu</span>
        </div>
        <div class="mini-stat-row">
          <div class="mini-stat-box">
            <div class="mini-stat-num"><?= $total_pinjam_user ?></div>
            <div class="mini-stat-label">Total Pinjam</div>
          </div>
          <div class="mini-stat-box">
            <div class="mini-stat-num"><?= $sedang_dipinjam_user ?></div>
            <div class="mini-stat-label">Sedang Dipinjam</div>
          </div>
          <div class="mini-stat-box <?= $belum_dikembalikan_user > 0 ? 'warn' : '' ?>">
            <div class="mini-stat-num"><?= $belum_dikembalikan_user ?></div>
            <div class="mini-stat-label">Belum Dikembalikan</div>
          </div>
        </div>
        <?php if ($belum_dikembalikan_user > 0): ?>
          <div class="mini-stat-note">⚠ Ada buku yang sudah melewati batas waktu, segera kembalikan ke perpustakaan.</div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </div><!-- /col-left -->

    <!-- RIGHT COLUMN -->
    <div class="col-right">

      <?php if ($is_guest): ?>
      <div class="section-card">
        <div class="section-header">
          <span class="section-title">Fitur Anggota</span>
        </div>
        <div class="empty-row" style="padding:10px 0;">
          Simpan, sukai, dan beri rating buku hanya untuk anggota terdaftar.<br>
          <a href="sign_in.php" style="color:var(--accent);font-weight:700;">Masuk</a> ·
          <a href="sign_up.php" style="color:var(--accent);font-weight:700;">Daftar sekarang</a>
        </div>
      </div>
      <?php elseif (!$is_admin): ?>
      <div class="section-card">
        <div class="section-header">
          <span class="section-title">History</span>
        </div>
        <div class="history-row">
          <?php foreach (array_slice($trending, 0, 4) as $i => $buku):
            $grad = ["linear-gradient(135deg,#f5a623,#d4820a)","linear-gradient(135deg,#e74c3c,#922b21)","linear-gradient(135deg,#9b59b6,#6c3483)","linear-gradient(135deg,#3498db,#1a5276)"][$i];
          ?>
          <div class="history-cover" style="background:<?= $grad ?>">
            <?php if ($buku["gambar"] && file_exists($buku["gambar"])): ?>
              <img src="<?= htmlspecialchars($buku["gambar"]) ?>" alt="">
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="section-card">
        <div class="section-header">
          <span class="section-title">Buku Simpan</span>
          <a href="buku_simpan.php" class="view-all">View all &rsaquo;</a>
        </div>
        <?php if (empty($saved_books)): ?>
          <div class="empty-row" style="padding:16px 0;font-size:.78rem;">Belum ada buku yang disimpan.</div>
        <?php else: ?>
        <div class="history-row">
          <?php foreach ($saved_books as $i => $buku):
            $grad = ["linear-gradient(135deg,#f5a623,#d4820a)","linear-gradient(135deg,#2ecc71,#1a8a4a)","linear-gradient(135deg,#9b59b6,#6c3483)","linear-gradient(135deg,#e74c3c,#922b21)","linear-gradient(135deg,#3498db,#1a5276)","linear-gradient(135deg,#e91e63,#880e4f)"][$i % 6];
          ?>
          <div class="history-cover" style="background:<?= $grad ?>" onclick="bukaDetailBuku(<?= $buku['id'] ?>)" title="<?= htmlspecialchars($buku['judul']) ?>">
            <?php if ($buku["gambar"] && file_exists($buku["gambar"])): ?>
              <img src="<?= htmlspecialchars($buku["gambar"]) ?>" alt="">
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="admin-card">
        <div class="admin-avatar">
          <?php if ($profil_foto && file_exists($profil_foto)): ?>
            <img src="<?= htmlspecialchars($profil_foto) ?>?v=<?= filemtime($profil_foto) ?>" alt="Admin"
                 style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;"/>
          <?php else: ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          <?php endif; ?>
        </div>
        <div>
          <div class="admin-name"><?= htmlspecialchars($profil_nama) ?></div>
          <div class="admin-role">Admin</div>
        </div>
      </div>

    </div><!-- /col-right -->
  </div><!-- /grid -->

  <!-- ─── Footer ─── -->
  <footer class="site-footer">
    <div class="footer-grid">
      <div>
        <div class="footer-brand-name">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          AKSA NOVA
        </div>
        <p class="footer-tagline">Perpustakaan digital yang menghubungkan pembaca dengan ribuan judul buku pilihan — kapan saja, di mana saja.</p>
      </div>
      <div>
        <div class="footer-heading">Tautan Cepat</div>
        <ul class="footer-list">
          <li><a href="beranda.php">Beranda</a></li>
          <li><a href="daftar_buku.php">Daftar Buku</a></li>
          <?php if (!$is_admin): ?><li><a href="buku_simpan.php">Buku Simpan</a></li><?php endif; ?>
          <?php if ($is_admin): ?><li><a href="halaman_admin.php">Panel Admin</a></li><?php endif; ?>
        </ul>
      </div>
      <div>
        <div class="footer-heading">Jam Layanan</div>
        <div class="footer-hours"><span>Senin – Jumat</span><span>08.00 – 20.00</span></div>
        <div class="footer-hours"><span>Sabtu</span><span>09.00 – 17.00</span></div>
        <div class="footer-hours"><span>Minggu</span><span>Tutup</span></div>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date("Y") ?> AKSA NOVA Library Catalog App. Semua hak dilindungi.</span>
      <div class="footer-socials">
        <a href="#" title="Instagram" onclick="return false;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><line x1="17.5" y1="6.5" x2="17.5" y2="6.5"/></svg></a>
        <a href="#" title="Email" onclick="return false;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg></a>
        <a href="#" title="Lokasi" onclick="return false;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></a>
      </div>
    </div>
  </footer>

</main>

<!-- modal aruul -->
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

<?php require_once "pengaturan_panel.php"; ?>

<script>
  const toggle  = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  toggle.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
  overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

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

  // Apakah sesi ini admin / tamu (dikirim dari PHP ke JS)
  const IS_ADMIN = <?= json_encode($is_admin) ?>;
  const IS_GUEST = <?= json_encode($is_guest) ?>;

  // Aksi yang butuh login (suka, simpan, rating, pinjam) — arahkan tamu ke form login
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

    // Tombol aksi bawah modal — hanya tampil untuk user bukan admin
    const userActionBtns = IS_ADMIN ? '' : `
      <div class="detail-footer-btns" style="margin-top:16px;">
        <button id="modalLikeBtn_${b.id}" class="detail-btn-like ${b.user_like ? 'aktif' : ''}" onclick="toggleLikeModal(this, ${b.id})">
          <svg viewBox="0 0 24 24" fill="${b.user_like ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          <span id="modalLikeLabel_${b.id}">${b.user_like ? 'Disukai' : 'Suka'}</span>
        </button>
        <button id="modalSaveBtn_${b.id}" class="detail-btn-save ${b.user_favorit ? 'aktif' : ''}" onclick="toggleSimpanModal(this, ${b.id})">
          <svg viewBox="0 0 24 24" fill="${b.user_favorit ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
          <span id="modalSaveLabel_${b.id}">${b.user_favorit ? 'Tersimpan' : 'Simpan'}</span>
        </button>
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
          <div style="border-left:1px solid #e0e0e0;height:16px;"></div>
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
        ${b.sinopsis ? `<div class="detail-sinopsis">${escHTML(b.sinopsis).replace(/\n/g,'<br>')}</div>` : `<div class="detail-sinopsis"><span class="detail-sinopsis-empty">Sinopsis belum tersedia untuk buku ini.</span></div>`}
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

  // ─── Hero Carousel: geser/drag, panah, dots, autoplay ───
  (function () {
    const carousel = document.getElementById('heroCarousel');
    const track     = document.getElementById('heroTrack');
    if (!carousel || !track) return;

    const slides = track.children.length;
    let current  = 0;
    let startX   = 0;
    let deltaX   = 0;
    let dragging = false;
    let autoplayTimer = null;

    window.heroGoTo = function (idx) {
      current = ((idx % slides) + slides) % slides;
      track.style.transform = `translateX(${-current * 100}%)`;
      document.querySelectorAll('#heroDots .hero-dot').forEach((d, i) => d.classList.toggle('active', i === current));
      restartAutoplay();
    };
    window.heroGo = function (dir) { heroGoTo(current + dir); };

    function restartAutoplay() {
      if (slides <= 1) return;
      clearInterval(autoplayTimer);
      autoplayTimer = setInterval(() => heroGoTo(current + 1), 5000);
    }

    // ── Drag / swipe (mouse & touch) ──
    function dragStart(x) {
      dragging = true; startX = x; deltaX = 0;
      carousel.classList.add('dragging');
      clearInterval(autoplayTimer);
    }
    function dragMove(x) {
      if (!dragging) return;
      deltaX = x - startX;
      const pct = (deltaX / carousel.offsetWidth) * 100;
      track.style.transform = `translateX(${-current * 100 + pct}%)`;
    }
    function dragEnd() {
      if (!dragging) return;
      dragging = false;
      carousel.classList.remove('dragging');
      const threshold = carousel.offsetWidth * 0.15;
      const moved = Math.abs(deltaX) > 5;
      if (deltaX > threshold) heroGoTo(current - 1);
      else if (deltaX < -threshold) heroGoTo(current + 1);
      else heroGoTo(current);
      if (moved) {
        // Cegah link banner ikut ter-klik setelah selesai menggeser
        track.querySelectorAll('.hero-slide-link').forEach(a => {
          a.style.pointerEvents = 'none';
          setTimeout(() => { a.style.pointerEvents = ''; }, 200);
        });
      }
    }

    track.addEventListener('mousedown', e => { dragStart(e.clientX); e.preventDefault(); });
    window.addEventListener('mousemove', e => dragMove(e.clientX));
    window.addEventListener('mouseup', dragEnd);

    track.addEventListener('touchstart', e => dragStart(e.touches[0].clientX), { passive: true });
    track.addEventListener('touchmove',  e => dragMove(e.touches[0].clientX),  { passive: true });
    track.addEventListener('touchend', dragEnd);

    carousel.addEventListener('mouseenter', () => clearInterval(autoplayTimer));
    carousel.addEventListener('mouseleave', restartAutoplay);

    restartAutoplay();
  })();

  function escHTML(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function starSVG(filled) {
    return `<svg viewBox="0 0 24 24" fill="${filled?'#f5a623':'none'}" stroke="#f5a623" stroke-width="2" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>`;
  }
  function renderStarsDisplay(avg, total) {
    let stars = '';
    for (let i = 1; i <= 5; i++) stars += starSVG(i <= Math.round(avg));
    return `<div class="stars-display">${stars}</div><span class="rating-text">${total > 0 ? avg + ' (' + total + ' ulasan)' : 'Belum ada rating'}</span>`;
  }
  function renderStarsInput(bukuId, userRating) {
    let html = `<div class="stars-input" id="starsInput_${bukuId}">`;
    for (let i = 1; i <= 5; i++) {
      html += `<svg viewBox="0 0 24 24" fill="${i<=userRating?'#f5a623':'none'}" stroke="#f5a623" stroke-width="2" stroke-linejoin="round" class="${i<=userRating?'aktif':''}" onmouseover="hoverStar(${bukuId},${i})" onmouseout="resetStarHover(${bukuId})" onclick="submitRating(${bukuId},${i})"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>`;
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
</script>
</body>
</html>