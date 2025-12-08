	<?php
// rescue_ui.php
// UI untuk Rescue Sender: Memungkinkan kirim ulang data dalam rentang tanggal tertentu

require_once 'auth_check.php'; 
require_once 'config.php';
$current_user = $_SESSION['user_admin'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rescue Mission - Orthanc Injector</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #0f0f0f; color: #e0e0e0; font-family: 'Segoe UI', monospace; }
        .card { background-color: #1a1a1a; border: 1px solid #333; }
        .form-control { background-color: #262626; border: 1px solid #444; color: #fff; }
        .form-control:focus { background-color: #262626; color: #fff; border-color: #ffc107; }
        .btn-warning-glow { box-shadow: 0 0 15px rgba(255, 193, 7, 0.3); }
        .console-box { 
            background-color: #000; 
            border: 1px solid #333; 
            border-left: 3px solid #ffc107;
            color: #28a745; 
            font-family: 'Consolas', monospace; 
            height: 400px; 
            overflow-y: scroll; 
            padding: 15px; 
            font-size: 0.85rem; 
        }
        .log-time { color: #6c757d; margin-right: 10px; }
        .log-error { color: #dc3545; }
        .log-warn { color: #ffc107; }
    </style>
</head>
<body class="p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-warning fw-bold"><i class="fa-solid fa-truck-medical"></i> RESCUE MISSION</h2>
            <small class="text-muted">Kirim Ulang Data yang Macet (Gatekeeper Status: READY)</small>
        </div>
        <div>
            <a href="admin_panel.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>
        </div>
    </div>

    <div class="card mb-4 border-warning">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="small text-muted mb-1">Mulai Tanggal</label>
                    <input type="date" id="r_start" class="form-control form-control-lg">
                </div>
                <div class="col-md-4">
                    <label class="small text-muted mb-1">Sampai Tanggal</label>
                    <input type="date" id="r_end" class="form-control form-control-lg">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-warning btn-lg w-100 fw-bold btn-warning-glow" onclick="startRescue()">
                        <i class="fa-solid fa-paper-plane"></i> SCAN & RESCUE
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-dark border-secondary d-flex justify-content-between">
            <span class="font-monospace text-muted"><i class="fa-solid fa-terminal"></i> MISSION LOGS</span>
            <span id="status-indicator" class="badge bg-secondary">IDLE</span>
        </div>
        <div class="card-body p-0">
            <div id="mission-console" class="console-box">
                <div class="text-muted">> Waiting for command... Select date range and click Scan & Rescue.</div>
            </div>
        </div>
    </div>
</div>

<script>
    // Init Date: Hari Ini
    document.getElementById('r_start').valueAsDate = new Date();
    document.getElementById('r_end').valueAsDate = new Date();

    function log(msg, type='info') {
        let colorClass = '';
        if(type === 'error') colorClass = 'log-error';
        if(type === 'warn') colorClass = 'log-warn';
        
        let time = new Date().toLocaleTimeString();
        let html = `<div class="${colorClass}"><span class="log-time">[${time}]</span> ${msg}</div>`;
        
        let consoleBox = $('#mission-console');
        consoleBox.append(html);
        consoleBox.scrollTop(consoleBox[0].scrollHeight);
    }

    function startRescue() {
        let ds = $('#r_start').val();
        let de = $('#r_end').val();

        if(!ds || !de) { Swal.fire('Error', 'Pilih tanggal dulu!', 'error'); return; }

        Swal.fire({
            title: 'Mulai Misi Penyelamatan?',
            text: `Sistem akan memindai Orthanc dari ${ds} s/d ${de} dan mengirim paksa data yang sudah memiliki ID ServiceRequest valid.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Ya, Laksanakan!',
            background: '#1a1a1a', color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#mission-console').html('<div class="text-info">> Initializing Mission...</div>');
                $('#status-indicator').removeClass('bg-secondary bg-success').addClass('bg-warning').text('RUNNING');
                
                $.ajax({
                    url: 'rescue_sender.php',
                    data: { start_date: ds, end_date: de },
                    dataType: 'json',
                    success: function(res) {
                        if(res.status === 'finished') {
                            $('#status-indicator').removeClass('bg-warning').addClass('bg-success').text('COMPLETED');
                            
                            // Tampilkan Log
                            if(res.logs && res.logs.length > 0) {
                                res.logs.forEach(l => {
                                    let type = 'info';
                                    if(l.includes('FAIL') || l.includes('Error')) type = 'error';
                                    else if(l.includes('SKIP')) type = 'warn';
                                    log(l, type);
                                });
                            } else {
                                log("Tidak ada data yang diproses.", 'warn');
                            }

                            log(`------------------------------------------------`, 'info');
                            log(`MISSION REPORT: Total Pushed: ${res.pushed_total}`, 'success');
                            Swal.fire({
                                title: 'Misi Selesai', 
                                text: `Berhasil mengirim ${res.pushed_total} data ke Router.`, 
                                icon: 'success',
                                background: '#1a1a1a', color: '#fff'
                            });
                        } else {
                            log(`ERROR: ${res.msg}`, 'error');
                            $('#status-indicator').removeClass('bg-warning').addClass('bg-danger').text('ERROR');
                        }
                    },
                    error: function(xhr) {
                        log(`FATAL ERROR: Koneksi Terputus / Timeout`, 'error');
                        log(xhr.responseText, 'error');
                        $('#status-indicator').removeClass('bg-warning').addClass('bg-danger').text('FATAL ERROR');
                    }
                });
            }
        });
    }
</script>
</body>
</html>