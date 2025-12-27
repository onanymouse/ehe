<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid"><h1>Monitoring Mikrotik</h1></div>
  </div>
  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <?php foreach($routers as $r): ?>
        <div class="col-md-4">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><b><?php echo $r['name']; ?></b></h3>
                </div>
                <div class="card-body">
                    <p>IP: <?php echo $r['ip_address']; ?></p>
                    <a href="index.php?page=mikrotik&action=monitor&id=<?php echo $r['id']; ?>" class="btn btn-primary btn-block">
                        <i class="fas fa-desktop"></i> Lihat User Online
                    </a>
                    <a href="index.php?page=mikrotik&action=secrets&id=<?php echo $r['id']; ?>" class="btn btn-default btn-block">
                        <i class="fas fa-list"></i> Lihat Semua Secret
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
