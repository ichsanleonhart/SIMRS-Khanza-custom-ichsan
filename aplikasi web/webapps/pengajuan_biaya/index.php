<?php
session_start();
if (!isset($_SESSION['username_pengajuan'])) {
    header('Location: login.php');
    exit;
}

$logged_in_user = $_SESSION['username_pengajuan'];
$logged_in_nama = $_SESSION['nama_pengajuan'];
$is_admin = $_SESSION['is_admin_pengajuan'];
$user_bidang = $_SESSION['bidang_pengajuan'] ?? '';
$user_departemen = $_SESSION['departemen_pengajuan'] ?? '';

// Include file konfigurasi database
include '../conf/conf.php';
$konektor = bukakoneksi();
if (!$konektor) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Buat CSRF token jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Pengaturan path
$upload_dir = 'foto/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// 2. MENGAMBIL DATA GLOBAL (BRANDING)
// -----------------------------------------------------------------------------
$nama_instansi = "RS";
$alamat_instansi = "Alamat RS";
$kontak_instansi = "Kontak RS";
$logo_path = "logo.php?v=logo";
$favicon_path = "logo.php?v=favicon";

try {
    $setting_sql = "SELECT nama_instansi, alamat_instansi, kontak FROM setting LIMIT 1";
    $setting_result = mysqli_query($konektor, $setting_sql);
    if ($setting_row = mysqli_fetch_assoc($setting_result)) {
        $nama_instansi = $setting_row['nama_instansi'];
        $alamat_instansi = $setting_row['alamat_instansi'];
        $kontak_instansi = $setting_row['kontak'];
    }
} catch (Exception $e) {
    // Biarkan nilai default jika query gagal
}

// 3. LOGIKA PEMROSESAN FORM (CRUD)
// -----------------------------------------------------------------------------
$action = $_GET['action'] ?? 'view'; // Aksi default adalah 'view'
$error_message = '';
$success_message = '';

// Fungsi untuk validasi CSRF
function validate_csrf($token) {
    if (empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

// Fungsi untuk generate No. Pengajuan otomatis
function autoNomor($konektor, $tanggal) {
    $sql = "SELECT IFNULL(MAX(CONVERT(RIGHT(no_pengajuan,3),SIGNED)),0) 
            FROM pengajuan_biaya 
            WHERE tanggal = ?";
    $stmt = mysqli_prepare($konektor, $sql);
    mysqli_stmt_bind_param($stmt, "s", $tanggal);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_array($result);
    $max_num = $row[0];
    mysqli_stmt_close($stmt);

    $next_num = $max_num + 1;
    $no_urut = sprintf('%03s', $next_num);
    
    // Format tanggal YYYYMMDD dari format YYYY-MM-DD
    $tgl_parts = explode('-', $tanggal);
    $tgl_formatted = $tgl_parts[0] . $tgl_parts[1] . $tgl_parts[2];

    return "PK" . $tgl_formatted . $no_urut;
}

// Fungsi untuk menangani upload file
function handleFileUpload($file, $no_pengajuan, $upload_dir) {
    if (isset($file) && $file['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $file_type = mime_content_type($file['tmp_name']);
        
        if (!in_array($file_type, $allowed_types)) {
            return ['error' => 'Format file tidak valid. Hanya JPG, PNG, GIF, atau PDF yang diizinkan.'];
        }

        if ($file['size'] > 5000000) { // Batas 5MB
            return ['error' => 'Ukuran file terlalu besar. Maksimal 5MB.'];
        }

        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = $no_pengajuan . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            return ['success' => true, 'path' => $upload_path];
        } else {
            return ['error' => 'Gagal memindahkan file yang diunggah.'];
        }
    }
    return ['success' => false]; // Tidak ada file atau error
}

// LOGIKA CRUD
switch ($action) {
    case 'save':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf($_POST['csrf_token'])) {
            // Ambil data dari form
            $no_pengajuan = $_POST['no_pengajuan'];
            $tanggal = $_POST['tanggal'];
            $nik = $is_admin ? $_POST['nik'] : $logged_in_user; // Admin bisa input NIK, user biasa otomatis
            $urgensi = $_POST['urgensi'];
            $uraian_latar_belakang = $_POST['uraian_latar_belakang'];
            $tujuan_pengajuan = $_POST['tujuan_pengajuan'];
            $target_sasaran = $_POST['target_sasaran'];
            $lokasi_kegiatan = $_POST['lokasi_kegiatan'];
            $jumlah = (double)str_replace(['.', ','], ['', '.'], $_POST['jumlah']);
            $harga = (double)str_replace(['.', ','], ['', '.'], $_POST['harga']);
            $total = $jumlah * $harga;
            $keterangan = $_POST['keterangan'];
            $nik_pj = $_POST['nik_pj'];
            $status = "Proses Pengajuan"; // Status default

            // Validasi Sederhana
            if (empty($no_pengajuan) || empty($tanggal) || empty($nik) || empty($nik_pj) || empty($uraian_latar_belakang) || empty($tujuan_pengajuan) || $jumlah <= 0 || $harga <= 0) {
                $error_message = "Data tidak lengkap. Pastikan semua field yang wajib diisi telah terisi dengan benar.";
                $action = 'add'; // Kembali ke form add
                break;
            }

            // Mulai transaksi
            mysqli_begin_transaction($konektor);

            try {
                // 1. Insert ke pengajuan_biaya
                $sql_pengajuan = "INSERT INTO pengajuan_biaya 
                                  (no_pengajuan, tanggal, nik, urgensi, uraian_latar_belakang, tujuan_pengajuan, 
                                   target_sasaran, lokasi_kegiatan, jumlah, harga, total, keterangan, nik_pj, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_pengajuan = mysqli_prepare($konektor, $sql_pengajuan);
                mysqli_stmt_bind_param($stmt_pengajuan, "ssssssssdddsss", 
                    $no_pengajuan, $tanggal, $nik, $urgensi, $uraian_latar_belakang, $tujuan_pengajuan, 
                    $target_sasaran, $lokasi_kegiatan, $jumlah, $harga, $total, $keterangan, $nik_pj, $status);
                
                if (!mysqli_stmt_execute($stmt_pengajuan)) {
                    throw new Exception("Gagal menyimpan data pengajuan: " . mysqli_stmt_error($stmt_pengajuan));
                }
                mysqli_stmt_close($stmt_pengajuan);

                // 2. Handle file upload
                $upload_result = handleFileUpload($_FILES['foto'], $no_pengajuan, $upload_dir);
                if (isset($upload_result['error'])) {
                    throw new Exception($upload_result['error']);
                }

                // 3. Insert ke pengajuan_biaya_foto jika upload berhasil
                if ($upload_result['success']) {
                    $sql_foto = "INSERT INTO pengajuan_biaya_foto (no_pengajuan, gambar) VALUES (?, ?)";
                    $stmt_foto = mysqli_prepare($konektor, $sql_foto);
                    mysqli_stmt_bind_param($stmt_foto, "ss", $no_pengajuan, $upload_result['path']);
                    
                    if (!mysqli_stmt_execute($stmt_foto)) {
                        throw new Exception("Gagal menyimpan data foto: " . mysqli_stmt_error($stmt_foto));
                    }
                    mysqli_stmt_close($stmt_foto);
                }

                // Jika semua berhasil, commit transaksi
                mysqli_commit($konektor);
                $success_message = "Data pengajuan biaya berhasil disimpan.";
                $action = 'view'; // Kembali ke tampilan daftar

            } catch (Exception $e) {
                // Jika ada error, rollback transaksi
                mysqli_rollback($konektor);
                $error_message = $e->getMessage();
                $action = 'add'; // Kembali ke form add
            }
            
        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $error_message = "Error: Sesi tidak valid. Silakan coba lagi.";
            }
            $action = 'add'; // Kembali ke form add
        }
        break;

    case 'update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf($_POST['csrf_token'])) {
            // Ambil data dari form
            $no_pengajuan = $_POST['no_pengajuan'];
            $tanggal = $_POST['tanggal'];
            $nik = $is_admin ? $_POST['nik'] : $logged_in_user;
            $urgensi = $_POST['urgensi'];
            $uraian_latar_belakang = $_POST['uraian_latar_belakang'];
            $tujuan_pengajuan = $_POST['tujuan_pengajuan'];
            $target_sasaran = $_POST['target_sasaran'];
            $lokasi_kegiatan = $_POST['lokasi_kegiatan'];
            $jumlah = (double)str_replace(['.', ','], ['', '.'], $_POST['jumlah']);
            $harga = (double)str_replace(['.', ','], ['', '.'], $_POST['harga']);
            $total = $jumlah * $harga;
            $keterangan = $_POST['keterangan'];
            $nik_pj = $_POST['nik_pj'];
            $status_lama = $_POST['status_lama']; // Status saat data diambil

            // Cek Otorisasi
            $auth_sql = "SELECT nik, nik_pj, status FROM pengajuan_biaya WHERE no_pengajuan = ?";
            $stmt_auth = mysqli_prepare($konektor, $auth_sql);
            mysqli_stmt_bind_param($stmt_auth, "s", $no_pengajuan);
            mysqli_stmt_execute($stmt_auth);
            $result_auth = mysqli_stmt_get_result($stmt_auth);
            $row_auth = mysqli_fetch_assoc($result_auth);
            mysqli_stmt_close($stmt_auth);

            if (!$row_auth) {
                $error_message = "Data tidak ditemukan.";
                $action = 'view';
                break;
            }

            // Hanya boleh edit jika admin, pengaju, atau PJ
            if (!$is_admin && $row_auth['nik'] != $logged_in_user && $row_auth['nik_pj'] != $logged_in_user) {
                $error_message = "Anda tidak memiliki hak untuk mengubah data ini.";
                $action = 'view';
                break;
            }

            // Hanya boleh edit jika status masih 'Proses Pengajuan'
            if ($row_auth['status'] != 'Proses Pengajuan' && !$is_admin) {
                $error_message = "Data tidak dapat diubah karena sudah diproses (Status: " . $row_auth['status'] . ").";
                $action = 'view';
                break;
            }
            
            // Validasi Sederhana
            if (empty($no_pengajuan) || empty($tanggal) || empty($nik) || empty($nik_pj) || empty($uraian_latar_belakang) || empty($tujuan_pengajuan) || $jumlah <= 0 || $harga <= 0) {
                $error_message = "Data tidak lengkap. Pastikan semua field yang wajib diisi telah terisi dengan benar.";
                $action = 'edit'; // Kembali ke form edit
                $_GET['id'] = $no_pengajuan; // Pastikan ID tetap ada untuk form edit
                break;
            }

            // Mulai transaksi
            mysqli_begin_transaction($konektor);

            try {
                // 1. Update pengajuan_biaya
                $sql_update = "UPDATE pengajuan_biaya SET 
                                tanggal = ?, nik = ?, urgensi = ?, uraian_latar_belakang = ?, tujuan_pengajuan = ?, 
                                target_sasaran = ?, lokasi_kegiatan = ?, jumlah = ?, harga = ?, total = ?, 
                                keterangan = ?, nik_pj = ?
                               WHERE no_pengajuan = ?";
                $stmt_update = mysqli_prepare($konektor, $sql_update);
                mysqli_stmt_bind_param($stmt_update, "sssssssdddsss", 
                    $tanggal, $nik, $urgensi, $uraian_latar_belakang, $tujuan_pengajuan, 
                    $target_sasaran, $lokasi_kegiatan, $jumlah, $harga, $total, 
                    $keterangan, $nik_pj, $no_pengajuan);

                if (!mysqli_stmt_execute($stmt_update)) {
                    throw new Exception("Gagal memperbarui data pengajuan: " . mysqli_stmt_error($stmt_update));
                }
                mysqli_stmt_close($stmt_update);

                // 2. Handle file upload (jika ada file baru)
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                    $upload_result = handleFileUpload($_FILES['foto'], $no_pengajuan, $upload_dir);
                    if (isset($upload_result['error'])) {
                        throw new Exception($upload_result['error']);
                    }

                    if ($upload_result['success']) {
                        // Hapus file lama jika ada
                        $old_file_path = $_POST['foto_lama'];
                        if (!empty($old_file_path) && file_exists($old_file_path)) {
                            unlink($old_file_path);
                        }

                        // Cek apakah data foto sudah ada (UPSERT)
                        $sql_check_foto = "SELECT no_pengajuan FROM pengajuan_biaya_foto WHERE no_pengajuan = ?";
                        $stmt_check = mysqli_prepare($konektor, $sql_check_foto);
                        mysqli_stmt_bind_param($stmt_check, "s", $no_pengajuan);
                        mysqli_stmt_execute($stmt_check);
                        $result_check = mysqli_stmt_get_result($stmt_check);
                        
                        if (mysqli_num_rows($result_check) > 0) {
                            // Update
                            $sql_foto_update = "UPDATE pengajuan_biaya_foto SET gambar = ? WHERE no_pengajuan = ?";
                            $stmt_foto = mysqli_prepare($konektor, $sql_foto_update);
                            mysqli_stmt_bind_param($stmt_foto, "ss", $upload_result['path'], $no_pengajuan);
                        } else {
                            // Insert
                            $sql_foto_insert = "INSERT INTO pengajuan_biaya_foto (no_pengajuan, gambar) VALUES (?, ?)";
                            $stmt_foto = mysqli_prepare($konektor, $sql_foto_insert);
                            mysqli_stmt_bind_param($stmt_foto, "ss", $no_pengajuan, $upload_result['path']);
                        }
                        
                        if (!mysqli_stmt_execute($stmt_foto)) {
                            throw new Exception("Gagal menyimpan data foto: " . mysqli_stmt_error($stmt_foto));
                        }
                        mysqli_stmt_close($stmt_foto);
                        mysqli_stmt_close($stmt_check);
                    }
                }

                // Commit transaksi
                mysqli_commit($konektor);
                $success_message = "Data pengajuan biaya berhasil diperbarui.";
                $action = 'view'; // Kembali ke tampilan daftar

            } catch (Exception $e) {
                // Rollback jika ada error
                mysqli_rollback($konektor);
                $error_message = $e->getMessage();
                $action = 'edit'; // Kembali ke form edit
                $_GET['id'] = $no_pengajuan; // Pastikan ID tetap ada
            }

        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $error_message = "Error: Sesi tidak valid. Silakan coba lagi.";
            }
            $action = 'edit'; // Kembali ke form edit
            $_GET['id'] = $_POST['no_pengajuan'] ?? $_GET['id']; // Pastikan ID tetap ada
        }
        break;

    case 'delete':
        $no_pengajuan = $_GET['id'] ?? '';
        $token = $_GET['token'] ?? '';

        if (!validate_csrf($token)) {
            $error_message = "Error: Sesi tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman dan coba lagi.";
            $action = 'view';
            break;
        }

        if (empty($no_pengajuan)) {
            $error_message = "No. Pengajuan tidak valid.";
            $action = 'view';
            break;
        }

        // Cek Otorisasi
        $auth_sql = "SELECT nik, nik_pj, status FROM pengajuan_biaya WHERE no_pengajuan = ?";
        $stmt_auth = mysqli_prepare($konektor, $auth_sql);
        mysqli_stmt_bind_param($stmt_auth, "s", $no_pengajuan);
        mysqli_stmt_execute($stmt_auth);
        $result_auth = mysqli_stmt_get_result($stmt_auth);
        $row_auth = mysqli_fetch_assoc($result_auth);
        mysqli_stmt_close($stmt_auth);

        if (!$row_auth) {
            $error_message = "Data tidak ditemukan.";
            $action = 'view';
            break;
        }

        // Hanya boleh hapus jika admin, pengaju, atau PJ
        if (!$is_admin && $row_auth['nik'] != $logged_in_user && $row_auth['nik_pj'] != $logged_in_user) {
            $error_message = "Anda tidak memiliki hak untuk menghapus data ini.";
            $action = 'view';
            break;
        }
        
        // Hanya boleh hapus jika status masih 'Proses Pengajuan'
        if ($row_auth['status'] != 'Proses Pengajuan' && !$is_admin) {
            $error_message = "Data tidak dapat dihapus karena sudah diproses (Status: " . $row_auth['status'] . ").";
            $action = 'view';
            break;
        }

        // Mulai transaksi
        mysqli_begin_transaction($konektor);

        try {
            // 1. Ambil path file dan hapus file
            $sql_get_foto = "SELECT gambar FROM pengajuan_biaya_foto WHERE no_pengajuan = ?";
            $stmt_get_foto = mysqli_prepare($konektor, $sql_get_foto);
            mysqli_stmt_bind_param($stmt_get_foto, "s", $no_pengajuan);
            mysqli_stmt_execute($stmt_get_foto);
            $result_foto = mysqli_stmt_get_result($stmt_get_foto);
            if ($row_foto = mysqli_fetch_assoc($result_foto)) {
                if (!empty($row_foto['gambar']) && file_exists($row_foto['gambar'])) {
                    unlink($row_foto['gambar']); // Hapus file fisik
                }
            }
            mysqli_stmt_close($stmt_get_foto);

            // 2. Hapus data dari pengajuan_biaya_foto (atau biarkan cascade jika sudah di-set)
            // Manual delete untuk keamanan jika FK ON DELETE CASCADE tidak diset
            $sql_del_foto = "DELETE FROM pengajuan_biaya_foto WHERE no_pengajuan = ?";
            $stmt_del_foto = mysqli_prepare($konektor, $sql_del_foto);
            mysqli_stmt_bind_param($stmt_del_foto, "s", $no_pengajuan);
            mysqli_stmt_execute($stmt_del_foto);
            mysqli_stmt_close($stmt_del_foto);

            // 3. Hapus data dari pengajuan_biaya
            $sql_del_pengajuan = "DELETE FROM pengajuan_biaya WHERE no_pengajuan = ?";
            $stmt_del_pengajuan = mysqli_prepare($konektor, $sql_del_pengajuan);
            mysqli_stmt_bind_param($stmt_del_pengajuan, "s", $no_pengajuan);
            
            if (!mysqli_stmt_execute($stmt_del_pengajuan)) {
                 throw new Exception("Gagal menghapus data pengajuan: " . mysqli_stmt_error($stmt_del_pengajuan));
            }
            mysqli_stmt_close($stmt_del_pengajuan);

            // Commit
            mysqli_commit($konektor);
            $success_message = "Data pengajuan biaya berhasil dihapus.";
        
        } catch (Exception $e) {
            // Rollback
            mysqli_rollback($konektor);
            $error_message = $e->getMessage();
        }

        $action = 'view';
        break;
}

// 4. LOGIKA PENCARIAN & TAMPIL DATA (UNTUK 'view' action)
// -----------------------------------------------------------------------------
$list_pengajuan = [];
$total_anggaran = 0;

if ($action === 'view') {
    $sql_tampil = "
        SELECT 
            p.no_pengajuan, p.tanggal, p.nik, peg1.nama as namapengaju, peg1.bidang, peg1.departemen,
            p.urgensi, p.uraian_latar_belakang, p.tujuan_pengajuan, p.target_sasaran, p.lokasi_kegiatan,
            p.jumlah, p.harga, p.total, p.keterangan, p.nik_pj, peg2.nama as namapj, p.status,
            pf.gambar 
        FROM pengajuan_biaya p
        INNER JOIN pegawai AS peg1 ON p.nik = peg1.nik
        INNER JOIN pegawai AS peg2 ON p.nik_pj = peg2.nik
        LEFT JOIN pengajuan_biaya_foto AS pf ON p.no_pengajuan = pf.no_pengajuan
        WHERE 1=1
    ";

    // Filter Tanggal
    $tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-d');
    $tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
    $sql_tampil .= " AND p.tanggal BETWEEN ? AND ?";
    $params = [$tgl_awal, $tgl_akhir];
    $types = "ss";

    // Filter User (Non-Admin hanya bisa lihat pengajuan sendiri atau yg dia PJ-kan)
    if (!$is_admin) {
        $sql_tampil .= " AND (p.nik = ? OR p.nik_pj = ?)";
        $params[] = $logged_in_user;
        $params[] = $logged_in_user;
        $types .= "ss";
    }

    // Filter Pencarian (Key Word)
    $keyword = $_GET['keyword'] ?? '';
    if (!empty($keyword)) {
        $sql_tampil .= " AND (
            p.no_pengajuan LIKE ? OR 
            p.nik LIKE ? OR peg1.nama LIKE ? OR peg1.bidang LIKE ? OR peg1.departemen LIKE ? OR
            p.urgensi LIKE ? OR p.uraian_latar_belakang LIKE ? OR p.tujuan_pengajuan LIKE ? OR
            p.lokasi_kegiatan LIKE ? OR p.keterangan LIKE ? OR p.nik_pj LIKE ? OR 
            peg2.nama LIKE ? OR p.status LIKE ?
        )";
        $keyword_param = "%" . $keyword . "%";
        for ($i = 0; $i < 13; $i++) {
            $params[] = $keyword_param;
            $types .= "s";
        }
    }

    $sql_tampil .= " ORDER BY p.tanggal DESC, p.no_pengajuan DESC";
    
    $stmt_tampil = mysqli_prepare($konektor, $sql_tampil);
    if ($stmt_tampil) {
        mysqli_stmt_bind_param($stmt_tampil, $types, ...$params);
        mysqli_stmt_execute($stmt_tampil);
        $result_tampil = mysqli_stmt_get_result($stmt_tampil);
        while ($row = mysqli_fetch_assoc($result_tampil)) {
            $list_pengajuan[] = $row;
            $total_anggaran += $row['total'];
        }
        mysqli_stmt_close($stmt_tampil);
    } else {
        $error_message = "Query tampil data gagal: " . mysqli_error($konektor);
    }
}

// 5. PERSIAPAN DATA UNTUK FORM (jika action 'add' atau 'edit')
// -----------------------------------------------------------------------------
$data_form = [
    'no_pengajuan' => '',
    'tanggal' => date('Y-m-d'),
    'nik' => $logged_in_user,
    'namapengaju' => $logged_in_nama,
    'bidang' => $user_bidang,
    'departemen' => $user_departemen,
    'urgensi' => 'Biasa',
    'uraian_latar_belakang' => '',
    'tujuan_pengajuan' => '',
    'target_sasaran' => '',
    'lokasi_kegiatan' => '',
    'jumlah' => 0,
    'harga' => 0,
    'total' => 0,
    'keterangan' => '',
    'nik_pj' => '',
    'namapj' => '',
    'status' => 'Proses Pengajuan',
    'gambar' => ''
];

if ($action === 'add') {
    // Generate nomor baru untuk form add
    $data_form['no_pengajuan'] = autoNomor($konektor, date('Y-m-d'));
} elseif ($action === 'edit' && isset($_GET['id'])) {
    $no_pengajuan_edit = $_GET['id'];
    $sql_edit = "
        SELECT 
            p.no_pengajuan, p.tanggal, p.nik, peg1.nama as namapengaju, peg1.bidang, peg1.departemen,
            p.urgensi, p.uraian_latar_belakang, p.tujuan_pengajuan, p.target_sasaran, p.lokasi_kegiatan,
            p.jumlah, p.harga, p.total, p.keterangan, p.nik_pj, peg2.nama as namapj, p.status,
            pf.gambar
        FROM pengajuan_biaya p
        INNER JOIN pegawai AS peg1 ON p.nik = peg1.nik
        INNER JOIN pegawai AS peg2 ON p.nik_pj = peg2.nik
        LEFT JOIN pengajuan_biaya_foto AS pf ON p.no_pengajuan = pf.no_pengajuan
        WHERE p.no_pengajuan = ?
    ";
    $stmt_edit = mysqli_prepare($konektor, $sql_edit);
    mysqli_stmt_bind_param($stmt_edit, "s", $no_pengajuan_edit);
    mysqli_stmt_execute($stmt_edit);
    $result_edit = mysqli_stmt_get_result($stmt_edit);
    
    if ($row_edit = mysqli_fetch_assoc($result_edit)) {
        // Otorisasi: Cek apakah user boleh mengedit
        if (!$is_admin && $row_edit['nik'] != $logged_in_user && $row_edit['nik_pj'] != $logged_in_user) {
            $error_message = "Anda tidak memiliki hak untuk mengubah data ini.";
            $action = 'view';
        } 
        // Hanya admin yang bisa edit jika status BUKAN 'Proses Pengajuan'
        elseif ($row_edit['status'] != 'Proses Pengajuan' && !$is_admin) {
            $error_message = "Data tidak dapat diubah karena sudah diproses (Status: " . $row_edit['status'] . ").";
            $action = 'view';
        }
        else {
            $data_form = $row_edit;
            $data_form['tanggal'] = date('Y-m-d', strtotime($data_form['tanggal'])); // Format tanggal untuk input
        }
    } else {
        $error_message = "Data pengajuan tidak ditemukan.";
        $action = 'view';
    }
    mysqli_stmt_close($stmt_edit);
}

// Ambil daftar pegawai untuk lookup PJ
$pegawai_list = [];
$sql_pegawai = "SELECT nik, nama FROM pegawai WHERE stts_aktif = 'AKTIF' ORDER BY nama";
$result_pegawai = mysqli_query($konektor, $sql_pegawai);
while($row_pegawai = mysqli_fetch_assoc($result_pegawai)) {
    $pegawai_list[] = $row_pegawai;
}

mysqli_close($konektor); // Tutup koneksi
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Biaya - <?php echo htmlspecialchars($nama_instansi, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" href="<?php echo $favicon_path; ?>" type="image/png">
    <link rel="stylesheet" href="style.css">
    <script>
        function hitungTotal() {
            var jumlah = document.getElementById('jumlah').value.replace(/[^0-9]/g, '');
            var harga = document.getElementById('harga').value.replace(/[^0-9]/g, '');
            
            var jumlahVal = parseFloat(jumlah) || 0;
            var hargaVal = parseFloat(harga) || 0;
            
            var total = jumlahVal * hargaVal;
            
            // Format sebagai mata uang Rupiah
            var formatter = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            });
            
            document.getElementById('total').value = formatter.format(total);
        }

        function konfirmasiHapus(noPengajuan, token) {
            if (confirm('Apakah Anda yakin ingin menghapus data pengajuan nomor ' + noPengajuan + '?')) {
                window.location.href = 'index.php?action=delete&id=' + noPengajuan + '&token=' + token;
            }
        }
        
        function formatRupiah(angka, prefix = 'Rp ') {
            var number_string = angka.replace(/[^,\d]/g, '').toString(),
                split = number_string.split(','),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
            return prefix + rupiah;
        }

        function setupFormatRupiah() {
            var hargaInput = document.getElementById('harga');
            if (hargaInput) {
                hargaInput.addEventListener('keyup', function(e) {
                    hargaInput.value = formatRupiah(this.value, '');
                    hitungTotal();
                });
            }
        }
        
        document.addEventListener('DOMContentLoaded', setupFormatRupiah);
    </script>
	
	<link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css"
    />

    <style>
        .fancybox__button--flip,
        .fancybox__button--rotate {
            display: block; /* Secara default tersembunyi, kita tampilkan */
        }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>	
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    </head>

</head>
	
</head>
<body>

    <header class="header">
        <div class="logo">
            <img src="<?php echo $logo_path; ?>" alt="Logo">
            <h1><?php echo htmlspecialchars($nama_instansi, ENT_QUOTES, 'UTF-8'); ?></h1>
        </div>
        <div class="user-info">
            Selamat datang, <strong><?php echo htmlspecialchars($logged_in_nama, ENT_QUOTES, 'UTF-8'); ?></strong>
            (<?php echo htmlspecialchars($logged_in_user, ENT_QUOTES, 'UTF-8'); ?>)
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="content">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php // Tampilkan Form jika action=add atau action=edit
            if ($action === 'add' || $action === 'edit'): 
                $form_action = ($action === 'add') ? 'index.php?action=save' : 'index.php?action=update';
                $form_title = ($action === 'add') ? 'Formulir Pengajuan Biaya Baru' : 'Edit Pengajuan Biaya';
            ?>
                <div class="form-section">
                    <h2><?php echo $form_title; ?></h2>
                    <form action="<?php echo $form_action; ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="no_pengajuan" value="<?php echo htmlspecialchars($data_form['no_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="status_lama" value="<?php echo htmlspecialchars($data_form['status'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="foto_lama" value="<?php echo htmlspecialchars($data_form['gambar'], ENT_QUOTES, 'UTF-8'); ?>">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="no_pengajuan">No. Pengajuan</label>
                                <input type="text" id="no_pengajuan" value="<?php echo htmlspecialchars($data_form['no_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="tanggal">Tanggal Pengajuan</label>
                                <input type="date" id="tanggal" name="tanggal" value="<?php echo htmlspecialchars($data_form['tanggal'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="nik">Diajukan Oleh (NIK)</label>
                                <input type="text" id="nik" name="nik" value="<?php echo htmlspecialchars($data_form['nik'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $is_admin ? '' : 'readonly'; ?> required>
                            </div>
                            <div class="form-group">
                                <label for="namapengaju">Nama Pengaju</label>
                                <input type="text" id="namapengaju" name="namapengaju" value="<?php echo htmlspecialchars($data_form['namapengaju'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="bidang">Bidang</label>
                                <input type="text" id="bidang" name="bidang" value="<?php echo htmlspecialchars($data_form['bidang'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="departemen">Departemen</label>
                                <input type="text" id="departemen" name="departemen" value="<?php echo htmlspecialchars($data_form['departemen'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>

                            <div class="form-group full-width">
                                <label for="nik_pj">P.J. Terkait</label>
                            <!--<select id="nik_pj" name="nik_pj" required>-->
								<select id="select-pj" name="nik_pj" placeholder="-- Pilih P.J. Terkait --" required>
                                    <option value="">-- Pilih P.J. Terkait --</option>
                                    <?php foreach ($pegawai_list as $pegawai): ?>
                                    <option value="<?php echo htmlspecialchars($pegawai['nik'], ENT_QUOTES, 'UTF-8'); ?>" 
                                            <?php echo ($data_form['nik_pj'] == $pegawai['nik']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($pegawai['nama'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($pegawai['nik'], ENT_QUOTES, 'UTF-8'); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group full-width">
                                <label for="uraian_latar_belakang">Uraian Latar Belakang</label>
                                <textarea id="uraian_latar_belakang" name="uraian_latar_belakang" required><?php echo htmlspecialchars($data_form['uraian_latar_belakang'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            <div class="form-group full-width">
                                <label for="tujuan_pengajuan">Tujuan Pengajuan</label>
                                <textarea id="tujuan_pengajuan" name="tujuan_pengajuan" required><?php echo htmlspecialchars($data_form['tujuan_pengajuan'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="target_sasaran">Target Sasaran</label>
                                <input type="text" id="target_sasaran" name="target_sasaran" value="<?php echo htmlspecialchars($data_form['target_sasaran'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="lokasi_kegiatan">Lokasi Kegiatan</label>
                                <input type="text" id="lokasi_kegiatan" name="lokasi_kegiatan" value="<?php echo htmlspecialchars($data_form['lokasi_kegiatan'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="urgensi">Urgensi</label>
                                <select id="urgensi" name="urgensi">
                                    <option value="Cito" <?php echo ($data_form['urgensi'] == 'Cito') ? 'selected' : ''; ?>>Cito</option>
                                    <option value="Emergensi" <?php echo ($data_form['urgensi'] == 'Emergensi') ? 'selected' : ''; ?>>Emergensi</option>
                                    <option value="Biasa" <?php echo ($data_form['urgensi'] == 'Biasa') ? 'selected' : ''; ?>>Biasa</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="keterangan">Keterangan</label>
                                <input type="text" id="keterangan" name="keterangan" value="<?php echo htmlspecialchars($data_form['keterangan'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="jumlah">Jumlah</label>
                                <input type="text" id="jumlah" name="jumlah" value="<?php echo number_format($data_form['jumlah'], 0, ',', '.'); ?>" onkeyup="hitungTotal()" required>
                            </div>
                            <div class="form-group">
                                <label for="harga">Harga Satuan (Rp)</label>
                                <input type="text" id="harga" name="harga" value="<?php echo number_format($data_form['harga'], 0, ',', '.'); ?>" onkeyup="hitungTotal()" required>
                            </div>
                            <div class="form-group full-width">
                                <label for="total">Total Pengajuan</label>
                                <input type="text" id="total" name="total" class="total-display" value="Rp <?php echo number_format($data_form['total'], 0, ',', '.'); ?>" readonly>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="foto">Upload Bukti/Lampiran (Opsional)</label>
                                <input type="file" id="foto" name="foto">
                                <?php if ($action == 'edit' && !empty($data_form['gambar']) && file_exists($data_form['gambar'])): ?>
                                <div class="current-photo">
                                    <p>File saat ini:</p>
                                    <?php
                                        $file_ext = strtolower(pathinfo($data_form['gambar'], PATHINFO_EXTENSION));
                                        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])):
                                    ?>
                                        <a href="<?php echo htmlspecialchars($data_form['gambar'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                                            <img src="<?php echo htmlspecialchars($data_form['gambar'], ENT_QUOTES, 'UTF-8'); ?>" alt="Lampiran">
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo htmlspecialchars($data_form['gambar'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">Lihat Lampiran (<?php echo htmlspecialchars(basename($data_form['gambar']), ENT_QUOTES, 'UTF-8'); ?>)</a>
                                    <?php endif; ?>
                                    <p><small>Mengunggah file baru akan menggantikan file ini.</small></p>
                                </div>
                                <?php endif; ?>
                            </div>

                        </div>
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-success"><?php echo ($action === 'add') ? 'Simpan' : 'Update'; ?></button>
                            <a href="index.php" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            
            <?php // Tampilkan Tabel jika action=view
            else: ?>
                <div class="action-buttons">
                    <a href="index.php?action=add" class="btn btn-primary">Tambah Pengajuan Baru</a>
                </div>

                <div class="filter-panel">
                    <form action="index.php" method="GET">
                        <input type="hidden" name="action" value="view">
                        <div class="form-group">
                            <label for="tgl_awal">Tanggal:</label>
                            <input type="date" id="tgl_awal" name="tgl_awal" value="<?php echo htmlspecialchars($tgl_awal, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="tgl_akhir">s.d.</label>
                            <input type="date" id="tgl_akhir" name="tgl_akhir" value="<?php echo htmlspecialchars($tgl_akhir, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="keyword">Keyword:</label>
                            <input type="text" id="keyword" name="keyword" value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cari No.Pengajuan, Nama, Uraian...">
                        </div>
                        <button type="submit" class="btn btn-primary">Cari</button>
                        <a href="index.php?action=view" class="btn btn-secondary">Reset</a>
                    </form>
                </div>

                <h3>Daftar Pengajuan Biaya</h3>
                <p>Total Anggaran Diajukan: <strong>Rp <?php echo number_format($total_anggaran, 0, ',', '.'); ?></strong> | Jumlah Record: <strong><?php echo count($list_pengajuan); ?></strong></p>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Aksi</th>
                                <th>Status</th>
                                <th>No. Pengajuan</th>
                                <th>Tanggal</th>
                                <th>Diajukan Oleh</th>
                                <th>Bidang</th>
                                <th>Departemen</th>
                                <th>Urgensi</th>
                                <th>Uraian</th>
                                <th>Tujuan</th>
                                <th>Target</th>
                                <th>Lokasi</th>
                                <th>Jml</th>
                                <th>Harga (Rp)</th>
                                <th>Total (Rp)</th>
                                <th>Keterangan</th>
                                <th>P.J. Terkait</th>
                                <th>Lampiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($list_pengajuan)): ?>
                                <tr>
                                    <td colspan="18" style="text-align: center;">Tidak ada data yang ditemukan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($list_pengajuan as $row): ?>
                                <tr>
                                    <td class="actions">
                                        <?php 
                                        // Cek Otorisasi untuk edit/hapus
                                        $can_modify = false;
                                        if ($is_admin || $row['nik'] == $logged_in_user || $row['nik_pj'] == $logged_in_user) {
                                            $can_modify = true;
                                        }
                                        
                                        // Hanya bisa edit/hapus jika status 'Proses Pengajuan' atau jika dia admin
                                        if (($row['status'] == 'Proses Pengajuan' && $can_modify) || $is_admin): 
                                        ?>
                                            <a href="index.php?action=edit&id=<?php echo htmlspecialchars($row['no_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-warning" style="padding: 5px 8px; font-size: 12px;">Edit</a>
                                            <a href="javascript:void(0);" onclick="konfirmasiHapus('<?php echo htmlspecialchars($row['no_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo $csrf_token; ?>')" class="btn btn-danger" style="padding: 5px 8px; font-size: 12px;">Hapus</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['no_pengajuan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($row['tanggal'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['namapengaju'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($row['nik'], ENT_QUOTES, 'UTF-8'); ?>)</td>
                                    <td><?php echo htmlspecialchars($row['bidang'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['departemen'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['urgensi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['uraian_latar_belakang'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['tujuan_pengajuan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['target_sasaran'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['lokasi_kegiatan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['jumlah'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td style="text-align: right;"><?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                                    <td style="text-align: right;"><?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($row['keterangan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['namapj'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($row['nik_pj'], ENT_QUOTES, 'UTF-8'); ?>)</td>
                                    <!--<td>
                                        <?php if (!empty($row['gambar']) && file_exists($row['gambar'])): ?>
                                            <a href="<?php echo htmlspecialchars($row['gambar'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">Lihat</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>-->
									<td>  <!--Tambahan pake library agar gambarnya dinamis-->
                                        <?php if (!empty($row['gambar']) && file_exists($row['gambar'])): ?>
                                            <?php
                                            // Ambil ekstensi file
                                            $file_path = htmlspecialchars($row['gambar'], ENT_QUOTES, 'UTF-8');
                                            $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                                            $allowed_image_ext = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
                                            
                                            // Buat caption untuk lightbox
                                            $caption = "Lampiran: " . htmlspecialchars($row['no_pengajuan'], ENT_QUOTES, 'UTF-8') .
                                                       "<br>Uraian: " . htmlspecialchars($row['uraian_latar_belakang'], ENT_QUOTES, 'UTF-8');

                                            // Jika file adalah gambar, gunakan FancyBox
                                            if (in_array($file_ext, $allowed_image_ext)):
                                            ?>
                                                <a data-fancybox="lampiran" 
                                                   data-src="<?php echo $file_path; ?>" 
                                                   data-caption="<?php echo $caption; ?>" 
                                                   href="javascript:;" 
                                                   class="btn btn-secondary" style="padding: 5px 8px; font-size: 12px;">
                                                   Lihat Foto
                                                </a>
                                            
                                            <?php else: // Jika bukan gambar (misal: PDF) ?>
                                                <a href="<?php echo $file_path; ?>" 
                                                   target="_blank" 
                                                   class="btn btn-secondary" style="padding: 5px 8px; font-size: 12px;">
                                                   Lihat File
                                                </a>
                                            <?php endif; ?>
                                            
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
									
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
        <footer class="footer">
            Copyright &copy; 2025 - <?php echo date('Y'); ?> <?php echo htmlspecialchars($nama_instansi, ENT_QUOTES, 'UTF-8'); ?>
        </footer>
    </div>

	<script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi FancyBox untuk semua link dengan atribut 'data-fancybox="lampiran"'
            Fancybox.bind('[data-fancybox="lampiran"]', {
                // Konfigurasi kustom toolbar
                Toolbar: {
                    display: {
                        left: ["close"],
                        middle: [],
                        // Menambahkan tombol zoom, flip, dan rotate sesuai permintaan Anda
                        right: ["zoomIn", "zoomOut", "flipX", "flipY", "rotateCCW", "rotateCW", "download"],
                    },
                },
            });
            
            // Panggil kembali fungsi format rupiah jika ada form (untuk mode edit/add)
            if(document.getElementById('harga')) {
                setupFormatRupiah();
				
				// [TAMBAHAN] Inisialisasi Tom Select untuk P.J. Terkait
                // Kita target elemen dengan ID baru #select-pj
                new TomSelect("#select-pj",{
                    create: false, // User tidak boleh menambah data baru
                    sortField: {
                        field: "text", // Urutkan berdasarkan nama
                        direction: "asc"
                    }
                    // 'placeholder' sudah kita set di tag <select>
                });
            }
        });
    </script>
    
</body>
</html>

</body>
</html>