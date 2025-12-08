<?php
// injector_engine.php
// VERSI ULTIMATE: Support Time-Travel Injection + Log Explorer API

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Jakarta');
header('Content-Type: application/json');

// --- KONFIGURASI DB ---
$db_host     = '192.168.1.2';
$db_user     = 'client';
$db_pass     = 'epotoransu';
$db_name     = 'sik_master';

// --- KONFIGURASI ORTHANC ---
$orthanc_url = "http://localhost:8042"; 
$orthanc_u   = "ichsan";      
$orthanc_p   = "epotoransu";  

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) throw new Exception("DB Error: " . $conn->connect_error);

    // ==========================================
    // MODE 1: VIEW LOG (API untuk Tabel)
    // ==========================================
    if (isset($_GET['mode']) && $_GET['mode'] === 'view_log') {
        $page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit  = 10;
        $offset = ($page - 1) * $limit;
        
        $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
        $date_start = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $date_end   = isset($_GET['date_end']) ? $_GET['date_end'] : '';

        // Build Query
        $where = "WHERE 1=1";
        if (!empty($search)) {
            $where .= " AND (nama_pasien LIKE '%$search%' OR no_rm LIKE '%$search%' OR acsn_baru LIKE '%$search%')";
        }
        if (!empty($date_start) && !empty($date_end)) {
            $where .= " AND (DATE(waktu_suntik) BETWEEN '$date_start' AND '$date_end')";
        }

        // Hitung Total Data (untuk Pagination)
        $q_count = $conn->query("SELECT COUNT(*) as total FROM orthanc_injector_log $where");
        $total_data = $q_count->fetch_assoc()['total'];
        $total_pages = ceil($total_data / $limit);

        // Ambil Data
        $sql = "SELECT * FROM orthanc_injector_log $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
        $q_log = $conn->query($sql);
        
        $data = [];
        while($r = $q_log->fetch_assoc()) { $data[] = $r; }

        ob_clean();
        echo json_encode([
            "status" => "success",
            "data" => $data,
            "pagination" => [
                "current_page" => $page,
                "total_pages" => $total_pages,
                "total_data" => $total_data
            ]
        ]);
        exit; // Stop di sini agar tidak menjalankan injektor
    }

    // ==========================================
    // MODE 2: INJECTOR ENGINE (Core Logic)
    // ==========================================
    
    // Tentukan Range Tanggal (Auto vs Manual)
    $start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) 
                  ? $_GET['start_date'] 
                  : date('Y-m-d', strtotime('-7 days')); // Default H-7

    $end_date   = isset($_GET['end_date']) && !empty($_GET['end_date']) 
                  ? $_GET['end_date'] 
                  : date('Y-m-d');

    $processed_count = 0;
    $logs = [];

    // Format Tanggal Orthanc
    $orthanc_date_query = str_replace('-', '', $start_date) . '-' . str_replace('-', '', $end_date);

    // Query Orthanc
    $payload = ["Level" => "Study", "Expand" => true, "Query" => ["StudyDate" => $orthanc_date_query]];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$orthanc_url/tools/find");
    curl_setopt($ch, CURLOPT_USERPWD, "$orthanc_u:$orthanc_p");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);

    $studies = json_decode($res, true);

    if (!empty($studies)) {
        foreach ($studies as $study) {
            $study_id_lama = $study['ID'];
            $patient_id    = $study['PatientMainDicomTags']['PatientID'] ?? ''; 
            $patient_name  = $study['PatientMainDicomTags']['PatientName'] ?? 'No Name';
            $current_acsn  = isset($study['MainDicomTags']['AccessionNumber']) ? $study['MainDicomTags']['AccessionNumber'] : "";

            if (empty($patient_id)) continue;
            if (strpos($current_acsn, 'PR') === 0) continue; // Skip jika sudah disuntik

            // -------------------------------------------------------
            // SMART SQL V2: Eksklusi Order yang Sudah Digunakan
            // -------------------------------------------------------
            $sql = "
                SELECT 
                    pr.noorder, 
                    pr.no_rawat, 
                    p.nm_pasien,
                    pr.informasi_tambahan,
                    jpr.nm_perawatan,
                    ss.id_servicerequest
                FROM permintaan_radiologi pr
                JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
                JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                LEFT JOIN permintaan_pemeriksaan_radiologi ppr ON pr.noorder = ppr.noorder
                LEFT JOIN jns_perawatan_radiologi jpr ON ppr.kd_jenis_prw = jpr.kd_jenis_prw
                LEFT JOIN satu_sehat_servicerequest_radiologi ss ON ss.noorder = pr.noorder 
                     AND ss.kd_jenis_prw = ppr.kd_jenis_prw
                WHERE rp.no_rkm_medis = '$patient_id' 
                AND pr.tgl_permintaan BETWEEN '$start_date' AND '$end_date'
                
                -- [PERBAIKAN VITAL] Cek apakah No.Order ini sudah pernah dipakai di log?
                AND pr.noorder NOT IN (
                    SELECT acsn_baru 
                    FROM orthanc_injector_log 
                    WHERE no_rm = '$patient_id' 
                    AND acsn_baru IS NOT NULL
                )
                
                LIMIT 1
            ";
            
            $q_khanza = $conn->query($sql);

            if ($q_khanza->num_rows > 0) {
                $row = $q_khanza->fetch_assoc();
                
                // Gatekeeper
                if (empty($row['id_servicerequest'])) continue; 

                $target_acsn = $row['noorder'];
                $real_name   = $row['nm_pasien'];
                $final_study_desc = strtoupper((!empty($row['nm_perawatan']) ? $row['nm_perawatan'] : $row['informasi_tambahan']) ?: "PEMERIKSAAN RADIOLOGI");

                // Suntik
                $modify_data = [
                    "Replace" => [
                        "AccessionNumber"  => $target_acsn,
                        "PatientName"      => strtoupper($real_name),
                        "StudyDescription" => $final_study_desc
                    ],
                    "Force" => true 
                ];

                $ch2 = curl_init();
                curl_setopt($ch2, CURLOPT_URL, "$orthanc_url/studies/$study_id_lama/modify");
                curl_setopt($ch2, CURLOPT_USERPWD, "$orthanc_u:$orthanc_p");
                curl_setopt($ch2, CURLOPT_POST, 1);
                curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($modify_data));
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                $res_mod = curl_exec($ch2);
                $http_code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                curl_close($ch2);

                if ($http_code == 200) {
                    $json_mod = json_decode($res_mod, true);
                    $study_id_baru = $json_mod['ID'];

                    // Hapus File Lama
                    $ch3 = curl_init();
                    curl_setopt($ch3, CURLOPT_URL, "$orthanc_url/studies/$study_id_lama");
                    curl_setopt($ch3, CURLOPT_USERPWD, "$orthanc_u:$orthanc_p");
                    curl_setopt($ch3, CURLOPT_CUSTOMREQUEST, "DELETE");
                    curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
                    curl_exec($ch3);
                    curl_close($ch3);

                    // Log ke DB
                    $stmt = $conn->prepare("INSERT INTO orthanc_injector_log (no_rawat, no_rm, nama_pasien, acsn_lama, acsn_baru, orthanc_study_id_lama, orthanc_study_id_baru) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssss", $row['no_rawat'], $patient_id, $real_name, $current_acsn, $target_acsn, $study_id_lama, $study_id_baru);
                    $stmt->execute();

                    $processed_count++;
                    $logs[] = "$real_name -> $target_acsn";
                }
            }
        }
    }

    ob_clean();
    echo json_encode([
        "status" => "success", 
        "range_info" => "$start_date s/d $end_date",
        "processed" => $processed_count,
        "logs" => $logs
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
}
?>