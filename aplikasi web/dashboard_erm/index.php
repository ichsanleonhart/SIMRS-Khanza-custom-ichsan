<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Monitoring ERM - <?php echo $nama_rs; ?></title>
    <link rel="icon" type="image/png" href="<?php echo $logo_src; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .card-menu { transition: transform 0.3s; cursor: pointer; border: none; border-radius: 15px; }
        .card-menu:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .icon-large { font-size: 3rem; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="text-center mb-5">
            <img src="<?php echo $logo_src; ?>" height="80" class="mb-3">
            <h2 class="fw-bold text-secondary">Portal Monitoring ERM</h2>
            <h4 class="text-primary"><?php echo $nama_rs; ?></h4>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-md-4 col-lg-3">
                <a href="erm_monitor.php?page=igd" class="text-decoration-none">
                    <div class="card card-menu h-100 bg-danger text-white text-center p-4">
                        <i class="fas fa-ambulance icon-large"></i>
                        <h4>IGD</h4>
                        <p class="mb-0">Monitoring Gawat Darurat</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-lg-3">
                <a href="erm_monitor.php?page=ralan" class="text-decoration-none">
                    <div class="card card-menu h-100 bg-info text-white text-center p-4">
                        <i class="fas fa-user-md icon-large"></i>
                        <h4>Poliklinik</h4>
                        <p class="mb-0">Monitoring Rawat Jalan</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-lg-3">
                <a href="erm_monitor.php?page=ranap" class="text-decoration-none">
                    <div class="card card-menu h-100 bg-success text-white text-center p-4">
                        <i class="fas fa-procedures icon-large"></i>
                        <h4>Rawat Inap</h4>
                        <p class="mb-0">Monitoring Bangsal</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-lg-3">
                <a href="erm_monitor.php?page=operasi" class="text-decoration-none">
                    <div class="card card-menu h-100 bg-warning text-dark text-center p-4">
                        <i class="fas fa-syringe icon-large"></i>
                        <h4>Kamar Operasi</h4>
                        <p class="mb-0">Monitoring Bedah/OK</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-lg-3">
                <a href="erm_monitor.php?page=bpjs" class="text-decoration-none">
                    <div class="card card-menu h-100 bg-primary text-white text-center p-4">
                        <i class="fas fa-file-invoice-dollar icon-large"></i>
                        <h4>Casemix BPJS</h4>
                        <p class="mb-0">Kelengkapan Klaim</p>
                    </div>
                </a>
            </div>
             <div class="col-md-4 col-lg-3">
                <a href="erm_monitor.php?page=audit_full" class="text-decoration-none">
                    <div class="card card-menu h-100 bg-secondary text-white text-center p-4">
                        <i class="fas fa-chart-bar icon-large"></i>
                        <h4>Laporan Audit</h4>
                        <p class="mb-0">Rekapitulasi Total</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</body>
</html>