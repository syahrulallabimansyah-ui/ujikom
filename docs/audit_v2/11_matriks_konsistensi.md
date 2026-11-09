# MATRIKS KONSISTENSI LINTAS DOKUMEN & KODE (AUDIT v2)
**Proyek:** AKSA NOVA — Sistem Informasi Perpustakaan Digital SMK Negeri 1 Rongga  
**Tanggal Audit:** 2026-11-08  
**Auditor:** Software Auditor & System Analyst (Audit v2 — Clean Room & Read-Only)  
**Status Audit:** SELESAI (Audit Read-Only)  

---

## 1. Pengantar Matriks Konsistensi

Matriks konsistensi ini memetakan setiap fitur utama AKSA NOVA ke seluruh artefak dokumentasi perangkat lunak:
- **KODE**: Implementasi PHP & SQL riil
- **SRS**: Dokumen Spesifikasi Kebutuhan (`SRS_AKSA_NOVA.docx` & `.pdf`)
- **ERD**: Diagram Hubungan Entitas (`erd_diagram.png`, `.html`, `.puml`, `.mmd`)
- **USE CASE**: Diagram Use Case (`UseCase_Diagram_AKSA_NOVA.png`, `.puml`, `.html`, `usecase-penjelasan.docx`)
- **CLASS**: Class Diagram (`Class_Diagram_AKSA_NOVA.png`, `Penjelasan Class Diagram.docx`, `image1.png`)
- **SEQUENCE**: Diagram Sequence (31 diagram `.png` & 29 `.puml`)
- **ACTIVITY**: Diagram Activity (31 diagram `.png` & 29 `.puml`)

Notasi Evaluasi:
- **[KONSISTEN]**: Selaras 100% dengan kode aktual.
- **[INKONSISTEN]**: Ada pertentangan fakta, nama file fiktif, halusinasi SQL, atau inkonsistensi antar-dokumen.
- **[TIDAK ADA]**: Fitur tidak dimodelkan pada dokumen tersebut.

---

## 2. Tabel Matriks Konsistensi Fitur

| No | Fitur Utama Sistem | Fakta Kode (Ground Truth) | Status SRS | Status ERD | Status Use Case | Status Class | Status Sequence | Status Activity | Evaluasi Konsistensi Global |
|---:|---|---|---|---|---|---|---|---|:---:|
| 1 | **Registrasi Mandiri Anggota** | `sign_up.php` (status `pending`, upload foto `temp_*`) | [KONSISTEN] (FR-01) | [KONSISTEN] (`users`) | [KONSISTEN] (UC-01) | [KONSISTEN] (`Users`) | [INKONSISTEN] (PNG subjudul klaim 'langsung approved') | [KONSISTEN] (01_activity) | **INKONSISTEN** (Subjudul sequence 10 bertolak belakang dgn status pending) |
| 2 | **Login Multi-Identitas** | `sign_in.php:40` (`email` / `username`, no brute force) | [INKONSISTEN] (klaim no_anggota & brute force) | [KONSISTEN] (`users`) | [INKONSISTEN] (asosiakan Tamu, klaim brute force) | [KONSISTEN] (`Users`) | [INKONSISTEN] (.puml halusinasi `OR no_anggota=?`) | [INKONSISTEN] (02_activity klaim brute lock) | **INKONSISTEN** (Dokumen mengarang login no_anggota & proteksi brute force) |
| 3 | **Reset Password Kartu (OTP)** | `lupa_kartu.php`, `includes/otp.php` (OTP 6 digit via email, table `password_otp`) | [KONSISTEN] (FR-04 DOCX; tapi PDF tidak ada) | [KONSISTEN] (`password_otp` ada; tapi bab 5.11 docx lewat) | [KONSISTEN] (UC-03) | [KONSISTEN] (`PasswordOtp`, helper `OtpReset`) | [INKONSISTEN] (S08 klaim QR Code, urutan rate limit terbalik) | [INKONSISTEN] (A08 klaim QR Code, dead-end Hanguskan OTP) | **INKONSISTEN** (Klaim format QR Code bertentangan dgn Barcode 128 riil) |
| 4 | **Penerbitan Kartu Anggota** | `kartu_anggota.php` (Barcode Code 128 SVG, tanpa cetak password) | [INKONSISTEN] (Bab 1-2 klaim QR code & bullet password; Bab 3-4 Barcode 128) | [KONSISTEN] (`users`) | [INKONSISTEN] (Pustakawan tdk dihubungkan ke cetak kartu) | [KONSISTEN] (`Users`) | [INKONSISTEN] (S08 klaim format QR Code) | [INKONSISTEN] (A08 klaim format QR Code) | **INKONSISTEN** (Pertentangan sistemik Barcode Code 128 vs QR Code) |
| 5 | **Edit Profil & Ganti Sandi Mandiri** | `edit_kartu.php` (username, nik, password dg old_pass; kelas disabled) | [KONSISTEN] (FR-06) | [KONSISTEN] (`users`) | [KONSISTEN] (UC-05) | [KONSISTEN] (`Users`) | [INKONSISTEN] (seq 09 klaim edit kelas, abaikan old_password) | [INKONSISTEN] (act 09 klaim edit kelas, abaikan old_password) | **INKONSISTEN** (Diagram memodelkan edit kelas yang faktanya disabled di PHP) |
| 6 | **Pencarian & Katalog Buku** | `daftar_buku.php` (user), `halaman_admin.php` (admin) | [KONSISTEN] (FR-08) | [KONSISTEN] (`buku`, `kategori`) | [KONSISTEN] (UC-07) | [KONSISTEN] (`Buku`, `Kategori`) | [INKONSISTEN] (seq 12 .puml rujuk `katalog.php` & `detail_buku.php`) | [INKONSISTEN] (act 12 klaim admin bisa buka `daftar_buku.php`) | **INKONSISTEN** (PUML rujuk file fiktif katalog.php; act 12 abaikan redirect admin) |
| 7 | **Detail Buku & Stok Fisik** | `buku_detail.php?id=...` (token masked) | [KONSISTEN] (FR-09) | [KONSISTEN] (`buku`) | [KONSISTEN] (UC-08) | [KONSISTEN] (`Buku`) | [INKONSISTEN] (seq 12 .puml rujuk `detail_buku.php`) | [INKONSISTEN] (act 12 .puml rujuk `detail_buku.php`) | **INKONSISTEN** (Nama file tertukar antara buku_detail.php dan detail_buku.php) |
| 8 | **Interaksi Like & Favorit** | `like_handler.php` (AJAX POST, toggle, no CSRF) | [KONSISTEN] (FR-11) | [KONSISTEN] (`buku_likes`, `buku_favorites`) | [KONSISTEN] (UC-10) | [KONSISTEN] (`BukuLikes`, `BukuFavorites`) | [INKONSISTEN] (seq 10 .puml rujuk `api/like.php` & `api/favorite.php`) | [INKONSISTEN] (act 10 .puml rujuk `api/like.php`) | **INKONSISTEN** (PUML mengarang folder api/; PNG abaikan ketiadaan CSRF) |
| 9 | **Rating & Ulasan Buku** | `rating_handler.php` (AJAX POST, bintang 1-5, no CSRF) | [KONSISTEN] (FR-10) | [KONSISTEN] (`buku_ratings`) | [KONSISTEN] (UC-09) | [KONSISTEN] (`BukuRatings`) | [INKONSISTEN] (seq 11 .puml rujuk `api/rate.php`) | [INKONSISTEN] (act 11 .puml rujuk `api/rate.php`) | **INKONSISTEN** (PUML mengarang endpoint api/rate.php) |
| 10 | **Pengajuan Peminjaman Daring** | `pengajuan_peminjaman.php` (status `menunggu`, URL buku_id unmasked) | [INKONSISTEN] (klaim URL masked & status pending) | [INKONSISTEN] (klaim NO FK fisik, padahal DDL punya FK CASCADE) | [KONSISTEN] (UC-12) | [INKONSISTEN] (klaim garis putus-putus tanpa FK) | [INKONSISTEN] (seq 12 .puml klaim `status='pending'`) | [INKONSISTEN] (act 03 klaim pratinjau rak aktif) | **INKONSISTEN** (Kesalahan kritis mengklaim pengajuan tanpa FK fisik di DB) |
| 11 | **Approval Pengajuan Admin** | `pengajuan_buku.php` (approve/reject, hapus riwayat) | [KONSISTEN] (FR-15) | [KONSISTEN] (`pengajuan_peminjaman`) | [KONSISTEN] (UC-13) | [KONSISTEN] (`PengajuanPeminjaman`) | [INKONSISTEN] (seq 13 .puml rujuk `admin/peminjaman.php`) | [INKONSISTEN] (act 04 .puml rujuk `admin/peminjaman.php`) | **INKONSISTEN** (PUML mengutip file fiktif admin/peminjaman.php) |
| 12 | **Peminjaman Langsung di Meja**| `pinjam_buku.php` (input manual id, stok - 1) | [KONSISTEN] (FR-16) | [KONSISTEN] (`peminjaman`) | [KONSISTEN] (UC-14) | [KONSISTEN] (`Peminjaman`) | [INKONSISTEN] (narasi docx klaim pustakawan scan barcode) | [INKONSISTEN] (narasi docx klaim pustakawan scan barcode) | **INKONSISTEN** (Narasi mengarang scan barcode di meja sirkulasi admin) |
| 13 | **Pengembalian Buku & Denda** | `telah_dipinjam.php` (status `dikembalikan`, denda_terlambat) | [KONSISTEN] (FR-17, FR-18) | [KONSISTEN] (`peminjaman`) | [INKONSISTEN] (pelunasan denda tdk mandiri di diagram) | [INKONSISTEN] (image1 docx memuat `tgl_kembali` & `denda_terbayar`) | [INKONSISTEN] (seq 15 .puml memuat kolom fiktif tgl_kembali) | [INKONSISTEN] (act 06 .puml memuat kolom fiktif tgl_kembali) | **INKONSISTEN** (Kolom SQL denda_terbayar & tgl_kembali halusinasi dokumen) |
| 14 | **Pengingat Keterlambatan WA** | `telah_dipinjam.php` (link `https://wa.me/...`, no cancel) | [KONSISTEN] (FR-23) | [TIDAK ADA] (tanpa tabel log WA) | [KONSISTEN] (UC-23) | [INKONSISTEN] (metode fiktif `+ batalWA()`) | [INKONSISTEN] (aktor Cron, alur fiktif `batal_wa`) | [INKONSISTEN] (swimlane Cron, alur fiktif `batal_wa`) | **INKONSISTEN FATAL** (Mengarang aktor Cron dan fitur fiktif batal_wa) |
| 15 | **Katalog Buku & ISBN Admin** | `halaman_admin.php` (CRUD buku & fetch Google Books) | [KONSISTEN] (FR-19, FR-20) | [KONSISTEN] (`buku`, `kategori`) | [KONSISTEN] (UC-17, UC-18) | [INKONSISTEN] (image1 docx memuat kolom fiktif penerbit, tahun) | [INKONSISTEN] (seq 16 .puml klaim `daftar_buku.php` utk admin CRUD) | [INKONSISTEN] (act 07 memuat swimlane pihak ke-3 Layanan API) | **INKONSISTEN** (PUML klaim admin CRUD di daftar_buku.php; swimlane API non-manusia) |
| 16 | **Manajemen Anggota Admin** | `daftar_anggota.php` (approve, reject, unfreeze, reset pass) | [INKONSISTEN] (klaim admin bisa freeze kartu) | [KONSISTEN] (`users`) | [INKONSISTEN] (UC-28 klaim admin bisa freeze kartu) | [KONSISTEN] (`Users`) | [INKONSISTEN] (seq 15 .puml rujuk `admin/reset_password.php`) | [INKONSISTEN] (act 15 .puml rujuk `admin/reset_password.php`) | **INKONSISTEN** (Klaim bahwa admin bisa membekukan kartu siswa secara manual) |
| 17 | **Pengaturan Musik Suasana** | `pengaturan_musik.php` (admin db, 3 endpoint AJAX) | [KONSISTEN] (FR-24) | [KONSISTEN] (`pengaturan`) | [INKONSISTEN] (use case gabung musik ke browser setting) | [KONSISTEN] (`Pengaturan`) | [INKONSISTEN] (seq 28 gambar simpan form monolitik) | [INKONSISTEN] (act 28 gambar simpan form monolitik) | **INKONSISTEN** (Use case salah domain; diagram alur abaikan 3 endpoint AJAX) |
| 18 | **Pengaturan Denda & Waktu** | `pengaturan_denda.php` (tabel `pengaturan`, csrf valid) | [KONSISTEN] (FR-22) | [KONSISTEN] (`pengaturan`) | [KONSISTEN] (UC-20) | [KONSISTEN] (`Pengaturan`) | [INKONSISTEN] (seq 23 abaikan csrf_verify dan tabel peminjaman) | [KONSISTEN] (act 23) | **INKONSISTEN** (Sequence diagram abaikan verifikasi token CSRF) |
| 19 | **Pengaturan Lokasi & Kontak**| `pengaturan_lokasi.php` (tabel `pengaturan`, Google Maps) | [KONSISTEN] (FR-25) | [KONSISTEN] (`pengaturan`) | [KONSISTEN] (UC-24) | [KONSISTEN] (`Pengaturan`) | [INKONSISTEN] (seq 34 .puml rujuk `admin/lokasi.php`) | [KONSISTEN] (act 34) | **INKONSISTEN** (PUML merujuk path fiktif admin/lokasi.php) |
| 20 | **Notifikasi Suara Audio Admin**| `notifikasi_pengajuan_admin.php` (akord C5-E5-G5-C6) | [INKONSISTEN] (klaim suara chime 880 Hz tunggal) | [TIDAK ADA] (polling memory) | [INKONSISTEN] (UC-19 sebut Sistem sbg Aktor) | [TIDAK ADA] | [INKONSISTEN] (seq 33 .puml rujuk `admin/check_notif.php`) | [INKONSISTEN] (act 33 garis menembus kotak teks 403) | **INKONSISTEN** (Spesifikasi frekuensi audio keliru; Sistem dijadikan aktor) |
| 21 | **Ekspor Laporan Excel** | `export_excel_dashboard.php` (HTML Excel, 3 sheet riil) | [INKONSISTEN] (nama sheet beda di SRS) | [TIDAK ADA] | [KONSISTEN] (UC-22) | [TIDAK ADA] | [INKONSISTEN] (seq 30 .puml rujuk `admin/ekspor_excel.php`) | [KONSISTEN] (act 30) | **INKONSISTEN** (SRS mengutip nama sheet yang berbeda dari kode aktual) |
| 22 | **Logout Sesi Dua Tahap** | `logout.php`, `settings_include.php` (modal konfirmasi) | [KONSISTEN] | [TIDAK ADA] | [KONSISTEN] | [TIDAK ADA] | [INKONSISTEN] (seq 32 abaikan modal konfirmasi AksaConfirm) | [KONSISTEN] (act 32) | **INKONSISTEN** (Sequence diagram menggambarkan logout instan tanpa konfirmasi) |

---

## 3. Ringkasan Konflik Antar-Dokumen

1. **Konflik Barcode 128 vs QR Code:**
   - KODE & Bab 3-4 SRS: Menggunakan Barcode Code 128 SVG murni.
   - Bab 1-2 SRS, Diagram S08, dan Diagram A08: Mengklaim kartu menggunakan QR Code.
2. **Konflik Foreign Key `pengajuan_peminjaman`:**
   - KODE (`aksa_nova.sql:265-266`): Memiliki FK fisik `ON DELETE CASCADE`.
   - ERD, Class Diagram, dan SRS Bab 8: Mengklaim relasi adalah relasi logis tanpa FK basis data.
3. **Konflik Pembekuan Anggota (Freeze):**
   - KODE: Admin hanya memiliki tombol `unfreeze`, `approve`, `reject`, `reset_password`. Freeze hanya terjadi otomatis via OTP.
   - SRS (FR-05), Use Case (UC-28), dan Narasi Word: Mengklaim admin dapat membekukan kartu siswa secara manual.
4. **Konflik Fitur Pengingat WhatsApp:**
   - KODE: Tautan keluar biasa `https://wa.me/...`.
   - Class Diagram, Sequence 24, Activity 24: Mengarang aktor Cron dan alur pembatalan `batal_wa`.
5. **Konflik Nama File & Struktur Folder:**
   - KODE: Seluruh script berada di flat root directory.
   - 23 file PUML Lengkap, SRS Tabel 7.2, dan Narasi Use Case Word: Mencantumkan 22-27 file non-existent di folder `admin/*.php` dan `api/*.php`.
