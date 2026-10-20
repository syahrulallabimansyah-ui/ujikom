const { spawn } = require('child_process');
const fs = require('fs');

async function test() {
  const chromeProc = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
    '--headless',
    '--remote-debugging-port=9222',
    '--disable-gpu',
    '--no-sandbox',
    '--hide-scrollbars',
    '--window-size=1440,900'
  ]);
  await new Promise(r => setTimeout(r, 1200));

  try {
    const tabRes = await fetch('http://localhost:9222/json/new', { method: 'PUT' });
    const tab = await tabRes.json();
    const ws = new WebSocket(tab.webSocketDebuggerUrl);

    let id = 1;
    const map = new Map();
    ws.onmessage = (e) => {
      const m = JSON.parse(e.data);
      if (m.id && map.has(m.id)) {
        map.get(m.id)(m.result);
      }
    };
    await new Promise(r => (ws.onopen = r));

    const send = (method, params = {}) =>
      new Promise((res) => {
        const cur = id++;
        map.set(cur, res);
        ws.send(JSON.stringify({ id: cur, method, params }));
      });

    await send('Page.enable');
    await send('Network.enable');
    await send('Runtime.enable');
    await send('Network.setCookie', {
      name: 'PHPSESSID',
      value: 'aksasessionadmin999',
      domain: 'localhost',
      path: '/'
    });

    await send('Page.navigate', { url: 'http://localhost/aksanovaphp_revisi_hell_hihi/halaman_admin.php' });
    // Wait 2.5s for 800ms polling to finish
    await new Promise((r) => setTimeout(r, 2500));

    const evalRes = await send('Runtime.evaluate', {
      expression: `
        (() => {
          const link = document.querySelector('a[href*="pengajuan_buku.php"]');
          const badges = link ? link.querySelectorAll('.aksa-notif-badge, .badge-nav, span[style*="border-radius:20px"], span[style*="border-radius: 20px"]') : [];
          return {
            linkText: link ? link.innerText.trim().replace(/\\s+/g, ' ') : null,
            badgeCount: badges.length,
            badgesText: Array.from(badges).map(b => b.textContent.trim())
          };
        })()
      `,
      returnByValue: true
    });

    console.log('Result from page after polling:', evalRes.result.value);

    // Also update screenshot in mockup_prototype
    const shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync('mockup_prototype/screens/screen_10_halaman_admin.png', Buffer.from(shot.data, 'base64'));
    fs.writeFileSync('docs/mockup/screen_10_halaman_admin.png', Buffer.from(shot.data, 'base64'));
    console.log('Updated screen_10_halaman_admin.png in mockup screens.');

    ws.close();
  } finally {
    chromeProc.kill();
  }
}

test();
