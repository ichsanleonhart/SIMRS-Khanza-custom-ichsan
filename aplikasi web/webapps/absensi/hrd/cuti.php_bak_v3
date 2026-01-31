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
                
                <div class="md:col-span-4 flex flex-col gap-2">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Rentang Tanggal</label>
                        <div class="flex gap-2">
                            <input type="date" id="tgl_mulai" class="bg-gray-900 border border-gray-600 rounded p-1.5 text-sm text-white w-full">
                            <span class="text-gray-500 self-center">-</span>
                            <input type="date" id="tgl_akhir" class="bg-gray-900 border border-gray-600 rounded p-1.5 text-sm text-white w-full">
                        </div>
                    </div>
                    <div class="flex gap-4 mt-1">
                        <label class="flex items-center space-x-2 text-xs text-gray-300 cursor-pointer">
                            <input type="radio" name="filter_type" value="pengajuan" class="w-4 h-4 bg-gray-700 border-gray-500">
                            <span>Tgl Pengajuan</span>
                        </label>
                        <label class="flex items-center space-x-2 text-xs text-gray-300 cursor-pointer">
                            <input type="radio" name="filter_type" value="cuti" class="w-4 h-4 bg-gray-700 border-gray-500" checked>
                            <span>Tgl Mulai Cuti</span>
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

                <div class="md:col-span-2 flex gap-2 items-end h-full pt-6">
                    <button type="button" id="btnCari" onclick="reloadTable()" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white py-1.5 rounded text-sm font-bold shadow transition border border-blue-500 h-[36px]">
                        Cari
                    </button>
                    <button type="button" onclick="exportExcel()" class="flex-1 bg-green-700 hover:bg-green-600 text-white py-1.5 rounded text-sm font-bold shadow transition border border-green-600 h-[36px]" title="Download Excel">
                        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 p-4 rounded shadow border border-gray-700 overflow-x-auto">
            <table id="tabelCuti" class="w-full text-sm text-left text-gray-300">
                <thead class="text-xs uppercase bg-gray-700 text-gray-400">
                    <tr>
                        <th class="p-3">Tanggal Ajuan</th>
                        <th class="p-3">Pegawai & Sisa Cuti</th>
                        <th class="p-3">Rencana Cuti</th>
                        <th class="p-3">Kepentingan</th>
                        <th class="p-3">Status Atasan (L1)</th>
                        <th class="p-3">Waktu L1</th>
                        <th class="p-3">Status HRD (L2)</th>
                        <th class="p-3">Waktu L2</th>
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
        const today = new Date().toISOString().split('T')[0];
        $('#tgl_mulai').val(today);
        $('#tgl_akhir').val(today);
        
        table = $('#tabelCuti').DataTable({
            processing: true,
            serverSide: false, 
            ajax: {
                url: 'api_cuti.php?act=list',
                type: 'GET',
                data: function(d) {
                    d.tgl_mulai   = $('#tgl_mulai').val();
                    d.tgl_akhir   = $('#tgl_akhir').val();
                    d.filter_type = $('input[name="filter_type"]:checked').val();
                    d.status_atasan = $('#status_atasan').val();
                    d.status_hrd    = $('#status_hrd').val();
                }
            },
            order: [[ 0, "desc" ]],
            columns: [
                { data: 'tanggal', render: d => d },
                
                // --- FIXED LOGIC WARNA BADGE ---
                { data: 'nama', render: (data, type, row) => {
                    // Gunakan sisa_cuti_angka untuk logika warna
                    let sisaVal = parseInt(row.sisa_cuti_angka);
                    let badgeColor = sisaVal > 0 ? 'bg-blue-900 border-blue-700 text-blue-200' : 'bg-red-900 border-red-700 text-red-200';
                    
                    return `<div>
                                <div class="font-bold text-white text-base">${data}</div>
                                <div class="text-xs text-gray-400 mb-1">${row.nik} - ${row.nama_dep ?? '-'}</div>
                                <span class="${badgeColor} text-[10px] px-2 py-0.5 rounded border font-mono">
                                   Sisa Cuti: <b>${row.sisa_cuti_display}</b>
                                </span>
                            </div>`;
                }},

                { data: null, render: row => {
                    return `<div class="font-mono text-xs">
                                ${row.tanggal_awal} s.d ${row.tanggal_akhir}<br>
                                <span class="text-yellow-400 font-bold">${row.jumlah} Hari</span>
                            </div>`;
                }},
                { data: 'kepentingan' },
                
                { data: 'status', render: function(data) {
                    if(data === 'Disetujui') return `<span class="bg-green-900 text-green-200 px-2 py-1 rounded text-xs">✅ Disetujui</span>`;
                    if(data === 'Ditolak') return `<span class="bg-red-900 text-red-200 px-2 py-1 rounded text-xs">❌ Ditolak</span>`;
                    return `<span class="bg-yellow-600 text-white px-2 py-1 rounded text-xs animate-pulse">⏳ Proses</span>`;
                }},

                { data: 'waktu_disetujui_atasan', render: function(data) {
                    if(!data || data === '0000-00-00 00:00:00') return '<span class="text-gray-600">-</span>';
                    return `<span class="text-[10px] text-gray-400 font-mono">${data}</span>`;
                }},

                { data: 'status_persetujuan_HRD', render: function(data) {
                    if(data === 'Disetujui') return `<span class="text-green-400 font-bold">DONE</span>`;
                    if(data === 'Ditolak') return `<span class="text-red-400 font-bold">REJECTED</span>`;
                    return `<span class="text-gray-500">PROSES</span>`;
                }},

                { data: 'waktu_disetujui_HRD', render: function(data) {
                    if(!data || data === '0000-00-00 00:00:00') return '<span class="text-gray-600">-</span>';
                    return `<span class="text-[10px] text-gray-400 font-mono">${data}</span>`;
                }},

                { data: null, className: "text-center", render: function(data, type, row) {
                    if(row.status_persetujuan_HRD === 'Disetujui' || row.status_persetujuan_HRD === 'Ditolak') {
                        return `<span class="text-xs text-gray-500">Selesai</span>`;
                    }
                    // KIRIM PARAMETER YANG SUDAH DIPISAH (ANGKA & DISPLAY)
                    return `<div class="flex gap-1 justify-center">
                                <button onclick="proses('${row.no_pengajuan}', 'Disetujui', '${row.sisa_cuti_angka}', '${row.jumlah}', '${row.sisa_cuti_display}')" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1 rounded text-xs font-bold" title="Setujui">ACC</button>
                                <button onclick="proses('${row.no_pengajuan}', 'Ditolak', 0, 0, '')" class="bg-red-600 hover:bg-red-500 text-white px-3 py-1 rounded text-xs font-bold">TOLAK</button>
                            </div>`;
                }}
            ]
        });
    });

    function reloadTable() {
        const btn = $('#btnCari');
        const originalText = btn.text();
        btn.text('...').prop('disabled', true);
        table.ajax.reload(function() {
            btn.text(originalText).prop('disabled', false);
        });
    }

    function exportExcel() {
        const tgl_mulai   = $('#tgl_mulai').val();
        const tgl_akhir   = $('#tgl_akhir').val();
        const filter_type = $('input[name="filter_type"]:checked').val();
        const status_atasan = $('#status_atasan').val();
        const status_hrd    = $('#status_hrd').val();

        const url = `api_cuti.php?act=export_excel&tgl_mulai=${tgl_mulai}&tgl_akhir=${tgl_akhir}&filter_type=${filter_type}&status_atasan=${status_atasan}&status_hrd=${status_hrd}`;
        window.open(url, '_blank');
    }

    // --- FIX LOGIC MATEMATIKA ---
    function proses(no_pengajuan, status, sisaAngka, jumlahDiajukan, sisaDisplay) {
        let title, htmlMsg, icon;
        
        if (status === 'Disetujui') {
            title = 'Setujui Pengajuan?';
            icon = 'question';
            
            // Hitung menggunakan data Angka Murni
            let sisaInt = parseInt(sisaAngka);
            let jmlInt = parseInt(jumlahDiajukan);
            let sisaSetelahCuti = sisaInt - jmlInt;
            
            let warningSisa = "";
            if (sisaSetelahCuti < 0) {
                warningSisa = `<div class='bg-red-900/50 border border-red-500 p-2 rounded text-red-200 text-xs mb-3 text-left'>
                                ⚠️ <b>PERINGATAN:</b> Sisa cuti pegawai (${sisaDisplay}) 
                                tidak mencukupi untuk pengajuan ini (${jmlInt} hari). 
                                <br>Sisa akan menjadi <b>minus (${sisaSetelahCuti})</b>.
                               </div>`;
            } else {
                warningSisa = `<div class='bg-blue-900/30 border border-blue-500 p-2 rounded text-blue-200 text-xs mb-3 text-left'>
                                ℹ️ Sisa cuti saat ini: <b>${sisaDisplay}</b>.
                                <br>Setelah disetujui sisa menjadi: <b>${sisaSetelahCuti} Hari</b>.
                               </div>`;
            }

            htmlMsg = `${warningSisa} Jadwal pegawai akan otomatis berubah menjadi 'Cuti'.`;
        } else {
            title = 'Tolak Pengajuan?';
            icon = 'warning';
            htmlMsg = "Pengajuan akan ditolak dan jadwal tidak berubah.";
        }
            
        Swal.fire({
            title: title,
            html: htmlMsg,
            icon: icon,
            showCancelButton: true,
            confirmButtonText: 'Ya, Lakukan',
            confirmButtonColor: status === 'Disetujui' ? '#2563eb' : '#dc2626',
            background: '#1f2937', color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('api_cuti.php?act=approve', {
                    no_pengajuan: no_pengajuan,
                    status: status
                }, function(res) {
                    if(res.status === 'success') {
                        Swal.fire({title: 'Berhasil', text: res.message, icon: 'success', background: '#1f2937', color: '#fff'});
                        reloadTable();
                    } else {
                        Swal.fire({title: 'Gagal', text: res.message, icon: 'error', background: '#1f2937', color: '#fff'});
                    }
                }, 'json');
            }
        });
    }
</script>
</body>
</html>