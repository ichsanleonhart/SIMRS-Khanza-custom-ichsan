<?php
// [2026-01-27] Konfigurasi ERM (PHP 7.3 Compatible)
date_default_timezone_set('Asia/Jakarta');

// Database Khanza
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sik_master');

// Kredensial BPJS (Gunakan punya VClaim/RS, bukan PCare)
define('BPJS_CONS_ID', '12345'); 
define('BPJS_SECRET_KEY', 'rahasia');
define('BPJS_USER_KEY', 'userkey_anda'); 
define('BPJS_API_URL', 'https://apijkn-dev.bpjs-kesehatan.go.id/erekammedis_dev'); // Ganti URL PROD jika siap

// Koneksi PDO Global
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>