<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lama = $_POST['password_lama'];
    $baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi_baru'];

    if ($baru !== $konfirmasi) {
        echo "<script>alert('Konfirmasi password tidak cocok!');</script>";
    } else {
        // Validasi kompleksitas password baru
        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
        if (!preg_match($pattern, $baru)) {
            echo "<script>alert('Password baru harus minimal 8 karakter, mengandung huruf besar, kecil, angka, dan simbol khusus!');</script>";
        } else {
            $cek = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
            $cek->bind_param("i", $user_id);
            $cek->execute();
            $cek->store_result();
            $cek->bind_result($hash);
            $cek->fetch();

            if (password_verify($lama, $hash)) {
                $newHash = password_hash($baru, PASSWORD_BCRYPT);
                $update = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $update->bind_param("si", $newHash, $user_id);
                $update->execute();
                echo "<script>alert('Password berhasil diubah!'); window.location='ubah_password.php';</script>";
                exit;
            } else {
                echo "<script>alert('Password lama salah!');</script>";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ubah Password</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">

    <?php include 'sidebar_user.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'topbar.php'; ?>

            <div class="container-fluid">
                <h1 class="h3 mb-4 text-gray-800">Ubah Password</h1>

                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-warning text-white">Form Ubah Password</div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label>Password Lama</label>
                                    <input type="password" id="passwordLama" name="password_lama" class="form-control" required>
                                    <div class="form-check mt-1">
                                        <input type="checkbox" class="form-check-input" onclick="togglePassword('passwordLama')">
                                        <label class="form-check-label small">Tampilkan Password</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Password Baru</label>
                                    <input type="password" id="passwordBaru" name="password_baru" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Konfirmasi Password Baru</label>
                                    <input type="password" id="konfirmasiBaru" name="konfirmasi_baru" class="form-control" required>
                                    <div class="form-check mt-1">
                                        <input type="checkbox" class="form-check-input" onclick="togglePassword('passwordBaru'); togglePassword('konfirmasiBaru')">
                                        <label class="form-check-label small">Tampilkan Password Baru</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-warning text-white">Simpan Password Baru</button>
                            </form>
                        </div>
                    </div>
                </div>

            </div> <!-- /.container -->
        </div>
    </div>
</div>

<?php include 'logout_modal.php'; ?>
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>

<!-- JS Toggle Password -->
<script>
function togglePassword(id) {
    var field = document.getElementById(id);
    if (field.type === "password") {
        field.type = "text";
    } else {
        field.type = "password";
    }
}
</script>
</body>
</html>
