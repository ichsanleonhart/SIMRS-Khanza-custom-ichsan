<?php
// orthanc.php SIMPAN FILE INI DI ROOT FOLDER WEBAPPS, contoh: 192.168.1.2/webapps/orthanc.php
if (isset($_FILES['file']['name']) && !empty($_FILES['file']['name'])) {
    $name = $_FILES['file']['name'];
    $tmp_name = $_FILES['file']['tmp_name'];
    
    // Ambil parameter doc dari URL, misal: radiologi/pages/upload
    // Jika tidak ada, default ke folder radiologi
    $target_dir = isset($_GET['doc']) ? $_GET['doc'] . "/" : "radiologi/pages/upload/";
    
    // Pastikan folder tujuan ada
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $location = $target_dir . $name;

    if (move_uploaded_file($tmp_name, $location)) {
        echo "Success";
    } else {
        echo "Failed";
    }
}
?>