<?php
/*
 * File: api/data_kunjungan_aktif_ssp.php (FIX V6 - SYNC LOGIC)
 * - Fix: Menambahkan Biaya Registrasi & Logika Kamar yang diperbaiki.
 * - Menyamakan 100% dengan logika audit_kunjungan_belum_closing.php.
 */

ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once(dirname(__DIR__) . '/config/koneksi.php'); 

function hitungEstimasiAkurat($conn, $no_rawat) {
    $total = 0;

    // 1. Registrasi (ALL)
    $q_reg = $conn->query("SELECT biaya_reg FROM reg_periksa WHERE no_rawat='$no_rawat'");
    if($q_reg && $r_reg = $q_reg->fetch_assoc()) $total += (float)$r_reg['biaya_reg'];

    // 2. Obat (Gross)
    $q1 = $conn->query("SELECT SUM(total) as val FROM detail_pemberian_obat WHERE no_rawat='$no_rawat'");
    if($q1 && $r1 = $q1->fetch_assoc()) $total += (float)$r1['val'];

    // 3. Retur Obat (Pengurang)
    $q_ret = $conn->query("SELECT SUM(h_retur * jml) as val FROM returpasien WHERE no_rawat='$no_rawat'");
    if($q_ret && $r_ret = $q_ret->fetch_assoc()) $total -= (float)$r_ret['val'];

    // 4. Tindakan & Penunjang
    $tables = ['rawat_jl_dr', 'rawat_jl_pr', 'rawat_jl_drpr', 'rawat_inap_dr', 'rawat_inap_pr', 'rawat_inap_drpr', 'periksa_lab', 'periksa_radiologi', 'penggunaan_darah_donor'];
    foreach($tables as $tbl) {
        $col = ($tbl == 'periksa_lab' || $tbl == 'periksa_radiologi' || $tbl == 'penggunaan_darah_donor') ? 'biaya' : 'biaya_rawat';
        $q = $conn->query("SELECT SUM($col) as val FROM $tbl WHERE no_rawat='$no_rawat'");
        if($q && $r = $q->fetch_assoc()) $total += (float)$r['val'];
    }

    // 5. Operasi
    $q_op = $conn->query("SELECT SUM(biayaoperator1+biayaoperator2+biayaoperator3+biayaasisten_operator1+biayaasisten_operator2+biayadokter_anestesi+biayaasisten_anestesi+biayaasisten_anestesi2+biayadokter_anak+biayaperawaat_resusitas+biayabidan+biayabidan2+biayabidan3+biayaperawat_luar+biayasewaok+biayaalat+akomodasi+bagian_rs+biaya_omloop+biaya_omloop2+biaya_omloop3+biaya_omloop4+biaya_omloop5+biayasarpras+biaya_dokter_pjanak+biaya_dokter_umum) as val FROM operasi WHERE no_rawat='$no_rawat'");
    if($q_op && $r_op = $q_op->fetch_assoc()) $total += (float)$r_op['val'];

    // 6. Kamar Inap (Fixed Logic)
    $q_kamar = $conn->query("
        SELECT k.trf_kamar, ki.tgl_masuk, ki.jam_masuk, ki.tgl_keluar, ki.jam_keluar, ki.stts_pulang
        FROM kamar_inap ki 
        INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar 
        WHERE ki.no_rawat='$no_rawat'
    ");
    if($q_kamar) {
        while($r_kamar = $q_kamar->fetch_assoc()) {
            $masuk = new DateTime($r_kamar['tgl_masuk'] . ' ' . $r_kamar['jam_masuk']);
            if ($r_kamar['stts_pulang'] != 'Pindah Kamar' && $r_kamar['tgl_keluar'] != '0000-00-00') {
                $keluar = new DateTime($r_kamar['tgl_keluar'] . ' ' . $r_kamar['jam_keluar']);
            } else {
                $keluar = new DateTime();
            }
            
            $hari = $keluar->diff($masuk)->days;
            // Jika 0 hari dan bukan pindah kamar, hitung 1
            if ($hari == 0 && $r_kamar['stts_pulang'] != 'Pindah Kamar') {
                $hari = 1;
            }
            
            $total += ($hari * (float)$r_kamar['trf_kamar']);
        }
    }
    
    // 7. Tambahan/Pengurangan
    $q_add = $conn->query("SELECT SUM(besar) as val FROM tambahan_biaya WHERE no_rawat='$no_rawat'");
    if($q_add && $r_add = $q_add->fetch_assoc()) $total += (float)$r_add['val'];
    
    $q_min = $conn->query("SELECT SUM(besar) as val FROM pengurangan_biaya WHERE no_rawat='$no_rawat'");
    if($q_min && $r_min = $q_min->fetch_assoc()) $total -= (float)$r_min['val'];

    return $total;
}

// --- PROSES DATA ---
$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$length = isset($_GET['length']) ? (int)$_GET['length'] : 10;
$search_value = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
$draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;

$sql_from = "
    FROM kamar_inap ki
    INNER JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
    INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar
    INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
    INNER JOIN penjab pj ON rp.kd_pj = pj.kd_pj
    WHERE (ki.stts_pulang = '-' OR ki.stts_pulang = '')
";

if (!empty($search_value)) {
    $sql_from .= " AND (
        p.nm_pasien LIKE '%$search_value%' 
        OR ki.no_rawat LIKE '%$search_value%' 
        OR b.nm_bangsal LIKE '%$search_value%'
        OR rp.no_rkm_medis LIKE '%$search_value%'
    ) ";
}

$q_cnt = $koneksi->query("SELECT COUNT(*) as total " . $sql_from);
$totalFiltered = ($q_cnt) ? $q_cnt->fetch_assoc()['total'] : 0;
$totalData = $totalFiltered;

$sql_data = "
    SELECT 
        ki.no_rawat, ki.tgl_masuk, ki.jam_masuk, 
        p.no_rkm_medis, p.nm_pasien, pj.png_jawab, 
        k.kd_kamar, b.nm_bangsal, k.kelas,
        DATEDIFF(NOW(), ki.tgl_masuk) as lama
    " . $sql_from . "
    ORDER BY ki.tgl_masuk DESC
    LIMIT $start, $length
";

$res_data = $koneksi->query($sql_data);
$data = [];

if ($res_data) {
    while ($row = $res_data->fetch_assoc()) {
        $total_biaya = hitungEstimasiAkurat($koneksi, $row['no_rawat']);
        $lama = ($row['lama'] < 1) ? 1 : $row['lama'];

        $data[] = [
            'waktu' => $row['tgl_masuk'] . ' ' . $row['jam_masuk'],
            'no_rawat' => $row['no_rawat'],
            'rm' => $row['no_rkm_medis'],
            'pasien' => $row['nm_pasien'],
            'kamar' => $row['nm_bangsal'] . ' (' . $row['kelas'] . ')',
            'penjamin' => $row['png_jawab'],
            'lama' => $lama . ' Hari',
            'estimasi' => 'Rp ' . number_format($total_biaya, 0, ',', '.')
        ];
    }
}

echo json_encode([
    "draw" => $draw,
    "recordsTotal" => $totalData,
    "recordsFiltered" => $totalFiltered,
    "data" => $data
]);

$koneksi->close();
?>