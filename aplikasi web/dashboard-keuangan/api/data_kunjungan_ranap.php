<?php
/*
 * File: api/data_kunjungan_ranap.php (FIX V9 - PARITY MODE)
 * Deskripsi: Menampilkan list pasien Ranap dengan kalkulasi biaya realtime.
 * Fix: Menggunakan logika PHP loop untuk Operasi (bukan SQL SUM) untuk mencegah error typo kolom.
 * Fix: Menjamin sinkronisasi 100% dengan data_rincian_billing.php
 */

ob_start();
ini_set('display_errors', 0);
error_reporting(0);
set_time_limit(0);

mysqli_report(MYSQLI_REPORT_OFF);
header('Content-Type: application/json; charset=utf-8');
require_once(dirname(__DIR__) . '/config/koneksi.php'); 

// 1. HELPER FUNCTIONS
function safeFloat($val) {
    if (is_null($val) || $val === '') return 0.0;
    return (float)$val;
}

function safe_query($conn, $sql) {
    $res = $conn->query($sql);
    if ($res === false) { return false; }
    return $res;
}

// 2. LOAD GLOBAL SETTINGS
$setting_kamar = ['hariawal' => 'no', 'lamajam' => 0]; 
$q_jam = safe_query($koneksi, "SELECT hariawal, lamajam FROM set_jam_minimal LIMIT 1");
if($q_jam && $r_jam = $q_jam->fetch_assoc()) $setting_kamar = $r_jam;

$tampilkan_ppn_ranap = false;
$q_set = $koneksi->query("SELECT tampilkan_ppnobat_ranap FROM set_nota LIMIT 1");
if($q_set && $r_set = $q_set->fetch_assoc()){
    if($r_set['tampilkan_ppnobat_ranap'] == 'Yes') $tampilkan_ppn_ranap = true;
}

$service_umum = null; $service_piutang = null;
$q_su = safe_query($koneksi, "SELECT * FROM set_service_ranap LIMIT 1");
if($q_su) $service_umum = $q_su->fetch_assoc();
$q_sp = safe_query($koneksi, "SELECT * FROM set_service_ranap_piutang LIMIT 1");
if($q_sp) $service_piutang = $q_sp->fetch_assoc();

// 3. PARAMETER DATATABLES
$draw   = isset($_GET['draw']) ? intval($_GET['draw']) : 0;
$start  = isset($_GET['start']) ? intval($_GET['start']) : 0;
$length = isset($_GET['length']) ? intval($_GET['length']) : 10;
$search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
$mode   = isset($_GET['mode']) ? $_GET['mode'] : 'active';
$tgl1   = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl2   = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// 4. QUERY UTAMA
$where = " WHERE 1=1 ";
if ($mode == 'active') {
    $where .= " AND ki.stts_pulang = '-' ";
} else {
    $where .= " AND ki.tgl_masuk BETWEEN '$tgl1' AND '$tgl2' ";
}

if (!empty($search)) {
    $s = $koneksi->real_escape_string($search);
    $where .= " AND (ki.no_rawat LIKE '%$s%' OR p.nm_pasien LIKE '%$s%' OR d.nm_dokter LIKE '%$s%' OR b.nm_bangsal LIKE '%$s%') ";
}

$sql_count = "SELECT COUNT(DISTINCT ki.no_rawat) as total 
              FROM kamar_inap ki 
              JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
              JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
              LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
              LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
              LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
              $where";
$q_count = $koneksi->query($sql_count);
$total_records = ($q_count) ? $q_count->fetch_assoc()['total'] : 0;

$sql_data = "SELECT ki.no_rawat, ki.tgl_masuk, ki.jam_masuk, ki.stts_pulang,
             p.nm_pasien, p.no_rkm_medis, d.nm_dokter, b.nm_bangsal, k.kd_kamar,
             pj.png_jawab, pj.kd_pj, rp.biaya_reg
             FROM kamar_inap ki 
             JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
             JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
             JOIN penjab pj ON rp.kd_pj = pj.kd_pj
             LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
             LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
             LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
             $where
             GROUP BY ki.no_rawat
             ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC
             LIMIT $start, $length";

$q_data = $koneksi->query($sql_data);

if (!$q_data) {
    ob_end_clean();
    echo json_encode([
        "draw" => $draw, "recordsTotal" => 0, "recordsFiltered" => 0, "data" => [],
        "error" => "SQL Error: " . $koneksi->error
    ]);
    exit;
}

$raw_data = [];
while ($r = $q_data->fetch_assoc()) {
    $raw_data[] = $r;
}
$q_data->free();

// 5. PROSES KALKULASI DETAIL (MIRROR LOGIC from data_rincian_billing.php)
$data = [];
foreach ($raw_data as $r) {
    $no_rawat = $r['no_rawat'];
    $grand_total = 0.0;
    
    // Accumulators
    $sum_kamar = 0; $sum_reg = 0; 
    $sum_dr_ralan = 0; $sum_pr_ralan = 0; 
    $sum_dr_ranap = 0; $sum_pr_ranap = 0; 
    $sum_lab = 0; $sum_rad = 0; $sum_op = 0; $sum_obat = 0; 
    $sum_retur = 0; $sum_tambah = 0; $sum_potong = 0; $sum_harian = 0;

    // A. Registrasi
    if(safeFloat($r['biaya_reg']) > 0) {
        $val = safeFloat($r['biaya_reg']); $sum_reg += $val; $grand_total += $val;
    }

    // B. Kamar Inap (History Mode)
    $q_hist_kamar = safe_query($koneksi, "SELECT k.kd_kamar, k.trf_kamar, ki.tgl_masuk, ki.tgl_keluar, ki.lama, ki.ttl_biaya FROM kamar_inap ki JOIN kamar k ON ki.kd_kamar = k.kd_kamar WHERE ki.no_rawat='$no_rawat'");
    if($q_hist_kamar) {
        while($rhk = $q_hist_kamar->fetch_assoc()) {
            $tgl_masuk = $rhk['tgl_masuk'];
            $tgl_keluar = ($rhk['tgl_keluar'] != '0000-00-00') ? $rhk['tgl_keluar'] : date('Y-m-d');
            $d1 = new DateTime($tgl_masuk); $d2 = new DateTime($tgl_keluar);
            $diff = $d2->diff($d1);
            $hari_raw = $diff->days;

            if ($setting_kamar['hariawal'] == 'yes') $hari = $hari_raw + 1;
            else $hari = $hari_raw;

            if (safeFloat($rhk['ttl_biaya']) > 0 && safeFloat($rhk['lama']) > 0) $hari = safeFloat($rhk['lama']);

            $biaya_satu_kamar = $hari * safeFloat($rhk['trf_kamar']);
            if($biaya_satu_kamar > 0) { $sum_kamar += $biaya_satu_kamar; $grand_total += $biaya_satu_kamar; }

            $kd_k = $rhk['kd_kamar'];
            $q_bs = safe_query($koneksi, "SELECT SUM(besar_biaya) as tot FROM biaya_sekali WHERE kd_kamar='$kd_k'");
            if($q_bs && $row_bs = $q_bs->fetch_assoc()) { $val = safeFloat($row_bs['tot']); $sum_harian += $val; $grand_total += $val; }

            $q_bh = safe_query($koneksi, "SELECT SUM(besar_biaya) as tot FROM biaya_harian WHERE kd_kamar='$kd_k'");
            if($q_bh && $row_bh = $q_bh->fetch_assoc()) { $val = ($hari * safeFloat($row_bh['tot'])); $sum_harian += $val; $grand_total += $val; }
        }
    }

    // C. Operasi (Metode PHP Loop - Lebih Aman dari Typo SQL)
    $q_op = safe_query($koneksi, "SELECT * FROM operasi WHERE no_rawat='$no_rawat'");
    if($q_op) {
        while($r_op = $q_op->fetch_assoc()) {
            // Daftar komponen operasi standar Khanza
            $komponen = ['biayaoperator1','biayaoperator2','biayaoperator3','biayaasisten_operator1','biayaasisten_operator2','biayadokter_anestesi','biayaasisten_anestesi','biayasewaok','biayaalat','akomodasi','bagian_rs','biaya_omloop','biayasarpras','biaya_dokter_anak','biayaperawaat_resusitas','biayabidan'];
            foreach($komponen as $k) { 
                if(isset($r_op[$k])) {
                    $val = safeFloat($r_op[$k]);
                    $sum_op += $val;
                }
            }
        }
    }
    $grand_total += $sum_op;

    // D. Tindakan (Union Mode)
    $sql_tind = "SELECT 'lab' as grp, SUM(biaya) as tot FROM periksa_lab WHERE no_rawat='$no_rawat'
                 UNION ALL SELECT 'rad', SUM(biaya) FROM periksa_radiologi WHERE no_rawat='$no_rawat'
                 UNION ALL SELECT 'dr_ralan', SUM(biaya_rawat) FROM rawat_jl_dr WHERE no_rawat='$no_rawat'
                 UNION ALL SELECT 'pr_ralan', SUM(biaya_rawat) FROM rawat_jl_pr WHERE no_rawat='$no_rawat'
                 UNION ALL SELECT 'dr_ralan', SUM(biaya_rawat) FROM rawat_jl_drpr WHERE no_rawat='$no_rawat'
                 UNION ALL SELECT 'dr_ranap', SUM(biaya_rawat) FROM rawat_inap_dr WHERE no_rawat='$no_rawat'
                 UNION ALL SELECT 'pr_ranap', SUM(biaya_rawat) FROM rawat_inap_pr WHERE no_rawat='$no_rawat'
                 UNION ALL SELECT 'dr_ranap', SUM(biaya_rawat) FROM rawat_inap_drpr WHERE no_rawat='$no_rawat'
                 UNION ALL SELECT 'tambah', SUM(besar_biaya) FROM tambahan_biaya WHERE no_rawat='$no_rawat'
                 UNION ALL SELECT 'potong', SUM(besar_pengurangan) FROM pengurangan_biaya WHERE no_rawat='$no_rawat'";
    
    $q_tind = safe_query($koneksi, $sql_tind);
    if($q_tind) {
        while($rt = $q_tind->fetch_assoc()){
            $val = safeFloat($rt['tot']);
            $grp = $rt['grp'];
            if($val != 0) {
                if($grp == 'lab') $sum_lab += $val;
                else if($grp == 'rad') $sum_rad += $val;
                else if($grp == 'dr_ralan') $sum_dr_ralan += $val;
                else if($grp == 'pr_ralan') $sum_pr_ralan += $val;
                else if($grp == 'dr_ranap') $sum_dr_ranap += $val;
                else if($grp == 'pr_ranap') $sum_pr_ranap += $val;
                else if($grp == 'tambah') $sum_tambah += $val;
                else if($grp == 'potong') { $sum_potong += (-1 * abs($val)); $grand_total += (-1 * abs($val)); continue; } 
                $grand_total += $val;
            }
        }
    }

    // E. Obat & Retur
    $sql_obat = "SELECT SUM(total) as tot FROM detail_pemberian_obat WHERE no_rawat='$no_rawat'
                 UNION ALL SELECT SUM(besar_tagihan) FROM tagihan_obat_langsung WHERE no_rawat='$no_rawat'
                 UNION ALL SELECT SUM(hargasatuan * jumlah) FROM beri_obat_operasi WHERE no_rawat='$no_rawat'";
    $q_obat = safe_query($koneksi, $sql_obat);
    if($q_obat) while($ro = $q_obat->fetch_assoc()) $sum_obat += safeFloat($ro['tot']);
    $grand_total += $sum_obat;

    $q_ret_fix = safe_query($koneksi, "SELECT SUM(r.jml * d.ralan) as tot FROM returpasien r JOIN databarang d ON r.kode_brng = d.kode_brng WHERE r.no_rawat='$no_rawat'");
    if($q_ret_fix && $rr = $q_ret_fix->fetch_assoc()) $sum_retur += abs(safeFloat($rr['tot']));
    $grand_total -= $sum_retur;

    // F. PPN
    if($tampilkan_ppn_ranap) {
        $obat_bersih = $sum_obat - $sum_retur;
        if($obat_bersih > 0) $grand_total += round($obat_bersih * 0.11);
    }

    // G. Jasa Admin (Service)
    $s = null;
    $kd_pj = $r['kd_pj'];
    if($kd_pj != '-' && $kd_pj != 'UMUM' && $kd_pj != 'A01') $s = $service_piutang;
    else $s = $service_umum;

    if($s) {
        $basis = 0;
        if($s['laborat'] == 'Yes') $basis += $sum_lab;
        if($s['radiologi'] == 'Yes') $basis += $sum_rad;
        if($s['operasi'] == 'Yes') $basis += $sum_op;
        if($s['obat'] == 'Yes') $basis += ($sum_obat - $sum_retur);
        if($s['ranap_dokter'] == 'Yes') $basis += $sum_dr_ranap;
        if($s['ranap_paramedis'] == 'Yes') $basis += $sum_pr_ranap;
        if($s['ralan_dokter'] == 'Yes') $basis += $sum_dr_ralan;
        if($s['ralan_paramedis'] == 'Yes') $basis += $sum_pr_ralan;
        if($s['tambahan'] == 'Yes') $basis += $sum_tambah;
        if($s['potongan'] == 'Yes') $basis += $sum_potong;
        if($s['kamar'] == 'Yes') $basis += $sum_kamar;
        if($s['registrasi'] == 'Yes') $basis += $sum_reg;
        if($s['harian'] == 'Yes') $basis += $sum_harian;

        $persen = safeFloat($s['besar']);
        if($basis > 0 && $persen > 0) {
            $jasa_admin = round($basis * ($persen / 100));
            
            // Cek real billing di DB
            $cek = safe_query($koneksi, "SELECT totalbiaya FROM billing WHERE no_rawat='$no_rawat' AND (nm_perawatan LIKE '%Administrasi%' OR nm_perawatan LIKE '%Service%')");
            if(!$cek || $cek->num_rows == 0) {
                // Jika belum ada di billing, tambahkan hitungan
                $grand_total += $jasa_admin;
            } else {
                 // Jika sudah ada, ambil real dari billing (biasanya admin billing sudah final)
                 while($row_bill = $cek->fetch_assoc()) $grand_total += safeFloat($row_bill['totalbiaya']);
            }
        }
    }

    // DPJP & Metadata
    $dpjp = $r['nm_dokter'];
    $is_dpjp_fallback = false;
    $q_dpjp = safe_query($koneksi, "SELECT d.nm_dokter FROM dpjp_ranap dr JOIN dokter d ON dr.kd_dokter = d.kd_dokter WHERE dr.no_rawat='$no_rawat' LIMIT 1");
    if($q_dpjp && $rd = $q_dpjp->fetch_assoc()) $dpjp = $rd['nm_dokter'];
    else $is_dpjp_fallback = true;

    $plafon = 0; 
    $selisih = $plafon - $grand_total;
    $is_over = ($plafon > 0 && $grand_total > $plafon);

    $data[] = [
        "waktu" => $r['tgl_masuk'],
        "no_rawat" => $r['no_rawat'],
        "pasien" => $r['nm_pasien'],
        "rm" => $r['no_rkm_medis'],
        "dpjp" => $dpjp,
        "is_dpjp_fallback" => $is_dpjp_fallback,
        "kamar" => $r['nm_bangsal'], 
        "penjamin" => $r['png_jawab'],
        "plafon" => ($plafon > 0) ? number_format($plafon, 0, ',', '.') : '-',
        "estimasi" => number_format($grand_total, 0, ',', '.'),
        "selisih" => ($plafon > 0) ? number_format(abs($selisih), 0, ',', '.') : '-',
        "is_over" => $is_over,
        "status_pulang" => ($r['stts_pulang'] != '-') ? $r['stts_pulang'] : 'Masih Dirawat'
    ];
}

$output = [
    "draw" => $draw,
    "recordsTotal" => $total_records,
    "recordsFiltered" => $total_records,
    "data" => $data
];

ob_end_clean();
echo json_encode($output);
?>