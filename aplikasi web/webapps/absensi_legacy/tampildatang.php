

<div id="post" class="bg-slate-900/60 rounded-xl border border-slate-800 shadow p-4">
    <h1 class="title text-xl md:text-2xl font-extrabold tracking-tight text-white">:: Presensi Datang ::</h1>
    <div class="entry">    
        <form name="frm_aturadmin" onsubmit="return validasiIsi();" method="post" enctype=multipart/form-data class="mt-3">
        <?php
                $keyword      =cleankar(isset($_GET['keyword'])?$_GET['keyword']:NULL);
                echo "<input type=hidden name=keyword value=$keyword>";
        ?>
            <table width="100%" align="center">
                <tr class="head">
                    <td width="20%" class="text-slate-300 font-semibold">Keyword</td><td width="" class="text-slate-500">:</td>
                    <td width="80%"><input name="keyword" class="text bg-slate-800 text-slate-100 border border-slate-700 rounded-md px-3 py-2 w-full" onkeydown="setDefault(this, document.getElementById('MsgIsi1'));" type=text id="TxtIsi1" value="<?php echo $keyword;?>" size="50" maxlength="250" pattern="[a-zA-Z0-9, ./@_]{1,200}" title=" a-zA-Z0-9, ./@_ (Maksimal 200 karakter)" autocomplete="off"/></td>
                </tr>  
            </table>
              <div align="center" class="mt-3"><input name=BtnCari type=submit class="button" value="Cari"/></div>
            <br/>
            
            
    <div style="width: 100%; height: 350px; overflow: auto; ">

    <?php
       
        $order=cleankar(isset($_GET['order']))?cleankar($_GET['order']):NULL; 
        if (empty($order)) $order=1;
        
        $urut="pegawai.nik asc";
        if($order==1){
            $urut="pegawai.nik asc";
        }elseif($order==2){
            $urut="pegawai.nik desc";
        }elseif($order==3){
            $urut="pegawai.nama asc";
        }elseif($order==4){
            $urut="pegawai.nama desc";
        }elseif($order==5){
            $urut="temporary_presensi.shift asc";
        }elseif($order==6){
            $urut="temporary_presensi.shift desc";
        }elseif($order==7){
            $urut="temporary_presensi.jam_datang asc";
        }elseif($order==8){
            $urut="temporary_presensi.jam_datang desc";
        }elseif($order==9){
            $urut="temporary_presensi.jam_pulang asc";
        }elseif($order==10){
            $urut="temporary_presensi.jam_pulang desc";
        }elseif($order==11){
            $urut="temporary_presensi.status asc";
        }elseif($order==12){
            $urut="temporary_presensi.status desc";
        }elseif($order==13){
            $urut="temporary_presensi.keterlambatan asc";
        }elseif($order==14){
            $urut="temporary_presensi.keterlambatan desc";
        }elseif($order==15){
            $urut="temporary_presensi.durasi asc";
        }elseif($order==16){
            $urut="temporary_presensi.durasi desc";
        }
        
        $keyword= trim(isset($_POST['keyword']))?trim($_POST['keyword']):NULL;
        $keyword= validTeks($keyword);
        $_sql = "SELECT pegawai.id, pegawai.nik, pegawai.nama, temporary_presensi.shift,
                temporary_presensi.jam_datang, temporary_presensi.jam_pulang, temporary_presensi.status, 
                temporary_presensi.keterlambatan, temporary_presensi.durasi, temporary_presensi.photo  from pegawai 
                inner join temporary_presensi on pegawai.id=temporary_presensi.id                 
                where  pegawai.nik like '%".$keyword."%' or
                pegawai.nama like '%".$keyword."%' or
                temporary_presensi.shift like '%".$keyword."%' or
                temporary_presensi.jam_datang like '%".$keyword."%' or
                temporary_presensi.status like '%".$keyword."%' or
                temporary_presensi.keterlambatan like '%".$keyword."%' 
                order by $urut ";                 

       
        $hasil=bukaquery($_sql);
        $jumlah=mysqli_num_rows($hasil);
        
        if(mysqli_num_rows($hasil)!=0) { 
            $i=1;
            
            echo "<table width='1500px' border='0' align='center' cellpadding='0' cellspacing='0' class='tbl_form text-slate-200'>
                    <tr class='head bg-slate-800/60'>
                        <td width='20px'><div align='center'><strong>No</strong></div></td>
                        <td width='100px'><div align='center'><strong> <a class=\"text-sky-400\" href='?page=TampilDatang&order=1'>  </a> NIK<a class=\"text-sky-400\" href='?page=TampilDatang&order=2'>  </a> </strong></div></td>
                        <td width='200px'><div align='center'><strong> <a class=\"text-sky-400\" href='?page=TampilDatang&order=3'>  </a> Nama <a class=\"text-sky-400\" href='?page=TampilDatang&order=4'>  </a> </strong></div></td>
                        <td width='70px'><div align='center'><strong> <a class=\"text-sky-400\" href='?page=TampilDatang&order=5'>  </a> Shift <a class=\"text-sky-400\" href='?page=TampilDatang&order=6'>  </a> </strong></div></td>
                        <td width='100px'><div align='center'><strong> <a class=\"text-sky-400\" href='?page=TampilDatang&order=7'>  </a> Jam Datang <a class=\"text-sky-400\" href='?page=TampilDatang&order=8'>  </a> </strong></div></td>
                        <td width='100px'><div align='center'><strong> <a class=\"text-sky-400\" href='?page=TampilDatang&order=9'>  </a> Jam Pulang <a class=\"text-sky-400\" href='?page=TampilDatang&order=10'>  </a> </strong></div></td>
                        <td width='100px'><div align='center'><strong> <a class=\"text-sky-400\" href='?page=TampilDatang&order=11'>  </a> Status <a class=\"text-sky-400\" href='?page=TampilDatang&order=12'>  </a> </strong></div></td>
                        <td width='100px'><div align='center'><strong> <a class=\"text-sky-400\" href='?page=TampilDatang&order=13'>  </a> Keterlambatan<a class=\"text-sky-400\" href='?page=TampilDatang&order=14'>  </a> </strong></div></td>
                        <td width='100px'><div align='center'><strong> <a class=\"text-sky-400\" href='?page=TampilDatang&order=15'>  </a> Durasi <a class=\"text-sky-400\" href='?page=TampilDatang&order=16'>  </a> </strong></div></td>
                        <td width='100px'><div align='center'><strong> Photo </strong></div></td>
                    </tr>";
                    while($baris = mysqli_fetch_array($hasil)) {            
                        $gb="-";
                        if($baris[9]=="pages/pegawai/"){
                            $gb="-";                            
                        }else{
                            $gb="<img src='$baris[9]' width='120px' height='120px'>";
                        }
                        echo "<tr class='isi' title='$baris[0], $baris[1], $baris[2], $baris[3], $baris[4], $baris[5], $baris[6], $baris[7]'>
                                <td valign='Top'>$i</td>
                                <td valign='Top'>$baris[1]</a></td>
                                <td valign='Top'>$baris[2]</a></td>   
                                <td valign='Top'>$baris[3]</a></td>         
                                <td valign='Top'>$baris[4]</a></td>         
                                <td valign='Top'>$baris[5]</a></td>         
                                <td valign='Top'>$baris[6]</a></td>         
                                <td valign='Top'>$baris[7]</a></td>         
                                <td valign='Top'>$baris[8]</a></td>         
                                <td valign='Top' align='center'>$gb</a></td>                          
                             </tr>";$i++;
                    }
            echo "</table>";
            
        } else {echo "<b>Data Presensi Masuk masih kosong !</b>";}

    ?>   
    </div>
              
 </form>
    <?php
        if(mysqli_num_rows($hasil)!=0) {
            echo("Data : $jumlah ");
        }
    ?>
    </div>
</div>

