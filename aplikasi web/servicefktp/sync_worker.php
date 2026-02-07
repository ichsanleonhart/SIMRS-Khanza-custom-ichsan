<?php
// [2026-02-07] REVISI DEBUGGER MODE: SUPER DETAIL LOGGING
// File: sync_worker.php

require_once 'config.php';
require_once 'bpjs_helper.php';

class AntrolWorker {
    private $pdo;
    private $bpjs;
    private $logs = [];

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->bpjs = new BpjsService();
    }

    private function log($msg) {
        // Tambahkan timestamp di setiap baris log agar presisi
        $this->logs[] = "[" . date('H:i:s') . "] " . $msg;
    }

    private function catatTracker($tanggal, $jsonResponse, $pesanManusia) {
        try {
            $sqleContent = "[BPJS RESPONSE]: " . $jsonResponse . "\n\n" . 
                           "[KETERANGAN]: " . $pesanManusia;

            $sql = "INSERT INTO trackersql (tanggal, sqle, usere) VALUES (:tgl, :sqle, 'aplikasi_bridging')";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'tgl' => $tanggal,
                'sqle' => $sqleContent
            ]);
        } catch (Exception $e) {
            // Silent fail untuk tracker agar tidak mengganggu flow utama
        }
    }

    private function getKodePoliBpjs($kd_poli_rs) {
        $stmt = $this->pdo->prepare("SELECT kd_poli_bpjs FROM maping_poli_bpjs WHERE kd_poli_rs = ?");
        $stmt->execute([$kd_poli_rs]);
        $res = $stmt->fetch();
        if ($res) return $res['kd_poli_bpjs'];

        $stmt2 = $this->pdo->prepare("SELECT kd_poli_pcare FROM maping_poliklinik_pcare WHERE kd_poli_rs = ?");
        $stmt2->execute([$kd_poli_rs]);
        $res2 = $stmt2->fetch();
        return $res2 ? $res2['kd_poli_pcare'] : null;
    }

    public function run() {
        $tglSekarang = date('Y-m-d');
        
        // =========================================================================
        // 1. QUERY TASK 1 (HADIR)
        // =========================================================================
        $sqlHadir = "SELECT 
                        reg_periksa.no_rawat, 
                        pasien.no_peserta, 
                        reg_periksa.tgl_registrasi, 
                        reg_periksa.kd_poli,
                        pemeriksaan_ralan.tgl_perawatan, 
                        pemeriksaan_ralan.jam_rawat,
                        dokter.nm_dokter
                     FROM reg_periksa 
                     JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                     JOIN pemeriksaan_ralan ON reg_periksa.no_rawat = pemeriksaan_ralan.no_rawat
                     JOIN dokter ON pemeriksaan_ralan.nip = dokter.kd_dokter
                     WHERE reg_periksa.tgl_registrasi = :tgl
                     AND reg_periksa.kd_pj = 'BPJ'
                     
                     -- CEK SUDAH ADA TASK 0 (ADD)
                     AND reg_periksa.no_rawat IN (
                        SELECT referensi_mobilejkn_bpjs_taskid.no_rawat 
                        FROM referensi_mobilejkn_bpjs_taskid 
                        WHERE referensi_mobilejkn_bpjs_taskid.taskid = '0'
                     )
                     
                     -- CEK BELUM ADA TASK 1 (HADIR)
                     AND reg_periksa.no_rawat NOT IN (
                        SELECT referensi_mobilejkn_bpjs_taskid.no_rawat 
                        FROM referensi_mobilejkn_bpjs_taskid 
                        WHERE referensi_mobilejkn_bpjs_taskid.taskid = '1'
                     )
                     ORDER BY pemeriksaan_ralan.jam_rawat ASC
                     LIMIT 5"; 

        $stmt = $this->pdo->prepare($sqlHadir);
        $stmt->execute(['tgl' => $tglSekarang]);
        $listHadir = $stmt->fetchAll();

        foreach ($listHadir as $row) {
            $this->prosesHadir($row);
        }

        // =========================================================================
        // 2. QUERY TASK 2 (BATAL)
        // =========================================================================
        $sqlBatal = "SELECT 
                        reg_periksa.no_rawat, 
                        pasien.no_peserta, 
                        reg_periksa.tgl_registrasi, 
                        reg_periksa.kd_poli
                     FROM reg_periksa 
                     JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                     WHERE reg_periksa.tgl_registrasi = :tgl
                     AND reg_periksa.kd_pj = 'BPJ'
                     AND reg_periksa.stts = 'Batal'
                     
                     -- SUDAH ADA TASK 0
                     AND reg_periksa.no_rawat IN (
                        SELECT referensi_mobilejkn_bpjs_taskid.no_rawat 
                        FROM referensi_mobilejkn_bpjs_taskid 
                        WHERE referensi_mobilejkn_bpjs_taskid.taskid = '0'
                     )
                     
                     -- BELUM ADA TASK 2
                     AND reg_periksa.no_rawat NOT IN (
                        SELECT referensi_mobilejkn_bpjs_taskid.no_rawat 
                        FROM referensi_mobilejkn_bpjs_taskid 
                        WHERE referensi_mobilejkn_bpjs_taskid.taskid = '2'
                     )
                     LIMIT 5";

        $stmt = $this->pdo->prepare($sqlBatal);
        $stmt->execute(['tgl' => $tglSekarang]);
        $listBatal = $stmt->fetchAll();

        foreach ($listBatal as $row) {
            $this->prosesBatal($row);
        }

        return ['status' => 'success', 'logs' => $this->logs];
    }

    private function prosesHadir($row) {
        // Ambil cara bayar aktual saat ini (Realtime check)
        $stmtCek = $this->pdo->prepare("SELECT kd_pj FROM reg_periksa WHERE no_rawat = ?");
        $stmtCek->execute([$row['no_rawat']]);
        $pjAktual = $stmtCek->fetchColumn();

        if (trim($pjAktual) !== 'BPJ') { // Sesuaikan 'BPJ' dengan kode di database
            $this->log("SKIP " . $row['no_rawat'] . ": Pasien Non-BPJS (Cara Bayar: $pjAktual).");
            // Opsional: Hapus sampah Task 0 agar tidak dicek lagi selamanya
            // $this->pdo->exec("DELETE FROM referensi_mobilejkn_bpjs_taskid WHERE no_rawat = '{$row['no_rawat']}'");
            return;
        }
		
		$kdPoliBpjs = $this->getKodePoliBpjs($row['kd_poli']);
        
        if (empty($kdPoliBpjs)) {
            $this->log("SKIP " . $row['no_rawat'] . ": Mapping Poli Kosong.");
            return;
        }

        if (empty($row['no_peserta']) || $row['no_peserta'] == '-') {
            $this->log("SKIP " . $row['no_rawat'] . ": No Peserta Kosong.");
            return;
        }

        // --- DEBUG WAKTU ---
        $waktuSoapString = $row['tgl_perawatan'] . ' ' . $row['jam_rawat'];
        $timestampMilis = strtotime($waktuSoapString) * 1000; 
        
        // String balik untuk cek validitas manusia
        $checkDate = date('Y-m-d H:i:s', $timestampMilis / 1000);
        $serverTime = date('Y-m-d H:i:s');

        $payload = [
            "kodepoli" => $kdPoliBpjs,
            "nomorkartu" => trim($row['no_peserta']),
            "tanggalperiksa" => $row['tgl_registrasi'],
            "status" => 1, 
            "waktu" => $timestampMilis 
        ];

        // LOGGING SEBELUM KIRIM
        $this->log("SENDING HADIR " . $row['no_rawat'] . "...");
        
        $response = $this->bpjs->request('antrean/panggil', 'POST', $payload);
        $code = $response['metadata']['code'] ?? 0;
        $message = $response['metadata']['message'] ?? 'No Message';
        
        if ($code == 200 || $code == 201) {
            $insert = $this->pdo->prepare("INSERT INTO referensi_mobilejkn_bpjs_taskid (no_rawat, taskid, waktu) VALUES (?, '1', ?)");
            $insert->execute([$row['no_rawat'], $waktuSoapString]);

            $this->catatTracker(date('Y-m-d H:i:s'), json_encode($response), "Sukses Update Status antrol HADIR (Trigger: SOAP Dokter)");
            $this->log("✅ SUKSES " . $row['no_rawat'] . " | Msg: $message");
        } else {
            // --- LOGGING SUPER DETAIL SAAT ERROR ---
            $errMsg = "❌ GAGAL " . $row['no_rawat'] . " | Code: $code | Msg: $message\n";
            $errMsg .= "      >> DEBUG WAKTU:\n";
            $errMsg .= "         - Input Dokter: $waktuSoapString\n";
            $errMsg .= "         - Dikirim (ms): $timestampMilis ($checkDate)\n";
            $errMsg .= "         - Jam Server  : $serverTime\n";
            $errMsg .= "      >> DEBUG DATA:\n";
            $errMsg .= "         - Poli Kirim  : $kdPoliBpjs\n";
            $errMsg .= "         - Kartu Kirim : " . trim($row['no_peserta']) . "\n";
            $errMsg .= "      >> RAW RESPONSE:\n";
            $errMsg .= "         " . json_encode($response);
            
            $this->log($errMsg);
            
            $this->catatTracker(date('Y-m-d H:i:s'), json_encode($response), "GAGAL Kirim antrol Hadir. Error: $message");
        }
    }

    private function prosesBatal($row) {
        $kdPoliBpjs = $this->getKodePoliBpjs($row['kd_poli']);
        
        if (empty($kdPoliBpjs)) return;

        if (empty($row['no_peserta']) || $row['no_peserta'] == '-') {
            $this->log("SKIP BATAL " . $row['no_rawat'] . ": No Peserta Kosong.");
            return;
        }

        $payload = [
            "kodepoli" => $kdPoliBpjs,
            "nomorkartu" => trim($row['no_peserta']),
            "tanggalperiksa" => $row['tgl_registrasi'],
            "alasan" => "Pasien membatalkan kunjungan"
        ];

        $this->log("SENDING BATAL " . $row['no_rawat'] . "...");

        $response = $this->bpjs->request('antrean/batal', 'POST', $payload);
        $code = $response['metadata']['code'] ?? 0;
        $message = $response['metadata']['message'] ?? 'No Message';

        if ($code == 200 || $code == 201) {
            $insert = $this->pdo->prepare("INSERT INTO referensi_mobilejkn_bpjs_taskid (no_rawat, taskid, waktu) VALUES (?, '2', NOW())");
            $insert->execute([$row['no_rawat']]);

            $this->catatTracker(date('Y-m-d H:i:s'), json_encode($response), "Sukses Batal Antrean antrol");
            $this->log("✅ SUKSES BATAL " . $row['no_rawat']);
        } else {
            // DETAIL ERROR BATAL
            $errMsg = "❌ GAGAL BATAL " . $row['no_rawat'] . " | Code: $code | Msg: $message\n";
            $errMsg .= "      >> PAYLOAD: " . json_encode($payload);
            $this->log($errMsg);
        }
    }
}

header('Content-Type: application/json');
try {
    $worker = new AntrolWorker($pdo);
    echo json_encode($worker->run());
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>