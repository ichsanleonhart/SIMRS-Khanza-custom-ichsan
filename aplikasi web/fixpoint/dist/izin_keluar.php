<?php
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];

$current_file = basename(__FILE__); // 

// Cek apakah user boleh mengakses halaman ini
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

// Ambil data user login
$user_id = $_SESSION['user_id'];
$user_query = mysqli_query($conn, "SELECT u.*, a.nama AS nama_atasan 
                                    FROM users u 
                                    LEFT JOIN users a ON u.atasan_id = a.id 
                                    WHERE u.id = $user_id");

$user = mysqli_fetch_assoc($user_query);
if (!$user) {
  die("User tidak ditemukan.");
}

// Proses Simpan
if (isset($_POST['simpan'])) {
  $nik         = $user['nik'];
  $nama        = $user['nama'];
  $jabatan     = $user['jabatan'];
  $unit_kerja  = $user['unit_kerja'];
  $atasan      = $user['nama_atasan'];
  $jam_keluar  = $_POST['jam_keluar'];
  $jam_kembali = $_POST['jam_kembali'];
  $alasan      = mysqli_real_escape_string($conn, $_POST['alasan']);
  $status = 'Menunggu'; // status umum
$status_atasan = 'Menunggu';
$status_hrd = 'Menunggu';

  $waktu_input = date('Y-m-d H:i:s');

$query = "INSERT INTO izin_keluar 
(nik, nama, jabatan, unit_kerja, atasan, jam_keluar, jam_kembali, alasan, status, status_atasan, status_hrd, waktu_input) 
VALUES 
('$nik', '$nama', '$jabatan', '$unit_kerja', '$atasan', '$jam_keluar', '$jam_kembali', '$alasan', '$status', '$status_atasan', '$status_hrd', '$waktu_input')";


  if (mysqli_query($conn, $query)) {
    $_SESSION['flash_message'] = "✅ Izin keluar berhasil disimpan.";
    header("Location: izin_keluar.php");
    exit;
  } else {
    $_SESSION['flash_message'] = "❌ Gagal menyimpan data: " . mysqli_error($conn);
  }
}

// Ambil data izin
$data_izin = mysqli_query($conn, "SELECT * FROM izin_keluar ORDER BY waktu_input DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>f.i.x.p.o.i.n.t</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/css/components.css" />
  <style>
    .izin-table {
      font-size: 13px;
      white-space: nowrap;
    }
    .izin-table th, .izin-table td {
      padding: 6px 10px;
      vertical-align: middle;
    }
    .flash-center {
      position: fixed;
      top: 20%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 1050;
      min-width: 300px;
      max-width: 90%;
      text-align: center;
      padding: 15px;
      border-radius: 8px;
      font-weight: 500;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
  </style>
</head>
<body>
<div id="app">
  <div class="main-wrapper main-wrapper-1">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
      <section class="section">
        <div class="section-body">

          <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-info flash-center" id="flashMsg">
              <?= $_SESSION['flash_message'] ?>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
          <?php endif; ?>

          <div class="card">
            <div class="card-header">
              <h4 class="mb-0">Form Izin Keluar</h4>
            </div>

            <div class="card-body">
              <ul class="nav nav-tabs" id="izinTab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="input-tab" data-toggle="tab" href="#input" role="tab">Input Izin</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="data-tab" data-toggle="tab" href="#data" role="tab">Data Izin</a>
                </li>
              </ul>

              <div class="tab-content mt-3">
                <!-- Form Input -->
                <div class="tab-pane fade show active" id="input" role="tabpanel">
                <form method="POST">
  <div class="row">
    <!-- Kiri -->
    <div class="col-md-6">
      <div class="form-group">
        <label>NIK</label>
        <input type="text" name="nik_display" class="form-control" value="<?= htmlspecialchars($user['nik']) ?>" readonly>
      </div>
      <div class="form-group">
        <label>Nama</label>
        <input type="text" name="nama_display" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" readonly>
      </div>
      <div class="form-group">
        <label>Jabatan</label>
        <input type="text" name="jabatan_display" class="form-control" value="<?= htmlspecialchars($user['jabatan']) ?>" readonly>
      </div>
      <div class="form-group">
        <label>Jam Keluar</label>
        <input type="time" name="jam_keluar" class="form-control" required>
      </div>
    </div>

    <!-- Kanan -->
    <div class="col-md-6">
      <div class="form-group">
        <label>Unit Kerja</label>
        <input type="text" name="unit_kerja_display" class="form-control" value="<?= htmlspecialchars($user['unit_kerja']) ?>" readonly>
      </div>
      <div class="form-group">
        <label>Atasan Langsung</label>
        <input type="text" name="atasan_display" class="form-control" value="<?= htmlspecialchars($user['nama_atasan']) ?>" readonly>
      </div>
      <div class="form-group">
        <label>Jam Kembali</label>
        <input type="time" name="jam_kembali" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Alasan</label>
        <textarea name="alasan" class="form-control" rows="3" required></textarea>
      </div>
    </div>
  </div>

  <input type="hidden" name="status" value="Menunggu">
  <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
</form>

                </div>

                <!-- Tabel Data -->
                <div class="tab-pane fade" id="data" role="tabpanel">
                  <div class="table-responsive">
                    <table class="table table-bordered izin-table">
                      <thead class="thead-dark">
                        <tr>
                          <th>No</th>
                          <th>NIK</th>
                          <th>Nama</th>
                          <th>Jabatan</th>
                          <th>Unit Kerja</th>
                          <th>Atasan</th>
                          <th>Jam Keluar</th>
                          <th>Jam Kembali</th>
                          <th>Alasan</th>
                          <th>Status</th>
                          <th>Tanggal Input</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $no = 1; while ($izin = mysqli_fetch_assoc($data_izin)) : ?>
                          <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($izin['nik']) ?></td>
                            <td><?= htmlspecialchars($izin['nama']) ?></td>
                            <td><?= htmlspecialchars($izin['jabatan']) ?></td>
                            <td><?= htmlspecialchars($izin['unit_kerja']) ?></td>
                            <td><?= htmlspecialchars($izin['atasan']) ?></td>
                            <td><?= htmlspecialchars($izin['jam_keluar']) ?></td>
                            <td><?= htmlspecialchars($izin['jam_kembali']) ?></td>
                            <td><?= htmlspecialchars($izin['alasan']) ?></td>
                            <td><?= htmlspecialchars($izin['status']) ?></td>
                            <td><?= date('d-m-Y H:i', strtotime($izin['waktu_input'])) ?></td>
                          </tr>
                        <?php endwhile; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div> <!-- End Tab Content -->
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>

<!-- JS -->
  <script src="assets/modules/jquery.min.js"></script>
  <script src="assets/modules/popper.js"></script>
  <script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
  <script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
  <script src="assets/modules/moment.min.js"></script>
  <script src="assets/js/stisla.js"></script>
  <script src="assets/js/scripts.js"></script>
  <script src="assets/js/custom.js"></script>
<script>
  $(document).ready(function() {
    setTimeout(function() {
      $("#flashMsg").fadeOut("slow");
    }, 3000);
  });
</script>
</body>
</html>
