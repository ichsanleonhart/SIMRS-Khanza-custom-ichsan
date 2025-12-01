<?php
require_once('../conf/conf.php');
$allowed_ip_prefix = '192.168.';
$user_ip = $_SERVER['REMOTE_ADDR'];
if (strpos($user_ip, $allowed_ip_prefix) !== 0 && $user_ip !== '127.0.0.1' && $user_ip !== '::1') {
    die("<center><h1>Akses Ditolak</h1>Hanya via WiFi RS</center>");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Presensi Wajah RS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="js/jquery.min.js"></script>
    <script src="js/face-api.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        video { transform: scaleX(-1); width: 100%; height: auto; border-radius: 1rem; }
        #video-container { position: relative; width: 100%; max-width: 600px; margin: 0 auto; }
        canvas { position: absolute; top: 0; left: 0; }
    </style>
</head>
<body class="bg-gray-900 flex flex-col items-center justify-center min-h-screen text-white relative">

    <div class="absolute top-4 left-4 z-20">
        <a href="jadwal/login.php" class="bg-blue-600 hover:bg-blue-500 text-white p-3 rounded-full shadow-lg transition flex items-center gap-2 px-4 border border-blue-400/30 backdrop-blur-sm" title="Cek Jadwal Dinas">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span class="text-xs font-bold hidden md:block">Jadwal</span>
        </a>
    </div>

    <div class="absolute top-4 right-4 z-20">
        <a href="hrd/login.php" class="bg-gray-800/80 hover:bg-gray-700 text-gray-400 hover:text-white p-3 rounded-full shadow-lg transition border border-gray-600 backdrop-blur-sm flex items-center gap-2" title="Login Admin / HRD">
            <span class="text-xs font-bold hidden md:block">Admin</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </a>
    </div>
    <div class="absolute top-8 w-full text-center z-10">
        <h1 class="text-xl font-bold text-blue-400 tracking-widest">PRESENSI WAJAH</h1>
        <div id="jam" class="text-4xl font-mono font-bold mt-1">00:00:00</div>
    </div>

    <div id="video-container" class="border-4 border-gray-700 shadow-2xl rounded-2xl overflow-hidden">
        <video id="video" autoplay muted playsinline></video>
        <canvas id="overlay"></canvas>
    </div>

    <div class="mt-6 text-center px-4">
        <p id="status" class="text-yellow-400 animate-pulse font-medium">Memuat Data Wajah...</p>
    </div>
    <audio id="audio_beep" src="assets/beep.mp3"></audio>

<script>
    const video = document.getElementById('video');
    const statusText = document.getElementById('status');
    const audioBeep = document.getElementById('audio_beep');
    //const modelPath = '/webapps/absensi/models'; 
	const modelPath = 'models';
    
    let labeledFaceDescriptors = [];
    let faceMatcher;
    let isProcessing = false; // Kunci agar tidak double popup
    let isCoolingDown = false; // Kunci jeda setelah absen

    // 1. Init AI
    Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(modelPath),
        faceapi.nets.faceLandmark68Net.loadFromUri(modelPath),
        faceapi.nets.faceRecognitionNet.loadFromUri(modelPath)
    ]).then(loadDataPegawai).catch(e => alert("Gagal load model: "+e));

    async function loadDataPegawai() {
        try {
            const res = await fetch('api_presensi.php?act=get_descriptors');
            
            // CEK 1: Apakah response OK?
            if (!res.ok) throw new Error(res.statusText);

            // CEK 2: Apakah valid JSON?
            const text = await res.text(); // Ambil teks mentah dulu
            try {
                const data = JSON.parse(text); // Coba parse
                
                if (data.length === 0) { 
                    statusText.innerText = "⚠️ Belum ada data wajah pegawai."; 
                    return; 
                }

                labeledFaceDescriptors = data.map(d => {
                    return new faceapi.LabeledFaceDescriptors(d.label + "|" + d.nama, [new Float32Array(d.descriptor)]);
                });
                
                faceMatcher = new faceapi.FaceMatcher(labeledFaceDescriptors, 0.45); 
                statusText.innerText = "Kamera sedang disiapkan...";
                startVideo();

            } catch(e) {
                console.error("Raw Response:", text); // Lihat ini di Console jika error
                throw new Error("Format Data Salah: " + text.substring(0, 50) + "..."); 
            }
            
        } catch (e) { 
            console.error(e); 
            // Tampilkan error spesifik di layar hitam
            statusText.innerText = "Error: " + e.message; 
            statusText.classList.add('text-red-500');
        }
    }

    function startVideo() {
        navigator.mediaDevices.getUserMedia({ video: {} })
            .then(stream => { 
                video.srcObject = stream; 
                statusText.innerText = "✅ Kamera Siap. Silahkan menghadap..."; 
            })
            .catch(e => Swal.fire('Error', 'Kamera tidak aktif', 'error'));
    }

    // 2. Logic Loop Deteksi (Recursive Timeout agar TIDAK double popup)
    video.addEventListener('play', () => {
        const canvas = document.getElementById('overlay');
        const displaySize = { width: video.videoWidth, height: video.videoHeight };
        faceapi.matchDimensions(canvas, displaySize);

        // Fungsi deteksi rekursif
        async function detectLoop() {
            // Jika sedang memproses popup atau sedang cooldown, skip deteksi frame ini
            if (isProcessing || isCoolingDown) {
                setTimeout(detectLoop, 1000); // Cek lagi 1 detik kemudian
                return;
            }

            try {
                const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptors();
                
                // Bersihkan canvas
                canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

                if (detections.length > 0 && faceMatcher) {
                    const resizedDetections = faceapi.resizeResults(detections, displaySize);
                    const results = resizedDetections.map(d => faceMatcher.findBestMatch(d.descriptor));
                    
                    // Ambil hasil terbaik pertama
                    const bestResult = results[0];
                    
                    // Gambar kotak
                    const box = resizedDetections[0].detection.box;
                    new faceapi.draw.DrawBox(box, { label: bestResult.toString() }).draw(canvas);

                    if (bestResult.label !== 'unknown') {
                        const [nik, nama] = bestResult.label.split('|');
                        
                        // KUNCI LOGIC: Langsung set processing true agar loop selanjutnya diblokir
                        isProcessing = true; 
                        
                        // Bunyikan Suara
                        audioBeep.play().catch(e => console.log('Audio play failed'));

                        // Proses selanjutnya
                        handleDetectedFace(nik, nama);
                        
                        // Jangan panggil detectLoop lagi di sini, tunggu handle selesai
                        return; 
                    }
                }
            } catch (error) {
                console.error(error);
            }

            // Jika tidak ada wajah terdeteksi/unknown, lanjut loop
            setTimeout(detectLoop, 800); // Interval 0.8 detik
        }

        // Mulai Loop
        detectLoop();
    });

    // 3. Handle Wajah & Fetch Data Jadwal
    async function handleDetectedFace(nik, nama) {
        statusText.innerText = "Memverifikasi Data...";

        // Snap Foto
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth; canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        const img = canvas.toDataURL('image/jpeg');

        try {
            // Ambil data jadwal dan status absen terkini
            const raw = await fetch('api_presensi.php?act=get_schedule&nik=' + nik);
            const info = await raw.json();
			
			// TAMBAHAN: Jika server menolak (karena jadwal kosong)
            if (info.status === 'error') {
                Swal.fire('Info', info.message, 'info').then(() => {
                    // Cooldown agar tidak spam alert yang sama terus menerus
                    aktifkanCooldown(); 
                });
                return;
            }
            
            // Cek jika sudah selesai absen hari ini
            if(info.mode_absen === "SELESAI") {
                Swal.fire('Info', 'Anda sudah menyelesaikan absen hari ini.', 'info').then(() => startCooldown());
                return;
            }

            // Warna judul berdasarkan Masuk/Pulang
            let colorTitle = info.mode_absen === 'MASUK' ? 'text-green-600' : 'text-orange-600';
            let btnText = info.mode_absen === 'MASUK' ? 'YA, ABSEN MASUK' : 'YA, ABSEN PULANG';

            let htmlContent = `
                <div class="text-left bg-gray-100 p-3 rounded mb-3 shadow-inner">
                    <h3 class="font-bold text-xl ${colorTitle} mb-2 text-center">KONFIRMASI ${info.mode_absen}</h3>
                    <hr class="mb-2 border-gray-300">
                    <p class="text-sm"><strong>Nama:</strong> ${nama}</p>
                    <p class="text-sm"><strong>Shift:</strong> ${info.shift}</p>
                    <p class="text-sm"><strong>Jadwal:</strong> ${info.jam_masuk} - ${info.jam_pulang_jadwal}</p>
                </div>
                <img src="${img}" class="w-full rounded border-2 border-gray-300 shadow-sm">
            `;

            Swal.fire({
                html: htmlContent,
                showCancelButton: true,
                confirmButtonText: btnText,
                cancelButtonText: 'Batal',
                confirmButtonColor: info.mode_absen === 'MASUK' ? '#10B981' : '#F59E0B',
                cancelButtonColor: '#EF4444',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    kirimAbsen(nik, img);
                } else {
                    // Jika batal, buka kunci setelah jeda singkat
                    setTimeout(() => { 
                        isProcessing = false; 
                        statusText.innerText = "✅ Kamera Siap...";
                        // Trigger loop lagi manual karena tadi di-return
                        // Tapi karena kita pakai setTimeout recursive di atas, kita perlu reload page atau restart function
                        // Cara gampang: reload page sebagian
                        location.reload(); 
                    }, 1000);
                }
            });

        } catch (e) {
            Swal.fire('Gagal', 'Koneksi API Error', 'error').then(() => startCooldown());
        }
    }

    function kirimAbsen(nik, img) {
        Swal.fire({ title: 'Menyimpan...', didOpen: () => Swal.showLoading() });
        
        $.post('api_presensi.php?act=submit_absen', { nik: nik, image: img }, function(res) {
            try {
                const data = JSON.parse(res);
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.nama + ' berhasil absen ' + data.mode + ' pada ' + data.waktu,
                        timer: 3000,
                        showConfirmButton: false
                    }).then(() => startCooldown());
                } else {
                    Swal.fire('Gagal', data.message, 'error').then(() => startCooldown());
                }
            } catch(e) {
                Swal.fire('Error', 'Respons Server Invalid', 'error').then(() => startCooldown());
            }
        }).fail(() => {
            Swal.fire('Error', 'Koneksi Terputus', 'error').then(() => startCooldown());
        });
    }

    function startCooldown() {
        isProcessing = false; // Reset processing flag
        isCoolingDown = true; // Aktifkan cooldown
        
        statusText.innerText = "⏳ Jeda sistem 10 detik...";
        statusText.classList.add("text-red-400");

        let countdown = 10;
        let timer = setInterval(() => {
            countdown--;
            statusText.innerText = `⏳ Jeda sistem ${countdown} detik...`;
            if(countdown <= 0) {
                clearInterval(timer);
                isCoolingDown = false;
                statusText.innerText = "✅ Kamera Siap...";
                statusText.classList.remove("text-red-400");
                // Panggil loop lagi karena tadi di-return
                const videoEl = document.getElementById('video');
                // Trigger play event manual untuk restart loop jika perlu, 
                // atau cukup biarkan karena loop pakai recursive timeout yang mengecek flag
                // Kita panggil manual satu kali untuk memancing loop:
                const canvas = document.getElementById('overlay');
                // re-init loop logic if needed, but usually reload is safer to clear memory
                location.reload(); 
            }
        }, 1000);
    }

    setInterval(() => { document.getElementById('jam').innerText = new Date().toLocaleTimeString('id-ID', {hour12: false}); }, 1000);
</script>
</body>
</html>