<?php
/*
 * File: index.php (REVISI V2)
 * - Added: Fetch Nama Instansi dari Database.
 * - Added: Favicon & Logo (mengarah ke core/logo.php).
 * - UI: Layout dipercantik dengan logo di tengah.
 */

// 1. Koneksi Database (Hanya untuk ambil Nama RS)
// Menggunakan require_once agar jika file tidak ada, script berhenti (safety)
// Pastikan path 'config/koneksi.php' sesuai dengan struktur folder Anda.
if (file_exists('config/koneksi.php')) {
    require_once('config/koneksi.php');
}

$nama_instansi = "Rumah Sakit"; // Default fallback jika DB gagal
if (isset($koneksi)) {
    $sql_setting = "SELECT nama_instansi FROM setting LIMIT 1";
    $result_setting = $koneksi->query($sql_setting);
    if ($result_setting && $result_setting->num_rows > 0) {
        $row_setting = $result_setting->fetch_assoc();
        $nama_instansi = htmlspecialchars($row_setting['nama_instansi']);
    }
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - <?php echo $nama_instansi; ?></title>
    
    <link rel="icon" href="core/logo.php" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f0f2f5; /* Warna background sedikit lebih gelap agar kartu menonjol */
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            border: none;
            border-radius: 10px; /* Sudut lebih bulat */
        }
        .login-logo {
            max-width: 80px;
            height: auto;
            margin-bottom: 15px;
            filter: drop-shadow(0px 4px 4px rgba(0, 0, 0, 0.1));
        }
        .btn-primary {
            background-color: #0d6efd;
            padding: 10px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="card login-card shadow">
    <div class="card-body p-4">
        
        <div class="text-center mb-4">
            <img src="core/logo.php" alt="Logo RS" class="login-logo">
            <h5 class="fw-bold text-dark mb-1"><?php echo $nama_instansi; ?></h5>
            <span class="text-muted small text-uppercase ls-1">Dashboard Eksekutif</span>
        </div>
        
        <hr class="my-4">

        <form action="core/login_process.php" method="POST">
            
            <?php
            // Menampilkan pesan error jika login gagal
            if (isset($_GET['error'])) {
                echo '<div class="alert alert-danger text-center p-2 small" role="alert">Username atau Password salah!</div>';
            }
            ?>

            <div class="mb-3">
                <label for="username" class="form-label small fw-bold text-secondary">Username / NIP</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Masukan ID Pengguna" required autofocus>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label small fw-bold text-secondary">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Masukan Kata Sandi" required>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-primary shadow-sm">
                    <i class="fas fa-sign-in-alt me-2"></i> Masuk Aplikasi
                </button>
            </div>
            
            <div class="text-center mt-4">
                <small class="text-muted">&copy; <?php echo date('Y'); ?> SIMKES Khanza Dashboard</small>
            </div>
        </form>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>