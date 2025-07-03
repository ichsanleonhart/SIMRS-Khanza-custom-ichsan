<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$tanggal_filter = isset($_GET['tanggal']) && $_GET['tanggal'] !== ''
    ? $_GET['tanggal']
    : date('Y-m-d');

$query = "
  SELECT l.nomor_tiket, l.tanggal AS waktu_permintaan, 
         h.status AS status_terakhir, h.waktu AS waktu_selesai, 
         u.nama AS petugas
  FROM laporan l
  LEFT JOIN (
      SELECT hs1.* FROM histori_status hs1
      JOIN (
          SELECT laporan_id, MAX(waktu) AS waktu_max
          FROM histori_status GROUP BY laporan_id
      ) hs2 ON hs1.laporan_id = hs2.laporan_id AND hs1.waktu = hs2.waktu_max
  ) h ON l.id = h.laporan_id
  LEFT JOIN users u ON h.admin_id = u.id
  WHERE DATE(l.tanggal) = '$tanggal_filter'
  ORDER BY l.tanggal DESC
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Laporan Proses Tiket</title>
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
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 text-gray-800">Handling Time IT</h1>
  </div>

  <h5 class="mb-3 text-gray-800 d-flex justify-content-between align-items-center">
    <span>Record Jam Kerja</span>
    <form method="get" class="form-inline">
      <label class="mr-2 mb-0 font-weight-bold text-dark">Filter Tanggal</label>
      <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal_filter); ?>" class="form-control form-control-sm mr-2">
      <button type="submit" class="btn btn-sm btn-primary">Tampilkan</button>
    </form>
  </h5>

  <div class="table-responsive">
    <table class="table table-bordered table-sm">
      <thead class="bg-primary text-white">
        <tr>
          <th>No</th>
          <th>Tanggal</th>
          <th>No Tiket</th>
          <th>Status</th>
          <th>Jam Permintaan</th>
          <th>Jam Selesai</th>
          <th>Durasi</th>
          <th>Petugas</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $no = 1;
        while ($row = $result->fetch_assoc()):
          $durasi = '-';
          $jam_permintaan = '-';
          $jam_selesai    = '-';
          $tanggal = '-';

          if (!empty($row['waktu_permintaan'])) {
              $tanggal = date("d/m/Y", strtotime($row['waktu_permintaan']));
              $jam_permintaan = date("H:i", strtotime($row['waktu_permintaan']));
          }

          if (!empty($row['waktu_selesai'])) {
              $jam_selesai = date("H:i", strtotime($row['waktu_selesai']));
          }

          if (!empty($row['waktu_permintaan']) && !empty($row['waktu_selesai'])) {
              try {
                  $start = new DateTime($row['waktu_permintaan']);
                  $end   = new DateTime($row['waktu_selesai']);
                  $totalDetik = $end->getTimestamp() - $start->getTimestamp();
                  $totalMenit = ceil($totalDetik / 60);
                  $jam = floor($totalMenit / 60);
                  $menit = $totalMenit % 60;
                  $durasi = ($jam ? $jam . ' jam ' : '') . $menit . ' menit';
              } catch (Exception $e) {
                  $durasi = 'Error';
              }
          }
        ?>
        <tr>
          <td class="text-center"><?= $no++; ?></td>
          <td><?= $tanggal; ?></td>
          <td><?= htmlspecialchars($row['nomor_tiket']); ?></td>
          <td><?= ucwords($row['status_terakhir'] ?? '-'); ?></td>
          <td><?= $jam_permintaan; ?></td>
          <td><?= $jam_selesai; ?></td>
          <td><?= $durasi ?></td>
          <td><?= htmlspecialchars($row['petugas'] ?? '-'); ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
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
