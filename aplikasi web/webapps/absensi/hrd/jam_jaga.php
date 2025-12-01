<?php
session_start();
require_once('../../conf/conf.php');
if (!isset($_SESSION['hrd_login'])) { header("Location: login.php"); exit(); }

// Handle Hapus
if(isset($_GET['hapus'])) {
    $id = validTeks($_GET['hapus']);
    bukaquery("DELETE FROM jam_jaga WHERE no_id='$id'");
    header("Location: jam_jaga.php");
}

// Daftar ENUM Shift Lengkap (Sesuai Permintaan)
$daftar_shift = [
    'Pagi','Pagi2','Pagi3','Pagi4','Pagi5','Pagi6','Pagi7','Pagi8','Pagi9','Pagi10',
    'Siang','Siang2','Siang3','Siang4','Siang5','Siang6','Siang7','Siang8','Siang9','Siang10',
    'Malam','Malam2','Malam3','Malam4','Malam5','Malam6','Malam7','Malam8','Malam9','Malam10',
    'Midle Pagi1','Midle Pagi2','Midle Pagi3','Midle Pagi4','Midle Pagi5','Midle Pagi6','Midle Pagi7','Midle Pagi8','Midle Pagi9','Midle Pagi10',
    'Midle Siang1','Midle Siang2','Midle Siang3','Midle Siang4','Midle Siang5','Midle Siang6','Midle Siang7','Midle Siang8','Midle Siang9','Midle Siang10',
    'Midle Malam1','Midle Malam2','Midle Malam3','Midle Malam4','Midle Malam5','Midle Malam6','Midle Malam7','Midle Malam8','Midle Malam9','Midle Malam10'
];

// Ambil Data Departemen untuk Dropdown
$q_dep = bukaquery("SELECT dep_id, nama FROM departemen ORDER BY nama ASC");
$opt_dep = "";
while($r = mysqli_fetch_assoc($q_dep)) {
    $opt_dep .= "<option value='{$r['dep_id']}'>{$r['nama']}</option>";
}

// Ambil Data Jam Jaga
$keyword = isset($_GET['q']) ? validTeks($_GET['q']) : '';
$sql = "SELECT j.*, d.nama as nama_dep 
        FROM jam_jaga j 
        JOIN departemen d ON j.dep_id = d.dep_id 
        WHERE d.nama LIKE '%$keyword%' OR j.shift LIKE '%$keyword%'
        ORDER BY d.nama, j.jam_masuk ASC";
$result = bukaquery($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setting Jam Jaga</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/jquery.min.js"></script>
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        /* Custom CSS untuk Select2 di Dark Mode */
        .select2-container--default .select2-selection--single {
            background-color: #111827; /* bg-gray-900 */
            border-color: #4b5563; /* border-gray-600 */
            color: white;
            height: 38px;
            display: flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: white;
            padding-left: 10px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .select2-dropdown {
            background-color: #1f2937; /* bg-gray-800 */
            border-color: #4b5563;
            color: white;
        }
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: #2563eb; /* blue-600 */
            color: white;
        }
        .select2-container--default .select2-results__option--selected {
            background-color: #374151; /* gray-700 */
        }
        .select2-search__field {
            background-color: #111827 !important;
            color: white !important;
            border-color: #4b5563 !important;
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen p-4">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-blue-400">Pengaturan Jam Jaga</h1>
            <a href="index.php" class="text-gray-400 hover:text-white">Kembali</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gray-800 p-4 rounded-lg border border-gray-700 h-fit">
                <h2 class="font-bold mb-4 border-b border-gray-700 pb-2">Tambah / Edit Jadwal</h2>
                <form id="formJaga">
                    <input type="hidden" name="id" id="id">
                    <div class="mb-3">
                        <label class="block text-xs text-gray-400 mb-1">Departemen</label>
                        <select name="dep_id" id="dep_id" class="select2 w-full bg-gray-900 border border-gray-600 rounded p-2 text-sm" required>
                            <option value="">- Pilih Departemen -</option>
                            <?php echo $opt_dep; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs text-gray-400 mb-1">Nama Shift (Ketik untuk mencari)</label>
                        <select name="shift" id="shift" class="select2 w-full bg-gray-900 border border-gray-600 rounded p-2 text-sm" required>
                            <option value="">- Pilih Shift -</option>
                            <?php 
                            foreach($daftar_shift as $shift_name) {
                                echo "<option value='$shift_name'>$shift_name</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mb-4">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Jam Masuk</label>
                            <input type="time" name="jam_masuk" id="jam_masuk" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Jam Pulang</label>
                            <input type="time" name="jam_pulang" id="jam_pulang" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-sm" required>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 py-2 rounded font-bold text-sm">SIMPAN</button>
                        <button type="button" onclick="resetForm()" class="bg-gray-600 hover:bg-gray-500 py-2 px-3 rounded font-bold text-sm">Batal</button>
                    </div>
                </form>
            </div>

            <div class="md:col-span-2 bg-gray-800 p-4 rounded-lg border border-gray-700">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="font-bold">Daftar Jam Jaga</h2>
                    <form method="GET" class="flex gap-2">
                        <input type="text" name="q" value="<?php echo $keyword; ?>" class="bg-gray-900 border border-gray-600 rounded px-3 py-1 text-sm text-white" placeholder="Cari...">
                        <button class="bg-blue-600 px-3 py-1 rounded text-sm text-white">Cari</button>
                    </form>
                </div>
                
                <div class="overflow-x-auto" style="max-height: 600px;">
                    <table class="w-full text-sm text-left text-gray-400">
                        <thead class="text-xs text-gray-200 uppercase bg-gray-700 sticky top-0">
                            <tr>
                                <th class="px-4 py-3">Departemen</th>
                                <th class="px-4 py-3">Shift</th>
                                <th class="px-4 py-3 text-center">Jam Masuk</th>
                                <th class="px-4 py-3 text-center">Jam Pulang</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr class="border-b border-gray-700 hover:bg-gray-700">
                                <td class="px-4 py-3 font-bold text-white"><?php echo $row['nama_dep']; ?></td>
                                <td class="px-4 py-3 text-yellow-400"><?php echo $row['shift']; ?></td>
                                <td class="px-4 py-3 text-center font-mono text-white bg-green-900/30 rounded"><?php echo substr($row['jam_masuk'],0,5); ?></td>
                                <td class="px-4 py-3 text-center font-mono text-white bg-red-900/30 rounded"><?php echo substr($row['jam_pulang'],0,5); ?></td>
                                <td class="px-4 py-3 text-right">
                                    <button onclick='edit(<?php echo json_encode($row); ?>)' class="text-blue-400 hover:underline mr-2">Edit</button>
                                    <a href="?hapus=<?php echo $row['no_id']; ?>" onclick="return confirm('Hapus?')" class="text-red-400 hover:underline">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<script>
    // Inisialisasi Select2
    $(document).ready(function() {
        $('.select2').select2();
    });

    function resetForm() {
        $('#id').val('');
        $('#formJaga')[0].reset();
        // Reset Select2
        $('#dep_id').val('').trigger('change');
        $('#shift').val('').trigger('change');
    }

    function edit(data) {
        $('#id').val(data.no_id);
        // Set value untuk Select2 dan trigger change agar tampilan berubah
        $('#dep_id').val(data.dep_id).trigger('change');
        $('#shift').val(data.shift).trigger('change');
        
        $('#jam_masuk').val(data.jam_masuk);
        $('#jam_pulang').val(data.jam_pulang);
        
        // Scroll ke atas (mobile friendly)
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    $('#formJaga').on('submit', function(e){
        e.preventDefault();
        
        // Validasi Tambahan di JS (Double Check)
        var shiftVal = $('#shift').val();
        if(!shiftVal) {
             Swal.fire('Error', 'Silahkan pilih shift yang valid', 'error');
             return;
        }

        $.post('api_shift.php', $(this).serialize(), function(res){
            try {
                const data = JSON.parse(res);
                if(data.status === 'success') {
                    Swal.fire('Berhasil', 'Data tersimpan', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Gagal', data.message, 'error');
                }
            } catch(err) {
                console.log(res);
                Swal.fire('Error', 'Respon server tidak valid', 'error');
            }
        });
    });
</script>
</body>
</html>