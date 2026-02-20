<?php
// File: modules/edokter/index.php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../helpers/auth_helper.php';

cekLogin();
if (!cekAkses('soap_perawatan')) { die("Akses Ditolak."); }

$kd_dokter = $_SESSION['user_id'];
$tgl_hari_ini = date('Y-m-d');

try {
    // 1. Hitung Pasien Ralan Hari Ini
    $q_ralan = $pdo->prepare("SELECT COUNT(*) FROM reg_periksa WHERE kd_dokter=? AND tgl_registrasi=? AND status_lanjut='Ralan'");
    $q_ralan->execute([$kd_dokter, $tgl_hari_ini]);
    $jml_ralan = $q_ralan->fetchColumn();

    // 2. Hitung Pasien Ranap Aktif
    $q_ranap = $pdo->prepare("SELECT COUNT(ki.no_rawat) FROM kamar_inap ki LEFT JOIN dpjp_ranap dr ON ki.no_rawat = dr.no_rawat WHERE ki.stts_pulang = '-' AND (dr.kd_dokter = ? OR dr.kd_dokter IS NULL)");
    $q_ranap->execute([$kd_dokter]);
    $jml_ranap = $q_ranap->fetchColumn();

    // 3. KALKULASI AKURAT JASMED HARI INI (Sesuai Logika Query Lama)
    $total_jasmed = 0;
    
    // Fungsi pembantu untuk mengeksekusi SUM dengan aman
    function getSumJasmed($pdo, $sql, $params) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    // A. Ralan (Dr & Dr+Pr)
    $total_jasmed += getSumJasmed($pdo, "SELECT IFNULL(SUM(tarif_tindakandr),0) FROM rawat_jl_dr WHERE tgl_perawatan=? AND kd_dokter=?", [$tgl_hari_ini, $kd_dokter]);
    $total_jasmed += getSumJasmed($pdo, "SELECT IFNULL(SUM(tarif_tindakandr),0) FROM rawat_jl_drpr WHERE tgl_perawatan=? AND kd_dokter=?", [$tgl_hari_ini, $kd_dokter]);

    // B. Ranap (Dr & Dr+Pr)
    $total_jasmed += getSumJasmed($pdo, "SELECT IFNULL(SUM(tarif_tindakandr),0) FROM rawat_inap_dr WHERE tgl_perawatan=? AND kd_dokter=?", [$tgl_hari_ini, $kd_dokter]);
    $total_jasmed += getSumJasmed($pdo, "SELECT IFNULL(SUM(tarif_tindakandr),0) FROM rawat_inap_drpr WHERE tgl_perawatan=? AND kd_dokter=?", [$tgl_hari_ini, $kd_dokter]);

    // C. Operasi (Semua Peran)
    $total_jasmed += getSumJasmed($pdo, "SELECT IFNULL(SUM(biayaoperator1),0) FROM operasi WHERE DATE(tgl_operasi)=? AND operator1=?", [$tgl_hari_ini, $kd_dokter]);
    $total_jasmed += getSumJasmed($pdo, "SELECT IFNULL(SUM(biayaoperator2),0) FROM operasi WHERE DATE(tgl_operasi)=? AND operator2=?", [$tgl_hari_ini, $kd_dokter]);
    $total_jasmed += getSumJasmed($pdo, "SELECT IFNULL(SUM(biayaoperator3),0) FROM operasi WHERE DATE(tgl_operasi)=? AND operator3=?", [$tgl_hari_ini, $kd_dokter]);
    $total_jasmed += getSumJasmed($pdo, "SELECT IFNULL(SUM(biayadokter_anak),0) FROM operasi WHERE DATE(tgl_operasi)=? AND dokter_anak=?", [$tgl_hari_ini, $kd_dokter]);
    $total_jasmed += getSumJasmed($pdo, "SELECT IFNULL(SUM(biayadokter_anestesi),0) FROM operasi WHERE DATE(tgl_operasi)=? AND dokter_anestesi=?", [$tgl_hari_ini, $kd_dokter]);
    $total_jasmed += getSumJasmed($pdo, "SELECT IFNULL(SUM(biaya_dokter_umum),0) FROM operasi WHERE DATE(tgl_operasi)=? AND dokter_umum=?", [$tgl_hari_ini, $kd_dokter]);
    $total_jasmed += getSumJasmed($pdo, "SELECT IFNULL(SUM(biaya_dokter_pjanak),0) FROM operasi WHERE DATE(tgl_operasi)=? AND dokter_pjanak=?", [$tgl_hari_ini, $kd_dokter]);

    // D. Radiologi (Tindakan & Perujuk)
    $total_jasmed += getSumJasmed($pdo, "SELECT IFNULL(SUM(tarif_tindakan_dokter),0) FROM periksa_radiologi WHERE tgl_periksa=? AND kd_dokter=?", [$tgl_hari_ini, $kd_dokter]);
    $total_jasmed += getSumJasmed($pdo, "SELECT IFNULL(SUM(tarif_perujuk),0) FROM periksa_radiologi WHERE tgl_periksa=? AND dokter_perujuk=?", [$tgl_hari_ini, $kd_dokter]);

    // E. Laboratorium (Tindakan/Detail & Perujuk)
    $total_jasmed += getSumJasmed($pdo, "SELECT IFNULL(SUM(d.bagian_dokter),0) FROM periksa_lab t JOIN detail_periksa_lab d ON t.no_rawat=d.no_rawat AND t.kd_jenis_prw=d.kd_jenis_prw AND t.tgl_periksa=d.tgl_periksa AND t.jam=d.jam WHERE t.tgl_periksa=? AND t.kd_dokter=?", [$tgl_hari_ini, $kd_dokter]);
    $total_jasmed += getSumJasmed($pdo, "SELECT IFNULL(SUM(tarif_perujuk),0) FROM periksa_lab WHERE tgl_periksa=? AND dokter_perujuk=?", [$tgl_hari_ini, $kd_dokter]);

} catch (PDOException $e) {
    die("Error Kalkulasi: " . $e->getMessage());
}

require_once '../../layout/header.php';
require_once '../../layout/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="h5 mb-0 text-gray-800"><i class="fas fa-stethoscope text-primary me-2"></i> Dashboard E-Dokter</h4>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-left-primary h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Antrean Poliklinik (Hari Ini)</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?= $jml_ralan ?> Pasien</div>
                            <a href="<?= $base_url ?>modules/edokter/ralan/index.php" class="btn btn-primary btn-sm mt-3"><i class="fas fa-arrow-right"></i> Buka Ralan</a>
                        </div>
                        <div class="col-auto"><i class="fas fa-user-md fa-3x text-gray-300 opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-left-success h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Pasien Bangsal (Dirawat)</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?= $jml_ranap ?> Pasien</div>
                            <a href="<?= $base_url ?>modules/edokter/ranap/index.php" class="btn btn-success btn-sm mt-3"><i class="fas fa-arrow-right"></i> Buka Ranap</a>
                        </div>
                        <div class="col-auto"><i class="fas fa-procedures fa-3x text-gray-300 opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-left-warning h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Jasmed Hari Ini (Estimasi)</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($total_jasmed, 0, ',', '.') ?></div>
                            <a href="<?= $base_url ?>modules/edokter/jasmed/ringkasan_shift.php" class="btn btn-warning btn-sm mt-3 fw-bold text-dark"><i class="fas fa-wallet"></i> Detail Jasmed</a>
                        </div>
                        <div class="col-auto"><i class="fas fa-coins fa-3x text-gray-300 opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../layout/footer.php'; ?>