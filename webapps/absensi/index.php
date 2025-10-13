<?php
 session_start();
 require_once('conf/command.php');
 require_once('../conf/conf.php');
 require_once('conf/paging.php');
 header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // date in the past
 header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
 header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
 header("Cache-Control: post-check=0, pre-check=0", false);
 header("Pragma: no-cache"); // HTTP/1.0
 $setting=  mysqli_fetch_array(bukaquery("select setting.nama_instansi,setting.alamat_instansi,setting.kabupaten,setting.propinsi,setting.kontak,setting.email,setting.logo from setting"));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php title();?></title>
    <script src="js/jquery.min.js"></script>
    <script src="js/webcam.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
      /* Override legacy template backgrounds/borders to let Tailwind theme take over */
      body, #container, #mainContent, #footer, #header, .headerBackground, #navcontainer,
      .t, .b, .l, .r, .bl, .br, .tl, .tr, .y,
      #post, .entry, .tbl_form, .tbl_form .head, .tbl_form .isi, .tbl_form .isi13 {
        background: transparent !important;
        background-image: none !important;
        border: none !important;
        box-shadow: none !important;
      }
      .tbl_form td, .tbl_form th { background: transparent !important; }
      /* Reset default links inside tables for better contrast */
      .tbl_form a { color: #38bdf8; }

      /* Global font and base colors */
      html, body { background-color: #0b1220; color: #e5e7eb; }
      body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; }
      h1, h2, h3, h4, h5 { color: #f1f5f9; }
      a { color: #7dd3fc; }
      a:hover { color: #bae6fd; }
      ::selection { background: #334155; color: #f8fafc; }

      /* Header and navigation layout fixes */
      #header { position: relative; z-index: 900; }
      #header .headerBackground { position: relative; z-index: 10; }
      #navcontainer { position: relative; z-index: 2000; clear: both; pointer-events: auto; }
      #navcontainer ul { display: flex; flex-wrap: wrap; gap: 0.5rem; padding: 0; margin: 0; list-style: none; }
      #navcontainer li { list-style: none; }
      #navcontainer a { 
        display: inline-block; 
        padding: 0.5rem 0.75rem; 
        border-radius: 0.5rem; 
        color: #e5e7eb; 
        text-decoration: none; 
        cursor: pointer; 
        position: relative; 
        z-index: 2001; 
        pointer-events: auto; 
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        transform: translateY(0);
      }
      #navcontainer a:hover { background: rgb(51 65 85 / 0.7); color: #ffffff; }
      #navcontainer a:active { transform: translateY(1px); }
      #navcontainer a:focus { outline: 2px solid #38bdf8; outline-offset: 2px; }
      #navcontainer { overflow-x: auto; }
      .clearfloat { display: none !important; }

      /* Form controls normalization (legacy classes -> Tailwind-like look) */
      input.text, input.text7, select.text, select.text7 {
        background-color: rgb(15 23 42 / 1);
        color: #e5e7eb;
        border: 1px solid rgb(51 65 85 / 1);
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        width: 100%;
        box-sizing: border-box;
      }
      input::placeholder { color: #94a3b8; }
      input.text:focus, input.text7:focus, select.text:focus, select.text7:focus {
        border-color: #38bdf8;
        box-shadow: 0 0 0 3px rgba(56, 189, 248, .25);
        outline: none;
      }
      .button {
        background-image: linear-gradient(135deg, #2563eb 0%, #1d4ed8 50%, #0ea5e9 100%);
        color: #fff !important;
        border: none !important;
        border-radius: 0.5rem;
        padding: 0.6rem 1rem;
        font-weight: 700;
        box-shadow: 0 10px 22px rgba(29, 78, 216, 0.35);
        cursor: pointer;
      }
      .button:hover { filter: brightness(1.05); }
      .button:disabled { background: #6b7280 !important; box-shadow: none; cursor: not-allowed; }

      /* Table polishing */
      /* Hide sorting symbols in table headers */
      .tbl_form a { text-decoration: none !important; }
      .tbl_form a:before, .tbl_form a:after { display: none !important; }
      
      .tbl_form {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        display: block;
        overflow-x: auto;
        border: 1px solid rgba(30,41,59,0.8);
        border-radius: 12px;
        background: rgba(2,6,23,0.35);
        animation: fadeInUp 0.6s ease-out;
      }
      .tbl_form .head td, .tbl_form .head th {
        position: sticky; top: 0; z-index: 5;
        background-color: rgba(30, 41, 59, 0.85) !important;
        backdrop-filter: blur(4px);
        color: #e2e8f0;
        font-weight: 700;
        text-transform: none;
      }
      .tbl_form td, .tbl_form th {
        padding: 0.75rem 0.9rem;
        border-bottom: 1px solid rgba(30,41,59,1);
      }
      .tbl_form tr.isi:nth-child(even) td { background: rgba(30,41,59,0.35) !important; }
      .tbl_form tr.isi:hover td { 
        background: rgba(51,65,85,0.55) !important; 
        transform: scale(1.01);
        transition: all 0.2s ease;
      }
      
      /* Page transition animations */
      @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
      }
      
      @keyframes slideIn {
        from { opacity: 0; transform: translateX(-10px); }
        to { opacity: 1; transform: translateX(0); }
      }
      
      #mainContent > div { animation: slideIn 0.4s ease-out; }

      /* Inner page wrapper becomes flat so we don't get double cards */
      #post { background: transparent !important; border: none !important; border-radius: 0; padding: 0; }
      .title { margin-bottom: 0.75rem; }
      .entry { margin-top: 0.5rem; }

      /* Ensure legacy wrapper blocks don't force light backgrounds or spacing */
      .t, .b, .l, .r, .bl, .br, .tl, .tr, .y { padding: 0 !important; margin: 0 !important; }
    </style>
    
    <style type="text/css">
        #results { padding: 0px; background:#EEFFEE; width: 490; height: 390 }
    </style>
    
    
</head>
<!--This template was created by www.flash-templates-today.com
Flash-Templates-Today.com - Gives a possibility to obtain a ready free flash template, free css template and other kind of website template!-->
<body class="bg-slate-900 text-slate-100">
<!-- begin #container -->
<div id="container" class="min-h-screen">
    <!-- begin #header -->
    <div id="header" class="bg-gradient-to-r from-slate-900 to-slate-800 border-b border-slate-700 shadow">
    	<div class="headerBackground max-w-7xl mx-auto px-4 py-4 flex items-center gap-4">
            <div class="logo">
              <?php if(isset($setting['logo'])){ ?>
                <img src="<?php echo 'data:image/jpeg;base64,'.base64_encode($setting['logo']); ?>" alt="Logo" style="width:56px;height:56px;border-radius:12px;border:1px solid rgba(148,163,184,.3);background:rgba(15,23,42,.6);padding:6px;" />
              <?php } ?>
            </div>
            <div class="heading">
              <h1 class="text-xl md:text-2xl font-extrabold tracking-tight text-white"><?php echo $setting["nama_instansi"]; ?></h1>
              <p class="text-xs md:text-sm text-slate-300">SISTEM INFORMASI PRESENSI PEGAWAI</p>
            </div>
        </div>
         <!-- Disabled menu
          <div id="navcontainer" class="bg-slate-800/70">
             <div class="max-w-7xl mx-auto px-4 py-2">
               <?php tampilMenu();?>
             </div>
          </div>
         -->
         
    </div>
    <!-- end #header -->
    <!-- begin #sidebar1 -->

    <!-- end #sidebar1 -->
    <!-- begin #mainContent -->
    <div id="mainContent" class="max-w-7xl mx-auto px-4 py-6">
        <div class="p-0">
         <?php
           // Navigation menu disabled. Original router kept below for easy re-enable.
           /*
           $halaman= validTeks(isset($_GET["page"]) ? $_GET["page"] : NULL);
           if($halaman=="Input"){
               include "inputdata.php";
           }elseif($halaman=="TampilDatang"){
               include "tampildatang.php";
           }elseif($halaman=="TampilPulang"){
               include "tampilpulang.php";
           }elseif($halaman=="GantiKeterangan"){
               include "ubah.php";
           }elseif($halaman=="Cari"){
               include "cari.php";
           }else{
               include "inputdata.php";
           }
           */
           include "inputdata.php";
        ?> 
        </div>
    <!-- end #mainContent -->
    <!-- This clearing element should immediately follow the #mainContent div in order to force the #container div to contain all child floats --><br class="clearfloat" />
    <!-- begin #footer -->
    <div id="footer" class="border-t border-slate-800 mt-8">
        <p class="max-w-6xl mx-auto px-4 py-6 text-xs text-slate-400">
        <?php bawah();?>
        </p>
    </div>
    <!-- end #footer -->
</div>
<!-- end #container -->
</body>
</html>


