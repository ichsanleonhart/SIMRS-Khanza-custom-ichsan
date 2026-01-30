<?php
// FILE: index.php
// Pastikan kompatibel PHP 7.3+
include 'koneksi.php';

// 1. Ambil Data Instansi & Logo (Blob)
// Kita ambil limit 1 saja
$q_instansi = $mysqli->query("SELECT nama_instansi, logo FROM setting LIMIT 1");
$instansi   = $q_instansi->fetch_assoc();
$nama_rs    = $instansi['nama_instansi'];

// Konversi BLOB ke Base64 untuk ditampilkan
// Cek jika logo ada isinya
if ($instansi['logo']) {
    $logo_b64 = base64_encode($instansi['logo']);
    $logo_src = 'data:image/jpeg;base64,' . $logo_b64;
} else {
    $logo_src = 'https://via.placeholder.com/50'; // Placeholder jika kosong
}

// 2. Ambil Data Bangsal untuk Dropdown
$sql_depo = "SELECT kd_bangsal, nm_bangsal FROM bangsal WHERE status='1' ORDER BY nm_bangsal ASC";
$res_depo = $mysqli->query($sql_depo);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Command Center - <?= $nama_rs ?></title>
    
    <link rel="icon" type="image/png" href="<?= $logo_src ?>">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <style>
        :root {
            --primary-bg: #f3f4f6;
            --card-bg: #ffffff;
            --text-main: #1f2937;
            --danger-color: #dc2626;
            --success-color: #059669;
            --accent-color: #2563eb;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--primary-bg);
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar-custom {
            background: var(--card-bg);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            padding: 0.8rem 2rem;
            border-bottom: 3px solid var(--accent-color);
        }

        .brand-logo {
            height: 45px;
            width: 45px;
            object-fit: cover;
            margin-right: 15px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }

        /* Dashboard Cards */
        .dashboard-card {
            background: var(--card-bg);
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        /* Status Indicator Large */
        .status-indicator {
            padding: 2.5rem;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 1.5rem;
            transition: all 0.5s ease;
            position: relative;
            overflow: hidden;
        }
        
        .status-safe {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .status-danger {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #991b1b;
            border: 1px solid #fecaca;
            animation: pulse-red 2s infinite;
        }

        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        /* Table Styling */
        .table-custom thead th {
            background-color: #f9fafb;
            color: #6b7280;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            font-weight: 700;
            border-bottom: 2px solid #e5e7eb;
        }
        .table-custom td {
            vertical-align: middle;
            font-size: 0.9rem;
            border-bottom: 1px solid #f3f4f6;
        }
        .stok-badge {
            font-size: 1rem;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 6px;
            min-width: 50px;
            display: inline-block;
        }

        /* Overlay Welcome Screen */
        #interaction-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.98);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        
        .btn-start {
            padding: 12px 35px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            transition: transform 0.2s;
        }
        .btn-start:hover { transform: scale(1.05); box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4); }

        /* Blink Animation */
        .blink-text { animation: blinker-text 1s linear infinite; }
        @keyframes blinker-text { 50% { opacity: 0; } }

        /* Countdown Badge */
        .countdown-badge {
            font-family: 'Courier New', monospace;
            font-weight: 800;
            letter-spacing: 1px;
        }

    </style>
</head>
<body>

<div id="interaction-overlay">
    <img src="<?= $logo_src ?>" width="90" height="90" class="mb-4 rounded-circle shadow-sm" style="object-fit:cover;">
    <h3 class="mb-2 fw-bold text-dark">Sistem Monitoring Stok</h3>
    <p class="text-secondary mb-4">Klik tombol di bawah untuk mengaktifkan Dashboard & Alarm</p>
    <button class="btn btn-primary btn-start" onclick="startSystem()">
        <i class="bi bi-power me-2"></i> AKTIFKAN MONITORING
    </button>
</div>

<nav class="navbar navbar-custom d-flex justify-content-between align-items-center sticky-top">
    <div class="d-flex align-items-center">
        <img src="<?= $logo_src ?>" alt="Logo" class="brand-logo rounded-circle">
        <div>
            <h6 class="mb-0 fw-bold text-dark"><?= $nama_rs ?></h6>
            <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Alarm Monitoring Stok Menipis</span>
        </div>
    </div>
    <div class="text-end d-none d-md-block">
        <div id="jam-digital" class="fw-bold fs-5 font-monospace text-dark">00:00:00</div>
        <div id="tanggal-digital" class="small text-muted" style="font-size: 0.75rem;">...</div>
    </div>
</nav>

<div class="container py-4">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="dashboard-card p-4 h-100">
                <h6 class="text-uppercase text-secondary fw-bold mb-4" style="font-size: 0.8rem; letter-spacing: 1px;">
                    <i class="bi bi-sliders me-2"></i>Konfigurasi Depo
                </h6>
                
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted">Lokasi Pantau</label>
                    <select id="pilihDepo" class="form-select">
                        <?php while($row = $res_depo->fetch_assoc()): ?>
                            <option value="<?= $row['kd_bangsal'] ?>" <?= $row['kd_bangsal'] == 'AP' ? 'selected' : '' ?>>
                                <?= $row['nm_bangsal'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded mb-4 border">
                    <div>
                        <span class="fw-bold d-block text-dark" style="font-size: 0.9rem;">
                            <i class="bi bi-volume-up-fill me-1 text-primary"></i> Alarm Suara
                        </span>
                        <small class="text-muted" style="font-size: 0.75rem;">Bunyi otomatis saat kritis</small>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="toggleSuara" style="transform: scale(1.3);" checked>
                    </div>
                </div>

                <div class="mt-auto pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted fw-bold">Status Sistem</small>
                        <span id="countdown-wrapper" class="badge bg-light text-dark border countdown-badge">
                            <i class="bi bi-arrow-repeat me-1"></i> <span id="countdown-timer">--</span>s
                        </span>
                    </div>
                    <div id="status-badge" class="alert alert-secondary py-2 px-3 mb-0 small text-center fw-bold">
                        <i class="bi bi-hourglass-split me-1"></i> Standby...
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8 mb-4">
            <div class="dashboard-card p-4 h-100">
                <div id="status-panel" class="status-indicator status-safe">
                    <div class="d-flex justify-content-center align-items-center mb-2">
                        <i class="bi bi-shield-check display-3 me-3"></i>
                        <div class="text-start">
                            <h2 class="fw-bold mb-0">STOK AMAN</h2>
                            <small class="opacity-75">Tidak ada obat di bawah stok minimal</small>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-uppercase text-secondary fw-bold mb-0" style="font-size: 0.8rem; letter-spacing: 1px;">
                        Live Data Feed
                    </h6>
                    <span class="badge bg-primary rounded-pill" id="total-items">0 Item</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover" id="tabel-stok">
                        <thead>
                            <tr>
                                <th width="15%">Kode</th>
                                <th width="40%">Nama Obat</th>
                                <th width="15%">Satuan</th>
                                <th width="15%" class="text-center">Min</th>
                                <th width="15%" class="text-center">Sisa</th>
                            </tr>
                        </thead>
                        <tbody>
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="text-center text-muted small py-3 opacity-50">
    SIMRS Inventory Monitor System &copy; <?= date('Y') ?> <?= $nama_rs ?>
</footer>

<audio id="audioAlarm" loop preload="auto">
    <source src="audio/alarm.mp3" type="audio/mpeg">
</audio>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    // --- KONFIGURASI ---
    const REFRESH_INTERVAL = 10; // Detik (Waktu hitung mundur)

    // --- GLOBAL VARIABLES ---
    const alarm = document.getElementById('audioAlarm');
    let isSoundOn = true;
    let isInitialized = false;
    let timerValue = REFRESH_INTERVAL;
    let timerInterval = null;

    // 1. Inisialisasi Select2
    $('#pilihDepo').select2({
        theme: "bootstrap-5",
        width: '100%'
    });

    // 2. JAM DIGITAL
    setInterval(() => {
        const now = new Date();
        document.getElementById('jam-digital').innerText = now.toLocaleTimeString('id-ID');
        document.getElementById('tanggal-digital').innerText = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }, 1000);

    // 3. START SYSTEM (Unlock Audio & Start Timer)
    function startSystem() {
        // Unlock Audio Context
        alarm.play().then(() => {
            alarm.pause();
            alarm.currentTime = 0;
        }).catch(e => console.log("Audio unlock status:", e));

        $('#interaction-overlay').fadeOut(400);
        isInitialized = true;
        
        // Cek data pertama kali
        cekStok();
        // Mulai hitung mundur
        startCountdown();
    }

    // 4. COUNTDOWN TIMER LOGIC
    function startCountdown() {
        if(timerInterval) clearInterval(timerInterval); // Reset jika ada duplikat
        
        timerValue = REFRESH_INTERVAL;
        $('#countdown-timer').text(timerValue);

        timerInterval = setInterval(() => {
            if(!isInitialized) return;

            timerValue--;
            $('#countdown-timer').text(timerValue);

            if (timerValue <= 0) {
                cekStok();           // Trigger cek stok
                timerValue = REFRESH_INTERVAL; // Reset nilai visual segera
            }
        }, 1000);
    }

    // 5. CEK STOK (AJAX)
    function cekStok() {
        let depo = $('#pilihDepo').val();
        
        // Update UI status sedang loading (opsional, biar user tau sedang fetch)
        $('#status-badge').html('<i class="bi bi-cloud-arrow-down-fill animate-bounce"></i> Updating...').removeClass('alert-danger alert-success').addClass('alert-primary');

        $.ajax({
            url: 'api_cek_stok.php',
            type: 'GET',
            data: { depo: depo },
            dataType: 'json',
            success: function(response) {
                // Update Badge Status Koneksi
                let waktu = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit', second:'2-digit'});
                $('#status-badge').html('<i class="bi bi-wifi"></i> Terhubung • ' + waktu)
                                  .removeClass('alert-primary alert-danger').addClass('alert-success');

                $('#total-items').text(response.jumlah_warning + ' Item Warning');

                let tbody = '';

                if (response.jumlah_warning > 0) {
                    // --- KONDISI KRITIS ---
                    $('#status-panel').removeClass('status-safe').addClass('status-danger');
                    $('#status-panel').html(`
                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <i class="bi bi-exclamation-triangle-fill display-3 me-3 blink-text"></i>
                            <div class="text-start">
                                <h2 class="fw-bold mb-0 text-danger">PERHATIAN!</h2>
                                <p class="mb-0 fw-bold">${response.jumlah_warning} Obat Menipis</p>
                            </div>
                        </div>
                    `);

                    // Render Tabel
                    $.each(response.data, function(i, item) {
                        tbody += `<tr>
                            <td class="font-monospace text-muted small">${item.kode_brng}</td>
                            <td class="fw-bold text-dark">${item.nama_brng}</td>
                            <td class="text-muted small">${item.satuan}</td>
                            <td class="text-center fw-bold text-secondary">${item.stokminimal}</td>
                            <td class="text-center">
                                <span class="stok-badge bg-danger text-white shadow-sm blink-text">${item.stok}</span>
                            </td>
                        </tr>`;
                    });

                    // Bunyikan Alarm (Hanya jika belum bunyi & fitur on)
                    if (isSoundOn) {
                        if (alarm.paused) {
                            alarm.play().catch(e => console.error("Autoplay blocked:", e));
                        }
                    }

                } else {
                    // --- KONDISI AMAN ---
                    $('#status-panel').removeClass('status-danger').addClass('status-safe');
                    $('#status-panel').html(`
                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <i class="bi bi-shield-check display-3 me-3"></i>
                            <div class="text-start">
                                <h2 class="fw-bold mb-0">STOK AMAN</h2>
                                <small class="opacity-75">Monitoring aktif. Stok terkendali.</small>
                            </div>
                        </div>
                    `);

                    tbody = '<tr><td colspan="5" class="text-center py-5 text-muted fst-italic bg-light">Tidak ada item yang perlu perhatian saat ini.</td></tr>';

                    // Matikan Alarm
                    alarm.pause();
                    alarm.currentTime = 0;
                }

                $('#tabel-stok tbody').html(tbody);
            },
            error: function() {
                $('#status-badge').html('<i class="bi bi-wifi-off"></i> Koneksi Terputus!')
                                  .removeClass('alert-primary alert-success').addClass('alert-danger');
            }
        });
    }

    // 6. EVENT LISTENER
    
    // Ganti Depo -> Reset Timer & Cek Langsung
    $('#pilihDepo').on('change', function() {
        cekStok();
        timerValue = REFRESH_INTERVAL; // Reset timer count agar tidak double jump
        $('#countdown-timer').text(timerValue);
    });

    // Toggle Suara
    $('#toggleSuara').change(function() {
        isSoundOn = $(this).is(':checked');
        if(!isSoundOn) {
            alarm.pause();
            alarm.currentTime = 0;
        } else {
            // Cek jika sedang bahaya, nyalakan lagi
            if($('#status-panel').hasClass('status-danger')){
                alarm.play().catch(e => console.log(e));
            }
        }
    });

</script>
</body>
</html>