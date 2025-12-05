<?php
// Matikan error reporting agar output bersih untuk JSON
error_reporting(0);
ini_set('display_errors', 0);

include 'conf.php';
header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    // --- 1. DATATABLES SERVER-SIDE (LIST OBAT) ---
    if ($action == 'load_table') {
        // Parameter Pencarian dari Input Khusus
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

        // Base Query
        $sql = "SELECT d.kode_brng, d.nama_brng, 
                m.obat_code, m.obat_display, 
                m.form_code, m.form_display,
                m.route_code, m.route_display,
                m.denominator_code, m.denominator_system
                FROM databarang d 
                LEFT JOIN satu_sehat_mapping_obat m ON d.kode_brng = m.kode_brng";

        $params = [];

        // Filter 1: Pencarian Utama (Server Side)
        if (!empty($keyword)) {
            $sql .= " WHERE (d.nama_brng LIKE :k OR d.kode_brng LIKE :k)";
            $params[':k'] = "%$keyword%";
        }

        // Filter 2: Pencarian Bawaan Datatables (Client side filtering support)
        if (!empty($_GET['search']['value'])) {
            $search = $_GET['search']['value'];
            $sql .= empty($keyword) ? " WHERE " : " AND ";
            $sql .= "(d.nama_brng LIKE :s OR m.obat_code LIKE :s)";
            $params[':s'] = "%$search%";
        }

        // Sorting Default
        $sql .= " ORDER BY d.nama_brng ASC";

        // Logic LIMIT: Hanya batasi jika TIDAK ada pencarian spesifik
        if (empty($keyword) && empty($_GET['search']['value'])) {
            $sql .= " LIMIT 100"; 
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Status Badge
            $status = !empty($row['obat_code']) 
                ? '<span class="badge bg-success"><i class="fa fa-check"></i> Mapped</span>' 
                : '<span class="badge bg-danger">Belum</span>';

            // Info Mapping untuk ditampilkan di tabel
            $info_map = "";
            if(!empty($row['obat_code'])) {
                $info_map = "<b>KFA:</b> ".$row['obat_code']." <br> 
                             <small class='text-muted'>".$row['obat_display']."</small><br>
                             <b>Route:</b> ".$row['route_display']." (".$row['route_code'].") | 
                             <b>Unit:</b> ".$row['denominator_code'];
            } else {
                $info_map = "<small class='text-muted'>- Belum dimapping -</small>";
            }

            // Tombol Action
            // Gunakan htmlspecialchars pada nama barang untuk mencegah error JSON akibat karakter kutip
            $nama_obat_safe = htmlspecialchars($row['nama_brng'], ENT_QUOTES, 'UTF-8');
            $row['nama_brng'] = $nama_obat_safe;

            $btn = "<button class='btn btn-sm btn-primary btn-map' 
                    data-json='".json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT)."'>
                    <i class='fa fa-edit'></i> Mapping</button>";

            $data[] = [
                $row['kode_brng'],
                $nama_obat_safe,
                $info_map,
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
        
        // Pastikan tabel referensi ada dan terisi
        $sql = "SELECT kfa_code as id, CONCAT(kfa_code, ' - ', display_name) as text, display_name 
                FROM satu_sehat_ref_kfa 
                WHERE display_name LIKE :q OR kfa_code LIKE :q LIMIT 20";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':q' => "%$q%"]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['results' => $results]);
        exit;
    }

} catch (Exception $e) {
    // Tangkap error agar tidak merusak response JSON table
    echo json_encode(["data" => [], "error" => $e->getMessage()]);
}
?>