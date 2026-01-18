<?php
// modul/jasmed/laporan_shift.php
session_start();
require_once '../../config/database.php';
require_once '../../config/fungsi.php';

if (!isset($_SESSION['login_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$title = 'Laporan Pendapatan Per Shift';
$menu  = 'laporan_shift';
$user_dokter = $_SESSION['kd_dokter'] ?? '';
$role = $_SESSION['role'];
$can_view_all = ($role == 'admin' || $role == 'super_dokter');

// Filter Input
$tgl_input = $_GET['tgl'] ?? date('Y-m-d');
$shift_pilih = $_GET['shift'] ?? 'Pagi';
$kd_dokter = $_GET['kd_dokter'] ?? $user_dokter;

// Logic Konversi Shift ke Jam Database
$jam_awal = ""; $jam_akhir = "";
if($shift_pilih == 'Pagi'){
    $jam_awal = "$tgl_input 07:00:00";
    $jam_akhir = "$tgl_input 13:59:59";
} elseif($shift_pilih == 'Siang'){
    $jam_awal = "$tgl_input 14:00:00";
    $jam_akhir = "$tgl_input 20:59:59";
} elseif($shift_pilih == 'Malam'){
    $jam_awal = "$tgl_input 21:00:00";
    $besok = date('Y-m-d', strtotime($tgl_input . "+1 day"));
    $jam_akhir = "$besok 06:59:59";
}

// Data Dokter (Admin)
$dokters = [];
if($can_view_all) {
    $stmt = $pdo->query("SELECT kd_dokter, nm_dokter FROM dokter WHERE status='1' ORDER BY nm_dokter ASC");
    $dokters = $stmt->fetchAll();
}

$hasil = [];
if(isset($_GET['proses'])) {
    try {
        $sql_parts = [];
        $params = ['tgl1' => $jam_awal, 'tgl2' => $jam_akhir, 'dokter' => $kd_dokter];

        // --- COPAS QUERY MONSTER YANG SAMA ---
        // (Saya tulis ulang lengkap agar tidak error saat copas)
        
        $sql_parts[] = "SELECT 'Ralan (Dr)' as sumber, r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) as tgl_transaksi, jp.nm_perawatan as tindakan, t.biaya_rawat as total_biaya, t.tarif_tindakandr as jm_dokter FROM rawat_jl_dr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan jp ON t.kd_jenis_prw=jp.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
        $sql_parts[] = "SELECT 'Ralan (Dr+Pr)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat), jp.nm_perawatan, t.biaya_rawat, t.tarif_tindakandr FROM rawat_jl_drpr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan jp ON t.kd_jenis_prw=jp.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
        $sql_parts[] = "SELECT 'Ranap (Dr)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat), jpi.nm_perawatan, t.biaya_rawat, t.tarif_tindakandr FROM rawat_inap_dr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_inap jpi ON t.kd_jenis_prw=jpi.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
        $sql_parts[] = "SELECT 'Ranap (Dr+Pr)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat), jpi.nm_perawatan, t.biaya_rawat, t.tarif_tindakandr FROM rawat_inap_drpr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_inap jpi ON t.kd_jenis_prw=jpi.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
        
        // Operasi 27 Komponen
        $rumus_op = "(t.biayaoperator1 + t.biayaoperator2 + t.biayaoperator3 + t.biayaasisten_operator1 + t.biayaasisten_operator2 + t.biayaasisten_operator3 + t.biayainstrumen + t.biayadokter_anak + t.biayaperawaat_resusitas + t.biayadokter_anestesi + t.biayaasisten_anestesi + t.biayaasisten_anestesi2 + t.biayabidan + t.biayabidan2 + t.biayabidan3 + t.biayaperawat_luar + t.biayaalat + t.biayasewaok + t.akomodasi + t.bagian_rs + t.biaya_omloop + t.biaya_omloop2 + t.biaya_omloop3 + t.biaya_omloop4 + t.biaya_omloop5 + t.biayasarpras + t.biaya_dokter_pjanak + t.biaya_dokter_umum)";
        
        $sql_parts[] = "SELECT 'Operasi (Op1)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (Operator 1)'), $rumus_op, t.biayaoperator1 FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.operator1=:dokter";
        $sql_parts[] = "SELECT 'Operasi (Op2)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (Operator 2)'), $rumus_op, t.biayaoperator2 FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.operator2=:dokter";
        $sql_parts[] = "SELECT 'Operasi (Op3)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (Operator 3)'), $rumus_op, t.biayaoperator3 FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.operator3=:dokter";
        $sql_parts[] = "SELECT 'Operasi (Dr Anak)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (Dokter Anak)'), $rumus_op, t.biayadokter_anak FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_anak=:dokter";
        $sql_parts[] = "SELECT 'Operasi (Anestesi)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (Dokter Anestesi)'), $rumus_op, t.biayadokter_anestesi FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_anestesi=:dokter";
        $sql_parts[] = "SELECT 'Operasi (Dr Umum)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (Dokter Umum)'), $rumus_op, t.biaya_dokter_umum FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_umum=:dokter";
        $sql_parts[] = "SELECT 'Operasi (PJ Anak)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (PJ Anak)'), $rumus_op, t.biaya_dokter_pjanak FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_pjanak=:dokter";
        
        $sql_parts[] = "SELECT 'Radiologi', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam), jpr.nm_perawatan, t.biaya, t.tarif_tindakan_dokter FROM periksa_radiologi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_radiologi jpr ON t.kd_jenis_prw=jpr.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
        $sql_parts[] = "SELECT 'Radiologi (Perujuk)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam), CONCAT(jpr.nm_perawatan, ' (Perujuk)'), t.biaya, t.tarif_perujuk FROM periksa_radiologi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_radiologi jpr ON t.kd_jenis_prw=jpr.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.dokter_perujuk=:dokter";
        
        $sql_parts[] = "SELECT 'Laborat', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam), jpl.nm_perawatan, d.biaya_item, d.bagian_dokter FROM periksa_lab t JOIN detail_periksa_lab d ON t.no_rawat=d.no_rawat AND t.kd_jenis_prw=d.kd_jenis_prw AND t.tgl_periksa=d.tgl_periksa AND t.jam=d.jam JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_lab jpl ON t.kd_jenis_prw=jpl.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
        $sql_parts[] = "SELECT 'Laborat (Perujuk)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam), CONCAT(jpl.nm_perawatan, ' (Perujuk)'), t.biaya, t.tarif_perujuk FROM periksa_lab t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_lab jpl ON t.kd_jenis_prw=jpl.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.dokter_perujuk=:dokter";

        $final_sql = implode(" UNION ALL ", $sql_parts) . " ORDER BY tgl_transaksi ASC";
        $stmt = $pdo->prepare($final_sql);
        $stmt->execute($params);
        $hasil = $stmt->fetchAll();

    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>".$e->getMessage()."</div>";
    }
}

require_once '../../layout/header.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">

<?php require_once '../../layout/sidebar.php'; ?>

<div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6"><h1>Laporan Pendapatan Per Shift</h1></div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        
        <div class="card card-info">
            <div class="card-header"><h3 class="card-title">Filter Shift</h3></div>
            <form method="GET" action="">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Tanggal Mulai Shift</label>
                                <input type="date" class="form-control" name="tgl" value="<?= $tgl_input ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Pilih Shift</label>
                                <select class="form-control" name="shift">
                                    <option value="Pagi" <?= $shift_pilih=='Pagi'?'selected':'' ?>>Pagi (07:00 - 14:00)</option>
                                    <option value="Siang" <?= $shift_pilih=='Siang'?'selected':'' ?>>Siang (14:00 - 21:00)</option>
                                    <option value="Malam" <?= $shift_pilih=='Malam'?'selected':'' ?>>Malam (21:00 - 07:00)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                             <div class="form-group">
                                <label>Dokter</label>
                                <?php if($can_view_all): ?>
                                    <select class="form-control select2" name="kd_dokter">
                                        <?php foreach($dokters as $dr): ?>
                                            <option value="<?= $dr['kd_dokter'] ?>" <?= ($kd_dokter == $dr['kd_dokter']) ? 'selected' : '' ?>>
                                                <?= $dr['nm_dokter'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="text" class="form-control" value="<?= $_SESSION['nama'] ?>" readonly>
                                    <input type="hidden" name="kd_dokter" value="<?= $user_dokter ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button type="submit" name="proses" value="1" class="btn btn-info btn-block">
                                <i class="fas fa-search"></i> Cek
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <?php if(isset($_GET['proses'])): 
            $total_jm = 0;
            foreach($hasil as $row) $total_jm += $row['jm_dokter'];
        ?>
        
        <div class="row">
            <div class="col-12">
                <div class="callout callout-success">
                    <h5><i class="fas fa-wallet"></i> Total Pendapatan Shift <?= $shift_pilih ?></h5>
                    <p>Periode: <b><?= $jam_awal ?></b> s/d <b><?= $jam_akhir ?></b></p>
                    <h2 class="text-success font-weight-bold"><?= format_rupiah($total_jm) ?></h2>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Rincian Transaksi</h3></div>
            <div class="card-body">
                <table id="tblShift" class="table table-bordered table-striped table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>No. RM</th>
                            <th>Pasien</th>
                            <th>Tindakan</th>
                            <th>Sumber</th>
                            <th class="text-right">Biaya Pasien</th>
                            <th class="text-right">JM Dokter</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($hasil as $row): ?>
                        <tr>
                            <td><?= date('H:i', strtotime($row['tgl_transaksi'])) ?></td>
                            <td><?= $row['no_rkm_medis'] ?></td>
                            <td><?= $row['nm_pasien'] ?></td>
                            <td><?= $row['tindakan'] ?></td>
                            <td><?= $row['sumber'] ?></td>
                            <td class="text-right"><?= number_format($row['total_biaya'],0,',','.') ?></td>
                            <td class="text-right font-weight-bold"><?= number_format($row['jm_dokter'],0,',','.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

      </div>
    </section>
</div>

<?php require_once '../../layout/footer.php'; ?>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script>
  $(function () {
    // Tabel Utama
    $("#tblShift").DataTable({
        "responsive": true,
        "autoWidth": false,
        "pageLength": 10,
        "order": [[ 0, "asc" ]]
    });

    // Tabel dalam Modal (Fix width issue saat modal muncul)
    $('.modal').on('shown.bs.modal', function (e) {
        if (!$.fn.DataTable.isDataTable($(this).find('.datatable-modal'))) {
            $(this).find('.datatable-modal').DataTable({
                "responsive": true,
                "autoWidth": false,
                "pageLength": 10,
                "searching": true
            });
        } else {
            // Recalculate columns width agar responsif jalan di modal
            $(this).find('.datatable-modal').DataTable().columns.adjust().responsive.recalc();
        }
    });
  });
</script>