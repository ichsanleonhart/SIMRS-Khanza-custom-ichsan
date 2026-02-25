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
function catat_trackersql($pdo, $sqle, $usere)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO trackersql (tanggal, sqle, usere) VALUES (NOW(), ?, ?)");
        $stmt->execute([$sqle, $usere]);
    }
    catch (Exception $e) {
    }
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
            $tgl = date('Y-m-d');
            $jam = date('H:i:s');
            $sql = "INSERT INTO pemeriksaan_ranap (no_rawat, tgl_perawatan, jam_rawat, suhu_tubuh, tensi, nadi, respirasi, tinggi, berat, spo2, gcs, kesadaran, keluhan, pemeriksaan, alergi, penilaian, rtl, instruksi, evaluasi, nip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = array_merge([$no_rawat, $tgl, $jam], $data, [$nip_dokter]);
            $pdo->prepare($sql)->execute($params);

            catat_trackersql($pdo, "INSERT INTO pemeriksaan_ranap VALUES ('$no_rawat','$tgl','$jam', ...)", $nip_dokter);

            echo json_encode(['status' => 'success', 'message' => 'Data CPPT Rawat Inap berhasil disimpan!']);
            exit;
        }
        elseif ($mode == 'ubah') {
            $tgl = $_POST['tgl_perawatan'];
            $jam = $_POST['jam_rawat'];

            // Validasi 48 jam dan Kepemilikan (menggunakan query MySQL/MariaDB TimestampDiff)
            $stmt = $pdo->prepare("SELECT nip FROM pemeriksaan_ranap WHERE no_rawat=? AND tgl_perawatan=? AND jam_rawat=? AND TIMESTAMPDIFF(HOUR, CONCAT(tgl_perawatan, ' ', jam_rawat), NOW()) <= 48");
            $stmt->execute([$no_rawat, $tgl, $jam]);
            $cek = $stmt->fetch();

            if (!$cek) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal Edit: Data tidak ditemukan atau batas waktu 48 jam terlewati.']);
                exit;
            }
            if ($cek['nip'] != $nip_dokter) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal Edit: Anda bukan penulis CPPT ini.']);
                exit;
            }

            $sql = "UPDATE pemeriksaan_ranap SET suhu_tubuh=?, tensi=?, nadi=?, respirasi=?, tinggi=?, berat=?, spo2=?, gcs=?, kesadaran=?, keluhan=?, pemeriksaan=?, alergi=?, penilaian=?, rtl=?, instruksi=?, evaluasi=? WHERE no_rawat=? AND tgl_perawatan=? AND jam_rawat=? AND nip=?";
            $params = array_merge($data, [$no_rawat, $tgl, $jam, $nip_dokter]);
            $pdo->prepare($sql)->execute($params);

            catat_trackersql($pdo, "UPDATE pemeriksaan_ranap SET suhu_tubuh='{$data[0]}', ... WHERE no_rawat='$no_rawat' AND tgl_perawatan='$tgl' AND jam_rawat='$jam'", $nip_dokter);

            echo json_encode(['status' => 'success', 'message' => 'Data CPPT Rawat Inap berhasil diperbarui!']);
            exit;
        }
    }

    // ====================================================================
    // 1b. DELETE CPPT RANAP
    // ====================================================================
    elseif ($act == 'hapus_cppt') {
        $no_rawat = $_POST['no_rawat'];
        $tgl = $_POST['tgl_perawatan'];
        $jam = $_POST['jam_rawat'];

        $stmt = $pdo->prepare("SELECT nip FROM pemeriksaan_ranap WHERE no_rawat=? AND tgl_perawatan=? AND jam_rawat=? AND TIMESTAMPDIFF(HOUR, CONCAT(tgl_perawatan, ' ', jam_rawat), NOW()) <= 48");
        $stmt->execute([$no_rawat, $tgl, $jam]);
        $cek = $stmt->fetch();

        if (!$cek) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal Hapus: Data tidak ditemukan atau batas waktu 48 jam terlewati.']);
            exit;
        }
        if ($cek['nip'] != $nip_dokter) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal Hapus: Anda bukan penulis CPPT ini.']);
            exit;
        }

        $sql = "DELETE FROM pemeriksaan_ranap WHERE no_rawat=? AND tgl_perawatan=? AND jam_rawat=? AND nip=?";
        $pdo->prepare($sql)->execute([$no_rawat, $tgl, $jam, $nip_dokter]);

        catat_trackersql($pdo, "DELETE FROM pemeriksaan_ranap WHERE no_rawat='$no_rawat' AND tgl_perawatan='$tgl' AND jam_rawat='$jam'", $nip_dokter);

        echo json_encode(['status' => 'success', 'message' => 'Data CPPT berhasil dihapus.']);
        exit;
    }

    // ====================================================================
    // 1c. SIMPAN / UPDATE PENGKAJIAN AWAL MEDIS RANAP
    // ====================================================================
    elseif ($act == 'simpan_pengkajian') {
        $no_rawat = $_POST['no_rawat'];
        $tanggal = !empty($_POST['tanggal']) ? date('Y-m-d H:i:s', strtotime($_POST['tanggal'])) : date('Y-m-d H:i:s');

        $sql = "INSERT INTO penilaian_medis_ranap (
                    no_rawat, tanggal, kd_dokter,
                    anamnesis, hubungan,
                    keluhan_utama, rps, rpd, rpk, rpo, alergi,
                    keadaan, gcs, kesadaran,
                    td, nadi, rr, suhu, spo, bb, tb,
                    kepala, mata, gigi, tht, thoraks, jantung, paru,
                    abdomen, genital, ekstremitas, kulit,
                    ket_fisik, ket_lokalis,
                    lab, rad, penunjang,
                    diagnosis, tata, edukasi
                ) VALUES (
                    ?, ?, ?,
                    ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?,
                    ?, ?, ?,
                    ?, ?, ?
                ) ON DUPLICATE KEY UPDATE
                    tanggal=VALUES(tanggal), kd_dokter=VALUES(kd_dokter),
                    anamnesis=VALUES(anamnesis), hubungan=VALUES(hubungan),
                    keluhan_utama=VALUES(keluhan_utama), rps=VALUES(rps), rpd=VALUES(rpd), rpk=VALUES(rpk), rpo=VALUES(rpo), alergi=VALUES(alergi),
                    keadaan=VALUES(keadaan), gcs=VALUES(gcs), kesadaran=VALUES(kesadaran),
                    td=VALUES(td), nadi=VALUES(nadi), rr=VALUES(rr), suhu=VALUES(suhu), spo=VALUES(spo), bb=VALUES(bb), tb=VALUES(tb),
                    kepala=VALUES(kepala), mata=VALUES(mata), gigi=VALUES(gigi), tht=VALUES(tht), thoraks=VALUES(thoraks), jantung=VALUES(jantung), paru=VALUES(paru),
                    abdomen=VALUES(abdomen), genital=VALUES(genital), ekstremitas=VALUES(ekstremitas), kulit=VALUES(kulit),
                    ket_fisik=VALUES(ket_fisik), ket_lokalis=VALUES(ket_lokalis),
                    lab=VALUES(lab), rad=VALUES(rad), penunjang=VALUES(penunjang),
                    diagnosis=VALUES(diagnosis), tata=VALUES(tata), edukasi=VALUES(edukasi)";

        $params = [
            $no_rawat, $tanggal, $nip_dokter,
            $_POST['anamnesis'] ?? 'Autoanamnesis', $_POST['hubungan'] ?? '',
            $_POST['keluhan_utama'] ?? '', $_POST['rps'] ?? '', $_POST['rpd'] ?? '', $_POST['rpk'] ?? '', $_POST['rpo'] ?? '', $_POST['alergi'] ?? '',
            $_POST['keadaan'] ?? 'Sehat', $_POST['gcs'] ?? '', $_POST['kesadaran'] ?? 'Compos Mentis',
            $_POST['td'] ?? '', $_POST['nadi'] ?? '', $_POST['rr'] ?? '', $_POST['suhu'] ?? '', $_POST['spo'] ?? '', $_POST['bb'] ?? '', $_POST['tb'] ?? '',
            $_POST['kepala'] ?? 'Tidak Diperiksa', $_POST['mata'] ?? 'Tidak Diperiksa', $_POST['gigi'] ?? 'Tidak Diperiksa', $_POST['tht'] ?? 'Tidak Diperiksa',
            $_POST['thoraks'] ?? 'Tidak Diperiksa', $_POST['jantung'] ?? 'Tidak Diperiksa', $_POST['paru'] ?? 'Tidak Diperiksa',
            $_POST['abdomen'] ?? 'Tidak Diperiksa', $_POST['genital'] ?? 'Tidak Diperiksa', $_POST['ekstremitas'] ?? 'Tidak Diperiksa', $_POST['kulit'] ?? 'Tidak Diperiksa',
            $_POST['ket_fisik'] ?? '', $_POST['ket_lokalis'] ?? '',
            $_POST['lab'] ?? '', $_POST['rad'] ?? '', $_POST['penunjang'] ?? '',
            $_POST['diagnosis'] ?? '', $_POST['tata'] ?? '', $_POST['edukasi'] ?? ''
        ];

        $pdo->prepare($sql)->execute($params);
        catat_trackersql($pdo, "UPSERT penilaian_medis_ranap WHERE no_rawat='$no_rawat'", $nip_dokter);

        echo json_encode(['status' => 'success', 'message' => 'Data Pengkajian Awal Medis Rawat Inap berhasil disimpan!']);
        exit;
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

}
catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>