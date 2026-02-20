<?php
// File: helpers/ajax/ajax_pasien_konsul.php
require_once '../../config/database.php';

// Tangkap parameter 'q' (dari setting Select2 baru) atau 'term' (bawaan lama)
$keyword = $_GET['q'] ?? $_GET['term'] ?? '';

if (empty(trim($keyword))) {
    echo json_encode([]);
    exit;
}

$search = "%" . trim($keyword) . "%";

try {
    $sql = "SELECT r.no_rawat, p.no_rkm_medis, p.nm_pasien, r.status_lanjut 
            FROM reg_periksa r
            JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
            WHERE r.stts <> 'Batal' 
            AND (p.nm_pasien LIKE :q1 OR p.no_rkm_medis LIKE :q2 OR r.no_rawat LIKE :q3)
            ORDER BY r.tgl_registrasi DESC, r.jam_reg DESC
            LIMIT 30";

    $stmt = $pdo->prepare($sql);
    
    // [FIX] Masukkan nilai untuk ketiga parameter secara eksplisit
    $stmt->execute([
        'q1' => $search,
        'q2' => $search,
        'q3' => $search
    ]);
    
    $data = [];

    while($row = $stmt->fetch()){
        $label_status = ($row['status_lanjut'] == 'Ranap') ? 'Ranap' : 'Ralan';
        
        $data[] = [
            'id' => $row['no_rawat'],
            'text' => $row['no_rkm_medis'] . ' - ' . $row['nm_pasien'] . ' [' . $label_status . '] (' . $row['no_rawat'] . ')'
        ];
    }

    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode([['id' => '', 'text' => 'Error DB: ' . $e->getMessage()]]);
}
?>