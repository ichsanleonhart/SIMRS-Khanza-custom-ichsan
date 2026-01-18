<?php
// modul/jasmed/index.php
session_start();
require_once '../../config/database.php';
require_once '../../config/fungsi.php';

if (!isset($_SESSION['login_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$title = 'Audit Jasa Medis';
$menu  = 'jasmed';
$role = $_SESSION['role'];
$user_dokter = $_SESSION['kd_dokter'] ?? '';
$can_view_all = ($role == 'admin' || $role == 'super_dokter');

// Filter
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-d');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
$kd_dokter = $_GET['kd_dokter'] ?? $user_dokter;
$default_cats = ['ralan_dr', 'ralan_pr', 'ranap_dr', 'ranap_pr', 'operasi', 'radiologi', 'laborat'];
$kategori = $_GET['kategori'] ?? $default_cats;

// Data Dokter (Admin)
$dokters = [];
if($can_view_all) {
    $stmt = $pdo->query("SELECT kd_dokter, nm_dokter FROM dokter WHERE status='1' ORDER BY nm_dokter ASC");
    $dokters = $stmt->fetchAll();
}

$hasil = [];
if(isset($_GET['proses'])) {
    try {
        $sql_parts = [];
        $params = [
            'tgl1' => $tgl_awal . " 00:00:00",
            'tgl2' => $tgl_akhir . " 23:59:59",
            'dokter' => $kd_dokter
        ];

        // --- 1. RALAN DOKTER ---
        if(in_array('ralan_dr', $kategori)){
            $sql_parts[] = "
            SELECT 
                'Ralan (Dr)' as sumber,
                r.no_rawat, r.no_rkm_medis, p.nm_pasien, 
                CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) as tgl_transaksi,
                jp.nm_perawatan as tindakan,
                t.biaya_rawat as total_biaya,
                t.tarif_tindakandr as jm_dokter
            FROM rawat_jl_dr t
            JOIN reg_periksa r ON t.no_rawat = r.no_rawat
            JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
            JOIN jns_perawatan jp ON t.kd_jenis_prw = jp.kd_jenis_prw
            WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter = :dokter
            ";
        }

        // --- 2. RALAN DOKTER & PARAMEDIS ---
        if(in_array('ralan_pr', $kategori)){
            $sql_parts[] = "
            SELECT 
                'Ralan (Dr+Pr)' as sumber,
                r.no_rawat, r.no_rkm_medis, p.nm_pasien, 
                CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) as tgl_transaksi,
                jp.nm_perawatan as tindakan,
                t.biaya_rawat as total_biaya,
                t.tarif_tindakandr as jm_dokter
            FROM rawat_jl_drpr t
            JOIN reg_periksa r ON t.no_rawat = r.no_rawat
            JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
            JOIN jns_perawatan jp ON t.kd_jenis_prw = jp.kd_jenis_prw
            WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter = :dokter
            ";
        }

        // --- 3. RANAP DOKTER ---
        if(in_array('ranap_dr', $kategori)){
            $sql_parts[] = "
            SELECT 
                'Ranap (Dr)' as sumber,
                r.no_rawat, r.no_rkm_medis, p.nm_pasien, 
                CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) as tgl_transaksi,
                jpi.nm_perawatan as tindakan,
                t.biaya_rawat as total_biaya,
                t.tarif_tindakandr as jm_dokter
            FROM rawat_inap_dr t
            JOIN reg_periksa r ON t.no_rawat = r.no_rawat
            JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
            JOIN jns_perawatan_inap jpi ON t.kd_jenis_prw = jpi.kd_jenis_prw
            WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter = :dokter
            ";
        }

        // --- 4. RANAP DOKTER & PARAMEDIS ---
        if(in_array('ranap_pr', $kategori)){
            $sql_parts[] = "
            SELECT 
                'Ranap (Dr+Pr)' as sumber,
                r.no_rawat, r.no_rkm_medis, p.nm_pasien, 
                CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) as tgl_transaksi,
                jpi.nm_perawatan as tindakan,
                t.biaya_rawat as total_biaya,
                t.tarif_tindakandr as jm_dokter
            FROM rawat_inap_drpr t
            JOIN reg_periksa r ON t.no_rawat = r.no_rawat
            JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
            JOIN jns_perawatan_inap jpi ON t.kd_jenis_prw = jpi.kd_jenis_prw
            WHERE CONCAT(t.tgl_perawatan, ' ', t.jam_rawat) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter = :dokter
            ";
        }

        // --- 5. OPERASI (FULL SUM COST) ---
        if(in_array('operasi', $kategori)){
            // Rumus Total Biaya Operasi (Manual Sum)
            $rumus_total_op = "(t.biayaoperator1 + t.biayaoperator2 + t.biayaoperator3 + t.biayaasisten_operator1 + t.biayaasisten_operator2 + t.biayaasisten_operator3 + t.biayainstrumen + t.biayadokter_anak + t.biayaperawaat_resusitas + t.biayadokter_anestesi + t.biayaasisten_anestesi + t.biayaasisten_anestesi2 + t.biayabidan + t.biayabidan2 + t.biayabidan3 + t.biayaperawat_luar + t.biayaalat + t.biayasewaok + t.akomodasi + t.bagian_rs + t.biaya_omloop + t.biaya_omloop2 + t.biaya_omloop3 + t.biaya_omloop4 + t.biaya_omloop5 + t.biayasarpras + t.biaya_dokter_pjanak + t.biaya_dokter_umum)";

            $ops_query = "
            SELECT 'Operasi (Op1)' as sumber, r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi as tgl_transaksi, CONCAT(po.nm_perawatan, ' (Operator 1)') as tindakan, $rumus_total_op as total_biaya, t.biayaoperator1 as jm_dokter
            FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket 
            WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.operator1 = :dokter

            UNION ALL
            SELECT 'Operasi (Op2)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (Operator 2)'), $rumus_total_op, t.biayaoperator2
            FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket 
            WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.operator2 = :dokter

            UNION ALL
            SELECT 'Operasi (Op3)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (Operator 3)'), $rumus_total_op, t.biayaoperator3
            FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket 
            WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.operator3 = :dokter

            UNION ALL
            SELECT 'Operasi (Dr Anak)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (Dokter Anak)'), $rumus_total_op, t.biayadokter_anak
            FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket 
            WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_anak = :dokter

            UNION ALL
            SELECT 'Operasi (Anestesi)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (Dokter Anestesi)'), $rumus_total_op, t.biayadokter_anestesi
            FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket 
            WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_anestesi = :dokter

            UNION ALL
            SELECT 'Operasi (Dr Umum)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (Dokter Umum)'), $rumus_total_op, t.biaya_dokter_umum
            FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket 
            WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_umum = :dokter
            
            UNION ALL
            SELECT 'Operasi (PJ Anak)', r.no_rawat, r.no_rkm_medis, p.nm_pasien, t.tgl_operasi, CONCAT(po.nm_perawatan, ' (PJ Anak)'), $rumus_total_op, t.biaya_dokter_pjanak
            FROM operasi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN paket_operasi po ON t.kode_paket=po.kode_paket 
            WHERE t.tgl_operasi BETWEEN :tgl1 AND :tgl2 AND t.dokter_pjanak = :dokter
            ";
            $sql_parts[] = $ops_query;
        }

        // --- 6. RADIOLOGI (PERBAIKAN TOTAL BIAYA PERUJUK) ---
        if(in_array('radiologi', $kategori)){
            $rad_query = "
            /* Pemeriksa */
            SELECT 'Radiologi' as sumber, r.no_rawat, r.no_rkm_medis, p.nm_pasien, 
            CONCAT(t.tgl_periksa, ' ', t.jam) as tgl_transaksi,
            jpr.nm_perawatan as tindakan, t.biaya as total_biaya, t.tarif_tindakan_dokter as jm_dokter
            FROM periksa_radiologi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_radiologi jpr ON t.kd_jenis_prw=jpr.kd_jenis_prw
            WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter = :dokter

            UNION ALL
            /* Perujuk (Sekarang munculkan Biaya Asli) */
            SELECT 'Radiologi (Perujuk)' as sumber, r.no_rawat, r.no_rkm_medis, p.nm_pasien, 
            CONCAT(t.tgl_periksa, ' ', t.jam) as tgl_transaksi,
            CONCAT(jpr.nm_perawatan, ' (Perujuk)') as tindakan, 
            t.biaya as total_biaya, /* Ambil Biaya Asli */
            t.tarif_perujuk as jm_dokter 
            FROM periksa_radiologi t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_radiologi jpr ON t.kd_jenis_prw=jpr.kd_jenis_prw
            WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.dokter_perujuk = :dokter
            ";
            $sql_parts[] = $rad_query;
        }

        // --- 7. LABORATORIUM (PERBAIKAN TOTAL BIAYA PERUJUK) ---
        if(in_array('laborat', $kategori)){
            $lab_query = "
            /* Pemeriksa */
            SELECT 'Laborat' as sumber, r.no_rawat, r.no_rkm_medis, p.nm_pasien, 
            CONCAT(t.tgl_periksa, ' ', t.jam) as tgl_transaksi,
            jpl.nm_perawatan as tindakan, d.biaya_item as total_biaya, d.bagian_dokter as jm_dokter
            FROM periksa_lab t 
            JOIN detail_periksa_lab d ON t.no_rawat=d.no_rawat AND t.kd_jenis_prw=d.kd_jenis_prw AND t.tgl_periksa=d.tgl_periksa AND t.jam=d.jam
            JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_lab jpl ON t.kd_jenis_prw=jpl.kd_jenis_prw
            WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.kd_dokter = :dokter

            UNION ALL
            /* Perujuk (Sekarang munculkan Biaya Asli) */
            SELECT 'Laborat (Perujuk)' as sumber, r.no_rawat, r.no_rkm_medis, p.nm_pasien, 
            CONCAT(t.tgl_periksa, ' ', t.jam) as tgl_transaksi,
            CONCAT(jpl.nm_perawatan, ' (Perujuk)') as tindakan, 
            t.biaya as total_biaya, /* Ambil Biaya Asli */
            t.tarif_perujuk as jm_dokter
            FROM periksa_lab t JOIN reg_periksa r ON t.no_rawat=r.no_rawat JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis JOIN jns_perawatan_lab jpl ON t.kd_jenis_prw=jpl.kd_jenis_prw
            WHERE CONCAT(t.tgl_periksa, ' ', t.jam) BETWEEN :tgl1 AND :tgl2 AND t.dokter_perujuk = :dokter
            ";
            $sql_parts[] = $lab_query;
        }

        if(!empty($sql_parts)){
            $final_sql = implode(" UNION ALL ", $sql_parts) . " ORDER BY tgl_transaksi ASC";
            $stmt = $pdo->prepare($final_sql);
            $stmt->execute($params);
            $hasil = $stmt->fetchAll();
        }

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

require_once '../../layout/header.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap4.min.css">

<?php require_once '../../layout/sidebar.php'; ?>

<div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6"><h1>Audit Jasa Medis (Final Fix)</h1></div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="card card-primary collapsed-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter"></i> Filter Pencarian</h3>
                <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button></div>
            </div>
            <div class="card-body" style="display: none;">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Periode Tindakan</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" name="tgl_awal" value="<?= $tgl_awal ?>" required>
                                    <div class="input-group-append"><span class="input-group-text">s/d</span></div>
                                    <input type="date" class="form-control" name="tgl_akhir" value="<?= $tgl_akhir ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Dokter</label>
                                <?php if($can_view_all): ?>
                                    <select class="form-control select2" name="kd_dokter">
                                        <?php foreach($dokters as $dr): ?>
                                            <option value="<?= $dr['kd_dokter'] ?>" <?= ($kd_dokter == $dr['kd_dokter']) ? 'selected' : '' ?>>
                                                <?= $dr['nm_dokter'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="text" class="form-control" value="<?= $_SESSION['nama'] ?>" readonly>
                                    <input type="hidden" name="kd_dokter" value="<?= $user_dokter ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>&nbsp;</label>
                            <button type="submit" name="proses" value="1" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> Tampilkan
                            </button>
                        </div>
                    </div>
                    <hr>
                    <div class="form-group">
                        <label>Sumber Data:</label>
                        <div class="row">
                            <?php 
                            $labels = ['ralan_dr' => 'Ralan (Dr)', 'ralan_pr' => 'Ralan (Dr+Pr)', 'ranap_dr' => 'Ranap (Dr)', 'ranap_pr' => 'Ranap (Dr+Pr)', 'operasi' => 'Operasi', 'radiologi' => 'Radiologi', 'laborat' => 'Laborat'];
                            foreach($labels as $val => $txt): 
                            ?>
                            <div class="col-md-3">
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" type="checkbox" id="c_<?= $val ?>" name="kategori[]" value="<?= $val ?>" <?= in_array($val, $kategori) ? 'checked' : '' ?>>
                                    <label for="c_<?= $val ?>" class="custom-control-label"><?= $txt ?></label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if(isset($_GET['proses'])): ?>
        
        <?php
            // Kita susun parameter GET yang sama persis untuk dikirim ke export.php
            $params_export = [
                'tgl_awal'  => $tgl_awal,
                'tgl_akhir' => $tgl_akhir,
                'kd_dokter' => $kd_dokter,
                'kategori'  => $kategori // Ini array, http_build_query bisa menanganinya
            ];
            // Buat URL lengkap
            $url_export = "export.php?" . http_build_query($params_export);
        ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Hasil Data</h3>
                <div class="card-tools">
                    <a href="<?= $url_export ?>" target="_blank" class="btn btn-tool bg-success">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <button type="button" class="btn btn-tool" data-card-widget="maximize"><i class="fas fa-expand"></i></button>
                </div>
            </div>
            <div class="card-body">
                <table id="tblJasmed" class="table table-bordered table-striped table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>No. RM</th>
                            <th>Pasien</th>
                            <th>No. Rawat</th>
                            <th>Tindakan</th>
                            <th>Sumber</th>
                            <th class="text-right">Biaya Pasien</th>
                            <th class="text-right">JM Dokter</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sum_biaya = 0; $sum_jm = 0;
                        // Array untuk mencegah Double Counting Biaya Pasien (Pembengkakan Sum)
                        $processed_transactions = []; 

                        foreach($hasil as $row): 
                            // Buat Unique Key per transaksi (No Rawat + Waktu + Tindakan (tanpa suffix peran))
                            // Kita bersihkan suffix seperti " (Perujuk)" atau " (Operator 1)" untuk identifikasi tindakan yg sama
                            $clean_tindakan = preg_replace('/\s\((Perujuk|Operator \d|Dokter .*)\)$/', '', $row['tindakan']);
                            $unique_key = $row['no_rawat'] . '_' . $row['tgl_transaksi'] . '_' . $clean_tindakan;

                            $is_duplicate_cost = false;
                            if(in_array($unique_key, $processed_transactions)){
                                $is_duplicate_cost = true; // Transaksi ini sudah dihitung biayanya di baris lain (misal di baris Pemeriksa)
                            } else {
                                $sum_biaya += $row['total_biaya']; // Tambahkan ke Grand Total
                                $processed_transactions[] = $unique_key; // Tandai sudah dihitung
                            }

                            $sum_jm += $row['jm_dokter']; // JM Dokter SELALU dijumlahkan (karena hak individu)
                            $class_row = ($row['jm_dokter'] == 0 && $row['total_biaya'] > 0) ? 'text-danger' : '';
                        ?>
                        <tr class="<?= $class_row ?>">
                            <td><?= $row['tgl_transaksi'] ?></td>
                            <td><?= $row['no_rkm_medis'] ?></td>
                            <td><?= $row['nm_pasien'] ?></td>
                            <td><small><?= $row['no_rawat'] ?></small></td>
                            <td><?= $row['tindakan'] ?></td>
                            <td><?= $row['sumber'] ?></td>
                            <td class="text-right">
                                <?= number_format($row['total_biaya'],0,',','.') ?>
                                <?php if($is_duplicate_cost && $row['total_biaya'] > 0): ?>
                                    <br><small class="text-muted text-nowrap">(*included)</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-right font-weight-bold"><?= number_format($row['jm_dokter'],0,',','.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <th colspan="6" class="text-right">TOTAL BERSIH (Tanpa Duplikasi Biaya):</th>
                            <th class="text-right"><?= number_format($sum_biaya,0,',','.') ?></th>
                            <th class="text-right"><?= number_format($sum_jm,0,',','.') ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endif; ?>

      </div>
    </section>
</div>

<?php require_once '../../layout/footer.php'; ?>

<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script>
  $(function () {
    // Aktifkan DataTables dengan Fitur Responsive
    $("#tblJasmed").DataTable({
      "responsive": true, 
      "lengthChange": false, 
      "autoWidth": false,
      "pageLength": 25,
      "order": [[ 0, "asc" ]],
      "columnDefs": [
          { "priority": 1, "targets": 0 }, // Kolom Waktu selalu muncul
          { "priority": 2, "targets": -1 }, // Kolom JM Dokter selalu muncul
          { "priority": 3, "targets": -2 }  // Kolom Biaya Pasien prioritas 3
      ],
      "language": {
          "search": "Cari:",
          "zeroRecords": "Tidak ada data yang cocok",
          "info": "Hal _PAGE_ dari _PAGES_",
          "infoEmpty": "Kosong",
          "paginate": { "previous": "<", "next": ">" }
      }
    });
  });
</script>