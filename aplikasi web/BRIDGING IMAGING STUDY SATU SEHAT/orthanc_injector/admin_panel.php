<?php
// admin_panel.php
// VERSI 5.7: FULL DIAGNOSTIC (Enc, SR, Orthanc, SS Img)

require_once 'auth_check.php'; 
require_once 'config.php';
$current_user = $_SESSION['user_admin'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #121212; color: #e0e0e0; font-family: 'Segoe UI', monospace; }
        .card { background-color: #1e1e1e; border: 1px solid #333; }
        .form-control { background-color: #2d2d2d; border: 1px solid #444; color: #fff; }
        .form-control:focus { background-color: #2d2d2d; color: #fff; border-color: #0dcaf0; }
        .table-dark { --bs-table-bg: #1e1e1e; }
        .modal-content { background-color: #212529; border: 1px solid #444; color: #fff; }
        .modal-header { border-bottom: 1px solid #444; }
        .modal-footer { border-top: 1px solid #444; }
        .debug-box { background-color: #000; color: #00ff00; font-family: 'Consolas', monospace; font-size: 0.85rem; border: 1px solid #333; }
        .debug-header { cursor: pointer; user-select: none; }
        .status-cell { font-size: 0.9rem; font-weight: bold; }
        .fa-spin { animation-duration: 1s; }
        
        .pulse-green { animation: pulse-green 2s infinite; }
        @keyframes pulse-green { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark border-bottom border-secondary py-3">
    <div class="container-fluid px-4">
        <span class="navbar-brand fw-bold text-info"><i class="fa-solid fa-user-secret"></i> SUPER ADMIN PANEL</span>
        
        <div class="d-flex align-items-center gap-3">
            <div class="border border-secondary rounded px-3 py-1 bg-black d-flex align-items-center gap-2" title="Robot Penyelamat Berjalan Otomatis">
                <i class="fa-solid fa-robot text-success pulse-green"></i>
                <div class="d-flex flex-column" style="line-height: 1;">
                    <small class="text-muted" style="font-size: 0.65rem;">AUTO-RESCUE</small>
                    <span class="text-success fw-bold" style="font-size: 0.8rem;">ACTIVE (50m)</span>
                </div>
            </div>

            <span class="text-muted small border-start border-secondary ps-3">User: <b class="text-white"><?= $current_user ?></b></span>
            <a href="rescue_ui.php" class="btn btn-sm btn-outline-warning fw-bold"> <i class="fa-solid fa-truck-medical"></i> MANUAL RESCUE </a>
            <a href="logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="card border-info mb-4">
        <div class="card-header bg-dark border-info text-info fw-bold">
            <i class="fa-solid fa-clock-rotate-left"></i> Time-Travel Injector
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="small text-muted">Dari Tanggal</label>
                    <input type="date" id="inj_start" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="small text-muted">Sampai Tanggal</label>
                    <input type="date" id="inj_end" class="form-control">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-info w-100 fw-bold" onclick="runManualInject()">
                        <i class="fa-solid fa-bolt"></i> JALANKAN INJEKSI MANUAL
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-danger h-100 mb-4">
        <div class="card-header bg-dark border-danger text-danger fw-bold d-flex justify-content-between align-items-center">
            <span><i class="fa-solid fa-trash-can"></i> SatuSehat Data Manager (Sorted by ACSN)</span>
            <div>
                <button class="btn btn-sm btn-warning me-2 fw-bold" onclick="scanCurrentPage()">
                    <i class="fa-solid fa-magnifying-glass-chart"></i> Cek Status Halaman Ini
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="loadAdminTable(1)"><i class="fa-solid fa-rotate"></i> Refresh</button>
            </div>
        </div>
        <div class="card-body bg-secondary bg-opacity-10 py-2 border-bottom border-secondary">
            <div class="row g-2">
                <div class="col-md-4"><input type="text" id="crud_search" class="form-control form-control-sm" placeholder="Cari ACSN / Pasien..."></div>
                <div class="col-md-3"><input type="date" id="crud_start" class="form-control form-control-sm"></div>
                <div class="col-md-3"><input type="date" id="crud_end" class="form-control form-control-sm"></div>
                <div class="col-md-2 d-flex gap-1">
                    <button class="btn btn-danger btn-sm w-50" onclick="loadAdminTable(1)">Filter</button>
                    <button class="btn btn-outline-light btn-sm w-50" onclick="loadAdminTable(1, 200)" title="Tampilkan Max 200 data">Show All</button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead class="sticky-top bg-dark">
                        <tr>
                            <th>Waktu Inject</th>
                            <th>No RM</th>
                            <th>No Rawat</th>
                            <th>Pasien</th>
                            <th>ACSN</th>
                            <th class="text-center" title="SatuSehat Encounter">Enc</th>
                            <th class="text-center" title="Service Request Radiologi">SR</th>
                            <th class="text-center text-warning" title="Cek Penyimpanan Lokal">Orthanc</th>
                            <th class="text-center" style="width: 100px;">SS Img</th>
                            <th class="text-end" style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="crud-body"><tr><td colspan="10" class="text-center py-5">Memuat Data...</td></tr></tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-dark border-top border-secondary py-2">
            <div class="d-flex justify-content-between">
                <small class="text-muted" id="crud-page-info">-</small>
                <nav><ul class="pagination pagination-sm mb-0" id="crud-pagination"></ul></nav>
            </div>
        </div>
    </div>

    <div class="card bg-black border-secondary mb-5">
        <div class="card-header bg-dark border-secondary text-success font-monospace py-1 d-flex justify-content-between">
            <span><i class="fa-solid fa-terminal"></i> LIVE TECHNICIAN LOG</span>
            <small class="text-muted" id="next-rescue-timer">Next Auto-Rescue: ...</small>
        </div>
        <div id="live-console" class="font-monospace text-success">
            <div class="text-muted">> System Ready. Auto-Rescue Bot Initialized...</div>
        </div>
    </div>
</div>

<div class="modal fade" id="crudModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg"> 
        <div class="modal-content">
            <div class="modal-header border-warning">
                <h5 class="modal-title text-warning"><i class="fa-solid fa-magnifying-glass"></i> Audit SatuSehat</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-black">
                <div id="modal-loader" class="text-center my-4">
                    <div class="spinner-border text-info" role="status"></div>
                    <p class="mt-2">Connecting to Ministry of Health...</p>
                </div>
                <div id="modal-content" class="d-none">
                    <div class="mb-2 text-muted small">Target ACSN: <span id="m-acsn" class="text-info font-monospace fs-5"></span></div>
                    <div id="m-found-area" class="alert alert-dark border-secondary p-3 mb-3 d-none">
                        <div class="d-flex align-items-center mb-2"><i class="fa-solid fa-circle-check text-success fa-lg me-2"></i><span class="fw-bold text-success">DATA DITEMUKAN</span></div>
                        <div class="bg-black bg-opacity-50 p-2 rounded mb-2"><small class="text-secondary d-block">Logical Resource ID:</small><span class="text-white font-monospace text-break user-select-all" id="m-id"></span></div>
                        <div class="row g-2">
                            <div class="col-4"><div class="p-2 border border-secondary rounded text-center"><small class="text-muted">Status</small><br><b class="text-warning" id="m-status"></b></div></div>
                            <div class="col-4"><div class="p-2 border border-secondary rounded text-center"><small class="text-muted">Series</small><br><b class="text-white" id="m-series"></b></div></div>
                            <div class="col-4"><div class="p-2 border border-secondary rounded text-center"><small class="text-muted">Instances</small><br><b class="text-white" id="m-inst"></b></div></div>
                        </div>
                    </div>
                    <div id="m-notfound-area" class="alert alert-warning text-center mb-3 d-none"><i class="fa-solid fa-triangle-exclamation fa-2x mb-2"></i><br><strong>Data 404 Not Found</strong><br><small>Data belum terkirim atau sudah dihapus dari server.</small></div>
                    <div class="card debug-box">
                        <div class="card-header debug-header py-1 px-2 d-flex justify-content-between align-items-center" onclick="$('#debug-modal-console').slideToggle()"><small><i class="fa-solid fa-terminal"></i> TECHNICIAN LOG (DEBUG)</small><i class="fa-solid fa-chevron-down small"></i></div>
                        <div id="debug-modal-console" class="card-body p-2" style="max-height: 200px; overflow-y: auto;"><pre class="m-0 text-success" style="white-space: pre-wrap;" id="debug-text"></pre></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-none justify-content-between" id="modal-actions">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-danger fw-bold" onclick="executeDeleteFromModal()"><i class="fa-solid fa-radiation"></i> HAPUS PERMANEN</button>
            </div>
        </div>
    </div>
</div>

<script>
    let today = new Date();
    document.getElementById('inj_start').valueAsDate = today;
    document.getElementById('inj_end').valueAsDate = today;
    document.getElementById('crud_start').valueAsDate = today;
    document.getElementById('crud_end').valueAsDate = today;

    function addToLiveLog(msg, type = 'info') {
        let color = type === 'error' ? 'text-danger' : (type === 'success' ? 'text-info' : (type === 'bot' ? 'text-warning' : 'text-success'));
        let time = new Date().toLocaleTimeString();
        let prefix = type === 'bot' ? '<i class="fa-solid fa-robot"></i> ' : '';
        $('#live-console').prepend(`<div class="${color}"><span class="text-secondary">[${time}]</span> ${prefix}${msg}</div>`);
    }
    
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

    setInterval(runAutoRescue, 3000000); // 50 Menit
    
    let nextRescue = 3000;
    setInterval(() => {
        nextRescue--;
        let m = Math.floor(nextRescue / 60);
        let s = nextRescue % 60;
        $('#next-rescue-timer').text(`Next Auto-Rescue: ${m}m ${s}s`);
        if(nextRescue <= 0) nextRescue = 3000;
    }, 1000);


    // --- EXISTING FUNCTIONS ---
    function runManualInject() {
        let ds = $('#inj_start').val(), de = $('#inj_end').val();
        Swal.fire({title: 'Jalankan?', text: `Inject ${ds} s/d ${de}?`, icon: 'question', showCancelButton: true, confirmButtonText: 'Ya'}).then((res) => {
            if(res.isConfirmed) {
                addToLiveLog(`Running Manual Injector (${ds} to ${de})...`, 'info');
                Swal.fire({title: 'Memproses...', didOpen: () => Swal.showLoading()});
                $.ajax({ url: 'injector_engine.php', data: { start_date: ds, end_date: de }, dataType: 'json',
                    success: function(r) { Swal.fire('Selesai', `Proses: ${r.processed}`, 'success'); addToLiveLog(`Inject Completed. Processed: ${r.processed}`, 'success'); loadAdminTable(1); },
                    error: function(e) { addToLiveLog('Inject Error: Server Timeout/Error', 'error'); }
                });
            }
        });
    }

    function loadAdminTable(page, limit = 10) {
        let msg = limit > 10 ? 'Memuat SEMUA data...' : 'Memuat data...';
        $('#crud-body').html(`<tr><td colspan="10" class="text-center py-5 text-muted">${msg}</td></tr>`);
        
        $.ajax({
            url: 'injector_engine.php',
            data: { mode: 'view_log', page: page, limit: limit, search: $('#crud_search').val(), date_start: $('#crud_start').val(), date_end: $('#crud_end').val() },
            dataType: 'json',
            success: function(res) {
                let html = '';
                if(res.data.length === 0) html = '<tr><td colspan="10" class="text-center py-4 text-muted">Data tidak ditemukan.</td></tr>';
                else $.each(res.data, function(i, r) {
                    
                    let badgeEnc = r.has_encounter 
                        ? `<span class="badge bg-success" title="ID: ${r.id_encounter}"><i class="fa-solid fa-check"></i></span>` 
                        : `<span class="badge bg-danger" title="Encounter Belum Terkirim/Gagal"><i class="fa-solid fa-xmark"></i></span>`;

                    let badgeSR = r.has_servicerequest 
                        ? `<span class="badge bg-success" title="ID: ${r.id_servicerequest}"><i class="fa-solid fa-check"></i></span>` 
                        : `<span class="badge bg-danger" title="ServiceRequest Belum Terkirim/Gagal"><i class="fa-solid fa-xmark"></i></span>`;

                    html += `<tr id="row-${r.acsn_baru}">
                        <td><small>${r.waktu_suntik}</small></td>
                        <td>${r.no_rm}</td>
                        <td class="text-white-50"><small>${r.real_no_rawat}</small></td>
                        <td>${r.nama_pasien}</td>
                        <td class="text-info font-monospace acsn-cell">${r.acsn_baru}</td>
                        <td class="text-center">${badgeEnc}</td>
                        <td class="text-center">${badgeSR}</td>
                        
                        <td class="text-center orthanc-cell" id="orthanc-${r.acsn_baru}">
                            <span class="text-muted small"><i class="fa-regular fa-circle"></i></span>
                        </td>

                        <td class="text-center status-cell" id="status-${r.acsn_baru}"><span class="text-muted small"><i class="fa-regular fa-circle"></i></span></td>
                        <td class="text-end action-cell" id="action-${r.acsn_baru}">
                            <button class="btn btn-sm btn-outline-warning fw-bold" onclick="openCrudModal('${r.acsn_baru}')" title="Cek Detail & Hapus"><i class="fa-solid fa-magnifying-glass"></i> Cek</button>
                        </td>
                    </tr>`;
                });
                $('#crud-body').html(html);
                
                let limitInfo = limit > 10 ? ' (SHOW ALL)' : '';
                $('#crud-page-info').text(`Page ${res.pagination.current_page}/${res.pagination.total_pages} ${limitInfo}`);
                let pHtml = '';
                if(limit === 10) {
                    if(res.pagination.current_page > 1) pHtml += `<li class="page-item"><button class="page-link" onclick="loadAdminTable(${res.pagination.current_page-1})">&laquo;</button></li>`;
                    if(res.pagination.current_page < res.pagination.total_pages) pHtml += `<li class="page-item"><button class="page-link" onclick="loadAdminTable(${res.pagination.current_page+1})">&raquo;</button></li>`;
                }
                $('#crud-pagination').html(pHtml);
            }
        });
    }
    loadAdminTable(1);

    function scanCurrentPage() {
        let count = $('.acsn-cell').length;
        addToLiveLog(`Starting Batch Scan for ${count} items...`, 'info');
        $('.acsn-cell').each(function(i, obj) { 
            let acsn = $(this).text();
            setTimeout(() => { 
                checkOrthancStatus(acsn);
                checkRowStatus(acsn); 
            }, i * 250); 
        });
    }

    function checkOrthancStatus(acsn) {
        $(`#orthanc-${acsn}`).html('<span class="text-warning"><i class="fa-solid fa-spinner fa-spin"></i></span>');
        $.ajax({
            url: 'injector_engine.php?mode=check_orthanc', type: 'POST', data: { acsn: acsn }, dataType: 'json',
            success: function(res) {
                if(res.status === 'found') {
                    $(`#orthanc-${acsn}`).html('<span class="badge bg-primary">ADA</span>');
                } else {
                    $(`#orthanc-${acsn}`).html('<span class="badge bg-secondary text-dark">KOSONG</span>');
                }
            },
            error: function() { $(`#orthanc-${acsn}`).html('<span class="text-danger small">Err</span>'); }
        });
    }

    function checkRowStatus(acsn) {
        $(`#status-${acsn}`).html('<span class="text-warning"><i class="fa-solid fa-spinner fa-spin"></i></span>');
        $.ajax({
            url: 'injector_engine.php?mode=check_ss', type: 'POST', data: { acsn: acsn }, dataType: 'json',
            success: function(res) {
                if(res.status === 'found') {
                    $(`#status-${acsn}`).html('<span class="badge bg-success">✅ ADA</span>');
                    if($(`#action-${acsn} .btn-danger`).length === 0) $(`#action-${acsn}`).append(`<button class="btn btn-sm btn-danger ms-1" onclick="fastDelete('${acsn}')"><i class="fa-solid fa-trash"></i></button>`);
                    addToLiveLog(`SCAN [${acsn}]: FOUND`, 'success');
                } else {
                    $(`#status-${acsn}`).html('<span class="badge bg-secondary">❌ KOSONG</span>');
                    $(`#action-${acsn} .btn-danger`).remove();
                    addToLiveLog(`SCAN [${acsn}]: NOT FOUND`, 'error');
                }
            },
            error: function() { $(`#status-${acsn}`).html('<span class="text-danger small">Err</span>'); }
        });
    }

    function fastDelete(acsn) {
        Swal.fire({title: 'HAPUS?', text: `Hapus ${acsn}?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya'}).then((result) => {
            if (result.isConfirmed) {
                $(`#status-${acsn}`).html('<span class="text-danger"><i class="fa-solid fa-spinner fa-spin"></i></span>');
                $.ajax({ url: 'injector_engine.php?mode=delete_ss', type: 'POST', data: { acsn: acsn }, dataType: 'json',
                    success: function(res) {
                        if(res.status === 'success') {
                            $(`#status-${acsn}`).html('<span class="badge bg-secondary">🗑️ DELETED</span>');
                            $(`#action-${acsn} .btn-danger`).remove();
                            addToLiveLog(`DELETE [${acsn}]: SUCCESS`, 'success');
                        } else {
                            $(`#status-${acsn}`).html('<span class="badge bg-danger">GAGAL</span>');
                            Swal.fire('Gagal', res.msg, 'error');
                        }
                    }
                });
            }
        });
    }

    let currentAcsn = '';
    function openCrudModal(acsn) {
        currentAcsn = acsn;
        let modal = new bootstrap.Modal(document.getElementById('crudModal'));
        $('#modal-loader').removeClass('d-none'); $('#modal-content, #modal-actions, #m-found-area, #m-notfound-area').addClass('d-none'); $('#debug-text').text('Initializing communication...');
        modal.show();
        $.ajax({
            url: 'injector_engine.php?mode=check_ss', type: 'POST', data: { acsn: acsn }, dataType: 'json',
            success: function(res) {
                $('#modal-loader').addClass('d-none'); $('#modal-content').removeClass('d-none'); $('#m-acsn').text(acsn);
                $('#debug-text').text(res.debug_log ? res.debug_log.join('\n') : 'No logs available.');
                if(res.status === 'found') {
                    $('#m-found-area').removeClass('d-none'); $('#m-id').text(res.id); $('#m-status').text(res.ss_status); $('#m-series').text(res.series_count); $('#m-inst').text(res.instance_count); $('#modal-actions').removeClass('d-none'); 
                } else {
                    $('#m-notfound-area').removeClass('d-none'); $('#modal-actions').addClass('d-none');
                }
            },
            error: function(xhr) { $('#modal-loader').addClass('d-none'); $('#modal-content').removeClass('d-none'); $('#debug-text').text("FATAL AJAX ERROR:\n" + xhr.responseText); }
        });
    }

    function executeDeleteFromModal() {
        Swal.fire({title: 'HAPUS PERMANEN?', text: `Yakin hapus data ${currentAcsn}?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus!'}).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({title: 'Menghapus...', didOpen: () => Swal.showLoading()});
                $.ajax({ url: 'injector_engine.php?mode=delete_ss', type: 'POST', data: { acsn: currentAcsn }, dataType: 'json',
                    success: function(res) {
                        Swal.close();
                        if(res.status === 'success') {
                            bootstrap.Modal.getInstance(document.getElementById('crudModal')).hide();
                            Swal.fire('Sukses', 'Data terhapus.', 'success');
                            $(`#status-${currentAcsn}`).html('<span class="badge bg-secondary">🗑️ DELETED</span>');
                            $(`#action-${currentAcsn} .btn-danger`).remove();
                            addToLiveLog(`MODAL DELETE [${currentAcsn}]: SUCCESS`, 'success');
                        } else {
                            Swal.fire('Gagal', res.msg, 'error');
                        }
                    }
                });
            }
        });
    }
</script>
</body>
</html>