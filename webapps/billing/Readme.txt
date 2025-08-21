semua file di simpan didalam folder webapps/billing

html2canvas.min
jspdf.min
jspdf.plugin.autotable.min
LaporanBilling1.php
upload_billing.php


file upload_billing.php
di query 

$sql_check = "SELECT lokasi_file FROM berkas_digital_perawatan WHERE no_rawat='$no_rawat' AND kode='*menyesuaikan kode berkas*'";

$sql = "UPDATE berkas_digital_perawatan SET lokasi_file='$lokasi_file' WHERE no_rawat='$no_rawat' AND kode='*menyesuaikan kode berkas*'";

INSERT INTO berkas_digital_perawatan (no_rawat, kode, lokasi_file) VALUES ('$no_rawat', '*menyesuaikan kode berkas*', '$lokasi_file')