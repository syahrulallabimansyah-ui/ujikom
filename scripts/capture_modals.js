// scripts/capture_modals.js
// Capture key interactive modal dialog states via CDP
const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const OUTPUT_DIR = path.join(__dirname, '..', 'mockup_prototype', 'screens');
const DOCS_DIR = path.join(__dirname, '..', 'docs', 'mockup');

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

  await new Promise(r => setTimeout(r, 1200));

  try {
    const newTabRes = await fetch('http://localhost:9222/json/new', { method: 'PUT' });
    const tabData = await newTabRes.json();
    const ws = new WebSocket(tabData.webSocketDebuggerUrl);

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
    await sendCmd('Runtime.enable');

    await sendCmd('Emulation.setDeviceMetricsOverride', {
      width: 1440,
      height: 900,
      deviceScaleFactor: 1.5,
      mobile: false
    });

    // 1. Modal Detail Buku pada daftar_buku.php
    console.log('Capturing Modal Detail Buku...');
    await sendCmd('Network.setCookie', {
      name: 'PHPSESSID',
      value: 'aksasessionmember999',
      domain: 'localhost',
      path: '/'
    });
    await sendCmd('Page.navigate', { url: 'http://localhost/aksanovaphp_revisi_hell_hihi/daftar_buku.php' });
    await new Promise(r => setTimeout(r, 1200));

    // Click first book card or trigger detail modal
    await sendCmd('Runtime.evaluate', {
      expression: `
        const firstBook = document.querySelector('.card, .book-card, [onclick*="bukaModal"], [onclick*="detail"]');
        if (firstBook) firstBook.click();
        else if (typeof bukaModalDetail === 'function') bukaModalDetail(47);
        else if (typeof openModal === 'function') openModal(47);
      `
    });
    await new Promise(r => setTimeout(r, 800));

    const shot1 = await sendCmd('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(OUTPUT_DIR, 'screen_modal_detail_buku.png'), Buffer.from(shot1.data, 'base64'));
    fs.writeFileSync(path.join(DOCS_DIR, 'screen_modal_detail_buku.png'), Buffer.from(shot1.data, 'base64'));
    console.log('Saved screen_modal_detail_buku.png');

    // 2. Modal Tambah Buku pada halaman_admin.php
    console.log('Capturing Modal Tambah Buku Admin...');
    await sendCmd('Network.clearBrowserCookies');
    await sendCmd('Network.setCookie', {
      name: 'PHPSESSID',
      value: 'aksasessionadmin999',
      domain: 'localhost',
      path: '/'
    });
    await sendCmd('Page.navigate', { url: 'http://localhost/aksanovaphp_revisi_hell_hihi/halaman_admin.php' });
    await new Promise(r => setTimeout(r, 1200));

    await sendCmd('Runtime.evaluate', {
      expression: `
        const btnAdd = document.querySelector('.btn-add, [onclick*="Modal"], [onclick*="modalTambah"]');
        if (btnAdd) btnAdd.click();
      `
    });
    await new Promise(r => setTimeout(r, 800));

    const shot2 = await sendCmd('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(OUTPUT_DIR, 'screen_modal_tambah_buku.png'), Buffer.from(shot2.data, 'base64'));
    fs.writeFileSync(path.join(DOCS_DIR, 'screen_modal_tambah_buku.png'), Buffer.from(shot2.data, 'base64'));
    console.log('Saved screen_modal_tambah_buku.png');

    ws.close();
  } catch (err) {
    console.error('Modal capture error:', err);
  } finally {
    chromeProc.kill();
  }
}

main();
