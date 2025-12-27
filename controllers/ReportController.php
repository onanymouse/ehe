<?php
class ReportController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function index() {
        // Default: Tampilkan data bulan ini
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

        // Query Laporan Pembayaran (Hanya yang PAID)
        $sql = "SELECT i.*, c.name as customer_name, c.customer_code, p.name as package_name 
                FROM invoices i
                JOIN customers c ON i.customer_id = c.id
                JOIN packages p ON c.package_id = p.id
                WHERE i.status = 'paid' 
                AND DATE(i.paid_at) BETWEEN ? AND ?
                ORDER BY i.paid_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$start_date, $end_date]);
        $reports = $stmt->fetchAll();

        // Hitung Total
        $total_income = 0;
        foreach($reports as $r) {
            $total_income += $r['amount'];
        }

        require_once 'views/report/index.php';
    }

    public function print() {
        // Logika sama, tapi diarahkan ke view print
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

        $sql = "SELECT i.*, c.name as customer_name 
                FROM invoices i
                JOIN customers c ON i.customer_id = c.id
                WHERE i.status = 'paid' 
                AND DATE(i.paid_at) BETWEEN ? AND ?
                ORDER BY i.paid_at ASC"; // Urutkan ASC agar enak dibaca kronologisnya
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$start_date, $end_date]);
        $reports = $stmt->fetchAll();

        $total_income = 0;
        foreach($reports as $r) $total_income += $r['amount'];

        require_once 'views/report/print.php';
    }
}
?>
