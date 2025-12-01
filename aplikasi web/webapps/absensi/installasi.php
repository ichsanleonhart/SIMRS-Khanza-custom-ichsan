<?php
// Setting agar proses berat tidak timeout
set_time_limit(0);
ini_set('memory_limit', '-1');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Style CSS untuk log yang enak dibaca
echo "
<style>
    body { background: #1e1e2e; color: #cdd6f4; font-family: 'Consolas', monospace; padding: 20px; font-size: 14px; }
    .box { background: #313244; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 5px solid #585b70; }
    .success { border-left-color: #a6e3a1; color: #a6e3a1; }
    .warning { border-left-color: #f9e2af; color: #f9e2af; }
    .error { border-left-color: #f38ba8; color: #f38ba8; }
    .info { border-left-color: #89b4fa; color: #89b4fa; }
    h3 { border-bottom: 1px solid #45475a; padding-bottom: 10px; margin-top: 0; color: #cba6f7; }
    .btn { display: inline-block; padding: 10px 20px; background: #89b4fa; color: #1e1e2e; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; }
    .btn:hover { background: #b4befe; }
</style>
";

echo "<h1>🚀 DEPLOYMENT & PERBAIKAN DATABASE (V4.2)</h1>";

// 1. KONEKSI DATABASE
$conf_path = '../conf/conf.php';
if (!file_exists($conf_path)) {
    die("<div class='box error'><h3>FATAL ERROR</h3>File konfigurasi database (../conf/conf.php) tidak ditemukan.<br>Pastikan file ini berada di folder /webapps/absensi/</div>");
}
require_once($conf_path);
$konektor = bukakoneksi();
if (!$konektor) die("<div class='box error'><h3>DB CONNECTION ERROR</h3>Gagal koneksi ke database Khanza.</div>");

// Fungsi Helper
function cekKolom($table, $column) {
    global $konektor;
    $res = mysqli_query($konektor, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return (mysqli_num_rows($res) > 0);
}

function execute($sql, $msg) {
    global $konektor;
    if (mysqli_query($konektor, $sql)) {
        echo "<div class='box success'>[OK] $msg</div>";
    } else {
        echo "<div class='box error'>[GAGAL] $msg <br>Error: " . mysqli_error($konektor) . "</div>";
    }
}

// =================================================================================
// MODUL 1: TABEL FACE_ENROLLMENT (Jantung Aplikasi)
// =================================================================================
echo "<h3>1. Tabel Biometrik Wajah (Face Enrollment)</h3>";

// A. Buat Tabel jika belum ada
$sql_create = "CREATE TABLE IF NOT EXISTS `face_enrollment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `nik` varchar(20) NOT NULL,
  `face_descriptor` longtext NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `nik` (`nik`),
  CONSTRAINT `fk_face_pegawai_new` FOREIGN KEY (`user_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if(mysqli_query($konektor, $sql_create)) {
    echo "<div class='box success'>[OK] Cek/Buat Tabel `face_enrollment` selesai.</div>";
} else {
    echo "<div class='box error'>[GAGAL] Buat tabel `face_enrollment`: " . mysqli_error($konektor) . "</div>";
}

// B. Cek Kelengkapan Kolom (Anti-Serangan Jantung)
// Ini mengatasi masalah tabel sudah ada (versi lama) tapi kolom NIK/Photo belum ada.

if (!cekKolom('face_enrollment', 'nik')) {
    execute("ALTER TABLE `face_enrollment` ADD COLUMN `nik` VARCHAR(20) NOT NULL AFTER `user_id`, ADD INDEX (`nik`)", "Menambahkan kolom `nik` yang hilang");
} else {
    echo "<div class='box info'>[INFO] Kolom `nik` di `face_enrollment` sudah ada.</div>";
}

if (!cekKolom('face_enrollment', 'photo')) {
    execute("ALTER TABLE `face_enrollment` ADD COLUMN `photo` VARCHAR(255) DEFAULT NULL AFTER `face_descriptor`", "Menambahkan kolom `photo` yang hilang");
} else {
    echo "<div class='box info'>[INFO] Kolom `photo` di `face_enrollment` sudah ada.</div>";
}

// =================================================================================
// MODUL 2: MODIFIKASI TABEL BAWAAN (Inject Kolom Foto)
// =================================================================================
echo "<h3>2. Modifikasi Tabel Presensi Khanza</h3>";

// Cek temporary_presensi
if (!cekKolom('temporary_presensi', 'photo')) {
    execute("ALTER TABLE `temporary_presensi` ADD COLUMN `photo` VARCHAR(500) DEFAULT NULL", "Inject kolom `photo` ke `temporary_presensi`");
} else {
    echo "<div class='box info'>[INFO] Kolom `photo` di `temporary_presensi` sudah ada.</div>";
}

// Cek rekap_presensi
if (!cekKolom('rekap_presensi', 'photo')) {
    execute("ALTER TABLE `rekap_presensi` ADD COLUMN `photo` VARCHAR(500) DEFAULT NULL", "Inject kolom `photo` ke `rekap_presensi`");
} else {
    echo "<div class='box info'>[INFO] Kolom `photo` di `rekap_presensi` sudah ada.</div>";
}

// =================================================================================
// MODUL 3: UPDATE ENUM JADWAL (Libur & Cuti)
// =================================================================================
echo "<h3>3. Update ENUM Jadwal (Support Libur & Cuti)</h3>";
echo "<div class='box warning'>Proses ini mungkin memakan waktu beberapa detik/menit...</div>";

// Daftar Enum Lengkap
$enum_list = "'Pagi','Pagi2','Pagi3','Pagi4','Pagi5','Pagi6','Pagi7','Pagi8','Pagi9','Pagi10'," .
             "'Siang','Siang2','Siang3','Siang4','Siang5','Siang6','Siang7','Siang8','Siang9','Siang10'," .
             "'Malam','Malam2','Malam3','Malam4','Malam5','Malam6','Malam7','Malam8','Malam9','Malam10'," .
             "'Midle Pagi1','Midle Pagi2','Midle Pagi3','Midle Pagi4','Midle Pagi5','Midle Pagi6','Midle Pagi7','Midle Pagi8','Midle Pagi9','Midle Pagi10'," .
             "'Midle Siang1','Midle Siang2','Midle Siang3','Midle Siang4','Midle Siang5','Midle Siang6','Midle Siang7','Midle Siang8','Midle Siang9','Midle Siang10'," .
             "'Midle Malam1','Midle Malam2','Midle Malam3','Midle Malam4','Midle Malam5','Midle Malam6','Midle Malam7','Midle Malam8','Midle Malam9','Midle Malam10'," .
             "'','Libur','Cuti'"; 

$jadwal_tables = ['jadwal_pegawai', 'jadwal_tambahan'];

foreach ($jadwal_tables as $tbl) {
    // Cek apakah tabel ada
    $cek_tbl = mysqli_query($konektor, "SHOW TABLES LIKE '$tbl'");
    if (mysqli_num_rows($cek_tbl) > 0) {
        // Kita cek sample kolom h1 dulu, apakah sudah ada enum Libur?
        // Kalau sudah ada, skip aja biar cepet.
        $col_info = mysqli_fetch_assoc(mysqli_query($konektor, "SHOW COLUMNS FROM `$tbl` LIKE 'h1'"));
        if (strpos($col_info['Type'], "'Libur'") !== false && strpos($col_info['Type'], "'Cuti'") !== false) {
            echo "<div class='box info'>[INFO] Tabel `$tbl` sudah support Libur/Cuti. Skip update.</div>";
        } else {
            // Lakukan Update Massal
            $alters = [];
            for ($i = 1; $i <= 31; $i++) {
                $alters[] = "MODIFY COLUMN `h$i` ENUM($enum_list) DEFAULT ''";
            }
            $sql_alter = "ALTER TABLE `$tbl` " . implode(", ", $alters);
            execute($sql_alter, "Update struktur ENUM tabel `$tbl`");
        }
    } else {
        echo "<div class='box error'>[SKIP] Tabel `$tbl` tidak ditemukan di database.</div>";
    }
}

// =================================================================================
// MODUL 4: FILE SYSTEM CHECK
// =================================================================================
echo "<h3>4. Cek Folder & Permission</h3>";

$dirs = ['photo_enrollment', 'foto_absen'];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "<div class='box success'>[OK] Folder `$dir` berhasil dibuat.</div>";
        } else {
            echo "<div class='box error'>[GAGAL] Tidak bisa membuat folder `$dir`. Cek permission server!</div>";
        }
    } else {
        // Coba chmod lagi untuk memastikan
        if (@chmod($dir, 0777)) {
            echo "<div class='box success'>[OK] Folder `$dir` ada dan Writable.</div>";
        } else {
            echo "<div class='box warning'>[WARN] Folder `$dir` ada tapi gagal set permission 777. Pastikan webserver bisa menulis.</div>";
        }
    }
}

echo "<hr>";
echo "<center>
        <h2 style='color:#a6e3a1'>🎉 INSTALASI / PERBAIKAN SELESAI 🎉</h2>
        <p>Sistem Absensi Wajah V4.2 siap digunakan.</p>
        <a href='index.php' class='btn'>BUKA APLIKASI</a>
      </center>";

mysqli_close($konektor);
?>