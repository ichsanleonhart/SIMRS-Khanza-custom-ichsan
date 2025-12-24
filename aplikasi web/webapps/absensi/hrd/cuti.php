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
    <title>Approval Cuti Pegawai</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../js/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    
    <style>
        .dataTables_wrapper .dataTables_length select, .dataTables_wrapper .dataTables_filter input {
            background-color: #1f2937; color: white; border: 1px solid #4b5563; padding: 4px; border-radius: 4px;
        }
        table.dataTable tbody tr { background-color: #1f2937; color: #d1d5db; }
        table.dataTable tbody tr:hover { background-color: #374151; }
        /* Style Radio Button */
        input[type="radio"]:checked { background-color: #3b82f6; border-color: #3b82f6; }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen p-4">

    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-blue-400">Approval Cuti</h1>
                <p class="text-xs text-gray-400">Kelola pengajuan cuti & otomatis update jadwal</p>
            </div>
            <a href="index.php" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded text-white">Kembali</a>
        </div>

        <div class="bg-gray-800 p-4 rounded-lg shadow border border-gray-700 mb-4">
            <h3 class="text-sm font-bold text-gray-300 mb-3 border-b border-gray-700 pb-1">Filter Data</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                
                <div class="md:col-span-5 flex flex-col gap-2">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Rentang Tanggal (Default: Hari Ini)</label>
                        <div class="flex gap-2">
                            <input type="date" id="tgl_mulai" class="bg-gray-900 border border-gray-600 rounded p-1.5 text-sm text-white w-full">
                            <span class="text-gray-500 self-center">-</span>
                            <input type="date" id="tgl_akhir" class="bg-gray-900 border border-gray-600 rounded p-1.5 text-sm text-white w-full">
                        </div>
                    </div>
                    <div class="flex gap-6 mt-1">
                        <label class="flex items-center space-x-2 text-xs text-gray-300 cursor-pointer">
                            <input type="radio" name="filter_type" value="pengajuan" class="w-4 h-4 bg-gray-700 border-gray-500">
                            <span>Tgl Pengajuan</span>
                        </label>
                        <label class="flex items-center space-x-2 text-xs text-gray-300 cursor-pointer">
                            <input type="radio" name="filter_type" value="cuti" class="w-4 h-4 bg-gray-700 border-gray-500" checked>
                            <span>Tgl Mulai Cuti (Default)</span>
                        </label>
                    </div>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs text-gray-400 mb-1">Status Atasan (L1)</label>
                    <select id="status_atasan" class="w-full bg-gray-900 border border-gray-600 rounded p-1.5 text-sm text-white">
                        <option value="ALL">Semua</option>
                        <option value="Proses Pengajuan">Proses Pengajuan</option>
                        <option value="Disetujui">Disetujui</option>
                        <option value="Ditolak">Ditolak</option>
                    </select>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs text-gray-400 mb-1">Status HRD (L2)</label>
                    <select id="status_hrd" class="w-full bg-gray-900 border border-gray-600 rounded p-1.5 text-sm text-white">
                        <option value="ALL">Semua</option>
                        <option value="Proses Pengajuan" selected>Proses Pengajuan</option>
                        <option value="Disetujui">Disetujui</option>
                        <option value="Ditolak">Ditolak</option>
                    </select>
                </div>

                <div class="md:col-span-1">
                     <label class="block text-xs text-gray-400 mb-1 opacity-0">Act</label>
                    <button type="button" id="btnCari" onclick="reloadTable()" class="w-full bg-blue-600 hover:bg-blue-500 text-white py-1.5 rounded text-sm font-bold shadow-lg transition border border-blue-500 h-[34px]">
                        Cari
                    </button>
                </div>

            </div>
        </div>

        <div class="bg-gray-800 p-4 rounded shadow border border-gray-700">
            <table id="tabelCuti" class="w-full text-sm text-left text-gray-300">
                <thead class="text-xs uppercase bg-gray-700 text-gray-400">
                    <tr>
                        <th class="p-3">Tanggal Ajuan</th>
                        <th class="p-3">Pegawai</th>
                        <th class="p-3">Rencana Cuti</th>
                        <th class="p-3">Kepentingan</th>
                        <th class="p-3">Status Atasan (L1)</th>
                        <th class="p-3">Status HRD (L2)</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

<script>
    let table;

    $(document).ready(function() {
        // 1. SET DEFAULT DATE (HARI INI)
        const today = new Date().toISOString().split('T')[0];
        $('#tgl_mulai').val(today);
        $('#tgl_akhir').val(today);
        
        // Init DataTable
        table = $('#tabelCuti').DataTable({
            processing: true,
            serverSide: false, 
            ajax: {
                url: 'api_cuti.php?act=list',
                type: 'GET',
                data: function(d) {
                    // AMBIL PARAMETER FILTER SAAT AJAX JALAN
                    d.tgl_mulai   = $('#tgl_mulai').val();
                    d.tgl_akhir   = $('#tgl_akhir').val();
                    d.filter_type = $('input[name="filter_type"]:checked').val(); // 'pengajuan' atau 'cuti'
                    d.status_atasan = $('#status_atasan').val();
                    d.status_hrd    = $('#status_hrd').val();
                }
            },
            order: [[ 0, "desc" ]],
            columns: [
                { data: 'tanggal', render: d => d },
                { data: 'nama', render: (data, type, row) => {
                    return `<div><div class="font-bold text-white">${data}</div>
                            <div class="text-xs text-gray-400">${row.nik} - ${row.nama_dep ?? '-'}</div></div>`;
                }},
                { data: null, render: row => {
                    return `<div class="font-mono text-xs">
                                ${row.tanggal_awal} s.d ${row.tanggal_akhir}<br>
                                <span class="text-yellow-400 font-bold">${row.jumlah} Hari</span>
                            </div>`;
                }},
                { data: 'kepentingan' },
                
                // STATUS L1
                { data: 'status', render: function(data) {
                    if(data === 'Disetujui') return `<span class="bg-green-900 text-green-200 px-2 py-1 rounded text-xs">✅ Disetujui</span>`;
                    if(data === 'Ditolak') return `<span class="bg-red-900 text-red-200 px-2 py-1 rounded text-xs">❌ Ditolak</span>`;
                    // Default / Proses Pengajuan
                    return `<span class="bg-yellow-600 text-white px-2 py-1 rounded text-xs animate-pulse">⏳ Proses Pengajuan</span>`;
                }},

                // STATUS L2
                { data: 'status_persetujuan_HRD', render: function(data) {
                    if(data === 'Disetujui') return `<span class="text-green-400 font-bold">DONE</span>`;
                    if(data === 'Ditolak') return `<span class="text-red-400 font-bold">REJECTED</span>`;
                    // Default / Proses Pengajuan
                    return `<span class="text-gray-500">PROSES PENGAJUAN</span>`;
                }},

                { data: null, className: "text-center", render: function(data, type, row) {
                    // Logic tombol selesai jika HRD sudah bertindak
                    if(row.status_persetujuan_HRD === 'Disetujui' || row.status_persetujuan_HRD === 'Ditolak') {
                        return `<span class="text-xs text-gray-500">Selesai</span>`;
                    }
                    return `<div class="flex gap-1 justify-center">
                                <button onclick="proses('${row.no_pengajuan}', 'Disetujui')" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1 rounded text-xs font-bold" title="Setujui">Setujui</button>
                                <button onclick="proses('${row.no_pengajuan}', 'Ditolak')" class="bg-red-600 hover:bg-red-500 text-white px-3 py-1 rounded text-xs font-bold">Tolak</button>
                            </div>`;
                }}
            ]
        });
    });

    // FUNGSI RELOAD TABLE SAAT TOMBOL CARI DIKLIK
    function reloadTable() {
        const btn = $('#btnCari');
        const originalText = btn.text();
        
        // Visual Feedback
        btn.text('...').prop('disabled', true);

        // Reload Ajax
        table.ajax.reload(function() {
            btn.text(originalText).prop('disabled', false);
        });
    }

    function proses(no_pengajuan, status) {
        let pesan = status === 'Disetujui' 
            ? "Jadwal pegawai akan otomatis berubah menjadi 'Cuti'."
            : "Pengajuan akan ditolak.";
            
        Swal.fire({
            title: 'Konfirmasi HRD',
            text: pesan,
            icon: status === 'Disetujui' ? 'question' : 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Lakukan',
            confirmButtonColor: status === 'Disetujui' ? '#2563eb' : '#dc2626'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('api_cuti.php?act=approve', {
                    no_pengajuan: no_pengajuan,
                    status: status
                }, function(res) {
                    if(res.status === 'success') {
                        Swal.fire('Berhasil', res.message, 'success');
                        reloadTable();
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                }, 'json');
            }
        });
    }
</script>
</body>
</html>