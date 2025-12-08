<?php
// Lokasi: htdocs/orthanc_injector/gatekeeper_check.php
require_once 'config.php';

// FIX: Buat koneksi database terlebih dahulu!
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("DB_ERROR"); // Jangan sampai crash, cukup return error text
}

if (!isset($_GET['acsn'])) die("ERR_PARAM");

$acsn = $conn->real_escape_string($_GET['acsn']);

// Query Cek
$sql = "SELECT id_servicerequest FROM satu_sehat_servicerequest_radiologi WHERE noorder = '$acsn' LIMIT 1";
$res = $conn->query($sql);

if ($res && $res->num_rows > 0) {
    $r = $res->fetch_assoc();
    // Jika kolom id_servicerequest terisi, berarti READY
    echo (!empty($r['id_servicerequest'])) ? "READY" : "WAIT";
} else {
    // Data belum ada sama sekali di tabel bridging
    echo "WAIT";
}

$conn->close();
?>