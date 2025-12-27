<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1>Data OLT (Optical Line Terminal)</h1></div>
        <div class="col-sm-6 text-right">
            <a href="index.php?page=olt&action=create" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah OLT</a>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <?php foreach($olts as $o): ?>
        <div class="col-md-4">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title"><b><?php echo $o['name']; ?></b></h3>
                    <div class="card-tools">
                        <a href="index.php?page=olt&action=delete&id=<?php echo $o['id']; ?>" class="btn btn-tool" onclick="return confirm('Hapus OLT ini?')"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted"><i class="fas fa-network-wired"></i> <?php echo $o['ip_address']; ?>:<?php echo $o['telnet_port']; ?></p>
                    <a href="index.php?page=olt&action=detail&id=<?php echo $o['id']; ?>" class="btn btn-success btn-block">
                        <i class="fas fa-search"></i> Masuk Dashboard OLT
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>