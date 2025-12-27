<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1 class="m-0"><?php echo $olt['name']; ?></h1></div>
            <div class="col-sm-6 text-right">
                <a href="index.php?page=olt&action=index" class="btn btn-default mr-2">Kembali</a>
                <button onclick="syncOlt()" class="btn btn-primary shadow">
                    <i class="fas fa-sync"></i> Sinkronisasi Data
                </button>
            </div>
        </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
        
        <div class="row">
            <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3><?php echo $stat['total']; ?></h3><p>Total ONU</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3><?php echo $stat['online']; ?></h3><p>Online</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-danger"><div class="inner"><h3><?php echo $stat['los']; ?></h3><p>LOS</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-secondary"><div class="inner"><h3><?php echo $stat['offline']; ?></h3><p>Offline</p></div></div></div>
        </div>

        <div class="card card-outline card-primary">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-4"><select id="filter_pon" class="form-control" onchange="reloadTable()"><option value="">- Semua PON -</option><?php foreach($pons as $p): ?><option value="<?php echo $p['pon_port']; ?>"><?php echo $p['pon_port']; ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-3"><select id="filter_status" class="form-control" onchange="reloadTable()"><option value="">- Status -</option><option value="online">Online</option><option value="los">LOS</option><option value="offline">Offline</option></select></div>
                    <div class="col-md-5"></div>
                </div>
            </div>
            <div class="card-body">
                <table id="table-onu" class="table table-bordered table-hover nowrap" style="width:100%">
                    <thead><tr><th>Interface / SN</th><th>Pelanggan</th><th>Status</th><th>Sinyal</th><th>Aksi</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-onu"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header bg-info"><h4 class="modal-title">Detail ONU Live</h4><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div><div class="modal-body"><div id="onu-loading" class="text-center"><div class="spinner-border text-info"></div><p>Mengambil data...</p></div><div id="onu-content" style="display:none;"><h3 class="text-center text-primary font-weight-bold" id="d-interface"></h3><div class="text-center mb-3"><span id="d-status" class="badge badge-secondary p-2"></span></div><table class="table table-striped"><tr><td>Tipe</td><td>: <b id="d-type"></b></td></tr><tr><td>SN</td><td>: <span id="d-sn"></span></td></tr><tr><td>Jarak</td><td>: <span id="d-dist"></span> meter</td></tr><tr><td>Redaman</td><td>: <b id="d-rx" style="font-size:1.2em"></b></td></tr></table><div class="mt-2"><button class="btn btn-xs btn-default" data-toggle="collapse" data-target="#debugBox">Debug</button><div class="collapse" id="debugBox"><pre id="raw-debug" style="font-size:10px; background:#eee; padding:5px;"></pre></div></div><button class="btn btn-danger btn-block mt-3" id="btn-reboot" onclick="rebootOnu()">REBOOT MODEM</button></div></div></div></div></div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
var tableOnu;
var oltId = "<?php echo $olt['id']; ?>";
var currentOnuId = 0;

$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#table-onu')) { $('#table-onu').DataTable().destroy(); }

    tableOnu = $('#table-onu').DataTable({
        "processing": true, "serverSide": true, "responsive": true, "order": [], 
        "ajax": {
            "url": "index.php?page=olt&action=get_onu_ajax&id_olt=" + oltId,
            "type": "GET",
            "data": function (d) { d.pon = $('#filter_pon').val(); d.status = $('#filter_status').val(); }
        },
        "columnDefs": [ { "targets": [4], "orderable": false } ],
        "language": { "processing": "Memuat data...", "search": "Cari (SN/Interface):" }
    });
});

function reloadTable() { tableOnu.draw(); }

// --- FUNGSI SYNC AJAX (BARU) ---
function syncOlt() {
    Swal.fire({
        title: 'Sedang Sinkronisasi...',
        text: 'Mohon tunggu, sedang mengambil data dari OLT.',
        allowOutsideClick: false,
        showConfirmButton: false,
        onBeforeOpen: () => { Swal.showLoading() }
    });

    $.ajax({
        url: 'index.php?page=olt&action=sync&id=' + oltId,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if(res.status == 'success') {
                Swal.fire('Berhasil!', res.message, 'success').then(() => {
                    location.reload(); // Reload untuk update statistik angka
                });
            } else {
                Swal.fire('Gagal!', res.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            Swal.fire('Error!', 'Koneksi terputus atau Timeout.', 'error');
        }
    });
}

function detailOnu(id) {
    currentOnuId = id; $('#modal-onu').modal('show'); $('#onu-content').hide(); $('#onu-loading').show();
    $.ajax({
        url: 'index.php?page=olt&action=detail_onu&id=' + id, type: 'GET', dataType: 'json',
        success: function(res) {
            if(res.status == 'success') {
                $('#d-interface').text(res.interface); $('#d-status').text(res.state);
                $('#d-type').text(res.type); $('#d-sn').text(res.sn); $('#d-dist').text(res.distance);
                let rx = parseFloat(res.rx_power); let color = (res.rx_power == 'N/A') ? 'text-muted' : (rx < -27 ? 'text-danger' : 'text-success');
                $('#d-rx').attr('class', color).text(res.rx_power); $('#raw-debug').text(res.raw_debug);
                $('#onu-loading').hide(); $('#onu-content').fadeIn(); reloadTable();
            } else { alert(res.message); $('#modal-onu').modal('hide'); }
        },
        error: function(xhr) { alert('Error: ' + xhr.responseText); $('#modal-onu').modal('hide'); }
    });
}
function rebootOnu() {
    if(!confirm('Reboot?')) return;
    $('#btn-reboot').text('Sending...').prop('disabled', true);
    $.ajax({ url: 'index.php?page=olt&action=reboot_onu&id='+currentOnuId, type: 'GET', dataType: 'json',
        success: function(res) { alert(res.message); $('#modal-onu').modal('hide'); $('#btn-reboot').html('REBOOT').prop('disabled', false); }
    });
}
</script>
