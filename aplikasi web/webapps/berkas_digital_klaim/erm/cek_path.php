<?php
echo "<h3>Diagnosa Path & Permission</h3>";

// 1. Cek Posisi Script Saat Ini
echo "Posisi Script: " . __DIR__ . "<br>";

// 2. Cek Target Vendor
$target = dirname(__DIR__) . '/vendor/autoload.php';
echo "Mencari Vendor di: " . $target . "<br><br>";

// 3. Cek Status File
if (file_exists($target)) {
    echo "<span style='color:green'>[OK] File ditemukan.</span><br>";
    echo "Permissions: " . substr(sprintf('%o', fileperms($target)), -4) . "<br>";
    echo "Owner ID: " . fileowner($target) . "<br>";
    echo "Readable by PHP? " . (is_readable($target) ? "YA" : "<span style='color:red'>TIDAK (Masalah Permission)</span>");
} else {
    echo "<span style='color:red'>[ERROR] File benar-benar tidak ditemukan oleh PHP.</span><br>";
    echo "Cek isi folder parent: " . dirname(__DIR__) . "<br>";
    $files = scandir(dirname(__DIR__));
    echo "<pre>"; print_r($files); echo "</pre>";
}
?>