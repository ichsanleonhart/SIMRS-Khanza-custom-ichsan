<?php
/*
 * File: kunjungan_belum_closing.php
 * Integrasi dari 'kunjungan_aktif.php'
 * Menampilkan pasien yang status bayarnya 'Belum Bayar' dan belum ada di billing.
 */

// 1. Setup Dashboard
$page_title = "Kunjungan Belum Closing Kasir";
require_once('includes/header.php');
require_once('includes/functions.php'); // Memuat cariIsiAngka & cariIsi

// 2. Inisialisasi Variabel Filter
// Menggunakan default awal bulan ini s/d hari ini jika tidak ada input
$tgl_awal = isset($_POST['tgl_awal']) ? $_POST['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : date('Y-m-d');
$data = [];

// 3. Logika Pengambilan Data
if ($koneksi) {
    $sql = "
        SELECT
            reg_periksa.no_rawat,
            reg_periksa.tgl_registrasi AS 'Tgl Reg',
            reg_periksa.jam_reg AS 'Jam reg',
            penjab.png_jawab AS 'Penjamin',
            poliklinik.nm_poli AS 'Poliklinik',
            reg_periksa.no_rkm_medis AS 'no_rm',
            pasien.nm_pasien AS 'nama_pasien',
            reg_periksa.status_lanjut,
            reg_periksa.stts AS 'Status Pelayanan',
            reg_periksa.biaya_reg
        FROM reg_periksa
        LEFT JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
        LEFT JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
        LEFT JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
        WHERE
            reg_periksa.status_bayar = 'Belum Bayar'
            AND reg_periksa.no_rawat NOT IN (SELECT no_rawat FROM billing)
            AND reg_periksa.tgl_registrasi BETWEEN ? AND ?
        ORDER BY
            reg_periksa.tgl_registrasi DESC,
            reg_periksa.jam_reg DESC
    ";
    
    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $tgl_awal, $tgl_akhir);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $no_rawat = $row['no_rawat'];
                
                // --- Hitung Biaya (Kalkulasi Manual seperti di DlgPerkiraanBiayaRanap.java) ---
                // Menggunakan fungsi helper yang sudah ada di includes/functions.php
                
                $Registrasi = $row['biaya_reg'];
                $Laborat = cariIsiAngka($koneksi, "select sum(biaya) from periksa_lab where no_rawat=?", $no_rawat) + cariIsiAngka($koneksi, "select sum(biaya_item) from detail_periksa_lab where no_rawat=?", $no_rawat);
                $Radiologi = cariIsiAngka($koneksi, "select sum(biaya) from periksa_radiologi where no_rawat=?", $no_rawat);
                $Operasi = cariIsiAngka($koneksi, "select sum(biayaoperator1+biayaoperator2+biayaoperator3+biayaasisten_operator1+biayaasisten_operator2+biayaasisten_operator3+biayainstrumen+biayadokter_anak+biayaperawaat_resusitas+biayadokter_anestesi+biayaasisten_anestesi+biayaasisten_anestesi2+biayabidan+biayabidan2+biayabidan3+biayaperawat_luar+biayaalat+biayasewaok+akomodasi+bagian_rs+biaya_omloop+biaya_omloop2+biaya_omloop3+biaya_omloop4+biaya_omloop5+biayasarpras+biaya_dokter_pjanak+biaya_dokter_umum) from operasi where no_rawat=?", $no_rawat);
                $Obat = cariIsiAngka($koneksi, "select sum(total) from detail_pemberian_obat where no_rawat=?", $no_rawat) + cariIsiAngka($koneksi, "select sum(besar_tagihan) from tagihan_obat_langsung where no_rawat=?", $no_rawat) + cariIsiAngka($koneksi, "select sum(hargasatuan*jumlah) from beri_obat_operasi where no_rawat=?", $no_rawat);
                $Ranap_Dokter = cariIsiAngka($koneksi, "select sum(biaya_rawat) from rawat_inap_dr where no_rawat=?", $no_rawat);
                $Ranap_Dokter_Paramedis = cariIsiAngka($koneksi, "select sum(biaya_rawat) from rawat_inap_drpr where no_rawat=?", $no_rawat);
                $Ranap_Paramedis = cariIsiAngka($koneksi, "select sum(biaya_rawat) from rawat_inap_pr where no_rawat=?", $no_rawat);
                $Ralan_Dokter = cariIsiAngka($koneksi, "select sum(biaya_rawat) from rawat_jl_dr where no_rawat=?", $no_rawat);
                $Ralan_Dokter_Paramedis = cariIsiAngka($koneksi, "select sum(biaya_rawat) from rawat_jl_drpr where no_rawat=?", $no_rawat);
                $Ralan_Paramedis = cariIsiAngka($koneksi, "select sum(biaya_rawat) from rawat_jl_pr where no_rawat=?", $no_rawat);
                $Tambahan = cariIsiAngka($koneksi, "select sum(besar_biaya) from tambahan_biaya where no_rawat=?", $no_rawat);
                $Potongan = cariIsiAngka($koneksi, "select sum(besar_pengurangan) from pengurangan_biaya where no_rawat=?", $no_rawat);
                $Kamar = cariIsiAngka($koneksi, "select sum(ttl_biaya) from kamar_inap where no_rawat=?", $no_rawat) + cariIsiAngka($koneksi, "select sum(biaya_sekali.besar_biaya) from biaya_sekali inner join kamar_inap on kamar_inap.kd_kamar=biaya_sekali.kd_kamar where kamar_inap.no_rawat=?", $no_rawat);
                $Harian = cariIsiAngka($koneksi, "select sum(biaya_harian.jml*biaya_harian.besar_biaya*kamar_inap.lama) from kamar_inap inner join biaya_harian on kamar_inap.kd_kamar=biaya_harian.kd_kamar where kamar_inap.no_rawat=?", $no_rawat);
                $Retur_Obat = (-1) * cariIsiAngka($koneksi, "select sum(subtotal) from detreturjual where no_retur_jual like ?", "%".$no_rawat."%");
                $Resep_Pulang = cariIsiAngka($koneksi, "select sum(total) from resep_pulang where no_rawat=?", $no_rawat);

                // Logika Ranap Gabung
                $no_rawat_gabung = cariIsi($koneksi, "select no_rawat2 from ranap_gabung where no_rawat=?", $no_rawat);
                if (!empty($no_rawat_gabung)) {
                    $Laborat += cariIsiAngka($koneksi, "select sum(biaya) from periksa_lab where no_rawat=?", $no_rawat_gabung) + cariIsiAngka($koneksi, "select sum(biaya_item) from detail_periksa_lab where no_rawat=?", $no_rawat_gabung);
                    $Radiologi += cariIsiAngka($koneksi, "select sum(biaya) from periksa_radiologi where no_rawat=?", $no_rawat_gabung);
                    $Operasi += cariIsiAngka($koneksi, "select sum(biayaoperator1+biayaoperator2+biayaoperator3+biayaasisten_operator1+biayaasisten_operator2+biayaasisten_operator3+biayainstrumen+biayadokter_anak+biayaperawaat_resusitas+biayadokter_anestesi+biayaasisten_anestesi+biayaasisten_anestesi2+biayabidan+biayabidan2+biayabidan3+biayaperawat_luar+biayaalat+biayasewaok+akomodasi+bagian_rs+biaya_omloop+biaya_omloop2+biaya_omloop3+biaya_omloop4+biaya_omloop5+biayasarpras+biaya_dokter_pjanak+biaya_dokter_umum) from operasi where no_rawat=?", $no_rawat_gabung);
                    $Obat += cariIsiAngka($koneksi, "select sum(total) from detail_pemberian_obat where no_rawat=?", $no_rawat_gabung) + cariIsiAngka($koneksi, "select sum(besar_tagihan) from tagihan_obat_langsung where no_rawat=?", $no_rawat_gabung) + cariIsiAngka($koneksi, "select sum(hargasatuan*jumlah) from beri_obat_operasi where no_rawat=?", $no_rawat_gabung);
                    $Ranap_Dokter += cariIsiAngka($koneksi, "select sum(biaya_rawat) from rawat_inap_dr where no_rawat=?", $no_rawat_gabung);
                    $Ranap_Dokter_Paramedis += cariIsiAngka($koneksi, "select sum(biaya_rawat) from rawat_inap_drpr where no_rawat=?", $no_rawat_gabung);
                    $Ranap_Paramedis += cariIsiAngka($koneksi, "select sum(biaya_rawat) from rawat_inap_pr where no_rawat=?", $no_rawat_gabung);
                    $Ralan_Dokter += cariIsiAngka($koneksi, "select sum(biaya_rawat) from rawat_jl_dr where no_rawat=?", $no_rawat_gabung);
                    $Ralan_Dokter_Paramedis += cariIsiAngka($koneksi, "select sum(biaya_rawat) from rawat_jl_drpr where no_rawat=?", $no_rawat_gabung);
                    $Ralan_Paramedis += cariIsiAngka($koneksi, "select sum(biaya_rawat) from rawat_jl_pr where no_rawat=?", $no_rawat_gabung);
                    $Tambahan += cariIsiAngka($koneksi, "select sum(besar_biaya) from tambahan_biaya where no_rawat=?", $no_rawat_gabung);
                    $Potongan += cariIsiAngka($koneksi, "select sum(besar_pengurangan) from pengurangan_biaya where no_rawat=?", $no_rawat_gabung);
                    $Kamar += cariIsiAngka($koneksi, "select sum(ttl_biaya) from kamar_inap where no_rawat=?", $no_rawat_gabung) + cariIsiAngka($koneksi, "select sum(biaya_sekali.besar_biaya) from biaya_sekali inner join kamar_inap on kamar_inap.kd_kamar=biaya_sekali.kd_kamar where kamar_inap.no_rawat=?", $no_rawat_gabung);
                    $Harian += cariIsiAngka($koneksi, "select sum(biaya_harian.jml*biaya_harian.besar_biaya*kamar_inap.lama) from kamar_inap inner join biaya_harian on kamar_inap.kd_kamar=biaya_harian.kd_kamar where kamar_inap.no_rawat=?", $no_rawat_gabung);
                    $Retur_Obat += (-1) * cariIsiAngka($koneksi, "select sum(subtotal) from detreturjual where no_retur_jual like ?", "%".$no_rawat_gabung."%");
                    $Resep_Pulang += cariIsiAngka($koneksi, "select sum(total) from resep_pulang where no_rawat=?", $no_rawat_gabung);
                }

                $Jumlah = $Laborat + $Radiologi + $Operasi + $Obat + 
                          $Ranap_Dokter + $Ranap_Dokter_Paramedis + $Ranap_Paramedis + 
                          $Ralan_Dokter + $Ralan_Dokter_Paramedis + $Ralan_Paramedis + 
                          $Tambahan + $Potongan + $Kamar + $Registrasi + 
                          $Harian + $Retur_Obat + $Resep_Pulang;
                
                $row['billing_sementara_raw'] = $Jumlah;
                $row['billing_sementara_formatted'] = number_format($Jumlah, 0, ',', '.');
                $row['Biaya Obat_raw'] = $Obat; 
                $row['Biaya Obat_formatted'] = number_format($Obat, 0, ',', '.');
                
                $data[] = $row;
            }
            
            // Sorting Logic (Temuan Audit Batal tapi ada biaya ke atas)
            function sort_audit_priority($a, $b) {
                $a_is_audit = ($a['Status Pelayanan'] == 'Batal' && $a['Biaya Obat_raw'] > 0 && $a['billing_sementara_raw'] > 0);
                $b_is_audit = ($b['Status Pelayanan'] == 'Batal' && $b['Biaya Obat_raw'] > 0 && $b['billing_sementara_raw'] > 0);

                if ($a_is_audit == $b_is_audit) {
                    $date_a = $a['Tgl Reg'] . ' ' . $a['Jam reg'];
                    $date_b = $b['Tgl Reg'] . ' ' . $b['Jam reg'];
                    return strcmp($date_b, $date_a);
                }
                return (int)$b_is_audit - (int)$a_is_audit;
            }
            usort($data, 'sort_audit_priority');
        }
        $stmt->close();
    }
}
?>

<div class="card mb-4 shadow-sm">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filter Data Kunjungan Belum Closing</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="kunjungan_belum_closing.php">
            <div class="row">
                <div class="col-md-5">
                    <div class="mb-3">
                        <label for="tgl_awal" class="form-label">Tanggal Awal Registrasi:</label>
                        <input type="date" class="form-control" id="tgl_awal" name="tgl_awal" value="<?php echo htmlspecialchars($tgl_awal); ?>">
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="mb-3">
                        <label for="tgl_akhir" class="form-label">Tanggal Akhir Registrasi:</label>
                        <input type="date" class="form-control" id="tgl_akhir" name="tgl_akhir" value="<?php echo htmlspecialchars($tgl_akhir); ?>">
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end mb-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Hasil Data (<?php echo count($data); ?> Kunjungan)</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm" id="laporanTable" width="100%" cellspacing="0">
                <thead class="table-dark">
                    <tr>
                        <th>No. Kunjungan</th>
                        <th>Tgl Reg</th>
                        <th>Jam Reg</th>
                        <th>Penjamin</th>
                        <th>Poliklinik</th>
                        <th>No. RM</th>
                        <th>Nama Pasien</th>
                        <th>Status Lanjut</th>
                        <th>Status Pelayanan</th>
                        <th class="text-end">Billing Sementara</th>
                        <th class="text-end">Biaya Obat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): ?>
                        <?php foreach ($data as $row):
                            $rowClass = '';
                            // Highlight merah jika Batal tapi ada biaya
                            if ($row['Status Pelayanan'] == 'Batal' && $row['Biaya Obat_raw'] > 0 && $row['billing_sementara_raw'] > 0) {
                                $rowClass = 'table-danger'; 
                            }
                        ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td><?php echo htmlspecialchars($row['no_rawat']); ?></td>
                            <td><?php echo htmlspecialchars($row['Tgl Reg']); ?></td>
                            <td><?php echo htmlspecialchars($row['Jam reg']); ?></td>
                            <td><?php echo htmlspecialchars($row['Penjamin']); ?></td>
                            <td><?php echo htmlspecialchars($row['Poliklinik']); ?></td>
                            <td><?php echo htmlspecialchars($row['no_rm']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_pasien']); ?></td>
                            <td><?php echo htmlspecialchars($row['status_lanjut']); ?></td>
                            <td><?php echo htmlspecialchars($row['Status Pelayanan']); ?></td>
                            <td class="text-end"><?php echo htmlspecialchars($row['billing_sementara_formatted']); ?></td>
                            <td class="text-end"><?php echo htmlspecialchars($row['Biaya Obat_formatted']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center py-4">Tidak ada data yang ditemukan untuk rentang tanggal ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// JavaScript untuk DataTables (di-inject ke footer)
ob_start();
?>
<script>
    $(document).ready(function() {
        <?php if (!empty($data)): ?>
        $('#laporanTable').DataTable({
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
            },
            "pageLength": 25,
            "order": [] // Gunakan urutan dari PHP (custom sort)
        });
        <?php endif; ?>
    });
</script>
<?php
$page_js = ob_get_clean();
?>

<?php
require_once('includes/footer.php');
?>