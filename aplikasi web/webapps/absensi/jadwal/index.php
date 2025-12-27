<?php
session_start();
require_once('../../conf/conf.php');

// Cek Session
if (!isset($_SESSION['jadwal_login'])) {
    header("Location: login.php");
    exit();
}

// Hitung Notifikasi Cuti (Pending Level 1)
$nik_login = $_SESSION['jadwal_user'];
$q_notif = "SELECT count(*) as total FROM pengajuan_cuti 
            WHERE nik_pj='$nik_login' AND (status='Proses Pengajuan' OR status IS NULL OR status='')";
$d_notif = fetch_assoc($q_notif);
$total_pending = $d_notif['total'];

// Badge HTML
$badge_html = "";
if($total_pending > 0) {
    $badge_html = "<span class='absolute -top-2 -right-2 bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full animate-pulse shadow-lg'>$total_pending</span>";
}

$dep_akses = $_SESSION['jadwal_dep']; // 'ALL' atau Kode Dept (misal 'D001')
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$keyword = isset($_GET['q']) ? validTeks($_GET['q']) : '';

// Query Pegawai (Sesuai Hak Akses)
$where_dep = ($dep_akses == 'ALL') ? "" : "AND p.departemen = '$dep_akses'";
$where_key = ($keyword) ? "AND (p.nama LIKE '%$keyword%' OR p.nik LIKE '%$keyword%')" : "";

// Join ke tabel jadwal untuk cek status (sudah diisi/belum)
// Kita gunakan LEFT JOIN untuk mendeteksi null
$sql = "SELECT p.id, p.nik, p.nama, p.jbtn, d.nama as nama_dep,
        j.id as id_jadwal
        FROM pegawai p
        LEFT JOIN departemen d ON p.departemen = d.dep_id
        LEFT JOIN jadwal_pegawai j ON p.id = j.id AND j.bulan = '$bulan' AND j.tahun = '$tahun'
        WHERE p.stts_aktif = 'AKTIF' 
        $where_dep 
        $where_key
        ORDER BY p.nama ASC";

$result = bukaquery($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Jadwal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen font-sans text-slate-800">

<nav class="bg-white shadow-sm sticky top-0 z-20">
    <div class="max-w-4xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div>
                    <h1 class="font-bold text-slate-800 leading-tight">Manajemen Jadwal</h1>
                    <p class="text-xs text-slate-500">
                        <?php echo ($_SESSION['jadwal_dep'] == 'ALL') ? 'Super Administrator' : 'Unit: ' . $_SESSION['jadwal_dep']; ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center">
                <a href="logout.php" class="text-slate-400 hover:text-red-500 px-3 py-2">
                    <i class="fa-solid fa-right-from-bracket text-xl"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="bg-white border-b border-slate-200 sticky top-16 z-10 shadow-sm">
    <div class="max-w-4xl mx-auto px-4 py-3">
        <form method="GET" class="flex flex-col md:flex-row gap-2">
            <div class="flex gap-2 flex-1">
                <select name="bulan" class="bg-slate-50 border border-slate-300 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <?php
                    $bln_arr = ["01"=>"Januari","02"=>"Februari","03"=>"Maret","04"=>"April","05"=>"Mei","06"=>"Juni","07"=>"Juli","08"=>"Agustus","09"=>"September","10"=>"Oktober","11"=>"November","12"=>"Desember"];
                    foreach($bln_arr as $k => $v) {
                        $sel = ($k == $bulan) ? 'selected' : '';
                        echo "<option value='$k' $sel>$v</option>";
                    }
                    ?>
                </select>
                <select name="tahun" class="bg-slate-50 border border-slate-300 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-24 p-2.5">
                    <?php
                    for($y=date('Y')-1; $y<=date('Y')+1; $y++){
                        $sel = ($y == $tahun) ? 'selected' : '';
                        echo "<option value='$y' $sel>$y</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="relative flex-1">
                <input type="search" name="q" value="<?php echo $keyword; ?>" class="block w-full p-2.5 pl-10 text-sm text-slate-900 border border-slate-300 rounded-lg bg-slate-50 focus:ring-blue-500 focus:border-blue-500" placeholder="Cari Pegawai...">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="fa-solid fa-search text-slate-400"></i>
                </div>
                <button type="submit" class="absolute right-0 bottom-0 top-0 bg-blue-600 text-white px-4 rounded-r-lg hover:bg-blue-700">Cari</button>
            </div>
        </form>
    </div>
</div>

<main class="max-w-4xl mx-auto px-4 py-6 pb-20">

	<div class="mb-6">
        <a href="approval_cuti.php" class="relative group block w-full">
            
            <?php if($total_pending > 0): ?>
                <span class="absolute -top-2 -right-1 z-10 flex h-6 w-6">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-6 w-6 bg-red-600 text-white text-[10px] font-bold items-center justify-center border-2 border-slate-100">
                        <?= $total_pending ?>
                    </span>
                </span>
            <?php endif; ?>

            <div class="flex items-center justify-between p-5 bg-gradient-to-r from-slate-800 to-slate-900 rounded-xl shadow-lg border-l-4 border-indigo-500 hover:shadow-indigo-500/20 transition-all transform hover:-translate-y-1">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gray-700/50 rounded-lg flex items-center justify-center text-indigo-400 group-hover:text-white group-hover:bg-indigo-600 transition duration-300">
                        <i class="fa-solid fa-file-signature text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-white text-lg group-hover:text-indigo-300 transition">Approval Cuti</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Kelola persetujuan izin cuti bawahan (Level 1)</p>
                    </div>
                </div>
                
                <div class="text-gray-500 group-hover:text-white transition">
                    <i class="fa-solid fa-chevron-right"></i>
                </div>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 gap-3">
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
                <?php 
                    // Status Visual
                    $status_color = $row['id_jadwal'] ? 'border-l-4 border-green-500' : 'border-l-4 border-slate-300';
                    $status_text  = $row['id_jadwal'] ? '<span class="text-green-600 text-xs font-bold"><i class="fa-solid fa-check"></i> Sudah Diisi</span>' : '<span class="text-slate-400 text-xs"><i class="fa-regular fa-circle"></i> Belum Diisi</span>';
                    $bg_card      = $row['id_jadwal'] ? 'bg-white' : 'bg-slate-50';
                ?>
                
                <a href="input.php?id=<?php echo $row['id']; ?>&bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>" 
                   class="block <?php echo $bg_card; ?> p-4 rounded-lg shadow-sm hover:shadow-md transition <?php echo $status_color; ?>">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-slate-800"><?php echo $row['nama']; ?></h3>
                            <p class="text-xs text-slate-500 mb-1"><?php echo $row['nik']; ?> &bull; <?php echo $row['jbtn']; ?></p>
                            <div class="flex items-center gap-2">
                                <span class="bg-blue-100 text-blue-800 text-[10px] font-medium px-2.5 py-0.5 rounded"><?php echo $row['nama_dep']; ?></span>
                                <?php echo $status_text; ?>
                            </div>
                        </div>
                        <div class="text-slate-300">
                            <i class="fa-solid fa-chevron-right"></i>
                        </div>
                    </div>
                </a>

            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-10 text-slate-400">
                <i class="fa-solid fa-user-slash text-4xl mb-3"></i>
                <p>Tidak ada data pegawai ditemukan.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<a href="laporan.php?bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>" class="fixed bottom-6 right-6 bg-indigo-600 hover:bg-indigo-700 text-white p-4 rounded-full shadow-xl flex items-center justify-center transition transform hover:scale-110" title="Lihat Laporan">
    <i class="fa-solid fa-file-pdf text-xl"></i>
</a>

</body>
</html>