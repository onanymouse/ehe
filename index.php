<?php
// --- MODE DEBUG (NYALAKAN UNTUK LIHAT ERROR) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 
// -------------------------------------

session_start();

// 1. Load semua konfigurasi
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'helpers/functions.php';

// 2. Tentukan Halaman yang dituju
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

// 3. Cek Status Login (Kecuali sedang di halaman Auth)
if (!isset($_SESSION['user_id']) && $page != 'auth') {
    header("Location: " . BASE_URL . "index.php?page=auth&action=login");
    exit;
}

// 4. Arahkan ke Controller yang sesuai
switch ($page) {
    case 'auth':
        require_once 'controllers/AuthController.php';
        $controller = new AuthController();
        if ($action == 'login') $controller->login();
        elseif ($action == 'post_login') $controller->post_login();
        elseif ($action == 'logout') $controller->logout();
        break;

     case 'dashboard':
        checkAuth();
        require_once 'controllers/DashboardController.php';
        $dash = new DashboardController();
        $dash->index();
        break;

     case 'router':
        checkAuth(['admin']);
        require_once 'controllers/RouterController.php';
        $router = new RouterController();
        
        if ($action == 'index') $router->index();
        elseif ($action == 'create') $router->create();
        elseif ($action == 'store') $router->store();
        elseif ($action == 'edit') $router->edit();
        elseif ($action == 'update') $router->update();
        elseif ($action == 'delete') $router->delete();
        
        // ROUTE UNTUK FORM ROUTER (Test Connection & Load Profile Manual)
        elseif ($action == 'test_connection') $router->test_connection();
        elseif ($action == 'get_profiles_api') $router->get_profiles_api();
        
        // ROUTE UNTUK FORM PELANGGAN (Load Data By ID)
        elseif ($action == 'get_secrets') $router->get_secrets();
        
        // Perhatikan ini: Request 'get_profiles' diarahkan ke 'get_profiles_by_id'
        elseif ($action == 'get_profiles') $router->get_profiles_by_id(); 
        break;

    // --- MODULE ACS MANAGER (GENIEACS) ---
    case 'acs':
        checkAuth(['admin']); // Hanya Admin
        require_once 'controllers/AcsController.php';
        $acs = new AcsController();
        
        if ($action == 'index') $acs->index();
        elseif ($action == 'search') $acs->search();
        elseif ($action == 'detail') $acs->detail();
        elseif ($action == 'reboot') $acs->reboot();
        elseif ($action == 'ajax_list') $acs->ajax_list(); 
        elseif ($action == 'manage_servers') $acs->manage_servers();
        break;

     // --- MODULE ZTE PRO (OLD / LIVE MODE) ---
    case 'zte_pro':
        checkAuth(['admin']); 
        require_once 'controllers/ZteProController.php';
        $zte = new ZteProController();
        
        if ($action == 'index') $zte->index();
        elseif ($action == 'scan_uncfg') $zte->scan_uncfg();
        elseif ($action == 'get_pon_details') $zte->get_pon_details();
        elseif ($action == 'get_routers') $zte->get_routers();
        elseif ($action == 'get_profiles') $zte->get_profiles();
        elseif ($action == 'register') $zte->register();
        elseif ($action == 'get_active_onus') $zte->get_active_onus();
        elseif ($action == 'reboot_onu') $zte->reboot_onu();
        elseif ($action == 'get_pon_interfaces') $zte->get_pon_interfaces();
        elseif ($action == 'push_tr069') $zte->push_tr069();
        elseif ($action == 'check_signal') $zte->check_signal();
        elseif ($action == 'reset_onu') $zte->reset_onu();
            // ...
    elseif ($action == 'get_db_pons') $omp->get_db_pons(); // Tambahan wajib
    // ...
    
        break;

    // --- MODULE OLT MANAGER PRO (NEW - SYNC DATABASE) ---
    case 'olt_manager_pro':
        checkAuth(['admin', 'teknisi']); 
        require_once 'controllers/OltManagerProController.php';
        $omp = new OltManagerProController();
        
        if ($action == 'index') $omp->index();
        // Dashboard Stats
        elseif ($action == 'get_stats') $omp->get_stats();
        // DataTables
        elseif ($action == 'get_table_data') $omp->get_table_data();
        // Core Logic
        elseif ($action == 'sync_data') $omp->sync_data();
        elseif ($action == 'get_pon_interfaces') $omp->get_pon_interfaces();
        // Actions
        elseif ($action == 'reboot_onu') $omp->reboot_onu();
        elseif ($action == 'reset_onu') $omp->reset_onu();
        elseif ($action == 'delete_onu') $omp->delete_onu();
        elseif ($action == 'push_tr069') $omp->push_tr069();
        break;
    
    // --- MODULE PAKET ---
    case 'package':
        checkAuth(['admin']); // Hanya Admin
        require_once 'controllers/PackageController.php';
        $pkg = new PackageController();
        if ($action == 'index') $pkg->index();
        elseif ($action == 'store') $pkg->store();
        elseif ($action == 'delete') $pkg->delete();
        elseif ($action == 'edit') $pkg->edit();    
        elseif ($action == 'update') $pkg->update(); 
        break;

    // --- MODULE AREA ---
    case 'area':
        checkAuth(['admin']); // Hanya Admin
        require_once 'controllers/AreaController.php';
        $area = new AreaController();
        if ($action == 'index') $area->index();
        elseif ($action == 'store') $area->store();
        elseif ($action == 'delete') $area->delete();
        elseif ($action == 'edit') $area->edit();
        elseif ($action == 'update') $area->update();
        break;
        
    // --- MODULE PELANGGAN ---
    case 'customer':
        checkAuth();
        require_once 'controllers/CustomerController.php';
        $cust = new CustomerController();
        
        if ($action == 'index') $cust->index();
        elseif ($action == 'create') $cust->create();
        elseif ($action == 'store') $cust->store();
        elseif ($action == 'delete') $cust->delete();
        elseif ($action == 'edit') $cust->edit();
        elseif ($action == 'update') $cust->update();
        // --- AJAX ACTIONS ---
        elseif ($action == 'check_status') $cust->check_status();
        elseif ($action == 'detail') $cust->detail();
        elseif ($action == 'traffic_api') $cust->traffic_api();
        elseif ($action == 'get_data_ajax') $cust->get_data_ajax();
        elseif ($action == 'get_onu_list') $cust->get_onu_list();
        break;

    // --- MODULE BILLING (TAGIHAN) ---
    case 'billing':
        checkAuth(['admin', 'keuangan', 'kolektor']); 
        require_once 'controllers/BillingController.php';
        $bill = new BillingController();
        
        if ($action == 'index') $bill->index();
        elseif ($action == 'generate') $bill->generate();
        elseif ($action == 'pay') $bill->pay();
        elseif ($action == 'print') $bill->print(); 
        elseif ($action == 'history') $bill->history();
        elseif ($action == 'isolate_manual') $bill->isolate_manual();
        elseif ($action == 'bulk_delete') $bill->bulk_delete();
        break;

    // --- MODULE MANAJEMEN USER ---
    case 'user':
        checkAuth(['admin']); // Hanya Admin yg boleh akses
        require_once 'controllers/UserController.php';
        $usr = new UserController();
        if ($action == 'index') $usr->index();
        elseif ($action == 'create') $usr->create();
        elseif ($action == 'store') $usr->store();
        elseif ($action == 'edit') $usr->edit();
        elseif ($action == 'update') $usr->update();
        elseif ($action == 'delete') $usr->delete();
        break;
        
    // --- MODULE LAPORAN ---
    case 'report':
        checkAuth(['admin', 'keuangan']);
        require_once 'controllers/ReportController.php';
        $rpt = new ReportController();
        if ($action == 'index') $rpt->index();
        elseif ($action == 'print') $rpt->print();
        break;

    // --- MODULE SETTING ---
    case 'setting':
        checkAuth(['admin']);
        require_once 'controllers/SettingController.php';
        $set = new SettingController();
        if ($action == 'index') $set->index();
        elseif ($action == 'update') $set->update();
        break;

    // --- MODULE MONITORING MIKROTIK ---
    case 'mikrotik':
        checkAuth(['admin', 'teknisi']);
        require_once 'controllers/MikrotikController.php';
        $mik = new MikrotikController();
        
        if ($action == 'index') $mik->index();
        elseif ($action == 'monitor') $mik->monitor();
        elseif ($action == 'secrets') $mik->secrets();
        elseif ($action == 'kick') $mik->kick();
        elseif ($action == 'enable_secret') $mik->enable_secret();
        elseif ($action == 'disable_secret') $mik->disable_secret();
        // --- TAMBAHAN BARU (AJAX API) ---
        elseif ($action == 'get_active_data') $mik->get_active_data();
        elseif ($action == 'get_secret_data') $mik->get_secret_data();
        elseif ($action == 'check_router_status') $mik->check_router_status();
        break;

    // --- MODULE OLT (BASIC) ---
    case 'olt':
        checkAuth(['admin', 'teknisi']);
        require_once 'controllers/OltController.php';
        $olt = new OltController();
        
        if ($action == 'index') $olt->index();
        elseif ($action == 'create') $olt->create();
        elseif ($action == 'store') $olt->store();
        elseif ($action == 'delete') $olt->delete();
        // FITUR UTAMA OLT
        elseif ($action == 'detail') $olt->detail(); 
        elseif ($action == 'sync') $olt->sync();     
        // AJAX ACTIONS
        elseif ($action == 'detail_onu') $olt->detail_onu();
        elseif ($action == 'reboot_onu') $olt->reboot_onu();
        elseif ($action == 'get_onu_ajax') $olt->get_onu_ajax();
        elseif ($action == 'reset_onu') $olt->reset_onu();
        elseif ($action == 'delete_onu') $olt->delete_onu();
        break; 

    // --- MODULE PROFIL (SEMUA USER) ---
    case 'profile':
        checkAuth(); // Semua yang login boleh akses
        require_once 'controllers/ProfileController.php';
        $prof = new ProfileController();
        if ($action == 'index') $prof->index();
        elseif ($action == 'update') $prof->update();
        break;
        
    default:
        echo "<h1>404 Halaman Tidak Ditemukan</h1>";
        break;
}
?>
