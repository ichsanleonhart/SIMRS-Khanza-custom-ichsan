<?php
// File: get_antrean_racikan_display.php
// PERBAIKAN: Memanggil file conf.php lokal
require_once('conf.php'); 

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
header("Cache-Control: no-store, no-cache, must-revalidate"); 
header("Pragma: no-cache"); 

$query_racikan = "
    SELECT 
        RIGHT(ro.no_resep, 4) as no_antrian, /* (Poin 8) */
        ro.no_rawat, 
        p.nm_pasien, 
        pl.nm_poli,
        ro.tgl_perawatan as tgl_validasi, /* (Poin 7) */
        ro.jam as jam_validasi /* (Poin 7) */
    FROM resep_obat ro
    INNER JOIN reg_periksa r ON ro.no_rawat = r.no_rawat
    INNER JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
    INNER JOIN poliklinik pl ON r.kd_poli = pl.kd_poli
    /* Filter hanya yang racikan */
    INNER JOIN resep_dokter_racikan rdr ON ro.no_resep = rdr.no_resep 
    /* Filter yang TIDAK SEDANG DIPANGGIL */
    LEFT JOIN antrean_farmasi_panggil afp ON ro.no_resep = afp.no_resep
    WHERE 
        ro.tgl_peresepan = CURDATE()
        AND ro.jam != '00:00:00'               /* Sudah divalidasi */
        AND ro.jam_penyerahan = '00:00:00'   /* (Poin 7) Belum diserahkan */
        AND afp.no_resep IS NULL               /* Tidak sedang dalam status 'PANGGIL' */
        AND ro.status = 'ralan'
    GROUP BY ro.no_resep 
	ORDER BY no_antrian desc";
    // ORDER BY ro.jam ASC"; /* 'jam' adalah jam validasi */

$hasil = bukaquery($query_racikan); 

echo '<table>';
echo '<thead><tr><th>No. Antrian</th><th>Nama Pasien</th><th>Poliklinik</th><th>Tgl Validasi</th><th>Jam Validasi</th></tr></thead>';
echo '<tbody>';
if (mysqli_num_rows($hasil) > 0) {
    while ($data = mysqli_fetch_assoc($hasil)) { 
        echo '<tr>';
        echo '<td>' . htmlspecialchars($data['no_antrian']) . '</td>';
        echo '<td>' . htmlspecialchars(substr($data['nm_pasien'], 0, 20)) . '</td>'; 
        echo '<td>' . htmlspecialchars($data['nm_poli']) . '</td>';
        echo '<td>' . htmlspecialchars($data['tgl_validasi']) . '</td>';
        echo '<td>' . htmlspecialchars($data['jam_validasi']) . '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="5" style="text-align:center; padding: 20px;">- Tidak ada antrean -</td></tr>';
}
echo '</tbody>';
echo '</table>';
?>