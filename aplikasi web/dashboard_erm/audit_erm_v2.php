<?php
/**
 * AUDIT KEPATUHAN ERM (Electronic Medical Record) - ADVANCED VERSION
 * Based on Schema SIMRS Khanza
 * Author: Kamerad (Gemini) for Alicia
 * Date: 2025-11-25
 */

// ==========================================
// 1. KONFIGURASI & KONEKSI
// ==========================================
$db_host = '192.168.1.2';
$db_user = 'client';
$db_pass = 'epotoransu';
$db_name = 'sik_master';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $koneksi = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $koneksi->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Gagal terkoneksi ke database SIMRS: " . $e->getMessage());
}

// Ambil Data Instansi untuk Header
$q_instansi = $koneksi->query("SELECT nama_instansi, logo FROM setting LIMIT 1");
$data_instansi = $q_instansi->fetch_assoc();
$nama_rs = $data_instansi['nama_instansi'] ?? 'Rumah Sakit Khanza';
$logo_base64 = base64_encode($data_instansi['logo']);
$logo_src = "data:image/jpeg;base64," . $logo_base64;

// ==========================================
// 2. DEFINISI PETA DATA ERM (MAPPING)
// ==========================================
// Array ini mengatur urutan, grouping, dan tabel sumber data
// Format: 'Label Kolom' => ['tabel' => 'nama_tabel_db', 'grup' => 'Nama Grup', 'tipe' => 'Ralan/Ranap/All']

$erm_map = [
    // --- GRUP 1: IGD & TRIASE ---
    'Triase IGD' => ['tabel' => 'data_triase_igd', 'grup' => 'IGD', 'tipe' => 'All'],
    'Asesmen Awal IGD (Medis)' => ['tabel' => 'penilaian_medis_igd', 'grup' => 'IGD', 'tipe' => 'All'],
    'Asesmen Awal IGD (Kep)' => ['tabel' => 'penilaian_awal_keperawatan_igd', 'grup' => 'IGD', 'tipe' => 'All'],
    'Catatan Observasi IGD' => ['tabel' => 'catatan_observasi_igd', 'grup' => 'IGD', 'tipe' => 'All'],

    // --- GRUP 2: ASESMEN AWAL MEDIS (RALAN) ---
    'Asesmen Medis Ralan (Umum)' => ['tabel' => 'penilaian_medis_ralan', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Anak' => ['tabel' => 'penilaian_medis_ralan_anak', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Kandungan' => ['tabel' => 'penilaian_medis_ralan_kandungan', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Penyakit Dalam' => ['tabel' => 'penilaian_medis_ralan_penyakit_dalam', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Mata' => ['tabel' => 'penilaian_medis_ralan_mata', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis THT' => ['tabel' => 'penilaian_medis_ralan_tht', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Bedah' => ['tabel' => 'penilaian_medis_ralan_bedah', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Orthopedi' => ['tabel' => 'penilaian_medis_ralan_orthopedi', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Saraf' => ['tabel' => 'penilaian_medis_ralan_neurologi', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Jiwa' => ['tabel' => 'penilaian_medis_ralan_psikiatrik', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Kulit' => ['tabel' => 'penilaian_medis_ralan_kulitdankelamin', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Geriatri' => ['tabel' => 'penilaian_medis_ralan_geriatri', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Rehab Medik' => ['tabel' => 'penilaian_medis_ralan_rehab_medik', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],

    // --- GRUP 3: ASESMEN AWAL KEPERAWATAN (RALAN) ---
    'Asesmen Kep Ralan (Umum)' => ['tabel' => 'penilaian_awal_keperawatan_ralan', 'grup' => 'Asesmen Awal Keperawatan', 'tipe' => 'Ralan'],
    'Asesmen Kep Bayi/Anak' => ['tabel' => 'penilaian_awal_keperawatan_ralan_bayi', 'grup' => 'Asesmen Awal Keperawatan', 'tipe' => 'Ralan'],
    'Asesmen Kep Gigi' => ['tabel' => 'penilaian_awal_keperawatan_gigi', 'grup' => 'Asesmen Awal Keperawatan', 'tipe' => 'Ralan'],
    'Asesmen Kep Kebidanan' => ['tabel' => 'penilaian_awal_keperawatan_kebidanan', 'grup' => 'Asesmen Awal Keperawatan', 'tipe' => 'Ralan'],
    'Asesmen Kep Mata' => ['tabel' => 'penilaian_awal_keperawatan_mata', 'grup' => 'Asesmen Awal Keperawatan', 'tipe' => 'Ralan'],
    'Asesmen Kep Psikiatri' => ['tabel' => 'penilaian_awal_keperawatan_ralan_psikiatri', 'grup' => 'Asesmen Awal Keperawatan', 'tipe' => 'Ralan'],
    'Asesmen Kep Geriatri' => ['tabel' => 'penilaian_awal_keperawatan_ralan_geriatri', 'grup' => 'Asesmen Awal Keperawatan', 'tipe' => 'Ralan'],

    // --- GRUP 4: ASESMEN AWAL RANAP (Masuk Perawatan) ---
    'Transfer Antar Ruang' => ['tabel' => 'transfer_pasien_antar_ruang', 'grup' => 'Asesmen Ranap', 'tipe' => 'Ranap'],
    'Asesmen Awal Medis Ranap' => ['tabel' => 'penilaian_medis_ranap', 'grup' => 'Asesmen Ranap', 'tipe' => 'Ranap'],
    'Asesmen Awal Medis Kandungan' => ['tabel' => 'penilaian_medis_ranap_kandungan', 'grup' => 'Asesmen Ranap', 'tipe' => 'Ranap'],
    'Asesmen Awal Medis Neonatus' => ['tabel' => 'penilaian_medis_ranap_neonatus', 'grup' => 'Asesmen Ranap', 'tipe' => 'Ranap'],
    'Asesmen Awal Kep Ranap' => ['tabel' => 'penilaian_awal_keperawatan_ranap', 'grup' => 'Asesmen Ranap', 'tipe' => 'Ranap'],
    'Asesmen Awal Kebidanan Ranap' => ['tabel' => 'penilaian_awal_keperawatan_kebidanan_ranap', 'grup' => 'Asesmen Ranap', 'tipe' => 'Ranap'],
    'Asesmen Awal Neonatus Ranap' => ['tabel' => 'penilaian_awal_keperawatan_ranap_neonatus', 'grup' => 'Asesmen Ranap', 'tipe' => 'Ranap'],

    // --- GRUP 5: CPPT & CATATAN PERKEMBANGAN ---
    'CPPT Ralan' => ['tabel' => 'pemeriksaan_ralan', 'grup' => 'CPPT & SOAP', 'tipe' => 'Ralan'],
    'CPPT Ranap' => ['tabel' => 'pemeriksaan_ranap', 'grup' => 'CPPT & SOAP', 'tipe' => 'Ranap'],
    'Catatan Keperawatan Ranap' => ['tabel' => 'catatan_keperawatan_ranap', 'grup' => 'CPPT & SOAP', 'tipe' => 'Ranap'],
    'Grafik Harian / Observasi' => ['tabel' => 'catatan_observasi_ranap', 'grup' => 'CPPT & SOAP', 'tipe' => 'Ranap'],

    // --- GRUP 6: PENUNJANG (Resep, Lab, Rad) ---
    'Resep Dokter' => ['tabel' => 'resep_obat', 'grup' => 'Penunjang', 'tipe' => 'All'],
    'Permintaan Lab' => ['tabel' => 'permintaan_lab', 'grup' => 'Penunjang', 'tipe' => 'All'],
    'Permintaan Radiologi' => ['tabel' => 'permintaan_radiologi', 'grup' => 'Penunjang', 'tipe' => 'All'],
    'Diagnosa (ICD10)' => ['tabel' => 'diagnosa_pasien', 'grup' => 'Penunjang', 'tipe' => 'All'],
    'Prosedur (ICD9)' => ['tabel' => 'prosedur_pasien', 'grup' => 'Penunjang', 'tipe' => 'All'],

    // --- GRUP 7: OPERASI & ANESTESI ---
    'Penilaian Pre-Operasi' => ['tabel' => 'penilaian_pre_operasi', 'grup' => 'Operasi', 'tipe' => 'All'],
    'Penilaian Pre-Anestesi' => ['tabel' => 'penilaian_pre_anestesi', 'grup' => 'Operasi', 'tipe' => 'All'],
    'Sign In (Sebelum Anestesi)' => ['tabel' => 'signin_sebelum_anestesi', 'grup' => 'Operasi', 'tipe' => 'All'],
    'Time Out (Sebelum Insisi)' => ['tabel' => 'timeout_sebelum_insisi', 'grup' => 'Operasi', 'tipe' => 'All'],
    'Sign Out (Menutup Luka)' => ['tabel' => 'signout_sebelum_menutup_luka', 'grup' => 'Operasi', 'tipe' => 'All'],
    'Laporan Operasi' => ['tabel' => 'laporan_operasi', 'grup' => 'Operasi', 'tipe' => 'All'],

    // --- GRUP 8: MONITORING & RESIKO (SKP) ---
    'Penilaian Ulang Nyeri' => ['tabel' => 'penilaian_ulang_nyeri', 'grup' => 'Monitoring & Risiko', 'tipe' => 'All'],
    'Risiko Dekubitus' => ['tabel' => 'penilaian_risiko_dekubitus', 'grup' => 'Monitoring & Risiko', 'tipe' => 'Ranap'],
    'Risiko Jatuh Dewasa' => ['tabel' => 'penilaian_lanjutan_resiko_jatuh_dewasa', 'grup' => 'Monitoring & Risiko', 'tipe' => 'Ranap'],
    'Risiko Jatuh Anak' => ['tabel' => 'penilaian_lanjutan_resiko_jatuh_anak', 'grup' => 'Monitoring & Risiko', 'tipe' => 'Ranap'],
    'Risiko Jatuh Lansia' => ['tabel' => 'penilaian_lanjutan_resiko_jatuh_lansia', 'grup' => 'Monitoring & Risiko', 'tipe' => 'Ranap'],
    'EWS Neonatus' => ['tabel' => 'pemantauan_ews_neonatus', 'grup' => 'Monitoring & Risiko', 'tipe' => 'Ranap'],
    'MEOWS Obstetri' => ['tabel' => 'pemantauan_meows_obstetri', 'grup' => 'Monitoring & Risiko', 'tipe' => 'Ranap'],
    'PEWS Anak' => ['tabel' => 'pemantauan_pews_anak', 'grup' => 'Monitoring & Risiko', 'tipe' => 'Ranap'],
    'NEWS Dewasa' => ['tabel' => 'pemantauan_pews_dewasa', 'grup' => 'Monitoring & Risiko', 'tipe' => 'Ranap'],

    // --- GRUP 9: GIZI ---
    'Skrining Gizi' => ['tabel' => 'skrining_gizi', 'grup' => 'Gizi', 'tipe' => 'All'],
    'Asuhan Gizi (ADIME)' => ['tabel' => 'catatan_adime_gizi', 'grup' => 'Gizi', 'tipe' => 'Ranap'],

    // --- GRUP 10: PULANG & RESUME ---
    'Perencanaan Pemulangan' => ['tabel' => 'perencanaan_pemulangan', 'grup' => 'Resume & Pulang', 'tipe' => 'Ranap'],
    'Resume Pasien (Ralan)' => ['tabel' => 'resume_pasien', 'grup' => 'Resume & Pulang', 'tipe' => 'Ralan'],
    'Resume Pasien (Ranap)' => ['tabel' => 'resume_pasien_ranap', 'grup' => 'Resume & Pulang', 'tipe' => 'Ranap'],
];

// ==========================================
// 3. LOGIKA HANDLER (POST Request)
// ==========================================
$tgl_awal = date('Y-m-d');
$tgl_akhir = date('Y-m-d');
$status_lanjut = 'Semua';
$selected_cols = array_keys($erm_map); // Default semua terpilih

if (isset($_POST['cari'])) {
    $tgl_awal = $_POST['tanggal_awal'];
    $tgl_akhir = $_POST['tanggal_akhir'];
    $status_lanjut = $_POST['status_lanjut'];
    
    // Jika user memilih kolom dari modal
    if (isset($_POST['cols']) && is_array($_POST['cols'])) {
        $selected_cols = $_POST['cols'];
    }
}

// Fungsi Helper
if (!function_exists('format_audit')) {
    function format_audit($val) {
        if ($val === 'Tidak Ada') {
            return "<span class='badge bg-danger text-uppercase' style='font-size:0.7rem; letter-spacing:1px;'>KOSONG</span>";
        } else {
            return "<i class='fas fa-check-circle text-success' style='font-size:1.1rem;'></i>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Kepatuhan ERM - <?php echo $nama_rs; ?></title>
    <link rel="icon" type="image/png" href="<?php echo $logo_src; ?>">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 0.85rem; }
        .header-rs { background: #fff; padding: 15px 20px; border-bottom: 3px solid #dc3545; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .rs-logo { height: 50px; margin-right: 15px; }
        .filter-box { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; }
        
        /* Table Styles */
        table.dataTable thead th { background-color: #343a40; color: #fff; vertical-align: middle; text-align: center; white-space: nowrap; font-size: 0.8rem; }
        table.dataTable tbody td { vertical-align: middle; padding: 5px !important; font-size: 0.8rem; }
        .fixed-col { background-color: #e9ecef !important; font-weight: bold; }
        
        /* Modal Checkbox Styles */
        .group-header { background-color: #e9ecef; padding: 8px 10px; font-weight: bold; border-radius: 4px; margin-top: 10px; margin-bottom: 5px; }
        .check-item { margin-bottom: 5px; }
        .check-item label { font-size: 0.85rem; cursor: pointer; }
    </style>
</head>
<body>

<div class="header-rs">
    <div class="d-flex align-items-center">
        <img src="<?php echo $logo_src; ?>" alt="Logo" class="rs-logo">
        <div>
            <h4 class="m-0 fw-bold"><?php echo $nama_rs; ?></h4>
            <small class="text-muted">Audit Kepatuhan & Kelengkapan Rekam Medis Elektronik</small>
        </div>
    </div>
    <div class="text-end">
        <h5 class="m-0 text-danger fw-bold">AUDIT LOG</h5>
        <small><?php echo date('d F Y'); ?></small>
    </div>
</div>

<div class="container-fluid px-4">
    <form method="POST" action="">
        
        <div class="filter-box">
            <div class="row align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-bold">Tanggal Awal</label>
                    <input type="date" name="tanggal_awal" class="form-control form-control-sm" value="<?php echo $tgl_awal; ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" class="form-control form-control-sm" value="<?php echo $tgl_akhir; ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Status Pelayanan</label>
                    <select name="status_lanjut" class="form-select form-select-sm">
                        <option value="Semua" <?php echo ($status_lanjut == 'Semua') ? 'selected' : ''; ?>>Semua (Ralan & Ranap)</option>
                        <option value="Ralan" <?php echo ($status_lanjut == 'Ralan') ? 'selected' : ''; ?>>Rawat Jalan Saja</option>
                        <option value="Ranap" <?php echo ($status_lanjut == 'Ranap') ? 'selected' : ''; ?>>Rawat Inap Saja</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Kolom Audit</label>
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#columnModal">
                        <i class="fas fa-list-check me-1"></i> Pilih Kolom
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="cari" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i> Tampilkan Data
                    </button>
                </div>
            </div>
        </div>

        <div class="modal fade" id="columnModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title"><i class="fas fa-tasks me-2"></i>Konfigurasi Kolom Audit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3 sticky-top bg-white py-2 border-bottom">
                            <div class="col-md-6">
                                <input type="text" id="searchCol" class="form-control form-control-sm" placeholder="Cari nama formulir/asesmen...">
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="button" class="btn btn-sm btn-success" onclick="checkAll(true)">Pilih Semua</button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="checkAll(false)">Hapus Semua</button>
                            </div>
                        </div>
                        
                        <div class="row" id="checkboxList">
                            <?php
                            // Mengelompokkan array berdasarkan Grup
                            $grouped_map = [];
                            foreach ($erm_map as $key => $val) {
                                $grouped_map[$val['grup']][$key] = $val;
                            }

                            foreach ($grouped_map as $grup => $items) {
                                echo "<div class='col-12 group-header'>$grup</div>";
                                foreach ($items as $label => $val) {
                                    $checked = in_array($label, $selected_cols) ? 'checked' : '';
                                    // Filter checkbox display based on selected status (optional UX improvement)
                                    // But to correspond with POST logic, we render all, user chooses.
                                    echo "
                                    <div class='col-md-3 col-sm-6 check-item'>
                                        <div class='form-check'>
                                            <input class='form-check-input col-checkbox' type='checkbox' name='cols[]' value='$label' id='chk_$label' $checked>
                                            <label class='form-check-label' for='chk_$label'>$label</label>
                                        </div>
                                    </div>";
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Simpan Pilihan</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-2">
            <div class="table-responsive">
                <table id="tableAudit" class="table table-bordered table-hover w-100">
                    <thead>
                        <tr>
                            <th class="fixed-col">No. Rawat</th>
                            <th class="fixed-col">Tgl Reg</th>
                            <th class="fixed-col">No. RM</th>
                            <th class="fixed-col">Pasien</th>
                            <th>Dokter</th>
                            <th>Poli/Bangsal</th>
                            <th>Status</th>
                            
                            <?php foreach ($selected_cols as $col): ?>
                                <th class="text-center"><?php echo $col; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if (isset($_POST['cari'])) {
                        
                        // 1. Base Query (Reg Periksa)
                        $sql = "SELECT 
                                    rp.no_rawat, rp.tgl_registrasi, rp.no_rkm_medis, rp.status_lanjut,
                                    p.nm_pasien, d.nm_dokter, 
                                    IF(rp.status_lanjut='Ralan', poli.nm_poli, b.nm_bangsal) as unit
                                FROM reg_periksa rp
                                JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                                JOIN dokter d ON rp.kd_dokter = d.kd_dokter
                                LEFT JOIN poliklinik poli ON rp.kd_poli = poli.kd_poli
                                LEFT JOIN kamar_inap ki ON rp.no_rawat = ki.no_rawat
                                LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
                                LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
                                WHERE rp.tgl_registrasi BETWEEN ? AND ?
                                AND rp.stts <> 'Batal' ";
                        
                        // Filter Status Lanjut
                        if ($status_lanjut != 'Semua') {
                            $sql .= " AND rp.status_lanjut = '$status_lanjut' ";
                        }

                        // Grouping untuk Ranap agar tidak duplikat baris jika pindah kamar
                        $sql .= " GROUP BY rp.no_rawat ORDER BY rp.tgl_registrasi ASC, rp.jam_reg ASC";

                        $stmt = $koneksi->prepare($sql);
                        $stmt->bind_param("ss", $tgl_awal, $tgl_akhir);
                        $stmt->execute();
                        $result_main = $stmt->get_result();
                        
                        // Pre-Fetch IDs to avoid N+1 Query problem? 
                        // Khanza tables are usually 1-to-1 or 1-to-many on no_rawat. 
                        // For simplicity in PHP Standalone without ORM, we will do optimized checking loop.
                        
                        while ($row = $result_main->fetch_assoc()) {
                            $no_rawat = $row['no_rawat'];
                            
                            echo "<tr>";
                            echo "<td class='fw-bold'>$no_rawat</td>";
                            echo "<td>{$row['tgl_registrasi']}</td>";
                            echo "<td>{$row['no_rkm_medis']}</td>";
                            echo "<td>{$row['nm_pasien']}</td>";
                            echo "<td>{$row['nm_dokter']}</td>";
                            echo "<td>{$row['unit']}</td>";
                            echo "<td><span class='badge bg-info'>{$row['status_lanjut']}</span></td>";
                            
                            // Loop Dinamis Kolom Terpilih
                            foreach ($selected_cols as $col_label) {
                                $config = $erm_map[$col_label];
                                $table_name = $config['tabel'];
                                
                                // Cek apakah kolom ini relevan dengan status pasien saat ini?
                                // Misal: Pasien Ralan tidak perlu dicek "Risiko Jatuh Dewasa" (Ranap)
                                // Kecuali user memaksa pilih "Semua". Kita ikuti data apa adanya.
                                
                                // Optimized Check: Simple EXISTS query per cell is cleanest code 
                                // though not most performant for 10k rows. Efficient enough for < 500 rows.
                                
                                $check_sql = "SELECT 1 FROM $table_name WHERE no_rawat = '$no_rawat' LIMIT 1";
                                // Resep obat linknya no_resep -> no_rawat, tapi ada tabel bridging. 
                                // Di Khanza, resep_obat punya kolom no_rawat langsung. Aman.
                                
                                $check_res = $koneksi->query($check_sql);
                                $status_isi = ($check_res->num_rows > 0) ? 'Ada' : 'Tidak Ada';
                                
                                // Override Logic Khusus jika diperlukan (misal Resep harus ada tgl_peresepan)
                                if ($table_name == 'resep_obat' && $status_isi == 'Ada') {
                                     // cek additional logic if needed
                                }
                                
                                echo "<td class='text-center'>" . format_audit($status_isi) . "</td>";
                            }
                            echo "</tr>";
                        }
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    // Inisialisasi DataTable
    $('#tableAudit').DataTable({
        "scrollX": true,
        "scrollY": "60vh",
        "scrollCollapse": true,
        "paging": false, // Menampilkan semua data dalam satu halaman scroll
        "fixedColumns": {
            left: 1 // Fix kolom No Rawat
        },
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excel', className: 'btn btn-success btn-sm', text: '<i class="fas fa-file-excel"></i> Excel' },
            { extend: 'print', className: 'btn btn-secondary btn-sm', text: '<i class="fas fa-print"></i> Print' }
        ],
        language: { url: "//cdn.datatables.net/plug-ins/1.10.21/i18n/Indonesian.json" }
    });

    // Fungsi Pencarian Checkbox di Modal
    $("#searchCol").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $(".check-item").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
        // Toggke group header jika semua anaknya hidden
        $(".group-header").each(function() {
            var groupVisible = $(this).nextUntil(".group-header", ".check-item:visible").length > 0;
            $(this).toggle(groupVisible);
        });
    });
});

// Fungsi Pilih Semua / Hapus Semua
function checkAll(status) {
    $('.col-checkbox').prop('checked', status);
}
</script>

</body>
</html>