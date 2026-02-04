<?php
// File: helpers/auth_helper.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function cekLogin() {
    // Jika belum login, tendang ke halaman login
    if (!isset($_SESSION['is_login']) || $_SESSION['is_login'] !== true) {
        header("Location: " . $GLOBALS['base_url'] . "modules/auth/login.php");
        exit;
    }
}

// Fungsi untuk Super Admin bypass semua akses
function isSuperAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
}

// Fungsi cek hak akses modul (misal: 'mpp_skrining')
// Super Admin selalu TRUE
function cekAkses($hak_akses_kolom) {
    if (isSuperAdmin()) return true;

    // Untuk user biasa, cek session permissions
    if (isset($_SESSION['hak_akses'][$hak_akses_kolom])) {
        return $_SESSION['hak_akses'][$hak_akses_kolom] === 'true';
    }
    return false;
}
?>