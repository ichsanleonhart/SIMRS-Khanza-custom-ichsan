<?php
// Komentar: Memanggil file konfigurasi global
include '../conf/conf.php'; 
$konektor = bukakoneksi();
if (!$konektor) {
    die("Koneksi gagal");
}

$sql = "SELECT logo FROM setting LIMIT 1";
$result = mysqli_query($konektor, $sql);

if ($row = mysqli_fetch_assoc($result)) {
    $logo = $row['logo'];
    if ($logo) {
        header("Content-Type: image/png"); 
        echo $logo;
    } else {
        // Gambar default jika logo BLOB kosong
        readfile("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAAAXNSR0IArs4c6QAAARFJREFUeJzt2LENglAUAFEb9CjNAsSiNAsQjMFiOggDkYAGBIugmYVd3mS+74e4IS8eL/8sZgAAAIB3G+DqfD/P+71u8/0dQRB8L/P5AOCABhBAAAEEEEAAAcQQQAABBBBAAAEEEEAAAQQQQAABBBBAAAEEEEAAAQQQQAABBBBAAAEEEEAAAQQQQAABBBBAAAEEEEAAAQQQQAABBBBAAAEEEEAAAQQQQAABBBBAAAEEEEAAAQQQQAABBBBAAIEdA3gej3f+h+iP2jOAZ/O5LzEGAAABBHQggAACCCCgAwIIIIAAAggoIYAAAggggIAKCCCCAAIIIKCFAAIIIIAAAgogcAkBBEgYQAABBBBAQAcEEEAAAQQQUAIBBBBAAAEEPgAAT/wCFJqA/xQZf+gAAAAASUVORK5CYII="); 
    }
}
mysqli_close($konektor);
?>