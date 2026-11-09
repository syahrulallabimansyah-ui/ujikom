# LAPORAN AUDIT v2: ENTITY RELATIONSHIP DIAGRAM (ERD)
**Proyek:** AKSA NOVA — Sistem Informasi Perpustakaan Digital SMK Negeri 1 Rongga  
**Tanggal Audit:** 2026-11-08  
**Auditor:** Software Auditor & System Analyst (Audit v2 — Clean Room & Read-Only)  
**Status Audit:** SELESAI (Audit Read-Only)  

---

## 1. Ringkasan Eksekutif ERD

Audit terhadap seluruh artefak Entity Relationship Diagram (ERD) AKSA NOVA mencakup:
1. Gambar diagram: [`ERD/erd_diagram.png`](file:///c:/laragon/www/aksanova/ERD/erd_diagram.png) (265.309 byte, 2026-11-03 08:28:02).
2. Dokumen interaktif: [`ERD/erd_interactive.html`](file:///c:/laragon/www/aksanova/ERD/erd_interactive.html) (32.940 byte).
3. Sumber teks PlantUML: [`ERD/erd.puml`](file:///c:/laragon/www/aksanova/ERD/erd.puml) (4.657 byte).
4. Sumber teks Mermaid: [`ERD/erd.mmd`](file:///c:/laragon/www/aksanova/ERD/erd.mmd) (5.016 byte).
5. Dokumen narasi pendamping: [`ERD/Penjelasan ERD - AKSA NOVA.docx`](file:///c:/laragon/www/aksanova/ERD/Penjelasan%20ERD%20-%20AKSA%20NOVA.docx) (303.807 byte).

**Sumber Kebenaran (Ground Truth):**
- Basis data DDL: [`database/aksa_nova.sql`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql) (baris 1-285) & migrasi [`database/migrations/2026_otp_reset.sql`](file:///c:/laragon/www/aksanova/database/migrations/2026_otp_reset.sql).
- Ekstraksi skema mekanis: [`docs/audit_v2/schema_model.json`](file:///c:/laragon/www/aksanova/docs/audit_v2/schema_model.json) & [`docs/audit_v2/erd_diff_results.json`](file:///c:/laragon/www/aksanova/docs/audit_v2/erd_diff_results.json).

**Hasil Keseluruhan:**
ERD AKSA NOVA secara substantif memiliki **akurasi struktur tabel dan kolom yang sangat tinggi (12/12 tabel cocok, 100/100 kolom cocok, 13/13 PK cocok)**. Tabel migrasi terbaru `password_otp` sudah tercantum di diagram. Namun, ditemukan **1 kesalahan Kritis** (klaim keliru bahwa relasi `pengajuan_peminjaman` adalah logical tanpa FK fisik, padahal di DDL terdapat physical FK ON DELETE CASCADE), **1 masalah visual Tinggi** (pemotongan badge kunci akibat CSS overflow di PNG), serta beberapa inkonsistensi kardinalitas dan sintaks PlantUML.

---

## 2. Cakupan Terukur (Measurable Coverage)

| Kategori Elemen | Jumlah Diperiksa | BENAR | BENAR DENGAN CATATAN | SALAH | TIDAK TERVERIFIKASI | Keterangan Singkat |
|---|:---:|:---:|:---:|:---:|:---:|---|
| **Tabel Basis Data** | 12 | 12 | 0 | 0 | 0 | 12 tabel InnoDB cocok 100% |
| **Kolom / Atribut** | 100 | 100 | 0 | 0 | 0 | 100 kolom cocok 100% tanpa kolom fiktif |
| **Tipe Data Kolom** | 100 | 95 | 5 | 0 | 0 | 5 kolom enum/varchar disederhanakan |
| **Nullability Kolom** | 100 | 59 | 5 | 36 | 0 | erd.puml kurang 36 tanda `*` NOT NULL; PNG kurang 5 label nullable |
| **Primary Key (PK)** | 12 | 12 | 0 | 0 | 0 | Seluruh 12 PK teridentifikasi |
| **Foreign Key (FK Fisik)** | 13 | 11 | 0 | 2 | 0 | FK buku_id & user_id pengajuan diklaim `NO FK` |
| **Relasi Diagram (Crow's Foot)** | 14 | 9 | 2 | 3 | 0 | 2 relasi salah garis putus-putus, 1 salah kardinalitas, 2 salah arah prong |
| **Render Visual (PNG)** | 1 | 0 | 0 | 1 | 0 | Overflow CSS memotong badge users & pengajuan |
| **Dokumen Narasi DOCX** | 1 | 0 | 1 | 0 | 0 | 12 tabel tercantum di bab 4, tapi bab 5.11 melewatkan password_otp |
| **TOTAL AKUMULATIF** | **353** | **298 (84,4%)** | **13 (3,7%)** | **42 (11,9%)** | **0 (0,0%)** | Akurasi struktural tinggi, cacat relasi & rendering |

---

## 3. Elemen yang Terverifikasi BENAR

Berdasarkan perbandingan langsung antara artefak ERD dengan [`database/aksa_nova.sql`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql):

1. **Kelengkapan 12 Tabel (100% Cocok):**
   - Seluruh 12 tabel fisik hadir dalam diagram: `users`, `buku`, `kategori`, `peminjaman`, `pengajuan_peminjaman`, `buku_ratings`, `buku_likes`, `buku_favorites`, `password_otp`, `banner`, `pengaturan`, `admin_profile`.
   - Bukti DDL: `aksa_nova.sql:24` (`users`), `53` (`kategori`), `67` (`buku`), `109` (`banner`), `127` (`pengaturan`), `144` (`peminjaman`), `177` (`pengajuan_peminjaman`), `199` (`buku_favorites`), `209` (`buku_likes`), `219` (`buku_ratings`), `228` (`password_otp`), `244` (`admin_profile`).

2. **Kelengkapan 100 Kolom Fisik (100% Cocok):**
   - Tidak ada kolom fisik di database yang hilang dari ERD.
   - Tidak ada kolom halusinasi/fiktif yang ditambahkan ke ERD (seperti `penerbit`, `tahun`, `sampul` yang sempat muncul di dokumen lain).
   - Kolom `plain_password` pada `users` (`aksa_nova.sql:33`) secara jujur dan akurat ditandai sebagai `[LEGACY]` di `erd_interactive.html` dan `erd_diagram.png`.
   - Kolom `nik` pada `users` (`aksa_nova.sql:27`) tidak dilabeli Unique Key (UK), sesuai fakta skema di mana `nik` tidak memiliki indeks UNIQUE (hanya `username`, `email`, `no_anggota`).

3. **Inklusi Tabel Migrasi `password_otp`:**
   - Tabel `password_otp` yang dibuat melalui migrasi [`database/migrations/2026_otp_reset.sql`](file:///c:/laragon/www/aksanova/database/migrations/2026_otp_reset.sql) dan disatukan di `aksa_nova.sql:228-241` tercantum lengkap dengan 8 kolom: `id`, `user_id`, `otp_hash`, `expires_at`, `attempts`, `used_at`, `ip_address`, `created_at`.
   - Relasi FK fisik `password_otp.user_id -> users.id` (ON DELETE CASCADE) tercantum dengan benar (`aksa_nova.sql:267`).

4. **Kunci Primer (PK) dan Relasi Mayoritas:**
   - 11 relasi FK fisik lainnya digambarkan dengan benar menghubungkan kolom asal ke target referensi.

---

## 4. Tabel Temuan Masalah & Ketidaksesuaian ERD

| ID | Dokumen + Lokasi | Isi Dokumen | Fakta Kode | Bukti Path:Baris | Keparahan | Perbaikan yang Disarankan |
|---|---|---|---|---|:---:|---|
| **[ERD-01]** | `ERD/erd_diagram.png`, `ERD/erd_interactive.html`, `ERD/erd.puml`, `ERD/erd.mmd` | Kolom `pengajuan_peminjaman.user_id` dan `buku_id` dilabeli badge `[NO FK]` dan legenda menyebutnya "Relasi Logis (Tanpa FK DB)". | Tabel `pengajuan_peminjaman` memiliki Foreign Key fisik resmi di DDL dengan constraint `fk_pengajuan_buku` (`buku_id -> buku.id ON DELETE CASCADE ON UPDATE CASCADE`) dan `fk_pengajuan_user` (`user_id -> users.id ON DELETE CASCADE ON UPDATE CASCADE`). | [`database/aksa_nova.sql:265-266`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql#L265-L266) | **Kritis** | Ubah badge `[NO FK]` menjadi `[FK]` pada kedua kolom di diagram HTML, PUML, MMD, dan PNG. Ubah jenis garis relasi menjadi garis solid (Physical FK). |
| **[ERD-02]** | `ERD/erd_diagram.png` (Visual Rendering) | Sisi kanan kartu entitas `users` dan `pengajuan_peminjaman` terpotong secara visual. Badge `PK`, `UK`, `LEGACY` pada `users` dan badge `PK`, `NO FK` pada `pengajuan_peminjaman` terpotong/hilang dari pandangan mata. | File CSS pada template HTML menggunakan `.entity-card { overflow: hidden; width: ... }` dengan padding ketat, sehingga saat diekspor/dirender ke format PNG, kolom badge kunci terpotong oleh clipping boundary. | [`ERD/erd_interactive.html:48-55`](file:///c:/laragon/www/aksanova/ERD/erd_interactive.html#L48-L55) | **Tinggi** | Tambahkan margin/padding kanan pada `.entity-card` atau hapus `overflow: hidden`, perlebar lebar kartu tabel pengguna dari 300px menjadi 340px sebelum diekspor ulang ke PNG. |
| **[ERD-03]** | `ERD/erd_diagram.png`, `ERD/erd_interactive.html` (SVG Path) | Garis relasi dari `users` menuju `buku_favorites`, `buku_likes`, dan `buku_ratings` digambar menggunakan garis putus-putus (`stroke-dasharray="6,4"`). Legenda mendefinisikan garis putus-putus sebagai "Relasi Logis Tanpa FK". | Ketiga tabel tersebut memiliki Foreign Key fisik formal di database: `fk_fav_user`, `fk_like_user`, `fk_rate_user` dengan referensi ke `users(id) ON DELETE CASCADE`. | [`database/aksa_nova.sql:247, 253, 259`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql#L247) | **Sedang** | Ganti kelas SVG dari `rel-logical` (putus-putus) menjadi `rel-physical` (garis solid) untuk ketiga relasi interaksi sosial tersebut. |
| **[ERD-04]** | `ERD/erd_diagram.png`, `ERD/erd_interactive.html`, `ERD/erd.mmd` | Kardinalitas pada ujung entitas `users` untuk relasi `users ||--o{ peminjaman` digambar dengan simbol `||` (mandatori: satu dan hanya satu pengguna). | Kolom `peminjaman.user_id` bersifat NULLABLE (`INT DEFAULT NULL`). DDL mendefinisikan `ON DELETE SET NULL`. Bila akun anggota dihapus, data peminjaman tetap tersimpan dengan `user_id = NULL` sebagai arsip historis. Kardinalitas sisi parent adalah opsional (`zero or one` / `o|`). | [`database/aksa_nova.sql:147, 194`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql#L147) | **Sedang** | Ubah kardinalitas dari `||` (mandatory one) menjadi `o|` (optional one) pada relasi `users` ke `peminjaman`. |
| **[ERD-05]** | `ERD/erd_diagram.png`, `ERD/erd_interactive.html` (SVG Path Prongs) | Garis relasi yang mengalir dari kanan ke kiri (misalnya dari `buku` ke `pengajuan_peminjaman` dan `peminjaman`) memiliki simbol cabang Crow's Foot (`>|` atau `}|`) yang menghadap ke arah berlawanan (menjauh dari entitas anak). | Simbol Crow's foot (kaki gagak) harus menancap dan membuka ke arah entitas anak (child entity/many-side). | [`ERD/erd_interactive.html:410-440`](file:///c:/laragon/www/aksanova/ERD/erd_interactive.html#L410-L440) | **Sedang** | Perbaiki perhitungan orientasi path marker SVG di `erd_interactive.html` agar cabang kaki gagak selalu membuka ke arah entitas anak terlepas dari arah koordinat X (kiri/kanan). |
| **[ERD-06]** | `ERD/erd.puml` (Atribut Not-Null) | Terdapat 36 kolom yang tidak memiliki penanda bintang (`*`) NOT NULL di dalam sintaks PlantUML `ERD/erd.puml` (mis. `buku.judul`, `buku.penulis`, `users.nama`, `peminjaman.tgl_pinjam`). | Di dalam skema SQL, ke-36 kolom tersebut didefinisikan secara tegas sebagai `NOT NULL`. Ketiadaan tanda `*` di PlantUML menyebabkan visualisasi atribut dianggap opsional/nullable. | [`docs/audit_v2/erd_diff_results.json`](file:///c:/laragon/www/aksanova/docs/audit_v2/erd_diff_results.json) (kategori `puml_missing_not_null`) | **Sedang** | Tambahkan karakter `*` di depan nama ke-36 kolom di dalam `ERD/erd.puml` agar sinkron dengan DDL. |
| **[ERD-07]** | `ERD/erd_diagram.png`, `ERD/erd_interactive.html` | 5 kolom nullable (`users.no_hp`, `users.alamat`, `users.plain_password`, `peminjaman.catatan`, `buku_ratings.review`) tidak diberi label `[Nullable]`, sehingga secara visual tidak dapat dibedakan dari kolom wajib di HTML. | Di basis data SQL, ke-5 kolom tersebut tidak memiliki klausa `NOT NULL` (secara default `NULL`). | [`database/aksa_nova.sql:30-33, 155, 224`](file:///c:/laragon/www/aksanova/database/aksa_nova.sql#L30-L33) | **Rendah** | Tambahkan badge abu-abu `[Nullable]` atau beri warna pembeda pada 5 kolom tersebut di tampilan HTML dan PNG. |
| **[ERD-08]** | `ERD/Penjelasan ERD - AKSA NOVA.docx` | Judul bab 5 tertulis *"5. Relasi Antar-Entitas Fisik Fisik"* (repetisi kata "Fisik"). Pada subbab relasi, tabel `password_otp` dilewatkan dari matriks relasi bab 5 (melompat dari 5.10 ke bab 6). | Merupakan kesalahan ketik tipografi dan kelalaian updating dokumen narasi saat penambahan tabel `password_otp`. | [`ERD/Penjelasan ERD - AKSA NOVA.docx`](file:///c:/laragon/www/aksanova/ERD/Penjelasan%20ERD%20-%20AKSA%20NOVA.docx) (Teks Bab 5) | **Rendah** | Perbaiki judul bab menjadi *"5. Relasi Antar-Entitas Fisik"* dan tambahkan subbab *5.11 Relasi Tabel password_otp dengan users* ke dalam dokumen Word. |

---

## 5. Analisis Sumber Diagram ERD

1. **`ERD/erd_interactive.html` vs `ERD/erd_diagram.png`:**
   - Keduanya berbasis desain SVG yang sama persis. File PNG merupakan hasil rasterisasi/screenshot dari file HTML interaktif.
   - Karena masalah layout CSS di HTML, rendering PNG mewarisi pemotongan visual kolom kanan (clipping issue [ERD-02]).
2. **`ERD/erd.puml` (PlantUML):**
   - Struktur entitas lengkap 12 tabel.
   - Mengandung kesalahan relasi `pengajuan_peminjaman` (`}o--||` tanpa foreign key fisik).
   - Kehilangan 36 penanda `*` NOT NULL, sehingga bila dirender via jar PlantUML standar, atribut mandatory tampak sebagai opsional.
3. **`ERD/erd.mmd` (Mermaid):**
   - Mendefinisikan seluruh 12 tabel dengan sintaks `erDiagram`.
   - Mengulang klaim keliru `users ||--o{ pengajuan_peminjaman : "mengajukan (logis)"` dan `buku ||--o{ pengajuan_peminjaman : "diajukan (logis)"`.

---

## 6. Rekomendasi Perbaikan ERD (Hanya Ditulis, Tidak Dikerjakan)

1. **Koreksi Relasi DDL:**
   Perbarui seluruh sumber ERD (`.html`, `.puml`, `.mmd`, `.docx`) untuk mengakui bahwa `pengajuan_peminjaman` memiliki Foreign Key fisik formal (`fk_pengajuan_buku` dan `fk_pengajuan_user`) dengan sifat `ON DELETE CASCADE`. Ganti badge `[NO FK]` menjadi `[FK]`.
2. **Perbaikan Layout & CSS untuk Render PNG:**
   Pada `ERD/erd_interactive.html`, ubah styling kartu tabel:
   ```css
   .entity-card {
     /* Ubah width agar muat seluruh badge */
     min-width: 320px;
     overflow: visible; /* Mencegah clipping badge kanan */
   }
   ```
3. **Koreksi Arah Cabang Crow's Foot SVG:**
   Perbaiki algoritma penggambaran marker SVG pada relasi koordinat negatif/arah kiri agar cabang kaki gagak konsisten menunjuk ke arah tabel anak.
4. **Sinkronisasi PUML:**
   Tambahkan tanda `*` di depan setiap kolom NOT NULL di `ERD/erd.puml` sesuai daftar pada `docs/audit_v2/erd_diff_results.json`.
