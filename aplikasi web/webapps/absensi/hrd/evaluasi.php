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
    <title>Evaluasi Ketidakhadiran & Mangkir</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../js/jquery.min.js"></script>
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .dataTables_wrapper { color: #d1d5db !important; }
        .dataTables_wrapper select, .dataTables_wrapper input {
            background-color: #1f2937; color: white; border: 1px solid #4b5563; padding: 4px; border-radius: 4px;
        }
        table.dataTable tbody tr { background-color: #1f2937; color: #d1d5db; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen p-4 font-sans">

    <div class="max-w-[1600px] mx-auto">
        <div class="bg-gray-800 p-5 rounded-lg shadow-lg mb-6 border border-gray-700">
            <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
                <div>
                    <h1 class="text-2xl font-bold text-red-400">Evaluasi Ketidakhadiran</h1>
                    <p class="text-xs text-gray-400">Komparasi Jadwal Dinas vs Realisasi Absensi (Realtime)</p>
                </div>
                <a href="index.php" class="text-gray-400 hover:text-white bg-gray-700 px-3 py-2 rounded text-sm">Kembali</a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Dari Tanggal</label>
                    <input type="date" id="tgl1" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-sm text-white" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Sampai Tanggal</label>
                    <input type="date" id="tgl2" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-sm text-white" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Departemen</label>
                    <select id="dep" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-sm text-white">
                        <option value="ALL">Semua Departemen</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Mode Laporan</label>
                    <select id="filter" class="w-full bg-gray-900 border border-red-500 rounded p-2 text-sm text-white font-bold">
                        <option value="MANGKIR">HANYA YANG MANGKIR / ALPHA</option>
                        <option value="ALL">Tampilkan Semua Jadwal</option>
                    </select>
                </div>
                <div>
                    <button onclick="loadEvaluasi()" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition shadow-lg">
                        🔍 Analisa Data
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 p-4 rounded-lg shadow-lg border border-gray-700">
            <table id="tabelEvaluasi" class="display responsive nowrap w-full text-sm" style="width:100%">
                <thead>
                    <tr class="text-left text-gray-400 border-b border-gray-600">
                        <th>Tanggal</th>
                        <th>Nama Pegawai</th>
                        <th class="text-center">Shift Jadwal</th>
                        <th class="text-center">Jam Kerja (Wajib)</th>
                        <th class="text-center">Status Kehadiran</th>
                        <th class="text-center">Realisasi Masuk</th>
                        <th class="text-center">Realisasi Pulang</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>

    <script>
        let table;

        $(document).ready(function() {
            // Load Departemen
            $.get('api_laporan.php?act=get_dep', function(res) {
                try {
                    const data = JSON.parse(res);
                    data.forEach(d => { $('#dep').append(`<option value="${d.dep_id}">${d.nama}</option>`); });
                } catch(e) {}
            });

            table = $('#tabelEvaluasi').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    { extend: 'excel', text: 'Download Excel Evaluasi', className: 'bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs border-none font-bold' }
                ],
                order: [[0, 'asc'], [1, 'asc']],
                columns: [
                    { data: 'tanggal', className: 'text-gray-300' },
                    { data: 'nama', render: (data, type, row) => `<b>${data}</b><br><span class="text-xs text-gray-500">${row.nik}</span>` },
                    
                    // KOLOM SHIFT DENGAN WARNA VIBE
                    { data: 'jadwal', className: "text-center", render: function(d) {
                        let cl = 'bg-gray-700 text-gray-300 border-gray-600';
                        let v = d.toLowerCase();
                        if(v.includes('pagi')) cl = 'bg-green-900 text-green-200 border-green-700';
                        else if(v.includes('siang')) cl = 'bg-yellow-900 text-yellow-200 border-yellow-700';
                        else if(v.includes('malam')) cl = 'bg-blue-900 text-blue-200 border-blue-700';
                        
                        return `<span class="${cl} px-2 py-1 rounded text-xs border font-bold uppercase tracking-wider">${d}</span>`;
                    }},
                    
                    { data: null, className: "text-center", render: function(data, type, row) {
                        if(row.jadwal_in === '-') return '<span class="text-gray-600">-</span>';
                        return `<span class="text-gray-400 font-mono text-xs">${row.jadwal_in} - ${row.jadwal_out}</span>`;
                    }},

                    // STATUS EVALUASI
                    { data: 'status_evaluasi', className: "text-center", render: function(data) {
                        if(data === 'MANGKIR') return `<span class="bg-red-600 text-white px-3 py-1 rounded-full text-[10px] font-bold shadow-red-900 shadow-md animate-pulse">❌ MANGKIR</span>`;
                        if(data === 'DINAS') return `<span class="bg-yellow-600 text-white px-3 py-1 rounded-full text-[10px] font-bold animate-pulse">⚡ SEDANG DINAS</span>`;
                        if(data === 'BELUM_WAKTUNYA') return `<span class="bg-gray-600 text-gray-300 px-3 py-1 rounded-full text-[10px] font-bold border border-gray-500">⏳ BELUM MULAI</span>`;
                        return `<span class="bg-green-600 text-white px-2 py-1 rounded-full text-[10px] font-bold">✅ HADIR</span>`;
                    }},

                    { data: 'jam_masuk', className: "text-center font-mono text-green-400" },
                    { data: 'jam_pulang', className: "text-center font-mono text-yellow-400" },
                    { data: 'keterangan', className: "italic text-gray-400 text-xs" }
                ]
            });
        });

        function loadEvaluasi() {
            const tgl1 = $('#tgl1').val();
            const tgl2 = $('#tgl2').val();
            const dep = $('#dep').val();
            const filter = $('#filter').val();

            Swal.fire({title: 'Sedang Menganalisa...', text: 'Membandingkan Jadwal vs Absensi (Realtime & Arsip)', didOpen: () => Swal.showLoading()});

            $.ajax({
                url: `api_evaluasi.php?act=analyze&tgl1=${tgl1}&tgl2=${tgl2}&dep=${dep}&filter=${filter}`,
                success: function(res) {
                    Swal.close();
                    try {
                        const data = JSON.parse(res);
                        table.clear().rows.add(data.data).draw();
                        if(data.data.length === 0) Swal.fire('Bersih!', 'Tidak ditemukan data sesuai filter.', 'success');
                    } catch(e) { Swal.fire('Error', 'Gagal memproses data.', 'error'); }
                },
                error: () => Swal.fire('Error', 'Koneksi gagal.', 'error')
            });
        }
    </script>
</body>
</html>