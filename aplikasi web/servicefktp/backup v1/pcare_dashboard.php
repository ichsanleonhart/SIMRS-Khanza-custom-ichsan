<?php
// [2025-11-16] Selalu beri komentar.
// File: pcare_dashboard.php
// Fungsi: Dashboard Monitoring PCare (24/7 Stability Edition).
// Fitur: Auto-Refresh Midnight, Memory Protection, Non-Blocking Worker.

require_once 'pcare_config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCare Service - Klinik Musytasyfah</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .terminal-log { font-family: 'Courier New', Courier, monospace; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #1f2937; }
        ::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 4px; }
        
        /* Animasi indikator hidup */
        @keyframes pulse-dot {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1); }
        }
        .live-dot { animation: pulse-dot 2s infinite; }
    </style>
</head>
<body class="bg-gray-900 text-gray-200 min-h-screen font-sans p-6 flex flex-col">

    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
        <div class="flex items-center gap-3">
            <div class="w-3 h-3 bg-green-500 rounded-full live-dot shadow-[0_0_10px_#22c55e]"></div>
            <div>
                <h1 class="text-2xl font-bold text-white tracking-wide">
                    SERVICE <span class="text-green-500">PCARE BPJS</span>
                </h1>
                <p class="text-xs text-gray-400">Worker Otomatis (H-7 s.d Hari Ini)</p>
            </div>
        </div>
        
        <div class="flex gap-4 items-center">
            <div class="text-right">
                <div class="text-[10px] text-gray-400 uppercase tracking-wider">Jam Server</div>
                <div id="clock" class="text-xl font-mono font-bold text-green-400">00:00:00</div>
            </div>
            <a href="index.php" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-bold border border-gray-600 transition">
                Menu Utama
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 flex-1">
        
        <div class="lg:col-span-1 space-y-6">
            
            <div class="bg-gray-800 rounded-lg p-5 border border-gray-700 shadow-lg">
                <h2 class="text-sm font-bold text-gray-400 mb-4 uppercase">Status Worker</h2>
                
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-gray-300">Interval</span>
                    <span class="bg-blue-900 text-blue-200 px-2 py-0.5 rounded text-xs font-mono">60 Detik</span>
                </div>
                
                <div class="flex justify-between items-center mb-4">
                    <span class="text-sm text-gray-300">Mode</span>
                    <span class="bg-purple-900 text-purple-200 px-2 py-0.5 rounded text-xs font-mono">Auto H-7</span>
                </div>

                <div id="worker-status" class="p-3 bg-gray-900 rounded text-center text-xs text-yellow-500 font-mono border border-gray-700">
                    Menunggu siklus...
                </div>

                <div class="mt-4 pt-4 border-t border-gray-700">
                    <label class="flex items-center cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" id="toggle-service" class="sr-only" checked>
                            <div class="w-10 h-4 bg-gray-600 rounded-full shadow-inner"></div>
                            <div class="dot absolute w-6 h-6 bg-white rounded-full shadow -left-1 -top-1 transition"></div>
                        </div>
                        <div class="ml-3 text-sm font-bold text-gray-300" id="toggle-label">Service ON</div>
                    </label>
                </div>
            </div>

            <div class="bg-gray-800 rounded-lg p-5 border border-gray-700 shadow-lg">
                <h2 class="text-sm font-bold text-gray-400 mb-4 uppercase">Sapu Bersih (Manual)</h2>
                
                <div class="space-y-3">
                    <div>
                        <label class="text-[10px] text-gray-500">Dari Tanggal</label>
                        <input type="date" id="tgl_mulai" class="w-full bg-gray-900 text-white border border-gray-600 rounded px-2 py-1.5 text-sm focus:border-green-500 outline-none">
                    </div>
                    <div>
                        <label class="text-[10px] text-gray-500">Sampai Tanggal</label>
                        <input type="date" id="tgl_akhir" class="w-full bg-gray-900 text-white border border-gray-600 rounded px-2 py-1.5 text-sm focus:border-green-500 outline-none">
                    </div>
                    
                    <button onclick="runSapuBersih()" id="btn-sapu" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded text-sm transition shadow-lg mt-2">
                        🧹 Proses Massal
                    </button>
                </div>
            </div>
        </div>

        <div class="lg:col-span-3 flex flex-col bg-black rounded-lg border border-gray-700 shadow-2xl overflow-hidden h-[600px] lg:h-auto">
            <div class="bg-gray-900 px-4 py-2 flex justify-between items-center border-b border-gray-800">
                <span class="text-xs font-mono text-green-500">root@pcare-service:~/logs$ tail -f activity.log</span>
                <button onclick="$('#log-container').empty()" class="text-[10px] text-gray-500 hover:text-white transition">[Clear Screen]</button>
            </div>
            
            <div id="log-container" class="flex-1 p-4 overflow-y-auto terminal-log text-xs space-y-1 scroll-smooth">
                <div class="text-gray-500 italic">System initialized. Ready for 24/7 operation...</div>
            </div>
        </div>
    </div>

    <script>
        let isRunning = true;
        let workerTimeout;
        const loadedDate = new Date().getDate(); // Simpan tanggal saat load

        // --- 1. CLOCK & MIDNIGHT REFRESH ---
        setInterval(() => {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', { hour12: false });
            $('#clock').text(timeStr);

            // [FITUR 24 JAM] Auto Refresh saat ganti hari
            if (now.getDate() !== loadedDate) {
                console.log("Pergantian hari terdeteksi. Refreshing system...");
                location.reload(); 
            }
        }, 1000);

        // --- 2. LOGGER (MEMORY SAFE) ---
        function addLog(msg, type = 'info') {
            const now = new Date();
            const time = now.toLocaleTimeString('id-ID', { hour12: false });
            let color = 'text-gray-300';
            
            if (type === 'success') color = 'text-green-400';
            else if (type === 'error') color = 'text-red-400 font-bold';
            else if (type === 'warning') color = 'text-yellow-400';

            const line = `<div class="${color} hover:bg-gray-900">[${time}] ${msg}</div>`;
            const container = $('#log-container');
            
            container.append(line);
            
            // [FITUR MEMORY SAFE] Hapus log lama jika lebih dari 500 baris
            if (container.children().length > 500) {
                container.children().first().remove();
            }
            
            // Auto scroll ke bawah
            container.scrollTop(container[0].scrollHeight);
        }

        // --- 3. WORKER CORE (NON-BLOCKING) ---
        function startWorkerCycle() {
            if (!isRunning) return;

            $('#worker-status').text('Syncing...').removeClass('text-gray-500 text-red-500').addClass('text-yellow-500');

            $.ajax({
                url: 'pcare_worker.php',
                method: 'POST',
                data: { mode: 'auto' }, // Auto akan otomatis handle tanggal H-7 server side
                dataType: 'json',
                timeout: 30000, // Timeout 30 detik agar tidak hang
                success: function(res) {
                    if (res.status === 'success') {
                        $('#worker-status').text('Idle (Last: Success)').removeClass('text-yellow-500').addClass('text-green-500');
                        // Log detail
                        if (res.logs && res.logs.length > 0) {
                            res.logs.forEach(l => {
                                const type = (l.includes('GAGAL') || l.includes('412')) ? 'error' : (l.includes('SKIP') ? 'warning' : 'success');
                                addLog(l, type);
                            });
                        }
                    } else {
                        // Idle
                        $('#worker-status').text('Idle (No Data)').removeClass('text-yellow-500').addClass('text-gray-500');
                    }
                },
                error: function(xhr, status, error) {
                    $('#worker-status').text('Connection Error').removeClass('text-yellow-500').addClass('text-red-500');
                    addLog("Worker Connection Error: " + error, 'error');
                },
                complete: function() {
                    // [FITUR NON-BLOCKING]
                    // Jadwalkan run berikutnya HANYA setelah request ini selesai.
                    // Mencegah request menumpuk jika server lambat.
                    if (isRunning) {
                        workerTimeout = setTimeout(startWorkerCycle, 60000); // 60 Detik
                    }
                }
            });
        }

        // --- KONTROL TOMBOL ---
        $('#toggle-service').change(function() {
            if(this.checked) {
                isRunning = true;
                $('#toggle-label').text('Service ON').removeClass('text-gray-500').addClass('text-gray-300');
                $('.dot').addClass('transform translate-x-full bg-green-400').removeClass('bg-white');
                addLog("Service STARTED by User.", 'success');
                startWorkerCycle();
            } else {
                isRunning = false;
                clearTimeout(workerTimeout);
                $('#toggle-label').text('Service PAUSED').removeClass('text-gray-300').addClass('text-gray-500');
                $('.dot').removeClass('transform translate-x-full bg-green-400').addClass('bg-white');
                $('#worker-status').text('PAUSED').addClass('text-gray-500');
                addLog("Service PAUSED by User.", 'warning');
            }
        });

        // --- SAPU BERSIH LOGIC ---
        async function runSapuBersih() {
            const tgl1 = $('#tgl_mulai').val();
            const tgl2 = $('#tgl_akhir').val();
            
            if(!tgl1 || !tgl2) return alert("Pilih tanggal dulu!");
            
            $('#btn-sapu').prop('disabled', true).text('⏳ Memproses...');
            addLog(`=== START MANUAL ${tgl1} s.d ${tgl2} ===`, 'warning');

            // Loop tanggal client-side agar log enak dilihat per hari
            let current = new Date(tgl1);
            const end = new Date(tgl2);

            while (current <= end) {
                const dateStr = current.toISOString().split('T')[0];
                addLog(`>> Processing ${dateStr}...`, 'info');
                
                await processDate(dateStr); // Tunggu sampai selesai baru lanjut tgl berikutnya
                
                current.setDate(current.getDate() + 1);
            }

            addLog("=== SELESAI ===", 'success');
            $('#btn-sapu').prop('disabled', false).text('🧹 Proses Massal');
        }

        function processDate(tgl) {
            return new Promise((resolve) => {
                $.ajax({
                    url: 'pcare_worker.php',
                    method: 'POST',
                    data: { mode: 'sapu_bersih', tgl_mulai: tgl, tgl_akhir: tgl },
                    dataType: 'json',
                    success: function(res) {
                        if(res.status === 'success') {
                            if(res.logs.length > 0) {
                                res.logs.forEach(l => {
                                    const type = (l.includes('GAGAL') || l.includes('412')) ? 'error' : (l.includes('SKIP') ? 'warning' : 'success');
                                    addLog("   " + l, type);
                                });
                            } else {
                                addLog("   Tidak ada data valid.", 'info');
                            }
                        } else {
                            addLog("   " + res.message, 'info');
                        }
                        resolve();
                    },
                    error: function() {
                        addLog("   Error Timeout/Network", 'error');
                        resolve(); // Tetap lanjut tgl berikutnya
                    }
                });
            });
        }

        // Set default date hari ini
        $(document).ready(function() {
            const today = new Date().toISOString().split('T')[0];
            $('#tgl_mulai').val(today);
            $('#tgl_akhir').val(today);
            
            // Start automatically
            addLog("System initialized. Starting worker...", 'info');
            startWorkerCycle();
        });
    </script>
</body>
</html>