<?php
// [2025-11-16] Selalu beri komentar saat memberikan kode.
// File: config.php
// Fungsi: Menyimpan konfigurasi database dan kredensial BPJS.

date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Database SIMKES Khanza
// Pastikan user/pass ini sesuai dengan server local Anda
define('DB_HOST', '192.168.1.2');
define('DB_USER', 'client'); // Sesuaikan
define('DB_PASS', 'epotoransu');     // Sesuaikan
define('DB_NAME', 'sik_master');  // Sesuaikan nama database Khanza

// Konfigurasi BPJS Antrean FKTP (Dari Memory)
define('BPJS_CONS_ID', '19370');
define('BPJS_SECRET_KEY', '2jXA6F8927');
define('BPJS_USER_KEY', '21de12c1ed2695cdb0627db656914d60'); // Userkey Antrean
define('BPJS_KODE_FASKES', '0131B110');

// Base URL API BPJS (Sesuaikan jika masa testing/production)
// Antrean FKTP biasanya: https://apijkn.bpjs-kesehatan.go.id/antreanfktp
define('BPJS_BASE_URL', 'https://apijkn.bpjs-kesehatan.go.id/antreanfktp');
						 
// Koneksi Database menggunakan PDO
try {
    $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>