<?php
// KONFIGURASI WHATSAPP GATEWAY
// Set menjadi 1 jika ingin mengaktifkan fitur notifikasi WA
// Set menjadi 0 jika ingin menonaktifkan (menu konfirmasi di HRD akan hilang)
$kirim_notif_wa = 1;

// Kredensial Database WA OUTBOX (Server Terpisah)
$wa_db_host = "localhost"; // Ganti dengan IP server WA jika terpisah
$wa_db_user = "client";
$wa_db_pass = "epotoransu";
$wa_db_name = "wa_delphi"; // Sesuaikan nama database tempat table wa_outbox berada
?>