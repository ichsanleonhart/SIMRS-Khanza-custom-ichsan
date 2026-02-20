<?php
// File: modules/edokter/ralan/proses_resep.php
require_once '../../../config/config.php';
require_once '../../../config/database.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi login habis.']); exit;
}

$nip_dokter = $_SESSION['user_id'];
$action = $_POST['action'] ?? 'simpan_resep'; 

function catat_trackersql($pdo, $sqle, $usere) {
    try { $pdo->prepare("INSERT INTO trackersql (tanggal, sqle, usere) VALUES (NOW(), ?, ?)")->execute([$sqle, $usere]); } catch (Exception $e) { }
}

// Helper: Auto-save Aturan Pakai Baru
function simpan_aturan($pdo, $aturan) {
    if(!empty(trim($aturan))) {
        try { $pdo->prepare("INSERT IGNORE INTO master_aturan_pakai (aturan) VALUES (?)")->execute([trim($aturan)]); } catch (Exception $e) {}
    }
}

try {
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

        // 1. INSERT HEADER
        $sql_header = "INSERT INTO resep_obat (no_resep, tgl_perawatan, jam, no_rawat, kd_dokter, tgl_peresepan, jam_peresepan, status, tgl_penyerahan, jam_penyerahan) VALUES (?, '0000-00-00', '00:00:00', ?, ?, ?, ?, 'ralan', '0000-00-00', '00:00:00')";
        $pdo->prepare($sql_header)->execute([$no_resep, $no_rawat, $nip_dokter, $tgl_sekarang, $jam_sekarang]);
        catat_trackersql($pdo, "INSERT INTO resep_obat VALUES ('$no_resep', '0000-00-00', '00:00:00', '$no_rawat', '$nip_dokter', '$tgl_sekarang', '$jam_sekarang', 'ralan', '0000-00-00', '00:00:00')", $nip_dokter);

        // 2. INSERT OBAT UMUM
        if (!empty($_POST['umum_kd_brng'])) {
            $stmt_umum = $pdo->prepare("INSERT INTO resep_dokter (no_resep, kode_brng, jml, aturan_pakai) VALUES (?, ?, ?, ?)");
            foreach ($_POST['umum_kd_brng'] as $key => $kd_brng) {
                $jml = $_POST['umum_jml'][$key];
                $aturan = $_POST['umum_aturan'][$key];
                
                $stmt_umum->execute([$no_resep, $kd_brng, $jml, $aturan]);
                simpan_aturan($pdo, $aturan); // Simpan aturan jika baru
                catat_trackersql($pdo, "INSERT INTO resep_dokter VALUES ('$no_resep', '$kd_brng', '$jml', '$aturan')", $nip_dokter);
                
                $nm_obat = $pdo->query("SELECT nama_brng FROM databarang WHERE kode_brng='$kd_brng'")->fetchColumn();
                $txt_resep_cppt .= "- $nm_obat (Jml: $jml) Aturan: $aturan\n";
            }
        }

        // 3. INSERT OBAT RACIKAN (HEADER)
        if (!empty($_POST['racik_no'])) {
            $stmt_racik = $pdo->prepare("INSERT INTO resep_dokter_racikan (no_resep, no_racik, nama_racik, kd_racik, jml_dr, aturan_pakai, keterangan) VALUES (?, ?, ?, ?, ?, ?, '-')");
            foreach ($_POST['racik_no'] as $key => $no_racik) {
                $nama = $_POST['racik_nama'][$key];
                $aturan = $_POST['racik_aturan'][$key];
                
                $stmt_racik->execute([$no_resep, $no_racik, $nama, $_POST['racik_kd_metode'][$key], $_POST['racik_jml'][$key], $aturan]);
                simpan_aturan($pdo, $aturan); // Simpan aturan jika baru
                catat_trackersql($pdo, "INSERT INTO resep_dokter_racikan VALUES ('$no_resep', '$no_racik', '$nama', '{$_POST['racik_kd_metode'][$key]}', '{$_POST['racik_jml'][$key]}', '$aturan', '-')", $nip_dokter);
                
                $txt_resep_cppt .= "- Racikan: $nama (Jml: {$_POST['racik_jml'][$key]}) Aturan: $aturan\n";
            }
        }

        // 4. INSERT OBAT RACIKAN (DETAIL)
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

    // APPEND CPPT
    elseif ($action === 'append_cppt') {
        $no_rawat = $_POST['no_rawat'];
        $txt_resep = $_POST['txt_resep'];
        
        $sql_cek = "SELECT tgl_perawatan, jam_rawat, rtl FROM pemeriksaan_ralan WHERE no_rawat=? AND nip=? ORDER BY tgl_perawatan DESC, jam_rawat DESC LIMIT 1";
        $stmt_cek = $pdo->prepare($sql_cek);
        $stmt_cek->execute([$no_rawat, $nip_dokter]);
        $last_cppt = $stmt_cek->fetch();
        
        if ($last_cppt) {
            $new_rtl = $last_cppt['rtl'] . "\n\n" . trim($txt_resep);
            $pdo->prepare("UPDATE pemeriksaan_ralan SET rtl=? WHERE no_rawat=? AND tgl_perawatan=? AND jam_rawat=? AND nip=?")->execute([$new_rtl, $no_rawat, $last_cppt['tgl_perawatan'], $last_cppt['jam_rawat'], $nip_dokter]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'CPPT belum dibuat.']);
        }
        exit;
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>