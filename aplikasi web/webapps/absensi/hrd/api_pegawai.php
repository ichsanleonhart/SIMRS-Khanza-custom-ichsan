<?php
session_start();
require_once('../../conf/conf.php');
header('Content-Type: application/json');

if (!isset($_SESSION['hrd_login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi habis, silakan login ulang']);
    exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$konektor = bukakoneksi();

// --- 1. AMBIL OPSI DROPDOWN (REFERENSI) ---
if ($act == 'get_options') {
    $data = [];
    
    // Helper function biar kodenya rapi
    function get_ref($table, $col_id, $col_name) {
        global $konektor;
        $res = [];
        $q = mysqli_query($konektor, "SELECT $col_id as id, $col_name as nama FROM $table ORDER BY $col_name ASC");
        while ($r = mysqli_fetch_assoc($q)) $res[] = $r;
        return $res;
    }

    $data['departemen'] = get_ref('departemen', 'dep_id', 'nama');
    $data['jenjang']    = get_ref('jnj_jabatan', 'kode', 'nama');
    $data['kelompok']   = get_ref('kelompok_jabatan', 'kode_kelompok', 'nama_kelompok');
    $data['resiko']     = get_ref('resiko_kerja', 'kode_resiko', 'nama_resiko');
    $data['emergency']  = get_ref('emergency_index', 'kode_emergency', 'nama_emergency');
    $data['pendidikan'] = get_ref('pendidikan', 'tingkat', 'tingkat'); // id & nama sama
    $data['stts_wp']    = get_ref('stts_wp', 'stts', 'ktg');
    $data['stts_kerja'] = get_ref('stts_kerja', 'stts', 'ktg');
    $data['bidang']     = get_ref('bidang', 'nama', 'nama');
    $data['bank']       = get_ref('bank', 'namabank', 'namabank');

    echo json_encode($data);
    exit;
}

// --- 2. LIST DATA PEGAWAI (DATATABLES) ---
elseif ($act == 'list') {
    $sql = "SELECT id, nik, nama, jbtn, departemen, stts_aktif, photo FROM pegawai ORDER BY stts_aktif ASC, nama ASC";
    $result = bukaquery($sql);
    $data = [];
    while ($r = mysqli_fetch_assoc($result)) {
        // Fix path foto untuk display (relatif dari folder hrd)
        $r['photo_url'] = "../../" . $r['photo']; 
        $data[] = $r;
    }
    echo json_encode(['data' => $data]);
    exit;
}

// --- 3. GENERATE NIP OTOMATIS (SUGGESTION) ---
elseif ($act == 'gen_nik') {
    // Format: YYYY.XXX (Contoh: 2023.005)
    $prefix = date('Y') . ".";
    // Cari NIK terakhir yang berawalan tahun ini
    $q = fetch_assoc("SELECT nik FROM pegawai WHERE nik LIKE '$prefix%' ORDER BY nik DESC LIMIT 1");
    if ($q) {
        $last_no = (int) substr($q['nik'], 5); // Ambil angka setelah titik
        $new_no = $last_no + 1;
    } else {
        $new_no = 1;
    }
    $new_nik = $prefix . sprintf("%03d", $new_no); // Padding 001
    echo json_encode(['nik' => $new_nik]);
    exit;
}

// --- 4. AMBIL DETAIL PEGAWAI (UNTUK EDIT) ---
elseif ($act == 'detail') {
    $id = validTeks($_GET['id']);
    $data = fetch_assoc("SELECT * FROM pegawai WHERE id='$id'");
    if($data) {
        $data['photo_url'] = "../../" . $data['photo'];
        echo json_encode(['status'=>'success', 'data'=>$data]);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Data tidak ditemukan']);
    }
    exit;
}

// --- 5. SIMPAN DATA (INSERT / UPDATE) ---
elseif ($act == 'save') {
    // Sanitasi Input Wajib
    $id             = isset($_POST['id']) ? validTeks($_POST['id']) : ''; // Kosong = Insert
    $nik            = validTeks($_POST['nik']);
    $nama           = validTeks($_POST['nama']);
    $jk             = validTeks($_POST['jk']);
    $no_ktp         = validTeks($_POST['no_ktp']);
    
    // Validasi Dasar
    if(empty($nik) || empty($nama) || empty($no_ktp)) {
        echo json_encode(['status'=>'error', 'message'=>'NIK, Nama, dan No KTP wajib diisi.']); exit;
    }

    // --- VALIDASI DUPLIKAT KTP ---
    // Cek apakah KTP sudah dipakai orang lain?
    $ktp_check_sql = "SELECT id, nama FROM pegawai WHERE no_ktp='$no_ktp'";
    if(!empty($id)) $ktp_check_sql .= " AND id != '$id'"; // Kecualikan diri sendiri saat edit
    
    $cek_ktp = fetch_assoc($ktp_check_sql);
    if($cek_ktp) {
        echo json_encode(['status'=>'error', 'message'=>"Gagal! No KTP sudah digunakan oleh pegawai: " . $cek_ktp['nama']]); exit;
    }

    // --- VALIDASI DUPLIKAT NIK (Hanya Insert) ---
    if(empty($id)) {
        $cek_nik = fetch_assoc("SELECT id FROM pegawai WHERE nik='$nik'");
        if($cek_nik) {
            echo json_encode(['status'=>'error', 'message'=>"NIK $nik sudah terdaftar."]); exit;
        }
    }

    // --- HANDLE FOTO ---
    $photo_db = "pages/pegawai/photo/default.jpg"; // Default
    if(!empty($id)) {
        // Ambil foto lama jika edit
        $old = fetch_assoc("SELECT photo FROM pegawai WHERE id='$id'");
        if($old) $photo_db = $old['photo'];
    }

    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "../../pages/pegawai/photo/";
        
        // Buat folder jika belum ada (meskipun biasanya sudah ada bawaan Khanza)
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $new_name = $nik . "." . $ext;
            $target_file = $target_dir . $new_name;
            
            if(move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                $photo_db = "pages/pegawai/photo/" . $new_name;
            }
        }
    }

    // --- MAPPING KOLOM (34 Kolom) ---
    // Kita gunakan array agar mudah menyusun query
    $data = [
        'nik' => $nik, 'nama' => $nama, 'jk' => $jk, 'no_ktp' => $no_ktp,
        'jbtn' => validTeks($_POST['jbtn']),
        'jnj_jabatan' => validTeks($_POST['jnj_jabatan']),
        'kode_kelompok' => validTeks($_POST['kode_kelompok']),
        'kode_resiko' => validTeks($_POST['kode_resiko']),
        'kode_emergency' => validTeks($_POST['kode_emergency']),
        'departemen' => validTeks($_POST['departemen']),
        'bidang' => validTeks($_POST['bidang']),
        'stts_wp' => validTeks($_POST['stts_wp']),
        'stts_kerja' => validTeks($_POST['stts_kerja']),
        'npwp' => validTeks($_POST['npwp']),
        'pendidikan' => validTeks($_POST['pendidikan']),
        'gapok' => (double)$_POST['gapok'],
        'tmp_lahir' => validTeks($_POST['tmp_lahir']),
        'tgl_lahir' => validTeks($_POST['tgl_lahir']),
        'alamat' => validTeks($_POST['alamat']),
        'kota' => validTeks($_POST['kota']),
        'mulai_kerja' => validTeks($_POST['mulai_kerja']),
        'ms_kerja' => validTeks($_POST['ms_kerja']),
        'indexins' => validTeks($_POST['indexins']),
        'bpd' => validTeks($_POST['bpd']), // Bank
        'rekening' => validTeks($_POST['rekening']),
        'stts_aktif' => validTeks($_POST['stts_aktif']),
        'wajibmasuk' => (int)$_POST['wajibmasuk'],
        'pengurang' => (double)$_POST['pengurang'],
        'indek' => (int)$_POST['indek'],
        'mulai_kontrak' => validTeks($_POST['mulai_kontrak']),
        'cuti_diambil' => (int)$_POST['cuti_diambil'],
        'dankes' => (double)$_POST['dankes'],
        'photo' => $photo_db
    ];

    if(empty($id)) {
        // --- INSERT ---
        $cols = implode(", ", array_keys($data));
        $vals = "'" . implode("', '", array_values($data)) . "'";
        $sql = "INSERT INTO pegawai ($cols) VALUES ($vals)";
    } else {
        // --- UPDATE ---
        $set = "";
        foreach($data as $k => $v) {
            $set .= "$k = '$v', ";
        }
        $set = rtrim($set, ", ");
        $sql = "UPDATE pegawai SET $set WHERE id='$id'";
    }

    if(mysqli_query($konektor, $sql)) {
        echo json_encode(['status'=>'success', 'message'=>'Data pegawai berhasil disimpan.']);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Database Error: '.mysqli_error($konektor)]);
    }
    exit;
}

// --- 6. HAPUS PEGAWAI ---
elseif ($act == 'delete') {
    $id = validTeks($_POST['id']);
    
    // Hapus foto fisik jika bukan default
    $q = fetch_assoc("SELECT photo FROM pegawai WHERE id='$id'");
    if($q && $q['photo'] != 'pages/pegawai/photo/default.jpg') {
        $path = "../../" . $q['photo'];
        if(file_exists($path)) unlink($path);
    }

    // Hapus data (relasi foreign key di Khanza biasanya ON DELETE RESTRICT, jadi pastikan bersih)
    // Tapi user minta hapus pegawai saja.
    // Kita coba hapus, jika gagal karena relasi, user akan diberitahu.
    $sql = "DELETE FROM pegawai WHERE id='$id'";
    
    if(mysqli_query($konektor, $sql)) {
        echo json_encode(['status'=>'success', 'message'=>'Pegawai berhasil dihapus']);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Gagal hapus (Mungkin data terikat di tabel lain): '.mysqli_error($konektor)]);
    }
    exit;
}
?>