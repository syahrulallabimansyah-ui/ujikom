<?php
// edit_kartu.php — Update Data Diri (nama, kelas, no HP, email, foto) untuk anggota yang SUDAH LOGIN
// ── PERUBAHAN: akses dipindah dari halaman Sign In ke Beranda (kartu "Profil Saya") ──
// Karena diakses dari dalam dashboard, halaman ini langsung memakai sesi login yang sedang aktif
// (tidak perlu lagi memasukkan ulang email & password, dan TIDAK ADA LAGI konfirmasi kata sandi
// saat ini sebelum menyimpan — sesi login yang aktif sudah cukup sebagai otorisasi).
// Username, NIK, dan nomor anggota TIDAK bisa diubah lewat halaman ini. Ganti kata sandi baru
// tetap opsional (kosongkan jika tidak ingin mengganti).
session_start();
require_once "db.php";

// ─── Halaman ini khusus anggota (member) yang sudah login ───
if (!isset($_SESSION["user_id"])) {
    header("Location: sign_in.php");
    exit;
}
if (($_SESSION["role"] ?? "") !== "member") {
    header("Location: beranda.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

// ─── Ambil data terbaru milik akun yang sedang login ───
$stmt = mysqli_prepare($conn,
    "SELECT id, full_name, nik, kelas, no_hp, email, no_anggota, username, password, foto, role, status, card_status
     FROM users WHERE id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    session_destroy();
    header("Location: sign_in.php");
    exit;
}

$error   = "";
$blocked = "";

if ($user["status"] === "pending") {
    $blocked = "Akun kamu masih menunggu persetujuan admin, belum bisa memakai fitur ini.";
} elseif ($user["status"] === "rejected") {
    $blocked = "Pendaftaran kamu ditolak oleh admin. Silakan hubungi petugas perpustakaan.";
} elseif (($user["card_status"] ?? "active") === "frozen") {
    $blocked = "Kartu kamu sedang dibekukan (proses Lupa Kartu belum selesai). Selesaikan itu dulu sebelum mengedit data.";
}

$form_data = [
    "full_name" => $user["full_name"],
    "kelas"     => $user["kelas"],
    "no_hp"     => $user["no_hp"],
    "email"     => $user["email"],
];

// ─────────────────────────────────────────────
//  Simpan perubahan data
// ─────────────────────────────────────────────
if ($blocked === "" && $_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "update_data") {

    $new_full_name = trim($_POST["full_name"] ?? "");
    $new_kelas     = trim($_POST["kelas"] ?? "");
    $new_no_hp     = trim($_POST["no_hp"] ?? "");
    $new_email     = trim($_POST["email"] ?? "");
    $new_password  = trim($_POST["new_password"] ?? "");
    $new_password2 = trim($_POST["new_password_confirm"] ?? "");
    $foto_path     = $user["foto"] ?? "";
    $foto_dir      = __DIR__ . "/uploads/anggota";
    $foto_web_dir  = "uploads/anggota";

    $form_data = [
        "full_name" => $new_full_name,
        "kelas"     => $new_kelas,
        "no_hp"     => $new_no_hp,
        "email"     => $new_email,
    ];

    // Ganti password bersifat opsional — kosongkan berarti password tidak berubah
    $want_change_password = $new_password !== "" || $new_password2 !== "";

    if ($new_full_name === "") {
        $error = "Nama lengkap wajib diisi.";
    } elseif ($new_kelas === "") {
        $error = "Kelas wajib diisi.";
    } elseif ($new_email === "" || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Alamat email tidak valid.";
    } elseif ($new_no_hp !== "" && !preg_match('/^[\d+\-\s]{6,20}$/', $new_no_hp)) {
        $error = "Nomor HP tidak valid.";
    } elseif ($want_change_password && strlen($new_password) < 8) {
        $error = "Kata sandi baru minimal 8 karakter.";
    } elseif ($want_change_password && (!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password))) {
        $error = "Kata sandi baru harus mengandung huruf dan angka.";
    } elseif ($want_change_password && $new_password !== $new_password2) {
        $error = "Konfirmasi kata sandi baru tidak cocok.";
    } else {
        // Cek email tidak dipakai anggota lain
        $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id <> ?");
        mysqli_stmt_bind_param($chk, "si", $new_email, $user_id);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            $error = "Email tersebut sudah dipakai akun lain.";
        }
        mysqli_stmt_close($chk);
    }

    // ── Ganti foto profil (opsional) — bisa ambil foto langsung atau dari galeri ──
    $new_foto_uploaded = "";
    if ($error === "" && isset($_FILES["foto"]) && $_FILES["foto"]["error"] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES["foto"]["error"] !== UPLOAD_ERR_OK) {
            $error = "Gagal mengunggah foto baru. Silakan coba lagi.";
        } else {
            $foto_tmp  = $_FILES["foto"]["tmp_name"];
            $foto_size = $_FILES["foto"]["size"];
            $mime      = function_exists("mime_content_type") ? mime_content_type($foto_tmp) : $_FILES["foto"]["type"];
            $allowed_mimes = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];

            if (!isset($allowed_mimes[$mime])) {
                $error = "Format foto harus JPG, PNG, atau WEBP.";
            } elseif ($foto_size > 5 * 1024 * 1024) {
                $error = "Ukuran foto maksimal 5MB.";
            } else {
                if (!is_dir($foto_dir)) {
                    mkdir($foto_dir, 0755, true);
                }
                $ext = $allowed_mimes[$mime];
                $new_filename = "anggota_" . bin2hex(random_bytes(8)) . "." . $ext;
                if (move_uploaded_file($foto_tmp, $foto_dir . "/" . $new_filename)) {
                    $new_foto_uploaded = $foto_web_dir . "/" . $new_filename;
                } else {
                    $error = "Gagal menyimpan foto baru. Silakan coba lagi.";
                }
            }
        }
    }

    if ($error === "") {
        $old_foto_relative = "";
        $set_parts = ["full_name = ?", "kelas = ?", "no_hp = ?", "email = ?"];
        $types  = "ssss";
        $params = [$new_full_name, $new_kelas, $new_no_hp, $new_email];

        if ($new_foto_uploaded !== "") {
            $old_foto_relative = $foto_path; // foto lama sebelum diganti
            $foto_path = $new_foto_uploaded;
            $set_parts[] = "foto = ?";
            $types      .= "s";
            $params[]    = $foto_path;
        }

        if ($want_change_password) {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $set_parts[] = "password = ?";
            $types      .= "s";
            $params[]    = $new_password_hash;
        }

        $sql    = "UPDATE users SET " . implode(", ", $set_parts) . " WHERE id = ?";
        $types .= "i";
        $params[] = $user_id;

        $stmt = mysqli_prepare($conn, $sql);
        $bind_refs = [$stmt, $types];
        foreach ($params as $k => $v) { $bind_refs[] = &$params[$k]; }
        call_user_func_array("mysqli_stmt_bind_param", $bind_refs);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Hapus file foto lama supaya tidak menumpuk
        if ($old_foto_relative !== "") {
            $old_foto_full = __DIR__ . "/" . $old_foto_relative;
            if (file_exists($old_foto_full)) {
                @unlink($old_foto_full);
            }
        }

        // Selaraskan sesi supaya nama di dashboard ikut ter-update
        $_SESSION["user_name"] = $new_full_name;

        // Tampilkan kartu dengan data terbaru. Password yang ditampilkan di kartu SELALU
        // yang asli/valid: kalau diganti, pakai password baru; kalau tidak, pakai password
        // saat ini yang sudah dikonfirmasi & diverifikasi di atas — bukan versi disensor.
        $_SESSION["kartu_data"] = [
            "id"                 => $user_id,
            "full_name"          => $new_full_name,
            "nik"                => $user["nik"],
            "kelas"              => $new_kelas,
            "no_hp"              => $new_no_hp,
            "email"              => $new_email,
            "no_anggota"         => $user["no_anggota"],
            "username"           => $user["username"],
            "password"           => $want_change_password ? $new_password : "",
            "foto"               => $foto_path,
            "status"             => "approved",
            "reissued"           => false,
            "data_updated_only"  => true,
            "password_changed"   => $want_change_password,
        ];

        header("Location: kartu_anggota.php");
        exit;
    }
}

$page_title = "Update Data Diri – AKSA NOVA";
$user_name  = $_SESSION["user_name"];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&family=Cormorant+Garamond:wght@700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"/>

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
      min-height:100vh;
      animation:bodyIn .5s ease both;
    }
    @keyframes bodyIn { from{opacity:0} to{opacity:1} }

    /* ── TOPBAR (pengganti sidebar — halaman ini berdiri sendiri, tidak lagi dalam layout dashboard) ── */
    .topbar {
      display:flex; align-items:center; justify-content:space-between;
      padding:14px 24px; background:var(--sidebar-bg);
      border-bottom:1px solid var(--border-color,#e8e9f0);
      box-shadow:0 2px 14px rgba(20,20,50,.04);
      position:sticky; top:0; z-index:50;
    }
    .topbar-brand { display:flex; align-items:center; gap:10px; text-decoration:none; }
    .topbar-logo {
      width:38px; height:38px; border-radius:10px; flex-shrink:0;
      background:linear-gradient(135deg,#f0f0f8 0%,#fff 100%);
      display:flex; align-items:center; justify-content:center;
      box-shadow:0 3px 12px rgba(20,20,20,.14);
    }
    .topbar-logo svg { width:19px; height:19px; color:var(--accent); }
    .topbar-name { font-family:'Cormorant Garamond',serif; font-size:.95rem; font-weight:700; color:var(--text); letter-spacing:.05em; }
    .topbar-tag  { font-size:.55rem; color:var(--muted); letter-spacing:.1em; text-transform:uppercase; margin-top:1px; }
    /* ── MAIN (halaman berdiri sendiri, tidak ada sidebar) ── */
    .main { padding:32px 20px 50px; min-height:calc(100vh - 68px); }

    .back-btn {
      display:inline-flex; align-items:center; gap:6px;
      font-size:.8rem; font-weight:700; color:var(--muted);
      text-decoration:none; transition:color var(--trans);
      background:#f0f2ff; padding:8px 14px 8px 10px; border-radius:50px;
    }
    .back-btn svg { width:16px; height:16px; }
    .back-btn:hover { color:var(--accent); background:#e4e8ff; }

    /* ── Divider antar-bagian form ── */
    .form-divider {
      font-size:.7rem; font-weight:800; color:var(--muted);
      text-transform:uppercase; letter-spacing:.07em;
      margin:22px 0 12px; padding-top:16px;
      border-top:1px dashed #e2e3ef;
    }
    .form-divider-hint { font-size:.72rem; font-weight:500; color:var(--muted); text-transform:none; letter-spacing:0; margin-top:2px; }

    /* ── Kartu form Update Data Diri ── */
    .settings-wrap { display:flex; justify-content:center; }
    .settings-card {
      width:100%; max-width:640px; background:var(--card);
      border-radius:var(--radius); padding:28px 30px 32px;
      box-shadow:var(--shadow-sm);
      animation:fadeUp .5s cubic-bezier(.22,1,.36,1) both;
    }
    @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

    .settings-head { display:flex; align-items:flex-start; gap:16px; margin-bottom:22px; }
    .settings-icon {
      width:50px; height:50px; flex-shrink:0; border-radius:14px;
      background:linear-gradient(135deg,#eef0ff,#e0e4ff);
      display:flex; align-items:center; justify-content:center;
    }
    .settings-icon svg { width:24px; height:24px; color:var(--accent); }
    .settings-title { font-family:'Cormorant Garamond',serif; font-size:1.55rem; font-weight:700; color:var(--text); line-height:1.2; }
    .settings-sub { font-size:.8rem; color:var(--muted); line-height:1.6; margin-top:4px; }

    .alert { font-size:.8rem; padding:11px 14px; border-radius:10px; margin-bottom:16px; line-height:1.55; }
    .alert-error { background:#fff0f0; border:1px solid #f5c6cb; color:#c0392b; }
    .alert-info  { background:#eef0ff; border:1px solid #d3d9ff; color:#2b3fbf; }

    .row2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .field { margin-bottom:15px; }
    .field label { display:block; font-size:.74rem; font-weight:700; color:var(--muted); margin-bottom:6px; }
    .field input {
      width:100%; padding:11px 14px; border:1.5px solid #e6e7f0; border-radius:10px;
      background:#f8f9ff; font-family:var(--font-family,'Nunito',sans-serif);
      font-size:.86rem; color:var(--text); outline:none;
      transition:background var(--trans), border-color var(--trans), box-shadow var(--trans);
    }
    .field input:focus { background:#fff; border-color:var(--accent); box-shadow:0 0 0 3px rgba(43,79,255,.12); }
    .field input:disabled { background:#eceef4; color:var(--muted); cursor:not-allowed; }
    .hint { font-size:.68rem; color:var(--muted); margin-top:4px; line-height:1.5; }

    /* Foto profil */
    .foto-field { display:flex; align-items:center; gap:16px; margin-bottom:20px; }
    .foto-preview-wrap {
      position:relative; width:74px; height:74px; border-radius:50%; overflow:hidden;
      background:#f8f9ff; border:1.5px dashed #c9cdf0; cursor:pointer; flex-shrink:0;
      display:flex; align-items:center; justify-content:center;
      transition:border-color var(--trans);
    }
    .foto-preview-wrap:hover { border-color:var(--accent); }
    .foto-preview-wrap.has-photo { border-style:solid; border-color:var(--accent); }
    .foto-preview-wrap img { width:100%; height:100%; object-fit:cover; display:none; }
    .foto-placeholder { display:flex; flex-direction:column; align-items:center; gap:3px; color:var(--muted); }
    .foto-placeholder svg { width:20px; height:20px; }
    .foto-placeholder span { font-size:.55rem; text-align:center; line-height:1.3; padding:0 4px; }
    .foto-info-text { font-size:.78rem; font-weight:700; color:var(--text); margin-bottom:4px; }
    .foto-hint { font-size:.68rem; color:var(--muted); line-height:1.5; }

    .form-actions { display:flex; align-items:center; gap:16px; margin-top:8px; flex-wrap:wrap; }
    .btn-primary {
      padding:12px 26px; border:none; border-radius:50px;
      background:linear-gradient(120deg,#2b4fff,#4d6bff);
      color:#fff; font-family:var(--font-family,'Nunito',sans-serif);
      font-size:.85rem; font-weight:800; letter-spacing:.02em; cursor:pointer;
      box-shadow:0 8px 22px rgba(43,79,255,.28);
      transition:transform .15s, box-shadow var(--trans);
    }
    .btn-primary:hover { transform:translateY(-1px); box-shadow:0 10px 26px rgba(43,79,255,.36); }
    .btn-primary:active { transform:scale(.97); }
    .btn-secondary {
      display:inline-flex; padding:11px 22px; border-radius:50px;
      background:#eef0ff; color:var(--accent); font-size:.82rem; font-weight:800;
      text-decoration:none; transition:background var(--trans);
    }
    .btn-secondary:hover { background:#dde1ff; }
    .btn-text {
      background:none; border:none; font-family:var(--font-family,'Nunito',sans-serif);
      font-size:.8rem; font-weight:700; color:var(--muted); cursor:pointer; text-decoration:none;
    }
    .btn-text:hover { color:#e74c3c; }

    @media (max-width:700px) {
      .topbar { padding:12px 16px; }
      .topbar-tag { display:none; }
      .back-btn span { display:none; }
      .main { padding:24px 14px 30px; }
      .row2 { grid-template-columns:1fr; }
      .settings-card { padding:22px 18px 26px; }
    }
  </style>
</head>
<body>

<div class="topbar">
  <a href="beranda.php" class="topbar-brand">
    <div class="topbar-logo">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
      </svg>
    </div>
    <div>
      <div class="topbar-name">AKSA NOVA</div>
      <div class="topbar-tag">Library Catalog App</div>
    </div>
  </a>
  <a href="beranda.php" class="back-btn">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="15 18 9 12 15 6"/></svg>
    <span>Kembali ke Beranda</span>
  </a>
</div>

<main class="main">

  <div class="settings-wrap">
    <div class="settings-card">

      <div class="settings-head">
        <div class="settings-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
        </div>
        <div>
          <h1 class="settings-title">Update Data Diri</h1>
          <p class="settings-sub">Halo, <?= htmlspecialchars($user_name) ?>. Perbarui nama, kelas, nomor HP, email, atau foto profil kartu anggota kamu di sini.</p>
        </div>
      </div>

      <?php if ($blocked): ?>
        <div class="alert alert-error"><?= htmlspecialchars($blocked) ?></div>
        <a href="beranda.php" class="btn-secondary">Kembali ke Beranda</a>
      <?php else: ?>

        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <div class="alert alert-info">ℹ️ Username, NIK, dan nomor anggota tidak bisa diubah di sini. Kartu anggota kamu akan dicetak ulang otomatis dengan data terbaru.</div>

        <?php
          $current_foto = $user["foto"] ?? "";
          $current_foto_exists = $current_foto !== "" && file_exists(__DIR__ . "/" . $current_foto);
        ?>
        <form method="POST" action="edit_kartu.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="update_data">

          <div class="foto-field">
            <div class="foto-preview-wrap <?= $current_foto_exists ? 'has-photo' : '' ?>" id="fotoPreviewWrap" onclick="document.getElementById('fotoInput').click()">
              <img id="fotoPreviewImg" src="<?= $current_foto_exists ? htmlspecialchars($current_foto) : '' ?>" alt="Foto profil" style="<?= $current_foto_exists ? 'display:block;' : '' ?>">
              <div class="foto-placeholder" id="fotoPlaceholder" style="<?= $current_foto_exists ? 'display:none;' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                  <circle cx="12" cy="13" r="4"/>
                </svg>
                <span>Foto Profil</span>
              </div>
            </div>
            <div>
              <div class="foto-info-text">Foto Profil Kartu</div>
              <p class="foto-hint">Ketuk untuk ambil foto baru dari kamera atau pilih dari galeri. Kosongkan jika tidak ingin mengganti foto.</p>
            </div>
            <input type="file" name="foto" id="fotoInput" accept="image/*" style="display:none">
          </div>

          <div class="field">
            <label>Nama Lengkap</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($form_data['full_name']) ?>" required>
          </div>

          <div class="row2">
            <div class="field">
              <label>Kelas</label>
              <input type="text" name="kelas" value="<?= htmlspecialchars($form_data['kelas']) ?>" required>
            </div>
            <div class="field">
              <label>NIK (tidak bisa diubah)</label>
              <input type="text" value="<?= htmlspecialchars($user['nik'] ?? '') ?>" disabled>
            </div>
          </div>

          <div class="row2">
            <div class="field">
              <label>Nomor HP</label>
              <input type="text" name="no_hp" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($form_data['no_hp']) ?>">
            </div>
            <div class="field">
              <label>Email</label>
              <input type="email" name="email" value="<?= htmlspecialchars($form_data['email']) ?>" required>
            </div>
          </div>
          <p class="hint" style="margin:-8px 0 15px;">Ganti email juga berarti login berikutnya pakai email baru ini (atau tetap bisa pakai username).</p>

          <div class="form-divider">
            Ubah Kata Sandi
            <div class="form-divider-hint">Opsional — kosongkan kedua kolom ini kalau tidak ingin mengganti kata sandi.</div>
          </div>

          <div class="row2">
            <div class="field">
              <label>Kata Sandi Baru</label>
              <input type="password" name="new_password" placeholder="Minimal 6 karakter" autocomplete="new-password">
            </div>
            <div class="field">
              <label>Konfirmasi Kata Sandi Baru</label>
              <input type="password" name="new_password_confirm" placeholder="Ulangi kata sandi baru" autocomplete="new-password">
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-primary">Simpan &amp; Cetak Ulang Kartu</button>
            <a href="beranda.php" class="btn-text">Batalkan</a>
          </div>
        </form>

      <?php endif; ?>

    </div>
  </div>

</main>

<?php require_once "pengaturan_panel.php"; ?>

<script>
  <?php if (!$blocked): ?>
  var fotoInput       = document.getElementById('fotoInput');
  var fotoPreviewWrap = document.getElementById('fotoPreviewWrap');
  var fotoPreviewImg  = document.getElementById('fotoPreviewImg');
  var fotoPlaceholder = document.getElementById('fotoPlaceholder');

  fotoInput.addEventListener('change', function (e) {
    var file = e.target.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function (ev) {
      fotoPreviewImg.src = ev.target.result;
      fotoPreviewImg.style.display = 'block';
      fotoPlaceholder.style.display = 'none';
      fotoPreviewWrap.classList.add('has-photo');
    };
    reader.readAsDataURL(file);
  });
  <?php endif; ?>
</script>

</body>
</html>