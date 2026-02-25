<?php
// File: modules/auth/login.php
require_once '../../config/config.php';
require_once '../../config/database.php';

session_start();

// Jika sudah login, lempar ke dashboard sesuai hak akses
if (isset($_SESSION['is_login']) && $_SESSION['is_login'] === true) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin') {
        header("Location: " . $base_url . "modules/dashboard/index.php");
        exit;
    }
    elseif (isset($_SESSION['hak_akses']['mpp_skrining']) && $_SESSION['hak_akses']['mpp_skrining'] === 'true') {
        header("Location: " . $base_url . "modules/dashboard/index.php");
        exit;
    }
    elseif (isset($_SESSION['hak_akses']['soap_perawatan']) && $_SESSION['hak_akses']['soap_perawatan'] === 'true') {
        header("Location: " . $base_url . "modules/edokter/index.php");
        exit;
    }
    else {
        // Hancurkan session jika tidak punya hak sama sekali
        session_destroy();
        header("Location: " . $base_url . "modules/auth/login.php?error=no_access");
        exit;
    }
}

$error = '';
if (isset($_GET['error']) && $_GET['error'] === 'no_access') {
    $error = "Akses Ditolak: Anda tidak memiliki hak akses di sistem ini.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // --- SKENARIO 1: CEK TABEL USER (Pegawai RS) ---
    $stmt = $pdo->prepare("
        SELECT 
            AES_DECRYPT(user.id_user, 'nur') as dekripsi_id, 
            AES_DECRYPT(user.password, 'windi') as dekripsi_pass, 
            user.mpp_skrining,
			user.soap_perawatan
        FROM user 
        WHERE AES_DECRYPT(user.id_user, 'nur') = ?
    ");
    $stmt->execute([$username]);
    $user_data = $stmt->fetch();

    $login_sukses = false;
    $role = 'user';

    // Verifikasi User Biasa
    if ($user_data && $user_data['dekripsi_pass'] === $password) {

        // --- GATEKEEPER: Cek keberadaan user di tabel roles ---
        $stmt_role = $pdo->prepare("SELECT role FROM roles WHERE username = ?");
        $stmt_role->execute([$username]);
        $role_data = $stmt_role->fetch();

        if ($role_data) {
            // --- GATEKEEPER MINIMAL HAK AKSES ---
            if ($user_data['mpp_skrining'] !== 'true' && $user_data['soap_perawatan'] !== 'true') {
                $error = "Login Gagal: Anda tidak memiliki hak akses MPP maupun E-Dokter.";
            }
            else {
                $login_sukses = true;
            }
        }
        else {
            $error = "Login Gagal: Akun Anda belum terdaftar sebagai pengguna portal MPP/E-Dokter.";
        }

    }
    else {
        // --- SKENARIO 2: CEK TABEL ADMIN (Super Admin IT) ---
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
        }
        else {
            if (empty($error))
                $error = "Username atau Password salah.";
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
                'mpp_skrining' => $user_data['mpp_skrining'],
                'soap_perawatan' => $user_data['soap_perawatan']
            ];
        }

        // --- CATAT KE TRACKER ---
        try {
            $tgl = date('Y-m-d');
            $jam = date('H:i:s');
            $pdo->prepare("INSERT INTO tracker (nip, tgl_login, jam_login) VALUES (?, ?, ?)")->execute([$username, $tgl, $jam]);
        }
        catch (Exception $e) {
        }

        // --- SMART REDIRECT ---
        // Lempar ke halaman yang sesuai dengan hak aksesnya
        if ($role === 'superadmin' || (isset($user_data['mpp_skrining']) && $user_data['mpp_skrining'] === 'true')) {
            header("Location: " . $base_url . "modules/dashboard/index.php");
        }
        elseif (isset($user_data['soap_perawatan']) && $user_data['soap_perawatan'] === 'true') {
            header("Location: " . $base_url . "modules/edokter/index.php");
        }
        else {
            // Fallback (jika role ada tapi tidak punya kedua hak akses tersebut)
            header("Location: " . $base_url . "modules/dashboard/index.php");
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Dashboard Terintegrasi</title>
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
        <h4>🔐 MPP & E-Dokter</h4>
        <small class="text-muted">Portal Terintegrasi RS</small>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?php echo $error; ?></div>
    <?php
endif; ?>

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
        <small class="text-muted">Versi 2.0 (Mobile Friendly)</small>
    </div>
</div>

</body>
</html>