<?php
/**
 * API PRESENSI WAJAH - V5.1 (FIXED PATH & STORAGE)
 * Perbaikan: Path penyimpanan foto fisik dan database disinkronkan
 */

require_once('../conf/conf.php');

// --- 1. KEAMANAN ---
$allowed_ip_prefix = '192.168.'; 
$user_ip = $_SERVER['REMOTE_ADDR'];
if (strpos($user_ip, $allowed_ip_prefix) !== 0 && $user_ip !== '127.0.0.1' && $user_ip !== '::1') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Akses Ditolak: IP Ilegal']));
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$konektor = bukakoneksi();

// --- HELPER ---
function get_jam_jaga($dep_id, $shift) {
    return fetch_assoc("SELECT jam_masuk, jam_pulang FROM jam_jaga WHERE dep_id='$dep_id' AND shift='$shift'");
}

// 1. GET DESCRIPTORS
if ($act == 'get_descriptors') {
    $sql = "SELECT f.nik, f.face_descriptor, p.nama FROM face_enrollment f JOIN pegawai p ON f.user_id = p.id";
    $hasil = bukaquery($sql);
    $data = [];
    while($row = mysqli_fetch_assoc($hasil)) {
        $descriptorArray = json_decode($row['face_descriptor']);
        if ($descriptorArray) {
            $data[] = [
                'label' => $row['nik'],
                'nama'  => $row['nama'],
                'descriptor' => $descriptorArray
            ];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// 2. CEK STATUS (Untuk Popup Konfirmasi)
elseif ($act == 'check_status_rs') {
    $nik = validTeks($_GET['nik']);
    $tgl_sql = date('Y-m-d');
    $thn = date('Y'); $bln = date('m'); $tgl = date('j');
    
    $pegawai = fetch_assoc("SELECT id, nama, departemen FROM pegawai WHERE nik='$nik'");
    if(!$pegawai) { echo json_encode(['status'=>'error', 'message'=>'Pegawai tidak ditemukan']); exit; }
    
    $id_peg = $pegawai['id'];
    $dep_id = $pegawai['departemen'];

    // Cek Temporary
    $cek_temp = fetch_assoc("SELECT * FROM temporary_presensi WHERE id='$id_peg'");

    if ($cek_temp) {
        // MODE PULANG
        $shift_kode = $cek_temp['shift'];
        $d_jam = get_jam_jaga($dep_id, $shift_kode);
        
        echo json_encode([
            'status' => 'success',
            'mode' => 'PULANG',
            'nama' => $pegawai['nama'],
            'shift' => $shift_kode,
            'jam_kerja' => ($d_jam ? $d_jam['jam_masuk'] . ' - ' . $d_jam['jam_pulang'] : '-')
        ]);
    } else {
        // MODE MASUK - Cari Jadwal
        $cek_rekap = fetch_assoc("SELECT count(id) as total FROM rekap_presensi WHERE id='$id_peg' AND jam_datang LIKE '$tgl_sql%'");
        $total_absen = $cek_rekap['total'];
        $table_jadwal = ($total_absen > 0) ? 'jadwal_tambahan' : 'jadwal_pegawai';
        $hari_kolom = "h" . $tgl;

        // Query Jadwal (Handle bulan '01' atau '1')
        $q_jadwal = "SELECT $hari_kolom as shift FROM $table_jadwal WHERE id='$id_peg' AND tahun='$thn' AND (bulan='$bln' OR bulan='".(int)$bln."')";
        $d_jadwal = fetch_assoc($q_jadwal);
        $shift_kode = $d_jadwal['shift'] ?? '';
        
        if (empty($shift_kode) || $shift_kode == 'Non Shift' || $shift_kode == '-' || $shift_kode == 'L' || $shift_kode == '') {
             // Coba cek jadwal tambahan jika jadwal utama kosong
             if($table_jadwal == 'jadwal_pegawai') {
                 $q_add = "SELECT $hari_kolom as shift FROM jadwal_tambahan WHERE id='$id_peg' AND tahun='$thn' AND (bulan='$bln' OR bulan='".(int)$bln."')";
                 $d_add = fetch_assoc($q_add);
                 if($d_add && $d_add['shift'] != '' && $d_add['shift'] != '-') {
                     $shift_kode = $d_add['shift'];
                 } else {
                     echo json_encode(['status' => 'error', 'message' => 'Jadwal libur / Kosong.']); exit;
                 }
             } else {
                 echo json_encode(['status' => 'error', 'message' => 'Jadwal tidak ditemukan.']); exit;
             }
        }

        $d_jam = get_jam_jaga($dep_id, $shift_kode);
        if (!$d_jam) {
            echo json_encode(['status' => 'error', 'message' => "Shift '$shift_kode' belum disetting jam jaganya."]); exit;
        }

        echo json_encode([
            'status' => 'success',
            'mode' => 'MASUK',
            'nama' => $pegawai['nama'],
            'shift' => $shift_kode,
            'jam_kerja' => $d_jam['jam_masuk'] . ' - ' . $d_jam['jam_pulang']
        ]);
    }
    exit;
}

// 3. SUBMIT ABSEN
elseif ($act == 'submit_absen') {
    $nik = validTeks($_POST['nik']);
    $img_base64 = $_POST['image'];
    
    $pegawai = fetch_assoc("SELECT id, nama, departemen FROM pegawai WHERE nik='$nik'");
    $id_peg = $pegawai['id'];
    $nama_peg = $pegawai['nama'];
    $dep_id = $pegawai['departemen'];

    // --- FIX PATH PENYIMPANAN ---
    // Simpan di: /var/www/html/webapps/absensi/foto_absen/2025-12/
    $subFolder = date("Y-m");
    $physicalDir = __DIR__ . "/foto_absen/" . $subFolder; 
    
    if (!file_exists($physicalDir)) {
        if (!mkdir($physicalDir, 0777, true)) {
            echo json_encode(['status'=>'error', 'message'=>'Gagal buat folder foto']); exit;
        }
    }
    
    $fileName = $nik . "-" . date("Ymd-His") . ".jpg";
    $fullPath = $physicalDir . "/" . $fileName;
    
    // Decode & Simpan Fisik
    $img_data = base64_decode(explode(";base64,", $img_base64)[1]);
    if(file_put_contents($fullPath, $img_data) === false) {
        echo json_encode(['status'=>'error', 'message'=>'Gagal simpan file fisik']); exit;
    }
    
    // Path Database (Relatif dari root webapps agar bisa tampil di laporan)
    // Contoh: absensi/foto_absen/2025-12/123.jpg
    $dbPhotoPath = "absensi/foto_absen/" . $subFolder . "/" . $fileName;

    $konektor = bukakoneksi();
    $cek_temp = fetch_assoc("SELECT * FROM temporary_presensi WHERE id='$id_peg'");

    if (!$cek_temp) {
        // --- MASUK ---
        // (Logic penentuan shift sama seperti check_status, dipersingkat)
        $thn = date('Y'); $bln = date('m'); $tgl = date('j'); $hari = "h".$tgl;
        
        // Cek total rekap hari ini
        $cek_rekap = fetch_assoc("SELECT count(id) as total FROM rekap_presensi WHERE id='$id_peg' AND jam_datang LIKE '".date('Y-m-d')."%'");
        $tbl = ($cek_rekap['total'] > 0) ? 'jadwal_tambahan' : 'jadwal_pegawai';
        
        $qj = fetch_assoc("SELECT $hari as shift FROM $tbl WHERE id='$id_peg' AND tahun='$thn' AND (bulan='$bln' OR bulan='".(int)$bln."')");
        $shift_kode = $qj['shift'] ?? '-';
        
        // Fallback cek tambahan
        if(($shift_kode == '-' || $shift_kode == '') && $tbl == 'jadwal_pegawai') {
             $qj2 = fetch_assoc("SELECT $hari as shift FROM jadwal_tambahan WHERE id='$id_peg' AND tahun='$thn' AND (bulan='$bln' OR bulan='".(int)$bln."')");
             if($qj2 && $qj2['shift'] != '-') $shift_kode = $qj2['shift'];
        }

        $d_jam = get_jam_jaga($dep_id, $shift_kode);
        $status = "Tepat Waktu";
        $telat_db = "";
        
        if ($d_jam) {
            $selisih = strtotime(date('H:i:s')) - strtotime($d_jam['jam_masuk']);
            if ($selisih > 0) {
                $menit = floor($selisih/60);
                $set = fetch_assoc("SELECT * FROM set_keterlambatan LIMIT 1");
                if ($menit > ($set['toleransi']??0)) $status = ($menit <= ($set['terlambat1']??0)) ? "Terlambat I" : "Terlambat II";
                $telat_db = gmdate("H:i:s", $selisih);
            }
        }

        $sql = "INSERT INTO temporary_presensi (id, shift, jam_datang, status, keterlambatan, durasi, photo) 
                VALUES ('$id_peg', '$shift_kode', NOW(), '$status', '$telat_db', '', '$dbPhotoPath')";

        if (mysqli_query($konektor, $sql)) {
            echo json_encode(['status'=>'success', 'mode'=>'MASUK', 'nama'=>$nama_peg, 'waktu'=>date('H:i:s')]);
        } else {
            echo json_encode(['status'=>'error', 'message'=>'DB Error: '.mysqli_error($konektor)]);
        }

    } else {
        // --- PULANG ---
        $shift_kode = $cek_temp['shift'];
        $status_awal = $cek_temp['status'];
        $jam_masuk = $cek_temp['jam_datang'];
        $d_jam = get_jam_jaga($dep_id, $shift_kode);
        $jam_pulang_baku = $d_jam['jam_pulang'] ?? '14:00:00';
        
        $status_akhir = $status_awal;
        if (strtotime(date('H:i:s')) < strtotime($jam_pulang_baku) && strpos($status_awal, 'PSW') === false) {
            $status_akhir .= " & PSW";
        }

        $durasi = gmdate("H:i:s", strtotime(date('H:i:s')) - strtotime($jam_masuk));

        // Update Temp (Penting: Update foto terbaru)
        mysqli_query($konektor, "UPDATE temporary_presensi SET jam_pulang=NOW(), status='$status_akhir', durasi='$durasi', photo='$dbPhotoPath' WHERE id='$id_peg'");

        // Pindahkan ke Rekap
        $q_mov = "INSERT INTO rekap_presensi (id, shift, jam_datang, jam_pulang, status, keterlambatan, durasi, keterangan, photo)
                  SELECT id, shift, jam_datang, jam_pulang, status, keterlambatan, durasi, '-', photo FROM temporary_presensi WHERE id='$id_peg'";
        
        if (mysqli_query($konektor, $q_mov)) {
            mysqli_query($konektor, "DELETE FROM temporary_presensi WHERE id='$id_peg'");
            echo json_encode(['status'=>'success', 'mode'=>'PULANG', 'nama'=>$nama_peg, 'waktu'=>date('H:i:s')]);
        } else {
            echo json_encode(['status'=>'error', 'message'=>'Gagal Arsip Rekap']);
        }
    }
}
?>