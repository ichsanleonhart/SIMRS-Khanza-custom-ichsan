<?php
session_start();
require_once('../../conf/conf.php');
if (!isset($_SESSION['hrd_login'])) { exit; }

$act = isset($_GET['act']) ? $_GET['act'] : '';
$konektor = bukakoneksi();

// --- PERBAIKAN ACTOR AUDIT ---
$actor = isset($_SESSION['hrd_user']) ? $_SESSION['hrd_user'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'HRD-Unknown');

// --- 1. CARI PEGAWAI ---
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

// --- 3. SAVE MANUAL (SUPPORT LINTAS HARI) ---
elseif ($act == 'save') {
    $nik     = validTeks($_POST['nik']);
    $shift   = validTeks($_POST['shift']);
    $catatan = validTeks($_POST['catatan']);
    
    // MENERIMA DUA TANGGAL BERBEDA
    $tgl_masuk  = mysqli_real_escape_string($konektor, $_POST['tgl_masuk']);
    $tgl_pulang = mysqli_real_escape_string($konektor, $_POST['tgl_pulang']);
    $jam_masuk  = mysqli_real_escape_string($konektor, $_POST['jam_masuk']);
    $jam_pulang = mysqli_real_escape_string($konektor, $_POST['jam_pulang']);

    $peg = fetch_assoc("SELECT id, nama, departemen FROM pegawai WHERE nik='$nik'");
    if(!$peg) { echo json_encode(['status'=>'error', 'message'=>'Pegawai tidak ditemukan']); exit; }
    
    $id_peg   = $peg['id'];
    $nama_peg = $peg['nama'];
    $dep_id   = $peg['departemen'];

    if(empty($tgl_masuk) || empty($tgl_pulang) || empty($jam_masuk) || empty($jam_pulang)) {
        echo json_encode(['status'=>'error', 'message'=>'Lengkapi data tanggal dan jam (Masuk & Pulang).']); exit;
    }

    // Cek apakah pegawai sedang 'Live' (belum pulang) di sistem
    $cek_temp = fetch_assoc("SELECT jam_datang FROM temporary_presensi WHERE id='$id_peg'");
    if($cek_temp) {
        $tgl_temp = date('Y-m-d', strtotime($cek_temp['jam_datang']));
        // Jika input manual dilakukan pada tanggal yg sama dengan sesi live, tolak.
        if($tgl_masuk == $tgl_temp) {
            echo json_encode(['status'=>'error', 'message'=> "GAGAL: $nama_peg tercatat sedang Login (Live) di sistem. Gunakan menu Monitoring untuk memulangkan."]);
            exit;
        }
    }

    // Cek Duplikasi di Rekap (Berdasarkan Tanggal Masuk)
    $cek_rekap = fetch_assoc("SELECT jam_datang FROM rekap_presensi WHERE id='$id_peg' AND jam_datang LIKE '$tgl_masuk%'");
    if($cek_rekap) {
        echo json_encode(['status'=>'error', 'message'=> "GAGAL: Data absensi $nama_peg pada tanggal $tgl_masuk sudah ada."]);
        exit;
    }

    // KONSTRUKSI TIMESTAMP PENUH (KUNCI LOGIKA LINTAS HARI)
    $masuk_ts  = "$tgl_masuk $jam_masuk:00";
    $pulang_ts = "$tgl_pulang $jam_pulang:00";

    $d_jam = fetch_assoc("SELECT jam_masuk, jam_pulang FROM jam_jaga WHERE dep_id='$dep_id' AND shift='$shift'");
    if(!$d_jam) { echo json_encode(['status'=>'error', 'message'=>'Shift/Jam Jaga belum diset']); exit; }

    // Hitung Keterlambatan (Berdasarkan Tanggal Masuk + Jam Baku)
    $jam_baku_masuk = "$tgl_masuk " . $d_jam['jam_masuk'];
    $selisih_masuk = strtotime($masuk_ts) - strtotime($jam_baku_masuk);
    
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

    // Hitung Pulang Cepat (Berdasarkan Tanggal Pulang + Jam Baku Pulang)
    // Note: Kita asumsikan jam pulang baku di database itu relatif terhadap hari kerjanya.
    // Jika shift malam (masuk tgl 1 jam 21, pulang tgl 2 jam 07), maka perbandingan PSW agak tricky.
    // Untuk amannya, kita bandingkan JAM nya saja untuk PSW, atau gunakan timestamp penuh jika kita tahu tanggal pulangnya.
    
    // Disini kita gunakan logika: Jika tanggal pulang berbeda dengan tanggal masuk, berarti ini shift malam.
    // Maka jam baku pulangnya adalah tgl_pulang + jam_baku.
    
    // Deteksi apakah jam baku pulang (DB) lebih kecil dari jam baku masuk (DB)?
    // Jika ya (misal masuk 21:00 pulang 07:00), maka jam baku pulang harusnya ikut tanggal pulang.
    $ts_baku_masuk_temp = strtotime($d_jam['jam_masuk']);
    $ts_baku_pulang_temp = strtotime($d_jam['jam_pulang']);
    
    $target_tgl_pulang_baku = $tgl_masuk;
    if ($ts_baku_pulang_temp < $ts_baku_masuk_temp) {
        // Ini indikasi shift lintas hari di database jadwal
        $target_tgl_pulang_baku = date('Y-m-d', strtotime("$tgl_masuk +1 day"));
    }
    
    // Namun karena user input manual Tgl Pulang, kita pakai inputan user sebagai referensi 'Hari Pulang Aktual'
    // PSW dihitung: Apakah waktu pulang aktual < Waktu pulang baku (pada tanggal tersebut)?
    $jam_baku_pulang_full = "$target_tgl_pulang_baku " . $d_jam['jam_pulang'];
    
    if(strtotime($pulang_ts) < strtotime($jam_baku_pulang_full) && strpos($status, 'PSW') === false) {
        // Toleransi dikit untuk PSW (misal 1 menit)
        if ((strtotime($jam_baku_pulang_full) - strtotime($pulang_ts)) > 60) {
             $status .= " & PSW";
        }
    }

    // HITUNG DURASI (Sekarang aman untuk lintas hari)
    $durasi_detik = strtotime($pulang_ts) - strtotime($masuk_ts);
    
    if($durasi_detik <= 0) {
         echo json_encode(['status'=>'error', 'message'=>'GAGAL: Waktu Pulang harus lebih besar dari Waktu Masuk. Periksa Tanggal dan Jam inputan Anda.']); exit;
    }
    
    $durasi = gmdate("H:i:s", $durasi_detik);
    
    // Jika durasi > 24 jam, format gmdate H:i:s akan reset. 
    // Untuk kasus > 24 jam (jarang tapi mungkin), kita handle manual jamnya.
    if ($durasi_detik >= 86400) {
        $jam_total = floor($durasi_detik / 3600);
        $sisa_detik = $durasi_detik % 3600;
        $durasi = sprintf("%02d:%s", $jam_total, gmdate("i:s", $sisa_detik));
    }

    $catatan_fix = $catatan . " (Input Manual HRD: $actor)";

    $sql = "INSERT INTO rekap_presensi 
            (id, shift, jam_datang, jam_pulang, status, keterlambatan, durasi, keterangan, photo)
            VALUES 
            ('$id_peg', '$shift', '$masuk_ts', '$pulang_ts', '$status', '$keterlambatan', '$durasi', '$catatan_fix', '-')";

    if(mysqli_query($konektor, $sql)) {
        // --- AUDIT TRAIL ---
        $log_msg = "HRD MANUAL INPUT: Pegawai=$nama_peg ($nik), In=$masuk_ts, Out=$pulang_ts, Durasi=$durasi";
        $log_msg = mysqli_real_escape_string($konektor, $log_msg);
        mysqli_query($konektor, "INSERT INTO trackersql (tanggal, sqle, usere) VALUES (NOW(), '$log_msg', '$actor')");
        
        echo json_encode(['status'=>'success']);
    } else {
        echo json_encode(['status'=>'error', 'message'=>"Database Error: ".mysqli_error($konektor)]);
    }
}
?>