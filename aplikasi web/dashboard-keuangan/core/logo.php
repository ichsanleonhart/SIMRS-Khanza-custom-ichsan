<?php
// Komentar: File ini khusus untuk merender data BLOB logo dari database.

require_once(dirname(__DIR__) . '/config/koneksi.php');

$sql = "SELECT setting.logo FROM setting LIMIT 1";
$result = $koneksi->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // Komentar: Mengatur header HTTP sebagai gambar PNG.
    // Jika logo Anda JPG, ganti menjadi 'image/jpeg'
    header("Content-type: image/png"); 
    
    // Komentar: Tampilkan data BLOB
    echo $row['logo'];
} else {
    // Jika tidak ada logo, tampilkan gambar placeholder (opsional)
    // Untuk saat ini, kita biarkan kosong.
}

$koneksi->close();
?>