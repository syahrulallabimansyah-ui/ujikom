# 01 — Pemahaman Menyeluruh Proyek AKSA NOVA (Murni dari Kode Sumber)

> **Sumber Kebenaran Tunggal**: Seluruh narasi dan angka bisnis dalam dokumen ini ditulis murni dari pembacaan baris-demi-baris atas 44 berkas PHP aplikasi, berkas skema SQL (`database/aksa_nova.sql` & `database/migrations/2026_otp_reset.sql`), dan berkas konfigurasi Apache (`.htaccess`, `storage/.htaccess`), didukung ekstraksi mekanis `01_php_fact_extractor.php` (`02_fakta_kode.json`) serta `02_schema_and_drift.py` (`schema_model.json`). Tidak ada satu pun dokumen (`ERD/`, `SRS/`, `UML/`) yang dibuka saat menyusun berkas ini.

---

## 1.1 Peta Proyek & Klasifikasi Berkas Aplikasi

Proyek **AKSA NOVA** adalah sistem informasi perpustakaan sekolah berbasis web (PHP prosedural + MySQLi + HTML/CSS/JS vanilla) untuk **SMK Negeri 1 Rongga** (`index.php:114`, `pengaturan_lokasi.php:315`). Tanpa menghitung pustaka pihak ketiga `lib/PHPMailer/` (3 berkas, 251.240 byte) dan dokumen eksternal (`ERD/`, `SRS/`, `UML/`), aplikasi terdiri atas **44 berkas PHP aplikasi**, **2 berkas SQL**, **2 berkas `.htaccess`**, **1 `.gitignore`**, **1 berkas cache JSON**, serta **32 berkas media unggahan aktif** di `uploads/`.

### A. Titik Masuk & Halaman Antarmuka (23 Halaman)
1. **`index.php`** (`174.909 B`, `4.230 baris`): *Landing page* publik bergaya 7 *slide-scroll* (Hero Banner gambar/GIF/video, 4 Buku Terbaru, Pencarian & Kutipan Harian, Tata Tertib & Info Denda, Tentang Katalog, Peta Google Maps + Status Operasional Realtime WIB, Footer). Tidak memanggil `session_start()`.
2. **`beranda.php`** (`115.443 B`, `2.745 baris`): Katalog utama untuk **Tamu** dan **Anggota** (`approved` & kartu `active`; `admin` diarahkan ke `dashboard.php` di `:6-9`). Menampilkan *Spotlight* & Top 5 buku paling disukai (`buku_likes`), 10 buku terbaru, 6 genre populer, fitur acak *"Bingung Mau Baca Apa?"* (`20` buku `stok > 0`), serta 9 berita RSS terkini dari **detikcom** dan **CNN Indonesia** (3 berita per slide).
3. **`daftar_buku.php`** (`67.973 B`, `1.608 baris`): Katalog pencarian & filter genre untuk Tamu & Anggota dengan paginasi `10` buku/halaman (`:34`) serta mode *live search* fragmen HTML (`?ajax=1`, `:1129-1135`).
4. **`buku_simpan.php`** (`46.973 B`, `1.199 baris`): Halaman koleksi buku favorit/tersimpan (`buku_favorites`) khusus Anggota login (`approved` & `active`), dilengkapi pencarian judul/penulis (`?ajax=1`, `:839-844`).
5. **`sign_up.php`** (`67.855 B`, `1.760 baris`): Pendaftaran mandiri anggota baru (Siswa atau Guru) dengan unggah foto profil opsional dan pembuatan nomor anggota unik.
6. **`sign_in.php`** (`31.592 B`, `860 baris`): Halaman login terpadu menggunakan `email` atau `username` untuk Anggota dan Admin, mendukung parameter `?redirect=` tervalidasi whitelist regex (`:7-14`).
7. **`logout.php`** (`5.086 B`, `160 baris`): Halaman konfirmasi keluar dan penghancuran sesi (`session_destroy()` + hapus cookie sesi).
8. **`lupa_kartu.php`** (`60.533 B`, `1.516 baris`): Wizard 3 langkah pemulihan akun & penerbitan ulang kartu anggota berbasis OTP 6 digit via email SMTP.
9. **`kartu_anggota.php`** (`34.901 B`, `815 baris`): Halaman pratinjau & unduh PNG 300 DPI (`html2canvas`) kartu anggota digital berstandar CR80 yang merender **Barcode Code 128 SVG** (`barcode128.php`). Dapat dibuka seusai registrasi, seusai pemulihan OTP, seusai edit profil, dari menu anggota login, atau oleh Admin via `?id=mask_id(id)`.
10. **`edit_kartu.php`** (`41.484 B`, `1.029 baris`): Halaman edit data diri anggota (`full_name`, `username`, `nik`, `no_hp`, `email`, `foto`) dan ganti kata sandi opsional.
11. **`dashboard_user.php`** (`52.660 B`, `1.251 baris`): Halaman *"Peminjaman Saya"* khusus Anggota untuk memantau pinjaman aktif, estimasi denda berjalan, tunggakan denda (`belum_bayar`), 5 pengajuan terakhir, 6 riwayat pengembalian terakhir, 4 buku tersimpan, dan kartu anggota mini.
12. **`pengajuan_peminjaman.php`** (`55.146 B`, `1.272 baris`): Formulir pengajuan peminjaman buku online oleh Anggota (`1–40` eksemplar) beserta unggah wajib bukti foto kartu anggota (`file_kartu`) dan jadwal pengambilan (`sekarang` / `nanti`).
13. **`dashboard.php`** (`45.107 B`, `1.060 baris`): Dashboard statistik & laporan tahunan Admin (ringkasan koleksi, status anggota, pinjaman aktif/terlambat, aktivitas bulan ini, Top 5 buku terpopuler, ringkasan denda, tabel rekap 12 bulan, cetak laporan, ekspor `.xls`, dan kirim ringkasan via `wa.me`).
14. **`halaman_admin.php`** (`70.648 B`, `1.855 baris`): Halaman Admin CRUD katalog buku (termasuk kamera langsung `getUserMedia` untuk foto sampul, *auto-fill* ISBN via `cari_isbn.php`, dan *live search* `?ajax=1`).
15. **`pengajuan_buku.php`** (`50.946 B`, `1.169 baris`): Halaman Admin verifikasi pengajuan peminjaman (`setujui`, `tolak` beserta alasan, `hapus_riwayat`, `bersihkan_riwayat`).
16. **`pinjam_buku.php`** (`44.695 B`, `1.182 baris`): Halaman Admin pencatatan peminjaman langsung di meja sirkulasi (`1` eksemplar per transaksi, disertai *live search* `?ajax=1`).
17. **`telah_dipinjam.php`** (`53.309 B`, `1.263 baris`): Halaman Admin sirkulasi peminjaman aktif & riwayat pengembalian, perhitungan denda otomatis saat pengembalian, pelunasan denda (`tandai_lunas`), pengiriman pengingat keterlambatan via WhatsApp (`wa.me` + log `tandai_wa`), dan hapus riwayat (`?ajax=1`).
18. **`daftar_anggota.php`** (`51.997 B`, `1.264 baris`): Halaman Admin manajemen anggota (`approve`, `reject`/nonaktifkan, `unfreeze`/cairkan kartu beku, `reset_password`, `hapus`, lihat kartu terenkripsi `mask_id`, dan chat `wa.me`, plus *live search* `?ajax=1`).
19. **`kelola_banner.php`** (`41.006 B`, `1.023 baris`): Halaman Admin CRUD banner beranda (gambar/GIF/video MP4/WebM, *crop* 16:7 di klien, `toggle` aktif, `geser` urutan, dan `set_background` utama).
20. **`pengaturan_denda.php`** (`38.268 B`, `913 baris`): Halaman Admin pengaturan tarif denda per hari, status aktif denda, masa toleransi (*grace period*) dengan *auto-save* AJAX, serta tabel pelunasan tunggakan denda.
21. **`pengaturan_lokasi.php`** (`105.070 B`, `2.529 baris`): Halaman Admin pengaturan lokasi peta Google Maps (mode *query* atau *iframe embed*), identitas kontak perpustakaan, status operasional (`otomatis`, `buka_manual`, `tutup_libur`), dan jam buka Senin–Minggu dengan *auto-save* AJAX.
22. **`pengaturan_musik.php`** (`41.610 B`, `1.079 baris`): Halaman Admin unggah & kelola musik latar perpustakaan (`upload_file`, `toggle_musik`, `simpan_judul`, `hapus_file`, `simpan`).

### B. Endpoint Handler Form, Redirect, Ekspor, & AJAX Murni (10 Berkas)
23. **`buku_detail.php`** (`4.588 B`, `131 baris`): Endpoint GET JSON detail buku + agregat suka, favorit, rating, dan status interaksi user.
24. **`like_handler.php`** (`1.445 B`, `48 baris`): Endpoint POST JSON toggle `buku_likes` (`type=like`) dan `buku_favorites` (`type=favorite`).
25. **`rating_handler.php`** (`2.625 B`, `77 baris`): Endpoint GET/POST JSON ambil & simpan nilai rating bintang `1–5` (`buku_ratings`).
26. **`cari_isbn.php`** (`17.643 B`, `425 baris`): Endpoint GET JSON Admin pencarian metadata buku 4 tahap (Google Books + 3 endpoint Open Library) sekaligus pengunduh sampul otomatis ke `uploads/gambar/`.
27. **`api_cek_pengajuan_admin.php`** (`2.270 B`, `71 baris`): Endpoint GET JSON Admin penghitung pengajuan `'menunggu'` dan pengambil 1 pengajuan terbaru untuk polling notifikasi.
28. **`export_excel_dashboard.php`** (`15.694 B`, `318 baris`): Endpoint GET Admin pengunduh laporan tahunan berformat **SpreadsheetML XML (`.xls`)** 3 worksheet.
29. **`update_profil_admin.php`** (`4.956 B`, `125 baris`): Endpoint POST Admin penyimpan nama & foto profil (`admin_profile`) serta pengganti kata sandi opsional (`users`).
30. **`ganti_sandi_admin.php`** (`2.557 B`, `70 baris`): Endpoint POST Admin mandiri untuk ganti kata sandi (tidak lagi dirujuk oleh `modal_profil_admin.php:131`, namun masih aktif di disk).
31. **`kartu_masuk.php`** (`953 B`, `29 baris`): Endpoint transisi seusai kartu terbit untuk mengautentikasi sesi anggota (`$_SESSION['user_id']`) lalu mengarahkan ke `beranda.php`.
32. **`kartu_clear.php`** (`851 B`, `27 baris`): Endpoint latar belakang (`navigator.sendBeacon`) pembersih `$_SESSION['kartu_data']` dengan proteksi auto-login anti-*race condition*.

### C. Skrip Cron CLI (1 Berkas)
33. **`cron_cek_terlambat.php`** (`2.163 B`, `60 baris`): Skrip khusus CLI (`PHP_SAPI === 'cli'`) pencatat keterlambatan harian pinjaman aktif ke tabel `reminder_log`.

### D. Pustaka Konfigurasi, Helper, & Komponen Include UI (11 Berkas)
34. **`config.php`** (`3.384 B`, `102 baris`): Timezone `'Asia/Jakarta'`, cookie sesi aman, konstanta `GOOGLE_BOOKS_API_KEY`, helper CSRF (`csrf_token`, `csrf_field`, `csrf_verify`), dan penyamar ID (`mask_id`, `unmask_id`).
35. **`db.php`** (`622 B`, `23 baris`): Memuat `config.php`, membuka koneksi MySQLi `localhost` database `aksa_nova` (`utf8mb4`), dan menyetel `SET time_zone = '+07:00'`.
36. **`config_mail.php`** (`894 B`, `23 baris`) & 37. **`config_mail.example.php`** (`1.123 B`, `28 baris`): Konstanta kredensial SMTP TLS port `587`, `OTP_SECRET`, dan `OTP_DEV_MODE`.
38. **`includes/otp.php`** (`13.861 B`, `361 baris`): Logika pembatasan laju (*rate-limit*), pembangkitan HMAC-SHA256, pengiriman, dan verifikasi OTP 6 digit.
39. **`includes/mailer.php`** (`5.412 B`, `140 baris`): Pembungkus `PHPMailer`, sensor rahasia pada `storage/mail_error.log`, dan *rollback* otomatis baris OTP bila SMTP gagal kirim.
40. **`barcode128.php`** (`3.216 B`, `73 baris`): Generator vektor SVG Barcode **Code 128 Subset B** murni PHP (`render_code128_svg()`).
41. **`settings_include.php`** (`127.835 B`, `3.222 baris`): Partial `<head>` global untuk tema Terang/Gelap bebas *flash*, 5 gaya UI, 6 font, Mode Lite (`reduced-motion`), dialog kustom aksesibel (`AksaDialog`/`AksaConfirm`/`AksaAlert`), intersepsi konfirmasi logout, dan pemutar musik persisten lintas halaman (`window.AksaAudio`).
42. **`pengaturan_panel.php`** (`25.817 B`, `633 baris`): Partial `<aside>` panel kustomisasi tampilan sisi klien berbasis `localStorage` + pintasan `Alt+D` / `Ctrl+Shift+D`.
43. **`modal_profil_admin.php`** (`14.432 B`, `300 baris`): Partial modal edit profil & ganti sandi Admin (otomatis me-`require_once` `notifikasi_pengajuan_admin.php` di baris `300`).
44. **`notifikasi_pengajuan_admin.php`** (`28.481 B`, `833 baris`): Partial khusus Admin yang melakukan polling tiap `4` detik ke `api_cek_pengajuan_admin.php`, memutar *chime* Web Audio 4 nada, memperbarui badge sidebar, dan menampilkan pop-up pengajuan baru.

### E. Konfigurasi Server, Penyimpanan, & Aset
- **`.htaccess`** (`35 baris`): Menonaktifkan `Indexes`, memblokir akses HTTP langsung ke berkas `\.(sql|env|ini|log|bak|git|py|sh|md|json)$`, memblokir folder `database/`, `scripts/`, dan `cache/` via `RewriteRule ... [F,L]`, serta menyetel header `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `X-XSS-Protection: 1; mode=block`, dan `Referrer-Policy: strict-origin-when-cross-origin`.
- **`storage/.htaccess`** (`7 baris`): Memblokir akses web penuh (`Require all denied`) ke folder log `storage/` (tempat `mail_error.log` dan `otp_dev.log` ditulis bila terjadi galat/mode dev).
- **Aset Unggahan (`uploads/`)**: Terbagi ke 6 subdirektori aktif: `uploads/anggota/` (foto anggota), `uploads/banner/` (gambar/GIF/video banner), `uploads/gambar/` (sampul buku manual & hasil ISBN), `uploads/kartu_pengajuan/` (bukti kartu peminjaman), `uploads/musik/` (audio latar), dan `uploads/profil/` (foto profil admin).
- **Cache (`cache/`)**: Menyimpan `cache/berita_cache_v2.json` untuk cache RSS berita berdurasi 30 menit (`beranda.php:265`).

---

## 1.2 Model Aktor, Sesi, & Keamanan Aktual di Kode

### A. Tiga Aktor Sah & Guard Role
Di kode sumber hanya terdapat **2 nilai enum `users.role`** (`'member'` dan `'admin'`, `database/aksa_nova.sql:383`) ditambah pengunjung tanpa sesi (`!isset($_SESSION['user_id'])`), yang membentuk **3 aktor manusia**:
1. **Tamu (*Guest*)**: Belum login. Dapat mengakses `index.php`, `beranda.php`, `daftar_buku.php`, `buku_detail.php`, `sign_up.php`, `sign_in.php`, dan `lupa_kartu.php`. Saat mengklik Suka, Simpan, Rating, atau Ajukan Peminjaman di katalog, JS `butuhLogin()` mengarahkan ke `sign_in.php` (`beranda.php:2489-2492`).
2. **Anggota (*Member* — Siswa & Guru)**: Dibedakan melalui kolom `users.kelas` (`'Guru'` vs `'Siswa'` / nama kelas seperti `'Kelas DKP'`, `sign_up.php:42-45`). Halaman anggota penuh (`beranda.php`, `daftar_buku.php`, `buku_simpan.php`, `dashboard_user.php`, `pengajuan_peminjaman.php`) memverifikasi ke DB pada setiap request bahwa `status === 'approved'` dan `card_status !== 'frozen'`; bila melanggar, sesi langsung dihancurkan (`session_destroy()`) dan diarahkan ke `sign_in.php`. Jika Admin mencoba membuka halaman katalog/anggota tersebut, Admin diarahkan ke `dashboard.php`.
3. **Pustakawan (*Admin*)**: Wajib `isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'`. Seluruh halaman admin mengarahkan pengguna tak berhak ke `sign_in.php`, sedangkan endpoint AJAX/unduh (`api_cek_pengajuan_admin.php`, `cari_isbn.php`, `export_excel_dashboard.php`) mengembalikan HTTP `403`.

> **Catatan Celah Konsistensi Guard**: Berbeda dari halaman penuh, dua handler AJAX anggota (`like_handler.php:7-16` dan `rating_handler.php:6-17`) hanya memeriksa `$_SESSION['user_id']` dan `$_SESSION['role'] !== 'admin'` tanpa mengecek ulang ke DB apakah akun anggota baru saja dinonaktifkan (`rejected`) atau kartunya dibekukan (`frozen`).

### B. Mekanisme Keamanan yang Benar-Benar Ada vs Tidak Ada di Kode
- **Hashing Kata Sandi**: Menggunakan `password_hash(..., PASSWORD_DEFAULT)` (bcrypt `$2y$10$...`) dan `password_verify()` (`sign_up.php:196`, `sign_in.php:47`, `lupa_kartu.php:287`, `edit_kartu.php:132, 235`, `daftar_anggota.php:128`, `update_profil_admin.php:60, 71`).
- **Regenerasi ID Sesi (`session_regenerate_id(true)`)**: Dipanggil saat login berhasil (`sign_in.php:56`), saat auto-login dari kartu (`kartu_anggota.php:18`, `kartu_masuk.php:15`, `kartu_clear.php:14`), dan saat OTP berhasil diverifikasi (`lupa_kartu.php:182`).
- **Proteksi CSRF (`csrf_verify()` dengan `hash_equals`)**:
  - **TERPASANG** pada: `sign_up.php:38`, `sign_in.php:30`, `lupa_kartu.php:35`, `edit_kartu.php:97`, `pengajuan_peminjaman.php:83`, `pengajuan_buku.php:41`, `pinjam_buku.php:60`, `telah_dipinjam.php:77`, `halaman_admin.php:73`, `daftar_anggota.php:51`, `kelola_banner.php:55`, `pengaturan_denda.php:66`, `pengaturan_lokasi.php:72`, `update_profil_admin.php:23`.
  - **TIDAK TERPASANG** pada 5 pemroses aksi mutasi/keluar: `like_handler.php`, `rating_handler.php`, `pengaturan_musik.php`, `ganti_sandi_admin.php`, dan `logout.php` (meskipun `logout.php:152` merender `csrf_field()`, PHP-nya tidak memanggil `csrf_verify()` dan mengizinkan `GET ?confirm=1` di `:16`).
- **Rate Limiting**: **ADA** untuk permintaan & verifikasi OTP (`includes/otp.php:29-88, 308`), namun **TIDAK ADA** rate limiting / *account lockout* pada login `sign_in.php`.
- **Prepared Statements vs Interpolasi SQL**: Campuran keduanya. Modul autentikasi/OTP/pengajuan online (`sign_up.php`, `sign_in.php`, `lupa_kartu.php`, `includes/otp.php`, `edit_kartu.php`, `pengajuan_peminjaman.php`, `buku_detail.php`) memakai `mysqli_prepare()`, sedangkan banyak kueri admin/katalog menggunakan `mysqli_query()` dengan `(int)` casting atau `mysqli_real_escape_string()`.
- **Cookie Secure / HTTPS Enforcement**: `config.php:14-16` menyetel `cookie_httponly=1`, `use_only_cookies=1`, dan `cookie_samesite=Lax`, namun **tidak** menyetel `cookie_secure=1` dan `.htaccess` **tidak** memaksa redirect HTTP ke HTTPS (agar tetap berjalan di `localhost` Laragon).

---

## 1.3 Skema Database & Analisis Drift Dua Arah (SQL <-> Kode PHP)

Berdasarkan ekstraksi mekanis `02_schema_and_drift.py` atas `database/aksa_nova.sql` dan `database/migrations/2026_otp_reset.sql`, database `aksa_nova` memiliki tepat **12 tabel InnoDB**:

1. **`users`** (15 kolom): `id` (PK AI), `full_name` (`varchar(100)`), `nik` (`varchar(30)` default `''`), `kelas` (`varchar(50)` default `''`), `no_hp` (`varchar(20)` default `''`), `no_anggota` (`varchar(30)` UK `uq_no_anggota`), `username` (`varchar(50)` UK `username`), `email` (`varchar(100)` UK `email`), `password` (`varchar(255)`), `plain_password` (`varchar(255)` default `''`), `foto` (`varchar(255)` default `''`), `role` (`enum('admin','member')` nullable default `'member'`), `status` (`enum('pending','approved','rejected')` default `'pending'`), `card_status` (`enum('active','frozen')` default `'active'`), `created_at` (`timestamp`).
2. **`admin_profile`** (5 kolom): `id` (PK AI), `user_id` (`int` UK `uq_admin_user`, FK -> `users.id` `ON DELETE CASCADE ON UPDATE CASCADE`), `display_name` (`varchar(100)` default `'Admin'`), `foto` (`varchar(255)` default `''`), `updated_at` (`timestamp`).
3. **`password_otp`** (9 kolom — identik antara `aksa_nova.sql:348-361` dan `migrations/2026_otp_reset.sql:5-18`): `id` (PK AI), `user_id` (`int`, IDX `(user_id, created_at)`, FK -> `users.id` `ON DELETE CASCADE`, `ON UPDATE` tidak ditulis = default `RESTRICT`), `otp_hash` (`char(64)`), `expires_at` (`datetime`), `attempts` (`tinyint` default `0`), `used_at` (`datetime` nullable), `last_sent_at` (`datetime`), `created_at` (`timestamp`), `request_ip` (`varchar(45)` nullable).
4. **`buku`** (11 kolom): `id` (PK AI), `judul` (`varchar(255)`), `penulis` (`varchar(255)`), `isbn` (`varchar(50)` — bukan UK di DB), `genre` (`varchar(100)`), `sinopsis` (`text` nullable), `stok` (`int` default `0`), `rak` (`varchar(100)`), `gambar` (`varchar(500)`), `created_at`, `updated_at`.
5. **`buku_likes`** (4 kolom): `id` (PK AI), `buku_id` (FK -> `buku.id` `CASCADE/CASCADE`), `user_id` (FK -> `users.id` `CASCADE/CASCADE`), `created_at`, UK komposit `uniq_like (buku_id, user_id)`.
6. **`buku_favorites`** (4 kolom): `id` (PK AI), `buku_id` (FK -> `buku.id` `CASCADE/CASCADE`), `user_id` (FK -> `users.id` `CASCADE/CASCADE`), `created_at`, UK komposit `uniq_fav (buku_id, user_id)`.
7. **`buku_ratings`** (6 kolom): `id` (PK AI), `user_id` (FK -> `users.id` `CASCADE/CASCADE`), `buku_id` (FK -> `buku.id` `CASCADE/CASCADE`), `rating` (`tinyint`), `created_at` (`datetime`), `updated_at` (`datetime` — tanpa `ON UPDATE CURRENT_TIMESTAMP`, diupdate manual dengan `NOW()` di PHP), UK komposit `uq_user_buku (user_id, buku_id)`.
8. **`pengajuan_peminjaman`** (13 kolom): `id` (PK AI), `user_id` (FK -> `users.id` `CASCADE/CASCADE`), `buku_id` (FK -> `buku.id` `CASCADE/CASCADE`), `nama_peminjam` (`varchar(150)`), `file_kartu` (`varchar(255)`), `total_buku` (`int` default `1`), `batas_kembali` (`date`), `waktu_pengambilan` (`enum('sekarang','nanti')` default `'sekarang'`), `catatan_pengambilan` (`varchar(255)` nullable), `status` (`enum('menunggu','disetujui','ditolak')` default `'menunggu'`), `alasan_penolakan` (`text` nullable), `approved_at` (`datetime` nullable), `created_at` (`timestamp`). **Catatan penting**: Tabel ini **SUDAH memiliki FOREIGN KEY fisik** `fk_pengajuan_buku` dan `fk_pengajuan_user` di `database/aksa_nova.sql:265-266` (namun **tidak** memiliki FK ke tabel `peminjaman`).
9. **`peminjaman`** (12 kolom): `id` (PK AI), `buku_id` (FK -> `buku.id` **`ON DELETE RESTRICT ON UPDATE CASCADE`**), `user_id` (`int` **nullable**, FK -> `users.id` **`ON DELETE SET NULL ON UPDATE CASCADE`**), `nama_peminjam` (`varchar(150)`), `waktu_pinjam` (`datetime`), `batas_kembali` (`datetime`), `waktu_kembali` (`datetime` nullable), `terlambat_hari` (`int` default `0`), `denda` (`decimal(12,0)` default `0`), `status_denda` (`enum('tidak_ada','belum_bayar','lunas')` default `'tidak_ada'`), `status` (`enum('dipinjam','dikembalikan')` default `'dipinjam'`), `created_at` (`timestamp`).
10. **`reminder_log`** (7 kolom): `id` (PK AI), `peminjaman_id` (FK -> `peminjaman.id` `CASCADE/CASCADE`), `tanggal` (`date`), `terlambat_hari` (`int` default `0`), `wa_terkirim` (`tinyint(1)` default `0`), `wa_terkirim_at` (`datetime` nullable), `created_at` (`timestamp`), UK komposit `uq_pinjam_tanggal (peminjaman_id, tanggal)`.
11. **`banner`** (9 kolom): `id` (PK AI), `judul` (`varchar(150)`), `subjudul` (`varchar(255)`), `gambar` (`varchar(500)`), `link_url` (`varchar(255)`), `urutan` (`int` default `0`), `aktif` (`tinyint(1)` default `1`), `created_at`, `updated_at`.
12. **`pengaturan`** (5 kolom): `id` (PK AI), `kunci` (`varchar(100)` UK `uq_kunci`), `nilai` (`text`), `keterangan` (`varchar(255)` default `''`), `updated_at` (`timestamp`).

### Hasil Pemeriksaan Drift Dua Arah (Kode PHP <-> SQL)
- **0 tabel hantu & 0 kolom hantu**: Seluruh tabel dan kolom yang diakses oleh 44 berkas PHP benar-benar ada di `database/aksa_nova.sql`, dan seluruh 12 tabel di SQL benar-benar diakses oleh kode PHP.
- **Drift `admin_profile.user_id`**: Di SQL `admin_profile.user_id` bersifat `NOT NULL` dengan FK ke `users.id` (`aksa_nova.sql:27, 33`), tetapi cabang `INSERT INTO admin_profile (display_name, foto)` pada `update_profil_admin.php:118` mengabaikan kolom `user_id` (tidak memicu error saat ini semata-mata karena tabel sudah berisi 1 baris seed `id=1, user_id=1` sehingga eksekusi selalu masuk ke `UPDATE` di `:113-116`).
- **Kolom Warisan `users.plain_password`**: Masih ada di skema (`aksa_nova.sql:381`), tetapi seluruh fitur aktif mengisinya dengan string kosong `""` dan tidak pernah menampilkan sandi polos di kartu maupun panel admin.
- **Keunikan Aplikasi vs Database**: `users.username`, `users.email`, dan `users.no_anggota` memiliki `UNIQUE KEY` di DB, sedangkan **`users.nik` TIDAK memiliki `UNIQUE KEY` di DB** (hanya dicek via `SELECT` di `sign_up.php:153` dan `edit_kartu.php:162`), dan **`buku.isbn` TIDAK memiliki `UNIQUE KEY` di DB** (hanya memberi peringatan duplikat kuning di `cari_isbn.php:217-226` — bahkan dump SQL memiliki dua buku berbeda `id=58` dan `id=61` dengan ISBN sama).
- **Penghapusan Riwayat di Level Aplikasi lebih ketat dari FK DB**: Meskipun `peminjaman.user_id` memakai `ON DELETE SET NULL` di DB, `daftar_anggota.php:84-88` menolak penghapusan anggota bila `COUNT(*) FROM peminjaman WHERE user_id=$id > 0`. Demikian pula `halaman_admin.php:143-149` menolak penghapusan buku bila ada riwayat `peminjaman` (selaras dengan `ON DELETE RESTRICT`).

---

## 1.4 Narasi Alur Bisnis Penuh (Ditulis Langsung dari Kode)

### 1. Registrasi Mandiri Anggota (`sign_up.php`)
- Hanya dapat diakses pengunjung yang belum login (`sign_up.php:7-10`). Setiap pemuatan halaman menghapus berkas `uploads/anggota/temp_*` yang berumur `> 7200` detik (2 jam, `:28-34`).
- Pengguna memilih `tipe_anggota` (`siswa` -> `kelas = "Siswa"`, atau `guru` -> `kelas = "Guru"`, `:41-45`), mengisi `full_name`, `nik` (Siswa wajib digit `/^[0-9]{3,20}$/`; Guru wajib minimal `3` karakter, `:57-68`), `no_hp` (`/^[\\d+\\-\\s]{8,20}$/`, `:71-75`), `email` (wajib berakhiran `@student.smkn1rongga.sch.id`, `@smkn1rongga.sch.id`, atau `@gmail.com`, `:77-87`), `password` + konfirmasi (minimal `8` karakter, wajib memuat huruf dan angka, `:89-96`), serta opsional unggah `foto` (`jpg/png/webp`, MIME terverifikasi, maks `5 MB`, disangga ke `temp_*` bila field lain gagal validasi, `:99-139`).
- Setelah validasi format lolos, sistem mengecek keunikan `email` dan `nik` di tabel `users` (`:143-162`).
- Jika unik: foto `temp_*` diganti nama menjadi `uploads/anggota/anggota_<16hex>.<ext>`, `username` dibangkitkan otomatis dari huruf-angka kecil `full_name` (ditambah akhiran angka `1, 2, ...` bila bentrok, `:179-193`), sandi di-hash `PASSWORD_DEFAULT`, dan `no_anggota` dibangkitkan dengan pola `AN-YYYY-NNNNN` unik (`:199-210`).
- Baris baru dimasukkan ke `users` dengan **`role = 'member'` dan `status = 'approved'`** secara langsung (`:213-222`) — **tidak melewati antrean `pending`**. Sistem menyimpan `$_SESSION['kartu_data']` (`is_first_creation => true`, belum menyetel `$_SESSION['user_id']`) lalu mengarahkan ke `kartu_anggota.php` (`:229-246`).

### 2. Login & Logout (`sign_in.php`, `logout.php`)
- **Login**: Memverifikasi `csrf_verify()` (`sign_in.php:30`), mencari `WHERE email = ? OR username = ?` (`:40`), lalu memverifikasi `password_verify()` (`:47`). Bila cocok, sistem mengecek 3 kondisi blokir: `role === 'member' && status === 'pending'`, `role === 'member' && status === 'rejected'`, serta `card_status === 'frozen'` (`:48-54`). Bila lolos, sistem memanggil `session_regenerate_id(true)`, menyetel `$_SESSION['user_id']`, `user_name`, `username`, `role`, lalu mengarahkan Admin ke `dashboard.php` atau Anggota ke `$safe_redirect ?: 'beranda.php'` (`:56-67`).
- **Logout**: Di seluruh halaman yang menyertakan `settings_include.php`, klik tautan `logout.php` dicegat oleh dialog `AksaConfirm` (`settings_include.php:289-352`) yang men-submit `POST confirm_logout=1` ke `logout.php`. Jika diakses langsung via GET tanpa `?confirm=1`, `logout.php` menampilkan halaman konfirmasi HTML (`:140-158`). Saat dikonfirmasi, sesi dikosongkan, cookie sesi dihapus, `session_destroy()` dijalankan, dan diarahkan ke `sign_in.php` (`:19-37`).

### 3. Pemulihan Akun / Lupa Kartu via OTP Email (`lupa_kartu.php`, `includes/otp.php`, `includes/mailer.php`)
- **Langkah 1 — Permintaan OTP (`action = request_otp`)**:
  - Memverifikasi CSRF (`lupa_kartu.php:35`), format email (`:75`), dan batas IP (`otp_batas_permintaan`: maks **10 permintaan/jam per IP**, `includes/otp.php:33-47`).
  - Mencari akun di `users`; syarat layak adalah `role === 'member' && status === 'approved'` (`lupa_kartu.php:98-101`).
  - **Proteksi Anti-Enumerasi**: Jika email tidak terdaftar, milik Admin, atau berstatus `pending`/`rejected`, sistem melakukan jeda acak `150–250 ms` (`usleep`), membuat sesi dummy (`is_dummy => true`, `step => 2`), dan menampilkan halaman input OTP dengan pesan generik yang sama persis (`lupa_kartu.php:135-150`).
  - Jika akun layak: sistem mengecek *cooldown* **60 detik** sejak pengiriman terakhir dan batas **5 permintaan/jam per user** (`includes/otp.php:54-83`). `otp_buat()` menghapus baris OTP kedaluwarsa `> 1 hari`, menggugurkan OTP aktif sebelumnya milik user (`SET expires_at = NOW()`), membangkitkan 6 digit acak `random_int(0, 999999)`, menghitung `hash_hmac('sha256', $otp_raw, OTP_SECRET)`, dan menyimpan ke `password_otp` dengan masa berlaku **10 menit (600 detik)** (`includes/otp.php:102-125`).
  - `otp_kirim()` mengirim email HTML via `PHPMailer` (`smtp.gmail.com:587` TLS, timeout `10s`; atau menulis ke `storage/otp_dev.log` hanya bila `OTP_DEV_MODE === true` **dan** diakses dari localhost/CLI, `includes/otp.php:145-172`). Bila pengiriman SMTP gagal, `kirim_email()` menyensor sandi & 6 digit kode di `storage/mail_error.log` lalu **menghapus kembali (`DELETE`) baris OTP terbaru** agar kuota & *cooldown* pengguna tidak terpotong (`includes/mailer.php:90-131`). **Kartu belum dibekukan pada tahap ini.**
- **Langkah 2 — Verifikasi OTP (`verify_otp`, `resend_otp`, `change_email`)**:
  - Input wajib 6 digit angka (`includes/otp.php:258`). Untuk sesi asli, sistem mengecek berurutan: baris ada, `used_at === null`, belum melewati `expires_at`, dan `attempts < 5` (`:284-314`). Hash dibandingkan dengan `hash_equals()` (`:319`).
  - Bila salah: `attempts` dinaikkan `+1`; pada kesalahan ke-5 kode OTP dinyatakan hangus (`burned`, sisa percobaan `0`, `:336-352`).
  - Bila benar: `used_at = NOW(), attempts = attempts + 1` (`:321`), memanggil `session_regenerate_id(true)`, **membekukan kartu lama (`UPDATE users SET card_status = 'frozen'`)** (`lupa_kartu.php:182-190`), dan masuk ke `step = 3` (`otp_verified_at = time()`).
- **Langkah 3 — Atur Kata Sandi Baru & Terbitkan Kartu (`set_password`, `cancel`)**:
  - Jika pengguna menekan **Batalkan** (`action = cancel`), `card_status` dikembalikan menjadi `'active'` dan sesi pemulihan dihapus (`lupa_kartu.php:43-57`).
  - Saat submit `set_password`, jika waktu sejak `otp_verified_at` melebihi **600 detik (10 menit)**, `card_status` dicairkan kembali ke `'active'` dan dialihkan ke `lupa_kartu.php?expired=1` (`:262-274`). *(Catatan: bila pengguna menutup tab begitu saja di Langkah 3 tanpa submit/cancel, `card_status` tetap `'frozen'` hingga pengguna mengulang/membatalkan pemulihan atau dicairkan Admin di `daftar_anggota.php`).*
  - Sandi baru divalidasi (min `8` karakter, huruf + angka, cocok konfirmasi), di-hash `PASSWORD_DEFAULT`, lalu disimpan via `UPDATE users SET password = ?, plain_password = '', card_status = 'active'` (`:279-297` — `no_anggota` dan `username` tetap dipertahankan). Sesi `kartu_data` diisi dengan `reissued => true` lalu diarahkan ke `kartu_anggota.php` (`:300-320`).

### 4. Kartu Anggota Digital (`kartu_anggota.php`, `barcode128.php`, `kartu_masuk.php`, `kartu_clear.php`)
- Menampilkan kartu ukuran `428x270` px yang memuat foto profil, nama lengkap, status Guru/NIP atau Siswa/NIS, `username`, masa berlaku (`"Selama Bertugas"` bagi Guru atau `"s/d " . (tahun_daftar + 3)` bagi Siswa, `kartu_anggota.php:111-118`), serta **Barcode Code 128 SVG** dari `no_anggota` (`:696`).
- Tombol unduh mengekspor elemen `#kartu` menjadi PNG beresolusi tinggi (`scale: 2.362`, ~`1011x638` px / 300 DPI) menggunakan `html2canvas` (`:721-750`).
- Tombol selesai mengirim beacon ke `kartu_clear.php` dan mengarahkan pengguna baru/pemulihan ke `kartu_masuk.php` yang mengaktifkan sesi login (`session_regenerate_id(true)`, set `$_SESSION['user_id']`, dll.) lalu masuk ke `beranda.php`.

### 5. Edit Data Diri & Ganti Sandi Anggota (`edit_kartu.php`)
- Hanya untuk `role === 'member'` berstatus `approved` dan kartu tidak `frozen` (`edit_kartu.php:12-49`).
- Memvalidasi CSRF (`:97`), `full_name` (min `2` karakter), `username` (`/^[a-zA-Z0-9._]{3,30}$/`), `nik`, `email` (3 domain diizinkan), `no_hp` (opsional, `6–20` karakter bila diisi), keunikan `username`/`nik`/`email` (`AND id <> ?`), serta unggah foto baru opsional (`jpg/png/webp`, maks `5 MB`, menghapus foto lama via `@unlink`, `:186-263`).
- Jika kolom sandi baru diisi, wajib memverifikasi `old_password` (`password_verify`, atau fallback `plain_password`), minimal 8 karakter huruf+angka, dan **berbeda dari sandi lama** (`:130-145`). Seusai simpan, mengarahkan ke `kartu_anggota.php` (`data_updated_only => true`).

### 6. Penjelajahan Katalog, Detail Buku, & Berita RSS (`index.php`, `beranda.php`, `daftar_buku.php`, `buku_detail.php`)
- Pencarian di `daftar_buku.php` memfilter `judul`, `penulis`, atau `isbn` (`LIKE`), serta `genre` eksak, dengan paginasi `10` buku/halaman dan *live search* debounce `300 ms` via `?ajax=1`.
- Klik kartu buku membuka modal detail yang memanggil `GET buku_detail.php?id=...` (mendukung ID biasa maupun token `mask_id`) dan menampilkan stok (`Kosong` `<=0`, `Terbatas` `1..3`, `Tersedia` `>3`), lokasi `rak`, sinopsis, rata-rata rating, total suka, dan tombol tindakan.
- Di `beranda.php`, sistem mengambil RSS XML dari **detikcom** dan **CNN Indonesia** (timeout 4 detik), menyilang 9 berita terbaru, dan menyimpannya ke `cache/berita_cache_v2.json` selama `1800` detik (30 menit).

### 7. Suka, Simpan/Favorit, & Rating Bintang (`like_handler.php`, `buku_simpan.php`, `rating_handler.php`)
- **Suka & Favorit**: `POST like_handler.php` (`buku_id`, `type='like'|'favorite'`) melakukan *toggle* `INSERT` / `DELETE` pada `buku_likes` atau `buku_favorites` dan mengembalikan jumlah terbaru (`like_handler.php:29-48`). Daftar favorit dilihat dan dicari di `buku_simpan.php`.
- **Rating**: `POST rating_handler.php` (`buku_id`, `rating` bulat `1..5`) melakukan *upsert* ke `buku_ratings` dan mengembalikan rata-rata 1 desimal serta total pemberi nilai (`rating_handler.php:47-74`). Tidak ada kolom komentar/ulasan teks dan tidak ada syarat harus pernah meminjam buku.

### 8. Pengajuan Peminjaman Online & Notifikasi Realtime Admin (`pengajuan_peminjaman.php`, `api_cek_pengajuan_admin.php`, `notifikasi_pengajuan_admin.php`)
- Anggota (`approved` & `active`) memilih buku berstok `> 0`, mengisi `total_buku` (**`1` s/d `40` eksemplar** dan `<= stok`, tanpa pembedaan kuota Siswa vs Guru di kode, `pengajuan_peminjaman.php:128-134`), memilih `batas_kembali` (`>= hari ini` di PHP; default H+7 dan min H+1 di HTML, tanpa batas maksimum hari di server, `:134-137, 924-926`), memilih jadwal pengambilan (`sekarang` atau `nanti` beserta tanggal & slot jam istirahat/pulang sekolah), serta wajib mengunggah bukti foto kartu anggota (`jpg/jpeg/png/webp`, maks `5 MB`, lolos `getimagesize()`, disimpan ke `uploads/kartu_pengajuan/`, `:138-176`).
- Menyimpan baris ke `pengajuan_peminjaman` berstatus `'menunggu'` tanpa mengurangi stok buku (`:178-188`).
- Di seluruh halaman Admin, `notifikasi_pengajuan_admin.php` melakukan polling setiap **4 detik** ke `api_cek_pengajuan_admin.php`; bila ada `latest_id` baru yang belum dilihat di `localStorage`, muncul modal pop-up realtime disertai bunyi *chime* 4 nada Web Audio dan pembaruan angka badge sidebar.

### 9. Verifikasi Pengajuan oleh Admin (`pengajuan_buku.php`)
- **`setujui`**: Mengecek pengajuan masih `'menunggu'` dan stok mencukupi, mengurangi stok secara atomik (`UPDATE buku SET stok = stok - $total_buku WHERE id = $buku_id AND stok >= $total_buku`, `pengajuan_buku.php:68-70`), menyisipkan **$total_buku baris terpisah** (1 baris per eksemplar) ke tabel `peminjaman` dengan `batas_kembali = YYYY-MM-DD 23:59:59` dan `status = 'dipinjam'` (`:72-81`), lalu mengubah pengajuan menjadi `'disetujui'` beserta `approved_at = NOW()` (`:84-85`).
- **`tolak`**: Mengubah status pengajuan menjadi `'ditolak'`, menyimpan `alasan_penolakan` (default `'Pengajuan ditolak oleh petugas perpustakaan.'`) dan `approved_at` (`:95-110`).
- **`hapus_riwayat` & `bersihkan_riwayat`**: Menghapus berkas fisik `file_kartu` (`@unlink`) dan menghapus baris `pengajuan_peminjaman` satuan atau massal (`disetujui`/`ditolak`/`semua_selesai`, `:111-156`).

### 10. Peminjaman Langsung di Meja Sirkulasi (`pinjam_buku.php`)
- Admin memilih buku berstok `> 0`, memilih anggota (`role='member' AND status='approved' AND card_status != 'frozen'`, `pinjam_buku.php:73-82`), dan menentukan `batas_kembali` (default H+7, ditambah `' 23:59:59'`, `:93-95`).
- Mengurangi stok `1` eksemplar secara atomik (`UPDATE buku SET stok = stok - 1 WHERE id = $buku_id AND stok > 0`) dan menyisipkan 1 baris ke `peminjaman` (`:100-107`).

### 11. Pengembalian Buku, Perhitungan Denda, Pengingat WA, & Cron (`telah_dipinjam.php`, `pengaturan_denda.php`, `cron_cek_terlambat.php`, `dashboard_user.php`)
- **Rumus Denda**: Membaca `denda_aktif`, `denda_per_hari`, dan `denda_grace_period` dari tabel `pengaturan` (nilai saat ini di dump SQL: aktif `1`, tarif `Rp 1.500`/hari, masa toleransi `2` hari). Saat waktu pengembalian melampaui `batas_kembali`:
  - `hari = (int)$batas->diff($kembali)->days` (karena `batas_kembali` pukul `23:59:59`, keterlambatan `< 24 jam` penuh menghasilkan `days === 0` sehingga denda `Rp 0`),
  - `hari_denda = max(0, hari - grace_period)`,
  - `total_denda = denda_aktif ? (hari_denda * denda_per_hari) : 0` (`telah_dipinjam.php:55-69`).
- **Aksi `kembalikan`**: Mengubah `peminjaman` menjadi `status='dikembalikan'`, mengisi `waktu_kembali`, `terlambat_hari`, `denda`, dan `status_denda` (`'belum_bayar'` jika `total_denda > 0`, selain itu `'tidak_ada'`), serta menambah kembali `buku.stok + 1` (`telah_dipinjam.php:82-124`).
- **Pelunasan Denda (`tandai_lunas`)**: Tersedia baik di `telah_dipinjam.php:144-153` maupun `pengaturan_denda.php:115-124`, mengubah `status_denda` dari `'belum_bayar'` menjadi `'lunas'`.
- **Pengingat WhatsApp (`wa.me` + `tandai_wa`)**: Tombol `https://wa.me/62...` di `telah_dipinjam.php:960-980` hanya muncul untuk pinjaman yang masih `'dipinjam'`, sudah melewati `batas_kembali`, dan memiliki `users.no_hp`. Saat diklik, JS mengirim `POST action=tandai_wa` yang melakukan *upsert* `wa_terkirim = 1, wa_terkirim_at = NOW()` ke `reminder_log` untuk tanggal hari ini (`:132-143`).
- **Cron CLI (`cron_cek_terlambat.php`)**: Dijalankan via CLI untuk memindai pinjaman `'dipinjam'` dengan `batas_kembali < NOW()` dan meng-*upsert* `(peminjaman_id, CURDATE(), terlambat_hari)` ke `reminder_log` (tidak mengirim pesan WA/email otomatis).

### 12. Kelola Katalog Buku & Pencarian ISBN (`halaman_admin.php`, `cari_isbn.php`)
- Admin dapat menambah, mengedit, dan menghapus buku (beserta sampul maks `5 MB` `jpg/jpeg/png/webp/gif` dari file atau tangkapan kamera `getUserMedia`). Penghapusan buku ditolak bila terdapat baris riwayat apa pun di tabel `peminjaman` (`halaman_admin.php:143-149`).
- Fitur **Cari ISBN** (`cari_isbn.php`) menerima ISBN 10/13 digit, mengecek duplikat lokal di tabel `buku` (sebagai peringatan non-blokir), lalu melakukan pengayaan progresif 4 tahap: **(1) Google Books API** -> **(2) Open Library Books API** -> **(3) Open Library Search API** -> **(4) Open Library Edition/Work API**, serta mengunduh gambar sampul (`>= 500` byte) ke `uploads/gambar/buku_isbn_...jpg`.

### 13. Kelola Anggota oleh Admin (`daftar_anggota.php`)
- Menyediakan tab filter (`all`, `siswa`, `guru`, `approved`, `rejected`, dan `pending` bila ada) serta pencarian langsung (`?ajax=1`).
- Aksi mutasi yang tersedia: **`approve`** (aktifkan/aktifkan kembali), **`reject`** (tolak/nonaktifkan), **`unfreeze`** (cairkan kartu yang membeku akibat proses Lupa Kartu — tidak ada fitur *freeze* oleh admin), **`reset_password`** (ubah sandi manual atau acak 8 karakter, mengosongkan `plain_password`, ditampilkan sekali di banner), dan **`hapus`** (ditolak bila anggota punya riwayat di `peminjaman`, menghapus file foto lama). Tersedia pula tautan lihat kartu (`kartu_anggota.php?id=mask_id(...)`) dan chat `wa.me`. Tidak ada fitur manajemen kelas atau kenaikan kelas.

### 14. Pengaturan Banner, Musik, Lokasi/Jam, Tarif Denda, Profil Admin, Tema, & Laporan Dashboard
- **Banner (`kelola_banner.php`)**: Tambah/edit/hapus/toggle/geser urutan/jadikan background utama (`banner_background_id`), mendukung gambar/`gif` maks `5 MB` (dengan *crop* `16:7` di klien untuk gambar non-GIF) dan video `mp4/webm` maks `20 MB`.
- **Musik (`pengaturan_musik.php` + `settings_include.php`)**: Unggah audio `mp3/ogg/wav/m4a` maks `15 MB` ke `uploads/musik/`, simpan ke kunci `musik_file`, `musik_judul`, `musik_aktif`. Diputar lintas halaman oleh `window.AksaAudio` dengan sinkronisasi detik `localStorage`.
- **Lokasi & Jam (`pengaturan_lokasi.php`)**: Mengelola 20 kunci di tabel `pengaturan` untuk peta Google Maps (`query` vs `embed`), alamat/kontak sekolah, mode operasional (`otomatis`/`buka_manual`/`tutup_libur`), dan jam layanan Senin–Minggu yang dirender di `index.php`.
- **Profil & Sandi Admin (`modal_profil_admin.php` -> `update_profil_admin.php`)**: Mengubah `display_name` & foto profil (`uploads/profil/`, maks `5 MB`) di `admin_profile` serta opsional mengganti sandi Admin di `users` (min 8 karakter, huruf+angka, verifikasi sandi lama, berbeda dari sandi lama).
- **Dashboard & Ekspor (`dashboard.php`, `export_excel_dashboard.php`)**: Menampilkan statistik perpustakaan, rekap bulanan per tahun (`2000..2100`), cetak halaman, berbagi ringkasan ke `wa.me`, dan unduh berkas `Laporan_AKSANOVA_{tahun}_...xls` berformat XML Spreadsheet 2003 (3 worksheet: `Rekap Bulanan`, `Ringkasan Perpustakaan`, `Buku Terpopuler` Top 10; terdapat bug variabel belum terdefinisi `$total_tahun_pinjam` di `export_excel_dashboard.php:273`).
- **Panel Tema (`pengaturan_panel.php` + `settings_include.php`)**: Menyimpan mode Terang/Gelap (`Alt+D`), 5 gaya antarmuka, 6 font, dan Mode Lite sepenuhnya di `localStorage` peramban tanpa menyentuh database.
