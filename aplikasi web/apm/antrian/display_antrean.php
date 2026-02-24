<?php
include_once '../conf/conf.php';
$setting = fetch_assoc("SELECT nama_instansi FROM setting LIMIT 1");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Display Antrean TV</title>
  <style>
    body {
      margin: 0; padding: 0; background: #1a252f; color: #fff;
      font-family: 'Segoe UI', sans-serif; height: 100vh;
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      overflow: hidden;
    }
    .instansi { font-size: 30px; font-weight: bold; color: #f1c40f; margin-bottom: 40px; text-transform: uppercase; letter-spacing: 2px;}
    
    .card-display { background: rgba(255,255,255,0.1); border-radius: 20px; padding: 60px 100px; text-align: center; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
    .label-atas { font-size: 35px; color: #ecf0f1; margin-bottom: 20px; }
    .nomor-raksasa { font-size: 200px; font-weight: bold; color: #e74c3c; line-height: 1; text-shadow: 4px 4px 10px rgba(0,0,0,0.3); margin-bottom: 20px; }
    .label-bawah { font-size: 40px; color: #2ecc71; font-weight: bold; }
    
    /* Tombol Start untuk mengizinkan Audio Autoplay di Browser */
    #startOverlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); display: flex; align-items: center; justify-content: center; z-index: 1000; }
    .btn-start { background: #3498db; color: #fff; padding: 20px 40px; font-size: 30px; border: none; border-radius: 10px; cursor: pointer; }
  </style>
</head>
<body>

  <div id="startOverlay">
    <button class="btn-start" onclick="mulaiDisplay()">▶ MULAI DISPLAY TV</button>
  </div>

  <div class="instansi"><?= htmlspecialchars($setting['nama_instansi']) ?></div>

  <div class="card-display">
    <div class="label-atas">NOMOR ANTREAN</div>
    <div class="nomor-raksasa" id="displayNomor">---</div>
    <div class="label-bawah" id="displayLoket">MENUJU LOKET -</div>
  </div>

  <script>
    let lastTimestamp = 0;

    function mulaiDisplay() {
        document.getElementById('startOverlay').style.display = 'none';
        
        // Pancing TTS browser dengan suara kosong agar aktif
        const initSpeech = new SpeechSynthesisUtterance('');
        window.speechSynthesis.speak(initSpeech);

        // Mulai polling cek data dari server setiap 2 detik
        setInterval(cekPanggilanBaru, 2000);
    }

    async function cekPanggilanBaru() {
        try {
            // Parameter cache-buster agar browser TV tidak meload cache lama
            const res = await fetch('api_cek_display.php?v=' + new Date().getTime());
            const data = await res.json();

            // Jika ada perubahan timestamp (berarti tombol panggil baru saja ditekan)
            if (data.timestamp > lastTimestamp && data.nomor !== '-') {
                lastTimestamp = data.timestamp;
                
                // Animasi & Update Angka
                document.getElementById('displayNomor').innerText = data.nomor;
                document.getElementById('displayLoket').innerText = "MENUJU LOKET " + data.loket;

                // Mainkan Suara
                suaraPanggilan(data.nomor, data.loket);
            }
        } catch (e) {
            console.error("Gagal terhubung ke API Display");
        }
    }

    function suaraPanggilan(nomorTeks, loket) {
        const nomorAngka = parseInt(nomorTeks, 10);
        const teks = `Antrian admisi, nomor, ${nomorAngka}. Silakan menuju, loket, ${loket}`;
        
        const speech = new SpeechSynthesisUtterance(teks);
        speech.lang = 'id-ID';
        speech.rate = 0.8;
        window.speechSynthesis.speak(speech);
    }
  </script>
</body>
</html>