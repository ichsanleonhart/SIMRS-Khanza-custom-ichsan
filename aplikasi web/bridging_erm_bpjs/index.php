<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERM BPJS Bridge - Terminal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background-color: #0f172a; color: #38bdf8; font-family: 'Courier New', Courier, monospace; }
        .terminal { height: 70vh; overflow-y: auto; background-color: #000; border: 1px solid #334155; padding: 1rem; border-radius: 0.5rem; }
        .log-entry { margin-bottom: 2px; border-bottom: 1px solid #1e293b; }
        .log-success { color: #4ade80; }
        .log-error { color: #f87171; }
        .log-idle { color: #94a3b8; font-style: italic; }
        
        /* Scrollbar Keren */
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 5px; }
    </style>
</head>
<body class="p-6">

    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-2xl font-bold text-white">ERM BPJS BRIDGING <span class="text-xs bg-blue-900 px-2 py-1 rounded">PHP 7.3 COMPATIBLE</span></h1>
                <p class="text-sm text-slate-400">Status Monitor & Sender Service</p>
            </div>
            <div class="text-right">
                <div id="status-indicator" class="text-red-500 font-bold text-xl">OFFLINE 🔴</div>
                <div class="text-xs text-slate-500" id="clock">00:00:00</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            
            <div class="bg-slate-800 p-4 rounded border border-slate-700">
                <h3 class="text-white font-bold mb-2">🤖 AUTO MODE (24 Jam)</h3>
                <button id="btn-start" onclick="startAuto()" class="w-full bg-green-700 hover:bg-green-600 text-white font-bold py-2 px-4 rounded mb-2">START SERVICE</button>
                <button id="btn-stop" onclick="stopAuto()" class="w-full bg-red-800 hover:bg-red-700 text-white font-bold py-2 px-4 rounded hidden">STOP SERVICE</button>
            </div>

            <div class="bg-slate-800 p-4 rounded border border-slate-700">
                <h3 class="text-white font-bold mb-2">📅 KIRIM MUNDUR</h3>
                <div class="flex gap-2 mb-2">
                    <input type="date" id="tgl_mulai" class="bg-slate-700 text-white p-1 rounded w-1/2">
                    <input type="date" id="tgl_akhir" class="bg-slate-700 text-white p-1 rounded w-1/2">
                </div>
                <button onclick="kirimPeriode()" class="w-full bg-blue-700 hover:bg-blue-600 text-white py-1 rounded">Kirim Periode Ini</button>
            </div>

            <div class="bg-slate-800 p-4 rounded border border-slate-700">
                <h3 class="text-white font-bold mb-2">🔧 RESEND MANUAL</h3>
                <input type="text" id="no_sep_manual" placeholder="No. SEP" class="bg-slate-700 text-white p-2 rounded w-full mb-2">
                <button onclick="kirimManual()" class="w-full bg-yellow-700 hover:bg-yellow-600 text-white py-1 rounded">Resend SEP Ini</button>
            </div>
        </div>

        <div id="terminal" class="terminal shadow-2xl">
            <div class="log-entry text-green-500">System Ready. Waiting for command...</div>
        </div>
    </div>

    <script>
        let isRunning = false;
        let workerTimeout;

        function updateClock() {
            const now = new Date();
            document.getElementById('clock').innerText = now.toLocaleTimeString();
        }
        setInterval(updateClock, 1000);

        function log(msg, type = 'normal') {
            const term = document.getElementById('terminal');
            const time = new Date().toLocaleTimeString();
            let colorClass = 'text-slate-300';
            
            if (type === 'success') colorClass = 'log-success';
            else if (type === 'error') colorClass = 'log-error';
            else if (type === 'idle') colorClass = 'log-idle';

            const div = document.createElement('div');
            div.className = `log-entry ${colorClass}`;
            div.innerHTML = `<span class="text-slate-600">[${time}]</span> ${msg}`;
            
            term.appendChild(div);
            term.scrollTop = term.scrollHeight; // Auto scroll

            // Prevent Memory Leak: Keep last 500 lines
            if (term.childElementCount > 500) {
                term.removeChild(term.firstChild);
            }
        }

        // --- CORE WORKER FUNCTION ---
        function runWorker(mode, extraData = {}) {
            if (!isRunning && mode === 'auto') return;

            const payload = { mode: mode, ...extraData };

            $.ajax({
                url: 'erm_worker.php',
                method: 'POST',
                data: payload,
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        if (res.logs && res.logs.length > 0) {
                            res.logs.forEach(l => {
                                const logType = l.includes('SUKSES') ? 'success' : 'error';
                                log(l, logType);
                            });
                        }
                    } else if (res.status === 'idle') {
                        if (mode !== 'auto') log(res.message, 'idle'); 
                        // Jika auto mode dan idle, jangan spam log
                    } else {
                        log("Unknown Response: " + JSON.stringify(res), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    log(`Connection Error: ${error}`, 'error');
                },
                complete: function() {
                    // Jika mode auto, jalankan lagi setelah delay
                    if (mode === 'auto' && isRunning) {
                        const delay = 5000; // 5 Detik delay antar request
                        document.getElementById('status-indicator').innerText = `RUNNING (Next in ${delay/1000}s) 🟢`;
                        workerTimeout = setTimeout(() => runWorker('auto'), delay);
                    } else if (mode === 'auto' && !isRunning) {
                         document.getElementById('status-indicator').innerText = "OFFLINE 🔴";
                    }
                }
            });
        }

        // --- CONTROLS ---
        function startAuto() {
            if (isRunning) return;
            isRunning = true;
            document.getElementById('btn-start').classList.add('hidden');
            document.getElementById('btn-stop').classList.remove('hidden');
            document.getElementById('status-indicator').innerText = "STARTING... 🟢";
            document.getElementById('status-indicator').className = "text-green-500 font-bold text-xl";
            
            log("Service STARTED. Scanning database...", 'success');
            runWorker('auto');
        }

        function stopAuto() {
            isRunning = false;
            clearTimeout(workerTimeout);
            document.getElementById('btn-start').classList.remove('hidden');
            document.getElementById('btn-stop').classList.add('hidden');
            document.getElementById('status-indicator').innerText = "OFFLINE 🔴";
            document.getElementById('status-indicator').className = "text-red-500 font-bold text-xl";
            log("Service STOPPED.", 'error');
        }

        function kirimManual() {
            const noSep = document.getElementById('no_sep_manual').value;
            if (!noSep) { alert("Isi No SEP!"); return; }
            log(`Manual Request for ${noSep}...`, 'normal');
            runWorker('manual', { no_sep: noSep });
        }

        function kirimPeriode() {
            const tgl1 = document.getElementById('tgl_mulai').value;
            const tgl2 = document.getElementById('tgl_akhir').value;
            if (!tgl1 || !tgl2) { alert("Isi tanggal!"); return; }
            
            if (confirm(`Yakin kirim data dari ${tgl1} s.d ${tgl2}?`)) {
                log(`Bulk Request ${tgl1} - ${tgl2}...`, 'normal');
                // Untuk periode, mungkin perlu loop khusus di JS atau PHP, 
                // tapi di sini kita trigger sekali, PHP yang handle logic loop/limitnya
                runWorker('periode', { tgl_mulai: tgl1, tgl_akhir: tgl2 });
            }
        }

        // Set Default Date
        document.getElementById('tgl_mulai').valueAsDate = new Date();
        document.getElementById('tgl_akhir').valueAsDate = new Date();
    </script>
</body>
</html>