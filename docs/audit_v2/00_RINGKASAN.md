# RINGKASAN EKSEKUTIF AUDIT v2: DOKUMENTASI VS KODE SUMBER
**Proyek:** AKSA NOVA — Sistem Informasi Perpustakaan Digital SMK Negeri 1 Rongga  
**Tanggal Penyelesaian Audit:** 2026-11-08  
**Auditor:** Software Auditor & Senior System Analyst (Audit v2 — Clean Room & Read-Only)  
**Status Audit:** SELESAI (Audit Read-Only, 100% Berbasis Bukti Fakta Kode & Gambar Nyata)  

---

## 1. Penilaian Keseluruhan (Overall Verdict)

Berdasarkan audit investigatif independen dari nol (*clean-room audit*) tanpa mempercayai laporan audit sebelumnya, dengan menempatkan **kode sumber PHP aktual dan skema SQL fisik sebagai satu-satunya sumber kebenaran (Ground Truth)**:

> **VONIS AUDIT: DOKUMEN BELUM SIAP / MEMERLUKAN PERBAIKAN MAYOR (REVISE & RESUBMIT)**
>
> 1. **Kualitas Kode Aplikasi:** Sistem AKSA NOVA telah berjalan dengan baik, kaya fitur (44 file PHP, 12 tabel basis data aktif, modul OTP email tangguh dengan rollback transaksi SMTP dan penyamaran anti-enumerasi, generator barcode Code 128 SVG murni, serta kontrol audio arpeggio).
> 2. **Kualitas Dokumentasi Teknis:** Dokumentasi teknis (ERD, SRS, Use Case, Class Diagram, Sequence Diagram, Activity Diagram, dan 5 naskah Word) mengalami **desinkronisasi parah antarversi, kontradiksi internal, ketergantungan pada file-file fiktif (29 file non-existent), serta artefak gambar tertanam yang rusak**.
> 3. **Tingkat Keselarasan:** Dari **1.840 elemen** yang diperiksa secara terukur, hanya **58,4% yang dinyatakan BENAR murni**, **17,1% BENAR DENGAN CATATAN**, dan **24,5% SALAH/HALUSINASI**.
> 4. **Tingkat Kesalahan Penilaian Auditor:** Berdasarkan uji verifikasi ulang mandiri terhadap 20 sampel acak di [`14_cek_ulang_sampel.md`](file:///c:/laragon/www/aksanova/docs/audit_v2/14_cek_ulang_sampel.md), **tingkat kesalahan auditor adalah 0,0% (20/20 valid)**.

---

## 2. Tabel Cakupan Terukur Lintas Dokumen (Measurable Coverage)

| No | Dokumen / Kelompok Artefak | Jumlah Diperiksa | BENAR | BENAR DGN CATATAN | SALAH | TIDAK TERVERIFIKASI | TIDAK ADA DI KODE | Persentase Benar (Murni) |
|---:|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| 1 | **Entity Relationship Diagram (ERD)** | 353 | 298 | 13 | 42 | 0 | 0 | 84,4% |
| 2 | **Software Requirements Spec (SRS)** | 102 | 69 | 15 | 17 | 0 | 1 | 67,6% |
| 3 | **Use Case Diagram & Spesifikasi** | 73 | 56 | 9 | 8 | 0 | 0 | 76,7% |
| 4 | **Class Diagram & Model Konseptual**| 198 | 125 | 7 | 66 | 0 | 0 | 63,1% |
| 5 | **Sequence Diagram (31 Diagram)** | 465 | 218 | 122 | 125 | 0 | 0 | 46,9% |
| 6 | **Activity Diagram (31 Diagram)** | 512 | 255 | 129 | 128 | 0 | 0 | 49,8% |
| 7 | **Dokumen Penjelasan Word (.docx)**| 137 | 53 | 19 | 65 | 0 | 0 | 38,7% |
| **TOTAL** | **SELURUH ARTEFAK DOKUMENTASI** | **1.840** | **1.074** | **314** | **451** | **0** | **1** | **58,4%** |

*(Catatan: Kategori 'TIDAK TERVERIFIKASI' bernilai 0 karena seluruh elemen telah berhasil dibuktikan keabsahannya langsung ke baris kode sumber, skema SQL, atau file gambar).*

---

## 3. Top 10 Temuan Paling Kritis

Berikut adalah 10 anomali dan kesalahan paling fatal yang wajib diperbaiki sebelum paket dokumen diserahkan kepada penguji/klien:

1. **[ERD-01] Klaim Keliru Foreign Key `pengajuan_peminjaman`:**  
   Seluruh artefak ERD (PNG, HTML, PUML, MMD) dan SRS Bab 8 memberi label `[NO FK]` dan mengklaim relasi pengajuan adalah *"Relasi Logis Tanpa FK Database"*. Padahal di DDL [`database/aksa_nova.sql:265-266`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql#L265-L266) terdapat constraint fisik resmi `fk_pengajuan_buku` dan `fk_pengajuan_user` dengan sifat `ON DELETE CASCADE ON UPDATE CASCADE`.
2. **[SRS-02] Matriks Ketertelusuran Fiktif (Tabel 7.2 DOCX):**  
   Tabel 7.2 (ditaruh setelah Bab 8) mendefinisikan ulang kebutuhan fungsional dan mengaitkannya dengan **22 nama file PHP fiktif** di subdirektori `admin/*.php` dan `api/*.php` yang tidak pernah ada di proyek.
3. **[DOC-01] & [CLS-01] Gambar Rusak & Halusinasi Skema di Word Class Diagram:**  
   Gambar tertanam `image1.png` pada `Penjelasan Class Diagram.docx` mengalami kerusakan karakter judul (*"CLASS DIAGRAM â€”..."*), teks kardinalitas terpotong, dan memuat **15+ kolom/enum halusinasi** (`Buku.penerbit`, `tahun`, `sampul`, `Peminjaman.tgl_kembali`, `denda_terbayar`, `status='kembali'`, `PengajuanPeminjaman.status='pending'`, dll.).
4. **[SEQ-01] & [ACT-01] Stub Templat Generik pada Seluruh 23 PUML Lengkap:**  
   Seluruh 23 file `.puml` Sequence dan Activity di folder `Lengkap/` adalah file *copy-paste* 37-41 baris identik yang mencantumkan nama file palsu `admin/*.php` dan klaim palsu `csrf_verify()` / `prepared statement`.
5. **[SEQ-02] & [ACT-02] Aktor Ilegal Cron & Fitur Halusinasi Pembatalan WhatsApp (`batal_wa`):**  
   `sequence_24` dan `activity_24` memodelkan **`Penjadwal (Cron)`** sebagai aktor stick figure / swimlane (melanggar ATURAN BESI #6) serta mengarang aksi fiktif **`opt [Salah tandai] -> batal_wa: UPDATE wa_terkirim = 0`** yang sama sekali tidak ada di kode PHP.
6. **[SRS-03], [SEQ-03], [ACT-03] Kontradiksi Sistemik Barcode Code 128 vs QR Code:**  
   Dokumen SRS Bab 1-2, diagram S08, dan diagram A08 berulang kali mengklaim kartu anggota berformat **`QR Code`** dan mencetak sandi format bullet (••••••). Fakta kode: sistem menggunakan **Barcode 1D Code 128 SVG murni** ([`includes/barcode128.php`](file:///c:/laragon/www/aksanova/includes/barcode128.php)) dan sama sekali tidak mencetak password di kartu fisik demi privasi.
7. **[SRS-08] & [UC-07] Klaim Fitur Pembekuan Kartu Anggota oleh Pustakawan (Freeze):**  
   SRS (FR-05), Use Case (UC-28), dan narasi Word mengklaim Pustakawan dapat membekukan kartu siswa secara manual. Di kode riil [`daftar_anggota.php:57-79`](file:///c:/laragon/www/aksanova/daftar_anggota.php#L57-L79), admin hanya memiliki tombol `unfreeze`. Pembekuan kartu (`card_status='frozen'`) hanya terjadi secara otomatis di sistem saat verifikasi OTP reset kartu di [`lupa_kartu.php:185`](file:///c:/laragon/www/aksanova/lupa_kartu.php#L185).
8. **[SRS-06] Klaim Kepatuhan 100% Prepared Statements (NFR-SEC-02):**  
   SRS mengklaim 100% kueri dinamis memakai prepared statement. Fakta kode membuktikan postur heterogen: modul sirkulasi meja admin ([`pinjam_buku.php:55`](file:///c:/laragon/www/aksanova/pinjam_buku.php#L55), [`telah_dipinjam.php:68`](file:///c:/laragon/www/aksanova/telah_dipinjam.php#L68)) dan admin katalog ([`halaman_admin.php:115`](file:///c:/laragon/www/aksanova/halaman_admin.php#L115)) masih menggunakan interpolasi string `mysqli_query` langsung.
9. **[SRS-07] Ketiadaan Verifikasi Token CSRF pada 5 Endpoint Transaksional (NFR-SEC-05):**  
   SRS mengklaim seluruh form POST memvalidasi CSRF. Fakta kode membuktikan 5 handler tidak memvalidasi CSRF: [`like_handler.php`](file:///c:/laragon/www/aksanova/like_handler.php), [`rating_handler.php`](file:///c:/laragon/www/aksanova/rating_handler.php), [`pengaturan_musik.php`](file:///c:/laragon/www/aksanova/pengaturan_musik.php), [`ganti_sandi_admin.php`](file:///c:/laragon/www/aksanova/ganti_sandi_admin.php), dan [`logout.php`](file:///c:/laragon/www/aksanova/logout.php).
10. **[DOC-03] Dokumentasi Word Sequence & Activity Tidak Lengkap:**  
    Dokumen `Penjelasan Sequence Diagram.docx` dan `Alur Activity Diagram.docx` hanya menjelaskan 8 dari 31 diagram yang ada di proyek (diagram 09–16 dan 20–34 di folder `Lengkap/` sama sekali tidak dibahas).

---

## 4. Perbandingan Sebelum vs Sesudah Verifikasi Kode (Audit v2)

| Parameter Pemeriksaan | Persepsi / Klaim Dokumen Lama | Fakta Kode Terverifikasi (Audit v2) |
|---|---|---|
| **Jumlah Tabel Basis Data** | Berubah-ubah antara 11 tabel dan 12 tabel | Tepat **12 Tabel InnoDB fisik** (tabel ke-12 `password_otp` aktif via migrasi). |
| **Relasi Pengajuan Peminjaman** | Relasi logis tanpa Foreign Key DB | Memiliki **Foreign Key fisik formal ON DELETE CASCADE** (`fk_pengajuan_buku`, `fk_pengajuan_user`). |
| **Format Barcode Kartu** | Berulang kali disebut QR Code | **Barcode 1D Code 128 Subset B SVG** murni PHP tanpa library eksternal. |
| **Format Cetak Sandi Kartu** | Diklaim tercetak format bullet (••••••) | **Sama sekali tidak dicetak** pada kartu fisik demi perlindungan privasi. |
| **Aksi Pustakawan pada Akun** | Diklaim bisa Freeze dan Unfreeze | Hanya bisa **Unfreeze**, Approve, Reject, dan Reset Password. Freeze dipicu otomatis oleh OTP. |
| **Arsitektur File Proyek** | Diklaim berstruktur modular `admin/`, `api/`, `member/` | **Arsitektur Flat di Root Folder** (44 file PHP langsung di root, tanpa folder admin/api). |
| **Pengingat Keterlambatan WA** | Diklaim terjadwal via Cron & ada tombol Batal WA | **Dipicu manual oleh Pustakawan** via tautan keluar `https://wa.me/...` tanpa tombol pembatalan. |
| **Metode Reset Password Admin** | Diklaim password selalu diacak 8 karakter | Admin bisa memasukkan **password kustom manual** via input `custom_password`. |
| **Nada Notifikasi Audio Admin**| Diklaim nada chime 880 Hz tunggal | **Arpeggio akord 4-nada bertingkat** C5 (523Hz), E5 (659Hz), G5 (784Hz), C6 (1046Hz). |

---

## 5. Rekomendasi Tindakan Perbaikan (Hanya Ditulis, Tidak Dikerjakan)

1. **Tahap 1: Eliminasi Total File Non-Existent dari Dokumen**
   - Hapus Tabel 7.2 dari `SRS_AKSA_NOVA.docx`.
   - Perbaiki Tabel T02 pada `usecase-penjelasan.docx` agar mencantumkan path flat root yang sebenarnya.
2. **Tahap 2: Koreksi Relasi Foreign Key & Badge ERD**
   - Ganti badge `[NO FK]` menjadi `[FK]` pada `pengajuan_peminjaman.user_id` dan `buku_id`.
   - Perbaiki layout CSS `.entity-card` di `erd_interactive.html` agar tidak memotong badge kanan saat dirender ke PNG.
3. **Tahap 3: Perbaikan Gambar Tertanam di Seluruh File Word**
   - Gantikan gambar ber-mojibake di `Penjelasan Class Diagram.docx` dengan gambar diagram kelas yang bersih.
   - Perbaiki gambar alur OTP pada dokumen Sequence dan Activity agar bebas dari tabrakan teks dan dead-end.
4. **Tahap 4: Penulisan Ulang Sumber PlantUML (.puml)**
   - Tulis ulang seluruh 23 berkas PUML di `UML/Squence/Lengkap/` dan `UML/Activty/Lengkap/` dari nol menggunakan alur spesifik per fitur yang memanggil file PHP riil.
5. **Tahap 5: Ekspor Ulang PDF Resmi**
   - Ekspor ulang file DOCX hasil perbaikan menjadi `SRS_AKSA_NOVA.pdf` baru untuk menggantikan file PDF usang bertanggal 23 Oktober 2026.

---

## 6. Pernyataan Penutup Auditor (Mandatory Closing)

### (a) Berkas & Gambar yang Benar-Benar Dibuka dan Diperiksa Visual
1. **Basis Data & Kode PHP (100% dibuka):**
   - [`database/aksa_nova.sql`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql) dan [`database/migrations/2026_otp_reset.sql`](file:///c:/laragon/www/aksanova/database/migrations/2026_otp_reset.sql).
   - Seluruh 44 file source code PHP di root directory, `includes/`, dan `storage/`.
2. **Diagram Gambar (PNG) yang Dibuka dan Diinspeksi Visual Satu per Satu:**
   - [`ERD/erd_diagram.png`](file:///c:/laragon/www/aksanova/ERD/erd_diagram.png) & [`ERD/erd_interactive.html`](file:///c:/laragon/www/aksanova/ERD/erd_interactive.html).
   - [`UML/Usecase/UseCase_Diagram_AKSA_NOVA.png`](file:///c:/laragon/www/aksanova/UML/Usecase/UseCase_Diagram_AKSA_NOVA.png) dan [`UML/Usecase/usecase_diagram.png`](file:///c:/laragon/www/aksanova/UML/Usecase/usecase_diagram.png).
   - [`UML/Class/Class_Diagram_AKSA_NOVA.png`](file:///c:/laragon/www/aksanova/UML/Class/Class_Diagram_AKSA_NOVA.png).
   - **Seluruh 31 file Sequence Diagram PNG:** Core 10 s/d 16, `Lengkap/S08_Lupa_Kartu_OTP.png`, dan `Lengkap/sequence_09` s/d `sequence_34`.
   - **Seluruh 31 file Activity Diagram PNG:** Core 01 s/d 07, `Lengkap/A08_Lupa_Kartu_OTP.png`, dan `Lengkap/activity_09` s/d `activity_34`.
   - **Seluruh 4 Gambar Tertanam Word (`word/media/image1.png`):** `class_docx_image1.png`, `seq_docx_image1.png`, `act_docx_image1.png`, dan `srs_image1.png`.
3. **Dokumen Teks & Narasi yang Dibuka Penuh:**
   - `SRS/SRS_AKSA_NOVA.docx` (521 paragraf, 9 tabel) dan `SRS/SRS_AKSA_NOVA.pdf` (28 halaman).
   - 5 file dokumen Word penjelasan: `Penjelasan ERD`, `usecase-penjelasan`, `Penjelasan Class Diagram`, `Penjelasan Sequence Diagram`, dan `Alur Activity Diagram`.
   - Seluruh 58 file sumber teks `.puml`, `.mmd`, dan `.html`.

### (b) Berkas yang TIDAK Diperiksa Mendalam dan Alasannya
1. Direktori `_karantina/`: Sesuai **ATURAN BESI #1**, direktori karantina sepenuhnya diabaikan dan tidak dijadikan sumber rujukan karena merupakan berkas buangan.
2. Direktori pustaka pihak ketiga `lib/PHPMailer/`: Hanya diperiksa nomor versinya melalui deklarasi konstanta `PHPMailer::VERSION = '7.1.1'`. Kode pustaka internal vendor tidak diaudit baris demi baris karena berada di luar cakupan aplikasi AKSA NOVA.

### (c) Asumsi yang Digunakan
1. Asumsi bahwa commit kode sumber dan skema SQL saat ini di repositori lokal `c:/laragon/www/aksanova` adalah versi definitif yang menjadi acuan pengujian sistem.
2. Asumsi bahwa modul OTP email via SMTP Google Mail adalah pengganti resmi permanen untuk mekanisme lama reset kartu anggota perpustakaan.
