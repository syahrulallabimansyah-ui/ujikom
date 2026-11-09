<?php
declare(strict_types=1);

/**
 * 01_php_fact_extractor.php
 * Ekstraktor fakta kode mekanis berbasis PHP native lexer (token_get_all).
 * READ-ONLY terhadap seluruh proyek; hanya menulis ke docs/audit_v2/02_fakta_kode.json.
 * Nilai rahasia (password/API key/secret) disanitasi menjadi "[REDACTED]".
 */

$root = realpath(__DIR__ . '/../../..');
if ($root === false) {
    fwrite(STDERR, "Gagal menemukan root proyek.\n");
    exit(1);
}

$skipDirs = ['.git', 'lib', 'ERD', 'SRS', 'UML', 'docs', '_karantina', 'uploads', 'cache', 'storage', 'database'];

function collectPhpFiles(string $dir, string $root, array $skipDirs): array {
    $result = [];
    $items = scandir($dir);
    if ($items === false) return $result;
    sort($items);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        $rel  = str_replace('\\', '/', substr($full, strlen($root) + 1));
        if (is_dir($full)) {
            if (in_array($item, $skipDirs, true)) continue;
            $result = array_merge($result, collectPhpFiles($full, $root, $skipDirs));
        } elseif (is_file($full) && strtolower(pathinfo($item, PATHINFO_EXTENSION)) === 'php') {
            $result[] = ['full' => $full, 'rel' => $rel];
        }
    }
    return $result;
}

$phpFiles = collectPhpFiles($root, $root, $skipDirs);

$sensitiveConstants = ['MAIL_PASSWORD', 'OTP_SECRET', 'GOOGLE_BOOKS_API_KEY'];

$securityFuncs = [
    'session_start', 'session_regenerate_id', 'session_destroy', 'session_unset',
    'csrf_verify', 'csrf_field', 'csrf_token',
    'password_hash', 'password_verify',
    'hash_hmac', 'hash_equals', 'random_int', 'random_bytes', 'bin2hex',
    'filter_var', 'htmlspecialchars', 'preg_match'
];

$fileOpFuncs = [
    'move_uploaded_file', 'unlink', 'rename', 'copy',
    'file_put_contents', 'file_get_contents', 'mkdir', 'fopen',
    'curl_init', 'curl_exec'
];

$dbFuncs = [
    'mysqli_connect', 'mysqli_query', 'mysqli_prepare', 'mysqli_begin_transaction', 'mysqli_commit', 'mysqli_rollback'
];

function prevNonWs(array $tokens, int $idx): ?array {
    for ($i = $idx - 1; $i >= 0; $i--) {
        $t = $tokens[$i];
        if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) continue;
        return is_array($t) ? $t : [0, $t, -1];
    }
    return null;
}

function nextNonWs(array $tokens, int $idx, int &$outIdx = -1): ?array {
    $n = count($tokens);
    for ($i = $idx + 1; $i < $n; $i++) {
        $t = $tokens[$i];
        if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) continue;
        $outIdx = $i;
        return is_array($t) ? $t : [0, $t, -1];
    }
    return null;
}

function extractSqlInfo(string $rawStr): ?array {
    $clean = trim(preg_replace('/\s+/', ' ', $rawStr));
    if (!preg_match('/^(SELECT|INSERT\s+INTO|INSERT\s+IGNORE\s+INTO|UPDATE|DELETE\s+FROM|CREATE\s+TABLE|ALTER\s+TABLE|DROP\s+TABLE|SHOW\s+TABLES|SHOW\s+COLUMNS)\b/i', $clean, $m)) {
        return null;
    }
    $verb = strtoupper(preg_replace('/\s+/', ' ', $m[1]));
    $tables = [];
    if (preg_match_all('/\b(?:FROM|JOIN|INTO|UPDATE|TABLE)\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i', $clean, $tm)) {
        foreach ($tm[1] as $tb) {
            $tbLow = strtolower($tb);
            if (!in_array($tbLow, ['select', 'set', 'where', 'values', 'null', 'dual'], true)) {
                $tables[] = $tbLow;
            }
        }
    }
    return [
        'type' => $verb,
        'tables' => array_values(array_unique($tables)),
        'sql_preview' => mb_substr($clean, 0, 260)
    ];
}

$output = [
    'generated_at' => date('c'),
    'php_version_used' => PHP_VERSION,
    'total_files' => count($phpFiles),
    'files' => []
];

foreach ($phpFiles as $fInfo) {
    $code = file_get_contents($fInfo['full']);
    if ($code === false) continue;
    $lines = substr_count($code, "\n") + 1;
    $tokens = token_get_all($code);
    $count = count($tokens);

    $strictTypes = false;
    $includes = [];
    $superglobals = ['_GET' => [], '_POST' => [], '_FILES' => [], '_SESSION' => [], '_SERVER' => [], '_COOKIE' => [], '_REQUEST' => []];
    $functionsDefined = [];
    $securityCalls = [];
    $fileOps = [];
    $dbCalls = [];
    $sqlQueries = [];
    $headers = [];
    $constants = [];
    $externalUrls = [];

    for ($i = 0; $i < $count; $i++) {
        $tok = $tokens[$i];
        if (!is_array($tok)) continue;
        [$id, $text, $line] = $tok;

        // declare(strict_types=1)
        if ($id === T_DECLARE) {
            $slice = '';
            for ($k = $i; $k < min($count, $i + 12); $k++) {
                $slice .= is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];
            }
            if (stripos($slice, 'strict_types') !== false && strpos($slice, '1') !== false) {
                $strictTypes = true;
            }
        }

        // include / require
        if (in_array($id, [T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE], true)) {
            $expr = '';
            for ($k = $i + 1; $k < min($count, $i + 20); $k++) {
                $tk = $tokens[$k];
                $s = is_array($tk) ? $tk[1] : $tk;
                if ($s === ';') break;
                $expr .= $s;
            }
            $includes[] = ['kind' => strtolower($text), 'target_expr' => trim($expr), 'line' => $line];
        }

        // function definitions
        if ($id === T_FUNCTION) {
            $nxIdx = -1;
            $nx = nextNonWs($tokens, $i, $nxIdx);
            if ($nx && $nx[0] === T_STRING) {
                $functionsDefined[] = ['name' => $nx[1], 'line' => $line];
            }
        }

        // superglobals $_GET['x'] etc.
        if ($id === T_VARIABLE) {
            $varName = substr($text, 1);
            if (isset($superglobals[$varName])) {
                $nxIdx = -1;
                $nx = nextNonWs($tokens, $i, $nxIdx);
                $key = '*';
                if ($nx && $nx[1] === '[') {
                    $kTokIdx = -1;
                    $kTok = nextNonWs($tokens, $nxIdx, $kTokIdx);
                    if ($kTok && $kTok[0] === T_CONSTANT_ENCAPSED_STRING) {
                        $key = trim($kTok[1], "'\"");
                    } elseif ($kTok) {
                        $key = $kTok[1];
                    }
                }
                $superglobals[$varName][] = ['key' => $key, 'line' => $line];
            }
        }

        // function calls (T_STRING followed by '(')
        if ($id === T_STRING) {
            $fnLow = strtolower($text);
            $prev = prevNonWs($tokens, $i);
            $nxIdx = -1;
            $nx = nextNonWs($tokens, $i, $nxIdx);
            $isCall = ($nx && $nx[1] === '(') && (!$prev || $prev[0] !== T_FUNCTION);

            if ($isCall) {
                if (in_array($fnLow, $securityFuncs, true)) {
                    $securityCalls[] = ['func' => $fnLow, 'line' => $line];
                }
                if (in_array($fnLow, $fileOpFuncs, true)) {
                    $fileOps[] = ['func' => $fnLow, 'line' => $line];
                }
                if (in_array($fnLow, $dbFuncs, true)) {
                    $dbCalls[] = ['func' => $fnLow, 'line' => $line];
                }
                if ($fnLow === 'header') {
                    $arg = '';
                    for ($k = $nxIdx + 1; $k < min($count, $nxIdx + 25); $k++) {
                        $tk = $tokens[$k];
                        $s = is_array($tk) ? $tk[1] : $tk;
                        if ($s === ';' || $s === ')') break;
                        $arg .= $s;
                    }
                    $headers[] = ['arg' => trim($arg), 'line' => $line];
                }
                if ($fnLow === 'define') {
                    $cIdx = -1;
                    $cTok = nextNonWs($tokens, $nxIdx, $cIdx);
                    if ($cTok && $cTok[0] === T_CONSTANT_ENCAPSED_STRING) {
                        $cName = trim($cTok[1], "'\"");
                        $valPreview = '[EXPR]';
                        if (in_array($cName, $sensitiveConstants, true)) {
                            $valPreview = '[REDACTED_SECRET]';
                        } else {
                            $commaIdx = -1;
                            $comma = nextNonWs($tokens, $cIdx, $commaIdx);
                            if ($comma && $comma[1] === ',') {
                                $vIdx = -1;
                                $vTok = nextNonWs($tokens, $commaIdx, $vIdx);
                                if ($vTok) {
                                    $valPreview = $vTok[1];
                                    if (preg_match('/AIzaSy[A-Za-z0-9_\-]+/', $valPreview)) {
                                        $valPreview = '[REDACTED_API_KEY]';
                                    }
                                }
                            }
                        }
                        $constants[] = ['name' => $cName, 'value' => $valPreview, 'line' => $line];
                    }
                }
            }
        }

        // String literals: check SQL queries & external URLs (redact API keys)
        if ($id === T_CONSTANT_ENCAPSED_STRING || $id === T_ENCAPSED_AND_WHITESPACE) {
            $unquoted = ($id === T_CONSTANT_ENCAPSED_STRING) ? substr($text, 1, -1) : $text;
            $sqlInfo = extractSqlInfo($unquoted);
            if ($sqlInfo !== null) {
                $sqlInfo['line'] = $line;
                $sqlQueries[] = $sqlInfo;
            }
            if (preg_match_all('#https?://[^\s\'"<>\\\\]+#i', $unquoted, $um)) {
                foreach ($um[0] as $u) {
                    if (stripos($u, 'w3.org/') !== false) continue;
                    $uSafe = preg_replace('/AIzaSy[A-Za-z0-9_\-]+/', '[REDACTED]', $u);
                    $externalUrls[] = ['url' => $uSafe, 'line' => $line];
                }
            }
        }

        // Inline HTML: check external URLs, forms, fetch() calls
        if ($id === T_INLINE_HTML) {
            $baseLine = $line;
            $htmlLines = explode("\n", $text);
            foreach ($htmlLines as $offset => $hl) {
                $curLine = $baseLine + $offset;
                if (preg_match_all('#https?://[^\s\'"<>\\\\]+#i', $hl, $um)) {
                    foreach ($um[0] as $u) {
                        if (stripos($u, 'w3.org/') !== false || stripos($u, 'fonts.googleapis.com') !== false || stripos($u, 'fonts.gstatic.com') !== false) continue;
                        $uSafe = preg_replace('/AIzaSy[A-Za-z0-9_\-]+/', '[REDACTED]', $u);
                        $externalUrls[] = ['url' => $uSafe, 'line' => $curLine];
                    }
                }
            }
        }
    }

    // Also scan raw lines for fetch('...php') and <form ... action="...">
    $rawLines = explode("\n", $code);
    $htmlForms = [];
    $jsFetches = [];
    foreach ($rawLines as $idx => $rl) {
        $ln = $idx + 1;
        if (preg_match('/<form\b[^>]*>/i', $rl, $fm)) {
            $method = 'GET';
            $action = '(self)';
            if (preg_match('/method\s*=\s*[\'"]?([a-zA-Z]+)/i', $fm[0], $mm)) $method = strtoupper($mm[1]);
            if (preg_match('/action\s*=\s*[\'"]([^\'"]*)[\'"]/i', $fm[0], $am)) $action = $am[1] === '' ? '(self)' : $am[1];
            $htmlForms[] = ['method' => $method, 'action' => $action, 'line' => $ln];
        }
        if (preg_match_all('/\bfetch\s*\(\s*[\'"`]([^\'"`]+)[\'"`]/i', $rl, $fcm)) {
            foreach ($fcm[1] as $furl) {
                $jsFetches[] = ['target' => $furl, 'line' => $ln];
            }
        }
    }

    // Summarize superglobals uniquely per key with line list
    $sgSummary = [];
    foreach ($superglobals as $sgName => $entries) {
        if (empty($entries)) continue;
        $byKey = [];
        foreach ($entries as $e) {
            $byKey[$e['key']][] = $e['line'];
        }
        foreach ($byKey as $k => $lns) {
            $sgSummary['$' . $sgName][$k] = array_values(array_unique($lns));
        }
    }

    $output['files'][$fInfo['rel']] = [
        'path' => $fInfo['rel'],
        'size_bytes' => filesize($fInfo['full']),
        'line_count' => $lines,
        'strict_types' => $strictTypes,
        'includes_requires' => $includes,
        'functions_defined' => $functionsDefined,
        'constants_defined' => $constants,
        'superglobals' => $sgSummary,
        'security_calls' => $securityCalls,
        'db_calls' => $dbCalls,
        'sql_queries' => $sqlQueries,
        'headers_redirects' => $headers,
        'file_ops' => $fileOps,
        'external_urls' => $externalUrls,
        'html_forms' => $htmlForms,
        'js_fetches' => $jsFetches
    ];
}

$outPath = $root . '/docs/audit_v2/02_fakta_kode.json';
file_put_contents($outPath, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
echo "Extracted facts for " . count($output['files']) . " PHP files -> docs/audit_v2/02_fakta_kode.json\n";
