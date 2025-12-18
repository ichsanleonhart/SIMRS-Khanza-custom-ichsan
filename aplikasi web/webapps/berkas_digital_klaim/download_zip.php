<?php
/*
 * File: download_zip.php
 * Fungsi: Membungkus file PDF hasil generate menjadi ZIP
 */

$files_json = $_POST['files']; // String JSON dari input hidden
$file_list = json_decode($files_json, true);

if (!$file_list || empty($file_list)) {
    die("Tidak ada file untuk di-zip.");
}

$zip = new ZipArchive();
$zip_name = "Berkas_Klaim_" . date('Ymd_His') . ".zip";
$zip_path = __DIR__ . "/tmp_bulk/" . $zip_name;

if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
    die("Gagal membuat file zip.");
}

foreach ($file_list as $file_path) {
    if (file_exists($file_path)) {
        // Masukkan file ke zip dengan nama dasarnya (misal: 2025-12-01_Budi.pdf)
        $zip->addFile($file_path, basename($file_path));
    }
}

$zip->close();

// Download Process
if (file_exists($zip_path)) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_name . '"');
    header('Content-Length: ' . filesize($zip_path));
    readfile($zip_path);

    // Cleanup: Hapus file ZIP dan semua PDF sementara
    unlink($zip_path);
    foreach ($file_list as $f) {
        if(file_exists($f)) unlink($f);
    }
} else {
    echo "Gagal memproses file ZIP.";
}
?>