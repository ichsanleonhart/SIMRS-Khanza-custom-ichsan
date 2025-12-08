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
    </style>
</head>
<body class="p-4">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-info fw-bold"><i class="fa-solid fa-satellite-dish"></i> Radiology Monitor</h2>
            <small>Engine Status: <span id="sys-status" class="badge bg-success badge-pulse">ONLINE (Auto H-7)</span></small>
            <small class="text-muted ms-2" id="last-sync">Waiting sync...</small>
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
    // Ini berjalan diam-diam tanpa mempedulikan filter tanggal di atas
    function runAutoInjector() {
        $('#sys-status').removeClass('bg-success').addClass('bg-warning').text('SYNCING...');
        $.ajax({
            url: 'injector_engine.php', 
            // Tidak kirim parameter = Mode Auto H-7 Default
            dataType: 'json',
            success: function(res) {
                $('#sys-status').removeClass('bg-warning bg-danger').addClass('bg-success').text('ONLINE (Auto H-7)');
                $('#last-sync').text('Last Sync: ' + new Date().toLocaleTimeString());
                
                // Jika ada injeksi baru sukses, refresh tabel log (biar user lihat update realtime)
                if(res.processed > 0) {
                    loadLogs(1); 
                }
            },
            error: function() {
                $('#sys-status').removeClass('bg-warning bg-success').addClass('bg-danger').text('CONNECTION LOST');
            }
        });
    }

    // Jalankan Engine tiap 15 detik
    setInterval(runAutoInjector, 15000);
    runAutoInjector(); // Jalankan sekali saat load

    // --- 2. LOG VIEWER (FOREGROUND) ---
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