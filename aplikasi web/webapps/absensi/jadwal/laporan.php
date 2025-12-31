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

// Nama Bulan Indonesia
$indo_bln = ["01"=>"Januari", "02"=>"Februari", "03"=>"Maret", "04"=>"April", "05"=>"Mei", "06"=>"Juni", 
             "07"=>"Juli", "08"=>"Agustus", "09"=>"September", "10"=>"Oktober", "11"=>"November", "12"=>"Desember"];
$jml_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

// Nama Departemen
$nama_dep = "SEMUA UNIT";
if($dep_akses != 'ALL') {
    $d = fetch_assoc("SELECT nama FROM departemen WHERE dep_id='$dep_akses'");
    $nama_dep = $d['nama'];
}

// --- QUERY DINAMIS (MERGE REGULER & TAMBAHAN) ---
// Kita buat select string untuk h1-h31 dari dua tabel sekaligus
$select_cols = "p.nama, p.nik";
for($k=1; $k<=31; $k++) {
    $select_cols .= ", j1.h$k as r_h$k, j2.h$k as t_h$k"; // r_ = reguler, t_ = tambahan
}

$where_dep = ($dep_akses == 'ALL') ? "" : "AND p.departemen = '$dep_akses'";

$sql = "SELECT $select_cols 
        FROM pegawai p 
        LEFT JOIN jadwal_pegawai j1 ON p.id = j1.id AND j1.bulan = '$bulan' AND j1.tahun = '$tahun'
        LEFT JOIN jadwal_tambahan j2 ON p.id = j2.id AND j2.bulan = '$bulan' AND j2.tahun = '$tahun'
        WHERE p.stts_aktif = 'AKTIF' $where_dep 
        ORDER BY p.nama ASC";

$result = bukaquery($sql);

// --- HELPER STYLE & CODE ---
function parseShift($val) {
    $val = strtolower($val);
    if(empty($val)) return null;
    
    $code = ''; 
    $cat = ''; // P/S/M/L/C
    $bg = '';

    if (strpos($val, 'midle') !== false) { $code = 'Md'; $cat = 'M'; $bg = 'bg-purple-100 text-purple-800'; } // Middle masuk kategori Malam/Siang tergantung kebijakan, disini saya mapping ke code Md tapi kategori hitung manual nanti
    elseif (strpos($val, 'pagi') !== false) { $code = 'P'; $cat = 'P'; $bg = 'bg-green-100 text-green-800'; }
    elseif (strpos($val, 'siang') !== false) { $code = 'S'; $cat = 'S'; $bg = 'bg-yellow-100 text-yellow-800'; }
    elseif (strpos($val, 'malam') !== false) { $code = 'M'; $cat = 'M'; $bg = 'bg-blue-100 text-blue-800'; }
    elseif (strpos($val, 'libur') !== false || $val == 'off') { $code = 'L'; $cat = 'L'; $bg = 'bg-red-200 text-red-800 font-bold'; }
    elseif (strpos($val, 'cuti') !== false) { $code = 'C'; $cat = 'C'; $bg = 'bg-orange-200 text-orange-800 font-bold'; }
    
    // Fix kategori Middle agar terhitung di footer (Asumsi Middle Pagi = Pagi)
    if (strpos($val, 'midle pagi') !== false) $cat = 'P';
    if (strpos($val, 'midle siang') !== false) $cat = 'S';
    if (strpos($val, 'midle malam') !== false) $cat = 'M';

    return ['code' => $code, 'cat' => $cat, 'bg' => $bg];
}

// Inisialisasi Array Rekap Harian (Footer)
$daily_rekap = [];
for($d=1; $d<=31; $d++) {
    $daily_rekap[$d] = ['P'=>0, 'S'=>0, 'M'=>0];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Jadwal - <?php echo $nama_dep; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page { size: landscape; margin: 5mm; }
            body { background: white; -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
            .text-xs { font-size: 9px !important; }
            table { font-size: 9px !important; }
        }
        .cell-h { width: 26px; text-align: center; }
        .double-shift { font-size: 8px; line-height: 1; font-weight: bold; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-6 text-slate-800">

    <div class="no-print max-w-[1500px] mx-auto mb-6 flex justify-between items-center bg-white p-4 rounded-lg shadow">
        <div class="flex gap-4 items-center">
            <a href="index.php" class="text-blue-600 hover:underline">&larr; Kembali</a>
            <form method="GET" class="flex gap-2">
                <select name="bulan" class="border rounded px-2 py-1 text-sm" onchange="this.form.submit()">
                    <?php foreach($indo_bln as $k=>$v) echo "<option value='$k' ".($k==$bulan?'selected':'').">$v</option>"; ?>
                </select>
                <select name="tahun" class="border rounded px-2 py-1 text-sm" onchange="this.form.submit()">
                    <?php for($y=date('Y')-1; $y<=date('Y')+1; $y++) echo "<option value='$y' ".($y==$tahun?'selected':'').">$y</option>"; ?>
                </select>
            </form>
        </div>
        <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 font-bold flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
            Cetak PDF
        </button>
    </div>

    <div class="max-w-[1500px] mx-auto bg-white p-6 shadow-lg">
        
        <div class="text-center mb-4 border-b-2 border-black pb-2">
            <h1 class="text-xl font-bold uppercase">REKAPITULASI JADWAL DINAS (GABUNGAN)</h1>
            <h2 class="text-lg font-bold uppercase text-gray-600"><?php echo $nama_dep; ?></h2>
            <p class="font-bold text-sm mt-1">PERIODE: <?php echo strtoupper($indo_bln[$bulan]) . " " . $tahun; ?></p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-black text-xs">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="border border-black p-2 text-left w-48">NAMA PEGAWAI</th>
                        <?php for($i=1; $i<=$jml_hari; $i++): 
                             $date = "$tahun-$bulan-$i";
                             $day = date('D', strtotime($date));
                             $bg = ($day=='Sun') ? 'bg-red-200' : ''; 
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
                            // Reset Counter Personal
                            $cnt = ['P'=>0, 'S'=>0, 'M'=>0, 'L'=>0, 'C'=>0, 'Total'=>0];
                            
                            echo "<tr>";
                            echo "<td class='border border-black p-1 px-2 font-medium truncate uppercase' title='{$row['nama']}'>".substr($row['nama'],0,25)."</td>";
                            
                            // LOOP TANGGAL 1 - 31
                            for($i=1; $i<=$jml_hari; $i++) {
                                $val_reg = $row['r_h'.$i]; // Jadwal Reguler
                                $val_add = $row['t_h'.$i]; // Jadwal Tambahan

                                $p_reg = parseShift($val_reg);
                                $p_add = parseShift($val_add);

                                // LOGIC MERGE TAMPILAN
                                $display_html = "";
                                $cell_bg = "";

                                // Jika ada jadwal tambahan DAN reguler
                                if ($p_reg && $p_add && $p_reg['code'] != $p_add['code']) {
                                    $display_html = $p_reg['code'] . " / " . $p_add['code'];
                                    $cell_bg = "bg-gray-100"; // Warna netral untuk ganda
                                    
                                    // Hitung keduanya untuk rekap footer
                                    if(isset($daily_rekap[$i][$p_reg['cat']])) $daily_rekap[$i][$p_reg['cat']]++;
                                    if(isset($daily_rekap[$i][$p_add['cat']])) $daily_rekap[$i][$p_add['cat']]++;

                                    // Hitung personal (Hitung 1 hari kerja)
                                    if($p_reg['cat'] != 'L' && $p_reg['cat'] != 'C') $cnt['Total']++; 

                                } 
                                // Jika hanya Reguler
                                elseif ($p_reg) {
                                    $display_html = $p_reg['code'];
                                    $cell_bg = $p_reg['bg'];
                                    
                                    // Rekap Footer
                                    if(isset($daily_rekap[$i][$p_reg['cat']])) $daily_rekap[$i][$p_reg['cat']]++;
                                    
                                    // Rekap Personal
                                    if(isset($cnt[$p_reg['cat']])) $cnt[$p_reg['cat']]++;
                                    if($p_reg['cat'] != 'L') $cnt['Total']++; // JML exclude Libur
                                }
                                // Jika hanya Tambahan (Kasus jarang tapi mungkin)
                                elseif ($p_add) {
                                    $display_html = $p_add['code'];
                                    $cell_bg = $p_add['bg'];
                                    
                                    if(isset($daily_rekap[$i][$p_add['cat']])) $daily_rekap[$i][$p_add['cat']]++;
                                    
                                    if(isset($cnt[$p_add['cat']])) $cnt[$p_add['cat']]++;
                                    if($p_add['cat'] != 'L') $cnt['Total']++;
                                }

                                // Render Cell
                                $cls_dbl = (strlen($display_html) > 2) ? 'double-shift' : '';
                                echo "<td class='border border-black p-0.5 text-center font-bold $cell_bg $cls_dbl'>$display_html</td>";
                            }

                            // Tampilkan Rekap Personal (Kanan)
                            echo "<td class='border border-black text-center font-bold bg-green-50'>{$cnt['P']}</td>";
                            echo "<td class='border border-black text-center font-bold bg-yellow-50'>{$cnt['S']}</td>";
                            echo "<td class='border border-black text-center font-bold bg-blue-50'>{$cnt['M']}</td>";
                            echo "<td class='border border-black text-center font-bold bg-red-50'>{$cnt['L']}</td>";
                            echo "<td class='border border-black text-center font-bold bg-orange-50'>{$cnt['C']}</td>";
                            echo "<td class='border border-black text-center font-bold bg-gray-200'>{$cnt['Total']}</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='40' class='text-center p-4'>Belum ada data jadwal untuk periode ini.</td></tr>";
                    }
                    ?>
                </tbody>
                
                <tfoot>
                    <tr class="bg-green-50">
                        <td class="border border-black p-1 font-bold text-right pr-2">TOTAL PAGI</td>
                        <?php for($i=1; $i<=$jml_hari; $i++): ?>
                            <td class="border border-black text-center font-bold"><?= ($daily_rekap[$i]['P'] > 0) ? $daily_rekap[$i]['P'] : '' ?></td>
                        <?php endfor; ?>
                        <td colspan="6" class="bg-gray-300 border border-black"></td>
                    </tr>
                    
                    <tr class="bg-yellow-50">
                        <td class="border border-black p-1 font-bold text-right pr-2">TOTAL SIANG</td>
                        <?php for($i=1; $i<=$jml_hari; $i++): ?>
                            <td class="border border-black text-center font-bold"><?= ($daily_rekap[$i]['S'] > 0) ? $daily_rekap[$i]['S'] : '' ?></td>
                        <?php endfor; ?>
                        <td colspan="6" class="bg-gray-300 border border-black"></td>
                    </tr>

                    <tr class="bg-blue-50">
                        <td class="border border-black p-1 font-bold text-right pr-2">TOTAL MALAM</td>
                        <?php for($i=1; $i<=$jml_hari; $i++): ?>
                            <td class="border border-black text-center font-bold"><?= ($daily_rekap[$i]['M'] > 0) ? $daily_rekap[$i]['M'] : '' ?></td>
                        <?php endfor; ?>
                        <td colspan="6" class="bg-gray-300 border border-black"></td>
                    </tr>
                </tfoot>

            </table>
        </div>

        <div class="mt-8 flex justify-end text-xs">
            <div class="text-center w-64">
                <p class="mb-16">Mengetahui,<br>Kepala Ruangan / Unit</p>
                <p class="font-bold border-b border-black pb-1 inline-block min-w-[200px]">
                    ( ........................................... )
                </p>
            </div>
        </div>

    </div>
</body>
</html>