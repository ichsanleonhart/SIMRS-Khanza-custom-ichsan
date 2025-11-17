<?php
// File: conf.php
// Konfigurasi lokal untuk aplikasi antrean farmasi (Standalone)

// Poin 9 (Kredensial)
$host = "localhost";
$user = "client";
$pass = "epotoransu";
$db = "sik_master"; 

// --- FUNGSI KONEKSI & HELPER KHANZA ---

// Aktifkan error reporting untuk debugging (PENTING untuk atasi blank screen)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Buat koneksi
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (mysqli_connect_errno()) {
    // Jika koneksi gagal, TAMPILKAN error, jangan blank screen
    echo "Gagal terhubung ke MySQL: " . mysqli_connect_error();
    exit();
}

// Fungsi query standar Khanza
function bukaquery($sql) {
    global $koneksi;
    try {
        $result = mysqli_query($koneksi, $sql);
        if (!$result) {
            throw new Exception(mysqli_error($koneksi));
        }
        return $result;
    } catch (Exception $e) {
        echo "Error MySQL: " . $e->getMessage();
        die();
    }
}

// Fungsi query untuk INSERT, UPDATE, DELETE
function bukaquery2($sql) {
    global $koneksi;
    try {
        $result = mysqli_query($koneksi, $sql);
        if (!$result) {
            throw new Exception(mysqli_error($koneksi));
        }
        return $result;
    } catch (Exception $e) {
        echo "Error MySQL: " . $e->getMessage();
        die();
    }
}

// Fungsi cleankar (basic sanitation)
function cleankar($data) {
    global $koneksi;
    return mysqli_real_escape_string($koneksi, stripslashes(strip_tags(htmlspecialchars($data, ENT_QUOTES))));
}

// Set timezone
date_default_timezone_set("Asia/Bangkok");
?>