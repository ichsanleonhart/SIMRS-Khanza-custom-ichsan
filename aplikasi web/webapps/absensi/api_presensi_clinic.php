<?php
/**
 * API PRESENSI KLINIK (SELF-SERVICE SHIFT)
 * Fitur: User memilih shift sendiri saat absen masuk.
 * Kompatibel dengan database existing.
 */

// 1. BUFFERING & CLEANER (Standar V4.2)
ob_start();
require_once('../conf/conf.php');

function send_json($data) {
    ob_clean(); 
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// 2. KEAMANAN
$allowed_ip_prefix = '192.168.'; 
$user_ip = $_SERVER['REMOTE_ADDR'];
if (strpos($user_ip, $allowed_ip_prefix) !== 0 && $user_ip !== '127.0.0.1' && $user_ip !== '::1') {
    send_json(['status' => 'error', 'message' => 'Akses Ditolak: IP Ilegal']);
}

$konektor = bukakoneksi();
$act = isset($_GET['act']) ? $_GET['act'] : '';

// --- HELPER ---
function get_jam_jaga_list($dep_id) {
    // Ambil semua shift yang tersedia untuk departemen ini
    $sql = "SELECT shift, jam_masuk, jam_pulang FROM jam_jaga WHERE dep_id='$dep_id' ORDER BY jam_masuk ASC";
    $res = bukaquery($sql);
    $list = [];
    while($r = mysqli_fetch_assoc($res)) {
        $list[] = $r;
    }
    return $list;
}

function get_jam_jaga_detail($dep_id, $shift) {
    return fetch_assoc("SELECT jam_masuk, jam_pulang FROM jam_jaga WHERE dep_id='$dep_id' AND shift='$shift'");
}

function get_setting_keterlambatan() {
    return fetch_assoc("SELECT * FROM set_keterlambatan LIMIT 1");
}

// ==================================================================================
// 1. GET DESCRIPTORS (Sama persis dengan RS)
// ==================================================================================
if ($act == 'get_descriptors') {
    $sql = "SELECT p.nik, f.face_descriptor, p.nama 
            FROM face_enrollment f 
            JOIN pegawai p ON f.user_id = p.id";
    $hasil = mysqli_query($konektor, $sql);
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
    send_json($data);
}

// ==================================================================================
// 2. GET STATUS & PILIHAN SHIFT (LOGIKA KLINIK)
// ==================================================================================
elseif ($act == 'check_status') {
    $nik = validTeks($_GET['nik']);
    
    $pegawai = fetch_assoc("SELECT id, departemen, nama FROM pegawai WHERE nik='$nik'");
    if(!$pegawai) send_json(['status'=>'error', 'message'=>'Pegawai tidak ditemukan']);
    
    $id_peg = $pegawai['id'];
    $dep_id = $pegawai['departemen'];

    // Cek Temporary (Sedang Dinas?)
    $cek_temp = fetch_assoc("SELECT * FROM temporary_presensi WHERE id='$id_peg'");

    if ($cek_temp) {
        // --- MODE PULANG (Sudah ada shift) ---
        // Tidak perlu pilih shift lagi, pakai yang sudah tersimpan
        send_json([
            'status' => 'success',
            'mode' => 'PULANG',
            'nama' => $pegawai['nama'],
            'shift_sekarang' => $cek_temp['shift'],
            'jam_masuk_dulu' => $cek_temp['jam_datang']
        ]);
    } else {
        // --- MODE MASUK (Belum ada shift) ---
        // Ambil daftar shift departemen untuk dipilih user
        $daftar_shift = get_jam_jaga_list($dep_id);
        
        if (empty($daftar_shift)) {
            send_json(['status' => 'error', 'message' => 'Departemen Anda belum memiliki setting Jam Jaga. Hubungi Admin.']);
        }

        send_json([
            'status' => 'success',
            'mode' => 'MASUK',
            'nama' => $pegawai['nama'],
            'pilihan_shift' => $daftar_shift // Array shift dikirim ke Frontend
        ]);
    }
}

// ==================================================================================
// 3. SUBMIT ABSEN (LOGIKA KLINIK)
// ==================================================================================
elseif ($act == 'submit_absen') {
    $nik = validTeks($_POST['nik']);
    $img_base64 = $_POST['image'];
    
    // Parameter Khusus Klinik: Shift yang dipilih user
    $shift_pilihan = isset($_POST['shift']) ? validTeks($_POST['shift']) : '';

    $pegawai = fetch_assoc("SELECT id, nama, departemen FROM pegawai WHERE nik='$nik'");
    $id_peg = $pegawai['id'];
    $nama_peg = $pegawai['nama'];
    $dep_id = $pegawai['departemen'];

    // Simpan Foto
    $folderRelative = "foto_absen/" . date("Y-m") . "/";
    $folderServer   = "../" . $folderRelative; // Naik satu folder karena ini di dalam absensi/
    if (!file_exists($folderServer)) mkdir($folderServer, 0777, true);
    
    $fileName = $nik . "-" . date("Ymd-His") . ".jpg";
    file_put_contents($folderServer . $fileName, base64_decode(explode(";base64,", $img_base64)[1]));
    $dbPhotoPath = "absensi/" . $folderRelative . $fileName; // Path standard Khanza

    $jam_sekarang = date('H:i:s');
    
    // Cek lagi status terkini
    $cek_temp = fetch_assoc("SELECT * FROM temporary_presensi WHERE id='$id_peg'");

    if (!$cek_temp) {
        // --- ABSEN MASUK (INSERT) ---
        
        // Validasi: Shift harus dipilih!
        if(empty($shift_pilihan)) {
            send_json(['status'=>'error', 'message'=>'Anda belum memilih shift!']);
        }

        // Ambil detail jam jaga berdasarkan pilihan user
        $d_jam = get_jam_jaga_detail($dep_id, $shift_pilihan);
        if (!$d_jam) send_json(['status'=>'error', 'message'=>'Shift tidak valid / dihapus admin.']);

        $jam_baku = $d_jam['jam_masuk'];

        // Hitung Keterlambatan
        $settings = get_setting_keterlambatan();
        $selisih = strtotime($jam_sekarang) - strtotime($jam_baku);
        $menit = floor($selisih / 60);

        $status = "Tepat Waktu";
        if ($menit > 0) {
            if ($menit <= ($settings['toleransi'] ?? 0)) $status = "Terlambat Toleransi";
            elseif ($menit <= ($settings['terlambat1'] ?? 0)) $status = "Terlambat I";
            else $status = "Terlambat II";
        }
        $durasi_telat = ($menit > 0) ? gmdate("H:i:s", $selisih) : "";

        // INSERT dengan shift pilihan user
        $sql = "INSERT INTO temporary_presensi 
                (id, shift, jam_datang, status, keterlambatan, durasi, photo) 
                VALUES 
                ('$id_peg', '$shift_pilihan', NOW(), '$status', '$durasi_telat', '', '$dbPhotoPath')";

        if (mysqli_query($konektor, $sql)) {
            send_json(['status'=>'success', 'mode'=>'MASUK', 'nama'=>$nama_peg, 'waktu'=>$jam_sekarang, 'shift'=>$shift_pilihan]);
        } else {
            send_json(['status'=>'error', 'message'=>'Gagal Insert: '.mysqli_error($konektor)]);
        }

    } else {
        // --- ABSEN PULANG (UPDATE) ---
        // Logika sama persis dengan RS, tidak perlu pilih shift lagi
        
        $jam_masuk = $cek_temp['jam_datang'];
        $shift_kode = $cek_temp['shift']; // Ambil shift yang dulu dipilih saat masuk
        $status_awal = $cek_temp['status'];
        
        $d_jam = get_jam_jaga_detail($dep_id, $shift_kode);
        $jam_pulang_baku = $d_jam ? $d_jam['jam_pulang'] : '14:00:00';

        $status_akhir = $status_awal;
        if (strtotime($jam_sekarang) < strtotime($jam_pulang_baku) && strpos($status_awal, 'PSW') === false) {
            $status_akhir .= " & PSW";
        }

        $durasi = sprintf("%02d:%02d", floor((strtotime($jam_sekarang) - strtotime($jam_masuk))/3600), floor(((strtotime($jam_sekarang) - strtotime($jam_masuk))%3600)/60));

        $q_upd = "UPDATE temporary_presensi SET jam_pulang=NOW(), status='$status_akhir', durasi='$durasi', photo='$dbPhotoPath' WHERE id='$id_peg'";
        mysqli_query($konektor, $q_upd);

        $q_mov = "INSERT INTO rekap_presensi (id, shift, jam_datang, jam_pulang, status, keterlambatan, durasi, keterangan, photo)
                  SELECT id, shift, jam_datang, jam_pulang, status, keterlambatan, durasi, '-', photo FROM temporary_presensi WHERE id='$id_peg'";
        
        if (mysqli_query($konektor, $q_mov)) {
            mysqli_query($konektor, "DELETE FROM temporary_presensi WHERE id='$id_peg'");
            send_json(['status'=>'success', 'mode'=>'PULANG', 'nama'=>$nama_peg, 'waktu'=>$jam_sekarang]);
        } else {
            send_json(['status'=>'error', 'message'=>'Gagal Arsip Rekap']);
        }
    }
}
?>