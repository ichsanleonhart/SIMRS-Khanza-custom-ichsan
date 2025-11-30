<?php
/*
 * File: /webapps/berkas_digital_perawatan/index.php
 * Fungsi: Halaman Login Khusus Aplikasi Berkas Digital
 */
session_start();

// Jika sudah login, langsung lempar ke dashboard
if (isset($_SESSION['casemix_login']) && $_SESSION['casemix_login'] === true) {
    header("Location: dashboard.php");
    exit;
}

require_once('../conf/conf.php');
$koneksi = bukakoneksi();

// Ambil Nama Instansi & Logo untuk Tampilan
$nama_instansi = "RS Khanza";
$q_set = mysqli_query($koneksi, "SELECT nama_instansi FROM setting LIMIT 1");
if ($r_set = mysqli_fetch_assoc($q_set)) {
    $nama_instansi = $r_set['nama_instansi'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Casemix - <?= $nama_instansi ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .login-header {
            background-color: #0d6efd;
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 2rem;
            text-align: center;
        }
        .logo-img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            background: white;
            border-radius: 50%;
            padding: 5px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="card login-card">
    <div class="login-header">
        <img src="logo.php" alt="Logo" class="logo-img">
        <h5 class="mb-0 fw-bold">Portal Berkas Digital</h5>
        <small><?= $nama_instansi ?></small>
    </div>
    <div class="card-body p-4">
        
        <?php if(isset($_GET['pesan'])): ?>
            <div class="alert alert-danger text-center small py-2">
                <?php 
                if($_GET['pesan'] == 'gagal') echo "Username atau Password Salah!";
                elseif($_GET['pesan'] == 'noaccess') echo "Anda tidak memiliki hak akses Casemix!";
                elseif($_GET['pesan'] == 'logout') echo "Berhasil Logout.";
                ?>
            </div>
        <?php endif; ?>

        <form action="login_check.php" method="POST">
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold">USERNAME / NIP</label>
                <input type="text" name="username" class="form-control form-control-lg" required autofocus placeholder="NIP Pegawai">
            </div>
            <div class="mb-4">
                <label class="form-label text-muted small fw-bold">PASSWORD</label>
                <input type="password" name="password" class="form-control form-control-lg" required placeholder="Password">
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-lg fw-bold shadow-sm">MASUK</button>
        </form>
    </div>
    <div class="card-footer text-center bg-white border-0 pb-4">
        <small class="text-muted">&copy; <?= date('Y') ?> SIMRS Khanza</small>
    </div>
</div>

</body>
</html>
<?php mysqli_close($koneksi); ?>