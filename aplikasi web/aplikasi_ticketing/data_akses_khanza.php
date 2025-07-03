<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$tanggal_filter = isset($_GET['tanggal']) && $_GET['tanggal'] !== ''
    ? $_GET['tanggal']
    : date('Y-m-d');
$query = "SELECT 
             a.id, a.subjek, a.deskripsi, a.tanggal, a.status, a.catatan_admin,
             u.nama, u.jabatan, u.unit_kerja, k.nama_kategori
          FROM akses_khanza a
          JOIN users u ON a.user_id = u.id
          JOIN kategori_pelaporan k ON a.kategori_id = k.id
          WHERE DATE(a.tanggal) = '$tanggal_filter'
          ORDER BY a.tanggal DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Dashboard Admin - Akses KHanza</title>
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
                    <h1 class="h3 text-gray-800">Permintaan Akses KHanza</h1>
                </div>

                <h5 class="mb-3 text-gray-800 d-flex justify-content-between align-items-center">
                    <span>Daftar Permintaan</span>
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
                                <th>Nama</th>
                                <th>Unit Kerja</th>
                                <th>Kategori</th>
                                <th>Subjek</th>
                                <th>Deskripsi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
<?php
$no = 1;
if ($result->num_rows > 0):
    while ($row = $result->fetch_assoc()):
?>
<tr>
    <td class="text-center"><?= $no++; ?></td>
    <td><?= date("d/m/Y H:i", strtotime($row['tanggal'])); ?></td>
    <td><?= htmlspecialchars($row['nama']); ?><br><small><?= $row['jabatan']; ?></small></td>
    <td><?= htmlspecialchars($row['unit_kerja']); ?></td>
    <td><?= htmlspecialchars($row['nama_kategori']); ?></td>
    <td><?= htmlspecialchars($row['subjek']); ?></td>
    <td><?= nl2br(htmlspecialchars($row['deskripsi'])); ?></td>
    <td>
        <button class="badge badge-light" data-toggle="modal" data-target="#modalStatus<?= $row['id']; ?>" style="border: none;">
            <?= ucwords($row['status']); ?> ✎
        </button>
    </td>
</tr>

<!-- Modal -->
<div class="modal fade" id="modalStatus<?= $row['id']; ?>" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="proses_ubah_status_khanza.php">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Ubah Status Permintaan</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $row['id']; ?>">
          <p><strong>Subjek:</strong> <?= $row['subjek']; ?></p>
          <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control" required>
              <option value="pending"   <?= $row['status']=='pending' ? 'selected':''; ?>>Menunggu</option>
              <option value="diperiksa" <?= $row['status']=='diperiksa' ? 'selected':''; ?>>Ditinjau</option>
              <option value="diproses"  <?= $row['status']=='diproses' ? 'selected':''; ?>>Diproses</option>
              <option value="selesai"   <?= $row['status']=='selesai' ? 'selected':''; ?>>Selesai</option>
              <option value="ditolak"   <?= $row['status']=='ditolak' ? 'selected':''; ?>>Ditolak</option>
            </select>
          </div>
          <div class="form-group">
            <label>Catatan Admin (opsional)</label>
            <textarea name="catatan" class="form-control" rows="3"><?= htmlspecialchars($row['catatan_admin']); ?></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endwhile; else: ?>
<tr>
    <td colspan="8" class="text-center text-muted">Belum ada data permintaan yang tersedia.</td>
</tr>
<?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
<?php include 'logout_modal.php'; ?>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>
</body>
</html>
