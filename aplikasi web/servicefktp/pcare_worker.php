<?php
// [2025-11-16] Selalu beri komentar.
// File: pcare_worker.php
// Fungsi: Worker PCare dengan Smart Merge & VERBOSE ERROR LOGGING.

set_time_limit(0);
ini_set('memory_limit', '-1');

require_once 'pcare_helper.php';

$pcare = new PcareService();
$responseLog = [];

$mode = 'auto';
$targetNoRawat = '';
$tglMulai = date('Y-m-d', strtotime('-7 days'));
$tglAkhir = date('Y-m-d');

if (isset($_POST['mode'])) {
    $mode = $_POST['mode'];
    if ($mode == 'manual' && isset($_POST['no_rawat'])) $targetNoRawat = $_POST['no_rawat'];
    if ($mode == 'sapu_bersih' && isset($_POST['tgl_mulai']) && isset($_POST['tgl_akhir'])) {
        $tglMulai = $_POST['tgl_mulai'];
        $tglAkhir = $_POST['tgl_akhir'];
    }
}

// Fungsi Cek Validitas Data (Untuk Merge)
function isValidValue($val) {
    if (is_null($val)) return false;
    $clean = trim($val);
    if ($clean === '') return false;
    if ($clean === '0') return false;
    if ($clean === '0/0') return false;
    if ($clean === '-') return false;
    return true;
}

try {
    // 1. QUERY CANDIDATE
    $sqlFilter = "SELECT rp.no_rawat 
                  FROM reg_periksa rp
                  INNER JOIN pcare_pendaftaran pp ON rp.no_rawat = pp.no_rawat
                  LEFT JOIN pcare_kunjungan_umum pku ON rp.no_rawat = pku.no_rawat
                  WHERE pp.status = 'Terkirim' 
                  AND (pku.noKunjungan IS NULL OR pku.noKunjungan = '' OR pku.status != 'Terkirim') ";
    
    $params = [];

    if ($mode == 'manual') {
        $sqlFilter .= " AND rp.no_rawat = :norawat";
        $params['norawat'] = $targetNoRawat;
    } else {
        $sqlFilter .= " AND rp.tgl_registrasi BETWEEN :tgl1 AND :tgl2";
        $params['tgl1'] = $tglMulai;
        $params['tgl2'] = $tglAkhir;
        if ($mode == 'auto') $sqlFilter .= " LIMIT 20";
    }

    $stmt = $pdo->prepare($sqlFilter);
    $stmt->execute($params);
    $candidates = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($candidates)) {
        $msg = ($mode == 'sapu_bersih') ? "Selesai. Tidak ada data pending." : "Idle.";
        echo json_encode(['status' => 'idle', 'message' => $msg]);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($candidates), '?'));

    // 2. QUERY DETAIL (ORDER BY WAKTU ASC UNTUK MERGE)
    $sqlDetail = "SELECT 
                    rp.no_rawat, rp.tgl_registrasi, rp.status_lanjut,
                    pp.noKartu, pp.kdPoli, pp.noUrut,
                    dp.kd_penyakit as kdDiag1, 
                    (SELECT kd_penyakit FROM diagnosa_pasien WHERE no_rawat = rp.no_rawat AND prioritas = 2 LIMIT 1) as kdDiag2,
                    (SELECT kd_penyakit FROM diagnosa_pasien WHERE no_rawat = rp.no_rawat AND prioritas = 3 LIMIT 1) as kdDiag3,
                    pr.tensi, pr.berat, pr.tinggi, pr.respirasi, pr.nadi, pr.suhu_tubuh,
                    pr.keluhan, pr.kesadaran, pr.lingkar_perut,
                    mdp.kd_dokter_pcare
                FROM reg_periksa rp
                INNER JOIN pcare_pendaftaran pp ON rp.no_rawat = pp.no_rawat 
                INNER JOIN diagnosa_pasien dp ON rp.no_rawat = dp.no_rawat AND dp.prioritas = 1
                INNER JOIN maping_dokter_pcare mdp ON rp.kd_dokter = mdp.kd_dokter
                INNER JOIN pemeriksaan_ralan pr ON rp.no_rawat = pr.no_rawat
                WHERE rp.no_rawat IN ($placeholders)
                ORDER BY rp.no_rawat, pr.tgl_perawatan ASC, pr.jam_rawat ASC"; 

    $stmtDetail = $pdo->prepare($sqlDetail);
    $stmtDetail->execute($candidates);
    $rawRows = $stmtDetail->fetchAll();

    // 3. SMART MERGE (Last Valid Value Wins)
    $mergedData = [];
    foreach ($rawRows as $row) {
        $nr = $row['no_rawat'];
        if (!isset($mergedData[$nr])) {
            $mergedData[$nr] = $row;
        } else {
            // Overwrite jika data baru valid
            if (isValidValue($row['tensi'])) $mergedData[$nr]['tensi'] = $row['tensi'];
            if (isValidValue($row['berat'])) $mergedData[$nr]['berat'] = $row['berat'];
            if (isValidValue($row['tinggi'])) $mergedData[$nr]['tinggi'] = $row['tinggi'];
            if (isValidValue($row['respirasi'])) $mergedData[$nr]['respirasi'] = $row['respirasi'];
            if (isValidValue($row['nadi'])) $mergedData[$nr]['nadi'] = $row['nadi'];
            if (isValidValue($row['suhu_tubuh'])) $mergedData[$nr]['suhu_tubuh'] = $row['suhu_tubuh'];
            if (isValidValue($row['lingkar_perut'])) $mergedData[$nr]['lingkar_perut'] = $row['lingkar_perut'];
            if (isValidValue($row['keluhan'])) $mergedData[$nr]['keluhan'] = $row['keluhan'];
            if (isValidValue($row['kesadaran'])) $mergedData[$nr]['kesadaran'] = $row['kesadaran'];
        }
    }

    $processed = 0;
    foreach ($mergedData as $row) {
        
        // --- DATA PREP ---
        $sistole = 0; $diastole = 0;
        if (!empty($row['tensi'])) {
            $cleanTensi = preg_replace("/[^0-9\/]/", "", $row['tensi']);
            $parts = explode('/', $cleanTensi);
            if (count($parts) >= 2) {
                $sistole = intval($parts[0]);
                $diastole = intval($parts[1]);
            } else {
                $sistole = intval($parts[0]);
            }
        }

        // Safety Filter Log
        if ($sistole == 0 || $diastole == 0) {
            $responseLog[] = "[SKIP] " . $row['no_rawat'] . ": Tensi 0/0 atau Invalid (Hasil Merge).";
            continue; 
        }

        $kdSadar = "01"; 
        if (stripos($row['kesadaran'], 'Sopor') !== false) $kdSadar = "03";
        if (stripos($row['kesadaran'], 'Coma') !== false) $kdSadar = "04";

        $suhu = !empty($row['suhu_tubuh']) ? $row['suhu_tubuh'] : "36";
        $tglFix = date('d-m-Y', strtotime($row['tgl_registrasi']));

        $payload = array(
            "noKunjungan" => null,
            "noKartu" => trim($row['noKartu']),
            "tglDaftar" => $tglFix,
            "kdPoli" => trim($row['kdPoli']),
            "keluhan" => !empty($row['keluhan']) ? substr($row['keluhan'], 0, 400) : "Tidak Ada",
            "kdSadar" => $kdSadar,
            "sistole" => $sistole,
            "diastole" => $diastole,
            "beratBadan" => intval($row['berat']),
            "tinggiBadan" => intval($row['tinggi']),
            "respRate" => intval($row['respirasi']),
            "heartRate" => intval($row['nadi']),
            "lingkarPerut" => intval($row['lingkar_perut']),
            "kdStatusPulang" => "3",
            "tglPulang" => $tglFix,
            "kdDokter" => trim($row['kd_dokter_pcare']),
            "kdDiag1" => trim($row['kdDiag1']),
            "kdDiag2" => !empty($row['kdDiag2']) ? $row['kdDiag2'] : null,
            "kdDiag3" => !empty($row['kdDiag3']) ? $row['kdDiag3'] : null,
            "kdPoliRujukInternal" => null,
            "rujukLanjut" => null,
            "kdTacc" => -1,
            "alasanTacc" => null,
            "anamnesa" => !empty($row['keluhan']) ? substr($row['keluhan'], 0, 400) : "Tidak Ada",
            "alergiMakan" => "00", 
            "alergiUdara" => "00", 
            "alergiObat" => "00", 
            "kdPrognosa" => "01",
            "terapiObat" => "Tidak Ada", 
            "terapiNonObat" => "Tidak Ada", 
            "bmhp" => "Tidak Ada",
            "suhu" => $suhu
        );

        $logInfo = "NoRawat: " . $row['no_rawat'];
        $resp = $pcare->request('kunjungan/V1', 'POST', $payload, $logInfo);
        
        $code = $resp['metaData']['code'] ?? 0;
        $message = $resp['metaData']['message'] ?? '';
        
        if ($code == 201) {
            // ... (Kode sukses sama seperti sebelumnya) ...
            $responseData = $resp['response'];
            $noKunjunganBaru = '';
            if (is_array($responseData)) {
                if (isset($responseData['noKunjungan'])) $noKunjunganBaru = $responseData['noKunjungan'];
                elseif (isset($responseData['message'])) $noKunjunganBaru = $responseData['message'];
                elseif (isset($responseData[0]) && !is_array($responseData[0])) $noKunjunganBaru = $responseData[0];
            } else {
                $noKunjunganBaru = $responseData;
            }

            if (!empty($noKunjunganBaru) && strlen($noKunjunganBaru) > 5) {
                // UPDATE
                $cek = $pdo->prepare("SELECT no_rawat FROM pcare_kunjungan_umum WHERE no_rawat = ?");
                $cek->execute([$row['no_rawat']]);
                if ($cek->rowCount() > 0) {
                    $upd = $pdo->prepare("UPDATE pcare_kunjungan_umum SET noKunjungan=?, status='Terkirim' WHERE no_rawat=?");
                    $upd->execute([$noKunjunganBaru, $row['no_rawat']]);
                } else {
                    $pasienData = $pdo->query("SELECT p.nm_pasien, rp.no_rkm_medis FROM reg_periksa rp JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis WHERE rp.no_rawat='{$row['no_rawat']}'")->fetch();
                    $stmtIns = $pdo->prepare("INSERT INTO pcare_kunjungan_umum (no_rawat, noKunjungan, tglDaftar, no_rkm_medis, nm_pasien, noKartu, kdPoli, keluhan, kdSadar, sistole, diastole, beratBadan, tinggiBadan, respRate, heartRate, lingkarPerut, kdStatusPulang, tglPulang, kdDokter, kdDiag1, status, bmhp, terapi_non_obat, KdAlergiMakanan, KdAlergiUdara, KdAlergiObat, KdPrognosa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '3', ?, ?, ?, 'Terkirim', 'Tidak Ada', 'Tidak Ada', '00', '00', '00', '01')");
                    $stmtIns->execute([$row['no_rawat'], $noKunjunganBaru, $row['tgl_registrasi'], $pasienData['no_rkm_medis'], $pasienData['nm_pasien'], $row['noKartu'], $row['kdPoli'], $row['keluhan'], $kdSadar, $sistole, $diastole, $row['berat'], $row['tinggi'], $row['respirasi'], $row['nadi'], $row['lingkar_perut'], $row['tgl_registrasi'], $row['kd_dokter_pcare'], $row['kdDiag1']]);
                }
                $responseLog[] = "[SUKSES] " . $row['no_rawat'] . " -> " . $noKunjunganBaru;
                $processed++;
            } 
        } else {
            // [RESTORED] Tampilkan detail response BODY dari BPJS agar bisa forensik
            $detailErr = "";
            if (isset($resp['response'])) {
                $detailErr = " | DETAIL: " . (is_string($resp['response']) ? $resp['response'] : json_encode($resp['response']));
            }
            $responseLog[] = "[GAGAL $code] " . $row['no_rawat'] . ": " . $message . $detailErr;
        }
    }
    
    echo json_encode(['status' => 'success', 'processed' => $processed, 'logs' => $responseLog]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>