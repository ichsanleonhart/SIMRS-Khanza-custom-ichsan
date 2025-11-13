<?php
// ... (Fungsi-fungsi helper: validate_csrf, autoNomorSurat, handleFileUpload, updateStatusPengadaanHeader) ...
function validate_csrf($token) {
    if (empty($token) || !hash_equals($_SESSION['csrf_token_asset'], $token)) {
        return false;
    }
    return true;
}
function autoNomorSurat($konektor, $tanggal) {
    $sql = "SELECT IFNULL(MAX(CONVERT(RIGHT(pengajuan_asset.no_surat_pengajuan,3),SIGNED)),0) FROM pengajuan_asset WHERE pengajuan_asset.tanggal_pengajuan = ?";
    $stmt = mysqli_prepare($konektor, $sql);
    mysqli_stmt_bind_param($stmt, "s", $tanggal);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_array($result);
    $max_num = $row[0];
    mysqli_stmt_close($stmt);
    $next_num = $max_num + 1;
    $no_urut = sprintf('%03s', $next_num);
    $tgl_parts = explode('-', $tanggal);
    $tgl_formatted = $tgl_parts[0] . $tgl_parts[1] . $tgl_parts[2];
    return "ASET" . $tgl_formatted . $no_urut;
}
function handleFileUpload($file, $no_surat, $upload_dir, $prefix) {
    if (isset($file) && $file['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $file_type = mime_content_type($file['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            return ['error' => 'Format file tidak valid (Hanya JPG, PNG, GIF, PDF).'];
        }
        if ($file['size'] > 5000000) {
            return ['error' => 'Ukuran file terlalu besar (Maks 5MB).'];
        }
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = $prefix . $no_surat . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            return ['success' => true, 'path' => $upload_path];
        } else {
            return ['error' => 'Gagal memindahkan file. Pastikan folder ' . $upload_dir . ' writable.'];
        }
    }
    return ['success' => false];
}
function updateStatusPengadaanHeader($konektor, $no_surat_pengajuan) {
    $sql_check = "
        SELECT 
            SUM(pengajuan_asset_detail.jumlah_disetujui_direktur) AS total_disetujui,
            SUM(pengajuan_asset_detail.jumlah_sudah_divalidasi) AS total_validasi
        FROM pengajuan_asset_detail
        WHERE pengajuan_asset_detail.no_surat_pengajuan = ? AND pengajuan_asset_detail.status_approval_direktur = 'Disetujui'
    ";
    $stmt_check = mysqli_prepare($konektor, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "s", $no_surat_pengajuan);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $row_check = mysqli_fetch_assoc($result_check);
    mysqli_stmt_close($stmt_check);
    if ($row_check) {
        $total_disetujui = (double)$row_check['total_disetujui'];
        $total_validasi = (double)$row_check['total_validasi'];
        $status_pengadaan_baru = 'Proses Pengadaan'; 
        if ($total_disetujui == 0) {
            $status_pengadaan_baru = 'Selesai Penuh'; 
        } elseif ($total_validasi >= $total_disetujui) {
            $status_pengadaan_baru = 'Selesai Penuh';
        } elseif ($total_validasi > 0 && $total_validasi < $total_disetujui) {
            $status_pengadaan_baru = 'Selesai Sebagian';
        }
        $sql_update_header = "UPDATE pengajuan_asset SET status_pengadaan = ? WHERE no_surat_pengajuan = ?";
        $stmt_update = mysqli_prepare($konektor, $sql_update_header);
        mysqli_stmt_bind_param($stmt_update, "ss", $status_pengadaan_baru, $no_surat_pengajuan);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
    }
}

?>