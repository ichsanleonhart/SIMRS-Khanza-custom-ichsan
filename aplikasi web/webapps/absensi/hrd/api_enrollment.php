<?php
// File: /var/www/html/webapps/absensi/hrd/api_enrollment.php
session_start();
// Bersihkan output buffer agar JSON murni
ob_start();
require_once('../../conf/conf.php');
ob_end_clean();

header('Content-Type: application/json');

if (!isset($_SESSION['hrd_login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi habis, login ulang']);
    exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$konektor = bukakoneksi();

// --- 1. SEARCH PEGAWAI (Untuk didaftarkan) ---
if($act == 'search') {
    // Output HTML partial untuk list pencarian
    header('Content-Type: text/html'); 
    $kw = validTeks($_POST['kw']);
    
    // Cari pegawai yang BELUM terdaftar di face_enrollment
    // Agar tidak double input
    $sql = "SELECT p.id, p.nik, p.nama, p.jbtn 
            FROM pegawai p 
            LEFT JOIN face_enrollment f ON p.id = f.user_id
            WHERE p.stts_aktif='AKTIF' 
            AND f.id IS NULL
            AND (p.nik LIKE '%$kw%' OR p.nama LIKE '%$kw%') 
            LIMIT 5";
            
    $res = mysqli_query($konektor, $sql);
    
    if(mysqli_num_rows($res) > 0) {
        while($r = mysqli_fetch_assoc($res)) {
            echo "<div class='flex justify-between items-center bg-gray-700 p-3 rounded border border-gray-600 mb-2 hover:bg-gray-600 transition'>
                  <div>
                    <span class='font-bold text-sm text-white'>{$r['nama']}</span><br>
                    <span class='text-xs text-gray-400'>{$r['nik']} - {$r['jbtn']}</span>
                  </div>
                  <button onclick=\"pilih('{$r['id']}','{$r['nik']}','{$r['nama']}')\" class='bg-blue-600 hover:bg-blue-500 text-white px-3 py-1 rounded text-xs font-bold shadow'>PILIH</button>
                  </div>";
        }
    } else {
        echo "<div class='text-yellow-400 text-sm italic'>Pegawai tidak ditemukan atau sudah terdaftar.</div>";
    }
    exit;
}

// --- 2. GET LIST SUDAH TERDAFTAR (Untuk HRD Monitoring) ---
elseif ($act == 'get_registered') {
    $kw = isset($_GET['q']) ? validTeks($_GET['q']) : '';
    $filter = "";
    if(!empty($kw)) {
        $filter = "AND (p.nama LIKE '%$kw%' OR p.nik LIKE '%$kw%')";
    }

    $sql = "SELECT f.id as fid, p.nik, p.nama, p.jbtn, f.photo, f.created_at 
            FROM face_enrollment f 
            JOIN pegawai p ON f.user_id = p.id
            WHERE p.stts_aktif='AKTIF' $filter
            ORDER BY f.created_at DESC LIMIT 20";
            
    $res = mysqli_query($konektor, $sql);
    $data = [];
    while($r = mysqli_fetch_assoc($res)) {
        // Path foto relatif untuk ditampilkan di web
        // Di DB tersimpan: absensi/photo_enrollment/xxx.jpg (Standar V4.2)
        // Kita butuh path dari HRD: ../../absensi/photo_enrollment
        // Cek dulu apakah path di DB diawali 'absensi/' atau langsung 'photo_enrollment/'
        
        $img = $r['photo'];
        if(strpos($img, 'http') === false) {
             $img = "../../" . $img; // Naik 2 level
        }
        
        $data[] = [
            'fid' => $r['fid'],
            'nik' => $r['nik'],
            'nama' => $r['nama'],
            'jbtn' => $r['jbtn'],
            'photo' => $img,
            'tgl' => date('d/m/Y', strtotime($r['created_at']))
        ];
    }
    echo json_encode($data);
    exit;
}

// --- 3. HAPUS DATA WAJAH (RESET) ---
elseif ($act == 'delete') {
    $fid = validTeks($_POST['fid']);
    
    // Ambil path foto dulu untuk dihapus fisik
    $q = mysqli_query($konektor, "SELECT photo FROM face_enrollment WHERE id='$fid'");
    $d = mysqli_fetch_assoc($q);
    
    if($d) {
        $path_fisik = "../../" . $d['photo']; // Sesuaikan path fisik server
        if(file_exists($path_fisik) && !is_dir($path_fisik)) {
            unlink($path_fisik);
        }
        
        // Hapus Database
        if(mysqli_query($konektor, "DELETE FROM face_enrollment WHERE id='$fid'")) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($konektor)]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan']);
    }
    exit;
}

// --- 4. SIMPAN WAJAH (ENROLLMENT BARU) ---
elseif($act == 'save') {
    $id = validTeks($_POST['id']);
    $nik = validTeks($_POST['nik']);
    $desc = $_POST['desc']; // JSON string descriptor
    $img = $_POST['img'];
    
    if(empty($id) || empty($desc) || empty($img)) {
        echo json_encode(['status' => 'error', 'message' => 'Data wajah tidak lengkap.']);
        exit;
    }

    // Persiapan Folder (Sesuai Standar V4.2)
    // Path Fisik: /var/www/html/webapps/absensi/photo_enrollment/
    $folderName = "photo_enrollment/"; 
    $targetDir = "../" . $folderName; // Naik satu dari folder HRD

    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    
    // Simpan File
    $fileName = $nik . "_ref.jpg";
    $image_parts = explode(";base64,", $img);
    $image_decoded = base64_decode($image_parts[1]);
    file_put_contents($targetDir . $fileName, $image_decoded);

    // Path Database (Relatif webapps/)
    $dbPath = "absensi/" . $folderName . $fileName;

    // Simpan DB
    $desc_safe = mysqli_real_escape_string($konektor, $desc);
    
    // Cek duplikasi (Delete dulu kalo ada, biar update)
    mysqli_query($konektor, "DELETE FROM face_enrollment WHERE user_id='$id'");

    $sql = "INSERT INTO face_enrollment (user_id, nik, face_descriptor, photo) 
            VALUES ('$id', '$nik', '$desc_safe', '$dbPath')";

    if(mysqli_query($konektor, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Wajah Berhasil Didaftarkan!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . mysqli_error($konektor)]);
    }
    exit;
}
?>