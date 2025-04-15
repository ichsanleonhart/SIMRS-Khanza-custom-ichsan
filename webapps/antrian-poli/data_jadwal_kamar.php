 <?php
 require_once('conf/conf.php');
 header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
 header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT"); 
 header("Cache-Control: no-store, no-cache, must-revalidate"); 
 header("Cache-Control: post-check=0, pre-check=0", false);
 header("Pragma: no-cache"); // HTTP/1.0
 date_default_timezone_set("Asia/Bangkok");
 $tanggal= mktime(date("m"),date("d"),date("Y"));
 $jam=date("H:i");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Kamar</title>

    <!-- Same CSS and fonts as index.html -->
    <link rel="stylesheet" href="assets/css/rtl/core.css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/theme-default.css" />
    <link rel="stylesheet" href="assets/vendor/fonts/fontawesome.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans&display=swap" rel="stylesheet">

<style>
     body {
            font-family: 'Public Sans', sans-serif;
            font-size: 16px;
            color: #333;
            padding: 0px;
            margin: 0;
            background: transparent;
        }
        .highlight {
            color: #0d6efd;
            font-weight: bold;
        }

    .scroll-box {
        height: 100vh;
        overflow: hidden;
        position: relative;
    }
	
	#scroll-container {
    height: 100vh;
    overflow-y: hidden;
    position: relative;
}

#scroll-content {
    position: relative;
    padding-top: 100vh; /* Add space at top equal to viewport height */
    padding-bottom: 100px;
}

    @keyframes scroll-up {
    from {
        top: 100%;
    }
    to {
        top: -100%;
    }
}

    table.default {
        width: 100%;
        border-collapse: collapse;
        background: white;
        font-size: 24px;
    }

    table.default th, table.default td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ccc;
    }

    table.default th {
        background: #f2f2f2;
    }

    h5.center {
        font-size: 45px;
        margin-bottom: 20px;
    }
</style>
<body>

<!-- <div class="col s12 row" style="font-size:25px;"> -->

<!--<div class="scroll-box">-->
<div id="scroll-container">
    <div id="scroll-content">                
		<div class="card"> <h3 style="font-size:25px; margin-bottom: 1rem; margin-top: 0; font-weight: 600; line-height: 1.37; text-align:center; color: #5d596c;">JADWAL PRAKTEK DOKTER</h3> </div>
		<table class="default" style="font-size:15px; margin-bottom: 1rem; margin-top: 0; font-weight: 600; line-height: 1.37; text-align:center; color: #5d596c;">
            <thead>
                <tr>
                    <th><b>Nama Dokter</b></th>
                    <th><b>Poliklinik</b></th>
                    <th><b>Mulai</b></th>
                    <th><b>Selesai</b></th>
                </tr>
            </thead>
            <tbody>
                <?php  
                    $hari = getOne("select DAYNAME(current_date())");
                    $namahari = [
                        "Sunday"    => "AKHAD",
                        "Monday"    => "SENIN",
                        "Tuesday"   => "SELASA",
                        "Wednesday" => "RABU",
                        "Thursday"  => "KAMIS",
                        "Friday"    => "JUMAT",
                        "Saturday"  => "SABTU"
                    ][$hari];

                    $_sql = "SELECT dokter.nm_dokter, poliklinik.nm_poli, jadwal.jam_mulai, jadwal.jam_selesai 
                             FROM jadwal 
                             INNER JOIN dokter ON dokter.kd_dokter = jadwal.kd_dokter 
                             INNER JOIN poliklinik ON jadwal.kd_poli = poliklinik.kd_poli 
                             WHERE jadwal.hari_kerja = '$namahari'";
                    $hasil = bukaquery($_sql);

                    while ($data = mysqli_fetch_array($hasil)) {
                        echo "<tr>
                                <td><b>{$data['nm_dokter']}</b></td>
                                <td><b>{$data['nm_poli']}</b></td>
                                <td align='center'><b>{$data['jam_mulai']}</b></td>
                                <td align='center'><b>{$data['jam_selesai']}</b></td>
                              </tr>";
                    }
                ?>
            </tbody>
        </table>

			<div class="card"> <h3 style="font-size:25px; margin-bottom: 1rem; margin-top: 0; font-weight: 600; line-height: 1.37; text-align:center; color: #5d596c;">DAFTAR RUANG RAWAT INAP</h3> </div>
				<table class="default" style="font-size:15px; margin-bottom: 1rem; margin-top: 0; font-weight: 600; line-height: 1.37; text-align:center; color: #5d596c;">
                    <thead>
                      <tr>
                        <td align='left'><b>Kelas Kamar</b></td>
                        <td align='center'><b>Jumlah Bed</b></td>
                        <td align='center'><b>Bed Terisi</b></td>
                        <td align='center'><b>Bed Kosong</b></td>
                      </tr>
                    </thead>
                    <tbody>
					<?php
$kelasList = bukaquery("SELECT DISTINCT kelas FROM kamar WHERE statusdata='1' AND kd_bangsal NOT LIKE '%ICU%' AND kd_bangsal NOT LIKE '%HCU%' AND kd_bangsal NOT LIKE '%PRN%' AND kd_bangsal NOT LIKE '%ISO%'");

while ($data = mysqli_fetch_array($kelasList)) {
    $kelas = $data['kelas'];
    $jumlah = getOne("SELECT COUNT(kelas) FROM kamar WHERE statusdata='1' AND kelas='$kelas' AND kd_bangsal NOT LIKE '%ICU%' AND kd_bangsal NOT LIKE '%HCU%' AND kd_bangsal NOT LIKE '%PRN%' AND kd_bangsal NOT LIKE '%ISO%'");
    $isi = getOne("SELECT COUNT(kelas) FROM kamar WHERE statusdata='1' AND kelas='$kelas' AND status='ISI' AND kd_bangsal NOT LIKE '%ICU%' AND kd_bangsal NOT LIKE '%HCU%' AND kd_bangsal NOT LIKE '%PRN%' AND kd_bangsal NOT LIKE '%ISO%'");
    $kosong = getOne("SELECT COUNT(kelas) FROM kamar WHERE statusdata='1' AND kelas='$kelas' AND status='KOSONG' AND kd_bangsal NOT LIKE '%ICU%' AND kd_bangsal NOT LIKE '%HCU%' AND kd_bangsal NOT LIKE '%PRN%' AND kd_bangsal NOT LIKE '%ISO%'");

    echo "<tr>
            <td align='left'><b>$kelas</b></td>
            <td align='center'><b>$jumlah</b></td>
            <td align='center'><b>$isi</b></td>
            <td align='center'><b>$kosong</b></td>
        </tr>";
}
?>
				<tr>&nbsp <td>&nbsp</td> <td>&nbsp</td> <td>&nbsp</td> <td>&nbsp</td></tr> <tr>&nbsp <td>&nbsp</td> <td>&nbsp</td> <td>&nbsp</td> <td>&nbsp</td></tr> <tr>&nbsp <td>&nbsp</td> <td>&nbsp</td> <td>&nbsp</td> <td>&nbsp</td></tr> <tr>&nbsp <td>&nbsp</td> <td>&nbsp</td> <td>&nbsp</td> <td>&nbsp</td></tr> <tr>&nbsp <td>&nbsp</td> <td>&nbsp</td> <td>&nbsp</td> <td>&nbsp</td></tr> <tr>&nbsp <td>&nbsp</td> <td>&nbsp</td> <td>&nbsp</td> <td>&nbsp</td></tr> <tr>&nbsp <td>&nbsp</td> <td>&nbsp</td> <td>&nbsp</td> <td>&nbsp</td></tr> <tr>&nbsp <td>&nbsp</td> <td>&nbsp</td> <td>&nbsp</td> <td>&nbsp</td></tr> <tr>&nbsp <td>&nbsp</td> <td>&nbsp</td> <td>&nbsp</td> <td>&nbsp</td></tr> 
				
                    </tbody>
                </table>
				
    </div>





    </div>
</div>

<script>
let scrollContainer;
let scrollContent;
let scrollSpeed = 1;
let scrollInterval;

function scrollStep() {
    // Scroll up by subtracting from scrollTop
    scrollContainer.scrollTop += scrollSpeed;

    // Check if we've reached the top
    if (scrollContainer.scrollTop <= 0) {
        clearInterval(scrollInterval);
        setTimeout(() => {
            // Reset to bottom and start again
            scrollContainer.scrollTop = scrollContainer.scrollHeight - scrollContainer.clientHeight;
            scrollInterval = setInterval(scrollStep, 30);
        }, 3000); // pause 3 seconds at the top
    }
}

window.onload = function () {
    scrollContainer = document.getElementById('scroll-container');
    scrollContent = document.getElementById('scroll-content');
    
    // Initialize scroll position to the bottom
    //scrollContainer.scrollTop = scrollContainer.scrollHeight - scrollContainer.clientHeight;
    scrollContainer.scrollTop = 0; // Start at top
    // Start scrolling up
    scrollInterval = setInterval(scrollStep, 30);
};
</script>




</body>
</html>
      