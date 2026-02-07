<?php
// [2026-02-07] REVISI TOTAL: SOPHISTICATED RESEND WORKER
// File: resend_worker.php
// Fungsi: Mengirim ulang data antrean (Add, Hadir, Batal) dengan validasi ketat & debug detail.

set_time_limit(0);
ini_set('memory_limit', '-1');

require_once 'bpjs_helper.php';

$bpjs = new BpjsService();
$log = [];

if (!isset($_POST['tgl_mulai']) || !isset($_POST['tgl_akhir'])) {
    echo json_encode(['status' => 'error', 'message' => 'Tanggal harus diisi!']);
    exit;
}

$tglMulai = $_POST['tgl_mulai'];
$tglAkhir = $_POST['tgl_akhir'];

function getHariIndonesia($date) {
    $days = [
        'Sunday' => 'AKHAD', 'Monday' => 'SENIN', 'Tuesday' => 'SELASA',
        'Wednesday' => 'RABU', 'Thursday' => 'KAMIS', 'Friday' => 'JUMAT', 'Saturday' => 'SABTU'
    ];
    return $days[date('l', strtotime($date))];
}

function logTracker($pdo, $msg) {
    try {
        $stmt = $pdo->prepare("INSERT INTO trackersql (tanggal, sqle, usere) VALUES (NOW(), ?, ?)");
        $stmt->execute([$msg, 'Admin Resend']);
    } catch (Exception $e) { }
}

try {
    // 1. QUERY UTAMA (STRICT FILTER BPJS)
    // Kita filter kd_pj di SQL agar pasien umum TIDAK PERNAH TERAMBIL.
    $sql = "SELECT 
                rp.no_reg, rp.no_rawat, rp.tgl_registrasi, rp.jam_reg, 
                rp.kd_dokter, d.nm_dokter, 
                rp.kd_poli, p.nm_poli, 
                rp.stts_daftar, rp.stts, rp.no_rkm_medis, rp.kd_pj,
                ps.no_ktp, ps.no_peserta, ps.no_tlp, ps.nm_pasien,
                mdp.kd_dokter_pcare,
                mpp.kd_poli_pcare
            FROM reg_periksa rp
            INNER JOIN pasien ps ON rp.no_rkm_medis = ps.no_rkm_medis
            INNER JOIN dokter d ON rp.kd_dokter = d.kd_dokter
            INNER JOIN poliklinik p ON rp.kd_poli = p.kd_poli
            INNER JOIN maping_dokter_pcare mdp ON rp.kd_dokter = mdp.kd_dokter
            INNER JOIN maping_poliklinik_pcare mpp ON rp.kd_poli = mpp.kd_poli_rs
            WHERE rp.tgl_registrasi BETWEEN :tgl_mulai AND :tgl_akhir
            AND (rp.kd_pj = 'BPJ' OR rp.kd_pj = 'BPJ ') -- [STRICT FILTER]
            ORDER BY rp.tgl_registrasi ASC, rp.jam_reg ASC"; 
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['tgl_mulai' => $tglMulai, 'tgl_akhir' => $tglAkhir]);
    $registrasi = $stmt->fetchAll();

    $totalData = count($registrasi);
    $processed = 0;
    $failed = 0;

    foreach ($registrasi as $reg) {
        $noRawat = $reg['no_rawat'];
        $hariRegistrasi = getHariIndonesia($reg['tgl_registrasi']);
        
        // Cek Status Task Lokal
        $stmtTask = $pdo->prepare("SELECT taskid FROM referensi_mobilejkn_bpjs_taskid WHERE no_rawat = ?");
        $stmtTask->execute([$noRawat]);
        $existingTasks = $stmtTask->fetchAll(PDO::FETCH_COLUMN);

        // Validasi Nomor Kartu
        $nomorKartu = trim($reg['no_peserta']);
        if (strlen($nomorKartu) < 10 || $nomorKartu == '-') {
            $log[] = "[SKIP] $noRawat: No Peserta Invalid ($nomorKartu)";
            continue;
        }

        // ====================================================================
        // TASK 0: ADD ANTREAN
        // ====================================================================
        if (!in_array('0', $existingTasks)) {
            $stmtJadwal = $pdo->prepare("SELECT jam_mulai, jam_selesai FROM jadwal WHERE kd_dokter = ? AND kd_poli = ? AND hari_kerja = ?");
            $stmtJadwal->execute([$reg['kd_dokter'], $reg['kd_poli'], $hariRegistrasi]);
            $jadwal = $stmtJadwal->fetch();

            if ($jadwal) {
                $jamPraktek = substr($jadwal['jam_mulai'], 0, 5) . "-" . substr($jadwal['jam_selesai'], 0, 5);
                $waktuRegistrasiAsli = $reg['tgl_registrasi'] . ' ' . $reg['jam_reg'];

                $payloadAdd = [
                    "nomorkartu" => $nomorKartu,
                    "nik" => trim($reg['no_ktp']),
                    "nohp" => trim($reg['no_tlp']),
                    "kodepoli" => trim($reg['kd_poli_pcare']),
                    "namapoli" => trim($reg['nm_poli']),
                    "norm" => trim($reg['no_rkm_medis']),
                    "tanggalperiksa" => trim($reg['tgl_registrasi']),
                    "kodedokter" => (int)$reg['kd_dokter_pcare'],
                    "namadokter" => trim($reg['nm_dokter']),
                    "jampraktek" => $jamPraktek,
                    "nomorantrean" => trim($reg['no_reg']),
                    "angkaantrean" => (int)$reg['no_reg'],
                    "keterangan" => "Peserta harap 30 menit lebih awal guna pencatatan administrasi."
                ];

                $resp = $bpjs->request('antrean/add', 'POST', $payloadAdd);
                $code = $resp['metadata']['code'] ?? 0;
                $msg  = $resp['metadata']['message'] ?? '';

                // Handle 200 (Sukses) atau 201 + "Sudah Terdaftar" (Bypass)
                if ($code == 200 || ($code == 201 && stripos($msg, 'sudah terdaftar') !== false)) {
                    $ins0 = $pdo->prepare("INSERT IGNORE INTO referensi_mobilejkn_bpjs_taskid (no_rawat, taskid, waktu) VALUES (?, ?, ?)");
                    $ins0->execute([$noRawat, '0', $waktuRegistrasiAsli]);
                    
                    $statusMsg = ($code == 200) ? "Sukses" : "Bypass antrol (Sudah Terdaftar)";
                    logTracker($pdo, "Resend Task 0 $statusMsg. No.Rawat: $noRawat");
                    $log[] = "[ADD] $noRawat: $statusMsg";
                    
                    // Update array task lokal agar Task 1 bisa lanjut di loop ini
                    $existingTasks[] = '0'; 
                    $processed++;
                } else {
                    $log[] = "[GAGAL ADD] $noRawat: $code - $msg";
                    $failed++;
                }
            } else {
                $log[] = "[SKIP ADD] $noRawat: Jadwal Dokter Tidak Ditemukan";
            }
        }

        // ====================================================================
        // TASK 1: UPDATE HADIR (PANGGIL)
        // Syarat: Harus punya Task 0 (atau baru saja sukses dibuat di atas)
        // ====================================================================
        if (in_array('0', $existingTasks) && !in_array('1', $existingTasks)) {
            // Ambil waktu SOAP dokter
            $stmtPeriksa = $pdo->prepare("SELECT tgl_perawatan, jam_rawat FROM pemeriksaan_ralan WHERE no_rawat = ? LIMIT 1");
            $stmtPeriksa->execute([$noRawat]);
            $periksa = $stmtPeriksa->fetch();

            if ($periksa) {
                $waktuSoapString = $periksa['tgl_perawatan'] . ' ' . $periksa['jam_rawat'];
                $waktuTask1 = strtotime($waktuSoapString) * 1000;
                
                // Debug Info
                $serverTime = date('Y-m-d H:i:s');
                
                $payloadHadir = [
                    "tanggalperiksa" => $reg['tgl_registrasi'],
                    "kodepoli" => trim($reg['kd_poli_pcare']),
                    "nomorkartu" => $nomorKartu,
                    "status" => 1, 
                    "waktu" => $waktuTask1
                ];

                $resp = $bpjs->request('antrean/panggil', 'POST', $payloadHadir);
                $code = $resp['metadata']['code'] ?? 0;
                $msg  = $resp['metadata']['message'] ?? '';

                if ($code == 200) {
                    $ins1 = $pdo->prepare("INSERT IGNORE INTO referensi_mobilejkn_bpjs_taskid (no_rawat, taskid, waktu) VALUES (?, ?, ?)");
                    $ins1->execute([$noRawat, '1', $waktuSoapString]);

                    logTracker($pdo, "Resend antrol Task 1 Sukses. No.Rawat: $noRawat");
                    $log[] = "[HADIR] $noRawat Sukses (SOAP: $waktuSoapString)";
                    $processed++;
                } else {
                    $errMsg = "[GAGAL HADIR] $noRawat: $code - $msg | SOAP: $waktuSoapString | Server: $serverTime";
                    $log[] = $errMsg;
                    $failed++;
                }
            }
        }

        // ====================================================================
        // TASK 2: BATAL
        // Syarat: Status Batal & Punya Task 0 & Belum punya Task 2
        // ====================================================================
        if ($reg['stts'] == 'Batal' && in_array('0', $existingTasks) && !in_array('2', $existingTasks)) {
            $payloadBatal = [
                "tanggalperiksa" => $reg['tgl_registrasi'],
                "kodepoli" => trim($reg['kd_poli_pcare']),
                "nomorkartu" => $nomorKartu,
                "alasan" => "Pasien membatalkan pendaftaran"
            ];

            $resp = $bpjs->request('antrean/batal', 'POST', $payloadBatal);
            $code = $resp['metadata']['code'] ?? 0;
            $msg  = $resp['metadata']['message'] ?? '';

            if ($code == 200) {
                $ins2 = $pdo->prepare("INSERT IGNORE INTO referensi_mobilejkn_bpjs_taskid (no_rawat, taskid, waktu) VALUES (?, ?, ?)");
                $ins2->execute([$noRawat, '2', date('Y-m-d H:i:s')]);
                
                logTracker($pdo, "Resend antrol Task 2 (Batal) Sukses. No.Rawat: $noRawat");
                $log[] = "[BATAL] $noRawat Sukses";
                $processed++;
            } else {
                $log[] = "[GAGAL BATAL] $noRawat: $code - $msg";
                $failed++;
            }
        }
    }

    echo json_encode([
        'status' => 'success', 
        'message' => "Proses Selesai. Total Data: $totalData. Berhasil: $processed. Gagal: $failed.",
        'logs' => $log
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>