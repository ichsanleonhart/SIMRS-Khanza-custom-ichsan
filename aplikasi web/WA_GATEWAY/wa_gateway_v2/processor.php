<?php
// FILE: processor.php (FIXED SCHEDULE BUG)
// Fitur: Hanya memproses pesan yang waktunya sudah tiba + Anti Timeout

// 1. SETTING NYAWA TAK TERBATAS
set_time_limit(0); 
ini_set('max_execution_time', 0);

ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
header('Content-Type: application/json; charset=utf-8');

// --- SECURITY CHECK ---
$user_ip = $_SERVER['REMOTE_ADDR'];
if ($user_ip !== '127.0.0.1' && $user_ip !== '::1' && $user_ip !== 'localhost') {
    echo json_encode(['status' => 'error', 'log' => "AKSES DITOLAK (IP: $user_ip)."]);
    exit;
}

// --- LOAD CONFIG ---
$configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config.json';
if (!file_exists($configFile)) {
    echo json_encode(['status' => 'error', 'log' => "Config.json tidak ditemukan!"]);
    exit;
}
$config = json_decode(file_get_contents($configFile), true);

$host = $config['db_host'];
$db   = $config['db_name'];
$user = $config['db_user'];
$pass = $config['db_pass'];
$node_port = $config['node_port'];

$node_base_url = 'http://localhost:' . $node_port; 
$media_folder  = __DIR__ . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR;

// --- FUNGSI EMOJI ---
function convertHexToEmoji($text) {
    $pattern = '/(0x[0-9a-fA-F]{2}(?:\s*0x[0-9a-fA-F]{2})*)/i';
    return preg_replace_callback($pattern, function ($matches) {
        $clean_hex = str_replace(['0x', '0X', ' '], '', $matches[0]);
        if (strlen($clean_hex) % 2 != 0) return $matches[0];
        return hex2bin($clean_hex);
    }, $text);
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // =================================================================================
    // FIX FATAL: TAMBAHKAN 'AND tanggal_jam <= NOW()'
    // Agar pesan masa depan tidak terkirim sekarang.
    // =================================================================================
    $sql = "SELECT * FROM wa_outbox 
            WHERE UPPER(status) = 'ANTRIAN' 
            AND tanggal_jam <= NOW() 
            ORDER BY tanggal_jam ASC LIMIT 1";

    $stmt = $pdo->query($sql);
    $row_raw = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row_raw) {
        $row = array_change_key_case($row_raw, CASE_LOWER);

        $id_db = $row['nomor']; 
        $target_wa = $row['nowa']; 
        $pesan_raw = $row['pesan'];
        $pesan = convertHexToEmoji($pesan_raw);
        $file_name = $row['file']; 
        
        $log_msg = "[" . date('H:i:s') . "] ID:$id_db | Ke:$target_wa";

        $target_url = "";
        $postData = [];
        
        if (!empty($file_name) && file_exists($media_folder . $file_name)) {
            $target_url = $node_base_url . '/send-file';
            $postData = [
                'number'   => $target_wa,
                'namafile' => $file_name,
                'caption'  => $pesan 
            ];
            $log_msg .= " + File";
        } else {
            $target_url = $node_base_url . '/send-message';
            $postData = [
                'number'  => $target_wa,
                'message' => $pesan 
            ];
        }

        $ch = curl_init($target_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_TIMEOUT, 600); // 10 Menit Timeout
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $response = 'Curl Error: ' . curl_error($ch);
            $httpCode = 500;
        }
        curl_close($ch);

        if ($httpCode == 200) {
            $sql = "UPDATE wa_outbox SET status = 'Terproses', sender = 'NODEJS', success = '1', response = :resp WHERE nomor = :id_db";
            $log_msg .= " -> SUKSES.";
            $status_op = 'success';
        } else {
            $resp_short = substr($response, 0, 1000);
            $sql = "UPDATE wa_outbox SET status = 'Gagal', sender = 'NODEJS', success = '0', response = :resp WHERE nomor = :id_db";
            $log_msg .= " -> GAGAL (HTTP $httpCode). Msg: " . $resp_short;
            $status_op = 'error';
        }

        $update = $pdo->prepare($sql);
        $update->execute([':resp' => $response, ':id_db' => $id_db]);

        echo json_encode(['status' => $status_op, 'log' => $log_msg]);

    } else {
        // Jika tidak ada antrian yang valid (waktunya belum tiba)
        echo json_encode(['status' => 'empty', 'log' => '']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'log' => 'DB ERROR: ' . $e->getMessage()]);
}
?>