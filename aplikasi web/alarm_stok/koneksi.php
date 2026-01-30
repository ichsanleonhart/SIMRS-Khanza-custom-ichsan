<?php
// koneksi.php
// Konfigurasi database SIMRS Khanza
$host = "192.168.1.5";
$user = "client"; // Sesuaikan user db
$pass = "epotoransu";     // Sesuaikan pass db
$db   = "sik_master";  // Nama database Khanza

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}
?>