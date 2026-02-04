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
    <title>Input Presensi Manual</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../js/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        /* Select2 Dark Theme Fix */
        .select2-container--default .select2-selection--single { background-color: #1f2937; border-color: #4b5563; height: 42px; color: white; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { color: white; line-height: 40px; }
        .select2-dropdown { background-color: #1f2937; border-color: #4b5563; color: white; }
        .select2-results__option--highlighted { background-color: #2563eb !important; }
        .select2-search__field { background-color: #374151 !important; color: white !important; }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen p-4">

<div class="max-w-3xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-blue-400">Input Presensi Manual</h1>
        <a href="index.php" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded text-sm">Kembali</a>
    </div>

    <div class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700">
        <form id="formManual">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="block text-gray-400 text-sm mb-2">Pilih Pegawai</label>
                    <select id="pegawai" name="nik" class="w-full bg-gray-900" required>
                        <option value="">Cari Nama / NIK...</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Shift</label>
                <select id="shift" name="shift" class="w-full bg-gray-900 border border-gray-600 rounded p-2.5 text-white h-[42px]" required>
                    <option value="">- Pilih Pegawai Dulu -</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4 border-t border-gray-700 pt-4">
                <div>
                    <label class="block text-green-400 text-sm mb-1 font-bold">Tanggal Masuk</label>
                    <input type="date" name="tgl_masuk" id="tgl_masuk" value="<?= date('Y-m-d') ?>" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" required>
                </div>
                <div>
                    <label class="block text-green-400 text-sm mb-1 font-bold">Jam Masuk</label>
                    <input type="time" name="jam_masuk" value="07:00" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" required>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4 pb-4 border-b border-gray-700">
                <div>
                    <label class="block text-red-400 text-sm mb-1 font-bold">Tanggal Pulang</label>
                    <input type="date" name="tgl_pulang" id="tgl_pulang" value="<?= date('Y-m-d') ?>" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" required>
                </div>
                <div>
                    <label class="block text-red-400 text-sm mb-1 font-bold">Jam Pulang</label>
                    <input type="time" name="jam_pulang" value="14:00" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" required>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-gray-400 text-sm mb-2">Catatan / Keterangan</label>
                <textarea name="catatan" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" rows="2" placeholder="Contoh: Lupa absen, Mesin Error, Shift Malam Lintas Hari" required></textarea>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded transition">
                SIMPAN DATA PRESENSI
            </button>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Init Select2 Pegawai
    $('#pegawai').select2({
        ajax: {
            url: 'api_manual.php?act=cari_pegawai',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) {
                return { results: data };
            }
        }
    });

    // Helper: Jika Tanggal Masuk berubah, Tanggal Pulang otomatis mengikuti (Default behavior)
    // HRD tetap bisa merubah tanggal pulang secara manual jika shift malam
    $('#tgl_masuk').on('change', function() {
        $('#tgl_pulang').val($(this).val());
    });

    // Load Shift saat Pegawai dipilih
    $('#pegawai').on('select2:select', function (e) {
        let nik = e.params.data.id;
        $.get('api_manual.php?act=get_shifts&nik=' + nik, function(res) {
            let data = JSON.parse(res);
            $('#shift').empty();
            data.forEach(s => {
                $('#shift').append(`<option value="${s.shift}">${s.shift} (${s.jam_masuk}-${s.jam_pulang})</option>`);
            });
        });
    });

    // Submit
    $('#formManual').on('submit', function(e) {
        e.preventDefault();
        
        // Validasi Pre-Submit untuk UX
        let tglMasuk = $('#tgl_masuk').val();
        let tglPulang = $('#tgl_pulang').val();
        
        if (tglPulang < tglMasuk) {
            Swal.fire('Error Tanggal', 'Tanggal Pulang tidak boleh lebih lampau dari Tanggal Masuk', 'error');
            return;
        }

        Swal.fire({
            title: 'Simpan Presensi?',
            text: "Pastikan data jam dan tanggal (terutama lintas hari) sudah benar.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Simpan'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('api_manual.php?act=save', $(this).serialize(), function(res) {
                    let data = JSON.parse(res);
                    if(data.status === 'success') {
                        Swal.fire('Berhasil', 'Data tersimpan!', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Gagal', data.message, 'error');
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>