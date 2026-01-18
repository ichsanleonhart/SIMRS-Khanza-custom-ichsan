<?php
// modul/auth/login.php
session_start();
require_once '../../config/database.php';
require_once '../../config/fungsi.php';

if (isset($_SESSION['login_user'])) {
    redirect('../../index.php');
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_input = trim($_POST['username']);
    $password_input = trim($_POST['password']);

    try {
        // 1. CEK ADMIN UTAMA (Tabel admin)
        $sql_admin = "SELECT AES_DECRYPT(usere, 'nur') as usere 
                      FROM admin 
                      WHERE usere = AES_ENCRYPT(:user, 'nur') 
                      AND passworde = AES_ENCRYPT(:pass, 'windi')";
        
        $stmt = $pdo->prepare($sql_admin);
        $stmt->execute(['user' => $username_input, 'pass' => $password_input]);
        $admin = $stmt->fetch();

        if ($admin) {
            session_regenerate_id(true);
            $_SESSION['login_user'] = $admin['usere'];
            $_SESSION['role']       = 'admin'; // Admin otomatis bisa intip
            $_SESSION['nama']       = 'Administrator';
            $_SESSION['kd_dokter']  = ''; // Admin tidak punya kode dokter (FIX BUG DISINI)
            redirect('../../index.php');
        } else {
            // 2. CEK USER BIASA (Dokter/Pegawai)
            $sql_dokter = "SELECT AES_DECRYPT(u.id_user, 'nur') as nik, d.nm_dokter, d.kd_dokter 
                           FROM user u
                           LEFT JOIN dokter d ON AES_DECRYPT(u.id_user, 'nur') = d.kd_dokter
                           WHERE u.id_user = AES_ENCRYPT(:user, 'nur') 
                           AND u.password = AES_ENCRYPT(:pass, 'windi')";

            $stmt2 = $pdo->prepare($sql_dokter);
            $stmt2->execute(['user' => $username_input, 'pass' => $password_input]);
            $user = $stmt2->fetch();

            if ($user) {
                session_regenerate_id(true);
                $_SESSION['login_user'] = $user['nik'];
                
                // Cek apakah user ini ada di daftar VIP?
                $vip_users = get_vip_users(); // Dari fungsi.php
                
                if (in_array($user['nik'], $vip_users)) {
                    $_SESSION['role'] = 'super_dokter'; // HAK AKSES MENGINTIP AKTIF
                } else {
                    $_SESSION['role'] = 'dokter';
                }

                $_SESSION['nama']      = $user['nm_dokter'] ?? $user['nik'];
                $_SESSION['kd_dokter'] = $user['kd_dokter'] ?? ''; // Jika pegawai biasa, ini kosong
                
                redirect('../../index.php');
            } else {
                $error = "Username atau Password salah!";
            }
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login | e-Dokter</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="login-logo">
    <a href="#"><b>e-Dokter</b> Jasmed</a>
  </div>
  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Silakan login akun Khanza</p>
      <?php if($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endif; ?>
      <form action="" method="post">
        <div class="input-group mb-3">
          <input type="text" name="username" class="form-control" placeholder="Username" required autofocus>
          <div class="input-group-append"><div class="input-group-text"><span class="fas fa-user"></span></div></div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Password" required>
          <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>