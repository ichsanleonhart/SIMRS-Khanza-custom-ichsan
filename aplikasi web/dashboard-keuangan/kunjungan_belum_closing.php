<?php
/*
 * File: kunjungan_belum_closing.php (UPDATE V3 - DETAIL BILLING)
 * - Added: Tombol Detail Billing (Icon Mata/Search).
 * - Added: Modal Rincian Biaya per Komponen.
 */
$page_title = "Kunjungan Aktif (Belum Closing)";
require_once('includes/header.php');
?>

<div class="container-fluid">

    <div class="alert alert-primary shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <div class="me-3"><i class="fas fa-info-circle fa-2x"></i></div>
            <div>
                <h5 class="alert-heading mb-1">Estimasi Biaya Realtime</h5>
                <p class="mb-0">Menampilkan pasien aktif (belum lunas). Klik tombol <strong>Detail</strong> untuk melihat rincian komponen biaya (Obat, Tindakan, Kamar, dll).</p>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Pasien Rawat Inap Aktif</h6>
            <button onclick="reloadTable()" class="btn btn-sm btn-primary"><i class="fas fa-sync-alt me-2"></i>Refresh Data</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="tableKunjungan" width="100%" cellspacing="0">
                    <thead class="table-dark">
                        <tr>
                            <th>Tgl Masuk</th>
                            <th>No. Rawat</th>
                            <th>No. RM</th>
                            <th>Nama Pasien</th>
                            <th>Bangsal / Kelas</th>
                            <th>Penjamin</th>
                            <th class="text-center">Lama</th>
                            <th class="text-end bg-warning text-dark">Est. Biaya (Rp)</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="modalDetailBilling" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-file-invoice-dollar me-2"></i>Rincian Billing Sementara</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 p-2 bg-light border rounded">
                    <strong>Pasien:</strong> <span id="lbl-pasien">-</span> <br>
                    <strong>No. Rawat:</strong> <span id="lbl-norawat">-</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover border">
                        <thead class="table-light">
                            <tr>
                                <th>Kategori</th>
                                <th>Nama Item / Tindakan</th>
                                <th class="text-end">Biaya (Rp)</th>
                            </tr>
                        </thead>
                        <tbody id="bodyDetailBilling">
                            <tr><td colspan="3" class="text-center">Memuat data...</td></tr>
                        </tbody>
                        <tfoot class="fw-bold bg-light">
                            <tr>
                                <td colspan="2" class="text-end">TOTAL ESTIMASI:</td>
                                <td class="text-end text-primary" id="lbl-total">0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
    var tableKunjungan;

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    }

    $(document).ready(function() {
        tableKunjungan = $('#tableKunjungan').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "api/data_kunjungan_aktif_ssp.php",
                "type": "GET",
                "error": function(xhr, error, thrown) { console.error("Error DataTables:", error); }
            },
            "pageLength": 10,
            "order": [[ 0, "desc" ]],
            "columns": [
                { "data": "waktu" },
                { "data": "no_rawat" },
                { "data": "rm" },
                { "data": "pasien", "className": "fw-bold" },
                { "data": "kamar" },
                { "data": "penjamin" },
                { "data": "lama", "className": "text-center" },
                { "data": "estimasi", "className": "text-end fw-bold text-danger fs-6" },
                { 
                    "data": null, "className": "text-center", 
                    "render": function(data, type, row) {
                        return `<button class="btn btn-sm btn-info text-white" onclick="showDetailBilling('${row.no_rawat}', '${row.pasien.replace(/'/g, "\\'")}')" title="Lihat Rincian"><i class="fas fa-search"></i> Detail</button>`;
                    }
                }
            ]
        });
    });

    function reloadTable() {
        tableKunjungan.ajax.reload();
    }

    function showDetailBilling(noRawat, namaPasien) {
        $('#lbl-pasien').text(namaPasien);
        $('#lbl-norawat').text(noRawat);
        $('#bodyDetailBilling').html('<tr><td colspan="3" class="text-center"><div class="spinner-border text-primary spinner-border-sm"></div> Memuat rincian...</td></tr>');
        $('#lbl-total').text('...');
        
        $('#modalDetailBilling').modal('show');

        $.ajax({
            url: 'api/data_rincian_billing.php',
            type: 'GET',
            data: { no_rawat: noRawat },
            dataType: 'json',
            success: function(res) {
                var html = '';
                if (res.data && res.data.length > 0) {
                    res.data.forEach(function(item) {
                        var colorClass = (item.biaya < 0) ? 'text-danger' : 'text-dark';
                        html += `<tr>
                                    <td><span class="badge bg-secondary">${item.kategori}</span></td>
                                    <td>${item.nama}</td>
                                    <td class="text-end ${colorClass}">${formatRupiah(item.biaya)}</td>
                                 </tr>`;
                    });
                } else {
                    html = '<tr><td colspan="3" class="text-center">Tidak ada data biaya ditemukan.</td></tr>';
                }
                $('#bodyDetailBilling').html(html);
                $('#lbl-total').text(formatRupiah(res.total));
            },
            error: function() {
                $('#bodyDetailBilling').html('<tr><td colspan="3" class="text-center text-danger">Gagal memuat data.</td></tr>');
            }
        });
    }
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>