<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../conf/conf.php';
header('Content-Type: application/json; charset=utf-8');

$conn = bukakoneksi();

// Ambil tanggal dan jam server saat ini
$tanggal = date('Y-m-d');
$jam = date('H:i:s');

// Cari nomor urut terakhir di hari ini (Sesuai query Java Khanza)
$sql = "SELECT IFNULL(MAX(CONVERT(nomor, SIGNED)), 0) as max_no FROM antriloketcetak WHERE tanggal = '$tanggal'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

// Tambah 1 dari nomor terakhir
$next_no = (int)$row['max_no'] + 1;

// Format menjadi 3 digit (contoh: 1 menjadi "001")
$nomor = str_pad($next_no, 3, '0', STR_PAD_LEFT);

// Simpan ke tabel antriloketcetak
$sql_insert = "INSERT INTO antriloketcetak (tanggal, jam, nomor) VALUES ('$tanggal', '$jam', '$nomor')";

if (mysqli_query($conn, $sql_insert)) {
    echo json_encode([
        'success' => true, 
        'nomor' => $nomor, 
        'tanggal' => $tanggal, 
        'jam' => $jam
    ]);
} else {
    echo json_encode([
        'error' => 'Gagal membuat antrean loket: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>