<?php
/*
 * File: kunjungan_ralan.php
 * Deskripsi: Monitoring Billing Rawat Jalan & Deteksi Anomali Batal
 */
$page_title = "Billing Rawat Jalan & Anomali";
require_once('includes/header.php');
?>

<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<div class="container-fluid">
    <div class="alert alert-warning shadow-sm mb-4 border-left-warning">
        <div class="d-flex align-items-center">
            <div class="me-3"><i class="fas fa-exclamation-triangle fa-2x text-warning"></i></div>
            <div>
                <h5 class="alert-heading mb-1">Monitoring Rawat Jalan</h5>
                <p class="mb-0">
                    Data menampilkan kunjungan <strong>Hari Ini</strong>. 
                    Baris berwarna <strong class="text-warning bg-dark px-1 rounded">Kuning</strong> menandakan Pasien <b>BATAL</b> namun masih memiliki tagihan (Anomali).
                </p>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Pasien Rawat Jalan Aktif</h6>
            <button onclick="reloadTable()" class="btn btn-sm btn-primary"><i class="fas fa-sync-alt me-2"></i>Refresh Data</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="tableRalan" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th width="12%">Waktu</th>
                            <th width="20%">No. Rawat / Pasien</th>
                            <th width="20%">Dokter / Poli</th>
                            <th width="15%">Penjamin</th>
                            <th class="text-end">Biaya Obat</th>
                            <th class="text-end bg-success text-white">Est. Total</th>
                            <th class="text-center">Status</th>
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
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-file-invoice me-2"></i>Rincian Billing Rawat Jalan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between bg-light p-2 mb-2 rounded border">
                    <div><strong>Pasien:</strong> <span id="lbl-pasien">-</span></div>
                    <div><strong>No. Rawat:</strong> <span id="lbl-norawat">-</span></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover" style="font-size: 0.85rem;">
                        <thead class="table-dark text-center">
                            <tr>
                                <th width="20%">Kategori</th>
                                <th width="30%">Item / Tindakan</th>
                                <th width="15%">Biaya</th>
                                <th width="5%">Jml</th>
                                <th width="15%">Tambahan</th>
                                <th width="15%">Total</th>
                            </tr>
                        </thead>
                        <tbody id="bodyDetailBilling"></tbody>
                        <tfoot class="table-light fw-bold fs-5">
                            <tr>
                                <td colspan="5" class="text-end">TOTAL TAGIHAN:</td>
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
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
    var tableRalan;

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    }

    $(document).ready(function() {
        tableRalan = $('#tableRalan').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "api/data_kunjungan_ralan.php",
                "type": "GET"
            },
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
            "dom": 'Bfrtip',
            "buttons": [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export Excel',
                    className: 'btn btn-success btn-sm mb-3', // Hijau Kecil
                    title: 'Laporan Billing Rawat Jalan',
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] }
                },
                {
                    extend: 'pageLength',
                    className: 'btn btn-secondary btn-sm mb-3' // Abu Kecil (SEJAJAR)
                }
            ],
            "order": [],
            "createdRow": function(row, data, dataIndex) {
                // WARNAI BARIS KUNING JIKA STATUS BATAL (ANOMALI)
                if (data.is_anomali === true) {
                    $(row).addClass('table-warning');
                }
            },
            "columns": [
                { "data": "waktu" },
                { 
                    "data": null,
                    "render": function(data) {
                        return `<b>${data.no_rawat}</b><br>${data.pasien} <br><small class="text-muted">${data.rm}</small>`;
                    }
                },
                { 
                    "data": null,
                    "render": function(data) {
                        return `<b>${data.poli}</b><br><small>${data.dokter}</small>`;
                    }
                },
                { 
                    "data": null,
                    "render": function(data) {
                        let penjamin = data.penjamin.toLowerCase();
                        let badgeClass = 'bg-secondary'; 
                        let badgeStyle = '';

                        if (penjamin.includes('bpjs')) {
                            badgeClass = 'bg-success';
                        } else if (penjamin.includes('umum')) {
                            badgeClass = 'bg-primary';
                        } else if (penjamin.includes('asuransi') || penjamin.includes('inhealth')) {
                            badgeClass = ''; 
                            badgeStyle = 'background-color: #e83e8c; color: white;'; // Pink Custom
                        }
                        return `<span class="badge ${badgeClass}" style="${badgeStyle}">${data.penjamin}</span>`;
                    }
                },
                { "data": "biaya_obat", "className": "text-end" },
                { "data": "estimasi", "className": "text-end fw-bold text-success" },
                { 
                    "data": "status",
                    "className": "text-center",
                    "render": function(data, type, row) {
                        if(data === 'Batal') return `<span class="badge bg-danger">BATAL</span>`;
                        if(data === 'Sudah') return `<span class="badge bg-success">Sudah</span>`;
                        return `<span class="badge bg-secondary">${data}</span>`;
                    }
                },
                { 
                    "data": null, "className": "text-center", 
                    "render": function(data, type, row) {
                        return `<button class="btn btn-sm btn-info text-white" 
                                onclick="showDetailBilling('${row.no_rawat}', '${row.pasien.replace(/'/g, "\\'")}')" 
                                title="Rincian">
                                <i class="fas fa-eye"></i>
                                </button>`;
                    }
                }
            ]
        });
    });

    function reloadTable() { tableRalan.ajax.reload(); }

    function showDetailBilling(noRawat, namaPasien) {
        $('#lbl-pasien').text(namaPasien);
        $('#lbl-norawat').text(noRawat);
        $('#bodyDetailBilling').html('<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary"></div><br>Memuat rincian...</td></tr>');
        $('#lbl-total').text('...');
        $('#modalDetailBilling').modal('show');

        $.ajax({
            url: 'api/data_rincian_billing.php', // Menggunakan API detail yang sudah diupdate
            type: 'GET',
            data: { no_rawat: noRawat },
            dataType: 'json',
            success: function(res) {
                var html = '';
                if (res.data && res.data.length > 0) {
                    res.data.forEach(function(item) {
                        if (item.is_header) {
                            html += `<tr class="table-secondary fw-bold"><td colspan="6">${item.keterangan} ${item.tagihan}</td></tr>`;
                        } else {
                            var style = (item.total < 0) ? 'text-danger fw-bold' : '';
                            html += `<tr>
                                        <td>${item.keterangan}</td>
                                        <td>${item.tagihan}</td>
                                        <td class="text-end">${formatRupiah(item.biaya)}</td>
                                        <td class="text-center">${item.jumlah}</td>
                                        <td class="text-end">${formatRupiah(item.tambahan)}</td>
                                        <td class="text-end fw-bold ${style}">${formatRupiah(item.total)}</td>
                                     </tr>`;
                        }
                    });
                } else {
                    html = '<tr><td colspan="6" class="text-center">Tidak ada data tagihan.</td></tr>';
                }
                $('#bodyDetailBilling').html(html);
                $('#lbl-total').text(res.total_rupiah);
            }
        });
    }
</script>
<?php $page_js = ob_get_clean(); ?>
<?php require_once('includes/footer.php'); ?>