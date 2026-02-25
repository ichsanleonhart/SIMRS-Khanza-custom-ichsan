<?php
// File: modules/edokter/ranap/form_resume.php
require_once '../../../config/config.php';
require_once '../../../config/database.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin') {
    die('<div class="alert alert-danger">Mode Super Admin: Pengisian Resume dikunci.</div>');
}

$no_rawat = $_GET['no_rawat'] ?? '';
if(empty($no_rawat)) exit('<div class="alert alert-danger">No Rawat tidak ditemukan.</div>');

$data = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM resume_pasien_ranap WHERE no_rawat = ?");
    $stmt->execute([$no_rawat]);
    $data = $stmt->fetch() ?: [];
} catch (PDOException $e) {
    die('<div class="alert alert-danger m-3">Tabel <b>resume_pasien_ranap</b> belum siap. <br>' . $e->getMessage() . '</div>');
}

$def_keluhan = ''; $def_fisik = ''; $def_rad = ''; $def_lab = ''; $def_diag_awal = '';

if (empty($data)) {
    try {
        $q_kamar = $pdo->prepare("SELECT diagnosa_awal FROM kamar_inap WHERE no_rawat=?");
        $q_kamar->execute([$no_rawat]);
        if($r_kamar = $q_kamar->fetch()) $def_diag_awal = $r_kamar['diagnosa_awal'];
    } catch (Exception $e) {}

    try {
        $q_cppt = $pdo->prepare("SELECT keluhan, pemeriksaan FROM pemeriksaan_ranap WHERE no_rawat=? ORDER BY tgl_perawatan DESC, jam_rawat DESC LIMIT 1");
        $q_cppt->execute([$no_rawat]);
        if($r_cppt = $q_cppt->fetch()) {
            $def_keluhan = $r_cppt['keluhan'];
            $def_fisik = $r_cppt['pemeriksaan'];
        }
    } catch (Exception $e) {}

    try {
        $q_rad = $pdo->prepare("SELECT hasil FROM hasil_radiologi WHERE no_rawat=? ORDER BY tgl_periksa DESC, jam DESC");
        $q_rad->execute([$no_rawat]);
        while($rd = $q_rad->fetch()) { $def_rad .= $rd['hasil'] . "\n"; }
        
        $q_lab = $pdo->prepare("SELECT t.Pemeriksaan, d.nilai, d.satuan, d.keterangan FROM detail_periksa_lab d JOIN template_laboratorium t ON d.id_template = t.id_template WHERE d.no_rawat=? AND d.keterangan IN ('L','H','LOW','HIGH','*','RENDAH','TINGGI')");
        $q_lab->execute([$no_rawat]);
        while($lb = $q_lab->fetch()) {
            $def_lab .= "- " . $lb['Pemeriksaan']." : ".$lb['nilai']." ".$lb['satuan']." (".$lb['keterangan'].")\n";
        }
    } catch (Exception $e) {}
}

function val($data, $key) { return isset($data[$key]) ? htmlspecialchars($data[$key]) : ''; }
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
    <h5 class="text-success mb-0"><i class="fas fa-file-medical"></i> Resume Medis Rawat Inap</h5>
    <?php if(!empty($data)): ?>
        <span class="badge bg-primary">Status: Edit Data Tersimpan</span>
    <?php else: ?>
        <span class="badge bg-success">Status: Input Baru (Auto-fill Aktif)</span>
    <?php endif; ?>
</div>

<form id="formResumeRanap">
    <input type="hidden" name="no_rawat" value="<?= htmlspecialchars($no_rawat) ?>">
    
    <div class="row g-2">
        <div class="col-md-12"><h6 class="text-primary border-bottom pb-1 mt-2">1. Indikasi & Anamnesa</h6></div>
        <div class="col-md-6">
            <label class="small fw-bold">Diagnosa Awal Masuk</label>
            <input type="text" class="form-control form-control-sm" name="diagnosa_awal" value="<?= val($data, 'diagnosa_awal') ?: $def_diag_awal ?>">
        </div>
        <div class="col-md-6">
            <label class="small fw-bold">Alasan Masuk / Dirawat</label>
            <input type="text" class="form-control form-control-sm" name="alasan" value="<?= val($data, 'alasan') ?>">
        </div>
        <div class="col-md-4">
            <label class="small fw-bold">Keluhan Utama</label>
            <textarea class="form-control form-control-sm" name="keluhan_utama" rows="3"><?= val($data, 'keluhan_utama') ?: $def_keluhan ?></textarea>
        </div>
        <div class="col-md-4">
            <label class="small fw-bold">Pemeriksaan Fisik</label>
            <textarea class="form-control form-control-sm" name="pemeriksaan_fisik" rows="3"><?= val($data, 'pemeriksaan_fisik') ?: $def_fisik ?></textarea>
        </div>
        <div class="col-md-4">
            <label class="small fw-bold">Jalannya Penyakit</label>
            <textarea class="form-control form-control-sm" name="jalannya_penyakit" rows="3"><?= val($data, 'jalannya_penyakit') ?></textarea>
        </div>

        <div class="col-md-12"><h6 class="text-primary border-bottom pb-1 mt-3">2. Penunjang & Terapi di RS</h6></div>
        <div class="col-md-6">
            <label class="small fw-bold">Pemeriksaan Penunjang (Radiologi/Lainnya)</label>
            <textarea class="form-control form-control-sm" name="pemeriksaan_penunjang" rows="3"><?= val($data, 'pemeriksaan_penunjang') ?: trim($def_rad) ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="small fw-bold">Hasil Laboratorium (Penting/Abnormal)</label>
            <textarea class="form-control form-control-sm" name="hasil_laborat" rows="3"><?= val($data, 'hasil_laborat') ?: trim($def_lab) ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="small fw-bold">Tindakan / Operasi selama di RS</label>
            <textarea class="form-control form-control-sm" name="tindakan_dan_operasi" rows="2"><?= val($data, 'tindakan_dan_operasi') ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="small fw-bold">Obat yang diberikan selama di RS</label>
            <textarea class="form-control form-control-sm" name="obat_di_rs" rows="2"><?= val($data, 'obat_di_rs') ?></textarea>
        </div>

        <div class="col-md-12"><h6 class="text-danger border-bottom pb-1 mt-3">3. Diagnosa Akhir Pasien Keluar (ICD-10)</h6></div>
        <?php 
        $diag_fields = [
            ['lbl' => 'Utama (Wajib)', 'k' => 'utama', 'req' => 'required', 'col' => 'text-danger'],
            ['lbl' => 'Sekunder 1', 'k' => 'sekunder', 'req' => '', 'col' => ''],
            ['lbl' => 'Sekunder 2', 'k' => 'sekunder2', 'req' => '', 'col' => ''],
            ['lbl' => 'Sekunder 3', 'k' => 'sekunder3', 'req' => '', 'col' => ''],
            ['lbl' => 'Sekunder 4', 'k' => 'sekunder4', 'req' => '', 'col' => '']
        ];
        foreach($diag_fields as $f): 
        ?>
        <div class="col-md-6 mb-2">
            <label class="small fw-bold <?= $f['col'] ?>">Diagnosa <?= $f['lbl'] ?></label>
            <div class="row g-1">
                <div class="col-4">
                    <select class="form-control icd10" name="kd_diagnosa_<?= $f['k'] ?>" data-target="nm_diag_<?= $f['k'] ?>" style="width: 100%;">
                        <?php if(val($data, 'kd_diagnosa_'.$f['k'])): ?>
                            <option value="<?= val($data, 'kd_diagnosa_'.$f['k']) ?>" selected><?= val($data, 'kd_diagnosa_'.$f['k']) ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-8">
                    <input type="text" class="form-control form-control-sm" name="diagnosa_<?= $f['k'] ?>" id="nm_diag_<?= $f['k'] ?>" placeholder="Nama Penyakit..." value="<?= val($data, 'diagnosa_'.$f['k']) ?>" <?= $f['req'] ?>>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="col-md-12"><h6 class="text-primary border-bottom pb-1 mt-3">4. Prosedur Utama / Sekunder (ICD-9)</h6></div>
        <?php 
        $pros_fields = [
            ['lbl' => 'Utama', 'k' => 'utama'], ['lbl' => 'Sekunder 1', 'k' => 'sekunder'],
            ['lbl' => 'Sekunder 2', 'k' => 'sekunder2'], ['lbl' => 'Sekunder 3', 'k' => 'sekunder3']
        ];
        foreach($pros_fields as $f): 
        ?>
        <div class="col-md-6 mb-2">
            <label class="small fw-bold">Prosedur <?= $f['lbl'] ?></label>
            <div class="row g-1">
                <div class="col-4">
                    <select class="form-control icd9" name="kd_prosedur_<?= $f['k'] ?>" data-target="nm_pros_<?= $f['k'] ?>" style="width: 100%;">
                        <?php if(val($data, 'kd_prosedur_'.$f['k'])): ?>
                            <option value="<?= val($data, 'kd_prosedur_'.$f['k']) ?>" selected><?= val($data, 'kd_prosedur_'.$f['k']) ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-8">
                    <input type="text" class="form-control form-control-sm" name="prosedur_<?= $f['k'] ?>" id="nm_pros_<?= $f['k'] ?>" placeholder="Nama Prosedur..." value="<?= val($data, 'prosedur_'.$f['k']) ?>">
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="col-md-12"><h6 class="text-success border-bottom pb-1 mt-3">5. Kondisi Pulang & Edukasi</h6></div>
        
        <div class="col-md-3">
            <label class="small fw-bold">Diet / Nutrisi</label>
            <input type="text" class="form-control form-control-sm" name="diet" value="<?= val($data, 'diet') ?>">
        </div>
        <div class="col-md-3">
            <label class="small fw-bold">Alergi</label>
            <input type="text" class="form-control form-control-sm" name="alergi" value="<?= val($data, 'alergi') ?>">
        </div>
        <div class="col-md-6">
            <label class="small fw-bold">Lab/Rad yang belum selesai</label>
            <input type="text" class="form-control form-control-sm" name="lab_belum" value="<?= val($data, 'lab_belum') ?>">
        </div>
        <div class="col-md-12">
            <label class="small fw-bold">Edukasi Pasien / Keluarga</label>
            <textarea class="form-control form-control-sm" name="edukasi" rows="2"><?= val($data, 'edukasi') ?></textarea>
        </div>

        <div class="col-md-3 mt-3">
            <label class="small fw-bold">Cara Keluar</label>
            <select class="form-select form-select-sm" name="cara_keluar">
                <?php foreach(['Atas Izin Dokter','Pindah RS','Pulang Atas Permintaan Sendiri','Lainnya'] as $c) echo "<option value='$c' ".(val($data,'cara_keluar')==$c?'selected':'').">$c</option>"; ?>
            </select>
        </div>
        <div class="col-md-3 mt-3">
            <label class="small fw-bold">Keterangan Cara Keluar</label>
            <input type="text" class="form-control form-control-sm" name="ket_keluar" value="<?= val($data, 'ket_keluar') ?>">
        </div>

        <div class="col-md-3 mt-3">
            <label class="small fw-bold">Keadaan Pulang</label>
            <select class="form-select form-select-sm" name="keadaan">
                <?php foreach(['Membaik','Sembuh','Keadaan Khusus','Meninggal'] as $k) echo "<option value='$k' ".(val($data,'keadaan')==$k?'selected':'').">$k</option>"; ?>
            </select>
        </div>
        <div class="col-md-3 mt-3">
            <label class="small fw-bold">Keterangan Keadaan</label>
            <input type="text" class="form-control form-control-sm" name="ket_keadaan" value="<?= val($data, 'ket_keadaan') ?>">
        </div>

        <div class="col-md-3 mt-3">
            <label class="small fw-bold">Pengobatan Lanjutan</label>
            <select class="form-select form-select-sm" name="dilanjutkan">
                <?php foreach(['Kembali Ke RS','RS Lain','Dokter Luar','Puskesmes','Lainnya'] as $l) echo "<option value='$l' ".(val($data,'dilanjutkan')==$l?'selected':'').">$l</option>"; ?>
            </select>
        </div>
        <div class="col-md-3 mt-3">
            <label class="small fw-bold">Keterangan Lanjutan</label>
            <input type="text" class="form-control form-control-sm" name="ket_dilanjutkan" value="<?= val($data, 'ket_dilanjutkan') ?>">
        </div>
        
        <div class="col-md-3 mt-3">
            <label class="small fw-bold">Jadwal Kontrol Poliklinik</label>
            <input type="datetime-local" class="form-control form-control-sm" name="kontrol" value="<?= val($data, 'kontrol') ? date('Y-m-d\TH:i', strtotime($data['kontrol'])) : '' ?>">
        </div>

        <div class="col-md-12 mt-3">
            <label class="small fw-bold text-success">Obat Pulang (Resep Bawa Pulang)</label>
            <textarea class="form-control form-control-sm" name="obat_pulang" rows="3"><?= val($data, 'obat_pulang') ?></textarea>
        </div>
    </div>

    <div class="text-end mt-4 mb-5">
        <button type="submit" class="btn btn-success btn-lg px-5 shadow" id="btnSimpanResume"><i class="fas fa-save"></i> Simpan Resume Ranap</button>
    </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    var ajaxIcdUrl = '<?= $base_url ?>helpers/ajax/ajax_icd.php';
    $.fn.modal.Constructor.prototype._enforceFocus = function() {};

    $('.icd10').each(function() {
        $(this).select2({
            placeholder: 'Kode/Bebas', allowClear: true, tags: true, width: '100%',
            dropdownParent: $('#modalERM .modal-content'),
            ajax: { url: ajaxIcdUrl + '?type=icd10', dataType: 'json', delay: 300, data: function (p) { return { q: p.term }; }, processResults: function (d) { return { results: d.results }; }, cache: true }
        }).on('select2:select', function (e) {
            var targetId = $(this).data('target');
            var nama_asli = e.params.data.nama_asli || '';
            if(nama_asli !== '') { $('#' + targetId).val(nama_asli); } else { $('#' + targetId).focus(); }
        });
    });

    $('.icd9').each(function() {
        $(this).select2({
            placeholder: 'Kode/Bebas', allowClear: true, tags: true, width: '100%',
            dropdownParent: $('#modalERM .modal-content'),
            ajax: { url: ajaxIcdUrl + '?type=icd9', dataType: 'json', delay: 300, data: function (p) { return { q: p.term }; }, processResults: function (d) { return { results: d.results }; }, cache: true }
        }).on('select2:select', function (e) {
            var targetId = $(this).data('target');
            var nama_asli = e.params.data.nama_asli || '';
            if(nama_asli !== '') { $('#' + targetId).val(nama_asli); } else { $('#' + targetId).focus(); }
        });
    });

    $('#modalERM .modal-body, #ermTabs').on('scroll', function() { $('.icd10, .icd9').select2('close'); });

    // LOGIKA AJAX SIMPAN
    $('#formResumeRanap').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#btnSimpanResume');
        var ori = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: 'proses.php?act=simpan_resume',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html(ori);
                if(res.status == 'success') {
                    // Tampilkan notif inline
var $notif = $('<div class="alert alert-success alert-dismissible py-2 mb-3"><i class="fas fa-check-circle me-1"></i><strong>Resume tersimpan!</strong> ' + res.message + '<button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button></div>');
                    if ($('#resume-notif').length) { $('#resume-notif').html($notif); } else { $('#formResumeRanap').prepend($notif); }
                    setTimeout(function(){ $notif.fadeOut(500); }, 4000);
                    // Auto-refresh riwayat di panel kanan
                    if (typeof refreshRiwayat === 'function') { refreshRiwayat(); }
                } else {
                    alert("GAGAL: " + res.message);
                }
            },
            error: function() {
                btn.prop('disabled', false).html(ori);
                alert("Terjadi kesalahan koneksi server.");
            }
        });
    });
});
</script>