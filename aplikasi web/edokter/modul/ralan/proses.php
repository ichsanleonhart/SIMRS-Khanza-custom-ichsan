<?php
// modul/ralan/proses.php
session_start();
require_once '../../config/database.php';
require_once '../../config/fungsi.php';

if (!isset($_SESSION['login_user'])) {
    die("Akses Ditolak");
}

$act = $_GET['act'] ?? '';
$nip_dokter = $_SESSION['login_user']; 

try {
    // --- CREATE / UPDATE CPPT ---
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
            catat_tracker($pdo, "INSERT INTO pemeriksaan_ralan VALUES ('$no_rawat','$tgl','$jam',...)");

            // --- PERUBAHAN DISINI: REDIRECT DENGAN TAWARAN UPDATE ---
            // Kita kirim parameter offer_update=1 agar index.php tahu harus tanya dokter
            header("Location: index.php?status=sukses&offer_update=1&no_rawat=$no_rawat");
            exit();

        } elseif ($mode == 'ubah') {
            $tgl_lama = $_POST['tgl_lama']; $jam_lama = $_POST['jam_lama'];
            $sql = "UPDATE pemeriksaan_ralan SET suhu_tubuh=?, tensi=?, nadi=?, respirasi=?, tinggi=?, berat=?, spo2=?, gcs=?, kesadaran=?, keluhan=?, pemeriksaan=?, alergi=?, lingkar_perut=?, rtl=?, penilaian=?, instruksi=?, evaluasi=? WHERE no_rawat=? AND tgl_perawatan=? AND jam_rawat=? AND nip=?";
            $params = array_merge($data, [$no_rawat, $tgl_lama, $jam_lama, $nip_dokter]);
            $pdo->prepare($sql)->execute($params);
            catat_tracker($pdo, "UPDATE pemeriksaan_ralan SET ... WHERE no_rawat='$no_rawat' AND jam='$jam_lama'");
            
            // Kalau edit, biasanya tidak perlu ditanya update status lagi, balik normal saja
            header("Location: index.php?status=sukses");
            exit();
        }
    }

    // --- FITUR BARU: UPDATE STATUS PERIKSA & MUTASI BERKAS ---
    elseif ($act == 'update_status_periksa') {
        $no_rawat = $_GET['no_rawat'];
        
        // 1. Update Reg Periksa
        $sql_reg = "UPDATE reg_periksa SET stts='Sudah' WHERE no_rawat=?";
        $pdo->prepare($sql_reg)->execute([$no_rawat]);
        
        // Audit Reg Periksa
        catat_tracker($pdo, "UPDATE reg_periksa SET stts='Sudah' WHERE no_rawat='$no_rawat'");

        // 2. Simpan/Update Mutasi Berkas (SESUAI LOGIKA JAVA & STRUKTUR TABEL)
        // Kolom: no_rawat, status, dikirim, diterima, kembali, tidakada, ranap
        $sql_mutasi = "INSERT INTO mutasi_berkas 
                       (no_rawat, status, dikirim, diterima, kembali, tidakada, ranap) 
                       VALUES (?, 'Sudah Kembali', NOW(), '0000-00-00 00:00:00', NOW(), '0000-00-00 00:00:00', '0000-00-00 00:00:00')
                       ON DUPLICATE KEY UPDATE 
                       status = 'Sudah Kembali', 
                       kembali = NOW()";
        
        // Eksekusi (Hanya butuh 1 parameter yaitu no_rawat untuk values pertama)
        $pdo->prepare($sql_mutasi)->execute([$no_rawat]);

        // Audit Mutasi Berkas
        catat_tracker($pdo, "UPSERT mutasi_berkas STATUS='Sudah Kembali' WHERE no_rawat='$no_rawat'");

        header("Location: index.php?status=sukses_update_status");
        exit();
    }

    // --- DELETE CPPT ---
    elseif ($act == 'hapus_cppt') {
        $no_rawat = $_GET['no_rawat']; $tgl = $_GET['tgl']; $jam = $_GET['jam'];
        $del = $pdo->prepare("DELETE FROM pemeriksaan_ralan WHERE no_rawat=? AND tgl_perawatan=? AND jam_rawat=? AND nip=?");
        $del->execute([$no_rawat, $tgl, $jam, $nip_dokter]);
        
        if ($del->rowCount() > 0) {
            catat_tracker($pdo, "DELETE FROM pemeriksaan_ralan WHERE no_rawat='$no_rawat' AND jam='$jam'");
            header("Location: index.php?status=terhapus");
        } else {
            die("Gagal menghapus. Data tidak ditemukan atau Anda bukan pemilik data tersebut.");
        }
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
