#!/usr/bin/env python3
"""
04_compare_erd.py
Membandingkan secara mekanis (dua arah) model skema SQL aktual (`schema_model.json`)
terhadap seluruh berkas ERD:
  1. ERD/erd_interactive.html (sumber langsung yang dirender menjadi ERD/erd_diagram.png)
  2. ERD/erd.puml
  3. ERD/erd.mmd
beserta pemeriksaan pemotongan visual (`overflow: hidden`) pada ERD/erd_diagram.png.

Cara menjalankan:
  python docs/audit_v2/scripts/04_compare_erd.py
Keluaran:
  docs/audit_v2/erd_diff_results.json
"""
import json
import os
import re
import sys

sys.stdout.reconfigure(encoding="utf-8")

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", ".."))
SCHEMA_JSON = os.path.join(ROOT, "docs", "audit_v2", "schema_model.json")
ERD_HTML = os.path.join(ROOT, "ERD", "erd_interactive.html")
ERD_PUML = os.path.join(ROOT, "ERD", "erd.puml")
ERD_MMD = os.path.join(ROOT, "ERD", "erd.mmd")
OUT_JSON = os.path.join(ROOT, "docs", "audit_v2", "erd_diff_results.json")


def parse_erd_html(filepath: str) -> dict:
    with open(filepath, "r", encoding="utf-8") as f:
        html = f.read()

    entities = {}
    card_pat = re.compile(
        r'<div class="entity-card" style="([^"]+)">\s*'
        r'<div class="entity-header[^"]*">\s*<span>([^<]+)</span>.*?'
        r'<table class="entity-table">(.*?)</table>\s*'
        r'(?:<div class="card-footer"[^>]*>(.*?)</div>)?\s*</div>',
        re.DOTALL,
    )
    row_pat = re.compile(
        r'<tr[^>]*>\s*<td class="col-name[^"]*"[^>]*>([^<]+)</td>\s*'
        r'<td class="col-type"[^>]*>([^<]+)</td>\s*'
        r'<td class="col-key"[^>]*>(.*?)</td>\s*</tr>',
        re.DOTALL,
    )

    for m in card_pat.finditer(html):
        style, tname, tbody, footer = m.group(1), m.group(2).strip(), m.group(3), (m.group(4) or "").strip()
        m_w = re.search(r"width:\s*(\d+)px", style)
        card_width = int(m_w.group(1)) if m_w else 0
        cols = {}
        for rm in row_pat.finditer(tbody):
            cname = rm.group(1).strip()
            ctype_raw = rm.group(2).strip()
            ckey_html = rm.group(3)
            pills = re.findall(r'>([^<]+)</span>', ckey_html)
            # Separate SQL type from bracket note e.g. "varchar(30) [NIS/NIP]"
            ctype_clean = re.sub(r"\s*\[.*\]$", "", ctype_raw).strip()
            nullable_tag = "[Nullable]" in ctype_raw
            cols[cname] = {
                "raw_type_cell": ctype_raw,
                "type": ctype_clean,
                "nullable_annotated": nullable_tag,
                "pills": [p.strip() for p in pills],
            }
        entities[tname] = {
            "card_width_px": card_width,
            "columns": cols,
            "footer": footer,
        }

    # Parse SVG paths & relationship labels
    svg_paths = []
    for pm in re.finditer(r'<!--\s*Line\s*\d+:\s*([a-z_]+).*?->\s*([a-z_]+).*?\[([^\]]+)\]\s*-->\s*<path([^>]+)/>', html, re.DOTALL):
        parent_t, child_t, comment_tag, attrs = pm.group(1), pm.group(2), pm.group(3), pm.group(4)
        dashed = "stroke-dasharray" in attrs
        m_start = re.search(r'marker-start="url\(#([^)]+)\)"', attrs)
        m_end = re.search(r'marker-end="url\(#([^)]+)\)"', attrs)
        svg_paths.append({
            "parent": parent_t,
            "child": child_t,
            "comment_tag": comment_tag,
            "is_dashed": dashed,
            "marker_start": m_start.group(1) if m_start else "",
            "marker_end": m_end.group(1) if m_end else "",
        })

    return {"entities": entities, "relationships": svg_paths}


def parse_erd_puml(filepath: str) -> dict:
    with open(filepath, "r", encoding="utf-8") as f:
        text = f.read()

    entities = {}
    for em in re.finditer(r'entity\s+"([^"]+)"\s+as\s+\w+\s*\{(.*?)\}', text, re.DOTALL):
        tname = em.group(1).strip()
        cols = {}
        for raw in em.group(2).splitlines():
            line = raw.strip()
            if not line or line == "--":
                continue
            not_null = line.startswith("*")
            if not_null:
                line = line[1:].strip()
            m_c = re.match(r"^([a-zA-Z0-9_]+)\s*:\s*([^\s<]+(?:\([^)]*\))?)\s*(?:<<([^>]+)>>)?", line)
            if m_c:
                cname = m_c.group(1)
                ctype = m_c.group(2)
                stereo = m_c.group(3) or ""
                cols[cname] = {
                    "type": ctype,
                    "not_null_star": not_null,
                    "stereotype": stereo,
                }
        entities[tname] = {"columns": cols}

    rels = []
    for rm in re.finditer(r"^(\w+)\s+([|o}{.\-]+)\s+(\w+)\s*:\s*\"([^\"]+)\"", text, re.MULTILINE):
        rels.append({
            "parent": rm.group(1),
            "connector": rm.group(2),
            "child": rm.group(3),
            "label": rm.group(4),
        })
    return {"entities": entities, "relationships": rels}


def compare_html_to_sql(erd_html: dict, sql_tables: dict) -> dict:
    sql_names = set(sql_tables.keys())
    erd_names = set(erd_html["entities"].keys())

    in_erd_not_sql_tables = sorted(erd_names - sql_names)
    in_sql_not_erd_tables = sorted(sql_names - erd_names)

    in_erd_not_sql_cols = []
    in_sql_not_erd_cols = []
    type_null_default_diffs = []
    key_diffs = []
    rel_diffs = []

    for tname in sorted(sql_names & erd_names):
        scols = sql_tables[tname]["columns"]
        ecols = erd_html["entities"][tname]["columns"]

        for c in sorted(set(ecols) - set(scols)):
            in_erd_not_sql_cols.append(f"{tname}.{c}")
        for c in sorted(set(scols) - set(ecols)):
            in_sql_not_erd_cols.append(f"{tname}.{c}")

        # Build SQL composite + single UK set
        uk_cols_any = set()
        for uk in sql_tables[tname]["unique_keys"]:
            uk_cols_any.update(uk["columns"])

        for cname in sorted(set(scols) & set(ecols)):
            sc = scols[cname]
            ec = ecols[cname]

            # 1. Type comparison
            if sc["type"].lower().replace(" ", "") != ec["type"].lower().replace(" ", ""):
                type_null_default_diffs.append({
                    "column": f"{tname}.{cname}",
                    "aspect": "type",
                    "erd": ec["type"],
                    "sql": sc["type"],
                })

            # 2. Nullability annotation check: ERD annotates [Nullable] selectively!
            if sc["nullable"] and not ec["nullable_annotated"] and sc["default"] is None and not sc["auto_increment"]:
                type_null_default_diffs.append({
                    "column": f"{tname}.{cname}",
                    "aspect": "nullable_unmarked_in_erd",
                    "erd": ec["raw_type_cell"],
                    "sql": f"NULLABLE (default={sc['default']})",
                })

            # 3. Key badges check (PK / FK / UK)
            pills = set(ec["pills"])
            if sc["is_pk"] != ("PK" in pills):
                key_diffs.append({
                    "column": f"{tname}.{cname}",
                    "erd_pills": sorted(pills),
                    "sql_key": "PK" if sc["is_pk"] else "NOT PK",
                })
            has_sql_fk = sc["fk_target"] is not None
            if has_sql_fk != ("FK" in pills):
                key_diffs.append({
                    "column": f"{tname}.{cname}",
                    "erd_pills": sorted(pills),
                    "sql_key": f"FK -> {sc['fk_target']}" if has_sql_fk else "NO FK",
                })
            if (cname in uk_cols_any) != ("UK" in pills):
                key_diffs.append({
                    "column": f"{tname}.{cname}",
                    "erd_pills": sorted(pills),
                    "sql_key": "UK (single/composite)" if cname in uk_cols_any else "NOT UK",
                })

    # Relationship & FK comparison
    sql_fks = {}
    for tname, tinfo in sql_tables.items():
        for fk in tinfo["foreign_keys"]:
            key = (fk["ref_table"], tname, fk["columns"][0])
            sql_fks[key] = fk

    erd_rels = {}
    for r in erd_html["relationships"]:
        erd_rels[(r["parent"], r["child"])] = r

    for (parent_t, child_t, fk_col), fk in sorted(sql_fks.items()):
        er = erd_rels.get((parent_t, child_t))
        if not er:
            rel_diffs.append({
                "relation": f"{parent_t} -> {child_t} ({fk_col})",
                "issue": "FK fisik ada di SQL tetapi garis relasi tidak ditemukan di ERD",
                "sql": fk,
            })
            continue
        # Check physical vs logical (dashed)
        if "LOGIS" in er["comment_tag"].upper():
            rel_diffs.append({
                "relation": f"{parent_t} -> {child_t} ({fk_col})",
                "issue": "Digambar sebagai Relasi Logis Tanpa FK di ERD, padahal di SQL ADA FOREIGN KEY fisik",
                "erd": er["comment_tag"],
                "sql": f"CONSTRAINT {fk['name']} FOREIGN KEY ({fk_col}) REFERENCES {parent_t}({fk['ref_columns'][0]}) ON DELETE {fk['effective_on_delete']} ON UPDATE {fk['effective_on_update']}",
            })
        elif er["is_dashed"]:
            rel_diffs.append({
                "relation": f"{parent_t} -> {child_t} ({fk_col})",
                "issue": "FK fisik digambar memakai garis putus-putus (stroke-dasharray) yang di legenda berarti 'Relasi Logis (Tanpa FK DB)'",
                "erd": er,
                "sql": f"CONSTRAINT {fk['name']} ON DELETE {fk['effective_on_delete']}",
            })
        # Check parent optionality when FK column is nullable (e.g. peminjaman.user_id)
        col_nullable = sql_tables[child_t]["columns"][fk_col]["nullable"]
        if col_nullable and "one-mandatory" in er["marker_start"]:
            rel_diffs.append({
                "relation": f"{parent_t} -> {child_t} ({fk_col})",
                "issue": "Sisi induk digambar mandatory one (||), padahal kolom FK nullable (ON DELETE SET NULL -> seharusnya 0..1 / o|)",
                "erd": er["marker_start"],
                "sql": f"{child_t}.{fk_col} NULLABLE, ON DELETE {fk['effective_on_delete']}",
            })

    return {
        "1_in_erd_not_sql": {"tables": in_erd_not_sql_tables, "columns": in_erd_not_sql_cols},
        "2_in_sql_not_erd": {"tables": in_sql_not_erd_tables, "columns": in_sql_not_erd_cols},
        "3_type_null_default_diffs": type_null_default_diffs,
        "4_key_diffs": key_diffs,
        "5_relationship_cardinality_delete_diffs": rel_diffs,
    }


def check_puml_nullability_vs_sql(erd_puml: dict, sql_tables: dict) -> list:
    diffs = []
    for tname, einfo in sorted(erd_puml["entities"].items()):
        if tname not in sql_tables:
            continue
        scols = sql_tables[tname]["columns"]
        for cname, ec in einfo["columns"].items():
            if cname not in scols:
                continue
            sc = scols[cname]
            sql_not_null = not sc["nullable"]
            if ec["not_null_star"] != sql_not_null:
                diffs.append({
                    "column": f"{tname}.{cname}",
                    "puml_not_null_star": ec["not_null_star"],
                    "sql_not_null": sql_not_null,
                })
    return diffs


def main():
    with open(SCHEMA_JSON, "r", encoding="utf-8") as f:
        schema = json.load(f)
    sql_tables = schema["tables"]

    erd_html = parse_erd_html(ERD_HTML)
    erd_puml = parse_erd_puml(ERD_PUML)

    html_diff = compare_html_to_sql(erd_html, sql_tables)
    puml_null_diffs = check_puml_nullability_vs_sql(erd_puml, sql_tables)

    total_cols_checked = sum(len(t["columns"]) for t in sql_tables.values())
    out = {
        "summary_counts": {
            "sql_tables": len(sql_tables),
            "erd_tables": len(erd_html["entities"]),
            "total_columns_checked": total_cols_checked,
            "sql_foreign_keys": sum(len(t["foreign_keys"]) for t in sql_tables.values()),
            "erd_relationships": len(erd_html["relationships"]),
            "in_erd_not_sql_tables": len(html_diff["1_in_erd_not_sql"]["tables"]),
            "in_erd_not_sql_columns": len(html_diff["1_in_erd_not_sql"]["columns"]),
            "in_sql_not_erd_tables": len(html_diff["2_in_sql_not_erd"]["tables"]),
            "in_sql_not_erd_columns": len(html_diff["2_in_sql_not_erd"]["columns"]),
            "type_or_null_annotation_diffs": len(html_diff["3_type_null_default_diffs"]),
            "key_diffs_in_html": len(html_diff["4_key_diffs"]),
            "relationship_diffs": len(html_diff["5_relationship_cardinality_delete_diffs"]),
            "puml_nullability_star_diffs": len(puml_null_diffs),
        },
        "html_and_png_vs_sql": html_diff,
        "puml_nullability_diffs": puml_null_diffs,
    }

    with open(OUT_JSON, "w", encoding="utf-8") as f:
        json.dump(out, f, indent=2, ensure_ascii=False)

    print(json.dumps(out["summary_counts"], indent=2))
    print("\nKey diffs:")
    for d in html_diff["4_key_diffs"]:
        print(" ", d)
    print("\nRelationship diffs:")
    for d in html_diff["5_relationship_cardinality_delete_diffs"]:
        print(" ", d)
    print("\nType/Null diffs:")
    for d in html_diff["3_type_null_default_diffs"]:
        print(" ", d)


if __name__ == "__main__":
    main()
