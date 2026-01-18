<?php
// modul/jasmed/export.php
session_start();
require_once '../../config/database.php';
require_once '../../config/fungsi.php';

if (!isset($_SESSION['login_user'])) {
    exit("Akses Ditolak");
}

// Ambil Parameter Filter
$user_dokter = $_SESSION['kd_dokter'] ?? '';
$role = $_SESSION['role'];
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-d');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
$kd_dokter = $_GET['kd_dokter'] ?? $user_dokter;
$kategori  = $_GET['kategori'] ?? ['ralan_dr', 'ralan_pr', 'ranap_dr', 'ranap_pr', 'operasi', 'radiologi', 'laborat'];

// Header Excel
$filename = "Audit_Jasmed_" . $kd_dokter . "_" . $tgl_awal . ".xls";
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$filename");

$hasil = [];
try {
    $sql_parts = [];
    // Range Waktu Full 24 Jam Presisi
    $params = [
        'tgl1' => $tgl_awal . " 00:00:00",
        'tgl2' => $tgl_akhir . " 23:59:59",
        'dokter' => $kd_dokter
    ];

    // --- QUERY MONSTER (SAMA DENGAN INDEX.PHP REVISI 6) ---

    // 1. Ralan Dr
    if(in_array('ralan_dr', $kategori)){
        $sql_parts[] = "SELECT 'Ralan (Dr)' as sumber, r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) as tgl_transaksi, jp.nm_perawatan as tindakan, t.biaya_rawat as total_biaya, t.tarif_tindakandr as jm_dokter FROM rawat_jl_dr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan jp ON t.kd_jenis_prw=jp.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
    }
    // 2. Ralan DrPr
    if(in_array('ralan_pr', $kategori)){
        $sql_parts[] = "SELECT 'Ralan (Dr+Pr)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat), jp.nm_perawatan, t.biaya_rawat, t.tarif_tindakandr FROM rawat_jl_drpr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan jp ON t.kd_jenis_prw=jp.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
    }
    // 3. Ranap Dr
    if(in_array('ranap_dr', $kategori)){
        $sql_parts[] = "SELECT 'Ranap (Dr)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat), jpi.nm_perawatan, t.biaya_rawat, t.tarif_tindakandr FROM rawat_inap_dr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_inap jpi ON t.kd_jenis_prw=jpi.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
    }
    // 4. Ranap DrPr
    if(in_array('ranap_pr', $kategori)){
        $sql_parts[] = "SELECT 'Ranap (Dr+Pr)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_perawatan, ' ', t.jam_rawat), jpi.nm_perawatan, t.biaya_rawat, t.tarif_tindakandr FROM rawat_inap_drpr t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_inap jpi ON t.kd_jenis_prw=jpi.kd_jenis_prw WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
    }
    // 5. Operasi (27 Komponen Biaya)
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
    if(in_array('radiologi', $kategori)){
        $sql_parts[] = "SELECT 'Radiologi', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam), jpr.nm_perawatan, t.biaya, t.tarif_tindakan_dokter FROM periksa_radiologi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_radiologi jpr ON t.kd_jenis_prw=jpr.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
        $sql_parts[] = "SELECT 'Radiologi (Perujuk)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam), CONCAT(jpr.nm_perawatan, ' (Perujuk)'), t.biaya, t.tarif_perujuk FROM periksa_radiologi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_radiologi jpr ON t.kd_jenis_prw=jpr.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.dokter_perujuk=:dokter";
    }
    // 7. Laborat
    if(in_array('laborat', $kategori)){
        $sql_parts[] = "SELECT 'Laborat', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam), jpl.nm_perawatan, d.biaya_item, d.bagian_dokter FROM periksa_lab t JOIN detail_periksa_lab d ON t.no_rawat=d.no_rawat AND t.kd_jenis_prw=d.kd_jenis_prw AND t.tgl_periksa=d.tgl_periksa AND t.jam=d.jam JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_lab jpl ON t.kd_jenis_prw=jpl.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter=:dokter";
        $sql_parts[] = "SELECT 'Laborat (Perujuk)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, CONCAT(t.tgl_periksa, ' ', t.jam), CONCAT(jpl.nm_perawatan, ' (Perujuk)'), t.biaya, t.tarif_perujuk FROM periksa_lab t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_lab jpl ON t.kd_jenis_prw=jpl.kd_jenis_prw WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.dokter_perujuk=:dokter";
    }

    if(!empty($sql_parts)){
        $final_sql = implode(" UNION ALL ", $sql_parts) . " ORDER BY tgl_transaksi ASC";
        $stmt = $pdo->prepare($final_sql);
        $stmt->execute($params);
        $hasil = $stmt->fetchAll();
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>
<html>
<head>
<style>
    body { font-family: sans-serif; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #000; padding: 5px; font-size: 12px; }
    th { background-color: #eee; }
    .str { mso-number-format:"\@"; } /* Text format */
    .num { mso-number-format:"\#\,\#\#0"; } /* Number format */
</style>
</head>
<body>
    <h3>Audit Jasa Medis Dokter</h3>
    <p>Periode: <?= $tgl_awal ?> s/d <?= $tgl_akhir ?><br>Kode Dokter: <?= $kd_dokter ?></p>
    <table>
        <thead>
            <tr>
                <th>Waktu</th>
                <th>No. RM</th>
                <th>Pasien</th>
                <th>No. Rawat</th>
                <th>Tindakan</th>
                <th>Sumber</th>
                <th>Biaya Pasien</th>
                <th>JM Dokter</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $grand_biaya = 0; 
            $grand_jm = 0; 
            $processed = []; // Anti-Duplikasi Biaya Pasien

            foreach($hasil as $row): 
                // Generate ID Unik Transaksi (Rawat + Waktu + Tindakan Bersih)
                // Hapus suffix peran (Operator 1, Perujuk, dll) dari nama tindakan untuk cek duplikasi
                $tindakan_bersih = preg_replace('/\s\((Perujuk|Operator \d|Dokter .*|Anestesi|PJ Anak)\)$/', '', $row['tindakan']);
                $unique_key = $row['no_rawat'] . '_' . $row['tgl_transaksi'] . '_' . $tindakan_bersih;

                $add_cost = false;
                if(!in_array($unique_key, $processed)){
                    $grand_biaya += $row['total_biaya'];
                    $processed[] = $unique_key;
                    $add_cost = true;
                }
                $grand_jm += $row['jm_dokter'];
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
            <tr>
                <th colspan="6" align="right">TOTAL BERSIH (Tanpa Duplikasi Biaya):</th>
                <th class="num"><?= $grand_biaya ?></th>
                <th class="num"><?= $grand_jm ?></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>