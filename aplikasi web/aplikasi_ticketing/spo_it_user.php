<?php
session_start();
require 'koneksi.php';

// Cek apakah session user valid
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data user untuk tampilan
$userQuery = $conn->prepare("SELECT nama, jabatan, unit_kerja FROM users WHERE id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$resultUser = $userQuery->get_result();
if ($resultUser->num_rows === 0) {
    session_destroy();
    header("Location: login.php");
    exit;
}
$user = $resultUser->fetch_assoc();

// Ambil data SPO dari user yang role-nya admin
$spo = $conn->query("
    SELECT s.no_spo, s.judul_spo, s.file_pdf, s.tanggal_upload 
    FROM spo_it s
    JOIN users u ON s.user_id = u.id
    WHERE u.role = 'admin'
    ORDER BY s.tanggal_upload DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Dokumen SPO IT</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .modal.fade .modal-dialog { animation: fadeInUp 0.3s ease-out; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body id="page-top">
<div id="wrapper">
<?php include 'sidebar_user.php'; ?>
<div id="content-wrapper" class="d-flex flex-column">
<div id="content">
<?php include 'topbar.php'; ?>
<div class="container-fluid">

<div class="col-lg-12 mb-4">
    <div class="card shadow">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <span>Dokumen SPO IT</span>
        </div>

        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="bg-primary text-white">
                    <tr>
                        <th>No</th>
                        <th>No SPO</th>
                        <th>Judul</th>
                        <th>Dokumen</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = 1;
                while ($row = $spo->fetch_assoc()):
                ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['no_spo']); ?></td>
                        <td><?= htmlspecialchars($row['judul_spo']); ?></td>
                        <td>
                            <a href="uploads/<?= urlencode($row['file_pdf']); ?>" class="btn btn-sm btn-info" target="_blank">
                                <i class="fas fa-file-pdf"></i> Lihat
                            </a><br>
                            <small><?= date("d/m/Y H:i", strtotime($row['tanggal_upload'])); ?></small>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($spo->num_rows === 0): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">Belum ada dokumen SPO dari admin.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div> <!-- /.container-fluid -->
</div>
</div>
</div>

<?php include 'logout_modal.php'; ?>
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>
</body>
</html>
