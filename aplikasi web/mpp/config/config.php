<?php
// File: config/config.php

// 1. Deteksi Base URL otomatis
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'] . "/mpp/";

// 2. Konfigurasi Lokasi Webapps (Khanza Original)
// Ganti IP jika di server production. Untuk XAMPP biarkan localhost.
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    $webapps_url = "http://192.168.1.5/webapps/"; 
} else {
    // Sesuaikan IP Public / Domain RS nanti
    $webapps_url = "http://" . $_SERVER['HTTP_HOST'] . "/webapps/";
}

// 3. Path Absolut (PENTING untuk include file PHP)
define('BASE_PATH', dirname(__DIR__) . '/');

// 4. Security Keys (Hardcoded sesuai request)
define('AES_KEY_USER', 'nur');
define('AES_KEY_PASS', 'windi');

// 5. Timezone Indonesia
date_default_timezone_set('Asia/Jakarta');
?>