<?php
session_start();
if (isset($_SESSION['pegawai_login'])) { header("Location: index.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pegawai - Pengajuan Cuti</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/jquery.min.js"></script>
</head>
<body class="bg-gray-900 flex items-center justify-center h-screen px-4">

    <div class="w-full max-w-sm bg-gray-800 rounded-xl shadow-2xl border border-gray-700 p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-white">Portal Pegawai</h1>
            <p class="text-gray-400 text-sm mt-1">Layanan Mandiri & Pengajuan Cuti</p>
        </div>

        <form id="formLogin">
            <div class="mb-4">
                <label class="block text-gray-400 text-xs font-bold mb-2">USERNAME / NIK</label>
                <input type="text" name="username" class="w-full bg-gray-900 text-white border border-gray-600 rounded p-3 focus:border-blue-500 focus:outline-none" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-400 text-xs font-bold mb-2">PASSWORD</label>
                <input type="password" name="password" class="w-full bg-gray-900 text-white border border-gray-600 rounded p-3 focus:border-blue-500 focus:outline-none" required>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded transition shadow-lg">MASUK</button>
        </form>
    </div>

<script>
    $('#formLogin').on('submit', function(e) {
        e.preventDefault();
        $.post('api.php?act=login', $(this).serialize(), function(res) {
            if(res.status === 'success') {
                window.location.href = 'index.php';
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        }, 'json');
    });
</script>
</body>
</html>