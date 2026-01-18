<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="<?= BASE_URL ?>" class="brand-link">
      <img src="<?= BASE_URL ?>/image_load.php" alt="Logo RS" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light"><b>e-Dokter</b></span>
    </a>

    <div class="sidebar">
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nama'] ?? 'Dr') ?>&background=random" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block"><?= htmlspecialchars(substr($_SESSION['nama'] ?? 'Dokter', 0, 25)) ?></a>
          <small class="text-muted"><i class="fas fa-user-md"></i> <?= ucfirst($_SESSION['role'] ?? 'User') ?></small>
        </div>
      </div>

      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          
          <li class="nav-item">
            <a href="<?= BASE_URL ?>/index.php" class="nav-link <?= ($menu == 'dashboard') ? 'active' : '' ?>">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>

          <li class="nav-header">JASA MEDIS</li>

          <li class="nav-item">
            <a href="<?= BASE_URL ?>/modul/jasmed/index.php" class="nav-link <?= ($menu == 'jasmed') ? 'active' : '' ?>">
              <i class="nav-icon fas fa-calculator"></i>
              <p>Audit Jasmed (Total)</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="<?= BASE_URL ?>/modul/jasmed/ringkasan_shift.php" class="nav-link <?= ($menu == 'ringkasan_shift') ? 'active' : '' ?>">
              <i class="nav-icon fas fa-clock"></i>
              <p>Laporan Per Shift</p>
            </a>
          </li>

          </ul>
      </nav>
    </div>
  </aside>