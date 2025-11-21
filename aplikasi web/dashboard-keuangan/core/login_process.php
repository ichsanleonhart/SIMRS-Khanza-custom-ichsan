<?php
/*
 * File: core/login_process.php (UPDATE V3 - SUPER ADMIN EXCEPTION)
 * - Super Admin (Tabel admin): Bypass validasi roles.
 * - User Biasa (Tabel user): Wajib validasi ke tabel roles.
 */

session_start();
require_once(dirname(__DIR__) . '/config/koneksi.php');

// 1. Ambil data dari form
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password_input = isset($_POST['password']) ? trim($_POST['password']) : '';

if (empty($username) || empty($password_input)) {
    header('Location: ../index.php?error=1');
    exit;
}

// -------------------------------------------------------------------------
// A. CEK SUPER ADMIN (Tabel 'admin') - BYPASS ROLES
// -------------------------------------------------------------------------
$sql_admin = "
    SELECT 
        AES_DECRYPT(usere, 'nur') as usere, 
        AES_DECRYPT(passworde, 'windi') as passworde 
    FROM admin 
    WHERE AES_DECRYPT(usere, 'nur') = ?
";

$stmt_admin = $koneksi->prepare($sql_admin);
$stmt_admin->bind_param("s", $username);
$stmt_admin->execute();
$res_admin = $stmt_admin->get_result();

if ($res_admin->num_rows === 1) {
    $row_admin = $res_admin->fetch_assoc();
    
    // Verifikasi Password Admin
    if ($row_admin['passworde'] === $password_input) {
        // Login Berhasil sebagai Super Admin
        session_regenerate_id(true);
        $_SESSION['user_id'] = $username;
        $_SESSION['nama_user'] = "Super Admin";
        $_SESSION['is_admin'] = true; // Flag khusus Super Admin
        $_SESSION['role'] = 'Super Admin';
        
        header("Location: ../dashboard.php");
        exit; // Stop eksekusi, jangan cek user lain
    }
}
$stmt_admin->close();


// -------------------------------------------------------------------------
// B. CEK USER BIASA (Tabel 'user') - WAJIB CEK ROLES
// -------------------------------------------------------------------------
$sql_user = "
    SELECT 
        AES_DECRYPT(id_user, 'nur') as id_user, 
        AES_DECRYPT(password, 'windi') as password 
    FROM user 
    WHERE AES_DECRYPT(id_user, 'nur') = ?
";

$stmt_user = $koneksi->prepare($sql_user);
$stmt_user->bind_param("s", $username);
$stmt_user->execute();
$res_user = $stmt_user->get_result();

if ($res_user->num_rows === 1) {
    $row_user = $res_user->fetch_assoc();
    
    // Verifikasi Password User
    if ($row_user['password'] === $password_input) {
        
        // --- VALIDASI ROLE (Hanya untuk User Biasa) ---
        $sql_role = "SELECT role FROM roles WHERE username = ?";
        $stmt_role = $koneksi->prepare($sql_role);
        $stmt_role->bind_param("s", $username);
        $stmt_role->execute();
        $res_role = $stmt_role->get_result();
        
        if ($res_role->num_rows > 0) {
            $data_role = $res_role->fetch_assoc();
            
            // Cek apakah Role adalah Admin
            if ($data_role['role'] === 'Admin') {
                // Login Berhasil sebagai User Admin
                session_regenerate_id(true);
                $_SESSION['user_id'] = $username;
                $_SESSION['role'] = 'Admin';
                $_SESSION['is_admin'] = false; // Bukan Super Admin
                
                // Ambil Nama Asli dari Petugas/Dokter
                $q_nama = $koneksi->query("SELECT nama FROM petugas WHERE nip = '$username'");
                if ($q_nama->num_rows == 0) {
                    $q_nama = $koneksi->query("SELECT nm_dokter as nama FROM dokter WHERE kd_dokter = '$username'");
                }
                
                if ($q_nama && $r_nama = $q_nama->fetch_assoc()) {
                    $_SESSION['nama_user'] = $r_nama['nama'];
                } else {
                    $_SESSION['nama_user'] = $username;
                }
                
                header("Location: ../dashboard.php");
                exit;
            }
        }
        $stmt_role->close();
    }
}
$stmt_user->close();

// -------------------------------------------------------------------------
// C. GAGAL LOGIN
// -------------------------------------------------------------------------
// Jika sampai di sini, berarti tidak ada yang cocok
header("Location: ../index.php?error=1");
exit;
?>