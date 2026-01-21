<?php
// modul/jasmed/export_shift.php
session_start();
require_once '../../config/database.php';
require_once '../../config/fungsi.php';

if (!isset($_SESSION['login_user'])) {
    exit("Akses Ditolak");
}

// 1. AMBIL PARAMETER FILTER
$user_dokter_login = $_SESSION['kd_dokter'] ?? '';
$nama_dokter_login = $_SESSION['nama'] ?? '';
$role = $_SESSION['role'];

// Ambil filter dari URL
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-d');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
$kd_dokter = $_GET['kd_dokter'] ?? $user_dokter_login; // Kode dokter yang DIPILIH
$filter_shift = $_GET['filter_shift'] ?? 'all';
$kategori  = $_GET['kategori'] ?? ['ralan_dr', 'ralan_pr', 'ranap_dr', 'ranap_pr', 'operasi', 'radiologi', 'laborat'];

// --- LOGIKA PENENTUAN NAMA DOKTER UNTUK JUDUL ---
$nama_dokter_judul = $nama_dokter_login; // Default nama sendiri

// Jika yang dipilih BUKAN dokter yang login (Fitur Mengintip oleh Admin/Direktur)
if ($kd_dokter != $user_dokter_login && !empty($kd_dokter)) {
    try {
        $stmt_dr = $pdo->prepare("SELECT nm_dokter FROM dokter WHERE kd_dokter = ?");
        $stmt_dr->execute([$kd_dokter]);
        $row_dr = $stmt_dr->fetch();
        if ($row_dr) {
            $nama_dokter_judul = $row_dr['nm_dokter'];
        }
    } catch (Exception $e) {
        // Fallback jika error, biarkan default atau kosong
    }
}
// ------------------------------------------------

// 2. HEADER EXCEL
$filename = "Laporan_Shift_" . $kd_dokter . "-" . preg_replace('/[^A-Za-z0-9]/', '_', $nama_dokter_judul) . "_" . $tgl_awal . ".xls";
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$filename");

$rekap_shift = [];
$grand_total_periode = 0;
try {
    $sql_parts = [];
    // Buffer waktu H-1 dan H+1 untuk shift malam lintas hari
    $buffer_start = date('Y-m-d', strtotime($tgl_awal . ' -1 day')) . " 21:00:00";
    $buffer_end   = date('Y-m-d', strtotime($tgl_akhir . ' +1 day')) . " 07:00:00";

    $params = [
        'tgl1' => $buffer_start, 
        'tgl2' => $buffer_end, 
        'dokter' => $kd_dokter
    ];

    // --- QUERY MONSTER (Copy dari ringkasan_shift.php Revisi Terakhir) ---
    
    if(in_array('ralan_dr', $kategori)) $sql_parts[] = "SELECT 'Ralan (Dr)' as sumber, r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) as tgl_transaksi, jp.nm_perawatan as tindakan, t.biaya_rawat as total_biaya, t.tarif_tindakandr as jm_dokter FROM rawat_jl_dr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan jp ON t.kd_jenis_prw=jp.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
    
    if(in_array('ralan_pr', $kategori)) $sql_parts[] = "SELECT 'Ralan (Dr+Pr)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat), jp.nm_perawatan, t.biaya_rawat, t.tarif_tindakandr FROM rawat_jl_drpr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan jp ON t.kd_jenis_prw=jp.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
    
    if(in_array('ranap_dr', $kategori)) $sql_parts[] = "SELECT 'Ranap (Dr)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat), jpi.nm_perawatan, t.biaya_rawat, t.tarif_tindakandr FROM rawat_inap_dr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_inap jpi ON t.kd_jenis_prw=jpi.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
    
    if(in_array('ranap_pr', $kategori)) $sql_parts[] = "SELECT 'Ranap (Dr+Pr)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat), jpi.nm_perawatan, t.biaya_rawat, t.tarif_tindakandr FROM rawat_inap_drpr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_inap jpi ON t.kd_jenis_prw=jpi.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
    
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

    if(in_array('radiologi', $kategori)) {
        $sql_parts[] = "SELECT 'Radiologi', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam), jpr.nm_perawatan, t.biaya, t.tarif_tindakan_dokter FROM periksa_radiologi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_radiologi jpr ON t.kd_jenis_prw=jpr.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
        $sql_parts[] = "SELECT 'Radiologi (Perujuk)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam), CONCAT(jpr.nm_perawatan, ' (Perujuk)'), t.biaya, t.tarif_perujuk FROM periksa_radiologi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_radiologi jpr ON t.kd_jenis_prw=jpr.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.dokter_perujuk=:dokter";
    }

    if(in_array('laborat', $kategori)) {
        $sql_parts[] = "SELECT 'Laborat', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam), jpl.nm_perawatan, d.biaya_item, d.bagian_dokter FROM periksa_lab t JOIN detail_periksa_lab d ON t.no_rawat=d.no_rawat AND t.kd_jenis_prw=d.kd_jenis_prw AND t.tgl_periksa=d.tgl_periksa AND t.jam=d.jam JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_lab jpl ON t.kd_jenis_prw=jpl.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
        $sql_parts[] = "SELECT 'Laborat (Perujuk)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam), CONCAT(jpl.nm_perawatan, ' (Perujuk)'), t.biaya, t.tarif_perujuk FROM periksa_lab t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_lab jpl ON t.kd_jenis_prw=jpl.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.dokter_perujuk=:dokter";
    }

    if(!empty($sql_parts)){
        $final_sql = implode(" UNION ALL ", $sql_parts) . " ORDER BY tgl_transaksi ASC";
        $stmt = $pdo->prepare($final_sql);
        $stmt->execute($params);
        $raw_data = $stmt->fetchAll();

        // --- GROUPING LOGIC (SAMA DENGAN RINGKASAN_SHIFT.PHP) ---
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
    echo "Error: " . $e->getMessage();
    exit;
}
?>
<html>
<head>
<style>
    body { font-family: sans-serif; font-size: 12px; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #000; padding: 5px; }
    .header { background-color: #ddd; font-weight: bold; }
    .subheader { background-color: #f9f9f9; font-weight: bold; }
    .str { mso-number-format:"\@"; }
    .num { mso-number-format:"\#\,\#\#0"; }
    .total { background-color: #ffffcc; font-weight: bold; }
</style>
</head>
<body>
    <h2>Laporan Detail Jasa Medis Per Shift</h2>
    <p>
        Dokter: <?= $kd_dokter ?>- <?= $nama_dokter_judul ?><br>
        Periode: <?= tanggal_indo($tgl_awal) ?> s/d <?= tanggal_indo($tgl_akhir) ?>
    </p>

    <?php 
    // LOOPING UTAMA (PER SHIFT)
    foreach($rekap_shift as $key => $data): 
    ?>
        <table border="1">
            <thead>
                <tr>
                    <th colspan="7" class="header" align="left">
                        TANGGAL: <?= tanggal_indo($data['tanggal']) ?> | SHIFT: <?= strtoupper($data['shift']) ?>
                    </th>
                </tr>
                <tr class="subheader">
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
                <?php foreach($data['details'] as $det): ?>
                <tr>
                    <td><?= date('H:i', strtotime($det['tgl_transaksi'])) ?></td>
                    <td class="str"><?= $det['no_rkm_medis'] ?></td>
                    <td><?= $det['nm_pasien'] ?></td>
                    <td><?= $det['tindakan'] ?></td>
                    <td><?= $det['sumber'] ?></td>
                    <td class="num"><?= $det['total_biaya'] ?></td>
                    <td class="num"><?= $det['jm_dokter'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total">
                    <td colspan="6" align="right">SUBTOTAL SHIFT <?= strtoupper($data['shift']) ?>:</td>
                    <td class="num"><?= $data['total_jm'] ?></td>
                </tr>
            </tfoot>
        </table>
        <br>
    <?php endforeach; ?>

    <table border="1">
        <tr class="header" style="background-color: #4CAF50; color: white;">
            <td colspan="6" align="right">GRAND TOTAL PERIODE INI:</td>
            <td class="num"><?= $grand_total_periode ?></td>
        </tr>
    </table>

</body>
</html>