<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Klinik (Self-Service)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="js/jquery.min.js"></script>
    <script src="js/face-api.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #111827; color: white; font-family: 'Segoe UI', sans-serif; }
        #video { transform: scaleX(-1); border-radius: 12px; box-shadow: 0 0 20px rgba(0, 255, 255, 0.2); }
        .canvas-overlay { position: absolute; top: 0; left: 0; }
    </style>
</head>
<body class="h-screen flex flex-col items-center justify-center p-4">

    <div class="absolute top-5 left-5 right-5 flex justify-between items-center z-20">
        <div>
            <h1 class="text-2xl font-bold text-blue-400 tracking-wider">KLINIK ABSENSI</h1>
            <p class="text-xs text-gray-400">Mode: Self-Service Shift</p>
        </div>
        <div class="text-right">
            <div id="jam" class="text-3xl font-mono font-bold">00:00:00</div>
            <div id="tanggal" class="text-xs text-gray-400">...</div>
        </div>
    </div>

    <div class="relative w-full max-w-2xl aspect-video bg-gray-900 rounded-xl overflow-hidden border-2 border-gray-700">
        <video id="video" class="w-full h-full object-cover" autoplay muted></video>
        <canvas id="overlay" class="canvas-overlay"></canvas>
        
        <div id="loading" class="absolute inset-0 flex items-center justify-center bg-black/80 z-10">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-3"></div>
                <p class="text-blue-300 animate-pulse">Memuat AI Wajah...</p>
            </div>
        </div>
    </div>

    <div id="status" class="mt-4 text-center text-gray-400 text-sm">Menunggu wajah...</div>

<script>
    const modelPath = 'models'; // Path relative
    let labeledFaceDescriptors = [];
    let faceMatcher;
    let isProcessing = false; // Flag biar gak spam request

    // 1. JAM DIGITAL
    setInterval(() => {
        const now = new Date();
        document.getElementById('jam').innerText = now.toLocaleTimeString('id-ID', {hour12: false});
        document.getElementById('tanggal').innerText = now.toLocaleDateString('id-ID', {weekday:'long', day:'numeric', month:'long', year:'numeric'});
    }, 1000);

    // 2. LOAD AI & DATA PEGAWAI
    Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(modelPath),
        faceapi.nets.faceLandmark68Net.loadFromUri(modelPath),
        faceapi.nets.faceRecognitionNet.loadFromUri(modelPath),
        faceapi.nets.ssdMobilenetv1.loadFromUri(modelPath) // Load SSD juga biar aman
    ]).then(startVideo).catch(err => Swal.fire('Error AI', ''+err, 'error'));

    function startVideo() {
        navigator.mediaDevices.getUserMedia({ video: {} })
            .then(stream => {
                document.getElementById('video').srcObject = stream;
                loadDataPegawai();
            })
            .catch(err => Swal.fire('Error Kamera', 'Tidak bisa akses kamera. Pastikan izin diberikan.', 'error'));
    }

    async function loadDataPegawai() {
        document.getElementById('loading').querySelector('p').innerText = "Mengambil Data Wajah...";
        try {
            // Panggil API KLINIK
            const res = await fetch('api_presensi_clinic.php?act=get_descriptors');
            const data = await res.json();
            
            if (data.length === 0) {
                document.getElementById('status').innerText = "Belum ada data wajah pegawai.";
                document.getElementById('loading').classList.add('hidden');
                return;
            }

            labeledFaceDescriptors = data.map(d => {
                return new faceapi.LabeledFaceDescriptors(d.label + "|" + d.nama, [new Float32Array(d.descriptor)]);
            });
            
            // Threshold 0.45 sesuai request ketat
            faceMatcher = new faceapi.FaceMatcher(labeledFaceDescriptors, 0.45);
            
            document.getElementById('loading').classList.add('hidden');
            startDetection();

        } catch (e) {
            Swal.fire('Error Data', 'Gagal ambil data wajah: ' + e, 'error');
        }
    }

    // 3. DETEKSI WAJAH LOOP
    function startDetection() {
        const video = document.getElementById('video');
        const canvas = document.getElementById('overlay');
        const displaySize = { width: video.videoWidth, height: video.videoHeight };
        faceapi.matchDimensions(canvas, displaySize);

        setInterval(async () => {
            if (isProcessing) return; // Jangan deteksi kalau lagi proses absen

            const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptors();
            const resizedDetections = faceapi.resizeResults(detections, displaySize);
            
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

            if (resizedDetections.length > 0) {
                const results = resizedDetections.map(d => faceMatcher.findBestMatch(d.descriptor));
                
                results.forEach((result, i) => {
                    const box = resizedDetections[i].detection.box;
                    const { label, distance } = result;
                    
                    // Visualisasi Kotak
                    const drawBox = new faceapi.draw.DrawBox(box, { label: label });
                    drawBox.draw(canvas);

                    if (label !== 'unknown') {
                        // Format Label: NIK|NAMA
                        const [nik, nama] = label.split('|');
                        prosesAbsen(nik, nama);
                    }
                });
            }
        }, 1000); // Cek tiap 1 detik
    }

    // 4. PROSES LOGIKA ABSEN (KLINIK)
    async function prosesAbsen(nik, nama) {
        if (isProcessing) return;
        isProcessing = true; // Kunci proses

        // Capture Foto
        const video = document.getElementById('video');
        const cvs = document.createElement('canvas');
        cvs.width = video.videoWidth; cvs.height = video.videoHeight;
        cvs.getContext('2d').drawImage(video, 0, 0);
        const imgData = cvs.toDataURL('image/jpeg');

        // Cek Status Dulu (Masuk/Pulang?)
        try {
            const res = await fetch(`api_presensi_clinic.php?act=check_status&nik=${nik}`);
            const data = await res.json();

            if (data.status === 'success') {
                if (data.mode === 'PULANG') {
                    // --- MODE PULANG (Langsung Proses) ---
                    tanyaKonfirmasi(`Halo ${nama}`, `Anda akan absen PULANG dari shift ${data.shift_sekarang}. Lanjut?`, () => {
                        kirimDataAbsen(nik, imgData, null); // Shift null karena update
                    });
                } else {
                    // --- MODE MASUK (Pilih Shift) ---
                    // Tampilkan Popup Pilihan Shift
                    let options = {};
                    data.pilihan_shift.forEach(s => {
                        options[s.shift] = `${s.shift} (${s.jam_masuk} - ${s.jam_pulang})`;
                    });

                    const { value: selectedShift } = await Swal.fire({
                        title: `Halo, ${nama}`,
                        text: "Silakan pilih jadwal dinas Anda hari ini:",
                        input: 'radio',
                        inputOptions: options,
                        inputValidator: (value) => {
                            if (!value) return 'Anda harus memilih salah satu shift!'
                        },
                        showCancelButton: true,
                        confirmButtonText: 'Absen Masuk',
                        cancelButtonText: 'Batal',
                        allowOutsideClick: false
                    });

                    if (selectedShift) {
                        kirimDataAbsen(nik, imgData, selectedShift);
                    } else {
                        isProcessing = false; // Buka kunci jika batal
                    }
                }
            } else {
                Swal.fire('Error', data.message, 'error').then(() => isProcessing = false);
            }

        } catch (e) {
            console.error(e);
            Swal.fire('Error Koneksi', 'Gagal menghubungi server.', 'error').then(() => isProcessing = false);
        }
    }

    function tanyaKonfirmasi(judul, teks, callbackYa) {
        Swal.fire({
            title: judul,
            text: teks,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Proses',
            cancelButtonText: 'Batal',
            timer: 5000,
            timerProgressBar: true
        }).then((result) => {
            if (result.isConfirmed) {
                callbackYa();
            } else {
                isProcessing = false;
            }
        });
    }

    function kirimDataAbsen(nik, img, shift) {
        // Prepare Data
        let formData = new FormData();
        formData.append('nik', nik);
        formData.append('image', img);
        if(shift) formData.append('shift', shift);

        Swal.fire({title: 'Menyimpan...', didOpen: () => Swal.showLoading()});

        $.ajax({
            url: 'api_presensi_clinic.php?act=submit_absen',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                if(res.status === 'success') {
                    let msg = res.mode === 'MASUK' 
                        ? `Selamat bekerja! Shift: ${res.shift}` 
                        : `Terima kasih! Hati-hati di jalan.`;
                    
                    Swal.fire({
                        title: 'BERHASIL',
                        text: msg,
                        icon: 'success',
                        timer: 3000,
                        showConfirmButton: false
                    }).then(() => {
                        isProcessing = false; // Buka kunci
                    });
                } else {
                    Swal.fire('Gagal', res.message, 'error').then(() => isProcessing = false);
                }
            },
            error: function() {
                Swal.fire('Error', 'Gagal kirim data.', 'error').then(() => isProcessing = false);
            }
        });
    }
</script>
</body>
</html>