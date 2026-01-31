<?php
session_start();
// Pastikan path config benar
require_once('../../conf/conf.php');
require_once('../conf/config_whatsapp.php'); 

// Set Timezone Wajib (Agar sinkron dengan DB)
date_default_timezone_set('Asia/Jakarta');

// 1. CEK SESI
if (!isset($_SESSION['hrd_login'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Sesi Expired']);
    exit;
}

// 2. CEK CONFIG WA
if ($kirim_notif_wa != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Fitur WA Dimatikan di Config']);
    exit;
}

$konektor = bukakoneksi();
$logs = []; 

// Koneksi ke Database WA (Terpisah)
$konektor_wa = mysqli_connect($wa_db_host, $wa_db_user, $wa_db_pass, $wa_db_name);
if (!$konektor_wa) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal Koneksi Database WA']);
    exit;
}

// ==========================================
// BAGIAN A: REMINDER BELUM MASUK (Late In)
// ==========================================
$tgl_skrg = date('Y-m-d');
$thn = date('Y');
$bln = date('m');
$hari_col = "h" . date('j'); 
$jam_skrg_ts = time();

// Hemat resource: Hanya jalan jam 06:00 - 22:00
if (date('H') >= 6 && date('H') <= 22) {
    $q_peg = "SELECT id, nik, nama, departemen FROM pegawai WHERE stts_aktif='AKTIF'";
    $r_peg = mysqli_query($konektor, $q_peg);

    while ($peg = mysqli_fetch_assoc($r_peg)) {
        $id_peg = $peg['id'];
        $dep_id = $peg['departemen'];
        
        // 1. Cek Jadwal
        $shift_kode = '';
        $q_add = "SELECT $hari_col as shift FROM jadwal_tambahan WHERE id='$id_peg' AND tahun='$thn' AND (bulan='$bln' OR bulan='".(int)$bln."')";
        $r_add = mysqli_query($konektor, $q_add);
        $d_add = mysqli_fetch_assoc($r_add);
        
        if($d_add && !empty($d_add['shift'])) $shift_kode = $d_add['shift'];
        else {
            $q_main = "SELECT $hari_col as shift FROM jadwal_pegawai WHERE id='$id_peg' AND tahun='$thn' AND (bulan='$bln' OR bulan='".(int)$bln."')";
            $r_main = mysqli_query($konektor, $q_main);
            $d_main = mysqli_fetch_assoc($r_main);
            if($d_main) $shift_kode = $d_main['shift'];
        }

        if(empty($shift_kode) || in_array(strtoupper($shift_kode), ['-','','L','LIBUR','CUTI','OFF'])) continue;

        // 2. Ambil Jam Masuk
        $q_jam_sql = "SELECT jam_masuk FROM jam_jaga WHERE dep_id='$dep_id' AND shift='$shift_kode'";
        $r_jam = mysqli_query($konektor, $q_jam_sql);
        $d_jam = mysqli_fetch_assoc($r_jam);
        
        if(!$d_jam) {
            $r_jam_gl = mysqli_query($konektor, "SELECT jam_masuk FROM jam_jaga WHERE shift='$shift_kode' LIMIT 1");
            $d_jam = mysqli_fetch_assoc($r_jam_gl);
        }
        
        if($d_jam) {
            $jam_masuk_str = $d_jam['jam_masuk'];
            $ts_masuk = strtotime("$tgl_skrg $jam_masuk_str");
            $ts_batas_awal = $ts_masuk + (10 * 60); // Telat 10 menit
            $ts_batas_akhir = $ts_masuk + (60 * 60); // Batas 1 jam

            // 3. Trigger Waktu
            if ($jam_skrg_ts > $ts_batas_awal && $jam_skrg_ts < $ts_batas_akhir) {
                
                // 4. Cek Absen
                $cek_temp = mysqli_num_rows(mysqli_query($konektor, "SELECT id FROM temporary_presensi WHERE id='$id_peg'"));
                $cek_rekap = mysqli_num_rows(mysqli_query($konektor, "SELECT id FROM rekap_presensi WHERE id='$id_peg' AND jam_datang LIKE '$tgl_skrg%'"));

                if ($cek_temp == 0 && $cek_rekap == 0) {
                    
                    // 5. ANTI SPAM V4 (CONTENT MATCHING)
                    // Cari pesan yang dikirim hari ini ke nomor ini dengan kata kunci "Jadwal dinas shift X"
                    $no_hp = get_no_hp($id_peg);
                    
                    if ($no_hp) {
                        // Keyword spesifik: Shift dan 'belum melakukan presensi'
                        $keyword_shift = "shift *$shift_kode*"; 
                        
                        $sql_cek_spam = "SELECT nomor FROM wa_outbox 
                                         WHERE nowa = '$no_hp' 
                                         AND source = 'KhanzaBot'
                                         AND pesan LIKE '%$keyword_shift%' 
                                         AND pesan LIKE '%belum melakukan presensi%'
                                         AND DATE(tanggal_jam) = '$tgl_skrg' 
                                         LIMIT 1";
                        
                        $run_cek = mysqli_query($konektor_wa, $sql_cek_spam);

                        if (mysqli_num_rows($run_cek) == 0) {
                            // KIRIM
                            $pesan = "Selamat Pagi/Siang, Kak *{$peg['nama']}*! ☀️\n\n" .
                                     "Jadwal dinas shift *$shift_kode* dimulai pukul *$jam_masuk_str*.\n" .
                                     "Sistem mendeteksi Kakak belum melakukan presensi (Terlambat >10 Menit).\n\n" .
                                     "Jangan lupa absen yaa! Semangat! 💪\n_- HRD Bot_";
                            
                            $kode_unik = "REM_IN_{$tgl_skrg}_{$id_peg}";
                            kirim_wa($no_hp, $pesan, $kode_unik);
                            
                            $logs[] = "<span class='text-green-400'>[MASUK]</span> Terkirim ke {$peg['nama']} ($shift_kode)";
                            catat_audit("BOT: Reminder Masuk {$peg['nama']}");
                        } 
                    } 
                }
            }
        }
    }
}

// ==========================================
// BAGIAN B: REMINDER BELUM PULANG (Lupa Checkout)
// ==========================================
$q_temp = "SELECT t.*, p.nama, p.nik, p.departemen FROM temporary_presensi t JOIN pegawai p ON t.id = p.id";
$r_temp = mysqli_query($konektor, $q_temp);

while ($row = mysqli_fetch_assoc($r_temp)) {
    $id_peg = $row['id'];
    $shift_kode = $row['shift'];
    $dep_id = $row['departemen'];
    $jam_datang_asli = $row['jam_datang']; 

    // Cari Jam Pulang
    $q_jam_sql = "SELECT jam_pulang FROM jam_jaga WHERE dep_id='$dep_id' AND shift='$shift_kode'";
    $r_jam = mysqli_query($konektor, $q_jam_sql);
    $d_jam = mysqli_fetch_assoc($r_jam);
    
    if(!$d_jam) {
        $r_jam_gl = mysqli_query($konektor, "SELECT jam_pulang FROM jam_jaga WHERE shift='$shift_kode' LIMIT 1");
        $d_jam = mysqli_fetch_assoc($r_jam_gl);
    }

    if ($d_jam) {
        $jam_pulang_str = $d_jam['jam_pulang'];
        
        // Logika Hitung Batas Pulang
        $ts_masuk_asli = strtotime($jam_datang_asli);
        $tgl_masuk_only = date('Y-m-d', $ts_masuk_asli);
        $ts_pulang_cek = strtotime("$tgl_masuk_only $jam_pulang_str");
        
        if ($ts_pulang_cek < $ts_masuk_asli) {
            $ts_pulang_cek = strtotime("+1 day", $ts_pulang_cek);
        }

        $ts_batas_pulang = $ts_pulang_cek + (30 * 60); // 30 Menit toleransi
        
        // Cek Waktu
        if ($jam_skrg_ts > $ts_batas_pulang) {
            
            $no_hp = get_no_hp($id_peg);
            
            if ($no_hp) {
                // ANTI SPAM V4 (CONTENT MATCHING)
                // Cek apakah sudah pernah kirim reminder pulang untuk Shift ini dalam 18 jam terakhir
                $keyword_shift = "shift *$shift_kode*";
                
                $sql_cek_spam = "SELECT nomor FROM wa_outbox 
                                 WHERE nowa = '$no_hp' 
                                 AND source = 'KhanzaBot'
                                 AND pesan LIKE '%$keyword_shift%'
                                 AND pesan LIKE '%lupa absen pulang%'
                                 AND tanggal_jam >= DATE_SUB(NOW(), INTERVAL 18 HOUR)
                                 LIMIT 1";
                
                $run_cek = mysqli_query($konektor_wa, $sql_cek_spam);
                
                if (mysqli_num_rows($run_cek) == 0) {
                    // KIRIM
                    $pesan = "Halo Kak *{$row['nama']}* 👋,\n\n" .
                             "Sudah >30 menit lewat jam pulang shift *$shift_kode* (Pukul $jam_pulang_str).\n" .
                             "Sepertinya Kakak lupa absen pulang (Checkout)? 😅\n\n" .
                             "Mohon segera absen pulang agar durasi valid. Hati-hati di jalan! 🛵\n_- HRD Bot_";
                    
                    $kode_unik = "REM_OUT_{$tgl_masuk_only}_{$id_peg}";
                    kirim_wa($no_hp, $pesan, $kode_unik);
                    
                    $logs[] = "<span class='text-green-400'>[PULANG]</span> Terkirim ke {$row['nama']} ($shift_kode)";
                    catat_audit("BOT: Reminder Pulang {$row['nama']}");
                } else {
                    // Log Debug (Opsional, agar tahu bot bekerja tapi menahan diri)
                    $logs[] = "<span class='text-yellow-600'>[SKIP]</span> {$row['nama']} (Anti-Spam Active)";					
                }
            }
        }
    }
}

mysqli_close($konektor_wa);
echo json_encode(['status' => 'success', 'logs' => $logs]);


// --- HELPER FUNCTIONS ---

function get_no_hp($id_peg) {
    global $konektor; 
    $r_nik = mysqli_query($konektor, "SELECT nik FROM pegawai WHERE id='$id_peg'");
    $p = mysqli_fetch_assoc($r_nik);
    if(!$p) return null;
    $nik = $p['nik'];

    $r_petugas = mysqli_query($konektor, "SELECT no_telp FROM petugas WHERE nip='$nik'");
    $cek_petugas = mysqli_fetch_assoc($r_petugas);
    if ($cek_petugas && !empty($cek_petugas['no_telp'])) return clean_hp($cek_petugas['no_telp']);

    $r_dokter = mysqli_query($konektor, "SELECT no_telp FROM dokter WHERE kd_dokter='$nik'");
    $cek_dokter = mysqli_fetch_assoc($r_dokter);
    if ($cek_dokter && !empty($cek_dokter['no_telp'])) return clean_hp($cek_dokter['no_telp']);

    return null;
}

function clean_hp($hp) {
    $hp = preg_replace('/[^0-9]/', '', $hp);
    if (substr($hp, 0, 1) == '0') $hp = '62' . substr($hp, 1);
    return $hp . "@c.us";
}

function kirim_wa($nowa, $pesan, $request_code) {
    global $konektor_wa;
    $pesan_safe = mysqli_real_escape_string($konektor_wa, $pesan);
    $nowa_safe = mysqli_real_escape_string($konektor_wa, $nowa);
    $req_safe = mysqli_real_escape_string($konektor_wa, $request_code);
    
    // Pastikan source = 'KhanzaBot'
    $q = "INSERT INTO wa_outbox (nowa, pesan, tanggal_jam, status, sender, source, request) 
          VALUES ('$nowa_safe', '$pesan_safe', NOW(), 'ANTRIAN', 'NODEJS', 'KhanzaBot', '$req_safe')";
    mysqli_query($konektor_wa, $q);
}

function catat_audit($msg) {
    global $konektor;
    $msg = mysqli_real_escape_string($konektor, $msg);
    mysqli_query($konektor, "INSERT INTO trackersql (tanggal, sqle, usere) VALUES (NOW(), '$msg', 'BOT-REMINDER')");
}
?>