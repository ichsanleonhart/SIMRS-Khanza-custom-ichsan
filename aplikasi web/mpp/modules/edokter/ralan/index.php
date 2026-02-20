<?php
// File: modules/edokter/ralan/index.php
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

// 3. Filter Rentang Tanggal (Default Hari Ini)
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-d');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
$status_periksa = $_GET['stts'] ?? 'Belum';

try {
    // 4. Tambahkan sub-query pengecekan resume_pasien
    $sql = "SELECT r.no_rawat, r.no_rkm_medis, r.tgl_registrasi, r.jam_reg, r.stts, r.status_lanjut, r.no_reg, r.p_jawab, r.hubunganpj, p.nm_pasien, p.jk, pol.nm_poli, pj.png_jawab, 
            (SELECT COUNT(*) FROM pemeriksaan_ralan pr WHERE pr.no_rawat = r.no_rawat) as ttv_count,
            (SELECT COUNT(*) FROM resume_pasien res WHERE res.no_rawat = r.no_rawat) as resume_count 
            FROM reg_periksa r 
            JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis 
            JOIN poliklinik pol ON r.kd_poli = pol.kd_poli 
            JOIN penjab pj ON r.kd_pj = pj.kd_pj 
            WHERE r.tgl_registrasi BETWEEN :tgl_awal AND :tgl_akhir 
            AND r.status_lanjut = 'Ralan'";
    
    $params = ['tgl_awal' => $tgl_awal, 'tgl_akhir' => $tgl_akhir];

    if (!$is_superadmin) {
        $sql .= " AND r.kd_dokter = :dokter";
        $params['dokter'] = $kd_dokter;
    }

    if ($status_periksa != 'Semua') {
        $sql .= " AND r.stts = :stts";
        $params['stts'] = $status_periksa;
    }
    $sql .= " ORDER BY r.no_reg DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pasien = $stmt->fetchAll();
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

require_once '../../../layout/header.php';
require_once '../../../layout/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="h5 mb-0 text-gray-800">
            <i class="fas fa-user-md text-warning me-2"></i> Antrean Poliklinik 
            <?= $is_superadmin ? '<span class="badge bg-danger ms-2">Mode Super Admin</span>' : '' ?>
        </h4>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="small fw-bold">Dari Tanggal</label>
                        <input type="date" class="form-control form-control-sm" name="tgl_awal" value="<?= $tgl_awal ?>" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Sampai Tanggal</label>
                        <input type="date" class="form-control form-control-sm" name="tgl_akhir" value="<?= $tgl_akhir ?>" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Status Periksa</label>
                        <select class="form-select form-select-sm" name="stts" onchange="this.form.submit()">
                            <option value="Belum" <?= $status_periksa=='Belum'?'selected':'' ?>>Belum Periksa</option>
                            <option value="Sudah" <?= $status_periksa=='Sudah'?'selected':'' ?>>Sudah Periksa</option>
                            <option value="Semua" <?= $status_periksa=='Semua'?'selected':'' ?>>Tampilkan Semua</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-3 table-responsive">
            <table class="table table-hover table-bordered mb-0 align-middle w-100" id="tblAntrean">
                <thead class="bg-light text-center small">
                    <tr>
                        <th width="5%">No</th>
                        <th width="25%">Pasien & RM</th>
                        <th width="20%">Poliklinik</th>
                        <th width="25%">Status / Berkas</th>
                        <th width="10%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pasien as $row): ?>
                    <tr>
                        <td class="text-center fw-bold fs-5"><?= $row['no_reg'] ?></td>
                        <td>
                            <b><?= $row['nm_pasien'] ?></b><br>
                            <small class="text-muted">RM: <?= $row['no_rkm_medis'] ?> | <?= $row['no_rawat'] ?></small>
                        </td>
                        <td><?= $row['nm_poli'] ?><br><small class="text-primary"><?= $row['png_jawab'] ?></small></td>
                        <td class="text-center">
                            <span class="badge <?= $row['stts']=='Sudah'?'bg-success':'bg-secondary' ?> mb-1"><?= $row['stts'] ?></span><br>
                            
                            <?php if($row['ttv_count'] > 0): ?>
                                <span class="badge bg-primary" title="CPPT / SOAP Sudah Diisi"><i class="fas fa-check"></i> CPPT</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark" title="CPPT / SOAP Belum Diisi"><i class="fas fa-exclamation"></i> No CPPT</span>
                            <?php endif; ?>

                            <?php if($row['resume_count'] > 0): ?>
                                <span class="badge bg-success" title="Resume Medis Sudah Dibuat"><i class="fas fa-file-medical"></i> Resume</span>
                            <?php else: ?>
                                <span class="badge bg-danger" title="Resume Medis Belum Dibuat"><i class="fas fa-times"></i> No Resume</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-warning btn-sm btn-periksa text-dark fw-bold" data-rawat="<?= $row['no_rawat'] ?>" data-nama="<?= $row['nm_pasien'] ?>" title="Buka ERM Pasien">
                                <i class="fas fa-laptop-medical"></i> Buka ERM
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
            <div class="modal-header bg-warning py-2">
                <h5 class="modal-title text-dark fw-bold" id="ermTitle"><i class="fas fa-laptop-medical"></i> E-Rekam Medis Terpadu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-2">
                <ul class="nav nav-tabs fw-bold flex-nowrap overflow-auto" id="ermTabs" role="tablist" style="white-space: nowrap;">
                    <?php if(!$is_superadmin): ?>
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-cppt"><i class="fas fa-edit"></i> Input CPPT</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-resume"><i class="fas fa-file-medical"></i> Input Resume</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-resep"> <i class="fas fa-pills"></i> Input Resep</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link <?= $is_superadmin ? 'active' : '' ?> load-ajax" data-type="cppt" data-bs-toggle="tab" href="#tab-history"><i class="fas fa-history"></i> Riwayat CPPT</a></li>
                    <li class="nav-item"><a class="nav-link load-ajax" data-type="lab" data-bs-toggle="tab" href="#tab-lab"><i class="fas fa-flask"></i> Hasil Lab</a></li>
                    <li class="nav-item"><a class="nav-link load-ajax" data-type="rad" data-bs-toggle="tab" href="#tab-rad"><i class="fas fa-x-ray"></i> Radiologi</a></li>
                    <li class="nav-item"><a class="nav-link text-muted" data-bs-toggle="tab" href="#tab-allhistory" onclick="alert('Modul Riwayat Lengkap sedang dalam pengembangan.');"><i class="fas fa-book-medical"></i> Riwayat Lengkap</a></li>
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
                    <div class="tab-pane fade" id="tab-allhistory"><div class="text-center py-5 text-muted"><h4>Modul Riwayat Lengkap Belum Tersedia</h4></div></div>
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

    // 1 & 2. Aktifkan DataTables dengan fitur Search & Sort Header
    $('#tblAntrean').DataTable({
        "ordering": true,       // Aktifkan klik Sort pada Header
        "searching": true,      // Aktifkan Searchbox universal
        "paging": true,
        "info": true,
        "language": {
            "search": "Cari Pasien/RM/Poli:",
            "emptyTable": "Tidak ada antrean pasien untuk kriteria ini."
        }
    });

    // Kita tempelkan event ke <tbody> dari #tblAntrean
    $('#tblAntrean tbody').on('click', '.btn-periksa', function() {
        activeNoRawat = $(this).data('rawat');
        var nama = $(this).data('nama');
        
        $('#ermTitle').html('<i class="fas fa-laptop-medical"></i> ERM: ' + nama + ' (' + activeNoRawat + ')');
        $('#tab-history, #tab-lab, #tab-rad').html('<div class="text-center mt-5"><div class="spinner-border text-primary"></div></div>');
        
        if (!isSuperadmin) {
            // Load CPPT
            $('#tab-cppt').html('<div class="text-center mt-5"><div class="spinner-border text-warning"></div></div>');
            $('#tab-cppt').load('form_cppt.php?no_rawat=' + activeNoRawat);
            
            // Load Resume
            $('#tab-resume').html('<div class="text-center mt-5"><div class="spinner-border text-success"></div></div>');
            $('#tab-resume').load('form_resume.php?no_rawat=' + activeNoRawat);
			
			// Load resep
            $('#tab-resep').html('<div class="text-center mt-5"><div class="spinner-border text-success"></div></div>');
            $('#tab-resep').load('form_resep.php?no_rawat=' + activeNoRawat);

            $('#ermTabs a[href="#tab-cppt"]').tab('show');
        } else {
            $('#ermTabs a[href="#tab-history"]').tab('show').trigger('shown.bs.tab');
        }
        
        $('#modalERM').modal('show');
    });

    // Lazy Load untuk Tab Component Shared (menggunakan Helpers)
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