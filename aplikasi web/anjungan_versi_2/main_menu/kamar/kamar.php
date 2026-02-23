<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../conf/conf.php';
include_once '../conf/helpers.php';

// Ambil setting instansi
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten FROM setting LIMIT 1");

// Ambil data kamar + nama bangsal + kelas dari tabel kamar
$sql = "SELECT b.nm_bangsal, k.kelas, k.statusdata,
               COUNT(k.kd_kamar) AS jumlah,
               SUM(CASE WHEN k.status='ISI' THEN 1 ELSE 0 END) AS terisi,
               SUM(CASE WHEN k.status='KOSONG' THEN 1 ELSE 0 END) AS kosong
        FROM kamar k
        JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
        Where k.statusdata = '1'
        GROUP BY b.nm_bangsal, k.kelas, k.statusdata
        ORDER BY k.kelas ASC";
$result = bukaquery($sql);

// Hitung jumlah card
$count = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Ketersediaan Kamar</title>
  <!-- CSS global (logo, instansi, jam, banner) -->
  <link rel="stylesheet" href="../assets/style.css">
  <!-- CSS khusus kamar -->
  <link rel="stylesheet" href="kamar.css">
</head>
<body>
  <header class="header">
    <div class="logo">
      <?php include '../assets/logo.php'; ?>
    </div>
    <div class="instansi">
      <h1><?= $setting['nama_instansi'] ?></h1>
      <p><?= $setting['alamat_instansi'] ?> – <?= $setting['kabupaten'] ?></p>
    </div>
    <div id="clock"></div>
    <div id="next-prayer"></div>
  </header>

  <main class="dashboard">
    <h2>DASHBOARD KETERSEDIAAN KAMAR INAP</h2>

    <!-- Grid: scrollable hanya jika card >= 10 -->
    <div class="grid <?= ($count >= 10 ? 'scrollable' : '') ?>">
      <?php while($row = mysqli_fetch_assoc($result)): ?>
        <div class="card">
          <h3><?= $row['nm_bangsal'] ?></h3>
          <div class="kelas">(<?= $row['kelas'] ?>)</div>
          <table class="info">
            <tr><td>Total Bed</td><td>: <?= $row['jumlah'] ?></td></tr>
            <tr><td>Terisi</td><td>: <?= $row['terisi'] ?></td></tr>
            <tr><td>Kosong</td><td class="kosong">: <?= $row['kosong'] ?></td></tr>
          </table>
        </div>
      <?php endwhile; ?>
    </div>

    <!-- Banner ucapan default -->
    <?php include '../assets/banner.php'; ?>
  </main>

  <script src="../assets/clock.js"></script>

  <!-- Refresh otomatis setiap 60 detik -->
  <script>
  setTimeout(function(){
     location.reload();
  }, 60000);
  </script>

  <!-- Auto scroll vertikal hanya jika scrollable -->
  <script>
    document.querySelectorAll('.grid.scrollable').forEach(grid => {
      let direction = 1; // 1 = turun, -1 = naik
      function autoScroll() {
        grid.scrollTop += direction;
        if (grid.scrollTop + grid.clientHeight >= grid.scrollHeight) {
          direction = -1; // ganti arah ke atas
        } else if (grid.scrollTop <= 0) {
          direction = 1; // ganti arah ke bawah
        }
      }
      setInterval(autoScroll, 50); // kecepatan scroll
    });
  </script>

</body>
</html>
