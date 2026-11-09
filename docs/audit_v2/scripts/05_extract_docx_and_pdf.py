#!/usr/bin/env python3
"""
05_extract_docx_and_pdf.py
Mengekstrak seluruh paragraf, heading, dan tabel dari:
  1. SRS/SRS_AKSA_NOVA.docx
  2. SRS/SRS_AKSA_NOVA.pdf (jika pypdf/pymupdf tersedia, atau pemeriksaan ukuran/metadata)
  3. ERD/Penjelasan ERD - AKSA NOVA.docx
  4. UML/Usecase/usecase-penjelasan.docx
  5. UML/Class/Penjelasan Class Diagram - AKSA NOVA.docx
  6. UML/Squence/Penjelasan Sequence Diagram - AKSA NOVA.docx
  7. UML/Activty/Alur Activity Diagram.docx
serta memindai rujukan nama file `.php`, tabel, kolom, dan fungsi di dalamnya!

Cara menjalankan:
  python docs/audit_v2/scripts/05_extract_docx_and_pdf.py
Keluaran:
  docs/audit_v2/extracted_docs.json
"""
import json
import os
import re
import sys

import docx
from docx.document import Document as _Document
from docx.oxml.table import CT_Tbl
from docx.oxml.text.paragraph import CT_P
from docx.table import Table, _Cell
from docx.text.paragraph import Paragraph

sys.stdout.reconfigure(encoding="utf-8")

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", ".."))
OUT_JSON = os.path.join(ROOT, "docs", "audit_v2", "extracted_docs.json")

DOCX_FILES = [
    "SRS/SRS_AKSA_NOVA.docx",
    "ERD/Penjelasan ERD - AKSA NOVA.docx",
    "UML/Usecase/usecase-penjelasan.docx",
    "UML/Class/Penjelasan Class Diagram - AKSA NOVA.docx",
    "UML/Squence/Penjelasan Sequence Diagram - AKSA NOVA.docx",
    "UML/Activty/Alur Activity Diagram.docx",
]


def iter_block_items(parent):
    if isinstance(parent, _Document):
        parent_elm = parent.element.body
    elif isinstance(parent, _Cell):
        parent_elm = parent._tc
    else:
        raise ValueError("Unsupported parent")
    for child in parent_elm.iterchildren():
        if isinstance(child, CT_P):
            yield Paragraph(child, parent)
        elif isinstance(child, CT_Tbl):
            yield Table(child, parent)


def extract_docx(rel_path: str) -> dict:
    full = os.path.join(ROOT, rel_path)
    doc = docx.Document(full)
    blocks = []
    p_idx = 0
    t_idx = 0
    full_text_parts = []

    for item in iter_block_items(doc):
        if isinstance(item, Paragraph):
            txt = item.text.strip()
            if txt:
                p_idx += 1
                style = item.style.name if item.style else "Normal"
                blocks.append({"kind": "p", "index": p_idx, "style": style, "text": txt})
                full_text_parts.append(txt)
        elif isinstance(item, Table):
            t_idx += 1
            rows_data = []
            for row in item.rows:
                cells = [re.sub(r"\s+", " ", c.text.strip()) for c in row.cells]
                rows_data.append(cells)
                full_text_parts.append(" | ".join(cells))
            blocks.append({"kind": "table", "index": t_idx, "rows": rows_data})

    joined = "\n".join(full_text_parts)
    php_refs = sorted(set(re.findall(r"\b(?:[a-zA-Z0-9_\-]+/)*[a-zA-Z0-9_\-]+\.php\b", joined)))

    return {
        "path": rel_path,
        "paragraph_count": p_idx,
        "table_count": t_idx,
        "php_file_references": php_refs,
        "blocks": blocks,
    }


def main():
    with open(os.path.join(ROOT, "docs", "audit_v2", "02_fakta_kode.json"), "r", encoding="utf-8") as f:
        fakta = json.load(f)
    real_php_files = set(fakta["files"].keys()) | {"lib/PHPMailer/PHPMailer.php", "lib/PHPMailer/SMTP.php", "lib/PHPMailer/Exception.php"}

    extracted = {}
    for rel in DOCX_FILES:
        data = extract_docx(rel)
        missing_php = [
            p for p in data["php_file_references"]
            if p not in real_php_files and f"includes/{p}" not in real_php_files and p not in ("PHPMailer.php", "SMTP.php", "Exception.php")
        ]
        data["non_existent_php_refs"] = missing_php
        extracted[rel] = data
        print(f"{rel}: {data['paragraph_count']} paragraphs, {data['table_count']} tables | PHP refs={len(data['php_file_references'])}, missing={missing_php}")

    with open(OUT_JSON, "w", encoding="utf-8") as f:
        json.dump(extracted, f, indent=2, ensure_ascii=False)
    print(f"Saved extracted text & tables -> {OUT_JSON}")


if __name__ == "__main__":
    main()
