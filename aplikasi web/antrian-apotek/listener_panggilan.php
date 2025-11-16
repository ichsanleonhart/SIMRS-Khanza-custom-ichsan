<?php
// File: listener_panggilan.php
// PERBAIKAN: Memanggil file conf.php lokal
require_once('conf.php'); 

// --- PROSES PANGGILAN BARU (DARI TOMBOL PENYERAHAN DI JAVA) ---
// (Poin 1, 2, 4) Cek tabel trigger 'antriapotek3'
$query_penyerahan = "SELECT * FROM antriapotek3 WHERE status = '1' LIMIT 1";
$hasil_penyerahan = bukaquery($query_penyerahan); 

if ($data = mysqli_fetch_assoc($hasil_penyerahan)) {
    $no_resep = $data['no_resep'];
    $no_rawat = $data['no_rawat'];
    
    // A. (Poin 6) Hapus semua panggilan LAMA dari tabel panggil kita
    bukaquery2("DELETE FROM antrean_farmasi_panggil");
    
    // B. Ambil data pasien untuk resep yang dipanggil
    $data_pasien_sql = "
        SELECT p.nm_pasien, pl.nm_poli 
        FROM reg_periksa r
        JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
        JOIN poliklinik pl ON r.kd_poli = pl.kd_poli
        WHERE r.no_rawat = '".cleankar($no_rawat)."'"; 
    $hasil_pasien = bukaquery($data_pasien_sql);
    $data_pasien = mysqli_fetch_assoc($hasil_pasien);
    
    $nm_pasien = $data_pasien['nm_pasien'] ?? 'Pasien';
    $nm_poli = $data_pasien['nm_poli'] ?? 'Poli';

    // C. (Poin 6) Masukkan panggilan BARU ke tabel panggil kita
    bukaquery2("
        REPLACE INTO antrean_farmasi_panggil 
        (no_resep, no_rawat, nm_pasien, nm_poli, waktu_panggil) 
        VALUES 
        ('".cleankar($no_resep)."', '".cleankar($no_rawat)."', '".cleankar($nm_pasien)."', 
         '".cleankar($nm_poli)."', NOW())
    "); 
    
    // D. (Poin 6) KEMBALIKAN STATUS antriapotek3 ke '0' (Reset trigger)
    bukaquery2("UPDATE antriapotek3 SET status = '0' WHERE no_resep = '".cleankar($no_resep)."'");
    
    echo "Resep $no_resep (Penyerahan) dicatat sebagai PANGGIL.<br>";
}

// 5. (Poin 5) BERSIHKAN DATA PANGGILAN LAMA (Pembersih otomatis jika > 1 menit)
bukaquery2("DELETE FROM antrean_farmasi_panggil WHERE waktu_panggil < (NOW() - INTERVAL 1 MINUTE)");

echo "Listener panggilan selesai.";
?>