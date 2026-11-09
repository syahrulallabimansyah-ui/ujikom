# LAPORAN AUDIT v2: CLASS DIAGRAM
**Proyek:** AKSA NOVA — Sistem Informasi Perpustakaan Digital SMK Negeri 1 Rongga  
**Tanggal Audit:** 2026-11-08  
**Auditor:** Software Auditor & System Analyst (Audit v2 — Clean Room & Read-Only)  
**Status Audit:** SELESAI (Audit Read-Only)  

---

## 1. Ringkasan Eksekutif Class Diagram

Audit terhadap artefak Class Diagram AKSA NOVA mencakup:
1. Diagram utama terkini: [`UML/Class/Class_Diagram_AKSA_NOVA.png`](file:///c:/laragon/www/aksanova/UML/Class/Class_Diagram_AKSA_NOVA.png) (963.856 byte, 2026-11-05 08:09:07, 14 kelas, 66 metode).
2. Dokumen penjelasan narasi: [`UML/Class/Penjelasan Class Diagram - AKSA NOVA.docx`](file:///c:/laragon/www/aksanova/UML/Class/Penjelasan%20Class%20Diagram%20-%20AKSA%20NOVA.docx) (110.158 byte, 2026-11-03 08:35:10).
3. Gambar tertanam di dalam DOCX: `word/media/image1.png` (547.452 byte).

**Sumber Kebenaran (Ground Truth):**
- Seluruh 44 file source code PHP ([`docs/audit_v2/02_fakta_kode.md`](file:///c:/laragon/www/aksanova/docs/audit_v2/02_fakta_kode.md)).
- Analisis sintaks OOP PHP (tidak ada class domain OOP; proyek mengadopsi arsitektur prosedural berbasis skrip PHP terpisah).
- Skema database fisik DDL ([`database/aksa_nova.sql`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql)).
- Hasil perbandingan kelas mekanis ([`docs/audit_v2/class_diff_results.json`](file:///c:/laragon/www/aksanova/docs/audit_v2/class_diff_results.json)).

**Hasil Keseluruhan:**
Class diagram AKSA NOVA memiliki perbedaan ekstrem antara file PNG eksternal dengan gambar yang tertanam di dalam dokumen Word:
- **Gambar tertanam di Word (`image1.png`) adalah artefak rusak dan penuh halusinasi:** Judul mengalami kerusakan encoding/mojibake (*"CLASS DIAGRAM â€” SISTEM INFORMASI..."*), teks kardinalitas terpotong (*"1 .. 0..1 (CAS"*), serta mendefinisikan **15+ kolom dan enum fiktif** yang tidak pernah ada di database maupun kode (seperti `Buku.penerbit`, `tahun`, `kategori`, `deskripsi`, `sampul`, `Peminjaman.tgl_kembali`, `denda_terbayar`, `status='kembali'`, `PengajuanPeminjaman.status='pending'`, `ReminderLog.admin_id`, `waktu_kirim`).
- **Diagram mandiri (`Class_Diagram_AKSA_NOVA.png`) jauh lebih mendekati basis data**, namun tetap mengulang kesalahan ERD bahwa `PengajuanPeminjaman` tidak memiliki FK fisik, mengarang metode fiktif `+ batalWA() : void` pada `ReminderLog`, serta tidak mencantumkan stereotipe `<<conceptual>>` sehingga seolah-olah mengklaim bahwa aplikasi AKSA NOVA dibangun dengan kelas-kelas OOP aktif padahal kenyataannya 100% prosedural.

---

## 2. Cakupan Terukur (Measurable Coverage)

| Kategori Elemen | Jumlah Diperiksa | BENAR | BENAR DENGAN CATATAN | SALAH | TIDAK TERVERIFIKASI | Keterangan Singkat |
|---|:---:|:---:|:---:|:---:|:---:|---|
| **Kelas Domain (14 Kelas)** | 14 | 12 | 2 | 0 | 0 | 12 kelas entitas cocok tabel; 2 kelas layanan (OtpReset, GoogleBooks) |
| **Atribut Kelas vs Kolom SQL**| 100 | 97 | 3 | 0 | 0 | 3 perbedaan minor penamaan atribut pada diagram baru |
| **Metode Kelas (66 Metode)** | 66 | 5 | 0 | 61 | 0 | 61 metode fiktif (tidak ada di OOP PHP; hanya 5 fungsi helper OTP riil) |
| **Relasi & Kardinalitas** | 15 | 11 | 2 | 2 | 0 | Pengajuan tanpa FK salah; garis putus-putus menabrak package border |
| **Stereotipe Arsitektur** | 1 | 0 | 0 | 1 | 0 | Tidak ada `<<conceptual>>`, menyesatkan pembaca teknis |
| **Gambar Tertanam DOCX (image1)**| 1 | 0 | 0 | 1 | 0 | Rusak mojibake, teks terpotong, 15+ kolom/enum halusinasi |
| **Ketersediaan File Sumber** | 1 | 0 | 0 | 1 | 0 | Gambar 14 kelas tidak memiliki file sumber teks (.puml/.drawio) |
| **TOTAL AKUMULATIF** | **198** | **125 (63,1%)** | **7 (3,5%)** | **66 (33,3%)** | **0 (0,0%)** | Tingkat ketidaksesuaian implementasi OOP tinggi |

---

## 3. Elemen yang Terverifikasi BENAR

Berdasarkan pencocokan elemen diagram mandiri `Class_Diagram_AKSA_NOVA.png` dengan basis data dan kode:

1. **Pemodelan 12 Entitas Data Sesuai Tabel Fisik:**
   - 12 kelas entitas merefleksikan 12 tabel InnoDB dengan benar: `Users`, `Buku`, `Kategori`, `Peminjaman`, `PengajuanPeminjaman`, `BukuRatings`, `BukuLikes`, `BukuFavorites`, `PasswordOtp`, `Banner`, `Pengaturan`, `AdminProfile`.
2. **Kesesuaian Tipe Data Atribut Kunci:**
   - Primary key seluruh entitas bertipe `int` dengan visibility `+` (public) atau `-` (private).
   - Atribut kolom status kartu (`card_status: enum('active','frozen')`) tercantum secara presisi sesuai nilai enum di basis data [`aksa_nova.sql:37`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql#L37).
3. **Representasi Fungsi Helper OTP:**
   - Kelas konseptual `OtpReset` merangkum 5 fungsi inti dari [`includes/otp.php`](file:///c:/laragon/www/aksanova/includes/otp.php): pembuatan OTP, pengiriman OTP, verifikasi OTP, pengecekan batas frekuensi, dan pembersihan token kedaluwarsa.

---

## 4. Tabel Temuan Masalah & Ketidaksesuaian Class Diagram

| ID | Dokumen + Lokasi | Isi Dokumen | Fakta Kode | Bukti Path:Baris | Keparahan | Perbaikan yang Disarankan |
|---|---|---|---|---|:---:|---|
| **[CLS-01]** | `UML/Class/Penjelasan Class Diagram - AKSA NOVA.docx` (Embedded `word/media/image1.png`) | Gambar yang tertanam di dalam dokumen Word adalah diagram rusak: Judul mojibake (*"CLASS DIAGRAM â€” SISTEM..."*), teks terpotong (*"1 .. 0..1 (CAS"*), serta mendefinisikan **15+ kolom dan enum fiktif**: `Buku.penerbit`, `tahun`, `kategori`, `deskripsi`, `sampul`, `Peminjaman.tgl_pinjam`, `tgl_kembali`, `denda_terbayar`, `status='kembali'`, `PengajuanPeminjaman.status='pending'`, `ReminderLog.admin_id`, `waktu_kirim`. | Basis data AKSA NOVA tidak memiliki kolom `penerbit` (menggunakan teks bebas/tanpa penerbit), `tahun` (menggunakan `tahun_terbit`), `sampul` (menggunakan `gambar`), dan status peminjaman hanya `'dipinjam'` atau `'dikembalikan'`. Dokumen Word menyematkan gambar draf usang yang cacat fatal. | [`database/aksa_nova.sql:67-95, 144-165`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql#L67-L95), [`scratch/docs_txt/UML__Class__Penjelasan_Class_Diagram_-_AKSA_NOVA.docx.txt`](file:///C:/Users/Windows/.gemini/antigravity/brain/b32efb7e-9aac-4e95-b4ce-27938e353c90/scratch/docs_txt/UML__Class__Penjelasan_Class_Diagram_-_AKSA_NOVA.docx.txt) | **Kritis** | Ganti gambar `image1.png` di dalam dokumen Word dengan gambar `Class_Diagram_AKSA_NOVA.png` versi perbaikan, dan selaraskan teks tabel kamus kelas di Word dengan skema riil. |
| **[CLS-02]** | `UML/Class/Class_Diagram_AKSA_NOVA.png` | Kelas `PengajuanPeminjaman` digambar dengan garis putus-putus berlabel *"tanpa FK"* dan kolom `buku_id` serta `user_id` tidak memiliki label `[FK]`. | Tabel `pengajuan_peminjaman` memiliki Foreign Key fisik formal di database: `fk_pengajuan_buku` (`buku_id -> buku.id ON DELETE CASCADE ON UPDATE CASCADE`) dan `fk_pengajuan_user` (`user_id -> users.id ON DELETE CASCADE ON UPDATE CASCADE`). | [`database/aksa_nova.sql:265-266`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql#L265-L266) | **Tinggi** | Ubah garis relasi menjadi garis solid (asosiasi kuat) dan tambahkan penanda `[FK]` pada kedua atribut tersebut di diagram. |
| **[CLS-03]** | `UML/Class/Class_Diagram_AKSA_NOVA.png` | Kelas `ReminderLog` memiliki metode fiktif `+ batalWA() : void`. | Di kode riil perpustakaan, pengingat WhatsApp dilakukan dengan mengarahkan browser admin ke URL `https://wa.me/...`. Di database dan PHP **tidak ada fitur atau tombol untuk membatalkan pengiriman WA** (`batal_wa`), dan tidak ada mekanisme penarikan pesan eksternal WhatsApp. | [`telah_dipinjam.php:380-420`](file:///c:/laragon/www/aksanova/telah_dipinjam.php#L380-L420) | **Tinggi** | Hapus metode `+ batalWA() : void` dari kelas `ReminderLog`. |
| **[CLS-04]** | `UML/Class/Class_Diagram_AKSA_NOVA.png` (Keseluruhan Diagram) | Diagram memodelkan 14 kelas berorientasi objek lengkap dengan atribut private dan 66 metode public tanpa mencantumkan stereotipe `<<conceptual>>` atau `<<table-model>>`. | Seluruh aplikasi AKSA NOVA ditulis secara prosedural dalam file-file PHP terpisah (`halaman_admin.php`, `daftar_buku.php`, dll.) tanpa mendeklarasikan class OOP domain. Dari 66 metode pada diagram, **61 metode sama sekali tidak ada di kode sumber PHP**. | [`docs/audit_v2/02_fakta_kode.md`](file:///c:/laragon/www/aksanova/docs/audit_v2/02_fakta_kode.md) | **Tinggi** | Tambahkan stereotipe `<<conceptual>>` pada header diagram dan setiap kotak kelas, serta beri keterangan resmi bahwa diagram memodelkan domain konseptual sistem, bukan arsitektur kelas OOP riil. |
| **[CLS-05]** | `UML/Class/Class_Diagram_AKSA_NOVA.png` (Kelas OtpReset) | Signature metode pada kelas `OtpReset` ditulis tidak akurat, mis. `+ otp_batas_permintaan(email: string, ip: string) : bool`. | Pada implementasi riil [`includes/otp.php:51`](file:///c:/laragon/www/aksanova/includes/otp.php#L51), signature fungsinya adalah `function otp_cek_rate_limit($conn, ?int $user_id, ?string $ip): array` yang mengembalikan array asosiatif status limit, bukan boolean sederhana, dan menerima koneksi DB serta ID pengguna. | [`includes/otp.php:51-85`](file:///c:/laragon/www/aksanova/includes/otp.php#L51-L85) | **Sedang** | Sesuaikan parameter dan tipe kembalian metode pada kelas `OtpReset` agar selaras dengan fungsi-fungsi di `includes/otp.php`. |
| **[CLS-06]** | `UML/Class/Class_Diagram_AKSA_NOVA.png` (Layout Visual) | Beberapa garis relasi asosiasi menabrak dan berhenti di tepi bingkai package `Interaksi Anggota-Buku`, alih-alih menempel langsung pada header kartu kelas di dalamnya. | Cacat perutean panah visual pada perender diagram. | [`UML/Class/Class_Diagram_AKSA_NOVA.png`](file:///c:/laragon/www/aksanova/UML/Class/Class_Diagram_AKSA_NOVA.png) | **Sedang** | Perbaiki perutean garis asosiasi agar menembus bingkai package dan langsung menghubungkan kotak kelas target. |
| **[CLS-07]** | `UML/Class/Class_Diagram_AKSA_NOVA.png` | Diagram tidak memiliki file sumber teks (`.puml`, `.drawio`, atau `.mmd`) di folder `UML/Class/`. | Tidak ada cara langsung untuk meregenerasi diagram secara otomatis jika skema berubah. | [`docs/audit_v2/03_inventaris_dokumen.csv`](file:///c:/laragon/www/aksanova/docs/audit_v2/03_inventaris_dokumen.csv) | **Sedang** | Buat dan simpan file sumber deklaratif yang merepresentasikan diagram kelas konseptual ini. |

---

## 5. Rekomendasi Perbaikan Class Diagram (Hanya Ditulis, Tidak Dikerjakan)

1. **Penggantian Total Gambar di Dokumen Word:**
   Keluarkan gambar rusak ber-mojibake (`image1.png`) dari `Penjelasan Class Diagram - AKSA NOVA.docx`, dan ganti dengan gambar bersih yang telah diperbaiki.
2. **Klarifikasi Status Konseptual:**
   Wajib menambahkan label `<<conceptual domain model>>` agar penguji/auditor eksternal tidak menyangka sistem AKSA NOVA dibangun menggunakan paradigma OOP berbasis class.
3. **Koreksi Relasi dan Metode Fiktif:**
   Ubah relasi `PengajuanPeminjaman` menjadi asosiasi penuh ber-FK fisik, dan hapus metode `batalWA()`.
