<?php
// File: modules/edokter/konsultasi/proses.php
require_once '../../../config/config.php';
require_once '../../../config/database.php';
require_once '../../../helpers/auth_helper.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) { die("Akses Ditolak"); }

$act = $_GET['act'] ?? '';
$kd_dokter_login = $_SESSION['user_id'] ?? '';

// FUNGSI AUDIT TRAIL KHANZA
function catat_trackersql($pdo, $sqle, $usere) {
    try {
        $stmt = $pdo->prepare("INSERT INTO trackersql (tanggal, sqle, usere) VALUES (NOW(), ?, ?)");
        $stmt->execute([$sqle, $usere]);
    } catch (Exception $e) {}
}

try {
    // --- 1. KIRIM PERMINTAAN BARU ---
    if ($act == 'kirim_permintaan') {
        $no_rawat = $_POST['no_rawat'];
        $dokter_tujuan = $_POST['dokter_tujuan'];
        $jenis = $_POST['jenis_permintaan'];
        $diagnosa = $_POST['diagnosa_kerja'];
        $uraian = $_POST['uraian_konsultasi'];
        $tanggal = date('Y-m-d H:i:s');

        // Generate No Permintaan (Format: KM + YYYYMMDD + XXXX)
        $prefix = "KM" . date('Ymd');
        $cek_no = $pdo->query("SELECT max(no_permintaan) as last FROM konsultasi_medik WHERE no_permintaan LIKE '$prefix%'")->fetch();
        $lastNo = $cek_no['last'];
        
        $urutan = empty($lastNo) ? 1 : ((int) substr($lastNo, 10, 4)) + 1;
        $no_permintaan = $prefix . sprintf("%04s", $urutan);

        $sql = "INSERT INTO konsultasi_medik (no_permintaan, no_rawat, tanggal, jenis_permintaan, kd_dokter, kd_dokter_dikonsuli, diagnosa_kerja, uraian_konsultasi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$no_permintaan, $no_rawat, $tanggal, $jenis, $kd_dokter_login, $dokter_tujuan, $diagnosa, $uraian]);

        catat_trackersql($pdo, "INSERT INTO konsultasi_medik VALUES ('$no_permintaan', '$no_rawat', '$tanggal', '$jenis', '$kd_dokter_login', '$dokter_tujuan', '$diagnosa', '$uraian')", $kd_dokter_login);

        header("Location: index.php?status=sukses_kirim");
    }

    // --- 2. EDIT PERMINTAAN ---
    elseif ($act == 'edit_permintaan') {
        $no_permintaan = $_POST['no_permintaan'];
        $dokter_tujuan = $_POST['dokter_tujuan'];
        $jenis = $_POST['jenis_permintaan'];
        $diagnosa = $_POST['diagnosa_kerja'];
        $uraian = $_POST['uraian_konsultasi'];

        // Proteksi: Cek apakah sudah dibalas?
        $cek = $pdo->prepare("SELECT no_permintaan FROM jawaban_konsultasi_medik WHERE no_permintaan = ?");
        $cek->execute([$no_permintaan]);
        if($cek->rowCount() > 0) {
            header("Location: index.php?status=gagal_edit_sudah_dijawab"); exit();
        }

        $sql = "UPDATE konsultasi_medik SET kd_dokter_dikonsuli=?, jenis_permintaan=?, diagnosa_kerja=?, uraian_konsultasi=? WHERE no_permintaan=? AND kd_dokter=?";
        $pdo->prepare($sql)->execute([$dokter_tujuan, $jenis, $diagnosa, $uraian, $no_permintaan, $kd_dokter_login]);

        catat_trackersql($pdo, "UPDATE konsultasi_medik SET kd_dokter_dikonsuli='$dokter_tujuan', jenis_permintaan='$jenis', diagnosa_kerja='$diagnosa', uraian_konsultasi='$uraian' WHERE no_permintaan='$no_permintaan'", $kd_dokter_login);

        header("Location: index.php?status=sukses_edit");
    }

    // --- 3. JAWAB PERMINTAAN ---
    elseif ($act == 'jawab_permintaan') {
        $no_permintaan = $_POST['no_permintaan'];
        $diagnosa = $_POST['diagnosa_kerja'];
        $jawaban = $_POST['uraian_jawaban'];
        $tanggal = date('Y-m-d H:i:s');

        // Upsert Logika
        $cek = $pdo->prepare("SELECT no_permintaan FROM jawaban_konsultasi_medik WHERE no_permintaan = ?");
        $cek->execute([$no_permintaan]);
        
        if($cek->rowCount() > 0) {
            $pdo->prepare("UPDATE jawaban_konsultasi_medik SET tanggal=?, diagnosa_kerja=?, uraian_jawaban=? WHERE no_permintaan=?")->execute([$tanggal, $diagnosa, $jawaban, $no_permintaan]);
            $log = "UPDATE jawaban_konsultasi_medik SET tanggal='$tanggal', diagnosa_kerja='$diagnosa', uraian_jawaban='$jawaban' WHERE no_permintaan='$no_permintaan'";
        } else {
            $pdo->prepare("INSERT INTO jawaban_konsultasi_medik (no_permintaan, tanggal, diagnosa_kerja, uraian_jawaban) VALUES (?, ?, ?, ?)")->execute([$no_permintaan, $tanggal, $diagnosa, $jawaban]);
            $log = "INSERT INTO jawaban_konsultasi_medik VALUES ('$no_permintaan', '$tanggal', '$diagnosa', '$jawaban')";
        }
        
        catat_trackersql($pdo, $log, $kd_dokter_login);
        header("Location: index.php?status=sukses_jawab");
    }

    // --- 4. HAPUS PERMINTAAN ---
    elseif ($act == 'hapus') {
        $id = $_GET['id'];

        // Proteksi Hapus
        $cek = $pdo->prepare("SELECT no_permintaan FROM jawaban_konsultasi_medik WHERE no_permintaan = ?");
        $cek->execute([$id]);

        if($cek->rowCount() > 0) {
            header("Location: index.php?status=gagal_hapus_sudah_dijawab");
        } else {
            $pdo->prepare("DELETE FROM konsultasi_medik WHERE no_permintaan = ? AND kd_dokter = ?")->execute([$id, $kd_dokter_login]);
            catat_trackersql($pdo, "DELETE FROM konsultasi_medik WHERE no_permintaan='$id'", $kd_dokter_login);
            header("Location: index.php?status=sukses_hapus");
        }
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>