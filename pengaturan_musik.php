<?php
// pengaturan_musik.php — Panel admin untuk mengatur musik latar halaman index (AKSA NOVA)
session_start();

// Cek login & role admin
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: sign_in.php");
    exit;
}

require_once "db.php";

$page_title = "Pengaturan Musik – AKSA NOVA";
$msg        = "";
$msg_type   = "";

// ─── Profil admin (untuk avatar & nama di sidebar, sama seperti halaman_admin.php) ───
$profil_res  = mysqli_query($conn, "SELECT * FROM admin_profile LIMIT 1");
$profil      = $profil_res ? mysqli_fetch_assoc($profil_res) : null;
$admin_name  = $profil["display_name"] ?? ($_SESSION["user_name"] ?? "Admin");
$admin_foto  = $profil["foto"] ?? "";

// ─────────────────────────────────────────────
//  HELPER: simpan satu baris pengaturan (kunci → nilai)
// ─────────────────────────────────────────────
function simpanPengaturan($conn, $kunci, $nilai) {
    $kunci_e = mysqli_real_escape_string($conn, $kunci);
    $nilai_e = mysqli_real_escape_string($conn, $nilai);
    $cek = mysqli_query($conn, "SELECT kunci FROM pengaturan WHERE kunci = '$kunci_e' LIMIT 1");
    if ($cek && mysqli_num_rows($cek) > 0) {
        mysqli_query($conn, "UPDATE pengaturan SET nilai = '$nilai_e' WHERE kunci = '$kunci_e'");
    } else {
        mysqli_query($conn, "INSERT INTO pengaturan (kunci, nilai) VALUES ('$kunci_e', '$nilai_e')");
    }
}

// ─────────────────────────────────────────────
//  Ambil pengaturan musik saat ini
// ─────────────────────────────────────────────
$musik_aktif = 0;
$musik_file  = "";
$musik_judul = "";
$res = mysqli_query($conn, "SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('musik_aktif','musik_file','musik_judul')");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        if ($row["kunci"] === "musik_aktif") $musik_aktif = (int) $row["nilai"];
        if ($row["kunci"] === "musik_file")  $musik_file  = $row["nilai"];
        if ($row["kunci"] === "musik_judul") $musik_judul = $row["nilai"];
    }
}

// ─────────────────────────────────────────────
//  AKSI: simpan pengaturan (upload / pilih file, toggle aktif, judul)
// ─────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "simpan";

    if ($action === "hapus_file") {
        // Hapus file musik yang tersimpan & matikan fitur
        if ($musik_file && file_exists($musik_file)) {
            @unlink($musik_file);
        }
        simpanPengaturan($conn, "musik_file", "");
        simpanPengaturan($conn, "musik_aktif", "0");
        $msg = "Musik latar dihapus dan fitur dinonaktifkan."; $msg_type = "success";
        $musik_file = ""; $musik_aktif = 0;
    } else {
        $aktif_baru = isset($_POST["musik_aktif"]) ? 1 : 0;
        $judul_baru = trim($_POST["musik_judul"] ?? "");

        $file_final    = $musik_file;
        $upload_error  = "";

        if (isset($_FILES["musik_file"]) && $_FILES["musik_file"]["error"] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES["musik_file"];
            if ($file["error"] !== UPLOAD_ERR_OK) {
                $upload_error = "Upload musik gagal (kode error {$file['error']}).";
            } elseif ($file["size"] > 15 * 1024 * 1024) {
                $upload_error = "Ukuran file melebihi batas 15 MB.";
            } else {
                $ext     = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
                $allowed = ["mp3", "ogg", "wav", "m4a"];
                if (!in_array($ext, $allowed)) {
                    $upload_error = "Format tidak didukung (hanya MP3, OGG, WAV, M4A).";
                } else {
                    $dir = "uploads/musik/";
                    if (!is_dir($dir)) mkdir($dir, 0775, true);
                    // Hapus file lama supaya tidak menumpuk
                    if ($musik_file && file_exists($musik_file)) @unlink($musik_file);
                    $filename   = uniqid("musik_") . "." . $ext;
                    move_uploaded_file($file["tmp_name"], $dir . $filename);
                    $file_final = $dir . $filename;
                }
            }
        }

        if ($upload_error !== "") {
            $msg = $upload_error; $msg_type = "error";
        } elseif ($aktif_baru === 1 && $file_final === "") {
            $msg = "Aktifkan musik memerlukan file musik. Silakan unggah file terlebih dahulu."; $msg_type = "error";
        } else {
            simpanPengaturan($conn, "musik_file", $file_final);
            simpanPengaturan($conn, "musik_judul", $judul_baru);
            simpanPengaturan($conn, "musik_aktif", (string) $aktif_baru);
            $msg = "Pengaturan musik berhasil disimpan!"; $msg_type = "success";
            $musik_file  = $file_final;
            $musik_judul = $judul_baru;
            $musik_aktif = $aktif_baru;
        }
    }
}
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
    --gold:        #c89a4e;
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

  /* ── SIDEBAR (identik dengan halaman_admin.php agar konsisten) ── */
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
    width:40px; height:40px; border-radius:10px; border:none;
    background:#fff; box-shadow:0 2px 10px rgba(0,0,0,.15);
    cursor:pointer; align-items:center; justify-content:center;
  }
  .sidebar-toggle svg { width:20px; height:20px; }

  .avatar-circle {
    width:96px; height:96px; border-radius:50%;
    background:#c0c0c8; overflow:hidden;
    border:3px solid rgba(255,255,255,.25);
    display:flex; align-items:center; justify-content:center;
    margin-bottom: 14px;
  }
  .avatar-circle img { width:100%; height:100%; object-fit:cover; display:block; }
  .avatar-circle .default-icon { width:52px; height:52px; color:#888; }

  .admin-name-label { color:#fff; font-size:.95rem; font-weight:700; margin-bottom:8px; text-align:center; }

  .sidebar-btn {
    width:100%; display:flex; align-items:center; gap:10px;
    padding:10px 14px; border-radius:8px; border:none;
    background:rgba(255,255,255,.12); color:#fff;
    font-family:'Nunito',sans-serif; font-size:.82rem; font-weight:700;
    cursor:pointer; margin-bottom:8px;
    transition:background var(--trans);
    text-align:left; text-decoration:none;
    flex-shrink:0;
  }
  .sidebar-btn:hover { background:rgba(255,255,255,.22); }
  .sidebar-btn.active { background:rgba(255,255,255,.3); }
  .sidebar-btn svg { width:16px; height:16px; flex-shrink:0; }

  /* ── MAIN ── */
  .main { margin-left:var(--sidebar-w); flex:1; padding:26px 24px; transition:margin-left var(--trans); max-width: 760px; }

  .alert {
    padding:12px 18px; border-radius:8px; font-size:.82rem;
    font-weight:700; margin-bottom:16px; animation:fadeUp .4s both;
  }
  .alert-success { background:#e8f5e9; color:#1a8a4a; border:1px solid #c8e6c9; }
  .alert-error   { background:#fce4ec; color:#c0392b; border:1px solid #f8bbd0; }
  @keyframes fadeUp { from { opacity:0; transform:translateY(8px);} to { opacity:1; transform:translateY(0);} }

  .content-header { margin-bottom: 18px; animation: fadeUp .5s .05s both; }
  .content-title { font-family:'Cormorant Garamond',serif; font-size:1.7rem; font-weight:700; color:var(--text); }
  .content-sub { font-size:.85rem; color:var(--muted); margin-top:4px; }

  .card {
    background: var(--card);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 26px;
    margin-bottom: 20px;
    animation: fadeUp .5s .08s both;
  }
  .card h2 {
    font-family:'Cormorant Garamond',serif; font-size:1.2rem; font-weight:700;
    color: var(--text); margin-bottom: 4px;
  }
  .card .hint { font-size:.78rem; color: var(--muted); margin-bottom: 18px; line-height:1.5; }

  .field { margin-bottom: 18px; }
  .field label {
    display:block; font-size:.78rem; font-weight:700; color:var(--text);
    margin-bottom: 6px; text-transform:uppercase; letter-spacing:.03em;
  }
  .field input[type="text"] {
    width:100%; padding:10px 14px; border-radius:8px;
    border:1px solid #ddd; font-family:'Nunito',sans-serif; font-size:.88rem;
    color: var(--text); outline: none; transition: border-color .18s ease;
  }
  .field input[type="text"]:focus { border-color: var(--accent); }
  .field .file-drop {
    border: 1.6px dashed #ccc; border-radius: 10px; padding: 20px;
    text-align:center; cursor:pointer; transition: border-color .18s ease, background .18s ease;
    position: relative;
  }
  .field .file-drop:hover { border-color: var(--accent); background: #fafafc; }
  .field .file-drop input[type="file"] {
    position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%;
  }
  .field .file-drop svg { width:26px; height:26px; color:#999; margin-bottom:8px; }
  .field .file-drop .file-drop-text { font-size:.82rem; color: var(--muted); }
  .field .file-drop .file-name { font-size:.82rem; font-weight:700; color: var(--text); margin-top:4px; }

  /* Toggle switch */
  .toggle-row {
    display:flex; align-items:center; justify-content:space-between;
    padding: 4px 0 18px;
  }
  .toggle-row .toggle-label { font-size:.9rem; font-weight:700; color: var(--text); }
  .toggle-row .toggle-desc  { font-size:.78rem; color: var(--muted); margin-top:2px; }
  .switch { position:relative; display:inline-block; width:46px; height:26px; flex-shrink:0; }
  .switch input { opacity:0; width:0; height:0; }
  .switch .slider {
    position:absolute; cursor:pointer; inset:0;
    background:#d0d0d8; border-radius:999px; transition: background .2s ease;
  }
  .switch .slider::before {
    content:''; position:absolute; width:20px; height:20px; left:3px; top:3px;
    background:#fff; border-radius:50%; transition: transform .2s ease;
    box-shadow: 0 1px 4px rgba(0,0,0,.3);
  }
  .switch input:checked + .slider { background: var(--gold); }
  .switch input:checked + .slider::before { transform: translateX(20px); }

  .current-preview {
    display:flex; align-items:center; gap:12px;
    padding: 12px 14px; border-radius: 10px;
    background:#f6f6f9; border:1px solid #ececf2;
    margin-bottom: 18px;
  }
  .current-preview .icon-wrap {
    width:38px; height:38px; border-radius:50%;
    background: var(--gold); color:#fff;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
  }
  .current-preview .icon-wrap svg { width:18px; height:18px; }
  .current-preview .info { flex:1; min-width:0; }
  .current-preview .info .name { font-size:.85rem; font-weight:700; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .current-preview .info .status { font-size:.74rem; color: var(--muted); }
  .current-preview audio { max-width: 220px; height: 32px; }

  .btn-row { display:flex; gap:10px; flex-wrap:wrap; margin-top: 6px; }
  .btn-simpan {
    padding:11px 26px; border-radius:8px; border:none;
    background: var(--btn-primary); color:#fff;
    font-family:'Nunito',sans-serif; font-size:.85rem; font-weight:700;
    cursor:pointer; transition: background var(--trans), box-shadow var(--trans);
  }
  .btn-simpan:hover { background:#222; box-shadow:0 4px 14px rgba(0,0,0,.2); }
  .btn-hapus-file {
    padding:11px 22px; border-radius:8px; border:1px solid #f0b0b0;
    background:#fff; color:#c0392b;
    font-family:'Nunito',sans-serif; font-size:.85rem; font-weight:700;
    cursor:pointer; transition: background var(--trans);
  }
  .btn-hapus-file:hover { background:#fdeeee; }

  /* Responsive */
  @media (max-width:860px) {
    :root { --sidebar-w:170px; }
    .avatar-circle { width:76px; height:76px; }
  }
  @media (max-width:620px) {
    .sidebar { transform:translateX(-100%); width:220px; }
    .sidebar.open { transform:translateX(0); }
    .sidebar-toggle { display:flex; }
    .main { margin-left:0; padding:70px 14px 24px; }
    .card { padding: 18px; }
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
  <div class="sidebar-header">
    <div class="avatar-circle">
      <?php if ($admin_foto && file_exists($admin_foto)): ?>
        <img src="<?= htmlspecialchars($admin_foto) ?>?v=<?= filemtime($admin_foto) ?>" alt="Admin"/>
      <?php else: ?>
        <svg class="default-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
        </svg>
      <?php endif; ?>
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
      Daftar Anggota
    </a>
    <a class="sidebar-btn" href="pinjam_buku.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
      </svg>
      Pinjam Buku
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
    <a class="sidebar-btn active" href="pengaturan_musik.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>
      </svg>
      Pengaturan Musik
    </a>
    <a class="sidebar-btn" href="beranda.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      Kembali
    </a>
  </nav>
</aside>

<main class="main">

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type ?>">
    <?= $msg_type === "success" ? "✅" : "❌" ?> <?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>

  <div class="content-header">
    <div class="content-title">Pengaturan Musik</div>
    <div class="content-sub">Atur musik latar yang diputar di halaman utama (index) untuk pengunjung.</div>
  </div>

  <?php if ($musik_file): ?>
  <div class="current-preview">
    <div class="icon-wrap">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
    </div>
    <div class="info">
      <div class="name"><?= htmlspecialchars($musik_judul ?: basename($musik_file)) ?></div>
      <div class="status">Status saat ini: <?= $musik_aktif ? "Aktif — tombol musik tampil di halaman utama" : "Nonaktif — tombol musik disembunyikan" ?></div>
    </div>
    <audio controls src="<?= htmlspecialchars($musik_file) ?>"></audio>
  </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="simpan">

    <div class="card">
      <h2>Nyalakan Musik Latar</h2>
      <p class="hint">Jika diaktifkan, pengunjung akan melihat tombol musik di halaman utama (di samping tombol Masuk) yang bisa mereka nyalakan/matikan sendiri.</p>

      <div class="toggle-row">
        <div>
          <div class="toggle-label">Tampilkan tombol musik</div>
          <div class="toggle-desc">Memerlukan file musik terunggah di bawah.</div>
        </div>
        <label class="switch">
          <input type="checkbox" name="musik_aktif" <?= $musik_aktif ? "checked" : "" ?>>
          <span class="slider"></span>
        </label>
      </div>

      <div class="field">
        <label for="musik_judul">Nama / Judul Musik</label>
        <input type="text" id="musik_judul" name="musik_judul" placeholder="Contoh: Lo-fi Perpustakaan" value="<?= htmlspecialchars($musik_judul) ?>">
      </div>

      <div class="field">
        <label>File Musik (MP3 / OGG / WAV / M4A, maks 15 MB)</label>
        <div class="file-drop" id="fileDrop">
          <input type="file" name="musik_file" id="musikFileInput" accept=".mp3,.ogg,.wav,.m4a,audio/*">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
          <div class="file-drop-text">Klik untuk memilih file, atau seret file ke sini</div>
          <div class="file-name" id="fileNameLabel"><?= $musik_file ? "Saat ini: " . htmlspecialchars(basename($musik_file)) : "" ?></div>
        </div>
      </div>

      <div class="btn-row">
        <button type="submit" class="btn-simpan">Simpan Pengaturan</button>
      </div>
    </div>
  </form>

  <?php if ($musik_file): ?>
  <form method="POST" onsubmit="return confirm('Hapus file musik ini dan matikan fitur musik di halaman utama?');">
    <input type="hidden" name="action" value="hapus_file">
    <div class="btn-row">
      <button type="submit" class="btn-hapus-file">Hapus File Musik</button>
    </div>
  </form>
  <?php endif; ?>

</main>

<script>
  const toggle  = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  toggle.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
  overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

  const musikFileInput = document.getElementById('musikFileInput');
  const fileNameLabel  = document.getElementById('fileNameLabel');
  musikFileInput.addEventListener('change', () => {
    if (musikFileInput.files && musikFileInput.files[0]) {
      fileNameLabel.textContent = "Dipilih: " + musikFileInput.files[0].name;
    }
  });
</script>

</body>
</html>
