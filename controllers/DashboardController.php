<?php
class DashboardController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function index() {
        $role = $_SESSION['role'];
        $my_id = $_SESSION['user_id'];

        // --- 1. STATISTIK UTAMA (SAMA SEPERTI SEBELUMNYA) ---
        $sql_cust = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                        SUM(CASE WHEN status = 'nonactive' THEN 1 ELSE 0 END) as nonactive,
                        SUM(CASE WHEN status = 'isolated' THEN 1 ELSE 0 END) as isolated
                     FROM customers WHERE 1=1";
        
        if($role == 'kolektor') { $sql_cust .= " AND collector_id = '$my_id'"; }
        $cust_stat = $this->conn->query($sql_cust)->fetch();

        // --- 2. TAGIHAN UNPAID ---
        $sql_unpaid = "SELECT COUNT(i.id) as total_inv, SUM(i.amount) as total_money 
                       FROM invoices i JOIN customers c ON i.customer_id = c.id 
                       WHERE i.status = 'unpaid'";
        if($role == 'kolektor') { $sql_unpaid .= " AND c.collector_id = '$my_id'"; }
        $unpaid = $this->conn->query($sql_unpaid)->fetch();

        // --- 3. TOTAL ROUTER & OLT (KHUSUS ADMIN/TEKNISI) ---
        $router_count = 0; $olt_count = 0;
        if($role == 'admin' || $role == 'teknisi') {
            $router_count = $this->conn->query("SELECT COUNT(*) FROM routers")->fetchColumn();
            $olt_count = $this->conn->query("SELECT COUNT(*) FROM olts")->fetchColumn();
        }

        // --- 4. DATA CHART (GRAFIK) ---
        // Inisialisasi array 12 bulan (Jan-Des) dengan nilai 0
        $chart_income = array_fill(1, 12, 0);
        $chart_customer = array_fill(1, 12, 0);
        $year = date('Y');

        // A. Grafik Pemasukan (HANYA ADMIN & KEUANGAN)
        if($role == 'admin' || $role == 'keuangan') {
            $sql_chart_money = "SELECT MONTH(paid_at) as bulan, SUM(amount) as total 
                                FROM invoices 
                                WHERE status='paid' AND YEAR(paid_at) = '$year' 
                                GROUP BY MONTH(paid_at)";
            $res_money = $this->conn->query($sql_chart_money)->fetchAll();
            foreach($res_money as $row) {
                $chart_income[$row['bulan']] = $row['total'];
            }
        }

        // B. Grafik Pertumbuhan Pelanggan (ADMIN & TEKNISI)
        if($role == 'admin' || $role == 'teknisi') {
            $sql_chart_cust = "SELECT MONTH(created_at) as bulan, COUNT(*) as total 
                               FROM customers 
                               WHERE YEAR(created_at) = '$year' 
                               GROUP BY MONTH(created_at)";
            $res_cust = $this->conn->query($sql_chart_cust)->fetchAll();
            foreach($res_cust as $row) {
                $chart_customer[$row['bulan']] = $row['total'];
            }
        }

        // --- 5. RIWAYAT TERAKHIR ---
        $sql_hist = "SELECT i.*, c.name FROM invoices i JOIN customers c ON i.customer_id = c.id WHERE i.status = 'paid'";
        if($role == 'kolektor') { $sql_hist .= " AND i.paid_by_user_id = '$my_id'"; }
        $sql_hist .= " ORDER BY i.paid_at DESC LIMIT 5";
        $history = $this->conn->query($sql_hist)->fetchAll();

        // Konversi data chart ke format String agar bisa dibaca JS (Contoh: "100, 200, 500...")
        $data_income_str = implode(',', array_values($chart_income));
        $data_cust_str = implode(',', array_values($chart_customer));

        require_once 'views/dashboard/index.php';
    }
}
?>
