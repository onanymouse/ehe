<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header"><h1>Tambah OLT Baru</h1></div>
    <div class="content">
        <div class="card card-primary">
            <form action="index.php?page=olt&action=store" method="post">
                <div class="card-body">
                    <div class="form-group"><label>Nama OLT</label><input type="text" name="name" class="form-control" required placeholder="OLT Pusat"></div>
                    <div class="form-group"><label>IP Address</label><input type="text" name="ip_address" class="form-control" required></div>
                    <div class="form-group"><label>Telnet Port</label><input type="number" name="telnet_port" class="form-control" value="23" required></div>
                    <div class="form-group"><label>Username</label><input type="text" name="username" class="form-control" required></div>
                    <div class="form-group"><label>Password</label><input type="text" name="password" class="form-control" required></div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
