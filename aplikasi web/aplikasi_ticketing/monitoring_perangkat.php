<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$userQuery = $conn->prepare("SELECT nama, jabatan, unit_kerja FROM users WHERE id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$user = $userQuery->get_result()->fetch_assoc();

// Simpan data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $perangkat = $_POST['perangkat'];
    $load = $_POST['load_status'];
    $cpu = $_POST['cpu'];
    $ram = $_POST['ram'];
    $root = $_POST['root'];

    $stmt = $conn->prepare("INSERT INTO monitoring_perangkat (user_id, perangkat, load_status, cpu_usage, ram_usage, root_space) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issddd", $user_id, $perangkat, $load, $cpu, $ram, $root);
    $stmt->execute();
    echo "<script>alert('Data monitoring berhasil disimpan.'); window.location='monitoring_perangkat.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Monitoring Perangkat</title>
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
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
      <span>Monitoring Perangkat Server</span>
      <button type="button" class="btn btn-light btn-sm text-info font-weight-bold" data-toggle="modal" data-target="#modalMonitoring">
        + Tambah Data
      </button>
    </div>

    <div class="card-body table-responsive">
      <table class="table table-bordered table-sm">
        <thead class="bg-primary text-white">
          <tr>
            <th>No</th>
            <th>Perangkat</th>
            <th>Load</th>
            <th>CPU (%)</th>
            <th>RAM (%)</th>
            <th>Root (GB)</th>
            <th>Waktu</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $monitoring = $conn->query("SELECT * FROM monitoring_perangkat WHERE user_id = $user_id ORDER BY waktu_input DESC");
          $no = 1;
          while ($row = $monitoring->fetch_assoc()):
          ?>
          <tr>
            <td><?= $no++; ?></td>
            <td><?= htmlspecialchars($row['perangkat']); ?></td>
            <td><?= htmlspecialchars($row['load_status']); ?></td>
            <td><?= $row['cpu_usage']; ?></td>
            <td><?= $row['ram_usage']; ?></td>
            <td><?= $row['root_space']; ?></td>
            <td><?= date("d/m/Y H:i", strtotime($row['waktu_input'])); ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="modalMonitoring" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content shadow">
      <form method="POST">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Form Monitoring Perangkat</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Nama Perangkat</label>
            <input type="text" name="perangkat" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Load Status (%)</label>
            <input type="number" name="load_status" class="form-control" step="0.01" min="0" placeholder="Contoh: 1.23" required>
          </div>
          <div class="form-group">
            <label>CPU Usage (%)</label>
            <input type="number" name="cpu" class="form-control" min="0" max="100" step="0.1" placeholder="Contoh: 45.5" required>
          </div>
          <div class="form-group">
            <label>RAM Usage (%)</label>
            <input type="number" name="ram" class="form-control" min="0" max="100" step="0.1" placeholder="Contoh: 78.3" required>
          </div>
          <div class="form-group">
            <label>Root Usage (GB)</label>
            <input type="number" name="root" class="form-control" step="0.01" min="0" placeholder="Contoh: 35.75" required>
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
