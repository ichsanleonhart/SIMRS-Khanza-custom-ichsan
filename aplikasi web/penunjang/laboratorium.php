<?php
/*
 * ===================================================================================
 * DASHBOARD LABORATORIUM
 * ===================================================================================
 * Halaman ini menampilkan daftar pemeriksaan laboratorium untuk diisi kesan & saran.
 */
require_once 'config.php';
require_login();

// Pastikan hanya dokter lab yang bisa akses
if ($_SESSION['user_role'] !== 'laboratorium') {
    header('Location: index.php'); // Arahkan ke dashboard radiologi jika bukan dokter lab
    exit;
}

$pdo = connect_db();
$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-d');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');

// Query diadaptasi dari method tampil() di DlgCariPeriksaLab.java
$sql = "SELECT
            pl.no_rawat,
            rp.no_rkm_medis,
            p.nm_pasien,
            pl.tgl_periksa,
            pl.jam,
            dp_pj.nm_dokter AS dokter_penanggung_jawab,
            (SELECT COUNT(*) FROM saran_kesan_lab skl WHERE skl.no_rawat = pl.no_rawat AND skl.tgl_periksa = pl.tgl_periksa AND skl.jam = pl.jam) AS status_kesan
        FROM periksa_lab pl
        JOIN reg_periksa rp ON pl.no_rawat = rp.no_rawat
        JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        JOIN dokter dp_pj ON pl.kd_dokter = dp_pj.kd_dokter
        WHERE pl.kategori = 'PK' AND pl.tgl_periksa BETWEEN :tgl_awal AND :tgl_akhir
        GROUP BY pl.no_rawat, pl.tgl_periksa, pl.jam
        ORDER BY pl.tgl_periksa DESC, pl.jam DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':tgl_awal' => $tgl_awal, ':tgl_akhir' => $tgl_akhir]);
    $data_pemeriksaan = $stmt->fetchAll();
} catch (\PDOException $e) {
    error_log("Query Lab Gagal: " . $e->getMessage());
    $data_pemeriksaan = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Daftar Pasien Lab | <?php echo e($_SESSION['settings']['nama_instansi']); ?></title>
  <?php if (!empty($_SESSION['settings']['logo_base64'])): ?>
  <link rel="icon" type="image/png" href="data:image/png;base64,<?php echo $_SESSION['settings']['logo_base64']; ?>">
  <?php endif; ?>
  
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li></ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#"><i class="far fa-user"></i> <?php echo e($_SESSION['user_name']); ?></a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <a href="index.php?action=logout" class="dropdown-item"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
        </div>
      </li>
    </ul>
  </nav>

  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="index.php" class="brand-link">
       <?php if (!empty($_SESSION['settings']['logo_base64'])): ?><img src="data:image/png;base64,<?php echo $_SESSION['settings']['logo_base64']; ?>" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8"><?php endif; ?>
	  <span class="brand-text font-weight-light"><?php echo e($_SESSION['settings']['nama_instansi']); ?></span> <br>
      <span style="display:block; text-align:center;" class="brand-text font-weight-light">Expertise App</span>
    </a> 
    <div class="sidebar">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">          
          <li class="nav-item"><a href="laboratorium.php" class="nav-link active"><i class="nav-icon fas fa-flask"></i><p>Laboratorium</p></a></li>
        </ul>
      </nav>
    </div>
  </aside>

  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid"><h1>Daftar Pasien Laboratorium</h1></div>
    </section>
    <section class="content">
      <div class="container-fluid">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Filter Data Pemeriksaan Laboratorium</h3></div>
          <div class="card-body">
            <form action="laboratorium.php" method="get" class="row">
                <div class="col-md-5"><label for="tgl_awal">Tgl Awal</label><input type="date" class="form-control" id="tgl_awal" name="tgl_awal" value="<?php echo e($tgl_awal); ?>"></div>
                <div class="col-md-5"><label for="tgl_akhir">Tgl Akhir</label><input type="date" class="form-control" id="tgl_akhir" name="tgl_akhir" value="<?php echo e($tgl_akhir); ?>"></div>
                <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100">Filter</button></div>
            </form>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><h3 class="card-title">Hasil Pemeriksaan Pasien</h3></div>
          <div class="card-body">
            <table id="labTable" class="table table-bordered table-hover">
              <thead><tr><th>No. Rawat</th><th>No. RM</th><th>Nama Pasien</th><th>Tgl & Jam Periksa</th><th>Dokter P.J.</th><th>Aksi</th></tr></thead>
              <tbody>
              <?php foreach ($data_pemeriksaan as $row): ?>
                  <?php $detail_url = sprintf("hasil_laboratorium.php?no_rawat=%s&tgl_periksa=%s&jam=%s", urlencode($row['no_rawat']), urlencode($row['tgl_periksa']), urlencode($row['jam'])); ?>
                  <tr>
                      <td><?php echo e($row['no_rawat']); ?></td>
                      <td><?php echo e($row['no_rkm_medis']); ?></td>
                      <td><?php echo e($row['nm_pasien']); ?></td>
                      <td><?php echo e($row['tgl_periksa']); ?> <?php echo e($row['jam']); ?></td>
                      <td><?php echo e($row['dokter_penanggung_jawab']); ?></td>
                      <td class="text-center">
                          <a href="<?php echo e($detail_url); ?>" class="btn btn-sm <?php echo ($row['status_kesan'] > 0) ? 'btn-success' : 'btn-warning'; ?>">
                              <i class="fas fa-file-medical-alt"></i> <?php echo ($row['status_kesan'] > 0) ? 'Lihat/Edit Kesan' : 'Input Kesan'; ?>
                          </a>
                      </td>
                  </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
  </div>
  <footer class="main-footer">
    <div class="float-right d-none d-sm-block"><b>Version</b> 2.0.0</div>
    <strong>&copy; <?php echo date("Y"); ?> IT <?php echo e($_SESSION['settings']['nama_instansi']); ?>.</strong>
  </footer>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
  $(function () {
    $("#labTable").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false, "searching": true, "paging": true, "info": true, "ordering": true,
      "language": { "search": "Cari data:", "emptyTable": "Tidak ada data ditemukan", "zeroRecords": "Tidak ada data yang cocok ditemukan", "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri", "infoEmpty": "Menampilkan 0 sampai 0 dari 0 entri", "infoFiltered": "(difilter dari _MAX_ total entri)", "paginate": { "first": "Pertama", "last": "Terakhir", "next": "Selanjutnya", "previous": "Sebelumnya" } }
    });
  });
</script>
</body>
</html>
