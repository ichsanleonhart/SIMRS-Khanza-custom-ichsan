<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Proses simpan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama       = $_POST['nama_perusahaan'];
    $alamat     = $_POST['alamat'];
    $kota       = $_POST['kota'];
    $provinsi   = $_POST['provinsi'];
    $kontak     = $_POST['kontak'];
    $email      = $_POST['email'];
    $logo_file  = $_FILES['logo']['name'];
    $tmp_name   = $_FILES['logo']['tmp_name'];

    if ($logo_file) {
        $target = "uploads/logo_" . time() . "_" . basename($logo_file);
        move_uploaded_file($tmp_name, $target);
    } else {
        $target = null;
    }

    $stmt = $conn->prepare("INSERT INTO perusahaan (nama_perusahaan, alamat, kota, provinsi, kontak, email, logo) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $nama, $alamat, $kota, $provinsi, $kontak, $email, $target);
    $stmt->execute();
    echo "<script>alert('Data perusahaan berhasil ditambahkan!'); window.location='master_perusahaan.php';</script>";
    exit;
}

// Ambil semua perusahaan
$data = $conn->query("SELECT * FROM perusahaan ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Master Perusahaan</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
    .logo-thumb { max-height: 50px; }
    </style>
</head>
<body id="page-top">
<div id="wrapper">
    <?php include 'sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'topbar.php'; ?>

            <div class="container-fluid">
                <h1 class="h3 text-gray-800 mb-4">Master Data Perusahaan</h1>

                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white">Input Perusahaan</div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label>Nama Perusahaan</label>
                                        <input type="text" name="nama_perusahaan" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Alamat</label>
                                        <textarea name="alamat" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Kota</label>
                                        <input type="text" name="kota" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Provinsi</label>
                                        <input type="text" name="provinsi" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Kontak</label>
                                        <input type="text" name="kontak" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Logo</label>
                                        <input type="file" name="logo" class="form-control-file">
                                    </div>
                                    <button type="submit" class="btn btn-success">Simpan</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-info text-white">Daftar Perusahaan</div>
                            <div class="card-body table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="bg-primary text-white">
                                        <tr>
                                            <th>Nama Perusahaan</th>
                                            <th>Alamat</th>
                                            <th>Kota</th>
                                            <th>Provinsi</th>
                                            <th>Kontak</th>
                                            <th>Email</th>
                                            <th>Logo</th>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $data->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['nama_perusahaan']); ?></td>
                                                 <td><?= htmlspecialchars($row['alamat']); ?></td>
                                                 <td><?= htmlspecialchars($row['kota']); ?></td>
                                                 <td><?= htmlspecialchars($row['provinsi']); ?></td>
                                                 <td><?= htmlspecialchars($row['kontak']); ?></td>
                                                 <td><?= htmlspecialchars($row['email']); ?></td>
                                                <td>
                                                    <?php if ($row['logo']): ?>
                                                        <img src="<?= $row['logo']; ?>" class="logo-thumb">
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div> <!-- /row -->

            </div>
        </div>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
<?php include 'logout_modal.php'; ?>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>
</body>
</html>
