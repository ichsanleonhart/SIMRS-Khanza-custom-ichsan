<?php
// [2025-11-16] Selalu beri komentar.
// File: index.php
// Fungsi: Halaman utama (Portal) akses ke seluruh modul Service FKTP (Updated).
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Center FKTP - Klinik Musytasyfah</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-200 min-h-screen font-sans flex items-center justify-center">

    <div class="container mx-auto p-6">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-bold text-white tracking-wide mb-2">
                SERVICE CENTER <span class="text-blue-500">FKTP</span>
            </h1>
            <p class="text-gray-400">Pusat Kontrol Bridging Antrean & PCare BPJS</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
            
            <a href="dashboard.php" class="block group">
                <div class="bg-gray-800 border border-gray-700 rounded-xl p-6 hover:bg-gray-700 hover:border-blue-500 transition duration-300 shadow-lg h-full flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-blue-900 rounded-full flex items-center justify-center mb-4 text-blue-300 group-hover:scale-110 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Worker Antrol</h3>
                    <p class="text-sm text-gray-400">Service pengiriman antrean (Add/Hadir/Batal) ke Mobile JKN.</p>
                </div>
            </a>

            <a href="monitor_antrol.php" class="block group">
                <div class="bg-gray-800 border border-gray-700 rounded-xl p-6 hover:bg-gray-700 hover:border-cyan-500 transition duration-300 shadow-lg h-full flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-cyan-900 rounded-full flex items-center justify-center mb-4 text-cyan-300 group-hover:scale-110 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Audit Antrol</h3>
                    <p class="text-sm text-gray-400">Cek pasien BPJS yang tidak dibridging (Task 0 Kosong).</p>
                </div>
            </a>

            <a href="pcare_dashboard.php" class="block group">
                <div class="bg-gray-800 border border-gray-700 rounded-xl p-6 hover:bg-gray-700 hover:border-green-500 transition duration-300 shadow-lg h-full flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-green-900 rounded-full flex items-center justify-center mb-4 text-green-300 group-hover:scale-110 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Worker PCare</h3>
                    <p class="text-sm text-gray-400">Service pengiriman data Kunjungan (Diagnosa & Vital).</p>
                </div>
            </a>

            <a href="monitor_pcare.php" class="block group">
                <div class="bg-gray-800 border border-gray-700 rounded-xl p-6 hover:bg-gray-700 hover:border-yellow-500 transition duration-300 shadow-lg h-full flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-yellow-900 rounded-full flex items-center justify-center mb-4 text-yellow-300 group-hover:scale-110 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Audit PCare</h3>
                    <p class="text-sm text-gray-400">Cek pasien yang data medisnya belum lengkap.</p>
                </div>
            </a>

            <a href="monitor_log.php" class="block group">
                <div class="bg-gray-800 border border-gray-700 rounded-xl p-6 hover:bg-gray-700 hover:border-purple-500 transition duration-300 shadow-lg h-full flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-purple-900 rounded-full flex items-center justify-center mb-4 text-purple-300 group-hover:scale-110 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Log Viewer</h3>
                    <p class="text-sm text-gray-400">Analisa Raw Log respon BPJS (JSON Payload & Response).</p>
                </div>
            </a>

        </div>

        <div class="mt-10 text-center text-xs text-gray-600">
            &copy; <?= date('Y') ?> SIMKES Khanza - Service Modul
        </div>
    </div>

</body>
</html>