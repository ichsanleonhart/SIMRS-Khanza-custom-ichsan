<?php
/*
 * File laporan_billing_global.php
 * Menampilkan SEMUA data billing (Ralan, Ranap, Tunai, Piutang)
 * dengan filter datetime yang spesifik.
 * PHP 7.3 compatible.
 */

// 1. Set Judul & Sertakan Header (Otomatis koneksi & session check)
$page_title = "Laporan Billing Global";
require_once('includes/header.php');
require_once('includes/functions.php');

// 2. Ambil Parameter dari URL atau set default
$tgl_awal = isset($_GET['tgl_awal']) ? htmlspecialchars($_GET['tgl_awal']) : date('Y-m-d');
$jam_awal = isset($_GET['jam_awal']) ? htmlspecialchars($_GET['jam_awal']) : '00:00:00';
$tgl_akhir = isset($_GET['tgl_akhir']) ? htmlspecialchars($_GET['tgl_akhir']) : date('Y-m-d');
$jam_akhir = isset($_GET['jam_akhir']) ? htmlspecialchars($_GET['jam_akhir']) : '23:59:59';

// Gabungkan menjadi format DATETIME
$datetime_awal = $tgl_awal . ' ' . $jam_awal;
$datetime_akhir = $tgl_akhir . ' ' . $jam_akhir;

// 3. Inisialisasi Array Data
$data_billing = [];

// 4. Kueri SQL (UNION ALL)
// Komentar: Kita menggabungkan 2 kueri (Ralan & Ranap) menjadi satu
$sql_union = "
    (SELECT 
        reg_periksa.no_rawat, 
        nota_jalan.no_nota, 
        pasien.nm_pasien, 
        nota_jalan.tanggal, 
        nota_jalan.jam, 
        penjab.png_jawab, 
        'Ralan' AS status_lanjut,
        (SELECT SUM(billing.totalbiaya) 
         FROM billing 
         WHERE billing.no_rawat = reg_periksa.no_rawat 
           AND billing.status NOT IN ('Potongan', 'Retur Obat')) AS total_rupiah,
        IF(reg_periksa.no_rawat IN (SELECT piutang_pasien.no_rawat FROM piutang_pasien WHERE piutang_pasien.no_rawat = reg_periksa.no_rawat), 'Piutang', 'Tunai') AS status_bayar
    FROM reg_periksa 
    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis 
    INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj 
    INNER JOIN nota_jalan ON reg_periksa.no_rawat = nota_jalan.no_rawat
    WHERE CONCAT(nota_jalan.tanggal, ' ', nota_jalan.jam) BETWEEN ? AND ?)
    
    UNION ALL
    
    (SELECT 
        reg_periksa.no_rawat, 
        nota_inap.no_nota, 
        pasien.nm_pasien, 
        nota_inap.tanggal, 
        nota_inap.jam, 
        penjab.png_jawab, 
        'Ranap' AS status_lanjut,
        (SELECT SUM(billing.totalbiaya) 
         FROM billing 
         WHERE billing.no_rawat = reg_periksa.no_rawat 
           AND billing.status NOT IN ('Potongan', 'Retur Obat')) AS total_rupiah,
        IF(reg_periksa.no_rawat IN (SELECT piutang_pasien.no_rawat FROM piutang_pasien WHERE piutang_pasien.no_rawat = reg_periksa.no_rawat), 'Piutang', 'Tunai') AS status_bayar
    FROM reg_periksa 
    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis 
    INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj 
    INNER JOIN nota_inap ON reg_periksa.no_rawat = nota_inap.no_rawat
    WHERE CONCAT(nota_inap.tanggal, ' ', nota_inap.jam) BETWEEN ? AND ?)
    
    ORDER BY tanggal, jam
";

$stmt = $koneksi->prepare($sql_union);
if ($stmt) {
    // Bind 4 parameter (2 untuk Ralan, 2 untuk Ranap)
    $stmt->bind_param("ssss", $datetime_awal, $datetime_akhir, $datetime_awal, $datetime_akhir);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data_billing[] = $row;
    }
    $stmt->close();
} else {
    echo "Error preparing statement: " . $koneksi->error;
}
?>

<!-- 
=============================================================================
 BAGIAN TAMPILAN HTML DIMULAI DI SINI
=============================================================================
-->

<div class="container-fluid">
    <!-- 1. Form Filter (Sesuai permintaan) -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title">Filter Billing Global</h5>
            <form action="laporan_billing_global.php" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="tgl_awal" class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" name="tgl_awal" id="tgl_awal" value="<?php echo $tgl_awal; ?>">
                </div>
                <div class="col-md-2">
                    <label for="jam_awal" class="form-label">Jam</label>
                    <input type="time" class="form-control" name="jam_awal" id="jam_awal" value="<?php echo $jam_awal; ?>">
                </div>
                <div class="col-md-3">
                    <label for="tgl_akhir" class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" name="tgl_akhir" id="tgl_akhir" value="<?php echo $tgl_akhir; ?>">
                </div>
                <div class="col-md-2">
                    <label for="jam_akhir" class="form-label">Jam</label>
                    <input type="time" class="form-control" name="jam_akhir" id="jam_akhir" value="<?php echo $jam_akhir; ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Tabel Data -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title">Data Billing (<?php echo count($data_billing); ?> Transaksi)</h5>
            <div class="table-responsive">
			<table id="tabel-billing-global" class="table table-striped table-bordered table-sm" style="width:100%">
                <thead>
                    <tr>
                        <th>Waktu Bayar</th>
                        <th>No. Rawat</th>
                        <th>No. Nota</th>
                        <th>Nama Pasien</th>
                        <th>Status Pasien</th> <!-- Ralan/Ranap -->
                        <th>Penjamin</th>
                        <th>Status Bayar</th> <!-- Tunai/Piutang -->
                        <th class="text-end">Total (Rp)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data_billing as $data): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($data['tanggal'] . ' ' . $data['jam']); ?></td>
                        <td><?php echo htmlspecialchars($data['no_rawat']); ?></td>
                        <td><?php echo htmlspecialchars($data['no_nota']); ?></td>
                        <td><?php echo htmlspecialchars($data['nm_pasien']); ?></td>
                        <td><?php echo htmlspecialchars($data['status_lanjut']); ?></td>
                        <td><?php echo htmlspecialchars($data['png_jawab']); ?></td>
                        <td><?php 
                            if ($data['status_bayar'] == 'Piutang') {
                                echo '<span class="badge bg-warning text-dark">Piutang</span>';
                            } else {
                                echo '<span class="badge bg-success">Tunai</span>';
                            }
                        ?></td>
                        <td class="text-end"><?php echo formatRupiah($data['total_rupiah']); ?></td>
                        <td>
                            <button type="button" class="btn btn-success btn-sm btn-lihat-nota" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalDetailNota"
                                    data-norawat="<?php echo htmlspecialchars($data['no_rawat']); ?>"
                                    data-nonota="<?php echo htmlspecialchars($data['no_nota']); ?>">
                                Nota
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
			</div>
        </div>
    </div>
</div>

<!-- 
=============================================================================
 BAGIAN MODAL (POPUP) UNTUK "LIHAT NOTA"
 (Menggunakan kode yang sudah Anda buat di laporan_detail.php)
=============================================================================
-->
<div class="modal fade" id="modalDetailNota" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel">Detail Isi Nota: <span id="nomor-nota-modal">...</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="isi-nota-container">
                    <p class="text-center">Memuat data...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>


<!-- 
=============================================================================
 BAGIAN JAVASCRIPT DIMULAI DI SINI
=============================================================================
-->
<?php
// Komentar: Kita 'inject' JavaScript ini ke footer.php
ob_start(); 
?>
<script>
    
    // Fungsi helper JS untuk format Rupiah
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

    // Komentar: Jalankan skrip saat dokumen siap
    $(document).ready(function() {
        
        // Komentar: Inisialisasi DataTables untuk tabel global
        $('#tabel-billing-global').DataTable({ 
            "responsive": true, 
            "order": [[ 0, "desc" ]],
            "pageLength": 25, // Tampilkan 25 data per halaman
            "lengthChange": true
        });
        
        
        // Komentar: Event handler untuk tombol "Lihat Nota"
        // (Ini adalah kode yang sama persis dari file laporan_detail.php Anda)
        $(document).on('click', '.btn-lihat-nota', function() {
            var noRawat = $(this).data('norawat');
            var noNota = $(this).data('nonota');
            
            $("#nomor-nota-modal").text(noNota + " (No. Rawat: " + noRawat + ")");
            $("#isi-nota-container").html("<p class='text-center'>Memuat data...</p>");

            $.ajax({
                url: "api/get_detail_nota.php", // Memanggil API yang sudah ada
                type: "GET",
                data: { no_rawat: noRawat }, 
                dataType: "json",
                success: function(response) {
                    var html = '<table class="table table-sm">';
                    html += '<thead style="border-bottom: 2px solid #333;"><tr>';
                    html += '<th scope="col" style="width: 5%;">Ket.</th>';
                    html += '<th scope="col" style="width: 45%;">Perawatan/Tindakan/Obat</th>';
                    html += '<th scope="col" style="width: 20%;">Status</th>';
                    html += '<th scope="col" class="text-end" style="width: 10%;">Biaya</th>';
                    html += '<th scope="col" class="text-center" style="width: 5%;">Jml</th>';
                    html += '<th scope="col" class="text-end" style="width: 15%;">Total</th>';
                    html += '</tr></thead><tbody>';
                    
                    var grandTotal = 0;
                    
                    if (Array.isArray(response) && response.length > 0) {
                        response.forEach(function(item) {
                            var no = item.no || '';
                            var nm_perawatan = item.nm_perawatan || 'N/A';
                            var status = item.status || 'N/A';
                            var biaya = parseFloat(item.biaya) || 0;
                            var jumlah = parseFloat(item.jumlah) || 0;
                            var totalbiaya = parseFloat(item.totalbiaya) || 0;

                            html += '<tr>';
                            html += '<td>' + (no || '') + '</td>';
                            html += '<td>' + (nm_perawatan) + '</td>';
                            html += '<td>' + (status) + '</td>';
                            html += '<td class="text-end">' + (biaya > 0 ? formatRupiah(biaya) : 'Rp 0') + '</td>';
                            html += '<td class="text-center">' + (jumlah > 0 ? jumlah : '0') + '</td>';
                            html += '<td class="text-end">' + (totalbiaya > 0 ? formatRupiah(totalbiaya) : 'Rp 0') + '</td>';
                            html += '</tr>';
                            
                            if (status !== '' && status !== '-') {
                                grandTotal += totalbiaya;
                            }
                        });
                    } else {
                        html += '<tr><td colspan="6" class="text-center">Tidak ada data detail billing ditemukan.</td></tr>';
                    }
                    
                    html += '</tbody><tfoot style="border-top: 2px solid #333;">';
                    html += '<tr><th colspan="5" class="text-end h5">Grand Total:</th><th class="text-end h5">' + formatRupiah(grandTotal) + '</th></tr>';
                    html += '</tfoot></table>';
                    
                    $("#isi-nota-container").html(html);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $("#isi-nota-container").html("<p class='text-danger'>Gagal memuat data. Status: " + textStatus + ", Error: " + errorThrown + "</p>");
                }
            });
        });
        
    });
</script>
<?php
// Komentar: Simpan semua skrip JS di atas ke variabel $page_js
$page_js = ob_get_clean();
?>

<?php
// 8. Sertakan Footer
require_once('includes/footer.php');
?>