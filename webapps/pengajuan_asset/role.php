<?php
/*
 * ==================================================================
 * ROLE.PHP (REFACTORED - V.15 / Sesi 3)
 * ==================================================================
 * [UPDATE V.15 - Sesi 3]:
 * - Mengisi logika 'action=edit' untuk Role Pengaju.
 * - Menambahkan otorisasi di 'action=edit' (hanya milik sendiri & status 'Menunggu').
 */

// 4. LOGIKA PENGAMBILAN DATA (SESUAI ROLE)
// -----------------------------------------------------------------------------
$list_pengajuan_pengaju = [];
$pegawai_list_logum = [];
$data_form_no_surat = '';
$data_form_tanggal = date('Y-m-d');
$list_approval_logum = [];
$list_validasi_logum = []; 
$list_riwayat_logum = []; 
$list_approval_direktur = [];
$list_riwayat_direktur = [];
$data_detail_header = null;
$data_detail_items = [];
$data_detail_validasi = [];
$is_locked_by_validation = false; 

// ==========================================================
// ROLE: PENGAJU
// ==========================================================
if ($role_login == 'pengaju') {
    if ($action === 'view') {
        $sql_tampil = "
            SELECT 
                pengajuan_asset.no_surat_pengajuan, pengajuan_asset.tanggal_pengajuan, pengajuan_asset.uraian_latar_belakang, 
                pengajuan_asset.total_pengajuan, pengajuan_asset.total_disetujui, pengajuan_asset.status_approval_logum, 
                pengajuan_asset.status_approval_direktur, pengajuan_asset.status_pengadaan,
                pj_logum.nama AS nama_pj_logum
            FROM pengajuan_asset 
            INNER JOIN pegawai AS pj_logum ON pengajuan_asset.nik_pj = pj_logum.nik
            WHERE pengajuan_asset.nik = ? 
            ORDER BY pengajuan_asset.tanggal_pengajuan DESC, pengajuan_asset.no_surat_pengajuan DESC
        ";
        $stmt_tampil = mysqli_prepare($konektor, $sql_tampil);
        mysqli_stmt_bind_param($stmt_tampil, "s", $nik_login);
        mysqli_stmt_execute($stmt_tampil);
        $result_tampil = mysqli_stmt_get_result($stmt_tampil);
        while ($row = mysqli_fetch_assoc($result_tampil)) {
            $list_pengajuan_pengaju[] = $row;
        }
        mysqli_stmt_close($stmt_tampil);
    }
    
    // Logika untuk 'add' atau 'edit'
    if ($action === 'add' || $action === 'edit') {
        // Ambil daftar petugas logistik umum
        $sql_pj = "
            SELECT pegawai.nik, pegawai.nama 
            FROM pegawai 
            INNER JOIN user ON pegawai.nik = AES_DECRYPT(user.id_user, 'nur')
            WHERE user.ipsrs_barang = 'true' AND pegawai.stts_aktif = 'AKTIF'
            ORDER BY pegawai.nama
        ";
        $result_pj = mysqli_query($konektor, $sql_pj);
        while($row_pj = mysqli_fetch_assoc($result_pj)) {
            $pegawai_list_logum[] = $row_pj;
        }
        
        // Jika 'add', generate nomor baru
        if ($action === 'add') {
            $data_form_no_surat = autoNomorSurat($konektor, date('Y-m-d'));
        }
        
        // [PERBAIKAN V.15] Jika 'edit', ambil data untuk form
        if ($action === 'edit' && isset($_GET['id'])) {
            $no_surat_edit = $_GET['id'];
            
            // 1. Ambil data header DENGAN OTORISASI
            $sql_edit_h = "
                SELECT * FROM pengajuan_asset 
                WHERE no_surat_pengajuan = ? 
                AND nik = ? 
                AND status_approval_logum = 'Menunggu'
            ";
            $stmt_edit_h = mysqli_prepare($konektor, $sql_edit_h);
            mysqli_stmt_bind_param($stmt_edit_h, "ss", $no_surat_edit, $nik_login);
            mysqli_stmt_execute($stmt_edit_h);
            $result_edit_h = mysqli_stmt_get_result($stmt_edit_h);
            $data_detail_header = mysqli_fetch_assoc($result_edit_h);
            mysqli_stmt_close($stmt_edit_h);

            // 2. Jika data tidak ada atau tidak boleh diedit, tendang kembali
            if (!$data_detail_header) {
                $error_message = "Data tidak ditemukan, atau tidak dapat diedit lagi karena sudah diproses Logum.";
                $action = 'view';
            } else {
                // 3. Ambil data item
                $sql_edit_d = "SELECT * FROM pengajuan_asset_detail WHERE no_surat_pengajuan = ? ORDER BY no_urut";
                $stmt_edit_d = mysqli_prepare($konektor, $sql_edit_d);
                mysqli_stmt_bind_param($stmt_edit_d, "s", $no_surat_edit);
                mysqli_stmt_execute($stmt_edit_d);
                $result_edit_d = mysqli_stmt_get_result($stmt_edit_d);
                while($row_d = mysqli_fetch_assoc($result_edit_d)) {
                    $data_detail_items[] = $row_d;
                }
                mysqli_stmt_close($stmt_edit_d);
            }
        }
    }
}

// ==========================================================
// ROLE: LOGISTIK UMUM
// ==========================================================
if ($role_login == 'logum') {
    if ($action === 'view') {
        // 1. Ambil daftar pengajuan yang perlu di-approve (Menu 1)
        $sql_logum = "
            SELECT 
                pengajuan_asset.no_surat_pengajuan, pengajuan_asset.tanggal_pengajuan, 
                pegawai_pengaju.nama AS nama_pengaju,
                pengajuan_asset.uraian_latar_belakang, pengajuan_asset.total_pengajuan
            FROM pengajuan_asset
            INNER JOIN pegawai AS pegawai_pengaju ON pengajuan_asset.nik = pegawai_pengaju.nik
            WHERE 
                pengajuan_asset.nik_pj = ? AND 
                pengajuan_asset.status_approval_logum = 'Menunggu'
            ORDER BY pengajuan_asset.tanggal_pengajuan ASC
        ";
        $stmt_logum = mysqli_prepare($konektor, $sql_logum);
        mysqli_stmt_bind_param($stmt_logum, "s", $nik_login);
        mysqli_stmt_execute($stmt_logum);
        $result_logum = mysqli_stmt_get_result($stmt_logum);
        while ($row = mysqli_fetch_assoc($result_logum)) {
            $list_approval_logum[] = $row;
        }
        mysqli_stmt_close($stmt_logum);
        
        // 2. Ambil daftar barang yang perlu divalidasi (Menu 2)
        $sql_validasi = "
            SELECT 
                pengajuan_asset_detail.no_surat_pengajuan, pengajuan_asset_detail.no_urut,
                pengajuan_asset_detail.nama_barang, pengajuan_asset_detail.jumlah_diminta,
                pengajuan_asset_detail.jumlah_disetujui_direktur, 
                (pengajuan_asset_detail.harga_satuan * pengajuan_asset_detail.jumlah_disetujui_direktur) AS total_nilai_disetujui_direktur,
                pengajuan_asset_detail.jumlah_sudah_divalidasi,
                (pengajuan_asset_detail.jumlah_disetujui_direktur - pengajuan_asset_detail.jumlah_sudah_divalidasi) AS sisa
            FROM pengajuan_asset_detail
            INNER JOIN pengajuan_asset ON pengajuan_asset_detail.no_surat_pengajuan = pengajuan_asset.no_surat_pengajuan
            WHERE 
                pengajuan_asset.nik_pj = ? AND
                pengajuan_asset_detail.status_approval_direktur = 'Disetujui' AND
                (pengajuan_asset_detail.jumlah_disetujui_direktur > pengajuan_asset_detail.jumlah_sudah_divalidasi)
            ORDER BY 
                pengajuan_asset_detail.no_surat_pengajuan, pengajuan_asset_detail.no_urut
        ";
        $stmt_val = mysqli_prepare($konektor, $sql_validasi);
        mysqli_stmt_bind_param($stmt_val, "s", $nik_login);
        mysqli_stmt_execute($stmt_val);
        $result_val = mysqli_stmt_get_result($stmt_val);
        while ($row_val = mysqli_fetch_assoc($result_val)) {
            $list_validasi_logum[] = $row_val;
        }
        mysqli_stmt_close($stmt_val);
        
        // 3. Ambil daftar riwayat yang bisa diedit (Menu 3)
        $sql_riwayat = "
            SELECT 
                pengajuan_asset.no_surat_pengajuan, pengajuan_asset.tanggal_pengajuan, 
                pegawai_pengaju.nama AS nama_pengaju,
                pengajuan_asset.status_approval_logum,
                pengajuan_asset.status_approval_direktur
            FROM pengajuan_asset
            INNER JOIN pegawai AS pegawai_pengaju ON pengajuan_asset.nik = pegawai_pengaju.nik
            WHERE 
                pengajuan_asset.nik_pj = ? AND 
                pengajuan_asset.status_approval_logum != 'Menunggu' AND
                pengajuan_asset.status_approval_direktur = 'Menunggu'
            ORDER BY pengajuan_asset.waktu_aprove_logum DESC
        ";
        $stmt_riwayat = mysqli_prepare($konektor, $sql_riwayat);
        mysqli_stmt_bind_param($stmt_riwayat, "s", $nik_login);
        mysqli_stmt_execute($stmt_riwayat);
        $result_riwayat = mysqli_stmt_get_result($stmt_riwayat);
        while ($row_riwayat = mysqli_fetch_assoc($result_riwayat)) {
            $list_riwayat_logum[] = $row_riwayat;
        }
        mysqli_stmt_close($stmt_riwayat);
    }
    
    // Logika untuk halaman detail approval Logum (action=detail_logum)
    if ($action === 'detail_logum' && isset($_GET['id'])) {
        $no_surat = $_GET['id'];
        
        $sql_h = "
            SELECT 
                pengajuan_asset.*,
                pegawai_pengaju.nama AS nama_pengaju,
                pegawai_pengaju.jbtn AS jbtn_pengaju,
                pegawai_pengaju.departemen AS dep_pengaju
            FROM pengajuan_asset
            INNER JOIN pegawai AS pegawai_pengaju ON pengajuan_asset.nik = pegawai_pengaju.nik
            WHERE 
                pengajuan_asset.no_surat_pengajuan = ? AND 
                pengajuan_asset.nik_pj = ? AND
                pengajuan_asset.status_approval_direktur = 'Menunggu' 
        ";
        $stmt_h = mysqli_prepare($konektor, $sql_h);
        mysqli_stmt_bind_param($stmt_h, "ss", $no_surat, $nik_login);
        mysqli_stmt_execute($stmt_h);
        $result_h = mysqli_stmt_get_result($stmt_h);
        $data_detail_header = mysqli_fetch_assoc($result_h);
        mysqli_stmt_close($stmt_h);
        
        if (!$data_detail_header) {
            $error_message = "Pengajuan tidak ditemukan, atau sudah dikunci (diapprove Direktur), atau bukan ditujukan untuk Anda.";
            $action = 'view';
        } else {
            $sql_d = "SELECT * FROM pengajuan_asset_detail WHERE no_surat_pengajuan = ? ORDER BY no_urut";
            $stmt_d = mysqli_prepare($konektor, $sql_d);
            mysqli_stmt_bind_param($stmt_d, "s", $no_surat);
            mysqli_stmt_execute($stmt_d);
            $result_d = mysqli_stmt_get_result($stmt_d);
            while($row_d = mysqli_fetch_assoc($result_d)) {
                $data_detail_items[] = $row_d;
            }
            mysqli_stmt_close($stmt_d);
        }
    }
}

// ==========================================================
// ROLE: DIREKTUR
// ==========================================================
if ($role_login == 'direktur') {
    if ($action === 'view') {
        // 1. Ambil daftar pengajuan yang perlu di-approve (Menu 1)
        $sql_dir = "
            SELECT 
                pengajuan_asset.no_surat_pengajuan, pengajuan_asset.tanggal_pengajuan, 
                pegawai_pengaju.nama AS nama_pengaju,
                pegawai_pj.nama AS nama_pj_logum,
                pengajuan_asset.uraian_latar_belakang,
                pengajuan_asset.total_pengajuan,
                pengajuan_asset.status_approval_logum
            FROM pengajuan_asset
            INNER JOIN pegawai AS pegawai_pengaju ON pengajuan_asset.nik = pegawai_pengaju.nik
            INNER JOIN pegawai AS pegawai_pj ON pengajuan_asset.nik_pj = pegawai_pj.nik
            WHERE 
                (pengajuan_asset.status_approval_logum = 'Disetujui' OR pengajuan_asset.status_approval_logum = 'Ditolak Sebagian') AND
                pengajuan_asset.status_approval_direktur = 'Menunggu'
            ORDER BY pengajuan_asset.tanggal_pengajuan ASC
        ";
        $stmt_dir = mysqli_prepare($konektor, $sql_dir);
        mysqli_stmt_execute($stmt_dir);
        $result_dir = mysqli_stmt_get_result($stmt_dir);
        while ($row = mysqli_fetch_assoc($result_dir)) {
            $list_approval_direktur[] = $row;
        }
        mysqli_stmt_close($stmt_dir);
        
        // 2. Ambil daftar riwayat yang bisa diedit (Menu 2)
        $sql_riwayat_dir = "
            SELECT 
                pengajuan_asset.no_surat_pengajuan, pengajuan_asset.tanggal_pengajuan, 
                pegawai_pengaju.nama AS nama_pengaju,
                pegawai_pj.nama AS nama_pj_logum,
                pengajuan_asset.status_approval_direktur,
                pengajuan_asset.total_disetujui
            FROM pengajuan_asset
            INNER JOIN pegawai AS pegawai_pengaju ON pengajuan_asset.nik = pegawai_pengaju.nik
            INNER JOIN pegawai AS pegawai_pj ON pengajuan_asset.nik_pj = pegawai_pj.nik
            WHERE 
                pengajuan_asset.status_approval_direktur != 'Menunggu' AND
                pengajuan_asset.status_pengadaan = 'Proses Pengadaan'
            ORDER BY pengajuan_asset.waktu_approval_direktur DESC
        ";
        $stmt_riwayat_dir = mysqli_prepare($konektor, $sql_riwayat_dir);
        mysqli_stmt_execute($stmt_riwayat_dir);
        $result_riwayat_dir = mysqli_stmt_get_result($stmt_riwayat_dir);
        while ($row_riwayat_dir = mysqli_fetch_assoc($result_riwayat_dir)) {
            $list_riwayat_direktur[] = $row_riwayat_dir;
        }
        mysqli_stmt_close($stmt_riwayat_dir);
    }
    
    // Logika untuk halaman detail approval Direktur (action=detail_direktur)
    if ($action === 'detail_direktur' && isset($_GET['id'])) {
        $no_surat = $_GET['id'];
        
        // Ambil data header
        $sql_h = "
            SELECT 
                pengajuan_asset.*,
                pegawai_pengaju.nama AS nama_pengaju,
                pegawai_pengaju.jbtn AS jbtn_pengaju,
                pegawai_pengaju.departemen AS dep_pengaju,
                pegawai_pj.nama AS nama_pj_logum,
                pegawai_approver_logum.nama AS nama_approver_logum
            FROM pengajuan_asset
            INNER JOIN pegawai AS pegawai_pengaju ON pengajuan_asset.nik = pegawai_pengaju.nik
            INNER JOIN pegawai AS pegawai_pj ON pengajuan_asset.nik_pj = pegawai_pj.nik
            LEFT JOIN pegawai AS pegawai_approver_logum ON pengajuan_asset.user_approval_logum = pegawai_approver_logum.nik
            WHERE 
                pengajuan_asset.no_surat_pengajuan = ? AND
                (pengajuan_asset.status_approval_logum = 'Disetujui' OR pengajuan_asset.status_approval_logum = 'Ditolak Sebagian')
        ";
        $stmt_h = mysqli_prepare($konektor, $sql_h);
        mysqli_stmt_bind_param($stmt_h, "s", $no_surat);
        mysqli_stmt_execute($stmt_h);
        $result_h = mysqli_stmt_get_result($stmt_h);
        $data_detail_header = mysqli_fetch_assoc($result_h);
        mysqli_stmt_close($stmt_h);

        if (!$data_detail_header) {
            $error_message = "Pengajuan tidak ditemukan atau belum diproses Logistik Umum.";
            $action = 'view';
        } else {
            // Ambil data item
            $sql_d = "SELECT * FROM pengajuan_asset_detail WHERE no_surat_pengajuan = ? ORDER BY no_urut";
            $stmt_d = mysqli_prepare($konektor, $sql_d);
            mysqli_stmt_bind_param($stmt_d, "s", $no_surat);
            mysqli_stmt_execute($stmt_d);
            $result_d = mysqli_stmt_get_result($stmt_d);
            while($row_d = mysqli_fetch_assoc($result_d)) {
                $data_detail_items[] = $row_d;
            }
            mysqli_stmt_close($stmt_d);
            
            // Cek apakah approval sudah dikunci oleh validasi Logum
            $sql_check_val = "SELECT SUM(pengajuan_asset_detail.jumlah_sudah_divalidasi) AS total_validasi FROM pengajuan_asset_detail WHERE pengajuan_asset_detail.no_surat_pengajuan = ?";
            $stmt_val = mysqli_prepare($konektor, $sql_check_val);
            mysqli_stmt_bind_param($stmt_val, "s", $no_surat);
            mysqli_stmt_execute($stmt_val);
            $result_val = mysqli_stmt_get_result($stmt_val);
            $row_val = mysqli_fetch_assoc($result_val);
            mysqli_stmt_close($stmt_val);
            
            if ($row_val && (double)$row_val['total_validasi'] > 0) {
                $is_locked_by_validation = true; 
            }
        }
    }
}

// ==========================================================
// LOGIKA UNIVERSAL (SEMUA ROLE)
// ==========================================================
if ($action === 'detail_lengkap' && isset($_GET['id'])) {
    $no_surat = $_GET['id'];
    
    $sql_h = "
        SELECT 
            pengajuan_asset.*,
            pegawai_pengaju.nama AS nama_pengaju,
            pegawai_pengaju.jbtn AS jbtn_pengaju,
            pegawai_pengaju.departemen AS dep_pengaju,
            pegawai_pj.nama AS nama_pj_logum,
            pegawai_approver_logum.nama AS nama_approver_logum,
            pegawai_approver_direktur.nama AS nama_approver_direktur
        FROM pengajuan_asset
        INNER JOIN pegawai AS pegawai_pengaju ON pengajuan_asset.nik = pegawai_pengaju.nik
        INNER JOIN pegawai AS pegawai_pj ON pengajuan_asset.nik_pj = pegawai_pj.nik
        LEFT JOIN pegawai AS pegawai_approver_logum ON pengajuan_asset.user_approval_logum = pegawai_approver_logum.nik
        LEFT JOIN pegawai AS pegawai_approver_direktur ON pengajuan_asset.user_approval_direktur = pegawai_approver_direktur.nik
        WHERE 
            pengajuan_asset.no_surat_pengajuan = ?
    ";
    $stmt_h = mysqli_prepare($konektor, $sql_h);
    mysqli_stmt_bind_param($stmt_h, "s", $no_surat);
    mysqli_stmt_execute($stmt_h);
    $result_h = mysqli_stmt_get_result($stmt_h);
    $data_detail_header = mysqli_fetch_assoc($result_h);
    mysqli_stmt_close($stmt_h);

    if (!$data_detail_header) {
        $error_message = "Pengajuan tidak ditemukan.";
        $action = 'view';
    } else {
        $is_owner = ($data_detail_header['nik'] == $nik_login);
        $is_pj = ($data_detail_header['nik_pj'] == $nik_login);
        $is_direktur = ($role_login == 'direktur');
        
        if (!$is_owner && !$is_pj && !$is_direktur) {
             $error_message = "Akses ditolak. Anda tidak memiliki wewenang untuk melihat detail surat ini.";
             $action = 'view';
             $data_detail_header = null; 
        } else {
            // Otorisasi sukses, ambil data item
            $sql_d = "SELECT * FROM pengajuan_asset_detail WHERE no_surat_pengajuan = ? ORDER BY no_urut";
            $stmt_d = mysqli_prepare($konektor, $sql_d);
            mysqli_stmt_bind_param($stmt_d, "s", $no_surat);
            mysqli_stmt_execute($stmt_d);
            $result_d = mysqli_stmt_get_result($stmt_d);
            while($row_d = mysqli_fetch_assoc($result_d)) {
                $data_detail_items[] = $row_d;
            }
            mysqli_stmt_close($stmt_d);
            
            // Otorisasi sukses, ambil data log validasi (realisasi)
            $sql_v = "SELECT * FROM pengajuan_asset_validasi WHERE no_surat_pengajuan = ? ORDER BY tanggal_validasi DESC";
            $stmt_v = mysqli_prepare($konektor, $sql_v);
            mysqli_stmt_bind_param($stmt_v, "s", $no_surat);
            mysqli_stmt_execute($stmt_v);
            $result_v = mysqli_stmt_get_result($stmt_v);
            while($row_v = mysqli_fetch_assoc($result_v)) {
                $data_detail_validasi[$row_v['no_urut_detail']][] = $row_v;
            }
            mysqli_stmt_close($stmt_v);
        }
    }
}
?>