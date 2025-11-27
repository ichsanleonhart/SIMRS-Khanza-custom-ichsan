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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; overflow-x: hidden; }
        
        /* Sidebar */
        #wrapper { display: flex; width: 100%; transition: all 0.3s; }
        #sidebar-wrapper { min-height: 100vh; width: 250px; margin-left: -250px; background-color: #343a40; color: #fff; transition: all 0.3s; position: fixed; z-index: 1000; }
        #sidebar-wrapper.active { margin-left: 0; }
        #page-content-wrapper { width: 100%; margin-left: 0; transition: all 0.3s; }
        #wrapper.toggled #page-content-wrapper { margin-left: 250px; } /* Push content */
        
        .sidebar-heading { padding: 1.5rem; font-size: 1.2rem; background: rgba(0,0,0,0.2); display: flex; align-items: center; gap: 10px; }
        .sidebar-heading img { height: 40px; }
        .list-group-item { background-color: transparent; color: #cfd8dc; border: none; padding: 15px 20px; }
        .list-group-item:hover { background-color: #495057; color: #fff; }
        .list-group-item.active { background-color: #007bff; color: #fff; border-left: 4px solid #fff; }
        
        /* Table Design */
        .table-responsive { background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .table thead th { background-color: #495057; color: #fff; vertical-align: middle; text-align: center; white-space: nowrap; font-size: 0.85rem; border-bottom: 3px solid #dee2e6; position: sticky; top: 0; z-index: 10; }
        .table tbody td { vertical-align: middle; font-size: 0.85rem; padding: 8px 5px; border-right: 1px solid #f0f0f0; }
        
        /* Status Badges */
        .badge-kosong { background-color: #ffebee; color: #c62828; font-weight: bold; padding: 5px 10px; border-radius: 20px; font-size: 0.7rem; border: 1px solid #ffcdd2; display: block; text-align: center; }
        .badge-ada { color: #2e7d32; font-size: 1.2rem; text-align: center; display: block; }
        
        /* Header Info */
        .header-info { background: #fff; padding: 15px 20px; border-bottom: 1px solid #ddd; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 4px rgba(0,0,0,0.02); margin-bottom: 20px; }
    </style>
</head>
<body class="<?php echo isset($_GET['sidebar']) ? 'toggled' : ''; ?>" id="wrapper">

    <div id="sidebar-wrapper" class="active">
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

    <div id="page-content-wrapper" style="margin-left: 250px;">
        
        <div class="header-info">
            <div class="d-flex align-items-center">
                <button class="btn btn-light border me-3" id="menu-toggle"><i class="fas fa-bars"></i></button>
                <div>
                    <h4 class="m-0 fw-bold text-<?php echo $current_menu['color']; ?>">
                        <i class="fas <?php echo $current_menu['icon']; ?> me-2"></i> <?php echo $current_menu['label']; ?>
                    </h4>
                    <small class="text-muted"><?php echo $nama_rs; ?> - Realtime Monitoring</small>
                </div>
            </div>
            
            <?php if($page != 'audit_full'): ?>
            <div class="d-flex align-items-center">
                <div class="spinner-border spinner-border-sm text-secondary me-2" id="loadingSpinner" role="status" style="display:none;"></div>
                <div class="text-end me-3">
                    <small class="d-block text-muted" style="font-size: 0.7rem;">AUTO REFRESH</small>
                    <span id="timer" class="fw-bold text-danger">60</span> Detik
                </div>
                <button class="btn btn-primary btn-sm shadow-sm" onclick="loadData()"><i class="fas fa-sync-alt"></i></button>
            </div>
            <?php endif; ?>
        </div>

        <div class="container-fluid px-4 pb-5">
            <?php if ($page == 'audit_full') { ?>
                <div class="alert alert-info shadow-sm">
                    <i class="fas fa-info-circle"></i> Halaman ini menggunakan mode <b>Audit Laporan Lengkap</b> dengan filter tanggal.
                </div>
                <?php 
                    // Pastikan file audit_erm_v2.php ada di folder yang sama
                    if(file_exists('audit_erm_v2.php')) {
                        include 'audit_erm_v2.php'; 
                    } else {
                        echo "<div class='alert alert-danger'>File audit_erm_v2.php tidak ditemukan!</div>";
                    }
                ?>
            <?php } else { ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="monitorTable">
                        <thead id="tableHeader">
                            </thead>
                        <tbody id="dataContainer">
                            <tr><td colspan="10" class="text-center p-5"><i class="fas fa-circle-notch fa-spin fa-3x text-muted"></i><br>Memuat Data...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 text-muted small">
                    * Data ditampilkan berdasarkan pasien aktif hari ini. <span class="badge-kosong d-inline-block w-auto">MERAH</span> = Belum Diisi, <span class="text-success fw-bold">✓</span> = Sudah Diisi.
                </div>
            <?php } ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle Sidebar
        $("#menu-toggle").click(function(e) {
            e.preventDefault();
            $("#sidebar-wrapper").toggleClass("active");
            if($("#sidebar-wrapper").hasClass("active")) {
                $("#sidebar-wrapper").css("margin-left", "0");
                $("#page-content-wrapper").css("margin-left", "250px");
            } else {
                $("#sidebar-wrapper").css("margin-left", "-250px");
                $("#page-content-wrapper").css("margin-left", "0");
            }
        });

        const currentPage = '<?php echo $page; ?>';
        
        if(currentPage !== 'audit_full') {
            let timeLeft = 60; // Refresh 60 detik sesuai request legacy file
            
            function loadData() {
                $('#loadingSpinner').show();
                $.ajax({
                    url: 'erm_api.php',
                    type: 'GET',
                    data: { mode: currentPage },
                    dataType: 'json',
                    success: function(response) {
                        // Render Header
                        let headerHtml = '<tr>';
                        response.columns.forEach(col => {
                            headerHtml += `<th>${col}</th>`;
                        });
                        headerHtml += '</tr>';
                        $('#tableHeader').html(headerHtml);

                        // Render Body
                        $('#dataContainer').html(response.html);
                        
                        timeLeft = 60;
                        $('#loadingSpinner').hide();
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                        $('#dataContainer').html('<tr><td colspan="100%" class="text-center text-danger fw-bold p-4">Gagal mengambil data. Periksa koneksi database.</td></tr>');
                        $('#loadingSpinner').hide();
                    }
                });
            }

            setInterval(function() {
                if(timeLeft <= 0) {
                    loadData();
                } else {
                    document.getElementById('timer').innerText = timeLeft;
                    timeLeft--;
                }
            }, 1000);

            // Initial Load
            loadData();
        }
    </script>
</body>
</html>