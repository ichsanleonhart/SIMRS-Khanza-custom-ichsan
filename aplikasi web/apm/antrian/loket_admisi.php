<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Loket Admisi</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f4f7f6;
      color: #333;
      margin: 0; padding: 20px;
    }
    .container {
      max-width: 1000px;
      margin: 40px auto;
      display: flex;
      gap: 20px;
    }
    .card {
      background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      padding: 30px;
    }
    .panel-kiri { flex: 1; text-align: center; }
    .panel-kanan { flex: 1; }
    
    h2, h3 { margin-top: 0; color: #2c3e50; }
    .hidden { display: none !important; }
    
    /* Tombol & Input */
    select { width: 100%; padding: 12px; font-size: 16px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 20px; }
    button { border: none; padding: 12px 24px; font-size: 16px; border-radius: 30px; cursor: pointer; font-weight: bold; width: 100%; transition: 0.3s; margin-bottom: 10px; color: #fff;}
    .btn-primary { background: #2980b9; } .btn-primary:hover { background: #3498db; }
    .btn-success { background: #27ae60; padding: 15px 24px; font-size: 18px; } .btn-success:hover { background: #2ecc71; }
    .btn-warning { background: #f39c12; } .btn-warning:hover { background: #f1c40f; }
    
    /* Layout Antrean */
    .nomor-display { font-size: 80px; font-weight: bold; color: #e74c3c; margin: 10px 0; line-height: 1; }
    .info-tunggu { font-size: 14px; color: #7f8c8d; margin-bottom: 20px; }
    .opsi-suara { text-align: left; background: #ecf0f1; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 20px;}
    
    /* Tabel Waiting List */
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
    th { background: #2c3e50; color: #fff; border-radius: 4px; }
    .badge-menunggu { background: #e74c3c; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px;}
  </style>
</head>
<body>

  <div class="card" id="setupLoket" style="max-width: 400px; margin: 100px auto; text-align:center;">
    <h2>Pilih Loket Admisi</h2>
    <p>Silakan pilih Anda bertugas di loket berapa:</p>
    <select id="pilihanLoket">
      <option value="" disabled selected>-- Pilih Loket --</option>
      <option value="1">Loket 1</option>
      <option value="2">Loket 2</option>
      <option value="3">Loket 3</option>
      <option value="4">Loket 4</option>
    </select>
    <button class="btn-primary" onclick="mulaiTugas()">Mulai Bertugas</button>
  </div>

  <div class="container hidden" id="panelUtama">
    <div class="card panel-kiri">
      <h3 style="color:#95a5a6;" id="teksLoketAktif">LOKET ADMISI</h3>
      <h2>Antrean Saat Ini</h2>
      <div class="nomor-display" id="nomorAntrean">---</div>
      <div class="info-tunggu" id="infoTunggu">Waktu Tunggu: - menit</div>
      
      <div class="opsi-suara">
        <label>
          <input type="checkbox" id="suaraLokal" checked> 
          <strong>Mainkan Suara di Komputer Ini</strong><br>
          <span style="color:#7f8c8d;">(Hilangkan centang jika suara hanya diputar melalui Layar TV Utama)</span>
        </label>
      </div>

      <button class="btn-success" onclick="panggilAntrean('panggil_next')">📢 PANGGIL SELANJUTNYA</button>
      <button class="btn-warning" onclick="panggilAntrean('panggil_ulang')">🔄 Panggil Ulang</button>
    </div>

    <div class="card panel-kanan">
      <h3>Daftar Menunggu</h3>
      <div style="max-height: 400px; overflow-y: auto;">
        <table id="tabelMenunggu">
          <thead>
            <tr>
              <th>Nomor</th>
              <th>Waktu Ambil</th>
              <th>Menunggu</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="3" style="text-align:center;">Memuat data...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
    let loketAktif = "";
    let intervalWaitlist;

    function mulaiTugas() {
      const select = document.getElementById("pilihanLoket");
      if (!select.value) { alert("Pilih loket terlebih dahulu!"); return; }
      loketAktif = select.value;
      
      document.getElementById("setupLoket").classList.add("hidden");
      document.getElementById("panelUtama").classList.remove("hidden");
      document.getElementById("teksLoketAktif").innerText = "LOKET ADMISI " + loketAktif;
      
      panggilAntrean('panggil_ulang', false); 
      loadWaitlist();
      
      // Auto-refresh daftar tunggu setiap 10 detik
      intervalWaitlist = setInterval(loadWaitlist, 10000);
    }

    async function panggilAntrean(action, playSoundParam = true) {
      try {
        const res = await fetch(`api_panggil_loket.php?action=${action}&loket=${loketAktif}`);
        const data = await res.json();

        if (data.error) {
            if (playSoundParam) alert(data.error);
            return;
        }

        document.getElementById("nomorAntrean").innerText = data.nomor;
        document.getElementById("infoTunggu").innerText = `Waktu Tunggu: ${data.waktu_tunggu}`;

        // Cek apakah opsi suara komputer dicentang
        const mainkanSuara = document.getElementById("suaraLokal").checked;
        if (playSoundParam && mainkanSuara) {
            suaraPanggilan(data.nomor, loketAktif);
        }

        // Segarkan daftar tunggu setiap kali selesai manggil
        loadWaitlist();

      } catch (e) { alert("Terjadi kesalahan jaringan: " + e.message); }
    }

    async function loadWaitlist() {
        try {
            const res = await fetch(`api_panggil_loket.php?action=get_waiting`);
            const data = await res.json();
            const tbody = document.querySelector("#tabelMenunggu tbody");
            
            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="3" style="text-align:center;">Tidak ada antrean menunggu</td></tr>`;
                return;
            }

            let html = "";
            data.forEach(p => {
                html += `
                <tr>
                    <td><strong>${p.nomor}</strong></td>
                    <td>${p.jam}</td>
                    <td><span class="badge-menunggu">${p.tunggu_berjalan} menit</span></td>
                </tr>`;
            });
            tbody.innerHTML = html;
        } catch (e) {
            console.error("Gagal load daftar tunggu");
        }
    }

    function suaraPanggilan(nomorTeks, loket) {
        const nomorAngka = parseInt(nomorTeks, 10);
        const teks = `Antrian admisi, nomor, ${nomorAngka}. Silakan menuju, loket, ${loket}`;
        const speech = new SpeechSynthesisUtterance(teks);
        speech.lang = 'id-ID'; speech.rate = 0.8;
        window.speechSynthesis.speak(speech);
    }
  </script>
</body>
</html>