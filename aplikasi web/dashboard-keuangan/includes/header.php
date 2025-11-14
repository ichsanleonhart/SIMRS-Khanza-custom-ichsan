<?php
/*
 * File header.php (PERBAIKAN CDN + MENU BARU)
 * Memuat CSS dari CDN dan menjalankan session check.
 */

// 1. WAJIB PANGGIL KONEKSI.PHP PERTAMA KALI
require_once(dirname(__DIR__) . '/config/koneksi.php');

// 2. Keamanan: Cek session SETELAH koneksi.php dipanggil
if (!isset($_SESSION['user_id'])) {
    // Sesuaikan path ini jika root folder Anda berbeda
    // (Misal: /nama_folder_proyek/index.php)
    header('Location: index.php'); 
    exit;
}

// 3. Ambil data branding (Nama RS & Logo)
$nama_instansi = "Dashboard RS"; 
$logo_src = "core/logo.php";

$sql_setting = "SELECT setting.nama_instansi, setting.logo FROM setting LIMIT 1";
$result_setting = $koneksi->query($sql_setting);

if ($result_setting && $result_setting->num_rows > 0) {
    $row_setting = $result_setting->fetch_assoc();
    $nama_instansi = htmlspecialchars($row_setting['nama_instansi']);
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?> - <?php echo $nama_instansi; ?></title>
    
    <link rel="icon" href="<?php echo $logo_src; ?>" type="image/png">

    <!-- Aset CSS sekarang dimuat dari CDN (Internet) -->
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    
    <!-- DataTables Bootstrap 5 CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- CSS Tambahan (opsional) untuk style KPI -->
    <style>
        .border-left-success { border-left: .25rem solid #198754 !important; }
        .border-left-danger { border-left: .25rem solid #dc3545 !important; }
        .border-left-info { border-left: .25rem solid #0dcaf0 !important; }
        .border-left-warning { border-left: .25rem solid #ffc107 !important; }
        .text-xs { font-size: .7rem; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">
      <img src="<?php echo $logo_src; ?>" alt="Logo" width="30" height="30" class="d-inline-block align-text-top">
      <?php echo $nama_instansi; ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto"> <!-- me-auto agar menu lain ke kanan -->
        <li class="nav-item">
          <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == 'dashboard.php') echo 'active'; ?>" href="dashboard.php">Dashboard</a>
        </li>
        <!-- ========================================================== -->
        <!-- KODE BARU: Tombol Menu Laporan Billing Global -->
        <!-- ========================================================== -->
        <li class="nav-item">
          <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == 'laporan_billing_global.php') echo 'active'; ?>" href="laporan_billing_global.php">Detail Semua Billing</a>
        </li>
        <!-- ========================================================== -->
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <span class="navbar-text me-3">
            Halo, <?php echo htmlspecialchars($_SESSION['nama_user']); ?>
          </span>
        </li>
        <li class="nav-item">
          <a class="btn btn-danger" href="core/logout.php">Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Konten utama akan dimulai di sini -->
<main class="container-fluid mt-4">