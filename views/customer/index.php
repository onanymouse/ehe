<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
$role = $_SESSION['role'];
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Data Pelanggan (Realtime)</h1></div>
        <div class="col-sm-6 text-right">
          <?php if($role == 'admin'): ?>
          <a href="index.php?page=customer&action=create" class="btn btn-primary"><i class="fas fa-user-plus"></i> Tambah Pelanggan</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      
      <div class="row">
          <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3><?php echo $stats['total']; ?></h3><p>Total Pelanggan</p></div><div class="icon"><i class="fas fa-users"></i></div></div></div>
          <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3><?php echo $stats['active']; ?></h3><p>Aktif</p></div><div class="icon"><i class="fas fa-check-circle"></i></div></div></div>
          <div class="col-lg-3 col-6"><div class="small-box bg-danger"><div class="inner"><h3><?php echo $stats['isolated']; ?></h3><p>Terisolir</p></div><div class="icon"><i class="fas fa-lock"></i></div></div></div>
          <div class="col-lg-3 col-6"><div class="small-box bg-secondary"><div class="inner"><h3><?php echo $stats['new_this_month']; ?></h3><p>Baru Bulan Ini</p></div><div class="icon"><i class="fas fa-calendar-plus"></i></div></div></div>
      </div>

      <div class="card card-outline card-secondary collapsed-card">
          <div class="card-header"><h3 class="card-title"><i class="fas fa-filter"></i> Filter Pencarian</h3>
              <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button></div>
          </div>
          <div class="card-body">
              <div class="row">
                  <div class="col-md-3">
                      <select id="filter_area" class="form-control">
                          <option value="">- Semua Area -</option>
                          <?php foreach($areas as $a): ?><option value="<?php echo $a['id']; ?>"><?php echo $a['name']; ?></option><?php endforeach; ?>
                      </select>
                  </div>
                  <div class="col-md-3">
                      <select id="filter_collector" class="form-control">
                          <option value="">- Semua Kolektor -</option>
                          <?php foreach($collectors as $k): ?><option value="<?php echo $k['id']; ?>"><?php echo $k['fullname']; ?></option><?php endforeach; ?>
                      </select>
                  </div>
                  <div class="col-md-3">
                      <select id="filter_status" class="form-control">
                          <option value="">- Semua Status -</option>
                          <option value="active">Active</option>
                          <option value="isolated">Terisolir</option>
                          <option value="nonactive">Non-Aktif</option>
                      </select>
                  </div>
                  <div class="col-md-3">
                      <button type="button" class="btn btn-secondary btn-block" onclick="reloadTable()"><i class="fas fa-search"></i> Terapkan Filter</button>
                  </div>
              </div>
          </div>
      </div>

      <div class="card card-outline card-primary">
        <div class="card-body">
          <table id="table-customer" class="table table-bordered table-hover nowrap" style="width:100%">
            <thead>
              <tr>
                <th style="width: 5%">No</th>
                <th>Pelanggan</th>
                <th>Koneksi</th>
                <th>Paket</th>
                <th>Info Tagihan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-diagnostic"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h4 class="modal-title">Diagnosa</h4><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div><div class="modal-body text-center" id="diagnostic-content">Loading...</div></div></div></div>

<div class="modal fade" id="modal-detail">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header bg-primary"><h4 class="modal-title">Detail</h4><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body">
            <div id="detail-loading" class="text-center"><div class="spinner-border text-primary"></div></div>
            <div id="detail-content" style="display:none;">
                <div class="row">
                    <div class="col-6">
                        <h5>Info Pribadi</h5>
                        <table class="table table-sm">
                            <tr><td>Nama</td><td>: <span id="d-name"></span></td></tr>
                            <tr><td>Alamat</td><td>: <span id="d-address"></span></td></tr>
                            <tr><td>Kolektor</td><td>: <span id="d-collector"></span></td></tr>
                        </table>
                    </div>
                    <div class="col-6">
                        <h5>Info Teknis</h5>
                        <table class="table table-sm">
                            <tr><td>Paket</td><td>: <span id="d-package"></span></td></tr>
                            <tr><td>Router</td><td>: <span id="d-router"></span></td></tr>
                            <tr><td>User</td><td>: <span id="d-pppoe-user"></span></td></tr>
                            <tr><td>Pass</td><td>: <span id="d-pppoe-pass" class="text-danger"></span></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div></div>
</div>

<div class="modal fade" id="modal-traffic" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0">
                <h4 class="modal-title text-success"><i class="fas fa-chart-line"></i> Live Traffic</h4>
                <button type="button" class="close text-white" onclick="stopTraffic()"><span>&times;</span></button>
            </div>
            <div class="modal-body text-center">
                <h3 id="traf-user" class="text-center text-warning mb-2">User</h3>
                <div class="text-center"><span id="traf-status" class="badge badge-secondary">Connecting...</span></div>
                <div class="row mt-3 text-center">
                    <div class="col-6"><small class="text-muted">UPLOAD</small><h2 id="traf-upload" class="text-primary font-weight-bold">0 Kbps</h2></div>
                    <div class="col-6"><small class="text-muted">DOWNLOAD</small><h2 id="traf-download" class="text-success font-weight-bold">0 Kbps</h2></div>
                </div>
                <div style="height: 250px; margin-top: 20px;"><canvas id="trafficChart"></canvas></div>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-secondary" onclick="stopTraffic()">Tutup Monitor</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>.bg-danger-light{background-color:#ffe6e6!important}.bg-secondary-light{background-color:#f2f2f2!important;color:#999}</style>

<script>
var tableCustomer;

$(document).ready(function() {
    // HANCURKAN DATA TABLE LAMA JIKA ADA (SAFETY FIRST)
    if ($.fn.DataTable.isDataTable('#table-customer')) {
        $('#table-customer').DataTable().destroy();
    }

    // INIT SERVER SIDE
    tableCustomer = $('#table-customer').DataTable({
        "processing": true,
        "serverSide": true,
        "responsive": true, // Aktifkan responsive khusus tabel ini
        "order": [],
        "ajax": {
            "url": "index.php?page=customer&action=get_data_ajax",
            "type": "GET",
            "data": function (d) {
                d.area = $('#filter_area').val();
                d.collector = $('#filter_collector').val();
                d.status = $('#filter_status').val();
            }
        },
        "columnDefs": [ { "targets": [0, 5], "orderable": false } ],
        "language": {
            "processing": "<span class='spinner-border spinner-border-sm'></span> Memuat data...",
            "search": "Cari (Nama/Kode/User):",
            "lengthMenu": "Tampil _MENU_ data",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ pelanggan",
            "zeroRecords": "Tidak ada data ditemukan",
            "emptyTable": "Tidak ada data"
        }
    });
});

function reloadTable() {
    tableCustomer.draw();
}

// --- FUNGSI TOMBOL AKSI ---
const formatRupiah = (money) => { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(money); }

function viewDetail(id) {
    $('#modal-detail').modal('show'); $('#detail-content').hide(); $('#detail-loading').show();
    $.ajax({ url: 'index.php?page=customer&action=detail&id='+id, type:'GET', dataType:'json', success:function(res){
        if(res.status=='success'){
             let d=res.data; $('#d-name').text(d.name); $('#d-address').text(d.address); $('#d-collector').text(d.collector_name);
             $('#d-package').text(d.package_name); $('#d-router').text(d.router_name); $('#d-pppoe-user').text(d.pppoe_user); $('#d-pppoe-pass').text(d.pppoe_password);
             $('#detail-loading').hide(); $('#detail-content').fadeIn();
        }
    }});
}

function checkConnection(id, name) {
    $('#modal-diagnostic').modal('show'); $('#diagnostic-content').html('<div class="spinner-border text-primary"></div><p>Cek Status...</p>');
    $.ajax({ url: 'index.php?page=customer&action=check_status&id=' + id, type: 'GET', dataType: 'json',
        success: function(res) { 
            let html = res.is_online ? '<h3 class="text-success">ONLINE</h3>' : '<h3 class="text-danger">OFFLINE</h3>';
            if(res.is_disabled) html += '<br><span class="badge badge-warning">Disabled in Mikrotik</span>';
            $('#diagnostic-content').html(html);
        },
        error: function() { $('#diagnostic-content').html('<div class="text-danger">Router Unreachable.</div>'); }
    });
}

var trafficInterval; var chartInstance;
function startTraffic(id, username) {
    $('#traf-user').text(username); $('#modal-traffic').modal('show');
    if(chartInstance) { chartInstance.destroy(); }
    var ctx = document.getElementById('trafficChart').getContext('2d');
    chartInstance = new Chart(ctx, {
        type: 'line',
        data: { labels: [], datasets: [ { label: 'Upload', borderColor: '#007bff', backgroundColor: 'rgba(0,123,255,0.1)', data: [], fill: true, tension: 0.4 }, { label: 'Download', borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,0.1)', data: [], fill: true, tension: 0.4 } ] },
        options: { responsive: true, maintainAspectRatio: false, scales: { x: { display: false }, y: { beginAtZero: true, grid: { color: '#444' } } }, plugins: { legend: { labels: { color: '#fff' } } } }
    });
    fetchTraffic(id); trafficInterval = setInterval(function() { fetchTraffic(id); }, 2000);
}
function stopTraffic() { clearInterval(trafficInterval); $('#modal-traffic').modal('hide'); }
function fetchTraffic(id) {
    $.ajax({ url: 'index.php?page=customer&action=traffic_api&id=' + id, type: 'GET', dataType: 'json', 
        success: function(res) { 
            if(res.online) { 
                $('#traf-status').removeClass('badge-danger').addClass('badge-success').text('ONLINE');
                $('#traf-upload').text(res.rx_fmt); $('#traf-download').text(res.tx_fmt);
                var time = new Date().toLocaleTimeString();
                if(chartInstance.data.labels.length > 20) { chartInstance.data.labels.shift(); chartInstance.data.datasets[0].data.shift(); chartInstance.data.datasets[1].data.shift(); }
                chartInstance.data.labels.push(time); chartInstance.data.datasets[0].data.push(res.rx_raw / 1000); chartInstance.data.datasets[1].data.push(res.tx_raw / 1000); chartInstance.update();
            } else { $('#traf-status').removeClass('badge-success').addClass('badge-danger').text('OFFLINE'); }
        },
        error: function() { $('#traf-status').text('Disconnect...'); }
    });
}
</script>
