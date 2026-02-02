<?php
// modul/konsultasi/index.php
session_start();
require_once '../../config/database.php';
require_once '../../config/fungsi.php';

if (!isset($_SESSION['login_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$title = 'e-Konsultasi Medik';
$menu  = 'konsultasi';
$kd_dokter = $_SESSION['kd_dokter'] ?? '';
$is_admin_mode = empty($kd_dokter);

try {
    $stmt_doc = $pdo->query("SELECT kd_dokter, nm_dokter FROM dokter WHERE status='1' ORDER BY nm_dokter ASC");
    $list_dokter = $stmt_doc->fetchAll();

    if ($is_admin_mode) {
        // ADMIN MODE
        $sql = "SELECT k.*, r.no_rkm_medis, p.nm_pasien, d1.nm_dokter as nama_peminta, d2.nm_dokter as nama_tujuan, j.uraian_jawaban 
                FROM konsultasi_medik k JOIN reg_periksa r ON k.no_rawat = r.no_rawat JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis JOIN dokter d1 ON k.kd_dokter = d1.kd_dokter JOIN dokter d2 ON k.kd_dokter_dikonsuli = d2.kd_dokter LEFT JOIN jawaban_konsultasi_medik j ON k.no_permintaan = j.no_permintaan ORDER BY k.tanggal DESC LIMIT 50";
        $monitoring_data = $pdo->query($sql)->fetchAll();
    } else {
        // DOKTER MODE
        // 1. INBOX
        $sql_inbox = "SELECT k.*, r.no_rkm_medis, p.nm_pasien, d.nm_dokter as nama_peminta, j.uraian_jawaban, j.diagnosa_kerja as diagnosa_jawab 
                      FROM konsultasi_medik k JOIN reg_periksa r ON k.no_rawat = r.no_rawat JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis JOIN dokter d ON k.kd_dokter = d.kd_dokter LEFT JOIN jawaban_konsultasi_medik j ON k.no_permintaan = j.no_permintaan WHERE k.kd_dokter_dikonsuli = :me ORDER BY k.tanggal DESC";
        $stmt = $pdo->prepare($sql_inbox);
        $stmt->execute(['me' => $kd_dokter]);
        $inbox = $stmt->fetchAll();

        // 2. OUTBOX
        $sql_outbox = "SELECT k.*, r.no_rkm_medis, p.nm_pasien, d.nm_dokter as nama_tujuan, j.uraian_jawaban 
                       FROM konsultasi_medik k JOIN reg_periksa r ON k.no_rawat = r.no_rawat JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis JOIN dokter d ON k.kd_dokter_dikonsuli = d.kd_dokter LEFT JOIN jawaban_konsultasi_medik j ON k.no_permintaan = j.no_permintaan WHERE k.kd_dokter = :me ORDER BY k.tanggal DESC";
        $stmt2 = $pdo->prepare($sql_outbox);
        $stmt2->execute(['me' => $kd_dokter]);
        $outbox = $stmt2->fetchAll();
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

require_once '../../layout/header.php';
require_once '../../layout/sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css">
<style>
    /* Paksa tabel agar 100% lebar container */
    table.dataTable {
        width: 100% !important;
    }
    /* Agar teks panjang di kolom uraian turun ke bawah (wrap), tidak memanjang */
    .datatable-global td {
        white-space: normal !important;
        word-wrap: break-word;
    }
    /* Perbaikan visual tombol expand (+) pada mobile */
    table.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control:before, 
    table.dataTable.dtr-inline.collapsed>tbody>tr>th.dtr-control:before {
        background-color: #007bff; /* Warna biru sesuai tema */
    }
</style>

<div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6"><h1>Konsultasi Medik</h1></div>
          <div class="col-sm-6 text-right">
              <?php if(!$is_admin_mode): ?>
                <button class="btn btn-primary" data-toggle="modal" data-target="#modalBuat"><i class="fas fa-plus"></i> Permintaan Baru</button>
              <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        
        <?php if(isset($_GET['status'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <?php 
                    if($_GET['status'] == 'sukses_edit') echo "Permintaan berhasil diedit.";
                    elseif($_GET['status'] == 'gagal_edit_sudah_dijawab') echo "Gagal Edit: Dokter tujuan sudah menjawab!";
                    else echo "Status: " . htmlspecialchars($_GET['status']);
                ?>
            </div>
        <?php endif; ?>

        <?php if ($is_admin_mode): ?>
            <div class="card card-indigo">
                <div class="card-header"><h3 class="card-title">Monitoring Konsultasi</h3></div>
                <div class="card-body">
                    <table class="table table-bordered table-striped datatable-global display nowrap" style="width:100%">
                        <thead><tr><th>Waktu</th><th>Dari -> Ke</th><th>Pasien</th><th>Jenis</th><th>Isi & Jawaban</th></tr></thead>
                        <tbody>
                            <?php foreach($monitoring_data as $row): ?>
                            <tr>
                                <td><?= date('d/m/y H:i', strtotime($row['tanggal'])) ?></td>
                                <td><span class="text-primary"><?= $row['nama_peminta'] ?></span> -> <span class="text-success"><?= $row['nama_tujuan'] ?></span></td>
                                <td><?= $row['nm_pasien'] ?></td>
                                <td><?= $row['jenis_permintaan'] ?></td>
                                <td>Q: <?= $row['uraian_konsultasi'] ?><br><hr class="my-1">A: <?= $row['uraian_jawaban'] ?? '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="card card-primary card-outline card-outline-tabs">
                <div class="card-header p-0 border-bottom-0">
                    <ul class="nav nav-tabs" id="konsulTab" role="tablist">
                        <li class="nav-item"><a class="nav-link active" id="inbox-tab" data-toggle="pill" href="#inbox">Kotak Masuk (<?= count($inbox) ?>)</a></li>
                        <li class="nav-item"><a class="nav-link" id="outbox-tab" data-toggle="pill" href="#outbox">Kotak Keluar (<?= count($outbox) ?>)</a></li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="inbox">
                            <table class="table table-bordered table-striped datatable-global display nowrap" style="width:100%">
                                <thead><tr><th>Tanggal</th><th>Dari</th><th>Pasien</th><th>Isi</th><th>Aksi</th></tr></thead>
                                <tbody>
                                    <?php foreach($inbox as $row): $dijawab = !empty($row['uraian_jawaban']); ?>
                                    <tr>
                                        <td><?= date('d/m/y H:i', strtotime($row['tanggal'])) ?></td>
                                        <td><?= $row['nama_peminta'] ?></td>
                                        <td><?= $row['nm_pasien'] ?><br><small class="text-danger"><?= $row['diagnosa_kerja'] ?></small></td>
                                        <td><span class="badge badge-secondary"><?= $row['jenis_permintaan'] ?></span><br><?= $row['uraian_konsultasi'] ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info btn-jawab" 
                                                data-id="<?= $row['no_permintaan'] ?>"
                                                data-peminta="<?= $row['nama_peminta'] ?>"
                                                data-pasien="<?= $row['nm_pasien'] ?>"
                                                data-soal="<?= $row['uraian_konsultasi'] ?>"
                                                data-jawab="<?= $row['uraian_jawaban'] ?? '' ?>"
                                                data-dx="<?= $row['diagnosa_jawab'] ?? '' ?>"
                                                data-toggle="modal" data-target="#modalJawab">
                                                <i class="fas fa-edit"></i> <?= $dijawab ? 'Edit' : 'Jawab' ?>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="tab-pane fade" id="outbox">
                            <table class="table table-bordered table-striped datatable-global display nowrap" style="width:100%">
                                <thead><tr><th>Tanggal</th><th>Ke</th><th>Pasien</th><th>Isi</th><th>Jawaban</th><th>Aksi</th></tr></thead>
                                <tbody>
                                    <?php foreach($outbox as $row): $dijawab = !empty($row['uraian_jawaban']); ?>
                                    <tr>
                                        <td><?= date('d/m/y H:i', strtotime($row['tanggal'])) ?></td>
                                        <td><?= $row['nama_tujuan'] ?></td>
                                        <td><?= $row['nm_pasien'] ?></td>
                                        <td><span class="badge badge-secondary"><?= $row['jenis_permintaan'] ?></span><br><?= $row['uraian_konsultasi'] ?></td>
                                        <td><?= $dijawab ? '<small class="text-success">'.$row['uraian_jawaban'].'</small>' : '<i class="text-muted">Menunggu...</i>' ?></td>
                                        <td>
                                            <?php if(!$dijawab): ?>
                                                <button class="btn btn-sm btn-warning btn-edit" 
                                                    data-id="<?= $row['no_permintaan'] ?>"
                                                    data-tujuan="<?= $row['kd_dokter_dikonsuli'] ?>"
                                                    data-jenis="<?= $row['jenis_permintaan'] ?>"
                                                    data-dx="<?= $row['diagnosa_kerja'] ?>"
                                                    data-soal="<?= $row['uraian_konsultasi'] ?>"
                                                    data-toggle="modal" data-target="#modalEdit">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                                <a href="proses.php?act=hapus&id=<?= $row['no_permintaan'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus permintaan ini?')"><i class="fas fa-trash"></i></a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" title="Sudah dibalas, terkunci" disabled><i class="fas fa-lock"></i></button>
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
    </section>
</div>

<div class="modal fade" id="modalBuat">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary"><h5 class="modal-title">Permintaan Baru</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <form action="proses.php?act=kirim_permintaan" method="POST">
                <div class="modal-body">
                    <div class="form-group"><label>Pasien</label><select class="form-control select2-pasien" name="no_rawat" required style="width: 100%;"></select></div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group"><label>Tujuan</label>
                                <select class="form-control select2" name="dokter_tujuan" required style="width: 100%;">
                                    <?php foreach($list_dokter as $d) echo "<option value='{$d['kd_dokter']}'>{$d['nm_dokter']}</option>"; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group"><label>Jenis</label>
                                <select class="form-control" name="jenis_permintaan" required>
                                    <option value="Konsultasi">Konsultasi</option><option value="Evaluasi">Evaluasi</option><option value="Rawat Bersama">Rawat Bersama</option><option value="Alih Rawat">Alih Rawat</option><option value="Pre/Post Operasi">Pre/Post Operasi</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group"><label>Diagnosa</label><input type="text" class="form-control" name="diagnosa_kerja" required></div>
                    <div class="form-group"><label>Uraian</label><textarea class="form-control" name="uraian_konsultasi" rows="3" required></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Kirim</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEdit">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning"><h5 class="modal-title">Edit Permintaan</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <form action="proses.php?act=edit_permintaan" method="POST">
                <input type="hidden" name="no_permintaan" id="e_id">
                <div class="modal-body">
                    <div class="alert alert-info py-2"><small>Anda hanya dapat mengedit jika dokter tujuan belum membalas.</small></div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group"><label>Dokter Tujuan</label>
                                <select class="form-control select2" name="dokter_tujuan" id="e_tujuan" required style="width: 100%;">
                                    <?php foreach($list_dokter as $d) echo "<option value='{$d['kd_dokter']}'>{$d['nm_dokter']}</option>"; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group"><label>Jenis Permintaan</label>
                                <select class="form-control" name="jenis_permintaan" id="e_jenis" required>
                                    <option value="Konsultasi">Konsultasi</option><option value="Evaluasi">Evaluasi</option><option value="Rawat Bersama">Rawat Bersama</option><option value="Alih Rawat">Alih Rawat</option><option value="Pre/Post Operasi">Pre/Post Operasi</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group"><label>Diagnosa Kerja</label><input type="text" class="form-control" name="diagnosa_kerja" id="e_dx" required></div>
                    <div class="form-group"><label>Uraian Konsultasi</label><textarea class="form-control" name="uraian_konsultasi" id="e_soal" rows="3" required></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-warning">Simpan Perubahan</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalJawab">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info"><h5 class="modal-title">Jawab Konsultasi</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <form action="proses.php?act=jawab_permintaan" method="POST">
                <input type="hidden" name="no_permintaan" id="j_id">
                <div class="modal-body">
                    <div class="callout callout-warning"><small>Dari: <b id="j_peminta"></b> | Pasien: <b id="j_pasien"></b></small><br>Q: <i id="j_soal"></i></div>
                    <div class="form-group"><label>Diagnosa</label><input type="text" class="form-control" name="diagnosa_kerja" id="j_dx" required></div>
                    <div class="form-group"><label>Jawaban</label><textarea class="form-control" name="uraian_jawaban" id="j_jawab" rows="4" required></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-info">Simpan</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../layout/footer.php'; ?>

<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap4.min.js"></script>

<script>
$(function() {
    // 1. Inisialisasi DataTables dengan fitur Responsive
    $('.datatable-global').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true, // INI KUNCINYA
        "language": {
            "search": "Cari:",
            "emptyTable": "Tidak ada data",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
            "lengthMenu": "Tampilkan _MENU_ baris"
        }
    });

    // 2. Select2 untuk Pencarian Pasien
    $('.select2-pasien').select2({
        theme: 'bootstrap4',
        placeholder: 'Cari Nama / No RM...',
        minimumInputLength: 3,
        ajax: {
            url: '../../ajax_pasien.php',
            dataType: 'json',
            delay: 250,
            processResults: function (data) { return { results: data }; }
        }
    });

    // 3. Handle Tombol Jawab
    $('.btn-jawab').on('click', function() {
        $('#j_id').val($(this).data('id'));
        $('#j_peminta').text($(this).data('peminta'));
        $('#j_pasien').text($(this).data('pasien'));
        $('#j_soal').text($(this).data('soal'));
        $('#j_jawab').val($(this).data('jawab'));
        $('#j_dx').val($(this).data('dx'));
    });

    // 4. Handle Tombol Edit
    $('.btn-edit').on('click', function() {
        $('#e_id').val($(this).data('id'));
        $('#e_tujuan').val($(this).data('tujuan')).trigger('change');
        $('#e_jenis').val($(this).data('jenis'));
        $('#e_dx').val($(this).data('dx'));
        $('#e_soal').val($(this).data('soal'));
    });
});
</script>