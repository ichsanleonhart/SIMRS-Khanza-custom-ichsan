<?php
/**
 * KONFIGURASI DATABASE & IDENTITAS
 * Author: Kamerad (Gemini) for Alicia
 */

$db_host = '192.168.1.5';
$db_user = 'client';
$db_pass = 'epotoransu';
$db_name = 'sik_master';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $koneksi = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $koneksi->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Gagal terkoneksi ke database SIMRS: " . $e->getMessage());
}

// Fetch Identitas Instansi (Sekali saja)
$q_instansi = $koneksi->query("SELECT nama_instansi, logo FROM setting LIMIT 1");
$data_instansi = $q_instansi->fetch_assoc();

$nama_rs = $data_instansi['nama_instansi'] ?? 'RS Khanza';
$logo_base64 = isset($data_instansi['logo']) ? base64_encode($data_instansi['logo']) : '';
$logo_src = $logo_base64 ? "data:image/jpeg;base64," . $logo_base64 : "https://via.placeholder.com/50"; // Fallback image
?>