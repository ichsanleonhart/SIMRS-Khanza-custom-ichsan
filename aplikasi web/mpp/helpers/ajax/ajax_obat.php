<?php
// File: helpers/ajax/ajax_obat.php
$base_path = dirname(dirname(__DIR__)); 
require_once $base_path . '/config/database.php';

header('Content-Type: application/json');

$q = $_GET['q'] ?? '';
$results = [];

try {
    // Cari obat yang statusnya aktif dan memiliki stok > 0
    $sql = "SELECT d.kode_brng as id, 
                   CONCAT(d.nama_brng, ' (Stok: ', SUM(g.stok), ' ', d.kode_sat, ')') as text, 
                   d.nama_brng, 
                   SUM(g.stok) as stok,
                   d.kapasitas
            FROM databarang d 
            JOIN gudangbarang g ON d.kode_brng = g.kode_brng 
            WHERE d.status = '1' AND (d.kode_brng LIKE ? OR d.nama_brng LIKE ?) 
            GROUP BY d.kode_brng 
            HAVING stok > 0 
            LIMIT 50";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$q%", "%$q%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Silent error
}

echo json_encode(['results' => $results]);
?>