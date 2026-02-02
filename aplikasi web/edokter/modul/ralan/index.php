<?php
// modul/ralan/index.php
session_start();
require_once '../../config/database.php';
require_once '../../config/fungsi.php';

if (!isset($_SESSION['login_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$title = 'Pasien Rawat Jalan';
$menu  = 'ralan';
$kd_dokter = $_SESSION['kd_dokter'];

// FILTER
$tgl_cari = $_GET['tgl'] ?? date('Y-m-d');
$status_periksa = $_GET['stts'] ?? 'Belum'; // Default Belum

// QUERY DATA PASIEN
try {
    $sql = "
    SELECT 
        r.no_rawat, 
        r.no_rkm_medis, 
        r.tgl_registrasi, 
        r.jam_reg, 
        r.stts, 
        r.status_lanjut,
        r.no_reg,          /* Req 4: No Urut */
        r.p_jawab,         /* Req 5: Penanggung Jawab */
        r.hubunganpj,      /* Req 5: Hubungan */
        p.nm_pasien, 
        p.jk, 
        pol.nm_poli,
        pj.png_jawab,      /* Req 3: Penjamin */
        (SELECT COUNT(*) FROM pemeriksaan_ralan pr WHERE pr.no_rawat = r.no_rawat) as ttv_count
    FROM reg_periksa r
    JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
    JOIN poliklinik pol ON r.kd_poli = pol.kd_poli
    JOIN penjab pj ON r.kd_pj = pj.kd_pj /* Join ke Penjab */
    WHERE r.kd_dokter = :dokter 
    AND r.tgl_registrasi = :tgl
    ";

    $params = [
        'dokter' => $kd_dokter,
        'tgl'    => $tgl_cari
    ];

    // Req 2: Opsi Tampilkan Semua
    if ($status_periksa != 'Semua') {
        $sql .= " AND r.stts = :stts";
        $params['stts'] = $status_periksa;
    }

    // Req 4: Default Descending by No Reg
    $sql .= " ORDER BY r.no_reg DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pasien = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

require_once '../../layout/header.php';
require_once '../../layout/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6"><h1>Rawat Jalan</h1></div>
          <div class="col-sm-6 text-right">
              <span class="badge badge-info">Dokter: <?= $_SESSION['nama'] ?></span>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        
        <div class="card card-outline card-primary">
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-4 col-6">
                            <div class="form-group mb-0">
                                <label>Tanggal</label>
                                <input type="date" class="form-control" name="tgl" value="<?= $tgl_cari ?>" onchange="this.form.submit()">
                            </div>
                        </div>
                        <div class="col-md-4 col-6">
                            <div class="form-group mb-0">
                                <label>Status Periksa</label>
                                <select class="form-control" name="stts" onchange="this.form.submit()">
                                    <option value="Belum" <?= $status_periksa=='Belum'?'selected':'' ?>>Belum Periksa</option>
                                    <option value="Sudah" <?= $status_periksa=='Sudah'?'selected':'' ?>>Sudah Periksa</option>
                                    <option value="Semua" <?= $status_periksa=='Semua'?'selected':'' ?>>Tampilkan Semua</option>
                                    <option value="Batal" <?= $status_periksa=='Batal'?'selected':'' ?>>Batal</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 col-12 text-right align-self-end mt-2 mt-md-0">
                            <a href="index.php" class="btn btn-default"><i class="fas fa-sync"></i> Refresh</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">
                    Antrean Pasien (<?= count($pasien) ?>)
                </h3>
            </div>
            <div class="card-body">
                <table id="tblPasien" class="table table-striped table-hover table-valign-middle table-sm">
                    <thead>
                        <tr>
                            <th>No. Reg</th>
                            <th>Jam Registrasi</th>
                            <th>Pasien</th>
                            <th>Penjamin</th>
                            <th>P.Jawab</th>
                            <th>Status/Data</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pasien as $row): ?>
                        <tr>
                            <td class="text-center font-weight-bold" style="font-size: 1.2em;">
                                <?= $row['no_reg'] ?>
                            </td>
                            <td>
                                <?= $row['jam_reg'] ?><br>
                                <span class="badge badge-light"><?= $row['nm_poli'] ?></span>
                            </td>
                            <td>
                                <b><?= $row['nm_pasien'] ?></b> <br>
                                <small>RM: <?= $row['no_rkm_medis'] ?> (<?= $row['jk'] ?>)</small><br>
                                <small class="text-muted"><?= $row['no_rawat'] ?></small>
                            </td>
                            <td>
                                <span class="badge badge-info"><?= $row['png_jawab'] ?></span>
                            </td>
                            <td>
                                <?= $row['p_jawab'] ?><br>
                                <small class="text-muted">(<?= $row['hubunganpj'] ?>)</small>
                            </td>
                            <td>
                                <?php 
                                    // Badge Status Periksa
                                    $cls_stts = 'secondary';
                                    if($row['stts']=='Sudah') $cls_stts = 'success';
                                    if($row['stts']=='Batal') $cls_stts = 'danger';
                                    echo "<span class='badge badge-$cls_stts'>{$row['stts']}</span><br>";
                                ?>

                                <?php if($row['ttv_count'] > 0): ?>
                                    <span class="badge badge-success" title="Ada data TTV/SOAP"><i class="fas fa-check"></i> CPPT Ada</span>
                                <?php else: ?>
                                    <span class="badge badge-warning" title="Belum ada inputan"><i class="fas fa-exclamation"></i> CPPT 0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <button class="btn btn-primary btn-sm btn-periksa" 
                                        data-rawat="<?= $row['no_rawat'] ?>"
                                        data-rm="<?= $row['no_rkm_medis'] ?>"
                                        data-nama="<?= $row['nm_pasien'] ?>">
                                    <i class="fas fa-stethoscope"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

      </div>
    </section>
</div>

<div class="modal fade" id="modalSOAP" data-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">Input CPPT / SOAP</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="modalContent">
                <div class="text-center py-5"><i class="fas fa-spinner fa-spin"></i> Memuat Form...</div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../layout/footer.php'; ?>

<script>
$(function() {
    $('#tblPasien').DataTable({
        "paging": true,
        "lengthChange": false,
        "searching": true,
        "ordering": false, // Kita matikan sorting JS agar ikut sorting PHP (No Reg Desc)
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "pageLength": 10,
        "language": {
            "search": "Cari Pasien:",
            "emptyTable": "Tidak ada data",
            "info": "_START_ - _END_ dari _TOTAL_"
        }
    });

    // Event Klik Tombol Periksa
    $('.btn-periksa').on('click', function() {
        var no_rawat = $(this).data('rawat');
        var nm_pasien = $(this).data('nama');
        
        // Ubah judul modal
        $('#modalSOAP .modal-title').html('<i class="fas fa-user-md"></i> Periksa: ' + nm_pasien);
        
        // Tampilkan Modal
        $('#modalSOAP').modal('show');

        // Loading indicator
        $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x"></i><br>Mengambil Data...</div>');

        // Load Form via AJAX
        $('#modalContent').load('form.php?no_rawat=' + no_rawat); 
    });
});

// --- LOGIKA POPUP KONFIRMASI STATUS SUDAH ---
    // Cek apakah URL memiliki parameter offer_update=1
    const urlParams = new URLSearchParams(window.location.search);
    const offerUpdate = urlParams.get('offer_update');
    const noRawat = urlParams.get('no_rawat');

    if (offerUpdate === '1' && noRawat) {
        // Gunakan setTimeOut agar render halaman selesai dulu baru muncul alert
        setTimeout(function() {
            var conf = confirm("CPPT Berhasil disimpan.\n\nApakah Anda ingin sekalian mengubah status pasien menjadi 'SUDAH DIPERIKSA'?");
            
            if (conf) {
                // Jika YES, panggil proses update
                window.location.href = 'proses.php?act=update_status_periksa&no_rawat=' + noRawat;
            } else {
                // Jika NO, bersihkan URL agar tidak muncul lagi saat refresh
                window.history.replaceState(null, null, window.location.pathname);
            }
        }, 500);
    }
</script>