@echo off
setlocal enabledelayedexpansion

:: ============================================================================ 
::         SKRIP PENGECEKAN WHATSAPP GATEWAY OTOMATIS
:: ============================================================================

:: Credential MySQL untuk notifikasi WhatsApp
set "MYSQL_EXE=D:\xampp\mysql\bin\mysql.exe"
set "MYSQL_HOST=192.168.1.2"
set "MYSQL_PORT=3306"
set "MYSQL_USER=client"
set "MYSQL_PASS=epotoransu"
set "MYSQL_DB=wa_marketing"
set "WA_TABLE=wa_outbox"

:: Data default untuk notifikasi WhatsApp
set "WA_NO=6285726123777@c.us"
set "WA_SOURCE=checker"
set "WA_SENDER=NODEJS"
set "WA_TYPE=TEXT"
set "WA_STATUS=ANTRIAN"

echo ========================================================
echo          Memulai Proses pengiriman pesan otomatis pengecekan
echo ========================================================
echo.

echo Pengecekan status whatsapp kirim notif ke hp Ichsan...
set "WA_MESSAGE=Ini adalah pengecekan status bot whatsapp harian RS Karina Medika (Nomor Marketing pengirim ucapan ultah) Pengecekan ini dilakukan pada tanggal %date% jam %time%. Jika berbeda dengan jam pengiriman, segera cek whatsapp gateway-nya!"

:: Escape karakter kutip tunggal agar tidak merusak sintaks SQL
set "WA_MESSAGE=%WA_MESSAGE:'=\'%"

:: Eksekusi INSERT ke database
echo INSERT INTO %WA_TABLE% (nowa, pesan, tanggal_jam, status, source, sender, type) VALUES ('%WA_NO%', '%WA_MESSAGE%', NOW(), '%WA_STATUS%', '%WA_SOURCE%', '%WA_SENDER%', '%WA_TYPE%'); | "%MYSQL_EXE%" -h%MYSQL_HOST% -P%MYSQL_PORT% -u%MYSQL_USER% -p%MYSQL_PASS% %MYSQL_DB%

echo [INFO] Notifikasi berhasil dimasukkan ke database.
echo.
echo ========================================================
echo      Proses kirim pesan selesai!
echo ========================================================

:end
timeout /t 1