<?php
// [2026-01-27] Worker ERM - Mengolah data dan mengirim ke BPJS
// Output: JSON untuk dibaca oleh index.php

require_once 'erm_config.php';
require_once 'ErmBridging.php';

header('Content-Type: application/json');

$bpjs = new ErmBridging();
$responseLog = [];
$processedCount = 0;

// Parameter dari Dashboard
$mode = isset($_POST['mode']) ? $_POST['mode'] : 'auto';
$limit = 1; // Kirim 1 per satu request agar log terlihat jalan ("Terminal Effect")

// 1. QUERY BUILDER
$sql = "SELECT bs.no_sep, bs.no_rawat, bs.jnspelayanan, bs.tglsep, 
               p.no_rkm_medis, p.nm_pasien, p.no_ktp, p.jk, p.tgl_lahir, p.alamat,
               rp.tgl_registrasi, rp.jam_reg,
               d.nm_dokter, d.no_ijn_praktek, d.no_ktp as nik_dokter,
               s.nama_instansi, s.kode_ppk,
               st.status_kirim
        FROM bridging_sep bs
        JOIN reg_periksa rp ON bs.no_rawat = rp.no_rawat
        JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        JOIN dokter d ON rp.kd_dokter = d.kd_dokter
        CROSS JOIN setting s
        -- JOIN ke Table Status Baru (LEFT JOIN agar yang belum ada tetap terambil)
        LEFT JOIN bridging_erm_status st ON bs.no_sep = st.no_sep
        WHERE 1=1 ";

// Filter Berdasarkan Mode
if ($mode == 'manual' && !empty($_POST['no_sep'])) {
    // Mode Kirim Ulang Satu SEP
    $sql .= " AND bs.no_sep = '" . $_POST['no_sep'] . "'";
} elseif ($mode == 'periode') {
    // Mode Tanggal Mundur
    $tgl1 = $_POST['tgl_mulai'];
    $tgl2 = $_POST['tgl_akhir'];
    $sql .= " AND bs.tglsep BETWEEN '$tgl1' AND '$tgl2' 
              AND (st.status_kirim IS NULL OR st.status_kirim != 'Sudah')";
} else {
    // Mode Auto (24 Jam) - Ambil hari ini yang belum terkirim
    // Atau ambil data lama yang belum terkirim (prioritas data terbaru)
    $sql .= " AND (st.status_kirim IS NULL OR st.status_kirim != 'Sudah') 
              ORDER BY bs.tglsep DESC";
}

$sql .= " LIMIT $limit";

$stmt = $pdo->query($sql);
$dataSep = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($dataSep) == 0) {
    echo json_encode(['status' => 'idle', 'message' => 'Tidak ada data antrian untuk dikirim.']);
    exit;
}

// 2. LOOPING DATA (Sebenarnya cuma 1 karena limit 1)
foreach ($dataSep as $row) {
    $noSep = $row['no_sep'];
    $noRawat = $row['no_rawat'];
    
    // --- AMBIL DATA MEDIS (DIAGNOSA) ---
    $qDiag = $pdo->prepare("SELECT py.kd_penyakit, py.nm_penyakit, dp.prioritas 
                            FROM diagnosa_pasien dp 
                            JOIN penyakit py ON dp.kd_penyakit = py.kd_penyakit 
                            WHERE dp.no_rawat = ? ORDER BY dp.prioritas ASC");
    $qDiag->execute([$noRawat]);
    $diagnosas = $qDiag->fetchAll(PDO::FETCH_ASSOC);

    // --- RAKIT FHIR BUNDLE ---
    // Generate UUIDs
    $uuidPasien = "urn:uuid:" . gen_uuid();
    $uuidDokter = "urn:uuid:" . gen_uuid();
    $uuidEncounter = "urn:uuid:" . gen_uuid();
    $uuidOrg = "urn:uuid:" . gen_uuid();

    // Struktur Dasar
    $bundle = [
        "resourceType" => "Bundle",
        "id" => "bundle-" . str_replace(" ", "", $noSep),
        "type" => "document",
        "timestamp" => date('c'),
        "entry" => []
    ];

    // --> Layer: Composition
    $diagText = "";
    $diagEntries = [];
    $conditionResources = [];
    
    foreach($diagnosas as $d) {
        $uuidCond = "urn:uuid:" . gen_uuid();
        $diagText .= $d['nm_penyakit'] . ", ";
        $diagEntries[] = ["reference" => $uuidCond];
        
        $conditionResources[] = [
            "fullUrl" => $uuidCond,
            "resource" => [
                "resourceType" => "Condition",
                "code" => ["coding" => [["system" => "http://hl7.org/fhir/sid/icd-10", "code" => $d['kd_penyakit'], "display" => $d['nm_penyakit']]]],
                "subject" => ["reference" => $uuidPasien],
                "encounter" => ["reference" => $uuidEncounter],
                "rank" => (int)$d['prioritas']
            ]
        ];
    }

    $composition = [
        "resource" => [
            "resourceType" => "Composition",
            "status" => "final",
            "type" => ["coding" => [["system" => "http://loinc.org", "code" => "18842-5", "display" => "Discharge Summary"]]],
            "subject" => ["reference" => $uuidPasien, "display" => $row['nm_pasien']],
            "encounter" => ["reference" => $uuidEncounter],
            "date" => date('c'),
            "author" => [["reference" => $uuidDokter, "display" => $row['nm_dokter']]],
            "title" => "Resume Medis",
            "section" => [[
                "title" => "Diagnosis",
                "code" => ["coding" => [["system" => "http://loinc.org", "code" => "11535-2", "display" => "Hospital Discharge Diagnosis"]]],
                "text" => ["status" => "generated", "div" => "<div>" . rtrim($diagText, ", ") . "</div>"],
                "entry" => $diagEntries
            ]]
        ]
    ];
    $bundle['entry'][] = $composition;

    // --> Layer: Patient
    $bundle['entry'][] = [
        "fullUrl" => $uuidPasien,
        "resource" => [
            "resourceType" => "Patient",
            "identifier" => [
                ["system" => "http://bpjs-kesehatan.go.id/id/no-ktp", "value" => $row['no_ktp']],
                ["system" => "http://bpjs-kesehatan.go.id/id/no-peserta", "value" => $row['no_peserta'] ?? '-'], // Handle null
                ["system" => "http://rs-anda.co.id/rm", "value" => $row['no_rkm_medis']]
            ],
            "name" => [["use" => "official", "text" => $row['nm_pasien']]],
            "gender" => ($row['jk'] == 'L' ? 'male' : 'female'),
            "birthDate" => $row['tgl_lahir']
        ]
    ];

    // --> Layer: Practitioner
    $bundle['entry'][] = [
        "fullUrl" => $uuidDokter,
        "resource" => [
            "resourceType" => "Practitioner",
            "identifier" => [["system" => "http://bpjs-kesehatan.go.id/id/nik", "value" => $row['nik_dokter']]],
            "name" => [["use" => "official", "text" => $row['nm_dokter']]]
        ]
    ];
    
    // --> Layer: Organization
    $bundle['entry'][] = [
        "fullUrl" => $uuidOrg,
        "resource" => [
            "resourceType" => "Organization",
            "identifier" => [["system" => "http://bpjs-kesehatan.go.id/id/kode-faskes", "value" => $row['kode_ppk']]],
            "name" => $row['nama_instansi']
        ]
    ];

    // --> Layer: Encounter
    $bundle['entry'][] = [
        "fullUrl" => $uuidEncounter,
        "resource" => [
            "resourceType" => "Encounter",
            "identifier" => [["system" => "http://bpjs-kesehatan.go.id/id/sep", "value" => $noSep]],
            "status" => "finished",
            "class" => [
                "system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                "code" => ($row['jnspelayanan'] == '1' ? 'IMP' : 'AMB'),
                "display" => ($row['jnspelayanan'] == '1' ? 'Inpatient' : 'Ambulatory')
            ],
            "subject" => ["reference" => $uuidPasien],
            "period" => ["start" => date('c', strtotime($row['tgl_registrasi'].' '.$row['jam_reg']))],
            "serviceProvider" => ["reference" => $uuidOrg]
        ]
    ];

    // --> Layer: Conditions
    foreach($conditionResources as $cond) {
        $bundle['entry'][] = $cond;
    }

    // --- ENKRIPSI & KIRIM ---
    $jsonRaw = json_encode($bundle);
    $encryptedMR = $bpjs->encryptData($jsonRaw);

    $logMsg = "";
    $statusKirim = "Gagal";

    if ($encryptedMR) {
        $reqBody = json_encode([
            "request" => [
                "noSep" => $noSep,
                "jnsPelayanan" => $row['jnspelayanan'],
                "bulan" => date('m', strtotime($row['tglsep'])),
                "tahun" => date('Y', strtotime($row['tglsep'])),
                "dataMR" => $encryptedMR
            ]
        ]);

        $resApi = $bpjs->postRequest('/eclaim/rekammedis/insert', $reqBody);
        $resJson = json_decode($resApi, true);
        
        $code = isset($resJson['metadata']['code']) ? $resJson['metadata']['code'] : '500';
        $msg = isset($resJson['metadata']['message']) ? $resJson['metadata']['message'] : $resApi;

        if ($code == '200') {
            $statusKirim = "Sudah";
            $logMsg = "[SUKSES] $noSep - Terkirim.";
        } else {
            $statusKirim = "Gagal";
            $logMsg = "[GAGAL] $noSep - Code: $code ($msg)";
        }
    } else {
        $logMsg = "[ERROR] $noSep - Gagal Enkripsi.";
        $resApi = "Encrypt Failed";
    }

    // --- SIMPAN KE TABEL STATUS BARU ---
    // Gunakan INSERT ON DUPLICATE KEY UPDATE agar aman untuk kirim ulang
    $sqlIns = "INSERT INTO bridging_erm_status (no_sep, no_rawat, status_kirim, waktu_kirim, respon_bpjs) 
               VALUES (?, ?, ?, NOW(), ?) 
               ON DUPLICATE KEY UPDATE status_kirim=?, waktu_kirim=NOW(), respon_bpjs=?";
    $stmtIns = $pdo->prepare($sqlIns);
    $stmtIns->execute([$noSep, $noRawat, $statusKirim, $resApi, $statusKirim, $resApi]);

    $responseLog[] = $logMsg;
    $processedCount++;
}

echo json_encode(['status' => 'success', 'processed' => $processedCount, 'logs' => $responseLog]);

// Helper UUID for PHP 7.3
function gen_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
?>