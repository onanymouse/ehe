<?php 
include 'views/layouts/header.php'; 
include 'views/layouts/sidebar.php'; 
?>

<style>
    .card-gradient-blue { background: linear-gradient(45deg, #4099ff, #73b4ff); color: white; }
    .card-gradient-green { background: linear-gradient(45deg, #2ed8b6, #59e0c5); color: white; }
    .card-gradient-red { background: linear-gradient(45deg, #FF5370, #ff869a); color: white; }
    .card-gradient-grey { background: linear-gradient(45deg, #546e7a, #819ca9); color: white; }
    .icon-bg { font-size: 3rem; opacity: 0.3; position: absolute; right: 20px; top: 20px; }
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0 text-dark"><i class="fas fa-server text-primary"></i> OLT MANAGER PRO</h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <div class="card shadow-sm mb-4">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <label class="mb-0">1. Pilih Server OLT:</label>
                            <select id="select_olt" class="form-control font-weight-bold">
                                <option value="">-- Pilih Server --</option>
                                <?php foreach($olts as $o): ?><option value="<?= $o['id'] ?>"><?= $o['name'] ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="mb-0">2. Filter Interface:</label>
                            <select id="filter_pon" class="form-control">
                                <option value="">-- Tampil Semua --</option>
                            </select>
                        </div>

                        <div class="col-md-6 text-right pt-4">
                            <button id="btnAutoSync" class="btn btn-primary font-weight-bold shadow-sm" onclick="startAutoSync()">
                                <i class="fas fa-satellite-dish"></i> AUTO SYNC ALL
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row" id="dashboard_area" style="display:none;">
                <div class="col-md-3 col-6">
                    <div class="card card-gradient-blue">
                        <div class="card-body"><h3 id="count_total">0</h3><p>Total ONU</p><i class="fas fa-users icon-bg"></i></div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card card-gradient-green">
                        <div class="card-body"><h3 id="count_online">0</h3><p>Online</p><i class="fas fa-check-circle icon-bg"></i></div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card card-gradient-red">
                        <div class="card-body"><h3 id="count_los">0</h3><p>Redaman/LOS</p><i class="fas fa-exclamation-triangle icon-bg"></i></div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card card-gradient-grey">
                        <div class="card-body"><h3 id="count_offline">0</h3><p>Offline</p><i class="fas fa-power-off icon-bg"></i></div>
                    </div>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header bg-white border-0">
                    <h3 class="card-title text-primary"><i class="fas fa-list"></i> Data ONU (Database)</h3>
                </div>
                <div class="card-body table-responsive">
                    <table id="tableOnu" class="table table-hover table-striped w-100">
                        <thead class="bg-light">
                            <tr><th>Interface</th><th>Nama/Desc</th><th>SN/Type</th><th>Status</th><th>Signal</th><th>Aksi</th></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<div class="modal fade" id="modalAction">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Kontrol ONU</h5><button class="close text-white" data-dismiss="modal">&times;</button></div>
            <div class="modal-body text-center">
                <h4 id="act_interface" class="font-weight-bold mb-1"></h4>
                <p id="act_name" class="text-muted mb-4"></p>
                <div class="row">
                    <div class="col-6 mb-2"><button class="btn btn-warning btn-block" onclick="doAction('push_tr069')"><i class="fas fa-upload"></i> PUSH TR069</button></div>
                    <div class="col-6 mb-2"><button class="btn btn-secondary btn-block" onclick="doAction('reboot')"><i class="fas fa-sync"></i> REBOOT</button></div>
                    <div class="col-6"><button class="btn btn-danger btn-block" onclick="doAction('reset')"><i class="fas fa-history"></i> RESET FACTORY</button></div>
                    <div class="col-6"><button class="btn btn-dark btn-block" onclick="doAction('delete')"><i class="fas fa-trash"></i> HAPUS ONU</button></div>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

<script>
var table;

// 1. KETIKA OLT DIPILIH
$('#select_olt').change(function(){
    var id = $(this).val();
    
    if(id) {
        $('#dashboard_area').fadeIn();
        loadStats(id);   
        loadDbPons(id);  // Load Filter Dropdown dari DB
        initTable(id);   
    } else {
        $('#dashboard_area').hide();
        $('#filter_pon').html('<option value="">-- Tampil Semua --</option>');
        if ($.fn.DataTable.isDataTable('#tableOnu')) { table.destroy(); $('#tableOnu tbody').empty(); }
    }
});

// 2. KETIKA FILTER PON DIGANTI
$('#filter_pon').change(function(){
    if(table) table.ajax.reload();
});

// LOAD FILTER PON DARI DATABASE
function loadDbPons(id) {
    $.post('index.php?page=olt_manager_pro&action=get_db_pons', {id: id}, function(res){
        var opt = '<option value="">-- Tampil Semua --</option>';
        if(res.status == 'success') {
            $.each(res.data, function(i, p){
                opt += '<option value="'+p+'">'+p+'</option>';
            });
        }
        $('#filter_pon').html(opt);
    }, 'json');
}

// INIT TABLE
function initTable(id) {
    if ($.fn.DataTable.isDataTable('#tableOnu')) { table.destroy(); }

    table = $('#tableOnu').DataTable({
        "processing": true,
        "serverSide": true,
        "order": [],
        "ajax": {
            "url": "index.php?page=olt_manager_pro&action=get_table_data",
            "type": "POST",
            "data": function(d) {
                d.olt_id = id;
                d.filter_pon = $('#filter_pon').val(); 
            }
        },
        "columns": [
            { "data": "interface" },
            { "data": "name" },
            { "data": "sn" },
            { "data": "status" },
            { "data": "rx_power" },
            { "data": "action", "orderable": false }
        ]
    });
}

function loadStats(id) {
    $.post('index.php?page=olt_manager_pro&action=get_stats', {olt_id:id}, function(res){
        if(res.status=='success') {
            $('#count_total').text(res.data.total); $('#count_online').text(res.data.online);
            $('#count_los').text(res.data.los); $('#count_offline').text(res.data.offline);
        }
    }, 'json');
}

// 3. LOGIC SYNC (AUTO DISCOVERY)
function startAutoSync() {
    var id = $('#select_olt').val(); 
    if(!id) { Swal.fire('Error', 'Silakan Pilih OLT Terlebih Dahulu!', 'error'); return; }

    Swal.fire({
        title: 'Mulai Auto Sync?',
        text: "Sistem akan mendeteksi seluruh port di OLT dan mengupdate database.",
        icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Mulai!'
    }).then((r) => { if (r.isConfirmed) processDiscovery(id); });
}

function processDiscovery(id) {
    var btn = $('#btnAutoSync'); btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Detecting...');
    // Minta list port hardware ke Controller
    $.post('index.php?page=olt_manager_pro&action=get_pon_interfaces', {id: id}, function(res){
        if(res.status == 'success' && res.data.length > 0) {
            processSyncQueue(id, res.data, 0);
        } else {
            Swal.fire('Info', 'Tidak ditemukan Port PON.', 'info');
            btn.prop('disabled', false).html('<i class="fas fa-satellite-dish"></i> AUTO SYNC ALL');
        }
    }, 'json').fail(function(){
        Swal.fire('Error', 'Gagal koneksi ke OLT.', 'error');
        btn.prop('disabled', false).html('<i class="fas fa-satellite-dish"></i> AUTO SYNC ALL');
    });
}

function processSyncQueue(id, ports, index) {
    var total = ports.length;
    var currentPort = ports[index];
    var percent = Math.round((index / total) * 100);

    Swal.fire({
        title: 'Sedang Sinkronisasi...',
        html: 'Scanning: <b>' + currentPort + '</b><br>(' + (index+1) + '/' + total + ')<div class="progress mt-2"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width: '+percent+'%">'+percent+'%</div></div>',
        allowOutsideClick: false,
        showConfirmButton: false
    });

    $.ajax({
        url: 'index.php?page=olt_manager_pro&action=sync_data',
        type: 'POST',
        data: {olt_id: id, pon_interface: currentPort},
        dataType: 'json',
        success: function(res) { 
            if(res.status == 'error') {
                // JIKA ERROR / KOSONG -> TAMPILKAN DEBUG
                console.log("Error at " + currentPort + ": " + res.message);
                if(res.debug_raw) {
                    alert("DEBUG INFO ("+currentPort+"):\n" + res.debug_raw); // POPUP ISI DATA MENTAH
                }
            }
            nextQueue(id, ports, index, total); 
        },
        error: function(xhr, status, error) { 
            console.log("Ajax Error: " + error);
            console.log(xhr.responseText); // Lihat error PHP di console
            nextQueue(id, ports, index, total); 
        }
    });
}


function nextQueue(id, ports, index, total) {
    if(index + 1 < total) { processSyncQueue(id, ports, index + 1); } 
    else {
        Swal.fire('Selesai!', 'Database Updated.', 'success');
        $('#btnAutoSync').prop('disabled', false).html('<i class="fas fa-satellite-dish"></i> AUTO SYNC ALL');
        loadStats(id); loadDbPons(id); table.ajax.reload(null, false);
    }
}

// ACTION HANDLER
var selectedOnu = {};
function openDetail(data) {
    selectedOnu = data;
    $('#act_interface').text($(data.interface).text()); $('#act_name').text($(data.name).text());
    $('#modalAction').modal('show');
}

function doAction(type) {
    var id = $('#select_olt').val();
    var iface = $(selectedOnu.interface).text();
    var actionUrl = (type=='reboot')?'reboot_onu':(type=='reset')?'reset_onu':(type=='delete')?'delete_onu':'push_tr069';
    var confirmMsg = (type=='delete')?'HAPUS PERMANEN? Internet Mati!':(type=='reset')?'RESET FACTORY? Config Hilang!':'Lanjutkan?';
    
    Swal.fire({ title: 'Konfirmasi', text: confirmMsg, icon: 'warning', showCancelButton: true }).then((r) => {
        if(r.isConfirmed) {
            $('#modalAction').modal('hide'); Swal.fire({title: 'Processing...', didOpen:()=>{Swal.showLoading()}});
            $.post('index.php?page=olt_manager_pro&action='+actionUrl, {olt_id: id, interface: iface}, function(res){
                if(res.status=='success'){
                    Swal.fire('Berhasil', res.message, 'success');
                    if(type=='delete') { table.ajax.reload(null, false); loadStats(id); }
                } else { Swal.fire('Gagal', res.message, 'error'); }
            }, 'json');
        }
    });
}
</script>

<?php include 'views/layouts/footer.php'; ?>
