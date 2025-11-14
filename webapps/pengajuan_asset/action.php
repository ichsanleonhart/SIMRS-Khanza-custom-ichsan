<?php
/*
 * ==================================================================
 * ACTION.PHP (REFACTORED - V.16)
 * ==================================================================
 * [UPDATE V.15 - Sesi 3]:
 * - Mengimplementasikan logika 'case "update":' untuk Role Pengaju.
 * - Logika ini menangani update header, item, dan file foto
 * dalam satu transaksi database.
 */

// Komentar: Blok switch utama
switch ($action) {
    case 'save':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf($_POST['csrf_token']) && $role_login == 'pengaju') {
            
            $no_surat_pengajuan = $_POST['no_surat_pengajuan'];
            $tanggal_pengajuan = $_POST['tanggal_pengajuan'];
            $nik = $nik_login; 
            $nik_pj = $_POST['nik_pj'];
            $urgensi = $_POST['urgensi'];
            $uraian_latar_belakang = $_POST['uraian_latar_belakang'];
            $tujuan_pengajuan = $_POST['tujuan_pengajuan'];
            $target_sasaran = $_POST['target_sasaran'];
            $lokasi_pengajuan = $_POST['lokasi_pengajuan'];
            $keterangan = $_POST['keterangan'];
            
            $nama_barang_arr = $_POST['nama_barang'];
            $jumlah_arr = $_POST['jumlah'];
            $harga_arr = $_POST['harga_satuan'];
            $foto_referensi_arr = $_FILES['foto_referensi'];

            if (empty($no_surat_pengajuan) || empty($tanggal_pengajuan) || empty($nik_pj) || empty($uraian_latar_belakang) || empty($tujuan_pengajuan)) {
                $error_message = "Data header tidak lengkap."; $action = 'add'; break;
            }
            if (empty($nama_barang_arr) || empty(trim($nama_barang_arr[0]))) {
                 $error_message = "Minimal harus ada 1 item barang yang diajukan."; $action = 'add'; break;
            }

            mysqli_begin_transaction($konektor);
            try {
                $path_foto_surat = NULL;
                $upload_surat = handleFileUpload($_FILES['foto_surat_pengajuan'], $no_surat_pengajuan, PATH_FOTO_SURAT, 'SURAT_');
                if ($upload_surat['success']) {
                    $path_foto_surat = $upload_surat['path'];
                } elseif (isset($upload_surat['error'])) {
                    throw new Exception($upload_surat['error']);
                }

                $total_pengajuan = 0;
                foreach ($jumlah_arr as $index => $jumlah) {
                    if (empty($nama_barang_arr[$index])) continue;
                    $harga = (double)str_replace(['.', ','], ['', '.'], $harga_arr[$index]);
                    $jml = (double)str_replace(['.', ','], ['', '.'], $jumlah);
                    $total_pengajuan += ($jml * $harga);
                }

                $sql_header = "
                    INSERT INTO pengajuan_asset 
                    (no_surat_pengajuan, tanggal_pengajuan, nik, nik_pj, urgensi, 
                     uraian_latar_belakang, tujuan_pengajuan, target_sasaran, lokasi_pengajuan, 
                     keterangan, total_pengajuan, foto_surat_pengajuan,
                     status_approval_logum, status_approval_direktur, status_pengadaan) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Menunggu', 'Menunggu', 'Baru')
                ";
                $stmt_header = mysqli_prepare($konektor, $sql_header);
                mysqli_stmt_bind_param($stmt_header, "ssssssssssds",
                    $no_surat_pengajuan, $tanggal_pengajuan, $nik, $nik_pj, $urgensi,
                    $uraian_latar_belakang, $tujuan_pengajuan, $target_sasaran, $lokasi_pengajuan,
                    $keterangan, $total_pengajuan, $path_foto_surat
                );
                if (!mysqli_stmt_execute($stmt_header)) { throw new Exception("Gagal menyimpan header pengajuan: " . mysqli_stmt_error($stmt_header)); }
                mysqli_stmt_close($stmt_header);

                $sql_detail = "
                    INSERT INTO pengajuan_asset_detail 
                    (no_surat_pengajuan, no_urut, nama_barang, jumlah_diminta, harga_satuan, foto_referensi,
                     status_approval_logum, status_approval_direktur) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Menunggu', 'Menunggu')
                ";
                $stmt_detail = mysqli_prepare($konektor, $sql_detail);
                foreach ($nama_barang_arr as $index => $nama_barang) {
                    if (empty($nama_barang)) continue; 
                    $no_urut = $index + 1;
                    $jumlah_diminta = (double)str_replace(['.', ','], ['', '.'], $jumlah_arr[$index]);
                    $harga_satuan = (double)str_replace(['.', ','], ['', '.'], $harga_arr[$index]);
                    $path_foto_referensi = NULL;
                    $file_item = [
                        'name' => $foto_referensi_arr['name'][$index], 'type' => $foto_referensi_arr['type'][$index],
                        'tmp_name' => $foto_referensi_arr['tmp_name'][$index], 'error' => $foto_referensi_arr['error'][$index],
                        'size' => $foto_referensi_arr['size'][$index]
                    ];
                    $upload_item = handleFileUpload($file_item, $no_surat_pengajuan, PATH_FOTO_REFERENSI, 'REF_'.$no_urut.'_');
                    if ($upload_item['success']) {
                        $path_foto_referensi = $upload_item['path'];
                    } elseif (isset($upload_item['error'])) {
                        throw new Exception("Upload foto referensi baris " . $no_urut . " gagal: " . $upload_item['error']);
                    }
                    mysqli_stmt_bind_param($stmt_detail, "sisdds",
                        $no_surat_pengajuan, $no_urut, $nama_barang, $jumlah_diminta, $harga_satuan, $path_foto_referensi
                    );
                    if (!mysqli_stmt_execute($stmt_detail)) { throw new Exception("Gagal menyimpan item barang baris " . $no_urut . ": " . mysqli_stmt_error($stmt_detail)); }
                }
                mysqli_stmt_close($stmt_detail);
                mysqli_commit($konektor);
                $success_message = "Data pengajuan aset berhasil disimpan dengan nomor " . $no_surat_pengajuan . ".";
                $action = 'view';
            } catch (Exception $e) {
                mysqli_rollback($konektor); $error_message = $e->getMessage(); $action = 'add'; 
            }
        }
        break;

    // [PERBAIKAN V.15] Logika 'update' untuk Pengaju
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf($_POST['csrf_token']) && $role_login == 'pengaju') {
            
            // 1. Ambil data header
            $no_surat_pengajuan = $_POST['no_surat_pengajuan'];
            $tanggal_pengajuan = $_POST['tanggal_pengajuan'];
            $nik_pj = $_POST['nik_pj'];
            $urgensi = $_POST['urgensi'];
            $uraian_latar_belakang = $_POST['uraian_latar_belakang'];
            $tujuan_pengajuan = $_POST['tujuan_pengajuan'];
            $target_sasaran = $_POST['target_sasaran'];
            $lokasi_pengajuan = $_POST['lokasi_pengajuan'];
            $keterangan = $_POST['keterangan'];
            $foto_surat_lama = $_POST['foto_surat_lama'];
            
            // 2. Ambil data item (array)
            $no_urut_arr = $_POST['no_urut']; // Ini penting untuk UPDATE
            $nama_barang_arr = $_POST['nama_barang'];
            $jumlah_arr = $_POST['jumlah'];
            $harga_arr = $_POST['harga_satuan'];
            $foto_referensi_lama_arr = $_POST['foto_referensi_lama'];
            $foto_referensi_baru_arr = $_FILES['foto_referensi_baru'];
            
            // Otorisasi Sederhana (Cek lagi apakah masih 'Menunggu')
            $sql_auth = "SELECT nik FROM pengajuan_asset WHERE no_surat_pengajuan = ? AND nik = ? AND status_approval_logum = 'Menunggu'";
            $stmt_auth = mysqli_prepare($konektor, $sql_auth);
            mysqli_stmt_bind_param($stmt_auth, "ss", $no_surat_pengajuan, $nik_login);
            mysqli_stmt_execute($stmt_auth);
            $result_auth = mysqli_stmt_get_result($stmt_auth);
            if (mysqli_num_rows($result_auth) == 0) {
                 $error_message = "Gagal update. Data tidak ditemukan atau sudah diproses Logum.";
                 $action = 'view';
                 break;
            }
            mysqli_stmt_close($stmt_auth);

            mysqli_begin_transaction($konektor);
            try {
                // 3. Handle Foto Surat Header
                $path_foto_surat = $foto_surat_lama;
                $upload_surat = handleFileUpload($_FILES['foto_surat_pengajuan'], $no_surat_pengajuan, PATH_FOTO_SURAT, 'SURAT_');
                if ($upload_surat['success']) {
                    $path_foto_surat = $upload_surat['path'];
                    // Hapus file lama jika upload baru berhasil
                    if (!empty($foto_surat_lama) && file_exists($foto_surat_lama)) {
                        @unlink($foto_surat_lama);
                    }
                } elseif (isset($upload_surat['error'])) {
                    throw new Exception($upload_surat['error']);
                }
                
                // 4. Hitung Ulang Total Pengajuan
                $total_pengajuan = 0;
                foreach ($jumlah_arr as $index => $jumlah) {
                    if (empty($nama_barang_arr[$index])) continue;
                    $harga = (double)str_replace(['.', ','], ['', '.'], $harga_arr[$index]);
                    $jml = (double)str_replace(['.', ','], ['', '.'], $jumlah);
                    $total_pengajuan += ($jml * $harga);
                }
                
                // 5. Update Header
                $sql_header = "
                    UPDATE pengajuan_asset 
                    SET tanggal_pengajuan = ?, nik_pj = ?, urgensi = ?, 
                        uraian_latar_belakang = ?, tujuan_pengajuan = ?, target_sasaran = ?, 
                        lokasi_pengajuan = ?, keterangan = ?, total_pengajuan = ?, foto_surat_pengajuan = ?
                    WHERE no_surat_pengajuan = ? AND nik = ?
                ";
                $stmt_header = mysqli_prepare($konektor, $sql_header);
                mysqli_stmt_bind_param($stmt_header, "ssssssssdsss",
                    $tanggal_pengajuan, $nik_pj, $urgensi,
                    $uraian_latar_belakang, $tujuan_pengajuan, $target_sasaran,
                    $lokasi_pengajuan, $keterangan, $total_pengajuan, $path_foto_surat,
                    $no_surat_pengajuan, $nik_login
                );
                if (!mysqli_stmt_execute($stmt_header)) { throw new Exception("Gagal mengupdate header pengajuan: " . mysqli_stmt_error($stmt_header)); }
                mysqli_stmt_close($stmt_header);
                
                // 6. Update Detail Item (Per baris, karena kita tidak izinkan add/delete)
                $sql_detail = "
                    UPDATE pengajuan_asset_detail 
                    SET nama_barang = ?, jumlah_diminta = ?, harga_satuan = ?, foto_referensi = ?
                    WHERE no_surat_pengajuan = ? AND no_urut = ?
                ";
                $stmt_detail = mysqli_prepare($konektor, $sql_detail);

                foreach ($no_urut_arr as $index => $no_urut) {
                    $nama_barang = $nama_barang_arr[$index];
                    $jumlah_diminta = (double)str_replace(['.', ','], ['', '.'], $jumlah_arr[$index]);
                    $harga_satuan = (double)str_replace(['.', ','], ['', '.'], $harga_arr[$index]);
                    $foto_referensi_lama = $foto_referensi_lama_arr[$index];
                    
                    $path_foto_referensi = $foto_referensi_lama;
                    
                    // Cek file baru
                    $file_item = [
                        'name' => $foto_referensi_baru_arr['name'][$index], 'type' => $foto_referensi_baru_arr['type'][$index],
                        'tmp_name' => $foto_referensi_baru_arr['tmp_name'][$index], 'error' => $foto_referensi_baru_arr['error'][$index],
                        'size' => $foto_referensi_baru_arr['size'][$index]
                    ];
                    
                    $upload_item = handleFileUpload($file_item, $no_surat_pengajuan, PATH_FOTO_REFERENSI, 'REF_'.$no_urut.'_');
                    if ($upload_item['success']) {
                        $path_foto_referensi = $upload_item['path'];
                        // Hapus file lama
                        if (!empty($foto_referensi_lama) && file_exists($foto_referensi_lama)) {
                            @unlink($foto_referensi_lama);
                        }
                    } elseif (isset($upload_item['error'])) {
                        throw new Exception("Upload foto referensi baris " . ($index + 1) . " gagal: " . $upload_item['error']);
                    }
                    
                    mysqli_stmt_bind_param($stmt_detail, "sddssi",
                        $nama_barang, $jumlah_diminta, $harga_satuan, $path_foto_referensi,
                        $no_surat_pengajuan, $no_urut
                    );
                    if (!mysqli_stmt_execute($stmt_detail)) { throw new Exception("Gagal mengupdate item baris " . ($index + 1) . ": " . mysqli_stmt_error($stmt_detail)); }
                }
                mysqli_stmt_close($stmt_detail);

                // 7. Commit
                mysqli_commit($konektor);
                $success_message = "Pengajuan " . htmlspecialchars($no_surat_pengajuan, ENT_QUOTES, 'UTF-8') . " berhasil diperbarui.";
                $action = 'view';
                
            } catch (Exception $e) {
                mysqli_rollback($konektor);
                $error_message = $e->getMessage();
                $action = 'edit'; // Kembali ke form edit
                $_GET['id'] = $no_surat_pengajuan; // Pastikan ID tetap ada
            }
        }
        break;

    case 'delete':
        $no_surat_pengajuan = isset($_GET['id']) ? $_GET['id'] : '';
        $token = isset($_GET['token']) ? $_GET['token'] : '';
        if (!validate_csrf($token) || $role_login != 'pengaju' || empty($no_surat_pengajuan)) {
            $error_message = "Aksi tidak valid."; $action = 'view'; break;
        }
        $sql_auth = "SELECT pengajuan_asset.nik, pengajuan_asset.status_approval_logum FROM pengajuan_asset WHERE pengajuan_asset.no_surat_pengajuan = ? AND pengajuan_asset.nik = ?";
        $stmt_auth = mysqli_prepare($konektor, $sql_auth);
        mysqli_stmt_bind_param($stmt_auth, "ss", $no_surat_pengajuan, $nik_login);
        mysqli_stmt_execute($stmt_auth);
        $result_auth = mysqli_stmt_get_result($stmt_auth);
        $row_auth = mysqli_fetch_assoc($result_auth);
        if (!$row_auth) {
            $error_message = "Data tidak ditemukan."; $action = 'view'; break;
        }
        if ($row_auth['status_approval_logum'] != 'Menunggu') {
             $error_message = "Gagal menghapus. Data sudah diproses."; $action = 'view'; break;
        }
        mysqli_begin_transaction($konektor);
        try {
            $file_paths = [];
            $sql_get_files = "SELECT pengajuan_asset.foto_surat_pengajuan FROM pengajuan_asset WHERE pengajuan_asset.no_surat_pengajuan = ?";
            $stmt_files1 = mysqli_prepare($konektor, $sql_get_files);
            mysqli_stmt_bind_param($stmt_files1, "s", $no_surat_pengajuan);
            mysqli_stmt_execute($stmt_files1);
            $result_files1 = mysqli_stmt_get_result($stmt_files1);
            if ($row_f1 = mysqli_fetch_assoc($result_files1)) { if (!empty($row_f1['foto_surat_pengajuan'])) $file_paths[] = $row_f1['foto_surat_pengajuan']; }
            mysqli_stmt_close($stmt_files1);
            $sql_get_files2 = "SELECT pengajuan_asset_detail.foto_referensi FROM pengajuan_asset_detail WHERE pengajuan_asset_detail.no_surat_pengajuan = ?";
            $stmt_files2 = mysqli_prepare($konektor, $sql_get_files2);
            mysqli_stmt_bind_param($stmt_files2, "s", $no_surat_pengajuan);
            mysqli_stmt_execute($stmt_files2);
            $result_files2 = mysqli_stmt_get_result($stmt_files2);
            while ($row_f2 = mysqli_fetch_assoc($result_files2)) { if (!empty($row_f2['foto_referensi'])) $file_paths[] = $row_f2['foto_referensi']; }
            mysqli_stmt_close($stmt_files2);
            $sql_get_files3 = "SELECT pengajuan_asset_validasi.foto_bukti_datang FROM pengajuan_asset_validasi WHERE pengajuan_asset_validasi.no_surat_pengajuan = ?";
            $stmt_files3 = mysqli_prepare($konektor, $sql_get_files3);
            mysqli_stmt_bind_param($stmt_files3, "s", $no_surat_pengajuan);
            mysqli_stmt_execute($stmt_files3);
            $result_files3 = mysqli_stmt_get_result($stmt_files3);
            while ($row_f3 = mysqli_fetch_assoc($result_files3)) { if (!empty($row_f3['foto_bukti_datang'])) $file_paths[] = $row_f3['foto_bukti_datang']; }
            mysqli_stmt_close($stmt_files3);
            $sql_del = "DELETE FROM pengajuan_asset WHERE no_surat_pengajuan = ?";
            $stmt_del = mysqli_prepare($konektor, $sql_del);
            mysqli_stmt_bind_param($stmt_del, "s", $no_surat_pengajuan);
            mysqli_stmt_execute($stmt_del);
            mysqli_stmt_close($stmt_del);
            foreach ($file_paths as $file) { if (file_exists($file)) { @unlink($file); } }
            mysqli_commit($konektor);
            $success_message = "Pengajuan " . htmlspecialchars($no_surat_pengajuan, ENT_QUOTES, 'UTF-8') . " berhasil dihapus.";
        } catch (Exception $e) {
            mysqli_rollback($konektor); $error_message = "Gagal menghapus data: " . $e->getMessage();
        }
        $action = 'view';
        break;

    case 'save_logum':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf($_POST['csrf_token']) && $role_login == 'logum') {
            $no_surat_pengajuan = $_POST['no_surat_pengajuan'];
            $nik_approver = $nik_login;
            $no_urut_arr = $_POST['no_urut'];
            $status_item_arr = $_POST['status_item'];
            $catatan_item_arr = $_POST['catatan_item'];
            $jumlah_disetujui_arr = $_POST['jumlah_disetujui_logum']; 
            $sql_auth = "SELECT pengajuan_asset.nik_pj FROM pengajuan_asset WHERE pengajuan_asset.no_surat_pengajuan = ?";
            $stmt_auth = mysqli_prepare($konektor, $sql_auth);
            mysqli_stmt_bind_param($stmt_auth, "s", $no_surat_pengajuan);
            mysqli_stmt_execute($stmt_auth);
            $result_auth = mysqli_stmt_get_result($stmt_auth);
            $row_auth = mysqli_fetch_assoc($result_auth);
            if (!$row_auth || $row_auth['nik_pj'] != $nik_approver) {
                $error_message = "Aksi tidak diizinkan. Anda bukan PJ yang dituju."; $action = 'view'; break;
            }
            mysqli_stmt_close($stmt_auth);
            mysqli_begin_transaction($konektor);
            try {
                $total_items = 0; $total_disetujui = 0; $total_ditolak = 0;
                $sql_update_detail = "
                    UPDATE pengajuan_asset_detail 
                    SET status_approval_logum = ?, jumlah_disetujui_logum = ?, catatan_logum = ?
                    WHERE no_surat_pengajuan = ? AND no_urut = ?
                ";
                $stmt_update = mysqli_prepare($konektor, $sql_update_detail);
                foreach ($no_urut_arr as $index => $no_urut) {
                    $total_items++;
                    $status_item = $status_item_arr[$index];
                    $catatan_item = $catatan_item_arr[$index];
                    $jumlah_disetujui = (double)str_replace(['.', ','], ['', '.'], $jumlah_disetujui_arr[$index]);
                    if ($status_item == 'Ditolak') {
                        $jumlah_disetujui = 0;
                        $total_ditolak++;
                    } else { 
                        if ($jumlah_disetujui > 0) {
                             $total_disetujui++;
                        } else {
                            $status_item = 'Ditolak'; $total_ditolak++;
                        }
                    }
                    mysqli_stmt_bind_param($stmt_update, "sdssi", $status_item, $jumlah_disetujui, $catatan_item, $no_surat_pengajuan, $no_urut);
                    if (!mysqli_stmt_execute($stmt_update)) { throw new Exception("Gagal mengupdate item no. urut " . $no_urut); }
                }
                mysqli_stmt_close($stmt_update);
                $status_header_logum = 'Menunggu'; 
                if ($total_items > 0) {
                    if ($total_disetujui == $total_items) { $status_header_logum = 'Disetujui'; } 
                    elseif ($total_ditolak == $total_items) { $status_header_logum = 'Ditolak'; } 
                    elseif ($total_disetujui > 0) { $status_header_logum = 'Ditolak Sebagian'; } 
                    else { $status_header_logum = 'Ditolak'; }
                }
                $sql_update_header = "
                    UPDATE pengajuan_asset 
                    SET status_approval_logum = ?, user_approval_logum = ?, waktu_aprove_logum = NOW()
                    WHERE no_surat_pengajuan = ?
                ";
                $stmt_header = mysqli_prepare($konektor, $sql_update_header);
                mysqli_stmt_bind_param($stmt_header, "sss", $status_header_logum, $nik_approver, $no_surat_pengajuan);
                if (!mysqli_stmt_execute($stmt_header)) { throw new Exception("Gagal mengupdate header pengajuan."); }
                mysqli_stmt_close($stmt_header);
                mysqli_commit($konektor);
                $success_message = "Approval untuk surat " . htmlspecialchars($no_surat_pengajuan, ENT_QUOTES, 'UTF-8') . " berhasil disimpan.";
                $action = 'view';
            } catch (Exception $e) {
                mysqli_rollback($konektor); $error_message = $e->getMessage(); $action = 'detail_logum'; $_GET['id'] = $no_surat_pengajuan; 
            }
        }
        break;

    case 'validate_logum':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf($_POST['csrf_token']) && $role_login == 'logum') {
            
            $no_surat_pengajuan = $_POST['no_surat_pengajuan'];
            $no_urut_detail = $_POST['no_urut_detail'];
            $jumlah_datang = (double)str_replace(['.', ','], ['', '.'], $_POST['jumlah_datang']);
            $catatan_validasi = $_POST['catatan_validasi'];
            $nik_validator = $nik_login;
            
            // [BARU V.16] Ambil data harga realisasi dari modal
            $harga_realisasi = (double)str_replace(['.', ','], ['', '.'], $_POST['harga_realisasi_satuan']);

            // Keamanan & Validasi
            if ($jumlah_datang <= 0) {
                 $error_message = "Jumlah datang tidak boleh nol atau minus.";
                 $action = 'view';
                 break;
            }
            // [BARU V.16] Validasi harga realisasi
            if ($harga_realisasi <= 0) {
                 $error_message = "Harga realisasi tidak boleh nol atau minus. Masukkan harga satuan barang yang sebenarnya.";
                 $action = 'view';
                 break;
            }

            mysqli_begin_transaction($konektor);
            try {
                // 1. Ambil data sisa barang
                $sql_get_sisa = "
                    SELECT pengajuan_asset_detail.jumlah_disetujui_direktur, pengajuan_asset_detail.jumlah_sudah_divalidasi 
                    FROM pengajuan_asset_detail 
                    WHERE pengajuan_asset_detail.no_surat_pengajuan = ? AND pengajuan_asset_detail.no_urut = ?
                ";
                $stmt_sisa = mysqli_prepare($konektor, $sql_get_sisa);
                mysqli_stmt_bind_param($stmt_sisa, "si", $no_surat_pengajuan, $no_urut_detail);
                mysqli_stmt_execute($stmt_sisa);
                $result_sisa = mysqli_stmt_get_result($stmt_sisa);
                $row_sisa = mysqli_fetch_assoc($result_sisa);
                mysqli_stmt_close($stmt_sisa);
                
                if (!$row_sisa) {
                     throw new Exception("Item barang tidak ditemukan.");
                }

                $sisa = (double)$row_sisa['jumlah_disetujui_direktur'] - (double)$row_sisa['jumlah_sudah_divalidasi'];
                
                // 2. Cek apakah jumlah datang melebihi sisa
                if ($jumlah_datang > $sisa) {
                     throw new Exception("Jumlah datang (" . $jumlah_datang . ") melebihi sisa barang yang belum divalidasi (" . $sisa . ").");
                }

                // 3. Handle upload foto bukti
                $path_foto_validasi = NULL;
                $upload_validasi = handleFileUpload($_FILES['foto_bukti_datang'], $no_surat_pengajuan, PATH_FOTO_VALIDASI, 'VALIDASI_'.$no_urut_detail.'_');
                if ($upload_validasi['success']) {
                    $path_foto_validasi = $upload_validasi['path'];
                } elseif (isset($upload_validasi['error'])) {
                    throw new Exception($upload_validasi['error']);
                }

                // 4. Insert ke tabel log validasi (pengajuan_asset_validasi)
                // [PERBAIKAN V.16] Menambahkan kolom harga_realisasi_satuan
                $sql_insert_validasi = "
                    INSERT INTO pengajuan_asset_validasi 
                    (no_surat_pengajuan, no_urut_detail, tanggal_validasi, jumlah_datang, 
                     harga_realisasi_satuan, 
                     user_validasi_logum, catatan_validasi, foto_bukti_datang)
                    VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)
                ";
                $stmt_val = mysqli_prepare($konektor, $sql_insert_validasi);
                // [PERBAIKAN V.16] Tipe diubah ke 'siddsss'
                mysqli_stmt_bind_param($stmt_val, "siddsss", 
                    $no_surat_pengajuan, $no_urut_detail, $jumlah_datang, 
                    $harga_realisasi,
                    $nik_validator, $catatan_validasi, $path_foto_validasi
                );
                
                if (!mysqli_stmt_execute($stmt_val)) {
                    throw new Exception("Gagal menyimpan log validasi: " . mysqli_stmt_error($stmt_val));
                }
                mysqli_stmt_close($stmt_val);

                // 5. Update jumlah total yang sudah divalidasi di tabel detail
                $sql_update_detail = "
                    UPDATE pengajuan_asset_detail 
                    SET jumlah_sudah_divalidasi = jumlah_sudah_divalidasi + ?
                    WHERE no_surat_pengajuan = ? AND no_urut = ?
                ";
                $stmt_update_det = mysqli_prepare($konektor, $sql_update_detail);
                mysqli_stmt_bind_param($stmt_update_det, "dsi", $jumlah_datang, $no_surat_pengajuan, $no_urut_detail);
                
                if (!mysqli_stmt_execute($stmt_update_det)) {
                    throw new Exception("Gagal mengupdate total validasi: " . mysqli_stmt_error($stmt_update_det));
                }
                mysqli_stmt_close($stmt_update_det);
                
                // 6. Update status header pengadaan
                updateStatusPengadaanHeader($konektor, $no_surat_pengajuan);
                
                // 7. Commit
                mysqli_commit($konektor);
                $success_message = "Validasi barang datang berhasil disimpan.";
                $action = 'view';

            } catch (Exception $e) {
                mysqli_rollback($konektor);
                $error_message = $e->getMessage();
                $action = 'view';
            }
        }
        break;

    case 'save_direktur':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf($_POST['csrf_token']) && $role_login == 'direktur') {
            $no_surat_pengajuan = $_POST['no_surat_pengajuan'];
            $nik_approver = $nik_login;
            $no_urut_arr = $_POST['no_urut'];
            $status_item_arr = $_POST['status_item'];
            $catatan_item_arr = $_POST['catatan_item'];
            $jumlah_disetujui_arr = $_POST['jumlah_disetujui'];
            mysqli_begin_transaction($konektor);
            try {
                $total_items = 0; $total_disetujui = 0; $total_ditolak = 0; $total_nilai_disetujui = 0;
                $sql_update_detail = "
                    UPDATE pengajuan_asset_detail 
                    SET status_approval_direktur = ?, jumlah_disetujui_direktur = ?, catatan_direktur = ?
                    WHERE no_surat_pengajuan = ? AND no_urut = ?
                ";
                $stmt_update = mysqli_prepare($konektor, $sql_update_detail);
                foreach ($no_urut_arr as $index => $no_urut) {
                    $total_items++;
                    $status_item = $status_item_arr[$index];
                    $catatan_item = $catatan_item_arr[$index];
                    $jumlah_disetujui = 0;
                    if ($status_item == 'Disetujui') {
                        $total_disetujui++;
                        $jumlah_disetujui = (double)str_replace(['.', ','], ['', '.'], $jumlah_disetujui_arr[$index]);
                        $sql_get_harga = "SELECT pengajuan_asset_detail.harga_satuan FROM pengajuan_asset_detail WHERE pengajuan_asset_detail.no_surat_pengajuan = ? AND pengajuan_asset_detail.no_urut = ?";
                        $stmt_harga = mysqli_prepare($konektor, $sql_get_harga);
                        mysqli_stmt_bind_param($stmt_harga, "si", $no_surat_pengajuan, $no_urut);
                        mysqli_stmt_execute($stmt_harga);
                        $result_harga = mysqli_stmt_get_result($stmt_harga);
                        $row_harga = mysqli_fetch_assoc($result_harga);
                        mysqli_stmt_close($stmt_harga);
                        $total_nilai_disetujui += $jumlah_disetujui * (double)$row_harga['harga_satuan'];
                    } elseif ($status_item == 'Ditolak') {
                        $total_ditolak++;
                        $jumlah_disetujui = 0;
                    }
                    mysqli_stmt_bind_param($stmt_update, "sdssi", $status_item, $jumlah_disetujui, $catatan_item, $no_surat_pengajuan, $no_urut);
                    if (!mysqli_stmt_execute($stmt_update)) { throw new Exception("Gagal mengupdate item no. urut " . $no_urut); }
                }
                mysqli_stmt_close($stmt_update);
                $status_header_direktur = 'Menunggu'; 
                if ($total_items > 0) {
                    if ($total_disetujui == $total_items) { $status_header_direktur = 'Disetujui'; } 
                    elseif ($total_ditolak == $total_items) { $status_header_direktur = 'Ditolak'; } 
                    elseif ($total_disetujui > 0) { $status_header_direktur = 'Ditolak Sebagian'; } 
                    else { $status_header_direktur = 'Ditolak'; }
                }
                $status_pengadaan = 'Proses Pengadaan';
                if ($total_disetujui == 0) { $status_pengadaan = 'Baru'; }
                $sql_update_header = "
                    UPDATE pengajuan_asset 
                    SET status_approval_direktur = ?, user_approval_direktur = ?, waktu_approval_direktur = NOW(),
                        status_pengadaan = ?, total_disetujui = ?
                    WHERE no_surat_pengajuan = ?
                ";
                $stmt_header = mysqli_prepare($konektor, $sql_update_header);
                mysqli_stmt_bind_param($stmt_header, "sssds", $status_header_direktur, $nik_approver, $status_pengadaan, $total_nilai_disetujui, $no_surat_pengajuan);
                if (!mysqli_stmt_execute($stmt_header)) { throw new Exception("Gagal mengupdate header pengajuan."); }
                mysqli_stmt_close($stmt_header);
                updateStatusPengadaanHeader($konektor, $no_surat_pengajuan);
                mysqli_commit($konektor);
                $success_message = "Approval Direktur untuk surat " . htmlspecialchars($no_surat_pengajuan, ENT_QUOTES, 'UTF-8') . " berhasil disimpan.";
                $action = 'view';
            } catch (Exception $e) {
                mysqli_rollback($konektor); $error_message = $e->getMessage(); $action = 'detail_direktur'; $_GET['id'] = $no_surat_pengajuan; 
            }
        }
        break;

    case 'view':
    case 'add':
    case 'edit':
    case 'detail_logum':
    case 'detail_direktur':
    case 'detail_lengkap':
        break;
    
    default:
        $action = 'view';
        break;
}
?>