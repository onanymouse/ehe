<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Tagihan Belum Bayar</h1>
        </div>
        <div class="col-sm-6 text-right">
            <a href="index.php?page=billing&action=history" class="btn btn-secondary">
                <i class="fas fa-history"></i> Riwayat Pembayaran
            </a>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      
      <form action="index.php?page=billing&action=bulk_delete" method="post" id="form-bulk">
      
      <div class="card card-outline card-danger">
        <div class="card-header">
            <h3 class="card-title">Daftar Tagihan</h3>
            <div class="card-tools">
                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus tagihan yang dicentang? Data yang dihapus tidak bisa kembali.')">
                    <i class="fas fa-trash"></i> Hapus Terpilih
                </button>
            </div>
        </div>
        <div class="card-body">
          <table class="table table-bordered table-hover table-responsive-data nowrap" id="table-billing" style="width:100%">
            <thead>
              <tr>
                <th style="width: 5%" class="text-center">
                    <input type="checkbox" id="check-all">
                </th>
                <th>Info Pelanggan</th>
                <th>Jatuh Tempo</th>
                <th>Tagihan</th>
                <th>Kolektor</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if(count($invoices) > 0): ?>
                <?php foreach($invoices as $inv): 
                    // Logika Hitung Hari (Sama seperti sebelumnya)
                    $tgl_jatuhtempo = date('Y-m', strtotime($inv['period_month'])) . '-' . sprintf("%02d", $inv['due_date']);
                    $now = time(); 
                    $your_date = strtotime($tgl_jatuhtempo);
                    $datediff = $your_date - $now;
                    $selisih_hari = round($datediff / (60 * 60 * 24));

                    if($selisih_hari < 0) { $badge_cls = 'danger'; $text_hari = "TELAT " . abs($selisih_hari) . " HARI"; }
                    elseif($selisih_hari == 0) { $badge_cls = 'danger'; $text_hari = "HARI INI"; }
                    else { $badge_cls = 'warning'; $text_hari = "H-" . $selisih_hari; }
                ?>
                <tr>
                  <td class="text-center">
                      <input type="checkbox" name="ids[]" value="<?php echo $inv['id']; ?>" class="check-item">
                  </td>
                  <td>
                      <b class="text-primary"><?php echo $inv['customer_name']; ?></b><br>
                      <small class="text-muted"><?php echo $inv['invoice_number']; ?></small>
                  </td>
                  <td>
                      <span class="badge badge-<?php echo $badge_cls; ?>"><?php echo $text_hari; ?></span><br>
                      <small><?php echo date('d M Y', strtotime($tgl_jatuhtempo)); ?></small>
                  </td>
                  <td class="text-danger font-weight-bold">
                      Rp <?php echo number_format($inv['amount'], 0, ',', '.'); ?>
                  </td>
                  <td>
                      <?php echo ($inv['collector_name']) ? "<span class='badge badge-secondary'>{$inv['collector_name']}</span>" : "<span class='badge badge-info'>UMUM</span>"; ?>
                  </td>
                  <td>
                    <div class="btn-group">
                        <a href="index.php?page=billing&action=pay&id=<?php echo $inv['id']; ?>" class="btn btn-success btn-xs" onclick="return confirm('Bayar?')"><i class="fas fa-money-bill"></i></a>
                        <a href="index.php?page=billing&action=isolate_manual&id=<?php echo $inv['id']; ?>" class="btn btn-danger btn-xs" onclick="return confirm('Isolir?')"><i class="fas fa-bolt"></i></a>
                        <a href="index.php?page=billing&action=print&id=<?php echo $inv['id']; ?>" target="_blank" class="btn btn-default btn-xs"><i class="fas fa-print"></i></a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                  <tr><td colspan="6" class="text-center">Tidak ada tagihan.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      
      </form> </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
$(document).ready(function() {
    // Init DataTable (Matikan sort di kolom checkbox)
    $('#table-billing').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[ 2, "asc" ]], // Urutkan berdasarkan jatuh tempo
        "columnDefs": [ { "orderable": false, "targets": 0 } ]
    });

    // LOGIKA CHECK ALL
    $('#check-all').click(function() {
        var isChecked = $(this).prop('checked');
        $('.check-item').prop('checked', isChecked);
    });
});
</script>