<?php
// Lokasi: htdocs/orthanc_injector/gatekeeper_check.php
require_once 'config.php';
if (!isset($_GET['acsn'])) die("ERR");

$acsn = $conn->real_escape_string($_GET['acsn']);
// Pastikan nama tabel benar sesuai DB Khanza kamu
$sql = "SELECT id_servicerequest FROM satu_sehat_servicerequest_radiologi WHERE noorder = '$acsn' LIMIT 1";
$res = $conn->query($sql);

if ($res && $res->num_rows > 0) {
    $r = $res->fetch_assoc();
    echo (!empty($r['id_servicerequest'])) ? "READY" : "WAIT";
} else {
    echo "WAIT";
}
?>