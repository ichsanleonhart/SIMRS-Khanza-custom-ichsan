<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SatuSehat Connection Tester</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        pre { background: #212529; color: #00ff00; padding: 15px; border-radius: 5px; max-height: 400px; overflow: auto; }
        .status-badge { font-size: 1.2em; font-weight: bold; }
    </style>
</head>
<body>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4>🔌 SatuSehat OAuth2 Connection Tester</h4>
        </div>
        <div class="card-body">
            <p>Script ini akan mencoba meminta <strong>Access Token</strong> ke server SatuSehat menggunakan cURL PHP murni.</p>
            
            <form method="post">
                <input type="hidden" name="client_id" value="jw4tmbbymU8WmoYAX4ET5pvKpoM4gvaklPG5hYM9W6NMVpeL">
                <input type="hidden" name="client_secret" value="VN8QAlIlKGGT9pDIz4GRjHL9dqprR3r61KNtQsWDzuAAVwksgtGxuj8cpAxLwBvq">
                
                <div class="d-grid gap-2">
                    <button type="submit" name="test_connection" class="btn btn-success btn-lg">
                        🚀 KLIK UNTUK TES KONEKSI SEKARANG
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php
    if (isset($_POST['test_connection'])) {
        echo '<div class="card mt-4"><div class="card-body">';
        echo '<h5>📊 Hasil Diagnosa:</h5>';

        // 1. KONFIGURASI
        $url = 'https://api-satusehat.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';
        $client_id = $_POST['client_id'];
        $client_secret = $_POST['client_secret'];

        // 2. PERSIAPAN DATA
        $fields = [
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'grant_type'    => 'client_credentials' // Parameter Wajib
        ];
        
        $post_data = http_build_query($fields);

        // 3. SETUP CURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypass SSL sementara
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        // Header Wajib
        $headers = [
            'Content-Type: application/x-www-form-urlencoded'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // 4. EKSEKUSI
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $curl_error = curl_error($ch);

        curl_close($ch);

        // 5. TAMPILAN HASIL
        echo "<div class='alert alert-info'>";
        echo "<strong>Target URL:</strong> $url<br>";
        echo "<strong>HTTP Status:</strong> $http_code<br>";
        echo "<strong>Content Type:</strong> $content_type<br>";
        echo "</div>";

        echo "<h6>Raw Response Server:</h6>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";

        echo "<hr>";
        echo "<h6>🕵️‍♂️ Analisis Dokter Bedah:</h6>";

        if ($curl_error) {
            echo "<div class='alert alert-danger'>❌ <strong>cURL Error:</strong> $curl_error</div>";
        } else {
            $json = json_decode($response, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($json['access_token'])) {
                echo "<div class='alert alert-success status-badge'>✅ SUKSES! TOKEN DITERIMA.</div>";
                echo "<p>Copy token ini untuk Postman:</p>";
                echo "<textarea class='form-control' rows='3'>" . $json['access_token'] . "</textarea>";
            } else {
                echo "<div class='alert alert-danger status-badge'>❌ GAGAL! Server Menolak / Memantul.</div>";
                
                if (strpos($response, 'client_id=') !== false) {
                    echo "<div class='alert alert-warning'>";
                    echo "<strong>⚠️ DIAGNOSA: ECHOING DETECTED</strong><br>";
                    echo "Server memantulkan kembali data yang dikirim mentah-mentah.<br>";
                    echo "Penyebab yang mungkin:<br>";
                    echo "1. IP Public RS diblokir sementara oleh Apigee Google (SatuSehat).<br>";
                    echo "2. Ada Proxy/Mikrotik yang merusak header saat keluar.<br>";
                    echo "3. Gangguan internal di sisi SatuSehat (Sedang Maintenance).";
                    echo "</div>";
                } else {
                    echo "<div class='alert alert-warning'>Respon server tidak dikenali atau format salah.</div>";
                }
            }
        }
        echo '</div></div>';
    }
    ?>
</div>

</body>
</html>