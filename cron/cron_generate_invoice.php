<?php
// FILE: cron/cron_generate_invoice.php
// FREKUENSI: Jalankan Setiap Jam (0 * * * *)
// FUNGSI: Membuat tagihan bulanan secara otomatis & bertahap (aman untuk ribuan user)

require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Config
set_time_limit(0);
ini_set('memory_limit', '512M');

// Logging
$logFile = __DIR__ . '/invoice_log.txt';
function writeLog($msg) {
    global $logFile;
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] $msg" . PHP_EOL, FILE_APPEND);
    echo "[$msg] <br>\n";
}

writeLog("--- START BATCH INVOICE GENERATOR ---");

// 1. TENTUKAN PERIODE TAGIHAN
// Kita generate untuk Bulan Ini.
// Format Periode: YYYY-MM (Misal: 2025-12)
$periode_ini = date('Y-m');
$tgl_inv = date('Y-m-d'); // Tanggal invoice dibuat
$jatuh_tempo_bulan_ini = date('Y-m'); // Tahun-Bulan untuk Due Date

writeLog("Target Periode: $periode_ini");

// 2. CARI KANDIDAT (LIMIT 50)
// Ambil pelanggan ACTIVE atau ISOLATED yang BELUM punya invoice di periode ini.
// Kita Join dengan tabel packages untuk ambil harga saat ini.

$sql = "SELECT c.id, c.name, c.customer_code, c.package_id, c.due_date, p.price, p.name as pkg_name
        FROM customers c
        JOIN packages p ON c.package_id = p.id
        WHERE (c.status = 'active' OR c.status = 'isolated')
        AND NOT EXISTS (
            SELECT 1 FROM invoices i 
            WHERE i.customer_id = c.id 
            AND i.period_month = ?
        )
        LIMIT 50";

$stmt = $conn->prepare($sql);
$stmt->execute([$periode_ini]);
$targets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_target = count($targets);
writeLog("Ditemukan $total_target pelanggan belum bertagihan.");

if ($total_target == 0) {
    writeLog("Semua tagihan bulan ini sudah lengkap. Selesai.");
    exit;
}

// 3. EKSEKUSI GENERATE
$count_success = 0;

$sql_insert = "INSERT INTO invoices (
    invoice_number, customer_id, package_name, amount, 
    period_month, due_date, status, created_at
) VALUES (?, ?, ?, ?, ?, ?, 'unpaid', NOW())";

$stmt_ins = $conn->prepare($sql_insert);

foreach ($targets as $row) {
    // A. Buat Nomor Invoice Unik (INV-TAHUNBULAN-IDPELANGGAN)
    // Contoh: INV-202512-0015
    $inv_no = "INV-" . date('Ym') . "-" . str_pad($row['id'], 4, '0', STR_PAD_LEFT);
    
    // B. Hitung Tanggal Jatuh Tempo Real
    // Jika tgl jatuh tempo user = 20, maka due_date = 2025-12-20
    $tgl_jt = $row['due_date'];
    // Validasi tanggal (misal tgl 30 feb tidak ada, fallback ke tgl 28)
    if($tgl_jt > 28) $tgl_jt = 28; 
    
    $full_due_date = $jatuh_tempo_bulan_ini . "-" . str_pad($tgl_jt, 2, '0', STR_PAD_LEFT);

    try {
        $stmt_ins->execute([
            $inv_no,
            $row['id'],
            $row['pkg_name'],
            $row['price'],
            $periode_ini,
            $full_due_date
        ]);
        
        $count_success++;
        writeLog(" -> Created: $inv_no for {$row['name']} ($full_due_date)");
        
    } catch (Exception $e) {
        writeLog(" -> GAGAL: {$row['name']} - " . $e->getMessage());
    }
}

writeLog("--- SELESAI. Berhasil membuat $count_success tagihan. ---");
?>
