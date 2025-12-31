<?php
session_start();
require_once('../../conf/conf.php');
if (!isset($_SESSION['hrd_login'])) { exit; }

$act = isset($_GET['act']) ? $_GET['act'] : '';
$konektor = bukakoneksi();

// --- 1. CARI PEGAWAI (SELECT2) ---
if ($act == 'cari_pegawai') {
    $q = validTeks($_GET['q']);
    $sql = "SELECT nik, nama FROM pegawai WHERE stts_aktif='AKTIF' AND (nama LIKE '%$q%' OR nik LIKE '%$q%') LIMIT 20";
    $res = bukaquery($sql);
    $data = [];
    while($r = mysqli_fetch_assoc($res)) {
        $data[] = ['id' => $r['nik'], 'text' => $r['nama'] . " (" . $r['nik'] . ")"];
    }
    echo json_encode($data);
}

// --- 2. GET SHIFTS ---
elseif ($act == 'get_shifts') {
    $nik = validTeks($_GET['nik']);
    $peg = fetch_assoc("SELECT departemen FROM pegawai WHERE nik='$nik'");
    $dep_id = $peg['departemen'];
    
    $sql = "SELECT shift, jam_masuk, jam_pulang FROM jam_jaga WHERE dep_id='$dep_id'";
    $res = bukaquery($sql);
    $data = [];
    while($r = mysqli_fetch_assoc($res)) {
        $data[] = $r;
    }
    echo json_encode($data);
}

// --- 3. SAVE MANUAL (PERBAIKAN TOTAL) ---
elseif ($act == 'save') {
    // 1. Ambil Input (HATI-HATI DENGAN validTeks UNTUK TANGGAL/JAM)
    $nik     = validTeks($_POST['nik']);
    $shift   = validTeks($_POST['shift']);
    $catatan = validTeks($_POST['catatan']);
    
    // Bypass validTeks untuk tanggal & jam agar simbol '-' dan ':' tidak hilang
    // Kita gunakan mysqli_real_escape_string langsung untuk keamanan
    $tgl_raw        = $_POST['tanggal']; 
    $jam_masuk_raw  = $_POST['jam_masuk']; 
    $jam_pulang_raw = $_POST['jam_pulang'];

    // Sanitasi manual
    $tgl        = mysqli_real_escape_string($konektor, $tgl_raw);
    $jam_masuk  = mysqli_real_escape_string($konektor, $jam_masuk_raw);
    $jam_pulang = mysqli_real_escape_string($konektor, $jam_pulang_raw);

    // Ambil Data Pegawai
    $peg = fetch_assoc("SELECT id, nama, departemen FROM pegawai WHERE nik='$nik'");
    if(!$peg) { echo json_encode(['status'=>'error', 'message'=>'Pegawai tidak ditemukan']); exit; }
    
    $id_peg   = $peg['id'];
    $nama_peg = $peg['nama'];
    $dep_id   = $peg['departemen'];

    // --- VALIDASI A: CEK FORMAT ---
    if(empty($tgl) || empty($jam_masuk) || empty($jam_pulang)) {
        echo json_encode(['status'=>'error', 'message'=>'Tanggal dan Jam harus diisi lengkap.']); exit;
    }

    // --- VALIDASI B: CEK APAKAH SEDANG DINAS (TEMPORARY) ---
    // Jika pegawai masih ada di temporary, tidak boleh input manual untuk menghindari data ganda/konflik
    $cek_temp = fetch_assoc("SELECT jam_datang FROM temporary_presensi WHERE id='$id_peg'");
    if($cek_temp) {
        $tgl_temp = date('Y-m-d', strtotime($cek_temp['jam_datang']));
        // Cek apakah tanggal input sama dengan tanggal dia sedang login
        if($tgl == $tgl_temp) {
            echo json_encode([
                'status'=>'error', 
                'message'=> "GAGAL: $nama_peg tercatat SEDANG ABSEN MASUK (Belum Pulang) pada tanggal $tgl_temp. \n\nSilakan gunakan menu 'Live Monitoring' lalu klik tombol 'Pulangkan' untuk menutup absensinya."
            ]);
            exit;
        }
    }

    // --- VALIDASI C: CEK DUPLIKAT DI REKAP (BAHASA MANUSIA) ---
    // Cek apakah di tanggal tersebut pegawai sudah punya data presensi di rekap
    // Kita gunakan LIKE '$tgl%' untuk mencocokkan tanggal YYYY-MM-DD
    $cek_rekap = fetch_assoc("SELECT jam_datang FROM rekap_presensi WHERE id='$id_peg' AND jam_datang LIKE '$tgl%'");
    if($cek_rekap) {
        echo json_encode([
            'status'=>'error', 
            'message'=> "GAGAL: Data absensi untuk $nama_peg pada tanggal $tgl SUDAH ADA di riwayat. \n\nSistem menolak penyimpanan ganda untuk tanggal yang sama."
        ]);
        exit;
    }

    // --- KONSTRUKSI DATETIME YANG BENAR ---
    // Pastikan format menjadi YYYY-MM-DD HH:MM:SS
    // Input HTML time cuma HH:MM, kita tambah :00
    $masuk_ts  = "$tgl $jam_masuk:00";
    $pulang_ts = "$tgl $jam_pulang:00";

    // Ambil Jam Baku Shift
    $d_jam = fetch_assoc("SELECT jam_masuk, jam_pulang FROM jam_jaga WHERE dep_id='$dep_id' AND shift='$shift'");
    if(!$d_jam) { echo json_encode(['status'=>'error', 'message'=>'Shift tidak valid / Jam Jaga belum diset di Master Departemen']); exit; }

    // Hitung Keterlambatan
    $jam_baku_masuk = "$tgl " . $d_jam['jam_masuk'];
    $selisih_masuk = strtotime($masuk_ts) - strtotime($jam_baku_masuk);
    
    // Ambil Setting Toleransi
    $set = fetch_assoc("SELECT * FROM set_keterlambatan LIMIT 1");
    $tol = ($set['toleransi'] ?? 0) * 60;
    $t1  = ($set['terlambat1'] ?? 0) * 60;
    $t2  = ($set['terlambat2'] ?? 0) * 60;

    $status = "Tepat Waktu";
    $keterlambatan = "";
    
    if($selisih_masuk > $tol) {
        if($selisih_masuk > $t1) {
            $status = ($selisih_masuk > $t2) ? "Terlambat II" : "Terlambat I";
        } else {
            $status = "Terlambat Toleransi";
        }
        $keterlambatan = gmdate("H:i:s", $selisih_masuk);
    }

    // Hitung PSW & Durasi
    $jam_baku_pulang = "$tgl " . $d_jam['jam_pulang'];
    
    // Cek PSW
    if(strtotime($pulang_ts) < strtotime($jam_baku_pulang)) {
        // Jika statusnya bukan terlambat, atau sudah terlambat tapi pulang cepat juga
        if (strpos($status, 'PSW') === false) { 
             $status .= " & PSW";
        }
    }

    $durasi_detik = strtotime($pulang_ts) - strtotime($masuk_ts);
    
    // Validasi Durasi (User terbalik input jam)
    if($durasi_detik <= 0) {
         echo json_encode(['status'=>'error', 'message'=>'GAGAL: Jam Pulang harus lebih besar dari Jam Masuk. Mohon cek inputan jam Anda.']);
         exit;
    }
    
    $durasi = gmdate("H:i:s", $durasi_detik);
    $catatan_fix = $catatan . " (Input Manual HRD)";

    // Insert ke Rekap
    $sql = "INSERT INTO rekap_presensi 
            (id, shift, jam_datang, jam_pulang, status, keterlambatan, durasi, keterangan, photo)
            VALUES 
            ('$id_peg', '$shift', '$masuk_ts', '$pulang_ts', '$status', '$keterlambatan', '$durasi', '$catatan_fix', '-')";

    if(mysqli_query($konektor, $sql)) {
        echo json_encode(['status'=>'success']);
    } else {
        $err = mysqli_error($konektor);
        // Tangkap error duplicate level database sebagai fallback
        if(strpos($err, 'Duplicate') !== false) {
             echo json_encode(['status'=>'error', 'message'=>"Terdeteksi Duplikasi Data ID/Waktu di Database."]);
        } else {
             echo json_encode(['status'=>'error', 'message'=>"Database Error: $err"]);
        }
    }
}
?>