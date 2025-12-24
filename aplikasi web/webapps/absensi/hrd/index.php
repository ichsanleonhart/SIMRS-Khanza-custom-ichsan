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
			<a href="jam_jaga.php" class="block bg-gray-800 hover:bg-gray-700 p-5 rounded-xl border border-gray-700 transition shadow hover:shadow-lg group">
                <div class="w-10 h-10 bg-orange-900/50 rounded-lg flex items-center justify-center text-orange-400 mb-3 group-hover:text-white group-hover:bg-orange-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="font-bold text-white group-hover:text-orange-300">Setting Jam Jaga</h3>
                <p class="text-xs text-gray-400 mt-1">Atur jam masuk & pulang per unit</p>
			</a>
			<a href="laporan_harian.php" class="block bg-gray-800 hover:bg-gray-700 p-5 rounded-xl border border-gray-700 transition shadow hover:shadow-lg group">
                <div class="w-10 h-10 bg-pink-900/50 rounded-lg flex items-center justify-center text-pink-400 mb-3 group-hover:text-white group-hover:bg-pink-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <h3 class="font-bold text-white group-hover:text-pink-300">Laporan Harian</h3>
                <p class="text-xs text-gray-400 mt-1">Rekap, Filter & Export</p>
            </a>
			<a href="monitoring.php" class="block bg-gray-800 hover:bg-gray-700 p-5 rounded-xl border border-gray-700 transition shadow hover:shadow-lg group relative overflow-hidden">
                <span class="absolute top-3 right-3 flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                
                <div class="w-10 h-10 bg-teal-900/50 rounded-lg flex items-center justify-center text-teal-400 mb-3 group-hover:text-white group-hover:bg-teal-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="font-bold text-white group-hover:text-teal-300">Live Monitoring</h3>
                <p class="text-xs text-gray-400 mt-1">Pantau pegawai yang sedang dinas</p>
            </a>
			<a href="pegawai.php" class="block bg-gray-800 hover:bg-gray-700 p-5 rounded-xl border border-gray-700 transition shadow hover:shadow-lg group relative overflow-hidden">
                <div class="w-10 h-10 bg-indigo-900/50 rounded-lg flex items-center justify-center text-indigo-400 mb-3 group-hover:text-white group-hover:bg-indigo-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <h3 class="font-bold text-white group-hover:text-indigo-300">Data Pegawai</h3>
                <p class="text-xs text-gray-400 mt-1">Input, Edit & Validasi Staf</p>
            </a>
			<a href="cuti.php" class="block bg-gray-800 hover:bg-gray-700 p-5 rounded-xl border border-gray-700 transition shadow hover:shadow-lg group relative overflow-hidden">
                <div class="w-10 h-10 bg-yellow-900/50 rounded-lg flex items-center justify-center text-yellow-400 mb-3 group-hover:text-white group-hover:bg-yellow-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <h3 class="font-bold text-white group-hover:text-yellow-300">Approval Cuti</h3>
                <p class="text-xs text-gray-400 mt-1">Persetujuan & Update Jadwal</p>
            </a>
			
        </div>
    </div>
</body>
</html>