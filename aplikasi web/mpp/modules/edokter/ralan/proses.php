<?php
// File: modules/edokter/ralan/proses.php
require_once '../../../config/config.php';
require_once '../../../config/database.php';
require_once '../../../helpers/auth_helper.php';

cekLogin();
if (!cekAkses('soap_perawatan')) { die("Akses Ditolak"); }

$act = $_GET['act'] ?? '';
$nip_dokter = $_SESSION['user_id']; 

// 3. FUNGSI AUDIT TRAIL
function catat_trackersql($pdo, $sqle, $usere) {
    try {
        $stmt = $pdo->prepare("INSERT INTO trackersql (tanggal, sqle, usere) VALUES (NOW(), ?, ?)");
        $stmt->execute([$sqle, $usere]);
    } catch (Exception $e) {
        // Silent error jika tabel belum ready, agar aplikasi tidak crash
    }
}

try {
    // CREATE / UPDATE CPPT
    if ($act == 'simpan_cppt') {
        $mode = $_POST['aksi_nyata']; 
        $no_rawat = $_POST['no_rawat'];
        $gcs_final = $_POST['gcs']; 

        $data = [
            $_POST['suhu_tubuh'], $_POST['tensi'], $_POST['nadi'], $_POST['respirasi'],
            $_POST['tinggi'], $_POST['berat'], $_POST['spo2'], $gcs_final,
            $_POST['kesadaran'], $_POST['keluhan'], $_POST['pemeriksaan'], $_POST['alergi'],
            $_POST['lingkar_perut'] ?? '', $_POST['rtl'], $_POST['penilaian'], $_POST['instruksi'], $_POST['evaluasi']
        ];

        if ($mode == 'simpan') {
            $tgl = date('Y-m-d'); $jam = date('H:i:s');
            $sql = "INSERT INTO pemeriksaan_ralan (no_rawat, tgl_perawatan, jam_rawat, suhu_tubuh, tensi, nadi, respirasi, tinggi, berat, spo2, gcs, kesadaran, keluhan, pemeriksaan, alergi, lingkar_perut, rtl, penilaian, instruksi, evaluasi, nip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = array_merge([$no_rawat, $tgl, $jam], $data, [$nip_dokter]);
            $pdo->prepare($sql)->execute($params);

            // Audit Trail Record
            $audit_query = "INSERT INTO pemeriksaan_ralan VALUES ('$no_rawat','$tgl','$jam', ...)";
            catat_trackersql($pdo, $audit_query, $nip_dokter);

            header("Location: index.php?status=sukses&offer_update=1&no_rawat=$no_rawat");
            exit();
        } 
    }
	
	
	// --- FITUR BARU: SIMPAN / UPDATE RESUME MEDIS ---
    elseif ($act == 'simpan_resume') {
        $no_rawat = $_POST['no_rawat'];
        
        $sql = "INSERT INTO resume_pasien (
                    no_rawat, kd_dokter, keluhan_utama, jalannya_penyakit, 
                    pemeriksaan_penunjang, hasil_laborat, 
                    diagnosa_utama, kd_diagnosa_utama, 
                    diagnosa_sekunder, kd_diagnosa_sekunder, 
                    diagnosa_sekunder2, kd_diagnosa_sekunder2, 
                    diagnosa_sekunder3, kd_diagnosa_sekunder3, 
                    diagnosa_sekunder4, kd_diagnosa_sekunder4, 
                    prosedur_utama, kd_prosedur_utama, 
                    prosedur_sekunder, kd_prosedur_sekunder, 
                    prosedur_sekunder2, kd_prosedur_sekunder2, 
                    prosedur_sekunder3, kd_prosedur_sekunder3, 
                    kondisi_pulang, obat_pulang
                ) VALUES (
                    ?, ?, ?, ?, 
                    ?, ?, 
                    ?, ?, 
                    ?, ?, 
                    ?, ?, 
                    ?, ?, 
                    ?, ?, 
                    ?, ?, 
                    ?, ?, 
                    ?, ?, 
                    ?, ?, 
                    ?, ?
                ) ON DUPLICATE KEY UPDATE 
                    keluhan_utama=VALUES(keluhan_utama), jalannya_penyakit=VALUES(jalannya_penyakit), 
                    pemeriksaan_penunjang=VALUES(pemeriksaan_penunjang), hasil_laborat=VALUES(hasil_laborat), 
                    diagnosa_utama=VALUES(diagnosa_utama), kd_diagnosa_utama=VALUES(kd_diagnosa_utama), 
                    diagnosa_sekunder=VALUES(diagnosa_sekunder), kd_diagnosa_sekunder=VALUES(kd_diagnosa_sekunder), 
                    diagnosa_sekunder2=VALUES(diagnosa_sekunder2), kd_diagnosa_sekunder2=VALUES(kd_diagnosa_sekunder2), 
                    diagnosa_sekunder3=VALUES(diagnosa_sekunder3), kd_diagnosa_sekunder3=VALUES(kd_diagnosa_sekunder3), 
                    diagnosa_sekunder4=VALUES(diagnosa_sekunder4), kd_diagnosa_sekunder4=VALUES(kd_diagnosa_sekunder4), 
                    prosedur_utama=VALUES(prosedur_utama), kd_prosedur_utama=VALUES(kd_prosedur_utama), 
                    prosedur_sekunder=VALUES(prosedur_sekunder), kd_prosedur_sekunder=VALUES(kd_prosedur_sekunder), 
                    prosedur_sekunder2=VALUES(prosedur_sekunder2), kd_prosedur_sekunder2=VALUES(kd_prosedur_sekunder2), 
                    prosedur_sekunder3=VALUES(prosedur_sekunder3), kd_prosedur_sekunder3=VALUES(kd_prosedur_sekunder3), 
                    kondisi_pulang=VALUES(kondisi_pulang), obat_pulang=VALUES(obat_pulang)";

        $params = [
            $no_rawat, $nip_dokter,
            $_POST['keluhan_utama'], $_POST['jalannya_penyakit'],
            $_POST['pemeriksaan_penunjang'], $_POST['hasil_laborat'],
            $_POST['diagnosa_utama'], $_POST['kd_diagnosa_utama'] ?? '',
            $_POST['diagnosa_sekunder'], $_POST['kd_diagnosa_sekunder'] ?? '',
            $_POST['diagnosa_sekunder2'], $_POST['kd_diagnosa_sekunder2'] ?? '',
            $_POST['diagnosa_sekunder3'], $_POST['kd_diagnosa_sekunder3'] ?? '',
            $_POST['diagnosa_sekunder4'], $_POST['kd_diagnosa_sekunder4'] ?? '',
            $_POST['prosedur_utama'], $_POST['kd_prosedur_utama'] ?? '',
            $_POST['prosedur_sekunder'], $_POST['kd_prosedur_sekunder'] ?? '',
            $_POST['prosedur_sekunder2'], $_POST['kd_prosedur_sekunder2'] ?? '',
            $_POST['prosedur_sekunder3'], $_POST['kd_prosedur_sekunder3'] ?? '',
            $_POST['kondisi_pulang'], $_POST['obat_pulang']
        ];

        $pdo->prepare($sql)->execute($params);
        catat_trackersql($pdo, "UPSERT resume_pasien WHERE no_rawat='$no_rawat'", $nip_dokter);

        header("Location: index.php?status=sukses_resume");
        exit();
    }
	
	
	

    // UPDATE STATUS PERIKSA & MUTASI BERKAS
    elseif ($act == 'update_status_periksa') {
        $no_rawat = $_GET['no_rawat'];
        
        $sql_reg = "UPDATE reg_periksa SET stts='Sudah' WHERE no_rawat=?";
        $pdo->prepare($sql_reg)->execute([$no_rawat]);
        catat_trackersql($pdo, "UPDATE reg_periksa SET stts='Sudah' WHERE no_rawat='$no_rawat'", $nip_dokter);
        
        $sql_mutasi = "INSERT INTO mutasi_berkas (no_rawat, status, dikirim, diterima, kembali, tidakada, ranap) 
                       VALUES (?, 'Sudah Kembali', NOW(), '0000-00-00 00:00:00', NOW(), '0000-00-00 00:00:00', '0000-00-00 00:00:00')
                       ON DUPLICATE KEY UPDATE status = 'Sudah Kembali', kembali = NOW()";
        $pdo->prepare($sql_mutasi)->execute([$no_rawat]);
        catat_trackersql($pdo, "UPSERT mutasi_berkas STATUS='Sudah Kembali' WHERE no_rawat='$no_rawat'", $nip_dokter);

        header("Location: index.php?status=sukses_update_status");
        exit();
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>