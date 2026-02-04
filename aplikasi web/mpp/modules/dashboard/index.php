<?php
// File: modules/dashboard/index.php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../helpers/auth_helper.php';

// Cek Login
cekLogin();

// LOAD LAYOUT
require_once '../../layout/header.php';
require_once '../../layout/sidebar.php';
?>

<div class="container-fluid">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 text-gray-800"><i class="fas fa-home me-2"></i> Dashboard MPP</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card bg-primary text-white shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h6 mb-0">Pasien Ranap Aktif</div>
                            <?php
                            $q_count = $pdo->query("SELECT COUNT(*) FROM kamar_inap WHERE stts_pulang = '-'")->fetchColumn();
                            ?>
                            <div class="h2 mb-0 fw-bold"><?= $q_count ?></div>
                        </div>
                        <i class="fas fa-bed fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card bg-success text-white shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h6 mb-0">Total MPP Skrining</div>
                            <div class="h2 mb-0 fw-bold">0</div>
                        </div>
                        <i class="fas fa-notes-medical fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card bg-warning text-dark shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h6 mb-0">Belum Pulang (Audit)</div>
                             <div class="h2 mb-0 fw-bold">-</div>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Selamat Datang, Kamerad!</h6>
        </div>
        <div class="card-body">
            <p>Anda login sebagai: <strong><?= $_SESSION['role'] ?></strong></p>
            <p>Silakan akses menu <strong>Kunjungan Ranap</strong> di sidebar untuk memulai proses skrining dan monitoring pasien.</p>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> 
                Sistem ini terhubung langsung dengan database SIMKES Khanza.
                Mohon berhati-hati dalam melakukan perubahan data hak akses.
            </div>
        </div>
    </div>

</div>
<?php
// LOAD FOOTER
require_once '../../layout/footer.php';
?>