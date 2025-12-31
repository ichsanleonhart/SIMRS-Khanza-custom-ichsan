<?php
session_start();
require_once('../../conf/conf.php');
if (!isset($_SESSION['hrd_login'])) { header("Location: login.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Biometrik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../js/jquery.min.js"></script>
    <script src="../js/face-api.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">

    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <div class="flex flex-col gap-4">
            <div class="flex justify-between items-center">
                <a href="index.php" class="text-gray-400 hover:text-white">&larr; Kembali</a>
                <h2 class="text-xl font-bold text-blue-400">Pendaftaran Wajah</h2>
            </div>

            <div id="area_scan" class="hidden bg-gray-800 p-4 rounded-lg border-2 border-blue-500 shadow-lg text-center relative order-first lg:order-none">
                <h3 class="font-bold text-lg text-white mb-2">Perekaman: <span id="nama_target" class="text-blue-300">...</span></h3>
                
                <div class="relative w-full aspect-video bg-black rounded overflow-hidden mb-4 border-2 border-gray-600">
                    <video id="video" class="w-full h-full object-cover transform -scale-x-100" autoplay muted></video>
                    <canvas id="overlay" class="absolute top-0 left-0 w-full h-full"></canvas>
                </div>

                <div class="flex gap-2">
                    <button id="btnScan" onclick="simpan()" class="flex-1 bg-green-600 hover:bg-green-500 py-3 rounded font-bold text-white transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        AMBIL WAJAH
                    </button>
                    <button onclick="batalScan()" class="bg-red-600 hover:bg-red-500 px-4 rounded font-bold text-white">Batal</button>
                </div>
                <p id="loading" class="mt-2 text-xs text-yellow-400 animate-pulse">Menyiapkan AI...</p>
            </div>

            <div class="bg-gray-800 p-4 rounded-lg border border-gray-700">
                <label class="text-xs text-gray-400">Pencarian Spesifik</label>
                <div class="flex gap-2 mt-1">
                    <input type="text" id="keyword" class="w-full p-2 rounded bg-gray-900 border border-gray-600 focus:border-blue-500 text-sm" placeholder="Ketik NIK atau Nama...">
                    <button onclick="cari()" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded font-bold transition text-sm">Cari</button>
                </div>
                <div id="hasil" class="mt-2 space-y-1 max-h-40 overflow-y-auto"></div>
            </div>

            <div class="bg-gray-800 p-4 rounded-lg border border-gray-700 flex-1">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-sm font-bold text-red-400">Pegawai Belum Enrollment</h3>
                    <input type="text" id="filter_belum" onkeyup="loadUnenrolled()" class="bg-gray-900 border border-gray-600 rounded p-1 text-xs w-32 text-white" placeholder="Filter...">
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-400">
                        <thead class="bg-gray-700 text-gray-200 uppercase">
                            <tr>
                                <th class="p-2">Nama Pegawai</th>
                                <th class="p-2">Unit</th>
                                <th class="p-2 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tabel_belum" class="divide-y divide-gray-700">
                            <tr><td colspan="3" class="p-4 text-center">Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 p-4 rounded-lg border border-gray-700 h-fit">
            <div class="flex justify-between items-center mb-4 border-b border-gray-700 pb-2">
                <h2 class="text-xl font-bold text-green-400">Data Terdaftar</h2>
                <button onclick="loadEnrolled()" class="text-xs bg-gray-700 px-2 py-1 rounded hover:bg-gray-600">Refresh</button>
            </div>
            
            <input type="text" id="cari_terdaftar" onkeyup="loadEnrolled()" class="w-full p-2 mb-3 rounded bg-gray-900 border border-gray-600 text-sm" placeholder="Filter nama terdaftar...">

            <div id="list_enrolled" class="space-y-3 max-h-[600px] overflow-y-auto pr-1">
                <p class="text-center text-gray-500 py-10">Memuat data...</p>
            </div>
        </div>

    </div>

<script>
    let selId='', selNik='';
    const modelPath = '../models'; 

    $(document).ready(function() {
        loadEnrolled();
        loadUnenrolled(); // Load list belum terdaftar
        loadModels();
    });

    async function loadModels() {
        const status = document.getElementById('loading');
        try {
            await faceapi.nets.tinyFaceDetector.loadFromUri(modelPath);
            await faceapi.nets.faceLandmark68Net.loadFromUri(modelPath);
            await faceapi.nets.faceRecognitionNet.loadFromUri(modelPath);
            status.innerText = "✅ AI Siap. Kamera mendeteksi.";
            status.classList.replace('text-yellow-400', 'text-green-400');
            status.classList.remove('animate-pulse');
        } catch (err) {
            status.innerText = "❌ Gagal Load AI";
        }
    }

    // --- FUNGSI CARI (MANUAL) ---
    function cari() {
        let kw = $('#keyword').val();
        if(kw.length < 3) { alert('Ketik minimal 3 huruf'); return; }
        $('#hasil').html('<p class="text-xs text-gray-400">Mencari...</p>');
        $.post('api_enrollment.php?act=search', {kw: kw}, function(data){ $('#hasil').html(data); });
    }

    // --- FUNGSI LOAD UNENROLLED (BARU) ---
    function loadUnenrolled() {
        let q = $('#filter_belum').val();
        $.get('api_enrollment.php?act=list_unenrolled&q=' + q, function(data) {
            let html = '';
            if(data.length === 0) {
                html = '<tr><td colspan="3" class="p-4 text-center">Semua pegawai sudah terdaftar.</td></tr>';
            } else {
                data.forEach(item => {
                    html += `
                    <tr class="hover:bg-gray-700/50 transition">
                        <td class="p-2">
                            <div class="font-bold text-white">${item.nama}</div>
                            <div class="text-[10px]">${item.nik}</div>
                        </td>
                        <td class="p-2 text-[10px]">${item.dep ?? '-'}</td>
                        <td class="p-2 text-center">
                            <button onclick="pilih('${item.id}', '${item.nik}', '${item.nama}')" class="bg-blue-600 hover:bg-blue-500 text-white px-2 py-1 rounded text-xs font-bold shadow">
                                REKAM
                            </button>
                        </td>
                    </tr>`;
                });
            }
            $('#tabel_belum').html(html);
        }, 'json');
    }

    // --- FUNGSI PILIH & AKTIFKAN KAMERA ---
    function pilih(id, nik, nama) {
        selId=id; selNik=nik;
        $('#nama_target').text(nama);
        $('#area_scan').removeClass('hidden');
        
        // Scroll ke atas (Area Scan)
        $('html, body').animate({scrollTop: $("#area_scan").offset().top - 20}, 500);

        $('#hasil').empty(); 
        $('#keyword').val('');

        navigator.mediaDevices.getUserMedia({video:{}})
            .then(stream => {
                const vid = document.getElementById('video');
                vid.srcObject = stream;
                
                vid.addEventListener('play', () => {
                    const canvas = document.getElementById('overlay');
                    const displaySize = { width: vid.clientWidth, height: vid.clientHeight };
                    faceapi.matchDimensions(canvas, displaySize);
                    
                    // Interval deteksi wajah
                    const interval = setInterval(async () => {
                        // Cek jika area scan ditutup, stop interval
                        if($('#area_scan').hasClass('hidden')) { clearInterval(interval); return; }

                        const det = await faceapi.detectSingleFace(vid, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks();
                        if(det) {
                            const btn = document.getElementById('btnScan');
                            if(det.detection.score > 0.8) {
                                btn.disabled = false;
                                btn.innerText = "AMBIL WAJAH (OK)";
                                btn.classList.replace('bg-gray-600', 'bg-green-600');
                                
                                const box = faceapi.resizeResults(det, displaySize).detection.box;
                                const ctx = canvas.getContext('2d');
                                ctx.clearRect(0, 0, canvas.width, canvas.height);
                                new faceapi.draw.DrawBox(box, { label: 'Wajah Terdeteksi' }).draw(canvas);
                            } else {
                                btn.disabled = true;
                                btn.innerText = "Wajah Kurang Jelas...";
                            }
                        }
                    }, 500);
                });
            })
            .catch(err => Swal.fire('Error', 'Gagal akses kamera: '+err, 'error'));
    }

    function batalScan() {
        $('#area_scan').addClass('hidden');
        const vid = document.getElementById('video');
        if(vid.srcObject) {
            vid.srcObject.getTracks().forEach(track => track.stop());
        }
    }

    // --- SIMPAN ---
    async function simpan() {
        const vid = document.getElementById('video');
        const btn = document.getElementById('btnScan');
        
        btn.innerText = "Memproses...";
        btn.disabled = true;
        
        try {
            const det = await faceapi.detectSingleFace(vid, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptor();
            
            if(det) {
                const cvs = document.createElement('canvas');
                cvs.width = vid.videoWidth; cvs.height = vid.videoHeight;
                cvs.getContext('2d').drawImage(vid, 0, 0);
                
                const imgData = cvs.toDataURL('image/jpeg', 0.9);
                const descData = JSON.stringify(Array.from(det.descriptor));

                $.post('api_enrollment.php?act=save', {
                    id: selId, nik: selNik, desc: descData, img: imgData
                }, function(res) {
                    if(res.status === 'success') {
                        Swal.fire({
                            title: 'Sukses', 
                            text: 'Wajah berhasil didaftarkan!', 
                            icon: 'success', 
                            timer: 1500, 
                            showConfirmButton: false
                        });
                        batalScan(); // Tutup kamera
                        loadEnrolled(); // Refresh kanan
                        loadUnenrolled(); // Refresh kiri (Hapus dari list belum)
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                        btn.disabled = false;
                    }
                }, 'json');
            }
        } catch (err) { alert("Error: " + err); btn.disabled = false; }
    }

    // --- LOAD LIST SUDAH TERDAFTAR ---
    function loadEnrolled() {
        let q = $('#cari_terdaftar').val();
        $.get('api_enrollment.php?act=list_enrolled&q=' + q, function(data) {
            let html = '';
            if(data.length === 0) html = '<p class="text-center text-gray-500 text-sm">Tidak ada data.</p>';
            
            data.forEach(item => {
                html += `
                <div class="bg-gray-900 p-3 rounded border border-gray-700 flex gap-3 items-center hover:border-gray-500 transition">
                    <img src="${item.photo}" class="w-12 h-12 rounded object-cover bg-gray-800 border border-gray-600" onerror="this.src='https://ui-avatars.com/api/?name=${item.nama}&background=random'">
                    <div class="flex-1 overflow-hidden">
                        <h4 class="font-bold text-sm truncate">${item.nama}</h4>
                        <p class="text-xs text-gray-400 font-mono">${item.nik}</p>
                    </div>
                    <button onclick="hapus('${item.id}', '${item.nama}')" class="text-red-400 hover:text-red-200 bg-red-900/30 p-2 rounded hover:bg-red-900/50" title="Hapus">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>`;
            });
            $('#list_enrolled').html(html);
        }, 'json');
    }

    function hapus(id, nama) {
        Swal.fire({
            title: 'Hapus Biometrik?',
            text: `Data wajah ${nama} akan dihapus.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('api_enrollment.php?act=delete', {id: id}, function(res){
                    if(res.status === 'success') {
                        loadEnrolled();
                        loadUnenrolled(); // Munculkan kembali di list belum
                        Swal.fire('Terhapus', 'Data berhasil dihapus.', 'success');
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                }, 'json');
            }
        });
    }
</script>
</body>
</html>