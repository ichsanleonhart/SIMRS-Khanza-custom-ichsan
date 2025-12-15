<?php
session_start();
require_once('../../conf/conf.php');
if (!isset($_SESSION['hrd_login'])) { header("Location: login.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Biometrik Wajah</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../js/jquery.min.js"></script>
    <script src="../js/face-api.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Agar video di-mirror seperti cermin */
        video { transform: scaleX(-1); }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen p-4 font-sans">

    <div class="flex justify-between items-center mb-6 max-w-7xl mx-auto border-b border-gray-700 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-blue-400">Manajemen Wajah</h1>
            <p class="text-xs text-gray-400">Pendaftaran & Validasi Data Biometrik</p>
        </div>
        <a href="index.php" class="bg-gray-800 hover:bg-gray-700 px-4 py-2 rounded text-sm transition">Kembali ke Dashboard</a>
    </div>

    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <div class="bg-gray-800 rounded-lg p-4 shadow-lg border border-gray-700 h-fit">
            <h2 class="font-bold text-lg mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Pegawai Terdaftar
            </h2>
            
            <div class="relative mb-4">
                <input type="text" id="cariTerdaftar" class="w-full bg-gray-900 border border-gray-600 rounded p-2 pl-8 text-sm focus:border-blue-500 outline-none" placeholder="Cari Pegawai terdaftar...">
                <svg class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <div id="listTerdaftar" class="space-y-3 max-h-[500px] overflow-y-auto pr-2">
                <p class="text-gray-500 text-center py-4">Memuat data...</p>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg p-4 shadow-lg border border-gray-700">
            <h2 class="font-bold text-lg mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tambah / Daftar Baru
            </h2>

            <div id="step1">
                <label class="text-xs text-gray-400 mb-1 block">Cari Pegawai (Belum Terdaftar)</label>
                <div class="flex gap-2 mb-4">
                    <input type="text" id="keyword" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-sm focus:border-blue-500" placeholder="NIK atau Nama...">
                    <button onclick="cariPegawai()" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded font-bold text-sm">Cari</button>
                </div>
                <div id="hasilPencarian" class="space-y-2 mb-4"></div>
            </div>

            <div id="areaScan" class="hidden animate-fade-in">
                <div class="bg-gray-900 p-3 rounded mb-4 border border-gray-600">
                    <p class="text-xs text-gray-400">Mendaftarkan:</p>
                    <h3 class="font-bold text-lg text-white" id="namaTarget"></h3>
                    <p class="text-xs text-blue-400" id="nikTarget"></p>
                </div>

                <div class="relative bg-black rounded-lg overflow-hidden border-2 border-gray-600 mb-4">
                    <video id="video" class="w-full h-auto" autoplay muted></video>
                    <canvas id="overlay" class="absolute top-0 left-0"></canvas>
                </div>

                <p id="statusAI" class="text-center text-sm text-yellow-400 font-mono mb-4">Memuat Model AI...</p>

                <div class="flex gap-2">
                    <button id="btnScan" onclick="prosesScan()" class="flex-1 bg-green-600 hover:bg-green-500 py-3 rounded font-bold disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        AMBIL DATA WAJAH
                    </button>
                    <button onclick="batalScan()" class="bg-red-600 hover:bg-red-500 px-4 rounded font-bold">Batal</button>
                </div>
            </div>
        </div>

    </div>

<script>
    // --- CONFIG ---
    // Gunakan path relatif (naik 1 folder)
    const MODEL_URL = '../models'; 
    let selId='', selNik='', selNama='';
    let streamCamera = null;

    // --- INIT ---
    $(document).ready(function() {
        loadDataTerdaftar(); // Load list kiri
        loadModels(); // Preload AI model agar cepat
    });

    // --- FUNGSI AI ---
    async function loadModels() {
        try {
            await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
            await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
            await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
            $('#statusAI').text('✅ AI Siap. Silahkan pilih pegawai.');
            $('#statusAI').removeClass('text-yellow-400').addClass('text-green-400');
        } catch(e) {
            alert("Gagal load model AI. Cek folder models.");
            console.error(e);
        }
    }

    // --- LOGIKA FORM KANAN ---
    function cariPegawai() {
        let kw = $('#keyword').val();
        if(kw.length < 3) { alert('Masukkan minimal 3 huruf'); return; }
        
        $('#hasilPencarian').html('<p class="text-center text-gray-500 text-sm">Mencari...</p>');
        $.post('api_enrollment.php?act=search', {kw: kw}, function(res){
            $('#hasilPencarian').html(res);
        });
    }

    function pilih(id, nik, nama) {
        selId = id; selNik = nik; selNama = nama;
        $('#namaTarget').text(nama);
        $('#nikTarget').text(nik);
        
        $('#step1').addClass('hidden');
        $('#areaScan').removeClass('hidden');
        
        startCamera();
    }

    function startCamera() {
        navigator.mediaDevices.getUserMedia({ video: {} })
            .then(stream => {
                streamCamera = stream;
                const vid = document.getElementById('video');
                vid.srcObject = stream;
                
                // Mulai deteksi realtime untuk UX
                vid.addEventListener('play', () => {
                    const canvas = document.getElementById('overlay');
                    const displaySize = { width: vid.offsetWidth, height: vid.offsetHeight };
                    faceapi.matchDimensions(canvas, displaySize);
                    
                    setInterval(async () => {
                        if($('#areaScan').hasClass('hidden')) return;
                        
                        const det = await faceapi.detectSingleFace(vid, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks();
                        
                        // Clear canvas
                        canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                        
                        if(det) {
                            // Gambar kotak jika wajah terdeteksi
                            const resized = faceapi.resizeResults(det, displaySize);
                            const box = resized.detection.box;
                            const drawBox = new faceapi.draw.DrawBox(box, { label: 'Wajah Terdeteksi', boxColor: '#10B981' });
                            drawBox.draw(canvas);
                            
                            $('#btnScan').prop('disabled', false).text("AMBIL DATA WAJAH");
                            $('#statusAI').text("Wajah Terdeteksi!").addClass('text-green-400');
                        } else {
                            $('#btnScan').prop('disabled', true).text("Wajah Tidak Terlihat");
                            $('#statusAI').text("Arahkan wajah ke kamera...").removeClass('text-green-400');
                        }
                    }, 500);
                });
            })
            .catch(e => alert("Gagal akses kamera: " + e));
    }

    function batalScan() {
        if(streamCamera) streamCamera.getTracks().forEach(track => track.stop());
        $('#areaScan').addClass('hidden');
        $('#step1').removeClass('hidden');
        $('#keyword').val('').focus();
        $('#hasilPencarian').html('');
    }

    async function prosesScan() {
        const vid = document.getElementById('video');
        const btn = $('#btnScan');
        
        btn.prop('disabled', true).text("Menganalisa & Menyimpan...");
        
        try {
            // Deteksi Wajah Full (Descriptor)
            const det = await faceapi.detectSingleFace(vid, new faceapi.TinyFaceDetectorOptions())
                                     .withFaceLandmarks()
                                     .withFaceDescriptor();
            
            if(!det) {
                alert("Wajah hilang! Coba lagi.");
                btn.prop('disabled', false).text("AMBIL DATA WAJAH");
                return;
            }

            // Validasi Kualitas (Opsional: Cek confidence score)
            if(det.detection.score < 0.8) {
                alert("Kualitas wajah kurang jelas. Pastikan pencahayaan cukup.");
                btn.prop('disabled', false).text("AMBIL DATA WAJAH");
                return;
            }

            // Ambil Foto
            const cvs = document.createElement('canvas');
            cvs.width = vid.videoWidth; cvs.height = vid.videoHeight;
            cvs.getContext('2d').drawImage(vid, 0, 0);
            const imgData = cvs.toDataURL('image/jpeg');
            
            // Siapkan Data
            const descData = JSON.stringify(Array.from(det.descriptor));

            // Kirim ke Server
            $.post('api_enrollment.php?act=save', {
                id: selId,
                nik: selNik,
                desc: descData,
                img: imgData
            }, function(res) {
                // Handle Response
                if(res.status === 'success') {
                    Swal.fire('Sukses', 'Wajah berhasil didaftarkan!', 'success').then(() => {
                        batalScan();
                        loadDataTerdaftar(); // Refresh list kiri
                    });
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                    btn.prop('disabled', false).text("Coba Lagi");
                }
            }, 'json').fail(function() {
                alert("Gagal koneksi ke server.");
                btn.prop('disabled', false).text("Coba Lagi");
            });

        } catch(e) {
            alert("Error sistem: " + e);
            btn.prop('disabled', false).text("Coba Lagi");
        }
    }

    // --- LOGIKA LIST KIRI ---
    function loadDataTerdaftar() {
        const q = $('#cariTerdaftar').val();
        $.getJSON('api_enrollment.php?act=get_registered', {q: q}, function(data) {
            let html = '';
            if(data.length === 0) {
                html = '<p class="text-gray-500 text-center py-4 text-xs">Tidak ada data.</p>';
            } else {
                data.forEach(item => {
                    html += `
                    <div class="flex items-center gap-3 bg-gray-900 p-3 rounded border border-gray-700 hover:border-gray-500 transition group">
                        <div class="h-10 w-10 rounded-full bg-gray-700 overflow-hidden shrink-0">
                            <img src="${item.photo}" class="h-full w-full object-cover" onerror="this.src='https://ui-avatars.com/api/?name=${item.nama}'">
                        </div>
                        <div class="flex-1 overflow-hidden">
                            <h4 class="font-bold text-sm truncate text-white" title="${item.nama}">${item.nama}</h4>
                            <p class="text-[10px] text-gray-400">${item.nik} &bull; ${item.tgl}</p>
                        </div>
                        <button onclick="hapusWajah('${item.fid}', '${item.nama}')" class="text-red-500 hover:text-red-300 p-2 opacity-0 group-hover:opacity-100 transition" title="Hapus Biometrik">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>`;
                });
            }
            $('#listTerdaftar').html(html);
        });
    }

    // Live Search List Kiri
    $('#cariTerdaftar').on('keyup', function() {
        loadDataTerdaftar();
    });

    function hapusWajah(fid, nama) {
        Swal.fire({
            title: 'Hapus Biometrik?',
            text: `Data wajah ${nama} akan dihapus permanen. Pegawai harus daftar ulang.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('api_enrollment.php?act=delete', {fid: fid}, function(res) {
                    if(res.status === 'success') {
                        loadDataTerdaftar();
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