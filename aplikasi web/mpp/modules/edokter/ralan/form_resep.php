<?php
// File: modules/edokter/ralan/form_resep.php
require_once '../../../config/config.php'; 
require_once '../../../config/database.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin') {
    die('<div class="alert alert-danger">Mode Super Admin: Pengisian Resep dikunci.</div>');
}

$no_rawat = $_GET['no_rawat'] ?? '';
if(empty($no_rawat)) exit('<div class="alert alert-danger">No Rawat tidak ditemukan.</div>');

$metode_racik = [];
try { $metode_racik = $pdo->query("SELECT kd_racik, nm_racik FROM metode_racik")->fetchAll(); } catch (Exception $e) {}
?>

<div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
    <h5 class="text-primary mb-0"><i class="fas fa-pills"></i> Input Resep Obat</h5>
    <span class="badge bg-info text-dark">Mode: Input Baru</span>
</div>

<form id="formResep" method="POST">
    <input type="hidden" name="no_rawat" id="no_rawat_resep" value="<?= htmlspecialchars($no_rawat) ?>">

    <ul class="nav nav-tabs mb-3 fw-bold" id="pills-tab" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="pill" href="#pills-umum">Obat Umum</a></li>
        <li class="nav-item"><a class="nav-link text-success" data-bs-toggle="pill" href="#pills-racikan">Obat Racikan (Master-Detail)</a></li>
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom p-3 bg-white" style="min-height: 50vh;">
        
        <div class="tab-pane fade show active" id="pills-umum">
            <div class="row g-2 mb-3 align-items-end bg-light p-2 rounded border">
                <div class="col-md-5">
                    <label class="small fw-bold">Cari Obat</label>
                    <select class="form-control select-obat" id="obat_umum" style="width: 100%;"></select>
                    <input type="hidden" id="stok_umum" value="0">
                    <input type="hidden" id="nama_umum" value="">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold">Jml (Keping)</label>
                    <input type="number" class="form-control form-control-sm" id="jml_umum" min="1" placeholder="0">
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold">Aturan Pakai</label>
                    <select class="form-control select-aturan" id="aturan_umum" style="width: 100%;"></select>
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-primary btn-sm w-100" id="btnAddUmum"><i class="fas fa-plus"></i></button>
                </div>
            </div>

            <table class="table table-sm table-bordered table-striped" id="tblUmum">
                <thead class="table-dark text-center">
                    <tr><th width="45%">Nama Obat</th><th width="15%">Jumlah</th><th width="30%">Aturan Pakai</th><th width="10%">Aksi</th></tr>
                </thead>
                <tbody>
                    <tr id="rowEmptyUmum"><td colspan="4" class="text-center text-muted">Belum ada obat ditambahkan.</td></tr>
                </tbody>
            </table>
        </div>

        <div class="tab-pane fade" id="pills-racikan">
            
            <div class="border p-2 mb-3 bg-light rounded shadow-sm border-success">
                <h6 class="text-success border-bottom pb-1"><i class="fas fa-box-open"></i> 1. Buat Wadah Racikan</h6>
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="small fw-bold">Nama Racikan</label>
                        <input type="text" class="form-control form-control-sm" id="nama_racikan" placeholder="Ex: Puyer Batuk">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Metode</label>
                        <select class="form-select form-select-sm" id="metode_racikan">
                            <?php foreach($metode_racik as $m): ?>
                                <option value="<?= $m['kd_racik'] ?>"><?= $m['nm_racik'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Jml Racik (Bungkus)</label>
                        <input type="number" class="form-control form-control-sm" id="jml_racikan" min="1" placeholder="0">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">Aturan Pakai</label>
                        <select class="form-control select-aturan" id="aturan_racikan" style="width: 100%;"></select>
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-success btn-sm w-100" id="btnAddRacikan" title="Buat Wadah"><i class="fas fa-check"></i> Buat</button>
                    </div>
                </div>
            </div>

            <div class="border p-2 mb-3 bg-warning bg-opacity-10 rounded shadow-sm border-warning" id="panelIsiObatRacik" style="display: none;">
                <h6 class="text-warning border-bottom pb-1 border-warning"><i class="fas fa-syringe"></i> 2. Masukkan Obat ke: <b id="lblRacikAktif" class="text-dark"></b></h6>
                <input type="hidden" id="aktif_no_racik">
                <input type="hidden" id="aktif_jml_racik">

                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="small fw-bold">Cari Obat</label>
                        <select class="form-control select-obat" id="obat_racik_det" style="width: 100%;"></select>
                        <input type="hidden" id="stok_racik_det" value="0">
                        <input type="hidden" id="nama_racik_det" value="">
                        <input type="hidden" id="kapasitas_racik_det" value="1">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Kandungan (mg)</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" id="kandungan_racik_det" placeholder="0">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Kapasitas Obat</label>
                        <input type="text" class="form-control form-control-sm bg-light" id="txt_kapasitas_det" readonly>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Terpakai (Keping)</label>
                        <input type="number" step="0.01" class="form-control form-control-sm bg-light fw-bold text-danger" id="jml_racik_det" readonly>
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-warning btn-sm w-100" id="btnAddObatDetail"><i class="fas fa-plus"></i> Isi</button>
                    </div>
                </div>
            </div>

            <table class="table table-sm table-bordered" id="tblRacikan">
                <thead class="table-success text-center">
                    <tr><th>Grup Racikan & Detail Obat</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <tr id="rowEmptyRacikan"><td colspan="2" class="text-center text-muted">Belum ada grup racikan dibuat.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-end mt-4 mb-5">
        <button type="button" class="btn btn-primary btn-lg px-5 shadow" id="btnSimpanResep"><i class="fas fa-save"></i> Simpan Resep</button>
    </div>
</form>

<script>
$(document).ready(function() {
    var ajaxObatUrl = '<?= $base_url ?>helpers/ajax/ajax_obat.php';
    var ajaxAturanUrl = '<?= $base_url ?>helpers/ajax/ajax_aturan.php';
    
    // [FIX BOOTSTRAP FOCUS TRAP]
    $.fn.modal.Constructor.prototype._enforceFocus = function() {};

    // 1. Inisialisasi Select2 Obat
    $('.select-obat').select2({
        placeholder: 'Ketik nama obat (Stok > 0)...',
        dropdownParent: $('#modalERM .modal-content'),
        ajax: {
            url: ajaxObatUrl, dataType: 'json', delay: 300,
            data: function (p) { return { q: p.term }; },
            processResults: function (d) { return { results: d.results }; }, cache: true
        }
    });

    // Event ketika Obat Umum dipilih
    $('#obat_umum').on('select2:select', function (e) {
        $('#stok_umum').val(e.params.data.stok);
        $('#nama_umum').val(e.params.data.nama_brng);
        $('#jml_umum').focus();
    });

    // Event ketika Obat Detail Racikan dipilih
    $('#obat_racik_det').on('select2:select', function (e) {
        $('#stok_racik_det').val(e.params.data.stok);
        $('#nama_racik_det').val(e.params.data.nama_brng);
        $('#kapasitas_racik_det').val(e.params.data.kapasitas); // Ambil kapasitas asli obat
        $('#txt_kapasitas_det').val(e.params.data.kapasitas + ' mg');
        $('#kandungan_racik_det').val(e.params.data.kapasitas); // Default kandungan = kapasitas
        hitungJmlDetailRacik(); // Langsung hitung
        $('#kandungan_racik_det').focus();
    });

    // 2. Inisialisasi Select2 Aturan Pakai (Tags: true agar bisa ketik bebas)
    $('.select-aturan').select2({
        placeholder: 'Pilih / Ketik Aturan...',
        tags: true, 
        dropdownParent: $('#modalERM .modal-content'),
        ajax: {
            url: ajaxAturanUrl, dataType: 'json', delay: 300,
            data: function (p) { return { q: p.term }; },
            processResults: function (d) { return { results: d.results }; }, cache: true
        }
    });

    // --- LOGIKA HITUNG OTOMATIS OBAT RACIKAN ---
    function hitungJmlDetailRacik() {
        var kandungan = parseFloat($('#kandungan_racik_det').val()) || 0;
        var kapasitas = parseFloat($('#kapasitas_racik_det').val()) || 1;
        var jml_racik = parseFloat($('#aktif_jml_racik').val()) || 1;
        
        // Rumus Khanza Asli
        var jml_keping = (kandungan / kapasitas) * jml_racik;
        $('#jml_racik_det').val(jml_keping.toFixed(2));
    }
    
    // Trigger hitung saat kandungan diketik
    $('#kandungan_racik_det').on('input', function() { hitungJmlDetailRacik(); });

    // --- LOGIKA ADD OBAT UMUM ---
    $('#btnAddUmum').click(function() {
        var kd_brng = $('#obat_umum').val(); var nm_brng = $('#nama_umum').val();
        var stok = parseFloat($('#stok_umum').val()); var jml = parseFloat($('#jml_umum').val());
        var aturan = $('#aturan_umum').val();

        if(!kd_brng || !jml || !aturan) { alert("Lengkapi data!"); return; }
        if(jml > stok) { alert("Stok kurang! Sisa: " + stok); return; }

        var row = `<tr>
            <td><input type="hidden" name="umum_kd_brng[]" value="${kd_brng}">
                <input type="hidden" name="umum_aturan[]" value="${aturan}">
                <input type="hidden" name="umum_jml[]" value="${jml}"> ${nm_brng}</td>
            <td class="text-center">${jml}</td><td>${aturan}</td>
            <td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-hapus"><i class="fas fa-trash"></i></button></td>
        </tr>`;
        $('#rowEmptyUmum').remove(); $('#tblUmum tbody').append(row);
        
        // Reset
        $('#obat_umum').val(null).trigger('change'); $('#jml_umum').val(''); $('#aturan_umum').val(null).trigger('change');
    });

    // --- LOGIKA ADD HEADER RACIKAN ---
    var no_racik_counter = 1;
    $('#btnAddRacikan').click(function() {
        var nama = $('#nama_racikan').val(); var kd_metode = $('#metode_racikan').val();
        var nm_metode = $('#metode_racikan option:selected').text();
        var jml = $('#jml_racikan').val(); var aturan = $('#aturan_racikan').val();

        if(!nama || !jml || !aturan) { alert("Lengkapi Header!"); return; }

        var rowId = "racikGroup_" + no_racik_counter;
        var row = `<tr id="${rowId}" class="table-light border-success">
            <td>
                <input type="hidden" name="racik_no[]" value="${no_racik_counter}">
                <input type="hidden" name="racik_nama[]" value="${nama}">
                <input type="hidden" name="racik_kd_metode[]" value="${kd_metode}">
                <input type="hidden" name="racik_jml[]" value="${jml}">
                <input type="hidden" name="racik_aturan[]" value="${aturan}">
                <b class="text-success"><i class="fas fa-box-open"></i> Racikan Ke-${no_racik_counter}: ${nama}</b> 
                <small class="text-muted ms-2">(${jml} bungkus | Metode: ${nm_metode} | Aturan: ${aturan})</small>
                <div class="mt-2 ps-4" id="detailList_${no_racik_counter}">
                    </div>
            </td>
            <td class="text-center align-middle" width="15%">
                <button type="button" class="btn btn-warning btn-sm btn-add-obat-ke-racik mb-1" data-no="${no_racik_counter}" data-jml="${jml}" data-nama="${nama}">+ Obat</button><br>
                <button type="button" class="btn btn-danger btn-sm btn-hapus"><i class="fas fa-trash"></i> Hapus Grup</button>
            </td>
        </tr>`;

        $('#rowEmptyRacikan').remove(); $('#tblRacikan tbody').append(row);
        no_racik_counter++;
        $('#nama_racikan').val(''); $('#jml_racikan').val(''); $('#aturan_racikan').val(null).trigger('change');
    });

    // --- BUKA PANEL ISI OBAT KE RACIKAN TERTENTU ---
    $(document).on('click', '.btn-add-obat-ke-racik', function() {
        var no = $(this).data('no'); var jml = $(this).data('jml'); var nama = $(this).data('nama');
        
        $('#aktif_no_racik').val(no);
        $('#aktif_jml_racik').val(jml);
        $('#lblRacikAktif').text("Racikan Ke-" + no + " (" + nama + " - " + jml + " bks)");
        
        $('#panelIsiObatRacik').slideDown();
        $('#obat_racik_det').select2('open'); // Langsung buka pencarian obat
    });

    // --- LOGIKA ADD DETAIL OBAT KE RACIKAN ---
    $('#btnAddObatDetail').click(function() {
        var target_no_racik = $('#aktif_no_racik').val();
        if(!target_no_racik) { alert("Pilih grup racikan dulu!"); return; }

        var kd_brng = $('#obat_racik_det').val(); var nm_brng = $('#nama_racik_det').val();
        var stok = parseFloat($('#stok_racik_det').val());
        var kand = parseFloat($('#kandungan_racik_det').val());
        var jml_keping = parseFloat($('#jml_racik_det').val());

        if(!kd_brng || !kand || !jml_keping) { alert("Lengkapi data obat racikan!"); return; }
        if(jml_keping > stok) { alert("Stok kurang! Dibutuhkan: " + jml_keping + ", Sisa: " + stok); return; }

        var detailHtml = `<div class="border-bottom py-1 text-primary small">
            <input type="hidden" name="racik_detail_no[]" value="${target_no_racik}">
            <input type="hidden" name="racik_detail_kd_brng[]" value="${kd_brng}">
            <input type="hidden" name="racik_detail_p1[]" value="1">
            <input type="hidden" name="racik_detail_p2[]" value="1">
            <input type="hidden" name="racik_detail_kandungan[]" value="${kand}">
            <input type="hidden" name="racik_detail_jml[]" value="${jml_keping}">
            <i class="fas fa-caret-right"></i> ${nm_brng} (Kandungan: ${kand}mg) <b>-> Memotong Stok: ${jml_keping} Keping</b>
            <button type="button" class="btn btn-link text-danger btn-sm p-0 ms-2 btn-hapus-detail" title="Hapus Obat">X</button>
        </div>`;

        $('#detailList_' + target_no_racik).append(detailHtml);

        // Reset panel obat
        $('#obat_racik_det').val(null).trigger('change');
        $('#kandungan_racik_det').val(''); $('#txt_kapasitas_det').val(''); $('#jml_racik_det').val('');
    });

    $(document).on('click', '.btn-hapus', function() { $(this).closest('tr').remove(); });
    $(document).on('click', '.btn-hapus-detail', function() { $(this).parent().remove(); });

    // --- PROSES SIMPAN AJAX ---
    $('#btnSimpanResep').click(function() {
        var formData = $('#formResep').serialize();
        var no_rawat = $('#no_rawat_resep').val();
        
        if ($('input[name="umum_kd_brng[]"]').length === 0 && $('input[name="racik_no[]"]').length === 0) {
            alert("Harap masukkan minimal 1 obat!"); return;
        }

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.post('proses_resep.php', formData, function(res) {
            if(res.status == 'success') {
                if(confirm("Resep berhasil disimpan!\n\nApakah Anda ingin memasukkan rincian resep ini ke kolom 'Plan / RTL' pada CPPT?")) {
                    $.post('proses_resep.php', { action: 'append_cppt', no_rawat: no_rawat, txt_resep: res.txt_resep }, function() {
                        alert("Selesai!"); $('#modalERM').modal('hide');
                    });
                } else {
                    $('#modalERM').modal('hide');
                }
            } else {
                alert("Gagal: " + res.message);
                $('#btnSimpanResep').prop('disabled', false).html('<i class="fas fa-save"></i> Simpan Resep');
            }
        }, 'json');
    });
});
</script>