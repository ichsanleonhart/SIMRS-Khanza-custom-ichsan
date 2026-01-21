<?php
// modul/jasmed/ringkasan_shift.php
session_start();
require_once '../../config/database.php';
require_once '../../config/fungsi.php';

if (!isset($_SESSION['login_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$title = 'Ringkasan Jasmed Per Shift';
$menu  = 'ringkasan_shift';
$role = $_SESSION['role'];
$user_dokter = $_SESSION['kd_dokter'] ?? '';
$can_view_all = ($role == 'admin' || $role == 'super_dokter');

// --- FILTER ---
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-d');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
$kd_dokter = $_GET['kd_dokter'] ?? $user_dokter;
$filter_shift = $_GET['filter_shift'] ?? 'all'; 
$default_cats = ['ralan_dr', 'ralan_pr', 'ranap_dr', 'ranap_pr', 'operasi', 'radiologi', 'laborat'];
$kategori = $_GET['kategori'] ?? $default_cats;

// Data Dokter (Admin)
$dokters = [];
if($can_view_all) {
    $stmt = $pdo->query("SELECT kd_dokter, nm_dokter FROM dokter WHERE status='1' ORDER BY nm_dokter ASC");
    $dokters = $stmt->fetchAll();
}

$rekap_shift = [];
$grand_total_periode = 0;

if(isset($_GET['proses'])) {
    try {
        $sql_parts = [];
        // BUFFER WAKTU: H-1 Jam 21:00 s/d H+1 Jam 07:00
        $buffer_start = date('Y-m-d', strtotime($tgl_awal . ' -1 day')) . " 21:00:00";
        $buffer_end   = date('Y-m-d', strtotime($tgl_akhir . ' +1 day')) . " 07:00:00";

        $params = [
            'tgl1' => $buffer_start, 
            'tgl2' => $buffer_end, 
            'dokter' => $kd_dokter
        ];

        // --- MONSTER QUERY (FULL VERSION) ---
        
        // 1. Ralan Dr
        if(in_array('ralan_dr', $kategori)) $sql_parts[] = "SELECT 'Ralan (Dr)' as sumber, r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) as tgl_transaksi, jp.nm_perawatan as tindakan, t.biaya_rawat as total_biaya, t.tarif_tindakandr as jm_dokter FROM rawat_jl_dr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan jp ON t.kd_jenis_prw=jp.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
        
        // 2. Ralan DrPr
        if(in_array('ralan_pr', $kategori)) $sql_parts[] = "SELECT 'Ralan (Dr+Pr)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat), jp.nm_perawatan, t.biaya_rawat, t.tarif_tindakandr FROM rawat_jl_drpr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan jp ON t.kd_jenis_prw=jp.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
        
        // 3. Ranap Dr
        if(in_array('ranap_dr', $kategori)) $sql_parts[] = "SELECT 'Ranap (Dr)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat), jpi.nm_perawatan, t.biaya_rawat, t.tarif_tindakandr FROM rawat_inap_dr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_inap jpi ON t.kd_jenis_prw=jpi.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
        
        // 4. Ranap DrPr
        if(in_array('ranap_pr', $kategori)) $sql_parts[] = "SELECT 'Ranap (Dr+Pr)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat), jpi.nm_perawatan, t.biaya_rawat, t.tarif_tindakandr FROM rawat_inap_drpr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_inap jpi ON t.kd_jenis_prw=jpi.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
        
        // 5. Operasi
        if(in_array('operasi', $kategori)){
            $rumus_op = "(t.biayaoperator1 + t.biayaoperator2 + t.biayaoperator3 + t.biayaasisten_operator1 + t.biayaasisten_operator2 + t.biayaasisten_operator3 + t.biayainstrumen + t.biayadokter_anak + t.biayaperawaat_resusitas + t.biayadokter_anestesi + t.biayaasisten_anestesi + t.biayaasisten_anestesi2 + t.biayabidan + t.biayabidan2 + t.biayabidan3 + t.biayaperawat_luar + t.biayaalat + t.biayasewaok + t.akomodasi + t.bagian_rs + t.biaya_omloop + t.biaya_omloop2 + t.biaya_omloop3 + t.biaya_omloop4 + t.biaya_omloop5 + t.biayasarpras + t.biaya_dokter_pjanak + t.biaya_dokter_umum)";
            $sql_parts[] = "SELECT 'Operasi (Op1)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (Operator 1)'), $rumus_op, t.biayaoperator1 FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.operator1=:dokter";
            $sql_parts[] = "SELECT 'Operasi (Op2)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (Operator 2)'), $rumus_op, t.biayaoperator2 FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.operator2=:dokter";
            $sql_parts[] = "SELECT 'Operasi (Op3)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (Operator 3)'), $rumus_op, t.biayaoperator3 FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.operator3=:dokter";
            $sql_parts[] = "SELECT 'Operasi (Dr Anak)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (Dokter Anak)'), $rumus_op, t.biayadokter_anak FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_anak=:dokter";
            $sql_parts[] = "SELECT 'Operasi (Anestesi)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (Dokter Anestesi)'), $rumus_op, t.biayadokter_anestesi FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_anestesi=:dokter";
            $sql_parts[] = "SELECT 'Operasi (Dr Umum)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (Dokter Umum)'), $rumus_op, t.biaya_dokter_umum FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_umum=:dokter";
            $sql_parts[] = "SELECT 'Operasi (PJ Anak)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (PJ Anak)'), $rumus_op, t.biaya_dokter_pjanak FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_pjanak=:dokter";
        }

        // 6. Radiologi
        if(in_array('radiologi', $kategori)) {
            $sql_parts[] = "SELECT 'Radiologi', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam), jpr.nm_perawatan, t.biaya, t.tarif_tindakan_dokter FROM periksa_radiologi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_radiologi jpr ON t.kd_jenis_prw=jpr.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
            $sql_parts[] = "SELECT 'Radiologi (Perujuk)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam), CONCAT(jpr.nm_perawatan, ' (Perujuk)'), t.biaya, t.tarif_perujuk FROM periksa_radiologi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_radiologi jpr ON t.kd_jenis_prw=jpr.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.dokter_perujuk=:dokter";
        }

        // 7. Laborat
        if(in_array('laborat', $kategori)) {
            $sql_parts[] = "SELECT 'Laborat', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam), jpl.nm_perawatan, d.biaya_item, d.bagian_dokter FROM periksa_lab t JOIN detail_periksa_lab d ON t.no_rawat=d.no_rawat AND t.kd_jenis_prw=d.kd_jenis_prw AND t.tgl_periksa=d.tgl_periksa AND t.jam=d.jam JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_lab jpl ON t.kd_jenis_prw=jpl.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
            $sql_parts[] = "SELECT 'Laborat (Perujuk)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam), CONCAT(jpl.nm_perawatan, ' (Perujuk)'), t.biaya, t.tarif_perujuk FROM periksa_lab t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_lab jpl ON t.kd_jenis_prw=jpl.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.dokter_perujuk=:dokter";
        }

        if(!empty($sql_parts)){
            $final_sql = implode(" UNION ALL ", $sql_parts) . " ORDER BY tgl_transaksi ASC";
            $stmt = $pdo->prepare($final_sql);
            $stmt->execute($params);
            $raw_data = $stmt->fetchAll();

            foreach($raw_data as $row) {
                $ts = strtotime($row['tgl_transaksi']);
                $jam = (int)date('H', $ts);
                $tgl_real = date('Y-m-d', $ts);
                
                $shift_name = "";
                $shift_date = $tgl_real;

                if ($jam >= 7 && $jam < 14) {
                    $shift_name = "Pagi";
                } elseif ($jam >= 14 && $jam < 21) {
                    $shift_name = "Siang";
                } else {
                    $shift_name = "Malam";
                    if ($jam < 7) {
                        $shift_date = date('Y-m-d', strtotime($tgl_real . ' -1 day'));
                    }
                }

                if ($shift_date >= $tgl_awal && $shift_date <= $tgl_akhir) {
                    if ($filter_shift != 'all' && $filter_shift != $shift_name) continue;

                    $key = $shift_date . "_" . $shift_name;
                    
                    if(!isset($rekap_shift[$key])) {
                        $rekap_shift[$key] = [
                            'tanggal' => $shift_date,
                            'shift' => $shift_name,
                            'total_jm' => 0,
                            'details' => []
                        ];
                    }
                    $rekap_shift[$key]['total_jm'] += $row['jm_dokter'];
                    $rekap_shift[$key]['details'][] = $row;
                    $grand_total_periode += $row['jm_dokter'];
                }
            }
            ksort($rekap_shift);
        }

    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

require_once '../../layout/header.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css">

<?php require_once '../../layout/sidebar.php'; ?>

<div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6"><h1>Ringkasan Per Shift</h1></div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        
        <div class="callout callout-info">
            <h5><i class="fas fa-info-circle"></i> Catatan Perhitungan Shift:</h5>
            <p class="mb-0">
                Laporan ini menggunakan logika <b>Jam Operasional Shift</b>, berbeda dengan tanggal kalender (Audit).<br>
                <ul class="mb-1 pl-4">
                    <li><b>Pagi:</b> 07:00 - 14:00</li>
                    <li><b>Siang:</b> 14:00 - 21:00</li>
                    <li><b>Malam:</b> 21:00 - 07:00 (Keesokan harinya)</li>
                </ul>
                <small class="text-danger font-italic">* Transaksi yang terjadi pada jam 00:00 s/d 07:00 pagi akan masuk ke perhitungan Shift Malam <b>tanggal sebelumnya</b>.</small>
            </p>
        </div>

        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title">Filter Pencarian</h3></div>
            <form method="GET" action="">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Tanggal Awal</label>
                                <input type="date" class="form-control" name="tgl_awal" value="<?= $tgl_awal ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Tanggal Akhir</label>
                                <input type="date" class="form-control" name="tgl_akhir" value="<?= $tgl_akhir ?>" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Shift</label>
                                <select class="form-control" name="filter_shift">
                                    <option value="all" <?= $filter_shift=='all'?'selected':'' ?>>Semua</option>
                                    <option value="Pagi" <?= $filter_shift=='Pagi'?'selected':'' ?>>Pagi</option>
                                    <option value="Siang" <?= $filter_shift=='Siang'?'selected':'' ?>>Siang</option>
                                    <option value="Malam" <?= $filter_shift=='Malam'?'selected':'' ?>>Malam</option>
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
                    </div>
                    
                    <div class="row">
                        <div class="col-md-10">
                            <div class="form-group mb-0">
                                <label>Sumber Data:</label><br>
                                <?php 
                                $labels = ['ralan_dr' => 'Ralan (Dr)', 'ralan_pr' => 'Ralan (Dr+Pr)', 'ranap_dr' => 'Ranap (Dr)', 'ranap_pr' => 'Ranap (Dr+Pr)', 'operasi' => 'Operasi', 'radiologi' => 'Radiologi', 'laborat' => 'Laborat'];
                                foreach($labels as $val => $txt): 
                                ?>
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input class="custom-control-input" type="checkbox" id="c_<?= $val ?>" name="kategori[]" value="<?= $val ?>" <?= in_array($val, $kategori) ? 'checked' : '' ?>>
                                    <label for="c_<?= $val ?>" class="custom-control-label"><?= $txt ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button type="submit" name="proses" value="1" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> Cek
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

    <?php if(isset($_GET['proses'])): ?>
    
    <?php
        $params_export = [
            'tgl_awal'  => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'kd_dokter' => $kd_dokter,
            'filter_shift' => $filter_shift,
            'kategori'  => $kategori
        ];
        $url_export = "export_shift.php?" . http_build_query($params_export);
    ?>

    <div class="card card-success card-outline">
        <div class="card-header">
            <h3 class="card-title">Hasil Rekapitulasi</h3>
            <div class="card-tools">
                <a href="<?= $url_export ?>" target="_blank" class="btn btn-tool bg-success">
                    <i class="fas fa-file-excel"></i> Download Excel
                </a>
            </div>
        </div>
            <div class="card-body p-0">
                <table id="tblSummary" class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Shift</th>
                            <th class="text-right">Total JM Dokter</th>
                            <th class="text-center" width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($rekap_shift as $key => $data): 
                            $modal_id = "modal_" . md5($key);
                            $badge = ($data['shift'] == 'Pagi') ? 'info' : (($data['shift'] == 'Siang') ? 'warning' : 'indigo');
                        ?>
                        <tr>
                            <td><b><?= tanggal_indo($data['tanggal']) ?></b></td>
                            <td><span class="badge badge-<?= $badge ?>" style="font-size: 100%"><?= $data['shift'] ?></span></td>
                            <td class="text-right font-weight-bold"><?= format_rupiah($data['total_jm']) ?></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#<?= $modal_id ?>">
                                    <i class="fas fa-list"></i> Lihat Detail
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($rekap_shift)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">Tidak ada data transaksi pada rentang ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="bg-success">
                        <tr>
                            <th colspan="2" class="text-right">TOTAL PENDAPATAN PERIODE INI:</th>
                            <th class="text-right"><?= format_rupiah($grand_total_periode) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endif; ?>

      </div>
    </section>
</div>

<?php if(isset($_GET['proses'])): ?>
    <?php foreach($rekap_shift as $key => $data): 
        $modal_id = "modal_" . md5($key);
        $badge = ($data['shift'] == 'Pagi') ? 'info' : (($data['shift'] == 'Siang') ? 'warning' : 'indigo');
    ?>
    <div class="modal fade" id="<?= $modal_id ?>" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-<?= $badge ?>">
                    <h5 class="modal-title text-white">
                        Detail Shift <?= $data['shift'] ?> (<?= tanggal_indo($data['tanggal']) ?>)
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered table-sm datatable-modal" style="width:100%">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>No. RM</th>
                                <th>Pasien</th>
                                <th>Tindakan</th>
                                <th>Sumber</th>
                                <th class="text-right">Biaya</th>
                                <th class="text-right">JM Dokter</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data['details'] as $det): 
                                $cls = ($det['jm_dokter'] == 0 && $det['total_biaya'] > 0) ? 'text-danger' : '';    
                            ?>
                            <tr class="<?= $cls ?>">
                                <td><?= date('d M Y H:i', strtotime($det['tgl_transaksi'])) ?></td>
                                <td><?= $det['no_rkm_medis'] ?></td>
                                <td><?= $det['nm_pasien'] ?></td>
                                <td><?= $det['tindakan'] ?></td>
                                <td><?= $det['sumber'] ?></td>
                                <td class="text-right"><?= number_format($det['total_biaya'],0,',','.') ?></td>
                                <td class="text-right font-weight-bold"><?= number_format($det['jm_dokter'],0,',','.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once '../../layout/footer.php'; ?>

<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"></script>

<script>
  $(function () {
    // Tabel Utama
    $("#tblSummary").DataTable({
        "responsive": true,
        "autoWidth": false,
        "ordering": false,
        "lengthChange": false,
        "pageLength": 50
    });

    // Tabel dalam Modal
    $('.modal').on('shown.bs.modal', function (e) {
        var modalTable = $(this).find('.datatable-modal');
        if (!$.fn.DataTable.isDataTable(modalTable)) {
            modalTable.DataTable({
                "responsive": true,
                "autoWidth": false,
                "pageLength": 10,
                "ordering": true,
                "language": {
                    "search": "Cari:",
                    "lengthMenu": "_MENU_",
                    "info": "_START_-_END_ dari _TOTAL_"
                }
            });
        } else {
            modalTable.DataTable().columns.adjust().responsive.recalc();
        }
    });
  });
</script>