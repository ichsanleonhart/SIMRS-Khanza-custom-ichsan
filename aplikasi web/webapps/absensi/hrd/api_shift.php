<?php
session_start();
require_once('../../conf/conf.php');

if (!isset($_SESSION['hrd_login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
    exit;
}

// PENTING: Gunakan validTeks untuk string biasa
$id = validTeks($_POST['id']);
$dep_id = validTeks($_POST['dep_id']);
$shift = validTeks($_POST['shift']);

// PERBAIKAN DISINI: 
// JANGAN pakai validTeks() untuk jam karena akan menghapus titik dua (:)
// Gunakan addslashes() agar format 08:00 tetap utuh.
$masuk = isset($_POST['jam_masuk']) ? addslashes($_POST['jam_masuk']) : '';
$pulang = isset($_POST['jam_pulang']) ? addslashes($_POST['jam_pulang']) : '';

if(empty($dep_id) || empty($shift) || empty($masuk)) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit;
}

$konektor = bukakoneksi();

if(empty($id)) {
    // INSERT BARU
    $cek = mysqli_query($konektor, "SELECT no_id FROM jam_jaga WHERE dep_id='$dep_id' AND shift='$shift'");
    if(mysqli_num_rows($cek) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Shift ini sudah ada di departemen tersebut.']);
        exit;
    }

    $sql = "INSERT INTO jam_jaga (dep_id, shift, jam_masuk, jam_pulang) VALUES ('$dep_id', '$shift', '$masuk', '$pulang')";
} else {
    // UPDATE
    $sql = "UPDATE jam_jaga SET dep_id='$dep_id', shift='$shift', jam_masuk='$masuk', jam_pulang='$pulang' WHERE no_id='$id'";
}

if(mysqli_query($konektor, $sql)) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal simpan: ' . mysqli_error($konektor)]);
}
?>