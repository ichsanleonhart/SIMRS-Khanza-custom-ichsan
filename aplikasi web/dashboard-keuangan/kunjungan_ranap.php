<?php
/*
 * File: kunjungan_belum_closing.php (FIX V21 - BADGE COLORS & SHOW ALL)
 */
$page_title = "Monitoring Plafon & Billing Aktif";
require_once('includes/header.php');
?>

<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<div class="container-fluid">
    <div class="alert alert-info shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <div class="me-3"><i class="fas fa-chart-line fa-2x"></i></div>
            <div>
                <h5 class="alert-heading mb-1">Monitoring Plafon & Estimasi Biaya</h5>
                <p class="mb-0">
                    Baris berwarna <strong class="text-danger">Merah Muda</strong> menandakan tagihan melebihi Plafon.
                    Klik tombol <span class="badge bg-primary"><i class="fas fa-list-ul"></i> Rincian</span> untuk melihat detail hitungan.
                </p>
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
                <table class="table table-bordered table-hover align-middle" id="tableKunjungan" width="100%" cellspacing="0">
                    <thead class="table-dark">
                        <tr>
                            <th width="10%">Tgl Masuk</th>
                            <th width="15%">No. Rawat / Pasien</th>
                            <th width="15%">DPJP / Dokter</th>
                            <th width="15%">Kamar / Penjamin</th>
                            <th width="10%" class="text-end bg-secondary">Plafon</th>
                            <th width="10%" class="text-end bg-warning text-dark">Est. Biaya</th>
                            <th width="10%" class="text-end">Selisih</th>
                            <th width="5%" class="text-center">Aksi</th>
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
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-file-invoice-dollar me-2"></i>Rincian Billing</h5>
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
                                <th width="20%">Kategori / Keterangan</th>
                                <th width="25%">Tagihan / Tindakan</th>
                                <th width="12%">Biaya</th>
                                <th width="5%">Jml</th>
                                <th width="12%">Tambahan</th>
                                <th width="15%">Total Biaya</th>
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
    var tableKunjungan;

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    }

    $(document).ready(function() {
        tableKunjungan = $('#tableKunjungan').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "api/data_kunjungan_ranap.php",
                "type": "GET"
            },
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
            "dom": 'Bfrtip',
            "buttons": [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export Excel',
                    className: 'btn btn-success btn-sm mb-3',
                    title: 'Laporan Estimasi Billing Rawat Inap',
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] }
                },
                {
                    extend: 'pageLength',
                    className: 'btn btn-secondary btn-sm mb-3' // Samakan tingginya dengan btn-sm
                }
            ],
            "order": [],
            "createdRow": function(row, data, dataIndex) {
                if (data.is_over === true) {
                    $(row).addClass('table-danger');
                }
            },
            "columns": [
                { "data": "waktu" },
                { 
                    "data": null,
                    "render": function(data) {
                        return `<b>${data.no_rawat}</b><br>${data.pasien} <br><small class="text-muted">RM: ${data.rm}</small>`;
                    }
                },
                { 
                    "data": "dpjp",
                    "render": function(data, type, row) {
                        let html = `<b>${data}</b>`;
                        if (row.is_dpjp_fallback) {
                            html += `<br><small class="badge bg-warning text-dark" style="font-size: 0.7em;">DPJP belum diset</small>`;
                        }
                        return html;
                    }
                },
                // KOLOM PENJAMIN DENGAN WARNA
                { 
                    "data": null,
                    "render": function(data) {
                        let penjamin = data.penjamin.toLowerCase();
                        let badgeClass = 'bg-secondary'; // Default Abu-abu
                        let badgeStyle = ''; // Default kosong

                        if (penjamin.includes('bpjs')) {
                            badgeClass = 'bg-success'; // Hijau
                        } else if (penjamin.includes('umum')) {
                            badgeClass = 'bg-primary'; // Biru
                        } else if (penjamin.includes('asuransi') || penjamin.includes('inhealth')) {
                            badgeClass = ''; 
                            badgeStyle = 'background-color: #e83e8c; color: white;'; // Pink Manual
                        }

                        return `${data.kamar}<br><span class="badge ${badgeClass}" style="${badgeStyle} border: 1px solid #ddd;">${data.penjamin}</span>`;
                    }
                },
                { "data": "plafon", "className": "text-end fw-bold", "defaultContent": "-" },
                { "data": "estimasi", "className": "text-end fw-bold text-primary" },
                { 
                    "data": "selisih", 
                    "className": "text-end fw-bold",
                    "defaultContent": "-",
                    "render": function(data, type, row) {
                        if (!data || data === '-') return '-';
                        return (row.is_over) ? `<span class="text-danger">(${data})</span>` : `<span class="text-success">+${data}</span>`;
                    }
                },
                { 
                    "data": null, "className": "text-center", 
                    "render": function(data, type, row) {
                        return `<button class="btn btn-sm btn-primary shadow-sm" 
                                onclick="showDetailBilling('${row.no_rawat}', '${row.pasien.replace(/'/g, "\\'")}')" 
                                title="Lihat Rincian Lengkap">
                                <i class="fas fa-list-ul"></i>
                                </button>`;
                    }
                }
            ]
        });
    });

    function reloadTable() { tableKunjungan.ajax.reload(); }

    function showDetailBilling(noRawat, namaPasien) {
        $('#lbl-pasien').text(namaPasien);
        $('#lbl-norawat').text(noRawat);
        $('#bodyDetailBilling').html('<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary"></div><br>Menghitung ulang rincian biaya...</td></tr>');
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
                        if (item.is_header) {
                            html += `<tr class="table-secondary fw-bold">
                                        <td colspan="6">${item.keterangan} ${item.tagihan}</td>
                                     </tr>`;
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
            },
            error: function() {
                $('#bodyDetailBilling').html('<tr><td colspan="6" class="text-center text-danger fw-bold">Gagal mengambil data dari server.</td></tr>');
            }
        });
    }
</script>
<?php $page_js = ob_get_clean(); ?>
<?php require_once('includes/footer.php'); ?>