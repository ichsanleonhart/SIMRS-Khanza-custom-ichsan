<?php
session_start();
// 1. Menggunakan require_once untuk memuat konfigurasi database
require_once 'config.php';

// --- Cek Status Login ---
$isLoggedIn = isset($_SESSION["ses_admin_login"]) && $_SESSION["ses_admin_login"] === true;
if (!$isLoggedIn) {
    header("Location: index.php");
    exit;
}

// 2. Tentukan Rentang Tanggal dari GET request, default ke hari ini
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-d');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');

// --- Variabel Global ---
$namaInstansi = 'Kelola Antrian WA';
$favicon = '';
$logoSrc = '';
$outboxData = [];
$editData = null;
$error = '';
$message = '';
$summaryCounts = ['antrian' => 0, 'terkirim' => 0, 'gagal' => 0];

// --- Koneksi Utama ---
$conn = new mysqli($dbHostWa, $dbUserWa, $dbPassWa, $dbNameWa);
if ($conn->connect_error) {
    die("Koneksi ke database WA gagal: " . $conn->connect_error);
}

// --- Logika Aksi (Delete, Update, Export) ---
$action = $_REQUEST['action'] ?? 'list';

// Fungsi untuk mengambil data outbox (digunakan untuk list dan export)
// Perbaikan: Menambahkan parameter $action
function getOutboxData($conn, $table, $tgl_awal, $tgl_akhir, $status_filter, $current_action) {
    $data = [];
    $params = [];
    $types = '';
    
    $sql = "SELECT nomor, nowa, tanggal_jam, status, success, response FROM {$table} WHERE DATE(tanggal_jam) BETWEEN ? AND ?";
    $types .= 'ss';
    $params[] = $tgl_awal;
    $params[] = $tgl_akhir;

    if ($status_filter === 'ANTRIAN') {
        $sql .= " AND status = ?";
        $types .= 's';
        $params[] = 'ANTRIAN';
    } elseif ($status_filter === 'TERKIRIM') {
        $sql .= " AND success = ?";
        $types .= 's';
        $params[] = '1';
    } elseif ($status_filter === 'GAGAL') {
        $sql .= " AND response LIKE ?";
        $types .= 's';
        $params[] = '%gagal%';
    }
    $sql .= " ORDER BY tanggal_jam DESC";
    
    // Perbaikan: Menggunakan variabel $current_action yang dilewatkan
    if ($current_action !== 'export') {
        $sql .= " LIMIT 200";
    }

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
    }
    return $data;
}


// --- Proses Export ---
if ($action === 'export') {
    $status_filter = $_GET['status'] ?? 'ANTRIAN';
    // Perbaikan: Melewatkan $action ke dalam fungsi
    $exportData = getOutboxData($conn, $dbTableWa, $tanggal_awal, $tanggal_akhir, $status_filter, $action);
    
    header("Content-Type: application/vnd.ms-excel");
    $filename = "outbox_" . $status_filter . "_" . $tanggal_awal . "_sd_" . $tanggal_akhir . ".xls";
    header("Content-Disposition: attachment; filename=$filename");
    
    echo "Urutan Kirim\tPesan Terkirim\tNomor WA\tResponse Gateway\tStatus\n";
    if (!empty($exportData)) {
        foreach ($exportData as $item) {
             $status_text = '';
             if ($item['status'] === 'ANTRIAN') $status_text = 'ANTRIAN';
             elseif (isset($item['success']) && $item['success'] == '1') $status_text = 'TERKIRIM';
             elseif (isset($item['response']) && stripos($item['response'], 'gagal') !== false) $status_text = 'GAGAL';
             else $status_text = $item['status'];

            echo htmlspecialchars($item['nomor']) . "\t" . 
                 htmlspecialchars($item['tanggal_jam']) . "\t" .
                 "'" . htmlspecialchars($item['nowa']) . "\t" . 
                 htmlspecialchars($item['response']) . "\t" .
                 htmlspecialchars($status_text) . "\n";
        }
    }
    $conn->close();
    exit;
}


// --- Ambil Data Instansi (Logo, Nama, dll) ---
try {
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

// --- Hitung Summary Berdasarkan Rentang Tanggal ---
$stmtSummary = $conn->prepare("SELECT COUNT(*) as total FROM {$dbTableWa} WHERE status = 'ANTRIAN' AND DATE(tanggal_jam) BETWEEN ? AND ?");
$stmtSummary->bind_param('ss', $tanggal_awal, $tanggal_akhir);
$stmtSummary->execute();
$summaryCounts['antrian'] = $stmtSummary->get_result()->fetch_assoc()['total'] ?? 0;
$stmtSummary->close();

$stmtSummary = $conn->prepare("SELECT COUNT(*) as total FROM {$dbTableWa} WHERE success = '1' AND DATE(tanggal_jam) BETWEEN ? AND ?");
$stmtSummary->bind_param('ss', $tanggal_awal, $tanggal_akhir);
$stmtSummary->execute();
$summaryCounts['terkirim'] = $stmtSummary->get_result()->fetch_assoc()['total'] ?? 0;
$stmtSummary->close();

$stmtSummary = $conn->prepare("SELECT COUNT(*) as total FROM {$dbTableWa} WHERE response LIKE '%gagal%' AND DATE(tanggal_jam) BETWEEN ? AND ?");
$stmtSummary->bind_param('ss', $tanggal_awal, $tanggal_akhir);
$stmtSummary->execute();
$summaryCounts['gagal'] = $stmtSummary->get_result()->fetch_assoc()['total'] ?? 0;
$stmtSummary->close();

// Proses Hapus
if ($action === 'delete' && isset($_GET['nomor'])) {
    $nomor = intval($_GET['nomor']);
    $stmt = $conn->prepare("DELETE FROM {$dbTableWa} WHERE nomor = ?");
    $stmt->bind_param('i', $nomor);
    if ($stmt->execute()) $message = "Pesan berhasil dihapus.";
    else $error = "Gagal menghapus pesan.";
    $stmt->close();
    $action = 'list';
}

// Proses Update
if ($action === 'update' && isset($_POST['nomor'])) {
    $nomor = intval($_POST['nomor']);
    $nowa = trim($_POST['nowa']);
    $pesan = trim($_POST['pesan']);
    $stmt = $conn->prepare("UPDATE {$dbTableWa} SET nowa = ?, pesan = ? WHERE nomor = ?");
    $stmt->bind_param('ssi', $nowa, $pesan, $nomor);
    if ($stmt->execute()) $message = "Pesan berhasil diperbarui.";
    else $error = "Gagal memperbarui pesan.";
    $stmt->close();
    $action = 'list';
}

// Ambil data untuk mode Edit
if ($action === 'edit' && isset($_GET['nomor'])) {
    $nomor = intval($_GET['nomor']);
    $stmt = $conn->prepare("SELECT nomor, nowa, pesan FROM {$dbTableWa} WHERE nomor = ?");
    $stmt->bind_param('i', $nomor);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Ambil data untuk ditampilkan di tabel (List)
if ($action === 'list') {
    $status_filter = $_GET['status'] ?? 'ANTRIAN';
    // Perbaikan: Melewatkan $action ke dalam fungsi
    $outboxData = getOutboxData($conn, $dbTableWa, $tanggal_awal, $tanggal_akhir, $status_filter, $action);
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $namaInstansi; ?> - Kelola Outbox</title>
    <?php if ($favicon): ?>
        <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .table-container { max-height: 60vh; overflow-y: auto; }
        .response-column { max-width: 350px; white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen py-12">
    <div class="w-full max-w-7xl mx-auto bg-white p-8 rounded-xl shadow-lg relative">
        <div class="flex justify-between items-start mb-6">
            <div class="flex items-center space-x-4">
                <?php if ($logoSrc): ?>
                    <img src="<?php echo $logoSrc; ?>" alt="Logo Instansi" class="h-12 w-12 object-contain">
                <?php endif; ?>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800"><?php echo $namaInstansi; ?></h1>
                    <p class="text-gray-500">Manajemen Antrian Pesan WhatsApp</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="index.php" class="text-sm text-gray-500 hover:text-indigo-600">Kirim Pesan</a>
                <a href="cari_pasien_pulang.php" class="text-sm text-gray-500 hover:text-indigo-600">Cari Pasien</a>
                <a href="index.php?action=logout" class="text-sm text-gray-500 hover:text-indigo-600">Logout</a>
            </div>
        </div>

        <!-- Summary Box -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg shadow-sm">
                <p class="text-sm font-medium text-yellow-600">Total Pesan dalam Antrian</p>
                <p class="mt-2 text-3xl font-bold text-yellow-900"><?php echo number_format($summaryCounts['antrian']); ?></p>
            </div>
            <div class="bg-green-50 border-l-4 border-green-400 p-6 rounded-lg shadow-sm">
                <p class="text-sm font-medium text-green-600">Total Pesan Terkirim</p>
                <p class="mt-2 text-3xl font-bold text-green-900"><?php echo number_format($summaryCounts['terkirim']); ?></p>
            </div>
            <div class="bg-red-50 border-l-4 border-red-400 p-6 rounded-lg shadow-sm">
                <p class="text-sm font-medium text-red-600">Total Pesan Gagal</p>
                <p class="mt-2 text-3xl font-bold text-red-900"><?php echo number_format($summaryCounts['gagal']); ?></p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($action === 'edit' && $editData): ?>
        <!-- FORM EDIT -->
        <div class="bg-gray-50 p-6 rounded-lg">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Edit Pesan Antrian</h2>
            <form action="manage_outbox.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="nomor" value="<?php echo htmlspecialchars($editData['nomor']); ?>">
                <div>
                    <label for="nowa" class="block text-sm font-medium text-gray-700">Nomor WhatsApp</label>
                    <input type="text" id="nowa" name="nowa" value="<?php echo htmlspecialchars($editData['nowa']); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                </div>
                <div>
                    <label for="pesan" class="block text-sm font-medium text-gray-700">Isi Pesan</label>
                    <textarea id="pesan" name="pesan" rows="6" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"><?php echo htmlspecialchars($editData['pesan']); ?></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <a href="manage_outbox.php?status=<?php echo $_GET['status'] ?? 'ANTRIAN'; ?>&tanggal_awal=<?php echo $tanggal_awal; ?>&tanggal_akhir=<?php echo $tanggal_akhir; ?>" class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Batal</a>
                    <button type="submit" class="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Simpan Perubahan</button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <!-- TABEL LIST -->
        <div>
            <!-- Form Filter Tanggal & Status -->
            <form action="manage_outbox.php" method="GET" class="bg-gray-50 p-4 rounded-lg mb-6 flex justify-between items-center">
                <div class="flex items-end space-x-4">
                    <div>
                        <label for="tanggal_awal" class="block text-sm font-medium text-gray-700">Tanggal Awal</label>
                        <input type="date" id="tanggal_awal" name="tanggal_awal" value="<?php echo htmlspecialchars($tanggal_awal); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                    </div>
                    <div>
                        <label for="tanggal_akhir" class="block text-sm font-medium text-gray-700">Tanggal Akhir</label>
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?php echo htmlspecialchars($tanggal_akhir); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                    </div>
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($_GET['status'] ?? 'ANTRIAN'); ?>">
                    <button type="submit" class="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Cari</button>
                </div>
                <div class="flex items-center space-x-2">
                    <a href="manage_outbox.php?action=export&status=<?php echo ($_GET['status'] ?? 'ANTRIAN'); ?>&tanggal_awal=<?php echo $tanggal_awal; ?>&tanggal_akhir=<?php echo $tanggal_akhir; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2-2z"></path></svg>
                        Export
                    </a>
                    <div class="flex space-x-1 bg-white p-1 rounded-lg border">
                        <?php $statuses = ['ANTRIAN', 'TERKIRIM', 'GAGAL']; ?>
                        <?php foreach ($statuses as $s): ?>
                            <a href="?status=<?php echo $s; ?>&tanggal_awal=<?php echo $tanggal_awal; ?>&tanggal_akhir=<?php echo $tanggal_akhir; ?>" class="px-4 py-2 text-sm font-medium rounded-md <?php echo ($_GET['status'] ?? 'ANTRIAN') === $s ? 'bg-indigo-600 text-white shadow' : 'text-gray-600 hover:bg-gray-100'; ?>"><?php echo $s; ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>

            <div class="border rounded-lg shadow table-container">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Urutan Kirim</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pesan Terkirim</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nomor WA</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Response Gateway</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($outboxData)): ?>
                            <?php foreach ($outboxData as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['nomor']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($item['tanggal_jam']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($item['nowa']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500 response-column"><?php echo htmlspecialchars($item['response']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php 
                                                $current_status_filter = $_GET['status'] ?? 'ANTRIAN';
                                                if ($current_status_filter === 'ANTRIAN') echo 'bg-yellow-100 text-yellow-800';
                                                elseif ($current_status_filter === 'TERKIRIM') echo 'bg-green-100 text-green-800';
                                                elseif ($current_status_filter === 'GAGAL') echo 'bg-red-100 text-red-800';
                                            ?>">
                                            <?php echo $current_status_filter; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <?php if ($item['status'] === 'ANTRIAN'): ?>
                                            <a href="?action=edit&nomor=<?php echo $item['nomor']; ?>&status=<?php echo $current_status_filter; ?>&tanggal_awal=<?php echo $tanggal_awal; ?>&tanggal_akhir=<?php echo $tanggal_akhir; ?>" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                            <a href="?action=delete&nomor=<?php echo $item['nomor']; ?>&status=<?php echo $current_status_filter; ?>&tanggal_awal=<?php echo $tanggal_awal; ?>&tanggal_akhir=<?php echo $tanggal_akhir; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Anda yakin ingin menghapus pesan ini dari antrian?');">Hapus</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada data untuk status "<?php echo htmlspecialchars($_GET['status'] ?? 'ANTRIAN'); ?>" pada rentang tanggal yang dipilih.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

