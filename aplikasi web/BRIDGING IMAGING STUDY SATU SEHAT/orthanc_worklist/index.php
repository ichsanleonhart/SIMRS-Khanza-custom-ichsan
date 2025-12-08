<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Worklist Factory & Sweeper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: none; }
        .badge-pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    </style>
</head>
<body class="p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-primary fw-bold"><i class="fa-solid fa-network-wired"></i> Worklist & Bridge Monitor</h2>
            <small class="text-muted">
                Agen A (Generator): <span class="badge bg-success badge-pulse">ON</span> | 
                Agen C (Sweeper): <span class="badge bg-info badge-pulse">ON</span>
            </small>
        </div>
        <div class="text-end">
            <h1 class="fw-bold m-0" id="clock">00:00:00</h1>
            <button class="btn btn-dark btn-sm mt-2" onclick="runProcess()">Force Sync All</button>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <span class="fw-bold py-1">Traffic Hari Ini</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Waktu Order</th>
                            <th>No Order (ACSN)</th>
                            <th>Pasien</th>
                            <th class="text-center">Worklist File</th>
                            <th class="text-center">Sent to SatuSehat</th>
                        </tr>
                    </thead>
                    <tbody id="data-body">
                        <tr><td colspan="5" class="text-center py-5">Loading System...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-2 text-end text-muted small" id="last-update">-</div>
</div>

<script>
    setInterval(() => { document.getElementById('clock').innerText = new Date().toLocaleTimeString(); }, 1000);

    function runProcess() {
        // 1. Jalankan Generator (Agen A)
        $.ajax({ url: 'worklist_generator.php', dataType: 'json', success: function() { console.log("Gen OK"); } });
        
        // 2. Jalankan Sweeper (Agen C)
        $.ajax({ url: 'sweeper_engine.php', dataType: 'json', success: function(res) { 
            if(res.swept > 0) console.log("Sweeper Sent: " + res.swept); 
        }});

        // 3. Refresh Tabel (ambil data dari worklist_generator karena dia punya query select)
        setTimeout(loadTable, 1000);
    }

    function loadTable() {
        $.ajax({
            url: 'worklist_generator.php', // File ini juga mereturn data tabel
            dataType: 'json',
            success: function(res) {
                let html = '';
                if(!res.data || res.data.length === 0) {
                    html = '<tr><td colspan="5" class="text-center py-4 text-muted">Belum ada order hari ini.</td></tr>';
                } else {
                    $.each(res.data, function(i, r) {
                        // Status Worklist
                        let wlBadge = (r.status_wl == '1') 
                            ? '<span class="badge bg-success"><i class="fa-solid fa-file-code"></i> READY</span>' 
                            : '<span class="badge bg-secondary">WAIT</span>';
                        
                        // Status Sent SS
                        let ssBadge = (r.status_sent_ss == '1') 
                            ? `<span class="badge bg-primary"><i class="fa-solid fa-paper-plane"></i> SENT<br><small>${r.waktu_sent_ss.split(' ')[1]}</small></span>` 
                            : '<span class="badge bg-warning text-dark"><i class="fa-solid fa-clock"></i> QUEUE</span>';

                        html += `<tr>
                            <td>${r.tgl_request}</td>
                            <td class="fw-bold text-primary font-monospace">${r.noorder}</td>
                            <td>${r.nomor_rm} - ${r.nama_pasien}</td>
                            <td class="text-center">${wlBadge}</td>
                            <td class="text-center">${ssBadge}</td>
                        </tr>`;
                    });
                }
                $('#data-body').html(html);
                $('#last-update').text("Last Update: " + new Date().toLocaleTimeString());
            }
        });
    }

    // Loop Otomatis 10 Detik
    setInterval(runProcess, 10000);
    runProcess(); // Start Awal

</script>
</body>
</html>