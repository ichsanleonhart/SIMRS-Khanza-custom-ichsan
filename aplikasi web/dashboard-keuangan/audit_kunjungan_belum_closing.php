<?php
/*
 * File: audit_kunjungan_belum_closing.php (UPDATE V2)
 * Fungsi: Audit billing aktif Ralan & Ranap.
 * Fitur: Kolom Obat terpisah, Warning Anomali (Batal tapi ada biaya).
 */
$page_title = "Audit Billing Aktif & Anomali";
require_once('includes/header.php');
?>

<div class="container-fluid">

    <div class="alert alert-warning border-left-warning shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <div class="me-3"><i class="fas fa-search-dollar fa-2x"></i></div>
            <div>
                <h5 class="alert-heading mb-1">Audit Billing Belum Closing</h5>
                <p class="mb-0 small">
                    Data menampilkan semua pasien (Ralan & Ranap) dengan status bayar <strong>'Belum Bayar'</strong>.<br>
                    <span class="badge bg-warning text-dark">Kuning</span> menandakan pasien <strong>Status Batal</strong> namun sistem mendeteksi adanya biaya (Obat/Tindakan) yang masuk. Harap verifikasi.
                </p>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Tagihan Berjalan</h6>
            <button onclick="reloadTable()" class="btn btn-sm btn-primary"><i class="fas fa-sync me-2"></i>Refresh</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm text-sm" id="tableAudit" width="100%">
                    <thead class="table-dark">
                        <tr>
                            <th>Waktu Reg</th>
                            <th>No. Rawat</th>
                            <th>Pasien</th>
                            <th>Unit/Poli</th>
                            <th>Penjamin</th>
                            <th class="text-center">Jns. Rawat</th>
                            <th class="text-center">Status Plg</th>
                            <th class="text-end bg-success text-white">Biaya Obat</th>
                            <th class="text-end bg-primary text-white">Est. Total</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php ob_start(); ?>
<script>
    var tableAudit;

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    }

    $(document).ready(function() {
        tableAudit = $('#tableAudit').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "api/data_audit_kunjungan_ssp.php",
                "type": "GET",
                "error": function(xhr) { console.error(xhr); alert('Gagal memuat data.'); }
            },
            "order": [[ 0, "desc" ]],
            "pageLength": 10,
            "createdRow": function(row, data, dataIndex) {
                // LOGIKA WARNA KUNING (ANOMALI)
                if (data.is_anomaly === true) {
                    $(row).addClass('table-warning border-warning');
                    $('td', row).eq(6).append(' <i class="fas fa-exclamation-triangle text-danger" title="Anomali: Batal tapi ada biaya"></i>');
                }
            },
            "columns": [
                { "data": "waktu", render: function(d){ return d.split(' ')[0]; } },
                { "data": "no_rawat" },
                { "data": "nm_pasien", render: function(d,t,r){ return '<b>'+d+'</b><br><small>'+r.no_rkm_medis+'</small>'; } },
                { "data": "nm_poli" },
                { "data": "png_jawab" },
                { "data": "status_lanjut", className: "text-center", render: function(d){
                    return d==='Ranap' ? '<span class="badge bg-info">Ranap</span>' : '<span class="badge bg-secondary">Ralan</span>';
                }},
                { "data": "status_pelayanan", className: "text-center fw-bold" },
                
                // KOLOM BARU: BIAYA OBAT
                { "data": "biaya_obat", className: "text-end text-success fw-bold", render: function(d){ return formatRupiah(d); } },
                
                // KOLOM TOTAL ESTIMASI
                { "data": "biaya_total", className: "text-end text-primary fw-bold", render: function(d){ return formatRupiah(d); } },
                
                { "data": null, className: "text-center", render: function(d,t,r){
                     // Tombol aksi (bisa dikembangkan untuk buka detail rincian)
                     return `<button class="btn btn-xs btn-outline-primary" title="Lihat Rincian"><i class="fas fa-list"></i></button>`;
                }}
            ]
        });
    });

    function reloadTable() {
        tableAudit.ajax.reload();
    }
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>