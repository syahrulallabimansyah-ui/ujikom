import re

with open('docs/LAPORAN_UKK_AKSA_NOVA.md', 'r', encoding='utf-8') as f:
    text = f.read()

# 1. Replace Use Case Mermaid block if present
uc_mermaid_pat = r'```mermaid\s+flowchart TD.*?UC_A5_WA -\.->\|<<extend>>\| UC_A5_DENDA\s+```'
uc_img = '![Gambar 4.1: Diagram Use Case Sistem Informasi Perpustakaan AKSA NOVA](../UML/Usecase/Usecase-v2.png)\n*Gambar 4.1: Diagram Use Case Sistem Informasi Perpustakaan AKSA NOVA*'
text, count1 = re.subn(uc_mermaid_pat, uc_img, text, flags=re.DOTALL)
print('Use case replaced:', count1)

# 2. Replace Class Diagram Mermaid block if present
class_mermaid_pat = r'```mermaid\s+classDiagram.*?Banner \.\.> Pengaturan : <<use>>\s+```'
class_img = '![Gambar 4.16: Class Diagram Sistem Informasi Perpustakaan AKSA NOVA](../UML/Class/17_class_diagram_aksa_nova.png)\n*Gambar 4.16: Class Diagram Sistem Informasi Perpustakaan AKSA NOVA*'
text, count2 = re.subn(class_mermaid_pat, class_img, text, flags=re.DOTALL)
print('Class diagram replaced:', count2)

# 3. Replace ERD Mermaid block if present
erd_mermaid_pat = r'```mermaid\s+erDiagram.*?PEMINJAMAN \|\|--o\{ REMINDER_LOG : \"mencatat log wa\"\s+```'
erd_img = '![Gambar 4.17: Entity Relationship Diagram (ERD) Basis Data AKSA NOVA](erd_diagram.png)\n*Gambar 4.17: Entity Relationship Diagram (ERD) Basis Data AKSA NOVA*'
text, count3 = re.subn(erd_mermaid_pat, erd_img, text, flags=re.DOTALL)
print('ERD diagram replaced:', count3)

# 4. Insert image references into Activity Diagrams
act_subs = [
    ('1. **Registrasi Anggota (`01_activity_registrasi_anggota.png`, `sign_up.php`)**:',
     '1. **Registrasi Anggota (`01_activity_registrasi_anggota.png`, `sign_up.php`)**:\n\n![Gambar 4.2: Activity Diagram Registrasi Anggota](../UML/Activty/01_activity_registrasi_anggota.png)\n*Gambar 4.2: Activity Diagram Registrasi Anggota*'),
    ('2. **Login Pengguna (`02_activity_login.png`, `sign_in.php`, `logout.php`)**:',
     '2. **Login Pengguna (`02_activity_login.png`, `sign_in.php`, `logout.php`)**:\n\n![Gambar 4.3: Activity Diagram Login Pengguna](../UML/Activty/02_activity_login.png)\n*Gambar 4.3: Activity Diagram Login Pengguna*'),
    ('3. **Pengajuan Peminjaman Buku Daring (`03_activity_pengajuan_peminjaman_anggota.png`, `pengajuan_peminjaman.php`)**:',
     '3. **Pengajuan Peminjaman Buku Daring (`03_activity_pengajuan_peminjaman_anggota.png`, `pengajuan_peminjaman.php`)**:\n\n![Gambar 4.4: Activity Diagram Pengajuan Peminjaman Buku](../UML/Activty/03_activity_pengajuan_peminjaman_anggota.png)\n*Gambar 4.4: Activity Diagram Pengajuan Peminjaman Buku*'),
    ('4. **Persetujuan Pengajuan Peminjaman (`04_activity_persetujuan_pengajuan_admin.png`, `pengajuan_buku.php`)**:',
     '4. **Persetujuan Pengajuan Peminjaman (`04_activity_persetujuan_pengajuan_admin.png`, `pengajuan_buku.php`)**:\n\n![Gambar 4.5: Activity Diagram Persetujuan Pengajuan Admin](../UML/Activty/04_activity_persetujuan_pengajuan_admin.png)\n*Gambar 4.5: Activity Diagram Persetujuan Pengajuan Admin*'),
    ('5. **Peminjaman Langsung di Meja Layanan (`05_activity_peminjaman_langsung.png`, `pinjam_buku.php`)**:',
     '5. **Peminjaman Langsung di Meja Layanan (`05_activity_peminjaman_langsung.png`, `pinjam_buku.php`)**:\n\n![Gambar 4.6: Activity Diagram Peminjaman Langsung di Meja Layanan](../UML/Activty/05_activity_peminjaman_langsung.png)\n*Gambar 4.6: Activity Diagram Peminjaman Langsung di Meja Layanan*'),
    ('6. **Pengembalian Buku dan Denda (`06_activity_pengembalian_denda.png`, `telah_dipinjam.php`)**:',
     '6. **Pengembalian Buku dan Denda (`06_activity_pengembalian_denda.png`, `telah_dipinjam.php`)**:\n\n![Gambar 4.7: Activity Diagram Pengembalian Buku dan Denda](../UML/Activty/06_activity_pengembalian_denda.png)\n*Gambar 4.7: Activity Diagram Pengembalian Buku dan Denda*'),
    ('7. **Tambah Buku dengan Pencarian ISBN (`07_activity_tambah_buku_isbn.png`, `halaman_admin.php`, `cari_isbn.php`)**:',
     '7. **Tambah Buku dengan Pencarian ISBN (`07_activity_tambah_buku_isbn.png`, `halaman_admin.php`, `cari_isbn.php`)**:\n\n![Gambar 4.8: Activity Diagram Tambah Buku dengan Pencarian ISBN](../UML/Activty/07_activity_tambah_buku_isbn.png)\n*Gambar 4.8: Activity Diagram Tambah Buku dengan Pencarian ISBN*')
]
for old_s, new_s in act_subs:
    if old_s in text and '../UML/Activty/' not in text[text.find(old_s):text.find(old_s)+300]:
        text = text.replace(old_s, new_s)
        print('Activity replaced:', old_s[:30])

# 5. Insert image references into Sequence Diagrams
seq_subs = [
    ('1. **Sequence Registrasi Anggota (`10_sequence_registrasi_anggota.png`)**:',
     '1. **Sequence Registrasi Anggota (`10_sequence_registrasi_anggota.png`)**:\n\n![Gambar 4.9: Sequence Diagram Registrasi Anggota](../UML/Squence/10_sequence_registrasi_anggota.png)\n*Gambar 4.9: Sequence Diagram Registrasi Anggota*'),
    ('2. **Sequence Login Pengguna (`11_sequence_login.png`)**:',
     '2. **Sequence Login Pengguna (`11_sequence_login.png`)**:\n\n![Gambar 4.10: Sequence Diagram Login Pengguna](../UML/Squence/11_sequence_login.png)\n*Gambar 4.10: Sequence Diagram Login Pengguna*'),
    ('3. **Sequence Pengajuan Peminjaman (`12_sequence_pengajuan_peminjaman.png`)**:',
     '3. **Sequence Pengajuan Peminjaman (`12_sequence_pengajuan_peminjaman.png`)**:\n\n![Gambar 4.11: Sequence Diagram Pengajuan Peminjaman](../UML/Squence/12_sequence_pengajuan_peminjaman.png)\n*Gambar 4.11: Sequence Diagram Pengajuan Peminjaman*'),
    ('4. **Sequence Persetujuan Pengajuan oleh Admin (`13_sequence_persetujuan_pengajuan.png`)**:',
     '4. **Sequence Persetujuan Pengajuan oleh Admin (`13_sequence_persetujuan_pengajuan.png`)**:\n\n![Gambar 4.12: Sequence Diagram Persetujuan Pengajuan](../UML/Squence/13_sequence_persetujuan_pengajuan.png)\n*Gambar 4.12: Sequence Diagram Persetujuan Pengajuan*'),
    ('5. **Sequence Peminjaman Langsung di Tempat (`14_sequence_peminjaman_langsung.png`)**:',
     '5. **Sequence Peminjaman Langsung di Tempat (`14_sequence_peminjaman_langsung.png`)**:\n\n![Gambar 4.13: Sequence Diagram Peminjaman Langsung](../UML/Squence/14_sequence_peminjaman_langsung.png)\n*Gambar 4.13: Sequence Diagram Peminjaman Langsung*'),
    ('6. **Sequence Pengembalian Buku dan Denda (`15_sequence_pengembalian_denda.png`)**:',
     '6. **Sequence Pengembalian Buku dan Denda (`15_sequence_pengembalian_denda.png`)**:\n\n![Gambar 4.14: Sequence Diagram Pengembalian Buku dan Denda](../UML/Squence/15_sequence_pengembalian_denda.png)\n*Gambar 4.14: Sequence Diagram Pengembalian Buku dan Denda*'),
    ('7. **Sequence Tambah Buku dengan ISBN (`16_sequence_tambah_buku_isbn.png`)**:',
     '7. **Sequence Tambah Buku dengan ISBN (`16_sequence_tambah_buku_isbn.png`)**:\n\n![Gambar 4.15: Sequence Diagram Tambah Buku dengan ISBN](../UML/Squence/16_sequence_tambah_buku_isbn.png)\n*Gambar 4.15: Sequence Diagram Tambah Buku dengan ISBN*')
]
for old_s, new_s in seq_subs:
    if old_s in text and '../UML/Squence/' not in text[text.find(old_s):text.find(old_s)+300]:
        text = text.replace(old_s, new_s)
        print('Sequence replaced:', old_s[:30])

with open('docs/LAPORAN_UKK_AKSA_NOVA.md', 'w', encoding='utf-8') as f:
    f.write(text)

print('Finished updating docs/LAPORAN_UKK_AKSA_NOVA.md!')
