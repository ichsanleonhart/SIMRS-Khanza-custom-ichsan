<?php
/*
 * ===================================================================================
 * FILE AJAX: GET RIWAYAT PASIEN (Versi 2.1 - Tampilan Bersusun + Info Diagnosa)
 * ===================================================================================
 * Halaman ini dipanggil via AJAX untuk mengambil riwayat lab dan CPPT pasien.
 *
 * MODIFIKASI:
 * - Mengubah tampilan Riwayat Lab menjadi accordion bersusun per kunjungan lab.
 * - Mengubah tampilan Riwayat CPPT/SOAPI dari timeline menjadi accordion bersusun
 * per nomor rawat (kunjungan).
 * - Menerapkan logika query N+1 (query di dalam loop) untuk meniru
 * perilaku RMRiwayatPerawatan.java (seperti yang diminta).
 * - Menambahkan pemisahan antara Lab PK/MB dan Lab PA.
 * - (BARU) Menambahkan query ke permintaan_lab di dalam loop PK/MB
 * untuk mengambil diagnosa_klinis & informasi_tambahan.
 *
 * Keamanan:
 * - Memerlukan login (require_login())
 * - Memerlukan metode POST
 * - Memerlukan validasi CSRF token
 * - Melakukan sanitasi input
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
$no_rawat_current = filter_input(INPUT_POST, 'no_rawat_current', FILTER_SANITIZE_STRING); // Digunakan untuk menandai item aktif

if (empty($no_rkm_medis)) {
    die('<div class="alert alert-warning">No. Rekam Medis tidak valid.</div>');
}

$pdo = connect_db();
?>

<!-- 3. Tampilan Modal (Tabs) -->
<ul class="nav nav-tabs" id="riwayatTab" role="tablist">
  <li class="nav-item">
    <a class="nav-link active" id="lab-tab" data-toggle="tab" href="#riwayatLab" role="tab" aria-controls="riwayatLab" aria-selected="true">
      <i class="fas fa-flask"></i> Riwayat Laboratorium
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" id="cppt-tab" data-toggle="tab" href="#riwayatCPPT" role="tab" aria-controls="riwayatCPPT" aria-selected="false">
      <i class="fas fa-notes-medical"></i> Riwayat CPPT/SOAPI
    </a>
  </li>
</ul>
<div class="tab-content" id="riwayatTabContent">
  
  <!-- ============================================= -->
  <!-- TAB 1: RIWAYAT LABORATORIUM (Tampilan Bersusun) -->
  <!-- ============================================= -->
  <div class="tab-pane fade show active" id="riwayatLab" role="tabpanel" aria-labelledby="lab-tab">
    
    <!-- Bagian Laboratorium PK & MB -->
    <h5 class="mt-3">Pemeriksaan Laboratorium PK & MB</h5>
    <?php
    try {
        // Query 1: (Outer loop - rs4) - Ambil header kunjungan lab PK/MB
        $sql_lab_pkmb = "SELECT
                            pl.no_rawat,
                            pl.tgl_periksa,
                            pl.jam,
                            d_pj.nm_dokter AS dokter_pj_lab,
                            d_perujuk.nm_dokter AS dokter_perujuk
                        FROM periksa_lab pl
                        JOIN reg_periksa rp ON pl.no_rawat = rp.no_rawat
                        LEFT JOIN dokter d_pj ON pl.kd_dokter = d_pj.kd_dokter
                        LEFT JOIN dokter d_perujuk ON pl.dokter_perujuk = d_perujuk.kd_dokter
                        WHERE rp.no_rkm_medis = ? AND pl.kategori <> 'PA'
                        GROUP BY pl.no_rawat, pl.tgl_periksa, pl.jam
                        ORDER BY pl.tgl_periksa DESC, pl.jam DESC
                        LIMIT 30"; // Batasi 30 kunjungan lab PK/MB terakhir
        
        $stmt_lab_pkmb = $pdo->prepare($sql_lab_pkmb);
        $stmt_lab_pkmb->execute([$no_rkm_medis]);
        
        if ($stmt_lab_pkmb->rowCount() == 0) {
            echo '<div class="alert alert-info mt-2">Tidak ditemukan riwayat laboratorium PK/MB.</div>';
        } else {
            echo '<div class="accordion" id="accordionLabPKMB">';
            $i_pkmb = 1;
            while ($lab = $stmt_lab_pkmb->fetch(PDO::FETCH_ASSOC)) {
                $collapse_id = 'collapseLabPKMB' . $i_pkmb;
                $header_id = 'headerLabPKMB' . $i_pkmb;
                
                // Cek apakah ini kunjungan yang sedang dibuka
                $is_current = ($lab['no_rawat'] == $no_rawat_current);
                $card_class = $is_current ? 'card-success' : 'card-primary';
                $badge_current = $is_current ? ' <span class="badge badge-success ml-2">Kunjungan Saat Ini</span>' : '';
            ?>
                <div class="card <?php echo $card_class; ?> card-outline mb-1">
                  <div class="card-header" id="<?php echo $header_id; ?>">
                    <h5 class="mb-0">
                      <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#<?php echo $collapse_id; ?>" aria-expanded="<?php echo $is_current ? 'true' : 'false'; ?>" aria-controls="<?php echo $collapse_id; ?>">
                        <b><?php echo $i_pkmb; ?>. Tgl:</b> <?php echo e($lab['tgl_periksa']); ?> <?php echo e($lab['jam']); ?> | <b>No:</b> <?php echo e($lab['no_rawat']); ?> | <b>Dokter PJ:</b> <?php echo e($lab['dokter_pj_lab']); ?>
                        <?php echo $badge_current; ?>
                      </button>
                    </h5>
                  </div>
                  <div id="<?php echo $collapse_id; ?>" class="collapse <?php echo $is_current ? 'show' : ''; ?>" aria-labelledby="<?php echo $header_id; ?>" data-parent="#accordionLabPKMB">
                    <div class="card-body p-2">
                      <?php
                      // --- AWAL MODIFIKASI ---
                      // Query 1.5: (BARU) - Ambil info tambahan & diagnosa klinis dari permintaan_lab
                      // Menggunakan composite key yang disarankan: (no_rawat, tgl_periksa/hasil, jam/hasil)
                      $sql_permintaan = "SELECT informasi_tambahan, diagnosa_klinis
                                         FROM permintaan_lab
                                         WHERE no_rawat = ? AND tgl_hasil = ? AND jam_hasil = ?
                                         LIMIT 1"; // Asumsi 1 permintaan per timestamp hasil
                      $stmt_permintaan = $pdo->prepare($sql_permintaan);
                      $stmt_permintaan->execute([$lab['no_rawat'], $lab['tgl_periksa'], $lab['jam']]);
                      $info_permintaan = $stmt_permintaan->fetch(PDO::FETCH_ASSOC);

                      if ($info_permintaan) {
                          echo '<div class="alert alert-info py-2 mb-2" style="font-size: 0.9em;">';
                          if (!empty($info_permintaan['diagnosa_klinis'])) {
                              echo '<strong>Diagnosa Klinis:</strong> ' . nl2br(e($info_permintaan['diagnosa_klinis'])) . '<br>';
                          }
                          if (!empty($info_permintaan['informasi_tambahan'])) {
                              echo '<strong>Info Tambahan:</strong> ' . nl2br(e($info_permintaan['informasi_tambahan']));
                          }
                          echo '</div>';
                      }
                      // --- AKHIR MODIFIKASI ---

                      // Query 2: (rs3 equiv) - Ambil detail hasil lab
                      $sql_detail = "SELECT
                                        jpl.kd_jenis_prw,
                                        jpl.nm_perawatan,
                                        tpl.Pemeriksaan,
                                        dpl.nilai,
                                        tpl.satuan,
                                        dpl.nilai_rujukan,
                                        dpl.keterangan
                                     FROM detail_periksa_lab dpl
                                     JOIN jns_perawatan_lab jpl ON dpl.kd_jenis_prw = jpl.kd_jenis_prw
                                     LEFT JOIN template_laboratorium tpl ON dpl.id_template = tpl.id_template
                                     WHERE dpl.no_rawat = ? AND dpl.tgl_periksa = ? AND dpl.jam = ?
                                     ORDER BY jpl.kd_jenis_prw, tpl.urut";
                      
                      $stmt_detail = $pdo->prepare($sql_detail);
                      $stmt_detail->execute([$lab['no_rawat'], $lab['tgl_periksa'], $lab['jam']]);
                      $hasil_lab = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);

                      // Kelompokkan hasil (sama seperti di hasil_laboratorium.php)
                      $grouped_hasil = [];
                      foreach ($hasil_lab as $hasil) {
                          if (!isset($grouped_hasil[$hasil['nm_perawatan']])) {
                              $grouped_hasil[$hasil['nm_perawatan']] = [];
                          }
                          $grouped_hasil[$hasil['nm_perawatan']][] = $hasil;
                      }
                      ?>
                      <div class="table-responsive">
                        <table class="table table-sm table-hover table-bordered mb-2">
                          <thead class="thead-light">
                            <tr>
                              <th>Pemeriksaan</th>
                              <th>Hasil</th>
                              <th>Satuan</th>
                              <th>Nilai Rujukan</th>
                              <th>Keterangan</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php if (empty($grouped_hasil)): ?>
                                <tr><td colspan="5" class="text-center">Detail hasil tidak ditemukan.</td></tr>
                            <?php else: ?>
                                <?php foreach ($grouped_hasil as $nama_perawatan => $details): ?>
                                  <tr class="bg-light font-weight-bold">
                                    <td colspan="5"><?php echo e($nama_perawatan); ?></td>
                                  </tr>
                                  <?php foreach ($details as $detail): ?>
                                    <?php
                                      $ket_lower = strtolower($detail['keterangan']);
                                      $nilai_class = '';
                                      if ($ket_lower == 'l' || strpos($ket_lower, 'low') !== false) {
                                          $nilai_class = 'text-primary font-weight-bold'; // Biru untuk Low
                                      } elseif ($ket_lower == 'h' || strpos($ket_lower, 'high') !== false) {
                                          $nilai_class = 'text-danger font-weight-bold'; // Merah untuk High
                                      } elseif ($ket_lower == 't' || $ket_lower == '*') {
                                          $nilai_class = 'font-weight-bold'; // Bold untuk T/Abnormal
                                      }
                                    ?>
                                    <tr>
                                      <td style="padding-left: 20px;"><?php echo e($detail['Pemeriksaan']); ?></td>
                                      <td class="<?php echo $nilai_class; ?>"><?php echo e($detail['nilai']); ?></td>
                                      <td><?php echo e($detail['satuan']); ?></td>
                                      <td><?php echo e($detail['nilai_rujukan']); ?></td>
                                      <td><?php echo e($detail['keterangan']); ?></td>
                                    </tr>
                                  <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                          </tbody>
                        </table>
                      </div>
                      <?php
                      // Query 3: Ambil Kesan & Saran
                      $stmt_kesan = $pdo->prepare("SELECT kesan, saran FROM saran_kesan_lab WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?");
                      $stmt_kesan->execute([$lab['no_rawat'], $lab['tgl_periksa'], $lab['jam']]);
                      $kesan_saran = $stmt_kesan->fetch(PDO::FETCH_ASSOC);
                      
                      if ($kesan_saran && (!empty($kesan_saran['kesan']) || !empty($kesan_saran['saran']))) {
                          echo '<hr class="my-2">';
                          if (!empty($kesan_saran['kesan'])) {
                              echo '<div><strong>Kesan:</strong><br>' . nl2br(e($kesan_saran['kesan'])) . '</div>';
                          }
                          if (!empty($kesan_saran['saran'])) {
                              echo '<div class="mt-2"><strong>Saran:</strong><br>' . nl2br(e($kesan_saran['saran'])) . '</div>';
                          }
                      }
                      ?>
                    </div>
                  </div>
                </div>
            <?php
                $i_pkmb++;
            } // end while pkmb
            echo '</div>'; // ./accordion
        }
    } catch (Exception $e) {
        echo '<div class="alert alert-danger mt-3">Gagal mengambil riwayat lab PK/MB: ' . e($e->getMessage()) . '</div>';
    }
    ?>

   
  </div>
  
  <!-- ============================================= -->
  <!-- TAB 2: RIWAYAT CPPT / SOAPI (Tampilan Bersusun) -->
  <!-- ============================================= -->
  <div class="tab-pane fade" id="riwayatCPPT" role="tabpanel" aria-labelledby="cppt-tab">
    <?php
    try {
        // Query 1: (Outer loop - rs) - Ambil Kunjungan (reg_periksa)
        $sql_kunjungan = "SELECT no_rawat, tgl_registrasi, status_lanjut 
                          FROM reg_periksa 
                          WHERE no_rkm_medis = ? AND stts <> 'Batal' 
                          ORDER BY tgl_registrasi DESC, jam_reg DESC
                          LIMIT 30"; // Batasi 30 kunjungan terakhir
        
        $stmt_kunjungan = $pdo->prepare($sql_kunjungan);
        $stmt_kunjungan->execute([$no_rkm_medis]);

        if ($stmt_kunjungan->rowCount() == 0) {
            echo '<div class="alert alert-info mt-3">Tidak ditemukan riwayat kunjungan (CPPT/SOAPI) untuk pasien ini.</div>';
        } else {
            echo '<div class="accordion" id="accordionSOAPI">';
            $i_soapi = 1;
            while ($kunjungan = $stmt_kunjungan->fetch(PDO::FETCH_ASSOC)) {
                $no_rawat_soapi = $kunjungan['no_rawat'];
                $collapse_id_soapi = 'collapseSOAPI' . $i_soapi;
                $header_id_soapi = 'headerSOAPI' . $i_soapi;
                
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
                  <div id="<?php echo $collapse_id_soapi; ?>" class="collapse <?php echo $is_current_soapi ? 'show' : ''; ?>" aria-labelledby="<?php echo $header_id_soapi; ?>" data-parent="#accordionSOAPI">
                    <div class="card-body p-2">
                      <?php
                      // Flag untuk cek apakah ada data
                      $ada_data_soapi = false;
                      
                      // Query 2: (rs2) - Ambil SOAPI Ralan
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
                              // Buat TTV untuk Objektif (O)
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
                              echo '<td>' . $objek . '</td>'; // Tampilkan Objek + TTV
                              echo '<td>' . nl2br(e($ralan['penilaian'])) . '</td>';
                              echo '<td>' . nl2br(e($ralan['rtl'])) . '</td>';
                              echo '<td>' . nl2br(e($ralan['instruksi'])) . '</td>';
                              echo '<td>' . nl2br(e($ralan['evaluasi'])) . '</td>';
                              echo '</tr>';
                          }
                          echo '</tbody></table></div>';
                      }
                      
                      // Query 3: (rs2) - Ambil SOAPI Ranap
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
                              // Buat TTV untuk Objektif (O)
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
                              echo '<td>' . $objek_ranap . '</td>'; // Tampilkan Objek + TTV
                              echo '<td>' . nl2br(e($ranap['penilaian'])) . '</td>';
                              echo '<td>' . nl2br(e($ranap['rtl'])) . '</td>';
                              echo '<td>' . nl2br(e($ranap['instruksi'])) . '</td>';
                              echo '<td>' . nl2br(e($ranap['evaluasi'])) . '</td>';
                              echo '</tr>';
                          }
                          echo '</tbody></table></div>';
                      }

                      // Jika tidak ada data Ralan & Ranap
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
// Inisialisasi ulang tooltip jika ada (opsional, tapi bagus)
$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})
// Script ini akan berjalan di dalam konteks AJAX success di hasil_laboratorium.php
// Jadi, tidak perlu $(document).ready()
</script>

