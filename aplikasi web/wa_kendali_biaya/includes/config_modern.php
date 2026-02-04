<?php
/*
 * File: includes/config_modern.php
 * Deskripsi: Konfigurasi Database & Parameter Batas
 */

// --- DATABASE SQL---
$host           = "192.168.1.5";
$user           = "client";
$pass           = "epotoransu";
$db_khanza      = "sik_master";


// --- DATABASE WA---
$hostwa           = "192.168.1.5";
$userwa           = "client";
$passwa           = "epotoransu";
$db_wa          = "wa_delphi";

// Koneksi Khanza
$koneksi_sik = new mysqli($host, $user, $pass, $db_khanza);
if ($koneksi_sik->connect_error) die("Gagal koneksi SIK: " . $koneksi_sik->connect_error);

// Koneksi WA
$koneksi_wa = new mysqli($hostwa, $userwa, $passwa, $db_wa);
if ($koneksi_wa->connect_error) die("Gagal koneksi WA: " . $koneksi_wa->connect_error);

// --- SETTINGS TELEGRAM (OPSIONAL) ---
define('BOT_TOKEN', '6678974103:AAFsAFZItAcuBd9Z8RoGhlpTGz59OJBKOHU');
define('CHAT_ID', '-1002491092040');

// --- SETTINGS WHATSAPP ---
// ID Group WA target (Update Sesuai Request)
define('WA_GROUP_ID', '120363047902955669@g.us'); 

// --- LOGIC BATAS (MODIFIED: 80%) ---
// Mode: 'PERCENT' (Persentase) atau 'FIXED' (Nilai Rupiah)
define('LIMIT_MODE', 'PERCENT'); 

// Jika PERCENT: Trigger jika tagihan mencapai 80% dari plafon
define('LIMIT_PERCENT', 80); 

// Jika FIXED: Trigger jika sisa plafon dibawah Rp 1.000.000 (Backup logic)
// define('LIMIT_FIXED_VAL', 1000000);

// Helper: Fungsi Kirim Telegram
function sendTelegram($message) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => CHAT_ID,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];
    
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'ignore_errors' => true 
        ]
    ];
    $context  = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    return ($result !== false);
}

function escape($conn, $str) {
    return mysqli_real_escape_string($conn, $str);
}
?>