<?php
// File: modules/edokter/ralan/form_resume.php
require_once '../../../config/config.php'; // [FIX 1]: Load config agar dapat $base_url
require_once '../../../config/database.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin') {
    die('<div class="alert alert-danger">Mode Super Admin: Pengisian Resume dikunci.</div>');
}

$no_rawat = $_GET['no_rawat'] ?? '';
if(empty($no_rawat)) exit('<div class="alert alert-danger">No Rawat tidak ditemukan.</div>');

$data = [];

// CEK TABEL RESUME (Dengan Try-Catch agar aman)
try {
    $stmt = $pdo->prepare("SELECT * FROM resume_pasien WHERE no_rawat = ?");
    $stmt->execute([$no_rawat]);
    $data = $stmt->fetch() ?: [];
} catch (PDOException $e) {
    die('<div class="alert alert-danger m-3">Pastikan tabel <b>resume_pasien</b> sudah dibuat. <br>' . $e->getMessage() . '</div>');
}

// --- LOGIKA AUTO-FILL ---
$def_keluhan = ''; $def_rad = ''; $def_lab = '';

if (empty($data)) {
    try {
        $q_cppt = $pdo->prepare("SELECT keluhan, pemeriksaan FROM pemeriksaan_ralan WHERE no_rawat=? ORDER BY tgl_perawatan DESC, jam_rawat DESC LIMIT 1");
        $q_cppt->execute([$no_rawat]);
        if($r_cppt = $q_cppt->fetch()) {
            $def_keluhan = "S: " . $r_cppt['keluhan'] . "\nO: " . $r_cppt['pemeriksaan'];
        }
    } catch (Exception $e) {}

    try {
        $q_rad = $pdo->prepare("SELECT hasil FROM hasil_radiologi WHERE no_rawat=? ORDER BY tgl_periksa DESC, jam DESC");
        $q_rad->execute([$no_rawat]);
        while($rd = $q_rad->fetch()) { $def_rad .= $rd['hasil'] . "\n"; }
    } catch (Exception $e) {}

    try {
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
    <h5 class="text-success mb-0"><i class="fas fa-file-medical"></i> Resume Medis Pasien</h5>
    <?php if(!empty($data)): ?>
        <span class="badge bg-primary">Status: Edit Data Tersimpan</span>
    <?php else: ?>
        <span class="badge bg-success">Status: Input Baru (Auto-fill Aktif)</span>
    <?php endif; ?>
</div>

<form action="proses.php?act=simpan_resume" method="POST" id="formResume">
    <input type="hidden" name="no_rawat" value="<?= htmlspecialchars($no_rawat) ?>">
    
    <div class="row g-3">
        <div class="col-md-6">
            <label class="small fw-bold">Keluhan Utama & Riwayat</label>
            <textarea class="form-control form-control-sm" name="keluhan_utama" rows="3"><?= val($data, 'keluhan_utama') ?: $def_keluhan ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="small fw-bold">Jalannya Penyakit</label>
            <textarea class="form-control form-control-sm" name="jalannya_penyakit" rows="3"><?= val($data, 'jalannya_penyakit') ?></textarea>
        </div>
        
        <div class="col-md-6">
            <label class="small fw-bold">Pemeriksaan Penunjang (Radiologi, dll)</label>
            <textarea class="form-control form-control-sm" name="pemeriksaan_penunjang" rows="3"><?= val($data, 'pemeriksaan_penunjang') ?: trim($def_rad) ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="small fw-bold">Hasil Laboratorium Abnormal (L/H)</label>
            <textarea class="form-control form-control-sm" name="hasil_laborat" rows="3"><?= val($data, 'hasil_laborat') ?: trim($def_lab) ?></textarea>
        </div>

        <div class="col-md-12 border-top mt-4 pt-2">
            <h6 class="text-primary"><i class="fas fa-stethoscope"></i> Diagnosa Penyakit (ICD-10)</h6>
        </div>

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
        <div class="col-md-6">
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

        <div class="col-md-12 border-top mt-4 pt-2">
            <h6 class="text-primary"><i class="fas fa-syringe"></i> Prosedur / Tindakan (ICD-9)</h6>
        </div>

        <?php 
        $pros_fields = [
            ['lbl' => 'Utama', 'k' => 'utama'], ['lbl' => 'Sekunder 1', 'k' => 'sekunder'],
            ['lbl' => 'Sekunder 2', 'k' => 'sekunder2'], ['lbl' => 'Sekunder 3', 'k' => 'sekunder3']
        ];
        foreach($pros_fields as $f): 
        ?>
        <div class="col-md-6">
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
        
        <div class="col-md-12 border-top mt-4 pt-2">
            <h6 class="text-success"><i class="fas fa-pills"></i> Terapi & Kondisi Pulang</h6>
        </div>
        
        <div class="col-md-8">
            <label class="small fw-bold">Obat Pulang / Terapi Lanjutan</label>
            <textarea class="form-control form-control-sm" name="obat_pulang" rows="3"><?= val($data, 'obat_pulang') ?></textarea>
        </div>
        <div class="col-md-4">
            <label class="small fw-bold">Kondisi Pulang</label>
            <select class="form-select form-select-sm" name="kondisi_pulang">
                <option value="Hidup" <?= val($data, 'kondisi_pulang') == 'Hidup' ? 'selected' : '' ?>>Hidup / Perbaikan</option>
                <option value="Meninggal" <?= val($data, 'kondisi_pulang') == 'Meninggal' ? 'selected' : '' ?>>Meninggal</option>
            </select>
        </div>
    </div>

    <div class="text-end mt-4 mb-5">
        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Resume Medis</button>
    </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    var ajaxIcdUrl = '<?= $base_url ?>helpers/ajax/ajax_icd.php';

    // [FIX 1] Matikan paksa Focus Trap Bootstrap agar kursor tidak pernah diblokir saat mengetik
    $.fn.modal.Constructor.prototype._enforceFocus = function() {};

    // 1. Inisialisasi ICD-10
    $('.icd10').each(function() {
        $(this).select2({
            placeholder: 'Kode/Bebas',
            allowClear: true,
            tags: true, // Mengizinkan ketik bebas
            width: '100%', // Mencegah UI berantakan
            dropdownParent: $('#modalERM .modal-content'), // [FIX 2] Tempelkan ke modal-content
            ajax: {
                url: ajaxIcdUrl + '?type=icd10',
                dataType: 'json',
                delay: 300,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data.results }; },
                cache: true
            }
        }).on('select2:select', function (e) {
            var targetId = $(this).data('target');
            var nama_asli = e.params.data.nama_asli || '';
            
            if(nama_asli !== '') {
                $('#' + targetId).val(nama_asli);
            } else {
                $('#' + targetId).focus();
            }
        });
    });

    // 2. Inisialisasi ICD-9
    $('.icd9').each(function() {
        $(this).select2({
            placeholder: 'Kode/Bebas',
            allowClear: true,
            tags: true, 
            width: '100%',
            dropdownParent: $('#modalERM .modal-content'), // [FIX 2]
            ajax: {
                url: ajaxIcdUrl + '?type=icd9',
                dataType: 'json',
                delay: 300,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data.results }; },
                cache: true
            }
        }).on('select2:select', function (e) {
            var targetId = $(this).data('target');
            var nama_asli = e.params.data.nama_asli || '';
            
            if(nama_asli !== '') {
                $('#' + targetId).val(nama_asli);
            } else {
                $('#' + targetId).focus();
            }
        });
    });

    // [FIX 3] UX Enhancement: Tutup otomatis dropdown Select2 jika dokter men-scroll halaman
    $('#modalERM .modal-body, #ermTabs').on('scroll', function() {
        $('.icd10, .icd9').select2('close');
    });
});
</script>