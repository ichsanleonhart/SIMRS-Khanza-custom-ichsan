<?php
// =======================================================================
// SECURITY: LOCALHOST ONLY
// Script ini diletakkan di paling atas sebelum HTML apapun dimuat.
// =======================================================================
$user_ip = $_SERVER['REMOTE_ADDR'];

// Daftar IP Localhost yang diizinkan:
// 127.0.0.1 = Localhost IPv4
// ::1       = Localhost IPv6
if ($user_ip !== '127.0.0.1' && $user_ip !== '::1') {
    // Tampilan jika akses ditolak
    die("
    <style>
        body { background: #0d1117; color: #f85149; font-family: monospace; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { border: 1px solid #30363d; padding: 30px; border-radius: 10px; background: #010409; text-align: center; }
        h1 { margin-top: 0; }
    </style>
    <div class='box'>
        <h1>⛔ AKSES DITOLAK</h1>
        <p>Aplikasi ini hanya boleh dibuka di Komputer Server (Localhost).</p>
        <p><small>IP Anda: $user_ip</small></p>
    </div>
    ");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard WA Gateway Khanza (Auto-Pilot)</title>
    <style>
        body { background-color: #0d1117; color: #c9d1d9; font-family: 'Consolas', 'Courier New', monospace; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { border-bottom: 2px solid #238636; padding-bottom: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        
        #log-window {
            background-color: #010409;
            border: 1px solid #30363d;
            height: 500px;
            overflow-y: scroll;
            padding: 15px;
            font-size: 13px;
            box-shadow: 0 0 15px rgba(0,0,0,0.5);
            border-radius: 6px;
        }
        
        .log-line { margin-bottom: 4px; border-bottom: 1px solid #161b22; padding: 2px 0; display: block; }
        .success { color: #3fb950; }
        .error { color: #f85149; }
        .info { color: #8b949e; }
        .warning { color: #d29922; }
        .timestamp { color: #58a6ff; font-weight: bold; margin-right: 10px; }

        button { padding: 10px 20px; cursor: pointer; font-weight: bold; border-radius: 6px; border: none; font-family: inherit; }
        .btn-start { background: #238636; color: white; }
        .btn-stop { background: #da3633; color: white; display: none; }
        
        #status-bar { margin-top: 10px; padding: 10px; background: #161b22; border-radius: 4px; border: 1px solid #30363d; display: flex; justify-content: space-between; }
        #countdown-timer { font-weight: bold; color: #e3b341; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h2>🤖 WA GATEWAY (AUTO PILOT)</h2>
            <small>Database: 192.168.1.5 | Node: Port 8100</small>
        </div>
        <div>
            <button id="btn-start" class="btn-start" onclick="manualStart()">START SEKARANG</button>
            <button id="btn-stop" class="btn-stop" onclick="stopEngine()">STOP SERVICE</button>
        </div>
    </div>

    <div id="status-bar">
        <span id="status-text" style="color: #da3633;">🔴 OFFLINE (Menunggu Auto-Start...)</span>
        <span id="countdown-timer"></span>
    </div>

    <div id="log-window">
        <div class="log-line info">Sistem siap.</div>
    </div>
</div>

<script>
    let isRunning = false;
    let timerId = null;     
    let countdownId = null; 
    let autoStartId = null; 

    const logWindow = document.getElementById('log-window');
    const statusText = document.getElementById('status-text');
    const countdownEl = document.getElementById('countdown-timer');

    // --- KONFIGURASI DELAY (Detik) ---
    const DELAY_MIN = 20; 
    const DELAY_MAX = 120;
    const DELAY_EMPTY = 5; 
    const AUTO_START_DELAY = 300; // 5 Menit

    // --- AUTO START LOGIC ---
    window.onload = function() {
        addLog(`⏳ Sistem akan AUTO-START dalam ${AUTO_START_DELAY/60} menit...`, 'warning');
        startCountdown(AUTO_START_DELAY, () => {
            addLog("🚀 Waktu habis! Melakukan Auto-Start...", 'success');
            startEngine();
        }, "Auto-Start dalam: ");
    };

    function addLog(msg, type) {
        const div = document.createElement('div');
        div.className = `log-line ${type}`;
        const time = new Date().toLocaleTimeString('id-ID');
        div.innerHTML = `<span class='timestamp'>[${time}]</span> ${msg}`;
        logWindow.appendChild(div);
        logWindow.scrollTop = logWindow.scrollHeight; 
    }

    function manualStart() {
        if(countdownId) clearInterval(countdownId);
        if(autoStartId) clearTimeout(autoStartId);
        countdownEl.innerText = "";
        startEngine();
    }

    function startEngine() {
        if(isRunning) return;
        
        isRunning = true;
        document.getElementById('btn-start').style.display = 'none';
        document.getElementById('btn-stop').style.display = 'inline-block';
        statusText.innerText = "🟢 ONLINE (SCANNING)";
        statusText.style.color = "#3fb950";
        
        addLog("=== SERVICE DIMULAI ===", "info");
        runProcessor(); 
    }

    function stopEngine() {
        isRunning = false;
        if(timerId) clearTimeout(timerId);
        if(countdownId) clearInterval(countdownId);
        
        document.getElementById('btn-start').style.display = 'inline-block';
        document.getElementById('btn-stop').style.display = 'none';
        statusText.innerText = "🔴 OFFLINE";
        statusText.style.color = "#da3633";
        countdownEl.innerText = "";
        addLog("=== SERVICE DIHENTIKAN MANUAL ===", "error");
    }

    // --- CORE LOGIC ---
    function runProcessor() {
        if(!isRunning) return;

        fetch('processor.php')
            .then(response => response.json())
            .then(data => {
                let nextDelay = DELAY_EMPTY; 
                let message = "";

                if (data.status === 'success') {
                    addLog(data.log, 'success');
                    nextDelay = Math.floor(Math.random() * (DELAY_MAX - DELAY_MIN + 1) + DELAY_MIN);
                    message = `Cooldown Anti-Spam: `;
                } else if (data.status === 'error') {
                    addLog(data.log, 'error');
                    nextDelay = 10;
                    message = "Retry error dalam: ";
                } else {
                    nextDelay = DELAY_EMPTY;
                    message = "Cek antrian lagi: ";
                }

                if(isRunning) {
                    startCountdown(nextDelay, () => {
                        runProcessor(); 
                    }, message);
                }
            })
            .catch(err => {
                addLog(`Gagal koneksi: ${err}`, 'error');
                if(isRunning) {
                    startCountdown(10, () => runProcessor(), "Reconnecting: ");
                }
            });
    }

    function startCountdown(seconds, callback, prefixText = "Menunggu: ") {
        let counter = seconds;
        countdownEl.innerText = `${prefixText} ${counter}s`;
        if(countdownId) clearInterval(countdownId);

        countdownId = setInterval(() => {
            counter--;
            if(counter < 0) {
                clearInterval(countdownId);
                countdownEl.innerText = "Memproses...";
                if(callback) callback();
            } else {
                countdownEl.innerText = `${prefixText} ${counter}s`;
            }
        }, 1000);
    }
</script>

</body>
</html>