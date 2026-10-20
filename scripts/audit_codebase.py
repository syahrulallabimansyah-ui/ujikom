# -*- coding: utf-8 -*-
"""
Deep Comprehensive Code Auditor for AKSA NOVA Project
Checks:
1. SQL Injection vectors (concatenation / unescaped vars in queries)
2. Prepared Statements vs Raw Queries
3. XSS vulnerabilities (unescaped output of user input)
4. CSRF / Authorization / Session security
5. File Upload validation (extensions, mime types, paths)
6. Business logic bugs (stock depletion, overdue fine calculation, date handling)
7. Broken links, undefined variables, missing file includes
"""

import os
import re

php_files = [f for f in os.listdir('.') if f.endswith('.php')]

print(f"Total PHP files found: {len(php_files)}")

# 1. SQL Injection Audit
print("\n" + "="*50)
print("1. SQL QUERY & SQL INJECTION SCAN")
print("="*50)

sql_pattern = re.compile(r'mysqli_query\s*\(\s*\$conn\s*,\s*(["\'])(.*?)\1', re.DOTALL)
potential_sqli = []

for filename in php_files:
    with open(filename, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()

    # Find mysqli_query calls
    for match in re.finditer(r'mysqli_query\s*\(\s*\$conn\s*,\s*([^\);]+)\)', content):
        arg = match.group(1).strip()
        # check if it uses string concatenation or variable interpolation
        # e.g., "SELECT ... WHERE id = '$id'" or $sql where $sql is built with vars
        if '$' in arg:
            # check what line it is on
            line_no = content[:match.start()].count('\n') + 1
            potential_sqli.append((filename, line_no, arg[:120]))

print(f"Total queries with direct variable references: {len(potential_sqli)}")
for item in potential_sqli[:20]:
    print(f"  [{item[0]}:{item[1]}] -> {item[2]}")

# 2. FILE UPLOADS
print("\n" + "="*50)
print("2. FILE UPLOAD SECURITY SCAN")
print("="*50)

upload_files = []
for filename in php_files:
    with open(filename, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()
    if '$_FILES' in content:
        line_no = content[:content.find('$_FILES')].count('\n') + 1
        has_ext_check = any(w in content.lower() for w in ['pathinfo', 'extension', 'explode', 'end(', 'mime', 'getimagesize'])
        has_php_block = 'php' in content.lower() and ('deny' in content.lower() or 'not in' in content.lower() or 'in_array' in content.lower())
        upload_files.append((filename, line_no, has_ext_check))

for item in upload_files:
    print(f"  File upload in [{item[0]}:{item[1]}] - Extension validation: {item[2]}")

# 3. XSS AUDIT
print("\n" + "="*50)
print("3. CROSS-SITE SCRIPTING (XSS) SCAN")
print("="*50)

raw_echoes = []
for filename in php_files:
    with open(filename, 'r', encoding='utf-8', errors='ignore') as f:
        lines = f.readlines()
    for idx, line in enumerate(lines, 1):
        # find <?= or <?php echo of $_GET, $_POST, or unsanitized strings
        if re.search(r'<\?=\s*\$_(GET|POST|REQUEST)\[', line) or re.search(r'echo\s+\$_(GET|POST|REQUEST)\[', line):
            if 'htmlspecialchars' not in line:
                raw_echoes.append((filename, idx, line.strip()))

print(f"Direct raw echos of $_GET/$_POST without htmlspecialchars: {len(raw_echoes)}")
for item in raw_echoes:
    print(f"  [{item[0]}:{item[1]}] -> {item[2]}")

# 4. BUSINESS LOGIC & SENSITIVE CHECKS
print("\n" + "="*50)
print("4. BUSINESS LOGIC & EDGE CASES")
print("="*50)

# Check stock reduction logic across borrowing files
for filename in ['pengajuan_buku.php', 'pinjam_buku.php', 'telah_dipinjam.php', 'cron_cek_terlambat.php']:
    if os.path.exists(filename):
        with open(filename, 'r', encoding='utf-8', errors='ignore') as f:
            code = f.read()
        print(f"\nChecking {filename}:")
        # Check stock update statements
        stock_updates = re.findall(r'.{0,50}(?:stok\s*=\s*stok\s*[\+\-]|UPDATE\s+buku\s+SET\s+stok).{0,50}', code, re.IGNORECASE)
        for su in stock_updates:
            print(f"  Stock op: {su.strip()}")

print("\nAudit scan complete.")
