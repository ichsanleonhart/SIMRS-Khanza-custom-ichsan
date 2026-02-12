<?php
// File: modules/ralan/index.php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../helpers/auth_helper.php';

cekLogin();
if (!cekAkses('mpp_skrining')) { die("Akses Ditolak"); }

require_once '../../layout/header.php';
require_once '../../layout/sidebar.php';

// Poliklinik List
$stmt_poli = $pdo->query("SELECT kd_poli, nm_poli FROM poliklinik WHERE status='1' ORDER BY nm_poli ASC");
$polikliniks = $stmt_poli->fetchAll();
?>

<style>
    .col-merged { white-space: nowrap; line-height: 1.3; }
    .col-merged .time { font-size: 0.8rem; color: #555; }
    .col-merged .rm { font-weight: bold; color: #0d6efd; font-size: 0.9rem; }
    .col-merged .rawat { font-size: 0.75rem; color: #777; font-family: 'Consolas', monospace; }
    
    .col-pasien { min-width: 200px; }
    .border-bpjs { border-left: 4px solid #198754 !important; padding-left: 8px; }
    .border-umum { border-left: 4px solid #0d6efd !important; padding-left: 8px; }
    .border-lain { border-left: 4px solid #ffc107 !important; padding-left: 8px; }
    
    .cost-text { font-family: 'Consolas', monospace; font-weight: bold; color: #2c3e50; }
    .btn-counter { position: relative; padding: 2px 8px; }
    .badge-counter { position: absolute; top: -5px; right: -5px; font-size: 0.6rem; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="h5 mb-0 text-gray-800"><i class="fas fa-wheelchair text-success me-2"></i> MPP - Rawat Jalan (IGD & Poli)</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Rawat Jalan</li>
            </ol>
        </nav>
    </div>

    <div class="card shadow-sm mb-3 border-start border-4 border-success">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">Tanggal Awal</label>
                    <input type="date" id="tgl_awal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">Tanggal Akhir</label>
                    <input type="date" id="tgl_akhir" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Poliklinik / Unit</label>
                    <select id="kd_poli" class="form-select form-select-sm">
                        <option value="">-- Semua Unit --</option>
                        <option value="IGDK" class="fw-bold">IGD (Instalasi Gawat Darurat)</option> 
                        <option disabled>----------------</option>
                        <?php foreach($polikliniks as $p): ?>
                            <option value="<?= $p['kd_poli'] ?>"><?= $p['nm_poli'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Status Periksa</label>
                    <select id="stts" class="form-select form-select-sm">
                        <option value="Belum">Belum Diperiksa</option>
                        <option value="Sudah">Sudah Diperiksa</option>
                        <option value="Dirawat">Dirawat (Hold)</option>
                        <option value="Berkas Diterima">Berkas Diterima</option>
                        <option value="">Semua Status</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="btn-filter" class="btn btn-success btn-sm w-100 shadow-sm">
                        <i class="fas fa-search me-1"></i> Tampilkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="table-ralan" class="table table-hover table-bordered mb-0 align-middle w-100">
                    <thead class="bg-light text-secondary text-uppercase small text-center">
                        <tr>
                            <th width="10%">Registrasi</th>
                            <th width="25%">Pasien & Penjamin</th>
                            <th width="15%">Unit / Dokter</th>
                            <th width="15%">Diagnosa</th>
                            <th width="5%">Lab</th>
                            <th width="5%">Rad</th>
                            <th width="15%">Estimasi Biaya</th>
                            <th width="5%">Aksi</th>
                            <th width="5%">Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUniversal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2"> 
                <h6 class="modal-title fw-bold" id="modalTitle">Detail Data</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="modalContent"></div>
        </div>
    </div>
</div>

<?php require_once '../../layout/footer.php'; ?>

<script>
$(document).ready(function() {
    
    var table = $('#table-ralan').DataTable({
        "processing": true, "serverSide": true, "ordering": false,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Show All"]],
        "ajax": {
            "url": "data_handler.php",
            "type": "POST",
            "data": function (d) {
                d.tgl_awal = $('#tgl_awal').val(); // Start Date
                d.tgl_akhir = $('#tgl_akhir').val(); // End Date
                d.kd_poli = $('#kd_poli').val();
                d.stts = $('#stts').val();
            }
        },
        "dom": "<'row p-3'<'col-sm-6'B><'col-sm-6'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row p-3'<'col-sm-5'l><'col-sm-7'p>>",
        "buttons": [ 'excel', 'print' ],
        "columns": [
            { 
                "data": null, "className": "col-merged",
                "render": function(data, type, row) {
                    return `<div class="time"><i class="far fa-calendar-alt"></i> ${row.waktu_reg}</div><div class="rm">${row.no_rkm_medis}</div><div class="rawat">${row.no_rawat}</div>`;
                }
            },
            { 
                "data": "nm_pasien", "className": "col-pasien",
                "render": function(data, type, row) {
                    let border = row.kategori_penjamin === 'BPJS' ? 'border-bpjs' : (row.kategori_penjamin === 'Umum' ? 'border-umum' : 'border-lain');
                    return `<div class="${border}"><div class="fw-bold text-dark text-wrap">${data}</div><div class="small text-muted"><i class="fas fa-credit-card me-1"></i>${row.penjamin}</div></div>`;
                }
            },
            { 
                "data": "nm_poli",
                "render": function(data, type, row) {
                    return `<div class="fw-bold text-success text-wrap">${data}</div><div class="small text-muted text-wrap" style="font-size:0.75rem">${row.nm_dokter}</div>`;
                }
            },
            { 
                "data": "diagnosa_awal", "render": function(data) { return data ? `<small class="text-wrap">${data}</small>` : '-'; }
            },
            { 
                "data": null, "className": "text-center",
                "render": function(data, type, row) {
                    if (row.count_lab > 0) return `<button class="btn btn-outline-info btn-sm btn-counter btn-modal" data-type="lab" data-id="${row.no_rawat}"><i class="fas fa-flask"></i><span class="badge bg-danger badge-counter">${row.count_lab}</span></button>`;
                    return '<small class="text-muted">-</small>';
                }
            },
            { 
                "data": null, "className": "text-center",
                "render": function(data, type, row) {
                    if (row.count_rad > 0) return `<button class="btn btn-outline-warning btn-sm btn-counter btn-modal" data-type="rad" data-id="${row.no_rawat}"><i class="fas fa-x-ray"></i><span class="badge bg-danger badge-counter">${row.count_rad}</span></button>`;
                    return '<small class="text-muted">-</small>';
                }
            },
            { "data": "total_biaya", "className": "text-end", "render": function(data) { return `<span class="cost-text">${data}</span>`; } },
            {
                "data": null, "className": "text-center",
                "render": function(data, type, row) {
                    return `<div class="btn-group btn-group-sm">
						<a href="../mpp/form_mpp.php?no_rawat=${row.no_rawat}" class="btn btn-warning text-dark" title="Form Skrining MPP"><i class="fas fa-clipboard-list"></i></a>
                        <button class="btn btn-primary btn-modal" data-type="billing" data-id="${row.no_rawat}" title="Rincian Billing"><i class="fas fa-file-invoice-dollar"></i></button>
                        <button class="btn btn-success btn-modal" data-type="cppt" data-id="${row.no_rawat}" title="CPPT"><i class="fas fa-notes-medical"></i></button>
                    </div>`;
                }
            },
            { 
                "data": "status_bayar", "className": "text-center",
                "render": function(data) {
                    return (data === 'Sudah Bayar') ? `<span class="badge bg-success rounded-pill">Lunas</span>` : `<span class="badge bg-secondary rounded-pill">Belum</span>`;
                }
            }
        ]
    });

    $('#btn-filter').click(function() { table.ajax.reload(); });

    $('#table-ralan tbody').on('click', '.btn-modal', function() {
        var type = $(this).data('type');
        var no_rawat = $(this).data('id');
        var url = '../ranap/ajax/view_' + type + '.php'; 
        var title = '';

        if(type === 'billing') title = 'Rincian Biaya Rawat Jalan';
        else if(type === 'cppt') title = 'CPPT / SOAP';
        else if(type === 'lab') title = 'Hasil Laboratorium';
        else if(type === 'rad') title = 'Hasil Radiologi';
        
        $('#modalTitle').html(title);
        $('#modalContent').html('<div class="text-center py-5"><div class="spinner-border text-success"></div></div>');
        $('#modalUniversal').modal('show');
        
        $.ajax({
            url: url, method: 'POST', data: { no_rawat: no_rawat },
            success: function(res) { $('#modalContent').html(res); },
            error: function() { $('#modalContent').html('<div class="p-3 text-danger">Gagal memuat data</div>'); }
        });
    });
});
</script>