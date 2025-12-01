<?php
session_start();
require_once('../../conf/conf.php');

// Jika sudah login, lempar ke index
if (isset($_SESSION['jadwal_login']) && $_SESSION['jadwal_login'] == true) {
    header("Location: index.php");
    exit();
}

$error = '';
if (isset($_POST['login'])) {
    $user = validTeks($_POST['username']);
    // Gunakan addslashes agar password dengan karakter unik aman
    $pass = isset($_POST['password']) ? addslashes($_POST['password']) : '';

    if (empty($user) || empty($pass)) {
        $error = "Username dan Password tidak boleh kosong.";
    } else {
        // 1. CEK ADMIN (Super User - Akses Semua Departemen)
        $q_admin = "SELECT AES_DECRYPT(usere, 'nur') as usere FROM admin 
                    WHERE usere = AES_ENCRYPT('$user', 'nur') 
                    AND passworde = AES_ENCRYPT('$pass', 'windi')";
        $r_admin = bukaquery($q_admin);

        if (mysqli_num_rows($r_admin) > 0) {
            $_SESSION['jadwal_login'] = true;
            $_SESSION['jadwal_user'] = $user;
            $_SESSION['jadwal_level'] = 'admin';
            $_SESSION['jadwal_dep'] = 'ALL'; // Kode sakti untuk akses semua
            header("Location: index.php");
            exit();
        } 
        
        // 2. CEK USER BIASA (Kepala Ruangan/Kanit)
        // Syarat: harus punya hak akses 'jadwal_pegawai' = true
        $q_user = "SELECT AES_DECRYPT(id_user, 'nur') as id_user 
                   FROM user 
                   WHERE id_user = AES_ENCRYPT('$user', 'nur') 
                   AND password = AES_ENCRYPT('$pass', 'windi')
                   AND jadwal_pegawai = 'true'";
        $r_user = bukaquery($q_user);

        if (mysqli_num_rows($r_user) > 0) {
            // Ambil Data Departemen Pegawai Ini
            $cek_peg = fetch_assoc("SELECT nama, departemen FROM pegawai WHERE nik='$user'");
            
            if ($cek_peg) {
                $_SESSION['jadwal_login'] = true;
                $_SESSION['jadwal_user'] = $cek_peg['nama'];
                $_SESSION['jadwal_level'] = 'karu';
                $_SESSION['jadwal_dep'] = $cek_peg['departemen']; // Kunci departemen
                header("Location: index.php");
                exit();
            } else {
                $error = "Data kepegawaian (NIK) tidak ditemukan.";
            }
        } else {
            $error = "Login gagal. Pastikan Anda memiliki hak akses 'Jadwal Pegawai'.";
        }
    }
}

// Ambil Logo & Nama Instansi
$setting = fetch_assoc("SELECT nama_instansi, logo FROM setting LIMIT 1");
$logo = isset($setting['logo']) ? 'data:image/jpeg;base64,' . base64_encode($setting['logo']) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Jadwal - <?php echo $setting['nama_instansi']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 h-screen flex items-center justify-center px-4">
    <div class="max-w-sm w-full bg-slate-800 rounded-xl shadow-2xl overflow-hidden border border-slate-700">
        <div class="p-8 text-center">
            <?php if($logo) echo "<img src='$logo' class='w-20 h-20 mx-auto rounded-full mb-4 shadow-lg'>"; ?>
            <h2 class="text-2xl font-bold text-white mb-1">E-Schedule</h2>
            <p class="text-slate-400 text-sm mb-6"><?php echo $setting['nama_instansi']; ?></p>

            <?php if($error): ?>
                <div class="bg-red-500/20 border border-red-500 text-red-200 text-sm p-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="text-left">
                <div class="mb-4">
                    <label class="block text-slate-400 text-xs font-bold mb-2 uppercase">Username / NIK</label>
                    <input type="password" name="username" class="w-full bg-slate-900 text-white border border-slate-600 rounded px-3 py-3 focus:outline-none focus:border-blue-500" required autocomplete="off">
                </div>
                <div class="mb-6">
                    <label class="block text-slate-400 text-xs font-bold mb-2 uppercase">Password</label>
                    <input type="password" name="password" class="w-full bg-slate-900 text-white border border-slate-600 rounded px-3 py-3 focus:outline-none focus:border-blue-500" required>
                </div>
                <button type="submit" name="login" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded transition">MASUK</button>
            </form>
        </div>

        <div class="bg-slate-950 p-4 border-t border-slate-700">
            <div class="grid grid-cols-2 gap-3 text-center">
                <a href="../hrd/login.php" class="text-xs font-bold text-purple-400 hover:text-white border border-purple-900 hover:bg-purple-900/50 py-2 rounded transition">
                    <span class="block text-[10px] text-slate-500 font-normal">Ke Portal</span>
                    ADMIN HRD
                </a>
                <a href="../index.php" class="text-xs font-bold text-green-400 hover:text-white border border-green-900 hover:bg-green-900/50 py-2 rounded transition">
                    <span class="block text-[10px] text-slate-500 font-normal">Ke Mesin</span>
                    ABSENSI WAJAH
                </a>
            </div>
        </div>

    </div>
</body>
</html>