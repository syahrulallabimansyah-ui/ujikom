# -*- coding: utf-8 -*-
"""
Script to generate updated UML diagrams with Teacher (Guru) support,
expanded email domains, and 40-book quota.
Copies fresh from backup_uml/ and applies pixel-perfect text updates to UML/.
Ensures clean file names without confusing version suffixes (v1, v2).
"""
import os
import shutil
from PIL import Image, ImageDraw, ImageFont

def ensure_backup():
    print("Checking backup_uml/ directory...")
    if not os.path.exists("backup_uml"):
        shutil.copytree("UML", "backup_uml")
        print("Created backup_uml/ from UML/")
    else:
        file_count = sum(len(files) for _, _, files in os.walk("backup_uml"))
        print(f"backup_uml/ already exists with {file_count} files safely preserved.")

def update_usecase_diagrams():
    font_bold = ImageFont.truetype(r'C:\Windows\Fonts\arialbd.ttf', 20)
    src_path = os.path.join('backup_uml', 'Usecase', 'Usecase-v2.png')
    if not os.path.exists(src_path):
        src_path = os.path.join('backup_uml', 'Usecase', 'usecase-v1.png')
    
    with Image.open(src_path) as src:
        im = src.convert('RGB')
    draw = ImageDraw.Draw(im)
    
    # Clear (Siswa) area at x=50..280, y=1230..1255
    draw.rectangle([50, 1230, 280, 1255], fill=(255, 255, 255))
    
    # Draw (Siswa & Guru) centered at x=164
    text = "(Siswa & Guru)"
    bbox = font_bold.getbbox(text)
    tw = bbox[2] - bbox[0]
    x = int(164 - tw / 2)
    draw.text((x, 1232), text, font=font_bold, fill=(0, 0, 0))
    
    # Save as definitive fixed names
    dest_path1 = os.path.join('UML', 'Usecase', 'usecase_diagram.png')
    dest_path2 = os.path.join('UML', 'Usecase', 'usecase.png')
    im.save(dest_path1)
    im.save(dest_path2)
    print(f"Saved updated {dest_path1} and {dest_path2}")

def update_activity_01():
    rel_path = 'Activty/01_activity_registrasi_anggota.png'
    src_path = os.path.join('backup_uml', rel_path)
    dest_path = os.path.join('UML', rel_path)
    print(f"Updating {dest_path}...")
    with Image.open(src_path) as src:
        im = src.convert('RGB')
    draw = ImageDraw.Draw(im)
    font = ImageFont.truetype(r'C:\Windows\Fonts\arial.ttf', 23)
    bg_yellow = (255, 244, 194)
    text_color = (0, 0, 0)
    
    # 1. Box 3 (isi data): x=128..426, y=765..875. Center x = 277
    draw.rectangle([128, 765, 426, 873], fill=bg_yellow)
    lines3 = [
        'Mengisi data Siswa/Guru',
        '(NIS/NIP, kelas, no. HP,',
        'email, kata sandi) &',
        'mengunggah foto profil'
    ]
    y = 771
    for line in lines3:
        bbox = font.getbbox(line)
        w = bbox[2] - bbox[0]
        x = int(277 - w / 2)
        draw.text((x, y), line, font=font, fill=text_color)
        y += 26

    # 2. Box 4 (validasi): x=956..1264, y=965..1050. Center x = 1110
    draw.rectangle([956, 965, 1264, 1048], fill=bg_yellow)
    lines4 = [
        'Memvalidasi input, format',
        'foto, serta email & NIS/NIP',
        'belum terdaftar'
    ]
    y = 972
    for line in lines4:
        bbox = font.getbbox(line)
        w = bbox[2] - bbox[0]
        x = int(1110 - w / 2)
        draw.text((x, y), line, font=font, fill=text_color)
        y += 26

    im.save(dest_path)
    print(f"Saved updated {dest_path}")

def update_activity_03():
    rel_path = 'Activty/03_activity_pengajuan_peminjaman_anggota.png'
    src_path = os.path.join('backup_uml', rel_path)
    dest_path = os.path.join('UML', rel_path)
    print(f"Updating {dest_path}...")
    with Image.open(src_path) as src:
        im = src.convert('RGB')
    draw = ImageDraw.Draw(im)
    font = ImageFont.truetype(r'C:\Windows\Fonts\arial.ttf', 23)
    bg_yellow = (255, 244, 194)
    text_color = (0, 0, 0)
    
    # 1. Box 3: x=128..426, y=755..885. Center x = 277
    draw.rectangle([128, 755, 426, 883], fill=bg_yellow)
    lines3 = [
        'Mengisi jumlah buku (1-40),',
        'batas kembali, waktu ambil',
        '(sekarang / nanti) &',
        'mengunggah foto kartu',
        'anggota'
    ]
    y = 762
    for line in lines3:
        bbox = font.getbbox(line)
        w = bbox[2] - bbox[0]
        x = int(277 - w / 2)
        draw.text((x, y), line, font=font, fill=text_color)
        y += 25

    # 2. Box 4: x=956..1264, y=965..1050. Center x = 1110
    draw.rectangle([956, 965, 1264, 1048], fill=bg_yellow)
    lines4 = [
        'Memvalidasi stok, kuota',
        '1 s/d 40 buku, tanggal &',
        'berkas gambar (maks. 5 MB)'
    ]
    y = 972
    for line in lines4:
        bbox = font.getbbox(line)
        w = bbox[2] - bbox[0]
        x = int(1110 - w / 2)
        draw.text((x, y), line, font=font, fill=text_color)
        y += 26

    im.save(dest_path)
    print(f"Saved updated {dest_path}")

def update_sequence_10():
    rel_path = 'Squence/10_sequence_registrasi_anggota.png'
    src_path = os.path.join('backup_uml', rel_path)
    dest_path = os.path.join('UML', rel_path)
    print(f"Updating {dest_path}...")
    with Image.open(src_path) as src:
        im = src.convert('RGB')
    draw = ImageDraw.Draw(im)
    font = ImageFont.truetype(r'C:\Windows\Fonts\arial.ttf', 18)
    bg_white = (255, 255, 255)
    text_color = (0, 0, 0)
    
    # Message 1 text area: x=220..560, y=425..473 (between lifelines, above arrow at 486)
    draw.rectangle([220, 425, 560, 473], fill=bg_white)
    
    line1 = '1: Mengisi formulir Siswa/Guru (NIS/NIP,'
    line2 = 'nama, email, kelas, sandi) klik Daftar'
    
    w1 = font.getbbox(line1)[2] - font.getbbox(line1)[0]
    w2 = font.getbbox(line2)[2] - font.getbbox(line2)[0]
    
    # Center between lifelines (x=218 and x=562, midpoint 390)
    x1 = int(390 - w1 / 2)
    x2 = int(390 - w2 / 2)
    
    draw.text((x1, 431), line1, font=font, fill=text_color)
    draw.text((x2, 457), line2, font=font, fill=text_color)

    im.save(dest_path)
    print(f"Saved updated {dest_path}")

def update_sequence_12():
    rel_path = 'Squence/12_sequence_pengajuan_peminjaman.png'
    src_path = os.path.join('backup_uml', rel_path)
    dest_path = os.path.join('UML', rel_path)
    print(f"Updating {dest_path}...")
    with Image.open(src_path) as src:
        im = src.convert('RGB')
    draw = ImageDraw.Draw(im)
    font = ImageFont.truetype(r'C:\Windows\Fonts\arial.ttf', 18)
    bg_white = (255, 255, 255)
    text_color = (0, 0, 0)
    
    # 1. Message 1: Replace line 1 "(maks. 3)" with "(maks. 40)"
    draw.rectangle([250, 430, 550, 452], fill=bg_white)
    draw.text((254, 431), '1: Memilih buku (maks. 40), tanggal', font=font, fill=text_color)

    # 2. Message 3: Replace "3," with "40," in "jumlah buku ≤ 3,"
    draw.rectangle([1244, 650, 1275, 674], fill=bg_white)
    draw.text((1246, 653), '40,', font=font, fill=text_color)

    im.save(dest_path)
    print(f"Saved updated {dest_path}")

def update_class_diagram():
    src_path = os.path.join('backup_uml', 'Class', '17_class_diagram_aksa_nova.png')
    dest_path1 = os.path.join('UML', 'Class', '17_class_diagram_aksa_nova.png')
    dest_path2 = os.path.join('UML', 'Class', 'class_diagram.png')
    print(f"Updating {dest_path1} and {dest_path2}...")
    with Image.open(src_path) as src:
        im = src.convert('RGB')
    draw = ImageDraw.Draw(im)
    
    # 1. In Pengguna box:
    # -nik: String -> -nik: String (NIS/NIP)
    # -kelas: String -> -kelas: String (Rombel/Guru)
    font_attr = ImageFont.truetype(r'C:\Windows\Fonts\arial.ttf', 18)
    bg_pengguna = (255, 244, 194)
    draw.rectangle([1533, 295, 1860, 352], fill=bg_pengguna)
    draw.text((1546, 299), '-nik: String (NIS/NIP)', font=font_attr, fill=(0, 0, 0))
    draw.text((1546, 329), '-kelas: String (Rombel/Guru)', font=font_attr, fill=(0, 0, 0))
    
    # 2. In Anggota box:
    # Header: Anggota -> Anggota (Siswa & Guru)
    font_hdr = ImageFont.truetype(r'C:\Windows\Fonts\arialbd.ttf', 35)
    bg_hdr = (255, 233, 163)
    draw.rectangle([2005, 965, 2515, 1010], fill=bg_hdr)
    text_hdr = "Anggota (Siswa & Guru)"
    bbox = font_hdr.getbbox(text_hdr)
    tw = bbox[2] - bbox[0]
    hx = int(2260 - tw / 2)
    draw.text((hx, 968), text_hdr, font=font_hdr, fill=(0, 0, 0))
    
    im.save(dest_path1)
    im.save(dest_path2)
    print(f"Saved updated {dest_path1} and {dest_path2}")

if __name__ == '__main__':
    ensure_backup()
    update_usecase_diagrams()
    update_activity_01()
    update_activity_03()
    update_sequence_10()
    update_sequence_12()
    update_class_diagram()
    print("\nAll target UML diagrams successfully updated and synced!")
