<?php
// api_cek_stok.php
header('Content-Type: application/json');
include 'koneksi.php';

// Ambil kode bangsal dari parameter GET, default 'AP' jika kosong
$kd_bangsal = isset($_GET['depo']) ? $_GET['depo'] : 'AP';

// Query Optimasi:
// 1. Join databarang (info item & stok min) dengan gudangbarang (stok lokasi)
// 2. Filter berdasarkan kd_bangsal yang dipilih
// 3. Filter Non-Batch (no_batch & no_faktur kosong)
// 4. Filter logic: Stok Lokasi <= Stok Minimal Master
$sql = "SELECT 
            d.kode_brng, 
            d.nama_brng, 
            k.satuan, 
            d.stokminimal, 
            g.stok 
        FROM gudangbarang g
        INNER JOIN databarang d ON g.kode_brng = d.kode_brng
        INNER JOIN kodesatuan k ON d.kode_sat = k.kode_sat
        WHERE 
            g.kd_bangsal = ? 
            AND g.no_batch = '' 
            AND g.no_faktur = '' 
            AND d.status = '1'
            AND g.stok <= d.stokminimal
        ORDER BY g.stok ASC";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $kd_bangsal);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Kembalikan data dalam JSON
echo json_encode([
    'jumlah_warning' => count($data),
    'data' => $data
]);
?>