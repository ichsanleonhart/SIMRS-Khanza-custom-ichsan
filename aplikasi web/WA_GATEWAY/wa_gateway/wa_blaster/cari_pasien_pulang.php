<?php
session_start();
// Menggunakan require_once untuk memuat konfigurasi database
require_once 'config.php';

// --- Cek Status Login ---
$isLoggedIn = isset($_SESSION["ses_admin_login"]) && $_SESSION["ses_admin_login"] === true;
if (!$isLoggedIn) {
    header("Location: index.php");
    exit;
}

// --- Tentukan Rentang Tanggal ---
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-d', strtotime('-1 day'));
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d', strtotime('-1 day'));


// --- Variabel Global ---
$namaInstansi = 'Cari Pasien Pulang';
$favicon = '';
$logoSrc = '';
$pasienData = [];
$error = '';

// --- Ambil Data Instansi (Logo, Nama, dll) ---
try {
    // Menggunakan variabel koneksi dari config.php
    $connSik = new mysqli($dbHostSik, $dbUserSik, $dbPassSik, $dbNameSik);
    if (!$connSik->connect_error) {
        $sqlSik = "SELECT nama_instansi, logo FROM setting LIMIT 1";
        $resultSik = $connSik->query($sqlSik);
        if ($resultSik && $resultSik->num_rows > 0) {
            $row = $resultSik->fetch_assoc();
            $namaInstansi = htmlspecialchars($row['nama_instansi']);
            if (!empty($row['logo'])) {
                $logoData = base64_encode($row['logo']);
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $finfo->buffer($row['logo']);
                $logoSrc = "data:{$mime_type};base64,{$logoData}";
                $favicon = $logoSrc;
            }
        }
        $connSik->close();
    }
} catch (Exception $e) {
    error_log("Error saat mengambil data instansi: " . $e->getMessage());
}

// --- Fungsi untuk mengambil data pasien dengan rentang tanggal ---
function getPasienData($conn, $tgl_awal, $tgl_akhir) {
    $data = [];
    $sql = "SELECT DISTINCT p.no_rkm_medis, p.nm_pasien, p.no_tlp, x.jam_pulang, x.tanggal_pulang
            FROM pasien p
            INNER JOIN reg_periksa rp ON p.no_rkm_medis = rp.no_rkm_medis
            INNER JOIN (
                SELECT no_rawat, jam AS jam_pulang, tanggal as tanggal_pulang FROM nota_jalan WHERE tanggal BETWEEN ? AND ?
                UNION ALL
                SELECT no_rawat, jam AS jam_pulang, tanggal as tanggal_pulang FROM nota_inap WHERE tanggal BETWEEN ? AND ?
            ) AS x ON rp.no_rawat = x.no_rawat
            WHERE p.no_tlp != '' AND p.no_tlp != '-'
            ORDER BY x.tanggal_pulang, x.jam_pulang, p.nm_pasien";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

// --- Proses Pengambilan Data & Ekspor ---
// Menggunakan variabel koneksi dari config.php
$conn = new mysqli($dbHostSik, $dbUserSik, $dbPassSik, $dbNameSik);
if ($conn->connect_error) {
    $error = "Koneksi database gagal untuk mencari pasien.";
} else {
    $pasienData = getPasienData($conn, $tanggal_awal, $tanggal_akhir);
    $conn->close();
}

if (isset($_GET['action']) && $_GET['action'] === 'export') {
    header("Content-Type: application/vnd.ms-excel");
    $filename = "pasien_pulang_" . $tanggal_awal . "_sd_" . $tanggal_akhir . ".xls";
    header("Content-Disposition: attachment; filename=$filename");
    
    echo "Tanggal Pulang\tJam Pulang\tNo. Rekam Medis\tNama Pasien\tNo. HP\n";
    if (!empty($pasienData)) {
        foreach ($pasienData as $pasien) {
            echo htmlspecialchars($pasien['tanggal_pulang']) . "\t" . 
                 htmlspecialchars($pasien['jam_pulang']) . "\t" .
                 htmlspecialchars($pasien['no_rkm_medis']) . "\t" . 
                 htmlspecialchars($pasien['nm_pasien']) . "\t" . 
                 "'" . htmlspecialchars($pasien['no_tlp']) . "\n";
        }
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $namaInstansi; ?> - Cari Pasien</title>
    <?php if ($favicon): ?>
        <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .table-container { max-height: 60vh; overflow-y: auto; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen py-12">
    <div class="w-full max-w-5xl mx-auto bg-white p-8 rounded-xl shadow-lg relative">
        <div class="flex justify-between items-start mb-6">
            <div class="flex items-center space-x-4">
                <?php if ($logoSrc): ?>
                    <img src="<?php echo $logoSrc; ?>" alt="Logo Instansi" class="h-12 w-12 object-contain">
                <?php endif; ?>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800"><?php echo $namaInstansi; ?></h1>
                    <p class="text-gray-500">Pencarian Pasien Pulang</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="index.php" class="text-sm text-gray-500 hover:text-indigo-600">Kembali ke Blaster</a>
                <a href="manage_outbox.php" class="text-sm text-gray-500 hover:text-indigo-600">Kelola Outbox</a>
                <a href="index.php?action=logout" class="text-sm text-gray-500 hover:text-indigo-600">Logout</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <div>
            <!-- Form Pencarian Tanggal -->
            <form action="cari_pasien_pulang.php" method="GET" class="bg-gray-50 p-4 rounded-lg mb-6 flex items-end space-x-4">
                <div>
                    <label for="tanggal_awal" class="block text-sm font-medium text-gray-700">Tanggal Awal</label>
                    <input type="date" id="tanggal_awal" name="tanggal_awal" value="<?php echo htmlspecialchars($tanggal_awal); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="tanggal_akhir" class="block text-sm font-medium text-gray-700">Tanggal Akhir</label>
                    <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?php echo htmlspecialchars($tanggal_akhir); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    Cari Data
                </button>
            </form>

            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-700">
                    Data Pasien Pulang: <?php echo date('d M Y', strtotime($tanggal_awal)) . ' - ' . date('d M Y', strtotime($tanggal_akhir)); ?>
                </h2>
                <a href="cari_pasien_pulang.php?action=export&tanggal_awal=<?php echo $tanggal_awal; ?>&tanggal_akhir=<?php echo $tanggal_akhir; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                    <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2-2z"></path></svg>
                    Export ke Excel
                </a>
            </div>
            <div class="border rounded-lg shadow table-container">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Pulang</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. RM</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Pasien</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. HP</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($pasienData)): ?>
                            <?php foreach ($pasienData as $pasien): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($pasien['tanggal_pulang']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($pasien['jam_pulang']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($pasien['no_rkm_medis']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($pasien['nm_pasien']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($pasien['no_tlp']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada data pasien yang ditemukan untuk rentang tanggal yang dipilih.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
