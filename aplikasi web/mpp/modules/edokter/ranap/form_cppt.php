<?php
// File: modules/edokter/ranap/form_cppt.php
require_once '../../../config/database.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin') {
    die('<div class="alert alert-danger"><i class="fas fa-lock me-2"></i> Mode Super Admin: Pengisian CPPT dikunci.</div>');
}

$no_rawat  = $_GET['no_rawat'] ?? '';
$nip_login = $_SESSION['user_id'];

if(empty($no_rawat)) exit('<div class="alert alert-danger">No Rawat tidak ditemukan.</div>');

$last_data = [];
try {
    $stmt_hist = $pdo->prepare("SELECT * FROM pemeriksaan_ranap WHERE no_rawat = ? ORDER BY tgl_perawatan DESC, jam_rawat DESC LIMIT 1");
    $stmt_hist->execute([$no_rawat]);
    $last_data = $stmt_hist->fetch() ?: [];
} catch (Exception $e) {}

function val($data, $key) { return isset($data[$key]) ? htmlspecialchars($data[$key]) : ''; }
?>
<div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
    <h5 class="text-primary mb-0"><i class="fas fa-edit"></i> Form TTV & SOAP (Rawat Inap)</h5>
    <div id="mode-status"><span class="badge bg-success">Mode: Input Baru (Copas TTV Terakhir)</span></div>
</div>
<div id="cppt-notif"></div>
<form id="formCPPT">
    <input type="hidden" name="no_rawat" value="<?= htmlspecialchars($no_rawat) ?>">
    <input type="hidden" name="aksi_nyata" value="simpan">
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
                <?php foreach(['Compos Mentis','Somnolence','Sopor','Coma','Alert','Confusion','Voice','Pain','Unresponsive','Apatis','Delirium'] as $k) {
                    echo "<option value='$k'" . (val($last_data,'kesadaran')==$k?' selected':'') . ">$k</option>";
                } ?>
            </select>
        </div>
        <div class="col-md-2"><label class="small fw-bold">Tensi (mmHg)</label><input type="text" class="form-control form-control-sm" name="tensi" value="<?= val($last_data,'tensi') ?>" required></div>
        <div class="col-md-2"><label class="small fw-bold">Suhu (°C)</label><input type="text" class="form-control form-control-sm" name="suhu_tubuh" value="<?= val($last_data,'suhu_tubuh') ?>"></div>
        <div class="col-md-2"><label class="small fw-bold">Nadi (/mnt)</label><input type="text" class="form-control form-control-sm" name="nadi" value="<?= val($last_data,'nadi') ?>"></div>
        <div class="col-md-2"><label class="small fw-bold">RR (/mnt)</label><input type="text" class="form-control form-control-sm" name="respirasi" value="<?= val($last_data,'respirasi') ?>"></div>
        <div class="col-md-2"><label class="small fw-bold">Tinggi (cm)</label><input type="number" class="form-control form-control-sm" name="tinggi" value="<?= val($last_data,'tinggi') ?>"></div>
        <div class="col-md-2"><label class="small fw-bold">Berat (kg)</label><input type="number" class="form-control form-control-sm" name="berat" value="<?= val($last_data,'berat') ?>"></div>
        <div class="col-md-2"><label class="small fw-bold">SpO2 (%)</label><input type="text" class="form-control form-control-sm" name="spo2" value="<?= val($last_data,'spo2') ?>" required></div>
        <div class="col-md-4"><label class="small fw-bold">Alergi</label><input type="text" class="form-control form-control-sm" name="alergi" value="<?= val($last_data,'alergi') ?>" placeholder="-"></div>
        <div class="col-md-12 mt-3"><h6 class="text-primary border-bottom pb-1">SOAP (Subjektif, Objektif, Asesmen, Plan)</h6></div>
        <div class="col-md-6"><label class="small fw-bold text-danger">S (Keluhan)</label><textarea class="form-control form-control-sm" name="keluhan" rows="3" required><?= val($last_data,'keluhan') ?></textarea></div>
        <div class="col-md-6"><label class="small fw-bold text-danger">O (Objektif)</label><textarea class="form-control form-control-sm" name="pemeriksaan" rows="3" required><?= val($last_data,'pemeriksaan') ?></textarea></div>
        <div class="col-md-6"><label class="small fw-bold text-danger">A (Assessment)</label><textarea class="form-control form-control-sm" name="penilaian" rows="3" required><?= val($last_data,'penilaian') ?></textarea></div>
        <div class="col-md-6"><label class="small fw-bold text-danger">P (Plan / RTL)</label><textarea class="form-control form-control-sm" name="rtl" rows="3" required><?= val($last_data,'rtl') ?></textarea></div>
        <div class="col-md-6"><label class="small fw-bold">Instruksi</label><textarea class="form-control form-control-sm" name="instruksi" rows="2"><?= val($last_data,'instruksi') ?></textarea></div>
        <div class="col-md-6"><label class="small fw-bold">Evaluasi</label><textarea class="form-control form-control-sm" name="evaluasi" rows="2"><?= val($last_data,'evaluasi') ?></textarea></div>
    </div>
    <div class="text-end mt-4 mb-5">
        <button type="submit" class="btn btn-primary btn-sm px-4" id="btnSimpanCPPT"><i class="fas fa-save"></i> Simpan CPPT Ranap</button>
    </div>
</form>
<script>
$(document).ready(function() {
    $('.gcs-input').on('input', function() {
        var t = (parseInt($('#gcs_e').val())||0)+(parseInt($('#gcs_v').val())||0)+(parseInt($('#gcs_m').val())||0);
        $('#gcs_total').val(Math.min(t, 15));
    });

    $('#formCPPT').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var btn = $('#btnSimpanCPPT'), ori = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
        $.ajax({
            url: 'proses.php?act=simpan_cppt',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html(ori);
                if (res.status == 'success') {
                    $('#cppt-notif').html('<div class="alert alert-success alert-dismissible py-2"><i class="fas fa-check-circle me-1"></i><strong>Berhasil disimpan!</strong> ' + res.message + '<button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button></div>');
                    setTimeout(function(){ $('#cppt-notif .alert').fadeOut(500); }, 4000);

                    var mode = $form.find('input[name="aksi_nyata"]').val();
                    if (mode === 'ubah') {
                        $form.find('input[name="aksi_nyata"]').val('simpan');
                        $form.find('input[name="tgl_perawatan"], input[name="jam_rawat"]').remove();
                        $('#mode-status').html('<span class="badge bg-success">Mode: Input Baru (Copas TTV Terakhir)</span>');
                        btn.html('<i class="fas fa-save"></i> Simpan CPPT Ranap').removeClass('btn-warning text-dark').addClass('btn-primary');
                        $('#btnBatalEditCPPT').remove();
                    } else {
                        // Reset hanya field SOAP, biarkan TTV untuk referensi berikutnya
                        $form.find('textarea').val('');
                    }
                    // Auto-refresh riwayat CPPT di panel kanan
                    if (typeof refreshRiwayat === 'function') { refreshRiwayat(); }
                } else {
                    $('#cppt-notif').html('<div class="alert alert-danger py-2"><i class="fas fa-exclamation-circle me-1"></i>' + res.message + '</div>');
                }
            },
            error: function() { btn.prop('disabled', false).html(ori); alert('Kesalahan koneksi server.'); }
        });
    });
});
</script>