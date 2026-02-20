<?php
// File: modules/ranap/ajax/view_cppt.php
// Deskripsi: Menampilkan Riwayat CPPT (SOAP + Instruksi & Evaluasi)

$base_path = dirname(dirname(__DIR__));
require_once $base_path . '/config/config.php'; // Load config untuk URL
require_once $base_path . '/config/database.php';

ini_set('display_errors', 0);
error_reporting(0);

$no_rawat = isset($_POST['no_rawat']) ? $_POST['no_rawat'] : '';

if (empty($no_rawat)) {
    echo '<div class="alert alert-danger">No Rawat tidak ditemukan.</div>';
    exit;
}

// --- 1. QUERY UNION (RALAN & RANAP) ---
// Update: Menambahkan instruksi dan evaluasi
$sql = "
    (
        SELECT tgl_perawatan, jam_rawat, suhu_tubuh, tensi, nadi, respirasi, spo2, 
               kesadaran, gcs, keluhan, pemeriksaan, penilaian, rtl, instruksi, evaluasi, nip, 
               'Rawat Inap' as sumber 
        FROM pemeriksaan_ranap 
        WHERE no_rawat = ?
    )
    UNION ALL
    (
        SELECT tgl_perawatan, jam_rawat, suhu_tubuh, tensi, nadi, respirasi, spo2, 
               kesadaran, gcs, keluhan, pemeriksaan, penilaian, rtl, instruksi, evaluasi, nip, 
               'Rawat Jalan' as sumber 
        FROM pemeriksaan_ralan 
        WHERE no_rawat = ?
    )
    ORDER BY tgl_perawatan DESC, jam_rawat DESC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$no_rawat, $no_rawat]);
    $cppt_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('<div class="alert alert-danger">Error Query: ' . $e->getMessage() . '</div>');
}

// Helper Get Nama Petugas
function getNamaPetugas($pdo, $nip) {
    $stmt = $pdo->prepare("SELECT nm_dokter FROM dokter WHERE kd_dokter = ?");
    $stmt->execute([$nip]);
    if ($d = $stmt->fetch()) return $d['nm_dokter'];

    $stmt = $pdo->prepare("SELECT nama FROM pegawai WHERE nik = ?");
    $stmt->execute([$nip]);
    if ($p = $stmt->fetch()) return $p['nama'];

    return $nip;
}

function tgl_indo($tanggal){
	$bulan = array (1 => 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
	$pecahkan = explode('-', $tanggal);
	return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}
?>

<style>
    .timeline-item { border-left: 4px solid #dee2e6; padding-left: 20px; margin-bottom: 20px; position: relative; }
    .timeline-item::before { content: ''; width: 14px; height: 14px; background: #fff; border: 3px solid #0d6efd; border-radius: 50%; position: absolute; left: -9px; top: 0; }
    .timeline-item.ranap { border-left-color: #0d6efd; }
    .timeline-item.ralan { border-left-color: #198754; }
    .timeline-item.ralan::before { border-color: #198754; }
    .soap-box { background: #fff; border: 1px solid #e3e6f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .soap-header { background-color: #f8f9fc; padding: 10px 15px; border-bottom: 1px solid #e3e6f0; border-radius: 8px 8px 0 0; }
    .vital-sign-badge { font-size: 0.8rem; padding: 4px 8px; margin-right: 5px; border-radius: 4px; background-color: #f1f3f5; border: 1px solid #ced4da; }
    .soap-label { font-weight: bold; color: #4e73df; width: 35px; display: inline-block; vertical-align: top; }
    .content-text { color: #333; display: inline-block; width: calc(100% - 40px); }
</style>

<div class="container-fluid p-3 bg-light">
    <?php if (empty($cppt_data)): ?>
        <div class="alert alert-info text-center"><i class="fas fa-info-circle fa-2x mb-3"></i><br>Belum ada data CPPT.</div>
    <?php else: ?>
        <div class="timeline-container">
            <?php foreach ($cppt_data as $row): ?>
                <?php 
                    $nama_petugas = getNamaPetugas($pdo, $row['nip']);
                    $jenis_class = ($row['sumber'] == 'Rawat Inap') ? 'ranap' : 'ralan';
                    $bg_badge = ($row['sumber'] == 'Rawat Inap') ? 'bg-primary' : 'bg-success';
                ?>
                <div class="timeline-item <?= $jenis_class ?>">
                    <div class="mb-1">
                        <span class="badge <?= $bg_badge ?> me-2"><?= $row['sumber'] ?></span>
                        <span class="text-muted fw-bold small">
                            <i class="far fa-calendar-alt me-1"></i> <?= tgl_indo($row['tgl_perawatan']) ?> 
                            <i class="far fa-clock ms-2 me-1"></i> <?= $row['jam_rawat'] ?>
                        </span>
                        <span class="float-end small text-muted"><i class="fas fa-user-md me-1"></i> <?= $nama_petugas ?></span>
                    </div>

                    <div class="soap-box">
                        <div class="soap-header">
                            <div class="d-flex flex-wrap gap-1">
                                <?php if($row['tensi']): ?><span class="vital-sign-badge">TD: <?= $row['tensi'] ?></span><?php endif; ?>
                                <?php if($row['nadi']): ?><span class="vital-sign-badge">Nadi: <?= $row['nadi'] ?></span><?php endif; ?>
                                <?php if($row['suhu_tubuh']): ?><span class="vital-sign-badge">Suhu: <?= $row['suhu_tubuh'] ?></span><?php endif; ?>
                                <?php if($row['respirasi']): ?><span class="vital-sign-badge">RR: <?= $row['respirasi'] ?></span><?php endif; ?>
                                <?php if($row['spo2']): ?><span class="vital-sign-badge">SpO2: <?= $row['spo2'] ?>%</span><?php endif; ?>
                                <?php if($row['gcs']): ?><span class="vital-sign-badge">GCS: <?= $row['gcs'] ?></span><?php endif; ?>
                                <?php if($row['kesadaran']): ?><span class="vital-sign-badge"><?= $row['kesadaran'] ?></span><?php endif; ?>
                            </div>
                        </div>
                        <div class="p-3">
                            <div class="mb-2"><span class="soap-label">S</span> <div class="content-text"><?= nl2br($row['keluhan']) ?></div></div>
                            <div class="mb-2"><span class="soap-label">O</span> <div class="content-text"><?= nl2br($row['pemeriksaan']) ?></div></div>
                            <div class="mb-2"><span class="soap-label">A</span> <div class="content-text"><?= nl2br($row['penilaian']) ?></div></div>
                            <div class="mb-2"><span class="soap-label">P</span> <div class="content-text"><?= nl2br($row['rtl']) ?></div></div>
                            
                            <?php if(!empty($row['instruksi'])): ?>
                                <div class="mb-2"><span class="soap-label text-warning">I</span> <div class="content-text bg-warning bg-opacity-10 p-1 rounded"><?= nl2br($row['instruksi']) ?></div></div>
                            <?php endif; ?>
                            
                            <?php if(!empty($row['evaluasi'])): ?>
                                <div class="mb-2"><span class="soap-label text-success">E</span> <div class="content-text bg-success bg-opacity-10 p-1 rounded"><?= nl2br($row['evaluasi']) ?></div></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>