<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1 class="m-0"><i class="fas fa-server"></i> <?php echo $olt['name']; ?></h1></div>
            <div class="col-sm-6 text-right">
                <a href="index.php?page=olt&action=index" class="btn btn-default mr-2">Kembali</a>
                <button onclick="syncOlt()" class="btn btn-primary shadow">
                    <i class="fas fa-sync"></i> Sinkronisasi
                </button>
            </div>
        </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
        
        <div class="row">
            <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3><?php echo $stat['total']; ?></h3><p>Total ONU</p></div><div class="icon"><i class="fas fa-microchip"></i></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3><?php echo $stat['online']; ?></h3><p>Online</p></div><div class="icon"><i class="fas fa-check-circle"></i></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-danger"><div class="inner"><h3><?php echo $stat['los']; ?></h3><p>LOS (Putus)</p></div><div class="icon"><i class="fas fa-exclamation-triangle"></i></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-secondary"><div class="inner"><h3><?php echo $stat['offline']; ?></h3><p>Mati Listrik/Off</p></div><div class="icon"><i class="fas fa-power-off"></i></div></div></div>
        </div>

        <div class="card card-outline card-primary">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-3">
                        <select id="filter_pon" class="form-control" onchange="reloadTable()">
                            <option value="">- Semua Interface -</option>
                            <?php foreach($pons as $p): ?>
                                <option value="<?php echo $p['pon_port']; ?>"><?php echo $p['pon_port']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="filter_status" class="form-control" onchange="reloadTable()">
                            <option value="">- Semua Status -</option>
                            <option value="online">Online</option>
                            <option value="los">LOS</option>
                            <option value="offline">Offline</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table id="table-onu" class="table table-bordered table-striped table-hover nowrap" style="width:100%">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th>Interface / SN</th>
                            <th>Pelanggan</th>
                            <th>Status</th>
                            <th>Sinyal (dBm)</th>
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

<div class="modal fade" id="modal-onu">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h4 class="modal-title"><i class="fas fa-cog"></i> Detail & Kontrol ONU</h4>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="onu-loading" class="text-center py-5">
                    <div class="spinner-border text-info" style="width: 3rem; height: 3rem;"></div>
                    <p class="mt-2">Sedang mengambil data realtime dari OLT...</p>
                </div>

                <div id="onu-content" style="display:none;">
                    <div class="row">
                        <div class="col-md-6 border-right">
                            <h5 class="text-primary font-weight-bold" id="d-interface"></h5>
                            <hr>
                            <table class="table table-sm">
                                <tr><td width="40%">Status</td><td>: <span id="d-status" class="badge badge-secondary"></span></td></tr>
                                <tr><td>Tipe</td><td>: <b id="d-type"></b></td></tr>
                                <tr><td>Serial Number</td><td>: <span id="d-sn"></span></td></tr>
                                <tr><td>Jarak Kabel</td><td>: <span id="d-dist"></span> meter</td></tr>
                                <tr><td>Redaman (Rx)</td><td>: <b id="d-rx" style="font-size:1.2em"></b></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Aksi Cepat:</h6>
                            
                            <button class="btn btn-warning btn-block mb-2 shadow-sm" id="btn-reboot" onclick="rebootOnu()">
                                <i class="fas fa-sync"></i> <b>REBOOT MODEM</b>
                                <br><small>Restart perangkat jarak jauh</small>
                            </button>

                            <button class="btn btn-danger btn-block mb-2 shadow-sm" id="btn-reset" onclick="resetOnu()">
                                <i class="fas fa-history"></i> <b>RESET FACTORY</b>
                                <br><small>Kembali ke pengaturan pabrik</small>
                            </button>

                            <hr>
                            
                            <button class="btn btn-dark btn-block shadow-sm" id="btn-delete" onclick="deleteOnu()">
                                <i class="fas fa-trash"></i> <b>HAPUS DARI OLT</b>
                                <br><small>Hapus config & data dari database</small>
                            </button>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-xs btn-outline-secondary" type="button" data-toggle="collapse" data-target="#debugBox">
                            Lihat Raw Output (Debug)
                        </button>
                        <div class="collapse mt-2" id="debugBox">
                            <pre id="raw-debug" style="font-size:10px; background:#f4f6f9; padding:10px; border:1px solid #ddd;"></pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
var tableOnu;
var oltId = "<?php echo $olt['id']; ?>";
var currentOnuId = 0; // ID Database
var currentInterface = ''; // Nama Interface

$(document).ready(function() {
    // Inisialisasi DataTable
    tableOnu = $('#table-onu').DataTable({
        "processing": true, 
        "serverSide": true, 
        "responsive": true, 
        "ordering": false, // Matikan sort default biar cepat
        "ajax": {
            "url": "index.php?page=olt&action=get_onu_ajax&id_olt=" + oltId,
            "type": "GET",
            "data": function (d) { 
                d.pon = $('#filter_pon').val(); 
                d.status = $('#filter_status').val(); 
            }
        },
        "language": {
            "search": "Cari (SN/User):",
            "processing": "<i class='fas fa-spinner fa-spin'></i> Memuat Data..."
        }
    });
});

function reloadTable() { tableOnu.draw(); }

// --- SYNC ---
function syncOlt() {
    Swal.fire({
        title: 'Sinkronisasi...',
        text: 'Sedang membaca data OLT. Mohon tunggu.',
        allowOutsideClick: false,
        onBeforeOpen: () => { Swal.showLoading() }
    });

    $.ajax({
        url: 'index.php?page=olt&action=sync&id=' + oltId,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if(res.status == 'success') {
                Swal.fire('Selesai!', res.message, 'success').then(() => { location.reload(); });
            } else {
                Swal.fire('Gagal!', res.message, 'error');
            }
        },
        error: function() { Swal.fire('Error!', 'Timeout koneksi.', 'error'); }
    });
}

// --- DETAIL ---
function detailOnu(id) {
    currentOnuId = id; 
    $('#modal-onu').modal('show'); 
    $('#onu-content').hide(); 
    $('#onu-loading').show();

    $.ajax({
        url: 'index.php?page=olt&action=detail_onu&id=' + id, 
        type: 'GET', 
        dataType: 'json',
        success: function(res) {
            if(res.status == 'success') {
                currentInterface = res.interface; // Simpan interface utk keperluan action
                $('#d-interface').text(res.interface); 
                $('#d-status').text(res.state);
                $('#d-type').text(res.type); 
                $('#d-sn').text(res.sn); 
                $('#d-dist').text(res.distance);
                
                // Warna Redaman
                let rx = parseFloat(res.rx_power); 
                let color = (res.rx_power == 'N/A') ? 'text-muted' : (rx < -27 ? 'text-danger' : (rx < -24 ? 'text-warning' : 'text-success'));
                $('#d-rx').attr('class', color).text(res.rx_power + ' dBm'); 
                
                $('#raw-debug').text(res.raw_debug);
                
                $('#onu-loading').hide(); 
                $('#onu-content').fadeIn(); 
                reloadTable(); // Refresh tabel belakang agar data update
            } else { 
                alert(res.message); 
                $('#modal-onu').modal('hide'); 
            }
        }
    });
}

// --- REBOOT ---
function rebootOnu() {
    if(!confirm('Yakin ingin me-RESTART modem ini?')) return;
    
    let btn = $('#btn-reboot');
    btn.html('<i class="fas fa-spinner fa-spin"></i> Sending...').prop('disabled', true);

    $.post('index.php?page=olt&action=reboot_onu&id='+currentOnuId, function(res) {
        alert(res.message);
        btn.html('<i class="fas fa-sync"></i> REBOOT MODEM').prop('disabled', false);
    }, 'json');
}

// --- RESET FACTORY ---
function resetOnu() {
    if(!confirm('PERINGATAN KERAS!\n\nAnda akan melakukan RESET FACTORY.\nSemua konfigurasi modem akan hilang.\n\nLanjutkan?')) return;
    
    let btn = $('#btn-reset');
    btn.html('<i class="fas fa-spinner fa-spin"></i> Resetting...').prop('disabled', true);

    $.ajax({
        url: 'index.php?page=olt&action=reset_onu',
        type: 'POST',
        data: { id: currentOnuId },
        dataType: 'json',
        success: function(res) {
            if(res.status == 'success') {
                alert("SUKSES: " + res.message);
            } else {
                alert("GAGAL: " + res.message);
            }
            btn.html('<i class="fas fa-history"></i> RESET FACTORY').prop('disabled', false);
        }
    });
}

// --- DELETE ONU ---
function deleteOnu() {
    if(!confirm('BAHAYA: MENGHAPUS MODEM DARI OLT!\n\nInterface: ' + currentInterface + '\n\nData akan dihapus permanen dari OLT dan Database.\nPelanggan akan terputus.\n\nYakin hapus?')) return;

    let btn = $('#btn-delete');
    btn.html('<i class="fas fa-spinner fa-spin"></i> Deleting...').prop('disabled', true);

    $.ajax({
        url: 'index.php?page=olt&action=delete_onu',
        type: 'POST',
        data: { id: currentOnuId },
        dataType: 'json',
        success: function(res) {
            if(res.status == 'success') {
                alert(res.message);
                $('#modal-onu').modal('hide');
                reloadTable(); // Refresh tabel
            } else {
                alert("GAGAL: " + res.message);
                btn.html('<i class="fas fa-trash"></i> HAPUS DARI OLT').prop('disabled', false);
            }
        }
    });
}
</script>
