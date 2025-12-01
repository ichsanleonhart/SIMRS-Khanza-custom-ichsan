<?php
// File: /var/www/html/webapps/absensi/hrd/login.php
session_start();
require_once('../../conf/conf.php');

$allowed_ip_prefix = '192.168.';
$user_ip = $_SERVER['REMOTE_ADDR'];
if (strpos($user_ip, $allowed_ip_prefix) !== 0 && $user_ip !== '127.0.0.1' && $user_ip !== '::1') {
    die("<center><h1>Akses Ditolak</h1></center>");
}

$error = '';
if (isset($_POST['login'])) {
    $usere = validTeks($_POST['username']);
    $pass = validTeks($_POST['password']);

    // Cek Admin
    $r_admin = bukaquery("SELECT AES_DECRYPT(usere, 'nur') FROM admin WHERE usere = AES_ENCRYPT('$usere', 'nur') AND passworde = AES_ENCRYPT('$pass', 'windi')");
    
    // Cek User HRD
    $r_user = bukaquery("SELECT AES_DECRYPT(id_user, 'nur') FROM user WHERE id_user = AES_ENCRYPT('$usere', 'nur') AND password = AES_ENCRYPT('$pass', 'windi') AND presensi_harian = 'true'");

    if (mysqli_num_rows($r_admin) > 0 || mysqli_num_rows($r_user) > 0) {
        $_SESSION['hrd_login'] = true;
        $_SESSION['hrd_user'] = $usere;
        header("Location: index.php");
        exit();
    } else {
        $error = "Login Gagal.";
    }
}
$set = fetch_assoc("SELECT nama_instansi, logo FROM setting LIMIT 1");
$logo = isset($set['logo']) ? 'data:image/jpeg;base64,' . base64_encode($set['logo']) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login HRD</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 flex items-center justify-center h-screen">
    <div class="bg-gray-800 p-8 rounded-lg shadow-lg w-96 border border-gray-700">
        <div class="text-center mb-6">
            <?php if($logo) echo "<img src='$logo' class='h-16 mx-auto rounded-full mb-2'>"; ?>
            <h2 class="text-white text-xl font-bold">Login HRD</h2>
            <p class="text-gray-400 text-sm"><?php echo $set['nama_instansi']; ?></p>
        </div>
        <?php if($error) echo "<div class='bg-red-500/20 text-red-300 p-2 rounded mb-4 text-sm text-center'>$error</div>"; ?>
        <form method="POST">
            <input type="password" name="username" class="w-full p-3 rounded bg-gray-700 text-white mb-3" placeholder="Username" required>
            <input type="password" name="password" class="w-full p-3 rounded bg-gray-700 text-white mb-6" placeholder="Password" required>
            <button type="submit" name="login" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded">MASUK</button>
        </form>
    </div>
</body>
</html>