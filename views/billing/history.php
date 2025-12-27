<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Riwayat & Log Pembayaran</h1></div>
            <div class="col-sm-6 text-right">
                <a href="index.php?page=billing&action=index" class="btn btn-default"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
        </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="card card-outline card-success">
        <div class="card-body">
          <table class="table table-bordered table-striped table-hover table-responsive-data" style="width:100%">
            <thead>
              <tr>
                <th>No Invoice</th>
                <th>Pelanggan</th>
                <th>Periode</th>
                <th>Nominal</th>
                <th>Waktu & Penerima (Log)</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($invoices as $inv): ?>
                <tr>
                  <td><?php echo $inv['invoice_number']; ?></td>
                  <td>
                      <b><?php echo $inv['customer_name']; ?></b><br>
                      <small><?php echo $inv['customer_code']; ?></small>
                  </td>
                  <td><?php echo $inv['period_month']; ?></td>
                  <td class="text-success text-bold"><?php echo format_rupiah($inv['amount']); ?></td>
                  <td>
                      <i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($inv['paid_at'])); ?><br>
                      <i class="fas fa-user-check"></i> 
                      <?php echo $inv['cashier_name'] ? $inv['cashier_name'] : 'System/Auto'; ?>
                  </td>
                  <td>
                      <a href="index.php?page=billing&action=print&id=<?php echo $inv['id']; ?>" target="_blank" class="btn btn-sm btn-default" title="Cetak Struk">
                          <i class="fas fa-print"></i>
                      </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
