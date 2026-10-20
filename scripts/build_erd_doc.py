# -*- coding: utf-8 -*-
"""
Builder for ERD Documentation & Word (.docx) file
Project: AKSA NOVA - Sistem Informasi Perpustakaan Digital
Author: Syahrul Bimansyah (@arul)
Institution: SMK Negeri 1 Rongga
Outputs:
  - ERD/Penjelasan ERD - AKSA NOVA.docx
  - docs/Penjelasan_ERD_AKSA_NOVA.docx
  - docs/ERD_Aksa_Nova.md
"""

import os
import shutil
import re
import docx
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.section import WD_ORIENTATION, WD_SECTION
from docx.oxml import parse_xml, OxmlElement
from docx.oxml.ns import nsdecls, qn
from PIL import Image

def set_cell_background(cell, fill_hex):
    tcPr = cell._element.get_or_add_tcPr()
    shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{fill_hex}"/>')
    tcPr.append(shd)

def set_cell_margins(cell, top=70, bottom=70, left=90, right=90):
    tcPr = cell._element.get_or_add_tcPr()
    tcMar = parse_xml(f'<w:tcMar {nsdecls("w")}><w:top w:w="{top}" w:type="dxa"/><w:bottom w:w="{bottom}" w:type="dxa"/><w:left w:w="{left}" w:type="dxa"/><w:right w:w="{right}" w:type="dxa"/></w:tcMar>')
    tcPr.append(tcMar)

def add_footer(sec, doc_title="Penjelasan ERD - AKSA NOVA"):
    footer = sec.footer
    p = footer.paragraphs[0]
    p.alignment = WD_ALIGN_PARAGRAPH.RIGHT

    r1 = p.add_run("Halaman ")
    r1.font.name = "Calibri"
    r1.font.size = Pt(9)
    r1.font.color.rgb = RGBColor(120, 120, 120)

    fldSimple = OxmlElement('w:fldSimple')
    fldSimple.set(qn('w:instr'), 'PAGE')
    p._element.append(fldSimple)

    r2 = p.add_run(f" | {doc_title} (SMK Negeri 1 Rongga)")
    r2.font.name = "Calibri"
    r2.font.size = Pt(9)
    r2.font.color.rgb = RGBColor(140, 140, 140)

def add_runs(p, text, default_size=11, bold=False, italic=False, color=RGBColor(35, 35, 35)):
    tokens = re.split(r'(\*\*.*?\*\*|\*.*?\*|`.*?`)', text)
    for tok in tokens:
        if not tok: continue
        if tok.startswith('**') and tok.endswith('**'):
            r = p.add_run(tok[2:-2])
            r.bold = True
            r.font.name = 'Calibri'
            r.font.size = Pt(default_size)
            r.font.color.rgb = color
        elif tok.startswith('*') and tok.endswith('*'):
            r = p.add_run(tok[1:-1])
            r.italic = True
            r.font.name = 'Calibri'
            r.font.size = Pt(default_size)
            r.font.color.rgb = color
        elif tok.startswith('`') and tok.endswith('`'):
            r = p.add_run(tok[1:-1])
            r.font.name = 'Consolas'
            r.font.size = Pt(default_size * 0.9)
            r.font.color.rgb = RGBColor(180, 100, 20)
        else:
            r = p.add_run(tok)
            r.bold = bold
            r.italic = italic
            r.font.name = 'Calibri'
            r.font.size = Pt(default_size)
            r.font.color.rgb = color

def create_erd_doc():
    os.makedirs('ERD', exist_ok=True)
    os.makedirs('docs', exist_ok=True)

    src_img_v1 = 'ERD/erd_diagram.png'
    if not os.path.exists(src_img_v1):
        src_img_v1 = 'docs/erd_diagram.png'

    src_img_v2 = 'ERD/erd_diagram_v2.png'
    if not os.path.exists(src_img_v2):
        src_img_v2 = 'docs/erd_diagram_v2.png'

    doc = Document()

    # Section 1: Portrait for Title, Overview, Notation
    sec1 = doc.sections[0]
    sec1.top_margin = Inches(1.0)
    sec1.bottom_margin = Inches(1.0)
    sec1.left_margin = Inches(1.0)
    sec1.right_margin = Inches(1.0)
    add_footer(sec1)

    # Document Header Title
    h1 = doc.add_heading(level=1)
    h1.paragraph_format.space_before = Pt(0)
    h1.paragraph_format.space_after = Pt(4)
    r_h1 = h1.add_run("Spesifikasi & Penjelasan Entity Relationship Diagram (ERD)")
    r_h1.font.name = "Calibri"
    r_h1.font.size = Pt(18)
    r_h1.bold = True
    r_h1.font.color.rgb = RGBColor(16, 42, 77)

    # Subtitle
    p_sub = doc.add_paragraph()
    p_sub.paragraph_format.space_before = Pt(0)
    p_sub.paragraph_format.space_after = Pt(16)
    r_sub = p_sub.add_run("AKSA NOVA — Sistem Informasi Perpustakaan Digital SMK Negeri 1 Rongga\nPenyusun: Syahrul Bimansyah (@arul) • Database Engine: MySQL 8.0 (InnoDB, utf8mb4)")
    r_sub.font.name = "Calibri"
    r_sub.font.size = Pt(10)
    r_sub.font.italic = True
    r_sub.font.color.rgb = RGBColor(100, 116, 139)

    # ==========================================
    # 1. Gambaran Umum Arsitektur Basis Data
    # ==========================================
    h2_1 = doc.add_heading(level=2)
    h2_1.paragraph_format.space_before = Pt(14)
    h2_1.paragraph_format.space_after = Pt(6)
    r_h2_1 = h2_1.add_run("1. Gambaran Umum Arsitektur Basis Data")
    r_h2_1.font.name = "Calibri"
    r_h2_1.font.size = Pt(13.5)
    r_h2_1.bold = True
    r_h2_1.font.color.rgb = RGBColor(30, 58, 138)

    p1 = doc.add_paragraph()
    p1.paragraph_format.line_spacing = 1.15
    p1.paragraph_format.space_after = Pt(6)
    add_runs(p1, "Entity Relationship Diagram (ERD) sistem **AKSA NOVA** merupakan rancangan pemodelan data fisik (*Physical Data Model*) yang memetakan struktur tabel, tipe data kolom, kunci relasi (*Primary Key, Foreign Key, Unique Key*), serta kardinalitas relasi antar entitas pada sistem perpustakaan digital SMK Negeri 1 Rongga.")

    p2 = doc.add_paragraph()
    p2.paragraph_format.line_spacing = 1.15
    p2.paragraph_format.space_after = Pt(6)
    add_runs(p2, "Basis data dibangun dengan standar industri menggunakan engine **MySQL 8.0 (InnoDB)** dengan character set **`utf8mb4`** dan collation **`utf8mb4_0900_ai_ci`** serta **`utf8mb4_unicode_ci`**. Hal ini menjamin konsistensi ACID (*Atomicity, Consistency, Isolation, Durability*), integritas referensial penuh melalui kunci tamu (*Foreign Key*), serta dukungan penyimpanan karakter multibyte (abjad internasional, huruf kanji Jepang pada katalog manga, dan emoji pada ulasan siswa).")

    p3 = doc.add_paragraph()
    p3.paragraph_format.line_spacing = 1.15
    p3.paragraph_format.space_after = Pt(8)
    add_runs(p3, "Struktur basis data mencakup **12 tabel entitas relasional** yang terbagi ke dalam 5 modul fungsional utama:")

    modules = [
        "**Modul Pengguna & Autentikasi (`users`, `admin_profile`, `kelas`)**: Mengelola akun pengguna (siswa, guru/pendidik, dan admin), kontrol hak akses (RBAC), data rombongan belajar kelas serta penugasan kelas 'Guru', pembekuan kartu identitas, dan profil pustakawan.",
        "**Modul Katalog & Inventaris Buku (`buku`)**: Menyimpan master data koleksi pustaka, kode ISBN unik, stok fisik atomik, dan pelabelan lokasi rak buku perpustakaan.",
        "**Modul Sirkulasi & Antrean Pinjam (`pengajuan_peminjaman`, `peminjaman`)**: Menangani proses bisnis sirkulasi lengkap mulai dari tiket pengajuan peminjaman daring berkuota hingga **maksimal 40 buku** (mengakomodasi kebutuhan guru meminjam paket buku materi untuk 1 kelas maupun siswa), transaksi fisik aktif di perpustakaan, batas waktu pengembalian, hingga penghitungan denda keterlambatan.",
        "**Modul Interaksi Sosial Katalog (`buku_favorites`, `buku_likes`, `buku_ratings`)**: Menampung keterlibatan pembaca melalui fitur bookmark buku favorit, apresiasi suka (*like*), dan rating ulasan bintang berskala 1–5.",
        "**Modul Pengaturan & Log Sistem (`reminder_log`, `banner`, `pengaturan`)**: Mendukung operasional sistem melalui log pengingat denda WhatsApp terjadwal, slider promosi media beranda, dan konfigurasi dinamis tarif perpus."
    ]

    for m in modules:
        bp = doc.add_paragraph(style='List Bullet')
        bp.paragraph_format.space_before = Pt(1)
        bp.paragraph_format.space_after = Pt(3)
        bp.paragraph_format.line_spacing = 1.15
        add_runs(bp, m)

    # ==========================================
    # 2. Notasi Crow's Foot & Konvensi ERD
    # ==========================================
    h2_2 = doc.add_heading(level=2)
    h2_2.paragraph_format.space_before = Pt(14)
    h2_2.paragraph_format.space_after = Pt(6)
    r_h2_2 = h2_2.add_run("2. Notasi Crow's Foot & Konvensi Pemodelan ERD")
    r_h2_2.font.name = "Calibri"
    r_h2_2.font.size = Pt(13.5)
    r_h2_2.bold = True
    r_h2_2.font.color.rgb = RGBColor(30, 58, 138)

    p_notasi = doc.add_paragraph()
    p_notasi.paragraph_format.line_spacing = 1.15
    p_notasi.paragraph_format.space_after = Pt(6)
    add_runs(p_notasi, "Diagram ERD AKSA NOVA disajikan menggunakan standar **Crow's Foot Notation** yang merupakan konvensi baku rekayasa perangkat lunak internasional. Setiap entitas direpresentasikan sebagai kotak tiga kompartemen:")

    notasi_list = [
        "**Header Entitas**: Menampilkan nama tabel basis data dan kategori fungsi entitas.",
        "**Kolom Atribut & Tipe Data**: Menyajikan nama field fisik, tipe data MySQL (`INT`, `VARCHAR`, `DATETIME`, `DECIMAL`, `ENUM`), serta ukuran panjangnya.",
        "**Label Kunci / Constraint**: Menandai penanda kunci yaitu **`PK`** (*Primary Key* sebagai identitas baris unik), **`FK`** (*Foreign Key* sebagai kunci relasi ke tabel induk), dan **`UK`** (*Unique Key* untuk mencegah duplikasi data bisnis).",
        "**Simbol Relasi Crow's Foot**:",
        "  • **`—||` (Mandatory One)**: Tepat satu data wajib ada pada entitas induk (*Primary Key side*).",
        "  • **`—o|` (Optional One)**: Nol atau paling banyak satu data (*Zero or One*).",
        "  • **`—o<` (Optional Many)**: Nol, satu, atau banyak data anak yang berelasi (*Zero or More* pada *Foreign Key side*)."
    ]

    for nl in notasi_list:
        bp = doc.add_paragraph(style='List Bullet')
        bp.paragraph_format.space_before = Pt(1)
        bp.paragraph_format.space_after = Pt(2)
        bp.paragraph_format.line_spacing = 1.15
        add_runs(bp, nl)

    # ==========================================
    # SECTION 2: LANDSCAPE FOR ERD DIAGRAM
    # ==========================================
    sec2 = doc.add_section(WD_SECTION.NEW_PAGE)
    sec2.orientation = WD_ORIENTATION.LANDSCAPE
    sec2.page_width = Inches(11.69)   # A4 Landscape width
    sec2.page_height = Inches(8.27)   # A4 Landscape height
    sec2.top_margin = Inches(0.5)
    sec2.bottom_margin = Inches(0.5)
    sec2.left_margin = Inches(0.6)
    sec2.right_margin = Inches(0.6)
    add_footer(sec2, "Diagram Visual ERD AKSA NOVA")

    # 3.1 Diagram V1 (Baseline Standar)
    h2_3 = doc.add_heading(level=2)
    h2_3.paragraph_format.space_before = Pt(0)
    h2_3.paragraph_format.space_after = Pt(4)
    r_h2_3 = h2_3.add_run("3. Visualisasi Diagram Hubungan Entitas (Physical Crow's Foot ERD)")
    r_h2_3.font.name = "Calibri"
    r_h2_3.font.size = Pt(13)
    r_h2_3.bold = True
    r_h2_3.font.color.rgb = RGBColor(30, 58, 138)

    p_diagram_note = doc.add_paragraph()
    p_diagram_note.paragraph_format.space_before = Pt(0)
    p_diagram_note.paragraph_format.space_after = Pt(4)
    r_dn = p_diagram_note.add_run("Diagram di bawah ini menampilkan arsitektur 12 tabel fisik, kolom atribut, tipe data presisi, kunci (PK/FK/UK), dan garis relasi Crow's Foot yang selaras dengan skema MySQL aksa_nova.sql:")
    r_dn.font.name = "Calibri"
    r_dn.font.size = Pt(9.5)
    r_dn.font.italic = True
    r_dn.font.color.rgb = RGBColor(71, 85, 105)

    if os.path.exists(src_img_v1):
        img_p = doc.add_paragraph()
        img_p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        img_p.paragraph_format.space_before = Pt(2)
        img_p.paragraph_format.space_after = Pt(4)
        img_p.add_run().add_picture(src_img_v1, width=Inches(10.2))

        cap_p = doc.add_paragraph()
        cap_p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        cap_p.paragraph_format.space_after = Pt(0)
        rcap = cap_p.add_run("Gambar 3.1: Physical Entity Relationship Diagram (Baseline Standar) AKSA NOVA — MySQL 8.0 / InnoDB")
        rcap.font.name = 'Calibri'
        rcap.font.size = Pt(9)
        rcap.font.italic = True
        rcap.font.color.rgb = RGBColor(100, 116, 139)

    # 3.2 Diagram V2 (Versi Perbaikan / Penyelarasan Atribut plain_password)
    if os.path.exists(src_img_v2):
        doc.add_page_break()
        h2_3b = doc.add_heading(level=2)
        h2_3b.paragraph_format.space_before = Pt(0)
        h2_3b.paragraph_format.space_after = Pt(4)
        r_h2_3b = h2_3b.add_run("3.2 Visualisasi Diagram ERD Versi Perbaikan (V2 — Sinkronisasi Atribut plain_password)")
        r_h2_3b.font.name = "Calibri"
        r_h2_3b.font.size = Pt(13)
        r_h2_3b.bold = True
        r_h2_3b.font.color.rgb = RGBColor(30, 58, 138)

        p_diagram_note2 = doc.add_paragraph()
        p_diagram_note2.paragraph_format.space_before = Pt(0)
        p_diagram_note2.paragraph_format.space_after = Pt(4)
        r_dn2 = p_diagram_note2.add_run("Diagram Versi 2 di bawah ini menyelaraskan atribut plain_password pada entitas USERS (ditandai khusus) yang digunakan oleh subsistem pencetakan kartu fisik siswa dan pemulihan akun oleh pustakawan:")
        r_dn2.font.name = "Calibri"
        r_dn2.font.size = Pt(9.5)
        r_dn2.font.italic = True
        r_dn2.font.color.rgb = RGBColor(71, 85, 105)

        img_p2 = doc.add_paragraph()
        img_p2.alignment = WD_ALIGN_PARAGRAPH.CENTER
        img_p2.paragraph_format.space_before = Pt(2)
        img_p2.paragraph_format.space_after = Pt(4)
        img_p2.add_run().add_picture(src_img_v2, width=Inches(10.2))

        cap_p2 = doc.add_paragraph()
        cap_p2.alignment = WD_ALIGN_PARAGRAPH.CENTER
        cap_p2.paragraph_format.space_after = Pt(0)
        rcap2 = cap_p2.add_run("Gambar 3.2: Physical Entity Relationship Diagram (Versi Perbaikan / V2) AKSA NOVA — Sinkronisasi plain_password")
        rcap2.font.name = 'Calibri'
        rcap2.font.size = Pt(9)
        rcap2.font.italic = True
        rcap2.font.color.rgb = RGBColor(100, 116, 139)

    # ==========================================
    # SECTION 3: PORTRAIT FOR MATRIX & DATA DICTIONARY
    # ==========================================
    sec3 = doc.add_section(WD_SECTION.NEW_PAGE)
    sec3.orientation = WD_ORIENTATION.PORTRAIT
    sec3.page_width = Inches(8.27)
    sec3.page_height = Inches(11.69)
    sec3.top_margin = Inches(1.0)
    sec3.bottom_margin = Inches(1.0)
    sec3.left_margin = Inches(1.0)
    sec3.right_margin = Inches(1.0)
    add_footer(sec3)

    # 4. Matriks Relasi Antar Entitas & Kardinalitas
    h2_4 = doc.add_heading(level=2)
    h2_4.paragraph_format.space_before = Pt(14)
    h2_4.paragraph_format.space_after = Pt(6)
    r_h2_4 = h2_4.add_run("4. Matriks Relasi Antar Entitas & Kardinalitas")
    r_h2_4.font.name = "Calibri"
    r_h2_4.font.size = Pt(13.5)
    r_h2_4.bold = True
    r_h2_4.font.color.rgb = RGBColor(30, 58, 138)

    p_rel_intro = doc.add_paragraph()
    p_rel_intro.paragraph_format.line_spacing = 1.15
    p_rel_intro.paragraph_format.space_after = Pt(6)
    add_runs(p_rel_intro, "Tabel matriks di bawah ini menjelaskan seluruh relasi antar entitas, tipe kardinalitas Crow's Foot, kolom penghubung (*Foreign Key*), serta aksi referensial (*ON DELETE / ON UPDATE*) yang diimplementasikan pada database:")

    matrix_headers = ["Tabel Induk (Parent)", "Tabel Anak (Child)", "Kardinalitas", "Foreign Key", "Aksi Referensial", "Deskripsi Hubungan Bisnis"]
    matrix_data = [
        ["users", "admin_profile", "1 : (0..1)", "admin_profile.user_id -> users.id", "CASCADE / CASCADE", "Satu akun admin memiliki tepat satu data profil tampilan display."],
        ["users", "peminjaman", "1 : N (0..*)", "peminjaman.user_id -> users.id", "SET NULL / CASCADE", "Satu siswa dapat memiliki banyak transaksi pinjam fisik. Jika akun siswa dihapus, riwayat transaksi tetap utuh (SET NULL) demi audit."],
        ["buku", "peminjaman", "1 : N (0..*)", "peminjaman.buku_id -> buku.id", "RESTRICT / CASCADE", "Buku fisik yang sedang dipinjam dilarang dihapus untuk menjaga keutuhan riwayat sirkulasi dan denda."],
        ["users", "pengajuan_peminjaman", "1 : N (0..*)", "pengajuan_peminjaman.user_id -> users.id", "Logical FK", "Satu anggota dapat mengajukan banyak permohonan antrean pinjam daring (maksimal 40 buku)."],
        ["buku", "pengajuan_peminjaman", "1 : N (0..*)", "pengajuan_peminjaman.buku_id -> buku.id", "Logical FK", "Satu judul buku dapat diajukan dalam berbagai tiket permohonan peminjaman daring."],
        ["peminjaman", "reminder_log", "1 : N (0..*)", "reminder_log.peminjaman_id -> peminjaman.id", "CASCADE / CASCADE", "Satu transaksi pinjam menunggak dapat memiliki log penagihan WhatsApp harian."],
        ["users", "buku_favorites", "1 : N (0..*)", "buku_favorites.user_id -> users.id", "CASCADE / CASCADE", "Daftar buku favorit yang disimpan oleh anggota."],
        ["buku", "buku_favorites", "1 : N (0..*)", "buku_favorites.buku_id -> buku.id", "CASCADE / CASCADE", "Buku yang disimpan dalam koleksi bacaan anggota."],
        ["users", "buku_likes", "1 : N (0..*)", "buku_likes.user_id -> users.id", "CASCADE / CASCADE", "Tanda suka yang diberikan oleh anggota pembaca."],
        ["buku", "buku_likes", "1 : N (0..*)", "buku_likes.buku_id -> buku.id", "CASCADE / CASCADE", "Buku yang disukai oleh anggota pembaca."],
        ["users", "buku_ratings", "1 : N (0..*)", "buku_ratings.user_id -> users.id", "CASCADE / CASCADE", "Ulasan nilai bintang (1-5) dari anggota. Admin dicegah memberi review."],
        ["buku", "buku_ratings", "1 : N (0..*)", "buku_ratings.buku_id -> buku.id", "CASCADE / CASCADE", "Ulasan dan penilaian rating pada katalog buku."]
    ]

    tbl_matrix = doc.add_table(rows=len(matrix_data) + 1, cols=len(matrix_headers))
    tbl_matrix.alignment = WD_TABLE_ALIGNMENT.CENTER
    tbl_matrix.autofit = True

    for c_idx, h_text in enumerate(matrix_headers):
        cell = tbl_matrix.rows[0].cells[c_idx]
        set_cell_background(cell, "1E3A8A")
        set_cell_margins(cell, top=70, bottom=70, left=80, right=80)
        p = cell.paragraphs[0]
        p.paragraph_format.space_before = Pt(2)
        p.paragraph_format.space_after = Pt(2)
        r = p.add_run(h_text)
        r.bold = True
        r.font.name = "Calibri"
        r.font.size = Pt(9)
        r.font.color.rgb = RGBColor(255, 255, 255)

    for r_idx, row_values in enumerate(matrix_data):
        row = tbl_matrix.rows[r_idx + 1]
        bg = "F8FAFC" if r_idx % 2 == 1 else "FFFFFF"
        for c_idx, val in enumerate(row_values):
            cell = row.cells[c_idx]
            set_cell_background(cell, bg)
            set_cell_margins(cell, top=50, bottom=50, left=80, right=80)
            p = cell.paragraphs[0]
            p.paragraph_format.space_before = Pt(1)
            p.paragraph_format.space_after = Pt(1)
            r = p.add_run(val)
            r.font.name = "Calibri"
            r.font.size = Pt(8.5)
            r.font.color.rgb = RGBColor(30, 41, 59)

    doc.add_paragraph().paragraph_format.space_after = Pt(6)

    # ==========================================
    # 5. Kamus Data Lengkap (Data Dictionary)
    # ==========================================
    doc.add_page_break()
    h2_5 = doc.add_heading(level=2)
    h2_5.paragraph_format.space_before = Pt(14)
    h2_5.paragraph_format.space_after = Pt(6)
    r_h2_5 = h2_5.add_run("5. Kamus Data Lengkap (Data Dictionary 12 Tabel Fisik)")
    r_h2_5.font.name = "Calibri"
    r_h2_5.font.size = Pt(13.5)
    r_h2_5.bold = True
    r_h2_5.font.color.rgb = RGBColor(30, 58, 138)

    p_dict_intro = doc.add_paragraph()
    p_dict_intro.paragraph_format.line_spacing = 1.15
    p_dict_intro.paragraph_format.space_after = Pt(8)
    add_runs(p_dict_intro, "Berikut adalah perincian teknis setiap tabel, kolom, tipe data presisi, nullability, nilai bawaan (*default*), jenis indeks (*key/index*), serta fungsi fungsionalnya dalam sistem yang disinkronkan 100% dengan `aksa_nova.sql`:")

    tables_spec = [
        {
            "name": "5.1 Tabel `users` (Master Autentikasi & Akun Pengguna)",
            "desc": "Tabel utama sistem yang menyimpan akun admin dan anggota (siswa dan guru), NIS/NIP, kelas/kategori Guru, nomor kontak, nomor kartu perpus, dan sandi terenkripsi Bcrypt.",
            "cols": [
                ["id", "INT", "No", "AUTO_INCREMENT", "PK", "Primary Key. Identitas unik pengguna."],
                ["full_name", "VARCHAR(100)", "No", "-", "-", "Nama lengkap resmi pengguna."],
                ["nik", "VARCHAR(30)", "No", "''", "-", "Nomor identitas resmi: NIS bagi siswa atau NIP/NUPTK bagi guru."],
                ["kelas", "VARCHAR(50)", "No", "''", "-", "Nama rombel kelas siswa (misal: 'XI RPL 1') atau 'Guru' bagi pendidik."],
                ["no_hp", "VARCHAR(20)", "No", "''", "-", "Nomor WhatsApp aktif untuk pengingat denda."],
                ["no_anggota", "VARCHAR(30)", "No", "''", "UK", "Nomor kartu anggota unik (AN-YYYY-XXXXX)."],
                ["username", "VARCHAR(50)", "No", "-", "UK", "Username unik untuk autentikasi login."],
                ["email", "VARCHAR(100)", "No", "-", "UK", "Email terverifikasi (@student.smkn1rongga.sch.id, @smkn1rongga.sch.id, @gmail.com)."],
                ["password", "VARCHAR(255)", "No", "-", "-", "Hash kata sandi menggunakan algoritma Bcrypt untuk autentikasi."],
                ["plain_password", "VARCHAR(255)", "No", "''", "-", "Kata sandi terkelola untuk cetak fisik kartu siswa & pemulihan akun."],
                ["foto", "VARCHAR(255)", "No", "''", "-", "Path berkas foto profil (uploads/anggota/)."],
                ["role", "ENUM('admin','member')", "Yes", "'member'", "-", "Tingkat hak akses pengguna (admin / member)."],
                ["status", "ENUM('pending','approved','rejected')", "No", "'pending'", "-", "Status persetujuan registrasi akun anggota."],
                ["card_status", "ENUM('active','frozen')", "No", "'active'", "-", "Status kartu; dibekukan saat proses Lupa Kartu."],
                ["created_at", "TIMESTAMP", "Yes", "CURRENT_TIMESTAMP", "-", "Waktu pertama kali akun dibuat di sistem."]
            ]
        },
        {
            "name": "5.2 Tabel `admin_profile` (Profil Tampilan Khusus Pustakawan)",
            "desc": "Menyimpan data pelengkap identitas visual khusus administrator perpustakaan (relasi 1:1 dengan `users`).",
            "cols": [
                ["id", "INT", "No", "AUTO_INCREMENT", "PK", "Primary Key identitas profil admin."],
                ["user_id", "INT", "No", "-", "FK, UK", "Foreign Key unik ke users.id. ON DELETE CASCADE."],
                ["display_name", "VARCHAR(100)", "No", "'Admin'", "-", "Nama panggilan di sidebar dan dashboard admin."],
                ["foto", "VARCHAR(255)", "No", "''", "-", "Path foto profil admin (uploads/profil/)."],
                ["updated_at", "TIMESTAMP", "Yes", "CURRENT_TIMESTAMP ON UPDATE", "-", "Waktu terakhir pembaruan data profil."]
            ]
        },
        {
            "name": "5.3 Tabel `kelas` (Master Referensi Rombongan Belajar & Kategori Guru)",
            "desc": "Tabel master data tingkat dan rombongan belajar siswa serta entitas khusus 'Guru' untuk opsi formulir pendaftaran, filter anggota, dan proteksi kenaikan kelas massal.",
            "cols": [
                ["id", "INT", "No", "AUTO_INCREMENT", "PK", "Primary Key."],
                ["nama_kelas", "VARCHAR(50)", "No", "-", "UK", "Nama kelas/kategori unik (misal: 'XI RPL 1', 'XII RPL 1', 'Alumni', 'Guru')."],
                ["created_at", "TIMESTAMP", "Yes", "CURRENT_TIMESTAMP", "-", "Waktu penambahan baris kelas."]
            ]
        },
        {
            "name": "5.4 Tabel `buku` (Master Katalog Koleksi & Stok Pustaka)",
            "desc": "Menyimpan katalog seluruh koleksi buku perpustakaan, kode ISBN, kuantitas stok atomik, sinopsis, dan nomor rak fisik.",
            "cols": [
                ["id", "INT", "No", "AUTO_INCREMENT", "PK", "Primary Key katalog buku."],
                ["judul", "VARCHAR(255)", "No", "-", "-", "Judul lengkap buku."],
                ["penulis", "VARCHAR(255)", "No", "''", "-", "Nama pengarang atau penulis buku."],
                ["isbn", "VARCHAR(50)", "No", "''", "-", "Kode ISBN unik buku (10 atau 13 digit)."],
                ["genre", "VARCHAR(100)", "No", "''", "-", "Klasifikasi tema buku (Komik, Fiksi, Teknologi)."],
                ["sinopsis", "TEXT", "Yes", "NULL", "-", "Ringkasan sinopsis atau sinopsis alur buku."],
                ["stok", "INT", "No", "0", "-", "Kuantitas eksemplar fisik yang tersedia."],
                ["rak", "VARCHAR(100)", "No", "''", "-", "Lokasi rak fisik di perpustakaan (misal: 'Rak Umum 1')."],
                ["gambar", "VARCHAR(500)", "No", "''", "-", "Path cover buku (uploads/gambar/)."],
                ["created_at", "TIMESTAMP", "Yes", "CURRENT_TIMESTAMP", "-", "Waktu buku didaftarkan."],
                ["updated_at", "TIMESTAMP", "Yes", "CURRENT_TIMESTAMP ON UPDATE", "-", "Waktu pembaruan informasi buku."]
            ]
        },
        {
            "name": "5.5 Tabel `peminjaman` (Sirkulasi Fisik, Jatuh Tempo & Denda)",
            "desc": "Mencatat transaksi fisik peminjaman buku di perpustakaan, tanggal jatuh tempo, denda keterlambatan, dan status pengembalian.",
            "cols": [
                ["id", "INT", "No", "AUTO_INCREMENT", "PK", "Primary Key sirkulasi peminjaman."],
                ["buku_id", "INT", "No", "-", "FK", "Foreign Key ke buku.id (ON DELETE RESTRICT)."],
                ["user_id", "INT", "Yes", "NULL", "FK", "Foreign Key ke users.id (ON DELETE SET NULL)."],
                ["nama_peminjam", "VARCHAR(150)", "No", "-", "-", "Snapshot nama peminjam saat transaksi dicatat."],
                ["waktu_pinjam", "DATETIME", "No", "CURRENT_TIMESTAMP", "-", "Waktu pencatatan peminjaman server."],
                ["batas_kembali", "DATETIME", "No", "-", "-", "Batas jatuh tempo wajib pengembalian buku."],
                ["waktu_kembali", "DATETIME", "Yes", "NULL", "-", "Waktu riil pengembalian buku ke perpustakaan."],
                ["terlambat_hari", "INT", "No", "0", "-", "Durasi keterlambatan dalam hitungan hari kalender."],
                ["denda", "DECIMAL(12,0)", "No", "0", "-", "Total denda keterlambatan (Rupiah)."],
                ["status_denda", "ENUM('tidak_ada','belum_bayar','lunas')", "No", "'tidak_ada'", "-", "Status pembayaran denda keterlambatan."],
                ["status", "ENUM('dipinjam','dikembalikan')", "No", "'dipinjam'", "-", "Status fisik sirkulasi buku."],
                ["created_at", "TIMESTAMP", "No", "CURRENT_TIMESTAMP", "-", "Waktu pembuatan record sirkulasi."]
            ]
        },
        {
            "name": "5.6 Tabel `pengajuan_peminjaman` (Antrean Pinjam Daring Maksimal 40 Buku)",
            "desc": "Menampung tiket permohonan pinjam buku daring dari anggota berkuota hingga 40 buku sebelum disetujui pustakawan.",
            "cols": [
                ["id", "INT", "No", "AUTO_INCREMENT", "PK", "Primary Key tiket antrean pengajuan."],
                ["user_id", "INT", "No", "-", "FK", "Foreign Key ke users.id."],
                ["buku_id", "INT", "No", "-", "FK", "Foreign Key ke buku.id."],
                ["nama_peminjam", "VARCHAR(150)", "No", "-", "-", "Nama anggota pemohon."],
                ["file_kartu", "VARCHAR(255)", "No", "-", "-", "Path kartu anggota digital yang diunggah pemohon."],
                ["total_buku", "INT", "No", "1", "-", "Jumlah buku yang diajukan (maksimal 40 buku)."],
                ["batas_kembali", "DATE", "No", "-", "-", "Rencana tanggal pengembalian buku."],
                ["waktu_pengambilan", "ENUM('sekarang','nanti')", "No", "'sekarang'", "-", "Pilihan jadwal pengambilan buku di perpus."],
                ["catatan_pengambilan", "VARCHAR(255)", "Yes", "NULL", "-", "Catatan jam/istirahat jika memilih opsi nanti."],
                ["status", "ENUM('menunggu','disetujui','ditolak')", "No", "'menunggu'", "INDEX", "Status verifikasi tiket pengajuan."],
                ["alasan_penolakan", "TEXT", "Yes", "NULL", "-", "Alasan penolakan jika permohonan ditolak admin."],
                ["approved_at", "DATETIME", "Yes", "NULL", "-", "Waktu persetujuan atau penolakan dilakukan."],
                ["created_at", "TIMESTAMP", "Yes", "CURRENT_TIMESTAMP", "-", "Waktu pengajuan dikirim oleh anggota."]
            ]
        },
        {
            "name": "5.7 Tabel `buku_favorites` (Bookmark Koleksi Anggota)",
            "desc": "Menyimpan buku-buku yang difavoritkan oleh anggota untuk dibaca kembali.",
            "cols": [
                ["id", "INT", "No", "AUTO_INCREMENT", "PK", "Primary Key."],
                ["buku_id", "INT", "No", "-", "FK, UK", "Foreign Key ke buku.id (ON DELETE CASCADE)."],
                ["user_id", "INT", "No", "-", "FK, UK", "Foreign Key ke users.id (ON DELETE CASCADE)."],
                ["created_at", "TIMESTAMP", "Yes", "CURRENT_TIMESTAMP", "-", "Waktu penambahan favorit. UK (buku_id, user_id)."]
            ]
        },
        {
            "name": "5.8 Tabel `buku_likes` (Apresiasi Suka Buku Anggota)",
            "desc": "Mencatat apresiasi suka (like) dari siswa pembaca terhadap buku tertentu.",
            "cols": [
                ["id", "INT", "No", "AUTO_INCREMENT", "PK", "Primary Key."],
                ["buku_id", "INT", "No", "-", "FK, UK", "Foreign Key ke buku.id (ON DELETE CASCADE)."],
                ["user_id", "INT", "No", "-", "FK, UK", "Foreign Key ke users.id (ON DELETE CASCADE)."],
                ["created_at", "TIMESTAMP", "Yes", "CURRENT_TIMESTAMP", "-", "Waktu like diberikan. UK (buku_id, user_id)."]
            ]
        },
        {
            "name": "5.9 Tabel `buku_ratings` (Rating Skala 1–5 & Ulasan Anggota)",
            "desc": "Menampung skor bintang penilaian 1 hingga 5 serta ulasan teks siswa pembaca. Admin dicegah memberi review.",
            "cols": [
                ["id", "INT", "No", "AUTO_INCREMENT", "PK", "Primary Key ulasan."],
                ["user_id", "INT", "No", "-", "FK, UK", "Foreign Key ke users.id (ON DELETE CASCADE)."],
                ["buku_id", "INT", "No", "-", "FK, UK", "Foreign Key ke buku.id (ON DELETE CASCADE)."],
                ["rating", "TINYINT", "No", "-", "-", "Skor nilai bintang (rentang nilai 1 - 5)."],
                ["created_at", "DATETIME", "Yes", "CURRENT_TIMESTAMP", "-", "Waktu pertama kali ulasan ditulis."],
                ["updated_at", "DATETIME", "Yes", "CURRENT_TIMESTAMP", "-", "Waktu pembaruan ulasan. UK (user_id, buku_id)."]
            ]
        },
        {
            "name": "5.10 Tabel `reminder_log` (Log Pengingat WhatsApp Keterlambatan)",
            "desc": "Mencatat riwayat pengiriman notifikasi penagihan denda via WhatsApp agar tidak terjadi spam pesan berulang dalam satu hari.",
            "cols": [
                ["id", "INT", "No", "AUTO_INCREMENT", "PK", "Primary Key log pengingat."],
                ["peminjaman_id", "INT", "No", "-", "FK, UK", "Foreign Key ke peminjaman.id (ON DELETE CASCADE)."],
                ["tanggal", "DATE", "No", "-", "UK", "Tanggal deteksi pengingat. UK (peminjaman_id, tanggal)."],
                ["terlambat_hari", "INT", "No", "0", "-", "Durasi hari keterlambatan saat log dicatat."],
                ["wa_terkirim", "TINYINT(1)", "No", "0", "-", "Status kirim (1 = terkirim via WA Web API, 0 = belum)."],
                ["wa_terkirim_at", "DATETIME", "Yes", "NULL", "-", "Waktu tepat saat tautan WA dibuka/dikirimkan."],
                ["created_at", "TIMESTAMP", "No", "CURRENT_TIMESTAMP", "-", "Waktu baris log dibuat."]
            ]
        },
        {
            "name": "5.11 Tabel `banner` (Media Slider Promo Carousel Beranda)",
            "desc": "Pengelolaan konten hero carousel beranda yang mendukung format gambar (JPG/PNG/WEBP), GIF animasi, dan video (MP4/WEBM).",
            "cols": [
                ["id", "INT", "No", "AUTO_INCREMENT", "PK", "Primary Key media banner."],
                ["judul", "VARCHAR(150)", "No", "''", "-", "Judul promo atau pengumuman perpustakaan."],
                ["subjudul", "VARCHAR(255)", "No", "''", "-", "Subjudul atau deskripsi singkat promo."],
                ["gambar", "VARCHAR(500)", "No", "''", "-", "Path berkas media banner (uploads/banner/)."],
                ["link_url", "VARCHAR(255)", "No", "''", "-", "Tautan tujuan saat banner diklik pengguna."],
                ["urutan", "INT", "No", "0", "-", "Nomor urut prioritas penayangan carousel."],
                ["aktif", "TINYINT(1)", "No", "1", "-", "Status tayang (1 = aktif tayang, 0 = disembunyikan)."],
                ["created_at", "TIMESTAMP", "Yes", "CURRENT_TIMESTAMP", "-", "Waktu pembuatan banner."],
                ["updated_at", "TIMESTAMP", "Yes", "CURRENT_TIMESTAMP ON UPDATE", "-", "Waktu terakhir pembaruan banner."]
            ]
        },
        {
            "name": "5.12 Tabel `pengaturan` (Konfigurasi Dinamis Key-Value Sistem)",
            "desc": "Penyimpanan variabel konfigurasi dinamis sistem seperti tarif denda per hari, masa tenggang denda, audio background, dan kontak.",
            "cols": [
                ["id", "INT", "No", "AUTO_INCREMENT", "PK", "Primary Key konfigurasi."],
                ["kunci", "VARCHAR(100)", "No", "-", "UK", "Nama unik kunci pengaturan (misal: 'denda_per_hari')."],
                ["nilai", "VARCHAR(500)", "No", "''", "-", "Nilai pengaturan tersimpan (misal: '5000')."],
                ["keterangan", "VARCHAR(255)", "No", "''", "-", "Penjelasan deskriptif fungsi pengaturan terkait."],
                ["updated_at", "TIMESTAMP", "Yes", "CURRENT_TIMESTAMP ON UPDATE", "-", "Waktu terakhir nilai diubah pustakawan."]
            ]
        }
    ]

    for t_spec in tables_spec:
        h3 = doc.add_heading(level=3)
        h3.paragraph_format.space_before = Pt(10)
        h3.paragraph_format.space_after = Pt(2)
        r_h3 = h3.add_run(t_spec["name"])
        r_h3.font.name = "Calibri"
        r_h3.font.size = Pt(11)
        r_h3.bold = True
        r_h3.font.color.rgb = RGBColor(30, 58, 138)

        p_desc = doc.add_paragraph()
        p_desc.paragraph_format.line_spacing = 1.15
        p_desc.paragraph_format.space_after = Pt(4)
        add_runs(p_desc, t_spec["desc"])

        t_headers = ["Kolom / Field", "Tipe Data", "Null?", "Default", "Key", "Fungsi & Aturan Integritas"]
        tbl = doc.add_table(rows=len(t_spec["cols"]) + 1, cols=6)
        tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
        tbl.autofit = True

        for c_idx, h_text in enumerate(t_headers):
            cell = tbl.rows[0].cells[c_idx]
            set_cell_background(cell, "1E293B")
            set_cell_margins(cell, top=60, bottom=60, left=70, right=70)
            p = cell.paragraphs[0]
            p.paragraph_format.space_before = Pt(2)
            p.paragraph_format.space_after = Pt(2)
            r = p.add_run(h_text)
            r.bold = True
            r.font.name = "Calibri"
            r.font.size = Pt(8.5)
            r.font.color.rgb = RGBColor(255, 255, 255)

        for r_idx, col_vals in enumerate(t_spec["cols"]):
            row = tbl.rows[r_idx + 1]
            bg = "F8FAFC" if r_idx % 2 == 1 else "FFFFFF"
            for c_idx in range(6):
                cell = row.cells[c_idx]
                set_cell_background(cell, bg)
                set_cell_margins(cell, top=45, bottom=45, left=70, right=70)
                p = cell.paragraphs[0]
                p.paragraph_format.space_before = Pt(1)
                p.paragraph_format.space_after = Pt(1)
                val = col_vals[c_idx] if c_idx < len(col_vals) else ""
                r = p.add_run(val)
                if c_idx == 0:
                    r.font.name = "Consolas"
                    r.font.size = Pt(8.5)
                    r.bold = True
                    r.font.color.rgb = RGBColor(185, 28, 28) if "PK" in col_vals[4] else (RGBColor(29, 78, 216) if "FK" in col_vals[4] else RGBColor(15, 23, 42))
                elif c_idx == 1:
                    r.font.name = "Consolas"
                    r.font.size = Pt(8)
                    r.font.color.rgb = RGBColor(100, 116, 139)
                elif c_idx == 4:
                    r.font.name = "Calibri"
                    r.font.size = Pt(8)
                    r.bold = True
                    r.font.color.rgb = RGBColor(185, 28, 28) if "PK" in val else (RGBColor(29, 78, 216) if "FK" in val else RGBColor(180, 83, 9))
                else:
                    r.font.name = "Calibri"
                    r.font.size = Pt(8.5)
                    r.font.color.rgb = RGBColor(30, 41, 59)

        doc.add_paragraph().paragraph_format.space_after = Pt(3)

    # ==========================================
    # 6. Aturan Integritas Data & Proteksi Sistem
    # ==========================================
    doc.add_page_break()
    h2_6 = doc.add_heading(level=2)
    h2_6.paragraph_format.space_before = Pt(14)
    h2_6.paragraph_format.space_after = Pt(6)
    r_h2_6 = h2_6.add_run("6. Aturan Integritas Data & Logika Transaksional Bisnis")
    r_h2_6.font.name = "Calibri"
    r_h2_6.font.size = Pt(13.5)
    r_h2_6.bold = True
    r_h2_6.font.color.rgb = RGBColor(30, 58, 138)

    rules = [
        ("6.1 Pengendalian Stok Buku Atomik (Pencegahan Race Condition)",
         "Untuk mencegah kuantitas stok buku menjadi negatif akibat persetujuan transaksi yang terjadi secara bersamaan, pemotongan stok pada persetujuan tiket pengajuan maupun peminjaman langsung dieksekusi secara atomik menggunakan klausa guard SQL:\n"
         "• Saat peminjaman: `UPDATE buku SET stok = stok - ? WHERE id = ? AND stok >= ?;`\n"
         "• Saat pengembalian: `UPDATE buku SET stok = stok + 1 WHERE id = ?;`\n"
         "Sistem secara ketat memeriksa nilai `mysqli_affected_rows()`. Jika bernilai 0 (artinya stok fisik sudah habis), transaksi peminjaman otomatis digagalkan dan pesan peringatan dimunculkan kepada pustakawan."),
        ("6.2 Validasi Kuota Maksimal Peminjaman (40 Buku)",
         "Pada tabel `pengajuan_peminjaman`, kolom `total_buku` divalidasi pada lapis backend PHP dan antarmuka JavaScript dengan batas atas 40 buku per transaksi. Validasi ini melindungi inventaris perpustakaan dari pemesanan massal yang tidak wajar."),
        ("6.3 Keutuhan Riwayat Sirkulasi & Denda (ON DELETE SET NULL)",
         "Penghapusan akun anggota siswa dari tabel `users` tidak diperbolehkan menghapus catatan sirkulasi peminjaman yang pernah terjadi. Relasi Foreign Key `peminjaman.user_id` diatur secara otomatis ke `ON DELETE SET NULL`. Bersamaan dengan itu, nama anggota tetap tersimpan permanen pada kolom snapshot `nama_peminjam` untuk menjamin akuntabilitas laporan pertanggungjawaban sirkulasi perpustakaan."),
        ("6.4 Fleksibilitas Akun Resmi & Keamanan Identitas",
         "Tabel `users` menerapkan tiga indeks unik independen: `no_anggota` (UK), `username` (UK), dan `email` (UK). Registrasi mandiri mendukung siswa dan tenaga pendidik/guru dengan verifikasi NIS/NIP serta domain email sekolah (`@student.smkn1rongga.sch.id`, `@smkn1rongga.sch.id`) maupun email umum terverifikasi (`@gmail.com`) untuk menjamin aksesibilitas seluruh warga sekolah tanpa mengorbankan integritas data."),
        ("6.5 Proteksi Anti-Spam Pengingat Keterlambatan WhatsApp",
         "Tabel `reminder_log` mengimplementasikan indeks unik komposit `UNIQUE KEY uq_pinjam_tanggal (peminjaman_id, tanggal)`. Jika administrator menekan tombol pengingat berkali-kali pada hari yang sama, database menolak penulisan ganda sehingga siswa tidak akan terganggu oleh pengiriman pesan WhatsApp berulang pada tanggal yang sama."),
        ("6.6 Isolasi Hak Akses Ulasan Buku (Admin Review Restriction)",
         "Tabel `buku_ratings` dirancang untuk mengukur kepuasan siswa pembaca. Pada kode backend `detail_buku.php`, terdapat proteksi logika bisnis bahwa akun dengan peran `role = 'admin'` dilarang menulis ulasan atau memberikan rating, sehingga objektivitas evaluasi buku tetap terjaga.")
    ]

    for title, desc in rules:
        p_t = doc.add_paragraph()
        p_t.paragraph_format.space_before = Pt(8)
        p_t.paragraph_format.space_after = Pt(2)
        r = p_t.add_run(title)
        r.bold = True
        r.font.name = "Calibri"
        r.font.size = Pt(11)
        r.font.color.rgb = RGBColor(15, 23, 42)

        p_d = doc.add_paragraph()
        p_d.paragraph_format.line_spacing = 1.15
        p_d.paragraph_format.space_after = Pt(6)
        add_runs(p_d, desc)

    # Save to both paths
    out_docx_erd = 'ERD/Penjelasan ERD - AKSA NOVA.docx'
    out_docx_docs = 'docs/Penjelasan_ERD_AKSA_NOVA.docx'

    print("Saving to " + out_docx_erd + "...")
    doc.save(out_docx_erd)
    try:
        print("Saving to " + out_docx_docs + "...")
        doc.save(out_docx_docs)
    except PermissionError:
        alt_path = 'docs/Penjelasan_ERD_AKSA_NOVA_terbaru.docx'
        print(f"Warning: {out_docx_docs} is currently locked by another program (e.g. Word). Saving to {alt_path} instead.")
        doc.save(alt_path)
    print("Done! ERD Explanation documents successfully updated!")

if __name__ == '__main__':
    create_erd_doc()
