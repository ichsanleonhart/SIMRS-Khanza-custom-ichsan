<?php
// Memuat file konfigurasi dan koneksi database dari SIMRS Khanza
require_once('conf/conf.php');

// Membuka koneksi menggunakan fungsi dari conf.php
$koneksi = bukakoneksi();

// --- FUNGSI UNTUK MENGAMBIL PENGATURAN INSTANSI ---
function getPengaturan($koneksi) {
    $response = ['nama_instansi' => 'Nama Instansi Tidak Ditemukan', 'logo' => ''];
    $query = "SELECT nama_instansi, logo FROM setting LIMIT 1";
    $result = mysqli_query($koneksi, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $response['nama_instansi'] = $row['nama_instansi'];
        // Mengubah data BLOB menjadi base64 untuk ditampilkan di HTML
        if (!empty($row['logo'])) {
            $response['logo'] = 'data:image/jpeg;base64,' . base64_encode($row['logo']);
        }
    }
    return $response;
}

// --- FUNGSI UNTUK MENGECEK PERMINTAAN ---
function cekPermintaanBaru($koneksi) {
    $response = [
        'lab' => 0, 
        'radiologi' => 0, 
        'total' => 0,
        'detail_lab' => [],
        'detail_radiologi' => []
    ];
    $tanggal_sekarang = date('Y-m-d'); // Mendapatkan tanggal hari ini

    // 1. Cek Permintaan Laboratorium yang belum diambil sampelnya HARI INI
    $query_lab = "
        SELECT pl.noorder, p.nm_pasien, jpl.nm_perawatan 
        FROM permintaan_lab pl
        INNER JOIN reg_periksa rp ON pl.no_rawat = rp.no_rawat
        INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        INNER JOIN permintaan_pemeriksaan_lab ppl ON pl.noorder = ppl.noorder
        INNER JOIN jns_perawatan_lab jpl ON ppl.kd_jenis_prw = jpl.kd_jenis_prw
        WHERE pl.tgl_sampel = '0000-00-00' AND pl.tgl_permintaan = '$tanggal_sekarang'
        ORDER BY pl.jam_permintaan DESC";
    
    $result_lab = mysqli_query($koneksi, $query_lab);
    if ($result_lab) {
        $temp_lab = [];
        while($row = mysqli_fetch_assoc($result_lab)) {
            $temp_lab[] = $row;
        }
        $grouped_lab = [];
        foreach ($temp_lab as $item) {
            $grouped_lab[$item['nm_pasien']][] = $item['nm_perawatan'];
        }
        $response['detail_lab'] = $grouped_lab;
        $response['lab'] = count($grouped_lab);
    }

    // 2. Cek Permintaan Radiologi yang belum diambil sampelnya HARI INI
    $query_radiologi = "
        SELECT pr.noorder, p.nm_pasien, jpr.nm_perawatan
        FROM permintaan_radiologi pr
        INNER JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
        INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        INNER JOIN permintaan_pemeriksaan_radiologi ppr ON pr.noorder = ppr.noorder
        INNER JOIN jns_perawatan_radiologi jpr ON ppr.kd_jenis_prw = jpr.kd_jenis_prw
        WHERE pr.tgl_sampel = '0000-00-00' AND pr.tgl_permintaan = '$tanggal_sekarang'
        ORDER BY pr.jam_permintaan DESC";
        
    $result_radiologi = mysqli_query($koneksi, $query_radiologi);
    if ($result_radiologi) {
        $temp_rad = [];
        while($row = mysqli_fetch_assoc($result_radiologi)) {
            $temp_rad[] = $row;
        }
        $grouped_rad = [];
        foreach ($temp_rad as $item) {
            $grouped_rad[$item['nm_pasien']][] = $item['nm_perawatan'];
        }
        $response['detail_radiologi'] = $grouped_rad;
        $response['radiologi'] = count($grouped_rad);
    }

    $response['total'] = $response['lab'] + $response['radiologi'];

    return $response;
}

// --- BAGIAN KONTROLER (AJAX HANDLER) ---
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    if ($_GET['action'] == 'check') {
        echo json_encode(cekPermintaanBaru($koneksi));
    } elseif ($_GET['action'] == 'get_settings') {
        echo json_encode(getPengaturan($koneksi));
    }
    mysqli_close($koneksi);
    exit;
}

mysqli_close($koneksi);
?>

<!-- BAGIAN TAMPILAN (HTML & JAVASCRIPT) -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Alarm Permintaan</title>
    <link id="favicon" rel="icon" type="image/png" href="">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <style>
        :root {
            --font-header: 'Bebas Neue', sans-serif;
            --font-body: 'Roboto', sans-serif;
        }
        html, body {
            height: 100%;
            margin: 0;
            overflow: hidden; /* Mencegah scroll di level body */
        }
        body {
            background-color: #e9ecef;
            font-family: var(--font-body);
            padding: 1vw; /* Padding dinamis */
        }
        .main-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            max-height: 100%;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
        }
        .main-header {
            font-family: var(--font-header);
            font-size: clamp(1.5rem, 2.5vw, 2.5rem); /* Font dinamis */
            letter-spacing: 1.5px;
            background-color: #343a40;
            color: white;
            border-radius: 10px;
            padding: 1vh 1vw;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0; /* Mencegah header menyusut */
        }
        .main-header img {
            height: clamp(30px, 4vh, 45px); /* Tinggi logo dinamis */
            margin-right: 20px;
        }
        .status-box {
            padding: 1.5vh 1vw;
            margin: 1vh 0;
            border-radius: 8px;
            color: white;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .status-box h5 {
            font-family: var(--font-header);
            font-size: clamp(1.2rem, 2vw, 1.8rem);
            margin-bottom: 1vh;
            letter-spacing: 1px;
        }
        .status-box h2 {
            font-family: var(--font-header);
            font-size: clamp(2.5rem, 5vw, 4rem);
            margin: 0;
        }
        .lab-box { background: linear-gradient(45deg, #007bff, #0056b3); }
        .radiologi-box { background: linear-gradient(45deg, #17a2b8, #10707f); }
        .no-alarm { background: linear-gradient(45deg, #28a745, #1e7e34); }
        .alarm-active { 
            background: linear-gradient(45deg, #dc3545, #b02a37); 
            animation: pulse 1.5s infinite;
        }
        .alarm-active h4 {
            font-family: var(--font-header);
            font-size: clamp(1.5rem, 2.2vw, 2rem);
            letter-spacing: 1.5px;
        }
        .detail-window {
            flex-grow: 1; /* Mengambil sisa ruang vertikal */
            display: flex;
            gap: 1.5vw;
            min-height: 0; /* Penting untuk flexbox di dalam flexbox */
        }
        .detail-window .col-md-6 {
            display: flex;
            flex-direction: column;
            padding: 0; /* Menghapus padding default bootstrap */
        }
        /* Memberi jarak antar kolom detail */
        .detail-window .col-md-6:first-child {
            padding-right: 0.75vw;
        }
        .detail-window .col-md-6:last-child {
            padding-left: 0.75vw;
        }
        .detail-window .card {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .detail-window .card-header {
            font-family: var(--font-header);
            font-size: clamp(1.2rem, 1.8vw, 1.8rem);
            letter-spacing: 1px;
            flex-shrink: 0;
        }
        .list-group {
            overflow-y: auto; /* Scroll hanya pada list ini */
        }
        .list-group-item {
            padding: 1.5vh 1.5vw;
            border-bottom: 1px solid #eee;
            text-align: center; /* Teks rata tengah */
        }
        .list-group-item:last-child { border-bottom: none; }
        .list-group-item strong {
            font-weight: 700;
            color: #333;
            font-size: clamp(0.9rem, 1.2vw, 1.1rem);
            display: block;
        }
        .list-group-item ul {
            padding-left: 0;
            margin-top: 5px;
            margin-bottom: 0;
            list-style-position: inside;
            text-align: left; /* Pemeriksaan tetap rata kiri agar mudah dibaca */
            display: inline-block; /* Membuat blok ini terpusat */
        }
        .list-group-item ul li {
            font-size: clamp(0.8rem, 1vw, 0.9rem);
            color: #555;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { box-shadow: 0 0 0 15px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="main-header">
        <img id="hospital-logo" src="" alt="Logo">
        <span id="hospital-name">Memuat...</span>
    </div>

    <div class="card">
        <div class="card-body py-2">
            <div id="status-display">
                <div class="status-box no-alarm">
                    <h4><span>Tidak Ada Permintaan Baru</span> 😴</h4>
                </div>
            </div>
            
            <div class="row no-gutters">
                <div class="col-6 pr-2">
                    <div class="status-box lab-box">
                        <h5>Permintaan Lab Baru</h5>
                        <h2 id="lab-count" class="font-weight-bold">0</h2>
                    </div>
                </div>
                <div class="col-6 pl-2">
                    <div class="status-box radiologi-box">
                        <h5>Permintaan Radiologi Baru</h5>
                        <h2 id="radiologi-count" class="font-weight-bold">0</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row detail-window mt-3 no-gutters">
        <div class="col-6 pr-2">
            <div class="card">
                <div class="card-header bg-primary text-white">Detail Permintaan Laboratorium</div>
                <ul class="list-group list-group-flush" id="detail-lab-list">
                    <li class="list-group-item text-center">Memuat data...</li>
                </ul>
            </div>
        </div>
        <div class="col-6 pl-2">
            <div class="card">
                <div class="card-header bg-info text-white">Detail Permintaan Radiologi</div>
                <ul class="list-group list-group-flush" id="detail-radiologi-list">
                    <li class="list-group-item text-center">Memuat data...</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<audio id="alarm-sound-lab" src="assets/alarm_lab.mp3" preload="auto"></audio> 
<audio id="alarm-sound-rad" src="assets/alarm_rad.mp3" preload="auto"></audio> 

<script>
    function playAlarm(type) {
        const alarmLab = document.getElementById('alarm-sound-lab');
        const alarmRad = document.getElementById('alarm-sound-rad');
        alarmLab.pause();
        alarmRad.pause();
        let soundToPlay = (type === 'lab') ? alarmLab : alarmRad;
        if (soundToPlay) {
            soundToPlay.currentTime = 0;
            soundToPlay.play().catch(e => console.warn(`Audio ${type} tidak dapat diputar otomatis.`));
        }
    }

    function updateDetailList(elementId, details) {
        const listElement = document.getElementById(elementId);
        listElement.innerHTML = '';
        if (Object.keys(details).length === 0) {
            listElement.innerHTML = '<li class="list-group-item text-center">Tidak ada data</li>';
            return;
        }

        for (const pasien in details) {
            const listItem = document.createElement('li');
            listItem.className = 'list-group-item';
            let pemeriksaanHtml = '<ul>';
            details[pasien].forEach(pemeriksaan => {
                pemeriksaanHtml += `<li>${pemeriksaan}</li>`;
            });
            pemeriksaanHtml += '</ul>';
            listItem.innerHTML = `<strong>${pasien}</strong>${pemeriksaanHtml}`;
            listElement.appendChild(listItem);
        }
    }

    function checkPermintaan() {
        fetch('alarm_lab_rad.php?action=check') 
            .then(response => response.json())
            .then(data => {
                document.getElementById('lab-count').textContent = data.lab;
                document.getElementById('radiologi-count').textContent = data.radiologi;
                updateDetailList('detail-lab-list', data.detail_lab);
                updateDetailList('detail-radiologi-list', data.detail_radiologi);

                const statusDisplay = document.getElementById('status-display');
                if (data.total > 0) {
                    if (!statusDisplay.querySelector('.alarm-active')) {
                        statusDisplay.innerHTML = `<div class="status-box alarm-active"><h4><span>ADA PERMINTAAN BARU!</span> 🚨</h4></div>`;
                    }
                    if (data.lab > 0) playAlarm('lab');
                    else if (data.radiologi > 0) playAlarm('radiologi');
                } else {
                    statusDisplay.innerHTML = `<div class="status-box no-alarm"><h4><span>Tidak Ada Permintaan Baru</span> 😴</h4></div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('status-display').innerHTML = `<div class="status-box bg-warning text-dark"><h4><span>Gagal terhubung...</span> 🔌</h4></div>`;
            });
    }

    function loadSettings() {
        fetch('alarm_lab_rad.php?action=get_settings')
            .then(response => response.json())
            .then(data => {
                document.title = `Alarm Permintaan - ${data.nama_instansi}`;
                document.getElementById('hospital-name').textContent = data.nama_instansi;
                if(data.logo) {
                    document.getElementById('hospital-logo').src = data.logo;
                    document.getElementById('favicon').href = data.logo;
                } else {
                    document.getElementById('hospital-logo').style.display = 'none';
                }
            })
            .catch(error => console.error('Error memuat pengaturan:', error));
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadSettings();
        checkPermintaan();
        setInterval(checkPermintaan, 5000); 

        document.body.addEventListener('click', () => {
            const sounds = [document.getElementById('alarm-sound-lab'), document.getElementById('alarm-sound-rad')];
            sounds.forEach(s => s.play().then(() => s.pause()).catch(() => {}));
        }, { once: true });
    });
</script>

</body>
</html>
