# -*- coding: utf-8 -*-
"""
Generator script for Software Requirements Specification (SRS) Document
Standard: IEEE Std 830-1998 / ISO/IEC/IEEE 29148
Project: Sistem Informasi Perpustakaan Digital AKSA NOVA
Author: Syahrul Bimansyah
Program Keahlian: Pengembangan Perangkat Lunak dan Gim (PPLG) / Rekayasa Perangkat Lunak (RPL)
Instansi: SMK Negeri 1 Rongga, Kabupaten Bandung Barat
Constraint: NO DIAGRAMS, NO UI SCREENSHOTS, Max borrow limit: 40 books.
Optimized: Zero empty pages, compact layout, authentic student-developer voice.
Outputs:
  - docs/SRS_AKSA_NOVA.md
  - docs/SRS_AKSA_NOVA.docx
  - SRS/SRS_AKSA_NOVA.docx
"""

import os
import re
import docx
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml import parse_xml, OxmlElement
from docx.oxml.ns import nsdecls, qn

srs_markdown = """# SPESIFIKASI KEBUTUHAN PERANGKAT LUNAK
## (SOFTWARE REQUIREMENTS SPECIFICATION / SRS)
### SISTEM INFORMASI PERPUSTAKAAN DIGITAL BERBASIS WEB "AKSA NOVA"
**Standar Format**: IEEE Std 830-1998 / ISO/IEC/IEEE 29148  
**Kode Dokumen**: SRS-AKSA-NOVA-2026 | **Versi**: 1.3 | **Status**: Disetujui (Revisi Keanggotaan Guru & Fleksibilitas Email)  
**Penyusun**: Syahrul Bimansyah  
**Program Keahlian**: Pengembangan Perangkat Lunak dan Gim (PPLG) / Rekayasa Perangkat Lunak (RPL)  
**Instansi**: SMK Negeri 1 Rongga, Kabupaten Bandung Barat, Jawa Barat  
**Tahun Ajaran / Terbit**: 2026  

---

# KATA PENGANTAR

Puji dan syukur saya panjatkan ke hadirat Tuhan Yang Maha Esa atas berkat dan rahmat-Nya sehingga dokumen **Spesifikasi Kebutuhan Perangkat Lunak (Software Requirements Specification - SRS)** untuk pengembangan **Sistem Informasi Perpustakaan Digital "AKSA NOVA"** ini dapat diselesaikan dengan baik dan terstruktur.

Penyusunan dokumen dan aplikasi ini berakar dari inisiatif, pengalaman, serta pengamatan langsung saya selama bersekolah di SMK Negeri 1 Rongga. Saya melihat adanya peluang nyata di lingkungan sekolah kita, khususnya pada tata kelola perpustakaan yang masih menghadapi kendala operasional harian. Sebagai siswa program keahlian Pengembangan Perangkat Lunak dan Gim (PPLG / Rekayasa Perangkat Lunak), peluang ini memberikan ruang berharga bagi saya untuk berkreasi, bereksplorasi, dan menerapkan ilmu pemrograman secara mandiri ke dalam bentuk produk nyata yang bermanfaat bagi warga sekolah.

Melalui proyek AKSA NOVA, saya berupaya membuktikan bahwa siswa kejuruan mampu merancang dan membangun solusi teknologi yang mandiri, fungsional, dan berkualitas. Sistem ini dibangun untuk menjawab kebutuhan nyata di lapangan: mempercepat sirkulasi pada waktu istirahat yang singkat (15–30 menit), mengakomodasi kebutuhan peminjaman buku paket pelajaran secara kolektif hingga kuota 40 eksemplar untuk satu rombongan belajar (baik diajukan oleh guru pengampu maupun perwakilan kelas), menerbitkan kartu anggota digital adaptif (Kartu Pelajar & Kartu Pendidik) dengan proteksi privasi kata sandi, serta menyediakan katalog cerdas dengan informasi lokasi rak fisik yang transparan.

Terima kasih saya sampaikan kepada keluarga tercinta yang senantiasa mendoakan dan memberikan dukungan moral, pihak perpustakaan sekolah yang telah memberikan gambaran alur kerja sirkulasi, serta rekan-rekan siswa SMKN 1 Rongga atas berbagai masukan dan saran yang membangun.

Semoga sistem informasi AKSA NOVA dapat memberikan manfaat nyata, memudahkan tata kelola perpustakaan, dan menyulut semangat literasi baru bagi seluruh warga SMK Negeri 1 Rongga.

Rongga, Oktober 2026  

**Syahrul Bimansyah**  
*Pengembang Perangkat Lunak AKSA NOVA*

---

# DAFTAR ISI

* **KATA PENGANTAR**
* **1. PENDAHULUAN**
  * 1.1 Latar Belakang & Alasan Pengembangan Sistem (Background & Motivation)
  * 1.2 Tujuan Dokumen & Tujuan Proyek (Purpose & Project Objectives)
  * 1.3 Ruang Lingkup Produk (Product Scope)
  * 1.4 Definisi, Istilah, dan Akronim (Definitions & Acronyms)
  * 1.5 Referensi Standar Pengembangan (References)
  * 1.6 Gambaran Umum Dokumen (Document Overview)
* **2. DESKRIPSI KESELURUHAN SISTEM (OVERALL DESCRIPTION)**
  * 2.1 Arsitektur & Perspektif Produk (Product Perspective)
  * 2.2 Fungsi Utama Produk (5 Sub-Sistem Modular)
  * 2.3 Karakteristik Pengguna (User Classes & Characteristics)
  * 2.4 Lingkungan Operasional (Operating Environment)
  * 2.5 Batasan Desain & Implementasi (Design Constraints)
  * 2.6 Asumsi dan Ketergantungan Sistem (Assumptions & Dependencies)
* **3. KEBUTUHAN ANTARMUKA EKSTERNAL (EXTERNAL INTERFACES)**
  * 3.1 Antarmuka Pengguna & Sistem Token Visual (User Interfaces)
  * 3.2 Antarmuka Perangkat Keras (Hardware Interfaces)
  * 3.3 Antarmuka Perangkat Lunak & API Eksternal (Software Interfaces)
  * 3.4 Antarmuka Komunikasi Data (Communication Interfaces)
* **4. SPESIFIKASI KEBUTUHAN FUNGSIONAL (FUNCTIONAL REQUIREMENTS)**
  * 4.1 Modul 1: Manajemen Akun & Otentikasi (FR-01 s/d FR-07)
  * 4.2 Modul 2: Inventaris & Master Data Katalog Buku (FR-08 s/d FR-12)
  * 4.3 Modul 3: Interaksi & Keterlibatan Anggota (FR-13 s/d FR-16)
  * 4.4 Modul 4: Sirkulasi Peminjaman, Daring & Denda (FR-17 s/d FR-22)
  * 4.5 Modul 5: Dasbor Analitik, Laporan Excel & Multimedia (FR-23 s/d FR-24)
* **5. KEBUTUHAN NON-FUNGSIONAL (NON-FUNCTIONAL REQUIREMENTS)**
  * 5.1 Kebutuhan Kinerja Sistem (Performance Requirements)
  * 5.2 Kebutuhan Keamanan & Integritas Data (Security Requirements)
  * 5.3 Kebutuhan Keandalan & Ketahanan Transaksi (Reliability & Robustness)
  * 5.4 Kebutuhan Ketersediaan Layanan (Availability Requirements)
  * 5.5 Kebutuhan Usabilitas & Aksesibilitas Mobile (Usability Requirements)
  * 5.6 Kebutuhan Privasi & Perlindungan Data Siswa (Privacy Requirements)
* **6. PEMODELAN ANALISIS & STRUKTUR DATA (DATA & SYSTEM MODELS)**
  * 6.1 Pemodelan Fungsional Tekstual: Spesifikasi 23 Use Case Sistem
  * 6.2 Pemodelan Relasional Data: Skema Hubungan Antar Tabel
  * 6.3 Kamus Data Sistem (Data Dictionary): Spesifikasi 12 Tabel Basis Data
  * 6.4 Pemodelan Berorientasi Objek Tekstual: Spesifikasi 14 Kelas Sistem
* **7. MATRIKS KETERTELUSURAN KEBUTUHAN (TRACEABILITY MATRIX)**
  * 7.1 Pemetaan Kebutuhan Fungsional (FR) terhadap File Kode PHP dan Use Case

---

# 1. PENDAHULUAN

### 1.1 Latar Belakang & Alasan Pengembangan Sistem (Background & Motivation)
Perpustakaan SMK Negeri 1 Rongga merupakan pusat sarana pendukung utama kegiatan belajar mengajar (KBM) yang melayani ratusan siswa dari berbagai program keahlian serta seluruh dewan guru dan staf kependidikan. Keberadaan perpustakaan memegang peranan penting dalam menyediakan buku teks pelajaran, modul kejuruan, referensi ilmiah, serta karya sastra untuk menumbuhkan minat baca siswa.

Namun, dalam kegiatan operasional sehari-hari di sekolah, pengelolaan perpustakaan konvensional masih menghadapi beberapa kendala praktis di lapangan:
1. **Keterbatasan Waktu Istirahat Sekolah**: Waktu istirahat siswa berdurasi relatif singkat (15 hingga 30 menit). Ketika puluhan siswa datang bersamaan ke ruang perpustakaan untuk meminjam atau mengembalikan buku, proses pencatatan manual pada buku register atau antrean pada satu komputer petugas sering kali memakan waktu lama. Hal ini tidak jarang menyebabkan siswa terburu-buru atau bahkan batal meminjam karena bel masuk kelas telah berbunyi.
2. **Ketiadaan Visibilitas Katalog dari Luar Perpustakaan**: Siswa tidak dapat mengecek dari kelas atau rumah apakah buku yang mereka butuhkan sedang tersedia di perpustakaan atau sedang dipinjam orang lain. Siswa juga kesulitan mengetahui posisi rak fisik buku sebelum tiba di lokasi, sehingga harus mencari manual satu per satu di rak buku.
3. **Kebutuhan Peminjaman Kolektif Kelas (Hingga 40 Eksemplar)**: Pada kegiatan belajar mengajar di SMK, guru mata pelajaran kejuruan (seperti PPLG/RPL) maupun mata pelajaran umum sering menugaskan ketua kelas atau perwakilan kelompok untuk meminjam buku paket materi bagi seluruh siswa dalam satu rombongan belajar (rombel) sekaligus. Peminjaman kolektif ini membutuhkan kuota besar—mencapai 30 hingga 40 eksemplar buku per transaksi. Sistem perpustakaan konvensional yang membatasi pinjaman hanya 2–3 buku per orang tidak mampu mengakomodasi kebutuhan riil pembelajaran ini.
4. **Kendala Kartu Fisik Perpustakaan**: Kartu anggota perpustakaan yang dicetak pada kertas atau kartu plastik rentan tertinggal di rumah, rusak, atau hilang. Kondisi ini kerap menjadi hambatan siswa untuk meminjam buku pada jam pelajaran yang membutuhkan bahan bacaan saat itu juga.
5. **Kurangnya Pengingat & Transparansi Denda**: Siswa kerap kali lupa batas waktu pengembalian buku karena tidak adanya notifikasi pengingat personal. Akibatnya, denda keterlambatan menumpuk dan menimbulkan ketidaknyamanan saat penyelesaian administrasi.

**Peluang dan Alasan Penulis**:  
Berangkat dari situasi nyata tersebut, penulis (**Syahrul Bimansyah**) melihat adanya **peluang nyata di SMK Negeri 1 Rongga untuk membangun aplikasi perpustakaan berbasis web yang mandiri, modern, dan tepat sasaran**. Bagi penulis yang mendalami bidang Pengembangan Perangkat Lunak dan Gim (PPLG / Rekayasa Perangkat Lunak), situasi ini bukan sekadar masalah administrasi sekolah, melainkan sebuah kesempatan emas untuk berkreasi, mengeksplorasi kemampuan teknis, dan mengaplikasikan teori rekayasa perangkat lunak secara langsung ke dunia nyata.

Daripada hanya membuat aplikasi latihan sederhana atau studi kasus tanpa dampak nyata, mengembangkan sistem perpustakaan sekolah sendiri memberikan tantangan teknis yang komprehensif: bagaimana merancang arsitektur web yang ringan tanpa *bloatware*, memastikan transaksi stok buku aman secara atomik, melindungi privasi akun siswa dengan penyamaran sandi (*credential masking*), mengintegrasikan API eksternal (Google Books, Open Library, Web Audio API, WhatsApp), serta merancang antarmuka bertema *Obsidian Gold* yang nyaman digunakan oleh sesama siswa maupun pustakawan.

Proyek ini diberi nama **AKSA NOVA**, yang menyatukan konsep ketajaman pandangan (*Aksa*) dan energi pencerahan baru (*Nova*), sebagai representasi komitmen siswa SMK dalam menghadirkan kontribusi teknologi yang berdaya guna bagi almamater tercinta.

### 1.2 Tujuan Dokumen & Tujuan Proyek (Purpose & Project Objectives)
Dokumen *Software Requirements Specification* (SRS) ini disusun berdasarkan panduan standar **IEEE Std 830-1998** dan **ISO/IEC/IEEE 29148** dengan tujuan:
1. **Bagi Pengembang (Developer)**: Memberikan rujukan teknis definitif dalam pengkodean logika bisnis, arsitektur basis data, keamanan akun, dan mekanisme antarmuka.
2. **Bagi Pustakawan & Pengguna**: Menjadi panduan operasional dan acuan fungsionalitas sistem perpustakaan digital di SMK Negeri 1 Rongga.
3. **Bagi Lembaga Sekolah (SMKN 1 Rongga)**: Mendokumentasikan kepemilikan aset perangkat lunak sekolah secara formal, transparan, dan dapat dipelihara (*maintainable*) di masa depan.

Adapun tujuan utama dari produk perangkat lunak **AKSA NOVA** adalah:
* Mengotomatisasi siklus sirkulasi peminjaman dan pengembalian buku di SMK Negeri 1 Rongga secara cepat, akurat, dan terdokumentasi rapi.
* Mengakomodasi kebutuhan peminjaman mandiri maupun peminjaman rombongan belajar kelas dengan kuota maksimal hingga **40 buku** per pengajuan.
* Menerbitkan kartu anggota digital ber-QR Code dengan penyamaran kata sandi (*bullet masking*) untuk menjamin privasi siswa.
* Menyediakan katalog buku daring cerdas lengkap dengan informasi lokasi rak fisik dan pengisian otomatis metadata via integrasi ISBN API eksternal.
* Menerapkan perhitungan denda otomatis dengan toleransi masa tenggang (*grace period*) serta integrasi pengingat via pesan WhatsApp Web.

### 1.3 Ruang Lingkup Produk (Product Scope)
Perangkat lunak yang dikembangkan adalah **AKSA NOVA: Sistem Informasi Perpustakaan Digital Berbasis Web**. Ruang lingkup fungsional sistem mencakup:
* Pendaftaran anggota mandiri (siswa dan tenaga pendidik/guru) dengan validasi nomor identitas (NIS/NIP), penugasan rombel/kategori kelas 'Guru', dan email terverifikasi (`@student.smkn1rongga.sch.id`, `@smkn1rongga.sch.id`, `@gmail.com`).
* Penanganan kasus lupa kartu anggota secara terverifikasi mandiri (*self-service card recovery*) dengan pembekuan kartu lama.
* Pengelolaan katalog buku fisik (CRUD), pemindaian/pencarian otomatis data buku via 10/13 digit nomor ISBN, serta penomoran rak fisik buku.
* Layanan sirkulasi ganda: peminjaman langsung di meja sirkulasi fisik (*offline*) dan pengajuan peminjaman daring (*online booking*) hingga kuota 40 eksemplar buku per transaksi (memfasilitasi peminjaman bahan ajar 1 kelas oleh guru maupun baca mandiri siswa).
* Notifikasi suara lonceng instan (*Web Audio API*) pada layar admin saat ada pengajuan peminjaman daring baru dari siswa.
* Manajemen denda keterlambatan otomatis dan tombol pengingat tagihan via skema URL WhatsApp.
* Pelaporan operasional perpustakaan dan generator ekspor data ke format Microsoft Excel (*SpreadsheetML*) 3-sheet.
* Fitur keterlibatan siswa: penilaian bintang (rating 1–5), bookmark koleksi simpanan, like buku, rekomendasi buku acak, dan pemutar musik latar santai (*AksaAudio*).

Sistem ini tidak mencakup pembacaan e-book berformat PDF penuh di dalam aplikasi maupun gerbang pembayaran digital perbankan otomatis (seluruh pelunasan denda diselesaikan secara langsung di meja sirkulasi sekolah).

### 1.4 Definisi, Istilah, dan Akronim (Definitions & Acronyms)
| Istilah / Akronim | Definisi dan Keterangan |
|---|---|
| **SRS** | *Software Requirements Specification* — Dokumen spesifikasi kebutuhan perangkat lunak formal yang mengikat secara teknis. |
| **IEEE 830-1998** | Standar resmi IEEE untuk pedoman penyusunan spesifikasi kebutuhan perangkat lunak. |
| **PPLG / RPL** | Pengembangan Perangkat Lunak dan Gim / Rekayasa Perangkat Lunak — Program keahlian teknologi kejuruan di SMKN 1 Rongga. |
| **FR** | *Functional Requirement* — Pernyataan kebutuhan fungsional yang mendefinisikan layanan spesifik yang wajib disediakan sistem. |
| **NFR** | *Non-Functional Requirement* — Batasan kualitas sistem yang mencakup aspek performa, keamanan, keandalan, dan usabilitas. |
| **RBAC** | *Role-Based Access Control* — Mekanisme pembatasan hak akses halaman dan data berdasarkan peran pengguna (*Guest*, *Member*, *Admin*). |
| **ISBN** | *International Standard Book Number* — Kode pengidentifikasi unik 10 atau 13 digit untuk buku komersial berlisensi. |
| **Bcrypt** | Fungsi hashing kriptografi satu arah adaptif berbasis algoritma *Blowfish* untuk mengamankan kata sandi di basis data. |
| **Atomisitas Transaksi** | Jaminan bahwa manipulasi data stok dieksekusi tuntas atau dibatalkan seutuhnya tanpa risiko inkonsistensi stok minus. |
| **Web Audio API** | Standar antarmuka peramban untuk sintesis audio gelombang suara secara lokal tanpa jeda latensi jaringan. |
| **SpreadsheetML** | Format representasi lembar kerja Microsoft Excel berbasis XML terstruktur yang kompatibel tanpa memerlukan pustaka berat. |

### 1.5 Referensi Standar Pengembangan (References)
1. IEEE Std 830-1998, *IEEE Recommended Practice for Software Requirements Specifications*, IEEE Computer Society, New York, USA.
2. ISO/IEC/IEEE 29148:2018, *Systems and software engineering — Life cycle processes — Requirements engineering*.
3. Standar Kompetensi Kerja Nasional Indonesia (SKKNI) Bidang Pengembangan Perangkat Lunak dan Gim.
4. Panduan Penyelenggaraan Perpustakaan Sekolah Standar Nasional Perpustakaan (SNP 009:2011), Perpustakaan Nasional Republik Indonesia.
5. Kurikulum Merdeka Program Keahlian Pengembangan Perangkat Lunak dan Gim (PPLG) SMK Negeri 1 Rongga.

### 1.6 Gambaran Umum Dokumen (Document Overview)
Dokumen SRS ini diorganisasikan ke dalam tujuh bab utama:
* **Bab 1 (Pendahuluan)** memaparkan latar belakang riil SMKN 1 Rongga, alasan personal dan profesional penulis, tujuan sistem, ruang lingkup, definisi istilah, dan acuan standar.
* **Bab 2 (Deskripsi Keseluruhan Sistem)** menguraikan arsitektur produk, lima sub-sistem utama, profil pengguna, lingkungan operasi, dan batasan desain implementasi.
* **Bab 3 (Kebutuhan Antarmuka Eksternal)** merinci kebutuhan antarmuka pengguna berbasis token warna, antarmuka perangkat keras, antarmuka API eksternal, dan komunikasi jaringan.
* **Bab 4 (Spesifikasi Kebutuhan Fungsional)** menyajikan spesifikasi mendalam 24 kebutuhan fungsional (FR-01 hingga FR-24) dalam format tabular berstandar industri.
* **Bab 5 (Kebutuhan Non-Fungsional)** menguraikan parameter kualitas performa, keamanan Bcrypt, keandalan transaksi atomik, ketersediaan, usabilitas mobile, dan privasi data siswa.
* **Bab 6 (Pemodelan Analisis & Struktur Data)** menyajikan spesifikasi tekstual 23 Use Case, struktur integritas relasi tabel, kamus data lengkap 12 tabel basis data, dan 14 pemodelan kelas OOP tanpa ketergantungan diagram gambar.
* **Bab 7 (Matriks Ketertelusuran)** menyajikan matriks ketertelusuran keterkaitan antara kode kebutuhan fungsional, use case, dan file kode sumber PHP yang mengimplementasikannya.

---

# 2. DESKRIPSI KESELURUHAN SISTEM (OVERALL DESCRIPTION)

### 2.1 Arsitektur & Perspektif Produk (Product Perspective)
AKSA NOVA dirancang sebagai sistem informasi mandiri (*self-contained system*) berbasis web arsitektur *Client-Server Three-Tier* modular. Seluruh komponen sistem dibangun secara *native* dan terstruktur untuk menghasilkan waktu pemuatan yang sangat cepat, mudah dipelihara oleh sekolah, dan tidak bergantung pada kerangka kerja berat pihak ketiga yang memerlukan konfigurasi rumit:

```
[LAPISAN PRESENTASI (CLIENT TIER)]
Peramban Web Klien (Desktop / Tablet / Ponsel Cerdas)
HTML5 Semantik | CSS3 Custom Token (Obsidian Gold) | Vanilla JS Fetch API
                          │ ▲
                          ▼ │ Protokol HTTP/HTTPS (Port 80/443) & JSON Payload
[LAPISAN LOGIKA BISNIS (APPLICATION TIER)]
Web Server Apache 2.4+ | PHP 8.1+ Strict Types
Modul Otentikasi RBAC | Mesin Sirkulasi Atomik | Kalkulator Denda
Integrasi API Eksternal: Google Books API, Open Library API, Web Audio API, WhatsApp
                          │ ▲
                          ▼ │ Driver MySQLi (Prepared Statements) - UTF8MB4
[LAPISAN PENYIMPANAN DATA (DATABASE TIER)]
RDBMS MySQL 8.0+ / MariaDB 10.4+
12 Tabel Relasional Terindeks dengan Integritas Referensial Penuh
```

### 2.2 Fungsi Utama Produk (5 Sub-Sistem Modular)
AKSA NOVA mengonsolidasikan seluruh kebutuhan operasional perpustakaan sekolah ke dalam lima pilar sub-sistem utama:
1. **Sub-Sistem Keanggotaan & Identitas Digital**:
   * Pendaftaran akun mandiri siswa dengan validasi NIS dan email domain resmi sekolah.
   * Penerbitan kartu anggota digital ber-QR Code dengan pengamanan penyamaran kata sandi (*bullet masking*).
   * Layanan mandiri penanganan lupa kartu anggota (*card recovery*) dengan pembekuan otomatis status kartu lama.
   * Manajemen rombongan belajar kelas dan pemrosesan kenaikan kelas massal di awal tahun ajaran baru.
2. **Sub-Sistem Inventarisasi & Katalog Cerdas**:
   * Pengelolaan data master buku (CRUD: Tambah, Lihat, Sunting, Hapus).
   * Pengisian otomatis data buku (*auto-fill metadata*) berbasis pemindaian/pencarian 10 atau 13 digit nomor ISBN melalui Google Books API dan Open Library API.
   * Penomoran lokasi penempatan rak fisik buku (contoh: "Rak B-1") untuk memandu navigasi fisik pengunjung di ruang perpustakaan.
   * Manajemen stok fisik buku dengan pemotongan stok otomatis dan proteksi atomik.
3. **Sub-Sistem Keterlibatan Pembaca (Reader Engagement)**:
   * Penelusuran katalog multi-genre dengan pencarian instan asinkron (*live AJAX search*).
   * Fitur rekomendasi buku acak ("Bingung Mau Baca Apa?") untuk menarik minat baca siswa.
   * Ulasan rating buku skala 1 hingga 5 bintang dari siswa pembaca (hak akses admin dibatasi untuk menjamin keaslian ulasan).
   * Penandaan buku favorit (*bookmark*) dan pencatatan like buku.
   * Pemutar musik latar santai (*AksaAudio*) yang tetap berputar saat siswa menjelajahi halaman.
4. **Sub-Sistem Sirkulasi Peminjaman & Denda**:
   * Pengajuan peminjaman daring (*online booking*) dengan kuota fleksibel hingga **40 buku** per transaksi dan verifikasi unggah kartu digital.
   * Notifikasi realtime bersuara lonceng (*Web Audio API chime*) dan pop-up modal di layar admin saat ada pengajuan daring yang masuk.
   * Pelayanan sirkulasi langsung di meja perpustakaan oleh petugas pustakawan.
   * Penyelesaian pengembalian buku fisik dan pengembalian kuantitas stok secara otomatis.
   * Kalkulasi denda keterlambatan otomatis dengan toleransi masa tenggang (*grace period*).
   * Integrasi tombol pengingat tagihan denda ke nomor WhatsApp siswa secara langsung.
5. **Sub-Sistem Analitik, Pelaporan & Multimedia**:
   * Dasbor eksekutif dengan visualisasi metrik statistik (total buku, anggota aktif, peminjaman berjalan, dan total denda).
   * Generator ekspor data operasional ke lembar kerja Microsoft Excel (*SpreadsheetML*) 3-sheet terstruktur (Ringkasan, Sirkulasi, dan Katalog Buku).
   * Pengelolaan media carousel banner promo beranda (dukungan format JPG, PNG, GIF, hingga video MP4/WEBM).
   * Pengaturan profil pustakawan dan preferensi tema tampilan.

### 2.3 Karakteristik Pengguna (User Classes & Characteristics)
Sistem AKSA NOVA membagi hak akses ke dalam tiga kelompok pengguna berdasarkan prinsip *Role-Based Access Control* (RBAC):
| Golongan Pengguna | Deskripsi Karakteristik | Hak Akses Utama dalam Sistem |
|---|---|---|
| **Tamu (Guest)** | Pengunjung umum atau siswa yang belum melakukan autentikasi login. Kemampuan teknis dasar mengoperasikan peramban web. | Menjelajahi katalog buku publik, mencari buku berdasarkan judul/genre, melihat informasi lokasi rak fisik dan ketersediaan stok, serta mengakses formulir mandiri Lupa Kartu. |
| **Anggota (Member: Siswa & Guru)** | Sivitas akademika SMK Negeri 1 Rongga (siswa dengan NIS dan rombel kelas, maupun tenaga pendidik/guru dengan NIP/NUPTK/NIK dan penugasan kelas 'Guru') yang telah memiliki akun terverifikasi. Terbiasa menggunakan peramban web dan gawai ponsel pintar. | Mengakses dasbor anggota, menayangkan dan mengunduh kartu anggota digital adaptif (Kartu Pelajar / Kartu Pendidik) ber-QR Code, menyukai dan menyimpan buku favorit, memberikan ulasan rating bintang, mengajukan peminjaman buku daring hingga kuota 40 eksemplar (memfasilitasi peminjaman mandiri maupun peminjaman buku paket pelajaran 1 kelas oleh guru), serta memantau riwayat pinjaman dan tagihan denda. |
| **Pustakawan (Admin)** | Petugas atau pengelola perpustakaan sekolah yang bertanggung jawab atas tata kelola sirkulasi fisik dan administrasi. | Mengelola master data buku, menggunakan auto-fill ISBN, memproses persetujuan pengajuan daring dengan notifikasi lonceng, melayani sirkulasi langsung, memproses pengembalian buku dan denda, mengelola anggota dan rombel kelas, mengekspor laporan Excel 3-sheet, serta mengatur banner dan musik latar. |

### 2.4 Lingkungan Operasional (Operating Environment)
* **Lingkungan Sisi Server**:
  * Sistem Operasi: Linux Server (Ubuntu 20.04 LTS atau lebih baru) / Windows Server / Lingkungan pengembangan Windows 10/11 (Laragon / XAMPP).
  * Web Server: Apache HTTP Server versi 2.4 atau lebih tinggi dengan modul `mod_rewrite` dan ekstensi cURL aktif.
  * Bahasa Pemrograman: PHP versi 8.1 atau lebih tinggi (menggunakan konfigurasi `declare(strict_types=1)` dan penanganan tanggal objek `DateTimeImmutable`).
  * Database Management System (DBMS): MySQL Community Server versi 8.0+ atau MariaDB versi 10.4+ dengan konfigurasi character set `utf8mb4_general_ci`.
* **Lingkungan Sisi Klien**:
  * Peramban Web: Google Chrome (v90+), Mozilla Firefox (v88+), Microsoft Edge (v90+), atau Safari Mobile iOS (v14+).
  * Perangkat Keras Klien: Komputer desktop, laptop, tablet, atau ponsel cerdas dengan dukungan JavaScript aktif.
  * Resolusi Layar: Responsif penuh (*fluid adaptation*) dari lebar layar minimum 360 piksel (*mobile viewport*) hingga 1920 piksel (*full HD desktop*).

### 2.5 Batasan Desain & Implementasi (Design Constraints)
1. **Proteksi Privasi Kata Sandi**: Kata sandi anggota tidak boleh ditampilkan dalam format teks polos (*plain-text*) pada kartu anggota digital, dasbor pengguna, maupun tabel data pustakawan. Sistem wajib menerapkan penyamaran visual (*bullet masking* `••••••••`).
2. **Integritas Transaksi Stok Atomik**: Seluruh proses pengurangan stok buku wajib dieksekusi dengan kueri bersyarat atomik (`WHERE stok >= ? AND stok > 0`) guna mencegah kuantitas stok bernilai negatif akibat akses bersamaan (*race condition*).
3. **Batas Maksimal Kuota Peminjaman Daring**: Kuantitas pengajuan peminjaman daring dibatasi secara tegas maksimal **40 eksemplar buku** per satu nomor transaksi pengajuan.
4. **Pengalaman Asinkron Ringan (*Zero-Bloat SPA Feel*)**: Seluruh interaksi pencarian, pemberian rating, penambahan like, dan polling notifikasi wajib menggunakan asinkron Fetch API tanpa memuat ulang halaman penuh.
5. **Kemandirian Pustaka (*Zero External CSS Dependency*)**: Tidak diperkenankan bergantung pada kerangka kerja CSS eksternal berskala besar yang memperlambat pemuatan pada koneksi internet sekolah yang terbatas; seluruh antarmuka menggunakan CSS modern modular berbasis *CSS Variables*.

### 2.6 Asumsi dan Ketergantungan Sistem (Assumptions & Dependencies)
* Ketersediaan jaringan internet diperlukan saat sistem memanggil Google Books API atau Open Library API untuk mengambil metadata buku secara otomatis via ISBN.
* Fitur pengingat denda via WhatsApp mengandalkan ketersediaan aplikasi WhatsApp atau WhatsApp Web pada perangkat pengguna melalui skema protokol `https://wa.me/`.
* Pengguna mendaftar menggunakan alamat email sekolah resmi (`@student.smkn1rongga.sch.id` untuk siswa, `@smkn1rongga.sch.id` untuk guru/staf) maupun alamat email umum terverifikasi (`@gmail.com`) untuk menjamin kemudahan akses seluruh warga sekolah.

---

# 3. KEBUTUHAN ANTARMUKA EKSTERNAL (EXTERNAL INTERFACES)

### 3.1 Antarmuka Pengguna & Sistem Token Visual (User Interfaces)
Antarmuka pengguna AKSA NOVA dirancang dengan memadukan estetika modern bernuansa *Obsidian Gold* yang memiliki rasio kontras tinggi sesuai standar aksesibilitas WCAG 2.1 Level AA:
* **Token Warna Utama**:
  * Warna Latar Utama (`--bg`): `#090c10` (Obsidian Gelap Mendalam).
  * Panel Navigasi Sidebar (`--sidebar-bg`): `#10151b`.
  * Kartu Konten & Wadah Formulir (`--card`): `#121820`.
  * Wadah Kartu Buku (`--book-card`): `#161e27`.
  * Aksen Utama Emas Mewah (`--accent`): `#d8b878` dan `#f0d9a8`.
  * Warna Teks Utama (`--text`): `#eef3f4` (Tingkat keterbacaan tinggi di atas latar gelap).
  * Warna Teks Sekunder (`--muted`): `rgba(238, 243, 244, 0.65)`.
  * Garis Batas Pembatas (`--border-color`): `rgba(216, 184, 120, 0.18)`.
* **Tipografi**: Menggunakan kombinasi font Google *Outfit* (sans-serif geometris modern untuk teks isi, antarmuka, dan tombol) serta *Cormorant Garamond* (serif klasik elegan untuk judul utama dan kartu perpustakaan).
* **Adaptasi Perangkat Bergerak (Mobile First Adaptation)**:
  * Pada layar desktop (lebar > 768px), sidebar admin berposisi tetap di sisi kiri dengan lebar 220px.
  * Pada layar ponsel (lebar ≤ 768px), sidebar bertransisi menjadi *off-canvas drawer* yang dapat dibuka/tutup melalui tombol menu burger di *mobile topbar*.
  * Seluruh tabel data administrasi bertransformasi secara otomatis menjadi tata letak kartu individual vertikal (*Card View*) pada layar ponsel, meniadakan kebutuhan geser horizontal (*horizontal scroll*) yang menyulitkan pengguna gawai.

### 3.2 Antarmuka Perangkat Keras (Hardware Interfaces)
* **Kamera Peramban (Webcam / Mobile Camera)**: Didukung melalui antarmuka HTML5 Media Capture API untuk memudahkan siswa mengambil foto profil langsung saat registrasi akun mandiri.
* **Perangkat Pemindai Barcode / Kamera Ponsel**: Mendukung pemindaian QR Code pada kartu identitas digital untuk identifikasi cepat di meja perpustakaan.

### 3.3 Antarmuka Perangkat Lunak & API Eksternal (Software Interfaces)
* **Google Books REST API & Open Library API**: Endpoint layanan web publik berbasis JSON yang dipanggil secara aman dari sisi server (`cari_isbn.php`) untuk mengambil metadata buku (judul, nama penulis, sinopsis cerita, penerbit, tahun terbit, dan tautan gambar sampul beresolusi tinggi).
* **Web Audio API**: Antarmuka pemrosesan audio lokal peramban web yang membangkitkan nada getaran gelombang sinus sintetis (frekuensi 880 Hz dengan envelope decay) untuk memutar notifikasi suara lonceng tanpa memerlukan pengunduhan berkas audio eksternal dan tanpa latensi jaringan (0 ms).
* **WhatsApp Click-to-Chat URL Scheme**: Protokol integrasi tautan `https://wa.me/{nomor_hp}?text={pesan_url_encoded}` yang memicu pembukaan aplikasi WhatsApp secara instan dengan draf pesan penagihan denda keterlambatan yang telah terisi lengkap.
* **SpreadsheetML Engine**: Mesin pembangkit berkas spreadsheet berbasis XML MIME type `application/vnd.ms-excel` yang memproduksi berkas laporan multi-sheet terstruktur secara instan tanpa membebani memori server.

### 3.4 Antarmuka Komunikasi Data (Communication Interfaces)
* Protokol Komunikasi: Hypertext Transfer Protocol (HTTP/1.1) dan Transport Layer Security (HTTPS) pada port standar TCP 80 / 443.
* Format Pertukaran Data: JSON (JavaScript Object Notation) dengan enkoding karakter UTF-8 terenkapsulasi untuk seluruh komunikasi asinkron antara antarmuka klien dan server PHP.

---

# 4. SPESIFIKASI KEBUTUHAN FUNGSIONAL (FUNCTIONAL REQUIREMENTS)

### 4.1 Modul 1: Manajemen Akun & Otentikasi
| ID Kebutuhan | Nama Kebutuhan Fungsional | Aktor Terlibat | Stimulus / Masukan | Proses & Respon Perangkat Lunak | Prioritas |
|:---:|---|---|---|---|:---:|
| **FR-01** | Registrasi Anggota Mandiri (Siswa & Guru) | Tamu | Pilihan Tipe Anggota (Siswa/Guru), Nama Lengkap, Nomor Identitas (NIS/NIP), Pilihan Rombel Kelas (Siswa) atau Kategori Otomatis 'Guru' (Guru), Email Terverifikasi (@student.smkn1rongga.sch.id, @smkn1rongga.sch.id, @gmail.com), No HP/WA, Kata Sandi, Berkas Foto Profil. | Sistem memvalidasi keunikan nomor identitas (NIS/NIP) dan email, mengenkripsi kata sandi menggunakan Bcrypt, menerbitkan nomor unik kartu anggota format `AN-YYYY-XXXXX`, menyimpan path foto profil, dan langsung mengaktifkan akun anggota (`role = 'member'`, status `approved`). | Critical |
| **FR-02** | Otentikasi Login Berbasis Peran | Pengguna | Username / Email terdaftar dan Kata Sandi. | Sistem memverifikasi kredensial via `password_verify()`, memeriksa status akun (aktif/beku), mencatat data sesi `$_SESSION`, dan mengarahkan pengguna secara otomatis ke beranda utama (`beranda.php`) dengan hak akses menu yang disesuaikan secara dinamis berdasarkan peran (Admin/Anggota). | Critical |
| **FR-03** | Penerbitan Kartu Anggota Digital Adaptif | Anggota, Admin | Nomor ID Anggota atau sesi login anggota. | Sistem menayangkan kartu identitas digital adaptif (desain 'Kartu Pelajar' untuk siswa atau 'Kartu Pendidik / Teacher Card' untuk guru) ber-QR Code dengan foto profil, NIS/NIP, kelas/status pendidik, nomor anggota, serta penyamaran kata sandi (*bullet masking* `••••••••`). Sistem menyediakan tombol unduh kartu sebagai berkas gambar PNG. | High |
| **FR-04** | Pelaporan Mandiri Lupa Kartu | Tamu | Alamat email terdaftar dan kata sandi lama siswa. | Sistem memvalidasi kecocokan data, membekukan status kartu lama (`card_status = 'frozen'`), mengizinkan pembuatan sandi baru, dan menerbitkan kartu anggota baru yang bersih. | High |
| **FR-05** | Manajemen Anggota & Filter Kategori | Admin | ID Anggota, Filter Kategori Tab (Semua/Siswa/Guru), Filter Kelas, Opsi Sandi Baru / Pembangkit Sandi Acak. | Admin dapat memfilter daftar anggota berdasarkan kategori (Semua, Siswa, Guru) serta filter kelas, membekukan/mengaktifkan kembali akun anggota, serta menetapkan sandi baru atau membangkitkan kombinasi sandi acak tanpa pernah menampilkan sandi polos di tabel data. | High |
| **FR-06** | Pembaruan Profil Pustakawan | Admin | Nama tampilan baru, berkas foto avatar, kata sandi admin. | Sistem memperbarui data profil pustakawan melalui modal universal yang dapat diakses di seluruh halaman admin dan mencerminkan perubahan seketika. | Medium |
| **FR-07** | Manajemen Rombongan Belajar Kelas | Admin | Nama rombel kelas baru / ID kelas yang dihapus. | Admin dapat menambah daftar rombel baru, menghapus kelas yang tidak aktif, dan menjalankan utilitas kenaikan kelas massal siswa pada pergantian tahun ajaran baru (anggota berkategori 'Guru' secara otomatis terlindungi dan dikecualikan dari proses kenaikan kelas). | Medium |

### 4.2 Modul 2: Inventaris & Master Data Katalog Buku
| ID Kebutuhan | Nama Kebutuhan Fungsional | Aktor Terlibat | Stimulus / Masukan | Proses & Respon Perangkat Lunak | Prioritas |
|:---:|---|---|---|---|:---:|
| **FR-08** | Pengelolaan Master Data Buku (CRUD) | Admin | Judul, Penulis, ISBN, Genre, Sinopsis, Stok Fisik, Lokasi Rak, Berkas Sampul. | Sistem memvalidasi kelengkapan data, memproses unggahan gambar sampul, menyimpan record ke tabel `buku`, dan memperbarui katalog buku perpustakaan sekolah. | Critical |
| **FR-09** | Pencarian Otomatis Metadata via ISBN | Admin | 10 atau 13 digit nomor unik ISBN buku. | Sistem memanggil Google Books API (dengan fallback Open Library API), mengurai respon JSON, mengunduh file gambar sampul otomatis, dan mengisi formulir penambahan buku secara otomatis tanpa input manual. | High |
| **FR-10** | Penelusuran Katalog & Filter Genre | Semua | Kata kunci pencarian, pemilihan filter kategori genre. | Sistem melakukan kueri asinkron (*live AJAX search*) dan memperbarui daftar kartu buku secara seketika (*instant response*) tanpa memuat ulang halaman peramban. | High |
| **FR-11** | Informasi Lokasi Penempatan Rak Fisik | Semua | Pemilihan buku pada katalog. | Sistem menampilkan penomoran kode lokasi rak buku fisik (contoh: *"Rak B-1"*) untuk memandu navigasi fisik pengunjung saat mencari buku di ruang perpustakaan sekolah. | Medium |
| **FR-12** | Manajemen Stok Fisik Buku Atomik | Sistem | Pemicu transaksi peminjaman, pengembalian, atau persetujuan daring. | Sistem mengurangi atau menambah kuantitas stok buku secara aman di level basis data (`WHERE stok >= ? AND stok > 0`), serta menandai status visual "Kosong" jika stok tersisa 0. | Critical |

### 4.3 Modul 3: Interaksi & Keterlibatan Anggota (Reader Engagement)
| ID Kebutuhan | Nama Kebutuhan Fungsional | Aktor Terlibat | Stimulus / Masukan | Proses & Respon Perangkat Lunak | Prioritas |
|:---:|---|---|---|---|:---:|
| **FR-13** | Penandaan Suka (*Like*) dan Simpan Buku | Anggota | Klik tombol suka (*heart*) atau tombol simpan (*bookmark*). | Sistem mencatat relasi suka atau simpanan di tabel `buku_likes` dan `buku_favorites` via AJAX, memperbarui angka hitungan suka, dan menampilkannya pada tab koleksi simpanan siswa. | Medium |
| **FR-14** | Pemberian Ulasan Rating Bintang | Anggota | Pemilihan nilai rating 1 hingga 5 bintang pada buku. | Sistem mencatat atau memperbarui rating buku dari siswa dan menghitung rata-rata skor bintang buku. Hak akses akun admin dibatasi agar tidak dapat memberi rating guna menjamin keaslian penilaian siswa. | Medium |
| **FR-15** | Rekomendasi Buku Acak | Anggota | Klik tombol "Bingung Mau Baca Apa?". | Sistem mengeksekusi kueri acak terhadap katalog buku berkualitas dan menampilkannya dalam jendela modal interaktif untuk memicu minat baca siswa. | Low |
| **FR-16** | Preferensi Mode Tampilan Tema | Semua | Tombol pengubah tema warna (Gelap / Emas / Terang). | Sistem menyimpan preferensi tema pada *Web LocalStorage* peramban klien dan menerapkannya secara instan di seluruh sesi penjelajahan pengguna. | Low |

### 4.4 Modul 4: Sirkulasi Peminjaman, Daring & Denda
| ID Kebutuhan | Nama Kebutuhan Fungsional | Aktor Terlibat | Stimulus / Masukan | Proses & Respon Perangkat Lunak | Prioritas |
|:---:|---|---|---|---|:---:|
| **FR-17** | Pengajuan Peminjaman Buku Daring (Maks 40 Buku) | Anggota | Pilihan buku, jumlah eksemplar (1 s/d 40), batas tanggal kembali, berkas unggahan kartu. | Sistem memvalidasi ketersediaan stok fisik buku, memastikan jumlah pengajuan berada dalam rentang valid (**1 s/d 40 buku**, dirancang untuk memfasilitasi kebutuhan peminjaman buku paket pelajaran satu kelas oleh guru maupun baca mandiri siswa), memeriksa kelengkapan file kartu anggota, dan mencatat transaksi ke tabel `pengajuan_peminjaman` berstatus 'menunggu'. | High |
| **FR-18** | Notifikasi Pengajuan Realtime Audio | Admin | Deteksi siklus polling asinkron berkala (interval 4 detik). | Sistem mendeteksi adanya transaksi pengajuan berstatus 'menunggu', membunyikan notifikasi audio lonceng sintetis (*Web Audio API*), dan menampilkan modal peringatan di layar admin secara otomatis. | High |
| **FR-19** | Verifikasi Persetujuan / Penolakan Pengajuan | Admin | Klik tombol 'Setujui' atau 'Tolak' (beserta catatan alasan penolakan). | Jika disetujui, sistem memotong stok buku secara atomik, menerbitkan transaksi sirkulasi aktif, dan mengarsipkan status pengajuan. Jika ditolak, sistem mencatat keterangan penolakan untuk dibaca siswa. | High |
| **FR-20** | Pelayanan Peminjaman Langsung di Meja | Admin | Pilihan identitas anggota aktif, pilihan buku, tanggal jatuh tempo. | Pustakawan memproses transaksi peminjaman fisik langsung di meja perpustakaan dengan pemotongan stok otomatis dan pencatatan waktu peminjaman server. | High |
| **FR-21** | Pengembalian Buku & Hitung Denda Otomatis | Admin | Klik tombol 'Kembalikan' pada baris transaksi aktif. | Sistem mencatat waktu pengembalian, mengembalikan kuantitas stok buku (+1), dan menghitung denda keterlambatan secara otomatis berdasarkan formula: `(Hari Terlambat - Grace Period) × Tarif Denda Harian`. | Critical |
| **FR-22** | Penagihan Denda via Tautan WhatsApp | Admin | Klik tombol 'Kirim Pengingat WA' pada transaksi terlambat. | Sistem menyusun draf pesan penagihan denda terformat (nama siswa, judul buku, hari telat, total nominal denda) dan membuka antarmuka WhatsApp Web langsung ke nomor ponsel siswa yang bersangkutan. | Medium |

### 4.5 Modul 5: Dasbor Analitik, Laporan Excel & Multimedia
| ID Kebutuhan | Nama Kebutuhan Fungsional | Aktor Terlibat | Stimulus / Masukan | Proses & Respon Perangkat Lunak | Prioritas |
|:---:|---|---|---|---|:---:|
| **FR-23** | Dasbor Analitik & Ekspor Laporan Excel | Admin | Pemilihan filter tahun laporan operasional. | Sistem menghitung metrik statistik perpustakaan (total buku, anggota, peminjaman aktif, penerimaan denda) dan menghasilkan berkas spreadsheet Microsoft Excel (*SpreadsheetML*) 3-sheet terstruktur secara instan. | High |
| **FR-24** | Manajemen Media Banner & Audio Beranda | Admin | Unggahan berkas banner (gambar/GIF/video MP4/WEBM), pengaturan audio latar. | Admin dapat mengatur urutan carousel banner promo beranda, mengaktifkan/menonaktifkan slide promosi, serta mengelola lagu pengiring latar beranda (*AksaAudio*). | Low |

---

# 5. KEBUTUHAN NON-FUNGSIONAL (NON-FUNCTIONAL REQUIREMENTS)

### 5.1 Kebutuhan Kinerja Sistem (Performance Requirements)
* **NFR-PERF-01**: Waktu muat halaman beranda (*First Contentful Paint*) tidak boleh melebihi 1,5 detik pada jaringan lokal sekolah (LAN/Wi-Fi) dengan beban 50 pengguna simultan.
* **NFR-PERF-02**: Muatan data respon kueri polling notifikasi pengajuan admin (`api_cek_pengajuan_admin.php`) berukuran sangat ringan (< 1 KB payload JSON) untuk mencegah lonjakan beban CPU server pada interval 4 detik.
* **NFR-PERF-03**: Audio notifikasi lonceng dibangkitkan langsung secara sintetis oleh mesin peramban klien melalui *Web Audio API*, menghasilkan latensi 0 ms tanpa konsumsi bandwidth jaringan server.

### 5.2 Kebutuhan Keamanan & Integritas Data (Security Requirements)
* **NFR-SEC-01**: Seluruh kata sandi pengguna wajib dienkripsi secara satu arah menggunakan algoritma *Bcrypt* (`password_hash(PASSWORD_BCRYPT)` dengan cost factor standar PHP).
* **NFR-SEC-02**: Seluruh operasi kueri basis data yang melibatkan variabel masukan pengguna wajib menggunakan *Prepared Statements* (MySQLi parameter binding) untuk mencegah celah keamanan *SQL Injection*.
* **NFR-SEC-03**: Seluruh data dinamis keluaran sistem yang dirender ke tampilan antarmuka web wajib disanitasi menggunakan fungsi `htmlspecialchars()` dengan flag `ENT_QUOTES` untuk mengeliminasi potensi serangan *Cross-Site Scripting* (XSS).
* **NFR-SEC-04**: Pengendalian akses halaman berbasis sesi peran pengguna (`$_SESSION['role']`). Upaya akses tidak sah ke modul administratif pustakawan wajib diblokir dan dialihkan seketika ke halaman masuk dengan pesan peringatan.

### 5.3 Kebutuhan Keandalan & Ketahanan Transaksi (Reliability & Robustness)
* **NFR-REL-01**: Operasi pemotongan stok buku fisik wajib menerapkan klausa pembatas atomik `WHERE stok >= ? AND stok > 0`. Jika stok tidak mencukupi, transaksi dibatalkan seutuhnya guna mencegah nilai kuantitas minus.
* **NFR-REL-02**: Fungsi perhitungan denda wajib dilindungi blok penanganan kesalahan (*try-catch*) untuk mengantisipasi anomali format tanggal peminjaman tanpa mengakibatkan kegagalan server (*Internal Server Error 500*).

### 5.4 Kebutuhan Ketersediaan Layanan (Availability Requirements)
* **NFR-AVAIL-01**: Sistem dirancang untuk mampu beroperasi secara andal selama jam operasional sekolah (06.30 – 17.30 WIB) dengan target ketersediaan layanan minimal 99,5%.

### 5.5 Kebutuhan Usabilitas & Aksesibilitas Mobile (Usability Requirements)
* **NFR-USE-01**: Seluruh antarmuka web wajib beradaptasi secara mulus (*fluid mobile adaptation*) pada rentang resolusi lebar 360px hingga 768px tanpa menimbulkan bilah geser horizontal (*no horizontal scrollbar*).
* **NFR-USE-02**: Formulir autentikasi dan pendaftaran dilengkapi petunjuk masukan yang jelas, indikator validasi real-time, serta tombol intip kata sandi (*eye-toggle peek*) untuk meminimalisasi kesalahan pengetikan pengguna.

### 5.6 Kebutuhan Privasi & Perlindungan Data Siswa (Privacy Requirements)
* **NFR-PRIV-01**: Kata sandi anggota tidak boleh ditampilkan dalam format teks biasa (*plain text*) pada kartu anggota digital maupun tabel data admin sekolah, dan wajib disamarkan dalam bentuk titik-titik (`••••••••`).
* **NFR-PRIV-02**: Pendaftaran anggota divalidasi keabsahannya menggunakan alamat email sekolah resmi (`@student.smkn1rongga.sch.id` untuk siswa, `@smkn1rongga.sch.id` untuk guru/staf) atau email umum terverifikasi (`@gmail.com`).

---

# 6. PEMODELAN ANALISIS & STRUKTUR DATA (DATA & SYSTEM MODELS)

### 6.1 Pemodelan Fungsional Tekstual: Spesifikasi 23 Use Case Sistem
Sistem AKSA NOVA mengorganisasi fungsi operasionalnya ke dalam 23 Use Case tekstual yang terstruktur rapi tanpa ketergantungan pada diagram gambar:

1. **UC-01: Registrasi Akun Anggota Mandiri**
   * *Aktor*: Tamu (Siswa Baru / Guru)
   * *Deskripsi*: Calon anggota mendaftarkan diri secara mandiri dengan memilih tipe anggota (Siswa atau Guru), mengisikan nomor identitas (NIS untuk siswa / NIP untuk guru), nama lengkap, rombel kelas (untuk siswa) atau otomatis 'Guru' (untuk pendidik), nomor kontak WhatsApp, email terverifikasi (@student..., @smkn1rongga..., @gmail.com), kata sandi, dan mengunggah foto profil.
   * *Prakondisi*: Calon anggota belum memiliki akun terdaftar dan memiliki email yang diizinkan sistem.
   * *Pascakondisi*: Akun terdaftar aktif di tabel `users` (dengan `role = 'member'`), dan nomor unik kartu anggota format `AN-YYYY-XXXXX` langsung diterbitkan.
2. **UC-02: Autentikasi Masuk (Login Akun)**
   * *Aktor*: Tamu / Anggota / Pustakawan
   * *Deskripsi*: Pengguna memasukkan kredensial untuk memperoleh hak akses sesuai peran (*Role-Based Access*).
   * *Prakondisi*: Pengguna telah terdaftar di sistem.
   * *Pascakondisi*: Sesi peran diaktifkan dan diarahkan ke dasbor yang sesuai.
3. **UC-03: Menayangkan & Mengunduh Kartu Anggota Digital Adaptif**
   * *Aktor*: Anggota (Siswa / Guru) / Pustakawan
   * *Deskripsi*: Menampilkan kartu identitas digital adaptif (desain 'Kartu Pelajar' untuk siswa atau 'Kartu Pendidik / Teacher Card' untuk guru) ber-QR Code dengan penyamaran kata sandi titik-titik dan menyediakan opsi unduh berkas gambar PNG.
4. **UC-04: Menjelajahi Katalog & Filter Kategori Genre**
   * *Aktor*: Semua Pengguna (Tamu, Anggota, Pustakawan)
   * *Deskripsi*: Pengguna mencari koleksi buku berdasarkan kata kunci judul, nama penulis, atau kategori genre secara instan asinkron.
5. **UC-05: Memeriksa Informasi Detail & Lokasi Rak Buku**
   * *Aktor*: Semua Pengguna
   * *Deskripsi*: Melihat sinopsis cerita buku, jumlah stok fisik tersedia, dan nomor kode penempatan rak buku di perpustakaan.
6. **UC-06: Mengajukan Pemulihan Akun Lupa Kartu**
   * *Aktor*: Tamu / Anggota
   * *Deskripsi*: Siswa memverifikasi kepemilikan akun untuk membekukan kartu lama yang hilang dan menerbitkan kartu anggota baru.
7. **UC-07: Mengajukan Peminjaman Buku Daring (Maksimal 40 Buku)**
   * *Aktor*: Anggota (Siswa & Guru)
   * *Deskripsi*: Anggota (siswa maupun guru) mengajukan peminjaman buku daring dengan menentukan kuantitas (1 s/d 40 buku, mengakomodasi kebutuhan guru meminjam buku paket pelajaran untuk 1 kelas penuh maupun siswa), batas waktu kembali, dan mengunggah berkas kartu anggota digital.
8. **UC-08: Verifikasi Persetujuan / Penolakan Pengajuan Daring**
   * *Aktor*: Pustakawan (Admin)
   * *Deskripsi*: Pustakawan menerima notifikasi lonceng realtime, memeriksa berkas kartu digital siswa, dan menyetujui atau menolak permohonan.
9. **UC-09: Melayani Transaksi Sirkulasi Peminjaman Langsung**
   * *Aktor*: Pustakawan (Admin)
   * *Deskripsi*: Pustakawan mencatat peminjaman buku fisik langsung di meja perpustakaan dengan pemotongan stok otomatis.
10. **UC-10: Menandai Suka (*Like*) dan Simpanan Koleksi Favorit**
    * *Aktor*: Anggota (Siswa)
    * *Deskripsi*: Siswa menandai buku yang disukai dan menyimpan buku ke daftar bacaan pribadi via AJAX.
11. **UC-11: Memberikan Ulasan Skor Rating Bintang**
    * *Aktor*: Anggota (Siswa)
    * *Deskripsi*: Siswa memberikan penilaian mutu buku skala 1 sampai 5 bintang (hak akses dibatasi khusus untuk siswa).
12. **UC-12: Menyelesaikan Pengembalian Buku & Kalkulasi Denda**
    * *Aktor*: Pustakawan (Admin)
    * *Deskripsi*: Pustakawan memproses pengembalian buku fisik, mengembalikan stok buku ke rak, dan sistem menghitung denda keterlambatan secara otomatis.
13. **UC-13: Mengirim Notifikasi Tagihan Denda via WhatsApp**
    * *Aktor*: Pustakawan (Admin)
    * *Deskripsi*: Pustakawan menekan tombol pengingat untuk membuka draf pesan penagihan denda keterlambatan langsung ke WhatsApp siswa.
14. **UC-14: Menambah Data Buku Otomatis via Pemindaian ISBN**
    * *Aktor*: Pustakawan (Admin)
    * *Deskripsi*: Pustakawan memasukkan 10 atau 13 digit nomor ISBN, dan sistem memanggil Google Books API untuk mengisi otomatis data buku dan sampulnya.
15. **UC-15: Mengatur Ulang (*Reset*) Kata Sandi Anggota**
    * *Aktor*: Pustakawan (Admin)
    * *Deskripsi*: Pustakawan menetapkan kata sandi baru atau membangkitkan kombinasi sandi acak bagi siswa yang lupa kredensial.
16. **UC-16: Mengelola Status Keaktifan Akun Anggota**
    * *Aktor*: Pustakawan (Admin)
    * *Deskripsi*: Pustakawan membekukan (*freeze*) atau mengaktifkan kembali hak akses anggota siswa.
17. **UC-17: Mendaftarkan Rombongan Belajar Kelas Baru**
    * *Aktor*: Pustakawan (Admin)
    * *Deskripsi*: Menambahkan tingkatan rombel kelas baru ke dalam master data sistem perpustakaan.
18. **UC-18: Menghapus Rombongan Belajar Kelas Tidak Aktif**
    * *Aktor*: Pustakawan (Admin)
    * *Deskripsi*: Menghapus data rombel kelas yang sudah tidak digunakan di sekolah.
19. **UC-19: Memproses Pembaruan Kenaikan Kelas Massal**
    * *Aktor*: Pustakawan (Admin)
    * *Deskripsi*: Menjalankan utilitas pembaruan tingkatan kelas siswa secara serentak pada awal tahun pelajaran baru (anggota berkategori 'Guru' secara otomatis terlindungi dan dikecualikan dari proses kenaikan kelas).
20. **UC-20: Memperbarui Informasi Data Buku**
    * *Aktor*: Pustakawan (Admin)
    * *Deskripsi*: Menyunting judul, penulis, sinopsis, penomoran rak, dan kuantitas stok fisik buku.
21. **UC-21: Menghapus Data Buku dari Katalog**
    * *Aktor*: Pustakawan (Admin)
    * *Deskripsi*: Menghapus arsip data buku yang sudah rusak fisik total atau tidak lagi beredar di perpustakaan.
22. **UC-22: Mengekspor Laporan Operasional ke Format Excel**
    * *Aktor*: Pustakawan (Admin)
    * *Deskripsi*: Mengunduh laporan rekapitulasi operasional perpustakaan dalam format SpreadsheetML Excel 3-sheet.
23. **UC-23: Memperbarui Profil dan Kredensial Pustakawan**
    * *Aktor*: Pustakawan (Admin)
    * *Deskripsi*: Menyunting nama tampilan, mengunggah foto profil baru, dan mengganti kata sandi admin melalui modal profil universal.

### 6.2 Pemodelan Relasional Data: Skema Hubungan Antar Tabel
Sistem AKSA NOVA mengimplementasikan skema relasional 12 tabel dengan integritas referensial (*Foreign Key Constraints*) yang ketat:
* **Relasi `users` -> `peminjaman`**: Hubungan Satu-ke-Banyak (*One-to-Many*) berbasis kolom kunci tamu `peminjaman.user_id` mereferensikan `users.id`. Satu anggota dapat memiliki banyak riwayat transaksi sirkulasi.
* **Relasi `buku` -> `peminjaman`**: Hubungan Satu-ke-Banyak (*One-to-Many*) berbasis `peminjaman.buku_id` mereferensikan `buku.id`. Satu judul buku dapat dipinjam berulang kali dalam transaksi berbeda.
* **Relasi `users` -> `pengajuan_peminjaman`**: Hubungan Satu-ke-Banyak (*One-to-Many*) berbasis `pengajuan_peminjaman.user_id` mereferensikan `users.id`.
* **Relasi `buku` -> `pengajuan_peminjaman`**: Hubungan Satu-ke-Banyak (*One-to-Many*) berbasis `pengajuan_peminjaman.buku_id` mereferensikan `buku.id`.
* **Relasi `users` -> `buku_likes` & `buku_favorites`**: Hubungan Banyak-ke-Banyak (*Many-to-Many*) antara pengguna dan buku yang dihubungkan melalui tabel perantara `buku_likes` dan `buku_favorites`.
* **Relasi `users` -> `buku_ratings`**: Hubungan pencatatan ulasan rating dengan batasan indeks unik gabungan (*Composite Unique Key*) pada `(buku_id, user_id)` sehingga satu siswa hanya dapat memberikan satu ulasan per judul buku.
* **Relasi `users` -> `admin_profile`**: Hubungan Satu-ke-Satu (*One-to-One*) berbasis `admin_profile.user_id` mereferensikan akun admin di tabel `users`.
* **Relasi `peminjaman` -> `reminder_log`**: Hubungan Satu-ke-Banyak (*One-to-Many*) berbasis `reminder_log.peminjaman_id` mereferensikan transaksi sirkulasi untuk mencatat riwayat pengiriman pesan tagihan WhatsApp.

### 6.3 Kamus Data Sistem (Data Dictionary): Spesifikasi 12 Tabel Basis Data

1. **Tabel `users` (Menyimpan master data identitas dan kredensial seluruh pengguna)**
   * `id`: INT, Primary Key, Auto Increment. Identifikasi unik setiap pengguna.
   * `full_name`: VARCHAR(100), Not Null. Nama lengkap pengguna (siswa, guru, atau staf perpustakaan).
   * `nik`: VARCHAR(30), Nullable. Nomor identitas resmi: Nomor Induk Siswa (NIS) untuk siswa, atau NIP/NUPTK/NIK untuk guru.
   * `kelas`: VARCHAR(50), Nullable. Nama rombongan belajar kelas siswa (misal: "XII PPLG") atau 'Guru' untuk tenaga pendidik.
   * `no_hp`: VARCHAR(20), Nullable. Nomor telepon kontak WhatsApp aktif.
   * `no_anggota`: VARCHAR(30), Nullable. Kode identitas kartu anggota digital format `AN-YYYY-XXXXX`.
   * `username`: VARCHAR(50), Unique, Not Null. Nama pengguna unik untuk keperluan masuk sistem.
   * `email`: VARCHAR(100), Unique, Not Null. Alamat email terverifikasi (`@student.smkn1rongga.sch.id`, `@smkn1rongga.sch.id`, `@gmail.com`).
   * `password`: VARCHAR(255), Not Null. Nilai hash enkripsi kata sandi menggunakan algoritma Bcrypt.
   * `plain_password`: VARCHAR(255), Nullable. Penampung sementara sandi saat registrasi untuk penerbitan kartu awal.
   * `foto`: VARCHAR(255), Nullable. Lokasi path berkas foto avatar pengguna di server.
   * `role`: ENUM('admin','member'), Default 'member'. Hak akses otoritas pengguna dalam sistem.
   * `status`: ENUM('pending','approved','rejected'), Default 'approved'. Status persetujuan keaktifan akun.
   * `card_status`: ENUM('active','frozen'), Default 'active'. Status keabsahan kartu identitas digital siswa.

2. **Tabel `buku` (Master katalog buku fisik perpustakaan)**
   * `id`: INT, Primary Key, Auto Increment. Nomor unik identifikasi buku.
   * `judul`: VARCHAR(255), Not Null. Judul resmi buku.
   * `penulis`: VARCHAR(255), Not Null. Nama pengarang atau penulis buku.
   * `isbn`: VARCHAR(50), Nullable. Nomor identitas internasional buku (10 atau 13 digit).
   * `genre`: VARCHAR(100), Not Null. Klasifikasi kategori tema/materi buku.
   * `sinopsis`: TEXT, Nullable. Ringkasan isi atau garis besar cerita buku.
   * `stok`: INT, Not Null, Default 0. Kuantitas ketersediaan eksemplar fisik buku di perpustakaan.
   * `rak`: VARCHAR(100), Nullable. Kode penempatan lokasi rak fisik buku (contoh: "Rak B-1").
   * `gambar`: VARCHAR(500), Nullable. Lokasi path berkas gambar sampul buku di direktori server.

3. **Tabel `peminjaman` (Mencatat transaksi sirkulasi peminjaman aktif dan riwayat pengembalian)**
   * `id`: INT, Primary Key, Auto Increment. Nomor unik bukti transaksi sirkulasi.
   * `buku_id`: INT, Foreign Key ke `buku.id`. Judul buku yang dipinjam.
   * `user_id`: INT, Foreign Key ke `users.id`. Anggota siswa peminjam buku.
   * `nama_peminjam`: VARCHAR(150), Not Null. Nama siswa peminjam tercatat.
   * `waktu_pinjam`: DATETIME, Not Null. Waktu pencatatan transaksi peminjaman oleh server.
   * `batas_kembali`: DATETIME, Not Null. Batas waktu tanggal dan jam jatuh tempo pengembalian.
   * `waktu_kembali`: DATETIME, Nullable. Waktu fisik saat buku dikembalikan ke perpustakaan.
   * `terlambat_hari`: INT, Default 0. Durasi hari keterlambatan pengembalian buku.
   * `denda`: DECIMAL(12,0), Default 0. Total nominal denda keterlambatan dalam mata uang Rupiah.
   * `status_denda`: ENUM('tidak_ada','belum_bayar','lunas'), Default 'tidak_ada'. Status penyelesaian denda.
   * `status`: ENUM('dipinjam','dikembalikan'), Default 'dipinjam'. Status sirkulasi transaksi.

4. **Tabel `pengajuan_peminjaman` (Antrean transaksi permohonan peminjaman buku daring oleh siswa)**
   * `id`: INT, Primary Key, Auto Increment. Nomor tiket antrean pengajuan peminjaman daring.
   * `user_id`: INT, Foreign Key ke `users.id`. Siswa pemohon peminjaman.
   * `buku_id`: INT, Foreign Key ke `buku.id`. Buku yang diajukan untuk dipinjam.
   * `nama_peminjam`: VARCHAR(150), Not Null. Nama siswa pemohon.
   * `file_kartu`: VARCHAR(255), Not Null. Path berkas gambar bukti kartu anggota digital yang diunggah siswa.
   * `total_buku`: INT, Not Null, Default 1. Jumlah eksemplar yang diajukan (rentang valid: 1 s/d 40 buku).
   * `batas_kembali`: DATE, Not Null. Usulan batas tanggal pengembalian buku.
   * `waktu_pengambilan`: ENUM('sekarang','nanti'), Default 'sekarang'. Rencana waktu pengambilan buku fisik.
   * `catatan_pengambilan`: VARCHAR(255), Nullable. Keterangan tambahan jadwal pengambilan di sekolah.
   * `status`: ENUM('menunggu','disetujui','ditolak'), Default 'menunggu'. Status verifikasi pustakawan.
   * `alasan_penolakan`: TEXT, Nullable. Catatan alasan pustakawan apabila permohonan ditolak.

5. **Tabel `buku_likes` (Pencatatan interaksi suka siswa terhadap koleksi buku)**
   * `id`: INT, Primary Key, Auto Increment.
   * `buku_id`: INT, Foreign Key ke `buku.id`. Buku yang disukai.
   * `user_id`: INT, Foreign Key ke `users.id`. Siswa yang menekan tombol suka.

6. **Tabel `buku_favorites` (Pencatatan daftar simpanan bacaan favorit siswa)**
   * `id`: INT, Primary Key, Auto Increment.
   * `buku_id`: INT, Foreign Key ke `buku.id`. Buku yang disimpan.
   * `user_id`: INT, Foreign Key ke `users.id`. Siswa pemilik daftar simpanan.

7. **Tabel `buku_ratings` (Pencatatan nilai ulasan bintang dari siswa pembaca)**
   * `id`: INT, Primary Key, Auto Increment.
   * `buku_id`: INT, Foreign Key ke `buku.id`. Buku yang dinilai.
   * `user_id`: INT, Foreign Key ke `users.id`. Siswa penilai.
   * `rating`: INT, Not Null. Nilai skor evaluasi bintang (rentang valid: 1 hingga 5).

8. **Tabel `kelas` (Master data rombongan belajar kelas siswa dan kategori tenaga pendidik)**
   * `id`: INT, Primary Key, Auto Increment.
   * `nama_kelas`: VARCHAR(50), Unique, Not Null. Nama rombel siswa (misal: "X PPLG 1", "XII PPLG") serta entitas kategori khusus "Guru".

9. **Tabel `admin_profile` (Konfigurasi data profil administratif pustakawan)**
   * `id`: INT, Primary Key, Auto Increment.
   * `user_id`: INT, Unique, Foreign Key ke `users.id`. Akun admin perpustakaan.
   * `display_name`: VARCHAR(100), Not Null. Nama panggilan atau gelar pengelola perpustakaan.
   * `foto`: VARCHAR(255), Nullable. Path berkas foto profil pengelola.

10. **Tabel `banner` (Pengaturan media carousel promosi pada beranda utama)**
    * `id`: INT, Primary Key, Auto Increment.
    * `judul`: VARCHAR(150), Not Null. Judul promosi slide banner.
    * `subjudul`: VARCHAR(255), Nullable. Teks pelengkap penjelasan promo.
    * `gambar`: VARCHAR(500), Not Null. Path berkas media gambar (JPG/PNG/GIF) atau video (MP4/WEBM).
    * `link_url`: VARCHAR(255), Nullable. Tautan tujuan ketika banner diklik pengunjung.
    * `urutan`: INT, Default 0. Urutan prioritas penayangan banner.
    * `aktif`: TINYINT(1), Default 1. Status aktif (1) atau dinonaktifkan (0).

11. **Tabel `pengaturan` (Penyimpanan variabel konfigurasi sistem yang dinamis)**
    * `id`: INT, Primary Key, Auto Increment.
    * `kunci`: VARCHAR(100), Unique, Not Null. Kunci pengaturan (misal: `denda_per_hari`, `toleransi_hari`).
    * `nilai`: TEXT, Nullable. Nilai parameter konfigurasi sistem.

12. **Tabel `reminder_log` (Log riwayat pengiriman notifikasi pengingat via WhatsApp)**
    * `id`: INT, Primary Key, Auto Increment.
    * `peminjaman_id`: INT, Foreign Key ke `peminjaman.id`. Transaksi pinjaman terlambat terkait.
    * `tanggal`: DATE, Not Null. Tanggal penekanan tombol kirim tagihan.
    * `terlambat_hari`: INT, Not Null. Durasi hari keterlambatan saat pengingat dikirimkan.

### 6.4 Pemodelan Berorientasi Objek Tekstual: Spesifikasi 14 Kelas Sistem
Arsitektur logika perangkat lunak AKSA NOVA dimodelkan ke dalam 14 kelas berorientasi objek murni (*Object-Oriented Programming*) tanpa memerlukan representasi diagram visual:

1. **Kelas `Database`**
   * *Tanggung Jawab*: Mengelola koneksi basis data MySQL via driver MySQLi, mengonfigurasi set karakter UTF8MB4, dan mengatur zona waktu server ke Asia/Jakarta (+07:00).
   * *Metode Utama*: `getConnection()`, `prepareQuery($sql)`, `closeConnection()`.
2. **Kelas `User` (Kelas Induk)**
   * *Tanggung Jawab*: Menangani entitas pengguna umum, verifikasi sandi, dan manajemen sesi masuk/keluar.
   * *Metode Utama*: `login($username, $password)`, `logout()`, `hashPassword($plainText)`.
3. **Kelas `Member` (Spesialisasi Turunan dari Kelas `User`)**
   * *Tanggung Jawab*: Mengelola profil anggota (siswa maupun tenaga pendidik/guru), penerbitan data kartu digital adaptif (Kartu Pelajar / Kartu Pendidik), pemulihan akun lupa kartu, dan penelusuran riwayat sirkulasi personal hingga kuota 40 buku.
   * *Metode Utama*: `getCardData()`, `reportLostCard()`, `requestLoan($bookId, $qty, $dueDate, $cardFile)`.
4. **Kelas `Admin` (Spesialisasi Turunan dari Kelas `User`)**
   * *Tanggung Jawab*: Memodelkan otoritas pustakawan dalam mengelola inventaris, sirkulasi fisik, dan administrasi anggota.
   * *Metode Utama*: `approveLoanRequest($requestId)`, `rejectLoanRequest($requestId, $reason)`, `resetMemberPassword($userId)`.
5. **Kelas `Book`**
   * *Tanggung Jawab*: Memodelkan entitas buku fisik, manipulasi data katalog, serta penambahan dan pengurangan stok fisik secara aman.
   * *Metode Utama*: `save()`, `update()`, `delete()`, `decrementStock($qty)`, `incrementStock($qty)`.
6. **Kelas `LoanTransaction`**
   * *Tanggung Jawab*: Menangani siklus transaksi peminjaman aktif, deteksi jatuh tempo pengembalian, dan penyelesaian sirkulasi.
   * *Metode Utama*: `createLoan($userId, $bookId, $dueDate)`, `returnLoan()`, `isOverdue()`.
7. **Kelas `OnlineLoanRequest`**
   * *Tanggung Jawab*: Menampung tiket antrean peminjaman daring hingga 40 eksemplar, memvalidasi bukti kartu digital, dan memicu perubahan status permohonan.
   * *Metode Utama*: `submit()`, `verifyCardImage()`, `changeStatus($newStatus)`.
8. **Kelas `FineCalculator`**
   * *Tanggung Jawab*: Mesin kalkulasi denda yang mengeksekusi rumus keterlambatan terhadap selisih hari dan tarif denda aktif.
   * *Metode Utama*: `calculateFine($daysOverdue, $gracePeriod, $dailyRate)`, `isExempt()`.
9. **Kelas `IsbnLookupService`**
   * *Tanggung Jawab*: Klien pemanggil API eksternal (Google Books dan Open Library) untuk mengurai metadata buku berdasarkan 10/13 digit ISBN.
   * *Metode Utama*: `searchByIsbn($isbn)`, `parseMetadata($jsonResponse)`, `downloadCover($imageUrl)`.
10. **Kelas `NotificationService`**
    * *Tanggung Jawab*: Mengendalikan sinyal pemberitahuan asinkron dan pemicu Web Audio API lonceng pada layar admin saat pengajuan masuk.
    * *Metode Utama*: `checkPendingRequests()`, `triggerChimeTone()`.
11. **Kelas `WhatsAppNotifier`**
    * *Tanggung Jawab*: Pembangkit teks pesan penagihan denda ramah siswa dan pembangun URL skema tautan `wa.me`.
    * *Metode Utama*: `buildReminderMessage($studentName, $bookTitle, $days, $fineAmount)`, `getChatUrl()`.
12. **Kelas `ExcelExportService`**
    * *Tanggung Jawab*: Generator penyusun dokumen lembar kerja SpreadsheetML Excel 3-sheet untuk pelaporan eksekutif perpustakaan.
    * *Metode Utama*: `generateSummarySheet()`, `generateCirculationSheet()`, `generateCatalogSheet()`, `export()`.
13. **Kelas `BannerManager`**
    * *Tanggung Jawab*: Mengelola unggahan dan tata urutan penayangan carousel promosi beranda (gambar/GIF/video).
    * *Metode Utama*: `uploadMedia($file)`, `setOrder($bannerId, $order)`, `toggleStatus($bannerId)`.
14. **Kelas `AudioBackgroundManager`**
    * *Tanggung Jawab*: Mengelola file audio lagu pengiring suasana beranda dan menjaga persistensi status putar (*AksaAudio*).
    * *Metode Utama*: `getActiveTrack()`, `uploadAudioTrack($file)`, `toggleAudioPreference()`.

---

# 7. MATRIKS KETERTELUSURAN KEBUTUHAN (TRACEABILITY MATRIX)

### 7.1 Pemetaan Kebutuhan Fungsional (FR) terhadap File Kode PHP dan Use Case
Tabel berikut menyajikan matriks ketertelusuran keterikatan antara seluruh Kebutuhan Fungsional (*Functional Requirements*), berkas kode program implementasi dalam proyek AKSA NOVA, serta Use Case terkait:

| Kode FR | Nama Kebutuhan Fungsional | File Kode Sumber Implementasi | Use Case Terkait | Status Implementasi |
|:---:|---|---|:---:|:---:|
| **FR-01** | Registrasi Anggota Mandiri | `sign_up.php`, `db.php` | UC-01 | Terimplementasi Penuh |
| **FR-02** | Otentikasi Login Berbasis Peran | `sign_in.php`, `logout.php` | UC-02 | Terimplementasi Penuh |
| **FR-03** | Penerbitan Kartu Anggota Digital | `kartu_anggota.php` | UC-03 | Terimplementasi Penuh |
| **FR-04** | Pelaporan Mandiri Lupa Kartu | `lupa_kartu.php` | UC-06 | Terimplementasi Penuh |
| **FR-05** | Manajemen Anggota & Ubah Sandi | `daftar_anggota.php` | UC-15, UC-16 | Terimplementasi Penuh |
| **FR-06** | Pembaruan Profil Pustakawan | `modal_profil_admin.php`, `update_profil_admin.php` | UC-23 | Terimplementasi Penuh |
| **FR-07** | Manajemen Rombel Kelas & Kenaikan | `daftar_anggota.php`, `db.php` | UC-17, UC-18, UC-19 | Terimplementasi Penuh |
| **FR-08** | Master Data Buku (CRUD) | `halaman_admin.php` | UC-14, UC-20, UC-21 | Terimplementasi Penuh |
| **FR-09** | Pencarian Otomatis via ISBN API | `cari_isbn.php`, `halaman_admin.php` | UC-14 | Terimplementasi Penuh |
| **FR-10** | Penelusuran Katalog & Filter Genre | `beranda.php`, `daftar_buku.php` | UC-04 | Terimplementasi Penuh |
| **FR-11** | Informasi Lokasi Rak Fisik Buku | `buku_detail.php`, `halaman_admin.php` | UC-05 | Terimplementasi Penuh |
| **FR-12** | Manajemen Stok Fisik Atomik | `pinjam_buku.php`, `pengajuan_buku.php` | UC-09, UC-12 | Terimplementasi Penuh |
| **FR-13** | Suka (*Like*) dan Simpan Buku | `like_handler.php`, `buku_simpan.php` | UC-10 | Terimplementasi Penuh |
| **FR-14** | Pemberian Ulasan Rating Bintang | `rating_handler.php`, `buku_detail.php` | UC-11 | Terimplementasi Penuh |
| **FR-15** | Rekomendasi Buku Acak | `beranda.php` | UC-04 | Terimplementasi Penuh |
| **FR-16** | Preferensi Mode Tampilan Tema | `settings_include.php`, `pengaturan_panel.php` | UC-04 | Terimplementasi Penuh |
| **FR-17** | Pengajuan Peminjaman Daring (Maks 40 Buku) | `pengajuan_peminjaman.php` | UC-07 | Terimplementasi Penuh |
| **FR-18** | Notifikasi Pengajuan Realtime Audio | `api_cek_pengajuan_admin.php`, `notifikasi_pengajuan_admin.php` | UC-08 | Terimplementasi Penuh |
| **FR-19** | Persetujuan / Penolakan Pengajuan | `pengajuan_buku.php` | UC-08 | Terimplementasi Penuh |
| **FR-20** | Sirkulasi Peminjaman Langsung | `pinjam_buku.php` | UC-09 | Terimplementasi Penuh |
| **FR-21** | Pengembalian Buku & Hitung Denda | `telah_dipinjam.php`, `cron_cek_terlambat.php` | UC-12 | Terimplementasi Penuh |
| **FR-22** | Penagihan Denda via WhatsApp | `telah_dipinjam.php` | UC-13 | Terimplementasi Penuh |
| **FR-23** | Dasbor Analitik & Ekspor Excel | `dashboard.php`, `export_excel_dashboard.php` | UC-22 | Terimplementasi Penuh |
| **FR-24** | Manajemen Banner & Audio Latar | `kelola_banner.php`, `pengaturan_musik.php` | UC-24 | Terimplementasi Penuh |

---
*Dokumen Spesifikasi Kebutuhan Perangkat Lunak (SRS) AKSA NOVA v1.3 ini merupakan dokumentasi spesifikasi teknis resmi pengembangan sistem perpustakaan digital SMK Negeri 1 Rongga, Kabupaten Bandung Barat.*
"""

def clean_latex(text):
    text = re.sub(r'\\\(', '', text)
    text = re.sub(r'\\\)', '', text)
    text = re.sub(r'\\\[', '', text)
    text = re.sub(r'\\\]', '', text)
    text = re.sub(r'\$\$', '', text)
    text = re.sub(r'\$', '', text)
    return text

def set_cell_background(cell, fill_hex):
    tcPr = cell._tc.get_or_add_tcPr()
    shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{fill_hex}"/>')
    tcPr.append(shd)

def set_cell_margins(cell, top=60, bottom=60, left=100, right=100):
    tcPr = cell._tc.get_or_add_tcPr()
    tcMar = parse_xml(f'<w:tcMar {nsdecls("w")}><w:top w:w="{top}" w:type="dxa"/><w:bottom w:w="{bottom}" w:type="dxa"/><w:left w:w="{left}" w:type="dxa"/><w:right w:w="{right}" w:type="dxa"/></w:tcMar>')
    tcPr.append(tcMar)

def add_footer_page_number(doc):
    for sec in doc.sections:
        sec.different_first_page_header_footer = True
        footer = sec.footer
        p = footer.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.RIGHT
        p.text = ""
        r1 = p.add_run("Halaman ")
        r1.font.name = "Calibri"
        r1.font.size = Pt(8.5)
        r1.font.color.rgb = RGBColor(120, 120, 120)

        fldSimple = OxmlElement('w:fldSimple')
        fldSimple.set(qn('w:instr'), 'PAGE')
        p._element.append(fldSimple)

        r2 = p.add_run(" | SRS AKSA NOVA v1.2 - SMK Negeri 1 Rongga")
        r2.font.name = "Calibri"
        r2.font.size = Pt(8.5)
        r2.font.color.rgb = RGBColor(140, 140, 140)

def add_formatted_runs(p, text, default_font='Calibri', default_size=10.5, default_color=RGBColor(35, 35, 35)):
    tokens = re.split(r'(\*\*.*?\*\*|\*.*?\*|`.*?`)', text)
    for tok in tokens:
        if not tok:
            continue
        if tok.startswith('**') and tok.endswith('**'):
            run = p.add_run(tok[2:-2])
            run.bold = True
            run.font.name = default_font
            run.font.size = Pt(default_size)
            run.font.color.rgb = default_color
        elif tok.startswith('*') and tok.endswith('*'):
            run = p.add_run(tok[1:-1])
            run.italic = True
            run.font.name = default_font
            run.font.size = Pt(default_size)
            run.font.color.rgb = default_color
        elif tok.startswith('`') and tok.endswith('`'):
            run = p.add_run(tok[1:-1])
            run.font.name = 'Consolas'
            run.font.size = Pt(default_size * 0.9)
            run.font.color.rgb = RGBColor(160, 100, 20)
        else:
            run = p.add_run(tok)
            run.font.name = default_font
            run.font.size = Pt(default_size)
            run.font.color.rgb = default_color

def build_cover_page(doc, logo_path):
    p_top = doc.add_paragraph()
    p_top.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_top.paragraph_format.space_before = Pt(0)
    p_top.paragraph_format.space_after = Pt(2)
    p_top.paragraph_format.line_spacing = 1.1
    
    r = p_top.add_run("PEMERINTAH DAERAH PROVINSI JAWA BARAT\nDINAS PENDIDIKAN\nCABANG DINAS PENDIDIKAN WILAYAH VII\n")
    r.font.name = "Calibri"
    r.font.size = Pt(10.5)
    r.bold = True
    r.font.color.rgb = RGBColor(70, 75, 80)
    
    r_smk = p_top.add_run("SMK NEGERI 1 RONGGA\n")
    r_smk.font.name = "Calibri"
    r_smk.font.size = Pt(13.5)
    r_smk.bold = True
    r_smk.font.color.rgb = RGBColor(16, 42, 77)

    r_jurusan = p_top.add_run("PROGRAM KEAHLIAN PENGEMBANGAN PERANGKAT LUNAK DAN GIM (PPLG)\nKONSENTRASI KEAHLIAN REKAYASA PERANGKAT LUNAK (RPL)")
    r_jurusan.font.name = "Calibri"
    r_jurusan.font.size = Pt(9.5)
    r_jurusan.font.color.rgb = RGBColor(100, 105, 115)

    p_line = doc.add_paragraph()
    p_line.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_line.paragraph_format.space_before = Pt(3)
    p_line.paragraph_format.space_after = Pt(14)
    r_line = p_line.add_run("_______________________________________________________________________________")
    r_line.font.color.rgb = RGBColor(20, 45, 85)
    r_line.font.size = Pt(8)

    # Logo
    if os.path.exists(logo_path):
        p_logo = doc.add_paragraph()
        p_logo.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_logo.paragraph_format.space_before = Pt(4)
        p_logo.paragraph_format.space_after = Pt(14)
        run_logo = p_logo.add_run()
        run_logo.add_picture(logo_path, width=Inches(2.1))

    # Title Block
    p_title = doc.add_paragraph()
    p_title.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_title.paragraph_format.space_before = Pt(8)
    p_title.paragraph_format.space_after = Pt(4)
    p_title.paragraph_format.line_spacing = 1.15

    r_srs = p_title.add_run("DOKUMEN SPESIFIKASI KEBUTUHAN PERANGKAT LUNAK\n(SOFTWARE REQUIREMENTS SPECIFICATION / SRS)\n")
    r_srs.font.name = "Calibri"
    r_srs.font.size = Pt(14)
    r_srs.bold = True
    r_srs.font.color.rgb = RGBColor(16, 42, 77)

    p_proj = doc.add_paragraph()
    p_proj.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_proj.paragraph_format.space_before = Pt(4)
    p_proj.paragraph_format.space_after = Pt(6)
    r_proj = p_proj.add_run("SISTEM INFORMASI PERPUSTAKAAN DIGITAL BERBASIS WEB\n\"AKSA NOVA\"")
    r_proj.font.name = "Calibri"
    r_proj.font.size = Pt(16)
    r_proj.bold = True
    r_proj.font.color.rgb = RGBColor(180, 115, 30)

    p_std = doc.add_paragraph()
    p_std.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_std.paragraph_format.space_before = Pt(2)
    p_std.paragraph_format.space_after = Pt(20)
    r_std = p_std.add_run("Berdasarkan Standar IEEE Std 830-1998 / ISO/IEC/IEEE 29148\nDokumentasi Perancangan Perangkat Lunak & Portofolio Siswa")
    r_std.font.name = "Calibri"
    r_std.font.size = Pt(9.5)
    r_std.italic = True
    r_std.font.color.rgb = RGBColor(110, 115, 125)

    # Author metadata
    p_meta = doc.add_paragraph()
    p_meta.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_meta.paragraph_format.space_before = Pt(12)
    p_meta.paragraph_format.space_after = Pt(3)
    p_meta.paragraph_format.line_spacing = 1.15

    r_oleh = p_meta.add_run("Disusun Oleh:\n")
    r_oleh.font.name = "Calibri"
    r_oleh.font.size = Pt(10.5)
    r_oleh.font.color.rgb = RGBColor(90, 95, 100)

    r_nama = p_meta.add_run("SYAHRUL BIMANSYAH\n")
    r_nama.font.name = "Calibri"
    r_nama.font.size = Pt(13)
    r_nama.bold = True
    r_nama.font.color.rgb = RGBColor(16, 42, 77)

    r_prog = p_meta.add_run("Kompetensi Keahlian: Rekayasa Perangkat Lunak (RPL) / PPLG\n")
    r_prog.font.name = "Calibri"
    r_prog.font.size = Pt(10.5)
    r_prog.font.color.rgb = RGBColor(60, 65, 75)

    p_foot = doc.add_paragraph()
    p_foot.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_foot.paragraph_format.space_before = Pt(10)
    p_foot.paragraph_format.space_after = Pt(0)
    p_foot.paragraph_format.line_spacing = 1.1

    r_sch = p_foot.add_run("SMK NEGERI 1 RONGGA\nKABUPATEN BANDUNG BARAT - PROVINSI JAWA BARAT\nTAHUN 2026")
    r_sch.font.name = "Calibri"
    r_sch.font.size = Pt(10.5)
    r_sch.bold = True
    r_sch.font.color.rgb = RGBColor(40, 50, 65)

    # Hard break exactly once after cover page
    doc.add_page_break()

def build_srs_documents():
    md_path = 'docs/SRS_AKSA_NOVA.md'
    docx_path1 = 'docs/SRS_AKSA_NOVA.docx'
    docx_path2 = 'SRS/SRS_AKSA_NOVA.docx'
    logo_path = 'docs/logo_smkn1rongga.png' if os.path.exists('docs/logo_smkn1rongga.png') else 'docs/logo_smkn1rongga.jpg'

    print("Writing markdown to " + md_path + "...")
    with open(md_path, 'w', encoding='utf-8') as f:
        f.write(srs_markdown.strip() + '\n')
    print("Markdown saved successfully!")

    print("Building docx document...")
    lines = [clean_latex(l.rstrip('\r\n')) for l in srs_markdown.strip().split('\n')]

    doc = Document()

    # Standard clean margins: 0.8 inch top/bottom/sides
    for sec in doc.sections:
        sec.top_margin = Inches(0.8)
        sec.bottom_margin = Inches(0.8)
        sec.left_margin = Inches(0.85)
        sec.right_margin = Inches(0.8)

    # Base font
    normal_style = doc.styles['Normal']
    normal_style.font.name = 'Calibri'
    normal_style.font.size = Pt(10)
    normal_style.font.color.rgb = RGBColor(35, 35, 35)

    add_footer_page_number(doc)

    # 1. Build Cover Page
    build_cover_page(doc, logo_path)

    # 2. Parse body text
    i = 0
    table_lines = []
    skip_header_block = True
    current_major_section = ""

    while i < len(lines):
        line = lines[i].strip()

        # Skip raw markdown title block on page 1 since cover page already handled it
        if skip_header_block:
            if line == '# KATA PENGANTAR':
                skip_header_block = False
            else:
                i += 1
                continue

        # Skip image markdown line in body
        if line.startswith('![Logo SMK'):
            i += 1
            continue

        # Ignore standalone horizontal rules if the next non-empty line is a heading
        if line == '---':
            # Check if next item is a major heading
            next_idx = i + 1
            while next_idx < len(lines) and not lines[next_idx].strip():
                next_idx += 1
            if next_idx < len(lines) and lines[next_idx].strip().startswith('#'):
                # Suppress horizontal rule to prevent orphan underline on blank page
                i += 1
                continue
            else:
                # Add a subtle separator paragraph
                p = doc.add_paragraph()
                p.paragraph_format.space_before = Pt(3)
                p.paragraph_format.space_after = Pt(4)
                r = p.add_run('_______________________________________________________________________________')
                r.font.color.rgb = RGBColor(220, 225, 230)
                r.font.size = Pt(7.5)
                i += 1
                continue

        # Table handling
        if line.startswith('|') and line.endswith('|'):
            table_lines.append(line)
            i += 1
            if i < len(lines) and lines[i].strip().startswith('|') and lines[i].strip().endswith('|'):
                continue
            else:
                if len(table_lines) >= 2:
                    rows_data = []
                    for tline in table_lines:
                        if re.match(r'^\|[\s\-:|]+\|$', tline):
                            continue
                        cells = [c.strip() for c in tline.strip('|').split('|')]
                        rows_data.append(cells)

                    if rows_data:
                        num_rows = len(rows_data)
                        num_cols = max(len(r) for r in rows_data)
                        tbl = doc.add_table(rows=num_rows, cols=num_cols)
                        tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
                        tbl.autofit = True

                        for r_idx, row_cells in enumerate(rows_data):
                            row = tbl.rows[r_idx]
                            is_header = (r_idx == 0)
                            
                            # Prevent row splitting across pages
                            trPr = row._tr.get_or_add_trPr()
                            trPr.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))

                            # Repeat header row if table spans pages
                            if is_header:
                                trPr.append(parse_xml(f'<w:tblHeader {nsdecls("w")}/>'))

                            for c_idx in range(num_cols):
                                cell = row.cells[c_idx]
                                cell_text = row_cells[c_idx] if c_idx < len(row_cells) else ''
                                cell.text = ''
                                p = cell.paragraphs[0]
                                p.paragraph_format.space_before = Pt(1)
                                p.paragraph_format.space_after = Pt(1)
                                p.paragraph_format.line_spacing = 1.02

                                set_cell_margins(cell, top=35, bottom=35, left=60, right=60)

                                if is_header:
                                    set_cell_background(cell, "162A4D")
                                    tokens = re.split(r'(\*\*.*?\*\*|\*.*?\*|`.*?`)', cell_text)
                                    for tok in tokens:
                                        if not tok: continue
                                        if tok.startswith('**') and tok.endswith('**'):
                                            r = p.add_run(tok[2:-2])
                                        else:
                                            r = p.add_run(tok)
                                        r.bold = True
                                        r.font.name = 'Calibri'
                                        r.font.size = Pt(9.0)
                                        r.font.color.rgb = RGBColor(255, 255, 255)
                                else:
                                    bg = "F4F7FA" if r_idx % 2 == 1 else "FFFFFF"
                                    set_cell_background(cell, bg)
                                    # handle line breaks inside cell (<br>)
                                    sub_parts = cell_text.split('<br>')
                                    for p_idx, sub in enumerate(sub_parts):
                                        if p_idx > 0:
                                            p = cell.add_paragraph()
                                            p.paragraph_format.space_before = Pt(0.5)
                                            p.paragraph_format.space_after = Pt(0.5)
                                            p.paragraph_format.line_spacing = 1.02
                                        tokens = re.split(r'(\*\*.*?\*\*|\*.*?\*|`.*?`)', sub.strip())
                                        for tok in tokens:
                                            if not tok: continue
                                            if tok.startswith('**') and tok.endswith('**'):
                                                r = p.add_run(tok[2:-2])
                                                r.bold = True
                                            elif tok.startswith('*') and tok.endswith('*'):
                                                r = p.add_run(tok[1:-1])
                                                r.italic = True
                                            elif tok.startswith('`') and tok.endswith('`'):
                                                r = p.add_run(tok[1:-1])
                                                r.font.name = 'Consolas'
                                                r.font.size = Pt(8.0)
                                                r.font.color.rgb = RGBColor(160, 100, 20)
                                            else:
                                                r = p.add_run(tok)
                                            r.font.name = 'Calibri'
                                            r.font.size = Pt(8.5)

                        # NO extra empty paragraph after table!

                table_lines = []
                continue

        # Header 1 (# ...)
        if line.startswith('# '):
            text = line[2:].strip()
            
            # Specific page breaks ONLY before preliminary divisions
            # Notice: Do NOT break before KATA PENGANTAR because cover page already broke!
            if text in ['DAFTAR ISI', '1. PENDAHULUAN']:
                doc.add_page_break()

            current_major_section = text
            h = doc.add_heading(level=1)
            h.paragraph_format.space_before = Pt(11)
            h.paragraph_format.space_after = Pt(4)
            h.paragraph_format.keep_with_next = True
            hrun = h.add_run(text)
            hrun.font.name = 'Calibri'
            hrun.font.size = Pt(14)
            hrun.bold = True
            hrun.font.color.rgb = RGBColor(16, 42, 77)
            i += 1
            continue

        # Header 2 (## ...)
        if line.startswith('## '):
            text = line[3:].strip()
            h = doc.add_heading(level=2)
            h.paragraph_format.space_before = Pt(8)
            h.paragraph_format.space_after = Pt(2.5)
            h.paragraph_format.keep_with_next = True
            hrun = h.add_run(text)
            hrun.font.name = 'Calibri'
            hrun.font.size = Pt(12)
            hrun.bold = True
            hrun.font.color.rgb = RGBColor(30, 60, 100)
            i += 1
            continue

        # Header 3 (### ...) mapped to Heading 2
        if line.startswith('### '):
            text = line[4:].strip()
            h = doc.add_heading(level=2)
            h.paragraph_format.space_before = Pt(7)
            h.paragraph_format.space_after = Pt(2)
            h.paragraph_format.keep_with_next = True
            hrun = h.add_run(text)
            hrun.font.name = 'Calibri'
            hrun.font.size = Pt(11)
            hrun.bold = True
            hrun.font.color.rgb = RGBColor(40, 75, 120)
            i += 1
            continue

        # Code block representation
        if line.startswith('```'):
            code_lines = []
            i += 1
            while i < len(lines) and not lines[i].strip().startswith('```'):
                code_lines.append(lines[i])
                i += 1
            i += 1 # skip closing ```
            
            p_code = doc.add_paragraph()
            p_code.paragraph_format.space_before = Pt(4)
            p_code.paragraph_format.space_after = Pt(4)
            p_code.paragraph_format.line_spacing = 1.0
            p_code.paragraph_format.left_indent = Inches(0.15)
            r_code = p_code.add_run('\n'.join(code_lines))
            r_code.font.name = 'Consolas'
            r_code.font.size = Pt(8.0)
            r_code.font.color.rgb = RGBColor(40, 50, 65)
            continue

        # Empty line
        if not line:
            i += 1
            continue

        # Check if inside DAFTAR ISI to make it super compact
        is_in_toc = (current_major_section == 'DAFTAR ISI')

        # Bullet list
        bullet_match = re.match(r'^(\s*)[-*]\s+(.*)$', lines[i])
        if bullet_match:
            indent_level = len(bullet_match.group(1)) // 2
            item_text = bullet_match.group(2).strip()
            
            p = doc.add_paragraph(style='List Bullet')
            if is_in_toc:
                p.paragraph_format.space_before = Pt(0)
                p.paragraph_format.space_after = Pt(1.5)
                p.paragraph_format.line_spacing = 1.05
                p.paragraph_format.left_indent = Inches(0.18 * (indent_level + 1))
                add_formatted_runs(p, item_text, default_size=9.5)
            else:
                p.paragraph_format.space_before = Pt(0.5)
                p.paragraph_format.space_after = Pt(1.5)
                p.paragraph_format.line_spacing = 1.08
                p.paragraph_format.left_indent = Inches(0.20 * (indent_level + 1))
                add_formatted_runs(p, item_text, default_size=10.0)
            i += 1
            continue

        # Numbered list
        num_match = re.match(r'^(\s*)(\d+)\.\s+(.*)$', lines[i])
        if num_match:
            indent_level = len(num_match.group(1)) // 2
            num = num_match.group(2)
            item_text = f"{num}. {num_match.group(3).strip()}"
            p = doc.add_paragraph(style='Normal')
            p.paragraph_format.space_before = Pt(0.5)
            p.paragraph_format.space_after = Pt(1.5)
            p.paragraph_format.line_spacing = 1.08
            p.paragraph_format.left_indent = Inches(0.20 * (indent_level + 1))
            add_formatted_runs(p, item_text, default_size=10.0)
            i += 1
            continue

        # Normal text paragraph
        p = doc.add_paragraph(style='Normal')
        p.paragraph_format.space_before = Pt(0)
        p.paragraph_format.space_after = Pt(3.0)
        p.paragraph_format.line_spacing = 1.10
        add_formatted_runs(p, line, default_size=10.0)
        i += 1

    print("Saving Word document to " + docx_path1 + "...")
    doc.save(docx_path1)
    
    os.makedirs('SRS', exist_ok=True)
    print("Saving Word document to " + docx_path2 + "...")
    doc.save(docx_path2)
    
    print("Done! SRS Document successfully updated in both docs/ and SRS/ folders!")

if __name__ == '__main__':
    build_srs_documents()
