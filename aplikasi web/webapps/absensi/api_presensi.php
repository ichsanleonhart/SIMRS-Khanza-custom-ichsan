<?php
/**
 * API PRESENSI WAJAH - V6.0 (SMART TIME-BASED SELECTION)
 * Perbaikan: Memilih jadwal berdasarkan kedekatan waktu, bukan urutan tabel.
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

// --- HELPER JAM JAGA (Robust) ---
function get_jam_jaga_smart($konektor, $dep_id, $shift_code) {
    // 1. Coba cari spesifik per departemen
    $q = mysqli_query($konektor, "SELECT jam_masuk, jam_pulang FROM jam_jaga WHERE dep_id='$dep_id' AND shift='$shift_code'");
    if ($r = mysqli_fetch_assoc($q)) return $r;

    // 2. Fallback: Cari global (jika admin salah input shift umum di jadwal unit)
    $q2 = mysqli_query($konektor, "SELECT jam_masuk, jam_pulang FROM jam_jaga WHERE shift='$shift_code' LIMIT 1");
    if ($r2 = mysqli_fetch_assoc($q2)) return $r2;

    return null;
}

// --- LOGIKA INTI: PEMILIHAN JADWAL CERDAS ---
function determine_active_shift($konektor, $id_peg, $dep_id) {
    $thn = date('Y'); $bln = date('m'); $tgl = date('j'); 
    $hari_col = "h" . $tgl;
    $today_str = date('Y-m-d');

    // A. Ambil Kandidat Jadwal (Utama & Tambahan)
    $candidates = [];

    // 1. Jadwal Utama
    $q1 = mysqli_query($konektor, "SELECT $hari_col as shift FROM jadwal_pegawai WHERE id='$id_peg' AND tahun='$thn' AND (bulan='$bln' OR bulan='".(int)$bln."')");
    if($d1 = mysqli_fetch_assoc($q1)) {
        if(!empty($d1['shift']) && !in_array($d1['shift'], ['-','','L','Libur','Cuti'])) {
            $candidates['utama'] = $d1['shift'];
        }
    }

    // 2. Jadwal Tambahan
    $q2 = mysqli_query($konektor, "SELECT $hari_col as shift FROM jadwal_tambahan WHERE id='$id_peg' AND tahun='$thn' AND (bulan='$bln' OR bulan='".(int)$bln."')");
    if($d2 = mysqli_fetch_assoc($q2)) {
        if(!empty($d2['shift']) && !in_array($d2['shift'], ['-','','L','Libur','Cuti'])) {
            $candidates['tambahan'] = $d2['shift'];
        }
    }

    if(empty($candidates)) return ['status' => 'error', 'message' => 'Tidak ada jadwal dinas hari ini (Libur/Kosong).'];

    // B. Cek Shift yang SUDAH SELESAI (Rekap)
    // Agar tidak absen 2x di shift yang sama
    $completed = [];
    $q_rekap = mysqli_query($konektor, "SELECT shift FROM rekap_presensi WHERE id='$id_peg' AND jam_datang LIKE '$today_str%'");
    while($r = mysqli_fetch_assoc($q_rekap)) {
        $completed[] = $r['shift'];
    }

    // C. Scoring Time Proximity
    $best_shift = null;
    $min_diff = 99999999; // Detik

    foreach($candidates as $source => $shift_code) {
        // Skip jika sudah selesai
        if(in_array($shift_code, $completed)) continue;

        // Ambil Jam Masuk
        $jam = get_jam_jaga_smart($konektor, $dep_id, $shift_code);
        if(!$jam) continue; // Skip jika jam belum disetting

        // Hitung Selisih Waktu (Sekarang vs Jam Masuk)
        $jam_masuk_ts = strtotime($today_str . ' ' . $jam['jam_masuk']);
        $now_ts = time();
        
        // Logika Selisih Absolut: 
        // Jika Jam Masuk 07:00, Sekarang 06:50 -> Beda 10 menit
        // Jika Jam Masuk 14:00, Sekarang 06:50 -> Beda 7 jam
        // Pemenang: 07:00
        $diff = abs($now_ts - $jam_masuk_ts);

        if($diff < $min_diff) {
            $min_diff = $diff;
            $best_shift = [
                'code' => $shift_code,
                'jam_masuk' => $jam['jam_masuk'],
                'jam_pulang' => $jam['jam_pulang'],
                'source' => $source // 'utama' atau 'tambahan' (untuk debug jika perlu)
            ];
        }
    }

    if(!$best_shift) {
        if(!empty($completed)) return ['status' => 'error', 'message' => 'Semua jadwal hari ini sudah selesai.'];
        return ['status' => 'error', 'message' => 'Jadwal ditemukan tapi jam jaga belum disetting master-nya.'];
    }

    return ['status' => 'success', 'data' => $best_shift];
}

// ==========================================
// ENDPOINTS
// ==========================================

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
    $pegawai = fetch_assoc("SELECT id, nama, departemen FROM pegawai WHERE nik='$nik'");
    if(!$pegawai) { echo json_encode(['status'=>'error', 'message'=>'Pegawai tidak ditemukan']); exit; }
    
    $id_peg = $pegawai['id'];
    $dep_id = $pegawai['departemen'];

    // Cek Temporary (Sedang Dinas?)
    $cek_temp = fetch_assoc("SELECT * FROM temporary_presensi WHERE id='$id_peg'");

    if ($cek_temp) {
        // --- MODE PULANG ---
        $shift_kode = $cek_temp['shift'];
        $d_jam = get_jam_jaga_smart($konektor, $dep_id, $shift_kode);
        
        echo json_encode([
            'status' => 'success',
            'mode' => 'PULANG',
            'nama' => $pegawai['nama'],
            'shift' => $shift_kode,
            'jam_kerja' => ($d_jam ? $d_jam['jam_masuk'] . ' - ' . $d_jam['jam_pulang'] : '-')
        ]);
    } else {
        // --- MODE MASUK (SMART LOGIC) ---
        $analisa = determine_active_shift($konektor, $id_peg, $dep_id);
        
        if($analisa['status'] == 'error') {
            echo json_encode($analisa); exit;
        }

        $shift = $analisa['data'];
        
        echo json_encode([
            'status' => 'success',
            'mode' => 'MASUK',
            'nama' => $pegawai['nama'],
            'shift' => $shift['code'],
            'jam_kerja' => $shift['jam_masuk'] . ' - ' . $shift['jam_pulang']
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

    // Simpan File Fisik
    $subFolder = date("Y-m");
    $physicalDir = __DIR__ . "/foto_absen/" . $subFolder; 
    
    if (!file_exists($physicalDir)) {
        if (!mkdir($physicalDir, 0777, true)) {
            echo json_encode(['status'=>'error', 'message'=>'Gagal buat folder foto']); exit;
        }
    }
    
    $fileName = $nik . "-" . date("Ymd-His") . ".jpg";
    $fullPath = $physicalDir . "/" . $fileName;
    
    $img_data = base64_decode(explode(";base64,", $img_base64)[1]);
    if(file_put_contents($fullPath, $img_data) === false) {
        echo json_encode(['status'=>'error', 'message'=>'Gagal simpan file fisik']); exit;
    }
    
    // Path Database (Relatif dari root webapps)
    $dbPhotoPath = "absensi/foto_absen/" . $subFolder . "/" . $fileName;

    // Cek Status Lagi
    $cek_temp = fetch_assoc("SELECT * FROM temporary_presensi WHERE id='$id_peg'");

    if (!$cek_temp) {
        // --- PROSES MASUK ---
        $analisa = determine_active_shift($konektor, $id_peg, $dep_id);
        if($analisa['status'] == 'error') {
            echo json_encode($analisa); exit;
        }

        $shift_data = $analisa['data'];
        $shift_kode = $shift_data['code'];
        $jam_masuk_baku = $shift_data['jam_masuk'];

        // Hitung Keterlambatan
        $status = "Tepat Waktu";
        $telat_db = "";
        
        $selisih = strtotime(date('H:i:s')) - strtotime($jam_masuk_baku);
        if ($selisih > 0) {
            $menit = floor($selisih/60);
            $set = fetch_assoc("SELECT * FROM set_keterlambatan LIMIT 1");
            if ($menit > ($set['toleransi']??0)) $status = ($menit <= ($set['terlambat1']??0)) ? "Terlambat I" : "Terlambat II";
            $telat_db = gmdate("H:i:s", $selisih);
        }

        $sql = "INSERT INTO temporary_presensi (id, shift, jam_datang, status, keterlambatan, durasi, photo) 
                VALUES ('$id_peg', '$shift_kode', NOW(), '$status', '$telat_db', '', '$dbPhotoPath')";

        if (mysqli_query($konektor, $sql)) {
            echo json_encode(['status'=>'success', 'mode'=>'MASUK', 'nama'=>$nama_peg, 'waktu'=>date('H:i:s')]);
        } else {
            echo json_encode(['status'=>'error', 'message'=>'DB Error: '.mysqli_error($konektor)]);
        }

    } else {
        // --- PROSES PULANG ---
        $shift_kode = $cek_temp['shift'];
        $status_awal = $cek_temp['status'];
        $jam_masuk = $cek_temp['jam_datang'];
        
        // Ambil info jam pulang baku
        $d_jam = get_jam_jaga_smart($konektor, $dep_id, $shift_kode);
        $jam_pulang_baku = $d_jam['jam_pulang'] ?? '14:00:00';
        
        // Cek PSW (Pulang Sebelum Waktu)
        $status_akhir = $status_awal;
        // Hanya tandai PSW jika pulang lebih awal > 15 menit (toleransi wajar)
        // Dan belum ada status PSW sebelumnya
        if ((strtotime($jam_pulang_baku) - strtotime(date('H:i:s'))) > 900 && strpos($status_awal, 'PSW') === false) {
            $status_akhir .= " & PSW";
        }

        $durasi = gmdate("H:i:s", strtotime(date('H:i:s')) - strtotime($jam_masuk));

        // Update Temp (Penting: Update foto terbaru saat pulang)
        mysqli_query($konektor, "UPDATE temporary_presensi SET jam_pulang=NOW(), status='$status_akhir', durasi='$durasi', photo='$dbPhotoPath' WHERE id='$id_peg'");

        // Pindahkan ke Rekap (Arsip)
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