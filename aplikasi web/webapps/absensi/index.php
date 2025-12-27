<?php
require_once('../conf/conf.php');
$allowed_ip_prefix = '192.168.';
$user_ip = $_SERVER['REMOTE_ADDR'];
if (strpos($user_ip, $allowed_ip_prefix) !== 0 && $user_ip !== '127.0.0.1' && $user_ip !== '::1') {
    die("<center><h1 style='color:red'>AKSES DITOLAK</h1><p>Hanya bisa diakses via WiFi RS</p></center>");
}
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, logo FROM setting LIMIT 1");
$logo_src = "data:image/jpeg;base64," . base64_encode($setting['logo']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Presensi Wajah - Anti Spoofing</title>
    <link rel="icon" type="image/x-icon" href="models/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="js/jquery.min.js"></script>
    <script src="js/face-api.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* VIDEO dimirror agar user merasa bercermin */
        video { transform: scaleX(-1); width: 100%; height: auto; border-radius: 1rem; }
        
        #video-container { 
            position: relative; width: 100%; max-width: 640px; margin: 0 auto; 
            overflow: hidden; border-radius: 1rem; border: 4px solid #374151; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.5); background: #000;
        }
        
        /* CANVAS JANGAN DIMIRROR via CSS. Kita mirror koordinatnya via JS agar teks terbaca */
        canvas { position: absolute; top: 0; left: 0; }

        #challenge-box {
            position: absolute; bottom: 30px; left: 0; right: 0;
            text-align: center; z-index: 50; pointer-events: none; display: none;
        }
        .challenge-text {
            background: rgba(0, 0, 0, 0.85); color: #fbbf24;
            font-size: 1.8rem; font-weight: 800; padding: 10px 30px; border-radius: 50px;
            display: inline-block; border: 3px solid #fbbf24;
            box-shadow: 0 0 20px rgba(251, 191, 36, 0.5); text-shadow: 2px 2px 0 #000;
            animation: pulse 0.8s infinite;
        }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.05); } 100% { transform: scale(1); } }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col font-sans">

    <div class="bg-gray-800 border-b border-gray-700 p-4 shadow-md z-20">
        <div class="max-w-4xl mx-auto flex items-center gap-4">
            <img src="<?= $logo_src ?>" class="h-12 w-12 object-contain bg-white rounded-full p-1">
            <div>
                <h1 class="text-xl font-bold text-blue-400 leading-tight"><?= $setting['nama_instansi'] ?></h1>
                <p class="text-xs text-gray-400"><?= $setting['alamat_instansi'] ?></p>
            </div>
            <div class="ml-auto text-right hidden md:block">
                <div id="jam" class="text-2xl font-mono font-bold text-white"></div>
            </div>
        </div>
    </div>

    <div class="flex-1 flex flex-col items-center justify-center p-4">
        <div id="video-container">
            <video id="video" autoplay muted playsinline></video>
            <canvas id="overlay"></canvas>
            
            <div id="loading" class="absolute inset-0 flex items-center justify-center bg-gray-900 z-40">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-blue-500 mx-auto mb-4"></div>
                    <p class="text-blue-400 font-bold animate-pulse">Menyiapkan AI...</p>
                </div>
            </div>

            <div id="challenge-box">
                <div id="instruction" class="challenge-text">INSTRUKSI</div>
            </div>
        </div>

        <div class="mt-6 w-full max-w-lg bg-gray-800 rounded-lg p-4 border border-gray-700 text-center shadow-lg">
            <p id="status-text" class="text-lg font-semibold text-gray-300">Menunggu Wajah...</p>
            <div class="mt-2 h-2 w-full bg-gray-700 rounded-full overflow-hidden">
                <div id="progress-bar" class="h-full bg-blue-500 w-0 transition-all duration-300"></div>
            </div>
        </div>
    </div>

    <div class="bg-gray-950 border-t border-gray-800 p-4">
        <div class="max-w-2xl mx-auto grid grid-cols-2 gap-4">
            <a href="jadwal/login.php" class="flex items-center justify-center gap-2 bg-gray-800 hover:bg-gray-700 p-3 rounded text-sm text-gray-300 border border-gray-700">Login Jadwal</a>
            <a href="hrd/login.php" class="flex items-center justify-center gap-2 bg-gray-800 hover:bg-gray-700 p-3 rounded text-sm text-gray-300 border border-gray-700">Portal HRD</a>
        </div>
    </div>

<script>
    const modelPath = 'models'; 
    const thresholdMatch = 0.45;
    
    let video, canvas, displaySize;
    let labeledFaceDescriptors = [];
    let faceMatcher;
    
    // States: IDLE -> CHALLENGE -> CONFIRM -> PROCESS -> FAILED -> COOLDOWN
    let state = 'IDLE'; 
    let currentChallenge = ''; 
    let challengeTimer = null;
    let detectedNIK = '';
    let detectedName = '';
    let finalPhotoData = null; 

    setInterval(() => { document.getElementById('jam').innerText = new Date().toLocaleTimeString('id-ID', {hour12: false}); }, 1000);

    $(document).ready(function() {
        video = document.getElementById('video');
        canvas = document.getElementById('overlay');

        Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(modelPath),
            faceapi.nets.faceLandmark68Net.loadFromUri(modelPath),
            faceapi.nets.faceRecognitionNet.loadFromUri(modelPath),
            faceapi.nets.faceExpressionNet.loadFromUri(modelPath),
            faceapi.nets.ssdMobilenetv1.loadFromUri(modelPath) 
        ]).then(startVideo).catch(err => Swal.fire('Error AI', ''+err, 'error'));
    });

    function startVideo() {
        navigator.mediaDevices.getUserMedia({ video: {} })
            .then(stream => {
                video.srcObject = stream;
                video.onplay = () => { loadDataPegawai(); };
            })
            .catch(err => Swal.fire('Error Kamera', 'Izin kamera ditolak!', 'error'));
    }

    async function loadDataPegawai() {
        $('#status-text').text("Mengunduh Database Wajah...");
        try {
            const res = await fetch('api_presensi.php?act=get_descriptors');
            const data = await res.json();
            
            if (data.length === 0) {
                $('#status-text').text("Database Wajah Kosong.");
                $('#loading').addClass('hidden');
                return;
            }

            labeledFaceDescriptors = data.map(d => {
                return new faceapi.LabeledFaceDescriptors(d.label + "|" + d.nama, [new Float32Array(d.descriptor)]);
            });
            
            faceMatcher = new faceapi.FaceMatcher(labeledFaceDescriptors, thresholdMatch);
            $('#loading').addClass('hidden');
            $('#status-text').text("Silakan Menghadap Kamera");
            
            startDetectionLoop();
        } catch (e) {
            Swal.fire('Error Server', 'Gagal ambil data: ' + e, 'error');
        }
    }

    function startDetectionLoop() {
        displaySize = { width: video.videoWidth, height: video.videoHeight };
        faceapi.matchDimensions(canvas, displaySize);

        setInterval(async () => {
            if (state === 'PROCESS' || state === 'COOLDOWN' || state === 'CONFIRM' || state === 'FAILED') return;

            const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks().withFaceExpressions().withFaceDescriptors();
            
            const resizedDetections = faceapi.resizeResults(detections, displaySize);
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            if (resizedDetections.length > 0) {
                const detection = resizedDetections[0];
                const result = faceMatcher.findBestMatch(detection.descriptor);
                const box = detection.detection.box;

                // --- MANUAL MIRRORING DRAWING (Agar teks tidak terbalik) ---
                // Karena Video dimirror CSS (-1), koordinat X asli (0) ada di kanan layar.
                // Kita harus membalik X: newX = width - x - widthBox
                const mirroredBox = {
                    x: displaySize.width - box.x - box.width,
                    y: box.y,
                    width: box.width,
                    height: box.height
                };

                // Gambar Kotak Custom
                ctx.strokeStyle = '#3b82f6';
                ctx.lineWidth = 3;
                ctx.strokeRect(mirroredBox.x, mirroredBox.y, mirroredBox.width, mirroredBox.height);
                
                // Gambar Teks
                ctx.fillStyle = '#3b82f6';
                ctx.fillRect(mirroredBox.x, mirroredBox.y - 25, mirroredBox.width, 25);
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 16px sans-serif';
                ctx.fillText(result.label.split('|')[1] || result.label, mirroredBox.x + 5, mirroredBox.y - 7);

                if (state === 'IDLE') {
                    if (result.label !== 'unknown') {
                        const [nik, nama] = result.label.split('|');
                        detectedNIK = nik;
                        detectedName = nama;
                        
                        // SNAPSHOT SEKARANG (Posisi Depan)
                        finalPhotoData = ambilFoto();
                        
                        mulaiTantangan(); 
                    } else {
                        $('#status-text').text("Wajah Tidak Dikenal").addClass('text-red-400');
                    }
                } 
                else if (state === 'CHALLENGE') {
                    const pose = cekTantangan(detection.landmarks, detection.expressions);
                    if (pose === currentChallenge) {
                        selesaikanTantangan();
                    }
                }
            } else {
                if (state === 'IDLE') $('#status-text').text("Menunggu Wajah...").removeClass('text-red-400');
            }
        }, 500); 
    }

    // --- LOGIKA TANTANGAN (KALIBRASI ULANG) ---
    function cekTantangan(landmarks, expressions) {
        if (expressions.happy > 0.7) return 'SMILE';

        const nose = landmarks.getNose()[3];      
        const jawLeft = landmarks.getJawOutline()[0];  
        const jawRight = landmarks.getJawOutline()[16];
        const chin = landmarks.getJawOutline()[8]; 
        
        const leftEye = landmarks.getLeftEye()[0];
        const rightEye = landmarks.getRightEye()[3];
        const midEyeY = (leftEye.y + rightEye.y) / 2;

        // YAW Calculation
        const distLeft = Math.abs(nose.x - jawLeft.x);
        const distRight = Math.abs(nose.x - jawRight.x);
        const yawRatio = distLeft / distRight;

        // REVISI ARAH (Berdasarkan Feedback User)
        // Jika Ratio > 1.5 -> Jarak ke kiri jauh, ke kanan dekat -> Hidung di kanan -> User menoleh KANAN
        // Jika Ratio < 0.6 -> Jarak ke kiri dekat -> Hidung di kiri -> User menoleh KIRI
        if (yawRatio > 1.5) return 'RIGHT'; 
        if (yawRatio < 0.6) return 'LEFT'; 

        // PITCH Calculation
        const topDist = Math.abs(nose.y - midEyeY);
        const bottomDist = Math.abs(chin.y - nose.y);
        const pitchRatio = topDist / bottomDist;

        if (pitchRatio < 0.45) return 'UP'; // Dongak
        if (pitchRatio > 1.1) return 'DOWN'; // Tunduk

        return 'CENTER';
    }

    function mulaiTantangan() {
        state = 'CHALLENGE';
        const options = ['LEFT', 'RIGHT', 'UP', 'DOWN', 'SMILE'];
        currentChallenge = options[Math.floor(Math.random() * options.length)];

        $('#challenge-box').show();
        let text = "";
        switch(currentChallenge) {
            case 'LEFT':  text = "TENGOK KANAN ➡"; break;
            case 'RIGHT': text = "⬅️ TENGOK KIRI"; break;
            case 'UP':    text = "⬆️ DONGAK ATAS"; break;
            case 'DOWN':  text = "⬇️ TUNDUK BAWAH"; break;
            case 'SMILE': text = "😊 SENYUM LEBAR"; break;
        }
        
        $('#instruction').text(text);
        $('#instruction').css('color', '#fbbf24').css('border-color', '#fbbf24'); // Reset warna
        $('#status-text').text(`Halo ${detectedName}, Ikuti Instruksi!`).removeClass('text-red-400').addClass('text-yellow-400');

        clearTimeout(challengeTimer);
        challengeTimer = setTimeout(() => {
            // Cek ulang state agar tidak clash dengan sukses
            if (state === 'CHALLENGE') gagalTantangan();
        }, 5000); 
    }

    function ambilFoto() {
        const cvs = document.createElement('canvas');
        cvs.width = video.videoWidth; cvs.height = video.videoHeight;
        cvs.getContext('2d').drawImage(video, 0, 0);
        return cvs.toDataURL('image/jpeg', 0.85);
    }

    function gagalTantangan() {
        state = 'FAILED'; // KUNCI STATE AGAR TIDAK BISA CONFIRM
        $('#instruction').text("GAGAL / TIMEOUT ❌").css('color', 'red').css('border-color', 'red');
        $('#status-text').text("Verifikasi Gagal.").removeClass('text-yellow-400').addClass('text-red-400');
        setTimeout(resetState, 2000);
    }

    function selesaikanTantangan() {
        if(state === 'FAILED') return; // Cegah race condition
        
        state = 'CONFIRM'; 
        clearTimeout(challengeTimer);
        $('#instruction').text("BERHASIL ✅").css('color', '#4ade80').css('border-color', '#4ade80');
        
        cekStatusDanKonfirmasi();
    }

    function cekStatusDanKonfirmasi() {
        $('#status-text').text("Mengecek Jadwal...");
        
        $.ajax({
            url: 'api_presensi.php?act=check_status_rs',
            type: 'GET',
            data: { nik: detectedNIK },
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    tampilkanKonfirmasi(res);
                } else {
                    Swal.fire('Info', res.message, 'info').then(masukCooldown);
                }
            },
            error: function() {
                Swal.fire('Error', 'Gagal cek jadwal', 'error').then(masukCooldown);
            }
        });
    }

    function tampilkanKonfirmasi(data) {
        let textMsg = "";
        let colorBtn = data.mode === 'MASUK' ? '#10b981' : '#f59e0b';
        
        if(data.mode === 'MASUK') {
            textMsg = `Shift: <b>${data.shift}</b><br>Jam: ${data.jam_kerja}`;
        } else {
            textMsg = `Absen Pulang untuk Shift: <b>${data.shift}</b>`;
        }

        Swal.fire({
            title: `Halo, ${data.nama}`,
            html: textMsg,
            imageUrl: finalPhotoData, 
            imageHeight: 150,
            showCancelButton: true,
            confirmButtonText: `YA, ABSEN ${data.mode}`,
            cancelButtonText: 'BATAL',
            confirmButtonColor: colorBtn,
            cancelButtonColor: '#ef4444',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                kirimAbsen();
            } else {
                masukCooldown();
            }
        });
    }

    function kirimAbsen() {
        state = 'PROCESS';
        Swal.fire({title: 'Menyimpan...', didOpen: () => Swal.showLoading()});
        
        $.ajax({
            url: 'api_presensi.php?act=submit_absen',
            type: 'POST',
            data: {
                nik: detectedNIK,
                image: finalPhotoData 
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire({
                        title: 'BERHASIL',
                        text: res.mode === 'MASUK' ? 'Selamat Bekerja' : 'Hati-hati di jalan',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(masukCooldown);
                } else {
                    Swal.fire('Gagal', res.message, 'error').then(masukCooldown);
                }
            },
            error: function(e) {
                Swal.fire('Error', 'Gagal koneksi server', 'error').then(masukCooldown);
            }
        });
    }

    function resetState() {
        state = 'IDLE';
        detectedNIK = ''; detectedName = ''; finalPhotoData = null; currentChallenge = '';
        $('#challenge-box').hide();
        $('#instruction').css('color', '#fbbf24').css('border-color', '#fbbf24');
        $('#status-text').text("Silakan Menghadap Kamera").removeClass('text-yellow-400').removeClass('text-red-400');
        $('#progress-bar').width('0%');
    }

    function masukCooldown() {
        state = 'COOLDOWN';
        $('#challenge-box').hide();
        let cd = 5;
        $('#status-text').text(`Jeda ${cd} detik...`);
        let i = setInterval(() => {
            cd--;
            $('#status-text').text(`Jeda ${cd} detik...`);
            if(cd <= 0) {
                clearInterval(i);
                resetState();
            }
        }, 1000);
    }
</script>
</body>
</html>