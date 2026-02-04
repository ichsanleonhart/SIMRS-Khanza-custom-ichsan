<?php
// File: layout/header.php
// Pastikan koneksi database sudah tersedia dari file induk

// 1. Ambil Identitas Instansi (Logo & Nama)
// Query tanpa alias sesuai request
$stmt_instansi = $pdo->prepare("SELECT setting.nama_instansi, setting.logo FROM setting LIMIT 1");
$stmt_instansi->execute();
$instansi = $stmt_instansi->fetch();

$nama_rs = $instansi ? $instansi['nama_instansi'] : 'RS MPP Dashboard';
$logo_b64 = '';

// Konversi BLOB ke Base64 agar bisa tampil di img src
if ($instansi && !empty($instansi['logo'])) {
    $logo_b64 = 'data:image/jpeg;base64,' . base64_encode($instansi['logo']);
} else {
    // Fallback jika tidak ada logo (bisa pakai icon default)
    $logo_b64 = 'https://via.placeholder.com/50'; 
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MPP - <?= $nama_rs ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { font-family: 'Source Sans Pro', sans-serif; background-color: #f4f6f9; min-height: 100vh; display: flex; flex-direction: column; }
        
        /* Sidebar Styling ala AdminLTE tapi versi Native Bootstrap 5 */
        .sidebar { min-height: 100vh; background-color: #343a40; color: #c2c7d0; transition: all 0.3s; }
        .sidebar .nav-link { color: #c2c7d0; padding: 10px 20px; border-radius: 4px; margin-bottom: 2px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #007bff; color: white; }
        .sidebar .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
        .brand-link { display: block; padding: 15px 20px; border-bottom: 1px solid #4b545c; color: white; text-decoration: none; font-size: 1.1rem; }
        .brand-link:hover { color: white; }
        .user-panel { padding: 15px 20px; border-bottom: 1px solid #4b545c; display: flex; align-items: center; }
        
        /* Main Content Wrapper */
        .content-wrapper { flex: 1; padding: 20px; width: 100%; }
        
        /* Mobile Toggle */
        @media (max-width: 768px) {
            .sidebar { position: fixed; top: 0; left: -250px; z-index: 1045; width: 250px; height: 100%; }
            .sidebar.show { left: 0; }
            .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040; }
            .overlay.show { display: block; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand navbar-white navbar-light bg-white shadow-sm sticky-top">
    <div class="container-fluid">
        <button class="btn btn-link d-md-none me-2" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>

        <span class="navbar-brand d-none d-md-block fw-bold text-primary">
            Sistem MPP Terintegrasi
        </span>

        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-1"></i> 
                    <?= $_SESSION['user_id'] ?? 'User' ?> 
                    <span class="badge bg-success ms-1"><?= $_SESSION['role'] ?? 'Guest' ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                    <li><a class="dropdown-item" href="#"><i class="fas fa-key me-2"></i> Ganti Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../../modules/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<div class="d-flex" style="flex: 1; overflow: hidden;">
    <div class="overlay" id="sidebarOverlay"></div>