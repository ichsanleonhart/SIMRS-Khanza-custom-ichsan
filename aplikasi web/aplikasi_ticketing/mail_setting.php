<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$userQuery = $conn->prepare("SELECT nama, jabatan, unit_kerja FROM users WHERE id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$user = $userQuery->get_result()->fetch_assoc();

$setting = $conn->query("SELECT * FROM mail_settings LIMIT 1")->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $host = $_POST['mail_host'];
    $port = $_POST['mail_port'];
    $user_email = $_POST['mail_username'];
    $pass = $_POST['mail_password'];
    $from = $_POST['mail_from_email'];
    $name = $_POST['mail_from_name'];

    if ($setting) {
        $stmt = $conn->prepare("UPDATE mail_settings SET mail_host=?, mail_port=?, mail_username=?, mail_password=?, mail_from_email=?, mail_from_name=? WHERE id=?");
        $stmt->bind_param("sissssi", $host, $port, $user_email, $pass, $from, $name, $setting['id']);
    } else {
        $stmt = $conn->prepare("INSERT INTO mail_settings (mail_host, mail_port, mail_username, mail_password, mail_from_email, mail_from_name) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissss", $host, $port, $user_email, $pass, $from, $name);
    }

    $stmt->execute();
    echo "<script>alert('Mail setting berhasil disimpan.'); window.location='mail_setting.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Mail Setting</title>
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
    <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
      <span>Konfigurasi Mail Server</span>
      <button type="button" class="btn btn-light btn-sm text-warning font-weight-bold" data-toggle="modal" data-target="#modalMail">
        + Tambah Setting
      </button>
    </div>

    <div class="card-body table-responsive">
      <?php if ($setting): ?>
      <table class="table table-bordered table-sm">
        <thead class="bg-primary text-white">
          <tr>
            <th>Host</th>
            <th>Port</th>
            <th>Username</th>
            <th>From Email</th>
            <th>From Name</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?= htmlspecialchars($setting['mail_host']) ?></td>
            <td><?= htmlspecialchars($setting['mail_port']) ?></td>
            <td><?= htmlspecialchars($setting['mail_username']) ?></td>
            <td><?= htmlspecialchars($setting['mail_from_email']) ?></td>
            <td><?= htmlspecialchars($setting['mail_from_name']) ?></td>
          </tr>
        </tbody>
      </table>
      <?php else: ?>
      <div class="text-muted">Belum ada konfigurasi email tersimpan.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="modalMail" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content shadow">
      <form method="POST">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Form Mail Setting</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">

          <div class="form-group">
            <label><i class="fas fa-server text-info mr-2"></i>SMTP Host</label>
            <input type="text" name="mail_host" class="form-control" value="<?= htmlspecialchars($setting['mail_host'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label><i class="fas fa-plug text-warning mr-2"></i>SMTP Port</label>
            <input type="number" name="mail_port" class="form-control" value="<?= htmlspecialchars($setting['mail_port'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label><i class="fas fa-envelope text-success mr-2"></i>SMTP Username (Email)</label>
            <input type="email" name="mail_username" class="form-control" value="<?= htmlspecialchars($setting['mail_username'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label><i class="fas fa-key text-danger mr-2"></i>SMTP Password</label>
            <input type="text" name="mail_password" class="form-control" value="<?= htmlspecialchars($setting['mail_password'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label><i class="fas fa-paper-plane text-primary mr-2"></i>From Email</label>
            <input type="email" name="mail_from_email" class="form-control" value="<?= htmlspecialchars($setting['mail_from_email'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label><i class="fas fa-user-tag text-secondary mr-2"></i>From Name</label>
            <input type="text" name="mail_from_name" class="form-control" value="<?= htmlspecialchars($setting['mail_from_name'] ?? '') ?>" required>
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
