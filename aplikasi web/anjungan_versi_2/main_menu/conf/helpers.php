<?php
// Fungsi untuk masking nama pasien
function sensorNama($nama) {
    $len = strlen($nama);
    if($len <= 2) return $nama; 
    return substr($nama,0,1) . str_repeat("*",$len-2) . substr($nama,-1);
}

// Fungsi umum lain bisa ditambahkan di sini
function hitungUmur($tgl_lahir) {
    $lahir = new DateTime($tgl_lahir);
    $today = new DateTime();
    $umur = $today->diff($lahir);
    return $umur->y . " th";
}

/* --- Fungsi pembersih nama pasien --- */
function cleanNamaPasien($nama) {
    // Hilangkan spasi, koma, titik di depan/belakang
    $nama = trim($nama, " ,.");

    // Hilangkan embel-embel di depan atau belakang (dengan titik opsional)
    $nama = preg_replace(
        '/^(AN|TN|BY|NY|NN|H|HJ|SDR)\.?(\s+)?|\s*(AN|TN|BY|NY|NN|H|HJ|SDR)\.?$/i',
        '',
        $nama
    );

    // Bersihkan lagi sisa tanda baca di ujung
    $nama = trim($nama, " ,.");

    return $nama;
}

?>
