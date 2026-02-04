<?php
// File: modules/ranap/data_handler.php
require_once '../../config/database.php';

// Matikan error display agar JSON valid
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

// --- 1. HELPER FUNCTIONS ---

function safeFloat($val) {
    if (is_null($val) || $val === '') return 0.0;
    return (float)$val;
}

function getSettings($pdo) {
    // Default: Tanpa service charge, tanpa PPN
    $settings = ['service_charge' => 0, 'ppn_obat' => false, 'components' => []];
    
    try {
        // Cek Setting Nota (PPN Obat)
        $stmt = $pdo->query("SELECT tampilkan_ppnobat_ranap FROM set_nota LIMIT 1");
        if ($r = $stmt->fetch()) $settings['ppn_obat'] = ($r['tampilkan_ppnobat_ranap'] == 'Yes');

        // Cek Service Charge Ranap
        $stmt = $pdo->query("SELECT * FROM set_service_ranap LIMIT 1");
        if ($r = $stmt->fetch()) {
            $settings['service_charge'] = safeFloat($r['besar']);
            // Mapping komponen yang kena service charge
            $keys = ['laborat', 'radiologi', 'operasi', 'obat', 'ranap_dokter', 'ranap_paramedis', 'ralan_dokter', 'ralan_paramedis', 'tambahan', 'potongan', 'kamar', 'registrasi', 'harian', 'retur_Obat', 'resep_Pulang'];
            foreach($keys as $k) $settings['components'][$k] = ($r[$k] == 'Yes');
        }
    } catch (Exception $e) {
        // Jika tabel setting tidak ada, biarkan default (0)
    }
    
    return $settings;
}

function hitungEstimasiAkurat($pdo, $no_rawat, $settings) {
    $biaya = [
        'laborat' => 0.0, 'radiologi' => 0.0, 'operasi' => 0.0, 'obat' => 0.0,
        'ranap_dokter' => 0.0, 'ranap_paramedis' => 0.0, 'ralan_dokter' => 0.0, 'ralan_paramedis' => 0.0,
        'tambahan' => 0.0, 'potongan' => 0.0, 'kamar' => 0.0, 'registrasi' => 0.0,
        'harian' => 0.0, 'retur_Obat' => 0.0, 'resep_Pulang' => 0.0
    ];

    // 1. Registrasi
    try {
        $stmt = $pdo->prepare("SELECT biaya_reg FROM reg_periksa WHERE no_rawat=?");
        $stmt->execute([$no_rawat]);
        if ($r = $stmt->fetch()) $biaya['registrasi'] += safeFloat($r['biaya_reg']);
    } catch (Exception $e) {}

    // 2. Obat (Detail + Tagihan Langsung)
    try {
        $stmt = $pdo->prepare("SELECT SUM(total) as val FROM detail_pemberian_obat WHERE no_rawat=?");
        $stmt->execute([$no_rawat]);
        if ($r = $stmt->fetch()) $biaya['obat'] += safeFloat($r['val']);
        
        // Cek tabel tagihan_obat_langsung (jika ada)
        $stmt = $pdo->prepare("SELECT SUM(besar_tagihan) as val FROM tagihan_obat_langsung WHERE no_rawat=?");
        $stmt->execute([$no_rawat]);
        if ($r = $stmt->fetch()) $biaya['obat'] += safeFloat($r['val']);
    } catch (Exception $e) {}

    // 3. Retur Obat
    try {
        $stmt = $pdo->prepare("SELECT SUM(r.jml * d.ralan) as val FROM returpasien r JOIN databarang d ON r.kode_brng = d.kode_brng WHERE r.no_rawat=?");
        $stmt->execute([$no_rawat]);
        if ($r = $stmt->fetch()) $biaya['retur_Obat'] += safeFloat($r['val']);
    } catch (Exception $e) {}

    // 4. Tindakan & Lab/Rad (Looping Tables)
    $tables = [
        'rawat_jl_dr'=>'ralan_dokter', 'rawat_jl_pr'=>'ralan_paramedis', 'rawat_jl_drpr'=>'ralan_dokter', 
        'rawat_inap_dr'=>'ranap_dokter', 'rawat_inap_pr'=>'ranap_paramedis', 'rawat_inap_drpr'=>'ranap_dokter',
        'periksa_lab'=>'laborat', 'periksa_radiologi'=>'radiologi', 'penggunaan_darah_donor'=>'obat'
    ];

    foreach($tables as $tbl => $cat) {
        $col = (strpos($tbl, 'periksa_') !== false || $tbl == 'penggunaan_darah_donor') ? 'biaya' : 'biaya_rawat';
        try {
            $stmt = $pdo->prepare("SELECT SUM($col) as val FROM $tbl WHERE no_rawat=?");
            $stmt->execute([$no_rawat]);
            if ($r = $stmt->fetch()) $biaya[$cat] += safeFloat($r['val']);
        } catch (Exception $e) { continue; }
    }

    // 5. Operasi
    try {
        $sql_op = "SELECT SUM(biayaoperator1+biayaoperator2+biayaoperator3+biayaasisten_operator1+biayaasisten_operator2+biayadokter_anestesi+biayaasisten_anestesi+biayaasisten_anestesi2+biayadokter_anak+biayaperawaat_resusitas+biayabidan+biayabidan2+biayabidan3+biayaperawat_luar+biayasewaok+biayaalat+akomodasi+bagian_rs+biaya_omloop+biaya_omloop2+biaya_omloop3+biaya_omloop4+biaya_omloop5+biayasarpras+biaya_dokter_pjanak+biaya_dokter_umum) as val FROM operasi WHERE no_rawat=?";
        $stmt = $pdo->prepare($sql_op);
        $stmt->execute([$no_rawat]);
        if ($r = $stmt->fetch()) $biaya['operasi'] += safeFloat($r['val']);
    } catch (Exception $e) {}

    // 6. Kamar Inap (Logic Penentu Angka Ngawur/Akurat)
    try {
        $q_kamar = "SELECT ki.kd_kamar, k.trf_kamar, ki.tgl_masuk, ki.jam_masuk, ki.tgl_keluar, ki.jam_keluar, ki.stts_pulang, ki.lama, ki.ttl_biaya FROM kamar_inap ki INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar WHERE ki.no_rawat=?";
        $stmt = $pdo->prepare($q_kamar);
        $stmt->execute([$no_rawat]);
        $rows_kamar = $stmt->fetchAll();

        foreach($rows_kamar as $r_kamar) {
            $hari = 0;
            // Jika sudah ada total biaya tersimpan (pasien pulang/closing), pakai itu
            if (safeFloat($r_kamar['ttl_biaya']) > 0) {
                $biaya['kamar'] += safeFloat($r_kamar['ttl_biaya']);
            } else {
                // Jika masih aktif, hitung manual
                $tgl_keluar = ($r_kamar['tgl_keluar'] != '0000-00-00') ? $r_kamar['tgl_keluar'] : date('Y-m-d');
                $jam_keluar = ($r_kamar['jam_keluar'] == '00:00:00') ? date('H:i:s') : $r_kamar['jam_keluar'];
                
                try {
                    $ts1 = strtotime($r_kamar['tgl_masuk'] . ' ' . $r_kamar['jam_masuk']);
                    $ts2 = strtotime($tgl_keluar . ' ' . $jam_keluar);
                    $hari = floor(($ts2 - $ts1) / (60 * 60 * 24));
                } catch (Exception $e) { $hari = 0; }
                
                // Jika 0 hari (masuk hari ini), Khanza biasanya hitung 1 hari atau 0 tergantung setting. 
                // Kita set minimal 1 jika setting mengharuskan, tapi amannya ikuti logic referensi:
                if ($hari <= 0) $hari = 1; 
                
                $biaya['kamar'] += ($hari * safeFloat($r_kamar['trf_kamar']));
            }

            // Biaya Harian & Sekali
            try {
                $s_h = $pdo->prepare("SELECT besar_biaya FROM biaya_harian WHERE kd_kamar=?");
                $s_h->execute([$r_kamar['kd_kamar']]);
                while($rh = $s_h->fetch()) $biaya['harian'] += ($hari > 0 ? $hari * safeFloat($rh['besar_biaya']) : 0);
            } catch(Exception $e) {}

            try {
                $s_s = $pdo->prepare("SELECT besar_biaya FROM biaya_sekali WHERE kd_kamar=?");
                $s_s->execute([$r_kamar['kd_kamar']]);
                while($rs = $s_s->fetch()) $biaya['kamar'] += safeFloat($rs['besar_biaya']);
            } catch(Exception $e) {}
        }
    } catch (Exception $e) {}

    // 7. Tambahan & Pengurangan
    try {
        $stmt = $pdo->prepare("SELECT SUM(besar_biaya) as val FROM tambahan_biaya WHERE no_rawat=?");
        $stmt->execute([$no_rawat]);
        if ($r = $stmt->fetch()) $biaya['tambahan'] += safeFloat($r['val']);
        
        $stmt = $pdo->prepare("SELECT SUM(besar_pengurangan) as val FROM pengurangan_biaya WHERE no_rawat=?");
        $stmt->execute([$no_rawat]);
        if ($r = $stmt->fetch()) $biaya['potongan'] += safeFloat($r['val']);
    } catch (Exception $e) {}

    // 8. Final Calculation (Service & PPN)
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

// --- 2. MAIN DATATABLES LOGIC ---

$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

// Filter Parameters
$tgl_awal = isset($_POST['tgl_awal']) ? $_POST['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : date('Y-m-d');
$status_pulang = isset($_POST['status_pulang']) ? $_POST['status_pulang'] : 'Masih Dirawat';

// Ambil Setting
$settings = getSettings($pdo);

// QUERY UTAMA (Tanpa Group By agar sesuai reference)
// Menambahkan jam_masuk!
$sql = "
SELECT 
    reg_periksa.no_rawat,
    reg_periksa.no_rkm_medis,
    pasien.nm_pasien,
    dokter.nm_dokter,
    penjab.png_jawab,
    bangsal.nm_bangsal,
    kamar_inap.tgl_masuk,
    kamar_inap.jam_masuk, 
    kamar_inap.stts_pulang,
    kamar_inap.diagnosa_awal,
    kamar_inap.diagnosa_akhir,
    (SELECT COUNT(*) FROM periksa_lab WHERE periksa_lab.no_rawat = reg_periksa.no_rawat) as total_lab,
    (SELECT COUNT(*) FROM periksa_radiologi WHERE periksa_radiologi.no_rawat = reg_periksa.no_rawat) as total_rad
FROM kamar_inap
JOIN reg_periksa ON kamar_inap.no_rawat = reg_periksa.no_rawat
JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
JOIN kamar ON kamar_inap.kd_kamar = kamar.kd_kamar
JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal
WHERE 1=1 
";

$params = [];

// Filter Tanggal (Wajib)
$sql .= " AND kamar_inap.tgl_masuk BETWEEN ? AND ? ";
$params[] = $tgl_awal;
$params[] = $tgl_akhir;

// Filter Status
if ($status_pulang == 'Masih Dirawat') {
    // Menampilkan pasien yg belum pulang ATAU pindah kamar (masih di RS)
    // Logika asli: stts_pulang = '-' atau kosong
    $sql .= " AND (kamar_inap.stts_pulang = '-' OR kamar_inap.stts_pulang = '') ";
} elseif ($status_pulang == 'Sudah Pulang') {
    $sql .= " AND kamar_inap.stts_pulang != '-' AND kamar_inap.stts_pulang != '' ";
}

// Filter Pencarian
if (!empty($searchValue)) {
    $sql .= " AND (
        reg_periksa.no_rawat LIKE ? OR 
        pasien.nm_pasien LIKE ? OR 
        reg_periksa.no_rkm_medis LIKE ?
    ) ";
    $val = "%$searchValue%";
    $params[] = $val; $params[] = $val; $params[] = $val;
}

// Order By
$sql .= " ORDER BY kamar_inap.tgl_masuk DESC, kamar_inap.jam_masuk DESC LIMIT $start, $length ";

// Hitung Total (Count)
$countSql = "SELECT COUNT(*) FROM kamar_inap JOIN reg_periksa ON kamar_inap.no_rawat = reg_periksa.no_rawat WHERE kamar_inap.tgl_masuk BETWEEN ? AND ?";
try {
    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute([$tgl_awal, $tgl_akhir]);
    $totalRecordsReal = $stmtCount->fetchColumn();
} catch(Exception $e) { $totalRecordsReal = 0; }

// Eksekusi Data
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
} catch (PDOException $e) {
    echo json_encode(["error" => "SQL Error: " . $e->getMessage()]);
    exit;
}

// Format Output
$output = [];
foreach ($data as $row) {
    // Hitung Estimasi
    $estimasi_biaya = hitungEstimasiAkurat($pdo, $row['no_rawat'], $settings);

    // Hitung Lama Rawat (PHP Side)
    $masuk = new DateTime($row['tgl_masuk'] . ' ' . $row['jam_masuk']);
    $sekarang = new DateTime(); // NOW
    $diff = $masuk->diff($sekarang);
    $hari = $diff->days;
    if ($hari < 1) $hari = 1; // Minimal 1 hari

    $output[] = [
        "waktu_masuk" => date('d/m/Y H:i', strtotime($row['tgl_masuk'] . ' ' . $row['jam_masuk'])),
        "no_rawat" => $row['no_rawat'],
        "no_rkm_medis" => $row['no_rkm_medis'],
        "nm_pasien" => $row['nm_pasien'],
        "kamar" => $row['nm_bangsal'],
        "hari_rawat" => $hari . " Hari",
        "dokter" => $row['nm_dokter'],
        "penjamin" => $row['png_jawab'],
        "diagnosa_awal" => $row['diagnosa_awal'],
        "diagnosa_akhir" => $row['diagnosa_akhir'],
        "total_biaya" => "Rp " . number_format($estimasi_biaya, 0, ',', '.'),
        "count_lab" => $row['total_lab'],
        "count_rad" => $row['total_rad']
    ];
}

echo json_encode([
    "draw" => $draw,
    "recordsTotal" => $totalRecordsReal,
    "recordsFiltered" => $totalRecordsReal,
    "data" => $output
]);
?>