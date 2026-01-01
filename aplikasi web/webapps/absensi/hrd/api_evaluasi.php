<?php
session_start();
require_once('../../conf/conf.php');

if (!isset($_SESSION['hrd_login'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
    exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$konektor = bukakoneksi();

if ($act == 'analyze') {
    $tgl1 = validTeks($_GET['tgl1']);
    $tgl2 = validTeks($_GET['tgl2']);
    $dep  = validTeks($_GET['dep']);
    $filter = validTeks($_GET['filter']); // 'ALL' atau 'MANGKIR'

    // 1. Ambil Referensi Jam Jaga
    $ref_jam = [];
    $q_jam = bukaquery("SELECT dep_id, shift, jam_masuk, jam_pulang FROM jam_jaga");
    while($j = mysqli_fetch_assoc($q_jam)) {
        $ref_jam[$j['dep_id']][$j['shift']] = [
            'in' => substr($j['jam_masuk'], 0, 5),
            'out' => substr($j['jam_pulang'], 0, 5)
        ];
    }

    // 2. Ambil Pegawai
    $filter_dep = ($dep != 'ALL' && $dep != '') ? "AND departemen = '$dep'" : "";
    $q_peg = "SELECT id, nik, nama, departemen FROM pegawai WHERE stts_aktif = 'AKTIF' $filter_dep ORDER BY nama ASC";
    $res_peg = bukaquery($q_peg);
    
    $pegawai_list = [];
    while($p = mysqli_fetch_assoc($res_peg)) {
        $pegawai_list[] = $p;
    }

    // 3. Loop Tanggal
    $data_evaluasi = [];
    $current_date = strtotime($tgl1);
    $end_date = strtotime($tgl2);
    $now_ts = time(); // Waktu server saat ini

    while ($current_date <= $end_date) {
        $date_sql = date('Y-m-d', $current_date);
        $is_today = ($date_sql == date('Y-m-d')); // Cek apakah hari ini
        
        $thn = date('Y', $current_date);
        $bln = date('m', $current_date);
        $hari_angka = date('j', $current_date);
        $col_h = "h" . $hari_angka;

        // Prefetch Data
        $logs_rekap = [];
        $q_log = "SELECT id, jam_datang, jam_pulang, shift, status FROM rekap_presensi WHERE jam_datang LIKE '$date_sql%'";
        $r_log = bukaquery($q_log);
        while($l = mysqli_fetch_assoc($r_log)) $logs_rekap[$l['id']] = $l;

        $logs_temp = [];
        $q_tmp = "SELECT id, jam_datang, shift, status FROM temporary_presensi WHERE jam_datang LIKE '$date_sql%'";
        $r_tmp = bukaquery($q_tmp);
        while($t = mysqli_fetch_assoc($r_tmp)) $logs_temp[$t['id']] = $t;

        foreach ($pegawai_list as $peg) {
            $id_peg = $peg['id'];
            $dep_peg = $peg['departemen'];
            
            // Cek Jadwal
            $jadwal_kode = '-';
            $q_add = fetch_assoc("SELECT $col_h as shift FROM jadwal_tambahan WHERE id='$id_peg' AND tahun='$thn' AND (bulan='$bln' OR bulan='".(int)$bln."')");
            if($q_add && !empty($q_add['shift'])) $jadwal_kode = $q_add['shift'];
            
            if($jadwal_kode == '-' || $jadwal_kode == '') {
                $q_main = fetch_assoc("SELECT $col_h as shift FROM jadwal_pegawai WHERE id='$id_peg' AND tahun='$thn' AND (bulan='$bln' OR bulan='".(int)$bln."')");
                if($q_main) $jadwal_kode = $q_main['shift'];
            }

            if(empty($jadwal_kode)) $jadwal_kode = '-';
            
            // Skip Libur
            if(in_array(strtoupper($jadwal_kode), ['-', '', 'L', 'LIBUR', 'CUTI', 'OFF'])) continue; 

            // Cek Status Kehadiran
            $data_log = null;
            $status_kehadiran = 'MANGKIR';
            $keterangan = 'Tidak Ada Data Absensi';

            if (isset($logs_rekap[$id_peg])) {
                $data_log = $logs_rekap[$id_peg];
                $status_kehadiran = 'HADIR';
                $keterangan = $data_log['status'];
            } 
            elseif (isset($logs_temp[$id_peg])) {
                $data_log = $logs_temp[$id_peg];
                $status_kehadiran = 'DINAS';
                $keterangan = "Sedang Bekerja (Belum Pulang)";
                $data_log['jam_pulang'] = null; 
            }

            // Ambil Info Jam Jaga
            $jadwal_in = '-'; 
            $jadwal_out = '-';
            $ts_masuk_jadwal = 0;

            if (isset($ref_jam[$dep_peg][$jadwal_kode])) {
                $jadwal_in = $ref_jam[$dep_peg][$jadwal_kode]['in'];
                $jadwal_out = $ref_jam[$dep_peg][$jadwal_kode]['out'];
                
                // Konversi jam masuk jadwal ke Timestamp hari ini
                $ts_masuk_jadwal = strtotime("$date_sql $jadwal_in");
            }

            // --- LOGIKA ANTI PERANG DUNIA ---
            // Jika hari ini, belum absen, TAPI jam sekarang < jam masuk jadwal
            if ($is_today && $status_kehadiran == 'MANGKIR' && $ts_masuk_jadwal > 0) {
                // Tambahkan toleransi misal 30 menit setelah jam masuk baru dianggap telat/mangkir
                // Tapi untuk strict logic: jika NOW < JAM_MASUK, berarti BELUM WAKTUNYA.
                if ($now_ts < $ts_masuk_jadwal) {
                    $status_kehadiran = 'BELUM_WAKTUNYA';
                    $keterangan = "Jadwal Belum Dimulai";
                }
            }

            // Filter Output
            if ($filter == 'MANGKIR') {
                // Skip jika HADIR, DINAS, atau BELUM WAKTUNYA
                if ($status_kehadiran == 'HADIR' || $status_kehadiran == 'DINAS' || $status_kehadiran == 'BELUM_WAKTUNYA') continue;
            }

            // Susun Data
            $jam_masuk_akt = ($data_log) ? date('H:i', strtotime($data_log['jam_datang'])) : '-';
            $jam_pulang_akt = '-';
            
            if ($data_log && !empty($data_log['jam_pulang']) && $data_log['jam_pulang'] != '0000-00-00 00:00:00') {
                $jam_pulang_akt = date('H:i', strtotime($data_log['jam_pulang']));
            } elseif ($status_kehadiran == 'DINAS') {
                $jam_pulang_akt = 'Sedang Dinas';
            }

            $data_evaluasi[] = [
                'tanggal' => date('d M Y', $current_date),
                'nik' => $peg['nik'],
                'nama' => $peg['nama'],
                'jadwal' => $jadwal_kode,
                'jadwal_in' => $jadwal_in,
                'jadwal_out' => $jadwal_out,
                'status_evaluasi' => $status_kehadiran,
                'jam_masuk' => $jam_masuk_akt,
                'jam_pulang' => $jam_pulang_akt,
                'keterangan' => $keterangan
            ];
        }

        $current_date = strtotime("+1 day", $current_date);
    }

    echo json_encode(['data' => $data_evaluasi]);
}
?>