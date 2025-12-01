<?php
session_start();
require_once('../../conf/conf.php');

// Cek Session
if (!isset($_SESSION['hrd_login'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
    exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

// --- AMBIL DATA DEPARTEMEN ---
if ($act == 'get_dep') {
    $sql = "SELECT dep_id, nama FROM departemen ORDER BY nama ASC";
    $res = bukaquery($sql);
    $data = [];
    while($row = mysqli_fetch_assoc($res)) {
        $data[] = $row;
    }
    echo json_encode($data);
}

// --- AMBIL DATA LIVE (YANG SEDANG DINAS) ---
elseif ($act == 'get_live_data') {
    $dep  = validTeks($_GET['dep']); // 'ALL' atau kode dep

    $filter_dep = "";
    if($dep != 'ALL' && $dep != '') {
        $filter_dep = "AND p.departemen = '$dep'";
    }

    // Query ke temporary_presensi
    $sql = "SELECT p.nik, p.nama, d.nama as dep, 
                   t.shift, t.jam_datang, t.status, t.keterlambatan, t.photo
            FROM temporary_presensi t
            JOIN pegawai p ON t.id = p.id
            JOIN departemen d ON p.departemen = d.dep_id
            WHERE 1=1 $filter_dep
            ORDER BY t.jam_datang DESC";

    $hasil = bukaquery($sql);
    $data = [];

    while($r = mysqli_fetch_assoc($hasil)) {
        // Hitung durasi berjalan (Realtime Duration)
        $masuk = strtotime($r['jam_datang']);
        $sekarang = time();
        $diff = $sekarang - $masuk;
        
        $jam = floor($diff / (60 * 60));
        $menit = floor(($diff - ($jam * 60 * 60)) / 60);
        $durasi_live = $jam . " jam " . $menit . " menit";

        $data[] = [
            'nik' => $r['nik'],
            'nama' => $r['nama'],
            'dep' => $r['dep'],
            'shift' => $r['shift'],
            'jam_datang' => date('H:i', strtotime($r['jam_datang'])),
            'status' => $r['status'],
            'telat' => $r['keterlambatan'],
            'durasi_live' => $durasi_live, // Durasi real-time
            'photo' => $r['photo']
        ];
    }

    echo json_encode(['data' => $data]);
}
?>