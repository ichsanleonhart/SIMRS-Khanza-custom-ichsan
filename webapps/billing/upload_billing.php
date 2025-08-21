<?php
include '../conf/conf.php';

header('Content-Type: application/json');

if (isset($_FILES['file'])) {
    $no_rawat = $_POST['no_rawat'];
    $file = $_FILES['file'];

    // Buat direktori jika belum ada
    $upload_dir = '../berkasrawat/pages/upload/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            echo json_encode(['success' => false, 'message' => 'Gagal membuat direktori upload']);
            exit;
        }
    }

    // Generate nama file
    $clean_norawat = str_replace('/', '', $no_rawat);
    $filename = 'Billing_' . $clean_norawat . '.pdf';
    $filepath = $upload_dir . $filename;

    // Validasi file
    if ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
        echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar (max 2MB)']);
        exit;
    }

    // Upload file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $lokasi_file = 'pages/upload/' . $filename;
        $sql_check = "SELECT lokasi_file FROM berkas_digital_perawatan WHERE no_rawat='$no_rawat' AND kode='001'";
        $result = bukaquery($sql_check);
        if (mysqli_num_rows($result) > 0) {
            // Update lokasi_file jika sudah ada
            $sql = "UPDATE berkas_digital_perawatan SET lokasi_file='$lokasi_file' WHERE no_rawat='$no_rawat' AND kode='001'";
        } else {
            // Insert baru jika belum ada
            $sql = "INSERT INTO berkas_digital_perawatan (no_rawat, kode, lokasi_file) VALUES ('$no_rawat', '001', '$lokasi_file')";
        }
        // Gunakan koneksi langsung agar dapat true/false
        $conn = bukaKoneksi();
        $queryResult = mysqli_query($conn, $sql);
        if ($queryResult) {
            echo json_encode(['success' => true]);
        } else {
            unlink($filepath); // hapus file jika gagal insert ke database
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ke database']);
        }
        mysqli_close($conn);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupload file']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Tidak ada file yang diupload']);
}
