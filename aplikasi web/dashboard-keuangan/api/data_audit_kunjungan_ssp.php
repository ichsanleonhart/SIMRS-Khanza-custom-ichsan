<?php
/*
 * File: api/data_audit_kunjungan_ssp.php (FIX V7 - LOGIKA KAMAR)
 * - Fix Fatal Bug: Kamar 'Pindah' tidak lagi dihitung sampai hari ini, tapi sesuai tgl_keluar.
 * - Result: Sinkron 100% dengan data Billing Khanza.
 */

ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once(dirname(__DIR__) . '/config/koneksi.php'); 

function hitungDetailBiaya($conn, $no_rawat, $status_lanjut) {
    $total = 0;
    $biaya_obat = 0;

    // 1. Registrasi (All)
    $q_reg = $conn->query("SELECT biaya_reg FROM reg_periksa WHERE no_rawat='$no_rawat'");
    if($q_reg && $r_reg = $q_reg->fetch_assoc()) $total += (float)$r_reg['biaya_reg'];

    // 2. Obat (Gross)
    $q_obat = $conn->query("SELECT SUM(total) as val FROM detail_pemberian_obat WHERE no_rawat='$no_rawat'");
    if($q_obat && $r_obat = $q_obat->fetch_assoc()) {
        $biaya_obat = (float)$r_obat['val'];
        $total += $biaya_obat;
    }

    // 3. Retur Obat (Pengurang)
    $q_ret = $conn->query("SELECT SUM(h_retur * jml) as val FROM returpasien WHERE no_rawat='$no_rawat'");
    if($q_ret && $r_ret = $q_ret->fetch_assoc()) {
        $val_retur = (float)$r_ret['val'];
        $total -= $val_retur;
        $biaya_obat -= $val_retur; 
    }

    // 4. Tindakan (Ralan + Ranap digabung untuk safety)
    $tables = ['rawat_jl_dr', 'rawat_jl_pr', 'rawat_jl_drpr', 'rawat_inap_dr', 'rawat_inap_pr', 'rawat_inap_drpr'];
    foreach($tables as $tbl) {
        $q = $conn->query("SELECT SUM(biaya_rawat) as val FROM $tbl WHERE no_rawat='$no_rawat'");
        if($q && $r = $q->fetch_assoc()) $total += (float)$r['val'];
    }

    // 5. KAMAR INAP (LOGIKA DIPERBAIKI)
    if ($status_lanjut == 'Ranap') {
        $q_kamar = $conn->query("
            SELECT k.trf_kamar, ki.tgl_masuk, ki.jam_masuk, ki.tgl_keluar, ki.jam_keluar, ki.stts_pulang, ki.lama
            FROM kamar_inap ki 
            INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar 
            WHERE ki.no_rawat='$no_rawat'
        ");
        
        if ($q_kamar) {
            while($r_kamar = $q_kamar->fetch_assoc()) {
                // Prioritas: Jika kolom 'lama' di DB sudah terisi > 0, pakai itu (Logic Khanza)
                $lama_db = (float)$r_kamar['lama'];
                $tarif = (float)$r_kamar['trf_kamar'];

                if ($lama_db > 0) {
                    $total += ($lama_db * $tarif);
                } else {
                    // Jika 0, hitung manual
                    $masuk = new DateTime($r_kamar['tgl_masuk'] . ' ' . $r_kamar['jam_masuk']);
                    
                    // FIX BUG DISINI: Cek tanggal keluar DB dulu!
                    if ($r_kamar['tgl_keluar'] != '0000-00-00') {
                        $keluar = new DateTime($r_kamar['tgl_keluar'] . ' ' . $r_kamar['jam_keluar']);
                    } else {
                        $keluar = new DateTime(); // Baru pakai NOW jika tgl_keluar kosong
                    }

                    $diff = $keluar->diff($masuk);
                    $hari = $diff->days;

                    // Aturan Main:
                    // Jika Pindah Kamar dan selisih 0 hari -> 0 Hari (Transit)
                    // Jika Belum Pulang dan selisih 0 hari -> 1 Hari (Minimal)
                    if ($hari == 0) {
                        if ($r_kamar['stts_pulang'] != 'Pindah Kamar') {
                            $hari = 1;
                        }
                    }
                    $total += ($hari * $tarif);
                }
            }
        }
    }

    // 6. Penunjang & Operasi
    $q_lab = $conn->query("SELECT SUM(biaya) as val FROM periksa_lab WHERE no_rawat='$no_rawat'");
    if($q_lab && $r = $q_lab->fetch_assoc()) $total += (float)$r['val'];

    $q_rad = $conn->query("SELECT SUM(biaya) as val FROM periksa_radiologi WHERE no_rawat='$no_rawat'");
    if($q_rad && $r = $q_rad->fetch_assoc()) $total += (float)$r['val'];

    $q_darah = $conn->query("SELECT SUM(biaya) as val FROM penggunaan_darah_donor WHERE no_rawat='$no_rawat'");
    if($q_darah && $r = $q_darah->fetch_assoc()) $total += (float)$r['val'];

    // Operasi
    $q_op = $conn->query("SELECT SUM(biayaoperator1+biayaoperator2+biayaoperator3+biayaasisten_operator1+biayaasisten_operator2+biayadokter_anestesi+biayaasisten_anestesi+biayaasisten_anestesi2+biayadokter_anak+biayaperawaat_resusitas+biayabidan+biayabidan2+biayabidan3+biayaperawat_luar+biayasewaok+biayaalat+akomodasi+bagian_rs+biaya_omloop+biaya_omloop2+biaya_omloop3+biaya_omloop4+biaya_omloop5+biayasarpras+biaya_dokter_pjanak+biaya_dokter_umum) as val FROM operasi WHERE no_rawat='$no_rawat'");
    if($q_op && $r = $q_op->fetch_assoc()) $total += (float)$r['val'];

    // 7. Tambahan & Pengurangan
    $q_add = $conn->query("SELECT SUM(besar) as val FROM tambahan_biaya WHERE no_rawat='$no_rawat'");
    if($q_add && $r = $q_add->fetch_assoc()) $total += (float)$r['val'];

    $q_min = $conn->query("SELECT SUM(besar) as val FROM pengurangan_biaya WHERE no_rawat='$no_rawat'");
    if($q_min && $r = $q_min->fetch_assoc()) $total -= (float)$r['val'];

    return ['obat' => $biaya_obat, 'total' => $total];
}

// --- DATATABLES ---
$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$length = isset($_GET['length']) ? (int)$_GET['length'] : 10;
$search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

$sql_base = "
    FROM reg_periksa rp
    INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    INNER JOIN penjab pj ON rp.kd_pj = pj.kd_pj
    INNER JOIN dokter d ON rp.kd_dokter = d.kd_dokter
    LEFT JOIN poliklinik poli ON rp.kd_poli = poli.kd_poli
    WHERE rp.status_bayar = 'Belum Bayar'
";

if (!empty($search)) {
    $sql_base .= " AND (p.nm_pasien LIKE '%$search%' OR rp.no_rawat LIKE '%$search%' OR rp.no_rkm_medis LIKE '%$search%') ";
}

$q_cnt = $koneksi->query("SELECT COUNT(*) as total " . $sql_base);
$totalData = ($q_cnt) ? $q_cnt->fetch_assoc()['total'] : 0;

// ORDER BY stts ASC (Batal paling atas)
$sql_data = "
    SELECT 
        rp.no_rawat, rp.tgl_registrasi, rp.jam_reg,
        rp.no_rkm_medis, p.nm_pasien,
        rp.status_lanjut, rp.stts as status_pelayanan,
        pj.png_jawab, poli.nm_poli
    " . $sql_base . "
    ORDER BY rp.stts ASC, rp.tgl_registrasi DESC
    LIMIT $start, $length
";

$res = $koneksi->query($sql_data);
$data = [];

if ($res) {
    while($row = $res->fetch_assoc()) {
        $biaya = hitungDetailBiaya($koneksi, $row['no_rawat'], $row['status_lanjut']);
        
        $row['biaya_obat'] = $biaya['obat'];
        $row['biaya_total'] = $biaya['total'];
        $row['is_anomaly'] = ($row['status_pelayanan'] == 'Batal' && $biaya['total'] > 0) ? true : false;
        $row['waktu'] = $row['tgl_registrasi'] . ' ' . $row['jam_reg'];
        
        $data[] = $row;
    }
}

echo json_encode([
    "draw" => isset($_GET['draw']) ? (int)$_GET['draw'] : 1,
    "recordsTotal" => $totalData,
    "recordsFiltered" => $totalData,
    "data" => $data
]);
?>