<?php
/*
 * File koneksi.php (PERBAIKAN)
 * Memulai session dengan aman di satu tempat.
 */

// 1. Mulai Session dengan Aman
// Komentar: Cek dulu apakah session BELUM dimulai
if (session_status() == PHP_SESSION_NONE) {
    /*
     * Komentar: Ganti 'false' (secure) menjadi 'true' saat nanti
     * Anda deploy di internet (production) menggunakan HTTPS.
     * Untuk 'pc lokal' (http://), 'false' sudah benar.
     */
    session_set_cookie_params(0, '/', '', false, true); // (lifetime, path, domain, secure, httponly)
    session_start();
}

// 2. Pengaturan Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 3. Detail Koneksi Database (Sesuai info Anda)
define('DB_HOST', '192.168.0.2');
define('DB_USER', 'client');        
define('DB_PASS', 'epotoransu');    
define('DB_NAME', 'sik_master');           

// 4. Buat Koneksi menggunakan MySQLi
$koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// 5. Cek Koneksi
if ($koneksi->connect_error) {
    die("Koneksi Gagal: " . $koneksi->connect_error);
}

// 6. Set Charset
$koneksi->set_charset("utf8mb4");

// 7. Set Timezone
date_default_timezone_set('Asia/Jakarta');

?>