<?php
session_start();
require_once('../../conf/conf.php');

// Header JSON
header('Content-Type: application/json');

if (!isset($_SESSION['hrd_login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi habis']);
    exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

// 1. CARI PEGAWAI (SEARCH BOX LAMA - Tetap Dipertahankan)
if($act == 'search') {
    header('Content-Type: text/html'); 
    $kw = validTeks($_POST['kw']);
    $sql = "SELECT p.id, p.nik, p.nama 
            FROM pegawai p 
            LEFT JOIN face_enrollment f ON p.id = f.user_id
            WHERE p.stts_aktif='AKTIF' 
            AND f.id IS NULL 
            AND (p.nik LIKE '%$kw%' OR p.nama LIKE '%$kw%') 
            LIMIT 5";
            
    $res = bukaquery($sql);
    if(mysqli_num_rows($res) > 0) {
        while($r = mysqli_fetch_assoc($res)) {
            echo "<div class='flex justify-between bg-gray-700 p-3 rounded border border-gray-600 mb-2'>
                  <span class='font-mono text-sm text-gray-300'><b>{$r['nik']}</b><br>{$r['nama']}</span>
                  <button onclick=\"pilih('{$r['id']}','{$r['nik']}','{$r['nama']}')\" class='bg-blue-600 hover:bg-blue-500 px-3 py-1 rounded text-xs font-bold shadow text-white'>PILIH</button>
                  </div>";
        }
    } else {
        echo "<div class='text-yellow-400 text-sm'>Pegawai tidak ditemukan atau sudah terdaftar.</div>";
    }
    exit;
} 

// 2. SIMPAN WAJAH BARU
elseif($act == 'save') {
    $id = $_POST['id'];
    $nik = $_POST['nik'];
    $desc = $_POST['desc']; 
    $img = $_POST['img'];
    
    // Validasi Folder
    $folder_foto = "../photo_enrollment/";
    if (!file_exists($folder_foto)) mkdir($folder_foto, 0777, true);
    
    // Simpan File
    $path = $folder_foto . $nik . ".jpg";
    $image_parts = explode(";base64,", $img);
    $image_decoded = base64_decode($image_parts[1]);
    file_put_contents($path, $image_decoded);

    // Simpan DB
    $konektor = bukakoneksi();
    $desc_safe = mysqli_real_escape_string($konektor, $desc);
    
    // Hapus data lama jika ada (Clean Install)
    mysqli_query($konektor, "DELETE FROM face_enrollment WHERE user_id='$id'");
    
    $sql = "INSERT INTO face_enrollment (user_id, nik, face_descriptor, photo) VALUES ('$id', '$nik', '$desc_safe', '$path')";

    if(mysqli_query($konektor, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Wajah Berhasil Didaftarkan!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . mysqli_error($konektor)]);
    }
    mysqli_close($konektor);
    exit;
}

// 3. LOAD LIST TERDAFTAR (KANAN)
elseif($act == 'list_enrolled') {
    $kw = isset($_GET['q']) ? validTeks($_GET['q']) : '';
    $filter = $kw ? "AND (p.nama LIKE '%$kw%' OR p.nik LIKE '%$kw%')" : "";
    
    $sql = "SELECT f.id, p.nama, p.nik, f.photo 
            FROM face_enrollment f 
            JOIN pegawai p ON f.user_id = p.id 
            WHERE p.stts_aktif='AKTIF' $filter
            ORDER BY f.created_at DESC LIMIT 20";
            
    $res = bukaquery($sql);
    $data = [];
    while($r = mysqli_fetch_assoc($res)) {
        $data[] = $r;
    }
    echo json_encode($data);
    exit;
}

// 4. HAPUS DATA WAJAH
elseif($act == 'delete') {
    $id_enroll = validTeks($_POST['id']);
    $d = fetch_assoc("SELECT photo FROM face_enrollment WHERE id='$id_enroll'");
    
    if($d) {
        if(file_exists($d['photo'])) unlink($d['photo']);
        bukaquery("DELETE FROM face_enrollment WHERE id='$id_enroll'");
        echo json_encode(['status'=>'success']);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Data tidak ditemukan']);
    }
    exit;
}

// 5. (BARU) LIST PEGAWAI BELUM ENROLLMENT (KIRI)
elseif($act == 'list_unenrolled') {
    $kw = isset($_GET['q']) ? validTeks($_GET['q']) : '';
    $filter = $kw ? "AND (p.nama LIKE '%$kw%' OR p.nik LIKE '%$kw%')" : "";

    // Ambil pegawai aktif yang tidak punya record di face_enrollment
    $sql = "SELECT p.id, p.nik, p.nama, d.nama as dep
            FROM pegawai p
            LEFT JOIN face_enrollment f ON p.id = f.user_id
            LEFT JOIN departemen d ON p.departemen = d.dep_id
            WHERE p.stts_aktif='AKTIF' AND f.id IS NULL $filter
            ORDER BY p.nama ASC LIMIT 50";

    $res = bukaquery($sql);
    $data = [];
    while($r = mysqli_fetch_assoc($res)) {
        $data[] = $r;
    }
    echo json_encode($data);
    exit;
}
?>