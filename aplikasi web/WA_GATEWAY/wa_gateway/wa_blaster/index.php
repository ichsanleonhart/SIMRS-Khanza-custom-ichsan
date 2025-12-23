<?php
session_start();
// Menggunakan require_once untuk memuat konfigurasi terpusat
require_once 'config.php';

// --- Placeholder untuk fungsi dari sistem Anda ---
if (!function_exists('validTeks4')) {
    function validTeks4($text, $length) {
        return substr(htmlspecialchars(strip_tags($text)), 0, $length);
    }
}
// --- Akhir dari Placeholder ---

// --- Proses Logout ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

// --- Variabel Global ---
$namaInstansi = 'Kirim Pesan WA Massal';
$favicon = '';
$logoSrc = '';
$loginError = '';

// --- Cek Status Login ---
$isLoggedIn = isset($_SESSION["ses_admin_login"]) && $_SESSION["ses_admin_login"] === true;

// --- Proses Login Jika Form Disubmit ---
if (isset($_POST['BtnLogin'])) {
    // Menggunakan koneksi SIK dari config.php
    $connLogin = new mysqli($dbHostSik, $dbUserSik, $dbPassSik, $dbNameSik);
    if (!$connLogin->connect_error) {
        $usere = $connLogin->real_escape_string(validTeks4($_POST['usere'], 30));
        $passworde = $connLogin->real_escape_string(validTeks4($_POST['passworde'], 30));

        $sqlAdmin = "SELECT COUNT(passworde) as total FROM admin WHERE usere=aes_encrypt(?,'nur') AND passworde=aes_encrypt(?,'windi')";
        $stmtAdmin = $connLogin->prepare($sqlAdmin);
        $stmtAdmin->bind_param('ss', $usere, $passworde);
        $stmtAdmin->execute();
        $resultAdmin = $stmtAdmin->get_result()->fetch_assoc();
        $stmtAdmin->close();

        $sqlUser = "SELECT COUNT(password) as total FROM user WHERE id_user=aes_encrypt(?,'nur') AND password=aes_encrypt(?,'windi')";
        $stmtUser = $connLogin->prepare($sqlUser);
        $stmtUser->bind_param('ss', $usere, $passworde);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result()->fetch_assoc();
        $stmtUser->close();

        if ($resultAdmin['total'] > 0 || $resultUser['total'] > 0) {
            $_SESSION["ses_admin_login"] = true;
            header("Location: index.php");
            exit;
        } else {
            $loginError = "Username atau Password salah!";
        }
        $connLogin->close();
    } else {
        $loginError = "Koneksi ke database login gagal: " . $connLogin->connect_error;
    }
}

// Jika sudah login, lanjutkan
if ($isLoggedIn) {
    // --- Ambil Data Instansi dari Database SIK ---
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

    // --- Proses Pengiriman Pesan ---
    $message = '';
    $error = '';
    $insertedRowCount = 0;
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pesan'])) {
        $pesanMassal = trim($_POST['pesan']);
        $inputType = $_POST['inputType'] ?? 'csv';
        $numbersToProcess = [];

        if (empty($pesanMassal)) {
            $error = "Kolom 'Isi Pesan' tidak boleh kosong.";
        } else {
            // Kumpulkan nomor HP berdasarkan metode input
            if ($inputType === 'csv' && isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] == UPLOAD_ERR_OK) {
                $fileTmpName = $_FILES['csvFile']['tmp_name'];
                if (($handle = fopen($fileTmpName, "r")) !== FALSE) {
                    fgetcsv($handle); // Lewati header
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (!empty($data[0])) $numbersToProcess[] = $data[0];
                    }
                    fclose($handle);
                } else {
                    $error = "Tidak dapat membuka file CSV.";
                }
            } elseif ($inputType === 'manual' && !empty($_POST['manualNumbers'])) {
                $numbersToProcess = preg_split('/[\s,]+/', $_POST['manualNumbers'], -1, PREG_SPLIT_NO_EMPTY);
            }

            if (empty($numbersToProcess) && empty($error)) {
                $error = "Tidak ada nomor HP yang dimasukkan. Silakan unggah file atau isi kolom manual.";
            }

            if (empty($error) && !empty($numbersToProcess)) {
                // Menggunakan koneksi WA dari config.php
                $connWa = new mysqli($dbHostWa, $dbUserWa, $dbPassWa, $dbNameWa);
                if ($connWa->connect_error) {
                    $error = "Koneksi ke database WA gagal: " . $connWa->connect_error;
                } else {
                    try {
                        $sql = "INSERT INTO {$dbTableWa} (nowa, pesan, SOURCE, tanggal_jam) VALUES (?, ?, 'GATEWAY_MANUAL', NOW())";
                        $stmt = $connWa->prepare($sql);
                        if ($stmt === false) throw new Exception("Gagal mempersiapkan statement SQL: " . $connWa->error);

                        foreach ($numbersToProcess as $nowa_raw) {
                            $nowa_numeric = preg_replace('/[^0-9]/', '', trim($nowa_raw));
                            if (!empty($nowa_numeric)) {
                                if (substr($nowa_numeric, 0, 1) === '0') {
                                    $nowa_normalized = '62' . substr($nowa_numeric, 1);
                                } else {
                                    $nowa_normalized = $nowa_numeric;
                                }
                                $nowa_final = $nowa_normalized . '@c.us';
                                $stmt->bind_param("ss", $nowa_final, $pesanMassal);
                                if ($stmt->execute()) $insertedRowCount++;
                            }
                        }
                        $stmt->close();
                        if ($insertedRowCount > 0) {
                            $message = "Berhasil! Sebanyak {$insertedRowCount} pesan telah dimasukkan ke dalam antrian.";
                        } else {
                            $error = "Tidak ada nomor valid yang berhasil diproses.";
                        }
                    } catch (Exception $e) {
                        $error = "Terjadi kesalahan: " . $e->getMessage();
                    }
                    $connWa->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $namaInstansi; ?> - WA Blaster</title>
    <?php if ($favicon): ?>
        <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        textarea { resize: vertical; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen py-12">
    <?php if ($isLoggedIn): ?>
    <!-- TAMPILAN UTAMA JIKA SUDAH LOGIN -->
    <div class="w-full max-w-lg mx-auto bg-white p-8 rounded-xl shadow-lg relative">
        <div class="text-center mb-8">
            <div class="flex items-center justify-center space-x-4">
                <?php if ($logoSrc): ?>
                    <img src="<?php echo $logoSrc; ?>" alt="Logo Instansi" class="h-12 w-12 object-contain">
                <?php endif; ?>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800"><?php echo $namaInstansi; ?></h1>
                    <p class="text-gray-500">Kirim Pesan WhatsApp Massal</p>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <strong class="font-bold">Sukses!</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="index.php" method="post" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">1. Pilih Metode Input Nomor HP</label>
                <div class="flex items-center space-x-4">
                    <label for="inputTypeCsv" class="flex items-center">
                        <input type="radio" id="inputTypeCsv" name="inputType" value="csv" class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500" checked>
                        <span class="ml-2 text-sm text-gray-700">Upload File CSV</span>
                    </label>
                    <label for="inputTypeManual" class="flex items-center">
                        <input type="radio" id="inputTypeManual" name="inputType" value="manual" class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700">Input Manual</span>
                    </label>
                </div>
            </div>

            <div id="csvInputContainer">
                <label for="csvFile" class="block text-sm font-medium text-gray-700 mb-2">Upload File CSV</label>
                <input type="file" name="csvFile" id="csvFile" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <p class="mt-1 text-xs text-gray-500">File hanya berisi satu kolom nomor HP.</p>
            </div>

            <div id="manualInputContainer" class="hidden">
                <label for="manualNumbers" class="block text-sm font-medium text-gray-700">Masukkan Nomor HP Manual</label>
                <div class="mt-1">
                    <textarea id="manualNumbers" name="manualNumbers" rows="5" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" placeholder="Pisahkan nomor dengan koma, spasi, atau baris baru. Contoh: 08123, 08567"></textarea>
                </div>
            </div>
            
            <div>
                <label for="pesan" class="block text-sm font-medium text-gray-700">2. Tulis Isi Pesan</label>
                <div class="mt-1">
                    <textarea id="pesan" name="pesan" rows="5" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" placeholder="Ketik pesan Anda di sini..." required></textarea>
                </div>
            </div>
            <div>
                <button type="submit" name="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Kirim ke Antrian
                </button>
            </div>
        </form>

        <!-- Menu Navigasi Footer -->
        <div class="border-t mt-8 pt-6">
            <div class="flex justify-center items-center space-x-6 text-sm text-gray-500">
                <a href="cari_pasien_pulang.php" class="hover:text-indigo-600">Cari Pasien</a>
                <span>|</span>
                <a href="manage_outbox.php" class="hover:text-indigo-600">Kelola Outbox</a>
                <span>|</span>
                <a href="?action=logout" class="hover:text-indigo-600">Logout</a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- TAMPILAN FORM LOGIN -->
    <div class="w-full max-w-md mx-auto bg-white p-8 rounded-xl shadow-lg">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Login WA Blaster</h1>
            <p class="text-gray-500">Aplikasi pengirim Whatsapp massal, silakan login menggunakan akun Khanza Anda.</p>
        </div>

        <?php if ($loginError): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6 text-center" role="alert">
                <span><?php echo htmlspecialchars($loginError); ?></span>
            </div>
        <?php endif; ?>

        <form action="index.php" method="post" class="space-y-6">
            <div>
                <label for="usere" class="block text-sm font-medium text-gray-700">Username</label>
                <div class="mt-1">
                    <input id="usere" name="usere" type="text" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" autofocus>
                </div>
            </div>
            <div>
                <label for="passworde" class="block text-sm font-medium text-gray-700">Password</label>
                <div class="mt-1">
                    <input id="passworde" name="passworde" type="password" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
            </div>
            <div>
                <button type="submit" name="BtnLogin" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Log In
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <script>
        // Jalankan script hanya jika pengguna sudah login
        <?php if ($isLoggedIn): ?>
        document.addEventListener('DOMContentLoaded', function () {
            const csvRadio = document.getElementById('inputTypeCsv');
            const manualRadio = document.getElementById('inputTypeManual');
            const csvContainer = document.getElementById('csvInputContainer');
            const manualContainer = document.getElementById('manualInputContainer');
            const csvFileInput = document.getElementById('csvFile');
            const manualTextarea = document.getElementById('manualNumbers');

            function toggleInputs() {
                if (csvRadio.checked) {
                    csvContainer.classList.remove('hidden');
                    manualContainer.classList.add('hidden');
                    csvFileInput.required = true;
                    manualTextarea.required = false;
                } else {
                    csvContainer.classList.add('hidden');
                    manualContainer.classList.remove('hidden');
                    csvFileInput.required = false;
                    manualTextarea.required = true;
                }
            }

            csvRadio.addEventListener('change', toggleInputs);
            manualRadio.addEventListener('change', toggleInputs);

            toggleInputs();
        });
        <?php endif; ?>
    </script>
</body>
</html>

