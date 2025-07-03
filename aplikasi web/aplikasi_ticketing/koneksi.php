<?php
$host     = 'localhost';    // sesuaikan dengan host database kamu
$user     = 'root';         // username MySQL
$password = '';             // password MySQL (kosong jika default XAMPP)
$database = 'tiketing';      // nama database yang kamu pakai

$conn = new mysqli($host, $user, $password, $database);
date_default_timezone_set('Asia/Jakarta');

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
