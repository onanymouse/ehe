<?php
class UserController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function index() {
        // Ambil semua user kecuali passwordnya
        $stmt = $this->conn->prepare("SELECT id, username, fullname, role, is_active, last_login FROM users ORDER BY id DESC");
        $stmt->execute();
        $users = $stmt->fetchAll();
        require_once 'views/user/index.php';
    }

    public function create() {
        require_once 'views/user/create.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = clean($_POST['username']);
            $fullname = clean($_POST['fullname']);
            $password = $_POST['password'];
            $role = clean($_POST['role']);

            // Cek username duplikat
            $cek = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
            $cek->execute([$username]);
            if($cek->rowCount() > 0) {
                setFlash('error', 'Username sudah dipakai!');
                echo "<script>window.history.back();</script>"; exit;
            }

            // Hash Password
            $hash = password_hash($password, PASSWORD_BCRYPT);

            try {
                $sql = "INSERT INTO users (username, password, fullname, role, is_active) VALUES (?, ?, ?, ?, 1)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$username, $hash, $fullname, $role]);
                
                setFlash('success', 'User baru berhasil dibuat.');
                redirect('index.php?page=user&action=index');
            } catch (PDOException $e) {
                setFlash('error', 'Error: ' . $e->getMessage());
                redirect('index.php?page=user&action=index');
            }
        }
    }

    public function edit() {
        $id = $_GET['id'];
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if(!$user) {
            setFlash('error', 'User tidak ditemukan');
            redirect('index.php?page=user&action=index');
        }
        require_once 'views/user/edit.php';
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'];
            $fullname = clean($_POST['fullname']);
            $role = clean($_POST['role']);
            $password = $_POST['password'];
            
            // Logic Password: Jika diisi, ganti hash baru. Jika kosong, biarkan yang lama.
            $pass_sql = "";
            $params = [$fullname, $role];

            if(!empty($password)) {
                $pass_sql = ", password = ?";
                $params[] = password_hash($password, PASSWORD_BCRYPT);
            }
            
            $params[] = $id; // ID untuk WHERE

            try {
                $sql = "UPDATE users SET fullname=?, role=? $pass_sql WHERE id=?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute($params);
                
                setFlash('success', 'Data user berhasil diupdate.');
                redirect('index.php?page=user&action=index');
            } catch (PDOException $e) {
                setFlash('error', 'Gagal update: ' . $e->getMessage());
                redirect('index.php?page=user&action=index');
            }
        }
    }

    public function delete() {
        $id = $_GET['id'];
        // Cegah hapus diri sendiri
        if($id == $_SESSION['user_id']) {
            setFlash('error', 'Anda tidak bisa menghapus akun sendiri!');
            redirect('index.php?page=user&action=index');
            exit;
        }

        try {
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'User berhasil dihapus.');
        } catch(PDOException $e) {
            setFlash('error', 'Gagal hapus (Mungkin user ini terikat data pelanggan): ' . $e->getMessage());
        }
        redirect('index.php?page=user&action=index');
    }
}
?>
