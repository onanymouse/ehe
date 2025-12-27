<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1>Daftar Paket Internet</h1></div>
        <div class="col-sm-6 text-right">
          <button class="btn btn-primary" onclick="addPackage()">
            <i class="fas fa-plus"></i> Tambah Paket
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="card card-outline card-primary">
        <div class="card-body">
          <table id="table-package" class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>Nama Paket</th>
                <th>Harga</th>
                <th>Profile Mikrotik</th>
                <th>Keterangan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($packages as $p): ?>
              <tr>
                <td><b><?php echo $p['name']; ?></b></td>
                <td>Rp <?php echo number_format($p['price'], 0, ',', '.'); ?></td>
                <td>
                    <span class="badge badge-info"><i class="fas fa-tag"></i> <?php echo $p['mikrotik_profile']; ?></span>
                </td>
                <td><?php echo $p['description'] ?? '-'; ?></td>

                <td>
                  <button class="btn btn-sm btn-warning" onclick="editPackage(<?php echo $p['id']; ?>)"><i class="fas fa-edit"></i></button>
                  <a href="index.php?page=package&action=delete&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus paket ini?')"><i class="fas fa-trash"></i></a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-package">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h4 class="modal-title" id="modal-title">Tambah Paket</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="form-package">
                <div class="modal-body">
                    <input type="hidden" name="id" id="id">
                    
                    <div class="form-group">
                        <label>Nama Paket</label>
                        <input type="text" name="name" id="name" class="form-control" required placeholder="Contoh: Paket Gold 10M">
                    </div>
                    
                    <div class="form-group">
                        <label>Harga (Rp)</label>
                        <input type="number" name="price" id="price" class="form-control" required placeholder="150000">
                    </div>

                    <div class="form-group bg-light p-2 border rounded">
                        <label class="text-primary"><i class="fas fa-network-wired"></i> Integrasi Profile Mikrotik</label>
                        
                        <div class="input-group mb-2">
                            <select id="scan_router_id" class="form-control">
                                <option value="">- Pilih Router Sumber -</option>
                                <?php foreach($routers as $r): ?>
                                    <option value="<?php echo $r['id']; ?>"><?php echo $r['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="input-group-append">
                                <button type="button" class="btn btn-info" onclick="scanProfile()">
                                    <i class="fas fa-sync"></i> Scan
                                </button>
                            </span>
                        </div>

                        <div class="form-group mb-0">
                            <label>Pilih Profile</label>
                            <select name="mikrotik_profile" id="mikrotik_profile" class="form-control select2" style="width:100%" required>
                                <option value="default">default</option>
                            </select>
                            <small class="text-danger">* Profile ini yang akan dipasang saat user lunas.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="description" id="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-save">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#table-package').DataTable({ responsive: true });
    // Init Select2 untuk Profile agar mudah dicari
    $('#mikrotik_profile').select2({ theme: 'bootstrap4', dropdownParent: $('#modal-package') });

    // Handle Submit
    $('#form-package').submit(function(e) {
        e.preventDefault();
        var id = $('#id').val();
        var url = (id == '') ? 'index.php?page=package&action=store' : 'index.php?page=package&action=update';
        var btn = $('#btn-save');

        btn.text('Menyimpan...').prop('disabled', true);

        $.ajax({
            url: url, type: 'POST', data: $(this).serialize(), dataType: 'json',
            success: function(res) {
                if(res.status == 'success') { $('#modal-package').modal('hide'); alert('Berhasil!'); location.reload(); } 
                else { alert('Gagal: ' + res.message); }
                btn.text('Simpan').prop('disabled', false);
            },
            error: function() { alert('Error Server'); btn.text('Simpan').prop('disabled', false); }
        });
    });
});

// FUNGSI LOAD PROFILE DARI ROUTER (REUSE API RouterController)
function scanProfile() {
    var routerId = $('#scan_router_id').val();
    if(!routerId) { alert("Pilih router sumber dulu!"); return; }
    
    var btn = $('button[onclick="scanProfile()"]');
    var select = $('#mikrotik_profile');
    
    btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
    select.empty().append('<option>Loading...</option>');

    // Panggil API get_profiles (by ID) yang sudah kita fix di RouterController
    $.ajax({
        url: 'index.php?page=router&action=get_profiles&id=' + routerId,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            btn.html('<i class="fas fa-sync"></i> Scan').prop('disabled', false);
            select.empty();
            
            if(data.error) {
                alert("Gagal konek router!");
                select.append('<option value="default">default</option>');
            } else {
                // Populate Dropdown
                select.append('<option value="default">default</option>');
                data.forEach(function(item) {
                    select.append('<option value="'+item.name+'">'+item.name+'</option>');
                });
                alert("Profile berhasil dimuat!");
            }
        },
        error: function() {
            btn.html('<i class="fas fa-sync"></i> Scan').prop('disabled', false);
            select.empty().append('<option value="default">default</option>');
            alert("Gagal menghubungi server.");
        }
    });
}

function addPackage() {
    $('#form-package')[0].reset();
    $('#id').val('');
    $('#mikrotik_profile').val('default').trigger('change'); // Reset Select2
    $('#modal-title').text('Tambah Paket');
    $('#modal-package').modal('show');
}

function editPackage(id) {
    $('#form-package')[0].reset();
    $('#modal-title').text('Edit Paket');
    
    $.ajax({
        url: 'index.php?page=package&action=edit&id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#id').val(data.id);
            $('#name').val(data.name);
            $('#price').val(data.price);
            $('#description').val(data.description);
            
            // Set Profile Manual (Karena dropdown mungkin kosong belum discan)
            // Kita tambahkan optionnya manual agar terpilih
            var exists = $("#mikrotik_profile option[value='"+data.mikrotik_profile+"']").length > 0;
            if(!exists) {
                $('#mikrotik_profile').append(new Option(data.mikrotik_profile, data.mikrotik_profile, true, true));
            }
            $('#mikrotik_profile').val(data.mikrotik_profile).trigger('change');
            
            $('#modal-package').modal('show');
        }
    });
}
</script>
