<?php
/*
 * File: api_monitor_ranap.php (FIX V3 - LOGIC DASHBOARD CLONE)
 * Deskripsi: Menghitung biaya menggunakan Logic PHP (bukan SQL Sum) agar akurat 100%
 */
header('Content-Type: application/json');
require_once('includes/config_modern.php');

ini_set('display_errors', 0);
error_reporting(E_ALL);

// ==================================================================================
// BAGIAN 1: FUNGSI HITUNG BIAYA (DICOAS DARI data_kunjungan_ranap.php)
// ==================================================================================

function safeFloat($val) {
    if (is_null($val) || $val === '') return 0.0;
    return (float)$val;
}

function getSettings($conn) {
    $settings = ['service_charge' => 0, 'ppn_obat' => false, 'components' => []];
    
    // Cek Setting Nota (PPN)
    $q = $conn->query("SELECT tampilkan_ppnobat_ranap FROM set_nota LIMIT 1");
    if($q && $r = $q->fetch_assoc()) $settings['ppn_obat'] = ($r['tampilkan_ppnobat_ranap'] == 'Yes');

    // Cek Setting Service Charge
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

    // 1. Registrasi
    $q = $conn->query("SELECT biaya_reg FROM reg_periksa WHERE no_rawat='$no_rawat'");
    if($q && $r = $q->fetch_assoc()) $biaya['registrasi'] += safeFloat($r['biaya_reg']);

    // 2. Obat & Retur
    $q = $conn->query("SELECT SUM(total) as val FROM detail_pemberian_obat WHERE no_rawat='$no_rawat'");
    if($q && $r = $q->fetch_assoc()) $biaya['obat'] += safeFloat($r['val']);
    
    $q = $conn->query("SELECT SUM(besar_tagihan) as val FROM tagihan_obat_langsung WHERE no_rawat='$no_rawat'");
    if($q && $r = $q->fetch_assoc()) $biaya['obat'] += safeFloat($r['val']);

    $q = $conn->query("SELECT SUM(r.jml * d.ralan) as val FROM returpasien r JOIN databarang d ON r.kode_brng = d.kode_brng WHERE r.no_rawat='$no_rawat'");
    if($q && $r = $q->fetch_assoc()) $biaya['retur_Obat'] += safeFloat($r['val']);

    $q = $conn->query("SELECT SUM(total) as val FROM resep_pulang WHERE no_rawat='$no_rawat'");
    if($q && $r = $q->fetch_assoc()) $biaya['resep_Pulang'] += safeFloat($r['val']);

    // 3. Tindakan & Lab/Rad
    $tables = [
        'rawat_jl_dr'=>'ralan_dokter', 'rawat_jl_pr'=>'ralan_paramedis', 'rawat_jl_drpr'=>'ralan_dokter', 
        'rawat_inap_dr'=>'ranap_dokter', 'rawat_inap_pr'=>'ranap_paramedis', 'rawat_inap_drpr'=>'ranap_dokter',
        'periksa_lab'=>'laborat', 'periksa_radiologi'=>'radiologi', 'penggunaan_darah_donor'=>'obat'
    ];
    foreach($tables as $tbl => $cat) {
        $col = (strpos($tbl, 'periksa_') !== false || $tbl == 'penggunaan_darah_donor') ? 'biaya' : 'biaya_rawat';
        $q = $conn->query("SELECT SUM($col) as val FROM $tbl WHERE no_rawat='$no_rawat'");
        if($q && $r = $q->fetch_assoc()) $biaya[$cat] += safeFloat($r['val']);
    }

    // Detail Lab (Kadang ada yg pakai detail)
    $q = $conn->query("SELECT SUM(biaya_item) as val FROM detail_periksa_lab WHERE no_rawat='$no_rawat'");
    if($q && $r = $q->fetch_assoc()) $biaya['laborat'] += safeFloat($r['val']);

    // 4. Operasi
    $sql_op = "SELECT SUM(biayaoperator1+biayaoperator2+biayaoperator3+biayaasisten_operator1+biayaasisten_operator2+biayaasisten_operator3+biayainstrumen+biayadokter_anak+biayaperawaat_resusitas+biayadokter_anestesi+biayaasisten_anestesi+biayaasisten_anestesi2+biayabidan+biayabidan2+biayabidan3+biayaperawat_luar+biayaalat+biayasewaok+akomodasi+bagian_rs+biaya_omloop+biaya_omloop2+biaya_omloop3+biaya_omloop4+biaya_omloop5+biayasarpras+biaya_dokter_pjanak+biaya_dokter_umum) as val FROM operasi WHERE no_rawat='$no_rawat'";
    $q = $conn->query($sql_op);
    if($q && $r = $q->fetch_assoc()) $biaya['operasi'] += safeFloat($r['val']);

    // 5. Kamar & Harian
    $q_kamar = $conn->query("SELECT ki.kd_kamar, k.trf_kamar, ki.tgl_masuk, ki.jam_masuk, ki.tgl_keluar, ki.jam_keluar, ki.ttl_biaya FROM kamar_inap ki INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar WHERE ki.no_rawat='$no_rawat'");
    if ($q_kamar) {
        while($r_kamar = $q_kamar->fetch_assoc()) {
            if (safeFloat($r_kamar['ttl_biaya']) > 0) {
                $biaya['kamar'] += safeFloat($r_kamar['ttl_biaya']);
                $hari = 0; 
            } else {
                if($r_kamar['tgl_keluar'] != '0000-00-00') { 
                    $tgl_keluar = $r_kamar['tgl_keluar']; 
                } else {
                    $tgl_keluar = date('Y-m-d');
                }
                try {
                    $ts1 = strtotime($r_kamar['tgl_masuk'] . ' ' . $r_kamar['jam_masuk']);
                    $ts2 = strtotime($tgl_keluar . ' ' . (isset($r_kamar['jam_keluar']) && $r_kamar['jam_keluar'] != '00:00:00' ? $r_kamar['jam_keluar'] : date('H:i:s')));
                    $hari = floor(($ts2 - $ts1) / (60 * 60 * 24));
                } catch (Exception $e) { $hari = 1; }
                if ($hari <= 0) $hari = 1;
                $biaya['kamar'] += ($hari * safeFloat($r_kamar['trf_kamar']));
            }

            if($hari > 0 || safeFloat($r_kamar['ttl_biaya']) > 0){
                $kd_kamar = $r_kamar['kd_kamar'];
                $q_h = $conn->query("SELECT besar_biaya FROM biaya_harian WHERE kd_kamar='$kd_kamar'");
                while($rh = $q_h->fetch_assoc()) $biaya['harian'] += ($hari * safeFloat($rh['besar_biaya']));

                $q_s = $conn->query("SELECT besar_biaya FROM biaya_sekali WHERE kd_kamar='$kd_kamar'");
                while($rs = $q_s->fetch_assoc()) $biaya['kamar'] += safeFloat($rs['besar_biaya']);
            }
        }
    }

    // 6. Tambahan & Potongan
    $q = $conn->query("SELECT SUM(besar_biaya) as val FROM tambahan_biaya WHERE no_rawat='$no_rawat'");
    if($q && $r = $q->fetch_assoc()) $biaya['tambahan'] += safeFloat($r['val']);
    
    $q = $conn->query("SELECT SUM(besar_pengurangan) as val FROM pengurangan_biaya WHERE no_rawat='$no_rawat'");
    if($q && $r = $q->fetch_assoc()) $biaya['potongan'] += safeFloat($r['val']);

    // Final Calculation (Service & PPN)
    $obat_bersih = $biaya['obat'] - $biaya['retur_Obat'];
    $ppn_rp = ($settings['ppn_obat'] && $obat_bersih > 0) ? $obat_bersih * 0.11 : 0;

    $service_base = 0;
    foreach ($settings['components'] as $key => $isActive) {
        if ($isActive && isset($biaya[$key])) {
            $service_base += ($key == 'retur_Obat') ? -($biaya[$key]) : $biaya[$key];
        }
    }
    $service_rp = ($service_base * $settings['service_charge']) / 100;

    // Total Bersih = Semua Biaya - Retur - Potongan + PPN + Service Charge
    return array_sum($biaya) - ($biaya['retur_Obat'] * 2) - ($biaya['potongan'] * 2) + $ppn_rp + $service_rp;
}

// ==================================================================================
// BAGIAN 2: PROSES UTAMA ROBOT
// ==================================================================================

// Ambil Settings Sekali Saja
$settings = getSettings($koneksi_sik);

// Query List Pasien Ranap Aktif
// Kita tidak perlu menghitung biaya di SQL lagi, cukup ambil ID Pasien
$sql = "
    SELECT
        ki.no_rawat,
        p.no_rkm_medis,
        p.nm_pasien,
        b.nm_bangsal,
        pbr.tarif as plafon,
        (SELECT nm_dokter FROM dpjp_ranap dr JOIN dokter d ON dr.kd_dokter = d.kd_dokter WHERE dr.no_rawat = ki.no_rawat LIMIT 1) as dpjp
    FROM kamar_inap ki
    INNER JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
    INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar
    INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
    LEFT JOIN perkiraan_biaya_ranap pbr ON ki.no_rawat = pbr.no_rawat
    WHERE rp.status_bayar = 'Belum Bayar' 
      AND rp.status_lanjut = 'Ranap' 
      AND ki.stts_pulang = '-'
      AND pbr.tarif > 0
";

$result = $koneksi_sik->query($sql);
$logs = [];
$count_processed = 0;
$count_notified = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $count_processed++;
        $no_rawat = $row['no_rawat'];
        $plafon = safeFloat($row['plafon']);
        
        // --- HITUNG REALTIME VIA PHP (SUPER AKURAT) ---
        $billing = hitungEstimasiAkurat($koneksi_sik, $no_rawat, $settings);
        
        $selisih = $plafon - $billing;
        $nm_pasien_singkat = substr($row['nm_pasien'], 0, 15) . '...';
        
        // Calculate Percent Usage
        $percent_usage = ($plafon > 0) ? ($billing / $plafon) * 100 : 0;
        $percent_usage = round($percent_usage, 1);

        $debug_msg = "[INFO] $nm_pasien_singkat | Bill: ".number_format($billing)." | Plafon: ".number_format($plafon)." | Pakai: {$percent_usage}%";

        $should_notify = false;
        $status_label = "";

        // --- LOGIC BATAS ---
        if (LIMIT_MODE == 'PERCENT') {
            if ($percent_usage >= LIMIT_PERCENT) {
                $should_notify = true;
                $status_label = "SUDAH " . $percent_usage . "% (Sisa: Rp ".number_format($selisih).")";
            } else {
                 $logs[] = "$debug_msg -> AMAN (Belum " . LIMIT_PERCENT . "%)";
            }
        } else {
            if ($selisih < LIMIT_FIXED_VAL) {
                $should_notify = true;
                $status_label = "PLAFON MENIPIS (Sisa < 1 Jt)";
            }
        }

        if ($should_notify) {
            // Cek ke tabel perkiraan_biaya_ranap_notif_wa
            $cek_sql = "SELECT no_rawat FROM perkiraan_biaya_ranap_notif_wa WHERE no_rawat = '$no_rawat'";
            $cek = $koneksi_sik->query($cek_sql);
            
            if ($cek->num_rows == 0) {
                // Handle DPJP Kosong
                $dpjp_name = !empty($row['dpjp']) ? $row['dpjp'] : "-";

                // --- BUILD PESAN ---
                $message = "⚠️ *PERINGATAN KENDALI BIAYA (80%)* ⚠️\n"
                         . "*Status: {$status_label}*\n\n"
                         . "📋 No. Rawat: " . $row['no_rawat'] . "\n"
                         . "👤 Pasien: " . $row['nm_pasien'] . " (" . $row['no_rkm_medis'] . ")\n"
                         . "🏥 Ruang: " . $row['nm_bangsal'] . "\n"
                         . "👨‍⚕️ DPJP: " . $dpjp_name . "\n"
                         . "----------------------------------\n"
                         . "💰 Billing: Rp " . number_format($billing, 0, ',', '.') . "\n"
                         . "🛡️ Plafon: Rp " . number_format($plafon, 0, ',', '.') . "\n"
                         . "📊 *Penggunaan: " . $percent_usage . "%*\n"
                         . "----------------------------------\n"
                         . "_Mohon segera dilakukan kendali biaya._";

                // 1. KIRIM TELEGRAM
                sendTelegram($message);
                
                // 2. KIRIM WA
                $pesan_wa = escape($koneksi_wa, $message);
                $target_wa = WA_GROUP_ID; 
                
                $wa_sql = "INSERT INTO wa_outbox (nowa, pesan, tanggal_jam, status, source, sender, type) 
                           VALUES ('$target_wa', '$pesan_wa', NOW(), 'ANTRIAN', 'KHANZA', 'NODEJS', 'TEXT')";
                
                if ($koneksi_wa->query($wa_sql) === TRUE) {
                    // Log Sukses
                    $log_sql = "INSERT INTO perkiraan_biaya_ranap_notif_wa (no_rawat, status_kirim_wa) VALUES ('$no_rawat', '1')";
                    $koneksi_sik->query($log_sql);
                    
                    $count_notified++;
                    $logs[] = "[ALERT] Insert Sukses ke Outbox untuk {$row['nm_pasien']} (Pakai: {$percent_usage}%)";
                } else {
                    $logs[] = "[ERROR DB WA] " . $koneksi_wa->error;
                }
            } else {
                $logs[] = "[SKIP] $nm_pasien_singkat (Pakai: {$percent_usage}%) -> Sudah dikirim.";
            }
        }
    }
} else {
    $logs[] = "[ERROR SQL] " . $koneksi_sik->error;
}

echo json_encode([
    "status" => "success", 
    "processed" => $count_processed, 
    "notified" => $count_notified, 
    "logs" => $logs
]);
?>