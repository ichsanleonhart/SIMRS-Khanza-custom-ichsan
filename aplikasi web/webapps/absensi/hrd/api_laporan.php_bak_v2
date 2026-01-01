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

// --- AMBIL DATA DEPARTEMEN (Untuk Filter) ---
if ($act == 'get_dep') {
    $sql = "SELECT dep_id, nama FROM departemen ORDER BY nama ASC";
    $res = bukaquery($sql);
    $data = [];
    while($row = mysqli_fetch_assoc($res)) {
        $data[] = $row;
    }
    echo json_encode($data);
}

// --- AMBIL DATA LAPORAN HARIAN ---
elseif ($act == 'get_data') {
    $tgl1 = validTeks($_GET['tgl1']);
    $tgl2 = validTeks($_GET['tgl2']);
    $dep  = validTeks($_GET['dep']); // 'ALL' atau kode dep

    // Filter Departemen
    $filter_dep = "";
    if($dep != 'ALL' && $dep != '') {
        $filter_dep = "AND p.departemen = '$dep'";
    }

    // Query Utama (Mirip DlgHarian.java tapi dioptimalkan)
    $sql = "SELECT p.nik, p.nama, d.nama as dep, 
                   r.shift, r.jam_datang, r.jam_pulang, 
                   r.status, r.keterlambatan, r.durasi, r.keterangan, r.photo
            FROM rekap_presensi r
            JOIN pegawai p ON r.id = p.id
            JOIN departemen d ON p.departemen = d.dep_id
            WHERE (r.jam_datang BETWEEN '$tgl1 00:00:00' AND '$tgl2 23:59:59')
            $filter_dep
            ORDER BY r.jam_datang DESC, p.nama ASC";

    $hasil = bukaquery($sql);
    $data = [];

    while($r = mysqli_fetch_assoc($hasil)) {
        // Format Jam agar lebih bersih (hilangkan detik jika perlu, atau biarkan)
        $jam_masuk = date('H:i', strtotime($r['jam_datang']));
        $jam_pulang = ($r['jam_pulang'] == '0000-00-00 00:00:00') ? '-' : date('H:i', strtotime($r['jam_pulang']));
        
        // Fix Path Foto untuk display di web
        // DB simpan: absensi/foto_absen/2023-12/xxx.jpg
        // Kita butuh relative path dari folder hrd: ../../absensi/foto_absen...
        // Atau kalau path di DB sudah 'pages/pegawai/...' sesuaikan.
        // Sesuai V2 terakhir, kita pakai: absensi/foto_absen/...
        
        // Kita kirim data mentah, formatting di JS
        $data[] = [
            'nik' => $r['nik'],
            'nama' => $r['nama'],
            'dep' => $r['dep'],
            'shift' => $r['shift'],
            'jam_datang' => $r['jam_datang'], // Full datetime untuk sorting
            'jam_pulang' => $r['jam_pulang'],
            'display_masuk' => $jam_masuk,
            'display_pulang' => $jam_pulang,
            'status' => $r['status'],
            'telat' => $r['keterlambatan'],
            'durasi' => $r['durasi'],
            'catatan' => $r['keterangan'],
            'photo' => $r['photo']
        ];
    }

    echo json_encode(['data' => $data]);
}
?>