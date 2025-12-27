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
$konektor = bukakoneksi();

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
    $dep  = validTeks($_GET['dep']); 

    $filter_dep = "";
    if($dep != 'ALL' && $dep != '') {
        $filter_dep = "AND p.departemen = '$dep'";
    }

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
        // Hitung durasi berjalan
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
            'full_jam_datang' => $r['jam_datang'], // Data lengkap untuk Modal
            'status' => $r['status'],
            'telat' => $r['keterlambatan'],
            'durasi_live' => $durasi_live,
            'photo' => $r['photo']
        ];
    }

    echo json_encode(['data' => $data]);
}

// --- FORCE CHECKOUT (PERBAIKAN LOGIKA) ---
elseif ($act == 'force_checkout') {
    $nik = validTeks($_POST['nik']);
    
    // Ambil Waktu dari Input HRD
    $input_waktu = isset($_POST['waktu_pulang']) ? $_POST['waktu_pulang'] : '';
    // Konversi: 2025-12-26T14:00 -> 2025-12-26 14:00:00
    $jam_pulang_manual = $input_waktu ? str_replace('T', ' ', $input_waktu) . ':00' : date('Y-m-d H:i:s');

    // Cek Data Pegawai
    $peg = fetch_assoc("SELECT id, departemen FROM pegawai WHERE nik='$nik'");
    if(!$peg) { echo json_encode(['status'=>'error', 'message'=>'Pegawai tidak ditemukan']); exit; }
    $id_peg = $peg['id'];
    $dep_id = $peg['departemen'];

    // Cek Data Temporary
    $cek_temp = fetch_assoc("SELECT * FROM temporary_presensi WHERE id='$id_peg'");
    if(!$cek_temp) { echo json_encode(['status'=>'error', 'message'=>'Data presensi tidak ditemukan/sudah pulang']); exit; }

    $jam_masuk = $cek_temp['jam_datang'];
    $shift_kode = $cek_temp['shift'];
    $status_awal = $cek_temp['status'];

    // Cek Jam Baku (Untuk Status PSW)
    $d_jam = fetch_assoc("SELECT jam_pulang FROM jam_jaga WHERE dep_id='$dep_id' AND shift='$shift_kode'");
    $jam_pulang_baku = $d_jam ? $d_jam['jam_pulang'] : '14:00:00';
    
    // Logic PSW
    $jam_keluar_only = date('H:i:s', strtotime($jam_pulang_manual));
    $status_akhir = $status_awal;
    if (strtotime($jam_keluar_only) < strtotime($jam_pulang_baku) && strpos($status_awal, 'PSW') === false) {
        $status_akhir .= " & PSW";
    }

    // Hitung Durasi Real
    $durasi_detik = strtotime($jam_pulang_manual) - strtotime($jam_masuk);
    if($durasi_detik < 0) $durasi_detik = 0; // Cegah minus
    $durasi_str = sprintf("%02d:%02d", floor($durasi_detik / 3600), floor(($durasi_detik % 3600) / 60));

    // LANGKAH 1: UPDATE DULU DI TEMPORARY (Supaya data lengkap sebelum dipindah)
    $sql_update = "UPDATE temporary_presensi SET 
                   jam_pulang = '$jam_pulang_manual', 
                   status = '$status_akhir', 
                   durasi = '$durasi_str' 
                   WHERE id='$id_peg'";
    
    if(mysqli_query($konektor, $sql_update)) {
        
        // LANGKAH 2: INSERT KE REKAP (TIRU LOGIKA ABSEN PULANG)
        // Kita selipkan catatan manual di kolom 'keterangan'
        $catatan = "Dipulangkan Manual oleh HRD";
        
        $sql_move = "INSERT INTO rekap_presensi (id, shift, jam_datang, jam_pulang, status, keterlambatan, durasi, keterangan, photo)
                     SELECT id, shift, jam_datang, jam_pulang, status, keterlambatan, durasi, '$catatan', photo
                     FROM temporary_presensi WHERE id='$id_peg'";
        
        if (mysqli_query($konektor, $sql_move)) {
            // LANGKAH 3: HAPUS DARI TEMPORARY
            mysqli_query($konektor, "DELETE FROM temporary_presensi WHERE id='$id_peg'");
            echo json_encode(['status'=>'success', 'message'=>'Pegawai berhasil dipulangkan.']);
        } else {
            echo json_encode(['status'=>'error', 'message'=>'Gagal simpan ke rekap: '.mysqli_error($konektor)]);
        }
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Gagal update data temporary: '.mysqli_error($konektor)]);
    }
    exit;
}
?>