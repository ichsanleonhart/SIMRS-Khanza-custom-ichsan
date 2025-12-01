<?php
/*
 * ==================================================================
 * LOGIN.PHP (PENGEMBANGAN APLIKASI PENGAJUAN ASET - PERBAIKAN BUG)
 * ==================================================================
 * Halaman login ini menangani 3 role:
 * 1. Direktur (jbtn LIKE "Direktur%")
 * 2. Logistik Umum (user.ipsrs_barang = 'true')
 * 3. Pengaju (user.pengajuan_biaya = 'true')
 *
 * PERBAIKAN:
 * - Menggunakan stripos() agar pengecekan "Direktur" tidak case-sensitive.
 *
 * Dibuat kompatibel dengan PHP 7.3
 */

session_start();

// Komentar: Jika user sudah login, langsung lempar ke index.php
if (isset($_SESSION['nik_pengajuan_asset'])) {
    header('Location: index.php');
    exit;
}

// Komentar: Memanggil file konfigurasi lokal
include 'config_pengajuan_asset.php'; 
$konektor = bukakoneksi();

$error = '';
$nama_instansi = "Login";
$logo_path = "logo.php?v=logo";

// Komentar: Mengambil nama instansi dan logo dari tabel 'setting' untuk branding
try {
    // Komentar: Query tanpa alias
    $rs_settings_sql = "SELECT setting.nama_instansi, setting.logo FROM setting LIMIT 1";
    $rs_result = mysqli_query($konektor, $rs_settings_sql);
    if ($rs_row = mysqli_fetch_assoc($rs_result)) {
        $nama_instansi = $rs_row['nama_instansi'];
    }
} catch (Exception $e) {
    // Biarkan default jika query gagal
}

// Komentar: Memproses form saat di-submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Username dan password tidak boleh kosong.';
    } else {
        $is_valid = false;
        $role_login = '';
        $nik_login = '';
        $nama_login = '';
        $jbtn_login = '';
        $dep_login = '';

        // ==========================================================
        // 1. Cek Super Admin (dari tabel 'admin')
        // ==========================================================
        // Komentar: Query tanpa alias
        $stmt_admin = mysqli_prepare($konektor, "SELECT admin.usere FROM admin WHERE AES_DECRYPT(admin.usere, 'nur') = ? AND AES_DECRYPT(admin.passworde, 'windi') = ?");
        mysqli_stmt_bind_param($stmt_admin, "ss", $username, $password);
        mysqli_stmt_execute($stmt_admin);
        $result_admin = mysqli_stmt_get_result($stmt_admin);

        if (mysqli_num_rows($result_admin) > 0) {
            $is_valid = true;
            $nik_login = $username;
            $nama_login = "Admin Utama";
            
            // Komentar: Admin Utama diasumsikan memiliki semua hak. Kita cek jabatan aslinya.
            // Komentar: Query tanpa alias
            $stmt_pegawai = mysqli_prepare($konektor, "SELECT pegawai.nama, pegawai.jbtn, pegawai.departemen FROM pegawai WHERE pegawai.nik = ?");
            mysqli_stmt_bind_param($stmt_pegawai, "s", $nik_login);
            mysqli_stmt_execute($stmt_pegawai);
            $result_pegawai = mysqli_stmt_get_result($stmt_pegawai);
            if ($row_pegawai = mysqli_fetch_assoc($result_pegawai)) {
                $nama_login = $row_pegawai['nama'];
                $jbtn_login = $row_pegawai['jbtn'];
                $dep_login = $row_pegawai['departemen'];
            }
            mysqli_stmt_close($stmt_pegawai);
            
            // Komentar: Pengecekan role untuk Admin
            // [PERBAIKAN] Menggunakan stripos() agar case-insensitive (misal: 'DIREKTUR' vs 'Direktur')
            if (stripos($jbtn_login, 'Direktur') === 0) {
                $role_login = 'direktur';
            } else {
                // Admin non-Direktur diasumsikan sebagai Logum jika punya hak, jika tidak sebagai Pengaju
                // Komentar: Query tanpa alias
                $stmt_hak = mysqli_prepare($konektor, "SELECT user.ipsrs_barang, user.pengajuan_biaya FROM user WHERE AES_DECRYPT(user.id_user, 'nur') = ?");
                mysqli_stmt_bind_param($stmt_hak, "s", $nik_login);
                mysqli_stmt_execute($stmt_hak);
                $result_hak = mysqli_stmt_get_result($stmt_hak);
                if($row_hak = mysqli_fetch_assoc($result_hak)) {
                    if ($row_hak['ipsrs_barang'] === 'true') {
                        $role_login = 'logum';
                    } elseif ($row_hak['pengajuan_biaya'] === 'true') {
                        $role_login = 'pengaju';
                    }
                }
                mysqli_stmt_close($stmt_hak);
            }
            // Jika admin tidak punya salah satu hak di atas, beri default
            if(empty($role_login)) $role_login = 'pengaju'; 
            
        }
        mysqli_stmt_close($stmt_admin);

        // ==========================================================
        // 2. Jika bukan admin, cek User (dari tabel 'user')
        // ==========================================================
        if (!$is_valid) {
            // Komentar: Query ini mengambil data user dan data pegawai sekaligus
            // Kita tidak menggunakan alias tabel sesuai permintaan Anda (Poin 2)
            $sql_user = "
                SELECT 
                    AES_DECRYPT(user.id_user, 'nur') as id_user, 
                    AES_DECRYPT(user.password, 'windi') as password, 
                    user.pengajuan_biaya, 
                    user.ipsrs_barang,
                    pegawai.nama,
                    pegawai.jbtn,
                    pegawai.departemen
                FROM user 
                INNER JOIN pegawai ON AES_DECRYPT(user.id_user, 'nur') = pegawai.nik
                WHERE AES_DECRYPT(user.id_user, 'nur') = ?
            ";
            
            $stmt_user = mysqli_prepare($konektor, $sql_user);
            mysqli_stmt_bind_param($stmt_user, "s", $username);
            mysqli_stmt_execute($stmt_user);
            $result_user = mysqli_stmt_get_result($stmt_user);

            if ($row = mysqli_fetch_assoc($result_user)) {
                // Verifikasi password
                if ($row['password'] === $password) {
                    
                    // Komentar: Penentuan Role berdasarkan prioritas (Direktur > Logum > Pengaju)
                    // [PERBAIKAN] Menggunakan stripos() agar case-insensitive (misal: 'DIREKTUR' vs 'Direktur')
                    // Ini adalah prioritas pertama (Sesuai Poin 3.8)
                    if (stripos($row['jbtn'], 'Direktur') === 0) {
                        $role_login = 'direktur';
                        $is_valid = true;
                    // Sesuai Poin 3.4
                    } elseif ($row['ipsrs_barang'] === 'true') {
                        $role_login = 'logum';
                        $is_valid = true;
                    // Sesuai Poin 3.3
                    } elseif ($row['pengajuan_biaya'] === 'true') {
                        $role_login = 'pengaju';
                        $is_valid = true;
                    }

                    if ($is_valid) {
                        $nik_login = $row['id_user'];
                        $nama_login = $row['nama'];
                        $jbtn_login = $row['jbtn'];
                        $dep_login = $row['departemen'];
                    } else {
                        $error = 'Anda tidak memiliki hak akses untuk fitur ini.';
                    }
                } else {
                    $error = 'Password salah.';
                }
            } else {
                $error = 'Username tidak ditemukan di tabel user atau pegawai.';
            }
            mysqli_stmt_close($stmt_user);
        }

        // ==========================================================
        // 3. Jika login berhasil, set session
        // ==========================================================
        if ($is_valid && !empty($role_login)) {
            session_regenerate_id(true); // Mencegah session fixation
            $_SESSION['nik_pengajuan_asset'] = $nik_login;
            $_SESSION['nama_pengajuan_asset'] = $nama_login;
            $_SESSION['role_pengajuan_asset'] = $role_login;
            $_SESSION['jbtn_pengajuan_asset'] = $jbtn_login;
            $_SESSION['departemen_pengajuan_asset'] = $dep_login;
            
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
    <title>Login - Pengajuan Aset | <?php echo htmlspecialchars($nama_instansi, ENT_QUOTES, 'UTF-8'); ?></title>
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
        <p>Silakan login untuk mengakses Modul Pengajuan Aset</p>

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