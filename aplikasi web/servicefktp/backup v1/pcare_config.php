<?php
// [2025-11-16] Selalu beri komentar.
// File: pcare_config.php
// Fungsi: Konfigurasi khusus bridging PCare (Beda User/Pass dengan Antrol).

date_default_timezone_set('Asia/Jakarta');

// Database Khanza
define('DB_HOST', '192.168.1.2'); // Sesuaikan IP Server
define('DB_PORT', '3306');
define('DB_USER', 'client');      // Sesuaikan User
define('DB_PASS', 'epotoransu');          // Sesuaikan Pass
define('DB_NAME', 'sik_master');       // Sesuaikan Nama DB

// Kredensial BPJS PCare
define('PCARE_CONS_ID', '19370');
define('PCARE_SECRET_KEY', '2jXA6F8927');
define('PCARE_USER_KEY', '522676814cb2c0d6109a05f75c923763'); 
define('PCARE_USERNAME', 'Ichsan.maulana');
define('PCARE_PASSWORD', 'Saneli@123');
define('PCARE_KODE_FASKES', '0131B110');

// [REVISI FATAL] Gunakan URL Legacy sesuai XML Java User
// Jangan pakai v3.0 jika Java tidak pakai.
define('PCARE_BASE_URL', 'https://apijkn.bpjs-kesehatan.go.id/pcare-rest');

// Koneksi PDO
try {
    $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 10,
    ]);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>