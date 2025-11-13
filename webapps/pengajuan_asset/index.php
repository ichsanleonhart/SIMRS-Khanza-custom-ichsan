<?php
/*
 * ==================================================================
 * INDEX.PHP (REFACTORED - V.15 / Sesi 3)
 * ==================================================================
 * Ini adalah file "View" utama.
 *
 * [UPDATE V.15 - Sesi 3]:
 * - Mengisi logika 'action=edit' untuk Role Pengaju.
 * - Menambahkan tombol 'Edit' di dashboard Pengaju.
 * - Menambahkan JavaScript untuk 'handleFormSubmit' (mencegah double click).
 * - Menambahkan perbaikan bug 'nilai 0' di JavaScript.
 *
 * Dibuat kompatibel dengan PHP 7.3
 */

// 1. INISIALISASI & KEAMANAN
// -----------------------------------------------------------------------------
session_start();

if (!isset($_SESSION['nik_pengajuan_asset'])) {
    header('Location: login.php');
    exit;
}

include 'config_pengajuan_asset.php';
$konektor = bukakoneksi();
if (!$konektor) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$nik_login = $_SESSION['nik_pengajuan_asset'];
$nama_login = $_SESSION['nama_pengajuan_asset'];
$role_login = $_SESSION['role_pengajuan_asset'];
$jbtn_login = $_SESSION['jbtn_pengajuan_asset'];
$dep_login = $_SESSION['departemen_pengajuan_asset'];

if (empty($_SESSION['csrf_token_asset'])) {
    $_SESSION['csrf_token_asset'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_asset'];

foreach ([PATH_FOTO_SURAT, PATH_FOTO_REFERENSI, PATH_FOTO_VALIDASI] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// 2. MENGAMBIL DATA GLOBAL (BRANDING)
// -----------------------------------------------------------------------------
$nama_instansi = "RSK";
$logo_path = "logo.php?v=logo";
$favicon_path = "logo.php?v=favicon";

try {
    $setting_sql = "SELECT setting.nama_instansi FROM setting LIMIT 1";
    $setting_result = mysqli_query($konektor, $setting_sql);
    if ($setting_row = mysqli_fetch_assoc($setting_result)) {
        $nama_instansi = $setting_row['nama_instansi'];
    }
} catch (Exception $e) { /* Biarkan default */ }

// 3. LOGIKA INTI (DI-INCLUDE DARI FILE LAIN)
// -----------------------------------------------------------------------------
$action = isset($_GET['action']) ? $_GET['action'] : 'view';
$error_message = '';
$success_message = '';

include 'function.php';
include 'action.php';
include 'role.php'; 

mysqli_close($konektor); 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Aset - <?php echo htmlspecialchars($nama_instansi, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" href="<?php echo $favicon_path; ?>" type="image/png">
    
    <link rel="stylesheet" href="style.css?v=V.13">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css"/>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    
    <style>
        .fancybox__button--flip, .fancybox__button--rotate { display: block; }
        .remove-item-btn {
            background-color: #dc3545; color: white; border: none;
            padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;
        }
        .remove-item-btn:hover { background-color: #c82333; }
        #add-item-btn { margin-top: 10px; font-weight: bold; }
        
        .approval-table input[type="radio"] { margin-right: 5px; }
        .approval-table label { margin-right: 15px; font-weight: normal; }
        .approval-table .radio-group { display: flex; align-items: center; flex-wrap: wrap; }
        .approval-table input[type="text"], .approval-table input[type="number"] { width: 95%; padding: 5px; box-sizing: border-box; }
        .approval-table th:first-child, .approval-table td:first-child { text-align: center; }
        
        .status-ditolak { background-color: #f8d7da !important; text-decoration: line-through; }
        .status-disetujui { background-color: #d4edda !important; }
        .status-menunggu { background-color: #fff3cd !important; }
        .status-ditolak-sebagian { background-color: #fff3cd !important; color: #856404; }
        .status-proses-pengadaan, .status-selesai-sebagian { background-color: #d1ecf1 !important; color: #0c5460; }
        .status-selesai-penuh { background-color: #d4edda !important; }

        .col-rp { text-align: right; white-space: nowrap; width: 120px; }
        .col-qty { text-align: center; width: 60px; }
        .col-sisa-rp { background-color: #fff3cd; font-weight: bold; text-align: right; }
        
        .menu-wrapper {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
        }
        @media (min-width: 992px) {
            .menu-wrapper {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        .menu-wrapper-3col {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
        }
        @media (min-width: 992px) {
            .menu-wrapper-3col {
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            }
        }
        
        .modal {
            display: none; position: fixed; z-index: 1050; left: 0; top: 0; 
            width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe; margin: 10% auto; padding: 20px; 
            border: 1px solid #888; width: 80%; max-width: 600px; 
            border-radius: 8px; position: relative;
        }
        .modal-close {
            color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;
        }
        /* Style untuk foto di form edit */
        .current-photo { margin-top: 5px; font-size: 0.9em; }
        .current-photo a { font-weight: bold; }
        .current-photo p { margin: 0; color: #666; }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo">
            <img src="<?php echo $logo_path; ?>" alt="Logo">
            <h1><?php echo htmlspecialchars($nama_instansi, ENT_QUOTES, 'UTF-8'); ?> (Aset)</h1>
        </div>
        <div class="user-info">
            Selamat datang, <strong><?php echo htmlspecialchars($nama_login, ENT_QUOTES, 'UTF-8'); ?></strong>
            (Role: <?php echo htmlspecialchars(ucfirst($role_login), ENT_QUOTES, 'UTF-8'); ?>)
            <?php if ($role_login == 'direktur' || $role_login == 'logum'): ?>
                <a href="laporan.php" style="color: #007bff;">Lihat Laporan</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="content">
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            
            <?php // =================================================================
                  // TAMPILAN UNTUK ROLE: PENGAJU
                  // =================================================================
                  if ($role_login == 'pengaju'): 
            ?>
            
                <?php // Tampilan Form Tambah (action=add)
                if ($action === 'add'): 
                ?>
                    <div class="form-section">
                        <h2>Formulir Pengajuan Aset Baru</h2>
                        <form action="index.php?action=save" method="POST" enctype="multipart/form-data" onsubmit="return handleFormSubmit(this);">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <h3>Data Surat Pengajuan</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>No. Pengajuan</label>
                                    <input type="text" name="no_surat_pengajuan" value="<?php echo htmlspecialchars($data_form_no_surat, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Pengajuan</label>
                                    <input type="date" name="tanggal_pengajuan" value="<?php echo htmlspecialchars($data_form_tanggal, ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Diajukan Oleh</label>
                                    <input type="text" value="<?php echo htmlspecialchars($nama_login . ' (' . $nik_login . ')', ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Departemen/Bidang</label>
                                    <input type="text" value="<?php echo htmlspecialchars($dep_login . ' / ' . $jbtn_login, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="select-pj-logum">Ditujukan ke P.J. Logistik Umum</label>
                                    <select id="select-pj-logum" name="nik_pj" placeholder="-- Pilih Petugas Logistik Umum --" required>
                                        <option value="">-- Pilih Petugas --</option>
                                        <?php foreach ($pegawai_list_logum as $pj): ?>
                                        <option value="<?php echo htmlspecialchars($pj['nik'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($pj['nama'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($pj['nik'], ENT_QUOTES, 'UTF-8'); ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group full-width">
                                    <label>Uraian Latar Belakang</label>
                                    <textarea name="uraian_latar_belakang" required></textarea>
                                </div>
                                <div class="form-group full-width">
                                    <label>Tujuan Pengajuan</label>
                                    <textarea name="tujuan_pengajuan" required></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Target Sasaran</label>
                                    <input type="text" name="target_sasaran" required>
                                </div>
                                <div class="form-group">
                                    <label>Lokasi Pengajuan (Ruang/Unit)</label>
                                    <input type="text" name="lokasi_pengajuan" required>
                                </div>

                                <div class="form-group">
                                    <label>Urgensi</label>
                                    <select name="urgensi">
                                        <option value="Biasa">Biasa</option>
                                        <option value="Cito">Cito</option>
                                        <option value="Emergensi">Emergensi</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Keterangan</label>
                                    <input type="text" name="keterangan" required>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label>Upload Foto Surat Pengajuan (Opsional, PDF/JPG/PNG, Max 5MB)</label>
                                    <input type="file" name="foto_surat_pengajuan">
                                </div>
                            </div>
                            
                            <hr style="margin: 20px 0;">
                            
                            <h3>Detail Item Barang/Aset</h3>
                            <div id="item-table-container" class="table-container">
                                <table id="item-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 30%;">Nama Barang/Aset</th>
                                            <th style="width: 10%;">Jumlah</th>
                                            <th style="width: 15%;">Harga Satuan (Rp)</th>
                                            <th style="width: 30%;">Foto Referensi (Opsional, PDF/JPG/PNG, Max 5MB)</th>
                                            <th style="width: 10%;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="item-list">
                                        <tr>
                                            <td><input type="text" name="nama_barang[]" required></td>
                                            <td><input type="text" name="jumlah[]" class="format-angka" value="1" required></td>
                                            <td><input type="text" name="harga_satuan[]" class="format-rupiah" value="0" required></td>
                                            <td><input type="file" name="foto_referensi[]"></td>
                                            <td>
                                                <button type="button" class="remove-item-btn">Hapus</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" id="add-item-btn" class="btn btn-primary btn-sm">+ Tambah Baris Barang</button>

                            <div class="action-buttons" style="margin-top: 30px;">
                                <button type="submit" class="btn btn-success">Simpan Pengajuan</button>
                                <a href="index.php" class="btn btn-secondary">Kembali ke Daftar</a>
                            </div>
                        </form>
                    </div>
					
					<?php // Tampilan Edit (action=edit)
                elseif ($action === 'edit' && $data_detail_header): 
                ?>
                    <div class="form-section">
                        <h2>Edit Pengajuan Aset (No: <?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>)</h2>
                        <form action="index.php?action=update" method="POST" enctype="multipart/form-data" onsubmit="return handleFormSubmit(this);">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="no_surat_pengajuan" value="<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>">
                            
                            <h3>Data Surat Pengajuan</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>No. Pengajuan</label>
                                    <input type="text" value="<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Pengajuan</label>
                                    <input type="date" name="tanggal_pengajuan" value="<?php echo htmlspecialchars($data_detail_header['tanggal_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Diajukan Oleh</label>
                                    <input type="text" value="<?php echo htmlspecialchars($nama_login . ' (' . $nik_login . ')', ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Departemen/Bidang</label>
                                    <input type="text" value="<?php echo htmlspecialchars($dep_login . ' / ' . $jbtn_login, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="select-pj-logum">Ditujukan ke P.J. Logistik Umum</label>
                                    <select id="select-pj-logum" name="nik_pj" placeholder="-- Pilih Petugas Logistik Umum --" required>
                                        <?php foreach ($pegawai_list_logum as $pj): ?>
                                        <option value="<?php echo htmlspecialchars($pj['nik'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($data_detail_header['nik_pj'] == $pj['nik']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pj['nama'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($pj['nik'], ENT_QUOTES, 'UTF-8'); ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group full-width">
                                    <label>Uraian Latar Belakang</label>
                                    <textarea name="uraian_latar_belakang" required><?php echo htmlspecialchars($data_detail_header['uraian_latar_belakang'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                <div class="form-group full-width">
                                    <label>Tujuan Pengajuan</label>
                                    <textarea name="tujuan_pengajuan" required><?php echo htmlspecialchars($data_detail_header['tujuan_pengajuan'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Target Sasaran</label>
                                    <input type="text" name="target_sasaran" value="<?php echo htmlspecialchars($data_detail_header['target_sasaran'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Lokasi Pengajuan (Ruang/Unit)</label>
                                    <input type="text" name="lokasi_pengajuan" value="<?php echo htmlspecialchars($data_detail_header['lokasi_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Urgensi</label>
                                    <select name="urgensi">
                                        <option value="Biasa" <?php echo ($data_detail_header['urgensi'] == 'Biasa') ? 'selected' : ''; ?>>Biasa</option>
                                        <option value="Cito" <?php echo ($data_detail_header['urgensi'] == 'Cito') ? 'selected' : ''; ?>>Cito</option>
                                        <option value="Emergensi" <?php echo ($data_detail_header['urgensi'] == 'Emergensi') ? 'selected' : ''; ?>>Emergensi</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Keterangan</label>
                                    <input type="text" name="keterangan" value="<?php echo htmlspecialchars($data_detail_header['keterangan'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label>Upload Foto Surat Pengajuan (Opsional, PDF/JPG/PNG, Max 5MB)</label>
                                    <input type="file" name="foto_surat_pengajuan">
                                    <input type="hidden" name="foto_surat_lama" value="<?php echo htmlspecialchars($data_detail_header['foto_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php if (!empty($data_detail_header['foto_surat_pengajuan'])): ?>
                                        <div class="current-photo">
                                            <a data-fancybox="surat-edit" data-src="<?php echo htmlspecialchars($data_detail_header['foto_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" href="javascript:;">Lihat Foto Saat Ini</a>
                                            <p><small>Mengunggah file baru akan menggantikan file ini.</small></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <hr style="margin: 20px 0;">
                            
                            <h3>Detail Item Barang/Aset</h3>
							<p class="alert alert-danger" style="background-color: #fcf8e3; color: #8a6d3b; border-color: #faebcc;">
                                <b>Perhatian:</b> Mode Edit tidak mengizinkan penambahan atau penghapusan baris item.
                            </p>
                            <div id="item-table-container" class="table-container">
                                <table id="item-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 30%;">Nama Barang/Aset</th>
                                            <th style="width: 10%;">Jumlah</th>
                                            <th style="width: 15%;">Harga Satuan (Rp)</th>
                                            <th style="width: 30%;">Foto Referensi (Opsional)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="item-list">
                                        <?php foreach ($data_detail_items as $item): ?>
                                        <tr>
                                            <input type="hidden" name="no_urut[]" value="<?php echo htmlspecialchars($item['no_urut'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="foto_referensi_lama[]" value="<?php echo htmlspecialchars($item['foto_referensi'], ENT_QUOTES, 'UTF-8'); ?>">
                                            
                                            <td><input type="text" name="nama_barang[]" value="<?php echo htmlspecialchars($item['nama_barang'], ENT_QUOTES, 'UTF-8'); ?>" required></td>
                                            <td><input type="text" name="jumlah[]" class="format-angka" value="<?php echo number_format($item['jumlah_diminta'], 0, ',', '.'); ?>" required></td>
                                            <td><input type="text" name="harga_satuan[]" class="format-rupiah" value="<?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?>" required></td>
                                            <td>
                                                <input type="file" name="foto_referensi_baru[]">
                                                <?php if (!empty($item['foto_referensi'])): ?>
                                                    <div class="current-photo">
                                                        <a data-fancybox="ref-edit" data-src="<?php echo htmlspecialchars($item['foto_referensi'], ENT_QUOTES, 'UTF-8'); ?>" href="javascript:;">Lihat Foto Lama</a>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="action-buttons" style="margin-top: 30px;">
                                <button type="submit" class="btn btn-success">Update Pengajuan</button>
                                <a href="index.php" class="btn btn-secondary">Kembali ke Daftar</a>
                            </div>
                        </form>
                    </div>

                <?php elseif ($action === 'detail_lengkap' && $data_detail_header): ?>
                    <div class="form-section">
                        <h2>Detail Pengajuan Aset (Kronologis)</h2>
                        <a href="index.php?action=view" class="btn btn-secondary" style="margin-bottom: 15px;">&larr; Kembali ke Daftar</a>
                        
                        <div class="action-buttons">
                            <?php if ($data_detail_header['status_approval_logum'] != 'Menunggu'): ?>
                                <a href="print.php?tipe=approval_logum&id=<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-primary btn-sm">Cetak Bukti Appr. Logum</a>
                            <?php endif; ?>
                            <?php if ($data_detail_header['status_approval_direktur'] != 'Menunggu'): ?>
                                <a href="print.php?tipe=approval_direktur&id=<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-success btn-sm">Cetak Surat Pengadaan (Appr. Direktur)</a>
                            <?php endif; ?>
                            <?php if ($data_detail_header['status_pengadaan'] == 'Selesai Sebagian' || $data_detail_header['status_pengadaan'] == 'Selesai Penuh'): ?>
                                <a href="print.php?tipe=validasi_barang&id=<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-warning btn-sm">Cetak Bukti Barang Datang</a>
                            <?php endif; ?>
                        </div>
                        
                        <h3>Data Surat Pengajuan</h3>
                        <div class="form-grid">
                             <div class="form-group">
                                <label>No. Pengajuan</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Pengajuan</label>
                                <input type="text" value="<?php echo htmlspecialchars(date('d-m-Y', strtotime($data_detail_header['tanggal_pengajuan'])), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Diajukan Oleh</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['nama_pengaju'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Ditujukan ke PJ Logum</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['nama_pj_logum'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            
                            <div class="form-group full-width">
                                <label>Uraian Latar Belakang</label>
                                <textarea readonly><?php echo htmlspecialchars($data_detail_header['uraian_latar_belakang'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            <div class="form-group full-width">
                                <label>Tujuan Pengajuan</label>
                                <textarea readonly><?php echo htmlspecialchars($data_detail_header['tujuan_pengajuan'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Target Sasaran</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['target_sasaran'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Lokasi Pengajuan (Ruang/Unit)</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['lokasi_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Urgensi</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['urgensi'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Keterangan</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['keterangan'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label>Status Approval Logum</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['status_approval_logum'], ENT_QUOTES, 'UTF-8'); ?> (Oleh: <?php echo htmlspecialchars($data_detail_header['nama_approver_logum'] ? $data_detail_header['nama_approver_logum'] : '-', ENT_QUOTES, 'UTF-8'); ?>)" readonly 
                                       class="status-<?php echo strtolower(str_replace(' ', '-', $data_detail_header['status_approval_logum'])); ?>">
                            </div>
                             <div class="form-group">
                                <label>Status Approval Direktur</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['status_approval_direktur'], ENT_QUOTES, 'UTF-8'); ?> (Oleh: <?php echo htmlspecialchars($data_detail_header['nama_approver_direktur'] ? $data_detail_header['nama_approver_direktur'] : '-', ENT_QUOTES, 'UTF-8'); ?>)" readonly
                                       class="status-<?php echo strtolower(str_replace(' ', '-', $data_detail_header['status_approval_direktur'])); ?>">
                            </div>
                            <div class="form-group full-width">
                                <label>Status Pengadaan Saat Ini</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['status_pengadaan'], ENT_QUOTES, 'UTF-8'); ?>" readonly 
                                       class="status-<?php echo strtolower(str_replace(' ', '-', $data_detail_header['status_pengadaan'])); ?>">
                            </div>
                             <?php if (!empty($data_detail_header['foto_surat_pengajuan'])): ?>
                                <div class="form-group">
                                    <label>Foto Surat</label><br>
                                    <a data-fancybox="surat" data-src="<?php echo htmlspecialchars($data_detail_header['foto_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" href="javascript:;" class="btn btn-secondary btn-sm">Lihat Foto Surat</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <hr style="margin: 20px 0;">
                        
                        <h3>Tabel 1: Detail Status Approval Item</h3>
                        <div class="table-container">
                             <table class="approval-table">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Nama Barang</th>
                                        <th>Ref.</th>
                                        <th class="col-qty">Jml Minta</th>
                                        <th class="col-rp">Total Minta (Rp)</th>
                                        <th>Status Logum</th>
                                        <th>Jml Appr. Logum</th>
                                        <th class="col-rp">Total Appr. (Rp)</th>
                                        <th>Status Direktur</th>
                                        <th>Jml Appr. Direktur</th>
                                        <th class="col-rp">Total Appr. (Rp)</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data_detail_items as $item): 
                                        $harga_satuan = (double)$item['harga_satuan'];
                                    ?>
                                    <tr class="status-<?php echo strtolower($item['status_approval_direktur']); ?>">
                                        <td><?php echo htmlspecialchars($item['no_urut'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($item['nama_barang'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <?php if (!empty($item['foto_referensi'])): ?>
                                                <a data-fancybox="item-ref" data-src="<?php echo htmlspecialchars($item['foto_referensi'], ENT_QUOTES, 'UTF-8'); ?>" href="javascript:;" class="btn btn-secondary btn-sm">Foto</a>
                                            <?php else: echo "-"; endif; ?>
                                        </td>
                                        <td class="col-qty"><?php echo number_format($item['jumlah_diminta'], 0, ',', '.'); ?></td>
                                        <td class="col-rp"><?php echo number_format($item['jumlah_diminta'] * $harga_satuan, 0, ',', '.'); ?></td>
                                        <td class="status-<?php echo strtolower($item['status_approval_logum']); ?>"><?php echo htmlspecialchars($item['status_approval_logum'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="col-qty"><?php echo number_format($item['jumlah_disetujui_logum'], 0, ',', '.'); ?></td>
                                        <td class="col-rp"><?php echo number_format($item['jumlah_disetujui_logum'] * $harga_satuan, 0, ',', '.'); ?></td>
                                        <td class="status-<?php echo strtolower($item['status_approval_direktur']); ?>"><?php echo htmlspecialchars($item['status_approval_direktur'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="col-qty"><?php echo number_format($item['jumlah_disetujui_direktur'], 0, ',', '.'); ?></td>
                                        <td class="col-rp"><?php echo number_format($item['jumlah_disetujui_direktur'] * $harga_satuan, 0, ',', '.'); ?></td>
                                        <td>
                                            <?php 
                                            echo "<b>Logum:</b> " . htmlspecialchars($item['catatan_logum'], ENT_QUOTES, 'UTF-8') . "<br>";
                                            echo "<b>Direktur:</b> " . htmlspecialchars($item['catatan_direktur'], ENT_QUOTES, 'UTF-8'); 
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr style="margin: 20px 0;">
                        
                        <h3>Tabel 2: Detail Realisasi Barang (Sisa)</h3>
                         <div class="table-container">
                             <table>
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Nama Barang</th>
                                        <th>Jml Disetujui</th>
                                        <th class="col-rp">Total Disetujui (Rp)</th>
                                        <th>Jml Sudah Datang</th>
                                        <th class="col-rp">Total Datang (Rp)</th>
                                        <th>Sisa</th>
                                        <th class="col-sisa-rp">Nilai Sisa (Rp)</th>
                                        <th>Detail Kedatangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data_detail_items as $item): 
                                        if ($item['status_approval_direktur'] != 'Disetujui') continue;
                                        $harga_satuan = (double)$item['harga_satuan'];
                                        $sisa = $item['jumlah_disetujui_direktur'] - $item['jumlah_sudah_divalidasi'];
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['no_urut'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($item['nama_barang'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="col-qty"><?php echo number_format($item['jumlah_disetujui_direktur'], 0, ',', '.'); ?></td>
                                        <td class="col-rp"><?php echo number_format($item['jumlah_disetujui_direktur'] * $harga_satuan, 0, ',', '.'); ?></td>
                                        <td class="col-qty"><?php echo number_format($item['jumlah_sudah_divalidasi'], 0, ',', '.'); ?></td>
                                        <td class="col-rp"><?php echo number_format($item['jumlah_sudah_divalidasi'] * $harga_satuan, 0, ',', '.'); ?></td>
                                        <td class="col-qty" style="background-color: #fff3cd; font-weight: bold;"><?php echo number_format($sisa, 0, ',', '.'); ?></td>
                                        <td class="col-sisa-rp"><?php echo number_format($sisa * $harga_satuan, 0, ',', '.'); ?></td>
                                        <td>
                                            <?php if (isset($data_detail_validasi[$item['no_urut']])): ?>
                                                <ul style="margin: 0; padding-left: 20px;">
                                                <?php foreach ($data_detail_validasi[$item['no_urut']] as $log): ?>
                                                    <li>
                                                        <?php echo htmlspecialchars(date('d-m-Y', strtotime($log['tanggal_validasi'])), ENT_QUOTES, 'UTF-8'); ?>: 
                                                        <b><?php echo number_format($log['jumlah_datang'], 0, ',', '.'); ?> pcs</b>
                                                        (<?php echo htmlspecialchars($log['catatan_validasi'], ENT_QUOTES, 'UTF-8'); ?>)
                                                        <?php if (!empty($log['foto_bukti_datang'])): ?>
                                                            <a data-fancybox="validasi-<?php echo $item['no_urut']; ?>" data-src="<?php echo htmlspecialchars($log['foto_bukti_datang'], ENT_QUOTES, 'UTF-8'); ?>" data-caption="<?php echo htmlspecialchars($log['catatan_validasi'], ENT_QUOTES, 'UTF-8'); ?>" href="javascript:;">[Foto]</a>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <i>(Belum ada)</i>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                
                <?php // Tampilan Daftar (action=view)
                else: 
                ?>
                    <div class="action-buttons">
                        <a href="index.php?action=add" class="btn btn-primary">Tambah Pengajuan Aset Baru</a>
                    </div>
                    
                    <h3>Daftar Pengajuan Saya</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Aksi</th>
                                    <th>No. Surat</th>
                                    <th>Tanggal</th>
                                    <th>Status Pengadaan</th>
                                    <th>Status Logum</th>
                                    <th>Status Direktur</th>
                                    <th class="col-rp">Total Disetujui (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($list_pengajuan_pengaju)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center;">Anda belum pernah membuat pengajuan.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($list_pengajuan_pengaju as $row): ?>
                                    <tr>
                                        <td class="actions">
                                            <a href="index.php?action=detail_lengkap&id=<?php echo htmlspecialchars($row['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-sm">Lihat Detail</a>
                                            <?php 
                                            if ($row['status_approval_logum'] == 'Menunggu'): 
                                            ?>
                                                <a href="index.php?action=edit&id=<?php echo htmlspecialchars($row['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-warning btn-sm">Edit</a>
                                                <a href="javascript:void(0);" onclick="konfirmasiHapus('<?php echo htmlspecialchars($row['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo $csrf_token; ?>')" class="btn btn-danger btn-sm">Hapus</a>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($row['tanggal_pengajuan'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="status-<?php echo strtolower(str_replace(' ', '-', $row['status_pengadaan'])); ?>"><?php echo htmlspecialchars($row['status_pengadaan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="status-<?php echo strtolower(str_replace(' ', '-', $row['status_approval_logum'])); ?>"><?php echo htmlspecialchars($row['status_approval_logum'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="status-<?php echo strtolower(str_replace(' ', '-', $row['status_approval_direktur'])); ?>"><?php echo htmlspecialchars($row['status_approval_direktur'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="col-rp"><?php echo number_format($row['total_disetujui'], 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <?php // =================================================================
                  // TAMPILAN UNTUK ROLE: LOGISTIK UMUM
                  // =================================================================
                  elseif ($role_login == 'logum'): 
            ?>

                <?php // Tampilan Detail Approval Logum (action=detail_logum)
                if ($action === 'detail_logum' && $data_detail_header): 
                ?>
                    <div class="form-section">
                        <h2><?php echo ($data_detail_header['status_approval_logum'] == 'Menunggu') ? 'Proses Approval Logistik Umum' : 'Edit Approval Logistik Umum'; ?></h2>
                        
                        <form action="index.php?action=save_logum" method="POST" onsubmit="return handleFormSubmit(this);">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="no_surat_pengajuan" value="<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>">
                            
                            <h3>Data Surat Pengajuan</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>No. Pengajuan</label>
                                    <input type="text" value="<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Diajukan Oleh</label>
                                    <input type="text" value="<?php echo htmlspecialchars($data_detail_header['nama_pengaju'] . ' (' . $data_detail_header['nik'] . ')', ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Total Pengajuan</label>
                                    <input type="text" class="total-display" value="Rp <?php echo number_format($data_detail_header['total_pengajuan'], 0, ',', '.'); ?>" readonly>
                                </div>
                                <?php if (!empty($data_detail_header['foto_surat_pengajuan'])): ?>
                                <div class="form-group">
                                    <label>Foto Surat</label><br>
                                    <a data-fancybox="surat" data-src="<?php echo htmlspecialchars($data_detail_header['foto_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" href="javascript:;" class="btn btn-secondary btn-sm">Lihat Foto Surat</a>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <hr style="margin: 20px 0;">
                            
                            <h3>Detail Item Barang (Untuk Diapprove)</h3>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-success btn-sm" id="setujui-semua">Setujui Semua</button>
                                <button type="button" class="btn btn-danger btn-sm" id="tolak-semua">Tolak Semua</button>
                            </div>
                            <div class="table-container">
                                <table class="approval-table">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Nama Barang</th>
                                            <th>Jml Minta</th>
                                            <th class="col-rp">Harga (Rp)</th>
                                            <th class="col-rp">Total Minta (Rp)</th>
                                            <th>Ref.</th>
                                            <th style="width: 15%;">Jml Disetujui</th>
                                            <th style="width: 18%;">Approval Logum</th>
                                            <th style="width: 20%;">Catatan Logum</th>
                                        </tr>
                                    </thead>
                                    <tbody id="approval-list-logum">
                                        <?php foreach ($data_detail_items as $i => $item): 
                                            $harga_satuan = (double)$item['harga_satuan'];
                                        ?>
                                        <tr class="status-<?php echo strtolower($item['status_approval_logum']); ?>">
                                            <td class="col-qty"><?php echo htmlspecialchars($item['no_urut'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($item['nama_barang'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="col-qty"><?php echo number_format($item['jumlah_diminta'], 0, ',', '.'); ?></td>
                                            <td class="col-rp"><?php echo number_format($harga_satuan, 0, ',', '.'); ?></td>
                                            <td class="col-rp"><?php echo number_format($item['jumlah_diminta'] * $harga_satuan, 0, ',', '.'); ?></td>
                                            <td>
                                                <?php if (!empty($item['foto_referensi'])): ?>
                                                    <a data-fancybox="item-ref" data-src="<?php echo htmlspecialchars($item['foto_referensi'], ENT_QUOTES, 'UTF-8'); ?>" href="javascript:;" class="btn btn-secondary btn-sm">Foto</a>
                                                <?php else: echo "-"; endif; ?>
                                            </td>
                                            <td>
                                                <input type="hidden" name="no_urut[]" value="<?php echo htmlspecialchars($item['no_urut'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="text" name="jumlah_disetujui_logum[]" class="format-angka input-jumlah" 
                                                       value="<?php echo number_format($item['status_approval_logum'] == 'Ditolak' ? 0 : ($item['status_approval_logum'] == 'Menunggu' ? $item['jumlah_diminta'] : $item['jumlah_disetujui_logum']), 0, ',', '.'); ?>" 
                                                       style="width: 60px; text-align: right;">
                                            </td>
                                            <td>
                                                <div class="radio-group">
                                                    <input type="radio" id="setuju_<?php echo $item['no_urut']; ?>" name="status_item[<?php echo $i; ?>]" value="Disetujui" <?php echo ($item['status_approval_logum'] == 'Ditolak') ? '' : 'checked'; ?>> 
                                                    <label for="setuju_<?php echo $item['no_urut']; ?>">Setuju</label>
                                                    <br>
                                                    <input type="radio" id="tolak_<?php echo $item['no_urut']; ?>" name="status_item[<?php echo $i; ?>]" value="Ditolak" <?php echo ($item['status_approval_logum'] == 'Ditolak') ? 'checked' : ''; ?>>
                                                    <label for="tolak_<?php echo $item['no_urut']; ?>">Tolak</label>
                                                </div>
                                            </td>
                                            <td><input type="text" name="catatan_item[]" placeholder="Catatan (opsional)..." value="<?php echo htmlspecialchars($item['catatan_logum'], ENT_QUOTES, 'UTF-8'); ?>"></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="action-buttons" style="margin-top: 30px;">
                                <button type="submit" class="btn btn-success">Simpan Approval</button>
                                <a href="index.php" class="btn btn-secondary">Batal</a>
                            </div>
                        </form>
                    </div>

                <?php elseif ($action === 'detail_lengkap' && $data_detail_header): ?>
                    <div class="form-section">
                        <h2>Detail Pengajuan Aset (Kronologis)</h2>
                        <a href="index.php?action=view" class="btn btn-secondary" style="margin-bottom: 15px;">&larr; Kembali ke Daftar</a>
                        
                        <div class="action-buttons">
                            <?php if ($data_detail_header['status_approval_logum'] != 'Menunggu'): ?>
                                <a href="print.php?tipe=approval_logum&id=<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-primary btn-sm">Cetak Bukti Appr. Logum</a>
                            <?php endif; ?>
                            <?php if ($data_detail_header['status_approval_direktur'] != 'Menunggu'): ?>
                                <a href="print.php?tipe=approval_direktur&id=<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-success btn-sm">Cetak Surat Pengadaan (Appr. Direktur)</a>
                            <?php endif; ?>
                            <?php if ($data_detail_header['status_pengadaan'] == 'Selesai Sebagian' || $data_detail_header['status_pengadaan'] == 'Selesai Penuh'): ?>
                                <a href="print.php?tipe=validasi_barang&id=<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-warning btn-sm">Cetak Bukti Barang Datang</a>
                            <?php endif; ?>
                        </div>
                        
                        <h3>Data Surat Pengajuan</h3>
                        <div class="form-grid">
                             <div class="form-group">
                                <label>No. Pengajuan</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Pengajuan</label>
                                <input type="text" value="<?php echo htmlspecialchars(date('d-m-Y', strtotime($data_detail_header['tanggal_pengajuan'])), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Diajukan Oleh</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['nama_pengaju'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Ditujukan ke PJ Logum</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['nama_pj_logum'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            
                            <div class="form-group full-width">
                                <label>Uraian Latar Belakang</label>
                                <textarea readonly><?php echo htmlspecialchars($data_detail_header['uraian_latar_belakang'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            <div class="form-group full-width">
                                <label>Tujuan Pengajuan</label>
                                <textarea readonly><?php echo htmlspecialchars($data_detail_header['tujuan_pengajuan'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Target Sasaran</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['target_sasaran'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Lokasi Pengajuan (Ruang/Unit)</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['lokasi_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Urgensi</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['urgensi'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Keterangan</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['keterangan'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label>Status Approval Logum</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['status_approval_logum'], ENT_QUOTES, 'UTF-8'); ?> (Oleh: <?php echo htmlspecialchars($data_detail_header['nama_approver_logum'] ? $data_detail_header['nama_approver_logum'] : '-', ENT_QUOTES, 'UTF-8'); ?>)" readonly 
                                       class="status-<?php echo strtolower(str_replace(' ', '-', $data_detail_header['status_approval_logum'])); ?>">
                            </div>
                             <div class="form-group">
                                <label>Status Approval Direktur</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['status_approval_direktur'], ENT_QUOTES, 'UTF-8'); ?> (Oleh: <?php echo htmlspecialchars($data_detail_header['nama_approver_direktur'] ? $data_detail_header['nama_approver_direktur'] : '-', ENT_QUOTES, 'UTF-8'); ?>)" readonly
                                       class="status-<?php echo strtolower(str_replace(' ', '-', $data_detail_header['status_approval_direktur'])); ?>">
                            </div>
                            <div class="form-group full-width">
                                <label>Status Pengadaan Saat Ini</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['status_pengadaan'], ENT_QUOTES, 'UTF-8'); ?>" readonly 
                                       class="status-<?php echo strtolower(str_replace(' ', '-', $data_detail_header['status_pengadaan'])); ?>">
                            </div>
                             <?php if (!empty($data_detail_header['foto_surat_pengajuan'])): ?>
                                <div class="form-group">
                                    <label>Foto Surat</label><br>
                                    <a data-fancybox="surat" data-src="<?php echo htmlspecialchars($data_detail_header['foto_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" href="javascript:;" class="btn btn-secondary btn-sm">Lihat Foto Surat</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <hr style="margin: 20px 0;">
                        
                        <h3>Tabel 1: Detail Status Approval Item</h3>
                        <div class="table-container">
                             <table class="approval-table">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Nama Barang</th>
                                        <th>Ref.</th>
                                        <th class="col-qty">Jml Minta</th>
                                        <th class="col-rp">Total Minta (Rp)</th>
                                        <th>Status Logum</th>
                                        <th>Jml Appr. Logum</th>
                                        <th class="col-rp">Total Appr. (Rp)</th>
                                        <th>Status Direktur</th>
                                        <th>Jml Appr. Direktur</th>
                                        <th class="col-rp">Total Appr. (Rp)</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data_detail_items as $item): 
                                        $harga_satuan = (double)$item['harga_satuan'];
                                    ?>
                                    <tr class="status-<?php echo strtolower($item['status_approval_direktur']); ?>">
                                        <td><?php echo htmlspecialchars($item['no_urut'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($item['nama_barang'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <?php if (!empty($item['foto_referensi'])): ?>
                                                <a data-fancybox="item-ref" data-src="<?php echo htmlspecialchars($item['foto_referensi'], ENT_QUOTES, 'UTF-8'); ?>" href="javascript:;" class="btn btn-secondary btn-sm">Foto</a>
                                            <?php else: echo "-"; endif; ?>
                                        </td>
                                        <td class="col-qty"><?php echo number_format($item['jumlah_diminta'], 0, ',', '.'); ?></td>
                                        <td class="col-rp"><?php echo number_format($item['jumlah_diminta'] * $harga_satuan, 0, ',', '.'); ?></td>
                                        <td class="status-<?php echo strtolower($item['status_approval_logum']); ?>"><?php echo htmlspecialchars($item['status_approval_logum'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="col-qty"><?php echo number_format($item['jumlah_disetujui_logum'], 0, ',', '.'); ?></td>
                                        <td class="col-rp"><?php echo number_format($item['jumlah_disetujui_logum'] * $harga_satuan, 0, ',', '.'); ?></td>
                                        <td class="status-<?php echo strtolower($item['status_approval_direktur']); ?>"><?php echo htmlspecialchars($item['status_approval_direktur'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="col-qty"><?php echo number_format($item['jumlah_disetujui_direktur'], 0, ',', '.'); ?></td>
                                        <td class="col-rp"><?php echo number_format($item['jumlah_disetujui_direktur'] * $harga_satuan, 0, ',', '.'); ?></td>
                                        <td>
                                            <?php 
                                            echo "<b>Logum:</b> " . htmlspecialchars($item['catatan_logum'], ENT_QUOTES, 'UTF-8') . "<br>";
                                            echo "<b>Direktur:</b> " . htmlspecialchars($item['catatan_direktur'], ENT_QUOTES, 'UTF-8'); 
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr style="margin: 20px 0;">
                        
                        <h3>Tabel 2: Detail Realisasi Barang (Sisa)</h3>
                         <div class="table-container">
                             <table>
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Nama Barang</th>
                                        <th>Jml Disetujui</th>
                                        <th class="col-rp">Total Disetujui (Rp)</th>
                                        <th>Jml Sudah Datang</th>
                                        <th class="col-rp">Total Datang (Rp)</th>
                                        <th>Sisa</th>
                                        <th class="col-sisa-rp">Nilai Sisa (Rp)</th>
                                        <th>Detail Kedatangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data_detail_items as $item): 
                                        if ($item['status_approval_direktur'] != 'Disetujui') continue;
                                        $harga_satuan = (double)$item['harga_satuan'];
                                        $sisa = $item['jumlah_disetujui_direktur'] - $item['jumlah_sudah_divalidasi'];
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['no_urut'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($item['nama_barang'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="col-qty"><?php echo number_format($item['jumlah_disetujui_direktur'], 0, ',', '.'); ?></td>
                                        <td class="col-rp"><?php echo number_format($item['jumlah_disetujui_direktur'] * $harga_satuan, 0, ',', '.'); ?></td>
                                        <td class="col-qty"><?php echo number_format($item['jumlah_sudah_divalidasi'], 0, ',', '.'); ?></td>
                                        <td class="col-rp"><?php echo number_format($item['jumlah_sudah_divalidasi'] * $harga_satuan, 0, ',', '.'); ?></td>
                                        <td class="col-qty" style="background-color: #fff3cd; font-weight: bold;"><?php echo number_format($sisa, 0, ',', '.'); ?></td>
                                        <td class="col-sisa-rp"><?php echo number_format($sisa * $harga_satuan, 0, ',', '.'); ?></td>
                                        <td>
                                            <?php if (isset($data_detail_validasi[$item['no_urut']])): ?>
                                                <ul style="margin: 0; padding-left: 20px;">
                                                <?php foreach ($data_detail_validasi[$item['no_urut']] as $log): ?>
                                                    <li>
                                                        <?php echo htmlspecialchars(date('d-m-Y', strtotime($log['tanggal_validasi'])), ENT_QUOTES, 'UTF-8'); ?>: 
                                                        <b><?php echo number_format($log['jumlah_datang'], 0, ',', '.'); ?> pcs</b>
                                                        (<?php echo htmlspecialchars($log['catatan_validasi'], ENT_QUOTES, 'UTF-8'); ?>)
                                                        <?php if (!empty($log['foto_bukti_datang'])): ?>
                                                            <a data-fancybox="validasi-<?php echo $item['no_urut']; ?>" data-src="<?php echo htmlspecialchars($log['foto_bukti_datang'], ENT_QUOTES, 'UTF-8'); ?>" data-caption="<?php echo htmlspecialchars($log['catatan_validasi'], ENT_QUOTES, 'UTF-8'); ?>" href="javascript:;">[Foto]</a>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <i>(Belum ada)</i>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php // Tampilan Dashboard Logum (action=view)
                else: 
                ?>
                    <div class="menu-wrapper-3col">
                        <div class="form-section">
                            <h3>Menu 1: Approval (Tugas)</h3>
                            <p>Daftar pengajuan baru yang menunggu approval Anda.</p>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Aksi</th>
                                            <th>No. Surat</th>
                                            <th>Pengaju</th>
                                            <th class="col-rp">Total (Rp)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($list_approval_logum)): ?>
                                            <tr><td colspan="4" style="text-align: center;">Tidak ada tugas approval.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($list_approval_logum as $row): ?>
                                            <tr>
                                                <td class="actions">
                                                    <a href="index.php?action=detail_logum&id=<?php echo htmlspecialchars($row['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-sm">Proses</a>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($row['nama_pengaju'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="col-rp"><?php echo number_format($row['total_pengajuan'], 0, ',', '.'); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>Menu 2: Validasi (Tugas)</h3>
                            <p>Daftar item yang sudah disetujui Direktur dan menunggu divalidasi.</p>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Aksi</th>
                                            <th>No. Surat</th>
                                            <th>Nama Barang</th>
                                            <th>Sisa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                         <?php if (empty($list_validasi_logum)): ?>
                                            <tr><td colspan="4" style="text-align: center;">Tidak ada item yang menunggu validasi.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($list_validasi_logum as $item): ?>
                                            <tr>
                                                <td class="actions">
                                                    <button type="button" class="btn btn-success btn-sm btn-validasi" 
                                                            data-surat="<?php echo htmlspecialchars($item['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-urut="<?php echo htmlspecialchars($item['no_urut'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-nama="<?php echo htmlspecialchars($item['nama_barang'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-sisa="<?php echo htmlspecialchars($item['sisa'], ENT_QUOTES, 'UTF-8'); ?>">
                                                        Validasi
                                                    </button>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($item['nama_barang'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="col-qty" style="background-color: #fff3cd; font-weight: bold;"><?php echo number_format($item['sisa'], 0, ',', '.'); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>Menu 3: Riwayat (Bisa Edit)</h3>
                            <p>Daftar pengajuan yang sudah Anda proses tapi belum dikunci oleh Direktur.</p>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Aksi</th>
                                            <th>No. Surat</th>
                                            <th>Pengaju</th>
                                            <th>Status Anda</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                         <?php if (empty($list_riwayat_logum)): ?>
                                            <tr><td colspan="4" style="text-align: center;">Tidak ada riwayat yang bisa diedit.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($list_riwayat_logum as $row): ?>
                                            <tr>
                                                <td class="actions">
                                                    <a href="index.php?action=detail_logum&id=<?php echo htmlspecialchars($row['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-warning btn-sm">Edit Approval</a>
                                                    <a href="index.php?action=detail_lengkap&id=<?php echo htmlspecialchars($row['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary btn-sm">Detail</a>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($row['nama_pengaju'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="status-<?php echo strtolower(str_replace(' ', '-', $row['status_approval_logum'])); ?>">
                                                    <?php echo htmlspecialchars($row['status_approval_logum'], ENT_QUOTES, 'UTF-8'); ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
				<?php // =================================================================
                  // TAMPILAN UNTUK ROLE: DIREKTUR
                  // =================================================================
                  elseif ($role_login == 'direktur'): 
            ?>

                <?php // Tampilan Detail Approval Direktur (action=detail_direktur)
                if ($action === 'detail_direktur' && $data_detail_header): 
                ?>
                    <div class="form-section">
                        <h2>Proses Approval Direktur</h2>
                        
                        <?php if ($is_locked_by_validation): ?>
                            <div class="alert alert-danger">
                                <b>APPROVAL DIKUNCI.</b><br>
                                Anda tidak dapat lagi mengubah approval ini karena Logistik Umum sudah mulai memvalidasi kedatangan barang.
                            </div>
                        <?php endif; ?>
                        
                        <form action="index.php?action=save_direktur" method="POST" onsubmit="return handleFormSubmit(this);">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="no_surat_pengajuan" value="<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>">
                            
                            <h3>Data Surat Pengajuan</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>No. Pengajuan</label>
                                    <input type="text" value="<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Diajukan Oleh</label>
                                    <input type="text" value="<?php echo htmlspecialchars($data_detail_header['nama_pengaju'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Di-approve Logum Oleh</label>
                                    <input type="text" value="<?php echo htmlspecialchars($data_detail_header['nama_approver_logum'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                </div>
                                <?php if (!empty($data_detail_header['foto_surat_pengajuan'])): ?>
                                <div class="form-group">
                                    <label>Foto Surat</label><br>
                                    <a data-fancybox="surat" data-src="<?php echo htmlspecialchars($data_detail_header['foto_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" href="javascript:;" class="btn btn-secondary btn-sm">Lihat Foto Surat</a>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <hr style="margin: 20px 0;">
                            
                            <h3>Detail Item Barang (Approval Final Direktur)</h3>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-success btn-sm" id="setujui-semua" <?php echo $is_locked_by_validation ? 'disabled' : ''; ?>>Setujui Semua</button>
                                <button type="button" class="btn btn-danger btn-sm" id="tolak-semua" <?php echo $is_locked_by_validation ? 'disabled' : ''; ?>>Tolak Semua</button>
                                <a href="print.php?tipe=approval_logum&id=<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-secondary btn-sm">Cetak Appr. Logum</a>
                            </div>
                            <div class="table-container">
                                <table class="approval-table">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Nama Barang</th>
                                            <th>Jml Appr. Logum</th>
                                            <th class="col-rp">Harga (Rp)</th>
                                            <th class="col-rp">Total Appr. Logum (Rp)</th>
                                            <th>Ref.</th>
                                            <th>Catatan Logum</th>
                                            <th style="width: 15%;">Jml Disetujui Direktur</th>
                                            <th style="width: 18%;">Approval Direktur</th>
                                            <th style="width: 20%;">Catatan Direktur</th>
                                        </tr>
                                    </thead>
                                    <tbody id="approval-list-direktur">
                                        <?php foreach ($data_detail_items as $i => $item): 
                                            if ($item['status_approval_logum'] == 'Ditolak') {
                                                continue;
                                            }
                                            $harga_satuan = (double)$item['harga_satuan'];
                                        ?>
                                        <tr class="status-<?php echo strtolower($item['status_approval_direktur']); ?>">
                                            <td class="col-qty"><?php echo htmlspecialchars($item['no_urut'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($item['nama_barang'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="col-qty"><?php echo number_format($item['jumlah_disetujui_logum'], 0, ',', '.'); ?></td>
                                            <td class="col-rp"><?php echo number_format($harga_satuan, 0, ',', '.'); ?></td>
                                            <td class="col-rp"><?php echo number_format($item['jumlah_disetujui_logum'] * $harga_satuan, 0, ',', '.'); ?></td>
                                            <td>
                                                <?php if (!empty($item['foto_referensi'])): ?>
                                                    <a data-fancybox="item-ref" data-src="<?php echo htmlspecialchars($item['foto_referensi'], ENT_QUOTES, 'UTF-8'); ?>" href="javascript:;" class="btn btn-secondary btn-sm">Foto</a>
                                                <?php else: echo "-"; endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['catatan_logum'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <input type="hidden" name="no_urut[]" value="<?php echo htmlspecialchars($item['no_urut'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="text" name="jumlah_disetujui[]" class="format-angka input-jumlah" 
                                                       value="<?php echo number_format($item['status_approval_direktur'] == 'Ditolak' ? 0 : ($item['status_approval_direktur'] == 'Menunggu' ? $item['jumlah_disetujui_logum'] : $item['jumlah_disetujui_direktur']), 0, ',', '.'); ?>" 
                                                       style="width: 60px; text-align: right;" required <?php echo $is_locked_by_validation ? 'disabled' : ''; ?>>
                                            </td>
                                            <td>
                                                <div class="radio-group">
                                                    <input type="radio" id="setuju_dir_<?php echo $item['no_urut']; ?>" name="status_item[<?php echo $i; ?>]" value="Disetujui" <?php echo ($item['status_approval_direktur'] == 'Ditolak') ? '' : 'checked'; ?> <?php echo $is_locked_by_validation ? 'disabled' : ''; ?>> 
                                                    <label for="setuju_dir_<?php echo $item['no_urut']; ?>">Setuju</label>
                                                    <br>
                                                    <input type="radio" id="tolak_dir_<?php echo $item['no_urut']; ?>" name="status_item[<?php echo $i; ?>]" value="Ditolak" <?php echo ($item['status_approval_direktur'] == 'Ditolak') ? 'checked' : ''; ?> <?php echo $is_locked_by_validation ? 'disabled' : ''; ?>>
                                                    <label for="tolak_dir_<?php echo $item['no_urut']; ?>">Tolak</label>
                                                </div>
                                            </td>
                                            <td><input type="text" name="catatan_item[]" placeholder="Catatan (opsional)..." <?php echo $is_locked_by_validation ? 'disabled' : ''; ?>></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="action-buttons" style="margin-top: 30px;">
                                <button type="submit" class="btn btn-success" <?php echo $is_locked_by_validation ? 'disabled' : ''; ?> onsubmit="return handleFormSubmit(this);">
                                    <?php echo $is_locked_by_validation ? 'Terkunci (Sudah Divalidasi)' : 'Simpan Approval Final'; ?>
                                </button>
                                <a href="index.php" class="btn btn-secondary">Batal</a>
                            </div>
                        </form>
                    </div>

                <?php elseif ($action === 'detail_lengkap' && $data_detail_header): ?>
                    <div class="form-section">
                        <h2>Detail Pengajuan Aset (Kronologis)</h2>
                        <a href="index.php?action=view" class="btn btn-secondary" style="margin-bottom: 15px;">&larr; Kembali ke Daftar</a>
                        
                        <div class="action-buttons">
                            <?php if ($data_detail_header['status_approval_logum'] != 'Menunggu'): ?>
                                <a href="print.php?tipe=approval_logum&id=<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-primary btn-sm">Cetak Bukti Appr. Logum</a>
                            <?php endif; ?>
                            <?php if ($data_detail_header['status_approval_direktur'] != 'Menunggu'): ?>
                                <a href="print.php?tipe=approval_direktur&id=<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-success btn-sm">Cetak Surat Pengadaan (Appr. Direktur)</a>
                            <?php endif; ?>
                            <?php if ($data_detail_header['status_pengadaan'] == 'Selesai Sebagian' || $data_detail_header['status_pengadaan'] == 'Selesai Penuh'): ?>
                                <a href="print.php?tipe=validasi_barang&id=<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-warning btn-sm">Cetak Bukti Barang Datang</a>
                            <?php endif; ?>
                        </div>
                        
                        <h3>Data Surat Pengajuan</h3>
                        <div class="form-grid">
                             <div class="form-group">
                                <label>No. Pengajuan</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Pengajuan</label>
                                <input type="text" value="<?php echo htmlspecialchars(date('d-m-Y', strtotime($data_detail_header['tanggal_pengajuan'])), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Diajukan Oleh</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['nama_pengaju'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Ditujukan ke PJ Logum</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['nama_pj_logum'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            
                            <div class="form-group full-width">
                                <label>Uraian Latar Belakang</label>
                                <textarea readonly><?php echo htmlspecialchars($data_detail_header['uraian_latar_belakang'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            <div class="form-group full-width">
                                <label>Tujuan Pengajuan</label>
                                <textarea readonly><?php echo htmlspecialchars($data_detail_header['tujuan_pengajuan'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Target Sasaran</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['target_sasaran'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Lokasi Pengajuan (Ruang/Unit)</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['lokasi_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Urgensi</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['urgensi'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Keterangan</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['keterangan'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label>Status Approval Logum</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['status_approval_logum'], ENT_QUOTES, 'UTF-8'); ?> (Oleh: <?php echo htmlspecialchars($data_detail_header['nama_approver_logum'] ? $data_detail_header['nama_approver_logum'] : '-', ENT_QUOTES, 'UTF-8'); ?>)" readonly 
                                       class="status-<?php echo strtolower(str_replace(' ', '-', $data_detail_header['status_approval_logum'])); ?>">
                            </div>
                             <div class="form-group">
                                <label>Status Approval Direktur</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['status_approval_direktur'], ENT_QUOTES, 'UTF-8'); ?> (Oleh: <?php echo htmlspecialchars($data_detail_header['nama_approver_direktur'] ? $data_detail_header['nama_approver_direktur'] : '-', ENT_QUOTES, 'UTF-8'); ?>)" readonly
                                       class="status-<?php echo strtolower(str_replace(' ', '-', $data_detail_header['status_approval_direktur'])); ?>">
                            </div>
                            <div class="form-group full-width">
                                <label>Status Pengadaan Saat Ini</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_detail_header['status_pengadaan'], ENT_QUOTES, 'UTF-8'); ?>" readonly 
                                       class="status-<?php echo strtolower(str_replace(' ', '-', $data_detail_header['status_pengadaan'])); ?>">
                            </div>
                             <?php if (!empty($data_detail_header['foto_surat_pengajuan'])): ?>
                                <div class="form-group">
                                    <label>Foto Surat</label><br>
                                    <a data-fancybox="surat" data-src="<?php echo htmlspecialchars($data_detail_header['foto_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" href="javascript:;" class="btn btn-secondary btn-sm">Lihat Foto Surat</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <hr style="margin: 20px 0;">
                        
                        <h3>Tabel 1: Detail Status Approval Item</h3>
                        <div class="table-container">
                             <table class="approval-table">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Nama Barang</th>
                                        <th>Ref.</th>
                                        <th class="col-qty">Jml Minta</th>
                                        <th class="col-rp">Total Minta (Rp)</th>
                                        <th>Status Logum</th>
                                        <th>Jml Appr. Logum</th>
                                        <th class="col-rp">Total Appr. (Rp)</th>
                                        <th>Status Direktur</th>
                                        <th>Jml Appr. Direktur</th>
                                        <th class="col-rp">Total Appr. (Rp)</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data_detail_items as $item): 
                                        $harga_satuan = (double)$item['harga_satuan'];
                                    ?>
                                    <tr class="status-<?php echo strtolower($item['status_approval_direktur']); ?>">
                                        <td><?php echo htmlspecialchars($item['no_urut'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($item['nama_barang'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <?php if (!empty($item['foto_referensi'])): ?>
                                                <a data-fancybox="item-ref" data-src="<?php echo htmlspecialchars($item['foto_referensi'], ENT_QUOTES, 'UTF-8'); ?>" href="javascript:;" class="btn btn-secondary btn-sm">Foto</a>
                                            <?php else: echo "-"; endif; ?>
                                        </td>
                                        <td class="col-qty"><?php echo number_format($item['jumlah_diminta'], 0, ',', '.'); ?></td>
                                        <td class="col-rp"><?php echo number_format($item['jumlah_diminta'] * $harga_satuan, 0, ',', '.'); ?></td>
                                        <td class="status-<?php echo strtolower($item['status_approval_logum']); ?>"><?php echo htmlspecialchars($item['status_approval_logum'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="col-qty"><?php echo number_format($item['jumlah_disetujui_logum'], 0, ',', '.'); ?></td>
                                        <td class="col-rp"><?php echo number_format($item['jumlah_disetujui_logum'] * $harga_satuan, 0, ',', '.'); ?></td>
                                        <td class="status-<?php echo strtolower($item['status_approval_direktur']); ?>"><?php echo htmlspecialchars($item['status_approval_direktur'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="col-qty"><?php echo number_format($item['jumlah_disetujui_direktur'], 0, ',', '.'); ?></td>
                                        <td class="col-rp"><?php echo number_format($item['jumlah_disetujui_direktur'] * $harga_satuan, 0, ',', '.'); ?></td>
                                        <td>
                                            <?php 
                                            echo "<b>Logum:</b> " . htmlspecialchars($item['catatan_logum'], ENT_QUOTES, 'UTF-8') . "<br>";
                                            echo "<b>Direktur:</b> " . htmlspecialchars($item['catatan_direktur'], ENT_QUOTES, 'UTF-8'); 
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr style="margin: 20px 0;">
                        
                        <h3>Tabel 2: Detail Realisasi Barang (Sisa)</h3>
                         <div class="table-container">
                             <table>
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Nama Barang</th>
                                        <th>Jml Disetujui</th>
                                        <th class="col-rp">Total Disetujui (Rp)</th>
                                        <th>Jml Sudah Datang</th>
                                        <th class="col-rp">Total Datang (Rp)</th>
                                        <th>Sisa</th>
                                        <th class="col-sisa-rp">Nilai Sisa (Rp)</th>
                                        <th>Detail Kedatangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data_detail_items as $item): 
                                        if ($item['status_approval_direktur'] != 'Disetujui') continue;
                                        $harga_satuan = (double)$item['harga_satuan'];
                                        $sisa = $item['jumlah_disetujui_direktur'] - $item['jumlah_sudah_divalidasi'];
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['no_urut'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($item['nama_barang'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="col-qty"><?php echo number_format($item['jumlah_disetujui_direktur'], 0, ',', '.'); ?></td>
                                        <td class="col-rp"><?php echo number_format($item['jumlah_disetujui_direktur'] * $harga_satuan, 0, ',', '.'); ?></td>
                                        <td class="col-qty"><?php echo number_format($item['jumlah_sudah_divalidasi'], 0, ',', '.'); ?></td>
                                        <td class="col-rp"><?php echo number_format($item['jumlah_sudah_divalidasi'] * $harga_satuan, 0, ',', '.'); ?></td>
                                        <td class="col-qty" style="background-color: #fff3cd; font-weight: bold;"><?php echo number_format($sisa, 0, ',', '.'); ?></td>
                                        <td class="col-sisa-rp"><?php echo number_format($sisa * $harga_satuan, 0, ',', '.'); ?></td>
                                        <td>
                                            <?php if (isset($data_detail_validasi[$item['no_urut']])): ?>
                                                <ul style="margin: 0; padding-left: 20px;">
                                                <?php foreach ($data_detail_validasi[$item['no_urut']] as $log): ?>
                                                    <li>
                                                        <?php echo htmlspecialchars(date('d-m-Y', strtotime($log['tanggal_validasi'])), ENT_QUOTES, 'UTF-8'); ?>: 
                                                        <b><?php echo number_format($log['jumlah_datang'], 0, ',', '.'); ?> pcs</b>
                                                        (<?php echo htmlspecialchars($log['catatan_validasi'], ENT_QUOTES, 'UTF-8'); ?>)
                                                        <?php if (!empty($log['foto_bukti_datang'])): ?>
                                                            <a data-fancybox="validasi-<?php echo $item['no_urut']; ?>" data-src="<?php echo htmlspecialchars($log['foto_bukti_datang'], ENT_QUOTES, 'UTF-8'); ?>" data-caption="<?php echo htmlspecialchars($log['catatan_validasi'], ENT_QUOTES, 'UTF-8'); ?>" href="javascript:;">[Foto]</a>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <i>(Belum ada)</i>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                
                <?php // Tampilan Dashboard Direktur (action=view)
                else: 
                ?>
                    <div class="menu-wrapper">
                        <div class="form-section">
                            <h3>Menu 1: Approval (Tugas)</h3>
                            <p>Daftar pengajuan yang menunggu approval final Anda.</p>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Aksi</th>
                                            <th>No. Surat</th>
                                            <th>Pengaju</th>
                                            <th>Status Logum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($list_approval_direktur)): ?>
                                            <tr><td colspan="4" style="text-align: center;">Tidak ada pengajuan yang menunggu approval Anda.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($list_approval_direktur as $row): ?>
                                            <tr>
                                                <td class="actions">
                                                    <a href="index.php?action=detail_direktur&id=<?php echo htmlspecialchars($row['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-sm">Proses</a>
                                                    <a href="index.php?action=detail_lengkap&id=<?php echo htmlspecialchars($row['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary btn-sm">Detail</a>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($row['nama_pengaju'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="status-<?php echo strtolower(str_replace(' ', '-', $row['status_approval_logum'])); ?>">
                                                    <?php echo htmlspecialchars($row['status_approval_logum'], ENT_QUOTES, 'UTF-8'); ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>Menu 2: Riwayat (Bisa Diedit)</h3>
                            <p>Daftar pengajuan yang sudah Anda proses tapi belum divalidasi Logum.</p>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Aksi</th>
                                            <th>No. Surat</th>
                                            <th>Pengaju</th>
                                            <th>Status Anda</th>
                                            <th class="col-rp">Total Disetujui</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($list_riwayat_direktur)): ?>
                                            <tr><td colspan="5" style="text-align: center;">Tidak ada riwayat yang bisa diedit.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($list_riwayat_direktur as $row): ?>
                                            <tr>
                                                <td class="actions">
                                                    <a href="index.php?action=detail_direktur&id=<?php echo htmlspecialchars($row['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-warning btn-sm">Edit Approval</a>
                                                    <a href="index.php?action=detail_lengkap&id=<?php echo htmlspecialchars($row['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary btn-sm">Detail</a>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($row['nama_pengaju'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="status-<?php echo strtolower(str_replace(' ', '-', $row['status_approval_direktur'])); ?>">
                                                    <?php echo htmlspecialchars($row['status_approval_direktur'], ENT_QUOTES, 'UTF-8'); ?>
                                                </td>
                                                <td class="col-rp"><?php echo number_format($row['total_disetujui'], 0, ',', '.'); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                    </div>
                <?php endif; ?>

            <?php endif; // Akhir dari router 3 role ?>

        </div>
        <footer class="footer">
            Copyright &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($nama_instansi, ENT_QUOTES, 'UTF-8'); ?>
        </footer>
    </div>
    
    
    <?php // Modal untuk Validasi Barang Datang (HANYA UNTUK LOGUM)
    if ($role_login == 'logum'): ?>
    <div id="modalValidasi" class="modal">
        <div class="modal-content">
            <span id="closeModalValidasi" class="modal-close">&times;</span>
            <form action="index.php?action=validate_logum" method="POST" enctype="multipart/form-data" onsubmit="return handleFormSubmit(this);">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" id="modal_no_surat" name="no_surat_pengajuan">
                <input type="hidden" id="modal_no_urut" name="no_urut_detail">
                
                <h2>Validasi Barang Datang</h2>
                
                <div class="form-group">
                    <label>Nama Barang:</label>
                    <input type="text" id="modal_nama_barang" readonly style="background-color: #eee;">
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label>Sisa Belum Datang:</label>
                    <input type="text" id="modal_sisa" readonly style="background-color: #eee;">
                </div>
                
                <hr>
                
                <div class="form-group" style="margin-top: 10px;">
                    <label for="modal_jumlah_datang">Jumlah Datang Saat Ini:</label>
                    <input type="text" id="modal_jumlah_datang" name="jumlah_datang" class="format-angka" required style="border-color: #007bff;">
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label for="modal_catatan_validasi">Catatan Validasi (No. Faktur, dll):</label>
                    <input type="text" id="modal_catatan_validasi" name="catatan_validasi">
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label for="modal_foto_bukti">Upload Foto Bukti Datang (Opsional):</label>
                    <input type="file" id="modal_foto_bukti" name="foto_bukti_datang">
                </div>
                
                <div class="action-buttons" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Simpan Validasi</button>
                    <button type="button" id="batalModalValidasi" class="btn btn-secondary">Batal</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>


    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    
    <script>
        /*
         * ==================================================================
         * JavaScript untuk Aplikasi Pengajuan Aset (V.15 FINAL LENGKAP)
         * ==================================================================
         */
    
        /* [PERBAIKAN BUG 4] Mencegah double-click */
        function handleFormSubmit(form) {
            var buttons = form.querySelectorAll('button[type="submit"]');
            for (var i = 0; i < buttons.length; i++) {
                buttons[i].disabled = true; 
                buttons[i].innerText = 'Menyimpan... Mohon Tunggu...';
            }
            return true; 
        }

        function formatRupiahInput(angka) {
            var number_string = (angka || '').toString().replace(/[^0-9]/g, '');
            var sisa = number_string.length % 3;
            var rupiah = number_string.substr(0, sisa);
            var ribuan = number_string.substr(sisa).match(/\d{3}/g);

            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }
            return (rupiah === '') ? '0' : rupiah;
        }
        
         function formatAngkaInput(angka) {
            var number_string = (angka || '').toString().replace(/[^0-9]/g, '');
            if (number_string.length > 1 && number_string.startsWith('0')) {
                 number_string = number_string.substring(1);
            }
            if (number_string === '') {
                return '0';
            }
            return number_string;
        }

        function tambahBarisBarang() {
            var tbody = document.getElementById('item-list');
            var newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td><input type="text" name="nama_barang[]" required></td>
                <td><input type="text" name="jumlah[]" class="format-angka" value="1" required></td>
                <td><input type="text" name="harga_satuan[]" class="format-rupiah" value="0" required></td>
                <td><input type="file" name="foto_referensi[]"></td>
                <td><button type="button" class="remove-item-btn">Hapus</button></td>
            `;
            tbody.appendChild(newRow);
            // Inisialisasi format untuk baris baru
            newRow.querySelector('.format-angka').value = formatAngkaInput('1');
            newRow.querySelector('.format-rupiah').value = formatRupiahInput('0');
        }

        function hapusBarisBarang(btn) {
            var row = btn.closest('tr');
            if (document.getElementById('item-list').getElementsByTagName('tr').length > 1) {
                row.parentNode.removeChild(row);
            } else {
                alert('Minimal harus ada 1 item barang yang diajukan.');
                row.querySelector('input[name="nama_barang[]"]').value = '';
                row.querySelector('input[name="jumlah[]"]').value = '1';
                row.querySelector('input[name="harga_satuan[]"]').value = '0';
                row.querySelector('input[name="foto_referensi[]"]').value = '';
            }
        }
        
        function konfirmasiHapus(noSurat, token) {
            if (confirm('Apakah Anda yakin ingin menghapus pengajuan nomor ' + noSurat + '? Data yang sudah diproses tidak bisa dikembalikan.')) {
                window.location.href = 'index.php?action=delete&id=' + noSurat + '&token=' + token;
            }
        }

        function setAllApproval(status) {
            var valueToSet = (status === 'setuju') ? 'Disetujui' : 'Ditolak';
            var radios = document.querySelectorAll('.approval-table input[type="radio"][value="' + valueToSet + '"]');
            radios.forEach(function(radio) {
                radio.checked = true;
                var event = new Event('change');
                radio.dispatchEvent(event);
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            
            Fancybox.bind('[data-fancybox]', {
                Toolbar: {
                    display: { left: ["close"], middle: [], right: ["zoomIn", "zoomOut", "flipX", "flipY", "rotateCCW", "rotateCW", "download"] },
                },
            });
            
            if(document.getElementById('select-pj-logum')) {
                new TomSelect("#select-pj-logum",{
                    create: false,
                    sortField: { field: "text", direction: "asc" }
                });
            }
            
            var itemList = document.getElementById('item-list');
            if (itemList) {
                 document.getElementById('add-item-btn').addEventListener('click', tambahBarisBarang);
                 
                itemList.addEventListener('click', function(e) {
                    if (e.target && e.target.classList.contains('remove-item-btn')) {
                        hapusBarisBarang(e.target);
                    }
                });
                
                itemList.addEventListener('keyup', function(e) {
                    if (e.target && e.target.classList.contains('format-rupiah')) {
                        e.target.value = formatRupiahInput(e.target.value);
                    }
                    if (e.target && e.target.classList.contains('format-angka')) {
                        e.target.value = formatAngkaInput(e.target.value);
                    }
                });
                itemList.querySelectorAll('.format-rupiah').forEach(function(el) { el.value = formatRupiahInput(el.value); });
                itemList.querySelectorAll('.format-angka').forEach(function(el) { el.value = formatAngkaInput(el.value); });
            }
            
            var btnSetujui = document.getElementById('setujui-semua');
            var btnTolak = document.getElementById('tolak-semua');
            
            if (btnSetujui) {
                btnSetujui.addEventListener('click', function() { setAllApproval('setuju'); });
            }
            if (btnTolak) {
                btnTolak.addEventListener('click', function() { setAllApproval('tolak'); });
            }
            
            // [PERBAIKAN BUG 2] Logika untuk 'Nilai 0'
            document.querySelectorAll('.approval-table input[name^="status_item"]').forEach(function(radio) {
                var listener = function() {
                    if (this.checked) { 
                        var row = this.closest('tr');
                        var inputJumlah = row.querySelector('input.input-jumlah'); 
                        if (inputJumlah) {
                            inputJumlah.disabled = (this.value === 'Ditolak');
                            if (this.value === 'Ditolak') {
                                if (!inputJumlah.hasAttribute('data-old-value')) {
                                    inputJumlah.setAttribute('data-old-value', inputJumlah.value); 
                                }
                                inputJumlah.value = '0'; 
                            } else if (this.value === 'Disetujui') {
                                var oldVal = inputJumlah.getAttribute('data-old-value');
                                if (oldVal && inputJumlah.value === '0') {
                                    inputJumlah.value = oldVal;
                                }
                            }
                        }
                    }
                };
                radio.addEventListener('change', listener);
                if (radio.checked) {
                    listener.call(radio);
                }
            });
            
            document.querySelectorAll('input.format-angka').forEach(function(input) {
                input.addEventListener('keyup', function(e) {
                    e.target.value = formatAngkaInput(e.target.value);
                });
                // Inisialisasi format saat load
                input.value = formatAngkaInput(input.value);
            });
             document.querySelectorAll('input.format-rupiah').forEach(function(input) {
                input.addEventListener('keyup', function(e) {
                    e.target.value = formatRupiahInput(e.target.value);
                });
                // Inisialisasi format saat load
                input.value = formatRupiahInput(input.value);
            });

            var modalValidasi = document.getElementById('modalValidasi');
            if(modalValidasi) {
                var closeModalValidasi = document.getElementById('closeModalValidasi');
                var batalModalValidasi = document.getElementById('batalModalValidasi');

                document.querySelectorAll('.btn-validasi').forEach(function(button) {
                    button.addEventListener('click', function() {
                        var surat = this.getAttribute('data-surat');
                        var urut = this.getAttribute('data-urut');
                        var nama = this.getAttribute('data-nama');
                        var sisa = this.getAttribute('data-sisa');

                        document.getElementById('modal_no_surat').value = surat;
                        document.getElementById('modal_no_urut').value = urut;
                        document.getElementById('modal_nama_barang').value = nama;
                        document.getElementById('modal_sisa').value = sisa;
                        document.getElementById('modal_jumlah_datang').value = sisa;
                        document.getElementById('modal_catatan_validasi').value = '';
                        document.getElementById('modal_foto_bukti').value = '';
                        
                        modalValidasi.style.display = "block";
                    });
                });

                closeModalValidasi.onclick = function() { modalValidasi.style.display = "none"; }
                batalModalValidasi.onclick = function() { modalValidasi.style.display = "none"; }
                window.onclick = function(event) {
                    if (event.target == modalValidasi) {
                        modalValidasi.style.display = "none";
                    }
                }
                
                var modalJumlah = document.getElementById('modal_jumlah_datang');
                if(modalJumlah) {
                     modalJumlah.addEventListener('keyup', function(e) {
                        e.target.value = formatAngkaInput(e.target.value);
                     });
                }
            }
        });
    </script>
    
</body>
</html>
				