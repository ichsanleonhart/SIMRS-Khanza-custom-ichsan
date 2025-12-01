<?php
session_start();
// Pastikan path ini benar. Naik 2 level dari folder hrd (hrd -> absensi -> webapps)
require_once('../../conf/conf.php');

// Cek jika sudah login
if (isset($_SESSION['hrd_login']) && $_SESSION['hrd_login'] == true) {
    header("Location: index.php");
    exit();
}

// LOGIKA FETCH LOGO YANG LEBIH AMAN
$logo_src = "https://via.placeholder.com/100?text=LOGO"; // Default jika DB kosong
$nama_instansi = "SIMKES Khanza";

$konektor = bukakoneksi(); // Buka koneksi eksplisit
if ($konektor) {
    $query_setting = mysqli_query($konektor, "SELECT nama_instansi, logo FROM setting LIMIT 1");
    if ($query_setting && mysqli_num_rows($query_setting) > 0) {
        $setting = mysqli_fetch_assoc($query_setting);
        $nama_instansi = $setting['nama_instansi'];
        
        // Cek apakah kolom logo ada isinya
        if (!empty($setting['logo'])) {
            $logo_src = 'data:image/jpeg;base64,' . base64_encode($setting['logo']);
        }
    }
}

// ... (Sisa logika login di bawah ini) ...
$error = '';
if (isset($_POST['login'])) {
    $usere = validTeks($_POST['username']);
    // Gunakan addslashes untuk password (jangan validTeks karena karakter unik bisa hilang)
    $passworde = isset($_POST['password']) ? addslashes($_POST['password']) : '';

    if (empty($usere) || empty($passworde)) {
        $error = "Username dan Password harus diisi.";
    } else {
        // Cek Admin
        $q_admin = "SELECT AES_DECRYPT(usere, 'nur') as usere 
                    FROM admin 
                    WHERE usere = AES_ENCRYPT('$usere', 'nur') 
                    AND passworde = AES_ENCRYPT('$passworde', 'windi')";
        
        // Gunakan bukakoneksi() untuk eksekusi query
        $r_admin = mysqli_query($konektor, $q_admin);
        
        if ($r_admin && mysqli_num_rows($r_admin) > 0) {
            $_SESSION['hrd_login'] = true;
            $_SESSION['hrd_user'] = $usere;
            $_SESSION['hrd_level'] = 'admin';
            header("Location: index.php");
            exit();
        } 
        
        // Cek User Pegawai
        $q_user = "SELECT AES_DECRYPT(id_user, 'nur') as id_user 
                   FROM user 
                   WHERE id_user = AES_ENCRYPT('$usere', 'nur') 
                   AND password = AES_ENCRYPT('$passworde', 'windi') 
                   AND presensi_harian = 'true'";
        
        $r_user = mysqli_query($konektor, $q_user);
        
        if ($r_user && mysqli_num_rows($r_user) > 0) {
            $_SESSION['hrd_login'] = true;
            $_SESSION['hrd_user'] = $usere;
            $_SESSION['hrd_level'] = 'hrd';
            header("Location: index.php");
            exit();
        } else {
            $error = "Login Gagal. Akun tidak ditemukan atau tidak memiliki hak akses.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login HRD - <?php echo $nama_instansi; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="<?php echo $logo_src; ?>">
</head>
<body class="bg-gray-900 min-h-screen flex flex-col items-center justify-center px-4">

    <div class="w-full max-w-md bg-gray-800 rounded-2xl shadow-2xl overflow-hidden border border-gray-700">
        <div class="bg-gray-700 p-6 text-center">
            <img src="<?php echo $logo_src; ?>" class="h-20 w-auto mx-auto mb-3 rounded shadow-md object-contain bg-white/10 p-1" alt="Logo">
            <h2 class="text-xl font-bold text-white">Portal HRD</h2>
            <p class="text-sm text-gray-400"><?php echo $nama_instansi; ?></p>
        </div>

        <div class="p-6">
            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-500 text-red-200 px-4 py-2 rounded mb-4 text-sm text-center">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-300 text-sm font-bold mb-2">ID User / Username</label>
                    <input type="password" name="username" class="w-full bg-gray-900 text-white border border-gray-600 rounded-lg py-3 px-4 focus:outline-none focus:border-blue-500" placeholder="Masukkan ID..." required autofocus>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-300 text-sm font-bold mb-2">Password</label>
                    <input type="password" name="password" class="w-full bg-gray-900 text-white border border-gray-600 rounded-lg py-3 px-4 focus:outline-none focus:border-blue-500" placeholder="Masukkan Password..." required>
                </div>
                <button type="submit" name="login" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300 shadow-lg">
                    MASUK
                </button>
            </form>
        </div>

        <div class="bg-gray-900 p-4 border-t border-gray-700">
            <div class="grid grid-cols-2 gap-3 text-center">
                <a href="../jadwal/login.php" class="text-xs font-bold text-blue-400 hover:text-white border border-blue-900 hover:bg-blue-900/50 py-2 rounded transition">
                    <span class="block text-[10px] text-gray-500 font-normal">Ke Portal</span>
                    JADWAL DINAS
                </a>
                <a href="../index.php" class="text-xs font-bold text-green-400 hover:text-white border border-green-900 hover:bg-green-900/50 py-2 rounded transition">
                    <span class="block text-[10px] text-gray-500 font-normal">Ke Mesin</span>
                    ABSENSI WAJAH
                </a>
            </div>
        </div>
    </div>

</body>
</html>