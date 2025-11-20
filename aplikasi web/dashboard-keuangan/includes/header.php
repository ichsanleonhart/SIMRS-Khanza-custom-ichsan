<?php
/*
 * File header.php (LAYOUT SIDEBAR DINAMIS & FIX NAMA INSTANSI)
 */

require_once(dirname(__DIR__) . '/config/koneksi.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php'); 
    exit;
}

// Ambil data branding
$nama_instansi = "Dashboard RS"; 
$logo_src = "core/logo.php";

$sql_setting = "SELECT setting.nama_instansi, setting.logo FROM setting LIMIT 1";
$result_setting = $koneksi->query($sql_setting);
if ($result_setting && $result_setting->num_rows > 0) {
    $row_setting = $result_setting->fetch_assoc();
    $nama_instansi = htmlspecialchars($row_setting['nama_instansi']);
}

// Dapatkan nama file saat ini untuk menandai menu aktif
$current_page = basename($_SERVER['PHP_SELF']);
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
        }

        body {
            font-size: .875rem;
            overflow-x: hidden; /* Mencegah scroll horizontal saat animasi */
        }

        /* --- 1. HEADER STYLING --- */
        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
            font-size: 1rem;
            background-color: rgba(0, 0, 0, .25);
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .25);
            width: var(--sidebar-width);
            transition: width var(--transition-speed);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* --- 2. SIDEBAR STYLING (SLIDING) --- */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            width: var(--sidebar-width);
            transition: margin-left var(--transition-speed);
            background-color: #f8f9fa;
        }

        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }

        /* --- 3. MAIN CONTENT ADJUSTMENT --- */
        main {
            margin-left: var(--sidebar-width); /* Default ada margin kiri */
            transition: margin-left var(--transition-speed);
            padding-top: 20px;
            min-height: 100vh;
        }

        /* --- 4. STATE: SIDEBAR HIDDEN (Toggled) --- */
        /* Saat body punya class 'sidebar-closed', geser sidebar ke kiri luar layar */
        body.sidebar-closed .sidebar {
            margin-left: calc(var(--sidebar-width) * -1);
        }
        
        /* Saat sidebar tutup, konten utama memenuhi layar */
        body.sidebar-closed main {
            margin-left: 0;
        }

        /* Nav Link Styling */
        .nav-link {
            font-weight: 500;
            color: #333;
            padding: 10px 20px;
            border-radius: 0 25px 25px 0; /* Efek bulat di kanan */
            margin-right: 10px;
        }
        .nav-link:hover {
            color: #007bff;
            background-color: #e9ecef;
        }
        .nav-link.active {
            color: #fff;
            background-color: #007bff; /* Biru Bootstrap */
        }
        .sidebar-heading {
            font-size: .75rem;
            text-transform: uppercase;
            margin-top: 1.5rem;
        }

        /* Mobile Responsiveness */
        @media (max-width: 767.98px) {
            /* Di HP, defaultnya sidebar tertutup */
            .sidebar {
                margin-left: calc(var(--sidebar-width) * -1);
            }
            main {
                margin-left: 0;
            }
            /* Jika class 'sidebar-open' ada di HP, munculkan sidebar */
            body.sidebar-open .sidebar {
                margin-left: 0;
            }
            /* Overlay hitam saat sidebar buka di HP */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 99;
            }
            body.sidebar-open .sidebar-overlay {
                display: block;
            }
        }
    </style>
</head>
<body>

<header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
  <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="#">
      <img src="<?php echo $logo_src; ?>" alt="Logo" width="25" height="25" class="d-inline-block align-text-top me-2">
      <?php echo $nama_instansi; ?>
  </a>
  
  <button class="btn btn-dark d-none d-md-block ms-2" id="sidebarToggleDesktop">
      <i class="fas fa-bars"></i>
  </button>
  
  <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" id="sidebarToggleMobile" style="right: 10px; top: 10px;">
    <span class="navbar-toggler-icon"></span>
  </button>
  
  <div class="w-100"></div> <div class="navbar-nav">
    <div class="nav-item text-nowrap">
      <span class="nav-link px-3 text-white">Halo, <?php echo htmlspecialchars($_SESSION['nama_user']); ?></span>
    </div>
  </div>
  <div class="navbar-nav">
    <div class="nav-item text-nowrap">
      <a class="nav-link px-3 text-danger" href="core/logout.php"><i class="fas fa-sign-out-alt"></i> Sign out</a>
    </div>
  </div>
</header>

<div class="container-fluid">
  <div class="row">
    
    <div class="sidebar-overlay" id="mobileOverlay"></div>

    <nav id="sidebarMenu" class="sidebar bg-light">
      <div class="sidebar-sticky pt-3">
        <ul class="nav flex-column">
          
          <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
              <i class="fas fa-home me-2" style="width: 20px;"></i>
              Dashboard Utama
            </a>
          </li>
          
        </ul>
		
		<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
          <span>Kendali Biaya</span>
        </h6>
		<ul class="nav flex-column mb-2">
		  <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'kunjungan_belum_closing.php') ? 'active' : ''; ?>" href="kunjungan_belum_closing.php">
              <i class="fas fa-user-clock me-2" style="width: 20px;"></i>
              Kunjungan Aktif
            </a>
          </li>
		</ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
          <span>Laporan Keuangan</span>
        </h6>
        <ul class="nav flex-column mb-2">		
          <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'laporan_billing_global.php') ? 'active' : ''; ?>" href="laporan_billing_global.php">
              <i class="fas fa-file-invoice-dollar me-2" style="width: 20px;"></i>
              Detail Semua Billing
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'laporan_piutang_detail.php') ? 'active' : ''; ?>" href="laporan_piutang_detail.php">
              <i class="fas fa-file-invoice me-2" style="width: 20px;"></i>
              Laporan Piutang/Shift
            </a>
          </li>
		  <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'laporan_detail.php') ? 'active' : ''; ?>" href="laporan_detail.php">
              <i class="fas fa-cash-register me-2" style="width: 20px;"></i>
              Laporan Tunai/Shift
            </a>
          </li>
        </ul>
		
		<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
          <span>Statistik & Indikator</span>
        </h6>
		<ul class="nav flex-column mb-2">
		  <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'laporan_kunjungan.php') ? 'active' : ''; ?>" href="laporan_kunjungan.php">
              <i class="fas fa-users me-2" style="width: 20px;"></i>
              Kunjungan Ralan/Ranap
            </a>
          </li>
		  <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'laporan_indikator_ranap.php') ? 'active' : ''; ?>" href="laporan_indikator_ranap.php">
              <i class="fas fa-chart-line me-2" style="width: 20px;"></i>
              BOR LOS TOI NDR GDR
            </a>
          </li>
		  <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'laporan_penyakit.php') ? 'active' : ''; ?>" href="laporan_penyakit.php">
              <i class="fas fa-chart-line me-2" style="width: 20px;"></i>
              Laporan Penyakit
            </a>
          </li>
		</ul>
		
		<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
          <span>KPI</span>
        </h6>
		<ul class="nav flex-column mb-2">
		  <li class="nav-item">
			<a class="nav-link <?php echo ($current_page == 'laporan_kinerja_dokter.php') ? 'active' : ''; ?>" href="laporan_kinerja_dokter.php">
		  <i class="fas fa-user-md me-2" style="width: 20px;"></i>
			Kinerja Dokter
		    </a>
		  </li>
		</ul>
		
		
      </div>
    </nav>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 w-100">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
      </div>

<script>
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
                
                // Simpan status ke localStorage
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