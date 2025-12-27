<?php
require_once 'helpers/routeros_api.class.php';

class MikrotikController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function index() {
        $routers = $this->conn->query("SELECT * FROM routers ORDER BY name ASC")->fetchAll();
        require_once 'views/mikrotik/index.php';
    }

    public function monitor() {
        $id = $_GET['id'];
        $stmt = $this->conn->prepare("SELECT * FROM routers WHERE id = ?"); // Ambil semua kolom termasuk port
        $stmt->execute([$id]);
        $router = $stmt->fetch();
        require_once 'views/mikrotik/monitor.php';
    }

    public function secrets() {
        $id = $_GET['id'];
        $stmt = $this->conn->prepare("SELECT * FROM routers WHERE id = ?");
        $stmt->execute([$id]);
        $router = $stmt->fetch();
        require_once 'views/mikrotik/secrets.php';
    }

    // --- API JSON DATA (FIX PORT) ---
    
    public function get_active_data() {
        $this->prepare_json_response();
        
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $router = $this->get_router_auth($id);

        if (!$router) { 
            echo json_encode(['data' => [], 'status' => 'offline', 'error' => 'Router Not Found']); exit; 
        }

        $API = new RouterosAPI();
        $API->debug = false;
        
        // --- PERBAIKAN UTAMA: SET PORT ---
        $API->port = $router['port']; 
        // ---------------------------------
        $API->timeout = 5; 

        if ($API->connect($router['ip_address'], $router['username'], $router['password'])) {
            $active = $API->comm("/ppp/active/print");
            $API->disconnect();
            
            $data = [];
            foreach($active as $a) {
                $data[] = [
                    'id' => isset($a['.id']) ? $a['.id'] : '',
                    'name' => isset($a['name']) ? $a['name'] : '-',
                    'address' => isset($a['address']) ? $a['address'] : '-',
                    'uptime' => isset($a['uptime']) ? $a['uptime'] : '-',
                    'caller_id' => isset($a['caller-id']) ? $a['caller-id'] : '-',
                    'service' => isset($a['service']) ? $a['service'] : '-'
                ];
            }
            echo json_encode(['data' => $data, 'status' => 'online']);
        } else {
            echo json_encode(['data' => [], 'status' => 'offline', 'error' => 'Connection Time Out (Cek Port/IP)']);
        }
        exit;
    }

    public function get_secret_data() {
        $this->prepare_json_response();
        
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $router = $this->get_router_auth($id);

        if (!$router) { 
            echo json_encode(['data' => [], 'status' => 'offline']); exit; 
        }

        $API = new RouterosAPI();
        $API->debug = false;
        
        // --- PERBAIKAN UTAMA: SET PORT ---
        $API->port = $router['port'];
        // ---------------------------------
        $API->timeout = 10; 

        if ($API->connect($router['ip_address'], $router['username'], $router['password'])) {
            $secrets = $API->comm("/ppp/secret/print");
            $API->disconnect();
            
            $data = [];
            foreach($secrets as $s) {
                $isDisabled = (isset($s['disabled']) && $s['disabled'] == 'true');
                $data[] = [
                    'id' => isset($s['.id']) ? $s['.id'] : '',
                    'name' => isset($s['name']) ? $s['name'] : '-',
                    'password' => isset($s['password']) ? $s['password'] : '****',
                    'profile' => isset($s['profile']) ? $s['profile'] : 'default',
                    'service' => isset($s['service']) ? $s['service'] : 'any',
                    'last_logout' => isset($s['last-logged-out']) ? $s['last-logged-out'] : '-',
                    'disabled' => $isDisabled
                ];
            }
            echo json_encode(['data' => $data, 'status' => 'online']);
        } else {
            echo json_encode(['data' => [], 'status' => 'offline']);
        }
        exit;
    }

    private function prepare_json_response() {
        session_write_close();
        ini_set('display_errors', 0); error_reporting(0);
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json');
    }

    private function get_router_auth($id) {
        $stmt = $this->conn->prepare("SELECT * FROM routers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function kick() { $this->action_helper('remove', '/ppp/active/remove', 'index.php?page=mikrotik&action=monitor&id='.$_GET['id_router']); }
    public function enable_secret() { $this->action_helper('enable', '/ppp/secret/enable', 'index.php?page=mikrotik&action=secrets&id='.$_GET['id_router']); }
    public function disable_secret() { $this->action_helper('disable', '/ppp/secret/disable', 'index.php?page=mikrotik&action=secrets&id='.$_GET['id_router']); }

    private function action_helper($action_name, $command, $redirect_url) {
        session_write_close(); 
        $id_router = $_GET['id_router'];
        $id_item = isset($_GET['id_session']) ? $_GET['id_session'] : $_GET['id_secret'];
        
        $router = $this->get_router_auth($id_router);
        if ($router) {
            $API = new RouterosAPI(); 
            // --- FIX PORT DI SINI JUGA ---
            $API->port = $router['port']; 
            // -----------------------------
            $API->timeout = 5;
            
            if ($API->connect($router['ip_address'], $router['username'], $router['password'])) {
                $API->comm($command, [".id" => $id_item]);
                $API->disconnect();
                session_start(); setFlash('success', 'Aksi berhasil!');
            } else {
                session_start(); setFlash('error', 'Gagal koneksi Router');
            }
        }
        echo "<script>window.location.href='$redirect_url';</script>";
        exit;
    }
}
?>
