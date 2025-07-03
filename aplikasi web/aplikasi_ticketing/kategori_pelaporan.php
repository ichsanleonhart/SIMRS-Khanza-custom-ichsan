<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Tambah kategori
if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama_kategori']);
    if ($nama !== '') {
        $stmt = $conn->prepare("INSERT INTO kategori_pelaporan (nama_kategori) VALUES (?)");
        $stmt->bind_param("s", $nama);
        $stmt->execute();
    }
    header("Location: kategori_pelaporan.php");
    exit;
}

// Ambil data kategori
$query = "SELECT id, nama_kategori FROM kategori_pelaporan";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Kategori Pelaporan</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700,900" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">

    <?php include 'sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <?php include 'topbar.php'; ?>

            <div class="container-fluid">
                <h1 class="h3 text-gray-800 mb-4">Master Kategori Pelaporan</h1>

                <!-- Form Tambah -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">Tambah Kategori</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <input type="text" name="nama_kategori" class="form-control" placeholder="Nama kategori" required>
                            </div>
                            <button type="submit" name="tambah" class="btn btn-primary">Tambah</button>
                        </form>
                    </div>
                </div>

                <!-- Tabel Kategori -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold text-primary">Daftar Kategori</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="bg-primary text-white">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nama Kategori</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $row['id']; ?></td>
                                        <td><?= htmlspecialchars($row['nama_kategori']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div> <!-- /.container-fluid -->
        </div>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
<?php include 'logout_modal.php'; ?>

<!-- JS -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>
</body>
</html>
