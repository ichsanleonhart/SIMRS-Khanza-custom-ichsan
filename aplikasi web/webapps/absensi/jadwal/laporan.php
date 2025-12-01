<?php
session_start();
require_once('../../conf/conf.php');

// 1. CEK AKSES
if (!isset($_SESSION['jadwal_login'])) {
    header("Location: login.php");
    exit();
}

$dep_akses = $_SESSION['jadwal_dep'];
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'reguler'; // Default reguler

// Nama Bulan Indonesia
$indo_bln = ["01"=>"Januari", "02"=>"Februari", "03"=>"Maret", "04"=>"April", "05"=>"Mei", "06"=>"Juni", 
             "07"=>"Juli", "08"=>"Agustus", "09"=>"September", "10"=>"Oktober", "11"=>"November", "12"=>"Desember"];
$jml_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

// Nama Departemen (Untuk Judul)
$nama_dep = "SEMUA UNIT";
if($dep_akses != 'ALL') {
    $d = fetch_assoc("SELECT nama FROM departemen WHERE dep_id='$dep_akses'");
    $nama_dep = $d['nama'];
}

// Tentukan Tabel Sumber
$tabel_jadwal = ($jenis == 'tambahan') ? 'jadwal_tambahan' : 'jadwal_pegawai';

// QUERY UTAMA (Join Pegawai + Jadwal)
$where_dep = ($dep_akses == 'ALL') ? "" : "AND p.departemen = '$dep_akses'";
$sql = "SELECT p.nama, p.nik, j.* FROM pegawai p 
        LEFT JOIN $tabel_jadwal j ON p.id = j.id AND j.bulan = '$bulan' AND j.tahun = '$tahun'
        WHERE p.stts_aktif = 'AKTIF' $where_dep 
        ORDER BY p.nama ASC";
$result = bukaquery($sql);

// Helper Function: Mapping Kode Shift ke Inisial & Warna
function getShiftStyle($val) {
    $val = strtolower($val);
    if (strpos($val, 'pagi') !== false) return ['code' => 'P', 'class' => 'bg-green-100 text-green-800'];
    if (strpos($val, 'siang') !== false) return ['code' => 'S', 'class' => 'bg-yellow-100 text-yellow-800'];
    if (strpos($val, 'malam') !== false) return ['code' => 'M', 'class' => 'bg-blue-100 text-blue-800'];
    if (strpos($val, 'midle') !== false) return ['code' => 'Md', 'class' => 'bg-purple-100 text-purple-800'];
    if ($val == 'libur') return ['code' => 'L', 'class' => 'bg-red-200 text-red-800 font-bold'];
    if ($val == 'cuti') return ['code' => 'C', 'class' => 'bg-orange-200 text-orange-800 font-bold'];
    return ['code' => '', 'class' => ''];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Jadwal - <?php echo $nama_dep; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* CSS Khusus Cetak */
        @media print {
            @page { size: landscape; margin: 10mm; }
            body { background: white; -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
            .border-print { border: 1px solid #000 !important; }
            .text-xs { font-size: 10px !important; }
        }
        .cell-h { width: 24px; text-align: center; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-6 text-slate-800">

    <div class="no-print max-w-[1400px] mx-auto mb-6 flex justify-between items-center bg-white p-4 rounded-lg shadow">
        <div class="flex gap-4 items-center">
            <a href="index.php" class="text-blue-600 hover:underline">&larr; Kembali</a>
            <form method="GET" class="flex gap-2">
                <select name="bulan" class="border rounded px-2 py-1 text-sm" onchange="this.form.submit()">
                    <?php foreach($indo_bln as $k=>$v) echo "<option value='$k' ".($k==$bulan?'selected':'').">$v</option>"; ?>
                </select>
                <select name="tahun" class="border rounded px-2 py-1 text-sm" onchange="this.form.submit()">
                    <?php for($y=date('Y')-1; $y<=date('Y')+1; $y++) echo "<option value='$y' ".($y==$tahun?'selected':'').">$y</option>"; ?>
                </select>
                <select name="jenis" class="border rounded px-2 py-1 text-sm" onchange="this.form.submit()">
                    <option value="reguler" <?php echo ($jenis=='reguler')?'selected':''; ?>>Jadwal Reguler</option>
                    <option value="tambahan" <?php echo ($jenis=='tambahan')?'selected':''; ?>>Jadwal Tambahan</option>
                </select>
            </form>
        </div>
        <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 font-bold flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
            Cetak PDF
        </button>
    </div>

    <div class="max-w-[1400px] mx-auto bg-white p-8 shadow-lg">
        
        <div class="text-center mb-6 border-b-2 border-black pb-4">
            <h1 class="text-2xl font-bold uppercase">JADWAL DINAS <?php echo ($jenis=='tambahan' ? 'TAMBAHAN' : ''); ?></h1>
            <h2 class="text-xl font-bold uppercase text-gray-600"><?php echo $nama_dep; ?></h2>
            <p class="font-bold mt-1">PERIODE: <?php echo strtoupper($indo_bln[$bulan]) . " " . $tahun; ?></p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-black text-xs">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="border border-black p-2 text-left w-40">NAMA PEGAWAI</th>
                        <?php for($i=1; $i<=$jml_hari; $i++): 
                             $date = "$tahun-$bulan-$i";
                             $day = date('D', strtotime($date));
                             $bg = ($day=='Sun') ? 'bg-red-200' : ''; // Minggu merah
                        ?>
                            <th class="border border-black cell-h <?php echo $bg; ?>">
                                <?php echo $i; ?><br>
                                <span class="text-[9px] font-normal"><?php echo substr($day,0,1); ?></span>
                            </th>
                        <?php endfor; ?>
                        <th class="border border-black p-1 bg-green-100">P</th>
                        <th class="border border-black p-1 bg-yellow-100">S</th>
                        <th class="border border-black p-1 bg-blue-100">M</th>
                        <th class="border border-black p-1 bg-red-100">L</th>
                        <th class="border border-black p-1 bg-orange-100">C</th>
                        <th class="border border-black p-1 bg-gray-100">JML</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(mysqli_num_rows($result) > 0) {
                        while($row = mysqli_fetch_assoc($result)) {
                            // Reset Counter
                            $cnt = ['P'=>0, 'S'=>0, 'M'=>0, 'L'=>0, 'C'=>0, 'Total'=>0];
                            
                            echo "<tr>";
                            echo "<td class='border border-black p-1 px-2 font-medium truncate' title='{$row['nama']}'>".substr($row['nama'],0,20)."</td>";
                            
                            // Loop Data Harian
                            for($i=1; $i<=$jml_hari; $i++) {
                                $val = $row['h'.$i]; // Isi: Pagi, Siang, Libur, dll
                                $style = getShiftStyle($val);
                                
                                // Hitung Rekap
                                if($style['code'] == 'P') $cnt['P']++;
                                elseif($style['code'] == 'S') $cnt['S']++;
                                elseif($style['code'] == 'M') $cnt['M']++;
                                elseif($style['code'] == 'L') $cnt['L']++;
                                elseif($style['code'] == 'C') $cnt['C']++;
                                
                                if($style['code'] != '') $cnt['Total']++;

                                echo "<td class='border border-black p-1 text-center font-bold {$style['class']}'>{$style['code']}</td>";
                            }

                            // Tampilkan Rekap
                            echo "<td class='border border-black text-center font-bold bg-green-50'>{$cnt['P']}</td>";
                            echo "<td class='border border-black text-center font-bold bg-yellow-50'>{$cnt['S']}</td>";
                            echo "<td class='border border-black text-center font-bold bg-blue-50'>{$cnt['M']}</td>";
                            echo "<td class='border border-black text-center font-bold bg-red-50'>{$cnt['L']}</td>";
                            echo "<td class='border border-black text-center font-bold bg-orange-50'>{$cnt['C']}</td>";
                            echo "<td class='border border-black text-center font-bold bg-gray-100'>{$cnt['Total']}</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='40' class='text-center p-4'>Belum ada data jadwal untuk periode ini.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="mt-12 flex justify-end">
            <div class="text-center w-64">
                <p class="mb-20">Mengetahui,<br>Kepala Ruangan / Unit</p>
                <p class="font-bold border-b border-black pb-1 inline-block min-w-[200px]">
                    ( ........................................... )
                </p>
            </div>
        </div>

    </div>
</body>
</html>