<?php
// File: modules/ranap/ajax/view_lab.php
// Deskripsi: View Hasil Lab (Grouped by Paket Pemeriksaan)

$base_path = dirname(dirname(dirname(__DIR__)));
require_once $base_path . '/config/database.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    $no_rawat = isset($_POST['no_rawat']) ? $_POST['no_rawat'] : '';
    if (empty($no_rawat)) throw new Exception("No Rawat tidak dikirim.");

    // 1. Ambil Header Pemeriksaan (Tanggal & Jam)
    $sql_header = "SELECT DISTINCT pl.tgl_periksa, pl.jam, pl.dokter_perujuk, pl.kd_dokter, d.nm_dokter
                   FROM periksa_lab pl
                   LEFT JOIN dokter d ON pl.kd_dokter = d.kd_dokter
                   WHERE pl.no_rawat = ?
                   ORDER BY pl.tgl_periksa DESC, pl.jam DESC";
    
    $stmt = $pdo->prepare($sql_header);
    $stmt->execute([$no_rawat]);
    $headers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    function tgl_indo($tanggal){
        $bulan = array (1 => 'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des');
        $pecahkan = explode('-', $tanggal);
        return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
    }

} catch (Exception $e) {
    die('<div class="alert alert-danger">Error Sistem: ' . $e->getMessage() . '</div>');
}
?>

<div class="container-fluid p-3">
    <?php if(empty($headers)): ?>
        <div class="alert alert-info text-center">Tidak ada data hasil laboratorium.</div>
    <?php else: ?>
        <div class="accordion" id="accordionLab">
            <?php foreach($headers as $index => $head): ?>
                <?php 
                    $collapseId = "collapseLab" . $index;
                    $headerId = "headingLab" . $index;
                    $waktu = tgl_indo($head['tgl_periksa']) . " " . $head['jam'];
                ?>
                <div class="accordion-item mb-3 border shadow-sm">
                    <h2 class="accordion-header" id="<?= $headerId ?>">
                        <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>">
                            <div class="d-flex w-100 justify-content-between align-items-center me-3">
                                <span><i class="fas fa-flask me-2 text-info"></i> <b><?= $waktu ?></b></span>
                                <span class="badge bg-secondary small"><?= $head['nm_dokter'] ?? '-' ?></span>
                            </div>
                        </button>
                    </h2>
                    <div id="<?= $collapseId ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#accordionLab">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle small">
                                    <thead class="table-light text-uppercase">
                                        <tr>
                                            <th style="width: 30%">Pemeriksaan</th>
                                            <th style="width: 20%">Hasil</th>
                                            <th style="width: 15%">Satuan</th>
                                            <th style="width: 20%">Nilai Rujukan</th>
                                            <th style="width: 15%">Ket</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // FIX QUERY: Join ke jns_perawatan_lab untuk nama paket
                                        // Dan urutkan berdasarkan kd_jenis_prw agar hasil terkelompok
                                        try {
                                            $sql_detail = "
                                                SELECT 
                                                    j.nm_perawatan AS nama_paket,
                                                    t.Pemeriksaan, 
                                                    d.nilai, 
                                                    t.satuan, 
                                                    d.nilai_rujukan, 
                                                    d.keterangan
                                                FROM detail_periksa_lab d
                                                INNER JOIN template_laboratorium t ON d.id_template = t.id_template
                                                LEFT JOIN jns_perawatan_lab j ON d.kd_jenis_prw = j.kd_jenis_prw
                                                WHERE d.no_rawat = ? AND d.tgl_periksa = ? AND d.jam = ?
                                                ORDER BY d.kd_jenis_prw ASC, t.urut ASC
                                            ";
                                            $stmt_d = $pdo->prepare($sql_detail);
                                            $stmt_d->execute([$no_rawat, $head['tgl_periksa'], $head['jam']]);
                                            $details = $stmt_d->fetchAll(PDO::FETCH_ASSOC);
                                        } catch (Exception $e) {
                                            $details = [];
                                            echo "<tr><td colspan='5' class='text-danger'>Error: " . $e->getMessage() . "</td></tr>";
                                        }
                                        
                                        $current_paket = ""; // Variabel penanda grouping

                                        foreach($details as $det):
                                            // LOGIKA GROUPING
                                            if ($det['nama_paket'] != $current_paket) {
                                                echo "<tr class='table-secondary'><td colspan='5' class='fw-bold text-primary'><i class='fas fa-tag me-2'></i>" . $det['nama_paket'] . "</td></tr>";
                                                $current_paket = $det['nama_paket'];
                                            }

                                            // Logika Warna Keterangan
                                            $ket_color = "";
                                            $ket = strtoupper($det['keterangan']);
                                            if (in_array($ket, ['L','LOW','RENDAH'])) $ket_color = "text-primary fw-bold";
                                            elseif (in_array($ket, ['H','HIGH','TINGGI','*'])) $ket_color = "text-danger fw-bold";
                                        ?>
                                        <tr>
                                            <td class="ps-4"><?= $det['Pemeriksaan'] ?></td> <td class="<?= $ket_color ?>"><?= $det['nilai'] ?></td>
                                            <td><?= $det['satuan'] ?></td>
                                            <td><?= $det['nilai_rujukan'] ?></td>
                                            <td><span class="<?= $ket_color ?>"><?= $det['keterangan'] ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if(empty($details)): ?>
                                            <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada rincian hasil.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>