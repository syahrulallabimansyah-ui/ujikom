// scripts/capture_all_screens.js
// Automated high-definition screen captures via native Node.js CDP WebSocket
const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const SCREENS = [
  // Siswa / Member Flow
  { id: 'screen_01_beranda', url: 'http://localhost/aksanovaphp_revisi_hell_hihi/beranda.php', session: 'none', title: 'Beranda / Landing Page' },
  { id: 'screen_02_sign_in', url: 'http://localhost/aksanovaphp_revisi_hell_hihi/sign_in.php', session: 'none', title: 'Masuk / Sign In' },
  { id: 'screen_03_sign_up', url: 'http://localhost/aksanovaphp_revisi_hell_hihi/sign_up.php', session: 'none', title: 'Registrasi Anggota / Sign Up' },
  { id: 'screen_04_dashboard_user', url: 'http://localhost/aksanovaphp_revisi_hell_hihi/dashboard_user.php', session: 'member', title: 'Dashboard Siswa / Anggota' },
  { id: 'screen_05_daftar_buku', url: 'http://localhost/aksanovaphp_revisi_hell_hihi/daftar_buku.php', session: 'member', title: 'Katalog Buku & Pencarian' },
  { id: 'screen_06_pengajuan_peminjaman', url: 'http://localhost/aksanovaphp_revisi_hell_hihi/pengajuan_peminjaman.php', session: 'member', title: 'Form Pengajuan Pinjam (Maks 40 Buku)' },
  { id: 'screen_07_kartu_anggota', url: 'http://localhost/aksanovaphp_revisi_hell_hihi/kartu_anggota.php', session: 'member', title: 'Kartu Anggota Digital' },
  { id: 'screen_08_lupa_kartu', url: 'http://localhost/aksanovaphp_revisi_hell_hihi/lupa_kartu.php', session: 'none', title: 'Alur Pemulihan Lupa Kartu' },

  // Admin Flow
  { id: 'screen_09_dashboard_admin', url: 'http://localhost/aksanovaphp_revisi_hell_hihi/dashboard.php', session: 'admin', title: 'Dashboard Administrator' },
  { id: 'screen_10_halaman_admin', url: 'http://localhost/aksanovaphp_revisi_hell_hihi/halaman_admin.php', session: 'admin', title: 'Kelola Buku / Perbarui Buku' },
  { id: 'screen_11_daftar_anggota', url: 'http://localhost/aksanovaphp_revisi_hell_hihi/daftar_anggota.php', session: 'admin', title: 'Kelola Anggota Perpustakaan' },
  { id: 'screen_12_pengajuan_buku', url: 'http://localhost/aksanovaphp_revisi_hell_hihi/pengajuan_buku.php', session: 'admin', title: 'Verifikasi Pengajuan Buku' },
  { id: 'screen_13_telah_dipinjam', url: 'http://localhost/aksanovaphp_revisi_hell_hihi/telah_dipinjam.php', session: 'admin', title: 'Sirkulasi Pengembalian & Denda' },
  { id: 'screen_14_pinjam_buku', url: 'http://localhost/aksanovaphp_revisi_hell_hihi/pinjam_buku.php', session: 'admin', title: 'Peminjaman Langsung Meja Sirkulasi' },
  { id: 'screen_15_kelola_banner', url: 'http://localhost/aksanovaphp_revisi_hell_hihi/kelola_banner.php', session: 'admin', title: 'Kelola Banner Slider Promo' },
  { id: 'screen_16_pengaturan_denda', url: 'http://localhost/aksanovaphp_revisi_hell_hihi/pengaturan_denda.php', session: 'admin', title: 'Pengaturan Tarif Denda & Toleransi' },
  { id: 'screen_17_pengaturan_musik', url: 'http://localhost/aksanovaphp_revisi_hell_hihi/pengaturan_musik.php', session: 'admin', title: 'Pengaturan Audio Latar Belakang' }
];

const OUTPUT_DIR = path.join(__dirname, '..', 'mockup_prototype', 'screens');
const DOCS_DIR = path.join(__dirname, '..', 'docs', 'mockup');

fs.mkdirSync(OUTPUT_DIR, { recursive: true });
fs.mkdirSync(DOCS_DIR, { recursive: true });

async function main() {
  const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
  const chromeProc = spawn(chromePath, [
    '--headless',
    '--remote-debugging-port=9222',
    '--disable-gpu',
    '--no-sandbox',
    '--hide-scrollbars',
    '--window-size=1440,900'
  ]);

  console.log('Starting Chrome headless on port 9222...');
  await new Promise(r => setTimeout(r, 1200));

  try {
    const verRes = await fetch('http://localhost:9222/json/version');
    const verData = await verRes.json();
    console.log('Connected to Chrome:', verData.Browser);

    const newTabRes = await fetch('http://localhost:9222/json/new', { method: 'PUT' });
    const tabData = await newTabRes.json();
    const wsUrl = tabData.webSocketDebuggerUrl;

    const ws = new WebSocket(wsUrl);

    let msgId = 1;
    const pendingPromises = new Map();

    ws.onmessage = (event) => {
      const msg = JSON.parse(event.data);
      if (msg.id && pendingPromises.has(msg.id)) {
        const { resolve, reject } = pendingPromises.get(msg.id);
        pendingPromises.delete(msg.id);
        if (msg.error) reject(msg.error);
        else resolve(msg.result);
      }
    };

    await new Promise((resolve) => (ws.onopen = resolve));

    function sendCmd(method, params = {}) {
      return new Promise((resolve, reject) => {
        const id = msgId++;
        pendingPromises.set(id, { resolve, reject });
        ws.send(JSON.stringify({ id, method, params }));
      });
    }

    await sendCmd('Page.enable');
    await sendCmd('Network.enable');

    // Configure viewport for desktop 1440x900 @ 1.5x scale for crisp mockup
    await sendCmd('Emulation.setDeviceMetricsOverride', {
      width: 1440,
      height: 900,
      deviceScaleFactor: 1.5,
      mobile: false
    });

    for (let i = 0; i < SCREENS.length; i++) {
      const sc = SCREENS[i];
      console.log(`[${i + 1}/${SCREENS.length}] Capturing ${sc.id} (${sc.title})...`);

      // Clear existing cookies
      await sendCmd('Network.clearBrowserCookies');

      // Set cookie based on session requirement
      if (sc.session === 'admin') {
        await sendCmd('Network.setCookie', {
          name: 'PHPSESSID',
          value: 'aksasessionadmin999',
          domain: 'localhost',
          path: '/'
        });
      } else if (sc.session === 'member') {
        await sendCmd('Network.setCookie', {
          name: 'PHPSESSID',
          value: 'aksasessionmember999',
          domain: 'localhost',
          path: '/'
        });
      }

      // Navigate
      await sendCmd('Page.navigate', { url: sc.url });

      // Wait for page to settle (load event, animations, fonts)
      await new Promise(r => setTimeout(r, 1000));

      // Capture screenshot
      const shot = await sendCmd('Page.captureScreenshot', {
        format: 'png',
        captureBeyondViewport: false
      });

      const buf = Buffer.from(shot.data, 'base64');
      const outPath = path.join(OUTPUT_DIR, `${sc.id}.png`);
      const docsPath = path.join(DOCS_DIR, `${sc.id}.png`);

      fs.writeFileSync(outPath, buf);
      fs.writeFileSync(docsPath, buf);
      console.log(`    -> Saved to ${sc.id}.png (${buf.length} bytes)`);
    }

    ws.close();
    console.log('\nAll screens successfully captured in high definition!');
  } catch (err) {
    console.error('Error during capture:', err);
  } finally {
    chromeProc.kill();
  }
}

main();
