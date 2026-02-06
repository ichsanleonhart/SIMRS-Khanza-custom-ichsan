<?php
// [2025-11-16] Selalu beri komentar.
// File: monitor_log.php
// Fungsi: GUI untuk memparsing dan memonitor log_pengiriman.log dengan DataTables.

require_once 'config.php';

$logFile = __DIR__ . '/log_pengiriman.log';
$parsedData = [];

// === LOGIC PARSING LOG FILE ===
if (file_exists($logFile)) {
    // Baca file
    $content = file_get_contents($logFile);
    
    // Pecah berdasarkan separator garis panjang
    // Menggunakan regex separator yang konsisten dengan bpjs_helper.php
    $blocks = explode("================================================================================", $content);
    
    // Loop blok dari bawah (terbaru) ke atas, atau biarkan array reverse nanti
    foreach ($blocks as $block) {
        $block = trim($block);
        if (empty($block)) continue;

        $entry = [];
        
        // 1. Ambil Waktu
        preg_match('/\[WAKTU LOG\]\s+:\s+(.*)/', $block, $waktu);
        $entry['waktu'] = $waktu[1] ?? '-';

        // 2. Ambil Info Data (No Rawat)
        preg_match('/\[INFO DATA\]\s+:\s+(.*)/', $block, $info);
        $rawInfo = $info[1] ?? '-';
        $entry['info_full'] = $rawInfo;
        
        // Ekstrak NoRawat saja untuk kolom tabel agar rapi
        // Format: Tgl Reg: ... | NoRawat: 2025/12/30/000001
        $parts = explode('| NoRawat: ', $rawInfo);
        $entry['no_rawat'] = $parts[1] ?? '-';

        // 3. Endpoint
        preg_match('/\[ENDPOINT\]\s+:\s+(.*)/', $block, $endpoint);
        $entry['endpoint'] = $endpoint[1] ?? '-';

        // 4. Payload (Request)
        preg_match('/\[PAYLOAD\]\s+:\s+(.*)/', $block, $payload);
        $entry['payload'] = $payload[1] ?? '{}';

        // 5. Response
        preg_match('/\[RESPONSE\]\s+:\s+(.*)/', $block, $response);
        $jsonResponse = $response[1] ?? '{}';
        $entry['response_raw'] = $jsonResponse;

        // Decode JSON Response untuk ambil Code & Message
        $decoded = json_decode($jsonResponse, true);
        
        // Handle struktur response yang kadang beda (metadata vs metaData)
        $meta = $decoded['metadata'] ?? $decoded['metaData'] ?? null;
        
        if ($meta) {
            $entry['code'] = $meta['code'] ?? 0;
            $entry['message'] = $meta['message'] ?? '';
        } else {
            // Kasus jika response bukan standar BPJS (misal error CURL string)
            $entry['code'] = 500;
            $entry['message'] = substr($jsonResponse, 0, 50) . '...';
        }

        // Masukkan ke array utama
        $parsedData[] = $entry;
    }
    
    // Balik array agar yang paling baru ada di atas (index 0)
    $parsedData = array_reverse($parsedData);
}

// === LOGIC HAPUS LOG (OPSIONAL) ===
if (isset($_POST['action']) && $_POST['action'] == 'clear_log') {
    file_put_contents($logFile, "");
    header("Location: monitor_log.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Monitor - Bridging BPJS</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.tailwind.min.css">

    <style>
        /* Custom Scrollbar for Dark Mode */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1f2937; }
        ::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #6b7280; }

        /* DataTables Custom Overrides for Dark Mode */
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter, 
        .dataTables_wrapper .dataTables_info, 
        .dataTables_wrapper .dataTables_processing, 
        .dataTables_wrapper .dataTables_paginate {
            color: #9ca3af !important;
        }
        .dataTables_wrapper .dataTables_length select, 
        .dataTables_wrapper .dataTables_filter input {
            background-color: #374151;
            color: white;
            border: 1px solid #4b5563;
            border-radius: 0.25rem;
            padding: 0.25rem;
        }
        table.dataTable tbody tr { background-color: #111827 !important; color: #e5e7eb; border-bottom: 1px solid #374151; }
        table.dataTable thead th { border-bottom: 1px solid #4b5563 !important; }
        table.dataTable.no-footer { border-bottom: 1px solid #374151; }
        
        /* Modal Overlay */
        #jsonModal { background-color: rgba(0, 0, 0, 0.85); }
    </style>
</head>
<body class="bg-gray-900 text-gray-200 min-h-screen font-sans p-6">

    <div class="container mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-white tracking-wide border-l-4 border-blue-500 pl-4">
                LOG VIEWER <span class="text-blue-400">BRIDGING BPJS</span>
            </h1>
            <div class="flex gap-2">
                <a href="dashboard.php" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-bold transition">
                    &larr; Kembali ke Dashboard
                </a>
                <!--<form method="POST" onsubmit="return confirm('Yakin ingin menghapus seluruh isi log? Data tidak bisa dikembalikan.');">
                    <input type="hidden" name="action" value="clear_log">
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm font-bold transition">
                        Hapus Log (Reset)
                    </button>
                </form>-->
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg shadow-xl p-4 border border-gray-700 overflow-x-auto">
            <table id="logsTable" class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-blue-300 text-sm uppercase">
                        <th class="p-3">Waktu</th>
                        <th class="p-3">No. Rawat</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Endpoint / Aksi</th>
                        <th class="p-3">Pesan Server</th>
                        <th class="p-3 text-center">Detail</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <?php if (empty($parsedData)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-gray-500">Belum ada data log pengiriman.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($parsedData as $index => $row): ?>
                            <?php 
                                // Tentukan Warna Badge berdasarkan Code
                                $badgeClass = 'bg-red-900 text-red-200 border-red-700';
                                if ($row['code'] == 200 || $row['code'] == 1) {
                                    $badgeClass = 'bg-green-900 text-green-200 border-green-700';
                                } elseif ($row['code'] == 201) {
                                    $badgeClass = 'bg-yellow-900 text-yellow-200 border-yellow-700'; // Warning/Sudah Ada
                                }
                            ?>
                            <tr class="hover:bg-gray-700 transition">
                                <td class="p-3 font-mono text-xs text-gray-400 whitespace-nowrap"><?= $row['waktu'] ?></td>
                                <td class="p-3 font-bold text-white"><?= $row['no_rawat'] ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-1 rounded text-xs font-bold border <?= $badgeClass ?>">
                                        <?= $row['code'] ?>
                                    </span>
                                </td>
                                <td class="p-3 text-xs text-blue-300 font-mono">
                                    <?php 
                                        // Persingkat endpoint display
                                        $method = explode(' ', $row['endpoint'])[0]; 
                                        $urlParts = explode('/', $row['endpoint']);
                                        $lastSegment = end($urlParts);
                                        echo "<span class='font-bold text-gray-300'>$method</span> .../$lastSegment";
                                    ?>
                                </td>
                                <td class="p-3 text-gray-300 truncate max-w-xs" title="<?= htmlspecialchars($row['message']) ?>">
                                    <?= htmlspecialchars(mb_strimwidth($row['message'], 0, 50, "...")) ?>
                                </td>
                                <td class="p-3 text-center">
                                    <button onclick='showDetail(<?= json_encode($row) ?>)' class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1 rounded text-xs">
                                        View JSON
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="jsonModal" class="fixed inset-0 hidden z-50 flex items-center justify-center">
        <div class="bg-gray-800 rounded-lg shadow-2xl border border-gray-600 w-11/12 lg:w-3/4 max-h-[90vh] flex flex-col">
            <div class="flex justify-between items-center p-4 border-b border-gray-700 bg-gray-900 rounded-t-lg">
                <h3 class="text-lg font-bold text-white">Detail Transaksi Log</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-white text-2xl">&times;</button>
            </div>
            
            <div class="p-6 overflow-y-auto font-mono text-xs text-gray-300 space-y-4">
                
                <div>
                    <label class="block text-blue-400 font-bold mb-1">INFO DATA:</label>
                    <div id="modalInfo" class="p-2 bg-gray-900 rounded border border-gray-700 text-yellow-300"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-green-400 font-bold mb-1">PAYLOAD (KIRIM):</label>
                        <pre id="modalPayload" class="p-3 bg-gray-900 rounded border border-gray-700 overflow-x-auto h-64 text-green-200"></pre>
                    </div>
                    <div>
                        <label class="block text-purple-400 font-bold mb-1">RESPONSE (TERIMA):</label>
                        <pre id="modalResponse" class="p-3 bg-gray-900 rounded border border-gray-700 overflow-x-auto h-64 text-purple-200"></pre>
                    </div>
                </div>

            </div>

            <div class="p-4 border-t border-gray-700 bg-gray-900 text-right rounded-b-lg">
                <button onclick="closeModal()" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded">Tutup</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#logsTable').DataTable({
                "order": [], // Matikan default sort DataTables karena data dari PHP sudah urut waktu terbaru
                "pageLength": 25,
                "language": {
                    "search": "Cari Data (NoRawat/Pesan):",
                    "lengthMenu": "Tampil _MENU_ baris",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ log",
                    "paginate": {
                        "first": "<<",
                        "last": ">>",
                        "next": ">",
                        "previous": "<"
                    },
                    "emptyTable": "Tidak ada data log yang ditemukan"
                }
            });
        });

        function showDetail(data) {
            $('#modalInfo').text(data.info_full);
            
            // Pretty Print JSON
            try {
                const payloadObj = JSON.parse(data.payload);
                $('#modalPayload').text(JSON.stringify(payloadObj, null, 2));
            } catch(e) {
                $('#modalPayload').text(data.payload); // Jika bukan JSON, tampilkan mentah
            }

            try {
                const responseObj = JSON.parse(data.response_raw);
                $('#modalResponse').text(JSON.stringify(responseObj, null, 2));
            } catch(e) {
                $('#modalResponse').text(data.response_raw);
            }

            $('#jsonModal').removeClass('hidden');
        }

        function closeModal() {
            $('#jsonModal').addClass('hidden');
        }
        
        // Close modal on escape key
        $(document).on('keydown', function(event) {
            if (event.key === "Escape") {
                closeModal();
            }
        });
    </script>
</body>
</html>