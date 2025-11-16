<?php
// Konfigurasi WhatsApp (menggunakan Fonnte API)
// Daftar di https://fonnte.com untuk mendapatkan token
// GRATIS 100 pesan/bulan
define('FONNTE_TOKEN', '');
define('WHATSAPP_TARGET', ''); // Format: 628xxx (tanpa +)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get data from POST request
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['message'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Message is required'
    ]);
    exit;
}

$serverName = $data['serverName'] ?? 'Unknown Server';
$status = $data['status'] ?? 'unknown';
$timestamp = date('Y-m-d H:i:s');

// Format pesan untuk WhatsApp
$emoji = $status === 'down' ? '🔴' : '🟢';
$statusText = $status === 'down' ? 'DOWN' : 'RECOVERED';

$whatsappMessage = "{$emoji} *SERVER ALERT* {$emoji}\n\n";
$whatsappMessage .= "Server: *{$serverName}*\n";
$whatsappMessage .= "Status: *{$statusText}*\n";
$whatsappMessage .= "Time: {$timestamp}\n\n";
$whatsappMessage .= $data['message'];

// Kirim ke WhatsApp
$result = sendWhatsAppMessage($whatsappMessage);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Notification sent to WhatsApp'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to send notification'
    ]);
}

function sendWhatsAppMessage($message) {
    $url = 'https://api.fonnte.com/send';
    
    $data = [
        'target' => WHATSAPP_TARGET,
        'message' => $message,
        'countryCode' => '62' // Indonesia
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . FONNTE_TOKEN
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode == 200;
}

// ALTERNATIF: Menggunakan Twilio (lebih reliable tapi berbayar)
/*
function sendWhatsAppViaTwilio($message) {
    $accountSid = 'YOUR_TWILIO_ACCOUNT_SID';
    $authToken = 'YOUR_TWILIO_AUTH_TOKEN';
    $twilioWhatsAppNumber = 'whatsapp:+14155238886'; // Twilio Sandbox
    $toWhatsAppNumber = 'whatsapp:+628123456789';
    
    $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
    
    $data = [
        'From' => $twilioWhatsAppNumber,
        'To' => $toWhatsAppNumber,
        'Body' => $message
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_USERPWD, "{$accountSid}:{$authToken}");
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode == 200 || $httpCode == 201;
}
*/

?>