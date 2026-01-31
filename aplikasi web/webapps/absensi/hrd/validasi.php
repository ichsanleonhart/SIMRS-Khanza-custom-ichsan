<?php
session_start();
require_once('../../conf/conf.php');
if (!isset($_SESSION['hrd_login'])) { header("Location: login.php"); exit(); }

$tgl = isset($_GET['tgl']) ? $_GET['tgl'] : date('Y-m-d');

// Query JOIN ke sidecar table
$sql = "SELECT p.nama, p.nik, p.departemen, r.jam_datang, r.jam_pulang, r.status, r.photo, 
               f.photo_out, r.id 
        FROM rekap_presensi r 
        JOIN pegawai p ON r.id = p.id 
        LEFT JOIN rekap_presensi_foto_keluar f ON r.id = f.id_pegawai AND r.jam_datang = f.jam_datang
        WHERE r.jam_datang LIKE '$tgl%' 
        ORDER BY r.jam_datang DESC";

$res = bukaquery($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Absensi Harian</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.3/viewer.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.3/viewer.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/jquery.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen font-sans">
    
    <nav class="bg-white shadow p-4 flex justify-between items-center sticky top-0 z-50">
        <div class="flex items-center gap-4">
            <a href="index.php" class="bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded text-gray-700 font-bold transition">&larr; Kembali</a>
            <h1 class="text-lg font-bold text-gray-800 hidden md:block">Validasi Absensi</h1>
        </div>
        <form class="flex items-center gap-2">
            <label class="text-sm font-semibold text-gray-600">Tanggal:</label>
            <input type="date" name="tgl" value="<?php echo $tgl; ?>" class="border p-2 rounded bg-gray-50 focus:ring focus:ring-blue-300" onchange="this.form.submit()">
        </form>
    </nav>

    <div class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" id="gallery">
        <?php while($row = mysqli_fetch_assoc($res)) { 
            $img = "../../" . $row['photo'];
            $img = file_exists($img) ? $img : "https://via.placeholder.com/150?text=No+Image";
            
            $badge_pulang = ($row['jam_pulang'] == '0000-00-00 00:00:00') 
                ? '<span class="text-red-500 font-bold text-xs">Belum Pulang</span>' 
                : '<span class="text-green-600 font-bold text-xs">'.substr($row['jam_pulang'],11,5).'</span>';
        ?>
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden flex flex-col">
            <div class="p-3 flex gap-3 items-start flex-1">
                <div class="relative group shrink-0">
                    <img src="<?php echo $img; ?>" class="w-20 h-24 object-cover rounded-lg cursor-pointer bg-gray-100 shadow-inner">
                </div>

                <div class="flex-1 overflow-hidden">
                    <h3 class="font-bold text-gray-800 text-sm leading-tight mb-1 truncate" title="<?php echo $row['nama']; ?>">
                        <?php echo $row['nama']; ?>
                    </h3>
                    <p class="text-xs text-blue-500 font-mono mb-2"><?php echo $row['nik']; ?></p>
                    
                    <div class="grid grid-cols-2 gap-1 text-xs bg-gray-50 p-2 rounded border border-gray-100">
                        <div class="text-gray-500">Masuk</div>
                        <div class="font-bold text-gray-800 text-right"><?php echo substr($row['jam_datang'],11,5); ?></div>
                        
                        <div class="text-gray-500">Pulang</div>
                        <div class="text-right"><?php echo $badge_pulang; ?></div>
                    </div>
                </div>
            </div>

            <button onclick="hapus('<?php echo $row['id']; ?>', '<?php echo $row['jam_datang']; ?>', '<?php echo addslashes($row['nama']); ?>')" 
                    class="w-full bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-3 px-4 flex items-center justify-center gap-2 transition duration-200 uppercase tracking-wider">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                Hapus Data Validasi
            </button>
        </div>
        <?php } ?>
        
        <?php if(mysqli_num_rows($res) == 0): ?>
            <div class="col-span-full text-center py-10 text-gray-400">
                <p>Tidak ada data absensi pada tanggal ini.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        new Viewer(document.getElementById('gallery'), { toolbar: false, navbar: false, title: false });

        function hapus(id, jam, nama) {
            Swal.fire({
                title: 'HAPUS PERMANEN?',
                html: `
                    <div class="text-left bg-gray-100 p-3 rounded text-sm mb-2 border border-gray-300">
                        <p>Nama: <b>${nama}</b></p>
                        <p>Jam Masuk: <b class="font-mono text-red-600">${jam}</b></p>
                    </div>
                    <span class="text-red-500 text-xs font-bold">⚠️ Data yang dihapus tidak bisa dikembalikan!</span><br>
                    <span class="text-gray-400 text-[10px]">Tindakan ini akan dicatat di Audit Trail.</span>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626', // Red-600
                cancelButtonColor: '#4b5563', // Gray-600
                confirmButtonText: 'YA, HAPUS SEKARANG',
                cancelButtonText: 'Batal',
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({title: 'Memproses...', didOpen: () => Swal.showLoading()});

                    $.post('api_hrd.php', {
                        act: 'hapus',
                        id: id,
                        jam: jam
                    }, function(res) {
                        try {
                            const data = JSON.parse(res);
                            if (data.status === 'success') {
                                Swal.fire('Terhapus!', data.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Gagal!', data.message, 'error');
                            }
                        } catch (e) {
                            Swal.fire('Error', 'Respon server tidak valid: ' + res, 'error');
                        }
                    }).fail(function() {
                        Swal.fire('Error', 'Gagal menghubungi server', 'error');
                    });
                }
            });
        }
    </script>
</body>
</html>