<?php
// ajax_pasien.php (Simpan di root edokter)
require_once 'config/database.php';

if (!isset($_GET['q'])) exit;

$search = "%" . $_GET['q'] . "%";

// Cari pasien yang SEDANG DIRAWAT (Jalan/Inap) agar relevan
// Limit 20 biar cepat
$sql = "
SELECT r.no_rawat, p.no_rkm_medis, p.nm_pasien 
FROM reg_periksa r
JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
WHERE r.status_lanjut IN ('Ralan','Ranap') 
AND (p.nm_pasien LIKE :q OR p.no_rkm_medis LIKE :q)
ORDER BY r.tgl_registrasi DESC
LIMIT 20";

$stmt = $pdo->prepare($sql);
$stmt->execute(['q' => $search]);
$data = [];

while($row = $stmt->fetch()){
    $data[] = [
        'id' => $row['no_rawat'],
        'text' => $row['no_rkm_medis'] . ' - ' . $row['nm_pasien'] . ' (' . $row['no_rawat'] . ')'
    ];
}

echo json_encode($data);
?>