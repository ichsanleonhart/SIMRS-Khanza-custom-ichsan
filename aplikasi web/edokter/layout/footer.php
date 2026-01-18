<footer class="main-footer">
    <div class="float-right d-none d-sm-block">
      <b>Version</b> 2.0 (Stable)
    </div>
    <strong>Copyright &copy; <?= date('Y') ?> <a href="#">IT <?= $_SESSION['nama_instansi'] ?? 'Rumah Sakit' ?></a>.</strong> All rights reserved.
  </footer>

</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"></script>

<script>
    $(function () {
        // Global Init
        $('.select2').select2({ theme: 'bootstrap4' });
    });
</script>
</body>
</html>