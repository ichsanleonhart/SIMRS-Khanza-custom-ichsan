<?php
/*
 * File: modules/ranap/data_handler.php
 * Deskripsi: Handler DataTables Ranap (LOGIKA HITUNG 100% COPY DARI VIEW_BILLING.PHP)
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

function formatTglSingkat($datetime) {
    if(empty($datetime)) return '-';
    $ts = strtotime($datetime);
    return date('d/m/y H:i', $ts);
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

// --- FUNGSI HITUNG (REPLIKASI 100% VIEW_BILLING.PHP) ---
function hitungEstimasiAkurat($pdo, $no_rawat) {
    // 1. Ambil Setting Jam Minimal
    $setting_kamar = ['hariawal' => 'no', 'lamajam' => 0]; 
    $r_jam = fetchOne($pdo, "SELECT hariawal, lamajam FROM set_jam_minimal LIMIT 1");
    if($r_jam) $setting_kamar = $r_jam;

    // 2. Info Pasien (Utk Status Lanjut & PJ)
    $pasien = fetchOne($pdo, "SELECT rp.status_lanjut, rp.kd_pj FROM reg_periksa rp WHERE rp.no_rawat = ? LIMIT 1", [$no_rawat]);
    $status_lanjut = $pasien['status_lanjut'] ?? 'Ranap';
    $kd_pj = $pasien['kd_pj'] ?? '-';

    // 3. Setting PPN
    $pakai_ppn = false;
    $r_set = fetchOne($pdo, "SELECT tampilkan_ppnobat_ralan, tampilkan_ppnobat_ranap FROM set_nota LIMIT 1");
    if($r_set) {
        if($status_lanjut == 'Ralan' && $r_set['tampilkan_ppnobat_ralan'] == 'Yes') $pakai_ppn = true;
        else if($status_lanjut == 'Ranap' && $r_set['tampilkan_ppnobat_ranap'] == 'Yes') $pakai_ppn = true;
    }

    // --- VARIABEL AKUMULATOR ---
    $grand_total = 0;
    
    // Variabel untuk Service Charge Basis
    $sum_kamar = 0; $sum_reg = 0; 
    $sum_dr_ralan = 0; $sum_pr_ralan = 0;
    $sum_dr_ranap = 0; $sum_pr_ranap = 0;
    $sum_lab = 0; $sum_rad = 0; $sum_op = 0; $sum_obat = 0; 
    $sum_retur = 0; $sum_tambah = 0; $sum_potong = 0; $sum_harian = 0;

    // A. REGISTRASI
    $reg = fetchOne($pdo, "SELECT biaya_reg FROM reg_periksa WHERE no_rawat = ?", [$no_rawat]);
    if ($reg) {
        $val = safeFloat($reg['biaya_reg']);
        $grand_total += $val;
        $sum_reg += $val;
    }

    // B. KAMAR INAP (CORE LOGIC FIX)
    $kamars = fetchAll($pdo, "SELECT k.kd_kamar, k.trf_kamar, ki.tgl_masuk, ki.jam_masuk, ki.tgl_keluar, ki.jam_keluar, ki.lama, ki.ttl_biaya FROM kamar_inap ki JOIN kamar k ON ki.kd_kamar = k.kd_kamar WHERE ki.no_rawat = ?", [$no_rawat]);
    foreach($kamars as $r) {
        $tgl_masuk = $r['tgl_masuk'];
        $tgl_keluar = ($r['tgl_keluar'] != '0000-00-00') ? $r['tgl_keluar'] : date('Y-m-d');
        
        $d1 = new DateTime($tgl_masuk);
        $d2 = new DateTime($tgl_keluar);
        $hari_raw = $d2->diff($d1)->days;

        // Logic hari persis view_billing
        $hari = ($setting_kamar['hariawal'] == 'yes') ? $hari_raw + 1 : $hari_raw;
        if (safeFloat($r['ttl_biaya']) > 0 && safeFloat($r['lama']) > 0) $hari = safeFloat($r['lama']);

        $biaya_kamar = $hari * safeFloat($r['trf_kamar']);
        
        // HANYA Tambah jika > 0 (Jangan dipaksa minimal 1 seperti kode sebelumnya)
        if ($biaya_kamar > 0 || $hari > 0) {
            $grand_total += $biaya_kamar;
            $sum_kamar += $biaya_kamar;
        }

        // Biaya Sekali & Harian
        $kd = $r['kd_kamar'];
        $biaya_sekali = fetchAll($pdo, "SELECT besar_biaya FROM biaya_sekali WHERE kd_kamar=?", [$kd]);
        foreach($biaya_sekali as $bs) {
            $val = safeFloat($bs['besar_biaya']);
            $grand_total += $val;
            $sum_harian += $val;
        }

        $biaya_harian = fetchAll($pdo, "SELECT besar_biaya FROM biaya_harian WHERE kd_kamar=?", [$kd]);
        foreach($biaya_harian as $bh) {
            $val = $hari * safeFloat($bh['besar_biaya']);
            $grand_total += $val;
            $sum_harian += $val;
        }
    }

    // C. OBAT & BHP
    // 1. Tagihan Langsung
    $ol = fetchOne($pdo, "SELECT besar_tagihan FROM tagihan_obat_langsung WHERE no_rawat=?", [$no_rawat]);
    if ($ol) {
        $val = safeFloat($ol['besar_tagihan']);
        $grand_total += $val;
        $sum_obat += $val;
    }
    // 2. Beri Obat Operasi
    $oop = fetchAll($pdo, "SELECT (hargasatuan * jumlah) as total FROM beri_obat_operasi WHERE no_rawat=?", [$no_rawat]);
    foreach($oop as $r) {
        $val = safeFloat($r['total']);
        $grand_total += $val;
        $sum_obat += $val;
    }
    // 3. Detail Pemberian Obat
    $dpo = fetchAll($pdo, "SELECT total FROM detail_pemberian_obat WHERE no_rawat=?", [$no_rawat]);
    foreach($dpo as $r) {
        $val = safeFloat($r['total']);
        $grand_total += $val;
        $sum_obat += $val;
    }
    // Retur Obat
    $returs = fetchAll($pdo, "SELECT (r.jml * d.ralan) as total_estimasi FROM returpasien r JOIN databarang d ON r.kode_brng = d.kode_brng WHERE r.no_rawat=?", [$no_rawat]);
    foreach($returs as $r) {
        $val = safeFloat($r['total_estimasi']);
        $grand_total -= abs($val);
        $sum_retur += abs($val);
    }
    // PPN
    if ($pakai_ppn) {
        $obat_bersih = $sum_obat - $sum_retur;
        if ($obat_bersih > 0) {
            $ppn = round($obat_bersih * 0.11);
            $grand_total += $ppn;
        }
    }

    // D. TINDAKAN (UNION)
    $sql_tind = "
        SELECT 'Ralan Dokter' as kat, t.biaya_rawat as total FROM rawat_jl_dr t WHERE t.no_rawat=?
        UNION ALL SELECT 'Ralan Paramedis', t.biaya_rawat FROM rawat_jl_pr t WHERE t.no_rawat=?
        UNION ALL SELECT 'Ralan Dr+Pr', t.biaya_rawat FROM rawat_jl_drpr t WHERE t.no_rawat=?
        UNION ALL SELECT 'Ranap Dokter', t.biaya_rawat FROM rawat_inap_dr t WHERE t.no_rawat=?
        UNION ALL SELECT 'Ranap Paramedis', t.biaya_rawat FROM rawat_inap_pr t WHERE t.no_rawat=?
        UNION ALL SELECT 'Ranap Dr+Pr', t.biaya_rawat FROM rawat_inap_drpr t WHERE t.no_rawat=?
        UNION ALL SELECT 'Laboratorium', t.biaya FROM periksa_lab t WHERE t.no_rawat=?
        UNION ALL SELECT 'Radiologi', t.biaya FROM periksa_radiologi t WHERE t.no_rawat=?
    ";
    $params_tind = array_fill(0, 8, $no_rawat);
    $tindakan = fetchAll($pdo, $sql_tind, $params_tind);

    foreach($tindakan as $r) {
        $val = safeFloat($r['total']);
        $grand_total += $val;

        $kat = strtolower($r['kat']);
        if (strpos($kat, 'lab') !== false) $sum_lab += $val;
        else if (strpos($kat, 'radiologi') !== false) $sum_rad += $val;
        else if (strpos($kat, 'ralan') !== false) {
            if (strpos($kat, 'dokter') !== false) $sum_dr_ralan += $val;
            else if (strpos($kat, 'paramedis') !== false) $sum_pr_ralan += $val;
            else $sum_dr_ralan += $val; 
        }
        else if (strpos($kat, 'ranap') !== false) {
            if (strpos($kat, 'dokter') !== false) $sum_dr_ranap += $val;
            else if (strpos($kat, 'paramedis') !== false) $sum_pr_ranap += $val;
            else $sum_dr_ranap += $val; 
        }
    }

    // E. OPERASI
    $ops = fetchAll($pdo, "SELECT * FROM operasi WHERE no_rawat=?", [$no_rawat]);
    foreach($ops as $r) {
        $komponen = ['biayaoperator1','biayaoperator2','biayaoperator3','biayaasisten_operator1','biayaasisten_operator2','biayadokter_anestesi','biayaasisten_anestesi','biayasewaok','biayaalat','akomodasi','bagian_rs','biaya_omloop','biayasarpras','biaya_dokter_anak','biayaperawaat_resusitas','biayabidan'];
        foreach($komponen as $k) {
            if (safeFloat($r[$k]) > 0) {
                $val = safeFloat($r[$k]);
                $grand_total += $val;
                $sum_op += $val;
            }
        }
    }

    // F. TAMBAHAN & POTONGAN
    $adds = fetchAll($pdo, "SELECT besar_biaya FROM tambahan_biaya WHERE no_rawat=?", [$no_rawat]);
    foreach($adds as $r) { $val = safeFloat($r['besar_biaya']); $grand_total += $val; $sum_tambah += $val; }

    $mins = fetchAll($pdo, "SELECT besar_pengurangan FROM pengurangan_biaya WHERE no_rawat=?", [$no_rawat]);
    foreach($mins as $r) { $val = safeFloat($r['besar_pengurangan']); $grand_total -= abs($val); $sum_potong += abs($val); }

    // G. JASA ADMINISTRASI
    if ($status_lanjut == 'Ranap') {
        $tabel_service = ($kd_pj != '-' && $kd_pj != 'UMUM' && $kd_pj != 'A01') ? 'set_service_ranap_piutang' : 'set_service_ranap';
        $s = fetchOne($pdo, "SELECT * FROM $tabel_service LIMIT 1");
        
        if ($s) {
            $total_basis = 0;
            if($s['laborat'] == 'Yes') $total_basis += $sum_lab;
            if($s['radiologi'] == 'Yes') $total_basis += $sum_rad;
            if($s['operasi'] == 'Yes') $total_basis += $sum_op;
            if($s['obat'] == 'Yes') $total_basis += ($sum_obat - $sum_retur);
            
            if($s['ranap_dokter'] == 'Yes') $total_basis += $sum_dr_ranap;
            if($s['ranap_paramedis'] == 'Yes') $total_basis += $sum_pr_ranap;
            if($s['ralan_dokter'] == 'Yes') $total_basis += $sum_dr_ralan;
            if($s['ralan_paramedis'] == 'Yes') $total_basis += $sum_pr_ralan;
            
            if($s['tambahan'] == 'Yes') $total_basis += $sum_tambah;
            if($s['potongan'] == 'Yes') $total_basis += $sum_potong; 
            if($s['kamar'] == 'Yes') $total_basis += $sum_kamar;
            if($s['registrasi'] == 'Yes') $total_basis += $sum_reg;
            if($s['harian'] == 'Yes') $total_basis += $sum_harian;

            $persen = safeFloat($s['besar']);
            if ($total_basis > 0 && $persen > 0) {
                $biaya_jasa = round($total_basis * ($persen / 100));
                
                // Cek Double di Billing (Koreksi dari user: pake yg real kalau ada)
                $cek = fetchOne($pdo, "SELECT totalbiaya FROM billing WHERE no_rawat=? AND (nm_perawatan LIKE '%Administrasi%' OR nm_perawatan LIKE '%Service%')", [$no_rawat]);
                if (!$cek) {
                    $grand_total += $biaya_jasa;
                } else {
                    $grand_total += safeFloat($cek['totalbiaya']);
                }
            }
        }
    }

    return $grand_total;
}

// --- 2. MAIN LOGIC (SEARCH & FILTER) ---

$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

$tgl1   = isset($_POST['tgl_awal']) ? $_POST['tgl_awal'] : date('Y-m-01');
$tgl2   = isset($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : date('Y-m-d');
$status_pulang = isset($_POST['status_pulang']) ? $_POST['status_pulang'] : 'Masih Dirawat';
$filter_by     = isset($_POST['filter_by']) ? $_POST['filter_by'] : 'masuk';

// QUERY BUILDER
$where = " WHERE 1=1 ";
$params = [];

// Filter Tanggal
if ($filter_by == 'masuk') {
    $where .= " AND ki.tgl_masuk BETWEEN ? AND ? ";
} else {
    $where .= " AND ki.tgl_keluar BETWEEN ? AND ? ";
}
$params[] = $tgl1;
$params[] = $tgl2;

// Filter Status
if ($status_pulang == 'Masih Dirawat') {
    $where .= " AND (ki.stts_pulang = '-' OR ki.stts_pulang = '') ";
} else {
    $where .= " AND ki.stts_pulang != '-' AND ki.stts_pulang != '' ";
}

// Search
if (!empty($search)) {
    $where .= " AND (
        ki.no_rawat LIKE ? 
        OR p.nm_pasien LIKE ? 
        OR d.nm_dokter LIKE ? 
        OR b.nm_bangsal LIKE ? 
        OR EXISTS (SELECT 1 FROM dpjp_ranap dr_search JOIN dokter d2_search ON dr_search.kd_dokter = d2_search.kd_dokter WHERE dr_search.no_rawat = ki.no_rawat AND d2_search.nm_dokter LIKE ?)
    ) ";
    $s_wild = "%$search%";
    $params[] = $s_wild; $params[] = $s_wild; $params[] = $s_wild; $params[] = $s_wild; $params[] = $s_wild;
}

// Order By
$order_by = ($filter_by == 'masuk') 
    ? "ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC" 
    : "ORDER BY ki.tgl_keluar DESC, ki.jam_keluar DESC";

// Get Total
$sql_count = "SELECT COUNT(DISTINCT ki.no_rawat) as total 
              FROM kamar_inap ki 
              JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
              JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
              LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
              LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
              LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
              $where";
$r_count = fetchOne($pdo, $sql_count, $params);
$total_records = ($r_count) ? $r_count['total'] : 0;

// Get Data
$sql_data = "SELECT ki.no_rawat, ki.tgl_masuk, ki.jam_masuk, ki.tgl_keluar, ki.jam_keluar, ki.stts_pulang,
             ki.diagnosa_awal, ki.diagnosa_akhir,
             p.nm_pasien, p.no_rkm_medis, d.nm_dokter, b.nm_bangsal, k.kd_kamar,
             pj.png_jawab, pj.kd_pj, rp.biaya_reg, rp.status_bayar,
             pbr.tarif as plafon_db,
             (SELECT COUNT(*) FROM periksa_lab WHERE no_rawat = ki.no_rawat) as total_lab,
             (SELECT COUNT(*) FROM periksa_radiologi WHERE no_rawat = ki.no_rawat) as total_rad
             FROM kamar_inap ki 
             JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
             JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
             JOIN penjab pj ON rp.kd_pj = pj.kd_pj
             LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
             LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
             LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
             LEFT JOIN perkiraan_biaya_ranap pbr ON ki.no_rawat = pbr.no_rawat
             $where
             GROUP BY ki.no_rawat
             $order_by";

if ($length != -1) $sql_data .= " LIMIT $start, $length ";

$raw_data = fetchAll($pdo, $sql_data, $params);

// --- PROCESS OUTPUT ---
$data_json = [];

foreach ($raw_data as $r) {
    // 1. Hitung Estimasi (Panggil Fungsi Replika View Billing)
    $grand_total = hitungEstimasiAkurat($pdo, $r['no_rawat']);

    // 2. Hitung Hari Rawat (Hanya untuk Display UI)
    $tgl_keluar = ($r['tgl_keluar'] != '0000-00-00') ? $r['tgl_keluar'] : date('Y-m-d');
    $jam_keluar = ($r['jam_keluar'] == '00:00:00') ? date('H:i:s') : $r['jam_keluar'];
    
    // Logic Hari untuk UI saja
    try {
        $d1 = new DateTime($r['tgl_masuk'].' '.$r['jam_masuk']);
        $d2 = new DateTime($tgl_keluar.' '.$jam_keluar);
        $diff = $d2->diff($d1);
        $hari_ui = $diff->days;
        if($hari_ui < 1) $hari_ui = 0; // Tampilkan 0 hari jika belum 24 jam (sesuai Khanza text)
    } catch(Exception $e) { $hari_ui = 0; }

    // 3. Plafon
    $plafon = safeFloat($r['plafon_db']);
    $selisih = ($plafon > 0) ? ($plafon - $grand_total) : 0;
    $persen = ($plafon > 0) ? ($grand_total / $plafon) * 100 : 0;

    // 4. Dokter DPJP
    $dpjp = $r['nm_dokter'];
    $q_dpjp = fetchOne($pdo, "SELECT d.nm_dokter FROM dpjp_ranap dr JOIN dokter d ON dr.kd_dokter = d.kd_dokter WHERE dr.no_rawat=? LIMIT 1", [$r['no_rawat']]);
    if($q_dpjp) {
        $dpjp = "<span class='text-success fw-bold'><i class='fas fa-user-md'></i> DPJP: " . $q_dpjp['nm_dokter'] . "</span>";
    } else {
        $dpjp = "<span class='text-danger fw-bold'><i class='fas fa-exclamation-triangle'></i> DPJP belum diset!</span><br><span style='font-size:0.7rem'>(Awal: " . $r['nm_dokter'] . ")</span>";
    }

    $data_json[] = [
        "waktu_short" => formatTglSingkat($r['tgl_masuk'] . ' ' . $r['jam_masuk']),
        "no_rawat" => $r['no_rawat'],
        "no_rkm_medis" => $r['no_rkm_medis'],
        "nm_pasien" => $r['nm_pasien'],
        "kamar" => $r['nm_bangsal'],
        "hari_rawat" => $hari_ui . " Hari", // Display Only
        "dokter" => $dpjp,
        "penjamin" => $r['png_jawab'],
        "kategori_penjamin" => (strpos(strtoupper($r['png_jawab']), 'BPJS') !== false) ? 'BPJS' : 'Umum',
        "diagnosa_awal" => $r['diagnosa_awal'],
        "diagnosa_akhir" => $r['diagnosa_akhir'],
        
        "total_biaya_raw" => $grand_total,
        "total_biaya" => "Rp " . number_format($grand_total, 0, ',', '.'),
        "plafon_raw" => $plafon,
        "plafon" => ($plafon > 0) ? "Rp " . number_format($plafon, 0, ',', '.') : '-',
        "persen_pemakaian" => round($persen),
        
        "count_lab" => $r['total_lab'],
        "count_rad" => $r['total_rad'],
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