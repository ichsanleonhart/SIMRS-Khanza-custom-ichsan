<?php
session_start();
require_once('../../conf/conf.php');
// Proteksi Login HRD Wajib Ada
if (!isset($_SESSION['hrd_login'])) { header("Location: login.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🤖 Auto Reminder Bot - HRD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../js/jquery.min.js"></script>
    <style>
        /* Efek Terminal Matrix */
        body { background-color: #0f172a; color: #33ff33; font-family: 'Courier New', Courier, monospace; }
        .log-container { 
            height: calc(100vh - 140px); 
            overflow-y: auto; 
            background: #000; 
            border: 1px solid #334155; 
            padding: 1rem; 
            border-radius: 0.5rem;
            box-shadow: inset 0 0 20px rgba(0, 255, 0, 0.1);
        }
        .log-entry { margin-bottom: 4px; border-bottom: 1px solid #1e293b; padding-bottom: 2px; }
        .timestamp { color: #94a3b8; margin-right: 10px; }
        .success { color: #4ade80; }
        .error { color: #f87171; }
        .info { color: #60a5fa; }
        
        /* Scrollbar Keren */
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 5px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>
<body class="p-6 flex flex-col h-screen overflow-hidden">

    <div class="flex justify-between items-center mb-4">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                <span class="animate-pulse">🟢</span> Auto Reminder Bot (WhatsApp)
            </h1>
            <p class="text-gray-400 text-sm mt-1">
                Biarkan halaman ini <b>TERBUKA TERUS</b>. Sesi tidak akan expired selama halaman ini jalan.
            </p>
        </div>
        <div class="text-right">
            <div id="clock" class="text-xl font-bold text-blue-400">00:00:00</div>
            <div class="text-xs text-gray-500">Next Check: <span id="countdown" class="text-yellow-400">60</span>s</div>
        </div>
    </div>

    <div id="terminal" class="log-container text-xs md:text-sm">
        <div class="log-entry info"><span class="timestamp">[SYSTEM]</span> Bot siap. Menunggu siklus pertama...</div>
    </div>

    <div class="mt-4 flex gap-4">
        <a href="index.php" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded border border-gray-600">Kembali ke Menu</a>
        <button onclick="runBot()" class="bg-blue-900 hover:bg-blue-800 text-blue-100 px-4 py-2 rounded border border-blue-700">Paksa Jalan Sekarang</button>
    </div>

    <script>
        let countdown = 60;
        
        // Update Jam Digital
        setInterval(() => {
            const now = new Date();
            document.getElementById('clock').innerText = now.toLocaleTimeString('id-ID');
        }, 1000);

        // Update Countdown & Trigger Bot
        setInterval(() => {
            countdown--;
            document.getElementById('countdown').innerText = countdown;
            
            if (countdown <= 0) {
                runBot();
                countdown = 60; // Reset timer 1 menit
            }
        }, 1000);

        function runBot() {
            addLog("Memulai pengecekan...", "info");
            
            $.ajax({
                url: 'api_reminder.php',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') {
                        if(res.logs.length > 0) {
                            res.logs.forEach(msg => addLog(msg, "success"));
                        } else {
                            addLog("Tidak ada target reminder (Zzz...)", "info");
                        }
                    } else {
                        addLog("Error: " + res.message, "error");
                    }
                },
                error: function(xhr, status, error) {
                    if(xhr.status === 403) {
                        addLog("⚠️ SESI HABIS / LOGOUT! Silakan login ulang.", "error");
                        alert("Sesi Habis! Halaman akan reload.");
                        location.reload();
                    } else {
                        addLog("Koneksi Gagal: " + error, "error");
                    }
                }
            });
        }

        function addLog(message, type) {
            const term = document.getElementById('terminal');
            const now = new Date().toLocaleTimeString('id-ID');
            const colorClass = type; // success, error, info
            
            const html = `<div class="log-entry ${colorClass}">
                            <span class="timestamp">[${now}]</span> ${message}
                          </div>`;
            
            term.insertAdjacentHTML('beforeend', html);
            term.scrollTop = term.scrollHeight; // Auto scroll ke bawah
        }
    </script>
</body>
</html>