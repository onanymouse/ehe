<?php
// Script ini dijalankan via CLI/Cronjob, jadi kita harus definisikan path manual
// Sesuaikan path ini dengan struktur folder hostingmu jika error
// __DIR__ mengarah ke folder /cron, jadi kita naik satu level ke root
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/routeros_api.class.php';

// Setup Koneksi Manual (Karena tidak lewat index.php)
$db = new Database();
$conn = $db->getConnection();

echo "--- MULAI PROSES AUTO ISOLIR (" . date('Y-m-d H:i:s') . ") ---\n";

// 1. Ambil Pelanggan yg AKTIF dan AUTO ISOLIR NYALA
// Kita cek yang tanggal jatuh temponya KEMARIN atau HARI INI sudah lewat
$tgl_ini = (int)date('d');
$bulan_ini = date('Y-m');

// Query: Cari pelanggan aktif yang tanggal jatuh temponya sudah lewat dari tanggal sekarang
// (Misal tgl skrg 21, cari yg jatuh tempo tgl 20 ke bawah)
$sql = "SELECT c.*, r.ip_address, r.username, r.password, r.port, p.mikrotik_profile 
        FROM customers c
        JOIN routers r ON c.router_id = r.id 
        JOIN packages p ON c.package_id = p.id
        WHERE c.status = 'active' 
        AND c.auto_isolir = 1 
        AND c.is_mikrotik = 1
        AND c.due_date < $tgl_ini"; 

$stmt = $conn->prepare($sql);
$stmt->execute();
$customers = $stmt->fetchAll();

$count = 0;

foreach ($customers as $c) {
    // 2. CEK APAKAH SUDAH BAYAR BULAN INI?
    // Cari invoice bulan ini yang statusnya 'paid'
    $cek_bayar = $conn->prepare("SELECT id FROM invoices WHERE customer_id = ? AND period_month = ? AND status = 'paid'");
    $cek_bayar->execute([$c['id'], $bulan_ini]);

    // Jika BELUM bayar (row count 0), maka ISOLIR!
    if($cek_bayar->rowCount() == 0) {
        
        echo "Memproses Isolir: " . $c['name'] . " (Jatuh Tempo Tgl " . $c['due_date'] . ")\n";
        
        // A. Eksekusi ke Mikrotik
        $API = new RouterosAPI();
        $API->debug = false;
        $API->port = $c['port'];

        if ($API->connect($c['ip_address'], $c['username'], $c['password'])) {
            
            // Cari ID Secret
            $secret = $API->comm("/ppp/secret/print", ["?name" => $c['pppoe_user']]);
            
            if (isset($secret[0]['.id'])) {
                // Ganti Profile ke ISOLIR (Pastikan profile 'isolir' sudah dibuat di Mikrotik)
                $API->comm("/ppp/secret/set", [
                    ".id" => $secret[0]['.id'],
                    "profile" => "isolir" // <--- GANTI SESUAI NAMA PROFILE ISOLIR DI MIKROTIK
                ]);
                
                // Kick User
                $active = $API->comm("/ppp/active/print", ["?name" => $c['pppoe_user']]);
                if(isset($active[0]['.id'])) {
                    $API->comm("/ppp/active/remove", [".id" => $active[0]['.id']]);
                }
                
                // B. Update Database
                $upd = $conn->prepare("UPDATE customers SET status = 'isolated' WHERE id = ?");
                $upd->execute([$c['id']]);
                
                $count++;
                echo "-> SUKSES: Terisolir.\n";
            } else {
                echo "-> GAGAL: User PPPoE tidak ditemukan di Mikrotik.\n";
            }
            $API->disconnect();
        } else {
            echo "-> GAGAL: Tidak bisa konek ke Router.\n";
        }
    }
}

echo "--- SELESAI. Total $count pelanggan diisolir. ---\n";
?>
