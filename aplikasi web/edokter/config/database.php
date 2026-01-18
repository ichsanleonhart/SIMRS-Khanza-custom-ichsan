<?php
// config/database.php

// Ganti sesuai settingan server lokal/server RS
$host = '192.168.0.2';
$db   = 'sik_master'; // Sesuaikan nama database SIK Khanza
$user = 'client';
$pass = 'epotoransu';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => true, 
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

define('BASE_URL', 'http://localhost/edokter'); 


?>