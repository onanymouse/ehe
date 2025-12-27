<?php
class AreaController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function index() {
        $stmt = $this->conn->prepare("SELECT * FROM areas ORDER BY name ASC");
        $stmt->execute();
        $areas = $stmt->fetchAll();
        require_once 'views/area/index.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = clean($_POST['name']);
            $code = clean($_POST['code']);

            try {
                $sql = "INSERT INTO areas (name, code) VALUES (?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$name, $code]);
                setFlash('success', 'Area berhasil ditambahkan');
                redirect('index.php?page=area&action=index');
            } catch (PDOException $e) {
                setFlash('error', "Error: " . $e->getMessage());
                redirect('index.php?page=area&action=index');
            }
        }
    }

    // --- FITUR BARU: EDIT (AJAX) ---
    public function edit() {
        ini_set('display_errors', 0); 
        error_reporting(0);
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $id = $_GET['id'];
        $stmt = $this->conn->prepare("SELECT * FROM areas WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        
        echo json_encode($data);
        exit;
    }

    // --- FITUR BARU: UPDATE ---
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'];
            $name = clean($_POST['name']);
            $code = clean($_POST['code']);

            try {
                $sql = "UPDATE areas SET name = ?, code = ? WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$name, $code, $id]);
                
                setFlash('success', 'Area berhasil diperbarui');
                redirect('index.php?page=area&action=index');
            } catch (PDOException $e) {
                setFlash('error', "Error: " . $e->getMessage());
                redirect('index.php?page=area&action=index');
            }
        }
    }

    public function delete() {
        $id = $_GET['id'];
        
        // Cek apakah dipakai pelanggan?
        $cek = $this->conn->prepare("SELECT id FROM customers WHERE area_id = ?");
        $cek->execute([$id]);
        
        if($cek->rowCount() > 0) {
            setFlash('error', 'Gagal hapus! Area ini sedang digunakan oleh pelanggan.');
            redirect('index.php?page=area&action=index');
            exit;
        }

        $stmt = $this->conn->prepare("DELETE FROM areas WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Area berhasil dihapus');
        redirect('index.php?page=area&action=index');
    }
}
?>
