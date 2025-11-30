<?php
/*
 * File: /webapps/berkas_digital_perawatan/dashboard.php
 * Fungsi: Menampilkan daftar pasien dan filter (Refined V3)
 */
session_start();

// CEK OTENTIKASI
if (!isset($_SESSION['casemix_login']) || $_SESSION['casemix_login'] !== true) {
    header("Location: index.php");
    exit;
}

require_once('../conf/conf.php');
$koneksi = bukakoneksi();

// 1. AMBIL INFO INSTANSI
$nama_instansi = "RS Khanza";
$q_set = mysqli_query($koneksi, "SELECT nama_instansi FROM setting LIMIT 1");
if($r_set = mysqli_fetch_assoc($q_set)) $nama_instansi = $r_set['nama_instansi'];

// 2. AMBIL NAMA USER (PEGAWAI)
$user_id = $_SESSION['casemix_user'];
$nama_user_login = $user_id; // Default tampilkan NIK jika nama tidak ketemu

// Cek di tabel pegawai
$q_pegawai = mysqli_query($koneksi, "SELECT nama FROM pegawai WHERE nik = '$user_id'");
if(mysqli_num_rows($q_pegawai) > 0){
    $r_peg = mysqli_fetch_assoc($q_pegawai);
    $nama_user_login = $r_peg['nama'];
} else {
    // Opsional: Cek di tabel dokter jika login sebagai dokter
    $q_dok = mysqli_query($koneksi, "SELECT nm_dokter FROM dokter WHERE kd_dokter = '$user_id'");
    if(mysqli_num_rows($q_dok) > 0){
        $r_dok = mysqli_fetch_assoc($q_dok);
        $nama_user_login = $r_dok['nm_dokter'];
    }
}

// 3. FILTER TANGGAL
$tgl_awal  = isset($_GET['tgl_awal']) ? validTeks4($_GET['tgl_awal'], 10) : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? validTeks4($_GET['tgl_akhir'], 10) : date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Casemix - <?= $nama_instansi ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; font-size: 0.9rem; }
        .navbar { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); }
        .navbar-brand img { height: 35px; border-radius: 4px; background: #fff; padding: 2px; }
        /* Custom Color untuk Ranap */
        .bg-pink { background-color: #d63384 !important; color: white; }
        .penjamin-text { font-size: 0.75rem; font-weight: 700; color: #6c757d; display: block; margin-top: 4px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="#">
            <img src="logo.php" alt="Logo"> <?= $nama_instansi ?>
        </a>
        <div class="d-flex text-white align-items-center">
            <span class="me-3 fw-bold"><i class="fas fa-user-circle me-2"></i><?= $nama_user_login ?></span>
            <a href="logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body py-3">
            <form action="" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Dari Tanggal</label>
                    <input type="date" name="tgl_awal" class="form-control" value="<?= $tgl_awal ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Sampai Tanggal</label>
                    <input type="date" name="tgl_akhir" class="form-control" value="<?= $tgl_akhir ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-list me-2"></i>Daftar Pasien</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablePasien" class="table table-hover table-bordered w-100">
                    <thead class="table-light">
                        <tr>
                            <th>No. Rawat</th>
                            <th>Tgl. Reg</th>
                            <th>No. RM</th>
                            <th>Nama Pasien</th>
                            <th>Status</th>
                            <th>Dokter</th>
                            <th>Poli</th>
                            <th>SEP</th>
                            <th>Diagnosa</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // QUERY UTAMA DENGAN JOIN PENJAB
                        $query = "SELECT 
                                    p.nm_pasien, 
                                    p.no_rkm_medis, 
                                    p.alamat, 
                                    rp.no_rawat, 
                                    rp.tgl_registrasi, 
                                    rp.jam_reg,
                                    rp.status_lanjut, 
                                    d.nm_dokter, 
                                    poli.nm_poli,
                                    pj.png_jawab, -- Ambil Nama Penjamin
                                    COALESCE(bs.no_sep, '-') as no_sep,
                                    COALESCE(bs.no_kartu, '-') as no_kartu,
                                    COALESCE(pen.nm_penyakit, '-') as diagnosa_utama,
                                    COALESCE(pen.kd_penyakit, '-') as kd_diagnosa
                                FROM reg_periksa rp
                                JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                                JOIN dokter d ON rp.kd_dokter = d.kd_dokter
                                JOIN poliklinik poli ON rp.kd_poli = poli.kd_poli
                                JOIN penjab pj ON rp.kd_pj = pj.kd_pj -- Join ke Penjab
                                LEFT JOIN bridging_sep bs ON rp.no_rawat = bs.no_rawat
                                LEFT JOIN diagnosa_pasien dp ON rp.no_rawat = dp.no_rawat AND dp.prioritas = 1
                                LEFT JOIN penyakit pen ON dp.kd_penyakit = pen.kd_penyakit
                                WHERE rp.tgl_registrasi BETWEEN '$tgl_awal' AND '$tgl_akhir'
                                ORDER BY rp.tgl_registrasi DESC, rp.jam_reg DESC";
                        
                        $hasil = mysqli_query($koneksi, $query);
                        while ($row = mysqli_fetch_assoc($hasil)) {
                            // LOGIKA WARNA BADGE
                            // Ralan = Hijau (Success), Ranap = Pink (Custom class .bg-pink)
                            $badge_class = ($row['status_lanjut'] == 'Ralan') ? 'bg-success' : 'bg-pink';
                        ?>
                        <tr>
                            <td><?= $row['no_rawat'] ?></td>
                            <td><?= $row['tgl_registrasi'] ?> <small class="text-muted"><?= $row['jam_reg'] ?></small></td>
                            <td><?= $row['no_rkm_medis'] ?></td>
                            <td>
                                <div class="fw-bold"><?= $row['nm_pasien'] ?></div>
                                <small class="text-muted text-truncate d-block" style="max-width:200px"><?= $row['alamat'] ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $badge_class ?>"><?= $row['status_lanjut'] ?></span>
                                <span class="penjamin-text"><?= $row['png_jawab'] ?></span>
                            </td>
                            <td><small><?= $row['nm_dokter'] ?></small></td>
                            <td><small><?= $row['nm_poli'] ?></small></td>
                            <td>
                                <?php if($row['no_sep'] !== '-'): ?>
                                    <span class="badge bg-primary"><?= $row['no_sep'] ?></span>
                                    <div class="small mt-1"><?= $row['no_kartu'] ?></div>
                                <?php else: echo '-'; endif; ?>
                            </td>
                            <td>
                                <?php if($row['kd_diagnosa'] !== '-'): ?>
                                    <span class="badge bg-secondary"><?= $row['kd_diagnosa'] ?></span>
                                    <div class="small mt-1 text-truncate" style="max-width:150px" title="<?= $row['diagnosa_utama'] ?>"><?= $row['diagnosa_utama'] ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="lihat_berkas.php?no_rawat=<?= urlencode($row['no_rawat']) ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-file-pdf"></i> Berkas
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#tablePasien').DataTable({
            dom: 'Bfrtip',
            pageLength: 15,
            order: [],
            buttons: [
                { extend: 'excel', className: 'btn btn-success btn-sm', text: '<i class="fas fa-file-excel me-2"></i>Export Excel' }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' }
        });
    });
</script>
</body>
</html>
<?php mysqli_close($koneksi); ?>