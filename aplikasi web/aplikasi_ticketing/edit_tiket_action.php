<?php
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomor = $_POST['nomor_tiket'];
    $subjek = $_POST['subjek'];
    $deskripsi = $_POST['deskripsi'];

    $stmt = $conn->prepare("UPDATE laporan SET subjek = ?, deskripsi = ? WHERE nomor_tiket = ? AND status = 'pending'");
    $stmt->bind_param("sss", $subjek, $deskripsi, $nomor);

    if ($stmt->execute()) {
        echo "<script>alert('Tiket berhasil diperbarui.'); window.location='order_tiket.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui tiket.'); window.location='order_tiket.php';</script>";
    }
}
?>
