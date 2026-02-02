<?php
// modul/konsultasi/proses.php
session_start();
require_once '../../config/database.php';
require_once '../../config/fungsi.php';

if (!isset($_SESSION['login_user'])) {
    die("Akses Ditolak");
}

$act = $_GET['act'] ?? '';
$kd_dokter_login = $_SESSION['kd_dokter'] ?? '';

try {
    // --- 1. KIRIM PERMINTAAN BARU ---
    if ($act == 'kirim_permintaan') {
        $no_rawat = $_POST['no_rawat'];
        $dokter_tujuan = $_POST['dokter_tujuan'];
        $jenis = $_POST['jenis_permintaan'];
        $diagnosa = $_POST['diagnosa_kerja'];
        $uraian = $_POST['uraian_konsultasi'];
        $tanggal = date('Y-m-d H:i:s');

        // Generate No Permintaan
        $prefix = "KM" . date('Ymd');
        $cek_no = $pdo->query("SELECT max(no_permintaan) as last FROM konsultasi_medik WHERE no_permintaan LIKE '$prefix%'")->fetch();
        $lastNo = $cek_no['last'];
        $urutan = (int) substr($lastNo, 10, 3);
        $urutan++;
        $no_permintaan = $prefix . sprintf("%03s", $urutan);

        $sql = "INSERT INTO konsultasi_medik (no_permintaan, no_rawat, tanggal, jenis_permintaan, kd_dokter, kd_dokter_dikonsuli, diagnosa_kerja, uraian_konsultasi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$no_permintaan, $no_rawat, $tanggal, $jenis, $kd_dokter_login, $dokter_tujuan, $diagnosa, $uraian]);

        // AUDIT TRAIL
        $log_sql = "INSERT INTO konsultasi_medik VALUES ('$no_permintaan', '$no_rawat', '$tanggal', '$jenis', '$kd_dokter_login', '$dokter_tujuan', '$diagnosa', '$uraian')";
        catat_tracker($pdo, $log_sql);

        header("Location: index.php?status=sukses_kirim");
    }

    // --- 2. EDIT PERMINTAAN (FITUR BARU) ---
    elseif ($act == 'edit_permintaan') {
        $no_permintaan = $_POST['no_permintaan'];
        $dokter_tujuan = $_POST['dokter_tujuan'];
        $jenis = $_POST['jenis_permintaan'];
        $diagnosa = $_POST['diagnosa_kerja'];
        $uraian = $_POST['uraian_konsultasi'];

        // Cek Keamanan: Apakah sudah dibalas?
        $cek = $pdo->prepare("SELECT no_permintaan FROM jawaban_konsultasi_medik WHERE no_permintaan = ?");
        $cek->execute([$no_permintaan]);
        
        if($cek->rowCount() > 0) {
            header("Location: index.php?status=gagal_edit_sudah_dijawab");
            exit();
        }

        $sql = "UPDATE konsultasi_medik SET kd_dokter_dikonsuli=?, jenis_permintaan=?, diagnosa_kerja=?, uraian_konsultasi=? WHERE no_permintaan=? AND kd_dokter=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$dokter_tujuan, $jenis, $diagnosa, $uraian, $no_permintaan, $kd_dokter_login]);

        // AUDIT TRAIL
        $log_sql = "UPDATE konsultasi_medik SET kd_dokter_dikonsuli='$dokter_tujuan', jenis_permintaan='$jenis', diagnosa_kerja='$diagnosa', uraian_konsultasi='$uraian' WHERE no_permintaan='$no_permintaan'";
        catat_tracker($pdo, $log_sql);

        header("Location: index.php?status=sukses_edit");
    }

    // --- 3. JAWAB PERMINTAAN ---
    elseif ($act == 'jawab_permintaan') {
        $no_permintaan = $_POST['no_permintaan'];
        $diagnosa = $_POST['diagnosa_kerja'];
        $jawaban = $_POST['uraian_jawaban'];
        $tanggal = date('Y-m-d H:i:s');

        // Cek Upsert
        $cek = $pdo->prepare("SELECT no_permintaan FROM jawaban_konsultasi_medik WHERE no_permintaan = ?");
        $cek->execute([$no_permintaan]);
        
        if($cek->rowCount() > 0) {
            $sql = "UPDATE jawaban_konsultasi_medik SET tanggal=?, diagnosa_kerja=?, uraian_jawaban=? WHERE no_permintaan=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tanggal, $diagnosa, $jawaban, $no_permintaan]);
            
            // AUDIT UPDATE
            $log_sql = "UPDATE jawaban_konsultasi_medik SET tanggal='$tanggal', diagnosa_kerja='$diagnosa', uraian_jawaban='$jawaban' WHERE no_permintaan='$no_permintaan'";
        } else {
            $sql = "INSERT INTO jawaban_konsultasi_medik (no_permintaan, tanggal, diagnosa_kerja, uraian_jawaban) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$no_permintaan, $tanggal, $diagnosa, $jawaban]);

            // AUDIT INSERT
            $log_sql = "INSERT INTO jawaban_konsultasi_medik VALUES ('$no_permintaan', '$tanggal', '$diagnosa', '$jawaban')";
        }
        
        catat_tracker($pdo, $log_sql);
        header("Location: index.php?status=sukses_jawab");
    }

    // --- 4. HAPUS PERMINTAAN ---
    elseif ($act == 'hapus') {
        $id = $_GET['id'];

        // Cek sudah dijawab belum
        $cek = $pdo->prepare("SELECT no_permintaan FROM jawaban_konsultasi_medik WHERE no_permintaan = ?");
        $cek->execute([$id]);

        if($cek->rowCount() > 0) {
            header("Location: index.php?status=gagal_hapus_sudah_dijawab");
        } else {
            $del = $pdo->prepare("DELETE FROM konsultasi_medik WHERE no_permintaan = ? AND kd_dokter = ?");
            $del->execute([$id, $kd_dokter_login]);
            
            // AUDIT TRAIL
            $log_sql = "DELETE FROM konsultasi_medik WHERE no_permintaan='$id'";
            catat_tracker($pdo, $log_sql);

            header("Location: index.php?status=sukses_hapus");
        }
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>