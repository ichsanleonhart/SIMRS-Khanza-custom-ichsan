<?php
session_start();
require_once('../../conf/conf.php');

if (!isset($_SESSION['hrd_login'])) {
    if(isset($_GET['act']) && $_GET['act']=='export_excel'){ die("Akses ditolak."); }
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Sesi habis']);
    exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$konektor = bukakoneksi();

// --- 1. LIST DATA (FIXED LOGIC) ---
if ($act == 'list') {
    header('Content-Type: application/json');
    $query = build_query_cuti($_GET);
    $hasil = bukaquery($query);
    $data = [];
    
    while($r = mysqli_fetch_assoc($hasil)) {
        // LOGIC SISA CUTI PER TAHUN
        $tahun_ajuan = date('Y', strtotime($r['tanggal_awal']));
        $nik_peg = $r['nik'];
        
        $q_sisa = fetch_assoc("SELECT COALESCE(SUM(jumlah),0) as terpakai FROM pengajuan_cuti 
                               WHERE nik='$nik_peg' 
                               AND status_persetujuan_HRD='Disetujui' 
                               AND urgensi='Tahunan' 
                               AND YEAR(tanggal_awal)='$tahun_ajuan'");
        
        $terpakai = $q_sisa['terpakai'];
        $sisa = 12 - $terpakai;
        if($sisa < 0) $sisa = 0;
        
        // --- PERBAIKAN DI SINI ---
        // Kirim Angka Murni untuk Logic JS
        $r['sisa_cuti_angka'] = (int)$sisa; 
        // Kirim Teks Formatted untuk Tampilan UI
        $r['sisa_cuti_display'] = "$sisa (Thn $tahun_ajuan)"; 
        
        $data[] = $r;
    }
    echo json_encode(['data' => $data]);
    exit;
}

// --- 2. EXPORT EXCEL ---
elseif ($act == 'export_excel') {
    header("Content-type: application/vnd-ms-excel");
    header("Content-Disposition: attachment; filename=Laporan_Cuti_".date('Ymd').".xls");
    $query = build_query_cuti($_GET);
    $hasil = bukaquery($query);
    ?>
    <style> table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #000; padding: 5px; } th { background-color: #f0f0f0; } </style>
    <h3>LAPORAN CUTI PEGAWAI</h3>
    <table>
        <thead>
            <tr><th>No</th><th>NIK</th><th>Nama</th><th>Jenis</th><th>Mulai</th><th>Selesai</th><th>Jml</th><th>Status HRD</th></tr>
        </thead>
        <tbody>
            <?php $no=1; while($r=mysqli_fetch_assoc($hasil)){ ?>
            <tr>
                <td><?=$no++?></td><td><?=$r['nik']?></td><td><?=$r['nama']?></td>
                <td><?=$r['urgensi']?></td><td><?=$r['tanggal_awal']?></td><td><?=$r['tanggal_akhir']?></td>
                <td><?=$r['jumlah']?></td><td><?=$r['status_persetujuan_HRD']?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <?php exit;
}

// --- 3. APPROVE ---
elseif ($act == 'approve') {
    header('Content-Type: application/json');
    $no_pengajuan = validTeks($_POST['no_pengajuan']);
    $status_hrd   = validTeks($_POST['status']); 
    
    $data_cuti = fetch_assoc("SELECT * FROM pengajuan_cuti WHERE no_pengajuan='$no_pengajuan'");
    if(!$data_cuti) { echo json_encode(['status'=>'error', 'message'=>'Data tidak ditemukan']); exit; }

    $pesan_tambahan = "";

    if ($status_hrd == 'Disetujui') {
        $sql_update = "UPDATE pengajuan_cuti SET status='Disetujui', status_persetujuan_HRD='Disetujui', waktu_disetujui_HRD=NOW(), waktu_disetujui_atasan=IF(waktu_disetujui_atasan IS NULL, NOW(), waktu_disetujui_atasan) WHERE no_pengajuan='$no_pengajuan'";
        
        $hasil_update = update_jadwal_otomatis($data_cuti['nik'], $data_cuti['tanggal_awal'], $data_cuti['tanggal_akhir']);
        
        // Update Saldo (Arsip)
        if(trim(strtolower($data_cuti['urgensi'])) == 'tahunan') {
            $jml = $data_cuti['jumlah'];
            $nik = $data_cuti['nik'];
            mysqli_query($konektor, "UPDATE pegawai SET cuti_diambil = cuti_diambil + $jml WHERE nik='$nik'");
        }
        $pesan_tambahan = " (" . $hasil_update . ")";
        
    } else {
        $sql_update = "UPDATE pengajuan_cuti SET status_persetujuan_HRD='Ditolak', waktu_disetujui_HRD=NOW() WHERE no_pengajuan='$no_pengajuan'";
    }

    if(mysqli_query($konektor, $sql_update)) {
        echo json_encode(['status'=>'success', 'message'=>"Berhasil $status_hrd." . $pesan_tambahan]);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'DB Error: '.mysqli_error($konektor)]);
    }
    exit;
}

// --- HELPER ---
function update_jadwal_otomatis($nik, $tgl_awal, $tgl_akhir) {
    global $konektor;
    $peg = fetch_assoc("SELECT id FROM pegawai WHERE nik='$nik'");
    if(!$peg) return "Pegawai tidak ditemukan";
    $id_peg = $peg['id'];

    $begin = new DateTime($tgl_awal);
    $end   = new DateTime($tgl_akhir);
    $end->modify('+1 day'); 
    $period = new DatePeriod($begin, DateInterval::createFromDateString('1 day'), $end);

    $count = 0;
    foreach ($period as $dt) {
        $thn = $dt->format("Y");
        $bln = $dt->format("n"); 
        $hari = "h" . $dt->format("j"); 

        $cek = fetch_assoc("SELECT id FROM jadwal_pegawai WHERE id='$id_peg' AND tahun='$thn' AND (bulan='$bln' OR bulan='0$bln')");
        
        if($cek) {
            $q = "UPDATE jadwal_pegawai SET $hari='Cuti' WHERE id='$id_peg' AND tahun='$thn' AND (bulan='$bln' OR bulan='0$bln')";
        } else {
            $q = "INSERT INTO jadwal_pegawai (id, tahun, bulan, $hari) VALUES ('$id_peg', '$thn', '$bln', 'Cuti')";
        }
        
        if(mysqli_query($konektor, $q)) $count++;
        mysqli_query($konektor, "DELETE FROM jadwal_tambahan WHERE id='$id_peg' AND tahun='$thn' AND (bulan='$bln' OR bulan='0$bln') AND $hari IS NOT NULL");
    }
    return "Jadwal Updated: $count hari";
}

function build_query_cuti($p) {
    $where = " WHERE 1=1 ";
    if (!empty($p['tgl_mulai']) && !empty($p['tgl_akhir'])) {
        $col = ($p['filter_type'] == 'cuti') ? 'pc.tanggal_awal' : 'pc.tanggal';
        $where .= " AND ($col BETWEEN '{$p['tgl_mulai']}' AND '{$p['tgl_akhir']}') ";
    }
    if (isset($p['status_hrd']) && $p['status_hrd'] != 'ALL') {
        $where .= " AND pc.status_persetujuan_HRD = '{$p['status_hrd']}' ";
    }
    return "SELECT pc.*, p.nama, p.departemen, p.cuti_diambil FROM pengajuan_cuti pc JOIN pegawai p ON pc.nik = p.nik $where ORDER BY pc.tanggal DESC";
}
?>