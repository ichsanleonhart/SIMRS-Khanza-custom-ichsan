<?php include 'conf.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mapping Lab Satu Sehat</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .select2-container { z-index: 9999; }
        .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid mt-4 mb-5">
    <div class="card shadow border-0">
        <div class="card-header bg-gradient-primary text-white py-3">
            <h5 class="mb-0"><i class="fa-solid fa-vial me-2"></i> Mapping Laboratorium (LOINC & SNOMED)</h5>
        </div>
        <div class="card-body">
            
            <div class="row mb-4 g-2 align-items-end bg-white p-3 rounded shadow-sm border">
                <div class="col-md-8">
                    <label class="form-label fw-bold text-primary">Cari Nama Pemeriksaan (Server Side)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
                        <input type="text" id="keyword_pemeriksaan" class="form-control form-control-lg" placeholder="Ketik nama pemeriksaan lalu tekan Enter (Contoh: Albumin, Darah Lengkap)...">
                        <button class="btn btn-primary px-4" id="btnCariServer">
                            <i class="fa fa-filter me-1"></i> Tampilkan Data
                        </button>
                    </div>
                    <div class="form-text">Pencarian ini mengambil data langsung dari server. Kosongkan untuk melihat 100 data teratas.</div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="alert alert-info mb-0 py-2 small">
                        <i class="fa fa-info-circle"></i> Gunakan fitur ini untuk mencari item spesifik sebelum melakukan Mapping.
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="tableLab" class="table table-hover table-bordered w-100 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">ID</th>
                            <th width="35%">Detail Pemeriksaan (RS)</th>
                            <th width="40%">Mapping (Satu Sehat)</th>
                            <th width="10%" class="text-center">Status</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMapping" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Mapping Pemeriksaan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formMapping">
                    <input type="hidden" name="id_template" id="m_id_template">
                    
                    <div class="p-3 mb-3 bg-light rounded border">
                        <small class="text-muted">Pemeriksaan:</small><br>
                        <strong id="m_nama_pemeriksaan" class="fs-4 text-dark"></strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-primary">1. Kode Pemeriksaan (LOINC)</label>
                        <select class="form-select" id="sel_loinc" name="loinc_code" style="width:100%" required></select>
                        <input type="hidden" name="loinc_display" id="m_loinc_display">
                        <div class="form-text">Cari dalam Bahasa Inggris. System: <i>http://loinc.org</i></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">2. Kode Spesimen/Sampel (SNOMED-CT)</label>
                        <select class="form-select" id="sel_snomed" name="snomed_code" style="width:100%" required></select>
                        <input type="hidden" name="snomed_display" id="m_snomed_display">
                        <div class="form-text">Contoh: Serum, Plasma, Urine, Stool. System: <i>http://snomed.info/sct</i></div>
                    </div>

                    <hr>
                    
                    <div class="form-check form-switch p-3 bg-warning bg-opacity-10 rounded border border-warning">
                        <input class="form-check-input" type="checkbox" role="switch" id="apply_all" name="apply_all" value="true" checked>
                        <label class="form-check-label fw-bold text-dark" for="apply_all">
                            Terapkan mapping ini ke semua pemeriksaan bernama sama?
                        </label>
                        <div class="small text-muted mt-1">
                            Jika dicentang, pemeriksaan lain dengan nama "<b id="m_nama_copy"></b>" akan otomatis ter-update, meskipun berada di paket lab yang berbeda.
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary px-4" id="btnSimpan">Simpan Mapping</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // 1. Load Table dengan Parameter Tambahan
    var table = $('#tableLab').DataTable({
        "processing": true,
        "serverSide": false, // Tetap client-side processing untuk sorting/filter cepat
        "ajax": {
            "url": "ajax_lab.php?action=load_table",
            "data": function(d) {
                // Kirim parameter keyword ke server
                d.keyword = $('#keyword_pemeriksaan').val();
            }
        },
        "columns": [
            { data: 0, className: "text-center" },
            { data: 1 },
            { data: 2 },
            { data: 3, className: "text-center" },
            { data: 4, className: "text-center" }
        ],
        "language": {
            "search": "Filter Hasil (Client Side):",
            "zeroRecords": "Tidak ada data yang cocok",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
            "infoFiltered": "(difilter dari _MAX_ total data)"
        }
    });

    // Event Listener untuk Tombol Cari Server Side
    $('#btnCariServer').click(function() {
        table.ajax.reload();
    });

    // Event Enter pada input text
    $('#keyword_pemeriksaan').on('keyup', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            table.ajax.reload();
        }
    });

    // 2. Init Select2
    $('#sel_loinc').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modalMapping'),
        placeholder: 'Cari Kode LOINC...',
        minimumInputLength: 2,
        ajax: {
            url: 'ajax_lab.php?action=search_loinc',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { term: params.term }; },
            processResults: function (data) { return { results: data.results }; }
        }
    }).on('select2:select', function (e) {
        $('#m_loinc_display').val(e.params.data.display);
    });

    $('#sel_snomed').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modalMapping'),
        placeholder: 'Cari Spesimen...',
        minimumInputLength: 2,
        ajax: {
            url: 'ajax_lab.php?action=search_snomed',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { term: params.term }; },
            processResults: function (data) { return { results: data.results }; }
        }
    }).on('select2:select', function (e) {
        $('#m_snomed_display').val(e.params.data.display);
    });

    // 3. Open Modal
    $('#tableLab tbody').on('click', '.btn-map', function () {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        var loinc = $(this).data('loinc');
        var loinc_disp = $(this).data('loinc-display');
        var snomed = $(this).data('snomed');
        var snomed_disp = $(this).data('snomed-display');
        
        $('#m_id_template').val(id);
        $('#m_nama_pemeriksaan').text(nama);
        $('#m_nama_copy').text(nama);

        // Reset & Set LOINC
        $('#sel_loinc').val(null).trigger('change');
        if(loinc) {
            var option = new Option(loinc + ' - ' + loinc_disp, loinc, true, true);
            $('#sel_loinc').append(option).trigger('change');
            $('#m_loinc_display').val(loinc_disp);
        } else {
             $('#m_loinc_display').val('');
        }

        // Reset & Set SNOMED
        $('#sel_snomed').val(null).trigger('change');
        if(snomed) {
            var option = new Option(snomed + ' - ' + snomed_disp, snomed, true, true);
            $('#sel_snomed').append(option).trigger('change');
            $('#m_snomed_display').val(snomed_disp);
        } else {
             $('#m_snomed_display').val('');
        }

        $('#modalMapping').modal('show');
    });

    // 4. Save Data
    $('#btnSimpan').click(function() {
        var formData = $('#formMapping').serialize();
        
        // Efek loading
        var originalText = $(this).html();
        $(this).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...').prop('disabled', true);

        $.post('ajax_lab.php?action=save_mapping', formData, function(response) {
            $('#btnSimpan').html(originalText).prop('disabled', false);
            
            if (response.status == 'success') {
                $('#modalMapping').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    html: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                // Reload tabel tapi jaga posisi paging
                table.ajax.reload(null, false); 
            } else {
                Swal.fire('Gagal!', response.message, 'error');
            }
        }, 'json').fail(function() {
            $('#btnSimpan').html(originalText).prop('disabled', false);
            Swal.fire('Error!', 'Terjadi kesalahan koneksi server.', 'error');
        });
    });
});
</script>

</body>
</html>