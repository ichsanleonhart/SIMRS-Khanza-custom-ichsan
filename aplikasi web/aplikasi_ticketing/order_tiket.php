<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}




$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT nama, jabatan, unit_kerja FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kategori = $_POST['kategori'];
    $subjek = $_POST['subjek'];
    $deskripsi = $_POST['deskripsi'];
    $tanggal = date("Y-m-d H:i:s");

    $prefix = "TIK" . date("Ymd") . "-";
    $cek = $conn->query("SELECT RIGHT(nomor_tiket, 4) AS no FROM laporan WHERE DATE(tanggal) = CURDATE() ORDER BY id DESC LIMIT 1");
    $noUrut = ($cek->num_rows > 0) ? str_pad($cek->fetch_assoc()['no'] + 1, 4, "0", STR_PAD_LEFT) : "0001";
    $nomor_tiket = $prefix . $noUrut;

    $stmt = $conn->prepare("INSERT INTO laporan (user_id, kategori_id, subjek, deskripsi, tanggal, nomor_tiket, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iissss", $user_id, $kategori, $subjek, $deskripsi, $tanggal, $nomor_tiket);
    if ($stmt->execute()) {
        echo "<script>alert('Tiket berhasil dikirim!'); window.location='order_tiket.php';</script>";
    } else {
        echo "<script>alert('Gagal mengirim tiket.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Order Tiket</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal.fade .modal-dialog {
            animation: fadeInUp 0.4s ease-out;
        }
        .btn-success:hover {
            background-color: #218838 !important;
            transform: scale(1.02);
            transition: all 0.2s ease;
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                   
                </div>

                <!-- MODAL FORM -->
                <div class="modal fade" id="modalTiket" tabindex="-1" role="dialog" aria-labelledby="modalTiketLabel" aria-hidden="true">
                  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content shadow-lg rounded">
                      <form method="POST">
                        <div class="modal-header bg-primary text-white">
                          <h5 class="modal-title">Form Laporan Gangguan</h5>
                          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body py-3">
                          <div class="row">
                            <div class="col-md-6 pr-md-4">
                              <div class="form-group">
                                <label><strong>Nama</strong></label>
                                <input type="text" class="form-control" value="<?= $user['nama']; ?>" disabled>
                              </div>
                              <div class="form-group">
                                <label><strong>Jabatan</strong></label>
                                <input type="text" class="form-control" value="<?= $user['jabatan']; ?>" disabled>
                              </div>
                              <div class="form-group">
                                <label><strong>Unit Kerja</strong></label>
                                <input type="text" class="form-control" value="<?= $user['unit_kerja']; ?>" disabled>
                              </div>
                              <div class="form-group">
                                <label><strong>Kategori Gangguan</strong></label>
                                <select name="kategori" class="form-control" required>
                                  <option value="">-- Pilih Kategori --</option>
                                  <?php
                                  $kategoriList = $conn->query("SELECT id, nama_kategori FROM kategori_pelaporan ORDER BY nama_kategori");
                                  while ($row = $kategoriList->fetch_assoc()):
                                  ?>
                                    <option value="<?= $row['id']; ?>"><?= $row['nama_kategori']; ?></option>
                                  <?php endwhile; ?>
                                </select>
                              </div>
                            </div>
                            <div class="col-md-6 pl-md-4">
                              <div class="form-group">
                                <label><strong>Subjek</strong></label>
                                <input type="text" name="subjek" class="form-control" required>
                              </div>
                              <div class="form-group">
                                <label><strong>Deskripsi Gangguan</strong></label>
                                <textarea name="deskripsi" class="form-control" rows="8" required></textarea>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="modal-footer bg-light border-top-0">
                          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                          <button type="submit" class="btn btn-primary">Kirim Tiket</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <!-- RIWAYAT TIKET -->
                <div class="col-lg-12 mb-4">
                    <div class="card shadow">
                       <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <span>Riwayat Tiket Anda</span>
                            <button type="button" class="btn btn-light btn-sm text-info font-weight-bold" data-toggle="modal" data-target="#modalTiket">
                                + Buat Tiket Baru
                            </button>
                        </div>

                    <div class="card-body table-responsive">
    <table class="table table-bordered table-sm">
        <thead class="bg-primary text-white">
            <tr>
                <th>No</th>
                <th>No Tiket</th>
                <th>Kategori</th>
                <th>Subjek</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Catatan Admin</th>
                <th>Lihat</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $limit = 6;
            $page = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
            $start = ($page - 1) * $limit;
            $no = $start + 1;

            $riwayat = $conn->query("SELECT l.nomor_tiket, k.nama_kategori, l.subjek, l.tanggal, l.status, l.catatan_admin 
                                     FROM laporan l
                                     JOIN kategori_pelaporan k ON l.kategori_id = k.id
                                     WHERE l.user_id = $user_id
                                     ORDER BY l.tanggal DESC
                                     LIMIT $start, $limit");

            while ($row = $riwayat->fetch_assoc()):
                $status = $row['status'];
                $label = match ($status) {
                    'pending'   => '<span class="badge badge-secondary">Menunggu Diproses</span>',
                    'diperiksa' => '<span class="badge badge-warning">Sedang Ditinjau</span>',
                    'diproses'  => '<span class="badge badge-primary">Dalam Penanganan</span>',
                    'selesai'   => '<span class="badge badge-success">Selesai</span>',
                    'ditolak'   => '<span class="badge badge-danger">Ditolak</span>',
                    default     => '<span class="badge badge-light">-</span>',
                };
            ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= $row['nomor_tiket']; ?></td>
                <td><?= $row['nama_kategori']; ?></td>
                <td><?= htmlspecialchars($row['subjek']); ?></td>
                <td><?= date("d/m/Y H:i", strtotime($row['tanggal'])); ?></td>
                <td><?= $label; ?></td>
                <td><?= !empty($row['catatan_admin']) ? nl2br(htmlspecialchars($row['catatan_admin'])) : '-'; ?></td>
                <td class="text-center">
                    <a href="view_tiket.php?id=<?= urlencode($row['nomor_tiket']); ?>" target="_blank" class="text-info mr-2" title="Lihat Tiket">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php
    $total = $conn->query("SELECT COUNT(*) AS total FROM laporan WHERE user_id = $user_id")->fetch_assoc()['total'];
    $pages = ceil($total / $limit);
    ?>
    <nav>
        <ul class="pagination pagination-sm justify-content-end mb-0">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="?halaman=<?= $i; ?>"><?= $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

                </div>
            </div> <!-- /.container-fluid -->
        </div> <!-- /.content -->
    </div> <!-- /.content-wrapper -->
</div> <!-- /.wrapper -->

<?php include 'logout_modal.php'; ?>
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>
</body>
</html>
