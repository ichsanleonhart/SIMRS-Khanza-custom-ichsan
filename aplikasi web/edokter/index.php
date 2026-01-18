<?php
// index.php
session_start();
require_once 'config/database.php';
require_once 'config/fungsi.php';

if (!isset($_SESSION['login_user'])) {
    header("Location: modul/auth/login.php");
    exit();
}

$title = 'Executive Dashboard';
$menu  = 'dashboard';

// FIX BUG DISINI: Pakai null coalescing (??) agar tidak error Undefined Index
$kd_dokter = $_SESSION['kd_dokter'] ?? ''; 

// 1. Data Waktu
$shift_info = get_current_shift();
$tgl_hari_ini = date('Y-m-d');
$jam_sekarang = date('H');

// Greeting
$salam = "Selamat Pagi";
if ($jam_sekarang >= 10) $salam = "Selamat Siang";
if ($jam_sekarang >= 15) $salam = "Selamat Sore";
if ($jam_sekarang >= 19) $salam = "Selamat Malam";

// 2. Query Dashboard
$total_shift = 0;
$total_hari  = 0;
$jumlah_pasien = 0;

// HANYA JALANKAN QUERY JIKA YANG LOGIN ADALAH DOKTER (PUNYA KD_DOKTER)
if (!empty($kd_dokter)) {
    try {
        $sql_shift = "
        SELECT SUM(jm_dokter) as total FROM (
            SELECT t.tarif_tindakandr as jm_dokter FROM rawat_jl_dr t WHERE CONCAT(t.tgl_perawatan,' ',t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter = :dokter
            UNION ALL SELECT t.tarif_tindakandr FROM rawat_jl_drpr t WHERE CONCAT(t.tgl_perawatan,' ',t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter = :dokter
            UNION ALL SELECT t.tarif_tindakandr FROM rawat_inap_dr t WHERE CONCAT(t.tgl_perawatan,' ',t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter = :dokter
            UNION ALL SELECT t.tarif_tindakandr FROM rawat_inap_drpr t WHERE CONCAT(t.tgl_perawatan,' ',t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter = :dokter
            UNION ALL SELECT t.biayaoperator1 FROM operasi t WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.operator1 = :dokter
            UNION ALL SELECT t.biayaoperator2 FROM operasi t WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.operator2 = :dokter
            UNION ALL SELECT t.biayaoperator3 FROM operasi t WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.operator3 = :dokter
            UNION ALL SELECT t.biayadokter_anak FROM operasi t WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_anak = :dokter
            UNION ALL SELECT t.biayadokter_anestesi FROM operasi t WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_anestesi = :dokter
            UNION ALL SELECT t.biaya_dokter_umum FROM operasi t WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_umum = :dokter
            UNION ALL SELECT t.biaya_dokter_pjanak FROM operasi t WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_pjanak = :dokter
            UNION ALL SELECT t.tarif_tindakan_dokter FROM periksa_radiologi t WHERE CONCAT(t.tgl_periksa,' ',t.jam) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter = :dokter
            UNION ALL SELECT t.tarif_perujuk FROM periksa_radiologi t WHERE CONCAT(t.tgl_periksa,' ',t.jam) BETWEEN :tgl1 AND :tgl2 AND t.dokter_perujuk = :dokter
            UNION ALL SELECT d.bagian_dokter FROM periksa_lab t JOIN detail_periksa_lab d ON t.no_rawat=d.no_rawat AND t.kd_jenis_prw=d.kd_jenis_prw WHERE CONCAT(t.tgl_periksa,' ',t.jam) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter = :dokter
            UNION ALL SELECT t.tarif_perujuk FROM periksa_lab t WHERE CONCAT(t.tgl_periksa,' ',t.jam) BETWEEN :tgl1 AND :tgl2 AND t.dokter_perujuk = :dokter
        ) as x";

        // Hitung Shift Ini
        $stmt = $pdo->prepare($sql_shift);
        $stmt->execute(['tgl1' => $shift_info['start'], 'tgl2' => $shift_info['end'], 'dokter' => $kd_dokter]);
        $total_shift = $stmt->fetchColumn() ?? 0;

        // Hitung Hari Ini
        $stmt2 = $pdo->prepare($sql_shift);
        $stmt2->execute(['tgl1' => "$tgl_hari_ini 00:00:00", 'tgl2' => "$tgl_hari_ini 23:59:59", 'dokter' => $kd_dokter]);
        $total_hari = $stmt2->fetchColumn() ?? 0;

        // Jumlah Pasien
        $sql_pasien = "
        SELECT COUNT(DISTINCT no_rawat) FROM (
            SELECT no_rawat FROM rawat_jl_dr WHERE kd_dokter=:dokter AND tgl_perawatan=:tgl
            UNION ALL SELECT no_rawat FROM rawat_inap_dr WHERE kd_dokter=:dokter AND tgl_perawatan=:tgl
            UNION ALL SELECT no_rawat FROM operasi WHERE (operator1=:dokter OR operator2=:dokter OR dokter_anak=:dokter) AND tgl_operasi=:tgl
        ) as p";
        $stmt3 = $pdo->prepare($sql_pasien);
        $stmt3->execute(['tgl' => $tgl_hari_ini, 'dokter' => $kd_dokter]);
        $jumlah_pasien = $stmt3->fetchColumn() ?? 0;

    } catch (Exception $e) {
        // Silent error
    }
}

require_once 'layout/header.php';
require_once 'layout/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0"><?= $salam ?>, <?= htmlspecialchars($_SESSION['nama']) ?></h1>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        
        <?php if(!empty($kd_dokter)): ?>
        <div class="row">
            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box mb-3 bg-info">
                    <span class="info-box-icon"><i class="fas fa-stopwatch"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pendapatan Shift <?= $shift_info['nama'] ?></span>
                        <span class="info-box-number" style="font-size: 1.5rem;"><?= format_rupiah($total_shift) ?></span>
                        <span class="progress-description">
                            <?= date('H:i', strtotime($shift_info['start'])) ?> s/d Sekarang
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box mb-3 bg-success">
                    <span class="info-box-icon"><i class="fas fa-calendar-day"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Akumulasi Hari Ini</span>
                        <span class="info-box-number" style="font-size: 1.5rem;"><?= format_rupiah($total_hari) ?></span>
                        <span class="progress-description"><?= date('d M Y') ?> (Full Day)</span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box mb-3 bg-warning">
                    <span class="info-box-icon"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pasien Ditangani</span>
                        <span class="info-box-number" style="font-size: 1.5rem;"><?= $jumlah_pasien ?></span>
                        <span class="progress-description">Pasien unik hari ini</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card card-outline card-primary">
                    <div class="card-header"><h3 class="card-title">Aksi Cepat</h3></div>
                    <div class="card-body">
                        <a href="modul/jasmed/ringkasan_shift.php?tgl=<?= date('Y-m-d') ?>&shift=<?= $shift_info['nama'] ?>&proses=1" class="btn btn-app bg-info">
                            <i class="fas fa-search"></i> Cek Shift Ini
                        </a>
                        <a href="modul/jasmed/index.php" class="btn btn-app bg-success">
                            <i class="fas fa-calculator"></i> Audit Bulanan
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="callout callout-info">
                    <h5><i class="fas fa-info"></i> Info Sistem</h5>
                    <p>Selamat datang di e-Dokter. Data yang ditampilkan adalah <b>Real Time</b> dari server RS.</p>
                </div>
            </div>
        </div>

        <?php else: ?>
        
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info">
                    <h5><i class="icon fas fa-user-shield"></i> Mode Manajemen/Admin</h5>
                    Anda login sebagai <b><?= $_SESSION['role'] ?></b>. Anda memiliki hak akses penuh untuk melihat data Jasa Medis seluruh dokter melalui menu di sidebar.
                </div>
                <div class="card">
                    <div class="card-body">
                        <p>Silakan gunakan menu <b>Audit Jasmed</b> atau <b>Laporan Per Shift</b> di sebelah kiri untuk melihat rincian pendapatan dokter.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>

      </div>
    </section>
</div>

<?php require_once 'layout/footer.php'; ?>