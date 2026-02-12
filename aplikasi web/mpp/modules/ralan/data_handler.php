<?php
/*
 * File: modules/ralan/data_handler.php
 * Deskripsi: Handler Ralan (Support Filter Rentang Tanggal)
 */

require_once '../../config/database.php';

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

// --- HELPER FUNCTIONS ---
function safeFloat($val) {
    if (is_null($val) || $val === '') return 0.0;
    return (float)$val;
}

function fetchOne($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return false; }
}

function fetchAll($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}

function formatTglSingkat($date) {
    if(empty($date) || $date == '0000-00-00') return '-';
    return date('d/m/y', strtotime($date));
}

// --- SETTINGS ---
$tampilkan_ppn_ralan = false;
$r_set = fetchOne($pdo, "SELECT tampilkan_ppnobat_ralan FROM set_nota LIMIT 1");
if($r_set && $r_set['tampilkan_ppnobat_ralan'] == 'Yes') $tampilkan_ppn_ralan = true;

// --- FILTER PARAMS ---
$draw   = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$start  = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

// FILTER TANGGAL (DEFAULT HARI INI)
$tgl_awal  = isset($_POST['tgl_awal']) ? $_POST['tgl_awal'] : date('Y-m-d');
$tgl_akhir = isset($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : date('Y-m-d');

$kd_poli = isset($_POST['kd_poli']) ? $_POST['kd_poli'] : '';
$stts    = isset($_POST['stts']) ? $_POST['stts'] : '';

// --- QUERY BUILDER ---
$where = " WHERE rp.status_lanjut = 'Ralan' ";
$params = [];

// 1. Filter Rentang Tanggal
$where .= " AND rp.tgl_registrasi BETWEEN ? AND ? ";
$params[] = $tgl_awal;
$params[] = $tgl_akhir;

// 2. Filter Poli
if (!empty($kd_poli)) {
    $where .= " AND rp.kd_poli = ? ";
    $params[] = $kd_poli;
}

// 3. Filter Status
if (!empty($stts)) {
    $where .= " AND rp.stts = ? ";
    $params[] = $stts;
}

// 4. Exclude Ranap
$where .= " AND NOT EXISTS (SELECT 1 FROM kamar_inap ki WHERE ki.no_rawat = rp.no_rawat) ";

// 5. Search Global
if (!empty($search)) {
    $where .= " AND (rp.no_rawat LIKE ? OR p.nm_pasien LIKE ? OR d.nm_dokter LIKE ?) ";
    $s_wild = "%$search%";
    $params[] = $s_wild; $params[] = $s_wild; $params[] = $s_wild;
}

// COUNT TOTAL
$sql_count = "SELECT COUNT(rp.no_rawat) as total 
              FROM reg_periksa rp
              JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
              LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
              $where";
$r_count = fetchOne($pdo, $sql_count, $params);
$total_records = ($r_count) ? $r_count['total'] : 0;

// GET DATA
$sql_data = "SELECT rp.no_rawat, rp.tgl_registrasi, rp.jam_reg, rp.stts, rp.status_bayar,
             p.nm_pasien, p.no_rkm_medis, 
             d.nm_dokter, poli.nm_poli,
             pj.png_jawab,
             rp.biaya_reg,
             (SELECT COUNT(*) FROM periksa_lab WHERE no_rawat = rp.no_rawat) as total_lab,
             (SELECT COUNT(*) FROM periksa_radiologi WHERE no_rawat = rp.no_rawat) as total_rad
             FROM reg_periksa rp 
             JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
             LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
             LEFT JOIN poliklinik poli ON rp.kd_poli = poli.kd_poli
             LEFT JOIN penjab pj ON rp.kd_pj = pj.kd_pj
             $where
             ORDER BY rp.tgl_registrasi DESC, rp.jam_reg DESC";

if ($length != -1) {
    $sql_data .= " LIMIT $start, $length ";
}

$raw_data = fetchAll($pdo, $sql_data, $params);

// --- PROCESS DATA ---
$data_json = [];

foreach ($raw_data as $r) {
    $no_rawat = $r['no_rawat'];
    $grand_total = 0.0;

    // Hitung Biaya Simple (Registrasi)
    if(safeFloat($r['biaya_reg']) > 0) $grand_total += safeFloat($r['biaya_reg']);

    // Tindakan
    $tind = fetchOne($pdo, "SELECT SUM(biaya_rawat) as total FROM (
        SELECT biaya_rawat FROM rawat_jl_dr WHERE no_rawat=?
        UNION ALL SELECT biaya_rawat FROM rawat_jl_pr WHERE no_rawat=?
        UNION ALL SELECT biaya_rawat FROM rawat_jl_drpr WHERE no_rawat=?
    ) as t", [$no_rawat, $no_rawat, $no_rawat]);
    $grand_total += safeFloat($tind['total'] ?? 0);

    // Penunjang
    $penunjang = fetchOne($pdo, "SELECT SUM(biaya) as total FROM (
        SELECT biaya FROM periksa_lab WHERE no_rawat=?
        UNION ALL SELECT biaya FROM periksa_radiologi WHERE no_rawat=?
    ) as p", [$no_rawat, $no_rawat]);
    $grand_total += safeFloat($penunjang['total'] ?? 0);

    // Obat & Retur
    $obat = fetchOne($pdo, "SELECT SUM(total) as val FROM detail_pemberian_obat WHERE no_rawat=?", [$no_rawat]);
    $retur = fetchOne($pdo, "SELECT SUM(r.jml * d.ralan) as val FROM returpasien r JOIN databarang d ON r.kode_brng = d.kode_brng WHERE r.no_rawat=?", [$no_rawat]);
    $obat_bersih = safeFloat($obat['val'] ?? 0) - safeFloat($retur['val'] ?? 0);
    $grand_total += $obat_bersih;

    if($tampilkan_ppn_ralan && $obat_bersih > 0) {
        $grand_total += ($obat_bersih * 0.11);
    }

    // UI Helpers
    $kategori_penjamin = 'Lain';
    $png = strtoupper($r['png_jawab']);
    if (strpos($png, 'BPJS') !== false) $kategori_penjamin = 'BPJS';
    elseif (strpos($png, 'UMUM') !== false) $kategori_penjamin = 'Umum';

    // Diagnosa
    $diag_awal = '-';
    $q_diag = fetchOne($pdo, "SELECT p.nm_penyakit FROM diagnosa_pasien dp JOIN penyakit p ON dp.kd_penyakit = p.kd_penyakit WHERE dp.no_rawat=? ORDER BY dp.prioritas ASC LIMIT 1", [$no_rawat]);
    if($q_diag) $diag_awal = $q_diag['nm_penyakit'];

    $data_json[] = [
        "waktu_reg" => formatTglSingkat($r['tgl_registrasi']) . ' ' . $r['jam_reg'],
        "no_rawat" => $r['no_rawat'],
        "no_rkm_medis" => $r['no_rkm_medis'],
        "nm_pasien" => $r['nm_pasien'],
        "nm_dokter" => $r['nm_dokter'],
        "nm_poli" => $r['nm_poli'],
        "penjamin" => $r['png_jawab'],
        "kategori_penjamin" => $kategori_penjamin,
        "diagnosa_awal" => $diag_awal,
        "count_lab" => $r['total_lab'],
        "count_rad" => $r['total_rad'],
        "total_biaya" => "Rp " . number_format($grand_total, 0, ',', '.'),
        "status_bayar" => $r['status_bayar']
    ];
}

echo json_encode([
    "draw" => $draw,
    "recordsTotal" => $total_records,
    "recordsFiltered" => $total_records,
    "data" => $data_json
]);
?>