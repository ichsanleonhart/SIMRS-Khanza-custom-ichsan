<?php
/**
 * API BACKEND ERM MONITORING
 * Author: Kamerad (Gemini) for Alicia
 */
include 'config.php';

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'igd';

// Ambil Tanggal dari Parameter, Default ke Hari Ini
$tgl_awal  = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// Helper Formatter
function fmt($val) {
    if ($val == 'X' || $val == 'Tidak Ada') {
        return "<span class='badge-kosong'>KOSONG</span>";
    } else {
        return "<span class='badge-ada'>✓</span>";
    }
}

$response = ['columns' => [], 'html' => ''];

// ==========================================================
// 1. MODE: IGD
// ==========================================================
if ($mode == 'igd') {
    $response['columns'] = [
        'Dokter', 'No RM', 'Pasien', 
        'CPPT', 'Triase', 'Asesmen Medis', 'Askep', 'Obs', 'Resep', 
        'Resume', 'EWS Neo', 'MEOWS', 'PEWS Anak', 'PEWS Dws', 'Transfer'
    ];

    $sql = "SELECT
        d.nm_dokter, r.no_rkm_medis, p.nm_pasien,
        IF(EXISTS(SELECT 1 FROM pemeriksaan_ralan WHERE no_rawat = r.no_rawat), '✓', 'X') AS cppt,
        IF(EXISTS(SELECT 1 FROM data_triase_igd WHERE no_rawat = r.no_rawat), '✓', 'X') AS triase,
        IF(EXISTS(SELECT 1 FROM penilaian_medis_igd WHERE no_rawat = r.no_rawat), '✓', 'X') AS asmed,
        IF(EXISTS(SELECT 1 FROM penilaian_awal_keperawatan_igd WHERE no_rawat = r.no_rawat), '✓', 'X') AS askep,
        IF(EXISTS(SELECT 1 FROM catatan_observasi_igd WHERE no_rawat = r.no_rawat), '✓', 'X') AS obs,
        IF(EXISTS(SELECT 1 FROM resep_obat WHERE no_rawat = r.no_rawat AND tgl_peresepan NOT LIKE '%0000%'), '✓', 'X') AS resep,
        IF(EXISTS(SELECT 1 FROM resume_pasien WHERE no_rawat = r.no_rawat), '✓', 'X') AS resume,
        IF(EXISTS(SELECT 1 FROM pemantauan_ews_neonatus WHERE no_rawat = r.no_rawat), '✓', 'X') AS ews_neo,
        IF(EXISTS(SELECT 1 FROM pemantauan_meows_obstetri WHERE no_rawat = r.no_rawat), '✓', 'X') AS meows,
        IF(EXISTS(SELECT 1 FROM pemantauan_pews_anak WHERE no_rawat = r.no_rawat), '✓', 'X') AS pews_anak,
        IF(EXISTS(SELECT 1 FROM pemantauan_pews_dewasa WHERE no_rawat = r.no_rawat), '✓', 'X') AS pews_dws,
        IF(EXISTS(SELECT 1 FROM transfer_pasien_antar_ruang WHERE no_rawat = r.no_rawat), '✓', 'X') AS transfer
    FROM reg_periksa r
    JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
    JOIN dokter d ON r.kd_dokter = d.kd_dokter
    WHERE r.tgl_registrasi BETWEEN '$tgl_awal' AND '$tgl_akhir'
    AND r.stts NOT LIKE '%batal%'
    AND r.kd_poli = 'IGDK'
    ORDER BY r.tgl_registrasi DESC, r.jam_reg DESC";

    $res = $koneksi->query($sql);
    if($res) {
        while($row = $res->fetch_assoc()) {
            $response['html'] .= "<tr>
                <td class='fw-bold'>{$row['nm_dokter']}</td>
                <td>{$row['no_rkm_medis']}</td>
                <td>{$row['nm_pasien']}</td>
                <td>".fmt($row['cppt'])."</td>
                <td>".fmt($row['triase'])."</td>
                <td>".fmt($row['asmed'])."</td>
                <td>".fmt($row['askep'])."</td>
                <td>".fmt($row['obs'])."</td>
                <td>".fmt($row['resep'])."</td>
                <td>".fmt($row['resume'])."</td>
                <td>".fmt($row['ews_neo'])."</td>
                <td>".fmt($row['meows'])."</td>
                <td>".fmt($row['pews_anak'])."</td>
                <td>".fmt($row['pews_dws'])."</td>
                <td>".fmt($row['transfer'])."</td>
            </tr>";
        }
    }

// ==========================================================
// 2. MODE: RALAN
// ==========================================================
} elseif ($mode == 'ralan') {
    $response['columns'] = [
        'Poli', 'No Reg', 'Dokter', 'No RM', 'Pasien', 
        'Resep', 'CPPT', 'Askep Ralan', 'Asmed Ralan', 'Resume', 'Diagnosa', 'Status Bayar'
    ];

    $sql = "SELECT
        poli.nm_poli, r.no_reg, d.nm_dokter, r.no_rkm_medis, p.nm_pasien, r.status_bayar,
        IF(EXISTS(SELECT 1 FROM resep_obat WHERE no_rawat = r.no_rawat AND tgl_peresepan NOT LIKE '%0000%'), '✓', 'X') AS resep,
        IF(EXISTS(SELECT 1 FROM pemeriksaan_ralan WHERE no_rawat = r.no_rawat), '✓', 'X') AS cppt,
        IF(EXISTS(SELECT 1 FROM penilaian_awal_keperawatan_ralan WHERE no_rawat = r.no_rawat), '✓', 'X') AS askep,
        IF(EXISTS(SELECT 1 FROM penilaian_medis_ralan WHERE no_rawat = r.no_rawat), '✓', 'X') AS asmed,
        IF(EXISTS(SELECT 1 FROM resume_pasien WHERE no_rawat = r.no_rawat), '✓', 'X') AS resume,
        IF(EXISTS(SELECT 1 FROM diagnosa_pasien WHERE no_rawat = r.no_rawat), '✓', 'X') AS diagnosa
    FROM reg_periksa r
    JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
    JOIN poliklinik poli ON r.kd_poli = poli.kd_poli
    JOIN dokter d ON r.kd_dokter = d.kd_dokter
    WHERE r.tgl_registrasi BETWEEN '$tgl_awal' AND '$tgl_akhir'
    AND r.stts NOT LIKE '%batal%'
    ORDER BY d.nm_dokter ASC, r.no_reg ASC";

    $res = $koneksi->query($sql);
    if($res) {
        while($row = $res->fetch_assoc()) {
            $bg_bayar = ($row['status_bayar'] == 'Belum Bayar') ? 'bg-danger text-white' : 'bg-success text-white';
            $response['html'] .= "<tr>
                <td>{$row['nm_poli']}</td>
                <td class='text-center fw-bold'>{$row['no_reg']}</td>
                <td>{$row['nm_dokter']}</td>
                <td>{$row['no_rkm_medis']}</td>
                <td>{$row['nm_pasien']}</td>
                <td>".fmt($row['resep'])."</td>
                <td>".fmt($row['cppt'])."</td>
                <td>".fmt($row['askep'])."</td>
                <td>".fmt($row['asmed'])."</td>
                <td>".fmt($row['resume'])."</td>
                <td>".fmt($row['diagnosa'])."</td>
                <td class='text-center'><span class='badge $bg_bayar'>{$row['status_bayar']}</span></td>
            </tr>";
        }
    }

// ==========================================================
// 3. MODE: RANAP
// ==========================================================
} elseif ($mode == 'ranap') {
    $response['columns'] = [
        'DPJP', 'No RM', 'Pasien', 'Ruangan',
        'Askep Umum', 'Askep Obgyn', 'RJatuh Neo', 'RJatuh Anak', 'RJatuh Dws',
        'Resep', 'PEWS', 'EWS', 'EWS Neo', 'CPPT', 'Cat. Kep', 'Transfer',
        'Observasi', 'Nyeri', 'Plg', 'Resume', 'Persetujuan'
    ];

    $sql = "SELECT
        d.nm_dokter, r.no_rkm_medis, p.nm_pasien, b.nm_bangsal,
        IF(EXISTS(SELECT 1 FROM penilaian_awal_keperawatan_ranap WHERE no_rawat = r.no_rawat), '✓', 'X') AS askep_umum,
        IF(EXISTS(SELECT 1 FROM penilaian_awal_keperawatan_kebidanan_ranap WHERE no_rawat = r.no_rawat), '✓', 'X') AS askep_obgyn,
        IF(EXISTS(SELECT 1 FROM penilaian_risiko_jatuh_neonatus WHERE no_rawat = r.no_rawat), '✓', 'X') AS rj_neo,
        IF(EXISTS(SELECT 1 FROM penilaian_lanjutan_resiko_jatuh_anak WHERE no_rawat = r.no_rawat), '✓', 'X') AS rj_anak,
        IF(EXISTS(SELECT 1 FROM penilaian_lanjutan_resiko_jatuh_dewasa WHERE no_rawat = r.no_rawat), '✓', 'X') AS rj_dws,
        IF(EXISTS(SELECT 1 FROM resep_obat WHERE no_rawat = r.no_rawat AND tgl_peresepan NOT LIKE '%0000%'), '✓', 'X') AS resep,
        IF(EXISTS(SELECT 1 FROM pemantauan_pews_anak WHERE no_rawat = r.no_rawat), '✓', 'X') AS pews,
        IF(EXISTS(SELECT 1 FROM pemantauan_pews_dewasa WHERE no_rawat = r.no_rawat), '✓', 'X') AS ews,
        IF(EXISTS(SELECT 1 FROM pemantauan_ews_neonatus WHERE no_rawat = r.no_rawat), '✓', 'X') AS ews_neo,
        IF(EXISTS(SELECT 1 FROM pemeriksaan_ranap WHERE no_rawat = r.no_rawat), '✓', 'X') AS cppt,
        IF(EXISTS(SELECT 1 FROM catatan_perawatan WHERE no_rawat = r.no_rawat), '✓', 'X') AS cat_kep,
        IF(EXISTS(SELECT 1 FROM transfer_pasien_antar_ruang WHERE no_rawat = r.no_rawat), '✓', 'X') AS transfer,
        IF(EXISTS(SELECT 1 FROM catatan_observasi_ranap WHERE no_rawat = r.no_rawat), '✓', 'X') AS obs,
        IF(EXISTS(SELECT 1 FROM penilaian_ulang_nyeri WHERE no_rawat = r.no_rawat), '✓', 'X') AS nyeri,
        IF(EXISTS(SELECT 1 FROM perencanaan_pemulangan WHERE no_rawat = r.no_rawat), '✓', 'X') AS pulang,
        IF(EXISTS(SELECT 1 FROM resume_pasien_ranap WHERE no_rawat = r.no_rawat), '✓', 'X') AS resume,
        IF(EXISTS(SELECT 1 FROM persetujuan_penolakan_tindakan WHERE no_rawat = r.no_rawat), '✓', 'X') AS setuju
    FROM reg_periksa r
    JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
    LEFT JOIN dpjp_ranap dr ON r.no_rawat = dr.no_rawat
    LEFT JOIN dokter d ON dr.kd_dokter = d.kd_dokter
    JOIN kamar_inap ki ON r.no_rawat = ki.no_rawat
    JOIN kamar k ON ki.kd_kamar = k.kd_kamar
    JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
    WHERE ki.stts_pulang = '-'
    AND r.stts NOT LIKE '%batal%'
    ORDER BY b.nm_bangsal ASC, d.nm_dokter ASC";

    $res = $koneksi->query($sql);
    if($res) {
        while($row = $res->fetch_assoc()) {
            $response['html'] .= "<tr>
                <td>{$row['nm_dokter']}</td>
                <td>{$row['no_rkm_medis']}</td>
                <td>{$row['nm_pasien']}</td>
                <td>{$row['nm_bangsal']}</td>
                <td>".fmt($row['askep_umum'])."</td>
                <td>".fmt($row['askep_obgyn'])."</td>
                <td>".fmt($row['rj_neo'])."</td>
                <td>".fmt($row['rj_anak'])."</td>
                <td>".fmt($row['rj_dws'])."</td>
                <td>".fmt($row['resep'])."</td>
                <td>".fmt($row['pews'])."</td>
                <td>".fmt($row['ews'])."</td>
                <td>".fmt($row['ews_neo'])."</td>
                <td>".fmt($row['cppt'])."</td>
                <td>".fmt($row['cat_kep'])."</td>
                <td>".fmt($row['transfer'])."</td>
                <td>".fmt($row['obs'])."</td>
                <td>".fmt($row['nyeri'])."</td>
                <td>".fmt($row['pulang'])."</td>
                <td>".fmt($row['resume'])."</td>
                <td>".fmt($row['setuju'])."</td>
            </tr>";
        }
    }

// ==========================================================
// 4. MODE: OPERASI
// ==========================================================
} elseif ($mode == 'operasi') {
    $response['columns'] = [
        'Operator', 'No RM', 'Pasien', 'Ruangan',
        'Resep', 'Pre-Anestesi', 'Laporan OP', 'Persetujuan',
        'Sign In', 'Sign Out', 'Time Out', 'Check Pre-OP', 'Check Post-OP', 'Bromage'
    ];

    $sql = "SELECT
        d.nm_dokter, r.no_rkm_medis, p.nm_pasien, b.nm_bangsal,
        IF(EXISTS(SELECT 1 FROM resep_obat WHERE no_rawat = r.no_rawat AND tgl_peresepan NOT LIKE '%0000%'), '✓', 'X') AS resep,
        IF(EXISTS(SELECT 1 FROM penilaian_pre_anestesi WHERE no_rawat = r.no_rawat), '✓', 'X') AS pre_anestesi,
        IF(EXISTS(SELECT 1 FROM operasi WHERE no_rawat = r.no_rawat), '✓', 'X') AS lap_op,
        IF(EXISTS(SELECT 1 FROM persetujuan_penolakan_tindakan WHERE no_rawat = r.no_rawat), '✓', 'X') AS setuju,
        IF(EXISTS(SELECT 1 FROM signin_sebelum_anestesi WHERE no_rawat = r.no_rawat), '✓', 'X') AS sign_in,
        IF(EXISTS(SELECT 1 FROM signout_sebelum_menutup_luka WHERE no_rawat = r.no_rawat), '✓', 'X') AS sign_out,
        IF(EXISTS(SELECT 1 FROM timeout_sebelum_insisi WHERE no_rawat = r.no_rawat), '✓', 'X') AS time_out,
        IF(EXISTS(SELECT 1 FROM checklist_pre_operasi WHERE no_rawat = r.no_rawat), '✓', 'X') AS check_pre,
        IF(EXISTS(SELECT 1 FROM checklist_post_operasi WHERE no_rawat = r.no_rawat), '✓', 'X') AS check_post,
        IF(EXISTS(SELECT 1 FROM skor_bromage_pasca_anestesi WHERE no_rawat = r.no_rawat), '✓', 'X') AS bromage
    FROM reg_periksa r
    JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
    LEFT JOIN kamar_inap ki ON r.no_rawat = ki.no_rawat
    LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
    LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
    RIGHT JOIN booking_operasi bo ON r.no_rawat = bo.no_rawat
    LEFT JOIN dokter d ON bo.kd_dokter = d.kd_dokter
    WHERE r.stts NOT LIKE '%batal%'
    AND bo.status NOT LIKE '%Selesai%'
    AND bo.tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'
    ORDER BY b.nm_bangsal ASC";

    $res = $koneksi->query($sql);
    if($res) {
        while($row = $res->fetch_assoc()) {
            $response['html'] .= "<tr>
                <td>{$row['nm_dokter']}</td>
                <td>{$row['no_rkm_medis']}</td>
                <td>{$row['nm_pasien']}</td>
                <td>{$row['nm_bangsal']}</td>
                <td>".fmt($row['resep'])."</td>
                <td>".fmt($row['pre_anestesi'])."</td>
                <td>".fmt($row['lap_op'])."</td>
                <td>".fmt($row['setuju'])."</td>
                <td>".fmt($row['sign_in'])."</td>
                <td>".fmt($row['sign_out'])."</td>
                <td>".fmt($row['time_out'])."</td>
                <td>".fmt($row['check_pre'])."</td>
                <td>".fmt($row['check_post'])."</td>
                <td>".fmt($row['bromage'])."</td>
            </tr>";
        }
    }

// ==========================================================
// 5. MODE: BPJS / CASEMIX (Final Upgrade - Status Rawat Added)
// ==========================================================
} elseif ($mode == 'bpjs') {
    // Definisi Judul Kolom
    $response['columns'] = [
        'No. Rawat', 'No. SEP', 'Pasien', 'Tgl Reg', 'Status Rawat', // <-- Kolom Baru
        'Asesmen IGD', 'Asesmen Ralan', 'Asesmen Ranap', 
        'Resume Medis', 'Laporan Op', 'Lab', 'Rad', 'Resep', 'CPPT', 'Status Billing'
    ];

    // Query
    $sql = "SELECT 
        r.no_rawat, 
        IFNULL(sep.no_sep, '-') as no_sep, 
        p.nm_pasien, 
        r.tgl_registrasi, 
        r.status_lanjut,
        r.status_bayar,
        
        -- 1. ASESMEN MEDIS IGD
        IF(EXISTS(SELECT 1 FROM penilaian_medis_igd WHERE no_rawat = r.no_rawat), 'Ada', 'X') AS asmed_igd,

        -- 2. ASESMEN MEDIS RALAN (Cek Seluruh Tabel Spesialis)
        COALESCE(
            (SELECT 'Umum' FROM penilaian_medis_ralan WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Mata' FROM penilaian_medis_ralan_mata WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Anak' FROM penilaian_medis_ralan_anak WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Bedah' FROM penilaian_medis_ralan_bedah WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Bedah Mulut' FROM penilaian_medis_ralan_bedah_mulut WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Kandungan' FROM penilaian_medis_ralan_kandungan WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Peny. Dalam' FROM penilaian_medis_ralan_penyakit_dalam WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'THT' FROM penilaian_medis_ralan_tht WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Saraf' FROM penilaian_medis_ralan_neurologi WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Ortho' FROM penilaian_medis_ralan_orthopedi WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Paru' FROM penilaian_medis_ralan_paru WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Jiwa' FROM penilaian_medis_ralan_psikiatrik WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Jiwa IGD' FROM penilaian_medis_ralan_gawat_darurat_psikiatri WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Kulit' FROM penilaian_medis_ralan_kulitdankelamin WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Geriatri' FROM penilaian_medis_ralan_geriatri WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Jantung' FROM penilaian_medis_ralan_jantung WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Rehab' FROM penilaian_medis_ralan_rehab_medik WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Urologi' FROM penilaian_medis_ralan_urologi WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Hemodialisa' FROM penilaian_medis_hemodialisa WHERE no_rawat = r.no_rawat LIMIT 1),
            NULL
        ) AS info_asmed_ralan,

        -- 3. ASESMEN MEDIS RANAP (Gabungan)
        COALESCE(
            (SELECT 'Umum' FROM penilaian_medis_ranap WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Kandungan' FROM penilaian_medis_ranap_kandungan WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Neonatus' FROM penilaian_medis_ranap_neonatus WHERE no_rawat = r.no_rawat LIMIT 1),
            (SELECT 'Jiwa' FROM penilaian_medis_ranap_psikiatrik WHERE no_rawat = r.no_rawat LIMIT 1),
            NULL
        ) AS info_asmed_ranap,

        -- Penunjang Lain
        IF(EXISTS(SELECT 1 FROM resume_pasien WHERE no_rawat = r.no_rawat) OR EXISTS(SELECT 1 FROM resume_pasien_ranap WHERE no_rawat = r.no_rawat), 'Ada', 'X') AS resume,
        IF(EXISTS(SELECT 1 FROM operasi WHERE no_rawat = r.no_rawat), 'Ada', 'X') AS operasi,
        IF(EXISTS(SELECT 1 FROM periksa_lab WHERE no_rawat = r.no_rawat), 'Ada', 'X') AS lab,
        IF(EXISTS(SELECT 1 FROM periksa_radiologi WHERE no_rawat = r.no_rawat), 'Ada', 'X') AS rad,
        IF(EXISTS(SELECT 1 FROM resep_obat WHERE no_rawat = r.no_rawat), 'Ada', 'X') AS resep,
        IF(EXISTS(SELECT 1 FROM pemeriksaan_ralan WHERE no_rawat = r.no_rawat) OR EXISTS(SELECT 1 FROM pemeriksaan_ranap WHERE no_rawat = r.no_rawat), 'Ada', 'X') AS cppt

    FROM reg_periksa r
    JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
    JOIN penjab pj ON r.kd_pj = pj.kd_pj
    LEFT JOIN bridging_sep sep ON r.no_rawat = sep.no_rawat
    WHERE r.tgl_registrasi BETWEEN '$tgl_awal' AND '$tgl_akhir'
    AND r.stts <> 'Batal'
    AND (pj.png_jawab LIKE '%BPJS%' OR sep.no_sep IS NOT NULL)
    ORDER BY r.tgl_registrasi DESC, r.jam_reg DESC";

    $res = $koneksi->query($sql);
    
    if ($res) {
        while($row = $res->fetch_assoc()) {
            // Styling Status Rawat
            $badge_status_rawat = ($row['status_lanjut'] == 'Ranap') 
                ? 'badge bg-warning text-dark' 
                : 'badge bg-success';

            // Logic Status Billing
            if ($row['status_bayar'] == 'Sudah Bayar') {
                $status_billing = "<span class='badge-ada'>CLOSED</span>";
            } else {
                $status_billing = "<span class='badge-kosong'>OPEN</span>";
            }

            // Logic Display Asesmen dengan Info Tambahan
            $tampil_igd = ($row['asmed_igd'] == 'Ada') ? "<span class='badge-ada'>✓</span>" : "<span class='badge-kosong'>KOSONG</span>";
            
            // Ralan
            if ($row['info_asmed_ralan'] != NULL) {
                $tampil_ralan = "<span class='badge-ada'>✓</span> <small class='text-muted'>(" . $row['info_asmed_ralan'] . ")</small>";
            } else {
                $tampil_ralan = "<span class='badge-kosong'>KOSONG</span>"; 
            }

            // Ranap (Hanya dicek jika Status = Ranap)
            if ($row['status_lanjut'] == 'Ranap') {
                if ($row['info_asmed_ranap'] != NULL) {
                    $tampil_ranap = "<span class='badge-ada'>✓</span> <small class='text-muted'>(" . $row['info_asmed_ranap'] . ")</small>";
                } else {
                    $tampil_ranap = "<span class='badge-kosong'>KOSONG</span>";
                }
            } else {
                $tampil_ranap = "<span class='text-muted'>-</span>";
            }

            $response['html'] .= "<tr>
                <td>{$row['no_rawat']}</td>
                <td class='fw-bold text-primary'>{$row['no_sep']}</td>
                <td>{$row['nm_pasien']}</td>
                <td>{$row['tgl_registrasi']}</td>
                <td class='text-center'><span class='{$badge_status_rawat}'>{$row['status_lanjut']}</span></td>
                
                <td class='text-center'>{$tampil_igd}</td>
                <td class='text-center'>{$tampil_ralan}</td>
                <td class='text-center'>{$tampil_ranap}</td>

                <td class='text-center'>".fmt($row['resume'])."</td>
                <td class='text-center'>".fmt($row['operasi'])."</td>
                <td class='text-center'>".fmt($row['lab'])."</td>
                <td class='text-center'>".fmt($row['rad'])."</td>
                <td class='text-center'>".fmt($row['resep'])."</td>
                <td class='text-center'>".fmt($row['cppt'])."</td>
                <td class='text-center'>{$status_billing}</td>
            </tr>";
        }
    } else {
        $response['html'] = "<tr><td colspan='15' class='text-danger text-center'>Error SQL: ".$koneksi->error."</td></tr>";
    }
}

echo json_encode($response);
?>