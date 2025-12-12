<div class="col-md-2 sidebar p-0 collapse" id="sidebarMenu" style="min-height: 100vh; background: #212529; color: #fff;">
    <div class="p-3 font-weight-bold border-bottom border-secondary">
        <i class="fas fa-layer-group me-2"></i> E-KLAIM
    </div>
    <div class="p-3 border-bottom border-secondary small">
        user: <?= $_SESSION['casemix_user'] ?>
    </div>
    <nav class="nav flex-column mt-3">
        <a class="nav-link text-white active" href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard Klaim</a>
        <div class="text-muted small text-uppercase px-3 mt-3 mb-1">Pengembangan</div>
        <a class="nav-link text-white-50" href="#"><i class="fas fa-chart-line me-2"></i> Kendali Biaya</a>
        <a class="nav-link text-white-50" href="#"><i class="fas fa-clipboard-check me-2"></i> Audit ERM</a>
        <div class="dropdown-divider"></div>
        <a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </nav>
</div>