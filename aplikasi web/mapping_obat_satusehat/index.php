<?php
include 'conf.php';

// PROSES SIMPAN (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $kode_brng = $_POST['kode_brng'];
        
        // Logika Cerdas: Form, Route, dan Unit
        // Kita ambil values langsung dari form yang sudah user pilih
        
        // 1. KFA
        $kfa_code = $_POST['kfa_code']; 
        $kfa_display = $_POST['kfa_display_hidden']; // Ambil dari hidden input jika select2
        if(empty($kfa_display)) $kfa_display = $_POST['kfa_display_manual']; // Fallback manual

        // 2. FORM (Bentuk)
        $form_raw = explode('|', $_POST['form_code']);
        $form_code = $form_raw[0]; 
        $form_display = $form_raw[1] ?? '';
        $form_system = "http://terminology.kemkes.go.id/CodeSystem/medication-form";

        // 3. ROUTE (Rute) - HARDCODED SYSTEM ATC
        $route_raw = explode('|', $_POST['route_code']);
        $route_code = $route_raw[0];
        $route_display = $route_raw[1] ?? '';
        $route_system = "http://www.whocc.no/atc";

        // 4. UNIT (Satuan) - AUTO DETECT SYSTEM
        $den_code = trim($_POST['denominator_code']);
        $den_system = "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm"; // Default TAB/CAP
        // Logic override ke UCUM jika satuan cair/berat
        if (in_array(strtolower($den_code), ['ml', 'l', 'mg', 'g', 'iu', '%', 'ug', 'mcg'])) {
            $den_system = "http://unitsofmeasure.org";
        }

        // UPSERT QUERY
        $sql = "INSERT INTO satu_sehat_mapping_obat 
                (kode_brng, obat_code, obat_system, obat_display, 
                 form_code, form_system, form_display, 
                 route_code, route_system, route_display, 
                 denominator_code, denominator_system)
                VALUES (?, ?, 'http://sys-ids.kemkes.go.id/kfa', ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                obat_code=?, obat_display=?, 
                form_code=?, form_display=?, 
                route_code=?, route_display=?, 
                denominator_code=?, denominator_system=?";
        
        $stmt = $pdo->prepare($sql);
        $params = [
            $kode_brng, $kfa_code, $kfa_display, 
            $form_code, $form_system, $form_display, 
            $route_code, $route_system, $route_display, 
            $den_code, $den_system,
            // Update params
            $kfa_code, $kfa_display, 
            $form_code, $form_display, 
            $route_code, $route_display, 
            $den_code, $den_system
        ];
        
        $stmt->execute($params);
        $msg_type = "success";
        $msg_content = "Mapping Berhasil Disimpan!";

    } catch (Exception $e) {
        $msg_type = "danger";
        $msg_content = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Smart Mapping Obat Satu Sehat</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .select2-container { z-index: 9999; } /* Fix Select2 di dalam Modal */
    </style>
</head>
<body class="bg-light">

<div class="container-fluid mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa-solid fa-pills"></i> Mapping Obat Satu Sehat</h5>
            <small>Database: <?= $db ?></small>
        </div>
        <div class="card-body">
            
            <?php if(isset($msg_type)): ?>
                <div class="alert alert-<?= $msg_type ?>"><?= $msg_content ?></div>
            <?php endif; ?>

            <div class="table-responsive">
                <table id="tabelObat" class="table table-striped table-hover table-bordered" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>Kode RS</th>
                            <th>Nama Obat (RS)</th>
                            <th>Mapping KFA (Kemenkes)</th>
                            <th>Rute / Satuan</th>
                            <th>Status</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMap" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Form Mapping Interoperabilitas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="kode_brng" id="m_kode_brng">
                    
                    <div class="alert alert-secondary d-flex align-items-center">
                        <i class="fa fa-info-circle fa-2x me-3"></i>
                        <div>
                            <div class="fw-bold" id="m_nama_brng_label">Nama Obat</div>
                            <small id="m_kode_brng_label">Kode RS</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-primary">1. Cari Kode KFA (Kamus Farmasi & Alkes)</label>
                        <div class="input-group">
                            <select class="form-select" id="select_kfa" name="kfa_code" style="width: 85%"></select>
                            <a href="https://kfa-browser.kemkes.go.id" target="_blank" class="btn btn-outline-secondary" title="Buka KFA Browser"><i class="fa fa-external-link-alt"></i></a>
                        </div>
                        <input type="hidden" name="kfa_display_hidden" id="kfa_display_hidden">
                        <input type="text" class="form-control mt-2" name="kfa_display_manual" id="kfa_display_manual" placeholder="Atau ketik Nama KFA manual jika pencarian tidak ditemukan...">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">2. Bentuk Sediaan (Form)</label>
                            <select class="form-select select2-static" name="form_code" id="select_form" required>
                                <option value="">-- Pilih Bentuk --</option>
                                <?php 
                                // Ambil data dari tabel ref_form
                                $stmt = $pdo->query("SELECT * FROM satu_sehat_ref_form ORDER BY display ASC");
                                while($f = $stmt->fetch()){
                                    echo "<option value='".$f['code']."|".$f['display']."'>".$f['display']." (".$f['code'].")</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-danger">3. Rute Pemberian (Wajib ATC)</label>
                            <select class="form-select select2-static" name="route_code" id="select_route" required>
                                <option value="">-- Pilih Rute --</option>
                                <?php 
                                // Ambil data dari tabel ref_route (ATC)
                                $stmt = $pdo->query("SELECT * FROM satu_sehat_ref_route");
                                while($r = $stmt->fetch()){
                                    echo "<option value='".$r['code']."|".$r['display']."'>".$r['display']." (ATC: ".$r['code'].")</option>";
                                }
                                ?>
                            </select>
                            <div class="form-text text-danger small">Sistem otomatis menggunakan http://www.whocc.no/atc</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">4. Satuan Dosis / Kemasan (Denominator)</label>
                        <div class="row g-2">
                            <div class="col-md-8">
                                <select class="form-select select2-tags" name="denominator_code" id="select_unit" required>
                                    <option value="TAB">TAB (Tablet)</option>
                                    <option value="CAP">CAP (Kapsul)</option>
                                    <option value="mL">mL (Mililiter)</option>
                                    <option value="mg">mg (Miligram)</option>
                                    <option value="g">g (Gram)</option>
                                    <option value="IU">IU (International Unit)</option>
                                    <option value="GEL">GEL</option>
                                    <option value="CRM">CRM (Cream)</option>
                                    <option value="SUPP">SUPP (Suppositoria)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <span class="badge bg-secondary w-100 py-2" id="unit_badge">System: Auto-Detect</span>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan Mapping</button>
                </div>
            </form>
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
    // 1. Init Datatable
    var table = $('#tabelObat').DataTable({
        "processing": true,
        "serverSide": false, // Ganti true jika data > 5000 baris dan implementasi server-side paging
        "ajax": "ajax.php?action=load_table",
        "pageLength": 10
    });

    // 2. Init Select2
    $('.select2-static').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modalMap')
    });

    $('.select2-tags').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modalMap'),
        tags: true // Izinkan user ketik manual jika tidak ada di list
    });

    // 3. Init Select2 KFA (AJAX Search)
    $('#select_kfa').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modalMap'),
        placeholder: 'Ketik Kode atau Nama KFA...',
        ajax: {
            url: 'ajax.php?action=search_kfa',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return { results: data.results };
            },
            cache: true
        }
    });

    // Event: Saat KFA dipilih, isi Nama Display otomatis
    $('#select_kfa').on('select2:select', function (e) {
        var data = e.params.data;
        $('#kfa_display_hidden').val(data.display_name);
        $('#kfa_display_manual').val(data.display_name); // Tampilkan juga di input manual
    });

    // 4. Buka Modal & Isi Data
    $('#tabelObat tbody').on('click', '.btn-map', function () {
        var data = $(this).data('json');
        
        $('#m_kode_brng').val(data.kode_brng);
        $('#m_nama_brng_label').text(data.nama_brng);
        $('#m_kode_brng_label').text(data.kode_brng);

        // Reset Select2
        $('#select_kfa').val(null).trigger('change');
        
        // Jika sudah ada data mapping sebelumnya, set value
        if(data.obat_code) {
            // Set KFA (Manual set option karena ajax)
            var option = new Option(data.obat_code + ' - ' + data.obat_display, data.obat_code, true, true);
            $('#select_kfa').append(option).trigger('change');
            $('#kfa_display_hidden').val(data.obat_display);
            $('#kfa_display_manual').val(data.obat_display);

            // Set Form, Route, Unit
            // Perlu logic string matching sederhana
            $('#select_form').val(data.form_code + '|' + data.form_display).trigger('change');
            
            // Cari value route yang pas
            let rVal = data.route_code + '|' + data.route_display;
            // Jika display null di DB, coba match code saja
            $("#select_route option").each(function() {
                if($(this).val().startsWith(data.route_code)) {
                    $(this).prop("selected", true).trigger('change');
                }
            });

            $('#select_unit').val(data.denominator_code).trigger('change');
        }
        
        $('#modalMap').modal('show');
    });

    // 5. Visual Logic: Unit System Auto-Detect
    $('#select_unit').on('change', function() {
        let val = $(this).val();
        if(!val) return;
        val = val.toLowerCase();
        
        let ucumUnits = ['ml', 'l', 'mg', 'g', 'iu', '%', 'ug', 'mcg'];
        if (ucumUnits.includes(val)) {
            $('#unit_badge').removeClass('bg-secondary bg-primary').addClass('bg-success').text('System: UCUM (unitsofmeasure)');
        } else {
            $('#unit_badge').removeClass('bg-secondary bg-success').addClass('bg-primary').text('System: DrugForm (HL7 v3)');
        }
    });
});
</script>

</body>
</html>