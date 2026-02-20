<?php
// File: modules/edokter/ralan/form_cppt.php
require_once '../../../config/database.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Kunci Ganda Super Admin
if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin') {
    die('<div class="alert alert-danger"><i class="fas fa-lock me-2"></i> Mode Super Admin: Anda hanya diizinkan untuk melihat data pasien. Pengisian CPPT dikunci.</div>');
}

$no_rawat = $_GET['no_rawat'] ?? '';
$nip_login = $_SESSION['user_id']; 

if(empty($no_rawat)) exit("No Rawat tidak ditemukan.");

// Ambil data CPPT terakhir
$sql_history = "SELECT * FROM pemeriksaan_ralan WHERE no_rawat = ? ORDER BY tgl_perawatan DESC, jam_rawat DESC LIMIT 1";
$stmt_hist = $pdo->prepare($sql_history);
$stmt_hist->execute([$no_rawat]);
$last_data = $stmt_hist->fetch() ?: [];

function val($data, $key) { return isset($data[$key]) ? $data[$key] : ''; }
?>

<div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
    <h5 class="text-primary mb-0"><i class="fas fa-edit"></i> Form TTV & SOAP</h5>
    <div id="mode-status"><span class="badge bg-success">Mode: Input Baru (Copas TTV Terakhir)</span></div>
</div>

<form action="proses.php?act=simpan_cppt" method="POST" id="formSOAP">
    <input type="hidden" name="no_rawat" value="<?= $no_rawat ?>">
    <input type="hidden" name="tgl_lama" id="tgl_lama">
    <input type="hidden" name="jam_lama" id="jam_lama">
    <input type="hidden" name="aksi_nyata" id="aksi_nyata" value="simpan"> 
    
    <div class="row g-2">
        <div class="col-md-2"><label class="small fw-bold">GCS (E,V,M)</label>
            <div class="input-group input-group-sm">
                <input type="number" class="form-control gcs-input" id="gcs_e" placeholder="E">
                <input type="number" class="form-control gcs-input" id="gcs_v" placeholder="V">
                <input type="number" class="form-control gcs-input" id="gcs_m" placeholder="M">
            </div>
        </div>
        <div class="col-md-2"><label class="small fw-bold">Total GCS</label><input type="text" class="form-control form-control-sm" name="gcs" id="gcs_total" readonly value="<?= val($last_data, 'gcs') ?>"></div>
        <div class="col-md-2"><label class="small fw-bold">Kesadaran</label>
            <select class="form-select form-select-sm" name="kesadaran" id="kesadaran">
                <?php foreach(['Compos Mentis','Somnolence','Sopor','Coma','Alert','Confusion','Voice','Pain','Unresponsive','Apatis','Delirium'] as $k) echo "<option value='$k'>$k</option>"; ?>
            </select>
        </div>
        <div class="col-md-2"><label class="small fw-bold">Tensi (mmHg)</label><input type="text" class="form-control form-control-sm" name="tensi" value="<?= val($last_data, 'tensi') ?>"></div>
        <div class="col-md-2"><label class="small fw-bold">Suhu (°C)</label><input type="text" class="form-control form-control-sm" name="suhu_tubuh" value="<?= val($last_data, 'suhu_tubuh') ?>"></div>
        <div class="col-md-2"><label class="small fw-bold">Nadi (/mnt)</label><input type="text" class="form-control form-control-sm" name="nadi" value="<?= val($last_data, 'nadi') ?>"></div>
        <div class="col-md-2"><label class="small fw-bold">RR (/mnt)</label><input type="text" class="form-control form-control-sm" name="respirasi" value="<?= val($last_data, 'respirasi') ?>"></div>
        <div class="col-md-2"><label class="small fw-bold">Tinggi (cm)</label><input type="number" class="form-control form-control-sm" name="tinggi" value="<?= val($last_data, 'tinggi') ?>"></div>
        <div class="col-md-2"><label class="small fw-bold">Berat (kg)</label><input type="number" class="form-control form-control-sm" name="berat" value="<?= val($last_data, 'berat') ?>"></div>
        <div class="col-md-2"><label class="small fw-bold">SpO2 (%)</label><input type="text" class="form-control form-control-sm" name="spo2" value="<?= val($last_data, 'spo2') ?>"></div>
        <div class="col-md-4"><label class="small fw-bold">Alergi</label><input type="text" class="form-control form-control-sm" name="alergi" value="<?= val($last_data, 'alergi') ?>" placeholder="-"></div>
        
        <div class="col-md-12 mt-3"><h6 class="text-primary border-bottom pb-1">SOAP (Pemeriksaan & Rencana)</h6></div>
        <div class="col-md-6"><label class="small fw-bold">S (Subjective / Keluhan)</label><textarea class="form-control form-control-sm" name="keluhan" rows="3"><?= val($last_data, 'keluhan') ?></textarea></div>
        <div class="col-md-6"><label class="small fw-bold">O (Objective Lainnya)</label><textarea class="form-control form-control-sm" name="pemeriksaan" rows="3"><?= val($last_data, 'pemeriksaan') ?></textarea></div>
        <div class="col-md-6"><label class="small fw-bold">A (Assessment / Diagnosa)</label><textarea class="form-control form-control-sm" name="penilaian" rows="3"><?= val($last_data, 'penilaian') ?></textarea></div>
        <div class="col-md-6"><label class="small fw-bold">P (Plan / RTL)</label><textarea class="form-control form-control-sm" name="rtl" rows="3"><?= val($last_data, 'rtl') ?></textarea></div>
        <div class="col-md-6"><label class="small fw-bold">Instruksi</label><textarea class="form-control form-control-sm" name="instruksi" rows="2"><?= val($last_data, 'instruksi') ?></textarea></div>
        <div class="col-md-6"><label class="small fw-bold">Evaluasi</label><textarea class="form-control form-control-sm" name="evaluasi" rows="2"><?= val($last_data, 'evaluasi') ?></textarea></div>
    </div>
    
    <div class="text-end mt-4">
        <button type="submit" class="btn btn-primary btn-sm" id="btnSimpan"><i class="fas fa-save"></i> Simpan CPPT</button>
    </div>
</form>

<script>
    $('.gcs-input').on('input', function() {
        let e = parseInt($('#gcs_e').val()) || 0;
        let v = parseInt($('#gcs_v').val()) || 0;
        let m = parseInt($('#gcs_m').val()) || 0;
        let total = (e + v + m) > 15 ? 15 : (e + v + m);
        $('#gcs_total').val(total);
    });
</script>