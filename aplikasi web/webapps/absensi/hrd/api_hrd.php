<?php
session_start();
require_once('../../conf/conf.php');

// 1. CEK SESSION (Wajib Login HRD)
if (!isset($_SESSION['hrd_login']) || $_SESSION['hrd_login'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

$act = isset($_POST['act']) ? $_POST['act'] : '';

// 2. ACTION: HAPUS ABSEN
// Perbaikan 1: Ubah pengecekan menjadi 'hapus' (sesuai JS di validasi.php)
if ($act == 'hapus') {
    $id  = validTeks($_POST['id']);
    
    // Perbaikan 2: JANGAN pakai validTeks() untuk jam karena akan menghapus titik dua (:)
    // Gunakan addslashes() saja agar format datetime (YYYY-MM-DD HH:MM:SS) tetap utuh
    $jam = isset($_POST['jam']) ? addslashes($_POST['jam']) : '';

    if(empty($id) || empty($jam)) {
        echo json_encode(['status' => 'error', 'message' => 'Parameter ID atau Jam tidak valid']);
        exit;
    }

    // A. Ambil lokasi foto dari database sebelum data dihapus
    $q_cek = "SELECT photo FROM rekap_presensi WHERE id='$id' AND jam_datang='$jam'";
    $d_cek = fetch_assoc($q_cek);
    
    if($d_cek) {
        // B. Hapus File Fisik
        // Path di DB: absensi/foto_absen/TAHUN-BULAN/namafile.jpg
        // Posisi file ini: /webapps/absensi/hrd/
        // Target: Naik 2 level (../../) untuk kembali ke root 'webapps', lalu sambung path DB
        
        $path_foto = "../../" . $d_cek['photo'];
        
        if(file_exists($path_foto)) {
            unlink($path_foto); // Hapus file gambar fisik
        }

        // C. Hapus Data dari Database
        $konektor = bukakoneksi();
        $sql = "DELETE FROM rekap_presensi WHERE id='$id' AND jam_datang='$jam'";
        
        if(mysqli_query($konektor, $sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Data dan Foto berhasil dihapus']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data DB: ' . mysqli_error($konektor)]);
        }
        mysqli_close($konektor);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Data presensi tidak ditemukan di Database']);
    }
}
?>