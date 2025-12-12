<?php
/*
 * File: cetak_resume.php (V2 - Full Data & QR Code)
 * Fungsi: Generator Resume Medis Rawat Inap
 */
session_start();
require_once('../conf/conf.php');
$koneksi = bukakoneksi();

// 1. Ambil No Rawat
$no_rawat = isset($_GET['no_rawat']) ? validTeks4($_GET['no_rawat'], 20) : '';

// 2. QUERY UTAMA (Sesuai Request User)
// Kita filter by no_rawat untuk cetak satu pasien
$sql = "SELECT 
    reg_periksa.no_rawat,
    reg_periksa.no_rkm_medis,
    pasien.nm_pasien,
    pasien.tgl_lahir,
    pasien.jk,
    pasien.alamat,
    resume_pasien_ranap.kd_dokter,
    dokter.nm_dokter,
    reg_periksa.kd_dokter AS kodepengirim,
    pengirim.nm_dokter AS pengirim,
    reg_periksa.tgl_registrasi,
    reg_periksa.jam_reg,
    resume_pasien_ranap.diagnosa_awal,
    resume_pasien_ranap.alasan,
    resume_pasien_ranap.keluhan_utama,
    resume_pasien_ranap.pemeriksaan_fisik,
    resume_pasien_ranap.jalannya_penyakit,
    resume_pasien_ranap.pemeriksaan_penunjang,
    resume_pasien_ranap.hasil_laborat,
    resume_pasien_ranap.tindakan_dan_operasi,
    resume_pasien_ranap.obat_di_rs,
    resume_pasien_ranap.diagnosa_utama,
    resume_pasien_ranap.kd_diagnosa_utama,
    resume_pasien_ranap.diagnosa_sekunder,
    resume_pasien_ranap.kd_diagnosa_sekunder,
    resume_pasien_ranap.diagnosa_sekunder2,
    resume_pasien_ranap.kd_diagnosa_sekunder2,
    resume_pasien_ranap.diagnosa_sekunder3,
    resume_pasien_ranap.kd_diagnosa_sekunder3,
    resume_pasien_ranap.diagnosa_sekunder4,
    resume_pasien_ranap.kd_diagnosa_sekunder4,
    resume_pasien_ranap.prosedur_utama,
    resume_pasien_ranap.kd_prosedur_utama,
    resume_pasien_ranap.prosedur_sekunder,
    resume_pasien_ranap.kd_prosedur_sekunder,
    resume_pasien_ranap.prosedur_sekunder2,
    resume_pasien_ranap.kd_prosedur_sekunder2,
    resume_pasien_ranap.prosedur_sekunder3,
    resume_pasien_ranap.kd_prosedur_sekunder3,
    resume_pasien_ranap.alergi,
    resume_pasien_ranap.diet,
    resume_pasien_ranap.lab_belum,
    resume_pasien_ranap.edukasi,
    resume_pasien_ranap.cara_keluar,
    resume_pasien_ranap.ket_keluar,
    resume_pasien_ranap.keadaan,
    resume_pasien_ranap.ket_keadaan,
    resume_pasien_ranap.dilanjutkan,
    resume_pasien_ranap.ket_dilanjutkan,
    resume_pasien_ranap.kontrol,
    resume_pasien_ranap.obat_pulang,
    reg_periksa.kd_pj,
    penjab.png_jawab,
    kamar_inap.tgl_keluar,
    kamar_inap.tgl_masuk
FROM resume_pasien_ranap
INNER JOIN reg_periksa ON resume_pasien_ranap.no_rawat = reg_periksa.no_rawat
INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
INNER JOIN dokter ON resume_pasien_ranap.kd_dokter = dokter.kd_dokter
INNER JOIN dokter AS pengirim ON reg_periksa.kd_dokter = pengirim.kd_dokter
INNER JOIN penjab ON penjab.kd_pj = reg_periksa.kd_pj
LEFT JOIN kamar_inap ON resume_pasien_ranap.no_rawat = kamar_inap.no_rawat
WHERE reg_periksa.no_rawat = '2025/11/30/000021'
ORDER BY kamar_inap.tgl_keluar DESC LIMIT 1";

$hasil = mysqli_query($koneksi, $sql);
$data = mysqli_fetch_assoc($hasil);

if(!$data) {
    die("<div style='text-align:center; margin-top:50px;'><h3>Data Resume Tidak Ditemukan</h3><p>Pastikan resume sudah diinput di SIMRS.</p></div>");
}

// 3. LOGIKA QR CODE (Tanda Tangan Elektronik)
// A. Ambil Data Setting RS
$q_set = mysqli_query($koneksi, "SELECT nama_instansi, kabupaten FROM setting LIMIT 1");
$r_set = mysqli_fetch_assoc($q_set);
$nama_rs = $r_set['nama_instansi'];
$kab_rs  = $r_set['kabupaten'];

// B. Ambil Fingerprint Dokter (SHA1)
$kd_dokter = $data['kd_dokter'];
$q_finger = mysqli_query($koneksi, "SELECT SHA1(sidikjari.sidikjari) as finger 
                                    FROM sidikjari 
                                    INNER JOIN pegawai ON pegawai.id = sidikjari.id 
                                    WHERE pegawai.nik = '$kd_dokter'");
$r_finger = mysqli_fetch_assoc($q_finger);
$finger_code = ($r_finger && !empty($r_finger['finger'])) ? $r_finger['finger'] : $kd_dokter;

// C. Susun String QR
$tgl_keluar_fix = !empty($data['tgl_keluar']) && $data['tgl_keluar'] != '0000-00-00' ? $data['tgl_keluar'] : date('Y-m-d');
$qr_content = "Dikeluarkan di " . $nama_rs . ", Kabupaten/Kota " . $kab_rs . "\n";
$qr_content .= "Ditandatangani secara elektronik oleh " . $data['nm_dokter'] . "\n";
$qr_content .= "ID " . $finger_code . "\n";
$qr_content .= $tgl_keluar_fix;

// 4. Helper Formatting
function tgl_indo($tanggal){
	if(empty($tanggal) || $tanggal == '0000-00-00') return "-";
    $bulan = array (1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
	$pecahkan = explode('-', $tanggal);
	return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

$lahir = new DateTime($data['tgl_lahir']);
$regis = new DateTime($data['tgl_registrasi']);
$usia = $regis->diff($lahir);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Resume Medis - <?= $data['nm_pasien'] ?></title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 11px; color: #000; -webkit-print-color-adjust: exact; }
        .container { width: 100%; max-width: 210mm; margin: 0 auto; background: #fff; padding: 10px; }
        
        /* Layout Tabel */
        table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        th, td { vertical-align: top; padding: 4px; }
        
        /* Tabel Bordered */
        .tbl-border, .tbl-border th, .tbl-border td { border: 1px solid #000; }
        .tbl-border th { background-color: #f0f0f0; font-weight: bold; text-align: left; }
        
        /* Header & Judul */
        .judul { text-align: center; font-weight: bold; font-size: 14px; text-transform: uppercase; margin: 15px 0; text-decoration: underline; }
        .sub-header { font-weight: bold; background: #e0e0e0; padding: 5px; border: 1px solid #000; margin-top: 10px; }
        
        /* TTD Area */
        .ttd-container { display: flex; justify-content: space-between; margin-top: 20px; page-break-inside: avoid; }
        .ttd-box { width: 45%; text-align: center; }
        .qr-img { width: 90px; height: 90px; margin: 10px auto; }
        
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
            .container { max-width: 100%; padding: 0; }
        }
    </style>
</head>
<body>

<div class="no-print" style="padding: 10px; background: #f8f9fa; border-bottom: 1px solid #ddd; text-align: center; margin-bottom: 20px;">
    <button onclick="window.print()" style="padding: 8px 20px; cursor: pointer; font-weight: bold;">🖨️ Cetak / Simpan PDF</button>
</div>

<div class="container">
    <table style="border-bottom: 2px solid #000; margin-bottom: 10px;">
        <tr>
            <td width="60" align="center"><img src="logo.php" width="50"></td>
            <td align="center">
                <div style="font-size: 16px; font-weight: bold;"><?= $nama_rs ?></div>
                <div style="font-size: 10px;"><?= $kab_rs ?></div>
            </td>
            <td width="60"></td>
        </tr>
    </table>

    <div class="judul">RESUME MEDIS PASIEN RAWAT INAP</div>

    <table class="tbl-border">
        <tr>
            <td width="15%"><b>No. RM</b></td>
            <td width="35%">: <?= $data['no_rkm_medis'] ?></td>
            <td width="15%"><b>No. Rawat</b></td>
            <td width="35%">: <?= $data['no_rawat'] ?></td>
        </tr>
        <tr>
            <td><b>Nama Pasien</b></td>
            <td>: <?= $data['nm_pasien'] ?></td>
            <td><b>Tgl. Lahir</b></td>
            <td>: <?= tgl_indo($data['tgl_lahir']) ?> (<?= $usia->y ?> Th)</td>
        </tr>
        <tr>
            <td><b>Jns Kelamin</b></td>
            <td>: <?= $data['jk'] == 'L' ? 'Laki-Laki' : 'Perempuan' ?></td>
            <td><b>Penjamin</b></td>
            <td>: <?= $data['png_jawab'] ?></td>
        </tr>
        <tr>
            <td><b>Tgl Masuk</b></td>
            <td>: <?= tgl_indo($data['tgl_masuk']) ?></td>
            <td><b>Tgl Keluar</b></td>
            <td>: <?= tgl_indo($data['tgl_keluar']) ?></td>
        </tr>
        <tr>
            <td><b>Dokter DPJP</b></td>
            <td>: <?= $data['nm_dokter'] ?></td>
            <td><b>Dokter Pengirim</b></td>
            <td>: <?= $data['pengirim'] ?></td>
        </tr>
    </table>

    <div class="sub-header">I. RINGKASAN RIWAYAT PENYAKIT</div>
    <table class="tbl-border" style="border-top: none;">
        <tr>
            <td width="25%"><b>Keluhan Utama</b></td>
            <td><?= nl2br($data['keluhan_utama']) ?></td>
        </tr>
        <tr>
            <td><b>Riwayat Penyakit</b></td>
            <td><?= nl2br($data['jalannya_penyakit']) ?></td>
        </tr>
        <tr>
            <td><b>Pemeriksaan Fisik</b></td>
            <td><?= nl2br($data['pemeriksaan_fisik']) ?></td>
        </tr>
        <tr>
            <td><b>Pemeriksaan Penunjang</b></td>
            <td><?= nl2br($data['pemeriksaan_penunjang']) ?></td>
        </tr>
        <tr>
            <td><b>Hasil Laboratorium</b></td>
            <td><?= nl2br($data['hasil_laborat']) ?></td>
        </tr>
        <tr>
            <td><b>Lab Belum Selesai</b></td>
            <td><?= nl2br($data['lab_belum']) ?></td>
        </tr>
        <tr>
            <td><b>Alergi</b></td>
            <td><?= nl2br($data['alergi']) ?></td>
        </tr>
    </table>

    <div class="sub-header">II. DIAGNOSA & TINDAKAN</div>
    <table class="tbl-border" style="border-top: none;">
        <tr>
            <td width="25%"><b>Diagnosa Awal</b></td>
            <td><?= $data['diagnosa_awal'] ?></td>
        </tr>
        <tr>
            <td><b>Diagnosa Utama</b></td>
            <td><b><?= $data['kd_diagnosa_utama'] ?></b> - <?= $data['diagnosa_utama'] ?></td>
        </tr>
        <?php for($i=1; $i<=4; $i++): 
            $suffix = ($i==1) ? '' : $i; // Sekunder, Sekunder2, dst
            if(!empty($data['diagnosa_sekunder'.$suffix])): ?>
            <tr>
                <td><b>Diagnosa Sekunder <?= $i ?></b></td>
                <td><b><?= $data['kd_diagnosa_sekunder'.$suffix] ?></b> - <?= $data['diagnosa_sekunder'.$suffix] ?></td>
            </tr>
        <?php endif; endfor; ?>
        
        <tr>
            <td><b>Prosedur Utama</b></td>
            <td><b><?= $data['kd_prosedur_utama'] ?></b> - <?= $data['prosedur_utama'] ?></td>
        </tr>
        <?php for($i=1; $i<=3; $i++): 
            $suffix = ($i==1) ? '' : $i;
            if(!empty($data['prosedur_sekunder'.$suffix])): ?>
            <tr>
                <td><b>Prosedur Tambahan <?= $i ?></b></td>
                <td><b><?= $data['kd_prosedur_sekunder'.$suffix] ?></b> - <?= $data['prosedur_sekunder'.$suffix] ?></td>
            </tr>
        <?php endif; endfor; ?>

        <tr>
            <td><b>Tindakan & Operasi</b></td>
            <td><?= nl2br($data['tindakan_dan_operasi']) ?></td>
        </tr>
    </table>

    <div class="sub-header">III. PENGOBATAN & KONDISI PULANG</div>
    <table class="tbl-border" style="border-top: none;">
        <tr>
            <td width="25%"><b>Obat Selama di RS</b></td>
            <td><?= nl2br($data['obat_di_rs']) ?></td>
        </tr>
        <tr>
            <td><b>Obat Pulang</b></td>
            <td><?= nl2br($data['obat_pulang']) ?></td>
        </tr>
        <tr>
            <td><b>Diet</b></td>
            <td><?= $data['diet'] ?></td>
        </tr>
        <tr>
            <td><b>Kondisi Pulang</b></td>
            <td>
                Status: <b><?= $data['keadaan'] ?></b> (<?= $data['ket_keadaan'] ?>)<br>
                Cara Keluar: <?= $data['cara_keluar'] ?> (<?= $data['ket_keluar'] ?>)
            </td>
        </tr>
        <tr>
            <td><b>Instruksi / Edukasi</b></td>
            <td><?= nl2br($data['edukasi']) ?></td>
        </tr>
        <tr>
            <td><b>Kontrol Ulang</b></td>
            <td><?= $data['kontrol'] ?></td>
        </tr>
    </table>

    <div class="ttd-container">
        <div class="ttd-box">
            <p>Pasien / Keluarga</p>
            <br><br><br><br>
            <p>( <?= $data['nm_pasien'] ?> / Keluarga )</p>
        </div>

        <div class="ttd-box">
            <p><?= $kab_rs ?>, <?= tgl_indo($tgl_keluar_fix) ?></p>
            <p>Dokter Penanggung Jawab Pelayanan</p>
            
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($qr_content) ?>" class="qr-img" alt="QR Code">
            
            <p style="font-weight: bold; text-decoration: underline;"><?= $data['nm_dokter'] ?></p>
        </div>
    </div>
</div>

</body>
</html>
<?php mysqli_close($koneksi); ?>