<?php
// File: modules/edokter/ranap/proses.php
require_once '../../../config/config.php';
require_once '../../../config/database.php';
require_once '../../../helpers/auth_helper.php';

cekLogin();
if (!cekAkses('soap_perawatan')) { 
    echo json_encode(['status' => 'error', 'message' => 'Akses Ditolak']); 
    exit; 
}

header('Content-Type: application/json');

$act = $_GET['act'] ?? '';
$nip_dokter = $_SESSION['user_id']; 

// FUNGSI AUDIT TRAIL
function catat_trackersql($pdo, $sqle, $usere) {
    try {
        $stmt = $pdo->prepare("INSERT INTO trackersql (tanggal, sqle, usere) VALUES (NOW(), ?, ?)");
        $stmt->execute([$sqle, $usere]);
    } catch (Exception $e) {}
}

try {
    // ====================================================================
    // 1. CREATE / UPDATE CPPT RANAP
    // ====================================================================
    if ($act == 'simpan_cppt') {
        $mode = $_POST['aksi_nyata'] ?? 'simpan'; 
        $no_rawat = $_POST['no_rawat'];
        $gcs_final = $_POST['gcs']; 

        $data = [
            $_POST['suhu_tubuh'], $_POST['tensi'], $_POST['nadi'], $_POST['respirasi'],
            $_POST['tinggi'], $_POST['berat'], $_POST['spo2'], $gcs_final,
            $_POST['kesadaran'], $_POST['keluhan'], $_POST['pemeriksaan'], $_POST['alergi'],
            $_POST['penilaian'], $_POST['rtl'], $_POST['instruksi'], $_POST['evaluasi']
        ];

        if ($mode == 'simpan') {
            $tgl = date('Y-m-d'); $jam = date('H:i:s');
            $sql = "INSERT INTO pemeriksaan_ranap (no_rawat, tgl_perawatan, jam_rawat, suhu_tubuh, tensi, nadi, respirasi, tinggi, berat, spo2, gcs, kesadaran, keluhan, pemeriksaan, alergi, penilaian, rtl, instruksi, evaluasi, nip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = array_merge([$no_rawat, $tgl, $jam], $data, [$nip_dokter]);
            $pdo->prepare($sql)->execute($params);

            catat_trackersql($pdo, "INSERT INTO pemeriksaan_ranap VALUES ('$no_rawat','$tgl','$jam', ...)", $nip_dokter);

            echo json_encode(['status' => 'success', 'message' => 'Data CPPT Rawat Inap berhasil disimpan!']);
            exit;
        } 
    }

    // ====================================================================
    // 2. CREATE / UPDATE RESUME MEDIS RANAP (40 KOLOM)
    // ====================================================================
    elseif ($act == 'simpan_resume') {
        $no_rawat = $_POST['no_rawat'];
        
        $kontrol = !empty($_POST['kontrol']) ? date('Y-m-d H:i:s', strtotime($_POST['kontrol'])) : null;
        
        $sql = "INSERT INTO resume_pasien_ranap (
                    no_rawat, kd_dokter, diagnosa_awal, alasan, keluhan_utama, pemeriksaan_fisik, jalannya_penyakit,
                    pemeriksaan_penunjang, hasil_laborat, tindakan_dan_operasi, obat_di_rs,
                    diagnosa_utama, kd_diagnosa_utama, diagnosa_sekunder, kd_diagnosa_sekunder,
                    diagnosa_sekunder2, kd_diagnosa_sekunder2, diagnosa_sekunder3, kd_diagnosa_sekunder3,
                    diagnosa_sekunder4, kd_diagnosa_sekunder4, prosedur_utama, kd_prosedur_utama,
                    prosedur_sekunder, kd_prosedur_sekunder, prosedur_sekunder2, kd_prosedur_sekunder2,
                    prosedur_sekunder3, kd_prosedur_sekunder3, alergi, diet, lab_belum, edukasi,
                    cara_keluar, ket_keluar, keadaan, ket_keadaan, dilanjutkan, ket_dilanjutkan,
                    kontrol, obat_pulang
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, ?, 
                    ?, ?
                ) ON DUPLICATE KEY UPDATE 
                    diagnosa_awal=VALUES(diagnosa_awal), alasan=VALUES(alasan), keluhan_utama=VALUES(keluhan_utama), 
                    pemeriksaan_fisik=VALUES(pemeriksaan_fisik), jalannya_penyakit=VALUES(jalannya_penyakit), 
                    pemeriksaan_penunjang=VALUES(pemeriksaan_penunjang), hasil_laborat=VALUES(hasil_laborat), 
                    tindakan_dan_operasi=VALUES(tindakan_dan_operasi), obat_di_rs=VALUES(obat_di_rs),
                    diagnosa_utama=VALUES(diagnosa_utama), kd_diagnosa_utama=VALUES(kd_diagnosa_utama), 
                    diagnosa_sekunder=VALUES(diagnosa_sekunder), kd_diagnosa_sekunder=VALUES(kd_diagnosa_sekunder), 
                    diagnosa_sekunder2=VALUES(diagnosa_sekunder2), kd_diagnosa_sekunder2=VALUES(kd_diagnosa_sekunder2), 
                    diagnosa_sekunder3=VALUES(diagnosa_sekunder3), kd_diagnosa_sekunder3=VALUES(kd_diagnosa_sekunder3), 
                    diagnosa_sekunder4=VALUES(diagnosa_sekunder4), kd_diagnosa_sekunder4=VALUES(kd_diagnosa_sekunder4), 
                    prosedur_utama=VALUES(prosedur_utama), kd_prosedur_utama=VALUES(kd_prosedur_utama), 
                    prosedur_sekunder=VALUES(prosedur_sekunder), kd_prosedur_sekunder=VALUES(kd_prosedur_sekunder), 
                    prosedur_sekunder2=VALUES(prosedur_sekunder2), kd_prosedur_sekunder2=VALUES(kd_prosedur_sekunder2), 
                    prosedur_sekunder3=VALUES(prosedur_sekunder3), kd_prosedur_sekunder3=VALUES(kd_prosedur_sekunder3), 
                    alergi=VALUES(alergi), diet=VALUES(diet), lab_belum=VALUES(lab_belum), edukasi=VALUES(edukasi),
                    cara_keluar=VALUES(cara_keluar), ket_keluar=VALUES(ket_keluar), keadaan=VALUES(keadaan), 
                    ket_keadaan=VALUES(ket_keadaan), dilanjutkan=VALUES(dilanjutkan), ket_dilanjutkan=VALUES(ket_dilanjutkan),
                    kontrol=VALUES(kontrol), obat_pulang=VALUES(obat_pulang)";

        $params = [
            $no_rawat, $nip_dokter, $_POST['diagnosa_awal'], $_POST['alasan'], $_POST['keluhan_utama'], $_POST['pemeriksaan_fisik'], $_POST['jalannya_penyakit'],
            $_POST['pemeriksaan_penunjang'], $_POST['hasil_laborat'], $_POST['tindakan_dan_operasi'], $_POST['obat_di_rs'],
            $_POST['diagnosa_utama'], $_POST['kd_diagnosa_utama'] ?? '', $_POST['diagnosa_sekunder'], $_POST['kd_diagnosa_sekunder'] ?? '',
            $_POST['diagnosa_sekunder2'], $_POST['kd_diagnosa_sekunder2'] ?? '', $_POST['diagnosa_sekunder3'], $_POST['kd_diagnosa_sekunder3'] ?? '',
            $_POST['diagnosa_sekunder4'], $_POST['kd_diagnosa_sekunder4'] ?? '', $_POST['prosedur_utama'], $_POST['kd_prosedur_utama'] ?? '',
            $_POST['prosedur_sekunder'], $_POST['kd_prosedur_sekunder'] ?? '', $_POST['prosedur_sekunder2'], $_POST['kd_prosedur_sekunder2'] ?? '',
            $_POST['prosedur_sekunder3'], $_POST['kd_prosedur_sekunder3'] ?? '', $_POST['alergi'], $_POST['diet'], $_POST['lab_belum'], $_POST['edukasi'],
            $_POST['cara_keluar'], $_POST['ket_keluar'], $_POST['keadaan'], $_POST['ket_keadaan'], $_POST['dilanjutkan'], $_POST['ket_dilanjutkan'],
            $kontrol, $_POST['obat_pulang']
        ];

        $pdo->prepare($sql)->execute($params);
        catat_trackersql($pdo, "UPSERT resume_pasien_ranap WHERE no_rawat='$no_rawat'", $nip_dokter);

        echo json_encode(['status' => 'success', 'message' => 'Data Resume Medis Rawat Inap berhasil disimpan!']);
        exit;
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>