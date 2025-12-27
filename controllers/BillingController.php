<?php
require_once 'helpers/routeros_api.class.php';

class BillingController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // --- HALAMAN TAGIHAN (UNPAID) ---
    public function index() {
        $user_id = $_SESSION['user_id'];
        $role = $_SESSION['role'];

        $sql = "SELECT i.*, c.name as customer_name, c.customer_code, c.due_date, c.collector_id, 
                p.name as package_name, u.fullname as collector_name 
                FROM invoices i
                JOIN customers c ON i.customer_id = c.id
                JOIN packages p ON c.package_id = p.id
                LEFT JOIN users u ON c.collector_id = u.id
                WHERE i.status = 'unpaid'";

        // FILTER KOLEKTOR
        if ($role == 'kolektor') {
            $sql .= " AND c.collector_id = $user_id";
        }
        
        $sql .= " ORDER BY i.created_at DESC";
        $invoices = $this->conn->query($sql)->fetchAll();
        require_once 'views/billing/index.php';
    }

    // --- FUNGSI BAYAR (FINAL: SMART PROFILE RESTORE) ---
    public function pay() {
        ini_set('display_errors', 0); 
        error_reporting(E_ALL);

        $id = $_GET['id']; 
        $admin_id = $_SESSION['user_id']; 

        // 1. AMBIL DATA LENGKAP (INVOICE + CUSTOMER + PACKAGES)
        // Kita JOIN ke tabel packages (p) untuk ambil 'mikrotik_profile'
        $sql = "SELECT i.*, 
                       c.id as cust_id, c.is_mikrotik, c.router_id, c.pppoe_user, 
                       p.mikrotik_profile as pkg_profile
                FROM invoices i 
                JOIN customers c ON i.customer_id = c.id 
                JOIN packages p ON c.package_id = p.id
                WHERE i.id = ?";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $inv = $stmt->fetch();

        if (!$inv) {
            echo "<script>alert('Data Invoice Tidak Ditemukan!'); window.location='index.php?page=billing&action=index';</script>";
            exit;
        }

        // 2. TRANSAKSI DATABASE
        $this->conn->beginTransaction();

        try {
            // A. Update Invoice (LUNAS)
            $sql_inv = "UPDATE invoices SET status = 'paid', paid_at = NOW(), paid_by_user_id = ? WHERE id = ?";
            $this->conn->prepare($sql_inv)->execute([$admin_id, $id]);

            // B. Update Customer (ACTIVE)
            $this->conn->prepare("UPDATE customers SET status = 'active' WHERE id = ?")->execute([$inv['cust_id']]);

            // C. MIKROTIK (RESTORE PROFILE)
            if ($inv['is_mikrotik'] == 1 && !empty($inv['router_id'])) {
                $r = $this->conn->query("SELECT * FROM routers WHERE id = " . $inv['router_id'])->fetch();
                if ($r) {
                    $API = new RouterosAPI();
                    $API->debug = false; 
                    $API->port = $r['port'];

                    if ($API->connect($r['ip_address'], $r['username'], $r['password'])) {
                        $user = $inv['pppoe_user'];
                        $sec = $API->comm("/ppp/secret/print", ["?name" => $user]);
                        
                        if (count($sec) > 0) {
                            $sid = $sec[0]['.id'];
                            
                            // TENTUKAN PROFILE TUJUAN
                            // Ambil dari paket. Jika kosong, baru ke default.
                            $target_profile = !empty($inv['pkg_profile']) ? $inv['pkg_profile'] : 'default';
                            
                            // Cek Mode Isolir Router
                            if (isset($r['isolir_mode']) && $r['isolir_mode'] == 'profile') {
                                // Jika mode ganti profile, kita kembalikan ke profile paket
                                $API->comm("/ppp/secret/set", [
                                    ".id" => $sid, 
                                    "profile" => $target_profile, // <--- INI SUDAH SESUAI PAKET
                                    "disabled" => "no",
                                    "comment" => "LUNAS - " . date('d/m/Y')
                                ]);
                            } else {
                                // Jika mode disable, kita enable.
                                // Sambil memastikan profilenya benar (jaga-jaga kalau pernah diubah manual)
                                $API->comm("/ppp/secret/enable", [".id" => $sid]);
                                $API->comm("/ppp/secret/set", [
                                    ".id" => $sid,
                                    "profile" => $target_profile, // Sekalian set profile biar rapi
                                    "comment" => "LUNAS - " . date('d/m/Y')
                                ]);
                            }

                            // KICK USER (Agar reconnect dengan speed baru)
                            $act = $API->comm("/ppp/active/print", ["?name" => $user]);
                            if (count($act) > 0) {
                                $API->comm("/ppp/active/remove", [".id" => $act[0]['.id']]);
                            }
                        }
                        $API->disconnect();
                    }
                }
            }

            $this->conn->commit();
            setFlash('success', 'Pembayaran Sukses! Layanan dipulihkan.');

        } catch (Exception $e) {
            $this->conn->rollBack();
            echo "<script>alert('GAGAL: " . addslashes($e->getMessage()) . "'); window.location='index.php?page=billing&action=index';</script>";
            exit;
        }

        echo "<script>window.location='index.php?page=billing&action=index';</script>";
        exit;
    }
    
    // --- FITUR BARU: HAPUS MASAL (BULK DELETE) ---
    public function bulk_delete() {
        if($_SESSION['role'] != 'admin') { header("Location: index.php"); exit; }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ids'])) {
            $ids = $_POST['ids']; // Array ID
            
            if (count($ids) > 0) {
                // Ubah array jadi string koma: 1,2,5,8
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                
                // Hapus hanya yang statusnya 'unpaid' (Safety)
                $sql = "DELETE FROM invoices WHERE id IN ($placeholders) AND status = 'unpaid'";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute($ids);
                
                setFlash('success', count($ids) . ' Tagihan sampah berhasil dibersihkan.');
            }
        }
        
        // Redirect kembali
        header("Location: index.php?page=billing&action=index");
        exit;
    }
  // --- FITUR ISOLIR MANUAL (VERSI ALERT POPUP / ANTI-SKIP) ---
    public function isolate_manual() {
        // Matikan error display agar alert JS tidak rusak
        ini_set('display_errors', 0); 
        error_reporting(E_ALL);
        
        $inv_id = $_GET['id'];

        // 1. Ambil Data
        $sql = "SELECT i.*, c.id as cust_id, c.is_mikrotik, c.router_id, c.pppoe_user, c.name,
                r.ip_address, r.username, r.password, r.port, r.isolir_mode, r.isolir_profile
                FROM invoices i
                JOIN customers c ON i.customer_id = c.id
                LEFT JOIN routers r ON c.router_id = r.id
                WHERE i.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$inv_id]);
        $data = $stmt->fetch();

        if(!$data) {
            echo "<script>alert('Data Invoice/Pelanggan tidak ditemukan!'); window.location='index.php?page=billing&action=index';</script>";
            exit;
        }

        // 2. Eksekusi Mikrotik
        $detail_status = "Status Database: ISOLATED (Sukses).";
        
        if ($data['is_mikrotik'] == 1 && !empty($data['router_id'])) {
            $API = new RouterosAPI();
            $API->debug = false; 
            $API->port = $data['port'];
            
            if ($API->connect($data['ip_address'], $data['username'], $data['password'])) {
                $user = $data['pppoe_user'];
                $sec = $API->comm("/ppp/secret/print", ["?name" => $user]);
                
                if (count($sec) > 0) {
                    $sid = $sec[0]['.id'];
                    $mode = isset($data['isolir_mode']) ? $data['isolir_mode'] : 'disable';
                    $prof = isset($data['isolir_profile']) ? $data['isolir_profile'] : 'default';

                    if ($mode == 'profile' && !empty($prof)) {
                        // MODE PROFILE
                        $API->comm("/ppp/secret/set", [".id"=>$sid, "profile"=>$prof, "comment"=>"ISOLIR MANUAL - ".date('d/m')]);
                        $detail_status .= "\nMikrotik: Profile diubah ke '$prof'.";
                    } else {
                        // MODE DISABLE
                        $API->comm("/ppp/secret/disable", [".id"=>$sid]);
                        $detail_status .= "\nMikrotik: Secret didisable.";
                    }
                    
                    // KICK USER
                    $act = $API->comm("/ppp/active/print", ["?name" => $user]);
                    if(count($act)>0) {
                        $API->comm("/ppp/active/remove", [".id"=>$act[0]['.id']]);
                        $detail_status .= "\nSesi Aktif: User ditendang (Kick).";
                    } else {
                        $detail_status .= "\nSesi Aktif: User sedang offline.";
                    }
                    
                } else {
                    $detail_status .= "\n⚠️ PERINGATAN: User PPPoE '$user' TIDAK DITEMUKAN di Mikrotik. Cek ejaan nama user.";
                }
                $API->disconnect();
            } else {
                echo "<script>alert('GAGAL KONEK ROUTER! Cek IP/User/Pass Router.'); window.location='index.php?page=billing&action=index';</script>";
                exit;
            }
        }

        // 3. Update Status DB
        $this->conn->prepare("UPDATE customers SET status = 'isolated' WHERE id = ?")->execute([$data['cust_id']]);
        
        // 4. TAMPILKAN ALERT (MUNCUL POPUP KLASIK)
        // Kita pakai json_encode agar teks yang ada enter-nya (\n) tetap aman di Javascript
        $pesan_final = "EKSEKUSI BERHASIL!\\n-------------------\\n" . $detail_status;
        
        echo "<script>
            alert(`$pesan_final`); 
            window.location='index.php?page=billing&action=index';
        </script>";
        exit;
    }
    
    // --- RIWAYAT ---
    public function history() {
        $user_id = $_SESSION['user_id'];
        $role = $_SESSION['role'];

         $sql = "SELECT i.*, c.name as customer_name, c.customer_code, u.fullname as cashier_name
                FROM invoices i
                JOIN customers c ON i.customer_id = c.id
                LEFT JOIN users u ON i.paid_by_user_id = u.id
                WHERE i.status = 'paid'";
        
        if($role == 'kolektor') {
            $sql .= " AND i.paid_by_user_id = $user_id";
        }

        $sql .= " ORDER BY i.paid_at DESC LIMIT 100";
        $invoices = $this->conn->query($sql)->fetchAll();
        require_once 'views/billing/history.php';
    }
    
    // --- PRINT ---
   // --- FUNGSI CETAK STRUK ---
    public function print() {
        // 1. Validasi ID
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            die("Error: ID Invoice tidak ditemukan di URL.");
        }
        
        $id = $_GET['id'];

        // 2. Query yang Aman (Pakai LEFT JOIN semua biar data tidak hilang)
        $sql = "SELECT i.*, 
                       c.name as customer_name, 
                       c.address, 
                       c.customer_code, 
                       p.name as package_name,
                       u.fullname as admin_name
                FROM invoices i 
                LEFT JOIN customers c ON i.customer_id = c.id 
                LEFT JOIN packages p ON c.package_id = p.id
                LEFT JOIN users u ON i.paid_by_user_id = u.id
                WHERE i.id = ?";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $inv = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 3. Cek Hasil
        if (!$inv) {
            die("<h3>Data Invoice ID $id Tidak Ditemukan di Database.</h3>");
        }
        
        // 4. Load View Struk
        require_once 'views/billing/print.php';
    }
}
?>