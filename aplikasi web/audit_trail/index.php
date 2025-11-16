<?php
// --- 1. KONFIGURASI KONEKSI DATABASE ---
$db_host = '192.168.1.5';
$db_user = 'client';
$db_pass = 'epotoransu';
$db_name = 'sik_master';

// --- 2. INISIALISASI VARIABEL ---
$data_audit = []; // Inisialisasi array data, default kosong
$pesan_error = '';  // Penampung pesan error

// Variabel untuk data setting (Default jika DB gagal)
$nama_instansi = 'Audit Trail SQL';
$logo_base64 = '';
$favicon_base64 = '';

// --- 3. KONEKSI & AMBIL DATA SETTING (WAJIB UNTUK HEADER & LOGO) ---
$koneksi = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Cek koneksi
if ($koneksi->connect_error) {
    $pesan_error = "Koneksi database gagal: " . $koneksi->connect_error;
} else {
    // Koneksi berhasil, ambil data setting
    // Diasumsikan tabel setting hanya punya 1 baris
    $sql_setting = "SELECT nama_instansi, logo FROM setting LIMIT 1";
    $result_setting = $koneksi->query($sql_setting);

    if ($result_setting && $result_setting->num_rows > 0) {
        $row_setting = $result_setting->fetch_assoc();
        $nama_instansi = $row_setting['nama_instansi'];
        
        // Encode BLOB ke Base64 untuk ditampilkan di HTML
        // Kita asumsikan formatnya PNG, jika JPEG ganti "image/png"
        if (!empty($row_setting['logo'])) {
            $logo_base64 = base64_encode($row_setting['logo']);
            $favicon_base64 = $logo_base64; // Gunakan logo yang sama untuk favicon
        }
    } else {
        $pesan_error = "Koneksi DB berhasil, tetapi gagal mengambil data 'setting'.";
    }
}


// --- 4. LOGIKA FILTER (SERVER-SIDE) ---
// Tetapkan nilai default atau ambil dari GET (untuk mengisi ulang form)
$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-d', strtotime('-30 days'));
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
$filter1 = $_GET['filter1'] ?? '';
$filter2 = $_GET['filter2'] ?? '';
$filter3 = $_GET['filter3'] ?? '';
$filter4 = $_GET['filter4'] ?? '';
$filter_not = $_GET['filter_not'] ?? '';


// --- 5. EKSEKUSI QUERY FILTER (HANYA JIKA FORM DI-SUBMIT) ---
// Periksa apakah form telah di-submit DAN koneksi awal berhasil
if (isset($_GET['filter_submit']) && !$koneksi->connect_error) {

    // 2. Membangun Query
    $sql_base = "SELECT trackersql.tanggal, trackersql.sqle, pegawai.nama
                 FROM trackersql
                 LEFT JOIN pegawai ON trackersql.usere = pegawai.nik";

    $where_clauses = [];
    $params = [];
    $types = "";

    // Filter Tanggal (Wajib ada)
    $where_clauses[] = "trackersql.tanggal BETWEEN ? AND ?";
    $params[] = $tgl_awal . ' 00:00:00';
    $params[] = $tgl_akhir . ' 23:59:59';
    $types .= "ss";

    // Filter Dinamis (hanya jika diisi)
    if (!empty($filter1)) {
        $where_clauses[] = "trackersql.sqle LIKE ?";
        $params[] = "%{$filter1}%";
        $types .= "s";
    }
    if (!empty($filter2)) {
        $where_clauses[] = "trackersql.sqle LIKE ?";
        $params[] = "%{$filter2}%";
        $types .= "s";
    }
    if (!empty($filter3)) {
        $where_clauses[] = "trackersql.sqle LIKE ?";
        $params[] = "%{$filter3}%";
        $types .= "s";
    }
    if (!empty($filter4)) {
        $where_clauses[] = "trackersql.sqle LIKE ?";
        $params[] = "%{$filter4}%";
        $types .= "s";
    }
    if (!empty($filter_not)) {
        $where_clauses[] = "trackersql.sqle NOT LIKE ?";
        $params[] = "%{$filter_not}%";
        $types .= "s";
    }

    $sql_query = $sql_base . " WHERE " . implode(" AND ", $where_clauses) . " ORDER BY trackersql.tanggal DESC";

    // 3. Eksekusi Prepared Statement
    $stmt = $koneksi->prepare($sql_query);

    if ($stmt === false) {
        $pesan_error = "Gagal mempersiapkan query filter: " . $koneksi->error;
    } else {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data_audit[] = $row;
        }
        $stmt->close();
    }
} // End check 'filter_submit'

// --- 6. TUTUP KONEKSI DATABASE ---
// Tutup koneksi setelah semua query selesai
if (isset($koneksi) && !$koneksi->connect_error) {
    $koneksi->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - <?php echo htmlspecialchars($nama_instansi); ?></title>

    <!-- CSS Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- CSS DataTables & Ekstensi Buttons (untuk Bootstrap 5) -->
    <link href="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.0.8/b-3.0.2/b-html5-3.0.2/b-print-3.0.2/datatables.min.css" rel="stylesheet">

    <!-- FAVICON (Baru) -->
    <?php if (!empty($favicon_base64)) : ?>
        <!-- Menggunakan data URI dari BLOB. Asumsi format image/png -->
        <link rel="icon" type="image/png" href="data:image/png;base64,<?php echo $favicon_base64; ?>">
    <?php endif; ?>
    <!-- Selesai Favicon -->

    <style>
        .dt-buttons .btn { margin-right: 5px; }
        .header-logo {
            height: 50px;
            width: 50px;
            object-fit: contain;
            margin-right: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            background-color: #fff;
        }
    </style>
</head>
<body>

    <div class="container-fluid mt-4 mb-4">

        <!-- HEADER (Baru) -->
        <div class="d-flex align-items-center mb-4 p-3 bg-white rounded shadow-sm">
            <?php if (!empty($logo_base64)) : ?>
                <!-- Menggunakan data URI dari BLOB. Asumsi format image/png -->
                <img src="data:image/png;base64,<?php echo $logo_base64; ?>" alt="Logo Instansi" class="header-logo">
            <?php endif; ?>
            <div>
                <h2 class="mb-0 text-primary"><?php echo htmlspecialchars($nama_instansi); ?></h2>
                <h5 class="text-muted fw-normal mb-0">Laporan Audit Trail SQL</h5>
            </div>
        </div>
        <!-- Selesai Header -->


        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Filter Audit Trail</h5>
            </div>
            <div class="card-body">
                <!-- Form Filter -->
                <form action="" method="GET">
                    <div class="row g-3">
                        <!-- Filter Tanggal -->
                        <div class="col-md-3">
                            <label for="tgl_awal" class="form-label">Tanggal Awal</label>
                            <input type="date" class="form-control" id="tgl_awal" name="tgl_awal" value="<?php echo htmlspecialchars($tgl_awal); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="tgl_akhir" class="form-label">Tanggal Akhir</label>
                            <input type="date" class="form-control" id="tgl_akhir" name="tgl_akhir" value="<?php echo htmlspecialchars($tgl_akhir); ?>">
                        </div>

                        <!-- Filter Teks (LIKE) - Label Diubah -->
                        <div class="col-md-3">
                            <label for="filter1" class="form-label">Keyword Pencarian 1</label>
                            <input type="text" class="form-control" id="filter1" name="filter1" placeholder="cth: INSERT" value="<?php echo htmlspecialchars($filter1); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="filter2" class="form-label">Keyword Pencarian 2</label>
                            <input type="text" class="form-control" id="filter2" name="filter2" placeholder="cth: pasien" value="<?php echo htmlspecialchars($filter2); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="filter3" class="form-label">Keyword Pencarian 3</label>
                            <input type="text" class="form-control" id="filter3" name="filter3" placeholder="cth: U0001" value="<?php echo htmlspecialchars($filter3); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="filter4" class="form-label">Keyword Pencarian 4</label>
                            <input type="text" class="form-control" id="filter4" name="filter4" placeholder="cth: 2024/10" value="<?php echo htmlspecialchars($filter4); ?>">
                        </div>
                        
                        <!-- Filter Teks (NOT LIKE) - Label Diubah -->
                        <div class="col-md-3">
                            <label for="filter_not" class="form-label fw-bold text-danger">Keyword Pencarian TIDAK Mengandung Kata</label>
                            <input type="text" class="form-control is-invalid" id="filter_not" name="filter_not" placeholder="cth: SELECT" value="<?php echo htmlspecialchars($filter_not); ?>">
                        </div>

                        <!-- Tombol Submit - Tambahkan name="filter_submit" -->
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" name="filter_submit" class="btn btn-primary w-100">Filter Data</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mt-4">
            <div class="card-header">
                <h5 class="mb-0">Hasil Audit Trail</h5>
            </div>
            <div class="card-body">
                
                <!-- Tampilkan pesan error jika ada -->
                <?php if (!empty($pesan_error)) : ?>
                    <div class="alert alert-danger">
                        <strong>Error:</strong> <?php echo htmlspecialchars($pesan_error); ?>
                    </div>
                <?php endif; ?>

                <!-- Tabel Hasil -->
                <div class="table-responsive">
                    <table id="tabelAudit" class="table table-striped table-bordered table-hover" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal & Waktu</th>
                                <th style="width: 60%;">Detail SQL Query</th>
                                <th>Nama Pegawai (User)</th>
                            </tr>
                        </thead>
                        <tbody class="table-group-divider">
                            <?php
                            // HANYA loop jika ada data. 
                            // Jika tidak, <tbody> akan sengaja dibiarkan kosong
                            // agar DataTables yang menanganinya (solusi error tn/4).
                            if (!empty($data_audit)) :
                                foreach ($data_audit as $data) :
                            ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($data['tanggal']); ?></td>
                                        <td><small><?php echo htmlspecialchars($data['sqle']); ?></small></td>
                                        <td><?php echo htmlspecialchars($data['nama']); ?></td>
                                    </tr>
                            <?php
                                endforeach;
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pustaka JavaScript -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.0.8/b-3.0.2/b-html5-3.0.2/b-print-3.0.2/datatables.min.js"></script>

    <!-- Inisialisasi DataTables (Solusi tn/4) -->
    <script>
        $(document).ready(function() {
            
            <?php
            // Siapkan pesan yang benar berdasarkan status PHP
            $pesan_tabel_kosong = "";
            if (!isset($_GET['filter_submit'])) {
                // 1. Saat halaman baru dibuka
                $pesan_tabel_kosong = "Silakan masukkan filter dan klik \"Filter Data\" untuk memulai pencarian.";
            } elseif (empty($data_audit) && empty($pesan_error)) {
                // 2. Saat sudah filter, tapi data 0
                $pesan_tabel_kosong = "Tidak ada data yang cocok dengan filter yang dimasukkan.";
            } else {
                // 3. Default jika ada error query
                $pesan_tabel_kosong = "Data tidak dapat ditampilkan. Cek pesan error di atas.";
            }
            ?>

            $('#tabelAudit').DataTable({
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>' +
                     '<"row"<"col-sm-12 mt-2"B>>',
                
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                
                "order": [], // Biarkan PHP (server) yang mengurutkan
                
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/2.0.8/i18n/id.json",
                    // Gunakan pesan dinamis yang kita buat di PHP
                    "emptyTable": "<?php echo $pesan_tabel_kosong; ?>"
                }
            });
        });
    </script>

</body>
</html>
