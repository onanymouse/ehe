<?php
// FILE: cron/cron_isolir_batch.php
// UPDATED: Per-Router Isolation Logic

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/routeros_api.class.php';

$db = new Database();
$conn = $db->getConnection();

set_time_limit(0); ini_set('memory_limit', '512M');

$logFile = __DIR__ . '/isolir_log.txt';
function writeLog($msg) {
    global $logFile;
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] $msg" . PHP_EOL, FILE_APPEND);
}

writeLog("--- START BATCH ISOLIR (PER-ROUTER SETTING) ---");

// AMBIL KANDIDAT
$hari_ini = (int)date('d'); 
$bulan_ini = date('Y-m');

$sql = "SELECT c.*, 
               r.ip_address, r.username, r.password, r.port, r.name as router_name,
               r.isolir_mode, r.isolir_profile 
        FROM customers c 
        LEFT JOIN routers r ON c.router_id = r.id
        WHERE c.status = 'active' 
        AND c.auto_isolir = 1 
        AND c.due_date <= ? 
        AND NOT EXISTS (
            SELECT 1 FROM invoices i 
            WHERE i.customer_id = c.id 
            AND i.period_month = ? 
            AND i.status = 'paid'
        )
        LIMIT 50";

$stmt = $conn->prepare($sql);
$stmt->execute([$hari_ini, $bulan_ini]);
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($candidates) == 0) { writeLog("Tidak ada target. Selesai."); exit; }

// GROUPING
$tasks_by_router = [];
$non_mikrotik = [];

foreach ($candidates as $row) {
    if ($row['is_mikrotik'] == 1 && !empty($row['router_id'])) {
        $rid = $row['router_id'];
        if (!isset($tasks_by_router[$rid])) {
            $tasks_by_router[$rid] = [
                'info' => [
                    'ip'=>$row['ip_address'], 'user'=>$row['username'], 'pass'=>$row['password'], 'port'=>$row['port'], 'name'=>$row['router_name'],
                    // Settingan Isolir Per Router
                    'iso_mode' => $row['isolir_mode'],
                    'iso_prof' => $row['isolir_profile']
                ],
                'users' => []
            ];
        }
        $tasks_by_router[$rid]['users'][] = $row;
    } else {
        $non_mikrotik[] = $row;
    }
}

// EKSEKUSI
foreach ($tasks_by_router as $rid => $group) {
    $rInfo = $group['info'];
    $users = $group['users'];
    
    // Tentukan mode untuk router ini
    $mode = $rInfo['iso_mode'];     // 'disable' atau 'profile'
    $prof = $rInfo['iso_prof'];     // Nama profile (misal: ISOLIR)

    writeLog("Router: {$rInfo['name']} | Mode: $mode ($prof)");

    $API = new RouterosAPI();
    $API->debug = false; $API->port = $rInfo['port'];

    if ($API->connect($rInfo['ip'], $rInfo['user'], $rInfo['pass'])) {
        foreach ($users as $target) {
            $user = $target['pppoe_user'];
            $secret = $API->comm("/ppp/secret/print", ["?name" => $user]);
            
            if (count($secret) > 0) {
                $id_secret = $secret[0]['.id'];
                
                if ($mode == 'profile' && !empty($prof)) {
                    // MODE GANTI PROFILE
                    $API->comm("/ppp/secret/set", [
                        ".id" => $id_secret,
                        "profile" => $prof,
                        "comment" => "ISOLIR (Profile) - " . date('d/m')
                    ]);
                    writeLog(" -> $user: Change Profile to $prof");
                } else {
                    // MODE DISABLE (Default)
                    $API->comm("/ppp/secret/disable", [".id" => $id_secret]);
                    writeLog(" -> $user: Disable Secret");
                }

                // KICK USER (Wajib)
                $active = $API->comm("/ppp/active/print", ["?name" => $user]);
                if (count($active) > 0) {
                    $API->comm("/ppp/active/remove", [".id" => $active[0]['.id']]);
                }
            }

            // Update Database
            $conn->prepare("UPDATE customers SET status = 'isolated' WHERE id = ?")->execute([$target['id']]);
        }
        $API->disconnect();
    } else {
        writeLog(" -> GAGAL KONEK ROUTER.");
    }
}

// EKSEKUSI NON-MIKROTIK
foreach ($non_mikrotik as $nm) {
    $conn->prepare("UPDATE customers SET status = 'isolated' WHERE id = ?")->execute([$nm['id']]);
    writeLog(" -> Isolir DB Only: " . $nm['name']);
}

writeLog("--- BATCH SELESAI ---");
?>
