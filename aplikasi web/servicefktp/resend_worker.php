<?php
// [2025-11-16] Selalu beri komentar.
// File: resend_worker.php
// Fungsi: Worker Manual (Logic Non-BPJS = Kosong & Log Debugging).

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
        $stmt->execute([$msg, 'Admin Utama']);
    } catch (Exception $e) { }
}

try {
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
            ORDER BY rp.tgl_registrasi ASC, rp.jam_reg ASC"; 
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['tgl_mulai' => $tglMulai, 'tgl_akhir' => $tglAkhir]);
    $registrasi = $stmt->fetchAll();

    $totalData = count($registrasi);
    $processed = 0;

    foreach ($registrasi as $reg) {
        $noRawat = $reg['no_rawat'];
        $hariRegistrasi = getHariIndonesia($reg['tgl_registrasi']);
        
        // [BARU] Variable Debugging
        $debugTime = "Tgl Reg: " . $reg['tgl_registrasi'] . " " . $reg['jam_reg'] . " | NoRawat: " . $noRawat;

        $stmtTask = $pdo->prepare("SELECT taskid FROM referensi_mobilejkn_bpjs_taskid WHERE no_rawat = ?");
        $stmtTask->execute([$noRawat]);
        $existingTasks = $stmtTask->fetchAll(PDO::FETCH_COLUMN);

        // LOGIKA PENENTUAN NOMOR KARTU (NON-BPJS = KOSONG)
        $nomorKartu = "";
        if (stripos($reg['kd_pj'], 'BPJ') !== false) {
            $nomorKartu = trim($reg['no_peserta']);
            if (strlen($nomorKartu) < 10) $nomorKartu = "";
        }

        // --- TASK 0: ADD ANTREAN ---
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

                $resp = $bpjs->request('antrean/add', 'POST', $payloadAdd, $debugTime);
                $code = $resp['metadata']['code'] ?? 0;

                if ($code == 200 || $code == 1 || $code == 201) {
                    $ins0 = $pdo->prepare("INSERT IGNORE INTO referensi_mobilejkn_bpjs_taskid (no_rawat, taskid, waktu) VALUES (?, ?, ?)");
                    $ins0->execute([$noRawat, '0', $waktuRegistrasiAsli]);
                    
                    logTracker($pdo, "Sukses Resend Add Antrean (Task 0). No.Rawat: $noRawat");
                    $log[] = "[ADD] $noRawat Sukses (Code $code)";
                    $processed++;
                }
            }
        }

        // --- TASK 1: UPDATE HADIR ---
        if (!in_array('1', $existingTasks)) {
            $stmtPeriksa = $pdo->prepare("SELECT tgl_perawatan, jam_rawat FROM pemeriksaan_ralan WHERE no_rawat = ? LIMIT 1");
            $stmtPeriksa->execute([$noRawat]);
            $periksa = $stmtPeriksa->fetch();

            if ($periksa) {
                $waktuTask1 = strtotime($periksa['tgl_perawatan'] . ' ' . $periksa['jam_rawat']) * 1000;
                $payloadHadir = [
                    "tanggalperiksa" => $reg['tgl_registrasi'],
                    "kodepoli" => trim($reg['kd_poli_pcare']),
                    "nomorkartu" => $nomorKartu,
                    "status" => 1, 
                    "waktu" => $waktuTask1
                ];

                $resp = $bpjs->request('antrean/panggil', 'POST', $payloadHadir, $debugTime);
                $code = $resp['metadata']['code'] ?? 0;

                // [PERBAIKAN LOGIKA] Hapus 201 dari kondisi sukses
                if ($code == 200 || $code == 1) {
                    $ins1 = $pdo->prepare("INSERT IGNORE INTO referensi_mobilejkn_bpjs_taskid (no_rawat, taskid, waktu) VALUES (?, ?, ?)");
                    $ins1->execute([$noRawat, '1', $periksa['tgl_perawatan'] . ' ' . $periksa['jam_rawat']]);

                    logTracker($pdo, "Sukses Resend Update Hadir (Task 1). No.Rawat: $noRawat");
                    $log[] = "[HADIR] $noRawat Update Sukses";
                    $processed++;
                } else {
                    // Tambahan log khusus resend agar ketahuan kenapa gagal
                    $message = $resp['metadata']['message'] ?? 'No Message';
                    $log[] = "[GAGAL HADIR] $noRawat: $code - $message";
                }
            }
        }

        // --- TASK 2: BATAL ---
        if ($reg['stts'] == 'Batal' && !in_array('2', $existingTasks)) {
            $payloadBatal = [
                "tanggalperiksa" => $reg['tgl_registrasi'],
                "kodepoli" => trim($reg['kd_poli_pcare']),
                "nomorkartu" => $nomorKartu,
                "alasan" => "Pasien membatalkan pendaftaran"
            ];

            $resp = $bpjs->request('antrean/batal', 'POST', $payloadBatal, $debugTime);
            $code = $resp['metadata']['code'] ?? 0;

            if ($code == 200 || $code == 1 || $code == 201) {
                $ins2 = $pdo->prepare("INSERT IGNORE INTO referensi_mobilejkn_bpjs_taskid (no_rawat, taskid, waktu) VALUES (?, ?, ?)");
                $ins2->execute([$noRawat, '2', date('Y-m-d H:i:s')]);
                logTracker($pdo, "Sukses Resend Batal (Task 2). No.Rawat: $noRawat");
                $log[] = "[BATAL] $noRawat Update Sukses";
                $processed++;
            }
        }
    }

    echo json_encode([
        'status' => 'success', 
        'message' => "Selesai tgl $tglMulai. Data: $totalData. Terupdate: $processed.",
        'logs' => $log
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>