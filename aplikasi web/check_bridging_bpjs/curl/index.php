<?php
function aes_encrypt($data, $key) {
    $key = substr(hash('sha256', $key, true), 0, 16);
    $encrypted_data = openssl_encrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    $encrypted_data = base64_encode($encrypted_data);
    return $encrypted_data;
}

function aes_decrypt($data, $key) {
    $key = substr(hash('sha256', $key, true), 0, 16);
    $data = base64_decode($data);
    $decrypted_data = openssl_decrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    return $decrypted_data;
}

$encryption_key = "super_secret_key";

// Daftar URL yang ingin Anda akses
$urls = array(
    'Update Waktu' =>'https://apijkn.bpjs-kesehatan.go.id/antreanrs/antrean/updatewaktu',
    'Add Antrean' =>'https://apijkn.bpjs-kesehatan.go.id/antreanrs/antrean/add',
    'Batal Antrean' =>'https://apijkn.bpjs-kesehatan.go.id/antreanrs/antrean/batal',
    'Add Farmasi' =>'https://apijkn.bpjs-kesehatan.go.id/antreanrs/antrean/farmasi/add',
    'Finger' =>'https://fp.bpjs-kesehatan.go.id/finger-rest',
    'Vclaim Rest' =>'https://apijkn.bpjs-kesehatan.go.id/vclaim-rest',
    'Aplicare' =>'https://new-api.bpjs-kesehatan.go.id/aplicaresws',
    'I-Care' =>'https://apijkn.bpjs-kesehatan.go.id/wsihs/api/rs',
    'Jaringan RS' =>' ',
    'Auth Satu Sehat'=>' ',
    'FHIR Satu Sehat'=>' ',
    'Get Token Rumah Sakit'=>' ',
    'Ambil Antrian RS'=>' ',
    'Checkin VIA Simrs'=>' ',
);

function getUrlInfo($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $start_time = microtime(true);
    $response = curl_exec($ch);
    $end_time = microtime(true);
    $response_time = ($end_time - $start_time) * 1000; // konversi ke milidetik
    // Hitung latency
    $latency = (curl_getinfo($ch, CURLINFO_STARTTRANSFER_TIME) - curl_getinfo($ch, CURLINFO_PRETRANSFER_TIME)) * 1000; // konversi ke milidetik
    // Tentukan status koneksi berdasarkan response time dan latency
    if ($response_time < 500 && $latency < 200) {
        $status = 'Jaringan Bagus';
        $box_color = 'green'; 
    } else {
        $status = 'Terputus';
        $box_color = 'red'; 
    }
    curl_close($ch);
    return array(
        'response_time' => $response_time,
        'latency' => $latency,
        'status' => $status,
        'box_color' => $box_color
    );
}

$url_infos = array();
foreach ($urls as $name => $url) {
    $url_infos[$name] = getUrlInfo($url);
}

$last_updated = array();
foreach ($urls as $name => $url) {
    // Ubah waktu menjadi WIB
    date_default_timezone_set('Asia/Jakarta');
    $last_updated[$name] = date('d-m-Y H:i');
}

$footer_content = "&copy; " . date("Y") . " M. Wira Sb. S. Kom. All rights reserved.";
$encrypted_footer = aes_encrypt($footer_content, $encryption_key);
$decrypted_footer = aes_decrypt($encrypted_footer, $encryption_key);
$footer_hash = md5($decrypted_footer);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RS Karina Medika</title>
    <!-- Load Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Load Font Awesome CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- CSS untuk gaya tambahan -->
    <style>
        body {
            background-color: #fff; /* Warna latar belakang halaman */
        }
        .status-box {
            border-width: 4px; /* Lebar garis batas kotak */
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 15px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1); /* Efek bayangan */
        }
        
        footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: #343a40; /* Warna latar belakang footer */
            text-align: center;
            padding: 10px 0;
            color: #fff; /* Warna teks footer */
        }
        .error {
            color: red;
        }
        .status-box h4 {
            color: #000; /* Warna teks judul kotak */
        }
        /* Tambahkan warna hijau pada judul kotak status */
        .status-box h4.green {
            color: green;
        }
        /* Gaya untuk emotikon */
        .emoji {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        /* Tengahkan semua isi dalam kolom */
        .col-md-4 {
            text-align: center;
        }
        /* Gaya untuk spedometer */
        .speedometer {
            width: 100px;
            height: 100px;
            background: conic-gradient(red 50%, green 50%);
            border-radius: 50%;
            position: relative;
            margin: auto;
        }
        .needle {
            width: 2px;
            height: 45px;
            background-color: black;
            position: absolute;
            left: 50%;
            top: 50%;
            transform-origin: bottom;
            transform: translateX(-50%) rotate(-45deg);
            border-radius: 2px;
        }
    </style>
    <!-- JavaScript untuk auto-refresh -->
    <script>
        setTimeout(function() {
            location.reload();
        }, 5000); 
        
    </script>
</head>
<body>
    <div class="container">
        <h3 class="mt-4 mb-4">Response Time Server WS Bridging BPJS RS Karina Medika</h3>
        <div class="row">
            <?php 
            $count = 0; // Inisialisasi variabel counter
            foreach ($url_infos as $name => $info): 
                if ($count % 3 == 0 && $count != 0) {
                    echo '</div><div class="row">';
                }
            ?>
            <div class="col-md-4">
                <div class="status-box" style="border-color: <?php echo ($info['status'] == 'Terputus') ? 'red' : $info['box_color']; ?>;">
                    <?php
                        // Icon mapping
                        $icons = array(
                            'Update Waktu' => 'clock',
                            'Add Antrean' => 'plus',
                            'Batal Antrean' => 'times',
                            'Add Farmasi' => 'clinic-medical',
                            'Finger' => 'fingerprint',
                            'Vclaim Rest' => 'file-medical',
                            'Aplicare' => 'heartbeat',
                            'I-Care' => 'user-md',
                            'Jaringan RS' => 'signal',
                            'Auth Satu Sehat' => 'user-lock',
                            'FHIR Satu Sehat' => 'stethoscope','Praktek dr. Husnul (owner)' => 'hospital-user',
                            'Get Token Rumah Sakit' => 'key',
                            'Ambil Antrian RS' => 'file-medical',
                            'Checkin VIA Simrs' => 'check-circle'
                            
                        );
                        $icon_class = isset($icons[$name]) ? $icons[$name] : 'question-circle';
                    ?>
                    <!-- Tambahkan kelas "green" pada judul kotak status jika statusnya bagus -->
                    <h4 class="<?php echo ($info['status'] == 'Jaringan Bagus') ? 'green' : ''; ?>"><i class="fas fa-<?php echo $icon_class; ?>" style="color: #000;"></i> <?php echo $name; ?></h4>
                    <?php
                        // Emotikon
                        $emoji = ($info['status'] == 'Jaringan Bagus') ? '😊' : '😢';
                        // Kecepatan (menggunakan rentang untuk menyimpulkan kecepatan)
                        $speed = '';
                        if ($info['latency'] < 100) {
                            $speed = '🚀 Sangat Cepat';
                        } elseif ($info['latency'] >= 100 && $info['latency'] < 200) {
                            $speed = '⚡ Cepat';
                        } elseif ($info['latency'] >= 200 && $info['latency'] < 500) {
                            $speed = '🚶 Sedang';
                        } else {
                            $speed = '🐢 Lambat';
                        }
                    ?>
                    <!-- Tampilkan ikon yang menarik -->
                    <div class="emoji"><?php echo $emoji; ?></div>
                    <!-- Tampilkan kecepatan di bawah emotikon -->
                    <p><?php echo $speed; ?></p>
                    <p><b>Status: <span style="color: <?php echo ($info['status'] == 'Terputus') ? 'red' : 'green'; ?>;"><?php echo $info['status']; ?></span></b></p>
                    <!-- Tampilkan kecepatan dalam ms -->
                    <p>Kecepatan: <?php echo $info['latency']; ?> ms</p>
                    <p>Last Updated: <?php echo $last_updated[$name]; ?></p>
                </div>
            </div>
            <?php 
                $count++; // Tingkatkan counter
            endforeach; 
            ?>
        </div>
    </div>
    <footer style="background-color: #212529;"> <!-- Warna latar belakang footer -->
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> M. Wira, Sb. S. Kom. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
