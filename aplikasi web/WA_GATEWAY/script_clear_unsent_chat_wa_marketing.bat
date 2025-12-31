@echo off

:: ============================================================================
::    SKRIP PENGHAPUSAN OTOMATIS PESAN WA GAGAL (ANTRIAN / NOWA SALAH)
:: ============================================================================
:: Versi 2: Menggunakan parameter -e untuk menghindari error karakter spesial
:: ============================================================================

:: Credential MySQL
set "MYSQL_EXE=D:\xampp\mysql\bin\mysql.exe"
set "MYSQL_HOST=192.168.1.2"
set "MYSQL_PORT=3306"
set "MYSQL_USER=client"
set "MYSQL_PASS=epotoransu"
set "MYSQL_DB=wa_marketing"
set "WA_TABLE=wa_outbox"

echo ========================================================
echo     Memulai Proses Penghapusan Pesan WA Gagal
echo ========================================================
echo.

echo [INFO] Menjalankan query penghapusan data...

:: Query SQL yang Anda minta
:: Kita gunakan parameter -e (execute) langsung di bawah
:: Ini jauh lebih aman daripada 'echo' dan '|' (pipe)
:: karena karakter seperti '<' tidak akan error.
set "SQL_QUERY=DELETE FROM %WA_TABLE% WHERE status = 'ANTRIAN' AND tanggal_jam < NOW() OR NOWA = '@c.us';"

:: Eksekusi DELETE ke database menggunakan parameter -e
"%MYSQL_EXE%" -h%MYSQL_HOST% -P%MYSQL_PORT% -u%MYSQL_USER% -p%MYSQL_PASS% %MYSQL_DB% -e "%SQL_QUERY%"

echo [INFO] Proses penghapusan data 'ANTRIAN' dan 'NOWA = @c.us' selesai.
echo.
echo ========================================================
echo     Proses Selesai!
echo ========================================================

:end
timeout /t 30