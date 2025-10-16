<?php
/*
 * ===================================================================================
 * HALAMAN LOGIN
 * ===================================================================================
 * Modifikasi: Kini dapat mengenali peran dokter Radiologi (RAD) dan 
 * dokter Patologi Klinis (PK).
 */

require_once 'config.php';

// Jika user sudah login, arahkan ke router utama (index.php)
if (is_user_authorized()) {
    header('Location: index.php');
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = 'Username dan password tidak boleh kosong.';
    } else {
        try {
            $pdo = connect_db();
            $sql = "SELECT AES_DECRYPT(u.id_user, 'nur') as id_user, AES_DECRYPT(u.password, 'windi') as password FROM user u WHERE AES_DECRYPT(u.id_user, 'nur') = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && $user['password'] === $password) {
                // --- MODIFIKASI: Cek spesialisasi dokter (Radiologi atau Lab) ---
                $sql_check_spesialis = "SELECT nm_dokter, kd_sps FROM dokter WHERE kd_dokter = :kd_dokter AND kd_sps IN ('RAD', 'LAB')";
                $stmt_check = $pdo->prepare($sql_check_spesialis);
                $stmt_check->execute([':kd_dokter' => $user['id_user']]);
                $doctor = $stmt_check->fetch();

                if ($doctor) {
                    $settings_stmt = $pdo->query("SELECT nama_instansi, logo FROM setting LIMIT 1");
                    $settings = $settings_stmt->fetch();

                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id_user'];
                    $_SESSION['user_name'] = $doctor['nm_dokter'];
                    
                    // Set user role berdasarkan spesialisasi
                    if ($doctor['kd_sps'] == 'RAD') {
                        $_SESSION['user_role'] = 'radiologi';
                    } elseif ($doctor['kd_sps'] == 'LAB') {
                        $_SESSION['user_role'] = 'laboratorium';
                    }

                    $_SESSION['settings'] = [
                        'nama_instansi' => $settings['nama_instansi'] ?? 'Nama Instansi Tidak Ditemukan',
                        'logo_base64' => isset($settings['logo']) ? base64_encode($settings['logo']) : ''
                    ];

                    header("Location: index.php");
                    exit;
                } else {
                    $error_message = "Akses ditolak. Anda bukan dokter spesialis Radiologi atau Patologi Klinis.";
                }
            } else {
                $error_message = "Username atau password salah.";
            }
        } catch (\PDOException $e) {
            error_log("Login Gagal: " . $e->getMessage());
            $error_message = "Terjadi kesalahan pada sistem. Coba lagi nanti.";
        }
    }
}

// Ambil data instansi untuk halaman login
try {
    $pdo_login = connect_db();
    $settings_stmt_login = $pdo_login->query("SELECT nama_instansi, logo FROM setting LIMIT 1");
    $settings_login = $settings_stmt_login->fetch();
    $nama_instansi = $settings_login['nama_instansi'] ?? 'Expertise Web App';
    $logo_base64 = isset($settings_login['logo']) ? base64_encode($settings_login['logo']) : '';
} catch (\PDOException $e) {
    $nama_instansi = 'Expertise Web App';
    $logo_base64 = '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Log in | Expertise App</title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="login-logo">
    <a href="#"><b>Expertise</b>App</a>
  </div>
  <div class="card">
    <div class="card-body login-card-body">
      <div class="text-center mb-4">
          <?php if ($logo_base64): ?>
            <img src="data:image/png;base64,<?php echo $logo_base64; ?>" alt="Logo" style="max-height: 80px;" class="mb-2">
          <?php endif; ?>
          <p class="login-box-msg"><?php echo e($nama_instansi); ?></p>
      </div>

      <?php if ($error_message): ?>
          <div class="alert alert-danger text-center"><?php echo e($error_message); ?></div>
      <?php endif; ?>

      <form action="login.php" method="post">
        <div class="input-group mb-3">
          <input type="text" class="form-control" placeholder="Username (Kode Dokter)" name="username" required autofocus>
          <div class="input-group-append"><div class="input-group-text"><span class="fas fa-user"></span></div></div>
        </div>
        <div class="input-group mb-3">
          <input type="password" class="form-control" placeholder="Password" name="password" required>
          <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
        </div>
        <div class="row"><div class="col-12"><button type="submit" class="btn btn-primary btn-block">Sign In</button></div></div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>

