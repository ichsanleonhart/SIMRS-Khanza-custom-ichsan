<?php
session_start();
require_once('../../conf/conf.php');

// Jika sudah login, lempar ke index
if (isset($_SESSION['jadwal_login']) && $_SESSION['jadwal_login'] == true) {
    header("Location: index.php");
    exit();
}

$error = '';
if (isset($_POST['login'])) {
    $user = validTeks($_POST['username']);
    $pass = isset($_POST['password']) ? addslashes($_POST['password']) : '';

    if (empty($user) || empty($pass)) {
        $error = "Username dan Password tidak boleh kosong.";
    } else {
        // 1. CEK ADMIN UTAMA (Tabel admin)
        $q_admin = "SELECT AES_DECRYPT(usere, 'nur') as usere FROM admin 
                    WHERE usere = AES_ENCRYPT('$user', 'nur') 
                    AND passworde = AES_ENCRYPT('$pass', 'windi')";
        $r_admin = bukaquery($q_admin);

        if (mysqli_num_rows($r_admin) > 0) {
            $_SESSION['jadwal_login'] = true;
            $_SESSION['jadwal_user'] = $user;
            $_SESSION['jadwal_level'] = 'admin';
            $_SESSION['jadwal_dep'] = 'ALL'; 
            header("Location: index.php");
            exit();
        } else {
            // 2. CEK PEGAWAI (Tabel user dengan Enkripsi)
            // FIX: Menggunakan Enkripsi id_user ('nur') dan password ('windi')
            // Serta filter jadwal_pegawai='true' sesuai request.
            
            $q_user = "SELECT AES_DECRYPT(id_user, 'nur') as nik 
                       FROM user 
                       WHERE id_user = AES_ENCRYPT('$user', 'nur') 
                       AND password = AES_ENCRYPT('$pass', 'windi')
                       AND jadwal_pegawai = 'true'";
            
            $r_user = bukaquery($q_user);
            $row_user = mysqli_fetch_assoc($r_user);

            if ($row_user) {
                // Login Sukses, kita dapatkan NIK hasil dekripsi
                $nik_valid = $row_user['nik'];
                
                // 3. AMBIL DATA JABATAN & DEPARTEMEN (Tabel pegawai)
                $q_peg = "SELECT id, nik, nama, departemen, jbtn 
                          FROM pegawai 
                          WHERE nik = '$nik_valid'";
                
                $r_peg = bukaquery($q_peg);
                $cek_peg = mysqli_fetch_assoc($r_peg);

                if ($cek_peg) {
                    $_SESSION['jadwal_login'] = true;
                    $_SESSION['jadwal_user'] = $cek_peg['nik'];
                    $_SESSION['jadwal_level'] = 'pegawai';
                    
                    // --- LOGIKA SUPER AKSES (HRD / DIREKSI) ---
                    $super_keywords = ['HRD', 'Direktur']; 
                    $akses_all = false;
                    
                    // Cek jabatan mengandung kata kunci?
                    foreach ($super_keywords as $keyword) {
                        if (stripos($cek_peg['jbtn'], $keyword) !== false) {
                            $akses_all = true;
                            break;
                        }
                    }

                    if ($akses_all) {
                        $_SESSION['jadwal_dep'] = 'ALL'; // KUNCI EMAS
                    } else {
                        $_SESSION['jadwal_dep'] = $cek_peg['departemen']; // KUNCI BIASA
                    }
                    
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Login berhasil, tapi data pegawai dengan NIK $nik_valid tidak ditemukan.";
                }
            } else {
                $error = "Username/Password salah atau Anda tidak memiliki hak akses jadwal.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Jadwal - RSIA Dian</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #0f172a; }
    </style>
</head>
<body class="flex items-center justify-center h-screen">

    <div class="w-full max-w-sm bg-slate-800 rounded-lg shadow-2xl overflow-hidden border border-slate-700">
        <div class="p-8">
            <h2 class="text-2xl font-bold text-center text-white mb-2">Login Jadwal</h2>
            <p class="text-center text-slate-400 text-sm mb-6">Masuk untuk mengelola jadwal dinas</p>
            
            <?php if($error): ?>
                <div class="bg-red-500/20 border border-red-500 text-red-200 p-3 rounded text-sm text-center mb-4">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label class="block text-slate-400 text-xs font-bold mb-2 uppercase">Username / NIK</label>
                    <input type="text" name="username" class="w-full bg-slate-900 text-white border border-slate-600 rounded px-3 py-3 focus:outline-none focus:border-blue-500" required autocomplete="off">
                </div>
                <div class="mb-6">
                    <label class="block text-slate-400 text-xs font-bold mb-2 uppercase">Password</label>
                    <input type="password" name="password" class="w-full bg-slate-900 text-white border border-slate-600 rounded px-3 py-3 focus:outline-none focus:border-blue-500" required>
                </div>
                <button type="submit" name="login" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded transition">MASUK</button>
            </form>
        </div>

        <div class="bg-slate-950 p-4 border-t border-slate-700">
            <div class="grid grid-cols-2 gap-3 text-center">
                <a href="../hrd/login.php" class="text-xs font-bold text-purple-400 hover:text-white border border-purple-900 hover:bg-purple-900/50 py-2 rounded transition">
                    <span class="block text-[10px] text-slate-500 font-normal">Ke Portal</span>
                    ADMIN HRD
                </a>
                <a href="../index.php" class="text-xs font-bold text-green-400 hover:text-white border border-green-900 hover:bg-green-900/50 py-2 rounded transition">
                    <span class="block text-[10px] text-slate-500 font-normal">Ke Mesin</span>
                    ABSEN WAJAH
                </a>
            </div>
        </div>
    </div>

</body>
</html>