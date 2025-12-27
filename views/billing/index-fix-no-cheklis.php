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
      
      <?php if($_SESSION['role'] == 'kolektor'): ?>
      <div class="alert alert-warning">
          <i class="fas fa-user-tag"></i> <b>Halo Kolektor!</b> Anda hanya melihat tagihan pelanggan yang ditugaskan kepada Anda (atau status ALL).
      </div>
      <?php endif; ?>

      <div class="card card-outline card-danger">
        <div class="card-body">
          <table class="table table-bordered table-striped table-hover table-responsive-data nowrap" style="width:100%">
            <thead>
              <tr>
                <th style="width: 5%">#</th>
                <th>Info Pelanggan</th>
                <th>Jatuh Tempo (H-?)</th>
                <th>Tagihan</th>
                <th>Kolektor</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if(count($invoices) > 0): ?>
                <?php $no=1; foreach($invoices as $inv): 
                    // --- LOGIKA HITUNG HARI (H-X) ---
                    $tgl_jatuhtempo = date('Y-m', strtotime($inv['period_month'])) . '-' . sprintf("%02d", $inv['due_date']);
                    
                    // Hitung selisih hari
                    $now = time(); 
                    $your_date = strtotime($tgl_jatuhtempo);
                    $datediff = $your_date - $now;
                    $selisih_hari = round($datediff / (60 * 60 * 24));

                    // Tentukan Warna & Teks Badge
                    if($selisih_hari < 0) {
                        $badge_cls = 'danger';
                        $text_hari = "TELAT " . abs($selisih_hari) . " HARI";
                    } elseif($selisih_hari == 0) {
                        $badge_cls = 'danger';
                        $text_hari = "HARI INI";
                    } else {
                        $badge_cls = ($selisih_hari <= 3) ? 'warning' : 'success';
                        $text_hari = "H-" . $selisih_hari;
                    }
                    
                    $tgl_indo_full = date('d F Y', strtotime($tgl_jatuhtempo));
                ?>
                <tr>
                  <td><?php echo $no++; ?></td>
                  <td>
                      <b class="text-primary"><?php echo $inv['customer_name']; ?></b><br>
                      <small class="text-muted">Kode: <?php echo $inv['customer_code']; ?></small><br>
                      <small class="text-muted">Inv: <?php echo $inv['invoice_number']; ?></small>
                  </td>
                  
                  <td>
                      <span class="text-bold"><?php echo $tgl_indo_full; ?></span><br>
                      <span class="badge badge-<?php echo $badge_cls; ?>"><?php echo $text_hari; ?></span>
                  </td>
                  
                  <td class="text-danger text-bold text-nowrap">
                      Rp <?php echo number_format($inv['amount'], 0, ',', '.'); ?>
                  </td>

                  <td>
                      <?php if($inv['collector_id'] == 0 || $inv['collector_id'] == NULL): ?>
                          <span class="badge badge-info">ALL / UMUM</span>
                      <?php else: ?>
                          <span class="badge badge-secondary"><?php echo $inv['collector_name']; ?></span>
                      <?php endif; ?>
                  </td>
                  
                  <td>
                    <div class="btn-group">
                        <a href="index.php?page=billing&action=pay&id=<?php echo $inv['id']; ?>" 
                           class="btn btn-success btn-sm shadow"
                           onclick="return confirm('Proses Pembayaran?')">
                            <i class="fas fa-money-bill-wave"></i> Bayar
                        </a>
                        
                        <a href="index.php?page=billing&action=isolate_manual&id=<?php echo $inv['id']; ?>" 
                           class="btn btn-danger btn-sm shadow"
                           title="Isolir Paksa (Manual)"
                           onclick="return confirm('Yakin ingin MENGISOLIR PAKSA pelanggan ini sekarang?')">
                            <i class="fas fa-bolt"></i>
                        </a>

                        <a href="index.php?page=billing&action=print&id=<?php echo $inv['id']; ?>" target="_blank" class="btn btn-default btn-sm">
                            <i class="fas fa-print"></i>
                        </a>
                    </div>
                </td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                  <tr><td colspan="6" class="text-center">Tidak ada tagihan tertunggak.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>