<?php
require_once 'erm_config.php';

class ErmBridging {
    
    // Header Generator (Signature)
    public function getHeaders($timestamp) {
        $data = BPJS_CONS_ID . "&" . $timestamp;
        $signature = base64_encode(hash_hmac('sha256', $data, BPJS_SECRET_KEY, true));
        
        return [
            "X-cons-id: " . BPJS_CONS_ID,
            "X-timestamp: " . $timestamp,
            "X-signature: " . $signature,
            "user_key: " . BPJS_USER_KEY,
            "Content-Type: application/json"
        ];
    }

    // Enkripsi Payload: GZIP -> AES-256-ECB -> Base64
    public function encryptData($jsonString) {
        // 1. Kompresi GZIP
        $compressed = gzencode($jsonString, 9);
        if ($compressed === false) return null;

        // 2. Hash Key (SHA256)
        $keyString = BPJS_CONS_ID . BPJS_SECRET_KEY . BPJS_USER_KEY;
        $keyHash = hash('sha256', $keyString, true);

        // 3. AES Encrypt
        $encrypted = openssl_encrypt($compressed, 'aes-256-ecb', $keyHash, OPENSSL_RAW_DATA);
        
        // 4. Base64
        return base64_encode($encrypted);
    }

    // Kirim POST Request
    public function postRequest($endpoint, $payloadBody) {
        $timestamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        $url = BPJS_API_URL . $endpoint;
        $headers = $this->getHeaders($timestamp);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Set true jika Production SSL Valid
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout 10 detik agar browser tidak hang
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return json_encode(['metadata' => ['code' => 500, 'message' => 'Curl Error: ' . $err]]);
        }
        return $response;
    }
}
?>