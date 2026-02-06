<?php
// [2025-11-16] Selalu beri komentar.
// File: pcare_helper.php
// Fungsi: Bridge API PCare (Fix: Auth Header +:095).

require_once 'pcare_config.php';
require_once 'LZString.php'; 

class PcareService {
    
    private $lastTimestamp = "";

    private function getHeaders() {
        $consId = PCARE_CONS_ID;
        $secretKey = PCARE_SECRET_KEY;
        $userKey = PCARE_USER_KEY;
        $username = PCARE_USERNAME;
        $password = PCARE_PASSWORD;
        
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        $this->lastTimestamp = $tStamp; 
        
        $signature = hash_hmac('sha256', $consId . "&" . $tStamp, $secretKey, true);
        $encodedSignature = base64_encode($signature);
        
        // [FIX FATAL] Tambahkan suffix :095 sesuai kode Java Khanza (Line 64 frmUtama.java)
        // Format: username:password:095
        $auth = base64_encode($username . ':' . $password . ':095');
        
        date_default_timezone_set('Asia/Jakarta');

        return [
            "X-cons-id: " . $consId,
            "X-timestamp: " . $tStamp,
            "X-signature: " . $encodedSignature,
            "X-authorization: Basic " . $auth,
            "user_key: " . $userKey,
            "Content-Type: text/plain" // Tetap text/plain sesuai Java
        ];
    }

    public function stringDecrypt($key, $string) {
        $key_hash = hex2bin(hash('sha256', $key));
        $iv = substr($key_hash, 0, 16);
        $decrypted = openssl_decrypt(base64_decode($string), 'AES-256-CBC', $key_hash, OPENSSL_RAW_DATA, $iv);
        return LZString::decompressFromEncodedURIComponent($decrypted);
    }

    private function writeToLogFile($endpoint, $method, $payload, $responseRaw, $extraInfo = '') {
        $logFile = __DIR__ . '/log_pengiriman.log';
        
        // Gunakan JSON Unescaped Slashes dan Unicode agar sama dengan Java string
        $payloadStr = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        $logContentPart  = "[ENDPOINT]   : " . $method . " " . PCARE_BASE_URL . "/" . $endpoint . "\n";
        $logContentPart .= "[PAYLOAD]    : " . $payloadStr . "\n";
        
        $respStr = (is_array($responseRaw) || is_object($responseRaw)) ? json_encode($responseRaw, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $responseRaw;
        $logContentPart .= "[RESPONSE]   : " . $respStr . "\n";

        // Anti-Duplikat
        if (file_exists($logFile)) {
            $lastBytes = file_get_contents($logFile, false, null, -4000); // Perbesar buffer cek
            if ($lastBytes !== false) {
                // Cek unik berdasarkan payload dan response
                if (strpos($lastBytes, $payloadStr) !== false && strpos($lastBytes, $respStr) !== false) {
                    return; 
                }
            }
        }

        $logEntry  = "================================================================================\n";
        $logEntry .= "[WAKTU LOG]  : " . date('Y-m-d H:i:s') . "\n";
        if (!empty($extraInfo)) $logEntry .= "[INFO DATA]  : " . $extraInfo . "\n";
        $logEntry .= $logContentPart;
        $logEntry .= "================================================================================\n\n";

        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    public function request($endpoint, $method = 'GET', $data = null, $extraLogInfo = '') {
        $url = PCARE_BASE_URL . '/' . $endpoint;
        $headers = $this->getHeaders();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);     
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);           
        
        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                // Gunakan JSON Unescaped agar tidak ada backslash aneh pada URL atau kutip
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            }
        }

        $rawResponse = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        $decodedResponse = json_decode($rawResponse, true);
        
        // Auto Decrypt
        if ($decodedResponse && isset($decodedResponse['response']) && is_string($decodedResponse['response'])) {
             $key = PCARE_CONS_ID . PCARE_SECRET_KEY . $this->lastTimestamp;
             try {
                 $decrypted = $this->stringDecrypt($key, $decodedResponse['response']);
                 $jsonDecrypted = json_decode($decrypted, true);
                 $decodedResponse['response'] = $jsonDecrypted ? $jsonDecrypted : $decrypted;
             } catch (Exception $e) { }
        }

        $responseToLog = $decodedResponse ? $decodedResponse : $rawResponse;
        if ($error) $responseToLog = "CURL Error: " . $error;
        
        $this->writeToLogFile($endpoint, $method, $data, $responseToLog, $extraLogInfo);

        if ($error) return ['metaData' => ['code' => 500, 'message' => 'Curl: ' . $error]];

        if ($decodedResponse === null) {
            $preview = strip_tags(substr($rawResponse, 0, 100));
            return [
                'metaData' => ['code' => 500, 'message' => 'Invalid JSON. Raw: ' . $preview],
                'response' => $rawResponse
            ];
        }

        return $decodedResponse;
    }
}
?>