<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil user info
$userQuery = $conn->prepare("SELECT nama, jabatan, unit_kerja FROM users WHERE id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$user = $userQuery->get_result()->fetch_assoc();

// Simpan data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $suhu = $_POST['suhu'];
    $ac = $_POST['ac'];
    $ups = $_POST['ups'];

    $stmt = $conn->prepare("INSERT INTO pencatatan_server (user_id, suhu, kondisi_ac, tegangan_ups) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $user_id, $suhu, $ac, $ups);
    $stmt->execute();
    echo "<script>alert('Pencatatan berhasil disimpan.'); window.location='pencatatan_server.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Pencatatan Server</title>
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

<div class="col-lg-12 mb-4">
  <div class="card shadow">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
      <span>Pencatatan Harian Server</span>
      <button type="button" class="btn btn-light btn-sm text-success font-weight-bold" data-toggle="modal" data-target="#modalServer">
        + Input Data
      </button>
    </div>

    <div class="card-body table-responsive">
      <table class="table table-bordered table-sm">
        <thead class="bg-primary text-white">
          <tr>
            <th>No</th>
            <th>Suhu (°C)</th>
            <th>Kondisi AC</th>
            <th>Tegangan UPS (V)</th>
            <th>Waktu</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $data = $conn->query("SELECT * FROM pencatatan_server WHERE user_id = $user_id ORDER BY waktu_input DESC");
          $no = 1;
          while ($row = $data->fetch_assoc()):
          ?>
          <tr>
            <td><?= $no++; ?></td>
            <td><?= htmlspecialchars($row['suhu']); ?></td>
            <td><?= htmlspecialchars($row['kondisi_ac']); ?></td>
            <td><?= htmlspecialchars($row['tegangan_ups']); ?></td>
            <td><?= date("d/m/Y H:i", strtotime($row['waktu_input'])); ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Input -->
<div class="modal fade" id="modalServer" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content shadow">
      <form method="POST">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Form Pencatatan Server</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Suhu Ruangan (°C)</label>
            <input type="number" name="suhu" class="form-control" step="0.1" required>
          </div>
          <div class="form-group">
            <label>Kondisi AC</label>
            <select name="ac" class="form-control" required>
              <option value="">-- Pilih --</option>
              <option value="Nyala">Nyala</option>
              <option value="Tidak Nyala">Tidak Nyala</option>
            </select>
          </div>
          <div class="form-group">
            <label>Tegangan UPS (Volt)</label>
            <input type="number" name="ups" class="form-control" step="0.1" required>
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

</div>
</div>
</div>
<?php include 'logout_modal.php'; ?>
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>
</body>
</html>
