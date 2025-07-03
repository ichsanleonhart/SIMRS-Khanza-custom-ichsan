<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$total      = $conn->query("SELECT COUNT(*) FROM laporan WHERE user_id = $user_id")->fetch_row()[0];
$pending    = $conn->query("SELECT COUNT(*) FROM laporan WHERE user_id = $user_id AND status = 'pending'")->fetch_row()[0];
$diperiksa  = $conn->query("SELECT COUNT(*) FROM laporan WHERE user_id = $user_id AND status = 'diperiksa'")->fetch_row()[0];
$diproses   = $conn->query("SELECT COUNT(*) FROM laporan WHERE user_id = $user_id AND status = 'diproses'")->fetch_row()[0];
$selesai    = $conn->query("SELECT COUNT(*) FROM laporan WHERE user_id = $user_id AND status = 'selesai'")->fetch_row()[0];
$ditolak    = $conn->query("SELECT COUNT(*) FROM laporan WHERE user_id = $user_id AND status = 'ditolak'")->fetch_row()[0];
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

    <?php include 'sidebar_user.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <?php include 'topbar.php'; ?>

            <div class="container-fluid">
                <!-- Judul Halaman -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 text-gray-800">Dashboard Pengguna</h1>
                </div>

                <!-- Isi konten dashboard pengguna bisa kamu tambahkan di sini -->
                <div class="row">
                    <!-- Kartu Statistik -->
<div class="col-xl-4 col-md-6 mb-4">
    <div class="card border-left-dark shadow h-100 py-2">
        <div class="card-body">
            <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">Total Tiket Saya</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total; ?></div>
        </div>
    </div>
</div>

<div class="col-xl-4 col-md-6 mb-4">
    <div class="card border-left-secondary shadow h-100 py-2">
        <div class="card-body">
            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Menunggu Diproses</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $pending; ?></div>
        </div>
    </div>
</div>

<div class="col-xl-4 col-md-6 mb-4">
    <div class="card border-left-warning shadow h-100 py-2">
        <div class="card-body">
            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Sedang Ditinjau</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $diperiksa; ?></div>
        </div>
    </div>
</div>

<div class="col-xl-4 col-md-6 mb-4">
    <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-body">
            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Dalam Penanganan</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $diproses; ?></div>
        </div>
    </div>
</div>

<div class="col-xl-4 col-md-6 mb-4">
    <div class="card border-left-success shadow h-100 py-2">
        <div class="card-body">
            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Selesai</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $selesai; ?></div>
        </div>
    </div>
</div>

<div class="col-xl-4 col-md-6 mb-4">
    <div class="card border-left-danger shadow h-100 py-2">
        <div class="card-body">
            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Ditolak</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $ditolak; ?></div>
        </div>
    </div>
</div>

                </div>
            </div>

        </div>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ready to Leave?</h5>
                <button class="close" type="button" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">Pilih "Logout" untuk mengakhiri sesi.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                <a class="btn btn-primary" href="login.php">Logout</a>
            </div>
        </div>
    </div>
</div>

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
