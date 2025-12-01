<?php
session_start();
// Jika sudah login, redirect ke index.php
if (isset($_SESSION['username_pengajuan'])) {
    header('Location: index.php');
    exit;
}

include '../conf/conf.php'; // Menggunakan file conf.php dari direktori induk
$konektor = bukakoneksi();

$error = '';

// Mengambil pengaturan rumah sakit untuk branding halaman login
$nama_instansi = "Login";
$logo_path = "logo.php?v=logo";
try {
    $rs_settings_sql = "SELECT nama_instansi, logo FROM setting LIMIT 1";
    $rs_result = mysqli_query($konektor, $rs_settings_sql);
    if ($rs_row = mysqli_fetch_assoc($rs_result)) {
        $nama_instansi = $rs_row['nama_instansi'];
    }
} catch (Exception $e) {
    // Biarkan default jika query gagal
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Username dan password tidak boleh kosong.';
    } else {
        $is_valid = false;
        $nama_user = '';
        $is_admin = false;
        $nik_user = '';

        // 1. Cek Super Admin (dari tabel 'admin')
        $stmt_admin = mysqli_prepare($konektor, "SELECT usere FROM admin WHERE AES_DECRYPT(usere, 'nur') = ? AND AES_DECRYPT(passworde, 'windi') = ?");
        mysqli_stmt_bind_param($stmt_admin, "ss", $username, $password);
        mysqli_stmt_execute($stmt_admin);
        $result_admin = mysqli_stmt_get_result($stmt_admin);

        if (mysqli_num_rows($result_admin) > 0) {
            $is_valid = true;
            $is_admin = true;
            $nik_user = $username; // Admin utama menggunakan username sebagai NIK
            // Coba cari nama dari tabel pegawai
            $stmt_pegawai = mysqli_prepare($konektor, "SELECT nama FROM pegawai WHERE nik = ?");
            mysqli_stmt_bind_param($stmt_pegawai, "s", $username);
            mysqli_stmt_execute($stmt_pegawai);
            $result_pegawai = mysqli_stmt_get_result($stmt_pegawai);
            if ($row_pegawai = mysqli_fetch_assoc($result_pegawai)) {
                $nama_user = $row_pegawai['nama'];
            } else {
                $nama_user = "Admin Utama";
            }
            mysqli_stmt_close($stmt_pegawai);
        }
        mysqli_stmt_close($stmt_admin);

        // 2. Jika bukan admin, cek User (dari tabel 'user')
        if (!$is_valid) {
            $sql_user = "SELECT AES_DECRYPT(u.id_user, 'nur') as id_user, AES_DECRYPT(u.password, 'windi') as password, u.pengajuan_biaya, p.nama 
                         FROM user u 
                         LEFT JOIN pegawai p ON AES_DECRYPT(u.id_user, 'nur') = p.nik
                         WHERE AES_DECRYPT(u.id_user, 'nur') = ?";
            
            $stmt_user = mysqli_prepare($konektor, $sql_user);
            mysqli_stmt_bind_param($stmt_user, "s", $username);
            mysqli_stmt_execute($stmt_user);
            $result_user = mysqli_stmt_get_result($stmt_user);

            if ($row = mysqli_fetch_assoc($result_user)) {
                // Verifikasi password
                if ($row['password'] === $password) {
                    // Verifikasi hak akses
                    if ($row['pengajuan_biaya'] === 'true') {
                        $is_valid = true;
                        $is_admin = false;
                        $nik_user = $row['id_user'];
                        $nama_user = $row['nama'];
                    } else {
                        $error = 'Anda tidak memiliki hak akses untuk fitur ini.';
                    }
                } else {
                    $error = 'Password salah.';
                }
            } else {
                $error = 'Username tidak ditemukan.';
            }
            mysqli_stmt_close($stmt_user);
        }

        // 3. Jika login berhasil, set session
        if ($is_valid) {
            session_regenerate_id(true); // Mencegah session fixation
            $_SESSION['username_pengajuan'] = $nik_user;
            $_SESSION['nama_pengajuan'] = $nama_user;
            $_SESSION['is_admin_pengajuan'] = $is_admin;
            
            // Mengambil data bidang dan departemen
            $stmt_detail = mysqli_prepare($konektor, "SELECT bidang, departemen FROM pegawai WHERE nik = ?");
            mysqli_stmt_bind_param($stmt_detail, "s", $nik_user);
            mysqli_stmt_execute($stmt_detail);
            $result_detail = mysqli_stmt_get_result($stmt_detail);
            if($row_detail = mysqli_fetch_assoc($result_detail)) {
                $_SESSION['bidang_pengajuan'] = $row_detail['bidang'];
                $_SESSION['departemen_pengajuan'] = $row_detail['departemen'];
            }
            mysqli_stmt_close($stmt_detail);

            header('Location: index.php');
            exit;
        } else {
            if (empty($error)) {
                $error = 'Username atau password salah.';
            }
        }
    }
}
mysqli_close($konektor);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pengajuan Biaya | <?php echo htmlspecialchars($nama_instansi, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" href="logo.php?v=favicon" type="image/png">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background-color: #fff; padding: 2.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 350px; text-align: center; }
        .login-container img { width: 80px; height: 80px; margin-bottom: 1rem; }
        .login-container h2 { margin-bottom: 0.5rem; color: #333; }
        .login-container p { margin-bottom: 1.5rem; color: #666; font-size: 0.9rem; }
        .login-form input[type="text"], .login-form input[type="password"] { width: 100%; padding: 12px; margin-bottom: 1rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .login-form button { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; font-weight: bold; }
        .login-form button:hover { background-color: #0056b3; }
        .error { color: #d9534f; background-color: #f2dede; border: 1px solid #ebccd1; padding: 10px; border-radius: 4px; margin-bottom: 1rem; text-align: left; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="<?php echo $logo_path; ?>" alt="Logo Instansi">
        <h2><?php echo htmlspecialchars($nama_instansi, ENT_QUOTES, 'UTF-8'); ?></h2>
        <p>Silakan login untuk mengakses Modul Pengajuan Biaya</p>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="login-form">
            <div>
                <input type="text" name="username" placeholder="Username (NIP/NIK)" required>
            </div>
            <div>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>