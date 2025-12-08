<?php
// processor.php
// Mencegah output sampah (warning/notice) muncul di respon JSON
ob_start(); 

header('Content-Type: application/json');
date_default_timezone_set('Asia/Jakarta');

// Matikan display error ke layar (agar tidak merusak JSON)
ini_set('display_errors', 0);
error_reporting(E_ALL);

$response = [];

try {
    // ====================================================================
    // 1. KONFIGURASI DATABASE (Sesuai Config Aplikasi Sebelah)
    // ====================================================================
    $db_host = '192.168.1.2';
    $db_user = 'client';
    $db_pass = 'epotoransu';
    $db_name = 'sik_master';

    // Folder tujuan file .wl (Double backslash untuk Windows)
    $orthanc_folder = "D:\\orthanc_worklists\\"; 

    // ====================================================================
    // 2. KONEKSI KE DATABASE
    // ====================================================================
    // Menggunakan try-catch khusus untuk koneksi
    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    } catch (Exception $e) {
        throw new Exception("Gagal Konek Database: " . $e->getMessage());
    }

    if ($conn->connect_error) {
        throw new Exception("Koneksi Error: " . $conn->connect_error);
    }

    // ====================================================================
    // 3. CEK TABEL LOG & FOLDER
    // ====================================================================
    // Cek apakah folder tujuan ada?
    if (!is_dir($orthanc_folder)) {
        throw new Exception("Folder Orthanc tidak ditemukan: " . $orthanc_folder);
    }

    // Cek apakah tabel bridging_orthanc_log ada?
    $check_table = $conn->query("SHOW TABLES LIKE 'bridging_orthanc_log'");
    if($check_table->num_rows == 0) {
        throw new Exception("Tabel 'bridging_orthanc_log' belum dibuat di database!");
    }

    // Ambil parameter tanggal
    $tgl_cari = isset($_GET['tgl']) ? $_GET['tgl'] : date('Y-m-d');

    // ====================================================================
    // 4. LOGIKA SYNC & GENERATE
    // ====================================================================
    
    // A. Sync Data (Insert Ignore agar tidak duplikat)
    // Pastikan kolom-kolom ini BENAR ada di tabel permintaan_radiologi kamu
    $sql_sync = "
        INSERT IGNORE INTO bridging_orthanc_log (noorder, no_rawat, nomor_rm, nama_pasien, tgl_request, status_wl)
        SELECT 
            pr.noorder, 
            pr.no_rawat, 
            p.no_rkm_medis, 
            p.nm_pasien, 
            CONCAT(pr.tgl_permintaan, ' ', pr.jam_permintaan),
            '0'
        FROM permintaan_radiologi pr
        JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
        JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        WHERE pr.tgl_permintaan = '$tgl_cari'
    ";
    
    if (!$conn->query($sql_sync)) {
        throw new Exception("Gagal Sync SQL: " . $conn->error);
    }

    // B. Cari Data Pending
    $sql_pending = "
        SELECT log.*, p.tgl_lahir, p.jk, d.nm_dokter, jpr.nm_perawatan 
        FROM bridging_orthanc_log log
        JOIN permintaan_radiologi pr ON log.noorder = pr.noorder
        JOIN pasien p ON log.nomor_rm = p.no_rkm_medis
        LEFT JOIN dokter d ON pr.dokter_perujuk = d.kd_dokter
        JOIN permintaan_pemeriksaan_radiologi ppr ON pr.noorder = ppr.noorder
        JOIN jns_perawatan_radiologi jpr ON ppr.kd_jenis_prw = jpr.kd_jenis_prw
        WHERE log.status_wl = '0' AND DATE(log.tgl_request) = '$tgl_cari'
    ";

    $result = $conn->query($sql_pending);
    $generated_count = 0;

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Sanitasi Data
            $acc_number = $row['noorder'];
            $pat_name   = strtoupper(preg_replace("/[^a-zA-Z0-9\s]/", "", $row['nama_pasien']));
            $pat_id     = $row['nomor_rm'];
            $dob        = str_replace("-", "", $row['tgl_lahir']);
            $sex        = ($row['jk'] == 'L') ? 'M' : 'F';
            $doc_name   = strtoupper(preg_replace("/[^a-zA-Z0-9\s]/", "", $row['nm_dokter']));
            $study_desc = strtoupper($row['nm_perawatan']);

            // Isi File Worklist
            $content  = "(0010,0010) " . $pat_name . "\n";
            $content .= "(0010,0020) " . $pat_id . "\n";
            $content .= "(0010,0030) " . $dob . "\n";
            $content .= "(0010,0040) " . $sex . "\n";
            $content .= "(0008,0050) " . $acc_number . "\n"; 
            $content .= "(0020,000d) " . $acc_number . "\n"; 
            $content .= "(0008,0090) " . $doc_name . "\n";
            $content .= "(0008,1030) " . $study_desc . "\n";
            $content .= "(0040,0100) \n"; 
            $content .= "  (0008,0060) CR\n"; 
            $content .= "  (0040,0001) ORTHANC_BRIDGE\n";
            $content .= "  (0040,0002) " . date('Ymd') . "\n"; 
            $content .= "  (0040,0003) " . date('His') . "\n";
            $content .= "  (0040,0007) " . $study_desc . "\n"; 
            $content .= "  (0040,0009) " . $acc_number . "\n"; 
            $content .= "(0020,0010) " . $acc_number . "\n";

            // Simpan File
            $filename = $orthanc_folder . $acc_number . ".wl";
            if(file_put_contents($filename, $content) !== false) {
                $conn->query("UPDATE bridging_orthanc_log SET status_wl='1', waktu_generate=NOW() WHERE noorder='$acc_number'");
                $generated_count++;
            }
        }
    }

    // ====================================================================
    // 5. OUTPUT DATA
    // ====================================================================
    $sql_view = "SELECT * FROM bridging_orthanc_log WHERE DATE(tgl_request) = '$tgl_cari' ORDER BY tgl_request DESC";
    $res_view = $conn->query($sql_view);
    $data_tabel = [];
    if($res_view) {
        while($r = $res_view->fetch_assoc()) {
            $data_tabel[] = $r;
        }
    }

    // BERSIHKAN BUFFER SEBELUM OUTPUT
    ob_clean(); 
    
    echo json_encode([
        "status" => "success",
        "generated" => $generated_count,
        "data" => $data_tabel
    ]);

} catch (Exception $e) {
    // BERSIHKAN BUFFER JIKA ERROR
    ob_clean();
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "msg" => $e->getMessage()
    ]);
}
?>