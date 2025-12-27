<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Edit Pelanggan</h1></div>
            <div class="col-sm-6 text-right"><a href="index.php?page=customer&action=index" class="btn btn-default"><i class="fas fa-arrow-left"></i> Kembali</a></div>
        </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <form action="index.php?page=customer&action=update" method="post">
        <input type="hidden" name="id" value="<?php echo $customer['id']; ?>">
        <input type="hidden" name="customer_code_hidden" value="<?php echo $customer['customer_code']; ?>">
        
        <div class="row">
            <div class="col-md-6">
                <div class="card card-warning card-outline h-100">
                    <div class="card-header"><h3 class="card-title">Data Pribadi</h3></div>
                    <div class="card-body">
                        <div class="form-group"><label>Kode</label><input type="text" class="form-control" value="<?php echo $customer['customer_code']; ?>" readonly style="background: #eee;"></div>
                        <div class="form-group"><label>Nama</label><input type="text" name="name" class="form-control" value="<?php echo $customer['name']; ?>" required></div>
                        <div class="form-group"><label>WA</label><input type="text" name="phone" class="form-control" value="<?php echo $customer['phone']; ?>" required></div>
                        <div class="form-group"><label>Alamat</label><textarea name="address" class="form-control" rows="2"><?php echo $customer['address']; ?></textarea></div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group"><label>Area</label><select name="area_id" class="form-control"><?php foreach($areas as $a): ?><option value="<?php echo $a['id']; ?>" <?php if($a['id'] == $customer['area_id']) echo 'selected'; ?>><?php echo $a['name']; ?></option><?php endforeach; ?></select></div>
                            </div>
                            <div class="col-6">
                                <div class="form-group"><label>Kolektor</label><select name="collector_id" class="form-control"><option value="0">-- ALL --</option><?php foreach($collectors as $k): ?><option value="<?php echo $k['id']; ?>" <?php if($k['id'] == $customer['collector_id']) echo 'selected'; ?>><?php echo $k['fullname']; ?></option><?php endforeach; ?></select></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-danger card-outline h-100">
                    <div class="card-header"><h3 class="card-title">Layanan & Integrasi</h3></div>
                    <div class="card-body">
                        
                        <div class="form-group bg-light p-2 border rounded">
                            <label>Status Berlangganan</label>
                            <select name="status" class="form-control font-weight-bold">
                                <option value="active" class="text-success" <?php if($customer['status'] == 'active') echo 'selected'; ?>>✅ AKTIF</option>
                                <option value="nonactive" class="text-secondary" <?php if($customer['status'] == 'nonactive') echo 'selected'; ?>>⚫ NON-AKTIF</option>
                                <option value="isolated" class="text-danger" <?php if($customer['status'] == 'isolated') echo 'selected'; ?>>🔒 TERISOLIR</option>
                            </select>
                        </div>

                        <div class="form-group"><label>Paket</label><select name="package_id" class="form-control"><?php foreach($packages as $p): ?><option value="<?php echo $p['id']; ?>" <?php if($p['id'] == $customer['package_id']) echo 'selected'; ?>><?php echo $p['name']; ?></option><?php endforeach; ?></select></div>
                        
                        <div class="row">
                            <div class="col-6"><div class="form-group"><label>Jatuh Tempo</label><input type="number" name="due_date" class="form-control" value="<?php echo $customer['due_date']; ?>"></div></div>
                            <div class="col-6"><div class="form-group pt-4"><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="auto_isolir" name="auto_isolir" value="1" <?php if($customer['auto_isolir'] == 1) echo 'checked'; ?>><label class="custom-control-label" for="auto_isolir">Auto Isolir</label></div></div></div>
                        </div>
                        
                        <div class="dropdown-divider"></div>
                        <div class="form-group bg-light p-2 rounded border">
                            <div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="is_mikrotik" name="is_mikrotik" value="1" <?php if($customer['is_mikrotik'] == 1) echo 'checked'; ?>><label class="custom-control-label font-weight-bold" for="is_mikrotik">Integrasi Mikrotik (PPPoE)</label></div>
                        </div>

                        <div id="mikrotik_section" <?php if($customer['is_mikrotik'] == 0) echo 'style="display:none;"'; ?>>
                            <div class="form-group">
                                <label>Router</label>
                                <select name="router_id" id="router_id" class="form-control">
                                    <option value="">- Pilih Router -</option>
                                    <?php foreach($routers as $r): ?><option value="<?php echo $r['id']; ?>" <?php if($r['id'] == $customer['router_id']) echo 'selected'; ?>><?php echo $r['name']; ?></option><?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Metode Koneksi:</label><br>
                                <div class="icheck-primary d-inline mr-3"><input type="radio" id="mode_connect" name="mikrotik_mode" value="connect_existing" checked><label for="mode_connect">Existing</label></div>
                                <div class="icheck-primary d-inline"><input type="radio" id="mode_create" name="mikrotik_mode" value="create_new"><label for="mode_create">Buat Baru</label></div>
                            </div>
                            
                            <div id="box_connect_existing" class="p-2 bg-light border rounded">
                                <div class="form-group mb-0">
                                    <label>Cari User PPPoE</label>
                                    <select name="pppoe_user_existing" id="pppoe_user_existing" class="form-control select2" style="width: 100%;">
                                        <?php if($customer['pppoe_user']): ?>
                                            <option value="<?php echo $customer['pppoe_user']; ?>" selected><?php echo $customer['pppoe_user']; ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div id="box_create_new" class="p-2 bg-light border rounded" style="display:none;">
                                <div class="form-group"><label>Profile</label><select name="mikrotik_profile_selected" id="mikrotik_profile_selected" class="form-control"></select></div>
                                <div class="row">
                                    <div class="col-6"><input type="text" name="pppoe_user_new" class="form-control" placeholder="User Baru"></div>
                                    <div class="col-6"><input type="text" name="pppoe_password_new" class="form-control" placeholder="Pass Baru"></div>
                                </div>
                            </div>
                        </div>

                        <div class="dropdown-divider"></div>
                        <h6 class="text-primary font-weight-bold"><i class="fas fa-network-wired"></i> Integrasi OLT</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Pilih OLT</label>
                                    <select name="olt_id" id="olt_select" class="form-control" onchange="loadOnuList(this.value)">
                                        <option value="">- Tidak Terhubung -</option>
                                        <?php foreach($olts as $o): ?>
                                            <option value="<?php echo $o['id']; ?>" <?php echo ($customer['olt_id'] == $o['id']) ? 'selected' : ''; ?>>
                                                <?php echo $o['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Interface Modem</label>
                                    <select name="onu_interface" id="onu_select" class="form-control select2">
                                        <option value="<?php echo $customer['onu_interface']; ?>" selected>
                                            <?php echo $customer['onu_interface']; ?> (Tersimpan)
                                        </option>
                                    </select>
                                    <small class="text-muted">Pilih OLT dulu untuk refresh list.</small>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer"><button type="submit" class="btn btn-danger btn-block">SIMPAN PERUBAHAN</button></div>
                </div>
            </div>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
$(document).ready(function() {
    // === SETUP MIKROTIK (LAMA - TETAP) ===
    var currentUser = "<?php echo $customer['pppoe_user']; ?>";
    var currentRouter = "<?php echo $customer['router_id']; ?>";

    $('#pppoe_user_existing').select2({ theme: 'bootstrap4', width: '100%' });
    $('#onu_select').select2({ theme: 'bootstrap4', width: '100%' }); 

    $('#is_mikrotik').change(function() { if(this.checked) { $('#mikrotik_section').slideDown(); } else { $('#mikrotik_section').slideUp(); } });
    
    $('input[name="mikrotik_mode"]').change(function() {
        if(this.value == 'create_new') { $('#box_create_new').slideDown(); $('#box_connect_existing').hide(); } 
        else { $('#box_create_new').hide(); $('#box_connect_existing').slideDown(); }
    });

    function loadRouterData(routerId) {
        if(!routerId) return;
        var profileSelect = $('#mikrotik_profile_selected');
        var secretSelect = $('#pppoe_user_existing');
        secretSelect.prop('disabled', true); 

        $.ajax({
            url: 'index.php?page=router&action=get_secrets&id=' + routerId,
            type: 'GET', dataType: 'json',
            success: function(data) {
                secretSelect.empty().append('<option value="">- Pilih User -</option>');
                if(!data.error) {
                    // Optimasi Loop Router
                    var opts = [];
                    $.each(data, function(key, val) {
                        if(val.name && val.service == 'pppoe') {
                            var isSel = (val.name == currentUser) ? 'selected' : '';
                            if(val.used_by && val.name != currentUser) {
                                opts.push('<option value="'+val.name+'" disabled>❌ '+val.name+' (Dipakai)</option>');
                            } else {
                                opts.push('<option value="'+val.name+'" '+isSel+'>'+val.name+'</option>');
                            }
                        }
                    });
                    secretSelect.append(opts.join(''));
                    secretSelect.prop('disabled', false).trigger('change');
                }
            }
        });

        $.ajax({
            url: 'index.php?page=router&action=get_profiles&id=' + routerId,
            type: 'GET', dataType: 'json',
            success: function(data) {
                profileSelect.empty();
                if(!data.error) {
                    var opts = [];
                    $.each(data, function(key, val) { if(val.name) opts.push('<option value="'+val.name+'">'+val.name+'</option>'); });
                    profileSelect.append(opts.join(''));
                }
            }
        });
    }

    $('#router_id').change(function() { loadRouterData($(this).val()); });
    if($('#is_mikrotik').is(':checked') && currentRouter) { loadRouterData(currentRouter); }


    // === SETUP OLT (OPTIMIZED) ===
    var savedOltId = "<?php echo $customer['olt_id']; ?>";
    var savedInterface = "<?php echo $customer['onu_interface']; ?>";

    if(savedOltId && savedOltId != "0") {
        loadOnuList(savedOltId, savedInterface);
    }
});

// Fungsi Load ONU (Optimasi Array Join)
function loadOnuList(oltId, selectedIf = '') {
    if(!oltId) {
        $('#onu_select').html('<option value="">- Pilih OLT Dulu -</option>');
        return;
    }
    
    // Tampilkan Loading (Non-Blocking)
    $('#onu_select').html('<option>Sedang memuat data...</option>');
    
    $.ajax({
        url: 'index.php?page=customer&action=get_onu_list&olt_id=' + oltId,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            // Gunakan Array untuk menampung HTML (Jauh lebih cepat daripada string +=)
            var options = ['<option value="">- Pilih Interface ONU -</option>'];
            
            for (var i = 0; i < data.length; i++) {
                var item = data[i];
                var isSel = (item.interface == selectedIf) ? 'selected' : '';
                
                // Icon status sederhana
                var icon = '🔴';
                if(item.state && item.state.toLowerCase().indexOf('working') !== -1) {
                    icon = '🟢';
                }
                
                var sn = item.sn ? item.sn : 'No SN';
                options.push(`<option value="${item.interface}" ${isSel}>${icon} ${item.interface} (${sn})</option>`);
            }
            
            // Render sekaligus
            $('#onu_select').html(options.join('')).trigger('change');
        },
        error: function() {
            $('#onu_select').html('<option value="">Gagal memuat data (Timeout)</option>');
        }
    });
}
</script>

