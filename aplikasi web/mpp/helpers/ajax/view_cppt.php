<?php
// File: helpers/ajax/view_cppt.php
// Deskripsi: Menampilkan Riwayat CPPT (SOAP + Instruksi & Evaluasi) + tombol Edit/Hapus

$base_path = dirname(dirname(__DIR__));
require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

ini_set('display_errors', 0);
error_reporting(0);

$no_rawat      = isset($_POST['no_rawat']) ? $_POST['no_rawat'] : '';
$current_nip   = $_SESSION['user_id'] ?? '';
$is_superadmin = isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
$time_now      = new DateTime();

if (empty($no_rawat)) {
    echo '<div class="alert alert-danger">No Rawat tidak ditemukan.</div>';
    exit;
}

// Query UNION Ranap + Ralan
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
    $bulan = array(1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
    $pecahkan = explode('-', $tanggal);
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
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
            <?php foreach ($cppt_data as $row): 
                $nama_petugas = getNamaPetugas($pdo, $row['nip']);
                $jenis_class  = ($row['sumber'] == 'Rawat Inap') ? 'ranap' : 'ralan';
                $bg_badge     = ($row['sumber'] == 'Rawat Inap') ? 'bg-primary' : 'bg-success';

                // Tombol Edit/Hapus: hanya ranap, penulis yg sama, dalam 48 jam
                $waktu_cppt  = new DateTime($row['tgl_perawatan'] . ' ' . $row['jam_rawat']);
                $diff_hours  = ($time_now->getTimestamp() - $waktu_cppt->getTimestamp()) / 3600;
                $is_editable = (!$is_superadmin && $row['nip'] == $current_nip && $diff_hours <= 48 && $jenis_class == 'ranap');
            ?>
            <div class="timeline-item <?= $jenis_class ?>">
                <div class="mb-1 d-flex flex-wrap align-items-center gap-2">
                    <span class="badge <?= $bg_badge ?>"><?= $row['sumber'] ?></span>
                    <span class="text-muted fw-bold small">
                        <i class="far fa-calendar-alt me-1"></i><?= tgl_indo($row['tgl_perawatan']) ?>
                        <i class="far fa-clock ms-2 me-1"></i><?= $row['jam_rawat'] ?>
                    </span>
                    <span class="ms-auto small text-muted"><i class="fas fa-user-md me-1"></i><?= $nama_petugas ?></span>
                    <?php if($is_editable): ?>
                    <button class="btn btn-outline-warning btn-sm py-0 px-2 btn-edit-cppt" 
                        data-tgl="<?= $row['tgl_perawatan'] ?>"
                        data-jam="<?= $row['jam_rawat'] ?>"
                        data-suhu="<?= htmlspecialchars($row['suhu_tubuh'] ?? '', ENT_QUOTES) ?>"
                        data-tensi="<?= htmlspecialchars($row['tensi'] ?? '', ENT_QUOTES) ?>"
                        data-nadi="<?= htmlspecialchars($row['nadi'] ?? '', ENT_QUOTES) ?>"
                        data-respirasi="<?= htmlspecialchars($row['respirasi'] ?? '', ENT_QUOTES) ?>"
                        data-spo2="<?= htmlspecialchars($row['spo2'] ?? '', ENT_QUOTES) ?>"
                        data-gcs="<?= htmlspecialchars($row['gcs'] ?? '', ENT_QUOTES) ?>"
                        data-kesadaran="<?= htmlspecialchars($row['kesadaran'] ?? '', ENT_QUOTES) ?>"
                        data-keluhan="<?= htmlspecialchars($row['keluhan'] ?? '', ENT_QUOTES) ?>"
                        data-pemeriksaan="<?= htmlspecialchars($row['pemeriksaan'] ?? '', ENT_QUOTES) ?>"
                        data-penilaian="<?= htmlspecialchars($row['penilaian'] ?? '', ENT_QUOTES) ?>"
                        data-rtl="<?= htmlspecialchars($row['rtl'] ?? '', ENT_QUOTES) ?>"
                        data-instruksi="<?= htmlspecialchars($row['instruksi'] ?? '', ENT_QUOTES) ?>"
                        data-evaluasi="<?= htmlspecialchars($row['evaluasi'] ?? '', ENT_QUOTES) ?>"
                        title="Edit CPPT ini"><i class="fas fa-edit"></i> Edit</button>
                    <button class="btn btn-outline-danger btn-sm py-0 px-2 btn-hapus-cppt"
                        data-tgl="<?= $row['tgl_perawatan'] ?>"
                        data-jam="<?= $row['jam_rawat'] ?>"
                        title="Hapus CPPT ini"><i class="fas fa-trash"></i></button>
                    <?php endif; ?>
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
                        <div class="mb-2"><span class="soap-label">S</span><div class="content-text"><?= nl2br(htmlspecialchars($row['keluhan'])) ?></div></div>
                        <div class="mb-2"><span class="soap-label">O</span><div class="content-text"><?= nl2br(htmlspecialchars($row['pemeriksaan'])) ?></div></div>
                        <div class="mb-2"><span class="soap-label">A</span><div class="content-text"><?= nl2br(htmlspecialchars($row['penilaian'])) ?></div></div>
                        <div class="mb-2"><span class="soap-label">P</span><div class="content-text"><?= nl2br(htmlspecialchars($row['rtl'])) ?></div></div>
                        <?php if(!empty($row['instruksi'])): ?>
                            <div class="mb-2"><span class="soap-label text-warning">I</span><div class="content-text bg-warning bg-opacity-10 p-1 rounded"><?= nl2br(htmlspecialchars($row['instruksi'])) ?></div></div>
                        <?php endif; ?>
                        <?php if(!empty($row['evaluasi'])): ?>
                            <div class="mb-2"><span class="soap-label text-success">E</span><div class="content-text bg-success bg-opacity-10 p-1 rounded"><?= nl2br(htmlspecialchars($row['evaluasi'])) ?></div></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    var noRawat = '<?= htmlspecialchars($no_rawat, ENT_QUOTES) ?>';

    // ---- TOMBOL EDIT ----
    $('.btn-edit-cppt').off('click').on('click', function() {
        var btn = $(this);
        // Pindah ke tab CPPT di panel kiri
        $('#leftTabs a[href="#tab-cppt"]').tab('show');
        // Isi form dengan data CPPT yang dipilih
        $('#formCPPT input[name="aksi_nyata"]').val('ubah');
        if ($('#formCPPT input[name="tgl_perawatan"]').length === 0) {
            $('#formCPPT').append('<input type="hidden" name="tgl_perawatan">');
            $('#formCPPT').append('<input type="hidden" name="jam_rawat">');
        }
        $('#formCPPT input[name="tgl_perawatan"]').val(btn.data('tgl'));
        $('#formCPPT input[name="jam_rawat"]').val(btn.data('jam'));
        $('#formCPPT input[name="suhu_tubuh"]').val(btn.data('suhu'));
        $('#formCPPT input[name="tensi"]').val(btn.data('tensi'));
        $('#formCPPT input[name="nadi"]').val(btn.data('nadi'));
        $('#formCPPT input[name="respirasi"]').val(btn.data('respirasi'));
        $('#formCPPT input[name="spo2"]').val(btn.data('spo2'));
        $('#formCPPT #gcs_total').val(btn.data('gcs'));
        $('#formCPPT select[name="kesadaran"]').val(btn.data('kesadaran'));
        $('#formCPPT textarea[name="keluhan"]').val(btn.data('keluhan'));
        $('#formCPPT textarea[name="pemeriksaan"]').val(btn.data('pemeriksaan'));
        $('#formCPPT textarea[name="penilaian"]').val(btn.data('penilaian'));
        $('#formCPPT textarea[name="rtl"]').val(btn.data('rtl'));
        $('#formCPPT textarea[name="instruksi"]').val(btn.data('instruksi'));
        $('#formCPPT textarea[name="evaluasi"]').val(btn.data('evaluasi'));
        // Ubah UI tombol ke mode Edit
        $('#mode-status').html('<span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-edit me-1"></i>MODE EDIT CPPT &mdash; ' + btn.data('tgl') + ' ' + btn.data('jam') + '</span>');
        $('#btnSimpanCPPT').html('<i class="fas fa-save"></i> Perbarui CPPT').removeClass('btn-primary').addClass('btn-warning text-dark');
        if ($('#btnBatalEditCPPT').length === 0) {
            $('#btnSimpanCPPT').before('<button type="button" id="btnBatalEditCPPT" class="btn btn-secondary btn-sm px-3 me-2"><i class="fas fa-times"></i> Batal Edit</button>');
            $('#btnBatalEditCPPT').on('click', function() {
                $('#formCPPT input[name="aksi_nyata"]').val('simpan');
                $('#formCPPT input[name="tgl_perawatan"], #formCPPT input[name="jam_rawat"]').remove();
                $('#mode-status').html('<span class="badge bg-success">Mode: Input Baru (Copas TTV Terakhir)</span>');
                $('#btnSimpanCPPT').html('<i class="fas fa-save"></i> Simpan CPPT Ranap').removeClass('btn-warning text-dark').addClass('btn-primary');
                $('#formCPPT')[0].reset();
                $(this).remove();
            });
        }
        $('#leftTabContent').animate({ scrollTop: 0 }, 400);
    });

    // ---- TOMBOL HAPUS ----
    $('.btn-hapus-cppt').off('click').on('click', function() {
        var btn = $(this);
        if (!confirm('PERINGATAN!\n\nApakah Anda yakin ingin MENGHAPUS CPPT ini?\nData yang dihapus TIDAK dapat dikembalikan.')) return;
        var oriHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({
            url: '<?= $base_url ?>modules/edokter/ranap/proses.php?act=hapus_cppt',
            type: 'POST',
            data: { no_rawat: noRawat, tgl_perawatan: btn.data('tgl'), jam_rawat: btn.data('jam') },
            dataType: 'json',
            success: function(res) {
                if (res.status == 'success') {
                    // Reload riwayat CPPT di panel kanan
                    refreshRiwayat();
                } else {
                    btn.prop('disabled', false).html(oriHtml);
                    alert('Gagal menghapus: ' + res.message);
                }
            },
            error: function() {
                btn.prop('disabled', false).html(oriHtml);
                alert('Terjadi kesalahan koneksi.');
            }
        });
    });
});

// Fungsi global agar bisa dipanggil dari iframe / script lain di halaman yang sama
function refreshRiwayat() {
    var url = '<?= $base_url ?>helpers/ajax/view_cppt.php';
    var noRawat = '<?= htmlspecialchars($no_rawat, ENT_QUOTES) ?>';
    var $target = $('#tab-history');
    $target.html('<div class="text-center mt-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted small">Memuat riwayat...</p></div>');
    $.post(url, { no_rawat: noRawat }, function(data) {
        $target.html(data);
    });
}
</script>