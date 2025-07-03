<?php
session_start();
require 'koneksi.php'; // file koneksi database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    if (empty($email) || empty($password)) {
    echo "<script>alert('Email dan Password tidak boleh kosong.');window.location='login.php';</script>";
    exit;
}


    // Cari user berdasarkan email
    $stmt = $conn->prepare("SELECT id, nama, role, password_hash, status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $nama, $role, $password_hash, $status);
        $stmt->fetch();

        if ($status != 'active') {
            echo "<script>alert('Akun belum aktif. Hubungi admin.');window.location='login.php';</script>";
            exit;
        }

        if (password_verify($password, $password_hash)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['nama'] = $nama;
            $_SESSION['role'] = $role;

            // Redirect sesuai role
            if ($role === 'admin') {
                header("Location: index_admin.php");
            } else {
                header("Location: index_user.php");
            }
            exit;
        } else {
            echo "<script>alert('Password salah.');window.location='login.php';</script>";
        }
    } else {
        echo "<script>alert('Email tidak ditemukan.');window.location='login.php';</script>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>SB Admin 2 - Login</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

</head>

<body class="bg-gradient-primary">

    <div class="container">

        <!-- Outer Row -->
        <div class="row justify-content-center">

            <div class="col-xl-10 col-lg-12 col-md-9">

                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <!-- Nested Row within Card Body -->
                     <div class="row align-items-stretch" style="min-height: 500px;">
    <!-- Kiri: Logo + Branding -->
    <div class="col-lg-6 d-none d-lg-flex bg-white justify-content-center align-items-center">
        <div class="text-center w-75">
            <img src="img/logo1.png" alt="FixPoint Logo" class="img-fluid mb-3" style="max-height: 300px;">
        </div>
    </div>

    <!-- Kanan: Form Login -->
    <div class="col-lg-6 d-flex align-items-center">
        <div class="p-5 w-100">
            <div class="text-center">
                <h1 class="h4 text-gray-900 mb-4">Selamat Datang!</h1>
            </div>
            <form class="user" method="POST" action="login.php">
                <div class="form-group">
                    <input type="email" name="email" class="form-control form-control-user" required placeholder="Enter Email...">
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="form-control form-control-user" required placeholder="Password">
                </div>
                <button type="submit" class="btn btn-primary btn-user btn-block">Login</button>
            </form>
            <hr>
            
            <div class="text-center">
                <a class="small" href="register.php">Buat Akun!</a>
            </div>
        </div>
    </div>
</div>

                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

</body>

</html>