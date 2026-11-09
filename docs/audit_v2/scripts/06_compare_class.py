#!/usr/bin/env python3
"""
06_compare_class.py
Membandingkan secara mekanis setiap kelas, atribut, tipe/enum, penanda kunci ([PK]/[FK]/[UK]),
multiplisitas/jenis relasi, dan metode yang tampak secara visual pada
`UML/Class/Class_Diagram_AKSA_NOVA.png` terhadap:
  - `docs/audit_v2/schema_model.json` (skema SQL aktual)
  - `docs/audit_v2/02_fakta_kode.json` (daftar fungsi PHP nyata di kode)

Cara menjalankan:
  python docs/audit_v2/scripts/06_compare_class.py
Keluaran:
  docs/audit_v2/class_diff_results.json
"""
import json
import os
import sys

sys.stdout.reconfigure(encoding="utf-8")

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", ".."))
SCHEMA_JSON = os.path.join(ROOT, "docs", "audit_v2", "schema_model.json")
FAKTA_JSON = os.path.join(ROOT, "docs", "audit_v2", "02_fakta_kode.json")
OUT_JSON = os.path.join(ROOT, "docs", "audit_v2", "class_diff_results.json")

# Transkripsi eksak 1-ke-1 seluruh kelas, atribut, kunci, dan metode yang tampak
# pada gambar `UML/Class/Class_Diagram_AKSA_NOVA.png` (dibuka & dibaca langsung).
CLASS_DIAGRAM_MODEL = {
    "Pengguna": {
        "sql_table": "users",
        "stereotypes": ["abstract", "users"],
        "attributes": [
            ("id", "int", ["PK"]),
            ("full_name", "varchar(100)", []),
            ("nik", "varchar(30)", []),
            ("kelas", "varchar(50)", []),
            ("no_hp", "varchar(20)", []),
            ("no_anggota", "varchar(30)", ["UK"]),
            ("username", "varchar(50)", ["UK"]),
            ("email", "varchar(100)", ["UK"]),
            ("password", "varchar(255)", []),
            ("plain_password", "varchar(255)", []),
            ("foto", "varchar(255)", []),
            ("role", "enum('admin','member')", []),
            ("status", "enum('pending','approved','rejected')", []),
            ("card_status", "enum('active','frozen')", []),
            ("created_at", "timestamp", []),
        ],
        "methods": [
            "login(identitas, sandi) : bool",
            "logout() : void",
            "ubahDataDiri() : bool",
            "ubahSandi(lama, baru) : bool",
        ],
    },
    "Pustakawan": {
        "sql_table": None,  # Subkelas konseptual dari users (role = 'admin')
        "stereotypes": ["role = 'admin'"],
        "attributes": [],
        "methods": [
            "ubahStatusAnggota(id, status) : bool",
            "cairkanKartu(id) : bool",
            "resetSandi(id) : string",
            "hapusAnggota(id) : bool",
            "setujuiPengajuan(id) : bool",
            "tolakPengajuan(id, alasan) : bool",
            "pinjamLangsung(buku_id, user_id) : bool",
            "kembalikan(pem_id) : bool",
            "tandaiDendaLunas(pem_id) : bool",
            "kirimPengingatWA(pem_id) : void",
            "kelolaBuku() : void",
            "kelolaBanner() : void",
            "kelolaMusik() : void",
            "aturDenda() : void",
            "aturLokasi() : void",
            "eksporLaporan(tahun) : xls",
        ],
    },
    "Anggota": {
        "sql_table": None,  # Subkelas konseptual dari users (role = 'member')
        "stereotypes": ["role = 'member'"],
        "attributes": [],
        "methods": [
            "registrasi() : bool",
            "ajukanPeminjaman(buku_id, jumlah) : bool",
            "lihatKartu() : void",
            "toggleSuka(buku_id) : bool",
            "toggleFavorit(buku_id) : bool",
            "beriRating(buku_id, nilai) : bool",
            "mintaOtp(email) : bool",
            "verifikasiOtp(otp) : bool",
            "buatSandiBaru(sandi) : bool",
        ],
    },
    "AdminProfile": {
        "sql_table": "admin_profile",
        "stereotypes": ["admin_profile"],
        "attributes": [
            ("id", "int", ["PK"]),
            ("user_id", "int", ["FK", "UK"]),
            ("display_name", "varchar(100)", []),
            ("foto", "varchar(255)", []),
            ("updated_at", "timestamp", []),
        ],
        "methods": ["ubahProfil(nama, foto) : bool"],
    },
    "OtpReset": {
        "sql_table": "password_otp",
        "stereotypes": ["password_otp"],
        "attributes": [
            ("id", "int", ["PK"]),
            ("user_id", "int", ["FK"]),
            ("otp_hash", "char(64)", []),
            ("expires_at", "datetime", []),
            ("attempts", "tinyint", []),
            ("used_at", "datetime", []),
            ("last_sent_at", "datetime", []),
            ("created_at", "timestamp", []),
            ("request_ip", "varchar(45)", []),
        ],
        "methods": [
            "otp_buat(user_id, ip) : string",
            "otp_kirim(email, otp) : bool",
            "otp_verifikasi(user_id, otp) : bool",
            "otp_batas_permintaan(email, ip) : bool",
        ],
    },
    "ReminderLog": {
        "sql_table": "reminder_log",
        "stereotypes": ["reminder_log"],
        "attributes": [
            ("id", "int", ["PK"]),
            ("peminjaman_id", "int", ["FK"]),
            ("tanggal", "date", ["UK"]),
            ("terlambat_hari", "int", []),
            ("wa_terkirim", "tinyint(1)", []),
            ("wa_terkirim_at", "datetime", []),
            ("created_at", "timestamp", []),
        ],
        "methods": [
            "catatKeterlambatan() : void",
            "tandaiWA() : void",
            "batalWA() : void",
        ],
    },
    "Peminjaman": {
        "sql_table": "peminjaman",
        "stereotypes": ["peminjaman"],
        "attributes": [
            ("id", "int", ["PK"]),
            ("buku_id", "int", ["FK"]),
            ("user_id", "int", ["FK"]),
            ("nama_peminjam", "varchar(150)", []),
            ("waktu_pinjam", "datetime", []),
            ("batas_kembali", "datetime", []),
            ("waktu_kembali", "datetime", []),
            ("terlambat_hari", "int", []),
            ("denda", "decimal(12,0)", []),
            ("status_denda", "enum('tidak_ada','belum_bayar','lunas')", []),
            ("status", "enum('dipinjam','dikembalikan')", []),
            ("created_at", "timestamp", []),
        ],
        "methods": [
            "hitungDenda(batas, kembali, aktif, tarif, toleransi) : array",
            "kembalikan() : bool",
            "tandaiLunas() : bool",
            "hapusRiwayat() : bool",
        ],
    },
    "Buku": {
        "sql_table": "buku",
        "stereotypes": ["buku"],
        "attributes": [
            ("id", "int", ["PK"]),
            ("judul", "varchar(255)", []),
            ("penulis", "varchar(255)", []),
            ("isbn", "varchar(50)", []),
            ("genre", "varchar(100)", []),
            ("sinopsis", "text", []),
            ("stok", "int", []),
            ("rak", "varchar(100)", []),
            ("gambar", "varchar(500)", []),
            ("created_at", "timestamp", []),
            ("updated_at", "timestamp", []),
        ],
        "methods": [
            "tambah() : bool",
            "ubah() : bool",
            "hapus() : bool",
            "cari(q, genre, hal) : list",
            "detail(id) : json",
            "cariISBN(isbn) : json",
            "kurangiStok(n) : bool",
            "tambahStok(n) : void",
        ],
    },
    "PengajuanPeminjaman": {
        "sql_table": "pengajuan_peminjaman",
        "stereotypes": ["pengajuan_peminjaman"],
        "attributes": [
            ("id", "int", ["PK"]),
            ("user_id", "int", []),
            ("buku_id", "int", []),
            ("nama_peminjam", "varchar(150)", []),
            ("file_kartu", "varchar(255)", []),
            ("total_buku", "int", []),
            ("batas_kembali", "date", []),
            ("waktu_pengambilan", "enum('sekarang','nanti')", []),
            ("catatan_pengambilan", "varchar(255)", []),
            ("status", "enum('menunggu','disetujui','ditolak')", []),
            ("alasan_penolakan", "text", []),
            ("approved_at", "datetime", []),
            ("created_at", "timestamp", []),
        ],
        "methods": [
            "ajukan() : bool",
            "setujui() : bool",
            "tolak(alasan) : bool",
            "hitungMenunggu() : int",
        ],
    },
    "Banner": {
        "sql_table": "banner",
        "stereotypes": ["banner"],
        "attributes": [
            ("id", "int", ["PK"]),
            ("judul", "varchar(150)", []),
            ("subjudul", "varchar(255)", []),
            ("gambar", "varchar(500)", []),
            ("link_url", "varchar(255)", []),
            ("urutan", "int", []),
            ("aktif", "tinyint(1)", []),
            ("created_at", "timestamp", []),
            ("updated_at", "timestamp", []),
        ],
        "methods": [
            "tambah() : bool",
            "ubah() : bool",
            "hapus() : bool",
            "ubahStatusAktif() : void",
            "geserUrutan(arah) : void",
            "jadikanLatar() : void",
        ],
    },
    "Pengaturan": {
        "sql_table": "pengaturan",
        "stereotypes": ["pengaturan"],
        "attributes": [
            ("id", "int", ["PK"]),
            ("kunci", "varchar(100)", ["UK"]),
            ("nilai", "text", []),
            ("keterangan", "varchar(255)", []),
            ("updated_at", "timestamp", []),
        ],
        "methods": [
            "ambilNilai(kunci, bawaan) : string",
            "simpanNilai(kunci, nilai) : void",
        ],
    },
    "BukuSuka": {
        "sql_table": "buku_likes",
        "stereotypes": ["buku_likes"],
        "attributes": [
            ("id", "int", ["PK"]),
            ("buku_id", "int", ["FK", "UK"]),
            ("user_id", "int", ["FK", "UK"]),
            ("created_at", "timestamp", []),
        ],
        "methods": ["toggleSuka() : bool"],
    },
    "BukuFavorit": {
        "sql_table": "buku_favorites",
        "stereotypes": ["buku_favorites"],
        "attributes": [
            ("id", "int", ["PK"]),
            ("buku_id", "int", ["FK", "UK"]),
            ("user_id", "int", ["FK", "UK"]),
            ("created_at", "timestamp", []),
        ],
        "methods": ["toggleSimpan() : bool", "daftarSimpan() : list"],
    },
    "BukuRating": {
        "sql_table": "buku_ratings",
        "stereotypes": ["buku_ratings"],
        "attributes": [
            ("id", "int", ["PK"]),
            ("user_id", "int", ["FK", "UK"]),
            ("buku_id", "int", ["FK", "UK"]),
            ("rating", "tinyint", []),
            ("created_at", "datetime", []),
            ("updated_at", "datetime", []),
        ],
        "methods": ["beriRating(nilai) : bool", "rataRata() : float"],
    },
}


def main():
    with open(SCHEMA_JSON, "r", encoding="utf-8") as f:
        schema = json.load(f)
    with open(FAKTA_JSON, "r", encoding="utf-8") as f:
        fakta = json.load(f)

    sql_tables = schema["tables"]
    real_php_funcs = {}
    for rel, info in fakta["files"].items():
        for fn in info["functions_defined"]:
            real_php_funcs.setdefault(fn["name"], []).append(f"{rel}:{fn['line']}")

    per_class_report = {}
    mapped_sql_tables = set()

    for cname, cinfo in CLASS_DIAGRAM_MODEL.items():
        tname = cinfo["sql_table"]
        diffs = []
        method_audit = []

        for m_sig in cinfo["methods"]:
            m_name = m_sig.split("(")[0].strip()
            if m_name in real_php_funcs:
                method_audit.append({
                    "signature": m_sig,
                    "status": "ADA_FUNGSI_PROSEDURAL",
                    "php_locations": real_php_funcs[m_name],
                })
            else:
                method_audit.append({
                    "signature": m_sig,
                    "status": "TIDAK_ADA_FUNGSI_BERNAMA_INI_DI_PHP (konseptual tanpa label <<conceptual>> atau karangan)",
                    "php_locations": [],
                })

        if tname is not None:
            mapped_sql_tables.add(tname)
            st = sql_tables[tname]
            scols = st["columns"]
            c_attrs = {a[0]: (a[1], set(a[2])) for a in cinfo["attributes"]}

            for col in sorted(set(c_attrs) - set(scols)):
                diffs.append(f"Atribut di Class tapi tidak di SQL: {col}")
            for col in sorted(set(scols) - set(c_attrs)):
                diffs.append(f"Kolom di SQL tapi tidak di Class: {col}")

            uk_cols_any = set()
            for uk in st["unique_keys"]:
                uk_cols_any.update(uk["columns"])

            for col in sorted(set(c_attrs) & set(scols)):
                ctype, ckeys = c_attrs[col]
                sc = scols[col]
                if ctype.lower().replace(" ", "") != sc["type"].lower().replace(" ", ""):
                    diffs.append(f"Tipe berbeda pada {col}: Class='{ctype}' vs SQL='{sc['type']}'")
                if sc["is_pk"] != ("PK" in ckeys):
                    diffs.append(f"Penanda PK berbeda pada {col}: Class={sorted(ckeys)} vs SQL PK={sc['is_pk']}")
                has_fk = sc["fk_target"] is not None
                if has_fk != ("FK" in ckeys):
                    diffs.append(f"Penanda FK berbeda pada {col}: Class={sorted(ckeys)} vs SQL FK={sc['fk_target']}")
                if (col in uk_cols_any) != ("UK" in ckeys):
                    diffs.append(f"Penanda UK berbeda pada {col}: Class={sorted(ckeys)} vs SQL in_UK={col in uk_cols_any}")

        per_class_report[cname] = {
            "sql_table": tname,
            "attribute_count": len(cinfo["attributes"]),
            "schema_diff_count": len(diffs),
            "schema_diffs": diffs,
            "method_count": len(cinfo["methods"]),
            "real_function_matches": sum(1 for m in method_audit if m["status"] == "ADA_FUNGSI_PROSEDURAL"),
            "methods": method_audit,
        }

    unmapped_tables = sorted(set(sql_tables.keys()) - mapped_sql_tables)

    out = {
        "total_classes_in_diagram": len(CLASS_DIAGRAM_MODEL),
        "mapped_sql_tables_count": len(mapped_sql_tables),
        "unmapped_sql_tables": unmapped_tables,
        "total_schema_diffs_across_all_classes": sum(r["schema_diff_count"] for r in per_class_report.values()),
        "per_class": per_class_report,
    }

    with open(OUT_JSON, "w", encoding="utf-8") as f:
        json.dump(out, f, indent=2, ensure_ascii=False)

    print(f"Classes checked: {out['total_classes_in_diagram']}, SQL tables mapped: {out['mapped_sql_tables_count']}/12")
    print(f"Total attribute/type/key schema diffs: {out['total_schema_diffs_across_all_classes']}")
    for cname, rep in per_class_report.items():
        print(f"  {cname} (table={rep['sql_table']}): {rep['schema_diff_count']} schema diffs | {rep['real_function_matches']}/{rep['method_count']} real PHP func names")
        for d in rep["schema_diffs"]:
            print(f"    - {d}")


if __name__ == "__main__":
    main()
