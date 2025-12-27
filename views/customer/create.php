<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Tambah Pelanggan Baru</h1></div>
        <div class="col-sm-6 text-right"><a href="index.php?page=customer&action=index" class="btn btn-default"><i class="fas fa-arrow-left"></i> Kembali</a></div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <form action="index.php?page=customer&action=store" method="post">
        
        <div class="row">
            <div class="col-md-6">
                <div class="card card-primary card-outline h-100">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-user-circle"></i> Data Pribadi</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Kode Pelanggan</label>
                            <input type="text" name="customer_code" class="form-control" value="<?php echo $customer_code; ?>" readonly style="background-color: #f4f6f9; font-weight: bold;">
                        </div>
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>No. WhatsApp</label>
                            <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxx" required>
                        </div>
                        <div class="form-group">
                            <label>Alamat Lengkap</label>
                            <textarea name="address" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Area</label>
                                    <select name="area_id" class="form-control">
                                        <option value="">- Pilih Area -</option>
                                        <?php foreach($areas as $a): ?><option value="<?php echo $a['id']; ?>"><?php echo $a['name']; ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Kolektor</label>
                                    <select name="collector_id" class="form-control">
                                        <option value="0">-- ALL (Umum) --</option>
                                        <?php foreach($collectors as $k): ?><option value="<?php echo $k['id']; ?>"><?php echo $k['fullname']; ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-success card-outline h-100">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-network-wired"></i> Layanan & Teknis</h3></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-8">
                                <div class="form-group">
                                    <label>Paket Internet</label>
                                    <select name="package_id" class="form-control" required>
                                        <option value="">- Pilih Paket -</option>
                                        <?php foreach($packages as $p): ?><option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?> - <?php echo format_rupiah($p['price']); ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Jatuh Tempo</label>
                                    <input type="number" name="due_date" class="form-control" value="20" min="1" max="28" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                             <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="auto_isolir" name="auto_isolir" value="1" checked>
                                <label class="custom-control-label" for="auto_isolir">Aktifkan Auto Isolir?</label>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div class="form-group bg-light p-2 rounded border">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_mikrotik" name="is_mikrotik" value="1" checked>
                                <label class="custom-control-label font-weight-bold" for="is_mikrotik">Integrasi ke Mikrotik</label>
                            </div>
                        </div>
                        <div id="mikrotik_section">
                            <div class="form-group">
                                <label>Pilih Router</label>
                                <select name="router_id" id="router_id" class="form-control">
                                    <option value="">- Pilih Router -</option>
                                    <?php foreach($routers as $r): ?><option value="<?php echo $r['id']; ?>"><?php echo $r['name']; ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Mode Koneksi:</label><br>
                                <div class="icheck-primary d-inline mr-3">
                                    <input type="radio" id="mode_create" name="mikrotik_mode" value="create_new" checked><label for="mode_create">Buat Secret Baru</label>
                                </div>
                                <div class="icheck-primary d-inline">
                                    <input type="radio" id="mode_connect" name="mikrotik_mode" value="connect_existing"><label for="mode_connect">Pakai Secret Lama</label>
                                </div>
                            </div>
                            <div id="box_create_new" class="p-3 mb-2" style="background: #e8f0fe; border-radius: 5px;">
                                <div class="form-group">
                                    <label>Profile Paket</label>
                                    <select name="mikrotik_profile_selected" id="mikrotik_profile_selected" class="form-control" disabled><option value="">- Pilih Router Dulu -</option></select>
                                </div>
                                <div class="row">
                                    <div class="col-6"><input type="text" name="pppoe_user_new" class="form-control" placeholder="Username Baru"></div>
                                    <div class="col-6"><input type="text" name="pppoe_password_new" class="form-control" placeholder="Password Baru"></div>
                                </div>
                            </div>
                            <div id="box_connect_existing" class="p-3 mb-2" style="background: #e6fffa; border-radius: 5px; display:none;">
                                <div class="form-group mb-0">
                                    <label>Cari User PPPoE</label>
                                    <select name="pppoe_user_existing" id="pppoe_user_existing" class="form-control select2" style="width: 100%;">
                                        <option value="">- Pilih Router Dulu -</option>
                                    </select>
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
                <option value="">- Tidak Terhubung ke OLT -</option>
                <?php foreach($olts as $o): ?>
                    <option value="<?php echo $o['id']; ?>"><?php echo $o['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="col-md-6">
                                <div class="form-group">
                                    <label>Interface Modem</label>
                                    <select name="onu_interface" id="onu_select" class="form-control select2">
                                        <option value="">- Pilih OLT Terlebih Dahulu -</option>
            </select>
            <small class="text-muted">Pastikan sudah Sync data di menu OLT.</small>
        </div>
    </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3 mb-5">
            <div class="col-12"><button type="submit" class="btn btn-primary btn-lg btn-block shadow">SIMPAN DATA</button></div>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#pppoe_user_existing').select2({ theme: 'bootstrap4', placeholder: "- Pilih Router Terlebih Dahulu -", allowClear: true, disabled: true });
    $('#is_mikrotik').change(function() { if(this.checked) { $('#mikrotik_section').slideDown(); } else { $('#mikrotik_section').slideUp(); } });
    $('input[name="mikrotik_mode"]').change(function() {
        if(this.value == 'create_new') { $('#box_create_new').slideDown(); $('#box_connect_existing').hide(); } 
        else { $('#box_create_new').hide(); $('#box_connect_existing').slideDown(); }
    });

    $('#router_id').change(function() {
        var routerId = $(this).val();
        var profileSelect = $('#mikrotik_profile_selected');
        var secretSelect = $('#pppoe_user_existing');
        
        secretSelect.val(null).trigger('change'); 
        secretSelect.empty(); 

        if(routerId != '') {
            profileSelect.html('<option>Loading...</option>').prop('disabled', true);
            $.ajax({
                url: 'index.php?page=router&action=get_profiles&id=' + routerId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    profileSelect.empty().append('<option value="">- Pilih Profile -</option>');
                    if(data.error) { alert(data.error); } else {
                        $.each(data, function(key, val) { if(val.name) profileSelect.append('<option value="'+val.name+'">'+val.name+'</option>'); });
                        profileSelect.prop('disabled', false);
                    }
                }
            });

            // LOGIKA FILTER DUPLIKAT
            secretSelect.select2({ theme: 'bootstrap4', placeholder: "Sedang mengambil data...", disabled: true });
            $.ajax({
                url: 'index.php?page=router&action=get_secrets&id=' + routerId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    secretSelect.append('<option value="">- Pilih User Existing -</option>');
                    if(!data.error) { 
                        $.each(data, function(key, val) {
                            if(val.name && val.service == 'pppoe') {
                                // JIKA SUDAH DIPAKAI: DISABLED & WARNA MERAH
                                if(val.used_by) {
                                    secretSelect.append('<option value="'+val.name+'" disabled style="color:red;">❌ '+val.name+' (Dipakai: '+val.used_by+')</option>');
                                } else {
                                    secretSelect.append('<option value="'+val.name+'">'+val.name+'</option>');
                                }
                            }
                        });
                        secretSelect.prop('disabled', false);
                        secretSelect.select2({ theme: 'bootstrap4', placeholder: "- Pilih User PPPoE -", allowClear: true, disabled: false });
                    }
                }
            });
        } else {
            profileSelect.html('<option value="">- Pilih Router Dulu -</option>').prop('disabled', true);
            secretSelect.select2({ theme: 'bootstrap4', placeholder: "- Pilih Router Terlebih Dahulu -", disabled: true });
        }
    });
});
// Fungsi Load ONU (Optimasi Array Join)
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
                if(item.state && item.state.toLowerCase().indexOf('online') !== -1) {
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
