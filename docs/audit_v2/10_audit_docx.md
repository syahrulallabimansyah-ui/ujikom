# LAPORAN AUDIT v2: DOKUMEN PENJELASAN (.DOCX) & GAMBAR TERTANAM
**Proyek:** AKSA NOVA — Sistem Informasi Perpustakaan Digital SMK Negeri 1 Rongga  
**Tanggal Audit:** 2026-11-08  
**Auditor:** Software Auditor & System Analyst (Audit v2 — Clean Room & Read-Only)  
**Status Audit:** SELESAI (Audit Read-Only)  

---

## 1. Ringkasan Eksekutif Dokumen Narasi (.docx)

Audit terhadap seluruh 5 dokumen penjelasan narasi bertipe Microsoft Word (.docx) AKSA NOVA mencakup:
1. [`ERD/Penjelasan ERD - AKSA NOVA.docx`](file:///c:/laragon/www/aksanova/ERD/Penjelasan%20ERD%20-%20AKSA%20NOVA.docx) (303.807 byte, 2026-11-03 08:28:29).
2. [`UML/Usecase/usecase-penjelasan.docx`](file:///c:/laragon/www/aksanova/UML/Usecase/usecase-penjelasan.docx) (240.292 byte, 2026-11-03 08:31:40).
3. [`UML/Class/Penjelasan Class Diagram - AKSA NOVA.docx`](file:///c:/laragon/www/aksanova/UML/Class/Penjelasan%20Class%20Diagram%20-%20AKSA%20NOVA.docx) (110.158 byte, 2026-11-03 08:35:10).
4. [`UML/Squence/Penjelasan Sequence Diagram - AKSA NOVA.docx`](file:///c:/laragon/www/aksanova/UML/Squence/Penjelasan%20Sequence%20Diagram%20-%20AKSA%20NOVA.docx) (97.747 byte, 2026-11-03 08:44:48).
5. [`UML/Activty/Alur Activity Diagram.docx`](file:///c:/laragon/www/aksanova/UML/Activty/Alur%20Activity%20Diagram.docx) (199.471 byte, 2026-11-03 08:45:55).

Serta inspeksi visual terhadap seluruh berkas gambar yang diekstrak langsung dari arsip internal `word/media/image1.png` masing-masing dokumen Word ([`scratch/docx_media/`](file:///C:/Users/Windows/.gemini/antigravity/brain/b32efb7e-9aac-4e95-b4ce-27938e353c90/scratch/docx_media/)).

**Sumber Kebenaran (Ground Truth):**
- Seluruh 44 file source code PHP ([`docs/audit_v2/02_fakta_kode.md`](file:///c:/laragon/www/aksanova/docs/audit_v2/02_fakta_kode.md)).
- Basis data DDL ([`database/aksa_nova.sql`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql) & [`database/migrations/2026_otp_reset.sql`](file:///c:/laragon/www/aksanova/database/migrations/2026_otp_reset.sql)).
- Hasil pemindaian path fiktif ([`docs/audit_v2/nonexistent_refs.json`](file:///c:/laragon/www/aksanova/docs/audit_v2/nonexistent_refs.json)).

**Temuan Utama Sistemik:**
1. **Gambar Tertanam Mengalami Kerusakan Visual & Halusinasi Parah:**
   - Pada `Penjelasan Class Diagram`, gambar `image1.png` tertanam menampilkan judul dengan teks rusak mojibake (*"CLASS DIAGRAM â€” SISTEM INFORMASI..."*), kardinalitas terpotong (*"1 .. 0..1 (CAS"*), serta mendefinisikan **15+ kolom dan enum fiktif** yang tidak pernah ada di database maupun kode.
   - Pada `Penjelasan Sequence Diagram` dan `Alur Activity Diagram`, gambar tertanam untuk alur reset kartu OTP mengalami tabrakan teks (text collisions), kotak teks tumpah keluar garis border, teks miring meluap dari belah ketupat keputusan, dan kotak hangus OTP yang terputus (dead-end).
2. **Cakupan Narasi Sequence & Activity Tidak Lengkap (Hanya 8 dari 31 Diagram):**
   - Dokumen Word Sequence dan Activity hanya menjelaskan diagram 01 s/d 08. Seluruh 23 diagram lanjutan di folder `Lengkap/` (diagram 09–16 dan 20–34) **sama sekali tidak memiliki dokumentasi narasi di Word**.
3. **Rujukan Massal File Non-Existent pada Dokumen Use Case:**
   - Pada `usecase-penjelasan.docx`, sebanyak **27 dari 34 baris pada Tabel T02 mengutip file yang tidak ada** di repositori (`admin/*.php`, `api/*.php`, `katalog.php`, `detail_buku.php`).
4. **Pengulangan Klaim Keliru:**
   - Seluruh dokumen Word secara konsisten mengulang klaim keliru bahwa `pengajuan_peminjaman` adalah logical FK tanpa FK database, mengklaim bahwa admin dapat membekukan kartu secara manual (freeze), dan mengklaim proteksi brute force login yang tidak pernah diimplementasikan.

---

## 2. Cakupan Terukur (Measurable Coverage)

| Dokumen Word (.docx) | Jumlah Elemen Diperiksa | BENAR | BENAR DGN CATATAN | SALAH | TIDAK TERVERIFIKASI | Keterangan Singkat |
|---|:---:|:---:|:---:|:---:|:---:|---|
| **Penjelasan ERD** | 24 | 18 | 3 | 3 | 0 | Bab 5.11 password_otp hilang; klaim pengajuan tanpa FK fisik |
| **usecase-penjelasan** | 42 | 11 | 4 | 27 | 0 | 27 baris Tabel T02 mengutip file fiktif; arah extend terbalik |
| **Penjelasan Class Diagram** | 35 | 12 | 4 | 19 | 0 | Gambar tertanam mojibake, teks terpotong, 15+ kolom fiktif |
| **Penjelasan Sequence Diagram** | 18 | 6 | 4 | 8 | 0 | Hanya bahas 8 diagram; gambar tertanam tumpang tindih teks |
| **Alur Activity Diagram** | 18 | 6 | 4 | 8 | 0 | Hanya bahas 8 diagram; gambar tertanam cacat layout & dead-end |
| **TOTAL AKUMULATIF** | **137** | **53 (38,7%)** | **19 (13,9%)** | **65 (47,4%)** | **0 (0,0%)** | Tingkat ketidakakuratan narasi dan gambar tertanam sangat tinggi |

---

## 3. Evaluasi Rinci per Dokumen Narasi

### A. Dokumen: `ERD/Penjelasan ERD - AKSA NOVA.docx`
- **Status Substantif:** Cukup baik dalam memetakan 12 entitas fisik pada bab pengantar dan kamus data tabel.
- **Kelemahan & Kesalahan:**
  1. *Typo Judul Bab 5:* Tertulis *"5. Relasi Antar-Entitas Fisik Fisik"*.
  2. *Subbab Hilang:* Penjelasan relasi melompat dari subbab 5.10 langsung ke Bab 6, **melewatkan subbab 5.11 untuk relasi tabel `password_otp` dengan `users`**.
  3. *Klaim Logical FK:* Menyatakan bahwa relasi `pengajuan_peminjaman` sengaja tidak diberi foreign key fisik untuk fleksibilitas, padahal DDL [`aksa_nova.sql:265-266`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql#L265-L266) membuktikan constraint fisik `ON DELETE CASCADE` aktif.
  4. *Rujukan File Fiktif:* Pada narasi detail buku merujuk file non-existent `detail_buku.php` (file riil adalah `buku_detail.php`).
  5. *Klaim Cetak Kartu:* Mengklaim bahwa kolom `plain_password` digunakan untuk mencetak kata sandi pada kartu fisik, padahal kode [`kartu_anggota.php`](file:///c:/laragon/www/aksanova/kartu_anggota.php) sama sekali tidak mencetak kata sandi.

### B. Dokumen: `UML/Usecase/usecase-penjelasan.docx`
- **Status Substantif:** Menyajikan narasi use case untuk 34 use case versi lama.
- **Kelemahan & Kesalahan:**
  1. *Rujukan Massal File Non-Existent:* Pada Tabel T02, sebanyak **27 dari 34 baris mencantumkan file di folder `admin/` dan `api/` yang tidak pernah ada di proyek**.
  2. *Gambar Tertanam Versi Lama:* Menyematkan gambar `usecase_diagram.png` versi 34 UC yang garis panah Tamu melintang menabrak teks judul boundary sistem.
  3. *Arah Relasi `<<extend>>` Terbalik:* Teks narasi mendeskripsikan *"Mengelola Data Buku <<extend>> Mencari Data Buku via ISBN"*. Secara standar UML, panah extend harus mengarah dari ekstensi ke use case dasar.
  4. *Klaim Brute Force & No Anggota:* Mengklaim login dilindungi penguncian brute force dan dapat menggunakan Nomor Anggota, padahal di `sign_in.php:40` kueri hanya mencocokkan `email` atau `username` dan tidak ada limit percobaan gagal login.
  5. *Klaim Freeze Admin:* Mengklaim admin dapat membekukan kartu di `UC-28`, padahal admin hanya bisa unfreeze di `daftar_anggota.php:74`.

### C. Dokumen: `UML/Class/Penjelasan Class Diagram - AKSA NOVA.docx`
- **Status Substantif:** Rusak berat pada gambar tertanam dan tabel kamus kelas.
- **Kelemahan & Kesalahan:**
  1. *Gambar Tertanam Rusak Fatal (`image1.png`):*
     - Judul gambar mengalami mojibake encoding: `CLASS DIAGRAM â€” SISTEM INFORMASI PERPUSTAKAAN AKSA NOVA`.
     - Teks label kardinalitas terpotong pada batas rendering: `1 .. 0..1 (CAS`.
     - Memodelkan **15+ kolom halusinasi**: `penerbit`, `tahun`, `kategori`, `deskripsi`, `sampul` pada Buku; `tgl_pinjam`, `tgl_kembali`, `denda_terbayar`, `status='kembali'` pada Peminjaman; `status='pending'` pada PengajuanPeminjaman; `admin_id`, `waktu_kirim` pada ReminderLog.
  2. *Teks Kamus Kelas Mengikuti Halusinasi Gambar:* Tabel rincian atribut di dalam teks Word menyalin kolom-kolom fiktif tersebut.
  3. *Klaim Pengajuan Tanpa FK:* Mengulang narasi bahwa pengajuan peminjaman tidak berelasi fisik di database.

### D. Dokumen: `UML/Squence/Penjelasan Sequence Diagram - AKSA NOVA.docx`
- **Status Substantif:** Tidak lengkap (hanya mencakup 8 dari 31 diagram sequence).
- **Kelemahan & Kesalahan:**
  1. *Cakupan Terbatas:* Hanya menjelaskan sequence diagram 01 s/d 08. Diagram 09 s/d 34 di folder `Lengkap/` tidak dijelaskan sama sekali.
  2. *Gambar Tertanam S08 Cacat Visual (`image1.png`):* Teks pesan dan label nomor mengalami tabrakan dan tumpang tindih visual (overlapping text).
  3. *Klaim QR Code pada Kartu:* Mendeskripsikan bahwa kartu pengganti dicetak dengan QR code (seharusnya Barcode Code 128).
  4. *Klaim Status Pending:* Mengklaim pengajuan buku memiliki status `'pending'`, padahal nilai enum riil di database adalah `'menunggu'`.

### E. Dokumen: `UML/Activty/Alur Activity Diagram.docx`
- **Status Substantif:** Tidak lengkap (hanya mencakup 8 dari 31 diagram activity).
- **Kelemahan & Kesalahan:**
  1. *Cakupan Terbatas:* Hanya menjelaskan activity diagram 01 s/d 08. Diagram 09 s/d 34 tidak dibahas.
  2. *Gambar Tertanam A08 Cacat Visual (`image1.png`):* Teks label pada decision diamond meluap keluar dari bentuk belah ketupat, teks aktivitas tumpah melampaui batas border kotak, dan aksi *"Hanguskan kode OTP"* digambar terputus tanpa panah keluar (dead-end).
  3. *Klaim Nilai Default 7 Hari & tgl_pinjam:* Mengklaim pengajuan buku mengisi `tgl_pinjam` dan default 7 hari, padahal di `pengajuan_peminjaman.php` kolom tanggal pinjam tidak ada di tabel pengajuan (hanya `tanggal_kembali`).
  4. *Klaim Scan Barcode Meja Sirkulasi:* Mengklaim pustakawan memindai barcode buku dan anggota di `pinjam_buku.php`, padahal form peminjaman langsung menggunakan input dropdown manual `user_id` dan `buku_id`.

---

## 4. Tabel Temuan Masalah & Ketidaksesuaian Terkonsolidasi (.DOCX)

| ID | Dokumen + Lokasi | Isi Dokumen | Fakta Kode | Bukti Path:Baris | Keparahan | Perbaikan yang Disarankan |
|---|---|---|---|---|:---:|---|
| **[DOC-01]** | `UML/Class/Penjelasan Class Diagram - AKSA NOVA.docx` (Embedded `image1.png` & Tabel) | Menyematkan gambar ber-mojibake (*"CLASS DIAGRAM â€”..."*), teks kardinalitas terpotong, dan memuat **15+ kolom/enum fiktif** (`penerbit`, `tahun`, `sampul`, `tgl_kembali`, `denda_terbayar`, `status='kembali'`, dll.). | Database AKSA NOVA menggunakan `tahun_terbit`, `gambar`, dan status peminjaman hanya `'dipinjam'` atau `'dikembalikan'`. Kolom-kolom fiktif tidak ada di skema SQL. | [`database/aksa_nova.sql:67-95, 144-165`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql#L67-L95) | **Kritis** | Ganti gambar `image1.png` di Word dengan `Class_Diagram_AKSA_NOVA.png` versi bersih dan perbaiki tabel atribut di naskah Word. |
| **[DOC-02]** | `UML/Usecase/usecase-penjelasan.docx` (Tabel T02) | 27 dari 34 baris tabel mencantumkan path fiktif di folder `admin/*.php` dan `api/*.php`. | Proyek tidak memiliki struktur subdirektori `admin/` atau `api/`. Seluruh script berada di root. | [`docs/audit_v2/nonexistent_refs.json`](file:///c:/laragon/www/aksanova/docs/audit_v2/nonexistent_refs.json) | **Kritis** | Tulis ulang Tabel T02 dengan mencantumkan nama file PHP root aktual. |
| **[DOC-03]** | `UML/Squence/Penjelasan Sequence Diagram.docx` & `Alur Activity Diagram.docx` | Hanya mendokumentasikan 8 dari 31 diagram. Menyematkan gambar alur OTP yang mengalami tabrakan teks, luapan kotak, dan dead-end. | Proyek memiliki 31 diagram sequence dan 31 diagram activity aktif. Gambar tertanam cacat layout parah. | [`docs/audit_v2/03_inventaris_dokumen.csv`](file:///c:/laragon/www/aksanova/docs/audit_v2/03_inventaris_dokumen.csv) | **Tinggi** | Lengkapi narasi hingga diagram #34 dan gantikan gambar tertanam dengan hasil render visual yang rapi. |
| **[DOC-04]** | `ERD/Penjelasan ERD - AKSA NOVA.docx` (Bab 5.11 & DDL) | Melewatkan subbab 5.11 untuk relasi `password_otp` dan mengklaim `pengajuan_peminjaman` adalah logical FK tanpa FK database. | Tabel `password_otp` memiliki FK fisik ke `users(id)`, dan `pengajuan_peminjaman` memiliki FK fisik formal di database. | [`database/aksa_nova.sql:265-267`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql#L265-L267) | **Tinggi** | Tambahkan subbab 5.11 untuk tabel OTP dan perbaiki penjelasan FK pengajuan peminjaman. |
| **[DOC-05]** | Seluruh 5 Dokumen Word | Berulang kali mengklaim fitur yang tidak ada di kode: admin membekukan kartu (freeze), proteksi brute force login, login nomor anggota, dan kartu ber-QR code. | Kode tidak memiliki modul freeze admin, brute force lock, login no_anggota, dan kartu menggunakan Barcode Code 128. | [`docs/audit_v2/02_fakta_kode.md`](file:///c:/laragon/www/aksanova/docs/audit_v2/02_fakta_kode.md) | **Tinggi** | Lakukan pembersihan menyeluruh terhadap klaim-klaim fiktif tersebut di seluruh naskah dokumen Word. |

---

## 5. Rekomendasi Perbaikan Dokumen Narasi (Hanya Ditulis, Tidak Dikerjakan)

1. **Penggantian Gambar Internal:**
   Ekstrak dan ganti seluruh gambar tertanam `word/media/image1.png` yang rusak, ber-mojibake, atau mengalami tabrakan teks dengan gambar diagram definitif hasil pembenahan.
2. **Sinkronisasi Path File:**
   Hapus seluruh rujukan ke folder fiktif `admin/` dan `api/`, ganti dengan path file flat root yang sah.
3. **Ekstensi Dokumentasi Sequence & Activity:**
   Tambahkan uraian narasi untuk diagram 09 s/d 34 agar dokumen penjelasan sebanding dengan inventaris diagram lengkap.
