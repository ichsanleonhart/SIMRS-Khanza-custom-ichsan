<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../conf/conf.php';
include_once '../conf/helpers.php';

// Ambil setting instansi
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten FROM setting LIMIT 1");

// Ambil data jadwal dokter
$sql = "SELECT d.nm_dokter, p.nm_poli, j.hari_kerja, j.jam_mulai, j.jam_selesai
        FROM jadwal j
        JOIN dokter d ON j.kd_dokter = d.kd_dokter
        JOIN poliklinik p ON j.kd_poli = p.kd_poli
        ORDER BY d.nm_dokter, p.nm_poli, j.hari_kerja, j.jam_mulai";
$result = bukaquery($sql);

// Susun data per dokter-poli
$jadwal = [];
while($row = mysqli_fetch_assoc($result)) {
    $key = $row['nm_dokter'].'|'.$row['nm_poli'];
    if(!isset($jadwal[$key])) {
        $jadwal[$key] = [];
    }
    $jadwal[$key][] = [
        'hari' => $row['hari_kerja'],
        'mulai' => $row['jam_mulai'],
        'selesai' => $row['jam_selesai']
    ];
}

// Hitung jumlah card
$count = count($jadwal);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Jadwal Praktek Dokter</title>
  <!-- CSS global -->
  <link rel="stylesheet" href="../assets/style.css">
  <!-- CSS khusus jadwal -->
  <link rel="stylesheet" href="jadwal.css">
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
    <h2>DASHBOARD JADWAL PRAKTEK DOKTER</h2>

    <!-- Grid: scrollable hanya jika card > 5 -->
    <div class="grid <?= ($count > 5 ? 'scrollable' : '') ?>">
      <?php foreach($jadwal as $dokterPoli => $list): ?>
        <?php list($namaDokter, $namaPoli) = explode('|', $dokterPoli); ?>
        <div class="card">
          <h3><?= $namaDokter ?></h3>
          <div class="kelas"><?= $namaPoli ?></div>
          <ul class="jadwal-list">
            <?php foreach($list as $j): ?>
              <li><?= $j['hari'] ?> : <?= $j['mulai'] ?> - <?= $j['selesai'] ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
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
