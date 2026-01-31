<?php
// File: /var/www/html/webapps/absensi/hrd/validasi.php
session_start();
require_once('../../conf/conf.php');
if (!isset($_SESSION['hrd_login'])) { header("Location: login.php"); exit(); }

$tgl = isset($_GET['tgl']) ? $_GET['tgl'] : date('Y-m-d');
$sql = "SELECT p.nama, p.nik, p.departemen, r.jam_datang, r.jam_pulang, r.status, r.photo, r.id 
        FROM rekap_presensi r JOIN pegawai p ON r.id = p.id 
        WHERE r.jam_datang LIKE '$tgl%' ORDER BY r.jam_datang DESC";
$res = bukaquery($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Absensi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.3/viewer.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.3/viewer.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow p-4 flex justify-between items-center sticky top-0 z-50">
        <a href="index.php" class="font-bold text-gray-700">&larr; Kembali</a>
        <form><input type="date" name="tgl" value="<?php echo $tgl; ?>" class="border p-2 rounded" onchange="this.form.submit()"></form>
    </nav>
    <div class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="gallery">
        <?php while($row = mysqli_fetch_assoc($res)) { 
            $img = "../../" . $row['photo'];
            $img = file_exists($img) ? $img : "https://via.placeholder.com/150?text=Hilang";
        ?>
        <div class="bg-white rounded-lg shadow p-4 flex gap-4">
            <img src="<?php echo $img; ?>" class="w-24 h-32 object-cover rounded cursor-pointer bg-gray-200">
            <div class="flex-1 overflow-hidden">
                <h3 class="font-bold truncate"><?php echo $row['nama']; ?></h3>
                <p class="text-sm text-gray-500"><?php echo $row['nik']; ?></p>
                <div class="mt-2 text-sm">
                    <div class="text-blue-600">Masuk: <b><?php echo substr($row['jam_datang'],11,5); ?></b></div>
                    <div class="text-orange-600">Pulang: <b><?php echo ($row['jam_pulang'] > 0) ? substr($row['jam_pulang'],11,5) : '-'; ?></b></div>
                </div>
                <button onclick="hapus('<?php echo $row['id']; ?>','<?php echo $row['jam_datang']; ?>')" class="mt-2 text-red-500 text-xs border border-red-200 px-2 py-1 rounded hover:bg-red-50">Hapus</button>
            </div>
        </div>
        <?php } ?>
    </div>
    <script>
        new Viewer(document.getElementById('gallery'), { toolbar: false, navbar: false, title: false });
        function hapus(id, jam) {
            if(confirm('Hapus data absensi ini?')) {
                fetch('api_hrd.php', {
                    method:'POST', body: new URLSearchParams({act:'hapus', id:id, jam:jam})
                }).then(()=>location.reload());
            }
        }
    </script>
</body>
</html>