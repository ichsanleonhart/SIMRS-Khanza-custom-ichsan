<?php
// Konfigurasi Telegram Bot
// Ganti dengan token bot dan chat ID Anda
define('TELEGRAM_BOT_TOKEN', '6678974103:AAFsAFZItAcuBd9Z8RoGhlpTGz59OJBKOHU');
define('TELEGRAM_CHAT_ID', '-1002491092040');

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

$message = $data['message'];
$serverName = $data['serverName'] ?? 'Unknown Server';
$status = $data['status'] ?? 'unknown';
$timestamp = date('Y-m-d H:i:s');

// Format pesan untuk Telegram
$emoji = $status === 'down' ? '🔴' : '🟢';
$statusText = $status === 'down' ? 'DOWN' : 'RECOVERED';

$telegramMessage = "{$emoji} *SERVER ALERT* {$emoji}\n\n";
$telegramMessage .= "Server: *{$serverName}*\n";
$telegramMessage .= "Status: *{$statusText}*\n";
$telegramMessage .= "Time: {$timestamp}\n";
$telegramMessage .= "\n{$message}";

// Kirim ke Telegram
$result = sendTelegramMessage($telegramMessage);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Notification sent to Telegram'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to send notification'
    ]);
}

function sendTelegramMessage($message) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    
    $data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode == 200;
}
?>