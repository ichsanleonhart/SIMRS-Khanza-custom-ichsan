<?php
session_start();
require_once('../../conf/conf.php');
header('Content-Type: application/json');

$act = isset($_GET['act']) ? $_GET['act'] : '';
$konektor = bukakoneksi();

// --- 1. LOGIN ---
if ($act == 'login') {
    $user = validTeks($_POST['username']);
    $pass = isset($_POST['password']) ? addslashes($_POST['password']) : '';

    $q = "SELECT AES_DECRYPT(id_user, 'nur') as nik, AES_DECRYPT(password, 'windi') as pass 
          FROM user WHERE id_user = AES_ENCRYPT('$user', 'nur')";
    $r = bukaquery($q);
    $row = mysqli_fetch_assoc($r);

    if ($row && $pass == $row['pass']) {
        $_SESSION['pegawai_login'] = true;
        $_SESSION['pegawai_nik'] = $row['nik'];
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Username atau Password salah']);
    }
    exit;
}

if (!isset($_SESSION['pegawai_login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi habis']);
    exit;
}

$nik_login = $_SESSION['pegawai_nik'];

// --- 2. DASHBOARD ---
if ($act == 'dashboard') {
    $q_peg = "SELECT nama, departemen, cuti_diambil, photo FROM pegawai WHERE nik='$nik_login'";
    $d_peg = fetch_assoc($q_peg);
    
    $tahun_ini = date('Y');
    $q_pakai = fetch_assoc("SELECT COALESCE(SUM(jumlah),0) as total 
                            FROM pengajuan_cuti 
                            WHERE nik='$nik_login' 
                            AND status_persetujuan_HRD = 'Disetujui' 
                            AND urgensi = 'Tahunan' 
                            AND YEAR(tanggal_awal) = '$tahun_ini'");
                            
    $terpakai_tahun_ini = $q_pakai['total'];
    $sisa_cuti = max(0, 12 - $terpakai_tahun_ini);

    $q_hist = "SELECT pc.*, p.nama as nama_pj FROM pengajuan_cuti pc 
               LEFT JOIN pegawai p ON pc.nik_pj = p.nik 
               WHERE pc.nik='$nik_login' ORDER BY pc.tanggal DESC LIMIT 20";
    $r_hist = bukaquery($q_hist);
    $history = [];
    while($row = mysqli_fetch_assoc($r_hist)) { $history[] = $row; }

    $photo_db = $d_peg['photo'];
    // Logic display foto: Cek apakah path DB valid
    if(empty($photo_db) || $photo_db == '-' || $photo_db == '') {
        $url_photo = "https://ui-avatars.com/api/?name=".urlencode($d_peg['nama'])."&background=random";
    } else {
        // Karena path di DB sudah mengandung "pages/pegawai/photo/", kita tinggal mundur 2 folder dari 'pegawai/api.php' ke root webapps
        // Lokasi api.php: /var/www/html/webapps/absensi/pegawai/api.php
        // Target foto: /var/www/html/webapps/penggajian/pages/pegawai/photo/...
        // Jadi path relatifnya: ../../penggajian/ + path_db
        $url_photo = "../../penggajian/" . $photo_db;
    }

    echo json_encode([
        'status' => 'success',
        'nama' => $d_peg['nama'],
        'departemen' => $d_peg['departemen'],
        'sisa_cuti' => $sisa_cuti,
        'tahun_ini' => $tahun_ini,
        'photo_url' => $url_photo,
        'history' => $history
    ]);
    exit;
}

// --- 3. GET ATASAN ---
elseif ($act == 'get_atasan') {
    $me = fetch_assoc("SELECT departemen FROM pegawai WHERE nik='$nik_login'");
    $sql = "SELECT nik, nama, departemen, jbtn FROM pegawai WHERE stts_aktif = 'AKTIF' ORDER BY (departemen = '{$me['departemen']}') DESC, nama ASC";
    $res = bukaquery($sql);
    $data = [];
    while($r = mysqli_fetch_assoc($res)) {
        $data[] = [
            'nik' => $r['nik'],
            'nama' => $r['nama'],
            'jabatan' => $r['jbtn'],
            'group' => ($r['departemen'] == $me['departemen']) ? 'Satu Departemen' : 'Departemen Lain'
        ];
    }
    echo json_encode($data);
    exit;
}

// --- 4. SIMPAN CUTI ---
elseif ($act == 'save') {
    $tgl_awal = validTeks($_POST['tanggal_awal']);
    $tgl_akhir = validTeks($_POST['tanggal_akhir']);
    $urgensi = validTeks($_POST['urgensi']);
    $alamat = validTeks($_POST['alamat']);
    $alasan = validTeks($_POST['kepentingan']);
    $nik_pj = validTeks($_POST['nik_pj']);
    
    $d1 = new DateTime($tgl_awal); $d2 = new DateTime($tgl_akhir);
    $jumlah_hari = $d1->diff($d2)->days + 1;

    $prefix = "PC" . date('Ymd');
    $cek_no = fetch_assoc("SELECT max(no_pengajuan) as last FROM pengajuan_cuti WHERE no_pengajuan LIKE '$prefix%'");
    $urut = 1;
    if ($cek_no && $cek_no['last']) {
        $urut = intval(substr($cek_no['last'], -3)) + 1;
    }
    $no_pengajuan = $prefix . sprintf("%03d", $urut);

    $sql = "INSERT INTO pengajuan_cuti (no_pengajuan, tanggal, tanggal_awal, tanggal_akhir, nik, urgensi, alamat, jumlah, kepentingan, nik_pj, status, status_persetujuan_HRD) 
            VALUES ('$no_pengajuan', NOW(), '$tgl_awal', '$tgl_akhir', '$nik_login', '$urgensi', '$alamat', '$jumlah_hari', '$alasan', '$nik_pj', 'Proses Pengajuan', 'Proses Pengajuan')";

    if (mysqli_query($konektor, $sql)) echo json_encode(['status' => 'success']);
    else echo json_encode(['status' => 'error', 'message' => mysqli_error($konektor)]);
    exit;
}

// --- 5. JADWAL + LOG FINGER ---
elseif ($act == 'get_jadwal') {
    $bulan = isset($_POST['bulan']) ? sprintf("%02d", $_POST['bulan']) : date('m');
    $tahun = isset($_POST['tahun']) ? $_POST['tahun'] : date('Y');
    
    $peg = fetch_assoc("SELECT id, departemen FROM pegawai WHERE nik='$nik_login'");
    $id_peg = $peg['id'];
    $my_dep = $peg['departemen']; 

    // A. REFERENSI JAM (PRIORITY)
    $shifts_utama = []; 
    $shifts_cadangan = []; 

    $qs = bukaquery("SELECT dep_id, shift, jam_masuk, jam_pulang FROM jam_jaga");
    while($s = mysqli_fetch_assoc($qs)) {
        $jam_str = substr($s['jam_masuk'],0,5) . " - " . substr($s['jam_pulang'],0,5);
        $kode_shift = $s['shift']; 

        if($s['dep_id'] == $my_dep) {
            $shifts_utama[$kode_shift] = $jam_str;
        } else {
            $shifts_cadangan[$kode_shift] = $jam_str;
        }
    }
    $shifts = array_merge($shifts_cadangan, $shifts_utama);

    // B. LOG PRESENSI
    $log_presensi = [];
    $like_date = "$tahun-$bulan-%";
    
    $q_rekap = bukaquery("SELECT jam_datang, jam_pulang FROM rekap_presensi WHERE id='$id_peg' AND jam_datang LIKE '$like_date'");
    while($r = mysqli_fetch_assoc($q_rekap)) {
        $tgl_only = date('Y-m-d', strtotime($r['jam_datang']));
        $log_presensi[$tgl_only] = [
            'in' => date('H:i', strtotime($r['jam_datang'])),
            'out' => ($r['jam_pulang'] == '0000-00-00 00:00:00') ? '?' : date('H:i', strtotime($r['jam_pulang']))
        ];
    }
    
    $q_temp = fetch_assoc("SELECT jam_datang FROM temporary_presensi WHERE id='$id_peg'");
    if($q_temp) {
        $tgl_only = date('Y-m-d', strtotime($q_temp['jam_datang']));
        if(strpos($tgl_only, "$tahun-$bulan") === 0) {
            $log_presensi[$tgl_only] = [
                'in' => date('H:i', strtotime($q_temp['jam_datang'])),
                'out' => '...' 
            ];
        }
    }

    // C. JADWAL
    $qj = fetch_assoc("SELECT * FROM jadwal_pegawai WHERE id='$id_peg' AND tahun='$tahun' AND (bulan='$bulan' OR bulan='".(int)$bulan."')");
    $qt = fetch_assoc("SELECT * FROM jadwal_tambahan WHERE id='$id_peg' AND tahun='$tahun' AND (bulan='$bulan' OR bulan='".(int)$bulan."')");

    $result = [];
    $jml_hari = date('t', mktime(0, 0, 0, $bulan, 1, $tahun));
    $days_id = ['Sun'=>'Min', 'Mon'=>'Sen', 'Tue'=>'Sel', 'Wed'=>'Rab', 'Thu'=>'Kam', 'Fri'=>'Jum', 'Sat'=>'Sab'];

    for($i=1; $i<=$jml_hari; $i++) {
        $date_str = "$tahun-$bulan-" . sprintf("%02d", $i);
        $col = "h" . $i; 
        
        $shift_kode = "";
        if(isset($qt[$col]) && $qt[$col] != "") $shift_kode = $qt[$col];
        elseif(isset($qj[$col])) $shift_kode = $qj[$col];

        $jam_shift = isset($shifts[$shift_kode]) ? $shifts[$shift_kode] : "-";
        
        $sk = strtolower($shift_kode); 
        $color = 'bg-gray-700 text-gray-300'; 

        if (strpos($sk, 'midle') !== false) $color = 'bg-teal-900 text-teal-200 border-teal-700'; 
        elseif (strpos($sk, 'pagi') !== false) $color = 'bg-green-900 text-green-200 border-green-700'; 
        elseif (strpos($sk, 'siang') !== false) $color = 'bg-yellow-900 text-yellow-200 border-yellow-700'; 
        elseif (strpos($sk, 'malam') !== false) $color = 'bg-blue-900 text-blue-200 border-blue-700'; 
        elseif (strpos($sk, 'libur') !== false || $sk == 'off' || $sk == 'l') $color = 'bg-red-900 text-red-200 border-red-700'; 
        elseif (strpos($sk, 'cuti') !== false) $color = 'bg-purple-900 text-purple-200 border-purple-700'; 

        $real_in = "-"; $real_out = "-";
        $has_log = false;
        if(isset($log_presensi[$date_str])) {
            $real_in = $log_presensi[$date_str]['in'];
            $real_out = $log_presensi[$date_str]['out'];
            $has_log = true;
        }

        $day_name = date('D', strtotime($date_str)); 
        
        $result[] = [
            'tanggal' => sprintf("%02d", $i),
            'hari' => $days_id[$day_name],
            'shift' => $shift_kode ? $shift_kode : '-', 
            'jam_shift' => $jam_shift, 
            'color' => $color,
            'is_today' => ($date_str == date('Y-m-d')),
            'has_log' => $has_log,
            'real_in' => $real_in,
            'real_out' => $real_out
        ];
    }
    echo json_encode($result);
    exit;
}

// --- 6. GET PROFILE ---
elseif ($act == 'get_profile') {
    $peg = fetch_assoc("SELECT nik, nama, tmp_lahir, tgl_lahir, alamat, kota, photo, no_ktp FROM pegawai WHERE nik='$nik_login'");
    
    $no_telp = "";
    $petugas = fetch_assoc("SELECT no_telp FROM petugas WHERE nip='$nik_login'");
    if($petugas) $no_telp = $petugas['no_telp'];
    else {
        $dokter = fetch_assoc("SELECT no_telp FROM dokter WHERE kd_dokter='$nik_login'");
        if($dokter) $no_telp = $dokter['no_telp'];
    }
    $peg['no_telp'] = $no_telp;
    
    // Logic Preview Foto di Form
    if(!empty($peg['photo']) && $peg['photo'] != '-') {
        // Path fisik: ../../penggajian/ + path_db
        $peg['photo_url'] = "../../penggajian/" . $peg['photo'];
    } else {
        $peg['photo_url'] = "";
    }

    echo json_encode($peg);
    exit;
}

// --- 7. UPDATE PROFILE (FIX UPLOAD FOTO PATH) ---
elseif ($act == 'update_profile') {
    $tmp_lahir = validTeks($_POST['tmp_lahir']);
    $tgl_lahir = validTeks($_POST['tgl_lahir']);
    $alamat    = validTeks($_POST['alamat']);
    $kota      = validTeks($_POST['kota']);
    $no_ktp    = validTeks($_POST['no_ktp']);
    $no_telp   = validTeks($_POST['no_telp']);
    
    $query_foto = "";
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "../../penggajian/pages/pegawai/photo/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $nama_file_fisik = $nik_login . "." . $ext; // Nama file di folder: 123.jpg
        $target_file = $target_dir . $nama_file_fisik;
        
        if(move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            // Path yang disimpan di DB: pages/pegawai/photo/123.jpg
            $nama_file_db = "pages/pegawai/photo/" . $nama_file_fisik;
            $query_foto = ", photo='$nama_file_db'";
        }
    }

    $sql_peg = "UPDATE pegawai SET tmp_lahir='$tmp_lahir', tgl_lahir='$tgl_lahir', alamat='$alamat', kota='$kota', no_ktp='$no_ktp' $query_foto WHERE nik='$nik_login'";
    
    if(!mysqli_query($konektor, $sql_peg)) {
        echo json_encode(['status'=>'error', 'message'=>'Gagal update pegawai: '.mysqli_error($konektor)]);
        exit;
    }

    $cek_ptg = fetch_assoc("SELECT nip FROM petugas WHERE nip='$nik_login'");
    if($cek_ptg) mysqli_query($konektor, "UPDATE petugas SET no_telp='$no_telp' WHERE nip='$nik_login'");
    else {
        $cek_dr = fetch_assoc("SELECT kd_dokter FROM dokter WHERE kd_dokter='$nik_login'");
        if($cek_dr) mysqli_query($konektor, "UPDATE dokter SET no_telp='$no_telp' WHERE kd_dokter='$nik_login'");
    }

    echo json_encode(['status'=>'success', 'message'=>'Data diri berhasil diperbarui']);
    exit;
}
?>