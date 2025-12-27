<?php
session_start();
if (!isset($_SESSION['pegawai_login'])) { header("Location: login.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pegawai</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../js/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        .modal-scroll::-webkit-scrollbar { width: 8px; }
        .modal-scroll::-webkit-scrollbar-track { background: #1f2937; }
        .modal-scroll::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 4px; }
        
        /* Custom CSS untuk Select2 Dark Mode (Agar serasi dengan tema) */
        .select2-container--default .select2-selection--single {
            background-color: #111827; /* bg-gray-900 */
            border: 1px solid #4b5563; /* border-gray-600 */
            border-radius: 0.5rem;
            height: 42px;
            color: white;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: white;
            line-height: 42px;
            padding-left: 12px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }
        .select2-dropdown {
            background-color: #1f2937;
            border: 1px solid #4b5563;
            color: white;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field {
            background-color: #374151;
            color: white;
            border: 1px solid #4b5563;
        }
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: #2563eb; /* blue-600 */
            color: white;
        }
        .select2-results__option { color: white; }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen font-sans">

    <nav class="bg-gray-800 border-b border-gray-700 p-4 sticky top-0 z-20 shadow-md">
        <div class="max-w-5xl mx-auto flex justify-between items-center">
            <div>
                <h1 class="font-bold text-lg text-blue-400">Portal Pegawai</h1>
                <p class="text-xs text-gray-400" id="navName">Memuat...</p>
            </div>
            <a href="logout.php" class="bg-red-600/20 hover:bg-red-600 text-red-400 hover:text-white px-3 py-1 rounded text-xs border border-red-600/50 transition">Keluar</a>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto p-4 space-y-6">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gradient-to-r from-blue-900 to-blue-800 p-5 rounded-xl border border-blue-700 shadow-lg relative overflow-hidden">
                <div class="relative z-10">
                    <h2 class="text-blue-200 text-sm font-bold uppercase mb-1">Sisa Cuti Tahunan</h2>
                    <div class="text-4xl font-bold text-white mb-1"><span id="valSisa">...</span> <span class="text-lg font-normal text-blue-300">Hari</span></div>
                    <p class="text-xs text-blue-300">Dari total jatah 12 hari</p>
                </div>
                <div class="absolute right-[-20px] bottom-[-20px] opacity-20 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-32 w-32" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>

            <div class="bg-gray-800 p-5 rounded-xl border border-gray-700 shadow flex items-center justify-between">
                <div>
                    <h2 class="text-gray-400 text-sm font-bold uppercase">Ajukan Cuti Baru</h2>
                    <p class="text-xs text-gray-500 mt-1">Isi formulir pengajuan secara digital</p>
                </div>
                <button onclick="bukaModal()" class="bg-green-600 hover:bg-green-500 text-white px-6 py-3 rounded-lg font-bold shadow-lg transition flex items-center gap-2">
                    <span>+ Buat Pengajuan</span>
                </button>
            </div>
        </div>

        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-700 bg-gray-800/50">
                <h3 class="font-bold text-gray-200">Riwayat Pengajuan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-400">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3">Tanggal Cuti</th>
                            <th class="px-4 py-3">Jenis & Alasan</th>
                            <th class="px-4 py-3">Durasi</th>
                            <th class="px-4 py-3">Status Atasan (PJ)</th>
                            <th class="px-4 py-3">Status HRD</th>
                        </tr>
                    </thead>
                    <tbody id="historyList">
                        <tr><td colspan="5" class="px-4 py-4 text-center">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div id="modalCuti" class="fixed inset-0 bg-black/80 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-gray-800 w-full max-w-lg rounded-2xl shadow-2xl border border-gray-700 flex flex-col max-h-[90vh]">
            <div class="p-5 border-b border-gray-700 flex justify-between items-center bg-gray-900 rounded-t-2xl">
                <h3 class="text-lg font-bold text-white">Form Pengajuan Cuti</h3>
                <button onclick="tutupModal()" class="text-gray-400 hover:text-white text-2xl">&times;</button>
            </div>
            
            <div class="p-6 overflow-y-auto modal-scroll space-y-4">
                <form id="formCuti">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Mulai Tanggal</label>
                            <input type="date" name="tanggal_awal" id="tgl1" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" required>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Sampai Tanggal</label>
                            <input type="date" name="tanggal_akhir" id="tgl2" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" required>
                        </div>
                    </div>
                    <div class="bg-blue-900/30 border border-blue-900 p-2 rounded text-center text-xs text-blue-300">
                        Total Cuti: <span id="totalHari" class="font-bold text-white">0</span> Hari
                    </div>

                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Jenis Cuti</label>
                        <select name="urgensi" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white">
                            <option value="Tahunan">Tahunan</option>
                            <option value="Sakit">Sakit</option>
                            <option value="Besar">Besar</option>
                            <option value="Melahirkan">Melahirkan</option>
                            <option value="Alasan Penting">Alasan Penting</option>
                            <option value="Keterangan Lainnya">Keterangan Lainnya</option>
                            <option value="Menikah">Menikah</option>
                            <option value="Keluarga Meninggal Dunia">Keluarga Meninggal Dunia</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Alamat Selama Cuti</label>
                        <input type="text" name="alamat" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" placeholder="Kota / Alamat Lengkap" required>
                    </div>

                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Keterangan / Alasan</label>
                        <textarea name="kepentingan" rows="2" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" placeholder="Contoh: Acara keluarga..." required></textarea>
                    </div>

                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Pilih Atasan Langsung (PJ)</label>
                        <select name="nik_pj" id="selectAtasan" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white text-sm" required style="width: 100%;">
                            <option value="">-- Cari Nama Atasan --</option>
                        </select>
                        <p class="text-[10px] text-gray-500 mt-1">*Ketik nama untuk mencari.</p>
                    </div>
                </form>
            </div>

            <div class="p-4 border-t border-gray-700 bg-gray-900 rounded-b-2xl flex justify-end gap-3">
                <button onclick="tutupModal()" class="px-4 py-2 text-sm text-gray-300 hover:text-white">Batal</button>
                <button onclick="kirimForm()" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2 rounded-lg font-bold text-sm shadow-lg">Kirim Pengajuan</button>
            </div>
        </div>
    </div>

<script>
    $(document).ready(function() {
        loadDashboard();
        loadAtasan();

        // Auto Hitung Hari
        $('#tgl1, #tgl2').on('change', function() {
            let d1 = new Date($('#tgl1').val());
            let d2 = new Date($('#tgl2').val());
            if(d1 && d2 && !isNaN(d1) && !isNaN(d2)) {
                let diffTime = Math.abs(d2 - d1);
                let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; 
                if(d2 < d1) diffDays = 0;
                $('#totalHari').text(diffDays);
            }
        });
    });

    function loadDashboard() {
        $.get('api.php?act=dashboard', function(res) {
            if(res.status === 'success') {
                $('#navName').text(res.nama + ' - ' + res.departemen);
                $('#valSisa').text(res.sisa_cuti);
                
                let html = '';
                if(res.history.length === 0) {
                    html = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada riwayat pengajuan.</td></tr>';
                } else {
                    res.history.forEach(item => {
                        html += `
                            <tr class="border-b border-gray-700 hover:bg-gray-700/30 transition">
                                <td class="px-4 py-3 font-mono text-xs">
                                    ${item.tanggal_awal} <br>s.d<br> ${item.tanggal_akhir}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-white text-xs">${item.urgensi}</div>
                                    <div class="text-[10px] text-gray-400 truncate max-w-[150px]">${item.kepentingan}</div>
                                </td>
                                <td class="px-4 py-3 font-bold text-white text-xs text-center">${item.jumlah} Hari</td>
                                <td class="px-4 py-3">${renderBadge(item.status)} <br> <span class="text-[9px] text-gray-500">${item.nama_pj || '-'}</span></td>
                                <td class="px-4 py-3">${renderBadge(item.status_persetujuan_HRD)}</td>
                            </tr>
                        `;
                    });
                }
                $('#historyList').html(html);
            }
        }, 'json');
    }

    function renderBadge(status) {
        if(status === 'Disetujui') return `<span class="bg-green-900 text-green-300 px-2 py-0.5 rounded text-[10px] font-bold border border-green-700">DISETUJUI</span>`;
        if(status === 'Ditolak') return `<span class="bg-red-900 text-red-300 px-2 py-0.5 rounded text-[10px] font-bold border border-red-700">DITOLAK</span>`;
        return `<span class="bg-yellow-900 text-yellow-300 px-2 py-0.5 rounded text-[10px] font-bold border border-yellow-700 animate-pulse">PROSES</span>`;
    }

    function loadAtasan() {
        $.get('api.php?act=get_atasan', function(res) {
            let html = '<option value="">-- Pilih Atasan --</option>';
            let lastGroup = '';
            
            res.forEach(item => {
                if(lastGroup !== item.group) {
                    if(lastGroup !== '') html += '</optgroup>';
                    html += `<optgroup label="${item.group}">`;
                    lastGroup = item.group;
                }
                html += `<option value="${item.nik}">${item.nama} (${item.jabatan})</option>`;
            });
            html += '</optgroup>';
            $('#selectAtasan').html(html);
        }, 'json');
    }

    function bukaModal() {
        $('#modalCuti').removeClass('hidden');
        $('#formCuti')[0].reset();
        $('#totalHari').text('0');
        // Default tanggal hari ini
        const today = new Date().toISOString().split('T')[0];
        $('#tgl1').val(today);
        $('#tgl2').val(today);

        // --- INIT SELECT2 SAAT MODAL DIBUKA ---
        // Kita init di sini agar tidak error width 0 karena modal hidden
        $('#selectAtasan').select2({
            dropdownParent: $('#modalCuti'), // PENTING: Agar search jalan di dalam modal
            width: '100%',
            placeholder: "Ketik nama atasan...",
            allowClear: true,
            language: {
                noResults: function() { return "Tidak ditemukan"; }
            }
        });
    }

    function tutupModal() {
        $('#modalCuti').addClass('hidden');
        // Destroy select2 agar bersih saat dibuka lagi (optional, tapi good practice)
        if ($('#selectAtasan').data('select2')) {
            $('#selectAtasan').select2('destroy');
        }
    }

    function kirimForm() {
        const form = $('#formCuti');
        if(!form[0].checkValidity()) {
            Swal.fire('Info', 'Mohon lengkapi semua kolom!', 'warning');
            return;
        }

        Swal.fire({
            title: 'Kirim Pengajuan?',
            text: 'Pastikan data dan atasan sudah benar.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Kirim',
            confirmButtonColor: '#2563eb'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('api.php?act=save', form.serialize(), function(res) {
                    if(res.status === 'success') {
                        tutupModal();
                        Swal.fire('Berhasil', 'Pengajuan cuti terkirim.', 'success');
                        loadDashboard();
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