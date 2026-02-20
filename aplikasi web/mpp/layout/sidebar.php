<?php
// File: layout/sidebar.php

function is_active($page_name) {
    return (basename($_SERVER['PHP_SELF']) == $page_name) ? 'active' : '';
}
function is_folder_active($folder_names) {
    if(!is_array($folder_names)) $folder_names = [$folder_names];
    foreach($folder_names as $folder) {
        if(strpos($_SERVER['REQUEST_URI'], $folder) !== false) return true;
    }
    return false;
}
?>

<nav id="sidebar">
    <div class="sidebar-header">
        <div class="d-flex align-items-center">
            <img src="<?= $logo_b64 ?>" alt="Logo" class="rounded-circle me-2 bg-white p-1" style="width: 35px; height: 35px; object-fit: contain;">
            <div style="line-height: 1.2;">
                <div class="fw-bold text-white text-truncate" style="font-size: 0.85rem; max-width: 180px;" title="<?= $nama_rs ?>">
                    <?= $nama_rs ?>
                </div>
                <div class="text-muted" style="font-size: 0.75rem; color: white !important;">Dashboard System</div>
            </div>
        </div>
    </div>

    <div class="px-3 py-3 border-bottom border-secondary border-opacity-25 bg-black bg-opacity-10">
        <div class="d-flex align-items-center">
            <img src="https://ui-avatars.com/api/?name=<?= $_SESSION['user_id'] ?>&background=0d6efd&color=fff" class="rounded-circle" style="width: 32px; height: 32px;">
            <div class="ms-2 text-white small">
                <div class="fw-bold"><?= $_SESSION['user_id'] ?></div>
                <div class="text-success" style="font-size: 0.7rem;"><i class="fas fa-circle fa-xs me-1"></i> Online</div>
            </div>
        </div>
    </div>

    <ul class="list-unstyled components">
        
        <?php if (cekAkses('mpp_skrining')): ?>
        <li class="mb-1">
            <a href="<?= $base_url ?>modules/dashboard/index.php" class="<?= is_active('index.php') && !is_folder_active(['ranap', 'ralan', 'mpp', 'edokter']) ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard Umum
            </a>
        </li>

        <li class="mb-1">
            <a href="#menuKunjungan" data-bs-toggle="collapse" aria-expanded="<?= is_folder_active(['ranap', 'ralan']) && !is_folder_active('edokter') ? 'true' : 'false' ?>" class="dropdown-toggle">
                <i class="fas fa-hospital-user"></i> Monitoring Kunjungan
            </a>
            <ul class="collapse list-unstyled <?= is_folder_active(['ranap', 'ralan']) && !is_folder_active('edokter') ? 'show' : '' ?>" id="menuKunjungan">
                <li>
                    <a href="<?= $base_url ?>modules/ranap/index.php" class="<?= is_folder_active('ranap') ? 'active' : '' ?>">
                        <i class="fas fa-procedures"></i> Rawat Inap
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url ?>modules/ralan/index.php" class="<?= is_folder_active('ralan') && !is_folder_active('edokter') ? 'active' : '' ?>">
                        <i class="fas fa-wheelchair"></i> Rawat Jalan
                    </a>
                </li>
            </ul>
        </li>

        <li class="mb-1">
            <a href="#menuMPP" data-bs-toggle="collapse" aria-expanded="<?= is_folder_active('mpp') ? 'true' : 'false' ?>" class="dropdown-toggle">
                <i class="fas fa-clipboard-list"></i> Manajer Pelayanan
            </a>
            <ul class="collapse list-unstyled <?= is_folder_active('mpp') ? 'show' : '' ?>" id="menuMPP">
                <li>
                    <a href="<?= $base_url ?>modules/mpp/index.php" class="<?= is_active('index.php') && is_folder_active('mpp') ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie"></i> Dashboard & Analisa
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url ?>modules/mpp/master_masalah.php" class="<?= is_active('master_masalah.php') ? 'active' : '' ?>">
                        <i class="fas fa-database"></i> Master Masalah
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>
		
        <?php if (cekAkses('soap_perawatan')): ?>
        <li class="mb-1 <?= cekAkses('mpp_skrining') ? 'mt-3' : '' ?>">
            <a href="#menuEDokter" data-bs-toggle="collapse" aria-expanded="<?= is_folder_active('edokter') ? 'true' : 'false' ?>" class="dropdown-toggle text-warning">
                <i class="fas fa-user-md"></i> E-Dokter
            </a>
            <ul class="collapse list-unstyled <?= is_folder_active('edokter') ? 'show' : '' ?>" id="menuEDokter">
                <li>
                    <a href="<?= $base_url ?>modules/edokter/ralan/index.php" class="<?= is_folder_active('edokter/ralan') ? 'active' : '' ?>">
                        <i class="fas fa-stethoscope"></i> Poliklinik (Ralan)
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url ?>modules/edokter/ranap/index.php" class="<?= is_folder_active('edokter/ranap') ? 'active' : '' ?>">
                        <i class="fas fa-bed"></i> Bangsal (Ranap)
                    </a>
                </li>
				<li>
                    <a href="<?= $base_url ?>modules/edokter/jasmed/index.php" class="<?= is_folder_active('edokter/jasmed') ? 'active' : '' ?>">
                        <i class="fas fa-wallet"></i> Hitung Jasmed
                    </a>
                </li>
				<li>
                    <a href="<?= $base_url ?>modules/edokter/konsultasi/index.php" class="<?= is_folder_active('edokter/konsultasi') ? 'active' : '' ?>">
                        <i class="fas fa-comments-medical"></i> Konsultasi Medis
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        <li class="nav-header text-uppercase small text-muted mt-4 mb-2 px-3 fw-bold" style="font-size:0.7rem; color: white !important;">Akun</li>
        <li>
            <a href="<?= $base_url ?>modules/auth/logout.php" class="text-danger-emphasis">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>

    </ul>
</nav>

<div id="content">
    
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm rounded mb-4 main-header">
        <div class="container-fluid">
            <button type="button" id="sidebarCollapse" class="btn btn-light text-primary shadow-none border me-2">
                <i class="fas fa-bars"></i>
            </button>

            <span class="navbar-brand fw-bold text-dark fs-6">
                Sistem Terintegrasi <span class="fw-light text-muted d-none d-sm-inline">MPP & E-Dokter</span>
            </span>

            <div class="ms-auto">
                <div class="dropdown">
                    <button class="btn btn-white dropdown-toggle border-0" type="button" data-bs-toggle="dropdown">
                        <i class="far fa-user me-1"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><a class="dropdown-item text-danger" href="<?= $base_url ?>modules/auth/logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>