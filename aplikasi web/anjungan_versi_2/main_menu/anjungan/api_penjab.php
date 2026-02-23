<?php
include_once '../conf/conf.php';
header('Content-Type: application/json; charset=utf-8');

$sql = "SELECT kd_pj, png_jawab 
        FROM penjab 
        WHERE status='1' 
        ORDER BY png_jawab";
$res = bukaquery($sql);


$penjab = [];
while ($row = mysqli_fetch_assoc($res)) {
    $penjab[] = $row;
}

echo json_encode($penjab, JSON_UNESCAPED_UNICODE);
?>