<?php
// File: modules/edokter/jasmed/export.php
require_once '../../../config/config.php';
require_once '../../../config/database.php';
require_once '../../../helpers/auth_helper.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) { die("Akses Ditolak"); }
$is_superadmin = isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';

// Filter Parameter dari URL
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-d');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
$kd_dokter = $_GET['kd_dokter'] ?? $_SESSION['user_id'];
if (!$is_superadmin) { $kd_dokter = $_SESSION['user_id']; }

$kategori = $_GET['kategori'] ?? ['ralan_dr', 'ralan_pr', 'ranap_dr', 'ranap_pr', 'operasi', 'radiologi', 'laborat'];

// Dapatkan Nama Dokter untuk Judul File
$nama_dokter = $kd_dokter;
try {
    $stmt_dr = $pdo->prepare("SELECT nm_dokter FROM dokter WHERE kd_dokter = ?");
    $stmt_dr->execute([$kd_dokter]);
    if($r = $stmt_dr->fetch()) $nama_dokter = $r['nm_dokter'];
} catch (Exception $e) {}

// Setting Header agar diunduh sebagai file Excel
$filename = "Audit_Jasmed_" . preg_replace('/[^A-Za-z0-9]/', '_', $nama_dokter) . "_" . $tgl_awal . ".xls";
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$filename");

$hasil = [];
$sum_biaya = 0;
$sum_jm = 0;

try {
    $sql_parts = [];
    $params = ['tgl1' => $tgl_awal . " 00:00:00", 'tgl2' => $tgl_akhir . " 23:59:59", 'dokter' => $kd_dokter];

    // MENGGUNAKAN ALIAS EKSPLISIT ('as nama_kolom') AGAR PHP MENGENALI SEMUA KOLOM DARI TIAP QUERY
    
    if(in_array('ralan_dr', $kategori)) 
        $sql_parts[] = "SELECT 'Ralan (Dr)' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) as tgl_transaksi, jp.nm_perawatan as tindakan, t.biaya_rawat as total_biaya, t.tarif_tindakandr as jm_dokter FROM rawat_jl_dr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan jp ON t.kd_jenis_prw=jp.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
    
    if(in_array('ralan_pr', $kategori)) 
        $sql_parts[] = "SELECT 'Ralan (Dr+Pr)' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) as tgl_transaksi, jp.nm_perawatan as tindakan, t.biaya_rawat as total_biaya, t.tarif_tindakandr as jm_dokter FROM rawat_jl_drpr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan jp ON t.kd_jenis_prw=jp.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
    
    if(in_array('ranap_dr', $kategori)) 
        $sql_parts[] = "SELECT 'Ranap (Dr)' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) as tgl_transaksi, jpi.nm_perawatan as tindakan, t.biaya_rawat as total_biaya, t.tarif_tindakandr as jm_dokter FROM rawat_inap_dr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_inap jpi ON t.kd_jenis_prw=jpi.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
    
    if(in_array('ranap_pr', $kategori)) 
        $sql_parts[] = "SELECT 'Ranap (Dr+Pr)' as sumber, r.no_rawat as no_rawat, r.no_rkm_medis as no_rkm_medis, p.nm_pasien as nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) as tgl_transaksi, jpi.nm_perawatan as tindakan, t.biaya_rawat as total_biaya, t.tarif_tindakandr as jm_dokter FROM rawat_inap_drpr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_inap jpi ON t.kd_jenis_prw=jpi.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
    
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
            $hasil = array_merge($hasil, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        // Sorting By Time ASC
        usort($hasil, function($a, $b) {
            return strtotime($a['tgl_transaksi']) - strtotime($b['tgl_transaksi']);
        });
    }
} catch (PDOException $e) {
    die("Error DB: " . $e->getMessage());
}
?>
<html>
<head>
<style>
    body { font-family: sans-serif; font-size: 12px; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #000; padding: 5px; }
    .header { background-color: #ddd; font-weight: bold; }
    .str { mso-number-format:"\@"; } /* Mencegah No RM dipotong oleh Excel */
    .num { mso-number-format:"\#\,\#\#0"; } /* Format rupiah standard */
    .total { background-color: #ffffcc; font-weight: bold; }
</style>
</head>
<body>
    <h2>Laporan Audit Jasa Medis (Detail Total)</h2>
    <p>
        Dokter: <?= htmlspecialchars($nama_dokter) ?> (<?= htmlspecialchars($kd_dokter) ?>)<br>
        Periode: <?= $tgl_awal ?> s/d <?= $tgl_akhir ?>
    </p>

    <table border="1">
        <thead>
            <tr class="header">
                <th>Waktu</th>
                <th>No. RM</th>
                <th>Pasien</th>
                <th>No. Rawat</th>
                <th>Tindakan</th>
                <th>Sumber</th>
                <th>Biaya Pasien (Rp)</th>
                <th>JM Dokter (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $processed = [];
            foreach($hasil as $row): 
                if(empty($row['tgl_transaksi'])) continue;

                // Logika Filter Duplikasi Biaya Pasien
                $tindakan_bersih = preg_replace('/\s\((Perujuk|Operator \d|Dokter .*|Anestesi|PJ Anak)\)$/', '', $row['tindakan']);
                $unique_key = $row['no_rawat'] . '_' . $row['tgl_transaksi'] . '_' . $tindakan_bersih;

                $add_cost = false;
                if(!in_array($unique_key, $processed)){
                    $sum_biaya += $row['total_biaya'];
                    $processed[] = $unique_key;
                    $add_cost = true;
                }
                $sum_jm += $row['jm_dokter'];
            ?>
            <tr>
                <td><?= $row['tgl_transaksi'] ?></td>
                <td class="str"><?= $row['no_rkm_medis'] ?></td>
                <td><?= $row['nm_pasien'] ?></td>
                <td class="str"><?= $row['no_rawat'] ?></td>
                <td><?= $row['tindakan'] ?></td>
                <td><?= $row['sumber'] ?></td>
                <td class="num" style="<?= (!$add_cost && $row['total_biaya']>0) ? 'color:gray;' : '' ?>">
                    <?= $row['total_biaya'] ?>
                </td>
                <td class="num"><?= $row['jm_dokter'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total">
                <th colspan="6" align="right">TOTAL BERSIH (Tanpa Duplikasi):</th>
                <th class="num"><?= $sum_biaya ?></th>
                <th class="num"><?= $sum_jm ?></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>