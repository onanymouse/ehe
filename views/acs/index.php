<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1>ACS Device Manager</h1></div>
        <div class="col-sm-6 text-right">
           <button class="btn btn-outline-primary" onclick="$('#modal-server').modal('show')"><i class="fas fa-server"></i> Tambah Server ACS</button>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="card card-primary card-outline">
          <div class="card-body">
              <div class="form-group row">
                  <label class="col-sm-2 col-form-label">Pilih Server ACS:</label>
                  <div class="col-sm-6">
                      <select id="select-server" class="form-control">
                          <option value="">-- Pilih Server --</option>
                          <?php foreach($servers as $s): ?>
                              <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?> (<?php echo $s['url']; ?>)</option>
                          <?php endforeach; ?>
                      </select>
                  </div>
              </div>
          </div>
      </div>

      <div class="card">
        <div class="card-header bg-dark">
            <h3 class="card-title">Daftar Perangkat (Live)</h3>
        </div>
        <div class="card-body">
          <table id="table-acs" class="table table-bordered table-striped" style="width:100%">
            <thead>
              <tr>
                <th>Perangkat (SN/Model)</th>
                <th>Status (IP/Last Seen)</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
          <div id="loading-msg" class="text-center text-muted mt-3" style="display:none;">Pilih server untuk memuat data...</div>
        </div>
      </div>

    </div>
  </div>
</div>

<div class="modal fade" id="modal-server">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Server GenieACS</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="index.php?page=acs&action=manage_servers" method="post">
                <div class="modal-body">
                    <div class="form-group"><label>Nama Server</label><input type="text" name="name" class="form-control" placeholder="Contoh: ACS Jakarta" required></div>
                    <div class="form-group"><label>URL API</label><input type="text" name="url" class="form-control" placeholder="http://ip-address:7557" required></div>
                    <div class="form-group"><label>Username</label><input type="text" name="user" class="form-control" required></div>
                    <div class="form-group"><label>Password</label><input type="text" name="pass" class="form-control" required></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
var table;

$(document).ready(function() {
    
    // INISIALISASI DATATABLE KOSONG
    table = $('#table-acs').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "index.php?page=acs&action=ajax_list",
            "type": "POST",
            "data": function ( d ) {
                d.server_id = $('#select-server').val(); // Kirim ID Server terpilih
            },
            "error": function() {
                // Handle error jika server mati
                $('#table-acs_processing').hide();
                alert("Gagal mengambil data dari Server ACS.");
            }
        },
        "columns": [
            { "orderable": false }, // Kolom SN
            { "orderable": false }, // Kolom IP
            { "orderable": false }  // Kolom Aksi
        ],
        "language": {
            "emptyTable": "Pilih Server ACS terlebih dahulu."
        },
        "deferLoading": 0 // Jangan load data dulu saat awal buka
    });

    // SAAT DROPDOWN BERUBAH
    $('#select-server').change(function() {
        var id = $(this).val();
        if(id) {
            table.draw(); // Trigger Datatables untuk reload
        } else {
            table.clear().draw();
        }
    });

});

function detailDevice(sn, serverId) {
    alert("Fitur Detail (Reboot/Ganti Pass) akan kita buat di tahap selanjutnya.\nSN: " + sn);
}
</script>
