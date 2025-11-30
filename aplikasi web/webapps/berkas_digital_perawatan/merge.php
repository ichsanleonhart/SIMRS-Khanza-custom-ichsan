<?php
// File: /webapps/berkas_digital_perawatan/merge.php
require_once('../conf/conf.php');
require_once('fpdf.php'); // Pastikan file fpdf.php ada di folder yang sama

// Konfigurasi Path
// Path fisik di server tempat file Khanza disimpan
$storage_path = "../berkasrawat/"; 
// Folder sementara untuk proses (harus writable/chmod 777)
$temp_dir = __DIR__ . "/tmp/"; 

// Buat folder tmp jika belum ada
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

$no_rawat = isset($_GET['no_rawat']) ? validTeks4($_GET['no_rawat'], 20) : '';
if(empty($no_rawat)) die("No Rawat Kosong");

// 1. Ambil List File
$query = "SELECT bdp.lokasi_file 
          FROM berkas_digital_perawatan bdp 
          WHERE bdp.no_rawat = '$no_rawat' 
          ORDER BY bdp.kode ASC";
$hasil = bukaquery($query);

if(mysqli_num_rows($hasil) == 0) {
    echo "<script>alert('Tidak ada berkas untuk digabung!'); window.history.back();</script>";
    exit;
}

$files_to_merge = [];
$temp_files_created = [];
$counter = 1;

// 2. Pre-processing (Konversi Gambar ke PDF & Validasi)
while($row = mysqli_fetch_assoc($hasil)) {
    $original_file = $storage_path . $row['lokasi_file'];
    
    if(!file_exists($original_file)) continue;

    $ext = strtolower(pathinfo($original_file, PATHINFO_EXTENSION));
    $temp_filename = $temp_dir . "part_" . $counter . ".pdf";

    if(in_array($ext, ['jpg', 'jpeg', 'png'])) {
        // --- KONVERSI GAMBAR KE PDF MENGGUNAKAN FPDF ---
        try {
            $pdf = new FPDF();
            $pdf->AddPage();
            // Auto fit image logic (A4 size: 210mm x 297mm)
            // Simpel: Margin 10mm, lebar 190mm
            $pdf->Image($original_file, 10, 10, 190); 
            $pdf->Output('F', $temp_filename);
            
            $files_to_merge[] = $temp_filename;
            $temp_files_created[] = $temp_filename; // Tandai untuk dihapus nanti
        } catch (Exception $e) {
            // Skip jika gambar rusak
        }

    } elseif ($ext == 'pdf') {
        // --- UNTUK PDF, LANGSUNG PAKAI ---
        // Tips: Kita tidak copy file asli, cukup referensi pathnya langsung ke Ghostscript
        // KECUALI jika nama filenya mengandung spasi aneh, lebih aman copy ke tmp
        $safe_pdf_path = $temp_dir . "safe_part_" . $counter . ".pdf";
        copy($original_file, $safe_pdf_path);
        
        $files_to_merge[] = $safe_pdf_path;
        $temp_files_created[] = $safe_pdf_path;
    }
    $counter++;
}

if(empty($files_to_merge)) die("Gagal memproses file. Pastikan file fisik ada.");

// 3. EKSEKUSI GHOSTSCRIPT (The Heavy Lifting)
// Output file akhir
$final_output = $temp_dir . "GABUNGAN_" . str_replace(['/','\\'], '-', $no_rawat) . ".pdf";
$files_command_string = implode(' ', $files_to_merge);

// Command Linux Ghostscript
// -dNOPAUSE -dBATCH : Jangan tanya user, langsung jalan
// -sDEVICE=pdfwrite : Outputnya PDF
// -sOutputFile : Lokasi simpan
$command = "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile={$final_output} {$files_command_string}";

// Jalankan Command
exec($command, $output, $return_var);

// 4. Download & Cleanup
if (file_exists($final_output)) {
    // Force Download
    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="'.basename($final_output).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($final_output));
    readfile($final_output);

    // --- CLEANUP (PENTING AGAR SERVER TIDAK PENUH) ---
    // Hapus file partisi sementara
    foreach($temp_files_created as $file) {
        if(file_exists($file)) unlink($file);
    }
    // Hapus file final
    unlink($final_output);
    exit;
} else {
    echo "Gagal menggabungkan PDF. Kode Error Ghostscript: " . $return_var;
    echo "<br>Debug Command: " . $command;
}
?>