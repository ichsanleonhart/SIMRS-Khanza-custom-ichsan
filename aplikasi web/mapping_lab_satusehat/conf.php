<?php
// conf.php
$host = "192.168.1.2";
$user = "client"; // User database
$pass = "epotoransu";     // Password database
$db   = "sik_master";  // Nama database khanza

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set mode fetch default ke array asosiatif
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Jika koneksi gagal, kirim format JSON error agar DataTables bisa membacanya
    header('Content-Type: application/json');
    echo json_encode([
        "data" => [], 
        "error" => "Koneksi Database Gagal: " . $e->getMessage()
    ]);
    exit;
}
?>