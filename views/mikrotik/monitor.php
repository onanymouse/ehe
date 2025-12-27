<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Monitor: <?php echo $router['name']; ?></h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="index.php?page=mikrotik&action=index" class="btn btn-default">Kembali</a>
                <button onclick="reloadTable()" class="btn btn-success"><i class="fas fa-sync"></i> Refresh Data</button>
            </div>
        </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      
      <div class="row">
          <div class="col-md-12 mb-3">
              <div class="card">
                  <div class="card-body p-3 d-flex align-items-center justify-content-between">
                      <div><i class="fas fa-server text-muted mr-2"></i> IP: <b><?php echo $router['ip_address']; ?></b></div>
                      <div id="router-status-indicator">
                          <span class="spinner-border spinner-border-sm text-primary"></span> Menghubungkan...
                      </div>
                  </div>
              </div>
          </div>
      </div>

      <div class="card card-outline card-success">
        <div class="card-header"><h3 class="card-title">PPP Active Connections</h3></div>
        <div class="card-body">
          <table id="table-active" class="table table-bordered table-striped table-hover nowrap" style="width:100%">
            <thead>
              <tr>
                <th>Username</th>
                <th>IP Address</th>
                <th>Uptime</th>
                <th>MAC Address</th>
                <th>Service</th>
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
var tableActive;
var routerId = "<?php echo $router['id']; ?>";

$(document).ready(function() {
    tableActive = $('#table-active').DataTable({
        "processing": true,
        "serverSide": false, // Client side processing karena data JSON penuh
        "responsive": true,  // AGAR RAPI DI HP
        "ajax": {
            "url": "index.php?page=mikrotik&action=get_active_data&id=" + routerId,
            "type": "GET",
            "dataSrc": function (json) {
                updateStatus(json.status, json.error);
                return json.data;
            },
            "error": function (xhr, error, thrown) {
                updateStatus('offline', 'Server Error / Timeout');
            }
        },
        "columns": [
            { "data": "name", "className": "font-weight-bold text-primary" },
            { "data": "address" },
            { "data": "uptime" },
            { "data": "caller_id" },
            { "data": "service" },
            { 
                "data": "id",
                "render": function(data, type, row) {
                    return `<a href="index.php?page=mikrotik&action=kick&id_router=${routerId}&id_session=${data}" class="btn btn-xs btn-danger" onclick="return confirm('Kick user ini?')"><i class="fas fa-power-off"></i> Kick</a>`;
                }
            }
        ],
        "language": { "emptyTable": "Tidak ada koneksi aktif atau Router Offline" }
    });
});

function updateStatus(status, errorMsg) {
    var indicator = $('#router-status-indicator');
    if(status == 'online') {
        indicator.html('<span class="badge badge-success px-3 py-2"><i class="fas fa-wifi"></i> ONLINE</span>');
    } else {
        var msg = errorMsg ? errorMsg : 'OFFLINE';
        indicator.html('<span class="badge badge-danger px-3 py-2"><i class="fas fa-times-circle"></i> ' + msg + '</span>');
    }
}

function reloadTable() {
    $('#router-status-indicator').html('<span class="spinner-border spinner-border-sm text-primary"></span> Refreshing...');
    tableActive.ajax.reload();
}
</script>
