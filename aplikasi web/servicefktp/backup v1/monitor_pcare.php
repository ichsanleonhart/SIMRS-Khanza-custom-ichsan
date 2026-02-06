<?php
// [2025-11-16] Selalu beri komentar.
// File: monitor_pcare.php
// Fungsi: Monitor PCare LENGKAP (Anamnesa, Lingkar Perut, TTV) + Validasi Pra-Kirim.

require_once 'pcare_config.php';

$tgl1 = isset($_GET['tgl1']) ? $_GET['tgl1'] : date('Y-m-d');
$tgl2 = isset($_GET['tgl2']) ? $_GET['tgl2'] : date('Y-m-d');

// Helper Validasi
function isValidValue($val) {
    if (is_null($val)) return false;
    $clean = trim($val);
    if ($clean === '') return false;
    if ($clean === '0') return false;
    if ($clean === '0/0') return false;
    if ($clean === '-') return false;
    return true;
}

// QUERY RAW (Tarik semua entry pemeriksaan untuk Smart Merge)
$sql = "SELECT 
            rp.no_rawat, rp.no_rkm_medis, rp.tgl_registrasi, rp.jam_reg, rp.status_lanjut,
            ps.nm_pasien, ps.no_peserta,
            d.nm_dokter, p.nm_poli,
            
            -- Mapping
            (SELECT COUNT(*) FROM maping_dokter_pcare WHERE kd_dokter = rp.kd_dokter) as is_map_dokter,
            (SELECT COUNT(*) FROM maping_poliklinik_pcare WHERE kd_poli_rs = rp.kd_poli) as is_map_poli,
            
            -- TTV & Fisik Raw (Termasuk Anamnesa & Lingkar Perut)
            pr.tensi, pr.nadi, pr.respirasi, pr.suhu_tubuh, pr.berat, pr.tinggi, 
            pr.keluhan, pr.lingkar_perut, pr.kesadaran,
            
            -- Diagnosa
            (SELECT kd_penyakit FROM diagnosa_pasien WHERE no_rawat = rp.no_rawat AND prioritas = 1 LIMIT 1) as kdDiag1,
            
            pk.noKunjungan
        FROM reg_periksa rp
        INNER JOIN pasien ps ON rp.no_rkm_medis = ps.no_rkm_medis
        INNER JOIN dokter d ON rp.kd_dokter = d.kd_dokter
        INNER JOIN poliklinik p ON rp.kd_poli = p.kd_poli
        LEFT JOIN pemeriksaan_ralan pr ON rp.no_rawat = pr.no_rawat
        LEFT JOIN pcare_kunjungan_umum pk ON rp.no_rawat = pk.no_rawat
        WHERE rp.kd_pj = 'BPJ' 
        AND rp.tgl_registrasi BETWEEN :tgl1 AND :tgl2
        ORDER BY rp.tgl_registrasi DESC, rp.jam_reg DESC, pr.jam_rawat ASC"; 

$stmt = $pdo->prepare($sql);
$stmt->execute(['tgl1' => $tgl1, 'tgl2' => $tgl2]);
$rawRows = $stmt->fetchAll();

// --- LOGIKA SMART MERGE PHP ---
$mergedData = [];
foreach ($rawRows as $row) {
    $nr = $row['no_rawat'];
    if (!isset($mergedData[$nr])) {
        $mergedData[$nr] = $row;
    } else {
        // Merge TTV
        if (isValidValue($row['tensi'])) $mergedData[$nr]['tensi'] = $row['tensi'];
        if (isValidValue($row['nadi'])) $mergedData[$nr]['nadi'] = $row['nadi'];
        if (isValidValue($row['respirasi'])) $mergedData[$nr]['respirasi'] = $row['respirasi'];
        if (isValidValue($row['suhu_tubuh'])) $mergedData[$nr]['suhu_tubuh'] = $row['suhu_tubuh'];
        if (isValidValue($row['berat'])) $mergedData[$nr]['berat'] = $row['berat'];
        if (isValidValue($row['tinggi'])) $mergedData[$nr]['tinggi'] = $row['tinggi'];
        
        // Merge Anamnesa & L.Perut (Prioritas Data Terbaru yang Valid)
        if (isValidValue($row['keluhan'])) $mergedData[$nr]['keluhan'] = $row['keluhan'];
        if (isValidValue($row['lingkar_perut'])) $mergedData[$nr]['lingkar_perut'] = $row['lingkar_perut'];
        
        // Merge Status
        if (!empty($row['noKunjungan'])) $mergedData[$nr]['noKunjungan'] = $row['noKunjungan'];
    }
}

// Helper Tampilan
function validateVal($val, $label, &$errors) {
    $clean = floatval(preg_replace("/[^0-9.]/", "", $val));
    if (empty($val) || $clean <= 0) {
        $errors[] = "$label 0";
        return "<span class='text-red-500 font-bold'>0</span>";
    }
    return "<span class='text-green-300'>$val</span>";
}

function validateTensi($tensi, &$errors) {
    if (empty($tensi) || $tensi == '-') {
        $errors[] = "Tensi -";
        return "<span class='text-red-500 font-bold'>-</span>";
    }
    $parts = explode('/', $tensi);
    $s = isset($parts[0]) ? intval($parts[0]) : 0;
    $d = isset($parts[1]) ? intval($parts[1]) : 0;
    if ($s <= 0 || $d <= 0) {
        $errors[] = "Tensi 0";
        return "<span class='text-red-500 font-bold'>$tensi</span>";
    }
    return "<span class='text-green-300'>$tensi</span>";
}

function validateAnamnesa($text, &$errors) {
    $len = strlen(trim($text));
    if ($len == 0 || trim($text) == '-') {
        $errors[] = "Anamnesa Kosong";
        return "<span class='text-red-500 font-bold'>KOSONG</span>";
    }
    if ($len < 10) {
        $errors[] = "Anamnesa < 10 Char";
        return "<span class='text-red-400 font-bold' title='Minimal 10 Karakter'>$text ($len)</span>";
    }
    // Potong jika terlalu panjang buat tampilan
    $display = (strlen($text) > 20) ? substr($text, 0, 20) . '...' : $text;
    return "<span class='text-green-300' title='$text'>$display</span>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor PCare Lengkap</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.tailwind.min.css">
    <style>
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1f2937; }
        ::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 4px; }
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { color: #9ca3af !important; }
        .dataTables_wrapper .dataTables_length select, .dataTables_wrapper .dataTables_filter input { background-color: #374151; color: white; border: 1px solid #4b5563; padding: 0.25rem; border-radius: 0.25rem; }
        table.dataTable tbody tr { background-color: #111827 !important; color: #e5e7eb; border-bottom: 1px solid #374151; }
    </style>
</head>
<body class="bg-gray-900 text-gray-200 min-h-screen font-sans p-4">

    <div class="container mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4 bg-gray-800 p-4 rounded-lg shadow-lg border border-gray-700">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-wide flex items-center gap-2">
                    <span class="text-3xl">🕵️</span> AUDIT TOTAL <span class="text-yellow-400">PCARE</span>
                </h1>
                <p class="text-xs text-gray-400 mt-1">Monitoring TTV, Fisik, Anamnesa & Diagnosa.</p>
            </div>
            
            <div class="flex gap-2 items-center">
                <form method="GET" class="flex gap-2 items-center bg-gray-900 p-2 rounded border border-gray-600">
                    <input type="date" name="tgl1" value="<?= $tgl1 ?>" class="bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-sm">
                    <span class="text-gray-400 text-sm font-bold">-</span>
                    <input type="date" name="tgl2" value="<?= $tgl2 ?>" class="bg-gray-700 text-white border border-gray-600 rounded px-2 py-1 text-sm">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-1 rounded text-sm font-bold shadow-lg transition">Filter</button>
                </form>
                <a href="index.php" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-bold border border-gray-600">Home</a>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg shadow-xl p-4 border border-gray-700 overflow-x-auto">
            <table id="auditTable" class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="text-gray-400 text-xs uppercase bg-gray-900 border-b border-gray-700">
                        <th class="p-3">Pasien</th>
                        <th class="p-3">Tanda Vital</th>
                        <th class="p-3 text-center">Fisik (BB/TB)</th>
                        <th class="p-3 text-center">L.Perut</th>
                        <th class="p-3">Anamnesa</th>
                        <th class="p-3 text-center">Diag</th>
                        <th class="p-3 text-center">Map</th>
                        <th class="p-3">Validasi</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-xs">
                    <?php foreach ($mergedData as $row): 
                        $errors = [];
                        
                        // Validasi Map
                        if (!$row['is_map_dokter']) $errors[] = "Map Dokter";
                        if (!$row['is_map_poli']) $errors[] = "Map Poli";
                        
                        // Validasi Diagnosa
                        if (empty($row['kdDiag1'])) $errors[] = "Diagnosa";
                    ?>
                        <tr class="hover:bg-gray-700 transition border-b border-gray-700">
                            <td class="p-3">
                                <div class="font-bold text-white text-sm"><?= $row['nm_pasien'] ?></div>
                                <div class="text-[10px] text-gray-400"><?= $row['no_rawat'] ?></div>
                                <div class="text-[10px] text-yellow-500"><?= $row['no_peserta'] ?></div>
                            </td>

                            <td class="p-3">
                                <div class="grid grid-cols-2 gap-x-2">
                                    <div>TD: <?= validateTensi($row['tensi'], $errors) ?></div>
                                    <div>N: <?= validateVal($row['nadi'], 'Nadi', $errors) ?></div>
                                    <div>RR: <?= validateVal($row['respirasi'], 'RR', $errors) ?></div>
                                    <div>S: <?= validateVal($row['suhu_tubuh'], 'Suhu', $errors) ?></div>
                                </div>
                            </td>

                            <td class="p-3 text-center">
                                <div>BB: <?= validateVal($row['berat'], 'BB', $errors) ?></div>
                                <div>TB: <?= validateVal($row['tinggi'], 'TB', $errors) ?></div>
                            </td>

                            <td class="p-3 text-center">
                                <?= validateVal($row['lingkar_perut'], 'L.P', $errors) ?>
                            </td>

                            <td class="p-3">
                                <?= validateAnamnesa($row['keluhan'], $errors) ?>
                            </td>

                            <td class="p-3 text-center">
                                <?= $row['kdDiag1'] ? '<span class="bg-blue-900 px-2 py-1 rounded text-blue-200 font-bold">'.$row['kdDiag1'].'</span>' : '<span class="text-red-500 font-bold">✕</span>' ?>
                            </td>

                            <td class="p-3 text-center">
                                <?= ($row['is_map_dokter'] && $row['is_map_poli']) ? '<span class="text-green-400 font-bold">✓</span>' : '<span class="text-red-500 font-bold">✕</span>' ?>
                            </td>

                            <td class="p-3">
                                <?php 
                                    if (!empty($row['noKunjungan'])) {
                                        echo '<span class="text-green-400 font-bold">TERKIRIM</span><br><span class="text-[9px] text-gray-500">'.$row['noKunjungan'].'</span>';
                                    } elseif (count($errors) > 0) {
                                        // Tampilkan Error Validasi
                                        echo '<div class="flex flex-col gap-1">';
                                        foreach($errors as $err) {
                                            echo '<span class="bg-red-900 text-red-300 px-1 rounded text-[9px] border border-red-800">'.$err.'</span>';
                                        }
                                        echo '</div>';
                                    } else {
                                        echo '<span class="text-blue-400 font-bold animate-pulse">SIAP KIRIM</span>';
                                    }
                                ?>
                            </td>

                            <td class="p-3 text-center">
                                <?php if (empty($row['noKunjungan']) && count($errors) == 0): ?>
                                    <button onclick="kirimManual('<?= $row['no_rawat'] ?>')" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1 rounded shadow-lg font-bold text-xs">
                                        🚀
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-4 p-4 bg-gray-800 rounded border border-gray-700 text-xs text-gray-400">
            <h3 class="font-bold text-white mb-2">Panduan Validasi BPJS:</h3>
            <ul class="list-disc pl-5 space-y-1">
                <li><span class="text-red-400 font-bold">Anamnesa < 10:</span> BPJS menolak keluhan yang terlalu pendek (Min 10 karakter). Contoh: "Sakit" (Ditolak). Harus: "Sakit kepala sejak 3 hari".</li>
                <li><span class="text-red-400 font-bold">L.Perut 0:</span> Lingkar perut wajib diisi.</li>
                <li><span class="text-red-400 font-bold">TTV 0:</span> Semua tanda vital harus ada isinya (kecuali kondisi khusus, tapi BPJS sistem biasanya nolak 0).</li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() { $('#auditTable').DataTable({ "pageLength": 25, "ordering": false }); });
        
        function kirimManual(noRawat) {
            if(!confirm('Kirim ' + noRawat + '?')) return;
            
            // Ubah UI jadi loading
            const btn = event.target;
            const oriText = btn.innerText;
            btn.innerText = '⏳';
            btn.disabled = true;

            $.ajax({
                url: 'pcare_worker.php', 
                method: 'POST', 
                data: { mode: 'manual', no_rawat: noRawat }, 
                dataType: 'json',
                success: function(res) { 
                    // [ANTI-ABS] Cek isi log, jangan percaya status 'success' saja
                    // Cari kata kunci kegagalan di logs
                    let isError = false;
                    let errorMsg = "";
                    
                    if (res.logs && res.logs.length > 0) {
                        res.logs.forEach(log => {
                            if (log.includes("GAGAL") || log.includes("412") || log.includes("Error") || log.includes("SKIP")) {
                                isError = true;
                                errorMsg += log + "\n";
                            }
                        });
                    }

                    if (isError) {
                        alert("❌ GAGAL KIRIM:\n" + errorMsg);
                        btn.innerText = oriText; // Balikin tombol
                        btn.disabled = false;
                    } else {
                        alert("✅ SUKSES TERKIRIM!");
                        location.reload(); 
                    }
                },
                error: function() { 
                    alert('❌ Error Network / Timeout'); 
                    btn.innerText = oriText;
                    btn.disabled = false;
                }
            });
        }
    </script>
</body>
</html>