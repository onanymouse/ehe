<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1 class="m-0">Secret: <?php echo $router['name']; ?></h1></div>
            <div class="col-sm-6 text-right">
                <a href="index.php?page=mikrotik&action=index" class="btn btn-default">Kembali</a>
                <button onclick="reloadTable()" class="btn btn-warning"><i class="fas fa-sync"></i> Refresh</button>
            </div>
        </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="card bg-light mb-3">
          <div class="card-body p-2 d-flex justify-content-between align-items-center">
              <span>Target: <b><?php echo $router['ip_address'] . ':' . $router['port']; ?></b></span>
              <span id="status-badge" class="badge badge-secondary">Connecting...</span>
          </div>
      </div>

      <div class="card card-outline card-warning">
        <div class="card-body">
          <table id="table-secret" class="table table-bordered table-striped table-hover nowrap" style="width:100%">
            <thead><tr><th>Name</th><th>Password</th><th>Profile</th><th>Service</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
var tableSecret;
var routerId = "<?php echo $router['id']; ?>";

$(document).ready(function() {
    $.fn.dataTable.ext.errMode = 'none';

    tableSecret = $('#table-secret').DataTable({
        "processing": true,
        "responsive": true,
        "ajax": {
            "url": "index.php?page=mikrotik&action=get_secret_data&id=" + routerId,
            "type": "GET",
            "dataSrc": function (json) {
                if(json.status == 'online') {
                    $('#status-badge').attr('class', 'badge badge-success').html('ONLINE');
                } else {
                    $('#status-badge').attr('class', 'badge badge-danger').html('OFFLINE');
                }
                return json.data;
            }
        },
        "columns": [
            { "data": "name", "className": "font-weight-bold" },
            { "data": "password", "className": "text-muted" },
            { "data": "profile" },
            { "data": "service" },
            { 
                "data": "disabled",
                "render": function(data) { return data ? '<span class="badge badge-danger">Disabled</span>' : '<span class="badge badge-success">Enabled</span>'; }
            },
            { 
                "data": null,
                "render": function(data, type, row) {
                    if(row.disabled) return `<a href="index.php?page=mikrotik&action=enable_secret&id_router=${routerId}&id_secret=${row.id}" class="btn btn-xs btn-success">Enable</a>`;
                    else return `<a href="index.php?page=mikrotik&action=disable_secret&id_router=${routerId}&id_secret=${row.id}" class="btn btn-xs btn-danger" onclick="return confirm('Disable?')">Disable</a>`;
                }
            }
        ]
    });

    tableSecret.on('error.dt', function (e, settings, techNote, message) {
        $('#status-badge').attr('class', 'badge badge-danger').html('Koneksi Gagal');
    });
});

function reloadTable() {
    $('#status-badge').attr('class', 'badge badge-warning').html('Refreshing...');
    tableSecret.ajax.reload();
}
</script>
