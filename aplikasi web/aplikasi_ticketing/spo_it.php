<?php
session_start();
require 'koneksi.php';

// Cek session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}


$user_id = $_SESSION['user_id'];

// Ambil data user untuk tampilan
$userQuery = $conn->prepare("SELECT nama, jabatan, unit_kerja FROM users WHERE id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$user = $userQuery->get_result()->fetch_assoc();

// Handle form upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['dokumen_pdf'])) {
    $no_spo = $_POST['no_spo'];
    $judul = $_POST['judul'];
    $nama_file = $_FILES['dokumen_pdf']['name'];
    $tmp = $_FILES['dokumen_pdf']['tmp_name'];
    $ekstensi = pathinfo($nama_file, PATHINFO_EXTENSION);

    if (strtolower($ekstensi) !== 'pdf') {
        echo "<script>alert('Hanya file PDF yang diperbolehkan!');</script>";
    } else {
        $nama_simpan = uniqid('spo_', true) . '.pdf';
        move_uploaded_file($tmp, 'uploads/' . $nama_simpan);
        $stmt = $conn->prepare("INSERT INTO spo_it (user_id, no_spo, judul_spo, file_pdf) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $no_spo, $judul, $nama_simpan);
        $stmt->execute();
        echo "<script>alert('SPO berhasil ditambahkan!'); window.location='spo_it.php';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Form SPO IT</title>
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
<?php include 'sidebar.php'; ?>
<div id="content-wrapper" class="d-flex flex-column">
<div id="content">
<?php include 'topbar.php'; ?>
<div class="container-fluid">

<div class="col-lg-12 mb-4">
    <div class="card shadow">
       <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <span>Dokumen SPO IT</span>
            <button type="button" class="btn btn-light btn-sm text-info font-weight-bold" data-toggle="modal" data-target="#modalSPO">
                + Tambah SPO
            </button>
        </div>

        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="bg-primary text-white">
                    <tr>
                        <th>No</th>
                        <th>No SPO</th>
                        <th>Judul</th>
                        <th>Upload</th>
                        <th>Dokumen</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $spo = $conn->query("SELECT * FROM spo_it WHERE user_id = $user_id ORDER BY tanggal_upload DESC");
                $no = 1;
                while ($row = $spo->fetch_assoc()):
                ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['no_spo']); ?></td>
                        <td><?= htmlspecialchars($row['judul_spo']); ?></td>
                        <td><?= date("d/m/Y H:i", strtotime($row['tanggal_upload'])); ?></td>
                        <td><a href="uploads/<?= urlencode($row['file_pdf']); ?>" target="_blank" class="btn btn-sm btn-primary"><i class="fas fa-file-pdf"></i> Lihat</a></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH SPO -->
<div class="modal fade" id="modalSPO" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content shadow">
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">Form Tambah SPO IT</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>No. SPO</label>
                <input type="text" name="no_spo" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Judul SPO</label>
                <input type="text" name="judul" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Upload Dokumen (PDF)</label>
                <input type="file" name="dokumen_pdf" accept="application/pdf" class="form-control-file" required>
            </div>
        </div>
        <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
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
