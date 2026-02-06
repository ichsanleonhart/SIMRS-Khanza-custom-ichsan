<?php
/*
 * File: api/data_indikator_ranap.php
 * Fungsi: Menghitung Indikator Ranap dengan Presisi Detik (Time-Based)
 * Update: Menggunakan Jam Masuk & Jam Keluar untuk akurasi BOR.
 */

error_reporting(0);
ini_set('display_errors', 0);

if(file_exists(__DIR__ . '/../../conf/conf.php')) {
    require_once(__DIR__ . '/../../conf/conf.php');
} else {
    require_once(__DIR__ . '/../conf/conf.php');
}

header('Content-Type: application/json');
$koneksi = bukakoneksi();

session_start();
if (!isset($_SESSION['casemix_login'])) {
    http_response_code(403); echo json_encode(['error' => 'Akses ditolak.']); exit;
}

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// Definisi Range Waktu Filter (Sampai detik terakhir)
$start_str = $tgl_awal . " 00:00:00";
$end_str   = $tgl_akhir . " 23:59:59";

// 1. Hitung Periode Hari (t)
$start = new DateTime($tgl_awal);
$end = new DateTime($tgl_akhir);
$days_period = $end->diff($start)->days + 1;

// 2. Hitung Jumlah Bed (A)
$total_bed = 0;
$q_bed = mysqli_query($koneksi, "SELECT COUNT(kd_kamar) as total FROM kamar WHERE statusdata='1'");
if($r_bed = mysqli_fetch_assoc($q_bed)) $total_bed = (int)$r_bed['total'];
if($total_bed == 0) $total_bed = 1;

// 3. QUERY PRESISI TINGGI (Hari Perawatan dalam Detik)
// Logika: Hitung selisih detik antara Masuk & Keluar, tapi POTONG hanya yang berada dalam $start_str s/d $end_str
$sql_hp = "
SELECT 
    SUM(
        CASE 
            -- Jika pasien sudah pulang
            WHEN tgl_keluar <> '0000-00-00' THEN
                GREATEST(0, TIMESTAMPDIFF(SECOND,
                    GREATEST(CONCAT(tgl_masuk, ' ', jam_masuk), '$start_str'),
                    LEAST(CONCAT(tgl_keluar, ' ', jam_keluar), '$end_str')
                ))
            -- Jika pasien masih dirawat (belum pulang)
            ELSE
                GREATEST(0, TIMESTAMPDIFF(SECOND,
                    GREATEST(CONCAT(tgl_masuk, ' ', jam_masuk), '$start_str'),
                    '$end_str' -- Hitung occupancy sampai akhir periode filter
                ))
        END
    ) as total_detik
FROM kamar_inap
WHERE 
    -- Ambil semua row yang beririsan dengan periode filter
    (CONCAT(tgl_masuk, ' ', jam_masuk) <= '$end_str') AND
    (
        (tgl_keluar <> '0000-00-00' AND CONCAT(tgl_keluar, ' ', jam_keluar) >= '$start_str') OR
        (tgl_keluar = '0000-00-00')
    )
";

$q_hp = mysqli_query($koneksi, $sql_hp);
$r_hp = mysqli_fetch_assoc($q_hp);
// Konversi Detik ke Hari (Presisi Decimal)
$hari_perawatan = floatval($r_hp['total_detik']) / 86400; 


// 4. Hitung Pasien Keluar & Mati (Exclude Pindah Kamar untuk Global)
$sql_stat = "SELECT 
    COUNT(no_rawat) as total_keluar,
    SUM(IF(stts_pulang = 'Meninggal', 1, 0)) as total_mati,
    SUM(IF(stts_pulang = 'Meninggal' AND TIMESTAMPDIFF(HOUR, CONCAT(tgl_masuk,' ',jam_masuk), CONCAT(tgl_keluar,' ',jam_keluar)) >= 48, 1, 0)) as mati_lebih_48
FROM kamar_inap 
WHERE tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
  AND stts_pulang != 'Pindah Kamar'";

$q_stat = mysqli_query($koneksi, $sql_stat);
$r_stat = mysqli_fetch_assoc($q_stat);

$pasien_keluar  = (int)$r_stat['total_keluar'];
$pasien_mati    = (int)$r_stat['total_mati'];
$pasien_mati_48 = (int)$r_stat['mati_lebih_48'];

$pembagi_pasien = ($pasien_keluar == 0) ? 1 : $pasien_keluar;

// 5. Kalkulasi Indikator
$bor  = ($hari_perawatan / ($total_bed * $days_period)) * 100;
$alos = $hari_perawatan / $pembagi_pasien;
$toi  = (($total_bed * $days_period) - $hari_perawatan) / $pembagi_pasien;
$bto  = $pasien_keluar / $total_bed;
$gdr  = ($pasien_mati / $pembagi_pasien) * 1000;
$ndr  = ($pasien_mati_48 / $pembagi_pasien) * 1000;

echo json_encode([
    'periode' => ['hari' => $days_period],
    'data_dasar' => [
        'jumlah_bed' => $total_bed,
        'hari_perawatan' => number_format($hari_perawatan, 2, '.', ','), // Tampilkan 2 desimal
        'pasien_keluar' => $pasien_keluar,
        'pasien_mati' => $pasien_mati,
        'pasien_mati_48' => $pasien_mati_48
    ],
    'indikator' => [
        'bor' => round($bor, 2),
        'alos' => round($alos, 2),
        'toi' => round($toi, 2),
        'bto' => round($bto, 2),
        'gdr' => round($gdr, 2),
        'ndr' => round($ndr, 2)
    ]
]);
?>