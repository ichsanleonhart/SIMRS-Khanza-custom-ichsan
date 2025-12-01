<?php
session_start();
require_once('../../conf/conf.php');

header('Content-Type: application/json');

// 1. KEAMANAN SESSION
if (!isset($_SESSION['jadwal_login'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
    exit();
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$dep_akses = $_SESSION['jadwal_dep']; // 'ALL' atau 'D001'

// --- FUNGSI 1: AMBIL DAFTAR SHIFT (DROPDOWN) ---
if ($act == 'get_shifts') {
    $id_pegawai = validTeks($_GET['id_pegawai']);
    
    // Ambil departemen pegawai target
    $q_peg = fetch_assoc("SELECT departemen FROM pegawai WHERE id='$id_pegawai'");
    $dep_id = $q_peg['departemen'];

    // Validasi Hak Akses (Jika user bukan admin, dilarang ambil data departemen lain)
    if ($dep_akses != 'ALL' && $dep_akses != $dep_id) {
        echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki akses ke departemen ini']);
        exit();
    }

    // Ambil Shift dari jam_jaga sesuai departemen
    $shifts = [];
    $q_jam = bukaquery("SELECT shift, jam_masuk, jam_pulang FROM jam_jaga WHERE dep_id='$dep_id' ORDER BY jam_masuk ASC");
    
    while ($r = mysqli_fetch_assoc($q_jam)) {
        $shifts[] = [
            'kode' => $r['shift'],
            'label' => $r['shift'] . " (" . substr($r['jam_masuk'],0,5) . " - " . substr($r['jam_pulang'],0,5) . ")"
        ];
    }

    // Tambahkan Opsi Wajib (Hardcoded)
    $shifts[] = ['kode' => 'Libur', 'label' => 'Libur / Lepas Jaga'];
    $shifts[] = ['kode' => 'Cuti', 'label' => 'Cuti'];
    $shifts[] = ['kode' => '', 'label' => '- Kosong -'];

    echo json_encode(['status' => 'success', 'data' => $shifts]);
}

// --- FUNGSI 2: AMBIL DATA JADWAL EXISTING ---
elseif ($act == 'get_schedule') {
    $id = validTeks($_GET['id']);
    $bln = validTeks($_GET['bulan']);
    $thn = validTeks($_GET['tahun']);
    $jenis = validTeks($_GET['jenis']); // 'reguler' atau 'tambahan'

    $table = ($jenis == 'tambahan') ? 'jadwal_tambahan' : 'jadwal_pegawai';

    $q = fetch_assoc("SELECT * FROM $table WHERE id='$id' AND bulan='$bln' AND tahun='$thn'");

    if ($q) {
        echo json_encode(['status' => 'success', 'found' => true, 'data' => $q]);
    } else {
        echo json_encode(['status' => 'success', 'found' => false, 'data' => null]);
    }
}

// --- FUNGSI 3: SIMPAN JADWAL (INSERT / UPDATE) ---
elseif ($act == 'save_schedule') {
    // Terima JSON Data
    $input = json_decode(file_get_contents('php://input'), true);

    $id = validTeks($input['id']);
    $bln = validTeks($input['bulan']);
    $thn = validTeks($input['tahun']);
    $jenis = validTeks($input['jenis']);
    $data_hari = $input['data']; // Array h1..h31

    $table = ($jenis == 'tambahan') ? 'jadwal_tambahan' : 'jadwal_pegawai';

    // Cek apakah sudah ada data?
    $cek = fetch_assoc("SELECT id FROM $table WHERE id='$id' AND bulan='$bln' AND tahun='$thn'");

    $konektor = bukakoneksi();

    if ($cek) {
        // --- UPDATE ---
        $set_query = [];
        for ($i = 1; $i <= 31; $i++) {
            $val = isset($data_hari['h'.$i]) ? $data_hari['h'.$i] : '';
            $set_query[] = "h$i = '$val'";
        }
        $set_string = implode(", ", $set_query);
        
        $sql = "UPDATE $table SET $set_string WHERE id='$id' AND bulan='$bln' AND tahun='$thn'";
    } else {
        // --- INSERT ---
        $cols = "id, tahun, bulan";
        $vals = "'$id', '$thn', '$bln'";
        
        for ($i = 1; $i <= 31; $i++) {
            $cols .= ", h$i";
            $val = isset($data_hari['h'.$i]) ? $data_hari['h'.$i] : '';
            $vals .= ", '$val'";
        }
        
        $sql = "INSERT INTO $table ($cols) VALUES ($vals)";
    }

    if (mysqli_query($konektor, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Jadwal berhasil disimpan']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal simpan: ' . mysqli_error($konektor)]);
    }
    mysqli_close($konektor);
}
?>