<?php
class SettingController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function index() {
        // Ambil semua setting dan jadikan array asosiatif biar mudah dipanggil
        $stmt = $this->conn->query("SELECT * FROM settings");
        $raw_data = $stmt->fetchAll();
        
        $settings = [];
        foreach($raw_data as $d) {
            $settings[$d['setting_key']] = $d['setting_value'];
        }

        require_once 'views/setting/index.php';
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Loop semua input post dan update ke database
            foreach($_POST as $key => $value) {
                // Jangan update tombol submit
                if($key == 'submit') continue;
                
                // Update atau Insert jika belum ada (UPSERT Logic sederhana)
                // Kita pakai UPDATE saja karena asumsi data awal sudah diinsert via SQL
                $stmt = $this->conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([clean($value), $key]);
            }
            
            setFlash('success', 'Pengaturan berhasil disimpan!');
            redirect('index.php?page=setting&action=index');
        }
    }
}
?>
