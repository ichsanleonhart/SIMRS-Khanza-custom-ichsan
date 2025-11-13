<?php
/*
 * ==================================================================
 * SETUP.PHP (PENGEMBANGAN APLIKASI PENGAJUAN ASET - TAHAP 1)
 * ==================================================================
 * Jalankan file ini sekali saja untuk membuat 4 tabel yang dibutuhkan
 * Sesuai Rancang Bangun Final V.05.
 *
 * Dibuat kompatibel dengan PHP 7.3
 */

// Sertakan file konfigurasi database utama
include_once '../conf/conf.php';

// Komentar: Membuka koneksi ke database menggunakan fungsi dari conf.php
$konektor = bukakoneksi();
if (!$konektor) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

echo "<h3>Memulai proses setup database...</h3>";

// SQL 1: Tabel Header Pengajuan Aset (pengajuan_asset)
$sql_tabel_1 = "
CREATE TABLE IF NOT EXISTS `pengajuan_asset` (
  `no_surat_pengajuan` varchar(20) NOT NULL,
  `tanggal_pengajuan` date NOT NULL,
  `nik` varchar(20) NOT NULL COMMENT 'NIK Pengaju',
  `nik_pj` varchar(20) NOT NULL COMMENT 'NIK Petugas Logum Yg Dituju',
  `urgensi` enum('Cito','Emergensi','Biasa') NOT NULL,
  `uraian_latar_belakang` varchar(200) NOT NULL,
  `tujuan_pengajuan` varchar(200) NOT NULL,
  `target_sasaran` varchar(70) NOT NULL,
  `lokasi_pengajuan` varchar(70) NOT NULL,
  `keterangan` varchar(70) NOT NULL,
  `total_pengajuan` double NOT NULL DEFAULT '0',
  `total_disetujui` double NOT NULL DEFAULT '0' COMMENT 'Total setelah approval Direktur',
  
  `status_approval_logum` enum('Menunggu','Disetujui','Ditolak','Ditolak Sebagian') NOT NULL DEFAULT 'Menunggu',
  `user_approval_logum` varchar(20) DEFAULT NULL,
  `waktu_aprove_logum` datetime DEFAULT NULL,
  
  `status_approval_direktur` enum('Menunggu','Disetujui','Ditolak','Ditolak Sebagian') NOT NULL DEFAULT 'Menunggu',
  `user_approval_direktur` varchar(20) DEFAULT NULL,
  `waktu_approval_direktur` datetime DEFAULT NULL,
  
  `status_pengadaan` enum('Baru','Proses Approval','Disetujui','Proses Pengadaan','Selesai Sebagian','Selesai Penuh') NOT NULL DEFAULT 'Baru',
  `foto_surat_pengajuan` varchar(500) DEFAULT NULL,
  
  PRIMARY KEY (`no_surat_pengajuan`),
  KEY `nik` (`nik`),
  KEY `nik_pj` (`nik_pj`),
  CONSTRAINT `pengajuan_asset_ibfk_1` FOREIGN KEY (`nik`) REFERENCES `pegawai` (`nik`) ON UPDATE CASCADE,
  CONSTRAINT `pengajuan_asset_ibfk_2` FOREIGN KEY (`nik_pj`) REFERENCES `pegawai` (`nik`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
";

if (mysqli_query($konektor, $sql_tabel_1)) {
    echo "<p style='color:green;'>1. Tabel 'pengajuan_asset' berhasil dibuat atau sudah ada.</p>";
} else {
    echo "<p style='color:red;'>Gagal membuat tabel 'pengajuan_asset': " . mysqli_error($konektor) . "</p>";
}

// SQL 2: Tabel Detail Item (pengajuan_asset_detail)
$sql_tabel_2 = "
CREATE TABLE IF NOT EXISTS `pengajuan_asset_detail` (
  `no_surat_pengajuan` varchar(20) NOT NULL,
  `no_urut` int(4) NOT NULL,
  `nama_barang` varchar(150) NOT NULL,
  `jumlah_diminta` double NOT NULL,
  `harga_satuan` double NOT NULL,
  `foto_referensi` varchar(500) DEFAULT NULL,
  
  `status_approval_logum` enum('Menunggu','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu',
  `jumlah_disetujui_logum` double NOT NULL DEFAULT '0',
  `catatan_logum` varchar(150) DEFAULT NULL,
  
  `status_approval_direktur` enum('Menunggu','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu',
  `jumlah_disetujui_direktur` double NOT NULL DEFAULT '0' COMMENT 'Kuantitas final yg disetujui',
  `catatan_direktur` varchar(150) DEFAULT NULL,
  
  `jumlah_sudah_divalidasi` double NOT NULL DEFAULT '0' COMMENT 'Total yg sudah datang',

  PRIMARY KEY (`no_surat_pengajuan`, `no_urut`),
  CONSTRAINT `pengajuan_asset_detail_ibfk_1` 
    FOREIGN KEY (`no_surat_pengajuan`) 
    REFERENCES `pengajuan_asset` (`no_surat_pengajuan`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
";

if (mysqli_query($konektor, $sql_tabel_2)) {
    echo "<p style='color:green;'>2. Tabel 'pengajuan_asset_detail' berhasil dibuat atau sudah ada.</p>";
} else {
    echo "<p style='color:red;'>Gagal membuat tabel 'pengajuan_asset_detail': " . mysqli_error($konektor) . "</p>";
}

// SQL 3: Tabel Validasi Kedatangan (pengajuan_asset_validasi)
$sql_tabel_3 = "
CREATE TABLE IF NOT EXISTS `pengajuan_asset_validasi` (
  `id_validasi` int(11) NOT NULL AUTO_INCREMENT,
  `no_surat_pengajuan` varchar(20) NOT NULL,
  `no_urut_detail` int(4) NOT NULL,
  `tanggal_validasi` datetime NOT NULL,
  `jumlah_datang` double NOT NULL,
  `user_validasi_logum` varchar(20) NOT NULL,
  `catatan_validasi` varchar(150) DEFAULT NULL COMMENT 'Misal: Batch 1, No. Faktur Pembelian, dll',
  `foto_bukti_datang` varchar(500) DEFAULT NULL COMMENT 'Path ke foto bukti barang datang',

  PRIMARY KEY (`id_validasi`),
  KEY `no_surat_pengajuan` (`no_surat_pengajuan`,`no_urut_detail`),
  CONSTRAINT `pengajuan_asset_validasi_ibfk_1` 
    FOREIGN KEY (`no_surat_pengajuan`, `no_urut_detail`) 
    REFERENCES `pengajuan_asset_detail` (`no_surat_pengajuan`, `no_urut`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pengajuan_asset_validasi_ibfk_2` 
    FOREIGN KEY (`user_validasi_logum`) 
    REFERENCES `pegawai` (`nik`) 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
";

if (mysqli_query($konektor, $sql_tabel_3)) {
    echo "<p style='color:green;'>3. Tabel 'pengajuan_asset_validasi' berhasil dibuat atau sudah ada.</p>";
} else {
    echo "<p style='color:red;'>Gagal membuat tabel 'pengajuan_asset_validasi': " . mysqli_error($konektor) . "</p>";
}

// SQL 4: Tabel Token Verifikasi (pengajuan_asset_verifikasi)
$sql_tabel_4 = "
CREATE TABLE IF NOT EXISTS `pengajuan_asset_verifikasi` (
  `token` varchar(100) NOT NULL,
  `no_surat_pengajuan` varchar(20) NOT NULL,
  `jenis_surat` enum('approval_logum','approval_direktur','validasi_barang') NOT NULL,
  `waktu_dibuat` datetime NOT NULL,
  
  PRIMARY KEY (`token`),
  KEY `no_surat_pengajuan` (`no_surat_pengajuan`),
  CONSTRAINT `pengajuan_asset_verifikasi_ibfk_1` 
    FOREIGN KEY (`no_surat_pengajuan`) 
    REFERENCES `pengajuan_asset` (`no_surat_pengajuan`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
";

if (mysqli_query($konektor, $sql_tabel_4)) {
    echo "<p style='color:green;'>4. Tabel 'pengajuan_asset_verifikasi' berhasil dibuat atau sudah ada.</p>";
} else {
    echo "<p style='color:red;'>Gagal membuat tabel 'pengajuan_asset_verifikasi': " . mysqli_error($konektor) . "</p>";
}

echo "<h4>Setup Selesai.</h4>";
echo "<p>Silakan **HAPUS** file 'setup.php' ini dari server Anda sekarang demi keamanan.</p>";

mysqli_close($konektor);
?>