<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orthanc ACSN Injector</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background-color: #1a1d20; color: #e0e0e0; font-family: 'Segoe UI', monospace; }
        .card { background-color: #2c3034; border: 1px solid #444; }
        .table { color: #ccc; }
        .table thead { background-color: #000; }
        .badge-pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    </style>
</head>
<body class="p-4">

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2 class="text-info fw-bold">💉 Orthanc ACSN Injector</h2>
            <p class="text-muted mb-0">Auto-Reconciliation System for FCR Prima</p>
        </div>
        <div class="col-md-4 text-end">
            <div id="status-badge" class="badge bg-success badge-pulse px-3 py-2">ENGINE RUNNING</div>
            <div class="mt-2 small text-secondary" id="last-check">Last check: -</div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title text-warning">Auto-Injection Active</h5>
                <small>Sistem akan memeriksa Orthanc & Khanza setiap 15 detik.</small>
            </div>
            <button id="btn-inject" class="btn btn-outline-info btn-lg" onclick="runInjector()">
                ⚡ Eksekusi Manual
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-dark text-white border-bottom border-secondary">
            Log Aktivitas (10 Terakhir)
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-dark table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>No RM</th>
                            <th>Nama Pasien</th>
                            <th>ACSN Lama (Kosong)</th>
                            <th>ACSN Baru (Disuntik)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="log-body">
                        <tr><td colspan="6" class="text-center py-3">Menunggu data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function updateTime() {
        const now = new Date();
        $('#last-check').text("Last check: " + now.toLocaleTimeString());
    }

    function runInjector() {
        $('#btn-inject').prop('disabled', true).text('Sedang Memproses...');
        $('#status-badge').removeClass('bg-success bg-danger').addClass('bg-warning').text('PROCESSING');

        $.ajax({
            url: 'injector_engine.php',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                $('#btn-inject').prop('disabled', false).html('⚡ Eksekusi Manual');
                
                if(res.status === 'success') {
                    $('#status-badge').removeClass('bg-warning bg-danger').addClass('bg-success').text('ENGINE RUNNING');
                    renderTable(res.data_table);
                    updateTime();
                    if(res.processed > 0) {
                        // Efek kedip jika ada yang berhasil
                        $('body').css('background-color', '#1f2e1f').delay(200).queue(function(next){
                            $(this).css('background-color', '#1a1d20'); 
                            next();
                        });
                    }
                } else {
                    $('#status-badge').removeClass('bg-success bg-warning').addClass('bg-danger').text('ERROR');
                    alert('Error: ' + res.msg);
                }
            },
            error: function(xhr, status, error) {
                $('#btn-inject').prop('disabled', false).text('⚡ Eksekusi Manual');
                $('#status-badge').removeClass('bg-success bg-warning').addClass('bg-danger').text('CONNECTION LOST');
                console.error(xhr.responseText);
            }
        });
    }

    function renderTable(data) {
        let html = '';
        if(data.length === 0) {
            html = '<tr><td colspan="6" class="text-center text-muted py-3">Belum ada data yang disuntik hari ini.</td></tr>';
        } else {
            $.each(data, function(i, row) {
                html += `<tr>
                    <td>${row.waktu_suntik}</td>
                    <td class="fw-bold text-info">${row.no_rm}</td>
                    <td>${row.nama_pasien}</td>
                    <td class="text-danger fst-italic">${row.acsn_lama ? row.acsn_lama : '(KOSONG)'}</td>
                    <td class="text-success fw-bold">✅ ${row.acsn_baru}</td>
                    <td><span class="badge bg-primary">${row.status}</span></td>
                </tr>`;
            });
        }
        $('#log-body').html(html);
    }

    // Load pertama kali
    runInjector();

    // Auto refresh setiap 15 detik
    setInterval(runInjector, 15000);

</script>
</body>
</html>