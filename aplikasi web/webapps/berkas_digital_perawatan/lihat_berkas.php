<?php
/*
 * File: /webapps/berkas_digital_perawatan/lihat_berkas.php
 * Fungsi: Menampilkan detail berkas pasien dan trigger merge
 */
session_start();

// 1. KEAMANAN
if (!isset($_SESSION['casemix_login']) || $_SESSION['casemix_login'] !== true) {
    header("Location: index.php");
    exit;
}

require_once('../conf/conf.php');
$koneksi = bukakoneksi();

$no_rawat = isset($_GET['no_rawat']) ? validTeks4($_GET['no_rawat'], 20) : '';
$base_url_berkas = "../berkasrawat/"; 

// AMBIL NAMA INSTANSI
$nama_instansi = "RS Khanza";
$q_set = mysqli_query($koneksi, "SELECT nama_instansi FROM setting LIMIT 1");
if($r_set = mysqli_fetch_assoc($q_set)) $nama_instansi = $r_set['nama_instansi'];

// QUERY DATA PASIEN
$query_pasien = "SELECT 
        p.nm_pasien, p.no_rkm_medis, p.alamat, 
        rp.no_rawat, rp.tgl_registrasi, rp.status_lanjut, 
        d.nm_dokter, poli.nm_poli,
        COALESCE(bs.no_sep, '-') as no_sep,
        COALESCE(bs.no_kartu, '-') as no_kartu_bpjs,
        COALESCE(pen.nm_penyakit, '-') as diagnosa_utama,
        COALESCE(pen.kd_penyakit, '-') as kd_diagnosa
    FROM reg_periksa rp
    JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    JOIN dokter d ON rp.kd_dokter = d.kd_dokter
    JOIN poliklinik poli ON rp.kd_poli = poli.kd_poli
    LEFT JOIN bridging_sep bs ON rp.no_rawat = bs.no_rawat
    LEFT JOIN diagnosa_pasien dp ON rp.no_rawat = dp.no_rawat AND dp.prioritas = 1
    LEFT JOIN penyakit pen ON dp.kd_penyakit = pen.kd_penyakit
    WHERE rp.no_rawat = '$no_rawat' LIMIT 1";

$hasil_pasien = mysqli_query($koneksi, $query_pasien);
$data_pasien  = mysqli_fetch_assoc($hasil_pasien);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berkas: <?= $data_pasien['nm_pasien'] ?? '-' ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .card-pasien { border-top: 4px solid #0d6efd; }
        .file-icon { width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 1.5rem; }
        .bg-pdf { background-color: #ffe5e7; color: #dc3545; }
        .bg-img { background-color: #e0f2fe; color: #0ea5e9; }
        .list-group-item:hover { background-color: #e9ecef; }
    </style>
</head>
<body>

<nav class="navbar navbar-light bg-white shadow-sm mb-4 sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="#">
            <img src="logo.php" height="30" class="me-2" alt="Logo"> Berkas Digital
        </a>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm fw-bold">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
    </div>
</nav>

<div class="container pb-5">
    <?php if(!$data_pasien): ?>
        <div class="alert alert-warning text-center shadow-sm p-5">
            <h4>Data Rawat Tidak Ditemukan</h4>
            <a href="dashboard.php" class="btn btn-primary mt-2">Kembali</a>
        </div>
    <?php else: ?>

        <div class="row g-4 mb-4">
            <div class="col-lg-12">
                <div class="card card-pasien shadow-sm">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 border-end">
                                <h4 class="fw-bold mb-0"><?= $data_pasien['nm_pasien'] ?></h4>
                                <div class="text-muted small mb-2">RM: <?= $data_pasien['no_rkm_medis'] ?></div>
                                <span class="badge bg-<?= $data_pasien['status_lanjut'] == 'Ralan' ? 'success' : 'warning text-dark' ?>"><?= $data_pasien['status_lanjut'] ?></span>
                            </div>
                            <div class="col-md-4 border-end">
                                <table class="table table-sm table-borderless mb-0 small">
                                    <tr><td class="text-muted" width="30%">No. Rawat</td><td><b><?= $data_pasien['no_rawat'] ?></b></td></tr>
                                    <tr><td class="text-muted">Tanggal</td><td><?= $data_pasien['tgl_registrasi'] ?></td></tr>
                                    <tr><td class="text-muted">Dokter</td><td><?= $data_pasien['nm_dokter'] ?></td></tr>
                                    <tr><td class="text-muted">Poli</td><td><?= $data_pasien['nm_poli'] ?></td></tr>
                                </table>
                            </div>
                            <div class="col-md-4">
                                <div class="small fw-bold text-muted mb-1">JAMINAN & DIAGNOSA</div>
                                <div class="mb-1">SEP: <span class="fw-bold text-primary"><?= $data_pasien['no_sep'] ?></span></div>
                                <div class="bg-light p-2 rounded border small">
                                    <?= $data_pasien['kd_diagnosa'] ?> - <?= $data_pasien['diagnosa_utama'] ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-folder-open text-warning me-2"></i> Daftar Dokumen</h6>
                <button onclick="konfirmasiGabung()" class="btn btn-danger btn-sm fw-bold px-4 shadow-sm">
                    <i class="fas fa-file-pdf me-1"></i> PROSES & DOWNLOAD PDF
                </button>
            </div>
            
            <div class="list-group list-group-flush">
                <?php
                $query_berkas = "SELECT bdp.lokasi_file, mbd.nama as jenis_berkas
                                 FROM berkas_digital_perawatan bdp
                                 JOIN master_berkas_digital mbd ON bdp.kode = mbd.kode
                                 WHERE bdp.no_rawat = '$no_rawat' ORDER BY bdp.kode ASC";
                $hasil_berkas = mysqli_query($koneksi, $query_berkas);
                
                if(mysqli_num_rows($hasil_berkas) > 0):
                    while($file = mysqli_fetch_assoc($hasil_berkas)):
                        $ext = strtolower(pathinfo($file['lokasi_file'], PATHINFO_EXTENSION));
                        $is_img = in_array($ext, ['jpg', 'jpeg', 'png']);
                        $icon_cls = $is_img ? 'bg-img text-primary' : 'bg-pdf text-danger';
                        $fa_icon = $is_img ? 'fa-image' : 'fa-file-pdf';
                        $link = $base_url_berkas . $file['lokasi_file'];
                ?>
                    <div class="list-group-item p-3 d-flex align-items-center">
                        <div class="file-icon <?= $icon_cls ?> me-3"><i class="fas <?= $fa_icon ?>"></i></div>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-dark"><?= $file['jenis_berkas'] ?></div>
                            <small class="text-muted"><?= $file['lokasi_file'] ?></small>
                        </div>
                        <a href="<?= $link ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> Buka</a>
                    </div>
                <?php 
                    endwhile; 
                else: 
                ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-folder-open fa-3x mb-3 opacity-25"></i>
                        <p>Belum ada berkas digital diupload.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function konfirmasiGabung() {
    const adaBerkas = document.querySelectorAll('.list-group-item').length > 0;
    if(!adaBerkas) {
        Swal.fire('Info', 'Tidak ada berkas untuk digabungkan.', 'info');
        return;
    }

    Swal.fire({
        title: 'Sedang Memproses...',
        html: 'Menstandarisasi dokumen & menggabungkan PDF.<br>Mohon tunggu sejenak.',
        icon: 'info',
        showConfirmButton: false,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
            window.location.href = 'merge.php?no_rawat=<?= urlencode($no_rawat) ?>';
        }
    });
    
    // Tutup loading setelah 10 detik (estimasi download mulai)
    setTimeout(() => { Swal.close(); }, 10000);
}
</script>

</body>
</html>
<?php mysqli_close($koneksi); ?>