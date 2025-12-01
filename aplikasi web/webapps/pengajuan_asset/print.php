<?php
/*
 * ==================================================================
 * PRINT.PHP (PENGEMBANGAN APLIKASI PENGAJUAN ASET - V.16)
 * ==================================================================
 * [UPDATE V.12 - PERBAIKAN BUG]:
 * - Mengubah query $sql_v (data validasi) dari INNER JOIN ke LEFT JOIN.
 * - Ini untuk mencegah data validasi hilang jika NIK validator (misal: admin)
 * tidak ada di tabel pegawai.
 * - Menambahkan pengecekan 'nama_validator' jika null.
 *
 * Dibuat kompatibel dengan PHP 7.3
 */

session_start();

// Komentar: Validasi Sesi
if (!isset($_SESSION['nik_pengajuan_asset'])) {
    die("Sesi tidak valid. Silakan login kembali.");
}

// Komentar: Memanggil file konfigurasi lokal
include 'config_pengajuan_asset.php';
// Komentar: Memanggil library mPDF (pastikan path-nya benar)
require_once __DIR__ . '/lib/mpdf-8.0.17/vendor/autoload.php'; 
// Komentar: Memanggil library phpqrcode
require_once __DIR__ . '/lib/phpqrcode/qrlib.php';

// Komentar: Ambil parameter dari URL
$no_surat = isset($_GET['id']) ? $_GET['id'] : '';
$tipe_cetak = isset($_GET['tipe']) ? $_GET['tipe'] : '';
$nik_login = $_SESSION['nik_pengajuan_asset'];
$nama_login = $_SESSION['nama_pengajuan_asset'];
$role_login = $_SESSION['role_pengajuan_asset'];

if (empty($no_surat) || empty($tipe_cetak)) {
    die("Parameter tidak lengkap.");
}

// Komentar: Buka koneksi database
$konektor = bukakoneksi();
if (!$konektor) {
    die("Koneksi database gagal.");
}

// Komentar: Ambil data nama instansi DAN LOGO
$nama_instansi = "RSK";
$logo_base64 = ""; // Variabel untuk menyimpan logo
try {
    $setting_sql = "SELECT setting.nama_instansi, setting.logo FROM setting LIMIT 1";
    $setting_result = mysqli_query($konektor, $setting_sql);
    if ($setting_row = mysqli_fetch_assoc($setting_result)) {
        $nama_instansi = $setting_row['nama_instansi'];
        // Komentar: Konversi BLOB logo ke Base64 agar bisa ditampilkan di HTML
        if (!empty($setting_row['logo'])) {
            $logo_base64 = base64_encode($setting_row['logo']);
        }
    }
} catch (Exception $e) {
    // Biarkan default
}

// Komentar: Query data header (Gunakan alias untuk JOIN ganda ke tabel pegawai)
$sql_h = "
    SELECT 
        pengajuan_asset.*,
        pegawai_pengaju.nama AS nama_pengaju,
        pegawai_pengaju.jbtn AS jbtn_pengaju,
        pegawai_pengaju.departemen AS dep_pengaju,
        pegawai_pj.nama AS nama_pj_logum,
        pegawai_approver_logum.nama AS nama_approver_logum,
        pegawai_approver_direktur.nama AS nama_approver_direktur
    FROM pengajuan_asset
    INNER JOIN pegawai AS pegawai_pengaju ON pengajuan_asset.nik = pegawai_pengaju.nik
    INNER JOIN pegawai AS pegawai_pj ON pengajuan_asset.nik_pj = pegawai_pj.nik
    LEFT JOIN pegawai AS pegawai_approver_logum ON pengajuan_asset.user_approval_logum = pegawai_approver_logum.nik
    LEFT JOIN pegawai AS pegawai_approver_direktur ON pengajuan_asset.user_approval_direktur = pegawai_approver_direktur.nik
    WHERE 
        pengajuan_asset.no_surat_pengajuan = ?
";
$stmt_h = mysqli_prepare($konektor, $sql_h);
mysqli_stmt_bind_param($stmt_h, "s", $no_surat);
mysqli_stmt_execute($stmt_h);
$result_h = mysqli_stmt_get_result($stmt_h);
$header = mysqli_fetch_assoc($result_h);
mysqli_stmt_close($stmt_h);

if (!$header) {
    die("Data pengajuan tidak ditemukan.");
}

// Komentar: Query data detail item
$sql_d = "SELECT * FROM pengajuan_asset_detail WHERE no_surat_pengajuan = ? ORDER BY no_urut";
$stmt_d = mysqli_prepare($konektor, $sql_d);
mysqli_stmt_bind_param($stmt_d, "s", $no_surat);
mysqli_stmt_execute($stmt_d);
$result_d = mysqli_stmt_get_result($stmt_d);
$items = [];
while($row_d = mysqli_fetch_assoc($result_d)) {
    $items[] = $row_d;
}
mysqli_stmt_close($stmt_d);

// Komentar: Query data validasi (jika tipe=validasi_barang)
    $validasi = [];
    if ($tipe_cetak == 'validasi_barang') {
        // [PERBAIKAN V.16] Menambahkan harga_realisasi_satuan
        $sql_v = "
            SELECT 
                pengajuan_asset_validasi.no_urut_detail,
                pengajuan_asset_validasi.tanggal_validasi,
                pengajuan_asset_validasi.jumlah_datang,
                pengajuan_asset_validasi.harga_realisasi_satuan,
                pengajuan_asset_validasi.catatan_validasi,
                pengajuan_asset_validasi.foto_bukti_datang,
                pegawai.nama AS nama_validator
            FROM pengajuan_asset_validasi
            LEFT JOIN pegawai ON pengajuan_asset_validasi.user_validasi_logum = pegawai.nik
            WHERE pengajuan_asset_validasi.no_surat_pengajuan = ?
            ORDER BY pengajuan_asset_validasi.no_urut_detail, pengajuan_asset_validasi.tanggal_validasi
        ";
        $stmt_v = mysqli_prepare($konektor, $sql_v);
        mysqli_stmt_bind_param($stmt_v, "s", $no_surat);
        mysqli_stmt_execute($stmt_v);
        $result_v = mysqli_stmt_get_result($stmt_v);
        while($row_v = mysqli_fetch_assoc($result_v)) {
            // Kelompokkan berdasarkan no_urut
            $validasi[$row_v['no_urut_detail']][] = $row_v;
        }
        mysqli_stmt_close($stmt_v);
    }

// ==================================================================
// LOGIKA PEMBUATAN QR CODE (SESUAI REQUEST)
// ==================================================================
$teks_qr = "Data tidak valid";
$approver_name = "N/A";
$approval_time = "N/A";

// Tentukan data QR berdasarkan tipe cetak
if ($tipe_cetak == 'approval_logum' && !empty($header['user_approval_logum'])) {
    $approver_name = $header['nama_approver_logum'];
    $approval_time = $header['waktu_aprove_logum'];
} elseif ($tipe_cetak == 'approval_direktur' && !empty($header['user_approval_direktur'])) {
    $approver_name = $header['nama_approver_direktur'];
    $approval_time = $header['waktu_approval_direktur'];
} elseif ($tipe_cetak == 'validasi_barang') {
    $approver_name = $nama_login; // Dibuat oleh Logum yang sedang login
    $approval_time = date('Y-m-d H:i:s');
}

// Buat token unik untuk verifikasi
$token = bin2hex(random_bytes(20));
$url_verifikasi = APP_URL_PENGAJUAN_ASET . "/verifikasi.php?token=" . $token;

// Simpan token ke database
try {
    $sql_token = "INSERT INTO pengajuan_asset_verifikasi (token, no_surat_pengajuan, jenis_surat, waktu_dibuat) VALUES (?, ?, ?, NOW())";
    $stmt_token = mysqli_prepare($konektor, $sql_token);
    mysqli_stmt_bind_param($stmt_token, "sss", $token, $no_surat, $tipe_cetak);
    mysqli_stmt_execute($stmt_token);
    mysqli_stmt_close($stmt_token);
} catch (Exception $e) {
    die("Gagal menyimpan token verifikasi: " . $e->getMessage());
}

// Buat teks final untuk QR Code (sesuai request)
$teks_qr = "Surat ini ditandatangani secara elektrik oleh " . $approver_name . " pada " . $approval_time . " di " . $nama_instansi . ". Validasi surat : " . $url_verifikasi;

// Buat file gambar QR Code sementara
$qr_file_path = sys_get_temp_dir() . '/qr_' . $token . '.png';
QRcode::png($teks_qr, $qr_file_path, QR_ECLEVEL_L, 3);
// Komentar: Siapkan data gambar QR untuk disisipkan ke HTML
$qr_image_data = "data:image/png;base64," . base64_encode(file_get_contents($qr_file_path));
@unlink($qr_file_path); // Hapus file sementara

mysqli_close($konektor);

// ==================================================================
// LOGIKA PEMBUATAN KONTEN HTML UNTUK PDF
// ==================================================================
$judul_surat = "Surat Pengajuan Aset";
$konten_html = "";

// Komentar: Ini adalah CSS untuk PDF
$css = "
<style>
    body { font-family: sans-serif; font-size: 10pt; }
    .header { border-bottom: 2px solid #000; padding-bottom: 10px; }
    .header-table { width: 100%; border-collapse: collapse; }
    .logo-cell { width: 70px; vertical-align: middle; }
    .logo-img { height: 60px; width: 60px; }
    .title-cell { text-align: center; vertical-align: middle; }
    .title-cell h2 { margin: 0; padding: 0; font-size: 16pt; }
    .title-cell h3 { margin: 0; padding: 0; font-size: 14pt; font-weight: normal; }
    
    .content { margin-top: 20px; }
    .info { width: 100%; border-collapse: collapse; }
    .info td { padding: 3px 5px; vertical-align: top; }
    .info .label { width: 25%; }
    .info .separator { width: 2%; }
    
    .item-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 9pt; }
    .item-table th, .item-table td { border: 1px solid #777; padding: 6px; }
    .item-table th { background-color: #f2f2f2; text-align: center; }
    .item-table .text-right { text-align: right; }
    .item-table .text-center { text-align: center; }
    
    .signature-box { margin-top: 40px; width: 100%; }
    .signature { width: 250px; float: right; text-align: center; }
    .signature .qr-code { width: 100px; height: 100px; margin: 0 auto 10px auto; }
    .signature p { margin: 0; padding: 0; }
    .signature .nama { font-weight: bold; text-decoration: underline; margin-top: 50px; }
    .clearfix { clear: both; }
    
    .lampiran-validasi img { max-height: 100px; margin: 5px; }
</style>
";

// Komentar: Data Header Info
$data_info_header = "
<div class='content'>
    <table class='info'>
        <tr>
            <td class='label'>No. Surat</td>
            <td class='separator'>:</td>
            <td>" . htmlspecialchars($header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8') . "</td>
            <td class='label'>Tanggal Pengajuan</td>
            <td class='separator'>:</td>
            <td>" . htmlspecialchars(date('d-m-Y', strtotime($header['tanggal_pengajuan'])), ENT_QUOTES, 'UTF-8') . "</td>
        </tr>
        <tr>
            <td class='label'>Pengaju</td>
            <td class='separator'>:</td>
            <td>" . htmlspecialchars($header['nama_pengaju'] . ' (' . $header['dep_pengaju'] . ')', ENT_QUOTES, 'UTF-8') . "</td>
            <td class='label'>PJ Logistik</td>
            <td class='separator'>:</td>
            <td>" . htmlspecialchars($header['nama_pj_logum'], ENT_QUOTES, 'UTF-8') . "</td>
        </tr>
        
        <tr>
            <td class='label'>Latar Belakang</td>
            <td class='separator'>:</td>
            <td colspan='4'>" . nl2br(htmlspecialchars($header['uraian_latar_belakang'], ENT_QUOTES, 'UTF-8')) . "</td>
        </tr>
        <tr>
            <td class='label'>Tujuan</td>
            <td class='separator'>:</td>
            <td colspan='4'>" . nl2br(htmlspecialchars($header['tujuan_pengajuan'], ENT_QUOTES, 'UTF-8')) . "</td>
        </tr>
         <tr>
            <td class='label'>Target Sasaran</td>
            <td class='separator'>:</td>
            <td>" . htmlspecialchars($header['target_sasaran'], ENT_QUOTES, 'UTF-8') . "</td>
            <td class='label'>Lokasi</td>
            <td class='separator'>:</td>
            <td>" . htmlspecialchars($header['lokasi_pengajuan'], ENT_QUOTES, 'UTF-8') . "</td>
        </tr>
        <tr>
            <td class='label'>Urgensi</td>
            <td class='separator'>:</td>
            <td>" . htmlspecialchars($header['urgensi'], ENT_QUOTES, 'UTF-8') . "</td>
            <td class='label'>Keterangan</td>
            <td class='separator'>:</td>
            <td>" . htmlspecialchars($header['keterangan'], ENT_QUOTES, 'UTF-8') . "</td>
        </tr>
        </table>
</div>
";

// Komentar: Fungsi KOP SURAT baru dengan Logo
function buatKopSurat($logo_base64, $nama_instansi, $judul_surat) {
    $logo_html = "";
    if (!empty($logo_base64)) {
        // Komentar: Tentukan tipe mime gambar (asumsi PNG jika tidak terdeteksi, atau sesuaikan)
        $mime_type = "image/png"; // Default
        // Coba deteksi tipe gambar dari data base64 (sederhana)
        if (substr($logo_base64, 0, 4) === 'iVBO') {
            $mime_type = "image/png";
        } elseif (substr($logo_base64, 0, 4) === '/9j/4') {
            $mime_type = "image/jpeg";
        }
        $logo_html = "<img src='data:" . $mime_type . ";base64," . $logo_base64 . "' class='logo-img'>";
    }
    
    return "
    <div class='header'>
        <table class='header-table'>
            <tr>
                <td class='logo-cell'>" . $logo_html . "</td>
                <td class='title-cell'>
                    <h2>" . htmlspecialchars($nama_instansi, ENT_QUOTES, 'UTF-8') . "</h2>
                    <h3>" . $judul_surat . "</h3>
                </td>
            </tr>
        </table>
    </div>";
}


// Komentar: Switch case untuk menentukan judul dan konten tabel
switch ($tipe_cetak) {
    case 'approval_logum':
        $judul_surat = "BUKTI APPROVAL LOGISTIK UMUM";
        $konten_html = $css . buatKopSurat($logo_base64, $nama_instansi, $judul_surat) . $data_info_header . "
            <table class='item-table'>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Barang</th>
                        <th>Jml Minta</th>
                        <th>Harga (Rp)</th>
                        <th>Total (Rp)</th>
                        <th>Status Approval</th>
                        <th>Jml Disetujui</th>
                        <th>Catatan Logum</th>
                    </tr>
                </thead>
                <tbody>";
        foreach ($items as $item) {
            $konten_html .= "
                    <tr>
                        <td class='text-center'>" . $item['no_urut'] . "</td>
                        <td>" . htmlspecialchars($item['nama_barang'], ENT_QUOTES, 'UTF-8') . "</td>
                        <td class='text-center'>" . number_format($item['jumlah_diminta'], 0, ',', '.') . "</td>
                        <td class='text-right'>" . number_format($item['harga_satuan'], 0, ',', '.') . "</td>
                        <td class='text-right'>" . number_format($item['jumlah_diminta'] * $item['harga_satuan'], 0, ',', '.') . "</td>
                        <td class='text-center'>" . htmlspecialchars($item['status_approval_logum'], ENT_QUOTES, 'UTF-8') . "</td>
                        <td class='text-center'>" . number_format($item['jumlah_disetujui_logum'], 0, ',', '.') . "</td>
                        <td>" . htmlspecialchars($item['catatan_logum'], ENT_QUOTES, 'UTF-8') . "</td>
                    </tr>";
        }
        $konten_html .= "
                </tbody>
            </table>
            <div class='signature-box'>
                <div class='signature'>
                    <p>Disetujui oleh,</p>
                    <div class='qr-code'><img src='" . $qr_image_data . "' width='100' height='100'></div>
                    <p class='nama'>" . htmlspecialchars($header['nama_approver_logum'], ENT_QUOTES, 'UTF-8') . "</p>
                    <p>Logistik Umum</p>
                </div>
                <div class='clearfix'></div>
            </div>
        ";
        break;

    case 'approval_direktur':
        $judul_surat = "SURAT PERSETUJUAN PENGADAAN ASET";
        $konten_html = $css . buatKopSurat($logo_base64, $nama_instansi, $judul_surat) . $data_info_header . "
            <table class='item-table'>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Barang</th>
                        <th>Jml Minta</th>
                        <th>Jml Disetujui Logum</th>
                        <th>Jml Disetujui Direktur</th>
                        <th>Harga (Rp)</th>
                        <th>Total Disetujui (Rp)</th>
                        <th>Catatan Direktur</th>
                    </tr>
                </thead>
                <tbody>";
        foreach ($items as $item) {
            if ($item['status_approval_logum'] == 'Disetujui' && $item['status_approval_direktur'] == 'Disetujui') {
                $konten_html .= "
                    <tr>
                        <td class='text-center'>" . $item['no_urut'] . "</td>
                        <td>" . htmlspecialchars($item['nama_barang'], ENT_QUOTES, 'UTF-8') . "</td>
                        <td class='text-center'>" . number_format($item['jumlah_diminta'], 0, ',', '.') . "</td>
                        <td class='text-center'>" . number_format($item['jumlah_disetujui_logum'], 0, ',', '.') . "</td>
                        <td class='text-center' style='background-color:#d4edda;'>" . number_format($item['jumlah_disetujui_direktur'], 0, ',', '.') . "</td>
                        <td class='text-right'>" . number_format($item['harga_satuan'], 0, ',', '.') . "</td>
                        <td class='text-right'>" . number_format($item['jumlah_disetujui_direktur'] * $item['harga_satuan'], 0, ',', '.') . "</td>
                        <td>" . htmlspecialchars($item['catatan_direktur'], ENT_QUOTES, 'UTF-8') . "</td>
                    </tr>";
            }
        }
        $konten_html .= "
                    <tr>
                        <td colspan='6' class='text-right'><b>Total Nilai Disetujui</b></td>
                        <td class='text-right' style='background-color:#d4edda;'><b>" . number_format($header['total_disetujui'], 0, ',', '.') . "</b></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            <div class='signature-box'>
                <div class='signature'>
                    <p>Disetujui oleh,</p>
                    <div class='qr-code'><img src='" . $qr_image_data . "' width='100' height='100'></div>
                    <p class='nama'>" . htmlspecialchars($header['nama_approver_direktur'], ENT_QUOTES, 'UTF-8') . "</p>
                    <p>Direktur</p>
                </div>
                <div class='clearfix'></div>
            </div>
        ";
        break;

    case 'validasi_barang':
        $judul_surat = "BUKTI VALIDASI KEDATANGAN BARANG";
        $konten_html = $css . buatKopSurat($logo_base64, $nama_instansi, $judul_surat) . $data_info_header . "
            <table class='item-table'>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Barang</th>
                        <th>Jml Disetujui</th>
                        <th>Detail Kedatangan</th>
                    </tr>
                </thead>
                <tbody>";
        foreach ($items as $item) {
            if ($item['status_approval_direktur'] == 'Disetujui') {
                $konten_html .= "
                    <tr>
                        <td class='text-center'>" . $item['no_urut'] . "</td>
                        <td>" . htmlspecialchars($item['nama_barang'], ENT_QUOTES, 'UTF-8') . "</td>
                        <td class='text-center'>" . number_format($item['jumlah_disetujui_direktur'], 0, ',', '.') . "</td>
                        <td>";
                
                // Komentar: Cek apakah ada data validasi untuk item ini
                if (isset($validasi[$item['no_urut']])) {
                    foreach ($validasi[$item['no_urut']] as $log_validasi) {
                        $nama_validator = !empty($log_validasi['nama_validator']) ? htmlspecialchars($log_validasi['nama_validator'], ENT_QUOTES, 'UTF-8') : '<i>(NIK tidak terdaftar)</i>';
                        
                        // [PERBAIKAN V.16] Menambahkan tampilan harga realisasi
                        $konten_html .= "
                            <div style='border-bottom: 1px dashed #ccc; padding-bottom: 5px; margin-bottom: 5px;'>
                                <b>Tgl:</b> " . htmlspecialchars(date('d-m-Y H:i', strtotime($log_validasi['tanggal_validasi'])), ENT_QUOTES, 'UTF-8') . "<br>
                                <b>Jml:</b> " . number_format($log_validasi['jumlah_datang'], 0, ',', '.') . " pcs<br>
                                <b>Harga Realisasi:</b> Rp " . number_format($log_validasi['harga_realisasi_satuan'], 0, ',', '.') . " /pcs<br>
                                <b>Subtotal Realisasi:</b> Rp " . number_format($log_validasi['jumlah_datang'] * $log_validasi['harga_realisasi_satuan'], 0, ',', '.') . "<br>
                                <b>Oleh:</b> " . $nama_validator . "<br>
                                <b>Catatan:</b> " . htmlspecialchars($log_validasi['catatan_validasi'], ENT_QUOTES, 'UTF-8') . "<br>";
                        if (!empty($log_validasi['foto_bukti_datang'])) {
                            $gambar_path = $_SERVER['DOCUMENT_ROOT'] . '/webapps/pengajuan_asset/' . $log_validasi['foto_bukti_datang'];
                            if (file_exists($gambar_path)) {
                                $konten_html .= "<div class='lampiran-validasi'><img src='" . htmlspecialchars($gambar_path, ENT_QUOTES, 'UTF-8') . "'></div>";
                            } else {
                                $konten_html .= "<div class='lampiran-validasi'><i>(Foto tidak ditemukan)</i></div>";
                            }
                        }
                        $konten_html .= "</div>";
                    }
                } else {
                    $konten_html .= "<i>(Belum ada barang datang)</i>";
                }
                $konten_html .= "</td></tr>";
            }
        }
        $konten_html .= "
                </tbody>
            </table>
            <div class='signature-box'>
                <div class='signature'>
                    <p>Dicetak oleh,</p>
                    <div class='qr-code'><img src='" . $qr_image_data . "' width='100' height='100'></div>
                    <p class='nama'>" . htmlspecialchars($nama_login, ENT_QUOTES, 'UTF-8') . "</p>
                    <p>Petugas Logistik Umum</p>
                </div>
                <div class='clearfix'></div>
            </div>
        ";
        break;

    default:
        die("Tipe cetak tidak dikenal.");
}

// ==================================================================
// PROSES GENERATE PDF
// ==================================================================
try {
    // Komentar: Konfigurasi mPDF
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 10,
        'margin_bottom' => 10,
        'tempDir' => __DIR__ . '/lib/mpdf-8.0.17/tmp' 
    ]);
    
    // Komentar: Tulis HTML ke PDF
    $mpdf->WriteHTML($konten_html);
    
    // Komentar: Outputkan PDF ke browser
    $mpdf->Output($judul_surat . '_' . $no_surat . '.pdf', 'I'); 

} catch (\Mpdf\MpdfException $e) {
    die("Gagal membuat PDF: " . $e->getMessage());
}

exit;
?>