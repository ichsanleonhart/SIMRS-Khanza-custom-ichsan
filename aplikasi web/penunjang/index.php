<?php
/*
 * ===================================================================================
 * HALAMAN ROUTER UTAMA
 * ===================================================================================
 * File ini hanya berfungsi sebagai router untuk mengarahkan pengguna
 * berdasarkan peran mereka setelah login.
 */

require_once 'config.php';
require_login(); // Memastikan pengguna sudah login

// Periksa peran pengguna yang tersimpan di session
if (isset($_SESSION['user_role'])) {
    $role = $_SESSION['user_role'];

    if ($role === 'laboratorium') {
        // Arahkan dokter lab ke dashboard laboratorium
        header('Location: laboratorium.php');
        exit;
    } elseif ($role === 'radiologi') {
        // Arahkan dokter radiologi ke dashboard radiologi
        header('Location: data_radiologi.php');
        exit;
    }
}

// Jika tidak ada peran yang valid, arahkan kembali ke halaman login untuk keamanan
header('Location: login.php');
exit;
?>

