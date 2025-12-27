<?php
class ProfileController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function index() {
        $id = $_SESSION['user_id'];
        
        // Ambil data user yang sedang login
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        require_once 'views/profile/index.php';
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_SESSION['user_id'];
            $fullname = clean($_POST['fullname']);
            $password = $_POST['password'];

            try {
                // Cek apakah user mau ganti password?
                if (!empty($password)) {
                    // Jika diisi, enkripsi password baru
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $sql = "UPDATE users SET fullname = ?, password = ? WHERE id = ?";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([$fullname, $hash, $id]);
                } else {
                    // Jika kosong, update nama saja
                    $sql = "UPDATE users SET fullname = ? WHERE id = ?";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([$fullname, $id]);
                }

                // Update Session agar nama di pojok kiri atas langsung berubah
                $_SESSION['fullname'] = $fullname;

                setFlash('success', 'Profil berhasil diperbarui!');
                redirect('index.php?page=profile&action=index');

            } catch (PDOException $e) {
                setFlash('error', 'Gagal update profil: ' . $e->getMessage());
                redirect('index.php?page=profile&action=index');
            }
        }
    }
}
?>
