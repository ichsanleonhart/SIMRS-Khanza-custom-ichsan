<?php
/*
 * ==================================================================
 * CONFIG_PENGAJUAN_ASET.PHP (TAHAP 1)
 * ==================================================================
 * File konfigurasi khusus untuk aplikasi Pengajuan Aset.
 * Dibuat agar aplikasi mudah dipindah/di-deploy.
 *
 * Dibuat kompatibel dengan PHP 7.3
 */

// Komentar: Memanggil file konfigurasi database global dari Khanza
// Pastikan path ini (../conf/conf.php) sudah benar
include_once __DIR__ . '/../conf/conf.php';

// Komentar: Mendefinisikan URL publik utama aplikasi ini.
// Ganti URL ini jika domain Anda berbeda. Ini digunakan untuk link verifikasi QR Code.
define('APP_URL_PENGAJUAN_ASET', 'https://berkas.rskarinamedika.com/webapps/pengajuan_asset');

// Komentar: Mendefinisikan path folder penyimpanan.
// Ini adalah path relatif dari file index.php
define('PATH_FOTO_SURAT', 'foto_surat/');
define('PATH_FOTO_REFERENSI', 'foto_referensi/');
define('PATH_FOTO_VALIDASI', 'foto_validasi/');

?>