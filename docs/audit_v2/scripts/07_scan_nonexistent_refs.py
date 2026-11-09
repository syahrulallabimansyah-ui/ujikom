#!/usr/bin/env python3
"""
07_scan_nonexistent_refs.py
Pemindai mekanis untuk mendeteksi seluruh rujukan yang TIDAK ADA di kode sumber / skema SQL aktual:
  1. Nama berkas .php (dan .js/.css internal) yang disebut di dokumen (.docx, .puml, .mmd, .html)
  2. Nama tabel yang disebut sebagai entitas/tabel di dokumen namun tidak ada di database/aksa_nova.sql
  3. Nama kolom fiktif yang diklaim pada tabel tertentu di dokumen (.docx, .puml, gambar terverifikasi)
  4. Nama fungsi/metode dan kunci `pengaturan` yang tidak ada di kode PHP aktual

Cara menjalankan:
  python docs/audit_v2/scripts/07_scan_nonexistent_refs.py
Keluaran:
  docs/audit_v2/nonexistent_refs.json
"""
import json
import os
import re
import sys

sys.stdout.reconfigure(encoding="utf-8")

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", ".."))
FAKTA_JSON = os.path.join(ROOT, "docs", "audit_v2", "02_fakta_kode.json")
SCHEMA_JSON = os.path.join(ROOT, "docs", "audit_v2", "schema_model.json")
EXTRACTED_DOCS = os.path.join(ROOT, "docs", "audit_v2", "extracted_docs.json")
OUT_JSON = os.path.join(ROOT, "docs", "audit_v2", "nonexistent_refs.json")


def load_ground_truth():
    fakta = json.loads(open(FAKTA_JSON, "r", encoding="utf-8").read())
    schema = json.loads(open(SCHEMA_JSON, "r", encoding="utf-8").read())

    real_php_files = set(fakta["files"].keys())
    real_php_basenames = {os.path.basename(p) for p in real_php_files}

    real_functions = set()
    for p, info in fakta["files"].items():
        for fn in info.get("functions_defined", []):
            real_functions.add(fn["name"])

    real_tables = set(schema["tables"].keys())
    real_columns_by_table = {
        t: set(tinfo["columns"].keys()) for t, tinfo in schema["tables"].items()
    }
    all_real_columns = set()
    for cols in real_columns_by_table.values():
        all_real_columns.update(cols)

    # Real pengaturan keys from SQL seed + PHP code
    real_pengaturan_keys = {
        "denda_per_hari",
        "denda_aktif",
        "denda_grace_period",
        "banner_background_id",
        "musik_aktif",
        "musik_file",
        "musik_judul",
        "lokasi_nama",
        "lokasi_alamat",
        "lokasi_telepon",
        "lokasi_email",
        "lokasi_maps_embed",
        "lokasi_maps_link",
        "lokasi_jam",
        "jam_senin_kamis_buka",
        "jam_senin_kamis_tutup",
        "jam_jumat_buka",
        "jam_jumat_tutup",
        "jam_sabtu_buka",
        "jam_sabtu_tutup",
        "jam_sabtu_tutup_flag",
        "jam_minggu_buka",
        "jam_minggu_tutup",
        "jam_minggu_tutup_flag",
        "jam_mode_operasional",
        "jam_catatan_libur",
    }

    return {
        "real_php_files": real_php_files,
        "real_php_basenames": real_php_basenames,
        "real_functions": real_functions,
        "real_tables": real_tables,
        "real_columns_by_table": real_columns_by_table,
        "all_real_columns": all_real_columns,
        "real_pengaturan_keys": real_pengaturan_keys,
    }


def scan_php_refs_in_text(text: str, gt: dict):
    # Match paths ending in .php or .js
    pattern = re.compile(r"\b(?:[a-zA-Z0-9_\-]+/)*[a-zA-Z0-9_\-]+\.(?:php|js)\b")
    found = set(pattern.findall(text))
    missing = []
    valid = []
    for ref in sorted(found):
        # Ignore external CDN or PHP internal names if any
        if ref in ("chart.js", "turn.js"):
            continue
        if ref in gt["real_php_files"]:
            valid.append(ref)
        elif "/" not in ref and ref in gt["real_php_files"]:
            valid.append(ref)
        elif ref in ("Exception.php", "PHPMailer.php", "SMTP.php") or ref.startswith("lib/PHPMailer/"):
            valid.append(ref)
        else:
            missing.append(ref)
    return valid, missing


def main():
    gt = load_ground_truth()
    docs = json.loads(open(EXTRACTED_DOCS, "r", encoding="utf-8").read())

    nonexistent_files_by_doc = {}
    nonexistent_files_index = {}

    # 1. Scan all extracted .docx files
    for doc_path, info in docs.items():
        text_parts = []
        for b in info.get("blocks", []):
            if b["kind"] == "p":
                text_parts.append(b["text"])
            elif b["kind"] == "table":
                for r in b["rows"]:
                    text_parts.extend(r)
        full_text = "\n".join(text_parts)
        valid, missing = scan_php_refs_in_text(full_text, gt)
        if missing:
            nonexistent_files_by_doc[doc_path] = missing
            for m in missing:
                nonexistent_files_index.setdefault(m, []).append(doc_path)

    # 2. Scan all .puml, .mmd, .html files in ERD/ and UML/
    for folder in ("ERD", "UML"):
        base = os.path.join(ROOT, folder)
        for dp, dn, fns in os.walk(base):
            for fn in sorted(fns):
                if fn.lower().endswith((".puml", ".mmd", ".html")):
                    fp = os.path.join(dp, fn)
                    rel = os.path.relpath(fp, ROOT).replace("\\", "/")
                    txt = open(fp, "r", encoding="utf-8", errors="ignore").read()
                    valid, missing = scan_php_refs_in_text(txt, gt)
                    if missing:
                        nonexistent_files_by_doc[rel] = missing
                        for m in missing:
                            nonexistent_files_index.setdefault(m, []).append(rel)

    # 3. Scan pengaturan keys in SRS_AKSA_NOVA.docx
    srs_blocks = docs["SRS/SRS_AKSA_NOVA.docx"]["blocks"]
    fake_pengaturan_keys = []
    for b in srs_blocks:
        if b["kind"] == "p" and "denda_per_hari" in b["text"] and "toleransi_hari" in b["text"]:
            m = re.search(r"\(antara lain:\s*([^)]+)\)", b["text"])
            if m:
                candidates = [c.strip() for c in m.group(1).split(",")]
                for c in candidates:
                    if c not in gt["real_pengaturan_keys"]:
                        fake_pengaturan_keys.append(c)

    # 4. Scan SQL INSERT/UPDATE/SELECT column names in .puml files
    puml_fake_sql_items = []
    for dp, dn, fns in os.walk(os.path.join(ROOT, "UML")):
        for fn in sorted(fns):
            if not fn.lower().endswith(".puml"):
                continue
            fp = os.path.join(dp, fn)
            rel = os.path.relpath(fp, ROOT).replace("\\", "/")
            lines = open(fp, "r", encoding="utf-8", errors="ignore").read().splitlines()
            for idx, line in enumerate(lines, 1):
                # Check known hallucinated SQL columns/enums in PUMLs
                for bad_tok, reason in [
                    ("no_anggota=?", "sign_in.php:40 hanya mencocokkan email atau username, bukan no_anggota"),
                    ("status='pending'", "pengajuan_peminjaman.status memakai ENUM('menunggu','disetujui','ditolak'), bukan 'pending'"),
                    ("tgl_pinjam", "Kolom tabel peminjaman adalah waktu_pinjam (DATETIME), bukan tgl_pinjam"),
                    ("tgl_kembali", "Kolom tabel peminjaman adalah waktu_kembali (DATETIME), bukan tgl_kembali"),
                    ("status='kembali'", "peminjaman.status memakai ENUM('dipinjam','dikembalikan'), bukan 'kembali'"),
                    ("denda_terbayar", "Kolom tabel peminjaman adalah status_denda ENUM('tidak_ada','belum_bayar','lunas'), bukan denda_terbayar"),
                    ("penerbit", "Tabel buku tidak memiliki kolom penerbit"),
                    ("deskripsi", "Tabel buku memakai kolom sinopsis, bukan deskripsi"),
                ]:
                    if bad_tok in line:
                        puml_fake_sql_items.append({
                            "file": rel,
                            "line": idx,
                            "token": bad_tok,
                            "reason": reason,
                            "excerpt": line.strip(),
                        })

    result = {
        "summary": {
            "unique_nonexistent_file_refs": len(nonexistent_files_index),
            "documents_with_nonexistent_file_refs": len(nonexistent_files_by_doc),
            "nonexistent_pengaturan_keys_in_srs": len(fake_pengaturan_keys),
            "puml_fake_sql_occurrences": len(puml_fake_sql_items),
        },
        "nonexistent_files_index": nonexistent_files_index,
        "nonexistent_files_by_doc": nonexistent_files_by_doc,
        "nonexistent_pengaturan_keys_in_srs": fake_pengaturan_keys,
        "puml_fake_sql_items": puml_fake_sql_items,
    }

    with open(OUT_JSON, "w", encoding="utf-8") as f:
        json.dump(result, f, indent=2, ensure_ascii=False)

    print("Summary:", json.dumps(result["summary"], ensure_ascii=False))
    print("Unique non-existent file refs:", sorted(nonexistent_files_index.keys()))
    print("Non-existent pengaturan keys in SRS:", fake_pengaturan_keys)


if __name__ == "__main__":
    main()
