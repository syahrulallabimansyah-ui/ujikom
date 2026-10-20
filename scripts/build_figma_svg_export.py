# -*- coding: utf-8 -*-
"""
Generate Figma-compatible SVG Artboard Canvas Export
Can be directly imported / dragged into Figma (Web or Desktop)
"""

import os

SCREENS_USER = [
    ("Frame 01 - Beranda", "screens/screen_01_beranda.png"),
    ("Frame 02 - Sign In", "screens/screen_02_sign_in.png"),
    ("Frame 03 - Sign Up", "screens/screen_03_sign_up.png"),
    ("Frame 04 - Dashboard Siswa", "screens/screen_04_dashboard_user.png"),
    ("Frame 05 - Katalog Buku", "screens/screen_05_daftar_buku.png"),
    ("Frame 05b - Modal Detail Buku", "screens/screen_modal_detail_buku.png"),
    ("Frame 06 - Form Pengajuan 40 Buku", "screens/screen_06_pengajuan_peminjaman.png"),
    ("Frame 07 - Kartu Anggota Digital", "screens/screen_07_kartu_anggota.png"),
    ("Frame 08 - Pemulihan Lupa Kartu", "screens/screen_08_lupa_kartu.png")
]

SCREENS_ADMIN = [
    ("Frame 09 - Dashboard Admin", "screens/screen_09_dashboard_admin.png"),
    ("Frame 10 - Kelola Buku ISBN", "screens/screen_10_halaman_admin.png"),
    ("Frame 10b - Modal Tambah Buku", "screens/screen_modal_tambah_buku.png"),
    ("Frame 11 - Kelola Anggota", "screens/screen_11_daftar_anggota.png"),
    ("Frame 12 - Verifikasi Pengajuan", "screens/screen_12_pengajuan_buku.png"),
    ("Frame 13 - Sirkulasi Denda WA", "screens/screen_13_telah_dipinjam.png"),
    ("Frame 14 - Pinjam Langsung", "screens/screen_14_pinjam_buku.png"),
    ("Frame 15 - Kelola Banner", "screens/screen_15_kelola_banner.png"),
    ("Frame 16 - Pengaturan Denda", "screens/screen_16_pengaturan_denda.png"),
    ("Frame 17 - Pengaturan Musik", "screens/screen_17_pengaturan_musik.png")
]

def generate_svg():
    frame_w = 1440
    frame_h = 900
    gap_x = 100
    gap_y = 200

    cols = max(len(SCREENS_USER), len(SCREENS_ADMIN))
    total_w = cols * (frame_w + gap_x) + 200
    total_h = 2 * (frame_h + gap_y) + 400

    svg_parts = [
        f'<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="{total_w}" height="{total_h}" viewBox="0 0 {total_w} {total_h}">',
        f'<rect width="{total_w}" height="{total_h}" fill="#090c10"/>',
        '<style>',
        '  .title { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 32px; font-weight: 700; fill: #d8b878; }',
        '  .subtitle { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 18px; fill: #94a3b8; }',
        '  .frame-label { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 20px; font-weight: 600; fill: #eef3f4; }',
        '</style>',
        '<text x="80" y="80" class="title">AKSA NOVA — High-Fidelity Figma Mockups & Artboards</text>',
        '<text x="80" y="115" class="subtitle">Sistem Informasi Perpustakaan Digital SMK Negeri 1 Rongga | Penyusun: Syahrul Bimansyah (@arul)</text>'
    ]

    # Row 1: User Flow
    y1 = 200
    svg_parts.append(f'<text x="80" y="{y1 - 30}" class="title" style="font-size:24px; fill:#38bdf8;">ALUR SISWA &amp; ANGGOTA PERPUSTAKAAN</text>')
    for idx, (title, img_path) in enumerate(SCREENS_USER):
        x = 80 + idx * (frame_w + gap_x)
        svg_parts.append(f'<g id="{title.replace(" ", "_")}">')
        svg_parts.append(f'  <text x="{x}" y="{y1 - 10}" class="frame-label">{title} (1440x900)</text>')
        svg_parts.append(f'  <rect x="{x}" y="{y1}" width="{frame_w}" height="{frame_h}" rx="8" fill="#121820" stroke="#d8b878" stroke-width="2"/>')
        svg_parts.append(f'  <image href="{img_path}" x="{x}" y="{y1}" width="{frame_w}" height="{frame_h}" preserveAspectRatio="xMidYMid slice"/>')
        svg_parts.append('</g>')

    # Row 2: Admin Flow
    y2 = y1 + frame_h + gap_y + 80
    svg_parts.append(f'<text x="80" y="{y2 - 30}" class="title" style="font-size:24px; fill:#a78bfa;">ALUR ADMINISTRATOR &amp; PUSTAKAWAN</text>')
    for idx, (title, img_path) in enumerate(SCREENS_ADMIN):
        x = 80 + idx * (frame_w + gap_x)
        svg_parts.append(f'<g id="{title.replace(" ", "_")}">')
        svg_parts.append(f'  <text x="{x}" y="{y2 - 10}" class="frame-label">{title} (1440x900)</text>')
        svg_parts.append(f'  <rect x="{x}" y="{y2}" width="{frame_w}" height="{frame_h}" rx="8" fill="#121820" stroke="#3b82f6" stroke-width="2"/>')
        svg_parts.append(f'  <image href="{img_path}" x="{x}" y="{y2}" width="{frame_w}" height="{frame_h}" preserveAspectRatio="xMidYMid slice"/>')
        svg_parts.append('</g>')

    svg_parts.append('</svg>')

    out_file = 'mockup_prototype/figma_artboards_export.svg'
    with open(out_file, 'w', encoding='utf-8') as f:
        f.write('\n'.join(svg_parts))
    print(f"Generated {out_file}")

if __name__ == '__main__':
    generate_svg()
