<?php

session_start();
if (!isset($_SESSION['hrd_login']) || $_SESSION['hrd_login'] !== true) {
    // Redirect ke folder hrd/login.php
    header("Location: hrd/login.php"); 
    exit();
}
// Mengambil config dari parent folder sesuai instruksi
require_once('../conf/conf.php');

// --- 1. LIMITASI IP (KEAMANAN) ---
$allowed_ip_prefix = '192.168.'; // Sesuaikan dengan IP Lokal RS
$user_ip = $_SERVER['REMOTE_ADDR'];
if (strpos($user_ip, $allowed_ip_prefix) !== 0 && $user_ip !== '127.0.0.1' && $user_ip !== '::1') {
    die("<h1>Akses Ditolak</h1><p>Hanya dapat diakses melalui Jaringan Lokal RS.</p>");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Wajah Pegawai - SIMKES Khanza</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="js/jquery.min.js"></script>
    <script src="js/face-api.min.js"></script>
</head>
<body class="bg-gray-900 text-white font-sans">

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4 text-center text-blue-400">Enrollment Wajah Pegawai</h1>

    <div class="bg-gray-800 p-4 rounded-lg shadow-lg mb-6">
        <label class="block mb-2 text-sm font-bold">Cari Pegawai (NIK / Nama):</label>
        <div class="flex gap-2">
            <input type="text" id="keyword" class="w-full p-2 rounded text-black" placeholder="Masukkan NIK atau Nama...">
            <button onclick="cariPegawai()" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded font-bold">Cari</button>
        </div>
        
        <div id="hasil_pencarian" class="mt-4 space-y-2"></div>
    </div>

    <div id="area_enrollment" class="hidden bg-gray-800 p-4 rounded-lg shadow-lg flex flex-col items-center">
        <h2 class="text-xl font-bold mb-2" id="nama_pegawai_target">Nama Pegawai</h2>
        <p class="text-sm text-gray-400 mb-4">NIK: <span id="nik_pegawai_target"></span></p>

        <div class="relative">
            <video id="video" width="640" height="480" autoplay muted class="rounded-lg border-2 border-blue-500"></video>
            <canvas id="overlay" class="absolute top-0 left-0"></canvas>
        </div>

        <div class="mt-4 space-x-4">
            <button id="btnScan" onclick="prosesWajah()" class="bg-green-600 hover:bg-green-700 px-6 py-3 rounded-lg font-bold text-lg disabled:opacity-50" disabled>
                📸 Ambil & Simpan Data Wajah
            </button>
            <button onclick="location.reload()" class="bg-red-600 hover:bg-red-700 px-4 py-3 rounded-lg font-bold">Batal</button>
        </div>
        <p id="status_loading" class="mt-2 text-yellow-400">Memuat Model AI...</p>
    </div>
</div>

<script>
    let selectedNik = '';
    let selectedId = '';
    const video = document.getElementById('video');
    const statusLoading = document.getElementById('status_loading');
    const btnScan = document.getElementById('btnScan');

    // 1. Load Model Face-API
    Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri('models'),
        faceapi.nets.faceLandmark68Net.loadFromUri('models'),
        faceapi.nets.faceRecognitionNet.loadFromUri('models')
    ]).then(startVideo).catch(err => {
        console.error(err);
        statusLoading.innerText = "Gagal memuat Model AI. Cek folder 'models'.";
    });

    function startVideo() {
        // Jangan nyalakan kamera dulu sampai user dipilih
        statusLoading.innerText = "Model AI Siap. Silahkan pilih pegawai.";
    }

    // 2. Cari Pegawai via AJAX
    function cariPegawai() {
        let kw = $('#keyword').val();
        if(kw.length < 3) return alert('Masukkan minimal 3 karakter');

        $.post('api_enrollment.php?act=search', {keyword: kw}, function(data) {
            $('#hasil_pencarian').html(data);
        });
    }

    // 3. Pilih Pegawai dari List
    function pilihPegawai(id, nik, nama) {
        selectedId = id;
        selectedNik = nik;
        $('#nama_pegawai_target').text(nama);
        $('#nik_pegawai_target').text(nik);
        $('#area_enrollment').removeClass('hidden');
        $('#hasil_pencarian').empty(); // Bersihkan pencarian

        // Nyalakan Kamera
        navigator.mediaDevices.getUserMedia({ video: {} })
            .then(stream => {
                video.srcObject = stream;
                statusLoading.innerText = "Silahkan menghadap kamera...";
                btnScan.disabled = false;
            })
            .catch(err => alert("Gagal akses kamera: " + err));
    }

    // 4. Proses Deteksi & Simpan
    async function prosesWajah() {
        if(!selectedId) return alert("Pilih pegawai dulu!");
        
        btnScan.innerText = "Sedang memproses...";
        btnScan.disabled = true;

        // Deteksi Wajah (Single Face)
        const detections = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptor();

        if (detections) {
            // Ambil Descriptor (Array Angka)
            const descriptor = detections.descriptor;
            // Konversi ke Array biasa (bukan Float32Array) agar bisa jadi JSON
            const jsonDescriptor = JSON.stringify(Array.from(descriptor));

            // Ambil Foto (Screenshot) untuk bukti visual HRD
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            const imageBase64 = canvas.toDataURL('image/jpeg');

            // Kirim ke Server
            $.post('api_enrollment.php?act=save', {
                id: selectedId,
                nik: selectedNik,
                descriptor: jsonDescriptor,
                image: imageBase64
            }, function(response) {
                const res = JSON.parse(response);
                if(res.status === 'success') {
                    alert("Berhasil! Wajah " + selectedNik + " telah terdaftar.");
                    location.reload();
                } else {
                    alert("Gagal: " + res.message);
                    btnScan.disabled = false;
                    btnScan.innerText = "📸 Ambil & Simpan Data Wajah";
                }
            });

        } else {
            alert("Wajah tidak terdeteksi! Pastikan pencahayaan cukup.");
            btnScan.disabled = false;
            btnScan.innerText = "📸 Ambil & Simpan Data Wajah";
        }
    }
</script>

</body>
</html>