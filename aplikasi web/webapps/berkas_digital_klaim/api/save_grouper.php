<?php
// File: api/save_grouper.php
error_reporting(0);
ini_set('display_errors', 0);

// [PERBAIKAN FATAL DISINI JUGA]
if(file_exists(__DIR__ . '/../../conf/conf.php')) {
    require_once(__DIR__ . '/../../conf/conf.php');
} else {
    require_once(__DIR__ . '/../conf/conf.php');
}

header('Content-Type: application/json');

$koneksi = bukakoneksi();
if (!$koneksi) {
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal']);
    exit;
}

session_start();

if (!isset($_SESSION['casemix_login'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Sesi habis']);
    exit;
}

$caseId = isset($_POST['case']) ? str_replace('-', '/', $_POST['case']) : '';
$val    = isset($_POST['grouper']) ? $_POST['grouper'] : '';

if (empty($caseId) || empty($val)) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit;
}

$parts = explode(':', $val, 2);
$tarif = floatval($parts[0]);
$kode  = isset($parts[1]) ? trim($parts[1]) : 'Manual Input';

if ($tarif <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Nominal nol/tidak valid']);
    exit;
}

// Cek Existing
$cek = mysqli_query($koneksi, "SELECT no_rawat FROM perkiraan_biaya_ranap WHERE no_rawat='$caseId'");

if (mysqli_num_rows($cek) > 0) {
    $q = "UPDATE perkiraan_biaya_ranap SET kd_penyakit=?, tarif=? WHERE no_rawat=?";
    $stmt = mysqli_prepare($koneksi, $q);
    mysqli_stmt_bind_param($stmt, "sds", $kode, $tarif, $caseId);
} else {
    $q = "INSERT INTO perkiraan_biaya_ranap (kd_penyakit, tarif, no_rawat) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $q);
    mysqli_stmt_bind_param($stmt, "sds", $kode, $tarif, $caseId);
}

if ($stmt && mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success', 'message' => 'Data tersimpan!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . mysqli_error($koneksi)]);
}
?>