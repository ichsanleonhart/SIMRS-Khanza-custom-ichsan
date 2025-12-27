<?php
session_start();
require_once('../../conf/conf.php');
header('Content-Type: application/json');

// Cek Sesi Login Jadwal
if (!isset($_SESSION['jadwal_login']) || empty($_SESSION['jadwal_user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi habis']);
    exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$nik_login = $_SESSION['jadwal_user']; // NIK Kepala Unit/PJ
$konektor = bukakoneksi();

// --- 1. LIST PENGAJUAN (KHUSUS BAWAHAN DIA) ---
if ($act == 'list') {
    // Filter hanya data di mana NIK PJ = NIK User Login
    $sql = "SELECT pc.*, p.nama, p.departemen, d.nama as nama_dep 
            FROM pengajuan_cuti pc
            JOIN pegawai p ON pc.nik = p.nik
            LEFT JOIN departemen d ON p.departemen = d.dep_id
            WHERE pc.nik_pj = '$nik_login'
            ORDER BY pc.tanggal DESC";
            
    $hasil = bukaquery($sql);
    $data = [];
    while($r = mysqli_fetch_assoc($hasil)) {
        $data[] = $r;
    }
    echo json_encode(['data' => $data]);
    exit;
}

// --- 2. APPROVE / REJECT (LEVEL 1 ONLY) ---
elseif ($act == 'action') {
    $no_pengajuan = validTeks($_POST['no_pengajuan']);
    $status       = validTeks($_POST['status']); // 'Disetujui' atau 'Ditolak'
    
    // Validasi Status
    if(!in_array($status, ['Disetujui', 'Ditolak'])) {
        echo json_encode(['status'=>'error', 'message'=>'Status tidak valid']);
        exit;
    }

    // Validasi Kepemilikan (Security)
    // Pastikan yang diedit benar-benar bawahan user ini
    $cek = fetch_assoc("SELECT nik_pj FROM pengajuan_cuti WHERE no_pengajuan='$no_pengajuan'");
    if(!$cek || $cek['nik_pj'] != $nik_login) {
        echo json_encode(['status'=>'error', 'message'=>'Anda tidak memiliki hak akses untuk pengajuan ini.']);
        exit;
    }

    // Update Level 1 Saja
    $sql_update = "UPDATE pengajuan_cuti SET 
                   status = '$status', 
                   waktu_disetujui_atasan = NOW()
                   WHERE no_pengajuan='$no_pengajuan'";

    if(mysqli_query($konektor, $sql_update)) {
        echo json_encode(['status'=>'success', 'message'=>"Pengajuan telah $status."]);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Gagal update DB: '.mysqli_error($konektor)]);
    }
    exit;
}
?>