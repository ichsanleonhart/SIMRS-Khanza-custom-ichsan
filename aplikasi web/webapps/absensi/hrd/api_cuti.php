<?php
session_start();
require_once('../../conf/conf.php');
header('Content-Type: application/json');

if (!isset($_SESSION['hrd_login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi habis']);
    exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$konektor = bukakoneksi();

// --- 1. LIST PENGAJUAN CUTI (FILTERED) ---
if ($act == 'list') {
    $tgl_mulai   = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : '';
    $tgl_akhir   = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '';
    $filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'cuti';
    $status_l1   = isset($_GET['status_atasan']) ? $_GET['status_atasan'] : 'ALL';
    $status_l2   = isset($_GET['status_hrd']) ? $_GET['status_hrd'] : 'ALL';

    $where = " WHERE 1=1 ";

    if (!empty($tgl_mulai) && !empty($tgl_akhir)) {
        if ($filter_type == 'cuti') {
            $where .= " AND (pc.tanggal_awal BETWEEN '$tgl_mulai' AND '$tgl_akhir') ";
        } else {
            $where .= " AND (pc.tanggal BETWEEN '$tgl_mulai' AND '$tgl_akhir') ";
        }
    }

    if ($status_l1 != 'ALL') {
        if ($status_l1 == 'Proses Pengajuan') {
            $where .= " AND (pc.status = 'Proses Pengajuan' OR pc.status IS NULL OR pc.status = '') ";
        } else {
            $where .= " AND pc.status = '$status_l1' ";
        }
    }

    if ($status_l2 != 'ALL') {
        if ($status_l2 == 'Proses Pengajuan') {
            $where .= " AND (pc.status_persetujuan_HRD = 'Proses Pengajuan' OR pc.status_persetujuan_HRD IS NULL OR pc.status_persetujuan_HRD = '') ";
        } else {
            $where .= " AND pc.status_persetujuan_HRD = '$status_l2' ";
        }
    }

    $sql = "SELECT pc.*, p.nama, p.departemen, d.nama as nama_dep 
            FROM pengajuan_cuti pc
            JOIN pegawai p ON pc.nik = p.nik
            LEFT JOIN departemen d ON p.departemen = d.dep_id
            $where
            ORDER BY pc.tanggal DESC";
            
    $hasil = bukaquery($sql);
    $data = [];
    while($r = mysqli_fetch_assoc($hasil)) {
        $data[] = $r;
    }
    
    echo json_encode(['data' => $data]);
    exit;
}

// --- 2. APPROVAL HRD ---
elseif ($act == 'approve') {
    $no_pengajuan = validTeks($_POST['no_pengajuan']);
    $status_hrd   = validTeks($_POST['status']); 
    
    if(!in_array($status_hrd, ['Disetujui', 'Ditolak'])) {
         echo json_encode(['status'=>'error', 'message'=>'Status tidak valid']);
         exit;
    }

    $data_cuti = fetch_assoc("SELECT * FROM pengajuan_cuti WHERE no_pengajuan='$no_pengajuan'");
    if(!$data_cuti) {
        echo json_encode(['status'=>'error', 'message'=>'Data pengajuan tidak ditemukan']);
        exit;
    }

    $pesan_tambahan = "";

    if ($status_hrd == 'Disetujui') {
        $sql_update = "UPDATE pengajuan_cuti SET 
                       status = 'Disetujui', 
                       waktu_disetujui_atasan = NOW(),
                       status_persetujuan_HRD = 'Disetujui', 
                       waktu_disetujui_HRD = NOW() 
                       WHERE no_pengajuan='$no_pengajuan'";
                       
        // Panggil fungsi update jadwal dan tangkap hasilnya
        $hasil_update = update_jadwal_otomatis($data_cuti['nik'], $data_cuti['tanggal_awal'], $data_cuti['tanggal_akhir']);
        $pesan_tambahan = " (" . $hasil_update . ")";
        
    } else {
        $sql_update = "UPDATE pengajuan_cuti SET 
                       status_persetujuan_HRD = 'Ditolak', 
                       waktu_disetujui_HRD = NOW() 
                       WHERE no_pengajuan='$no_pengajuan'";
    }

    if(mysqli_query($konektor, $sql_update)) {
        echo json_encode(['status'=>'success', 'message'=>"Pengajuan telah $status_hrd." . $pesan_tambahan]);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Gagal update DB: '.mysqli_error($konektor)]);
    }
    exit;
}

// --- HELPER: UPDATE JADWAL (DIPERBAIKI) ---
function update_jadwal_otomatis($nik, $tgl_awal, $tgl_akhir) {
    global $konektor;
    
    // 1. Ambil ID Pegawai
    $peg = fetch_assoc("SELECT id FROM pegawai WHERE nik='$nik'");
    if(!$peg) return "Pegawai tidak ditemukan";
    $id_peg = $peg['id'];

    $begin = new DateTime($tgl_awal);
    $end   = new DateTime($tgl_akhir);
    $end->modify('+1 day'); 

    $interval = DateInterval::createFromDateString('1 day');
    $period   = new DatePeriod($begin, $interval, $end);

    $count_success = 0;
    $count_failed = 0;

    foreach ($period as $dt) {
        $tahun = $dt->format("Y");
        
        // FIX: Gunakan 'n' agar bulan 1-9 tidak ada angka 0 di depan (1, 2, ... 12)
        // Sesuai standar Khanza integer
        $bulan = $dt->format("n"); 
        
        $hari  = $dt->format("j"); // 1-31
        $kolom = "h" . $hari;      // h1 - h31

        // Cek apakah baris jadwal sudah dibuat oleh admin?
        // Kita cek 2 kemungkinan format bulan (String '01' atau Int 1) agar aman
        $cek = fetch_assoc("SELECT id FROM jadwal_pegawai WHERE id='$id_peg' AND tahun='$tahun' AND (bulan='$bulan' OR bulan='0$bulan')");
        
        if($cek) {
            // Lakukan Update
            $q = "UPDATE jadwal_pegawai SET $kolom='Cuti' WHERE id='$id_peg' AND tahun='$tahun' AND (bulan='$bulan' OR bulan='0$bulan')";
            if(mysqli_query($konektor, $q)) {
                $count_success++;
            }
        } else {
            $count_failed++; // Jadwal bulan tersebut belum dibuat
        }
        
        // Update Jadwal Tambahan (Opsional)
        $cek2 = fetch_assoc("SELECT id FROM jadwal_tambahan WHERE id='$id_peg' AND tahun='$tahun' AND (bulan='$bulan' OR bulan='0$bulan')");
        if($cek2) {
             mysqli_query($konektor, "UPDATE jadwal_tambahan SET $kolom='Cuti' WHERE id='$id_peg' AND tahun='$tahun' AND (bulan='$bulan' OR bulan='0$bulan')");
        }
    }

    if ($count_failed > 0) {
        return "Update $count_success hari. Gagal $count_failed hari (Jadwal blm dibuat).";
    } else {
        return "Update jadwal sukses untuk $count_success hari.";
    }
}
?>