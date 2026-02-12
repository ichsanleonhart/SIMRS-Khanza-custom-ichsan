<?php
// File: modules/mpp/master_masalah.php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../helpers/auth_helper.php';

cekLogin();
if (!cekAkses('mpp_skrining')) { die("Akses Ditolak"); }

require_once '../../layout/header.php';
require_once '../../layout/sidebar.php';

// Logic CRUD Sederhana di satu file
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'];
    if ($act == 'add') {
        $stmt = $pdo->prepare("INSERT INTO master_masalah_mpp (kode_masalah, nama_masalah) VALUES (?, ?)");
        $stmt->execute([$_POST['kode'], $_POST['nama']]);
    } elseif ($act == 'edit') {
        $stmt = $pdo->prepare("UPDATE master_masalah_mpp SET nama_masalah=? WHERE kode_masalah=?");
        $stmt->execute([$_POST['nama'], $_POST['kode']]);
    } elseif ($act == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM master_masalah_mpp WHERE kode_masalah=?");
        $stmt->execute([$_POST['kode']]);
    }
    echo "<script>window.location.href='master_masalah.php';</script>";
    exit;
}

$data = $pdo->query("SELECT * FROM master_masalah_mpp ORDER BY kode_masalah ASC")->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="h5 text-gray-800"><i class="fas fa-list-alt text-primary me-2"></i> Master Masalah MPP</h4>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAdd">
            <i class="fas fa-plus"></i> Tambah Masalah
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Kode</th>
                            <th>Nama Masalah</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data as $row): ?>
                        <tr>
                            <td><?= $row['kode_masalah'] ?></td>
                            <td><?= $row['nama_masalah'] ?></td>
                            <td class="text-center">
                                <button class="btn btn-warning btn-sm btn-edit" 
                                    data-kode="<?= $row['kode_masalah'] ?>" 
                                    data-nama="<?= $row['nama_masalah'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Hapus data ini?');">
                                    <input type="hidden" name="act" value="delete">
                                    <input type="hidden" name="kode" value="<?= $row['kode_masalah'] ?>">
                                    <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Masalah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="act" value="add">
                    <div class="mb-3">
                        <label>Kode Masalah</label>
                        <input type="text" name="kode" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Nama Masalah</label>
                        <textarea name="nama" class="form-control" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Masalah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="act" value="edit">
                    <div class="mb-3">
                        <label>Kode Masalah</label>
                        <input type="text" name="kode" id="edit_kode" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Nama Masalah</label>
                        <textarea name="nama" id="edit_nama" class="form-control" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../layout/footer.php'; ?>
<script>
    $('.btn-edit').click(function(){
        $('#edit_kode').val($(this).data('kode'));
        $('#edit_nama').val($(this).data('nama'));
        $('#modalEdit').modal('show');
    });
</script>