<?php
// FILE: processor.php
// FIXED VERSION: Support Hex Emoji Converter (0xF0 0x9F...) -> Emoji Asli

header('Content-Type: application/json; charset=utf-8');

// --- KONFIGURASI DATABASE & NODEJS ---
$host = '192.168.1.2';
$db   = 'sik_master';
$user = 'client';
$pass = 'epotoransu';

// URL Node.js
$node_base_url = 'http://localhost:8100';

// Folder Media
$media_folder = __DIR__ . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR;

// --- FUNGSI SAKTI: KONVERSI HEX KE EMOJI ---
function convertHexToEmoji($text) {
    // Mencari pola seperti "0xF0 0x9F 0x8F 0xA5" (case insensitive)
    // Regex: Cari grup yang diawali 0x diikuti 2 huruf/angka hex, boleh ada spasi
    return preg_replace_callback('/((?:0x[0-9a-fA-F]{2}\s*)+)/', function ($matches) {
        // Bersihkan "0x" dan spasi, sisakan angka hex murni (contoh: F09F8FA5)
        $hex_clean = str_replace(['0x', '0X', ' '], '', $matches[0]);
        
        // Ubah Hex menjadi Binary String (Karakter Asli)
        return hex2bin($hex_clean);
    }, $text);
}

try {
    // 1. Koneksi Database
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Ambil 1 Pesan Antrian
    $stmt = $pdo->query("SELECT * FROM wa_outbox WHERE status = 'ANTRIAN' ORDER BY tanggal_jam ASC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $id_db = $row['nomor']; // Primary Key (misal: 41101)

        // Bersihkan Nomor HP
        $raw_nowa = $row['nowa']; 
        $parts = explode('@', $raw_nowa);
        $nomor_bersih = $parts[0]; 

        // --- KONVERSI PESAN (FITUR BARU) ---
        $pesan_raw = $row['pesan'];
        // Terjemahkan kode hex menjadi emoji sebelum dikirim
        $pesan = convertHexToEmoji($pesan_raw);

        $file_name = $row['file']; 

        // Setup Log
        $log_msg = "[" . date('H:i:s') . "] ID:$id_db | Ke:$nomor_bersih";

        // --- ROUTING ---
        $target_url = "";
        $postData = [];
        
        if (!empty($file_name) && file_exists($media_folder . $file_name)) {
            // JALUR FILE
            $target_url = $node_base_url . '/send-file';
            $postData = [
                'number'   => $nomor_bersih,
                'namafile' => $file_name,
                'caption'  => $pesan // Pesan (yang sudah jadi emoji) jadi caption
            ];
            $log_msg .= " + File ($file_name)";
        } else {
            // JALUR TEKS
            $target_url = $node_base_url . '/send-message';
            $postData = [
                'number'  => $nomor_bersih,
                'message' => $pesan // Kirim pesan yang sudah jadi emoji
            ];
        }

        // 3. Eksekusi cURL
        $ch = curl_init($target_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $response = 'Curl Error: ' . curl_error($ch);
            $httpCode = 500;
        }
        curl_close($ch);

        // 4. Update Database
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
        echo json_encode(['status' => 'empty', 'log' => '']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'log' => 'SYSTEM ERROR: ' . $e->getMessage()]);
}
?>