# LAPORAN AUDIT v2: UML ACTIVITY DIAGRAM
**Proyek:** AKSA NOVA — Sistem Informasi Perpustakaan Digital SMK Negeri 1 Rongga  
**Tanggal Audit:** 2026-11-08  
**Auditor:** Software Auditor & System Analyst (Audit v2 — Clean Room & Read-Only)  
**Status Audit:** SELESAI (Audit Read-Only)  

---

## 1. Ringkasan Eksekutif Activity Diagram

Audit terhadap seluruh artefak UML Activity Diagram AKSA NOVA mencakup **31 file gambar PNG** (7 diagram Core di `UML/Activty/` + 24 diagram Lengkap di `UML/Activty/Lengkap/`) dan **29 file sumber PlantUML (.puml)** (`A08_Lupa_Kartu_OTP.png` tidak memiliki file `.puml`). Seluruh gambar telah dibuka dan diperiksa secara visual satu per satu menggunakan alat inspeksi visual dan diverifikasi silang langsung dengan 44 berkas PHP serta skema basis data fisik.

**Sumber Kebenaran (Ground Truth):**
- Seluruh 44 file source code PHP ([`docs/audit_v2/02_fakta_kode.md`](file:///c:/laragon/www/aksanova/docs/audit_v2/02_fakta_kode.md)).
- Basis data DDL ([`database/aksa_nova.sql`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql)).
- Hasil pemindaian path fiktif ([`docs/audit_v2/nonexistent_refs.json`](file:///c:/laragon/www/aksanova/docs/audit_v2/nonexistent_refs.json)).

**Temuan Utama Sistemik:**
1. **Ketidakcocokan 100% Antara .png dan .puml (29 dari 29 Berpasangan = TIDAK COCOK):**
   - **Pada Folder Lengkap (23 file .puml):** Seluruh file `.puml` bertanggal 03 November 2026 adalah **stub templat generik copy-paste 37 baris** yang mencantumkan nama-nama file fiktif di folder `admin/*.php` dan `member/*.php` yang tidak pernah ada di repositori.
   - **Pada Folder Core (6 file .puml 01-07):** File `.puml` menyimpang dari `.png` (perbedaan percabangan decision node, penamaan aksi, dan validasi).
2. **Pelanggaran Partisi / Swimlane Aktor Non-Manusia (ATURAN BESI #6):**
   - Pada `activity_24_pengingat_keterlambatan_wa.png`, terdapat swimlane ketiga bernama **`Penjadwal (Cron)`** yang digambar menjalankan aktivitas sistem.
   - Pada `07_activity_tambah_buku_isbn.png`, terdapat swimlane ketiga bernama **`Layanan ISBN (API)`** yang digambar sebagai partisi proses independen.
3. **Halusinasi Fitur Kritis di Gambar PNG:**
   - **Diagram #24 (`activity_24`):** Menggambar alur keputusan **`[Salah tandai? Ya -> Klik Batal WA -> wa_terkirim = 0]`** (fitur fiktif `batal_wa` yang tidak ada di kode PHP).
   - **Diagram A08 (`A08_Lupa_Kartu_OTP.png`):** Pada aktivitas terminal akhir menyatakan kartu anggota diterbitkan dengan **`(QR Code)`**, padahal kartu resmi menggunakan **Barcode Code 128**. Pada Langkah 1 urutan cek email mendahului query SELECT user, dan pada Langkah 3 tidak memodelkan auto-unfreeze saat batas waktu 10 menit terlewati.
   - **Diagram #09 (`activity_09`):** Memodelkan bahwa anggota dapat mengubah `kelas`, padahal di `edit_kartu.php:210` kolom kelas bersifat readonly/disabled bagi anggota biasa. Sebaliknya diagram mengabaikan bahwa anggota wajib memasukkan `old_password` untuk mengganti kata sandi.
   - **Diagram #12 (`activity_12`):** Swimlane pengguna mengklaim Admin dapat membuka `daftar_buku.php` untuk menjelajahi katalog, padahal jika admin membuka file tersebut, kode PHP langsung me-redirect admin ke `halaman_admin.php` (`daftar_buku.php:12-16`).
   - **Diagram #03 (`03_activity`):** Mengklaim form pengajuan menampilkan pratinjau lokasi rak buku, padahal di `pengajuan_peminjaman.php:59, 69, 204` kolom `rak` tidak dimasukkan ke kueri SQL sehingga elemen preview lokasi rak tidak pernah terisi.
4. **Cacat Visual Tata Letak:**
   - Pada `activity_33_notifikasi_pengajuan.png`, garis konektor alur membelah menembus tepat di tengah kotak teks *"Menolak akses (403)"*.
   - Pada `activity_23` dan `activity_28`, terdapat kotak aktivitas hasil tempelan bitmap manual yang kehilangan garis batas tepi (borderless boxes).

---

## 2. Cakupan Terukur (Measurable Coverage)

| Kategori Elemen | Jumlah Diperiksa | BENAR | BENAR DENGAN CATATAN | SALAH | TIDAK TERVERIFIKASI | Keterangan Singkat |
|---|:---:|:---:|:---:|:---:|:---:|---|
| **Diagram Diperiksa (PNG)** | 31 | 0 | 17 | 14 | 0 | Seluruh 31 PNG diperiksa visual; tidak ada 100% sempurna |
| **Kesesuaian PNG vs PUML** | 29 | 0 | 0 | 29 | 0 | 100% pasangan tidak cocok; PUML stub generik/halusinasi |
| **Partisi / Swimlane (Aktor)** | 64 | 60 | 2 | 2 | 0 | 2 pelanggaran swimlane non-manusia (Cron di #24, API di #07) |
| **Simbol Awal & Akhir (Initial/Final)**| 31 | 31 | 0 | 0 | 0 | Node awal bulat hitam dan node akhir bullseye konsisten |
| **Aktivitas & Decision Nodes** | 215 | 88 | 71 | 56 | 0 | Halusinasi batal_wa, QR code, edit kelas, redirect admin |
| **Konektor & Guard Kondisi** | 142 | 76 | 39 | 27 | 0 | Garis menembus kotak teks di #33, patch bitmap di #23 & #28 |
| **TOTAL AKUMULATIF** | **512** | **255 (49,8%)** | **129 (25,2%)** | **128 (25,0%)** | **0 (0,0%)** | Status akurasi alur memerlukan pembenahan menyeluruh |

---

## PEMERIKSAAN RINCI PER DIAGRAM (1 s/d 16)

---

### 1. Activity Diagram 01 — Registrasi Anggota
- **Judul & Path Gambar / Sumber**:
  -Judul PNG: `Activity Diagram — Registrasi Anggota` *(subjudul: `Aplikasi AKSA NOVA · file: sign_up.php`)*
  - Gambar: `c:\laragon\www\aksanova\UML\Activty\01_activity_registrasi_anggota.png`
  - Sumber PUML: `c:\laragon\www\aksanova\UML\Activty\01_activity_registrasi_anggota.puml`
- **File PHP Aktual**: `sign_up.php`, `kartu_anggota.php`, `barcode128.php`, `kartu_masuk.php`, `kartu_clear.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - **Rincian Perbedaan**:
    1. Judul & swimlane berbeda: PNG memakai `Tamu` | `Sistem`, sedangkan PUML memakai `Tamu (Guest)` | `Sistem AKSA NOVA`.
    2. PUML memecah 2 *decision diamond* (`Token CSRF Valid?` dan `Email & NIS/NIP Belum Terdaftar?`) tanpa memeriksa format input/foto/domain email/sandi, sedangkan PNG menyatukan validasi dalam 1 *decision* `Data valid?`.
    3. Cabang gagal di PNG melakukan *loop-back* ke aksi `Mengisi data Siswa/Guru...`, sedangkan di PUML cabang gagal langsung berhenti di `stop` tanpa *loop-back*.
    4. PUML mencantumkan `Mengunduh Kartu Anggota Digital` dan detail `$_SESSION['kartu_data']`, sedangkan PNG hanya menulis `Melihat kartu anggota digital (username & No. Anggota)`.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: 2 kolom sah: `Tamu` dan `Sistem`.
  - **Alur Aksi & Keputusan**: `start` (Tamu) -> `Membuka halaman Sign Up` -> (Sistem) `Menampilkan form pendaftaran` -> (Tamu) `Mengisi data Siswa/Guru (NIS/NIP, no. HP, email, kata sandi) & mengunggah foto profil` -> (Sistem) `Memvalidasi input, format foto, serta email & NIS/NIP belum terdaftar` -> Diamond `Data valid?`:
    - `[Tidak]` -> `Menampilkan pesan kesalahan` -> *loop-back* ke `Mengisi data Siswa/Guru...`
    - `[Ya]` -> `Menyimpan foto, membuat username & No. Anggota otomatis, meng-hash kata sandi` -> `Menyimpan data ke tabel users (role member, status approved)` -> `Menampilkan Kartu Anggota Digital` -> (Tamu) `Melihat kartu anggota digital (username & No. Anggota)` -> `stop`.
  - **Visual PNG**: Bersih, proporsional, tidak ada distorsi diamond, tidak ada teks terpotong atau mojibake.
- **Daftar Pemeriksaan Elemen & Temuan** *(Total 7 aspek: **4 BENAR**, **2 BENAR DENGAN CATATAN**, **1 SALAH**, **0 TIDAK TERVERIFIKASI**)*:
  1. **Swimlane (`Tamu` & `Sistem`)** — **BENAR**: Hanya aktor sah (`sign_up.php:7-10` menolak user yang sudah login).
  2. **Status Akun Langsung `approved`** — **BENAR**: Sesuai `sign_up.php:215-216` (`VALUES (..., 'member', 'approved')`), tanpa antrean `pending`.
  3. **Pembangkitan `username`, `no_anggota` (`AN-YYYY-NNNNN`), & Hash Sandi** — **BENAR**: Sesuai `sign_up.php:179-210` (`password_hash(..., PASSWORD_DEFAULT)`).
  4. **Struktur Node Awal/Akhir, Decision Diamond, & Loop-back** — **BENAR**: 1 `start`, 1 `stop`, diamond 2 cabang berlabel `Ya`/`Tidak`, *loop-back* kembali ke pengisian form (dengan foto tersangga `temp_*` di `sign_up.php:121-135`).
  5. **Detail Field Input & Sifat Opsional Foto di Kotak Aksi** — **BENAR DENGAN CATATAN** (`Rendah`): Kotak Tamu menulis `Mengisi data Siswa/Guru (NIS/NIP, no. HP, email, kata sandi) & mengunggah foto profil`, belum menyebut `Nama Lengkap` (`full_name` wajib, `sign_up.php:53-55`) & `Konfirmasi Sandi` (`:94-96`), serta belum menandai bahwa **foto profil bersifat opsional** (`sign_up.php:98, 133-138`, maks 5 MB `jpg/png/webp` `:99, 110`) dan email dibatasi pada 3 domain (`@student.smkn1rongga.sch.id`, `@smkn1rongga.sch.id`, `@gmail.com`, `:80-86`) + `csrf_verify()` (`:37`).
     - *Saran*: Ubah teks aksi menjadi `Mengisi nama, jenis Siswa/Guru, NIS/NIP, no. HP, email, sandi + konfirmasi, & foto profil (opsional)`.
  6. **Representasi Kartu Anggota Digital (Barcode Code 128, Unduh PNG, & Auto-Login)** — **BENAR DENGAN CATATAN** (`Rendah`): PNG tidak mengklaim QR Code (sehingga tidak salah), tetapi melewatkan detail bahwa kartu merender **Barcode Code 128 SVG** (`kartu_anggota.php:108, 696`, `barcode128.php:5`), menyediakan tombol **Unduh PNG 300 DPI** (`kartu_anggota.php:704, 721-750`), dan tombol **Selesai, Masuk ke Akun** (`kartu_masuk.php:8-28`) karena `sign_up.php:229-245` baru menyetel `$_SESSION['kartu_data']` (belum login penuh).
     - *Saran*: Perjelas kotak akhir menjadi `Melihat/mengunduh Kartu Anggota Digital (Barcode Code 128, username & No. Anggota) lalu klik Masuk ke Akun`.
  7. **Sinkronisasi Berkas Sumber `.puml` terhadap `.png` & Kode** — **SALAH** (`Sedang`): `.puml` tidak sinkron dengan `.png`, tidak memiliki *loop-back* saat gagal validasi (`01_activity_registrasi_anggota.puml:45, 52`), dan melewatkan validasi format field/domain email/ukuran foto (`sign_up.php:53-139`).
     - *Saran*: Tulis ulang `.puml` agar 100% merepresentasikan alur `.png` yang telah disempurnakan.

---

### 2. Activity Diagram 02 — Login Pengguna
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Activity Diagram — Login Pengguna` *(subjudul: `Aplikasi AKSA NOVA · file: sign_in.php, logout.php`)*
  - Gambar: `c:\laragon\www\aksanova\UML\Activty\02_activity_login.png`
  - Sumber PUML: `c:\laragon\www\aksanova\UML\Activty\02_activity_login.puml`
- **File PHP Aktual**: `sign_in.php` *(serta `logout.php` yang tercantum keliru di subjudul PNG)*
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - **Rincian Perbedaan**:
    1. PUML mengklaim query berdasarkan `username/email/no_anggota` (`02_activity_login.puml:29`), padahal kode aktual & PNG hanya `email` atau `username` (`sign_in.php:40`).
    2. PUML mengecek `Status Approved` **sebelum** `password_verify` dan **lupa mengecek `card_status === 'frozen'`**, sedangkan PNG mengecek kecocokan akun & sandi terlebih dahulu baru mengecek `Akun disetujui & kartu tidak dibekukan?` (persis urutan `sign_in.php:47-54`).
    3. PNG memiliki *loop-back* ke pengisian form saat gagal login; PUML langsung berhenti di `stop`.
    4. Subjudul PNG mencantumkan `logout.php`, sedangkan PUML hanya `sign_in.php`.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: 2 kolom: `Pengguna (Anggota / Admin)` dan `Sistem`.
  - **Alur Aksi & Keputusan**: `start` -> `Membuka halaman Sign In` -> (Sistem) `Menampilkan form login` -> (Pengguna) `Mengisi username/email & kata sandi, lalu menekan Masuk` -> (Sistem) `Mencari akun berdasarkan email atau username, lalu memverifikasi kata sandi` -> Diamond 1 `Akun ada & sandi benar?`:
    - `[Tidak]` -> `Menampilkan pesan kesalahan (sandi salah / akun menunggu atau ditolak / kartu dibekukan)` -> *loop-back* ke `Mengisi username/email...`
    - `[Ya]` -> Diamond 2 `Akun disetujui & kartu tidak dibekukan?`:
      - `[Tidak]` -> masuk ke kotak pesan kesalahan yang sama -> *loop-back*.
      - `[Ya]` -> `Menyimpan sesi login (user_id, nama, role)` -> Diamond 3 `Role = Admin?`:
        - `[Ya]` -> `Mengalihkan ke dashboard.php (Dasbor Admin)`
        - `[Tidak]` -> `Mengalihkan ke beranda.php (Beranda Anggota)`
        - Keduanya bergabung ke (Pengguna) `Melihat halaman sesuai role` -> `stop`.
  - **Visual PNG**: Rapi, tidak ada tumpang tindih atau teks terpotong.
- **Daftar Pemeriksaan Elemen & Temuan** *(Total 7 aspek: **3 BENAR**, **2 BENAR DENGAN CATATAN**, **2 SALAH**, **0 TIDAK TERVERIFIKASI**)*:
  1. **Pencarian Identitas (`email` atau `username`) & Urutan Verifikasi Sandi + Status (`pending`/`rejected`/`frozen`)** — **BENAR**: Sesuai eksak dengan `sign_in.php:40, 47-54`.
  2. **Struktur Decision Diamonds, Label Cabang, & Loop-back** — **BENAR**: Ketiga diamond memiliki cabang `Ya`/`Tidak` lengkap dan *loop-back* mengarah tepat ke input login.
  3. **Pengalihan Berdasarkan Role (`dashboard.php` vs `beranda.php`)** — **BENAR DENGAN CATATAN** (`Rendah`): Sesuai `sign_in.php:62-66`, namun bagi Anggota kode juga mendukung pengalihan ke parameter internal `$safe_redirect` bila valid (`sign_in.php:7-14, 65`, mis. dari `pengajuan_peminjaman.php:11`), serta belum mencantumkan `csrf_verify()` (`:30`) dan `session_regenerate_id(true)` (`:56`).
     - *Saran*: Tambahkan catatan/aksi verifikasi CSRF, `session_regenerate_id(true)`, dan target `beranda.php` (atau halaman tujuan `redirect`).
  4. **Penamaan Swimlane `Pengguna (Anggota / Admin)`** — **BENAR DENGAN CATATAN** (`Rendah`): Menggabungkan dua aktor sah manusia yang menggunakan form login terpadu `sign_in.php`, tanpa aktor eksternal terlarang.
  5. **Subjudul PNG Mencantumkan `logout.php` Padahal Tidak Ada Alur Logout** — **SALAH** (`Rendah`): Subjudul PNG menulis `file: sign_in.php, logout.php`, padahal diagram ini sama sekali tidak memodelkan `logout.php` (logout sudah punya diagram sendiri di `activity_32_logout.png`).
     - *Saran*: Hapus `, logout.php` dari subjudul PNG sehingga menjadi `file: sign_in.php`.
  6. **Isi Berkas `.puml` Mengklaim Login dengan `no_anggota` & Lupa Cek `frozen`** — **SALAH** (`Tinggi`): `02_activity_login.puml:29-31` salah menulis query berdasarkan `no_anggota` (tidak ada di `sign_in.php:40`), salah urutan cek `approved` sebelum `password_verify()`, dan mengabaikan blokir `card_status === 'frozen'` (`sign_in.php:52-54`).
     - *Saran*: Selaraskan `.puml` dengan `sign_in.php:30-71` dan gambar `.png`.
  7. **Node Awal & Akhir** — **BENAR**: 1 `start` dan 1 `stop` bersih tanpa *dead-end*.

---

### 3. Activity Diagram 03 — Pengajuan Peminjaman Buku (Anggota)
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Activity Diagram — Pengajuan Peminjaman Buku (Anggota)` *(subjudul: `Prasyarat: anggota sudah login · file: pengajuan_peminjaman.php`)*
  - Gambar: `c:\laragon\www\aksanova\UML\Activty\03_activity_pengajuan_peminjaman_anggota.png`
  - Sumber PUML: `c:\laragon\www\aksanova\UML\Activty\03_activity_pengajuan_peminjaman_anggota.puml`
- **File PHP Aktual**: `pengajuan_peminjaman.php` *(serta pemantauan di `dashboard_user.php` dan polling di `notifikasi_pengajuan_admin.php`)*
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - **Rincian Perbedaan**:
    1. PUML menulis penyimpanan dengan **`status='pending'`** (`03_activity_pengajuan_peminjaman_anggota.puml:30`), padahal enum SQL & PHP aktual memakai **`status='menunggu'`** (`pengajuan_peminjaman.php:181`) sebagaimana tertulis benar di PNG!
    2. PUML **sama sekali tidak mencantumkan wajib unggah foto kartu anggota (`file_kartu` maks 5 MB)** dan `batas_kembali`, sedangkan PNG mencantumkannya secara eksplisit.
    3. PUML memiliki diamond cek login di awal dan berhenti tanpa *loop-back* saat gagal validasi; PNG memakai prasyarat login dan memiliki *loop-back* saat `Data valid? -> Tidak`.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: 2 kolom sah: `Anggota` dan `Sistem`.
  - **Alur Aksi & Keputusan**: `start` -> `Memilih buku di katalog & menekan Ajukan Pinjam` -> (Sistem) `Menampilkan form pengajuan (data buku, stok & lokasi rak)` -> (Anggota) `Mengisi jumlah buku (1-40), batas kembali, waktu ambil (sekarang / nanti) & mengunggah foto kartu anggota` -> (Sistem) `Memvalidasi stok, kuota 1 s/d 40 buku, tanggal & berkas gambar (maks. 5 MB)` -> Diamond `Data valid?`:
    - `[Tidak]` -> `Menampilkan pesan kesalahan` -> *loop-back* ke `Mengisi jumlah buku (1-40)...`
    - `[Ya]` -> `Menyimpan berkas kartu & data pengajuan (status 'menunggu')` -> `Menampilkan pesan pengajuan terkirim (admin menerima notifikasi pop-up)` -> (Anggota) `Menerima konfirmasi & menunggu keputusan petugas` -> `Memantau status di dashboard (disetujui -> ambil buku, ditolak -> lihat alasan)` -> `stop`.
  - **Visual PNG**: Teks pada 2 kotak hasil revisi (`Mengisi jumlah buku (1-40)...` dan `Memvalidasi stok, kuota 1 s/d 40 buku...`) menggunakan ukuran font lebih besar dan sangat mepet ke garis tepi bawah/samping kotak (`anggota` dan `(maks. 5 MB)` hampir menyentuh border), berbeda dari tipografi kotak lainnya.
- **Daftar Pemeriksaan Elemen & Temuan** *(Total 8 aspek: **4 BENAR**, **2 BENAR DENGAN CATATAN**, **2 SALAH**, **0 TIDAK TERVERIFIKASI**)*:
  1. **Swimlane (`Anggota` & `Sistem`)** — **BENAR**: Hanya aktor sah (`pengajuan_peminjaman.php:9-35` memverifikasi member `approved` & tidak `frozen`).
  2. **Kuota `1–40` Buku & Wajib Unggah Kartu `<= 5 MB`** — **BENAR**: Sesuai `pengajuan_peminjaman.php:89, 130-133` (`1..40` dan `<= stok`) serta `:138-166` (`jpg/jpeg/png/webp`, maks `5 MB`, `getimagesize()`).
  3. **Penyimpanan Status `'menunggu'` Tanpa Mengurangi Stok** — **BENAR**: Sesuai `pengajuan_peminjaman.php:179-182`.
  4. **Decision Diamond & Loop-back Validasi** — **BENAR**: Cabang `Ya`/`Tidak` lengkap dan *loop-back* tepat.
  5. **Klaim Menampilkan `lokasi rak` pada Form `pengajuan_peminjaman.php`** — **SALAH** (`Sedang`): Kotak Sistem ke-1 menulis `Menampilkan form pengajuan (data buku, stok & lokasi rak)`. Faktanya, meskipun template HTML menyiapkan elemen `#previewRak` (`pengajuan_peminjaman.php:819-821, 1030, 1045`), ketiga kueri SQL pengambilan buku di `pengajuan_peminjaman.php:59`, `:69`, dan `:204` **TIDAK memilih kolom `rak`** (`SELECT id, judul, penulis, isbn, genre, stok, gambar, sinopsis FROM buku`), sehingga lokasi rak tidak pernah tampil di form ini!
     - *Saran*: Hapus `& lokasi rak` dari kotak diagram (atau perbaiki kueri SQL bila kode nantinya direvisi, namun untuk dokumentasi kondisi kode saat ini hapus klaim `lokasi rak`).
  6. **Keterangan Teknis `(admin menerima notifikasi pop-up)` & Langkah Lanjutan Lintas Halaman** — **BENAR DENGAN CATATAN** (`Rendah`): Notifikasi pop-up admin sebenarnya dijalankan secara asinkron lewat *polling* 4 detik pada browser Admin yang sedang aktif (`notifikasi_pengajuan_admin.php:575, 789` -> `api_cek_pengajuan_admin.php`), bukan dikirim langsung oleh eksekusi `pengajuan_peminjaman.php`. Selain itu, kotak `Memantau status di dashboard...` adalah aktivitas terpisah di `dashboard_user.php:120-129`.
     - *Saran*: Sederhanakan kotak Sistem menjadi `Menampilkan pesan pengajuan berhasil dikirim` tanpa mencampurkan mekanisme polling Admin.
  7. **Kualitas Visual Kotak Revisi pada PNG** — **BENAR DENGAN CATATAN** (`Rendah`): Dua kotak (`Mengisi jumlah buku...` dan `Memvalidasi stok...`) memiliki ukuran font tidak seragam dan teks mepet garis batas kotak.
     - *Saran*: Render ulang PNG dengan ukuran font & padding seragam.
  8. **Ketidaksinkronan & Kesalahan Fakta pada `.puml`** — **SALAH** (`Tinggi`): `03_activity_pengajuan_peminjaman_anggota.puml:30` menulis `status='pending'` (salah enum DB) dan menghilangkan unggah wajib `file_kartu` (`pengajuan_peminjaman.php:138-176`).
     - *Saran*: Perbarui `.puml` agar selaras dengan `.png` dan `pengajuan_peminjaman.php`.

---

### 4. Activity Diagram 04 — Persetujuan Pengajuan Peminjaman (Admin)
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Activity Diagram — Persetujuan Pengajuan Peminjaman (Admin)` *(subjudul: `Prasyarat: admin sudah login · file: pengajuan_buku.php, api_cek_pengajuan_admin.php`)*
  - Gambar: `c:\laragon\www\aksanova\UML\Activty\04_activity_persetujuan_pengajuan_admin.png`
  - Sumber PUML: `c:\laragon\www\aksanova\UML\Activty\04_activity_persetujuan_pengajuan_admin.puml`
- **File PHP Aktual**: `pengajuan_buku.php`, `api_cek_pengajuan_admin.php`, `notifikasi_pengajuan_admin.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - **Rincian Perbedaan**:
    1. PUML menulis status awal tiket `'pending'` (`04_activity_persetujuan_pengajuan_admin.puml:20`), sedangkan kode & PNG memakai `'menunggu'`.
    2. PUML menulis `batas_kembali=7 hari ke depan` (`:29`), padahal kode aktual memakai `batas_kembali` pilihan anggota ditambah `' 23:59:59'` (`pengajuan_buku.php:72`).
    3. PUML **tidak memiliki pengecekan kecukupan stok (`Stok mencukupi?`)** dan tidak menyebutkan penyisipan *loop* sebanyak `total_buku` baris di `peminjaman`, sedangkan PNG mencantumkan keduanya!
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: 2 kolom sah: `Admin / Petugas` dan `Sistem`.
  - **Alur Aksi & Keputusan**: `start` -> `Membuka halaman Pengajuan Peminjaman (lewat pop-up notifikasi atau menu)` -> (Sistem) `Menampilkan daftar pengajuan 'menunggu' (peminjam, buku, lokasi rak & foto kartu)` -> (Admin) `Memeriksa data pengajuan & foto kartu anggota` -> Diamond 1 `Pengajuan disetujui?` (di swimlane Admin):
    - `[Tidak]` -> (Sistem) `Menyimpan status 'ditolak' & alasan penolakan` -> `stop`.
    - `[Ya]` -> (Sistem) Diamond 2 `Stok mencukupi?`:
      - `[Tidak]` -> `Menampilkan pesan stok tidak mencukupi` -> `stop`.
      - `[Ya]` -> `Mengurangi stok (atomic) & membuat data peminjaman 'dipinjam' sebanyak jumlah buku` -> `Mengubah status pengajuan menjadi 'disetujui' & mencatat waktu persetujuan` -> `Menampilkan pesan berhasil (data masuk ke Telah Dipinjam)` -> (Admin) `Menyiapkan buku untuk diambil anggota` -> `stop`.
  - **Visual PNG**: Sangat rapi, seluruh 3 node `stop` jelas, diamond dan teks terbaca sempurna.
- **Daftar Pemeriksaan Elemen & Temuan** *(Total 7 aspek: **5 BENAR**, **1 BENAR DENGAN CATATAN**, **1 SALAH**, **0 TIDAK TERVERIFIKASI**)*:
  1. **Swimlane (`Admin / Petugas` & `Sistem`)** — **BENAR**: Hanya aktor sah (`pengajuan_buku.php:6-9`).
  2. **Tampilan Daftar `'menunggu'` Beserta `lokasi rak` & `foto kartu`** — **BENAR**: Sesuai `pengajuan_buku.php:190, 905-914, 929-933` (`b.rak as buku_rak`, modal `previewKartu()`).
  3. **Cek Stok, Atomic Decrement, & Loop Insert Per Eksemplar ke `peminjaman`** — **BENAR**: Sesuai `pengajuan_buku.php:63-86` (`UPDATE buku SET stok = stok - $total_buku WHERE id = $buku_id AND stok >= $total_buku` lalu `for ($i = 0; $i < $total_buku; $i++) INSERT INTO peminjaman ...` dan `UPDATE pengajuan_peminjaman SET status = 'disetujui', approved_at = '$now'`).
  4. **Langkah Fisik (`Menyiapkan buku untuk diambil anggota`)** — **BENAR**: Berada pada urutan logis tepat setelah pengajuan disetujui dan stok diamankan.
  5. **Node Awal & Akhir (1 `start`, 3 `stop`) serta Visual** — **BENAR**: Bersih, tidak ada *dead-end*.
  6. **Aksi Input Alasan Penolakan oleh Admin pada Cabang `[Tidak]`** — **BENAR DENGAN CATATAN** (`Rendah`): Pada cabang `Pengajuan disetujui? -> Tidak`, panah langsung melompat dari diamond Admin ke kotak Sistem `Menyimpan status 'ditolak' & alasan penolakan`, melewati aksi Admin mengisi alasan penolakan pada modal tolak (`pengajuan_buku.php:96-97, 1071`, default `'Pengajuan ditolak oleh petugas perpustakaan.'`), serta verifikasi CSRF (`:43`) dan cek pengajuan masih `'menunggu'` (`:47-56`).
     - *Saran*: Sisipkan kotak aksi Admin `Mengisi alasan penolakan & menekan Tolak` sebelum kotak Sistem `Menyimpan status 'ditolak'...`.
  7. **Ketidaksinkronan & Kesalahan Fakta pada `.puml`** — **SALAH** (`Tinggi`): `04_activity_persetujuan_pengajuan_admin.puml:20, 29` salah menulis status `'pending'`, salah mengklaim `batas_kembali=7 hari ke depan` (mengabaikan `pengajuan_buku.php:72`), tidak mengecek stok, dan tidak mencatat *loop* per eksemplar.
     - *Saran*: Tulis ulang `.puml` mengikuti `.png` dan `pengajuan_buku.php:43-110`.

---

### 5. Activity Diagram 05 — Peminjaman Langsung di Perpustakaan
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Activity Diagram — Peminjaman Langsung di Perpustakaan` *(subjudul: `Prasyarat: admin sudah login · file: pinjam_buku.php`)*
  - Gambar: `c:\laragon\www\aksanova\UML\Activty\05_activity_peminjaman_langsung.png`
  - Sumber PUML: `c:\laragon\www\aksanova\UML\Activty\05_activity_peminjaman_langsung.puml`
- **File PHP Aktual**: `pinjam_buku.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - **Rincian Perbedaan**:
    1. PUML mengklaim fitur yang **TIDAK ADA** di kode: `Pindai Barcode Kartu Siswa atau input Nomor Anggota` (`05_activity_peminjaman_langsung.puml:18`), `Pindai Barcode Buku` (`:19`), dan `Tampilkan struk bukti transaksi berhasil` (`:28`). Di `pinjam_buku.php`, Admin memilih buku dari daftar katalog & memilih anggota dari modal daftar anggota aktif!
    2. PNG memodelkan 3 *decision diamond* sesuai `pinjam_buku.php:68-102` dengan *loop-back* saat error; PUML hanya punya 2 *decision* dan berhenti tanpa *loop-back*.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: 2 kolom sah: `Admin / Petugas` dan `Sistem`.
  - **Alur Aksi & Keputusan**: `start` -> `Membuka halaman Pinjam Buku` -> (Sistem) `Menampilkan daftar buku tersedia (stok > 0) beserta lokasi rak` -> (Admin) `Memilih buku & anggota, lalu mengisi tanggal pengembalian` -> (Sistem) 3 Diamond berurutan:
    1. `Buku, anggota & tanggal terisi?` (`[Tidak]` -> `Menampilkan pesan kesalahan` -> *loop-back* ke `Memilih buku & anggota...`; `[Ya]` -> lanjut)
    2. `Anggota terdaftar & disetujui?` (`[Tidak]` -> `Menampilkan pesan kesalahan` -> *loop-back*; `[Ya]` -> lanjut)
    3. `Stok buku tersedia?` (`[Tidak]` -> `Menampilkan pesan kesalahan` -> *loop-back*; `[Ya]` -> lanjut)
    -> `Mengurangi stok 1 & mencatat peminjaman (waktu pinjam otomatis, status 'dipinjam')` -> `Menampilkan pesan 'Buku berhasil dipinjam'` -> (Admin) `Menyerahkan buku kepada anggota` -> `stop`.
  - **Visual PNG**: Bersih, garis penggabungan 3 cabang `Tidak` ke kotak `Menampilkan pesan kesalahan` sangat rapi dan tidak berpotongan membingungkan.
- **Daftar Pemeriksaan Elemen & Temuan** *(Total 7 aspek: **5 BENAR**, **1 BENAR DENGAN CATATAN**, **1 SALAH**, **0 TIDAK TERVERIFIKASI**)*:
  1. **Swimlane (`Admin / Petugas` & `Sistem`)** — **BENAR**: Hanya aktor sah (`pinjam_buku.php:9-12`).
  2. **Tampilan Buku `stok > 0` & Pencatatan 1 Eksemplar Atomik** — **BENAR**: Sesuai `pinjam_buku.php:100-107, 126` (`UPDATE buku SET stok = stok - 1 WHERE id = $buku_id AND stok > 0`, `INSERT INTO peminjaman ...`).
  3. **Urutan 3 Validasi & Loop-back** — **BENAR**: Urutan ketiga *decision diamond* selaras persis dengan `if/else` bertingkat di `pinjam_buku.php:68, 73-82, 84-90, 100-114`.
  4. **Langkah Fisik (`Menyerahkan buku kepada anggota`)** — **BENAR**: Urutan logis tepat setelah transaksi tercatat berhasil di sistem.
  5. **Node Awal/Akhir & Visual PNG** — **BENAR**: 1 `start`, 1 `stop`, visual jernih.
  6. **Syarat Kartu Tidak Dibekukan (`card_status != 'frozen'`) pada Diamond ke-2** — **BENAR DENGAN CATATAN** (`Sedang`): Diamond ke-2 pada PNG hanya berbunyi `Anggota terdaftar & disetujui?`, padahal kueri validasi di `pinjam_buku.php:74-80` dan daftar modal di `:153-156` secara eksplisit juga memeriksa **`(card_status IS NULL OR card_status != 'frozen')`** dengan pesan `'Anggota tidak ditemukan, belum disetujui, atau kartunya sedang dibekukan.'` (serta `csrf_verify()` di `:60`).
     - *Saran*: Perbarui teks diamond ke-2 menjadi `Anggota aktif, disetujui & kartu tidak beku?`.
  7. **Halusinasi Fitur Pemindai Barcode & Cetak Struk pada `.puml`** — **SALAH** (`Tinggi`): `05_activity_peminjaman_langsung.puml:18-19, 28` mengklaim fitur pindai barcode kartu/buku dan tampilan struk transaksi yang tidak ada di `pinjam_buku.php`.
     - *Saran*: Hapus klaim pindai barcode & struk di `.puml` dan selaraskan penuh dengan `.png`.

---

### 6. Activity Diagram 06 — Pengembalian Buku & Denda
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Activity Diagram — Pengembalian Buku & Denda` *(subjudul: `Prasyarat: admin sudah login · file: telah_dipinjam.php, pengaturan_denda.php`)*
  - Gambar: `c:\laragon\www\aksanova\UML\Activty\06_activity_pengembalian_denda.png`
  - Sumber PUML: `c:\laragon\www\aksanova\UML\Activty\06_activity_pengembalian_denda.puml`
- **File PHP Aktual**: `telah_dipinjam.php`, `pengaturan_denda.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - **Rincian Perbedaan**:
    1. PUML mengabaikan **`grace_period` (masa toleransi)** dan **`denda_aktif`** (`06_activity_pengembalian_denda.puml:24-25`: `Terlambat >= 1 Hari? -> hari_terlambat * tarif_per_hari`), sedangkan PNG mencantumkan `(tarif x hari, setelah masa toleransi)` sesuai `telah_dipinjam.php:56-69`.
    2. PUML menulis nama kolom & enum yang **SALAH / TIDAK ADA di DB**: `tgl_kembali`, `status='kembali'`, dan `denda_terbayar='0'/'1'` (`:26, 29, 39`), sedangkan PNG menulis benar `status 'dikembalikan'` dan `status denda 'belum_bayar' / 'tidak_ada' / 'lunas'` (`telah_dipinjam.php:100-108, 148`).
    3. PNG mengecek `Status masih 'dipinjam'?` terlebih dahulu; PUML tidak mengeceknya.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: 2 kolom sah: `Admin / Petugas` dan `Sistem`.
  - **Alur Aksi & Keputusan**: `start` -> `Membuka halaman Telah Dipinjam (tab Dipinjam)` -> (Sistem) `Menampilkan daftar peminjaman aktif beserta sisa waktu / keterlambatan` -> (Admin) `Menerima buku dari anggota` -> `Menekan tombol Kembalikan pada peminjaman terkait` -> (Sistem) Diamond 1 `Status masih 'dipinjam'?`:
    - `[Tidak]` -> `Menampilkan pesan buku sudah dikembalikan` -> `stop`.
    - `[Ya]` -> `Mencatat waktu kembali, menghitung hari terlambat & denda (tarif × hari, setelah masa toleransi)` -> Diamond 2 `Terlambat & denda > 0?`:
      - `[Ya]` -> `Menyimpan status 'dikembalikan' & status denda 'belum_bayar'`
      - `[Tidak]` -> `Menyimpan status 'dikembalikan' & status denda 'tidak_ada'`
      - Keduanya bergabung ke `Menambah stok buku (+1) & menampilkan pesan hasil (beserta nominal denda jika ada)` -> (Admin) Diamond 3 `Ada denda belum dibayar?`:
        - `[Tidak]` -> `stop`
        - `[Ya]` -> `Menerima pembayaran denda & menekan Tandai Lunas` -> (Sistem) `Mengubah status denda menjadi 'lunas' & menampilkan pesan berhasil` -> `stop`.
  - **Visual PNG**: Sangat rapi, simbol `×` dan `+1` tampil baik tanpa mojibake.
- **Daftar Pemeriksaan Elemen & Temuan** *(Total 8 aspek: **5 BENAR**, **1 BENAR DENGAN CATATAN**, **2 SALAH**, **0 TIDAK TERVERIFIKASI**)*:
  1. **Swimlane (`Admin / Petugas` & `Sistem`)** — **BENAR**: Hanya aktor sah (`telah_dipinjam.php:8-11`).
  2. **Langkah Fisik (`Menerima buku dari anggota` Sebelum Klik `Kembalikan`)** — **BENAR**: Urutan fisik sirkulasi sangat logis dan tepat.
  3. **Rumus Perhitungan Denda & Masa Toleransi + Penambahan Stok `+1`** — **BENAR**: Sesuai `telah_dipinjam.php:56-69, 97-111` (`hari = diff->days`, `hari_denda = max(0, hari - grace_period)`, `total = denda_aktif ? hari_denda * denda_per_hari : 0`, `UPDATE buku SET stok = stok + 1`).
  4. **Pengecekan Awal `Status masih 'dipinjam'?`** — **BENAR**: Sesuai `telah_dipinjam.php:92, 120-123`.
  5. **Node Awal/Akhir & Kualitas Visual PNG** — **BENAR**: 1 `start`, 2 `stop`, visual bersih.
  6. **Kondisi Diamond `Terlambat & denda > 0?` Saat Terlambat dalam Masa Toleransi / Denda Nonaktif** — **BENAR DENGAN CATATAN** (`Rendah`): Di `telah_dipinjam.php:98-100`, seorang peminjam bisa saja `terlambat_hari > 0` tetapi `total_denda === 0` (karena masih dalam `grace_period` atau `denda_aktif == 0`), dan percabangan status denda murni ditentukan oleh **`$total_denda > 0 ? 'belum_bayar' : 'tidak_ada'`** (`:100`). Secara logika boolean `Terlambat & denda > 0` tetap bernilai `false` saat `denda == 0`, namun menulis `Total denda > 0?` lebih presisi.
     - *Saran*: Ubah teks diamond ke-2 menjadi `Total denda > 0?`.
  7. **Alur Pelunasan Denda Memaksa Pembayaran Seketika (Tidak Memodelkan Tunggakan `belum_bayar`)** — **SALAH** (`Sedang`): Pada PNG, setelah `status_denda = 'belum_bayar'` tersimpan, alur masuk ke diamond `Ada denda belum dibayar?`: bila `[Ya]`, Admin langsung digambarkan `Menerima pembayaran denda & menekan Tandai Lunas` hingga status menjadi `'lunas'`. Padahal di kode aplikasi (`telah_dipinjam.php:100, 144-153`, `pengaturan_denda.php:115-124, 135`, `dashboard_user.php:61`), peminjam **dapat mengembalikan buku terlebih dahulu tanpa langsung membayar denda saat itu juga**, sehingga denda tercatat sebagai tunggakan `'belum_bayar'` yang baru dilunasi kemudian di tab Dikembalikan/Riwayat atau menu `pengaturan_denda.php`!
     - *Saran*: Ubah diamond ke-3 di swimlane Admin menjadi `Ada denda & dibayar sekarang?` (`[Ya]` -> `Menerima pembayaran denda & menekan Tandai Lunas`; `[Tidak / Belum dibayar]` -> langsung ke `stop` dengan status tetap `'belum_bayar'`).
  8. **Kolom Hantu & Rumus Salah pada Berkas `.puml`** — **SALAH** (`Tinggi`): `06_activity_pengembalian_denda.puml:24-30, 39` menghilangkan `grace_period` & `denda_aktif`, serta menulis kolom/status hantu (`tgl_kembali`, `status='kembali'`, `denda_terbayar`).
     - *Saran*: Tulis ulang `.puml` agar sesuai skema tabel `peminjaman` dan `telah_dipinjam.php`.

---

### 7. Activity Diagram 07 — Tambah Buku (dengan Pencarian ISBN Otomatis)
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Activity Diagram — Tambah Buku (dengan Pencarian ISBN Otomatis)` *(subjudul: `Prasyarat: admin sudah login · file: halaman_admin.php, cari_isbn.php`)*
  - Gambar: `c:\laragon\www\aksanova\UML\Activty\07_activity_tambah_buku_isbn.png`
  - Sumber PUML: `c:\laragon\www\aksanova\UML\Activty\07_activity_tambah_buku_isbn.puml`
- **File PHP Aktual**: `halaman_admin.php`, `cari_isbn.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - **Rincian Perbedaan**:
    1. PNG memiliki **3 swimlane** (`Admin / Petugas`, `Sistem`, dan **`Layanan ISBN (API)`**), sedangkan PUML hanya memiliki **2 swimlane** (`Pustakawan (Admin)` dan `Sistem AKSA NOVA`).
    2. PUML pada judulnya salah merujuk file **`daftar_buku.php & cari_isbn.php`** (`07_activity_tambah_buku_isbn.puml:10`, padahal CRUD buku admin ada di `halaman_admin.php`), serta mengklaim ekstraksi `Penerbit, Tahun` (`:23`) yang tidak pernah diambil oleh `cari_isbn.php:245-257` maupun disimpan di tabel `buku`!
    3. PNG memiliki decision `Judul terisi?` beserta *loop-back* saat simpan buku; PUML tidak memvalidasi judul sama sekali.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: **3 kolom**: `Admin / Petugas`, `Sistem`, dan **`Layanan ISBN (API)`** *(pelanggaran aturan swimlane eksternal!)*.
  - **Alur Aksi & Keputusan**: `start` -> `Membuka halaman Kelola Buku & menekan Tambah Buku` -> (Sistem) `Menampilkan form tambah buku` -> (Admin) `Memasukkan nomor ISBN pada form` -> (Sistem) `Meminta data buku ke layanan ISBN (cari_isbn.php)` -> (**Layanan ISBN (API)**) `Mencari data berdasarkan ISBN (Google Books, cadangan Open Library)` -> (Sistem) Diamond 1 `Data buku ditemukan?`:
    - `[Tidak]` -> `Menampilkan pesan data tidak ditemukan (isi manual)` -> lanjut ke Admin `Melengkapi data...`
    - `[Ya]` -> `Mengisi judul, penulis, genre, sinopsis & mengunduh cover otomatis` -> lanjut ke Admin `Melengkapi data (stok, lokasi rak) lalu menekan Simpan` -> (Sistem) Diamond 2 `Judul terisi?`:
      - `[Tidak]` -> `Menampilkan pesan 'Judul buku tidak boleh kosong'` -> *loop-back* ke `Melengkapi data...`
      - `[Ya]` -> `Menyimpan data buku & cover ke tabel buku` -> `Menampilkan pesan 'Buku berhasil ditambahkan'` -> (Admin) `Melihat buku baru di daftar buku` -> `stop`.
  - **Visual PNG**: Secara grafis bersih, tidak ada teks terpotong atau mojibake.
- **Daftar Pemeriksaan Elemen & Temuan** *(Total 7 aspek: **3 BENAR**, **1 BENAR DENGAN CATATAN**, **3 SALAH**, **0 TIDAK TERVERIFIKASI**)*:
  1. **Swimlane Eksternal Terlarang `Layanan ISBN (API)` pada PNG** — **SALAH** (`Kritis`): PNG membuat swimlane ke-3 bernama `Layanan ISBN (API)` untuk Google Books & Open Library. Sesuai kaidah audit & fakta arsitektur (`cari_isbn.php:237-371` hanyalah pemanggilan HTTP cURL/server-side dari PHP), layanan API eksternal **bukan aktor** dan dilarang menjadi swimlane terpisah.
     - *Saran*: Hapus swimlane `Layanan ISBN (API)` dan pindahkan langkah pencarian API ke dalam swimlane `Sistem`.
  2. **Penyederhanaan Berlebihan Pencarian ISBN (Tidak Mencerminkan 4 Tahap + Validasi 10/13 Digit + Cek Duplikat Lokal)** — **SALAH** (`Sedang`): Kotak di PNG hanya menulis `(Google Books, cadangan Open Library)`, melewatkan fakta kode di `cari_isbn.php`:
     - Validasi panjang wajib **10 atau 13 digit** (`cari_isbn.php:207-212`, `halaman_admin.php:1639-1643`),
     - Pengecekan peringatan duplikat ISBN lokal di tabel `buku` (`cari_isbn.php:217-226`, `halaman_admin.php:1707-1711`),
     - Pengayaan progresif **4 tahap** (bukan sekadar fallback bila gagal total, tetapi melengkapi kolom yang masih kosong tanpa menimpa): **(1) Google Books API** (`:237-258`) -> **(2) Open Library Books API `bibkeys`** (`:267-304`) -> **(3) Open Library `search.json`** (`:312-337`) -> **(4) Open Library Edition `/isbn/{isbn}.json` + Work `/works/{key}.json`** khusus `genre` & `sinopsis` (`:351-371`).
     - *Saran*: Rinci aksi di swimlane `Sistem`: validasi ISBN 10/13 digit, cek duplikat lokal, dan pencarian progresif 4 tahap (Google Books + 3 endpoint Open Library) + unduh sampul lokal (`>= 500 B`).
  3. **Opsi Tambah Buku Tanpa ISBN & Unggah/Kamera Sampul Manual** — **BENAR DENGAN CATATAN** (`Rendah`): Pada PNG, mengisi ISBN digambar wajib dilewati sebelum mengisi form, dan unggah sampul manual/kamera (`halaman_admin.php:38-67, 97-98`, maks 5 MB `jpg/jpeg/png/webp/gif`) serta aturan *auto-fill* hanya pada kolom yang masih kosong (`halaman_admin.php:1691-1694`) belum disebutkan.
     - *Saran*: Beri percabangan opsional `Gunakan pencarian ISBN?` dan sebutkan pengisian hanya pada kolom kosong serta opsi unggah sampul manual/kamera.
  4. **Validasi `Judul terisi?` & Pesan `'Judul buku tidak boleh kosong'`** — **BENAR**: Sesuai eksak dengan `halaman_admin.php:91-92` beserta *loop-back*.
  5. **Penyimpanan ke Tabel `buku` & Pesan `'Buku berhasil ditambahkan'`** — **BENAR**: Sesuai `halaman_admin.php:100-104`.
  6. **Node Awal/Akhir & Visual PNG** — **BENAR**: 1 `start`, 1 `stop`, visual rapi.
  7. **Kesalahan Nama File & Kolom Hantu di `.puml`** — **SALAH** (`Tinggi`): `07_activity_tambah_buku_isbn.puml:10, 23` merujuk file yang salah (`daftar_buku.php` milik tamu/anggota, seharusnya `halaman_admin.php`) dan mengklaim ekstraksi `Penerbit, Tahun` yang tidak ada di kode/DB.
     - *Saran*: Perbaiki `.puml` menjadi `halaman_admin.php & cari_isbn.php`, hapus `Penerbit, Tahun`, dan tambahkan validasi judul serta 4 tahap pencarian ISBN.

---

### 8. Activity Diagram 08 — Lupa Kartu / Sandi dengan Verifikasi OTP (`A08_Lupa_Kartu_OTP.png`)
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Activity Diagram — Lupa Kartu / Sandi dengan Verifikasi OTP` *(subjudul: `lupa_kartu.php · includes/otp.php · includes/mailer.php`)*
  - Gambar: `c:\laragon\www\aksanova\UML\Activty\Lengkap\A08_Lupa_Kartu_OTP.png`
  - Sumber PUML: **TIDAK ADA** *(tidak terdapat berkas `.puml` pendamping di folder `Lengkap`)*
- **File PHP Aktual**: `lupa_kartu.php`, `includes/otp.php`, `includes/mailer.php`, `kartu_anggota.php`, `barcode128.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK / TIDAK ADA PUML** (`A08_Lupa_Kartu_OTP.png` berdiri sendiri tanpa berkas `.puml`).
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: 2 kolom sah: `Tamu (Guest)` dan `Sistem` (tanpa swimlane SMTP eksternal).
  - **Alur Aksi & Keputusan**:
    - **Langkah 1**: `start` -> `Membuka halaman lupa_kartu.php` -> (Sistem) `Menampilkan form email + token CSRF` -> (Tamu) `Mengisi email terdaftar lalu klik Kirim Kode OTP` -> (Sistem) `Memverifikasi CSRF dan menghitung permintaan OTP (batas 5/jam per email, 10/jam per IP, jeda 60 detik)` -> Diamond 1 `CSRF valid dan di bawah batas permintaan?` (`[Tidak]` -> `Pesan: sesi tidak valid atau terlalu sering meminta kode` -> *loop-back* ke `Mengisi email...`; `[Ya]` -> `Mencari user: email cocok, role member, status approved` -> Diamond 2 `Email terdaftar?`:
      - `[Tidak]` -> `Tidak ada OTP dibuat (respons tetap generik)` -> gabung ke `Pesan generik: jika email terdaftar, kode OTP telah dikirim. Tampilkan form 6 digit + hitung mundur 10 menit`.
      - `[Ya]` -> `Membatalkan OTP lama, membuat OTP 6 digit (random_int), menyimpan HMAC-SHA256, berlaku 10 menit` -> `Mengirim email OTP lewat SMTP (PHPMailer)` -> Diamond 3 `Email terkirim?` (`[Tidak]` -> `Menghapus OTP baru, pesan: kode belum dapat dikirim` -> *loop-back* ke `Mengisi email...`; `[Ya]` -> `Menyimpan sesi lupa_kartu (step 2, user_id)` -> gabung ke `Pesan generik...`)).
    - **Langkah 2**: (Tamu) `Membuka email, memasukkan 6 digit OTP, klik Verifikasi Kode` -> (Sistem) `Memverifikasi CSRF, mengambil OTP aktif (used_at kosong), membandingkan dengan hash_equals` -> Diamond 4 `OTP aktif, belum lewat 10 menit, dan cocok?`:
      - `[Tidak]` -> `attempts + 1 jika kode salah, tampilkan pesan salah atau kedaluwarsa` -> Diamond 5 `Percobaan salah >= 5 atau OTP kedaluwarsa?` (`[Tidak]` -> *loop-back* ke `Membuka email, memasukkan 6 digit OTP...`; `[Ya]` -> `OTP hangus, pesan: minta kode baru` -> *loop-back* ke Langkah 1 `Mengisi email terdaftar...`).
      - `[Ya]` -> `Menandai OTP terpakai (used_at), membekukan kartu lama (frozen), session_regenerate_id, catat otp_verified_at (step 3)` -> `Menampilkan form sandi baru`.
    - **Langkah 3**: (Tamu) `Mengisi sandi baru dan konfirmasi, klik Simpan` -> (Sistem) `Memverifikasi CSRF, umur verifikasi <= 10 menit, validasi sandi (>= 8, huruf + angka, sama dengan konfirmasi)` -> Diamond 6 `Verifikasi masih berlaku dan sandi valid?`:
      - `[Tidak]` -> `Pesan kesalahan (sandi tidak valid atau verifikasi kedaluwarsa)` -> *loop-back* ke Langkah 3 `Mengisi sandi baru dan konfirmasi, klik Simpan`.
      - `[Ya]` -> `Meng-hash sandi, UPDATE password, plain_password = '', card_status = active'` -> `Menyimpan kartu_data di sesi, menghapus sesi lupa_kartu, redirect ke kartu_anggota.php` -> (Tamu) **`Melihat dan mengunduh kartu pengganti (QR code)`** -> `stop`.
    - **Kotak Biru Mengambang di Kanan Bawah**: `Aksi Batal (langkah 2 atau 3): kartu dicairkan kembali (card_status = 'active') bila sudah dibekukan, sesi lupa_kartu dihapus, kembali ke sign_in.php. Kirim ulang kode = mengulang dari mengisi email (jeda 60 detik).`
  - **Visual PNG**: Resolusi tinggi dan terbaca jelas, namun terdapat **cacat teks tanda kutip ganjil** `card_status = active'` pada kotak kedua terakhir di Sistem serta **kotak catatan biru menggantung tanpa panah**.
- **Daftar Pemeriksaan Elemen & Temuan** *(Total 11 aspek: **4 BENAR**, **2 BENAR DENGAN CATATAN**, **5 SALAH**, **0 TIDAK TERVERIFIKASI**)*:
  1. **Swimlane (`Tamu (Guest)` & `Sistem`, Tanpa Swimlane SMTP)** — **BENAR**: PHPMailer SMTP dimodelkan dengan tepat sebagai aktivitas internal Sistem (`includes/mailer.php:47-86`).
  2. **Parameter Inti OTP (6 Digit, HMAC-SHA256, Berlaku 10 Menit, Maks 5 Percobaan, Cooldown 60 Detik, 5/jam/user, 10/jam/IP, Rollback DELETE Saat SMTP Gagal)** — **BENAR**: Sesuai `includes/otp.php:41, 66, 77, 113-119, 308-352` dan `includes/mailer.php:119-127`.
  3. **Proteksi Anti-Enumerasi Email & Waktu Pembekuan Kartu (`frozen` SETELAH OTP Valid)** — **BENAR**: Sesuai `lupa_kartu.php:116, 136-150` (sesi dummy generik) dan `lupa_kartu.php:182-190` (`session_regenerate_id(true)` lalu `UPDATE users SET card_status='frozen'` baru dijalankan setelah `otp_verifikasi()` sukses).
  4. **Node Awal/Akhir & Kelengkapan Label Cabang 6 Diamond** — **BENAR**: Seluruh 6 diamond memiliki label `Ya`/`Tidak`.
  5. **Klaim Kartu Pengganti Memakai `(QR code)` pada Kotak Aksi Terakhir** — **SALAH** (`Kritis`): Kotak aksi terakhir di swimlane `Tamu (Guest)` menulis **`Melihat dan mengunduh kartu pengganti (QR code)`**. Faktanya, `kartu_anggota.php:108, 696` merender **Barcode Code 128 Subset B SVG** (`barcode128.php:5-71`) dan sama sekali tidak memakai QR code!
     - *Saran*: Ganti `(QR code)` menjadi `(Barcode Code 128)`.
  6. **Alur Cabang Kedaluwarsa Sesi Langkah 3 (`> 10 menit`) Salah Loop-back & Tidak Mencairkan Kartu** — **SALAH** (`Tinggi`): Pada Langkah 3, cabang `[Tidak]` dari `Verifikasi masih berlaku dan sandi valid?` digabung menjadi `Pesan kesalahan (sandi tidak valid atau verifikasi kedaluwarsa)` lalu **loop-back ke Langkah 3 (`Mengisi sandi baru...`)**. Padahal di `lupa_kartu.php:262-274`, jika verifikasi melewati 600 detik (10 menit), sistem **mencairkan kembali kartu (`UPDATE users SET card_status='active'`), menghapus `$_SESSION['lupa_kartu']`, dan me-redirect ke `lupa_kartu.php?expired=1` (kembali ke Langkah 1 input email!)**. Hanya jika sandi tidak valid dalam batas `<= 10 menit` (`:279-285`) pengguna tetap di Langkah 3!
     - *Saran*: Pisahkan diamond `Sesi verifikasi <= 10 menit?` (`[Tidak]` -> `Cairkan kartu ('active'), hapus sesi, tampilkan pesan kedaluwarsa` -> *loop-back* ke Langkah 1 `Mengisi email...`) dari diamond `Sandi baru valid?` (`[Tidak]` -> *loop-back* ke Langkah 3 `Mengisi sandi baru...`).
  7. **Catatan Teknis Mengambang & Klaim Salah tentang `Kirim ulang kode`** — **SALAH** (`Sedang`): Terdapat kotak biru mengambang tanpa aliran panah di kanan bawah, dan kalimat keduanya menulis **`Kirim ulang kode = mengulang dari mengisi email (jeda 60 detik)`**. Faktanya, di `lupa_kartu.php:207-247` (`action === 'resend_otp'`), tombol **Kirim Ulang Kode** dieksekusi langsung di **Langkah 2** menggunakan `$_SESSION['lupa_kartu']['email']` **tanpa kembali mengisi email di Langkah 1** (kecuali pengguna menekan tombol terpisah `Ganti Email` / `action === 'change_email'` di `:62-66`).
     - *Saran*: Hapus kotak catatan biru mengambang; modelkan aksi `Kirim Ulang OTP` (tetap di Langkah 2 dengan cek jeda 60s) dan `Batalkan` (`card_status = 'active'` -> `sign_in.php`) sebagai percabangan aktivitas UML resmi, atau koreksi faktanya.
  8. **Loop-back Saat OTP Hangus (`>= 5` salah) atau Kedaluwarsa Mengarah ke Langkah 1** — **SALAH** (`Sedang`): Pada PNG, saat `Percobaan salah >= 5 atau OTP kedaluwarsa? -> Ya`, panah *loop-back* ditarik kembali ke **Langkah 1 (`Mengisi email terdaftar...`)**. Di kode aktual (`lupa_kartu.php:178-201`), ketika `otp_verifikasi()` gagal karena `burned` atau `expired`, sistem hanya mengisi `$error = $verif['message']` dan **tetap berada di Langkah 2** agar pengguna dapat langsung menekan `Kirim Ulang Kode OTP` (`resend_otp`) di halaman yang sama tanpa mengetik ulang email.
     - *Saran*: Arahkan cabang OTP hangus/kedaluwarsa ke aksi pengguna di Langkah 2 (`Klik Kirim Ulang Kode OTP` setelah jeda 60 detik atau `Ganti Email`).
  9. **Tidak Ada Berkas Sumber `.puml` & Penamaan File Tidak Konsisten (`A08_...`)** — **SALAH** (`Sedang`): Satu-satunya diagram dari 16 diagram yang tidak memiliki berkas `.puml` dan memakai pola nama `A08_Lupa_Kartu_OTP.png` (berbeda dari `activity_09_...` s/d `activity_16_...`).
     - *Saran*: Buat berkas sumber `.puml` yang sinkron dan seragamkan penamaan berkas bila diperlukan.
  10. **Urutan Pengecekan Rate-Limit Per User vs Pencarian Email** — **BENAR DENGAN CATATAN** (`Rendah`): Di PNG, `batas 5/jam per email, jeda 60 detik` ditulis sebelum `Mencari user`. Di `lupa_kartu.php:79-108`, sebelum mencari user sistem hanya mengecek format email (`:75`) dan batas IP `10/jam` (`:79`), baru setelah user ditemukan layak (`member` + `approved`) sistem mengecek `5/jam per user` & `jeda 60 detik` (`:105`).
  11. **Typo Tanda Kutip pada Teks PNG (`card_status = active'`)** — **BENAR DENGAN CATATAN** (`Rendah`): Kurang tanda kutip pembuka sebelum `active'` pada kotak `Meng-hash sandi, UPDATE password, plain_password = '', card_status = active'`.
     - *Saran*: Perbaiki penulisan menjadi `card_status = 'active'`.

---

### 9. Activity Diagram 09 — Edit Data Kartu Anggota
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Activity Diagram — Edit Data Kartu Anggota` *(subjudul: `Prasyarat: anggota sudah login · file: edit_kartu.php`)*
  - Gambar: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_09_edit_kartu_anggota.png`
  - Sumber PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_09_edit_kartu_anggota.puml`
- **File PHP Aktual**: `edit_kartu.php`, `kartu_anggota.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - **Rincian Perbedaan**: `.puml` hanyalah templat generik 37 baris yang merujuk pada file hantu **`member/profil.php`** (`activity_09_edit_kartu_anggota.puml:10, 23`) tanpa detail field, blokir kartu beku, hapus foto lama, maupun redirect ke `kartu_anggota.php`.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: 2 kolom sah: `Anggota` dan `Sistem`.
  - **Alur Aksi & Keputusan**: `start` -> `Membuka halaman Edit Kartu` -> (Sistem) `Memeriksa sesi login anggota & status akun (pending / ditolak / kartu dibekukan)` -> Diamond 1 `Akun boleh mengedit data?`:
    - `[Tidak]` -> `Menampilkan pesan akses diblokir` -> `stop`.
    - `[Ya]` -> `Menampilkan form data terbaru (nama, kelas, email, no. HP, foto, sandi opsional)` -> (Anggota) `Mengubah data lalu menekan Simpan` -> (Sistem) `Memvalidasi nama, kelas, email sekolah & unik, no. HP, sandi baru (opsional), foto (JPG/PNG/WEBP ≤ 5 MB)` -> Diamond 2 `Data valid?`:
      - `[Tidak]` -> `Menampilkan pesan kesalahan` -> *loop-back* ke `Mengubah data lalu menekan Simpan`.
      - `[Ya]` -> `Menyimpan foto baru (hapus foto lama) & memperbarui data users (+ hash sandi bila diganti)` -> `Menampilkan kartu anggota yang diperbarui` -> (Anggota) `Melihat kartu anggota terbaru` -> `stop`.
  - **Visual PNG**: Bersih, simbol `≤` tampil baik tanpa mojibake.
- **Daftar Pemeriksaan Elemen & Temuan** *(Total 7 aspek: **4 BENAR**, **1 BENAR DENGAN CATATAN**, **2 SALAH**, **0 TIDAK TERVERIFIKASI**)*:
  1. **Swimlane (`Anggota` & `Sistem`)** — **BENAR**: Hanya aktor sah (`edit_kartu.php:12-19`).
  2. **Blokir Form bagi Akun `pending` / `rejected` / Kartu `frozen`** — **BENAR DENGAN CATATAN** (`Rendah`): Sesuai `edit_kartu.php:43-49` (menampilkan `$blocked`), namun bila belum login (`:12-15`) atau `role !== 'member'` (`:16-19`) atau baris user terhapus (`:34-38`), sistem melakukan **redirect** ke `sign_in.php` / `dashboard.php` (bukan menampilkan pesan blokir di halaman).
  3. **Hapus Foto Lama (`@unlink`), Update `users`, & Redirect ke `kartu_anggota.php`** — **BENAR**: Sesuai `edit_kartu.php:246-289`.
  4. **Decision Diamonds, Label Cabang, & Loop-back Validasi** — **BENAR**: Lengkap dan tepat.
  5. **Node Awal/Akhir & Visual PNG** — **BENAR**: 1 `start`, 2 `stop`, visual bersih.
  6. **Daftar Field Form & Aturan Validasi Salah (`kelas` Diklaim Bisa Diedit, Sedangkan `username`, `NIS/NIP`, Domain `@gmail.com`, & Verifikasi `old_password` Dihilangkan)** — **SALAH** (`Tinggi`):
     - PNG menulis form menampilkan & memvalidasi **`kelas`**, padahal di `edit_kartu.php:72, 866-920` **tidak ada input `kelas`** (`$new_kelas = $user["kelas"] ?? ""` tetap dari DB)!
     - Sebaliknya, PNG **menghilangkan 2 field wajib utama** yang diedit dan dicek keunikannya (`AND id <> ?`) di `edit_kartu.php`: **`username`** (`:70, 103-106, 150-157`) dan **`nik` (NIS Siswa / NIP Guru)** (`:71, 107-112, 162-170`)!
     - PNG menulis **`email sekolah & unik`**, padahal `edit_kartu.php:116-122` juga mengizinkan **`@gmail.com`** selain 2 domain sekolah.
     - Saat mengganti sandi opsional, `edit_kartu.php:131-145` **wajib memverifikasi `old_password` (kata sandi saat ini)** dan memastikan sandi baru berbeda dari sandi lama (belum disebut di PNG, bersama `csrf_verify()` `:97`).
     - *Saran*: Ganti `kelas` dengan `username & NIS/NIP (unik)`, ubah `email sekolah & unik` menjadi `email (sekolah/Gmail & unik)`, dan tambahkan `verifikasi sandi lama bila ganti sandi`.
  7. **Berkas Sumber `.puml` Berupa Stub Generik dengan File Hantu `member/profil.php`** — **SALAH** (`Tinggi`): `activity_09_edit_kartu_anggota.puml:10, 23` merujuk modul fiktif `member/profil.php`.
     - *Saran*: Tulis ulang `.puml` sesuai `edit_kartu.php` dan `.png`.

---

### 10. Activity Diagram 10 — Suka (Like) & Simpan Buku (Favorit)
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Activity Diagram — Suka (Like) & Simpan Buku (Favorit)` *(subjudul: `Prasyarat: anggota sudah login · file: like_handler.php, buku_simpan.php`)*
  - Gambar: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_10_like_simpan_buku.png`
  - Sumber PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_10_like_simpan_buku.puml`
- **File PHP Aktual**: `like_handler.php`, `buku_simpan.php` *(dipanggil via AJAX dari `beranda.php`, `daftar_buku.php`, `buku_simpan.php`)*
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - **Rincian Perbedaan**: `.puml` adalah *stub* generik yang merujuk file hantu **`api/like.php & api/favorite.php`** (`activity_10_like_simpan_buku.puml:10, 23`) dan **mengklaim `Validasi ... token keamanan CSRF`** (`:21`), padahal `like_handler.php` **TIDAK memiliki `csrf_verify()`**!
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: 2 kolom sah: `Anggota` dan `Sistem`.
  - **Alur Aksi & Keputusan**: `start` -> `Membuka katalog buku atau halaman detail buku` -> `Menekan ikon Suka atau Simpan pada sebuah buku` -> (Sistem) `Menerima permintaan (AJAX) ke like_handler.php` -> Diamond 1 `Sudah login?` (`[Tidak]` -> kotak tolak) -> `[Ya]` -> Diamond 2 `Bukan admin?` (`[Tidak]` -> kotak tolak) -> `[Ya]` -> Diamond 3 `Data valid (ID buku & jenis aksi)?` (`[Tidak]` -> `Menolak permintaan & mengirim pesan (login dulu / admin tidak perlu fitur ini / data tidak valid)` -> `stop`) -> `[Ya]` -> Diamond 4 `Data suka/simpan sudah ada?`:
    - `[Ya]` -> `Menghapus data (batal suka / simpan)`
    - `[Tidak]` -> `Menambah data (suka / simpan)`
    - Keduanya bergabung ke `Menghitung total & mengirim hasil (status aktif & total) dalam JSON` -> (Anggota) `Melihat ikon & jumlah suka/simpan diperbarui` -> `Melihat daftar buku tersimpan di halaman Buku Simpan` -> `stop`.
  - **Visual PNG**: Bersih, seluruh 4 diamond memiliki label `Ya`/`Tidak` lengkap, tidak ada distorsi.
- **Daftar Pemeriksaan Elemen & Temuan** *(Total 6 aspek: **4 BENAR**, **1 BENAR DENGAN CATATAN**, **1 SALAH**, **0 TIDAK TERVERIFIKASI**)*:
  1. **Swimlane (`Anggota` & `Sistem`)** — **BENAR**: Hanya aktor sah.
  2. **Urutan 3 Guard (`Sudah login?`, `Bukan admin?`, `Data valid?`) & Pesan Penolakan di `like_handler.php`** — **BENAR**: Sesuai eksak `like_handler.php:7-10` (`"Login dulu"`), `:13-16` (`"Admin tidak perlu fitur ini"`), dan `:24-27` (`"Data tidak valid"`), serta **tidak mengklaim CSRF** (sesuai fakta kode bahwa `like_handler.php` memang tanpa CSRF).
  3. **Logika Toggle `DELETE` / `INSERT` pada `buku_likes` / `buku_favorites` & Respons JSON (`aktif`, `total`)** — **BENAR**: Sesuai eksak `like_handler.php:29-48`.
  4. **Node Awal/Akhir & Visual PNG** — **BENAR**: 1 `start`, 2 `stop`, visual rapi.
  5. **Langkah Akhir `Melihat daftar buku tersimpan di halaman Buku Simpan` Digambar Wajib Berurutan** — **BENAR DENGAN CATATAN** (`Rendah`): Kotak terakhir di swimlane Anggota (`Melihat daftar buku tersimpan di halaman Buku Simpan` / `buku_simpan.php`) digambar selalu dieksekusi setelah `Melihat ikon & jumlah suka/simpan diperbarui`, padahal aksi di diagram ini mencakup **Suka (`like`)** maupun **Batal Simpan**, dan membuka `buku_simpan.php` bersifat opsional/terpisah. Selain itu, detail buku di katalog berupa *modal* (bukan halaman tersendiri).
     - *Saran*: Tandai kotak terakhir sebagai opsional khusus aksi Simpan (`(Opsional) Membuka halaman Buku Simpan untuk melihat koleksi favorit`) atau akhiri alur toggle setelah UI ikon diperbarui.
  6. **Berkas Sumber `.puml` Merujuk File Hantu `api/like.php & api/favorite.php` & Mengklaim CSRF Palsu** — **SALAH** (`Tinggi`): `activity_10_like_simpan_buku.puml:10, 21, 23` salah nama file dan mengklaim validasi CSRF yang tidak ada di `like_handler.php`.
     - *Saran*: Tulis ulang `.puml` mengikuti `like_handler.php` dan `.png`.

---

### 11. Activity Diagram 11 — Memberi Rating Buku
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Activity Diagram — Memberi Rating Buku` *(subjudul: `Prasyarat: anggota sudah login · file: rating_handler.php`)*
  - Gambar: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_11_rating_buku.png`
  - Sumber PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_11_rating_buku.puml`
- **File PHP Aktual**: `rating_handler.php` *(dipanggil dari modal detail buku di `beranda.php`, `daftar_buku.php`, `buku_simpan.php`)*
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - **Rincian Perbedaan**: `.puml` adalah *stub* generik yang merujuk file hantu **`api/rate.php`** (`activity_11_rating_buku.puml:10, 23`) dan **mengklaim `Validasi ... token keamanan CSRF`** (`:21`), padahal `rating_handler.php` **TIDAK memiliki `csrf_verify()`**!
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: 2 kolom sah: `Anggota` dan `Sistem`.
  - **Alur Aksi & Keputusan**: `start` -> `Membuka halaman detail buku` -> `Memilih jumlah bintang (1–5)` -> (Sistem) `Menerima rating (POST) ke rating_handler.php` -> Diamond 1 `Sudah login & bukan admin?` (`[Tidak]` -> kotak tolak) -> `[Ya]` -> Diamond 2 `Rating 1–5 & ID buku valid?` (`[Tidak]` -> `Menolak permintaan & mengirim pesan (tidak berwenang / data tidak valid)` -> `stop`) -> `[Ya]` -> Diamond 3 `Pengguna sudah pernah memberi rating buku ini?`:
    - `[Ya]` -> `Memperbarui rating (UPDATE)`
    - `[Tidak]` -> `Menyimpan rating baru (INSERT)`
    - Keduanya bergabung ke `Menghitung rata-rata & jumlah rating terbaru lalu mengirim hasil (JSON)` -> (Anggota) `Melihat bintang pilihan & rata-rata rating diperbarui` -> `stop`.
  - **Visual PNG**: Sangat bersih, proporsional, tidak ada cacat visual.
- **Daftar Pemeriksaan Elemen & Temuan** *(Total 6 aspek: **4 BENAR**, **1 BENAR DENGAN CATATAN**, **1 SALAH**, **0 TIDAK TERVERIFIKASI**)*:
  1. **Swimlane (`Anggota` & `Sistem`)** — **BENAR**: Hanya aktor sah.
  2. **Validasi Sesi/Role (`HTTP 403`) & Rentang Skor `1–5` (Tanpa Komentar Teks, Tanpa CSRF)** — **BENAR**: Sesuai eksak `rating_handler.php:6-17, 48-53`.
  3. **Logika Upsert (`SELECT` -> `UPDATE` vs `INSERT` ke `buku_ratings`) & Hitung `ROUND(AVG(rating), 1)` + `COUNT(*)`** — **BENAR**: Sesuai eksak `rating_handler.php:56-73`.
  4. **Node Awal/Akhir & Visual PNG** — **BENAR**: 1 `start`, 2 `stop`, seluruh 3 diamond berlabel `Ya`/`Tidak`.
  5. **Istilah `Membuka halaman detail buku`** — **BENAR DENGAN CATATAN** (`Rendah`): Di AKSA NOVA tidak ada halaman detail buku berdiri sendiri; pemilihan bintang rating berada di dalam **modal detail buku** pada `beranda.php:2728`, `daftar_buku.php:1497`, dan `buku_simpan.php:1086`.
     - *Saran*: Ubah teks kotak pertama menjadi `Membuka modal detail buku di katalog`.
  6. **Berkas Sumber `.puml` Merujuk File Hantu `api/rate.php` & Mengklaim CSRF Palsu** — **SALAH** (`Tinggi`): `activity_11_rating_buku.puml:10, 21, 23` salah nama file dan mengklaim validasi CSRF yang tidak ada di `rating_handler.php`.
     - *Saran*: Tulis ulang `.puml` mengikuti `rating_handler.php` dan `.png`.

---

### 12. Activity Diagram 12 — Menelusuri Katalog & Melihat Detail Buku
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Activity Diagram — Menelusuri Katalog & Melihat Detail Buku` *(subjudul: `Tamu, anggota, maupun admin · file: daftar_buku.php, buku_detail.php`)*
  - Gambar: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_12_telusuri_katalog_detail.png`
  - Sumber PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_12_telusuri_katalog_detail.puml`
- **File PHP Aktual**: `daftar_buku.php`, `buku_detail.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - **Rincian Perbedaan**: `.puml` adalah *stub* generik yang merujuk file hantu **`katalog.php & detail_buku.php`** (`activity_12_telusuri_katalog_detail.puml:10, 23`), mengklaim validasi CSRF (`:21`), dan mengklaim operasi tulis DB (`:24`) pada fitur katalog baca-saja!
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: 2 kolom: `Pengguna` dan `Sistem`.
  - **Alur Aksi & Keputusan**: `start` -> `Membuka halaman Daftar Buku` -> (Sistem) `Menampilkan buku (10 per halaman) beserta daftar genre, suka/simpan & rating` -> (Pengguna) `Mengetik kata kunci (judul / penulis / ISBN), memilih genre, atau pindah halaman` -> (Sistem) `Menyaring buku sesuai kata kunci & genre, lalu menghitung halaman` -> Diamond 1 `Ada buku yang cocok?`:
    - `[Tidak]` -> `Menampilkan pesan buku tidak ditemukan` -> *loop-back* ke `Mengetik kata kunci...`
    - `[Ya]` -> `Menampilkan daftar buku hasil penelusuran` -> (Pengguna) `Memilih sebuah buku untuk melihat detail` -> (Sistem) `Mengambil data buku, jumlah suka & simpan, rating (serta status milik pengguna bila login)` -> Diamond 2 `Buku ditemukan?`:
      - `[Tidak]` -> `Menampilkan pesan buku tidak ditemukan (404)` -> `stop`
      - `[Ya]` -> `Menampilkan detail buku (judul, penulis, ISBN, genre, sinopsis, stok, rak)` -> (Pengguna) `Melihat detail buku` -> `stop`.
  - **Visual PNG**: Bersih, proporsional, tidak ada cacat visual.
- **Daftar Pemeriksaan Elemen & Temuan** *(Total 7 aspek: **3 BENAR**, **2 BENAR DENGAN CATATAN**, **2 SALAH**, **0 TIDAK TERVERIFIKASI**)*:
  1. **Paginasi `10` Buku/Halaman, Filter `judul`/`penulis`/`isbn` & `genre`, serta Agregat Suka/Simpan/Rating** — **BENAR**: Sesuai eksak `daftar_buku.php:34, 51, 55, 61-114`.
  2. **Data Detail Buku (`judul, penulis, ISBN, genre, sinopsis, stok, rak` + Status Interaksi User)** — **BENAR**: Sesuai `buku_detail.php:67-131`.
  3. **Node Awal/Akhir, Decision Diamonds, & Visual PNG** — **BENAR**: 1 `start`, 2 `stop`, label `Ya`/`Tidak` lengkap.
  4. **Klaim Aktor `admin` Dapat Membuka `daftar_buku.php` pada Subjudul & Alur PNG** — **SALAH** (`Sedang`): Subjudul PNG menulis **`Tamu, anggota, maupun admin · file: daftar_buku.php, buku_detail.php`** dengan langkah pertama `Membuka halaman Daftar Buku`. Faktanya, di `daftar_buku.php:6-9`, **Admin DILARANG mengakses `daftar_buku.php`** dan langsung di-redirect ke `dashboard.php` (`if ($_SESSION["role"] === "admin") { header("Location: dashboard.php"); exit; }`)! Selain itu, Anggota yang berstatus non-`approved` atau `card_status === 'frozen'` dihancurkan sesinya dan diarahkan ke `sign_in.php` (`daftar_buku.php:18-24`). Admin hanya memanggil `buku_detail.php` dari `halaman_admin.php:1283`, bukan dari `daftar_buku.php`.
     - *Saran*: Ubah subjudul & swimlane aktor menjadi `Tamu / Anggota` (bukan Admin), dan tambahkan catatan guard redirect bila Admin atau Anggota berstatus beku/ditolak mengakses `daftar_buku.php`.
  5. **Urutan Query di `buku_detail.php` & Validasi `unmask_id` (`HTTP 400`)** — **BENAR DENGAN CATATAN** (`Rendah`): Di PNG, kotak `Mengambil data buku, jumlah suka & simpan, rating...` ditempatkan **sebelum** diamond `Buku ditemukan?`. Di `buku_detail.php:34-40, 67-128`, sistem memvalidasi `unmask_id($_GET['id'])` dulu (jika gagal -> HTTP `400` `'ID tidak valid'`), lalu mengambil `SELECT * FROM buku WHERE id = ? LIMIT 1` dan mengecek `Buku ditemukan?` (`:69-71` -> HTTP `404`) **sebelum** menghitung `buku_likes`, `buku_favorites`, dan `buku_ratings` (`:74-128`).
     - *Saran*: Pindahkan perhitungan jumlah suka, simpan, & rating ke bawah cabang `Buku ditemukan? -> Ya`.
  6. **Pencarian/Filter Digambar Wajib Sebelum Memilih Buku** — **BENAR DENGAN CATATAN** (`Rendah`): Pada PNG, pengguna harus melewati `Mengetik kata kunci...` sebelum `Memilih sebuah buku untuk melihat detail`, padahal pengguna dapat langsung mengklik buku dari daftar awal 10 buku pertama.
  7. **Berkas Sumber `.puml` Merujuk File Hantu `katalog.php & detail_buku.php` & Klaim CSRF/Tulis DB** — **SALAH** (`Tinggi`): `activity_12_telusuri_katalog_detail.puml:10, 21-24` sepenuhnya tidak sesuai kode nyata.
     - *Saran*: Tulis ulang `.puml` sesuai `daftar_buku.php` & `buku_detail.php`.

---

### 13. Activity Diagram 13 — Melihat Dashboard Anggota
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Activity Diagram — Melihat Dashboard Anggota` *(subjudul: `Prasyarat: anggota sudah login · file: dashboard_user.php`)*
  - Gambar: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_13_dashboard_anggota.png`
  - Sumber PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_13_dashboard_anggota.puml`
- **File PHP Aktual**: `dashboard_user.php`, `barcode128.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - **Rincian Perbedaan**: `.puml` adalah *stub* generik yang merujuk file hantu **`member/dashboard.php`** (`activity_13_dashboard_anggota.puml:10, 23`) serta mengklaim pengisian formulir, validasi CSRF, dan operasi tulis DB pada halaman dashboard baca-saja!
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: 2 kolom sah: `Anggota` dan `Sistem`.
  - **Alur Aksi & Keputusan**: `start` -> `Membuka halaman Dashboard` -> (Sistem) `Memeriksa sesi login & role pengguna` -> Diamond `Sudah login sebagai anggota?`:
    - `[Tidak]` -> `Mengalihkan ke Sign In (belum login) atau halaman admin (role admin)` -> `stop`.
    - `[Ya]` -> `Mengambil profil, total peminjaman & total denda belum dibayar` -> `Mengambil peminjaman aktif, 6 riwayat pengembalian, 5 pengajuan terakhir & 4 buku simpan` -> `Menampilkan Dashboard (profil, statistik, denda, riwayat & status pengajuan)` -> (Anggota) `Melihat riwayat peminjaman, denda & status pengajuan` -> `stop`.
  - **Visual PNG**: Bersih, proporsional, tidak ada cacat visual.
- **Daftar Pemeriksaan Elemen & Temuan** *(Total 6 aspek: **3 BENAR**, **2 BENAR DENGAN CATATAN**, **1 SALAH**, **0 TIDAK TERVERIFIKASI**)*:
  1. **Swimlane (`Anggota` & `Sistem`)** — **BENAR**: Hanya aktor sah.
  2. **Angka Kuantitas Query (`6` Riwayat Pengembalian, `5` Pengajuan Terakhir, `4` Buku Simpan) & Total Denda `belum_bayar`** — **BENAR**: Sesuai eksak `dashboard_user.php:61` (`status_denda = 'belum_bayar'`), `:113` (`LIMIT 6`), `:125` (`LIMIT 5`), dan `:137` (`LIMIT 4`).
  3. **Node Awal/Akhir & Visual PNG** — **BENAR**: 1 `start`, 2 `stop`, diamond berlabel `Ya`/`Tidak`.
  4. **Guard Keaktifan Akun (`status === 'approved'`) & Kartu Tidak Beku (`card_status !== 'frozen'`) Belum Disebut** — **BENAR DENGAN CATATAN** (`Sedang`): Di `dashboard_user.php:24-36`, selain mengecek sesi login (`:5-8`) dan `role === 'admin'` (`:10-13`), sistem mengambil baris `users` dan mengecek **`$user['status'] !== 'approved' || $user['card_status'] === 'frozen'`** (`:31`) — jika melanggar, sesi dihancurkan (`session_unset(); session_destroy();`) dan dialihkan ke `sign_in.php`!
     - *Saran*: Perjelas diamond menjadi `Login sebagai anggota aktif (approved & kartu tidak beku)?`.
  5. **Perhitungan Estimasi Denda Berjalan & Kartu Anggota Mini (Barcode Code 128) Belum Disebut** — **BENAR DENGAN CATATAN** (`Rendah`): `dashboard_user.php:42-49, 86-104` juga membaca `pengaturan` denda untuk menghitung estimasi denda berjalan atas pinjaman aktif yang lewat jatuh tempo, serta merender kartu anggota mini ber-Barcode Code 128 (`:158`).
  6. **Berkas Sumber `.puml` Merujuk File Hantu `member/dashboard.php` & Klaim CSRF/Tulis DB** — **SALAH** (`Tinggi`): `activity_13_dashboard_anggota.puml:10, 18-24` sepenuhnya berupa templat palsu.
     - *Saran*: Tulis ulang `.puml` sesuai `dashboard_user.php` dan `.png`.

---

### 14. Activity Diagram 14 — Mengelola Status Akun & Kartu Anggota
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Activity Diagram — Mengelola Status Akun & Kartu Anggota` *(subjudul: `Prasyarat: admin sudah login · file: daftar_anggota.php`)*
  - Gambar: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_14_status_akun_kartu_anggota.png`
  - Sumber PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_14_status_akun_kartu_anggota.puml`
- **File PHP Aktual**: `daftar_anggota.php`
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - **Rincian Perbedaan**:
    1. **Perbedaan Kritis Fitur**: `.puml` pada judul & isinya menulis **`Pengelolaan Status Akun Anggota (Approve/Reject/Freeze)`** (`activity_14_status_akun_kartu_anggota.puml:10, 17`), mengklaim fitur **Freeze** oleh Admin yang **TIDAK ADA di kode**! Sebaliknya, `.png` secara **BENAR** menulis `Memilih anggota lalu menekan Aktifkan, Nonaktifkan, atau Cairkan Kartu` (`status = approved / rejected, atau card_status = active`).
    2. `.puml` merujuk file hantu **`admin/anggota.php`** (`:10, 23`), sedangkan `.png` merujuk benar `daftar_anggota.php`.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: 2 kolom sah: `Admin / Petugas` dan `Sistem`.
  - **Alur Aksi & Keputusan**: `start` -> `Membuka halaman Daftar Anggota` -> (Sistem) `Menampilkan daftar anggota (status akun, kartu, kelas)` -> (Admin) `Memilih anggota lalu menekan Aktifkan, Nonaktifkan, atau Cairkan Kartu` -> (Sistem) Diamond `ID anggota valid?`:
    - `[Tidak]` -> `Mengabaikan permintaan (tanpa perubahan)` -> `stop`.
    - `[Ya]` -> `Memperbarui tabel users (hanya role member): status = approved / rejected, atau card_status = active` -> `Menampilkan pesan hasil (akun diaktifkan / dinonaktifkan / kartu dicairkan)` -> (Admin) `Melihat status anggota yang diperbarui` -> `stop`.
  - **Visual PNG**: Sangat bersih, proporsional, tidak ada cacat visual.
- **Daftar Pemeriksaan Elemen & Temuan** *(Total 6 aspek: **4 BENAR**, **1 BENAR DENGAN CATATAN**, **1 SALAH**, **0 TIDAK TERVERIFIKASI**)*:
  1. **Swimlane (`Admin / Petugas` & `Sistem`)** — **BENAR**: Hanya aktor sah (`daftar_anggota.php:5-8`).
  2. **Tiga Aksi Mutasi Status di PNG (`approve`, `reject`, `unfreeze` — Tanpa Fitur Freeze oleh Admin)** — **BENAR**: Sesuai eksak `daftar_anggota.php:57-79` (`status='approved'`, `card_status='active'`, `status='rejected'` dengan filter `WHERE id=$id AND role='member'`). Tidak ada aksi pembekuan kartu oleh Admin di PNG.
  3. **Perilaku Saat `$id <= 0` (`Mengabaikan permintaan (tanpa perubahan)`)** — **BENAR**: Sesuai eksak `daftar_anggota.php:59, 67, 75` (`if ($id > 0) { ... }` tanpa cabang `else`).
  4. **Node Awal/Akhir & Visual PNG** — **BENAR**: 1 `start`, 2 `stop`, visual jernih.
  5. **Verifikasi Token CSRF (`csrf_verify()`) Sebelum Cek `$id > 0`** — **BENAR DENGAN CATATAN** (`Rendah`): Di `daftar_anggota.php:51-55`, seluruh POST diverifikasi dengan `csrf_verify()` terlebih dahulu; jika token CSRF gagal, sistem menampilkan pesan error `'Token sesi keamanan tidak valid atau kedaluwarsa...'` (bukan mengabaikan tanpa pesan). Selain itu, aksi `Cairkan Kartu` di UI memiliki dialog konfirmasi `konfirmasiCairkan()` (`:1139-1150`).
     - *Saran*: Tambahkan pengecekan CSRF sebelum `ID anggota valid?`.
  6. **Berkas Sumber `.puml` Mengklaim Fitur Palsu `Freeze` oleh Admin & File Hantu `admin/anggota.php`** — **SALAH** (`Kritis`): `activity_14_status_akun_kartu_anggota.puml:10, 17, 23` mengklaim Pustakawan memiliki fitur `Freeze` kartu anggota dan memakai file fiktif `admin/anggota.php`.
     - *Saran*: Tulis ulang `.puml` menjadi `Approve / Reject / Unfreeze` pada `daftar_anggota.php` selaras dengan `.png`.

---

### 15. Activity Diagram 15 — Reset Sandi Anggota
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Activity Diagram — Reset Sandi Anggota` *(subjudul: `Prasyarat: admin sudah login · file: daftar_anggota.php`)*
  - Gambar: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_15_reset_sandi_anggota.png`
  - Sumber PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_15_reset_sandi_anggota.puml`
- **File PHP Aktual**: `daftar_anggota.php` (`action === "reset_password"`, baris `103-144`, modal UI baris `1031-1080`, JS baris `1094-1121`)
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - **Rincian Perbedaan**: `.puml` adalah *stub* generik yang merujuk file hantu **`admin/reset_password.php`** (`activity_15_reset_sandi_anggota.puml:10, 23`), sedangkan `.png` merujuk benar `daftar_anggota.php` dengan alur spesifik.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: 2 kolom sah: `Admin / Petugas` dan `Sistem`.
  - **Alur Aksi & Keputusan**: `start` -> `Membuka halaman Daftar Anggota` -> (Sistem) `Menampilkan daftar anggota` -> (Admin) `Menekan Reset Sandi pada anggota terpilih` -> (Sistem) `Mengambil nama & username anggota (role member)` -> Diamond `Anggota ditemukan?`:
    - `[Tidak]` -> `Mengabaikan permintaan (tanpa perubahan)` -> `stop`.
    - `[Ya]` -> `Membuat sandi acak 8 karakter (huruf besar, kecil & angka) lalu di-hash` -> `Menyimpan sandi baru (hash) ke tabel users` -> `Menampilkan sandi baru sekali tampil beserta nama & username` -> (Admin) `Menyampaikan sandi baru kepada anggota` -> `stop`.
  - **Visual PNG**: Bersih, proporsional, tidak ada cacat visual.
- **Daftar Pemeriksaan Elemen & Temuan** *(Total 6 aspek: **3 BENAR**, **1 BENAR DENGAN CATATAN**, **2 SALAH**, **0 TIDAK TERVERIFIKASI**)*:
  1. **Swimlane (`Admin / Petugas` & `Sistem`)** — **BENAR**: Hanya aktor sah (`daftar_anggota.php:5-8`).
  2. **Cek `role='member'`, Hash `PASSWORD_DEFAULT`, Pengosongan `plain_password`, & Tampilan Sekali Tampil (`$reset_info`)** — **BENAR DENGAN CATATAN** (`Rendah`): Sesuai `daftar_anggota.php:107-110, 128-142` (termasuk pengosongan `plain_password = ""` di `:130-131` dan cek `csrf_verify()` di `:51`).
  3. **Node Awal/Akhir, Decision `Anggota ditemukan?`, & Visual PNG** — **BENAR**: 1 `start`, 2 `stop`, cabang `Ya`/`Tidak` lengkap (`if ($u)` di `:110`).
  4. **Langkah Komunikasi ke Anggota (`Menyampaikan sandi baru kepada anggota`)** — **BENAR**: Selaras dengan petunjuk UI di `daftar_anggota.php:1070` (`"Sampaikan sandi baru ini kepada anggota yang bersangkutan."`).
  5. **Melewatkan Modal Input Kata Sandi Manual (`custom_password`) vs Tombol Acak di UI & Backend** — **SALAH** (`Sedang`): PNG menggambarkan seolah-olah menekan Reset Sandi langsung membuat sandi acak 8 karakter di server tanpa input Admin. Faktanya, di `daftar_anggota.php:1032-1080` dan `:1094-1121`, menekan tombol Ubah/Reset Sandi membuka **Modal "Ubah Kata Sandi Anggota"** yang berisi field wajib `name="custom_password"` (`:1063`) di mana Admin **dapat mengetik kata sandi baru pilihan sendiri ATAU menekan tombol "Acak"** (`generateRandomSandi()` 8 karakter di JS `:1114-1121`), lalu klik **Simpan Sandi**; di backend (`daftar_anggota.php:105, 111-127`), PHP memprioritaskan `$custom_pw` bila tidak kosong dan hanya membangkitkan sandi acak server-side bila `$custom_pw === ""`!
     - *Saran*: Perbarui aksi Admin menjadi `Membuka modal Ubah Sandi, mengetik sandi baru atau klik Acak, lalu menekan Simpan Sandi`, dan kotak Sistem menjadi `Menggunakan sandi input (atau membuat sandi acak 8 karakter bila kosong) lalu meng-hash & mengosongkan plain_password`.
  6. **Berkas Sumber `.puml` Merujuk File Hantu `admin/reset_password.php`** — **SALAH** (`Tinggi`): `activity_15_reset_sandi_anggota.puml:10, 23` merujuk modul fiktif `admin/reset_password.php`.
     - *Saran*: Tulis ulang `.puml` sesuai `daftar_anggota.php` dan `.png`.

---

### 16. Activity Diagram 16 — Menghapus Anggota
- **Judul & Path Gambar / Sumber**:
  - Judul PNG: `Activity Diagram — Menghapus Anggota` *(subjudul: `Prasyarat: admin sudah login · file: daftar_anggota.php`)*
  - Gambar: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_16_hapus_anggota.png`
  - Sumber PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_16_hapus_anggota.puml`
- **File PHP Aktual**: `daftar_anggota.php` (`action === "hapus"`, baris `81-101`)
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**
  - **Rincian Perbedaan**: `.puml` adalah *stub* generik yang merujuk file hantu **`admin/anggota.php`** (`activity_16_hapus_anggota.puml:10, 23`) tanpa satupun logika pengecekan riwayat `peminjaman` maupun penghapusan foto anggota!
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: 2 kolom sah: `Admin / Petugas` dan `Sistem`.
  - **Alur Aksi & Keputusan**: `start` -> `Membuka halaman Daftar Anggota` -> (Sistem) `Menampilkan daftar anggota` -> (Admin) `Menekan Hapus pada anggota terpilih & mengonfirmasi` -> (Sistem) `Menghitung riwayat peminjaman milik anggota` -> Diamond `Punya riwayat peminjaman?`:
    - `[Ya]` -> `Menampilkan pesan gagal: anggota masih punya riwayat peminjaman` -> `stop`.
    - `[Tidak]` -> `Menghapus file foto anggota (jika ada)` -> `Menghapus data anggota dari tabel users` -> `Menampilkan pesan anggota berhasil dihapus` -> (Admin) `Melihat daftar anggota terbaru` -> `stop`.
  - **Visual PNG**: Sangat bersih, proporsional, tidak ada cacat visual.
- **Daftar Pemeriksaan Elemen & Temuan** *(Total 6 aspek: **4 BENAR**, **1 BENAR DENGAN CATATAN**, **1 SALAH**, **0 TIDAK TERVERIFIKASI**)*:
  1. **Swimlane (`Admin / Petugas` & `Sistem`)** — **BENAR**: Hanya aktor sah (`daftar_anggota.php:5-8`).
  2. **Proteksi Integritas Sirkulasi (`COUNT(*) FROM peminjaman WHERE user_id=$id > 0` Menolak Hapus)** — **BENAR**: Sesuai eksak `daftar_anggota.php:84-89` (`"Anggota tidak bisa dihapus karena masih punya $jml riwayat peminjaman."`).
  3. **Urutan Hapus File Foto (`@unlink`) Sebelum `DELETE FROM users WHERE id=$id AND role='member'`** — **BENAR**: Sesuai eksak `daftar_anggota.php:90-98`.
  4. **Node Awal/Akhir, Decision Diamond, & Visual PNG** — **BENAR**: 1 `start`, 2 `stop`, cabang `Ya`/`Tidak` lengkap, visual rapi.
  5. **Verifikasi CSRF & Filter `role='member'`** — **BENAR DENGAN CATATAN** (`Rendah`): `daftar_anggota.php:51-55` memeriksa `csrf_verify()` dan `:83` memeriksa `$id > 0` terlebih dahulu, serta kueri `SELECT foto` dan `DELETE FROM users` di `:91, 97` membatasi `AND role='member'` agar akun Admin tidak pernah terhapus.
     - *Saran*: Tambahkan keterangan `(role member)` pada kotak `Menghapus data anggota dari tabel users` dan verifikasi CSRF di awal POST.
  6. **Berkas Sumber `.puml` Merujuk File Hantu `admin/anggota.php` Tanpa Logika Cek Riwayat** — **SALAH** (`Tinggi`): `activity_16_hapus_anggota.puml:10, 23` merujuk modul fiktif `admin/anggota.php` dan tidak memuat pengecekan riwayat `peminjaman`.
     - *Saran*: Tulis ulang `.puml` sesuai `daftar_anggota.php:81-101` dan `.png`.

---

## REKAPITULASI KUANTITATIF HASIL AUDIT 16 ACTIVITY DIAGRAM

| No | Berkas Diagram (`.png`) | Status `.png` vs `.puml` | Jml Aspek Diperiksa | `BENAR` | `BENAR DGN CATATAN` | `SALAH` | `TIDAK TERVERIFIKASI` | Keparahan Tertinggi pada `.png` | Keparahan pada `.puml` |
|---:|---|---|---:|---:|---:|---:|---:|---|---|
| 01 | `01_activity_registrasi_anggota.png` | **TIDAK COCOK** | 7 | 4 | 2 | 1 | 0 | Rendah (catatan) | Sedang |
| 02 | `02_activity_login.png` | **TIDAK COCOK** | 7 | 3 | 2 | 2 | 0 | Rendah (subjudul `logout.php`) | Tinggi (`no_anggota` & lupa `frozen`) |
| 03 | `03_activity_pengajuan_peminjaman_anggota.png` | **TIDAK COCOK** | 8 | 4 | 2 | 2 | 0 | Sedang (`lokasi rak` tak di-SELECT + font mepet) | Tinggi (`status='pending'`, lupa `file_kartu`) |
| 04 | `04_activity_persetujuan_pengajuan_admin.png` | **TIDAK COCOK** | 7 | 5 | 1 | 1 | 0 | Rendah (catatan input alasan tolak) | Tinggi (`pending`, H+7 palsu, tanpa cek stok) |
| 05 | `05_activity_peminjaman_langsung.png` | **TIDAK COCOK** | 7 | 5 | 1 | 1 | 0 | Sedang (catatan kurang syarat `!frozen`) | Tinggi (halusinasi scan barcode & struk) |
| 06 | `06_activity_pengembalian_denda.png` | **TIDAK COCOK** | 8 | 5 | 1 | 2 | 0 | Sedang (paksa bayar langsung, tanpa opsi tunggakan) | Tinggi (kolom hantu & tanpa `grace_period`) |
| 07 | `07_activity_tambah_buku_isbn.png` | **TIDAK COCOK** | 7 | 3 | 1 | 3 | 0 | **Kritis** (swimlane eksternal `Layanan ISBN (API)` + kurang 4 tahap) | Tinggi (file salah `daftar_buku.php` & kolom hantu) |
| 08 | `Lengkap/A08_Lupa_Kartu_OTP.png` | **TANPA `.puml`** | 11 | 4 | 2 | 5 | 0 | **Kritis** (klaim `QR code`, salah loop expired step 3, klaim salah `resend_otp`, tanpa `.puml`) | Sedang (berkas `.puml` absen) |
| 09 | `Lengkap/activity_09_edit_kartu_anggota.png` | **TIDAK COCOK** | 7 | 4 | 1 | 2 | 0 | **Tinggi** (klaim edit `kelas`, lupa `username` & `NIS/NIP` & `@gmail.com` & `old_password`) | Tinggi (stub file hantu `member/profil.php`) |
| 10 | `Lengkap/activity_10_like_simpan_buku.png` | **TIDAK COCOK** | 6 | 4 | 1 | 1 | 0 | Rendah (catatan langkah `buku_simpan.php` opsional) | Tinggi (stub file hantu `api/like.php` + klaim CSRF) |
| 11 | `Lengkap/activity_11_rating_buku.png` | **TIDAK COCOK** | 6 | 4 | 1 | 1 | 0 | Rendah (catatan modal vs halaman) | Tinggi (stub file hantu `api/rate.php` + klaim CSRF) |
| 12 | `Lengkap/activity_12_telusuri_katalog_detail.png` | **TIDAK COCOK** | 7 | 3 | 2 | 2 | 0 | Sedang (klaim `admin` bisa buka `daftar_buku.php`) | Tinggi (stub file hantu `katalog.php` + klaim CSRF) |
| 13 | `Lengkap/activity_13_dashboard_anggota.png` | **TIDAK COCOK** | 6 | 3 | 2 | 1 | 0 | Sedang (catatan kurang guard `approved` & `!frozen`) | Tinggi (stub file hantu `member/dashboard.php`) |
| 14 | `Lengkap/activity_14_status_akun_kartu_anggota.png` | **TIDAK COCOK** | 6 | 4 | 1 | 1 | 0 | Rendah (catatan CSRF) — *PNG benar tanpa Freeze!* | **Kritis** (klaim palsu `Freeze` admin + `admin/anggota.php`) |
| 15 | `Lengkap/activity_15_reset_sandi_anggota.png` | **TIDAK COCOK** | 6 | 3 | 1 | 2 | 0 | Sedang (melewatkan modal input `custom_password` manual/acak) | Tinggi (stub file hantu `admin/reset_password.php`) |
| 16 | `Lengkap/activity_16_hapus_anggota.png` | **TIDAK COCOK** | 6 | 4 | 1 | 1 | 0 | Rendah (catatan CSRF & `role='member'`) | Tinggi (stub file hantu `admin/anggota.php`) |
| **TOTAL** | **16 Diagram Diperiksa Penuh** | **0 Cocok / 15 Tidak Cocok / 1 Tanpa PUML** | **112** | **62** | **22** | **28** | **0** | **2 Kritis, 1 Tinggi, 5 Sedang, 8 Rendah** | **1 Kritis, 13 Tinggi, 2 Sedang** |

---

## DAFTAR PRIORITAS PERBAIKAN UTAMA (RINGKASAN EKSEKUTIF)

1. **`[KRITIS]` `07_activity_tambah_buku_isbn.png`**: Hapus swimlane ke-3 **`Layanan ISBN (API)`** (satukan ke dalam swimlane `Sistem`) dan lengkapi detail pencarian ISBN menjadi validasi 10/13 digit + cek duplikat lokal + pengayaan progresif 4 tahap (Google Books + 3 endpoint Open Library).
2. **`[KRITIS]` `A08_Lupa_Kartu_OTP.png`**:
   - Ubah teks kotak akhir **`Melihat dan mengunduh kartu pengganti (QR code)`** menjadi **`(Barcode Code 128)`** (`kartu_anggota.php:696`, `barcode128.php:5`).
   - Pisahkan cabang kedaluwarsa sesi Langkah 3 (`> 10 menit`, `lupa_kartu.php:262-274`) karena kode mencairkan kembali kartu (`card_status='active'`) dan mengembalikan pengguna ke Langkah 1 (`?expired=1`), **bukan** *loop-back* ke form sandi baru.
   - Hapus kotak catatan biru mengambang yang salah mengklaim `Kirim ulang kode = mengulang dari mengisi email` (`lupa_kartu.php:207-247` tetap di Langkah 2), perbaiki *loop-back* OTP hangus/kedaluwarsa agar tetap di Langkah 2, perbaiki typo `card_status = active'`, dan tambahkan berkas `.puml` yang hilang.
3. **`[KRITIS pada PUML]` `activity_14_status_akun_kartu_anggota.puml` & Seluruh 15 Berkas `.puml`**: Tulis ulang seluruh 15 berkas `.puml` (khususnya hapus klaim palsu `Freeze` di `activity_14.puml`, hapus seluruh referensi file fiktif `member/*`, `admin/*`, `api/*`, `katalog.php` di `09`–`16.puml`, dan perbaiki halusinasi skema/fitur di `01`–`07.puml`) agar 100% cocok dengan `.png` dan kode PHP aktual.
4. **`[TINGGI]` `activity_09_edit_kartu_anggota.png`**: Hapus klaim input/validasi `kelas` (tidak dapat diedit di `edit_kartu.php:72`), tambahkan field wajib `username` & `NIS/NIP (nik)` beserta cek keunikan, tambahkan domain `@gmail.com`, serta verifikasi `old_password` saat ganti sandi.
5. **`[SEDANG]` `03`, `06`, `12`, `15` (`.png`)**:
   - `03`: Hapus klaim menampilkan `lokasi rak` (karena `pengajuan_peminjaman.php:59, 69, 204` tidak men-`SELECT rak`) dan seragamkan ukuran font pada 2 kotak revisi.
   - `06`: Tambahkan cabang pengembalian buku dengan denda yang **belum dibayar saat itu juga** (`status_denda = 'belum_bayar'` sebagai tunggakan) tanpa memaksa selalu langsung `Tandai Lunas`.
   - `12`: Hapus `admin` dari aktor pembuka `daftar_buku.php` (`daftar_buku.php:6-9` me-redirect Admin ke `dashboard.php`).
   - `15`: Gambarkan modal Ubah Sandi Anggota di mana Admin dapat mengetik `custom_password` manual atau menekan tombol `Acak` (`daftar_anggota.php:105, 111-113, 1063-1067`).

---

:|---|---:|---:|---:|---:|---:|---|
| 1 | `activity_20_edit_buku` | 8 | 3 | 2 | 3 | 0 | Tinggi |
| 2 | `activity_21_hapus_buku` | 7 | 3 | 3 | 1 | 0 | Tinggi |
| 3 | `activity_22_hapus_riwayat_peminjaman` | 7 | 2 | 3 | 2 | 0 | Tinggi |
| 4 | `activity_23_pengaturan_denda` | 7 | 1 | 2 | 4 | 0 | Tinggi |
| 5 | `activity_24_pengingat_keterlambatan_wa` | 8 | 2 | 0 | 6 | 0 | **Kritis** |
| 6 | `activity_25_tambah_banner` | 7 | 2 | 2 | 3 | 0 | Tinggi |
| 7 | `activity_26_ubah_banner` | 7 | 2 | 2 | 3 | 0 | Tinggi |
| 8 | `activity_27_atur_banner` | 7 | 2 | 2 | 3 | 0 | Tinggi |
| 9 | `activity_28_pengaturan_musik` | 8 | 2 | 2 | 4 | 0 | Tinggi |
| 10 | `activity_29_hapus_musik` | 7 | 2 | 2 | 3 | 0 | Tinggi |
| 11 | `activity_30_dashboard_admin_ekspor` | 8 | 3 | 1 | 4 | 0 | Tinggi |
| 12 | `activity_31_profil_sandi_admin` | 8 | 3 | 2 | 3 | 0 | Tinggi |
| 13 | `activity_32_logout` | 7 | 2 | 1 | 4 | 0 | Tinggi |
| 14 | `activity_33_notifikasi_pengajuan` | 8 | 2 | 1 | 5 | 0 | Tinggi |
| 15 | `activity_34_pengaturan_lokasi` | 8 | 3 | 1 | 4 | 0 | Tinggi |
| **TOTAL** | **15 Pasang (`.png` + `.puml`)** | **112** | **34** | **26** | **52** | **0** | **Kritis** |

---

## Rincian Pemeriksaan Satu per Satu (Diagram #20 s/d #34)

---

### 1. Diagram #20 — Mengedit Data Buku
- **Judul & Path Gambar / Sumber**:
  -Judul di PNG: `Activity Diagram — Mengedit Data Buku` (`Prasyarat: admin sudah login · file: halaman_admin.php`)
  - Path PNG: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_20_edit_buku.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_20_edit_buku.puml`
- **File PHP Aktual**: `halaman_admin.php` (serta endpoint AJAX opsional `cari_isbn.php`).
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**.
  - `.puml` berjudul `Penyuntingan Metadata & Stok Koleksi Buku / File: admin/buku.php` (`:10`), memakai swimlane `Pustakawan` & `Sistem AKSA NOVA`, merujuk berkas fiktif `admin/buku.php` (`:23`), dan hanya berisi 7 kotak template generik dengan decision tunggal `Permintaan Valid & Otoritas Sesuai?` (`:22`).
  - `.png` menggunakan swimlane `Admin / Petugas` & `Sistem`, merujuk `halaman_admin.php`, dan menggambarkan alur spesifik edit buku beserta decision `Judul terisi & ID valid?`.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: `Admin / Petugas` (kiri) dan `Sistem` (kanan).
  - **Alur**: `InitialNode` (`Admin / Petugas`) -> `Membuka halaman Kelola Buku` -> (`Sistem`) `Menampilkan daftar buku` -> (`Admin / Petugas`) `Menekan Edit, mengubah data buku (opsional: cari ISBN / unggah cover baru), lalu Simpan` -> (`Sistem`) `Menerima data buku & cover baru (jika ada)` -> Decision `Judul terisi & ID valid?`:
    - `[Tidak]` -> `Menampilkan pesan data tidak valid` -> loop-back ke `Menekan Edit, mengubah data buku (opsional: cari ISBN / unggah cover baru), lalu Simpan`.
    - `[Ya]` -> `Menentukan cover: unggahan baru, atau hasil ISBN, atau cover lama` -> `Memperbarui data buku (judul, penulis, ISBN, genre, sinopsis, stok, cover, rak)` -> `Menampilkan pesan buku berhasil diperbarui` -> (`Admin / Petugas`) `Melihat data buku terbaru` -> `ActivityFinalNode`.
- **Daftar Pemeriksaan Elemen & Temuan** (`Total: 8 aspek` — `BENAR: 3`, `BENAR DENGAN CATATAN: 2`, `SALAH: 3`, `TIDAK TERVERIFIKASI: 0`):
  1. **[BENAR]** Simbol `InitialNode`, `ActivityFinalNode`, belah ketupat decision 2 cabang berlabel (`Ya`/`Tidak`), dan teks tajam tanpa mojibake.
  2. **[BENAR]** Hierarki prioritas pemilihan sampul (`$gambar_baru` unggahan manual -> `$gambar_auto` hasil `cari_isbn.php` -> `$gambar_lama`) dan fakta bahwa sampul lama **tidak di-`unlink`** saat diganti (`halaman_admin.php:123-124, 130-133`).
  3. **[BENAR]** Arah loop-back dari `Menampilkan pesan data tidak valid` kembali ke aksi `Menekan Edit...`: karena form `#bookForm` disubmit via POST penuh (`halaman_admin.php:1141`), kegagalan me-reload halaman daftar buku dengan modal tertutup sehingga admin memang harus menekan Edit kembali (`halaman_admin.php:126-127`).
  4. **[BENAR DENGAN CATATAN]** *(Rendah)* Swimlane `Admin / Petugas` dan `Sistem` menggunakan aktor manusia sah tanpa swimlane layanan eksternal; disarankan menyeragamkan label aktor menjadi `Pustakawan` (atau `Pustakawan / Admin`) di seluruh diagram (`halaman_admin.php:6-9`).
  5. **[BENAR DENGAN CATATAN]** *(Rendah)* Pemeriksaan sesi admin (`halaman_admin.php:6-9`) dan token `csrf_verify()` (`halaman_admin.php:73-77`) tidak digambar sebagai aksi/decision eksplisit (dirangkum pada subjudul prasyarat).
  6. **[SALAH]** *(Sedang — Urutan Eksekusi Terbalik)* Di PNG, decision `Judul terisi & ID valid?` digambarkan **sebelum** pemrosesan/penentuan sampul. Di kode aktual `halaman_admin.php:122-128`, pada `action === "update"`, pemanggilan `uploadGambar($_FILES["gambar"], $upload_error)` dan penentuan `$gambar_final` dieksekusi **sebelum** `if ($judul === "" || $id === 0)` (berbeda dari `tambah` di `:91-98`).
  7. **[SALAH]** *(Sedang — Validasi Sampul & Kamera Tidak Tergambar)* Tidak menggambarkan validasi sampul di `uploadGambar()` (`<= 5 MB`, ekstensi `jpg/jpeg/png/webp/gif`, cek MIME `image/*` di `halaman_admin.php:45-62`), opsi ambil foto sampul via kamera `getUserMedia` (`:1161-1164, 1506-1589`), maupun perilaku *non-blocking upload error* (jika unggah sampul gagal, `UPDATE buku` tetap dijalankan dengan sampul lama/ISBN lalu pesan sukses ditambah `" Catatan: $upload_error"` di `:134-135`).
  8. **[SALAH]** *(Tinggi — Desinkronisasi PUML)* Berkas `.puml` berisi template generik dengan nama file fiktif `admin/buku.php` (`activity_20_edit_buku.puml:10, 23`).
- **Saran Perbaikan Tertulis**:
  - Tulis ulang `activity_20_edit_buku.puml` agar selaras 100% dengan `halaman_admin.php` dan render ulang `.png` dari `.puml`.
  - Gambarkan validasi `csrf_verify()`, opsi input sampul (unggah berkas / kamera / auto-ISBN), urutan/percabangan validasi berkas sampul (`<= 5 MB`, `JPG/JPEG/PNG/WEBP/GIF`), serta catatan peringatan bila unggahan sampul gagal sementara metadata buku tetap diperbarui.

---

### 2. Diagram #21 — Menghapus Buku
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Activity Diagram — Menghapus Buku` (`Prasyarat: admin sudah login · file: halaman_admin.php`)
  - Path PNG: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_21_hapus_buku.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_21_hapus_buku.puml`
- **File PHP Aktual**: `halaman_admin.php`.
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**.
  - `.puml` berjudul `Penghapusan Koleksi Buku dari Katalog / File: admin/buku.php` (`:10, 23`), berisi template generik tanpa pengecekan riwayat peminjaman maupun penghapusan berkas sampul.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: `Admin / Petugas` (kiri) dan `Sistem` (kanan).
  - **Alur**: `InitialNode` (`Admin / Petugas`) -> `Membuka halaman Kelola Buku` -> (`Sistem`) `Menampilkan daftar buku` -> (`Admin / Petugas`) `Menekan Hapus pada buku terpilih & mengonfirmasi` -> (`Sistem`) `Menghitung riwayat peminjaman buku tersebut` -> Decision `Masih punya riwayat peminjaman?`:
    - `[Ya]` -> (`Sistem`) `Menampilkan pesan gagal: hapus dulu riwayat di halaman Telah Dipinjam` -> `ActivityFinalNode` (di kolom `Admin / Petugas`).
    - `[Tidak]` -> (`Sistem`) `Menghapus file cover buku (jika ada)` -> `Menghapus data buku dari tabel buku` -> `Menampilkan pesan buku berhasil dihapus` -> (`Admin / Petugas`) `Melihat daftar buku terbaru` -> `ActivityFinalNode`.
- **Daftar Pemeriksaan Elemen & Temuan** (`Total: 7 aspek` — `BENAR: 3`, `BENAR DENGAN CATATAN: 3`, `SALAH: 1`, `TIDAK TERVERIFIKASI: 0`):
  1. **[BENAR]** Pengecekan `SELECT COUNT(*) AS c FROM peminjaman WHERE buku_id=$id` menghitung **seluruh** riwayat peminjaman (aktif maupun sudah dikembalikan, selaras FK `ON DELETE RESTRICT` di `database/aksa_nova.sql:224`), dan bila `> 0` menampilkan pesan gagal agar menghapus riwayat dulu di halaman Telah Dipinjam (`halaman_admin.php:144-149`).
  2. **[BENAR]** Urutan penghapusan saat lolos (`jml_pinjam === 0`): mengecek dan meng-`@unlink` berkas fisik `gambar` bila ada di disk, lalu menjalankan `DELETE FROM buku WHERE id=$id` (`halaman_admin.php:152-159`).
  3. **[BENAR]** Kualitas visual belah ketupat, panah ortogonal, label cabang (`Ya`/`Tidak`), dan teks bersih tanpa distorsi.
  4. **[BENAR DENGAN CATATAN]** *(Rendah)* Swimlane `Admin / Petugas` dan `Sistem` sah (seragamkan nama aktor `Pustakawan / Admin`).
  5. **[BENAR DENGAN CATATAN]** *(Rendah)* Dialog konfirmasi klien `AksaConfirm` (`konfirmasiHapus()`, `halaman_admin.php:1597-1611`) dipadatkan ke satu kotak tanpa decision `Konfirmasi hapus? [Batal/Ya]`; validasi `csrf_verify()` (`:73`) dan `$id > 0` (`:142`) juga tidak digambar eksplisit.
  6. **[BENAR DENGAN CATATAN]** *(Rendah)* Pada cabang `Ya`, panah dari kotak `Sistem` langsung menuju `ActivityFinalNode` yang diletakkan di kolom `Admin / Petugas` tanpa kotak aksi `Melihat pesan penolakan` pada Admin (kurang simetris dengan cabang `Tidak`).
  7. **[SALAH]** *(Tinggi — Desinkronisasi PUML)* Berkas `.puml` berisi template generik yang merujuk berkas fiktif `admin/buku.php` (`activity_21_hapus_buku.puml:10, 23`).
- **Saran Perbaikan Tertulis**:
  - Perbarui `activity_21_hapus_buku.puml` sesuai alur pada `.png`, tambahkan decision konfirmasi `AksaConfirm` (`Batal` / `Ya, Hapus Buku`), letakkan `ActivityFinalNode` cabang gagal setelah aksi Admin membaca pesan gagal (atau di kolom `Sistem`), lalu render ulang `.png`.

---

### 3. Diagram #22 — Menghapus Riwayat Peminjaman
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Activity Diagram — Menghapus Riwayat Peminjaman` (`Prasyarat: admin sudah login · file: telah_dipinjam.php`)
  - Path PNG: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_22_hapus_riwayat_peminjaman.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_22_hapus_riwayat_peminjaman.puml`
- **File PHP Aktual**: `telah_dipinjam.php` *(catatan cakupan terkait: `pengajuan_buku.php` juga memiliki penghapusan riwayat pengajuan)*.
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**.
  - `.puml` berjudul `Pembersihan Riwayat Sirkulasi Selesai / File: admin/hapus_riwayat.php` (`:10, 23`), berisi template generik dengan nama berkas fiktif `admin/hapus_riwayat.php`.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: `Admin / Petugas` (kiri) dan `Sistem` (kanan).
  - **Alur**: `InitialNode` (`Admin / Petugas`) -> `Membuka halaman Telah Dipinjam (tab Dikembalikan)` -> (`Sistem`) `Menampilkan riwayat peminjaman yang sudah dikembalikan` -> (`Admin / Petugas`) `Menekan Hapus Riwayat pada data terpilih` -> (`Sistem`) Decision `ID peminjaman valid?`:
    - `[Tidak]` -> `Mengabaikan permintaan (tanpa perubahan)` -> `ActivityFinalNode` (di kolom `Admin / Petugas`).
    - `[Ya]` -> `Menghapus data peminjaman yang berstatus 'dikembalikan'` -> `Menampilkan pesan riwayat berhasil dihapus` -> (`Admin / Petugas`) `Melihat daftar riwayat terbaru` -> `ActivityFinalNode`.
- **Daftar Pemeriksaan Elemen & Temuan** (`Total: 7 aspek` — `BENAR: 2`, `BENAR DENGAN CATATAN: 3`, `SALAH: 2`, `TIDAK TERVERIFIKASI: 0`):
  1. **[BENAR]** Logika `action === 'hapus_riwayat'` di `telah_dipinjam.php:125-131`: memeriksa `$pem_id > 0` (`ID peminjaman valid?`), mengabaikan bila `<= 0`, dan mengeksekusi `DELETE FROM peminjaman WHERE id=$pem_id AND status='dikembalikan'` beserta pesan `'Riwayat peminjaman berhasil dihapus.'`.
  2. **[BENAR]** Ketajaman simbol decision, garis panah, dan teks tanpa distorsi/mojibake.
  3. **[BENAR DENGAN CATATAN]** *(Rendah)* Swimlane `Admin / Petugas` dan `Sistem` sah (seragamkan label aktor).
  4. **[BENAR DENGAN CATATAN]** *(Rendah)* Kotak awal menyebut `(tab Dikembalikan)`, padahal di `telah_dipinjam.php:160-164, 992-998` tombol `Hapus Riwayat` juga muncul untuk item `'dikembalikan'` pada tab **Semua Riwayat** (`?tab=riwayat`); selain itu `csrf_verify()` (`:78`) tidak digambarkan.
  5. **[BENAR DENGAN CATATAN]** *(Rendah)* `ActivityFinalNode` cabang `Tidak` diletakkan di kolom `Admin / Petugas` langsung dari aksi `Sistem`.
  6. **[SALAH]** *(Sedang — Konfirmasi Modal Dilewatkan & Cakupan Riwayat Pengajuan)* Pertama, di `telah_dipinjam.php:995, 1044-1058`, menekan `Hapus Riwayat` membuka modal konfirmasi `#hapusRiwayatModalOverlay` (`"Hapus Riwayat?"` — `"Ya, Hapus"` / `"Batal"`) yang tidak digambarkan di PNG. Kedua, aplikasi juga memiliki fitur `hapus_riwayat` (satuan) & `bersihkan_riwayat` (massal `disetujui`/`ditolak`/`semua_selesai` disertai `@unlink` berkas `file_kartu`) pada `pengajuan_buku.php:111-156` yang belum dimodelkan di diagram mana pun.
  7. **[SALAH]** *(Tinggi — Desinkronisasi PUML)* Berkas `.puml` menunjuk file fiktif `admin/hapus_riwayat.php` (`activity_22_hapus_riwayat_peminjaman.puml:10, 23`).
- **Saran Perbaikan Tertulis**:
  - Tambahkan langkah & decision konfirmasi modal `Hapus Riwayat?` (`Batal` / `Ya, Hapus`), sebutkan tab `Sudah Dikembalikan / Semua Riwayat`, sinkronkan `.puml` dengan `.png` pada `telah_dipinjam.php`, dan pertimbangkan membuat diagram terpisah (atau memperjelas batas judul menjadi *Menghapus Riwayat Sirkulasi Peminjaman*) agar terbedakan dari pembersihan riwayat `pengajuan_buku.php`.

---

### 4. Diagram #23 — Pengaturan Denda
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Activity Diagram — Pengaturan Denda` (`Prasyarat: admin sudah login · file: pengaturan_denda.php`)
  - Path PNG: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_23_pengaturan_denda.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_23_pengaturan_denda.puml`
- **File PHP Aktual**: `pengaturan_denda.php`.
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**.
  - `.puml` berjudul `Konfigurasi Besaran Tarif & Batas Waktu Denda / File: admin/pengaturan.php` (`:10, 23`), berisi template generik dengan nama berkas fiktif `admin/pengaturan.php`.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: `Admin / Petugas` (kiri) dan `Sistem` (kanan).
  - **Alur**: `InitialNode` (`Admin / Petugas`) -> `Membuka halaman Pengaturan Denda` -> (`Sistem`) `Menampilkan pengaturan saat ini (tarif/hari, denda aktif, masa toleransi), statistik & daftar denda` -> (`Admin / Petugas`) `Mengubah tarif per hari, status denda aktif & masa toleransi (tersimpan otomatis)` -> (`Sistem`) Decision `Tarif denda ≥ 0?`:
    - `[Tidak]` -> `Menampilkan pesan: tarif tidak boleh negatif` -> loop-back ke `Mengubah tarif per hari...`.
    - `[Ya]` -> `Menyimpan otomatis denda_per_hari, denda_aktif & denda_grace_period ke tabel pengaturan` -> `Menampilkan indikator pengaturan tersimpan (toast & status pill)` -> (`Admin / Petugas`) `Melihat pengaturan baru (berlaku untuk perhitungan denda berikutnya)` -> `ActivityFinalNode`.
- **Daftar Pemeriksaan Elemen & Temuan** (`Total: 7 aspek` — `BENAR: 1`, `BENAR DENGAN CATATAN: 2`, `SALAH: 4`, `TIDAK TERVERIFIKASI: 0`):
  1. **[BENAR]** Logika *auto-save* AJAX `action === 'simpan_pengaturan'`: memvalidasi `$denda_per_hari >= 0` (`< 0` ditolak dengan JSON error `'Tarif denda tidak boleh bernilai negatif.'`), membatasi `grace_period = max(0, (int)...)`, meng-*upsert* `denda_per_hari`, `denda_aktif`, `denda_grace_period` ke tabel `pengaturan`, dan menampilkan toast + status pill (`pengaturan_denda.php:75-110, 844-907`).
  2. **[BENAR DENGAN CATATAN]** *(Rendah)* Swimlane `Admin / Petugas` dan `Sistem` sah (seragamkan label aktor).
  3. **[BENAR DENGAN CATATAN]** *(Rendah)* Pemeriksaan `csrf_verify()` (`pengaturan_denda.php:66-74`) tidak digambarkan eksplisit.
  4. **[SALAH]** *(Sedang — Fitur `tandai_lunas` Tidak Digambarkan)* Kotak pertama Sistem menyebut `"Menampilkan ... statistik & daftar denda"`, tetapi aksi mutasi kedua di `pengaturan_denda.php:115-124, 769-778, 822-833` — yakni tombol **`Tandai Lunas`** (`action === 'tandai_lunas'`, dialog `AksaConfirm`, `UPDATE peminjaman SET status_denda='lunas' WHERE id=$pem_id AND status_denda='belum_bayar'`) — hilang sepenuhnya dari diagram.
  5. **[SALAH]** *(Sedang — Cacat Visual Tambalan Bitmap pada PNG)* Tiga kotak aksi (`"Mengubah tarif per hari..."`, `"Menyimpan otomatis denda_per_hari..."`, `"Menampilkan indikator pengaturan tersimpan..."`) diedit secara manual di atas gambar bitmap menggunakan font berbeda sehingga **garis border vertikal kiri dan kanan ketiga kotak tersebut terpotong/hilang**.
  6. **[SALAH]** *(Rendah — Detail Teknis di Kotak Aksi)* Menulis nama kunci DB mentah (`denda_per_hari, denda_aktif & denda_grace_period`) serta komponen UI (`toast & status pill`) sebagai label aksi bisnis.
  7. **[SALAH]** *(Tinggi — Desinkronisasi PUML)* Berkas `.puml` menunjuk file fiktif `admin/pengaturan.php` (`activity_23_pengaturan_denda.puml:10, 23`).
- **Saran Perbaikan Tertulis**:
  - Render ulang gambar utuh dari kode sumber diagram (jangan menambal bitmap manual) agar border kotak utuh dan font seragam; ganti nama kolom teknis dengan istilah bisnis; tambahkan cabang aksi opsional `Tandai Lunas` (atau nyatakan eksplisit bila dipisah ke diagram pelunasan denda); dan selaraskan `.puml` ke `pengaturan_denda.php`.

---

### 5. Diagram #24 — Pengingat Keterlambatan via WhatsApp
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Activity Diagram — Pengingat Keterlambatan via WhatsApp` (`file: cron_cek_terlambat.php, telah_dipinjam.php`)
  - Path PNG: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_24_pengingat_keterlambatan_wa.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_24_pengingat_keterlambatan_wa.puml`
- **File PHP Aktual**: `telah_dipinjam.php` dan `cron_cek_terlambat.php`.
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**.
  - `.puml` berjudul `Pengiriman Notifikasi Keterlambatan via WhatsApp / File: admin/kirim_wa.php` (`:10, 23`), memakai 2 swimlane (`Pustakawan` & `Sistem AKSA NOVA`) dan menunjuk berkas fiktif `admin/kirim_wa.php`.
  - `.png` memakai **3 swimlane** (`Penjadwal (Cron)`, `Sistem`, `Admin / Petugas`) dan alur panjang lintas dua berkas.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: `Penjadwal (Cron)` (kiri), `Sistem` (tengah), `Admin / Petugas` (kanan).
  - **Alur**: `InitialNode` (`Penjadwal (Cron)`) -> `Menjalankan cron_cek_terlambat.php tiap hari (mis. 07:00)` -> (`Sistem`) Decision `Dijalankan lewat CLI?`:
    - `[Tidak]` -> `Menolak akses (403 Forbidden)` -> `ActivityFinalNode`.
    - `[Ya]` -> `Mencari peminjaman berstatus 'dipinjam' yang melewati batas kembali` -> `Menghitung hari terlambat lalu mencatat / memperbarui reminder_log (tanggal hari ini)` -> (`Admin / Petugas`) `Membuka halaman Telah Dipinjam` -> (`Sistem`) `Menampilkan peminjaman terlambat beserta status pengingat WA hari ini` -> (`Admin / Petugas`) `Menekan Kirim Pengingat WA pada anggota terlambat` -> (`Sistem`) `Menyusun pesan pengingat & membuka tautan wa.me ke nomor HP anggota` -> (`Admin / Petugas`) `Mengirim pesan di aplikasi WhatsApp` -> (`Sistem`) `Mencatat wa_terkirim = 1 beserta waktu (tandai_wa)` -> (`Admin / Petugas`) Decision `Salah tandai?`:
      - `[Ya]` -> (`Sistem`) `Mengatur wa_terkirim = 0 (batal_wa)` -> bergabung ke garis menuju `ActivityFinalNode`.
      - `[Tidak]` -> `ActivityFinalNode`.
- **Daftar Pemeriksaan Elemen & Temuan** (`Total: 8 aspek` — `BENAR: 2`, `BENAR DENGAN CATATAN: 0`, `SALAH: 6`, `TIDAK TERVERIFIKASI: 0`):
  1. **[BENAR]** Guard CLI `PHP_SAPI !== 'cli'` -> `403 Forbidden` (`cron_cek_terlambat.php:22-25`) serta logika kueri `status = 'dipinjam' AND batas_kembali < NOW()` yang meng-*upsert* `(peminjaman_id, tanggal, terlambat_hari)` ke `reminder_log` tanpa mengirim pesan WA otomatis (`cron_cek_terlambat.php:37-56`).
  2. **[BENAR]** Kualitas grafis garis, belah ketupat, dan teks tidak pecah/mojibake.
  3. **[SALAH]** *(Kritis — Swimlane Aktor Ilegal)* Menggunakan **`Penjadwal (Cron)`** sebagai swimlane aktor tersendiri. Sesuai aturan Activity Diagram proyek, aktor sah hanyalah `Tamu`, `Anggota`, atau `Pustakawan/Admin` dan `Sistem`; cron adalah pemicu waktu internal server (`AcceptTimeEventAction` di dalam `Sistem`).
  4. **[SALAH]** *(Kritis — Fitur Hantu / Halusinasi `batal_wa`)* Decision `Salah tandai?` -> `[Ya]` -> `Mengatur wa_terkirim = 0 (batal_wa)` **TIDAK ADA SAMA SEKALI** di kode sumber proyek (`telah_dipinjam.php:75-155` hanya memproses `kembalikan`, `hapus_riwayat`, `tandai_wa`, dan `tandai_lunas`; tidak ada tombol/handler pembatalan status WA di `telah_dipinjam.php:960-980, 1192-1200`).
  5. **[SALAH]** *(Tinggi — Penggabungan Sekuensial Keliru)* Mengalirkan panah langsung dari selesainya cron ke `Admin / Petugas: Membuka halaman Telah Dipinjam` seolah cron wajib mendahului aksi Admin. Faktanya `cron_cek_terlambat.php:59` langsung berhenti setelah `echo`, sedangkan `telah_dipinjam.php:132-143, 180-198, 282-286` mendeteksi keterlambatan secara *real-time* dan melakukan `INSERT ... ON DUPLICATE KEY UPDATE` ke `reminder_log` meskipun cron belum pernah dijalankan.
  6. **[SALAH]** *(Sedang — Urutan `tandai_wa` & Syarat `no_hp` Keliru)* Di `telah_dipinjam.php:971-979, 1192-1200`: (a) tombol WA hanya aktif bila anggota memiliki `no_hp` (`$waLink`), jika kosong muncul teks nonaktif `"No HP anggota belum diisi"` (`:978`) — syarat ini hilang di PNG; (b) `tandaiWa(id)` dipanggil otomatis via `setTimeout(..., 400)` segera saat tautan `wa.me` diklik (`:973`), **bukan** setelah Admin mengirim pesan di aplikasi WhatsApp eksternal.
  7. **[SALAH]** *(Rendah — Kode/Kolom Teknis di Kotak Aksi)* Menulis nama skrip/kolom/aksi teknis (`cron_cek_terlambat.php`, `reminder_log`, `wa_terkirim = 1 (tandai_wa)`, `wa_terkirim = 0 (batal_wa)`) di dalam kotak aksi.
  8. **[SALAH]** *(Tinggi — Desinkronisasi PUML)* Berkas `.puml` menunjuk file fiktif `admin/kirim_wa.php` (`activity_24_pengingat_keterlambatan_wa.puml:10, 23`).
- **Saran Perbaikan Tertulis**:
  - Hapus swimlane `Penjadwal (Cron)` sehingga hanya tersisa `Pustakawan / Admin` dan `Sistem`.
  - Pisahkan menjadi 2 partisi/alur independen (atau fokuskan diagram #24 pada alur pengiriman pengingat WA oleh Admin di `telah_dipinjam.php`, dan letakkan pencatatan harian CLI sebagai sinyal waktu di dalam `Sistem` dengan `ActivityFinalNode` sendiri).
  - Hapus seluruh blok fiktif `Salah tandai?` & `batal_wa`; tambahkan decision `Nomor HP anggota tersedia?`; perbaiki urutan bahwa klik `Kirim Pengingat WA` memicu pencatatan log pengingat hari ini di Sistem sekaligus membuka tautan WhatsApp (`wa.me`); lalu tulis ulang `.puml` dan render ulang `.png`.

---

### 6. Diagram #25 — Menambah Banner Beranda
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Activity Diagram — Menambah Banner Beranda` (`Prasyarat: admin sudah login · file: kelola_banner.php`)
  - Path PNG: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_25_tambah_banner.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_25_tambah_banner.puml`
- **File PHP Aktual**: `kelola_banner.php`.
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**.
  - `.puml` berjudul `Penambahan Banner Promosi & Literasi / File: admin/banner.php` (`:10, 23`), berisi template generik dengan nama berkas fiktif `admin/banner.php`.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: `Admin / Petugas` (kiri) dan `Sistem` (kanan).
  - **Alur**: `InitialNode` (`Admin / Petugas`) -> `Membuka halaman Kelola Banner` -> (`Sistem`) `Menampilkan daftar banner (urutan, status aktif, latar beranda)` -> (`Admin / Petugas`) `Mengisi judul, subjudul, tautan & memilih media (JPG/PNG/WEBP/GIF ≤ 5 MB, MP4/WEBM ≤ 20 MB) lalu menekan Simpan` -> (`Sistem`) `Memeriksa ukuran & ekstensi gambar lalu menyimpannya ke uploads/banner` -> Decision `Gambar valid & tersimpan?`:
    - `[Tidak]` -> `Menampilkan pesan: file banner wajib diunggah (format didukung)` -> loop-back ke `Mengisi judul, subjudul...`.
    - `[Ya]` -> `Menentukan urutan paling akhir & menyimpan banner (status aktif)` -> `Menampilkan pesan banner berhasil ditambahkan` -> (`Admin / Petugas`) `Melihat banner baru pada daftar` -> `ActivityFinalNode`.
- **Daftar Pemeriksaan Elemen & Temuan** (`Total: 7 aspek` — `BENAR: 2`, `BENAR DENGAN CATATAN: 2`, `SALAH: 3`, `TIDAK TERVERIFIKASI: 0`):
  1. **[BENAR]** Aturan format & batas ukuran berkas di `uploadGambarBanner()` (`jpg/jpeg/png/webp/gif <= 5 MB`, `mp4/webm <= 20 MB`), perhitungan `urutan = COALESCE(MAX(urutan),0) + 1`, dan penyimpanan baris `banner` baru dengan `aktif = 1` (`kelola_banner.php:37-49, 62-81`).
  2. **[BENAR]** Loop-back saat `$gambar === ""` menampilkan pesan error dan mengembalikan Admin ke pengisian form (`kelola_banner.php:72-74`).
  3. **[BENAR DENGAN CATATAN]** *(Rendah)* Swimlane `Admin / Petugas` dan `Sistem` sah (seragamkan label aktor).
  4. **[BENAR DENGAN CATATAN]** *(Rendah)* Validasi `csrf_verify()` (`kelola_banner.php:55-59`) tidak digambarkan eksplisit.
  5. **[SALAH]** *(Sedang — Fitur Crop 16:7 Klien Tidak Tergambar)* Di `kelola_banner.php:720-738, 805-851, 857-994`, saat Admin memilih gambar statis (`JPG/JPEG/PNG/WEBP`), sistem membuka **Modal Penyesuaian Gambar (`#modalCropOverlay`) rasio `16:7` (`1600x700` px)** sebelum berkas siap di-submit, sedangkan berkas `GIF` dan video `MP4/WEBM` langsung dipakai tanpa crop. Alur pembedaan ini absen di PNG.
  6. **[SALAH]** *(Rendah — Tambalan Visual & Inkonsistensi Istilah "gambar" vs "media")* Kotak ke-2 Admin diedit/ditambal manual pada bitmap (font sans-serif & ketebalan garis berbeda), sementara kotak Sistem & Decision di bawahnya masih berbunyi `"Memeriksa ukuran & ekstensi gambar..."` dan `"Gambar valid & tersimpan?"` serta menulis path teknis `uploads/banner`.
  7. **[SALAH]** *(Tinggi — Desinkronisasi PUML)* Berkas `.puml` menunjuk file fiktif `admin/banner.php` (`activity_25_tambah_banner.puml:10, 23`).
- **Saran Perbaikan Tertulis**:
  - Tambahkan percabangan klien `Media gambar statis (JPG/PNG/WEBP)?` -> `[Ya]` `Menyesuaikan area potong (crop) rasio 16:7`, seragamkan istilah `"berkas media (gambar/GIF/video)"` pada seluruh kotak dan decision, tulis ulang `.puml` pada `kelola_banner.php`, dan render ulang `.png` secara bersih tanpa tambalan manual.

---

### 7. Diagram #26 — Mengubah Banner Beranda
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Activity Diagram — Mengubah Banner Beranda` (`Prasyarat: admin sudah login · file: kelola_banner.php`)
  - Path PNG: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_26_ubah_banner.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_26_ubah_banner.puml`
- **File PHP Aktual**: `kelola_banner.php`.
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**.
  - `.puml` berjudul `Penyuntingan Judul & Gambar Banner / File: admin/banner.php` (`:10, 23`), berisi template generik dengan berkas fiktif `admin/banner.php`.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: `Admin / Petugas` (kiri) dan `Sistem` (kanan).
  - **Alur**: `InitialNode` (`Admin / Petugas`) -> `Membuka halaman Kelola Banner` -> (`Sistem`) `Menampilkan daftar banner` -> (`Admin / Petugas`) `Menekan Edit, mengubah judul / subjudul / tautan & (opsional) mengganti gambar, lalu Simpan` -> (`Sistem`) `Menyimpan gambar baru (jika ada) ke uploads/banner` -> Decision `ID banner valid?`:
    - `[Tidak]` -> `Menampilkan pesan data tidak valid` -> loop-back ke `Menekan Edit...`.
    - `[Ya]` -> `Menghapus gambar lama (bila diganti) & memperbarui data banner` -> `Menampilkan pesan banner berhasil diperbarui` -> (`Admin / Petugas`) `Melihat banner yang diperbarui` -> `ActivityFinalNode`.
- **Daftar Pemeriksaan Elemen & Temuan** (`Total: 7 aspek` — `BENAR: 2`, `BENAR DENGAN CATATAN: 2`, `SALAH: 3`, `TIDAK TERVERIFIKASI: 0`):
  1. **[BENAR]** Urutan eksekusi server persis sesuai `kelola_banner.php:84-107`: memanggil `uploadGambarBanner()` (`:91`) -> mengecek `$id === 0` (`:94`) -> meng-`@unlink($gambar_lama)` bila ada berkas baru (`:98-100`) -> `UPDATE banner` (`:101-104`).
  2. **[BENAR]** Ketajaman simbol, garis ortogonal, dan teks tanpa distorsi/mojibake.
  3. **[BENAR DENGAN CATATAN]** *(Rendah)* Swimlane `Admin / Petugas` dan `Sistem` sah (seragamkan label aktor).
  4. **[BENAR DENGAN CATATAN]** *(Rendah)* Validasi `csrf_verify()` (`kelola_banner.php:55-59`) tidak digambarkan eksplisit; path teknis `uploads/banner` ditulis di kotak aksi.
  5. **[SALAH]** *(Sedang — Dukungan GIF/Video & Crop 16:7 Tidak Tercermin)* Seluruh kotak hanya menyebut `"gambar"`, padahal fitur ubah banner juga menerima `GIF <= 5 MB` dan video `MP4/WEBM <= 20 MB` (`kelola_banner.php:40-43`) serta mewajibkan modal *crop* `16:7` di klien untuk `JPG/PNG/WEBP` (`:805-851`).
  6. **[SALAH]** *(Sedang — Perilaku Fallback Diam-diam Saat Media Baru Tidak Valid)* Di `kelola_banner.php:91-92`, jika berkas baru gagal validasi ukuran/ekstensi, `uploadGambarBanner()` mengembalikan `""` tanpa pesan galat dan sistem diam-diam memakai `$gambar_lama`. Kondisi validasi media baru ini tidak tampak di diagram.
  7. **[SALAH]** *(Tinggi — Desinkronisasi PUML)* Berkas `.puml` menunjuk file fiktif `admin/banner.php` (`activity_26_ubah_banner.puml:10, 23`).
- **Saran Perbaikan Tertulis**:
  - Ubah istilah `"gambar"` menjadi `"media banner (gambar JPG/PNG/WEBP dengan crop 16:7, GIF ≤ 5 MB, atau video MP4/WEBM ≤ 20 MB)"`, gambarkan percabangan validasi media baru (apabila diunggah), hilangkan nama path teknis `uploads/banner`, dan sinkronkan `.puml` dengan `.png`.

---

### 8. Diagram #27 — Mengatur Banner (Aktif, Urutan, Latar, Hapus)
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Activity Diagram — Mengatur Banner (Aktif, Urutan, Latar, Hapus)` (`Prasyarat: admin sudah login · file: kelola_banner.php`)
  - Path PNG: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_27_atur_banner.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_27_atur_banner.puml`
- **File PHP Aktual**: `kelola_banner.php`.
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**.
  - `.puml` berjudul `Pengaturan Urutan & Status Aktif Banner / File: admin/banner.php` (`:10, 23` — bahkan tidak mencakup fitur Latar Beranda & Hapus!), serta berisi template generik dengan berkas fiktif `admin/banner.php`.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: `Admin / Petugas` (kiri) dan `Sistem` (kanan).
  - **Alur**: `InitialNode` (`Admin / Petugas`) -> `Membuka halaman Kelola Banner` -> (`Sistem`) `Menampilkan daftar banner` -> (`Admin / Petugas`) `Memilih aksi pada banner: aktif/nonaktif, geser urutan, jadikan latar beranda, atau hapus` -> (`Sistem`) Decision `Aksi = Hapus?`:
    - `[Ya]` -> `Menghapus file gambar & data banner` -> `Menampilkan pesan hasil aksi`.
    - `[Tidak]` -> `Toggle aktif, tukar urutan dengan banner tetangga, atau simpan banner_background_id` -> `Menampilkan pesan hasil aksi`.
    - Dari `Menampilkan pesan hasil aksi` -> (`Admin / Petugas`) `Melihat daftar banner & tampilan beranda terbaru` -> `ActivityFinalNode`.
- **Daftar Pemeriksaan Elemen & Temuan** (`Total: 7 aspek` — `BENAR: 2`, `BENAR DENGAN CATATAN: 2`, `SALAH: 3`, `TIDAK TERVERIFIKASI: 0`):
  1. **[BENAR]** Mencakup keempat aksi manajemen banner di `kelola_banner.php`: `hapus` (`@unlink` + `DELETE`, `:110-122`), `toggle` (`aktif = 1 - aktif`, `:125-131`), `geser` tukar `urutan` dengan banner tetangga (`:134-149`), dan `set_background` simpan ID ke `pengaturan.banner_background_id` (`:152-165`).
  2. **[BENAR]** Simbol awal/akhir, belah ketupat, dan garis bersih tanpa distorsi.
  3. **[BENAR DENGAN CATATAN]** *(Rendah)* Swimlane `Admin / Petugas` dan `Sistem` sah (seragamkan label aktor).
  4. **[BENAR DENGAN CATATAN]** *(Rendah)* Validasi `csrf_verify()` (`:55`) tidak digambarkan; teks akhir `"Melihat daftar banner & tampilan beranda terbaru"` sedikit ambigu karena setelah aksi selesai halaman tetap berada di `kelola_banner.php` (bukan otomatis membuka `index.php`).
  5. **[SALAH]** *(Sedang — Aksi `geser` Tidak Menampilkan Pesan & Konfirmasi Hapus `AksaConfirm` Dilewatkan)* Di PNG semua cabang bermuara ke `"Menampilkan pesan hasil aksi"`, padahal di kode aktual `kelola_banner.php:134-149` aksi **`geser` sama sekali tidak menyetel `$msg`** (dan hanya menukar urutan jika `$tetangga` ditemukan di `:144`). Selain itu aksi `hapus` di klien wajib melewati dialog konfirmasi **`AksaConfirm`** (`konfirmasiHapusBanner`, `:664, 745-756`) yang memiliki cabang pembatalan.
  6. **[SALAH]** *(Rendah — Nama Kunci Teknis & Istilah Media)* Menulis nama kunci database mentah `banner_background_id` di dalam kotak aksi, serta menulis `"Menghapus file gambar"` padahal berkas banner dapat berupa video `mp4/webm`.
  7. **[SALAH]** *(Tinggi — Desinkronisasi PUML)* Berkas `.puml` menunjuk file fiktif `admin/banner.php` (`activity_27_atur_banner.puml:10, 23`).
- **Saran Perbaikan Tertulis**:
  - Pecah pemilihan aksi menggunakan decision multi-cabang (`toggle`, `geser`, `set_background`, `hapus` + konfirmasi `AksaConfirm`), bedakan bahwa `geser` langsung memuat ulang urutan daftar tanpa banner pesan notifikasi, ganti `banner_background_id` & `"file gambar"` dengan bahasa bisnis (`"pengaturan latar utama beranda"` & `"berkas media banner"`), lalu sinkronkan `.puml` dan `.png`.

---

### 9. Diagram #28 — Pengaturan Musik Latar
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Activity Diagram — Pengaturan Musik Latar` (`Prasyarat: admin sudah login · file: pengaturan_musik.php`)
  - Path PNG: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_28_pengaturan_musik.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_28_pengaturan_musik.puml`
- **File PHP Aktual**: `pengaturan_musik.php` (diputar lintas halaman oleh `settings_include.php`).
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**.
  - `.puml` berjudul `Pengaturan Musik Latar & Volume Default / File: admin/musik.php` (`:10, 23`), menunjuk berkas fiktif `admin/musik.php`, mengklaim fitur fiktif `Volume Default`, dan mengklaim `Validasi sesi pengguna dan token keamanan CSRF` (`:21`) yang faktanya **tidak ada** di `pengaturan_musik.php`.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: `Admin / Petugas` (kiri) dan `Sistem` (kanan).
  - **Alur**: `InitialNode` (`Admin / Petugas`) -> `Membuka halaman Pengaturan Musik` -> (`Sistem`) `Menampilkan pengaturan musik (aktif, judul, file)` -> (`Admin / Petugas`) `Mengisi judul, mengaktifkan musik & mengunggah file (MP3/OGG/WAV/M4A ≤ 15 MB) (tersimpan otomatis)` -> (`Sistem`) `Memeriksa ukuran, ekstensi & tipe MIME berkas` -> Decision 1 `Berkas valid (atau tak ada file baru)?`:
    - `[Tidak]` -> `Menampilkan pesan kesalahan unggah` -> loop-back vertikal ke `Mengisi judul...`.
    - `[Ya]` -> `Menghapus file lama & menyimpan file baru di uploads/musik` -> Decision 2 `Musik aktif tetapi belum ada file?`:
      - `[Ya]` -> `Menampilkan pesan: aktifkan musik memerlukan file` -> loop-back **garis miring diagonal** ke `Mengisi judul...`.
      - `[Tidak]` -> `Menyimpan otomatis musik_file, musik_judul & musik_aktif ke pengaturan` -> `Menampilkan indikator pengaturan tersimpan (toast & status pill)` -> (`Admin / Petugas`) `Melihat pengaturan musik terbaru (pemutar muncul bila aktif)` -> `ActivityFinalNode`.
- **Daftar Pemeriksaan Elemen & Temuan** (`Total: 8 aspek` — `BENAR: 2`, `BENAR DENGAN CATATAN: 2`, `SALAH: 4`, `TIDAK TERVERIFIKASI: 0`):
  1. **[BENAR]** Batas validasi berkas audio (`mp3/ogg/wav/m4a`, `<= 15 MB`, cek MIME audio di `pengaturan_musik.php:138-153, 972`), penolakan mengaktifkan musik bila `musik_file` kosong (`:87-97, 882-886`), serta tidak mengklaim CSRF di PNG (sesuai kode `:62-247` yang memang tanpa `csrf_verify()`).
  2. **[BENAR]** Node awal/akhir dan kelengkapan label `Ya`/`Tidak` pada kedua decision.
  3. **[BENAR DENGAN CATATAN]** *(Rendah)* Swimlane `Admin / Petugas` dan `Sistem` sah (seragamkan label aktor).
  4. **[BENAR DENGAN CATATAN]** *(Rendah)* Teks `"pemutar muncul bila aktif"`: di halaman `pengaturan_musik.php:773-786, 1005-1016`, elemen `<audio controls>` pratinjau selalu muncul selama berkas musik ada; yang muncul hanya saat aktif adalah tombol pemutar musik mengambang di halaman utama/publik (`:906`).
  5. **[SALAH]** *(Tinggi — Logika Alur Hibrida Kontradiktif)* Di kode UI aktual (`pengaturan_musik.php:878-1028`), halaman bekerja melalui **3 aksi AJAX independen** (`upload_file` saat pilih berkas, `simpan_judul` debounce `600 ms`, dan `toggle_musik` saat geser switch), bukan satu submit berurutan. Karena diagram lama hanya ditambal 3 kotak teksnya, cabang `Berkas valid (atau tak ada file baru)? -> [Ya]` langsung mengeksekusi **`"Menghapus file lama & menyimpan file baru di uploads/musik"`** — secara logis **salah** ketika tidak ada file baru yang diunggah (mis. saat Admin hanya mengedit judul atau mengaktifkan toggle)!
  6. **[SALAH]** *(Sedang — Cacat Visual Tambalan Bitmap & Garis Miring Bertumpuk)* Tiga kotak aksi ditambal manual dengan font berbeda; garis border vertikal kiri-kanan pada kotak `"Menyimpan otomatis musik_file..."` **terpotong/hilang**; dan garis loop-back kedua di sisi kiri **miring diagonal (tidak ortogonal)** serta menumpuk kepala panah di bawah kotak Admin.
  7. **[SALAH]** *(Rendah — Detail Teknis di Kotak Aksi)* Menyebut nama kunci DB (`musik_file, musik_judul & musik_aktif`), path `uploads/musik`, dan istilah komponen UI (`toast & status pill`).
  8. **[SALAH]** *(Tinggi — Desinkronisasi & Klaim Palsu PUML)* Berkas `.puml` menunjuk file fiktif `admin/musik.php`, mengklaim fitur fiktif `Volume Default`, dan keliru mengklaim adanya validasi CSRF (`activity_28_pengaturan_musik.puml:10, 21, 23`).
- **Saran Perbaikan Tertulis**:
  - Gambar ulang dari awal (melalui `.puml` yang benar tanpa tambalan bitmap) dengan memodelkan 3 cabang aksi *auto-save* di `pengaturan_musik.php`: (1) *Unggah Berkas Audio* (`<= 15 MB`, `MP3/OGG/WAV/M4A` -> hapus file lama bila ada -> simpan `musik_file` & auto-isi judul bila kosong), (2) *Ubah Judul Musik* (debounce simpan `musik_judul`), dan (3) *Ubah Toggle Aktif* (cek berkas musik sudah ada -> simpan `musik_aktif`), disertai garis ortogonal rapi.

---

### 10. Diagram #29 — Menghapus File Musik Latar
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Activity Diagram — Menghapus File Musik Latar` (`Prasyarat: admin sudah login · file: pengaturan_musik.php`)
  - Path PNG: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_29_hapus_musik.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_29_hapus_musik.puml`
- **File PHP Aktual**: `pengaturan_musik.php`.
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**.
  - `.puml` berjudul `Penonaktifan / Pembersihan Berkas Audio / File: admin/musik.php` (`:10, 23`), menunjuk file fiktif `admin/musik.php` dan keliru mengklaim `token keamanan CSRF` (`:21`).
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: `Admin / Petugas` (kiri) dan `Sistem` (kanan).
  - **Alur** (linier tanpa decision): `InitialNode` (`Admin / Petugas`) -> `Membuka halaman Pengaturan Musik` -> `Menekan Hapus File Musik` -> (`Sistem`) `Menghapus file musik dari penyimpanan (uploads/musik)` -> `Mengosongkan musik_file & menonaktifkan musik_aktif di tabel pengaturan` -> `Menampilkan pesan musik dihapus & fitur dinonaktifkan` -> (`Admin / Petugas`) `Melihat pengaturan musik (nonaktif)` -> `ActivityFinalNode`.
- **Daftar Pemeriksaan Elemen & Temuan** (`Total: 7 aspek` — `BENAR: 2`, `BENAR DENGAN CATATAN: 2`, `SALAH: 3`, `TIDAK TERVERIFIKASI: 0`):
  1. **[BENAR]** Efek bisnis `action === "hapus_file"` (`pengaturan_musik.php:67-85, 1047-1062`): menghapus berkas fisik (`@unlink`), mengosongkan `musik_file = ""` dan menyetel `musik_aktif = "0"` (sementara `musik_judul` tetap tersimpan), memperbarui UI menjadi nonaktif tanpa reload via AJAX, dan tidak mengklaim CSRF di PNG.
  2. **[BENAR]** Gambar bersih, garis ortogonal rapi, tanpa cacat visual/mojibake.
  3. **[BENAR DENGAN CATATAN]** *(Rendah)* Swimlane `Admin / Petugas` dan `Sistem` sah (seragamkan label aktor).
  4. **[BENAR DENGAN CATATAN]** *(Rendah)* Setelah `Membuka halaman Pengaturan Musik`, panah langsung turun ke `Menekan Hapus File Musik` tanpa aksi `Sistem` menampilkan halaman & tombol hapus terlebih dahulu (padahal tombol `#btnHapusMusik` hanya dirender bila berkas musik sedang tersimpan, `pengaturan_musik.php:790`).
  5. **[SALAH]** *(Sedang — Melewatkan Konfirmasi `AksaConfirm` & Cabang Batal)* Di `pengaturan_musik.php:1032-1041`, klik tombol Hapus memicu modal konfirmasi **`AksaConfirm`** (`"Hapus Berkas Musik?"` — `"Ya, Hapus Musik"` / `"Batal"`). Jika Admin memilih `"Batal"`, tidak ada request penghapusan yang dikirim. Diagram sama sekali tidak memiliki decision konfirmasi.
  6. **[SALAH]** *(Rendah — Detail Teknis di Kotak Aksi)* Menyebut path `uploads/musik` serta nama kunci DB `musik_file` dan `musik_aktif` secara harfiah di kotak aksi.
  7. **[SALAH]** *(Tinggi — Desinkronisasi & Klaim Palsu PUML)* Berkas `.puml` menunjuk file fiktif `admin/musik.php` dan mengklaim validasi CSRF yang tidak ada di kode (`activity_29_hapus_musik.puml:10, 21, 23`).
- **Saran Perbaikan Tertulis**:
  - Tambahkan respon `Sistem: Menampilkan pengaturan & berkas musik saat ini`, tambahkan decision konfirmasi `Konfirmasi hapus musik?` (`[Batal]` selesai / kembali ke halaman; `[Ya]` hapus via AJAX), gunakan bahasa bisnis bersih pada kotak aksi, dan tulis ulang `.puml` sesuai `pengaturan_musik.php` (tanpa klaim CSRF kecuali kode PHP-nya sudah diperbaiki dulu).

---

### 11. Diagram #30 — Dashboard Admin & Ekspor Laporan Excel
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Activity Diagram — Dashboard Admin & Ekspor Laporan Excel` (`file: dashboard.php, export_excel_dashboard.php`)
  - Path PNG: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_30_dashboard_admin_ekspor.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_30_dashboard_admin_ekspor.puml`
- **File PHP Aktual**: `dashboard.php` dan `export_excel_dashboard.php`.
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**.
  - `.puml` berjudul `Pemantauan Statistik & Ekspor Laporan Excel / File: admin/dashboard.php & admin/ekspor_excel.php` (`:10, 23` — keduanya path fiktif!) dan keliru mengklaim `Validasi sesi pengguna dan token keamanan CSRF` (`:21`) padahal kedua berkas murni melayani request `GET` tanpa CSRF.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: `Admin / Petugas` (kiri) dan `Sistem` (kanan).
  - **Alur**: `InitialNode` (`Admin / Petugas`) -> `Membuka halaman Dashboard` -> (`Sistem`) `Memeriksa sesi & role admin` -> Decision `Admin sudah login?`:
    - `[Tidak]` -> `Mengalihkan ke halaman Sign In` -> `ActivityFinalNode` (di kolom `Admin / Petugas`).
    - `[Ya]` -> `Menghitung statistik: buku & stok, status anggota, pinjaman aktif / terlambat, denda, buku terpopuler, rekap bulanan` -> `Menampilkan Dashboard (kartu statistik & grafik)` -> (`Admin / Petugas`) `Melihat statistik lalu menekan Ekspor Excel` -> (`Sistem`) `Memeriksa hak akses admin (403 bila bukan admin)` -> `Menyusun laporan dari data buku, anggota & peminjaman tahun berjalan` -> `Mengirim file Laporan_AKSANOVA_(tahun).xls untuk diunduh` -> (`Admin / Petugas`) `Menyimpan file laporan Excel` -> `ActivityFinalNode`.
- **Daftar Pemeriksaan Elemen & Temuan** (`Total: 8 aspek` — `BENAR: 3`, `BENAR DENGAN CATATAN: 1`, `SALAH: 4`, `TIDAK TERVERIFIKASI: 0`):
  1. **[BENAR]** Guard otorisasi di kedua berkas akurat: `dashboard.php:5-8` me-redirect non-admin ke `sign_in.php`, sedangkan `export_excel_dashboard.php:4-7` mengembalikan HTTP `403`.
  2. **[BENAR]** Cakupan metrik statistik yang dihitung (`dashboard.php:78-227`) dan mekanisme pengiriman lampiran `.xls` (`export_excel_dashboard.php:143-154`) sesuai kode.
  3. **[BENAR]** Ketajaman grafis, garis, dan teks bersih tanpa distorsi/mojibake.
  4. **[BENAR DENGAN CATATAN]** *(Rendah)* Swimlane `Admin / Petugas` dan `Sistem` sah (seragamkan label aktor).
  5. **[SALAH]** *(Sedang — Klaim Fiktif Menampilkan `"grafik"`)* Kotak `"Menampilkan Dashboard (kartu statistik & grafik)"` tidak sesuai kode: `dashboard.php:840-1019` **tidak memiliki grafik/chart visual apa pun** (hanya merender 5 kartu angka ringkasan, **tabel** rekap 12 bulan, **daftar** Top 5 buku terpopuler, dan 4 kartu ringkasan teks).
  6. **[SALAH]** *(Sedang — Filter Tahun `2000..2100`, Cetak Laporan, & Kirim WA Absen + Ekspor Dikunci `"tahun berjalan"`)* Di `dashboard.php:171-174, 886-900, 971-980` dan `export_excel_dashboard.php:40-42, 143-317`: (a) admin dapat memfilter `?tahun=` (`2000..2100`), bukan hanya `"tahun berjalan"`; (b) terdapat aksi **`Cetak Laporan` (`window.print()`)** dan **`Kirim via WhatsApp` (`wa.me/?text=...`)** selain `Export Excel`, dan admin bisa selesai hanya dengan melihat statistik tanpa wajib mengunduh Excel; (c) berkas `.xls` berisi 3 *worksheet* SpreadsheetML XML dengan pola nama `Laporan_AKSANOVA_{tahun}_{Ymd_His}.xls` *(catatan bug di kode PHP: variabel `$total_tahun_pinjam` belum didefinisikan pada `export_excel_dashboard.php:273`)*.
  7. **[SALAH]** *(Rendah — Keputusan Ditulis sebagai Kotak Aksi)* `"Memeriksa hak akses admin (403 bila bukan admin)"` menggabungkan pemeriksaan dan cabang gagal ke dalam satu kotak aksi biasa tanpa simbol Decision.
  8. **[SALAH]** *(Tinggi — Desinkronisasi PUML)* Berkas `.puml` menunjuk path fiktif `admin/dashboard.php & admin/ekspor_excel.php` dan mengklaim validasi CSRF (`activity_30_dashboard_admin_ekspor.puml:10, 21, 23`).
- **Saran Perbaikan Tertulis**:
  - Ganti `"kartu statistik & grafik"` menjadi `"kartu statistik, tabel rekap 12 bulan, daftar buku terpopuler & ringkasan laporan"`; tambahkan opsi navigasi filter tahun (`2000–2100`) serta pilihan tindakan opsional (`Ekspor Excel .xls 3 sheet`, `Cetak Laporan`, `Kirim Ringkasan via WhatsApp`, atau `Selesai`); ubah cek akses 403 menjadi decision yang benar; dan tulis ulang `.puml` sesuai kode aktual.

---

### 12. Diagram #31 — Ubah Profil & Kata Sandi Admin
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Activity Diagram — Ubah Profil & Kata Sandi Admin` (`Prasyarat: admin sudah login · file: update_profil_admin.php, ganti_sandi_admin.php`)
  - Path PNG: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_31_profil_sandi_admin.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_31_profil_sandi_admin.puml`
- **File PHP Aktual**: `modal_profil_admin.php` dan `update_profil_admin.php` *(sedangkan `ganti_sandi_admin.php` adalah berkas warisan yang tidak lagi dipanggil oleh UI modal)*.
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**.
  - `.puml` berjudul `Pembaruan Profil & Kata Sandi Administrator / File: admin/profil.php` (`:10, 23`), berisi template generik dengan berkas fiktif `admin/profil.php`.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: `Admin / Petugas` (kiri) dan `Sistem` (kanan).
  - **Alur**: `InitialNode` (`Admin / Petugas`) -> `Membuka modal Profil Admin` -> (`Sistem`) `Menampilkan nama tampilan, foto & form ganti sandi` -> (`Admin / Petugas`) `Mengubah nama, foto (≤ 5 MB) dan/atau kata sandi, lalu Simpan` -> (`Sistem`) `Memeriksa sesi admin; bila sandi diisi: semua kolom, ≥ 8 karakter, huruf & angka, konfirmasi sama, sandi lama benar & berbeda` -> Decision `Data valid?`:
    - `[Tidak]` -> `Mengalihkan kembali dengan pesan kesalahan` -> loop-back ke `Mengubah nama, foto (≤ 5 MB) dan/atau kata sandi, lalu Simpan`.
    - `[Ya]` -> `Menyimpan sandi baru (hash) bila diganti` -> `Menyimpan foto baru (hapus foto lama) & memperbarui nama di admin_profile` -> `Memperbarui nama di sesi & menampilkan pesan profil tersimpan` -> (`Admin / Petugas`) `Melihat profil yang diperbarui` -> `ActivityFinalNode`.
- **Daftar Pemeriksaan Elemen & Temuan** (`Total: 8 aspek` — `BENAR: 3`, `BENAR DENGAN CATATAN: 2`, `SALAH: 3`, `TIDAK TERVERIFIKASI: 0`):
  1. **[BENAR]** Seluruh syarat validasi ganti kata sandi opsional sesuai `update_profil_admin.php:36-76` (3 kolom wajib terisi bila ingin ganti sandi, min `8` karakter, wajib huruf + angka, cocok konfirmasi, `password_verify()` sandi saat ini cocok, berbeda dari sandi lama, di-hash `PASSWORD_DEFAULT`).
  2. **[BENAR]** Alur simpan foto baru (`<= 5 MB`, `jpg/jpeg/png/webp/gif` + `@unlink` foto lama), update `display_name` & `foto` pada `admin_profile`, update `$_SESSION["user_name"]`, dan redirect `?profil_saved=1` (`update_profil_admin.php:83-124`).
  3. **[BENAR]** Ketajaman simbol, garis ortogonal, dan teks bersih tanpa distorsi/mojibake.
  4. **[BENAR DENGAN CATATAN]** *(Rendah)* Swimlane `Admin / Petugas` dan `Sistem` sah (seragamkan label aktor).
  5. **[BENAR DENGAN CATATAN]** *(Rendah)* Validasi `csrf_verify()` (`update_profil_admin.php:23-26`) tidak disebut di kotak pemeriksaan, serta nama tabel teknis `admin_profile` ditulis di kotak aksi.
  6. **[SALAH]** *(Sedang — Subjudul Merujuk Berkas Warisan `ganti_sandi_admin.php` & Melewatkan `modal_profil_admin.php`)* Subjudul di PNG menulis `file: update_profil_admin.php, ganti_sandi_admin.php`. Faktanya, modal dirender oleh **`modal_profil_admin.php`** yang men-submit form ke **`update_profil_admin.php`** (`modal_profil_admin.php:131`), sedangkan `ganti_sandi_admin.php` tidak pernah dipanggil dari UI mana pun.
  7. **[SALAH]** *(Sedang — Validasi Foto Tidak Memicu Cabang `Data valid? -> Tidak`)* Di `update_profil_admin.php:88-106`, jika foto yang diunggah melebihi `5 MB` atau berformat salah, sistem **tidak** mengalihkan ke cabang kesalahan `?profil_err=...`, melainkan mengabaikan foto secara diam-diam dan tetap menyimpan nama/sandi (`?profil_saved=1`). Selain itu, setelah redirect `?profil_err=...`, modal hanya terbuka otomatis kembali di `dashboard.php:1052-1055` (di 9 halaman admin lain modal tertutup sehingga admin harus mengklik profil lagi).
  8. **[SALAH]** *(Tinggi — Desinkronisasi PUML)* Berkas `.puml` menunjuk file fiktif `admin/profil.php` (`activity_31_profil_sandi_admin.puml:10, 23`).
- **Saran Perbaikan Tertulis**:
  - Ubah subjudul file menjadi `modal_profil_admin.php, update_profil_admin.php`; tambahkan pemeriksaan CSRF dan jelaskan bahwa unggahan foto hanya diterapkan bila valid (`JPG/PNG/WEBP/GIF ≤ 5 MB`); ganti `admin_profile` menjadi `"data profil admin"`; dan tulis ulang `.puml` agar cocok dengan `.png`.

---

### 13. Diagram #32 — Logout
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Activity Diagram — Logout` (`Prasyarat: pengguna sudah login · file: logout.php`)
  - Path PNG: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_32_logout.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_32_logout.puml`
- **File PHP Aktual**: `logout.php` dan `settings_include.php`.
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**.
  - `.puml` berjudul `Terminasi Sesi Pengguna (Logout) / File: logout.php` (`:10`), berisi template generik yang mengklaim **`Validasi sesi pengguna dan token keamanan CSRF`** (`:21`) serta **`Eksekusi operasi baca / tulis ke basis data MySQL`** (`:24`) — keduanya **salah total** karena `logout.php` tidak memanggil `csrf_verify()` dan memiliki **`0` kueri SQL**!
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: `Pengguna (Anggota / Admin)` (kiri) dan `Sistem` (kanan).
  - **Alur** (4 kotak lurus tanpa decision): `InitialNode` (`Pengguna (Anggota / Admin)`) -> `Menekan tombol Logout` -> (`Sistem`) `Menghapus sesi login (session_destroy)` -> `Mengalihkan ke halaman Sign In` -> (`Pengguna (Anggota / Admin)`) `Melihat halaman Sign In` -> `ActivityFinalNode`.
- **Daftar Pemeriksaan Elemen & Temuan** (`Total: 7 aspek` — `BENAR: 2`, `BENAR DENGAN CATATAN: 1`, `SALAH: 4`, `TIDAK TERVERIFIKASI: 0`):
  1. **[BENAR]** Swimlane `Pengguna (Anggota / Admin)` dan `Sistem` mencakup kedua aktor sah yang memiliki alur logout identik tanpa aktor ilegal.
  2. **[BENAR]** Pengalihan akhir menuju `sign_in.php` (`logout.php:35`) serta kualitas visual kotak/panah bersih tanpa distorsi.
  3. **[BENAR DENGAN CATATAN]** *(Rendah)* Guard `if (!isset($_SESSION['user_id']))` (`logout.php:9-12`) dirangkum pada teks prasyarat, dan PNG tidak mengklaim CSRF (selaras fakta bahwa `logout.php:14-19` tidak memanggil `csrf_verify()`).
  4. **[SALAH]** *(Tinggi — Menghilangkan Seluruh Konfirmasi Logout Dua Lapis & Cabang Pembatalan)* Di kode aktual, menekan tombol Logout **tidak pernah langsung menghapus sesi**:
     - Di seluruh halaman ber-`settings_include.php:289-352`, klik tombol Logout dicegat oleh `konfirmasiLogoutAksa()` yang menampilkan dialog **`AksaConfirm`** (`"Ya, Keluar"` vs `"Tidak, Batal"`), menyimpan state `AksaAudio`, lalu mengirim `POST confirm_logout=1`.
     - Jika `logout.php` diakses langsung tanpa konfirmasi (`logout.php:14-18, 39-50, 141-157`), server menampilkan **halaman HTML Konfirmasi Logout** (`"Tidak, Batal"` kembali ke `$back_url` vs `"Ya, Keluar"`). Seluruh percabangan penting ini hilang dari PNG.
  5. **[SALAH]** *(Rendah — Pembersihan Cookie Sesi Tidak Disebut)* Di `logout.php:21-34`, selain `session_destroy()`, sistem mengosongkan `$_SESSION = []` dan **menghapus cookie sesi browser** (`setcookie(session_name(), '', time() - 42000, ...)`).
  6. **[SALAH]** *(Rendah — Nama Fungsi Teknis di Kotak Aksi)* Menulis `(session_destroy)` langsung di dalam label aksi bisnis.
  7. **[SALAH]** *(Tinggi — Desinkronisasi & Klaim Palsu PUML)* Berkas `.puml` mengklaim validasi CSRF & operasi MySQL yang tidak pernah ada di `logout.php` (`activity_32_logout.puml:21, 24`).
- **Saran Perbaikan Tertulis**:
  - Tambahkan langkah penampilan dialog/halaman konfirmasi Logout beserta decision `Konfirmasi keluar?`: cabang `[Tidak, Batal]` tetap di halaman asal (`$back_url`), cabang `[Ya, Keluar]` mengosongkan data sesi, menghapus cookie sesi, menghancurkan sesi server, dan mengarahkan ke halaman Sign In; hapus tulisan teknis `(session_destroy)`; serta tulis ulang `.puml` sesuai kode aktual.

---

### 14. Diagram #33 — Notifikasi Pengajuan Peminjaman (Admin)
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Activity Diagram — Notifikasi Pengajuan Peminjaman (Admin)` (`Berjalan otomatis di halaman admin · file: notifikasi_pengajuan_admin.php, api_cek_pengajuan_admin.php`)
  - Path PNG: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_33_notifikasi_pengajuan.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_33_notifikasi_pengajuan.puml`
- **File PHP Aktual**: `notifikasi_pengajuan_admin.php` dan `api_cek_pengajuan_admin.php`.
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**.
  - `.puml` berjudul `Pemantauan Polling Notifikasi Pengajuan 4 Detik / File: admin/check_notif.php` (`:10, 23`), menunjuk file fiktif `admin/check_notif.php` dan keliru mengklaim `token keamanan CSRF` (`:21`) pada endpoint polling `GET`.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: `Admin / Petugas` (kiri) dan `Sistem` (kanan).
  - **Alur**: `InitialNode` (di kolom `Sistem`) -> `Memanggil api_cek_pengajuan_admin.php setiap 4 detik (polling)` -> Decision 1 `Admin sudah login?`:
    - `[Tidak]` -> `Menolak akses (403 Akses ditolak)` -> `ActivityFinalNode`.
    - `[Ya]` -> `Menghitung pengajuan berstatus 'menunggu' & mengambil yang terbaru` -> `Mengirim hasil (jumlah & data pengajuan terbaru) dalam JSON` -> Decision 2 `Ada pengajuan baru (ID > terakhir dilihat)?`:
      - `[Tidak]` -> garis naik vertikal **menembus tepat di tengah kotak `Menolak akses (403 Akses ditolak)`** kembali ke `Memanggil api_cek_pengajuan_admin.php setiap 4 detik (polling)`.
      - `[Ya]` -> `Menampilkan pop-up notifikasi, bunyi lonceng & badge jumlah pengajuan` -> (`Admin / Petugas`) `Melihat notifikasi lalu menekan tautan ke halaman Pengajuan Buku (atau menutup)` -> (`Sistem`) `Menyimpan ID pengajuan terakhir dilihat di localStorage` -> `ActivityFinalNode`.
- **Daftar Pemeriksaan Elemen & Temuan** (`Total: 8 aspek` — `BENAR: 2`, `BENAR DENGAN CATATAN: 1`, `SALAH: 5`, `TIDAK TERVERIFIKASI: 0`):
  1. **[BENAR]** Interval polling `4 detik` (`4000 ms`, `notifikasi_pengajuan_admin.php:575, 825`), guard admin `403` JSON `'Akses ditolak.'` (`api_cek_pengajuan_admin.php:13-17`), serta kueri `COUNT(*)` pengajuan `'menunggu'` + ambil 1 pengajuan terbaru (`api_cek_pengajuan_admin.php:25-42`).
  2. **[BENAR]** Deteksi `latestId > lastSeenId` via `localStorage`, bunyi lonceng 4 nada Web Audio API (`notifikasi_pengajuan_admin.php:580-606`), modal pop-up, dan pencatatan ID terakhir dilihat saat ditindaklanjuti/ditutup (`:738-760, 808-814`).
  3. **[BENAR DENGAN CATATAN]** *(Rendah)* Swimlane `Admin / Petugas` dan `Sistem` sah (`InitialNode` di `Sistem` wajar untuk skrip pemantauan latar saat halaman admin terbuka).
  4. **[SALAH]** *(Tinggi — Cacat Visual Tabrakan Garis Konektor)* Garis loop-back cabang `Tidak` dari Decision `"Ada pengajuan baru (ID > terakhir dilihat)?"` ditarik lurus ke atas **menabrak/menembus kotak `"Menolak akses (403 Akses ditolak)"`**, sehingga tampak rancu seolah-olah kotak 403 memiliki panah masuk dari bawah dan panah keluar kembali ke polling!
  5. **[SALAH]** *(Sedang — Urutan Update Badge Sidebar Keliru)* Di PNG, pembaruan `badge jumlah pengajuan` diletakkan di dalam cabang `Ya` (`ID > terakhir dilihat`). Padahal di `notifikasi_pengajuan_admin.php:797-806`, `updateSidebarBadge(countMenunggu)` dijalankan **pada setiap siklus polling sebelum mengecek `latestId > lastSeenId`** (dan bila `countMenunggu === 0`, badge disembunyikan serta modal yang sedang terbuka otomatis ditutup).
  6. **[SALAH]** *(Sedang — Percabangan Buka `pengajuan_buku.php` vs Tutup `"Nanti Saja"` Tidak Dibedakan)* Di `notifikasi_pengajuan_admin.php:555-563, 738-760`, menekan `"Lihat & Proses Pengajuan"` menyimpan ID terakhir dilihat lalu **mengarahkan ke `pengajuan_buku.php`** (atau me-reload halaman bila sudah di sana), sedangkan menekan `"Nanti Saja"` hanya menutup modal dan siklus polling 4 detik tetap berjalan di latar belakang.
  7. **[SALAH]** *(Rendah — Detail Teknis di Kotak Aksi)* Menulis nama berkas `api_cek_pengajuan_admin.php`, `JSON`, dan `localStorage` secara harfiah sebagai label aksi.
  8. **[SALAH]** *(Tinggi — Desinkronisasi PUML)* Berkas `.puml` menunjuk file fiktif `admin/check_notif.php` dan mengklaim validasi CSRF (`activity_33_notifikasi_pengajuan.puml:10, 21, 23`).
- **Saran Perbaikan Tertulis**:
  - Jalurkan garis loop-back polling di sisi kiri atau di luar batas kanan kotak 403 agar tidak menabrak elemen mana pun; pindahkan aksi `Memperbarui angka badge pengajuan di sidebar` ke **sebelum** decision `Ada pengajuan baru yang belum dilihat?`; pecah tindak lanjut Admin menjadi `[Lihat & Proses]` -> buka halaman `pengajuan_buku.php` vs `[Nanti Saja / Tutup]` -> tutup pop-up & lanjutkan polling; dan tulis ulang `.puml` sesuai kode aktual.

---

### 15. Diagram #34 — Mengelola Peta Lokasi & Jam Operasional
- **Judul & Path Gambar / Sumber**:
  - Judul di PNG: `Activity Diagram — Mengelola Peta Lokasi & Jam Operasional` (`Prasyarat: admin sudah login · file: pengaturan_lokasi.php, index.php`)
  - Path PNG: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_34_pengaturan_lokasi.png`
  - Path PUML: `c:\laragon\www\aksanova\UML\Activty\Lengkap\activity_34_pengaturan_lokasi.puml`
- **File PHP Aktual**: `pengaturan_lokasi.php` *(dibaca saat pemuatan halaman publik oleh `index.php`)*.
- **Kecocokan PNG vs PUML**: **TIDAK COCOK**.
  - `.puml` berjudul `Pengaturan Kontak, Peta Lokasi & Jam Operasional / File: admin/lokasi.php` (`:10, 23`), berisi template generik dengan berkas fiktif `admin/lokasi.php`.
- **Rincian Apa yang Tampak di PNG**:
  - **Swimlane**: `Admin / Petugas` (kiri) dan `Sistem` (kanan).
  - **Alur**: `InitialNode` (`Admin / Petugas`) -> `Membuka halaman Pengaturan Peta & Jam Buka` -> (`Sistem`) `Menampilkan formulir peta, kontak instansi, jam harian & status operasional saat ini` -> (`Admin / Petugas`) `Mengubah lokasi/peta, jam operasional, kontak & status layanan perpustakaan (tersimpan otomatis)` -> (`Sistem`) `Mendeteksi perubahan input & memvalidasi data instansi (nama sekolah & alamat)` -> Decision `Nama & alamat terisi?`:
    - `[Tidak]` -> `Menampilkan pesan galat: nama & alamat wajib diisi, perubahan belum tersimpan` -> loop-back ke `Mengubah lokasi/peta...`.
    - `[Ya]` -> `Menyimpan pengaturan ke tabel pengaturan via AJAX tanpa reload halaman` -> `Menampilkan indikator status tersimpan (toast & status pill) & memutakhirkan live preview` -> `Memperbarui chip status layanan & kartu peta secara realtime pada etalase utama (index.php)` -> (`Admin / Petugas`) `Melihat indikator tersimpan & pratinjau peta serta jam operasional yang terbarui` -> `ActivityFinalNode`.
- **Daftar Pemeriksaan Elemen & Temuan** (`Total: 8 aspek` — `BENAR: 3`, `BENAR DENGAN CATATAN: 1`, `SALAH: 4`, `TIDAK TERVERIFIKASI: 0`):
  1. **[BENAR]** Swimlane `Admin / Petugas` dan `Sistem` sah (tidak menjadikan Google Maps sebagai swimlane aktor eksternal).
  2. **[BENAR]** Alur *auto-save* AJAX (`simpan_lokasi_jam`, debounce `700 ms` di `pengaturan_lokasi.php:2241`), validasi wajib `lokasi_sekolah` & `lokasi_alamat` (`:152-155`), penyimpanan ke tabel `pengaturan` (`:156-179`), serta pembaruan indikator tersimpan & *live preview* peta/status di halaman admin (`:193-245, 2273-2304`).
  3. **[BENAR]** Kualitas visual PNG sangat bersih, font seragam, garis ortogonal utuh tanpa border terpotong maupun mojibake.
  4. **[BENAR DENGAN CATATAN]** *(Rendah)* Pemeriksaan `csrf_verify()` (`pengaturan_lokasi.php:72-80`) disederhanakan (tidak digambar eksplisit).
  5. **[SALAH]** *(Sedang — Klaim Keliru Push Realtime ke `index.php`)* Kotak `"Memperbarui chip status layanan & kartu peta secara realtime pada etalase utama (index.php)"` menyesatkan secara teknis: saat Admin menyimpan di `pengaturan_lokasi.php`, sistem **hanya** menyimpan 20 kunci ke DB dan memutakhirkan *live preview* di halaman `pengaturan_lokasi.php:2277-2304` saja; `index.php:134-215` **tidak memiliki** WebSocket/SSE/polling AJAX (`0` `fetch()` di `index.php`) dan baru merender perubahan saat pengunjung memuat/me-refresh `index.php`.
  6. **[SALAH]** *(Sedang — Logika Deteksi Cerdas Peta & Mode Operasional Tidak Tergambar)* Tidak memperlihatkan 2 logika bisnis khas di `pengaturan_lokasi.php`: (a) deteksi otomatis input peta tunggal (`lokasi_map_input` kosong -> default `'SMK Negeri 1 Rongga, Bandung Barat'`; berisi `<iframe src=...>` / `google.com/maps/embed` -> mode `embed`; selain itu -> mode `query`, `:82-101`); serta (b) penentuan 3 mode operasional (`otomatis` berdasarkan jadwal Senin–Minggu WIB, `buka_manual`, `tutup_libur`, `:121, 208-245`) dan pembangkitan otomatis ringkasan `lokasi_jam` (`:125-150`).
  7. **[SALAH]** *(Rendah — Detail Teknis di Kotak Aksi)* Menyebut istilah teknis `via AJAX`, `toast & status pill`, dan nama berkas `(index.php)` di dalam kotak aksi.
  8. **[SALAH]** *(Tinggi — Desinkronisasi PUML)* Berkas `.puml` menunjuk file fiktif `admin/lokasi.php` (`activity_34_pengaturan_lokasi.puml:10, 23`).
- **Saran Perbaikan Tertulis**:
  - Hapus kotak `"Memperbarui chip status layanan & kartu peta secara realtime pada etalase utama (index.php)"` (cukup pada `pengaturan_lokasi.php` saja, karena pembacaan `index.php` terjadi pada alur kunjungan halaman depan); tambahkan langkah/percabangan deteksi mode input peta (`iframe embed` vs `query pencarian`) dan perhitungan status layanan (`otomatis` jadwal WIB / `buka_manual` / `tutup_libur`) sebelum menyimpan 20 pengaturan; bersihkan istilah teknis; dan tulis ulang `.puml` agar cocok 100% dengan `.png`.
---

## 5. Tabel Temuan Masalah & Ketidaksesuaian Terkonsolidasi (Activity Diagram)

| ID | Dokumen + Lokasi | Isi Dokumen | Fakta Kode | Bukti Path:Baris | Keparahan | Perbaikan yang Disarankan |
|---|---|---|---|---|:---:|---|
| **[ACT-01]** | Seluruh 23 file `.puml` di `UML/Activty/Lengkap/` | File `.puml` berisi 37 baris kode generik yang merujuk subdirektori fiktif `admin/*.php`, `member/*.php`, dan `api/*.php`. | Seluruh arsitektur kode AKSA NOVA adalah flat di root folder. Tidak ada file `admin/buku.php`, `admin/kirim_wa.php`, dll. | [`docs/audit_v2/nonexistent_refs.json`](file:///c:/laragon/www/aksanova/docs/audit_v2/nonexistent_refs.json) | **Kritis** | Tulis ulang seluruh berkas `.puml` agar merefleksikan alur aktivitas spesifik dan file PHP riil, bukan stub generik copy-paste. |
| **[ACT-02]** | `UML/Activty/Lengkap/activity_24_pengingat_keterlambatan_wa.png` | Menggambar swimlane `Penjadwal (Cron)` dan percabangan keputusan `[Salah tandai? Ya -> Klik Batal WA -> wa_terkirim = 0]`. | Cron bukan aktor sah (melanggar ATURAN BESI #6). Di kode PHP [`telah_dipinjam.php`](file:///c:/laragon/www/aksanova/telah_dipinjam.php), tidak ada fitur pembatalan WA (`batal_wa`). Tombol WA hanya membuka URL eksternal `https://wa.me/...`. | [`telah_dipinjam.php:380-420`](file:///c:/laragon/www/aksanova/telah_dipinjam.php#L380-L420) | **Kritis** | Hapus swimlane Cron (jadikan Pustakawan sebagai pemicu pengingat), dan hapus percabangan fiktif pembatalan WA (`batal_wa`). |
| **[ACT-03]** | `UML/Activty/Lengkap/A08_Lupa_Kartu_OTP.png` | Aktivitas akhir menyatakan kartu anggota baru diterbitkan dengan format `(QR Code)`. Urutan rate limit Langkah 1 mendahulukan cek email sebelum SELECT user. Timeout Langkah 3 tidak memodelkan auto-unfreeze. | Kartu anggota menggunakan format **Barcode Code 128** (`barcode128.php`). Pengecekan limit user memerlukan user_id hasil SELECT. Timeout Langkah 3 (>10m) otomatis mengaktifkan kembali kartu `card_status='active'`. | [`kartu_anggota.php:108`](file:///c:/laragon/www/aksanova/kartu_anggota.php#L108), [`lupa_kartu.php:105, 262`](file:///c:/laragon/www/aksanova/lupa_kartu.php#L105) | **Tinggi** | Ganti teks `(QR Code)` menjadi `Barcode Code 128` dan perbaiki urutan pengecekan rate limit serta auto-unfreeze pada Langkah 3. |
| **[ACT-04]** | `UML/Activty/Lengkap/activity_09_edit_kartu_anggota.png` | Memodelkan bahwa anggota dapat mengedit kolom `kelas`. Mengabaikan verifikasi `old_password` saat ganti password dan pengeditan `username` / `nik`. | Kolom `kelas` berstatus disabled/readonly pada form anggota biasa di [`edit_kartu.php:210`](file:///c:/laragon/www/aksanova/edit_kartu.php#L210). Penggantian password mewajibkan verifikasi kata sandi lama (`old_password`) di `:45-65`. | [`edit_kartu.php:45-65, 210`](file:///c:/laragon/www/aksanova/edit_kartu.php#L45-L65) | **Tinggi** | Hapus pengeditan kelas dari swimlane anggota, dan tambahkan aktivitas verifikasi kata sandi lama saat memperbarui password. |
| **[ACT-05]** | `UML/Activty/Lengkap/activity_12_telusuri_katalog_detail.png` | Swimlane pengguna mengklaim Admin dapat membuka `daftar_buku.php` untuk mencari katalog. | Jika admin membuka `daftar_buku.php`, baris 12-16 secara tegas me-redirect admin ke `halaman_admin.php`: `header('Location: halaman_admin.php'); exit;`. Admin mengelola dan melihat katalog di `halaman_admin.php`. | [`daftar_buku.php:12-16`](file:///c:/laragon/www/aksanova/daftar_buku.php#L12-L16) | **Sedang** | Hapus peran admin dari swimlane `daftar_buku.php` atau jelaskan pengalihan otomatis ke halaman admin. |
| **[ACT-06]** | `UML/Activty/03_activity_pengajuan_peminjaman_anggota.png` | Mengklaim form pengajuan peminjaman menampilkan informasi lokasi rak buku secara dinamis. | Pada file [`pengajuan_peminjaman.php:59, 69, 204`](file:///c:/laragon/www/aksanova/pengajuan_peminjaman.php#L59), kueri SQL tidak mengambil kolom `rak` sehingga elemen `<span id="previewRak">` tidak pernah terisi data rak buku. | [`pengajuan_peminjaman.php:59, 69, 204`](file:///c:/laragon/www/aksanova/pengajuan_peminjaman.php#L59) | **Sedang** | Perbaiki diagram agar mencerminkan data aktual yang ditampilkan, atau tambahkan kolom rak ke kueri PHP di masa depan. |
| **[ACT-07]** | `UML/Activty/07_activity_tambah_buku_isbn.png` | Memiliki swimlane independen ketiga bernama `Layanan ISBN (API)`. | Layanan API eksternal bukan swimlane aktor sistem (melanggar ATURAN BESI #6). Panggilan Google Books API dieksekusi langsung oleh server backend `halaman_admin.php` di dalam swimlane Sistem. | [`halaman_admin.php:38-95`](file:///c:/laragon/www/aksanova/halaman_admin.php#L38-L95) | **Sedang** | Gabungkan aktivitas pemanggilan API ke dalam swimlane Sistem (Server PHP). |
| **[ACT-08]** | `UML/Activty/Lengkap/activity_33_notifikasi_pengajuan.png` | Garis alur konektor membelah menembus tepat di tengah kotak teks *"Menolak akses (403)"*. | Cacat perutean panah visual pada perender diagram PNG. | [`UML/Activty/Lengkap/activity_33_notifikasi_pengajuan.png`](file:///c:/laragon/www/aksanova/UML/Activty/Lengkap/activity_33_notifikasi_pengajuan.png) | **Rendah** | Perbaiki perutean panah konektor agar mengitari kotak teks atau menempel pada connection point yang rapi. |

---

## 6. Rekomendasi Perbaikan Activity Diagram (Hanya Ditulis, Tidak Dikerjakan)

1. **Sinkronisasi Total PUML ke Kode Nyata:**
   Ganti seluruh stub PUML generik 37 baris di folder `Lengkap/` dengan alur aktivitas riil yang memanggil file PHP di root direktori.
2. **Koreksi Swimlane Non-Manusia dan Fitur Halusinasi:**
   Hapus swimlane `Penjadwal (Cron)` dan fitur `batal_wa` di activity 24, serta swimlane `Layanan ISBN (API)` di activity 07.
3. **Penyelarasan Kartu Barcode 128:**
   Ubah seluruh teks 'QR Code' pada A08 menjadi 'Barcode Code 128'.
