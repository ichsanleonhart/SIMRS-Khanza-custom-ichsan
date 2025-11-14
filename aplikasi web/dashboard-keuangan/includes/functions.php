<?php
/*
 * File functions.php
 * Berisi fungsi-fungsi pembantu yang akan sering digunakan.
 * PHP 7.3 compatible.
 */

// 1. Fungsi Format Rupiah
function formatRupiah($angka) {
    $hasil_rupiah = "Rp " . number_format($angka, 0, ',', '.');
    return $hasil_rupiah;
}

// 2. Fungsi Mengambil Jam Shift
function getShiftTimes($koneksi) {
    /*
     * Komentar: Mengambil semua data shift dari 'closing_kasir'
     * dan menyimpannya dalam array asosiatif agar mudah diakses.
     */
    $shifts = [];
    $sql = "SELECT closing_kasir.shift, closing_kasir.jam_masuk, closing_kasir.jam_pulang FROM closing_kasir";
    $result = $koneksi->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $shifts[$row['shift']] = [
                'masuk' => $row['jam_masuk'],
                'pulang' => $row['jam_pulang']
            ];
        }
    }
    return $shifts;
}

// 3. Fungsi Menghitung Rentang DateTime untuk Kueri
function getShiftDateTimeRange($tanggal_str, $shift, $shift_times) {
    /*
     * Komentar: Fungsi ini adalah inti dari logika filter.
     * Ia menghitung rentang datetime Awal dan Akhir,
     * terutama menangani "Shift Malam" yang melewati tengah malam.
     */
     
    if (!isset($shift_times[$shift])) {
        return null; // Shift tidak ditemukan
    }

    $jam_masuk = $shift_times[$shift]['masuk'];
    $jam_pulang = $shift_times[$shift]['pulang'];
    
    $dt_awal_str = $tanggal_str . ' ' . $jam_masuk;
    $dt_akhir_str = $tanggal_str . ' ' . $jam_pulang;

    // Logika khusus untuk Shift Malam (misal: 21:00 - 07:00)
    // Jika jam masuk lebih besar dari jam pulang, berarti lompat hari.
    if (strtotime($jam_masuk) > strtotime($jam_pulang)) {
        
        // Tanggal akhir adalah hari berikutnya
        $tanggal_obj = new DateTime($tanggal_str);
        $tanggal_obj->modify('+1 day');
        $tanggal_akhir_str = $tanggal_obj->format('Y-m-d');
        
        $dt_akhir_str = $tanggal_akhir_str . ' ' . $jam_pulang;
    }

    return [
        'start' => $dt_awal_str,
        'end' => $dt_akhir_str
    ];
}

// (Nanti kita akan tambahkan fungsi-fungsi kueri di sini)

?>