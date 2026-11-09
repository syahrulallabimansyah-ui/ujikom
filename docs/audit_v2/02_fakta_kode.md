# 02 — Peta Fakta Kode & Skema Mekanis (`file:baris`)

> Dokumen ini dibangkitkan secara mekanis dari `02_fakta_kode.json` (tokenizer PHP `01_php_fact_extractor.php`) dan `schema_model.json` (`02_schema_and_drift.py`), dilengkapi verifikasi guard dan konstanta bisnis baris-demi-baris.

## 1. Matriks 44 Berkas PHP Aplikasi, Guard Akses, Kueri, & Status CSRF

| No | Berkas PHP | Ukuran (B) | Baris | Peran | Guard Akses & Redirect (`file:baris`) | Jml SQL | POST Handler? | `csrf_verify()` |
|---:|---|---:|---:|---|---|---:|:---:|:---:|
| 1 | `api_cek_pengajuan_admin.php` | 2,270 | 71 | Endpoint AJAX JSON Polling Pengajuan Baru | Admin (!user_id atau !=admin -> HTTP 403 JSON) (`api_cek_pengajuan_admin.php:13-17`) | 2 | Tidak | - |
| 2 | `barcode128.php` | 3,216 | 73 | Library Generator SVG Barcode Code 128 Subset B | Include library (tanpa guard langsung) (`barcode128.php:5`) | 0 | Tidak | - |
| 3 | `beranda.php` | 115,443 | 2,745 | Halaman Katalog Utama + AJAX Modal Host | Tamu & Member approved+active (Admin -> dashboard.php:6-9; Member non-approved/frozen -> destroy+sign_in.php:16-23) (`beranda.php:6-23`) | 15 | Tidak | - |
| 4 | `buku_detail.php` | 5,565 | 131 | Handler AJAX JSON Detail Buku | Publik / Tamu / Member / Admin (tanpa blokir role) (`buku_detail.php:24`) | 7 | Tidak | - |
| 5 | `buku_simpan.php` | 65,716 | 1,199 | Halaman Koleksi Buku Disimpan + Fragmen AJAX (?ajax=1) | Member approved+active (Tamu -> sign_in.php:5-8; Admin -> dashboard.php:14-20; frozen/non-approved -> sign_in.php:23-29) (`buku_simpan.php:5-29`) | 6 | Tidak | - |
| 6 | `cari_isbn.php` | 19,891 | 425 | Endpoint AJAX JSON Pencarian Metadata & Unduh Sampul via ISBN | Admin (!user_id atau !=admin -> HTTP 403 JSON) (`cari_isbn.php:31-34`) | 1 | Tidak | - |
| 7 | `config.php` | 3,512 | 102 | Konfigurasi Timezone, Cookie Sesi, API Key Google Books, Helper CSRF & mask_id | Include konfigurasi inti (`config.php:1-102`) | 0 | Tidak | - |
| 8 | `config_mail.example.php` | 1,392 | 28 | Templat Contoh Konfigurasi SMTP & OTP_SECRET | Fallback include kredensial email/OTP (`config_mail.example.php:1-28`) | 0 | Tidak | - |
| 9 | `config_mail.php` | 941 | 23 | Konfigurasi Aktif SMTP Gmail & OTP_SECRET | Include kredensial email/OTP (`config_mail.php:1-23`) | 0 | Tidak | - |
| 10 | `cron_cek_terlambat.php` | 2,453 | 60 | Skrip CLI Harian Pencatat Keterlambatan ke reminder_log | Hanya CLI (PHP_SAPI !== 'cli' -> HTTP 403) (`cron_cek_terlambat.php:22-25`) | 2 | Tidak | - |
| 11 | `daftar_anggota.php` | 56,761 | 1,264 | Halaman Admin Kelola Anggota + Fragmen AJAX (?ajax=1) | Admin (!user_id atau !=admin -> sign_in.php) (`daftar_anggota.php:5-8`) | 17 | Ya | ADA (`:51`) |
| 12 | `daftar_buku.php` | 85,009 | 1,608 | Halaman Daftar/Cari Buku + Fragmen AJAX (?ajax=1) | Tamu & Member approved+active (Admin -> dashboard.php:6-9; Member non-approved/frozen -> destroy+sign_in.php:16-24) (`daftar_buku.php:6-24`) | 10 | Tidak | - |
| 13 | `dashboard.php` | 52,575 | 1,060 | Halaman Dashboard Statistik & Rekap Bulanan Admin | Admin (!user_id atau !=admin -> sign_in.php) (`dashboard.php:5-8`) | 18 | Tidak | - |
| 14 | `dashboard_user.php` | 63,572 | 1,251 | Halaman Dashboard Anggota ('Peminjaman Saya') | Member approved+active (!user_id -> sign_in.php:5-8; admin -> dashboard.php:10-13; non-approved/frozen -> sign_in.php:24-36) (`dashboard_user.php:5-36`) | 9 | Tidak | - |
| 15 | `db.php` | 616 | 23 | Koneksi MySQLi utf8mb4 + SET time_zone '+07:00' | Include koneksi database (`db.php:1-23`) | 0 | Tidak | - |
| 16 | `edit_kartu.php` | 50,865 | 1,029 | Halaman Edit Profil & Ganti Sandi Anggota | Member login (!user_id -> sign_in.php:12-15; !=member -> dashboard.php:16-19; pending/rejected/frozen diblokir form:43-49) (`edit_kartu.php:12-49`) | 6 | Ya | ADA (`:97`) |
| 17 | `export_excel_dashboard.php` | 17,895 | 318 | Endpoint Unduh Laporan SpreadsheetML XML (.xls) | Admin (!user_id atau !=admin -> HTTP 403) (`export_excel_dashboard.php:4-7`) | 13 | Tidak | - |
| 18 | `ganti_sandi_admin.php` | 2,618 | 70 | Handler POST Ganti Sandi Admin (endpoint mandiri/warisan) | Admin (!user_id atau !=admin -> sign_in.php) (`ganti_sandi_admin.php:14-17`) | 2 | Ya | **TIDAK ADA** |
| 19 | `halaman_admin.php` | 81,400 | 1,855 | Halaman Admin CRUD Katalog Buku + Fragmen AJAX (?ajax=1) | Admin (!user_id atau !=admin -> sign_in.php) (`halaman_admin.php:6-9`) | 12 | Ya | ADA (`:73`) |
| 20 | `includes/mailer.php` | 5,205 | 140 | Wrapper PHPMailer SMTP, Masking Log Error, & Rollback OTP Gagal Kirim | Include library SMTP (`includes/mailer.php:1-140`) | 2 | Tidak | - |
| 21 | `includes/otp.php` | 14,068 | 361 | Library Pembuatan, Rate-Limit, Pengiriman, & Verifikasi OTP 6 Digit | Include library OTP (`includes/otp.php:1-361`) | 8 | Tidak | - |
| 22 | `index.php` | 172,988 | 4,230 | Halaman Publik (Landing) | Publik (tanpa session_start) (`-`) | 6 | Tidak | - |
| 23 | `kartu_anggota.php` | 29,030 | 815 | Halaman Pratinjau & Unduh PNG Kartu Anggota Digital (Barcode Code 128) | Admin (?id=mask_id), Sesi kartu_data, atau Member login (lainnya -> sign_up.php) (`kartu_anggota.php:8-97`) | 3 | Tidak | - |
| 24 | `kartu_clear.php` | 1,103 | 27 | Endpoint Beacon Pembersih $_SESSION['kartu_data'] + Auto-Login | Pemilik $_SESSION['kartu_data']['id'] approved & !frozen (`kartu_clear.php:5-26`) | 1 | Tidak | - |
| 25 | `kartu_masuk.php` | 1,129 | 29 | Endpoint Penyelesaian Penerbitan Kartu & Auto-Login | Pemilik $_SESSION['kartu_data']['id'] approved & !frozen -> beranda.php (`kartu_masuk.php:5-28`) | 1 | Tidak | - |
| 26 | `kelola_banner.php` | 49,959 | 1,023 | Halaman Admin CRUD & Urutan Banner Beranda | Admin (!user_id atau !=admin -> sign_in.php) (`kelola_banner.php:5-8`) | 20 | Ya | ADA (`:55`) |
| 27 | `like_handler.php` | 1,573 | 48 | Handler AJAX JSON Toggle Suka & Simpan | Member login (Tamu JSON error:7-10; Admin JSON error:13-16; tidak cek DB status/card_status) (`like_handler.php:7-16`) | 4 | Ya | **TIDAK ADA** |
| 28 | `logout.php` | 5,117 | 160 | Halaman Konfirmasi & Eksekusi Logout | User login (!isset user_id -> sign_in.php) (`logout.php:9-12`) | 0 | Ya | **TIDAK ADA** |
| 29 | `lupa_kartu.php` | 59,092 | 1,516 | Halaman Wizard 3 Langkah Pemulihan Akun & Kartu via OTP Email | Publik (wizard berbasis $_SESSION['lupa_kartu']['step']) (`lupa_kartu.php:20-23`) | 5 | Ya | ADA (`:35`) |
| 30 | `modal_profil_admin.php` | 14,649 | 300 | Include Modal Edit Profil & Sandi Admin (+ muat notifikasi_pengajuan_admin) | Include halaman Admin (`modal_profil_admin.php:1-17`) | 1 | Tidak | - |
| 31 | `notifikasi_pengajuan_admin.php` | 26,993 | 833 | Include UI Pop-up Realtime & Chime WebAudio Pengajuan Baru | Include khusus Admin (bukan admin -> return) (`notifikasi_pengajuan_admin.php:15-17`) | 0 | Tidak | - |
| 32 | `pengajuan_buku.php` | 60,584 | 1,169 | Halaman Admin Verifikasi Pengajuan Peminjaman | Admin (!user_id atau !=admin -> sign_in.php) (`pengajuan_buku.php:6-9`) | 17 | Ya | ADA (`:43`) |
| 33 | `pengajuan_peminjaman.php` | 67,609 | 1,272 | Halaman Form Pengajuan Peminjaman Online + Upload Kartu | Member approved+active (!user_id -> sign_in.php?redirect=:9-13; admin -> dashboard.php:18-21; non-approved/frozen -> sign_in.php:24-35) (`pengajuan_peminjaman.php:9-35`) | 7 | Ya | ADA (`:83`) |
| 34 | `pengaturan_denda.php` | 43,023 | 913 | Halaman Admin Konfigurasi Tarif/Toleransi Denda & Pelunasan | Admin (!user_id atau !=admin -> sign_in.php) (`pengaturan_denda.php:6-9`) | 12 | Ya | ADA (`:66`) |
| 35 | `pengaturan_lokasi.php` | 107,665 | 2,529 | Halaman Admin Konfigurasi Lokasi Peta, Kontak, & Jam Layanan | Admin (!user_id atau !=admin -> sign_in.php) (`pengaturan_lokasi.php:9-12`) | 7 | Ya | ADA (`:72`) |
| 36 | `pengaturan_musik.php` | 46,071 | 1,079 | Halaman Admin Upload & Konfigurasi Musik Latar | Admin (!user_id atau !=admin -> sign_in.php) (`pengaturan_musik.php:6-9`) | 8 | Ya | **TIDAK ADA** |
| 37 | `pengaturan_panel.php` | 29,493 | 633 | Include Panel Slide-in Tema/Gaya UI/Font/Mode Lite (localStorage) | Include terbuka (client-side) (`pengaturan_panel.php:1-5`) | 0 | Tidak | - |
| 38 | `pinjam_buku.php` | 52,130 | 1,182 | Halaman Admin Input Peminjaman Langsung + Fragmen AJAX (?ajax=1) | Admin (!user_id atau !=admin -> sign_in.php) (`pinjam_buku.php:9-12`) | 12 | Ya | ADA (`:60`) |
| 39 | `rating_handler.php` | 3,084 | 77 | Handler AJAX JSON Ambil/Beri Rating 1-5 | Member login (Tamu HTTP 403:6-10; Admin HTTP 403:13-17; tidak cek DB status/card_status) (`rating_handler.php:6-17`) | 6 | Ya | **TIDAK ADA** |
| 40 | `settings_include.php` | 114,816 | 3,222 | Include Global <head>: CSS Variabel Tema, AksaDialog, AksaAudio, Sidebar JS | Include terbuka (client-side) (`settings_include.php:1-3`) | 0 | Tidak | - |
| 41 | `sign_in.php` | 29,697 | 860 | Halaman Login Terpadu Member & Admin | Tamu belum login (Sudah login -> dashboard.php / safe_redirect / beranda.php) (`sign_in.php:17-24`) | 1 | Ya | ADA (`:30`) |
| 42 | `sign_up.php` | 66,410 | 1,760 | Halaman Registrasi Mandiri Siswa/Guru | Tamu belum login (Sudah login -> dashboard.php / beranda.php) (`sign_up.php:7-10`) | 5 | Ya | ADA (`:37`) |
| 43 | `telah_dipinjam.php` | 64,940 | 1,263 | Halaman Admin Sirkulasi Pengembalian, Denda, & Pengingat WA + AJAX (?ajax=1) | Admin (!user_id atau !=admin -> sign_in.php) (`telah_dipinjam.php:8-11`) | 14 | Ya | ADA (`:78`) |
| 44 | `update_profil_admin.php` | 5,770 | 125 | Handler POST Simpan Profil & Sandi Admin | Admin (!user_id atau !=admin -> sign_in.php) (`update_profil_admin.php:5-8`) | 7 | Ya | ADA (`:23`) |

### Catatan Mekanis Endpoint pembaca `$_POST` tanpa `csrf_verify()`

1. `like_handler.php` (`like_handler.php:21-42`): memproses `POST` toggle `buku_likes` / `buku_favorites` tanpa `csrf_verify()`.
2. `rating_handler.php` (`rating_handler.php:45-61`): memproses `POST` simpan/update `buku_ratings` tanpa `csrf_verify()`.
3. `pengaturan_musik.php` (`pengaturan_musik.php:62-247`): memproses seluruh `POST` upload/hapus/toggle musik admin tanpa `csrf_verify()` maupun `csrf_field()`.
4. `ganti_sandi_admin.php` (`ganti_sandi_admin.php:19-69`): endpoint mandiri ganti sandi admin tanpa `csrf_verify()` (saat ini UI `modal_profil_admin.php:131` memakai `update_profil_admin.php` yang sudah memanggil `csrf_verify()` di `:23`).
5. `logout.php` (`logout.php:14-37, 152`): form merender `csrf_field()` di `:152`, namun pemroses PHP tidak memanggil `csrf_verify()` dan juga menerima `GET ?confirm=1` (`:16`).
6. `sign_in.php` memiliki `csrf_verify()` (`sign_in.php:30`), tetapi **tidak memiliki rate-limiting percobaan login**.

## 2. Tabel Konstanta Bisnis, Angka Batas, & Rumus di Kode

| Aspek / Aturan | Nilai Aktual di Kode | Lokasi Bukti (`path:baris`) |
|---|---|---|
| Zona waktu PHP & MySQL | `'Asia/Jakarta' / '+07:00'` | `config.php:10, db.php:21` |
| Cookie sesi keamanan | `httponly=1, use_only_cookies=1, samesite=Lax (tanpa cookie_secure)` | `config.php:14-16` |
| Panjang token CSRF | `32 byte random_bytes -> 64 karakter hex` | `config.php:36` |
| Obfuskasi ID URL (mask_id) | `XOR 0x5A7B3C1D + 4 hex md5('aksa_salt_'.id) + base64url` | `config.php:71-74, 94-99` |
| Domain email registrasi & edit profil | `@student.smkn1rongga.sch.id, @smkn1rongga.sch.id, @gmail.com` | `sign_up.php:80-84, edit_kartu.php:116-120` |
| Format NIS Siswa / NIP Guru | `Siswa: /^[0-9]{3,20}$/; Guru: panjang >= 3 karakter (HTML maks 30)` | `sign_up.php:58-68, edit_kartu.php:108-112` |
| Format Nomor HP | `sign_up: wajib /^[\d+\-\s]{8,20}$/; edit_kartu: opsional /^[\d+\-\s]{6,20}$/` | `sign_up.php:71-75, edit_kartu.php:125-127` |
| Syarat kata sandi baru (Member & Admin) | `Min 8 karakter, wajib ada huruf /[A-Za-z]/ dan angka /[0-9]/` | `sign_up.php:91-94, lupa_kartu.php:279-283, edit_kartu.php:137-141, update_profil_admin.php:43-46` |
| Status awal registrasi mandiri | `role='member', status='approved', card_status='active' (otomatis aktif tanpa tunggu admin)` | `sign_up.php:213-222, database/aksa_nova.sql:385` |
| Format Nomor Anggota | `'AN-' . date('Y') . '-' . 5 digit acak (00001..99999)` | `sign_up.php:200` |
| Masa berlaku kartu anggota | `Guru: 'Selama Bertugas'; Siswa: 's/d ' . (tahun_daftar + 3)` | `kartu_anggota.php:111-118, dashboard_user.php:1097-1105` |
| Jenis kode pada Kartu Anggota | `Barcode Code 128 Subset B SVG (barcode128.php), BUKAN QR Code` | `kartu_anggota.php:108, 696; barcode128.php:5-71` |
| Resolusi ekspor PNG Kartu Anggota | `Kanvas 428x270 px x scale 2.362 = ~1011x638 px (300 DPI CR80)` | `kartu_anggota.php:721-750` |
| Umur file foto sementara registrasi | `temp_* dihapus otomatis bila > 7200 detik (2 jam)` | `sign_up.php:30` |
| Panjang & algoritma hash OTP | `6 digit ('%06d', 0..999999), HMAC-SHA256 dengan OTP_SECRET, verifikasi hash_equals` | `includes/otp.php:113-116, 317-319` |
| Masa berlaku kode OTP | `600 detik (10 menit)` | `includes/otp.php:119` |
| Jeda kirim ulang OTP (cooldown) | `60 detik per user` | `includes/otp.php:66, lupa_kartu.php:214` |
| Batas permintaan OTP (rate limit) | `Maks 5 kali/jam/user DAN maks 10 kali/jam/IP` | `includes/otp.php:41, 77` |
| Batas salah input OTP | `Maks 5 percobaan per kode OTP (attempts >= 5 -> hangus/burned)` | `includes/otp.php:308, 345` |
| Pembersihan otomatis OTP kedaluwarsa | `DELETE baris expires_at < NOW() - INTERVAL 1 DAY` | `includes/otp.php:102` |
| Kapan kartu dibekukan (card_status='frozen') | `SETELAH OTP berhasil diverifikasi di Langkah 2 (bukan saat request OTP)` | `lupa_kartu.php:185-190` |
| Batas waktu sesi Langkah 3 (set sandi baru) | `600 detik (10 menit); jika lewat, card_status kembali 'active'` | `lupa_kartu.php:262-274` |
| Timeout koneksi SMTP PHPMailer | `10 detik; gagal kirim otomatis rollback DELETE baris OTP terbaru` | `includes/mailer.php:74, 119-127` |
| Syarat OTP_DEV_MODE aktif | `OTP_DEV_MODE === true DAN akses dari 127.0.0.1 / ::1 / localhost / CLI -> tulis storage/otp_dev.log` | `includes/otp.php:145-172` |
| Batas upload foto anggota, sampul buku, kartu pengajuan, profil admin | `Maks 5 MB (5 * 1024 * 1024 byte)` | `sign_up.php:108, edit_kartu.php:196, pengajuan_peminjaman.php:155, halaman_admin.php:45, update_profil_admin.php:96` |
| Batas upload banner beranda | `Gambar (jpg/jpeg/png/webp/gif): maks 5 MB; Video (mp4/webm): maks 20 MB` | `kelola_banner.php:40-43` |
| Batas upload musik latar | `Audio (mp3/ogg/wav/m4a): maks 15 MB` | `pengaturan_musik.php:138, 142` |
| Rentang nilai rating buku | `Integer 1 s/d 5 (tanpa kolom ulasan teks)` | `rating_handler.php:48-50` |
| Kuota pengajuan peminjaman online | `1 s/d 40 eksemplar per pengajuan dan <= stok tersedia (tanpa bedakan Siswa vs Guru di kode)` | `pengajuan_peminjaman.php:89, 128-134` |
| Interval polling notifikasi pengajuan admin | `4000 ms (4 detik) ke api_cek_pengajuan_admin.php (initial delay 800 ms)` | `notifikasi_pengajuan_admin.php:575, 823` |
| Rumus hari terlambat & denda | `hari = DateTimeImmutable::diff()->days; hari_denda = max(0, hari - grace_period); denda = denda_aktif ? hari_denda * denda_per_hari : 0` | `telah_dipinjam.php:55-69, dashboard_user.php:86-91` |
| Nilai pengaturan denda pada dump SQL saat ini | `denda_aktif='1', denda_per_hari='1500', denda_grace_period='2' (default fallback di kode: 1000 / 0; di index.php:80 fallback 5000)` | `database/aksa_nova.sql:304, telah_dipinjam.php:50-52, index.php:80-81` |
| Cache RSS Berita (detikcom & CNN Indonesia) | `9 berita selang-seling, TTL 1800 detik (30 menit) di cache/berita_cache_v2.json, timeout HTTP 4 detik` | `beranda.php:178-195, 265-298` |
| Paginasi katalog daftar_buku.php | `10 buku per halaman (ORDER BY id ASC)` | `daftar_buku.php:34, 77` |
| Pencarian ISBN (cari_isbn.php) | `10 atau 13 digit; 4 tahap fallback (Google Books -> OL Books -> OL Search -> OL Works); timeout 8s (sampul 10s, min 500 byte)` | `cari_isbn.php:66, 145, 189, 208, 237-371` |
| Format ekspor laporan Dashboard | `SpreadsheetML XML (Excel 2003 XML) berekstensi .xls dengan 3 Worksheet` | `export_excel_dashboard.php:143-154, 202, 245, 295` |

## 3. Peta Pemanggilan Layanan Eksternal (Bukan Aktor)

| Layanan Eksternal | Mekanisme Pemanggilan | Berkas & Baris Bukti |
|---|---|---|
| Google Books API v1 | Server-side cURL / `file_get_contents` GET `https://www.googleapis.com/books/v1/volumes?q=isbn:...` | `cari_isbn.php:237-258`, `config.php:23` |
| Open Library API (Books, Search, Editions/Works, Covers) | Server-side cURL / `file_get_contents` GET `https://openlibrary.org/...` & `covers.openlibrary.org` | `cari_isbn.php:267-371` |
| SMTP Email (Gmail `smtp.gmail.com:587` STARTTLS) | Server-side `PHPMailer` di `kirim_email()` | `includes/mailer.php:52-85`, `includes/otp.php:238`, `config_mail.php:10-16` |
| RSS Berita (`detikcom` & `CNN Indonesia`) | Server-side cURL / `file_get_contents` GET XML RSS dengan cache 30 menit | `beranda.php:178-299` |
| WhatsApp Click-to-Chat (`https://wa.me/...`) | Hyperlink HTML `<a href="https://wa.me/...">` di sisi klien (bukan API server) | `telah_dipinjam.php:285, 971`, `daftar_anggota.php:994`, `dashboard.php:260, 971` |
| Google Maps (Embed iframe & Search Link) | `<iframe src="https://maps.google.com/maps?...&output=embed">` / embed kustom di klien | `index.php:160-172, 3678`, `pengaturan_lokasi.php:82-101, 185-198` |
| Google Fonts & html2canvas CDN | Stylesheet `<link>` & `<script src="https://cdnjs.cloudflare.com/.../html2canvas.min.js">` di klien | `settings_include.php:4-6`, `kartu_anggota.php:721` |
| Layanan QR Eksternal (`qrserver`, dll.) | **TIDAK DIPAKAI** di kode aplikasi (kartu memakai SVG lokal `barcode128.php`) | `barcode128.php:5-71`, `kartu_anggota.php:108, 696` |

## 4. Ringkasan Mekanis Skema Database (`database/aksa_nova.sql` + `2026_otp_reset.sql`)

- Total tabel di `database/aksa_nova.sql`: **12 tabel**.
- Selisih definisi `password_otp` antara `aksa_nova.sql` dan `2026_otp_reset.sql`: **0 selisih** (identik secara fungsional).

| No | Nama Tabel | Baris SQL | Jml Kolom | PK | UK | FK (` kolom -> target [ON DELETE / ON UPDATE] `) | Dipakai di PHP (`S/I/U/D`) |
|---:|---|---:|---:|---|---|---|---|
| 1 | `admin_profile` | `:25` | 5 | `id` | `uq_admin_user`(user_id) | `user_id`->`users.id` [CASCADE/CASCADE] | `14/1/2/0` |
| 2 | `banner` | `:54` | 9 | `id` | - | - | `7/1/4/1` |
| 3 | `buku` | `:85` | 11 | `id` | - | - | `40/1/4/1` |
| 4 | `buku_favorites` | `:118` | 4 | `id` | `uniq_fav`(buku_id,user_id) | `buku_id`->`buku.id` [CASCADE/CASCADE]; `user_id`->`users.id` [CASCADE/CASCADE] | `11/1/0/1` |
| 5 | `buku_likes` | `:148` | 4 | `id` | `uniq_like`(buku_id,user_id) | `buku_id`->`buku.id` [CASCADE/CASCADE]; `user_id`->`users.id` [CASCADE/CASCADE] | `7/1/0/1` |
| 6 | `buku_ratings` | `:178` | 6 | `id` | `uq_user_buku`(user_id,buku_id) | `buku_id`->`buku.id` [CASCADE/CASCADE]; `user_id`->`users.id` [CASCADE/CASCADE] | `9/1/1/0` |
| 7 | `password_otp` | `:348` | 9 | `id` | - | `user_id`->`users.id` [CASCADE/RESTRICT] | `3/1/3/2` |
| 8 | `peminjaman` | `:209` | 12 | `id` | - | `buku_id`->`buku.id` [RESTRICT/CASCADE]; `user_id`->`users.id` [SET NULL/CASCADE] | `37/2/3/1` |
| 9 | `pengajuan_peminjaman` | `:247` | 13 | `id` | - | `buku_id`->`buku.id` [CASCADE/CASCADE]; `user_id`->`users.id` [CASCADE/CASCADE] | `29/1/2/2` |
| 10 | `pengaturan` | `:287` | 5 | `id` | `uq_kunci`(kunci) | - | `20/4/3/0` |
| 11 | `reminder_log` | `:315` | 7 | `id` | `uq_pinjam_tanggal`(peminjaman_id,tanggal) | `peminjaman_id`->`peminjaman.id` [CASCADE/CASCADE] | `1/2/0/0` |
| 12 | `users` | `:371` | 15 | `id` | `username`(username), `email`(email), `uq_no_anggota`(no_anggota) | - | `48/1/10/1` |

## 5. Hasil Pemeriksaan Drift Skema Dua Arah (Kode PHP <-> SQL)

- **Tabel dipakai di PHP tetapi tidak ada di SQL**: `0`
- **Kolom dipakai di PHP tetapi tidak ada di SQL**: `0`
- **Tabel ada di SQL tetapi tidak pernah diakses PHP**: `0`
- **Temuan Drift Spesifik Kolom & Perilaku**:
  1. `admin_profile.user_id` (`database/aksa_nova.sql:27,32,33` vs `update_profil_admin.php:118`): di SQL `user_id int NOT NULL` memiliki `UNIQUE KEY` dan `FOREIGN KEY -> users(id)`, tetapi cabang `INSERT INTO admin_profile (display_name, foto)` di `update_profil_admin.php:118` **tidak menyertakan `user_id`** (hanya bekerja saat ini karena dump SQL sudah berisi 1 baris seed `id=1, user_id=1` di `:43` sehingga kode selalu masuk cabang `UPDATE` di `:113-116`).
  2. `users.plain_password` (`database/aksa_nova.sql:381`): kolom warisan (*legacy*) `varchar(255) NOT NULL DEFAULT ''`. Seluruh penulisan baru di `sign_up.php:220`, `lupa_kartu.php:290`, `edit_kartu.php:236`, dan `daftar_anggota.php:129` selalu mengosongkannya menjadi `''`; hanya dibaca sebagai fallback verifikasi sandi lama di `edit_kartu.php:134`.
  3. `users.nik` (`database/aksa_nova.sql:374, 388-390` vs `sign_up.php:153`, `edit_kartu.php:162`): keunikan `nik` **hanya dicek di level aplikasi PHP**, **BUKAN** `UNIQUE KEY` di SQL (karena admin memiliki `nik = ''`).
  4. Kolom ditulis PHP namun tidak pernah ditampilkan kembali di UI PHP: `pengajuan_peminjaman.approved_at` (`pengajuan_buku.php:85, 100`), `reminder_log.wa_terkirim_at` (`telah_dipinjam.php:138`), `pengaturan.keterangan` (`pengaturan_lokasi.php:55`), `buku_ratings.updated_at` (`rating_handler.php:58`).
  5. Kunci seed `pengaturan` `'banner_mode' = 'dinamis'` (`database/aksa_nova.sql:304` baris `id=19`): ada di dump data SQL tetapi tidak pernah dibaca atau ditulis oleh kode PHP mana pun.
  6. Kueri introspeksi skema runtime (`SHOW TABLES` / `SHOW COLUMNS`): dipanggil di `dashboard.php:45, 54` dan `export_excel_dashboard.php:13, 21` untuk mendeteksi kolom tabel `peminjaman` secara adaptif.

## 6. Rincian Fakta Mekanis Per Berkas PHP

### `api_cek_pengajuan_admin.php` (71 baris, 2270 byte)
- **Include/Require**: `require_once 'db.php'` (`:19`)
- **Superglobals**: `$_SESSION` [`user_id`(:13), `role`(:13)]
- **Kueri SQL**:
  - `:25` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status = 'menunggu'`
  - `:32` [SELECT] tabel `pengajuan_peminjaman,buku,users`: `SELECT p.id, p.user_id, p.buku_id, p.nama_peminjam, p.total_buku, p.batas_kembali, p.waktu_pengambilan, p.catatan_pengambilan, p.created_at, b.judul, b.penulis, b.gambar, b.rak, u.nik, u.no_hp FROM pengajuan_peminjaman p LEFT JOIN buku b ON b.id = p.buku_id LE`

### `barcode128.php` (73 baris, 3216 byte)
- **Fungsi Didefinisikan**: `render_code128_svg()` (`:5`)

### `beranda.php` (2745 baris, 115443 byte)
- **Include/Require**: `require_once "db.php"` (`:11`), `require_once "settings_include.php"` (`:320`), `require_once "pengaturan_panel.php"` (`:2742`), `require_once __DIR__ . '/notifikasi_pengajuan_admin.php'` (`:2743`)
- **Fungsi Didefinisikan**: `berita_http_get()` (`:185`), `berita_img_url()` (`:209`), `berita_parse_feed()` (`:217`), `berita_ambil()` (`:264`), `berita_waktu()` (`:301`)
- **Superglobals**: `$_SESSION` [`role`(:6,27), `user_id`(:14,15), `user_name`(:24,26)]
- **JS `fetch()`**: `buku_detail.php?id=` (`:2474`), `like_handler.php` (`:2566`), `like_handler.php` (`:2584`), `rating_handler.php` (`:2728`)
- **Kueri SQL**:
  - `:17` [SELECT] tabel `users`: `SELECT status, card_status, full_name FROM users WHERE id =`
  - `:35` [SELECT] tabel `buku,buku_likes`: `SELECT b.*, COUNT(l.id) AS jumlah_like FROM buku b LEFT JOIN buku_likes l ON l.buku_id = b.id GROUP BY b.id ORDER BY jumlah_like DESC, b.id ASC LIMIT 5`
  - `:46` [SELECT] tabel `buku`: `SELECT * FROM buku ORDER BY created_at DESC, id DESC LIMIT 10`
  - `:50` [SELECT] tabel `buku`: `SELECT COUNT(*) AS total FROM buku`
  - `:57` [SELECT] tabel `buku`: `SELECT genre, COUNT(*) AS jumlah FROM buku WHERE genre IS NOT NULL AND genre != '' GROUP BY genre ORDER BY jumlah DESC LIMIT 6`
  - `:93` [SELECT] tabel `buku_ratings`: `SELECT buku_id, ROUND(AVG(rating),1) AS avg_r, COUNT(*) AS total FROM buku_ratings WHERE buku_id IN (`
  - `:105` [SELECT] tabel `banner`: `SELECT * FROM banner WHERE aktif = 1 ORDER BY urutan ASC, id ASC`
  - `:109` [SELECT] tabel `admin_profile`: `SELECT * FROM admin_profile LIMIT 1`
  - `:117` [SELECT] tabel `users`: `SELECT full_name, foto, no_anggota, nik FROM users WHERE id =`
  - `:125` [SELECT] tabel `buku,buku_favorites`: `SELECT b.* FROM buku b INNER JOIN buku_favorites f ON f.buku_id = b.id WHERE f.user_id =`
  - `:140` [SELECT] tabel `peminjaman`: `SELECT COUNT(*) AS total FROM peminjaman WHERE user_id =`
  - `:143` [SELECT] tabel `peminjaman`: `SELECT COUNT(*) AS total FROM peminjaman WHERE user_id =`
  - `:146` [SELECT] tabel `peminjaman`: `SELECT COUNT(*) AS total FROM peminjaman WHERE user_id =`
  - `:154` [SELECT] tabel `pengaturan`: `SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('musik_aktif','musik_file','musik_judul')`
  - `:166` [SELECT] tabel `buku`: `SELECT id, judul, penulis, genre, stok, gambar FROM buku WHERE stok > 0 ORDER BY RAND() LIMIT 20`

### `buku_detail.php` (131 baris, 5565 byte)
- **Include/Require**: `require_once 'db.php'` (`:26`), `require_once 'config.php'` (`:27`)
- **Fungsi Didefinisikan**: `jsonResponse()` (`:7`), `fetchAll()` (`:43`), `fetchOne()` (`:59`)
- **Superglobals**: `$_GET` [`id`(:33)]; `$_SESSION` [`user_id`(:24,36)]
- **Kueri SQL**:
  - `:67` [SELECT] tabel `buku`: `SELECT * FROM buku WHERE id = ? LIMIT 1`
  - `:75` [SELECT] tabel `buku_likes`: `SELECT COUNT(*) AS total FROM buku_likes WHERE buku_id = ?`
  - `:82` [SELECT] tabel `buku_favorites`: `SELECT COUNT(*) AS total FROM buku_favorites WHERE buku_id = ?`
  - `:89` [SELECT] tabel `buku_ratings`: `SELECT AVG(rating) AS avg_r, COUNT(*) AS total FROM buku_ratings WHERE buku_id = ?`
  - `:102` [SELECT] tabel `buku_ratings`: `SELECT rating FROM buku_ratings WHERE user_id = ? AND buku_id = ? LIMIT 1`
  - `:113` [SELECT] tabel `buku_favorites`: `SELECT id FROM buku_favorites WHERE user_id = ? AND buku_id = ? LIMIT 1`
  - `:124` [SELECT] tabel `buku_likes`: `SELECT id FROM buku_likes WHERE user_id = ? AND buku_id = ? LIMIT 1`

### `buku_simpan.php` (1199 baris, 65716 byte)
- **Include/Require**: `require_once "db.php"` (`:10`), `require_once "settings_include.php"` (`:634`), `require_once "pengaturan_panel.php"` (`:1197`)
- **Fungsi Didefinisikan**: `getStatus()` (`:94`)
- **Superglobals**: `$_GET` [`q`(:34), `kategori`(:35), `ajax`(:36)]; `$_SESSION` [`user_id`(:5,22), `user_name`(:12), `role`(:13)]
- **HTML Forms**: `GET -> buku_simpan.php` (`:725`)
- **JS `fetch()`**: `buku_simpan.php?` (`:897`), `like_handler.php` (`:1005`), `buku_detail.php?id=` (`:1033`), `rating_handler.php` (`:1086`), `like_handler.php` (`:1098`)
- **Kueri SQL**:
  - `:23` [SELECT] tabel `users`: `SELECT status, card_status, full_name FROM users WHERE id =`
  - `:52` [SELECT] tabel `buku_favorites,buku`: `SELECT DISTINCT b.genre FROM buku_favorites f INNER JOIN buku b ON b.id = f.buku_id WHERE f.user_id =`
  - `:64` [SELECT] tabel `buku_favorites,buku`: `SELECT COUNT(*) AS total FROM buku_favorites f INNER JOIN buku b ON b.id = f.buku_id WHERE f.user_id =`
  - `:73` [SELECT] tabel `buku_favorites,buku`: `SELECT b.*, f.id AS fav_id FROM buku_favorites f INNER JOIN buku b ON b.id = f.buku_id WHERE f.user_id =`
  - `:85` [SELECT] tabel `buku_ratings`: `SELECT buku_id, ROUND(AVG(rating),1) AS avg_r, COUNT(*) AS total FROM buku_ratings WHERE buku_id IN (`
  - `:106` [SELECT] tabel `pengaturan`: `SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('musik_aktif','musik_file','musik_judul')`

### `cari_isbn.php` (425 baris, 19891 byte)
- **Include/Require**: `require_once "db.php"` (`:36`), `require_once __DIR__ . "/config.php"` (`:38`)
- **Fungsi Didefinisikan**: `kirimJson()` (`:22`), `httpGetJson()` (`:58`), `downloadCover()` (`:136`)
- **Konstanta**: `GOOGLE_BOOKS_API_KEY = [REDACTED_SECRET]` (`:41`), `ISBN_DEBUG = isset` (`:49`)
- **Superglobals**: `$_GET` [`debug`(:49), `isbn`(:204)]; `$_SESSION` [`user_id`(:31), `role`(:31)]
- **Kueri SQL**:
  - `:219` [SELECT] tabel `buku`: `SELECT id, judul, stok FROM buku WHERE isbn = '`

### `config.php` (102 baris, 3512 byte)
- **Fungsi Didefinisikan**: `csrf_token()` (`:31`), `csrf_field()` (`:46`), `csrf_verify()` (`:55`), `mask_id()` (`:68`), `unmask_id()` (`:82`)
- **Konstanta**: `GOOGLE_BOOKS_API_KEY = [REDACTED_SECRET]` (`:23`)
- **Superglobals**: `$_POST` [`csrf_token`(:59)]; `$_SESSION` [`csrf_token`(:35,36,38,60)]; `$_SERVER` [`HTTP_X_CSRF_TOKEN`(:59)]

### `config_mail.example.php` (28 baris, 1392 byte)
- **Konstanta**: `MAIL_HOST = 'smtp.gmail.com'` (`:12`), `MAIL_PORT = 587` (`:13`), `MAIL_ENCRYPTION = 'tls'` (`:14`), `MAIL_USERNAME = 'perpustakaan@smkn1rongga.sch.id'` (`:15`), `MAIL_PASSWORD = [REDACTED_SECRET]` (`:16`), `MAIL_FROM_EMAIL = 'perpustakaan@smkn1rongga.sch.id'` (`:17`), `MAIL_FROM_NAME = 'Perpustakaan AKSA NOVA'` (`:18`), `OTP_SECRET = [REDACTED_SECRET]` (`:21`), `OTP_DEV_MODE = false` (`:27`)

### `config_mail.php` (23 baris, 941 byte)
- **Konstanta**: `MAIL_HOST = 'smtp.gmail.com'` (`:10`), `MAIL_PORT = 587` (`:11`), `MAIL_ENCRYPTION = 'tls'` (`:12`), `MAIL_USERNAME = 'bimansyaharul@gmail.com'` (`:13`), `MAIL_PASSWORD = [REDACTED_SECRET]` (`:14`), `MAIL_FROM_EMAIL = 'bimansyaharul@gmail.com'` (`:15`), `MAIL_FROM_NAME = 'Perpustakaan AKSA NOVA'` (`:16`), `OTP_SECRET = [REDACTED_SECRET]` (`:19`), `OTP_DEV_MODE = false` (`:22`)

### `cron_cek_terlambat.php` (60 baris, 2453 byte)
- **Include/Require**: `require_once __DIR__ . '/db.php'` (`:27`)
- **Kueri SQL**:
  - `:38` [SELECT] tabel `peminjaman`: `SELECT id, batas_kembali FROM peminjaman WHERE status = 'dipinjam' AND batas_kembali < NOW()`
  - `:51` [INSERT INTO] tabel `reminder_log`: `INSERT INTO reminder_log (peminjaman_id, tanggal, terlambat_hari) VALUES (`

### `daftar_anggota.php` (1264 baris, 56761 byte)
- **Include/Require**: `require_once "db.php"` (`:10`), `require_once "config.php"` (`:11`), `require_once 'settings_include.php'` (`:226`), `require_once 'modal_profil_admin.php'` (`:1260`), `require_once 'pengaturan_panel.php'` (`:1261`)
- **Fungsi Didefinisikan**: `format_nomor_wa()` (`:22`)
- **Superglobals**: `$_GET` [`q`(:149), `ajax`(:150), `tab`(:153), `status`(:153), `tipe`(:153)]; `$_POST` [`action`(:50), `id`(:58,66,74,82,104), `custom_password`(:105)]; `$_SESSION` [`user_id`(:5), `role`(:5), `user_name`(:41)]; `$_SERVER` [`REQUEST_METHOD`(:51)]
- **HTML Forms**: `GET -> (self)` (`:874`), `POST -> (self)` (`:1021`), `POST -> daftar_anggota.php` (`:1047`)
- **JS `fetch()`**: `daftar_anggota.php?` (`:1216`)
- **Kueri SQL**:
  - `:13` [SELECT] tabel `users`: `SELECT COUNT(*) as c FROM users WHERE role='member' AND status='pending'`
  - `:15` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`
  - `:39` [SELECT] tabel `admin_profile`: `SELECT * FROM admin_profile LIMIT 1`
  - `:44` [SELECT] tabel `buku`: `SELECT COUNT(*) AS c FROM buku`
  - `:60` [UPDATE] tabel `users`: `UPDATE users SET status='approved' WHERE id=`
  - `:68` [UPDATE] tabel `users`: `UPDATE users SET card_status='active' WHERE id=`
  - `:76` [UPDATE] tabel `users`: `UPDATE users SET status='rejected' WHERE id=`
  - `:84` [SELECT] tabel `peminjaman`: `SELECT COUNT(*) AS c FROM peminjaman WHERE user_id=`
  - `:91` [SELECT] tabel `users`: `SELECT foto FROM users WHERE id=`
  - `:97` [DELETE FROM] tabel `users`: `DELETE FROM users WHERE id=`
  - `:107` [SELECT] tabel `users`: `SELECT full_name, username FROM users WHERE id=`
  - `:131` [UPDATE] tabel `users`: `UPDATE users SET password=?, plain_password=? WHERE id=? AND role='member'`
  - `:174` [SELECT] tabel `users`: `SELECT * FROM users`
  - `:181` [SELECT] tabel `users`: `SELECT COUNT(*) AS c FROM users WHERE role='member'`
  - `:185` [SELECT] tabel `users`: `SELECT COUNT(*) AS c FROM users WHERE role='member' AND LOWER(TRIM(kelas)) = 'guru'`
  - `:190` [SELECT] tabel `users`: `SELECT status, COUNT(*) AS c FROM users WHERE role='member' GROUP BY status`
  - `:798` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`

### `daftar_buku.php` (1608 baris, 85009 byte)
- **Include/Require**: `require_once "db.php"` (`:11`), `require_once "settings_include.php"` (`:824`), `require_once "pengaturan_panel.php"` (`:1604`), `require_once __DIR__ . '/notifikasi_pengajuan_admin.php'` (`:1605`)
- **Fungsi Didefinisikan**: `getStatus()` (`:121`)
- **Superglobals**: `$_GET` [`page`(:35), `q`(:39), `kategori`(:40), `genre`(:40), `ajax`(:41)]; `$_SESSION` [`role`(:6,28), `user_id`(:15,17,82), `user_name`(:25,27)]
- **HTML Forms**: `GET -> daftar_buku.php` (`:948`)
- **JS `fetch()`**: `buku_detail.php?id=` (`:1173`), `like_handler.php` (`:1295`), `daftar_buku.php?` (`:1358`), `rating_handler.php` (`:1497`), `like_handler.php` (`:1525`), `like_handler.php` (`:1548`)
- **Kueri SQL**:
  - `:18` [SELECT] tabel `users`: `SELECT status, card_status, full_name FROM users WHERE id =`
  - `:62` [SELECT] tabel `buku`: `SELECT DISTINCT genre FROM buku WHERE genre IS NOT NULL AND genre != '' ORDER BY genre ASC`
  - `:69` [SELECT] tabel `buku`: `SELECT COUNT(*) AS total FROM buku`
  - `:77` [SELECT] tabel `buku`: `SELECT * FROM buku`
  - `:93` [SELECT] tabel `buku_likes`: `SELECT buku_id, COUNT(*) AS total FROM buku_likes WHERE buku_id IN (`
  - `:97` [SELECT] tabel `buku_favorites`: `SELECT buku_id, COUNT(*) AS total FROM buku_favorites WHERE buku_id IN (`
  - `:102` [SELECT] tabel `buku_likes`: `SELECT buku_id FROM buku_likes WHERE user_id=`
  - `:106` [SELECT] tabel `buku_favorites`: `SELECT buku_id FROM buku_favorites WHERE user_id=`
  - `:112` [SELECT] tabel `buku_ratings`: `SELECT buku_id, ROUND(AVG(rating),1) AS avg_r, COUNT(*) AS total FROM buku_ratings WHERE buku_id IN (`
  - `:131` [SELECT] tabel `pengaturan`: `SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('musik_aktif','musik_file','musik_judul')`

### `dashboard.php` (1060 baris, 52575 byte)
- **Include/Require**: `require_once "db.php"` (`:10`), `require_once 'settings_include.php'` (`:269`), `require_once 'modal_profil_admin.php'` (`:1057`), `require_once 'pengaturan_panel.php'` (`:1058`)
- **Fungsi Didefinisikan**: `tabelAda()` (`:43`), `kolomTersedia()` (`:49`)
- **Superglobals**: `$_GET` [`profil_saved`(:30), `profil_err`(:33,34), `tahun`(:172)]; `$_SESSION` [`user_id`(:5), `role`(:5), `user_name`(:24)]
- **Kueri SQL**:
  - `:12` [SELECT] tabel `users`: `SELECT COUNT(*) as c FROM users WHERE role='member' AND status='pending'`
  - `:14` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`
  - `:22` [SELECT] tabel `admin_profile`: `SELECT * FROM admin_profile LIMIT 1`
  - `:45` [SHOW TABLES] tabel ``: `SHOW TABLES LIKE '`
  - `:54` [SHOW COLUMNS] tabel ``: `SHOW COLUMNS FROM ``
  - `:79` [SELECT] tabel `buku`: `SELECT COUNT(*) AS c, COALESCE(SUM(stok),0) AS s FROM buku`
  - `:84` [SELECT] tabel `users`: `SELECT status, COUNT(*) AS c FROM users WHERE role='member' GROUP BY status`
  - `:102` [SELECT] tabel `peminjaman`: `SELECT COUNT(*) AS c FROM peminjaman WHERE LOWER(`
  - `:105` [SELECT] tabel `peminjaman`: `SELECT COUNT(*) AS c FROM peminjaman WHERE`
  - `:111` [SELECT] tabel `peminjaman`: `SELECT COUNT(*) AS c FROM peminjaman WHERE LOWER(`
  - `:114` [SELECT] tabel `peminjaman`: `SELECT COUNT(*) AS c FROM peminjaman WHERE`
  - `:117` [SELECT] tabel `peminjaman`: `SELECT COUNT(*) AS c FROM peminjaman WHERE LOWER(`
  - `:122` [SELECT] tabel ``: `SELECT COUNT(*) AS c, COUNT(DISTINCT`
  - `:130` [SELECT] tabel `peminjaman,buku`: `SELECT b.judul, COUNT(*) AS jml FROM peminjaman p JOIN buku b ON b.id = p.`
  - `:150` [SELECT] tabel `peminjaman`: `SELECT status_denda, COUNT(*) AS c, COALESCE(SUM(denda),0) AS t FROM peminjaman WHERE denda > 0 GROUP BY status_denda`
  - `:183` [SELECT] tabel ``: `SELECT MONTH(`
  - `:225` [SELECT] tabel ``: `SELECT COUNT(DISTINCT`
  - `:771` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`

### `dashboard_user.php` (1251 baris, 63572 byte)
- **Include/Require**: `require_once "db.php"` (`:15`), `require_once "barcode128.php"` (`:158`), `require_once "settings_include.php"` (`:169`), `require_once "pengaturan_panel.php"` (`:1248`)
- **Superglobals**: `$_SESSION` [`user_id`(:5,17), `role`(:10), `user_name`(:18)]
- **JS `fetch()`**: `buku_detail.php?id=` (`:1205`)
- **Kueri SQL**:
  - `:24` [SELECT] tabel `users`: `SELECT * FROM users WHERE id = ?`
  - `:42` [SELECT] tabel `pengaturan`: `SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('denda_per_hari','denda_aktif','denda_grace_period')`
  - `:57` [SELECT] tabel `peminjaman`: `SELECT COUNT(*) AS total FROM peminjaman WHERE user_id =`
  - `:61` [SELECT] tabel `peminjaman`: `SELECT SUM(denda) AS denda_sisa FROM peminjaman WHERE user_id =`
  - `:72` [SELECT] tabel `peminjaman,buku`: `SELECT p.*, b.judul, b.penulis, b.gambar, b.genre, b.isbn FROM peminjaman p LEFT JOIN buku b ON b.id = p.buku_id WHERE p.user_id =`
  - `:109` [SELECT] tabel `peminjaman,buku`: `SELECT p.*, b.judul, b.penulis, b.gambar FROM peminjaman p LEFT JOIN buku b ON b.id = p.buku_id WHERE p.user_id =`
  - `:121` [SELECT] tabel `pengajuan_peminjaman,buku`: `SELECT p.*, b.judul, b.penulis, b.gambar FROM pengajuan_peminjaman p LEFT JOIN buku b ON b.id = p.buku_id WHERE p.user_id =`
  - `:134` [SELECT] tabel `buku,buku_favorites`: `SELECT b.* FROM buku b INNER JOIN buku_favorites f ON f.buku_id = b.id WHERE f.user_id =`
  - `:147` [SELECT] tabel `pengaturan`: `SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('musik_aktif','musik_file','musik_judul')`

### `db.php` (23 baris, 616 byte)
- **Include/Require**: `require_once __DIR__ . '/config.php'` (`:5`)

### `edit_kartu.php` (1029 baris, 50865 byte)
- **Include/Require**: `require_once "db.php"` (`:9`), `require_once "settings_include.php"` (`:313`), `require_once "pengaturan_panel.php"` (`:1027`)
- **Superglobals**: `$_POST` [`action`(:67), `full_name`(:69), `username`(:70), `nik`(:71), `no_hp`(:73), `email`(:74), `old_password`(:75), `new_password`(:76), `new_password_confirm`(:77)]; `$_FILES` [`foto`(:186,187,190,191,192)]; `$_SESSION` [`user_id`(:12,21), `role`(:16), `user_name`(:266,294), `username`(:267), `kartu_data`(:271)]; `$_SERVER` [`REQUEST_METHOD`(:67)]
- **HTML Forms**: `POST -> edit_kartu.php` (`:828`)
- **Kueri SQL**:
  - `:25` [SELECT] tabel `users`: `SELECT id, full_name, nik, kelas, no_hp, email, no_anggota, username, password, plain_password, foto, role, status, card_status FROM users WHERE id = ?`
  - `:150` [SELECT] tabel `users`: `SELECT id FROM users WHERE username = ? AND id <> ?`
  - `:162` [SELECT] tabel `users`: `SELECT id FROM users WHERE nik = ? AND id <> ?`
  - `:174` [SELECT] tabel `users`: `SELECT id FROM users WHERE email = ? AND id <> ?`
  - `:246` [UPDATE] tabel `users`: `UPDATE users SET`
  - `:297` [SELECT] tabel `pengaturan`: `SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('musik_aktif','musik_file','musik_judul')`

### `export_excel_dashboard.php` (318 baris, 17895 byte)
- **Include/Require**: `require_once 'db.php'` (`:9`)
- **Fungsi Didefinisikan**: `tabelAdaEx()` (`:11`), `kolomTersediaEx()` (`:16`), `xlsCell()` (`:114`), `xlsHeader()` (`:119`), `xlsTitle()` (`:123`), `xlsHL()` (`:128`), `xlsSec()` (`:132`), `xlsFoot()` (`:136`), `emptyRow()` (`:140`)
- **Superglobals**: `$_GET` [`tahun`(:41)]; `$_SESSION` [`user_id`(:4), `role`(:4)]
- **Kueri SQL**:
  - `:13` [SHOW TABLES] tabel ``: `SHOW TABLES LIKE '`
  - `:21` [SHOW COLUMNS] tabel ``: `SHOW COLUMNS FROM ``
  - `:51` [SELECT] tabel `buku`: `SELECT COUNT(*) AS c, COALESCE(SUM(stok),0) AS s FROM buku`
  - `:56` [SELECT] tabel `users`: `SELECT status, COUNT(*) AS c FROM users WHERE role='member' GROUP BY status`
  - `:63` [SELECT] tabel `peminjaman`: `SELECT COUNT(*) AS c FROM peminjaman WHERE LOWER(`
  - `:66` [SELECT] tabel `peminjaman`: `SELECT COUNT(*) AS c FROM peminjaman WHERE`
  - `:70` [SELECT] tabel `peminjaman`: `SELECT COUNT(*) AS c FROM peminjaman WHERE LOWER(`
  - `:73` [SELECT] tabel `peminjaman`: `SELECT COUNT(*) AS c FROM peminjaman WHERE LOWER(`
  - `:76` [SELECT] tabel ``: `SELECT COUNT(*) AS c, COUNT(DISTINCT`
  - `:85` [SELECT] tabel ``: `SELECT MONTH(`
  - `:96` [SELECT] tabel `peminjaman`: `SELECT status_denda, COUNT(*) AS c, COALESCE(SUM(denda),0) AS t FROM peminjaman WHERE denda>0 GROUP BY status_denda`
  - `:102` [SELECT] tabel ``: `SELECT COUNT(DISTINCT`
  - `:109` [SELECT] tabel `peminjaman,buku`: `SELECT b.judul, COUNT(*) AS jml FROM peminjaman p JOIN buku b ON b.id=p.`

### `ganti_sandi_admin.php` (70 baris, 2618 byte)
- **Include/Require**: `require_once "db.php"` (`:4`)
- **Superglobals**: `$_POST` [`redirect`(:9), `current_password`(:25), `new_password`(:26), `confirm_new_password`(:27)]; `$_SESSION` [`user_id`(:14,24), `role`(:14)]; `$_SERVER` [`REQUEST_METHOD`(:19)]
- **Kueri SQL**:
  - `:42` [SELECT] tabel `users`: `SELECT password FROM users WHERE id = ? AND role = 'admin'`
  - `:60` [UPDATE] tabel `users`: `UPDATE users SET password = ? WHERE id = ? AND role = 'admin'`

### `halaman_admin.php` (1855 baris, 81400 byte)
- **Include/Require**: `require_once "db.php"` (`:11`), `require_once 'settings_include.php'` (`:199`), `require_once 'modal_profil_admin.php'` (`:1851`), `require_once 'pengaturan_panel.php'` (`:1852`)
- **Fungsi Didefinisikan**: `uploadGambar()` (`:38`)
- **Superglobals**: `$_GET` [`profil_saved`(:31), `q`(:170), `ajax`(:171)]; `$_POST` [`action`(:72), `judul`(:81,112), `penulis`(:82,113), `isbn`(:83,114), `genre`(:84,115), `sinopsis`(:85,116), `stok`(:86,117), `rak`(:87,118), `gambar_auto`(:89,120), `id`(:111,141), `gambar_lama`(:119)]; `$_FILES` [`gambar`(:97,123)]; `$_SESSION` [`user_id`(:6), `role`(:6), `user_name`(:27)]; `$_SERVER` [`REQUEST_METHOD`(:73)]
- **HTML Forms**: `GET -> (self)` (`:1049`), `POST -> (self)` (`:1141`), `POST -> (self)` (`:1237`)
- **JS `fetch()`**: `buku_detail.php?id=` (`:1283`), `cari_isbn.php?isbn=` (`:1650`)
- **Kueri SQL**:
  - `:13` [SELECT] tabel `users`: `SELECT COUNT(*) as c FROM users WHERE role='member' AND status='pending'`
  - `:15` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`
  - `:25` [SELECT] tabel `admin_profile`: `SELECT * FROM admin_profile LIMIT 1`
  - `:101` [INSERT INTO] tabel `buku`: `INSERT INTO buku (judul, penulis, isbn, genre, sinopsis, stok, gambar, rak) VALUES ('`
  - `:110` [UPDATE] tabel ``: `update`
  - `:130` [UPDATE] tabel `buku`: `UPDATE buku SET judul='`
  - `:144` [SELECT] tabel `peminjaman`: `SELECT COUNT(*) AS c FROM peminjaman WHERE buku_id=`
  - `:152` [SELECT] tabel `buku`: `SELECT gambar FROM buku WHERE id=`
  - `:158` [DELETE FROM] tabel `buku`: `DELETE FROM buku WHERE id=`
  - `:179` [SELECT] tabel `buku`: `SELECT * FROM buku`
  - `:970` [SELECT] tabel `users`: `SELECT COUNT(*) AS c FROM users WHERE role='member' AND status='pending'`
  - `:985` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`

### `includes/mailer.php` (140 baris, 5205 byte)
- **Include/Require**: `require_once __DIR__ . '/../config_mail.php'` (`:10`), `require_once __DIR__ . '/../config_mail.example.php'` (`:12`), `require_once __DIR__ . '/../lib/PHPMailer/Exception.php'` (`:15`), `require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php'` (`:16`), `require_once __DIR__ . '/../lib/PHPMailer/SMTP.php'` (`:17`)
- **Fungsi Didefinisikan**: `mailer_mask_email()` (`:26`), `kirim_email()` (`:47`)
- **Kueri SQL**:
  - `:112` [SELECT] tabel `users`: `SELECT id FROM users WHERE email = ? LIMIT 1`
  - `:121` [DELETE FROM] tabel `password_otp`: `DELETE FROM password_otp WHERE user_id = ? AND used_at IS NULL ORDER BY id DESC LIMIT 1`

### `includes/otp.php` (361 baris, 14068 byte)
- **Include/Require**: `require_once __DIR__ . '/../config_mail.php'` (`:10`), `require_once __DIR__ . '/../config_mail.example.php'` (`:12`), `require_once __DIR__ . '/mailer.php'` (`:15`)
- **Fungsi Didefinisikan**: `otp_batas_permintaan()` (`:29`), `otp_buat()` (`:98`), `otp_kirim()` (`:143`), `otp_verifikasi()` (`:256`)
- **Superglobals**: `$_SERVER` [`REMOTE_ADDR`(:30,99,144)]
- **Kueri SQL**:
  - `:33` [SELECT] tabel `password_otp`: `SELECT COUNT(*) FROM password_otp WHERE request_ip = ? AND created_at >= NOW() - INTERVAL 1 HOUR`
  - `:54` [SELECT] tabel `password_otp`: `SELECT COUNT(*), MAX(last_sent_at) FROM password_otp WHERE user_id = ? AND created_at >= NOW() - INTERVAL 1 HOUR`
  - `:102` [DELETE FROM] tabel `password_otp`: `DELETE FROM password_otp WHERE expires_at < NOW() - INTERVAL 1 DAY`
  - `:105` [UPDATE] tabel `password_otp`: `UPDATE password_otp SET expires_at = NOW() WHERE user_id = ? AND used_at IS NULL AND expires_at > NOW()`
  - `:123` [INSERT INTO] tabel `password_otp`: `INSERT INTO password_otp (user_id, otp_hash, expires_at, attempts, used_at, last_sent_at, request_ip) VALUES (?, ?, ?, 0, NULL, NOW(), ?)`
  - `:268` [SELECT] tabel `password_otp`: `SELECT id, otp_hash, expires_at, attempts, used_at FROM password_otp WHERE user_id = ? ORDER BY id DESC LIMIT 1`
  - `:321` [UPDATE] tabel `password_otp`: `UPDATE password_otp SET used_at = NOW(), attempts = attempts + 1 WHERE id = ?`
  - `:337` [UPDATE] tabel `password_otp`: `UPDATE password_otp SET attempts = ? WHERE id = ?`

### `index.php` (4230 baris, 172988 byte)
- **Include/Require**: `require_once "db.php"` (`:4`)
- **Fungsi Didefinisikan**: `format_genre()` (`:32`), `ringkas()` (`:42`)
- **HTML Forms**: `GET -> daftar_buku.php` (`:3384`)
- **Kueri SQL**:
  - `:10` [SELECT] tabel `buku`: `SELECT id, judul, penulis, genre, sinopsis, gambar FROM buku ORDER BY created_at DESC LIMIT 4`
  - `:52` [SELECT] tabel `banner`: `SELECT id, judul, subjudul, gambar, link_url FROM banner WHERE aktif = 1 ORDER BY urutan ASC`
  - `:62` [SELECT] tabel `pengaturan`: `SELECT nilai FROM pengaturan WHERE kunci = 'banner_background_id' LIMIT 1`
  - `:82` [SELECT] tabel `pengaturan`: `SELECT kunci, nilai FROM pengaturan`
  - `:100` [SELECT] tabel `pengaturan`: `SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('musik_aktif','musik_file','musik_judul')`
  - `:134` [SELECT] tabel `pengaturan`: `SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('lokasi_sekolah','lokasi_alamat','lokasi_jam','lokasi_telepon','lokasi_email','lokasi_map_mode','lokasi_map_query','lokasi_map_embed_custom','operasional_status','operasional_catatan','jam_buka_senin_kamis','`

### `kartu_anggota.php` (815 baris, 29030 byte)
- **Include/Require**: `require_once 'db.php'` (`:9`), `require_once "config.php"` (`:36`), `require_once "db.php"` (`:39`), `require_once "db.php"` (`:66`), `require_once "barcode128.php"` (`:108`)
- **Superglobals**: `$_GET` [`action`(:8), `id`(:37,40)]; `$_SESSION` [`kartu_data`(:10,11,28,64,70...), `user_id`(:20,37,65,67,707...), `user_name`(:21), `username`(:22), `role`(:23,37)]
- **Kueri SQL**:
  - `:12` [SELECT] tabel `users`: `SELECT id, full_name, username, role, status, card_status FROM users WHERE id = ? LIMIT 1`
  - `:42` [SELECT] tabel `users`: `SELECT full_name, nik, kelas, no_hp, email, no_anggota, username, foto, status, plain_password, created_at FROM users WHERE id =`
  - `:68` [SELECT] tabel `users`: `SELECT full_name, nik, kelas, no_hp, email, no_anggota, username, foto, status, plain_password, created_at FROM users WHERE id =`

### `kartu_clear.php` (27 baris, 1103 byte)
- **Include/Require**: `require_once "db.php"` (`:6`)
- **Superglobals**: `$_SESSION` [`user_id`(:5,16), `kartu_data`(:5,7,26), `user_name`(:17), `username`(:18), `role`(:19)]
- **Kueri SQL**:
  - `:8` [SELECT] tabel `users`: `SELECT id, full_name, username, role, status, card_status FROM users WHERE id = ? LIMIT 1`

### `kartu_masuk.php` (29 baris, 1129 byte)
- **Include/Require**: `require_once "db.php"` (`:6`)
- **Superglobals**: `$_SESSION` [`kartu_data`(:5,7,24), `user_id`(:16), `user_name`(:17), `username`(:18), `role`(:19)]
- **Kueri SQL**:
  - `:8` [SELECT] tabel `users`: `SELECT id, full_name, username, role, status, card_status FROM users WHERE id = ? LIMIT 1`

### `kelola_banner.php` (1023 baris, 49959 byte)
- **Include/Require**: `require_once "db.php"` (`:10`), `require_once 'settings_include.php'` (`:190`), `require_once 'modal_profil_admin.php'` (`:1020`), `require_once 'pengaturan_panel.php'` (`:1021`)
- **Fungsi Didefinisikan**: `uploadGambarBanner()` (`:37`)
- **Superglobals**: `$_GET` [`profil_saved`(:30)]; `$_POST` [`action`(:54), `judul`(:63,86), `subjudul`(:64,87), `link_url`(:65,88), `id`(:85,111,126,135,153), `gambar_lama`(:89), `arah`(:136)]; `$_FILES` [`gambar`(:66,91)]; `$_SESSION` [`user_id`(:5), `role`(:5), `user_name`(:26)]; `$_SERVER` [`REQUEST_METHOD`(:55)]
- **HTML Forms**: `POST -> (self)` (`:629`), `POST -> (self)` (`:640`), `POST -> (self)` (`:652`), `POST -> (self)` (`:664`), `POST -> (self)` (`:683`)
- **Kueri SQL**:
  - `:12` [SELECT] tabel `users`: `SELECT COUNT(*) as c FROM users WHERE role='member' AND status='pending'`
  - `:14` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`
  - `:24` [SELECT] tabel `admin_profile`: `SELECT * FROM admin_profile LIMIT 1`
  - `:69` [SELECT] tabel `banner`: `SELECT COALESCE(MAX(urutan),0) AS m FROM banner`
  - `:76` [INSERT INTO] tabel `banner`: `INSERT INTO banner (judul, subjudul, gambar, link_url, urutan, aktif) VALUES ('`
  - `:84` [UPDATE] tabel ``: `update`
  - `:102` [UPDATE] tabel `banner`: `UPDATE banner SET judul='`
  - `:113` [SELECT] tabel `banner`: `SELECT gambar FROM banner WHERE id=`
  - `:119` [DELETE FROM] tabel `banner`: `DELETE FROM banner WHERE id=`
  - `:128` [UPDATE] tabel `banner`: `UPDATE banner SET aktif = 1 - aktif WHERE id=`
  - `:137` [SELECT] tabel `banner`: `SELECT id, urutan FROM banner WHERE id=`
  - `:142` [SELECT] tabel `banner`: `SELECT id, urutan FROM banner WHERE urutan`
  - `:145` [UPDATE] tabel `banner`: `UPDATE banner SET urutan=`
  - `:146` [UPDATE] tabel `banner`: `UPDATE banner SET urutan=`
  - `:157` [SELECT] tabel `pengaturan`: `SELECT kunci FROM pengaturan WHERE kunci = '`
  - `:159` [UPDATE] tabel `pengaturan`: `UPDATE pengaturan SET nilai = '`
  - `:161` [INSERT INTO] tabel `pengaturan`: `INSERT INTO pengaturan (kunci, nilai) VALUES ('`
  - `:171` [SELECT] tabel `pengaturan`: `SELECT nilai FROM pengaturan WHERE kunci = 'banner_background_id' LIMIT 1`
  - `:177` [SELECT] tabel `banner`: `SELECT * FROM banner ORDER BY urutan ASC, id ASC`
  - `:540` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`

### `like_handler.php` (48 baris, 1573 byte)
- **Include/Require**: `require_once "db.php"` (`:18`)
- **Superglobals**: `$_POST` [`buku_id`(:21), `type`(:22)]; `$_SESSION` [`user_id`(:7,20), `role`(:13)]
- **Kueri SQL**:
  - `:32` [SELECT] tabel ``: `SELECT id FROM`
  - `:36` [DELETE FROM] tabel ``: `DELETE FROM`
  - `:40` [INSERT INTO] tabel ``: `INSERT INTO`
  - `:45` [SELECT] tabel ``: `SELECT COUNT(*) AS total FROM`

### `logout.php` (160 baris, 5117 byte)
- **Include/Require**: `require_once __DIR__ . '/config.php'` (`:3`), `require_once __DIR__ . '/settings_include.php'` (`:58`)
- **Superglobals**: `$_GET` [`confirm`(:16)]; `$_POST` [`confirm_logout`(:15)]; `$_SESSION` [`user_id`(:9), `*`(:21), `role`(:41)]; `$_SERVER` [`REQUEST_METHOD`(:15), `HTTP_REFERER`(:43,44,46,48), `HTTP_HOST`(:45)]
- **HTML Forms**: `POST -> logout.php` (`:151`)

### `lupa_kartu.php` (1516 baris, 59092 byte)
- **Include/Require**: `require_once __DIR__ . '/db.php'` (`:16`), `require_once __DIR__ . '/includes/otp.php'` (`:17`)
- **Superglobals**: `$_GET` [`expired`(:29)]; `$_POST` [`action`(:34), `email`(:72,1174), `otp`(:165), `otp_digits`(:166,167), `password`(:276), `password_confirm`(:277)]; `$_SESSION` [`lupa_kartu`(:20,45,46,54,63...), `kartu_data`(:300)]; `$_SERVER` [`REQUEST_METHOD`(:35,43,62,71,158...), `REMOTE_ADDR`(:73,213)]
- **HTML Forms**: `POST -> lupa_kartu.php` (`:1167`), `POST -> lupa_kartu.php` (`:1193`), `POST -> lupa_kartu.php` (`:1229`), `POST -> lupa_kartu.php` (`:1235`), `POST -> lupa_kartu.php` (`:1254`), `POST -> lupa_kartu.php` (`:1285`)
- **Kueri SQL**:
  - `:47` [UPDATE] tabel `users`: `UPDATE users SET card_status='active' WHERE id=?`
  - `:86` [SELECT] tabel `users`: `SELECT id, full_name, nik, kelas, no_hp, email, no_anggota, username, foto, role, status FROM users WHERE email = ? LIMIT 1`
  - `:185` [UPDATE] tabel `users`: `UPDATE users SET card_status='frozen' WHERE id=?`
  - `:265` [UPDATE] tabel `users`: `UPDATE users SET card_status='active' WHERE id=?`
  - `:293` [UPDATE] tabel `users`: `UPDATE users SET password = ?, plain_password = ?, card_status = 'active' WHERE id = ?`

### `modal_profil_admin.php` (300 baris, 14649 byte)
- **Include/Require**: `require_once __DIR__ . '/notifikasi_pengajuan_admin.php'` (`:300`)
- **Superglobals**: `$_GET` [`profil_saved`(:153), `profil_err`(:155,156)]; `$_SESSION` [`user_name`(:9,12)]; `$_SERVER` [`PHP_SELF`(:16)]
- **HTML Forms**: `POST -> update_profil_admin.php` (`:131`)
- **Kueri SQL**:
  - `:7` [SELECT] tabel `admin_profile`: `SELECT * FROM admin_profile LIMIT 1`

### `notifikasi_pengajuan_admin.php` (833 baris, 26993 byte)
- **Konstanta**: `AKSA_NOTIF_INCLUDED = true` (`:8`)
- **Superglobals**: `$_SESSION` [`user_id`(:15), `role`(:15)]
- **JS `fetch()`**: `api_cek_pengajuan_admin.php` (`:789`)

### `pengajuan_buku.php` (1169 baris, 60584 byte)
- **Include/Require**: `require_once 'db.php'` (`:11`), `require_once 'settings_include.php'` (`:620`), `require_once 'modal_profil_admin.php'` (`:1166`), `require_once 'pengaturan_panel.php'` (`:1167`)
- **Superglobals**: `$_GET` [`tab`(:171), `q`(:172)]; `$_POST` [`action`(:38), `pengajuan_id`(:41), `alasan_penolakan`(:96), `filter_target`(:131)]; `$_SESSION` [`user_id`(:6), `role`(:6), `user_name`(:32)]; `$_SERVER` [`REQUEST_METHOD`(:40)]
- **HTML Forms**: `GET -> pengajuan_buku.php` (`:811`), `POST -> (self)` (`:841`), `POST -> (self)` (`:996`), `POST -> (self)` (`:1015`), `POST -> (self)` (`:1027`), `POST -> (self)` (`:1071`)
- **Kueri SQL**:
  - `:13` [SELECT] tabel `users`: `SELECT COUNT(*) as c FROM users WHERE role='member' AND status='pending'`
  - `:15` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`
  - `:30` [SELECT] tabel `admin_profile`: `SELECT * FROM admin_profile LIMIT 1`
  - `:47` [SELECT] tabel `pengajuan_peminjaman,buku`: `SELECT p.*, b.stok, b.judul FROM pengajuan_peminjaman p LEFT JOIN buku b ON b.id = p.buku_id WHERE p.id = ? AND p.status = 'menunggu' LIMIT 1`
  - `:68` [UPDATE] tabel `buku`: `UPDATE buku SET stok = stok -`
  - `:78` [INSERT INTO] tabel `peminjaman`: `INSERT INTO peminjaman (buku_id, user_id, nama_peminjam, waktu_pinjam, batas_kembali, status) VALUES (`
  - `:85` [UPDATE] tabel `pengajuan_peminjaman`: `UPDATE pengajuan_peminjaman SET status = 'disetujui', approved_at = '`
  - `:100` [UPDATE] tabel `pengajuan_peminjaman`: `UPDATE pengajuan_peminjaman SET status = 'ditolak', alasan_penolakan = ?, approved_at = ? WHERE id = ? AND status = 'menunggu'`
  - `:112` [SELECT] tabel `pengajuan_peminjaman`: `SELECT file_kartu, nama_peminjam FROM pengajuan_peminjaman WHERE id = ? LIMIT 1`
  - `:123` [DELETE FROM] tabel `pengajuan_peminjaman`: `DELETE FROM pengajuan_peminjaman WHERE id =`
  - `:143` [SELECT] tabel `pengajuan_peminjaman`: `SELECT file_kartu FROM pengajuan_peminjaman`
  - `:152` [DELETE FROM] tabel `pengajuan_peminjaman`: `DELETE FROM pengajuan_peminjaman`
  - `:162` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman`
  - `:163` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`
  - `:164` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='disetujui'`
  - `:165` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='ditolak'`
  - `:190` [SELECT] tabel `pengajuan_peminjaman,buku,users`: `SELECT p.*, b.judul, b.penulis, b.gambar as buku_gambar, b.stok as buku_stok, b.rak as buku_rak, u.nik, u.kelas, u.no_anggota, u.email, u.no_hp, u.foto as user_foto FROM pengajuan_peminjaman p LEFT JOIN buku b ON b.id = p.buku_id LEFT JOIN users u ON u.id = p.`

### `pengajuan_peminjaman.php` (1272 baris, 67609 byte)
- **Include/Require**: `require_once 'db.php'` (`:6`), `require_once 'settings_include.php'` (`:222`), `require_once 'pengaturan_panel.php'` (`:1270`)
- **Superglobals**: `$_GET` [`buku_id`(:10,55)]; `$_POST` [`buku_id`(:55,88), `action`(:85,87), `total_buku`(:89), `batas_kembali`(:90), `waktu_pengambilan`(:91), `tanggal_ambil_nanti`(:98), `jam_ambil_pilihan`(:99), `catatan_pengambilan`(:100)]; `$_FILES` [`file_kartu`(:138,140,141,149,150...)]; `$_SESSION` [`user_id`(:9,15), `role`(:16)]; `$_SERVER` [`REQUEST_METHOD`(:83,87)]
- **HTML Forms**: `POST -> pengajuan_peminjaman.php` (`:778`)
- **Kueri SQL**:
  - `:24` [SELECT] tabel `users`: `SELECT id, full_name, nik, kelas, no_anggota, email, no_hp, foto, status, card_status FROM users WHERE id = ? LIMIT 1`
  - `:41` [SELECT] tabel `pengaturan`: `SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('musik_aktif','musik_file','musik_judul')`
  - `:59` [SELECT] tabel `buku`: `SELECT id, judul, penulis, isbn, genre, stok, gambar, sinopsis FROM buku WHERE id = ? LIMIT 1`
  - `:69` [SELECT] tabel `buku`: `SELECT id, judul, penulis, genre, stok, gambar FROM buku WHERE stok > 0 ORDER BY judul ASC`
  - `:119` [SELECT] tabel `buku`: `SELECT id, judul, stok FROM buku WHERE id = ? LIMIT 1`
  - `:179` [INSERT INTO] tabel `pengajuan_peminjaman`: `INSERT INTO pengajuan_peminjaman (user_id, buku_id, nama_peminjam, file_kartu, total_buku, batas_kembali, waktu_pengambilan, catatan_pengambilan, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'menunggu')`
  - `:204` [SELECT] tabel `buku`: `SELECT id, judul, penulis, isbn, genre, stok, gambar, sinopsis FROM buku WHERE id = ? LIMIT 1`

### `pengaturan_denda.php` (913 baris, 43023 byte)
- **Include/Require**: `require_once 'db.php'` (`:11`), `require_once 'settings_include.php'` (`:170`), `require_once 'modal_profil_admin.php'` (`:909`), `require_once 'pengaturan_panel.php'` (`:910`)
- **Fungsi Didefinisikan**: `getSetting()` (`:40`), `setSetting()` (`:49`)
- **Superglobals**: `$_GET` [`q`(:144)]; `$_POST` [`is_ajax`(:63), `action`(:75,115), `denda_per_hari`(:76), `denda_aktif`(:77), `grace_period`(:78), `field`(:96), `pem_id`(:116)]; `$_SESSION` [`user_id`(:6), `role`(:6), `user_name`(:33)]; `$_SERVER` [`REQUEST_METHOD`(:62), `HTTP_X_REQUESTED_WITH`(:64)]
- **HTML Forms**: `POST -> pengaturan_denda.php<?= $search ? ` (`:668`), `GET -> (self)` (`:713`), `POST -> pengaturan_denda.php<?= $search ? ` (`:770`)
- **JS `fetch()`**: `pengaturan_denda.php` (`:855`)
- **Kueri SQL**:
  - `:13` [SELECT] tabel `users`: `SELECT COUNT(*) as c FROM users WHERE role='member' AND status='pending'`
  - `:15` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`
  - `:31` [SELECT] tabel `admin_profile`: `SELECT * FROM admin_profile LIMIT 1`
  - `:42` [SELECT] tabel `pengaturan`: `SELECT nilai FROM pengaturan WHERE kunci='`
  - `:53` [INSERT INTO] tabel `pengaturan`: `INSERT INTO pengaturan (kunci, nilai) VALUES ('`
  - `:119` [UPDATE] tabel `peminjaman`: `UPDATE peminjaman SET status_denda='lunas' WHERE id=`
  - `:135` [SELECT] tabel `peminjaman`: `SELECT COUNT(*) AS c FROM peminjaman WHERE status_denda='belum_bayar'`
  - `:137` [SELECT] tabel `peminjaman`: `SELECT COUNT(*) AS c FROM peminjaman WHERE status_denda='lunas'`
  - `:139` [SELECT] tabel `peminjaman`: `SELECT COALESCE(SUM(denda),0) AS t FROM peminjaman WHERE status_denda='belum_bayar'`
  - `:141` [SELECT] tabel `peminjaman`: `SELECT COALESCE(SUM(denda),0) AS t FROM peminjaman WHERE status_denda='lunas'`
  - `:153` [SELECT] tabel `peminjaman,buku`: `SELECT p.*, b.judul, b.penulis FROM peminjaman p INNER JOIN buku b ON b.id = p.buku_id WHERE p.denda > 0`
  - `:567` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`

### `pengaturan_lokasi.php` (2529 baris, 107665 byte)
- **Include/Require**: `require_once 'db.php'` (`:14`), `require_once 'settings_include.php'` (`:451`), `require_once 'modal_profil_admin.php'` (`:2524`), `require_once 'pengaturan_panel.php'` (`:2525`)
- **Fungsi Didefinisikan**: `getSetting()` (`:43`), `setSetting()` (`:52`)
- **Superglobals**: `$_POST` [`action`(:68), `is_ajax`(:69,185), `lokasi_map_input`(:82), `lokasi_sekolah`(:104), `lokasi_alamat`(:105), `lokasi_telepon`(:106), `lokasi_email`(:107), `jam_buka_senin_kamis`(:110), `jam_tutup_senin_kamis`(:111), `jam_buka_jumat`(:112), `jam_tutup_jumat`(:113), `jam_status_sabtu`(:114), `jam_buka_sabtu`(:115), `jam_tutup_sabtu`(:116), `jam_status_minggu`(:117), `jam_buka_minggu`(:118), `jam_tutup_minggu`(:119), `operasional_status`(:121), `operasional_catatan`(:122), `lokasi_jam`(:147)]; `$_SESSION` [`user_id`(:9), `role`(:9), `user_name`(:36)]; `$_SERVER` [`REQUEST_METHOD`(:68), `HTTP_X_REQUESTED_WITH`(:70)]
- **HTML Forms**: `POST -> (self)` (`:1770`)
- **JS `fetch()`**: `pengaturan_lokasi.php` (`:2262`)
- **Kueri SQL**:
  - `:23` [SELECT] tabel `users`: `SELECT COUNT(*) as c FROM users WHERE role='member' AND status='pending'`
  - `:25` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`
  - `:34` [SELECT] tabel `admin_profile`: `SELECT * FROM admin_profile LIMIT 1`
  - `:45` [SELECT] tabel `pengaturan`: `SELECT nilai FROM pengaturan WHERE kunci='`
  - `:56` [SELECT] tabel `pengaturan`: `SELECT id FROM pengaturan WHERE kunci='`
  - `:58` [UPDATE] tabel `pengaturan`: `UPDATE pengaturan SET nilai='`
  - `:60` [INSERT INTO] tabel `pengaturan`: `INSERT INTO pengaturan (kunci, nilai, keterangan, updated_at) VALUES ('`

### `pengaturan_musik.php` (1079 baris, 46071 byte)
- **Include/Require**: `require_once "db.php"` (`:11`), `require_once 'settings_include.php'` (`:256`), `require_once 'modal_profil_admin.php'` (`:1075`), `require_once 'pengaturan_panel.php'` (`:1076`)
- **Fungsi Didefinisikan**: `simpanPengaturan()` (`:33`)
- **Superglobals**: `$_POST` [`is_ajax`(:63), `action`(:65), `musik_aktif`(:86,197), `musik_judul`(:114,172,198)]; `$_FILES` [`musik_file`(:132,135,174,203,204)]; `$_SESSION` [`user_id`(:6), `role`(:6), `user_name`(:27)]; `$_SERVER` [`REQUEST_METHOD`(:62), `HTTP_X_REQUESTED_WITH`(:64)]
- **JS `fetch()`**: `pengaturan_musik.php` (`:894`), `pengaturan_musik.php` (`:932`), `pengaturan_musik.php` (`:988`), `pengaturan_musik.php` (`:1047`)
- **Kueri SQL**:
  - `:13` [SELECT] tabel `users`: `SELECT COUNT(*) as c FROM users WHERE role='member' AND status='pending'`
  - `:15` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`
  - `:25` [SELECT] tabel `admin_profile`: `SELECT * FROM admin_profile LIMIT 1`
  - `:36` [SELECT] tabel `pengaturan`: `SELECT kunci FROM pengaturan WHERE kunci = '`
  - `:38` [UPDATE] tabel `pengaturan`: `UPDATE pengaturan SET nilai = '`
  - `:40` [INSERT INTO] tabel `pengaturan`: `INSERT INTO pengaturan (kunci, nilai) VALUES ('`
  - `:50` [SELECT] tabel `pengaturan`: `SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('musik_aktif','musik_file','musik_judul')`
  - `:714` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`

### `pengaturan_panel.php` (633 baris, 29493 byte)

### `pinjam_buku.php` (1182 baris, 52130 byte)
- **Include/Require**: `require_once 'db.php'` (`:14`), `require_once 'settings_include.php'` (`:179`), `require_once 'modal_profil_admin.php'` (`:1178`), `require_once 'pengaturan_panel.php'` (`:1179`)
- **Superglobals**: `$_GET` [`q`(:124), `ajax`(:125)]; `$_POST` [`action`(:57), `buku_id`(:64), `user_id`(:65), `batas_kembali`(:66)]; `$_SESSION` [`user_id`(:9), `role`(:9), `user_name`(:51)]
- **HTML Forms**: `GET -> (self)` (`:791`), `POST -> pinjam_buku.php<?= $search ? ` (`:886`)
- **Kueri SQL**:
  - `:16` [SELECT] tabel `users`: `SELECT COUNT(*) as c FROM users WHERE role='member' AND status='pending'`
  - `:18` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`
  - `:36` [SELECT] tabel `pengaturan`: `SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('musik_aktif','musik_file','musik_judul')`
  - `:49` [SELECT] tabel `admin_profile`: `SELECT * FROM admin_profile LIMIT 1`
  - `:74` [SELECT] tabel `users`: `SELECT full_name FROM users WHERE id=`
  - `:84` [SELECT] tabel `buku`: `SELECT stok FROM buku WHERE id=`
  - `:100` [UPDATE] tabel `buku`: `UPDATE buku SET stok = stok - 1 WHERE id =`
  - `:105` [INSERT INTO] tabel `peminjaman`: `INSERT INTO peminjaman (buku_id, user_id, nama_peminjam, waktu_pinjam, batas_kembali) VALUES (`
  - `:133` [SELECT] tabel `buku`: `SELECT * FROM buku`
  - `:141` [SELECT] tabel `peminjaman,buku`: `SELECT COUNT(*) AS c FROM peminjaman p INNER JOIN buku b ON b.id = p.buku_id WHERE p.status='dipinjam'`
  - `:153` [SELECT] tabel `users`: `SELECT id, full_name, no_anggota, nik, kelas, username, foto FROM users WHERE role = 'member' AND status = 'approved' AND (card_status IS NULL OR card_status != 'frozen') ORDER BY full_name ASC`
  - `:733` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`

### `rating_handler.php` (77 baris, 3084 byte)
- **Include/Require**: `require_once "db.php"` (`:19`)
- **Superglobals**: `$_GET` [`buku_id`(:26)]; `$_POST` [`buku_id`(:48), `rating`(:49)]; `$_SESSION` [`user_id`(:6,21), `role`(:13)]; `$_SERVER` [`REQUEST_METHOD`(:22)]
- **Kueri SQL**:
  - `:30` [SELECT] tabel `buku_ratings`: `SELECT rating FROM buku_ratings WHERE user_id=`
  - `:34` [SELECT] tabel `buku_ratings`: `SELECT AVG(rating) AS avg_r, COUNT(*) AS total FROM buku_ratings WHERE buku_id=`
  - `:56` [SELECT] tabel `buku_ratings`: `SELECT id FROM buku_ratings WHERE user_id=`
  - `:58` [UPDATE] tabel `buku_ratings`: `UPDATE buku_ratings SET rating=`
  - `:60` [INSERT INTO] tabel `buku_ratings`: `INSERT INTO buku_ratings (user_id, buku_id, rating, created_at, updated_at) VALUES (`
  - `:64` [SELECT] tabel `buku_ratings`: `SELECT AVG(rating) AS avg_r, COUNT(*) AS total FROM buku_ratings WHERE buku_id=`

### `settings_include.php` (3222 baris, 114816 byte)

### `sign_in.php` (860 baris, 29697 byte)
- **Include/Require**: `require_once "db.php"` (`:4`), `require_once 'settings_include.php'` (`:683`)
- **Superglobals**: `$_GET` [`redirect`(:7)]; `$_POST` [`redirect`(:7), `email`(:33,728), `password`(:34)]; `$_SESSION` [`user_id`(:17,57), `role`(:18,60), `user_name`(:58), `username`(:59)]; `$_SERVER` [`REQUEST_METHOD`(:29)]
- **HTML Forms**: `POST -> sign_in.php` (`:719`)
- **Kueri SQL**:
  - `:40` [SELECT] tabel `users`: `SELECT id, full_name, username, password, role, status, card_status FROM users WHERE email = ? OR username = ?`

### `sign_up.php` (1760 baris, 66410 byte)
- **Include/Require**: `require_once "db.php"` (`:4`), `require_once 'settings_include.php'` (`:1356`)
- **Superglobals**: `$_POST` [`tipe_anggota`(:41), `full_name`(:43), `nik`(:44), `no_hp`(:46), `email`(:47), `saved_foto`(:48), `password`(:49), `password_confirm`(:50)]; `$_FILES` [`foto`(:103,104,105,106,129)]; `$_SESSION` [`user_id`(:7), `role`(:8), `kartu_data`(:229)]; `$_SERVER` [`REQUEST_METHOD`(:36)]
- **HTML Forms**: `POST -> sign_up.php` (`:1403`)
- **Kueri SQL**:
  - `:143` [SELECT] tabel `users`: `SELECT id FROM users WHERE email = ?`
  - `:153` [SELECT] tabel `users`: `SELECT id FROM users WHERE nik = ?`
  - `:184` [SELECT] tabel `users`: `SELECT id FROM users WHERE username = ?`
  - `:202` [SELECT] tabel `users`: `SELECT id FROM users WHERE no_anggota = ?`
  - `:215` [INSERT INTO] tabel `users`: `INSERT INTO users (full_name, nik, kelas, no_hp, no_anggota, username, email, password, plain_password, foto, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'member', 'approved')`

### `telah_dipinjam.php` (1263 baris, 64940 byte)
- **Include/Require**: `require_once 'db.php'` (`:13`), `require_once 'settings_include.php'` (`:299`), `require_once 'modal_profil_admin.php'` (`:1259`), `require_once 'pengaturan_panel.php'` (`:1260`)
- **Fungsi Didefinisikan**: `getSetting()` (`:41`), `hitungDenda()` (`:56`), `durasiHuman()` (`:218`), `sisaWaktu()` (`:229`), `previewDenda()` (`:253`), `formatNoWa()` (`:265`), `pesanPengingatWa()` (`:277`)
- **Superglobals**: `$_GET` [`tab`(:160), `q`(:165), `ajax`(:166)]; `$_POST` [`action`(:75), `pem_id`(:83,126,133,145)]; `$_SESSION` [`user_id`(:8), `role`(:8), `user_name`(:36)]; `$_SERVER` [`REQUEST_METHOD`(:77)]
- **HTML Forms**: `GET -> (self)` (`:778`), `POST -> (self)` (`:949`), `POST -> (self)` (`:1031`), `POST -> (self)` (`:1048`)
- **JS `fetch()`**: `telah_dipinjam.php` (`:1197`), `telah_dipinjam.php?` (`:1231`)
- **Kueri SQL**:
  - `:15` [SELECT] tabel `users`: `SELECT COUNT(*) as c FROM users WHERE role='member' AND status='pending'`
  - `:17` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`
  - `:34` [SELECT] tabel `admin_profile`: `SELECT * FROM admin_profile LIMIT 1`
  - `:43` [SELECT] tabel `pengaturan`: `SELECT nilai FROM pengaturan WHERE kunci='`
  - `:86` [SELECT] tabel `peminjaman,buku`: `SELECT p.buku_id, p.status, p.batas_kembali FROM peminjaman p LEFT JOIN buku b ON b.id = p.buku_id WHERE p.id=`
  - `:103` [UPDATE] tabel `peminjaman`: `UPDATE peminjaman SET status='dikembalikan', waktu_kembali='`
  - `:111` [UPDATE] tabel `buku`: `UPDATE buku SET stok = stok + 1 WHERE id=`
  - `:128` [DELETE FROM] tabel `peminjaman`: `DELETE FROM peminjaman WHERE id=`
  - `:137` [INSERT INTO] tabel `reminder_log`: `INSERT INTO reminder_log (peminjaman_id, tanggal, wa_terkirim, wa_terkirim_at) VALUES (`
  - `:148` [UPDATE] tabel `peminjaman`: `UPDATE peminjaman SET status_denda='lunas' WHERE id=`
  - `:180` [SELECT] tabel `peminjaman,buku,users,reminder_log`: `SELECT p.*, b.judul, b.penulis, b.gambar, b.genre, u.no_hp AS anggota_no_hp, u.email AS anggota_email, rl.wa_terkirim AS wa_terkirim_hari_ini FROM peminjaman p INNER JOIN buku b ON b.id = p.buku_id LEFT JOIN users u ON u.id = p.user_id LEFT JOIN reminder_log r`
  - `:200` [SELECT] tabel `peminjaman,buku`: `SELECT COUNT(*) AS c FROM peminjaman p INNER JOIN buku b ON b.id = p.buku_id WHERE p.status='`
  - `:210` [SELECT] tabel `peminjaman`: `SELECT COUNT(*) AS c, COALESCE(SUM(denda),0) AS t FROM peminjaman WHERE status_denda='belum_bayar'`
  - `:716` [SELECT] tabel `pengajuan_peminjaman`: `SELECT COUNT(*) as c FROM pengajuan_peminjaman WHERE status='menunggu'`

### `update_profil_admin.php` (125 baris, 5770 byte)
- **Include/Require**: `require_once "db.php"` (`:10`)
- **Superglobals**: `$_POST` [`redirect`(:18), `current_password`(:33), `new_password`(:34), `confirm_new_password`(:35), `display_name`(:83)]; `$_FILES` [`foto_admin`(:88,91,93,96,103)]; `$_SESSION` [`user_id`(:5,28), `role`(:5), `user_name`(:122)]; `$_SERVER` [`REQUEST_METHOD`(:23)]
- **Kueri SQL**:
  - `:50` [SELECT] tabel `users`: `SELECT password FROM users WHERE id = ? AND role = 'admin'`
  - `:72` [UPDATE] tabel `users`: `UPDATE users SET password = ? WHERE id = ? AND role = 'admin'`
  - `:98` [SELECT] tabel `admin_profile`: `SELECT foto FROM admin_profile LIMIT 1`
  - `:109` [SELECT] tabel `admin_profile`: `SELECT id FROM admin_profile LIMIT 1`
  - `:113` [UPDATE] tabel `admin_profile`: `UPDATE admin_profile SET display_name='`
  - `:115` [UPDATE] tabel `admin_profile`: `UPDATE admin_profile SET display_name='`
  - `:118` [INSERT INTO] tabel `admin_profile`: `INSERT INTO admin_profile (display_name, foto) VALUES ('`
