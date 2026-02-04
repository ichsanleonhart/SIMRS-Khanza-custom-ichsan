<?php
// File: layout/sidebar.php

// Helper sederhana untuk cek menu aktif
function is_active($page_name) {
    // Ambil nama file dari URL
    $current_page = basename($_SERVER['PHP_SELF']);
    return ($current_page == $page_name) ? 'active' : '';
}
?>

<aside class="sidebar" id="mainSidebar">
    <a href="#" class="brand-link">
        <img src="<?= $logo_b64 ?>" alt="Logo" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;">
        <span class="fw-light font-weight-bold"><?= substr($nama_rs, 0, 18) ?>...</span>
    </a>

    <div class="user-panel">
        <div class="image">
            <img src="https://ui-avatars.com/api/?name=<?= $_SESSION['user_id'] ?>&background=random" class="rounded-circle" style="width: 35px;" alt="User Image">
        </div>
        <div class="info ms-3">
            <a href="#" class="d-block text-white text-decoration-none"><?= $_SESSION['user_id'] ?></a>
        </div>
    </div>

    <nav class="mt-3 px-2">
        <ul class="nav flex-column">
            
            <li class="nav-item">
                <a href="../../modules/dashboard/index.php" class="nav-link <?= is_active('index.php') ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>

            <li class="nav-item">
                <a href="../../modules/ranap/index.php" class="nav-link <?= is_active('index.php') && strpos($_SERVER['REQUEST_URI'], 'ranap') !== false ? 'active' : '' ?>">
                    <i class="fas fa-procedures"></i>
                    Kunjungan Ranap
                </a>
            </li>

            <?php if(isSuperAdmin()): ?>
            <li class="nav-header text-uppercase small text-muted mt-3 mb-1 px-3">Administrator</li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-users-cog"></i>
                    Manajemen User
                </a>
            </li>
            <?php endif; ?>

        </ul>
    </nav>
</aside>

<div class="content-wrapper">