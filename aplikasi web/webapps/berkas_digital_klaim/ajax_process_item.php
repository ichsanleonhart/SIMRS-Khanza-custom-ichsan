<?php
/*
 * File: ajax_process_item.php
 * Fungsi: Merge otomatis semua berkas milik 1 pasien untuk bulk download
 */
session_start();
require_once('../conf/conf.php');
require_once('fpdf.php'); // Pastikan path fpdf benar

$storage_path = "../berkasrawat/"; 
$temp_dir = __DIR__ . "/tmp_bulk/"; // Folder temp khusus bulk

if (!file_exists($temp_dir)) { mkdir($temp_dir, 0777, true); }

$no_rawat = $_POST['no_rawat'];
$nm_pasien = preg_replace('/[^A-Za-z0-9 ]/', '', $_POST['nm_pasien']); // Bersihkan nama

$koneksi = bukakoneksi();

// Ambil SEMUA berkas milik pasien ini
$q_berkas = "SELECT lokasi_file FROM berkas_digital_perawatan 
             WHERE no_rawat = '$no_rawat' ORDER BY kode ASC";
$res_berkas = mysqli_query($koneksi, $q_berkas);

if(mysqli_num_rows($res_berkas) == 0) {
    echo json_encode(['status' => 'skip']); exit;
}

$files_to_merge = [];
$temp_files_created = [];
$counter = 1;

while($row = mysqli_fetch_assoc($res_berkas)) {
    $original_file = $storage_path . $row['lokasi_file'];
    if(!file_exists($original_file)) continue;

    $ext = strtolower(pathinfo($original_file, PATHINFO_EXTENSION));
    $uniq = uniqid();
    $temp_part = $temp_dir . $uniq . "_" . $counter . ".pdf";

    if(in_array($ext, ['jpg', 'jpeg', 'png'])) {
        try {
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->Image($original_file, 10, 10, 190); 
            $pdf->Output('F', $temp_part);
            $files_to_merge[] = $temp_part;
            $temp_files_created[] = $temp_part;
        } catch (Exception $e) { }
    } elseif ($ext == 'pdf') {
        // Copy pdf asli ke temp
        copy($original_file, $temp_part);
        $files_to_merge[] = $temp_part;
        $temp_files_created[] = $temp_part;
    }
    $counter++;
}

if(count($files_to_merge) > 0) {
    // Nama file hasil: 2025-12-18-0001_NAMA_PASIEN.pdf
    $clean_rawat = str_replace(['/','\\'], '-', $no_rawat);
    $final_name = $clean_rawat . "_" . str_replace(' ', '_', $nm_pasien) . ".pdf";
    $final_path = $temp_dir . $final_name;

    // Merge via Ghostscript
    $files_str = implode(' ', $files_to_merge);
    $command = "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=\"{$final_path}\" {$files_str}";
    exec($command);

    // Hapus partisi temp
    foreach($temp_files_created as $f) { if(file_exists($f)) unlink($f); }

    if(file_exists($final_path)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'file' => $final_path]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'GS failed']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No files merged']);
}
?>