# -*- coding: utf-8 -*-
"""
High-definition Physical ERD Diagram Generator (V2 - Versi Perbaikan) for AKSA NOVA
Produces:
  - ERD/erd_interactive_v2.html
  - ERD/erd_diagram_v2.png (High-Res)
  - docs/erd_diagram_v2.png
  - ERD/erd_v2.mmd (Full detailed Mermaid with attributes including plain_password)
  - docs/erd_v2.mmd
Preserves all existing V1 files without deletion or overwrite.
"""

import os
import subprocess
import shutil
from PIL import Image

def build_mermaid_v2():
    mmd_content = """erDiagram
    %% ==========================================
    %% PHYSICAL ERD (V2 REVISI / PERBAIKAN) - AKSA NOVA (MySQL 8.0 / InnoDB)
    %% Penyusun: Syahrul Bimansyah
    %% SMK Negeri 1 Rongga
    %% Catatan Perbaikan: Menyelaraskan atribut plain_password pada entitas USERS
    %% ==========================================

    USERS ||--o| ADMIN_PROFILE : "user_id (1:1)"
    USERS ||--o{ PEMINJAMAN : "user_id (1:N)"
    USERS ||--o{ PENGAJUAN_PEMINJAMAN : "user_id (1:N)"
    USERS ||--o{ BUKU_FAVORITES : "user_id (1:N)"
    USERS ||--o{ BUKU_LIKES : "user_id (1:N)"
    USERS ||--o{ BUKU_RATINGS : "user_id (1:N)"

    BUKU ||--o{ PEMINJAMAN : "buku_id (1:N)"
    BUKU ||--o{ PENGAJUAN_PEMINJAMAN : "buku_id (1:N)"
    BUKU ||--o{ BUKU_FAVORITES : "buku_id (1:N)"
    BUKU ||--o{ BUKU_LIKES : "buku_id (1:N)"
    BUKU ||--o{ BUKU_RATINGS : "buku_id (1:N)"

    PEMINJAMAN ||--o{ REMINDER_LOG : "peminjaman_id (1:N)"

    USERS {
        int id PK
        varchar full_name
        varchar nik "NIS / NIK"
        varchar kelas
        varchar no_hp
        varchar no_anggota UK
        varchar username UK
        varchar email UK
        varchar password "Bcrypt hash login"
        varchar plain_password "Sandi kartu siswa / recovery"
        varchar foto
        enum role "admin, member"
        enum status "pending, approved, rejected"
        enum card_status "active, frozen"
        timestamp created_at
    }

    ADMIN_PROFILE {
        int id PK
        int user_id FK,UK "Relasi 1:1"
        varchar display_name
        varchar foto
        timestamp updated_at
    }

    KELAS {
        int id PK
        varchar nama_kelas UK
        timestamp created_at
    }

    BUKU {
        int id PK
        varchar judul
        varchar penulis
        varchar isbn
        varchar genre
        text sinopsis
        int stok "Stok fisik atomik"
        varchar rak "Lokasi rak buku fisik"
        varchar gambar
        timestamp created_at
        timestamp updated_at
    }

    PEMINJAMAN {
        int id PK
        int buku_id FK
        int user_id FK "Nullable"
        varchar nama_peminjam
        datetime waktu_pinjam
        datetime batas_kembali
        datetime waktu_kembali "Nullable"
        int terlambat_hari
        decimal denda
        enum status_denda "tidak_ada, belum_bayar, lunas"
        enum status "dipinjam, dikembalikan"
        timestamp created_at
    }

    PENGAJUAN_PEMINJAMAN {
        int id PK
        int user_id FK
        int buku_id FK
        varchar nama_peminjam
        varchar file_kartu
        int total_buku "Batas maks 40 buku"
        date batas_kembali
        enum waktu_pengambilan "sekarang, nanti"
        varchar catatan_pengambilan
        enum status "menunggu, disetujui, ditolak"
        text alasan_penolakan
        datetime approved_at
        timestamp created_at
    }

    BUKU_FAVORITES {
        int id PK
        int buku_id FK,UK
        int user_id FK,UK
        timestamp created_at
    }

    BUKU_LIKES {
        int id PK
        int buku_id FK,UK
        int user_id FK,UK
        timestamp created_at
    }

    BUKU_RATINGS {
        int id PK
        int user_id FK,UK
        int buku_id FK,UK
        tinyint rating "Skala 1 - 5"
        datetime created_at
        datetime updated_at
    }

    REMINDER_LOG {
        int id PK
        int peminjaman_id FK,UK
        date tanggal UK "Audit per hari"
        int terlambat_hari
        tinyint wa_terkirim
        datetime wa_terkirim_at
        timestamp created_at
    }

    BANNER {
        int id PK
        varchar judul
        varchar subjudul
        varchar gambar "JPG, GIF, MP4"
        varchar link_url
        int urutan
        tinyint aktif
        timestamp created_at
        timestamp updated_at
    }

    PENGATURAN {
        int id PK
        varchar kunci UK "Key konfigurasi"
        varchar nilai "Value konfigurasi"
        varchar keterangan
        timestamp updated_at
    }
"""
    with open("ERD/erd_v2.mmd", "w", encoding="utf-8") as f:
        f.write(mmd_content)
    with open("docs/erd_v2.mmd", "w", encoding="utf-8") as f:
        f.write(mmd_content)
    print("Generated ERD/erd_v2.mmd and docs/erd_v2.mmd")


def build_html_diagram_v2():
    with open("ERD/erd_interactive.html", "r", encoding="utf-8") as f:
        html = f.read()

    # 1. Update Title and Header to denote V2 / Perbaikan
    html = html.replace("<title>Physical ERD - AKSA NOVA</title>", "<title>Physical ERD (Versi Perbaikan / V2) - AKSA NOVA</title>")
    html = html.replace("<h1>PHYSICAL ENTITY RELATIONSHIP DIAGRAM (ERD) — AKSA NOVA</h1>",
                        "<h1>PHYSICAL ENTITY RELATIONSHIP DIAGRAM (ERD) — AKSA NOVA (VERSI PERBAIKAN)</h1>")

    # 2. Insert plain_password into USERS table card
    target_pwd = """    <tr>
      <td class="col-name">password</td>
      <td class="col-type">varchar(255)</td>
      <td class="col-key"></td>
    </tr>"""

    replacement_pwd = """    <tr>
      <td class="col-name">password</td>
      <td class="col-type">varchar(255) [Bcrypt]</td>
      <td class="col-key"></td>
    </tr>
    <tr style="background:#eff6ff;">
      <td class="col-name" style="color:#1d4ed8; font-weight:600;">plain_password</td>
      <td class="col-type">varchar(255) [Kartu]</td>
      <td class="col-key"><span class="pill" style="background:#dbeafe; color:#1e40af; border:1px solid #93c5fd;">REV</span></td>
    </tr>"""

    if target_pwd in html:
        html = html.replace(target_pwd, replacement_pwd, 1)
    else:
        # Fallback if whitespace differs
        print("Warning: target_pwd exact match not found, looking for alternative replace")
        html = html.replace('<td class="col-name">password</td>',
                            '<td class="col-name">password</td></tr><tr><td class="col-name" style="color:#1d4ed8; font-weight:600;">plain_password</td><td class="col-type">varchar(255) [Kartu]</td><td class="col-key"><span class="pill" style="background:#dbeafe; color:#1e40af; border:1px solid #93c5fd;">REV</span></td>')

    # 3. Update footer text of users card
    old_footer = '<div class="card-footer">UK: no_anggota, username, email. Sandi di-hash Bcrypt</div>'
    new_footer = '<div class="card-footer">UK: no_anggota, username, email. password (Bcrypt hash) & plain_password (cetak kartu/recovery)</div>'
    html = html.replace(old_footer, new_footer)

    out_html = os.path.abspath("ERD/erd_interactive_v2.html")
    with open(out_html, "w", encoding="utf-8") as f:
        f.write(html)
    print(f"Generated {out_html}")
    return out_html


def render_html_v2(html_abs):
    browser = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
    if not os.path.exists(browser):
        browser = r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"

    out_png = os.path.abspath("ERD/erd_diagram_v2.png")
    file_url = "file:///" + html_abs.replace("\\", "/")

    cmd = [
        browser,
        "--headless",
        "--disable-gpu",
        "--hide-scrollbars",
        "--window-size=2320,1580",
        f"--screenshot={out_png}",
        file_url
    ]

    subprocess.run(cmd, check=True)
    shutil.copy2(out_png, "docs/erd_diagram_v2.png")
    print(f"Rendered {out_png} and copied to docs/erd_diagram_v2.png")
    im = Image.open(out_png)
    print("V2 Image size:", im.size)


if __name__ == '__main__':
    build_mermaid_v2()
    html_path = build_html_diagram_v2()
    render_html_v2(html_path)
