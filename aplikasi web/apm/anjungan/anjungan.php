<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../conf/conf.php';
include_once '../conf/helpers.php';

// Ambil setting instansi
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten FROM setting LIMIT 1");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Anjungan Pendaftaran Mandiri</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="anjungan.css">
</head>
<body>
  <header class="header">
    <div class="logo"><?php include '../assets/logo.php'; ?></div>
    <div class="instansi">
      <h1><?= $setting['nama_instansi'] ?></h1>
      <p><?= $setting['alamat_instansi'] ?> – <?= $setting['kabupaten'] ?></p>
    </div>
    <div id="clock"></div>
    <div id="next-prayer"></div>
  </header>

  <main class="dashboard">
    <h2 id="anjunganTitle" class="anjungan-title">ANJUNGAN PENDAFTARAN MANDIRI</h2>

    <section id="formPasien" class="form-container">
      <div class="aturan">
        <h3>Petunjuk</h3>
        <p>
          Anjungan Pendaftaran Mandiri ini khusus untuk <strong>pasien lama</strong> yang sudah memiliki 
          <strong>Nomor Rekam Medis (No. RM)</strong>.
        </p>
      </div>

      <div class="form-input">
        <label for="identitas">No. KTP / No. RM:</label>
        <input type="text" id="identitas" placeholder="Masukkan No. KTP atau RM" onkeyup="autoCariPasien(event)">
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px dashed rgba(255,255,255,0.3);">
          <p style="font-size: 14px; color: #ddd; margin-bottom: 15px;">Pasien Baru / Belum Punya No. RM?</p>
          <button id="btnAntreanLoket" onclick="cetakAntreanLoket()" style="width: 100%; padding: 15px; font-size: 16px; font-weight: bold; color: #fff; background: linear-gradient(135deg, #e74c3c, #c0392b); border: none; border-radius: 30px; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.3); transition: transform 0.2s;">
            🎟️ AMBIL ANTREAN LOKET
          </button>
        </div>
      </div>
    </section>

    <div id="pasienPoli" class="hidden pasien-poli-layout">
      <section id="dataPasien"></section>
      <section id="jadwalPoli"></section>
    </div>

    <section id="draftBukti" class="hidden">
      <div class="kop-flex">
        <div class="logo">
          <?php include '../assets/logo.php'; ?>
        </div>
        <div class="instansi">
          <h1 id="namaInstansi"></h1>
          <p id="alamatInstansi"></p>
        </div>
      </div>
      <hr>
      <div class="box">
        <h3>Draft Bukti Registrasi</h3>
        <table>
          <tr><td class="label">Nama Pasien</td><td class="colon">:</td><td id="nmPasien"></td></tr>
          <tr><td class="label">Poli</td><td class="colon">:</td><td id="nmPoli"></td></tr>
          <tr><td class="label">Dokter</td><td class="colon">:</td><td id="nmDokter"></td></tr>
          <tr><td class="label">Jenis Bayar</td><td class="colon">:</td><td id="nmBayar"></td></tr>
          <tr><td class="label">Jam Daftar</td><td class="colon">:</td><td id="jamDaftar"></td></tr>
        </table>
        <div style="text-align:center; margin-top:20px;">
          <button onclick="editForm()">⬅ Kembali</button>
          <button onclick="simpanRegistrasi()">💾 Simpan & Cetak</button>
        </div>
      </div>
    </section>
  </main>

  <?php include '../assets/banner.php'; ?>

  <script src="../assets/clock.js"></script>
  <script src="anjungan.js?v=<?= time() ?>"></script>
  <script>
    // fungsi auto cari pasien
    function autoCariPasien(event) {
      if (event.key === "Enter") {
        cariPasien();
      }
    }
  </script>
</body>
</html>