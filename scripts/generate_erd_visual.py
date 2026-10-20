# -*- coding: utf-8 -*-
"""
High-definition Physical ERD Diagram Generator for AKSA NOVA
Produces:
  - ERD/erd_interactive.html
  - ERD/erd_diagram.png (High-Res 300 DPI equivalent)
  - docs/erd_diagram.png
  - ERD/erd.mmd (Full detailed Mermaid with attributes)
  - docs/erd.mmd
"""

import os
import subprocess
import shutil
from PIL import Image

def build_mermaid_file():
    mmd_content = """erDiagram
    %% ==========================================
    %% PHYSICAL ERD - AKSA NOVA (MySQL 8.0 / InnoDB)
    %% Penyusun: Syahrul Bimansyah
    %% SMK Negeri 1 Rongga
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
        varchar password "Bcrypt hash"
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
    with open("ERD/erd.mmd", "w", encoding="utf-8") as f:
        f.write(mmd_content)
    with open("docs/erd.mmd", "w", encoding="utf-8") as f:
        f.write(mmd_content)
    print("Updated ERD/erd.mmd and docs/erd.mmd")


def build_html_diagram():
    html = """<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Physical ERD - AKSA NOVA</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    background-color: #f1f5f9;
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
    color: #0f172a;
    width: 2320px;
    height: 1580px;
    position: relative;
    overflow: hidden;
  }

  /* Header banner */
  .title-header {
    position: absolute;
    top: 25px;
    left: 40px;
    right: 40px;
    height: 72px;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    padding: 12px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
    z-index: 10;
  }
  .title-header h1 {
    font-size: 21px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.5px;
  }
  .title-header .subtitle {
    font-size: 12.5px;
    color: #64748b;
    margin-top: 3px;
  }
  .legend-bar {
    display: flex;
    align-items: center;
    gap: 18px;
    font-size: 12px;
    color: #334155;
    background: #f8fafc;
    padding: 6px 14px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
  }
  .legend-item { display: flex; align-items: center; gap: 6px; font-weight: 500; }
  .badge-pk { background: #fee2e2; color: #b91c1c; font-weight: 700; padding: 2px 6px; border-radius: 4px; font-size: 10.5px; border: 1px solid #fca5a5; }
  .badge-fk { background: #dbeafe; color: #1e40af; font-weight: 700; padding: 2px 6px; border-radius: 4px; font-size: 10.5px; border: 1px solid #93c5fd; }
  .badge-uk { background: #fef3c7; color: #b45309; font-weight: 700; padding: 2px 6px; border-radius: 4px; font-size: 10.5px; border: 1px solid #fcd34d; }

  /* SVG overlay */
  svg.connections {
    position: absolute;
    top: 0;
    left: 0;
    width: 2320px;
    height: 1580px;
    pointer-events: none;
    z-index: 1;
  }

  /* Entity Card Component */
  .entity-card {
    position: absolute;
    background: #ffffff;
    border: 1.5px solid #94a3b8;
    border-radius: 6px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    z-index: 2;
    overflow: hidden;
  }

  .entity-header {
    padding: 7px 12px;
    font-weight: 700;
    font-size: 13.5px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1.5px solid #94a3b8;
  }
  .entity-header.blue { background: #dbeafe; border-bottom-color: #3b82f6; color: #1e3a8a; }
  .entity-header.emerald { background: #d1fae5; border-bottom-color: #10b981; color: #064e3b; }
  .entity-header.amber { background: #fef3c7; border-bottom-color: #f59e0b; color: #78350f; }
  .entity-header.purple { background: #ede9fe; border-bottom-color: #8b5cf6; color: #4c1d95; }
  .entity-header.indigo { background: #e0e7ff; border-bottom-color: #6366f1; color: #312e81; }
  .entity-header.slate { background: #e2e8f0; border-bottom-color: #64748b; color: #1e293b; }

  .header-tag { font-size: 11px; font-weight: 500; opacity: 0.85; }

  .entity-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11.5px;
  }

  .entity-table tr {
    border-bottom: 1px solid #f1f5f9;
  }
  .entity-table tr:last-child {
    border-bottom: none;
  }

  .entity-table td {
    padding: 4px 8px;
    vertical-align: middle;
  }

  .col-name {
    font-family: 'Consolas', 'Courier New', monospace;
    font-weight: 600;
    color: #1e293b;
    white-space: nowrap;
  }
  .col-name.is-pk { font-weight: 700; color: #b91c1c; }
  .col-name.is-fk { font-weight: 700; color: #1d4ed8; }

  .col-type {
    font-family: 'Consolas', 'Courier New', monospace;
    color: #64748b;
    font-size: 11px;
    white-space: nowrap;
    padding-right: 8px;
  }

  .col-key {
    text-align: right;
    white-space: nowrap;
  }

  .pill {
    font-size: 9.5px;
    font-weight: 700;
    padding: 1px 4px;
    border-radius: 3px;
    display: inline-block;
    margin-left: 2px;
  }
  .pill-pk { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
  .pill-fk { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
  .pill-uk { background: #fef3c7; color: #b45309; border: 1px solid #fcd34d; }

  .card-footer {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 4px 8px;
    font-size: 10px;
    color: #64748b;
    font-style: italic;
  }

  /* Relationship label pills on lines */
  .rel-label {
    position: absolute;
    background: #ffffff;
    border: 1px solid #94a3b8;
    border-radius: 4px;
    padding: 2px 6px;
    font-size: 10.5px;
    font-weight: 600;
    color: #334155;
    z-index: 5;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    white-space: nowrap;
  }
</style>
</head>
<body>

<div class="title-header">
  <div>
    <h1>PHYSICAL ENTITY RELATIONSHIP DIAGRAM (ERD) — AKSA NOVA</h1>
    <div class="subtitle">Sistem Informasi Perpustakaan Digital SMK Negeri 1 Rongga | Database Engine: MySQL 8.0 (InnoDB, utf8mb4) | Penyusun: Syahrul Bimansyah (@arul)</div>
  </div>
  <div class="legend-bar">
    <div class="legend-item"><span class="badge-pk">PK</span> Primary Key</div>
    <div class="legend-item"><span class="badge-fk">FK</span> Foreign Key</div>
    <div class="legend-item"><span class="badge-uk">UK</span> Unique Key</div>
    <div class="legend-item" style="color:#2563eb; margin-left:8px;"><strong>—||—o&lt;</strong> Relasi 1 to Many</div>
    <div class="legend-item" style="color:#6366f1;"><strong>—||—o|</strong> Relasi 1 to 1</div>
  </div>
</div>

<!-- SVG Canvas with Crow's Foot Markers -->
<svg class="connections" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <!-- Crow's Foot: Optional Many (Circle + Trident) -->
    <marker id="cf-many" markerWidth="20" markerHeight="20" refX="18" refY="10" orient="auto">
      <circle cx="5" cy="10" r="3" fill="#ffffff" stroke="#2563eb" stroke-width="1.8" />
      <path d="M 9 3 L 18 10 L 9 17 M 18 10 L 2 10" fill="none" stroke="#2563eb" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
    </marker>
    <!-- Crow's Foot: Optional Many (Rose Red for Likes) -->
    <marker id="cf-many-rose" markerWidth="20" markerHeight="20" refX="18" refY="10" orient="auto">
      <circle cx="5" cy="10" r="3" fill="#ffffff" stroke="#e11d48" stroke-width="1.8" />
      <path d="M 9 3 L 18 10 L 9 17 M 18 10 L 2 10" fill="none" stroke="#e11d48" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
    </marker>
    <!-- Crow's Foot: Optional Many (Amber for Ratings) -->
    <marker id="cf-many-amber" markerWidth="20" markerHeight="20" refX="18" refY="10" orient="auto">
      <circle cx="5" cy="10" r="3" fill="#ffffff" stroke="#d97706" stroke-width="1.8" />
      <path d="M 9 3 L 18 10 L 9 17 M 18 10 L 2 10" fill="none" stroke="#d97706" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
    </marker>
    <!-- Crow's Foot: Optional Many (Sky for Favorites) -->
    <marker id="cf-many-sky" markerWidth="20" markerHeight="20" refX="18" refY="10" orient="auto">
      <circle cx="5" cy="10" r="3" fill="#ffffff" stroke="#0284c7" stroke-width="1.8" />
      <path d="M 9 3 L 18 10 L 9 17 M 18 10 L 2 10" fill="none" stroke="#0284c7" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
    </marker>

    <!-- Parent: Mandatory One (Double bar: ||) -->
    <marker id="one-mandatory" markerWidth="14" markerHeight="20" refX="2" refY="10" orient="auto">
      <line x1="4" y1="3" x2="4" y2="17" stroke="#2563eb" stroke-width="2" />
      <line x1="9" y1="3" x2="9" y2="17" stroke="#2563eb" stroke-width="2" />
    </marker>
    <marker id="one-mandatory-sky" markerWidth="14" markerHeight="20" refX="2" refY="10" orient="auto">
      <line x1="4" y1="3" x2="4" y2="17" stroke="#0284c7" stroke-width="2" />
      <line x1="9" y1="3" x2="9" y2="17" stroke="#0284c7" stroke-width="2" />
    </marker>
    <marker id="one-mandatory-rose" markerWidth="14" markerHeight="20" refX="2" refY="10" orient="auto">
      <line x1="4" y1="3" x2="4" y2="17" stroke="#e11d48" stroke-width="2" />
      <line x1="9" y1="3" x2="9" y2="17" stroke="#e11d48" stroke-width="2" />
    </marker>
    <marker id="one-mandatory-amber" markerWidth="14" markerHeight="20" refX="2" refY="10" orient="auto">
      <line x1="4" y1="3" x2="4" y2="17" stroke="#d97706" stroke-width="2" />
      <line x1="9" y1="3" x2="9" y2="17" stroke="#d97706" stroke-width="2" />
    </marker>

    <!-- Optional One (Circle + Single bar: o|) -->
    <marker id="one-optional" markerWidth="18" markerHeight="20" refX="16" refY="10" orient="auto">
      <circle cx="5" cy="10" r="3" fill="#ffffff" stroke="#6366f1" stroke-width="1.8" />
      <line x1="12" y1="3" x2="12" y2="17" stroke="#6366f1" stroke-width="2" />
    </marker>
  </defs>

  <!-- Line 1: users (x=210, y=340) -> admin_profile (x=210, y=285) [1:1 Optional] -->
  <path d="M 210 340 L 210 285" fill="none" stroke="#6366f1" stroke-width="2" marker-start="url(#one-mandatory)" marker-end="url(#one-optional)" />

  <!-- Line 2: users (x=380, y=420) -> pengajuan_peminjaman (x=590, y=250) [1:N] -->
  <path d="M 380 420 L 485 420 L 485 250 L 590 250" fill="none" stroke="#2563eb" stroke-width="2" marker-start="url(#one-mandatory)" marker-end="url(#cf-many)" />

  <!-- Line 3: users (x=380, y=560) -> peminjaman (x=590, y=700) [1:N] -->
  <path d="M 380 560 L 485 560 L 485 700 L 590 700" fill="none" stroke="#2563eb" stroke-width="2" marker-start="url(#one-mandatory)" marker-end="url(#cf-many)" />

  <!-- Line 4: buku (x=1140, y=280) -> pengajuan_peminjaman (x=970, y=280) [1:N] -->
  <path d="M 1140 280 L 970 280" fill="none" stroke="#2563eb" stroke-width="2" marker-start="url(#one-mandatory)" marker-end="url(#cf-many)" />

  <!-- Line 5: buku (x=1140, y=440) -> peminjaman (x=970, y=750) [1:N] -->
  <path d="M 1140 440 L 1055 440 L 1055 750 L 970 750" fill="none" stroke="#2563eb" stroke-width="2" marker-start="url(#one-mandatory)" marker-end="url(#cf-many)" />

  <!-- Line 6: peminjaman (x=780, y=1030) -> reminder_log (x=780, y=1100) [1:N] -->
  <path d="M 780 1030 L 780 1100" fill="none" stroke="#2563eb" stroke-width="2" marker-start="url(#one-mandatory)" marker-end="url(#cf-many)" />

  <!-- Line 7: users (x=380, y=650) -> buku_favorites (x=1720, y=210) [1:N] -->
  <path d="M 380 650 L 440 650 L 440 105 L 1620 105 L 1620 210 L 1720 210" fill="none" stroke="#0284c7" stroke-width="1.8" stroke-dasharray="6,3" marker-start="url(#one-mandatory-sky)" marker-end="url(#cf-many-sky)" />

  <!-- Line 8: buku (x=1490, y=230) -> buku_favorites (x=1720, y=230) [1:N] -->
  <path d="M 1490 230 L 1720 230" fill="none" stroke="#0284c7" stroke-width="1.8" marker-start="url(#one-mandatory-sky)" marker-end="url(#cf-many-sky)" />

  <!-- Line 9: users (x=380, y=720) -> buku_likes (x=1720, y=430) [1:N] -->
  <path d="M 380 720 L 420 720 L 420 1490 L 1640 1490 L 1640 430 L 1720 430" fill="none" stroke="#e11d48" stroke-width="1.8" stroke-dasharray="6,3" marker-start="url(#one-mandatory-rose)" marker-end="url(#cf-many-rose)" />

  <!-- Line 10: buku (x=1490, y=340) -> buku_likes (x=1720, y=470) [1:N] -->
  <path d="M 1490 340 L 1600 340 L 1600 470 L 1720 470" fill="none" stroke="#e11d48" stroke-width="1.8" marker-start="url(#one-mandatory-rose)" marker-end="url(#cf-many-rose)" />

  <!-- Line 11: users (x=380, y=780) -> buku_ratings (x=1720, y=670) [1:N] -->
  <path d="M 380 780 L 400 780 L 400 1520 L 1660 1520 L 1660 670 L 1720 670" fill="none" stroke="#d97706" stroke-width="1.8" stroke-dasharray="6,3" marker-start="url(#one-mandatory-amber)" marker-end="url(#cf-many-amber)" />

  <!-- Line 12: buku (x=1490, y=460) -> buku_ratings (x=1720, y=710) [1:N] -->
  <path d="M 1490 460 L 1580 460 L 1580 710 L 1720 710" fill="none" stroke="#d97706" stroke-width="1.8" marker-start="url(#one-mandatory-amber)" marker-end="url(#cf-many-amber)" />
</svg>

<!-- Visual Relationship Labels -->
<div class="rel-label" style="left: 215px; top: 300px; color:#4338ca;">1:1 (user_id)</div>
<div class="rel-label" style="left: 495px; top: 235px; color:#1e40af;">1:N (user_id)</div>
<div class="rel-label" style="left: 495px; top: 685px; color:#1e40af;">1:N (user_id)</div>
<div class="rel-label" style="left: 1025px; top: 265px; color:#1e40af;">1:N (buku_id)</div>
<div class="rel-label" style="left: 1010px; top: 735px; color:#1e40af;">1:N (buku_id)</div>
<div class="rel-label" style="left: 785px; top: 1055px; color:#1e40af;">1:N (peminjaman_id)</div>
<div class="rel-label" style="left: 1540px; top: 215px; color:#0369a1;">1:N (buku_id)</div>
<div class="rel-label" style="left: 1540px; top: 415px; color:#be123c;">1:N (buku_id)</div>
<div class="rel-label" style="left: 1540px; top: 655px; color:#b45309;">1:N (buku_id)</div>

<!-- ========================================== -->
<!-- ENTITY CARDS -->
<!-- ========================================== -->

<!-- 1. admin_profile (Column 1, Top) -->
<div class="entity-card" style="left: 50px; top: 115px; width: 330px;">
  <div class="entity-header indigo">
    <span>admin_profile</span>
    <span class="header-tag">1:1 Akun Admin</span>
  </div>
  <table class="entity-table">
    <tr>
      <td class="col-name is-pk">id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-pk">PK</span></td>
    </tr>
    <tr>
      <td class="col-name is-fk">user_id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-fk">FK</span> <span class="pill pill-uk">UK</span></td>
    </tr>
    <tr>
      <td class="col-name">display_name</td>
      <td class="col-type">varchar(100)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">foto</td>
      <td class="col-type">varchar(255)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">updated_at</td>
      <td class="col-type">timestamp</td>
      <td class="col-key"></td>
    </tr>
  </table>
  <div class="card-footer">FK: user_id -> users(id) ON DELETE CASCADE</div>
</div>

<!-- 2. users (Column 1, Center) -->
<div class="entity-card" style="left: 50px; top: 340px; width: 330px;">
  <div class="entity-header blue">
    <span>users</span>
    <span class="header-tag">Master Autentikasi & Akun</span>
  </div>
  <table class="entity-table">
    <tr>
      <td class="col-name is-pk">id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-pk">PK</span></td>
    </tr>
    <tr>
      <td class="col-name">full_name</td>
      <td class="col-type">varchar(100)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">nik</td>
      <td class="col-type">varchar(30)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">kelas</td>
      <td class="col-type">varchar(50)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">no_hp</td>
      <td class="col-type">varchar(20)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">no_anggota</td>
      <td class="col-type">varchar(30)</td>
      <td class="col-key"><span class="pill pill-uk">UK</span></td>
    </tr>
    <tr>
      <td class="col-name">username</td>
      <td class="col-type">varchar(50)</td>
      <td class="col-key"><span class="pill pill-uk">UK</span></td>
    </tr>
    <tr>
      <td class="col-name">email</td>
      <td class="col-type">varchar(100)</td>
      <td class="col-key"><span class="pill pill-uk">UK</span></td>
    </tr>
    <tr>
      <td class="col-name">password</td>
      <td class="col-type">varchar(255)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">foto</td>
      <td class="col-type">varchar(255)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">role</td>
      <td class="col-type">enum('admin','member')</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">status</td>
      <td class="col-type">enum('pending','approved','rejected')</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">card_status</td>
      <td class="col-type">enum('active','frozen')</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">created_at</td>
      <td class="col-type">timestamp</td>
      <td class="col-key"></td>
    </tr>
  </table>
  <div class="card-footer">UK: no_anggota, username, email. Sandi di-hash Bcrypt</div>
</div>

<!-- 3. kelas (Column 1, Bottom) -->
<div class="entity-card" style="left: 50px; top: 880px; width: 330px;">
  <div class="entity-header slate">
    <span>kelas</span>
    <span class="header-tag">Master Referensi Rombel</span>
  </div>
  <table class="entity-table">
    <tr>
      <td class="col-name is-pk">id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-pk">PK</span></td>
    </tr>
    <tr>
      <td class="col-name">nama_kelas</td>
      <td class="col-type">varchar(50)</td>
      <td class="col-key"><span class="pill pill-uk">UK</span></td>
    </tr>
    <tr>
      <td class="col-name">created_at</td>
      <td class="col-type">timestamp</td>
      <td class="col-key"></td>
    </tr>
  </table>
  <div class="card-footer">Master data rombongan belajar (XI RPL 1, XII RPL 1, dll)</div>
</div>

<!-- 4. pengajuan_peminjaman (Column 2, Top) -->
<div class="entity-card" style="left: 590px; top: 115px; width: 380px;">
  <div class="entity-header amber">
    <span>pengajuan_peminjaman</span>
    <span class="header-tag">Antrean Pinjam Online (Maks 40)</span>
  </div>
  <table class="entity-table">
    <tr>
      <td class="col-name is-pk">id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-pk">PK</span></td>
    </tr>
    <tr>
      <td class="col-name is-fk">user_id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-fk">FK</span></td>
    </tr>
    <tr>
      <td class="col-name is-fk">buku_id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-fk">FK</span></td>
    </tr>
    <tr>
      <td class="col-name">nama_peminjam</td>
      <td class="col-type">varchar(150)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">file_kartu</td>
      <td class="col-type">varchar(255)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">total_buku</td>
      <td class="col-type">int (default 1, max 40)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">batas_kembali</td>
      <td class="col-type">date</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">waktu_pengambilan</td>
      <td class="col-type">enum('sekarang','nanti')</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">catatan_pengambilan</td>
      <td class="col-type">varchar(255)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">status</td>
      <td class="col-type">enum('menunggu','disetujui','ditolak')</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">alasan_penolakan</td>
      <td class="col-type">text</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">approved_at</td>
      <td class="col-type">datetime</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">created_at</td>
      <td class="col-type">timestamp</td>
      <td class="col-key"></td>
    </tr>
  </table>
  <div class="card-footer">Disetujui admin -> ditransfer otomatis ke tabel peminjaman fisik</div>
</div>

<!-- 5. peminjaman (Column 2, Center) -->
<div class="entity-card" style="left: 590px; top: 580px; width: 380px;">
  <div class="entity-header emerald">
    <span>peminjaman</span>
    <span class="header-tag">Sirkulasi Fisik, Jatuh Tempo & Denda</span>
  </div>
  <table class="entity-table">
    <tr>
      <td class="col-name is-pk">id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-pk">PK</span></td>
    </tr>
    <tr>
      <td class="col-name is-fk">buku_id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-fk">FK</span></td>
    </tr>
    <tr>
      <td class="col-name is-fk">user_id</td>
      <td class="col-type">int (nullable)</td>
      <td class="col-key"><span class="pill pill-fk">FK</span></td>
    </tr>
    <tr>
      <td class="col-name">nama_peminjam</td>
      <td class="col-type">varchar(150)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">waktu_pinjam</td>
      <td class="col-type">datetime</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">batas_kembali</td>
      <td class="col-type">datetime</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">waktu_kembali</td>
      <td class="col-type">datetime (nullable)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">terlambat_hari</td>
      <td class="col-type">int (default 0)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">denda</td>
      <td class="col-type">decimal(12,0) (default 0)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">status_denda</td>
      <td class="col-type">enum('tidak_ada','belum_bayar','lunas')</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">status</td>
      <td class="col-type">enum('dipinjam','dikembalikan')</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">created_at</td>
      <td class="col-type">timestamp</td>
      <td class="col-key"></td>
    </tr>
  </table>
  <div class="card-footer">FK: buku_id ON DELETE RESTRICT | user_id ON DELETE SET NULL</div>
</div>

<!-- 6. reminder_log (Column 2, Bottom) -->
<div class="entity-card" style="left: 590px; top: 1100px; width: 380px;">
  <div class="entity-header slate">
    <span>reminder_log</span>
    <span class="header-tag">Audit WhatsApp Reminder Denda</span>
  </div>
  <table class="entity-table">
    <tr>
      <td class="col-name is-pk">id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-pk">PK</span></td>
    </tr>
    <tr>
      <td class="col-name is-fk">peminjaman_id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-fk">FK</span> <span class="pill pill-uk">UK</span></td>
    </tr>
    <tr>
      <td class="col-name">tanggal</td>
      <td class="col-type">date</td>
      <td class="col-key"><span class="pill pill-uk">UK</span></td>
    </tr>
    <tr>
      <td class="col-name">terlambat_hari</td>
      <td class="col-type">int</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">wa_terkirim</td>
      <td class="col-type">tinyint(1)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">wa_terkirim_at</td>
      <td class="col-type">datetime</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">created_at</td>
      <td class="col-type">timestamp</td>
      <td class="col-key"></td>
    </tr>
  </table>
  <div class="card-footer">UK(peminjaman_id, tanggal) -> Anti-spam 1 WA pengingat per hari</div>
</div>

<!-- 7. buku (Column 3, Top) -->
<div class="entity-card" style="left: 1140px; top: 115px; width: 350px;">
  <div class="entity-header blue">
    <span>buku</span>
    <span class="header-tag">Master Katalog Koleksi</span>
  </div>
  <table class="entity-table">
    <tr>
      <td class="col-name is-pk">id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-pk">PK</span></td>
    </tr>
    <tr>
      <td class="col-name">judul</td>
      <td class="col-type">varchar(255)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">penulis</td>
      <td class="col-type">varchar(255)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">isbn</td>
      <td class="col-type">varchar(50)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">genre</td>
      <td class="col-type">varchar(100)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">sinopsis</td>
      <td class="col-type">text</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">stok</td>
      <td class="col-type">int (stok fisik atomik)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">rak</td>
      <td class="col-type">varchar(100) (lokasi rak fisik)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">gambar</td>
      <td class="col-type">varchar(500)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">created_at</td>
      <td class="col-type">timestamp</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">updated_at</td>
      <td class="col-type">timestamp</td>
      <td class="col-key"></td>
    </tr>
  </table>
  <div class="card-footer">Stok berkurang saat peminjaman aktif & bertambah saat dikembalikan</div>
</div>

<!-- 8. banner (Column 3, Middle) -->
<div class="entity-card" style="left: 1140px; top: 620px; width: 350px;">
  <div class="entity-header slate">
    <span>banner</span>
    <span class="header-tag">Slider Promo Hero Carousel</span>
  </div>
  <table class="entity-table">
    <tr>
      <td class="col-name is-pk">id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-pk">PK</span></td>
    </tr>
    <tr>
      <td class="col-name">judul</td>
      <td class="col-type">varchar(150)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">subjudul</td>
      <td class="col-type">varchar(255)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">gambar</td>
      <td class="col-type">varchar(500) (JPG/GIF/MP4)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">link_url</td>
      <td class="col-type">varchar(255)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">urutan</td>
      <td class="col-type">int</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">aktif</td>
      <td class="col-type">tinyint(1)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">created_at</td>
      <td class="col-type">timestamp</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">updated_at</td>
      <td class="col-type">timestamp</td>
      <td class="col-key"></td>
    </tr>
  </table>
  <div class="card-footer">Carousel homepage: mendukung media gambar statis, GIF & video MP4</div>
</div>

<!-- 9. pengaturan (Column 3, Bottom) -->
<div class="entity-card" style="left: 1140px; top: 1100px; width: 350px;">
  <div class="entity-header slate">
    <span>pengaturan</span>
    <span class="header-tag">Key-Value Konfigurasi Sistem</span>
  </div>
  <table class="entity-table">
    <tr>
      <td class="col-name is-pk">id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-pk">PK</span></td>
    </tr>
    <tr>
      <td class="col-name">kunci</td>
      <td class="col-type">varchar(100)</td>
      <td class="col-key"><span class="pill pill-uk">UK</span></td>
    </tr>
    <tr>
      <td class="col-name">nilai</td>
      <td class="col-type">varchar(500)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">keterangan</td>
      <td class="col-type">varchar(255)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">updated_at</td>
      <td class="col-type">timestamp</td>
      <td class="col-key"></td>
    </tr>
  </table>
  <div class="card-footer">Tarif denda/hari, batas toleransi hari, background audio, info kontak</div>
</div>

<!-- 10. buku_favorites (Column 4, Top) -->
<div class="entity-card" style="left: 1720px; top: 115px; width: 330px;">
  <div class="entity-header purple">
    <span>buku_favorites</span>
    <span class="header-tag">Bookmark Koleksi Anggota</span>
  </div>
  <table class="entity-table">
    <tr>
      <td class="col-name is-pk">id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-pk">PK</span></td>
    </tr>
    <tr>
      <td class="col-name is-fk">buku_id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-fk">FK</span> <span class="pill pill-uk">UK</span></td>
    </tr>
    <tr>
      <td class="col-name is-fk">user_id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-fk">FK</span> <span class="pill pill-uk">UK</span></td>
    </tr>
    <tr>
      <td class="col-name">created_at</td>
      <td class="col-type">timestamp</td>
      <td class="col-key"></td>
    </tr>
  </table>
  <div class="card-footer">UK(buku_id, user_id). Cascade on Delete</div>
</div>

<!-- 11. buku_likes (Column 4, Middle) -->
<div class="entity-card" style="left: 1720px; top: 340px; width: 330px;">
  <div class="entity-header purple">
    <span>buku_likes</span>
    <span class="header-tag">Apresiasi & Like Buku</span>
  </div>
  <table class="entity-table">
    <tr>
      <td class="col-name is-pk">id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-pk">PK</span></td>
    </tr>
    <tr>
      <td class="col-name is-fk">buku_id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-fk">FK</span> <span class="pill pill-uk">UK</span></td>
    </tr>
    <tr>
      <td class="col-name is-fk">user_id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-fk">FK</span> <span class="pill pill-uk">UK</span></td>
    </tr>
    <tr>
      <td class="col-name">created_at</td>
      <td class="col-type">timestamp</td>
      <td class="col-key"></td>
    </tr>
  </table>
  <div class="card-footer">UK(buku_id, user_id). Cascade on Delete</div>
</div>

<!-- 12. buku_ratings (Column 4, Bottom) -->
<div class="entity-card" style="left: 1720px; top: 565px; width: 330px;">
  <div class="entity-header purple">
    <span>buku_ratings</span>
    <span class="header-tag">Rating & Review Anggota</span>
  </div>
  <table class="entity-table">
    <tr>
      <td class="col-name is-pk">id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-pk">PK</span></td>
    </tr>
    <tr>
      <td class="col-name is-fk">user_id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-fk">FK</span> <span class="pill pill-uk">UK</span></td>
    </tr>
    <tr>
      <td class="col-name is-fk">buku_id</td>
      <td class="col-type">int</td>
      <td class="col-key"><span class="pill pill-fk">FK</span> <span class="pill pill-uk">UK</span></td>
    </tr>
    <tr>
      <td class="col-name">rating</td>
      <td class="col-type">tinyint (skala 1 - 5)</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">created_at</td>
      <td class="col-type">datetime</td>
      <td class="col-key"></td>
    </tr>
    <tr>
      <td class="col-name">updated_at</td>
      <td class="col-type">datetime</td>
      <td class="col-key"></td>
    </tr>
  </table>
  <div class="card-footer">UK(user_id, buku_id). Admin dicegah memberi review</div>
</div>

</body>
</html>
"""
    with open("ERD/erd_interactive.html", "w", encoding="utf-8") as f:
        f.write(html)
    print("Generated ERD/erd_interactive.html")
    return os.path.abspath("ERD/erd_interactive.html")


def render_html(html_abs):
    browser = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
    if not os.path.exists(browser):
        browser = r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"

    out_png = os.path.abspath("ERD/erd_diagram.png")
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
    shutil.copy2(out_png, "docs/erd_diagram.png")
    print(f"Rendered {out_png} and copied to docs/erd_diagram.png")
    im = Image.open(out_png)
    print("Image size:", im.size)


if __name__ == '__main__':
    build_mermaid_file()
    html_path = build_html_diagram()
    render_html(html_path)
