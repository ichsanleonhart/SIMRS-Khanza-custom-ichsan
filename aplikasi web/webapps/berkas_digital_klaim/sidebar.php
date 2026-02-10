<div id="sidebar-wrapper">
    <div class="sidebar-heading text-center fw-bold">
        <img src="<?= isset($logo_b64) ? $logo_b64 : 'logo.php' ?>" width="30" class="bg-white rounded p-1 me-2"> 
        SIMRS Casemix
    </div>
    
    <div class="list-group list-group-flush mt-3">
        <?php 
            $page = basename($_SERVER['PHP_SELF']); 
            
            // Definisi Grup untuk Auto-Expand
            $group_dashboard = ['dashboard.php', 'plafon_ranap.php', 'laporan_semongko.php'];
            $group_rm        = ['laporan_indikator_ranap.php', 'laporan_penyakit.php'];
            $group_farmasi   = ['laporan_farmasi.php', 'laporan_rl.php'];
            
            // Cek Status Active Group
            $show_dashboard = in_array($page, $group_dashboard) ? 'show' : '';
            $active_dash    = in_array($page, $group_dashboard) ? 'active' : '';
            
            $show_rm        = in_array($page, $group_rm) ? 'show' : '';
            $active_rm      = in_array($page, $group_rm) ? 'active' : '';

            $show_farmasi   = in_array($page, $group_farmasi) ? 'show' : '';
            $active_farmasi = in_array($page, $group_farmasi) ? 'active' : '';
        ?>

        <small class="text-uppercase text-white-50 px-3 mb-1" style="font-size:0.7rem">Menu Utama</small>
        
        <a class="list-group-item list-group-item-action dropdown-toggle <?= $active_dash ?>" href="#menuDashboard" data-bs-toggle="collapse" aria-expanded="<?= !empty($show_dashboard) ? 'true' : 'false' ?>">
            <i class="fas fa-tachometer-alt w-25 text-center"></i> Dashboard & Input
        </a>
        <div class="collapse <?= $show_dashboard ?>" id="menuDashboard">
            <div class="bg-dark bg-opacity-25 py-1">
                <a href="dashboard.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='dashboard.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.85rem;">
                    <i class="fas fa-file-invoice me-2"></i> Monitoring Berkas
                </a>
                <a href="plafon_ranap.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='plafon_ranap.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.85rem;">
                    <i class="fas fa-hand-holding-usd me-2"></i> Input Plafon BPJS
                </a>
                <a href="laporan_semongko.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_semongko.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.85rem;">
                    <i class="fas fa-bed me-2"></i> Laporan Semongko
                </a>
            </div>
        </div>

        <a class="list-group-item list-group-item-action dropdown-toggle mt-1 <?= $active_rm ?>" href="#menuRM" data-bs-toggle="collapse" aria-expanded="<?= !empty($show_rm) ? 'true' : 'false' ?>">
            <i class="fas fa-book-medical w-25 text-center"></i> Laporan RM
        </a>
        <div class="collapse <?= $show_rm ?>" id="menuRM">
            <div class="bg-dark bg-opacity-25 py-1">
                <a href="laporan_indikator_ranap.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_indikator_ranap.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.85rem;">
                    <i class="fas fa-chart-line me-2"></i> Indikator (BoR/LOS)
                </a>
                <a href="laporan_penyakit.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_penyakit.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.85rem;">
                    <i class="fas fa-disease me-2"></i> Laporan Penyakit
                </a>
            </div>
        </div>

        <a class="list-group-item list-group-item-action dropdown-toggle mt-1 <?= $active_farmasi ?>" href="#menuFarmasi" data-bs-toggle="collapse" aria-expanded="<?= !empty($show_farmasi) ? 'true' : 'false' ?>">
            <i class="fas fa-pills w-25 text-center"></i> Farmasi & RL
        </a>
        <div class="collapse <?= $show_farmasi ?>" id="menuFarmasi">
            <div class="bg-dark bg-opacity-25 py-1">
                <a href="laporan_rl_3.18.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.18.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.85rem;">
                    <i class="fas fa-prescription-bottle-alt me-2"></i> Penggunaan Obat (RL 3.18)
                </a>
                <a href="#" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white text-opacity-50" style="font-size: 0.85rem;" onclick="alert('Fitur Laporan RL sedang dikembangkan!')">
                    <i class="fas fa-file-contract me-2"></i> Laporan RL Dinkes
                </a>
            </div>
        </div>
        
        <small class="text-uppercase text-white-50 px-3 mt-4 mb-1" style="font-size:0.7rem">Akun</small>
        <a href="logout.php" class="list-group-item list-group-item-action text-danger">
            <i class="fas fa-sign-out-alt w-25 text-center"></i> Logout
        </a>
    </div>
    
    <div class="text-center text-white-50 small p-3 w-100 position-absolute bottom-0">
        &copy; <?= date('Y') ?> <?= isset($nama_instansi) ? $nama_instansi : 'RS' ?>
    </div>
</div>

<style>
    /* Rotasi Icon Chevron saat Collapsed */
    .list-group-item.dropdown-toggle::after {
        display: inline-block;
        margin-left: auto;
        vertical-align: middle;
        content: "\f078"; /* FontAwesome Chevron Down */
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        font-size: 0.7rem;
        float: right;
        transition: transform 0.3s ease;
    }
    /* Putar icon jika tidak collapsed (aria-expanded=true) */
    .list-group-item.dropdown-toggle[aria-expanded="true"]::after {
        transform: rotate(180deg);
    }
</style>