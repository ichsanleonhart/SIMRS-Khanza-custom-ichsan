<?php
// File: modules/edokter/konsultasi/index.php
require_once '../../../config/config.php';
require_once '../../../config/database.php';
require_once '../../../helpers/auth_helper.php';

cekLogin();
if (!cekAkses('soap_perawatan')) { die("Akses Ditolak."); }

$kd_dokter = $_SESSION['user_id'];
$is_superadmin = isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';

try {
    $stmt_doc = $pdo->query("SELECT kd_dokter, nm_dokter FROM dokter WHERE status='1' ORDER BY nm_dokter ASC");
    $list_dokter = $stmt_doc->fetchAll();

    if ($is_superadmin) {
        $sql = "SELECT k.*, r.no_rkm_medis, p.nm_pasien, d1.nm_dokter as nama_peminta, d2.nm_dokter as nama_tujuan, j.uraian_jawaban 
                FROM konsultasi_medik k JOIN reg_periksa r ON k.no_rawat = r.no_rawat JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis JOIN dokter d1 ON k.kd_dokter = d1.kd_dokter JOIN dokter d2 ON k.kd_dokter_dikonsuli = d2.kd_dokter LEFT JOIN jawaban_konsultasi_medik j ON k.no_permintaan = j.no_permintaan ORDER BY k.tanggal DESC LIMIT 100";
        $monitoring_data = $pdo->query($sql)->fetchAll();
    } else {
        // INBOX
        $sql_inbox = "SELECT k.*, r.no_rkm_medis, p.nm_pasien, d.nm_dokter as nama_peminta, j.uraian_jawaban, j.diagnosa_kerja as diagnosa_jawab 
                      FROM konsultasi_medik k JOIN reg_periksa r ON k.no_rawat = r.no_rawat JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis JOIN dokter d ON k.kd_dokter = d.kd_dokter LEFT JOIN jawaban_konsultasi_medik j ON k.no_permintaan = j.no_permintaan WHERE k.kd_dokter_dikonsuli = :me ORDER BY k.tanggal DESC";
        $stmt = $pdo->prepare($sql_inbox);
        $stmt->execute(['me' => $kd_dokter]);
        $inbox = $stmt->fetchAll();

        // OUTBOX
        $sql_outbox = "SELECT k.*, r.no_rkm_medis, p.nm_pasien, d.nm_dokter as nama_tujuan, j.uraian_jawaban 
                       FROM konsultasi_medik k JOIN reg_periksa r ON k.no_rawat = r.no_rawat JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis JOIN dokter d ON k.kd_dokter_dikonsuli = d.kd_dokter LEFT JOIN jawaban_konsultasi_medik j ON k.no_permintaan = j.no_permintaan WHERE k.kd_dokter = :me ORDER BY k.tanggal DESC";
        $stmt2 = $pdo->prepare($sql_outbox);
        $stmt2->execute(['me' => $kd_dokter]);
        $outbox = $stmt2->fetchAll();
    }
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

require_once '../../../layout/header.php';
require_once '../../../layout/sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<style>
    table.dataTable { width: 100% !important; }
    .datatable-global td { white-space: normal !important; word-wrap: break-word; }
    /* Fix z-index Select2 di dalam Modal Bootstrap 5 */
    .select2-container--bootstrap-5 .select2-dropdown {
        z-index: 1056 !important;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="h5 mb-0 text-gray-800"><i class="fas fa-comments-medical text-primary me-2"></i> E-Konsultasi Medik</h4>
        <?php if(!$is_superadmin): ?>
            <button class="btn btn-primary btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalBuat"><i class="fas fa-plus"></i> Permintaan Baru</button>
        <?php endif; ?>
    </div>

    <?php if(isset($_GET['status'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="fas fa-check-circle me-2"></i>
            <?php 
                if($_GET['status'] == 'sukses_kirim') echo "Permintaan konsultasi berhasil dikirim.";
                elseif($_GET['status'] == 'sukses_edit') echo "Permintaan konsultasi berhasil diedit.";
                elseif($_GET['status'] == 'sukses_jawab') echo "Jawaban konsultasi berhasil disimpan.";
                elseif($_GET['status'] == 'sukses_hapus') echo "Permintaan konsultasi berhasil dihapus.";
                elseif($_GET['status'] == 'gagal_edit_sudah_dijawab') echo "<span class='text-danger'>Gagal Edit: Dokter tujuan sudah memberikan jawaban!</span>";
                elseif($_GET['status'] == 'gagal_hapus_sudah_dijawab') echo "<span class='text-danger'>Gagal Hapus: Konsultasi ini sudah dijawab.</span>";
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($is_superadmin): ?>
        <div class="card shadow-sm border-primary">
            <div class="card-header bg-white py-2"><h6 class="mb-0 fw-bold text-primary">Monitoring Konsultasi (Admin Mode)</h6></div>
            <div class="card-body p-3 table-responsive">
                <table class="table table-bordered table-hover align-middle datatable-global display nowrap" style="width:100%">
                    <thead class="bg-light text-center small"><tr><th>Waktu</th><th>Dari -> Ke</th><th>Pasien</th><th>Jenis</th><th>Isi & Jawaban</th></tr></thead>
                    <tbody>
                        <?php foreach($monitoring_data as $row): ?>
                        <tr>
                            <td class="text-center small"><?= date('d/m/y H:i', strtotime($row['tanggal'])) ?></td>
                            <td><span class="text-primary fw-bold"><?= $row['nama_peminta'] ?></span> <br> <i class="fas fa-arrow-right text-muted"></i> <span class="text-success fw-bold"><?= $row['nama_tujuan'] ?></span></td>
                            <td><?= $row['nm_pasien'] ?><br><small class="text-muted">RM: <?= $row['no_rkm_medis'] ?></small></td>
                            <td class="text-center"><span class="badge bg-secondary"><?= $row['jenis_permintaan'] ?></span></td>
                            <td class="small"><b>Q:</b> <?= $row['uraian_konsultasi'] ?><hr class="my-1 text-muted"><b>A:</b> <span class="text-success"><?= $row['uraian_jawaban'] ?? '<i class="text-muted">Belum dijawab</i>' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-top-0">
            <ul class="nav nav-tabs fw-bold px-3 pt-2 bg-light" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#inbox">Kotak Masuk (<?= count($inbox) ?>)</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#outbox">Kotak Keluar (<?= count($outbox) ?>)</a></li>
            </ul>
            <div class="card-body p-3">
                <div class="tab-content">
                    
                    <div class="tab-pane fade show active table-responsive" id="inbox">
                        <table class="table table-bordered table-hover datatable-global align-middle w-100">
                            <thead class="bg-light text-center small"><tr><th width="10%">Tanggal</th><th width="15%">Dari</th><th width="20%">Pasien</th><th width="45%">Isi Konsultasi</th><th width="10%">Aksi</th></tr></thead>
                            <tbody>
                                <?php foreach($inbox as $row): $dijawab = !empty($row['uraian_jawaban']); ?>
                                <tr>
                                    <td class="text-center small"><?= date('d/m/y H:i', strtotime($row['tanggal'])) ?></td>
                                    <td class="fw-bold text-primary"><?= $row['nama_peminta'] ?></td>
                                    <td><b><?= $row['nm_pasien'] ?></b><br><small class="text-danger">Dx: <?= $row['diagnosa_kerja'] ?></small></td>
                                    <td><span class="badge bg-secondary mb-1"><?= $row['jenis_permintaan'] ?></span><br><?= $row['uraian_konsultasi'] ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-<?= $dijawab ? 'outline-success' : 'primary' ?> fw-bold btn-jawab" 
                                            data-id="<?= $row['no_permintaan'] ?>" data-peminta="<?= $row['nama_peminta'] ?>"
                                            data-pasien="<?= $row['nm_pasien'] ?>" data-soal="<?= $row['uraian_konsultasi'] ?>"
                                            data-jawab="<?= htmlspecialchars($row['uraian_jawaban'] ?? '') ?>"
                                            data-dx="<?= htmlspecialchars($row['diagnosa_jawab'] ?? '') ?>"
                                            data-bs-toggle="modal" data-bs-target="#modalJawab">
                                            <i class="fas fa-edit"></i> <?= $dijawab ? 'Edit Balasan' : 'Jawab' ?>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="tab-pane fade table-responsive" id="outbox">
                        <table class="table table-bordered table-hover datatable-global align-middle w-100">
                            <thead class="bg-light text-center small"><tr><th width="10%">Tanggal</th><th width="15%">Ke (Tujuan)</th><th width="15%">Pasien</th><th width="25%">Pertanyaan</th><th width="25%">Jawaban</th><th width="10%">Aksi</th></tr></thead>
                            <tbody>
                                <?php foreach($outbox as $row): $dijawab = !empty($row['uraian_jawaban']); ?>
                                <tr>
                                    <td class="text-center small"><?= date('d/m/y H:i', strtotime($row['tanggal'])) ?></td>
                                    <td class="fw-bold text-success"><?= $row['nama_tujuan'] ?></td>
                                    <td><b><?= $row['nm_pasien'] ?></b><br><small class="text-muted"><?= $row['no_rkm_medis'] ?></small></td>
                                    <td><span class="badge bg-secondary mb-1"><?= $row['jenis_permintaan'] ?></span><br><?= $row['uraian_konsultasi'] ?></td>
                                    <td class="<?= $dijawab?'text-success fw-bold':'text-muted fst-italic' ?>"><?= $dijawab ? $row['uraian_jawaban'] : 'Menunggu balasan...' ?></td>
                                    <td class="text-center">
                                        <?php if(!$dijawab): ?>
                                            <button class="btn btn-sm btn-warning btn-edit mb-1 w-100" 
                                                data-id="<?= $row['no_permintaan'] ?>" data-tujuan="<?= $row['kd_dokter_dikonsuli'] ?>"
                                                data-jenis="<?= $row['jenis_permintaan'] ?>" data-dx="<?= htmlspecialchars($row['diagnosa_kerja']) ?>"
                                                data-soal="<?= htmlspecialchars($row['uraian_konsultasi']) ?>"
                                                data-bs-toggle="modal" data-bs-target="#modalEdit">
                                                <i class="fas fa-pencil-alt"></i> Edit
                                            </button>
                                            <a href="proses.php?act=hapus&id=<?= $row['no_permintaan'] ?>" class="btn btn-sm btn-danger w-100" onclick="return confirm('Hapus permintaan ini?')"><i class="fas fa-trash"></i> Batal</a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary w-100" title="Sudah dibalas, terkunci" disabled><i class="fas fa-lock"></i> Selesai</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalBuat" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle"></i> Permintaan Konsultasi Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses.php?act=kirim_permintaan" method="POST">
                <div class="modal-body bg-light">
                    <div class="mb-3">
                        <label class="small fw-bold">Cari Pasien (Nama / No.RM)</label>
                        <select class="form-control select2-pasien" name="no_rawat" required style="width: 100%;"></select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Dokter Tujuan</label>
                            <select class="form-select select2" name="dokter_tujuan" required style="width: 100%;">
                                <option value="" disabled selected>-- Pilih Dokter --</option>
                                <?php foreach($list_dokter as $d) echo "<option value='{$d['kd_dokter']}'>{$d['nm_dokter']}</option>"; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Jenis Permintaan</label>
                            <select class="form-select" name="jenis_permintaan" required>
                                <option value="Konsultasi">Konsultasi</option><option value="Evaluasi">Evaluasi</option><option value="Rawat Bersama">Rawat Bersama</option><option value="Alih Rawat">Alih Rawat</option><option value="Pre/Post Operasi">Pre/Post Operasi</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3"><label class="small fw-bold">Diagnosa Kerja (Sementara)</label><input type="text" class="form-control" name="diagnosa_kerja" required></div>
                    <div class="mb-2"><label class="small fw-bold">Uraian / Pertanyaan Konsultasi</label><textarea class="form-control" name="uraian_konsultasi" rows="3" required></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary fw-bold px-4"><i class="fas fa-paper-plane"></i> Kirim</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEdit" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning py-2">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-pencil-alt"></i> Edit Permintaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses.php?act=edit_permintaan" method="POST">
                <input type="hidden" name="no_permintaan" id="e_id">
                <div class="modal-body bg-light">
                    <div class="alert alert-info py-2 small mb-3"><i class="fas fa-info-circle"></i> Anda hanya dapat mengedit jika dokter tujuan belum membalas.</div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Dokter Tujuan</label>
                            <select class="form-select select2" name="dokter_tujuan" id="e_tujuan" required style="width: 100%;">
                                <?php foreach($list_dokter as $d) echo "<option value='{$d['kd_dokter']}'>{$d['nm_dokter']}</option>"; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Jenis Permintaan</label>
                            <select class="form-select" name="jenis_permintaan" id="e_jenis" required>
                                <option value="Konsultasi">Konsultasi</option><option value="Evaluasi">Evaluasi</option><option value="Rawat Bersama">Rawat Bersama</option><option value="Alih Rawat">Alih Rawat</option><option value="Pre/Post Operasi">Pre/Post Operasi</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3"><label class="small fw-bold">Diagnosa Kerja</label><input type="text" class="form-control" name="diagnosa_kerja" id="e_dx" required></div>
                    <div class="mb-2"><label class="small fw-bold">Uraian Konsultasi</label><textarea class="form-control" name="uraian_konsultasi" id="e_soal" rows="3" required></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-warning fw-bold px-4"><i class="fas fa-save"></i> Simpan Perubahan</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalJawab" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2">
                <h5 class="modal-title fw-bold"><i class="fas fa-reply"></i> Form Balasan Konsultasi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses.php?act=jawab_permintaan" method="POST">
                <input type="hidden" name="no_permintaan" id="j_id">
                <div class="modal-body bg-light">
                    <div class="callout callout-warning bg-white border border-warning rounded p-3 mb-3 shadow-sm">
                        <small class="text-muted">Dari: <b id="j_peminta" class="text-dark"></b> | Pasien: <b id="j_pasien" class="text-dark"></b></small><hr class="my-2">
                        <b class="text-primary">Q:</b> <i id="j_soal"></i>
                    </div>
                    <div class="mb-3"><label class="small fw-bold">Diagnosa Akhir (Saran)</label><input type="text" class="form-control" name="diagnosa_kerja" id="j_dx" required></div>
                    <div class="mb-2"><label class="small fw-bold">Uraian Jawaban / Anjuran Terapi</label><textarea class="form-control" name="uraian_jawaban" id="j_jawab" rows="4" required></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success fw-bold px-4"><i class="fas fa-check-double"></i> Simpan Jawaban</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../../layout/footer.php'; ?>

<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    
    // Perbaikan agar Select2 bisa diketik di dalam Modal Bootstrap 5
    $.fn.modal.Constructor.prototype._enforceFocus = function() {};

    // Inisialisasi Select2 Biasa
    $('.select2').select2({ 
        theme: 'bootstrap-5',
        dropdownParent: $('#modalBuat')
    });

    // Inisialisasi Select2 Biasa untuk Modal Edit
    $('#e_tujuan').select2({ 
        theme: 'bootstrap-5',
        dropdownParent: $('#modalEdit')
    });

    // INISIALISASI PENCARIAN PASIEN (AJAX) - BUG FIXED
    $('.select2-pasien').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modalBuat'), // Wajib agar bisa diketik di dalam Modal BS5
        placeholder: 'Ketik Nama Pasien / No. RM...',
        minimumInputLength: 3,
        ajax: {
            url: '../../../helpers/ajax/ajax_pasien_konsul.php', // Path API
            dataType: 'json',
            delay: 250,
            data: function (params) {
                // Beri tahu Select2 untuk mengirim parameter 'q' ke server PHP
                return {
                    q: params.term 
                };
            },
            processResults: function (data) { 
                return { results: data }; 
            }
        }
    });

    // Inisialisasi DataTables
    $('.datatable-global').DataTable({
        "ordering": true,
        "responsive": true, 
        "language": { "search": "Cari Isi/Pasien:" },
        "pageLength": 25
    });

    // Binding Data ke Modal Jawab
    $('.btn-jawab').on('click', function() {
        $('#j_id').val($(this).data('id'));
        $('#j_peminta').text($(this).data('peminta'));
        $('#j_pasien').text($(this).data('pasien'));
        $('#j_soal').text($(this).data('soal'));
        $('#j_jawab').val($(this).data('jawab'));
        $('#j_dx').val($(this).data('dx'));
    });

    // Binding Data ke Modal Edit
    $('.btn-edit').on('click', function() {
        $('#e_id').val($(this).data('id'));
        $('#e_tujuan').val($(this).data('tujuan')).trigger('change');
        $('#e_jenis').val($(this).data('jenis'));
        $('#e_dx').val($(this).data('dx'));
        $('#e_soal').val($(this).data('soal'));
    });
});
</script>