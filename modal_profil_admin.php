<?php
// modal_profil_admin.php — Modal edit profil & ganti kata sandi admin universal
// Sertakan di semua halaman admin (sebelum pengaturan_panel.php)

if (!isset($admin_name) || !isset($admin_foto)) {
    if (isset($conn)) {
        $p_res = mysqli_query($conn, "SELECT * FROM admin_profile LIMIT 1");
        $p_row = $p_res ? mysqli_fetch_assoc($p_res) : null;
        if (!isset($admin_name)) $admin_name = $p_row["display_name"] ?? ($_SESSION["user_name"] ?? "Admin");
        if (!isset($admin_foto)) $admin_foto = $p_row["foto"] ?? "";
    } else {
        if (!isset($admin_name)) $admin_name = $_SESSION["user_name"] ?? "Admin";
        if (!isset($admin_foto)) $admin_foto = "";
    }
}
$current_page_file = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
?>
<style>
.modal-overlay {
  display: none; position: fixed; inset: 0;
  background: rgba(0,0,0,.65); z-index: 999;
  align-items: center; justify-content: center;
  padding: 20px; backdrop-filter: blur(4px);
}
.modal-overlay.open { display: flex !important; }
@keyframes modalIn {
  from { opacity: 0; transform: scale(.94) translateY(10px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}
.profil-modal-box {
  background: var(--card, #121820);
  border: 1px solid var(--border-color, rgba(216,184,120,.2));
  border-radius: 16px; width: 100%; max-width: 410px;
  max-height: 90vh; overflow-y: auto; padding: 24px;
  box-shadow: 0 20px 60px rgba(0,0,0,.5);
  animation: modalIn .25s cubic-bezier(.22,1,.36,1) both;
  color: var(--text, #eef3f4);
  font-family: var(--font-family, 'Outfit', sans-serif);
}
.profil-modal-box input:focus {
  outline: none;
  border-color: var(--accent, #d8b878) !important;
  box-shadow: 0 0 0 3px rgba(216,184,120,.15);
}
</style>

<!-- ═══════════ MODAL EDIT PROFIL ADMIN ═══════════ -->
<div class="modal-overlay" id="profilModalOverlay">
  <div class="profil-modal-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
      <div style="font-family:'Cormorant Garamond',serif;font-size:1.35rem;font-weight:700;color:var(--text,#eef3f4);display:flex;align-items:center;gap:8px;">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="var(--accent,#d8b878)" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Edit Profil Admin
      </div>
      <button type="button" onclick="closeProfilModal()" style="border:none;background:rgba(216,184,120,.1);width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text,#eef3f4);transition:background .2s;" title="Tutup">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <form method="POST" action="update_profil_admin.php" enctype="multipart/form-data">
      <input type="hidden" name="redirect" value="<?= htmlspecialchars($current_page_file) ?>"/>

      <!-- Preview foto -->
      <div style="position:relative;width:110px;height:110px;margin:0 auto 16px;border-radius:50%;border:2px dashed var(--border-color,rgba(216,184,120,.3));overflow:hidden;cursor:pointer;background:rgba(255,255,255,.03);" onclick="document.getElementById('inputFotoAdmin').click()" title="Klik untuk pilih foto baru">
        <?php if ($admin_foto && file_exists($admin_foto)): ?>
          <img id="profilPreviewImg" src="<?= htmlspecialchars($admin_foto) ?>?v=<?= filemtime($admin_foto) ?>" alt="Foto" style="width:100%;height:100%;max-width:100%;max-height:100%;object-fit:cover;display:block;"/>
          <div id="profilUploadPlaceholder" style="display:none;"></div>
        <?php else: ?>
          <img id="profilPreviewImg" src="" alt="Foto" style="display:none;width:100%;height:100%;max-width:100%;max-height:100%;object-fit:cover;"/>
          <div id="profilUploadPlaceholder" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--muted);gap:4px;">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
              <circle cx="12" cy="13" r="4"/>
            </svg>
            <span style="font-size:.68rem;">Pilih Foto</span>
          </div>
        <?php endif; ?>
      </div>
      <input type="file" id="inputFotoAdmin" name="foto_admin" accept="image/*" style="display:none"/>

      <?php if (isset($_GET['profil_saved'])): ?>
      <div style="background:rgba(5,150,105,.15);color:#4ade80;border:1px solid rgba(5,150,105,.3);padding:9px 12px;border-radius:8px;font-size:.8rem;font-weight:700;margin-bottom:12px;">✅ Profil berhasil disimpan.</div>
      <?php elseif (isset($_GET['profil_err'])): ?>
      <div style="background:rgba(220,38,38,.15);color:#f87171;border:1px solid rgba(220,38,38,.3);padding:9px 12px;border-radius:8px;font-size:.8rem;font-weight:700;margin-bottom:12px;">⚠️ <?= htmlspecialchars($_GET['profil_err']) ?></div>
      <?php endif; ?>

      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:.76rem;font-weight:700;color:var(--text);margin-bottom:6px;">Nama Tampilan</label>
        <input type="text" name="display_name"
               value="<?= htmlspecialchars($admin_name) ?>"
               placeholder="Nama yang ditampilkan" required
               style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid var(--border-color,rgba(216,184,120,.2));background:rgba(255,255,255,.05);color:var(--text,#eef3f4);font-family:inherit;font-size:.85rem;box-sizing:border-box;"/>
      </div>

      <!-- ─── Ganti Kata Sandi (opsional) ─── -->
      <div style="border-top:1px solid var(--border-color,rgba(216,184,120,.18));margin:14px 0 12px;padding-top:14px;">
        <div style="font-size:.73rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
          <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          Ganti Kata Sandi <span style="font-weight:400;opacity:.6;text-transform:none;">(opsional)</span>
        </div>

        <div style="margin-bottom:10px;">
          <label style="display:block;font-size:.74rem;font-weight:700;color:var(--text);margin-bottom:5px;">Kata Sandi Saat Ini</label>
          <div style="position:relative;">
            <input type="password" name="current_password" id="pwCurrent"
                   placeholder="Masukkan kata sandi saat ini" autocomplete="current-password"
                   style="width:100%;padding:9px 38px 9px 12px;border-radius:8px;border:1px solid var(--border-color,rgba(216,184,120,.2));background:rgba(255,255,255,.05);color:var(--text,#eef3f4);font-family:inherit;font-size:.85rem;box-sizing:border-box;"/>
            <button type="button" onclick="togglePwAdmin('pwCurrent',this)"
                    style="position:absolute;right:8px;top:50%;transform:translateY(-50%);border:none;background:none;cursor:pointer;color:var(--muted,#888);padding:4px;" title="Tampilkan/sembunyikan">
              <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <div style="margin-bottom:10px;">
          <label style="display:block;font-size:.74rem;font-weight:700;color:var(--text);margin-bottom:5px;">Kata Sandi Baru</label>
          <div style="position:relative;">
            <input type="password" name="new_password" id="pwNew"
                   placeholder="Min. 8 karakter, huruf + angka" autocomplete="new-password"
                   style="width:100%;padding:9px 38px 9px 12px;border-radius:8px;border:1px solid var(--border-color,rgba(216,184,120,.2));background:rgba(255,255,255,.05);color:var(--text,#eef3f4);font-family:inherit;font-size:.85rem;box-sizing:border-box;" oninput="cekKekuatanSandi(this.value)"/>
            <button type="button" onclick="togglePwAdmin('pwNew',this)"
                    style="position:absolute;right:8px;top:50%;transform:translateY(-50%);border:none;background:none;cursor:pointer;color:var(--muted,#888);padding:4px;" title="Tampilkan/sembunyikan">
              <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <div style="height:4px;border-radius:2px;margin-top:5px;background:rgba(255,255,255,.1);overflow:hidden;">
            <div id="pwStrengthBar" style="height:100%;width:0;border-radius:2px;transition:width .3s,background .3s;"></div>
          </div>
          <div id="pwStrengthLabel" style="font-size:.67rem;color:var(--muted);margin-top:2px;"></div>
        </div>

        <div style="margin-bottom:0;">
          <label style="display:block;font-size:.74rem;font-weight:700;color:var(--text);margin-bottom:5px;">Konfirmasi Kata Sandi Baru</label>
          <div style="position:relative;">
            <input type="password" name="confirm_new_password" id="pwConfirm"
                   placeholder="Ulangi kata sandi baru" autocomplete="new-password"
                   style="width:100%;padding:9px 38px 9px 12px;border-radius:8px;border:1px solid var(--border-color,rgba(216,184,120,.2));background:rgba(255,255,255,.05);color:var(--text,#eef3f4);font-family:inherit;font-size:.85rem;box-sizing:border-box;" oninput="cekKonfirmasi()"/>
            <button type="button" onclick="togglePwAdmin('pwConfirm',this)"
                    style="position:absolute;right:8px;top:50%;transform:translateY(-50%);border:none;background:none;cursor:pointer;color:var(--muted,#888);padding:4px;" title="Tampilkan/sembunyikan">
              <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <div id="pwMatchLabel" style="font-size:.67rem;margin-top:2px;"></div>
        </div>
      </div>

      <div style="display:flex;gap:10px;margin-top:18px;">
        <button type="button" onclick="closeProfilModal()" style="flex:1;padding:11px;border-radius:8px;border:none;background:rgba(255,255,255,.08);color:var(--text);font-family:inherit;font-size:.85rem;font-weight:700;cursor:pointer;transition:background .2s;">Batal</button>
        <button type="submit" style="flex:1;padding:11px;border-radius:8px;border:none;background:var(--accent,#d8b878);color:#1a1205;font-family:inherit;font-size:.85rem;font-weight:700;cursor:pointer;transition:opacity .2s;">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
// ─── Modal Profil & Ganti Sandi Global ───
function openProfilModal() {
  var m = document.getElementById('profilModalOverlay');
  if (m) m.classList.add('open');
}
function closeProfilModal() {
  var m = document.getElementById('profilModalOverlay');
  if (m) m.classList.remove('open');
}
(function(){
  var m = document.getElementById('profilModalOverlay');
  if (m) {
    m.addEventListener('click', function(e){ if (e.target === this) closeProfilModal(); });
  }
  var inp = document.getElementById('inputFotoAdmin');
  if (inp) {
    inp.addEventListener('change', function(e){
      var f = e.target.files[0];
      if (!f) return;
      var r = new FileReader();
      r.onload = function(ev){
        var img = document.getElementById('profilPreviewImg');
        var ph  = document.getElementById('profilUploadPlaceholder');
        if (img) { img.src = ev.target.result; img.style.display = 'block'; }
        if (ph)  ph.style.display = 'none';
      };
      r.readAsDataURL(f);
    });
  }
})();

function togglePwAdmin(inputId, btn) {
  var input = document.getElementById(inputId);
  if (!input) return;
  input.type = input.type === 'password' ? 'text' : 'password';
  btn.style.color = input.type === 'text' ? 'var(--accent,#d8b878)' : 'var(--muted,#888)';
}

function cekKekuatanSandi(val) {
  var bar = document.getElementById('pwStrengthBar');
  var label = document.getElementById('pwStrengthLabel');
  if (!bar || !label) return;
  var s = 0;
  if (val.length >= 8) s++;
  if (/[A-Z]/.test(val)) s++;
  if (/[0-9]/.test(val)) s++;
  if (/[^A-Za-z0-9]/.test(val)) s++;
  var c = ['#ef4444','#f97316','#eab308','#22c55e'];
  var l = ['Terlalu lemah','Lemah','Sedang','Kuat'];
  bar.style.width = val.length ? (s / 4 * 100) + '%' : '0';
  bar.style.background = val.length ? (c[s - 1] || '#ef4444') : 'transparent';
  label.textContent = val.length ? (l[s - 1] || 'Terlalu lemah') : '';
  label.style.color = val.length ? (c[s - 1] || '#ef4444') : '';
  cekKonfirmasi();
}

function cekKonfirmasi() {
  var pw1 = document.getElementById('pwNew');
  var pw2 = document.getElementById('pwConfirm');
  var label = document.getElementById('pwMatchLabel');
  if (!pw1 || !pw2 || !label) return;
  if (!pw2.value) { label.textContent = ''; return; }
  if (pw1.value === pw2.value) {
    label.textContent = '✅ Kata sandi cocok';
    label.style.color = '#22c55e';
  } else {
    label.textContent = '❌ Kata sandi tidak cocok';
    label.style.color = '#ef4444';
  }
}
</script>

<?php require_once __DIR__ . '/notifikasi_pengajuan_admin.php'; ?>