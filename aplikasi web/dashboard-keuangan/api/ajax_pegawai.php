<?php
/*
 * File: api/ajax_pegawai.php
 * Deskripsi: API Pencarian Pegawai untuk Select2
 */

ob_start();
ini_set('display_errors', 0);
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

header('Content-Type: application/json; charset=utf-8');
require_once(dirname(__DIR__) . '/config/koneksi.php'); 

$koneksi->query("SET sql_mode = ''");

$search = isset($_GET['q']) ? $koneksi->real_escape_string($_GET['q']) : '';
$data = [];

if (!empty($search)) {
    // Mencari berdasarkan NIK atau Nama
    $sql = "SELECT nik, nama FROM pegawai WHERE nik LIKE '%$search%' OR nama LIKE '%$search%' LIMIT 20";
    $res = $koneksi->query($sql);
    
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $data[] = [
                'id' => $row['nik'], // Ini akan jadi value (username)
                'text' => $row['nik'] . ' - ' . $row['nama'] // Ini label yang tampil
            ];
        }
    }
}

ob_end_clean();
echo json_encode($data);
$koneksi->close();
?>