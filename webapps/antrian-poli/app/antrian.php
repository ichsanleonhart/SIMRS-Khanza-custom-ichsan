<?php
/**
 * -----------------------------------------------------------------------------
 * DISCLAIMER:
 * -----------------------------------------------------------------------------
 * File ini adalah hasil pengembangan oleh [MUHAMMAD NURYAHYA] dan hanya boleh 
 * digunakan oleh pihak yang telah mendapatkan izin langsung dari saya. Setiap 
 * penggunaan file ini tanpa izin yang sah akan mengharuskan pengguna untuk 
 * mendapatkan persetujuan terlebih dahulu. Penggunaan file ini harus sesuai 
 * dengan ketentuan dan persyaratan yang telah ditetapkan.
 * 
 * **Peringatan**: Dilarang menghapus, memodifikasi, atau mengubah bagian 
 * disclaimer ini dalam bentuk apapun. Jika file ini digunakan, bagian 
 * disclaimer ini harus tetap utuh dan tidak boleh dihapus atau diubah.
 * 
 * Silakan kembangkan sesuai kebutuhan Anda. Jika ada modifikasi, Anda 
 * dipersilakan untuk mengirimkan hasilnya melalui email ke 
 * arrayazman@gmail.com agar kita bisa saling mengembangkan, 
 * 
 * Tidak boleh dijual atau dikomersilkan.
 * -----------------------------------------------------------------------------
 * DONASI:
 * -----------------------------------------------------------------------------
 * Jika Anda ingin mendukung pengembangan lebih lanjut dari proyek ini, 
 * Anda dapat melakukan donasi melalui rekening bank saya sebagai berikut:
 * 
 * BANK  : BCA
 * a/n   : MUHAMMAD NURYAHYA 
 * REK   : 6695446563
 * WA    : 0851-6299-3322
 * Tele  : https://t.me/arrayazman
 * Email : arrayazman@gmail.com
 * 
 * Terima kasih atas dukungan Anda!
 * -----------------------------------------------------------------------------
 */
require_once('../conf/conf.php');
date_default_timezone_set('Asia/Jakarta');

 
if(isset($_GET['p'])) {	 

//kode poli yang ingin ditampilkan
$poli="'ANA', 'BED', 'OBG','INT'";
//jam reset antrian
$jamreset='23:00:00';


  
switch($_GET['p']){	

   
   case 'pengaturan':
   $_sql ="select nama_instansi,email from setting";
   $hasil=bukaquery($_sql);
   $data = array();
   while ($r = mysqli_fetch_array ($hasil)){
      $r['text'] ="Selamat datang || Silakan menunggu sampai dipanggil oleh perawat. Untuk pasien yang baru mendaftar, silakan menuju nurse station untuk dilakukan pemeriksaan awal keperawatan untuk pengecekan tensi (tekanan darah), berat badan dan keluhan yang dirasakan";
      $data = $r;
   }  
   echo json_encode($data);
   break;


   
   case 'panggil' :

    
  $_sql1="SELECT * from antriadmisi WHERE status = '1' and jenis='kasir' order by waktu asc";  
  $hasil1=bukaquery($_sql1); 

  $_sql2="SELECT * from antriadmisi WHERE status = '1' and jenis='loket' order by waktu asc";  
  $hasil2=bukaquery($_sql2); 

  
  if(mysqli_num_rows($hasil1)>0){

    $_sql="SELECT a.norm,a.status,b.nm_pasien,a.kd_loket,concat(a.jenis) as nm_poli FROM antriadmisi a
    INNER JOIN 
        pasien b ON a.norm = b.no_rkm_medis
  WHERE a.status = '1' order by a.waktu asc LIMIT 1";  
  
        $hasil=bukaquery($_sql);
        $data = array();
        while ($r = mysqli_fetch_array ($hasil)){
          
        //tambahkan lagi yang ingin di replace            
		$awalnama = array("Tn.", "BY.", "Ny.", ", NY",", TN");
        $replacenama = array("Tuan ", "Bayi ", "Nyonya ","..","..");
  
        $awalpoli= array("THT", "Obgyn");
        $replacepoli= array("T H T", "Objin");
        
        $r['nm_poli']=str_replace($awalpoli,$replacepoli,$r['nm_poli']);      
        $r['nm_pasien']=str_replace($awalnama,$replacenama,$r['nm_pasien']);      
        $data[] = $r;
        
        bukaquery2("UPDATE antriadmisi SET status = '3' WHERE status='2'");
        bukaquery2("UPDATE antriadmisi SET status = '2' WHERE norm = '$r[norm]' and kd_loket='$r[kd_loket]' ");
        } 
        echo json_encode($data);

         } else if(mysqli_num_rows($hasil2)>0){
       
           $_sql="SELECT a.norm,a.status,b.nm_pasien,a.kd_loket,a.jenis as nm_poli,a.kd_loket FROM antriadmisi a
           INNER JOIN 
               pasien b ON a.norm = b.no_rkm_medis
         WHERE a.status = '1' order by a.waktu asc LIMIT 1";  
         
               $hasil=bukaquery($_sql);
               $data = array();
               while ($r = mysqli_fetch_array ($hasil)){
                 
               //tambahkan lagi yang ingin di replace    
               $awalnama = array("Tn.", "BY.", "Ny.",", NY",", TN");
               $replacenama = array("Tuan ", "Bayi ", "Nyonya ","..","..");
         
               $awalpoli= array("THT");
               $replacepoli= array("T H T");
               
               $r['nm_poli']=str_replace($awalpoli,$replacepoli,$r['nm_poli']);      
               $r['nm_pasien']=str_replace($awalnama,$replacenama,$r['nm_pasien']);      
               $data[] = $r;
               
               bukaquery2("UPDATE antriadmisi SET status = '3' WHERE status='2'");
               bukaquery2("UPDATE antriadmisi SET status = '2' WHERE norm = '$r[norm]' and kd_loket='$r[kd_loket]' ");
               } 
               echo json_encode($data);
       
                } else {
$_sql="SELECT a.no_rawat,b.no_reg, a.status, d.nm_poli,c.nm_pasien,a.kd_dokter,e.nm_dokter FROM antripoli a
  INNER JOIN
      reg_periksa b ON a.no_rawat = b.no_rawat
  INNER JOIN
      pasien c ON b.no_rkm_medis = c.no_rkm_medis
  INNER JOIN
      poliklinik d ON a.kd_poli = d.kd_poli
  INNER JOIN
      dokter e ON a.kd_dokter = e.kd_dokter
WHERE d.kd_poli IN ($poli) and a.status = '1' LIMIT 1";  

      $hasil=bukaquery($_sql);
      $data = array();
      while ($r = mysqli_fetch_array ($hasil)){
        
      //tambahkan lagi yang ingin di replace    
      $awalnama = array("Tn.", "By.", "Ny.",", NY",", TN");
      $replacenama = array("Tuan ", "Bayi ", "Nyonya ", "..","..");

      $awalpoli= array("THT");
      $replacepoli= array("T H T");
      
      $r['nm_poli']=str_replace($awalpoli,$replacepoli,$r['nm_poli']);      
      $r['nm_pasien']=str_replace($awalnama,$replacenama,$r['nm_pasien']);      
      $data[] = $r;
      
      bukaquery2("UPDATE antripoli SET status = '3' WHERE status='2' and kd_poli IN ($poli) ");
      bukaquery2("UPDATE antripoli SET status = '2' WHERE no_rawat = '$r[no_rawat]' and kd_poli IN ($poli)");
      } 
      echo json_encode($data);

    }

     break;	
     




     
case 'nomor' :    


 $_sql="SELECT b.no_reg, a.status, d.nm_poli, c.nm_pasien, a.no_rawat, a.kd_dokter, e.nm_dokter, b.kd_poli FROM antripoli a
INNER JOIN
 reg_periksa b ON a.no_rawat = b.no_rawat
INNER JOIN
 pasien c ON b.no_rkm_medis = c.no_rkm_medis
INNER JOIN
 poliklinik d ON b.kd_poli = d.kd_poli
INNER JOIN
 dokter e ON b.kd_dokter = e.kd_dokter
WHERE d.kd_poli IN ($poli) and  a.status < '3' AND a.status > '0' LIMIT 1";  
 $hasil=bukaquery($_sql);
 $data = array();
 
if(mysqli_num_rows($hasil)>0) {
  while ($row = mysqli_fetch_array($hasil)) {
      $data[] = $row;
  }
} else {
 $row['kd_poli']='';
 $row['no_reg']='000';
 $row['nm_pasien']='-';
 $row['nm_dokter']='-';
 $row['nm_poli']='-';
 $data[] = $row;

}
 echo json_encode($data); 
break;	



case 'poli' :  
$tanggal=date('Y-m-d');
$jam=date('H:i:s');

// query hapus atau reset data
if($jam>$jamreset){
  bukaquery2("delete from antripoli");
}


$hari=getOne("select DAYNAME(current_date())");
$namahari="";
if($hari=="Sunday"){
  $namahari="AKHAD";
}else if($hari=="Monday"){
  $namahari="SENIN";
}else if($hari=="Tuesday"){
  $namahari="SELASA";
}else if($hari=="Wednesday"){
  $namahari="RABU";
}else if($hari=="Thursday"){
  $namahari="KAMIS";
}else if($hari=="Friday"){
  $namahari="JUMAT";
}else if($hari=="Saturday"){
  $namahari="SABTU";
}
// Menginisialisasi array data di luar loop
$data = array();

$_sql = "SELECT dokter.nm_dokter, poliklinik.nm_poli, jadwal.jam_mulai,jadwal.jam_selesai, poliklinik.kd_poli, dokter.kd_dokter
FROM jadwal
INNER JOIN dokter ON dokter.kd_dokter = jadwal.kd_dokter
INNER JOIN poliklinik ON jadwal.kd_poli = poliklinik.kd_poli
WHERE poliklinik.kd_poli IN ($poli) and jadwal.hari_kerja = '$namahari' 
AND CURTIME() BETWEEN  jadwal.jam_mulai + INTERVAL -30 MINUTE and jadwal.jam_selesai + INTERVAL + 30 MINUTE
-- WHERE poliklinik.kd_poli NOT IN ('H', 'PRAD', 'IGDk','PU') and jadwal.jam_selesai>'$jam' and jadwal.hari_kerja = '$namahari' 
GROUP BY jadwal.kd_poli,dokter.kd_dokter";
$hasil = bukaquery($_sql);

while ($r = mysqli_fetch_array($hasil)) {
    $kd_dokter = $r['kd_dokter'];
    $kd_poli = $r['kd_poli'];
    
    $s = "SELECT b.no_reg, c.nm_pasien, a.no_rawat,d.kd_poli
    FROM antripoli a
    INNER JOIN reg_periksa b ON a.no_rawat = b.no_rawat
    INNER JOIN pasien c ON b.no_rkm_medis = c.no_rkm_medis
    INNER JOIN poliklinik d ON a.kd_poli = d.kd_poli
    INNER JOIN dokter e ON b.kd_dokter = e.kd_dokter
    WHERE e.kd_dokter='$kd_dokter' AND  d.kd_poli='$kd_poli'  and a.status NOT IN ('0', '1') limit 1";  
    $h = bukaquery($s);

    // Menggunakan array baru untuk setiap dokter
    $data_pasien = array();
if(mysqli_num_rows($h)>0) {
    while ($row = mysqli_fetch_array($h)) {
        $data_pasien[] = $row;
    }
} else {
   $row['kd_poli']='';
   $row['no_reg']='000';
   $row['nm_pasien']='-';
   $data_pasien[] = $row;

}

    // Menambahkan data dokter ke dalam array data
    $r['data_pasien'] = $data_pasien;
    
    // Menambahkan data dokter ke dalam array data
    $data[] = $r;
}

// Menampilkan hasil sebagai JSON
echo json_encode($data);
break;
}
}
?>