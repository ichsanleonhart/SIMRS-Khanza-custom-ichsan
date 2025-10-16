<?php
/*
 * ===================================================================================
 * HALAMAN DETAIL HASIL LABORATORIUM
 * ===================================================================================
 */
require_once 'config.php';
require_login();

// Pastikan hanya dokter lab yang bisa akses
if ($_SESSION['user_role'] !== 'laboratorium') {
    header('Location: index.php');
    exit;
}

$no_rawat = $_GET['no_rawat'] ?? null;
$tgl_periksa = $_GET['tgl_periksa'] ?? null;
$jam = $_GET['jam'] ?? null;

if (!$no_rawat || !$tgl_periksa || !$jam) {
    header("Location: laboratorium.php");
    exit;
}

$pdo = connect_db();
$message = '';
$message_type = '';

// Proses Simpan/Update Kesan & Saran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die('Aksi tidak diizinkan: Invalid CSRF Token.');
    }

    $kesan = $_POST['kesan'] ?? '';
    $saran = $_POST['saran'] ?? '';

    try {
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM saran_kesan_lab WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?");
        $stmt_check->execute([$no_rawat, $tgl_periksa, $jam]);
        $record_exists = $stmt_check->fetchColumn() > 0;

        if ($record_exists) {
            $stmt_update = $pdo->prepare("UPDATE saran_kesan_lab SET kesan = ?, saran = ? WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?");
            $stmt_update->execute([$kesan, $saran, $no_rawat, $tgl_periksa, $jam]);
        } else {
            $stmt_insert = $pdo->prepare("INSERT INTO saran_kesan_lab (no_rawat, tgl_periksa, jam, kesan, saran) VALUES (?, ?, ?, ?, ?)");
            $stmt_insert->execute([$no_rawat, $tgl_periksa, $jam, $kesan, $saran]);
        }
        $message = "Kesan dan Saran berhasil disimpan!";
        $message_type = 'success';
    } catch (\PDOException $e) {
        error_log("Simpan Kesan/Saran Gagal: " . $e->getMessage());
        $message = "Terjadi kesalahan saat menyimpan data.";
        $message_type = 'danger';
    }
}


// Ambil data untuk tampilan
try {
    // Info Pasien
    $sql_pasien = "SELECT p.no_rkm_medis, p.nm_pasien, p.tgl_lahir, p.jk, pl.no_rawat, d_pj.nm_dokter AS dokter_pj, d_perujuk.nm_dokter AS dokter_perujuk FROM periksa_lab pl JOIN reg_periksa rp ON pl.no_rawat = rp.no_rawat JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis JOIN dokter d_pj ON pl.kd_dokter = d_pj.kd_dokter LEFT JOIN dokter d_perujuk ON pl.dokter_perujuk = d_perujuk.kd_dokter WHERE pl.no_rawat = ? AND pl.tgl_periksa = ? AND pl.jam = ? GROUP BY pl.no_rawat";
    $stmt_pasien = $pdo->prepare($sql_pasien);
    $stmt_pasien->execute([$no_rawat, $tgl_periksa, $jam]);
    $data_pasien = $stmt_pasien->fetch();

    // Ambil jenis-jenis pemeriksaan
    $sql_jenis_pemeriksaan = "SELECT jpl.kd_jenis_prw, jpl.nm_perawatan FROM periksa_lab pl JOIN jns_perawatan_lab jpl ON pl.kd_jenis_prw = jpl.kd_jenis_prw WHERE pl.no_rawat = ? AND pl.tgl_periksa = ? AND pl.jam = ? ORDER BY jpl.kd_jenis_prw";
    $stmt_jenis = $pdo->prepare($sql_jenis_pemeriksaan);
    $stmt_jenis->execute([$no_rawat, $tgl_periksa, $jam]);
    $jenis_pemeriksaan = $stmt_jenis->fetchAll();
    
    // Ambil detail hasil & gabungkan ke jenis pemeriksaan
    $hasil_pemeriksaan_grouped = [];
    foreach($jenis_pemeriksaan as $jenis) {
        $sql_detail = "SELECT tl.Pemeriksaan, dpl.nilai, tl.satuan, dpl.nilai_rujukan, dpl.keterangan FROM detail_periksa_lab dpl JOIN template_laboratorium tl ON dpl.id_template = tl.id_template WHERE dpl.no_rawat = ? AND dpl.tgl_periksa = ? AND dpl.jam = ? AND dpl.kd_jenis_prw = ? ORDER BY tl.urut";
        $stmt_detail = $pdo->prepare($sql_detail);
        $stmt_detail->execute([$no_rawat, $tgl_periksa, $jam, $jenis['kd_jenis_prw']]);
        $details = $stmt_detail->fetchAll();
        $hasil_pemeriksaan_grouped[] = [
            'nama_pemeriksaan' => $jenis['nm_perawatan'],
            'detail' => $details
        ];
    }
    
    // Ambil Kesan & Saran yang sudah ada
    $stmt_kesan = $pdo->prepare("SELECT kesan, saran FROM saran_kesan_lab WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?");
    $stmt_kesan->execute([$no_rawat, $tgl_periksa, $jam]);
    $data_kesan_saran = $stmt_kesan->fetch();
    $kesan_tersimpan = $data_kesan_saran['kesan'] ?? '';
    $saran_tersimpan = $data_kesan_saran['saran'] ?? '';

} catch (\PDOException $e) {
    die("Gagal mengambil data: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hasil Lab | <?php echo e($data_pasien['nm_pasien'] ?? 'N/A'); ?></title>
  <?php if (!empty($_SESSION['settings']['logo_base64'])): ?><link rel="icon" type="image/png" href="data:image/png;base64,<?php echo $_SESSION['settings']['logo_base64']; ?>"><?php endif; ?>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <style>.text-low { color: blue; } .text-high { color: red; }</style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li></ul>
    <ul class="navbar-nav ml-auto"><li class="nav-item dropdown"><a class="nav-link" data-toggle="dropdown" href="#"><i class="far fa-user"></i> <?php echo e($_SESSION['user_name']); ?></a><div class="dropdown-menu dropdown-menu-lg dropdown-menu-right"><a href="index.php?action=logout" class="dropdown-item"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a></div></li></ul>
  </nav>

  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="index.php" class="brand-link"><?php if (!empty($_SESSION['settings']['logo_base64'])): ?><img src="data:image/png;base64,<?php echo $_SESSION['settings']['logo_base64']; ?>" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8"><?php endif; ?><span class="brand-text font-weight-light">Expertise App</span></a>
    <div class="sidebar"><nav class="mt-2"><ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false"><li class="nav-item"><a href="index.php" class="nav-link"><i class="nav-icon fas fa-x-ray"></i><p>Radiologi</p></a></li><li class="nav-item"><a href="laboratorium.php" class="nav-link active"><i class="nav-icon fas fa-flask"></i><p>Laboratorium</p></a></li></ul></nav></div>
  </aside>

  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-8"><h1>Hasil Pemeriksaan Laboratorium</h1></div>
          <div class="col-sm-4"><a href="laboratorium.php" class="btn btn-secondary float-sm-right"><i class="fas fa-arrow-left"></i> Kembali ke Daftar</a></div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-<?php echo e($message_type); ?> alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button><h5><i class="icon fas fa-check"></i> Info</h5><?php echo e($message); ?></div><?php endif; ?>
        <div class="card card-primary card-outline">
          <div class="card-header">
            <div class="row">
              <div class="col-md-6"><strong>Pasien:</strong> <?php echo e($data_pasien['nm_pasien']); ?> (<?php echo e($data_pasien['no_rkm_medis']); ?>)</div>
              <div class="col-md-6"><strong>Tgl Periksa:</strong> <?php echo e($tgl_periksa); ?> <?php echo e($jam); ?></div>
              <div class="col-md-6"><strong>Dr. P.J.:</strong> <?php echo e($data_pasien['dokter_pj']); ?></div>
              <div class="col-md-6"><strong>Dr. Perujuk:</strong> <?php echo e($data_pasien['dokter_perujuk'] ?? '-'); ?></div>
            </div>
          </div>
          <div class="card-body">
            <?php foreach($hasil_pemeriksaan_grouped as $pemeriksaan): ?>
                <h5 class="mt-4"><strong><?php echo e($pemeriksaan['nama_pemeriksaan']); ?></strong></h5>
                <table class="table table-sm table-bordered table-striped">
                    <thead class="thead-light"><tr><th>Pemeriksaan</th><th>Hasil</th><th>Satuan</th><th>Nilai Rujukan</th><th>Keterangan</th></tr></thead>
                    <tbody>
                        <?php foreach($pemeriksaan['detail'] as $detail): ?>
                            <?php
                                $row_class = '';
                                if (strpos($detail['keterangan'], 'L') !== false) $row_class = 'text-low';
                                if (strpos($detail['keterangan'], 'H') !== false) $row_class = 'text-high';
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td><?php echo e($detail['Pemeriksaan']); ?></td>
                                <td><?php echo e($detail['nilai']); ?></td>
                                <td><?php echo e($detail['satuan']); ?></td>
                                <td><?php echo e($detail['nilai_rujukan']); ?></td>
                                <td><?php echo e($detail['keterangan']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
          </div>
          <div class="card-footer text-center">
            <button type="button" class="btn btn-lg btn-success" data-toggle="modal" data-target="#kesanSaranModal"><i class="fas fa-comment-medical"></i> Input/Edit Kesan & Saran</button>
          </div>
        </div>
      </div>
    </section>
  </div>
  
  <div class="modal fade" id="kesanSaranModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <form action="" method="post">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
          <div class="modal-header">
            <h5 class="modal-title">Input Kesan & Saran Klinis</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label for="kesan">Kesan</label>
              <textarea name="kesan" id="kesan" class="form-control" rows="5"><?php echo e($kesan_tersimpan); ?></textarea>
            </div>
            <div class="form-group">
              <label for="saran">Saran</label>
              <textarea name="saran" id="saran" class="form-control" rows="5"><?php echo e($saran_tersimpan); ?></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <footer class="main-footer"><strong>&copy; <?php echo date("Y"); ?> IT <?php echo e($_SESSION['settings']['nama_instansi']); ?>.</strong></footer>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
