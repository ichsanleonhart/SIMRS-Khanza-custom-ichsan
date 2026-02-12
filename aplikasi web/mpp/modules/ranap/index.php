<?php
// File: modules/ranap/index.php (BASIS CODE LAMA + FILTER & TOMBOL BARU)
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
    
    /* Kolom Gabungan (Waktu, RM, Rawat) */
    .col-merged { white-space: nowrap; line-height: 1.3; }
    .col-merged .time { font-size: 0.8rem; color: #555; }
    .col-merged .rm { font-weight: bold; color: #0d6efd; font-size: 0.9rem; }
    .col-merged .rawat { font-size: 0.75rem; color: #777; font-family: 'Consolas', monospace; }

    /* Kolom Pasien (Lebar) */
    .col-pasien { min-width: 200px; }
    .border-bpjs { border-left: 4px solid #198754 !important; padding-left: 8px; }
    .border-umum { border-left: 4px solid #0d6efd !important; padding-left: 8px; }
    .border-lain { border-left: 4px solid #ffc107 !important; padding-left: 8px; }

    /* Kolom Diagnosa (Sempit & Wrap) */
    .col-diagnosa { 
        width: 150px; 
        max-width: 150px; 
        white-space: normal !important; 
        word-wrap: break-word;
        font-size: 0.8rem;
    }

    /* Progress Bar Plafon */
    .progress-wrapper { width: 100%; margin-top: 4px; background: #e9ecef; border-radius: 4px; height: 6px; overflow: hidden; }
    .progress-bar { height: 100%; border-radius: 4px; transition: width 0.5s ease; }
    
    /* Warna Progress */
    .bg-safe { background-color: #198754; } /* Hijau < 75% */
    .bg-warn { background-color: #ffc107; } /* Kuning 75-90% */
    .bg-danger-flash { 
        background-color: #dc3545; 
        animation: pulse 2s infinite; 
    }
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.6; }
        100% { opacity: 1; }
    }

    .btn-counter { position: relative; padding: 2px 8px; }
    .badge-counter { position: absolute; top: -5px; right: -5px; font-size: 0.6rem; }
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

    <div class="card shadow-sm mb-3 border-start border-4 border-primary">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">Filter Berdasarkan</label>
                    <select id="filter_by" class="form-select form-select-sm">
                        <option value="masuk">Tanggal Masuk</option>
                        <option value="pulang">Tanggal Pulang</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">Dari Tanggal</label>
                    <input type="date" id="tgl_awal" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">Sampai Tanggal</label>
                    <input type="date" id="tgl_akhir" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Status Pasien</label>
                    <select id="status_pulang" class="form-select form-select-sm">
                        <option value="Masih Dirawat">🏥 Masih Dirawat (Aktif)</option>
                        <option value="Sudah Pulang">🏠 Sudah Pulang (Audit)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="btn-filter" class="btn btn-primary btn-sm w-100 shadow-sm">
                        <i class="fas fa-search me-1"></i> Tampilkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="table-ranap" class="table table-hover table-bordered mb-0 align-middle w-100">
                    <thead class="bg-light text-secondary text-uppercase small text-center">
                        <tr>
                            <th width="10%">Data Masuk</th>
                            <th width="20%">Pasien & Penjamin</th>
                            <th width="15%">Diagnosa</th> <th width="15%">Kamar / Dokter</th>
                            <th width="5%">Lab</th>
                            <th width="5%">Rad</th>
                            <th width="20%">Estimasi & Plafon</th>
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
            <div class="modal-header bg-primary text-white py-2">
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
    
    var table = $('#table-ranap').DataTable({
        "processing": true,
        "serverSide": true,
        "ordering": false,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Show All"]],
        "ajax": {
            "url": "data_handler.php",
            "type": "POST",
            "data": function (d) {
                d.tgl_awal = $('#tgl_awal').val();
                d.tgl_akhir = $('#tgl_akhir').val();
                d.status_pulang = $('#status_pulang').val();
                d.filter_by = $('#filter_by').val(); // Kirim Parameter Filter Baru
            }
        },
        "dom": "<'row p-3'<'col-sm-6'B><'col-sm-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row p-3'<'col-sm-5'l><'col-sm-7'p>>",
        "buttons": [ 'excel', 'print' ],
        "columns": [
            // 1. GABUNGAN WAKTU, RM, NO RAWAT
            { 
                "data": null,
                "className": "col-merged",
                "render": function(data, type, row) {
                    return `
                        <div class="time"><i class="far fa-clock"></i> ${row.waktu_short}</div>
                        <div class="rm">${row.no_rkm_medis}</div>
                        <div class="rawat">${row.no_rawat}</div>
                    `;
                }
            },
            
            // 2. PASIEN (Lebar)
            { 
                "data": "nm_pasien",
                "className": "col-pasien",
                "render": function(data, type, row) {
                    let border = 'border-lain';
                    if (row.kategori_penjamin === 'BPJS') border = 'border-bpjs';
                    else if (row.kategori_penjamin === 'Umum') border = 'border-umum';

                    return `<div class="${border}">
                                <div class="fw-bold text-dark text-wrap">${data}</div>
                                <div class="small text-muted"><i class="fas fa-credit-card me-1"></i>${row.penjamin}</div>
                                <span class="badge bg-light text-dark border mt-1"><i class="fas fa-clock me-1"></i>${row.hari_rawat}</span>
                            </div>`;
                }
            },
            
            // 3. DIAGNOSA (Sempit, Wrap)
            { 
                "data": "diagnosa_awal",
                "className": "col-diagnosa",
                "render": function(data, type, row) { 
                    let h = '';
                    if(data) h += `<b>Awal:</b> ${data}<br>`;
                    if(row.diagnosa_akhir) h += `<span class="text-success"><b>Akhir:</b> ${row.diagnosa_akhir}</span>`;
                    return h || '-'; 
                }
            },

            // 4. KAMAR
            { 
                "data": "kamar",
                "render": function(data, type, row) {
                    return `<div class="small fw-bold text-primary text-wrap">${data}</div>
                            <div class="small text-muted text-wrap" style="font-size:0.75rem">${row.dokter}</div>`;
                }
            },

            // 5. LAB
            { 
                "data": null, "className": "text-center",
                "render": function(data, type, row) {
                    if (row.count_lab > 0) return `<button class="btn btn-outline-info btn-sm btn-counter btn-modal" data-type="lab" data-id="${row.no_rawat}"><i class="fas fa-flask"></i><span class="badge bg-danger badge-counter">${row.count_lab}</span></button>`;
                    return '<small class="text-muted">-</small>';
                }
            },

            // 6. RAD
            { 
                "data": null, "className": "text-center",
                "render": function(data, type, row) {
                    if (row.count_rad > 0) return `<button class="btn btn-outline-warning btn-sm btn-counter btn-modal" data-type="rad" data-id="${row.no_rawat}"><i class="fas fa-x-ray"></i><span class="badge bg-danger badge-counter">${row.count_rad}</span></button>`;
                    return '<small class="text-muted">-</small>';
                }
            },

            // 7. ESTIMASI & PLAFON (Visual Bar)
            { 
                "data": null,
                "className": "text-end",
                "render": function(data, type, row) {
                    let html = `<div class="fw-bold text-dark">${row.total_biaya}</div>`;
                    
                    if(row.plafon_raw > 0) {
                        let percent = row.persen_pemakaian;
                        let barColor = 'bg-safe';
                        let overText = '';

                        if(percent > 100) { 
                            barColor = 'bg-danger-flash'; 
                            percent = 100; // Mentok tampilan
                            overText = '<div class="text-danger fw-bold small mt-1">OVER LIMIT!</div>';
                        } else if(percent > 90) {
                            barColor = 'bg-danger'; 
                        } else if(percent > 75) {
                            barColor = 'bg-warn';
                        }

                        html += `
                            <div class="small text-muted mt-1">Plafon: ${row.plafon}</div>
                            <div class="progress-wrapper" title="Terpakai ${row.persen_pemakaian}%">
                                <div class="progress-bar ${barColor}" style="width: ${percent}%"></div>
                            </div>
                            ${overText}
                        `;
                    }
                    return html;
                }
            },

            // 8. AKSI (PENAMBAHAN TOMBOL MPP)
            {
                "data": null, "className": "text-center",
                "render": function(data, type, row) {
                    return `<div class="btn-group btn-group-sm">
                        <a href="../mpp/form_mpp.php?no_rawat=${row.no_rawat}" class="btn btn-warning text-dark" title="Skrining MPP"><i class="fas fa-clipboard-list"></i></a>
                        
                        <button class="btn btn-primary btn-modal" data-type="billing" data-id="${row.no_rawat}"><i class="fas fa-file-invoice-dollar"></i></button>
                        <button class="btn btn-success btn-modal" data-type="cppt" data-id="${row.no_rawat}"><i class="fas fa-notes-medical"></i></button>
                    </div>`;
                }
            },

            // 9. STATUS
            { 
                "data": "status_bayar", "className": "text-center",
                "render": function(data) {
                    if (data === 'Sudah Bayar') return `<span class="badge bg-success rounded-pill">Close</span>`;
                    return `<span class="badge bg-light text-dark border rounded-pill">Aktif</span>`;
                }
            }
        ]
    });

    $('#btn-filter').click(function() { table.ajax.reload(); });

    $('#table-ranap tbody').on('click', '.btn-modal', function() {
        var type = $(this).data('type');
        var no_rawat = $(this).data('id');
        var url = '', title = '';

        if(type === 'billing') { url = 'ajax/view_billing.php'; title = 'Rincian Biaya'; }
        else if(type === 'cppt') { url = 'ajax/view_cppt.php'; title = 'CPPT'; }
        else if(type === 'lab') { url = 'ajax/view_lab.php'; title = 'Hasil Lab'; }
        else if(type === 'rad') { url = 'ajax/view_rad.php'; title = 'Hasil Radiologi'; }
        
        $('#modalTitle').html(title);
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