<?php
// Fungsi Redirect Cepat
function redirect($url) {
    // Pastikan path benar
    header("Location: " . BASE_URL . $url);
    exit;
}

// Fungsi Bersihkan Input
function clean($data) {
    if(empty($data)) return '';
    return htmlspecialchars(stripslashes(trim($data)));
}

// Fungsi Format Rupiah
function format_rupiah($angka){
    return "Rp " . number_format($angka,0,',','.');
}

// Fungsi Cek Login & Role
function checkAuth($allowed_roles = []) {
    if (!isset($_SESSION['user_id'])) {
        redirect('index.php?page=auth&action=login');
    }
    
    if (!empty($allowed_roles)) {
        if (!in_array($_SESSION['role'], $allowed_roles)) {
            echo "<h1>AKSES DITOLAK!</h1><p>Anda tidak memiliki izin mengakses halaman ini.</p>";
            exit;
        }
    }
}

// --- FUNGSI AMBIL SETTING DARI DB ---
function getSetting($key) {
    global $db; // Kita butuh akses database global di sini
    // Karena logic koneksi kita OOP, kita buat instance baru khusus helper
    // Atau cara lebih bersih: panggil ini hanya di view yang sudah punya akses DB.
    // TAPI, agar simpel, kita lakukan trik koneksi cepat:
    
    static $conn_setting;
    if(!$conn_setting) {
        require_once __DIR__ . '/../config/database.php';
        $db_class = new Database();
        $conn_setting = $db_class->getConnection();
    }
    
    $stmt = $conn_setting->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $res = $stmt->fetch();
    return $res ? $res['setting_value'] : '';
}


// --- FITUR BARU: FLASH MESSAGE (NOTIFIKASI) ---
function setFlash($type, $message) {
    // Type: success, error, warning, info
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}
?>
