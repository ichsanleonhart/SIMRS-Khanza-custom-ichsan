<?php
session_start();
// modul/ralan/form.php
require_once '../../config/database.php';
require_once '../../config/fungsi.php';

$no_rawat = $_GET['no_rawat'] ?? '';
$nip_login = $_SESSION['login_user']; // NIP Dokter Login

if(empty($no_rawat)) exit("No Rawat tidak ditemukan.");

// 1. DATA PASIEN
$stmt = $pdo->prepare("SELECT p.nm_pasien, r.no_rkm_medis, p.tgl_lahir FROM reg_periksa r JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis WHERE r.no_rawat = ?");
$stmt->execute([$no_rawat]);
$pasien = $stmt->fetch();

// 2. AMBIL RIWAYAT CPPT (Untuk Tab Riwayat & Fitur Copas)
// Join ke pegawai/dokter untuk ambil nama pemeriksa
$sql_history = "SELECT pr.*, pg.nama as nama_pemeriksa 
                FROM pemeriksaan_ralan pr 
                LEFT JOIN pegawai pg ON pr.nip = pg.nik 
                WHERE pr.no_rawat = ? 
                ORDER BY pr.tgl_perawatan DESC, pr.jam_rawat DESC";
$stmt_hist = $pdo->prepare($sql_history);
$stmt_hist->execute([$no_rawat]);
$history = $stmt_hist->fetchAll();

// Ambil data terakhir untuk fitur 'Copas TTV' (jika input baru)
$last_data = $history[0] ?? []; 

function val($data, $key) { return isset($data[$key]) ? $data[$key] : ''; }
?>

<div class="alert alert-light border shadow-sm mb-3">
    <div class="d-flex justify-content-between">
        <div><i class="fas fa-user-injured text-primary"></i> <b><?= $pasien['nm_pasien'] ?></b> (<?= $pasien['no_rkm_medis'] ?>)</div>
        <div id="mode-status"><span class="badge badge-success">Mode: Input Baru</span></div>
    </div>
</div>

<ul class="nav nav-tabs" id="mainTab" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="input-tab" data-toggle="pill" href="#tab-input" role="tab"><i class="fas fa-edit"></i> Input CPPT</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="history-tab" data-toggle="pill" href="#tab-history" role="tab"><i class="fas fa-history"></i> Riwayat CPPT (<?= count($history) ?>)</a>
    </li>
</ul>

<div class="tab-content mt-3">
    
    <div class="tab-pane fade show active" id="tab-input" role="tabpanel">
        <form action="proses.php?act=simpan_cppt" method="POST" id="formSOAP">
            <input type="hidden" name="no_rawat" value="<?= $no_rawat ?>">
            <input type="hidden" name="tgl_lama" id="tgl_lama">
            <input type="hidden" name="jam_lama" id="jam_lama">
            <input type="hidden" name="aksi_nyata" id="aksi_nyata" value="simpan"> <div class="row">
                <div class="col-md-12 mb-2"><h6 class="text-primary border-bottom pb-1">Tanda Vital (Objective)</h6></div>
                
                <div class="col-md-2 col-4">
                    <div class="form-group">
                        <label>GCS (E, V, M)</label>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control gcs-input" id="gcs_e" placeholder="E" min="1" max="4">
                            <input type="number" class="form-control gcs-input" id="gcs_v" placeholder="V" min="1" max="5">
                            <input type="number" class="form-control gcs-input" id="gcs_m" placeholder="M" min="1" max="6">
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-4">
                    <div class="form-group">
                        <label>Total GCS</label>
                        <input type="text" class="form-control form-control-sm font-weight-bold" name="gcs" id="gcs_total" readonly placeholder="Total" value="<?= val($last_data, 'gcs') ?>">
                        <small class="text-muted">Max 15 (Satu Sehat)</small>
                    </div>
                </div>
                <div class="col-md-2 col-4">
                    <div class="form-group">
                        <label>Kesadaran</label>
                        <select class="form-control form-control-sm" name="kesadaran" id="kesadaran">
                            <?php 
                            $enum = ['Compos Mentis','Somnolence','Sopor','Coma','Alert','Confusion','Voice','Pain','Unresponsive','Apatis','Delirium'];
                            foreach($enum as $k) echo "<option value='$k'>$k</option>";
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="form-group">
                        <label>Tensi (mmHg)</label>
                        <input type="text" class="form-control form-control-sm" name="tensi" id="tensi" value="<?= val($last_data, 'tensi') ?>">
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="form-group">
                        <label>Suhu (°C)</label>
                        <input type="text" class="form-control form-control-sm" name="suhu_tubuh" id="suhu" value="<?= val($last_data, 'suhu_tubuh') ?>">
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="form-group">
                        <label>Nadi (/mnt)</label>
                        <input type="text" class="form-control form-control-sm" name="nadi" id="nadi" value="<?= val($last_data, 'nadi') ?>">
                    </div>
                </div>
                
                <div class="col-md-2 col-6">
                    <div class="form-group">
                        <label>Respirasi (/mnt)</label>
                        <input type="text" class="form-control form-control-sm" name="respirasi" id="rr" value="<?= val($last_data, 'respirasi') ?>">
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="form-group">
                        <label>Tinggi (cm)</label>
                        <input type="number" class="form-control form-control-sm" name="tinggi" id="tb" value="<?= val($last_data, 'tinggi') ?>">
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="form-group">
                        <label>Berat (kg)</label>
                        <input type="number" class="form-control form-control-sm" name="berat" id="bb" value="<?= val($last_data, 'berat') ?>">
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="form-group">
                        <label>SpO2 (%)</label>
                        <input type="text" class="form-control form-control-sm" name="spo2" id="spo2" value="<?= val($last_data, 'spo2') ?>">
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="form-group">
                        <label>Alergi</label>
                        <input type="text" class="form-control form-control-sm" name="alergi" id="alergi" value="<?= val($last_data, 'alergi') ?>" placeholder="-">
                    </div>
                </div>

                <div class="col-md-12 mb-2 mt-2"><h6 class="text-primary border-bottom pb-1">SOAP (Dokter)</h6></div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>S (Subjective / Keluhan)</label>
                        <textarea class="form-control" name="keluhan" id="keluhan" rows="3"><?= val($last_data, 'keluhan') ?></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>O (Objective Lainnya)</label>
                        <textarea class="form-control" name="pemeriksaan" id="pemeriksaan" rows="3"><?= val($last_data, 'pemeriksaan') ?></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>A (Assessment / Diagnosa)</label>
                        <textarea class="form-control" name="penilaian" id="penilaian" rows="3"><?= val($last_data, 'penilaian') ?></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>P (Plan / RTL)</label>
                        <textarea class="form-control" name="rtl" id="rtl" rows="3"><?= val($last_data, 'rtl') ?></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Instruksi</label>
                        <textarea class="form-control" name="instruksi" id="instruksi" rows="2"><?= val($last_data, 'instruksi') ?></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Evaluasi</label>
                        <textarea class="form-control" name="evaluasi" id="evaluasi" rows="2"><?= val($last_data, 'evaluasi') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-batal-edit" style="display:none;">Batal Edit</button>
                <button type="submit" class="btn btn-primary" id="btnSimpan"><i class="fas fa-save"></i> Simpan Data</button>
            </div>
        </form>
    </div>

    <div class="tab-pane fade" id="tab-history" role="tabpanel">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Oleh</th>
                        <th>Data TTV & SOAP</th>
                        <th width="10%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($history as $h): ?>
                    <tr>
                        <td><?= date('d/m/y', strtotime($h['tgl_perawatan'])) ?><br><?= $h['jam_rawat'] ?></td>
                        <td><?= $h['nama_pemeriksa'] ?></td>
                        <td style="font-size: 0.9em;">
                            <b>TTV:</b> TD:<?= $h['tensi'] ?>, N:<?= $h['nadi'] ?>, S:<?= $h['suhu_tubuh'] ?>, RR:<?= $h['respirasi'] ?>, GCS:<?= $h['gcs'] ?><br>
                            <b>S:</b> <?= $h['keluhan'] ?><br>
                            <b>O:</b> <?= $h['pemeriksaan'] ?><br>
                            <b>A:</b> <?= $h['penilaian'] ?><br>
                            <b>P:</b> <?= $h['rtl'] ?>
                        </td>
                        <td class="text-center">
                            <?php if($h['nip'] == $nip_login): ?>
                                <button class="btn btn-xs btn-warning btn-edit-cppt mb-1"
                                    data-json='<?= json_encode($h) ?>'>
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <a href="proses.php?act=hapus_cppt&no_rawat=<?= $h['no_rawat'] ?>&tgl=<?= $h['tgl_perawatan'] ?>&jam=<?= $h['jam_rawat'] ?>" 
                                   class="btn btn-xs btn-danger" onclick="return confirm('Hapus CPPT ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php else: ?>
                                <i class="fas fa-lock text-muted" title="Hanya pembuat yang bisa edit"></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(function() {
    // 1. Auto Hitung GCS
    $('.gcs-input').on('input', function() {
        let e = parseInt($('#gcs_e').val()) || 0;
        let v = parseInt($('#gcs_v').val()) || 0;
        let m = parseInt($('#gcs_m').val()) || 0;
        let total = e + v + m;
        if(total > 15) total = 15; // Max cap sesuai request
        $('#gcs_total').val(total);
    });

    // 2. Logic Edit CPPT
    $('.btn-edit-cppt').on('click', function() {
        let data = $(this).data('json');
        
        // Pindah ke Tab Input
        $('#input-tab').tab('show');
        
        // Ganti Judul & Tombol
        $('#mode-status').html('<span class="badge badge-warning">Mode: Edit Data</span>');
        $('#btnSimpan').html('<i class="fas fa-sync"></i> Update Perubahan');
        $('.btn-batal-edit').show();

        // Isi Form
        $('#tgl_lama').val(data.tgl_perawatan);
        $('#jam_lama').val(data.jam_rawat);
        $('#aksi_nyata').val('ubah'); // Flag untuk proses.php

        $('#tensi').val(data.tensi);
        $('#suhu').val(data.suhu_tubuh);
        $('#nadi').val(data.nadi);
        $('#rr').val(data.respirasi);
        $('#tb').val(data.tinggi);
        $('#bb').val(data.berat);
        $('#spo2').val(data.spo2);
        $('#gcs_total').val(data.gcs); // Load GCS Total
        $('#kesadaran').val(data.kesadaran);
        $('#alergi').val(data.alergi);
        
        $('#keluhan').val(data.keluhan);
        $('#pemeriksaan').val(data.pemeriksaan);
        $('#penilaian').val(data.penilaian);
        $('#rtl').val(data.rtl);
        $('#instruksi').val(data.instruksi);
        $('#evaluasi').val(data.evaluasi);
    });

    // 3. Batal Edit
    $('.btn-batal-edit').on('click', function() {
        $('#formSOAP')[0].reset();
        $('#mode-status').html('<span class="badge badge-success">Mode: Input Baru</span>');
        $('#btnSimpan').html('<i class="fas fa-save"></i> Simpan Data');
        $('#aksi_nyata').val('simpan');
        $(this).hide();
    });
});
</script>