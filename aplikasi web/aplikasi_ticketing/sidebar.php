<!-- === SIDEBAR === -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Brand Logo -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index_admin.php">
        <div class="sidebar-brand-icon rotate-n-15"></div>
        <div class="sidebar-brand-text mx-3">
            <br><br><br>
            <img src="img/logo2.png" alt="Logo" width="200">
        </div>
    </a>

    <hr class="sidebar-divider my-0">

    <!-- Dashboard -->
    <li class="nav-item active">
        <a class="nav-link" href="index_admin.php">
            <br><br><br>
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">

   <!-- === Submenu: Manajemen Tiket === -->
<div class="sidebar-heading">Manajemen Tiket</div>
<li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTiket" aria-expanded="true" aria-controls="collapseTiket">
        <i class="fas fa-ticket-alt"></i>
        <span>Menu Tiket</span>
    </a>
<div id="collapseTiket" class="collapse" data-parent="#accordionSidebar">
  <div class="bg-white py-2 collapse-inner rounded">
    <a class="collapse-item" href="tiket.php">
      <i class="fas fa-inbox mr-2 text-primary"></i> Tiket Masuk
    </a>
    <a class="collapse-item" href="histori_tiket.php">
      <i class="fas fa-history mr-2 text-success"></i> Histori Tiket
    </a>
    <a class="collapse-item" href="data_akses_khanza.php">
      <i class="fas fa-user-lock mr-2 text-warning"></i> Akses KHanza
    </a>
    <a class="collapse-item" href="laporan_waktu_proses.php">
      <i class="fas fa-stopwatch mr-2 text-danger"></i> Handling Time
    </a>
    <a class="collapse-item" href="spo_it.php">
      <i class="fas fa-file-signature mr-2 text-info"></i> Input SPO IT
    </a>
  </div>
</div>

</li>



    <!-- === Submenu: Server Point === -->
<div class="sidebar-heading">Server Point</div>
<li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseServer" aria-expanded="true" aria-controls="collapseServer">
        <i class="fas fa-server"></i>
        <span>Server Point</span>
    </a>
    <div id="collapseServer" class="collapse" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <a class="collapse-item" href="pencatatan_server.php"><i class="fas fa-box-open mr-2"></i>Keadaan Fisik</a>
            <a class="collapse-item" href="monitoring_perangkat.php"><i class="fas fa-microchip mr-2"></i>Keadaan CPU Server</a>
        </div>
    </div>
</li>

    <hr class="sidebar-divider">

    <!-- === Submenu: Manajemen Data Master === -->
    <div class="sidebar-heading">Manajemen Data</div>
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseData" aria-expanded="true" aria-controls="collapseData">
            <i class="fas fa-database"></i>
            <span>Data Master</span>
        </a>
    <div id="collapseData" class="collapse" aria-labelledby="headingData" data-parent="#accordionSidebar">
  <div class="bg-white py-2 collapse-inner rounded">
    <a class="collapse-item" href="Mail_setting.php">
      <i class="fas fa-envelope-open-text mr-2 text-danger"></i> Mail Settings
    </a>
    <a class="collapse-item" href="data_pengguna.php">
      <i class="fas fa-users-cog mr-2 text-primary"></i> Pengguna
    </a>
    <a class="collapse-item" href="kategori_pelaporan.php">
      <i class="fas fa-tags mr-2 text-success"></i> Kategori
    </a>
    <a class="collapse-item" href="unit_kerja.php">
      <i class="fas fa-network-wired mr-2 text-warning"></i> Unit Kerja
    </a>
    <a class="collapse-item" href="master_jabatan.php">
      <i class="fas fa-sitemap mr-2 text-info"></i> Jabatan
    </a>
    <a class="collapse-item" href="master_perusahaan.php">
      <i class="fas fa-building mr-2 text-secondary"></i> Perusahaan
    </a>
  </div>
</div>

    </li>

    <hr class="sidebar-divider d-none d-md-block">

  <!-- Sidebar Toggler -->
<div class="text-center d-none d-md-inline">
    <button class="rounded-circle border-0" id="sidebarToggle"></button>
</div>

<!-- Sidebar Copyright -->
<div class="sidebar-footer text-center mt-3 mb-3">
    <div class="text-white small">
        &copy; <?= date('Y'); ?> <strong>M. Wira. Sb</strong><br>
        <i class="fas fa-phone-alt mr-1 text-success"></i> 082177846209
    </div>
</div>


</ul>
