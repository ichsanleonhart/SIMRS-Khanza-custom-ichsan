<?php
session_start();

// --- Pengaturan Koneksi Database ---
$dbHost = '192.168.0.2';
$dbName = 'sik_master';
$dbUser = 'client';
$dbPass = 'epotoransu';

// --- Placeholder fungsi validasi dari file referensi ---
if (!function_exists('validTeks4')) {
    function validTeks4($text, $length) {
        return substr(htmlspecialchars(strip_tags($text)), 0, $length);
    }
}

// --- Proses Logout ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: " . basename(__FILE__));
    exit;
}

// --- Variabel Global ---
$namaInstansi = 'Data Operasi';
$favicon = '';
$logoSrc = '';
$loginError = '';

// --- Cek Status Login ---
$isLoggedIn = isset($_SESSION["ses_admin_login"]) && $_SESSION["ses_admin_login"] === true;

// --- Proses Login ---
if (!$isLoggedIn && isset($_POST['BtnLogin'])) {
    $connLogin = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if (!$connLogin->connect_error) {
        $usere = $connLogin->real_escape_string(validTeks4($_POST['usere'], 30));
        $passworde = $connLogin->real_escape_string(validTeks4($_POST['passworde'], 30));
        
        $sqlAdmin = "SELECT COUNT(*) as total FROM admin WHERE usere=aes_encrypt(?,'nur') AND passworde=aes_encrypt(?,'windi')";
        $stmtAdmin = $connLogin->prepare($sqlAdmin);
        $stmtAdmin->bind_param('ss', $usere, $passworde);
        $stmtAdmin->execute();
        $resultAdmin = $stmtAdmin->get_result()->fetch_assoc();
        $stmtAdmin->close();

        $sqlUser = "SELECT COUNT(*) as total FROM user WHERE id_user=aes_encrypt(?,'nur') AND password=aes_encrypt(?,'windi')";
        $stmtUser = $connLogin->prepare($sqlUser);
        $stmtUser->bind_param('ss', $usere, $passworde);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result()->fetch_assoc();
        $stmtUser->close();

        if ($resultAdmin['total'] > 0 || $resultUser['total'] > 0) {
            $_SESSION["ses_admin_login"] = true;
            header("Location: " . basename(__FILE__));
            exit;
        } else {
            $loginError = "Username atau Password salah!";
        }
        $connLogin->close();
    } else {
        $loginError = "Koneksi ke database login gagal.";
    }
}

// --- Inisialisasi variabel untuk data ---
$opDataTerlaksana = [];
$opDataRencana = [];
$error = '';
$namaInstansiHeader = 'Data Operasi';

// --- Logika Aplikasi Jika Sudah Login ---
if ($isLoggedIn) {
    // Ambil Data Instansi
    try {
        $connSik = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        if (!$connSik->connect_error) {
            $sqlSik = "SELECT nama_instansi, logo FROM setting LIMIT 1";
            $resultSik = $connSik->query($sqlSik);
            if ($resultSik && $resultSik->num_rows > 0) {
                $row = $resultSik->fetch_assoc();
                $namaInstansiHeader = htmlspecialchars($row['nama_instansi']);
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

    // Tentukan tab aktif
    $activeTab = $_GET['tab'] ?? 'terlaksana';

    // Koneksi utama untuk data
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($conn->connect_error) {
        $error = "Koneksi database gagal.";
    } else {
        // --- Logika untuk Tab Operasi Terlaksana ---
        if ($activeTab === 'terlaksana') {
            $tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01');
            $tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-t');
            $nama_perawatan = $_GET['nama_perawatan'] ?? '';

            function getOperasiTerlaksana($conn, $tgl_awal, $tgl_akhir, $perawatan) {
                $data = [];
                $sql = "SELECT reg_periksa.tgl_registrasi, reg_periksa.no_rawat, pasien.nm_pasien, 
                               rujuk_masuk.perujuk, rujuk_masuk.dokter_perujuk, paket_operasi.nm_perawatan
                        FROM reg_periksa
                        JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                        LEFT JOIN rujuk_masuk ON reg_periksa.no_rawat = rujuk_masuk.no_rawat
                        LEFT JOIN operasi ON reg_periksa.no_rawat = operasi.no_rawat
                        LEFT JOIN paket_operasi ON operasi.kode_paket = paket_operasi.kode_paket
                        WHERE reg_periksa.tgl_registrasi BETWEEN ? AND ?
                        AND paket_operasi.nm_perawatan LIKE ?
                        ORDER BY reg_periksa.tgl_registrasi ASC";
                $stmt = $conn->prepare($sql);
                $searchTerm = '%' . $perawatan . '%';
                $stmt->bind_param('sss', $tgl_awal, $tgl_akhir, $searchTerm);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) $data[] = $row;
                $stmt->close();
                return $data;
            }

            if (isset($_GET['action']) && $_GET['action'] === 'export_terlaksana') {
                $opDataExport = getOperasiTerlaksana($conn, $tanggal_awal, $tanggal_akhir, $nama_perawatan);
                header("Content-Type: application/vnd.ms-excel");
                $filename = "laporan_operasi_terlaksana_" . $tanggal_awal . "_sd_" . $tanggal_akhir . ".xls";
                header("Content-Disposition: attachment; filename=\"$filename\"");
                echo "Tgl Registrasi\tNo. Rawat\tNama Pasien\tPerujuk\tDokter Perujuk\tNama Perawatan\n";
                foreach ($opDataExport as $data) {
                    echo implode("\t", array_map('htmlspecialchars', $data)) . "\n";
                }
                $conn->close();
                exit;
            }
            $opDataTerlaksana = getOperasiTerlaksana($conn, $tanggal_awal, $tanggal_akhir, $nama_perawatan);
        }

        // --- Logika untuk Tab Rencana Operasi ---
        if ($activeTab === 'rencana') {
            function getRencanaOperasi($conn) {
                $data = [];
                $sql = "SELECT booking_operasi.no_rawat, reg_periksa.no_rkm_medis as RM, 
                               pasien.nm_pasien as 'nama_pasien', paket_operasi.nm_perawatan as 'nama_operasi', 
                               dokter.nm_dokter as 'nama_dokter', booking_operasi.tanggal as 'tgl_rencana', 
                               booking_operasi.jam_mulai, booking_operasi.status 
                        FROM booking_operasi
                        JOIN reg_periksa ON booking_operasi.no_rawat = reg_periksa.no_rawat
                        JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                        JOIN paket_operasi ON booking_operasi.kode_paket = paket_operasi.kode_paket
                        JOIN dokter ON booking_operasi.kd_dokter = dokter.kd_dokter
                        WHERE booking_operasi.tanggal > CURDATE()
                        ORDER BY booking_operasi.tanggal, booking_operasi.jam_mulai ASC";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) $data[] = $row;
                return $data;
            }
            
            if (isset($_GET['action']) && $_GET['action'] === 'export_rencana') {
                $opDataExport = getRencanaOperasi($conn);
                 header("Content-Type: application/vnd.ms-excel");
                $filename = "laporan_rencana_operasi_" . date('Y-m-d') . ".xls";
                header("Content-Disposition: attachment; filename=\"$filename\"");
                echo "No. Rawat\tRM\tNama Pasien\tNama Operasi\tNama Dokter\tTgl Rencana\tJam Mulai\tStatus\n";
                foreach ($opDataExport as $data) {
                    echo implode("\t", array_map('htmlspecialchars', $data)) . "\n";
                }
                $conn->close();
                exit;
            }
            $opDataRencana = getRencanaOperasi($conn);
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $namaInstansi; ?></title>
    <?php if ($favicon): ?><link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon"><?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .table-container { max-height: 65vh; overflow-y: auto; }
        .tab-link {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        .tab-link.active {
            border-color: #4f46e5; /* Indigo-600 */
            color: #4f46e5;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen py-10">
    <?php if ($isLoggedIn): ?>
    <div class="w-full max-w-7xl mx-auto bg-white p-6 md:p-8 rounded-xl shadow-lg">
        <div class="flex justify-between items-start mb-6">
            <div class="flex items-center space-x-4">
                <?php if ($logoSrc): ?><img src="<?php echo $logoSrc; ?>" alt="Logo Instansi" class="h-12 w-12 object-contain"><?php endif; ?>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800"><?php echo $namaInstansiHeader; ?></h1>
                    <p class="text-gray-500">Pencarian Data dan Rencana Operasi</p>
                </div>
            </div>
            <a href="?action=logout" class="text-sm text-gray-500 hover:text-indigo-600 font-medium">Logout</a>
        </div>
        
        <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endif; ?>

        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-4" aria-label="Tabs">
                <a href="?tab=terlaksana" class="tab-link <?php echo $activeTab === 'terlaksana' ? 'active' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                    Data Operasi Terlaksana
                </a>
                <a href="?tab=rencana" class="tab-link <?php echo $activeTab === 'rencana' ? 'active' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                    Rencana Operasi
                </a>
            </nav>
        </div>

        <div>
            <?php if ($activeTab === 'terlaksana'): ?>
            <div>
                <form action="" method="GET" class="bg-gray-50 p-4 rounded-lg mb-6 flex items-end space-x-4 flex-wrap">
                    <input type="hidden" name="tab" value="terlaksana">
                    <div>
                        <label for="tanggal_awal" class="block text-sm font-medium text-gray-700">Tanggal Awal</label>
                        <input type="date" name="tanggal_awal" value="<?php echo htmlspecialchars($tanggal_awal); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="tanggal_akhir" class="block text-sm font-medium text-gray-700">Tanggal Akhir</label>
                        <input type="date" name="tanggal_akhir" value="<?php echo htmlspecialchars($tanggal_akhir); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="nama_perawatan" class="block text-sm font-medium text-gray-700">Nama Paket Operasi</label>
                        <input type="text" name="nama_perawatan" value="<?php echo htmlspecialchars($nama_perawatan); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Contoh: Sectio Caesaria">
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Cari Data</button>
                </form>

                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-700">Hasil Pencarian</h2>
                    <a href="?action=export_terlaksana&tanggal_awal=<?php echo $tanggal_awal; ?>&tanggal_akhir=<?php echo $tanggal_akhir; ?>&nama_perawatan=<?php echo urlencode($nama_perawatan); ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        Export ke Excel
                    </a>
                </div>
                <div class="border rounded-lg shadow table-container">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tgl Registrasi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Rawat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Pasien</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Perujuk</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dokter Perujuk</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Perawatan</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($opDataTerlaksana)): ?>
                                <?php foreach ($opDataTerlaksana as $data): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($data['tgl_registrasi']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($data['no_rawat']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($data['nm_pasien']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($data['perujuk']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($data['dokter_perujuk']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($data['nm_perawatan']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada data.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php elseif ($activeTab === 'rencana'): ?>
            <div>
                 <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-700">Daftar Rencana Operasi (Booking)</h2>
                    <a href="?tab=rencana&action=export_rencana" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        Export ke Excel
                    </a>
                </div>
                <div class="border rounded-lg shadow table-container">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tgl Rencana</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jam Mulai</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Rawat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">RM</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Pasien</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Operasi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Dokter</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                         <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($opDataRencana)): ?>
                                <?php foreach ($opDataRencana as $data): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($data['tgl_rencana']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($data['jam_mulai']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($data['no_rawat']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($data['RM']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($data['nama_pasien']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($data['nama_operasi']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($data['nama_dokter']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($data['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada rencana operasi yang akan datang.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php else: ?>
    <div class="w-full max-w-md mx-auto bg-white p-8 rounded-xl shadow-lg">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Login Sistem</h1>
            <p class="text-gray-500">Silakan login menggunakan akun Khanza Anda.</p>
        </div>
        <?php if ($loginError): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6 text-center">
            <span><?php echo htmlspecialchars($loginError); ?></span>
        </div>
        <?php endif; ?>
        <form action="" method="post" class="space-y-6">
            <div>
                <label for="usere" class="block text-sm font-medium text-gray-700">Username</label>
                <input id="usere" name="usere" type="text" required class="mt-1 appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" autofocus>
            </div>
            <div>
                <label for="passworde" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="passworde" name="passworde" type="password" required class="mt-1 appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div>
                <button type="submit" name="BtnLogin" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Log In</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</body>
</html>
