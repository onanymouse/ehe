<?php
class PackageController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function index() {
        if($_SESSION['role'] != 'admin') { header("Location: index.php"); exit; }
        
        // Load Data Paket
        $packages = $this->conn->query("SELECT * FROM packages ORDER BY price ASC")->fetchAll();
        
        // Load Data Router (Untuk fitur scan profile di modal)
        $routers = $this->conn->query("SELECT * FROM routers ORDER BY name ASC")->fetchAll();
        
        require_once 'views/package/index.php';
    }

    // --- API EDIT (JSON) ---
    public function edit() {
        ini_set('display_errors', 0); error_reporting(0); while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        $id = $_GET['id'];
        $package = $this->conn->query("SELECT * FROM packages WHERE id='$id'")->fetch(PDO::FETCH_ASSOC);
        echo json_encode($package);
        exit;
    }

    // --- API STORE (JSON) ---
    public function store() {
        ini_set('display_errors', 0); error_reporting(0); while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        if($_SESSION['role'] != 'admin') { echo json_encode(['status'=>'error', 'message'=>'Akses Ditolak']); exit; }

        try {
            $name = clean($_POST['name']);
            $price = clean(str_replace('.', '', $_POST['price']));
            $desc = clean($_POST['description']);
            $profile = !empty($_POST['mikrotik_profile']) ? clean($_POST['mikrotik_profile']) : 'default';

            $sql = "INSERT INTO packages (name, description, price, mikrotik_profile) VALUES (?, ?, ?, ?)";
            $this->conn->prepare($sql)->execute([$name, $desc, $price, $profile]);

            echo json_encode(['status'=>'success']);
        } catch (Exception $e) {
            echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
        }
        exit;
    }

    // --- API UPDATE (JSON) ---
    public function update() {
        ini_set('display_errors', 0); error_reporting(0); while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        if($_SESSION['role'] != 'admin') { echo json_encode(['status'=>'error', 'message'=>'Akses Ditolak']); exit; }

        try {
            $id = $_POST['id'];
            $name = clean($_POST['name']);
            $price = clean(str_replace('.', '', $_POST['price']));
            $desc = clean($_POST['description']);
            $profile = !empty($_POST['mikrotik_profile']) ? clean($_POST['mikrotik_profile']) : 'default';

            $sql = "UPDATE packages SET name=?, description=?, price=?, mikrotik_profile=? WHERE id=?";
            $this->conn->prepare($sql)->execute([$name, $desc, $price, $profile, $id]);

            echo json_encode(['status'=>'success']);
        } catch (Exception $e) {
            echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
        }
        exit;
    }

    // --- DELETE ---
    public function delete() {
        if($_SESSION['role'] != 'admin') { header("Location: index.php"); exit; }
        $id = $_GET['id'];
        $cek = $this->conn->query("SELECT COUNT(*) FROM customers WHERE package_id='$id'")->fetchColumn();
        if($cek > 0) {
            setFlash('error', 'Gagal: Paket sedang dipakai pelanggan.');
        } else {
            $this->conn->query("DELETE FROM packages WHERE id='$id'");
            setFlash('success', 'Paket dihapus');
        }
        redirect('index.php?page=package&action=index');
    }
}
?>
