<?php
/*
 * File: lihat_berkas.php (V.Final - Command Center)
 */
session_start();
if (!isset($_SESSION['casemix_login'])) { header("Location: index.php"); exit; }

require_once('../conf/conf.php');
$koneksi = bukakoneksi();

$no_rawat = isset($_GET['no_rawat']) ? validTeks4($_GET['no_rawat'], 20) : '';
$base_url_berkas = "../berkasrawat/"; 

// Data Pasien (Sama)
$query_pasien = "SELECT p.nm_pasien, p.no_rkm_medis, rp.no_rawat, rp.tgl_registrasi, rp.status_lanjut, d.nm_dokter, poli.nm_poli,
    COALESCE(bs.no_sep, '-') as no_sep, COALESCE(pen.nm_penyakit, '-') as diagnosa_utama, COALESCE(pen.kd_penyakit, '-') as kd_diagnosa
    FROM reg_periksa rp
    JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    JOIN dokter d ON rp.kd_dokter = d.kd_dokter
    JOIN poliklinik poli ON rp.kd_poli = poli.kd_poli
    LEFT JOIN bridging_sep bs ON rp.no_rawat = bs.no_rawat
    LEFT JOIN diagnosa_pasien dp ON rp.no_rawat = dp.no_rawat AND dp.prioritas = 1
    LEFT JOIN penyakit pen ON dp.kd_penyakit = pen.kd_penyakit
    WHERE rp.no_rawat = '$no_rawat' LIMIT 1";

$data_pasien  = mysqli_fetch_assoc(mysqli_query($koneksi, $query_pasien));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berkas: <?= $data_pasien['nm_pasien'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-size: 0.9rem; }
        .file-icon { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 5px; font-size: 1.2rem; }
        .bg-pdf { background-color: #ffe5e7; color: #dc3545; }
        .bg-img { background-color: #e0f2fe; color: #0ea5e9; }
        .generator-card { border-left: 4px solid #198754; }
    </style>
</head>
<body>

<nav class="navbar navbar-light bg-white shadow-sm mb-4 sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold text-primary" href="#">Berkas Digital</a>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
    </div>
</nav>

<div class="container-fluid px-4">
    <?php if(!$data_pasien): ?>
        <div class="alert alert-warning">Data tidak ditemukan.</div>
    <?php else: ?>
        
    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body py-2 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0"><?= $data_pasien['nm_pasien'] ?> <small class="text-muted fs-6">(<?= $data_pasien['no_rkm_medis'] ?>)</small></h5>
                        <small class="text-muted"><?= $data_pasien['no_rawat'] ?> | <?= $data_pasien['tgl_registrasi'] ?> | <?= $data_pasien['nm_dokter'] ?></small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-primary">SEP: <?= $data_pasien['no_sep'] ?></span>
                        <div class="small fw-bold mt-1 text-muted"><?= $data_pasien['kd_diagnosa'] ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <form id="formMerge" action="merge.php" method="POST">
                <input type="hidden" name="no_rawat" value="<?= $no_rawat ?>">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="checkAll" checked>
                            <label class="form-check-label fw-bold" for="checkAll">Pilih Semua Dokumen</label>
                        </div>
                        <button type="button" onclick="konfirmasiGabung()" class="btn btn-danger btn-sm fw-bold">
                            <i class="fas fa-file-pdf me-1"></i> GABUNG PDF
                        </button>
                    </div>
                    <div class="list-group list-group-flush" id="listBerkas">
                        <?php
                        $q_berkas = "SELECT bdp.lokasi_file, mbd.nama, bdp.kode 
                                     FROM berkas_digital_perawatan bdp 
                                     JOIN master_berkas_digital mbd ON bdp.kode = mbd.kode 
                                     WHERE bdp.no_rawat = '$no_rawat' ORDER BY bdp.kode ASC";
                        $r_berkas = mysqli_query($koneksi, $q_berkas);
                        
                        if(mysqli_num_rows($r_berkas) > 0):
                            while($f = mysqli_fetch_assoc($r_berkas)):
                                $ext = strtolower(pathinfo($f['lokasi_file'], PATHINFO_EXTENSION));
                                $icon = in_array($ext, ['jpg','png']) ? 'bg-img fa-image' : 'bg-pdf fa-file-pdf';
                        ?>
                            <label class="list-group-item d-flex align-items-center p-2">
                                <input class="form-check-input item-chk me-3" type="checkbox" name="selected_files[]" value="<?= $f['kode'] ?>" checked>
                                <div class="file-icon <?= strpos($icon,'bg-img')!==false?'bg-img':'bg-pdf' ?> me-3"><i class="fas <?= explode(' ',$icon)[1] ?>"></i></div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold"><?= $f['nama'] ?></div>
                                    <small class="text-muted"><?= $f['lokasi_file'] ?></small>
                                </div>
                                <a href="<?= $base_url_berkas.$f['lokasi_file'] ?>" target="_blank" class="btn btn-sm btn-light border"><i class="fas fa-eye"></i></a>
                            </label>
                        <?php endwhile; else: ?>
                            <div class="text-center py-5 text-muted">Belum ada dokumen. Silahkan generate di panel kanan.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm generator-card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-magic me-2"></i>Generator Dokumen</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Jika dokumen belum tersedia di list kiri, silahkan buat baru di sini. Dokumen akan otomatis di-upload.</p>
                    
                    <div class="d-grid gap-2">
                        <a href="erm/cetak_resume.php?no_rawat=<?= urlencode($no_rawat) ?>" target="_blank" class="btn btn-outline-success text-start">
                            <i class="fas fa-file-medical me-2"></i> Resume Medis Ranap
                        </a>

                        <button disabled class="btn btn-outline-secondary text-start">
                            <i class="fas fa-file-invoice-dollar me-2"></i> Rincian Biaya (Billing)
                        </button>
                        
                        <button disabled class="btn btn-outline-secondary text-start">
                            <i class="fas fa-flask me-2"></i> Hasil Laboratorium
                        </button>

                        <hr>
                        <button onclick="alert('Gunakan SIMRS Desktop untuk upload manual scan PDF/JPG')" class="btn btn-light text-muted btn-sm">
                            <i class="fas fa-upload me-1"></i> Upload Manual Lainnya
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('checkAll').addEventListener('change', function() {
        document.querySelectorAll('.item-chk').forEach(c => c.checked = this.checked);
    });
    function konfirmasiGabung() {
        if(document.querySelectorAll('.item-chk:checked').length === 0) {
            Swal.fire('Warning', 'Pilih file dulu.', 'warning'); return;
        }
        Swal.fire({ title: 'Processing...', didOpen: () => { Swal.showLoading(); document.getElementById('formMerge').submit(); } });
        setTimeout(() => Swal.close(), 5000);
    }
</script>
</body>
</html>
<?php mysqli_close($koneksi); ?>