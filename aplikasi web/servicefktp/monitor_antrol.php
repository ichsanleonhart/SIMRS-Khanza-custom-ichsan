<?php
// [2026-02-08] UPDATE: Auto-Refresh Midnight & Strict Filter BPJS
// File: monitor_antrol.php
// Fungsi: Monitoring kelengkapan bridging Antrean Online (Antrol) vs Registrasi RS.

require_once 'config.php';

$tgl1 = isset($_GET['tgl1']) ? $_GET['tgl1'] : date('Y-m-d');
$tgl2 = isset($_GET['tgl2']) ? $_GET['tgl2'] : date('Y-m-d');

// QUERY ANALISA KELENGKAPAN ANTROL
// [FIX] Gunakan filter BPJS yang konsisten dengan Worker (BPJ atau BPJ+Spasi)

$sql = "SELECT 
            rp.no_rawat, rp.no_reg, rp.tgl_registrasi, rp.jam_reg, 
            rp.no_rkm_medis, ps.nm_pasien, ps.no_peserta, 
            rp.kd_dokter, d.nm_dokter, 
            rp.kd_poli, p.nm_poli,
            rp.stts, rp.stts_daftar,
            
            -- Cek Task ID di tabel referensi local
            (SELECT waktu FROM referensi_mobilejkn_bpjs_taskid WHERE no_rawat = rp.no_rawat AND taskid = '0') as task0_waktu,
            (SELECT waktu FROM referensi_mobilejkn_bpjs_taskid WHERE no_rawat = rp.no_rawat AND taskid = '1') as task1_waktu,
            (SELECT waktu FROM referensi_mobilejkn_bpjs_taskid WHERE no_rawat = rp.no_rawat AND taskid = '2') as task2_waktu,
            
            -- Cek apakah sudah ada pemeriksaan (SOAP)
            (SELECT jam_rawat FROM pemeriksaan_ralan WHERE no_rawat = rp.no_rawat LIMIT 1) as jam_periksa

        FROM reg_periksa rp
        INNER JOIN pasien ps ON rp.no_rkm_medis = ps.no_rkm_medis
        INNER JOIN dokter d ON rp.kd_dokter = d.kd_dokter
        INNER JOIN poliklinik p ON rp.kd_poli = p.kd_poli
        
        WHERE (rp.kd_pj = 'BPJ' OR rp.kd_pj = 'BPJ')
        AND rp.tgl_registrasi BETWEEN :tgl1 AND :tgl2
        ORDER BY rp.tgl_registrasi DESC, rp.jam_reg DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['tgl1' => $tgl1, 'tgl2' => $tgl2]);
$data = $stmt->fetchAll();

function renderCheck($waktu, $labelSuccess = 'OK') {
    if (!empty($waktu)) {
        // Tampilkan jam saja biar ringkas
        $jam = date('H:i:s', strtotime($waktu));
        return '<span class="bg-green-900 text-green-300 px-2 py-1 rounded text-xs border border-green-700 font-mono">'.$jam.'</span>';
    } else {
        return '<span class="text-gray-600 font-bold">-</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Antrol - Bridging BPJS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.tailwind.min.css">
    <style>
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1f2937; }
        ::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 4px; }
        
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter, 
        .dataTables_wrapper .dataTables_info, 
        .dataTables_wrapper .dataTables_paginate { color: #9ca3af !important; }
        
        .dataTables_wrapper .dataTables_length select, 
        .dataTables_wrapper .dataTables_filter input {
            background-color: #374151; color: white; border: 1px solid #4b5563; padding: 0.25rem; border-radius: 0.25rem;
        }
        table.dataTable tbody tr { background-color: #111827 !important; color: #e5e7eb; border-bottom: 1px solid #374151; }
    </style>
</head>
<body class="bg-gray-900 text-gray-200 min-h-screen font-sans p-6">

    <div class="container mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-wide border-l-4 border-blue-500 pl-4">
                    AUDIT <span class="text-blue-400">ANTREAN ONLINE</span>
                </h1>
                <p class="text-xs text-gray-400 mt-1">Evaluasi kedisiplinan bridging Antrol petugas registrasi</p>
                <div id="refreshStatus" class="text-[10px] text-gray-500 mt-1">Auto-check date active...</div>
            </div>
            
            <div class="flex gap-2 items-center bg-gray-800 p-2 rounded-lg border border-gray-700">
                <label class="text-sm text-gray-300 font-bold">Periode:</label>
                <form method="GET" class="flex gap-2 items-center" id="filterForm">
                    <input type="date" id="tgl1" name="tgl1" value="<?= $tgl1 ?>" class="bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-sm focus:outline-none focus:border-blue-500">
                    <span class="text-gray-400 text-sm">s.d</span>
                    <input type="date" id="tgl2" name="tgl2" value="<?= $tgl2 ?>" class="bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-sm focus:outline-none focus:border-blue-500">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1 rounded text-sm font-bold ml-2">
                        Tampilkan
                    </button>
                </form>
            </div>

            <div class="flex gap-2">
                <button onclick="window.location.reload()" class="bg-gray-600 hover:bg-gray-500 text-white px-3 py-2 rounded text-sm font-bold">
                    Refresh
                </button>
                <a href="index.php" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-bold">
                    Home
                </a>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg shadow-xl p-4 border border-gray-700 overflow-x-auto">
            <table id="antrolTable" class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-gray-400 text-xs uppercase bg-gray-900">
                        <th class="p-3 rounded-tl-lg">Tgl & Pasien</th>
                        <th class="p-3">Dokter & Poli</th>
                        <th class="p-3 text-center">Task 0 (Add)</th>
                        <th class="p-3 text-center">Task 1 (Hadir)</th>
                        <th class="p-3 text-center">Task 2 (Batal)</th>
                        <th class="p-3 text-center">Status RS</th>
                        <th class="p-3 rounded-tr-lg text-center">Kesimpulan</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <?php foreach ($data as $row): ?>
                        <tr class="hover:bg-gray-700 transition border-b border-gray-700">
                            <td class="p-3">
                                <div class="text-blue-300 font-mono text-xs mb-1"><?= $row['tgl_registrasi'] ?> <?= $row['jam_reg'] ?></div>
                                <div class="font-bold text-white"><?= $row['nm_pasien'] ?></div>
                                <div class="text-[10px] text-gray-400"><?= $row['no_rawat'] ?></div>
                                <div class="text-[10px] text-gray-500"><?= $row['no_peserta'] ?></div>
                            </td>
                            <td class="p-3">
                                <div class="text-white text-xs"><?= $row['nm_dokter'] ?></div>
                                <div class="text-xs text-gray-400"><?= $row['nm_poli'] ?></div>
                            </td>
                            
                            <td class="p-3 text-center"><?= renderCheck($row['task0_waktu']) ?></td>
                            <td class="p-3 text-center"><?= renderCheck($row['task1_waktu']) ?></td>
                            <td class="p-3 text-center"><?= renderCheck($row['task2_waktu']) ?></td>
                            
                            <td class="p-3 text-center">
                                <span class="text-xs font-bold text-gray-300"><?= $row['stts'] ?></span>
                                <?php if($row['jam_periksa']): ?>
                                    <div class="text-[10px] text-green-400 mt-1">Ada SOAP</div>
                                <?php endif; ?>
                            </td>

                            <td class="p-3 text-center">
                                <?php 
                                    // Logika Diagnosa Masalah
                                    if (!empty($row['task0_waktu'])) {
                                        // Sudah bridging
                                        if ($row['stts'] == 'Batal' && empty($row['task2_waktu'])) {
                                            echo '<span class="bg-yellow-900 text-yellow-300 px-2 py-1 rounded text-[10px] border border-yellow-700">Blm Batal BPJS</span>';
                                        } elseif ($row['jam_periksa'] && empty($row['task1_waktu'])) {
                                            echo '<span class="bg-purple-900 text-purple-300 px-2 py-1 rounded text-[10px] border border-purple-700">Miss Task 1</span>';
                                        } else {
                                            echo '<span class="text-green-500 text-xs font-bold">AMAN</span>';
                                        }
                                    } else {
                                        // Belum bridging (Task 0 Kosong)
                                        if ($row['stts'] != 'Batal') {
                                            // Status Aktif tapi tidak di bridging -> BANDEL
                                            echo '<span class="bg-red-600 text-white px-2 py-1 rounded text-[10px] font-bold animate-pulse">SKIP BRIDGING</span>';
                                        } else {
                                            echo '<span class="text-gray-500 text-[10px]">Batal Lokal</span>';
                                        }
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-4 text-xs text-gray-500">
            * <b>SKIP BRIDGING</b>: Pasien didaftarkan manual di RS tapi tidak dikirim (Add Antrean) ke BPJS. Ini menyebabkan Task 1 dst akan gagal.
            <br>
            * Solusi: Gunakan fitur "Sapu Bersih" di Dashboard Antrean untuk menyusulkan data ini.
        </div>
    </div>

    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            // DataTables
            $('#antrolTable').DataTable({
                "pageLength": 50, 
                "ordering": false, 
                "language": {
                    "search": "Cari Pasien / No.Rawat:",
                    "lengthMenu": "Tampil _MENU_",
                    "info": "Total _TOTAL_ Pasien BPJS",
                    "paginate": { "next": ">", "previous": "<" }
                }
            });

            // ========================================================
            // AUTO-REFRESH MIDNIGHT LOGIC
            // ========================================================
            function checkDateMismatch() {
                const now = new Date();
                
                // Format YYYY-MM-DD local time
                const offset = now.getTimezoneOffset() * 60000;
                const localISOTime = new Date(now - offset).toISOString().slice(0, 10);
                
                const currentInputDate = $('#tgl2').val(); // Ambil tanggal akhir di input

                // Jika tanggal hari ini SUDAH LEBIH MAJU dari tanggal di input
                // Artinya sudah ganti hari tapi page belum direfresh
                if (currentInputDate && localISOTime > currentInputDate) {
                    console.log("Date mismatch detected! Reloading page...");
                    window.location.href = window.location.pathname; // Reload ke URL dasar (biar ambil default date baru)
                }
            }

            // Cek setiap 1 menit (60000 ms)
            setInterval(checkDateMismatch, 60000);
            
            // Juga auto-reload halaman setiap 15 menit untuk update data status
            setTimeout(function() {
                window.location.reload();
            }, 15 * 60 * 1000); 
        });
    </script>
</body>
</html>