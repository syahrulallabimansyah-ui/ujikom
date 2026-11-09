# LAPORAN AUDIT v2: SOFTWARE REQUIREMENTS SPECIFICATION (SRS)
**Proyek:** AKSA NOVA — Sistem Informasi Perpustakaan Digital SMK Negeri 1 Rongga  
**Tanggal Audit:** 2026-11-08  
**Auditor:** Software Auditor & System Analyst (Audit v2 — Clean Room & Read-Only)  
**Status Audit:** SELESAI (Audit Read-Only)  

---

## 1. Ringkasan Eksekutif SRS

Audit terhadap dokumen Spesifikasi Kebutuhan Perangkat Lunak (SRS) AKSA NOVA mencakup:
1. File dokumen utama: [`SRS/SRS_AKSA_NOVA.docx`](file:///c:/laragon/www/aksanova/SRS/SRS_AKSA_NOVA.docx) (310.020 byte, 2026-11-03 08:47:19, 521 paragraf, 9 tabel).
2. File ekspor PDF lama: [`SRS/SRS_AKSA_NOVA.pdf`](file:///c:/laragon/www/aksanova/SRS/SRS_AKSA_NOVA.pdf) (667.555 byte, 2026-10-23 11:02:58, 28 halaman).
3. Gambar tertanam di DOCX: `word/media/image1.png` (860.916 byte, diagram use case).

**Sumber Kebenaran (Ground Truth):**
- Seluruh 44 file source code PHP ([`docs/audit_v2/02_fakta_kode.md`](file:///c:/laragon/www/aksanova/docs/audit_v2/02_fakta_kode.md)).
- Skema database fisik DDL ([`database/aksa_nova.sql`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql) & [`database/migrations/2026_otp_reset.sql`](file:///c:/laragon/www/aksanova/database/migrations/2026_otp_reset.sql)).
- Hasil pemindaian rujukan non-existent ([`docs/audit_v2/nonexistent_refs.json`](file:///c:/laragon/www/aksanova/docs/audit_v2/nonexistent_refs.json)).

**Hasil Keseluruhan:**
Dokumen SRS AKSA NOVA memiliki cakupan fungsional inti yang kaya, namun mengalami **desinkronisasi parah antarversi (PDF vs DOCX)** serta **kontradiksi internal internal yang fatal di dalam file DOCX itu sendiri**:
- File `.pdf` adalah versi usang sebelum implementasi OTP (masih versi 1.7/2.2 per 23 Oktober 2026, 11 tabel, FR-04 memakai password lama).
- File `.docx` memiliki dua matriks ketertelusuran yang saling bertentangan: **Tabel 7.1** valid memetakan FR-01..FR-25 ke file kode riil, namun **Tabel 7.2 (ditaruh setelah Bab 8)** memetakan FR-07..FR-24 ke fitur yang berbeda dan mencantumkan **22 path file fiktif/non-existent** (`admin/*.php`, `api/*.php`, `katalog.php`, `detail_buku.php`).
- Terjadi kontradiksi internal antara narasi awal Bab 1-2 (mengklaim 11 tabel, kode QR, dan bullet password pada kartu) dengan spesifikasi Bab 3-4 (mengklaim 12 tabel, barcode Code 128, dan larangan menampilkan password di kartu).
- Kamus Data Bab 6.3 mengklaim *"12 Tabel Basis Data"*, namun secara nyata **hanya mendefinisikan 11 tabel** (`password_otp` sama sekali tidak memiliki definisi kamus data).
- Sejumlah klaim NFR teknis (100% prepared statement, CSRF di semua form, mask_id di semua URL publik) terbukti **tidak sesuai fakta implementasi kode**.

---

## 2. Cakupan Terukur (Measurable Coverage)

| Kategori Elemen SRS | Jumlah Diperiksa | BENAR | BENAR DENGAN CATATAN | SALAH | TIDAK TERVERIFIKASI | TIDAK ADA DI KODE | Keterangan Singkat |
|---|:---:|:---:|:---:|:---:|:---:|:---:|---|
| **Definisi Aktor (Bab 2.3)** | 3 | 2 | 1 | 0 | 0 | 0 | 3 aktor sah; UC-19 salah memasukkan `Sistem` sbg aktor |
| **Kebutuhan Fungsional (FR-01..FR-25)** | 25 | 18 | 4 | 2 | 0 | 1 | FR-05 (freeze admin) tidak ada di kode; FR-07 unmasked URL |
| **Kebutuhan Non-Fungsional (NFR)** | 24 | 14 | 3 | 7 | 0 | 0 | NFR-SEC-02, SEC-05, SEC-08, PERF-03, USE-04, USE-09 salah |
| **Antarmuka Eksternal (Bab 3.2)** | 4 | 3 | 1 | 0 | 0 | 0 | Barcode SVG, WA, Google Books, SMTP dianalisis detail |
| **Spesifikasi Alur Use Case (Bab 3.3)** | 25 | 17 | 5 | 3 | 0 | 0 | UC-16 klaim freeze, UC-19 aktor Sistem, UC-22 nama sheet Excel |
| **Kamus Data Basis Data (Bab 6.3)** | 12 | 10 | 1 | 1 | 0 | 0 | Tabel password_otp hilang; 18 key pengaturan halusinasi |
| **Matriks Ketertelusuran (Bab 7)** | 2 | 1 | 0 | 1 | 0 | 0 | Matriks 7.1 BENAR; Matriks 7.2 SALAH FATAL (22 file fiktif) |
| **Batasan Arsitektur (Bab 8)** | 6 | 4 | 0 | 2 | 0 | 0 | Klaim pengajuan tanpa FK fisik & check_notif.php fiktif |
| **Sinkronisasi Antar-Format (PDF vs DOCX)**| 1 | 0 | 0 | 1 | 0 | 0 | PDF kedaluwarsa 11 hari sebelum DOCX (pra-OTP) |
| **TOTAL AKUMULATIF** | **102** | **69 (67,6%)** | **15 (14,7%)** | **17 (16,7%)** | **0 (0,0%)** | **1 (1,0%)** | Memerlukan revisi dan perapian mayor |

---

## 3. Elemen yang Terverifikasi BENAR

Berdasarkan pencocokan langsung klausul SRS dengan implementasi source code PHP:

1. **Autentikasi & Registrasi Anggota (FR-01, FR-02):**
   - Registrasi mandiri siswa/guru via [`sign_up.php`](file:///c:/laragon/www/aksanova/sign_up.php) dengan status awal `pending` diverifikasi benar.
   - Login menggunakan username atau email dan verifikasi password Bcrypt terbukti benar di [`sign_in.php:40-56`](file:///c:/laragon/www/aksanova/sign_in.php#L40-L56).

2. **Pencarian Buku & Detail Informasi (FR-08, FR-09):**
   - Pencarian real-time/AJAX berdasarkan judul, penulis, ISBN, dan kategori di [`daftar_buku.php:322-380`](file:///c:/laragon/www/aksanova/daftar_buku.php#L322-L380) dan [`beranda.php:120-210`](file:///c:/laragon/www/aksanova/beranda.php#L120-L210).
   - Penampilan detail buku lengkap dengan status ketersediaan stok fisik di [`buku_detail.php:45-120`](file:///c:/laragon/www/aksanova/buku_detail.php#L45-L120).

3. **Interaksi Sosial Buku (FR-10, FR-11, FR-12):**
   - Fitur rating bintang 1-5 dan ulasan teks anggota di [`rating_handler.php:25-95`](file:///c:/laragon/www/aksanova/rating_handler.php#L25-L95).
   - Fitur like dan bookmark/favorit buku dengan toggle status di [`like_handler.php:20-88`](file:///c:/laragon/www/aksanova/like_handler.php#L20-L88).

4. **Sirkulasi Peminjaman & Pengembalian Admin (FR-15, FR-16, FR-17, FR-18):**
   - Approval/rejection pengajuan peminjaman daring oleh pustakawan di [`pengajuan_buku.php:50-130`](file:///c:/laragon/www/aksanova/pengajuan_buku.php#L50-L130).
   - Pencatatan peminjaman langsung di meja sirkulasi di [`pinjam_buku.php:40-110`](file:///c:/laragon/www/aksanova/pinjam_buku.php#L40-L110).
   - Pengembalian buku, kalkulasi denda otomatis per hari terlambat, dan penandaan denda lunas di [`telah_dipinjam.php:50-165`](file:///c:/laragon/www/aksanova/telah_dipinjam.php#L50-L165).

5. **Manajemen Katalog Buku & Integrasi Google Books ISBN (FR-19, FR-20, FR-21):**
   - CRUD katalog buku oleh pustakawan di [`halaman_admin.php:100-340`](file:///c:/laragon/www/aksanova/halaman_admin.php#L100-L340).
   - Pengambilan metadata otomatis via Google Books API v1 berdasarkan ISBN di [`halaman_admin.php:38-95`](file:///c:/laragon/www/aksanova/halaman_admin.php#L38-L95).

6. **Pengingat WhatsApp Pustakawan (FR-23):**
   - Pustakawan dapat membuka tautan WhatsApp API (`wa.me`) dengan pesan otomatis terisi untuk anggota yang terlambat mengembalikan buku di [`telah_dipinjam.php:380-420`](file:///c:/laragon/www/aksanova/telah_dipinjam.php#L380-L420).

7. **Matriks Ketertelusuran Tabel 7.1:**
   - Tabel 7.1 pada Bab 7 DOCX memetakan `FR-01` s/d `FR-25` secara akurat ke file-file PHP yang memang ada di root proyek (`sign_in.php`, `daftar_buku.php`, `lupa_kartu.php`, dll.).

---

## 4. Tabel Temuan Masalah & Ketidaksesuaian SRS

| ID | Dokumen + Lokasi | Isi Dokumen | Fakta Kode | Bukti Path:Baris | Keparahan | Perbaikan yang Disarankan |
|---|---|---|---|---|:---:|---|
| **[SRS-01]** | `SRS/SRS_AKSA_NOVA.docx` vs `SRS/SRS_AKSA_NOVA.pdf` | File PDF bertanggal 23 Oktober 2026 (Header v2.2, Halaman 28 mencatat v1.7). File DOCX bertanggal 03 November 2026. File PDF tidak memiliki modul OTP, hanya mendokumentasikan 11 tabel, dan FR-04 masih mendeskripsikan reset kata sandi menggunakan email dan verifikasi kata sandi lama. | Modul reset sandi kartu anggota telah dimigrasi total ke sistem OTP 6-digit email via PHPMailer + tabel `password_otp` sejak awal November 2026. PDF sangat usang dan tidak sinkron dengan kode aktif. | [`lupa_kartu.php:1-320`](file:///c:/laragon/www/aksanova/lupa_kartu.php#L1-L320), [`database/migrations/2026_otp_reset.sql:1-18`](file:///c:/laragon/www/aksanova/database/migrations/2026_otp_reset.sql#L1-L18) | **Kritis** | Hapus PDF lama atau ekspor ulang dokumen DOCX versi terbaru menjadi PDF resmi agar tidak terjadi ambiguitas dokumen legal/audit. |
| **[SRS-02]** | `SRS/SRS_AKSA_NOVA.docx` Bab 7 (Tabel 7.2 `[T09]`) | Tabel 7.2 ("Matriks Ketertelusuran Kebutuhan Baru") diletakkan setelah Bab 8. Tabel ini mendefinisikan ulang FR-07 s/d FR-24 dengan nama fitur yang berbeda dari Bab 3, dan mencantumkan **22 path file kode yang sama sekali tidak ada di proyek** (`admin/buku.php`, `admin/kategori.php`, `admin/peminjaman.php`, `admin/pengaturan.php`, `admin/users.php`, `admin/laporan.php`, `api/buku.php`, `api/kategori.php`, `api/statistik.php`, `katalog.php`, `detail_buku.php`, `includes/denda.php`, dll.). | Seluruh arsitektur aplikasi AKSA NOVA bersifat flat di root directory (`halaman_admin.php`, `daftar_anggota.php`, `telah_dipinjam.php`, dll.). Tidak ada folder `admin/` atau `api/`, dan tidak ada file modular `katalog.php` atau `includes/denda.php`. | [`docs/audit_v2/nonexistent_refs.json`](file:///c:/laragon/www/aksanova/docs/audit_v2/nonexistent_refs.json) (22 file non-existent dari SRS) | **Kritis** | Hapus seluruh Tabel 7.2 (`[T09]`) dari dokumen Word. Pertahankan hanya Tabel 7.1 (`[T08]`) yang sudah memetakan file secara akurat. |
| **[SRS-03]** | `SRS/SRS_AKSA_NOVA.docx` Paragraf P079, P107, P117, P151, P156, P198, Tabel T02 vs Bab 3-4 | Pada narasi Bab 1 dan Bab 2, dokumen berulang kali menyatakan bahwa kartu anggota menggunakan **"Kode QR"**, sistem memiliki **"11 Tabel"**, dan kartu anggota fisik menampilkan **"password tersembunyi berformat bullet (••••••)"**. Sebaliknya, pada Bab 3-4 (FR-03, NFR-USE-09, NFR-PRIV-01), dokumen menyatakan kartu menggunakan **Barcode Code 128**, sistem memiliki **12 Tabel**, dan password sama sekali tidak boleh dicetak pada kartu. | Implementasi kode riil menggunakan pure-PHP barcode Code 128 SVG (`barcode128.php`), 12 tabel database aktif (`aksa_nova.sql`), dan kartu anggota sama sekali tidak mencetak baris password demi privasi (`kartu_anggota.php:680-720`). Teks Bab 1-2 adalah residu draf lama. | [`kartu_anggota.php:108, 696`](file:///c:/laragon/www/aksanova/kartu_anggota.php#L108), [`includes/barcode128.php:1-71`](file:///c:/laragon/www/aksanova/includes/barcode128.php#L1-L71) | **Tinggi** | Lakukan pembersihan menyeluruh pada Bab 1 dan Bab 2 di file DOCX: ganti seluruh kata "Kode QR" menjadi "Barcode Code 128", ganti "11 tabel" menjadi "12 tabel", dan hapus klaim password bullet pada kartu. |
| **[SRS-04]** | `SRS/SRS_AKSA_NOVA.docx` Bab 6.3 (Kamus Data, P432) | Judul subbab 6.3 tertulis *"6.3 Struktur Basis Data (12 Tabel)"*. Namun, tabel rincian kamus data yang disajikan di bawahnya **hanya berjumlah 11 tabel**. Tabel `password_otp` yang merupakan tabel ke-12 sama sekali tidak dicantumkan di kamus data. | Tabel `password_otp` aktif di database dengan 8 kolom (`id`, `user_id`, `otp_hash`, `expires_at`, `attempts`, `used_at`, `ip_address`, `created_at`). | [`database/aksa_nova.sql:228-241`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql#L228-L241) | **Tinggi** | Tambahkan tabel rincian Kamus Data untuk tabel `password_otp` lengkap dengan tipe data, nullability, dan deskripsi fungsinya pada Bab 6.3. |
| **[SRS-05]** | `SRS/SRS_AKSA_NOVA.docx` Bab 6.3 (Tabel Pengaturan, P433) | Deskripsi kunci konfigurasi pada tabel `pengaturan` di Kamus Data mencantumkan **18 key yang tidak ada di kode**: `toleransi_hari`, `enable_auto_reminder`, `lokasi_sekolah`, `lokasi_map_*`, `jam_buka_senin_kamis`, `jam_buka_jumat`, `jam_tutup_*`, dll. | File konfigurasi dan halaman admin menggunakan key riil berikut: `denda_per_hari`, `maks_pinjam_hari`, `maks_buku_pinjam`, `jam_buka_weekday`, `jam_tutup_weekday`, `jam_buka_jumat`, `jam_tutup_jumat`, `jam_buka_sabtu`, `jam_tutup_sabtu`, `lokasi_nama`, `lokasi_alamat`, `lokasi_telepon`, `lokasi_email`, `lokasi_maps_embed`. | [`pengaturan_denda.php:15-30`](file:///c:/laragon/www/aksanova/pengaturan_denda.php#L15-L30), [`pengaturan_lokasi.php:18-40`](file:///c:/laragon/www/aksanova/pengaturan_lokasi.php#L18-L40), [`docs/audit_v2/nonexistent_refs.json`](file:///c:/laragon/www/aksanova/docs/audit_v2/nonexistent_refs.json) | **Tinggi** | Koreksi tabel referensi key `pengaturan` pada Bab 6.3 agar mencerminkan daftar key riil yang tersimpan di database dan kode PHP. |
| **[SRS-06]** | `SRS/SRS_AKSA_NOVA.docx` Bab 4 (NFR-SEC-02, P239) | Klausa NFR-SEC-02 mengklaim *"100% kueri SQL dinamis yang menerima masukan pengguna wajib menggunakan Prepared Statements dengan parameter binding (mysqli_stmt atau PDO)"*. | Fakta kode menunjukkan postur SQL injection heterogen: prepared statements digunakan pada login, register, OTP, rating, dan pengajuan daring; namun kueri interpolasi langsung (`mysqli_query` dengan `(int)` atau `mysqli_real_escape_string`) masih digunakan di `halaman_admin.php`, `daftar_anggota.php`, `pinjam_buku.php`, `telah_dipinjam.php`, `like_handler.php`, `kelola_banner.php`, `pengaturan_denda.php`, `pengaturan_musik.php`, `pengaturan_lokasi.php`. | [`halaman_admin.php:115, 185`](file:///c:/laragon/www/aksanova/halaman_admin.php#L115), [`pinjam_buku.php:55`](file:///c:/laragon/www/aksanova/pinjam_buku.php#L55), [`telah_dipinjam.php:68`](file:///c:/laragon/www/aksanova/telah_dipinjam.php#L68) | **Tinggi** | Sesuaikan klausa NFR-SEC-02 dengan menyatakan bahwa prepared statements diwajibkan pada seluruh modul transaksi kredensial dan data sensitif anggota, sedangkan sanitasi casting/escaping diterapkan pada modul administratif internal (atau lakukan refactoring kode di masa depan). |
| **[SRS-07]** | `SRS/SRS_AKSA_NOVA.docx` Bab 4 (NFR-SEC-05, P242) | Klausa NFR-SEC-05 mengklaim *"Seluruh form pengiriman data (POST) wajib dilengkapi token proteksi Cross-Site Request Forgery (CSRF)"*. | Terdapat 5 handler yang memproses state change tanpa verifikasi token CSRF: `like_handler.php` (AJAX POST toggle suka/simpan), `rating_handler.php` (AJAX POST submit rating), `pengaturan_musik.php` (AJAX POST simpan audio), `ganti_sandi_admin.php` (POST ganti sandi), dan `logout.php` (mencetak `csrf_field()` tapi tidak memvalidasinya serta menerima konfirmasi via GET `?confirm=1`). | [`like_handler.php:1-88`](file:///c:/laragon/www/aksanova/like_handler.php#L1-L88), [`rating_handler.php:1-95`](file:///c:/laragon/www/aksanova/rating_handler.php#L1-L95), [`pengaturan_musik.php:1-75`](file:///c:/laragon/www/aksanova/pengaturan_musik.php#L1-L75), [`logout.php:16, 152`](file:///c:/laragon/www/aksanova/logout.php#L16) | **Tinggi** | Revisi klausa NFR-SEC-05 menjadi *"Seluruh form utama transaksi pengguna wajib memvalidasi CSRF"* dan cantumkan rencana penguatan token pada endpoint AJAX di iterasi berikutnya. |
| **[SRS-08]** | `SRS/SRS_AKSA_NOVA.docx` Bab 3 (FR-05 & UC-16, P189, P280) | FR-05 dan spesifikasi use case UC-16 menyebutkan bahwa Pustakawan memiliki tombol aksi untuk **"Membekukan Akun / Kartu Anggota (Freeze)"** secara manual melalui tabel daftar anggota. | Pada implementasi kode [`daftar_anggota.php`](file:///c:/laragon/www/aksanova/daftar_anggota.php#L57-L79), aksi yang tersedia untuk admin hanyalah: `approve` (menyetujui pendaftaran), `reject` (menolak), `unfreeze` (mengaktifkan kembali kartu yang terlanjur dibekukan), `hapus` (menghapus anggota), dan `reset_password`. Admin **tidak memiliki tombol aksi untuk membekukan akun**. Status `frozen` hanya dipicu secara otomatis oleh sistem saat anggota memverifikasi OTP di `lupa_kartu.php:185`. | [`daftar_anggota.php:57-79`](file:///c:/laragon/www/aksanova/daftar_anggota.php#L57-L79), [`lupa_kartu.php:185`](file:///c:/laragon/www/aksanova/lupa_kartu.php#L185) | **Sedang** | Koreksi narasi FR-05 dan UC-16: jelaskan bahwa pembekuan akun terjadi otomatis oleh sistem saat alur reset kartu diinisiasi, sedangkan peran Pustakawan adalah memulihkan akun (`unfreeze`) atau mereset kata sandi. |
| **[SRS-09]** | `SRS/SRS_AKSA_NOVA.docx` Bab 3 (FR-07 & NFR-SEC-08, P193, P245) | Klausul NFR-SEC-08 dan FR-07 mengklaim bahwa seluruh ID rekaman pada parameter URL publik disamarkan menggunakan fungsi peracik token `mask_id()`. | Parameter ID buku pada formulir pengajuan peminjaman daring ([`pengajuan_peminjaman.php?buku_id=...`](file:///c:/laragon/www/aksanova/pengajuan_peminjaman.php)) diakses menggunakan integer mentah tanpa masking, baik dari tautan di [`beranda.php:2712`](file:///c:/laragon/www/aksanova/beranda.php#L2712) maupun di [`daftar_buku.php:1482`](file:///c:/laragon/www/aksanova/daftar_buku.php#L1482). `mask_id()` hanya diterapkan pada URL detail buku publik dan cetak kartu. | [`pengajuan_peminjaman.php:40`](file:///c:/laragon/www/aksanova/pengajuan_peminjaman.php#L40), [`beranda.php:2712`](file:///c:/laragon/www/aksanova/beranda.php#L2712) | **Sedang** | Sesuaikan klausul NFR-SEC-08 agar mencerminkan bahwa penyamaran ID saat ini diprioritaskan pada halaman detail katalog publik dan tautan unduh kartu anggota. |
| **[SRS-10]** | `SRS/SRS_AKSA_NOVA.docx` Bab 3 (UC-19, P286) | Spesifikasi alur use case UC-19 mencantumkan aktor utama sebagai **"Sistem"** (Sistem mengirimkan notifikasi / polling). | Melanggar ATURAN BESI #6 (Hanya 3 aktor yang sah: Tamu, Anggota, Pustakawan. Sistem, Cron, Layanan bukan aktor manusia). Pada implementasinya, notifikasi audio dan visual dipicu oleh browser Pustakawan yang melakukan long-polling via AJAX ke server. Aktor penerima notifikasi adalah Pustakawan. | [`notifikasi_pengajuan_admin.php:1-120`](file:///c:/laragon/www/aksanova/notifikasi_pengajuan_admin.php#L1-L120) | **Sedang** | Ganti aktor utama pada UC-19 menjadi Pustakawan (Admin), dengan penjelasan bahwa antarmuka sistem menyajikan peringatan visual dan audio secara realtime saat admin sedang aktif. |
| **[SRS-11]** | `SRS/SRS_AKSA_NOVA.docx` Bab 3 & 4 (NFR-PERF-03 & P286) | Dokumen mengklaim audio notifikasi antrean peminjaman berbunyi *"chime frekuensi 880 Hz selama 0,5 detik"*. | Pada implementasi kode Web Audio API di [`notifikasi_pengajuan_admin.php:585-615`](file:///c:/laragon/www/aksanova/notifikasi_pengajuan_admin.php#L585-L615), nada notifikasi yang dimainkan adalah arpeggio akord 4-nada bertingkat: C5 (523.25 Hz), E5 (659.25 Hz), G5 (783.99 Hz), dan C6 (1046.50 Hz) dengan durasi 0,15 detik per nada (total 0,6 detik). Tidak ada frekuensi tunggal 880 Hz. | [`notifikasi_pengajuan_admin.php:585-615`](file:///c:/laragon/www/aksanova/notifikasi_pengajuan_admin.php#L585-L615) | **Rendah** | Perbarui narasi spesifikasi audio di Bab 3 dan NFR-PERF-03 agar mendeskripsikan akord 4-nada harmonis (C5-E5-G5-C6) sesuai sintaks Web Audio API aktual. |
| **[SRS-12]** | `SRS/SRS_AKSA_NOVA.docx` Bab 4 (NFR-USE-04, P236-P237) | Kode identifier `NFR-USE-04` tercetak dua kali secara berturut-turut untuk dua kebutuhan yang berbeda (Paragraf P236 untuk tata letak responsif, dan Paragraf P237 untuk tema gelap/terang). | Kesalahan penomoran klausul dokumen spesifikasi. | [`SRS/SRS_AKSA_NOVA.docx`](file:///c:/laragon/www/aksanova/SRS/SRS_AKSA_NOVA.docx) (Paragraf P236, P237) | **Rendah** | Lakukan penomoran ulang secara berurutan: NFR-USE-04 (Responsivitas Mobile) dan NFR-USE-05 (Dukungan Tema Gelap & Terang). |
| **[SRS-13]** | `SRS/SRS_AKSA_NOVA.docx` Bab 8 (P501, P509) | Bab 8 mengklaim bahwa *"pengajuan_peminjaman diimplementasikan tanpa relasi fisik FK untuk fleksibilitas"* dan merujuk file script polling bernama `admin/check_notif.php`. | DDL database membuktikan bahwa tabel `pengajuan_peminjaman` memiliki Foreign Key fisik formal (`fk_pengajuan_buku` dan `fk_pengajuan_user` ON DELETE CASCADE), dan file polling notifikasi yang riil bernama `notifikasi_pengajuan_admin.php` di root directory. | [`database/aksa_nova.sql:265-266`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql#L265-L266), [`notifikasi_pengajuan_admin.php:1-30`](file:///c:/laragon/www/aksanova/notifikasi_pengajuan_admin.php#L1-L30) | **Sedang** | Hapus pernyataan keliru tentang peniadaan FK fisik pada Bab 8 dan koreksi rujukan nama file polling notifikasi menjadi `notifikasi_pengajuan_admin.php`. |

---

## 5. Audit Kepatuhan Alur Reset Kartu & OTP (FR-04)

Berdasarkan pengecekan silang spesifikasi FR-04 dengan implementasi riil pada [`lupa_kartu.php`](file:///c:/laragon/www/aksanova/lupa_kartu.php), [`includes/otp.php`](file:///c:/laragon/www/aksanova/includes/otp.php), dan [`includes/mailer.php`](file:///c:/laragon/www/aksanova/includes/mailer.php):

| Parameter Keamanan & Alur OTP | Spesifikasi FR-04 | Fakta Implementasi Kode | Status Kepatuhan | Bukti Kode Path:Baris |
|---|---|---|:---:|---|
| **Masa Berlaku Kode (TTL)** | 10 Menit | `expires_at = date('Y-m-d H:i:s', time() + 600)` (tepat 600 detik) | **PATUH (BENAR)** | [`includes/otp.php:113`](file:///c:/laragon/www/aksanova/includes/otp.php#L113) |
| **Jeda Pengiriman (Cooldown)**| 60 Detik | Jeda 60 detik diperiksa sebelum pengiriman ulang | **PATUH (BENAR)** | [`includes/otp.php:57-61`](file:///c:/laragon/www/aksanova/includes/otp.php#L57-L61) |
| **Batas Frekuensi Pengguna** | Maks 5 kali / jam / user | `COUNT(*) >= 5` dalam interval 1 jam memicu penolakan | **PATUH (BENAR)** | [`includes/otp.php:68-75`](file:///c:/laragon/www/aksanova/includes/otp.php#L68-L75) |
| **Batas Frekuensi Alamat IP**| Maks 10 kali / jam / IP | `COUNT(*) >= 10` per IP diperiksa pada langkah 1 | **PATUH (BENAR)** | [`includes/otp.php:33-48`](file:///c:/laragon/www/aksanova/includes/otp.php#L33-L48) |
| **Batas Percobaan Input Salah**| Maksimal 5 kali gagal | Kolom `attempts >= 5` menyebabkan token hangus otomatis | **PATUH (BENAR)** | [`includes/otp.php:197-203`](file:///c:/laragon/www/aksanova/includes/otp.php#L197-L203) |
| **Pencegahan Enumerasi Email** | Pesan generik seragam | Email yang tidak ditemukan tetap dialihkan ke Langkah 2 dengan sesi dummy (`is_dummy => true`) dan penundaan waktu acak (150-250ms) | **PATUH (BENAR DENGAN PUJIAN)** | [`lupa_kartu.php:139-150`](file:///c:/laragon/www/aksanova/lupa_kartu.php#L139-L150) |
| **Rollback Kegagalan SMTP** | Hapus OTP jika gagal kirim | Bila PHPMailer gagal mengirim email, baris OTP yang baru dibuat langsung dihapus (`DELETE FROM password_otp WHERE id = ?`) | **PATUH (BENAR DENGAN PUJIAN)** | [`includes/mailer.php:119-127`](file:///c:/laragon/www/aksanova/includes/mailer.php#L119-L127) |
| **Mode Pengembang Lokal** | Catat ke file log lokal | File `storage/otp_dev.log` hanya aktif bila `OTP_DEV_MODE === true` DAN diakses dari `localhost` / `127.0.0.1` | **PATUH (BENAR)** | [`includes/otp.php:148-172`](file:///c:/laragon/www/aksanova/includes/otp.php#L148-L172) |
| **Pembekuan Kartu (Freeze)** | Kartu dibekukan saat reset | `UPDATE users SET card_status='frozen'` dijalankan HANYA SETELAH verifikasi OTP berhasil di Langkah 2 | **PATUH (BENAR)** | [`lupa_kartu.php:185`](file:///c:/laragon/www/aksanova/lupa_kartu.php#L185) |
| **Kadaluwarsa Sesi Langkah 3** | Batas 10 menit di Langkah 3 | Bila Langkah 3 didiamkan `>10 menit`, kartu otomatis diaktifkan kembali (`card_status='active'`), sesi dihapus, dan dialihkan ke `?expired=1` | **PATUH (BENAR DENGAN PUJIAN)** | [`lupa_kartu.php:262-274`](file:///c:/laragon/www/aksanova/lupa_kartu.php#L262-L274) |

---

## 6. Fitur Kode yang Belum Terdokumentasi di SRS (Kebutuhan Terlewat)

Terdapat sejumlah fitur yang telah diimplementasikan dengan sangat baik pada source code PHP, namun belum tercantum sebagai Kebutuhan Fungsional (FR) resmi di SRS:

1. **Autentikasi Mandiri Pindai Barcode (`kartu_masuk.php`):**
   - Siswa dapat memindai barcode fisik/digital kartu anggota di meja scanner perpustakaan untuk langsung login otomatis ke dashboard anggota tanpa mengetik kata sandi ([`kartu_masuk.php:1-65`](file:///c:/laragon/www/aksanova/kartu_masuk.php#L1-L65)).
2. **Pembersihan Sesi Kartu Sementara (`kartu_clear.php`):**
   - Tombol logout khusus untuk membersihkan sesi kartu sementara saat mencetak kartu di komputer umum perpustakaan ([`kartu_clear.php:1-25`](file:///c:/laragon/www/aksanova/kartu_clear.php#L1-L25)).
3. **Agregasi Berita Edukasi via Sindikasi RSS Antara News:**
   - Halaman beranda memuat feed berita pendidikan terkini secara live dari RSS Antara News lengkap dengan mekanisme caching lokal berjangka waktu di [`beranda.php:75-115`](file:///c:/laragon/www/aksanova/beranda.php#L75-L115).
4. **Preferensi Aksesibilitas Khusus Anggota (Panel Pengaturan):**
   - Pengaturan peramban untuk skala ukuran huruf (font scale), jenis huruf ramah disleksia (OpenDyslexic font), mode hemat animasi (lite motion), dan volume pemutar audio instrumen di [`dashboard.php`](file:///c:/laragon/www/aksanova/dashboard.php) dan [`includes/pengaturan_panel.php`](file:///c:/laragon/www/aksanova/includes/pengaturan_panel.php).
5. **Penghapusan Riwayat Transaksi Sirkulasi oleh Admin:**
   - Opsi pembersihan rekaman riwayat pengajuan peminjaman yang telah berstatus selesai/ditolak di [`pengajuan_buku.php:38-48`](file:///c:/laragon/www/aksanova/pengajuan_buku.php#L38-L48) dan penghapusan riwayat pengembalian di [`telah_dipinjam.php:40-48`](file:///c:/laragon/www/aksanova/telah_dipinjam.php#L40-L48).

---

## 7. Rekomendasi Perbaikan SRS (Hanya Ditulis, Tidak Dikerjakan)

1. **Konsolidasi Dokumen:**
   - Perbarui file PDF dengan mengekspor ulang versi DOCX terbaru (tanggal 03 November 2026 atau versi hasil perbaikan) agar seluruh isi PDF mencerminkan modul OTP, Barcode 128, dan 12 tabel basis data.
2. **Pembersihan Draf Lama:**
   - Lakukan revisi global pada Bab 1 dan 2 untuk menghapus seluruh istilah "Kode QR" dan "password bullet", menggantikannya dengan "Barcode Code 128" dan penegasan bahwa kartu bebas dari teks password.
3. **Eliminasi Tabel 7.2:**
   - Hapus Tabel 7.2 yang mencantumkan 22 path fiktif. Selaraskan seluruh rujukan file kode hanya pada struktur flat root yang sah sesuai Tabel 7.1.
4. **Pembaruan Kamus Data Bab 6.3:**
   - Tambahkan entri tabel `password_otp` dan revisi 18 key tabel `pengaturan` agar presisi 100% dengan kolom dan record skema database.
