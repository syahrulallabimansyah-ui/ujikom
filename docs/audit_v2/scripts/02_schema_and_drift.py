#!/usr/bin/env python3
"""
02_schema_and_drift.py
Membangun model skema database secara mekanis dari:
  - database/aksa_nova.sql
  - database/migrations/2026_otp_reset.sql
Lalu memeriksa DRIFT SKEMA DUA ARAH antara seluruh kueri SQL di 44 file PHP aplikasi
dengan definisi tabel & kolom di SQL.

Cara menjalankan:
  python docs/audit_v2/scripts/02_schema_and_drift.py
Keluaran:
  docs/audit_v2/schema_model.json
"""
import json
import os
import re
import sys

sys.stdout.reconfigure(encoding="utf-8")

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", ".."))
SQL_MAIN = os.path.join(ROOT, "database", "aksa_nova.sql")
SQL_MIG = os.path.join(ROOT, "database", "migrations", "2026_otp_reset.sql")
OUT_JSON = os.path.join(ROOT, "docs", "audit_v2", "schema_model.json")


def parse_sql_create_tables(filepath: str) -> dict:
    with open(filepath, "r", encoding="utf-8") as f:
        lines = f.readlines()

    tables = {}
    in_create = False
    cur_table = None
    cur_start_line = 0
    cur_lines = []

    for idx, raw_line in enumerate(lines, start=1):
        line = raw_line.strip()
        m_start = re.match(
            r"^CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s+`?([a-zA-Z0-9_]+)`?\s*\(",
            line,
            re.IGNORECASE,
        )
        if m_start:
            in_create = True
            cur_table = m_start.group(1)
            cur_start_line = idx
            cur_lines = []
            continue

        if in_create:
            if line.startswith(")") and ";" in line:
                tables[cur_table] = _parse_table_body(
                    cur_table, cur_start_line, cur_lines, line
                )
                in_create = False
                cur_table = None
                cur_lines = []
            else:
                cur_lines.append((idx, line))

    return tables


def _parse_table_body(table_name: str, start_line: int, body_lines: list, footer: str) -> dict:
    columns = {}
    col_order = []
    primary_key = []
    unique_keys = []
    indexes = []
    foreign_keys = []

    m_eng = re.search(r"ENGINE=([a-zA-Z0-9_]+)", footer, re.IGNORECASE)
    m_cs = re.search(r"CHARSET=([a-zA-Z0-9_]+)", footer, re.IGNORECASE)
    m_col = re.search(r"COLLATE=([a-zA-Z0-9_]+)", footer, re.IGNORECASE)

    for line_no, raw in body_lines:
        item = raw.rstrip(",").strip()
        if not item:
            continue

        # PRIMARY KEY
        m_pk = re.match(r"^PRIMARY\s+KEY\s*\((.+)\)$", item, re.IGNORECASE)
        if m_pk:
            primary_key = [c.strip().strip("`") for c in m_pk.group(1).split(",")]
            continue

        # UNIQUE KEY
        m_uk = re.match(
            r"^UNIQUE\s+KEY\s+`?([a-zA-Z0-9_]+)`?\s*\((.+)\)$", item, re.IGNORECASE
        )
        if m_uk:
            cols = [c.strip().strip("`") for c in m_uk.group(2).split(",")]
            unique_keys.append({"name": m_uk.group(1), "columns": cols, "line": line_no})
            continue

        # CONSTRAINT ... FOREIGN KEY
        m_fk = re.match(
            r"^(?:CONSTRAINT\s+`?([a-zA-Z0-9_]+)`?\s+)?FOREIGN\s+KEY\s*\(([^)]+)\)\s*REFERENCES\s+`?([a-zA-Z0-9_]+)`?\s*\(([^)]+)\)(.*)$",
            item,
            re.IGNORECASE,
        )
        if m_fk:
            fk_name = m_fk.group(1) or ""
            fk_cols = [c.strip().strip("`") for c in m_fk.group(2).split(",")]
            ref_table = m_fk.group(3)
            ref_cols = [c.strip().strip("`") for c in m_fk.group(4).split(",")]
            tail = m_fk.group(5) or ""
            m_del = re.search(
                r"ON\s+DELETE\s+(CASCADE|SET\s+NULL|RESTRICT|NO\s+ACTION|SET\s+DEFAULT)",
                tail,
                re.IGNORECASE,
            )
            m_upd = re.search(
                r"ON\s+UPDATE\s+(CASCADE|SET\s+NULL|RESTRICT|NO\s+ACTION|SET\s+DEFAULT)",
                tail,
                re.IGNORECASE,
            )
            raw_del = re.sub(r"\s+", " ", m_del.group(1).upper()) if m_del else None
            raw_upd = re.sub(r"\s+", " ", m_upd.group(1).upper()) if m_upd else None
            foreign_keys.append(
                {
                    "name": fk_name,
                    "columns": fk_cols,
                    "ref_table": ref_table,
                    "ref_columns": ref_cols,
                    "raw_on_delete": raw_del,
                    "raw_on_update": raw_upd,
                    "effective_on_delete": raw_del or "RESTRICT",
                    "effective_on_update": raw_upd or "RESTRICT",
                    "line": line_no,
                }
            )
            continue

        # KEY / INDEX
        m_idx = re.match(
            r"^(?:KEY|INDEX)\s+`?([a-zA-Z0-9_]+)`?\s*\((.+)\)$", item, re.IGNORECASE
        )
        if m_idx:
            cols = [c.strip().strip("`") for c in m_idx.group(2).split(",")]
            indexes.append({"name": m_idx.group(1), "columns": cols, "line": line_no})
            continue

        # COLUMN definition
        m_col_def = re.match(r"^`([a-zA-Z0-9_]+)`\s+(.+)$", item)
        if m_col_def:
            col_name = m_col_def.group(1)
            rest = m_col_def.group(2)

            # Extract type (including enum(...) or decimal(12,0) or varchar(255) etc.)
            m_type = re.match(
                r"^([a-zA-Z0-9_]+(?:\s*\([^)]*\))?(?:\s+unsigned)?)",
                rest,
                re.IGNORECASE,
            )
            col_type = m_type.group(1) if m_type else rest.split()[0]
            base_type = re.match(r"^([a-zA-Z0-9_]+)", col_type).group(1).lower()

            enum_values = None
            if base_type == "enum":
                m_ev = re.search(r"\((.+)\)", col_type)
                if m_ev:
                    enum_values = [
                        v.strip().strip("'") for v in m_ev.group(1).split(",")
                    ]

            not_null = bool(re.search(r"\bNOT\s+NULL\b", rest, re.IGNORECASE))
            nullable = not not_null
            auto_inc = bool(re.search(r"\bAUTO_INCREMENT\b", rest, re.IGNORECASE))
            on_update_ct = bool(
                re.search(r"\bON\s+UPDATE\s+CURRENT_TIMESTAMP\b", rest, re.IGNORECASE)
            )

            default_val = None
            has_default = False
            m_def_str = re.search(r"\bDEFAULT\s+'([^']*)'", rest, re.IGNORECASE)
            if m_def_str:
                has_default = True
                default_val = m_def_str.group(1)
            else:
                m_def_tok = re.search(
                    r"\bDEFAULT\s+(NULL|CURRENT_TIMESTAMP|-?\d+(?:\.\d+)?)\b",
                    rest,
                    re.IGNORECASE,
                )
                if m_def_tok:
                    has_default = True
                    tok = m_def_tok.group(1)
                    default_val = None if tok.upper() == "NULL" else tok

            m_comm = re.search(r"\bCOMMENT\s+'((?:[^']|'')*)'", rest, re.IGNORECASE)
            comment = m_comm.group(1) if m_comm else ""

            col_order.append(col_name)
            columns[col_name] = {
                "name": col_name,
                "type": col_type,
                "base_type": base_type,
                "enum_values": enum_values,
                "nullable": nullable,
                "has_default": has_default,
                "default": default_val,
                "auto_increment": auto_inc,
                "on_update_current_timestamp": on_update_ct,
                "comment": comment,
                "line": line_no,
            }

    # Annotate PK / UK / FK on columns
    uk_single = {
        uk["columns"][0] for uk in unique_keys if len(uk["columns"]) == 1
    }
    fk_map = {}
    for fk in foreign_keys:
        if len(fk["columns"]) == 1:
            fk_map[fk["columns"][0]] = f"{fk['ref_table']}.{fk['ref_columns'][0]}"

    for cname, cinfo in columns.items():
        cinfo["is_pk"] = cname in primary_key
        cinfo["is_uk_single"] = cname in uk_single
        cinfo["fk_target"] = fk_map.get(cname)

    return {
        "table": table_name,
        "start_line": start_line,
        "engine": m_eng.group(1) if m_eng else "",
        "charset": m_cs.group(1) if m_cs else "",
        "collate": m_col.group(1) if m_col else "",
        "column_order": col_order,
        "columns": columns,
        "primary_key": primary_key,
        "unique_keys": unique_keys,
        "indexes": indexes,
        "foreign_keys": foreign_keys,
    }


def collect_app_php_files() -> list:
    result = []
    for entry in sorted(os.listdir(ROOT)):
        full = os.path.join(ROOT, entry)
        if os.path.isfile(full) and entry.endswith(".php"):
            result.append((entry, full))
    inc_dir = os.path.join(ROOT, "includes")
    if os.path.isdir(inc_dir):
        for entry in sorted(os.listdir(inc_dir)):
            full = os.path.join(inc_dir, entry)
            if os.path.isfile(full) and entry.endswith(".php"):
                result.append((f"includes/{entry}", full))
    return result


SQL_KEYWORDS = {
    "SELECT", "FROM", "WHERE", "AND", "OR", "NOT", "NULL", "IS", "IN", "AS",
    "ON", "LEFT", "RIGHT", "INNER", "OUTER", "JOIN", "ORDER", "BY", "GROUP",
    "HAVING", "LIMIT", "OFFSET", "ASC", "DESC", "COUNT", "SUM", "AVG", "MIN",
    "MAX", "COALESCE", "IFNULL", "NOW", "CURDATE", "DATE", "TIMESTAMPDIFF",
    "DATEDIFF", "INTERVAL", "DAY", "HOUR", "MINUTE", "SECOND", "VALUES",
    "INTO", "INSERT", "IGNORE", "UPDATE", "SET", "DELETE", "DISTINCT", "CASE",
    "WHEN", "THEN", "ELSE", "END", "LIKE", "BETWEEN", "EXISTS", "TRUE", "FALSE",
    "DUPLICATE", "KEY", "FOR", "SHOW", "COLUMNS", "TABLES", "CREATE", "TABLE",
    "IF", "ALTER", "ADD", "COLUMN", "PRIMARY", "UNIQUE", "INDEX", "CONSTRAINT",
    "FOREIGN", "REFERENCES", "CASCADE", "RESTRICT", "DEFAULT", "CURRENT_TIMESTAMP",
    "AUTO_INCREMENT", "INT", "VARCHAR", "TEXT", "DATETIME", "TIMESTAMP", "TINYINT",
    "DECIMAL", "ENUM", "CHAR", "ENGINE", "INNODB", "CHARSET", "UTF8MB4", "COLLATE",
    "LOWER", "UPPER", "TRIM", "CONCAT", "DATE_FORMAT", "YEAR", "MONTH",
}


def strip_sql_literals(sql: str) -> str:
    # Replace single-quoted literals in SQL with ''
    return re.sub(r"'(?:[^'\\]|\\.)*'", "''", sql)


def analyze_php_sql_drift(schema_tables: dict, php_files: list) -> dict:
    known_tables = set(schema_tables.keys())
    table_usage = {t: {"SELECT": [], "INSERT": [], "UPDATE": [], "DELETE": [], "DDL": []} for t in known_tables}
    unknown_tables_used = []
    unknown_columns_used = []
    runtime_ddl = []

    # Track per-table column reads & writes
    col_reads = {t: set() for t in known_tables}
    col_writes = {t: set() for t in known_tables}

    # Regex for double or single quoted strings containing SQL verbs
    str_pattern = re.compile(
        r"""(?P<q>["'])(?P<body>(?:\\.|(?!(?P=q)).)*)(?P=q)""",
        re.DOTALL,
    )

    for rel_path, full_path in php_files:
        with open(full_path, "r", encoding="utf-8", errors="replace") as f:
            content = f.read()

        # Strip PHP comments roughly while preserving line numbers
        # Careful not to strip inside strings -> iterate string matches directly on raw content,
        # skipping matches that start inside // or /* */ comments.
        comment_spans = []
        for cm in re.finditer(r"//[^\n]*|/\*.*?\*/", content, re.DOTALL):
            comment_spans.append((cm.start(), cm.end()))

        def in_comment(pos: int) -> bool:
            for s, e in comment_spans:
                if s <= pos < e:
                    return True
                if s > pos:
                    break
            return False

        for m in str_pattern.finditer(content):
            if in_comment(m.start()):
                continue
            raw_sql = m.group("body")
            norm = re.sub(r"\s+", " ", raw_sql).strip()
            if not re.match(
                r"^(SELECT|INSERT|UPDATE|DELETE|CREATE\s+TABLE|ALTER\s+TABLE|SHOW\s+(?:COLUMNS|TABLES)|DROP\s+TABLE)\b",
                norm,
                re.IGNORECASE,
            ):
                continue

            line_no = content.count("\n", 0, m.start()) + 1
            loc = f"{rel_path}:{line_no}"

            # Expand ternary $table in like_handler.php ("buku_likes" / "buku_favorites")
            expanded_sqls = [norm]
            if "$table" in norm and "buku_likes" in content and "buku_favorites" in content:
                expanded_sqls = [
                    norm.replace("$table", "buku_likes"),
                    norm.replace("$table", "buku_favorites"),
                ]

            for exp_norm in expanded_sqls:
                clean = strip_sql_literals(exp_norm)
                # Remove PHP interpolation variables like $uid, {$where}, etc. for identifier parsing
                clean_no_php = re.sub(r"\{\$[^}]+\}|\$[a-zA-Z_][a-zA-Z0-9_]*(?:\[[^\]]*\])?", "?", clean)

                verb = exp_norm.split()[0].upper()
                if verb in ("CREATE", "ALTER", "SHOW", "DROP"):
                    runtime_ddl.append({"location": loc, "sql": exp_norm[:220]})
                    for t in known_tables:
                        if re.search(rf"\b`?{t}`?\b", clean_no_php, re.IGNORECASE):
                            table_usage[t]["DDL"].append(loc)
                    continue

                # Extract table + alias map in this query
                alias_map = {}  # alias -> table_name
                tables_in_q = []

                # INSERT INTO <table> (<cols>)
                m_ins = re.match(
                    r"^INSERT\s+(?:IGNORE\s+)?INTO\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?\s*(?:\(([^)]+)\))?",
                    clean_no_php,
                    re.IGNORECASE,
                )
                if m_ins:
                    tname = m_ins.group(1)
                    tables_in_q.append(tname)
                    alias_map[tname] = tname
                    if tname not in known_tables:
                        unknown_tables_used.append({"location": loc, "table": tname, "sql": exp_norm[:200]})
                    else:
                        table_usage[tname]["INSERT"].append(loc)
                        if m_ins.group(2):
                            for c in m_ins.group(2).split(","):
                                cname = c.strip().strip("`")
                                if cname in schema_tables[tname]["columns"]:
                                    col_writes[tname].add(cname)
                                elif cname and cname != "?":
                                    unknown_columns_used.append(
                                        {"location": loc, "table": tname, "column": cname, "sql": exp_norm[:200]}
                                    )
                        # Check ON DUPLICATE KEY UPDATE
                        m_odku = re.search(r"\bON\s+DUPLICATE\s+KEY\s+UPDATE\s+(.+)$", clean_no_php, re.IGNORECASE)
                        if m_odku:
                            for assign in m_odku.group(1).split(","):
                                m_lhs = re.match(r"^\s*`?([a-zA-Z_][a-zA-Z0-9_]*)`?\s*=", assign)
                                if m_lhs:
                                    cname = m_lhs.group(1)
                                    if cname in schema_tables[tname]["columns"]:
                                        col_writes[tname].add(cname)
                                    else:
                                        unknown_columns_used.append(
                                            {"location": loc, "table": tname, "column": cname, "sql": exp_norm[:200]}
                                        )
                    continue

                # UPDATE <table> SET ...
                m_upd = re.match(
                    r"^UPDATE\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?(?:\s+(?:AS\s+)?([a-zA-Z_][a-zA-Z0-9_]*))?\s+SET\s+(.+)$",
                    clean_no_php,
                    re.IGNORECASE,
                )
                if m_upd and (not m_upd.group(2) or m_upd.group(2).upper() != "SET"):
                    tname = m_upd.group(1)
                    if tname not in known_tables:
                        unknown_tables_used.append({"location": loc, "table": tname, "sql": exp_norm[:200]})
                    else:
                        table_usage[tname]["UPDATE"].append(loc)
                        rest_upd = m_upd.group(3)
                        parts_where = re.split(r"\bWHERE\b", rest_upd, maxsplit=1, flags=re.IGNORECASE)
                        set_clause = parts_where[0]
                        for assign in set_clause.split(","):
                            m_lhs = re.match(r"^\s*`?([a-zA-Z_][a-zA-Z0-9_]*)`?\s*=", assign)
                            if m_lhs:
                                cname = m_lhs.group(1)
                                if cname in schema_tables[tname]["columns"]:
                                    col_writes[tname].add(cname)
                                else:
                                    unknown_columns_used.append(
                                        {"location": loc, "table": tname, "column": cname, "sql": exp_norm[:200]}
                                    )
                        if len(parts_where) > 1:
                            for m_wc in re.finditer(r"`?([a-zA-Z_][a-zA-Z0-9_]*)`?\s*(?:=|<|>|<=|>=|<>|!=|\bIS\b|\bIN\b)", parts_where[1], re.IGNORECASE):
                                cname = m_wc.group(1)
                                if cname.upper() not in SQL_KEYWORDS:
                                    if cname in schema_tables[tname]["columns"]:
                                        col_reads[tname].add(cname)
                                    else:
                                        unknown_columns_used.append(
                                            {"location": loc, "table": tname, "column": cname, "sql": exp_norm[:200]}
                                        )
                    continue

                # SELECT / DELETE: find FROM & JOIN tables + aliases
                for m_tj in re.finditer(
                    r"\b(?:FROM|JOIN)\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?(?:\s+(?:AS\s+)?([a-zA-Z_][a-zA-Z0-9_]*))?",
                    clean_no_php,
                    re.IGNORECASE,
                ):
                    tname = m_tj.group(1)
                    if tname.upper() in SQL_KEYWORDS or tname == "?":
                        continue
                    alias = m_tj.group(2)
                    tables_in_q.append(tname)
                    alias_map[tname] = tname
                    if alias and alias.upper() not in SQL_KEYWORDS:
                        alias_map[alias] = tname
                    if tname not in known_tables:
                        unknown_tables_used.append({"location": loc, "table": tname, "sql": exp_norm[:200]})
                    else:
                        if verb in ("SELECT", "DELETE"):
                            table_usage[tname][verb].append(loc)

                # Qualified columns alias.col
                for m_qcol in re.finditer(r"\b([a-zA-Z_][a-zA-Z0-9_]*)\.`?([a-zA-Z_][a-zA-Z0-9_]*)`?", clean_no_php):
                    al, cname = m_qcol.group(1), m_qcol.group(2)
                    if al in alias_map:
                        real_t = alias_map[al]
                        if real_t in known_tables:
                            if cname in schema_tables[real_t]["columns"]:
                                col_reads[real_t].add(cname)
                            else:
                                unknown_columns_used.append(
                                    {"location": loc, "table": real_t, "column": cname, "sql": exp_norm[:200]}
                                )

                # Single-table SELECT / DELETE unqualified columns
                uniq_tables = list(dict.fromkeys(t for t in tables_in_q if t in known_tables))
                if len(uniq_tables) == 1:
                    only_t = uniq_tables[0]
                    if re.search(r"SELECT\s+\*", clean_no_php, re.IGNORECASE):
                        for cname in schema_tables[only_t]["columns"]:
                            col_reads[only_t].add(cname)
                    # Strip AS <alias> labels so column aliases aren't mistaken for real columns
                    no_as = re.sub(r"\bAS\s+`?[a-zA-Z_][a-zA-Z0-9_]*`?", "", clean_no_php, flags=re.IGNORECASE)
                    for m_tok in re.finditer(r"`?([a-zA-Z_][a-zA-Z0-9_]*)`?", no_as):
                        tok = m_tok.group(1)
                        if tok.upper() in SQL_KEYWORDS or tok == only_t or tok in alias_map:
                            continue
                        if tok in schema_tables[only_t]["columns"]:
                            col_reads[only_t].add(tok)

        # Also scan $row['col'] / $u['col'] / $b['col'] array key accesses across PHP to check column read coverage
        for m_arr in re.finditer(r"\$[a-zA-Z_][a-zA-Z0-9_]*\[\s*['\"]([a-zA-Z_][a-zA-Z0-9_]*)['\"]\s*\]", content):
            key = m_arr.group(1)
            for t in known_tables:
                if key in schema_tables[t]["columns"]:
                    col_reads[t].add(key)

    # Summarize unused tables & unused/unwritten columns
    unused_tables = [
        t for t, ops in table_usage.items()
        if not any(ops[k] for k in ("SELECT", "INSERT", "UPDATE", "DELETE"))
    ]
    column_coverage = {}
    for t, tinfo in schema_tables.items():
        column_coverage[t] = {}
        for cname, cinfo in tinfo["columns"].items():
            is_read = cname in col_reads[t]
            is_written = cname in col_writes[t]
            auto_managed = cinfo["auto_increment"] or (
                cinfo["default"] == "CURRENT_TIMESTAMP"
            )
            column_coverage[t][cname] = {
                "read_in_php": is_read,
                "written_in_php_sql": is_written,
                "auto_managed_by_db": auto_managed,
            }

    return {
        "table_usage_counts": {
            t: {k: len(v) for k, v in ops.items()} for t, ops in table_usage.items()
        },
        "table_usage_locations": table_usage,
        "runtime_ddl_in_php": runtime_ddl,
        "unknown_tables_in_php": unknown_tables_used,
        "unknown_columns_in_php": unknown_columns_used,
        "unused_sql_tables": unused_tables,
        "column_coverage": column_coverage,
    }


def compare_password_otp(main_tables: dict, mig_tables: dict) -> list:
    diffs = []
    t1 = main_tables.get("password_otp")
    t2 = mig_tables.get("password_otp")
    if not t1 or not t2:
        return ["password_otp missing in one of the SQL files"]
    for cname in sorted(set(t1["columns"]) | set(t2["columns"])):
        c1 = t1["columns"].get(cname)
        c2 = t2["columns"].get(cname)
        if not c1 or not c2:
            diffs.append(f"Column {cname} missing in one file")
            continue
        for k in ("type", "nullable", "default", "auto_increment"):
            if str(c1[k]).lower() != str(c2[k]).lower():
                diffs.append(f"password_otp.{cname} {k}: aksa_nova.sql={c1[k]} vs migration={c2[k]}")
    return diffs


def main():
    main_tables = parse_sql_create_tables(SQL_MAIN)
    mig_tables = parse_sql_create_tables(SQL_MIG)
    otp_diffs = compare_password_otp(main_tables, mig_tables)

    php_files = collect_app_php_files()
    drift = analyze_php_sql_drift(main_tables, php_files)

    out = {
        "source_files": {
            "main_sql": "database/aksa_nova.sql",
            "migration_sql": "database/migrations/2026_otp_reset.sql",
        },
        "table_count": len(main_tables),
        "tables": main_tables,
        "migration_tables": mig_tables,
        "password_otp_main_vs_migration_diffs": otp_diffs,
        "drift_analysis": drift,
    }

    with open(OUT_JSON, "w", encoding="utf-8") as f:
        json.dump(out, f, indent=2, ensure_ascii=False)

    print(f"Parsed {len(main_tables)} tables from aksa_nova.sql and {len(mig_tables)} from migration.")
    print(f"password_otp diffs between main & migration: {otp_diffs}")
    print(f"Unknown tables in PHP: {len(drift['unknown_tables_in_php'])}")
    print(f"Unknown columns in PHP: {len(drift['unknown_columns_in_php'])}")
    print(f"Unused SQL tables: {drift['unused_sql_tables']}")
    print(f"Runtime DDL statements in PHP: {len(drift['runtime_ddl_in_php'])}")
    for u in drift["unknown_tables_in_php"]:
        print("  [UNKNOWN TABLE]", u)
    for u in drift["unknown_columns_in_php"]:
        print("  [UNKNOWN COL]", u)
    print("Column coverage highlights (never written & not auto-managed, or never read):")
    for t, cols in drift["column_coverage"].items():
        for cname, cov in cols.items():
            if not cov["read_in_php"] or (not cov["written_in_php_sql"] and not cov["auto_managed_by_db"]):
                print(f"  {t}.{cname}: read={cov['read_in_php']}, written={cov['written_in_php_sql']}, auto={cov['auto_managed_by_db']}")


if __name__ == "__main__":
    main()
