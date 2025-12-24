<?php
// index.php (orthanc_injector)
// VERSI: MONITOR + AUTO INJECTOR + AUTO RESCUE
// Halaman ini sekarang menjalankan DUA misi otomatis sekaligus.
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Radiology Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background-color: #1a1d20; color: #e0e0e0; font-family: 'Segoe UI', monospace; }
        .card { background-color: #212529; border: 1px solid #343a40; }
        .form-control, .form-select { background-color: #2b3035; border: 1px solid #495057; color: #fff; }
        .form-control:focus { background-color: #2b3035; color: #fff; border-color: #0dcaf0; }
        .table { color: #ccc; }
        .table thead { background-color: #000; }
        .badge-pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
        /* Pagination */
        .page-link { background-color: #212529; border-color: #343a40; color: #aaa; }
        .page-item.active .page-link { background-color: #0dcaf0; border-color: #0dcaf0; color: #000; }
        
        .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; }
    </style>
</head>
<body class="p-4">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-info fw-bold"><i class="fa-solid fa-satellite-dish"></i> Radiology Monitor</h2>
            <div class="d-flex gap-3 align-items-center">
                <small>Injector: <span id="sys-status" class="badge bg-success badge-pulse">ONLINE (Auto H-7)</span></small>
                
                <small>Rescue Bot: <span id="rescue-status" class="badge bg-secondary">WAITING...</span></small>
            </div>
            <small class="text-muted d-block mt-1" id="last-sync">Waiting sync...</small>
        </div>
        <a href="login.php" class="btn btn-outline-warning"><i class="fa-solid fa-lock"></i> Login Super Admin</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-dark border-secondary d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Injeksi</span>
            <button class="btn btn-sm btn-outline-secondary" onclick="loadLogs(1)"><i class="fa-solid fa-rotate"></i> Refresh Data</button>
        </div>
        
        <div class="card-body bg-secondary bg-opacity-10 border-bottom border-secondary py-2">
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="text" id="search_q" class="form-control form-control-sm" placeholder="Cari Nama / RM / ACSN...">
                </div>
                <div class="col-md-3">
                    <input type="date" id="date_start" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <input type="date" id="date_end" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-info btn-sm w-100 fw-bold" onclick="loadLogs(1)">Cari Log</button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>No RM</th>
                            <th>Nama Pasien</th>
                            <th>ACSN Lama</th>
                            <th>ACSN Baru</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="log-body">
                        <tr><td colspan="6" class="text-center py-5">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-dark border-top border-secondary py-2">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted" id="page-info">Page 1</small>
                <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
            </div>
        </div>
    </div>
</div>

<script>
    // Set Default Filter Date (Hari Ini)
    document.getElementById('date_start').valueAsDate = new Date();
    document.getElementById('date_end').valueAsDate = new Date();

    // --- 1. ENGINE AUTO-INJECTOR (BACKGROUND) ---
    // Tugas: Memperbaiki ACSN yang salah (PR...)
    function runAutoInjector() {
        $('#sys-status').removeClass('bg-success').addClass('bg-warning').text('SYNCING...');
        $.ajax({
            url: 'injector_engine.php', 
            dataType: 'json',
            success: function(res) {
                $('#sys-status').removeClass('bg-warning bg-danger').addClass('bg-success').text('ONLINE (Auto H-7)');
                $('#last-sync').text('Last Sync: ' + new Date().toLocaleTimeString());
                
                // Jika ada injeksi baru sukses, refresh tabel log
                if(res.processed > 0) {
                    loadLogs(1); 
                }
            },
            error: function() {
                $('#sys-status').removeClass('bg-warning bg-success').addClass('bg-danger').text('CONNECTION LOST');
            }
        });
    }

    // --- 2. ENGINE AUTO-RESCUE (BACKGROUND) ---
    // Tugas: Mendorong data yang macet (WAIT -> READY) ke Router
    // --- AUTO RESCUE BOT LOGIC (UPGRADED: H-7 SCAN) ---
    function runAutoRescue() {
        // 1. Hitung Tanggal Akhir (Hari Ini)
        let endObj = new Date();
        let endDate = endObj.toISOString().split('T')[0];

        // 2. Hitung Tanggal Awal (Mundur 7 Hari)
        let startObj = new Date();
        startObj.setDate(startObj.getDate() - 7);
        let startDate = startObj.toISOString().split('T')[0];

        $('#next-rescue-timer').text('Bot Running (H-7)...');
        
        // Update status visual agar user tahu sedang scan rentang mana
        if($('#rescue-status').length) {
            $('#rescue-status').removeClass('bg-secondary bg-success').addClass('bg-warning').text('SCAN H-7...');
        }

        $.ajax({
            url: 'rescue_sender.php',
            data: { start_date: startDate, end_date: endDate }, // KIRIM RENTANG H-7
            dataType: 'json',
            success: function(res) {
                if(res.pushed_total > 0) {
                    let msg = `AUTO-RESCUE SUKSES: Mendorong ${res.pushed_total} data macet (${startDate} s/d ${endDate}).`;
                    addToLiveLog(msg, 'bot');
                    
                    // Update Indikator (khusus index.php)
                    if($('#rescue-status').length) {
                        $('#rescue-status').removeClass('bg-warning').addClass('bg-success').text('PUSHED: ' + res.pushed_total);
                    }
                    
                    // Refresh tabel
                    loadAdminTable(1);
                } else {
                    // Balikin status ke idle jika 0 data
                    if($('#rescue-status').length) {
                        $('#rescue-status').removeClass('bg-warning').addClass('bg-secondary').text('IDLE (H-7 Aman)');
                    }
                }
            },
            error: function() {
                addToLiveLog('Auto-Rescue Bot: Connection Failed', 'error');
                if($('#rescue-status').length) $('#rescue-status').addClass('bg-danger').text('ERROR');
            }
        });
    }

    // --- INTERVAL SETTING ---
    
    // 1. Jalankan Injector tiap 15 detik (Cukup cepat karena ini core function)
    setInterval(runAutoInjector, 15000);
    
    // 2. Jalankan Rescue tiap 12 JAM (Agar tidak agresif)
    // Rumus: 12 jam * 60 menit * 60 detik * 1000 ms = 43200000
    setInterval(runAutoRescue, 43200000); 

    // Start Awal saat halaman dibuka
    runAutoInjector();
    // Delay 10 detik agar injector jalan duluan, baru rescue menyusul pelan-pelan
    setTimeout(runAutoRescue, 10000);

    // --- 3. LOG VIEWER (FOREGROUND) ---
    function loadLogs(page) {
        let q = $('#search_q').val();
        let ds = $('#date_start').val();
        let de = $('#date_end').val();

        $.ajax({
            url: 'injector_engine.php',
            data: { mode: 'view_log', page: page, search: q, date_start: ds, date_end: de },
            dataType: 'json',
            success: function(res) {
                let html = '';
                if(res.data.length === 0) {
                    html = '<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data.</td></tr>';
                } else {
                    $.each(res.data, function(i, r) {
                        html += `<tr>
                            <td><small>${r.waktu_suntik}</small></td>
                            <td class="fw-bold text-info">${r.no_rm}</td>
                            <td>${r.nama_pasien}</td>
                            <td class="text-secondary small"><em>${r.acsn_lama ? r.acsn_lama : '-'}</em></td>
                            <td class="text-success fw-bold">${r.acsn_baru}</td>
                            <td><span class="badge bg-primary">${r.status}</span></td>
                        </tr>`;
                    });
                }
                $('#log-body').html(html);
                
                // Pagination Logic
                $('#page-info').text(`Page ${res.pagination.current_page} of ${res.pagination.total_pages} (Total: ${res.pagination.total_data})`);
                let pHtml = '';
                let curr = res.pagination.current_page;
                let last = res.pagination.total_pages;
                
                let prevDis = (curr == 1) ? 'disabled' : '';
                pHtml += `<li class="page-item ${prevDis}"><button class="page-link" onclick="loadLogs(${curr-1})">&laquo;</button></li>`;
                
                let nextDis = (curr == last) ? 'disabled' : '';
                pHtml += `<li class="page-item ${nextDis}"><button class="page-link" onclick="loadLogs(${curr+1})">&raquo;</button></li>`;
                $('#pagination').html(pHtml);
            }
        });
    }

    // Load Log Awal
    loadLogs(1);

    // Enter Key Search
    $('#search_q').keypress(function(e){ if(e.which == 13) loadLogs(1); });

</script>
</body>
</html>