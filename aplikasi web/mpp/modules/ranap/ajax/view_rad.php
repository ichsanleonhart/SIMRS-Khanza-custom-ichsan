<?php
// File: modules/ranap/ajax/view_rad.php
// Deskripsi: View Hasil Radiologi (Clean Version)

$base_path = dirname(dirname(dirname(__DIR__)));

// 1. Load Config (Sekarang $webapps_url pasti http://192.168.1.5/webapps/)
if (file_exists($base_path . '/config/config.php')) {
    require_once $base_path . '/config/config.php';
} else {
    $webapps_url = "http://192.168.1.5/webapps/";
}

require_once $base_path . '/config/database.php';

ini_set('display_errors', 0); 
error_reporting(E_ALL);

try {
    $no_rawat = isset($_POST['no_rawat']) ? $_POST['no_rawat'] : '';
    if (empty($no_rawat)) throw new Exception("No Rawat tidak dikirim.");

    // Query Data
    $sql = "SELECT pr.tgl_periksa, pr.jam, hr.hasil, d.nm_dokter, jp.nm_perawatan as jenis_periksa
            FROM periksa_radiologi pr
            LEFT JOIN hasil_radiologi hr ON pr.no_rawat = hr.no_rawat AND pr.tgl_periksa = hr.tgl_periksa AND pr.jam = hr.jam
            LEFT JOIN dokter d ON pr.kd_dokter = d.kd_dokter
            LEFT JOIN jns_perawatan_radiologi jp ON pr.kd_jenis_prw = jp.kd_jenis_prw
            WHERE pr.no_rawat = ?
            ORDER BY pr.tgl_periksa DESC, pr.jam DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$no_rawat]);
    $radiologi_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    function tgl_indo($tanggal){
        $bulan = array (1 => 'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des');
        $pecahkan = explode('-', $tanggal);
        return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
    }

} catch (Exception $e) {
    die('<div class="alert alert-danger">Error Sistem: ' . $e->getMessage() . '</div>');
}
?>

<div class="container-fluid p-3">
    <div class="alert alert-light border small text-muted py-1 mb-3">
        <i class="fas fa-link me-1"></i> Sumber Gambar: <strong><?= $webapps_url ?></strong>
    </div>

    <?php if(empty($radiologi_data)): ?>
        <div class="alert alert-info text-center">Tidak ada hasil radiologi.</div>
    <?php else: ?>
        <?php foreach($radiologi_data as $rad): ?>
            <?php 
                try {
                    $sql_img = "SELECT lokasi_gambar FROM gambar_radiologi WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?";
                    $stmt_img = $pdo->prepare($sql_img);
                    $stmt_img->execute([$no_rawat, $rad['tgl_periksa'], $rad['jam']]);
                    $images = $stmt_img->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) { $images = []; }
            ?>

            <div class="card mb-4 border shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-x-ray me-2"></i> <?= $rad['jenis_periksa'] ?></h6>
                        <small class="text-muted"><?= tgl_indo($rad['tgl_periksa']) ?> <?= $rad['jam'] ?> | Dokter: <?= $rad['nm_dokter'] ?></small>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-7 border-end">
                            <h6 class="text-uppercase small text-muted fw-bold mb-2">Expertise:</h6>
                            <div class="bg-white p-3 border rounded" style="white-space: pre-wrap; font-family: 'Consolas', monospace; font-size: 0.9rem; background: #fdfdfd; min-height: 150px;"><?= $rad['hasil'] ? $rad['hasil'] : '<span class="text-muted fst-italic">Belum ada bacaan dokter (Expertise).</span>' ?></div>
                        </div>
                        <div class="col-lg-5">
                            <h6 class="text-uppercase small text-muted fw-bold mb-2">Citra Radiologi:</h6>
                            <?php if(empty($images)): ?>
                                <div class="text-center text-muted py-5 border rounded bg-light small">
                                    <i class="fas fa-image fa-2x mb-2 text-secondary"></i><br>Tidak ada gambar digital.
                                </div>
                            <?php else: ?>
                                <div class="row g-2">
                                    <?php foreach($images as $img): ?>
                                        <?php 
                                            // FIX PATH: 
                                            // Jika di DB isinya "pages/upload/foto.jpg", kita harus pastikan "radiologi/" ada.
                                            // Asumsi: folder radiologi ada di dalam root webapps (http://192.168.1.5/webapps/radiologi/)
                                            
                                            $lokasi_db = $img['lokasi_gambar'];
                                            
                                            // Bersihkan path agar tidak double
                                            $clean_path = str_replace(['pages/upload/', 'radiologi/'], '', $lokasi_db);
                                            
                                            // Susun URL final standar Khanza
                                            $full_url = $webapps_url . "radiologi/pages/upload/" . $clean_path;
                                        ?>
                                        <div class="col-6">
                                            <div class="border rounded p-1 text-center bg-light">
                                                <a href="<?= $full_url ?>" target="_blank">
                                                    <img src="<?= $full_url ?>" class="img-fluid rounded hover-zoom" 
                                                         style="height: 120px; width: 100%; object-fit: cover;" 
                                                         alt="Rontgen"
                                                         onerror="this.onerror=null; this.src='https://via.placeholder.com/150?text=Gagal+Load'; this.parentElement.href='javascript:void(0)';">
                                                </a>
                                                <small class="d-block mt-1 text-muted text-truncate" style="font-size: 0.65rem;">
                                                    <?= basename($clean_path) ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
    .hover-zoom { transition: transform 0.2s; cursor: pointer; }
    .hover-zoom:hover { transform: scale(1.05); }
</style>