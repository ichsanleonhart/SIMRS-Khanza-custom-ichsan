<?php
session_start();
require 'koneksi.php';

// Cek apakah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Cek permintaan
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'], $_POST['status'])) {
    $id = intval($_POST['id']);
    $status = $_POST['status'];
    $catatan = isset($_POST['catatan']) ? trim($_POST['catatan']) : null;
    $admin_id = $_SESSION['user_id'];
    $updated_at = date("Y-m-d H:i:s");

    // Validasi status
    $validStatus = ['pending', 'diperiksa', 'diproses', 'selesai', 'ditolak'];
    if (!in_array($status, $validStatus)) {
        echo "<script>alert('Status tidak valid!'); window.location='tiket.php';</script>";
        exit;
    }

    // Update laporan
    $stmt = $conn->prepare("UPDATE laporan SET status = ?, catatan_admin = ?, updated_at = ? WHERE id = ?");
    $stmt->bind_param("sssi", $status, $catatan, $updated_at, $id);
    $stmt->execute();

    // Simpan histori status
    $stmt2 = $conn->prepare("INSERT INTO histori_status (laporan_id, status, catatan, waktu, admin_id) VALUES (?, ?, ?, ?, ?)");
    $stmt2->bind_param("isssi", $id, $status, $catatan, $updated_at, $admin_id);
    $stmt2->execute();

    echo "<script>alert('Status dan histori berhasil diperbarui!'); window.location='tiket.php';</script>";
    exit;
} else {
    header("Location: tiket.php");
    exit;
}
