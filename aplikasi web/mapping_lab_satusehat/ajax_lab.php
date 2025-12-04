<?php
// Matikan error reporting agar JSON bersih
error_reporting(0);
ini_set('display_errors', 0);

include 'conf.php';
header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    // --- 1. LOAD TABLE ---
    if ($action == 'load_table') {
        // Cek Tabel
        $cekTable = $pdo->query("SHOW TABLES LIKE 'satu_sehat_mapping_lab'");
        if ($cekTable->rowCount() == 0) {
            throw new Exception("Tabel 'satu_sehat_mapping_lab' belum dibuat!");
        }

        // Parameter Pencarian Server-Side (Dari Input Text di Index.php)
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

        // QUERY BASIS
        $sql = "SELECT 
                    pj.png_jawab, 
                    jp.nm_perawatan AS Nama_Paket_Lab, 
                    tl.Pemeriksaan, 
                    tl.satuan, 
                    tl.id_template AS id_template_lab, 
                    ml.code AS loinc_code, 
                    ml.display AS loinc_display, 
                    ml.sampel_code, 
                    ml.sampel_display
                FROM template_laboratorium tl
                JOIN jns_perawatan_lab jp ON tl.kd_jenis_prw = jp.kd_jenis_prw 
                JOIN penjab pj ON jp.kd_pj = pj.kd_pj
                LEFT JOIN satu_sehat_mapping_lab ml ON tl.id_template = ml.id_template
                WHERE jp.status = '1'
                  AND jp.nm_perawatan IS NOT NULL
                  AND COALESCE(NULLIF(TRIM(tl.Pemeriksaan), ''), NULL) IS NOT NULL ";

        $params = [];

        // LOGIKA FILTER UTAMA (DARI INPUT BOX)
        if (!empty($keyword)) {
            // Jika user mencari sesuatu, cari HANYA di kolom Pemeriksaan
            $sql .= " AND tl.Pemeriksaan LIKE :keyword ";
            $params[':keyword'] = "%$keyword%";
        }

        // Filter Pencarian Bawaan Datatables (Optional, untuk melengkapi)
        // Ini jika user mengetik di box 'Search' bawaan datatable yang ikut terkirim
        if (!empty($_GET['search']['value'])) {
            $searchDt = $_GET['search']['value'];
            $sql .= " AND (tl.Pemeriksaan LIKE :sdt OR jp.nm_perawatan LIKE :sdt OR ml.code LIKE :sdt) ";
            $params[':sdt'] = "%$searchDt%";
        }
        
        // ORDER BY
        $sql .= " ORDER BY tl.Pemeriksaan ASC ";

        // LOGIKA LIMIT (PENTING!)
        // Jika TIDAK ada keyword pencarian, kita limit 100 agar ringan.
        // Jika ADA keyword, kita TAMPILKAN SEMUA yang cocok (tanpa limit).
        if (empty($keyword) && empty($_GET['search']['value'])) {
            $sql .= " LIMIT 100"; 
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Status Badge
            $status = (!empty($row['loinc_code']) && !empty($row['sampel_code'])) 
                ? '<span class="badge bg-success"><i class="fa fa-check"></i> Mapped</span>' 
                : '<span class="badge bg-danger">Belum</span>';

            // Info Mapping
            $info = "";
            if ($row['loinc_code']) 
                $info .= "<span class='badge bg-primary'>LOINC</span> <b>".$row['loinc_code']."</b><br><small class='text-muted'>".substr($row['loinc_display'],0,40)."...</small><br>";
            if ($row['sampel_code']) 
                $info .= "<span class='badge bg-info text-dark'>SNOMED</span> <b>".$row['sampel_code']."</b><br><small class='text-muted'>".substr($row['sampel_display'],0,40)."...</small>";

            $pemeriksaan = htmlspecialchars($row['Pemeriksaan'], ENT_QUOTES, 'UTF-8');
            $paket = htmlspecialchars($row['Nama_Paket_Lab'], ENT_QUOTES, 'UTF-8');
            $penjab = htmlspecialchars($row['png_jawab'], ENT_QUOTES, 'UTF-8');

            // Tombol Aksi
            $btn = "<button class='btn btn-sm btn-outline-primary btn-map' 
                    data-id='".$row['id_template_lab']."'
                    data-nama='".$pemeriksaan."'
                    data-loinc='".$row['loinc_code']."'
                    data-loinc-display='".$row['loinc_display']."'
                    data-snomed='".$row['sampel_code']."'
                    data-snomed-display='".$row['sampel_display']."'>
                    <i class='fa fa-edit'></i> Mapping</button>";

            $data[] = [
                $row['id_template_lab'],
                "<b>$pemeriksaan</b><br><small class='text-muted'>$paket</small><br><span class='badge bg-secondary' style='font-size:0.6rem'>$penjab</span>",
                $info,
                $status,
                $btn
            ];
        }

        echo json_encode(["data" => $data]);
        exit;
    }

    // ... (KODE SEARCH LOINC & SNOMED TETAP SAMA, TIDAK PERLU DIUBAH) ...
    // --- 2. SEARCH LOINC ---
    if ($action == 'search_loinc') {
        $q = isset($_GET['term']) ? $_GET['term'] : '';
        $stmt = $pdo->prepare("SELECT loinc_num as id, CONCAT(loinc_num, ' - ', long_common_name) as text, long_common_name as display 
                               FROM satu_sehat_ref_loinc 
                               WHERE long_common_name LIKE :q OR loinc_num LIKE :q LIMIT 20");
        $stmt->execute([':q' => "%$q%"]);
        echo json_encode(['results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    // --- 3. SEARCH SNOMED ---
    if ($action == 'search_snomed') {
        $q = isset($_GET['term']) ? $_GET['term'] : '';
        $stmt = $pdo->prepare("SELECT conceptId as id, CONCAT(conceptId, ' - ', term) as text, term as display 
                               FROM satu_sehat_ref_snomed 
                               WHERE term LIKE :q OR conceptId LIKE :q LIMIT 20");
        $stmt->execute([':q' => "%$q%"]);
        echo json_encode(['results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }
    
    // --- 4. SAVE MAPPING (LOGIC SAMA SEPERTI SEBELUMNYA) ---
    if ($action == 'save_mapping') {
        $id_template = $_POST['id_template'];
        $loinc_code = $_POST['loinc_code'];
        $loinc_display = $_POST['loinc_display'];
        $snomed_code = $_POST['snomed_code'];
        $snomed_display = $_POST['snomed_display'];
        
        if(empty($id_template) || empty($loinc_code)) {
            throw new Exception("Data mapping tidak lengkap.");
        }

        // A. Simpan Item Utama
        $sql = "INSERT INTO satu_sehat_mapping_lab (id_template, code, system, display, sampel_code, sampel_system, sampel_display)
                VALUES (:id, :lc, 'http://loinc.org', :ld, :sc, 'http://snomed.info/sct', :sd)
                ON DUPLICATE KEY UPDATE 
                code=:lc2, display=:ld2, sampel_code=:sc2, sampel_display=:sd2";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id_template, 
            ':lc' => $loinc_code, ':ld' => $loinc_display, ':sc' => $snomed_code, ':sd' => $snomed_display,
            ':lc2' => $loinc_code, ':ld2' => $loinc_display, ':sc2' => $snomed_code, ':sd2' => $snomed_display
        ]);

        $pesan = "Berhasil menyimpan mapping.";

        // B. FITUR SMART COPY
        if (isset($_POST['apply_all']) && $_POST['apply_all'] === 'true') {
            $cek = $pdo->prepare("SELECT Pemeriksaan FROM template_laboratorium WHERE id_template = ?");
            $cek->execute([$id_template]);
            $raw_name = $cek->fetchColumn();
            
            if ($raw_name) {
                $keyword = trim($raw_name); // Bersihkan spasi
                
                // LOGIC: Exact Match, Case Insensitive, Trimmed
                $bulkSql = "INSERT INTO satu_sehat_mapping_lab (id_template, code, system, display, sampel_code, sampel_system, sampel_display)
                            SELECT t.id_template, :lc, 'http://loinc.org', :ld, :sc, 'http://snomed.info/sct', :sd
                            FROM template_laboratorium t
                            WHERE TRIM(LOWER(t.Pemeriksaan)) = TRIM(LOWER(:nama_pemeriksaan))
                            AND t.id_template != :id_asal
                            ON DUPLICATE KEY UPDATE 
                            code=:lc2, display=:ld2, sampel_code=:sc2, sampel_display=:sd2";
                
                $stmtBulk = $pdo->prepare($bulkSql);
                $stmtBulk->execute([
                    ':lc' => $loinc_code, ':ld' => $loinc_display, ':sc' => $snomed_code, ':sd' => $snomed_display,
                    ':nama_pemeriksaan' => $keyword,
                    ':id_asal' => $id_template,
                    ':lc2' => $loinc_code, ':ld2' => $loinc_display, ':sc2' => $snomed_code, ':sd2' => $snomed_display
                ]);
                
                $jml_copy = $stmtBulk->rowCount();
                if($jml_copy > 0) {
                    $pesan .= " Dan berhasil menyalin ke <b>$jml_copy</b> pemeriksaan lain yang bernama <b>'$keyword'</b>.";
                }
            }
        }
        
        echo json_encode(['status' => 'success', 'message' => $pesan]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server Error: " . $e->getMessage()]);
}
?>