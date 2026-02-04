<?php
// File: config/database.php

// Ganti sesuai settingan database kamu
$host = '192.168.1.5';
$db   = 'sik_master'; // Sesuai nama schema di file sql
$user = 'client';       // Default XAMPP
$pass = 'epotoransu';           // Default XAMPP kosong

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Jangan tampilkan error asli ke user di production
    die("Koneksi Database Gagal: Hubungi IT Support.");
}
?>