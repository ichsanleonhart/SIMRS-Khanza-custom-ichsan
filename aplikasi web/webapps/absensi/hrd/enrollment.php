<?php
// File: /var/www/html/webapps/absensi/hrd/enrollment.php
session_start();
require_once('../../conf/conf.php');
if (!isset($_SESSION['hrd_login'])) { header("Location: login.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Wajah</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../js/jquery.min.js"></script>
    <script src="../js/face-api.min.js"></script>
</head>
<body class="bg-gray-900 text-white p-4">
    <a href="index.php" class="text-gray-400 mb-4 inline-block">&larr; Kembali</a>
    <h1 class="text-2xl font-bold mb-4">Enrollment Wajah</h1>

    <div class="bg-gray-800 p-4 rounded-lg mb-4">
        <div class="flex gap-2">
            <input type="text" id="keyword" class="w-full p-2 rounded bg-gray-700 text-white" placeholder="Cari NIK / Nama...">
            <button onclick="cari()" class="bg-blue-600 px-4 py-2 rounded font-bold">Cari</button>
        </div>
        <div id="hasil" class="mt-4 space-y-2"></div>
    </div>

    <div id="area_scan" class="hidden bg-gray-800 p-4 rounded-lg text-center">
        <h2 class="font-bold text-xl mb-2" id="nama_target"></h2>
        <video id="video" width="100%" autoplay muted class="rounded border mb-4"></video>
        <button id="btnScan" onclick="simpan()" class="bg-green-600 w-full py-3 rounded font-bold" disabled>Simpan Wajah</button>
        <p id="loading" class="mt-2 text-yellow-400">Loading AI...</p>
    </div>

<script>
    let selId='', selNik='';
    const statusText = document.getElementById('loading');
    const modelPath = '/webapps/absensi/models'; // Path absolut agar tidak bingung ../

    // Load Models dengan Error Handling Lengkap
    async function loadModels() {
        statusText.innerText = "Memuat Model TinyFace...";
        try {
            await faceapi.nets.tinyFaceDetector.loadFromUri(modelPath);
            statusText.innerText = "Memuat Model Landmark...";
            await faceapi.nets.faceLandmark68Net.loadFromUri(modelPath);
            statusText.innerText = "Memuat Model Recognition...";
            await faceapi.nets.faceRecognitionNet.loadFromUri(modelPath);
            
            statusText.innerText = "✅ AI Siap digunakan";
            statusText.classList.remove('text-yellow-400');
            statusText.classList.add('text-green-400');
        } catch (err) {
            console.error(err);
            alert("GAGAL MEMUAT MODEL AI:\n" + err + "\n\nPastikan file .json dan .shard ada di folder /absensi/models/");
            statusText.innerText = "❌ Gagal Memuat AI";
            statusText.classList.add('text-red-500');
        }
    }

    loadModels();

    function cari() {
        $.post('api_enrollment.php?act=search', {kw: $('#keyword').val()}, function(data){ $('#hasil').html(data); });
    }
    
    function pilih(id, nik, nama) {
        selId=id; selNik=nik;
        $('#nama_target').text(nama);
        $('#area_scan').removeClass('hidden');
        
        navigator.mediaDevices.getUserMedia({video:{}})
            .then(stream => {
                document.getElementById('video').srcObject = stream;
                // Cek tombol hanya aktif jika AI sudah siap
                if(statusText.innerText.includes("Siap")) {
                    document.getElementById('btnScan').disabled = false;
                } else {
                    // Tunggu sebentar lalu aktifkan
                    setTimeout(() => { document.getElementById('btnScan').disabled = false; }, 2000);
                }
            })
            .catch(err => alert("Gagal akses kamera: " + err));
    }

    async function simpan() {
        const vid = document.getElementById('video');
        const btn = document.getElementById('btnScan');
        
        btn.innerText = "Sedang Memproses...";
        btn.disabled = true;
        
        try {
            // Deteksi Wajah
            const det = await faceapi.detectSingleFace(vid, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptor();
            
            if(det) {
                // Ambil Gambar
                const cvs = document.createElement('canvas');
                cvs.width = vid.videoWidth; cvs.height = vid.videoHeight;
                cvs.getContext('2d').drawImage(vid,0,0);
                const imgData = cvs.toDataURL('image/jpeg');
                const descData = JSON.stringify(Array.from(det.descriptor));

                // Kirim ke Server dengan Error Handling
                $.ajax({
                    url: 'api_enrollment.php?act=save',
                    type: 'POST',
                    data: {
                        id: selId, 
                        nik: selNik, 
                        desc: descData,
                        img: imgData
                    },
                    success: function(response) {
                        console.log(response); // Cek di console browser
                        if(response.status === 'success') {
                            alert("SUKSES: " + response.message);
                            location.reload();
                        } else {
                            alert("GAGAL SERVER: " + response.message);
                            btn.innerText = "Coba Simpan Lagi";
                            btn.disabled = false;
                        }
                    },
                    error: function(xhr, status, error) {
                        alert("ERROR KONEKSI: " + xhr.responseText);
                        btn.innerText = "Coba Simpan Lagi";
                        btn.disabled = false;
                    }
                });

            } else { 
                alert('Wajah tidak terdeteksi AI! Dekatkan wajah ke kamera.'); 
                btn.innerText = "Simpan Wajah";
                btn.disabled = false;
            }
        } catch (err) {
            alert("Error System: " + err);
            btn.disabled = false;
        }
    }
</script>
</body>
</html>