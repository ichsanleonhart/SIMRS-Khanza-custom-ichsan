<?php
/*
 * ===================================================================================
 * HALAMAN FORM EXPERTISE RADIOLOGI
 * ===================================================================================
 * Halaman ini digunakan untuk mengisi atau mengubah hasil expertise
 * untuk satu pemeriksaan pasien.
 *
 * MODIFIKASI:
 * - Penambahan tombol "Riwayat Pasien"
 * - Penambahan modal untuk menampilkan riwayat (via AJAX)
 * - Penambahan event listener jQuery untuk tombol riwayat
 * - Penambahan query untuk mengambil diagnosa/info klinis dari permintaan_radiologi
 * - Penambahan card untuk menampilkan diagnosa/info klinis
 * - (BARU) Integrasi viewer.js untuk galeri gambar utama
 */
require_once 'config.php';
require_login();

// Pastikan hanya dokter radiologi yang bisa akses
if ($_SESSION['user_role'] !== 'radiologi') {
    header('Location: index.php');
    exit;
}

$no_rawat = $_GET['no_rawat'] ?? null;
$tgl_periksa = $_GET['tgl_periksa'] ?? null;
$jam = $_GET['jam'] ?? null;

// Jika parameter tidak lengkap, kembalikan ke halaman utama
if (!$no_rawat || !$tgl_periksa || !$jam) {
    header("Location: data_radiologi.php");
    exit;
}

$pdo = connect_db();
$message = '';
$message_type = '';
$csrf_token = csrf_token(); // Generate CSRF token

// Proses Simpan/Update Hasil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die('Aksi tidak diizinkan: Invalid CSRF Token.');
    }

    $hasil_periksa = $_POST['hasil_periksa'] ?? '';
    // (BARU) Ambil kd_dokter_pj dari POST
    $kd_dokter_baru = $_POST['kd_dokter_pj'] ?? null;

    try {
        $pdo->beginTransaction(); // Mulai transaksi

        // Cek apakah hasil sudah ada
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM hasil_radiologi WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?");
        $stmt_check->execute([$no_rawat, $tgl_periksa, $jam]);
        $exists = $stmt_check->fetchColumn();

        if ($exists) {
            // Update Hasil
            $sql_update = "UPDATE hasil_radiologi SET hasil = ? WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$hasil_periksa, $no_rawat, $tgl_periksa, $jam]);
            $action = 'Update';
            $data = ['hasil' => $hasil_periksa, 'no_rawat' => $no_rawat, 'tgl_periksa' => $tgl_periksa, 'jam' => $jam];
        } else {
            // Insert Hasil
            $sql_insert = "INSERT INTO hasil_radiologi (no_rawat, tgl_periksa, jam, hasil) VALUES (?, ?, ?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([$no_rawat, $tgl_periksa, $jam, $hasil_periksa]);
            $action = 'Insert';
            $data = ['no_rawat' => $no_rawat, 'tgl_periksa' => $tgl_periksa, 'jam' => $jam];
        }

        // (BARU) Update dokter penanggung jawab di periksa_radiologi
        // Hanya update jika dokter baru dipilih DAN berbeda dari dokter sebelumnya
        // Ambil dokter PJ sebelumnya
        $stmt_get_old_dokter = $pdo->prepare("SELECT kd_dokter FROM periksa_radiologi WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ? LIMIT 1");
        $stmt_get_old_dokter->execute([$no_rawat, $tgl_periksa, $jam]);
        $kd_dokter_lama = $stmt_get_old_dokter->fetchColumn();

        if ($kd_dokter_baru && $kd_dokter_baru !== $kd_dokter_lama) {
             $stmt_update_dokter = $pdo->prepare("UPDATE periksa_radiologi SET kd_dokter = ? WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?");
             $stmt_update_dokter->execute([$kd_dokter_baru, $no_rawat, $tgl_periksa, $jam]);
             // Lacak perubahan dokter PJ jika perlu
             if (function_exists('track_sql')) {
                 track_sql('UBAH DOKTER PJ RADIOLOGI', 'periksa_radiologi', [
                    'no_rawat' => $no_rawat,
                    'tgl_periksa' => $tgl_periksa,
                    'jam' => $jam,
                    'kd_dokter_lama' => $kd_dokter_lama,
                    'kd_dokter_baru' => $kd_dokter_baru
                 ]);
             }
        }


        // Lacak aktivitas simpan/update hasil
        if (function_exists('track_sql')) {
            track_sql($action . ' HASIL RADIOLOGI', 'hasil_radiologi', $data);
        }

        $pdo->commit(); // Commit transaksi jika semua berhasil
        $message = 'Hasil expertise radiologi berhasil disimpan.';
        $message_type = 'success';

        // (BARU) Redirect untuk mencegah resubmit form dan refresh data
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $message_type;
        header("Location: radiologi.php?no_rawat=$no_rawat&tgl_periksa=$tgl_periksa&jam=$jam");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack(); // Rollback jika ada error
        $message = 'Gagal menyimpan data: ' . $e->getMessage();
        $message_type = 'danger';
        error_log("Gagal simpan expertise radiologi: " . $e->getMessage()); // Log error
    }
}

// Ambil pesan flash setelah redirect (jika ada)
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'] ?? 'info';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}


// Ambil semua data awal yang diperlukan untuk ditampilkan di form
$data_pasien = [];
$data_pemeriksaan = [];
$hasil_radiologi = '';
$radiology_images = [];
$info_permintaan = null;
$dokter_pj_list = []; // (BARU) Daftar dokter radiologi

try {
    // 1. Ambil data pasien dan pemeriksaan
    $sql_pasien = "SELECT
                        p.no_rkm_medis, p.nm_pasien, p.tgl_lahir, p.jk, p.alamat, rp.tgl_registrasi,
                        pr.no_rawat, pr.tgl_periksa, pr.jam,
                        d_perujuk.nm_dokter AS dokter_perujuk,
                        d_pj.kd_dokter AS kd_dokter_pj,
                        d_pj.nm_dokter AS dokter_pj,
                        pj.png_jawab
                      FROM periksa_radiologi pr
                      JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
                      JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                      LEFT JOIN dokter d_perujuk ON pr.dokter_perujuk = d_perujuk.kd_dokter
                      LEFT JOIN dokter d_pj ON pr.kd_dokter = d_pj.kd_dokter
                      JOIN penjab pj ON rp.kd_pj = pj.kd_pj
                      WHERE pr.no_rawat = ? AND pr.tgl_periksa = ? AND pr.jam = ?
                      GROUP BY pr.no_rawat, pr.tgl_periksa, pr.jam";

    $stmt_pasien = $pdo->prepare($sql_pasien);
    $stmt_pasien->execute([$no_rawat, $tgl_periksa, $jam]);
    $data_pasien = $stmt_pasien->fetch(PDO::FETCH_ASSOC);

    if (!$data_pasien) {
        throw new Exception("Data pasien atau pemeriksaan radiologi tidak ditemukan.");
    }

    // 2. Ambil daftar tindakan pemeriksaan radiologi
    $sql_pemeriksaan = "SELECT
                            jpr.nm_perawatan, pr.proyeksi, pr.kV, pr.mAS, pr.FFD,
                            pr.BSF, pr.inak, pr.jml_penyinaran, pr.dosis
                          FROM periksa_radiologi pr
                          JOIN jns_perawatan_radiologi jpr ON pr.kd_jenis_prw = jpr.kd_jenis_prw
                          WHERE pr.no_rawat = ? AND pr.tgl_periksa = ? AND pr.jam = ?";
    $stmt_pemeriksaan = $pdo->prepare($sql_pemeriksaan);
    $stmt_pemeriksaan->execute([$no_rawat, $tgl_periksa, $jam]);
    $data_pemeriksaan = $stmt_pemeriksaan->fetchAll(PDO::FETCH_ASSOC);

    // 3. Ambil hasil expertise yang tersimpan (jika ada)
    $stmt_hasil = $pdo->prepare("SELECT hasil FROM hasil_radiologi WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?");
    $stmt_hasil->execute([$no_rawat, $tgl_periksa, $jam]);
    $hasil = $stmt_hasil->fetch(PDO::FETCH_ASSOC);
    $hasil_radiologi = $hasil['hasil'] ?? '';

    // 4. Ambil gambar radiologi
    $stmt_gambar = $pdo->prepare("SELECT lokasi_gambar FROM gambar_radiologi WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?");
    $stmt_gambar->execute([$no_rawat, $tgl_periksa, $jam]);
    $radiology_images = $stmt_gambar->fetchAll(PDO::FETCH_ASSOC);

    // 5. Ambil diagnosa klinis & info tambahan dari permintaan
    $sql_permintaan = "SELECT informasi_tambahan, diagnosa_klinis
                       FROM permintaan_radiologi
                       WHERE no_rawat = ? AND tgl_hasil = ? AND jam_hasil = ?
                       LIMIT 1";
    $stmt_permintaan = $pdo->prepare($sql_permintaan);
    $stmt_permintaan->execute([$no_rawat, $tgl_periksa, $jam]);
    $info_permintaan = $stmt_permintaan->fetch(PDO::FETCH_ASSOC);

    // (BARU) 6. Ambil daftar dokter radiologi untuk dropdown
    $stmt_dokter_rad = $pdo->query("SELECT kd_dokter, nm_dokter FROM dokter WHERE kd_sps = 'RAD' ORDER BY nm_dokter");
    $dokter_pj_list = $stmt_dokter_rad->fetchAll(PDO::FETCH_ASSOC);


    // Hitung umur
    $tgl_lahir = new DateTime($data_pasien['tgl_lahir']);
    $tgl_reg = new DateTime($data_pasien['tgl_registrasi']);
    $umur = $tgl_reg->diff($tgl_lahir);
    $umur_pasien = "{$umur->y} Th, {$umur->m} Bl, {$umur->d} Hr";
    $jenis_kelamin = ($data_pasien['jk'] == 'L') ? 'Laki-laki' : 'Perempuan';

} catch (Exception $e) {
    $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    header("Location: data_radiologi.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id"> <!-- Ganti lang ke "id" -->
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Expertise Radiologi | <?php echo e($_SESSION['settings']['nama_instansi']); ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- (BARU) CSS Viewer.js -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <style>
    /* (MODIFIKASI) Style untuk galeri viewer.js */
    .radiology-gallery {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 15px;
    }
    .radiology-gallery .image-item {
      flex: 1 1 200px; /* Lebar minimum, bisa membesar */
      min-width: 150px;
      background: #000;
      padding: 5px;
      border-radius: 4px;
      overflow: hidden; /* Mencegah gambar keluar batas */
    }
    .radiology-gallery .image-item img {
      width: 100%;
      height: auto;
      display: block;
      cursor: pointer; /* Menunjukkan bisa diklik */
      transition: transform 0.2s; /* Efek hover kecil */
    }
     .radiology-gallery .image-item img:hover {
        transform: scale(1.03);
     }
    .list-group-item { cursor: pointer; }
    .list-group-item:hover { background-color: #f8f9fa; }
	
	
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
       <?php if (!empty($_SESSION['settings']['logo_base64'])): ?>
          <img src="data:image/png;base64,<?php echo $_SESSION['settings']['logo_base64']; ?>" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
       <?php endif; ?>
	  <span class="brand-text font-weight-light"><?php echo e($_SESSION['settings']['nama_instansi'] ?? 'Radiologi'); ?></span> <br>
      <span style="display:block; text-align:center;" class="brand-text font-weight-light">Expertise App</span>
    </a>
    <div class="sidebar">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item">
            <a href="data_radiologi.php" class="nav-link active">
              <i class="nav-icon fas fa-x-ray"></i><p>Expertise Radiologi</p>
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
            <h1>Expertise Radiologi</h1>
          </div>
          <div class="col-sm-6">
            <a href="data_radiologi.php" class="btn btn-secondary btn-sm float-right"><i class="fas fa-arrow-left"></i> Kembali</a>
            <button type="button" class="btn btn-info btn-sm float-right mr-2" id="btnRiwayatPasien"
                    data-norm="<?php echo e($data_pasien['no_rkm_medis']); ?>"
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
                  <tr><th>No. RM</th><td>: <?php echo e($data_pasien['no_rkm_medis']); ?></td></tr>
                  <tr><th>Nama Pasien</th><td>: <?php echo e($data_pasien['nm_pasien']); ?></td></tr>
                  <tr><th>Tgl. Lahir / Umur</th><td>: <?php echo e($data_pasien['tgl_lahir']); ?> (<?php echo e($umur_pasien); ?>)</td></tr>
                  <tr><th>Jenis Kelamin</th><td>: <?php echo e($jenis_kelamin); ?></td></tr>
                </table>
              </div>
              <div class="col-md-6">
                <table class="table table-sm table-borderless">
                  <tr><th style="width: 30%;">Alamat</th><td>: <?php echo e($data_pasien['alamat']); ?></td></tr>
                  <tr><th>Cara Bayar</th><td>: <?php echo e($data_pasien['png_jawab']); ?></td></tr>
                  <tr><th>Tgl. Periksa</th><td>: <?php echo e($tgl_periksa); ?> <?php echo e($jam); ?></td></tr>
                  <tr><th>Dokter Perujuk</th><td>: <?php echo e($data_pasien['dokter_perujuk'] ?? '-'); ?></td></tr>
                  <tr><th>Dokter PJ Rad</th><td>: <?php echo e($data_pasien['dokter_pj'] ?? '-'); ?></td></tr>
                </table>
              </div>
            </div>
            <hr>
            <h5>Pemeriksaan Dilakukan:</h5>
            <ul>
              <?php foreach($data_pemeriksaan as $pemeriksaan): ?>
                 <li>
                    <?php echo e($pemeriksaan['nm_perawatan']); ?>
                    <?php
                        // (BARU) Tampilkan detail penyinaran jika ada
                        $details = [];
                        if (!empty($pemeriksaan['proyeksi'])) $details[] = 'Proyeksi: ' . e($pemeriksaan['proyeksi']);
                        if (!empty($pemeriksaan['kV'])) $details[] = 'kV: ' . e($pemeriksaan['kV']);
                        if (!empty($pemeriksaan['mAS'])) $details[] = 'mAS: ' . e($pemeriksaan['mAS']);
                        if (!empty($pemeriksaan['FFD'])) $details[] = 'FFD: ' . e($pemeriksaan['FFD']);
                        if (!empty($pemeriksaan['BSF'])) $details[] = 'BSF: ' . e($pemeriksaan['BSF']);
                        if (!empty($pemeriksaan['inak'])) $details[] = 'Inak: ' . e($pemeriksaan['inak']);
                        if (!empty($pemeriksaan['jml_penyinaran'])) $details[] = 'Jml: ' . e($pemeriksaan['jml_penyinaran']);
                        if (!empty($pemeriksaan['dosis'])) $details[] = 'Dosis: ' . e($pemeriksaan['dosis']);
                        if (!empty($details)) {
                            echo ' <small class="text-muted">(' . implode(', ', $details) . ')</small>';
                        }
                    ?>
                 </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>

        <!-- Informasi Klinis dari Permintaan -->
        <?php if ($info_permintaan && (!empty($info_permintaan['diagnosa_klinis']) || !empty($info_permintaan['informasi_tambahan']))): ?>
        <div class="card card-info card-outline">
          <div class="card-header"><h3 class="card-title">Informasi Klinis (dari Permintaan)</h3></div>
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

        <!-- Form Expertise dan Galeri -->
        <div class="card card-success card-outline">
          <div class="card-header"><h3 class="card-title">Hasil Pemeriksaan Radiologi</h3></div>
          <div class="card-body">
            <form action="" method="POST">
              <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

              <!-- (BARU) Dropdown Dokter PJ -->
               <div class="form-group row">
                    <label for="kd_dokter_pj" class="col-sm-3 col-form-label">Dokter P. Jawab</label>
                    <div class="col-sm-9">
                        <select class="form-control form-control-sm" id="kd_dokter_pj" name="kd_dokter_pj">
                             <option value="">- Pilih Dokter PJ -</option>
                             <?php foreach ($dokter_pj_list as $dokter): ?>
                             <option value="<?php echo e($dokter['kd_dokter']); ?>" <?php echo ($data_pasien['kd_dokter_pj'] == $dokter['kd_dokter']) ? 'selected' : ''; ?>>
                                 <?php echo e($dokter['nm_dokter']); ?>
                             </option>
                             <?php endforeach; ?>
                        </select>
                    </div>
                </div>


              <div class="form-group">
                <label for="hasil_periksa">Hasil Expertise (Bacaan)</label>
                <button type="button" class="btn btn-default btn-xs ml-2" data-toggle="modal" data-target="#templateModal">
                  <i class="fas fa-book"></i> Gunakan Template
                </button>
                <textarea name="hasil_periksa" id="hasil_periksa" class="form-control" rows="15"><?php echo e($hasil_radiologi); ?></textarea>
              </div>
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Hasil</button>
            </form>

            <?php if (!empty($radiology_images)): ?>
              <h5 class="mt-4">Gambar Radiologi (Klik untuk melihat)</h5>
              <!-- (MODIFIKASI) Beri ID dan class untuk viewer.js -->
              <div id="radiologyGallery" class="radiology-gallery">
                <?php foreach ($radiology_images as $image): ?>
                  <?php $imageUrl = e(WEBAPPS_URL . '/radiologi/' . $image['lokasi_gambar']); ?>
                  <div class="image-item">
                    <!-- (MODIFIKASI) Hapus tag <a>, sisakan <img> -->
                    <img src="<?php echo $imageUrl; ?>" alt="Gambar Radiologi - <?php echo e($data_pasien['nm_pasien']); ?>"
                         onerror="this.src='https://placehold.co/200x200/000000/FFFFFF?text=Error+Load'; this.style.cursor='default';">
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </section>
  </div>

  <!-- Modal Template -->
  <div class="modal fade" id="templateModal" tabindex="-1" role="dialog" aria-labelledby="templateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="templateModalLabel">Pilih Template Hasil Radiologi</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="text" id="templateSearch" class="form-control mb-2" placeholder="Cari nama pemeriksaan...">
          <div class="list-group" id="templateList" style="max-height: 50vh; overflow-y: auto;">
            <p class="text-center">Memuat template...</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Riwayat Pasien -->
  <div class="modal fade" id="modalRiwayat" tabindex="-1" role="dialog" aria-labelledby="modalRiwayatLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalRiwayatLabel">Riwayat Medis Pasien: <?php echo e($data_pasien['nm_pasien']); ?> (<?php echo e($data_pasien['no_rkm_medis']); ?>)</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body" id="contentRiwayat" style="max-height: 75vh; overflow-y: auto;">
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
<!-- (BARU) JS Viewer.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
  $(document).ready(function() {

    // (BARU) Inisialisasi Viewer.js untuk galeri utama
    const gallery = document.getElementById('radiologyGallery');
    if (gallery) {
      const viewer = new Viewer(gallery, {
          inline: false, // Tampilkan sebagai modal, bukan inline
          toolbar: { // Konfigurasi toolbar (sesuaikan dari kode lama Anda)
            zoomIn: 4,
            zoomOut: 4,
            oneToOne: 4,
            reset: 4,
            prev: 4,
            play: { show: 4, size: 'large' },
            next: 4,
            rotateLeft: 4,
            rotateRight: 4,
            flipHorizontal: 4,
            flipVertical: 4,
          },
          title: (image) => { // Fungsi untuk menampilkan title dari alt gambar
            return image.alt;
          },
          url: 'src', // Sumber URL gambar adalah atribut 'src' dari <img>
        });
    }

    // JS UNTUK RIWAYAT PASIEN
    $('#btnRiwayatPasien').on('click', function() {
      var no_rkm_medis = $(this).data('norm');
      var no_rawat = $(this).data('norawat');
      var modalBody = $('#contentRiwayat');
      var csrf_token = '<?php echo $csrf_token; ?>';

      $('#modalRiwayat').modal('show');
      modalBody.html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Memuat data riwayat...</p></div>');

      $.ajax({
        url: 'get_riwayat_pasien_radiologi.php',
        type: 'POST',
        data: {
          no_rkm_medis: no_rkm_medis,
          no_rawat_current: no_rawat,
          csrf_token: csrf_token
        },
        dataType: 'html',
        success: function(response) {
          modalBody.html(response);
          // Inisialisasi viewer.js mungkin perlu dipanggil lagi di sini jika ada di response AJAX
          // Kode inisialisasi viewer.js SUDAH ada di get_riwayat_pasien_radiologi.php
        },
        error: function(jqXHR, textStatus, errorThrown) {
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

    // JS untuk Modal Template
    $('#templateModal').on('show.bs.modal', function () {
      var templateList = $('#templateList');
      templateList.html('<p class="text-center">Memuat template...</p>');
      $('#templateSearch').val(''); // Kosongkan search box

      $.ajax({
        url: 'api_get_templates.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
          templateList.empty();
          if (response.success && response.data.length > 0) {
            $.each(response.data, function(i, template) {
              var listItem = $('<a href="#" class="list-group-item list-group-item-action"></a>');
              listItem.text(template.nama_pemeriksaan);
              listItem.data('template-content', template.template_hasil_radiologi);
              templateList.append(listItem);
            });
          } else {
            templateList.html('<p class="text-center text-danger">Gagal memuat template atau tidak ada template tersedia.</p>');
          }
        },
        error: function() {
          templateList.html('<p class="text-center text-danger">Terjadi kesalahan saat mengambil data template dari server.</p>');
        }
      });
    });

    $('#templateList').on('click', '.list-group-item', function(e) {
      e.preventDefault();
      var templateContent = $(this).data('template-content');
      $('#hasil_periksa').val(templateContent);
      $('#templateModal').modal('hide');
    });

    $('#templateSearch').on('keyup', function() {
      var value = $(this).val().toLowerCase();
      $("#templateList a").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
      });
    });

  });
</script>
</body>
</html>

