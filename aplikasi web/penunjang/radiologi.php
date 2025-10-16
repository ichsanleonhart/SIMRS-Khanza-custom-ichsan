<?php
/*
 * ===================================================================================
 * HALAMAN FORM EXPERTISE RADIOLOGI
 * ===================================================================================
 * Halaman ini digunakan untuk mengisi atau mengubah hasil expertise
 * untuk satu pemeriksaan pasien.
 */
require_once 'config.php';
require_login();

$no_rawat = $_GET['no_rawat'] ?? null;
$tgl_periksa = $_GET['tgl_periksa'] ?? null;
$jam = $_GET['jam'] ?? null;

// Jika parameter tidak lengkap, kembalikan ke halaman utama
if (!$no_rawat || !$tgl_periksa || !$jam) {
    header("Location: data_radiologi.php");
    exit;
}

$pdo = connect_db();

// Ambil semua data awal yang diperlukan untuk ditampilkan di form
$data_pasien = [];
$data_pemeriksaan = [];
$hasil_radiologi = '';
$dokter_pj_list = [];
$radiology_images = [];

try {
    $sql_pasien = "SELECT p.no_rkm_medis, p.nm_pasien, p.tgl_lahir, p.jk, pr.no_rawat, pr.tgl_periksa, pr.jam, d_perujuk.nm_dokter AS dokter_perujuk, d_pj.kd_dokter AS kd_dokter_pj, d_pj.nm_dokter AS dokter_pj FROM periksa_radiologi pr JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis LEFT JOIN dokter d_perujuk ON pr.dokter_perujuk = d_perujuk.kd_dokter LEFT JOIN dokter d_pj ON pr.kd_dokter = d_pj.kd_dokter WHERE pr.no_rawat = :no_rawat AND pr.tgl_periksa = :tgl_periksa AND pr.jam = :jam GROUP BY pr.no_rawat, pr.tgl_periksa, pr.jam";
    $stmt_pasien = $pdo->prepare($sql_pasien);
    $stmt_pasien->execute([':no_rawat' => $no_rawat, ':tgl_periksa' => $tgl_periksa, ':jam' => $jam]);
    $data_pasien = $stmt_pasien->fetch();

    if ($data_pasien) {
        $sql_pemeriksaan = "SELECT jpr.nm_perawatan FROM periksa_radiologi pr JOIN jns_perawatan_radiologi jpr ON pr.kd_jenis_prw = jpr.kd_jenis_prw WHERE pr.no_rawat = :no_rawat AND pr.tgl_periksa = :tgl_periksa AND pr.jam = :jam";
        $stmt_pemeriksaan = $pdo->prepare($sql_pemeriksaan);
        $stmt_pemeriksaan->execute([':no_rawat' => $no_rawat, ':tgl_periksa' => $tgl_periksa, ':jam' => $jam]);
        $data_pemeriksaan = $stmt_pemeriksaan->fetchAll(PDO::FETCH_COLUMN, 0);

        $stmt_hasil = $pdo->prepare("SELECT hasil FROM hasil_radiologi WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?");
        $stmt_hasil->execute([$no_rawat, $tgl_periksa, $jam]);
        $hasil_radiologi = $stmt_hasil->fetchColumn();
        
        $stmt_dokter = $pdo->query("SELECT kd_dokter, nm_dokter FROM dokter WHERE kd_sps = 'RAD' ORDER BY nm_dokter");
        $dokter_pj_list = $stmt_dokter->fetchAll();

        $sql_images = "SELECT lokasi_gambar FROM gambar_radiologi WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?";
        $stmt_images = $pdo->prepare($sql_images);
        $stmt_images->execute([$no_rawat, $tgl_periksa, $jam]);
        $radiology_images = $stmt_images->fetchAll(PDO::FETCH_COLUMN, 0);
    }

} catch (\PDOException $e) {
    error_log("Fetch Gagal: " . $e->getMessage());
    $message = "Gagal mengambil data pasien.";
    $message_type = 'danger';
}

if (!$data_pasien) {
    $_SESSION['error_message'] = "Data pemeriksaan untuk no_rawat " . e($no_rawat) . " tidak ditemukan.";
    header('Location: data_radiologi.php');
    exit;
}

// Ambil pesan notifikasi dari session jika ada
$message = '';
$message_type = '';
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message']['message'];
    $message_type = $_SESSION['flash_message']['type'];
    unset($_SESSION['flash_message']);
}

// Proses Simpan/Update Hasil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Error: Aksi tidak diizinkan (invalid CSRF token).');
	}
    unset($_SESSION['csrf_token']);
	
    $hasil_periksa = $_POST['hasil_periksa'] ?? '';
    $kd_dokter_baru = $_POST['kd_dokter_pj'] ?? null;

    try {
        $pdo->beginTransaction();
        
        // Cek apakah hasil expertise sudah ada sebelumnya
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM hasil_radiologi WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?");
        $stmt_check->execute([$no_rawat, $tgl_periksa, $jam]);
        $hasil_exists = $stmt_check->fetchColumn() > 0;

        if (empty(trim($hasil_periksa))) {
            if ($hasil_exists) {
                // Jika hasil dikosongkan, hapus record
                $stmt_delete = $pdo->prepare("DELETE FROM hasil_radiologi WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?");
                $stmt_delete->execute([$no_rawat, $tgl_periksa, $jam]);
                track_sql('HAPUS HASIL EXPERTISE', 'hasil_radiologi', ['no_rawat' => $no_rawat, 'tgl_periksa' => $tgl_periksa, 'jam' => $jam]);
            }
        } else {
            if ($hasil_exists) {
                // Jika sudah ada, update
                $stmt_update = $pdo->prepare("UPDATE hasil_radiologi SET hasil = ? WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?");
                $stmt_update->execute([$hasil_periksa, $no_rawat, $tgl_periksa, $jam]);
                track_sql('EDIT HASIL EXPERTISE', 'hasil_radiologi', ['no_rawat' => $no_rawat, 'tgl_periksa' => $tgl_periksa, 'jam' => $jam]);
            } else {
                // Jika belum ada, insert
                $stmt_insert = $pdo->prepare("INSERT INTO hasil_radiologi (no_rawat, tgl_periksa, jam, hasil) VALUES (?, ?, ?, ?)");
                $stmt_insert->execute([$no_rawat, $tgl_periksa, $jam, $hasil_periksa]);
                track_sql('SIMPAN HASIL EXPERTISE', 'hasil_radiologi', ['no_rawat' => $no_rawat, 'tgl_periksa' => $tgl_periksa, 'jam' => $jam]);
            }
        }
        
        // Update dokter penanggung jawab jika ada perubahan
        if ($kd_dokter_baru && $kd_dokter_baru !== $data_pasien['kd_dokter_pj']) {
             $stmt_update_dokter = $pdo->prepare("UPDATE periksa_radiologi SET kd_dokter = ? WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?");
             $stmt_update_dokter->execute([$kd_dokter_baru, $no_rawat, $tgl_periksa, $jam]);
             track_sql('UBAH DOKTER PJ', 'periksa_radiologi', ['no_rawat' => $no_rawat, 'tgl_periksa' => $tgl_periksa, 'jam' => $jam, 'kd_dokter_lama' => $data_pasien['kd_dokter_pj'], 'kd_dokter_baru' => $kd_dokter_baru]);
        }

        $pdo->commit();
        $_SESSION['flash_message'] = ['message' => 'Hasil expertise berhasil disimpan!', 'type' => 'success'];
    } catch (\PDOException $e) {
        $pdo->rollBack();
        error_log("Update Gagal: " . $e->getMessage());
        $_SESSION['flash_message'] = ['message' => 'Terjadi kesalahan saat menyimpan data.', 'type' => 'danger'];
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Buat token CSRF baru untuk form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Input Expertise | <?php echo e($_SESSION['settings']['nama_instansi']); ?></title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <style>
      .info-box-text, .info-box-number { word-wrap: break-word; }
      .radiology-gallery img {
          cursor: pointer;
          transition: transform 0.2s;
      }
      .radiology-gallery img:hover {
          transform: scale(1.05);
      }
  </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-user"></i> <?php echo e($_SESSION['user_name']); ?>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <a href="index.php?action=logout" class="dropdown-item">
            <i class="fas fa-sign-out-alt mr-2"></i> Logout
          </a>
        </div>
      </li>
    </ul>
  </nav>
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="data_radiologi.php" class="brand-link">
       <?php if (!empty($_SESSION['settings']['logo_base64'])): ?>
          <img src="data:image/png;base64,<?php echo $_SESSION['settings']['logo_base64']; ?>" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
       <?php endif; ?>
      <span class="brand-text font-weight-light">Expertise Radiologi</span>
    </a>

    <div class="sidebar">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item">
            <a href="data_radiologi.php" class="nav-link active">
              <i class="nav-icon fas fa-list"></i>
              <p>Daftar Pasien</p>
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
            <h1>Input Hasil Expertise</h1>
          </div>
          <div class="col-sm-6">
            <a href="data_radiologi.php" class="btn btn-secondary float-sm-right"><i class="fas fa-arrow-left"></i> Kembali ke Daftar</a>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo e($message_type); ?> alert-dismissible">
              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
              <h5><i class="icon fas fa-check"></i> Info</h5>
              <?php echo e($message); ?>
            </div>
        <?php endif; ?>
        <div class="row">
          <div class="col-md-4">
              <div class="card card-primary card-outline">
                  <div class="card-body box-profile">
                      <h3 class="profile-username text-center"><?php echo e($data_pasien['nm_pasien']); ?></h3>
                      <p class="text-muted text-center"><?php echo e($data_pasien['no_rkm_medis']); ?></p>
                      <ul class="list-group list-group-unbordered mb-3">
                          <li class="list-group-item">
                              <b>No. Rawat</b> <a class="float-right"><?php echo e($data_pasien['no_rawat']); ?></a>
                          </li>
                          <li class="list-group-item">
                              <b>Tgl Lahir / JK</b> <a class="float-right"><?php echo e($data_pasien['tgl_lahir']); ?> / <?php echo e($data_pasien['jk']); ?></a>
                          </li>
                          <li class="list-group-item">
                              <b>Tgl Periksa</b> <a class="float-right"><?php echo e($data_pasien['tgl_periksa']); ?> <?php echo e($data_pasien['jam']); ?></a>
                          </li>
                           <li class="list-group-item">
                              <b>Dokter Perujuk</b> <a class="float-right"><?php echo e($data_pasien['dokter_perujuk'] ?? '-'); ?></a>
                          </li>
                      </ul>
                  </div>
              </div>
              <div class="card card-primary">
                  <div class="card-header">
                      <h3 class="card-title">Jenis Pemeriksaan</h3>
                  </div>
                  <div class="card-body">
                      <strong><i class="fas fa-x-ray mr-1"></i> Pemeriksaan</strong>
                      <p class="text-muted"><?php echo e(implode(', ', $data_pemeriksaan)); ?></p>
                  </div>
              </div>
          </div>
          <div class="col-md-8">
            <form action="" method="post">
              <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
              <div class="card card-primary">
                  <div class="card-header">
                      <h3 class="card-title">Hasil Expertise</h3>
                      <div class="card-tools">
                          <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#templateModal">
                              <i class="fas fa-book"></i> Pilih dari Template
                          </button>
                      </div>
                  </div>
                  <div class="card-body">
                      <div class="form-group">
                          <label for="kd_dokter_pj">Dokter Penanggung Jawab</label>
                          <select class="form-control" id="kd_dokter_pj" name="kd_dokter_pj">
                              <?php foreach ($dokter_pj_list as $dokter): ?>
                              <option value="<?php echo e($dokter['kd_dokter']); ?>" <?php echo ($data_pasien['kd_dokter_pj'] == $dokter['kd_dokter']) ? 'selected' : ''; ?>>
                                  <?php echo e($dokter['nm_dokter']); ?>
                              </option>
                              <?php endforeach; ?>
                          </select>
                      </div>
                      <div class="form-group">
                          <label for="hasil_periksa">Hasil Expertise (Bacaan)</label>
                          <textarea class="form-control" id="hasil_periksa" name="hasil_periksa" rows="18" placeholder="Ketik hasil expertise di sini..."><?php echo e($hasil_radiologi); ?></textarea>
                      </div>
                  </div>
                  <div class="card-footer">
                      <button type="submit" class="btn btn-success btn-lg float-right"><i class="fas fa-save"></i> Simpan Hasil</button>
                  </div>
              </div>
            </form>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Galeri Gambar Radiologi</h3>
              </div>
              <div class="card-body">
                <?php if (!empty($radiology_images)): ?>
                <div class="row radiology-gallery" id="radiologyGallery">
                    <?php foreach ($radiology_images as $image_file): ?>
                    <div class="col-sm-3">
                        <img src="<?php echo e(WEBAPPS_URL . '/radiologi/' . $image_file); ?>" class="img-fluid mb-2" alt="Gambar Radiologi - <?php echo e($data_pasien['nm_pasien']); ?>"/>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="alert alert-info text-center">Tidak ada gambar radiologi ditemukan untuk pemeriksaan ini.</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        
      </div>
    </section>
  </div>
  <div class="modal fade" id="templateModal" tabindex="-1" role="dialog" aria-labelledby="templateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="templateModalLabel">Pilih Template Hasil Expertise</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" id="templateSearch" class="form-control mb-3" placeholder="Cari nama pemeriksaan...">
                <div id="templateList" class="list-group" style="max-height: 400px; overflow-y: auto;">
                    <p class="text-center">Memuat template...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
  </div>


  <footer class="main-footer">
    <div class="float-right d-none d-sm-block">
      <b>Version</b> 1.3.0
    </div>
    <strong>&copy; <?php echo date("Y"); ?> IT <?php echo e($_SESSION['settings']['nama_instansi']); ?>.</strong>
  </footer>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
  $(function () {
    const gallery = document.getElementById('radiologyGallery');
    if (gallery) {
        const viewer = new Viewer(gallery, {
            inline: false, 
            toolbar: {
                zoomIn: 4,
                zoomOut: 4,
                oneToOne: 4,
                reset: 4,
                prev: 4,
                play: {
                    show: 4,
                    size: 'large',
                },
                next: 4,
                rotateLeft: 4,
                rotateRight: 4,
                flipHorizontal: 4,
                flipVertical: 4,
            },
            title: (image) => {
                return image.alt; 
            },
            url: 'src', 
        });
    }

    $('#templateModal').on('show.bs.modal', function () {
        var templateList = $('#templateList');
        templateList.html('<p class="text-center">Memuat template...</p>');

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

