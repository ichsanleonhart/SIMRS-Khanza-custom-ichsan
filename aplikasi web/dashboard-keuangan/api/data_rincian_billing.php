<?php
/*
 * File: api/data_rincian_billing.php
 * Fungsi: Mengambil rincian detail billing sementara (Estimasi) per komponen.
 * Referensi Logic: DlgBilingRanap.java
 */

ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once(dirname(__DIR__) . '/config/koneksi.php'); 

$no_rawat = isset($_GET['no_rawat']) ? $_GET['no_rawat'] : '';

if(empty($no_rawat)) { echo json_encode([]); exit; }

$details = [];
$total_all = 0;

// 1. ADMINISTRASI (Registrasi)
$q_reg = $koneksi->query("SELECT biaya_reg FROM reg_periksa WHERE no_rawat='$no_rawat'");
if($q_reg && $r_reg = $q_reg->fetch_assoc()) {
    $val = (float)$r_reg['biaya_reg'];
    if($val > 0) {
        $details[] = ['kategori' => 'Registrasi/Administrasi', 'nama' => 'Biaya Pendaftaran', 'biaya' => $val];
        $total_all += $val;
    }
}

// 2. AKOMODASI (Kamar Inap)
$q_kamar = $koneksi->query("
    SELECT k.kd_kamar, b.nm_bangsal, k.trf_kamar, ki.tgl_masuk, ki.jam_masuk, ki.tgl_keluar, ki.jam_keluar, ki.stts_pulang
    FROM kamar_inap ki 
    INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar 
    INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
    WHERE ki.no_rawat='$no_rawat'
");

if ($q_kamar) {
    while($r = $q_kamar->fetch_assoc()) {
        $masuk = new DateTime($r['tgl_masuk'] . ' ' . $r['jam_masuk']);
        if ($r['stts_pulang'] != 'Pindah Kamar' && $r['tgl_keluar'] != '0000-00-00') {
            $keluar = new DateTime($r['tgl_keluar'] . ' ' . $r['jam_keluar']);
        } else {
            $keluar = new DateTime(); // Sampai sekarang
        }
        
        $hari = $keluar->diff($masuk)->days;
        // Logic Khanza: Jika 0 hari & bukan pindah kamar, hitung 1 hari
        if ($hari == 0 && $r['stts_pulang'] != 'Pindah Kamar') $hari = 1;
        
        $subtotal = $hari * (float)$r['trf_kamar'];
        if($subtotal > 0) {
            $details[] = [
                'kategori' => 'Kamar Inap', 
                'nama' => $r['nm_bangsal'] . ' (' . $hari . ' Hari)', 
                'biaya' => $subtotal
            ];
            $total_all += $subtotal;
        }
    }
}

// 3. TINDAKAN (Ralan & Ranap)
$tabel_tindakan = [
    'rawat_jl_dr' => 'Tindakan Ralan (Dr)', 
    'rawat_jl_pr' => 'Tindakan Ralan (Pr)', 
    'rawat_jl_drpr' => 'Tindakan Ralan (Dr+Pr)', 
    'rawat_inap_dr' => 'Tindakan Ranap (Dr)', 
    'rawat_inap_pr' => 'Tindakan Ranap (Pr)', 
    'rawat_inap_drpr' => 'Tindakan Ranap (Dr+Pr)'
];

foreach ($tabel_tindakan as $tbl => $label) {
    // Kita join ke jns_perawatan untuk ambil nama tindakan
    $tbl_master = (strpos($tbl, 'inap') !== false) ? 'jns_perawatan_inap' : 'jns_perawatan';
    
    $sql_tind = "SELECT j.nm_perawatan, t.biaya_rawat, t.tgl_perawatan 
                 FROM $tbl t 
                 INNER JOIN $tbl_master j ON t.kd_jenis_prw = j.kd_jenis_prw 
                 WHERE t.no_rawat='$no_rawat'";
                 
    $q_tind = $koneksi->query($sql_tind);
    while($r = $q_tind->fetch_assoc()) {
        $details[] = [
            'kategori' => 'Tindakan Medis',
            'nama' => $r['nm_perawatan'],
            'biaya' => (float)$r['biaya_rawat']
        ];
        $total_all += (float)$r['biaya_rawat'];
    }
}

// 4. OBAT & ALKES (Detail)
$q_obat = $koneksi->query("
    SELECT d.nama_brng, dp.jml, dp.total 
    FROM detail_pemberian_obat dp 
    INNER JOIN databarang d ON dp.kode_brng = d.kode_brng 
    WHERE dp.no_rawat='$no_rawat'
");
while($r = $q_obat->fetch_assoc()) {
    $details[] = [
        'kategori' => 'Obat & Alkes',
        'nama' => $r['nama_brng'] . ' (' . $r['jml'] . ')',
        'biaya' => (float)$r['total']
    ];
    $total_all += (float)$r['total'];
}

// 5. RETUR OBAT (Pengurang)
$q_retur = $koneksi->query("
    SELECT d.nama_brng, r.jml, (r.h_retur * r.jml) as total 
    FROM returpasien r 
    INNER JOIN databarang d ON r.kode_brng = d.kode_brng 
    WHERE r.no_rawat='$no_rawat'
");
while($r = $q_retur->fetch_assoc()) {
    $val = (float)$r['total'];
    $details[] = [
        'kategori' => 'Retur Obat',
        'nama' => 'RETUR: ' . $r['nama_brng'] . ' (' . $r['jml'] . ')',
        'biaya' => -$val // Negatif
    ];
    $total_all -= $val;
}

// 6. LABORATORIUM
$q_lab = $koneksi->query("
    SELECT j.nm_perawatan, p.biaya 
    FROM periksa_lab p 
    INNER JOIN jns_perawatan_lab j ON p.kd_jenis_prw = j.kd_jenis_prw 
    WHERE p.no_rawat='$no_rawat'
");
while($r = $q_lab->fetch_assoc()) {
    $details[] = ['kategori' => 'Laboratorium', 'nama' => $r['nm_perawatan'], 'biaya' => (float)$r['biaya']];
    $total_all += (float)$r['biaya'];
}

// 7. RADIOLOGI
$q_rad = $koneksi->query("
    SELECT j.nm_perawatan, p.biaya 
    FROM periksa_radiologi p 
    INNER JOIN jns_perawatan_radiologi j ON p.kd_jenis_prw = j.kd_jenis_prw 
    WHERE p.no_rawat='$no_rawat'
");
while($r = $q_rad->fetch_assoc()) {
    $details[] = ['kategori' => 'Radiologi', 'nama' => $r['nm_perawatan'], 'biaya' => (float)$r['biaya']];
    $total_all += (float)$r['biaya'];
}

// 8. OPERASI (Total Paket)
$q_op = $koneksi->query("
    SELECT p.nm_perawatan, 
    (t.biayaoperator1+t.biayaoperator2+t.biayaoperator3+t.biayaasisten_operator1+t.biayaasisten_operator2+t.biayadokter_anestesi+t.biayaasisten_anestesi+t.biayaasisten_anestesi2+t.biayadokter_anak+t.biayaperawaat_resusitas+t.biayabidan+t.biayabidan2+t.biayabidan3+t.biayaperawat_luar+t.biayasewaok+t.biayaalat+t.akomodasi+t.bagian_rs+t.biaya_omloop+t.biaya_omloop2+t.biaya_omloop3+t.biaya_omloop4+t.biaya_omloop5+t.biayasarpras+t.biaya_dokter_pjanak+t.biaya_dokter_umum) as total 
    FROM operasi t 
    INNER JOIN paket_operasi p ON t.kode_paket = p.kode_paket 
    WHERE t.no_rawat='$no_rawat'
");
while($r = $q_op->fetch_assoc()) {
    $details[] = ['kategori' => 'Operasi', 'nama' => $r['nm_perawatan'], 'biaya' => (float)$r['total']];
    $total_all += (float)$r['total'];
}

// 9. TAMBAHAN
$q_add = $koneksi->query("SELECT nama_biaya, besar FROM tambahan_biaya WHERE no_rawat='$no_rawat'");
while($r = $q_add->fetch_assoc()) {
    $details[] = ['kategori' => 'Tambahan', 'nama' => $r['nama_biaya'], 'biaya' => (float)$r['besar']];
    $total_all += (float)$r['besar'];
}

// 10. PENGURANGAN
$q_min = $koneksi->query("SELECT nama_pengurangan, besar FROM pengurangan_biaya WHERE no_rawat='$no_rawat'");
while($r = $q_min->fetch_assoc()) {
    $details[] = ['kategori' => 'Pengurangan', 'nama' => $r['nama_pengurangan'], 'biaya' => -(float)$r['besar']];
    $total_all -= (float)$r['besar'];
}

echo json_encode(['data' => $details, 'total' => $total_all]);
?>