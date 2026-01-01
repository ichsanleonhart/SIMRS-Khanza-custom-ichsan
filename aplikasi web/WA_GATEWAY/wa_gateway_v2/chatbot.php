<?php
// FILE: chatbot.php (UPGRADE: DYNAMIC INSTANCE, LAB, BED, QUEUE)
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

// --- LOAD CONFIG ---
$configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config.json';
if (!file_exists($configFile)) {
    echo json_encode(['reply' => 'Error: Config file not found']);
    exit;
}
$config = json_decode(file_get_contents($configFile), true);

$host = $config['db_host'];
$db   = $config['db_name'];
$user = $config['db_user'];
$pass = $config['db_pass'];

// --- FUNGSI BANTUAN ---
function getHariIndo() {
    $hari_inggris = date('l');
    $map = [
        'Sunday' => 'AKHAD', 'Monday' => 'SENIN', 'Tuesday' => 'SELASA',
        'Wednesday' => 'RABU', 'Thursday' => 'KAMIS', 'Friday' => 'JUMAT', 'Saturday' => 'SABTU'
    ];
    return $map[$hari_inggris] ?? 'SENIN'; 
}

function formatRupiah($angka){
	return "Rp " . number_format($angka,0,',','.');
}

// --- TERIMA DATA ---
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    echo json_encode(['reply' => 'Error: No data received']);
    exit;
}

$sender = $input['sender'];
$pesan_asli = trim($input['message']);
$pesan_lower = strtolower($pesan_asli); 

// Default Jawaban jika tidak match apapun
$reply_text = "Maaf, perintah tidak dikenali.\nKetik *#info* untuk melihat daftar bantuan.";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 0. AMBIL NAMA INSTANSI (DINAMIS)
    $stmtInstansi = $pdo->query("SELECT nama_instansi FROM setting LIMIT 1");
    $instansiRow = $stmtInstansi->fetch(PDO::FETCH_ASSOC);
    $nama_rs = $instansiRow['nama_instansi'] ?? 'RUMAH SAKIT';

    // =======================================================================
    // 1. MENU BANTUAN (#INFO saja)
    // =======================================================================
    if ($pesan_lower === '#info') {
        $reply_text = "🏥 *LAYANAN INFORMASI $nama_rs*\n";
        $reply_text .= "Berikut perintah yang bisa Anda gunakan:\n\n";
        $reply_text .= "📅 *Jadwal Dokter*\n";
        $reply_text .= "• `#Info jadwal praktek` (Semua)\n";
        $reply_text .= "• `#Info jadwal hari ini`\n";
        $reply_text .= "• `#Info jadwal dokter [nama]`\n\n";
        
        $reply_text .= "🛏️ *Ketersediaan Kamar*\n";
        $reply_text .= "• `#Info ketersediaan bed`\n\n";

        $reply_text .= "🧪 *Laboratorium*\n";
        $reply_text .= "• `#Info lab` (Daftar Paket)\n";
        $reply_text .= "• `#Info paket [nama paket]` (Detail)\n\n";

        $reply_text .= "🔢 *Antrean Poli*\n";
        $reply_text .= "• `#Info antrean poli [nama poli]`\n";
        $reply_text .= "  (Contoh: #Info antrean poli anak)";
    }

    // =======================================================================
    // 2. INFO JADWAL DOKTER
    // =======================================================================
    elseif (strpos($pesan_lower, '#info jadwal') === 0) {
        $sql = "SELECT d.nm_dokter, j.hari_kerja, j.jam_mulai, j.jam_selesai, p.nm_poli 
                FROM jadwal j 
                JOIN dokter d ON j.kd_dokter = d.kd_dokter 
                JOIN poliklinik p ON j.kd_poli = p.kd_poli 
                WHERE 1=1 ";
        $params = [];
        $judul_reply = "";

        if (strpos($pesan_lower, 'hari ini') !== false) {
            $hari_ini = getHariIndo();
            $sql .= " AND j.hari_kerja = :hari ";
            $params[':hari'] = $hari_ini;
            $judul_reply = "📅 *JADWAL PRAKTEK HARI INI ($hari_ini)*";
        } elseif (strpos($pesan_lower, 'dokter') !== false) {
            $nama_cari = trim(str_ireplace('#info jadwal dokter', '', $pesan_asli));
            if (!empty($nama_cari)) {
                $sql .= " AND d.nm_dokter LIKE :nama ";
                $params[':nama'] = "%$nama_cari%";
                $judul_reply = "👨‍⚕️ *PENCARIAN DOKTER: \"$nama_cari\"*";
            } else {
                $judul_reply = "📋 *SEMUA JADWAL DOKTER*";
            }
        } else {
            $judul_reply = "🏥 *JADWAL PRAKTEK $nama_rs*";
            $sql .= " ORDER BY j.hari_kerja, p.nm_poli LIMIT 50"; 
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $reply_text = "$judul_reply\n--------------------------\n";
            foreach ($results as $row) {
                $reply_text .= "🔹 *" . $row['nm_dokter'] . "*\n";
                $reply_text .= "    (" . $row['nm_poli'] . ")\n";
                $reply_text .= "    🕒 " . $row['hari_kerja'] . " : " . $row['jam_mulai'] . " - " . $row['jam_selesai'] . "\n\n";
            }
            $reply_text .= "_Terima kasih_ 🙏";
        } else {
            $reply_text = "❌ Jadwal tidak ditemukan.";
        }
    }

    // =======================================================================
    // 3. INFO KETERSEDIAAN BED
    // =======================================================================
    elseif (strpos($pesan_lower, '#info ketersediaan bed') === 0) {
        $sql = "SELECT k.kd_kamar, b.nm_bangsal, k.kelas, k.trf_kamar
                FROM kamar k
                INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
                WHERE k.status = 'KOSONG' AND k.statusdata = '1'
                ORDER BY b.nm_bangsal ASC, k.kelas ASC";
        
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $reply_text = "🛏️ *INFO BED KOSONG $nama_rs*\n--------------------------\n";
            
            // Grouping by Bangsal biar rapi
            $current_bangsal = "";
            foreach ($results as $row) {
                if ($current_bangsal != $row['nm_bangsal']) {
                    $current_bangsal = $row['nm_bangsal'];
                    $reply_text .= "\n🏥 *" . $current_bangsal . "*\n";
                }
                $reply_text .= "   • Bed " . $row['kd_kamar'] . " (Kls " . $row['kelas'] . ") - " . formatRupiah($row['trf_kamar']) . "\n";
            }
            $reply_text .= "\n_Silakan hubungi pendaftaran untuk booking._";
        } else {
            $reply_text = "❌ Mohon maaf, saat ini TIDAK ADA bed yang kosong.";
        }
    }

    // =======================================================================
    // 4. INFO LAB / PAKET LAB
    // =======================================================================
    elseif (strpos($pesan_lower, '#info lab') === 0 || strpos($pesan_lower, '#info paket') === 0) {
        
        // Cek apakah user mencari paket spesifik
        $is_search = strpos($pesan_lower, '#info paket') === 0;
        $keyword_paket = "";

        $sql = "SELECT j.nm_perawatan AS nama_paket, j.kelas, t.Pemeriksaan AS nama_parameter
                FROM jns_perawatan_lab j
                INNER JOIN template_laboratorium t ON j.kd_jenis_prw = t.kd_jenis_prw
                WHERE j.status = '1' ";

        $params = [];

        if ($is_search) {
            $keyword_paket = trim(str_ireplace('#info paket', '', $pesan_asli));
            if (!empty($keyword_paket)) {
                $sql .= " AND j.nm_perawatan LIKE :paket ";
                $params[':paket'] = "%$keyword_paket%";
            }
        }
        
        $sql .= " ORDER BY j.nm_perawatan ASC, t.urut ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $reply_text = "🧪 *INFORMASI LABORATORIUM*\n";
            if (!empty($keyword_paket)) $reply_text .= "Pencarian: \"$keyword_paket\"\n";
            $reply_text .= "--------------------------\n";

            // Grouping Paket
            $current_paket = "";
            foreach ($results as $row) {
                if ($current_paket != $row['nama_paket']) {
                    $current_paket = $row['nama_paket'];
                    $reply_text .= "\n📦 *" . $current_paket . "*\n";
                }
                $reply_text .= "   - " . $row['nama_parameter'] . "\n";
            }
        } else {
            $reply_text = "❌ Paket Lab tidak ditemukan.";
        }
    }

    // =======================================================================
    // 5. INFO ANTREAN POLI
    // =======================================================================
    elseif (strpos($pesan_lower, '#info antrean poli') === 0) {
        $nama_poli = trim(str_ireplace('#info antrean poli', '', $pesan_asli));
        
        if (empty($nama_poli)) {
            $reply_text = "⚠️ Sebutkan nama polinya.\nContoh: `#Info antrean poli anak`";
        } else {
            $sql = "SELECT MAX(no_reg) AS antrean_terakhir, p.nm_poli
                    FROM reg_periksa r
                    JOIN poliklinik p ON r.kd_poli = p.kd_poli
                    WHERE p.nm_poli LIKE :poli 
                    AND r.tgl_registrasi = CURDATE()";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':poli' => "%$nama_poli%"]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && $row['nm_poli']) {
                $last_no = $row['antrean_terakhir'] ?? '0';
                $reply_text = "🔢 *STATUS ANTREAN HARI INI*\n";
                $reply_text .= "🏥 Poli: *" . $row['nm_poli'] . "*\n";
                $reply_text .= "📅 Tanggal: " . date('d-m-Y') . "\n";
                $reply_text .= "--------------------------\n";
                $reply_text .= "Antrean Terakhir Terdaftar: *No. " . $last_no . "*\n\n";
                $reply_text .= "_Segera daftar sebelum kuota habis!_";
            } else {
                $reply_text = "❌ Poli \"$nama_poli\" tidak ditemukan atau belum ada pasien hari ini.";
            }
        }
    }

} catch (Exception $e) {
    $reply_text = "⚠️ Terjadi kesalahan sistem: " . $e->getMessage();
}

echo json_encode(['reply' => $reply_text]);
?>