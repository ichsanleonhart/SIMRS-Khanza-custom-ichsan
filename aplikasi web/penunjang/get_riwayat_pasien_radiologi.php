<?php
/*
 * ===================================================================================
 * FILE AJAX: GET RIWAYAT PASIEN RADIOLOGI (FINAL)
 * ===================================================================================
 * Halaman ini dipanggil via AJAX dari radiologi.php untuk mengambil riwayat
 * radiologi sebelumnya dan riwayat CPPT/SOAPI.
 *
 * Logika didasarkan pada get_riwayat_pasien.php dan RMRiwayatPerawatan.java
 *
 * Keamanan:
 * - Memerlukan login (require_login())
 * - Memerlukan metode POST
 * - Memerlukan validasi CSRF token
 * - Melakukan sanitasi input
 *
 * Fitur:
 * - Menggunakan konstanta WEBAPPS_URL dari config.php untuk URL gambar.
 * - Mengintegrasikan library viewer.js untuk galeri gambar di riwayat.
 *
 * !!! PENTING !!!
 * Agar viewer ini berfungsi, Anda HARUS menambahkan file CSS & JS
 * library viewer.js ke file *radiologi.php* (file yang memanggil modal ini).
 *
 * Tambahkan di <head> file radiologi.php:
 * <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.css">
 *
 * Tambahkan sebelum </body> di file radiologi.php:
 * <script src="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.js"></script>
 */
require_once 'config.php';
require_login(); // Memastikan pengguna sudah login

// 1. Validasi Keamanan Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    die('Metode tidak diizinkan.');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403); // Forbidden
    die('Aksi tidak diizinkan: Invalid CSRF Token.');
}

// 2. Sanitasi Input
$no_rkm_medis = filter_input(INPUT_POST, 'no_rkm_medis', FILTER_SANITIZE_STRING);
$no_rawat_current = filter_input(INPUT_POST, 'no_rawat_current', FILTER_SANITIZE_STRING);

if (empty($no_rkm_medis)) {
    die('<div class="alert alert-warning">No. Rekam Medis tidak valid.</div>');
}

$pdo = connect_db();
?>

<!-- 3. Tampilan Modal (Tabs) -->
<ul class="nav nav-tabs" id="riwayatRadTab" role="tablist">
  <li class="nav-item">
    <a class="nav-link active" id="rad-tab" data-toggle="tab" href="#riwayatRadiologi" role="tab" aria-controls="riwayatRadiologi" aria-selected="true">
      <i class="fas fa-x-ray"></i> Riwayat Radiologi
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" id="cppt-rad-tab" data-toggle="tab" href="#riwayatCPPTRad" role="tab" aria-controls="riwayatCPPTRad" aria-selected="false">
      <i class="fas fa-notes-medical"></i> Riwayat CPPT/SOAPI
    </a>
  </li>
</ul>
<div class="tab-content" id="riwayatRadTabContent">

  <!-- ============================================= -->
  <!-- TAB 1: RIWAYAT RADIOLOGI (Tampilan Bersusun)  -->
  <!-- ============================================= -->
  <div class="tab-pane fade show active" id="riwayatRadiologi" role="tabpanel" aria-labelledby="rad-tab">

    <div class="accordion mt-3" id="accordionRadiologi">
    <?php
    try {
        // Query 1: (Outer loop) - Ambil semua header pemeriksaan radiologi pasien
        // Dikelompokkan per timestamp pemeriksaan
        $sql_rad = "SELECT
                        pr.no_rawat,
                        pr.tgl_periksa,
                        pr.jam,
                        d_pj.nm_dokter AS dokter_pj,
                        d_perujuk.nm_dokter AS dokter_perujuk
                    FROM periksa_radiologi pr
                    JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
                    LEFT JOIN dokter d_pj ON pr.kd_dokter = d_pj.kd_dokter
                    LEFT JOIN dokter d_perujuk ON pr.dokter_perujuk = d_perujuk.kd_dokter
                    WHERE rp.no_rkm_medis = ?
                    GROUP BY pr.no_rawat, pr.tgl_periksa, pr.jam
                    ORDER BY pr.tgl_periksa DESC, pr.jam DESC
                    LIMIT 30"; // Batasi 30 kunjungan radiologi terakhir

        $stmt_rad = $pdo->prepare($sql_rad);
        $stmt_rad->execute([$no_rkm_medis]);

        if ($stmt_rad->rowCount() == 0) {
            echo '<div class="alert alert-info mt-2">Tidak ditemukan riwayat pemeriksaan radiologi.</div>';
        } else {
            $i_rad = 1;
            while ($rad = $stmt_rad->fetch(PDO::FETCH_ASSOC)) {
                $collapse_id = 'collapseRad' . $i_rad;
                $header_id = 'headerRad' . $i_rad;

                $is_current = ($rad['no_rawat'] == $no_rawat_current);
                $card_class = $is_current ? 'card-success' : 'card-primary';
                $badge_current = $is_current ? ' <span class="badge badge-success ml-2">Pemeriksaan Saat Ini</span>' : '';
            ?>
                <div class="card <?php echo $card_class; ?> card-outline mb-1">
                  <div class="card-header" id="<?php echo $header_id; ?>">
                    <h5 class="mb-0">
                      <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#<?php echo $collapse_id; ?>" aria-expanded="<?php echo $is_current ? 'true' : 'false'; ?>" aria-controls="<?php echo $collapse_id; ?>">
                        <b><?php echo $i_rad; ?>. Tgl:</b> <?php echo e($rad['tgl_periksa']); ?> <?php echo e($rad['jam']); ?> | <b>No:</b> <?php echo e($rad['no_rawat']); ?> | <b>Dokter PJ:</b> <?php echo e($rad['dokter_pj']); ?>
                        <?php echo $badge_current; ?>
                      </button>
                    </h5>
                  </div>
                  <div id="<?php echo $collapse_id; ?>" class="collapse <?php echo $is_current ? 'show' : ''; ?>" aria-labelledby="<?php echo $header_id; ?>" data-parent="#accordionRadiologi">
                    <div class="card-body p-2">

                      <?php
                      // Query 1.5: Ambil info tambahan & diagnosa klinis dari permintaan_radiologi
                      $sql_permintaan = "SELECT informasi_tambahan, diagnosa_klinis
                                         FROM permintaan_radiologi
                                         WHERE no_rawat = ? AND tgl_hasil = ? AND jam_hasil = ?
                                         LIMIT 1";
                      $stmt_permintaan = $pdo->prepare($sql_permintaan);
                      $stmt_permintaan->execute([$rad['no_rawat'], $rad['tgl_periksa'], $rad['jam']]);
                      $info_permintaan = $stmt_permintaan->fetch(PDO::FETCH_ASSOC);

                      if ($info_permintaan && (!empty($info_permintaan['diagnosa_klinis']) || !empty($info_permintaan['informasi_tambahan']))) {
                          echo '<div class="alert alert-info py-2 mb-2" style="font-size: 0.9em;">';
                          if (!empty($info_permintaan['diagnosa_klinis'])) {
                              echo '<strong>Diagnosa Klinis:</strong> ' . nl2br(e($info_permintaan['diagnosa_klinis'])) . '<br>';
                          }
                          if (!empty($info_permintaan['informasi_tambahan'])) {
                              echo '<strong>Info Tambahan:</strong> ' . nl2br(e($info_permintaan['informasi_tambahan']));
                          }
                          echo '</div>';
                      }
                      ?>

                      <h6 class="font-weight-bold">Tindakan Radiologi</h6>
                      <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-2">
                          <thead class="bg-light">
                            <tr>
                              <th>Nama Pemeriksaan</th>
                              <th>Petugas</th>
                              <th>Dokter PJ</th>
                              <th>Detail Penyinaran</th>
                            </tr>
                          </thead>
                          <tbody>
                          <?php
                          // Query 2: Ambil detail tindakan
                          $sql_tindakan = "SELECT
                                             jpr.nm_perawatan, p.nama AS nama_petugas, d.nm_dokter AS dokter_pj,
                                             pr.proyeksi, pr.kV, pr.mAS, pr.FFD, pr.BSF, pr.inak, pr.jml_penyinaran, pr.dosis
                                           FROM periksa_radiologi pr
                                           JOIN jns_perawatan_radiologi jpr ON pr.kd_jenis_prw = jpr.kd_jenis_prw
                                           JOIN petugas p ON pr.nip = p.nip
                                           JOIN dokter d ON pr.kd_dokter = d.kd_dokter
                                           WHERE pr.no_rawat = ? AND pr.tgl_periksa = ? AND pr.jam = ?";
                          $stmt_tindakan = $pdo->prepare($sql_tindakan);
                          $stmt_tindakan->execute([$rad['no_rawat'], $rad['tgl_periksa'], $rad['jam']]);

                          while ($tindakan = $stmt_tindakan->fetch(PDO::FETCH_ASSOC)) {
                              $proyeksi_parts = [];
                              if (!empty($tindakan['proyeksi'])) $proyeksi_parts[] = 'Proyeksi: ' . e($tindakan['proyeksi']);
                              if (!empty($tindakan['kV'])) $proyeksi_parts[] = 'kV: ' . e($tindakan['kV']);
                              if (!empty($tindakan['mAS'])) $proyeksi_parts[] = 'mAS: ' . e($tindakan['mAS']);
                              if (!empty($tindakan['FFD'])) $proyeksi_parts[] = 'FFD: ' . e($tindakan['FFD']);
                              if (!empty($tindakan['BSF'])) $proyeksi_parts[] = 'BSF: ' . e($tindakan['BSF']);
                              if (!empty($tindakan['inak'])) $proyeksi_parts[] = 'Inak: ' . e($tindakan['inak']);
                              if (!empty($tindakan['jml_penyinaran'])) $proyeksi_parts[] = 'Jml: ' . e($tindakan['jml_penyinaran']);
                              if (!empty($tindakan['dosis'])) $proyeksi_parts[] = 'Dosis: ' . e($tindakan['dosis']);
                              $proyeksi_string = implode(', ', $proyeksi_parts);

                              echo '<tr>';
                              echo '<td>' . e($tindakan['nm_perawatan']) . '</td>';
                              echo '<td>' . e($tindakan['nama_petugas']) . '</td>';
                              echo '<td>' . e($tindakan['dokter_pj']) . '</td>';
                              echo '<td style="font-size: 0.9em;">' . e($proyeksi_string) . '</td>';
                              echo '</tr>';
                          }
                          ?>
                          </tbody>
                        </table>
                      </div>

                      <h6 class="font-weight-bold mt-3">Hasil Bacaan (Expertise)</h6>
                      <?php
                      // Query 3: Ambil Hasil Bacaan
                      $stmt_hasil = $pdo->prepare("SELECT hasil FROM hasil_radiologi WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?");
                      $stmt_hasil->execute([$rad['no_rawat'], $rad['tgl_periksa'], $rad['jam']]);
                      $hasil = $stmt_hasil->fetch(PDO::FETCH_ASSOC);

                      if ($hasil && !empty($hasil['hasil'])) {
                          echo '<div class="card card-body bg-light p-3" style="font-size: 0.9em;">' . nl2br(e($hasil['hasil'])) . '</div>';
                      } else {
                          echo '<div class="alert alert-light">Belum ada hasil expertise untuk pemeriksaan ini.</div>';
                      }

                      // Query 4: Ambil Gambar
                      $stmt_gambar = $pdo->prepare("SELECT lokasi_gambar FROM gambar_radiologi WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?");
                      $stmt_gambar->execute([$rad['no_rawat'], $rad['tgl_periksa'], $rad['jam']]);
                      $gambars = $stmt_gambar->fetchAll(PDO::FETCH_ASSOC);

                      if ($gambars) {
                          echo '<h6 class="font-weight-bold mt-3">Gambar Radiologi (Klik untuk melihat)</h6>';

                          // Beri ID unik dan class untuk inisialisasi viewer.js
                          $gallery_id = 'gallery-' . e($collapse_id);
                          echo '<div id="' . $gallery_id . '" class="radiology-gallery-riwayat" style="display: flex; flex-wrap: wrap; gap: 10px; background: #f0f0f0; border-radius: 5px; padding: 10px;">';

                          foreach ($gambars as $gambar) {
                              $imageUrl = e(WEBAPPS_URL . '/radiologi/' . $gambar['lokasi_gambar']);
                              $imageAlt = 'Gambar Radiologi - ' . e($rad['tgl_periksa']) . ' ' . e($rad['jam']);

                              // Hapus <a> tag, sisakan <img> dengan style untuk cursor
                              echo '<div style="flex: 1 1 150px; min-width: 150px; background: #000; padding: 5px; border-radius: 4px;">';
                              echo '<img src="' . $imageUrl . '" alt="' . $imageAlt . '" style="width: 100%; height: auto; display: block; cursor: pointer;"
                                     onerror="this.src=\'https://placehold.co/150x150/000000/FFFFFF?text=Error\'; this.style.cursor=\'default\';">';
                              echo '</div>';
                          }
                          echo '</div>';
                      }
                      ?>
                    </div>
                  </div>
                </div>
            <?php
                $i_rad++;
            } // end while rad
        }
    } catch (Exception $e) {
        echo '<div class="alert alert-danger mt-3">Gagal mengambil riwayat radiologi: ' . e($e->getMessage()) . '</div>';
    }
    ?>
    </div> <!-- ./accordion -->
  </div>

  <!-- ============================================= -->
  <!-- TAB 2: RIWAYAT CPPT / SOAPI (Tampilan Bersusun) -->
  <!-- ============================================= -->
  <div class="tab-pane fade" id="riwayatCPPTRad" role="tabpanel" aria-labelledby="cppt-rad-tab">
    <?php
    // Logika ini sama persis dengan di get_riwayat_pasien.php (modul lab)
    try {
        $sql_kunjungan = "SELECT no_rawat, tgl_registrasi, status_lanjut
                          FROM reg_periksa
                          WHERE no_rkm_medis = ? AND stts <> 'Batal'
                          ORDER BY tgl_registrasi DESC, jam_reg DESC
                          LIMIT 30";

        $stmt_kunjungan = $pdo->prepare($sql_kunjungan);
        $stmt_kunjungan->execute([$no_rkm_medis]);

        if ($stmt_kunjungan->rowCount() == 0) {
            echo '<div class="alert alert-info mt-3">Tidak ditemukan riwayat kunjungan (CPPT/SOAPI) untuk pasien ini.</div>';
        } else {
            echo '<div class="accordion" id="accordionSOAPIRad">';
            $i_soapi = 1;
            while ($kunjungan = $stmt_kunjungan->fetch(PDO::FETCH_ASSOC)) {
                $no_rawat_soapi = $kunjungan['no_rawat'];
                $collapse_id_soapi = 'collapseSOAPIRad' . $i_soapi;
                $header_id_soapi = 'headerSOAPIRad' . $i_soapi;

                $is_current_soapi = ($no_rawat_soapi == $no_rawat_current);
                $card_class_soapi = $is_current_soapi ? 'card-success' : 'card-info';
                $badge_current_soapi = $is_current_soapi ? ' <span class="badge badge-success ml-2">Kunjungan Saat Ini</span>' : '';
            ?>
                <div class="card <?php echo $card_class_soapi; ?> card-outline mb-1">
                  <div class="card-header" id="<?php echo $header_id_soapi; ?>">
                    <h5 class="mb-0">
                      <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#<?php echo $collapse_id_soapi; ?>" aria-expanded="<?php echo $is_current_soapi ? 'true' : 'false'; ?>" aria-controls="<?php echo $collapse_id_soapi; ?>">
                        <b><?php echo $i_soapi; ?>. Tgl Registrasi:</b> <?php echo e($kunjungan['tgl_registrasi']); ?> | <b>No:</b> <?php echo e($no_rawat_soapi); ?> | <b>Status:</b> <?php echo e($kunjungan['status_lanjut']); ?>
                         <?php echo $badge_current_soapi; ?>
                      </button>
                    </h5>
                  </div>
                  <div id="<?php echo $collapse_id_soapi; ?>" class="collapse <?php echo $is_current_soapi ? 'show' : ''; ?>" aria-labelledby="<?php echo $header_id_soapi; ?>" data-parent="#accordionSOAPIRad">
                    <div class="card-body p-2">
                      <?php
                      $ada_data_soapi = false;

                      // Query SOAPI Ralan
                      $sql_ralan = "SELECT
                                      pr.tgl_perawatan, pr.jam_rawat, pr.keluhan, pr.pemeriksaan, pr.penilaian, pr.rtl, pr.instruksi, pr.evaluasi,
                                      pr.suhu_tubuh, pr.tensi, pr.nadi, pr.respirasi, pr.tinggi, pr.berat, pr.gcs, pr.spo2, pr.kesadaran, pr.alergi,
                                      IFNULL(d.nm_dokter, pg.nama) AS nama_petugas, pr.nip
                                    FROM pemeriksaan_ralan pr
                                    LEFT JOIN pegawai pg ON pr.nip = pg.nik
                                    LEFT JOIN dokter d ON pr.nip = d.kd_dokter
                                    WHERE pr.no_rawat = ?
                                    ORDER BY pr.tgl_perawatan, pr.jam_rawat";
                      $stmt_ralan = $pdo->prepare($sql_ralan);
                      $stmt_ralan->execute([$no_rawat_soapi]);

                      if ($stmt_ralan->rowCount() > 0) {
                          $ada_data_soapi = true;
                          echo '<h5>Pemeriksaan Rawat Jalan</h5>';
                          echo '<div class="table-responsive"><table class="table table-sm table-bordered table-striped" style="font-size: 0.9em;">
                                  <thead class="bg-light">
                                    <tr>
                                      <th>Tanggal</th>
                                      <th>Petugas</th>
                                      <th>Subjektif (S)</th>
                                      <th>Objektif (O)</th>
                                      <th>Assessment (A)</th>
                                      <th>Plan (P)</th>
                                      <th>Instruksi (I)</th>
                                      <th>Evaluasi (E)</th>
                                    </tr>
                                  </thead>
                                  <tbody>';
                          while ($ralan = $stmt_ralan->fetch(PDO::FETCH_ASSOC)) {
                              $objek = '<div>' . nl2br(e($ralan['pemeriksaan'])) . '</div>';
                              $ttv = [];
                              if (!empty($ralan['alergi'])) $ttv[] = 'Alergi: ' . e($ralan['alergi']);
                              if (!empty($ralan['suhu_tubuh']) && $ralan['suhu_tubuh'] != "0") $ttv[] = 'Suhu: ' . e($ralan['suhu_tubuh']) . ' °C';
                              if (!empty($ralan['tensi']) && $ralan['tensi'] != "0/0" && !empty($ralan['tensi'])) $ttv[] = 'Tensi: ' . e($ralan['tensi']) . ' mmHg';
                              if (!empty($ralan['nadi']) && $ralan['nadi'] != "0") $ttv[] = 'Nadi: ' . e($ralan['nadi']) . ' x/mnt';
                              if (!empty($ralan['respirasi']) && $ralan['respirasi'] != "0") $ttv[] = 'RR: ' . e($ralan['respirasi']) . ' x/mnt';
                              if (!empty($ralan['tinggi']) && $ralan['tinggi'] != "0") $ttv[] = 'TB: ' . e($ralan['tinggi']) . ' cm';
                              if (!empty($ralan['berat']) && $ralan['berat'] != "0") $ttv[] = 'BB: ' . e($ralan['berat']) . ' Kg';
                              if (!empty($ralan['spo2']) && $ralan['spo2'] != "0") $ttv[] = 'SpO2: ' . e($ralan['spo2']) . ' %';
                              if (!empty($ralan['gcs']) && $ralan['gcs'] != "0") $ttv[] = 'GCS: ' . e($ralan['gcs']);
                              if (!empty($ralan['kesadaran'])) $ttv[] = 'Kesadaran: ' . e($ralan['kesadaran']);

                              if (!empty($ttv)) {
                                  $objek .= '<div class="mt-2 pt-2 border-top text-muted" style="font-size: 0.9em;">' . implode(' | ', $ttv) . '</div>';
                              }

                              echo '<tr>';
                              echo '<td>' . e($ralan['tgl_perawatan']) . '<br>' . e($ralan['jam_rawat']) . '</td>';
                              echo '<td>' . e($ralan['nama_petugas']) . '<br>(' . e($ralan['nip']) . ')</td>';
                              echo '<td>' . nl2br(e($ralan['keluhan'])) . '</td>';
                              echo '<td>' . $objek . '</td>';
                              echo '<td>' . nl2br(e($ralan['penilaian'])) . '</td>';
                              echo '<td>' . nl2br(e($ralan['rtl'])) . '</td>';
                              echo '<td>' . nl2br(e($ralan['instruksi'])) . '</td>';
                              echo '<td>' . nl2br(e($ralan['evaluasi'])) . '</td>';
                              echo '</tr>';
                          }
                          echo '</tbody></table></div>';
                      }

                      // Query SOAPI Ranap
                      $sql_ranap = "SELECT
                                      pr.tgl_perawatan, pr.jam_rawat, pr.keluhan, pr.pemeriksaan, pr.penilaian, pr.rtl, pr.instruksi, pr.evaluasi,
                                      pr.suhu_tubuh, pr.tensi, pr.nadi, pr.respirasi, pr.tinggi, pr.berat, pr.gcs, pr.spo2, pr.kesadaran, pr.alergi,
                                      IFNULL(d.nm_dokter, pg.nama) AS nama_petugas, pr.nip
                                    FROM pemeriksaan_ranap pr
                                    LEFT JOIN pegawai pg ON pr.nip = pg.nik
                                    LEFT JOIN dokter d ON pr.nip = d.kd_dokter
                                    WHERE pr.no_rawat = ?
                                    ORDER BY pr.tgl_perawatan, pr.jam_rawat";
                      $stmt_ranap = $pdo->prepare($sql_ranap);
                      $stmt_ranap->execute([$no_rawat_soapi]);

                      if ($stmt_ranap->rowCount() > 0) {
                          $ada_data_soapi = true;
                          echo '<h5 class="mt-3">Pemeriksaan Rawat Inap</h5>';
                          echo '<div class="table-responsive"><table class="table table-sm table-bordered table-striped" style="font-size: 0.9em;">
                                  <thead class="bg-light">
                                    <tr>
                                      <th>Tanggal</th>
                                      <th>Petugas</th>
                                      <th>Subjektif (S)</th>
                                      <th>Objektif (O)</th>
                                      <th>Assessment (A)</th>
                                      <th>Plan (P)</th>
                                      <th>Instruksi (I)</th>
                                      <th>Evaluasi (E)</th>
                                    </tr>
                                  </thead>
                                  <tbody>';
                          while ($ranap = $stmt_ranap->fetch(PDO::FETCH_ASSOC)) {
                              $objek_ranap = '<div>' . nl2br(e($ranap['pemeriksaan'])) . '</div>';
                              $ttv_ranap = [];
                              if (!empty($ranap['alergi'])) $ttv_ranap[] = 'Alergi: ' . e($ranap['alergi']);
                              if (!empty($ranap['suhu_tubuh']) && $ranap['suhu_tubuh'] != "0") $ttv_ranap[] = 'Suhu: ' . e($ranap['suhu_tubuh']) . ' °C';
                              if (!empty($ranap['tensi']) && $ranap['tensi'] != "0/0" && !empty($ranap['tensi'])) $ttv_ranap[] = 'Tensi: ' . e($ranap['tensi']) . ' mmHg';
                              if (!empty($ranap['nadi']) && $ranap['nadi'] != "0") $ttv_ranap[] = 'Nadi: ' . e($ranap['nadi']) . ' x/mnt';
                              if (!empty($ranap['respirasi']) && $ranap['respirasi'] != "0") $ttv_ranap[] = 'RR: ' . e($ranap['respirasi']) . ' x/mnt';
                              if (!empty($ranap['tinggi']) && $ranap['tinggi'] != "0") $ttv_ranap[] = 'TB: ' . e($ranap['tinggi']) . ' cm';
                              if (!empty($ranap['berat']) && $ranap['berat'] != "0") $ttv_ranap[] = 'BB: ' . e($ranap['berat']) . ' Kg';
                              if (!empty($ranap['spo2']) && $ranap['spo2'] != "0") $ttv_ranap[] = 'SpO2: ' . e($ranap['spo2']) . ' %';
                              if (!empty($ranap['gcs']) && $ranap['gcs'] != "0") $ttv_ranap[] = 'GCS: ' . e($ranap['gcs']);
                              if (!empty($ranap['kesadaran'])) $ttv_ranap[] = 'Kesadaran: ' . e($ranap['kesadaran']);

                              if (!empty($ttv_ranap)) {
                                  $objek_ranap .= '<div class="mt-2 pt-2 border-top text-muted" style="font-size: 0.9em;">' . implode(' | ', $ttv_ranap) . '</div>';
                              }

                              echo '<tr>';
                              echo '<td>' . e($ranap['tgl_perawatan']) . '<br>' . e($ranap['jam_rawat']) . '</td>';
                              echo '<td>' . e($ranap['nama_petugas']) . '<br>(' . e($ranap['nip']) . ')</td>';
                              echo '<td>' . nl2br(e($ranap['keluhan'])) . '</td>';
                              echo '<td>' . $objek_ranap . '</td>';
                              echo '<td>' . nl2br(e($ranap['penilaian'])) . '</td>';
                              echo '<td>' . nl2br(e($ranap['rtl'])) . '</td>';
                              echo '<td>' . nl2br(e($ranap['instruksi'])) . '</td>';
                              echo '<td>' . nl2br(e($ranap['evaluasi'])) . '</td>';
                              echo '</tr>';
                          }
                          echo '</tbody></table></div>';
                      }

                      if (!$ada_data_soapi) {
                          echo '<div class="alert alert-light">Tidak ada data SOAPI-E yang tercatat untuk kunjungan ini.</div>';
                      }
                      ?>
                    </div>
                  </div>
                </div>
            <?php
                $i_soapi++;
            } // end while kunjungan
            echo '</div>'; // ./accordion
        }
    } catch (Exception $e) {
        echo '<div class="alert alert-danger mt-3">Gagal mengambil riwayat CPPT/SOAPI: ' . e($e->getMessage()) . '</div>';
    }
    ?>
  </div>
</div>

<script>
// (MODIFIKASI) Pindahkan skrip inisialisasi ke sini
// Ini akan dieksekusi oleh browser setelah konten AJAX dimuat ke dalam modal
$(function () {
  // Inisialisasi ulang tooltip jika ada (berguna untuk bootstrap)
  $('[data-toggle="tooltip"]').tooltip();

  // Inisialisasi Viewer.js untuk semua galeri yang baru dimuat
  // Kita gunakan setTimeout 0 untuk memastikan DOM sudah di-render oleh browser
  // setelah AJAX success, sebelum kita inisialisasi viewer-nya.
  setTimeout(function() {
    var galleries = document.querySelectorAll('.radiology-gallery-riwayat');
    galleries.forEach(function(gallery) {
        // Hindari inisialisasi ganda jika modal dibuka lagi atau accordion ditutup/dibuka
        if (!gallery.viewer) {
            new Viewer(gallery, {
                inline: false,
                toolbar: {
                    zoomIn: 4,
                    zoomOut: 4,
                    oneToOne: 4,
                    reset: 4,
                    prev: 4,
                    play: { show: 4, size: 'large' },
                    next: 4,
                    rotateLeft: 4,
                    rotateRight: 4,
                    flipHorizontal: 4,
                    flipVertical: 4,
                },
                title: (image) => {
                    return image.alt; // Ambil judul dari atribut alt
                },
                url: 'src', // Ambil gambar dari atribut src
            });
        }
    });
  }, 0); // setTimeout 0

});
</script>

