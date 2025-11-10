<?php
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
        header("Content-Type: image/png"); // Asumsikan logo adalah PNG, ganti jika perlu (misal: image/jpeg)
        echo $logo;
    } else {
        // Tampilkan gambar default jika tidak ada logo
        readfile("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAAAXNSR0IArs4c6QAAARFJREFUeJzt2LENglAUAFEb9CjNAsSiNAsQjMFiOggDkYAGBIugmYVd3mS+74e4IS8eL/8sZgAAAIB3G+DqfD/P+71u8/0dQRB8L/P5AOCABhBAAAEEEEAAAcQQQAABBBBAAAEEEEAAAQQQQAABBBBAAAEEEEAAAQQQQAABBBBAAAEEEEAAAQQQQAABBBBAAAEEEEAAAQQQQAABBBBAAAEEEEAAAQQQQAABBBBAAAEEEEAAAQQQQAABBBBAAIEdA3gej3f+h+iP2jOAZ/O5LzEGAAABBHQggAACCCCgAwIIIIAAAggoIYAAAggggIAKCCCCAAIIIKCFAAIIIIAAAgogcAkBBEgYQAABBBBAQAcEEEAAAQQQUAIBBBBAAAEEPgAAT/wCFJqA/xQZf+gAAAAASUVORK5CYII="); // Gambar 1x1 pixel transparan
    }
}
mysqli_close($konektor);
?>