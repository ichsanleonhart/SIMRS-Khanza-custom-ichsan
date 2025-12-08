<?php
// injector_engine.php
// VERSI 5.0: DYNAMIC LIMIT SUPPORT
// Memungkinkan Admin Panel meminta lebih dari 10 data sekaligus.

ob_start();
session_start(); 
ini_set('display_errors', 0);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Jakarta');
header('Content-Type: application/json');

require_once 'config.php';

$debug_trace = [];
function add_log($msg) { global $debug_trace; $debug_trace[] = "[" . date('H:i:s') . "] " . $msg; }
function json_response($status, $msg, $data = []) {
    global $debug_trace; ob_clean();
    echo json_encode(array_merge($data, ["status" => $status, "msg" => $msg, "debug_log" => $debug_trace])); exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) json_response("error", "DB Error");

// --- API PUBLIC (LOG) ---
if (isset($_GET['mode']) && $_GET['mode'] === 'view_log') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    // FIX: Menerima limit dari parameter, default 10, max 1000 (biar ga crash)
    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 1000) : 10; 
    $offset = ($page - 1) * $limit;
    
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    $where = "WHERE 1=1";
    if (!empty($search)) $where .= " AND (nama_pasien LIKE '%$search%' OR no_rm LIKE '%$search%' OR acsn_baru LIKE '%$search%')";
    if (!empty($_GET['date_start']) && !empty($_GET['date_end'])) $where .= " AND (DATE(waktu_suntik) BETWEEN '{$_GET['date_start']}' AND '{$_GET['date_end']}')";
    
    $total = $conn->query("SELECT COUNT(*) as t FROM orthanc_injector_log $where")->fetch_assoc()['t'];
    $q = $conn->query("SELECT * FROM orthanc_injector_log $where ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $data = []; while($r = $q->fetch_assoc()) $data[] = $r;
    
    json_response("success", "OK", ["data" => $data, "pagination" => ["current_page" => $page, "total_pages" => ceil($total/$limit), "limit" => $limit]]);
}

// --- ADMIN API ---
if (isset($_GET['mode'])) {
    if (!isset($_SESSION['user_admin'])) json_response("error", "Access Denied");
    $mode = $_GET['mode'];

    function get_token() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, SS_BASE_URL . "/oauth2/v1/accesstoken?grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['client_id' => SS_CLIENT_ID, 'client_secret' => SS_CLIENT_SECRET]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $res = json_decode(curl_exec($ch), true); curl_close($ch);
        return $res['access_token'] ?? false;
    }

    if ($mode === 'check_ss') {
        $acsn = trim($_POST['acsn']); add_log("CHECK: $acsn");
        $token = get_token(); if(!$token) json_response("error", "Token Fail");
        
        $url = SS_BASE_URL . "/fhir-r4/v1/ImagingStudy?identifier=" . urlencode("http://sys-ids.kemkes.go.id/acsn/".SS_ORG_ID."|".$acsn);
        add_log("URL: $url");
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $json = json_decode(curl_exec($ch), true); curl_close($ch);

        if (($json['total'] ?? 0) == 0) json_response("not_found", "Data 0");
        
        $r = $json['entry'][0]['resource'];
        json_response("found", "OK", ['id' => $r['id'], 'ss_status' => $r['status'] ?? '?', 'series_count' => $r['numberOfSeries'], 'instance_count' => $r['numberOfInstances']]);
    }

    if ($mode === 'delete_ss') {
        $acsn = trim($_POST['acsn']); add_log("HARD DELETE: $acsn");
        $token = get_token(); if(!$token) json_response("error", "Token Fail");

        // Search ID
        $url = SS_BASE_URL . "/fhir-r4/v1/ImagingStudy?identifier=" . urlencode("http://sys-ids.kemkes.go.id/acsn/".SS_ORG_ID."|".$acsn);
        $ch = curl_init($url); curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $search = json_decode(curl_exec($ch), true); curl_close($ch);
        $rid = $search['entry'][0]['resource']['id'] ?? null;
        if(!$rid) json_response("error", "Resource ID Not Found");

        // Delete
        $ch = curl_init(SS_BASE_URL . "/fhir-r4/v1/ImagingStudy/$rid");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{}"); // Fix empty body
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $res = curl_exec($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        add_log("DELETE HTTP: $http. Body: $res");

        if($http == 200 || $http == 204) {
            $conn->query("INSERT INTO trackersql (tanggal, sqle, usere) VALUES (NOW(), 'Hard Delete $acsn', '{$_SESSION['user_admin']}')");
            json_response("success", "Deleted");
        } else json_response("error", "Fail Code: $http");
    }
}

// --- AUTO INJECTOR ---
try {
    $start = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
    $end = $_GET['end_date'] ?? date('Y-m-d');
    $dq = str_replace('-', '', $start) . '-' . str_replace('-', '', $end);
    $processed = 0; $logs = [];

    $ch = curl_init(ORTHANC_URL . "/tools/find");
    curl_setopt($ch, CURLOPT_USERPWD, ORTHANC_USER.":".ORTHANC_PASS);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["Level" => "Study", "Expand" => true, "Query" => ["StudyDate" => $dq]]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $studies = json_decode(curl_exec($ch), true); curl_close($ch);

    if ($studies) {
        foreach ($studies as $s) {
            $pid = $s['PatientMainDicomTags']['PatientID'] ?? '';
            $acsn = $s['MainDicomTags']['AccessionNumber'] ?? '';
            if (!$pid || strpos($acsn, 'PR') === 0) continue;

            $sql = "SELECT pr.noorder, p.nm_pasien, jpr.nm_perawatan, ss.id_servicerequest FROM permintaan_radiologi pr 
                    JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis 
                    LEFT JOIN permintaan_pemeriksaan_radiologi ppr ON pr.noorder = ppr.noorder 
                    LEFT JOIN jns_perawatan_radiologi jpr ON ppr.kd_jenis_prw = jpr.kd_jenis_prw 
                    LEFT JOIN satu_sehat_servicerequest_radiologi ss ON ss.noorder = pr.noorder AND ss.kd_jenis_prw = ppr.kd_jenis_prw 
                    WHERE rp.no_rkm_medis = '$pid' AND pr.tgl_permintaan BETWEEN '$start' AND '$end' 
                    AND pr.noorder NOT IN (SELECT acsn_baru FROM orthanc_injector_log WHERE no_rm = '$pid' AND acsn_baru IS NOT NULL) LIMIT 1";
            
            $row = $conn->query($sql)->fetch_assoc();
            if ($row && !empty($row['id_servicerequest'])) {
                $new_acsn = $row['noorder'];
                $mod = ["Replace" => ["AccessionNumber" => $new_acsn, "PatientName" => strtoupper($row['nm_pasien']), "StudyDescription" => strtoupper($row['nm_perawatan'] ?: "RAD")], "Force" => true];
                
                $ch = curl_init(ORTHANC_URL . "/studies/{$s['ID']}/modify");
                curl_setopt($ch, CURLOPT_USERPWD, ORTHANC_USER.":".ORTHANC_PASS); curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($mod)); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $new_id = json_decode(curl_exec($ch), true)['ID'] ?? null; curl_close($ch);

                if ($new_id) {
                    $ch = curl_init(ORTHANC_URL . "/studies/{$s['ID']}"); curl_setopt($ch, CURLOPT_USERPWD, ORTHANC_USER.":".ORTHANC_PASS); curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE"); curl_exec($ch); curl_close($ch);
                    $stmt = $conn->prepare("INSERT INTO orthanc_injector_log (no_rawat, no_rm, nama_pasien, acsn_lama, acsn_baru, orthanc_study_id_lama, orthanc_study_id_baru) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssss", $row['no_rawat'], $pid, $row['nm_pasien'], $acsn, $new_acsn, $s['ID'], $new_id);
                    $stmt->execute();
                    $processed++; $logs[] = "{$row['nm_pasien']} -> $new_acsn";
                }
            }
        }
    }
    $v = []; $q = $conn->query("SELECT * FROM orthanc_injector_log ORDER BY id DESC LIMIT 5"); while($r=$q->fetch_assoc()) $v[]=$r;
    json_response("success", "OK", ["processed" => $processed, "logs" => $logs, "data_table" => $v]);
} catch (Exception $e) { json_response("error", $e->getMessage()); }
?>