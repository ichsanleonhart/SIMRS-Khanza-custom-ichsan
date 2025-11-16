<?php
// File: get_panggilan_display.php
require_once('conf.php'); 

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
header("Cache-Control: no-store, no-cache, must-revalidate"); 
header("Pragma: no-cache"); 

/* --- FUNGSI PENERJEMAH GELAR (LENGKAP) --- */
function terjemahkanGelar($nama) {
    
    // --- (PERBAIKAN) BAGIAN 1: Membersihkan & Menangani Suffix Gelar (Tn, Ny, Nn, An) ---
    
    // Bersihkan spasi di awal/akhir
    $nama_bersih = trim($nama);
    $nama_hasil = $nama_bersih; // Default
    
    // Pola: [Nama Apa Saja], [Gelar] (Tahan banting terhadap spasi dan titik)
    // Regex ini saya buat lebih 'longgar' di akhir string
    if (preg_match('/^(.+?)\s*,\s*(Tn|Nn|Ny|An)\.?\s*$/i', $nama_bersih, $matches)) {
        // $matches[1] = "CICIH JUMASIH" (Nama)
        // $matches[2] = "NY" (Gelar)
        
        $nama_utama = trim($matches[1]);
        $gelar = strtolower(trim($matches[2]));
        $gelar_terjemahan = '';
        
        switch ($gelar) {
            case 'tn': $gelar_terjemahan = 'Tuan'; break;
            case 'ny': $gelar_terjemahan = 'Nyonya'; break;
            case 'nn': $gelar_terjemahan = 'Nona'; break;
            case 'an': $gelar_terjemahan = 'Anak'; break;
        }
        
        if (!empty($gelar_terjemahan)) {
            // Hasil: "Nyonya CICIH JUMASIH"
            $nama_hasil = $gelar_terjemahan . ' ' . $nama_utama;
        }
    }
    // --- AKHIR BAGIAN 1 ---

    // --- BAGIAN 2: Menangani Prefiks (dr.) dan Suffix Akademik (S.Kom) ---
    // Daftar pencarian ini TETAP diperlukan untuk gelar non-suffix (dr, S.Kom, dll)
    $pencarian = array(
        'Ny. ', 'Nn. ', 'An. ', 'Tn. ', 'Sdr. ', 'Sdrn. ', 
        'dr. ', 'Dr. ', 'H. ', 'Hj. ', 'Ir. ', 'Prof. ', 'Pdt. ', 'By. ',
        ', SE', ', S.E', ', S.E.', ', S.Kom', ', S.Kom.', ', A.Md', ', A.Md.', ', Amd',
        ', S.Kep', ', S.Kep.', ', Ns', ', Ns.', ', S.Pd', ', S.Pd.', ', S.H', ', S.H.',
        ', M.Si', ', M.Si.', ', M.Hum', ', M.Hum.', ', M.Kes', ', M.Kes.'
        // ... (Tambahkan gelar akademik lainnya jika perlu) ...
    );
    $pengganti = array(
        'Nyonya ', 'Nona ', 'Anak ', 'Tuan ', 'Saudara ', 'Saudari ', 
        'Dokter ', 'Doktor ', 'Haji ', 'Hajjah ', 'Insinyur ', 'Profesor ', 'Pendeta ', 'Bayi ',
        ', Es E', ', Es E', ', Es E', ', Es Kom', ', Es Kom', ', A Em De', ', A Em De', ', A Em De',
        ', Es Kep', ', Es Kep', ', Ners', ', Ners', ', Es Pe De', ', Es Pe De', ', Es Ha', ', Es Ha',
        ', Em Es I', ', Em Es I', ', Em Hum', ', Em Hum', ', Em Kes', ', Em Kes.'
    );
    
    // Terapkan penggantian:
    // "Nyonya CICIH JUMASIH" -> (tidak ada perubahan)
    // "dr. ICHSAn" -> "Dokter ICHSAn"
    $nama_hasil = str_ireplace($pencarian, $pengganti, $nama_hasil);
    

    // --- (PERBAIKAN) BAGIAN 3: Konversi ke Title Case (Paling Penting!) ---
    // Ini adalah kunci untuk memperbaiki bug "ejaan" ALL CAPS
    // 'UTF-8' penting untuk menangani berbagai karakter nama
    
    // "Nyonya CICIH JUMASIH" -> "Nyonya Cicih Jumasih"
    // "Dokter ICHSAn" -> "Dokter Ichsan"
    // "(TEST BY IT) MOCHAMMAD" -> "(test By It) Mochammad"
    $nama_final = mb_convert_case($nama_hasil, MB_CASE_TITLE, "UTF-8");
    
    // --- BAGIAN 4: Pembersihan Akhir ---
    // Hapus sisa-sisa titik yang mungkin masih ada
    $nama_final = str_replace('.', '', $nama_final); 
    
    return $nama_final;
}
/* --- AKHIR FUNGSI PENERJEMAH --- */

// --- KODE UTAMA FILE ---
$query_panggil = "SELECT no_resep, no_rawat, nm_pasien, nm_poli, waktu_panggil 
                  FROM antrean_farmasi_panggil 
                  LIMIT 1";
$hasil = bukaquery($query_panggil); 
$data = mysqli_fetch_assoc($hasil);

if ($data) {
    // Nama dibersihkan untuk suara
    $nama_untuk_suara = terjemahkanGelar($data['nm_pasien']);
    
    echo '<div class="panggil-box">';
    echo '<h2>PANGGILAN PENYERAHAN OBAT</h2>';
    echo '<span class="nama-pasien">' . htmlspecialchars($data['nm_pasien']) . '</span>'; 
    echo '<span class="detail-pasien">No. Rawat: ' . htmlspecialchars($data['no_rawat']) . ' | Poli: ' . htmlspecialchars($data['nm_poli']) . '</span>';
    echo '</div>';

    // Kirim variabel ke JavaScript di index.php
    // Menggunakan json_encode agar lebih aman dari tanda kutip
    echo '<script>';
    echo 'var namaPanggil = ' . json_encode($nama_untuk_suara) . ';'; 
    echo 'var resepPanggilUnik = ' . json_encode($data['no_resep'] . $data['waktu_panggil']) . ';';
    echo '</script>';
    
} else {
    echo '<div class="panggil-box standby">';
    echo '<h2>ANTREAN FARMASI</h2>';
    echo '<span class="nama-pasien">Silakan Menunggu</span>';
    echo '</div>';
    
    // Kirim variabel kosong
    echo '<script>';
    echo 'var namaPanggil = "";';
    echo 'var resepPanggilUnik = "";';
    echo '</script>';
}
?>