<?php
// kartu_anggota.php — Menampilkan kartu anggota hasil pendaftaran (sekali tampil)
session_start();

if (!isset($_SESSION["kartu_data"])) {
    header("Location: sign_up.php");
    exit;
}

$d = $_SESSION["kartu_data"];
$d["status"]   = $d["status"]   ?? "pending";
$d["reissued"] = $d["reissued"] ?? false;
$d["data_updated_only"] = $d["data_updated_only"] ?? false;
$d["password_changed"]  = $d["password_changed"]  ?? false;
$d["foto"] = $d["foto"] ?? "";
$foto_exists = $d["foto"] !== "" && file_exists(__DIR__ . "/" . $d["foto"]);
$page_title = "Kartu Anggota – AKSA NOVA";

// QR code berisi nomor anggota (di-generate via layanan publik, hanya dipanggil dari browser)
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=0&data=" . urlencode($d["no_anggota"]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= htmlspecialchars($page_title) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --ink: #0f0f14;
    --dim: #6b6b80;
    --ghost: #a8a8b8;
  }

  html, body {
    min-height: 100vh;
    font-family: 'Outfit', sans-serif;
    background: linear-gradient(135deg, #d4d4e0 0%, #c2c2cf 50%, #d8d8e4 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 16px 60px;
  }

  .page-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2rem;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 4px;
    text-align: center;
  }
  .page-sub {
    font-size: .82rem;
    color: var(--dim);
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

  /* ── KARTU ANGGOTA ── */
  #kartu {
    width: 420px;
    max-width: 92vw;
    border-radius: 20px;
    overflow: hidden;
    background: linear-gradient(150deg, #1c1c28 0%, #2e2e40 55%, #46465c 100%);
    box-shadow: 0 24px 60px rgba(0,0,0,.35);
    position: relative;
    color: #fff;
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
    padding: 22px 24px 14px;
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
    gap: 16px;
    padding: 6px 24px 20px;
    position: relative;
  }

  .avatar {
    width: 64px; height: 64px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
  }
  .avatar svg { width: 30px; height: 30px; color: #d8d8e8; }
  .avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }

  .kartu-name { font-size: 1.15rem; font-weight: 600; line-height: 1.25; }
  .kartu-meta { font-size: .74rem; color: #c4c4d8; margin-top: 4px; line-height: 1.7; }
  .kartu-meta b { color: #fff; font-weight: 500; }

  .kartu-divider {
    margin: 0 24px;
    height: 1px;
    background: rgba(255,255,255,.14);
    position: relative;
  }

  .kartu-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px 22px;
    position: relative;
    gap: 14px;
  }

  .cred-block { display: flex; flex-direction: column; gap: 8px; }
  .cred-row { display: flex; align-items: baseline; gap: 8px; }
  .cred-label {
    font-size: .6rem;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #9c9cb4;
    width: 62px;
    flex-shrink: 0;
  }
  .cred-value {
    font-family: 'JetBrains Mono', monospace;
    font-size: .82rem;
    font-weight: 500;
    color: #fff;
    letter-spacing: .02em;
  }

  .qr-box {
    background: #fff;
    padding: 6px;
    border-radius: 10px;
    flex-shrink: 0;
    line-height: 0;
  }
  .qr-box img { display: block; width: 68px; height: 68px; }

  .no-anggota {
    font-family: 'JetBrains Mono', monospace;
    font-size: .68rem;
    color: #d8d8e8;
    text-align: center;
    margin-top: 6px;
    letter-spacing: .04em;
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
    transition: transform .15s, box-shadow .25s, background .25s;
  }
  .btn-download {
    background: var(--ink);
    color: #fff;
  }
  .btn-download:hover { background: #1a1a2a; box-shadow: 0 8px 24px rgba(0,0,0,.28); }
  .btn-download:active { transform: scale(.97); }

  .btn-continue {
    background: transparent;
    color: var(--ink);
    border: 1.5px solid rgba(15,15,20,.35);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
  }
  .btn-continue:hover { background: rgba(15,15,20,.08); }

  @media (max-width: 460px) {
    .kartu-body { flex-direction: row; align-items: flex-start; }
    .kartu-foot { flex-direction: column; align-items: flex-start; }
    .qr-box { align-self: center; }
  }
</style>
</head>
<body>

  <?php if ($d["reissued"]): ?>
    <h1 class="page-title">Kartu Anggota Baru Kamu Sudah Jadi 🎉</h1>
    <p class="page-sub">Kartu lama kamu sudah tidak berlaku lagi. Unduh kartu baru ini dan simpan baik-baik. Username &amp; password di dalamnya dipakai untuk login dan meminjam buku.</p>
  <?php elseif ($d["data_updated_only"]): ?>
    <h1 class="page-title">Kartu Anggota Kamu Sudah Diperbarui ✅</h1>
    <p class="page-sub">Data pada kartu sudah diperbarui. Unduh &amp; cetak ulang kartu ini supaya data yang tercetak selalu yang terbaru.</p>
  <?php else: ?>
    <h1 class="page-title">Kartu Anggota Kamu Sudah Jadi 🎉</h1>
    <p class="page-sub">Unduh kartu ini dan simpan baik-baik. Username &amp; password di dalamnya dipakai untuk login dan meminjam buku.</p>
  <?php endif; ?>

  <?php if ($d["data_updated_only"] && $d["password_changed"]): ?>
  <div class="warning" style="background:#eefaf0;border-color:#bfe8cc;color:#1a6b3a;">
    ✅ Data &amp; kata sandi berhasil diperbarui. Kata sandi baru kamu ditampilkan <b>satu kali</b> di kartu ini — catat baik-baik sebelum meninggalkan halaman.
  </div>
  <?php elseif ($d["data_updated_only"]): ?>
  <div class="warning" style="background:#eefaf0;border-color:#bfe8cc;color:#1a6b3a;">
    ✅ Data berhasil diperbarui. Kata sandi kamu <b>tidak berubah</b>.
  </div>
  <?php else: ?>
  <div class="warning">
    ⚠️ Password hanya ditampilkan <b>satu kali</b> di halaman ini. Setelah kamu keluar dari halaman ini, password tidak bisa dilihat lagi (hanya admin yang bisa mereset).
  </div>
  <?php endif; ?>

  <?php if ($d["status"] === "pending"): ?>
  <div class="warning" style="background:#eef2ff;border-color:#c7d2fe;color:#3730a3;">
    ⏳ Akun kamu berstatus <b>menunggu persetujuan admin</b>. Kamu belum bisa login sampai admin perpustakaan menyetujui pendaftaran ini. Simpan kartu ini dulu, coba login setelah disetujui.
  </div>
  <?php elseif ($d["reissued"]): ?>
  <div class="warning" style="background:#eefaf0;border-color:#bfe8cc;color:#1a6b3a;">
    ✅ Kartu &amp; password baru kamu sudah aktif. Gunakan kartu ini untuk login mulai sekarang, kartu lama sudah dibekukan permanen.
  </div>
  <?php endif; ?>

  <div id="kartu">
    <div class="kartu-head">
      <div class="brand">
        <div class="brand-mark">A</div>
        <div>
          <div class="brand-text">AKSA NOVA</div>
          <div class="brand-tag">Kartu Anggota</div>
        </div>
      </div>
      <div class="kartu-label">Member<br>Card</div>
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
          Kelas&nbsp;&nbsp;<b><?= htmlspecialchars($d["kelas"]) ?></b>
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
        <?php if ($d["password"] !== ""): ?>
        <div class="cred-row">
          <span class="cred-label">Password</span>
          <span class="cred-value"><?= htmlspecialchars($d["password"]) ?></span>
        </div>
        <?php else: ?>
        <div class="cred-row">
          <span class="cred-label">Password</span>
          <span class="cred-value" style="color:#9c9cb4;font-size:.72rem;">Tidak berubah</span>
        </div>
        <?php endif; ?>
      </div>
      <div>
        <div class="qr-box">
          <img src="<?= htmlspecialchars($qr_url) ?>" alt="QR anggota" crossorigin="anonymous">
        </div>
        <div class="no-anggota"><?= htmlspecialchars($d["no_anggota"]) ?></div>
      </div>
    </div>
  </div>

  <div class="actions">
    <button class="btn btn-download" id="btnDownload">⬇ Unduh Kartu (PNG)</button>
    <?php if ($d["data_updated_only"]): ?>
    <a href="beranda.php" class="btn btn-continue" id="btnContinue">Selesai, Kembali ke Beranda &rarr;</a>
    <?php else: ?>
    <a href="sign_in.php" class="btn btn-continue" id="btnContinue">Selesai, Masuk ke Akun &rarr;</a>
    <?php endif; ?>
  </div>

<script>
  document.getElementById('btnDownload').addEventListener('click', function () {
    const kartu = document.getElementById('kartu');
    html2canvas(kartu, { backgroundColor: null, scale: 3, useCORS: true }).then(function (canvas) {
      const link = document.createElement('a');
      link.download = 'kartu-anggota-<?= htmlspecialchars(preg_replace('/[^a-z0-9]/i', '', strtolower($d["username"]))) ?>.png';
      link.href = canvas.toDataURL('image/png');
      link.click();
    });
  });

  // Setelah user menekan "Selesai", hapus data kartu dari session di server
  document.getElementById('btnContinue').addEventListener('click', function () {
    navigator.sendBeacon('kartu_clear.php');
  });
</script>

</body>
</html>