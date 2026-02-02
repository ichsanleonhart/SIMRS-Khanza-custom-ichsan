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
$kd_dokter = $_SESSION['kd_dokter'] ?? '';
$is_admin = empty($kd_dokter);

// Waktu & Shift
$shift_info = get_current_shift();
$tgl_hari_ini = date('Y-m-d');
$jam_sekarang = date('H');

// Greeting Logic
$salam = "Selamat Pagi";
if ($jam_sekarang >= 10) $salam = "Selamat Siang";
if ($jam_sekarang >= 15) $salam = "Selamat Sore";
if ($jam_sekarang >= 19) $salam = "Selamat Malam";

// Inisialisasi Variabel
$total_shift = 0;
$total_hari  = 0;
$jumlah_pasien = 0;
$antrean_ralan = 0;
$konsul_pending = 0;

// --- LOGIC DATA DOKTER ---
if (!$is_admin) {
    try {
        // 1. HITUNG ANTREAN RALAN HARI INI (YANG BELUM DIPERIKSA)
        $sql_ralan = "SELECT COUNT(*) FROM reg_periksa 
                      WHERE kd_dokter = :dokter 
                      AND tgl_registrasi = :tgl 
                      AND stts = 'Belum'";
        $stmt = $pdo->prepare($sql_ralan);
        $stmt->execute(['dokter' => $kd_dokter, 'tgl' => $tgl_hari_ini]);
        $antrean_ralan = $stmt->fetchColumn();

        // 2. HITUNG KONSULTASI PENDING (YANG BELUM DIJAWAB)
        // Logic: Cari di konsultasi_medik yg tujuannya SAYA, tapi belum ada di jawaban_konsultasi_medik
        $sql_konsul = "SELECT COUNT(*) 
                       FROM konsultasi_medik k
                       LEFT JOIN jawaban_konsultasi_medik j ON k.no_permintaan = j.no_permintaan
                       WHERE k.kd_dokter_dikonsuli = :dokter 
                       AND j.no_permintaan IS NULL";
        $stmt = $pdo->prepare($sql_konsul);
        $stmt->execute(['dokter' => $kd_dokter]);
        $konsul_pending = $stmt->fetchColumn();

        // 3. HITUNG KEUANGAN (Query Monster - Versi Ringkas)
        $sql_uang = "
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

        // Eksekusi Keuangan Shift
        $stmt = $pdo->prepare($sql_uang);
        $stmt->execute(['tgl1' => $shift_info['start'], 'tgl2' => $shift_info['end'], 'dokter' => $kd_dokter]);
        $total_shift = $stmt->fetchColumn() ?? 0;

        // Eksekusi Keuangan Harian
        $stmt2 = $pdo->prepare($sql_uang);
        $stmt2->execute(['tgl1' => "$tgl_hari_ini 00:00:00", 'tgl2' => "$tgl_hari_ini 23:59:59", 'dokter' => $kd_dokter]);
        $total_hari = $stmt2->fetchColumn() ?? 0;

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
            <h1 class="m-0 text-dark"><?= $salam ?>, <?= htmlspecialchars($_SESSION['nama']) ?></h1>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        
        <?php if(!$is_admin): ?>
        
        <h5 class="mb-2 text-secondary"><i class="fas fa-tasks"></i> Tugas Anda Hari Ini</h5>
        <div class="row">
            
            <div class="col-lg-6 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?= $antrean_ralan ?></h3>
                        <p>Pasien Rawat Jalan (Menunggu)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <a href="modul/ralan/index.php?stts=Belum" class="small-box-footer">
                        Mulai Periksa <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-6 col-6">
                <div class="small-box <?= ($konsul_pending > 0) ? 'bg-danger' : 'bg-success' ?>">
                    <div class="inner">
                        <h3><?= $konsul_pending ?></h3>
                        <p>Konsultasi Masuk (Belum Dijawab)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <a href="modul/konsultasi/index.php" class="small-box-footer">
                        Lihat Inbox <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <h5 class="mb-2 mt-4 text-secondary"><i class="fas fa-chart-line"></i> Estimasi Pendapatan</h5>
        <div class="row">
            <div class="col-12 col-sm-6 col-md-6">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-stopwatch"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Shift <?= $shift_info['nama'] ?></span>
                        <span class="info-box-number"><?= format_rupiah($total_shift) ?></span>
                        <span class="progress-description text-muted text-sm">
                            <i class="far fa-clock"></i> <?= date('H:i', strtotime($shift_info['start'])) ?> s/d Sekarang
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-6">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-wallet"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Akumulasi Hari Ini</span>
                        <span class="info-box-number"><?= format_rupiah($total_hari) ?></span>
                        <span class="progress-description text-muted text-sm">
                           <i class="far fa-calendar-alt"></i> <?= date('d M Y') ?> (Full)
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="callout callout-info">
                    <h5><i class="fas fa-info-circle"></i> Catatan:</h5>
                    <p>
                        Widget merah pada <b>Konsultasi Masuk</b> menandakan ada sejawat yang membutuhkan jawaban Anda.<br>
                        Gunakan menu sidebar untuk akses fitur lengkap.
                    </p>
                </div>
            </div>
        </div>

        <?php else: ?>
        
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info">
                    <h5><i class="icon fas fa-user-shield"></i> Mode Manajemen/Admin</h5>
                    Anda login sebagai <b><?= $_SESSION['role'] ?></b>. Gunakan menu di sebelah kiri untuk monitoring data rumah sakit.
                </div>
            </div>
        </div>
        
        <?php endif; ?>

      </div>
    </section>
</div>

<?php require_once 'layout/footer.php'; ?>