<?php
require 'koneksi.php';
require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

$nomor_tiket = $_GET['id'] ?? die("Nomor tiket tidak ditemukan.");

// Ambil data tiket
$stmt = $conn->prepare("SELECT l.*, k.nama_kategori, u.nama, u.jabatan, u.unit_kerja 
                        FROM laporan l
                        JOIN kategori_pelaporan k ON l.kategori_id = k.id
                        JOIN users u ON l.user_id = u.id
                        WHERE l.nomor_tiket = ?");
$stmt->bind_param("s", $nomor_tiket);
$stmt->execute();
$tiket = $stmt->get_result()->fetch_assoc() ?: die("Data tiket tidak ditemukan.");

// Ambil data perusahaan
$pr = $conn->query("SELECT * FROM perusahaan LIMIT 1")->fetch_assoc();

// HTML layout
$html = "
<style>
body {
    font-family: 'Arial', sans-serif;
    font-size: 10px;
    margin: 0;
    padding: 15px;
}
.header {
    background: #2c3e50;
    color: #fff;
    padding: 8px 15px;
    text-align: center;
}
.title {
    font-size: 13px;
    font-weight: bold;
    letter-spacing: 1px;
}
.section {
    margin-top: 12px;
    border: 1px dashed #ccc;
    padding: 10px;
    background: #fdfdfd;
}
.label {
    font-weight: bold;
    color: #2c3e50;
    width: 30%;
    vertical-align: top;
}
table.details {
    width: 100%;
    border-collapse: collapse;
}
.details td {
    padding: 4px 3px;
}
.footer {
    margin-top: 20px;
    font-style: italic;
    font-size: 9px;
    text-align: center;
    color: #666;
    border-top: 1px dashed #ccc;
    padding-top: 6px;
}
</style>

<div class='header'>
    <div class='title'>IT TICKETING SERVICE</div>
    <div>{$pr['nama_perusahaan']} — {$pr['kota']}</div>
</div>

<div class='section'>
    <table class='details'>
        <tr><td class='label'>Nomor Tiket</td><td>: {$tiket['nomor_tiket']}</td></tr>
        <tr><td class='label'>Tanggal</td><td>: " . date("d/m/Y H:i", strtotime($tiket['tanggal'])) . "</td></tr>
        <tr><td class='label'>Pelapor</td><td>: {$tiket['nama']}</td></tr>
        <tr><td class='label'>Jabatan</td><td>: {$tiket['jabatan']}</td></tr>
        <tr><td class='label'>Unit Kerja</td><td>: {$tiket['unit_kerja']}</td></tr>
        <tr><td class='label'>Kategori</td><td>: {$tiket['nama_kategori']}</td></tr>
        <tr><td class='label'>Subjek</td><td>: {$tiket['subjek']}</td></tr>
        <tr><td class='label'>Deskripsi</td><td>: " . nl2br(htmlspecialchars($tiket['deskripsi'])) . "</td></tr>
        <tr><td class='label'>Status</td><td>: " . ucfirst($tiket['status']) . "</td></tr>
        <tr><td class='label'>Catatan Admin</td><td>: " . (!empty($tiket['catatan_admin']) ? nl2br(htmlspecialchars($tiket['catatan_admin'])) : '-') . "</td></tr>
    </table>
</div>

<div class='footer'>
    Dikeluarkan di {$pr['nama_perusahaan']}, tanggal " . date("d/m/Y", strtotime($tiket['tanggal'])) . "
</div>
";

// Generate PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper([0, 0, 420, 595], 'portrait'); // A6 size for compact look
$dompdf->render();
$dompdf->stream("Tiket_{$tiket['nomor_tiket']}.pdf", ["Attachment" => false]);
?>
