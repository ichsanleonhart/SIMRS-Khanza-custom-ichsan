<?php
/*
 * ===================================================================================
 * API UNTUK MENGAMBIL TEMPLATE HASIL RADIOLOGI
 * ===================================================================================
 * Endpoint ini mengembalikan daftar template dalam format JSON.
 */

// Set header untuk memastikan output adalah JSON
header('Content-Type: application/json');

require_once 'config.php';

// Memastikan hanya user yang sudah login yang bisa mengakses API ini
if (!is_user_authorized()) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan login terlebih dahulu.']);
    exit;
}

$pdo = connect_db();
$response = [];

try {
    $stmt = $pdo->query("SELECT no_template, nama_pemeriksaan, template_hasil_radiologi FROM template_hasil_radiologi ORDER BY nama_pemeriksaan");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['success'] = true;
    $response['data'] = $templates;
} catch (\PDOException $e) {
    http_response_code(500); // Internal Server Error
    error_log("API Template Gagal: " . $e->getMessage());
    $response['success'] = false;
    $response['message'] = 'Gagal mengambil data template dari server.';
}

// Mengembalikan data dalam format JSON
echo json_encode($response);
?>

