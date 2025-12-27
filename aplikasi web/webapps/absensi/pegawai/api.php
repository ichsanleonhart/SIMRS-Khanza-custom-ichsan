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

    // Enkripsi Khanza
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

// --- CEK SESI UNTUK FITUR DI BAWAH INI ---
if (!isset($_SESSION['pegawai_login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi habis']);
    exit;
}

$nik_login = $_SESSION['pegawai_nik'];

// --- 2. GET DASHBOARD DATA ---
if ($act == 'dashboard') {
    // A. Ambil Data Pegawai
    $q_peg = "SELECT nama, departemen, cuti_diambil FROM pegawai WHERE nik='$nik_login'";
    $d_peg = fetch_assoc($q_peg);
    
    // B. Hitung Real-time Cuti yang SUDAH DISETUJUI tahun ini
    // Kita filter hanya yang 'Tahunan' dan status HRD 'Disetujui'
    $tahun_ini = date('Y');
    $q_pakai = "SELECT SUM(jumlah) as total_pakai 
                FROM pengajuan_cuti 
                WHERE nik='$nik_login' 
                AND status_persetujuan_HRD = 'Disetujui' 
                AND urgensi = 'Tahunan' 
                AND YEAR(tanggal_awal) = '$tahun_ini'";
    
    $d_pakai = fetch_assoc($q_pakai);
    $real_cuti_tahun_ini = $d_pakai['total_pakai'] ?? 0;

    // Total terpakai = Data manual di tabel pegawai (jika ada) + Data transaksi sistem
    // (Opsional: jika tabel pegawai.cuti_diambil jarang diupdate admin, kamu bisa hapus $d_peg['cuti_diambil'] dari rumus)
    $total_terpakai = $d_peg['cuti_diambil'] + $real_cuti_tahun_ini;

    // Asumsi jatah cuti tahunan = 12
    $jatah_cuti = 12;
    $sisa_cuti = $jatah_cuti - $total_terpakai;
    
    // Pastikan tidak minus (visual saja)
    if($sisa_cuti < 0) $sisa_cuti = 0;

    // C. History Pengajuan
    $q_hist = "SELECT pc.*, p.nama as nama_pj 
               FROM pengajuan_cuti pc 
               LEFT JOIN pegawai p ON pc.nik_pj = p.nik 
               WHERE pc.nik='$nik_login' 
               ORDER BY pc.tanggal DESC LIMIT 20";
    $r_hist = bukaquery($q_hist);
    $history = [];
    while($row = mysqli_fetch_assoc($r_hist)) {
        $history[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'nama' => $d_peg['nama'],
        'departemen' => $d_peg['departemen'],
        'cuti_diambil' => $total_terpakai, // Kirim data total
        'sisa_cuti' => $sisa_cuti,
        'history' => $history
    ]);
    exit;
}

// --- 3. GET LIST ATASAN (PRIORITAS DEPARTEMEN) ---
elseif ($act == 'get_atasan') {
    // Ambil dulu departemen user login
    $me = fetch_assoc("SELECT departemen FROM pegawai WHERE nik='$nik_login'");
    $my_dep = $me['departemen'];

    // Query Smart Sorting: Departemen sama ditaruh di atas (urutan 1), sisanya urutan 2
    $sql = "SELECT nik, nama, departemen, jbtn 
            FROM pegawai 
            WHERE stts_aktif = 'AKTIF' 
            ORDER BY (departemen = '$my_dep') DESC, nama ASC";
            
    $res = bukaquery($sql);
    $data = [];
    while($r = mysqli_fetch_assoc($res)) {
        $is_same = ($r['departemen'] == $my_dep);
        $data[] = [
            'nik' => $r['nik'],
            'nama' => $r['nama'],
            'jabatan' => $r['jbtn'],
            'group' => $is_same ? 'Satu Departemen (Disarankan)' : 'Departemen Lain'
        ];
    }
    echo json_encode($data);
    exit;
}

// --- 4. SIMPAN PENGAJUAN ---
elseif ($act == 'save') {
    $tgl_awal  = validTeks($_POST['tanggal_awal']);
    $tgl_akhir = validTeks($_POST['tanggal_akhir']);
    $urgensi   = validTeks($_POST['urgensi']);
    $alamat    = validTeks($_POST['alamat']);
    $alasan    = validTeks($_POST['kepentingan']);
    $nik_pj    = validTeks($_POST['nik_pj']);
    
    // Hitung Jumlah Hari
    $d1 = new DateTime($tgl_awal);
    $d2 = new DateTime($tgl_akhir);
    $interval = $d1->diff($d2);
    $jumlah_hari = $interval->days + 1; // +1 karena inklusif

    // Generate No Pengajuan: PC + YYYYMMDD + 001
    $prefix = "PC" . date('Ymd');
    $cek_no = fetch_assoc("SELECT max(no_pengajuan) as last FROM pengajuan_cuti WHERE no_pengajuan LIKE '$prefix%'");
    
    $urut = 1;
    if ($cek_no && $cek_no['last']) {
        $last_seq = substr($cek_no['last'], -3);
        $urut = intval($last_seq) + 1;
    }
    $no_pengajuan = $prefix . sprintf("%03d", $urut);

    // Insert
    $sql = "INSERT INTO pengajuan_cuti 
            (no_pengajuan, tanggal, tanggal_awal, tanggal_akhir, nik, urgensi, alamat, jumlah, kepentingan, nik_pj, status, status_persetujuan_HRD) 
            VALUES 
            ('$no_pengajuan', NOW(), '$tgl_awal', '$tgl_akhir', '$nik_login', '$urgensi', '$alamat', '$jumlah_hari', '$alasan', '$nik_pj', 'Proses Pengajuan', 'Proses Pengajuan')";

    if (mysqli_query($konektor, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Pengajuan berhasil dikirim.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal simpan: ' . mysqli_error($konektor)]);
    }
    exit;
}
?>