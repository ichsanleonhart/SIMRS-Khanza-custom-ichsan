<?php
/*
 * File: ajax_get_targets.php
 * Fungsi: Mencari pasien yang punya berkas digital di rentang tgl closing
 */
session_start();
require_once('../conf/conf.php');
$koneksi = bukakoneksi();

header('Content-Type: application/json');

$tgl_awal  = isset($_POST['tgl_awal']) ? $_POST['tgl_awal'] : date('Y-m-d');
$tgl_akhir = isset($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : date('Y-m-d');

// Query cari pasien closing yg PUNYA berkas digital (INNER JOIN)
$query = "SELECT rp.no_rawat, p.nm_pasien
          FROM reg_periksa rp
          JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
          -- Cek Closing Rawat Jalan / Inap
          LEFT JOIN nota_jalan nj ON rp.no_rawat = nj.no_rawat
          LEFT JOIN nota_inap ni ON rp.no_rawat = ni.no_rawat
          -- Hanya ambil yang punya berkas
          INNER JOIN berkas_digital_perawatan bdp ON rp.no_rawat = bdp.no_rawat
          WHERE COALESCE(ni.tanggal, nj.tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
          GROUP BY rp.no_rawat";

$hasil = mysqli_query($koneksi, $query);
$data = [];

if($hasil) {
    while($row = mysqli_fetch_assoc($hasil)) {
        $data[] = [
            'no_rawat' => $row['no_rawat'],
            'nm_pasien' => $row['nm_pasien']
        ];
    }
    echo json_encode(['status' => 'success', 'data' => $data]);
} else {
    echo json_encode(['status' => 'error', 'message' => mysqli_error($koneksi)]);
}
?>