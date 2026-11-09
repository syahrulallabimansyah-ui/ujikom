# LAPORAN AUDIT v2: KELENGKAPAN KODE KE DOKUMEN (TWO-WAY COMPLETENESS)
**Proyek:** AKSA NOVA — Sistem Informasi Perpustakaan Digital SMK Negeri 1 Rongga  
**Tanggal Audit:** 2026-11-08  
**Auditor:** Software Auditor & System Analyst (Audit v2 — Clean Room & Read-Only)  
**Status Audit:** SELESAI (Audit Read-Only)  

---

## 1. Pengantar Audit Ketertelusuran Dua Arah

Audit rekayasa perangkat lunak yang komprehensif mewajibkan pemeriksaan dua arah (*two-way verification*):
1. **Soundness (Dokumen -> Kode):** Setiap klaim atau spesifikasi dalam dokumen wajib dibuktikan keberadaannya di dalam kode sumber riil.
2. **Completeness (Kode -> Dokumen):** Setiap modul, alur, dan file kode yang ada di aplikasi wajib diperiksa apakah telah didokumentasikan dengan benar di dalam dokumen rancangan (SRS, ERD, Use Case, Class, Sequence, Activity).

Bagian ini berfokus pada **Kode yang Tidak Terwakili (Unrepresented Code)**, yaitu fitur dan mekanisme canggih yang telah aktif berjalan di kode sumber PHP, namun luput didokumentasikan di dalam dokumen teknis.

---

## 2. Ringkasan Kelengkapan Dua Arah

- **Jumlah File PHP Aplikasi:** 44 berkas (100% dipindai AST di `docs/audit_v2/02_fakta_kode.md`).
- **File PHP yang Memiliki Perwakilan Dokumen:** 33 berkas (75,0%).
- **File PHP yang Sama Sekali Tidak Terwakili / Terlewat:** 11 berkas (25,0%).
- **Fitur Canggih yang Aktif tapi Tidak Tercatat di Dokumen:** 8 fitur utama.

---

## 3. Daftar File dan Fitur Kode yang Tidak Terwakili di Dokumen

| No | File Kode Aktual | Bukti Path:Baris | Peran / Logika Bisnis Aktual | Status di Dokumen (SRS / UML) | Dampak Ketidakterwakilan |
|---:|---|---|---|---|---|
| 1 | `kartu_masuk.php` | [`kartu_masuk.php:1-65`](file:///c:/laragon/www/aksanova/kartu_masuk.php#L1-L65) | Fitur otentikasi cepat mandiri: memindai Barcode Code 128 fisik kartu anggota di scanner perpustakaan untuk langsung login tanpa ketik sandi. | **Sama sekali tidak ada** di SRS, Use Case, Sequence, atau Activity. | Penguji/asesor tidak akan mengetahui adanya fitur login barcode mandiri yang sangat fungsional di perpustakaan. |
| 2 | `kartu_clear.php` | [`kartu_clear.php:1-25`](file:///c:/laragon/www/aksanova/kartu_clear.php#L1-L25) | Membersihkan sesi kartu sementara (`kartu_data`) setelah pencetakan kartu selesai di komputer bersama. | **Sama sekali tidak ada** di dokumen manapun. | Pengamanan sesi pencetakan kartu publik tidak terdokumentasi. |
| 3 | `includes/barcode128.php` | [`includes/barcode128.php:1-71`](file:///c:/laragon/www/aksanova/includes/barcode128.php#L1-L71) | Generator barcode Code 128 Subset B berbasis SVG vektor murni PHP tanpa pustaka pihak ketiga. | **Terdistorsi:** Dokumen lama justru mengklaim sistem menggunakan QR Code. | Standar teknis representasi visual kartu digital menjadi salah kaprah. |
| 4 | `includes/mailer.php` (Rollback OTP) | [`includes/mailer.php:119-127`](file:///c:/laragon/www/aksanova/includes/mailer.php#L119-L127) | Transaksi aman: bila SMTP gagal mengirim email OTP, baris rekaman OTP yang baru dibuat di tabel `password_otp` langsung dihapus (`DELETE`). | **Tidak ada di SRS:** Dokumen hanya menyebut pengiriman via email tanpa mekanisme rollback kegagalan jaringan. | Mekanisme ketahanan sistem (fault tolerance) tidak tercatat di NFR. |
| 5 | `includes/otp.php` (Dev Mode) | [`includes/otp.php:148-172`](file:///c:/laragon/www/aksanova/includes/otp.php#L148-L172) | Logging OTP lokal ke `storage/otp_dev.log` dengan proteksi ganda: `OTP_DEV_MODE===true` DAN IP wajib `localhost`/`127.0.0.1`. | **Tidak ada di dokumen teknis.** | Prosedur pengujian offline developer tidak terpetakan di dokumen arsitektur. |
| 6 | `ganti_sandi_admin.php` | [`ganti_sandi_admin.php:1-85`](file:///c:/laragon/www/aksanova/ganti_sandi_admin.php#L1-L85) | Modul lama penggantian kata sandi admin mandiri (tanpa CSRF). | **Tidak terwakili:** Dokumen hanya memetakan `update_profil_admin.php`. | Adanya endpoint ganti sandi alternatif luput dari analisis keamanan. |
| 7 | `pengajuan_buku.php` (Pembersihan Riwayat) | [`pengajuan_buku.php:38-48`](file:///c:/laragon/www/aksanova/pengajuan_buku.php#L38-L48) | Aksi `hapus_riwayat` dan `bersihkan_riwayat` untuk menghapus pengajuan sirkulasi yang telah berstatus `disetujui` atau `ditolak`. | **Tidak ada Use Case terpisah:** Di use case hanya tercatat "Menyetujui/Menolak Pengajuan". | Pemeliharaan data sirkulasi histori admin tidak terpetakan di UML. |
| 8 | `telah_dipinjam.php` (Hapus Riwayat) | [`telah_dipinjam.php:40-48`](file:///c:/laragon/www/aksanova/telah_dipinjam.php#L40-L48) | Aksi `hapus_semua` untuk membersihkan riwayat pengembalian buku dan denda yang telah lunas. | **Tidak ada Use Case terpisah:** Hanya ada use case pemrosesan pengembalian. | Fitur pembersihan arsip tidak teridentifikasi di use case diagram baru. |
| 9 | `beranda.php` (Sindikasi RSS) | [`beranda.php:75-115`](file:///c:/laragon/www/aksanova/beranda.php#L75-L115) | Pengambilan berita pendidikan live dari feed RSS Antara News dengan parsing XML dan caching berkala di filesystem. | **Sama sekali tidak ada di SRS.** | Integrasi berita eksternal tidak tercantum dalam daftar antarmuka perangkat lunak. |
| 10 | `includes/pengaturan_panel.php` (Aksesibilitas) | [`includes/pengaturan_panel.php:1-120`](file:///c:/laragon/www/aksanova/includes/pengaturan_panel.php#L1-L120) | Kontrol preferensi peramban: pembesar teks (font scaling), font OpenDyslexic untuk pengguna disleksia, dan lite motion untuk mengurangi animasi UI. | **Disebut sekilas tanpa rincian:** SRS NFR hanya menyebut UI responsif. | Fitur inklusivitas dan aksesibilitas penting tidak ditonjolkan. |
| 11 | `daftar_anggota.php` (Reset Sandi Fleksibel) | [`daftar_anggota.php:103-125`](file:///c:/laragon/www/aksanova/daftar_anggota.php#L103-L125) | Admin dapat memilih antara membuat password acak 8 karakter atau mengetikkan kata sandi kustom (`custom_password`) untuk anggota yang lupa sandi. | **SRS mengklaim reset sandi selalu acak.** | Fleksibilitas administrasi anggota tidak tercatat secara presisi. |

---

## 4. Evaluasi Soundness: Dokumen Menuju Kode

Sebaliknya, saat mengevaluasi klaim dokumen terhadap kode, ditemukan **banyak elemen fiktif (phantom features)** yang didokumentasikan secara rinci tetapi sama sekali tidak ada di kode:
1. **Fitur Pembatalan Pesan WhatsApp (`batal_wa`):** Muncul di Class Diagram, Sequence 24, dan Activity 24, padahal di kode hanya berupa tautan keluar biasa ke `https://wa.me/...`.
2. **Fitur Pembekuan Kartu oleh Admin (Freeze):** Muncul di SRS (FR-05), Use Case 28, dan narasi Word, padahal admin hanya memiliki tombol `unfreeze`.
3. **Struktur Folder Modular (`admin/`, `api/`, `member/`):** Muncul di seluruh 23 file PUML Lengkap dan SRS Tabel 7.2, padahal proyek berarsitektur flat di root folder.
4. **Proteksi Brute Force Login:** Diklaim mengunci akun setelah percobaan gagal beruntun, padahal kode `sign_in.php` tidak melakukan pengecekan percobaan gagal.

---

## 5. Rekomendasi Penyelarasan Dua Arah

1. **Tambahkan Fitur Riil ke Dokumen:**
   - Tambahkan use case dan alur activity/sequence untuk `kartu_masuk.php` (Login Cepat via Scanner Barcode).
   - Masukkan integrasi RSS Berita Antara dan fitur aksesibilitas font disleksia ke dalam SRS Bab 3.
2. **Eliminasi Fitur Phantom dari Dokumen:**
   - Hapus klaim pembatalan WhatsApp (`batal_wa`), freeze manual admin, dan struktur subfolder palsu.
