# DOKUMENTASI SKRIP AUDIT v2 (REPRODUCIBILITY GUIDE)
**Proyek:** AKSA NOVA — Sistem Informasi Perpustakaan Digital SMK Negeri 1 Rongga  
**Folder Skrip:** `docs/audit_v2/scripts/`  
**Prasyarat Lingkungan:** PHP 8.x (CLI) dan Python 3.10+ di Windows / PowerShell / Bash  

Seluruh skrip audit dirancang mandiri (*self-contained*), deterministik, dan dapat dijalankan ulang kapan saja untuk mereproduksi seluruh data temuan audit tanpa mengubah file proyek (*READ-ONLY*).

---

## 1. Daftar Skrip Audit & Hasil Keluaran

| No | File Skrip | Bahasa | Deskripsi Fungsi | File Keluaran | Perintah Eksekusi |
|---:|---|:---:|---|---|---|
| 1 | `01_php_fact_extractor.php` | PHP | Memindai 44 file PHP aplikasi menggunakan PHP tokenizer (`token_get_all`) untuk mengekstrak definisi sesi, kueri SQL, prepared statements, verifikasi CSRF, penanganan error, hash sandi, dan sanitasi input. | `docs/audit_v2/02_fakta_kode.json` | `php docs/audit_v2/scripts/01_php_fact_extractor.php` |
| 2 | `01b_render_fakta_kode_md.py` | Python | Merender laporan Markdown komprehensif dari hasil ekstraksi JSON AST PHP. | `docs/audit_v2/02_fakta_kode.md` | `python docs/audit_v2/scripts/01b_render_fakta_kode_md.py` |
| 3 | `02_schema_and_drift.py` | Python | Mem-parsing DDL SQL (`aksa_nova.sql` & `2026_otp_reset.sql`), mengekstrak 12 tabel InnoDB, 100 kolom, dan memvalidasi drift dua arah antara PHP dan skema SQL. | `docs/audit_v2/schema_model.json` | `python docs/audit_v2/scripts/02_schema_and_drift.py` |
| 4 | `03_inventory_docs.py` | Python | Menginventarisasi seluruh 138 berkas dokumentasi (`ERD/`, `SRS/`, `UML/`), mendeteksi pasangan `.png` <-> `.puml`, memeriksa media tertanam DOCX, dan mencatat status kesesuaian. | `docs/audit_v2/03_inventaris_dokumen.csv` | `python docs/audit_v2/scripts/03_inventory_docs.py` |
| 5 | `04_compare_erd.py` | Python | Membandingkan secara mekanis skema DDL fisik dengan artefak ERD (`erd_diagram.png`, `erd_interactive.html`, `erd.puml`, `erd.mmd`). | `docs/audit_v2/erd_diff_results.json` | `python docs/audit_v2/scripts/04_compare_erd.py` |
| 6 | `05_extract_docx_and_pdf.py` | Python | Mengekstrak teks paragraf, tabel, dan gambar tertanam dari file Word (`.docx`) dan PDF (`.pdf`) tanpa bergantung pada aplikasi Microsoft Office. | `docs/audit_v2/extracted_docs.json` | `python docs/audit_v2/scripts/05_extract_docx_and_pdf.py` |
| 7 | `06_compare_class.py` | Python | Membandingkan kelas, atribut, metode, dan relasi Class Diagram terhadap skema basis data dan kode OOP/prosedural PHP. | `docs/audit_v2/class_diff_results.json` | `python docs/audit_v2/scripts/06_compare_class.py` |
| 8 | `07_scan_nonexistent_refs.py` | Python | Memindai seluruh rujukan file `.php`/`.js`, kunci konfigurasi `pengaturan`, dan kolom database yang dikutip di dokumen tetapi tidak ada di kode fisik. | `docs/audit_v2/nonexistent_refs.json` | `python docs/audit_v2/scripts/07_scan_nonexistent_refs.py` |
| 9 | `08_sample_recheck.py` | Python | Melakukan verifikasi mandiri terhadap 20 sampel acak (10 klaim BENAR, 10 temuan SALAH) secara deterministik untuk mengukur tingkat kesalahan auditor. | `docs/audit_v2/14_cek_ulang_sampel.md` | `python docs/audit_v2/scripts/08_sample_recheck.py` |

---

## 2. Cara Menjalankan Seluruh Pipeline Audit Ulang

Jalankan rangkaian perintah berikut dari direktori root proyek (`c:\laragon\www\aksanova`):

```bash
# 1. Ekstraksi fakta kode PHP & skema basis data
php docs/audit_v2/scripts/01_php_fact_extractor.php
python docs/audit_v2/scripts/01b_render_fakta_kode_md.py
python docs/audit_v2/scripts/02_schema_and_drift.py

# 2. Inventarisasi dan pemindaian artefak dokumen
python docs/audit_v2/scripts/03_inventory_docs.py
python docs/audit_v2/scripts/04_compare_erd.py
python docs/audit_v2/scripts/05_extract_docx_and_pdf.py
python docs/audit_v2/scripts/06_compare_class.py
python docs/audit_v2/scripts/07_scan_nonexistent_refs.py

# 3. Pengujian sampel acak verifikasi diri
python docs/audit_v2/scripts/08_sample_recheck.py
```
Semua file hasil pemindaian di folder `docs/audit_v2/` akan diperbarui secara otomatis.
