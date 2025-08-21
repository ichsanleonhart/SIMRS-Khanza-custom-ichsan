<?php
session_start();
require_once('conf/conf.php');
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache"); // HTTP/1.0
$tanggal = mktime(date("m"), date("d"), date("Y"));
date_default_timezone_set('Asia/Jakarta');
$jam = date("H:i");
?>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link href="css/default.css" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="conf/validator.js"></script>
    <title>Jadwal Praktek Dokter</title>
    <script src="Scripts/AC_RunActiveContent.js" type="text/javascript"></script>
    <script src="Scripts/AC_ActiveX.js" type="text/javascript"></script>
    <style type="text/css">
        <!--
        body {
            background-image: url();
            background-repeat: no-repeat;
            background-color: #FFFFCC;
        }
        -->
    </style>
</head>

<body>

    <div align="left">
        <script type="text/javascript">
            AC_AX_RunContent('width', '32', 'height', '32'); //end AC code
        </script>
        <noscript>
            <object width="32" height="32">
                <embed width="32" height="32"></embed>
            </object>
        </noscript>
        <?php
        $token = trim(isset($_GET['iyem'])) ? trim($_GET['iyem']) : NULL;
        $token = json_decode(encrypt_decrypt($token, "d"), true);
        $kd_poli = "";
        $kd_dokter = "";
        if (isset($token["kd_poli"])) {
            $kd_poli = $token["kd_poli"];
            $kd_dokter = $token["kd_dokter"];
        } else {
            exit(header("Location: https://www.google.com"));
        }

        $kd_poli = validTeks4($kd_poli, 20);
        $kd_dokter = validTeks4($kd_dokter, 20);

        $setting = mysqli_fetch_array(bukaquery("select setting.nama_instansi,setting.alamat_instansi,setting.kabupaten,setting.propinsi,setting.kontak,setting.email,setting.logo from setting"));

        // Ambil data poli dan dokter untuk speech
        $nama_poli = getOne("select nm_poli from poliklinik where kd_poli='" . $kd_poli . "'");
        $nama_dokter = getOne("select nm_dokter from dokter where kd_dokter='" . $kd_dokter . "'");

        echo "   
           <table width='100%' align='center' border='0' class='tbl_form' cellspacing='0' cellpadding='0'>
                  <tr>
                        <td  width='10%' align='right' valign='center'>
                                <img width='90' height='90' src='data:image/jpeg;base64," . base64_encode($setting['logo']) . "'/>
                        </td>
                        <td>
                           <center>
                                  <font size='6' color='#AA00AA' face='Tahoma'>" . $setting["nama_instansi"] . "</font><br>
                                  <font size='5' color='#AA00AA' face='Tahoma'>
                                          " . $setting["alamat_instansi"] . ", " . $setting["kabupaten"] . ", " . $setting["propinsi"] . "<br>   
                                  </font> 
                                  <font size='5' color='#AAAA00' face='Tahoma' >Antrian Poli " . $nama_poli . ", Dokter " . $nama_dokter . "<br> " . date("d-M-Y", $tanggal) . "  " . $jam . "</font>
                                  <br><br>
                           </center>
                        </td>   
                        <td  width='10%' align='left'>
                                &nbsp;
                        </td>  
                        <td  width='10%' align='left' valign='top'>
                                <img width='180' height='130' src='header-kanan.jpg'/>
                        </td>                                                          
                 </tr>
          </table> ";
        ?>
        <table width='100%' bgcolor='FFFFFF' border='0' align='center' cellpadding='0' cellspacing='0'>
            <tr class='head5'>
                <td width='100%'>
                    <div align='center'></div>
                </td>
            </tr>
        </table>
        <table border='0' witdh='100%' cellpadding='0' cellspacing='0'>
            <tr class='head2' border='0'>
                <td width='35%' align='center'>
                    <font size='6' color='#DD0000'><b>Panggilan Poli</b></font>
                </td>
                <td>
                    <font size='6' color='#DD0000'><b>:</b></font>
                </td>
                <td width='64%' align='center'>
                    <?php
                    $_sql = "select * from antripoli where antripoli.kd_poli='" . $kd_poli . "' and antripoli.kd_dokter='" . $kd_dokter . "'";
                    $hasil = bukaquery($_sql);
                    $should_speak = false;
                    $speech_text = "";
                    $current_panggilan = "";

                    while ($data = mysqli_fetch_array($hasil)) {
                        $panggilan_data = getOne("select concat(reg_periksa.no_reg,' ',reg_periksa.no_rawat,' ',pasien.nm_pasien) from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis where reg_periksa.no_rawat='" . $data['no_rawat'] . "'");
                        $current_panggilan = $panggilan_data;
                        echo "<font size='6' color='#DD0000'><b>" . $panggilan_data . "</b></font>";

                        // Cek apakah ada panggilan dan belum dipanggil (status = 0)
                        if (!empty($panggilan_data) && $data['status'] == "0") {
                            echo "<audio autoplay='true' src='bell.wav'>";

                            // Update status ke 1 (sudah dipanggil)
                            bukaquery2("update antripoli set antripoli.status='1' where antripoli.kd_poli='" . $kd_poli . "' and antripoli.kd_dokter='" . $kd_dokter . "' and antripoli.no_rawat='" . $data['no_rawat'] . "'");

                            // Persiapkan teks untuk speech
                            $should_speak = true;
                            $panggilan_parts = explode(' ', $panggilan_data, 3);
                            $no_antrian = isset($panggilan_parts[0]) ? $panggilan_parts[0] : '';
                            $nama_pasien = isset($panggilan_parts[2]) ? $panggilan_parts[2] : '';

                            // Ubah nama pasien ke lowercase untuk pronunciation yang lebih natural
                            $nama_pasien_lower = strtolower($nama_pasien);

                            $speech_text = "Antrian Poli " . $nama_poli . " " . $nama_dokter . " Nomor Antrian " . $no_antrian . " " . $nama_pasien_lower;
                        }
                    }
                    ?>
                </td>
            </tr>
            </tr>
        </table>
        <table width='100%' bgcolor='FFFFFF' border='0' align='center' cellpadding='0' cellspacing='0'>
            <tr class='head4'>
                <td width='10%'>
                    <div align='center'>
                        <font size='5'><b>NO</b></font>
                    </div>
                </td>
                <td width='25%'>
                    <div align='center'>
                        <font size='5'><b>NO.RAWAT</b></font>
                    </div>
                </td>
                <td width='65%'>
                    <div align='center'>
                        <font size='5'><b>NAMA PASIEN</b></font>
                    </div>
                </td>
            </tr>
            <?php
            $_sql = "select reg_periksa.no_reg,reg_periksa.no_rawat,pasien.nm_pasien 
                       from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis
                       where reg_periksa.kd_poli='" . $kd_poli . "' and reg_periksa.kd_dokter='" . $kd_dokter . "' 
                       and reg_periksa.tgl_registrasi='" . date("Y-m-d", $tanggal) . "' and stts='Belum' order by reg_periksa.no_reg";
            $hasil = bukaquery($_sql);

            while ($data = mysqli_fetch_array($hasil)) {
                echo "<tr class='isi7' >
                                <td align='center'><font size='5' color='#555555' face='Tahoma'>" . $data['no_reg'] . "</font></td>
                                <td align='center'><font color='#555555' size='5'  face='Tahoma'>" . $data['no_rawat'] . "</font></td>
                                <td align='center'><font color='#555555' size='5'  face='Tahoma'>" . $data['nm_pasien'] . "</font></td>
                            </tr> ";
            }
            ?>
        </table>
        <table width='100%' bgcolor='FFFFFF' border='0' align='center' cellpadding='0' cellspacing='0'>
            <tr class='head5'>
                <td width='100%'>
                    <div align='center'></div>
                </td>
            </tr>
        </table>
        <img src="ft-2.jpg" alt="bar-pic" width="100%" height="83">

        <!-- Tombol Manual Speech -->
        <div style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
            <button onclick="manualCall()" style="
        background: #007bff; 
        color: white; 
        border: none; 
        padding: 15px 20px; 
        border-radius: 8px; 
        font-size: 16px; 
        font-weight: bold; 
        cursor: pointer; 
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
    " onmouseover="this.style.background='#0056b3'" onmouseout="this.style.background='#007bff'">
                🔊 Panggil Manual
            </button>
        </div>

        <!-- Web Speech API Script -->
        <script>
            function speakText(text) {
                // Cek apakah browser mendukung Web Speech API
                if ('speechSynthesis' in window) {
                    // Hentikan speech yang sedang berjalan
                    window.speechSynthesis.cancel();

                    // Buat utterance baru
                    const utterance = new SpeechSynthesisUtterance(text);

                    // Atur properti speech
                    utterance.lang = 'id-ID'; // Bahasa Indonesia
                    utterance.rate = 0.8; // Kecepatan bicara (0.1 - 10)
                    utterance.pitch = 1; // Nada suara (0 - 2)
                    utterance.volume = 1; // Volume (0 - 1)

                    // Jalankan speech
                    window.speechSynthesis.speak(utterance);
                } else {
                    alert('Browser tidak mendukung Web Speech API');
                }
            }

            // Fungsi panggil manual
            function manualCall() {
                <?php if (isset($current_panggilan) && !empty($current_panggilan)): ?>
                    var currentCall = "<?php echo addslashes($current_panggilan); ?>";

                    // Parse data panggilan
                    var parts = currentCall.split(' ');
                    var noAntrian = parts[0] || '';
                    var namaPasien = parts.slice(2).join(' ').toLowerCase() || '';

                    var speechText = "Antrian Poli <?php echo addslashes($nama_poli); ?>     <?php echo addslashes($nama_dokter); ?> Nomor Antrian " + noAntrian + " " + namaPasien;

                    speakText(speechText);
                <?php else: ?>
                    speakText('Tidak ada panggilan saat ini');
                <?php endif; ?>
            }

            // Fungsi untuk menjalankan speech jika ada panggilan baru
            <?php if (isset($should_speak) && $should_speak && !empty($speech_text)): ?>
                window.addEventListener('load', function () {
                    // Delay sedikit untuk memastikan audio bell selesai
                    setTimeout(function () {
                        speakText("<?php echo addslashes($speech_text); ?>");
                    }, 2000); // Delay 2 detik
                });
            <?php endif; ?>
        </script>

</body>
<?php
echo "<meta http-equiv='refresh' content='10;URL=?iyem=" . encrypt_decrypt("{\"kd_poli\":\"" . $kd_poli . "\",\"kd_dokter\":\"" . $kd_dokter . "\"}", "e") . "'>";
?>