<?php
// File: helpers/ajax/ajax_icd.php
$base_path = dirname(dirname(__DIR__)); 
require_once $base_path . '/config/database.php';

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';
$q = $_GET['q'] ?? '';
$results = [];

try {
    if ($type == 'icd10') {
        $stmt = $pdo->prepare("SELECT kd_penyakit as id, CONCAT(kd_penyakit, ' - ', nm_penyakit) as text, nm_penyakit as nama_asli FROM penyakit WHERE kd_penyakit LIKE ? OR nm_penyakit LIKE ? LIMIT 50");
        $stmt->execute(["%$q%", "%$q%"]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($type == 'icd9') {
        $stmt = $pdo->prepare("SELECT kode as id, CONCAT(kode, ' - ', deskripsi_panjang) as text, deskripsi_panjang as nama_asli FROM icd9 WHERE kode LIKE ? OR deskripsi_panjang LIKE ? LIMIT 50");
        $stmt->execute(["%$q%", "%$q%"]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // Abaikan error agar select2 tidak crash
}

echo json_encode(['results' => $results]);
?>