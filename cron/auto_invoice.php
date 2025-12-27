<?php
// Script dijalankan oleh Server (CLI), jadi gunakan path absolut
require_once __DIR__ . '/../config/database.php';

// Setup Koneksi
$db = new Database();
$conn = $db->getConnection();

echo "--- MULAI AUTO GENERATE INVOICE (" . date('Y-m-d H:i:s') . ") ---\n";

// Setting: Tanggal berapa tagihan dibuat otomatis?
// Biasanya ISP membuat tagihan serentak di Tanggal 1 atau Tanggal 20
// Kita set agar script ini hanya efektif bekerja di Tanggal 1 setiap bulan
// (Tapi aman dijalankan tanggal berapapun karena ada cek duplikat)
$tgl_sekarang = (int)date('d');

// Opsional: Jika ingin strict hanya tgl 1, uncomment baris bawah:
// if ($tgl_sekarang != 1) { die("Hari ini bukan tanggal 1. Skip generate.\n"); }

$bulan_ini = date('Y-m'); // Format: 2023-12

// 1. Ambil Semua Pelanggan Aktif
$sql = "SELECT id, customer_code, name, package_id FROM customers WHERE status != 'nonactive'";
$stmt = $conn->prepare($sql);
$stmt->execute();
$customers = $stmt->fetchAll();

$count = 0;

foreach ($customers as $c) {
    // 2. Cek apakah sudah ada tagihan bulan ini?
    $cek = $conn->prepare("SELECT id FROM invoices WHERE customer_id = ? AND period_month = ?");
    $cek->execute([$c['id'], $bulan_ini]);

    if ($cek->rowCount() == 0) {
        // BELUM ADA TAGIHAN -> BUAT BARU
        
        // Ambil harga paket
        $pkg = $conn->prepare("SELECT price FROM packages WHERE id = ?");
        $pkg->execute([$c['package_id']]);
        $paket = $pkg->fetch();
        
        if ($paket) {
            $price = $paket['price'];
            // Buat No Invoice: INV-TAHUNBULAN-ID (Contoh: INV-202312-0001)
            $inv_no = "INV-" . date('Ym') . "-" . sprintf("%04s", $c['id']);

            try {
                $ins = $conn->prepare("INSERT INTO invoices (invoice_number, customer_id, period_month, amount, status, created_at) VALUES (?, ?, ?, ?, 'unpaid', NOW())");
                $ins->execute([$inv_no, $c['id'], $bulan_ini, $price]);
                
                echo "[OK] Tagihan dibuat untuk: " . $c['name'] . " (" . $inv_no . ")\n";
                $count++;
            } catch (Exception $e) {
                echo "[ERROR] Gagal buat tagihan " . $c['name'] . ": " . $e->getMessage() . "\n";
            }
        }
    } else {
        // SUDAH ADA -> SKIP
        // echo "[SKIP] " . $c['name'] . " sudah punya tagihan.\n";
    }
}

echo "--- SELESAI. Berhasil generate $count tagihan baru. ---\n";
?>
