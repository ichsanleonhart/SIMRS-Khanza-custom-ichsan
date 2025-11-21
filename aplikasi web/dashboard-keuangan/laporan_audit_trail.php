<?php
/*
 * File: laporan_audit_trail.php (UPDATE V3 - FIX QUERY)
 * - Fix Table: Menggunakan 'trackersql' sesuai query manual user.
 * - Fix Join: Left Join ke tabel 'pegawai' untuk ambil nama.
 * - UI: Tetap mempertahankan Advanced Multi-Filter.
 */

$page_title = "Audit Trail System";
require_once('includes/header.php');
require_once('includes/functions.php');

// 1. Inisialisasi Parameter
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// --- Helper Function untuk Membangun Query ---
function build_filter_segment($col, $op, $val, &$params, &$types) {
    if (trim($val) === '') return " 1=1 "; 

    // 1. Mapping Kolom Target (Sesuai Query Manual)
    $target_cols = [];
    if ($col == 'all') {
        $target_cols = ['p.nama', 't.usere', 't.sqle'];
    } elseif ($col == 'user') {
        $target_cols = ['p.nama', 't.usere']; // Cari di Nama atau NIK
    } elseif ($col == 'sql') {
        $target_cols = ['t.sqle'];
    } else {
        $target_cols = ['t.sqle']; // Default
    }

    // 2. Mapping Operator SQL
    $sql_op = "LIKE";
    $sql_val = "%$val%";
    
    if ($op == 'not_contains') {
        $sql_op = "NOT LIKE";
        $sql_val = "%$val%";
    } elseif ($op == 'equals') {
        $sql_op = "=";
        $sql_val = "$val";
    } elseif ($op == 'starts_with') {
        $sql_op = "LIKE";
        $sql_val = "$val%";
    }

    // 3. Konstruksi SQL Fragment
    $segments = [];
    foreach ($target_cols as $c) {
        $segments[] = "$c $sql_op ?";
        $params[] = $sql_val;
        $types .= "s";
    }

    // 4. Gabungkan
    $logic_internal = ($op == 'not_contains') ? " AND " : " OR ";
    return "(" . implode($logic_internal, $segments) . ")";
}

// 2. Main Logic Query
$data_audit = [];
if ($koneksi) {
    // Base Query dengan JOIN (Sesuai Request)
    $sql = "
        SELECT 
            t.tanggal, 
            t.sqle, 
            t.usere, 
            p.nama as nama_pegawai
        FROM trackersql t
        LEFT JOIN pegawai p ON t.usere = p.nik
        WHERE t.tanggal BETWEEN ? AND ? 
    ";
    
    // Format tanggal harus lengkap dengan jam untuk trackersql
    $params = [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'];
    $types = "ss";

    // --- FILTER 1 (Utama) ---
    if (!empty($_GET['val1'])) {
        $cond1 = build_filter_segment($_GET['col1'], $_GET['op1'], $_GET['val1'], $params, $types);
        $sql .= " AND ( $cond1 ";

        // --- FILTER 2 (Opsional) ---
        if (!empty($_GET['val2'])) {
            $logic1 = ($_GET['logic1'] == 'OR') ? " OR " : " AND ";
            $cond2 = build_filter_segment($_GET['col2'], $_GET['op2'], $_GET['val2'], $params, $types);
            $sql .= " $logic1 $cond2 ";

            // --- FILTER 3 (Opsional) ---
            if (!empty($_GET['val3'])) {
                $logic2 = ($_GET['logic2'] == 'OR') ? " OR " : " AND ";
                $cond3 = build_filter_segment($_GET['col3'], $_GET['op3'], $_GET['val3'], $params, $types);
                $sql .= " $logic2 $cond3 ";
            }
        }
        $sql .= " ) "; 
    }

    // Limit & Order (Penting untuk tabel log yang besar)
    $sql .= " ORDER BY t.tanggal DESC LIMIT 1000";

    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        $bind_names[] = $types;
        for ($i=0; $i<count($params);$i++) { $bind_names[] = &$params[$i]; }
        call_user_func_array(array($stmt, 'bind_param'), $bind_names);
        
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data_audit[] = $row;
        }
        $stmt->close();
    } else {
        // Debugging jika query gagal
        echo "<div class='alert alert-danger'>Query Error: " . $koneksi->error . "</div>";
    }
}

// Helper array untuk Dropdown
$opt_cols = [
    'all' => 'Semua Kolom',
    'user' => 'User (Nama/NIK)',
    'sql' => 'Isi Query SQL'
];
$opt_ops = [
    'contains' => 'Mengandung (Like)',
    'not_contains' => 'TIDAK Mengandung (Not Like)',
    'equals' => 'Sama Persis (=)',
    'starts_with' => 'Dimulai dengan'
];
?>

<div class="container-fluid">
    
    <div class="alert alert-secondary border-left-secondary shadow-sm mb-4">
        <i class="fas fa-user-secret me-2"></i>
        <strong>Audit Trail (TrackerSQL):</strong> Memantau aktivitas database berdasarkan tabel <code>trackersql</code>.
    </div>

    <div class="card shadow-sm mb-4 border-left-primary">
        <div class="card-header py-2 bg-gray-100">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-search me-2"></i>Pencarian Bertingkat</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="laporan_audit_trail.php">
                
                <div class="row g-2 align-items-center mb-3 pb-3 border-bottom">
                    <div class="col-md-2">
                        <label class="small fw-bold">Dari Tanggal</label>
                        <input type="date" class="form-control form-control-sm" name="tgl_awal" value="<?php echo $tgl_awal; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Sampai Tanggal</label>
                        <input type="date" class="form-control form-control-sm" name="tgl_akhir" value="<?php echo $tgl_akhir; ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="small fw-bold text-muted d-block">&nbsp;</label>
                        <span class="badge bg-info text-dark"><i class="fas fa-info-circle"></i> Menampilkan maksimal 1000 data terbaru.</span>
                    </div>
                </div>

                <div class="row g-2 align-items-center mb-2">
                    <div class="col-md-1 text-end"><span class="badge bg-primary">Syarat 1</span></div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="col1">
                            <?php foreach($opt_cols as $k=>$v) echo "<option value='$k' ".($_GET['col1']==$k?'selected':'').">$v</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="op1">
                            <?php foreach($opt_ops as $k=>$v) echo "<option value='$k' ".($_GET['op1']==$k?'selected':'').">$v</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control form-control-sm" name="val1" value="<?php echo htmlspecialchars($_GET['val1'] ?? ''); ?>" placeholder="Kata kunci...">
                    </div>
                </div>

                <div class="row g-2 align-items-center mb-2">
                    <div class="col-md-1 text-end">
                        <select class="form-select form-select-sm bg-warning text-dark fw-bold" name="logic1">
                            <option value="AND" <?php echo ($_GET['logic1']=='AND'?'selected':''); ?>>AND</option>
                            <option value="OR" <?php echo ($_GET['logic1']=='OR'?'selected':''); ?>>OR</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="col2">
                            <?php foreach($opt_cols as $k=>$v) echo "<option value='$k' ".($_GET['col2']==$k?'selected':'').">$v</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="op2">
                            <?php foreach($opt_ops as $k=>$v) echo "<option value='$k' ".($_GET['op2']==$k?'selected':'').">$v</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control form-control-sm" name="val2" value="<?php echo htmlspecialchars($_GET['val2'] ?? ''); ?>" placeholder="Kata kunci kedua...">
                    </div>
                </div>

                <div class="row g-2 align-items-center mb-3">
                    <div class="col-md-1 text-end">
                        <select class="form-select form-select-sm bg-warning text-dark fw-bold" name="logic2">
                            <option value="AND" <?php echo ($_GET['logic2']=='AND'?'selected':''); ?>>AND</option>
                            <option value="OR" <?php echo ($_GET['logic2']=='OR'?'selected':''); ?>>OR</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="col3">
                            <?php foreach($opt_cols as $k=>$v) echo "<option value='$k' ".($_GET['col3']==$k?'selected':'').">$v</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="op3">
                            <?php foreach($opt_ops as $k=>$v) echo "<option value='$k' ".($_GET['op3']==$k?'selected':'').">$v</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control form-control-sm" name="val3" value="<?php echo htmlspecialchars($_GET['val3'] ?? ''); ?>" placeholder="Kata kunci ketiga...">
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 text-end">
                        <a href="laporan_audit_trail.php" class="btn btn-secondary btn-sm me-2"><i class="fas fa-sync"></i> Reset</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4"><i class="fas fa-search me-2"></i> Terapkan Filter</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Log Aktivitas (Trackersql)</h6>
            <span class="badge bg-secondary"><?php echo count($data_audit); ?> Records</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm text-sm" id="dataTable" width="100%" cellspacing="0">
                    <thead class="table-dark">
                        <tr>
                            <th width="15%">Waktu</th>
                            <th width="20%">User (Pegawai)</th>
                            <th>Perintah SQL (Query)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data_audit as $row): ?>
                        <tr>
                            <td class="align-middle font-monospace text-nowrap"><?php echo htmlspecialchars($row['tanggal']); ?></td>
                            <td class="align-middle">
                                <div class="fw-bold text-primary"><?php echo htmlspecialchars($row['nama_pegawai'] ?? 'Unknown'); ?></div>
                                <small class="text-muted"><i class="fas fa-id-badge me-1"></i><?php echo htmlspecialchars($row['usere']); ?></small>
                            </td>
                            <td class="align-middle">
                                <code class="d-block bg-gray-100 p-2 rounded text-dark border" style="max-height: 120px; overflow-y: auto; font-family: 'Consolas', monospace; font-size: 0.8rem;">
                                    <?php echo htmlspecialchars($row['sqle']); ?>
                                </code>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php ob_start(); ?>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "responsive": true,
            "dom": 'Bfrtip',
            "buttons": [
                { extend: 'excel', className: 'btn-sm btn-success', title: 'Audit Log Export' },
                { extend: 'print', className: 'btn-sm btn-secondary' }
            ],
            "pageLength": 20,
            "ordering": false, // Biarkan urutan dari SQL (Desc)
            "language": {
                "search": "Cari di Halaman Ini:",
                "lengthMenu": "Tampilkan _MENU_ baris"
            }
        });
    });
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>