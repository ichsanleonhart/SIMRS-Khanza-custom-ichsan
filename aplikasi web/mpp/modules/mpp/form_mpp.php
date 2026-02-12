<?php
// File: modules/mpp/form_mpp.php (REVISI: FIX JEBAKAN BETMEN & FILTER PETUGAS)

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../helpers/auth_helper.php';

cekLogin();

// --- HELPER FUNCTION ---
function fetchOne($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return false; }
}

$no_rawat = $_GET['no_rawat'] ?? '';
if(!$no_rawat) die("Error: No Rawat tidak ditemukan.");

// 1. DATA PASIEN & DPJP AWAL
// Kita ambil kd_dokter juga dari sini untuk default value
$sql_pasien = "SELECT p.nm_pasien, p.no_rkm_medis, p.tgl_lahir, p.jk, reg.tgl_registrasi, 
                      d.nm_dokter, reg.kd_dokter 
               FROM reg_periksa reg 
               JOIN pasien p ON reg.no_rkm_medis = p.no_rkm_medis 
               LEFT JOIN dokter d ON reg.kd_dokter = d.kd_dokter
               WHERE reg.no_rawat = ?";
$pasien = fetchOne($pdo, $sql_pasien, [$no_rawat]);

// 2. DATA EXISTING (Jika Ada)
$skrining = fetchOne($pdo, "SELECT * FROM mpp_skrining WHERE no_rawat = ?", [$no_rawat]);
$evaluasi = fetchOne($pdo, "SELECT * FROM mpp_evaluasi WHERE no_rawat = ?", [$no_rawat]);

// --- LOGIKA DEFAULT VALUE (ANTI JEBAKAN BETMEN) ---

// A. DPJP
// Jika sudah ada di evaluasi, pakai itu. Jika belum, pakai dari registrasi.
$kd_dokter_val = '';
$nm_dokter_val = '';

if (!empty($evaluasi['kd_dokter']) && $evaluasi['kd_dokter'] != '-') {
    $kd_dokter_val = $evaluasi['kd_dokter'];
    // Ambil nama dokter dari DB biar akurat
    $d = fetchOne($pdo, "SELECT nm_dokter FROM dokter WHERE kd_dokter=?", [$kd_dokter_val]);
    $nm_dokter_val = $d ? $d['nm_dokter'] : '-';
} else {
    // Default dari Registrasi Pasien
    $kd_dokter_val = $pasien['kd_dokter'];
    $nm_dokter_val = $pasien['nm_dokter'];
}

// B. KONSULAN
$kd_konsulan_val = '';
$nm_konsulan_val = '';

if (!empty($evaluasi['kd_konsulan']) && $evaluasi['kd_konsulan'] != '-') {
    $kd_konsulan_val = $evaluasi['kd_konsulan'];
    $k = fetchOne($pdo, "SELECT nm_dokter FROM dokter WHERE kd_dokter=?", [$kd_konsulan_val]);
    $nm_konsulan_val = $k ? $k['nm_dokter'] : '-';
}

// C. PETUGAS MPP
// Default KOSONG (sesuai request), jangan ambil session user.
$nip_petugas_val = '';
$nm_petugas_val  = '';

if (!empty($evaluasi['nip']) && $evaluasi['nip'] != '-') {
    $nip_petugas_val = $evaluasi['nip'];
    // Cek nama di pegawai
    $p = fetchOne($pdo, "SELECT nama FROM pegawai WHERE nik=?", [$nip_petugas_val]);
    $nm_petugas_val = $p ? $p['nama'] : '';
}

// 3. CHECKBOX MASALAH
$masalah_checked = [];
try {
    $q_masalah = $pdo->prepare("SELECT kode_masalah FROM mpp_evaluasi_masalah WHERE no_rawat = ?");
    $q_masalah->execute([$no_rawat]);
    while($row = $q_masalah->fetch(PDO::FETCH_ASSOC)) { 
        $masalah_checked[] = $row['kode_masalah']; 
    }
} catch (Exception $e) {}

// 4. LIST CATATAN
$list_catatan = [];
try {
    $catatan = $pdo->prepare("SELECT c.*, p.nama as nama_petugas FROM mpp_evaluasi_catatan c LEFT JOIN pegawai p ON c.nip = p.nik WHERE c.no_rawat = ? ORDER BY c.tgl_implementasi DESC");
    $catatan->execute([$no_rawat]);
    $list_catatan = $catatan->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }

// 5. MASTER DATA
$master_masalah = $pdo->query("SELECT * FROM master_masalah_mpp ORDER BY kode_masalah ASC")->fetchAll(PDO::FETCH_ASSOC);
$master_dokter = $pdo->query("SELECT kd_dokter, nm_dokter FROM dokter WHERE status='1' ORDER BY nm_dokter ASC")->fetchAll(PDO::FETCH_ASSOC);

// 6. MASTER PETUGAS (FILTER STRICT: Bukan Dokter)
// Mengambil data pegawai yang NIK-nya TIDAK ADA di tabel dokter
$master_petugas = $pdo->query("
    SELECT nik, nama 
    FROM pegawai 
    WHERE stts_aktif='AKTIF' 
    AND nik NOT IN (SELECT kd_dokter FROM dokter) 
    ORDER BY nama ASC
")->fetchAll(PDO::FETCH_ASSOC);

require_once '../../layout/header.php';
require_once '../../layout/sidebar.php';
?>

<div class="container-fluid">
    <div class="card shadow-sm mb-3 border-start border-4 border-info">
        <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold"><?= $pasien['nm_pasien'] ?> (<?= $pasien['no_rkm_medis'] ?>)</h5>
                    <small class="text-muted">
                        No Rawat: <?= $no_rawat ?> | Tgl Lahir: <?= $pasien['tgl_lahir'] ?> | 
                        JK: <?= $pasien['jk'] == 'L' ? 'Laki-laki' : 'Perempuan' ?>
                    </small>
                </div>
                <a href="../../modules/ranap/index.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs" id="mppTabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#skrining"><i class="fas fa-tasks me-1"></i> Skrining</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#evaluasi"><i class="fas fa-file-medical me-1"></i> Form A (Evaluasi)</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#catatan"><i class="fas fa-history me-1"></i> Form B (Catatan)</a></li>
    </ul>

    <div class="tab-content card border-top-0 shadow-sm bg-white p-3">
        
        <div class="tab-pane fade show active" id="skrining">
            <form id="formSkrining">
                <input type="hidden" name="action" value="save_skrining">
                <input type="hidden" name="no_rawat" value="<?= $no_rawat ?>">
                <div class="row g-2">
                    <?php 
                    $params = [
                        1 => "Keluhan pembiayaan", 2 => "Penundaan tindakan diagnostik", 3 => "Keluhan pembiayaan/klaim melebihi",
                        4 => "Berisiko tinggi komplain", 5 => "Sering masuk IGD (1x24 jam)", 6 => "Usia >65 thn ketergantungan",
                        7 => "Kasus kompleks (kronis/komplikasi)", 8 => "Pasien APS", 9 => "Tidak ada keluarga",
                        10 => "Ditangani >2 spesialis bermasalah", 11 => "Penolakan diagnostik", 12 => "Penolakan keperawatan",
                        13 => "Penolakan medis", 14 => "Penundaan medis", 15 => "Mental/Narkoba/Terlantar",
                        16 => "Butuh kontinuitas pelayanan"
                    ];
                    foreach($params as $k => $label): 
                        $val = isset($skrining["param$k"]) ? $skrining["param$k"] : 'Tidak';
                    ?>
                    <div class="col-md-6 border-bottom pb-1">
                        <div class="d-flex justify-content-between">
                            <label class="small"><?= $k ?>. <?= $label ?></label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="param<?= $k ?>" value="Ya" <?= $val=='Ya'?'checked':'' ?>> <small>Ya</small>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="param<?= $k ?>" value="Tidak" <?= $val=='Tidak'?'checked':'' ?>> <small>Tidak</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-primary btn-sm mt-3"><i class="fas fa-save"></i> Simpan Skrining</button>
            </form>
        </div>

        <div class="tab-pane fade" id="evaluasi">
            <form id="formEvaluasi">
                <input type="hidden" name="action" value="save_evaluasi">
                <input type="hidden" name="no_rawat" value="<?= $no_rawat ?>">

                <div class="card bg-light mb-3 border-0">
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="small fw-bold">Dokter P.J. (DPJP)</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" id="nm_dokter" value="<?= $nm_dokter_val ?>" readonly>
                                    <input type="hidden" name="kd_dokter" id="kd_dokter" value="<?= $kd_dokter_val ?>">
                                    <button class="btn btn-secondary btn-cari-dokter" type="button" data-target="kd_dokter"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold">Dokter Konsulan</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" id="nm_konsulan" value="<?= $nm_konsulan_val ?>" readonly>
                                    <input type="hidden" name="kd_konsulan" id="kd_konsulan" value="<?= $kd_konsulan_val ?>">
                                    <button class="btn btn-secondary btn-cari-dokter" type="button" data-target="kd_konsulan"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold text-danger">Petugas MPP (Wajib)</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" id="nm_petugas" value="<?= $nm_petugas_val ?>" readonly placeholder="-- Pilih Petugas --">
                                    <input type="hidden" name="nip_petugas" id="nip_petugas" value="<?= $nip_petugas_val ?>">
                                    <button class="btn btn-danger btn-cari-petugas" type="button"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="small fw-bold">Diagnosis Medis</label>
                        <textarea class="form-control form-control-sm" name="diagnosis" rows="2"><?= $evaluasi['diagnosis'] ?? '' ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold">Kelompok Resiko</label>
                        <textarea class="form-control form-control-sm" name="kelompok" rows="2"><?= $evaluasi['kelompok'] ?? '' ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="small fw-bold">Asesmen / Kondisi Pasien</label>
                        <textarea class="form-control form-control-sm" name="assesmen" rows="2"><?= $evaluasi['assesmen'] ?? '' ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="small fw-bold">Identifikasi Masalah (Centang & Tambahkan Manual)</label>
                        <div class="border rounded p-2 bg-light mb-2" style="max-height: 200px; overflow-y: auto;">
                            <?php foreach($master_masalah as $m): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="masalah_check[]" value="<?= $m['kode_masalah'] ?>" 
                                    <?= in_array($m['kode_masalah'], $masalah_checked) ? 'checked' : '' ?>>
                                <label class="form-check-label small"><?= $m['nama_masalah'] ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <textarea class="form-control form-control-sm" name="identifikasi" rows="2" placeholder="Ketik tambahan identifikasi masalah disini..."><?= $evaluasi['identifikasi'] ?? '' ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="small fw-bold">Perencanaan</label>
                        <textarea class="form-control form-control-sm" name="rencana" rows="2"><?= $evaluasi['rencana'] ?? '' ?></textarea>
                    </div>
                </div>

                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save"></i> Simpan Form A</button>
                </div>
            </form>
        </div>

        <div class="tab-pane fade" id="catatan">
            <div class="alert alert-info py-2 small mb-2">
                <i class="fas fa-info-circle"></i> Catatan ini akan disimpan atas nama Petugas di Form A.
            </div>

            <form id="formCatatan">
                <input type="hidden" name="action" value="save_catatan">
                <input type="hidden" name="no_rawat" value="<?= $no_rawat ?>">
                <input type="hidden" name="nip_petugas_catatan" id="nip_petugas_catatan" value="<?= $nip_petugas_val ?>">
                
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="small fw-bold">Masalah</label>
                        <textarea class="form-control form-control-sm" name="masalah" rows="2" required></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">Tindak Lanjut</label>
                        <textarea class="form-control form-control-sm" name="tinjut" rows="2" required></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">Evaluasi Hasil</label>
                        <textarea class="form-control form-control-sm" name="evaluasi" rows="2" required></textarea>
                    </div>
                </div>
                <div class="text-end mb-3">
                    <button type="submit" class="btn btn-info btn-sm text-white">Tambah Catatan</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-sm small">
                    <thead class="bg-light">
                        <tr>
                            <th width="15%">Tanggal</th>
                            <th width="25%">Masalah</th>
                            <th width="25%">Tindak Lanjut</th>
                            <th width="20%">Evaluasi</th>
                            <th width="15%">Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($list_catatan as $cat): ?>
                        <tr>
                            <td><?= $cat['tgl_implementasi'] ?></td>
                            <td><?= nl2br($cat['masalah']) ?></td>
                            <td><?= nl2br($cat['tinjut']) ?></td>
                            <td><?= nl2br($cat['evaluasi']) ?></td>
                            <td><?= $cat['nama_petugas'] ?? $cat['nip'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDokter" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2 bg-light">
                <h6 class="modal-title">Pilih Dokter</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2">
                <input type="text" id="cariDokter" class="form-control form-control-sm mb-2" placeholder="Cari nama dokter...">
                <div class="list-group" id="listDokter">
                    <?php foreach($master_dokter as $dok): ?>
                        <button type="button" class="list-group-item list-group-item-action small item-dokter" 
                            data-kd="<?= $dok['kd_dokter'] ?>" data-nm="<?= $dok['nm_dokter'] ?>">
                            <?= $dok['nm_dokter'] ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPetugas" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2 bg-light">
                <h6 class="modal-title">Pilih Petugas MPP</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2">
                <input type="text" id="cariPetugas" class="form-control form-control-sm mb-2" placeholder="Cari nama petugas...">
                <div class="list-group" id="listPetugas">
                    <?php foreach($master_petugas as $p): ?>
                        <button type="button" class="list-group-item list-group-item-action small item-petugas" 
                            data-nip="<?= $p['nik'] ?>" data-nama="<?= $p['nama'] ?>">
                            <?= $p['nama'] ?> (<?= $p['nik'] ?>)
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../layout/footer.php'; ?>

<script>
$(document).ready(function() {
    
    // --- LOGIC DOKTER ---
    var targetInput = ''; 
    var targetName = '';

    $('.btn-cari-dokter').click(function() {
        targetInput = $(this).data('target'); 
        targetName = targetInput === 'kd_dokter' ? 'nm_dokter' : 'nm_konsulan';
        $('#modalDokter').modal('show');
    });

    $('#cariDokter').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $("#listDokter button").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    $('.item-dokter').click(function() {
        $('#' + targetInput).val($(this).data('kd'));
        $('#' + targetName).val($(this).data('nm'));
        $('#modalDokter').modal('hide');
    });

    // --- LOGIC PETUGAS ---
    $('.btn-cari-petugas').click(function() {
        $('#modalPetugas').modal('show');
    });

    $('#cariPetugas').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $("#listPetugas button").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    $('.item-petugas').click(function() {
        var nip = $(this).data('nip');
        var nama = $(this).data('nama');
        $('#nip_petugas').val(nip);
        $('#nm_petugas').val(nama);
        $('#nip_petugas_catatan').val(nip); // Sync ke Form B juga
        $('#modalPetugas').modal('hide');
    });

    // --- SUBMIT FORM ---
    function submitForm(formId) {
        $(formId).on('submit', function(e) {
            e.preventDefault();
            var btn = $(this).find('button[type="submit"]');
            var originalText = btn.html();
            btn.prop('disabled', true).html('Menyimpan...');

            $.ajax({
                url: 'action_mpp.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    btn.prop('disabled', false).html(originalText);
                    if(res.status == 'success') {
                        alert('Berhasil disimpan!');
                        location.reload();
                    } else {
                        alert('Gagal: ' + res.message);
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html(originalText);
                    console.log(xhr.responseText);
                    alert('Error Koneksi: ' + xhr.responseText);
                }
            });
        });
    }

    submitForm('#formSkrining');
    submitForm('#formEvaluasi');
    submitForm('#formCatatan');
});
</script>