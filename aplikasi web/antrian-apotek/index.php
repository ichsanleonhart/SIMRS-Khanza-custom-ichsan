<?php
// File: index.php
// PERBAIKAN: Memanggil file conf.php lokal
require_once('conf.php'); 

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT"); 
header("Cache-Control: no-store, no-cache, must-revalidate"); 
header("Cache-Control: post-check=0, pre-check=0", false); 
header("Pragma: no-cache"); // HTTP/1.0

// Ambil data setting RS
$hasil_setting = bukaquery("SELECT nama_instansi, logo FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($hasil_setting);
$nama_instansi = $setting['nama_instansi'] ?? 'Antrian Farmasi';
$logo_blob = $setting['logo'] ?? ''; 

// Ambil data running text
$hasil_runtext = bukaquery("SELECT teks FROM runtextapotek WHERE aktifkan='Yes' LIMIT 1");
$runtext_data = mysqli_fetch_assoc($hasil_runtext);
$running_text = $runtext_data['teks'] ?? 'Selamat datang di Farmasi ' . $nama_instansi . '. Silakan menunggu panggilan.'; 
?>
<!doctype html>
<html lang="id">
<head>
    <title>Antrian Farmasi - <?php echo htmlspecialchars($nama_instansi); ?></title>
    
    <?php
    if (!empty($logo_blob)) {
        echo '<link rel="icon" href="data:image/png;base64,' . base64_encode($logo_blob) . '" type="image/x-icon">';
    } else {
        echo '<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🏥</text></svg>">';
    }
    ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
    
    <style>
        :root {
            --primary-color: #1976d2; /* Biru Tua */
            --primary-dark: #0d47a1;  /* Biru Sangat Tua */
            --primary-light: #e3f2fd; /* Biru Sangat Muda */
            --accent-color: #FF6D00;   /* Oranye */
            --text-dark: #37474f;
            --text-light: #546e7a;
            --bg-color: #eceff1;      /* Latar belakang biru-abu muda */
        }
        
        body {
            background-color: var(--bg-color);
            font-family: 'Roboto', Arial, sans-serif;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100vh;
            margin: 0;
        }
        
        /* --- HEADER --- */
        header {
            flex-shrink: 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            z-index: 10;
        }
        nav {
            background-color: var(--primary-color);
            height: 64px; 
            line-height: 64px;
        }
        .nav-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 15px;
            height: 100%;
        }
        .nav-logo {
            display: flex;
            align-items: center;
            font-size: 1.6em;
            color: white;
            max-width: 50%; 
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .nav-logo img {
            height: 40px;
            width: 40px;
            margin-right: 10px;
            border-radius: 4px;
            background-color: white;
            padding: 2px;
            flex-shrink: 0; 
        }
        .nav-logo span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .nav-datetime {
            display: flex;
            align-items: center;
            flex-shrink: 0; 
        }
        .nav-datetime .time-item {
            display: flex;
            align-items: center;
            color: white;
            font-size: 1.2em;
            margin-left: 20px;
        }
        .nav-datetime .time-item i {
            margin-right: 8px;
            font-size: 1.5rem; 
        }
        
        /* --- KOTAK PANGGILAN ATAS --- */
        #panggil_pasien_wrapper {
            flex-shrink: 0;
            padding: 2.5vh 2vw;
            text-align: center;
            background-color: #ffffff;
            border-bottom: 5px solid var(--primary-dark);
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            min-height: 25vh; 
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .panggil-box {
            animation: fadeIn 0.5s;
            width: 100%;
        }
        .panggil-box.standby {
             background-color: #ffffff;
        }
        .panggil-box h2 {
            margin: 0 0 1vh 0;
            font-size: 2.8em; 
            color: var(--primary-dark);
            font-weight: 700;
        }
        .panggil-box .nama-pasien {
            display: block;
            font-size: 4.5em; 
            font-weight: 700;
            color: var(--accent-color);
            margin: 1vh 0;
            animation: pulse 1.5s infinite;
        }
        .panggil-box.standby .nama-pasien {
            color: var(--text-light);
            animation: none;
            font-size: 3.5em; 
        }
        .panggil-box .detail-pasien {
            font-size: 2em; 
            color: var(--text-dark);
        }

        /* --- KONTENER ANTRIAN (MAIN) --- */
        main {
            flex-grow: 1;
            display: flex;
            width: 100%;
            overflow: hidden;
            padding: 0 10px 10px 10px;
            box-sizing: border-box;
        }

        /* --- TABEL KIRI & KANAN --- */
        .antrean-shell {
            width: 50%;
            background: #ffffff;
            border-radius: 8px;
            margin: 0 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .antrean-shell h3 {
            text-align: center;
            color: var(--primary-color);
            background-color: var(--primary-light);
            margin: 0;
            padding: 15px;
            border-bottom: 2px solid #bbdefb;
            flex-shrink: 0;
            font-size: 2.2em;
            font-weight: 500;
        }
        .table-container {
             overflow-y: auto;
             flex-grow: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 1.5vh 1vw;
            border-bottom: 1px solid #ddd;
            text-align: left;
            font-size: 1.1em; /* Ukuran font disesuaikan agar muat */
        }
        th {
            background-color: #f5f5f5;
            position: sticky;
            top: 0;
            font-weight: 500;
            color: #333;
        }
        tbody tr:nth-child(even) {
            background-color: #fafafa;
        }
        
        /* --- FOOTER RUNNING TEXT --- */
        footer {
            flex-shrink: 0;
            background-color: var(--primary-dark);
            color: white;
            padding: 10px;
            font-size: 1.5em;
            font-weight: 500;
            overflow: hidden;
            white-space: nowrap;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
        }
        footer marquee {
            width: 100%;
        }
        
        /* Animasi */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.03); }
            100% { transform: scale(1); }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="nav-wrapper">
                <a href="#!" class="nav-logo">
                    <?php
                    if (!empty($logo_blob)) {
                        echo '<img src="data:image/png;base64,' . base64_encode($logo_blob) . '" alt="Logo">';
                    }
                    ?>
                    <span><?php echo htmlspecialchars($nama_instansi); ?></span>
                </a>
                
                <div class="nav-datetime">
                    <div class="time-item">
                        <i class="material-icons">perm_contact_calendar</i>
                        <span id="tanggal"></span>
                    </div>
                    <div class="time-item">
                        <i class="material-icons">query_builder</i>  
                        <span id="jam"></span>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <div id="panggil_pasien_wrapper">
        <div class="panggil-box standby" id="panggil_pasien">
            <h2>ANTREAN FARMASI</h2>
            <span class="nama-pasien">Silakan Menunggu</span>
        </div>
    </div>
    
    <main>
        <div id="antrean_racikan_shell" class="antrean-shell">
             <h3>ANTREAN RACIKAN (SIAP)</h3>
             <div class="table-container" id="data_racikan">
                </div>
        </div>
        
        <div id="antrean_nonracikan_shell" class="antrean-shell">
             <h3>ANTREAN NON-RACIKAN (SIAP)</h3>
             <div class="table-container" id="data_nonracikan">
                </div>
        </div>
    </main>
    
    <footer>
        <marquee behavior="scroll" direction="left" scrollamount="6">
            <?php echo htmlspecialchars($running_text); ?>
        </marquee>
    </footer>
    
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    
    <script type="text/javascript">
       var lastCalledResepUnik = ""; 

       window.onload = function() { 
           updateTime(); 
           
           runListener();
           loadPanggilan();
           loadAntreanRacikan();
           loadAntreanNonRacikan();
           
           // Interval untuk listener trigger (Poin 4)
           setInterval(runListener, 3000); //
           // Interval untuk update box panggilan
           setInterval(loadPanggilan, 3000); //
           // Interval untuk update tabel antrean
           setInterval(function() {
               loadAntreanRacikan();
               loadAntreanNonRacikan();
           }, 7000); //
       }

       function updateTime() {
           var eJam = document.getElementById('jam');
           var eTgl = document.getElementById('tanggal');
           var d = new Date();
           
           var h = set(d.getHours()); //
           var m = set(d.getMinutes());
           var s = set(d.getSeconds());
           eJam.innerHTML = h +':'+ m +':'+ s; //
           
           var hariArray = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
           var bulanArray = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
           
           var hari = hariArray[d.getDay()]; //
           var tanggal = d.getDate(); //
           var bulan = bulanArray[d.getMonth()]; //
           var tahun = d.getFullYear(); //
           eTgl.innerHTML = hari + ", " + tanggal + " " + bulan + " " + tahun;
           
           setTimeout(updateTime, 1000); //
       }

       function set(e) {
           return e < 10 ? '0' + e : e; //
       }
       
       // Fungsi pemanggil suara (Poin 5)
       function panggilPasien(nama) {
           if ('speechSynthesis' in window) {
               window.speechSynthesis.cancel(); 
               // Teks panggilan dinamis
               var utterance = new SpeechSynthesisUtterance('Pasien atas nama ' + nama + ', silakan menuju Apotek.');
               utterance.lang = 'id-ID'; 
               utterance.rate = 0.9;     
               window.speechSynthesis.speak(utterance);
           }
       }

       // Fungsi listener (Poin 4)
       function runListener() {
           $.ajax({
               url: 'listener_panggilan.php', // Memanggil file listener
               cache: false,
               error: function(xhr, status, error) {
                   console.error("Gagal menjalankan listener: " + error);
               }
           });
       }

       // Fungsi pemuat box panggilan (Poin 5 & 6)
       function loadPanggilan() {
           $.ajax({
               url: 'get_panggilan_display.php',
               cache: false, 
               success: function(html) {
                   $('#panggil_pasien_wrapper').html(html); 
                   
                   // Logika untuk mencegah suara diputar berulang-ulang
                   if (typeof resepPanggilUnik !== 'undefined' && resepPanggilUnik !== "" && resepPanggilUnik !== lastCalledResepUnik) {
                       if(typeof namaPanggil !== 'undefined' && namaPanggil !== "") {
                             console.log("Memanggil: " + namaPanggil + " (ID: " + resepPanggilUnik + ")");
                             panggilPasien(namaPanggil); // Panggil suara
                       }
                       lastCalledResepUnik = resepPanggilUnik; // Simpan ID resep yang sudah dipanggil
                   } else if (typeof resepPanggilUnik !== 'undefined' && resepPanggilUnik === "") {
                       lastCalledResepUnik = ""; // Reset jika tidak ada panggilan
                   }
               },
               error: function(xhr, status, error) {
                   console.error("Gagal memuat panggilan: " + error);
                   // Tampilkan pesan error di box panggilan agar mudah di-debug
                   $('#panggil_pasien_wrapper').html('<div class="panggil-box standby"><span class="nama-pasien">Error: Gagal memuat panggilan.</span></div>');
               }
           });
       }
       
       // Fungsi pemuat tabel (Poin 7 & 8)
       function loadAntreanRacikan() {
           $('#data_racikan').load('get_antrean_racikan_display.php', function(response, status, xhr) {
               if (status == "error") {
                   $(this).html('<tr><td colspan="5" style="text-align:center; padding: 20px; color: red;">Gagal memuat data racikan: ' + xhr.statusText + '</td></tr>');
               }
           });
       }
       
       function loadAntreanNonRacikan() {
           $('#data_nonracikan').load('get_antrean_nonracikan_display.php', function(response, status, xhr) {
               if (status == "error") {
                   $(this).html('<tr><td colspan="5" style="text-align:center; padding: 20px; color: red;">Gagal memuat data non-racikan: ' + xhr.statusText + '</td></tr>');
               }
           });
       }
       
    </script>
</body>
</html>