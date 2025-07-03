<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$stmt = $conn->prepare("SELECT nik, nama, email, jabatan, unit_kerja FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Ambil list master jabatan & unit
$jabatanList = $conn->query("SELECT nama_jabatan FROM jabatan ORDER BY nama_jabatan");
$unitList = $conn->query("SELECT nama_unit FROM unit_kerja ORDER BY nama_unit");

// Update data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $jabatan = $_POST['jabatan'];
    $unit_kerja = $_POST['unit_kerja'];

    $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, jabatan = ?, unit_kerja = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $nama, $email, $jabatan, $unit_kerja, $user_id);
    $stmt->execute();

    echo "<script>alert('Profil berhasil diperbarui!'); window.location='profil_user.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Profil Saya</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">

    <?php include 'sidebar_user.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'topbar.php'; ?>

            <div class="container-fluid">
                <h1 class="h3 text-gray-800 mb-4">Profil Saya</h1>

                <div class="row">
                    <!-- Kolom kiri: Form Ubah -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white">Edit Profil</div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="form-group">
                                        <label>NIK</label>
                                        <input type="text" class="form-control" value="<?= $user['nik']; ?>" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Nama Lengkap</label>
                                        <input type="text" name="nama" class="form-control" value="<?= $user['nama']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Email Aktif</label>
                                        <input type="email" name="email" class="form-control" value="<?= $user['email']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Jabatan</label>
                                        <select name="jabatan" class="form-control" required>
                                            <option value="">-- Pilih Jabatan --</option>
                                            <?php while ($row = $jabatanList->fetch_assoc()): ?>
                                                <option value="<?= $row['nama_jabatan']; ?>" <?= $row['nama_jabatan'] == $user['jabatan'] ? 'selected' : ''; ?>>
                                                    <?= $row['nama_jabatan']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Unit Kerja</label>
                                        <select name="unit_kerja" class="form-control" required>
                                            <option value="">-- Pilih Unit Kerja --</option>
                                            <?php while ($row = $unitList->fetch_assoc()): ?>
                                                <option value="<?= $row['nama_unit']; ?>" <?= $row['nama_unit'] == $user['unit_kerja'] ? 'selected' : ''; ?>>
                                                    <?= $row['nama_unit']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom kanan: Informasi Profil -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-info text-white">Data Profil Saat Ini</div>
                            <div class="card-body">
                                <p><strong>NIK:</strong> <?= $user['nik']; ?></p>
                                <p><strong>Nama:</strong> <?= $user['nama']; ?></p>
                                <p><strong>Email:</strong> <?= $user['email']; ?></p>
                                <p><strong>Jabatan:</strong> <?= $user['jabatan']; ?></p>
                                <p><strong>Unit Kerja:</strong> <?= $user['unit_kerja']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

            </div> <!-- /.container -->
        </div> <!-- /.content -->

    </div> <!-- /.content-wrapper -->
</div> <!-- /.wrapper -->

<?php include 'logout_modal.php'; ?>
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>
</body>
</html>
