<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../conf/conf.php';

function respond($ok, $extra = []){
    echo json_encode(array_merge(['ok'=>$ok], $extra));
    exit;
}

$barcodeReq = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';
if($barcodeReq === ''){ respond(false, ['error'=>'empty barcode']); }

$today = date('Y-m-d');
$hariIndex = date('j');
$bulan = date('m');
$tahun = date('Y');

try{
    // Resolve pegawai id from barcode/nik/id
    $idpeg = getOne("select id from barcode where barcode='$barcodeReq'");
    if(empty($idpeg)){
        $idpeg = getOne("select id from pegawai where nik='$barcodeReq' limit 1");
    }
    if(empty($idpeg)){
        $idpeg = getOne("select id from pegawai where id='$barcodeReq' limit 1");
    }
    if(empty($idpeg)){
        respond(false, ['error'=>'id not found']);
    }

    $depId = getOne("select departemen from pegawai where id='$idpeg'");
    $jamMasuk = '';

    // jadwal_pegawai: column h{day} stores shift code
    $col = 'h'.$hariIndex;
    $shiftCode = getOne("select $col from jadwal_pegawai where id='$idpeg' and bulan='$bulan' and tahun='$tahun' limit 1");
    if(!empty($shiftCode)){
        $jamMasuk = getOne("select TIME_FORMAT(jam_masuk,'%H:%i:%s') from jam_jaga where shift='$shiftCode' and dep_id='$depId' limit 1");
    }

    // jadwal_tambahan override for today
    if(empty($jamMasuk)){
        $jamMasuk = getOne("select TIME_FORMAT(jam_masuk,'%H:%i:%s') from jadwal_tambahan where id='$idpeg' and tanggal='$today' limit 1");
    }

    // fallback: any jam_jaga in department (earliest)
    if(empty($jamMasuk)){
        $jamMasuk = getOne("select TIME_FORMAT(jam_masuk,'%H:%i:%s') from jam_jaga where dep_id='$depId' order by jam_masuk asc limit 1");
    }

    respond(true, ['jam_masuk'=>$jamMasuk ?: '']);
}catch(Exception $e){
    respond(false, ['error'=>$e->getMessage()]);
}


