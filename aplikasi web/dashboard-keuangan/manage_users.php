<?php
/*
 * File: manage_users.php
 * Deskripsi: Utility untuk Manage User & Roles (Super Admin Only)
 */

// --- KONFIGURASI AKSES ---
// Masukkan NIK/Username Super Admin yang diizinkan mengakses halaman ini
$super_admins = ['ichsanleonhart']; 

session_start();
require_once('config/koneksi.php');

// Cek apakah user yang login adalah Super Admin
$current_user = isset($_SESSION['username']) ? $_SESSION['username'] : '';
// Atau jika session menggunakan field lain, sesuaikan. 
// Misal: if (!in_array($current_user, $super_admins) && $_SESSION['role'] !== 'Admin') { ... }

if (!in_array($current_user, $super_admins) && (!isset($_SESSION['role']) || $_SESSION['role'] != 'Super Admin')) {
    die("<div style='text-align:center; margin-top:50px;'><h3>⛔ AKSES DITOLAK</h3><p>Halaman ini hanya untuk Super Admin.</p><a href='index.php'>Kembali</a></div>");
}

// PROSES FORM (SIMPAN/HAPUS)
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'];
    
    if ($act == 'save') {
        $username = $koneksi->real_escape_string($_POST['username']);
        $role     = $koneksi->real_escape_string($_POST['role']);
        $cap      = $koneksi->real_escape_string($_POST['cap']);
        $module   = $koneksi->real_escape_string($_POST['module']);

        if (!empty($username) && !empty($role)) {
            // Menggunakan REPLACE INTO agar jika user sudah punya role, akan di-update
            $sql = "REPLACE INTO roles (username, role, cap, module) VALUES ('$username', '$role', '$cap', '$module')";
            if ($koneksi->query($sql)) {
                $msg = "<div class='alert alert-success'>Data user <b>$username</b> berhasil disimpan/diupdate.</div>";
            } else {
                $msg = "<div class='alert alert-danger'>Gagal menyimpan: " . $koneksi->error . "</div>";
            }
        }
    } elseif ($act == 'delete') {
        $username = $koneksi->real_escape_string($_POST['username_del']);
        $koneksi->query("DELETE FROM roles WHERE username='$username'");
        $msg = "<div class='alert alert-warning'>User <b>$username</b> telah dihapus dari hak akses.</div>";
    }
}

$page_title = "Manajemen User & Role";
require_once('includes/header.php');
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><i class="fas fa-users-cog me-2"></i>Manajemen User & Role</h1>
    
    <?= $msg; ?>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow border-left-primary">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tambah / Edit User</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="act" value="save">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Cari Pegawai (Username/NIP)</label>
                            <select class="form-select" id="select_pegawai" name="username" required>
                                <option value="">-- Cari Nama/NIK --</option>
                            </select>
                            <small class="text-muted">Ketik Nama atau NIK Pegawai dari database Khanza.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Role Access</label>
                            <select class="form-select" name="role" required>
                                <option value="">-- Pilih Role --</option>
                                <option value="Admin">Admin (Full Access)</option>
                                <option value="Manajemen">Manajemen</option>
                                <option value="Apotek">Apotek</option>
                                <option value="Keuangan">Keuangan</option>
                                <option value="Casemix">Casemix</option>
                                <option value="Medis">Medis</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Capability (Cap)</label>
                            <input type="text" class="form-control" name="cap" placeholder="Contoh: read,write,delete">
                            <small class="text-muted">Opsional: String untuk validasi khusus.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Allowed Modules</label>
                            <textarea class="form-control" name="module" rows="3" placeholder="Contoh: ralan,ranap,apotek"></textarea>
                            <small class="text-muted">Opsional: Daftar modul yang diizinkan.</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i> Simpan User</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar User Terdaftar (Tabel Roles)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="tableUsers" width="100%" cellspacing="0">
                            <thead class="table-light">
                                <tr>
                                    <th>Username (NIK)</th>
                                    <th>Nama Pegawai</th>
                                    <th>Role</th>
                                    <th>Modules</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Join ke tabel pegawai untuk mengambil nama asli
                                $q = $koneksi->query("SELECT r.*, p.nama FROM roles r LEFT JOIN pegawai p ON r.username = p.nik ORDER BY r.role ASC, p.nama ASC");
                                while($row = $q->fetch_assoc()) {
                                    $username_esc = htmlspecialchars($row['username']);
                                    $nama_esc = htmlspecialchars($row['nama'] ?? 'User Tidak Dikenal (Bukan Pegawai)');
                                    $role_badge = 'bg-secondary';
                                    
                                    if($row['role'] == 'Admin') $role_badge = 'bg-danger';
                                    elseif($row['role'] == 'Medis') $role_badge = 'bg-success';
                                    elseif($row['role'] == 'Keuangan') $role_badge = 'bg-warning text-dark';
                                ?>
                                <tr>
                                    <td><?= $username_esc ?></td>
                                    <td><?= $nama_esc ?></td>
                                    <td><span class="badge <?= $role_badge ?>"><?= $row['role'] ?></span></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($row['module']) ?></small></td>
                                    <td class="text-center">
                                        <form method="POST" onsubmit="return confirm('Hapus akses user ini?');" style="display:inline;">
                                            <input type="hidden" name="act" value="delete">
                                            <input type="hidden" name="username_del" value="<?= $username_esc ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus Akses"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Inisialisasi DataTable
    $('#tableUsers').DataTable();

    // Inisialisasi Select2 dengan AJAX
    $('#select_pegawai').select2({
        theme: 'bootstrap-5',
        placeholder: 'Ketik Nama atau NIK Pegawai...',
        allowClear: true,
        minimumInputLength: 3, // Minimal ketik 3 huruf baru mencari
        ajax: {
            url: 'api/ajax_pegawai.php',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term }; // Kirim parameter 'q'
            },
            processResults: function (data) {
                return { results: data };
            },
            cache: true
        }
    });
});
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>