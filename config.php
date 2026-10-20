<?php
/**
 * config.php — Konfigurasi Lingkungan & Kunci API Terpusat AKSA NOVA
 *
 * File ini menyimpan konfigurasi global dan kunci API eksternal agar tidak
 * tercecer di berbagai file PHP atau terekspos di repo publik.
 */

// Kunci Google Books API untuk pencarian metadata & cover otomatis via ISBN
if (!defined('GOOGLE_BOOKS_API_KEY')) {
    define('GOOGLE_BOOKS_API_KEY', 'AIzaSyAGnEXPAvJtnKpSkkInJ3oAzp-PXfgHZbw');
}
