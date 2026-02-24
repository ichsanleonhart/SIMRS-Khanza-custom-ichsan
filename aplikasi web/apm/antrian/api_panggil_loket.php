<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Jakarta');
include_once '../conf/conf.php';
header('Content-Type: application/json; charset=utf-8');

$conn = bukakoneksi();
$action = $_GET['action'] ?? '';
$loket = $_GET['loket'] ?? '1';
$tanggal = date('Y-m-d');
$waktu_sekarang = date('H:i:s');

// Fungsi untuk menyimpan trigger ke file JSON agar dibaca oleh TV
function simpanTriggerTV($nomor, $loket) {
    $data = [
        'nomor' => $nomor,
        'loket' => $loket,
        'timestamp' => microtime(true) // gunakan microtime agar panggil ulang selalu terdeteksi
    ];
    file_put_contents(__DIR__ . '/trigger_panggilan.json', json_encode($data));
}

if ($action === 'panggil_next') {
    $sql_cari = "SELECT a.nomor, a.jam as jam_cetak 
                 FROM antriloketcetak a
                 LEFT JOIN antrian_loket l ON a.nomor = l.noantrian AND l.postdate = '$tanggal' AND l.type = 'admisi'
                 WHERE a.tanggal = '$tanggal' AND l.noantrian IS NULL
                 ORDER BY a.nomor ASC LIMIT 1";
    
    $res_cari = mysqli_query($conn, $sql_cari);
    
    if ($row = mysqli_fetch_assoc($res_cari)) {
        $nomor_baru = $row['nomor'];
        $jam_cetak = $row['jam_cetak'];
        
        $sql_insert = "INSERT INTO antrian_loket (type, noantrian, postdate, start_time, end_time, noka) 
                       VALUES ('admisi', '$nomor_baru', '$tanggal', '$jam_cetak', '$waktu_sekarang', '-')";
        
        if (mysqli_query($conn, $sql_insert)) {
            $sqle_escaped = mysqli_real_escape_string($conn, $sql_insert);
            mysqli_query($conn, "INSERT INTO trackersql (tanggal, sqle, usere) VALUES ('$waktu_sekarang', '$sqle_escaped', 'petugas admisi')");
            
            $start = strtotime($jam_cetak);
            $end = strtotime($waktu_sekarang);
            $selisih_menit = round(($end - $start) / 60);
            
            // Trigger TV
            simpanTriggerTV($nomor_baru, $loket);
            
            echo json_encode([
                'success' => true,
                'nomor' => $nomor_baru,
                'waktu_tunggu' => $selisih_menit . ' menit',
                'pesan' => 'Antrean baru berhasil dipanggil'
            ]);
        } else {
            echo json_encode(['error' => 'Gagal menyimpan data: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['error' => 'Belum ada antrean baru yang bisa dipanggil']);
    }

} elseif ($action === 'panggil_ulang') {
    $sql_last = "SELECT noantrian, start_time, end_time 
                 FROM antrian_loket 
                 WHERE postdate = '$tanggal' AND type = 'admisi' 
                 ORDER BY kd DESC LIMIT 1";
                 
    $res_last = mysqli_query($conn, $sql_last);
    if ($row = mysqli_fetch_assoc($res_last)) {
        $start = strtotime($row['start_time']);
        $end = strtotime($waktu_sekarang); // Hitung s/d waktu saat panggil ulang ditekan
        $selisih_menit = round(($end - $start) / 60);

        // Trigger TV
        simpanTriggerTV($row['noantrian'], $loket);

        echo json_encode([
            'success' => true,
            'nomor' => $row['noantrian'],
            'waktu_tunggu' => $selisih_menit . ' menit (Panggil Ulang)',
            'pesan' => 'Memanggil ulang'
        ]);
    } else {
        echo json_encode(['error' => 'Belum ada antrean yang dipanggil hari ini']);
    }

} elseif ($action === 'get_waiting') {
    // Menampilkan seluruh antrean yang belum dipanggil hari ini
    $sql_wait = "SELECT a.nomor, a.jam 
                 FROM antriloketcetak a
                 LEFT JOIN antrian_loket l ON a.nomor = l.noantrian AND l.postdate = '$tanggal' AND l.type = 'admisi'
                 WHERE a.tanggal = '$tanggal' AND l.noantrian IS NULL
                 ORDER BY a.nomor ASC";
                 
    $res_wait = mysqli_query($conn, $sql_wait);
    $data = [];
    while ($row = mysqli_fetch_assoc($res_wait)) {
        // Hitung perkiraan waktu tunggu berjalan
        $start = strtotime($row['jam']);
        $end = strtotime($waktu_sekarang);
        $row['tunggu_berjalan'] = round(($end - $start) / 60);
        $data[] = $row;
    }
    
    echo json_encode($data);
} else {
    echo json_encode(['error' => 'Aksi tidak valid']);
}

mysqli_close($conn);
?>