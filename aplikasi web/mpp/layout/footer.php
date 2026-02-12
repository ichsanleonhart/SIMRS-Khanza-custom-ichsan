<?php 
// File: layout/footer.php 
?>
</div> </div> <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function () {
        // Toggle Sidebar
        $('#sidebarCollapse').on('click', function () {
            // 1. Toggle class 'active' di sidebar
            $('#sidebar').toggleClass('active');
            
            // 2. Toggle overlay (Hanya efek di mobile krn CSS desktop display:none)
            $('#mobileOverlay').toggleClass('active');
        });

        // Klik Overlay (Tutup Sidebar di Mobile)
        $('#mobileOverlay').on('click', function () {
            $('#sidebar').removeClass('active');
            $(this).removeClass('active');
        });
    });
</script>

</body>
</html>