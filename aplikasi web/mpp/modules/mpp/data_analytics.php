<?php
// File: modules/mpp/data_analytics.php
require_once '../../config/database.php';
require_once '../../helpers/auth_helper.php';

header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(0);

$response = [];

try {
    // ---------------------------------------------------------
    // CHART 1: TOP 5 ALASAN SKRINING (Penyebab Utama)
    // ---------------------------------------------------------
    // Kita hitung jumlah 'Ya' untuk setiap param1 s.d param16
    $sql_top = "SELECT 
        SUM(param1='Ya') as p1, SUM(param2='Ya') as p2, SUM(param3='Ya') as p3, SUM(param4='Ya') as p4,
        SUM(param5='Ya') as p5, SUM(param6='Ya') as p6, SUM(param7='Ya') as p7, SUM(param8='Ya') as p8,
        SUM(param9='Ya') as p9, SUM(param10='Ya') as p10, SUM(param11='Ya') as p11, SUM(param12='Ya') as p12,
        SUM(param13='Ya') as p13, SUM(param14='Ya') as p14, SUM(param15='Ya') as p15, SUM(param16='Ya') as p16
        FROM mpp_skrining";
    
    $row_top = fetchOne($pdo, $sql_top);
    
    // Mapping Label (Sesuai Form Java)
    $labels_map = [
        'p1' => "Keluhan Pembiayaan", 'p2' => "Tunda Diagnostik", 'p3' => "Klaim Over Limit", 'p4' => "Risiko Komplain",
        'p5' => "Sering Masuk IGD", 'p6' => "Lansia Ketergantungan", 'p7' => "Kasus Kompleks", 'p8' => "Pulang APS",
        'p9' => "Tidak Ada Keluarga", 'p10' => ">2 Dokter Spesialis", 'p11' => "Tolak Diagnostik", 'p12' => "Tolak Keperawatan",
        'p13' => "Tolak Medis", 'p14' => "Tunda Medis", 'p15' => "Mental/Narkoba/Terlantar", 'p16' => "Kontinuitas Discharge"
    ];

    $data_top = [];
    foreach($row_top as $key => $val) {
        $data_top[$labels_map[$key]] = (int)$val;
    }
    // Sort Descending & Ambil Top 5
    arsort($data_top);
    $top5 = array_slice($data_top, 0, 5);
    
    $response['chart1'] = [
        'labels' => array_keys($top5),
        'values' => array_values($top5)
    ];

    // ---------------------------------------------------------
    // CHART 2: PIE CHART RISIKO PASIEN
    // ---------------------------------------------------------
    // Klasifikasi: 
    // Rendah (0-1 parameter 'Ya')
    // Sedang (2-3 parameter 'Ya')
    // Tinggi (>3 parameter 'Ya')
    
    // Ambil raw data semua skrining
    $raw_skrining = $pdo->query("SELECT * FROM mpp_skrining")->fetchAll();
    $risiko = ['Rendah' => 0, 'Sedang' => 0, 'Tinggi' => 0];

    foreach($raw_skrining as $r) {
        $count_ya = 0;
        for($i=1; $i<=16; $i++) { if($r["param$i"] == 'Ya') $count_ya++; }
        
        if($count_ya <= 1) $risiko['Rendah']++;
        elseif($count_ya <= 3) $risiko['Sedang']++;
        else $risiko['Tinggi']++;
    }

    $response['chart2'] = [
        'labels' => array_keys($risiko),
        'values' => array_values($risiko)
    ];

    // ---------------------------------------------------------
    // CHART 3: MASALAH TERBANYAK (FORM A)
    // ---------------------------------------------------------
    $sql_masalah = "SELECT m.nama_masalah, COUNT(em.kode_masalah) as total 
                    FROM mpp_evaluasi_masalah em
                    JOIN master_masalah_mpp m ON em.kode_masalah = m.kode_masalah
                    GROUP BY em.kode_masalah
                    ORDER BY total DESC LIMIT 5";
    $res_masalah = $pdo->query($sql_masalah)->fetchAll();
    
    $label_masalah = [];
    $val_masalah = [];
    foreach($res_masalah as $r) {
        $label_masalah[] = substr($r['nama_masalah'], 0, 25) . '...'; // Potong nama panjang
        $val_masalah[] = (int)$r['total'];
    }

    $response['chart3'] = [
        'labels' => $label_masalah,
        'values' => $val_masalah
    ];

    // ---------------------------------------------------------
    // CHART 4: KINERJA PETUGAS (Top Performance)
    // ---------------------------------------------------------
    // Hitung jumlah pasien unik yang diskrining per petugas
    $sql_petugas = "SELECT p.nama, COUNT(DISTINCT s.no_rawat) as total 
                    FROM mpp_skrining s 
                    JOIN pegawai p ON s.nip = p.nik 
                    GROUP BY s.nip 
                    ORDER BY total DESC LIMIT 5";
    $res_petugas = $pdo->query($sql_petugas)->fetchAll();

    $response['chart4'] = $res_petugas; // Kirim raw array untuk tabel/list

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);

// Helper function fetchOne (Duplikat dari handler agar file ini mandiri)
function fetchOne($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>