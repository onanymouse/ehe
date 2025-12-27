<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="#" class="brand-link">
      <span class="brand-text font-weight-light px-3"><b>ISP</b> BILLING PRO</span>
    </a>

    <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['fullname']); ?>" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="index.php?page=profile&action=index" class="d-block"><?php echo $_SESSION['fullname']; ?></a>
        </div>
      
      
      </div>

      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          
          <li class="nav-item">
            <a href="index.php?page=dashboard" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>

          <li class="nav-header">OPERASIONAL</li>

          <li class="nav-item">
            <a href="index.php?page=customer&action=index" class="nav-link">
              <i class="nav-icon fas fa-users"></i>
              <p>Data Pelanggan</p>
            </a>
          </li>

          <?php if(in_array($_SESSION['role'], ['admin', 'kolektor', 'keuangan'])): ?>
          <li class="nav-item">
            <a href="index.php?page=billing&action=index" class="nav-link">
              <i class="nav-icon fas fa-file-invoice-dollar"></i>
              <p>Tagihan & Bayar</p>
            </a>
          </li>
          <?php endif; ?>

          <?php if(in_array($_SESSION['role'], ['admin', 'teknisi'])): ?>
          <li class="nav-header">NETWORK</li>
          
          <?php if($_SESSION['role'] == 'admin'): ?>
          <li class="nav-item">
            <a href="index.php?page=router&action=index" class="nav-link">
              <i class="nav-icon fas fa-server"></i>
              <p>Data Router (Master)</p>
            </a>
          </li>
          <?php endif; ?>

          <li class="nav-item">
            <a href="index.php?page=mikrotik&action=index" class="nav-link">
              <i class="nav-icon fas fa-network-wired"></i>
              <p>Monitoring & Active</p>
            </a>
          </li>
         <!-- <li class="nav-item">
    <a href="index.php?page=olt_manager_pro" class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] == 'olt_manager_pro') ? 'active' : ''; ?>">
        <i class="nav-icon fas fa-server text-warning"></i>
        <p>
            OLT Manager Pro
            <span class="right badge badge-danger">NEW</span>
        </p>
    </a>
</li>-->

          <li class="nav-item">
    <a href="index.php?page=olt&action=index" class="nav-link">
        <i class="nav-icon fas fa-broadcast-tower"></i>
        <p>Data OLT (FTTH)</p>
    </a>
</li>
<li class="nav-item">
    <a href="index.php?page=zte_pro&action=index" class="nav-link">
        <i class="nav-icon fas fa-broadcast-tower"></i>
        <p>Config Onu OLT</p>
    </a>
</li>

         <!-- <?php if($_SESSION['role'] == 'admin'): ?>
          <li class="nav-item">
            <a href="index.php?page=acs&action=index" class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] == 'acs') ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-satellite-dish"></i>
              <p>
                ACS Manager
                <span class="right badge badge-danger">New</span>
              </p>
            </a>
          </li>
          <?php endif; ?>-->


          <?php endif; ?>

          <?php if($_SESSION['role'] == 'admin'): ?>
          <li class="nav-header">PENGATURAN</li>
          <li class="nav-item">
            <a href="index.php?page=package&action=index" class="nav-link"><i class="nav-icon fas fa-box"></i> <p>Paket Internet</p></a>
          </li>
          <li class="nav-item">
            <a href="index.php?page=area&action=index" class="nav-link"><i class="nav-icon fas fa-map-marker-alt"></i> <p>Wilayah / Area</p></a>
          </li>
          <li class="nav-item">
            <a href="index.php?page=report&action=index" class="nav-link"><i class="nav-icon fas fa-chart-line"></i> <p>Laporan Keuangan</p></a>
          </li>
          <li class="nav-item">
            <a href="index.php?page=user&action=index" class="nav-link"><i class="nav-icon fas fa-user-cog"></i> <p>Manajemen User</p></a>
          </li>
          <li class="nav-item">
            <a href="index.php?page=setting&action=index" class="nav-link"><i class="nav-icon fas fa-cogs"></i> <p>Pengaturan Aplikasi</p></a>
          </li>
          <?php endif; ?>
          
                    <li class="nav-header">AKUN SAYA</li>
          <li class="nav-item">
            <a href="index.php?page=profile&action=index" class="nav-link">
              <i class="nav-icon fas fa-user-circle"></i>
              <p>Profil & Password</p>
            </a>
          </li>
          

        </ul>
      </nav>
    </div>
  </aside>
