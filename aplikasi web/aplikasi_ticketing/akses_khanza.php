<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil info user
$stmtUser = $conn->prepare("SELECT nama, jabatan, unit_kerja FROM users WHERE id = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$user = $stmtUser->get_result()->fetch_assoc();

// Simpan permintaan jika ada POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kategori   = $_POST['kategori'];
    $subjek     = $_POST['subjek'];
    $deskripsi  = $_POST['deskripsi'];
    $tanggal    = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("INSERT INTO akses_khanza (user_id, kategori_id, subjek, deskripsi, tanggal, status)
                            VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iisss", $user_id, $kategori, $subjek, $deskripsi, $tanggal);

    if ($stmt->execute()) {
        echo "<script>alert('Permintaan akses berhasil dikirim!'); window.location='akses_khanza.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal mengirim permintaan: " . $conn->error . "');</script>";
    }
}

// Pagination
$limit  = 10;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page   = max($page, 1);
$offset = ($page - 1) * $limit;

$total_query  = $conn->query("SELECT COUNT(*) AS total FROM akses_khanza WHERE user_id = $user_id");
$total_data   = $total_query->fetch_assoc()['total'];
$total_pages  = ceil($total_data / $limit);

// Data paginasi
$riwayat = $conn->query("SELECT a.id, a.subjek, a.deskripsi, a.tanggal, a.status, k.nama_kategori
                         FROM akses_khanza a
                         JOIN kategori_pelaporan k ON a.kategori_id = k.id
                         WHERE a.user_id = $user_id
                         ORDER BY a.tanggal DESC
                         LIMIT $limit OFFSET $offset");
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

             <div class="modal fade" id="modalTiket" tabindex="-1" role="dialog" aria-labelledby="modalTiketLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Form Buka Akses Khanza</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body py-3">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label><strong>Nama</strong></label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['nama']); ?>" disabled>
              </div>
              <div class="form-group">
                <label><strong>Jabatan</strong></label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['jabatan']); ?>" disabled>
              </div>
              <div class="form-group">
                <label><strong>Unit Kerja</strong></label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['unit_kerja']); ?>" disabled>
              </div>
              <div class="form-group">
                <label><strong>Kategori Akses</strong></label>
                <select name="kategori" class="form-control" required>
                  <option value="">-- Pilih Kategori --</option>
                  <?php
                  $kategoriList = $conn->query("SELECT id, nama_kategori FROM kategori_pelaporan ORDER BY nama_kategori");
                  while ($row = $kategoriList->fetch_assoc()):
                  ?>
                    <option value="<?= $row['id']; ?>"><?= htmlspecialchars($row['nama_kategori']); ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label><strong>Subjek</strong></label>
                <input type="text" name="subjek" class="form-control" required>
              </div>
              <div class="form-group">
                <label><strong>Akses Yang Diminta</strong></label>
                <textarea name="deskripsi" class="form-control" rows="6" required></textarea>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Kirim Permintaan</button>
        </div>
      </form>
    </div>
  </div>
</div>

              <div class="card shadow">
  <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
    <span>Riwayat Permintaan Anda</span>
    <button class="btn btn-sm btn-light text-primary font-weight-bold" data-toggle="modal" data-target="#modalTiket">
      <i class="fas fa-plus-circle mr-1"></i> Tambah Akses
    </button>
  </div>

   <div class="card-body table-responsive">
  <table class="table table-bordered table-sm">
    <thead class="bg-primary text-white">
      <tr>
        <th class="text-center" style="width: 40px;">No</th>
        <th>Jenis</th>
        <th>Kategori</th>
        <th>Nama Akses</th>
        <th>Tanggal</th>
        <th>Status</th>
        <th>Lihat</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($riwayat->num_rows > 0): $no = 1; while ($row = $riwayat->fetch_assoc()): ?>
      <tr>
        <td class="text-center"><?= $no++; ?></td>
        <td><?= htmlspecialchars($row['subjek']); ?></td>
        <td><?= htmlspecialchars($row['nama_kategori']); ?></td>
        <td><?= nl2br(htmlspecialchars($row['deskripsi'])); ?></td>
        <td><?= date("d/m/Y H:i", strtotime($row['tanggal'])); ?></td>
        <td>
          <?php
          $badge = match ($row['status']) {
            'pending'   => 'secondary',
            'diperiksa' => 'warning',
            'diproses'  => 'primary',
            'selesai'   => 'success',
            'ditolak'   => 'danger',
            default     => 'light',
          };
          ?>
          <span class="badge badge-<?= $badge; ?>"><?= ucfirst($row['status']); ?></span>
        </td>
        <td class="text-center">
          <a href="lihat_permintaan_akses.php?id=<?= $row['id']; ?>" target="_blank" class="btn btn-sm btn-info">
            <i class="fas fa-file-pdf"></i> PDF
          </a>
        </td>
      </tr>
      <?php endwhile; else: ?>
      <tr><td colspan="7" class="text-center text-muted">Belum ada permintaan.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <nav>
    <ul class="pagination pagination-sm justify-content-end mb-0">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
          <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
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
