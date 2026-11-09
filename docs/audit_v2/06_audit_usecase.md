# LAPORAN AUDIT v2: USE CASE DIAGRAM
**Proyek:** AKSA NOVA — Sistem Informasi Perpustakaan Digital SMK Negeri 1 Rongga  
**Tanggal Audit:** 2026-11-08  
**Auditor:** Software Auditor & System Analyst (Audit v2 — Clean Room & Read-Only)  
**Status Audit:** SELESAI (Audit Read-Only)  

---

## 1. Ringkasan Eksekutif Use Case Diagram

Audit terhadap artefak Use Case Diagram AKSA NOVA mencakup:
1. Diagram utama terkini: [`UML/Usecase/UseCase_Diagram_AKSA_NOVA.png`](file:///c:/laragon/www/aksanova/UML/Usecase/UseCase_Diagram_AKSA_NOVA.png) (952.393 byte, 2026-11-05 08:09:17, 24 Use Cases).
2. Diagram sekunder (versi lama 34 UC): [`UML/Usecase/usecase_diagram.png`](file:///c:/laragon/www/aksanova/UML/Usecase/usecase_diagram.png) (212.309 byte, 2026-11-03 08:30:26).
3. Sumber HTML interaktif: [`UML/Usecase/usecase_diagram.html`](file:///c:/laragon/www/aksanova/UML/Usecase/usecase_diagram.html) (18.385 byte).
4. Sumber teks PlantUML: [`UML/Usecase/usecase_diagram.puml`](file:///c:/laragon/www/aksanova/UML/Usecase/usecase_diagram.puml) (4.638 byte).
5. Dokumen penjelasan narasi: [`UML/Usecase/usecase-penjelasan.docx`](file:///c:/laragon/www/aksanova/UML/Usecase/usecase-penjelasan.docx) (240.292 byte).

**Sumber Kebenaran (Ground Truth):**
- Seluruh 44 file kode PHP ([`docs/audit_v2/02_fakta_kode.md`](file:///c:/laragon/www/aksanova/docs/audit_v2/02_fakta_kode.md)).
- Aturan Aktor (ATURAN BESI #6: Hanya 3 aktor sah: Tamu, Anggota, Pustakawan).

**Hasil Keseluruhan:**
Diagram terbaru `UseCase_Diagram_AKSA_NOVA.png` (24 UC) berhasil mematuhi aturan 3 aktor sah tanpa memasukkan Cron, WhatsApp, atau Sistem sebagai aktor eksternal. Namun, diagram ini **tidak memiliki file sumber mentah (.puml atau .drawio)** sehingga sulit dimodifikasi secara konsisten. Terdapat pula anomali relasi use case seperti penggabungan keliru antara Musik (database admin) dengan Tema/Font (browser client), peniadaan hak admin mengunduh kartu di diagram, pemodelan pelunasan denda yang tidak mandiri, dan klaim keliru aksi pembekuan (freeze) anggota oleh pustakawan pada versi 34 UC.

---

## 2. Cakupan Terukur (Measurable Coverage)

| Kategori Elemen | Jumlah Diperiksa | BENAR | BENAR DENGAN CATATAN | SALAH | TIDAK TERVERIFIKASI | Keterangan Singkat |
|---|:---:|:---:|:---:|:---:|:---:|---|
| **Aktor Sistem (Actor)** | 3 | 3 | 0 | 0 | 0 | Tamu, Anggota, Pustakawan patuh 100% pada diagram baru |
| **Batasan Sistem (System Boundary)** | 1 | 0 | 1 | 0 | 0 | Boundary rapi di versi 24 UC, tapi terpotong garis di versi 34 UC |
| **Use Case Fungsional (24 UC Baru)** | 24 | 19 | 3 | 2 | 0 | 2 UC salah domain/konflik fakta (Musik, Freeze) |
| **Relasi Asosiasi (Actor -> UC)** | 32 | 27 | 3 | 2 | 0 | Asosiasi kartu anggota kurang Pustakawan; Login ke Tamu rancu |
| **Relasi `<<include>>` / `<<extend>>`** | 10 | 7 | 2 | 1 | 0 | `<<extend>>` denda lunas tidak memiliki jalur mandiri |
| **Kesesuaian Sumber (.png vs .puml)** | 2 | 0 | 0 | 2 | 0 | Versi 24 UC tanpa sumber; versi 34 UC .puml menyimpang |
| **Dokumen Narasi DOCX** | 1 | 0 | 0 | 1 | 0 | Tabel T02 mengutip 26 file non-existent; freeze admin diklaim |
| **TOTAL AKUMULATIF** | **73** | **56 (76,7%)** | **9 (12,3%)** | **8 (11,0%)** | **0 (0,0%)** | Memerlukan penyediaan file sumber & pembenahan asosiasi |

---

## 3. Elemen yang Terverifikasi BENAR

Berdasarkan pencocokan elemen use case dengan fungsionalitas kode:

1. **Kepatuhan 3 Aktor Sah (ATURAN BESI #6):**
   - Diagram `UseCase_Diagram_AKSA_NOVA.png` secara disiplin hanya menampilkan 3 aktor manusia:
     - `Tamu (Guest)`: Pengguna yang belum terautentikasi.
     - `Anggota (Member)`: Siswa dan Tenaga Pendidik SMKN 1 Rongga.
     - `Pustakawan (Admin)`: Pengelola teknis dan sirkulasi perpustakaan.
   - Tidak ada aktor ilegal seperti Cron, Google Books API, WhatsApp, SMTP, atau Sistem.

2. **Use Case Anggota yang Sepenuhnya Akurat (9 UC):**
   - Mendaftar Akun Mandiri (`sign_up.php`).
   - Mereset Password Kartu via OTP Email (`lupa_kartu.php`).
   - Menjelajahi Katalog & Informasi Perpustakaan (`daftar_buku.php`, `beranda.php`).
   - Melihat Detail Koleksi Buku (`buku_detail.php`).
   - Memberikan Rating & Ulasan Buku (`rating_handler.php`).
   - Memberikan Like & Menyimpan Buku Favorit (`like_handler.php`).
   - Mengajukan Peminjaman Buku Daring (`pengajuan_peminjaman.php`).
   - Mengakses Dashboard Profil & Histori Anggota (`dashboard_user.php`).
   - Mengubah Data Profil & Password Mandiri (`edit_kartu.php`).

3. **Use Case Pustakawan yang Sepenuhnya Akurat (12 UC):**
   - Menyetujui / Menolak Registrasi Anggota (`daftar_anggota.php:57-79`).
   - Memulihkan Status Kartu / Reset Password Anggota (`daftar_anggota.php:74, 103`).
   - Memproses Persetujuan Peminjaman Daring (`pengajuan_buku.php`).
   - Mencatat Peminjaman Langsung di Tempat (`pinjam_buku.php`).
   - Memproses Pengembalian Buku & Hitung Denda (`telah_dipinjam.php`).
   - Mengirim Notifikasi Pengingat Keterlambatan via WhatsApp (`telah_dipinjam.php:380-420`).
   - Mengelola Data Buku & Kategori (`halaman_admin.php`).
   - Mengimpor Metadata Buku Otomatis via ISBN Google Books (`halaman_admin.php:38-95`).
   - Mengelola Konten Banner Promosi (`kelola_banner.php`).
   - Mengatur Tarif Denda & Batas Waktu Peminjaman (`pengaturan_denda.php`).
   - Mengatur Informasi Lokasi & Jam Operasional (`pengaturan_lokasi.php`).
   - Memantau Dashboard Statistik & Ekspor Laporan Excel (`admin_dashboard.php`, `export_excel_dashboard.php`).

---

## 4. Tabel Temuan Masalah & Ketidaksesuaian Use Case Diagram

| ID | Dokumen + Lokasi | Isi Dokumen | Fakta Kode | Bukti Path:Baris | Keparahan | Perbaikan yang Disarankan |
|---|---|---|---|---|:---:|---|
| **[UC-01]** | `UML/Usecase/UseCase_Diagram_AKSA_NOVA.png` | Gambar diagram use case versi 24 UC bertanggal 05 November 2026 tidak memiliki file sumber teks (`.puml`, `.drawio`, atau `.mmd`) di folder `UML/Usecase/`. | File sumber teks yang ada di direktori tersebut (`usecase_diagram.puml` dan `usecase_diagram.html`) adalah diagram versi lama yang berisi 34 use case. | [`docs/audit_v2/03_inventaris_dokumen.csv`](file:///c:/laragon/www/aksanova/docs/audit_v2/03_inventaris_dokumen.csv) | **Tinggi** | Buat file sumber definisi teks deklaratif (mis. SVG/XML Draw.io atau format terdokumentasi non-PlantUML/Mermaid) yang sesuai persis dengan diagram 24 UC. |
| **[UC-02]** | `UML/Usecase/UseCase_Diagram_AKSA_NOVA.png` (Grup Pengaturan) | Terdapat use case bernama *"Mengatur Tema, Font & Musik (disimpan di browser)"* yang digambar dapat diakses oleh Anggota maupun Tamu. | Terjadi percampuran dua domain arsitektur yang bertolak belakang: Pengaturan Tema (gelap/terang) dan Font (skala/OpenDyslexic) memang disimpan di sisi browser (`localStorage`). Namun, **Musik Perpustakaan** adalah aset server yang diunggah dan dikonfigurasi secara eksklusif oleh **Pustakawan** di database/server (`pengaturan_musik.php` -> tabel `pengaturan` kolom `audio_path`). Anggota/Tamu hanya memutar audio yang sudah disiapkan admin. | [`pengaturan_musik.php:1-75`](file:///c:/laragon/www/aksanova/pengaturan_musik.php#L1-L75), [`database/aksa_nova.sql:136`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql#L136) | **Tinggi** | Pisahkan use case tersebut menjadi dua: (1) *"Menyesuaikan Preferensi Tampilan & Aksesibilitas (Tema/Font)"* untuk Anggota/Tamu, dan (2) *"Mengelola Musik Latar Perpustakaan"* untuk Pustakawan. |
| **[UC-03]** | `UML/Usecase/UseCase_Diagram_AKSA_NOVA.png` | Use Case *"Melihat & Mengunduh Kartu Anggota Digital"* hanya dihubungkan dengan garis asosiasi ke aktor **Anggota**. | Pustakawan memiliki hak dan fungsi resmi untuk melihat, mencetak, dan mengunduh kartu anggota siswa/guru dari panel admin melalui tautan cetak kartu di daftar anggota. | [`daftar_anggota.php:990`](file:///c:/laragon/www/aksanova/daftar_anggota.php#L990), [`kartu_anggota.php:25`](file:///c:/laragon/www/aksanova/kartu_anggota.php#L25) | **Sedang** | Hubungkan garis asosiasi dari aktor **Pustakawan** ke use case *"Melihat & Mengunduh Kartu Anggota Digital"*. |
| **[UC-04]** | `UML/Usecase/UseCase_Diagram_AKSA_NOVA.png` | Use Case *"Menandai Denda Lunas"* hanya digambarkan sebagai relasi `<<extend>>` yang menempel pada *"Memproses Pengembalian Buku"*, tanpa memiliki garis asosiasi langsung dari Pustakawan. | Di halaman sirkulasi riwayat pengembalian ([`telah_dipinjam.php`](file:///c:/laragon/www/aksanova/telah_dipinjam.php#L73)), Pustakawan dapat menandai denda lunas secara mandiri kapan saja untuk transaksi yang bukunya telah dikembalikan beberapa hari sebelumnya tanpa perlu memproses pengembalian ulang. | [`telah_dipinjam.php:73-82`](file:///c:/laragon/www/aksanova/telah_dipinjam.php#L73-L82) | **Sedang** | Tambahkan garis asosiasi langsung dari Pustakawan ke use case *"Menandai Denda Lunas"*, atau pertahankan `<<extend>>` dengan menambahkan opsi eksekusi mandiri. |
| **[UC-05]** | `UML/Usecase/UseCase_Diagram_AKSA_NOVA.png` | Use Case *"Masuk ke Sistem (Login)"* dihubungkan ke ketiga aktor (Tamu, Anggota, Pustakawan). | Secara semantik pemodelan UML standar, Tamu yang berhasil login bertransformasi menjadi Anggota atau Pustakawan. Menghubungkan Tamu ke dalam use case autentikasi internal bersama-sama dengan peran terotentikasi menimbulkan kebingungan peran pada boundary. | Konsep Analisis Sistem | **Rendah** | Pertahankan asosiasi Tamu ke Login (sebagai inisiator autentikasi) dan hubungkan hasil autentikasi ke Anggota/Pustakawan, atau jelaskan dalam narasi use case. |
| **[UC-06]** | `UML/Usecase/usecase_diagram.png` (Versi 34 UC) | Garis asosiasi merah dari aktor Tamu menuju use case pencarian katalog digambar melintang menabrak kotak teks judul system boundary (*"Sistem Informasi Perpustakaan AKSA NOVA"*). | Cacat tata letak visual (visual layout collision) pada perenderan diagram versi 34 UC. | [`UML/Usecase/usecase_diagram.png`](file:///c:/laragon/www/aksanova/UML/Usecase/usecase_diagram.png) | **Sedang** | Rutekan garis asosiasi di bawah judul system boundary agar tidak menimpa teks. |
| **[UC-07]** | `UML/Usecase/usecase_diagram.puml`, `usecase_diagram.html`, `usecase-penjelasan.docx` | Terdapat use case `UC-28` bertajuk *"Membekukan / Mengaktifkan Kartu Anggota"* yang diklaim dapat dipicu manual oleh Pustakawan untuk membekukan akun yang bermasalah. | Di kode riil [`daftar_anggota.php`](file:///c:/laragon/www/aksanova/daftar_anggota.php#L57-L79), aksi admin hanya `unfreeze` (mengaktifkan kartu kembali). Aksi `freeze` manual tidak ada di antarmuka pustakawan. Pembekuan kartu terjadi otomatis di `lupa_kartu.php:185`. | [`daftar_anggota.php:57-79`](file:///c:/laragon/www/aksanova/daftar_anggota.php#L57-L79) | **Tinggi** | Ubah nama use case menjadi *"Mengaktifkan Kembali Kartu Anggota (Unfreeze)"* dan hapus klaim bahwa admin dapat membekukan kartu siswa secara sepihak. |
| **[UC-08]** | `UML/Usecase/usecase-penjelasan.docx` | Pada Tabel T02, sebanyak 27 dari 34 baris mencantumkan path file fiktif/non-existent (`admin/peminjaman.php`, `admin/buku.php`, `api/buku.php`, dll.). Selain itu teks narasi membalik arah `<<extend>>` (*"Mengelola Data Buku <<extend>> Mencari Data Buku via ISBN"*). | Proyek tidak memiliki subdirektori `admin/` atau `api/`. Arah panah `<<extend>>` harus menunjuk dari use case ekstensi (Pencarian ISBN) menuju use case dasar (Tambah Buku). | [`docs/audit_v2/nonexistent_refs.json`](file:///c:/laragon/www/aksanova/docs/audit_v2/nonexistent_refs.json) | **Tinggi** | Revisi Tabel T02 pada dokumen Word dengan mencantumkan nama-nama file PHP root aktual dan perbaiki deskripsi arah panah extend. |

---

## 5. Rekomendasi Perbaikan Use Case (Hanya Ditulis, Tidak Dikerjakan)

1. **Standarisasi Dokumen pada Versi 24 UC:**
   Gunakan diagram `UseCase_Diagram_AKSA_NOVA.png` (24 UC) sebagai rujukan tunggal definitif dan arsipkan versi lama 34 UC yang memiliki cacat tabrakan visual garis judul serta mencantumkan file fiktif.
2. **Koreksi Pemodelan Pengaturan Musik:**
   Pisahkan pengelolaan audio musik latar (tugas Pustakawan) dari preferensi tampilan peramban (aksesibilitas pengguna).
3. **Penyediaan File Sumber Definitif:**
   Simpan definisi diagram dalam format sumber yang rapi dan konsisten dengan hasil visual 24 UC.
