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

// Proses Simpan Jatah Cuti
if (isset($_POST['simpan'])) {
  $id_karyawan = intval($_POST['id_karyawan']);
  $id_cuti     = intval($_POST['id_cuti']);
  $waktu       = date('Y-m-d H:i:s');
  $user_input  = $_SESSION['user_id'];

  // Cek apakah data dengan id_karyawan dan id_cuti ini sudah ada
  $cek = mysqli_query($conn, "SELECT * FROM jatah_cuti WHERE id_karyawan = $id_karyawan AND id_cuti = $id_cuti");

  if (mysqli_num_rows($cek) > 0) {
    echo "<script>
      alert('❌ Data cuti ini sudah ada untuk karyawan tersebut!');
      window.location.href = 'jatah_cuti.php';
    </script>";
  } else {
    $query = mysqli_query($conn, "INSERT INTO jatah_cuti (id_karyawan, id_cuti, waktu_input, user_input) VALUES ($id_karyawan, $id_cuti, '$waktu', $user_input)");

    if ($query) {
      echo "<script>
        alert('✅ Jatah cuti berhasil disimpan.');
        window.location.href = 'jatah_cuti.php';
      </script>";
    } else {
      echo "<script>
        alert('❌ Gagal menyimpan jatah cuti!');
        window.location.href = 'jatah_cuti.php';
      </script>";
    }
  }
}


// Ambil data untuk dropdown dan tabel
$data_karyawan = mysqli_query($conn, "SELECT id, nama FROM users ORDER BY nama ASC");
$data_cuti     = mysqli_query($conn, "SELECT * FROM master_cuti ORDER BY nama_cuti ASC");
$data_jatah    = mysqli_query($conn, "
  SELECT jc.*, u.nama AS nama_karyawan, mc.nama_cuti, mc.keterangan
  FROM jatah_cuti jc
  JOIN users u ON jc.id_karyawan = u.id
  JOIN master_cuti mc ON jc.id_cuti = mc.id
  ORDER BY jc.waktu_input DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>f.i.x.p.o.i.n.t</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <style>
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
    .cuti-table {
      font-size: 13px;
      white-space: nowrap;
    }
    .cuti-table th, .cuti-table td {
      padding: 6px 10px;
      vertical-align: middle;
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
              <h4 class="mb-0">Jatah Cuti</h4>
            </div>

            <div class="card-body">
              <!-- Tab menu -->
              <ul class="nav nav-tabs" id="cutiTab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="input-tab" data-toggle="tab" href="#input" role="tab">Input Data</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="data-tab" data-toggle="tab" href="#data" role="tab">Data Jatah Cuti</a>
                </li>
              </ul>

              <!-- Tab Content -->
              <div class="tab-content mt-3">
                <!-- Form Input -->
                <div class="tab-pane fade show active" id="input" role="tabpanel">
                  <form method="POST">
                    <div class="form-group">
                      <label>Pilih Karyawan</label>
                      <select name="id_karyawan" class="form-control" required>
                        <option value="">-- Pilih Karyawan --</option>
                        <?php while($kar = mysqli_fetch_assoc($data_karyawan)) : ?>
                          <option value="<?= $kar['id'] ?>"><?= htmlspecialchars($kar['nama']) ?></option>
                        <?php endwhile; ?>
                      </select>
                    </div>

                    <div class="form-group">
                      <label>Jenis Cuti</label>
                      <select name="id_cuti" id="id_cuti" class="form-control" required>
                        <option value="">-- Pilih Jenis Cuti --</option>
                        <?php 
                          mysqli_data_seek($data_cuti, 0);
                          while($cuti = mysqli_fetch_assoc($data_cuti)) : 
                        ?>
                        <option 
                          value="<?= $cuti['id'] ?>" 
                          data-ket="<?= htmlspecialchars($cuti['keterangan']) ?>" 
                          data-hari="<?= htmlspecialchars($cuti['maks_hari']) ?>">
                          <?= htmlspecialchars($cuti['nama_cuti']) ?>
                        </option>
                        <?php endwhile; ?>
                      </select>
                    </div>

                    <div class="form-group">
                      <label>Jatah Hari</label>
                      <input type="number" name="jatah_hari" id="jatah_hari" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                      <label>Keterangan</label>
                      <textarea name="keterangan" class="form-control" id="keterangan" rows="3" readonly></textarea>
                    </div>

                    <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                  </form>
                </div>

                <!-- Tabel Data -->
                <div class="tab-pane fade" id="data" role="tabpanel">
                  <div class="table-responsive">
                    <table class="table table-bordered cuti-table">
                      <thead class="thead-dark">
                        <tr>
                          <th>No</th>
                          <th>Nama Karyawan</th>
                          <th>Jenis Cuti</th>
                          <th>Jatah Hari</th>
                          <th>Keterangan</th>
                          <th>Tanggal Input</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $no = 1; while ($jatah = mysqli_fetch_assoc($data_jatah)) : ?>
                          <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($jatah['nama_karyawan']) ?></td>
                            <td><?= htmlspecialchars($jatah['nama_cuti']) ?></td>
                            <td><?= $jatah['jatah_hari'] ?> hari</td>
                            <td><?= htmlspecialchars($jatah['keterangan']) ?></td>
                            <td><?= date('d-m-Y H:i', strtotime($jatah['waktu_input'])) ?></td>
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

    $('#id_cuti').on('change', function() {
      const selected = this.options[this.selectedIndex];
      $('#keterangan').val(selected.getAttribute('data-ket') || '');
      $('#jatah_hari').val(selected.getAttribute('data-hari') || '');
    });

    $('#id_cuti').trigger('change');
  });
</script>
</body>
</html>
