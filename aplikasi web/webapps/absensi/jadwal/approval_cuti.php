<?php
session_start();
require_once('../../conf/conf.php');
if (!isset($_SESSION['jadwal_login'])) { header("Location: login.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Cuti - Kepala Unit</title>
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
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen p-4">

    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-blue-400">Persetujuan Cuti (Level 1)</h1>
                <p class="text-xs text-gray-400">Kelola izin cuti bawahan Anda</p>
            </div>
            <a href="index.php" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded text-white">Kembali</a>
        </div>

        <div class="bg-gray-800 p-4 rounded shadow border border-gray-700">
            <table id="tabelCuti" class="w-full text-sm text-left text-gray-300">
                <thead class="text-xs uppercase bg-gray-700 text-gray-400">
                    <tr>
                        <th class="p-3">Tanggal Ajuan</th>
                        <th class="p-3">Pegawai</th>
                        <th class="p-3">Detail Cuti</th>
                        <th class="p-3">Alasan</th>
                        <th class="p-3 text-center">Status Anda</th>
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
        table = $('#tabelCuti').DataTable({
            ajax: 'api_approval.php?act=list',
            order: [[ 0, "desc" ]],
            columns: [
                { data: 'tanggal', render: d => d },
                { data: 'nama', render: (data, type, row) => {
                    return `<div><div class="font-bold text-white">${data}</div>
                            <div class="text-xs text-gray-400">${row.nik}</div></div>`;
                }},
                { data: null, render: row => {
                    return `<div class="font-mono text-xs">
                                ${row.tanggal_awal} s.d ${row.tanggal_akhir}<br>
                                <span class="text-yellow-400 font-bold">${row.jumlah} Hari</span><br>
                                <span class="text-blue-300">${row.urgensi}</span>
                            </div>`;
                }},
                { data: 'kepentingan' },
                
                // STATUS LEVEL 1 (STATUS ANDA SEBAGAI KARU)
                { data: 'status', className: "text-center", render: function(data) {
                    if(data === 'Disetujui') return `<span class="bg-green-900 text-green-200 px-2 py-1 rounded text-xs font-bold">✅ DISETUJUI</span>`;
                    if(data === 'Ditolak') return `<span class="bg-red-900 text-red-200 px-2 py-1 rounded text-xs font-bold">❌ DITOLAK</span>`;
                    return `<span class="bg-yellow-600 text-white px-2 py-1 rounded text-xs animate-pulse">⏳ MENUNGGU</span>`;
                }},

                // TOMBOL AKSI
                { data: null, className: "text-center", render: function(data, type, row) {
                    // Jika sudah diproses, tombol mati/hilang
                    if(row.status === 'Disetujui' || row.status === 'Ditolak') {
                        return `<span class="text-xs text-gray-500">Selesai</span>`;
                    }
                    
                    return `<div class="flex gap-2 justify-center">
                                <button onclick="proses('${row.no_pengajuan}', 'Disetujui')" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1 rounded text-xs font-bold shadow">Setujui</button>
                                <button onclick="proses('${row.no_pengajuan}', 'Ditolak')" class="bg-red-600 hover:bg-red-500 text-white px-3 py-1 rounded text-xs font-bold shadow">Tolak</button>
                            </div>`;
                }}
            ]
        });
    });

    function proses(no_pengajuan, status) {
        let pesan = status === 'Disetujui' 
            ? "Saya menyetujui cuti ini dan meneruskannya ke HRD."
            : "Saya menolak pengajuan cuti ini.";
            
        Swal.fire({
            title: 'Konfirmasi Kepala Unit',
            text: pesan,
            icon: status === 'Disetujui' ? 'question' : 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Proses',
            confirmButtonColor: status === 'Disetujui' ? '#2563eb' : '#dc2626'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('api_approval.php?act=action', {
                    no_pengajuan: no_pengajuan,
                    status: status
                }, function(res) {
                    if(res.status === 'success') {
                        Swal.fire('Berhasil', res.message, 'success');
                        table.ajax.reload();
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