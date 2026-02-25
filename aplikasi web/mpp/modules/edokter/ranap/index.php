<?php
// File: modules/edokter/ranap/index.php
require_once '../../../config/config.php';
require_once '../../../config/database.php';
require_once '../../../helpers/auth_helper.php';

cekLogin();

// Cek Super Admin atau Hak Akses
$is_superadmin = isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
if (!$is_superadmin && !cekAkses('soap_perawatan')) { 
    die("Akses Ditolak: Anda tidak memiliki hak akses E-Dokter."); 
}

$kd_dokter = $_SESSION['user_id']; 

// Filter
$stts_pulang = $_GET['stts'] ?? 'Belum';
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-d', strtotime('-7 days')); 
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');

try {
    // Query menggunakan LEFT JOIN dpjp_ranap agar pasien tanpa DPJP tetap terbaca
    $sql = "SELECT ki.no_rawat, r.no_rkm_medis, p.nm_pasien, p.jk, 
            ki.tgl_masuk, ki.jam_masuk, ki.stts_pulang, k.kd_kamar, b.nm_bangsal, pj.png_jawab,
            dr.kd_dokter as dpjp, d_dpjp.nm_dokter as nm_dpjp, 
            (SELECT COUNT(*) FROM pemeriksaan_ranap pr WHERE pr.no_rawat = ki.no_rawat) as ttv_count,
            (SELECT COUNT(*) FROM resume_pasien_ranap res WHERE res.no_rawat = ki.no_rawat) as resume_count 
            FROM kamar_inap ki
            JOIN reg_periksa r ON ki.no_rawat = r.no_rawat 
            JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis 
            JOIN kamar k ON ki.kd_kamar = k.kd_kamar
            JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
            JOIN penjab pj ON r.kd_pj = pj.kd_pj 
            LEFT JOIN dpjp_ranap dr ON ki.no_rawat = dr.no_rawat
            LEFT JOIN dokter d_dpjp ON dr.kd_dokter = d_dpjp.kd_dokter ";

    $params = [];
    $sql .= " WHERE 1=1 ";

    // Tampilkan pasien jika DPJP adalah dokter login saat ini, ATAU belum di-set sama sekali
    if (!$is_superadmin) {
        $sql .= " AND (dr.kd_dokter = :dokter OR dr.kd_dokter IS NULL) ";
        $params['dokter'] = $kd_dokter;
    }

    if ($stts_pulang == 'Belum') {
        $sql .= " AND ki.stts_pulang = '-' ";
    } else {
        $sql .= " AND ki.stts_pulang != '-' AND ki.tgl_keluar BETWEEN :tgl_awal AND :tgl_akhir ";
        $params['tgl_awal'] = $tgl_awal;
        $params['tgl_akhir'] = $tgl_akhir;
    }

    $sql .= " ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC";

    $stmt = $pdo->prepare($sql);
    if(count($params) > 0) { $stmt->execute($params); } else { $stmt->execute(); }
    $pasien = $stmt->fetchAll();

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

require_once '../../../layout/header.php';
require_once '../../../layout/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="h5 mb-0 text-gray-800">
            <i class="fas fa-procedures text-primary me-2"></i> Pasien Rawat Inap
            <?= $is_superadmin ? '<span class="badge bg-danger ms-2">Mode Super Admin</span>' : '' ?>
        </h4>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="small fw-bold">Status Perawatan</label>
                        <select class="form-select form-select-sm" name="stts" id="stts_filter" onchange="this.form.submit()">
                            <option value="Belum" <?= $stts_pulang=='Belum'?'selected':'' ?>>Masih Dirawat (Aktif)</option>
                            <option value="Pulang" <?= $stts_pulang=='Pulang'?'selected':'' ?>>Sudah Pulang / Keluar</option>
                        </select>
                    </div>
                    <?php if($stts_pulang == 'Pulang'): ?>
                    <div class="col-md-3">
                        <label class="small fw-bold">Dari Tgl Pulang</label>
                        <input type="date" class="form-control form-control-sm" name="tgl_awal" value="<?= $tgl_awal ?>" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Sampai Tgl Pulang</label>
                        <input type="date" class="form-control form-control-sm" name="tgl_akhir" value="<?= $tgl_akhir ?>" onchange="this.form.submit()">
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-3 table-responsive">
            <table class="table table-hover table-bordered mb-0 align-middle w-100" id="tblRanap">
                <thead class="bg-light text-center small">
                    <tr>
                        <th width="15%">Tgl Masuk</th>
                        <th width="30%">Pasien & RM</th>
                        <th width="25%">Kamar / Bangsal</th>
                        <th width="20%">Status Berkas</th>
                        <th width="10%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pasien as $row): ?>
                    <tr>
                        <td class="text-center">
                            <b><?= date('d/m/Y', strtotime($row['tgl_masuk'])) ?></b><br>
                            <small class="text-muted"><?= $row['jam_masuk'] ?></small>
                        </td>
                        <td>
                            <b><?= $row['nm_pasien'] ?></b> (<?= $row['jk'] ?>)<br>
                            <small class="text-muted">RM: <?= $row['no_rkm_medis'] ?> | <?= $row['no_rawat'] ?></small>
                            <?php if(empty($row['dpjp'])): ?>
                                <br><span class="badge bg-danger mt-1"><i class="fas fa-exclamation-triangle"></i> DPJP belum diset!</span>
                            <?php else: ?>
                                <br><span class="badge bg-success mt-1"><i class="fas fa-user-md"></i> DPJP: <?= $row['nm_dpjp'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="text-primary fw-bold"><?= $row['nm_bangsal'] ?></span><br>
                            <small class="text-muted">Kamar: <?= $row['kd_kamar'] ?> | <?= $row['png_jawab'] ?></small>
                        </td>
                        <td class="text-center">
                            <?php if($row['ttv_count'] > 0): ?>
                                <span class="badge bg-primary" title="CPPT Sudah Diisi"><i class="fas fa-check"></i> <?= $row['ttv_count'] ?> CPPT</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark" title="CPPT Belum Diisi"><i class="fas fa-exclamation"></i> No CPPT</span>
                            <?php endif; ?>

                            <?php if($row['resume_count'] > 0): ?>
                                <span class="badge bg-success" title="Resume Medis Sudah Dibuat"><i class="fas fa-file-medical"></i> Resume</span>
                            <?php else: ?>
                                <span class="badge bg-danger" title="Resume Medis Belum Dibuat"><i class="fas fa-times"></i> No Resume</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-primary btn-sm btn-periksa fw-bold" data-rawat="<?= $row['no_rawat'] ?>" data-nama="<?= $row['nm_pasien'] ?>">
                                <i class="fas fa-laptop-medical"></i> ERM
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalERM" data-bs-backdrop="static">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header bg-primary py-2 text-white">
                <h5 class="modal-title fw-bold" id="ermTitle"><i class="fas fa-laptop-medical"></i> E-Rekam Medis Inap</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-2">
                <ul class="nav nav-tabs fw-bold flex-nowrap overflow-auto" id="ermTabs" role="tablist" style="white-space: nowrap;">
                    <?php if(!$is_superadmin): ?>
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-cppt"><i class="fas fa-edit"></i> Input CPPT</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-resume"><i class="fas fa-file-medical"></i> Input Resume</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-resep"><i class="fas fa-pills"></i> Input Resep</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link <?= $is_superadmin ? 'active' : '' ?> load-ajax" data-type="cppt" data-bs-toggle="tab" href="#tab-history"><i class="fas fa-history"></i> Riwayat CPPT</a></li>
                    <li class="nav-item"><a class="nav-link load-ajax" data-type="lab" data-bs-toggle="tab" href="#tab-lab"><i class="fas fa-flask"></i> Hasil Lab</a></li>
                    <li class="nav-item"><a class="nav-link load-ajax" data-type="rad" data-bs-toggle="tab" href="#tab-rad"><i class="fas fa-x-ray"></i> Radiologi</a></li>
                </ul>

                <div class="tab-content bg-white border border-top-0 p-3" style="min-height: 80vh;">
                    <?php if(!$is_superadmin): ?>
                        <div class="tab-pane fade show active" id="tab-cppt"></div>
                        <div class="tab-pane fade" id="tab-resume"></div>
                        <div class="tab-pane fade" id="tab-resep"></div>
                    <?php endif; ?>
                    <div class="tab-pane fade <?= $is_superadmin ? 'show active' : '' ?>" id="tab-history"></div>
                    <div class="tab-pane fade" id="tab-lab"></div>
                    <div class="tab-pane fade" id="tab-rad"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../layout/footer.php'; ?>

<script>
$(document).ready(function() {
    var activeNoRawat = '';
    var isSuperadmin = <?= $is_superadmin ? 'true' : 'false' ?>;
    var baseUrl = '<?= $base_url ?>';

    $('#tblRanap').DataTable({
        "ordering": true,
        "searching": true,
        "language": { "search": "Cari Pasien/RM/Kamar:" }
    });

    $('#tblRanap tbody').on('click', '.btn-periksa', function() {
        activeNoRawat = $(this).data('rawat');
        var nama = $(this).data('nama');
        
        $('#ermTitle').html('<i class="fas fa-laptop-medical"></i> ERM Ranap: ' + nama + ' (' + activeNoRawat + ')');
        $('#tab-history, #tab-lab, #tab-rad').html('<div class="text-center mt-5"><div class="spinner-border text-primary"></div></div>');
        
        if (!isSuperadmin) {
            $('#tab-cppt').html('<div class="text-center mt-5"><div class="spinner-border text-primary"></div></div>');
            $('#tab-cppt').load('form_cppt.php?no_rawat=' + activeNoRawat);
            
            $('#tab-resume').html('<div class="text-center mt-5"><div class="spinner-border text-success"></div></div>');
            $('#tab-resume').load('form_resume.php?no_rawat=' + activeNoRawat);

            $('#tab-resep').html('<div class="text-center mt-5"><div class="spinner-border text-warning"></div></div>');
            $('#tab-resep').load('form_resep.php?no_rawat=' + activeNoRawat);

            $('#ermTabs a[href="#tab-cppt"]').tab('show');
        } else {
            $('#ermTabs a[href="#tab-history"]').tab('show').trigger('shown.bs.tab');
        }
        
        $('#modalERM').modal('show');
    });

    $('.load-ajax').on('shown.bs.tab', function (e) {
        var targetTab = $(e.target).attr("href");
        var type = $(e.target).data("type");
        var url = baseUrl + 'helpers/ajax/view_' + type + '.php';
        
        if ($(targetTab).html().includes('spinner-border')) {
            $.post(url, { no_rawat: activeNoRawat }, function(data) {
                $(targetTab).html(data);
            }).fail(function(xhr) {
                $(targetTab).html('<div class="alert alert-danger">Gagal memuat data.</div>');
            });
        }
    });
});
</script>