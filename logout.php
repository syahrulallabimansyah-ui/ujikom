<?php
// logout.php — Konfirmasi & penghapusan sesi pengguna AKSA NOVA
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika belum login sama sekali, langsung arahkan ke halaman sign in
if (!isset($_SESSION['user_id'])) {
    header('Location: sign_in.php');
    exit;
}

$is_confirmed = (
    ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm_logout'] ?? '') === '1') ||
    (($_GET['confirm'] ?? '') === '1')
);

if ($is_confirmed) {
    // Bersihkan seluruh variabel sesi & cookie sesi secara tuntas
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
    header('Location: sign_in.php');
    exit;
}

// Fallback pengaman server-side: jika logout.php diakses tanpa klik tombol "Ya, Keluar",
// JANGAN hapus sesi terlebih dahulu — tampilkan dialog konfirmasi terlebih dahulu.
$default_back = (($_SESSION['role'] ?? '') === 'admin') ? 'dashboard.php' : 'beranda.php';
$back_url = $default_back;
if (!empty($_SERVER['HTTP_REFERER'])) {
    $ref_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    $cur_host = $_SERVER['HTTP_HOST'] ?? '';
    $ref_path = basename((string)parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH));
    if ((!$ref_host || $ref_host === $cur_host) && $ref_path !== '' && $ref_path !== 'logout.php') {
        $back_url = $_SERVER['HTTP_REFERER'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Konfirmasi Logout – AKSA NOVA</title>
  <?php require_once __DIR__ . '/settings_include.php'; ?>
  <style>
    body {
      min-height: 100vh;
      margin: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      background: var(--bg, #090c10);
      color: var(--text, #eef3f4);
      font-family: var(--font-family, 'Outfit', sans-serif);
    }
    .logout-confirm-box {
      width: 100%;
      max-width: 400px;
      background: var(--card, #121820);
      border: 1px solid var(--border-color, rgba(216,184,120,.2));
      border-radius: 18px;
      padding: 28px 24px 24px;
      text-align: center;
      box-shadow: 0 20px 50px rgba(0,0,0,.45);
    }
    .logout-icon {
      width: 56px;
      height: 56px;
      margin: 0 auto 16px;
      border-radius: 50%;
      background: rgba(239, 68, 68, 0.14);
      border: 1px solid rgba(239, 68, 68, 0.32);
      color: #ef4444;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .logout-icon svg { width: 26px; height: 26px; }
    .logout-title {
      font-size: 1.15rem;
      font-weight: 700;
      margin: 0 0 8px;
      color: var(--text, #eef3f4);
    }
    .logout-desc {
      font-size: 0.88rem;
      line-height: 1.55;
      color: var(--muted, rgba(238,243,244,.7));
      margin: 0 0 22px;
    }
    .logout-actions {
      display: flex;
      gap: 10px;
    }
    .btn-logout-cancel,
    .btn-logout-yes {
      flex: 1;
      padding: 11px 16px;
      border-radius: 10px;
      font-size: 0.86rem;
      font-weight: 700;
      font-family: inherit;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: transform .15s ease, opacity .15s ease;
    }
    .btn-logout-cancel {
      background: rgba(148, 163, 184, 0.14);
      border: 1px solid var(--border-color, rgba(148,163,184,.25));
      color: var(--text, #eef3f4);
    }
    .btn-logout-yes {
      background: linear-gradient(135deg, #ef4444, #dc2626);
      border: none;
      color: #ffffff;
      box-shadow: 0 6px 18px rgba(220, 38, 38, 0.32);
    }
    .btn-logout-cancel:hover,
    .btn-logout-yes:hover { opacity: 0.92; transform: translateY(-1px); }
  </style>
</head>
<body>
  <div class="logout-confirm-box" role="dialog" aria-modal="true" aria-labelledby="logoutTitle">
    <div class="logout-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
    </div>
    <h1 class="logout-title" id="logoutTitle">Konfirmasi Logout</h1>
    <p class="logout-desc">Apakah Anda yakin ingin keluar (logout) dari sistem AKSA NOVA?</p>
    <form method="POST" action="logout.php" class="logout-actions">
      <?= csrf_field() ?>
      <input type="hidden" name="confirm_logout" value="1"/>
      <a href="<?= htmlspecialchars($back_url, ENT_QUOTES, 'UTF-8') ?>" class="btn-logout-cancel">Tidak, Batal</a>
      <button type="submit" class="btn-logout-yes">Ya, Keluar</button>
    </form>
  </div>
</body>
</html>
