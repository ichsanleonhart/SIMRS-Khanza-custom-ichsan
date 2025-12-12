<?php
/*
 * File: /webapps/berkas_digital_perawatan/login_check.php
 * Fungsi: Memvalidasi kredensial user ke database Khanza (Fixed Version)
 */
session_start();
require_once('../conf/conf.php');

$koneksi = bukakoneksi();

// 1. Ambil Input
// Username kita sanitasi untuk keamanan SQL
$username = validTeks4($_POST['username'], 50);

// Password kita ambil RAW (mentah) untuk pencocokan PHP (agar karakter unik tidak hilang)
// Tapi nanti TIDAK BOLEH dimasukkan langsung ke SQL Query tanpa escaping
$password_raw = $_POST['password'];

if (empty($username) || empty($password_raw)) {
    header("Location: index.php?pesan=gagal");
    exit;
}

// ============================================================================
// 1. CEK SUPER ADMIN (Tabel: admin)
// ============================================================================
// Gunakan CAST(... AS CHAR) untuk memastikan hasil dekripsi adalah String, bukan Binary
$q_admin = "SELECT 
                CAST(AES_DECRYPT(usere, 'nur') AS CHAR) as usere, 
                CAST(AES_DECRYPT(passworde, 'windi') AS CHAR) as passworde 
            FROM admin 
            WHERE AES_DECRYPT(usere, 'nur') = '$username' LIMIT 1";

$r_admin = mysqli_query($koneksi, $q_admin);

if ($r_admin && mysqli_num_rows($r_admin) > 0) {
    $row = mysqli_fetch_assoc($r_admin);
    
    // Bandingkan password dari DB dengan Input Mentah User
    if ($row['passworde'] === $password_raw) {
        // Login Berhasil Sebagai Super Admin
        session_regenerate_id(true); // Security: Cegah Session Fixation
        $_SESSION['casemix_login'] = true;
        $_SESSION['casemix_user']  = $username;
        $_SESSION['casemix_role']  = 'Super Admin';
        
        header("Location: dashboard.php");
        exit;
    }
}

// ============================================================================
// 2. CEK USER PEGAWAI (Tabel: user)
// ============================================================================
$q_user = "SELECT 
                CAST(AES_DECRYPT(id_user, 'nur') AS CHAR) as id_user, 
                CAST(AES_DECRYPT(password, 'windi') AS CHAR) as password,
                inacbg_klaim_baru_manual, 
                inacbg_klaim_baru_manual2, 
                inacbg_klaim_baru_otomatis
            FROM user 
            WHERE AES_DECRYPT(id_user, 'nur') = '$username' LIMIT 1";

$r_user = mysqli_query($koneksi, $q_user);

if ($r_user && mysqli_num_rows($r_user) > 0) {
    $row = mysqli_fetch_assoc($r_user);
    
    // Bandingkan password
    if ($row['password'] === $password_raw) {
        // Cek Hak Akses Casemix (Salah satu harus true)
        if ($row['inacbg_klaim_baru_manual'] == 'true' || 
            $row['inacbg_klaim_baru_manual2'] == 'true' || 
            $row['inacbg_klaim_baru_otomatis'] == 'true') {
            
            // Login Berhasil Sebagai Petugas
            session_regenerate_id(true);
            $_SESSION['casemix_login'] = true;
            $_SESSION['casemix_user']  = $username;
            $_SESSION['casemix_role']  = 'Petugas Casemix';
            
            header("Location: dashboard.php");
            exit;
        } else {
            // Password benar, tapi tidak punya hak akses
            header("Location: index.php?pesan=noaccess");
            exit;
        }
    }
}

// Jika sampai sini, berarti Gagal (Username tidak ada atau Password salah)
header("Location: index.php?pesan=gagal");
mysqli_close($koneksi);
?>