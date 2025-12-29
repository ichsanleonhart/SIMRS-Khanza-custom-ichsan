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
        
        .select2-container--default .select2-selection--single {
            background-color: #111827; border: 1px solid #4b5563; border-radius: 0.5rem; height: 42px; color: white;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: white; line-height: 42px; padding-left: 12px;
        }
        .select2-dropdown { background-color: #1f2937; border: 1px solid #4b5563; color: white; }
        .select2-search__field { background-color: #374151; color: white; }
        .select2-results__option--highlighted.select2-results__option--selectable { background-color: #2563eb; }
        .select2-results__option { color: white; }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen font-sans">

    <nav class="bg-gray-800 border-b border-gray-700 p-4 sticky top-0 z-20 shadow-md">
        <div class="max-w-5xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-3">
                <img id="navPhoto" src="" class="h-10 w-10 rounded-full object-cover border border-gray-600 bg-gray-700">
                <div>
                    <h1 class="font-bold text-lg text-blue-400">Portal Pegawai</h1>
                    <p class="text-xs text-gray-400" id="navName">Memuat...</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="bukaModalProfil()" class="bg-gray-700 hover:bg-gray-600 text-gray-300 px-3 py-1 rounded text-xs border border-gray-600 transition flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Data Diri
                </button>
                <a href="logout.php" class="bg-red-600/20 hover:bg-red-600 text-red-400 hover:text-white px-3 py-1 rounded text-xs border border-red-600/50 transition flex items-center">Keluar</a>
            </div>
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

            <div class="flex flex-col gap-3">
                <button onclick="bukaModal()" class="flex-1 bg-gray-800 hover:bg-gray-700 p-4 rounded-xl border border-gray-700 shadow flex items-center justify-between group transition">
                    <div>
                        <h2 class="text-green-400 font-bold group-hover:text-green-300">Ajukan Cuti Baru</h2>
                        <p class="text-xs text-gray-500">Formulir digital</p>
                    </div>
                    <div class="bg-green-900/30 p-2 rounded-full text-green-400 group-hover:bg-green-600 group-hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </div>
                </button>

                <button onclick="bukaModalJadwal()" class="flex-1 bg-gray-800 hover:bg-gray-700 p-4 rounded-xl border border-gray-700 shadow flex items-center justify-between group transition">
                    <div>
                        <h2 class="text-purple-400 font-bold group-hover:text-purple-300">Jadwal & Log Absen</h2>
                        <p class="text-xs text-gray-500">Cek shift dan jam presensi</p>
                    </div>
                    <div class="bg-purple-900/30 p-2 rounded-full text-purple-400 group-hover:bg-purple-600 group-hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                </button>
            </div>
        </div>

        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-700 bg-gray-800/50">
                <h3 class="font-bold text-gray-200">Riwayat Pengajuan Cuti</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-400">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Jenis & Alasan</th>
                            <th class="px-4 py-3">Durasi</th>
                            <th class="px-4 py-3">Status Atasan</th>
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
                
                <div class="bg-blue-900/40 border border-blue-600 rounded p-3 text-center mb-2">
                    <p class="text-xs text-blue-200">Sisa Cuti Tersedia:</p>
                    <p class="text-xl font-bold text-white"><span id="sisaCutiModal">...</span> Hari</p>
                </div>

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
                    <div class="bg-gray-700/50 p-2 rounded text-center text-xs text-gray-300 border border-gray-600">
                        Durasi Pengajuan: <span id="totalHari" class="font-bold text-white">0</span> Hari
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

    <div id="modalJadwal" class="fixed inset-0 bg-black/90 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-gray-800 w-full max-w-5xl rounded-2xl shadow-2xl border border-gray-700 flex flex-col max-h-[95vh]">
            <div class="p-4 border-b border-gray-700 flex justify-between items-center bg-gray-900 rounded-t-2xl">
                <h3 class="text-lg font-bold text-purple-400">Jadwal & Log Absensi</h3>
                <button onclick="tutupModalJadwal()" class="text-gray-400 hover:text-white text-2xl">&times;</button>
            </div>
            
            <div class="p-4 bg-gray-800 border-b border-gray-700 flex gap-2">
                <select id="blnJadwal" class="bg-gray-900 border border-gray-600 rounded p-2 text-sm text-white focus:border-purple-500">
                    <?php 
                    $bulan = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
                    for($i=1;$i<=12;$i++){ 
                        $sel = ($i == date('n')) ? 'selected' : '';
                        echo "<option value='$i' $sel>".$bulan[$i-1]."</option>"; 
                    } 
                    ?>
                </select>
                <select id="thnJadwal" class="bg-gray-900 border border-gray-600 rounded p-2 text-sm text-white focus:border-purple-500">
                    <?php 
                    $thn = date('Y');
                    for($i=$thn-1; $i<=$thn+1; $i++){
                        $sel = ($i == $thn) ? 'selected' : '';
                        echo "<option value='$i' $sel>$i</option>";
                    }
                    ?>
                </select>
                <button onclick="loadJadwal()" class="bg-purple-600 hover:bg-purple-500 text-white px-4 rounded text-sm font-bold shadow">Lihat</button>
            </div>

            <div class="p-4 overflow-y-auto modal-scroll bg-gray-900">
                <div id="gridJadwal" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-2">
                    </div>
            </div>
        </div>
    </div>

    <div id="modalProfil" class="fixed inset-0 bg-black/90 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-gray-800 w-full max-w-2xl rounded-2xl shadow-2xl border border-gray-700 flex flex-col max-h-[95vh]">
            <div class="p-4 border-b border-gray-700 flex justify-between items-center bg-gray-900 rounded-t-2xl">
                <h3 class="text-lg font-bold text-blue-400">Update Data Diri</h3>
                <button onclick="tutupModalProfil()" class="text-gray-400 hover:text-white text-2xl">&times;</button>
            </div>
            
            <div class="p-6 overflow-y-auto modal-scroll space-y-4">
                <form id="formProfil" enctype="multipart/form-data">
                    <div class="flex gap-6 flex-col md:flex-row">
                        <div class="w-full md:w-1/3 flex flex-col items-center gap-3">
                            <img id="previewFoto" src="" class="w-32 h-32 rounded-lg object-cover border-2 border-gray-600 bg-gray-700">
                            <label class="bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded text-xs cursor-pointer border border-gray-500">
                                Ganti Foto
                                <input type="file" name="photo" class="hidden" accept="image/*" onchange="previewImage(this)">
                            </label>
                            <p class="text-[10px] text-gray-500 text-center">Format JPG/PNG. Maks 2MB.</p>
                        </div>

                        <div class="w-full md:w-2/3 space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-400">Tempat Lahir</label>
                                    <input type="text" name="tmp_lahir" id="tmp_lahir" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400">Tgl Lahir</label>
                                    <input type="date" name="tgl_lahir" id="tgl_lahir" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs text-gray-400">No. KTP</label>
                                <input type="text" name="no_ktp" id="no_ktp" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white">
                            </div>

                            <div>
                                <label class="block text-xs text-gray-400">No. Telepon / WA</label>
                                <input type="text" name="no_telp" id="no_telp" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white">
                            </div>

                            <div>
                                <label class="block text-xs text-gray-400">Alamat Lengkap</label>
                                <textarea name="alamat" id="alamat" rows="2" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white"></textarea>
                            </div>

                            <div>
                                <label class="block text-xs text-gray-400">Kota/Kabupaten</label>
                                <input type="text" name="kota" id="kota" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white">
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="p-4 border-t border-gray-700 bg-gray-900 rounded-b-2xl flex justify-end gap-3">
                <button onclick="tutupModalProfil()" class="px-4 py-2 text-sm text-gray-300 hover:text-white">Batal</button>
                <button onclick="simpanProfil()" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2 rounded-lg font-bold text-sm shadow-lg">Simpan Perubahan</button>
            </div>
        </div>
    </div>

<script>
    let SISA_CUTI_GLOBAL = 0;

    $(document).ready(function() {
        loadDashboard();
        loadAtasan();

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
                $('#navPhoto').attr('src', res.photo_url); // Update foto navbar
                
                SISA_CUTI_GLOBAL = res.sisa_cuti;

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

    // --- MODAL CUTI ---
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
        $('#sisaCutiModal').text(SISA_CUTI_GLOBAL);
        const today = new Date().toISOString().split('T')[0];
        $('#tgl1').val(today); $('#tgl2').val(today);

        $('#selectAtasan').select2({
            dropdownParent: $('#modalCuti'),
            width: '100%',
            placeholder: "Ketik nama atasan...",
            allowClear: true,
            language: { noResults: function() { return "Tidak ditemukan"; } }
        });
    }

    function tutupModal() { $('#modalCuti').addClass('hidden'); }

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

    // --- MODAL JADWAL & LOG ABSEN ---
    function bukaModalJadwal() {
        $('#modalJadwal').removeClass('hidden');
        loadJadwal();
    }
    
    function tutupModalJadwal() { $('#modalJadwal').addClass('hidden'); }

    function loadJadwal() {
        const bln = $('#blnJadwal').val();
        const thn = $('#thnJadwal').val();
        
        $('#gridJadwal').html('<div class="col-span-full text-center text-gray-500 py-10">Memuat jadwal...</div>');

        $.post('api.php?act=get_jadwal', {bulan: bln, tahun: thn})
         .done(function(res) {
            let html = '';
            const data = (typeof res === 'string') ? JSON.parse(res) : res;
            
            if(data.length === 0) {
                $('#gridJadwal').html('<div class="col-span-full text-center text-red-400 py-10">Jadwal belum dibuat.</div>');
                return;
            }

            data.forEach(d => {
                let borderClass = d.is_today ? 'ring-2 ring-blue-500 bg-gray-800 transform scale-105 z-10' : 'bg-gray-800 border-gray-700 hover:border-gray-500';
                
                // LOGIC LOG ABSEN (Kuning/Putih)
                let logInfo = '';
                if(d.has_log) {
                    logInfo = `
                        <div class="mt-2 text-[10px] bg-yellow-900/30 border border-yellow-700/50 rounded px-1 py-0.5">
                            <div class="flex justify-between text-yellow-200">
                                <span>IN: ${d.real_in}</span>
                                <span>OUT: ${d.real_out}</span>
                            </div>
                        </div>
                    `;
                }

                html += `
                <div class="${borderClass} p-2 rounded border flex flex-col justify-between min-h-[100px] relative overflow-hidden transition shadow-lg">
                    <div class="flex justify-between items-start">
                        <span class="text-xs text-gray-500 font-bold uppercase">${d.hari}</span>
                        <span class="text-lg font-bold text-white">${d.tanggal}</span>
                    </div>
                    
                    <div class="mt-1">
                        <span class="${d.color} px-2 py-0.5 rounded text-[10px] font-bold border border-opacity-50 block text-center truncate mb-1">
                            ${d.shift}
                        </span>
                        <div class="text-[9px] text-center text-gray-400">${d.jam_shift}</div>
                        ${logInfo} </div>
                </div>`;
            });
            $('#gridJadwal').html(html);
         })
         .fail(function() {
             $('#gridJadwal').html('<div class="col-span-full text-center text-red-500 py-10">Gagal memuat data.</div>');
         });
    }

    // --- MODAL PROFIL (FITUR BARU) ---
    function bukaModalProfil() {
        $('#modalProfil').removeClass('hidden');
        // Load data dari API
        $.get('api.php?act=get_profile', function(data) {
            const d = (typeof data === 'string') ? JSON.parse(data) : data;
            $('#tmp_lahir').val(d.tmp_lahir);
            $('#tgl_lahir').val(d.tgl_lahir);
            $('#alamat').val(d.alamat);
            $('#kota').val(d.kota);
            $('#no_ktp').val(d.no_ktp);
            $('#no_telp').val(d.no_telp);
            if(d.photo_url) $('#previewFoto').attr('src', d.photo_url);
        });
    }

    function tutupModalProfil() { $('#modalProfil').addClass('hidden'); }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#previewFoto').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function simpanProfil() {
        let formData = new FormData(document.getElementById('formProfil'));
        
        Swal.fire({
            title: 'Simpan Perubahan?',
            text: 'Pastikan data yang Anda input benar.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Simpan'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api.php?act=update_profile',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        const data = (typeof res === 'string') ? JSON.parse(res) : res;
                        if(data.status === 'success') {
                            Swal.fire('Berhasil', data.message, 'success');
                            tutupModalProfil();
                            loadDashboard(); // Refresh foto di navbar
                        } else {
                            Swal.fire('Gagal', data.message, 'error');
                        }
                    }
                });
            }
        });
    }
</script>
</body>
</html>