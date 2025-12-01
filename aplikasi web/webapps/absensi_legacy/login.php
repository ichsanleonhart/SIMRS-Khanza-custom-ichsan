<?php
    include_once "conf/command.php";
    include_once "conf/conf.php";
    if (isset($_GET['act']) && ($_GET['act']=="login")){
        $sql = "SELECT nip,usere,passwordte,type FROM user WHERE usere='".validTeks($_POST['usere'])."' AND passwordte=aes_encrypt('". validTeks($_POST['passwordte'])."','windi')";
        $hasil=bukaquery($sql);
        $baris=mysqli_fetch_row($hasil);

        $nip            = $baris[0];
        $usere          = $baris[1];
        $passwordte     = $baris[2];
        $type           = $baris[3];

        $hasil=bukaquery($sql);
        $baris=mysqli_fetch_row($hasil);
        if (JumlahBaris($hasil)==0) {
            $sql2   = "SELECT pegawai.id,user.password FROM user inner join pegawai
                on pegawai.id=user.id
                where pegawai.nik='".validTeks($_POST['usere'])."' AND 
                user.password=aes_encrypt('".validTeks($_POST['passwordte'])."','windi')";
            $hasil2  = bukaquery($sql2);
            $baris2  = mysqli_fetch_row($hasil2);

            $nip     = $baris2[0];

            $hasil2=bukaquery($sql2);
            $baris2=mysqli_fetch_row($hasil2);
            if (JumlahBaris($hasil2)==0) {
                header("Location:index.php");
            }else{
                session_start();
                HapusAll(" sesion ");
                InsertData(" sesion ","'$nip'");
                $ses_pegawai = $hasil2[0];
                session_register("ses_pegawai");
                $url = "index.php?act=HomeAdmin";                            
                header("Location:".$url);
            }   
        } else {
             session_start();
             HapusAll(" sesion ");
             InsertData(" sesion ","'$nip'");
             if($type=='ADMIN'){
                 $ses_admin = $hasil[0];
                 session_register("ses_admin");
                 $url = "index.php?act=HomeAdmin";
             }
            header("Location:".$url);
        }
    }

    
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Presensi</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif}</style>
</head>
<body class="min-h-screen bg-slate-900 text-slate-100 flex items-center justify-center p-4">
  <div class="w-full max-w-md">
    <div class="bg-slate-800/60 border border-slate-700 rounded-2xl shadow-xl p-6">
      <h1 class="text-2xl font-extrabold tracking-tight text-white">Masuk Presensi</h1>
      <p class="text-slate-400 text-sm mt-1">Gunakan NIK atau Username dan kata sandi Anda</p>
      <form class="mt-6" method="post" action="?act=login">
        <label class="block text-sm font-medium text-slate-300">Username / NIK</label>
        <input type="text" name="usere" autocomplete="username" class="mt-1 w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500" required />
        <label class="block text-sm font-medium text-slate-300 mt-4">Kata Sandi</label>
        <input type="password" name="passwordte" autocomplete="current-password" class="mt-1 w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500" required />
        <button type="submit" class="mt-6 w-full bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 text-white font-bold py-2.5 rounded-lg shadow-lg">Masuk</button>
      </form>
    </div>
    <p class="text-center text-xs text-slate-500 mt-4">© <?php echo date('Y'); ?> Sistem Presensi</p>
  </div>
</body>
</html>
