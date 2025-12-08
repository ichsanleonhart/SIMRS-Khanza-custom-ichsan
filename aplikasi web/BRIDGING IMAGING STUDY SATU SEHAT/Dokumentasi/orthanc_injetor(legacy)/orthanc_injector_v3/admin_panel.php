<?php
// admin_panel.php
// VERSI STABIL: HARD DELETE ONLY
// Soft Delete dihapus karena tidak didukung oleh API SatuSehat.

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
        .debug-box { background-color: #000; color: #00ff00; font-family: 'Consolas', monospace; font-size: 0.8rem; border: 1px solid #333; }
        .debug-header { cursor: pointer; user-select: none; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark border-bottom border-secondary py-3">
    <div class="container">
        <span class="navbar-brand fw-bold text-info"><i class="fa-solid fa-user-secret"></i> SUPER ADMIN PANEL</span>
        <div>
            <span class="text-muted me-3">User: <b class="text-white"><?= $current_user ?></b></span>
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

    <div class="card border-danger h-100 mb-5">
        <div class="card-header bg-dark border-danger text-danger fw-bold d-flex justify-content-between align-items-center">
            <span><i class="fa-solid fa-trash-can"></i> SatuSehat Data Manager</span>
            <button class="btn btn-sm btn-outline-secondary" onclick="loadAdminTable(1)"><i class="fa-solid fa-rotate"></i> Refresh</button>
        </div>
        <div class="card-body bg-secondary bg-opacity-10 py-2 border-bottom border-secondary">
            <div class="row g-2">
                <div class="col-md-4"><input type="text" id="crud_search" class="form-control form-control-sm" placeholder="Cari ACSN / Pasien..."></div>
                <div class="col-md-3"><input type="date" id="crud_start" class="form-control form-control-sm"></div>
                <div class="col-md-3"><input type="date" id="crud_end" class="form-control form-control-sm"></div>
                <div class="col-md-2"><button class="btn btn-danger btn-sm w-100" onclick="loadAdminTable(1)">Filter</button></div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Waktu Inject</th><th>No RM</th><th>Pasien</th><th>ACSN (SatuSehat Key)</th><th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="crud-body"><tr><td colspan="5" class="text-center py-5">Memuat Data...</td></tr></tbody>
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
</div>

<div class="modal fade" id="crudModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg"> 
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-warning"><i class="fa-solid fa-magnifying-glass"></i> Audit SatuSehat</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modal-loader" class="text-center my-4">
                    <div class="spinner-border text-info" role="status"></div>
                    <p class="mt-2">Connecting to Ministry of Health...</p>
                </div>
                <div id="modal-content" class="d-none">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                         <span class="text-muted">Target ACSN:</span>
                         <span class="h5 text-info font-monospace m-0" id="m-acsn"></span>
                    </div>
                    
                    <div id="m-found-area" class="alert alert-dark border-secondary p-3 mb-3 d-none">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-circle-check text-success fa-lg me-2"></i>
                            <span class="fw-bold text-success">DATA DITEMUKAN</span>
                        </div>
                        <div class="bg-black bg-opacity-50 p-2 rounded mb-2">
                            <small class="text-secondary d-block">ID:</small>
                            <span class="text-white font-monospace text-break user-select-all" id="m-id"></span>
                        </div>
                        <div class="row g-2">
                            <div class="col-4"><div class="p-1 border border-secondary rounded text-center"><small>Status</small><br><b class="text-warning" id="m-status"></b></div></div>
                            <div class="col-4"><div class="p-1 border border-secondary rounded text-center"><small>Series</small><br><b class="text-white" id="m-series"></b></div></div>
                            <div class="col-4"><div class="p-1 border border-secondary rounded text-center"><small>Instances</small><br><b class="text-white" id="m-inst"></b></div></div>
                        </div>
                    </div>

                    <div id="m-notfound-area" class="alert alert-warning text-center mb-3 d-none">
                        <i class="fa-solid fa-triangle-exclamation fa-2x mb-2"></i><br><strong>Data 404 Not Found</strong>
                    </div>

                    <div class="card debug-box">
                        <div class="card-header debug-header py-1 px-2" onclick="$('#debug-console').slideToggle()">
                            <small><i class="fa-solid fa-terminal"></i> TECHNICIAN LOG (DEBUG)</small>
                        </div>
                        <div id="debug-console" class="card-body p-2" style="max-height: 200px; overflow-y: auto;">
                            <pre class="m-0 text-success" style="white-space: pre-wrap;" id="debug-text"></pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-none justify-content-between" id="modal-actions">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <div>
                    <button class="btn btn-danger fw-bold" onclick="executeDelete()"><i class="fa-solid fa-radiation"></i> HAPUS PERMANEN</button>
                </div>
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

    function runManualInject() {
        let ds = $('#inj_start').val(), de = $('#inj_end').val();
        Swal.fire({title: 'Jalankan?', text: `Inject ${ds} s/d ${de}?`, icon: 'question', showCancelButton: true, confirmButtonText: 'Ya'}).then((res) => {
            if(res.isConfirmed) {
                Swal.fire({title: 'Memproses...', didOpen: () => Swal.showLoading()});
                $.ajax({ url: 'injector_engine.php', data: { start_date: ds, end_date: de }, dataType: 'json',
                    success: function(r) { Swal.fire('Selesai', `Proses: ${r.processed}`, 'success'); loadAdminTable(1); }
                });
            }
        });
    }

    function loadAdminTable(page) {
        $.ajax({
            url: 'injector_engine.php',
            data: { mode: 'view_log', page: page, search: $('#crud_search').val(), date_start: $('#crud_start').val(), date_end: $('#crud_end').val() },
            dataType: 'json',
            success: function(res) {
                let html = '';
                if(res.data.length === 0) html = '<tr><td colspan="5" class="text-center py-4 text-muted">Data tidak ditemukan.</td></tr>';
                else $.each(res.data, function(i, r) {
                    html += `<tr><td><small>${r.waktu_suntik}</small></td><td>${r.no_rm}</td><td>${r.nama_pasien}</td>
                    <td class="text-info font-monospace">${r.acsn_baru}</td><td class="text-end">
                    <button class="btn btn-sm btn-outline-warning fw-bold" onclick="openCrudModal('${r.acsn_baru}')">
                    <i class="fa-solid fa-magnifying-glass"></i> Cek</button></td></tr>`;
                });
                $('#crud-body').html(html);
                $('#crud-page-info').text(`Page ${res.pagination.current_page}/${res.pagination.total_pages}`);
                let pHtml = '';
                if(res.pagination.current_page > 1) pHtml += `<li class="page-item"><button class="page-link" onclick="loadAdminTable(${res.pagination.current_page-1})">&laquo;</button></li>`;
                if(res.pagination.current_page < res.pagination.total_pages) pHtml += `<li class="page-item"><button class="page-link" onclick="loadAdminTable(${res.pagination.current_page+1})">&raquo;</button></li>`;
                $('#crud-pagination').html(pHtml);
            }
        });
    }
    loadAdminTable(1);

    let currentAcsn = '';

    function openCrudModal(acsn) {
        currentAcsn = acsn;
        let modal = new bootstrap.Modal(document.getElementById('crudModal'));
        $('#modal-loader').removeClass('d-none');
        $('#modal-content, #modal-actions, #m-found-area, #m-notfound-area').addClass('d-none');
        $('#debug-text').text('Connecting...');
        modal.show();

        $.ajax({
            url: 'injector_engine.php?mode=check_ss', type: 'POST', data: { acsn: acsn }, dataType: 'json',
            success: function(res) {
                $('#modal-loader').addClass('d-none');
                $('#modal-content').removeClass('d-none');
                $('#m-acsn').text(acsn);
                if(res.debug_log) $('#debug-text').text(res.debug_log.join('\n'));

                if(res.status === 'found') {
                    $('#m-found-area, #modal-actions').removeClass('d-none');
                    $('#m-id').text(res.id);
                    $('#m-status').text(res.ss_status);
                    $('#m-series').text(res.series_count);
                    $('#m-inst').text(res.instance_count);
                } else {
                    $('#m-notfound-area').removeClass('d-none');
                    $('#debug-console').show();
                }
            },
            error: function(xhr) {
                $('#modal-loader').addClass('d-none');
                $('#debug-text').text("FATAL JS ERROR:\n" + xhr.responseText);
                $('#debug-console').show();
            }
        });
    }

    function executeDelete() {
        Swal.fire({title: 'HAPUS PERMANEN?', text: 'Data akan hilang!', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Hapus'}).then((r) => {
            if(r.isConfirmed) {
                Swal.fire({title: 'Memproses...', didOpen: () => Swal.showLoading()});
                $.ajax({ url: 'injector_engine.php?mode=delete_ss', type: 'POST', data: { acsn: currentAcsn }, dataType: 'json',
                    success: function(res) {
                        Swal.close();
                        updateLog(res.debug_log, "HARD DELETE");
                        if(res.status === 'success') { 
                            bootstrap.Modal.getInstance(document.getElementById('crudModal')).hide();
                            Swal.fire('Sukses', 'Data terhapus.', 'success'); 
                            loadAdminTable(1);
                        } else Swal.fire('Gagal', res.msg, 'error');
                    },
                    error: function(xhr) { Swal.close(); updateLog([xhr.responseText], "FATAL ERROR"); }
                });
            }
        });
    }

    function updateLog(logs, title) {
        if(logs) {
            let old = $('#debug-text').text();
            $('#debug-text').text(old + `\n\n--- ${title} ---\n` + logs.join('\n'));
            $('#debug-console').show();
        }
    }
</script>
</body>
</html>