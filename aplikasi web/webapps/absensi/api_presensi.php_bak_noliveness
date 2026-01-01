<?php
/**
 * API PRESENSI WAJAH - FINAL WITH JAM JAGA VALIDATION
 * Fitur: Validasi ketat ketersediaan setting jam jaga per departemen
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

// --- HELPER ---
function get_setting_keterlambatan() {
    $data = fetch_assoc("SELECT * FROM set_keterlambatan LIMIT 1");
    return $data; 
}

function get_jam_jaga($dep_id, $shift) {
    // Validasi SQL Injection sederhana via escape string di conf.php biasanya sudah terhandle, 
    // tapi pastikan $shift dan $dep_id bersih.
    return fetch_assoc("SELECT jam_masuk, jam_pulang FROM jam_jaga WHERE dep_id='$dep_id' AND shift='$shift'");
}

// ==================================================================================
// ACTION 1: GET DESCRIPTORS
// ==================================================================================
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

// ==================================================================================
// ACTION 2: GET SCHEDULE (VALIDASI JAM JAGA)
// ==================================================================================
elseif ($act == 'get_schedule') {
    $nik = validTeks($_GET['nik']);
    $tgl_sql = date('Y-m-d');
    $thn = date('Y'); $bln = date('m'); $tgl = date('j');
    
    $pegawai = fetch_assoc("SELECT id, departemen FROM pegawai WHERE nik='$nik'");
    if(!$pegawai) { echo json_encode(['status'=>'error', 'message'=>'Pegawai tidak ditemukan']); exit; }
    
    $id_peg = $pegawai['id'];
    $dep_id = $pegawai['departemen'];

    // Cek Temporary (Sedang berlangsung?)
    $cek_temp = fetch_assoc("SELECT * FROM temporary_presensi WHERE id='$id_peg'");

    if ($cek_temp) {
        // --- MODE PULANG ---
        $shift_kode = $cek_temp['shift'];
        $d_jam = get_jam_jaga($dep_id, $shift_kode);
        
        // Validasi Jam Jaga (Safety Check saat pulang)
        if (!$d_jam) {
            echo json_encode([
                'status' => 'error',
                'message' => "Shift '$shift_kode' pada jam jaga departemen belum disetting, silakan hubungi IT / HRD."
            ]);
            exit;
        }
        
        echo json_encode([
            'status' => 'success',
            'mode_absen' => 'PULANG',
            'shift' => $shift_kode,
            'jam_masuk' => $d_jam['jam_masuk'],
            'jam_pulang_jadwal' => $d_jam['jam_pulang']
        ]);
    } else {
        // --- MODE MASUK ---
        $cek_rekap = fetch_assoc("SELECT count(id) as total FROM rekap_presensi WHERE id='$id_peg' AND jam_datang LIKE '$tgl_sql%'");
        $total_absen = $cek_rekap['total'];
        $hari_kolom = "h" . $tgl;

        // Tentukan Tabel Jadwal
        if ($total_absen == 0) {
            $table_jadwal = 'jadwal_pegawai';
            $label_jadwal = 'Jadwal Reguler';
        } else {
            $table_jadwal = 'jadwal_tambahan';
            $label_jadwal = 'Jadwal Tambahan';
        }

        // Ambil Kode Shift
        $q_jadwal = "SELECT $hari_kolom as shift FROM $table_jadwal WHERE id='$id_peg' AND tahun='$thn' AND bulan='$bln'";
        $d_jadwal = fetch_assoc($q_jadwal);
        $shift_kode = $d_jadwal['shift'] ?? 'Non Shift';
        
        // 1. Validasi Shift Kosong di Jadwal
        if (empty($shift_kode) || $shift_kode == 'Non Shift' || $shift_kode == '' || $shift_kode == '-') {
            if ($total_absen > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Anda sudah selesai shift reguler & tidak memiliki jadwal tambahan.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki jadwal shift hari ini (Libur).']);
            }
            exit;
        }

        // 2. VALIDASI SETTING JAM JAGA (Query ke table jam_jaga)
        $d_jam = get_jam_jaga($dep_id, $shift_kode);
        
        if (!$d_jam) {
            // Jika return false/null, berarti belum disetting di jam_jaga
            echo json_encode([
                'status' => 'error', 
                'message' => "Shift '$shift_kode' pada jam jaga departemen belum disetting, silakan hubungi IT / HRD terlebih dahulu."
            ]);
            exit;
        }

        // Jika lolos semua validasi
        echo json_encode([
            'status' => 'success',
            'mode_absen' => 'MASUK',
            'shift' => $shift_kode . " ($label_jadwal)",
            'jam_masuk' => $d_jam['jam_masuk'],
            'jam_pulang_jadwal' => $d_jam['jam_pulang']
        ]);
    }
    exit;
}

// ==================================================================================
// ACTION 3: SUBMIT ABSEN (UPDATED PATH)
// ==================================================================================
elseif ($act == 'submit_absen') {
    $nik = validTeks($_POST['nik']);
    $img_base64 = $_POST['image'];
    
    $pegawai = fetch_assoc("SELECT id, nama, departemen FROM pegawai WHERE nik='$nik'");
    $id_peg = $pegawai['id'];
    $nama_peg = $pegawai['nama'];
    $dep_id = $pegawai['departemen'];

    // --- PERBAIKAN PATH PENYIMPANAN ---
    // Kita berada di: /var/www/html/webapps/absensi/
    // Tujuan fisik:   /var/www/html/webapps/absensi/foto_absen/YYYY-MM/
    
    $subFolder = date("Y-m") . "/";
    $targetDir = "foto_absen/" . $subFolder; // Relatif terhadap file ini
    
    // Buat folder jika belum ada
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = $nik . "-" . date("Ymd-His") . ".jpg";
    
    // Simpan Fisik
    file_put_contents($targetDir . $fileName, base64_decode(explode(";base64,", $img_base64)[1]));
    
    // Path untuk Database (Relatif terhadap folder webapps/, standar Khanza)
    // Hasil: absensi/foto_absen/2025-12/123.jpg
    $dbPhotoPath = "absensi/" . $targetDir . $fileName;

    $konektor = bukakoneksi();
    $tgl_sekarang = date('Y-m-d');
    $jam_sekarang = date('H:i:s');

    $cek_temp = fetch_assoc("SELECT * FROM temporary_presensi WHERE id='$id_peg'");

    if (!$cek_temp) {
        // --- MODE MASUK ---
        $cek_rekap = fetch_assoc("SELECT count(id) as total FROM rekap_presensi WHERE id='$id_peg' AND jam_datang LIKE '$tgl_sekarang%'");
        $total_absen = $cek_rekap['total'];
        $thn = date('Y'); $bln = date('m'); $tgl = date('j'); $hari_kolom = "h" . $tgl;
        
        $table_jadwal = ($total_absen > 0) ? 'jadwal_tambahan' : 'jadwal_pegawai';
        
        $q_jadwal = "SELECT $hari_kolom as shift FROM $table_jadwal WHERE id='$id_peg' AND tahun='$thn' AND bulan='$bln'";
        $d_jadwal = fetch_assoc($q_jadwal);
        $shift_kode = $d_jadwal['shift'] ?? 'Non Shift';

        // Validasi 1: Shift Ada?
        if (empty($shift_kode) || $shift_kode == 'Non Shift' || $shift_kode == '-') {
            echo json_encode(['status'=>'error', 'message'=>'Tidak ada jadwal valid.']);
            exit;
        }

        // Validasi 2: Jam Jaga Ada?
        $d_jam = get_jam_jaga($dep_id, $shift_kode);
        if (!$d_jam) {
             echo json_encode(['status'=>'error', 'message'=>"Shift '$shift_kode' belum disetting di Jam Jaga Departemen."]);
             exit;
        }

        $jam_baku_masuk = $d_jam['jam_masuk'];

        // Hitung Telat
        $settings = get_setting_keterlambatan();
        $toleransi = $settings['toleransi'] ?? 0;
        $t1 = $settings['terlambat1'] ?? 0;
        
        $selisih_detik = strtotime($jam_sekarang) - strtotime($jam_baku_masuk);
        $selisih_menit = floor($selisih_detik / 60);

        $status = "Tepat Waktu";
        if ($selisih_menit > 0) {
            if ($selisih_menit <= $toleransi) $status = "Terlambat Toleransi";
            elseif ($selisih_menit <= $t1) $status = "Terlambat I";
            else $status = "Terlambat II";
        }

        $ket_telat_db = ($selisih_menit > 0) ? gmdate("H:i:s", $selisih_detik) : ""; 

        $sql = "INSERT INTO temporary_presensi 
                (id, shift, jam_datang, status, keterlambatan, durasi, photo) 
                VALUES 
                ('$id_peg', '$shift_kode', NOW(), '$status', '$ket_telat_db', '', '$dbPhotoPath')";

        if (mysqli_query($konektor, $sql)) {
            echo json_encode(['status'=>'success', 'mode'=>'MASUK', 'nama'=>$nama_peg, 'waktu'=>$jam_sekarang]);
        } else {
            echo json_encode(['status'=>'error', 'message'=>'Gagal Insert: '.mysqli_error($konektor)]);
        }

    } else {
        // --- MODE PULANG ---
        $jam_masuk_awal = $cek_temp['jam_datang'];
        $shift_kode = $cek_temp['shift'];
        $status_awal = $cek_temp['status'];
        
        $d_jam = get_jam_jaga($dep_id, $shift_kode);
        
        // Validasi Jam Jaga (Safety)
        if (!$d_jam) {
            // Fallback darurat jika jam jaga dihapus di tengah shift
            $jam_baku_pulang = '14:00:00'; 
        } else {
            $jam_baku_pulang = $d_jam['jam_pulang'];
        }

        // Cek PSW
        $status_akhir = $status_awal;
        if (strtotime($jam_sekarang) < strtotime($jam_baku_pulang)) {
            if (strpos($status_awal, 'PSW') === false) $status_akhir .= " & PSW";
        }

        // Hitung Durasi
        $durasi_detik = strtotime($jam_sekarang) - strtotime($jam_masuk_awal);
        $durasi_str = sprintf("%02d:%02d", floor($durasi_detik / 3600), floor(($durasi_detik % 3600) / 60));

        $sql_update = "UPDATE temporary_presensi SET jam_pulang=NOW(), status='$status_akhir', durasi='$durasi_str', photo='$dbPhotoPath' WHERE id='$id_peg'";
        
        if(mysqli_query($konektor, $sql_update)) {
            $sql_move = "INSERT INTO rekap_presensi (id, shift, jam_datang, jam_pulang, status, keterlambatan, durasi, keterangan, photo)
                         SELECT id, shift, jam_datang, jam_pulang, status, keterlambatan, durasi, '-', photo
                         FROM temporary_presensi WHERE id='$id_peg'";
            
            if (mysqli_query($konektor, $sql_move)) {
                mysqli_query($konektor, "DELETE FROM temporary_presensi WHERE id='$id_peg'");
                echo json_encode(['status'=>'success', 'mode'=>'PULANG', 'nama'=>$nama_peg, 'waktu'=>$jam_sekarang]);
            } else {
                echo json_encode(['status'=>'error', 'message'=>'Gagal Arsip ke Rekap']);
            }
        } else {
            echo json_encode(['status'=>'error', 'message'=>'Gagal Update Temp']);
        }
    }
    mysqli_close($konektor);
}
?>