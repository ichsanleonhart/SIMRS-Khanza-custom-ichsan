<?php
// File: config/config.php

// 1. Deteksi Base URL otomatis (Anti-Reverse Proxy / Protocol-Relative)
// Gunakan awalan "//" agar browser otomatis mengikuti HTTP atau HTTPS yang sedang aktif!
$base_url = "//" . $_SERVER['HTTP_HOST'] . "/mpp/";

// 2. Konfigurasi Lokasi Webapps (SUMBER GAMBAR KHANZA)
$webapps_url = "http://localhost/webapps/";

// 3. Path Absolut
define('BASE_PATH', dirname(__DIR__) . '/');

// 4. Security Keys
define('AES_KEY_USER', 'nur');
define('AES_KEY_PASS', 'windi');

// 5. Timezone
date_default_timezone_set('Asia/Jakarta');
?>