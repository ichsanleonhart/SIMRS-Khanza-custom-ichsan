<?php
// File: helpers/ajax/ajax_aturan.php
$base_path = dirname(dirname(__DIR__)); 
require_once $base_path . '/config/database.php';

header('Content-Type: application/json');

$q = $_GET['q'] ?? '';
$results = [];

try {
    $sql = "SELECT aturan as id, aturan as text FROM master_aturan_pakai WHERE aturan LIKE ? LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$q%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

echo json_encode(['results' => $results]);
?>