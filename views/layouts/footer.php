  <footer class="main-footer">
    <div class="float-right d-none d-sm-inline">
      Versi 1.3 (Select2)
    </div>
    <strong>Copyright &copy; <?php echo date('Y'); ?> <a href="#"><?php echo APP_NAME; ?></a>.</strong> All rights reserved.
  </footer>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  $(function () {
    // Init DataTables
    $(".table-responsive-data").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false, "pageLength": 10,
      "language": { "search": "Cari:", "zeroRecords": "Tidak ada data", "info": "Hal _PAGE_ dari _PAGES_", "paginate": { "first": "Awal", "last": "Akhir", "next": ">", "previous": "<" } }
    });

    // Init Select2 secara Global (Opsional, tapi bagus untuk form lain)
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Cek Flash Message
    <?php if(isset($_SESSION['flash'])): ?>
        Swal.fire({
            icon: '<?php echo $_SESSION['flash']['type']; ?>',
            title: '<?php echo ucfirst($_SESSION['flash']['type'] == 'success' ? 'Berhasil!' : 'Gagal!'); ?>',
            text: '<?php echo $_SESSION['flash']['message']; ?>',
            timer: 3000,
            showConfirmButton: false
        });
    <?php unset($_SESSION['flash']); endif; ?>
  });
</script>
</body>
</html>
