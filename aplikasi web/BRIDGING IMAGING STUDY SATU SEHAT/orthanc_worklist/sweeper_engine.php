<?php
// htdocs/orthanc_worklist/sweeper_engine.php
// AGEN C: THE SWEEPER (Penyapu Ranjau)
// Tugas: Memastikan gambar yang tertahan (WAIT) segera dikirim begitu ID ServiceRequest tersedia.

require_once 'config.php';

// Konfigurasi Orthanc (Sesuaikan user/pass jika beda)
define('ORTHANC_URL', 'http://localhost:8042');
define('ORTHANC_USER', 'ichsan');
define('ORTHANC_PASS', 'epotoransu');
define('ORTHANC_MODALITY', 'DCMROUTER'); // Nama Modality Router di orthanc.json

ob_start();
header('Content-Type: application/json');
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Jakarta');

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) throw new Exception("DB Connection Failed");

    $today = date('Y-m-d');
    
    // 1. CARI TARGET (Sudah WL, Belum Sent SS)
    $sql_target = "
        SELECT log.noorder, log.nomor_rm 
        FROM bridging_orthanc_log log
        WHERE log.status_wl = '1' 
        AND log.status_sent_ss = '0'
        AND DATE(log.tgl_request) = '$today'
    ";
    
    $q = $conn->query($sql_target);
    $processed = 0;
    $logs = [];

    if ($q && $q->num_rows > 0) {
        while ($row = $q->fetch_assoc()) {
            $acsn = $row['noorder'];

            // 2. CEK APAKAH ID SATUSEHAT SUDAH ADA? (Syarat Utama)
            // Pastikan nama tabel bridging sesuai DB Khanza
            $sql_check_ss = "SELECT id_servicerequest FROM satu_sehat_servicerequest_radiologi WHERE noorder = '$acsn' LIMIT 1";
            $res_ss = $conn->query($sql_check_ss);
            
            $ss_ready = false;
            if ($res_ss && $res_ss->num_rows > 0) {
                $d = $res_ss->fetch_assoc();
                if (!empty($d['id_servicerequest'])) {
                    $ss_ready = true;
                }
            }

            if (!$ss_ready) {
                // Skip, Java belum kirim ServiceRequest
                continue; 
            }

            // 3. JIKA SS READY -> TANYA ORTHANC (Apakah gambarnya sudah masuk?)
            // Kita cari Study ID berdasarkan AccessionNumber
            $payload = [
                "Level" => "Study",
                "Expand" => true,
                "Query" => [ "AccessionNumber" => $acsn ]
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, ORTHANC_URL . "/tools/find");
            curl_setopt($ch, CURLOPT_USERPWD, ORTHANC_USER . ":" . ORTHANC_PASS);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $res_orthanc = curl_exec($ch);
            curl_close($ch);

            $studies = json_decode($res_orthanc, true);

            if (!empty($studies)) {
                // Gambar Ditemukan di Orthanc!
                // Ambil ID Study Orthanc pertama (biasanya cuma 1)
                $orthanc_id = $studies[0]['ID'];

                // 4. EKSEKUSI KIRIM KE ROUTER
                $ch2 = curl_init();
                curl_setopt($ch2, CURLOPT_URL, ORTHANC_URL . "/modalities/" . ORTHANC_MODALITY . "/store");
                curl_setopt($ch2, CURLOPT_USERPWD, ORTHANC_USER . ":" . ORTHANC_PASS);
                curl_setopt($ch2, CURLOPT_POST, 1);
                curl_setopt($ch2, CURLOPT_POSTFIELDS, $orthanc_id); // Body isinya ID Study
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                $res_send = curl_exec($ch2);
                $http_code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                curl_close($ch2);

                if ($http_code == 200) {
                    // 5. UPDATE STATUS DB (Terkirim)
                    $conn->query("UPDATE bridging_orthanc_log SET status_sent_ss='1', waktu_sent_ss=NOW() WHERE noorder='$acsn'");
                    $processed++;
                    $logs[] = "SENT: $acsn -> Router";
                } else {
                    $logs[] = "ERR SEND: $acsn (Orthanc Code $http_code)";
                }
            } else {
                // SS Ready, tapi Gambar belum masuk Orthanc (Radiografer belum foto)
                // Biarkan saja, nanti Agen B (Lua) yang handle saat gambar masuk.
            }
        }
    }

    ob_clean();
    echo json_encode([
        "status" => "success",
        "swept" => $processed,
        "logs" => $logs
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
}
?>