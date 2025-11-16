<?php
// Allow CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Get URL from request
$url = isset($_GET['url']) ? $_GET['url'] : '';

if (empty($url)) {
    echo json_encode([
        'error' => 'URL parameter is required',
        'status' => 'offline',
        'responseTime' => 0
    ]);
    exit;
}

// Validate URL
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode([
        'error' => 'Invalid URL format',
        'status' => 'offline',
        'responseTime' => 0
    ]);
    exit;
}

// Start timing
$startTime = microtime(true);

// Initialize cURL
$ch = curl_init($url);

// Set cURL options
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false, // For self-signed certificates
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_NOBODY => true, // Only check headers (faster)
    CURLOPT_USERAGENT => 'Server Monitor/1.0'
]);

// Execute request
curl_exec($ch);

// Get response info
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);

// Close cURL
curl_close($ch);

// Calculate response time
$endTime = microtime(true);
$responseTime = round(($endTime - $startTime) * 1000); // in milliseconds

// Determine status
$status = 'offline';
if ($httpCode >= 200 && $httpCode < 400) {
    $status = 'online';
} elseif ($httpCode >= 400 && $httpCode < 600) {
    // Server responded but with error
    $status = 'online'; // Server is reachable but returning error
}

// Return JSON response
echo json_encode([
    'status' => $status,
    'responseTime' => $responseTime,
    'httpCode' => $httpCode,
    'error' => $curlErrno ? $curlError : null
]);
?>