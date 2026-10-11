# -*- coding: utf-8 -*-
"""
Builder Script for Professional UKK Report of AKSA NOVA
Generates: docs/LAPORAN_UKK_AKSA_NOVA.docx
With:
- Official Word Heading Styles (Heading 1, 2, 3, 4) for active Navigation Pane & auto TOC
- Native XML Footer Page Numbers
- Automatic Page Breaks for Cover, Front Matter, and Chapters
- High Quality Embedded PNG Diagrams and UI Screenshots (23 total)
- Elegant Table Styling with dark navy headers and zebra shading
- Formatted Code blocks and clean Typography
"""

import os
import re
import docx
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import parse_xml, OxmlElement
from docx.oxml.ns import nsdecls, qn
from PIL import Image

def clean_latex(s):
    replacements = [
        (r'$\rightarrow$', '→'),
        (r'$\dashrightarrow$', '⇢'),
        (r'$\times$', '×'),
        (r'$\le 768px$', '≤ 768px'),
        (r'$\le 5$', '≤ 5'),
        (r'$\le 1$', '≤ 1'),
        (r'$\ge 360 \times 640$', '≥ 360 × 640'),
        (r'$\blacklozenge$', '◆'),
        (r'\ll\text{create}\gg', '«create»'),
        (r'\ll\text{use}\gg', '«use»'),
        (r'\ll\text{include}\gg', '«include»'),
        (r'\ll\text{extend}\gg', '«extend»'),
        (r'$\le$', '≤'),
        (r'$\ge$', '≥'),
        (r'\le', '≤'),
        (r'\ge', '≥'),
        (r'\times', '×'),
        (r'\rightarrow', '→'),
    ]
    for old, new in replacements:
        s = s.replace(old, new)
    return s

def set_cell_background(cell, fill_hex):
    tcPr = cell._element.get_or_add_tcPr()
    shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{fill_hex}"/>')
    tcPr.append(shd)

def set_cell_margins(cell, top=100, bottom=100, left=150, right=150):
    tcPr = cell._element.get_or_add_tcPr()
    tcMar = parse_xml(f'<w:tcMar {nsdecls("w")}><w:top w:w="{top}" w:type="dxa"/><w:bottom w:w="{bottom}" w:type="dxa"/><w:left w:w="{left}" w:type="dxa"/><w:right w:w="{right}" w:type="dxa"/></w:tcMar>')
    tcPr.append(tcMar)

def add_footer_page_number(doc):
    for section in doc.sections:
        footer = section.footer
        p = footer.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.RIGHT
        
        r1 = p.add_run("Halaman ")
        r1.font.name = "Calibri"
        r1.font.size = Pt(9)
        r1.font.color.rgb = RGBColor(120, 120, 120)

        fldSimple = OxmlElement('w:fldSimple')
        fldSimple.set(qn('w:instr'), 'PAGE')
        p._element.append(fldSimple)

        r2 = p.add_run(" | Laporan UKK PPLG - Perpustakaan Digital AKSA NOVA")
        r2.font.name = "Calibri"
        r2.font.size = Pt(9)
        r2.font.color.rgb = RGBColor(140, 140, 140)

def add_formatted_runs(p, text, default_font='Calibri', default_size=11, default_color=RGBColor(35, 35, 35)):
    tokens = re.split(r'(\*\*.*?\*\*|\*.*?\*|`.*?`)', text)
    for tok in tokens:
        if not tok:
            continue
        if tok.startswith('**') and tok.endswith('**'):
            run = p.add_run(tok[2:-2])
            run.bold = True
            run.font.name = default_font
            run.font.size = Pt(default_size)
            run.font.color.rgb = default_color
        elif tok.startswith('*') and tok.endswith('*'):
            run = p.add_run(tok[1:-1])
            run.italic = True
            run.font.name = default_font
            run.font.size = Pt(default_size)
            run.font.color.rgb = default_color
        elif tok.startswith('`') and tok.endswith('`'):
            run = p.add_run(tok[1:-1])
            run.font.name = 'Consolas'
            run.font.size = Pt(default_size * 0.9)
            run.font.color.rgb = RGBColor(180, 115, 30)
        else:
            run = p.add_run(tok)
            run.font.name = default_font
            run.font.size = Pt(default_size)
            run.font.color.rgb = default_color

def build_docx_report(md_path, docx_path):
    print(f"Reading {md_path}...")
    with open(md_path, 'r', encoding='utf-8') as f:
        raw_lines = f.readlines()

    lines = [clean_latex(l.rstrip('\r\n')) for l in raw_lines]

    doc = Document()

    # Standardize margins (1 inch)
    for sec in doc.sections:
        sec.top_margin = Inches(1.0)
        sec.bottom_margin = Inches(1.0)
        sec.left_margin = Inches(1.0)
        sec.right_margin = Inches(1.0)

    # Base font style
    normal_style = doc.styles['Normal']
    normal_style.font.name = 'Calibri'
    normal_style.font.size = Pt(11)
    normal_style.font.color.rgb = RGBColor(35, 35, 35)

    add_footer_page_number(doc)

    base_dir = os.path.dirname(os.path.abspath(md_path))
    repo_root = os.path.abspath(os.path.join(base_dir, '..'))

    i = 0
    in_code_block = False
    is_mermaid_block = False
    code_block_lines = []
    table_lines = []

    # Flag for cover page processing
    is_first_h1 = True

    while i < len(lines):
        line = lines[i].strip()

        # Handle Code Block delimiters
        if line.startswith('```'):
            if in_code_block:
                in_code_block = False
                if not is_mermaid_block and code_block_lines:
                    code_text = '\n'.join(code_block_lines)
                    # Render code snippet inside table/box for clean layout
                    code_tbl = doc.add_table(rows=1, cols=1)
                    code_tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
                    cell = code_tbl.rows[0].cells[0]
                    set_cell_background(cell, "F4F6F8")
                    set_cell_margins(cell, top=100, bottom=100, left=150, right=150)
                    cp = cell.paragraphs[0]
                    cp.paragraph_format.space_before = Pt(2)
                    cp.paragraph_format.space_after = Pt(2)
                    crun = cp.add_run(code_text)
                    crun.font.name = 'Consolas'
                    crun.font.size = Pt(9.0)
                    crun.font.color.rgb = RGBColor(40, 50, 60)
                    doc.add_paragraph().paragraph_format.space_after = Pt(4)

                code_block_lines = []
                is_mermaid_block = False
            else:
                in_code_block = True
                lang = line[3:].strip().lower()
                is_mermaid_block = ('mermaid' in lang)
                code_block_lines = []
            i += 1
            continue

        if in_code_block:
            if not is_mermaid_block:
                if any(m in line.lower() for m in ['flowchart', 'classdiagram', 'erdiagram', 'sequencediagram', 'graph ']):
                    is_mermaid_block = True
                else:
                    code_block_lines.append(lines[i]) # keep original indentation
            i += 1
            continue

        # Skip HTML wrappers
        if line in ['<div align="center">', '</div>', '<br>']:
            i += 1
            continue

        # Markdown image handling: ![Alt text](path/to/image.png)
        img_match = re.match(r'^!\[(.*?)\]\((.*?)\)$', line)
        if img_match:
            alt_text = img_match.group(1).strip()
            raw_img_path = img_match.group(2).strip()

            candidates = [
                os.path.join(base_dir, raw_img_path),
                os.path.join(repo_root, raw_img_path),
                os.path.abspath(raw_img_path)
            ]
            if raw_img_path.startswith('../'):
                candidates.insert(0, os.path.join(repo_root, raw_img_path[3:]))

            resolved_path = None
            for cand in candidates:
                if os.path.exists(cand):
                    resolved_path = cand
                    break

            if resolved_path:
                try:
                    with Image.open(resolved_path) as pil_img:
                        w_px, h_px = pil_img.size
                        aspect = w_px / h_px

                    img_p = doc.add_paragraph()
                    img_p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                    img_p.paragraph_format.space_before = Pt(12)
                    img_p.paragraph_format.space_after = Pt(4)
                    img_p.paragraph_format.keep_with_next = True

                    if aspect >= 1.25:  # Wide Landscape
                        target_w = Inches(6.2)
                    elif aspect <= 0.8:  # Tall Portrait
                        target_w = Inches(3.8)
                    else:  # Square-ish
                        target_w = Inches(4.8)

                    img_p.add_run().add_picture(resolved_path, width=target_w)

                    # Add caption
                    caption_text = alt_text if alt_text else os.path.basename(resolved_path)
                    cap_p = doc.add_paragraph()
                    cap_p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                    cap_p.paragraph_format.space_before = Pt(2)
                    cap_p.paragraph_format.space_after = Pt(12)
                    r_cap = cap_p.add_run(caption_text)
                    r_cap.font.name = 'Calibri'
                    r_cap.font.size = Pt(9.5)
                    r_cap.font.italic = True
                    r_cap.font.color.rgb = RGBColor(90, 90, 90)

                except Exception as e:
                    print(f"Error adding picture {resolved_path}: {e}")
            else:
                print(f"Warning: Image file not found: {raw_img_path}")

            i += 1
            continue

        # Skip redundant caption lines if they repeat *Gambar ...* right after image
        if line.startswith('*Gambar') and line.endswith('*'):
            i += 1
            continue

        # Table handling
        if line.startswith('|') and line.endswith('|'):
            table_lines.append(line)
            i += 1
            if i < len(lines) and lines[i].strip().startswith('|') and lines[i].strip().endswith('|'):
                continue
            else:
                if len(table_lines) >= 2:
                    rows_data = []
                    for tline in table_lines:
                        if re.match(r'^\|[\s\-:|]+\|$', tline):
                            continue
                        cells = [c.strip() for c in tline.strip('|').split('|')]
                        rows_data.append(cells)

                    if rows_data:
                        num_rows = len(rows_data)
                        num_cols = max(len(r) for r in rows_data)
                        tbl = doc.add_table(rows=num_rows, cols=num_cols)
                        tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
                        tbl.autofit = True

                        for r_idx, row_cells in enumerate(rows_data):
                            row = tbl.rows[r_idx]
                            is_header = (r_idx == 0)
                            for c_idx in range(num_cols):
                                cell = row.cells[c_idx]
                                cell_text = row_cells[c_idx] if c_idx < len(row_cells) else ''
                                cell.text = ''
                                p = cell.paragraphs[0]
                                p.paragraph_format.space_before = Pt(3)
                                p.paragraph_format.space_after = Pt(3)
                                p.paragraph_format.line_spacing = 1.05

                                set_cell_margins(cell, top=80, bottom=80, left=120, right=120)

                                if is_header:
                                    set_cell_background(cell, "2C3440")
                                    tokens = re.split(r'(\*\*.*?\*\*|\*.*?\*|`.*?`)', cell_text)
                                    for tok in tokens:
                                        if not tok: continue
                                        if tok.startswith('**') and tok.endswith('**'):
                                            r = p.add_run(tok[2:-2])
                                        else:
                                            r = p.add_run(tok)
                                        r.bold = True
                                        r.font.name = 'Calibri'
                                        r.font.size = Pt(10)
                                        r.font.color.rgb = RGBColor(255, 255, 255)
                                else:
                                    bg = "F8F9FA" if r_idx % 2 == 1 else "FFFFFF"
                                    set_cell_background(cell, bg)
                                    tokens = re.split(r'(\*\*.*?\*\*|\*.*?\*|`.*?`)', cell_text)
                                    for tok in tokens:
                                        if not tok: continue
                                        if tok.startswith('**') and tok.endswith('**'):
                                            r = p.add_run(tok[2:-2])
                                            r.bold = True
                                        elif tok.startswith('*') and tok.endswith('*'):
                                            r = p.add_run(tok[1:-1])
                                            r.italic = True
                                        elif tok.startswith('`') and tok.endswith('`'):
                                            r = p.add_run(tok[1:-1])
                                            r.font.name = 'Consolas'
                                            r.font.size = Pt(9)
                                        else:
                                            r = p.add_run(tok)
                                        r.font.name = 'Calibri'
                                        r.font.size = Pt(9.5)

                        doc.add_paragraph().paragraph_format.space_after = Pt(6)

                table_lines = []
                continue

        # Header 1 (# ...)
        if line.startswith('# '):
            text = line[2:].strip()

            # Automatic page breaks before formal sections and chapters
            page_break_triggers = [
                'LEMBAR PENGESAHAN',
                'KATA PENGANTAR',
                'DAFTAR ISI',
                'DAFTAR GAMBAR',
                'DAFTAR TABEL',
                'BAB I',
                'BAB II',
                'BAB III',
                'BAB IV',
                'BAB V',
                'BAB VI',
                'DAFTAR PUSTAKA',
                'LAMPIRAN'
            ]
            if any(text.startswith(t) for t in page_break_triggers):
                doc.add_page_break()

            # Real Word Heading 1 Style
            h = doc.add_heading(level=1)
            h.paragraph_format.space_before = Pt(18)
            h.paragraph_format.space_after = Pt(8)
            h.paragraph_format.keep_with_next = True
            if any(text.startswith(t) for t in ['BAB ', 'LEMBAR ', 'KATA ', 'DAFTAR ']):
                h.alignment = WD_ALIGN_PARAGRAPH.CENTER if any(text.startswith(t) for t in ['LEMBAR', 'KATA', 'DAFTAR', 'BAB']) else WD_ALIGN_PARAGRAPH.LEFT

            hrun = h.add_run(text)
            hrun.font.name = 'Calibri'
            hrun.font.size = Pt(16)
            hrun.bold = True
            hrun.font.color.rgb = RGBColor(27, 54, 93) # Formal Navy
            i += 1
            continue

        # Header 2 (## ...) - e.g. Cover subtitle
        if line.startswith('## '):
            text = line[3:].strip()
            h = doc.add_heading(level=2)
            h.paragraph_format.space_before = Pt(12)
            h.paragraph_format.space_after = Pt(6)
            h.paragraph_format.keep_with_next = True
            hrun = h.add_run(text)
            hrun.font.name = 'Calibri'
            hrun.font.size = Pt(13)
            hrun.bold = True
            hrun.font.color.rgb = RGBColor(44, 62, 80)
            i += 1
            continue

        # Header 3 (### ...) mapped to Word Heading 2 — for sub-bab (1.1, 2.3 etc.)
        if line.startswith('### '):
            text = line[4:].strip()
            h = doc.add_heading(level=2)
            h.paragraph_format.space_before = Pt(14)
            h.paragraph_format.space_after = Pt(5)
            h.paragraph_format.keep_with_next = True
            hrun = h.add_run(text)
            hrun.font.name = 'Calibri'
            hrun.font.size = Pt(12)
            hrun.bold = True
            hrun.font.color.rgb = RGBColor(40, 60, 90)
            i += 1
            continue

        # Header 4 (#### ...) mapped to Word Heading 3 — for sub-sub-bab
        if line.startswith('#### '):
            text = line[5:].strip()
            h = doc.add_heading(level=3)
            h.paragraph_format.space_before = Pt(10)
            h.paragraph_format.space_after = Pt(4)
            h.paragraph_format.keep_with_next = True
            hrun = h.add_run(text)
            hrun.font.name = 'Calibri'
            hrun.font.size = Pt(11)
            hrun.bold = True
            hrun.font.color.rgb = RGBColor(60, 80, 110)
            i += 1
            continue

        # Horizontal rule (---)
        if line == '---':
            p = doc.add_paragraph()
            p.paragraph_format.space_before = Pt(4)
            p.paragraph_format.space_after = Pt(6)
            run = p.add_run('_______________________________________________________________________________')
            run.font.color.rgb = RGBColor(210, 215, 220)
            run.font.size = Pt(8)
            i += 1
            continue

        # Empty line
        if not line:
            i += 1
            continue

        # Bullet list (- or *)
        bullet_match = re.match(r'^(\s*)[-*]\s+(.*)$', lines[i])
        if bullet_match:
            indent_level = len(bullet_match.group(1)) // 2
            item_text = bullet_match.group(2).strip()
            p = doc.add_paragraph(style='List Bullet')
            p.paragraph_format.space_before = Pt(1)
            p.paragraph_format.space_after = Pt(3)
            p.paragraph_format.line_spacing = 1.15
            p.paragraph_format.left_indent = Inches(0.25 * (indent_level + 1))
            add_formatted_runs(p, item_text)
            i += 1
            continue

        # Numbered list (1. or 2.)
        num_match = re.match(r'^(\s*)(\d+)\.\s+(.*)$', lines[i])
        if num_match:
            indent_level = len(num_match.group(1)) // 2
            num = num_match.group(2)
            item_text = f"{num}. {num_match.group(3).strip()}"
            p = doc.add_paragraph(style='Normal')
            p.paragraph_format.space_before = Pt(1)
            p.paragraph_format.space_after = Pt(3)
            p.paragraph_format.line_spacing = 1.15
            p.paragraph_format.left_indent = Inches(0.25 * (indent_level + 1))
            add_formatted_runs(p, item_text)
            i += 1
            continue

        # Normal text paragraph
        p = doc.add_paragraph(style='Normal')
        p.paragraph_format.space_before = Pt(0)
        p.paragraph_format.space_after = Pt(6)
        p.paragraph_format.line_spacing = 1.15
        add_formatted_runs(p, line)
        i += 1

    print(f"Saving to {docx_path}...")
    doc.save(docx_path)
    print("Done! UKK Word document successfully created!")

if __name__ == '__main__':
    md_file = 'docs/LAPORAN_UKK_AKSA_NOVA.md'
    docx_file = 'docs/LAPORAN_UKK_AKSA_NOVA.docx'
    build_docx_report(md_file, docx_file)
