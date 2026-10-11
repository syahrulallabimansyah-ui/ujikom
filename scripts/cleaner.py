# -*- coding: utf-8 -*-
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
        (r'$\ge 360 \times 640$', '≥ 360 × 640'),
        (r'$\blacklozenge$', '◆'),
        (r'\ll\text{create}\gg', '«create»'),
        (r'\ll\text{use}\gg', '«use»'),
        (r'\ll\text{include}\gg', '«include»'),
        (r'\ll\text{extend}\gg', '«extend»'),
        (r'$\le$', '≤'),
        (r'$\ge$', '≥'),
    ]
    for old, new in replacements:
        s = s.replace(old, new)
    return s

def build_full_report_markdown():
    # Read the perfected template
    with open('docs/LAPORAN_UKK_AKSA_NOVA.md', 'r', encoding='utf-8') as f:
        md = f.read()
    md = clean_latex(md)
    return md

print("Markdown cleaner ready.")
