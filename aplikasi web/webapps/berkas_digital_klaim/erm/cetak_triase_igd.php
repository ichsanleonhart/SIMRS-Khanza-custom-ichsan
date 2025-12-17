<?php
/*
 * File: erm/cetak_triase_igd.php
 * Fungsi: Preview Triase IGD (Mode View HTML)
 */

session_start();
require_once('../../conf/conf.php');
$koneksi = bukakoneksi();

$no_rawat = isset($_GET['no_rawat']) ? validTeks4($_GET['no_rawat'], 20) : '';

// --- LOGIKA DATA (SAMA DENGAN GENERATOR) ---

// 1. ROUTER SKALA
$skala_terdeteksi = 0;
$tabel_detail = "";
$tipe_triase = ""; 

if(mysqli_num_rows(mysqli_query($koneksi, "SELECT no_rawat FROM data_triase_igddetail_skala1 WHERE no_rawat='$no_rawat'")) > 0){
    $skala_terdeteksi = 1; $tabel_detail = "data_triase_igddetail_skala1"; $tipe_triase = "PRIMER";
}
elseif(mysqli_num_rows(mysqli_query($koneksi, "SELECT no_rawat FROM data_triase_igddetail_skala2 WHERE no_rawat='$no_rawat'")) > 0){
    $skala_terdeteksi = 2; $tabel_detail = "data_triase_igddetail_skala2"; $tipe_triase = "PRIMER";
}
elseif(mysqli_num_rows(mysqli_query($koneksi, "SELECT no_rawat FROM data_triase_igddetail_skala3 WHERE no_rawat='$no_rawat'")) > 0){
    $skala_terdeteksi = 3; $tabel_detail = "data_triase_igddetail_skala3"; $tipe_triase = "SEKUNDER";
}
elseif(mysqli_num_rows(mysqli_query($koneksi, "SELECT no_rawat FROM data_triase_igddetail_skala4 WHERE no_rawat='$no_rawat'")) > 0){
    $skala_terdeteksi = 4; $tabel_detail = "data_triase_igddetail_skala4"; $tipe_triase = "SEKUNDER";
}
elseif(mysqli_num_rows(mysqli_query($koneksi, "SELECT no_rawat FROM data_triase_igddetail_skala5 WHERE no_rawat='$no_rawat'")) > 0){
    $skala_terdeteksi = 5; $tabel_detail = "data_triase_igddetail_skala5"; $tipe_triase = "SEKUNDER";
}
else {
    die("<div style='text-align:center;margin-top:20px'><h3>Data Triase Kosong</h3><p>Belum ada inputan triase untuk No.Rawat: $no_rawat</p></div>");
}

// 2. CONFIG DISPLAY
$config = [
    'sub_judul' => '',
    'kode_berkas' => '001', 
    'warna_bg' => '#FFFFFF',
    'warna_txt' => '#000000'
];

switch ($skala_terdeteksi) {
    case 1: $config['sub_judul'] = "TRIASE SKALA 1 (RESUSITASI)"; $config['warna_bg'] = "#FF0000"; $config['warna_txt'] = "#FFFFFF"; break;
    case 2: $config['sub_judul'] = "TRIASE SKALA 2 (EMERGENCY)"; $config['warna_bg'] = "#FF0000"; $config['warna_txt'] = "#FFFFFF"; break;
    case 3: $config['sub_judul'] = "TRIASE SKALA 3 (URGENT)"; $config['warna_bg'] = "#FFFF00"; $config['warna_txt'] = "#000000"; break;
    case 4: $config['sub_judul'] = "TRIASE SKALA 4 (SEMI URGENT)"; $config['warna_bg'] = "#00FF00"; $config['warna_txt'] = "#000000"; break;
    case 5: $config['sub_judul'] = "TRIASE SKALA 5 (NON URGENT)"; $config['warna_bg'] = "#FFFFFF"; $config['warna_txt'] = "#000000"; break;
}

// 3. QUERY DATA UMUM (FIX ALAMAT)
$q_umum = "SELECT 
    p.nm_pasien, p.no_rkm_medis, p.tgl_lahir, p.jk, p.alamat, -- Pastikan p.alamat diambil
    rp.tgl_registrasi, 
    d.nm_dokter, 
    tri.*,
    mtmk.macam_kasus
FROM data_triase_igd tri
JOIN reg_periksa rp ON tri.no_rawat = rp.no_rawat
JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
LEFT JOIN master_triase_macam_kasus mtmk ON tri.kode_kasus = mtmk.kode_kasus
WHERE tri.no_rawat = '$no_rawat'";

$res_umum = mysqli_query($koneksi, $q_umum);
$d_umum = mysqli_fetch_assoc($res_umum);

// 4. DATA SPESIFIK & PERAWAT
$d_khusus = [];
$nik_perawat = "";

if ($tipe_triase == "PRIMER") {
    $q_primer = "SELECT * FROM data_triase_igdprimer WHERE no_rawat = '$no_rawat'";
    $d_khusus = mysqli_fetch_assoc(mysqli_query($koneksi, $q_primer));
    $nik_perawat = $d_khusus['nik'] ?? '';
} else {
    $q_sekunder = "SELECT * FROM data_triase_igdsekunder WHERE no_rawat = '$no_rawat'";
    $d_khusus = mysqli_fetch_assoc(mysqli_query($koneksi, $q_sekunder));
    $nik_perawat = $d_khusus['nik'] ?? '';
}

// 5. HELPER
$nama_perawat = "-";
if(!empty($nik_perawat)){
    $r_peg = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama FROM pegawai WHERE nik = '$nik_perawat'"));
    $nama_perawat = $r_peg['nama'] ?? '-';
}

$setting = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1"));
$logo_b64 = '../logo.php'; // Pakai path relative untuk preview HTML

function formatTgl($dt) { return ($dt && $dt!='0000-00-00 00:00:00') ? date('d-m-Y', strtotime($dt)) : '-'; }
function formatJam($dt) { return ($dt && $dt!='0000-00-00 00:00:00') ? date('H:i:s', strtotime($dt)) . ' WIB' : '-'; }

// QR Code (Preview pake API)
$qr_b64 = "";
if(!empty($nik_perawat)){
    $finger_code = $nik_perawat; 
    $r_finger = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SHA1(sidikjari.sidikjari) as finger FROM sidikjari WHERE id = (SELECT id FROM pegawai WHERE nik='$nik_perawat')"));
    if($r_finger && !empty($r_finger['finger'])) $finger_code = $r_finger['finger'];

    $tgl_tte = $d_khusus['tanggaltriase'] ?? date('Y-m-d H:i:s');
    $qr_content = "Dikeluarkan di " . $setting['nama_instansi'] . ", Kabupaten/Kota " . $setting['kabupaten'] . "\nDitandatangani secara elektronik oleh " . $nama_perawat . "\nID " . $finger_code . "\n" . $tgl_tte;
    $qr_b64 = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qr_content);
}

// 6. CHECKLIST
$checklist_data = [];
$master_skala = "master_triase_skala" . $skala_terdeteksi;
$kode_skala   = "kode_skala" . $skala_terdeteksi;
$pengkajian   = "pengkajian_skala" . $skala_terdeteksi;

$q_check = "SELECT mtp.nama_pemeriksaan, mts.$pengkajian as hasil 
            FROM $tabel_detail dtd 
            JOIN $master_skala mts ON dtd.$kode_skala = mts.$kode_skala 
            JOIN master_triase_pemeriksaan mtp ON mts.kode_pemeriksaan = mtp.kode_pemeriksaan 
            WHERE dtd.no_rawat = '$no_rawat' 
            ORDER BY mtp.kode_pemeriksaan ASC";
$res_check = mysqli_query($koneksi, $q_check);
while($row = mysqli_fetch_assoc($res_check)){
    $checklist_data[] = ['kategori' => $row['nama_pemeriksaan'], 'nilai' => $row['hasil']];
}

// 7. RENDER LAYOUT
?>
<div style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
    <button onclick="window.location.href='simpan_triase_igd.php?no_rawat=<?= urlencode($no_rawat) ?>'" 
            style="background: #28a745; color: white; font-weight: bold; padding: 12px 24px; border: none; border-radius: 50px; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
        <i style="margin-right: 8px;">💾</i> SIMPAN KE BERKAS DIGITAL
    </button>
    <br><br>
    <button onclick="window.close()" 
            style="background: #dc3545; color: white; font-weight: bold; padding: 10px 20px; border: none; border-radius: 50px; cursor: pointer; width: 100%;">
        TUTUP
    </button>
</div>

<div style="width: 210mm; min-height: 297mm; margin: 0 auto; background: white; padding: 10mm; box-shadow: 0 0 10px rgba(0,0,0,0.5);">
    <?php include 'layout_triase_main.php'; ?>
</div>

<style>
    body { background: #525659; margin: 0; padding: 20px; }
    @media print {
        body { background: white; padding: 0; }
        div[style*="fixed"] { display: none !important; } /* Hide Button */
        div[style*="width: 210mm"] { box-shadow: none; margin: 0; padding: 0; width: 100%; }
    }
</style>