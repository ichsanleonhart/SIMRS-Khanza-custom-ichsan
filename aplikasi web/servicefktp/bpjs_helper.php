<?php
// [2025-11-16] Selalu beri komentar.
// File: bpjs_helper.php
// Fungsi: Bridge API BPJS dengan fitur DECRYPT & SMART LOGGING (Anti-Duplikat).

require_once 'config.php';
require_once 'LZString.php'; 

class BpjsService {
    
    private $lastTimestamp = "";

    private function getHeaders() {
        $consId = BPJS_CONS_ID;
        $secretKey = BPJS_SECRET_KEY;
        $userKey = BPJS_USER_KEY;
        
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        $this->lastTimestamp = $tStamp; 
        
        $signature = hash_hmac('sha256', $consId . "&" . $tStamp, $secretKey, true);
        $encodedSignature = base64_encode($signature);
        date_default_timezone_set('Asia/Jakarta');

        return [
            "X-cons-id: " . $consId,
            "X-timestamp: " . $tStamp,
            "X-signature: " . $encodedSignature,
            "user_key: " . $userKey,
            "Content-Type: application/json"
        ];
    }

    public function stringDecrypt($key, $string) {
        $key_hash = hex2bin(hash('sha256', $key));
        $iv = substr($key_hash, 0, 16);
        $decrypted = openssl_decrypt(base64_decode($string), 'AES-256-CBC', $key_hash, OPENSSL_RAW_DATA, $iv);
        return LZString::decompressFromEncodedURIComponent($decrypted);
    }

    // [REVISI] Smart Logging: Cek duplikat sebelum tulis
    private function writeToLogFile($endpoint, $method, $payload, $responseRaw, $extraInfo = '') {
        $logFile = __DIR__ . '/log_pengiriman.log';
        
        // Siapkan konten log baru (tanpa timestamp dulu untuk perbandingan)
        $logContentPart  = "[ENDPOINT]   : " . $method . " " . BPJS_BASE_URL . "/" . $endpoint . "\n";
        $logContentPart .= "[PAYLOAD]    : " . json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n";
        
        if (is_array($responseRaw) || is_object($responseRaw)) {
            $logContentPart .= "[RESPONSE]   : " . json_encode($responseRaw, JSON_UNESCAPED_SLASHES) . "\n";
        } else {
            $logContentPart .= "[RESPONSE]   : " . $responseRaw . "\n";
        }

        // Cek file log terakhir
        if (file_exists($logFile)) {
            // Ambil 2000 karakter terakhir (cukup untuk cover 1 blok log)
            $lastBytes = file_get_contents($logFile, false, null, -2000); 
            if ($lastBytes !== false) {
                // Cek apakah konten inti (Payload + Response) ada di log terakhir
                // Kita gunakan strpos untuk efisiensi
                $payloadStr = json_encode($payload, JSON_UNESCAPED_SLASHES);
                
                // Jika Payload SAMA dan Response SAMA, jangan tulis lagi (SKIP)
                if (strpos($lastBytes, $payloadStr) !== false) {
                    // Cek response juga
                    $respStr = (is_array($responseRaw) || is_object($responseRaw)) ? json_encode($responseRaw, JSON_UNESCAPED_SLASHES) : $responseRaw;
                    if (strpos($lastBytes, $respStr) !== false) {
                        return; // SKIP WRITING
                    }
                }
            }
        }

        // Jika lolos cek (tidak duplikat), susun log lengkap dengan timestamp
        $logEntry  = "================================================================================\n";
        $logEntry .= "[WAKTU LOG]  : " . date('Y-m-d H:i:s') . "\n";
        if (!empty($extraInfo)) {
            $logEntry .= "[INFO DATA]  : " . $extraInfo . "\n";
        }
        $logEntry .= $logContentPart;
        $logEntry .= "================================================================================\n\n";

        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    public function request($endpoint, $method = 'GET', $data = null, $extraLogInfo = '') {
        $url = BPJS_BASE_URL . '/' . $endpoint;
        $headers = $this->getHeaders();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);     
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);           
        
        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $rawResponse = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        $decodedResponse = json_decode($rawResponse, true);
        
        // Auto Decrypt Logic (Untuk response terenkripsi)
        if ($decodedResponse && isset($decodedResponse['response']) && is_string($decodedResponse['response'])) {
             // Cek metadata dulu, kalau code 200/201 baru decrypt
             $key = BPJS_CONS_ID . BPJS_SECRET_KEY . $this->lastTimestamp;
             try {
                 $decrypted = $this->stringDecrypt($key, $decodedResponse['response']);
                 $jsonDecrypted = json_decode($decrypted, true);
                 $decodedResponse['response'] = $jsonDecrypted ? $jsonDecrypted : $decrypted;
             } catch (Exception $e) {
                 // Biarkan encrypted jika gagal
             }
        }

        $responseToLog = $decodedResponse ? $decodedResponse : $rawResponse;
        if ($error) $responseToLog = "CURL Error: " . $error;
        
        $this->writeToLogFile($endpoint, $method, $data, $responseToLog, $extraLogInfo);

        if ($error) {
            return ['metadata' => ['code' => 500, 'message' => 'Curl Error: ' . $error]];
        }

        return $decodedResponse;
    }
}
?>