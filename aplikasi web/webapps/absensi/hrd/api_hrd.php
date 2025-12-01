<?php
// File: /var/www/html/webapps/absensi/hrd/api_hrd.php
session_start();
require_once('../../conf/conf.php');
if (!isset($_SESSION['hrd_login'])) die;

if($_POST['act'] == 'hapus') {
    $id = $_POST['id'];
    $jam = $_POST['jam'];
    
    // Hapus Foto Fisik
    $d = fetch_assoc("SELECT photo FROM rekap_presensi WHERE id='$id' AND jam_datang='$jam'");
    if($d) {
        $path = "../../../" . $d['photo'];
        if(file_exists($path)) unlink($path);
    }
    
    // Hapus Data
    bukaquery("DELETE FROM rekap_presensi WHERE id='$id' AND jam_datang='$jam'");
}
?>