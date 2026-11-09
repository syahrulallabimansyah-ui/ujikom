<?php
// kartu_anggota.php — Menampilkan kartu anggota hasil pendaftaran (sekali tampil)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handler masuk langsung ke akun dan beranda jika aksi masuk dipanggil
if (isset($_GET['action']) && $_GET['action'] === 'masuk') {
    require_once 'db.php';
    if (isset($_SESSION['kartu_data']['id'])) {
        $kid = (int)$_SESSION['kartu_data']['id'];
        $stmt = mysqli_prepare($conn, 'SELECT id, full_name, username, role, status, card_status FROM users WHERE id = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $kid);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($res && ($user = mysqli_fetch_assoc($res))) {
                if (($user['status'] ?? 'approved') === 'approved' && ($user['card_status'] ?? 'active') !== 'frozen') {
                    session_regenerate_id(true);
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['role']      = $user['role'] ?? 'member';
                }
            }
            mysqli_stmt_close($stmt);
        }
        unset($_SESSION['kartu_data']);
    }
    header('Location: beranda.php');
    exit;
}

$admin_view = false;

require_once "config.php";
if (isset($_GET["id"]) && isset($_SESSION["user_id"]) && ($_SESSION["role"] ?? "") === "admin") {
    // ── Mode admin: lihat kartu milik anggota lain, tanpa menyentuh sesi kartu milik anggota ──
    require_once "db.php";
    $vid = unmask_id($_GET["id"] ?? "");
    if ($vid <= 0) { header("Location: daftar_anggota.php"); exit; }
    $rq = mysqli_query($conn, "SELECT full_name, nik, kelas, no_hp, email, no_anggota, username, foto, status, plain_password, created_at FROM users WHERE id = $vid AND role = 'member'");
    if ($rq && ($u = mysqli_fetch_assoc($rq))) {
        $admin_view = true;
        $member_pw  = "";
        $d = [
            "full_name"         => $u["full_name"],
            "nik"               => $u["nik"],
            "kelas"             => $u["kelas"],
            "no_hp"             => $u["no_hp"],
            "email"             => $u["email"],
            "no_anggota"        => $u["no_anggota"],
            "username"          => $u["username"],
            "foto"              => $u["foto"] ?? "",
            "status"            => $u["status"] ?? "pending",
            "reissued"          => false,
            "data_updated_only" => false,
            "created_at"        => $u["created_at"] ?? null,
        ];
    } else {
        header("Location: daftar_anggota.php");
        exit;
    }
} elseif (!isset($_SESSION["kartu_data"])) {
    if (isset($_SESSION["user_id"])) {
        require_once "db.php";
        $uid = (int)$_SESSION["user_id"];
        $rq = mysqli_query($conn, "SELECT full_name, nik, kelas, no_hp, email, no_anggota, username, foto, status, plain_password, created_at FROM users WHERE id = $uid");
        if ($rq && ($u = mysqli_fetch_assoc($rq))) {
            $_SESSION["kartu_data"] = [
                "full_name"         => $u["full_name"],
                "nik"               => $u["nik"],
                "kelas"             => $u["kelas"],
                "no_hp"             => $u["no_hp"],
                "email"             => $u["email"],
                "no_anggota"        => $u["no_anggota"],
                "username"          => $u["username"],
                "password"          => "",
                "foto"              => $u["foto"] ?? "",
                "status"            => $u["status"] ?? "approved",
                "reissued"          => false,
                "data_updated_only" => true,
                "created_at"        => $u["created_at"] ?? null,
            ];
        } else {
            header("Location: sign_up.php");
            exit;
        }
    } else {
        header("Location: sign_up.php");
        exit;
    }
}

if (!$admin_view) {
    $d = $_SESSION["kartu_data"];
}
$d["status"]   = $d["status"]   ?? "pending";
$d["reissued"] = $d["reissued"] ?? false;
$d["data_updated_only"] = $d["data_updated_only"] ?? false;
$d["password_changed"]  = $d["password_changed"]  ?? false;
$d["password"]          = $d["password"]          ?? "";
$d["foto"] = $d["foto"] ?? "";
$foto_exists = $d["foto"] !== "" && file_exists(__DIR__ . "/" . $d["foto"]);
$page_title = "Kartu Anggota – AKSA NOVA";

// Barcode Code 128 Standar Perpustakaan Resmi (Murni SVG Offline)
require_once "barcode128.php";

// Hitung Masa Berlaku Kartu Resmi Operasional Perpustakaan
$tahun_reg = (int)date('Y');
if (!empty($d["created_at"])) {
    $tahun_reg = (int)date('Y', strtotime($d["created_at"]));
} elseif (!empty($d["no_anggota"]) && preg_match('/AN-(\d{4})-/', $d["no_anggota"], $m_th)) {
    $tahun_reg = (int)$m_th[1];
}
$is_pendidik = strcasecmp($d["kelas"] ?? "", "Guru") === 0;
$masa_berlaku = $is_pendidik ? "Selama Bertugas" : "s/d " . ($tahun_reg + 3);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= htmlspecialchars($page_title) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
  (function() {
    try {
      var t = localStorage.getItem('aksanova_theme');
      if (!t) {
        var s = JSON.parse(localStorage.getItem('aksanova_settings') || '{}');
        t = s.mode;
      }
      if (t === 'light') {
        document.documentElement.classList.add('theme-light', 'light');
        document.documentElement.classList.remove('dark');
      } else {
        document.documentElement.classList.add('dark');
        document.documentElement.classList.remove('theme-light', 'light');
      }
    } catch(e) {}
  })();
</script>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --ink: #0f0f14;
    --dim: #6b6b80;
    --ghost: #a8a8b8;

    /* Warna teks/tombol di LUAR kartu — menyesuaikan tema.
       Default (tema gelap): terang di atas background gelap. */
    --text-strong: #f4ecd8;
    --text-soft: #b9b9cc;
    --outline-color: #eef3f4;
    --outline-border: rgba(238,243,244,.35);
    --outline-hover-bg: rgba(238,243,244,.08);
  }

  /* Tema terang: teks/tombol jadi gelap di atas background terang */
  html.theme-light, html.light {
    --text-strong: #8a6323;
    --text-soft: #6b645b;
    --outline-color: #1a1714;
    --outline-border: rgba(15,15,20,.35);
    --outline-hover-bg: rgba(15,15,20,.08);
  }

  html, body {
    min-height: 100vh;
    font-family: 'Outfit', sans-serif;
    background: linear-gradient(135deg, #090c10 0%, #121820 50%, #090c10 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 16px 60px;
    color: #eef3f4;
  }

  /* ══════════════════ TOMBOL MODE GELAP / TERANG ══════════════════ */
  .btn-mode {
    position: fixed;
    top: 18px;
    right: 18px;
    z-index: 999;
    appearance: none;
    cursor: pointer;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    border: 1px solid rgba(216,184,120,.3);
    background: rgba(16,21,27,.75);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d8b878;
    transition: background .2s ease, border-color .2s ease, transform .18s ease, box-shadow .2s ease;
    box-shadow: 0 6px 20px rgba(0,0,0,.4);
  }
  .btn-mode:hover {
    transform: translateY(-2px);
    border-color: #d8b878;
    box-shadow: 0 8px 24px rgba(216,184,120,.3);
  }
  .btn-mode svg { width: 19px; height: 19px; transition: transform .4s cubic-bezier(.22,1,.36,1); }
  .btn-mode .icon-sun { display: none; }

  /* ══════════════════ LIGHT THEME OVERRIDES ══════════════════ */
  html.theme-light, html.theme-light body,
  html.light, html.light body {
    background: linear-gradient(135deg, #f6f2e9 0%, #ece4d4 50%, #f7f3ec 100%);
    color: #1a1714;
  }
  html.theme-light .btn-mode,
  html.light .btn-mode {
    background: rgba(255,255,255,.85);
    border-color: rgba(154,115,40,.3);
    color: #9a7328;
    box-shadow: 0 6px 20px rgba(60,45,20,.12);
  }
  html.theme-light .btn-mode:hover,
  html.light .btn-mode:hover {
    border-color: #9a7328;
    box-shadow: 0 8px 24px rgba(154,115,40,.22);
  }
  html.theme-light .btn-mode .icon-moon,
  html.light .btn-mode .icon-moon { display: none; }
  html.theme-light .btn-mode .icon-sun,
  html.light .btn-mode .icon-sun  { display: block; }

  .page-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-strong);
    margin-bottom: 4px;
    text-align: center;
  }
  .page-sub {
    font-size: .82rem;
    color: var(--text-soft);
    text-align: center;
    max-width: 440px;
    margin-bottom: 30px;
    line-height: 1.6;
  }

  .warning {
    background: #fff8e1;
    border: 1px solid #ffe082;
    color: #8a6100;
    font-size: .78rem;
    padding: 10px 16px;
    border-radius: 10px;
    max-width: 440px;
    text-align: center;
    margin-bottom: 26px;
    line-height: 1.6;
  }

  /* ── KARTU ANGGOTA (Format Standar Internasional ISO/IEC 7810 ID-1 / CR80: 85.60 x 53.98 mm) ── */
  #kartu {
    width: 428px;
    min-height: 270px;
    max-width: 94vw;
    border-radius: 16px;
    overflow: hidden;
    background: linear-gradient(150deg, #1c1c28 0%, #2e2e40 55%, #46465c 100%);
    box-shadow: 0 20px 50px rgba(0,0,0,.38);
    position: relative;
    color: #fff;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-sizing: border-box;
    animation: cardSmoothIn 0.55s cubic-bezier(0.16, 1, 0.3, 1) both;
    will-change: transform, opacity;
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }
  #kartu:hover {
    transform: translateY(-4px);
    box-shadow: 0 30px 70px rgba(0,0,0,.45);
  }
  @keyframes cardSmoothIn {
    from { opacity: 0; transform: translateY(16px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
  }

  #kartu::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
      radial-gradient(circle at 85% 10%, rgba(255,255,255,.10), transparent 45%),
      radial-gradient(circle at 5% 95%, rgba(255,255,255,.06), transparent 40%);
    pointer-events: none;
  }

  .kartu-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px 10px;
    position: relative;
  }

  .brand {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .brand-mark {
    width: 30px; height: 30px;
    border-radius: 8px;
    background: linear-gradient(135deg, #fff, #a8a8c0);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Cormorant Garamond', serif;
    font-weight: 700;
    color: #1c1c28;
    font-size: 1rem;
  }
  .brand-text {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 700;
    font-size: 1.05rem;
    letter-spacing: .04em;
  }
  .brand-tag {
    font-size: .6rem;
    letter-spacing: .18em;
    color: #b8b8cc;
    text-transform: uppercase;
    margin-top: 1px;
  }

  .kartu-label {
    font-size: .62rem;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: #c8c8dc;
    text-align: right;
  }

  .kartu-body {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 2px 20px 8px;
    position: relative;
  }

  .avatar {
    width: 52px; height: 52px;
    border-radius: 12px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
  }
  .avatar svg { width: 26px; height: 26px; color: #d8d8e8; }
  .avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }

  .kartu-name { font-size: 1.05rem; font-weight: 600; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 320px; }
  .kartu-meta { font-size: .72rem; color: #e0ac78; margin-top: 2px; line-height: 1.4; font-weight: 500; }
  .kartu-meta b { color: #e0ac78; font-weight: 600; }

  .kartu-divider {
    margin: 0 20px;
    height: 1px;
    background: rgba(255,255,255,.14);
    position: relative;
  }

  .kartu-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 20px 14px;
    position: relative;
    gap: 12px;
  }

  .cred-block {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 9px;
    min-width: 0;
  }
  .cred-row {
    display: flex;
    align-items: baseline;
    gap: 8px;
  }
  .cred-label {
    font-size: .62rem;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #adadc7;
    width: 62px;
    flex-shrink: 0;
  }
  .cred-value {
    font-family: 'JetBrains Mono', monospace;
    font-size: .84rem;
    font-weight: 600;
    color: #fff;
    letter-spacing: .02em;
    word-break: break-all;
  }

  .barcode-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    text-align: center;
  }

  .barcode-box {
    background: #ffffff;
    padding: 5px 8px 3px;
    border-radius: 7px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 174px;
    box-sizing: border-box;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
  }

  .barcode-box svg {
    width: 100%;
    height: 32px;
    display: block;
  }

  .barcode-label {
    font-family: 'JetBrains Mono', monospace;
    font-size: .62rem;
    font-weight: 700;
    color: #111111;
    text-align: center;
    margin-top: 2px;
    letter-spacing: .08em;
    white-space: nowrap;
  }

  /* ── Aksi ── */
  .actions {
    display: flex;
    gap: 12px;
    margin-top: 30px;
    flex-wrap: wrap;
    justify-content: center;
  }

  .btn {
    padding: 13px 28px;
    border-radius: 50px;
    font-family: 'Outfit', sans-serif;
    font-size: .84rem;
    font-weight: 600;
    letter-spacing: .02em;
    cursor: pointer;
    border: none;
    transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s cubic-bezier(0.16, 1, 0.3, 1), background 0.2s ease, border-color 0.2s ease;
  }
  .btn:active { transform: scale(0.975); }
  .btn-download {
    background: var(--ink);
    color: #fff;
  }
  .btn-download:hover { background: #1a1a2a; transform: translateY(-2px); box-shadow: 0 10px 28px rgba(0,0,0,.35); }
  .btn-download:active { transform: scale(.975); }

  .btn-continue {
    background: transparent;
    color: var(--outline-color);
    border: 1.5px solid var(--outline-border);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
  }
  .btn-continue:hover { background: var(--outline-hover-bg); }

  .btn-back {
    background: transparent;
    color: var(--text-soft);
    border: none;
    text-decoration: underline;
    text-underline-offset: 3px;
    display: inline-flex;
    align-items: center;
    padding: 13px 10px;
  }
  .btn-back:hover { color: var(--outline-color); }

  @media (max-width: 460px) {
    #kartu {
      width: 100%;
      max-width: 360px;
      min-height: 240px;
      height: auto;
    }
    .kartu-head {
      padding: 12px 14px 6px;
    }
    .brand-mark {
      width: 24px; height: 24px; font-size: .85rem; border-radius: 6px;
    }
    .brand-text { font-size: .92rem; }
    .brand-tag { font-size: .52rem; }
    .kartu-label { font-size: .54rem; }
    .kartu-body {
      padding: 2px 14px 6px;
      gap: 10px;
    }
    .avatar {
      width: 44px;
      height: 44px;
      border-radius: 10px;
    }
    .kartu-name {
      font-size: .92rem;
      max-width: 240px;
    }
    .kartu-meta {
      font-size: .64rem;
      margin-top: 1px;
    }
    .kartu-divider {
      margin: 0 14px;
    }
    .kartu-foot {
      padding: 6px 14px 10px;
      gap: 8px;
      flex-direction: row;
      align-items: center;
      justify-content: space-between;
    }
    .cred-label {
      width: 48px;
      font-size: .52rem;
    }
    .cred-value {
      font-size: .72rem;
    }
    .barcode-box {
      width: 140px;
      padding: 4px 6px 2px;
    }
    .barcode-box svg {
      height: 26px;
    }
    .barcode-label {
      font-size: .50rem;
      margin-top: 1px;
    }
  }
</style>
</head>
<body>

<!-- Tombol Ganti Mode Gelap / Terang -->
<button type="button" class="btn-mode" id="btnMode" aria-label="Ganti mode gelap/terang" title="Mode Gelap / Terang" aria-pressed="false">
  <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/>
  </svg>
  <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="4.2"/>
    <path d="M12 2.5v2.4M12 19.1v2.4M4.9 4.9l1.7 1.7M17.4 17.4l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.9 19.1l1.7-1.7M17.4 6.6l1.7-1.7"/>
  </svg>
</button>

  <?php if ($admin_view): ?>
    <h1 class="page-title">Kartu Anggota: <?= htmlspecialchars($d["full_name"]) ?></h1>
    <p class="page-sub">Tampilan kartu anggota resmi perpustakaan sekolah standar ISO/IEC 7810 format ID-1 (CR80).</p>
  <?php elseif ($d["reissued"]): ?>
    <h1 class="page-title">Kartu Anggota Baru Kamu Sudah Jadi</h1>
    <p class="page-sub">Kartu lama kamu sudah tidak berlaku lagi. Unduh kartu baru ini dan simpan baik-baik sebagai tanda pengenal resmi perpustakaan.</p>
  <?php elseif ($d["data_updated_only"]): ?>
    <h1 class="page-title">Kartu Anggota Kamu Sudah Diperbarui</h1>
    <p class="page-sub">Data pada kartu sudah diperbarui. Unduh &amp; cetak ulang kartu ini supaya data yang tercetak selalu yang terbaru.</p>
  <?php else: ?>
    <h1 class="page-title">Kartu Anggota Kamu Sudah Jadi</h1>
    <p class="page-sub">Unduh kartu ini dan simpan baik-baik. Tunjukkan kartu ini atau pindai kode QR saat meminjam buku fisik di perpustakaan.</p>
  <?php endif; ?>

  <?php if ($admin_view): ?>
    <?php if ($d["status"] === "pending"): ?>
      <div style="display:inline-flex;align-items:center;gap:7px;padding:6px 14px;border-radius:20px;background:rgba(216,184,120,.12);border:1px solid rgba(216,184,120,.28);color:var(--text-strong);font-size:.74rem;font-weight:600;margin-bottom:20px;letter-spacing:.02em;">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
    Standar Resmi ID-1 (CR80) &bull; Barcode Code 128 &bull; 85,60 &times; 53,98 mm
  </div>

  <div class="warning" style="background:#eef2ff;border-color:#c7d2fe;color:#3730a3;">
      Status anggota ini masih <b>menunggu persetujuan</b>.
    </div>
    <?php elseif ($d["status"] === "rejected"): ?>
    <div class="warning" style="background:#fce4ec;border-color:#f5b8c8;color:#c0392b;">
      Akun anggota ini berstatus <b>nonaktif</b>.
    </div>
    <?php endif; ?>
  <?php elseif ($d["data_updated_only"] && $d["password_changed"]): ?>
  <div class="warning" style="background:#eefaf0;border-color:#bfe8cc;color:#1a6b3a;">
    Data &amp; kata sandi berhasil diperbarui. Kata sandi akun kamu telah tersimpan dengan aman.
  </div>
  <?php elseif ($d["data_updated_only"]): ?>
  <div class="warning" style="background:#eefaf0;border-color:#bfe8cc;color:#1a6b3a;">
    Data berhasil diperbarui. Kata sandi kamu <b>tidak berubah</b>.
  </div>
  <?php else: ?>
  <div class="warning">
    Simpan kartu anggota ini baik-baik. Pastikan kamu selalu mengingat username dan kata sandi akunmu.
  </div>
  <?php endif; ?>

  <?php if (!$admin_view): ?>
    <?php if ($d["status"] === "pending"): ?>
    <div class="warning" style="background:#eef2ff;border-color:#c7d2fe;color:#3730a3;">
      Akun kamu berstatus <b>menunggu persetujuan admin</b>. Kamu belum bisa login sampai admin perpustakaan menyetujui pendaftaran ini. Simpan kartu ini dulu, coba login setelah disetujui.
    </div>
    <?php elseif ($d["reissued"]): ?>
    <div class="warning" style="background:#eefaf0;border-color:#bfe8cc;color:#1a6b3a;">
      Kartu &amp; password baru kamu sudah aktif. Gunakan kartu ini untuk login mulai sekarang, kartu lama sudah dibekukan permanen.
    </div>
    <?php endif; ?>
  <?php endif; ?>

  <div id="kartu">
    <div class="kartu-head">
      <div class="brand">
        <div class="brand-mark">A</div>
        <div>
          <div class="brand-text">AKSA NOVA</div>
          <div class="brand-tag"><?= strcasecmp($d["kelas"] ?? "", "Guru") === 0 ? "Kartu Pendidik" : "Kartu Anggota" ?></div>
        </div>
      </div>
      <div class="kartu-label"><?= strcasecmp($d["kelas"] ?? "", "Guru") === 0 ? "Teacher<br>Card" : "Member<br>Card" ?></div>
    </div>

    <div class="kartu-body">
      <div class="avatar">
        <?php if ($foto_exists): ?>
          <img src="<?= htmlspecialchars($d["foto"]) ?>" alt="Foto profil <?= htmlspecialchars($d["full_name"]) ?>" crossorigin="anonymous">
        <?php else: ?>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <circle cx="12" cy="8" r="4"/>
            <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
          </svg>
        <?php endif; ?>
      </div>
      <div>
        <div class="kartu-name"><?= htmlspecialchars($d["full_name"]) ?></div>
        <div class="kartu-meta">
          <?php if (strcasecmp($d["kelas"] ?? "", "Guru") === 0): ?>
            Status&nbsp;&nbsp;<b>Tenaga Pendidik / Guru</b>
            <?php if (!empty($d["nik"])): ?>
              &nbsp;•&nbsp;NIP: <b><?= htmlspecialchars($d["nik"]) ?></b>
            <?php endif; ?>
          <?php else: ?>
            <?php if (!empty($d["nik"])): ?>
              NIS: <b><?= htmlspecialchars($d["nik"]) ?></b>
            <?php else: ?>
              Anggota Resmi Perpustakaan
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="kartu-divider"></div>

    <div class="kartu-foot">
      <div class="cred-block">
        <div class="cred-row">
          <span class="cred-label">Username</span>
          <span class="cred-value"><?= htmlspecialchars($d["username"]) ?></span>
        </div>
        <div class="cred-row">
          <span class="cred-label">Berlaku</span>
          <span class="cred-value" style="color:#d8b878;font-weight:600;"><?= htmlspecialchars($masa_berlaku) ?></span>
        </div>
      </div>
      <div class="barcode-wrap">
        <div class="barcode-box">
          <?= render_code128_svg($d["no_anggota"], 32) ?>
          <div class="barcode-label"><?= htmlspecialchars($d["no_anggota"]) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="actions">
    <button class="btn btn-download" id="btnDownload" style="display:inline-flex;align-items:center;gap:8px;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Unduh Kartu (PNG)</button>
    <?php if ($admin_view): ?>
    <a href="daftar_anggota.php" class="btn btn-continue" id="btnContinue">Selesai, Kembali ke Daftar Anggota &rarr;</a>
    <?php elseif (isset($_SESSION["user_id"])): ?>
    <a href="beranda.php" class="btn btn-continue" id="btnContinue">Selesai, Kembali ke Beranda &rarr;</a>
    <?php elseif (!empty($d["data_updated_only"])): ?>
    <a href="beranda.php" class="btn btn-continue" id="btnContinue">Selesai, Kembali ke Beranda &rarr;</a>
    <?php else: ?>
    <a href="kartu_masuk.php" class="btn btn-continue" id="btnContinue">Selesai, Masuk ke Akun &rarr;</a>
    <?php endif; ?>

    <?php if (!$admin_view && !isset($_SESSION["user_id"])): ?>
    <a href="sign_in.php" class="btn btn-back">&larr; Kembali ke Halaman Login</a>
    <?php endif; ?>
  </div>

<script>
  document.getElementById('btnDownload').addEventListener('click', function () {
    const kartu = document.getElementById('kartu');
    const origWidth = kartu.style.width;
    const origHeight = kartu.style.height;
    const origMaxW = kartu.style.maxWidth;
    // Kunci ukuran standar resmi ISO/IEC 7810 ID-1 CR80 (428x270 px)
    // Dengan skala 2.362x menghasilkan tepat 1011 x 638 piksel (resolusi standar cetak 300 DPI)
    kartu.style.width = '428px';
    kartu.style.minHeight = '270px';
    kartu.style.height = 'auto';
    kartu.style.maxWidth = '428px';
    html2canvas(kartu, {
      backgroundColor: null,
      scale: 2.362,
      useCORS: true,
      windowWidth: 1200
    }).then(function (canvas) {
      kartu.style.width = origWidth;
      kartu.style.height = origHeight;
      kartu.style.maxWidth = origMaxW;
      const link = document.createElement('a');
      link.download = 'kartu-anggota-<?= htmlspecialchars(preg_replace('/[^a-z0-9]/i', '', strtolower($d["username"]))) ?>.png';
      link.href = canvas.toDataURL('image/png');
      link.click();
    }).catch(function (err) {
      kartu.style.width = origWidth;
      kartu.style.maxWidth = origMaxW;
      console.error('Download error:', err);
    });
  });

  // Setelah user menekan "Selesai", hapus data kartu dari session di server
  document.getElementById('btnContinue').addEventListener('click', function () {
    navigator.sendBeacon('kartu_clear.php');
  });



  // Mode gelap / terang
  (function () {
    var btn  = document.getElementById('btnMode');
    if (!btn) return;
    var root = document.documentElement;
    var STORAGE_KEY = 'aksanova_theme';

    function updateThemeUI() {
      var isLight = root.classList.contains('theme-light') || root.classList.contains('light');
      btn.setAttribute('aria-pressed', isLight ? 'true' : 'false');
      var iconMoon = btn.querySelector('.icon-moon');
      var iconSun  = btn.querySelector('.icon-sun');
      if (iconMoon) iconMoon.style.display = isLight ? 'none' : 'block';
      if (iconSun)  iconSun.style.display  = isLight ? 'block' : 'none';
      if (isLight) {
        root.style.backgroundColor = '#f6f2e9';
      } else {
        root.style.backgroundColor = '#090c10';
      }
    }
    updateThemeUI();

    btn.addEventListener('click', function () {
      var isCurrentlyLight = root.classList.contains('theme-light') || root.classList.contains('light');
      var newMode = isCurrentlyLight ? 'dark' : 'light';

      if (newMode === 'light') {
        root.classList.remove('dark');
        root.classList.add('light', 'theme-light');
      } else {
        root.classList.remove('light', 'theme-light');
        root.classList.add('dark');
      }

      try {
        localStorage.setItem(STORAGE_KEY, newMode);
        var s = JSON.parse(localStorage.getItem('aksanova_settings') || '{}');
        s.mode = newMode;
        localStorage.setItem('aksanova_settings', JSON.stringify(s));
      } catch (e) {}

      updateThemeUI();
    });

    document.addEventListener('keydown', function (e) {
      var tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
      if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
      if ((e.altKey && (e.key === 'd' || e.key === 'D')) || ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'd' || e.key === 'D'))) {
        e.preventDefault();
        btn.click();
      }
    });
  })();
</script>

</body>
</html>