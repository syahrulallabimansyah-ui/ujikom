<?php
/**
 * config.php — Konfigurasi Lingkungan, Keamanan & Kunci API Terpusat AKSA NOVA
 *
 * File ini menyimpan konfigurasi global, kunci API eksternal, dan helper keamanan
 * (CSRF Protection & Session Hardening) untuk seluruh modul aplikasi.
 */

// ─── 0. Zona Waktu Standar WIB (Asia/Jakarta) ───
date_default_timezone_set('Asia/Jakarta');

// ─── 1. Session Cookie Hardening ───
if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.use_only_cookies', '1');
    @ini_set('session.cookie_samesite', 'Lax');
}

// ─── 2. Kunci Google Books API ───
// Mendukung pembacaan dari environment variable server atau fallback ke kunci default
if (!defined('GOOGLE_BOOKS_API_KEY')) {
    $env_key = getenv('GOOGLE_BOOKS_API_KEY');
    define('GOOGLE_BOOKS_API_KEY', $env_key ? $env_key : 'AIzaSyAGnEXPAvJtnKpSkkInJ3oAzp-PXfgHZbw');
}

// ─── 3. Proteksi CSRF (Cross-Site Request Forgery) ───
if (!function_exists('csrf_token')) {
    /**
     * Mengambil atau membuat token CSRF unik untuk sesi saat ini
     */
    function csrf_token(): string {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Menghasilkan input hidden HTML berisi token CSRF untuk formulir POST
     */
    function csrf_field(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_verify')) {
    /**
     * Memvalidasi keabsahan token CSRF dari request POST atau header HTTP
     */
    function csrf_verify(?string $token = null): bool {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $submitted = $token ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        $expected  = $_SESSION['csrf_token'] ?? '';
        return (!empty($expected) && !empty($submitted) && hash_equals($expected, $submitted));
    }
}
/**
 * Masking / Enkripsi ID integer menjadi token string URL-safe (mencegah parameter tampering & enumeration)
 */
if (!function_exists('mask_id')) {
    function mask_id($id): string {
        $id = (int)$id;
        if ($id <= 0) return '';
        $secret = 0x5A7B3C1D;
        $obf = ($id ^ $secret);
        $check = substr(md5("aksa_salt_" . $id), 0, 4);
        return rtrim(strtr(base64_encode($obf . ":" . $check), '+/', '-_'), '=');
    }
}

/**
 * Unmasking / Dekripsi token string URL-safe kembali ke ID integer asli
 */
if (!function_exists('unmask_id')) {
    function unmask_id($hash): int {
        if (is_numeric($hash)) {
            return (int)$hash;
        }
        if (empty($hash) || !is_string($hash)) return 0;
        $pad = strlen($hash) % 4;
        if ($pad > 0) {
            $hash .= str_repeat('=', 4 - $pad);
        }
        $raw = base64_decode(strtr($hash, '-_', '+/'));
        if (!$raw || strpos($raw, ':') === false) return 0;
        list($obf, $check) = explode(':', $raw, 2);
        $secret = 0x5A7B3C1D;
        $id = ((int)$obf ^ $secret);
        if ($id <= 0) return 0;
        $expectedCheck = substr(md5("aksa_salt_" . $id), 0, 4);
        if ($check !== $expectedCheck) return 0;
        return $id;
    }
}
