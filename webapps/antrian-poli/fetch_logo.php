<?php
require_once("../conf/conf.php"); // adjust path if needed

$koneksi = bukakoneksi();
$sql = "SELECT logo FROM setting LIMIT 1";
$result = mysqli_query($koneksi, $sql);

if ($row = mysqli_fetch_assoc($result)) {
    header("Content-Type: image/png"); // adjust if your logo is JPEG, etc.
    echo $row['logo'];
} else {
    http_response_code(404);
    echo "Logo not found.";
}
?>