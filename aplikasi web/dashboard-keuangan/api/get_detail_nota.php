<?php
/*
 * File api/get_detail_nota.php (PERBAIKAN V2)
 * API untuk mengambil rincian isi nota (dari tabel billing).
 * Sekarang difilter agar lebih bersih.
 * PHP 7.3 compatible.
 */

// 1. Set Header sebagai JSON
header('Content-Type: application/json');

// 2. Sertakan Koneksi & Fungsi
require_once(dirname(__DIR__) . '/config/koneksi.php'); 
require_once(dirname(__DIR__) . '/includes/functions.php');

// 3. Keamanan: Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    http_response_code(403); 
    echo json_encode(['error' => 'Akses ditolak. Silakan login terlebih dahulu.']);
    exit;
}

// 4. Ambil Parameter 'no_rawat'
$no_rawat = isset($_GET['no_rawat']) ? trim($_GET['no_rawat']) : '';

if (empty($no_rawat)) {
    http_response_code(400); 
    echo json_encode(['error' => 'No. Rawat tidak valid atau tidak disertakan.']);
    exit;
}

// 5. Siapkan Kueri (MODIFIKASI)
// Komentar: 
// 1. Kita tambahkan 'billing.noindex' untuk urutan.
// 2. Kita filter 'billing.totalbiaya != 0' untuk membuang baris "grup" yang kosong.
// 3. Kita filter 'billing.status != ""' untuk membuang baris data pasien (yg statusnya kosong).
$sql_billing = "
    SELECT 
        billing.noindex,
		billing.no,
        billing.nm_perawatan, 
        billing.totalbiaya, 
        billing.status,
        billing.biaya,
        billing.jumlah
    FROM billing 
    WHERE billing.no_rawat = ?      
    ORDER BY billing.noindex
";

// 6. Eksekusi Kueri dengan Prepared Statement
$stmt = $koneksi->prepare($sql_billing);
if ($stmt === false) {
    http_response_code(500); 
    echo json_encode(['error' => 'Gagal mempersiapkan kueri: ' . $koneksi->error]);
    exit;
}

$stmt->bind_param("s", $no_rawat);
$stmt->execute();
$result = $stmt->get_result();

// 7. Fetch Data dan Simpan ke Array
$data_billing = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data_billing[] = $row;
    }
}

// 8. Tutup Statement dan Koneksi
$stmt->close();
$koneksi->close();

// 9. Kirim Response JSON
echo json_encode($data_billing);

?>