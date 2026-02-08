<?php
// [2026-02-08] REVISI ANTI-CRASH DATABASE (SANITASI KARAKTER)
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
        $this->logs[] = "[" . date('H:i:s') . "] " . $msg;
    }

    // [SAFETY] Fungsi pembersih karakter aneh/emoji (PENTING UNTUK KHANZA)
    private function cleanStr($str) {
        // Hanya izinkan karakter ASCII standar (32-126)
        // Membuang Emoji dan karakter 4-byte lainnya.
        return preg_replace('/[^\x20-\x7E]/', '', $str ?? '');
    }

    private function catatTracker($tanggal, $jsonResponse, $pesanManusia) {
        try {
            if ($jsonResponse === false || $jsonResponse === null) {
                $jsonResponse = "Invalid JSON/Binary Data";
            }

            // Gabungkan string
            $rawContent = "[BPJS RESPONSE]: " . $jsonResponse . "\n\n" . 
                          "[KETERANGAN]: " . $pesanManusia;
            
            // [CRITICAL FIX] Sanitasi string sebelum INSERT
            $sqleContent = $this->cleanStr($rawContent);

            $sql = "INSERT INTO trackersql (tanggal, sqle, usere) VALUES (:tgl, :sqle, 'aplikasi_bridging')";
            
            if (!$this->pdo) return; // Silent fail jika koneksi putus

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'tgl' => $tanggal,
                'sqle' => $sqleContent
            ]);

        } catch (Exception $e) {
            // Error tracker jangan mematikan flow utama, cukup log saja
            $this->log("ERROR TRACKER: " . $this->cleanStr($e->getMessage()));
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
        
        // 1. QUERY TASK 1 (HADIR)
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
                     
                     AND reg_periksa.no_rawat IN (
                        SELECT referensi_mobilejkn_bpjs_taskid.no_rawat 
                        FROM referensi_mobilejkn_bpjs_taskid 
                        WHERE referensi_mobilejkn_bpjs_taskid.taskid = '0'
                     )
                     
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

        // 2. QUERY TASK 2 (BATAL)
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
                     
                     AND reg_periksa.no_rawat IN (
                        SELECT referensi_mobilejkn_bpjs_taskid.no_rawat 
                        FROM referensi_mobilejkn_bpjs_taskid 
                        WHERE referensi_mobilejkn_bpjs_taskid.taskid = '0'
                     )
                     
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
        // Double Check Cara Bayar
        $stmtCek = $this->pdo->prepare("SELECT kd_pj FROM reg_periksa WHERE no_rawat = ?");
        $stmtCek->execute([$row['no_rawat']]);
        $pjAktual = $stmtCek->fetchColumn();

        if (trim($pjAktual) !== 'BPJ') { 
            $this->log("SKIP " . $row['no_rawat'] . ": Pasien Non-BPJS (Cara Bayar: $pjAktual).");
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

        $waktuSoapString = $row['tgl_perawatan'] . ' ' . $row['jam_rawat'];
        $timestampMilis = strtotime($waktuSoapString) * 1000; 
        
        $payload = [
            "kodepoli" => $kdPoliBpjs,
            "nomorkartu" => trim($row['no_peserta']),
            "tanggalperiksa" => $row['tgl_registrasi'],
            "status" => 1, 
            "waktu" => $timestampMilis 
        ];

        $this->log("SENDING HADIR " . $row['no_rawat'] . "...");
        
        $response = $this->bpjs->request('antrean/panggil', 'POST', $payload);
        $code = $response['metadata']['code'] ?? 0;
        $message = $response['metadata']['message'] ?? 'No Message';
        
        if ($code == 200 || $code == 201) {
            $insert = $this->pdo->prepare("INSERT INTO referensi_mobilejkn_bpjs_taskid (no_rawat, taskid, waktu) VALUES (?, '1', ?)");
            $insert->execute([$row['no_rawat'], $waktuSoapString]);

            $this->catatTracker(date('Y-m-d H:i:s'), json_encode($response), "Sukses Update Status HADIR (Trigger: SOAP Dokter)");
            $this->log("[OK] SUKSES " . $row['no_rawat'] . " | Msg: $message"); // Emoji dihapus
        } else {
            $errMsg = "[GAGAL] " . $row['no_rawat'] . " | Code: $code | Msg: $message"; // Emoji dihapus
            // Debug info
            $errMsg .= " | SOAP: $waktuSoapString | Kirim: $timestampMilis";
            $this->log($errMsg);
            $this->catatTracker(date('Y-m-d H:i:s'), json_encode($response), "GAGAL Kirim Hadir: $message");
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

            $this->catatTracker(date('Y-m-d H:i:s'), json_encode($response), "Sukses Batal Antrean");
            $this->log("[OK] SUKSES BATAL " . $row['no_rawat']); // Emoji dihapus
        } else {
            $this->log("[GAGAL] BATAL " . $row['no_rawat'] . ": " . $message);
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