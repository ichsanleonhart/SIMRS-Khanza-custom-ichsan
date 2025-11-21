<?php
// --- 1. INISIALISASI SESI & KEAMANAN ---
session_start();

// Nonaktifkan tampilan error di lingkungan produksi
error_reporting(0);
ini_set('display_errors', 0);

// --- 2. KONFIGURASI KONEKSI DATABASE ---
define('DB_HOST', '192.168.1.5');
define('DB_USER', 'client');
define('DB_PASS', 'epotoransu'); // Masukkan password database Anda
define('DB_NAME', 'sik_master'); // Ganti dengan nama database SIMKES Khanza Anda

// --- 3. INISIALISASI VARIABEL & SETTING GLOBAL (REQ 1 & 2) ---
$loginError = '';
$nama_instansi = 'Laporan Audit Billing'; // Judul default
$favicon_href = '';
$logo_src = '';
$data = []; // Penampung data laporan
$tgl_awal = date('Y-m-01');
$tgl_akhir = date('Y-m-d');

// Ambil setting instansi & logo (dijalankan sekali untuk login & main page)
$conn_global = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn_global->connect_error) {
    $sql_setting_global = "SELECT nama_instansi, logo FROM setting LIMIT 1";
    $result_setting_global = $conn_global->query($sql_setting_global);
    if ($result_setting_global && $result_setting_global->num_rows > 0) {
        $setting = $result_setting_global->fetch_assoc();
        $nama_instansi = $setting['nama_instansi'];
        
        // Konversi data BLOB logo menjadi data URI base64
        if (!empty($setting['logo'])) {
            $logo_base64 = base64_encode($setting['logo']);
            $logo_src = 'data:image/png;base64,' . $logo_base64; 
            $favicon_href = $logo_src;
        }
    }
    $conn_global->close();
}
// --- AKHIR SETTING GLOBAL ---

// --- FUNGSI HELPER (Meniru Sequel.java) ---
function cariIsiAngka($conn, $sql, $no_rawat) {
    $value = 0;
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $no_rawat);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_array()) {
            $value = $row[0];
        }
        $stmt->close();
    }
    return floatval($value);
}

function cariIsi($conn, $sql, $no_rawat) {
    $value = "";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $no_rawat);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_array()) {
            $value = $row[0];
        }
        $stmt->close();
    }
    return $value;
}
// --- AKHIR FUNGSI HELPER ---

// --- 4. LOGIKA LOGOUT ---
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

// --- 5. LOGIKA LOGIN (Jika form login di-submit) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    
    $conn_login = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn_login->connect_error) {
        $loginError = "Koneksi Gagal: " . $conn_login->connect_error;
    } else {
        $sql_login = "SELECT 
                        AES_DECRYPT(u.id_user, 'nur') as id_user, 
                        AES_DECRYPT(u.password, 'windi') as password,
                        p.nama as nama_pegawai
                      FROM user u 
                      JOIN pegawai p ON AES_DECRYPT(u.id_user, 'nur') = p.nik
                      WHERE AES_DECRYPT(u.id_user, 'nur') = ?";
        
        $stmt_login = $conn_login->prepare($sql_login);
        
        if ($stmt_login) {
            $stmt_login->bind_param("s", $_POST['username']);
            $stmt_login->execute();
            $result_login = $stmt_login->get_result();
            
            if ($result_login->num_rows === 1) {
                $user = $result_login->fetch_assoc();
                
                if ($user['password'] === $_POST['password']) {
                    session_regenerate_id(true); 
                    $_SESSION['loggedin'] = true;
                    $_SESSION['username'] = $user['id_user']; 
                    $_SESSION['nama_pegawai'] = $user['nama_pegawai'];
                    header('Location: index.php'); 
                    exit;
                } else {
                    $loginError = 'Password yang Anda masukkan salah.';
                }
            } else {
                $loginError = 'Username tidak ditemukan atau tidak terdaftar sebagai pegawai.';
            }
            $stmt_login->close();
        } else {
            $loginError = 'Query login gagal disiapkan: '. $conn_login->error;
        }
        $conn_login->close();
    }
}

// --- 6. LOGIKA APLIKASI UTAMA (Jika SUDAH login) ---
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Koneksi Gagal: " . $conn->connect_error);
    }
    
    // (Query setting sudah dipindah ke atas/global)

    // --- 6b. Logika Filter & Eksekusi Query ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tgl_awal'])) {
        
        if (!empty($_POST['tgl_awal'])) {
            $tgl_awal = $_POST['tgl_awal'];
        }
        if (!empty($_POST['tgl_akhir'])) {
            $tgl_akhir = $_POST['tgl_akhir'];
        }
        
        $sql = <<<SQL
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
            reg_periksa.jam_reg DESC;
        SQL;
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        
        $stmt->bind_param("ss", $tgl_awal, $tgl_akhir);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result === false) {
            die("Error executing statement: " . $stmt->error);
        }

        // --- 6c. Kalkulasi Akurat (Meniru DlgPerkiraanBiayaRanap.java) ---
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $no_rawat = $row['no_rawat'];
                
                $Registrasi = $row['biaya_reg'];
                $Laborat = cariIsiAngka($conn, "select sum(biaya) from periksa_lab where no_rawat=?", $no_rawat) + cariIsiAngka($conn, "select sum(biaya_item) from detail_periksa_lab where no_rawat=?", $no_rawat);
                $Radiologi = cariIsiAngka($conn, "select sum(biaya) from periksa_radiologi where no_rawat=?", $no_rawat);
                $Operasi = cariIsiAngka($conn, "select sum(biayaoperator1+biayaoperator2+biayaoperator3+biayaasisten_operator1+biayaasisten_operator2+biayaasisten_operator3+biayainstrumen+biayadokter_anak+biayaperawaat_resusitas+biayadokter_anestesi+biayaasisten_anestesi+biayaasisten_anestesi2+biayabidan+biayabidan2+biayabidan3+biayaperawat_luar+biayaalat+biayasewaok+akomodasi+bagian_rs+biaya_omloop+biaya_omloop2+biaya_omloop3+biaya_omloop4+biaya_omloop5+biayasarpras+biaya_dokter_pjanak+biaya_dokter_umum) from operasi where no_rawat=?", $no_rawat);
                $Obat = cariIsiAngka($conn, "select sum(total) from detail_pemberian_obat where no_rawat=?", $no_rawat) + cariIsiAngka($conn, "select sum(besar_tagihan) from tagihan_obat_langsung where no_rawat=?", $no_rawat) + cariIsiAngka($conn, "select sum(hargasatuan*jumlah) from beri_obat_operasi where no_rawat=?", $no_rawat);
                $Ranap_Dokter = cariIsiAngka($conn, "select sum(biaya_rawat) from rawat_inap_dr where no_rawat=?", $no_rawat);
                $Ranap_Dokter_Paramedis = cariIsiAngka($conn, "select sum(biaya_rawat) from rawat_inap_drpr where no_rawat=?", $no_rawat);
                $Ranap_Paramedis = cariIsiAngka($conn, "select sum(biaya_rawat) from rawat_inap_pr where no_rawat=?", $no_rawat);
                $Ralan_Dokter = cariIsiAngka($conn, "select sum(biaya_rawat) from rawat_jl_dr where no_rawat=?", $no_rawat);
                $Ralan_Dokter_Paramedis = cariIsiAngka($conn, "select sum(biaya_rawat) from rawat_jl_drpr where no_rawat=?", $no_rawat);
                $Ralan_Paramedis = cariIsiAngka($conn, "select sum(biaya_rawat) from rawat_jl_pr where no_rawat=?", $no_rawat);
                $Tambahan = cariIsiAngka($conn, "select sum(besar_biaya) from tambahan_biaya where no_rawat=?", $no_rawat);
                $Potongan = cariIsiAngka($conn, "select sum(besar_pengurangan) from pengurangan_biaya where no_rawat=?", $no_rawat);
                $Kamar = cariIsiAngka($conn, "select sum(ttl_biaya) from kamar_inap where no_rawat=?", $no_rawat) + cariIsiAngka($conn, "select sum(biaya_sekali.besar_biaya) from biaya_sekali inner join kamar_inap on kamar_inap.kd_kamar=biaya_sekali.kd_kamar where kamar_inap.no_rawat=?", $no_rawat);
                $Harian = cariIsiAngka($conn, "select sum(biaya_harian.jml*biaya_harian.besar_biaya*kamar_inap.lama) from kamar_inap inner join biaya_harian on kamar_inap.kd_kamar=biaya_harian.kd_kamar where kamar_inap.no_rawat=?", $no_rawat);
                $Retur_Obat = (-1) * cariIsiAngka($conn, "select sum(subtotal) from detreturjual where no_retur_jual like ?", "%".$no_rawat."%");
                $Resep_Pulang = cariIsiAngka($conn, "select sum(total) from resep_pulang where no_rawat=?", $no_rawat);
                $Deposit = cariIsiAngka($conn, "select sum(besar_deposit) from deposit where no_rawat=?", $no_rawat);

                // Logika Ranap Gabung
                $no_rawat_gabung = cariIsi($conn, "select no_rawat2 from ranap_gabung where no_rawat=?", $no_rawat);
                if (!empty($no_rawat_gabung)) {
                    $Laborat += cariIsiAngka($conn, "select sum(biaya) from periksa_lab where no_rawat=?", $no_rawat_gabung) + cariIsiAngka($conn, "select sum(biaya_item) from detail_periksa_lab where no_rawat=?", $no_rawat_gabung);
                    $Radiologi += cariIsiAngka($conn, "select sum(biaya) from periksa_radiologi where no_rawat=?", $no_rawat_gabung);
                    $Operasi += cariIsiAngka($conn, "select sum(biayaoperator1+biayaoperator2+biayaoperator3+biayaasisten_operator1+biayaasisten_operator2+biayaasisten_operator3+biayainstrumen+biayadokter_anak+biayaperawaat_resusitas+biayadokter_anestesi+biayaasisten_anestesi+biayaasisten_anestesi2+biayabidan+biayabidan2+biayabidan3+biayaperawat_luar+biayaalat+biayasewaok+akomodasi+bagian_rs+biaya_omloop+biaya_omloop2+biaya_omloop3+biaya_omloop4+biaya_omloop5+biayasarpras+biaya_dokter_pjanak+biaya_dokter_umum) from operasi where no_rawat=?", $no_rawat_gabung);
                    $Obat += cariIsiAngka($conn, "select sum(total) from detail_pemberian_obat where no_rawat=?", $no_rawat_gabung) + cariIsiAngka($conn, "select sum(besar_tagihan) from tagihan_obat_langsung where no_rawat=?", $no_rawat_gabung) + cariIsiAngka($conn, "select sum(hargasatuan*jumlah) from beri_obat_operasi where no_rawat=?", $no_rawat_gabung);
                    $Ranap_Dokter += cariIsiAngka($conn, "select sum(biaya_rawat) from rawat_inap_dr where no_rawat=?", $no_rawat_gabung);
                    $Ranap_Dokter_Paramedis += cariIsiAngka($conn, "select sum(biaya_rawat) from rawat_inap_drpr where no_rawat=?", $no_rawat_gabung);
                    $Ranap_Paramedis += cariIsiAngka($conn, "select sum(biaya_rawat) from rawat_inap_pr where no_rawat=?", $no_rawat_gabung);
                    $Ralan_Dokter += cariIsiAngka($conn, "select sum(biaya_rawat) from rawat_jl_dr where no_rawat=?", $no_rawat_gabung);
                    $Ralan_Dokter_Paramedis += cariIsiAngka($conn, "select sum(biaya_rawat) from rawat_jl_drpr where no_rawat=?", $no_rawat_gabung);
                    $Ralan_Paramedis += cariIsiAngka($conn, "select sum(biaya_rawat) from rawat_jl_pr where no_rawat=?", $no_rawat_gabung);
                    $Tambahan += cariIsiAngka($conn, "select sum(besar_biaya) from tambahan_biaya where no_rawat=?", $no_rawat_gabung);
                    $Potongan += cariIsiAngka($conn, "select sum(besar_pengurangan) from pengurangan_biaya where no_rawat=?", $no_rawat_gabung);
                    $Kamar += cariIsiAngka($conn, "select sum(ttl_biaya) from kamar_inap where no_rawat=?", $no_rawat_gabung) + cariIsiAngka($conn, "select sum(biaya_sekali.besar_biaya) from biaya_sekali inner join kamar_inap on kamar_inap.kd_kamar=biaya_sekali.kd_kamar where kamar_inap.no_rawat=?", $no_rawat_gabung);
                    $Harian += cariIsiAngka($conn, "select sum(biaya_harian.jml*biaya_harian.besar_biaya*kamar_inap.lama) from kamar_inap inner join biaya_harian on kamar_inap.kd_kamar=biaya_harian.kd_kamar where kamar_inap.no_rawat=?", $no_rawat_gabung);
                    $Retur_Obat += (-1) * cariIsiAngka($conn, "select sum(subtotal) from detreturjual where no_retur_jual like ?", "%".$no_rawat_gabung."%");
                    $Resep_Pulang += cariIsiAngka($conn, "select sum(total) from resep_pulang where no_rawat=?", $no_rawat_gabung);
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
            
            // --- 6d. Req 3: Sorting Audit (Temuan) ke Atas ---
            function sort_audit_priority($a, $b) {
                // Kriteria audit: Batal TAPI ada biaya obat DAN biaya total
                $a_is_audit = ($a['Status Pelayanan'] == 'Batal' && $a['Biaya Obat_raw'] > 0 && $a['billing_sementara_raw'] > 0);
                $b_is_audit = ($b['Status Pelayanan'] == 'Batal' && $b['Biaya Obat_raw'] > 0 && $b['billing_sementara_raw'] > 0);

                if ($a_is_audit == $b_is_audit) {
                    // Jika statusnya sama (keduanya audit ATAU keduanya bukan audit)
                    // Urutkan berdasarkan TANGGAL (DESC)
                    $date_a = $a['Tgl Reg'] . ' ' . $a['Jam reg'];
                    $date_b = $b['Tgl Reg'] . ' ' . $b['Jam reg'];
                    return strcmp($date_b, $date_a);
                }

                // Mengurutkan berdasarkan status audit (DESC), (true/1) akan diletakkan sebelum (false/0)
                return (int)$b_is_audit - (int)$a_is_audit;
            }
            // Terapkan sorting ke array data
            usort($data, 'sort_audit_priority');

        } // End if ($result->num_rows > 0)
        
        $stmt->close();
    } // End if ($_SERVER['REQUEST_METHOD'] == 'POST')
    
    $conn->close();
} // End if (isset($_SESSION['loggedin']))

// --- 7. TAMPILAN HTML ---
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($nama_instansi); ?></title>
    <?php if (!empty($favicon_href)): ?>
    <link rel="icon" type="image/png" href="<?php echo $favicon_href; ?>">
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
    <link href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <?php endif; ?>

    <style>
        body {
            background-color: #f0f2f5;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .report-container {
            padding: 1rem;
        }
        .card {
            border-radius: 0.5rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            border: none;
        }
        .navbar-brand img {
            max-height: 40px;
            margin-right: 10px;
        }
    </style>
</head>
<body>

<?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
      <div class="container-fluid">
        <a class="navbar-brand" href="#">
          <?php if (!empty($logo_src)): ?>
            <img src="<?php echo $logo_src; ?>" alt="Logo">
          <?php endif; ?>
          <?php echo htmlspecialchars($nama_instansi); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item">
              <span class="navbar-text me-3">
                Login sebagai: <strong><?php echo htmlspecialchars($_SESSION['nama_pegawai']); ?></strong>
              </span>
            </li>
            <li class="nav-item">
              <a class="btn btn-outline-danger" href="?logout=true">Logout</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <div class="container-fluid report-container">
        <h1 class="h3 my-4 text-gray-800">Laporan Audit Billing (Belum Bayar)</h1>

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Filter Data</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="index.php">
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
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                                  <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                                </svg>
                                Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Hasil Data</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="laporanTable" width="100%" cellspacing="0">
                        <thead>
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
                                <th>Billing Sementara</th>
                                <th>Biaya Obat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($data)): ?>
                                <?php foreach ($data as $row):
                                    // Req 3 & 6: Logika pewarnaan baris (Temuan Audit)
                                    $rowClass = '';
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
                                <?php if ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
                                <tr>
                                    <td colspan="11" class="text-center">Tidak ada data yang ditemukan untuk rentang tanggal yang dipilih.</td>
                                </tr>
                                <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-center">Silakan atur filter tanggal dan klik "Filter" untuk menampilkan data.</td>
                                </tr>
                                <?php endif; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div> 

<?php else: ?>
    <div class="container login-container">
        <div class="card login-card">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <?php if (!empty($logo_src)): ?>
                        <img src="<?php echo $logo_src; ?>" alt="Logo" style="max-height: 80px; margin-bottom: 1rem;">
                    <?php endif; ?>
                    <h3 class="card-title mb-0"><?php echo htmlspecialchars($nama_instansi); ?></h3>
                    <p class="text-muted">Laporan Audit Billing</p>
                </div>

                <form method="POST" action="index.php">
                    <?php if (!empty($loginError)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $loginError; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Login</button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <small class="text-muted">Harap login menggunakan akun SIMKES Khanza Anda.</small>
                </div>

            </div>
        </div>
    </div>

<?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>

        <?php 
        // Hanya inisialisasi DataTables JIKA $data (hasil query) tidak kosong.
        if (!empty($data)): 
        ?>
        <script>
            $(document).ready(function() {
                $('#laporanTable').DataTable({
                    "language": {
                        "url": "https://cdn.datatables.net/plug-ins/2.0.8/i18n/id.json"
                    },
                    "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"] ],
                    "pageLength": 25,
                    
                    // Req 3: Nonaktifkan sorting default DataTables
                    // karena kita sudah sorting manual di PHP
                    "order": [] 
                });
            });
        </script>
        <?php 
        endif; // Akhir dari if (!empty($data))
        ?>

    <?php endif; // Akhir dari if (loggedin) ?>

</body>
</html>