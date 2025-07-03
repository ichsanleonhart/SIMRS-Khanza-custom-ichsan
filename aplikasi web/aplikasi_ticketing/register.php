<?php
require 'koneksi.php';

// Simpan isian sebelumnya untuk dikembalikan ke form saat validasi gagal
$old = [
    'nik' => $_POST['nik'] ?? '',
    'nama' => $_POST['nama'] ?? '',
    'jabatan' => $_POST['jabatan'] ?? '',
    'unit_kerja' => $_POST['unit_kerja'] ?? '',
    'email' => $_POST['email'] ?? ''
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nik = $_POST['nik'];
    $nama = $_POST['nama'];
    $jabatan = $_POST['jabatan'];
    $unit_kerja = $_POST['unit_kerja'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $ulang = $_POST['konfirmasi_password'];

    if ($password !== $ulang) {
        echo "<script>alert('Password tidak cocok!');</script>";
    } else {
        // Validasi kompleksitas password
        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
        if (!preg_match($pattern, $password)) {
            echo "<script>alert('Password harus minimal 8 karakter dan mengandung huruf besar, huruf kecil, angka, dan simbol khusus!');</script>";
        } else {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            $cek = $conn->prepare("SELECT id FROM users WHERE email = ? OR nik = ?");
            $cek->bind_param("ss", $email, $nik);
            $cek->execute();
            $cek->store_result();

            if ($cek->num_rows > 0) {
                echo "<script>alert('Email atau NIK sudah terdaftar!');</script>";
            } else {
                $stmt = $conn->prepare("INSERT INTO users (nik, nama, jabatan, unit_kerja, email, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $nik, $nama, $jabatan, $unit_kerja, $email, $password_hash);
                if ($stmt->execute()) {
                    echo "<script>alert('Registrasi berhasil! Tunggu aktivasi admin.'); window.location='login.php';</script>";
                    exit;
                } else {
                    echo "<script>alert('Terjadi kesalahan saat menyimpan data.');</script>";
                }
            }
        }
    }
}

// Ambil data jabatan dan unit kerja untuk dropdown
$jabatanList = $conn->query("SELECT nama_jabatan FROM jabatan ORDER BY nama_jabatan");
$unitList = $conn->query("SELECT nama_unit FROM unit_kerja ORDER BY nama_unit");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Register Akun</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
<div class="container">
    <div class="card o-hidden border-0 shadow-lg my-5">
        <div class="card-body p-0">
            <div class="row">
                <div class="col-lg-5 d-none d-lg-block bg-register-image"></div>
                <div class="col-lg-7">
                    <div class="p-5">
                        <div class="text-center">
                            <h1 class="h4 text-gray-900 mb-4">Buat Akun Baru</h1>
                        </div>
                        <form class="user" method="POST" action="register.php">
                            <div class="form-group">
                                <input type="text" name="nik" class="form-control form-control-user" placeholder="NIK" required value="<?= htmlspecialchars($old['nik']) ?>">
                            </div>
                            <div class="form-group">
                                <input type="text" name="nama" class="form-control form-control-user" placeholder="Nama Lengkap" required value="<?= htmlspecialchars($old['nama']) ?>">
                            </div>
                            <div class="form-group">
                                <select name="jabatan" class="form-control form-control-user" required>
                                    <option value="">-- Pilih Jabatan --</option>
                                    <?php while ($row = $jabatanList->fetch_assoc()): ?>
                                        <option value="<?= $row['nama_jabatan']; ?>" <?= $old['jabatan'] == $row['nama_jabatan'] ? 'selected' : '' ?>>
                                            <?= $row['nama_jabatan']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <select name="unit_kerja" class="form-control form-control-user" required>
                                    <option value="">-- Pilih Unit Kerja --</option>
                                    <?php while ($row = $unitList->fetch_assoc()): ?>
                                        <option value="<?= $row['nama_unit']; ?>" <?= $old['unit_kerja'] == $row['nama_unit'] ? 'selected' : '' ?>>
                                            <?= $row['nama_unit']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <input type="email" name="email" class="form-control form-control-user" placeholder="Email Aktif" required value="<?= htmlspecialchars($old['email']) ?>">
                            </div>
                            <div class="form-group row">
                                <div class="col-sm-6 mb-3 mb-sm-0">
                                    <input type="password" name="password" class="form-control form-control-user" placeholder="Password" required>
                                </div>
                                <div class="col-sm-6">
                                    <input type="password" name="konfirmasi_password" class="form-control form-control-user" placeholder="Ulangi Password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-user btn-block">Daftar Sekarang</button>
                        </form>

                        <hr>
                        
                        <div class="text-center">
                            <a class="small" href="login.php">Sudah punya akun? Login!</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- card -->
</div> <!-- container -->

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>
</body>
</html>
