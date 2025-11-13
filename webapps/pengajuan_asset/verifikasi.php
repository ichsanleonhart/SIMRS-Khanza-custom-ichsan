<?php
/*
 * ==================================================================
 * VERIFIKASI.PHP (PENGEMBANGAN APLIKASI PENGAJUAN ASET - TAHAP 3)
 * ==================================================================
 * Halaman ini adalah halaman publik untuk validasi QR Code.
 *
 * Dibuat kompatibel dengan PHP 7.3
 */

// Komentar: Memanggil file konfigurasi lokal
include 'config_pengajuan_asset.php';
$konektor = bukakoneksi();

if (!$konektor) {
    die("Koneksi gagal.");
}

// Komentar: Ambil token dari URL
$token = isset($_GET['token']) ? $_GET['token'] : '';
$data_surat = null;
$nama_instansi = "RSK";

if (empty($token)) {
    $error = "Token tidak ditemukan.";
} else {
    // Komentar: Ambil data nama instansi
    $setting_sql = "SELECT setting.nama_instansi FROM setting LIMIT 1";
    $setting_result = mysqli_query($konektor, $setting_sql);
    if ($setting_row = mysqli_fetch_assoc($setting_result)) {
        $nama_instansi = $setting_row['nama_instansi'];
    }
    
    // Komentar: Cari token di database
    $sql = "
        SELECT 
            pengajuan_asset_verifikasi.no_surat_pengajuan, 
            pengajuan_asset_verifikasi.jenis_surat, 
            pengajuan_asset_verifikasi.waktu_dibuat,
            pengajuan_asset.tanggal_pengajuan,
            pegawai_pengaju.nama AS nama_pengaju,
            pegawai_pj.nama AS nama_pj_logum,
            pegawai_approver_logum.nama AS nama_approver_logum,
            pegawai_approver_direktur.nama AS nama_approver_direktur
        FROM pengajuan_asset_verifikasi
        INNER JOIN pengajuan_asset ON pengajuan_asset_verifikasi.no_surat_pengajuan = pengajuan_asset.no_surat_pengajuan
        INNER JOIN pegawai AS pegawai_pengaju ON pengajuan_asset.nik = pegawai_pengaju.nik
        INNER JOIN pegawai AS pegawai_pj ON pengajuan_asset.nik_pj = pegawai_pj.nik
        LEFT JOIN pegawai AS pegawai_approver_logum ON pengajuan_asset.user_approval_logum = pegawai_approver_logum.nik
        LEFT JOIN pegawai AS pegawai_approver_direktur ON pengajuan_asset.user_approval_direktur = pegawai_approver_direktur.nik
        WHERE pengajuan_asset_verifikasi.token = ?
    ";
    
    $stmt = mysqli_prepare($konektor, $sql);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data_surat = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$data_surat) {
        $error = "Token verifikasi tidak valid atau tidak ditemukan.";
    }
}

mysqli_close($konektor);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Dokumen</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background-color: #fff; padding: 2.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 90%; max-width: 600px; }
        .header { text-align: center; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        .header h2 { margin: 0; color: #333; }
        .header p { margin: 5px 0 0; color: #666; }
        .status { padding: 15px; border-radius: 5px; font-size: 1.1rem; font-weight: bold; text-align: center; margin-bottom: 20px; }
        .status.valid { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status.invalid { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info-table { width: 100%; }
        .info-table td { padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
        .info-table td:first-child { font-weight: bold; color: #555; width: 40%; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2><?php echo htmlspecialchars($nama_instansi, ENT_QUOTES, 'UTF-8'); ?></h2>
            <p>Sistem Verifikasi Dokumen Elektronik</p>
        </div>

        <?php if (isset($data_surat) && $data_surat): ?>
            <div class="status valid">
                DOKUMEN TERVERIFIKASI (SAH)
            </div>
            <table class="info-table">
                <tr>
                    <td>Jenis Dokumen</td>
                    <td><?php echo htmlspecialchars(ucwords(str_replace("_", " ", $data_surat['jenis_surat'])), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <td>No. Surat Pengajuan</td>
                    <td><?php echo htmlspecialchars($data_surat['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <td>Tanggal Pengajuan</td>
                    <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($data_surat['tanggal_pengajuan'])), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <td>Diajukan Oleh</td>
                    <td><?php echo htmlspecialchars($data_surat['nama_pengaju'], ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                
                <?php if ($data_surat['jenis_surat'] == 'approval_logum' || $data_surat['jenis_surat'] == 'approval_direktur'): ?>
                <tr>
                    <td>Diapprove Logum Oleh</td>
                    <td><?php echo htmlspecialchars($data_surat['nama_approver_logum'], ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <?php endif; ?>
                
                <?php if ($data_surat['jenis_surat'] == 'approval_direktur'): ?>
                <tr>
                    <td>Diapprove Direktur Oleh</td>
                    <td><?php echo htmlspecialchars($data_surat['nama_approver_direktur'], ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <?php endif; ?>
                
                 <tr>
                    <td>Dokumen Dibuat Pada</td>
                    <td><?php echo htmlspecialchars(date('d-m-Y H:i:s', strtotime($data_surat['waktu_dibuat'])), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            </table>
        
        <?php else: ?>
            <div class="status invalid">
                DOKUMEN TIDAK VALID
            </div>
            <p style="text-align: center;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

    </div>
</body>
</html>