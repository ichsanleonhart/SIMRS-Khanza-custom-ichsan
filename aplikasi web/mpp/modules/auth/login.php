<?php
// File: modules/auth/login.php
require_once '../../config/config.php';
require_once '../../config/database.php';

session_start();

// Jika sudah login, lempar ke dashboard
if (isset($_SESSION['is_login']) && $_SESSION['is_login'] === true) {
    header("Location: " . $base_url . "modules/dashboard/index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // --- SKENARIO 1: CEK TABEL USER (Pegawai RS) ---
    // Query tanpa alias sesuai request
    $stmt = $pdo->prepare("
        SELECT 
            AES_DECRYPT(user.id_user, 'nur') as dekripsi_id, 
            AES_DECRYPT(user.password, 'windi') as dekripsi_pass, 
            user.mpp_skrining
        FROM user 
        WHERE AES_DECRYPT(user.id_user, 'nur') = ?
    ");
    $stmt->execute([$username]);
    $user_data = $stmt->fetch();

    $login_sukses = false;
    $role = 'user';

    // Verifikasi User Biasa
    if ($user_data && $user_data['dekripsi_pass'] === $password) {
        // Cek Hak Akses MPP
        if ($user_data['mpp_skrining'] === 'true') {
            $login_sukses = true;
        } else {
            $error = "Login Gagal: Anda tidak memiliki hak akses MPP Skrining.";
        }
    } else {
        // --- SKENARIO 2: CEK TABEL ADMIN (Super Admin IT) ---
        // Jika gagal di tabel user, coba tabel admin
        $stmt_admin = $pdo->prepare("
            SELECT 
                AES_DECRYPT(admin.usere, 'nur') as dekripsi_user, 
                AES_DECRYPT(admin.passworde, 'windi') as dekripsi_pass
            FROM admin 
            WHERE AES_DECRYPT(admin.usere, 'nur') = ?
        ");
        $stmt_admin->execute([$username]);
        $admin_data = $stmt_admin->fetch();

        if ($admin_data && $admin_data['dekripsi_pass'] === $password) {
            $login_sukses = true;
            $role = 'superadmin';
        } else {
            if (empty($error)) $error = "Username atau Password salah.";
        }
    }

    if ($login_sukses) {
        // Regenerasi ID Session (Security Fix)
        session_regenerate_id(true);

        $_SESSION['is_login'] = true;
        $_SESSION['user_id'] = $username;
        $_SESSION['role'] = $role;
        
        // Simpan hak akses spesifik jika user biasa
        if ($role === 'user') {
            $_SESSION['hak_akses'] = [
                'mpp_skrining' => $user_data['mpp_skrining']
            ];
        }

        // --- CATAT KE TRACKER (Sesuai Request) ---
        // Mencatat nip, tgl_login, jam_login
        try {
            $tgl = date('Y-m-d');
            $jam = date('H:i:s');
            $stmt_track = $pdo->prepare("INSERT INTO tracker (nip, tgl_login, jam_login) VALUES (?, ?, ?)");
            $stmt_track->execute([$username, $tgl, $jam]);
        } catch (Exception $e) {
            // Silent error jika tracker gagal (misal duplikat primary key di detik yang sama)
        }

        header("Location: " . $base_url . "modules/dashboard/index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Dashboard MPP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-box { width: 100%; max-width: 400px; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .logo-box { text-align: center; margin-bottom: 20px; }
        .logo-box img { max-height: 80px; }
    </style>
</head>
<body>

<div class="login-box">
    <div class="logo-box">
        <h4>🔐 MPP System</h4>
        <small class="text-muted">Manajemen Pelayanan Pasien</small>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Username / NIP</label>
            <input type="text" name="username" class="form-control" required autofocus placeholder="Masukkan NIP Anda">
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required placeholder="Masukkan Password">
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
    
    <div class="text-center mt-3">
        <small class="text-muted">Versi 1.0 (Mobile Friendly)</small>
    </div>
</div>

</body>
</html>