# LAPORAN AUDIT v2: UML SEQUENCE DIAGRAM
**Proyek:** AKSA NOVA — Sistem Informasi Perpustakaan Digital SMK Negeri 1 Rongga  
**Tanggal Audit:** 2026-11-08  
**Auditor:** Software Auditor & System Analyst (Audit v2 — Clean Room & Read-Only)  
**Status Audit:** SELESAI (Audit Read-Only)  

---

## 1. Ringkasan Eksekutif Sequence Diagram

Audit terhadap seluruh artefak UML Sequence Diagram AKSA NOVA mencakup **31 file gambar PNG** (7 diagram Core di `UML/Squence/` + 24 diagram Lengkap di `UML/Squence/Lengkap/`) dan **29 file sumber PlantUML (.puml)** (`S08_Lupa_Kartu_OTP.png` tidak memiliki file `.puml`). Seluruh berkas telah diperiksa secara visual satu per satu menggunakan inspeksi visual mendalam dan diverifikasi langsung terhadap 44 berkas PHP serta skema basis data fisik.

**Sumber Kebenaran (Ground Truth):**
- Seluruh 44 file source code PHP ([`docs/audit_v2/02_fakta_kode.md`](file:///c:/laragon/www/aksanova/docs/audit_v2/02_fakta_kode.md)).
- Basis data DDL ([`database/aksa_nova.sql`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql)).
- Hasil pemindaian path fiktif ([`docs/audit_v2/nonexistent_refs.json`](file:///c:/laragon/www/aksanova/docs/audit_v2/nonexistent_refs.json)).

**Temuan Utama Sistemik:**
1. **Ketidakcocokan 100% Antara .png dan .puml (29 dari 29 Berpasangan = TIDAK COCOK):**
   - **Pada Folder Lengkap (23 file .puml):** Seluruh file `.puml` bertanggal 03 November 2026 adalah **stub templat generik copy-paste 41 baris** yang mencantumkan **nama-nama file PHP fiktif** di subdirektori `admin/` dan `member/` yang tidak pernah ada (`admin/buku.php`, `admin/kirim_wa.php`, `member/profil.php`, dll.) serta mengklaim `csrf_verify()` dan `prepared statement` secara membabi-buta.
   - **Pada Folder Core (6 file .puml 10-16):** File `.puml` menyimpang dari `.png` dan memuat banyak halusinasi SQL (`no_anggota=?`, `status='pending'`, `tgl_kembali`, `denda_terbayar`).
2. **Pelanggaran Aktor Non-Manusia (ATURAN BESI #6):**
   - Pada `sequence_24_pengingat_keterlambatan_wa.png`, **`Penjadwal (Cron)`** digambar menggunakan simbol orang-orangan (`actor`). Padahal Cron adalah daemon/layanan sistem, bukan aktor manusia.
3. **Halusinasi Fitur Kritis di Gambar PNG:**
   - **Diagram #24 (`sequence_24`):** Menggambar fitur fiktif **`opt [Salah tandai] -> batal_wa: UPDATE wa_terkirim = 0`** yang sama sekali tidak ada di kode PHP (tidak ada tombol batalkan kirim WA di sistem).
   - **Diagram S08 (`S08_Lupa_Kartu_OTP.png`):** Pada pesan 51 mengklaim penerbitan kartu pengganti berformat **`(QR code)`**, padahal kartu anggota resmi menggunakan **Barcode Code 128**. Urutan rate limit Langkah 1 terbalik (cek email sebelum SELECT user), dan timeout Langkah 3 diabaikan.
   - **Diagram #28 (`sequence_28`):** Menggambar aksi simpan form monolitik tunggal padahal antarmuka UI bekerja murni melalui 3 endpoint AJAX independen.
   - **Diagram #32 (`sequence_32`):** Menggambar logout langsung menghancurkan sesi tanpa memodelkan modal konfirmasi `AksaConfirm` dan halaman GET `logout.php`.

---

## 2. Cakupan Terukur (Measurable Coverage)

| Kategori Elemen | Jumlah Diperiksa | BENAR | BENAR DENGAN CATATAN | SALAH | TIDAK TERVERIFIKASI | Keterangan Singkat |
|---|:---:|:---:|:---:|:---:|:---:|---|
| **Diagram Diperiksa (PNG)** | 31 | 0 | 18 | 13 | 0 | Seluruh 31 PNG diperiksa visual; tidak ada 100% sempurna |
| **Kesesuaian PNG vs PUML** | 29 | 0 | 0 | 29 | 0 | 100% pasangan tidak cocok; PUML stub generik/halusinasi |
| **Kepatuhan Aktor (3 Aktor Sah)** | 31 | 29 | 1 | 1 | 0 | 1 pelanggaran (Cron di #24); 1 aktor 'Pengguna' di #32 |
| **Lifelines / Komponen Sistem**| 124 | 98 | 14 | 12 | 0 | Komponen eksternal rapi (box), bbrp boundary tembak DB |
| **Alur Pesan & Validasi Kode** | 188 | 57 | 73 | 58 | 0 | Halusinasi batal_wa, QR code, alur form musik, urutan sanitasi |
| **Guard Kondisi (alt/opt)** | 62 | 34 | 16 | 12 | 0 | Blok opt salah tandai fiktif; guard csrf_verify hilang di 10 PNG |
| **TOTAL AKUMULATIF** | **465** | **218 (46,9%)** | **122 (26,2%)** | **125 (26,9%)** | **0 (0,0%)** | Status akurasi alur memerlukan pembenahan menyeluruh |

---

:|---|---|---:|---:|---:|---:|---:|
| 1 | `10_sequence_registrasi_anggota.png` | TIDAK COCOK | 7 | 2 | 3 | 2 | 0 |
| 2 | `11_sequence_login.png` | TIDAK COCOK | 7 | 2 | 4 | 1 | 0 |
| 3 | `12_sequence_pengajuan_peminjaman.png` | TIDAK COCOK | 7 | 2 | 1 | 4 | 0 |
| 4 | `13_sequence_persetujuan_pengajuan.png` | TIDAK COCOK | 7 | 1 | 4 | 2 | 0 |
| 5 | `14_sequence_peminjaman_langsung.png` | TIDAK COCOK | 7 | 1 | 4 | 2 | 0 |
| 6 | `15_sequence_pengembalian_denda.png` | TIDAK COCOK | 7 | 2 | 3 | 2 | 0 |
| 7 | `16_sequence_tambah_buku_isbn.png` | TIDAK COCOK | 8 | 2 | 3 | 3 | 0 |
| 8 | `Lengkap/S08_Lupa_Kartu_OTP.png` | TANPA `.puml` | 10 | 2 | 2 | 6 | 0 |
| 9 | `Lengkap/sequence_09_edit_kartu_anggota.png` | TIDAK COCOK (Templat) | 7 | 2 | 3 | 2 | 0 |
| 10 | `Lengkap/sequence_10_like_simpan_buku.png` | TIDAK COCOK (Templat) | 6 | 4 | 1 | 1 | 0 |
| 11 | `Lengkap/sequence_11_rating_buku.png` | TIDAK COCOK (Templat) | 6 | 4 | 1 | 1 | 0 |
| 12 | `Lengkap/sequence_12_telusuri_katalog_detail.png` | TIDAK COCOK (Templat) | 6 | 2 | 3 | 1 | 0 |
| 13 | `Lengkap/sequence_13_dashboard_anggota.png` | TIDAK COCOK (Templat) | 6 | 1 | 2 | 3 | 0 |
| 14 | `Lengkap/sequence_14_status_akun_kartu_anggota.png` | TIDAK COCOK (Templat) | 5 | 1 | 3 | 1 | 0 |
| 15 | `Lengkap/sequence_15_reset_sandi_anggota.png` | TIDAK COCOK (Templat) | 6 | 2 | 2 | 2 | 0 |
| 16 | `Lengkap/sequence_16_hapus_anggota.png` | TIDAK COCOK (Templat) | 5 | 3 | 1 | 1 | 0 |
| **TOTAL** | **16 Gambar PNG (+ 15 PUML)** | **0 COCOK** | **107** | **33** | **40** | **34** | **0** |

---

## Audit Rinci Per Diagram (1 s/d 16)

---

### Diagram 1 — Registrasi Anggota
- **Judul & Path Gambar / Sumber**:
  -Judul di PNG: `Sequence Diagram — Registrasi Anggota` *(subjudul: `sign_up.php · akun langsung berstatus approved (tanpa verifikasi admin)`)*
  - Path PNG: `c:\laragon\www\aksanova\UML\Squence\10_sequence_registrasi_anggota.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Squence\10_sequence_registrasi_anggota.puml`
- **File PHP Aktual**: `sign_up.php`, `kartu_anggota.php`, `barcode128.php`, `kartu_masuk.php`, `kartu_clear.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - `.png` (`2026-10-23`) memiliki **4 lifeline** (`Tamu`, `Halaman Sign Up`, `Proses Registrasi`, `Tabel users`) dan **14 pesan**, tanpa `csrf_verify()`, tanpa lifeline `kartu_anggota.php`, dan diakhiri pesan `14: Tampilkan kartu anggota & arahkan ke Sign In`.
  - `.puml` (`2026-11-03`) memiliki **5 lifeline** (menambah `Halaman Kartu Anggota (kartu_anggota.php)`), menyertakan `csrf_verify()` dan redirect ke `kartu_anggota.php`, tetapi belum dirender ke `.png` serta mengandung kesalahan kode sendiri: menulis `SELECT COUNT(*) WHERE email=? OR nik=?` (`:41`, aktual 2 `SELECT id` terpisah), menulis `PASSWORD_BCRYPT` (`:50`, aktual `PASSWORD_DEFAULT`), dan menghilangkan seluruh blok validasi format input (`:53-139`).
- **Rincian Apa yang Tampak di PNG**:
  - **Lifeline (4)**: `Tamu` (`actor`), `Halaman Sign Up` (`boundary`), `Proses Registrasi` (`control`), `Tabel users` (`entity`).
  - **Pesan Utama (`1:`–`14:`)**: `1` Isi form Siswa/Guru (NIS/NIP, nama, email, no HP, sandi) & klik Daftar -> `2` POST -> `3` Validasi kelengkapan & format input -> `6` Cek NIS/NIP & email terdaftar -> `7` Hasil -> `10` Enkripsi sandi (`password_hash`) & buat nomor kartu -> `11` `INSERT anggota baru (status = approved)` -> `12` Data tersimpan -> `13` Registrasi berhasil -> `14` Tampilkan kartu anggota & arahkan ke Sign In.
  - **Blok Fragmen**: `alt [Input tidak valid] / [Input valid]`, di dalamnya bersarang `alt [Sudah terdaftar] / [Belum terdaftar]`.
  - **Kualitas Visual**: Sangat bersih, kontras tajam, penomoran 1–14 urut, tidak ada teks terpotong atau mojibake.
- **Daftar Pemeriksaan Elemen & Temuan** (`7 diperiksa`: `2 BENAR`, `3 BENAR DENGAN CATATAN`, `2 SALAH`, `0 TIDAK TERVERIFIKASI`):
  1. **[BENAR]** Subjudul & Pesan 11 `INSERT anggota baru (status = approved)` tanpa verifikasi admin — sesuai `sign_up.php:215-217` (`VALUES (..., 'member', 'approved')`).
  2. **[BENAR]** Struktur bersarang validasi format mendahului pengecekan duplikasi DB — sesuai urutan `sign_up.php:53-139` sebelum `:142-162`.
  3. **[BENAR DENGAN CATATAN]** Aktor `Tamu` (`sign_up.php:7-10`) — secara peran benar hanya pengunjung belum login, namun nama aktor standar proyek adalah `Tamu (Guest)`.
  4. **[BENAR DENGAN CATATAN]** Pesan 6 `Cek NIS/NIP & email sudah terdaftar?` (`sign_up.php:143-161`) — di kode dieksekusi sebagai **2 prepared statement terpisah** dengan urutan `email` lebih dulu (`:143`), baru `nik` (`:153`).
  5. **[BENAR DENGAN CATATAN]** Pesan 10 `Enkripsi sandi (password_hash) & buat nomor kartu` (`sign_up.php:167-210`) — `password_hash($password, PASSWORD_DEFAULT)` (`:196`) dan `no_anggota` `AN-YYYY-NNNNN` unik (`:199-210`) benar, namun PNG melewatkan **pembangkitan `username` unik otomatis** dari `full_name` (`SELECT id FROM users WHERE username = ?` dalam loop `while`, `:179-193`) serta rename foto `temp_*` menjadi `anggota_*` (`:167-176`).
  6. **[SALAH — Sedang]** Hilangnya `csrf_verify()`, guard sesi aktif, aturan domain email/sandi, dan staging foto `temp_*` di PNG — Bukti: `sign_up.php:7-10` (redirect jika sudah login), `sign_up.php:28-34` (hapus `temp_* > 7200` detik), `sign_up.php:37-39` (`csrf_verify()`), `sign_up.php:80-96` (3 domain email `@student.smkn1rongga.sch.id` / `@smkn1rongga.sch.id` / `@gmail.com`, sandi min 8 karakter huruf+angka), dan `sign_up.php:99-139` (unggah foto opsional JPG/PNG/WEBP maks 5 MB ke `temp_*`).
     - *Saran perbaikan*: Tambahkan guard `csrf_verify()`, rincian aturan validasi utama, dan `opt [Unggah foto]` sebelum penyimpanan DB.
  7. **[SALAH — Tinggi]** Pesan 13–14 `Tampilkan kartu anggota & arahkan ke Sign In` kembali melalui `Halaman Sign Up` — Bukti: `sign_up.php:229-246`, `kartu_anggota.php:696, 712-716`, `kartu_masuk.php:14-27`. Setelah `INSERT`, `sign_up.php` menyetel `$_SESSION['kartu_data']` (`is_first_creation => true`, belum menyetel `$_SESSION['user_id']`) lalu me-redirect HTTP ke **`kartu_anggota.php`** yang merender **Barcode Code 128 SVG** (`barcode128.php`). Di halaman tersebut tombol utama `"Selesai, Masuk ke Akun ->"` mengarah ke **`kartu_masuk.php`** (auto-login langsung ke `beranda.php` tanpa melewati `sign_in.php`), sedangkan `sign_in.php` hanyalah tautan sekunder.
     - *Saran perbaikan*: Tambahkan lifeline `Halaman Kartu Anggota (kartu_anggota.php)` (dan opsional `kartu_masuk.php`), ubah pesan 13–14 menjadi simpan `$_SESSION['kartu_data']` -> `Redirect ke kartu_anggota.php` -> tampilkan kartu ber-Barcode Code 128 dengan opsi langsung masuk akun (`kartu_masuk.php` -> `beranda.php`) atau ke `sign_in.php`, lalu sinkronkan `.puml` dan render ulang `.png`.

---

### Diagram 2 — Login
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Sequence Diagram — Login` *(subjudul: `sign_in.php`)*
  - Path PNG: `c:\laragon\www\aksanova\UML\Squence\11_sequence_login.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Squence\11_sequence_login.puml`
- **File PHP Aktual**: `sign_in.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - `.png` (`2026-10-23`) memiliki **6 lifeline** (termasuk `Halaman Beranda` & `Halaman Dasbor Admin`) dan **16 pesan**, menguji `status` (`pending`/`rejected`) serta `card_status` (`frozen`) **setelah** `password_verify()`, namun tidak memuat `csrf_verify()` dan `session_regenerate_id(true)`.
  - `.puml` (`2026-11-03`) hanya **4 lifeline**, memuat `csrf_verify()` dan `session_regenerate_id(true)`, tetapi **mengarang kueri palsu** `WHERE (username=? OR email=? OR no_anggota=?)` (`:35` — login dengan `no_anggota` TIDAK ADA di `sign_in.php:40`!), membalik urutan cek `status != 'approved'` ke sebelum `password_verify()` (`:40`), menghilangkan cek `card_status === 'frozen'`, dan salah menulis kunci sesi `'full_name'` (`:50`, aktual `'user_name'` & `'username'`).
- **Rincian Apa yang Tampak di PNG**:
  - **Lifeline (6)**: `Pengguna` (`actor`), `Halaman Sign In` (`boundary`), `Proses Login` (`control`), `Tabel users` (`entity`), `Halaman Beranda` (`boundary`), `Halaman Dasbor Admin` (`boundary`).
  - **Pesan Utama (`1:`–`16:`)**: `1` Isi username/email & sandi klik Masuk -> `2` POST -> `3` `SELECT` berdasarkan username/email -> `4` Data pengguna/kosong -> evaluasi kondisi -> `12` Simpan session (`user_id, user_name, username, role`) -> redirect sesuai role (`13-14` `dashboard.php` / `15-16` `beranda.php`).
  - **Blok Fragmen**: `alt` 4 cabang (`[Pengguna tidak ditemukan atau sandi salah]`, `[Status akun pending / rejected]`, `[Kartu anggota dibekukan (frozen)]`, `[Valid]`), dengan `alt` bersarang `[Role = Admin]` / `[Role = Anggota]`.
  - **Kualitas Visual**: Rapi, proporsional, seluruh 16 pesan terbaca jelas tanpa tumpang tindih.
- **Daftar Pemeriksaan Elemen & Temuan** (`7 diperiksa`: `2 BENAR`, `4 BENAR DENGAN CATATAN`, `1 SALAH`, `0 TIDAK TERVERIFIKASI`):
  1. **[BENAR]** Pesan 3–4 `SELECT pengguna berdasarkan username atau email` — sesuai `sign_in.php:40` (`WHERE email = ? OR username = ?`).
  2. **[BENAR]** Urutan pengecekan blokir akun (`pending`, `rejected`, lalu `card_status === 'frozen'`) dilakukan **setelah** sandi terverifikasi — sesuai `sign_in.php:47-54`.
  3. **[BENAR DENGAN CATATAN]** Aktor `Pengguna` — mewakili pengunjung belum login yang hendak masuk sebagai Anggota atau Pustakawan; gunakan penamaan baku `Tamu (Guest) / Anggota / Pustakawan`.
  4. **[BENAR DENGAN CATATAN]** Pesan 5 `password_verify() gagal` di dalam cabang `[Pengguna tidak ditemukan atau sandi salah]` — di `sign_in.php:47` (`if ($user && password_verify(...))`), jika `$user` tidak ditemukan maka PHP short-circuit sehingga `password_verify()` tidak dipanggil. Catatan pada Pesan 10: teks kode di `:53` meminta menyelesaikan proses Lupa Kartu yang menggantung, bukan memulai Lupa Kartu baru.
  5. **[BENAR DENGAN CATATAN]** Pesan 12 `Simpan session (user_id, user_name, username, role)` — keempat kunci sesi 100% tepat (`sign_in.php:57-60`), tetapi melewatkan panggilan keamanan penting **`session_regenerate_id(true)`** di `sign_in.php:56`.
  6. **[BENAR DENGAN CATATAN]** Pesan 15 `Redirect ke beranda.php` untuk Anggota — di `sign_in.php:7-14, 65`, sistem mendukung redirect dinamis tervalidasi regex `$safe_redirect !== "" ? $safe_redirect : "beranda.php"` (mis. kembali ke `pengajuan_peminjaman.php`).
  7. **[SALAH — Sedang]** Hilangnya verifikasi **`csrf_verify()`**, validasi field kosong, dan guard pengguna sudah login — Bukti: `sign_in.php:17-24` (guard sudah login), `sign_in.php:30-32` (`if (!csrf_verify())`), dan `sign_in.php:36-38` (`if (empty($identity) || empty($password))`) dieksekusi sebelum menyentuh `Tabel users`.
     - *Saran perbaikan*: Tambahkan langkah `csrf_verify()` dan cek input kosong sebelum Pesan 3, tambahkan `session_regenerate_id(true)` pada Pesan 12, hapus klausa palsu `OR no_anggota=?` di `.puml`, lalu render ulang `.png`.

---

### Diagram 3 — Pengajuan Peminjaman (Anggota)
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Sequence Diagram — Pengajuan Peminjaman (Anggota)` *(subjudul: `pengajuan_peminjaman.php`)*
  - Path PNG: `c:\laragon\www\aksanova\UML\Squence\12_sequence_pengajuan_peminjaman.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Squence\12_sequence_pengajuan_peminjaman.puml`
- **File PHP Aktual**: `pengajuan_peminjaman.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - `.png` (`2026-10-21`) menampilkan 14 pesan dengan unggah kartu `<= 5 MB`, cek stok, simpan file kartu, dan `INSERT pengajuan (status = menunggu)`.
  - `.puml` (`2026-11-03`) **rusak berat terhadap kode**: menulis `(POST tanpa tanggal pinjam)` tanpa `batas_kembali` dan tanpa unggah `file_kartu` (`:35-37`), serta menulis SQL palsu `INSERT INTO pengajuan_peminjaman (buku_id, user_id, jumlah, status='pending', catatan)` (`:41` — kolom aktual adalah `total_buku`, `catatan_pengambilan`, `file_kartu`, `batas_kembali`, `waktu_pengambilan`, dan enum `status='menunggu'`).
- **Rincian Apa yang Tampak di PNG**:
  - **Lifeline (5)**: `Anggota` (`actor`), `Halaman Pengajuan` (`boundary`), `Proses Pengajuan` (`control`), `Tabel buku` (`entity`), `Tabel pengajuan_peminjaman` (`entity`).
  - **Pesan Utama (`1:`–`14:`)**: `1` Pilih buku (maks. 40), tanggal pinjam/kembali, unggah foto kartu -> `2` POST + file -> `3` Cek sesi login, jumlah buku <= 40, tanggal, file kartu <= 5 MB -> `6` Cek stok setiap buku yang dipilih -> `10` Simpan file foto kartu -> `11` `INSERT pengajuan (status = menunggu)` -> `14` Tampilkan konfirmasi menunggu persetujuan admin.
  - **Blok Fragmen**: `alt [Validasi gagal] / [Validasi lolos]`, bersarang `alt [Stok habis] / [Stok tersedia]`.
  - **Kualitas Visual**: Bersih, terbaca jelas, penomoran 1–14 urut.
- **Daftar Pemeriksaan Elemen & Temuan** (`7 diperiksa`: `2 BENAR`, `1 BENAR DENGAN CATATAN`, `4 SALAH`, `0 TIDAK TERVERIFIKASI`):
  1. **[BENAR]** Pesan 10–11 `Simpan file foto kartu` ke disk lalu `INSERT pengajuan (status = menunggu)` tanpa memotong stok buku — sesuai `pengajuan_peminjaman.php:172-188`.
  2. **[BENAR]** Pesan 13–14 konfirmasi menunggu persetujuan admin — sesuai `pengajuan_peminjaman.php:189`.
  3. **[BENAR DENGAN CATATAN]** Aktor `Anggota` & kuota `<= 40` eksemplar + batas file `<= 5 MB` — angka `40` (`:132`) dan `5 MB` (`:155`) benar; gunakan label aktor baku `Anggota (Siswa & Guru)`.
  4. **[SALAH — Sedang]** Lifeline `Tabel users` dan guard akun aktif/non-frozen + `csrf_verify()` tidak dimodelkan — Bukti: `pengajuan_peminjaman.php:9-35` memeriksa sesi, mengarahkan Admin ke `dashboard.php`, dan meng-query `SELECT ... status, card_status FROM users WHERE id = ?` (menghancurkan sesi jika bukan `approved` atau `frozen`), serta `:83` memeriksa `csrf_verify()` saat POST.
     - *Saran perbaikan*: Tambahkan lifeline `Tabel users` untuk guard status akun/kartu serta langkah `csrf_verify()` pada awal POST.
  5. **[SALAH — Sedang]** Pesan 1 mengklaim input `tanggal pinjam/kembali` dan Pesan 6 mengklaim `Cek stok setiap buku yang dipilih` (jamak) — Bukti: `pengajuan_peminjaman.php:88-100, 119`. Formulir **tidak memiliki input tanggal pinjam**; yang diinput adalah **1 `buku_id`** (bukan banyak judul buku), `total_buku` (`1..40` eksemplar dari 1 judul tersebut), `batas_kembali`, `waktu_pengambilan` (`'sekarang'` / `'nanti'` + jadwal ambil), dan `file_kartu`.
     - *Saran perbaikan*: Ganti teks Pesan 1 menjadi `Memilih 1 buku, jumlah (1–40), batas kembali, waktu pengambilan, & foto kartu`, dan Pesan 6 menjadi `SELECT id, judul, stok FROM buku WHERE id = ?`.
  6. **[SALAH — Sedang]** Urutan eksekusi Pesan 3 vs Pesan 6 terbalik terhadap kode PHP — Bukti: `pengajuan_peminjaman.php:119-166`. Di kode, setelah `csrf_verify()`, server **mengecek keberadaan & stok buku di `Tabel buku` LEBIH DULU** (`:119-131`), **baru kemudian** memvalidasi `total_buku > 40` (`:132`), `batas_kembali >= hari ini` (`:134-137`), ekstensi `jpg/jpeg/png/webp`, ukuran `<= 5 MB`, dan `@getimagesize()` pada `file_kartu` (`:138-166`).
     - *Saran perbaikan*: Pindahkan query `SELECT stok FROM buku` ke sebelum validasi kuota/tanggal/berkas sesuai urutan `if-elseif` di `:126-166`.
  7. **[SALAH — Rendah]** Kondisi cabang `[Stok habis]` di Pesan 8 kurang mencakup kasus stok kurang dari jumlah yang diminta — Bukti: `pengajuan_peminjaman.php:128-131` menolak baik ketika `stok <= 0` maupun ketika `total_buku > stok`.
     - *Saran perbaikan*: Ubah label guard menjadi `[Buku tidak ada / stok <= 0 / total_buku > stok]`.

---

### Diagram 4 — Persetujuan Pengajuan (Admin)
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Sequence Diagram — Persetujuan Pengajuan (Admin)` *(subjudul: `pengajuan_buku.php`)*
  - Path PNG: `c:\laragon\www\aksanova\UML\Squence\13_sequence_persetujuan_pengajuan.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Squence\13_sequence_persetujuan_pengajuan.puml`
- **File PHP Aktual**: `pengajuan_buku.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - `.png` (`2026-10-10`) memiliki 22 pesan dengan nama status benar (`menunggu`, `ditolak` + alasan, `disetujui`) dan menyebut penyisipan sebanyak `total buku`.
  - `.puml` (`2026-11-03`) menulis enum palsu `WHERE status='pending'` (`:27`), kolom palsu `jumlah` (`:49`) & `tgl_pinjam=CURRENT_DATE` (`:54`), menghilangkan `alasan_penolakan` & `approved_at`, menghilangkan penyisipan per eksemplar, dan mengklaim pembaruan tabel secara `asinkron` (`:66`, padahal form POST sinkron biasa).
- **Rincian Apa yang Tampak di PNG**:
  - **Lifeline (6)**: `Admin` (`actor`), `Halaman Pengajuan Buku` (`boundary`), `Proses Persetujuan` (`control`), `Tabel pengajuan_peminjaman` (`entity`), `Tabel buku` (`entity`), `Tabel peminjaman` (`entity`).
  - **Pesan Utama (`1:`–`22:`)**: `1-6` Ambil & tampilkan pengajuan `menunggu` -> `7-8` Klik Setujui/Tolak -> cabang `[Tolak]` (`9-12`: `UPDATE status = ditolak + alasan`) atau `[Setujui]` (`13-14`: Cek stok & `UPDATE stok = stok - n` atomik -> `17-18`: `INSERT peminjaman (status = dipinjam) sebanyak total buku` -> `19-20`: `UPDATE status = disetujui`).
  - **Blok Fragmen**: `alt [Tolak] / [Setujui]`, bersarang `alt [Stok tidak cukup] / [Stok cukup]`.
  - **Kualitas Visual**: Bersih, tajam, penomoran 1–22 berurutan tanpa cacat visual.
- **Daftar Pemeriksaan Elemen & Temuan** (`7 diperiksa`: `1 BENAR`, `4 BENAR DENGAN CATATAN`, `2 SALAH`, `0 TIDAK TERVERIFIKASI`):
  1. **[BENAR]** Urutan transaksi persetujuan (`UPDATE buku` atomik -> `INSERT peminjaman` -> `UPDATE pengajuan_peminjaman SET status = 'disetujui'`) — sesuai `pengajuan_buku.php:68-86`.
  2. **[BENAR DENGAN CATATAN]** Aktor `Admin` — ubah label ke standar `Pustakawan (Admin)`.
  3. **[BENAR DENGAN CATATAN]** Pesan 3 `SELECT pengajuan (menunggu)` — di `pengajuan_buku.php:190-196` melakukan `LEFT JOIN buku` & `LEFT JOIN users` serta mendukung tab filter `menunggu`/`disetujui`/`ditolak`/`semua`.
  4. **[BENAR DENGAN CATATAN]** Pesan 9 (`UPDATE status = ditolak + alasan`) & Pesan 19 (`UPDATE status = disetujui`) — keduanya juga mencatat timestamp `approved_at = NOW()` (`pengajuan_buku.php:85, 100`).
  5. **[BENAR DENGAN CATATAN]** Pesan 17 `INSERT peminjaman (status = dipinjam) sebanyak total buku` — teks sudah mencerminkan `for ($i = 0; $i < $total_buku; $i++)` dengan `batas_kembali . ' 23:59:59'` (`pengajuan_buku.php:72-81`), namun dalam UML Sequence Diagram perulangan kueri wajib dibungkus fragmen **`loop [total_buku kali]`**.
  6. **[SALAH — Sedang]** Tidak menampilkan guard sesi/role Admin dan **`csrf_verify()`** saat POST — Bukti: `pengajuan_buku.php:6-9` (guard role `admin`) dan `pengajuan_buku.php:43-45` (`if (!csrf_verify())`).
     - *Saran perbaikan*: Tambahkan pemeriksaan `csrf_verify()` tepat setelah Pesan 8 sebelum masuk blok `alt [Tolak] / [Setujui]`.
  7. **[SALAH — Sedang]** Kueri baca awal pada cabang `[Setujui]` (`SELECT p.*, b.stok, b.judul FROM pengajuan_peminjaman p LEFT JOIN buku b ... WHERE p.id = ? AND p.status = 'menunggu'`) dihilangkan — Bukti: `pengajuan_buku.php:47-66` mengambil baris pengajuan & stok buku terlebih dahulu (menolak jika sudah diproses `:54` atau `$current_stok < $total_buku` `:63`) sebelum menjalankan `UPDATE buku SET stok = stok - $total_buku WHERE id = $buku_id AND stok >= $total_buku` (`:68-70`).
     - *Saran perbaikan*: Tambahkan pesan `SELECT pengajuan & stok buku (status = 'menunggu')` ke `Tabel pengajuan_peminjaman` + `Tabel buku` sebelum `UPDATE` atomik Pesan 13, serta bungkus Pesan 17–18 dalam `loop [i = 1..total_buku]`.

---

### Diagram 5 — Peminjaman Langsung (Admin)
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Sequence Diagram — Peminjaman Langsung (Admin)` *(subjudul: `pinjam_buku.php`)*
  - Path PNG: `c:\laragon\www\aksanova\UML\Squence\14_sequence_peminjaman_langsung.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Squence\14_sequence_peminjaman_langsung.puml`
- **File PHP Aktual**: `pinjam_buku.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - `.png` (`2026-10-10`) memiliki 19 pesan dengan alur pilih anggota, buku, tanggal kembali -> cek kelengkapan -> cek anggota -> cek stok -> `UPDATE stok = stok - 1` -> `INSERT peminjaman`.
  - `.puml` (`2026-11-03`) mengarang alur `Pindai Barcode Kartu / Input No. Anggota & ISBN Buku` (`:23`), parameter palsu `no_anggota` dengan `WHERE no_anggota=?` (`:29`, aktual `WHERE id=$user_id`!), kolom palsu `tgl_pinjam=CURRENT_DATE` (`:52`, aktual `waktu_pinjam`), menghilangkan `AND stok > 0` pada `UPDATE buku`, dan mengarang `struk konfirmasi peminjaman` (`:58`).
- **Rincian Apa yang Tampak di PNG**:
  - **Lifeline (6)**: `Admin` (`actor`), `Halaman Pinjam Buku` (`boundary`), `Proses Peminjaman` (`control`), `Tabel buku` (`entity`), `Tabel users` (`entity`), `Tabel peminjaman` (`entity`).
  - **Pesan Utama (`1:`–`19:`)**: `1` Pilih anggota, buku, tanggal kembali klik Pinjam -> `2` POST -> `3` Cek kelengkapan input -> `6` Cek anggota (`status approved`) -> `10` Cek stok buku -> `14` `UPDATE stok = stok - 1` -> `16` `INSERT peminjaman (status = dipinjam)` -> `19` Tampilkan pesan berhasil.
  - **Blok Fragmen**: 3 lapis `alt` bersarang (`[Input tidak lengkap] / [Input lengkap]`, `[Anggota tidak valid] / [Anggota valid]`, `[Stok = 0] / [Stok > 0]`).
  - **Kualitas Visual**: Sangat rapi, mudah dibaca, penomoran 1–19 konsisten.
- **Daftar Pemeriksaan Elemen & Temuan** (`7 diperiksa`: `1 BENAR`, `4 BENAR DENGAN CATATAN`, `2 SALAH`, `0 TIDAK TERVERIFIKASI`):
  1. **[BENAR]** Pesan 3–5 validasi kelengkapan input (`buku_id <= 0 || user_id <= 0 || batas_kembali === ''`) sebelum kueri DB — sesuai `pinjam_buku.php:68-71`.
  2. **[BENAR DENGAN CATATAN]** Aktor `Admin` — gunakan nama baku `Pustakawan (Admin)`.
  3. **[BENAR DENGAN CATATAN]** Pesan 6 `Cek anggota (status approved)` — kueri aktual di `pinjam_buku.php:74-76` lebih ketat: memeriksa **`role='member' AND status='approved' AND (card_status IS NULL OR card_status != 'frozen')`** sekaligus mengambil `full_name` untuk kolom `nama_peminjam`.
  4. **[BENAR DENGAN CATATAN]** Pesan 14 `UPDATE stok = stok - 1` — kueri aktual bersifat atomik `UPDATE buku SET stok = stok - 1 WHERE id = $buku_id AND stok > 0` (`pinjam_buku.php:100`) diikuti cek `mysqli_affected_rows($conn) > 0` (`:102`).
  5. **[BENAR DENGAN CATATAN]** Pesan 16 `INSERT peminjaman (status = dipinjam)` — kueri di `pinjam_buku.php:105-106` menyisipkan `(buku_id, user_id, nama_peminjam, waktu_pinjam, batas_kembali)` dengan `batas_kembali` ditambah `' 23:59:59'` (`:93-95`), sementara nilai `'dipinjam'` berasal dari `DEFAULT` skema tabel `peminjaman`.
  6. **[SALAH — Sedang]** Tidak menampilkan guard sesi/role Admin (`pinjam_buku.php:9-12`) dan **`csrf_verify()`** (`pinjam_buku.php:60-62`) saat POST `action=pinjam`.
     - *Saran perbaikan*: Tambahkan langkah `csrf_verify()` sebelum Pesan 3 dan cantumkan syarat `card_status != 'frozen'` pada Pesan 6.
  7. **[SALAH — Rendah]** Pemuatan awal daftar buku berstok `> 0` (`pinjam_buku.php:133`) & daftar anggota aktif non-frozen (`:153-156`) serta cabang gagal *race condition* `mysqli_affected_rows == 0` (`:111-114`) tidak dimodelkan; selain itu `.puml` harus ditulis ulang karena memakai `no_anggota` & `tgl_pinjam` palsu.
     - *Saran perbaikan*: Lengkapi kondisi atomik `WHERE id=? AND stok > 0` beserta cabang gagalnya, dan perbaiki `.puml` agar memakai `user_id` & `waktu_pinjam`.

---

### Diagram 6 — Pengembalian & Denda (Admin)
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Sequence Diagram — Pengembalian & Denda (Admin)` *(subjudul: `telah_dipinjam.php`)*
  - Path PNG: `c:\laragon\www\aksanova\UML\Squence\15_sequence_pengembalian_denda.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Squence\15_sequence_pengembalian_denda.puml`
- **File PHP Aktual**: `telah_dipinjam.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - `.png` (`2026-10-10`) memiliki 21 pesan dengan parameter denda lengkap (`denda_aktif, denda_per_hari, denda_grace_period`), fungsi `hitungDenda()`, nama kolom DB akurat, urutan `UPDATE peminjaman` -> `UPDATE buku`, dan blok `opt` pelunasan denda (`tandai_lunas`).
  - `.puml` (`2026-11-03`) **sangat menyimpang**: menghilangkan `denda_aktif` & `denda_grace_period`, membalik urutan `UPDATE buku` sebelum `UPDATE peminjaman`, mengarang kolom/enum palsu `tgl_kembali=CURRENT_DATE, status='kembali', denda_terbayar='0'` (`:51`), dan menghapus fitur `tandai_lunas`.
- **Rincian Apa yang Tampak di PNG**:
  - **Lifeline (6)**: `Admin` (`actor`), `Halaman Telah Dipinjam` (`boundary`), `Proses Pengembalian` (`control`), `Tabel peminjaman` (`entity`), `Tabel pengaturan` (`entity`), `Tabel buku` (`entity`).
  - **Pesan Utama (`1:`–`21:`)**: `1-2` Klik & kirim aksi `kembalikan` -> `3-4` `SELECT peminjaman` -> `7-8` Ambil `denda_aktif, denda_per_hari, denda_grace_period` -> `9` `hitungDenda(): terlambat_hari & denda` -> `10-11` `UPDATE status = dikembalikan, waktu_kembali, terlambat_hari, denda, status_denda` -> `12-13` `UPDATE stok = stok + 1` -> `14-15` Tampilkan hasil -> `16-21` `opt [Ada denda belum dibayar]` klik & kirim `tandai_lunas` -> `UPDATE status_denda = lunas`.
  - **Blok Fragmen**: `alt [Status bukan dipinjam] / [Status dipinjam]`, diikuti `opt [Ada denda belum dibayar]`.
  - **Kualitas Visual**: Bersih, terbaca jelas, penomoran 1–21 berurutan.
- **Daftar Pemeriksaan Elemen & Temuan** (`7 diperiksa`: `2 BENAR`, `3 BENAR DENGAN CATATAN`, `2 SALAH`, `0 TIDAK TERVERIFIKASI`):
  1. **[BENAR]** Pesan 10–13 urutan & nama kolom `UPDATE peminjaman` (`status='dikembalikan', waktu_kembali, terlambat_hari, denda, status_denda`) diikuti `UPDATE buku SET stok = stok + 1` — 100% sesuai `telah_dipinjam.php:100-111`.
  2. **[BENAR]** Ketiga kunci konfigurasi denda (`denda_aktif`, `denda_per_hari`, `denda_grace_period`) dan nama fungsi `hitungDenda()` — 100% sesuai `telah_dipinjam.php:50-56`.
  3. **[BENAR DENGAN CATATAN]** Aktor `Admin` — gunakan nama baku `Pustakawan (Admin)`.
  4. **[BENAR DENGAN CATATAN]** Pesan 9 `hitungDenda()` — tambahkan catatan rumus aktual `hari = DateTimeImmutable::diff()->days` (penuh 24 jam dari `23:59:59`), `hari_denda = max(0, hari - grace_period)`, `total = denda_aktif ? hari_denda * denda_per_hari : 0`, dan `status_denda = total > 0 ? 'belum_bayar' : 'tidak_ada'` (`telah_dipinjam.php:56-69, 100`).
  5. **[BENAR DENGAN CATATAN]** Blok `opt [Ada denda belum dibayar]` Pesan 16–21 (`tandai_lunas`) — sesuai `telah_dipinjam.php:144-152` (`UPDATE peminjaman SET status_denda='lunas' WHERE id=$pem_id AND status_denda='belum_bayar'`), merupakan transaksi HTTP POST terpisah.
  6. **[SALAH — Sedang]** Tidak menampilkan guard sesi/role Admin (`telah_dipinjam.php:8-11`) dan **`csrf_verify()`** (`telah_dipinjam.php:78-80`) baik pada POST `kembalikan` maupun POST `tandai_lunas`.
     - *Saran perbaikan*: Tambahkan `csrf_verify()` pada kedua aksi POST (`kembalikan` dan `tandai_lunas`).
  7. **[SALAH — Rendah]** Urutan pengambilan `Tabel pengaturan` (Pesan 7) digambar setelah `SELECT peminjaman` (Pesan 3) — Bukti: `telah_dipinjam.php:50-52` vs `:85-89`. Di kode aktual, `getSetting()` untuk `denda_aktif`, `denda_per_hari`, dan `denda_grace_period` dieksekusi di awal pemuatan skrip sebelum blok POST dan sebelum `SELECT peminjaman`.
     - *Saran perbaikan*: Pindahkan query `getSetting()` ke awal eksekusi `telah_dipinjam.php`, dan perbaiki `.puml` yang saat ini memuat kolom palsu `tgl_kembali` & `denda_terbayar`.

---

### Diagram 7 — Tambah Buku dengan Cari ISBN (Admin)
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Sequence Diagram — Tambah Buku dengan Cari ISBN (Admin)` *(subjudul: `halaman_admin.php · cari_isbn.php`)*
  - Path PNG: `c:\laragon\www\aksanova\UML\Squence\16_sequence_tambah_buku_isbn.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Squence\16_sequence_tambah_buku_isbn.puml`
- **File PHP Aktual**: `halaman_admin.php`, `cari_isbn.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - `.png` (`2026-10-10`) memakai nama file benar `halaman_admin.php · cari_isbn.php`, 6 lifeline, 20 pesan, mencakup unduh sampul di server dan validasi judul kosong.
  - `.puml` (`2026-11-03`) **salah menyebut nama file `daftar_buku.php`** (`:14, 17`, padahal katalog admin ada di `halaman_admin.php`!), mengarang kolom palsu `INSERT INTO buku (judul, penulis, penerbit, tahun, isbn, stok, rak, deskripsi, gambar)` (`:53` — kolom `penerbit`, `tahun`, `deskripsi` tidak ada di tabel `buku`; aktualnya `genre` dan `sinopsis`), dan menghilangkan `downloadCover()`.
- **Rincian Apa yang Tampak di PNG**:
  - **Lifeline (6)**: `Admin` (`actor`), `Halaman Kelola Buku` (`boundary`), `Proses Cari ISBN` (`control`), `Layanan ISBN (Google Books / Open Library)` (`object/rectangle` — bukan stick figure), `Proses Kelola Buku` (`control`), `Tabel buku` (`entity`).
  - **Pesan Utama (`1:`–`20:`)**: `1-2` Input & kirim ISBN -> `3-4` Minta data Google Books -> `5-6` `opt [Tidak ditemukan di Google Books]` Minta data Open Library -> `7-12` `alt [Data ditemukan]` unduh cover & autofill form / `[Tidak ditemukan]` isi manual -> `13-14` Klik Simpan (POST) -> `15-20` `alt [Judul kosong]` error / `[Judul terisi]` `INSERT buku baru`.
  - **Blok Fragmen**: `opt [Tidak ditemukan di Google Books]`, `alt [Data ditemukan] / [Tidak ditemukan]`, `alt [Judul kosong] / [Judul terisi]`.
  - **Kualitas Visual**: Bersih, terbaca jelas, penomoran 1–20 urut.
- **Daftar Pemeriksaan Elemen & Temuan** (`8 diperiksa`: `2 BENAR`, `3 BENAR DENGAN CATATAN`, `3 SALAH`, `0 TIDAK TERVERIFIKASI`):
  1. **[BENAR]** `Layanan ISBN (Google Books / Open Library)` digambar sebagai objek kotak (bukan `actor` stick figure) — patuh aturan pemodelan aktor eksternal.
  2. **[BENAR]** Cabang `alt [Judul kosong] / [Judul terisi]` sebelum `INSERT INTO buku` — sesuai `halaman_admin.php:91-104`.
  3. **[BENAR DENGAN CATATAN]** Aktor `Admin` — gunakan label baku `Pustakawan (Admin)`.
  4. **[BENAR DENGAN CATATAN]** Pesan 7–8 `Unduh gambar cover` — di `cari_isbn.php:136-199, 413`, `downloadCover()` hanya mengunduh bila `$gambar_url !== ""` dan ukuran respons `>= 500` byte, lalu menyimpan ke `uploads/gambar/buku_isbn_...jpg` (sebaiknya dibungkus `opt [gambar_url tersedia]`).
  5. **[BENAR DENGAN CATATAN]** Pesan 10 `Form terisi otomatis` — di `halaman_admin.php:1691-1697`, JS hanya mengisi field `judul, penulis, genre, sinopsis` yang **masih kosong** dan menaruh path cover di input hidden `gambar_auto`.
  6. **[SALAH — Sedang]** Pencarian ISBN disederhanakan menjadi 2 tahap (`opt [Tidak ditemukan di Google Books]`), padahal kode menjalankan **pengayaan progresif 4 tahap** — Bukti: `cari_isbn.php:237-371`. Meskipun Google Books menemukan judul, bila salah satu dari `judul`, `genre`, `sinopsis`, atau `gambar_url` masih kosong, `cari_isbn.php` tetap memanggil berurutan: (1) **Google Books API** (`:237`), (2) **Open Library Books API `bibkeys`** (`:267`), (3) **Open Library Search API `search.json`** (`:312`), dan (4) **Open Library Edition + Work API** (`/isbn/{isbn}.json` -> `/works/{key}.json`, `:351-371` untuk melengkapi `genre` & `sinopsis`).
     - *Saran perbaikan*: Gambarkan 4 tahap pengayaan progresif `cari_isbn.php` dengan kondisi pengisian kolom yang masih kosong.
  7. **[SALAH — Sedang]** Validasi ISBN 10/13 digit, guard Admin HTTP 403, dan **kueri cek duplikat ISBN ke `Tabel buku`** di dalam `cari_isbn.php` hilang dari PNG — Bukti: `cari_isbn.php:31-34` (guard Admin 403), `:207-212` (wajib 10 atau 13 digit), dan `:219-226` (`SELECT id, judul, stok FROM buku WHERE isbn = '$isbn_esc' LIMIT 1` yang dikirim sebagai peringatan kuning `duplikat` di `halaman_admin.php:1707-1711`).
     - *Saran perbaikan*: Tambahkan panah validasi 10/13 digit dan kueri `SELECT` cek duplikat dari `Proses Cari ISBN` ke `Tabel buku` sebelum memanggil Google Books API.
  8. **[SALAH — Sedang]** Proses Simpan Buku (`Proses Kelola Buku`) tidak menampilkan **`csrf_verify()`** dan `uploadGambar()` sampul manual/kamera — Bukti: `halaman_admin.php:73-77` (`csrf_verify()`) dan `:97-98` (`uploadGambar($_FILES["gambar"])` maks 5 MB yang diprioritaskan di atas `gambar_auto`).
     - *Saran perbaikan*: Tambahkan `csrf_verify()` dan `opt [Upload sampul manual]` setelah Pesan 14, serta perbaiki `.puml` (ganti `daftar_buku.php` -> `halaman_admin.php` dan kolom tabel `buku`).

---

### Diagram 8 — Lupa Kartu / Sandi dengan Verifikasi OTP
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Sequence Diagram — Lupa Kartu / Sandi dengan Verifikasi OTP` *(subjudul: `lupa_kartu.php · includes/otp.php · includes/mailer.php`)*
  - Path PNG: `c:\laragon\www\aksanova\UML\Squence\Lengkap\S08_Lupa_Kartu_OTP.png`
  - Path PUML: **TIDAK ADA** (tidak terdapat berkas `.puml` untuk `S08` di folder `UML\Squence\Lengkap\`).
- **File PHP Aktual**: `lupa_kartu.php`, `includes/otp.php`, `includes/mailer.php`, `kartu_anggota.php`, `barcode128.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK / TANPA `.puml`** (tidak ada source `.puml` untuk pemeliharaan diagram, dan penamaan file `S08_Lupa_Kartu_OTP.png` tidak konsisten dengan pola `sequence_09_...` s/d `sequence_34_...`).
- **Rincian Apa yang Tampak di PNG**:
  - **Lifeline (7)**: `Tamu (Guest)` (`actor`), `Halaman lupa_kartu.php` (`boundary`), `Proses Lupa Kartu (otp.php)` (`control`), `Tabel users` (`entity`), `Tabel password_otp` (`entity`), `Server SMTP (PHPMailer)` (`object/rectangle`), `Halaman kartu_anggota.php` (`boundary`).
  - **Pesan Utama (`1:`–`51:`)**: Dibagi ke 3 pita seksi:
    - *LANGKAH 1 (`1:`–`23:`)*: Request OTP -> `csrf_verify()` -> `otp_batas_permintaan()` (5/jam/email, 10/jam/IP, jeda 60s) -> `SELECT users` -> `INSERT password_otp` (HMAC-SHA256 6 digit, +10 menit) -> `kirim_email()` -> jika gagal hapus OTP / jika berhasil simpan sesi `step=2` -> jika email tidak terdaftar tampilkan pesan generik.
    - *LANGKAH 2 (`24:`–`39:`)*: Verify OTP -> `csrf_verify()` -> `SELECT OTP` -> cek aktif/10m/attempts<5 -> `hash_hmac` + `hash_equals` -> jika salah `attempts + 1` -> jika benar `used_at = NOW()`, **`UPDATE card_status = 'frozen'`**, `session_regenerate_id(true)`, `step = 3`.
    - *LANGKAH 3 (`40:`–`51:`)*: Set sandi baru -> `csrf_verify()`, cek `<= 10 menit`, validasi sandi (`>= 8`, huruf+angka, cocok) -> `password_hash()` -> `UPDATE password, plain_password='', card_status='active'` -> simpan `kartu_data`, hapus `lupa_kartu`, redirect `kartu_anggota.php` -> `51: Tampilkan kartu pengganti (QR code, nomor anggota dan username tetap)` + kotak catatan kuning aksi Batal & Kirim Ulang.
  - **Kualitas Visual**: Terbaca baik pada ukuran penuh; pada Langkah 1 terdapat 4 `alt` bersarang cukup padat dan teks Pesan 51 memotong garis lifeline `Halaman lupa_kartu.php`.
- **Daftar Pemeriksaan Elemen & Temuan** (`10 diperiksa`: `2 BENAR`, `2 BENAR DENGAN CATATAN`, `6 SALAH`, `0 TIDAK TERVERIFIKASI`):
  1. **[BENAR]** Aktor `Tamu (Guest)` & representasi `Server SMTP (PHPMailer)` sebagai kotak objek non-aktor, serta ketepatan angka batas OTP (`6` digit, `10` menit, `5x` salah hangus, `60` detik cooldown, `5/jam/user`, `10/jam/IP`, sandi `>= 8` huruf+angka) — sesuai `includes/otp.php:41, 66, 77, 113, 119, 308` dan `lupa_kartu.php:279-285`.
  2. **[BENAR]** Pembekuan kartu (`UPDATE users SET card_status = 'frozen'`) dilakukan **SETELAH OTP valid di Langkah 2** (Pesan 36), bukan di Langkah 1 — sesuai `lupa_kartu.php:116, 185-190`.
  3. **[BENAR DENGAN CATATAN]** Pesan 12–16 pembatalan OTP aktif lama, penyimpanan `HMAC-SHA256`, dan *rollback* hapus baris OTP saat SMTP gagal kirim — sesuai `includes/otp.php:102-130` & `includes/mailer.php:119-127` (catatan: rollback `DELETE` dieksekusi di dalam `kirim_email()` pada `mailer.php` beserta penulisan log tersensor `storage/mail_error.log`, dan `otp_buat()` juga membersihkan baris kedaluwarsa `> 1 hari` di `:102`).
  4. **[BENAR DENGAN CATATAN]** Pesan 35–37 verifikasi OTP berhasil — urutan kode aktual adalah `UPDATE password_otp SET used_at = NOW(), attempts = attempts + 1` (`includes/otp.php:321`), lalu `session_regenerate_id(true)` (`lupa_kartu.php:182`), lalu `UPDATE users SET card_status='frozen'` (`:185`).
  5. **[SALAH — Tinggi]** Pesan 51 menulis **`(QR code, nomor anggota dan username tetap)`** — Bukti: `kartu_anggota.php:108, 696` dan `barcode128.php:5-71`. Kartu anggota AKSA NOVA merender **Barcode Code 128 Subset B SVG**, **BUKAN QR code**!
     - *Saran perbaikan*: Ubah teks Pesan 51 menjadi `51: Tampilkan kartu pengganti (Barcode Code 128, nomor anggota dan username tetap)`.
  6. **[SALAH — Sedang]** Urutan Pesan 6–11 memeriksa rate-limit per email/user & cooldown 60 detik **sebelum** mencari user di `Tabel users` — Bukti: `lupa_kartu.php:75-108` & `includes/otp.php:29-88`. Di kode, sebelum `SELECT ... FROM users WHERE email = ?` (`:86`), `otp_batas_permintaan($conn, null, $client_ip)` (`:79`) **hanya memeriksa batas IP (`10/jam`)** karena `user_id` masih `null`. Pengecekan cooldown `60 detik` dan batas `5/jam per user` baru dipanggil di `:105` **setelah** akun ditemukan dan dinyatakan layak (`role === 'member' && status === 'approved'`, `:99`).
     - *Saran perbaikan*: Pisahkan cek rate-limit IP (`10/jam`) sebelum `SELECT users`, dan cek rate-limit user (`60s cooldown` & `5/jam`) di dalam cabang `[Email terdaftar & eligible]`.
  7. **[SALAH — Sedang]** Cabang `[Email tidak terdaftar]` (Pesan 22–23) tidak memodelkan sesi *dummy* Langkah 2 anti-enumerasi — Bukti: `lupa_kartu.php:139-150, 170-176`. Ketika email tidak terdaftar/bukan member `approved`, kode melakukan jeda acak `usleep(150000..250000)`, menyimpan `$_SESSION['lupa_kartu'] = ['step' => 2, 'is_dummy' => true, ...]`, dan **tetap me-redirect pengguna ke form 6 kotak OTP Langkah 2** (bukan berhenti di Langkah 1).
     - *Saran perbaikan*: Gambarkan `usleep(150-250ms)`, set `$_SESSION['lupa_kartu']` (`step=2, is_dummy=true`), dan tampilkan form Langkah 2 yang sama; serta tambahkan cabang `is_dummy` pada Langkah 2.
  8. **[SALAH — Sedang]** Langkah 2 Pesan 27 (`WHERE user_id = ? AND used_at IS NULL`) dan Pesan 30 (`Arahkan kembali ke Langkah 1`) tidak sesuai kode — Bukti: `includes/otp.php:258, 268, 292-314` & `lupa_kartu.php:198-200`. (a) Kode memvalidasi regex `/^[0-9]{6}$/` dulu (`:258`), lalu menjalankan `SELECT ... FROM password_otp WHERE user_id = ? ORDER BY id DESC LIMIT 1` (tanpa `AND used_at IS NULL` di SQL agar bisa memberi pesan spesifik `already_used` di `:292`); (b) saat gagal/kedaluwarsa/hangus, kode **tetap berada di Langkah 2** (tidak me-redirect ke Langkah 1).
     - *Saran perbaikan*: Perbaiki klausa SQL Pesan 27 dan ubah Pesan 30 menjadi tetap menampilkan pesan error di Langkah 2 dengan tombol Kirim Ulang / Ganti Email.
  9. **[SALAH — Sedang]** Langkah 3 cabang `[Verifikasi lewat 10 menit atau sandi tidak valid]` (Pesan 43–44) menyatukan dua jalur yang perilakunya berbeda jauh — Bukti: `lupa_kartu.php:262-274` vs `:279-285`. Jika waktu sejak `otp_verified_at > 600` detik, kode **mencairkan kembali kartu (`UPDATE users SET card_status='active' WHERE id=?`)**, menghapus `$_SESSION['lupa_kartu']`, dan me-redirect ke `lupa_kartu.php?expired=1` (Langkah 1). Hanya jika sandi tidak valid halaman bertahan di Langkah 3.
     - *Saran perbaikan*: Pisahkan cabang `alt [Verifikasi > 10 menit]` (UPDATE `card_status='active'` + unset session + redirect Langkah 1) dari `[Sandi tidak valid]`.
  10. **[SALAH — Sedang]** Catatan kuning di bawah menulis `Kirim ulang kode = mengulang Langkah 1`, serta tidak ada berkas sumber `.puml` — Bukti: `lupa_kartu.php:62-66, 207-247`. Kirim ulang kode ditangani oleh aksi tersendiri `POST action=resend_otp` di **Langkah 2** (cek jeda 60s -> `otp_batas_permintaan` -> `otp_buat` -> `otp_kirim`), sedangkan yang mengulang Langkah 1 adalah `POST action=change_email`.
      - *Saran perbaikan*: Koreksi catatan aksi `resend_otp` vs `change_email`, dan buat berkas `sequence_08_lupa_kartu_otp.puml` agar terdokumentasi sebagai kode.

---

### Diagram 9 — Edit Data Kartu Anggota
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Sequence Diagram — Edit Data Kartu Anggota` *(subjudul: `edit_kartu.php`)*
  - Path PNG: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_09_edit_kartu_anggota.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_09_edit_kartu_anggota.puml`
- **File PHP Aktual**: `edit_kartu.php`, `kartu_anggota.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK (PUML Templat Generik Palsu)**
  - `.puml` (`2026-11-03`) adalah templat generik 41 baris yang menulis nama file karangan **`member/profil.php`** (`:12, 15, 16`) tanpa satu pun logika `edit_kartu.php`.
  - `.png` (`2026-10-10`) menampilkan 5 lifeline dan 21 pesan spesifik untuk `edit_kartu.php`.
- **Rincian Apa yang Tampak di PNG**:
  - **Lifeline (5)**: `Anggota` (`actor`), `Halaman Edit Kartu` (`boundary`), `Proses Edit Kartu` (`control`), `Tabel users` (`entity`), `Penyimpanan Foto` (`object/rectangle`).
  - **Pesan Utama (`1:`–`21:`)**: `1-4` Buka halaman, periksa sesi, `SELECT` data akun -> `5-6` blokir jika `pending/ditolak/frozen` / `7-8` tampilkan data terbaru & daftar kelas -> `9-10` POST `action = update_data` -> `11` Validasi nama, kelas, email sekolah, no. HP, sandi opsional -> `14-15` `opt [Ada foto baru]` simpan foto (`JPG/PNG/WEBP <= 5 MB`) -> `16-17` `UPDATE users` -> `18-19` `opt [Foto diganti]` hapus foto lama -> `20-21` Redirect ke `kartu_anggota.php`.
  - **Blok Fragmen**: `alt [Pending / ditolak / kartu dibekukan] / [Akun boleh mengedit]`, `alt [Validasi gagal] / [Validasi lolos]`, `opt [Ada foto baru]`, `opt [Foto diganti]`.
  - **Kualitas Visual**: Bersih, proporsional, penomoran 1–21 urut.
- **Daftar Pemeriksaan Elemen & Temuan** (`7 diperiksa`: `2 BENAR`, `3 BENAR DENGAN CATATAN`, `2 SALAH`, `0 TIDAK TERVERIFIKASI`):
  1. **[BENAR]** `Penyimpanan Foto` digambar sebagai objek kotak (bukan `actor`), serta urutan `opt [Ada foto baru]` simpan foto `JPG/PNG/WEBP <= 5 MB` -> `UPDATE users` -> `opt [Foto diganti]` `@unlink` foto lama — persis sesuai `edit_kartu.php:186-263`.
  2. **[BENAR]** Nama aksi POST `action = update_data` — sesuai `edit_kartu.php:67`.
  3. **[BENAR DENGAN CATATAN]** Aktor `Anggota` — gunakan nama baku `Anggota (Siswa & Guru)`.
  4. **[BENAR DENGAN CATATAN]** Pesan 2–6 pemeriksaan sesi & blokir `pending`/`rejected`/`frozen` — di `edit_kartu.php:12-19`, Tamu langsung di-redirect ke `sign_in.php` dan Admin ke `dashboard.php` sebelum kueri DB, sementara member `pending`/`rejected`/`frozen` ditampilkan pesan blokir `$blocked` (`:43-49`).
  5. **[BENAR DENGAN CATATAN]** Pesan 20–21 `Redirect ke kartu_anggota.php` — di `edit_kartu.php:266-289`, kode juga menyelaraskan `$_SESSION['user_name']` & `$_SESSION['username']` serta mengisi `$_SESSION['kartu_data']` (`data_updated_only => true`) sebelum HTTP redirect ke halaman terpisah `kartu_anggota.php`.
  6. **[SALAH — Tinggi]** Pesan 7 mengklaim `daftar kelas` dan Pesan 11 mengklaim `Validasi nama, kelas, email sekolah...`, sembari menghilangkan **`username`** dan **`nik` (NIS/NIP)** — Bukti: `edit_kartu.php:69-74, 99-127`. Kolom **`kelas` TIDAK BISA DIEDIT** di `edit_kartu.php` (`:72`: `$new_kelas = $user["kelas"] ?? ""`, tidak ada `$_POST['kelas']` maupun dropdown daftar kelas). Sebaliknya, field wajib yang diedit & divalidasi adalah `full_name`, **`username`** (`3–30` kar), **`nik`** (NIS/NIP), `email` (**termasuk `@gmail.com`** di `:119`, bukan hanya email sekolah!), `no_hp` opsional, serta sandi opsional (wajib verifikasi `old_password` & berbeda dari sandi lama di `:130-145`).
     - *Saran perbaikan*: Hapus klaim `daftar kelas` & validasi `kelas`; ganti dengan `username`, `nik (NIS/NIP)`, domain email (`sekolah / @gmail.com`), dan verifikasi `old_password`.
  7. **[SALAH — Sedang]** Hilangnya **`csrf_verify()`** dan **3 kueri `SELECT` pengecekan duplikasi** ke `Tabel users` sebelum unggah foto/`UPDATE` — Bukti: `edit_kartu.php:97-98` (`csrf_verify()`) dan `edit_kartu.php:150-182` (`SELECT id FROM users WHERE username = ? AND id <> ?`, `WHERE nik = ? AND id <> ?`, `WHERE email = ? AND id <> ?`).
     - *Saran perbaikan*: Tambahkan `csrf_verify()` dan 3 kueri cek keunikan (`username`, `nik`, `email`) sebelum `opt [Ada foto baru]`, serta ganti total isi `sequence_09_edit_kartu_anggota.puml` yang masih berupa templat `member/profil.php`.

---

### Diagram 10 — Suka (Like) & Simpan Buku
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Sequence Diagram — Suka (Like) & Simpan Buku` *(subjudul: `like_handler.php`)*
  - Path PNG: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_10_like_simpan_buku.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_10_like_simpan_buku.puml`
- **File PHP Aktual**: `like_handler.php` (dipanggil dari `beranda.php`, `daftar_buku.php`, `buku_simpan.php`)
- **Kecocokan PNG vs PUML**: **TIDAK COCOK (PUML Templat Generik Palsu)**
  - `.puml` (`2026-11-03`) adalah templat generik yang mengarang nama file **`api/like.php & api/favorite.php`** (`:12, 15, 16`), mengklaim ada **`csrf_verify()`** (`:24`), dan mengklaim **`prepared statement`** (`:25`) — ketiganya **100% palsu**!
  - `.png` (`2026-10-10`) memakai nama file benar `like_handler.php`, tidak mengklaim `csrf_verify()`, dan menggambarkan alur toggle dengan akurat.
- **Rincian Apa yang Tampak di PNG**:
  - **Lifeline (4)**: `Anggota` (`actor`), `Halaman Katalog / Detail` (`boundary`), `Proses Like Handler` (`control`), `Tabel buku_likes / buku_favorites` (`entity`).
  - **Pesan Utama (`1:`–`14:`)**: `1` Klik ikon Suka/Simpan -> `2` Kirim AJAX (`buku_id, type`) -> `3-4` jika gagal tolak JSON `ok = false` / `5-6` jika valid `SELECT` data milik user -> `7-8` `[Data sudah ada]` `DELETE` (toggle off) / `9-10` `[Data belum ada]` `INSERT` (toggle on) -> `11-12` `COUNT` total untuk buku -> `13` `JSON ok, aktif, total` -> `14` Perbarui ikon & jumlah.
  - **Blok Fragmen**: `alt [Belum login / role admin / data tidak valid] / [Valid]`, bersarang `alt [Data sudah ada] / [Data belum ada]`.
  - **Kualitas Visual**: Sangat bersih, terbaca jelas, penomoran 1–14 urut.
- **Daftar Pemeriksaan Elemen & Temuan** (`6 diperiksa`: `4 BENAR`, `1 BENAR DENGAN CATATAN`, `1 SALAH`, `0 TIDAK TERVERIFIKASI`):
  1. **[BENAR]** Pesan 2 `Kirim AJAX (buku_id, type)` tanpa token CSRF — akurat sesuai fakta kode `like_handler.php:21-22` yang memang tidak memiliki `csrf_verify()`.
  2. **[BENAR]** Guard server `like_handler.php:7-27` hanya mengecek sesi `user_id`, `role !== 'admin'`, `buku_id > 0`, dan `type in ['like','favorite']` (tanpa mengecek ulang tabel `users`).
  3. **[BENAR]** Urutan kueri toggle (`SELECT id` -> `DELETE` atau `INSERT` pada `buku_likes`/`buku_favorites` -> `SELECT COUNT(*) AS total`) — 100% sesuai `like_handler.php:29-46`.
  4. **[BENAR]** Struktur respons Pesan 13 `JSON ok, aktif, total` — 100% sesuai `like_handler.php:48`.
  5. **[BENAR DENGAN CATATAN]** Aktor `Anggota` — gunakan nama baku `Anggota (Siswa & Guru)`.
  6. **[SALAH — Rendah]** Pesan 4 mengklaim `4: Tampilkan pesan penolakan` di antarmuka saat menerima `ok = false` — Bukti: `beranda.php:2562, 2569, 2580, 2587` & `daftar_buku.php:1291, 1298, 1521, 1528, 1543, 1551`. Di kode klien, pengunjung Tamu sudah dicegat oleh `if (IS_GUEST) return butuhLogin();` (redirect ke `sign_in.php`) sebelum AJAX dikirim, sedangkan pada callback `fetch`, kode menulis `if (!data.ok) return;` sehingga **tidak menampilkan pesan penolakan apa pun** ke layar. Selain itu berkas `.puml` wajib diganti dari templat palsu `api/like.php` (dan jangan mencantumkan `csrf_verify()` selama kode PHP belum memilikinya).
     - *Saran perbaikan*: Ubah Pesan 4 menjadi pengabaian respons (`if (!data.ok) return`) dan tambahkan catatan pencegatan Tamu di klien (`butuhLogin() -> sign_in.php`), serta tulis ulang `sequence_10_like_simpan_buku.puml` sesuai `.png`.

---

### Diagram 11 — Memberi Rating Buku
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Sequence Diagram — Memberi Rating Buku` *(subjudul: `rating_handler.php`)*
  - Path PNG: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_11_rating_buku.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_11_rating_buku.puml`
- **File PHP Aktual**: `rating_handler.php` (dipanggil dari modal detail di `beranda.php`, `daftar_buku.php`, `buku_simpan.php`)
- **Kecocokan PNG vs PUML**: **TIDAK COCOK (PUML Templat Generik Palsu)**
  - `.puml` (`2026-11-03`) adalah templat generik yang mengarang file palsu **`api/rate.php`** (`:12, 15, 16`), mengklaim ada **`csrf_verify()`** (`:24`), dan mengklaim **`prepared statement`** (`:25`) — seluruhnya salah!
  - `.png` (`2026-10-10`) memakai subjudul benar `rating_handler.php`, tanpa klaim `csrf_verify()`, dan akurat terhadap alur POST rating.
- **Rincian Apa yang Tampak di PNG**:
  - **Lifeline (4)**: `Anggota` (`actor`), `Halaman Detail Buku` (`boundary`), `Proses Rating` (`control`), `Tabel buku_ratings` (`entity`).
  - **Pesan Utama (`1:`–`14:`)**: `1` Pilih bintang (`1–5`) -> `2` `POST (buku_id, rating)` -> `3-4` jika tidak valid `JSON ok = false + pesan` / `5-6` `SELECT` rating user -> `7-8` `[Rating sudah ada]` `UPDATE rating & updated_at` / `9-10` `[Rating belum ada]` `INSERT rating baru` -> `11-12` `AVG(rating) & COUNT` -> `13` `JSON ok, user_rating, avg, total` -> `14` Perbarui bintang & rata-rata.
  - **Blok Fragmen**: `alt [Belum login / admin / rating tidak valid] / [Valid]`, bersarang `alt [Rating sudah ada] / [Rating belum ada]`.
  - **Kualitas Visual**: Sangat bersih, terbaca jelas, penomoran 1–14 urut.
- **Daftar Pemeriksaan Elemen & Temuan** (`6 diperiksa`: `4 BENAR`, `1 BENAR DENGAN CATATAN`, `1 SALAH`, `0 TIDAK TERVERIFIKASI`):
  1. **[BENAR]** Pesan 1–2 input bintang integer `1–5` via `POST (buku_id, rating)` tanpa `csrf_verify()` dan tanpa syarat pernah meminjam — sesuai `rating_handler.php:47-53`.
  2. **[BENAR]** Guard sesi `user_id` & penolakan `role === 'admin'` (HTTP 403) tanpa kueri tabel `users` — sesuai `rating_handler.php:6-17`.
  3. **[BENAR]** Urutan upsert manual (`SELECT id` -> `UPDATE rating, updated_at=NOW()` atau `INSERT (..., created_at, updated_at)` -> `SELECT AVG(rating), COUNT(*)`) — 100% sesuai `rating_handler.php:56-65`.
  4. **[BENAR]** Struktur respons Pesan 13 `JSON ok, user_rating, avg, total` — 100% sesuai `rating_handler.php:68-73`.
  5. **[BENAR DENGAN CATATAN]** Aktor `Anggota` & Boundary `Halaman Detail Buku` — gunakan nama aktor baku `Anggota (Siswa & Guru)`, dan beri keterangan bahwa `Halaman Detail Buku` berupa modal di `beranda.php`/`daftar_buku.php`/`buku_simpan.php` (serta `rating_handler.php:25-44` juga melayani `GET ?buku_id=`).
  6. **[SALAH — Rendah]** Pesan 4 mengklaim `4: Tampilkan pesan penolakan` pada UI klien saat `ok = false` — Bukti: `beranda.php:2724-2729` & `daftar_buku.php:1492-1500`. Di klien, Admin langsung di-`return`, Tamu diarahkan ke `sign_in.php` via `butuhLogin()`, dan callback `fetch` menulis `if (!data.ok) return;` tanpa menampilkan pesan penolakan. Selain itu `.puml` wajib ditulis ulang untuk menggantikan templat palsu `api/rate.php`.
     - *Saran perbaikan*: Koreksi perilaku UI pada Pesan 4 (`if (!data.ok) return`) dan tulis ulang `sequence_11_rating_buku.puml` agar selaras dengan kode `rating_handler.php`.

---

### Diagram 12 — Menelusuri Katalog & Melihat Detail Buku
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Sequence Diagram — Menelusuri Katalog & Melihat Detail Buku` *(subjudul: `daftar_buku.php · buku_detail.php`)*
  - Path PNG: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_12_telusuri_katalog_detail.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_12_telusuri_katalog_detail.puml`
- **File PHP Aktual**: `daftar_buku.php`, `buku_detail.php` (serupa pada `beranda.php`)
- **Kecocokan PNG vs PUML**: **TIDAK COCOK (PUML Templat Generik Palsu)**
  - `.puml` (`2026-11-03`) adalah templat generik yang mengarang nama file **`katalog.php & detail_buku.php`** (`:12, 15, 16`) serta mengklaim `csrf_verify()` pada penelusuran katalog GET!
  - `.png` (`2026-10-10`) memakai nama file benar `daftar_buku.php · buku_detail.php` dengan 6 lifeline dan 18 pesan spesifik.
- **Rincian Apa yang Tampak di PNG**:
  - **Lifeline (6)**: `Pengguna` (`actor`), `Halaman Daftar Buku` (`boundary`), `Proses Daftar Buku` (`control`), `Tabel buku` (`entity`), `Proses Detail Buku` (`control`), `Tabel suka / simpan / rating` (`entity`).
  - **Pesan Utama (`1:`–`18:`)**: `1-2` Buka Daftar Buku / ketik `q`, pilih `kategori`, pindah `page` -> `3-4` `SELECT` genre unik, `COUNT` & `SELECT` buku (`10` per halaman) -> `5-6` `SELECT` jumlah suka, simpan, rating buku di halaman -> `7-8` Tampilkan daftar buku -> `9-10` Pilih buku, minta detail (`id`) -> `11-12` `SELECT` buku berdasarkan `id` -> `13-14` `[Buku tidak ditemukan]` JSON 404 / `15-18` `[Buku ditemukan]` `COUNT` suka & simpan, `AVG` rating (+ status milik user bila login) -> JSON detail buku -> tampilkan detail.
  - **Blok Fragmen**: `alt [Buku tidak ditemukan] / [Buku ditemukan]`.
  - **Kualitas Visual**: Sangat bersih, terbaca jelas, penomoran 1–18 urut.
- **Daftar Pemeriksaan Elemen & Temuan** (`6 diperiksa`: `2 BENAR`, `3 BENAR DENGAN CATATAN`, `1 SALAH`, `0 TIDAK TERVERIFIKASI`):
  1. **[BENAR]** Paginasi `10` buku per halaman, filter `judul/penulis/ISBN` (`LIKE`) & `genre` eksak, serta kueri genre unik di `daftar_buku.php:34, 49-78`.
  2. **[BENAR]** Alur `buku_detail.php` (`SELECT * FROM buku WHERE id = ?` -> HTTP `404` bila kosong -> agregasi `buku_likes`, `buku_favorites`, `buku_ratings` + status user bila login -> JSON `ok: true, buku`) — 100% sesuai `buku_detail.php:67-131`.
  3. **[BENAR DENGAN CATATAN]** Aktor `Pengguna` — di `daftar_buku.php:6-9`, `Pustakawan (Admin)` justru **dilarang masuk** dan langsung di-redirect ke `dashboard.php`! Jadi aktor katalog ini hanya `Tamu (Guest)` dan `Anggota (Siswa & Guru)` (meski endpoint `buku_detail.php` juga dipakai `halaman_admin.php:1283`).
  4. **[BENAR DENGAN CATATAN]** Pesan 5–6 agregasi di `daftar_buku.php:89-114` — hanya dieksekusi bila `!empty($buku_list)` (`opt [Ada buku di halaman]`), dan bagi Anggota login turut mengambil `liked_ids` & `fav_ids` milik user (`:100-108`). Halaman juga mendukung mode fragmen HTML `?ajax=1` (`:41, 1129-1135`).
  5. **[BENAR DENGAN CATATAN]** Pesan 10 `Minta detail buku (id)` — parameter `id` disamarkan dengan `mask_id()` di katalog dan dibuka dengan `unmask_id()` di `buku_detail.php:34-40`; bila ID tidak valid (`<= 0`), `buku_detail.php:38-40` langsung mengembalikan **HTTP `400` (`ID tidak valid`)** sebelum menyentuh `Tabel buku`.
  6. **[SALAH — Rendah]** Guard sesi pada `daftar_buku.php:6-24` (Admin -> `dashboard.php`; Member dengan `status !== 'approved'` atau `card_status === 'frozen'` -> `session_destroy()` + `sign_in.php` via kueri `Tabel users`) serta cabang HTTP 400 pada `buku_detail.php:38-40` tidak tergambarkan; dan `.puml` memakai file palsu `katalog.php & detail_buku.php`.
     - *Saran perbaikan*: Ubah label aktor menjadi `Tamu (Guest) / Anggota (Siswa & Guru)`, tambahkan catatan guard sesi & dekode `unmask_id()` (HTTP 400 bila gagal), serta tulis ulang `sequence_12_telusuri_katalog_detail.puml`.

---

### Diagram 13 — Melihat Dashboard Anggota
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Sequence Diagram — Melihat Dashboard Anggota` *(subjudul: `dashboard_user.php`)*
  - Path PNG: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_13_dashboard_anggota.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_13_dashboard_anggota.puml`
- **File PHP Aktual**: `dashboard_user.php`, `barcode128.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK (PUML Templat Generik Palsu)**
  - `.puml` (`2026-11-03`) adalah templat generik yang mengarang file palsu **`member/dashboard.php`** (`:12, 15, 16`) dan `csrf_verify()`.
  - `.png` (`2026-10-10`) memakai subjudul benar `dashboard_user.php` dengan 7 lifeline dan 14 pesan.
- **Rincian Apa yang Tampak di PNG**:
  - **Lifeline (7)**: `Anggota` (`actor`), `Halaman Dashboard` (`boundary`), `Proses Dashboard` (`control`), `Tabel users` (`entity`), `Tabel peminjaman` (`entity`), `Tabel pengajuan_peminjaman` (`entity`), `Tabel buku_favorites` (`entity`).
  - **Pesan Utama (`1:`–`14:`)**: `1-2` Buka Dashboard & periksa sesi/role -> `3-4` `[Belum login / role admin]` Redirect ke Sign In / halaman admin -> `5-6` `[Anggota login]` `SELECT` profil anggota -> `7-8` `COUNT` peminjaman, `SUM` denda belum bayar, `SELECT` pinjaman aktif & `6` riwayat -> `9-10` `SELECT` `5` pengajuan terakhir -> `11-12` `SELECT` `4` buku simpan -> `13-14` Tampilkan profil, statistik, denda, riwayat & status pengajuan.
  - **Blok Fragmen**: `alt [Belum login / role admin] / [Anggota login]`.
  - **Kualitas Visual**: Bersih, terbaca jelas, penomoran 1–14 urut.
- **Daftar Pemeriksaan Elemen & Temuan** (`6 diperiksa`: `1 BENAR`, `2 BENAR DENGAN CATATAN`, `3 SALAH`, `0 TIDAK TERVERIFIKASI`):
  1. **[BENAR]** Cabang `alt [Belum login / role admin]` mengarahkan pengunjung belum login ke `sign_in.php` dan Admin ke `dashboard.php` — sesuai `dashboard_user.php:5-13`.
  2. **[BENAR DENGAN CATATAN]** Aktor `Anggota` — gunakan label baku `Anggota (Siswa & Guru)`.
  3. **[BENAR DENGAN CATATAN]** Angka batas pada Pesan 7 (`6` riwayat pengembalian), Pesan 9 (`5` pengajuan terakhir), dan Pesan 11 (`4` buku simpan) — ketiganya 100% akurat sesuai `dashboard_user.php:113, 125, 137`, dan halaman juga merender kartu anggota mini ber-**Barcode Code 128 SVG** (`barcode128.php`, `:158, 1119`).
  4. **[SALAH — Sedang]** Tidak ada cabang blokir & penghancuran sesi setelah `SELECT profil anggota` (Pesan 5–6) bila akun `status !== 'approved'` atau `card_status === 'frozen'` — Bukti: `dashboard_user.php:31-36` (`session_unset(); session_destroy(); header("Location: sign_in.php"); exit;`).
     - *Saran perbaikan*: Tambahkan `alt [Akun tidak approved / kartu frozen]` setelah Pesan 6 yang menghancurkan sesi dan mengarahkan ke `sign_in.php`.
  5. **[SALAH — Sedang]** Lifeline **`Tabel pengaturan`** dan kalkulasi estimasi denda berjalan (`$denda_berjalan`) hilang padahal Pesan 14 mengklaim menampilkan `denda` — Bukti: `dashboard_user.php:42-49, 85-104` membaca `denda_per_hari`, `denda_aktif`, dan `denda_grace_period` dari `Tabel pengaturan` lalu menghitung denda berjalan atas setiap pinjaman aktif yang melampaui `batas_kembali`.
     - *Saran perbaikan*: Tambahkan pembacaan konfigurasi denda dari `Tabel pengaturan` dan self-call perhitungan estimasi denda berjalan.
  6. **[SALAH — Rendah]** Lifeline **`Tabel buku`** hilang padahal 4 kueri utama (pinjaman aktif `:74`, 6 riwayat `:111`, 5 pengajuan `:123`, dan 4 favorit `:134`) semuanya melakukan `JOIN buku` untuk mengambil `judul, penulis, gambar`; selain itu `.puml` masih berupa templat palsu `member/dashboard.php`.
     - *Saran perbaikan*: Sertakan `Tabel buku` (atau beri label komposit `JOIN buku` pada keempat pesan) dan tulis ulang `sequence_13_dashboard_anggota.puml`.

---

### Diagram 14 — Mengelola Status Akun & Kartu Anggota
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Sequence Diagram — Mengelola Status Akun & Kartu Anggota` *(subjudul: `daftar_anggota.php`)*
  - Path PNG: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_14_status_akun_kartu_anggota.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_14_status_akun_kartu_anggota.puml`
- **File PHP Aktual**: `daftar_anggota.php` (aksi `approve`, `reject`, `unfreeze`)
- **Kecocokan PNG vs PUML**: **TIDAK COCOK (PUML Templat Generik Palsu)**
  - `.puml` (`2026-11-03`) adalah templat generik yang mengarang file palsu **`admin/anggota.php`** (`:12, 15, 16`), menulis judul salah **`(Approve/Reject/Freeze)`** (padahal fitur **Freeze oleh Admin tidak pernah ada**, yang ada adalah **`unfreeze`**!), dan mengklaim `prepared statement` (`:25`, padahal ketiga aksi memakai `mysqli_query()` biasa).
  - `.png` (`2026-10-10`) memakai nama file benar `daftar_anggota.php` dan ketiga aksi benar (`approve / reject / unfreeze`).
- **Rincian Apa yang Tampak di PNG**:
  - **Lifeline (4)**: `Admin` (`actor`), `Halaman Daftar Anggota` (`boundary`), `Proses Kelola Anggota` (`control`), `Tabel users` (`entity`).
  - **Pesan Utama (`1:`–`6:`)**: `1` Pilih anggota, klik Aktifkan / Nonaktifkan / Cairkan Kartu -> `2` Kirim aksi (`approve / reject / unfreeze`) & `id` -> `3-4` `opt [ID anggota valid (> 0)]` `UPDATE status = approved / rejected, atau card_status = active (role member)` -> `5-6` Tampilkan pesan & status terbaru.
  - **Blok Fragmen**: `opt [ID anggota valid (> 0)]`.
  - **Kualitas Visual**: Teks terbaca jelas, namun kotak latar putih pada teks Pesan 2 dan Pesan 3 memotong batang aktivasi vertikal `Halaman Daftar Anggota` dan `Proses Kelola Anggota`.
- **Daftar Pemeriksaan Elemen & Temuan** (`5 diperiksa`: `1 BENAR`, `3 BENAR DENGAN CATATAN`, `1 SALAH`, `0 TIDAK TERVERIFIKASI`):
  1. **[BENAR]** Ketiga nama aksi (`approve`, `reject`, `unfreeze`) beserta efek kolomnya (`status='approved'`, `status='rejected'`, `card_status='active'`) dan klausa pengaman `WHERE id=$id AND role='member'` di dalam `if ($id > 0)` — 100% sesuai `daftar_anggota.php:57-79`.
  2. **[BENAR DENGAN CATATAN]** Aktor `Admin` — gunakan nama baku `Pustakawan (Admin)`.
  3. **[BENAR DENGAN CATATAN]** Pesan 6 `Tampilkan pesan & status terbaru` — setelah `UPDATE`, skrip melanjutkan eksekusi `SELECT * FROM users WHERE role = 'member' ...` (`daftar_anggota.php:174`) untuk merender ulang tabel anggota.
  4. **[BENAR DENGAN CATATAN]** Kualitas visual label Pesan 2 & 3 memotong batang aktivasi lifeline — rapikan margin/lebar antar lifeline saat me-render ulang.
  5. **[SALAH — Sedang]** Guard sesi/role Admin (`daftar_anggota.php:5-8`) dan **`csrf_verify()`** (`daftar_anggota.php:51-55`) tidak dimodelkan di PNG, sementara `.puml` memakai file palsu `admin/anggota.php` dan istilah salah `Freeze` — Bukti: `daftar_anggota.php:51-55` membatalkan aksi POST bila `csrf_verify()` gagal.
     - *Saran perbaikan*: Tambahkan `csrf_verify()` (`alt [CSRF tidak valid] / [CSRF valid]`) sebelum `opt [ID anggota valid (> 0)]`, dan tulis ulang `sequence_14_status_akun_kartu_anggota.puml`.

---

### Diagram 15 — Reset Sandi Anggota
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Sequence Diagram — Reset Sandi Anggota` *(subjudul: `daftar_anggota.php`)*
  - Path PNG: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_15_reset_sandi_anggota.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_15_reset_sandi_anggota.puml`
- **File PHP Aktual**: `daftar_anggota.php` (aksi `reset_password`)
- **Kecocokan PNG vs PUML**: **TIDAK COCOK (PUML Templat Generik Palsu)**
  - `.puml` (`2026-11-03`) adalah templat generik yang mengarang file palsu **`admin/reset_password.php`** (`:12, 15, 16`).
  - `.png` (`2026-10-10`) memakai subjudul benar `daftar_anggota.php` dengan 4 lifeline dan 9 pesan.
- **Rincian Apa yang Tampak di PNG**:
  - **Lifeline (4)**: `Admin` (`actor`), `Halaman Daftar Anggota` (`boundary`), `Proses Kelola Anggota` (`control`), `Tabel users` (`entity`).
  - **Pesan Utama (`1:`–`9:`)**: `1` Klik Reset Sandi pada anggota -> `2` Kirim aksi `reset_password` & `id` -> `3-4` `SELECT` nama & username anggota -> `5` `opt [Anggota ditemukan]` Buat sandi acak 8 karakter lalu `password_hash` -> `6-7` `UPDATE password (hash)` -> `8-9` Tampilkan sandi baru sekali tampil.
  - **Blok Fragmen**: `opt [Anggota ditemukan]`.
  - **Kualitas Visual**: Teks terbaca jelas (kotak teks Pesan 5 sedikit menimpa garis putus-putus `Tabel users`, namun tidak menghalangi keterbacaan).
- **Daftar Pemeriksaan Elemen & Temuan** (`6 diperiksa`: `2 BENAR`, `2 BENAR DENGAN CATATAN`, `2 SALAH`, `0 TIDAK TERVERIFIKASI`):
  1. **[BENAR]** Pesan 3 `SELECT nama & username anggota` — sesuai `daftar_anggota.php:107` (`SELECT full_name, username FROM users WHERE id=$id AND role='member'`).
  2. **[BENAR]** Pesan 8–9 `Nama, username & sandi baru` ditampilkan sekali saja (`$reset_info`) tanpa disimpan ke database dalam bentuk teks polos — sesuai `daftar_anggota.php:136-141`.
  3. **[BENAR DENGAN CATATAN]** Aktor `Admin` — gunakan nama baku `Pustakawan (Admin)`.
  4. **[BENAR DENGAN CATATAN]** Pesan 6 `UPDATE password (hash)` — kueri aktual di `daftar_anggota.php:131` juga mengosongkan kolom warisan `plain_password` (`UPDATE users SET password=?, plain_password='' WHERE id=? AND role='member'`).
  5. **[SALAH — Sedang]** Pesan 1, 2, dan 5 menghilangkan input **`custom_password`** pada Modal Ubah Kata Sandi Anggota dan mengklaim server selalu membuat sandi acak 8 karakter — Bukti: `daftar_anggota.php:105, 111-127, 1032-1078`. Di UI aktual, klik tombol membuka `#ubahSandiModalOverlay` tempat Admin dapat **mengetik sandi kustom** atau menekan tombol JS `Acak` (`generateRandomSandi()`), lalu mengirim `custom_password`. Di PHP (`:111-127`), jika `$custom_pw !== ""` maka `$new_password = $custom_pw`, dan **hanya jika `$custom_pw === ""`** server membangkitkan sandi acak 8 karakter.
     - *Saran perbaikan*: Gambarkan pengisian `custom_password` (manual / tombol Acak) di modal, dan ubah Pesan 5 menjadi `alt [custom_password diisi] gunakan custom_password / [kosong] buat sandi acak 8 karakter`, lalu `password_hash()`.
  6. **[SALAH — Sedang]** Guard sesi/role Admin (`daftar_anggota.php:5-8`), **`csrf_verify()`** (`daftar_anggota.php:51-55`), dan syarat `if ($id > 0)` (`:106`) tidak dimodelkan di PNG, serta `.puml` memakai file palsu `admin/reset_password.php`.
     - *Saran perbaikan*: Tambahkan `csrf_verify()` sebelum kueri DB dan tulis ulang `sequence_15_reset_sandi_anggota.puml`.

---

### Diagram 16 — Menghapus Anggota
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Sequence Diagram — Menghapus Anggota` *(subjudul: `daftar_anggota.php`)*
  - Path PNG: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_16_hapus_anggota.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_16_hapus_anggota.puml`
- **File PHP Aktual**: `daftar_anggota.php` (aksi `hapus`)
- **Kecocokan PNG vs PUML**: **TIDAK COCOK (PUML Templat Generik Palsu)**
  - `.puml` (`2026-11-03`) adalah templat generik yang mengarang file palsu **`admin/anggota.php`** (`:12, 15, 16`).
  - `.png` (`2026-10-10`) memakai subjudul benar `daftar_anggota.php` dengan 6 lifeline dan 14 pesan akurat.
- **Rincian Apa yang Tampak di PNG**:
  - **Lifeline (6)**: `Admin` (`actor`), `Halaman Daftar Anggota` (`boundary`), `Proses Kelola Anggota` (`control`), `Tabel peminjaman` (`entity`), `Tabel users` (`entity`), `Penyimpanan Foto` (`object/rectangle`).
  - **Pesan Utama (`1:`–`14:`)**: `1` Klik Hapus & konfirmasi -> `2` Kirim aksi `hapus` & `id` -> `3-4` `COUNT` riwayat peminjaman anggota -> `5-6` `[Punya riwayat peminjaman]` pesan gagal / `7-8` `[Tidak punya riwayat]` `SELECT` foto anggota -> `9-10` `opt [Foto ada]` hapus file foto -> `11-12` `DELETE` anggota (`role member`) -> `13-14` Tampilkan daftar anggota terbaru.
  - **Blok Fragmen**: `alt [Punya riwayat peminjaman] / [Tidak punya riwayat]`, bersarang `opt [Foto ada]`.
  - **Kualitas Visual**: Sangat bersih, proporsional, penomoran 1–14 urut tanpa cacat.
- **Daftar Pemeriksaan Elemen & Temuan** (`5 diperiksa`: `3 BENAR`, `1 BENAR DENGAN CATATAN`, `1 SALAH`, `0 TIDAK TERVERIFIKASI`):
  1. **[BENAR]** `Penyimpanan Foto` digambar sebagai kotak objek (bukan `actor`).
  2. **[BENAR]** Pengecekan integritas `SELECT COUNT(*) AS c FROM peminjaman WHERE user_id=$id` menolak penghapusan bila `$jml > 0` — sesuai `daftar_anggota.php:84-89`.
  3. **[BENAR]** Urutan `SELECT foto FROM users WHERE id=$id AND role='member'` -> `opt [Foto ada]` `@unlink` -> `DELETE FROM users WHERE id=$id AND role='member'` — 100% sesuai `daftar_anggota.php:91-98` (diikuti kueri daftar terbaru `:174`).
  4. **[BENAR DENGAN CATATAN]** Aktor `Admin` — gunakan nama baku `Pustakawan (Admin)` (dan dapat diberi catatan bahwa `DELETE FROM users` otomatis menghapus relasi `ON DELETE CASCADE` di `password_otp`, `buku_likes`, `buku_favorites`, `buku_ratings`, serta `pengajuan_peminjaman`).
  5. **[SALAH — Sedang]** Guard sesi/role Admin (`daftar_anggota.php:5-8`), **`csrf_verify()`** (`daftar_anggota.php:51-55`), dan validasi `if ($id > 0)` (`:83`) tidak dimodelkan di PNG, sementara `.puml` memakai file palsu `admin/anggota.php`.
     - *Saran perbaikan*: Tambahkan `csrf_verify()` dan `opt [id > 0]` sebelum Pesan 3, serta tulis ulang `sequence_16_hapus_anggota.puml` sesuai alur nyata `daftar_anggota.php`.

---

:|---|:---:|---:|---:|---:|---:|---:|:---:|
| 20 | `sequence_20_edit_buku` | TIDAK COCOK | 7 | 2 | 3 | 2 | 0 | `Tinggi` (PUML) / `Sedang` (PNG) |
| 21 | `sequence_21_hapus_buku` | TIDAK COCOK | 6 | 3 | 2 | 1 | 0 | `Tinggi` (PUML) / `Sedang` (PNG) |
| 22 | `sequence_22_hapus_riwayat_peminjaman` | TIDAK COCOK | 5 | 1 | 3 | 1 | 0 | `Tinggi` (PUML) / `Sedang` (PNG) |
| 23 | `sequence_23_pengaturan_denda` | TIDAK COCOK | 6 | 2 | 2 | 2 | 0 | `Tinggi` (PUML) / `Sedang` (PNG) |
| 24 | `sequence_24_pengingat_keterlambatan_wa` | TIDAK COCOK | 6 | 1 | 0 | 5 | 0 | **`Kritis`** |
| 25 | `sequence_25_tambah_banner` | TIDAK COCOK | 6 | 1 | 3 | 2 | 0 | `Tinggi` (PUML) / `Sedang` (PNG) |
| 26 | `sequence_26_ubah_banner` | TIDAK COCOK | 5 | 2 | 2 | 1 | 0 | `Tinggi` (PUML) / `Sedang` (PNG) |
| 27 | `sequence_27_atur_banner` | TIDAK COCOK | 5 | 1 | 2 | 2 | 0 | `Tinggi` (PUML) / `Sedang` (PNG) |
| 28 | `sequence_28_pengaturan_musik` | TIDAK COCOK | 5 | 2 | 2 | 1 | 0 | **`Tinggi`** |
| 29 | `sequence_29_hapus_musik` | TIDAK COCOK | 5 | 2 | 3 | 0 | 0 | `Tinggi` (PUML) / `Rendah` (PNG) |
| 30 | `sequence_30_dashboard_admin_ekspor` | TIDAK COCOK | 5 | 2 | 2 | 1 | 0 | `Tinggi` (PUML) / `Rendah` (PNG) |
| 31 | `sequence_31_profil_sandi_admin` | TIDAK COCOK | 6 | 2 | 2 | 2 | 0 | `Tinggi` (PUML) / `Sedang` (PNG) |
| 32 | `sequence_32_logout` | TIDAK COCOK | 4 | 0 | 2 | 2 | 0 | **`Tinggi`** |
| 33 | `sequence_33_notifikasi_pengajuan` | TIDAK COCOK | 5 | 2 | 2 | 1 | 0 | `Tinggi` (PUML) / `Rendah` (PNG) |
| 34 | `sequence_34_pengaturan_lokasi` | TIDAK COCOK | 5 | 1 | 3 | 1 | 0 | `Tinggi` (PUML) / `Sedang` (PNG) |
| **Total** | **15 Pasang (`.png` + `.puml`)** | **0 Cocok / 15 Tidak Cocok** | **81** | **24** | **33** | **24** | **0** | **`Kritis`** |

---

## Laporan Rinci Per Diagram (20 s/d 34)

---

### 1. Diagram #20 — Mengedit Data Buku
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Sequence Diagram — Mengedit Data Buku` (Subjudul: `halaman_admin.php`)
  - Gambar: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_20_edit_buku.png`
  - Sumber: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_20_edit_buku.puml`
- **File PHP Aktual**: `halaman_admin.php` (`:38-67, 72-77, 110-137, 179, 1141-1232`) — serta opsional `cari_isbn.php` bila memakai tombol Cari ISBN di modal edit (`halaman_admin.php:1186, 1650`).
- **Kecocokan PNG vs PUML**: **TIDAK COCOK** (`Tinggi`).
  - `.puml` (`1.287 B`) adalah templat generik 41 baris berjudul *Penyuntingan Metadata & Stok Koleksi Buku (`File: admin/buku.php` — berkas fiktif)* dengan 4 partisipan generik dan klaim `Eksekusi prepared statement query`.
  - `.png` menampilkan subjudul `halaman_admin.php`, 5 partisipan spesifik (`Admin`, `Halaman Kelola Buku`, `Proses Kelola Buku`, `Penyimpanan Cover`, `Tabel buku`), dan 11 pesan berurutan termasuk `opt [Ada cover baru diunggah]` dan prioritas pemilihan cover.
- **Rincian Apa yang Tampak di PNG**:
  - **Partisipan (5)**: `Admin` (`actor` stick figure), `Halaman Kelola Buku` (`boundary`), `Proses Kelola Buku` (`control`), `Penyimpanan Cover` (`participant` kotak), `Tabel buku` (`entity`).
  - **Alur Pesan (1–11)**: `1:` Klik Edit, ubah data buku lalu Simpan -> `2:` Kirim data (`action = update`) + cover baru -> `alt [Judul kosong atau ID tidak valid]` (`3:` Pesan: data tidak valid -> `4:` Tampilkan pesan error) / `[Data valid]` -> `opt [Ada cover baru diunggah]` (`5:` Simpan file cover baru -> `6:` Path cover baru) -> self-call `7:` Pilih cover: unggahan > hasil ISBN > cover lama -> `8:` UPDATE data buku -> `9:` Data diperbarui -> `10:` Pesan buku berhasil diperbarui -> `11:` Tampilkan data buku terbaru.
  - **Kualitas Visual PNG**: Tajam, proporsional, bebas mojibake; ujung kanan teks pesan `2:` sedikit menyentuh batang aktivasi `Proses Kelola Buku` namun tetap terbaca jelas.
- **Daftar Pemeriksaan Elemen & Temuan** *(7 aspek: 2 `BENAR`, 3 `BENAR DENGAN CATATAN`, 2 `SALAH`, 0 `TIDAK TERVERIFIKASI`)*:
  1. **[BENAR] Nama Berkas Tujuan (`halaman_admin.php`)**: Sesuai pemroses CRUD buku `halaman_admin.php:110-137`.
  2. **[BENAR] Prioritas Pemilihan Sampul & Tidak Dihapusnya Sampul Lama**: Pesan `7: Pilih cover: unggahan > hasil ISBN > cover lama` akurat mencerminkan `$gambar_final = $gambar_baru !== "" ? $gambar_baru : ($gambar_auto !== "" ? $gambar_auto : $gambar_lama)` (`halaman_admin.php:124`), dan PNG benar **tidak** menggambarkan `unlink` sampul lama karena aksi `update` memang tidak menghapus file sampul lama.
  3. **[BENAR DENGAN CATATAN — `Rendah`] Penamaan Aktor (`Admin`)**: Menggunakan aktor manusia yang sah (`halaman_admin.php:6`), tetapi tertulis `"Admin"`, belum diseragamkan menjadi `Pustakawan (Admin)`.
  4. **[SALAH — `Sedang`] Guard Sesi Admin & Verifikasi CSRF (`csrf_verify()`) Tidak Digambarkan**: `halaman_admin.php:6-9` memeriksa sesi `admin` dan `halaman_admin.php:73-77` memanggil `csrf_verify()` (token disisipkan `csrf_field()` di `:1142`); keduanya absen di PNG.
  5. **[SALAH — `Sedang`] Urutan `uploadGambar()` terhadap Validasi `Judul / ID` Terbalik dari Kode**: Di PNG, `opt [Ada cover baru diunggah]` (`5–6`) berada di dalam cabang `[Data valid]` setelah `alt [Judul kosong atau ID tidak valid]`. Faktanya di `halaman_admin.php:123-128`, `uploadGambar($_FILES["gambar"] ?? null, $upload_error)` (`:123`) dijalankan **sebelum** `if ($judul === "" || $id === 0)` (`:126`), sehingga file cover baru tetap tersimpan ke `uploads/gambar/` walaupun judul kosong atau `$id === 0` (berbeda dari `tambah` di `:91-97`). Selain itu kode hanya mengecek `$id === 0`, bukan `$id <= 0`.
  6. **[BENAR DENGAN CATATAN — `Rendah`] Validasi `uploadGambar()` & Perilaku Non-Blokir Saat Gagal Unggah**: `uploadGambar()` memvalidasi ukuran `<= 5 MB`, ekstensi `jpg/jpeg/png/webp/gif`, dan `mime_content_type` (`halaman_admin.php:45-63`); bila unggah gagal (`$upload_error !== ""`), `UPDATE buku` **tetap dieksekusi** dengan fallback gambar dan pesan sukses ditambahi `" Catatan: $upload_error"` (`:134-135`). Detail ini belum tercermin di `opt [Ada cover baru diunggah]`.
  7. **[BENAR DENGAN CATATAN — `Rendah`] Kueri `UPDATE` & Kueri Muat Ulang Daftar Buku**: `8: UPDATE data buku` sesuai `halaman_admin.php:130-133` (memakai `mysqli_real_escape_string`), tetapi sebelum `11: Tampilkan data buku terbaru`, sistem juga mengeksekusi `SELECT * FROM buku $where ORDER BY id DESC` (`:179`).
  - **Saran Perbaikan**: Tulis ulang `sequence_20_edit_buku.puml` agar sinkron dengan `halaman_admin.php`; ubah label aktor menjadi `Pustakawan (Admin)`; tambahkan pengecekan sesi & `csrf_verify()` sebelum `action = update`; sesuaikan posisi `uploadGambar()` sesuai urutan kode (atau perbaiki kode PHP `:123` agar memvalidasi judul/ID lebih dulu seperti aksi `tambah`), tambahkan catatan validasi `<= 5 MB` + MIME, serta gambarkan `SELECT * FROM buku` saat memuat ulang tabel.

---

### 2. Diagram #21 — Menghapus Buku
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Sequence Diagram — Menghapus Buku` (Subjudul: `halaman_admin.php`)
  - Gambar: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_21_hapus_buku.png`
  - Sumber: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_21_hapus_buku.puml`
- **File PHP Aktual**: `halaman_admin.php` (`:72-77, 140-162, 179, 1237-1241`).
- **Kecocokan PNG vs PUML**: **TIDAK COCOK** (`Tinggi`).
  - `.puml` (`1.284 B`) adalah templat generik 41 baris yang merujuk `admin/buku.php` (fiktif) tanpa pengecekan `peminjaman` maupun `unlink` cover; sedangkan `.png` memiliki 6 partisipan dan 14 pesan spesifik.
- **Rincian Apa yang Tampak di PNG**:
  - **Partisipan (6)**: `Admin` (`actor`), `Halaman Kelola Buku` (`boundary`), `Proses Kelola Buku` (`control`), `Tabel peminjaman` (`entity`), `Tabel buku` (`entity`), `Penyimpanan Cover` (`participant` kotak).
  - **Alur Pesan (1–14)**: `1:` Klik Hapus pada buku & konfirmasi -> `2:` Kirim aksi hapus & id -> `3:` COUNT riwayat peminjaman buku -> `4:` Jumlah riwayat -> `alt [Masih punya riwayat peminjaman]` (`5:` Pesan gagal: hapus riwayat dulu -> `6:` Tampilkan pesan error) / `[Tidak punya riwayat]` -> `7:` SELECT gambar buku -> `8:` Path cover -> `opt [Cover ada]` (`9:` Hapus file cover -> `10:` File terhapus) -> `11:` DELETE buku -> `12:` Buku terhapus -> `13:` Pesan buku berhasil dihapus -> `14:` Tampilkan daftar buku terbaru.
  - **Kualitas Visual PNG**: Sangat rapi, terbaca jelas, tanpa tumpang tindih maupun mojibake.
- **Daftar Pemeriksaan Elemen & Temuan** *(6 aspek: 3 `BENAR`, 2 `BENAR DENGAN CATATAN`, 1 `SALAH`, 0 `TIDAK TERVERIFIKASI`)*:
  1. **[BENAR] Nama Berkas Tujuan (`halaman_admin.php`)**: Sesuai `halaman_admin.php:140-162`.
  2. **[BENAR] Pengecekan Seluruh Riwayat di `Tabel peminjaman`**: Pesan `3–5` akurat menggambarkan `SELECT COUNT(*) AS c FROM peminjaman WHERE buku_id=$id` dan penolakan bila `$jml_pinjam > 0` untuk **semua status** riwayat (`dipinjam` maupun `dikembalikan`), selaras dengan `halaman_admin.php:144-149` dan FK `ON DELETE RESTRICT` (`database/aksa_nova.sql:228`).
  3. **[BENAR] Penghapusan Fisik Sampul Buku (`SELECT gambar` -> `@unlink`)**: Akurat mencerminkan `halaman_admin.php:152-157` (`if ($row["gambar"] && file_exists($row["gambar"])) @unlink(...)`).
  4. **[BENAR DENGAN CATATAN — `Rendah`] Penamaan Aktor (`Admin`)**: Aktor manusia sah, namun sebaiknya dinamai `Pustakawan (Admin)`.
  5. **[SALAH — `Sedang`] Guard Sesi Admin, `csrf_verify()`, & Guard `$id > 0` Absen**: PNG melewatkan pemeriksaan sesi admin (`halaman_admin.php:6-9`), `csrf_verify()` (`:73-77`, `csrf_field()` di `:1238`), serta `if ($id > 0)` (`:142`).
  6. **[BENAR DENGAN CATATAN — `Rendah`] `DELETE FROM buku`, Cascade FK, & Reload Daftar**: `11: DELETE buku` sesuai `halaman_admin.php:158`; sebagai kelengkapan, di DB penghapusan ini men-trigger `ON DELETE CASCADE` ke `buku_likes`, `buku_favorites`, `buku_ratings`, dan `pengajuan_peminjaman` (`02_fakta_kode.md:127-129, 132`), lalu halaman memanggil `SELECT * FROM buku` (`:179`) sebelum pesan `14:`.
  - **Saran Perbaikan**: Perbarui `.puml` agar sesuai alur nyata `halaman_admin.php`; ubah aktor menjadi `Pustakawan (Admin)`; tambahkan verifikasi sesi admin, `csrf_verify()`, guard `$id > 0`, dan kueri `SELECT * FROM buku` seusai penghapusan.

---

### 3. Diagram #22 — Menghapus Riwayat Peminjaman
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Sequence Diagram — Menghapus Riwayat Peminjaman` (Subjudul: `telah_dipinjam.php`)
  - Gambar: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_22_hapus_riwayat_peminjaman.png`
  - Sumber: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_22_hapus_riwayat_peminjaman.puml`
- **File PHP Aktual**: `telah_dipinjam.php` (`:77-81, 125-131, 180, 994-997, 1044-1058, 1161-1169`). *(Catatan: penghapusan riwayat pengajuan online ada terpisah di `pengajuan_buku.php:111-156`).*
- **Kecocokan PNG vs PUML**: **TIDAK COCOK** (`Tinggi`).
  - `.puml` (`1.325 B`) adalah templat generik 41 baris yang merujuk berkas fiktif `admin/hapus_riwayat.php`; sedangkan `.png` menampilkan `telah_dipinjam.php` dengan 4 partisipan dan 6 pesan.
- **Rincian Apa yang Tampak di PNG**:
  - **Partisipan (4)**: `Admin` (`actor`), `Halaman Telah Dipinjam` (`boundary`), `Proses Riwayat Peminjaman` (`control`), `Tabel peminjaman` (`entity`).
  - **Alur Pesan (1–6)**: `1:` Klik Hapus Riwayat pada data terpilih -> `2:` Kirim aksi `hapus_riwayat` & `pem_id` -> `opt [ID peminjaman valid (> 0)]` (`3:` DELETE peminjaman WHERE status = dikembalikan -> `4:` Riwayat terhapus -> `5:` Pesan riwayat berhasil dihapus -> `6:` Tampilkan daftar riwayat terbaru).
  - **Kualitas Visual PNG**: Sangat bersih, terbaca jelas, penomoran `1:` s/d `6:` rapi.
- **Daftar Pemeriksaan Elemen & Temuan** *(5 aspek: 1 `BENAR`, 3 `BENAR DENGAN CATATAN`, 1 `SALAH`, 0 `TIDAK TERVERIFIKASI`)*:
  1. **[BENAR] Nama Berkas Tujuan (`telah_dipinjam.php`)**: Sesuai `telah_dipinjam.php:125-131`.
  2. **[BENAR DENGAN CATATAN — `Rendah`] Penamaan Aktor (`Admin`)**: Aktor manusia sah, namun belum memakai nama lengkap `Pustakawan (Admin)`.
  3. **[SALAH — `Sedang`] Guard Sesi Admin & `csrf_verify()` Tidak Digambarkan**: `telah_dipinjam.php:8-11` memverifikasi sesi admin dan `telah_dipinjam.php:78-80` memanggil `csrf_verify()` (`csrf_field()` di `:1049`); keduanya tidak tampak di PNG.
  4. **[BENAR DENGAN CATATAN — `Rendah`] Modal Konfirmasi Hapus di UI Diringkas**: Di kode (`telah_dipinjam.php:994-997, 1044-1058, 1161-1166`), klik `"Hapus Riwayat"` membuka modal konfirmasi `#hapusRiwayatModalOverlay` terlebih dahulu dan baru mengirim POST saat tombol `"Ya, Hapus"` diklik; di PNG dipadatkan menjadi pesan `1:`.
  5. **[BENAR DENGAN CATATAN — `Rendah`] Kueri `DELETE`, Cascade `reminder_log`, & Render Ulang**: `opt [ID peminjaman valid (> 0)]` dan `DELETE FROM peminjaman WHERE id=$pem_id AND status='dikembalikan'` sesuai `telah_dipinjam.php:127-130`. Catatan: kueri ini men-trigger `ON DELETE CASCADE` ke `reminder_log` (`02_fakta_kode.md:134`), tidak memeriksa `status_denda`, dan halaman tetap menjalankan `SELECT` daftar peminjaman (`:180`) baik `$pem_id > 0` maupun tidak.
  - **Saran Perbaikan**: Tulis ulang `.puml` merujuk `telah_dipinjam.php`; ganti label aktor ke `Pustakawan (Admin)`; tambahkan langkah konfirmasi modal, pemeriksaan sesi & `csrf_verify()`, serta `SELECT` ulang daftar riwayat di luar `opt`.

---

### 4. Diagram #23 — Pengaturan Denda
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Sequence Diagram — Pengaturan Denda` (Subjudul: `pengaturan_denda.php`)
  - Gambar: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_23_pengaturan_denda.png`
  - Sumber: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_23_pengaturan_denda.puml`
- **File PHP Aktual**: `pengaturan_denda.php` (`:6-9, 40-56, 62-161, 850-890`).
- **Kecocokan PNG vs PUML**: **TIDAK COCOK** (`Tinggi`).
  - `.puml` (`1.316 B`) adalah templat generik 41 baris yang merujuk berkas fiktif `admin/pengaturan.php`; sedangkan `.png` menampilkan `pengaturan_denda.php` dengan 4 partisipan dan 14 pesan.
- **Rincian Apa yang Tampak di PNG**:
  - **Partisipan (4)**: `Admin` (`actor`), `Halaman Pengaturan Denda` (`boundary`), `Proses Pengaturan Denda` (`control`), `Tabel pengaturan` (`entity`).
  - **Alur Pesan (1–14)**: `1:` Membuka halaman Pengaturan Denda -> `2:` Ambil pengaturan denda -> `3:` SELECT `denda_per_hari`, `denda_aktif`, `denda_grace_period` -> `4:` Nilai pengaturan -> `5:` Pengaturan, statistik & daftar denda -> `6:` Tampilkan pengaturan saat ini -> `7:` Mengubah tarif, status aktif, masa toleransi (perubahan tersimpan otomatis) -> `8:` Kirim aksi `simpan_pengaturan` -> `alt [Tarif denda negatif]` (`9:` Pesan: tarif tidak boleh negatif -> `10:` Tampilkan pesan error) / `[Tarif valid]` (`11:` INSERT / UPDATE 3 pengaturan denda -> `12:` Pengaturan tersimpan -> `13:` Pesan berhasil disimpan -> `14:` Tampilkan pengaturan baru).
  - **Kualitas Visual PNG**: Sangat bersih, terbaca jelas, penomoran `1:` s/d `14:` urut.
- **Daftar Pemeriksaan Elemen & Temuan** *(6 aspek: 2 `BENAR`, 2 `BENAR DENGAN CATATAN`, 2 `SALAH`, 0 `TIDAK TERVERIFIKASI`)*:
  1. **[BENAR] Nama Berkas Tujuan (`pengaturan_denda.php`)**: Sesuai `pengaturan_denda.php:1-162`.
  2. **[BENAR] Validasi Tarif Denda Negatif (`$denda_per_hari < 0`)**: Akurat mencerminkan `pengaturan_denda.php:80-88`.
  3. **[BENAR DENGAN CATATAN — `Rendah`] Penamaan Aktor (`Admin`)**: Aktor manusia sah, sebaiknya diseragamkan menjadi `Pustakawan (Admin)`.
  4. **[SALAH — `Sedang`] Entitas `Tabel peminjaman` (& `Tabel buku`) Hilang padahal Disebut di Pesan `5:` + Aksi `tandai_lunas` Tidak Ada**: Pesan `5:` mengklaim mengembalikan `"Pengaturan, statistik & daftar denda"`, namun di diagram tidak ada `Tabel peminjaman` maupun `Tabel buku` tempat 5 kueri statistik & daftar denda dijalankan (`pengaturan_denda.php:134-158`). Selain itu fitur pelunasan denda `action === 'tandai_lunas'` (`UPDATE peminjaman SET status_denda='lunas'`, `:115-124`) di halaman yang sama tidak tergambar.
  5. **[SALAH — `Sedang`] Guard Sesi Admin & `csrf_verify()` Tidak Digambarkan**: `pengaturan_denda.php:6-9` memeriksa sesi admin dan `pengaturan_denda.php:66-74` memverifikasi `csrf_verify()` (mengembalikan JSON error bila `is_ajax=1`); keduanya tidak tampak di PNG.
  6. **[BENAR DENGAN CATATAN — `Rendah`] Detail Auto-Save AJAX (`is_ajax=1`) & Clamping `grace_period`**: Di kode, auto-save mengirim `POST` via `fetch()` dengan `is_ajax=1` (`:855`) yang dibalas JSON (`:93-110`) tanpa reload halaman penuh, `getSetting()`/`:42` serta `setSetting()` (`INSERT ... ON DUPLICATE KEY UPDATE`, `:53-54`) dipanggil 3 kali untuk ketiga kunci, dan nilai `grace_period` negatif di-clamp otomatis dengan `max(0, (int)...)` (`:78`).
  - **Saran Perbaikan**: Tulis ulang `.puml` sesuai `pengaturan_denda.php`; ubah aktor menjadi `Pustakawan (Admin)`; tambahkan entitas `Tabel peminjaman` (beserta kueri statistik/daftar denda dan opsional cabang `tandai_lunas`), tambahkan `csrf_verify()`, serta perjelas respons JSON pada mode auto-save `is_ajax=1`.

---

### 5. Diagram #24 — Pengingat Keterlambatan via WhatsApp
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Sequence Diagram — Pengingat Keterlambatan via WhatsApp` (Subjudul: `cron_cek_terlambat.php · telah_dipinjam.php`)
  - Gambar: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_24_pengingat_keterlambatan_wa.png`
  - Sumber: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_24_pengingat_keterlambatan_wa.puml`
- **File PHP Aktual**: `cron_cek_terlambat.php` (`:22-60`) dan `telah_dipinjam.php` (`:8-11, 77-81, 132-143, 180-188, 265-286, 960-980, 1192-1200`).
- **Kecocokan PNG vs PUML**: **TIDAK COCOK** (`Tinggi`).
  - `.puml` (`1.323 B`) adalah templat generik 41 baris yang merujuk berkas fiktif `admin/kirim_wa.php` dengan 4 partisipan; sedangkan `.png` menampilkan `cron_cek_terlambat.php · telah_dipinjam.php` dengan 7 partisipan dan 19 pesan.
- **Rincian Apa yang Tampak di PNG**:
  - **Partisipan (7)**: `Penjadwal (Cron)` (**stick figure `actor`**), `Proses Cek Terlambat` (`control`), `Tabel peminjaman` (`entity`), `Tabel reminder_log` (`entity`), `Halaman Telah Dipinjam` (`boundary`), `Admin` (`actor` stick figure), `Aplikasi WhatsApp` (`participant` kotak).
  - **Alur Pesan (1–19)**:
    - Blok Cron (`1–7`): `1:` Jalankan `cron_cek_terlambat.php` (CLI, tiap hari) -> `alt [Bukan CLI]` (`2:` 403 Forbidden) / `[CLI]` -> `3:` SELECT peminjaman dipinjam & `batas_kembali < NOW()` -> `4:` Daftar peminjaman terlambat -> `loop [tiap peminjaman terlambat]` (`5:` Hitung hari terlambat -> `6:` INSERT `reminder_log` (`ON DUPLICATE KEY UPDATE terlambat_hari`) -> `7:` Log tercatat).
    - Blok Admin WA (`8–19`): `8:` Membuka halaman Telah Dipinjam -> `Halaman Telah Dipinjam` langsung ke DB `9:` SELECT peminjaman + LEFT JOIN `reminder_log` hari ini -> `10:` Daftar terlambat & status WA -> `11:` Tampilkan peminjaman terlambat -> `12:` Klik Kirim Pengingat WA -> `13:` Buka tautan `wa.me` ke no. HP anggota + pesan pengingat -> `14:` Pesan siap dikirim di WhatsApp -> `15:` `tandai_wa`: INSERT / UPDATE `wa_terkirim = 1` -> `16:` Tanda tersimpan -> **`opt [Salah tandai]` (`17:` Klik batalkan tanda -> `18:` `batal_wa`: UPDATE `wa_terkirim = 0` -> `19:` Tanda dibatalkan)**.
  - **Kualitas Visual PNG**: Ukuran huruf agak kecil karena lebar 7 kolom partisipan, namun seluruh 19 pesan terbaca jelas tanpa tumpang tindih.
- **Daftar Pemeriksaan Elemen & Temuan** *(6 aspek: 1 `BENAR`, 0 `BENAR DENGAN CATATAN`, 5 `SALAH`, 0 `TIDAK TERVERIFIKASI`)*:
  1. **[BENAR] Logika Skrip CLI `cron_cek_terlambat.php` (Pesan 1–7) & `Aplikasi WhatsApp` Bukan `actor`**: Guard `PHP_SAPI !== 'cli'` -> `403` (`cron_cek_terlambat.php:22-25`), kueri pinjaman aktif `batas_kembali < NOW()` (`:38-40`), hitung hari (`:46-48`), dan upsert `reminder_log` (`:51-54`) tanpa kirim WA otomatis sudah akurat; `Aplikasi WhatsApp` juga sudah benar digambar sebagai kotak `participant`, bukan stick figure.
  2. **[SALAH — `Tinggi`] `Penjadwal (Cron)` Digambar sebagai Stick Figure `actor` (+ Label `Admin` Kurang Lengkap)**: Melanggar aturan 3 aktor manusia sah (`Tamu (Guest)`, `Anggota (Siswa & Guru)`, `Pustakawan (Admin)`). Cron adalah proses penjadwal OS/server yang tidak boleh memakai simbol orang-orangan (`actor`), dan `Admin` perlu ditulis `Pustakawan (Admin)`.
  3. **[SALAH — `Kritis`] Fitur Fiktif / Halusinasi `opt [Salah tandai]` (`batal_wa: UPDATE wa_terkirim = 0`, Pesan 17–19)**: Di seluruh kode sumber proyek (`telah_dipinjam.php:75-155` dan `02_fakta_kode.md:134`), **sama sekali TIDAK ADA** aksi `batal_wa`, tidak ada tombol batalkan tanda WA di UI, dan tidak ada kueri `UPDATE reminder_log SET wa_terkirim = 0`. Tiga pesan terakhir (`17–19`) murni karangan!
  4. **[SALAH — `Sedang`] `Halaman Telah Dipinjam` (`boundary`) Mengakses Tabel Database Secara Langsung Tanpa `control`**: Pada pesan `9` dan `15`, `Halaman Telah Dipinjam` mengeksekusi `SELECT` dan `INSERT/UPDATE` langsung ke `Tabel peminjaman` serta `Tabel reminder_log` tanpa melalui lifeline `Proses Telah Dipinjam` (`control`), padahal di kode `tandaiWa()` mengirim HTTP `POST` `fetch('telah_dipinjam.php')` (`telah_dipinjam.php:1197`).
  5. **[SALAH — `Sedang`] Guard Sesi Admin, `csrf_verify()`, & `setTimeout(400ms)` + `window.location.reload()` Tidak Digambarkan**: `telah_dipinjam.php` memverifikasi sesi admin (`:8-11`) dan `csrf_verify()` (`:78`, token dikirim JS `tandaiWa` di `:1196`), lalu memicu `setTimeout(..., 400)` saat klik tautan `wa.me` (`:973`) dan `window.location.reload()` seusai `fetch` (`:1198`).
  6. **[SALAH — `Sedang`] Kueri Pesan `9` Melewatkan `JOIN users` (Sumber `no_hp`) & `JOIN buku`**: `telah_dipinjam.php:180-186` melakukan `INNER JOIN buku b` dan `LEFT JOIN users u` untuk mengambil `u.no_hp AS anggota_no_hp`. Tombol `Kirim Pengingat WA` hanya dirender jika pinjaman berstatus `'dipinjam'`, lewat `batas_kembali`, **dan** `no_hp` anggota tersedia (`:960-979`; jika kosong tampil `"No HP anggota belum diisi"`).
  - **Saran Perbaikan**: Hapus seluruh blok fiktif `opt [Salah tandai]` (`17–19`); ubah `Penjadwal (Cron)` dari `actor` menjadi `participant` `<<system>>` dan `Admin` menjadi `Pustakawan (Admin)`; tambahkan `control` `Proses Telah Dipinjam` (dengan cek sesi & `csrf_verify()`), sertakan `Tabel users` & `Tabel buku` (atau satukan entitas yang relevan) beserta syarat ketersediaan `no_hp`, dan tulis ulang `.puml` agar cocok dengan `.png`.

---

### 6. Diagram #25 — Menambah Banner Beranda
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Sequence Diagram — Menambah Banner Beranda` (Subjudul: `kelola_banner.php`)
  - Gambar: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_25_tambah_banner.png`
  - Sumber: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_25_tambah_banner.puml`
- **File PHP Aktual**: `kelola_banner.php` (`:5-8, 37-49, 54-81, 177, 680-780`).
- **Kecocokan PNG vs PUML**: **TIDAK COCOK** (`Tinggi`).
  - `.puml` (`1.292 B`) adalah templat generik 41 baris yang merujuk berkas fiktif `admin/banner.php`; sedangkan `.png` menampilkan `kelola_banner.php` dengan 5 partisipan dan 12 pesan.
- **Rincian Apa yang Tampak di PNG**:
  - **Partisipan (5)**: `Admin` (`actor`), `Halaman Kelola Banner` (`boundary`), `Proses Kelola Banner` (`control`), `Penyimpanan Gambar` (`participant` kotak), `Tabel banner` (`entity`).
  - **Alur Pesan (1–12)**: `1:` Mengisi judul, subjudul, tautan, pilih gambar lalu Simpan -> `2:` Kirim aksi `tambah` + file gambar -> `3:` Simpan gambar (`JPG/PNG/WEBP/GIF ≤ 5 MB, video MP4/WEBM ≤ 20 MB`) -> `4:` Path gambar / gagal -> `alt [Gambar tidak valid]` (`5:` Pesan: file banner wajib diunggah -> `6:` Tampilkan pesan error) / `[Gambar valid]` (`7:` SELECT `MAX(urutan)` -> `8:` Urutan terakhir -> `9:` INSERT banner (`urutan + 1`, aktif) -> `10:` Banner tersimpan -> `11:` Pesan banner berhasil ditambahkan -> `12:` Tampilkan banner baru).
  - **Kualitas Visual PNG**: Jernih, simbol `≤` tampil benar, penomoran `1:` s/d `12:` urut tanpa cacat visual.
- **Daftar Pemeriksaan Elemen & Temuan** *(6 aspek: 1 `BENAR`, 3 `BENAR DENGAN CATATAN`, 2 `SALAH`, 0 `TIDAK TERVERIFIKASI`)*:
  1. **[BENAR] Nama Berkas Tujuan (`kelola_banner.php`)**: Sesuai `kelola_banner.php:62-81`.
  2. **[BENAR DENGAN CATATAN — `Rendah`] Penamaan Aktor (`Admin`)**: Aktor manusia sah, sebaiknya ditulis `Pustakawan (Admin)`.
  3. **[SALAH — `Sedang`] Guard Sesi Admin & `csrf_verify()` Tidak Digambarkan**: `kelola_banner.php:5-8` memeriksa sesi admin dan `kelola_banner.php:55-59` memanggil `csrf_verify()` (`csrf_field()` di `:684`); keduanya absen di PNG.
  4. **[SALAH — `Rendah`] Urutan `SELECT COALESCE(MAX(urutan),0)` Berada di Dalam `[Gambar valid]` (Berbeda dari Kode)**: Di `kelola_banner.php:69-72`, kueri `SELECT COALESCE(MAX(urutan),0) AS m FROM banner` dijalankan **sebelum** pengecekan `if ($gambar === "")` (`:72`), bukan di dalam cabang `else` setelah gambar valid seperti digambar pada pesan `7:`.
  5. **[BENAR DENGAN CATATAN — `Rendah`] Spesifikasi Media Banner, Crop Klien `16:7`, & Tanpa Cek MIME Server**: Pesan `3:` tepat mencantumkan `JPG/PNG/WEBP/GIF ≤ 5 MB, video MP4/WEBM ≤ 20 MB` (`kelola_banner.php:40-43`). Catatan: `uploadGambarBanner()` hanya mengecek ekstensi & ukuran byte **tanpa cek MIME** di server (`:37-49`), nama partisipan `Penyimpanan Gambar` sebenarnya menyimpan gambar maupun video (`uploads/banner/`), dan untuk gambar non-GIF tersedia modal crop `16:7` di sisi klien (`:693, 720`).
  6. **[BENAR DENGAN CATATAN — `Rendah`] `INSERT INTO banner` & Reload Daftar**: `9: INSERT banner (urutan + 1, aktif)` sesuai `kelola_banner.php:75-78` (`aktif = 1`), diikuti `SELECT * FROM banner ORDER BY urutan ASC, id ASC` (`:177`) sebelum `12:`.
  - **Saran Perbaikan**: Tulis ulang `.puml` merujuk `kelola_banner.php`; ubah aktor ke `Pustakawan (Admin)` dan `Penyimpanan Gambar` ke `Penyimpanan Media (uploads/banner/)`; tambahkan sesi & `csrf_verify()`; sesuaikan posisi `SELECT COALESCE(MAX(urutan),0)` dengan kode; serta cantumkan catatan opsional crop `16:7` di klien.

---

### 7. Diagram #26 — Mengubah Banner Beranda
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Sequence Diagram — Mengubah Banner Beranda` (Subjudul: `kelola_banner.php`)
  - Gambar: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_26_ubah_banner.png`
  - Sumber: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_26_ubah_banner.puml`
- **File PHP Aktual**: `kelola_banner.php` (`:5-8, 37-49, 54-59, 84-107, 177`).
- **Kecocokan PNG vs PUML**: **TIDAK COCOK** (`Tinggi`).
  - `.puml` (`1.288 B`) adalah templat generik 41 baris yang merujuk `admin/banner.php` (fiktif); sedangkan `.png` menampilkan `kelola_banner.php` dengan 5 partisipan dan 12 pesan.
- **Rincian Apa yang Tampak di PNG**:
  - **Partisipan (5)**: `Admin` (`actor`), `Halaman Kelola Banner` (`boundary`), `Proses Kelola Banner` (`control`), `Penyimpanan Gambar` (`participant` kotak), `Tabel banner` (`entity`).
  - **Alur Pesan (1–12)**: `1:` Klik Edit, ubah data / gambar lalu Simpan -> `2:` Kirim aksi `update` + gambar baru (opsional) -> `opt [Ada gambar baru]` (`3:` Simpan gambar baru (`JPG/PNG/WEBP/GIF ≤ 5 MB, video MP4/WEBM ≤ 20 MB`) -> `4:` Path gambar baru) -> `alt [ID banner tidak valid]` (`5:` Pesan: data tidak valid -> `6:` Tampilkan pesan error) / `[ID valid]` -> `opt [Gambar diganti]` (`7:` Hapus gambar lama -> `8:` Gambar lama terhapus) -> `9:` UPDATE judul, subjudul, tautan, gambar -> `10:` Banner diperbarui -> `11:` Pesan banner berhasil diperbarui -> `12:` Tampilkan banner terbaru.
  - **Kualitas Visual PNG**: Sangat bersih, simbol `≤` utuh, penomoran `1:` s/d `12:` urut dan terbaca jelas.
- **Daftar Pemeriksaan Elemen & Temuan** *(5 aspek: 2 `BENAR`, 2 `BENAR DENGAN CATATAN`, 1 `SALAH`, 0 `TIDAK TERVERIFIKASI`)*:
  1. **[BENAR] Nama Berkas Tujuan (`kelola_banner.php`)**: Sesuai `kelola_banner.php:84-107`.
  2. **[BENAR] Urutan Eksekusi Upload Baru -> Cek `$id === 0` -> `@unlink($gambar_lama)` -> `UPDATE banner`**: Sangat akurat mencerminkan urutan baris demi baris pada `kelola_banner.php:91-104` (termasuk fakta bahwa `uploadGambarBanner()` dipanggil di `:91` sebelum cek `$id === 0` di `:94`, dan `@unlink($gambar_lama)` dijalankan di `:98-100` hanya bila `$id` valid dan `$gambar_baru !== ""`).
  3. **[BENAR DENGAN CATATAN — `Rendah`] Penamaan Aktor (`Admin`)**: Aktor manusia sah, sebaiknya ditulis `Pustakawan (Admin)`.
  4. **[SALAH — `Sedang`] Guard Sesi Admin & `csrf_verify()` Tidak Digambarkan**: `kelola_banner.php:5-8` dan `csrf_verify()` di `:55-59` tidak tampak di PNG.
  5. **[BENAR DENGAN CATATAN — `Rendah`] Fallback Diam-Diam Saat File Baru Tidak Valid & Tanpa Cek MIME**: Jika pengguna mengunggah file baru yang melebihi batas ukuran atau ekstensinya salah, `uploadGambarBanner()` (`:37-49`, tanpa cek MIME) mengembalikan `""` tanpa pesan error dan kode diam-diam mempertahankan `$gambar_lama` dari `$_POST["gambar_lama"]` (`:89, 92`) lalu tetap melakukan `UPDATE` serta `SELECT * FROM banner` (`:177`).
  - **Saran Perbaikan**: Tulis ulang `.puml` sesuai `kelola_banner.php`; ubah label aktor menjadi `Pustakawan (Admin)` dan `Penyimpanan Gambar` menjadi `Penyimpanan Media (uploads/banner/)`; tambahkan pemeriksaan sesi admin & `csrf_verify()`, serta kueri `SELECT * FROM banner` saat me-render daftar terbaru.

---

### 8. Diagram #27 — Mengatur Banner (Aktif, Urutan, Latar, Hapus)
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Sequence Diagram — Mengatur Banner (Aktif, Urutan, Latar, Hapus)` (Subjudul: `kelola_banner.php`)
  - Gambar: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_27_atur_banner.png`
  - Sumber: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_27_atur_banner.puml`
- **File PHP Aktual**: `kelola_banner.php` (`:5-8, 54-59, 110-180, 629-672`).
- **Kecocokan PNG vs PUML**: **TIDAK COCOK** (`Tinggi`).
  - `.puml` (`1.293 B`) adalah templat generik 41 baris yang merujuk `admin/banner.php` (fiktif) dan judulnya hanya menyebut *Urutan & Status Aktif*; sedangkan `.png` menampilkan `kelola_banner.php` dengan 6 partisipan dan 16 pesan mencakup 4 aksi (`hapus`, `toggle`, `geser`, `set_background`).
- **Rincian Apa yang Tampak di PNG**:
  - **Partisipan (6)**: `Admin` (`actor`), `Halaman Kelola Banner` (`boundary`), `Proses Kelola Banner` (`control`), `Tabel banner` (`entity`), `Tabel pengaturan` (`entity`), `Penyimpanan Gambar` (`participant` kotak).
  - **Alur Pesan (1–16)**: `1:` Memilih aksi pada banner -> `2:` Kirim aksi (`toggle / geser / set_background / hapus`) & `id` -> `alt [Aksi hapus]` (`3:` SELECT gambar banner -> `4:` Path gambar -> `5:` Hapus file gambar -> `6:` File terhapus -> `7:` DELETE banner -> `8:` Banner terhapus) / `[Aksi toggle aktif]` (`9:` UPDATE `aktif = 1 - aktif` -> `10:` Status diperbarui) / `[Aksi geser urutan]` (`11:` SELECT banner tetangga & UPDATE urutan keduanya -> `12:` Urutan ditukar) / `[Aksi jadikan latar beranda]` (`13:` INSERT / UPDATE `banner_background_id` -> `14:` Pengaturan tersimpan) -> `15:` Pesan hasil aksi -> `16:` Tampilkan daftar banner terbaru.
  - **Kualitas Visual PNG**: Terbaca baik; catatan visual minor: pada cabang `[Aksi geser urutan]`, teks label `11: SELECT banner tetangga & UPDATE urutan keduanya` memotong batang aktivasi biru `Proses Kelola Banner` sehingga batang tampak terbelah.
- **Daftar Pemeriksaan Elemen & Temuan** *(5 aspek: 1 `BENAR`, 2 `BENAR DENGAN CATATAN`, 2 `SALAH`, 0 `TIDAK TERVERIFIKASI`)*:
  1. **[BENAR] Nama Berkas Tujuan (`kelola_banner.php`) & Cakupan 4 Aksi**: Sesuai keempat handler `hapus`, `toggle`, `geser`, dan `set_background` di `kelola_banner.php:110-165`.
  2. **[BENAR DENGAN CATATAN — `Rendah`] Penamaan Aktor (`Admin`)**: Aktor manusia sah, sebaiknya diseragamkan menjadi `Pustakawan (Admin)`.
  3. **[SALAH — `Sedang`] Guard Sesi Admin & `csrf_verify()` Tidak Digambarkan**: Keempat form POST di `kelola_banner.php:630, 641, 653, 665` mengirim `csrf_field()` dan diverifikasi oleh `csrf_verify()` di `:55-59` (serta guard sesi `:5-8`), namun absen di PNG.
  4. **[SALAH — `Rendah`] Pesan `15: Pesan hasil aksi` Tidak Berlaku untuk Aksi `geser` (+ Parameter `arah` Hilang)**: Di PNG, pesan `15: Pesan hasil aksi` berada di luar `alt` sehingga seolah-olah semua aksi menghasilkan pesan notifikasi. Faktanya, `action === "geser"` (`kelola_banner.php:134-149`) **sama sekali tidak menyetel variabel `$msg`** (berbeda dengan `hapus` `:120`, `toggle` `:129`, dan `set_background` `:163`), serta membutuhkan input `$_POST["arah"]` (`naik`/`turun`, `:136`).
  5. **[BENAR DENGAN CATATAN — `Rendah`] Penyederhanaan Detail `hapus`, `geser`, & `set_background`**: Pada `hapus` (`:110-122`), UI menampilkan konfirmasi `konfirmasiHapusBanner()` (`:664`) terlebih dahulu dan `@unlink` bersifat kondisional jika file ada (`:115-116`); pada `geser` (`:137-147`), server menjalankan 2 `SELECT` + 2 `UPDATE` kondisional bila tetangga ada; pada `set_background` (`:157-162`), server mengecek `SELECT kunci` pada `pengaturan` sebelum `UPDATE`/`INSERT`; lalu halaman memuat ulang `pengaturan` (`:171`) & `banner` (`:177`).
  - **Saran Perbaikan**: Tulis ulang `.puml` sesuai `kelola_banner.php`; ubah aktor ke `Pustakawan (Admin)`; tambahkan cek sesi & `csrf_verify()`, sertakan parameter `arah` pada `geser`, bungkus penghapusan file pada `hapus` dalam `opt [File ada]`, pindahkan pesan notifikasi ke cabang yang benar-benar menyetel `$msg`, dan rapikan label pesan `11:` agar tidak memotong batang aktivasi.

---

### 9. Diagram #28 — Pengaturan Musik Latar
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Sequence Diagram — Pengaturan Musik Latar` (Subjudul: `pengaturan_musik.php`)
  - Gambar: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_28_pengaturan_musik.png`
  - Sumber: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_28_pengaturan_musik.puml`
- **File PHP Aktual**: `pengaturan_musik.php` (`:6-9, 33-57, 62-247, 878-1028`).
- **Kecocokan PNG vs PUML**: **TIDAK COCOK** (`Tinggi`).
  - `.puml` (`1.295 B`) adalah templat generik 41 baris yang merujuk berkas fiktif `admin/musik.php`, menyebut *Volume Default* di judul, dan **secara keliru mengklaim `csrf_verify()`**; sedangkan `.png` menampilkan `pengaturan_musik.php` dengan 5 partisipan dan 13 pesan tanpa `csrf_verify()`.
- **Rincian Apa yang Tampak di PNG**:
  - **Partisipan (5)**: `Admin` (`actor`), `Halaman Pengaturan Musik` (`boundary`), `Proses Pengaturan Musik` (`control`), `Penyimpanan Musik` (`participant` kotak), `Tabel pengaturan` (`entity`).
  - **Alur Pesan (1–13)**: `1:` Mengisi judul, aktifkan musik, unggah file (perubahan tersimpan otomatis) -> `2:` Kirim aksi `simpan` + file musik -> `opt [Ada file baru]` (`3:` Cek ukuran `≤ 15 MB`, ekstensi & tipe MIME -> `alt [Berkas tidak valid]` (`4:` Pesan kesalahan unggah -> `5:` Tampilkan pesan error) / `[Berkas valid]` (`6:` Hapus file lama & simpan file baru -> `7:` Path file baru)) -> `alt [Musik aktif tetapi belum ada file]` (`8:` Pesan: aktifkan musik memerlukan file -> `9:` Tampilkan pesan error) / `[Lengkap]` (`10:` INSERT / UPDATE `musik_file`, `musik_judul`, `musik_aktif` -> `11:` Pengaturan tersimpan -> `12:` Pesan pengaturan berhasil disimpan -> `13:` Tampilkan pengaturan terbaru).
  - **Kualitas Visual PNG**: Jernih, simbol `≤` utuh, penomoran `1:` s/d `13:` rapi.
- **Daftar Pemeriksaan Elemen & Temuan** *(5 aspek: 2 `BENAR`, 2 `BENAR DENGAN CATATAN`, 1 `SALAH`, 0 `TIDAK TERVERIFIKASI`)*:
  1. **[BENAR] Nama Berkas Tujuan (`pengaturan_musik.php`)**: Sesuai `pengaturan_musik.php:1-1079`.
  2. **[BENAR] Validasi Audio (`≤ 15 MB`, `mp3/ogg/wav/m4a`, & MIME) + `@unlink` File Lama**: Akurat mencerminkan `pengaturan_musik.php:138-159` (dan `:207-229`).
  3. **[BENAR DENGAN CATATAN — `Rendah`] Penamaan Aktor (`Admin`)**: Aktor manusia sah, sebaiknya ditulis `Pustakawan (Admin)`.
  4. **[BENAR DENGAN CATATAN — `Rendah`] Ketiadaan `csrf_verify()` Sesuai Kode, namun Guard Sesi Admin & Kueri Baca Awal Hilang**: `pengaturan_musik.php` memang **tidak memanggil `csrf_verify()`** (`02_fakta_kode.md:44, 58`), sehingga ketiadaan CSRF di PNG sesuai fakta kode. Namun PNG melewatkan pemeriksaan sesi admin (`:6-9`) dan kueri awal `SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('musik_aktif','musik_file','musik_judul')` (`:50`) untuk mengetahui `$musik_file` lama.
  5. **[SALAH — `Tinggi`] Mencampuradukkan Auto-Save UI (3 Aksi AJAX Terpisah) dengan Cabang Kode Mati `action=simpan` + Struktur `alt` Berlanjut Saat Gagal Unggah**:
     - Pesan `1:` menulis `(perubahan tersimpan otomatis)`, tetapi pesan `2:` mengirim `aksi simpan + file musik` dan pesan `10:` meng-update ketiga kunci sekaligus. Faktanya, antarmuka `pengaturan_musik.php` **tidak memiliki `<form>` maupun tombol Simpan**, dan tidak pernah mengirim `action=simpan`! Sebaliknya, UI mengirim **3 request `fetch()` AJAX (`is_ajax=1`) independen** yang merespons JSON: `action=toggle_musik` (`:85-112`, JS `:890`), `action=simpan_judul` (`:113-128`, JS debounce `600ms` `:928`), dan `action=upload_file` (`:128-195`, JS `:983`, yang hanya menyimpan `musik_file` & `musik_judul` tanpa menyentuh `musik_aktif`). Cabang `else` (`simpan`, `:196-246`) tidak pernah dipanggil oleh UI.
     - Secara struktural di PNG, bila masuk cabang `[Berkas tidak valid]` (`4–5`) di dalam `opt [Ada file baru]`, alur masih jatuh ke blok `alt [Musik aktif tetapi belum ada file] / [Lengkap]` di bawahnya, padahal di kode (`:164-171` maupun `:233-235`) saat `$upload_error !== ""` eksekusi langsung dihentikan tanpa menyimpan ke DB.
  - **Saran Perbaikan**: Tulis ulang `.puml` (tanpa klaim palsu `csrf_verify()` selama kode PHP belum ditambah CSRF, dan merujuk `pengaturan_musik.php`); ubah aktor ke `Pustakawan (Admin)`; modelkan 3 interaksi AJAX aktual (`upload_file`, `simpan_judul`, `toggle_musik`) dengan respons JSON toast menggunakan `alt` per aksi, serta pastikan cabang gagal unggah tidak berlanjut ke `INSERT/UPDATE`.

---

### 10. Diagram #29 — Menghapus File Musik Latar
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Sequence Diagram — Menghapus File Musik Latar` (Subjudul: `pengaturan_musik.php`)
  - Gambar: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_29_hapus_musik.png`
  - Sumber: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_29_hapus_musik.puml`
- **File PHP Aktual**: `pengaturan_musik.php` (`:6-9, 33-57, 67-85, 1031-1072`).
- **Kecocokan PNG vs PUML**: **TIDAK COCOK** (`Tinggi`).
  - `.puml` (`1.290 B`) adalah templat generik 41 baris yang merujuk `admin/musik.php` (fiktif) dan **secara salah mengklaim `csrf_verify()`**; sedangkan `.png` menampilkan `pengaturan_musik.php` dengan 5 partisipan dan 8 pesan tanpa `csrf_verify()`.
- **Rincian Apa yang Tampak di PNG**:
  - **Partisipan (5)**: `Admin` (`actor`), `Halaman Pengaturan Musik` (`boundary`), `Proses Pengaturan Musik` (`control`), `Penyimpanan Musik` (`participant` kotak), `Tabel pengaturan` (`entity`).
  - **Alur Pesan (1–8)**: `1:` Klik Hapus File Musik -> `2:` Kirim aksi `hapus_file` -> `3:` Hapus file musik -> `4:` File terhapus -> `5:` UPDATE `musik_file = kosong` & `musik_aktif = 0` -> `6:` Pengaturan diperbarui -> `7:` Pesan musik dihapus & fitur nonaktif -> `8:` Tampilkan pengaturan (nonaktif).
  - **Kualitas Visual PNG**: Sangat bersih, terbaca jelas, penomoran `1:` s/d `8:` urut.
- **Daftar Pemeriksaan Elemen & Temuan** *(5 aspek: 2 `BENAR`, 3 `BENAR DENGAN CATATAN`, 0 `SALAH`, 0 `TIDAK TERVERIFIKASI`)*:
  1. **[BENAR] Nama Berkas Tujuan (`pengaturan_musik.php`)**: Sesuai `pengaturan_musik.php:67-85`.
  2. **[BENAR] Efek Penghapusan (`@unlink` + `musik_file=""` + `musik_aktif="0"`)**: Tepat mencerminkan `pengaturan_musik.php:68-74` (`musik_judul` memang tidak dihapus).
  3. **[BENAR DENGAN CATATAN — `Rendah`] Penamaan Aktor (`Admin`)**: Aktor manusia sah, sebaiknya ditulis `Pustakawan (Admin)`.
  4. **[BENAR DENGAN CATATAN — `Rendah`] Ketiadaan `csrf_verify()` Sesuai Kode, namun Guard Sesi Admin & Kueri Baca Path Awal Tidak Tampak**: Ketiadaan `csrf_verify()` sesuai fakta `pengaturan_musik.php:62-85`, tetapi pemeriksaan sesi admin (`:6-9`) dan pembacaan awal `SELECT kunci, nilai FROM pengaturan` (`:50`) untuk memperoleh path `$musik_file` sebelum `if ($musik_file && file_exists($musik_file)) @unlink(...)` (`:68-70`) belum digambarkan.
  5. **[BENAR DENGAN CATATAN — `Rendah`] Konfirmasi `AksaConfirm` & Eksekusi AJAX JSON (`is_ajax=1`)**: Di UI (`pengaturan_musik.php:1032-1065`), klik tombol memunculkan dialog `AksaConfirm` terlebih dahulu, lalu mengirim `fetch('pengaturan_musik.php', POST action=hapus_file & is_ajax=1)` yang mengembalikan JSON (`:75-82`) dan memperbarui tampilan secara dinamis tanpa reload penuh; selain itu `simpanPengaturan()` (`:36-41`) mengecek `SELECT kunci` sebelum `UPDATE`/`INSERT`.
  - **Saran Perbaikan**: Tulis ulang `.puml` merujuk `pengaturan_musik.php` (tanpa klaim palsu `csrf_verify()`); ubah aktor ke `Pustakawan (Admin)`; tambahkan konfirmasi `AksaConfirm`, cek sesi admin, pembacaan awal `musik_file`, `opt [File musik ada]` untuk `@unlink`, serta respons JSON `is_ajax=1`.

---

### 11. Diagram #30 — Dashboard Admin & Ekspor Laporan Excel
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Sequence Diagram — Dashboard Admin & Ekspor Laporan Excel` (Subjudul: `dashboard.php · export_excel_dashboard.php`)
  - Gambar: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_30_dashboard_admin_ekspor.png`
  - Sumber: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_30_dashboard_admin_ekspor.puml`
- **File PHP Aktual**: `dashboard.php` (`:5-261, 970-980`) dan `export_excel_dashboard.php` (`:3-318`).
- **Kecocokan PNG vs PUML**: **TIDAK COCOK** (`Tinggi`).
  - `.puml` (`1.342 B`) adalah templat generik 41 baris yang merujuk berkas fiktif `admin/dashboard.php & admin/ekspor_excel.php` dan mengklaim `csrf_verify()`; sedangkan `.png` menampilkan `dashboard.php · export_excel_dashboard.php` dengan 7 partisipan dan 22 pesan.
- **Rincian Apa yang Tampak di PNG**:
  - **Partisipan (7)**: `Admin` (`actor`), `Halaman Dashboard` (`boundary`), `Proses Dashboard` (`control`), `Proses Ekspor Excel` (`control`), `Tabel buku` (`entity`), `Tabel users` (`entity`), `Tabel peminjaman` (`entity`).
  - **Alur Pesan (1–22)**:
    - Muat Dashboard (`1–12`): `1:` Membuka Dashboard -> `2:` Periksa sesi & role admin -> `alt [Belum login / bukan admin]` (`3:` Redirect ke Sign In -> `4:` Tampilkan halaman Sign In) / `[Admin login]` (`5:` COUNT buku & SUM stok -> `6:` Total buku & stok -> `7:` COUNT anggota per status -> `8:` Jumlah anggota -> `9:` COUNT aktif, terlambat, denda, buku terpopuler, rekap bulanan -> `10:` Data statistik -> `11:` Seluruh statistik -> `12:` Tampilkan kartu statistik & grafik).
    - Ekspor Excel (`13–22`): `13:` Klik Ekspor Excel -> `14:` Minta laporan -> `alt [Bukan admin]` (`15:` 403 Akses ditolak) / `[Admin]` (`16:` Ambil data buku -> `17:` Data buku -> `18:` Ambil data anggota -> `19:` Data anggota -> `20:` Ambil data peminjaman tahun berjalan -> `21:` Data peminjaman -> `22:` Unduh `Laporan_AKSANOVA_{tahun}.xls`).
  - **Kualitas Visual PNG**: Tajam dan rapi; teks pesan `20:` sedikit melintasi garis putus-putus lifeline `Tabel buku` dan `Tabel users` namun tetap terbaca baik.
- **Daftar Pemeriksaan Elemen & Temuan** *(5 aspek: 2 `BENAR`, 2 `BENAR DENGAN CATATAN`, 1 `SALAH`, 0 `TIDAK TERVERIFIKASI`)*:
  1. **[BENAR] Nama Berkas Tujuan (`dashboard.php · export_excel_dashboard.php`)**: Sesuai kedua berkas PHP aktual.
  2. **[BENAR] Perbedaan Guard Akses (`Redirect sign_in.php` di Dashboard vs `HTTP 403` di Ekspor) & Tanpa CSRF**: Sangat akurat! `dashboard.php:5-8` me-redirect ke `sign_in.php`, sedangkan `export_excel_dashboard.php:4-7` mengembalikan `403 Akses ditolak.` (dan sebagai endpoint `GET`, keduanya memang tidak memakai `csrf_verify()`).
  3. **[BENAR DENGAN CATATAN — `Rendah`] Penamaan Aktor (`Admin`)**: Aktor manusia sah, sebaiknya diseragamkan menjadi `Pustakawan (Admin)`.
  4. **[SALAH — `Rendah`] Klaim `"grafik"` pada Pesan `12: Tampilkan kartu statistik & grafik`**: `dashboard.php` tidak menampilkan komponen grafik/chart apa pun (tidak ada `<canvas>`/SVG chart), melainkan kartu statistik ringkasan, daftar peringkat Top 5 Buku Terpopuler, **tabel HTML Rekap Bulanan 12 bulan**, dan kartu Laporan Ringkasan (`dashboard.php:790-1035`, dilengkapi filter `?tahun=`, tombol `Cetak Laporan` `window.print()` `:979`, dan tautan `Kirim via WhatsApp` `wa.me` `:260, 971`).
  5. **[BENAR DENGAN CATATAN — `Rendah`] Spesifikasi Format `.xls` (SpreadsheetML XML 3 Sheet), Parameter `?tahun=`, & `JOIN buku`**:
     - Pesan `22:` sudah benar memakai ekstensi `.xls` (nama file persisnya `Laporan_AKSANOVA_{$tahun}_` + `Ymd_His` + `.xls`, `export_excel_dashboard.php:143`), namun perlu diperjelas bahwa keluarannya berformat **SpreadsheetML XML (Excel 2003 XML)** dengan **3 Worksheet** (`Rekap Bulanan {tahun}`, `Ringkasan Perpustakaan`, `Buku Terpopuler` Top 10, `:150-318`).
     - Parameter yang dikirim tombol ekspor (`dashboard.php:975`) adalah `?tahun=$tahun` (`2000..2100`, `export_excel_dashboard.php:41-42`), bukan semata "tahun berjalan" (pesan `20:`). Selain itu kueri Buku Terpopuler melakukan `JOIN` antara `peminjaman` dan `buku` (`dashboard.php:130-135`, `export_excel_dashboard.php:109`), serta keduanya melakukan introspeksi `SHOW TABLES`/`SHOW COLUMNS` (`:45, 54` & `:13, 21`). *(Catatan bug kode di `export_excel_dashboard.php:273`: variabel `$total_tahun_pinjam` belum terdefinisi).*
  - **Saran Perbaikan**: Tulis ulang `.puml` sesuai kedua berkas aktual; ubah aktor menjadi `Pustakawan (Admin)`; ganti kata `"grafik"` pada pesan `12:` menjadi `"tabel rekap 12 bulan & laporan ringkasan"`; ubah pesan `14:` & `20:` agar menyertakan parameter `?tahun=`, serta beri keterangan `SpreadsheetML XML (.xls, 3 Worksheet)` pada pesan `22:`.

---

### 12. Diagram #31 — Ubah Profil & Kata Sandi Admin
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Sequence Diagram — Ubah Profil & Kata Sandi Admin` (Subjudul: `update_profil_admin.php`)
  - Gambar: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_31_profil_sandi_admin.png`
  - Sumber: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_31_profil_sandi_admin.puml`
- **File PHP Aktual**: `modal_profil_admin.php` (`:119-299`) dan `update_profil_admin.php` (`:1-125`). *(Catatan: `ganti_sandi_admin.php` masih ada di disk tanpa `csrf_verify()`, tetapi sudah tidak dirujuk oleh `modal_profil_admin.php:131`).*
- **Kecocokan PNG vs PUML**: **TIDAK COCOK** (`Tinggi`).
  - `.puml` (`1.304 B`) adalah templat generik 41 baris yang merujuk berkas fiktif `admin/profil.php`; sedangkan `.png` menampilkan `update_profil_admin.php` dengan 6 partisipan dan 16 pesan.
- **Rincian Apa yang Tampak di PNG**:
  - **Partisipan (6)**: `Admin` (`actor`), `Modal Profil Admin` (`boundary`), `Proses Update Profil` (`control`), `Tabel users` (`entity`), `Tabel admin_profile` (`entity`), `Penyimpanan Foto` (`participant` kotak).
  - **Alur Pesan (1–16)**: `1:` Mengubah nama, foto, sandi lalu klik Simpan -> `2:` Kirim data profil (`POST + foto`) -> `opt [Sandi diisi]` (`3:` SELECT sandi admin -> `4:` Hash sandi -> `5:` Validasi (`≥ 8`, huruf & angka, konfirmasi, sandi lama benar & beda) -> `alt [Validasi sandi gagal]` (`6:` Redirect dengan `profil_err` -> `7:` Tampilkan pesan kesalahan) / `[Valid]` (`8:` UPDATE password (hash) -> `9:` Sandi diperbarui)) -> `opt [Ada foto baru valid (≤ 5 MB)]` (`10:` Hapus foto lama & simpan foto baru -> `11:` Path foto baru) -> `12:` UPDATE / INSERT `display_name` (& foto) -> `13:` Profil tersimpan -> `14:` Perbarui `user_name` di sesi -> `15:` Redirect dengan `profil_saved` -> `16:` Tampilkan profil terbaru.
  - **Kualitas Visual PNG**: Sangat bersih, simbol `≥` dan `≤` tampil utuh, penomoran `1:` s/d `16:` urut.
- **Daftar Pemeriksaan Elemen & Temuan** *(6 aspek: 2 `BENAR`, 2 `BENAR DENGAN CATATAN`, 2 `SALAH`, 0 `TIDAK TERVERIFIKASI`)*:
  1. **[BENAR] Nama Berkas Tujuan (`update_profil_admin.php` via `Modal Profil Admin`)**: Tepat merujuk handler aktif `update_profil_admin.php` yang dipanggil `modal_profil_admin.php:131` (bukan endpoint warisan `ganti_sandi_admin.php`).
  2. **[BENAR] Pembaruan `$_SESSION["user_name"]` & Redirect `?profil_saved=1`**: Akurat mencerminkan `update_profil_admin.php:122-125`.
  3. **[BENAR DENGAN CATATAN — `Rendah`] Penamaan Aktor (`Admin`)**: Aktor manusia sah, sebaiknya ditulis `Pustakawan (Admin)`.
  4. **[SALAH — `Sedang`] Guard Sesi Admin, Whitelist `redirect`, & `csrf_verify()` Tidak Digambarkan**: `update_profil_admin.php` memeriksa sesi admin (`:5-8`), memvalidasi `$_POST["redirect"]` terhadap whitelist 10 halaman admin (`:12-21`), dan memverifikasi `csrf_verify()` (`:23-26`, gagal -> redirect `?profil_err=...` + `exit`); ketiganya absen di PNG.
  5. **[SALAH — `Sedang`] Urutan Validasi Format Sandi vs `SELECT users` Terbalik & Eksekusi Tampak Tetap Lanjut Seusai `profil_err`**:
     - Di kode (`update_profil_admin.php:41-55`), validasi kelengkapan 3 kolom, panjang `>= 8`, huruf+angka, dan kecocokan konfirmasi dijalankan **sebelum** `SELECT password FROM users`; kueri DB hanya dipanggil bila format valid.
     - Secara struktural UML di PNG, cabang `[Validasi sandi gagal]` (`6–7`) berada di dalam `opt [Sandi diisi]` tanpa penghentian alur (`return`/`exit` atau pembungkusan langkah `10–16`), sehingga lifeline `Proses Update Profil` tampak tetap berlanjut mengeksekusi simpan foto & `UPDATE admin_profile` (`10–16`), padahal di kode `:66-69` skrip langsung `exit` setelah `header("Location: $redirect?profil_err=...")`.
  6. **[BENAR DENGAN CATATAN — `Rendah`] Kueri `SELECT foto` & `SELECT id` ke `Tabel admin_profile` serta Validasi MIME Foto**: Sebelum menghapus foto lama di pesan `10:`, kode memanggil `SELECT foto FROM admin_profile LIMIT 1` (`:98`), memvalidasi ekstensi `jpg/jpeg/png/webp/gif` + `mime_content_type` + ukuran `<= 5 MB` (`:91-96`, foto tak valid diabaikan tanpa error), serta memanggil `SELECT id FROM admin_profile LIMIT 1` (`:109`) sebelum `UPDATE`/`INSERT`.
  - **Saran Perbaikan**: Tulis ulang `.puml` merujuk `modal_profil_admin.php` & `update_profil_admin.php`; ubah aktor ke `Pustakawan (Admin)`; tambahkan pemeriksaan sesi, whitelist `redirect`, dan `csrf_verify()`; urutkan validasi format sandi sebelum `SELECT users`; dan bungkus langkah simpan foto + profil dalam cabang yang tidak dieksekusi bila validasi sandi gagal (`exit`).

---

### 13. Diagram #32 — Logout
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Sequence Diagram — Logout` (Subjudul: `logout.php`)
  - Gambar: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_32_logout.png`
  - Sumber: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_32_logout.puml`
- **File PHP Aktual**: `logout.php` (`:1-160`) dan interseptor konfirmasi global `settings_include.php` (`:289-352`).
- **Kecocokan PNG vs PUML**: **TIDAK COCOK** (`Tinggi`).
  - `.puml` (`1.268 B`) adalah templat generik 41 baris yang **secara palsu menggambarkan `csrf_verify()` dan `Eksekusi prepared statement query` ke `Basis Data (MySQL Engine)`** (padahal `logout.php` tidak meng-include `db.php`, memiliki `0` kueri SQL, dan tidak memanggil `csrf_verify()`); sedangkan `.png` memiliki 4 partisipan tanpa DB dan 5 pesan sederhana.
- **Rincian Apa yang Tampak di PNG**:
  - **Partisipan (4)**: `Pengguna` (`actor` stick figure), `Tombol Logout` (`boundary`), `Proses Logout` (`control`), `Halaman Sign In` (`boundary`).
  - **Alur Pesan (1–5)**: `1:` Klik Logout -> `2:` Panggil `logout.php` -> self-call `3:` `session_destroy()` -> `4:` Redirect ke `sign_in.php` -> `5:` Tampilkan halaman Sign In.
  - **Kualitas Visual PNG**: Sangat bersih, terbaca jelas, penomoran `1:` s/d `5:` urut.
- **Daftar Pemeriksaan Elemen & Temuan** *(4 aspek: 0 `BENAR`, 2 `BENAR DENGAN CATATAN`, 2 `SALAH`, 0 `TIDAK TERVERIFIKASI`)*:
  1. **[BENAR DENGAN CATATAN — `Rendah`] Subjudul `logout.php` & Tidak Adanya `csrf_verify()` / DB**: Subjudul benar dan ketiadaan `csrf_verify()` maupun entitas DB di PNG sesuai kode aktual (`logout.php` memang tidak memanggil `csrf_verify()` di `:14-17` walaupun merender `csrf_field()` di `:152`, serta menerima `GET ?confirm=1`).
  2. **[SALAH — `Sedang`] Aktor Dinamai `"Pengguna"` (Bukan Aktor Sah)**: Stick figure diberi nama generik `"Pengguna"`, melanggar ketetapan 3 aktor sah; untuk Logout seharusnya menggunakan `Anggota (Siswa & Guru)` dan/atau `Pustakawan (Admin)`.
  3. **[SALAH — `Tinggi`] Mekanisme Konfirmasi Dua Tahap (`AksaConfirm` & Halaman Konfirmasi `logout.php`) Hilang Total**: PNG mengklaim klik tombol Logout langsung memanggil `logout.php` dan seketika menghancurkan sesi. Faktanya:
     - Pada seluruh halaman yang memuat `settings_include.php:289-352`, klik tautan `logout.php` dicegat oleh `konfirmasiLogoutAksa()` yang menampilkan dialog `AksaConfirm` (`"Ya, Keluar"` vs `"Tidak, Batal"`), lalu men-submit form dinamis `POST logout.php` (`confirm_logout=1`).
     - Apabila `logout.php` diakses langsung via `GET` biasa tanpa `?confirm=1` (`logout.php:14-17, 39-158`), server **tidak menjalankan `session_destroy()`**, melainkan merender halaman HTML **Konfirmasi Logout** (dengan tombol `"Tidak, Batal"` kembali ke `back_url` dan `"Ya, Keluar"` `POST confirm_logout=1`).
  4. **[BENAR DENGAN CATATAN — `Rendah`] Guard Sesi Awal & Pembersihan Cookie Sesi Tidak Tampak**: Sebelum `session_destroy()` (`logout.php:34`), kode memeriksa `if (!isset($_SESSION['user_id']))` (`:9-12`), mengosongkan `$_SESSION = []` (`:21`), dan menghapus cookie sesi lewat `setcookie(session_name(), '', time() - 42000, ...)` (`:22-33`).
  - **Saran Perbaikan**: Tulis ulang `.puml` (hapus `MySQL Engine` dan `csrf_verify()`); ganti aktor `"Pengguna"` menjadi `Anggota (Siswa & Guru)` / `Pustakawan (Admin)`; gambarkan dialog konfirmasi `AksaConfirm` (`POST confirm_logout=1`) beserta cabang fallback halaman konfirmasi HTML `logout.php` bila diakses tanpa konfirmasi, serta sertakan pengosongan `$_SESSION` + penghapusan cookie sesi sebelum `session_destroy()`.

---

### 14. Diagram #33 — Notifikasi Pengajuan Peminjaman (Admin)
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Sequence Diagram — Notifikasi Pengajuan Peminjaman (Admin)` (Subjudul: `notifikasi_pengajuan_admin.php · api_cek_pengajuan_admin.php`)
  - Gambar: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_33_notifikasi_pengajuan.png`
  - Sumber: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_33_notifikasi_pengajuan.puml`
- **File PHP Aktual**: `notifikasi_pengajuan_admin.php` (`:15-17, 573-831`) dan `api_cek_pengajuan_admin.php` (`:1-71`).
- **Kecocokan PNG vs PUML**: **TIDAK COCOK** (`Tinggi`).
  - `.puml` (`1.325 B`) adalah templat generik 41 baris yang merujuk berkas fiktif `admin/check_notif.php` dan mengklaim `csrf_verify()`; sedangkan `.png` menampilkan `notifikasi_pengajuan_admin.php · api_cek_pengajuan_admin.php` dengan 5 partisipan dan 11 pesan di dalam `loop [polling tiap 4 detik]`.
- **Rincian Apa yang Tampak di PNG**:
  - **Partisipan (5)**: `Admin` (`actor`), `Skrip Notifikasi (halaman admin)` (`boundary`), `Proses Cek Pengajuan` (`control`), `Tabel pengajuan_peminjaman` (`entity`), `Penyimpanan Browser (localStorage)` (`participant` kotak).
  - **Alur Pesan (1–11)** dalam `loop [polling tiap 4 detik]`: `1:` Panggil `api_cek_pengajuan_admin.php` -> `alt [Belum login admin]` (`2:` 403 Akses ditolak) / `[Admin login]` (`3:` COUNT pengajuan menunggu & SELECT yang terbaru -> `4:` Jumlah & data terbaru -> `5:` JSON `count_menunggu`, `latest` -> `6:` Baca ID pengajuan terakhir dilihat -> `7:` ID terakhir -> `opt [ID terbaru > ID terakhir dilihat]` (`8:` Tampilkan pop-up, bunyi lonceng & badge -> `9:` Klik tautan ke Pengajuan Buku atau tutup -> `10:` Simpan ID pengajuan terakhir dilihat -> `11:` Tersimpan)).
  - **Kualitas Visual PNG**: Jernih dan terstruktur; label pesan `8:` dan `9:` sedikit melewati garis lifeline `Admin` & `Skrip Notifikasi` karena jarak kolom rapat, tetapi memiliki latar belakang putih sehingga tetap terbaca jelas.
- **Daftar Pemeriksaan Elemen & Temuan** *(5 aspek: 2 `BENAR`, 2 `BENAR DENGAN CATATAN`, 1 `SALAH`, 0 `TIDAK TERVERIFIKASI`)*:
  1. **[BENAR] Nama Berkas Tujuan (`notifikasi_pengajuan_admin.php · api_cek_pengajuan_admin.php`)**: Sesuai kedua berkas PHP aktual.
  2. **[BENAR] Interval Polling `4 detik` (`4000 ms`), Guard `403`, & Dedup via `localStorage`**: Akurat mencerminkan `POLL_INTERVAL = 4000` (`notifikasi_pengajuan_admin.php:575, 825, 829`), guard HTTP `403` JSON (`api_cek_pengajuan_admin.php:13-17`, tanpa CSRF karena `GET`), dan penyimpanan `aksa_notif_pengajuan_last_seen_id` ke `localStorage` saat ditutup/dibuka (`notifikasi_pengajuan_admin.php:574, 746, 752, 809-814`).
  3. **[BENAR DENGAN CATATAN — `Rendah`] Penamaan Aktor (`Admin`)**: Aktor manusia sah, sebaiknya ditulis `Pustakawan (Admin)`.
  4. **[SALAH — `Rendah`] Pembaruan Badge Sidebar Digabung di Dalam `opt [ID terbaru > ID terakhir dilihat]`**: Di PNG, pembaruan `badge` berada di pesan `8:` di dalam `opt`. Faktanya di `notifikasi_pengajuan_admin.php:797-806`, `updateSidebarBadge(countMenunggu)` dijalankan **pada setiap siklus polling sebelum membaca `localStorage`**, dan bila `countMenunggu === 0` skrip menyembunyikan badge serta menutup pop-up yang masih terbuka lalu `return` tanpa mengecek `localStorage`.
  5. **[BENAR DENGAN CATATAN — `Rendah`] Kueri Kedua Kondisional (`count_menunggu > 0`) dengan `LEFT JOIN buku` & `LEFT JOIN users`**: Pada `api_cek_pengajuan_admin.php:31-42`, `SELECT` pengajuan terbaru hanya dijalankan jika `$count_menunggu > 0` dan melakukan `LEFT JOIN buku b` serta `LEFT JOIN users u` (untuk judul, sampul, rak, NIK, no HP), serta JSON juga menyertakan field `latest_id` (`:68`). Chime 4 nada dibangkitkan via Web Audio API (`:580-606`) dan penutupan pop-up disinkronkan lintas tab via event `storage` (`:763-770`).
  - **Saran Perbaikan**: Tulis ulang `.puml` merujuk kedua berkas aktual; ubah aktor ke `Pustakawan (Admin)`; pindahkan pembaruan badge sidebar ke luar `opt [ID terbaru > ID terakhir dilihat]` (serta tambahkan kondisi `count_menunggu > 0`), dan cantumkan keterlibatan tabel `buku` & `users` pada kueri detail pengajuan terbaru.

---

### 15. Diagram #34 — Pengaturan Peta Lokasi & Jam Operasional
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Sequence Diagram — Pengaturan Peta Lokasi & Jam Operasional` (Subjudul: `pengaturan_lokasi.php, index.php`)
  - Gambar: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_34_pengaturan_lokasi.png`
  - Sumber: `c:\laragon\www\aksanova\UML\Squence\Lengkap\sequence_34_pengaturan_lokasi.puml`
- **File PHP Aktual**: `pengaturan_lokasi.php` (`:9-12, 43-62, 68-260, 2245-2310`) dan pembacaan publik di `index.php` (`:134-175, 3678`).
- **Kecocokan PNG vs PUML**: **TIDAK COCOK** (`Tinggi`).
  - `.puml` (`1.308 B`) adalah templat generik 41 baris yang merujuk berkas fiktif `admin/lokasi.php`; sedangkan `.png` menampilkan `pengaturan_lokasi.php, index.php` dengan 4 partisipan dan 14 pesan.
- **Rincian Apa yang Tampak di PNG**:
  - **Partisipan (4)**: `Admin` (`actor`), `Halaman Pengaturan Lokasi` (`boundary`), `Proses Pengaturan Lokasi` (`control`), `Tabel pengaturan` (`entity`).
  - **Alur Pesan (1–14)**: `1:` Membuka halaman Pengaturan Lokasi -> `2:` Ambil pengaturan lokasi & jam buka -> `3:` SELECT pengaturan lokasi, peta, jam operasional & status layanan -> `4:` Nilai pengaturan lokasi & jam -> `5:` Data lokasi, peta & status operasional -> `6:` Tampilkan formulir, peta & jam buka -> `7:` Mengubah lokasi, peta, jam atau status (perubahan tersimpan otomatis) -> `8:` Kirim aksi `simpan_lokasi_jam` -> `alt [Nama sekolah / alamat kosong]` (`9:` Pesan: nama & alamat wajib diisi -> `10:` Tampilkan pesan error) / `[Data lengkap & valid]` (`11:` INSERT / UPDATE pengaturan lokasi -> `12:` Pengaturan tersimpan -> `13:` Pesan berhasil disimpan & status baru -> `14:` Tampilkan pengaturan baru (tampil realtime di `index.php`)).
  - **Kualitas Visual PNG**: Sangat bersih, terbaca jelas, penomoran `1:` s/d `14:` urut tanpa cacat visual.
- **Daftar Pemeriksaan Elemen & Temuan** *(5 aspek: 1 `BENAR`, 3 `BENAR DENGAN CATATAN`, 1 `SALAH`, 0 `TIDAK TERVERIFIKASI`)*:
  1. **[BENAR] Validasi Wajib Isi (`$lokasi_sekolah === '' || $lokasi_alamat === ''`)**: Akurat mencerminkan `pengaturan_lokasi.php:152-155`.
  2. **[BENAR DENGAN CATATAN — `Rendah`] Aktor (`Admin` Stick Figure; Google Maps Tidak Dijadikan `actor`)**: Google Maps sudah benar tidak digambar sebagai stick figure `actor`, namun label `"Admin"` perlu diseragamkan menjadi `Pustakawan (Admin)`.
  3. **[BENAR DENGAN CATATAN — `Rendah`] Subjudul `pengaturan_lokasi.php, index.php` vs Cakupan Lifeline**: Kedua nama file benar, namun `index.php` tidak digambarkan sebagai lifeline tersendiri dan hanya disebut dalam kurung pada pesan `14: (tampil realtime di index.php)` (faktanya `index.php:134` membaca `Tabel pengaturan` saat pengunjung memuat halaman `index.php`).
  4. **[SALAH — `Sedang`] Guard Sesi Admin & `csrf_verify()` Tidak Digambarkan**: `pengaturan_lokasi.php:9-12` memeriksa sesi admin dan `pengaturan_lokasi.php:72-80` memanggil `csrf_verify()` (mengembalikan JSON error bila `is_ajax=1`); keduanya tidak tampak di PNG.
  5. **[BENAR DENGAN CATATAN — `Rendah`] Detail 20 Kunci `pengaturan`, Deteksi Peta (`query` vs `embed`), & Auto-Save AJAX JSON**: Di kode (`pengaturan_lokasi.php:82-182`), server mendeteksi apakah input peta berupa `<iframe src="...">` / URL embed (`mode='embed'`) atau teks biasa (`mode='query'`), merangkai `lokasi_jam` otomatis, menyimpan **20 kunci** via `setSetting()` (`SELECT id` lalu `UPDATE`/`INSERT`, `:52-62`), serta pada mode auto-save (`is_ajax=1`, `:185-260`, JS `fetch()` `:2262`) mengembalikan JSON berisi URL pratinjau `<iframe>` Google Maps dan kalkulasi status operasional WIB (`otomatis` / `buka_manual` / `tutup_libur`) tanpa reload halaman.
  - **Saran Perbaikan**: Tulis ulang `.puml` sesuai `pengaturan_lokasi.php`; ubah aktor menjadi `Pustakawan (Admin)`; tambahkan pemeriksaan sesi admin & `csrf_verify()`; perjelas pengiriman AJAX `is_ajax=1` beserta respons JSON pembaruan pratinjau peta & badge status WIB; serta tambahkan lifeline opsional atau catatan pembacaan `SELECT pengaturan` saat pengunjung membuka `index.php`.
---

## 5. Tabel Temuan Masalah & Ketidaksesuaian Terkonsolidasi (Sequence Diagram)

| ID | Dokumen + Lokasi | Isi Dokumen | Fakta Kode | Bukti Path:Baris | Keparahan | Perbaikan yang Disarankan |
|---|---|---|---|---|:---:|---|
| **[SEQ-01]** | Seluruh 23 file `.puml` di `UML/Squence/Lengkap/` | File `.puml` berisi 41 baris kode generik yang merujuk subdirektori fiktif `admin/*.php`, `member/*.php`, dan `api/*.php`. | Seluruh arsitektur kode AKSA NOVA adalah flat di root folder. Tidak ada file `admin/buku.php`, `admin/kirim_wa.php`, dll. | [`docs/audit_v2/nonexistent_refs.json`](file:///c:/laragon/www/aksanova/docs/audit_v2/nonexistent_refs.json) | **Kritis** | Tulis ulang seluruh berkas `.puml` agar merefleksikan alur spesifik dan file PHP riil, bukan stub generik copy-paste. |
| **[SEQ-02]** | `UML/Squence/Lengkap/sequence_24_pengingat_keterlambatan_wa.png` | Menggambar aktor orang-orangan `Penjadwal (Cron)` dan blok `opt [Salah tandai] -> batal_wa: UPDATE wa_terkirim = 0`. | Cron bukan aktor manusia (melanggar ATURAN BESI #6). Di kode PHP [`telah_dipinjam.php`](file:///c:/laragon/www/aksanova/telah_dipinjam.php), tidak ada fitur pembatalan WA (`batal_wa`). Tombol WA hanya membuka tautan eksternal `https://wa.me/...`. | [`telah_dipinjam.php:380-420`](file:///c:/laragon/www/aksanova/telah_dipinjam.php#L380-L420) | **Kritis** | Hapus aktor Cron (jadikan Pustakawan sebagai pemicu pengingat), dan hapus blok fiktif pembatalan WA (`batal_wa`). |
| **[SEQ-03]** | `UML/Squence/Lengkap/S08_Lupa_Kartu_OTP.png` | Pesan 51 menyatakan kartu anggota baru diterbitkan dengan format `(QR code)`. Urutan rate limit Langkah 1 mendahulukan cek email sebelum SELECT user. Timeout Langkah 3 tidak memodelkan auto-unfreeze. | Kartu anggota menggunakan format **Barcode Code 128** (`barcode128.php`). Pengecekan limit user memerlukan user_id hasil SELECT. Timeout Langkah 3 (>10m) otomatis mengembalikan `card_status='active'`. | [`kartu_anggota.php:108`](file:///c:/laragon/www/aksanova/kartu_anggota.php#L108), [`lupa_kartu.php:105, 262`](file:///c:/laragon/www/aksanova/lupa_kartu.php#L105) | **Tinggi** | Ganti label `(QR code)` menjadi `Barcode Code 128` dan perbaiki urutan pengecekan rate limit serta auto-unfreeze pada Langkah 3. |
| **[SEQ-04]** | `UML/Squence/Lengkap/sequence_28_pengaturan_musik.png` | Menggambar aksi simpan form monolitik `Kirim aksi simpan + file musik` (cabang kode warisan `:196-246`). | Antarmuka pengguna `pengaturan_musik.php` tidak memiliki `<form>` simpan global, melainkan bekerja lewat 3 aksi AJAX independen: `toggle_musik`, `simpan_judul`, dan `upload_file`. | [`pengaturan_musik.php:1-75`](file:///c:/laragon/www/aksanova/pengaturan_musik.php#L1-L75) | **Tinggi** | Gambar 3 alur pesan AJAX terpisah untuk toggle status audio, pembaruan judul, dan pengunggahan berkas musik. |
| **[SEQ-05]** | `UML/Squence/Lengkap/sequence_32_logout.png` | Menggambar klik logout langsung memanggil `session_destroy()`. | Di kode riil, sistem mengimplementasikan konfirmasi modal `AksaConfirm` sebelum mengirim POST `confirm_logout=1`, serta halaman HTML konfirmasi jika diakses melalui GET. | [`includes/settings_include.php:289-352`](file:///c:/laragon/www/aksanova/includes/settings_include.php#L289-L352), [`logout.php:16-158`](file:///c:/laragon/www/aksanova/logout.php#L16-L158) | **Tinggi** | Tambahkan interaksi konfirmasi logout pada alur sequence sebelum penghancuran sesi. |
| **[SEQ-06]** | 10 Diagram Admin (`#20, #21, #22, #23, #24, #25, #26, #27, #31, #34`) | Gambar PNG tidak menampilkan pemanggilan fungsi `csrf_verify()`. | Seluruh file PHP tersebut secara ketat memverifikasi token CSRF di baris awal POST (`halaman_admin.php:73`, `telah_dipinjam.php:78`, `pengaturan_denda.php:66`, dll.). | [`docs/audit_v2/02_fakta_kode.md`](file:///c:/laragon/www/aksanova/docs/audit_v2/02_fakta_kode.md) | **Sedang** | Tambahkan pesan panggilan `csrf_verify()` pada seluruh sequence diagram aksi admin. |

---

## 6. Rekomendasi Perbaikan Sequence Diagram (Hanya Ditulis, Tidak Dikerjakan)

1. **Sinkronisasi Total PUML ke Kode Nyata:**
   Ganti seluruh stub PUML generik 41 baris di folder `Lengkap/` dengan alur riil yang memanggil file PHP di root direktori.
2. **Koreksi Aktor dan Fitur Halusinasi:**
   Hapus aktor `Penjadwal (Cron)` dan fitur `batal_wa` di sequence 24.
3. **Penyelarasan Kartu Barcode 128:**
   Ubah seluruh teks 'QR Code' pada S08 menjadi 'Barcode Code 128'.
