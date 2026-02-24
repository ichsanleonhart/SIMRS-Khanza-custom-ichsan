<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../conf/conf.php';

// Ambil data yang dikirim dari javascript
$nomor = $_GET['nomor'] ?? '000';
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$jam = $_GET['jam'] ?? date('H:i:s');

$conn = bukakoneksi();
$setting = $conn->query("SELECT nama_instansi, alamat_instansi, kabupaten FROM setting LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Antrean Loket - <?= htmlspecialchars($nomor) ?></title>
  <style>
    /* Konfigurasi untuk kertas Thermal 80mm */
    @page { margin: 0; size: 80mm auto; }
    body {
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      width: 74mm;
      margin: 4mm auto;
      font-size: 12px;
      text-align: center;
      color: #000;
      background: #fff;
    }
    .header h1 { margin: 0; font-size: 16px; font-weight: bold; text-transform: uppercase; }
    .header p { margin: 2px 0; font-size: 10px; }
    .divider { border-bottom: 1px dashed #000; margin: 10px 0; }
    .title { font-size: 16px; font-weight: bold; margin: 10px 0; }
    .nomor { font-size: 70px; font-weight: bold; margin: 10px 0; line-height: 1; }
    .footer { font-size: 11px; margin-top: 15px; margin-bottom: 20px;}
  </style>
</head>
<body onload="cetakDanKembali()">
  
  <div class="header">
    <h1><?= htmlspecialchars($setting['nama_instansi']) ?></h1>
    <p><?= htmlspecialchars($setting['alamat_instansi']) ?><br><?= htmlspecialchars($setting['kabupaten']) ?></p>
  </div>
  
  <div class="divider"></div>
  <div class="title">ANTREAN LOKET ADMISI</div>
  
  <div class="nomor"><?= htmlspecialchars($nomor) ?></div>
  
  <div class="divider"></div>
  <div>Tanggal: <?= date('d-m-Y', strtotime($tanggal)) ?></div>
  <div>Jam: <?= htmlspecialchars($jam) ?> WIB</div>
  <div class="divider"></div>
  
  <div class="footer">
    <p>Silakan duduk dan tunggu panggilan.<br>Terima kasih.</p>
  </div>

  <script>
    function cetakDanKembali() {
      window.print();
      // Beri jeda sebentar sebelum kembali ke layar utama
      setTimeout(function() {
        window.location.href = 'anjungan.php';
      }, 1500);
    }
  </script>
</body>
</html>