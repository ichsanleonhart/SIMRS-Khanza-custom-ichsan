<?php
// FILE: chatbot.php
// Path: C:\xampp\htdocs\wa_gateway\chatbot.php
// Logic: Menerima request dari Node.js, query ke Khanza, kembalikan jawaban teks.

// Matikan error display agar response bersih untuk Node.js
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

// --- KONFIGURASI DB KHANZA ---
$host = '192.168.1.5';
$db   = 'sik_master';
$user = 'client';
$pass = 'epotoransu';

// --- FUNGSI BANTUAN HARI INDO ---
function getHariIndo() {
    $hari_inggris = date('l');
    $map = [
        'Sunday' => 'AKHAD', 'Monday' => 'SENIN', 'Tuesday' => 'SELASA',
        'Wednesday' => 'RABU', 'Thursday' => 'KAMIS', 'Friday' => 'JUMAT', 'Saturday' => 'SABTU'
    ];
    return $map[$hari_inggris] ?? 'SENIN'; // Default Senin kalau error
}

// --- TERIMA DATA DARI NODE.JS ---
// Node.js akan kirim JSON: { "sender": "628...", "message": "#info..." }
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    echo json_encode(['reply' => 'Error: No data received']);
    exit;
}

$sender = $input['sender'];
$pesan_asli = trim($input['message']);
$pesan_lower = strtolower($pesan_asli); // Ubah ke huruf kecil biar gampang cek

// Default Jawaban
$reply_text = "Maaf, perintah tidak dikenali.\nKetik *#info jadwal praktek* untuk bantuan.";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- LOGIKA CERDAS: PARSING PERINTAH ---
    
    // 1. CEK PREFIX #INFO
    if (strpos($pesan_lower, '#info jadwal') === 0) {
        
        // QUERY DASAR
        $sql = "SELECT d.nm_dokter, j.hari_kerja, j.jam_mulai, j.jam_selesai, p.nm_poli 
                FROM jadwal j 
                JOIN dokter d ON j.kd_dokter = d.kd_dokter 
                JOIN poliklinik p ON j.kd_poli = p.kd_poli 
                WHERE 1=1 ";
        
        $params = [];
        $judul_reply = "";

        // KASUS A: #info jadwal hari ini
        if (strpos($pesan_lower, 'hari ini') !== false) {
            $hari_ini = getHariIndo();
            $sql .= " AND j.hari_kerja = :hari ";
            $params[':hari'] = $hari_ini;
            $judul_reply = "📅 *JADWAL PRAKTEK HARI INI ($hari_ini)*";

        } 
        // KASUS B: #info jadwal dokter [nama]
        // Contoh: #info jadwal dokter tati
        elseif (strpos($pesan_lower, 'dokter') !== false) {
            // Ambil nama setelah kata "dokter"
            // Explode berdasarkan spasi, ambil kata setelah index kata 'dokter'
            // Cara simpel: Hapus "#info jadwal dokter", sisanya adalah nama
            $nama_cari = trim(str_ireplace('#info jadwal dokter', '', $pesan_asli));
            
            if (!empty($nama_cari)) {
                $sql .= " AND d.nm_dokter LIKE :nama ";
                $params[':nama'] = "%$nama_cari%";
                $judul_reply = "👨‍⚕️ *PENCARIAN DOKTER: \"$nama_cari\"*";
            } else {
                // Kalau user cuma ketik "#info jadwal dokter" tanpa nama
                $judul_reply = "📋 *SEMUA JADWAL DOKTER*";
            }

        } 
        // KASUS C: #info jadwal praktek (Semua)
        else {
            $judul_reply = "🏥 *JADWAL PRAKTEK RSU KARINA MEDIKA*";
            // Limit biar ga kepanjangan kalau semua
            $sql .= " ORDER BY j.hari_kerja, p.nm_poli LIMIT 50"; 
        }

        // EKSEKUSI QUERY
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $reply_text = "$judul_reply\n--------------------------\n";
            foreach ($results as $row) {
                // Format: dr. Nama (Poli) - SENIN (08:00-12:00)
                $reply_text .= "🔹 *" . $row['nm_dokter'] . "*\n";
                $reply_text .= "    (" . $row['nm_poli'] . ")\n";
                $reply_text .= "    🕒 " . $row['hari_kerja'] . " : " . $row['jam_mulai'] . " - " . $row['jam_selesai'] . "\n\n";
            }
            $reply_text .= "--------------------------\n_Terima kasih_ 🙏";
        } else {
            $reply_text = "❌ Mohon maaf, jadwal yang Anda cari tidak ditemukan.";
        }
        
        // --- OPSIONAL: SIMPAN KE INBOX DATABASE (Hanya yang valid) ---
        // Jika kamu ingin mencatat request valid ini ke tabel wa_inbox (buat tabel dulu jika belum ada)
        /*
        $log_sql = "INSERT INTO wa_inbox (sender, message, tgl_terima) VALUES (:sender, :msg, NOW())";
        $stmt_log = $pdo->prepare($log_sql);
        $stmt_log->execute([':sender' => $sender, ':msg' => $pesan_asli]);
        */
    }

} catch (Exception $e) {
    $reply_text = "⚠️ Terjadi kesalahan sistem: " . $e->getMessage();
}

// Kirim Jawaban Balik ke Node.js
echo json_encode(['reply' => $reply_text]);
?>