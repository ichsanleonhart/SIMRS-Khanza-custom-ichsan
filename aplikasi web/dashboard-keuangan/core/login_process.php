<?php
/*
 * File login_process.php
 * Memproses data login, mengecek Super Admin dan User Biasa.
 * PHP 7.3 compatible.
 */

// 1. Sertakan file koneksi (yang akan otomatis memulai session)
require_once('../config/koneksi.php');

// 2. Ambil data dari form POST
// Kita gunakan isset() untuk PHP 7.3
$username = isset($_POST['username']) ? $_POST['username'] : '';
$password_input = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($username) || empty($password_input)) {
    header('Location: ../index.php?error=1');
    exit;
}

$login_sukses = false;

// 3. Cek Super Admin (Tabel 'admin')
// Komentar: Kueri ini menggunakan AES_DECRYPT sesuai permintaan Anda.
$sql_admin = "
    SELECT 
        AES_DECRYPT(admin.usere, 'nur') AS usere, 
        AES_DECRYPT(admin.passworde, 'windi') AS passworde 
    FROM admin 
    WHERE AES_DECRYPT(admin.usere, 'nur') = ?
";

// Menggunakan Prepared Statement (MySQLi) untuk keamanan
$stmt_admin = $koneksi->prepare($sql_admin);
$stmt_admin->bind_param("s", $username);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();

if ($result_admin->num_rows === 1) {
    $row_admin = $result_admin->fetch_assoc();
    
    // Komentar: Membandingkan password yang di-dekripsi dengan password inputan
    if ($row_admin['passworde'] === $password_input) {
        $login_sukses = true;
        
        session_regenerate_id(true); // Mencegah session fixation
        $_SESSION['user_id'] = $row_admin['usere'];
        $_SESSION['nama_user'] = 'Super Admin';
        $_SESSION['is_admin'] = true;
    }
}
$stmt_admin->close();


// 4. Jika bukan Super Admin, cek User Biasa (Tabel 'user')
if (!$login_sukses) {
    // Komentar: Kueri ini juga menggunakan AES_DECRYPT dan mengecek hak akses rekap_per_shift
    $sql_user = "
        SELECT 
            AES_DECRYPT(user.id_user, 'nur') AS id_user, 
            AES_DECRYPT(user.password, 'windi') AS password, 
            user.rekap_per_shift 
        FROM user 
        WHERE AES_DECRYPT(user.id_user, 'nur') = ?
    ";

    $stmt_user = $koneksi->prepare($sql_user);
    $stmt_user->bind_param("s", $username);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($result_user->num_rows === 1) {
        $row_user = $result_user->fetch_assoc();
        
        // Komentar: Cek password DAN hak akses
        if ($row_user['password'] === $password_input) {
            
            if ($row_user['rekap_per_shift'] == 'true') {
                $login_sukses = true;
                
                session_regenerate_id(true);
                $_SESSION['user_id'] = $row_user['id_user'];
                
                // Ambil nama petugas/dokter untuk sapaan di dashboard
                $nama_user = $koneksi->query("SELECT petugas.nama FROM petugas WHERE petugas.nip = '" . $koneksi->real_escape_string($row_user['id_user']) . "'")->fetch_assoc();
                if ($nama_user) {
                     $_SESSION['nama_user'] = $nama_user['nama'];
                } else {
                     $_SESSION['nama_user'] = $row_user['id_user'];
                }
                
                $_SESSION['is_admin'] = false; // Ini bukan super admin
            }
        }
    }
    $stmt_user->close();
}

// 5. Finalisasi Login
if ($login_sukses) {
    // Jika login berhasil, arahkan ke dashboard
    header('Location: ../dashboard.php');
    exit;
} else {
    // Jika gagal total, kembalikan ke halaman login
    header('Location: ../index.php?error=1');
    exit;
}

?>