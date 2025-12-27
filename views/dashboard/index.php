<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
$role = $_SESSION['role'];
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Dashboard Overview</h1></div>
        <div class="col-sm-6 text-right"><small>Tahun: <?php echo date('Y'); ?></small></div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      
      <div class="row">
        <div class="col-12 col-sm-6 col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Total Pelanggan</span>
              <span class="info-box-number"><?php echo number_format($cust_stat['total']); ?></span>
              <span class="text-xs text-muted">Aktif: <?php echo $cust_stat['active']; ?> | Isolir: <span class="text-danger"><?php echo $cust_stat['isolated']; ?></span></span>
            </div>
          </div>
        </div>
        
        <div class="col-12 col-sm-6 col-md-3">
          <div class="info-box mb-3">
            <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-file-invoice-dollar"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Belum Lunas</span>
              <span class="info-box-number"><?php echo number_format($unpaid['total_inv']); ?> <small>Inv</small></span>
              <span class="text-xs text-muted">Rp <?php echo number_format($unpaid['total_money'] ?? 0); ?></span>
            </div>
          </div>
        </div>

        <?php if($role == 'admin' || $role == 'teknisi'): ?>
        <div class="col-12 col-sm-6 col-md-3">
          <div class="info-box mb-3">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-server"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Perangkat</span>
              <span class="info-box-number"><?php echo $router_count; ?> <small>Router</small></span>
              <span class="text-xs text-muted"><?php echo $olt_count; ?> OLT Terdaftar</span>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
                <span class="info-box-icon bg-success elevation-1"><i class="fas fa-network-wired"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Status Jaringan</span>
                    <span class="info-box-number">Online</span>
                    <span class="text-xs text-success">Monitoring Aktif</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if($role == 'kolektor'): ?>
        <div class="col-12 col-sm-6 col-md-6">
            <div class="alert alert-info">
                <h5><i class="icon fas fa-info"></i> Halo Kolektor!</h5>
                Selamat bekerja. Pastikan setoran diserahkan tepat waktu.
            </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="row">
        
        <?php if($role == 'admin' || $role == 'keuangan'): ?>
        <div class="col-md-8">
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar"></i> Pemasukan Tahun <?php echo date('Y'); ?></h3>
                    <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button></div>
                </div>
                <div class="card-body">
                    <div class="chart"><canvas id="incomeChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if($role == 'admin' || $role == 'teknisi'): ?>
        <div class="col-md-4">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-plus"></i> Pertumbuhan Pelanggan</h3>
                    <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button></div>
                </div>
                <div class="card-body">
                    <div class="chart"><canvas id="customerChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if($role == 'kolektor'): ?>
        <div class="col-md-12">
             <div class="card">
                <div class="card-header border-0"><h3 class="card-title">Riwayat Penagihan Terakhir</h3></div>
                <div class="card-body table-responsive p-0">
                  <table class="table table-striped table-valign-middle">
                    <thead><tr><th>Pelanggan</th><th>Nominal</th><th>Tanggal</th></tr></thead>
                    <tbody>
                    <?php if(count($history) > 0): foreach($history as $h): ?>
                        <tr>
                          <td><?php echo $h['name']; ?></td>
                          <td class="text-success"><?php echo format_rupiah($h['amount']); ?></td>
                          <td><?php echo date('d/m H:i', strtotime($h['paid_at'])); ?></td>
                        </tr>
                    <?php endforeach; else: ?><tr><td colspan="3" class="text-center">Belum ada data.</td></tr><?php endif; ?>
                    </tbody>
                  </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

      </div>
      <?php if($role != 'kolektor'): ?>
      <div class="row">
          <div class="col-md-12">
             <div class="card">
                <div class="card-header border-0">
                    <h3 class="card-title">Pembayaran Terakhir Masuk (Live)</h3>
                    <div class="card-tools"><a href="index.php?page=billing&action=history" class="btn btn-tool btn-sm"><i class="fas fa-bars"></i> Lihat Semua</a></div>
                </div>
                <div class="card-body table-responsive p-0">
                  <table class="table table-striped table-valign-middle">
                    <thead><tr><th>Pelanggan</th><th>Nominal</th><th>Waktu</th></tr></thead>
                    <tbody>
                    <?php if(count($history) > 0): foreach($history as $h): ?>
                        <tr>
                          <td><?php echo $h['name']; ?></td>
                          <td class="text-success"><?php echo format_rupiah($h['amount']); ?></td>
                          <td><?php echo date('d/m/Y H:i', strtotime($h['paid_at'])); ?></td>
                        </tr>
                    <?php endforeach; else: ?><tr><td colspan="3" class="text-center">Belum ada pembayaran masuk.</td></tr><?php endif; ?>
                    </tbody>
                  </table>
                </div>
            </div>
          </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(function () {
    // KONFIGURASI UMUM
    var ticksStyle = { fontColor: '#495057', fontStyle: 'bold' }
    var mode = 'index'; var intersect = true;

    // 1. CHART INCOME (HANYA ADMIN/KEUANGAN)
    <?php if($role == 'admin' || $role == 'keuangan'): ?>
    var ctxIncome = document.getElementById('incomeChart').getContext('2d');
    var incomeChart = new Chart(ctxIncome, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [{
                label: 'Pemasukan (Rp)',
                backgroundColor: '#28a745',
                borderColor: '#28a745',
                data: [<?php echo $data_income_str; ?>]
            }]
        },
        options: {
            maintainAspectRatio: false,
            tooltips: { mode: mode, intersect: intersect },
            hover: { mode: mode, intersect: intersect },
            legend: { display: false },
            scales: {
                yAxes: [{ gridLines: { display: true, lineWidth: '4px', color: 'rgba(0, 0, 0, .2)', zeroLineColor: 'transparent' }, ticks: $.extend({ beginAtZero: true, callback: function (value) { if (value >= 1000) { value /= 1000; value += 'k'; } return 'Rp ' + value; } }, ticksStyle) }],
                xAxes: [{ display: true, gridLines: { display: false }, ticks: ticksStyle }]
            }
        }
    });
    <?php endif; ?>

    // 2. CHART CUSTOMER (HANYA ADMIN/TEKNISI)
    <?php if($role == 'admin' || $role == 'teknisi'): ?>
    var ctxCust = document.getElementById('customerChart').getContext('2d');
    var customerChart = new Chart(ctxCust, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [{
                label: 'Pelanggan Baru',
                backgroundColor: 'transparent',
                borderColor: '#17a2b8',
                pointBorderColor: '#17a2b8',
                pointBackgroundColor: '#17a2b8',
                fill: false,
                data: [<?php echo $data_cust_str; ?>]
            }]
        },
        options: {
            maintainAspectRatio: false,
            tooltips: { mode: mode, intersect: intersect },
            hover: { mode: mode, intersect: intersect },
            legend: { display: false },
            scales: {
                yAxes: [{ gridLines: { display: true, lineWidth: '4px', color: 'rgba(0, 0, 0, .2)', zeroLineColor: 'transparent' }, ticks: $.extend({ beginAtZero: true, stepSize: 1 }, ticksStyle) }],
                xAxes: [{ display: true, gridLines: { display: false }, ticks: ticksStyle }]
            }
        }
    });
    <?php endif; ?>
});
</script>
