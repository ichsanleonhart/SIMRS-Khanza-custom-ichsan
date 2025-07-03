<?php
session_start();
require 'koneksi.php';

// Cek session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Aktif/nonaktifkan user
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $currentStatus = $_GET['toggle'];
    $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';

    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $id);
    $stmt->execute();

    header("Location: data_pengguna.php");
    exit;
}

if (isset($_GET['changerole']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $currentRole = $_GET['changerole'];

    $newRole = ($currentRole === 'admin') ? 'user' : 'admin';

    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $newRole, $id);
    $stmt->execute();

    header("Location: data_pengguna.php");
    exit;
}



// Ambil data pengguna (semua role atau terfilter)
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
$validRoles = ['admin', 'user', 'manager']; // tambahkan role yang diizinkan

$where = '';
if (in_array($roleFilter, $validRoles)) {
    $where = "WHERE role = '$roleFilter'";
}
$query = "SELECT id, nik, nama, jabatan, unit_kerja, email, role, status FROM users $where";

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

                <h5 class="mb-3 text-gray-800">Data Pengguna</h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Unit Kerja</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['nik']); ?></td>
                                    <td><?= htmlspecialchars($row['nama']); ?></td>
                                    <td><?= htmlspecialchars($row['jabatan']); ?></td>
                                    <td><?= htmlspecialchars($row['unit_kerja']); ?></td>
                                    <td><?= htmlspecialchars($row['email']); ?></td>
                                    <td>
    <a href="?changerole=<?= $row['role']; ?>&id=<?= $row['id']; ?>"
       class="badge badge-info"
       style="cursor: pointer; text-decoration: none;">
        Ubah jadi <?= $row['role'] === 'admin' ? 'User' : 'Admin'; ?>
    </a>
</td>

                                    <td>
                                        <?php if ($row['status'] === 'active'): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                             <td>
    <a href="?toggle=<?= $row['status']; ?>&id=<?= $row['id']; ?>"
       class="badge <?= $row['status'] === 'active' ? 'badge-danger' : 'badge-success'; ?>"
       style="cursor: pointer; text-decoration: none;">
        <?= $row['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan'; ?>
    </a>
</td>




                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
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
