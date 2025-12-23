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
    <title>Manajemen Pegawai</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../js/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    
    <style>
        /* CSS Khusus Tab */
        .tab-btn.active { border-bottom: 2px solid #3b82f6; color: #3b82f6; font-weight: bold; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        /* DataTables Dark Mode Patch */
        .dataTables_wrapper .dataTables_length select, .dataTables_wrapper .dataTables_filter input {
            background-color: #1f2937; color: white; border: 1px solid #4b5563; padding: 4px; border-radius: 4px;
        }
        table.dataTable tbody tr { background-color: #1f2937; color: #d1d5db; }
        table.dataTable tbody tr:hover { background-color: #374151; cursor: pointer;}
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen p-4">

    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-blue-400">Data Kepegawaian</h1>
                <p class="text-xs text-gray-400">Kelola data pegawai RSIA Dian</p>
            </div>
            <div class="flex gap-2">
                <button onclick="bukaModal()" class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded font-bold flex items-center gap-2">
                    <span>+ Tambah Pegawai</span>
                </button>
                <a href="index.php" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded text-white">Kembali</a>
            </div>
        </div>

        <div class="bg-gray-800 p-4 rounded shadow border border-gray-700">
            <table id="tabelPegawai" class="w-full text-sm text-left text-gray-300">
                <thead class="text-xs uppercase bg-gray-700 text-gray-400">
                    <tr>
                        <th class="p-3">Foto</th>
                        <th class="p-3">NIK / Nama</th>
                        <th class="p-3">Jabatan</th>
                        <th class="p-3">Departemen</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div id="modalForm" class="fixed inset-0 bg-black/80 hidden z-50 overflow-y-auto">
        <div class="min-h-screen flex items-center justify-center p-4">
            <div class="bg-gray-800 w-full max-w-4xl rounded-lg shadow-2xl border border-gray-700">
                
                <div class="flex justify-between items-center p-4 border-b border-gray-700 bg-gray-900 rounded-t-lg">
                    <h3 class="text-lg font-bold text-white" id="modalTitle">Tambah Pegawai Baru</h3>
                    <button onclick="tutupModal()" class="text-gray-400 hover:text-white text-2xl">&times;</button>
                </div>

                <form id="formPegawai" class="p-0" novalidate>
                    <input type="hidden" name="id" id="id">
                    
                    <div class="flex border-b border-gray-700 bg-gray-800 sticky top-0 z-10">
                        <button type="button" onclick="switchTab('tab1')" id="btn-tab1" class="tab-btn active flex-1 py-3 text-center text-sm transition">1. Identitas</button>
                        <button type="button" onclick="switchTab('tab2')" id="btn-tab2" class="tab-btn flex-1 py-3 text-center text-sm transition">2. Kepegawaian</button>
                        <button type="button" onclick="switchTab('tab3')" id="btn-tab3" class="tab-btn flex-1 py-3 text-center text-sm transition">3. Payroll & Lainnya</button>
                    </div>

                    <div class="p-6 h-[60vh] overflow-y-auto">
                        
                        <div id="tab1" class="tab-content active space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">NIK (Nomor Induk Pegawai)</label>
                                    <div class="flex gap-2">
                                        <input type="text" name="nik" id="nik" class="flex-1 bg-gray-900 border border-gray-600 rounded p-2 text-white font-mono" required placeholder="Contoh: 2024.001">
                                        <button type="button" onclick="genNIK()" class="bg-blue-600 px-3 py-1 rounded text-xs font-bold hover:bg-blue-500" title="Generate Otomatis">AUTO</button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">No. KTP (Wajib Unik)</label>
                                    <input type="text" name="no_ktp" id="no_ktp" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" required minlength="16" maxlength="16" placeholder="16 Digit NIK KTP">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs text-gray-400 mb-1">Nama Lengkap</label>
                                    <input type="text" name="nama" id="nama" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" required>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Jenis Kelamin</label>
                                    <select name="jk" id="jk" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white">
                                        <option value="L">Laki-Laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Tempat Lahir</label>
                                    <input type="text" name="tmp_lahir" id="tmp_lahir" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Tanggal Lahir</label>
                                    <input type="date" name="tgl_lahir" id="tgl_lahir" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Kota Tinggal</label>
                                    <input type="text" name="kota" id="kota" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs text-gray-400 mb-1">Alamat Lengkap</label>
                                    <textarea name="alamat" id="alamat" rows="2" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Foto Pegawai</label>
                                    <input type="file" name="photo" id="photo" class="w-full text-xs text-gray-400 bg-gray-900 border border-gray-600 rounded p-2">
                                </div>
                            </div>
                        </div>

                        <div id="tab2" class="tab-content space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Departemen</label>
                                    <select name="departemen" id="departemen" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white"></select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Bidang</label>
                                    <select name="bidang" id="bidang" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white"></select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Jabatan (Ketik Manual)</label>
                                    <input type="text" name="jbtn" id="jbtn" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Jenjang Jabatan</label>
                                    <select name="jnj_jabatan" id="jnj_jabatan" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white"></select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Status Aktif</label>
                                    <select name="stts_aktif" id="stts_aktif" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white bg-blue-900/50">
                                        <option value="AKTIF">AKTIF</option>
                                        <option value="CUTI">CUTI</option>
                                        <option value="KELUAR">KELUAR</option>
                                        <option value="TENAGA LUAR">TENAGA LUAR</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Mulai Kerja</label>
                                    <input type="date" name="mulai_kerja" id="mulai_kerja" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Mulai Kontrak</label>
                                    <input type="date" name="mulai_kontrak" id="mulai_kontrak" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Masa Kerja</label>
                                    <select name="ms_kerja" id="ms_kerja" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white">
                                        <option value="< 1 Tahun">< 1 Tahun</option>
                                        <option value="1 - 2 Tahun">1 - 2 Tahun</option>
                                        <option value="> 2 Tahun">> 2 Tahun</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Pendidikan</label>
                                    <select name="pendidikan" id="pendidikan" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white"></select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Kelompok Jabatan</label>
                                    <select name="kode_kelompok" id="kode_kelompok" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white"></select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Resiko Kerja</label>
                                    <select name="kode_resiko" id="kode_resiko" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white"></select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Index Emergency</label>
                                    <select name="kode_emergency" id="kode_emergency" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white"></select>
                                </div>
                            </div>
                        </div>

                        <div id="tab3" class="tab-content space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Gaji Pokok</label>
                                    <input type="number" name="gapok" id="gapok" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" value="0">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Status WP</label>
                                    <select name="stts_wp" id="stts_wp" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white"></select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Status Kerja</label>
                                    <select name="stts_kerja" id="stts_kerja" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white"></select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">NPWP</label>
                                    <input type="text" name="npwp" id="npwp" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Bank (BPD)</label>
                                    <select name="bpd" id="bpd" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white"></select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">No Rekening</label>
                                    <input type="text" name="rekening" id="rekening" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Index Ins</label>
                                    <input type="text" name="indexins" id="indexins" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Pengurang</label>
                                    <input type="number" name="pengurang" id="pengurang" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" value="0">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Wajib Masuk (Hari)</label>
                                    <input type="number" name="wajibmasuk" id="wajibmasuk" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" value="0">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Cuti Diambil</label>
                                    <input type="number" name="cuti_diambil" id="cuti_diambil" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" value="0">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Indek</label>
                                    <input type="number" name="indek" id="indek" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" value="0">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">Dankes</label>
                                    <input type="number" name="dankes" id="dankes" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" value="0">
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="p-4 border-t border-gray-700 bg-gray-900 rounded-b-lg flex justify-end gap-2">
                        <button type="button" onclick="tutupModal()" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded text-white font-bold">Batal</button>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 px-6 py-2 rounded text-white font-bold">SIMPAN DATA</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
    let table;

    $(document).ready(function() {
        loadDropdowns();
        
        table = $('#tabelPegawai').DataTable({
            ajax: 'api_pegawai.php?act=list',
            columns: [
                { data: 'photo_url', render: function(data) {
                    return `<img src="${data}" class="w-10 h-10 rounded-full object-cover border border-gray-600" onerror="this.src='https://ui-avatars.com/api/?background=random&name=User'">`;
                }},
                { data: null, render: function(data, type, row) {
                    return `<div><div class="font-bold text-white">${row.nama}</div><div class="text-xs font-mono text-gray-400">${row.nik}</div></div>`;
                }},
                { data: 'jbtn' },
                { data: 'departemen' },
                { data: 'stts_aktif', render: function(data) {
                    return data === 'AKTIF' 
                        ? `<span class="bg-green-900 text-green-200 px-2 py-0.5 rounded text-xs">${data}</span>` 
                        : `<span class="bg-red-900 text-red-200 px-2 py-0.5 rounded text-xs">${data}</span>`;
                }},
                { data: null, render: function(data, type, row) {
                    return `<button onclick="edit('${row.id}')" class="text-blue-400 hover:underline mr-2">Edit</button>
                            <button onclick="hapus('${row.id}', '${row.nama}')" class="text-red-400 hover:underline">Hapus</button>`;
                }}
            ]
        });
    });

    // --- LOGIC DROPDOWN ---
    function loadDropdowns() {
        $.get('api_pegawai.php?act=get_options', function(res) {
            fillSelect('departemen', res.departemen, 'id', 'nama');
            fillSelect('jnj_jabatan', res.jenjang, 'id', 'nama');
            fillSelect('kode_kelompok', res.kelompok, 'id', 'nama');
            fillSelect('kode_resiko', res.resiko, 'id', 'nama');
            fillSelect('kode_emergency', res.emergency, 'id', 'nama');
            fillSelect('pendidikan', res.pendidikan, 'id', 'nama');
            fillSelect('stts_wp', res.stts_wp, 'id', 'nama');
            fillSelect('stts_kerja', res.stts_kerja, 'id', 'nama');
            fillSelect('bidang', res.bidang, 'id', 'nama');
            fillSelect('bpd', res.bank, 'id', 'nama');
        });
    }

    function fillSelect(id, data, valKey, textKey) {
        let el = document.getElementById(id);
        el.innerHTML = '<option value="">- Pilih -</option>';
        data.forEach(item => {
            el.innerHTML += `<option value="${item[valKey]}">${item[textKey]}</option>`;
        });
    }

    // --- TAB SWITCHER ---
    function switchTab(tabId) {
        $('.tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
        $('.tab-btn').removeClass('active');
        $('#btn-' + tabId).addClass('active');
    }

    // --- MODAL ACTION ---
    function bukaModal() {
        $('#formPegawai')[0].reset();
        $('#id').val('');
        $('#modalTitle').text('Tambah Pegawai Baru');
        $('#nik').prop('readonly', false);
        $('#modalForm').removeClass('hidden');
        switchTab('tab1');
    }

    function tutupModal() {
        $('#modalForm').addClass('hidden');
    }

    function genNIK() {
        $.get('api_pegawai.php?act=gen_nik', function(res) {
            $('#nik').val(res.nik);
        });
    }

    // --- CRUD ---
    // --- VALIDASI & SUBMIT CERDAS ---
    
    // Hapus merah-merah saat user mulai mengetik/memilih
    $('#formPegawai input, #formPegawai select, #formPegawai textarea').on('input change', function() {
        $(this).removeClass('border-red-500 ring-2 ring-red-500');
    });

    $('#formPegawai').on('submit', function(e) {
        e.preventDefault();
        
        let isValid = true;
        let errorList = [];
        let firstErrorInput = null;

        // Loop semua elemen yang punya atribut 'required'
        $(this).find('[required]').each(function() {
            let val = $(this).val();
            
            // Cek jika kosong
            if (!val || val.trim() === '') {
                isValid = false;
                
                // 1. Beri Visual Merah
                $(this).addClass('border-red-500 ring-2 ring-red-500');
                
                // 2. Ambil Nama Label untuk pesan error
                // Mencari label terdekat (prev sibling atau parent)
                let label = $(this).closest('div').find('label').text().replace('*', '').trim();
                if(!label) label = $(this).attr('name'); // Fallback ke nama atribut
                
                errorList.push(label);

                // Simpan input error pertama untuk fokus nanti
                if (!firstErrorInput) firstErrorInput = $(this);
            }
        });

        if (!isValid) {
            // --- LOGIKA PINDAH TAB OTOMATIS ---
            // Cari input error pertama ada di tab mana
            let parentTab = firstErrorInput.closest('.tab-content').attr('id');
            
            // Pindah ke tab tersebut
            switchTab(parentTab);

            // Fokus ke input
            setTimeout(() => { firstErrorInput.focus(); }, 100);

            // Tampilkan Pesan Error Rinci
            let errorHtml = '<ul class="text-left text-sm mt-2 list-disc pl-5 text-red-400 space-y-1">';
            errorList.forEach(err => {
                errorHtml += `<li>${err} wajib diisi</li>`;
            });
            errorHtml += '</ul>';

            Swal.fire({
                icon: 'warning',
                title: 'Data Belum Lengkap!',
                html: `Mohon lengkapi kolom berikut sebelum menyimpan:<br>${errorHtml}`,
                confirmButtonColor: '#3b82f6'
            });

            return; // STOP PROSES DISINI
        }

        // --- JIKA LOLOS VALIDASI, LANJUT SIMPAN ---
        let formData = new FormData(this);
        let btnSimpan = $(this).find('button[type="submit"]');
        let btnText = btnSimpan.text();
        
        // Loading State
        btnSimpan.prop('disabled', true).text('Menyimpan...');

        $.ajax({
            url: 'api_pegawai.php?act=save',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                btnSimpan.prop('disabled', false).text(btnText);
                if(res.status === 'success') {
                    Swal.fire({
                        title: 'Berhasil', 
                        text: res.message, 
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    tutupModal();
                    table.ajax.reload();
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            error: function(xhr) {
                btnSimpan.prop('disabled', false).text(btnText);
                Swal.fire('Error', 'Terjadi kesalahan server', 'error');
            }
        });
    });

    function edit(id) {
        $.get('api_pegawai.php?act=detail&id=' + id, function(res) {
            if(res.status === 'success') {
                bukaModal();
                $('#modalTitle').text('Edit Data Pegawai');
                let d = res.data;
                
                // Auto Fill Inputs
                for(let key in d) {
                    let el = document.getElementsByName(key)[0];
                    if(el) el.value = d[key];
                }
                
                // Handle Readonly NIK saat edit (opsional, biasanya NIK jangan diubah sembarangan)
                // $('#nik').prop('readonly', true); 
            }
        });
    }

    function hapus(id, nama) {
        Swal.fire({
            title: 'Hapus Pegawai?',
            text: `Data ${nama} akan dihapus permanen.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            confirmButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('api_pegawai.php?act=delete', {id: id}, function(res) {
                    if(res.status === 'success') {
                        Swal.fire('Terhapus', res.message, 'success');
                        table.ajax.reload();
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                });
            }
        });
    }
</script>
</body>
</html>