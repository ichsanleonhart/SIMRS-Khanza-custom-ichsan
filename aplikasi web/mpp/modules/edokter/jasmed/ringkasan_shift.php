<?php
// File: modules/edokter/jasmed/ringkasan_shift.php
require_once '../../../config/config.php';
require_once '../../../config/database.php';
require_once '../../../helpers/auth_helper.php';

cekLogin();
if (!cekAkses('soap_perawatan')) { die("Akses Ditolak."); }

$is_superadmin = isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
$user_dokter = $_SESSION['user_id'];

// Filter
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-d');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
$kd_dokter = $_GET['kd_dokter'] ?? $user_dokter;
$filter_shift = $_GET['filter_shift'] ?? 'all'; 
$default_cats = ['ralan_dr', 'ralan_pr', 'ranap_dr', 'ranap_pr', 'operasi', 'radiologi', 'laborat'];
$kategori = $_GET['kategori'] ?? $default_cats;

if (!$is_superadmin) { $kd_dokter = $user_dokter; }

$dokters = [];
if($is_superadmin) {
    $stmt = $pdo->query("SELECT kd_dokter, nm_dokter FROM dokter WHERE status='1' ORDER BY nm_dokter ASC");
    $dokters = $stmt->fetchAll();
}

$rekap_shift = [];
$grand_total_periode = 0;
$raw_data = [];

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

    // [FIX 1970 BUG]: ALIAS EKSPLISIT ('as nama_kolom')
    if(in_array('ralan_dr', $kategori)) $sql_parts[] = "SELECT 'Ralan (Dr)' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) as tgl_transaksi, jp.nm_perawatan as tindakan, t.biaya_rawat as total_biaya, t.tarif_tindakandr as jm_dokter FROM rawat_jl_dr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan jp ON t.kd_jenis_prw=jp.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
    if(in_array('ralan_pr', $kategori)) $sql_parts[] = "SELECT 'Ralan (Dr+Pr)' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) as tgl_transaksi, jp.nm_perawatan as tindakan, t.biaya_rawat as total_biaya, t.tarif_tindakandr as jm_dokter FROM rawat_jl_drpr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan jp ON t.kd_jenis_prw=jp.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
    if(in_array('ranap_dr', $kategori)) $sql_parts[] = "SELECT 'Ranap (Dr)' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) as tgl_transaksi, jpi.nm_perawatan as tindakan, t.biaya_rawat as total_biaya, t.tarif_tindakandr as jm_dokter FROM rawat_inap_dr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_inap jpi ON t.kd_jenis_prw=jpi.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
    if(in_array('ranap_pr', $kategori)) $sql_parts[] = "SELECT 'Ranap (Dr+Pr)' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) as tgl_transaksi, jpi.nm_perawatan as tindakan, t.biaya_rawat as total_biaya, t.tarif_tindakandr as jm_dokter FROM rawat_inap_drpr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_inap jpi ON t.kd_jenis_prw=jpi.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
    
    if(in_array('operasi', $kategori)){
        $rumus_op = "(t.biayaoperator1 + t.biayaoperator2 + t.biayaoperator3 + t.biayaasisten_operator1 + t.biayaasisten_operator2 + t.biayaasisten_operator3 + t.biayainstrumen + t.biayadokter_anak + t.biayaperawaat_resusitas + t.biayadokter_anestesi + t.biayaasisten_anestesi + t.biayaasisten_anestesi2 + t.biayabidan + t.biayabidan2 + t.biayabidan3 + t.biayaperawat_luar + t.biayaalat + t.biayasewaok + t.akomodasi + t.bagian_rs + t.biaya_omloop + t.biaya_omloop2 + t.biaya_omloop3 + t.biaya_omloop4 + t.biaya_omloop5 + t.biayasarpras + t.biaya_dokter_pjanak + t.biaya_dokter_umum)";
        $sql_parts[] = "SELECT 'Operasi (Op1)' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, t.tgl_operasi as tgl_transaksi, CONCAT(po.nm_perawatan, ' (Operator 1)') as tindakan, $rumus_op as total_biaya, t.biayaoperator1 as jm_dokter FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.operator1=:dokter";
        $sql_parts[] = "SELECT 'Operasi (Op2)' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, t.tgl_operasi as tgl_transaksi, CONCAT(po.nm_perawatan, ' (Operator 2)') as tindakan, $rumus_op as total_biaya, t.biayaoperator2 as jm_dokter FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.operator2=:dokter";
        $sql_parts[] = "SELECT 'Operasi (Op3)' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, t.tgl_operasi as tgl_transaksi, CONCAT(po.nm_perawatan, ' (Operator 3)') as tindakan, $rumus_op as total_biaya, t.biayaoperator3 as jm_dokter FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.operator3=:dokter";
        $sql_parts[] = "SELECT 'Operasi (Dr Anak)' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, t.tgl_operasi as tgl_transaksi, CONCAT(po.nm_perawatan, ' (Dokter Anak)') as tindakan, $rumus_op as total_biaya, t.biayadokter_anak as jm_dokter FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_anak=:dokter";
        $sql_parts[] = "SELECT 'Operasi (Anestesi)' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, t.tgl_operasi as tgl_transaksi, CONCAT(po.nm_perawatan, ' (Dokter Anestesi)') as tindakan, $rumus_op as total_biaya, t.biayadokter_anestesi as jm_dokter FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_anestesi=:dokter";
        $sql_parts[] = "SELECT 'Operasi (Dr Umum)' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, t.tgl_operasi as tgl_transaksi, CONCAT(po.nm_perawatan, ' (Dokter Umum)') as tindakan, $rumus_op as total_biaya, t.biaya_dokter_umum as jm_dokter FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_umum=:dokter";
        $sql_parts[] = "SELECT 'Operasi (PJ Anak)' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, t.tgl_operasi as tgl_transaksi, CONCAT(po.nm_perawatan, ' (PJ Anak)') as tindakan, $rumus_op as total_biaya, t.biaya_dokter_pjanak as jm_dokter FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_pjanak=:dokter";
    }

    if(in_array('radiologi', $kategori)) {
        $sql_parts[] = "SELECT 'Radiologi' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam) as tgl_transaksi, jpr.nm_perawatan as tindakan, t.biaya as total_biaya, t.tarif_tindakan_dokter as jm_dokter FROM periksa_radiologi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_radiologi jpr ON t.kd_jenis_prw=jpr.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
        $sql_parts[] = "SELECT 'Radiologi (Perujuk)' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam) as tgl_transaksi, CONCAT(jpr.nm_perawatan, ' (Perujuk)') as tindakan, t.biaya as total_biaya, t.tarif_perujuk as jm_dokter FROM periksa_radiologi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_radiologi jpr ON t.kd_jenis_prw=jpr.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.dokter_perujuk=:dokter";
    }

    if(in_array('laborat', $kategori)) {
        $sql_parts[] = "SELECT 'Laborat' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam) as tgl_transaksi, jpl.nm_perawatan as tindakan, d.biaya_item as total_biaya, d.bagian_dokter as jm_dokter FROM periksa_lab t JOIN detail_periksa_lab d ON t.no_rawat=d.no_rawat AND t.kd_jenis_prw=d.kd_jenis_prw AND t.tgl_periksa=d.tgl_periksa AND t.jam=d.jam JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_lab jpl ON t.kd_jenis_prw=jpl.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
        $sql_parts[] = "SELECT 'Laborat (Perujuk)' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam) as tgl_transaksi, CONCAT(jpl.nm_perawatan, ' (Perujuk)') as tindakan, t.biaya as total_biaya, t.tarif_perujuk as jm_dokter FROM periksa_lab t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_lab jpl ON t.kd_jenis_prw=jpl.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.dokter_perujuk=:dokter";
    }

    if(!empty($sql_parts)){
        foreach($sql_parts as $q) {
            $stmt = $pdo->prepare($q);
            $stmt->execute($params);
            $raw_data = array_merge($raw_data, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        // Urutkan Array berdasar Waktu Transaksi ASC
        usort($raw_data, function($a, $b) {
            return strtotime($a['tgl_transaksi']) - strtotime($b['tgl_transaksi']);
        });

        // LOGIKA GROUPING PER SHIFT
        foreach($raw_data as $row) {
            if(empty($row['tgl_transaksi'])) continue;
            
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
                    $shift_date = date('Y-m-d', strtotime($tgl_real . ' -1 day')); // Tarik mundur tanggal
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
} catch (PDOException $e) {
    die("Error DB: " . $e->getMessage());
}

$params_export = [
    'tgl_awal'  => $tgl_awal, 'tgl_akhir' => $tgl_akhir,
    'kd_dokter' => $kd_dokter, 'filter_shift' => $filter_shift, 'kategori'  => $kategori
];
$url_export = "export_shift.php?" . http_build_query($params_export);

require_once '../../../layout/header.php';
require_once '../../../layout/sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="h5 mb-0 text-gray-800"><i class="fas fa-clock text-primary me-2"></i> Ringkasan Jasmed Per Shift</h4>
        <div>
            <a href="index.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali ke Audit Total</a>
        </div>
    </div>

    <div class="alert alert-info py-2 small shadow-sm">
        <h6 class="alert-heading fw-bold mb-1"><i class="fas fa-info-circle"></i> Catatan Perhitungan Shift:</h6>
        Laporan ini menggunakan logika <b>Jam Operasional Shift</b>, berbeda dengan tanggal kalender (Audit).<br>
        <ul class="mb-0 ps-3">
            <li><b>Pagi:</b> 07:00 - 14:00</li>
            <li><b>Siang:</b> 14:00 - 21:00</li>
            <li><b>Malam:</b> 21:00 - 07:00 (Keesokan harinya)</li>
        </ul>
        <span class="text-danger fst-italic">* Transaksi yang terjadi pada jam 00:00 s/d 07:00 pagi akan masuk ke perhitungan Shift Malam <b>tanggal sebelumnya</b>.</span>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="small fw-bold">Dari Tanggal</label>
                        <input type="date" class="form-control form-control-sm" name="tgl_awal" value="<?= htmlspecialchars($tgl_awal) ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Sampai Tanggal</label>
                        <input type="date" class="form-control form-control-sm" name="tgl_akhir" value="<?= htmlspecialchars($tgl_akhir) ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Pilih Shift</label>
                        <select class="form-select form-select-sm" name="filter_shift">
                            <option value="all" <?= $filter_shift=='all'?'selected':'' ?>>Semua Shift</option>
                            <option value="Pagi" <?= $filter_shift=='Pagi'?'selected':'' ?>>Pagi</option>
                            <option value="Siang" <?= $filter_shift=='Siang'?'selected':'' ?>>Siang</option>
                            <option value="Malam" <?= $filter_shift=='Malam'?'selected':'' ?>>Malam</option>
                        </select>
                    </div>
                    
                    <?php if($is_superadmin): ?>
                    <div class="col-md-4">
                        <label class="small fw-bold">Pilih Dokter (Admin)</label>
                        <select class="form-select form-select-sm select2" name="kd_dokter">
                            <?php foreach($dokters as $d): ?>
                                <option value="<?= $d['kd_dokter'] ?>" <?= ($kd_dokter == $d['kd_dokter']) ? 'selected' : '' ?>><?= $d['nm_dokter'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-search"></i> Cek Shift</button>
                    </div>
                </div>

                <div class="mt-2 small">
                    <span class="fw-bold me-2">Sumber Data:</span>
                    <?php 
                    $cat_list = [
                        'ralan_dr' => 'Ralan (Dr)', 'ralan_pr' => 'Ralan (Dr+Pr)', 
                        'ranap_dr' => 'Ranap (Dr)', 'ranap_pr' => 'Ranap (Dr+Pr)', 
                        'operasi' => 'Operasi', 'radiologi' => 'Radiologi', 'laborat' => 'Laborat'
                    ];
                    foreach($cat_list as $val => $label): 
                        $checked = in_array($val, $kategori) ? 'checked' : '';
                    ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="kategori[]" value="<?= $val ?>" <?= $checked ?>>
                            <label class="form-check-label"><?= $label ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-4 border-success">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-success">Hasil Rekapitulasi Per Shift</span>
            <a href="<?= $url_export ?>" target="_blank" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Download Excel</a>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover table-bordered mb-0 align-middle w-100" id="tblSummary">
                <thead class="bg-light text-center small">
                    <tr>
                        <th width="30%">Tanggal Shift</th>
                        <th width="20%">Shift Jaga</th>
                        <th width="30%">Total JM Dokter (Rp)</th>
                        <th width="20%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rekap_shift as $key => $data): 
                        $modal_id = "modal_" . md5($key);
                        $badge_color = ($data['shift'] == 'Pagi') ? 'info' : (($data['shift'] == 'Siang') ? 'warning text-dark' : 'dark');
                    ?>
                    <tr>
                        <td class="text-center fw-bold"><?= date('d/m/Y', strtotime($data['tanggal'])) ?></td>
                        <td class="text-center"><span class="badge bg-<?= $badge_color ?> w-50 py-2"><?= $data['shift'] ?></span></td>
                        <td class="text-end fw-bold text-success" data-sort="<?= $data['total_jm'] ?>"><?= number_format($data['total_jm'], 0, ',', '.') ?></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#<?= $modal_id ?>">
                                <i class="fas fa-list"></i> Lihat Detail
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-success text-white">
                    <tr>
                        <th colspan="2" class="text-end py-3">GRAND TOTAL PERIODE INI:</th>
                        <th class="text-end fs-5 py-3">Rp <?= number_format($grand_total_periode, 0, ',', '.') ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php foreach($rekap_shift as $key => $data): 
    $modal_id = "modal_" . md5($key);
    $badge_color = ($data['shift'] == 'Pagi') ? 'info' : (($data['shift'] == 'Siang') ? 'warning' : 'dark');
    $text_color = ($data['shift'] == 'Siang') ? 'text-dark' : 'text-white';
?>
<div class="modal fade" id="<?= $modal_id ?>" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-<?= $badge_color ?> <?= $text_color ?> py-2">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-search-dollar"></i> Detail Shift <?= $data['shift'] ?> (<?= date('d/m/Y', strtotime($data['tanggal'])) ?>)
                </h5>
                <button type="button" class="btn-close <?= $data['shift'] != 'Siang' ? 'btn-close-white' : '' ?>" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2 table-responsive">
                <table class="table table-bordered table-sm table-hover align-middle datatable-modal w-100">
                    <thead class="bg-light text-center small">
                        <tr>
                            <th>Waktu</th>
                            <th>No. RM</th>
                            <th>Pasien</th>
                            <th>Tindakan</th>
                            <th>Sumber</th>
                            <th>Biaya Pasien</th>
                            <th>JM Dokter</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $processed_modal = [];
                        foreach($data['details'] as $det): 
                            // Anti Duplikasi Biaya Pasien dalam Modal
                            $tindakan_bersih = preg_replace('/\s\((Perujuk|Operator \d|Dokter .*|Anestesi|PJ Anak)\)$/', '', $det['tindakan']);
                            $unique_key = $det['no_rawat'] . '_' . $det['tgl_transaksi'] . '_' . $tindakan_bersih;

                            $add_cost = false;
                            if(!in_array($unique_key, $processed_modal)){
                                $processed_modal[] = $unique_key;
                                $add_cost = true;
                            }
                            $strike_cls = (!$add_cost && $det['total_biaya']>0) ? 'text-muted text-decoration-line-through' : 'fw-bold';
                        ?>
                        <tr>
                            <td class="text-center"><small><?= date('H:i', strtotime($det['tgl_transaksi'])) ?></small></td>
                            <td class="text-center"><?= $det['no_rkm_medis'] ?></td>
                            <td><?= htmlspecialchars($det['nm_pasien']) ?></td>
                            <td><?= htmlspecialchars($det['tindakan']) ?></td>
                            <td class="text-center"><span class="badge bg-secondary"><?= $det['sumber'] ?></span></td>
                            <td class="text-end <?= $strike_cls ?>"><?= number_format($det['total_biaya'],0,',','.') ?></td>
                            <td class="text-end fw-bold text-success"><?= number_format($det['jm_dokter'],0,',','.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer py-1 bg-light">
                <span class="me-auto fw-bold">TOTAL JM SHIFT INI: <span class="text-success fs-5">Rp <?= number_format($data['total_jm'],0,',','.') ?></span></span>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php require_once '../../../layout/footer.php'; ?>

<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap-5' });

    // Tabel Utama Summary
    $("#tblSummary").DataTable({
        "ordering": true,
        "order": [[ 0, "asc" ]],
        "language": { "search": "Cari Shift/Tanggal:" },
        "pageLength": 25
    });

    // Inisialisasi DataTables dalam Modal (Saat Modal Dibuka)
    $('.modal').on('shown.bs.modal', function (e) {
        var modalTable = $(this).find('.datatable-modal');
        if (!$.fn.DataTable.isDataTable(modalTable)) {
            modalTable.DataTable({
                "ordering": true,
                "pageLength": 10,
                "language": { "search": "Cari Pasien/Tindakan:" }
            });
        }
    });
});
</script>