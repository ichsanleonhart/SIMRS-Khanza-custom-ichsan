<?php
// Pastikan session start
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['login_user'])) {
    header("Location: " . BASE_URL . "/modul/auth/login.php");
    exit();
}

// Ambil Nama Instansi jika belum ada di session (untuk efisiensi)
if (!isset($_SESSION['nama_instansi'])) {
    require_once __DIR__ . '/../config/database.php'; // Pastikan path benar
    $stmt_set = $pdo->query("SELECT nama_instansi FROM setting LIMIT 1");
    $setting = $stmt_set->fetch();
    $_SESSION['nama_instansi'] = $setting['nama_instansi'] ?? 'Rumah Sakit';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= isset($title) ? $title : 'e-Dokter' ?> | <?= $_SESSION['nama_instansi'] ?></title>

  <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/image_load.php">

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css">

  <style>
      /* Tweaks */
      .main-header { border-bottom: 1px solid #dee2e6; }
      .brand-link .brand-image { float: left; line-height: .8; margin-left: .8rem; margin-right: .5rem; margin-top: -3px; max-height: 33px; width: auto; }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <span class="nav-link font-weight-bold"><?= $_SESSION['nama_instansi'] ?></span>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modul/auth/logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i> Keluar
            </a>
        </li>
    </ul>
  </nav>