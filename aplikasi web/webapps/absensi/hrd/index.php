<?php
// File: /var/www/html/webapps/absensi/hrd/index.php
session_start();
require_once('../../conf/conf.php');
if (!isset($_SESSION['hrd_login'])) { header("Location: login.php"); exit(); }

$set = fetch_assoc("SELECT nama_instansi, logo FROM setting LIMIT 1");
$logo = 'data:image/jpeg;base64,' . base64_encode($set['logo']);
$tgl = date('Y-m-d');
$hadir = fetch_assoc("SELECT count(id) as t FROM rekap_presensi WHERE jam_datang LIKE '$tgl%'");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Dashboard HRD</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <nav class="bg-gray-800 p-4 flex justify-between items-center border-b border-gray-700">
        <div class="flex items-center gap-3">
            <img src="<?php echo $logo; ?>" class="h-10 w-10 rounded-full">
            <div>
                <div class="font-bold">HRD PANEL</div>
                <div class="text-xs text-gray-400"><?php echo $set['nama_instansi']; ?></div>
            </div>
        </div>
        <a href="logout.php" class="text-gray-400 hover:text-white">Logout</a>
    </nav>

    <div class="p-4 max-w-4xl mx-auto">
        <div class="bg-blue-900 p-6 rounded-xl mb-6">
            <h1 class="text-xl font-bold">Selamat Datang, <?php echo $_SESSION['hrd_user']; ?></h1>
            <p class="text-blue-200">Kehadiran Hari Ini: <b><?php echo $hadir['t']; ?></b> Pegawai</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <a href="validasi.php" class="bg-gray-800 p-6 rounded-xl border border-gray-700 hover:bg-gray-700 transition">
                <h3 class="font-bold text-lg text-blue-400 mb-1">Validasi Absen</h3>
                <p class="text-gray-400 text-sm">Cek foto & data presensi</p>
            </a>
            <a href="enrollment.php" class="bg-gray-800 p-6 rounded-xl border border-gray-700 hover:bg-gray-700 transition">
                <h3 class="font-bold text-lg text-purple-400 mb-1">Enrollment</h3>
                <p class="text-gray-400 text-sm">Daftarkan wajah pegawai</p>
            </a>
        </div>
    </div>
</body>
</html>