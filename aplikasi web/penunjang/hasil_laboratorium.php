<?php
/*
 * ===================================================================================
 * HALAMAN DETAIL HASIL LABORATORIUM
 * ===================================================================================
 * MODIFIKASI:
 * - Penambahan tombol "Riwayat Pasien"
 * - Penambahan modal untuk menampilkan riwayat (via AJAX)
 * - Penambahan event listener jQuery untuk tombol riwayat
 * - (BARU) Penambahan query untuk mengambil diagnosa/info klinis dari permintaan_lab
 * - (BARU) Penambahan card untuk menampilkan diagnosa/info klinis
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
        $exists = $stmt_check->fetchColumn();

        if ($exists) {
            // Update
            $sql = "UPDATE saran_kesan_lab SET kesan = ?, saran = ? WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$kesan, $saran, $no_rawat, $tgl_periksa, $jam]);
            $action = 'Update';
            $data = ['kesan' => $kesan, 'saran' => $saran, 'no_rawat' => $no_rawat, 'tgl_periksa' => $tgl_periksa, 'jam' => $jam];
        } else {
            // Insert
            $sql = "INSERT INTO saran_kesan_lab (no_rawat, tgl_periksa, jam, kesan, saran) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$no_rawat, $tgl_periksa, $jam, $kesan, $saran]);
            $action = 'Insert';
            $data = ['no_rawat' => $no_rawat, 'tgl_periksa' => $tgl_periksa, 'jam' => $jam];
        }

        // Lacak aktivitas (jika fungsi track_sql ada di config.php)
        if (function_exists('track_sql')) {
            track_sql($action, 'saran_kesan_lab', $data);
        }

        $message = 'Kesan dan saran berhasil disimpan.';
        $message_type = 'success';

    } catch (PDOException $e) {
        $message = 'Gagal menyimpan data: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Ambil data pasien dan detail pemeriksaan
try {
    $sql_pasien = "SELECT
                      rp.no_rkm_medis,
                      p.nm_pasien,
                      p.tgl_lahir,
                      p.jk,
                      p.alamat,
                      rp.tgl_registrasi,
                      d_perujuk.nm_dokter AS dokter_perujuk,
                      dp_pj.nm_dokter AS dokter_penanggung_jawab,
                      pj.png_jawab
                    FROM periksa_lab pl
                    JOIN reg_periksa rp ON pl.no_rawat = rp.no_rawat
                    JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                    LEFT JOIN dokter d_perujuk ON pl.dokter_perujuk = d_perujuk.kd_dokter
                    LEFT JOIN dokter dp_pj ON pl.kd_dokter = dp_pj.kd_dokter
                    JOIN penjab pj ON rp.kd_pj = pj.kd_pj
                    WHERE pl.no_rawat = ? AND pl.tgl_periksa = ? AND pl.jam = ?
                    GROUP BY pl.no_rawat";

    $stmt_pasien = $pdo->prepare($sql_pasien);
    $stmt_pasien->execute([$no_rawat, $tgl_periksa, $jam]);
    $pasien = $stmt_pasien->fetch(PDO::FETCH_ASSOC);

    if (!$pasien) {
        throw new Exception("Data pasien atau pemeriksaan tidak ditemukan.");
    }

    // Hitung umur
    $tgl_lahir = new DateTime($pasien['tgl_lahir']);
    $tgl_reg = new DateTime($pasien['tgl_registrasi']);
    $umur = $tgl_reg->diff($tgl_lahir);
    $umur_pasien = "{$umur->y} Th, {$umur->m} Bl, {$umur->d} Hr";
    $jenis_kelamin = ($pasien['jk'] == 'L') ? 'Laki-laki' : 'Perempuan';

    // Ambil detail hasil lab
    $sql_hasil = "SELECT
                    jpl.kd_jenis_prw,
                    jpl.nm_perawatan,
                    tpl.Pemeriksaan,
                    dpl.nilai,
                    tpl.satuan,
                    dpl.nilai_rujukan,
                    dpl.keterangan,
                    dpl.kd_jenis_prw AS detail_kd_jenis_prw
                  FROM detail_periksa_lab dpl
                  JOIN jns_perawatan_lab jpl ON dpl.kd_jenis_prw = jpl.kd_jenis_prw
                  LEFT JOIN template_laboratorium tpl ON dpl.id_template = tpl.id_template
                  WHERE dpl.no_rawat = ? AND dpl.tgl_periksa = ? AND dpl.jam = ?
                  ORDER BY jpl.kd_jenis_prw, tpl.urut";

    $stmt_hasil = $pdo->prepare($sql_hasil);
    $stmt_hasil->execute([$no_rawat, $tgl_periksa, $jam]);
    $hasil_lab = $stmt_hasil->fetchAll(PDO::FETCH_ASSOC);

    // Kelompokkan hasil berdasarkan jenis perawatan (header)
    $grouped_hasil = [];
    foreach ($hasil_lab as $hasil) {
        $kd_jenis_prw = $hasil['kd_jenis_prw'];
        if (!isset($grouped_hasil[$kd_jenis_prw])) {
            $grouped_hasil[$kd_jenis_prw] = [
                'nama_perawatan' => $hasil['nm_perawatan'],
                'detail' => []
            ];
        }
        $grouped_hasil[$kd_jenis_prw]['detail'][] = $hasil;
    }

    // --- AWAL MODIFIKASI ---
    // (BARU) Ambil diagnosa klinis & info tambahan dari permintaan
    $sql_permintaan = "SELECT informasi_tambahan, diagnosa_klinis
                       FROM permintaan_lab
                       WHERE no_rawat = ? AND tgl_hasil = ? AND jam_hasil = ?
                       LIMIT 1"; // Asumsi 1 permintaan per timestamp hasil
    $stmt_permintaan = $pdo->prepare($sql_permintaan);
    $stmt_permintaan->execute([$no_rawat, $tgl_periksa, $jam]);
    $info_permintaan = $stmt_permintaan->fetch(PDO::FETCH_ASSOC);
    // --- AKHIR MODIFIKASI ---
    
    // Ambil kesan & saran yang tersimpan
    $stmt_kesan = $pdo->prepare("SELECT kesan, saran FROM saran_kesan_lab WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?");
    $stmt_kesan->execute([$no_rawat, $tgl_periksa, $jam]);
    $kesan_saran = $stmt_kesan->fetch(PDO::FETCH_ASSOC);
    $kesan_tersimpan = $kesan_saran['kesan'] ?? '';
    $saran_tersimpan = $kesan_saran['saran'] ?? '';

} catch (Exception $e) {
    // Jika data tidak ditemukan atau error, redirect dengan pesan
    $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    header("Location: laboratorium.php");
    exit;
}

$csrf_token = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hasil Laboratorium | <?php echo e($_SESSION['settings']['nama_instansi']); ?></title>
  <?php if (!empty($_SESSION['settings']['logo_base64'])): ?>
  <link rel="icon" type="image/png" href="data:image/png;base64,<?php echo $_SESSION['settings']['logo_base64']; ?>">
  <?php endif; ?>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <style>
    .hasil-lab-header { font-size: 1.1em; font-weight: bold; background-color: #f4f4f4; }
    .table-sm td, .table-sm th { padding: .4rem; }
    .nilai-abnormal { color: red; font-weight: bold; }
    .nilai-normal { color: #0056b3; } /* Biru untuk nilai normal agar tetap terlihat jelas */
  </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
  
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="fas fa-user-md"></i> <?php echo e($_SESSION['user_name'] ?? 'Dokter'); ?>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <a href="?action=logout" class="dropdown-item">
            <i class="fas fa-sign-out-alt mr-2"></i> Logout
          </a>
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
          <li class="nav-item">
            <a href="laboratorium.php" class="nav-link active">
              <i class="nav-icon fas fa-flask"></i><p>Pemeriksaan Lab</p>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </aside>

  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Hasil Laboratorium</h1>
          </div>
          <div class="col-sm-6">
            <a href="laboratorium.php" class="btn btn-secondary btn-sm float-right"><i class="fas fa-arrow-left"></i> Kembali</a>
            <!-- TOMBOL RIWAYAT PASIEN (BARU) -->
            <button type="button" class="btn btn-info btn-sm float-right mr-2" id="btnRiwayatPasien"
                    data-norm="<?php echo e($pasien['no_rkm_medis']); ?>"
                    data-norawat="<?php echo e($no_rawat); ?>">
                <i class="fas fa-history"></i> Riwayat Pasien
            </button>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
          <?php echo e($message); ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <?php endif; ?>

        <!-- Informasi Pasien -->
        <div class="card card-primary card-outline">
          <div class="card-header"><h3 class="card-title">Informasi Pasien & Pemeriksaan</h3></div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <table class="table table-sm table-borderless">
                  <tr><th style="width: 30%;">No. Rawat</th><td>: <?php echo e($no_rawat); ?></td></tr>
                  <tr><th>No. RM</th><td>: <?php echo e($pasien['no_rkm_medis']); ?></td></tr>
                  <tr><th>Nama Pasien</th><td>: <?php echo e($pasien['nm_pasien']); ?></td></tr>
                  <tr><th>Tgl. Lahir / Umur</th><td>: <?php echo e($pasien['tgl_lahir']); ?> (<?php echo e($umur_pasien); ?>)</td></tr>
                  <tr><th>Jenis Kelamin</th><td>: <?php echo e($jenis_kelamin); ?></td></tr>
                </table>
              </div>
              <div class="col-md-6">
                <table class="table table-sm table-borderless">
                  <tr><th style="width: 30%;">Alamat</th><td>: <?php echo e($pasien['alamat']); ?></td></tr>
                  <tr><th>Cara Bayar</th><td>: <?php echo e($pasien['png_jawab']); ?></td></tr>
                  <tr><th>Tgl. Periksa</th><td>: <?php echo e($tgl_periksa); ?> <?php echo e($jam); ?></td></tr>
                  <tr><th>Dokter Perujuk</th><td>: <?php echo e($pasien['dokter_perujuk']); ?></td></tr>
                  <tr><th>Dokter PJ Lab</th><td>: <?php echo e($pasien['dokter_penanggung_jawab']); ?></td></tr>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- (BARU) Informasi Klinis dari Permintaan -->
        <?php if ($info_permintaan && (!empty($info_permintaan['diagnosa_klinis']) || !empty($info_permintaan['informasi_tambahan']))): ?>
        <div class="card card-info card-outline">
          <div class="card-header"><h3 class="card-title">Informasi Klinis (Dari Permintaan)</h3></div>
          <div class="card-body py-2" style="font-size: 0.9em;">
            <?php if (!empty($info_permintaan['diagnosa_klinis'])): ?>
                <strong>Diagnosa Klinis:</strong>
                <p class="mb-1"><?php echo nl2br(e($info_permintaan['diagnosa_klinis'])); ?></p>
            <?php endif; ?>
            <?php if (!empty($info_permintaan['informasi_tambahan'])): ?>
                <strong class="mt-2">Info Tambahan:</strong>
                <p class="mb-0"><?php echo nl2br(e($info_permintaan['informasi_tambahan'])); ?></p>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
        <!-- (AKHIR BARU) -->

        <!-- Hasil Pemeriksaan -->
        <div class="card card-success card-outline">
          <div class="card-header"><h3 class="card-title">Detail Hasil</h3></div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm table-hover">
                <thead class="thead-light">
                  <tr>
                    <th>Pemeriksaan</th>
                    <th>Hasil</th>
                    <th>Satuan</th>
                    <th>Nilai Rujukan</th>
                    <th>Keterangan</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($grouped_hasil)): ?>
                    <tr><td colspan="5" class="text-center">Tidak ada detail hasil yang ditemukan.</td></tr>
                  <?php else: ?>
                    <?php foreach ($grouped_hasil as $group): ?>
                      <tr class="hasil-lab-header">
                        <td colspan="5"><?php echo e($group['nama_perawatan']); ?></td>
                      </tr>
                      <?php foreach ($group['detail'] as $detail): ?>
                        <?php
                          // Cek nilai abnormal (simplifikasi: jika 'keterangan' mengandung 'L' atau 'H' atau '*')
                          $is_abnormal = (
                              strpos($detail['keterangan'], 'L') !== false ||
                              strpos($detail['keterangan'], 'H') !== false ||
                              strpos($detail['keterangan'], '*') !== false
                          );
                          $nilai_class = $is_abnormal ? 'nilai-abnormal' : 'nilai-normal';
                        ?>
                        <tr>
                          <td style="padding-left: 20px;"><?php echo e($detail['Pemeriksaan']); ?></td>
                          <td class="<?php echo $nilai_class; ?>"><?php echo e($detail['nilai']); ?></td>
                          <td><?php echo e($detail['satuan']); ?></td>
                          <td><?php echo e($detail['nilai_rujukan']); ?></td>
                          <td><?php echo e($detail['keterangan']); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        
        <!-- Kesan dan Saran -->
        <div class="card card-info card-outline">
          <div class="card-header"><h3 class="card-title">Kesan & Saran Klinis</h3></div>
          <div class="card-body">
            <form action="" method="POST">
              <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
              <div class="form-group">
                <label for="kesan">Kesan</label>
                <textarea name="kesan" id="kesan" class="form-control" rows="5"><?php echo e($kesan_tersimpan); ?></textarea>
              </div>
              <div class="form-group">
                <label for="saran">Saran</label>
                <textarea name="saran" id="saran" class="form-control" rows="5"><?php echo e($saran_tersimpan); ?></textarea>
              </div>
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Kesan & Saran</button>
            </form>
          </div>
        </div>

      </div>
    </section>
  </div>

  <!-- MODAL UNTUK RIWAYAT PASIEN (BARU) -->
  <div class="modal fade" id="modalRiwayat" tabindex="-1" role="dialog" aria-labelledby="modalRiwayatLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalRiwayatLabel">Riwayat Medis Pasien: <?php echo e($pasien['nm_pasien']); ?> (<?php echo e($pasien['no_rkm_medis']); ?>)</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body" id="contentRiwayat" style="max-height: 75vh; overflow-y: auto;">
          <!-- Content loaded via AJAX -->
          <div class="text-center">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Memuat data riwayat...</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>
  </div>

  <footer class="main-footer"><strong>&copy; <?php echo date("Y"); ?> IT <?php echo e($_SESSION['settings']['nama_instansi']); ?>.</strong></footer>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
$(document).ready(function() {
    
    // JS UNTUK RIWAYAT PASIEN (BARU)
    $('#btnRiwayatPasien').on('click', function() {
        var no_rkm_medis = $(this).data('norm');
        var no_rawat = $(this).data('norawat');
        var modalBody = $('#contentRiwayat');
        var csrf_token = '<?php echo $csrf_token; ?>'; // Ambil CSRF token dari PHP

        // Tampilkan modal
        $('#modalRiwayat').modal('show');
        
        // Set loading state
        modalBody.html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Memuat data riwayat...</p></div>');

        // Panggil AJAX ke file baru
        $.ajax({
            url: 'get_riwayat_pasien.php',
            type: 'POST',
            data: {
                no_rkm_medis: no_rkm_medis,
                no_rawat_current: no_rawat,
                csrf_token: csrf_token // Kirim CSRF token
            },
            dataType: 'html',
            success: function(response) {
                // Tampilkan hasil di modal body
                modalBody.html(response);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Tampilkan pesan error
                var errorMsg = '<div class="alert alert-danger">Gagal memuat riwayat pasien. ';
                if (jqXHR.status == 403) {
                    errorMsg += 'Kesalahan keamanan (CSRF Token tidak valid).';
                } else if (jqXHR.status == 405) {
                    errorMsg += 'Metode request tidak diizinkan.';
                } else {
                    errorMsg += 'Error: ' + textStatus + ' - ' + errorThrown;
                }
                errorMsg += '</div>';
                modalBody.html(errorMsg);
            }
        });
    });

    // Menghilangkan alert setelah beberapa detik
    setTimeout(function() {
        $('.alert-dismissible').fadeOut('slow');
    }, 3000);
});
</script>
</body>
</html>

