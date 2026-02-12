<?php
// File: config/config.php

// 1. Deteksi Base URL otomatis (Untuk Dashboard MPP sendiri)
// Ini boleh dinamis karena Dashboardnya ada di laptopmu.
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'] . "/mpp/";

// 2. Konfigurasi Lokasi Webapps (SUMBER GAMBAR KHANZA)
// PENTING: Jangan pakai logika if localhost. Tembak langsung ke IP Server Khanza.
// IP ini adalah lokasi fisik dimana folder "webapps/radiologi" berada.
$webapps_url = "https://aplikasi.rssuliawati.com/webapps/"; 

// 3. Path Absolut
define('BASE_PATH', dirname(__DIR__) . '/');

// 4. Security Keys
define('AES_KEY_USER', 'nur');
define('AES_KEY_PASS', 'windi');

// 5. Timezone
date_default_timezone_set('Asia/Jakarta');
?>
