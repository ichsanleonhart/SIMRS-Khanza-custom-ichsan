<?php
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__); // 

// Cek apakah user boleh mengakses halaman ini
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}


// Proses Simpan
if (isset($_POST['simpan'])) {
  $user_id       = $_SESSION['user_id'];
  $id_cuti       = intval($_POST['id_cuti']);
  $tanggal_array = array_map('trim', explode(',', $_POST['tanggal_cuti']));
  $alasan        = mysqli_real_escape_string($conn, $_POST['alasan']);
  $atasan_id     = intval($_POST['atasan_id']);
  $pengganti_id  = intval($_POST['pengganti_id']);
  $waktu_input   = date('Y-m-d H:i:s');

  // Validasi format tanggal cuti
  $valid_tanggal = array_filter($tanggal_array, function($tgl) {
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl);
  });

  if (empty($valid_tanggal)) {
    $_SESSION['flash_message'] = "❌ Format tanggal tidak valid.";
  } else {
    // Hitung total hari cuti
    $total_hari = count($valid_tanggal);
    $tanggal_mulai = min($valid_tanggal);
    $tanggal_selesai = max($valid_tanggal);

    // Ambil jatah cuti user
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT jatah_cuti FROM users WHERE id = $user_id"));
    $jatah_cuti = (int) $user['jatah_cuti'];

    if ($total_hari > $jatah_cuti) {
      $_SESSION['flash_message'] = "❌ Jatah cuti tidak mencukupi. Tersisa: $jatah_cuti hari.";
    } else {
      // Simpan ke tabel pengajuan_cuti
      $query = "INSERT INTO pengajuan_cuti 
        (user_id, id_cuti, tanggal_mulai, tanggal_selesai, alasan, atasan_id, pengganti_id, status, created_at) 
        VALUES 
        ('$user_id', '$id_cuti', '$tanggal_mulai', '$tanggal_selesai', '$alasan', '$atasan_id', '$pengganti_id', 'Menunggu', '$waktu_input')";

     if (mysqli_query($conn, $query)) {
  $pengajuan_id = mysqli_insert_id($conn);

  // Simpan detail cuti
  foreach ($valid_tanggal as $tgl) {
    $detail = mysqli_query($conn, "INSERT INTO detail_cuti (pengajuan_id, tanggal_cuti) VALUES ('$pengajuan_id', '$tgl')");
    if (!$detail) {
      $_SESSION['flash_message'] = "❌ Gagal simpan detail tanggal $tgl: " . mysqli_error($conn);
      echo "<script>location.href='pengajuan_cuti.php';</script>";
      exit;
    }
  }

  // Update jatah cuti
  $update = mysqli_query($conn, "UPDATE users SET jatah_cuti = jatah_cuti - $total_hari WHERE id = $user_id");
  if (!$update) {
    $_SESSION['flash_message'] = "❌ Gagal update jatah cuti: " . mysqli_error($conn);
    echo "<script>location.href='pengajuan_cuti.php';</script>";
    exit;
  }

  $_SESSION['flash_message'] = "✅ Pengajuan cuti berhasil. Total: $total_hari hari. Sisa jatah: " . ($jatah_cuti - $total_hari);
  echo "<script>location.href='pengajuan_cuti.php';</script>";
  exit;

} else {
  $_SESSION['flash_message'] = "❌ Gagal menyimpan pengajuan cuti: " . mysqli_error($conn);
}

    }
  }
}

// Ambil data user (untuk dropdown atasan & pengganti)
$users = mysqli_query($conn, "SELECT * FROM users WHERE status = 'active' ORDER BY nama ASC");
?>


<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>f.i.x.p.o.i.n.t</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

  <style>
    .ui-datepicker {
      font-size: 12px;
    }
  </style>
</head>
<body>
<div id="app">
  <div class="main-wrapper main-wrapper-1">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
      <section class="section">
        <div class="section-body">

          <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-info text-center">
              <?= $_SESSION['flash_message'] ?>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
          <?php endif; ?>

          <div class="card">
            <div class="card-header"><h4>Form Pengajuan Cuti</h4></div>
            <div class="card-body">
              <form method="POST">
                <div class="form-group">
                  <label>Jenis Cuti</label>
                <?php
                  $jenis_cuti_data = mysqli_query($conn, "SELECT id, nama_cuti FROM master_cuti ORDER BY nama_cuti ASC");
                  ?>
                  <select name="id_cuti" class="form-control" required>
                    <option value="">-- Pilih Jenis Cuti --</option>
                    <?php while ($cuti = mysqli_fetch_assoc($jenis_cuti_data)) : ?>
                      <option value="<?= $cuti['id'] ?>"><?= htmlspecialchars($cuti['nama_cuti']) ?></option>
                    <?php endwhile; ?>
                  </select>

                </div>

                <div class="form-group">
                  <label>Tanggal Cuti (boleh pilih lebih dari 1)</label>
                  <input type="text" name="tanggal_cuti" id="tanggal_cuti" class="form-control" autocomplete="off" required>
                  <small class="form-text text-muted">Klik beberapa tanggal di kalender.</small>
                </div>

                <div class="form-group">
                  <label>Alasan Cuti</label>
                  <textarea name="alasan" class="form-control" rows="3" required></textarea>
                </div>

                <div class="form-group">
                  <label>Atasan Langsung</label>
                  <select name="atasan_id" class="form-control" required>
                    <option value="">-- Pilih Atasan --</option>
                    <?php mysqli_data_seek($users, 0); while ($user = mysqli_fetch_assoc($users)) : ?>
                      <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['nama']) ?> (<?= $user['nik'] ?>)</option>
                    <?php endwhile; ?>
                  </select>
                </div>

                <div class="form-group">
                  <label>Pengganti Selama Cuti</label>
                  <select name="pengganti_id" class="form-control" required>
                    <option value="">-- Pilih Pengganti --</option>
                    <?php mysqli_data_seek($users, 0); while ($user = mysqli_fetch_assoc($users)) : ?>
                      <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['nama']) ?> (<?= $user['nik'] ?>)</option>
                    <?php endwhile; ?>
                  </select>
                </div>

                <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Ajukan Cuti</button>
              </form>
            </div>
          </div>

        </div>
      </section>
    </div>
  </div>
</div>

<!-- JS -->
<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="https://rawcdn.githack.com/dubrox/Multiple-Dates-Picker-for-jQuery-UI/master/jquery-ui.multidatespicker.js"></script>

<script>
$(function() {
  $('#tanggal_cuti').multiDatesPicker({
    dateFormat: "yy-mm-dd"
  });
});
</script>
</body>
</html>
