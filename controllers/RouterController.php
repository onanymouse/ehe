<?php
require_once 'helpers/routeros_api.class.php';

class RouterController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // --- CRUD BIASA (TETAP SAMA) ---
    public function index() {
        if($_SESSION['role'] != 'admin') { header("Location: index.php"); exit; }
        $routers = $this->conn->query("SELECT * FROM routers ORDER BY name ASC")->fetchAll();
        require_once 'views/router/index.php';
    }
    public function create() {
        if($_SESSION['role'] != 'admin') { header("Location: index.php"); exit; }
        require_once 'views/router/create.php';
    }
    public function store() {
        if($_SESSION['role'] != 'admin') { header("Location: index.php"); exit; }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = $_POST['name']; $ip = $_POST['ip_address']; $user = $_POST['username']; $pass = $_POST['password']; $port = $_POST['port'];
            $iso_mode = isset($_POST['isolir_mode']) ? $_POST['isolir_mode'] : 'disable';
            $iso_prof = ($iso_mode == 'profile' && isset($_POST['isolir_profile'])) ? $_POST['isolir_profile'] : NULL;
            $sql = "INSERT INTO routers (name, ip_address, username, password, port, isolir_mode, isolir_profile) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $this->conn->prepare($sql)->execute([$name, $ip, $user, $pass, $port, $iso_mode, $iso_prof]);
            setFlash('success', 'Router berhasil ditambahkan'); header("Location: index.php?page=router&action=index");
        }
    }
    public function edit() {
        if($_SESSION['role'] != 'admin') { header("Location: index.php"); exit; }
        $id = $_GET['id'];
        $router = $this->conn->query("SELECT * FROM routers WHERE id='$id'")->fetch();
        require_once 'views/router/edit.php';
    }
    public function update() {
        if($_SESSION['role'] != 'admin') { header("Location: index.php"); exit; }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id']; $name = $_POST['name']; $ip = $_POST['ip_address']; $user = $_POST['username']; $pass = $_POST['password']; $port = $_POST['port'];
            $iso_mode = isset($_POST['isolir_mode']) ? $_POST['isolir_mode'] : 'disable';
            $iso_prof = ($iso_mode == 'profile' && isset($_POST['isolir_profile'])) ? $_POST['isolir_profile'] : NULL;
            $sql = "UPDATE routers SET name=?, ip_address=?, username=?, password=?, port=?, isolir_mode=?, isolir_profile=? WHERE id=?";
            $this->conn->prepare($sql)->execute([$name, $ip, $user, $pass, $port, $iso_mode, $iso_prof, $id]);
            setFlash('success', 'Router berhasil diupdate'); header("Location: index.php?page=router&action=index");
        }
    }
    public function delete() {
        if($_SESSION['role'] != 'admin') { header("Location: index.php"); exit; }
        $id = $_GET['id'];
        $this->conn->query("DELETE FROM routers WHERE id='$id'");
        setFlash('success', 'Router dihapus'); header("Location: index.php?page=router&action=index");
    }

    // --- AJAX PING ---
    public function test_connection() {
        ini_set('display_errors', 0); error_reporting(0); while (ob_get_level()) ob_end_clean(); header('Content-Type: application/json');
        try {
            $id = isset($_GET['id']) ? $_GET['id'] : 0;
            $router = $this->conn->query("SELECT * FROM routers WHERE id = '$id'")->fetch();
            if (!$router) { echo json_encode(['status'=>'error', 'message'=>'Router not found']); exit; }
            $API = new RouterosAPI(); $API->debug = false; $API->port = $router['port']; $API->timeout = 3;
            $start = microtime(true);
            if ($API->connect($router['ip_address'], $router['username'], $router['password'])) {
                $ping = round((microtime(true) - $start) * 1000, 0);
                $res = $API->comm("/system/resource/print");
                $info = isset($res[0]) ? "v" . $res[0]['version'] : 'OK';
                $API->disconnect();
                echo json_encode(['status'=>'success', 'ping'=> $ping . ' ms', 'info'=>$info]);
            } else { echo json_encode(['status'=>'error', 'message'=>'Gagal Connect']); }
        } catch (Exception $e) { echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]); } exit;
    }

    // --- AJAX LOAD PROFILE (MANUAL INPUT) ---
    public function get_profiles_api() {
        ini_set('display_errors', 0); error_reporting(0); while (ob_get_level()) ob_end_clean(); header('Content-Type: application/json');
        $ip = $_POST['ip']; $user = $_POST['user']; $pass = $_POST['pass']; $port = $_POST['port'];
        $API = new RouterosAPI(); $API->debug = false; $API->port = $port; $API->timeout = 3;
        if ($API->connect($ip, $user, $pass)) {
            $profiles = $API->comm("/ppp/profile/print"); $API->disconnect();
            $data = []; foreach($profiles as $p) { $data[] = ['name' => $p['name']]; }
            echo json_encode(['status'=>'success', 'data'=>$data]);
        } else { echo json_encode(['status'=>'error', 'message'=>'Gagal Connect']); } exit;
    }

    // --- AJAX LOAD PROFILE (BY ID DATABASE - UNTUK PELANGGAN) ---
    public function get_profiles_by_id() {
        ini_set('display_errors', 0); error_reporting(0); while (ob_get_level()) ob_end_clean(); header('Content-Type: application/json');
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $r = $this->conn->query("SELECT * FROM routers WHERE id='$id'")->fetch();
        if(!$r) { echo json_encode(['error'=>true]); exit; }
        $API = new RouterosAPI(); $API->debug = false; $API->port = $r['port']; $API->timeout = 3;
        if ($API->connect($r['ip_address'], $r['username'], $r['password'])) {
            $profiles = $API->comm("/ppp/profile/print"); $API->disconnect();
            $data = []; foreach($profiles as $p) { $data[] = ['name' => $p['name']]; }
            echo json_encode($data);
        } else { echo json_encode(['error'=>true]); } exit;
    }

    // --- AJAX LOAD SECRETS (LOGIKA LAMA DIKEMBALIKAN) ---
    // Membandingkan dengan Database Lokal (bukan active user)
    public function get_secrets() {
        ini_set('display_errors', 0); error_reporting(0); while (ob_get_level()) ob_end_clean(); header('Content-Type: application/json');

        $id = $_GET['id'];
        $r = $this->conn->query("SELECT * FROM routers WHERE id='$id'")->fetch();
        
        // 1. Ambil Data Pelanggan Lokal (Untuk Cek Siapa yang pakai)
        // Array key = pppoe_user, Value = Nama Pelanggan
        $db_users = [];
        $stmt_cust = $this->conn->query("SELECT pppoe_user, name FROM customers WHERE router_id = '$id' AND pppoe_user IS NOT NULL");
        while($row = $stmt_cust->fetch()) {
            $db_users[$row['pppoe_user']] = $row['name'];
        }

        $API = new RouterosAPI(); $API->debug = false; $API->port = $r['port']; $API->timeout = 3;
        
        if ($API->connect($r['ip_address'], $r['username'], $r['password'])) {
            $secrets = $API->comm("/ppp/secret/print");
            $API->disconnect();
            
            $data = [];
            foreach($secrets as $s) {
                // LOGIKA: Apakah user ini ada di array database?
                $is_used = isset($db_users[$s['name']]) ? $db_users[$s['name']] : false;
                
                $data[] = [
                    'name' => isset($s['name']) ? $s['name'] : '',
                    'service' => isset($s['service']) ? $s['service'] : 'any',
                    'used_by' => $is_used // Kirim Nama Pelanggan jika terpakai
                ];
            }
            echo json_encode($data);
        } else { 
            echo json_encode(['error'=>true]); 
        }
        exit;
    }
}
?>
