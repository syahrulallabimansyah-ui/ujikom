# -*- coding: utf-8 -*-
import os

files = [
    'halaman_admin.php',
    'dashboard.php',
    'daftar_anggota.php',
    'kelola_banner.php',
    'pengaturan_denda.php',
    'pengaturan_musik.php',
    'pinjam_buku.php',
    'telah_dipinjam.php',
    'pengajuan_buku.php'
]

old_span = '<span style="margin-left:auto;background:rgba(245,158,11,.2);color:#fbbf24;border:1px solid rgba(245,158,11,.4);font-size:.65rem;font-weight:800;padding:2px 7px;border-radius:20px;"><?= $cnt_aju_badge ?></span>'
new_span = '<span class="aksa-notif-badge" style="margin-left:auto;background:rgba(245,158,11,.2);color:#fbbf24;border:1px solid rgba(245,158,11,.4);font-size:.65rem;font-weight:800;padding:2px 7px;border-radius:20px;"><?= $cnt_aju_badge ?></span>'

for f in files:
    if not os.path.exists(f):
        continue
    with open(f, 'r', encoding='utf-8') as fp:
        content = fp.read()
    
    count = content.count(old_span)
    print(f'{f}: {count} occurrences of old_span found')
    if count > 0:
        content = content.replace(old_span, new_span)
        with open(f, 'w', encoding='utf-8') as fp:
            fp.write(content)
        print(f'  -> Replaced in {f}')
