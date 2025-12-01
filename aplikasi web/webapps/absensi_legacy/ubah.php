<div class="t">
<div class="b">
<div class="l">
<div class="r">
<div class="bl">
<div class="br">
<div class="tl">
<div class="tr">
<div class="y">

<div id="post" class="bg-slate-900/60 rounded-xl border border-slate-800 shadow p-4">
    <h1 class="title text-xl md:text-2xl font-extrabold tracking-tight text-white">::[ Input  Keterangan]::</h1>
    <div class="entry">
        <form name="frm_ruang" onsubmit="return validasiIsi();" method="post" action="" enctype=multipart/form-data class="mt-3">
            <?php
                echo "";
                $action     = isset($_GET['action'])?$_GET['action']:NULL;
                $id         = validTeks(isset($_GET['id'])?str_replace("_"," ",$_GET['id']):NULL);
                $jam_datang = validTeks2(isset($_GET['jam_datang'])?str_replace("_"," ",$_GET['jam_datang']):NULL);
				$keterangan = "";
                if($action == "TAMBAH"){
                    $id      = validTeks($_GET['id']);
                }else if($action == "UBAH"){
                    $_sql         = "SELECT id, keterangan FROM rekap_presensi WHERE id='$id' and jam_datang='$jam_datang'";
                    $hasil        = bukaquery($_sql);
                    while($baris  = mysqli_fetch_row($hasil)){
                        $id           = $baris[0];
                        $keterangan   = $baris[1];
                    }
                        
                }
                echo"<input type='hidden' name='id' value='$id'><input type=hidden name='jam_datang' value='$jam_datang'><input type='hidden' name='action' value='$action'>";
                
            ?>
            <table width="100%" align="center">
                <tr class="head">
                    <td width="31%" class="text-slate-300 font-semibold">Keterangan </td><td width="" class="text-slate-500">:</td>
                    <td width="67%"><input name="keterangan" class="text bg-slate-800 text-slate-100 border border-slate-700 rounded-md px-3 py-2 w-full" onkeydown="setDefault(this, document.getElementById('MsgIsi2'));" type=text id="TxtIsi2" class="inputbox" value="<?php echo $keterangan;?>" size="40" maxlength="100"/>
                    <span id="MsgIsi2" style="color:#CC0000; font-size:10px;"></span>
                    </td>
                </tr>
            </table>
            <div align="center" class="mt-3"><input name=BtnSimpan type=submit class="button" value="Simpan"/>&nbsp<input name=BtnKosong type=reset class="button" value="Kosong"/></div><br/>
            <?php
                $BtnSimpan=isset($_POST['BtnSimpan'])?$_POST['BtnSimpan']:NULL;
                if (isset($BtnSimpan)) {
                    $id           = validTeks(trim($_POST['id']));
                    $keterangan   = validTeks(trim($_POST['keterangan']));    
                    $jam_datang   = validTeks(trim($_POST['jam_datang']));   
				    if ((!empty($id))&&(!empty($keterangan))) {
                                Ubah(" rekap_presensi "," keterangan='$keterangan'  WHERE id='$id' and jam_datang='$jam_datang'  ", " Keterangan ");
                                echo"<html><head><title></title><meta http-equiv='refresh' content='1;URL=?page=TampilPulang'></head><body></body></html>";
                    }else if (empty($id)||empty($keterangan)){
                        echo '<b>Semua field harus isi..!!</b>';
                    }
                }
            ?>
        </form>
    </div>
</div>

</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>