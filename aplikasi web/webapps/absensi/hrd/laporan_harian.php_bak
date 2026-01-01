<?php
session_start();
require_once('../../conf/conf.php');
if (!isset($_SESSION['hrd_login'])) { header("Location: login.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Harian Absensi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../js/jquery.min.js"></script>
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* Overrides untuk DataTables agar match dengan Tailwind Dark Mode style kita */
        .dataTables_wrapper .dataTables_length select, 
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #4b5563;
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
            background-color: #1f2937;
            color: white;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #2563eb !important;
            border-color: #2563eb !important;
            color: white !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: white !important;
        }
        table.dataTable tbody tr {
            background-color: #1f2937;
            color: #d1d5db;
        }
        table.dataTable tbody tr:hover {
            background-color: #374151;
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen p-4">

    <div class="max-w-7xl mx-auto">
        <div class="bg-gray-800 p-4 rounded-lg shadow-lg mb-6 border border-gray-700">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold text-blue-400">Laporan Harian</h1>
                <a href="index.php" class="text-gray-400 hover:text-white">Kembali</a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Dari Tanggal</label>
                    <input type="date" id="tgl1" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-sm" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Sampai Tanggal</label>
                    <input type="date" id="tgl2" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-sm" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Departemen</label>
                    <select id="dep" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-sm">
                        <option value="ALL">Semua Departemen</option>
                    </select>
                </div>
                <div>
                    <button onclick="loadTable()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition">
                        Tampilkan Data
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 p-4 rounded-lg shadow-lg border border-gray-700">
            <table id="tabelAbsen" class="display responsive nowrap w-full text-sm" style="width:100%">
                <thead>
                    <tr class="text-left text-gray-300">
                        <th>Nama Pegawai</th>
                        <th>Departemen</th>
                        <th>Shift</th>
                        <th>Masuk</th>
                        <th>Pulang</th>
                        <th>Status</th>
                        <th>Telat</th>
                        <th>Durasi</th>
                        <th>Foto</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

    <script>
        let table;

        $(document).ready(function() {
            // 1. Load Dropdown Departemen
            $.get('api_laporan.php?act=get_dep', function(res) {
                const data = JSON.parse(res);
                data.forEach(d => {
                    $('#dep').append(`<option value="${d.dep_id}">${d.nama}</option>`);
                });
            });

            // 2. Init DataTable
            table = $('#tabelAbsen').DataTable({
                responsive: true,
                dom: 'Bfrtip', // Layout DataTables (Buttons, Filter, Table, Info, Pagination)
                buttons: [
                    { extend: 'excel', className: 'bg-green-600 text-white px-3 py-1 rounded text-xs mr-1 border-none' },
                    { extend: 'pdf', className: 'bg-red-600 text-white px-3 py-1 rounded text-xs mr-1 border-none' },
                    { extend: 'print', className: 'bg-gray-600 text-white px-3 py-1 rounded text-xs mr-1 border-none' }
                ],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampil _MENU_ baris",
                    info: "Menampilkan _START_ s.d _END_ dari _TOTAL_ data",
                    paginate: { first: "Awal", last: "Akhir", next: "→", previous: "←" }
                },
                columns: [
                    { data: 'nama', render: (data, type, row) => `<b>${data}</b><br><span class="text-xs text-gray-400">${row.nik}</span>` },
                    { data: 'dep' },
                    { data: 'shift' },
                    { data: 'display_masuk', className: 'text-green-400 font-mono' },
                    { data: 'display_pulang', className: 'text-yellow-400 font-mono' },
                    { data: 'status', render: function(data) {
                        if(data.includes('Terlambat')) return `<span class="bg-red-900 text-red-200 px-2 py-0.5 rounded text-xs">${data}</span>`;
                        return `<span class="bg-green-900 text-green-200 px-2 py-0.5 rounded text-xs">${data}</span>`;
                    }},
                    { data: 'telat' },
                    { data: 'durasi' },
                    { data: 'photo', orderable: false, render: function(data, type, row) {
                        // Path foto dari DB biasanya relatif webapps, kita sesuaikan untuk view
                        // Asumsi data: absensi/foto_absen/2023...
                        // Kita perlu: ../../../absensi/foto_absen... (naik dari hrd->absensi->webapps ke root->absensi)
                        // TAPI! File ini ada di /hrd/, jadi pathnya cukup ../../ + path_db
                        let imgPath = "../../" + data;
                        return `<button onclick="lihatFoto('${imgPath}', '${row.nama}')" class="text-blue-400 hover:text-blue-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>`;
                    }},
                    { data: 'catatan' }
                ]
            });

            // Load data pertama kali
            loadTable();
        });

        function loadTable() {
            const tgl1 = $('#tgl1').val();
            const tgl2 = $('#tgl2').val();
            const dep = $('#dep').val();
            
            // Gunakan AJAX reload milik DataTables
            table.ajax.url(`api_laporan.php?act=get_data&tgl1=${tgl1}&tgl2=${tgl2}&dep=${dep}`).load();
        }

        function lihatFoto(url, nama) {
            Swal.fire({
                title: nama,
                imageUrl: url,
                imageAlt: 'Bukti Absen',
                imageHeight: 400,
                background: '#1f2937',
                color: '#fff'
            });
        }
    </script>
</body>
</html>