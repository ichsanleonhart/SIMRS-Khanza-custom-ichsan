<?php
/*
 * File: /webapps/berkas_digital_perawatan/merge.php
 * Fungsi: Menggabungkan berkas terpilih menjadi satu PDF
 * Update: Support filtering checkbox selection
 */

require_once('../conf/conf.php');
require_once('fpdf.php'); // Pastikan library ini ada

// 1. SETUP & VALIDASI
$storage_path = "../berkasrawat/"; 
$temp_dir = __DIR__ . "/tmp/"; 

// Pastikan folder tmp ada
if (!file_exists($temp_dir)) {
    if (!mkdir($temp_dir, 0777, true) && !is_dir($temp_dir)) {
        die("Gagal membuat direktori temporary.");
    }
}

// Tangkap No Rawat (Bisa dari POST atau GET sebagai fallback)
$no_rawat = isset($_REQUEST['no_rawat']) ? validTeks4($_REQUEST['no_rawat'], 20) : '';
if(empty($no_rawat)) die("No Rawat tidak ditemukan.");

// Tangkap List Kode File yang dipilih (Array)
$selected_codes = isset($_POST['selected_files']) ? $_POST['selected_files'] : [];

// 2. BUILD QUERY
$koneksi = bukakoneksi();

// Query Dasar
$sql = "SELECT bdp.lokasi_file, mbd.nama as jenis_berkas 
        FROM berkas_digital_perawatan bdp 
        JOIN master_berkas_digital mbd ON bdp.kode = mbd.kode
        WHERE bdp.no_rawat = '$no_rawat'";

// Tambahkan Filter jika User Memilih (Jika kosong/select all via GET, ambil semua)
if (!empty($selected_codes)) {
    // Sanitasi array agar aman masuk ke SQL IN (...)
    $safe_codes = array_map(function($code) use ($koneksi) {
        return "'" . mysqli_real_escape_string($koneksi, $code) . "'";
    }, $selected_codes);
    
    $in_clause = implode(',', $safe_codes);
    $sql .= " AND bdp.kode IN ($in_clause)";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Jika via POST tapi array kosong, berarti user uncheck semua
    echo "<script>alert('Tidak ada file yang dipilih!'); window.history.back();</script>";
    exit;
}

$sql .= " ORDER BY bdp.kode ASC";
$hasil = mysqli_query($koneksi, $sql);

if(mysqli_num_rows($hasil) == 0) {
    echo "<script>alert('Berkas tidak ditemukan atau tidak ada yang dipilih.'); window.history.back();</script>";
    exit;
}

// 3. PROSES MERGE (GHOSTSCRIPT HYBRID)
$files_to_merge = [];
$temp_files_created = [];
$counter = 1;

while($row = mysqli_fetch_assoc($hasil)) {
    $original_file = $storage_path . $row['lokasi_file'];
    
    // Skip jika file fisik hilang
    if(!file_exists($original_file)) continue;

    $ext = strtolower(pathinfo($original_file, PATHINFO_EXTENSION));
    $uniq = uniqid(); // Cegah bentrok nama
    $temp_filename = $temp_dir . $uniq . "_part_" . $counter . ".pdf";

    if(in_array($ext, ['jpg', 'jpeg', 'png'])) {
        // --- GAMBAR KE PDF ---
        try {
            $pdf = new FPDF();
            $pdf->AddPage();
            // Fit Image A4 (210mm) - margin 10mm = 190mm width
            $pdf->Image($original_file, 10, 10, 190); 
            $pdf->Output('F', $temp_filename);
            
            $files_to_merge[] = $temp_filename;
            $temp_files_created[] = $temp_filename;
        } catch (Exception $e) { }
    } elseif ($ext == 'pdf') {
        // --- PDF ASLI ---
        // Copy ke tmp agar aman saat diproses GS
        $safe_pdf = $temp_dir . $uniq . "_safe_" . $counter . ".pdf";
        copy($original_file, $safe_pdf);
        $files_to_merge[] = $safe_pdf;
        $temp_files_created[] = $safe_pdf;
    }
    $counter++;
}

if(empty($files_to_merge)) die("Gagal memproses file fisik.");

// Output Final Path
$clean_no_rawat = str_replace(['/','\\'], '-', $no_rawat);
$final_output = $temp_dir . "MERGED_" . $clean_no_rawat . "_" . date('His') . ".pdf";

// Command Ghostscript (Linux)
// Pastikan gs terinstall: sudo apt-get install ghostscript
$files_str = implode(' ', $files_to_merge);
$command = "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=\"{$final_output}\" {$files_str}";

// Eksekusi
exec($command, $output, $return_var);

// 4. DOWNLOAD & CLEANUP
if (file_exists($final_output)) {
    // Kirim Header Download
    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Berkas_'.$clean_no_rawat.'.pdf"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($final_output));
    readfile($final_output);

    // Hapus file temporary
    foreach($temp_files_created as $f) { if(file_exists($f)) unlink($f); }
    unlink($final_output);
    exit;
} else {
    echo "Terjadi kesalahan saat menggabungkan PDF (Ghostscript Error code: $return_var).";
}
?>