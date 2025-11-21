<?php
/*
 * File: api/data_kunjungan_ralan.php (FIX V26 - HYBRID 7.3 & 8.3 COMPATIBLE)
 * - Fix: mysqli_report(OFF) agar error SQL tidak bikin Crash di PHP 8.1+.
 * - Fix: JSON_INVALID_UTF8_IGNORE agar karakter aneh tidak merusak JSON.
 * - Fix: Helper textToUtf8 & safeFloat untuk keamanan tipe data.
 */

ob_start();
ini_set('display_errors', 0);
error_reporting(0);
set_time_limit(0);

// PENTING: Matikan Exception MySQLi untuk PHP 8.1+ (Kembali ke perilaku PHP 7)
mysqli_report(MYSQLI_REPORT_OFF);

header('Content-Type: application/json; charset=utf-8');
require_once(dirname(__DIR__) . '/config/koneksi.php'); 

$koneksi->query("SET sql_mode = ''");

// --- HELPER FUNCTIONS ---
function textToUtf8($str) {
    if (is_null($str)) return "";
    $str = (string)$str;
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
    }
    return @utf8_encode($str);
}

function safeFloat($val) {
    if (is_null($val) || $val === '') return 0.0;
    return (float)$val;
}

function getSettings($conn) {
    $settings = ['service_charge' => 0, 'ppn_obat' => false, 'components' => []];
    
    $q = $conn->query("SELECT tampilkan_ppnobat_ralan FROM set_nota LIMIT 1");
    if($q && $r = $q->fetch_assoc()) $settings['ppn_obat'] = ($r['tampilkan_ppnobat_ralan'] == 'Yes');

    $q = $conn->query("SELECT * FROM set_service_ranap LIMIT 1"); 
    if($q && $r = $q->fetch_assoc()) {
        $settings['service_charge'] = safeFloat($r['besar']);
        $keys = ['laborat', 'radiologi', 'operasi', 'obat', 'ranap_dokter', 'ranap_paramedis', 'ralan_dokter', 'ralan_paramedis', 'tambahan', 'potongan', 'kamar', 'registrasi', 'harian', 'retur_Obat', 'resep_Pulang'];
        foreach($keys as $k) $settings['components'][$k] = ($r[$k] == 'Yes');
    }
    return $settings;
}

function hitungEstimasiAkurat($conn, $no_rawat, $settings) {
    $biaya = [
        'laborat' => 0.0, 'radiologi' => 0.0, 'operasi' => 0.0, 'obat' => 0.0,
        'ranap_dokter' => 0.0, 'ranap_paramedis' => 0.0, 'ralan_dokter' => 0.0, 'ralan_paramedis' => 0.0,
        'tambahan' => 0.0, 'potongan' => 0.0, 'kamar' => 0.0, 'registrasi' => 0.0,
        'harian' => 0.0, 'retur_Obat' => 0.0, 'resep_Pulang' => 0.0
    ];

    $q = $conn->query("SELECT biaya_reg FROM reg_periksa WHERE no_rawat='$no_rawat'");
    if($q && $r = $q->fetch_assoc()) $biaya['registrasi'] += safeFloat($r['biaya_reg']);

    $q = $conn->query("SELECT SUM(total) as val FROM detail_pemberian_obat WHERE no_rawat='$no_rawat'");
    if($q && $r = $q->fetch_assoc()) $biaya['obat'] += safeFloat($r['val']);
    
    $q = $conn->query("SELECT SUM(besar_tagihan) as val FROM tagihan_obat_langsung WHERE no_rawat='$no_rawat'");
    if($q && $r = $q->fetch_assoc()) $biaya['obat'] += safeFloat($r['val']);

    $q = $conn->query("SELECT SUM(r.jml * d.ralan) as val FROM returpasien r JOIN databarang d ON r.kode_brng = d.kode_brng WHERE r.no_rawat='$no_rawat'");
    if($q && $r = $q->fetch_assoc()) $biaya['retur_Obat'] += safeFloat($r['val']);

    $tables = [
        'rawat_jl_dr'=>'ralan_dokter', 'rawat_jl_pr'=>'ralan_paramedis', 'rawat_jl_drpr'=>'ralan_dokter', 
        'periksa_lab'=>'laborat', 'periksa_radiologi'=>'radiologi', 'penggunaan_darah_donor'=>'obat'
    ];
    foreach($tables as $tbl => $cat) {
        $col = (strpos($tbl, 'periksa_') !== false || $tbl == 'penggunaan_darah_donor') ? 'biaya' : 'biaya_rawat';
        $q = $conn->query("SELECT SUM($col) as val FROM $tbl WHERE no_rawat='$no_rawat'");
        if($q && $r = $q->fetch_assoc()) $biaya[$cat] += safeFloat($r['val']);
    }

    $sql_op = "SELECT SUM(biayaoperator1+biayaoperator2+biayaoperator3+biayaasisten_operator1+biayaasisten_operator2+biayadokter_anestesi+biayaasisten_anestesi+biayaasisten_anestesi2+biayadokter_anak+biayaperawaat_resusitas+biayabidan+biayabidan2+biayabidan3+biayaperawat_luar+biayasewaok+biayaalat+akomodasi+bagian_rs+biaya_omloop+biaya_omloop2+biaya_omloop3+biaya_omloop4+biaya_omloop5+biayasarpras+biaya_dokter_pjanak+biaya_dokter_umum) as val FROM operasi WHERE no_rawat='$no_rawat'";
    $q = $conn->query($sql_op);
    if($q && $r = $q->fetch_assoc()) $biaya['operasi'] += safeFloat($r['val']);

    $q = $conn->query("SELECT SUM(besar_biaya) as val FROM tambahan_biaya WHERE no_rawat='$no_rawat'");
    if($q && $r = $q->fetch_assoc()) $biaya['tambahan'] += safeFloat($r['val']);
    
    $q = $conn->query("SELECT SUM(besar_pengurangan) as val FROM pengurangan_biaya WHERE no_rawat='$no_rawat'");
    if($q && $r = $q->fetch_assoc()) $biaya['potongan'] += safeFloat($r['val']);

    $obat_bersih = $biaya['obat'] - $biaya['retur_Obat'];
    $ppn_rp = ($settings['ppn_obat'] && $obat_bersih > 0) ? $obat_bersih * 0.11 : 0;

    $service_base = 0;
    foreach ($settings['components'] as $key => $isActive) {
        if ($isActive && isset($biaya[$key])) {
            $service_base += ($key == 'retur_Obat') ? -($biaya[$key]) : $biaya[$key];
        }
    }
    $service_rp = ($service_base * $settings['service_charge']) / 100;

    return array_sum($biaya) - ($biaya['retur_Obat'] * 2) - ($biaya['potongan'] * 2) + $ppn_rp + $service_rp;
}

function hitungObatSaja($conn, $no_rawat) {
    $total_obat = 0.0;
    $q = $conn->query("SELECT SUM(total) as val FROM detail_pemberian_obat WHERE no_rawat='$no_rawat'");
    if($q && $r = $q->fetch_assoc()) $total_obat += safeFloat($r['val']);

    $q = $conn->query("SELECT SUM(besar_tagihan) as val FROM tagihan_obat_langsung WHERE no_rawat='$no_rawat'");
    if($q && $r = $q->fetch_assoc()) $total_obat += safeFloat($r['val']);

    $q = $conn->query("SELECT SUM(r.jml * d.ralan) as val FROM returpasien r JOIN databarang d ON r.kode_brng = d.kode_brng WHERE r.no_rawat='$no_rawat'");
    if($q && $r = $q->fetch_assoc()) $total_obat -= safeFloat($r['val']);

    return $total_obat;
}

$settings = getSettings($koneksi);
$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$length = isset($_GET['length']) ? (int)$_GET['length'] : 10;
$search_value = isset($_GET['search']['value']) ? $koneksi->real_escape_string($_GET['search']['value']) : '';
$draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;

$filter_date = " AND rp.tgl_registrasi = CURRENT_DATE() ";
if(!empty($search_value)) {
    $filter_date = ""; 
}

$sql_from = "
    FROM reg_periksa rp
    INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    INNER JOIN dokter d ON rp.kd_dokter = d.kd_dokter
    INNER JOIN poliklinik pl ON rp.kd_poli = pl.kd_poli
    INNER JOIN penjab pj ON rp.kd_pj = pj.kd_pj
    WHERE rp.status_lanjut = 'Ralan' 
    AND rp.status_bayar = 'Belum Bayar' 
    $filter_date
";

if (!empty($search_value)) {
    $sql_from .= " AND (
        p.nm_pasien LIKE '%$search_value%' 
        OR rp.no_rawat LIKE '%$search_value%' 
        OR rp.no_rkm_medis LIKE '%$search_value%'
        OR pl.nm_poli LIKE '%$search_value%'
    ) ";
}

$q_cnt = $koneksi->query("SELECT COUNT(*) as total " . $sql_from);
if(!$q_cnt) {
    ob_end_clean();
    die(json_encode(["error" => "SQL Count Error: " . $koneksi->error]));
}
$totalFiltered = $q_cnt->fetch_assoc()['total'];
$totalData = $totalFiltered;

$sql_limit = "";
if ($length != -1) {
    $sql_limit = "LIMIT $start, $length";
}

$sql_data = "
    SELECT 
        rp.no_rawat, rp.tgl_registrasi, rp.jam_reg, 
        p.no_rkm_medis, p.nm_pasien, 
        d.nm_dokter, pl.nm_poli, 
        pj.png_jawab, rp.stts
    " . $sql_from . "
    ORDER BY rp.tgl_registrasi DESC, rp.jam_reg DESC
    $sql_limit 
";

$res_data = $koneksi->query($sql_data);
if(!$res_data) {
    ob_end_clean();
    die(json_encode(["error" => "SQL Data Error: " . $koneksi->error]));
}

$raw_data = [];

while ($row = $res_data->fetch_assoc()) {
    $total_biaya = hitungEstimasiAkurat($koneksi, $row['no_rawat'], $settings);
    $biaya_obat = hitungObatSaja($koneksi, $row['no_rawat']);
    
    $is_anomali = ($row['stts'] == 'Batal' && $total_biaya > 0);

    $raw_data[] = [
        'waktu' => $row['tgl_registrasi'] . ' ' . $row['jam_reg'],
        'no_rawat' => $row['no_rawat'],
        'rm' => $row['no_rkm_medis'],
        'pasien' => textToUtf8($row['nm_pasien']),
        'poli' => textToUtf8($row['nm_poli']),
        'dokter' => textToUtf8($row['nm_dokter']),
        'penjamin' => textToUtf8($row['png_jawab']),
        'status' => $row['stts'],
        'is_anomali' => $is_anomali,
        'biaya_obat_raw' => $biaya_obat,
        'biaya_obat' => 'Rp ' . number_format((float)$biaya_obat, 0, ',', '.'), 
        'estimasi_raw' => $total_biaya, 
        'estimasi' => 'Rp ' . number_format((float)$total_biaya, 0, ',', '.')
    ];
}

ob_end_clean();
// Gunakan opsi JSON_INVALID_UTF8_IGNORE untuk mencegah error karakter aneh
echo json_encode([
    "draw" => $draw,
    "recordsTotal" => (int)$totalData,
    "recordsFiltered" => (int)$totalFiltered,
    "data" => $raw_data
], JSON_INVALID_UTF8_IGNORE);

$koneksi->close();
?>