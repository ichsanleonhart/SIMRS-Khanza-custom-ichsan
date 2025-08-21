<?php
include 'koneksi.php';

// ID yang tidak ingin diubah password-nya
$excluded_ids = [4, 5, 6];

$query = "SELECT id, nik FROM users WHERE id NOT IN (" . implode(',', $excluded_ids) . ")";
$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $id = $row['id'];
    $nik = $row['nik'];

    // Generate bcrypt hash dari nik
    $hashed_password = password_hash($nik, PASSWORD_DEFAULT);

    // Update password ke database
    $update = "UPDATE users SET password_hash = '" . mysqli_real_escape_string($conn, $hashed_password) . "' WHERE id = $id";
    mysqli_query($conn, $update);
}

echo "Password berhasil direset menggunakan NIK untuk semua user (kecuali ID 4, 5, dan 6).";
?>
