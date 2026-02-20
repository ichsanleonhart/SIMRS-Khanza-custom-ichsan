<?php
// File: modules/edokter/ranap/proses_resep.php
require_once '../../../config/config.php';
require_once '../../../config/database.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi login habis. Silakan login ulang.']); 
    exit;
}

$nip_dokter = $_SESSION['user_id'];
$action = $_POST['action'] ?? ''; 

function catat_trackersql($pdo, $sqle, $usere) {
    try { $pdo->prepare("INSERT INTO trackersql (tanggal, sqle, usere) VALUES (NOW(), ?, ?)")->execute([$sqle, $usere]); } catch (Exception $e) { }
}

function simpan_aturan($pdo, $aturan) {
    if(!empty(trim($aturan))) {
        try { $pdo->prepare("INSERT IGNORE INTO master_aturan_pakai (aturan) VALUES (?)")->execute([trim($aturan)]); } catch (Exception $e) {}
    }
}

try {
    // =========================================================================
    // 1. SIMPAN RESEP REGULER (UMUM & RACIKAN)
    // =========================================================================
    if ($action === 'simpan_resep') {
        $no_rawat = $_POST['no_rawat'];
        
        $tgl_sekarang = date('Y-m-d');
        $prefix = date('Ymd');
        
        $stmt_max = $pdo->prepare("SELECT MAX(RIGHT(no_resep, 4)) as max_id FROM resep_obat WHERE tgl_peresepan = ?");
        $stmt_max->execute([$tgl_sekarang]);
        $row_max = $stmt_max->fetch();
        
        $last_id = $row_max['max_id'] ? (int)$row_max['max_id'] : 0;
        $no_resep = $prefix . str_pad($last_id + 1, 4, '0', STR_PAD_LEFT);

        $jam_sekarang = date('H:i:s');
        $txt_resep_cppt = "Resep Obat:\n"; 
        
        $pdo->beginTransaction(); 

        // Insert Header Resep Obat (Status RANAP)
        $sql_header = "INSERT INTO resep_obat (no_resep, tgl_perawatan, jam, no_rawat, kd_dokter, tgl_peresepan, jam_peresepan, status, tgl_penyerahan, jam_penyerahan) VALUES (?, '0000-00-00', '00:00:00', ?, ?, ?, ?, 'ranap', '0000-00-00', '00:00:00')";
        $pdo->prepare($sql_header)->execute([$no_resep, $no_rawat, $nip_dokter, $tgl_sekarang, $jam_sekarang]);
        catat_trackersql($pdo, "INSERT INTO resep_obat VALUES ('$no_resep', '0000-00-00', '00:00:00', '$no_rawat', '$nip_dokter', '$tgl_sekarang', '$jam_sekarang', 'ranap', '0000-00-00', '00:00:00')", $nip_dokter);

        // Insert Detail Obat Umum
        if (!empty($_POST['umum_kd_brng'])) {
            $stmt_umum = $pdo->prepare("INSERT INTO resep_dokter (no_resep, kode_brng, jml, aturan_pakai) VALUES (?, ?, ?, ?)");
            foreach ($_POST['umum_kd_brng'] as $key => $kd_brng) {
                $jml = $_POST['umum_jml'][$key];
                $aturan = $_POST['umum_aturan'][$key];
                
                $stmt_umum->execute([$no_resep, $kd_brng, $jml, $aturan]);
                simpan_aturan($pdo, $aturan);
                catat_trackersql($pdo, "INSERT INTO resep_dokter VALUES ('$no_resep', '$kd_brng', '$jml', '$aturan')", $nip_dokter);
                
                $nm_obat = $pdo->query("SELECT nama_brng FROM databarang WHERE kode_brng='$kd_brng'")->fetchColumn();
                $txt_resep_cppt .= "- $nm_obat (Jml: $jml) Aturan: $aturan\n";
            }
        }

        // Insert Header Obat Racikan
        if (!empty($_POST['racik_no'])) {
            $stmt_racik = $pdo->prepare("INSERT INTO resep_dokter_racikan (no_resep, no_racik, nama_racik, kd_racik, jml_dr, aturan_pakai, keterangan) VALUES (?, ?, ?, ?, ?, ?, '-')");
            foreach ($_POST['racik_no'] as $key => $no_racik) {
                $nama = $_POST['racik_nama'][$key];
                $aturan = $_POST['racik_aturan'][$key];
                
                $stmt_racik->execute([$no_resep, $no_racik, $nama, $_POST['racik_kd_metode'][$key], $_POST['racik_jml'][$key], $aturan]);
                simpan_aturan($pdo, $aturan);
                catat_trackersql($pdo, "INSERT INTO resep_dokter_racikan VALUES ('$no_resep', '$no_racik', '$nama', '{$_POST['racik_kd_metode'][$key]}', '{$_POST['racik_jml'][$key]}', '$aturan', '-')", $nip_dokter);
                
                $txt_resep_cppt .= "- Racikan: $nama (Jml: {$_POST['racik_jml'][$key]}) Aturan: $aturan\n";
            }
        }

        // Insert Detail Obat Racikan
        if (!empty($_POST['racik_detail_no'])) {
            $stmt_det = $pdo->prepare("INSERT INTO resep_dokter_racikan_detail (no_resep, no_racik, kode_brng, p1, p2, kandungan, jml) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($_POST['racik_detail_no'] as $key => $no_racik) {
                $kd_brng = $_POST['racik_detail_kd_brng'][$key];
                $p1 = $_POST['racik_detail_p1'][$key];
                $p2 = $_POST['racik_detail_p2'][$key];
                $kand = $_POST['racik_detail_kandungan'][$key];
                $jml = $_POST['racik_detail_jml'][$key];

                $stmt_det->execute([$no_resep, $no_racik, $kd_brng, $p1, $p2, $kand, $jml]);
                catat_trackersql($pdo, "INSERT INTO resep_dokter_racikan_detail VALUES ('$no_resep', '$no_racik', '$kd_brng', '$p1', '$p2', '$kand', '$jml')", $nip_dokter);
            }
        }

        $pdo->commit(); 
        echo json_encode(['status' => 'success', 'no_resep' => $no_resep, 'txt_resep' => $txt_resep_cppt]);
        exit;
    }

    // =========================================================================
    // 2. APPEND TEKS RESEP KE CPPT RANAP
    // =========================================================================
    elseif ($action === 'append_cppt') {
        $no_rawat = $_POST['no_rawat'];
        $txt_resep = $_POST['txt_resep'];
        
        $sql_cek = "SELECT tgl_perawatan, jam_rawat, rtl FROM pemeriksaan_ranap WHERE no_rawat=? AND nip=? ORDER BY tgl_perawatan DESC, jam_rawat DESC LIMIT 1";
        $stmt_cek = $pdo->prepare($sql_cek);
        $stmt_cek->execute([$no_rawat, $nip_dokter]);
        $last_cppt = $stmt_cek->fetch();
        
        if ($last_cppt) {
            $new_rtl = $last_cppt['rtl'] . "\n\n" . trim($txt_resep);
            $pdo->prepare("UPDATE pemeriksaan_ranap SET rtl=? WHERE no_rawat=? AND tgl_perawatan=? AND jam_rawat=? AND nip=?")->execute([$new_rtl, $no_rawat, $last_cppt['tgl_perawatan'], $last_cppt['jam_rawat'], $nip_dokter]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'CPPT Rawat Inap belum dibuat. Harap isi CPPT terlebih dahulu.']);
        }
        exit;
    }

    // =========================================================================
    // 3. SIMPAN PERMINTAAN RESEP PULANG (DRAFT APOTEK)
    // =========================================================================
    elseif ($action === 'simpan_resep_pulang') {
        $no_rawat = $_POST['no_rawat'];
        $tgl_sekarang = date('Y-m-d');
        $jam_sekarang = date('H:i:s');
        
        $pdo->beginTransaction(); 
        
        // Generate No Permintaan (Sesuai Khanza: YYYYMMDDXXXX)
        $stmt_max = $pdo->prepare("SELECT MAX(RIGHT(no_permintaan, 4)) as max_id FROM permintaan_resep_pulang WHERE tgl_permintaan = ?");
        $stmt_max->execute([$tgl_sekarang]);
        $row_max = $stmt_max->fetch();
        
        $last_id = $row_max['max_id'] ? (int)$row_max['max_id'] : 0;
        $no_permintaan = date('Ymd') . str_pad($last_id + 1, 4, '0', STR_PAD_LEFT);

        // Insert Header Permintaan Resep Pulang
        $sql_head = "INSERT INTO permintaan_resep_pulang (no_permintaan, tgl_permintaan, jam, no_rawat, kd_dokter, status, tgl_validasi, jam_validasi) VALUES (?, ?, ?, ?, ?, 'Belum', '0000-00-00', '00:00:00')";
        $pdo->prepare($sql_head)->execute([$no_permintaan, $tgl_sekarang, $jam_sekarang, $no_rawat, $nip_dokter]);
        catat_trackersql($pdo, "INSERT INTO permintaan_resep_pulang VALUES ('$no_permintaan', '$tgl_sekarang', '$jam_sekarang', '$no_rawat', '$nip_dokter', 'Belum', '0000-00-00', '00:00:00')", $nip_dokter);

        // Insert Detail Obat Pulang
        if (!empty($_POST['pulang_kd_brng'])) {
            $stmt_det = $pdo->prepare("INSERT INTO detail_permintaan_resep_pulang (no_permintaan, kode_brng, jml, dosis) VALUES (?, ?, ?, ?)");
            foreach ($_POST['pulang_kd_brng'] as $key => $kd_brng) {
                $jml = $_POST['pulang_jml'][$key];
                $aturan = $_POST['pulang_aturan'][$key];
                
                $stmt_det->execute([$no_permintaan, $kd_brng, $jml, $aturan]);
                simpan_aturan($pdo, $aturan); // Simpan template aturan
                catat_trackersql($pdo, "INSERT INTO detail_permintaan_resep_pulang VALUES ('$no_permintaan', '$kd_brng', '$jml', '$aturan')", $nip_dokter);
            }
        }

        $pdo->commit(); 
        echo json_encode(['status' => 'success', 'message' => 'Resep Pulang Terkirim']);
        exit;
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>