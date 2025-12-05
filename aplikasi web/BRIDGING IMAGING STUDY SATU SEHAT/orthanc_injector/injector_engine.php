<?php
// injector_engine.php
// VERSI SMART: Hanya menyuntik jika ServiceRequest SUDAH terkirim ke SatuSehat

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Jakarta');
header('Content-Type: application/json');

// --- KONFIGURASI ---
$db_host     = '192.168.1.2';
$db_user     = 'client';
$db_pass     = 'epotoransu';
$db_name     = 'sik_master';
$orthanc_url = "http://localhost:8042"; 
$orthanc_u   = "ichsan";      
$orthanc_p   = "epotoransu";  
// -------------------

$logs = [];
$processed_count = 0;

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) throw new Exception("DB Error: " . $conn->connect_error);

    $today_dicom = date('Ymd'); 
    
    // Cari Study di Orthanc Hari Ini
    $payload = ["Level" => "Study", "Expand" => true, "Query" => ["StudyDate" => $today_dicom]];

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
            $patient_id    = $study['PatientMainDicomTags']['PatientID']; 
            $patient_name  = $study['PatientMainDicomTags']['PatientName'];
            $current_acsn  = isset($study['MainDicomTags']['AccessionNumber']) ? $study['MainDicomTags']['AccessionNumber'] : "";

            if (empty($patient_id)) continue;
            
            // SKIP jika sudah disuntik (Format PR...)
            if (strpos($current_acsn, 'PR') === 0) continue; 

            // -------------------------------------------------------
            // SMART SQL: Cek Ketersediaan ID ServiceRequest
            // -------------------------------------------------------
            $sql = "
                SELECT 
                    pr.noorder, 
                    pr.no_rawat, 
                    p.nm_pasien,
                    pr.informasi_tambahan,
                    jpr.nm_perawatan,
                    ss.id_servicerequest  -- Kita cek kolom ini
                FROM permintaan_radiologi pr
                JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
                JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                LEFT JOIN permintaan_pemeriksaan_radiologi ppr ON pr.noorder = ppr.noorder
                LEFT JOIN jns_perawatan_radiologi jpr ON ppr.kd_jenis_prw = jpr.kd_jenis_prw
                -- JOIN KE TABEL STATUS SATUSEHAT
                LEFT JOIN satu_sehat_servicerequest_radiologi ss ON ss.noorder = pr.noorder 
                     AND ss.kd_jenis_prw = ppr.kd_jenis_prw
                WHERE rp.no_rkm_medis = '$patient_id' 
                AND pr.tgl_permintaan = CURDATE()
                LIMIT 1
            ";
            
            $q_khanza = $conn->query($sql);

            if ($q_khanza->num_rows > 0) {
                $row = $q_khanza->fetch_assoc();
                
                // === GATEKEEPER ===
                // Cek apakah id_servicerequest sudah ada?
                if (empty($row['id_servicerequest'])) {
                    // JIKA KOSONG: Berarti Java belum kirim. KITA TAHAN DULU.
                    // Jangan lakukan injeksi. Biarkan file menunggu di Orthanc.
                    // Tidak perlu dicatat di log agar tidak spamming.
                    continue; 
                }
                // ==================

                $target_acsn = $row['noorder'];
                $real_name   = $row['nm_pasien'];
                $final_study_desc = !empty($row['nm_perawatan']) ? $row['nm_perawatan'] : $row['informasi_tambahan'];
                $final_study_desc = strtoupper($final_study_desc ?: "PEMERIKSAAN RADIOLOGI");

                // EKSEKUSI INJEKSI (Karena sudah terkonfirmasi aman)
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

                    // Log Database
                    $stmt = $conn->prepare("INSERT INTO orthanc_injector_log (no_rawat, no_rm, nama_pasien, acsn_lama, acsn_baru, orthanc_study_id_lama, orthanc_study_id_baru) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssss", $row['no_rawat'], $patient_id, $real_name, $current_acsn, $target_acsn, $study_id_lama, $study_id_baru);
                    $stmt->execute();

                    $processed_count++;
                    $logs[] = "Berhasil (Sync SS Ready): $real_name ($target_acsn)";
                }
            }
        }
    }

    $log_view = [];
    $q_log = $conn->query("SELECT * FROM orthanc_injector_log ORDER BY id DESC LIMIT 10");
    while($r = $q_log->fetch_assoc()) { $log_view[] = $r; }

    ob_clean();
    echo json_encode(["status" => "success", "processed" => $processed_count, "logs" => $logs, "data_table" => $log_view]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
}
?>