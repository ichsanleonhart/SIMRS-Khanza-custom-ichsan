<?php
/*
 * File: laporan_rl.php
 * Fungsi: Laporan RL 3.18 Farmasi Resep (Grouping Dinamis + Drill Down)
 */
session_start();
if (!isset($_SESSION['casemix_login']) || $_SESSION['casemix_login'] !== true) {
    header("Location: index.php"); exit;
}
require_once('../conf/conf.php');
$koneksi = bukakoneksi();

// Data Instansi & User (Standard)
$q_set = mysqli_query($koneksi, "SELECT nama_instansi, logo FROM setting LIMIT 1");
$r_set = mysqli_fetch_assoc($q_set);
$nama_instansi = $r_set['nama_instansi'] ?? 'RS';
$logo_b64 = isset($r_set['logo']) ? 'data:image/jpeg;base64,' . base64_encode($r_set['logo']) : 'logo.php';
$nama_user_login = "User"; // Simplified for brevity
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-01'); 
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan RL 3.18 - <?= $nama_instansi ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { overflow-x: hidden; background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.9rem; }
        /* Sidebar Style omitted for brevity - use previous sidebar code */
        #sidebar-wrapper { min-height: 100vh; width: 250px; margin-left: -250px; position: fixed; top: 0; left: 0; bottom: 0; z-index: 1000; transition: margin .25s ease-out; background: linear-gradient(180deg, #1e3c72 0%, #2a5298 100%); color: #fff; box-shadow: 4px 0 15px rgba(0,0,0,0.1); }
        #sidebar-wrapper .sidebar-heading { padding: 1.2rem 1rem; font-size: 1.1rem; border-bottom: 1px solid rgba(255,255,255,0.15); }
        #sidebar-wrapper .list-group { width: 250px; }
        #sidebar-wrapper .list-group-item { background: transparent; color: rgba(255,255,255,0.85); border: none; padding: 12px 20px; }
        #sidebar-wrapper .list-group-item:hover { background: rgba(255,255,255,0.15); color: #fff; border-left: 4px solid #fff; }
        #sidebar-wrapper .list-group-item.active { background: rgba(255,255,255,0.2); color: #fff; font-weight: bold; border-left: 4px solid #4cd137; }
        #page-content-wrapper { width: 100%; transition: margin .25s ease-out; }
        body.sb-sidenav-toggled #sidebar-wrapper { margin-left: 0; }
        @media (min-width: 768px) { #sidebar-wrapper { margin-left: 0; } #page-content-wrapper { margin-left: 250px; } body.sb-sidenav-toggled #sidebar-wrapper { margin-left: -250px; } body.sb-sidenav-toggled #page-content-wrapper { margin-left: 0; } }
        #overlay { display: none; position: fixed; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 900; }
        body.sb-sidenav-toggled #overlay { display: block; } @media (min-width: 768px) { body.sb-sidenav-toggled #overlay { display: none; } }
        .top-navbar { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 10px 20px; }
        
        .table th { white-space: nowrap; text-align: center; vertical-align: middle; background-color: #f8f9fa; font-size: 0.85rem; }
        .table td { vertical-align: middle; font-size: 0.85rem; }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">
    <div id="overlay" onclick="toggleMenu()"></div>
    <?php include 'sidebar.php'; ?>

    <div id="page-content-wrapper">
        <nav class="top-navbar d-flex justify-content-between align-items-center sticky-top">
            <button class="btn btn-outline-secondary border-0" id="menu-toggle"><i class="fas fa-bars fa-lg"></i></button>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-md-block line-height-sm">
                    <div class="fw-bold text-dark small"><?= $nama_user_login ?></div>
                    <small class="text-muted" style="font-size:0.75rem">Petugas Casemix</small>
                </div>
                <img src="logo.php" class="rounded-circle border" width="35">
            </div>
        </nav>

        <div class="container-fluid px-4 py-4">
            
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body py-3">
                    <h5 class="fw-bold text-primary mb-3"><i class="fas fa-pills me-2"></i>Laporan RL 3.18 (Farmasi Resep)</h5>
                    <form id="filterForm">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Dari Tanggal</label>
                                <input type="date" class="form-control" id="tgl_awal" value="<?= $tgl_awal ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Sampai Tanggal</label>
                                <input type="date" class="form-control" id="tgl_akhir" value="<?= $tgl_akhir ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Pengelompokan Data</label>
                                <select class="form-select" id="group_mode">
                                    <option value="golongan" selected>Berdasarkan Golongan Obat</option>
                                    <option value="kategori">Berdasarkan Kategori Barang</option>
                                    <option value="jenis">Berdasarkan Jenis Barang</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary w-100" onclick="loadData()">
                                    <i class="fas fa-search me-2"></i> Tampilkan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm w-100" id="dataTable">
                            <thead class="text-center" style="font-size:0.75rem;">
                                <tr>
                                    <th width="5%" class="bg-dark text-white">No</th>
                                    <th class="bg-dark text-white text-start">Nama Kelompok</th>
                                    <th width="15%" class="bg-success text-white">Rawat Jalan</th>
                                    <th width="15%" class="bg-danger text-white">IGD</th>
                                    <th width="15%" class="bg-primary text-white">Rawat Inap</th>
                                    <th width="15%" class="bg-dark text-white">Total</th>
                                    <th width="5%" class="bg-dark text-white">Aksi</th>
                                </tr>
                            </thead>
                            <tbody style="font-size:0.85rem;"></tbody>
                            <tfoot class="bg-light fw-bold">
                                <tr>
                                    <td colspan="2" class="text-end">Total Keseluruhan :</td>
                                    <td id="sum-ralan" class="text-end">0</td>
                                    <td id="sum-igd" class="text-end">0</td>
                                    <td id="sum-ranap" class="text-end">0</td>
                                    <td id="sum-total" class="text-end">0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-list me-2"></i>Rincian Penggunaan Obat</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border shadow-sm mb-3">
                    <strong>Filter:</strong> <span id="detail-title">...</span> | 
                    <strong>Periode:</strong> <span id="detail-periode">...</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm w-100" id="tableDetail">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu</th>
                                <th>No. Rawat</th>
                                <th>Pasien</th>
                                <th>Unit</th>
                                <th>Nama Obat</th>
                                <th class="text-end">Jml</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script>
    document.getElementById("menu-toggle").onclick = function () { document.body.classList.toggle("sb-sidenav-toggled"); };

    var myTable, detailTable;

    $(document).ready(function() {
        // 1. TABEL UTAMA
        myTable = $('#dataTable').DataTable({
            dom: 'Bfrtip',
            buttons: [ 
                { 
                    extend: 'excelHtml5', 
                    className: 'btn btn-success btn-sm mb-2', 
                    text: '<i class="fas fa-file-excel me-1"></i> Excel', 
                    title: 'Laporan RL 3.18 Farmasi Resep',
                    exportOptions: { columns: ':not(:last-child)' } // JANGAN EXPORT KOLOM AKSI
                },
                { 
                    extend: 'print', 
                    className: 'btn btn-secondary btn-sm mb-2', 
                    text: '<i class="fas fa-print me-1"></i> Print',
                    exportOptions: { columns: ':not(:last-child)' }
                } 
            ],
            columns: [
                { data: null, render: (d,t,r,m) => m.row + 1, className: "text-center" },
                { data: "nama_group", className: "fw-bold" },
                { data: "jml_ralan", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "jml_igd", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "jml_ranap", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "total_semua", className: "text-end fw-bold bg-light", render: $.fn.dataTable.render.number('.', ',', 0) },
                { 
                    data: null, className: "text-center",
                    render: function(data, type, row) {
                        // Kirim Nama & ID Group ke fungsi openDetail
                        return `<button class="btn btn-sm btn-info text-white" onclick="openDetail('${row.kode_group}', '${row.nama_group}')" title="Lihat Rincian"><i class="fas fa-search"></i></button>`;
                    }
                }
            ],
            ordering: false, paging: false, searching: false, info: false,
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                var intVal = function (i) { return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0; };
                
                $('#sum-ralan').html(api.column(2).data().reduce((a, b) => intVal(a) + intVal(b), 0).toLocaleString('id-ID'));
                $('#sum-igd').html(api.column(3).data().reduce((a, b) => intVal(a) + intVal(b), 0).toLocaleString('id-ID'));
                $('#sum-ranap').html(api.column(4).data().reduce((a, b) => intVal(a) + intVal(b), 0).toLocaleString('id-ID'));
                $('#sum-total').html(api.column(5).data().reduce((a, b) => intVal(a) + intVal(b), 0).toLocaleString('id-ID'));
            }
        });

        // 2. TABEL DETAIL (MODAL)
        detailTable = $('#tableDetail').DataTable({
            dom: 'Bfrtip',
            buttons: [ 
                { extend: 'excelHtml5', className: 'btn btn-success btn-sm', text: '<i class="fas fa-file-excel me-1"></i> Export Detail' } 
            ],
            pageLength: 10,
            columns: [
                { data: "tgl_perawatan", render: (d,t,r) => d + ' ' + r.jam },
                { data: "no_rawat" },
                { data: "nm_pasien" },
                { data: "unit", render: (d) => `<span class="badge ${d=='IGD'?'bg-danger':(d=='Rawat Inap'?'bg-primary':'bg-success')}">${d}</span>` },
                { data: "nama_brng" },
                { data: "jml", className: "text-end" },
                { data: "harga", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "total", className: "text-end fw-bold", render: $.fn.dataTable.render.number('.', ',', 0) }
            ]
        });

        loadData();
    });

    function loadData() {
        var params = {
            tgl_awal: $('#tgl_awal').val(),
            tgl_akhir: $('#tgl_akhir').val(),
            mode: $('#group_mode').val() // Kirim mode grouping
        };

        $.ajax({
            url: 'api/data_rl_3_18.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(resp) {
                myTable.clear().rows.add(resp.data).draw();
            },
            error: function() { alert("Gagal memuat data."); }
        });
    }

    function openDetail(id, nama) {
        var tgl1 = $('#tgl_awal').val();
        var tgl2 = $('#tgl_akhir').val();
        var mode = $('#group_mode').val();

        $('#detail-title').text(nama);
        $('#detail-periode').text(tgl1 + ' s.d ' + tgl2);
        $('#modalDetail').modal('show');
        
        detailTable.clear().draw(); // Bersihkan dulu

        $.ajax({
            url: 'api/data_rl_3_18_detail.php',
            type: 'GET',
            data: { tgl_awal: tgl1, tgl_akhir: tgl2, mode: mode, id: id },
            success: function(resp) {
                detailTable.rows.add(resp.data).draw();
            },
            error: function() { console.error("Gagal load detail"); }
        });
    }
</script>

</body>
</html>
<?php mysqli_close($koneksi); ?>