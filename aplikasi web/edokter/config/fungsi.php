<?php
// config/fungsi.php

// 1. Fungsi Redirect
function redirect($url) {
    echo "<script>window.location.href='$url';</script>";
    exit();
}

// 2. Fungsi Format Rupiah
function format_rupiah($angka){
    return "Rp " . number_format($angka, 0, ',', '.');
}

// 3. Fungsi Tanggal Indo
function tanggal_indo($tgl){
    $bulan = array (
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $pecahkan = explode('-', $tgl);
    // Error handling jika format tanggal salah/kosong
    if(count($pecahkan) < 3) return $tgl; 
    
    return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

// 4. Fungsi Deteksi Shift (Untuk Widget Dashboard)
function get_current_shift() {
    date_default_timezone_set('Asia/Jakarta');
    $jam = date('H:i:s');
    $now = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime("-1 day"));
    $tomorrow = date('Y-m-d', strtotime("+1 day"));

    // Shift Pagi: 07:00 - 14:00
    if ($jam >= '07:00:00' && $jam < '14:00:00') {
        return [
            'nama' => 'Pagi',
            'start' => "$now 07:00:00",
            'end'   => "$now 13:59:59"
        ];
    }
    // Shift Siang: 14:00 - 21:00
    elseif ($jam >= '14:00:00' && $jam < '21:00:00') {
        return [
            'nama' => 'Siang',
            'start' => "$now 14:00:00",
            'end'   => "$now 20:59:59"
        ];
    }
    // Shift Malam: 21:00 - 07:00 (Lintas Hari)
    else {
        if ($jam >= '21:00:00') {
            return [
                'nama' => 'Malam',
                'start' => "$now 21:00:00",
                'end'   => "$tomorrow 06:59:59"
            ];
        } else {
            return [
                'nama' => 'Malam',
                'start' => "$yesterday 21:00:00",
                'end'   => "$now 06:59:59"
            ];
        }
    }
}


// 5. CONFIG USER SPESIAL (VIP LIST)
// Masukkan username login khanza mereka di sini
function get_vip_users() {
    return [
		'000582',   // Contoh username direktur
        'direktur',   // Contoh username direktur
        'hrd',        // Contoh username HRD
        'yanmed',     // Contoh username Yanmed
        'admin_keuangan' // Tambahkan sesuai kebutuhan
    ];
}
?>