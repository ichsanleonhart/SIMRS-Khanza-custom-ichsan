<?php
// image_load.php
require_once 'config/database.php';

try {
    $stmt = $pdo->query("SELECT logo FROM setting LIMIT 1");
    $data = $stmt->fetch();

    if ($data && !empty($data['logo'])) {
        header("Content-Type: image/jpeg"); // Asumsi format JPEG/PNG
        echo $data['logo'];
    } else {
        // Redirect ke gambar default jika logo kosong (opsional)
        // header("Location: assets/default-logo.png");
    }
} catch (Exception $e) {
    // Silent fail
}
?>