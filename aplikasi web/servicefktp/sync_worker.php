<?php
// [2026-02-06] REVISI TOTAL: LOGIC SOAP DOKTER & TABLE FIX
// File: sync_worker.php
// Fungsi: Worker sinkronisasi berdasarkan Input SOAP Dokter (Pemeriksaan Ralan).

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
        $this->logs[] = $msg;
    }

    // Fungsi Pencatat ke Table trackersql (Audit Trail)
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
            $this->log("GAGAL catat tracker: " . $e->getMessage());
        }
    }

    // Helper: Ambil Kode Poli BPJS
    private function getKodePoliBpjs($kd_poli_rs) {
        // Coba cari di maping_poli_bpjs 
        $stmt = $this->pdo->prepare("SELECT kd_poli_bpjs FROM maping_poli_bpjs WHERE kd_poli_rs = ?");
        $stmt->execute([$kd_poli_rs]);
        $res = $stmt->fetch();
        if ($res) return $res['kd_poli_bpjs'];

        // Fallback ke mapping pcare
        $stmt2 = $this->pdo->prepare("SELECT kd_poli_pcare FROM maping_poliklinik_pcare WHERE kd_poli_rs = ?");
        $stmt2->execute([$kd_poli_rs]);
        $res2 = $stmt2->fetch();
        return $res2 ? $res2['kd_poli_pcare'] : null;
    }

    public function run() {
        $tglSekarang = date('Y-m-d');
        
        // =========================================================================
        // 1. CEK SOAP DOKTER -> KIRIM TASK 1 (HADIR / DILAYANI)
        // =========================================================================
        // Logic: Cari pasien yang sudah punya Task 0 (Add), Belum punya Task 1, 
        // DAN sudah ada data di pemeriksaan_ralan oleh Dokter.
        
        $sqlHadir = "SELECT 
                        rp.no_rawat, rp.no_peserta, rp.tgl_registrasi, rp.kd_poli,
                        pr.tgl_perawatan, pr.jam_rawat,
                        d.nm_dokter
                     FROM reg_periksa rp
                     JOIN referensi_mobilejkn_bpjs_taskid task0 ON rp.no_rawat = task0.no_rawat AND task0.taskid = '0'
                     JOIN pemeriksaan_ralan pr ON rp.no_rawat = pr.no_rawat
                     JOIN dokter d ON pr.nip = d.kd_dokter  -- Validasi Inputan Dokter
                     LEFT JOIN referensi_mobilejkn_bpjs_taskid task1 ON rp.no_rawat = task1.no_rawat AND task1.taskid = '1'
                     WHERE rp.tgl_registrasi = :tgl
                     AND rp.kd_pj = 'BPJ'
                     AND task1.no_rawat IS NULL -- Hanya yang belum dikirim Task 1
                     ORDER BY pr.jam_rawat ASC
                     LIMIT 5"; 

        $stmt = $this->pdo->prepare($sqlHadir);
        $stmt->execute(['tgl' => $tglSekarang]);
        $listHadir = $stmt->fetchAll();

        foreach ($listHadir as $row) {
            $this->prosesHadir($row);
        }

        // =========================================================================
        // 2. CEK PASIEN BATAL -> KIRIM BATAL (TASK 2)
        // =========================================================================
        // Logic: Cari pasien status 'Batal', punya Task 0, tapi belum punya Task 2
        
        $sqlBatal = "SELECT 
                        rp.no_rawat, rp.no_peserta, rp.tgl_registrasi, rp.kd_poli
                     FROM reg_periksa rp
                     JOIN referensi_mobilejkn_bpjs_taskid task0 ON rp.no_rawat = task0.no_rawat AND task0.taskid = '0'
                     LEFT JOIN referensi_mobilejkn_bpjs_taskid task2 ON rp.no_rawat = task2.no_rawat AND task2.taskid = '2'
                     WHERE rp.tgl_registrasi = :tgl
                     AND rp.kd_pj = 'BPJ'
                     AND rp.stts = 'Batal'
                     AND task2.no_rawat IS NULL
                     LIMIT 5";

        $stmt = $this->pdo->prepare($sqlBatal);
        $stmt->execute(['tgl' => $tglSekarang]);
        $listBatal = $stmt->fetchAll();

        foreach ($listBatal as $row) {
            $this->prosesBatal($row);
        }

        return ['status' => 'success', 'logs' => $this->logs];
    }

    // === LOGIKA KIRIM STATUS HADIR (BERDASARKAN WAKTU SOAP) ===
    private function prosesHadir($row) {
        $kdPoliBpjs = $this->getKodePoliBpjs($row['kd_poli']);
        
        if (empty($kdPoliBpjs)) {
            $this->log("SKIP {$row['no_rawat']}: Mapping Poli Kosong.");
            return;
        }

        // AMBIL WAKTU DARI SOAP DOKTER (pemeriksaan_ralan)
        // Gabungkan tgl_perawatan + jam_rawat
        $waktuSoapString = $row['tgl_perawatan'] . ' ' . $row['jam_rawat'];
        $timestampMilis = strtotime($waktuSoapString) * 1000; // Konversi ke Milliseconds
        
        $payload = [
            "kodepoli" => $kdPoliBpjs,
            "nomorkartu" => $row['no_peserta'],
            "tanggalperiksa" => $row['tgl_registrasi'],
            "status" => 1, // 1 = Hadir
            "waktu" => $timestampMilis 
        ];

        $this->log("SEND HADIR (SOAP: {$row['jam_rawat']}) {$row['no_rawat']}...");
        
        $response = $this->bpjs->request('antrean/panggil', 'POST', $payload);
        $code = $response['metadata']['code'] ?? 0;
        
        if ($code == 200 || $code == 201) {
            // INSERT TASK 1 (Sesuai source Java Khanza: taskid='1' adalah Hadir/Dilayani)
            $insert = $this->pdo->prepare("INSERT INTO referensi_mobilejkn_bpjs_taskid (no_rawat, taskid, waktu) VALUES (?, '1', ?)");
            $insert->execute([$row['no_rawat'], $waktuSoapString]);

            // AUDIT TRAIL
            $msgManusia = "Sukses Update Status antrol HADIR. Trigger: Dokter {$row['nm_dokter']} mengisi SOAP pada jam {$row['jam_rawat']}.";
            $this->catatTracker(date('Y-m-d H:i:s'), json_encode($response), $msgManusia);

            $this->log("SUKSES HADIR: {$row['no_rawat']}");
        } else {
            $msgError = $response['metadata']['message'] ?? 'Unknown Error';
            $this->log("GAGAL HADIR {$row['no_rawat']}: $msgError");
            // Opsional: Catat kegagalan ke tracker juga agar tahu
            $this->catatTracker(date('Y-m-d H:i:s'), json_encode($response), "GAGAL Kirim Hadir: $msgError");
        }
    }

    // === LOGIKA KIRIM BATAL (TASK 2) ===
    private function prosesBatal($row) {
        $kdPoliBpjs = $this->getKodePoliBpjs($row['kd_poli']);
        
        if (empty($kdPoliBpjs)) return;

        $payload = [
            "kodepoli" => $kdPoliBpjs,
            "nomorkartu" => $row['no_peserta'],
            "tanggalperiksa" => $row['tgl_registrasi'],
            "alasan" => "Pasien membatalkan kunjungan / Tidak hadir"
        ];

        $this->log("SEND BATAL {$row['no_rawat']}...");

        $response = $this->bpjs->request('antrean/batal', 'POST', $payload);
        $code = $response['metadata']['code'] ?? 0;

        if ($code == 200 || $code == 201) {
            // INSERT TASK 2 (Sesuai source Java Khanza: taskid='2' adalah Batal)
            $insert = $this->pdo->prepare("INSERT INTO referensi_mobilejkn_bpjs_taskid (no_rawat, taskid, waktu) VALUES (?, '2', NOW())");
            $insert->execute([$row['no_rawat']]);

            $msgManusia = "Sukses Membatalkan antrol. Status sudah 'Batal'.";
            $this->catatTracker(date('Y-m-d H:i:s'), json_encode($response), $msgManusia);

            $this->log("SUKSES BATAL: {$row['no_rawat']}");
        } else {
            $this->log("GAGAL BATAL {$row['no_rawat']}: " . ($response['metadata']['message'] ?? 'Unknown Error'));
        }
    }
}

// Eksekusi Worker
header('Content-Type: application/json');
try {
    $worker = new AntrolWorker($pdo);
    echo json_encode($worker->run());
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>