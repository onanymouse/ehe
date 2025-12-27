<?php
class AuthController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function login() {
        if (isset($_SESSION['user_id'])) {
            redirect('index.php?page=dashboard');
        }
        require_once 'views/auth/login.php';
    }

    public function post_login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = clean($_POST['username']);
            $password = $_POST['password'];

            $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                
                // --- STABILIZATION: SESSION SECURITY ---
                // Ganti ID Session setiap login berhasil (Wajib Security)
                session_regenerate_id(true); 
                // ---------------------------------------

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];
                
                $upd = $this->conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $upd->execute([$user['id']]);

                redirect('index.php?page=dashboard');

            } else {
                $_SESSION['error'] = "Username atau Password salah!";
                redirect('index.php?page=auth&action=login');
            }
        }
    }

    public function logout() {
        // Hapus session dengan bersih
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        redirect('index.php?page=auth&action=login');
    }
}
?>
