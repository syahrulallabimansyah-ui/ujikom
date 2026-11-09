#!/usr/bin/env python3
"""
03_inventory_docs.py
Menginventarisasi seluruh berkas dokumen dan diagram pada proyek AKSA NOVA
(ERD/, SRS/, UML/ termasuk subfolder Lengkap/) dan menghasilkan:
  - docs/audit_v2/03_inventaris_dokumen.csv

Cara menjalankan:
  python docs/audit_v2/scripts/03_inventory_docs.py
"""
import csv
import datetime
import os
import re
import sys
import zipfile

sys.stdout.reconfigure(encoding="utf-8")

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", ".."))
OUT_CSV = os.path.join(ROOT, "docs", "audit_v2", "03_inventaris_dokumen.csv")
DOC_DIRS = ["ERD", "SRS", "UML"]


def check_docx_embedded_media(docx_path: str) -> list:
    media = []
    try:
        with zipfile.ZipFile(docx_path, "r") as zf:
            for name in zf.namelist():
                if name.startswith("word/media/"):
                    media.append(f"{name} ({zf.getinfo(name).file_size} B)")
    except Exception:
        pass
    return media


def infer_feature_and_php(rel_path: str) -> tuple:
    base = os.path.basename(rel_path)
    stem, ext = os.path.splitext(base)
    low = rel_path.lower()

    if low.startswith("erd/"):
        return ("Skema Database Keseluruhan (12 Tabel)", "database/aksa_nova.sql, database/migrations/2026_otp_reset.sql")
    if low.startswith("srs/"):
        return ("Spesifikasi Kebutuhan Perangkat Lunak (SRS) Sistem Penuh", "Seluruh 44 file PHP aplikasi")
    if "usecase" in low:
        return ("Diagram / Penjelasan Use Case Sistem Penuh (3 Aktor)", "Seluruh halaman & handler aplikasi")
    if "uml/class" in low:
        return ("Diagram / Penjelasan Class Sistem Penuh", "database/aksa_nova.sql + seluruh modul PHP")

    # Map numbered Activity / Sequence files
    mapping = {
        "01": ("Registrasi Anggota", "sign_up.php, kartu_anggota.php, kartu_masuk.php"),
        "02": ("Login & Pengalihan Peran", "sign_in.php"),
        "03": ("Pencarian & Detail Katalog Buku", "beranda.php, daftar_buku.php, buku_detail.php"),
        "04": ("Pengajuan Peminjaman Buku Online", "pengajuan_peminjaman.php"),
        "05": ("Verifikasi Pengajuan Peminjaman oleh Admin", "pengajuan_buku.php"),
        "06": ("Pengembalian Buku & Perhitungan Denda", "telah_dipinjam.php, pengaturan_denda.php"),
        "07": ("Kelola Buku & Pencarian ISBN", "halaman_admin.php, cari_isbn.php"),
        "a08": ("Pemulihan Akun / Lupa Kartu via OTP Email", "lupa_kartu.php, includes/otp.php, includes/mailer.php, kartu_anggota.php"),
        "s08": ("Pemulihan Akun / Lupa Kartu via OTP Email", "lupa_kartu.php, includes/otp.php, includes/mailer.php, kartu_anggota.php"),
        "08": ("Pemulihan Akun / Lupa Kartu", "lupa_kartu.php, includes/otp.php, includes/mailer.php"),
        "09": ("Lihat & Unduh Kartu Anggota Digital", "kartu_anggota.php, barcode128.php, kartu_masuk.php, kartu_clear.php"),
        "10": ("Registrasi / Edit Kartu (cek berdasarkan folder)", "sign_up.php / edit_kartu.php"),
        "11": ("Login / Suka & Simpan Buku (cek berdasarkan folder)", "sign_in.php / like_handler.php, buku_simpan.php"),
        "12": ("Katalog / Rating Buku (cek berdasarkan folder)", "daftar_buku.php / rating_handler.php"),
        "13": ("Pengajuan Online / Dashboard Anggota (cek berdasarkan folder)", "pengajuan_peminjaman.php / dashboard_user.php"),
        "14": ("Verifikasi Pengajuan / Pinjam Langsung (cek berdasarkan folder)", "pengajuan_buku.php / pinjam_buku.php"),
        "15": ("Pengembalian & Denda / Pengingat WA & Cron (cek berdasarkan folder)", "telah_dipinjam.php / cron_cek_terlambat.php"),
        "16": ("Kelola Buku & ISBN / Kelola Anggota (cek berdasarkan folder)", "halaman_admin.php, cari_isbn.php / daftar_anggota.php"),
    }

    # Better: derive from exact filename stem!
    clean_title = re.sub(r"^[AS]?\d+[_-]*", "", stem).replace("_", " ").strip()
    if clean_title:
        return (clean_title, "")
    return (stem, "")


def map_php_by_stem(rel_path: str) -> str:
    s = os.path.basename(rel_path).lower()
    if "registrasi" in s:
        return "sign_up.php, kartu_anggota.php, kartu_masuk.php"
    if "login" in s:
        return "sign_in.php"
    if "logout" in s:
        return "logout.php, settings_include.php"
    if "lupa_kartu" in s or "otp" in s:
        return "lupa_kartu.php, includes/otp.php, includes/mailer.php, kartu_anggota.php"
    if "kartu_anggota" in s:
        return "kartu_anggota.php, barcode128.php, kartu_masuk.php, kartu_clear.php"
    if "edit_data" in s or "edit_kartu" in s or "profil_anggota" in s:
        return "edit_kartu.php, kartu_anggota.php"
    if "katalog" in s or "pencarian" in s or "cari_buku" in s:
        return "index.php, beranda.php, daftar_buku.php, buku_detail.php"
    if "suka" in s or "favorit" in s or "simpan" in s:
        return "like_handler.php, buku_simpan.php"
    if "rating" in s:
        return "rating_handler.php, buku_detail.php"
    if "dashboard_anggota" in s or "peminjaman_saya" in s or "riwayat_anggota" in s:
        return "dashboard_user.php"
    if "notifikasi" in s:
        return "notifikasi_pengajuan_admin.php, api_cek_pengajuan_admin.php"
    if "verifikasi_pengajuan" in s or "persetujuan_pengajuan" in s or "kelola_pengajuan" in s:
        return "pengajuan_buku.php"
    if "pengajuan" in s:
        return "pengajuan_peminjaman.php"
    if "pinjam_langsung" in s or "peminjaman_langsung" in s or "input_peminjaman" in s:
        return "pinjam_buku.php"
    if "pengembalian" in s or "pelunasan" in s:
        return "telah_dipinjam.php, pengaturan_denda.php"
    if "wa" in s or "whatsapp" in s or "pengingat" in s or "cron" in s or "terlambat" in s:
        return "telah_dipinjam.php, cron_cek_terlambat.php"
    if "isbn" in s or "kelola_buku" in s or "tambah_buku" in s or "edit_buku" in s or "hapus_buku" in s:
        return "halaman_admin.php, cari_isbn.php"
    if "anggota" in s or "reset_sandi" in s or "cairkan" in s:
        return "daftar_anggota.php"
    if "banner" in s:
        return "kelola_banner.php, index.php"
    if "musik" in s:
        return "pengaturan_musik.php, settings_include.php"
    if "lokasi" in s or "jam" in s:
        return "pengaturan_lokasi.php, index.php"
    if "denda" in s:
        return "pengaturan_denda.php, telah_dipinjam.php"
    if "profil_admin" in s or "sandi_admin" in s:
        return "modal_profil_admin.php, update_profil_admin.php, ganti_sandi_admin.php"
    if "ekspor" in s or "export" in s or "excel" in s:
        return "export_excel_dashboard.php, dashboard.php"
    if "dashboard" in s:
        return "dashboard.php"
    if "tema" in s or "tampilan" in s:
        return "pengaturan_panel.php, settings_include.php"
    if "berita" in s or "rss" in s:
        return "beranda.php"
    return ""


def main():
    all_files = []
    for d in DOC_DIRS:
        abs_d = os.path.join(ROOT, d)
        if not os.path.isdir(abs_d):
            continue
        for r, _, fs in os.walk(abs_d):
            for f in sorted(fs):
                full = os.path.join(r, f)
                rel = os.path.relpath(full, ROOT).replace("\\", "/")
                all_files.append((rel, full))

    rel_set = {r for r, _ in all_files}
    rows = []

    for rel, full in all_files:
        size_b = os.path.getsize(full)
        mtime = datetime.datetime.fromtimestamp(os.path.getmtime(full)).strftime("%Y-%m-%d %H:%M:%S")
        folder = os.path.dirname(rel)
        base = os.path.basename(rel)
        stem, ext = os.path.splitext(base)
        ext_low = ext.lower()

        # Jenis berkas
        jenis_map = {
            ".png": "Gambar Diagram (PNG)",
            ".svg": "Gambar Diagram (SVG)",
            ".html": "Dokumen / Diagram HTML Interaktif",
            ".puml": "Sumber PlantUML (.puml)",
            ".mmd": "Sumber Mermaid (.mmd)",
            ".drawio": "Sumber Draw.io (.drawio)",
            ".docx": "Dokumen Word (.docx)",
            ".pdf": "Dokumen PDF (.pdf)",
            ".md": "Dokumen Markdown (.md)",
        }
        jenis = jenis_map.get(ext_low, f"Berkas ({ext_low})")

        fitur, php_default = infer_feature_and_php(rel)
        php_mapped = php_default or map_php_by_stem(rel)

        # Pair detection
        pasangan = "-"
        status_pasangan = "-"
        dipakai_rujukan = "Ya (Utama)"

        if ext_low == ".png":
            cand_puml = f"{folder}/{stem}.puml"
            cand_mmd = f"{folder}/{stem}.mmd"
            cand_html = f"{folder}/{stem}.html"
            # Special ERD case
            if rel == "ERD/erd_diagram.png":
                sources = [x for x in ["ERD/erd.puml", "ERD/erd.mmd", "ERD/erd_interactive.html"] if x in rel_set]
                pasangan = ", ".join(sources) if sources else "TANPA SUMBER"
                status_pasangan = "Ada 3 sumber (erd.puml ada selisih 36 NOT NULL; pengajuan_peminjaman FK terpotong di PNG)"
            elif rel == "UML/Usecase/UseCase_Diagram_AKSA_NOVA.png":
                pasangan = "TANPA SUMBER LANGSUNG (tidak ada .puml/.drawio untuk versi 24 UC ini)"
                status_pasangan = "GAMBAR BARU TANPA SUMBER (.puml/.drawio tidak ada)"
            elif rel == "UML/Usecase/usecase_diagram.png":
                pasangan = "UML/Usecase/usecase_diagram.puml, UML/Usecase/usecase_diagram.html"
                status_pasangan = "Versi lama 34 UC (cocok .html, garis Tamu memotong judul)"
                dipakai_rujukan = "Sekunder / Versi Lama (periksa keduanya)"
            elif rel == "UML/Class/Class_Diagram_AKSA_NOVA.png":
                pasangan = "TANPA SUMBER LANGSUNG (.puml/.drawio tidak ada)"
                status_pasangan = "GAMBAR TANPA SUMBER (.puml/.drawio tidak ada; beda dari embedded docx)"
            elif rel in ("UML/Squence/Lengkap/S08_Lupa_Kartu_OTP.png", "UML/Activty/Lengkap/A08_Lupa_Kartu_OTP.png"):
                pasangan = "TANPA SUMBER .puml"
                status_pasangan = "GAMBAR TANPA SUMBER (.puml tidak ada)"
            elif cand_puml in rel_set:
                pasangan = cand_puml
                if "Lengkap" in folder:
                    status_pasangan = "TIDAK COCOK: PUML adalah stub templat generik 37/41 baris"
                else:
                    status_pasangan = "TIDAK COCOK: PUML menyimpang dari PNG (fakta alur/objek beda)"
            elif cand_mmd in rel_set:
                pasangan = cand_mmd
                status_pasangan = "Berpasangan (.png <-> .mmd)"
            else:
                pasangan = "TANPA SUMBER"
                status_pasangan = "GAMBAR TANPA SUMBER"
        elif ext_low in (".puml", ".mmd"):
            cand_png = f"{folder}/{stem}.png"
            if rel in ("ERD/erd.puml", "ERD/erd.mmd"):
                pasangan = "ERD/erd_diagram.png"
                status_pasangan = "Sumber teks (erd.puml kurang 36 NOT NULL *; FK pengajuan_peminjaman salah)"
                dipakai_rujukan = "Sumber Diagram"
            elif cand_png in rel_set:
                pasangan = cand_png
                if "Lengkap" in folder:
                    status_pasangan = "TIDAK COCOK DGN PNG (stub templat generik, file non-existent)"
                else:
                    status_pasangan = "TIDAK COCOK DGN PNG (isi PUML menyimpang dari PNG)"
                dipakai_rujukan = "Sumber Teks (Menyimpang dari PNG)"
            else:
                pasangan = "TANPA GAMBAR"
                status_pasangan = "SUMBER TANPA GAMBAR (.png tidak ada)"
                dipakai_rujukan = "Sumber Yatim (Tanpa PNG)"
        elif ext_low == ".html":
            dipakai_rujukan = "Pratinjau / Sumber HTML"
            if rel == "ERD/erd_interactive.html":
                pasangan = "ERD/erd_diagram.png"
                status_pasangan = "HTML interaktif pendamping ERD (cek kesesuaian)"
            elif f"{folder}/{stem}.png" in rel_set:
                pasangan = f"{folder}/{stem}.png"
                status_pasangan = "HTML pendamping PNG (cek kesesuaian)"
            else:
                pasangan = "TANPA GAMBAR"
                status_pasangan = "HTML mandiri"
        elif ext_low == ".docx":
            media = check_docx_embedded_media(full)
            pasangan = f"Embedded media: {len(media)} file" if media else "Teks saja (0 gambar tertanam)"
            if rel == "SRS/SRS_AKSA_NOVA.docx":
                pasangan += " | Pendamping: SRS/SRS_AKSA_NOVA.pdf"
                status_pasangan = "Dokumen utama SRS (.docx lebih baru dari .pdf)"
            else:
                status_pasangan = "Dokumen penjelasan narasi"
        elif ext_low == ".pdf":
            if rel == "SRS/SRS_AKSA_NOVA.pdf":
                pasangan = "SRS/SRS_AKSA_NOVA.docx"
                status_pasangan = "Ekspor PDF lama (cek selisih tanggal & isi terhadap .docx)"
                dipakai_rujukan = "Ya (Salinan PDF — periksa sinkronisasi dgn .docx)"

        rows.append({
            "path": rel,
            "jenis": jenis,
            "ukuran_byte": size_b,
            "tanggal_ubah": mtime,
            "fitur_yang_digambarkan": fitur,
            "file_php_terkait": php_mapped,
            "pasangan_gambar_atau_sumber": pasangan,
            "apakah_dipakai_rujukan": dipakai_rujukan,
            "status_pasangan_dan_kecocokan": status_pasangan,
        })

    with open(OUT_CSV, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=list(rows[0].keys()))
        writer.writeheader()
        writer.writerows(rows)

    print(f"Total dokumen & diagram ditemukan: {len(rows)} berkas -> {OUT_CSV}")
    for r in rows:
        print(f"  {r['path']} | {r['ukuran_byte']} B | {r['tanggal_ubah']} | {r['status_pasangan_dan_kecocokan']}")


if __name__ == "__main__":
    main()
