# LAPORAN AUDIT v2: RUJUKAN KE ELEMEN YANG TIDAK ADA
**Proyek:** AKSA NOVA — Sistem Informasi Perpustakaan Digital SMK Negeri 1 Rongga  
**Tanggal Audit:** 2026-11-08  
**Auditor:** Software Auditor & System Analyst (Audit v2 — Clean Room & Read-Only)  
**Status Audit:** SELESAI (Audit Read-Only)  

---

## 1. Pengantar Audit Rujukan Tidak Ada

Laporan ini mengumpulkan dan mengklasifikasikan seluruh rujukan ke file, kolom database, kunci konfigurasi, dan fungsi/metode yang **tercantum dalam dokumen atau diagram, tetapi TIDAK ADA di kode sumber aktual**. Sumber data didapatkan dari pemindaian AST mekanis [`docs/audit_v2/scripts/07_scan_nonexistent_refs.py`](file:///c:/laragon/www/aksanova/docs/audit_v2/scripts/07_scan_nonexistent_refs.py) dan verifikasi visual dokumen.

---

## 2. Rujukan File Kode PHP / JS yang Tidak Ada (29 File)

Berikut adalah daftar file yang dikutip atau dirujuk oleh dokumen (SRS, PUML Lengkap, usecase-penjelasan.docx), padahal file-file tersebut tidak pernah ada di repositori:

| No | Path File Dikutip Dokumen | Dokumen / Artefak yang Mengutip | Fakta Proyek | File Riil Pengganti di Kode |
|---:|---|---|---|---|
| 1 | `admin/buku.php` | SRS Tabel 7.2, `sequence_20.puml`, `activity_20.puml` | Tidak ada folder `admin/` | [`halaman_admin.php`](file:///c:/laragon/www/aksanova/halaman_admin.php) |
| 2 | `admin/kategori.php` | SRS Tabel 7.2 | Tidak ada folder `admin/` | [`halaman_admin.php`](file:///c:/laragon/www/aksanova/halaman_admin.php) (tab kategori) |
| 3 | `admin/peminjaman.php` | SRS Tabel 7.2, `sequence_13.puml`, `activity_04.puml` | Tidak ada folder `admin/` | [`pengajuan_buku.php`](file:///c:/laragon/www/aksanova/pengajuan_buku.php) & [`pinjam_buku.php`](file:///c:/laragon/www/aksanova/pinjam_buku.php) |
| 4 | `admin/pengaturan.php` | SRS Tabel 7.2, `sequence_23.puml` | Tidak ada folder `admin/` | [`pengaturan_denda.php`](file:///c:/laragon/www/aksanova/pengaturan_denda.php) |
| 5 | `admin/users.php` | SRS Tabel 7.2, `usecase-penjelasan.docx` | Tidak ada folder `admin/` | [`daftar_anggota.php`](file:///c:/laragon/www/aksanova/daftar_anggota.php) |
| 6 | `admin/laporan.php` | SRS Tabel 7.2 | Tidak ada folder `admin/` | [`admin_dashboard.php`](file:///c:/laragon/www/aksanova/admin_dashboard.php) |
| 7 | `admin/hapus_riwayat.php` | `sequence_22.puml`, `activity_22.puml` | Tidak ada file ini | [`telah_dipinjam.php?aksi=hapus_semua`](file:///c:/laragon/www/aksanova/telah_dipinjam.php) |
| 8 | `admin/kirim_wa.php` | `sequence_24.puml`, `activity_24.puml` | Tidak ada file ini | Tautan langsung `https://wa.me/...` di [`telah_dipinjam.php`](file:///c:/laragon/www/aksanova/telah_dipinjam.php) |
| 9 | `admin/banner.php` | `sequence_25.puml`, `activity_25.puml` | Tidak ada file ini | [`kelola_banner.php`](file:///c:/laragon/www/aksanova/kelola_banner.php) |
| 10 | `admin/musik.php` | `sequence_28.puml`, `activity_28.puml` | Tidak ada file ini | [`pengaturan_musik.php`](file:///c:/laragon/www/aksanova/pengaturan_musik.php) |
| 11 | `admin/dashboard.php` | `sequence_30.puml`, `activity_30.puml` | Tidak ada file ini | [`admin_dashboard.php`](file:///c:/laragon/www/aksanova/admin_dashboard.php) |
| 12 | `admin/ekspor_excel.php` | `sequence_30.puml`, `activity_30.puml` | Tidak ada file ini | [`export_excel_dashboard.php`](file:///c:/laragon/www/aksanova/export_excel_dashboard.php) |
| 13 | `admin/profil.php` | `sequence_31.puml`, `activity_31.puml` | Tidak ada file ini | [`update_profil_admin.php`](file:///c:/laragon/www/aksanova/update_profil_admin.php) |
| 14 | `admin/check_notif.php` | SRS Bab 8, `sequence_33.puml`, `activity_33.puml` | Tidak ada file ini | [`notifikasi_pengajuan_admin.php`](file:///c:/laragon/www/aksanova/notifikasi_pengajuan_admin.php) |
| 15 | `admin/lokasi.php` | `sequence_34.puml`, `activity_34.puml` | Tidak ada file ini | [`pengaturan_lokasi.php`](file:///c:/laragon/www/aksanova/pengaturan_lokasi.php) |
| 16 | `admin/anggota.php` | `sequence_14.puml`, `activity_14.puml` | Tidak ada file ini | [`daftar_anggota.php`](file:///c:/laragon/www/aksanova/daftar_anggota.php) |
| 17 | `admin/reset_password.php`| `sequence_15.puml`, `activity_15.puml` | Tidak ada file ini | [`daftar_anggota.php?aksi=reset_password`](file:///c:/laragon/www/aksanova/daftar_anggota.php) |
| 18 | `member/profil.php` | `sequence_09.puml`, `activity_09.puml` | Tidak ada folder `member/` | [`edit_kartu.php`](file:///c:/laragon/www/aksanova/edit_kartu.php) |
| 19 | `member/dashboard.php` | `sequence_13.puml`, `activity_13.puml` | Tidak ada folder `member/` | [`dashboard_user.php`](file:///c:/laragon/www/aksanova/dashboard_user.php) |
| 20 | `api/buku.php` | SRS Tabel 7.2 | Tidak ada folder `api/` | Kueri langsung di [`daftar_buku.php`](file:///c:/laragon/www/aksanova/daftar_buku.php) |
| 21 | `api/kategori.php` | SRS Tabel 7.2 | Tidak ada folder `api/` | Kueri langsung di [`daftar_buku.php`](file:///c:/laragon/www/aksanova/daftar_buku.php) |
| 22 | `api/statistik.php` | SRS Tabel 7.2 | Tidak ada folder `api/` | Kueri langsung di [`admin_dashboard.php`](file:///c:/laragon/www/aksanova/admin_dashboard.php) |
| 23 | `api/like.php` | `sequence_10.puml`, `activity_10.puml` | Tidak ada file ini | [`like_handler.php`](file:///c:/laragon/www/aksanova/like_handler.php) |
| 24 | `api/favorite.php` | `sequence_10.puml` | Tidak ada file ini | [`like_handler.php`](file:///c:/laragon/www/aksanova/like_handler.php) (mode favorite) |
| 25 | `api/rate.php` | `sequence_11.puml`, `activity_11.puml` | Tidak ada file ini | [`rating_handler.php`](file:///c:/laragon/www/aksanova/rating_handler.php) |
| 26 | `katalog.php` | SRS Tabel 7.2, `sequence_12.puml`, `activity_12.puml` | Tidak ada file ini | [`daftar_buku.php`](file:///c:/laragon/www/aksanova/daftar_buku.php) |
| 27 | `detail_buku.php` | SRS Tabel 7.2, `Penjelasan ERD.docx`, `sequence_12.puml` | Tidak ada file ini | [`buku_detail.php`](file:///c:/laragon/www/aksanova/buku_detail.php) |
| 28 | `includes/denda.php` | SRS Tabel 7.2 | Tidak ada file ini | Logika denda inline di [`telah_dipinjam.php`](file:///c:/laragon/www/aksanova/telah_dipinjam.php) |
| 29 | `export_excel.php` | `sequence_30.puml` | Tidak ada file ini | [`export_excel_dashboard.php`](file:///c:/laragon/www/aksanova/export_excel_dashboard.php) |

---

## 3. Kunci Konfigurasi Pengaturan Fiktif di SRS (18 Kunci)

Pada Kamus Data Bab 6.3 SRS (`SRS_AKSA_NOVA.docx` P433), tercantum 18 kunci konfigurasi tabel `pengaturan` yang tidak pernah ada di database maupun kode PHP:

1. `toleransi_hari` (fakta: denda dihitung tepat hari keterlambatan tanpa toleransi grace period)
2. `enable_auto_reminder` (fakta: pengingat WA dipicu manual oleh admin, tidak ada cron/auto)
3. `lokasi_sekolah`
4. `lokasi_map_lat`
5. `lokasi_map_lng`
6. `lokasi_map_zoom`
7. `jam_buka_senin_kamis` (fakta: key riil adalah `jam_buka_weekday`)
8. `jam_tutup_senin_kamis` (fakta: key riil adalah `jam_tutup_weekday`)
9. `jam_buka_jumat` (tersedia tapi format pairing di dokumen terbalik)
10. `jam_tutup_jumat`
11. `jam_buka_sabtu`
12. `jam_tutup_sabtu`
13. `nama_perpustakaan` (fakta: nama perpustakaan hardcoded di title dan navbar)
14. `pesan_wa_template` (fakta: template pesan WA ditulis langsung di `telah_dipinjam.php`)
15. `maks_pinjam_guru` (fakta: limit pinjam sama untuk semua role via `maks_buku_pinjam`)
16. `maks_pinjam_siswa` (fakta: limit seragam)
17. `biaya_ganti_kartu` (fakta: reset kartu OTP gratis)
18. `smtp_auto_test`

---

## 4. Kolom dan Nilai Enum Database Fiktif (15+ Entri)

Ditemukan pada `Penjelasan Class Diagram - AKSA NOVA.docx` (tabel dan embedded `image1.png`):

1. `Buku.penerbit` (fakta: tabel `buku` tidak memiliki kolom penerbit)
2. `Buku.tahun` (fakta: kolom riil bernama `tahun_terbit`)
3. `Buku.kategori` (fakta: tabel `buku` berelasi via FK `kategori_id`, bukan menyimpan nama kategori teks)
4. `Buku.deskripsi` (fakta: tabel `buku` hanya memiliki `sinopsis`)
5. `Buku.sampul` (fakta: kolom riil bernama `gambar`)
6. `Peminjaman.tgl_pinjam` (fakta: kolom riil bernama `tanggal_pinjam`)
7. `Peminjaman.tgl_kembali` (fakta: kolom riil bernama `tanggal_kembali`)
8. `Peminjaman.denda_terbayar` (fakta: tabel `peminjaman` hanya memiliki `denda` dan `status_denda`, tidak ada kolom riwayat pembayaran parsial)
9. `Peminjaman.status = 'kembali'` (fakta: enum riil di DDL adalah `'dikembalikan'`)
10. `PengajuanPeminjaman.status = 'pending'` (fakta: enum riil di DDL adalah `'menunggu'`)
11. `PengajuanPeminjaman.jumlah` (fakta: peminjaman buku satuan, tidak ada kolom jumlah per pengajuan)
12. `PengajuanPeminjaman.tgl_pengajuan` (fakta: kolom riil bernama `created_at`)
13. `PengajuanPeminjaman.catatan` (fakta: tidak ada kolom catatan pengajuan di tabel)
14. `ReminderLog.admin_id` (fakta: entitas/tabel `reminder_log` sama sekali tidak ada di database)
15. `ReminderLog.waktu_kirim` (fakta: pengiriman WA hanya mencatat kolom `wa_terkirim` di tabel `peminjaman`)

---

## 5. Kelas dan Metode OOP Fiktif (61+ Metode)

Ditemukan pada `Class_Diagram_AKSA_NOVA.png` dan dokumen penjelasannya:
- 12 kelas entitas (`Users`, `Buku`, `Peminjaman`, dll.) tidak memiliki implementasi file kelas `.php` di proyek (100% fungsionalitas diimplementasikan secara prosedural dalam script).
- Dari 66 metode yang dimodelkan, **61 metode tidak ada di PHP**, antara lain:
  - `Buku::tambahBuku()`, `Buku::updateStok()`, `Buku::hitungRating()`
  - `Users::verifikasiPassword()`, `Users::generateKartu()`, `Users::bekukanKartu()`
  - `Peminjaman::hitungDenda()`, `Peminjaman::kembalikanBuku()`
  - `ReminderLog::batalWA()` (fitur halusinasi pembatalan WhatsApp)
  - `OtpController::generateOtp()`, `OtpController::verifyOtp()`
