<?php
include 'config.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'igd';

$menus = [
    'igd' => ['icon' => 'fa-ambulance', 'label' => 'IGD', 'color' => 'danger'],
    'ralan' => ['icon' => 'fa-user-md', 'label' => 'Poliklinik', 'color' => 'info'],
    'ranap' => ['icon' => 'fa-procedures', 'label' => 'Rawat Inap', 'color' => 'success'],
    'operasi' => ['icon' => 'fa-syringe', 'label' => 'Kamar Operasi', 'color' => 'warning'],
    'bpjs' => ['icon' => 'fa-file-invoice-dollar', 'label' => 'Casemix BPJS', 'color' => 'primary'],
    'audit_full' => ['icon' => 'fa-chart-bar', 'label' => 'Laporan Audit', 'color' => 'secondary'],
];

$current_menu = $menus[$page];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor ERM - <?php echo $current_menu['label']; ?></title>
    <link rel="icon" type="image/png" href="<?php echo $logo_src; ?>">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
            --transition-speed: 0.3s;
            --sidebar-bg: #343a40;
            --primary-color: #0d6efd;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: .875rem;
            overflow-x: hidden;
            background-color: #f4f6f9;            
        }

        /* --- SIDEBAR (FIXED LEFT) --- */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1000;
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            color: #fff;
            transition: transform var(--transition-speed) ease-in-out;
            overflow-y: auto;
        }
        
        .sidebar-heading {
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            background: rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            height: var(--header-height);
        }
        .sidebar-heading img { height: 35px; }

        .list-group-item {
            background-color: transparent;
            color: #cfd8dc;
            border: none;
            padding: 12px 20px;
        }
        .list-group-item:hover { background-color: #495057; color: #fff; }
        .list-group-item.active { background-color: #007bff; color: #fff; border-left: 4px solid #fff; }

        /* --- MAIN CONTENT (DYNAMIC MARGIN) --- */
        main {
            display: block; 
            width: auto; 
            margin-left: var(--sidebar-width); 
            min-height: 100vh;
            transition: margin-left var(--transition-speed) ease-in-out;
        }

        /* --- HEADER BAR (INSIDE MAIN) --- */
        .header-info {
            background: #fff;
            padding: 0 20px;
            height: var(--header-height);
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            margin-bottom: 20px;
        }

        /* --- LOGIKA TOGGLE DESKTOP --- */
        /* Saat kelas .sidebar-closed ada di body, sidebar geser kiri, main margin 0 */
        body.sidebar-closed .sidebar {
            transform: translateX(-100%);
        }
        body.sidebar-closed main {
            margin-left: 0;
        }

        /* --- LOGIKA TOGGLE MOBILE --- */
        @media (max-width: 767.98px) {
            /* Default Mobile: Sidebar sembunyi, Main full */
            .sidebar { transform: translateX(-100%); }
            main { margin-left: 0; }

            /* Saat Mobile Open: Sidebar muncul */
            body.sidebar-open .sidebar { transform: translateX(0); box-shadow: 0 0 15px rgba(0,0,0,0.2); }
            
            /* Overlay Mobile */
            .sidebar-overlay {
                display: none;
                position: fixed; inset: 0;
                background: rgba(0,0,0,0.5); z-index: 999;
            }
            body.sidebar-open .sidebar-overlay { display: block; }
        }
        
        /* UI Elements */
        .badge-kosong { background-color: #ffebee; color: #c62828; font-weight: bold; padding: 5px 10px; border-radius: 20px; font-size: 0.7rem; border: 1px solid #ffcdd2; display: block; text-align: center; }
        .badge-ada { color: #2e7d32; font-size: 1.2rem; text-align: center; display: block; }
        .table-responsive { background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .table thead th { background-color: #495057; color: #fff; vertical-align: middle; text-align: center; white-space: nowrap; font-size: 0.85rem; border-bottom: 3px solid #dee2e6; }
        .table tbody td { vertical-align: middle; font-size: 0.85rem; padding: 8px 5px; border-right: 1px solid #f0f0f0; }
        .dt-buttons .btn { font-size: 0.8rem; }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-heading">
            <img src="<?php echo $logo_src; ?>" alt="Logo"> 
            <div style="line-height: 1.2;">
                <small class="d-block text-muted" style="font-size: 0.7rem;">MONITORING</small>
                ERM KHANZA
            </div>
        </div>
        <div class="list-group list-group-flush mt-3">
            <a href="index.php" class="list-group-item list-group-item-action">
                <i class="fas fa-home me-2"></i> Home
            </a>
            <?php foreach($menus as $key => $menu): ?>
            <a href="?page=<?php echo $key; ?>" class="list-group-item list-group-item-action <?php echo $page == $key ? 'active' : ''; ?>">
                <i class="fas <?php echo $menu['icon']; ?> me-2"></i> <?php echo $menu['label']; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <main>
        <div class="header-info sticky-top">
            <div class="d-flex align-items-center">
                <button class="btn btn-light border me-3 shadow-sm" id="menu-toggle"><i class="fas fa-bars"></i></button>
                <div>
                    <h5 class="m-0 fw-bold text-<?php echo $current_menu['color']; ?>">
                        <i class="fas <?php echo $current_menu['icon']; ?> me-2"></i> <?php echo $current_menu['label']; ?>
                    </h5>
                </div>
            </div>
            
            <?php if($page != 'audit_full'): ?>
            <div class="d-flex align-items-center gap-2">
                <div class="d-none d-md-flex input-group input-group-sm">
                    <span class="input-group-text bg-light">Tgl</span>
                    <input type="date" id="tgl_awal" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    <span class="input-group-text bg-light">s/d</span>
                    <input type="date" id="tgl_akhir" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <button class="btn btn-primary btn-sm shadow-sm" onclick="loadData(true)">
                    <i class="fas fa-search me-1"></i> <span class="d-none d-md-inline">Cari</span>
                </button>

                <?php if($page != 'bpjs'): ?>
                <div class="ms-2 text-end d-none d-lg-block">
                    <small class="d-block text-muted" style="font-size: 0.6rem;">AUTO REFRESH</small>
                    <span id="timer" class="fw-bold text-danger">60</span>s
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="container-fluid px-4 pb-5">
            <?php if ($page == 'audit_full') { ?>
                <div class="alert alert-info shadow-sm">
                    <i class="fas fa-info-circle"></i> Halaman ini menggunakan mode <b>Audit Laporan Lengkap</b>.
                </div>
                <?php 
                    if(file_exists('audit_erm_v2.php')) { include 'audit_erm_v2.php'; } 
                    else { echo "<div class='alert alert-danger'>File audit_erm_v2.php tidak ditemukan!</div>"; }
                ?>
            <?php } else { ?>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div id="loadingOverlay" class="text-center p-5" style="display:none;">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted">Mengambil data...</p>
                        </div>

                        <div class="table-responsive p-2">
                            <table class="table table-hover table-bordered table-striped mb-0 w-100" id="monitorTable">
                                <thead id="tableHeader" class="bg-light"></thead>
                                <tbody id="dataContainer"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            <?php } ?>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <script>
        // Toggle Sidebar Logic
        const body = document.body;
        const overlay = document.getElementById('sidebarOverlay');
        const menuToggle = document.getElementById('menu-toggle');

        menuToggle.addEventListener('click', (e) => {
            e.preventDefault();
            if (window.innerWidth >= 768) {
                body.classList.toggle('sidebar-closed');
            } else {
                body.classList.toggle('sidebar-open');
            }
        });

        // Close sidebar on overlay click (Mobile)
        overlay.addEventListener('click', () => {
            body.classList.remove('sidebar-open');
        });

        // Data Loading Logic
        const currentPage = '<?php echo $page; ?>';
        let dataTableInstance = null;
        
        if(currentPage !== 'audit_full') {
            let timeLeft = 60;
            let autoRefresh = (currentPage !== 'bpjs');

            function loadData(manual = false) {
                if(manual) $('#loadingOverlay').show();
                
                // Handle datepicker value safely
                let tglAwal = $('#tgl_awal').val() || '<?php echo date("Y-m-d"); ?>';
                let tglAkhir = $('#tgl_akhir').val() || '<?php echo date("Y-m-d"); ?>';

                $.ajax({
                    url: 'erm_api.php',
                    type: 'GET',
                    data: { 
                        mode: currentPage,
                        tgl_awal: tglAwal,
                        tgl_akhir: tglAkhir
                    },
                    dataType: 'json',
                    success: function(response) {
                        if ($.fn.DataTable.isDataTable('#monitorTable')) {
                            $('#monitorTable').DataTable().destroy();
                        }

                        let headerHtml = '<tr>';
                        response.columns.forEach(col => { headerHtml += `<th>${col}</th>`; });
                        headerHtml += '</tr>';
                        $('#tableHeader').html(headerHtml);

                        $('#dataContainer').html(response.html);
                        
                        dataTableInstance = $('#monitorTable').DataTable({
                            "pageLength": 25,
                            "ordering": false,
                            "scrollX": true,
                            dom: 'Bfrtip',
                            buttons: [
                                { extend: 'excel', className: 'btn btn-success btn-sm', text: '<i class="fas fa-file-excel"></i> Excel', title: 'Laporan ERM '+currentPage },
                                { extend: 'print', className: 'btn btn-secondary btn-sm', text: '<i class="fas fa-print"></i> Print' }
                            ]
                        });

                        if(autoRefresh) timeLeft = 60;
                        $('#loadingOverlay').hide();
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                        $('#dataContainer').html('<tr><td colspan="100%" class="text-center text-danger fw-bold p-4">Gagal mengambil data. Periksa koneksi database.</td></tr>');
                        $('#loadingOverlay').hide();
                    }
                });
            }

            if(autoRefresh) {
                setInterval(function() {
                    if(timeLeft <= 0) { loadData(); } 
                    else { $('#timer').text(timeLeft); timeLeft--; }
                }, 1000);
            }

            loadData(true);
        }
    </script>
</body>
</html>