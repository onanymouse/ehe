<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Profil Saya</h1>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-6">
          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title">Edit Akun</h3>
            </div>
            
            <form action="index.php?page=profile&action=update" method="post">
              <div class="card-body">
                
                <div class="form-group">
                  <label>Username</label>
                  <input type="text" class="form-control" value="<?php echo $user['username']; ?>" readonly disabled>
                  <small class="text-muted">Username tidak bisa diubah.</small>
                </div>

                <div class="form-group">
                  <label>Role / Jabatan</label>
                  <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" readonly disabled>
                </div>

                <div class="form-group">
                  <label>Nama Lengkap</label>
                  <input type="text" name="fullname" class="form-control" value="<?php echo $user['fullname']; ?>" required>
                </div>

                <div class="form-group">
                  <label>Password Baru</label>
                  <input type="password" name="password" class="form-control" placeholder="(Biarkan kosong jika tidak ingin mengganti password)">
                  <small class="text-danger">Isi hanya jika ingin mengubah password login Anda.</small>
                </div>

              </div>

              <div class="card-footer">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
              </div>
            </form>
          </div>
        </div>
        
        <div class="col-md-6">
            <div class="callout callout-info">
                <h5><i class="fas fa-info"></i> Info Akun</h5>
                <p>
                    Terdaftar Sejak: <b><?php echo date('d F Y', strtotime($user['created_at'] ?? date('Y-m-d'))); ?></b><br>
                    Login Terakhir: <b><?php echo $user['last_login']; ?></b>
                </p>
            </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
