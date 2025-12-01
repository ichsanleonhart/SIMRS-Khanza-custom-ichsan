<?php
session_start();
require_once('../../conf/conf.php');

// Set Header JSON agar respon rapi
header('Content-Type: application/json');

if (!isset($_SESSION['hrd_login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi habis, silakan login ulang']);
    exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

if($act == 'search') {
    // Output HTML untuk pencarian tetap sama, tidak perlu JSON
    header('Content-Type: text/html'); 
    $kw = validTeks($_POST['kw']);
    $sql = "SELECT id, nik, nama FROM pegawai WHERE stts_aktif='AKTIF' AND (nik LIKE '%$kw%' OR nama LIKE '%$kw%') LIMIT 5";
    $res = bukaquery($sql);
    if(mysqli_num_rows($res) > 0) {
        while($r = mysqli_fetch_assoc($res)) {
            echo "<div class='flex justify-between bg-gray-700 p-3 rounded border border-gray-600 mb-2'>
                  <span class='font-mono text-sm'><b>{$r['nik']}</b><br>{$r['nama']}</span>
                  <button onclick=\"pilih('{$r['id']}','{$r['nik']}','{$r['nama']}')\" class='bg-blue-600 hover:bg-blue-500 px-3 py-1 rounded text-xs font-bold shadow'>PILIH</button>
                  </div>";
        }
    } else {
        echo "<div class='text-yellow-400 text-sm'>Pegawai tidak ditemukan</div>";
    }
    exit;
} 

elseif($act == 'save') {
    $id = $_POST['id'];
    $nik = $_POST['nik'];
    $desc = $_POST['desc']; // JSON string
    $img = $_POST['img'];
    
    // 1. Cek Validitas Data
    if(empty($id) || empty($desc) || empty($img)) {
        echo json_encode(['status' => 'error', 'message' => 'Data wajah/ID kosong. Ulangi scan.']);
        exit;
    }

    // 2. Cek Permission Folder
    $folder_foto = "../photo_enrollment/";
    if (!file_exists($folder_foto)) {
        if (!mkdir($folder_foto, 0777, true)) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal membuat folder photo_enrollment. Cek permission server.']);
            exit;
        }
    }
    
    if (!is_writable($folder_foto)) {
        echo json_encode(['status' => 'error', 'message' => 'Folder photo_enrollment tidak bisa ditulisi (Permission Denied). Lakukan chmod 777.']);
        exit;
    }

    // 3. Simpan File Foto
    $path = $folder_foto . $nik . ".jpg";
    $image_parts = explode(";base64,", $img);
    
    if (count($image_parts) < 2) {
        echo json_encode(['status' => 'error', 'message' => 'Format gambar base64 salah.']);
        exit;
    }
    
    $image_decoded = base64_decode($image_parts[1]);
    
    if(file_put_contents($path, $image_decoded) === false) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menulis file gambar ke server.']);
        exit;
    }

    // 4. Simpan ke Database (Gunakan MySQLi Object dari conf.php jika memungkinkan, atau query manual)
    $konektor = bukakoneksi();
    // Escape string untuk keamanan JSON
    $desc_safe = mysqli_real_escape_string($konektor, $desc);
    
    // Cek data lama
    $cek = mysqli_query($konektor, "SELECT id FROM face_enrollment WHERE user_id='$id'");
    
    if(mysqli_num_rows($cek) > 0) {
        $sql = "UPDATE face_enrollment SET face_descriptor='$desc_safe', photo='$path' WHERE user_id='$id'";
    } else {
        $sql = "INSERT INTO face_enrollment (user_id, nik, face_descriptor, photo) VALUES ('$id', '$nik', '$desc_safe', '$path')";
    }

    if(mysqli_query($konektor, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Data Wajah Berhasil Disimpan ke Database!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . mysqli_error($konektor)]);
    }
    mysqli_close($konektor);
    exit;
}
?>