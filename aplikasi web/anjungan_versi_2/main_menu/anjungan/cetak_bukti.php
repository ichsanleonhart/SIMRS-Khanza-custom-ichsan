<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include_once '../conf/conf.php';

// ambil koneksi dari fungsi bukakoneksi()
$conn = bukakoneksi();

// ambil no_rawat dari query
$no_rawat = $_GET['no_rawat'] ?? '';

// query data registrasi
$sql = "SELECT r.no_rawat, r.no_reg, p.no_rkm_medis, p.nm_pasien, pl.nm_poli, d.nm_dokter, pj.png_jawab
        FROM reg_periksa r
        JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis
        JOIN poliklinik pl ON r.kd_poli=pl.kd_poli
        JOIN dokter d ON r.kd_dokter=d.kd_dokter
        JOIN penjab pj ON r.kd_pj=pj.kd_pj
        WHERE r.no_rawat='$no_rawat'";

$result = $conn->query($sql);
if (!$result) {
    die("Query error: " . $conn->error);
}
$data = $result->fetch_assoc();

// ambil setting instansi
$setting = $conn->query("SELECT nama_instansi, alamat_instansi, kabupaten FROM setting LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Bukti Registrasi</title>
  <link rel="stylesheet" href="anjungan.css">
</head>
<body onload="cetakDanKembali()">

  <div class="kop">
    <div class="logo">
      <?php include '../assets/logo.php'; ?>
    </div>
    <div class="instansi">
      <h1><?= $setting['nama_instansi'] ?></h1>
      <p><?= $setting['alamat_instansi'] ?> – <?= $setting['kabupaten'] ?></p>
    </div>
  </div>

  <h3 class="center">Bukti Registrasi</h3>
  <table>
    <tr><td>No. Rawat</td><td>:</td><td><?= $data['no_rawat'] ?? '' ?></td></tr>
    <tr><td>No. Reg</td><td>:</td><td><?= $data['no_reg'] ?? '' ?></td></tr>
    <tr><td>No. RM</td><td>:</td><td><?= $data['no_rkm_medis'] ?? '' ?></td></tr>
    <tr><td>Nama Pasien</td><td>:</td><td><?= $data['nm_pasien'] ?? '' ?></td></tr>
    <tr><td>Poli</td><td>:</td><td><?= $data['nm_poli'] ?? '' ?></td></tr>
    <tr><td>Dokter</td><td>:</td><td><?= $data['nm_dokter'] ?? '' ?></td></tr>
    <tr><td>Jenis Bayar</td><td>:</td><td><?= $data['png_jawab'] ?? '' ?></td></tr>
    <tr><td>Tanggal</td><td>:</td><td><?= date("d-m-Y") ?></td></tr>
    <tr><td>Jam</td><td>:</td><td><?= date("H:i") ?></td></tr>
  </table>

  <p class="center" style="margin-top:15px;">
    *** Harap dibawa saat pemeriksaan ***
  </p>

  <script>
    function cetakDanKembali() {
      // tampilkan dialog print
      window.print();

      // bersihkan sessionStorage agar input kosong
      sessionStorage.clear();

      // setelah print, kembali ke menu awal
      setTimeout(function() {
        window.location.href = 'anjungan.php';
      }, 1000);
    }
  </script>
</body>
</html>
