<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Tambah jabatan
if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama_jabatan']);
    if ($nama !== '') {
        $stmt = $conn->prepare("INSERT INTO jabatan (nama_jabatan) VALUES (?)");
        $stmt->bind_param("s", $nama);
        $stmt->execute();
    }
    header("Location: master_jabatan.php");
    exit;
}

// Ambil semua jabatan
$result = $conn->query("SELECT id, nama_jabatan FROM jabatan");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Master Jabatan</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">
    <?php include 'sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'topbar.php'; ?>

            <div class="container-fluid">
                <h1 class="h3 mb-4 text-gray-800">Master Jabatan</h1>

                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">Tambah Jabatan</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="text" name="nama_jabatan" class="form-control mb-2" placeholder="Nama Jabatan" required>
                            <button type="submit" name="tambah" class="btn btn-primary">Tambah</button>
                        </form>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold text-primary">Daftar Jabatan</h6>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="bg-primary text-white">
                                <tr><th>ID</th><th>Nama Jabatan</th></tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $row['id']; ?></td>
                                        <td><?= htmlspecialchars($row['nama_jabatan']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<?php include 'logout_modal.php'; ?>
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>
</body>
</html>
