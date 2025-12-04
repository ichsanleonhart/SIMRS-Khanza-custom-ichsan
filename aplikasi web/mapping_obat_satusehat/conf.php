<?php
// conf.php
$host = "192.168.1.2";
$user = "client"; // User database
$pass = "epotoransu";     // Password database
$db   = "sik_master";  // Nama database khanza

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi Gagal: " . $e->getMessage());
}
?>