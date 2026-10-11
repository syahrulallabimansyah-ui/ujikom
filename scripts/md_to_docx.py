import re
import os
import docx
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml import parse_xml
from docx.oxml.ns import nsdecls
from PIL import Image

def set_cell_background(cell, fill_hex):
    tcPr = cell._element.get_or_add_tcPr()
    shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{fill_hex}"/>')
    tcPr.append(shd)

def set_cell_margins(cell, top=100, bottom=100, left=150, right=150):
    tcPr = cell._element.get_or_add_tcPr()
    tcMar = parse_xml(f'<w:tcMar {nsdecls("w")}><w:top w:w="{top}" w:type="dxa"/><w:bottom w:w="{bottom}" w:type="dxa"/><w:left w:w="{left}" w:type="dxa"/><w:right w:w="{right}" w:type="dxa"/></w:tcMar>')
    tcPr.append(tcMar)

def add_styled_paragraph(doc, text, style='Normal', space_after=6, space_before=0, line_spacing=1.15, align=WD_ALIGN_PARAGRAPH.LEFT):
    p = doc.add_paragraph(style=style)
    p.paragraph_format.space_after = Pt(space_after)
    p.paragraph_format.space_before = Pt(space_before)
    p.paragraph_format.line_spacing = line_spacing
    p.alignment = align
    
    tokens = re.split(r'(\*\*.*?\*\*|\*.*?\*|`.*?`)', text)
    for tok in tokens:
        if not tok:
            continue
        if tok.startswith('**') and tok.endswith('**'):
            run = p.add_run(tok[2:-2])
            run.bold = True
        elif tok.startswith('*') and tok.endswith('*'):
            run = p.add_run(tok[1:-1])
            run.italic = True
        elif tok.startswith('`') and tok.endswith('`'):
            run = p.add_run(tok[1:-1])
            run.font.name = 'Consolas'
            run.font.size = Pt(9.5)
            run.font.color.rgb = RGBColor(180, 130, 60)
        else:
            p.add_run(tok)
    return p

def convert_md_to_docx(md_path, docx_path):
    with open(md_path, 'r', encoding='utf-8') as f:
        lines = f.readlines()

    doc = Document()

    # Set page margins (1 inch all sides)
    for section in doc.sections:
        section.top_margin = Inches(1.0)
        section.bottom_margin = Inches(1.0)
        section.left_margin = Inches(1.0)
        section.right_margin = Inches(1.0)

    # Set default font
    normal_style = doc.styles['Normal']
    normal_style.font.name = 'Calibri'
    normal_style.font.size = Pt(11)
    normal_style.font.color.rgb = RGBColor(30, 30, 30)

    i = 0
    in_code_block = False
    is_mermaid_block = False
    code_block_lines = []

    table_lines = []

    base_dir = os.path.dirname(os.path.abspath(md_path))
    repo_root = os.path.abspath(os.path.join(base_dir, '..'))

    while i < len(lines):
        line = lines[i].rstrip('\r\n')

        # Code block handling (Mermaid vs Normal Code)
        if line.strip().startswith('```'):
            if in_code_block:
                in_code_block = False
                if not is_mermaid_block and code_block_lines:
                    code_text = '\n'.join(code_block_lines)
                    code_p = doc.add_paragraph()
                    code_p.paragraph_format.space_before = Pt(4)
                    code_p.paragraph_format.space_after = Pt(8)
                    code_p.paragraph_format.left_indent = Inches(0.25)
                    run = code_p.add_run(code_text)
                    run.font.name = 'Consolas'
                    run.font.size = Pt(9)
                    run.font.color.rgb = RGBColor(80, 80, 80)
                code_block_lines = []
                is_mermaid_block = False
            else:
                in_code_block = True
                lang = line.strip()[3:].strip().lower()
                is_mermaid_block = ('mermaid' in lang)
                code_block_lines = []
            i += 1
            continue

        if in_code_block:
            if not is_mermaid_block:
                # Check if first line of code block has mermaid markers
                if any(m in line.lower() for m in ['flowchart', 'classdiagram', 'erdiagram', 'sequencediagram']):
                    is_mermaid_block = True
                else:
                    code_block_lines.append(line)
            i += 1
            continue

        # Markdown image handling: ![Alt text](path/to/image.png)
        img_match = re.match(r'^!\[(.*?)\]\((.*?)\)$', line.strip())
        if img_match:
            alt_text = img_match.group(1).strip()
            raw_img_path = img_match.group(2).strip()

            # Resolve image path
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
                    img_p.paragraph_format.space_before = Pt(10)
                    img_p.paragraph_format.space_after = Pt(4)
                    img_p.paragraph_format.keep_with_next = True

                    # Max page width: 6.2 inches. Max page height: 8.0 inches.
                    if aspect >= 1.2:  # Landscape
                        target_w = Inches(min(6.2, 6.2))
                        img_p.add_run().add_picture(resolved_path, width=target_w)
                    elif aspect <= 0.8:  # Portrait
                        target_w = Inches(3.8)
                        img_p.add_run().add_picture(resolved_path, width=target_w)
                    else:  # Square-ish
                        target_w = Inches(4.8)
                        img_p.add_run().add_picture(resolved_path, width=target_w)

                    # Add caption if available
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
                print(f"Image file not found: {raw_img_path}")

            i += 1
            continue

        # Skip standalone caption lines if they follow an image and start with *Gambar
        if line.strip().startswith('*Gambar') and line.strip().endswith('*'):
            i += 1
            continue

        # Table handling
        if line.strip().startswith('|') and line.strip().endswith('|'):
            table_lines.append(line.strip())
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
            p = doc.add_paragraph()
            p.paragraph_format.space_before = Pt(18)
            p.paragraph_format.space_after = Pt(8)
            p.paragraph_format.keep_with_next = True
            run = p.add_run(text)
            run.font.name = 'Calibri'
            run.font.size = Pt(18)
            run.bold = True
            run.font.color.rgb = RGBColor(20, 35, 60)
            i += 1
            continue

        # Header 2 (## ...)
        if line.startswith('## '):
            text = line[3:].strip()
            p = doc.add_paragraph()
            p.paragraph_format.space_before = Pt(14)
            p.paragraph_format.space_after = Pt(6)
            p.paragraph_format.keep_with_next = True
            run = p.add_run(text)
            run.font.name = 'Calibri'
            run.font.size = Pt(15)
            run.bold = True
            run.font.color.rgb = RGBColor(30, 50, 80)
            i += 1
            continue

        # Header 3 (### ...)
        if line.startswith('### '):
            text = line[4:].strip()
            p = doc.add_paragraph()
            p.paragraph_format.space_before = Pt(12)
            p.paragraph_format.space_after = Pt(4)
            p.paragraph_format.keep_with_next = True
            run = p.add_run(text)
            run.font.name = 'Calibri'
            run.font.size = Pt(13)
            run.bold = True
            run.font.color.rgb = RGBColor(40, 70, 110)
            i += 1
            continue

        # Header 4 (#### ...)
        if line.startswith('#### '):
            text = line[5:].strip()
            p = doc.add_paragraph()
            p.paragraph_format.space_before = Pt(10)
            p.paragraph_format.space_after = Pt(4)
            p.paragraph_format.keep_with_next = True
            run = p.add_run(text)
            run.font.name = 'Calibri'
            run.font.size = Pt(11.5)
            run.bold = True
            run.font.color.rgb = RGBColor(50, 80, 120)
            i += 1
            continue

        # Horizontal rule (---)
        if line.strip() == '---':
            p = doc.add_paragraph()
            p.paragraph_format.space_before = Pt(6)
            p.paragraph_format.space_after = Pt(6)
            run = p.add_run('_______________________________________________________________________________')
            run.font.color.rgb = RGBColor(200, 200, 200)
            run.font.size = Pt(8)
            i += 1
            continue

        # Empty line
        if not line.strip():
            i += 1
            continue

        # Bullet list (- or *)
        bullet_match = re.match(r'^(\s*)[-*]\s+(.*)$', line)
        if bullet_match:
            indent_level = len(bullet_match.group(1)) // 2
            item_text = bullet_match.group(2)
            p = add_styled_paragraph(doc, item_text, style='List Bullet', space_after=3, space_before=1)
            p.paragraph_format.left_indent = Inches(0.25 * (indent_level + 1))
            i += 1
            continue

        # Numbered list (1. or 2.)
        num_match = re.match(r'^(\s*)(\d+)\.\s+(.*)$', line)
        if num_match:
            indent_level = len(num_match.group(1)) // 2
            num = num_match.group(2)
            item_text = f"{num}. {num_match.group(3)}"
            p = add_styled_paragraph(doc, item_text, style='Normal', space_after=3, space_before=1)
            p.paragraph_format.left_indent = Inches(0.25 * (indent_level + 1))
            i += 1
            continue

        # Normal text paragraph
        add_styled_paragraph(doc, line, style='Normal', space_after=6, space_before=0)
        i += 1

    doc.save(docx_path)
    print(f"Successfully converted {md_path} to {docx_path} with embedded images!")

if __name__ == '__main__':
    convert_md_to_docx('docs/LAPORAN_UKK_AKSA_NOVA.md', 'docs/LAPORAN_UKK_AKSA_NOVA.docx')
