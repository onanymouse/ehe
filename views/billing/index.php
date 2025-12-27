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
          <table class="table table-bordered table-hover nowrap" id="table-billing" style="width:100%">
            <thead>
              <tr>
                <th style="width: 5%" class="text-center no-sort">
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
              <?php 
              // Pastikan $invoices valid array, jika null ubah jadi array kosong
              $invoices = isset($invoices) && is_array($invoices) ? $invoices : [];
              
              if(count($invoices) > 0): 
              ?>
                <?php foreach($invoices as $inv): 
                    // Safety check data
                    $amount = isset($inv['amount']) ? $inv['amount'] : 0;
                    $cust_name = isset($inv['customer_name']) ? $inv['customer_name'] : '-';
                    $inv_num = isset($inv['invoice_number']) ? $inv['invoice_number'] : '-';
                    $coll_name = isset($inv['collector_name']) ? $inv['collector_name'] : null;

                    // Logika Hitung Hari
                    $period = isset($inv['period_month']) ? $inv['period_month'] : date('Y-m');
                    $due = isset($inv['due_date']) ? $inv['due_date'] : 10;
                    
                    $tgl_jatuhtempo = date('Y-m', strtotime($period)) . '-' . sprintf("%02d", $due);
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
                      <b class="text-primary"><?php echo $cust_name; ?></b><br>
                      <small class="text-muted"><?php echo $inv_num; ?></small>
                  </td>
                  <td>
                      <span class="badge badge-<?php echo $badge_cls; ?>"><?php echo $text_hari; ?></span><br>
                      <small><?php echo date('d M Y', strtotime($tgl_jatuhtempo)); ?></small>
                  </td>
                  <td class="text-danger font-weight-bold">
                      Rp <?php echo number_format($amount, 0, ',', '.'); ?>
                  </td>
                  <td>
                      <?php echo ($coll_name) ? "<span class='badge badge-secondary'>{$coll_name}</span>" : "<span class='badge badge-info'>UMUM</span>"; ?>
                  </td>
                  <td>
                    <div class="btn-group">
                        <a href="index.php?page=billing&action=pay&id=<?php echo $inv['id']; ?>" class="btn btn-success btn-xs" onclick="return confirm('Konfirmasi Pembayaran Manual?')"><i class="fas fa-money-bill"></i></a>
                        <a href="index.php?page=billing&action=isolate_manual&id=<?php echo $inv['id']; ?>" class="btn btn-danger btn-xs" onclick="return confirm('Isolir Pelanggan Ini?')"><i class="fas fa-bolt"></i></a>
                        <a href="index.php?page=billing&action=print&id=<?php echo $inv['id']; ?>" target="_blank" class="btn btn-default btn-xs"><i class="fas fa-print"></i></a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php 
              // PERBAIKAN PENTING: 
              // JANGAN ADA 'ELSE' UNTUK ROW KOSONG DI SINI. 
              // BIARKAN TBODY KOSONG JIKA TIDAK ADA DATA. 
              // DATATABLES AKAN OTOMATIS MEMUNCULKAN "No data available".
              endif; 
              ?>
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
    // Init DataTable
    $('#table-billing').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[ 2, "asc" ]], // Urutkan berdasarkan Jatuh Tempo
        "columnDefs": [ 
            { "orderable": false, "targets": [0, 5] }, // Matikan sort di Checkbox (0) dan Aksi (5)
            { "className": "text-center", "targets": [0, 5] }
        ],
        "language": {
            "emptyTable": "Tidak ada tagihan yang belum dibayar.",
            "zeroRecords": "Data tidak ditemukan."
        }
    });

    // LOGIKA CHECK ALL
    $('#check-all').click(function() {
        var isChecked = $(this).prop('checked');
        $('.check-item').prop('checked', isChecked);
    });
});
</script>