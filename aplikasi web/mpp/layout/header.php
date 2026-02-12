<?php
// File: layout/header.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$root_path = dirname(__DIR__); 
require_once $root_path . '/config/config.php';
require_once $root_path . '/config/database.php';

// Data Instansi
$nama_rs = 'MPP Dashboard';
$logo_b64 = 'https://via.placeholder.com/50';
try {
    $stmt = $pdo->prepare("SELECT nama_instansi, logo FROM setting LIMIT 1");
    $stmt->execute();
    $instansi = $stmt->fetch();
    if ($instansi) {
        $nama_rs = $instansi['nama_instansi'];
        if (!empty($instansi['logo'])) {
            $logo_b64 = 'data:image/jpeg;base64,' . base64_encode($instansi['logo']);
        }
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MPP - <?= $nama_rs ?></title>
    <link rel="icon" type="image/x-icon" href="<?= $logo_b64 ?>">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; overflow-x: hidden; }
        
        /* --- LAYOUT STRUCTURE --- */
        .wrapper { display: flex; width: 100%; align-items: stretch; min-height: 100vh; }
        
        /* NAVBAR */
        .main-header { 
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            z-index: 1030;
            padding: 0.5rem 1rem;
        }

        /* SIDEBAR BASE */
        #sidebar {
            min-width: 260px; 
            max-width: 260px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); /* Dark Slate */
            color: #94a3b8;
            transition: all 0.3s ease-in-out;
            z-index: 1040;
            display: flex;
            flex-direction: column;
        }

        /* Sidebar Header */
        #sidebar .sidebar-header { 
            padding: 20px; 
            background: rgba(0,0,0,0.1); 
            border-bottom: 1px solid rgba(255,255,255,0.05); 
        }

        /* Sidebar Links */
        #sidebar ul.components { padding: 15px 10px; flex: 1; }
        #sidebar ul li a { 
            padding: 12px 15px; 
            font-size: 0.9rem; 
            display: flex; align-items: center;
            color: #cbd5e1; 
            text-decoration: none; 
            border-radius: 8px;
            margin-bottom: 4px;
            transition: all 0.2s;
        }
        #sidebar ul li a:hover { color: #fff; background: rgba(255,255,255,0.1); transform: translateX(5px); }
        #sidebar ul li a.active { background: #3b82f6; color: #fff; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4); }
        #sidebar ul li a i { margin-right: 12px; width: 20px; text-align: center; }

        /* Dropdown Submenu */
        #sidebar ul li a[data-bs-toggle="collapse"] { position: relative; }
        #sidebar ul li a[data-bs-toggle="collapse"]::after {
            content: "\f107"; font-family: "Font Awesome 6 Free"; font-weight: 900;
            position: absolute; right: 15px; transition: transform 0.3s;
        }
        #sidebar ul li a[aria-expanded="true"]::after { transform: rotate(-180deg); }
        #sidebar ul ul a { font-size: 0.85em !important; padding-left: 48px !important; background: rgba(0,0,0,0.2); }

        /* CONTENT AREA */
        #content { 
            width: 100%; 
            padding: 20px; 
            min-height: 100vh; 
            transition: all 0.3s; 
            display: flex;
            flex-direction: column;
        }

        /* --- RESPONSIVE LOGIC --- */

        /* DESKTOP MODE (> 768px) */
        @media (min-width: 769px) {
            /* Sidebar nempel di kiri, konten di kanan */
            #sidebar { margin-left: 0; }
            #sidebar.active { margin-left: -260px; } /* Hide Sidebar */
            
            .overlay { display: none !important; } /* Tidak butuh overlay di desktop */
        }

        /* MOBILE MODE (< 768px) */
        @media (max-width: 768px) {
            /* Sidebar default hidden (off-canvas) */
            #sidebar {
                margin-left: -260px;
                position: fixed; /* Melayang di atas konten */
                height: 100vh;
                top: 0; left: 0;
                box-shadow: 5px 0 15px rgba(0,0,0,0.3);
            }
            /* Show Sidebar */
            #sidebar.active { margin-left: 0; }
            
            /* Overlay Hitam */
            .overlay {
                display: none;
                position: fixed;
                width: 100vw; height: 100vh;
                background: rgba(0, 0, 0, 0.6);
                z-index: 1035; /* Di bawah sidebar, di atas konten */
                opacity: 0;
                transition: all 0.3s ease-in-out;
                top: 0; left: 0;
            }
            .overlay.active { display: block; opacity: 1; }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="overlay" id="mobileOverlay"></div>