<?php
/*
 * File dashboard.php (PERBAIKAN FATAL ERROR)
 * Halaman utama untuk owner. Menampilkan filter, KPI, dan Grafik.
 * PHP 7.3 compatible.
 */

// 1. Set Judul Halaman (akan digunakan oleh header.php)
$page_title = "Dashboard Keuangan";

// 2. Sertakan Header (Otomatis koneksi & session check)
require_once('includes/header.php');

// 3. Sertakan Fungsi-fungsi Bantuan
require_once('includes/functions.php');

// 4. Proses Logika Filter Tanggal
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');


// 5. Logika Perhitungan KPI
// ==========================================================
// PERBAIKAN: Inisialisasi semua variabel KPI ke 0
// ==========================================================
$total_pemasukan_tunai = 0;
$total_pengeluaran = 0;
$total_piutang_terbentuk = 0;
$net_cash_flow = 0;
// ==========================================================

// Ambil semua definisi shift
$shift_times = getShiftTimes($koneksi);

// Buat rentang tanggal untuk di-loop
$start_date = new DateTime($tgl_awal);
$end_date = new DateTime($tgl_akhir);
$end_date->modify('+1 day');

$interval = new DateInterval('P1D');
$date_range = new DatePeriod($start_date, $interval, $end_date);

// --- Mulai Loop Perhitungan KPI ---
if ($date_range) {
    foreach ($date_range as $tanggal) {
        $tanggal_str = $tanggal->format('Y-m-d');

        foreach ($shift_times as $nama_shift => $times) {
            
            $range = getShiftDateTimeRange($tanggal_str, $nama_shift, $shift_times);

            // A. KUERI PEMASUKAN RALAN (TUNAI)
            $sql_ralan = "
                SELECT SUM(billing.totalbiaya) AS Total
                FROM billing
                INNER JOIN nota_jalan ON billing.no_rawat = nota_jalan.no_rawat
                WHERE 
                    CONCAT(nota_jalan.tanggal, ' ', nota_jalan.jam) BETWEEN ? AND ?
                    AND billing.no_rawat NOT IN (
                        SELECT piutang_pasien.no_rawat 
                        FROM piutang_pasien 
                        WHERE piutang_pasien.no_rawat = billing.no_rawat
                    )
                    AND billing.status NOT IN ('Potongan', 'Retur Obat')
            ";
            
            // Komentar: Menambahkan error handling untuk prepare
            $stmt_ralan = $koneksi->prepare($sql_ralan);
            if ($stmt_ralan) {
                $stmt_ralan->bind_param("ss", $range['start'], $range['end']);
                $stmt_ralan->execute();
                $result_ralan = $stmt_ralan->get_result();
                if ($result_ralan) {
                    $total_pemasukan_tunai += (float) $result_ralan->fetch_assoc()['Total'];
                }
                $stmt_ralan->close();
            }

            // B. KUERI PEMASUKAN RANAP (TUNAI)
            $sql_ranap = "
                SELECT SUM(billing.totalbiaya) AS Total
                FROM billing
                INNER JOIN nota_inap ON billing.no_rawat = nota_inap.no_rawat
                WHERE 
                    CONCAT(nota_inap.tanggal, ' ', nota_inap.jam) BETWEEN ? AND ?
                    AND billing.no_rawat NOT IN (
                        SELECT piutang_pasien.no_rawat 
                        FROM piutang_pasien 
                        WHERE piutang_pasien.no_rawat = billing.no_rawat
                    )
                    AND billing.status NOT IN ('Potongan', 'Retur Obat')
            ";
            
            $stmt_ranap = $koneksi->prepare($sql_ranap);
            if ($stmt_ranap) {
                $stmt_ranap->bind_param("ss", $range['start'], $range['end']);
                $stmt_ranap->execute();
                $result_ranap = $stmt_ranap->get_result();
                if ($result_ranap) {
                    $total_pemasukan_tunai += (float) $result_ranap->fetch_assoc()['Total'];
                }
                $stmt_ranap->close();
            }

            // C. KUERI PEMASUKAN LAIN
            $sql_lain = "
                SELECT SUM(pemasukan_lain.besar) AS Total
                FROM pemasukan_lain
                WHERE pemasukan_lain.tanggal BETWEEN ? AND ?
            ";
            $stmt_lain = $koneksi->prepare($sql_lain);
            if($stmt_lain) {
                $stmt_lain->bind_param("ss", $range['start'], $range['end']);
                $stmt_lain->execute();
                $result_lain = $stmt_lain->get_result();
                if ($result_lain) {
                    $total_pemasukan_tunai += (float) $result_lain->fetch_assoc()['Total'];
                }
                $stmt_lain->close();
            }
            
            // D. KUERI PENGELUARAN
            $sql_keluar = "
                SELECT SUM(pengeluaran_harian.biaya) AS Total
                FROM pengeluaran_harian
                WHERE pengeluaran_harian.tanggal BETWEEN ? AND ?
            ";
            $stmt_keluar = $koneksi->prepare($sql_keluar);
            if($stmt_keluar) {
                $stmt_keluar->bind_param("ss", $range['start'], $range['end']);
                $stmt_keluar->execute();
                $result_keluar = $stmt_keluar->get_result();
                if ($result_keluar) {
                    $total_pengeluaran += (float) $result_keluar->fetch_assoc()['Total'];
                }
                $stmt_keluar->close();
            }
        }
    }
}
// --- Selesai Loop Perhitungan KPI ---


// E. KUERI PIUTANG TERBENTUK
// Komentar: Ini dihitung per TANGGAL (tgl_piutang), bukan per jam shift.
$sql_piutang = "
    SELECT SUM(piutang_pasien.totalpiutang) AS Total
    FROM piutang_pasien
    WHERE piutang_pasien.tgl_piutang BETWEEN ? AND ?
";

// ==========================================================
// PERBAIKAN: Menambahkan error handling untuk kueri piutang
// ==========================================================
$stmt_piutang = $koneksi->prepare($sql_piutang);
if ($stmt_piutang) {
    $stmt_piutang->bind_param("ss", $tgl_awal, $tgl_akhir);
    $stmt_piutang->execute();
    $result_piutang = $stmt_piutang->get_result();
    if ($result_piutang) {
        $row_piutang = $result_piutang->fetch_assoc();
        // Pastikan $total_piutang_terbentuk adalah angka
        $total_piutang_terbentuk = (float) $row_piutang['Total'];
    }
    $stmt_piutang->close();
} else {
    // Jika kueri prepare gagal, set ke 0 agar tidak error
    $total_piutang_terbentuk = 0;
    // Tampilkan error di log (opsional, tapi bagus untuk debug)
    error_log("Gagal prepare kueri piutang: " . $koneksi->error);
}
// ==========================================================


// 6. Hitung KPI Final
$net_cash_flow = $total_pemasukan_tunai - $total_pengeluaran;

?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5 class="card-title">Filter Data</h5>
        <form action="dashboard.php" method="GET" class="row g-3">
            <div class="col-md-5">
                <label for="tgl_awal" class="form-label">Dari Tanggal</label>
                <input type="date" class="form-control" name="tgl_awal" id="tgl_awal" value="<?php echo htmlspecialchars($tgl_awal); ?>">
            </div>
            <div class="col-md-5">
                <label for="tgl_akhir" class="form-label">Sampai Tanggal</label>
                <input type="date" class="form-control" name="tgl_akhir" id="tgl_akhir" value="<?php echo htmlspecialchars($tgl_akhir); ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-success w-100">Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
						<!--<div class="text-xs font-weight-bold text-success text-uppercase mb-1">Pemasukan Tunai</div>-->
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1" 
						data-bs-toggle="tooltip" 
						data-bs-placement="bottom" 
						data-bs-title="Total uang tunai yang diterima dari Ralan, Ranap, dan Pemasukan Lain (Omzet Tunai). Belum dikurangi pengeluaran.">
						Pemasukan Tunai
						</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo formatRupiah($total_pemasukan_tunai); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1"
                             data-bs-toggle="tooltip" 
                             data-bs-placement="bottom" 
                             data-bs-title="Total uang tunai yang dikeluarkan untuk biaya operasional harian (dari tabel pengeluaran_harian).">
                             Pengeluaran Tunai
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo formatRupiah($total_pengeluaran); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1"
                             data-bs-toggle="tooltip" 
                             data-bs-placement="bottom" 
                             data-bs-title="Kas Bersih. Dihitung dari [Pemasukan Tunai] DIKURANGI [Pengeluaran Tunai]. Menunjukkan sisa uang tunai di akhir periode.">
                             Nett Cash Flow (Kas Bersih)
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo formatRupiah($net_cash_flow); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <a href="laporan_piutang_detail.php?tgl_awal=<?php echo htmlspecialchars($tgl_awal); ?>&tgl_akhir=<?php echo htmlspecialchars($tgl_akhir); ?>" style="text-decoration: none;">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1"
                                 data-bs-toggle="tooltip" 
                                 data-bs-placement="bottom" 
                                 data-bs-title="KLIK UNTUK MELIHAT DETAIL. Total tagihan yang belum dibayar (masuk ke piutang_pasien) dalam rentang tanggal ini.">
                                 Piutang Terbentuk (Klik Detail)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo formatRupiah($total_piutang_terbentuk); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>
</div>

<div class="row">
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Grafik Tren Pemasukan vs Pengeluaran Harian</h6>
            </div>
            <div class="card-body">
                <canvas id="chartTrenHarian"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Total Pemasukan Tunai per Shift</h6>
            </div>
			
			<div class="card-body">
                <canvas id="chartPerShift"></canvas>
                
                <hr class="mt-4">
                <h6 class="text-center">Lihat Detail Laporan per Shift</h6>
                
				<div class="d-grid gap-2 mt-4">
					<a class="btn btn-info" href="laporan_detail.php?tgl_awal=<?php echo htmlspecialchars($tgl_awal); ?>&tgl_akhir=<?php echo htmlspecialchars($tgl_akhir); ?>">
					Lihat Laporan Detail (Per Tanggal & Per Shift)
					</a>
				</div>
				
                <!--<form action="laporan_detail.php" method="GET" class="row g-2">
                
                    <input type="hidden" name="tgl_awal" value="<?php echo htmlspecialchars($tgl_awal); ?>">
                    <input type="hidden" name="tgl_akhir" value="<?php echo htmlspecialchars($tgl_akhir); ?>">
                    
                     <div class="col-7">
                        <label for="shift_detail" class="visually-hidden">Pilih Shift</label>
                        <select name="shift" id="shift_detail" class="form-select">
                            <?php 
                            if (!empty($shift_times)) {
                                foreach ($shift_times as $nama_shift => $times) {
                                    echo '<option value="' . htmlspecialchars($nama_shift) . '">' . htmlspecialchars($nama_shift) . '</option>';
                                }
                            } else {
                                echo '<option value="" disabled>Data Shift Kosong</option>';
                            }
                            ?>
                        </select>
                    </div>  
                    <div class="col-5">
                        <button type="submit" class="btn btn-info w-100">Lihat Detail</button>
                    </div>
                </form> -->
                </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Komposisi Pengeluaran</h6>
            </div>
            <div class="card-body">
                <canvas id="chartPengeluaran"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Komposisi Pemasukan Tunai</h6>
            </div>
            <div class="card-body">
                <canvas id="chartKomposisiPemasukan"></canvas>
            </div>
        </div>
    </div>
</div>


<?php
// Komentar: Blok <script> ini (yang berisi fungsi-fungsi JavaScript)
// akan di-inject ke footer.php
ob_start(); 
?>

<script>
    // Komentar: Simpan variabel PHP (tanggal filter) ke dalam variabel JavaScript
    var tglAwal = '<?php echo $tgl_awal; ?>';
    var tglAkhir = '<?php echo $tgl_akhir; ?>';

    // Komentar: Deklarasikan variabel chart di scope global
    var myChartTrenHarian, myChartPerShift, myChartPengeluaran, myChartKomposisiPemasukan;

    // Komentar: Ambil konteks (canvas) dari masing-masing chart
    var ctxTren = document.getElementById('chartTrenHarian').getContext('2d');
    var ctxShift = document.getElementById('chartPerShift').getContext('2d');
    var ctxKeluar = document.getElementById('chartPengeluaran').getContext('2d');
    var ctxMasuk = document.getElementById('chartKomposisiPemasukan').getContext('2d');

    
    // =========================================================================
    // FUNGSI HELPER JAVASCRIPT
    // =========================================================================
    function formatRupiah(angka) {
        if(angka == null || isNaN(angka)) return "Rp 0";
        
        var number_string = angka.toString().replace(/[^,\d]/g, ''),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);
            
        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        
        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return 'Rp ' + rupiah;
    }
    // =========================================================================


    // Komentar: Fungsi ini memanggil API untuk mengambil data
    function loadAllCharts() {
        var apiUrl = 'api/data_grafik.php?tgl_awal=' + tglAwal + '&tgl_akhir=' + tglAkhir;

        fetch(apiUrl)
            .then(function(response) {
                if (!response.ok) {
                    // Jika API mengembalikan error (spt 404 atau 500), lempar error
                    throw new Error('API request failed with status ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                // Jika data sukses diterima (format JSON valid), panggil fungsi render
                renderChartTren(data.tren_harian);
                renderChartPerShift(data.per_shift);
                renderChartPengeluaran(data.komposisi_pengeluaran);
                renderChartKomposisiPemasukan(data.komposisi_pemasukan);
            })
            .catch(function(error) {
                console.error('Error fetching or parsing data:', error);
                // Tampilkan error di console browser (F12) jika API gagal
                ctxTren.fillText('Gagal memuat data grafik tren.', 10, 50);
                ctxShift.fillText('Gagal memuat data grafik shift.', 10, 50);
                // (Ini akan membantu kita debug jika api/data_grafik.php error)
            });
    }

    // 1. Fungsi untuk merender Grafik Tren Harian
    function renderChartTren(data) {
        if (myChartTrenHarian) {
            myChartTrenHarian.destroy(); 
        }
        myChartTrenHarian = new Chart(ctxTren, {
            type: 'line',
            data: {
                labels: data.labels, 
                datasets: [{
                    label: 'Pemasukan Tunai',
                    data: data.pemasukan,
                    borderColor: 'rgb(25, 135, 84)', // Hijau
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    fill: true,
                    tension: 0.1
                }, {
                    label: 'Pengeluaran Tunai',
                    data: data.pengeluaran,
                    borderColor: 'rgb(220, 53, 69)', // Merah
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + formatRupiah(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function(value, index, values) {
                                // Format Sumbu Y sebagai Rupiah
                                return formatRupiah(value);
                            }
                        }
                    }
                }
            }
        });
    }
    
    // 2. Fungsi untuk merender Grafik Pemasukan per Shift (Bar Chart)
    function renderChartPerShift(data) {
        if (myChartPerShift) {
            myChartPerShift.destroy();
        }
        myChartPerShift = new Chart(ctxShift, {
            type: 'bar',
            data: {
                labels: data.labels, // Dinamis: ['Pagi', 'Siang', 'Malam']
                datasets: [{
                    label: 'Total Pemasukan Tunai',
                    data: data.data,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)', // Pagi
                        'rgba(54, 162, 235, 0.2)', // Siang
                        'rgba(255, 206, 86, 0.2)', // Sore
                        'rgba(75, 192, 192, 0.2)'  // Malam
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                
                // ==========================================================
                // PERUBAHAN: Bagian 'onClick' telah dihapus dari sini.
                // ==========================================================

                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Total: ' + formatRupiah(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function(value, index, values) {
                                return formatRupiah(value);
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Fungsi untuk merender Grafik Komposisi Pengeluaran
    function renderChartPengeluaran(data) {
        if (myChartPengeluaran) {
            myChartPengeluaran.destroy();
        }
        myChartPengeluaran = new Chart(ctxKeluar, {
            type: 'doughnut',
            data: {
                labels: data.labels, 
                datasets: [{
                    data: data.data,
                    backgroundColor: ['#e74c3c', '#f1c40f', '#9b59b6', '#3498db', '#2ecc71', '#e67e22']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                return label + ': ' + formatRupiah(context.parsed);
                            }
                        }
                    }
                }
            }
        });
    }

    // 4. Fungsi untuk merender Grafik Komposisi Pemasukan
    function renderChartKomposisiPemasukan(data) {
        if (myChartKomposisiPemasukan) {
            myChartKomposisiPemasukan.destroy();
        }
        myChartKomposisiPemasukan = new Chart(ctxMasuk, {
            type: 'pie',
            data: {
                labels: data.labels, // ['Ralan', 'Ranap', 'Pemasukan Lain']
                datasets: [{
                    data: data.data,
                    backgroundColor: ['#2ecc71', '#3498db', '#e67e22']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                return label + ': ' + formatRupiah(context.parsed);
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Komentar: Panggil fungsi utama saat halaman selesai dimuat
    document.addEventListener("DOMContentLoaded", function() {
        // KODE BARU: Mengaktifkan semua tooltip di halaman ini
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl)
        })
        
        // Memuat semua grafik (kode lama)
        loadAllCharts();
    });

</script>

<?php
// Komentar: Ambil output <script> dan simpan ke variabel $page_js
$page_js = ob_get_clean();
?>

<?php
// 7. Sertakan Footer
require_once('includes/footer.php');
?>