<?php
// API ringan untuk dibaca oleh TV Display
header('Content-Type: application/json; charset=utf-8');

$file = __DIR__ . '/trigger_panggilan.json';

if (file_exists($file)) {
    echo file_get_contents($file);
} else {
    echo json_encode(['nomor' => '-', 'loket' => '-', 'timestamp' => 0]);
}
?>