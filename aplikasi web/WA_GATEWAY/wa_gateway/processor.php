<?php
// FILE: processor.php (RSIA DIAN - UNIVERSAL AUTO CONVERT)
// Fitur: Mengubah pattern 0x.. menjadi Emoji secara otomatis tanpa merusak teks lain.

// 1. Matikan error display agar JSON bersih
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

header('Content-Type: application/json; charset=utf-8');

// --- SECURITY CHECK ---
$user_ip = $_SERVER['REMOTE_ADDR'];
if ($user_ip !== '127.0.0.1' && $user_ip !== '::1' && $user_ip !== 'localhost') {
    echo json_encode(['status' => 'error', 'log' => "AKSES DITOLAK (IP: $user_ip)."]);
    exit;
}

// --- KONFIGURASI RSIA DIAN ---
$host = '192.168.1.5'; 
$db   = 'sik_master';
$user = 'client';      
$pass = 'epotoransu'; 

$node_base_url = 'http://localhost:8100';
$media_folder = __DIR__ . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR;

// --- FUNGSI EMOJI AUTO-CONVERT (CERDAS) ---
function convertHexToEmoji($text) {
    // REGEX PENJELASAN:
    // (0x[0-9a-fA-F]{2}      -> Cari awalan "0x" diikuti 2 digit hex (cth: 0xF0)
    // (?:\s*0x[0-9a-fA-F]{2})*) -> Boleh diikuti spasi (opsional) lalu "0x" lagi berulang-ulang
    // Pattern ini memastikan hanya menangkap rantai hex, TIDAK menangkap teks biasa.
    $pattern = '/(0x[0-9a-fA-F]{2}(?:\s*0x[0-9a-fA-F]{2})*)/i';

    return preg_replace_callback($pattern, function ($matches) {
        $found_string = $matches[0]; // Cth: "0xF0 0x9F 0x98 0x8A"

        // Bersihkan hanya bagian yang tertangkap (Hapus 0x dan Spasi)
        $clean_hex = str_replace(['0x', '0X', ' '], '', $found_string);

        // Validasi: Panjang harus genap (karena hex = 2 digit)
        if (strlen($clean_hex) % 2 != 0) {
            return $found_string; // Kembalikan aslinya jika aneh
        }

        // Konversi Hex bersih ke Biner (Emoji)
        return hex2bin($clean_hex);
    }, $text);
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ambil Antrian
    $stmt = $pdo->query("SELECT * FROM wa_outbox WHERE status = 'ANTRIAN' ORDER BY tanggal_jam ASC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $id_db = $row['nomor']; 
        $raw_nowa = $row['nowa']; 
        $parts = explode('@', $raw_nowa);
        $nomor_bersih = $parts[0]; 

        // --- PROSES PESAN ---
        $pesan_raw = $row['pesan'];
        
        // JALANKAN KONVERSI OTOMATIS
        $pesan = convertHexToEmoji($pesan_raw);

        $file_name = $row['file']; 
        $log_msg = "[" . date('H:i:s') . "] ID:$id_db | Ke:$nomor_bersih";

        $target_url = "";
        $postData = [];
        
        // Cek File
        if (!empty($file_name) && file_exists($media_folder . $file_name)) {
            $target_url = $node_base_url . '/send-file';
            $postData = [
                'number'   => $nomor_bersih,
                'namafile' => $file_name,
                'caption'  => $pesan 
            ];
            $log_msg .= " + File";
        } else {
            $target_url = $node_base_url . '/send-message';
            $postData = [
                'number'  => $nomor_bersih,
                'message' => $pesan 
            ];
        }

        // Tembak Node.js
        $ch = curl_init($target_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        // http_build_query sangat penting untuk menjaga emoji tetap utuh saat dikirim via HTTP
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $response = 'Curl Error: ' . curl_error($ch);
            $httpCode = 500;
        }
        curl_close($ch);

        // Update Database
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
    echo json_encode(['status' => 'error', 'log' => 'DB ERROR: ' . $e->getMessage()]);
}
?>