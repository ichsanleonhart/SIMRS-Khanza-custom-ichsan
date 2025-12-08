<?php
// htdocs/orthanc_worklist/worklist_generator.php
// AGEN A: THE SUPPLIER (UI SUPPORT EDITION)

require_once 'config.php';

ob_start();
header('Content-Type: application/json');
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Jakarta');

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) throw new Exception("DB Connection Failed");

    $today = date('Y-m-d');

    // 1. SYNC DATA (Khanza -> Log)
    $sql_sync = "
        INSERT IGNORE INTO bridging_orthanc_log (noorder, no_rawat, nomor_rm, nama_pasien, tgl_request, status_wl)
        SELECT pr.noorder, pr.no_rawat, p.no_rkm_medis, p.nm_pasien, CONCAT(pr.tgl_permintaan, ' ', pr.jam_permintaan), '0'
        FROM permintaan_radiologi pr
        JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
        JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        WHERE pr.tgl_permintaan = '$today'
    ";
    $conn->query($sql_sync);

    // 2. GENERATE PENDING
    $sql_pending = "
        SELECT log.noorder, log.nomor_rm, log.nama_pasien, p.tgl_lahir, p.jk, d.nm_dokter, jpr.nm_perawatan 
        FROM bridging_orthanc_log log
        JOIN permintaan_radiologi pr ON log.noorder = pr.noorder
        JOIN pasien p ON log.nomor_rm = p.no_rkm_medis
        LEFT JOIN dokter d ON pr.dokter_perujuk = d.kd_dokter
        LEFT JOIN permintaan_pemeriksaan_radiologi ppr ON pr.noorder = ppr.noorder
        LEFT JOIN jns_perawatan_radiologi jpr ON ppr.kd_jenis_prw = jpr.kd_jenis_prw
        WHERE log.status_wl = '0' AND DATE(log.tgl_request) = '$today' LIMIT 20
    ";

    $q = $conn->query($sql_pending);
    $generated = 0;
    $logs = [];

    if ($q && $q->num_rows > 0) {
        while ($row = $q->fetch_assoc()) {
            $acsn = $row['noorder'];
            // Sanitasi
            $pat_name = substr(strtoupper(preg_replace("/[^a-zA-Z0-9\s\^]/", "", $row['nama_pasien'])), 0, 60);
            $pat_id   = preg_replace("/[^a-zA-Z0-9]/", "", $row['nomor_rm']);
            $dob      = str_replace('-', '', $row['tgl_lahir']); 
            $sex      = ($row['jk'] == 'L') ? 'M' : 'F';
            $doc_name = substr(strtoupper(preg_replace("/[^a-zA-Z0-9\s\^.]/", "", $row['nm_dokter'])), 0, 60);
            $desc     = substr(strtoupper(preg_replace("/[^a-zA-Z0-9\s]/", " ", $row['nm_perawatan'])), 0, 60);
            $study_uid = ORG_ROOT_UID . date('YmdHis') . '.' . rand(1000,9999);

            $dump_content = "
(0010,0010) PN [$pat_name]
(0010,0020) LO [$pat_id]
(0010,0030) DA [$dob]
(0010,0040) CS [$sex]
(0008,0050) SH [$acsn]
(0020,000d) UI [$study_uid]
(0008,0090) PN [$doc_name]
(0008,1030) LO [$desc]
(0040,0100) SQ
(fffe,e000) na
(0008,0060) CS [CR]
(0040,0001) AE [ORTHANC_WL]
(0040,0002) DA [" . date('Ymd') . "]
(0040,0003) TM [" . date('His') . "]
(0040,0007) LO [$desc]
(0040,0009) SH [$acsn]
(fffe,e00d) na
(fffe,e0dd) na
(0020,0010) SH [$acsn]
";
            $dump_file = WL_OUTPUT_DIR . "temp_" . $acsn . ".dump";
            $wl_file   = WL_OUTPUT_DIR . $acsn . ".wl";

            file_put_contents($dump_file, trim($dump_content));
            $cmd = '"' . DCMTK_BIN . '" "' . $dump_file . '" "' . $wl_file . '"';
            exec($cmd);
            if (file_exists($dump_file)) unlink($dump_file);

            if (file_exists($wl_file) && filesize($wl_file) > 0) {
                $conn->query("UPDATE bridging_orthanc_log SET status_wl='1', waktu_generate=NOW() WHERE noorder='$acsn'");
                $generated++;
                $logs[] = "OK: $acsn";
            } else {
                $logs[] = "GAGAL: $acsn";
            }
        }
    }

    // 3. AMBIL DATA UNTUK TABEL (BARU DITAMBAHKAN)
    $sql_view = "SELECT * FROM bridging_orthanc_log WHERE DATE(tgl_request) = '$today' ORDER BY tgl_request DESC";
    $res_view = $conn->query($sql_view);
    $data_tabel = [];
    if($res_view) {
        while($r = $res_view->fetch_assoc()) $data_tabel[] = $r;
    }

    ob_clean();
    echo json_encode([
        "status" => "success",
        "generated" => $generated,
        "logs" => $logs,
        "data" => $data_tabel // Data untuk Dashboard
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
}
?>