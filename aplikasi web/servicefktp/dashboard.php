<?php
// [2026-02-09] REVISI: DASHBOARD MONITORING & DEBUGGING CENTER
// File: dashboard.php
// Fungsi: Dashboard Utama Pengendali Antrean Online (Manual & Auto).
// Fitur: Auto-Date, Heartbeat Timer, JSON Pretty Print, Color Coded Logs.

require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ANTROL COMMAND CENTER - Klinik Musytasyfah</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&display=swap');

        body { background-color: #0f172a; color: #e2e8f0; font-family: 'Segoe UI', sans-serif; }
        
        /* Terminal Styling */
        .terminal-window {
            font-family: 'Fira Code', monospace;
            background-color: #000000;
            border: 1px solid #334155;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.1);
        }
        
        /* Scrollbar Keren */
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 5px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }

        /* Status Indicator Animation */
        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
        .status-active { animation: pulse-green 2s infinite; }

        /* Log Colors */
        .log-time { color: #64748b; margin-right: 8px; font-size: 0.8em; }
        .log-info { color: #38bdf8; }     /* Biru Muda */
        .log-success { color: #4ade80; }  /* Hijau Neon */
        .log-error { color: #ef4444; font-weight: bold; }   /* Merah */
        .log-warn { color: #facc15; }     /* Kuning */
        .log-json { color: #a5b4fc; font-size: 0.85em; }    /* Ungu Pucat */
        
        pre { white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body class="h-screen flex flex-col p-4 overflow-hidden">

    <div class="bg-gray-800 p-4 rounded-xl shadow-lg border border-gray-700 flex flex-col md:flex-row justify-between items-center gap-4 mb-4">
        
        <div class="flex items-center gap-4">
            <div id="statusIndicator" class="w-4 h-4 bg-gray-500 rounded-full"></div>
            <div>
                <h1 class="text-xl font-bold tracking-wider text-white">
                    ANTROL <span class="text-blue-500">WORKER</span>
                </h1>
                <div class="flex items-center gap-2 text-xs text-gray-400">
                    <span>Status: <span id="txtStatus">Standby</span></span>
                    <span class="text-gray-600">|</span>
                    <span id="countdownTimer" class="font-mono text-yellow-400 font-bold hidden">Next Scan: 30s</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 bg-gray-900 p-2 rounded-lg border border-gray-700">
            <div class="text-xs text-gray-400 font-bold mr-2">MANUAL RESEND:</div>
            <input type="date" id="tgl_mulai" class="bg-gray-800 text-white text-sm border border-gray-600 rounded px-2 py-1 focus:outline-none focus:border-blue-500">
            <span class="text-gray-500">-</span>
            <input type="date" id="tgl_akhir" class="bg-gray-800 text-white text-sm border border-gray-600 rounded px-2 py-1 focus:outline-none focus:border-blue-500">
            
            <button onclick="runManualResend()" id="btnResend" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-1 rounded text-sm font-bold transition flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                EKSEKUSI
            </button>
        </div>

        <div class="flex gap-2">
            <button onclick="clearTerminal()" class="bg-gray-700 hover:bg-gray-600 text-gray-300 px-3 py-1 rounded text-xs">Clear Log</button>
            <a href="index.php" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-bold">Home</a>
        </div>
    </div>

    <div class="flex-1 terminal-window rounded-xl p-4 overflow-y-auto relative" id="terminalContainer">
        <div id="terminalContent" class="space-y-1">
            <div class="text-gray-500 text-sm">System initialized. Waiting for cycle...</div>
        </div>
        
        <div id="loadingIcon" class="absolute bottom-4 right-4 hidden">
            <svg class="animate-spin h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>

    <script>
        // KONFIGURASI
        const SYNC_INTERVAL_SEC = 30; // Detik antar request
        let countdown = SYNC_INTERVAL_SEC;
        let timerInterval;

        // ==========================================================
        // 1. SYSTEM INITIALIZATION & AUTO DATE
        // ==========================================================
        $(document).ready(function() {
            setTodayDate();
            
            // Mulai Auto Sync Worker
            runSyncWorker();
            
            // Cek Pergantian Hari Setiap 1 Menit
            setInterval(checkMidnightRefresh, 60000); 
        });

        function setTodayDate() {
            const today = new Date();
            // Format YYYY-MM-DD sesuai zona waktu lokal (Penting!)
            const offset = today.getTimezoneOffset() * 60000;
            const localISOTime = new Date(today - offset).toISOString().split('T')[0];
            
            $('#tgl_mulai').val(localISOTime);
            $('#tgl_akhir').val(localISOTime);
        }

        function checkMidnightRefresh() {
            const now = new Date();
            const offset = now.getTimezoneOffset() * 60000;
            const todayStr = new Date(now - offset).toISOString().split('T')[0];
            
            const inputDate = $('#tgl_mulai').val();
            
            // Jika tanggal hari ini > tanggal input, berarti sudah ganti hari. RELOAD.
            if (todayStr > inputDate) {
                console.log("Midnight detected! Reloading system...");
                location.reload(); 
            }
        }

        // ==========================================================
        // 2. TERMINAL & LOGGING (THE "FULL DEBUGGING" PART)
        // ==========================================================
        function addLog(message, type = 'info') {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', { hour12: false });
            
            let colorClass = 'text-gray-300';
            if (type === 'success') colorClass = 'log-success';
            if (type === 'error') colorClass = 'log-error';
            if (type === 'warning') colorClass = 'log-warn';
            if (type === 'json') colorClass = 'log-json';

            // Auto-Detect JSON String inside message
            let formattedMessage = message;
            
            // Jika pesan terlihat seperti JSON (dimulai { atau [), coba pretty print
            if (message.trim().startsWith('{') || message.trim().startsWith('[')) {
                try {
                    const jsonObj = JSON.parse(message);
                    // Format JSON dengan indentasi
                    formattedMessage = `<pre>${JSON.stringify(jsonObj, null, 2)}</pre>`;
                    colorClass = 'log-json'; // Override warna khusus JSON
                } catch (e) {
                    // Bukan JSON valid, biarkan text biasa
                }
            } else {
                // Highlight kata kunci penting untuk text biasa
                formattedMessage = message
                    .replace(/SUKSES/g, '<span class="log-success font-bold">SUKSES</span>')
                    .replace(/GAGAL/g, '<span class="log-error font-bold">GAGAL</span>')
                    .replace(/SKIP/g, '<span class="log-warn font-bold">SKIP</span>');
            }

            const html = `
                <div class="flex items-start hover:bg-gray-900 p-1 rounded">
                    <span class="log-time font-mono">[${timeStr}]</span>
                    <div class="${colorClass} flex-1 break-all">${formattedMessage}</div>
                </div>
            `;

            $('#terminalContent').append(html);
            scrollToBottom();
        }

        function scrollToBottom() {
            const el = document.getElementById('terminalContainer');
            el.scrollTop = el.scrollHeight;
        }

        function clearTerminal() {
            $('#terminalContent').html('<div class="text-gray-500 text-sm">Terminal cleared.</div>');
        }

        // ==========================================================
        // 3. CORE WORKER LOGIC
        // ==========================================================
        function runSyncWorker() {
            // Update UI State
            $('#statusIndicator').removeClass('bg-gray-500 bg-red-500').addClass('bg-green-500 status-active');
            $('#txtStatus').text("Running Sync...");
            $('#loadingIcon').removeClass('hidden');
            $('#countdownTimer').addClass('hidden');
            clearInterval(timerInterval); // Stop timer saat request jalan

            $.ajax({
                url: 'sync_worker.php',
                method: 'GET',
                dataType: 'json',
                success: function(res) {
                    // Parse logs dari array PHP
                    if (res.logs && res.logs.length > 0) {
                        res.logs.forEach(logLine => {
                            // Deteksi tipe log berdasarkan isi string
                            let type = 'info';
                            if (logLine.includes('SUKSES')) type = 'success';
                            if (logLine.includes('GAGAL') || logLine.includes('Error')) type = 'error';
                            if (logLine.includes('SKIP')) type = 'warning';
                            
                            // Bersihkan timestamp dari PHP karena JS buat sendiri biar sinkron
                            // Format PHP log: "[10:00:00] Pesan"
                            const cleanMsg = logLine.replace(/^\[.*?\]\s*/, '');
                            
                            addLog(cleanMsg, type);
                        });
                    } else {
                        // Optional: Jangan nyampah kalau kosong
                        // addLog("No pending tasks.", 'info'); 
                    }
                },
                error: function(xhr, status, error) {
                    addLog("Worker Error: " + error, 'error');
                },
                complete: function() {
                    $('#loadingIcon').addClass('hidden');
                    startCountdown(); // Mulai hitung mundur untuk siklus berikutnya
                }
            });
        }

        function startCountdown() {
            countdown = SYNC_INTERVAL_SEC;
            $('#statusIndicator').removeClass('status-active bg-green-500').addClass('bg-gray-500');
            $('#txtStatus').text("Idle");
            $('#countdownTimer').removeClass('hidden');
            
            updateTimerDisplay();

            timerInterval = setInterval(() => {
                countdown--;
                updateTimerDisplay();

                if (countdown <= 0) {
                    clearInterval(timerInterval);
                    runSyncWorker(); // TRIGGER ULANG
                }
            }, 1000);
        }

        function updateTimerDisplay() {
            $('#countdownTimer').text(`Next Scan: ${countdown}s`);
        }

        // ==========================================================
        // 4. MANUAL RESEND (SAPU BERSIH)
        // ==========================================================
        function runManualResend() {
            const btn = $('#btnResend');
            const oriText = btn.html();
            const t1 = $('#tgl_mulai').val();
            const t2 = $('#tgl_akhir').val();

            if(!t1 || !t2) { alert("Pilih tanggal dulu!"); return; }

            // Kunci Tombol
            btn.prop('disabled', true).html('<span class="animate-spin">↻</span> Proses...');
            addLog(`--- MEMULAI MANUAL RESEND (${t1} s.d ${t2}) ---`, 'info');

            $.ajax({
                url: 'resend_worker.php',
                method: 'POST',
                data: { tgl_mulai: t1, tgl_akhir: t2 },
                dataType: 'json',
                success: function(res) {
                    if (res.logs && res.logs.length > 0) {
                        res.logs.forEach(logLine => {
                            let type = 'info';
                            if (logLine.includes('Sukses')) type = 'success';
                            if (logLine.includes('GAGAL')) type = 'error';
                            if (logLine.includes('SKIP')) type = 'warning';
                            addLog(logLine, type);
                        });
                    }
                    addLog(`MANUAL DONE: ${res.message}`, 'success');
                },
                error: function(xhr, status, error) {
                    addLog("Manual Resend Error: " + error, 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html(oriText);
                }
            });
        }
    </script>
</body>
</html>