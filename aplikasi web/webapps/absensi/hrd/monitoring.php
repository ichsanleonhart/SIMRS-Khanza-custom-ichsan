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
    <title>Live Monitoring - Absensi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../js/jquery.min.js"></script>
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .dataTables_wrapper .dataTables_length select, 
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #4b5563;
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
            background-color: #1f2937;
            color: white;
        }
        table.dataTable tbody tr { background-color: #1f2937; color: #d1d5db; }
        table.dataTable tbody tr:hover { background-color: #374151; cursor: pointer; }
        .paginate_button.current { background: #2563eb !important; color: white !important; border: none !important; }
        .paginate_button { color: white !important; }
        .swal2-input { color: #fff !important; background: #374151 !important; border-color: #4b5563 !important; }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen p-4">

    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6 bg-gray-800 p-4 rounded-lg border border-gray-700 shadow-lg">
            <div class="flex items-center gap-4">
                <div class="relative flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Live Monitoring</h1>
                    <p class="text-xs text-gray-400">Pegawai yang sedang dinas saat ini</p>
                </div>
            </div>
            <div class="flex gap-2 items-center">
                <select id="dep" class="bg-gray-900 border border-gray-600 rounded p-2 text-sm focus:border-blue-500">
                    <option value="ALL">Semua Departemen</option>
                </select>
                <a href="index.php" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm transition">Kembali</a>
            </div>
        </div>

        <div class="bg-gray-800 p-5 rounded-lg shadow-lg border border-gray-700">
            <table id="tabelLive" class="display responsive nowrap w-full text-sm" style="width:100%">
                <thead>
                    <tr class="text-left text-gray-400 border-b border-gray-700">
                        <th>Foto</th>
                        <th>Nama Pegawai</th>
                        <th>Unit / Dept</th>
                        <th>Shift</th>
                        <th>Jam Masuk</th>
                        <th>Durasi Dinas</th>
                        <th class="text-center">Aksi (HRD)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>

    <script>
        let table;
        let reloadInterval;

        $(document).ready(function() {
            $.get('api_monitoring.php?act=get_dep', function(res) {
                const data = JSON.parse(res);
                data.forEach(d => {
                    $('#dep').append(`<option value="${d.dep_id}">${d.nama}</option>`);
                });
            });

            table = $('#tabelLive').DataTable({
                responsive: true,
                language: { search: "Cari:", emptyTable: "Tidak ada pegawai yang sedang dinas saat ini.", zeroRecords: "Tidak ditemukan" },
                order: [[ 4, "desc" ]], 
                columns: [
                    { data: 'photo', orderable: false, width: "50px", render: function(data, type, row) {
                        let imgPath = "../../" + data;
                        return `<div class="h-10 w-10 rounded-full overflow-hidden border-2 border-blue-500 cursor-pointer" onclick="lihatFoto('${imgPath}', '${row.nama}')">
                                    <img src="${imgPath}" class="h-full w-full object-cover" onerror="this.src='https://ui-avatars.com/api/?name=${row.nama}&background=random'">
                                </div>`;
                    }},
                    { data: 'nama', render: (data, type, row) => `<div class="font-bold">${data}</div><div class="text-xs text-gray-400">${row.nik}</div>` },
                    { data: 'dep' },
                    { data: 'shift', render: function(data) {
                        return `<span class="bg-blue-900 text-blue-200 px-2 py-0.5 rounded text-xs">${data}</span>`;
                    }},
                    { data: 'jam_datang', className: 'text-green-400 font-mono font-bold' },
                    { data: 'durasi_live', className: 'text-yellow-400 font-mono' },
                    
                    // TOMBOL PULANGKAN
                    { data: null, orderable: false, className: "text-center", render: function(data, type, row) {
                        return `<button onclick="paksaPulang('${row.nik}', '${row.nama}', '${row.full_jam_datang}')" class="bg-red-600 hover:bg-red-500 text-white px-3 py-1 rounded text-xs font-bold border border-red-500 shadow-sm transition flex items-center gap-1 mx-auto">
                                    <i class="fa-solid fa-right-from-bracket"></i> Pulangkan
                                </button>`;
                    }},

                    { data: 'status', render: function(data) {
                        return data.includes('Terlambat') 
                            ? `<span class="text-red-400 font-bold">${data}</span>` 
                            : `<span class="text-green-500">${data}</span>`;
                    }}
                ]
            });

            loadTable();

            reloadInterval = setInterval(function() {
                table.ajax.reload(null, false);
            }, 30000);

            $('#dep').change(function() {
                loadTable();
            });
        });

        function loadTable() {
            const dep = $('#dep').val();
            table.ajax.url(`api_monitoring.php?act=get_live_data&dep=${dep}`).load();
        }

        function lihatFoto(url, nama) {
            Swal.fire({
                title: nama,
                imageUrl: url,
                imageHeight: 400,
                imageAlt: 'Foto Absen',
                background: '#1f2937',
                color: '#fff',
                confirmButtonColor: '#2563eb'
            });
        }
        
        // FUNGSI PAKSA PULANG DENGAN INFO DETAIL
        function paksaPulang(nik, nama, tgl_masuk) {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            const nowStr = now.toISOString().slice(0, 16);

            Swal.fire({
                title: 'Paksa Pulang Pegawai',
                html: `
                    <div class="text-left bg-gray-700 p-3 rounded mb-4 text-sm border border-gray-600">
                        <p class="text-gray-300">Pegawai: <b class="text-white">${nama}</b></p>
                        <p class="text-yellow-400 mt-2 font-mono">
                           <i class="fa-regular fa-clock"></i> Masuk: <b>${tgl_masuk}</b>
                        </p>
                        <p class="text-xs text-gray-400 mt-1 italic">*Perhatikan tanggal masuknya!</p>
                    </div>
                    <div class="text-left mb-1 text-xs text-gray-400 font-bold">Set Waktu Pulang:</div>
                    <input type="datetime-local" id="waktu_pulang" class="swal2-input w-full text-sm" value="${nowStr}" style="margin: 0;">
                `,
                icon: 'warning',
                background: '#1f2937',
                color: '#fff',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#374151',
                confirmButtonText: 'Proses Pulangkan',
                cancelButtonText: 'Batal',
                preConfirm: () => {
                    const val = document.getElementById('waktu_pulang').value;
                    if (!val) {
                        Swal.showValidationMessage('Waktu pulang harus diisi');
                    }
                    return val;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({title: 'Memproses...', didOpen: () => Swal.showLoading(), background: '#1f2937', color: '#fff'});
                    
                    $.post('api_monitoring.php?act=force_checkout', {
                        nik: nik,
                        waktu_pulang: result.value
                    }, function(res) {
                        const data = JSON.parse(res);
                        if(data.status === 'success') {
                            Swal.fire({
                                title: 'Berhasil', 
                                text: data.message, 
                                icon: 'success', 
                                background: '#1f2937', 
                                color: '#fff'
                            });
                            loadTable();
                        } else {
                            Swal.fire({
                                title: 'Gagal', 
                                text: data.message, 
                                icon: 'error', 
                                background: '#1f2937', 
                                color: '#fff'
                            });
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>