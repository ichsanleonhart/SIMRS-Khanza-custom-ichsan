<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orthanc Injector Master</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background-color: #121212; color: #e0e0e0; font-family: 'Segoe UI', sans-serif; }
        .card { background-color: #1e1e1e; border: 1px solid #333; }
        .table { color: #ccc; }
        .table thead { background-color: #000; color: #fff; }
        .form-control-dark { background-color: #2d2d2d; border: 1px solid #444; color: #fff; }
        .form-control-dark:focus { background-color: #2d2d2d; color: #fff; border-color: #0dcaf0; box-shadow: none; }
        .badge-pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
        /* Pagination Custom */
        .page-link { background-color: #2d2d2d; border-color: #444; color: #aaa; }
        .page-link:hover { background-color: #444; color: #fff; }
        .page-item.active .page-link { background-color: #0dcaf0; border-color: #0dcaf0; color: #000; }
        .page-item.disabled .page-link { background-color: #1a1a1a; border-color: #333; }
    </style>
</head>
<body class="p-4">

<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="text-info fw-bold"><i class="fa-solid fa-syringe"></i> Orthanc Injector <span class="badge bg-warning text-dark fs-6">Ultimate</span></h2>
            <p class="text-muted mb-0">Bridge & Auto-Reconciliation System</p>
        </div>
        <div class="col-md-6 text-end">
            <div id="status-badge" class="badge bg-success badge-pulse px-3 py-2 fs-6">ENGINE RUNNING</div>
            <div class="mt-2 small text-secondary" id="last-check">Waiting sync...</div>
            <div class="mt-1 small text-info" id="range-info">Scope: Auto</div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card border-info h-100">
                <div class="card-header bg-transparent border-info text-info fw-bold">
                    <i class="fa-solid fa-bolt"></i> Manual Injection
                </div>
                <div class="card-body">
                    <p class="small text-muted">Paksa sistem menyisir rentang tanggal tertentu.</p>
                    <div class="mb-3">
                        <label class="form-label small text-secondary">Dari Tanggal</label>
                        <input type="date" id="inj_start" class="form-control form-control-dark">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-secondary">Sampai Tanggal</label>
                        <input type="date" id="inj_end" class="form-control form-control-dark">
                    </div>
                    <button id="btn-inject" class="btn btn-info w-100 fw-bold" onclick="runInjector(true)">
                        <i class="fa-solid fa-play"></i> Eksekusi Range Ini
                    </button>
                    <hr class="border-secondary">
                    <div class="text-center">
                        <small class="text-success"><i class="fa-solid fa-rotate"></i> Auto-Sync: 15 Detik (H-7)</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-list"></i> Riwayat Log Injeksi</span>
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadLogs(1)"><i class="fa-solid fa-arrows-rotate"></i> Refresh</button>
                </div>
                
                <div class="card-body bg-secondary bg-opacity-10 border-bottom border-secondary">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <input type="text" id="log_search" class="form-control form-control-dark form-control-sm" placeholder="🔍 Cari Nama / No.RM / ACSN...">
                        </div>
                        <div class="col-md-3">
                            <input type="date" id="log_date_start" class="form-control form-control-dark form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <input type="date" id="log_date_end" class="form-control form-control-dark form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary btn-sm w-100" onclick="loadLogs(1)">Filter</button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-dark table-striped mb-0 align-middle">
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
                                <tr><td colspan="6" class="text-center py-5"><i class="fa-solid fa-spinner fa-spin fa-2x"></i><br>Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card-footer bg-dark border-top border-secondary d-flex justify-content-between align-items-center">
                    <small class="text-muted" id="page-info">Halaman 1 dari 1</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="pagination">
                            </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Init Default Date Inputs
    document.getElementById('inj_start').valueAsDate = new Date();
    document.getElementById('inj_end').valueAsDate = new Date();
    
    // Global State untuk Pagination Log
    let currentPage = 1;

    // --- FUNGSI 1: INJECTOR ENGINE (Background & Manual) ---
    function runInjector(isManual = false) {
        let payload = {};
        if (isManual) {
            payload.start_date = $('#inj_start').val();
            payload.end_date = $('#inj_end').val();
            $('#btn-inject').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Memproses...');
            $('#status-badge').text('MANUAL RUNNING').removeClass('bg-success').addClass('bg-primary');
        } else {
            $('#status-badge').text('AUTO RUNNING').removeClass('bg-primary').addClass('bg-success');
        }

        $.ajax({
            url: 'injector_engine.php', // Panggil file yang sama
            type: 'GET',
            data: payload,
            dataType: 'json',
            success: function(res) {
                if (isManual) $('#btn-inject').prop('disabled', false).html('<i class="fa-solid fa-play"></i> Eksekusi Range Ini');
                
                if(res.status === 'success') {
                    $('#status-badge').text('ENGINE STANDBY');
                    $('#last-check').text("Sync: " + new Date().toLocaleTimeString());
                    $('#range-info').text("Scope: " + res.range_info);

                    if(res.processed > 0) {
                        // Jika ada data baru diproses, refresh tabel log otomatis
                        loadLogs(1);
                    }
                } else {
                    $('#status-badge').removeClass('bg-success').addClass('bg-danger').text('ERROR');
                }
            },
            error: function() {
                if (isManual) $('#btn-inject').prop('disabled', false).text('Error Connection');
            }
        });
    }

    // --- FUNGSI 2: LOG EXPLORER (View Data) ---
    function loadLogs(page) {
        currentPage = page;
        
        let search = $('#log_search').val();
        let d_start = $('#log_date_start').val();
        let d_end = $('#log_date_end').val();

        $.ajax({
            url: 'injector_engine.php',
            type: 'GET',
            data: {
                mode: 'view_log', // Mode khusus melihat log
                page: page,
                search: search,
                date_start: d_start,
                date_end: d_end
            },
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    renderTable(res.data);
                    renderPagination(res.pagination);
                }
            }
        });
    }

    function renderTable(data) {
        let html = '';
        if(data.length === 0) {
            html = '<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data ditemukan.</td></tr>';
        } else {
            $.each(data, function(i, row) {
                html += `<tr>
                    <td><small>${row.waktu_suntik}</small></td>
                    <td class="fw-bold text-info">${row.no_rm}</td>
                    <td>${row.nama_pasien}</td>
                    <td class="text-danger small fst-italic">${row.acsn_lama ? row.acsn_lama : '-'}</td>
                    <td class="text-success fw-bold"><i class="fa-solid fa-check"></i> ${row.acsn_baru}</td>
                    <td><span class="badge bg-success bg-opacity-25 text-success border border-success">${row.status}</span></td>
                </tr>`;
            });
        }
        $('#log-body').html(html);
    }

    function renderPagination(pg) {
        $('#page-info').text(`Halaman ${pg.current_page} dari ${pg.total_pages} (Total: ${pg.total_data})`);
        
        let html = '';
        // Prev
        let prevDisabled = pg.current_page == 1 ? 'disabled' : '';
        html += `<li class="page-item ${prevDisabled}"><a class="page-link" href="#" onclick="loadLogs(${pg.current_page - 1})">Prev</a></li>`;
        
        // Next
        let nextDisabled = pg.current_page == pg.total_pages ? 'disabled' : '';
        html += `<li class="page-item ${nextDisabled}"><a class="page-link" href="#" onclick="loadLogs(${pg.current_page + 1})">Next</a></li>`;
        
        $('#pagination').html(html);
    }

    // --- INISIALISASI ---
    // 1. Load Log Pertama Kali
    loadLogs(1);
    
    // 2. Jalankan Engine Auto-Sync (15 Detik)
    runInjector(false); 
    setInterval(function() { runInjector(false); }, 15000);

    // 3. Bind tombol Enter di Search Log
    $('#log_search').keypress(function(e){
        if(e.which == 13) loadLogs(1);
    });

</script>
</body>
</html>