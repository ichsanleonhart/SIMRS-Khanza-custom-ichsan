<?php
require 'koneksi.php';
require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

if (!isset($_GET['id'])) {
    die("ID tidak ditemukan");
}

$id = intval($_GET['id']);

// Ambil data perusahaan
$perusahaan = $conn->query("SELECT nama_perusahaan, alamat, kota, provinsi, kontak, email FROM perusahaan LIMIT 1")->fetch_assoc();

// Ambil data permintaan + user
$stmt = $conn->prepare("SELECT a.id, a.subjek, a.deskripsi, a.tanggal, a.status, a.catatan_admin,
                               u.nama, u.nik, u.jabatan, u.unit_kerja, k.nama_kategori
                        FROM akses_khanza a
                        JOIN users u ON a.user_id = u.id
                        JOIN kategori_pelaporan k ON a.kategori_id = k.id
                        WHERE a.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Data tidak ditemukan");
}

$kota = $perusahaan['kota'] ?? '__________';

function formatTanggalIndo($tanggal) {
  $bulanIndo = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
  ];
  $tanggalObj = date_create($tanggal);
  $tgl = date_format($tanggalObj, 'd');
  $bln = date_format($tanggalObj, 'm');
  $thn = date_format($tanggalObj, 'Y');
  return "$tgl {$bulanIndo[$bln]} $thn";
}

$tanggalCetak = formatTanggalIndo($data['tanggal']);
$statusCap = strtoupper($data['status']);

$html = '
<style>
  body { font-family: Arial, sans-serif; font-size: 12px; }
  .kop-surat { text-align: center; border-bottom: 2px solid black; padding-bottom: 5px; margin-bottom: 20px; }
  .kop-surat h2 { margin: 0; font-size: 16pt; }
  .kop-surat p { margin: 2px; font-size: 10pt; }
  .indent { text-indent: 40px; text-align: justify; line-height: 1.6; margin-bottom: 10px; }
  table { width: 100%; border-collapse: collapse; margin-top: 10px; }
  td { padding: 6px; vertical-align: top; }
</style>

<div class="kop-surat">
  <h2>' . htmlspecialchars($perusahaan['nama_perusahaan']) . '</h2>
  <p>' . htmlspecialchars($perusahaan['alamat']) . ', ' . htmlspecialchars($perusahaan['kota']) . ', ' . htmlspecialchars($perusahaan['provinsi']) . '</p>
  <p>Telp: ' . htmlspecialchars($perusahaan['kontak']) . ' | Email: ' . htmlspecialchars($perusahaan['email']) . '</p>
</div>

<p class="indent">
  Sehubungan dengan kebutuhan operasional unit kerja dalam mengakses Sistem Informasi Manajemen Rumah Sakit (SIMRS) Khanza, bersama ini saya mengajukan pembukaan akses yang datanya terlampir di bawah. Akses ini akan digunakan sesuai dengan tanggung jawab pekerjaan saya serta berdasarkan kewenangan yang telah ditetapkan:
</p>

<div style="margin-left: 80px;">
  <table style="width: 80%; font-size: 12px;">
    <tr><td style="width: 30mm;">NIK</td><td>: ' . htmlspecialchars($data['nik']) . '</td></tr>
    <tr><td>Nama</td><td>: ' . htmlspecialchars($data['nama']) . '</td></tr>
    <tr><td>Jabatan</td><td>: ' . htmlspecialchars($data['jabatan']) . '</td></tr>
    <tr><td>Unit Kerja</td><td>: ' . htmlspecialchars($data['unit_kerja']) . '</td></tr>
    <tr><td>Akses</td><td>: ' . nl2br(htmlspecialchars($data['deskripsi'])) . '</td></tr>
  </table>
</div>

<p class="indent">
  Demikian formulir permintaan akses ini saya ajukan untuk diproses sebagaimana mestinya. Atas perhatian dan kerja samanya saya ucapkan terima kasih.
</p>

<br><br>
<table style="width:100%; margin-top: 30px;">
  <tr>
    <td style="width:50%; vertical-align:top;">
      <div style="border: 2px solid red; display: inline-block; padding: 8px 18px; font-weight: bold; font-size: 13px; color: red; font-style: italic; transform: rotate(-5deg); letter-spacing: 1px;">
        STATUS: ' . $statusCap . '
      </div>';

if ($data['status'] === 'selesai' && !empty($data['catatan_admin'])) {
  $html .= '
      <div style="margin-top: 6px; font-style: italic; font-size: 11px; color: #333;">
        *Catatan admin: ' . htmlspecialchars($data['catatan_admin']) . '
      </div>';
}

$html .= '
    </td>
    <td style="width:50%; text-align:center;">
      ' . htmlspecialchars($kota) . ', ' . $tanggalCetak . '<br>
      Pemohon,<br><br><br><br>
      <u>' . htmlspecialchars($data['nama']) . '</u><br>
      NIK: ' . htmlspecialchars($data['nik']) . '
    </td>
  </tr>
</table>
';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("permintaan_akses_khanza_" . $data['id'] . ".pdf", ["Attachment" => false]);
?>
