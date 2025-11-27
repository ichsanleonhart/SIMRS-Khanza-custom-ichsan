
<?php
/*
 * File header.php (FINAL FIX - STRUCTURE REFACTOR)
 * - Fix: Menghapus container-fluid/row pembungkus agar Main bisa resize otomatis.
 * - Fitur: Sidebar & Main sejajar (siblings), bukan parent-child dalam grid.
 */

require_once(dirname(__DIR__) . '/config/koneksi.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php'); 
    exit;
}

$nama_instansi = "Dashboard RS"; 
$logo_src = "core/logo.php";

$sql_setting = "SELECT setting.nama_instansi, setting.logo FROM setting LIMIT 1";
$result_setting = $koneksi->query($sql_setting);
if ($result_setting && $result_setting->num_rows > 0) {
    $row_setting = $result_setting->fetch_assoc();
    $nama_instansi = htmlspecialchars($row_setting['nama_instansi']);
}

$current_page = basename($_SERVER['PHP_SELF']);

function get_collapse_class($pages, $current) {
    return in_array($current, $pages) ? 'show' : '';
}
function is_active($page, $current) {
    return ($page == $current) ? 'active' : '';
}
function get_arrow_class($pages, $current) {
    return in_array($current, $pages) ? '' : 'collapsed'; 
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?> - <?php echo $nama_instansi; ?></title>
    <link rel="icon" href="<?php echo $logo_src; ?>" type="image/png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --sidebar-width: 260px;
            --header-height: 56px;
            --transition-speed: 0.3s;
            --sidebar-bg: #f8f9fa;
            --primary-color: #0d6efd;
        }

        body {
            font-size: .875rem;
            overflow-x: hidden;
            background-color: #f4f6f9;
            /*padding-top: var(--header-height); *//* Body turun sesuai tinggi header */
        }

        /* --- NAVBAR (FIXED TOP) --- */
        .navbar {
            height: var(--header-height);
            z-index: 1030; /* Di atas sidebar */
        }
        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
            font-size: 1rem;
            background-color: rgba(0, 0, 0, .25);
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .25);
            width: var(--sidebar-width);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* --- SIDEBAR (INDEPENDENT) --- */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1000; /* Di bawah navbar */
            padding-top: var(--header-height); 
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            border-right: 1px solid #dee2e6;
            transition: transform var(--transition-speed) ease-in-out;
            overflow-y: auto;
        }

        /* --- MAIN CONTENT (DYNAMIC WIDTH) --- */
        main {
            /* KUNCI: Block element, bukan Flex item */
            display: block; 
            width: auto; 
            /* Margin kiri sesuai lebar sidebar */
            margin-left: var(--sidebar-width); 
            padding: 20px;
            min-height: calc(100vh - var(--header-height));
            transition: margin-left var(--transition-speed) ease-in-out;
        }

        /* --- LOGIKA TOGGLE DESKTOP --- */
        /* Saat ditutup, Sidebar geser ke kiri (hilang), Main margin jadi 0 */
        body.sidebar-closed .sidebar {
            transform: translateX(-100%); /* Lebih performant daripada margin-left */
        }
        body.sidebar-closed main {
            margin-left: 0; /* Konten otomatis melebar full */
        }

        /* --- LOGIKA TOGGLE MOBILE --- */
        @media (max-width: 767.98px) {
            /* Default Mobile: Sidebar sembunyi, Main full */
            .sidebar { transform: translateX(-100%); }
            main { margin-left: 0; }

            /* Saat Mobile Open: Sidebar muncul, Main TETAP (ditimpa overlay) */
            body.sidebar-open .sidebar { transform: translateX(0); box-shadow: 0 0 15px rgba(0,0,0,0.2); }
            
            /* Overlay Mobile */
            .sidebar-overlay {
                display: none;
                position: fixed; inset: 0;
                background: rgba(0,0,0,0.5); z-index: 999;
            }
            body.sidebar-open .sidebar-overlay { display: block; }
        }

        /* Menu Styling (Sama seperti sebelumnya) */
        .nav-link { color: #333; padding: 8px 16px; font-weight: 500; }
        .nav-link:hover { color: var(--primary-color); background-color: #e9ecef; }
        .nav-link.active { color: var(--primary-color); background-color: #e7f1ff; border-left: 3px solid var(--primary-color); }
        .sidebar-group-header { cursor: pointer; padding: 10px 15px; margin-top: 5px; color: #6c757d; font-size: 0.75rem; font-weight: 700; display: flex; justify-content: space-between; text-transform: uppercase;}
        .sidebar-group-header:hover { color: var(--primary-color); }
        .sidebar-group-header .fa-chevron-down { transition: transform 0.3s; }
        .sidebar-group-header.collapsed .fa-chevron-down { transform: rotate(-90deg); }
        .collapse .nav-flex-column { padding-left: 10px; background-color: #fff; }

    </style>
</head>
<body>

<header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
  <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="#">
      <img src="<?php echo $logo_src; ?>" alt="Logo" width="25" height="25" class="d-inline-block align-text-top me-2">
      <?php echo $nama_instansi; ?>
  </a>
  
  <button class="btn btn-link text-white d-none d-md-block ms-2" id="sidebarToggleDesktop">
      <i class="fas fa-bars fa-lg"></i>
  </button>
  
  <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" id="sidebarToggleMobile" style="right: 10px; top: 15px;">
    <span class="navbar-toggler-icon"></span>
  </button>
  
  <div class="w-100"></div>
  
  <div class="navbar-nav d-flex flex-row">
    <div class="nav-item text-nowrap">
      <span class="nav-link px-3 text-white small">Halo, <?php echo htmlspecialchars($_SESSION['nama_user']); ?></span>
    </div>
    <div class="nav-item text-nowrap">
      <a class="nav-link px-3 text-danger" href="core/logout.php" title="Keluar"><i class="fas fa-sign-out-alt"></i></a>
    </div>
  </div>
</header>

<div class="sidebar-overlay" id="mobileOverlay"></div>

<nav id="sidebarMenu" class="sidebar">
  <div class="pt-3 pb-5">
    <ul class="nav flex-column mb-2">
      <li class="nav-item">
        <a class="nav-link <?php echo is_active('dashboard.php', $current_page); ?>" href="dashboard.php">
          <i class="fas fa-home me-2 text-primary" style="width: 20px;"></i> Dashboard Utama
        </a>
      </li>
    </ul>

    <?php $grp_biaya = ['kunjungan_ralan.php', 'kunjungan_ranap.php']; ?>
    <div class="sidebar-group-header <?php echo get_arrow_class($grp_biaya, $current_page); ?>" data-bs-toggle="collapse" data-bs-target="#menuBiaya">
        <span>Kendali Biaya</span> <i class="fas fa-chevron-down"></i>
    </div>
    <div class="collapse <?php echo get_collapse_class($grp_biaya, $current_page); ?>" id="menuBiaya">
        <ul class="nav flex-column nav-flex-column">
            <li class="nav-item"><a class="nav-link <?php echo is_active('kunjungan_ralan.php', $current_page); ?>" href="kunjungan_ralan.php"><i class="fas fa-walking me-2" style="width: 20px;"></i> Billing Ralan</a></li>
            <li class="nav-item"><a class="nav-link <?php echo is_active('kunjungan_ranap.php', $current_page); ?>" href="kunjungan_ranap.php"><i class="fas fa-procedures me-2" style="width: 20px;"></i> Billing Ranap</a></li>
        </ul>
    </div>

    <?php $grp_keuangan = ['laporan_kas.php', 'laporan_billing_global.php', 'laporan_piutang_detail.php', 'laporan_detail.php', 'laporan_tindakan.php', 'laporan_jasa_medis.php', 'laporan_analisa_lengkap.php']; ?>
    <div class="sidebar-group-header <?php echo get_arrow_class($grp_keuangan, $current_page); ?>" data-bs-toggle="collapse" data-bs-target="#menuKeuangan">
        <span>Laporan Keuangan</span> <i class="fas fa-chevron-down"></i>
    </div>
    <div class="collapse <?php echo get_collapse_class($grp_keuangan, $current_page); ?>" id="menuKeuangan">
        <ul class="nav flex-column nav-flex-column">
            <li class="nav-item"><a class="nav-link <?php echo is_active('laporan_kas.php', $current_page); ?>" href="laporan_kas.php"><i class="fas fa-wallet me-2" style="width: 20px;"></i> Laporan Kas</a></li>
            <li class="nav-item"><a class="nav-link <?php echo is_active('laporan_billing_global.php', $current_page); ?>" href="laporan_billing_global.php"><i class="fas fa-file-invoice-dollar me-2" style="width: 20px;"></i> Detail Billing</a></li>
            <li class="nav-item"><a class="nav-link <?php echo is_active('laporan_piutang_detail.php', $current_page); ?>" href="laporan_piutang_detail.php"><i class="fas fa-file-invoice me-2" style="width: 20px;"></i> Laporan Piutang</a></li>
            <li class="nav-item"><a class="nav-link <?php echo is_active('laporan_detail.php', $current_page); ?>" href="laporan_detail.php"><i class="fas fa-cash-register me-2" style="width: 20px;"></i> Laporan Tunai</a></li>
            <li class="nav-item"><a class="nav-link <?php echo is_active('laporan_tindakan.php', $current_page); ?>" href="laporan_tindakan.php"><i class="fas fa-stethoscope me-2" style="width: 20px;"></i> Analisa Tindakan</a></li>
            <li class="nav-item"><a class="nav-link <?php echo is_active('laporan_analisa_lengkap.php', $current_page); ?>" href="laporan_analisa_lengkap.php"><i class="fas fa-microscope me-2" style="width: 20px;"></i> Analisa Lengkap</a></li>
            <li class="nav-item"><a class="nav-link <?php echo is_active('laporan_jasa_medis.php', $current_page); ?>" href="laporan_jasa_medis.php"><i class="fas fa-user-tie me-2" style="width: 20px;"></i> Jasa Medis</a></li>
        </ul>
    </div>

    <?php $grp_stat = ['laporan_kunjungan.php', 'laporan_indikator_ranap.php', 'laporan_penyakit.php']; ?>
    <div class="sidebar-group-header <?php echo get_arrow_class($grp_stat, $current_page); ?>" data-bs-toggle="collapse" data-bs-target="#menuStatistik">
        <span>Statistik & Indikator</span> <i class="fas fa-chevron-down"></i>
    </div>
    <div class="collapse <?php echo get_collapse_class($grp_stat, $current_page); ?>" id="menuStatistik">
        <ul class="nav flex-column nav-flex-column">
            <li class="nav-item"><a class="nav-link <?php echo is_active('laporan_kunjungan.php', $current_page); ?>" href="laporan_kunjungan.php"><i class="fas fa-users me-2" style="width: 20px;"></i> Kunjungan RS</a></li>
            <li class="nav-item"><a class="nav-link <?php echo is_active('laporan_indikator_ranap.php', $current_page); ?>" href="laporan_indikator_ranap.php"><i class="fas fa-chart-line me-2" style="width: 20px;"></i> BOR LOS TOI</a></li>
            <li class="nav-item"><a class="nav-link <?php echo is_active('laporan_penyakit.php', $current_page); ?>" href="laporan_penyakit.php"><i class="fas fa-heartbeat me-2" style="width: 20px;"></i> Laporan Penyakit</a></li>
        </ul>
    </div>

    <?php $grp_kpi = ['laporan_kinerja_dokter.php', 'laporan_operasi_view.php']; ?>
    <div class="sidebar-group-header <?php echo get_arrow_class($grp_kpi, $current_page); ?>" data-bs-toggle="collapse" data-bs-target="#menuKPI">
        <span>Key Performance</span> <i class="fas fa-chevron-down"></i>
    </div>
    <div class="collapse <?php echo get_collapse_class($grp_kpi, $current_page); ?>" id="menuKPI">
        <ul class="nav flex-column nav-flex-column">
            <li class="nav-item"><a class="nav-link <?php echo is_active('laporan_kinerja_dokter.php', $current_page); ?>" href="laporan_kinerja_dokter.php"><i class="fas fa-user-md me-2" style="width: 20px;"></i> Kinerja Dokter</a></li>
            <li class="nav-item"><a class="nav-link <?php echo is_active('laporan_operasi_view.php', $current_page); ?>" href="laporan_operasi_view.php"><i class="fas fa-cut me-2" style="width: 20px;"></i> Laporan Operasi</a></li>
        </ul>
    </div>

    <?php $grp_farmasi = ['laporan_stok_farmasi.php', 'laporan_stok_opname.php', 'laporan_proyeksi_keuntungan.php']; ?>
    <div class="sidebar-group-header <?php echo get_arrow_class($grp_farmasi, $current_page); ?>" data-bs-toggle="collapse" data-bs-target="#menuFarmasi">
        <span>Manajemen Farmasi</span> <i class="fas fa-chevron-down"></i>
    </div>
    <div class="collapse <?php echo get_collapse_class($grp_farmasi, $current_page); ?>" id="menuFarmasi">
        <ul class="nav flex-column nav-flex-column">
            <li class="nav-item"><a class="nav-link <?php echo is_active('laporan_stok_farmasi.php', $current_page); ?>" href="laporan_stok_farmasi.php"><i class="fas fa-capsules me-2" style="width: 20px;"></i> Monitoring Stok</a></li>
            <li class="nav-item"><a class="nav-link <?php echo is_active('laporan_stok_opname.php', $current_page); ?>" href="laporan_stok_opname.php"><i class="fas fa-clipboard-check me-2" style="width: 20px;"></i> Stok Opname</a></li>
            <li class="nav-item"><a class="nav-link <?php echo is_active('laporan_proyeksi_keuntungan.php', $current_page); ?>" href="laporan_proyeksi_keuntungan.php"><i class="fas fa-chart-pie me-2" style="width: 20px;"></i> Profit Farmasi</a></li>
        </ul>
    </div>
	
	
	<?php $grp_erm = ['laporan_audit_erm_full.php', 'laporan_audit_erm_full.php']; ?>
    <div class="sidebar-group-header <?php echo get_arrow_class($grp_erm, $current_page); ?>" data-bs-toggle="collapse" data-bs-target="#menuAuditERM">
        <span>Kelengkapan ERM</span> <i class="fas fa-chevron-down"></i>
    </div>
    <div class="collapse <?php echo get_collapse_class($grp_erm, $current_page); ?>" id="menuAuditERM">
        <ul class="nav flex-column nav-flex-column">
			<li class="nav-item"> <a class="nav-link <?php echo is_active('laporan_audit_erm_full.php', $current_page); ?>" href="laporan_audit_erm_full.php"><i class="fas fa-check-double me-2" style="width: 20px;"></i> Audit Kepatuhan ERM</a></li>
            </a></li>
        </ul>
    </div>
	
	
	

    <?php $grp_sys = ['laporan_audit_trail.php']; ?>
    <div class="sidebar-group-header <?php echo get_arrow_class($grp_sys, $current_page); ?>" data-bs-toggle="collapse" data-bs-target="#menuSystem">
        <span>System & Utility</span> <i class="fas fa-chevron-down"></i>
    </div>
    <div class="collapse <?php echo get_collapse_class($grp_sys, $current_page); ?>" id="menuSystem">
        <ul class="nav flex-column nav-flex-column">
            <li class="nav-item"><a class="nav-link <?php echo is_active('laporan_audit_trail.php', $current_page); ?>" href="laporan_audit_trail.php"><i class="fas fa-shield-alt me-2" style="width: 20px;"></i> Audit Trail</a></li>
        </ul>
    </div>
	
	
	<?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'Super Admin') { ?>
    <div class="collapse <?php echo get_collapse_class($grp_sys, $current_page); ?>" id="menuSystem">
		<ul class="nav flex-column nav-flex-column">
			<li class="nav-item"><a class="nav-link <?php echo is_active('manage_users.php', $current_page); ?>" href="manage_users.php"><i class="fas fa-users-cog" style="width: 20px;"></i> Manage Users</a></li>
		</ul>
	</div>
	<?php } ?>
	

    <br><br>
  </div>
</nav>

<main>
    <div class="container-fluid">

<script>
    // Script untuk Sidebar Toggle & Persistence
    document.addEventListener("DOMContentLoaded", function() {
        const body = document.body;
        const toggleDesktop = document.getElementById('sidebarToggleDesktop');
        const toggleMobile = document.getElementById('sidebarToggleMobile');
        const overlay = document.getElementById('mobileOverlay');

        // 1. Cek Preferensi User (LocalStorage)
        const savedState = localStorage.getItem('sidebarState');
        if (savedState === 'closed') {
            body.classList.add('sidebar-closed');
        }

        // 2. Toggle Desktop
        if(toggleDesktop) {
            toggleDesktop.addEventListener('click', function() {
                body.classList.toggle('sidebar-closed');
                // Simpan state
                if (body.classList.contains('sidebar-closed')) {
                    localStorage.setItem('sidebarState', 'closed');
                } else {
                    localStorage.setItem('sidebarState', 'open');
                }
            });
        }

        // 3. Toggle Mobile
        if(toggleMobile) {
            toggleMobile.addEventListener('click', function() {
                body.classList.toggle('sidebar-open');
            });
        }

        // 4. Tutup Sidebar Mobile saat klik overlay
        if(overlay) {
            overlay.addEventListener('click', function() {
                body.classList.remove('sidebar-open');
            });
        }
    });
</script>