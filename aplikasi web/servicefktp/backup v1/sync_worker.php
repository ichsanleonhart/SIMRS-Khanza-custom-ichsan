<?php
// [2025-11-16] Selalu beri komentar.
// File: sync_worker.php
// Fungsi: Worker Antrol (Filter BPJS Only & Handling Code 201).

require_once 'bpjs_helper.php';

$bpjs = new BpjsService();
$log = [];

function getHariIndonesia($date) {
    $days = [
        'Sunday' => 'AKHAD', 'Monday' => 'SENIN', 'Tuesday' => 'SELASA',
        'Wednesday' => 'RABU', 'Thursday' => 'KAMIS', 'Friday' => 'JUMAT', 'Saturday' => 'SABTU'
    ];
    return $days[date('l', strtotime($date))];
}

try {
    $tglSekarang = date('Y-m-d');
    $hariIni = getHariIndonesia($tglSekarang);
    
    // [REVISI] QUERY HANYA PASIEN BPJS (kd_pj = 'BPJ')
    // Pastikan kode 'BPJ' sesuai dengan master cara bayar di RS Anda.
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
            WHERE rp.tgl_registrasi = :tgl 
            AND rp.kd_pj = 'BPJ' 
            ORDER BY rp.jam_reg DESC LIMIT 50"; 
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['tgl' => $tglSekarang]);
    $registrasi = $stmt->fetchAll();

    foreach ($registrasi as $reg) {
        $noRawat = $reg['no_rawat'];
        $debugTime = "Tgl Reg: " . $reg['tgl_registrasi'] . " " . $reg['jam_reg'] . " | NoRawat: " . $noRawat;
        
        $stmtTask = $pdo->prepare("SELECT taskid FROM referensi_mobilejkn_bpjs_taskid WHERE no_rawat = ?");
        $stmtTask->execute([$noRawat]);
        $existingTasks = $stmtTask->fetchAll(PDO::FETCH_COLUMN);

        // --- TASK 0: ADD ANTREAN ---
        if (!in_array('0', $existingTasks)) {
            $stmtJadwal = $pdo->prepare("SELECT jam_mulai, jam_selesai FROM jadwal WHERE kd_dokter = ? AND kd_poli = ? AND hari_kerja = ?");
            $stmtJadwal->execute([$reg['kd_dokter'], $reg['kd_poli'], $hariIni]);
            $jadwal = $stmtJadwal->fetch();

            if ($jadwal) {
                $jamPraktek = substr($jadwal['jam_mulai'], 0, 5) . "-" . substr($jadwal['jam_selesai'], 0, 5);
                $waktuRegistrasiAsli = $reg['tgl_registrasi'] . ' ' . $reg['jam_reg'];

                $payloadAdd = [
                    "nomorkartu" => trim($reg['no_peserta']), // Pasti BPJS karena filter query
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
                $message = $resp['metadata']['message'] ?? '';

                // [REVISI] Logic 201 "Sudah Terdaftar" dianggap SUKSES
                $isSuccess = ($code == 200 || $code == 1);
                $isAlreadyRegistered = ($code == 201 && (stripos($message, 'sudah terdaftar') !== false));

                if ($isSuccess || $isAlreadyRegistered) {
                    $ins0 = $pdo->prepare("INSERT IGNORE INTO referensi_mobilejkn_bpjs_taskid (no_rawat, taskid, waktu) VALUES (?, ?, ?)");
                    $ins0->execute([$noRawat, '0', $waktuRegistrasiAsli]);

                    // Jika sukses murni (200), catat ke tracker. Jika 201, cukup simpan referensi saja (silent)
                    if ($isSuccess) {
                        try {
                            $insTrack = $pdo->prepare("INSERT INTO trackersql (tanggal, sqle, usere) VALUES (NOW(), ?, ?)");
                            $insTrack->execute(["Sukses Add Antrean (PHP). No.Rawat: $noRawat", "SYSTEM-WEB"]);
                        } catch (Exception $e) {}
                        $log[] = "[ADD] Sukses kirim antrean No.Rawat: $noRawat";
                    } else {
                        $log[] = "[INFO] Pasien $noRawat sudah terdaftar di BPJS. Ditandai Task 0 OK.";
                    }
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
                    "nomorkartu" => trim($reg['no_peserta']),
                    "status" => 1, 
                    "waktu" => $waktuTask1
                ];

                $resp = $bpjs->request('antrean/panggil', 'POST', $payloadHadir, $debugTime);
                $code = $resp['metadata']['code'] ?? 0;

                // Update Status HANYA jika 200 OK. 201 di sini berarti Gagal (Data antrean tdk ada)
                if ($code == 200 || $code == 1) {
                    $ins1 = $pdo->prepare("INSERT IGNORE INTO referensi_mobilejkn_bpjs_taskid (no_rawat, taskid, waktu) VALUES (?, ?, ?)");
                    $ins1->execute([$noRawat, '1', $periksa['tgl_perawatan'] . ' ' . $periksa['jam_rawat']]);
                    $log[] = "[HADIR] Update Pasien Hadir No.Rawat: $noRawat";
                }
            }
        }

        // --- TASK 2: BATAL ---
        if ($reg['stts'] == 'Batal' && !in_array('2', $existingTasks)) {
            $payloadBatal = [
                "tanggalperiksa" => $reg['tgl_registrasi'],
                "kodepoli" => trim($reg['kd_poli_pcare']),
                "nomorkartu" => trim($reg['no_peserta']),
                "alasan" => "Pasien membatalkan pendaftaran"
            ];

            $resp = $bpjs->request('antrean/batal', 'POST', $payloadBatal, $debugTime);
            $code = $resp['metadata']['code'] ?? 0;

            if ($code == 200 || $code == 1) {
                $ins2 = $pdo->prepare("INSERT IGNORE INTO referensi_mobilejkn_bpjs_taskid (no_rawat, taskid, waktu) VALUES (?, ?, ?)");
                $ins2->execute([$noRawat, '2', date('Y-m-d H:i:s')]);
                $log[] = "[BATAL] Antrean Dibatalkan No.Rawat: $noRawat";
            }
        }
    }

    if (!empty($log)) {
        echo json_encode(['status' => 'success', 'logs' => $log]);
    } else {
        echo json_encode(['status' => 'idle', 'message' => 'Tidak ada antrean BPJS yang perlu disinkronisasi saat ini.']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>