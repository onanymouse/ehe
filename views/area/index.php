<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Data Wilayah / Area</h1></div>
        <div class="col-sm-6 text-right">
          <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-tambah">
            <i class="fas fa-plus"></i> Tambah Area
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="card card-outline card-primary">
        <div class="card-body">
          <table class="table table-bordered table-striped table-hover table-responsive-data" style="width:100%">
            <thead>
              <tr>
                <th style="width: 10px">#</th>
                <th>Nama Area</th>
                <th>Kode Area</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if(count($areas) > 0): ?>
                <?php $no=1; foreach($areas as $a): ?>
                <tr>
                  <td><?php echo $no++; ?></td>
                  <td class="text-bold"><?php echo $a['name']; ?></td>
                  <td><?php echo $a['code']; ?></td>
                  <td>
                    <button type="button" class="btn btn-sm btn-warning" onclick="editArea(<?php echo $a['id']; ?>)" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>

                    <a href="index.php?page=area&action=delete&id=<?php echo $a['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus area ini?')">
                      <i class="fas fa-trash"></i>
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                  <tr><td colspan="4" class="text-center">Belum ada data area.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-tambah">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h4 class="modal-title">Tambah Area</h4>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form action="index.php?page=area&action=store" method="post">
        <div class="modal-body">
          <div class="form-group">
            <label>Nama Area</label>
            <input type="text" name="name" class="form-control" placeholder="Contoh: Perum Griya Asri" required>
          </div>
          <div class="form-group">
            <label>Kode Unik (Opsional)</label>
            <input type="text" name="code" class="form-control" placeholder="Contoh: BLOK-A">
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-edit">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h4 class="modal-title">Edit Area</h4>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form action="index.php?page=area&action=update" method="post">
        <div class="modal-body">
          <input type="hidden" name="id" id="edit_id">
          
          <div class="form-group">
            <label>Nama Area</label>
            <input type="text" name="name" id="edit_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Kode Unik</label>
            <input type="text" name="code" id="edit_code" class="form-control">
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-warning font-weight-bold">Update Area</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
function editArea(id) {
    // 1. Tampilkan Modal
    $('#modal-edit').modal('show');
    $('#edit_name').val('Loading...');
    $('#edit_code').val('');

    // 2. Ambil Data
    $.ajax({
        url: 'index.php?page=area&action=edit&id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#edit_id').val(data.id);
            $('#edit_name').val(data.name);
            $('#edit_code').val(data.code);
        },
        error: function() {
            alert('Gagal mengambil data area.');
            $('#modal-edit').modal('hide');
        }
    });
}
</script>
