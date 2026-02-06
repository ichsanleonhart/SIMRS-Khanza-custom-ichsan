<div id="sidebar-wrapper">
    <div class="sidebar-heading text-center fw-bold">
        <img src="<?= isset($logo_b64) ? $logo_b64 : 'logo.php' ?>" width="30" class="bg-white rounded p-1 me-2"> 
        SIMRS Casemix
    </div>
    
    <div class="list-group list-group-flush mt-3">
        <?php $page = basename($_SERVER['PHP_SELF']); ?>

        <small class="text-uppercase text-white-50 px-3 mb-1" style="font-size:0.7rem">Menu Utama</small>
        
        <a href="dashboard.php" class="list-group-item list-group-item-action <?= ($page=='dashboard.php')?'active':'' ?>">
            <i class="fas fa-tachometer-alt w-25 text-center"></i> Dashboard
        </a>
        
        <a href="plafon_ranap.php" class="list-group-item list-group-item-action <?= ($page=='plafon_ranap.php')?'active':'' ?>">
            <i class="fas fa-hand-holding-usd w-25 text-center"></i> Input Plafon BPJS
        </a>
		
		<a href="laporan_semongko.php" class="list-group-item list-group-item-action <?= ($page=='laporan_semongko.php')?'active':'' ?>">
            <i class="fas fa-bed w-25 text-center"></i> Laporan Semongko
        </a>
		<a href="laporan_indikator_ranap.php" class="list-group-item list-group-item-action <?= ($page=='laporan_indikator_ranap.php')?'active':'' ?>">
            <i class="fas fa-chart-line w-25 text-center"></i> BoR, LOS, TOI, dll
        </a>
		<a href="laporan_penyakit.php" class="list-group-item list-group-item-action <?= ($page=='laporan_penyakit.php')?'active':'' ?>">
            <i class="fas fa-disease w-25 text-center"></i> Laporan Penyakit
        </a>
        
        <small class="text-uppercase text-white-50 px-3 mt-4 mb-1" style="font-size:0.7rem">Akun</small>
        <a href="logout.php" class="list-group-item list-group-item-action text-danger">
            <i class="fas fa-sign-out-alt w-25 text-center"></i> Logout
        </a>
    </div>
    
    <div class="text-center text-white-50 small p-3 w-100 position-absolute bottom-0">
        &copy; <?= date('Y') ?> <?= isset($nama_instansi) ? $nama_instansi : 'RS' ?>
    </div>
</div>