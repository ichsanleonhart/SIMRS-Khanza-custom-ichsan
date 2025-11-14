<?php
/*
 * ==================================================================
 * LAPORAN.PHP (PENGEMBANGAN APLIKASI PENGAJUAN ASET - TAHAP 4.B / V.16)
 * ==================================================================
 * [UPDATE V.16]:
 * - Menambahkan kolom harga realisasi di modal validasi.
 * - Mengubah query KPI untuk menghitung Realisasi & Selisih.
 * - Mengubah query Grafik Batang untuk membandingkan Estimasi vs Realisasi.
 * - Mengubah query Tabel Historis untuk menampilkan Total Realisasi.
 * - Mengupdate JavaScript Chart.js untuk menampilkan 2 dataset (grouped bar).
 *
 * Dibuat kompatibel dengan PHP 7.3
 */

// 1. INISIALISASI & KEAMANAN
// -----------------------------------------------------------------------------
session_start();

// Komentar: Cek jika user sudah login
if (!isset($_SESSION['nik_pengajuan_asset'])) {
    header('Location: login.php');
    exit;
}

// Komentar: Cek jika role adalah Direktur atau Logum
$role_login = $_SESSION['role_pengajuan_asset'];
if ($role_login != 'direktur' && $role_login != 'logum') {
    die("Akses ditolak. Halaman ini hanya untuk Direktur dan Logistik Umum.");
}

// Komentar: Panggil konfigurasi dan koneksi
include 'config_pengajuan_asset.php';
$konektor = bukakoneksi();
if (!$konektor) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$nik_login = $_SESSION['nik_pengajuan_asset'];
$nama_login = $_SESSION['nama_pengajuan_asset'];

// 2. MENGAMBIL DATA GLOBAL & FILTER
// -----------------------------------------------------------------------------
$nama_instansi = "RSK";
$logo_path = "logo.php?v=logo";
$favicon_path = "logo.php?v=favicon";

try {
    $setting_sql = "SELECT setting.nama_instansi FROM setting LIMIT 1";
    $setting_result = mysqli_query($konektor, $setting_sql);
    if ($setting_row = mysqli_fetch_assoc($setting_result)) {
        $nama_instansi = $setting_row['nama_instansi'];
    }
} catch (Exception $e) { /* Biarkan default */ }

// Komentar: Logika Filter (Filter Tanggal, Status, PJ Logum)
$filter_tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-d', strtotime('-30 days'));
$filter_tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$filter_status = isset($_GET['status_pengadaan']) ? $_GET['status_pengadaan'] : 'Semua';
$filter_pj = isset($_GET['pj_logum']) ? $_GET['pj_logum'] : 'Semua';

// Komentar: Bangun klausa WHERE berdasarkan filter
$where_clause = " WHERE pengajuan_asset.tanggal_pengajuan BETWEEN ? AND ? ";
$params = [$filter_tgl_awal, $filter_tgl_akhir];
$types = "ss";

if ($filter_status != 'Semua') {
    $where_clause .= " AND pengajuan_asset.status_pengadaan = ? ";
    $params[] = $filter_status;
    $types .= "s";
}

if ($role_login == 'logum') {
    $where_clause .= " AND pengajuan_asset.nik_pj = ? ";
    $params[] = $nik_login;
    $types .= "s";
} 
elseif ($role_login == 'direktur' && $filter_pj != 'Semua') {
    $where_clause .= " AND pengajuan_asset.nik_pj = ? ";
    $params[] = $filter_pj;
    $types .= "s";
}

// 3. PENGAMBILAN DATA UNTUK LAPORAN
// -----------------------------------------------------------------------------

// Komentar: [UPDATE V.16] Query 1: Data untuk KPI (Key Performance Indicators)
// Kita perlu JOIN dengan subquery dari validasi untuk mendapatkan total realisasi
$kpi = ['total_surat' => 0, 'total_nilai_disetujui' => 0, 'total_realisasi' => 0, 'selisih' => 0];
$sql_kpi = "
    SELECT 
        COUNT(pengajuan_asset.no_surat_pengajuan) AS total_surat,
        SUM(pengajuan_asset.total_disetujui) AS total_nilai_disetujui,
        SUM(IFNULL(v.total_realisasi, 0)) AS total_realisasi,
        (SUM(pengajuan_asset.total_disetujui) - SUM(IFNULL(v.total_realisasi, 0))) AS selisih
    FROM pengajuan_asset
    LEFT JOIN (
        SELECT 
            pengajuan_asset_validasi.no_surat_pengajuan, 
            SUM(pengajuan_asset_validasi.jumlah_datang * pengajuan_asset_validasi.harga_realisasi_satuan) AS total_realisasi
        FROM pengajuan_asset_validasi
        GROUP BY pengajuan_asset_validasi.no_surat_pengajuan
    ) AS v ON pengajuan_asset.no_surat_pengajuan = v.no_surat_pengajuan
    $where_clause
";
$stmt_kpi = mysqli_prepare($konektor, $sql_kpi);
mysqli_stmt_bind_param($stmt_kpi, $types, ...$params);
mysqli_stmt_execute($stmt_kpi);
$result_kpi = mysqli_stmt_get_result($stmt_kpi);
$kpi = mysqli_fetch_assoc($result_kpi);
mysqli_stmt_close($stmt_kpi);

// Komentar: Query 2: Data untuk Grafik Donat (Status Pengadaan)
$sql_donut = "
    SELECT pengajuan_asset.status_pengadaan, COUNT(*) AS jumlah
    FROM pengajuan_asset
    $where_clause
    GROUP BY pengajuan_asset.status_pengadaan
";
$stmt_donut = mysqli_prepare($konektor, $sql_donut);
mysqli_stmt_bind_param($stmt_donut, $types, ...$params);
mysqli_stmt_execute($stmt_donut);
$result_donut = mysqli_stmt_get_result($stmt_donut);
$chart_labels_donut = [];
$chart_data_donut = [];
while($row_donut = mysqli_fetch_assoc($result_donut)) {
    $chart_labels_donut[] = $row_donut['status_pengadaan'];
    $chart_data_donut[] = $row_donut['jumlah'];
}
mysqli_stmt_close($stmt_donut);

// Komentar: [UPDATE V.16] Query 3: Data untuk Grafik Batang (Estimasi vs Realisasi per Bulan)
$where_clause_grafik = " WHERE pengajuan_asset.tanggal_pengajuan >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) ";
$params_grafik = [];
$types_grafik = "";
if ($role_login == 'logum') {
    $where_clause_grafik .= " AND pengajuan_asset.nik_pj = ? ";
    $params_grafik[] = $nik_login;
    $types_grafik .= "s";
} elseif ($role_login == 'direktur' && $filter_pj != 'Semua') {
    $where_clause_grafik .= " AND pengajuan_asset.nik_pj = ? ";
    $params_grafik[] = $filter_pj;
    $types_grafik .= "s";
}

$sql_bar = "
    SELECT 
        DATE_FORMAT(pengajuan_asset.tanggal_pengajuan, '%Y-%m') AS bulan_tahun,
        SUM(pengajuan_asset.total_disetujui) AS total_estimasi,
        SUM(IFNULL(v.total_realisasi, 0)) AS total_realisasi
    FROM pengajuan_asset
    LEFT JOIN (
        SELECT 
            pengajuan_asset_validasi.no_surat_pengajuan, 
            SUM(pengajuan_asset_validasi.jumlah_datang * pengajuan_asset_validasi.harga_realisasi_satuan) AS total_realisasi
        FROM pengajuan_asset_validasi
        GROUP BY pengajuan_asset_validasi.no_surat_pengajuan
    ) AS v ON pengajuan_asset.no_surat_pengajuan = v.no_surat_pengajuan
    $where_clause_grafik
    GROUP BY DATE_FORMAT(pengajuan_asset.tanggal_pengajuan, '%Y-%m')
    ORDER BY bulan_tahun ASC
";
$stmt_bar = mysqli_prepare($konektor, $sql_bar);
if (!empty($params_grafik)) {
    mysqli_stmt_bind_param($stmt_bar, $types_grafik, ...$params_grafik);
}
mysqli_stmt_execute($stmt_bar);
$result_bar = mysqli_stmt_get_result($stmt_bar);
$chart_labels_bar = [];
$chart_data_bar_estimasi = [];
$chart_data_bar_realisasi = [];
while($row_bar = mysqli_fetch_assoc($result_bar)) {
    $chart_labels_bar[] = $row_bar['bulan_tahun'];
    $chart_data_bar_estimasi[] = $row_bar['total_estimasi'];
    $chart_data_bar_realisasi[] = $row_bar['total_realisasi'];
}
mysqli_stmt_close($stmt_bar);

// Komentar: [UPDATE V.16] Query 4: Data untuk Tabel Detail Historis
$list_laporan_detail = [];
$sql_table = "
    SELECT 
        pengajuan_asset.no_surat_pengajuan, pengajuan_asset.tanggal_pengajuan, 
        pegawai_pengaju.nama AS nama_pengaju,
        pegawai_pj.nama AS nama_pj_logum,
        pengajuan_asset.total_pengajuan,
        pengajuan_asset.total_disetujui,
        IFNULL(v.total_realisasi, 0) AS total_realisasi, -- [BARU V.16]
        pengajuan_asset.status_approval_logum,
        pengajuan_asset.status_approval_direktur,
        pengajuan_asset.status_pengadaan
    FROM pengajuan_asset
    INNER JOIN pegawai AS pegawai_pengaju ON pengajuan_asset.nik = pegawai_pengaju.nik
    INNER JOIN pegawai AS pegawai_pj ON pengajuan_asset.nik_pj = pegawai_pj.nik
    LEFT JOIN (
        SELECT 
            pengajuan_asset_validasi.no_surat_pengajuan, 
            SUM(pengajuan_asset_validasi.jumlah_datang * pengajuan_asset_validasi.harga_realisasi_satuan) AS total_realisasi
        FROM pengajuan_asset_validasi
        GROUP BY pengajuan_asset_validasi.no_surat_pengajuan
    ) AS v ON pengajuan_asset.no_surat_pengajuan = v.no_surat_pengajuan
    $where_clause
    ORDER BY pengajuan_asset.tanggal_pengajuan DESC
";
$stmt_table = mysqli_prepare($konektor, $sql_table);
mysqli_stmt_bind_param($stmt_table, $types, ...$params);
mysqli_stmt_execute($stmt_table);
$result_table = mysqli_stmt_get_result($stmt_table);
while($row_table = mysqli_fetch_assoc($result_table)) {
    $list_laporan_detail[] = $row_table;
}
mysqli_stmt_close($stmt_table);

// Komentar: Query 5: Data untuk Filter Dropdown PJ Logum (Hanya untuk Direktur)
$list_pj_logum = [];
if ($role_login == 'direktur') {
     $sql_pj = "
        SELECT DISTINCT pegawai.nik, pegawai.nama 
        FROM pegawai 
        INNER JOIN user ON pegawai.nik = AES_DECRYPT(user.id_user, 'nur')
        WHERE user.ipsrs_barang = 'true' AND pegawai.stts_aktif = 'AKTIF'
        ORDER BY pegawai.nama
    ";
    $result_pj = mysqli_query($konektor, $sql_pj);
    while($row_pj = mysqli_fetch_assoc($result_pj)) {
        $list_pj_logum[] = $row_pj;
    }
}

mysqli_close($konektor); 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pengajuan Aset - <?php echo htmlspecialchars($nama_instansi, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" href="<?php echo $favicon_path; ?>" type="image/png">
    
    <link rel="stylesheet" href="style.css?v=V.16">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <style>
        .kpi-wrapper {
            display: grid;
            /* [UPDATE V.16] 4 Kolom KPI */
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .kpi-box {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-left: 5px solid #007bff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .kpi-box.green { border-left-color: #28a745; }
        .kpi-box.orange { border-left-color: #ffc107; }
        .kpi-box.red { border-left-color: #dc3545; }
        .kpi-box .value {
            font-size: 2.2rem;
            font-weight: bold;
            color: #333;
            margin: 0 0 5px 0;
            /* [UPDATE V.16] Kecilkan font jika terlalu besar */
            overflow-wrap: break-word;
            font-size: clamp(1.5rem, 4vw, 2.2rem);
        }
        .kpi-box .title {
            font-size: 0.9rem;
            color: #777;
            margin: 0;
            text-transform: uppercase;
        }
        
        .chart-wrapper {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        @media (min-width: 992px) {
            .chart-wrapper {
                grid-template-columns: 2fr 1fr; 
            }
        }
        .chart-container {
             background-color: #ffffff;
             border: 1px solid #e0e0e0;
             padding: 20px;
             border-radius: 8px;
             box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .chart-container h3 { margin-top: 0; }
        
        .status-ditolak { background-color: #f8d7da !important; text-decoration: line-through; }
        .status-disetujui { background-color: #d4edda !important; }
        .status-menunggu { background-color: #fff3cd !important; }
        .status-ditolak-sebagian { background-color: #fff3cd !important; color: #856404; }
        .status-proses-pengadaan { background-color: #d1ecf1 !important; color: #0c5460; }
        .status-selesai-sebagian { background-color: #d1ecf1 !important; }
        .status-selesai-penuh { background-color: #d4edda !important; }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo">
            <img src="<?php echo $logo_path; ?>" alt="Logo">
            <h1><?php echo htmlspecialchars($nama_instansi, ENT_QUOTES, 'UTF-8'); ?> (Aset)</h1>
        </div>
        <div class="user-info">
            Selamat datang, <strong><?php echo htmlspecialchars($nama_login, ENT_QUOTES, 'UTF-8'); ?></strong>
            (Role: <?php echo htmlspecialchars(ucfirst($role_login), ENT_QUOTES, 'UTF-8'); ?>)
            <a href="index.php" style="color: #007bff;">Kembali ke Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="content">
            
            <h2>Laporan & Analitik Pengajuan Aset</h2>
            
            <div class="filter-panel">
                <form action="laporan.php" method="GET">
                    <div class="form-group">
                        <label for="tgl_awal">Tgl Awal:</label>
                        <input type="date" id="tgl_awal" name="tgl_awal" value="<?php echo htmlspecialchars($filter_tgl_awal, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="tgl_akhir">Tgl Akhir:</label>
                        <input type="date" id="tgl_akhir" name="tgl_akhir" value="<?php echo htmlspecialchars($filter_tgl_akhir, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="status_pengadaan">Status:</label>
                        <select id="filter_status" name="status_pengadaan">
                            <option value="Semua" <?php echo ($filter_status == 'Semua') ? 'selected' : ''; ?>>Semua Status</option>
                            <option value="Proses Pengadaan" <?php echo ($filter_status == 'Proses Pengadaan') ? 'selected' : ''; ?>>Proses Pengadaan</option>
                            <option value="Selesai Sebagian" <?php echo ($filter_status == 'Selesai Sebagian') ? 'selected' : ''; ?>>Selesai Sebagian</option>
                            <option value="Selesai Penuh" <?php echo ($filter_status == 'Selesai Penuh') ? 'selected' : ''; ?>>Selesai Penuh</option>
                            <option value="Ditolak" <?php echo ($filter_status == 'Ditolak') ? 'selected' : ''; ?>>Ditolak (Direktur)</option>
                        </select>
                    </div>
                    
                    <?php if ($role_login == 'direktur'): ?>
                    <div class="form-group">
                        <label for="pj_logum">PJ Logum:</label>
                        <select id="filter_pj" name="pj_logum">
                            <option value="Semua">Semua PJ Logum</option>
                            <?php foreach ($list_pj_logum as $pj): ?>
                            <option value="<?php echo htmlspecialchars($pj['nik'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($filter_pj == $pj['nik']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pj['nama'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-primary">Filter Laporan</button>
                    <a href="laporan.php" class="btn btn-secondary">Reset</a>
                </form>
            </div>
            
            <div class="kpi-wrapper">
                <div class="kpi-box">
                    <p class="value"><?php echo number_format($kpi['total_surat'], 0, ',', '.'); ?></p>
                    <p class="title">Total Surat Diajukan</p>
                </div>
                <div class="kpi-box orange">
                    <p class="value">Rp <?php echo number_format($kpi['total_nilai_disetujui'], 0, ',', '.'); ?></p>
                    <p class="title">Total Nilai Disetujui (Estimasi)</p>
                </div>
                <div class="kpi-box green">
                    <p class="value">Rp <?php echo number_format($kpi['total_realisasi'], 0, ',', '.'); ?></p>
                    <p class="title">Total Nilai Realisasi (Aktual)</p>
                </div>
                <div class="kpi-box <?php echo ($kpi['selisih'] >= 0 ? 'green' : 'red'); ?>">
                    <p class="value">Rp <?php echo number_format($kpi['selisih'], 0, ',', '.'); ?></p>
                    <p class="title"><?php echo ($kpi['selisih'] >= 0 ? 'Penghematan' : 'Kelebihan Biaya'); ?></p>
                </div>
            </div>
            
            <div class="chart-wrapper">
                <div class="chart-container">
                    <h3>Estimasi vs Realisasi Biaya (12 Bulan Terakhir)</h3>
                    <canvas id="chartBiayaBulanan"></canvas>
                </div>
                <div class="chart-container">
                    <h3>Ringkasan Status Pengadaan</h3>
                    <canvas id="chartStatusPengadaan"></canvas>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Detail Laporan Pengajuan</h3>
                <p>Menampilkan data historis berdasarkan filter yang dipilih.</p>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Aksi</th>
                                <th>No. Surat</th>
                                <th>Tanggal</th>
                                <th>Pengaju</th>
                                <th>PJ Logum</th>
                                <th>Total Disetujui (Rp)</th>
                                <th>Total Realisasi (Rp)</th>
                                <th>Status Logum</th>
                                <th>Status Direktur</th>
                                <th>Status Pengadaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($list_laporan_detail)): ?>
                                <tr>
                                    <td colspan="10" style="text-align: center;">Tidak ada data untuk ditampilkan berdasarkan filter ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($list_laporan_detail as $row): ?>
                                <tr>
                                    <td class="actions">
                                        <a href="index.php?action=detail_lengkap&id=<?php echo htmlspecialchars($row['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?>" 
                                           target="_blank" 
                                           class="btn btn-primary btn-sm">
                                           Lihat Detail
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['no_surat_pengajuan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($row['tanggal_pengajuan'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_pengaju'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_pj_logum'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td style="text-align: right;"><?php echo number_format($row['total_disetujui'], 0, ',', '.'); ?></td>
                                    <td style="text-align: right;"><?php echo number_format($row['total_realisasi'], 0, ',', '.'); ?></td>
                                    <td class="status-<?php echo strtolower(str_replace(' ', '-', $row['status_approval_logum'])); ?>"><?php echo htmlspecialchars($row['status_approval_logum'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="status-<?php echo strtolower(str_replace(' ', '-', $row['status_approval_direktur'])); ?>"><?php echo htmlspecialchars($row['status_approval_direktur'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="status-<?php echo strtolower(str_replace(' ', '-', $row['status_pengadaan'])); ?>"><?php echo htmlspecialchars($row['status_pengadaan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        <footer class="footer">
            Copyright &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($nama_instansi, ENT_QUOTES, 'UTF-8'); ?>
        </footer>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    
    <script>
        /*
         * ==================================================================
         * JavaScript untuk Aplikasi Pengajuan Aset (Tahap 4.B / V.16)
         * ==================================================================
         */
        
        // Komentar: Data dari PHP di-passing ke JavaScript
        var labelsBar = <?php echo json_encode($chart_labels_bar); ?>;
        // [UPDATE V.16] Data untuk 2 bar
        var dataBarEstimasi = <?php echo json_encode($chart_data_bar_estimasi); ?>;
        var dataBarRealisasi = <?php echo json_encode($chart_data_bar_realisasi); ?>;
        
        var labelsDonut = <?php echo json_encode($chart_labels_donut); ?>;
        var dataDonut = <?php echo json_encode($chart_data_donut); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            
            // Komentar: Inisialisasi TomSelect untuk filter
            if (document.getElementById('filter_status')) {
                new TomSelect("#filter_status",{ create: false });
            }
            if (document.getElementById('filter_pj')) {
                new TomSelect("#filter_pj",{ create: false });
            }

            // [UPDATE V.16] 1. Membuat Grafik Batang (Estimasi vs Realisasi)
            var ctxBar = document.getElementById('chartBiayaBulanan');
            if (ctxBar) {
                new Chart(ctxBar.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: labelsBar,
                        datasets: [
                            {
                                label: 'Total Estimasi Disetujui (Rp)',
                                data: dataBarEstimasi,
                                backgroundColor: 'rgba(255, 193, 7, 0.7)', // Orange
                                borderColor: 'rgba(255, 193, 7, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Total Realisasi (Rp)',
                                data: dataBarRealisasi,
                                backgroundColor: 'rgba(40, 167, 69, 0.7)', // Green
                                borderColor: 'rgba(40, 167, 69, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        legend: { position: 'bottom' },
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                    callback: function(value, index, values) {
                                        // Format Rupiah
                                        return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                                    }
                                }
                            }]
                        },
                        tooltips: {
                             callbacks: {
                                label: function(tooltipItem, data) {
                                    var label = data.datasets[tooltipItem.datasetIndex].label || '';
                                    var value = tooltipItem.yLabel;
                                    return label + ': Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                                }
                            }
                        }
                    }
                });
            }
            
            // Komentar: 2. Membuat Grafik Donat (Status Pengadaan)
            var ctxDonut = document.getElementById('chartStatusPengadaan');
            if (ctxDonut && dataDonut.length > 0) {
                 new Chart(ctxDonut.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: labelsDonut,
                        datasets: [{
                            data: dataDonut,
                            backgroundColor: [
                                '#007bff', // Proses Pengadaan
                                '#ffc107', // Selesai Sebagian
                                '#28a745', // Selesai Penuh
                                '#dc3545', // Ditolak
                                '#6c757d', // Baru
                                '#17a2b8'  // Menunggu
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        legend: {
                            position: 'bottom',
                        }
                    }
                });
            }

        });
    </script>
    
</body>
</html>