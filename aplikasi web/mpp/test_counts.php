<?php
require_once 'c:/xampp/htdocs/mpp/config/database.php';

// Query 1: Ranap Monitoring Kunjungan (data_handler.php)
// We just replicate the count logic
$where = " WHERE 1=1 ";
$where .= " AND (ki.stts_pulang = '-' OR ki.stts_pulang = '') ";
$sql_count_ranap = "SELECT COUNT(DISTINCT ki.no_rawat) as total 
              FROM kamar_inap ki 
              JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
              JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
              LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
              LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
              LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
              $where";

$stmt = $pdo->query($sql_count_ranap);
$res_ranap = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Ranap monitoring count: " . $res_ranap['total'] . "\n";


// Query 2: Edokter Ranap
$sql_edokter = "SELECT COUNT(DISTINCT ki.no_rawat) as total
            FROM kamar_inap ki
            JOIN reg_periksa r ON ki.no_rawat = r.no_rawat 
            JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis 
            LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
            LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
            LEFT JOIN penjab pj ON r.kd_pj = pj.kd_pj 
            LEFT JOIN dpjp_ranap dr ON ki.no_rawat = dr.no_rawat
            LEFT JOIN dokter d_dpjp ON dr.kd_dokter = d_dpjp.kd_dokter
            WHERE 1=1 AND (ki.stts_pulang = '-' OR ki.stts_pulang = '') ";

$stmt2 = $pdo->query($sql_edokter);
$res_edokter = $stmt2->fetch(PDO::FETCH_ASSOC);
echo "Edokter ranap count: " . $res_edokter['total'] . "\n";
