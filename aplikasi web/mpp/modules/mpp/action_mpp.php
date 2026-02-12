<?php
// File: modules/mpp/action_mpp.php
require_once '../../config/database.php';
require_once '../../helpers/auth_helper.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit(json_encode(['status'=>'error', 'message'=>'Invalid Request']));

$action = $_POST['action'];
$no_rawat = $_POST['no_rawat'];

try {
    
    // 1. SAVE SKRINING
    if ($action == 'save_skrining') {
        // Ambil NIP Login (Skrining biasanya user login)
        // Jika superadmin, fallback ke strip
        $nip = $_SESSION['user_id'];
        $cek_nip = $pdo->prepare("SELECT nik FROM pegawai WHERE nik=?");
        $cek_nip->execute([$nip]);
        if($cek_nip->rowCount() == 0) $nip = '-';

        $cek = $pdo->prepare("SELECT no_rawat FROM mpp_skrining WHERE no_rawat=?");
        $cek->execute([$no_rawat]);
        
        $params = [
            date('Y-m-d'), 
            $_POST['param1']??'Tidak', $_POST['param2']??'Tidak', $_POST['param3']??'Tidak', $_POST['param4']??'Tidak',
            $_POST['param5']??'Tidak', $_POST['param6']??'Tidak', $_POST['param7']??'Tidak', $_POST['param8']??'Tidak',
            $_POST['param9']??'Tidak', $_POST['param10']??'Tidak', $_POST['param11']??'Tidak', $_POST['param12']??'Tidak',
            $_POST['param13']??'Tidak', $_POST['param14']??'Tidak', $_POST['param15']??'Tidak', $_POST['param16']??'Tidak',
            $nip
        ];

        if ($cek->rowCount() > 0) {
            $sql = "UPDATE mpp_skrining SET tanggal=?, param1=?, param2=?, param3=?, param4=?, param5=?, param6=?, param7=?, param8=?, param9=?, param10=?, param11=?, param12=?, param13=?, param14=?, param15=?, param16=?, nip=? WHERE no_rawat=?";
            $params[] = $no_rawat; 
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            array_unshift($params, $no_rawat); 
            $sql = "INSERT INTO mpp_skrining VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }
    }

    // 2. SAVE EVALUASI (FORM A) - CRITICAL UPDATE
    elseif ($action == 'save_evaluasi') {
        $kd_dokter   = !empty($_POST['kd_dokter']) ? $_POST['kd_dokter'] : '-';
        $kd_konsulan = !empty($_POST['kd_konsulan']) ? $_POST['kd_konsulan'] : '-';
        $diagnosis   = $_POST['diagnosis'] ?? '-';
        $kelompok    = $_POST['kelompok'] ?? '-';
        $assesmen    = $_POST['assesmen'] ?? '-';     
        $identifikasi = $_POST['identifikasi'] ?? '-'; 
        $rencana     = $_POST['rencana'] ?? '-';
        $nip_petugas = $_POST['nip_petugas']; // NIP dari Input Form (Wajib)

        if(empty($nip_petugas)) throw new Exception("Petugas MPP belum dipilih!");

        // Waktu untuk sinkronisasi Header & Detail
        $timestamp_now = date('Y-m-d H:i:s');

        // A. Cek apakah sudah ada data header sebelumnya?
        $cek = $pdo->prepare("SELECT no_rawat FROM mpp_evaluasi WHERE no_rawat=?");
        $cek->execute([$no_rawat]);
        
        if ($cek->rowCount() > 0) {
            // UPDATE: Kita update tanggal jadi NOW(), agar detail masalah yang baru bisa masuk dengan tanggal yang sama.
            // ATAU: Kita pertahankan tanggal lama?
            // Biasanya update evaluasi = update tanggal juga.
            $sql = "UPDATE mpp_evaluasi SET tanggal=?, kd_dokter=?, kd_konsulan=?, diagnosis=?, kelompok=?, assesmen=?, identifikasi=?, rencana=?, nip=? WHERE no_rawat=?";
            $pdo->prepare($sql)->execute([$timestamp_now, $kd_dokter, $kd_konsulan, $diagnosis, $kelompok, $assesmen, $identifikasi, $rencana, $nip_petugas, $no_rawat]);
        } else {
            // INSERT
            $sql = "INSERT INTO mpp_evaluasi (no_rawat, tanggal, kd_dokter, kd_konsulan, diagnosis, kelompok, assesmen, identifikasi, rencana, nip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$no_rawat, $timestamp_now, $kd_dokter, $kd_konsulan, $diagnosis, $kelompok, $assesmen, $identifikasi, $rencana, $nip_petugas]);
        }

        // B. DETAIL MASALAH (Checkbox)
        // 1. Hapus semua masalah lama untuk pasien ini (Clean Slate strategy)
        //    Khanza Java logic: delete from mpp_evaluasi_masalah where no_rawat=? and tanggal=?
        //    Karena kita baru saja update tanggal header jadi $timestamp_now, maka yang lama akan jadi yatim piatu jika tidak dihapus.
        //    Strategy aman: Hapus semua detail masalah milik no_rawat ini.
        $pdo->prepare("DELETE FROM mpp_evaluasi_masalah WHERE no_rawat=?")->execute([$no_rawat]);
        
        // 2. Insert baru dengan TANGGAL YANG SAMA PERSIS dengan Header ($timestamp_now)
        if (isset($_POST['masalah_check']) && is_array($_POST['masalah_check'])) {
            $stmt_det = $pdo->prepare("INSERT INTO mpp_evaluasi_masalah (no_rawat, tanggal, kode_masalah) VALUES (?, ?, ?)");
            foreach ($_POST['masalah_check'] as $kd) {
                $stmt_det->execute([$no_rawat, $timestamp_now, $kd]);
            }
        }
    }

    // 3. SAVE CATATAN (FORM B)
    elseif ($action == 'save_catatan') {
        $masalah = $_POST['masalah'] ?? '-';
        $tinjut  = $_POST['tinjut'] ?? '-';
        $evaluasi= $_POST['evaluasi'] ?? '-';
        $nip_petugas = $_POST['nip_petugas_catatan'];

        if(empty($nip_petugas)) {
            // Fallback ke session user jika form kosong, lalu cek validitas
            $nip_petugas = $_SESSION['user_id'];
            $cek = $pdo->query("SELECT nik FROM pegawai WHERE nik='$nip_petugas'")->fetch();
            if(!$cek) $nip_petugas = '-';
        }

        $sql = "INSERT INTO mpp_evaluasi_catatan (no_rawat, tgl_implementasi, masalah, tinjut, evaluasi, nip) 
                VALUES (?, NOW(), ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$no_rawat, $masalah, $tinjut, $evaluasi, $nip_petugas]);
    }

    echo json_encode(['status'=>'success']);

} catch (Exception $e) {
    $msg = $e->getMessage();
    if(strpos($msg, '1452') !== false) $msg = "Constraint Error: Data Petugas/Dokter/Masalah tidak valid di database.";
    echo json_encode(['status'=>'error', 'message'=>$msg]);
}
?>