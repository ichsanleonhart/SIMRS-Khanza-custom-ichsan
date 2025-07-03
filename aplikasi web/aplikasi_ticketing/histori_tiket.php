<?php
session_start();
require 'koneksi.php';

// Cek session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil data histori
$tanggal_filter = isset($_GET['tanggal']) && $_GET['tanggal'] !== ''
    ? $_GET['tanggal']
    : date('Y-m-d');

$query = "SELECT h.id, h.laporan_id, h.status, h.catatan, h.waktu,
                 l.nomor_tiket, u.nama AS admin_nama
          FROM histori_status h
          JOIN laporan l ON h.laporan_id = l.id
          JOIN users u ON h.admin_id = u.id
          WHERE DATE(h.waktu) = '$tanggal_filter'
          ORDER BY h.waktu DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>SB Admin 2 - Dashboard</title>
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
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 text-gray-800">Dashboard Admin</h1>
                </div>

                   <h5 class="mb-3 text-gray-800 d-flex justify-content-between align-items-center">
  <span>History Tiket</span>
  <form method="get" class="form-inline">
    <label class="mr-2 mb-0 font-weight-bold text-dark">Filter Tanggal</label>
    <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal_filter); ?>" class="form-control form-control-sm mr-2">
    <button type="submit" class="btn btn-sm btn-primary">Tampilkan</button>
  </form>
</h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>No Tiket</th>
                                <th>Status</th>
                                <th>Catatan</th>
                                <th>Waktu</th>
                                <th>Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['nomor_tiket']; ?></td>
                                    <td><?= ucwords($row['status']); ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['catatan'])); ?></td>
                                    <td><?= date("d/m/Y H:i", strtotime($row['waktu'])); ?></td>
                                    <td><?= htmlspecialchars($row['admin_nama']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

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
<script src="vendor/chart.js/Chart.min.js"></script>
<script src="js/demo/chart-area-demo.js"></script>
<script src="js/demo/chart-pie-demo.js"></script>
</body>
</html>
