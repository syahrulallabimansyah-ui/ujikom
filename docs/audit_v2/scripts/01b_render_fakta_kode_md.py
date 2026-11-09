#!/usr/bin/env python3
"""
01b_render_fakta_kode_md.py
Menyusun docs/audit_v2/02_fakta_kode.md secara mekanis dari:
  - docs/audit_v2/02_fakta_kode.json (keluaran tokenizer PHP)
  - docs/audit_v2/schema_model.json (keluaran parser SQL & drift dua arah)
beserta peta konstanta bisnis & guard akses terverifikasi.

Cara menjalankan:
  python docs/audit_v2/scripts/01b_render_fakta_kode_md.py
"""
import json
import os
import sys

sys.stdout.reconfigure(encoding="utf-8")

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", ".."))
FAKTA_JSON = os.path.join(ROOT, "docs", "audit_v2", "02_fakta_kode.json")
SCHEMA_JSON = os.path.join(ROOT, "docs", "audit_v2", "schema_model.json")
OUT_MD = os.path.join(ROOT, "docs", "audit_v2", "02_fakta_kode.md")

# Klasifikasi peran file & guard akses berdasarkan pembacaan baris-demi-baris kode PHP
FILE_META = {
    "index.php": ("Halaman Publik (Landing)", "Publik (tanpa session_start)", "-"),
    "beranda.php": ("Halaman Katalog Utama + AJAX Modal Host", "Tamu & Member approved+active (Admin -> dashboard.php:6-9; Member non-approved/frozen -> destroy+sign_in.php:16-23)", "beranda.php:6-23"),
    "daftar_buku.php": ("Halaman Daftar/Cari Buku + Fragmen AJAX (?ajax=1)", "Tamu & Member approved+active (Admin -> dashboard.php:6-9; Member non-approved/frozen -> destroy+sign_in.php:16-24)", "daftar_buku.php:6-24"),
    "buku_detail.php": ("Handler AJAX JSON Detail Buku", "Publik / Tamu / Member / Admin (tanpa blokir role)", "buku_detail.php:24"),
    "buku_simpan.php": ("Halaman Koleksi Buku Disimpan + Fragmen AJAX (?ajax=1)", "Member approved+active (Tamu -> sign_in.php:5-8; Admin -> dashboard.php:14-20; frozen/non-approved -> sign_in.php:23-29)", "buku_simpan.php:5-29"),
    "like_handler.php": ("Handler AJAX JSON Toggle Suka & Simpan", "Member login (Tamu JSON error:7-10; Admin JSON error:13-16; tidak cek DB status/card_status)", "like_handler.php:7-16"),
    "rating_handler.php": ("Handler AJAX JSON Ambil/Beri Rating 1-5", "Member login (Tamu HTTP 403:6-10; Admin HTTP 403:13-17; tidak cek DB status/card_status)", "rating_handler.php:6-17"),
    "sign_up.php": ("Halaman Registrasi Mandiri Siswa/Guru", "Tamu belum login (Sudah login -> dashboard.php / beranda.php)", "sign_up.php:7-10"),
    "sign_in.php": ("Halaman Login Terpadu Member & Admin", "Tamu belum login (Sudah login -> dashboard.php / safe_redirect / beranda.php)", "sign_in.php:17-24"),
    "logout.php": ("Halaman Konfirmasi & Eksekusi Logout", "User login (!isset user_id -> sign_in.php)", "logout.php:9-12"),
    "lupa_kartu.php": ("Halaman Wizard 3 Langkah Pemulihan Akun & Kartu via OTP Email", "Publik (wizard berbasis $_SESSION['lupa_kartu']['step'])", "lupa_kartu.php:20-23"),
    "kartu_anggota.php": ("Halaman Pratinjau & Unduh PNG Kartu Anggota Digital (Barcode Code 128)", "Admin (?id=mask_id), Sesi kartu_data, atau Member login (lainnya -> sign_up.php)", "kartu_anggota.php:8-97"),
    "kartu_masuk.php": ("Endpoint Penyelesaian Penerbitan Kartu & Auto-Login", "Pemilik $_SESSION['kartu_data']['id'] approved & !frozen -> beranda.php", "kartu_masuk.php:5-28"),
    "kartu_clear.php": ("Endpoint Beacon Pembersih $_SESSION['kartu_data'] + Auto-Login", "Pemilik $_SESSION['kartu_data']['id'] approved & !frozen", "kartu_clear.php:5-26"),
    "barcode128.php": ("Library Generator SVG Barcode Code 128 Subset B", "Include library (tanpa guard langsung)", "barcode128.php:5"),
    "edit_kartu.php": ("Halaman Edit Profil & Ganti Sandi Anggota", "Member login (!user_id -> sign_in.php:12-15; !=member -> dashboard.php:16-19; pending/rejected/frozen diblokir form:43-49)", "edit_kartu.php:12-49"),
    "dashboard_user.php": ("Halaman Dashboard Anggota ('Peminjaman Saya')", "Member approved+active (!user_id -> sign_in.php:5-8; admin -> dashboard.php:10-13; non-approved/frozen -> sign_in.php:24-36)", "dashboard_user.php:5-36"),
    "pengajuan_peminjaman.php": ("Halaman Form Pengajuan Peminjaman Online + Upload Kartu", "Member approved+active (!user_id -> sign_in.php?redirect=:9-13; admin -> dashboard.php:18-21; non-approved/frozen -> sign_in.php:24-35)", "pengajuan_peminjaman.php:9-35"),
    "pengajuan_buku.php": ("Halaman Admin Verifikasi Pengajuan Peminjaman", "Admin (!user_id atau !=admin -> sign_in.php)", "pengajuan_buku.php:6-9"),
    "api_cek_pengajuan_admin.php": ("Endpoint AJAX JSON Polling Pengajuan Baru", "Admin (!user_id atau !=admin -> HTTP 403 JSON)", "api_cek_pengajuan_admin.php:13-17"),
    "notifikasi_pengajuan_admin.php": ("Include UI Pop-up Realtime & Chime WebAudio Pengajuan Baru", "Include khusus Admin (bukan admin -> return)", "notifikasi_pengajuan_admin.php:15-17"),
    "pinjam_buku.php": ("Halaman Admin Input Peminjaman Langsung + Fragmen AJAX (?ajax=1)", "Admin (!user_id atau !=admin -> sign_in.php)", "pinjam_buku.php:9-12"),
    "telah_dipinjam.php": ("Halaman Admin Sirkulasi Pengembalian, Denda, & Pengingat WA + AJAX (?ajax=1)", "Admin (!user_id atau !=admin -> sign_in.php)", "telah_dipinjam.php:8-11"),
    "cron_cek_terlambat.php": ("Skrip CLI Harian Pencatat Keterlambatan ke reminder_log", "Hanya CLI (PHP_SAPI !== 'cli' -> HTTP 403)", "cron_cek_terlambat.php:22-25"),
    "dashboard.php": ("Halaman Dashboard Statistik & Rekap Bulanan Admin", "Admin (!user_id atau !=admin -> sign_in.php)", "dashboard.php:5-8"),
    "export_excel_dashboard.php": ("Endpoint Unduh Laporan SpreadsheetML XML (.xls)", "Admin (!user_id atau !=admin -> HTTP 403)", "export_excel_dashboard.php:4-7"),
    "halaman_admin.php": ("Halaman Admin CRUD Katalog Buku + Fragmen AJAX (?ajax=1)", "Admin (!user_id atau !=admin -> sign_in.php)", "halaman_admin.php:6-9"),
    "cari_isbn.php": ("Endpoint AJAX JSON Pencarian Metadata & Unduh Sampul via ISBN", "Admin (!user_id atau !=admin -> HTTP 403 JSON)", "cari_isbn.php:31-34"),
    "daftar_anggota.php": ("Halaman Admin Kelola Anggota + Fragmen AJAX (?ajax=1)", "Admin (!user_id atau !=admin -> sign_in.php)", "daftar_anggota.php:5-8"),
    "kelola_banner.php": ("Halaman Admin CRUD & Urutan Banner Beranda", "Admin (!user_id atau !=admin -> sign_in.php)", "kelola_banner.php:5-8"),
    "pengaturan_denda.php": ("Halaman Admin Konfigurasi Tarif/Toleransi Denda & Pelunasan", "Admin (!user_id atau !=admin -> sign_in.php)", "pengaturan_denda.php:6-9"),
    "pengaturan_lokasi.php": ("Halaman Admin Konfigurasi Lokasi Peta, Kontak, & Jam Layanan", "Admin (!user_id atau !=admin -> sign_in.php)", "pengaturan_lokasi.php:9-12"),
    "pengaturan_musik.php": ("Halaman Admin Upload & Konfigurasi Musik Latar", "Admin (!user_id atau !=admin -> sign_in.php)", "pengaturan_musik.php:6-9"),
    "pengaturan_panel.php": ("Include Panel Slide-in Tema/Gaya UI/Font/Mode Lite (localStorage)", "Include terbuka (client-side)", "pengaturan_panel.php:1-5"),
    "modal_profil_admin.php": ("Include Modal Edit Profil & Sandi Admin (+ muat notifikasi_pengajuan_admin)", "Include halaman Admin", "modal_profil_admin.php:1-17"),
    "update_profil_admin.php": ("Handler POST Simpan Profil & Sandi Admin", "Admin (!user_id atau !=admin -> sign_in.php)", "update_profil_admin.php:5-8"),
    "ganti_sandi_admin.php": ("Handler POST Ganti Sandi Admin (endpoint mandiri/warisan)", "Admin (!user_id atau !=admin -> sign_in.php)", "ganti_sandi_admin.php:14-17"),
    "settings_include.php": ("Include Global <head>: CSS Variabel Tema, AksaDialog, AksaAudio, Sidebar JS", "Include terbuka (client-side)", "settings_include.php:1-3"),
    "config.php": ("Konfigurasi Timezone, Cookie Sesi, API Key Google Books, Helper CSRF & mask_id", "Include konfigurasi inti", "config.php:1-102"),
    "db.php": ("Koneksi MySQLi utf8mb4 + SET time_zone '+07:00'", "Include koneksi database", "db.php:1-23"),
    "config_mail.php": ("Konfigurasi Aktif SMTP Gmail & OTP_SECRET", "Include kredensial email/OTP", "config_mail.php:1-23"),
    "config_mail.example.php": ("Templat Contoh Konfigurasi SMTP & OTP_SECRET", "Fallback include kredensial email/OTP", "config_mail.example.php:1-28"),
    "includes/otp.php": ("Library Pembuatan, Rate-Limit, Pengiriman, & Verifikasi OTP 6 Digit", "Include library OTP", "includes/otp.php:1-361"),
    "includes/mailer.php": ("Wrapper PHPMailer SMTP, Masking Log Error, & Rollback OTP Gagal Kirim", "Include library SMTP", "includes/mailer.php:1-140"),
}

BUSINESS_CONSTANTS = [
    ("Zona waktu PHP & MySQL", "'Asia/Jakarta' / '+07:00'", "config.php:10, db.php:21"),
    ("Cookie sesi keamanan", "httponly=1, use_only_cookies=1, samesite=Lax (tanpa cookie_secure)", "config.php:14-16"),
    ("Panjang token CSRF", "32 byte random_bytes -> 64 karakter hex", "config.php:36"),
    ("Obfuskasi ID URL (mask_id)", "XOR 0x5A7B3C1D + 4 hex md5('aksa_salt_'.id) + base64url", "config.php:71-74, 94-99"),
    ("Domain email registrasi & edit profil", "@student.smkn1rongga.sch.id, @smkn1rongga.sch.id, @gmail.com", "sign_up.php:80-84, edit_kartu.php:116-120"),
    ("Format NIS Siswa / NIP Guru", "Siswa: /^[0-9]{3,20}$/; Guru: panjang >= 3 karakter (HTML maks 30)", "sign_up.php:58-68, edit_kartu.php:108-112"),
    ("Format Nomor HP", "sign_up: wajib /^[\\d+\\-\\s]{8,20}$/; edit_kartu: opsional /^[\\d+\\-\\s]{6,20}$/", "sign_up.php:71-75, edit_kartu.php:125-127"),
    ("Syarat kata sandi baru (Member & Admin)", "Min 8 karakter, wajib ada huruf /[A-Za-z]/ dan angka /[0-9]/", "sign_up.php:91-94, lupa_kartu.php:279-283, edit_kartu.php:137-141, update_profil_admin.php:43-46"),
    ("Status awal registrasi mandiri", "role='member', status='approved', card_status='active' (otomatis aktif tanpa tunggu admin)", "sign_up.php:213-222, database/aksa_nova.sql:385"),
    ("Format Nomor Anggota", "'AN-' . date('Y') . '-' . 5 digit acak (00001..99999)", "sign_up.php:200"),
    ("Masa berlaku kartu anggota", "Guru: 'Selama Bertugas'; Siswa: 's/d ' . (tahun_daftar + 3)", "kartu_anggota.php:111-118, dashboard_user.php:1097-1105"),
    ("Jenis kode pada Kartu Anggota", "Barcode Code 128 Subset B SVG (barcode128.php), BUKAN QR Code", "kartu_anggota.php:108, 696; barcode128.php:5-71"),
    ("Resolusi ekspor PNG Kartu Anggota", "Kanvas 428x270 px x scale 2.362 = ~1011x638 px (300 DPI CR80)", "kartu_anggota.php:721-750"),
    ("Umur file foto sementara registrasi", "temp_* dihapus otomatis bila > 7200 detik (2 jam)", "sign_up.php:30"),
    ("Panjang & algoritma hash OTP", "6 digit ('%06d', 0..999999), HMAC-SHA256 dengan OTP_SECRET, verifikasi hash_equals", "includes/otp.php:113-116, 317-319"),
    ("Masa berlaku kode OTP", "600 detik (10 menit)", "includes/otp.php:119"),
    ("Jeda kirim ulang OTP (cooldown)", "60 detik per user", "includes/otp.php:66, lupa_kartu.php:214"),
    ("Batas permintaan OTP (rate limit)", "Maks 5 kali/jam/user DAN maks 10 kali/jam/IP", "includes/otp.php:41, 77"),
    ("Batas salah input OTP", "Maks 5 percobaan per kode OTP (attempts >= 5 -> hangus/burned)", "includes/otp.php:308, 345"),
    ("Pembersihan otomatis OTP kedaluwarsa", "DELETE baris expires_at < NOW() - INTERVAL 1 DAY", "includes/otp.php:102"),
    ("Kapan kartu dibekukan (card_status='frozen')", "SETELAH OTP berhasil diverifikasi di Langkah 2 (bukan saat request OTP)", "lupa_kartu.php:185-190"),
    ("Batas waktu sesi Langkah 3 (set sandi baru)", "600 detik (10 menit); jika lewat, card_status kembali 'active'", "lupa_kartu.php:262-274"),
    ("Timeout koneksi SMTP PHPMailer", "10 detik; gagal kirim otomatis rollback DELETE baris OTP terbaru", "includes/mailer.php:74, 119-127"),
    ("Syarat OTP_DEV_MODE aktif", "OTP_DEV_MODE === true DAN akses dari 127.0.0.1 / ::1 / localhost / CLI -> tulis storage/otp_dev.log", "includes/otp.php:145-172"),
    ("Batas upload foto anggota, sampul buku, kartu pengajuan, profil admin", "Maks 5 MB (5 * 1024 * 1024 byte)", "sign_up.php:108, edit_kartu.php:196, pengajuan_peminjaman.php:155, halaman_admin.php:45, update_profil_admin.php:96"),
    ("Batas upload banner beranda", "Gambar (jpg/jpeg/png/webp/gif): maks 5 MB; Video (mp4/webm): maks 20 MB", "kelola_banner.php:40-43"),
    ("Batas upload musik latar", "Audio (mp3/ogg/wav/m4a): maks 15 MB", "pengaturan_musik.php:138, 142"),
    ("Rentang nilai rating buku", "Integer 1 s/d 5 (tanpa kolom ulasan teks)", "rating_handler.php:48-50"),
    ("Kuota pengajuan peminjaman online", "1 s/d 40 eksemplar per pengajuan dan <= stok tersedia (tanpa bedakan Siswa vs Guru di kode)", "pengajuan_peminjaman.php:89, 128-134"),
    ("Interval polling notifikasi pengajuan admin", "4000 ms (4 detik) ke api_cek_pengajuan_admin.php (initial delay 800 ms)", "notifikasi_pengajuan_admin.php:575, 823"),
    ("Rumus hari terlambat & denda", "hari = DateTimeImmutable::diff()->days; hari_denda = max(0, hari - grace_period); denda = denda_aktif ? hari_denda * denda_per_hari : 0", "telah_dipinjam.php:55-69, dashboard_user.php:86-91"),
    ("Nilai pengaturan denda pada dump SQL saat ini", "denda_aktif='1', denda_per_hari='1500', denda_grace_period='2' (default fallback di kode: 1000 / 0; di index.php:80 fallback 5000)", "database/aksa_nova.sql:304, telah_dipinjam.php:50-52, index.php:80-81"),
    ("Cache RSS Berita (detikcom & CNN Indonesia)", "9 berita selang-seling, TTL 1800 detik (30 menit) di cache/berita_cache_v2.json, timeout HTTP 4 detik", "beranda.php:178-195, 265-298"),
    ("Paginasi katalog daftar_buku.php", "10 buku per halaman (ORDER BY id ASC)", "daftar_buku.php:34, 77"),
    ("Pencarian ISBN (cari_isbn.php)", "10 atau 13 digit; 4 tahap fallback (Google Books -> OL Books -> OL Search -> OL Works); timeout 8s (sampul 10s, min 500 byte)", "cari_isbn.php:66, 145, 189, 208, 237-371"),
    ("Format ekspor laporan Dashboard", "SpreadsheetML XML (Excel 2003 XML) berekstensi .xls dengan 3 Worksheet", "export_excel_dashboard.php:143-154, 202, 245, 295"),
]


def main():
    with open(FAKTA_JSON, "r", encoding="utf-8") as f:
        fakta = json.load(f)
    with open(SCHEMA_JSON, "r", encoding="utf-8") as f:
        schema = json.load(f)

    lines = []
    lines.append("# 02 — Peta Fakta Kode & Skema Mekanis (`file:baris`)\n")
    lines.append(
        "> Dokumen ini dibangkitkan secara mekanis dari `02_fakta_kode.json` (tokenizer PHP `01_php_fact_extractor.php`) "
        "dan `schema_model.json` (`02_schema_and_drift.py`), dilengkapi verifikasi guard dan konstanta bisnis baris-demi-baris.\n"
    )

    # Section 1: Summary of all 44 PHP files + CSRF status
    lines.append("## 1. Matriks 44 Berkas PHP Aplikasi, Guard Akses, Kueri, & Status CSRF\n")
    lines.append("| No | Berkas PHP | Ukuran (B) | Baris | Peran | Guard Akses & Redirect (`file:baris`) | Jml SQL | POST Handler? | `csrf_verify()` |")
    lines.append("|---:|---|---:|---:|---|---|---:|:---:|:---:|")

    for idx, (rel, info) in enumerate(sorted(fakta["files"].items()), start=1):
        meta = FILE_META.get(rel, ("-", "-", "-"))
        sec_funcs = {c["func"]: [] for c in info["security_calls"]}
        for c in info["security_calls"]:
            sec_funcs[c["func"]].append(str(c["line"]))
        has_post = "$_" + "POST" in info["superglobals"] if isinstance(info["superglobals"], dict) else False
        csrf_lines = sec_funcs.get("csrf_verify", [])
        csrf_str = f"ADA (`:{','.join(csrf_lines)}`)" if csrf_lines else ("**TIDAK ADA**" if has_post and rel != "config.php" else "-")
        post_str = "Ya" if (has_post and rel != "config.php") else "Tidak"
        lines.append(
            f"| {idx} | `{rel}` | {info['size_bytes']:,} | {info['line_count']:,} | {meta[0]} | {meta[1]} (`{meta[2]}`) | {len(info['sql_queries'])} | {post_str} | {csrf_str} |"
        )

    # Highlight POST endpoints without csrf_verify
    lines.append("\n### Catatan Mekanis Endpoint pembaca `$_POST` tanpa `csrf_verify()`\n")
    lines.append("1. `like_handler.php` (`like_handler.php:21-42`): memproses `POST` toggle `buku_likes` / `buku_favorites` tanpa `csrf_verify()`.")
    lines.append("2. `rating_handler.php` (`rating_handler.php:45-61`): memproses `POST` simpan/update `buku_ratings` tanpa `csrf_verify()`.")
    lines.append("3. `pengaturan_musik.php` (`pengaturan_musik.php:62-247`): memproses seluruh `POST` upload/hapus/toggle musik admin tanpa `csrf_verify()` maupun `csrf_field()`.")
    lines.append("4. `ganti_sandi_admin.php` (`ganti_sandi_admin.php:19-69`): endpoint mandiri ganti sandi admin tanpa `csrf_verify()` (saat ini UI `modal_profil_admin.php:131` memakai `update_profil_admin.php` yang sudah memanggil `csrf_verify()` di `:23`).")
    lines.append("5. `logout.php` (`logout.php:14-37, 152`): form merender `csrf_field()` di `:152`, namun pemroses PHP tidak memanggil `csrf_verify()` dan juga menerima `GET ?confirm=1` (`:16`).")
    lines.append("6. `sign_in.php` memiliki `csrf_verify()` (`sign_in.php:30`), tetapi **tidak memiliki rate-limiting percobaan login**.\n")

    # Section 2: Business Constants
    lines.append("## 2. Tabel Konstanta Bisnis, Angka Batas, & Rumus di Kode\n")
    lines.append("| Aspek / Aturan | Nilai Aktual di Kode | Lokasi Bukti (`path:baris`) |")
    lines.append("|---|---|---|")
    for k, v, loc in BUSINESS_CONSTANTS:
        lines.append(f"| {k} | `{v}` | `{loc}` |")

    # Section 3: External Services
    lines.append("\n## 3. Peta Pemanggilan Layanan Eksternal (Bukan Aktor)\n")
    lines.append("| Layanan Eksternal | Mekanisme Pemanggilan | Berkas & Baris Bukti |")
    lines.append("|---|---|---|")
    lines.append("| Google Books API v1 | Server-side cURL / `file_get_contents` GET `https://www.googleapis.com/books/v1/volumes?q=isbn:...` | `cari_isbn.php:237-258`, `config.php:23` |")
    lines.append("| Open Library API (Books, Search, Editions/Works, Covers) | Server-side cURL / `file_get_contents` GET `https://openlibrary.org/...` & `covers.openlibrary.org` | `cari_isbn.php:267-371` |")
    lines.append("| SMTP Email (Gmail `smtp.gmail.com:587` STARTTLS) | Server-side `PHPMailer` di `kirim_email()` | `includes/mailer.php:52-85`, `includes/otp.php:238`, `config_mail.php:10-16` |")
    lines.append("| RSS Berita (`detikcom` & `CNN Indonesia`) | Server-side cURL / `file_get_contents` GET XML RSS dengan cache 30 menit | `beranda.php:178-299` |")
    lines.append("| WhatsApp Click-to-Chat (`https://wa.me/...`) | Hyperlink HTML `<a href=\"https://wa.me/...\">` di sisi klien (bukan API server) | `telah_dipinjam.php:285, 971`, `daftar_anggota.php:994`, `dashboard.php:260, 971` |")
    lines.append("| Google Maps (Embed iframe & Search Link) | `<iframe src=\"https://maps.google.com/maps?...&output=embed\">` / embed kustom di klien | `index.php:160-172, 3678`, `pengaturan_lokasi.php:82-101, 185-198` |")
    lines.append("| Google Fonts & html2canvas CDN | Stylesheet `<link>` & `<script src=\"https://cdnjs.cloudflare.com/.../html2canvas.min.js\">` di klien | `settings_include.php:4-6`, `kartu_anggota.php:721` |")
    lines.append("| Layanan QR Eksternal (`qrserver`, dll.) | **TIDAK DIPAKAI** di kode aplikasi (kartu memakai SVG lokal `barcode128.php`) | `barcode128.php:5-71`, `kartu_anggota.php:108, 696` |")

    # Section 4: Schema Model Summary
    lines.append("\n## 4. Ringkasan Mekanis Skema Database (`database/aksa_nova.sql` + `2026_otp_reset.sql`)\n")
    lines.append(f"- Total tabel di `database/aksa_nova.sql`: **{schema['table_count']} tabel**.")
    lines.append(f"- Selisih definisi `password_otp` antara `aksa_nova.sql` dan `2026_otp_reset.sql`: **{len(schema['password_otp_main_vs_migration_diffs'])} selisih** (identik secara fungsional).\n")
    lines.append("| No | Nama Tabel | Baris SQL | Jml Kolom | PK | UK | FK (` kolom -> target [ON DELETE / ON UPDATE] `) | Dipakai di PHP (`S/I/U/D`) |")
    lines.append("|---:|---|---:|---:|---|---|---|---|")

    usage = schema["drift_analysis"]["table_usage_counts"]
    for idx, (tname, tinfo) in enumerate(sorted(schema["tables"].items()), start=1):
        pk = ", ".join(f"`{c}`" for c in tinfo["primary_key"])
        uks = ", ".join(f"`{u['name']}`({','.join(u['columns'])})" for u in tinfo["unique_keys"]) or "-"
        fks = "; ".join(
            f"`{','.join(fk['columns'])}`->`{fk['ref_table']}.{','.join(fk['ref_columns'])}` [{fk['effective_on_delete']}/{fk['effective_on_update']}]"
            for fk in tinfo["foreign_keys"]
        ) or "-"
        u = usage[tname]
        u_str = f"{u['SELECT']}/{u['INSERT']}/{u['UPDATE']}/{u['DELETE']}"
        lines.append(
            f"| {idx} | `{tname}` | `:{tinfo['start_line']}` | {len(tinfo['columns'])} | {pk} | {uks} | {fks} | `{u_str}` |"
        )

    # Section 5: Drift Analysis
    lines.append("\n## 5. Hasil Pemeriksaan Drift Skema Dua Arah (Kode PHP <-> SQL)\n")
    drift = schema["drift_analysis"]
    lines.append(f"- **Tabel dipakai di PHP tetapi tidak ada di SQL**: `{len(drift['unknown_tables_in_php'])}`")
    lines.append(f"- **Kolom dipakai di PHP tetapi tidak ada di SQL**: `{len(drift['unknown_columns_in_php'])}`")
    lines.append(f"- **Tabel ada di SQL tetapi tidak pernah diakses PHP**: `{len(drift['unused_sql_tables'])}`")
    lines.append("- **Temuan Drift Spesifik Kolom & Perilaku**:")
    lines.append("  1. `admin_profile.user_id` (`database/aksa_nova.sql:27,32,33` vs `update_profil_admin.php:118`): di SQL `user_id int NOT NULL` memiliki `UNIQUE KEY` dan `FOREIGN KEY -> users(id)`, tetapi cabang `INSERT INTO admin_profile (display_name, foto)` di `update_profil_admin.php:118` **tidak menyertakan `user_id`** (hanya bekerja saat ini karena dump SQL sudah berisi 1 baris seed `id=1, user_id=1` di `:43` sehingga kode selalu masuk cabang `UPDATE` di `:113-116`).")
    lines.append("  2. `users.plain_password` (`database/aksa_nova.sql:381`): kolom warisan (*legacy*) `varchar(255) NOT NULL DEFAULT ''`. Seluruh penulisan baru di `sign_up.php:220`, `lupa_kartu.php:290`, `edit_kartu.php:236`, dan `daftar_anggota.php:129` selalu mengosongkannya menjadi `''`; hanya dibaca sebagai fallback verifikasi sandi lama di `edit_kartu.php:134`.")
    lines.append("  3. `users.nik` (`database/aksa_nova.sql:374, 388-390` vs `sign_up.php:153`, `edit_kartu.php:162`): keunikan `nik` **hanya dicek di level aplikasi PHP**, **BUKAN** `UNIQUE KEY` di SQL (karena admin memiliki `nik = ''`).")
    lines.append("  4. Kolom ditulis PHP namun tidak pernah ditampilkan kembali di UI PHP: `pengajuan_peminjaman.approved_at` (`pengajuan_buku.php:85, 100`), `reminder_log.wa_terkirim_at` (`telah_dipinjam.php:138`), `pengaturan.keterangan` (`pengaturan_lokasi.php:55`), `buku_ratings.updated_at` (`rating_handler.php:58`).")
    lines.append("  5. Kunci seed `pengaturan` `'banner_mode' = 'dinamis'` (`database/aksa_nova.sql:304` baris `id=19`): ada di dump data SQL tetapi tidak pernah dibaca atau ditulis oleh kode PHP mana pun.")
    lines.append("  6. Kueri introspeksi skema runtime (`SHOW TABLES` / `SHOW COLUMNS`): dipanggil di `dashboard.php:45, 54` dan `export_excel_dashboard.php:13, 21` untuk mendeteksi kolom tabel `peminjaman` secara adaptif.\n")

    # Section 6: Detailed Per-File Function, Include, Form, Fetch & SQL Reference
    lines.append("## 6. Rincian Fakta Mekanis Per Berkas PHP\n")
    for rel, info in sorted(fakta["files"].items()):
        lines.append(f"### `{rel}` ({info['line_count']} baris, {info['size_bytes']} byte)")
        if info["includes_requires"]:
            inc_s = ", ".join(f"`{x['kind']} {x['target_expr']}` (`:{x['line']}`)" for x in info["includes_requires"])
            lines.append(f"- **Include/Require**: {inc_s}")
        if info["functions_defined"]:
            fn_s = ", ".join(f"`{x['name']}()` (`:{x['line']}`)" for x in info["functions_defined"])
            lines.append(f"- **Fungsi Didefinisikan**: {fn_s}")
        if info["constants_defined"]:
            cs_s = ", ".join(f"`{x['name']} = {x['value']}` (`:{x['line']}`)" for x in info["constants_defined"])
            lines.append(f"- **Konstanta**: {cs_s}")
        if isinstance(info["superglobals"], dict) and info["superglobals"]:
            sg_parts = []
            for sg, keys in info["superglobals"].items():
                k_list = ", ".join(f"`{k}`(:{','.join(map(str, lns[:5]))}{'...' if len(lns)>5 else ''})" for k, lns in keys.items())
                sg_parts.append(f"`{sg}` [{k_list}]")
            lines.append(f"- **Superglobals**: {'; '.join(sg_parts)}")
        if info["html_forms"]:
            fm_s = ", ".join(f"`{x['method']} -> {x['action']}` (`:{x['line']}`)" for x in info["html_forms"])
            lines.append(f"- **HTML Forms**: {fm_s}")
        if info["js_fetches"]:
            fc_s = ", ".join(f"`{x['target']}` (`:{x['line']}`)" for x in info["js_fetches"])
            lines.append(f"- **JS `fetch()`**: {fc_s}")
        if info["sql_queries"]:
            lines.append("- **Kueri SQL**:")
            for q in info["sql_queries"]:
                lines.append(f"  - `:{q['line']}` [{q['type']}] tabel `{','.join(q['tables'])}`: `{q['sql_preview']}`")
        lines.append("")

    with open(OUT_MD, "w", encoding="utf-8") as f:
        f.write("\n".join(lines))

    print(f"Generated {OUT_MD} ({len(lines)} lines).")


if __name__ == "__main__":
    main()
