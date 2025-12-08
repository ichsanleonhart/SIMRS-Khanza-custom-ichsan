<?php
// rescue_sender.php
// SCRIPT PENYELAMAT: Mendorong paksa data berdasarkan rentang tanggal.
// Input: $_GET['start_date'], $_GET['end_date']

require_once 'config.php';

// Konfigurasi Tambahan (Sesuaikan jika perlu)
define('TARGET_MODALITY', 'SATUSEHAT_ROUTER'); // Nama di orthanc.json

ob_start();
header('Content-Type: application/json');
ini_set('display_errors', 0); // Matikan error display agar JSON valid
date_default_timezone_set('Asia/Jakarta');

$response = [];
$logs = [];
$pushed_count = 0;

try {
    // 1. Validasi Input Tanggal
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
    $end_date   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');

    // Format Orthanc Query: YYYYMMDD-YYYYMMDD
    $orthanc_date_query = str_replace('-', '', $start_date) . '-' . str_replace('-', '', $end_date);

    // 2. KONEKSI DB (Gatekeeper Logic)
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) throw new Exception("DB Error: " . $conn->connect_error);

    // 3. AMBIL DATA ORTHANC (Range Tanggal)
    $payload = [
        "Level" => "Study",
        "Expand" => true,
        "Query" => [ "StudyDate" => $orthanc_date_query ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, ORTHANC_URL . "/tools/find");
    curl_setopt($ch, CURLOPT_USERPWD, ORTHANC_USER . ":" . ORTHANC_PASS);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res_orthanc = curl_exec($ch);
    
    if(curl_errno($ch)) {
        throw new Exception("Orthanc Connection Error: " . curl_error($ch));
    }
    curl_close($ch);

    $studies = json_decode($res_orthanc, true);

    if (!empty($studies) && is_array($studies)) {
        foreach ($studies as $study) {
            $acsn = $study['MainDicomTags']['AccessionNumber'] ?? '';
            $pid  = $study['PatientMainDicomTags']['PatientID'] ?? '';
            $pname = $study['PatientMainDicomTags']['PatientName'] ?? '';
            $orthanc_id = $study['ID'];

            // Bersihkan ACSN
            $acsn = trim($acsn);

            // Filter: Hanya yang format PR (Orderan Khanza)
            if (empty($acsn) || strpos($acsn, 'PR') !== 0) continue;

            // 4. CEK GATEKEEPER (Apakah ID ServiceRequest Ada?)
            $acsn_safe = $conn->real_escape_string($acsn);
            $sql = "SELECT id_servicerequest FROM satu_sehat_servicerequest_radiologi WHERE noorder = '$acsn_safe' LIMIT 1";
            $q_gate = $conn->query($sql);
            
            $is_ready = false;
            if ($q_gate && $q_gate->num_rows > 0) {
                $r = $q_gate->fetch_assoc();
                if (!empty($r['id_servicerequest'])) {
                    $is_ready = true;
                }
            }

            if ($is_ready) {
                // 5. EKSEKUSI KIRIM (Store to Modality)
                $ch_send = curl_init();
                curl_setopt($ch_send, CURLOPT_URL, ORTHANC_URL . "/modalities/" . TARGET_MODALITY . "/store");
                curl_setopt($ch_send, CURLOPT_USERPWD, ORTHANC_USER . ":" . ORTHANC_PASS);
                curl_setopt($ch_send, CURLOPT_POST, 1);
                curl_setopt($ch_send, CURLOPT_POSTFIELDS, $orthanc_id); // Body = UUID Study
                curl_setopt($ch_send, CURLOPT_RETURNTRANSFER, true);
                $res_send = curl_exec($ch_send);
                $http_code = curl_getinfo($ch_send, CURLINFO_HTTP_CODE);
                curl_close($ch_send);

                if ($http_code == 200) {
                    $logs[] = "✅ RESCUE SUCCESS: $acsn ($pname) -> Sent to Router";
                    $pushed_count++;
                } else {
                    $logs[] = "❌ RESCUE FAIL: $acsn - Orthanc Error $http_code";
                }
            } else {
                $logs[] = "⚠️ SKIP: $acsn - ServiceRequest ID Belum Ada di DB";
            }
        }
    } else {
        $logs[] = "Info: Tidak ada study di Orthanc pada rentang $start_date s/d $end_date";
    }

    ob_clean();
    echo json_encode([
        "status" => "finished",
        "pushed_total" => $pushed_count,
        "logs" => $logs
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
}
?>