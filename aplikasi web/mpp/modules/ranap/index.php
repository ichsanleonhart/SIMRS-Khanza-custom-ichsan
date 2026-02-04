<?php
// File: modules/ranap/index.php (FINAL UI FIX)
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../helpers/auth_helper.php';

cekLogin();
if (!cekAkses('mpp_skrining')) { die("Akses Ditolak"); }

require_once '../../layout/header.php';
require_once '../../layout/sidebar.php';
?>

<style>
    .table-responsive { font-size: 0.85rem; }
    .patient-info { line-height: 1.2; }
    .no-rawat-badge { background: #e3f2fd; color: #0d47a1; font-family: monospace; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; }
    .cost-text { font-family: 'Consolas', monospace; font-weight: bold; color: #2c3e50; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="h5 mb-0 text-gray-800"><i class="fas fa-procedures text-primary me-2"></i> Kunjungan Rawat Inap</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Ranap</li>
            </ol>
        </nav>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="small fw-bold">Dari Tanggal</label>
                    <input type="date" id="tgl_awal" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold">Sampai Tanggal</label>
                    <input type="date" id="tgl_akhir" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold">Status</label>
                    <select id="status_pulang" class="form-select form-select-sm">
                        <option value="Masih Dirawat">Masih Dirawat (Aktif)</option>
                        <option value="Sudah Pulang">Sudah Pulang (Audit)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="btn-filter" class="btn btn-primary btn-sm w-100"><i class="fas fa-search me-1"></i> Cari Data</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="table-ranap" class="table table-hover table-bordered mb-0 align-middle">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th width="12%">Waktu Masuk</th>
                            <th width="18%">No. Rawat / RM</th>
                            <th width="20%">Pasien</th>
                            <th width="15%">Kamar / Dokter</th>
                            <th width="15%">Diagnosa</th>
                            <th width="12%" class="text-end">Biaya (Est)</th>
                            <th width="8%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUniversal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title" id="modalTitle">Detail</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="modalContent"></div>
        </div>
    </div>
</div>

<?php require_once '../../layout/footer.php'; ?>

<script>
$(document).ready(function() {
    var table = $('#table-ranap').DataTable({
        "processing": true,
        "serverSide": true,
        "ordering": false,
        "ajax": {
            "url": "data_handler.php",
            "type": "POST",
            "data": function (d) {
                d.tgl_awal = $('#tgl_awal').val();
                d.tgl_akhir = $('#tgl_akhir').val();
                d.status_pulang = $('#status_pulang').val();
            }
        },
        "dom": "Brtip",
        "buttons": [ 'excel', 'print' ],
        "columns": [
            { "data": "waktu_masuk" },
            { 
                "data": "no_rawat",
                "render": function(data, type, row) {
                    return `<div class="d-flex flex-column">
                        <span class="no-rawat-badge mb-1">${data}</span>
                        <small class="text-muted fw-bold">RM: ${row.no_rkm_medis}</small>
                    </div>`;
                }
            },
            { 
                "data": "nm_pasien",
                "render": function(data, type, row) {
                    return `<div class="fw-bold text-dark">${data}</div>
                            <div class="small text-muted"><i class="fas fa-credit-card me-1"></i>${row.penjamin}</div>
                            <div class="small text-info mt-1"><i class="fas fa-clock me-1"></i>${row.hari_rawat}</div>`;
                }
            },
            { 
                "data": "kamar",
                "render": function(data, type, row) {
                    return `<div class="small fw-bold text-primary">${data}</div>
                            <div class="small text-muted text-truncate" style="max-width:180px;">${row.dokter}</div>`;
                }
            },
            { 
                "data": "diagnosa_awal",
                "render": function(data) { return `<div class="small text-truncate" style="max-width:200px;" title="${data}">${data || '-'}</div>`; }
            },
            { 
                "data": "total_biaya",
                "className": "text-end",
                "render": function(data) { return `<span class="cost-text">${data}</span>`; }
            },
            {
                "data": null,
                "className": "text-center",
                "render": function(data, type, row) {
                    return `<div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary btn-modal" data-type="billing" data-id="${row.no_rawat}"><i class="fas fa-file-invoice"></i></button>
                        <button class="btn btn-outline-success btn-modal" data-type="cppt" data-id="${row.no_rawat}"><i class="fas fa-notes-medical"></i></button>
                    </div>`;
                }
            }
        ]
    });

    $('#btn-filter').click(function() { table.ajax.reload(); });

    $('#table-ranap tbody').on('click', '.btn-modal', function() {
        var type = $(this).data('type');
        var no_rawat = $(this).data('id');
        var url = (type === 'billing') ? 'ajax/view_billing.php' : 'ajax/view_cppt.php';
        var title = (type === 'billing') ? 'Rincian Billing' : 'CPPT Pasien';
        
        $('#modalTitle').text(title);
        $('#modalContent').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
        $('#modalUniversal').modal('show');
        
        $.ajax({
            url: url,
            method: 'POST',
            data: { no_rawat: no_rawat },
            success: function(res) { $('#modalContent').html(res); },
            error: function() { $('#modalContent').html('<div class="p-3 text-danger">Gagal memuat data</div>'); }
        });
    });
});
</script>