<?php
session_start();
require_once('../../conf/conf.php');

if (!isset($_SESSION['hrd_login'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
    exit;
}

$act = isset($_POST['act']) ? $_POST['act'] : '';
$konektor = bukakoneksi();

// --- PERBAIKAN ACTOR AUDIT (AKUNTABILITAS) ---
// Prioritas: Session hrd_user > Session username > Session nip > Fallback
if (isset($_SESSION['hrd_user'])) {
    $actor = $_SESSION['hrd_user'];
} elseif (isset($_SESSION['username'])) {
    $actor = $_SESSION['username'];
} elseif (isset($_SESSION['nip'])) {
    $actor = $_SESSION['nip'];
} else {
    $actor = 'HRD-Unknown'; // Flag jika session login belum menyimpan ID
}

// --- HAPUS DATA ABSENSI (VALIDASI) ---
if ($act == 'hapus') {
    $id = validTeks($_POST['id']);
    // FIX: Bypass validTeks untuk jam
    $jam = mysqli_real_escape_string($konektor, $_POST['jam']); 

    // 1. AMBIL DATA
    $q_info = "SELECT p.nama, p.nik, r.photo, f.photo_out 
               FROM rekap_presensi r 
               JOIN pegawai p ON r.id = p.id 
               LEFT JOIN rekap_presensi_foto_keluar f ON r.id = f.id_pegawai AND r.jam_datang = f.jam_datang
               WHERE r.id='$id' AND r.jam_datang='$jam'";
    
    $run_info = mysqli_query($konektor, $q_info);
    $info = mysqli_fetch_assoc($run_info);

    if (!$info) {
        echo json_encode(['status' => 'error', 'message' => "Data tidak ditemukan. ID: $id, Jam: $jam"]);
        exit;
    }

    $nama_peg = $info['nama'];
    $nik_peg  = $info['nik'];

    // 2. HAPUS DATABASE
    $sukses_hapus = false;
    $sql_del_main = "DELETE FROM rekap_presensi WHERE id='$id' AND jam_datang='$jam'";
    
    if (mysqli_query($konektor, $sql_del_main)) {
        mysqli_query($konektor, "DELETE FROM rekap_presensi_foto_keluar WHERE id_pegawai='$id' AND jam_datang='$jam'");
        $sukses_hapus = true;
    }
    
    if ($sukses_hapus) {
        // 3. AUDIT TRAIL (DENGAN USER SPESIFIK)
        $log_msg = "HRD DELETE VALIDASI: Menghapus data absensi a.n. $nama_peg ($nik_peg) tanggal/jam $jam secara permanen.";
        $log_msg = mysqli_real_escape_string($konektor, $log_msg);
        
        // Simpan User ID spesifik di kolom usere
        mysqli_query($konektor, "INSERT INTO trackersql (tanggal, sqle, usere) VALUES (NOW(), '$log_msg', '$actor')");

        // 4. BERSIHKAN FILE
        if (!empty($info['photo']) && $info['photo'] != '-') {
            $path_masuk = "../../" . $info['photo'];
            if (file_exists($path_masuk)) unlink($path_masuk);
        }
        if (isset($info['photo_out']) && !empty($info['photo_out']) && $info['photo_out'] != '-') {
            $path_keluar = "../../" . $info['photo_out'];
            if (file_exists($path_keluar)) unlink($path_keluar);
        }

        echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus & tercatat di audit trail.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus: ' . mysqli_error($konektor)]);
    }
    exit;
}
?>