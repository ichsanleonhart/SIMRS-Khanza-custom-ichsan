<?php
$page_title = "Sistem Peringatan Dini Plafon";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- PERBAIKAN CSS (CONTRAST FIX) --- */
        body { background-color: #0f172a; color: #f1f5f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { background-color: #1e293b; border: 1px solid #334155; }
        
        /* Paksa semua heading dan text menjadi terang */
        h1, h2, h3, h4, h5, h6, .card-title { color: #f8fafc !important; }
        .text-muted { color: #94a3b8 !important; } /* Abu-abu terang agar terbaca */
        
        .log-terminal {
            height: 500px;
            overflow-y: scroll;
            background-color: #020617; /* Lebih gelap untuk terminal */
            color: #4ade80; /* Hijau terminal */
            font-family: 'Courier New', Courier, monospace;
            padding: 15px;
            font-size: 0.8rem;
            border-radius: 6px;
            border: 1px solid #475569;
            box-shadow: inset 0 0 10px #000;
        }
        .status-indicator {
            width: 15px; height: 15px; border-radius: 50%; display: inline-block;
        }
        .status-ok { background-color: #22c55e; box-shadow: 0 0 10px #22c55e; }
        .status-busy { background-color: #eab308; box-shadow: 0 0 10px #eab308; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #1e293b; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #64748b; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3 class="fw-bold text-primary"><i class="fas fa-shield-virus me-2"></i><?= $page_title ?></h3>
            <div class="text-end">
                <span class="badge bg-secondary p-2">Mode: RAWAT INAP ONLY</span>
                <span class="badge bg-info p-2 ms-2">WA Group: ...472@g.us</span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Status Monitor</h5>
                    
                    <div class="d-flex align-items-center mb-3">
                        <div id="led-status" class="status-indicator status-ok me-3"></div>
                        <h2 class="m-0 fw-bold" id="txt-status">STANDBY</h2>
                    </div>
                    
                    <hr class="border-secondary">
                    
                    <div class="mb-3">
                        <small class="text-muted">Countdown Next Scan</small>
                        <h1 class="fw-bold text-warning" id="countdown">600</h1>
                    </div>

                    <div class="row text-center mt-4">
                        <div class="col-6">
                            <h4 id="stat-processed" class="fw-bold text-white">0</h4>
                            <small class="text-muted">Pasien Dicek</small>
                        </div>
                        <div class="col-6">
                            <h4 id="stat-notified" class="fw-bold text-danger">0</h4>
                            <small class="text-muted">Alert Terkirim</small>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top border-secondary">
                        <button onclick="runCheck(true)" class="btn btn-primary w-100 fw-bold">
                            <i class="fas fa-sync-alt me-2"></i> Cek Paksa & Debug
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-transparent border-secondary d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-light"><i class="fas fa-terminal me-2"></i>Live System Logs</span>
                    <button onclick="clearLog()" class="btn btn-sm btn-outline-secondary">Clear</button>
                </div>
                <div class="card-body p-0">
                    <div id="console-log" class="log-terminal">
                        <div class="text-muted">// Menunggu siklus pengecekan pertama...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const REFRESH_INTERVAL = 600; // 600 detik (10 Menit)
    let timer = REFRESH_INTERVAL;
    let intervalId;

    function log(msg, type='info') {
        const time = new Date().toLocaleTimeString('id-ID', { hour12: false });
        let color = '#94a3b8'; // Default greyish
        
        if (type === 'success') color = '#4ade80'; // Green
        if (type === 'error') color = '#f87171'; // Red
        if (type === 'warn') color = '#fbbf24'; // Yellow
        if (type === 'debug') color = '#60a5fa'; // Blue for calculation details
        
        const el = document.createElement('div');
        el.style.color = color;
        el.style.borderBottom = '1px solid #1e293b';
        el.style.padding = '2px 0';
        el.innerHTML = `<span style="opacity:0.5; margin-right:8px">[${time}]</span> ${msg}`;
        
        const container = document.getElementById('console-log');
        container.appendChild(el);
        container.scrollTop = container.scrollHeight;
    }

    function clearLog() {
        document.getElementById('console-log').innerHTML = '';
    }

    async function runCheck(manual = false) {
        if(manual) log("User melakukan scan manual...", 'warn');
        
        document.getElementById('led-status').className = 'status-indicator status-busy';
        document.getElementById('txt-status').innerText = 'SCANNING...';
        
        try {
            // Kita tambahkan parameter ?debug=1 agar API memberikan output lebih detail
            const response = await fetch('api_monitor_ranap.php?debug=1');
            const data = await response.json();
            
            if(data.status === 'success') {
                // Update UI Stats
                document.getElementById('stat-processed').innerText = data.processed;
                let currentNotif = parseInt(document.getElementById('stat-notified').innerText);
                document.getElementById('stat-notified').innerText = currentNotif + data.notified;

                // Tampilkan Logs dari Server
                if(data.logs && data.logs.length > 0) {
                    data.logs.forEach(l => {
                        // Deteksi tipe log dari stringnya
                        let type = 'info';
                        if(l.includes('[ALERT]')) type = 'success';
                        if(l.includes('[SKIP]')) type = 'warn';
                        if(l.includes('[INFO]')) type = 'debug'; // Ini untuk log perhitungan
                        if(l.includes('[ERROR]')) type = 'error';
                        
                        log(l, type);
                    });
                } else {
                     log("Tidak ada data log yang dikembalikan server.", 'warn');
                }

                if(data.processed === 0) log("Tidak ada pasien Ranap Aktif yang ditemukan.", 'warn');

            } else {
                log("API Error Status: " + JSON.stringify(data), 'error');
            }
        } catch (err) {
            console.error(err);
            log("Connection Error: " + err.message, 'error');
            log("Cek Console Browser (F12) untuk detail error HTML.", 'error');
        }

        // Reset UI
        document.getElementById('led-status').className = 'status-indicator status-ok';
        document.getElementById('txt-status').innerText = 'STANDBY';
        timer = REFRESH_INTERVAL; // Reset timer
    }

    // Countdown Timer
    setInterval(() => {
        timer--;
        document.getElementById('countdown').innerText = timer;
        if (timer <= 0) {
            runCheck();
            timer = REFRESH_INTERVAL;
        }
    }, 1000);

    // Run first time
    runCheck();
</script>
</body>
</html>