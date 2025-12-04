<?php
include 'conf.php';
header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

// --- 1. DATATABLES SERVER-SIDE (LIST OBAT) ---
if ($action == 'load_table') {
    // Kolom yang bisa dicari/diurutkan
    $columns = ['d.kode_brng', 'd.nama_brng', 'm.obat_code', 'm.obat_display', 'm.route_code', 'm.denominator_code'];
    
    // Base Query
    $sql = "SELECT d.kode_brng, d.nama_brng, 
            m.obat_code, m.obat_display, 
            m.form_code, m.form_display,
            m.route_code, m.route_display,
            m.denominator_code, m.denominator_system
            FROM databarang d 
            LEFT JOIN satu_sehat_mapping_obat m ON d.kode_brng = m.kode_brng";

    // Pencarian
    if (!empty($_GET['search']['value'])) {
        $search = $_GET['search']['value'];
        $sql .= " WHERE d.nama_brng LIKE '%$search%' OR d.kode_brng LIKE '%$search%' OR m.obat_code LIKE '%$search%'";
    }

    // Sorting & Limit (Sederhana)
    $sql .= " LIMIT 100"; // Batasi 100 agar ringan (paging server-side asli butuh logic lebih kompleks)

    $stmt = $pdo->query($sql);
    $data = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Status Badge
        $status = !empty($row['obat_code']) 
            ? '<span class="badge bg-success"><i class="fa fa-check"></i> Mapped</span>' 
            : '<span class="badge bg-danger">Belum</span>';

        // Tombol Action
        $btn = "<button class='btn btn-sm btn-primary btn-map' 
                data-json='".json_encode($row, JSON_HEX_APOS)."'>
                <i class='fa fa-edit'></i> Mapping</button>";

        $data[] = [
            $row['kode_brng'],
            $row['nama_brng'],
            $row['obat_code'] . '<br><small class="text-muted">'.$row['obat_display'].'</small>',
            $row['route_code'] . ' / ' . $row['denominator_code'],
            $status,
            $btn
        ];
    }

    echo json_encode(["data" => $data]);
    exit;
}

// --- 2. SELECT2 SEARCH KFA (CARIAN KODE KEMENKES) ---
if ($action == 'search_kfa') {
    $q = isset($_GET['term']) ? $_GET['term'] : '';
    
    // Cari di tabel referensi KFA (lokal)
    // Jika tabel kosong, user harus input manual atau import data dulu
    $sql = "SELECT kfa_code as id, CONCAT(kfa_code, ' - ', display_name) as text, display_name 
            FROM satu_sehat_ref_kfa 
            WHERE display_name LIKE :q OR kfa_code LIKE :q LIMIT 20";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':q' => "%$q%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['results' => $results]);
    exit;
}
?>