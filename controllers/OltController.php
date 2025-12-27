<?php
require_once 'helpers/telnet_api.php';

class OltController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // --- 1. INDEX ---
    public function index() {
        $olts = $this->conn->query("SELECT * FROM olts ORDER BY name ASC")->fetchAll();
        require_once 'views/olt/index.php';
    }

    // --- 2. DETAIL ---
    public function detail() {
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $stmt = $this->conn->prepare("SELECT * FROM olts WHERE id = ?");
        $stmt->execute([$id]);
        $olt = $stmt->fetch();

        if(!$olt) { die("OLT Tidak Ditemukan"); }

        $sql_stat = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN state LIKE '%working%' OR state LIKE '%online%' THEN 1 ELSE 0 END) as online,
                        SUM(CASE WHEN state LIKE '%los%' THEN 1 ELSE 0 END) as los,
                        SUM(CASE WHEN state LIKE '%offline%' OR state LIKE '%dying%' THEN 1 ELSE 0 END) as offline
                      FROM olt_onus WHERE olt_id = ?";
        $q_stat = $this->conn->prepare($sql_stat);
        $q_stat->execute([$id]);
        $stat = $q_stat->fetch();

        $sql_pon = "SELECT DISTINCT SUBSTRING_INDEX(interface, ':', 1) as pon_port FROM olt_onus WHERE olt_id = ? ORDER BY interface ASC";
        $q_pon = $this->conn->prepare($sql_pon);
        $q_pon->execute([$id]);
        $pons = $q_pon->fetchAll();

        require_once 'views/olt/detail.php';
    }

    // --- 3. SYNC MASSAL ---
        // --- 3. SYNC MASSAL (DENGAN PENGAMAN DATA PELANGGAN) ---
    public function sync() {
        set_time_limit(0); ini_set('memory_limit', '1024M');
        ini_set('display_errors', 0); error_reporting(0); while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        try {
            $id = $_GET['id'];
            $stmt = $this->conn->prepare("SELECT * FROM olts WHERE id = ?"); 
            $stmt->execute([$id]); 
            $olt = $stmt->fetch();

            if(!$olt) throw new Exception("OLT DB Error");

            $telnet = new TelnetAPI();
            if ($telnet->connect($olt['ip_address'], $olt['username'], $olt['password'], $olt['telnet_port'])) {
                
                // Catat waktu mulai sync
                $sync_time = date('Y-m-d H:i:s');
                
                $telnet->exec("terminal length 0");
                $output = $telnet->exec("show gpon onu state");
                $telnet->disconnect();

                $lines = explode("\n", $output);
                $updated = 0;
                
                $this->conn->beginTransaction();
                
                // Query Insert/Update (Sama seperti sebelumnya)
                $sql = "INSERT INTO olt_onus (olt_id, interface, state, dbm, updated_at) 
                        VALUES (?, ?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE state = VALUES(state), updated_at = VALUES(updated_at)";
                $stmt_ins = $this->conn->prepare($sql);

                foreach($lines as $line) {
                    $line = trim($line);
                    if (preg_match('/^(\d+\/\d+\/\d+:\d+)\s/', $line, $match)) {
                        $iface = "gpon-onu_" . $match[1];
                        
                        $state = 'Offline';
                        if (stripos($line, 'working')!==false) $state='Online';
                        elseif (stripos($line, 'online')!==false) $state='Online';
                        elseif (stripos($line, 'los')!==false) $state='LOS';
                        elseif (stripos($line, 'dying')!==false) $state='Power Fail';
                        
                        $stmt_ins->execute([$id, $iface, $state, 'N/A', $sync_time]);
                        $updated++;
                    }
                }
                
                // === [BAGIAN PENGAMAN DATA PELANGGAN] ===
                
                // 1. Cari pelanggan yang terhubung ke modem yang akan dihapus
                // (Modem akan dihapus jika 'updated_at' < waktu sync sekarang)
                // Kita "Unlink" dulu (Set NULL) agar data pelanggan tidak error/hilang
                $sql_safety = "UPDATE customers c 
                               JOIN olt_onus o ON (c.onu_interface = o.interface AND c.olt_id = o.olt_id)
                               SET c.onu_interface = NULL 
                               WHERE o.olt_id = ? AND o.updated_at < ?";
                $this->conn->prepare($sql_safety)->execute([$id, $sync_time]);

                // 2. Baru hapus ONU yang sudah tidak ada di OLT
                $sql_clean = "DELETE FROM olt_onus WHERE olt_id = ? AND updated_at < ?";
                $stmt_clean = $this->conn->prepare($sql_clean);
                $stmt_clean->execute([$id, $sync_time]);
                
                $deleted = $stmt_clean->rowCount();
                
                $this->conn->commit();
                
                echo json_encode(['status' => 'success', 'message' => "Sync Selesai.\nUpdate: $updated ONU.\nHapus: $deleted ONU lama (Pelanggan Aman)."]);

            } else { throw new Exception("Gagal Telnet ke OLT."); }

        } catch (Exception $e) {
            if($this->conn->inTransaction()) $this->conn->rollBack();
            echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
        }
        exit;
    }

    
    
    // --- 4. DETAIL ONU (FIX: USE FULL INTERFACE NAME) ---
    public function detail_onu() {
        ini_set('display_errors', 0); error_reporting(0); while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        try {
            $id_onu = $_GET['id'];
            $sql = "SELECT o.*, olt.ip_address, olt.username, olt.password, olt.telnet_port 
                    FROM olt_onus o 
                    JOIN olts olt ON o.olt_id = olt.id 
                    WHERE o.id = ?";
            $stmt = $this->conn->prepare($sql); 
            $stmt->execute([$id_onu]); 
            $data = $stmt->fetch();

            if(!$data) throw new Exception("Data tidak ditemukan");

            // FIX: GUNAKAN INTERFACE LENGKAP DARI DATABASE (gpon-onu_1/1/1:1)
            // JANGAN DI-STRIP/DIPOTONG
            $full_iface = $data['interface']; 

            $telnet = new TelnetAPI();
            if (!$telnet->connect($data['ip_address'], $data['username'], $data['password'], $data['telnet_port'])) {
                throw new Exception("Gagal Konek OLT");
            }

            $telnet->exec("terminal length 0");
            
            // 1. AMBIL DETAIL (STATUS ADA DISINI - PHASE STATE)
            $detail_raw = $telnet->exec("show gpon onu detail-info " . $full_iface);
            
            // 2. AMBIL SINYAL
            $power_raw = $telnet->exec("show pon power attenuation " . $full_iface);
            
            $telnet->disconnect();

            // --- PARSING PINTAR (PHASE STATE) ---
            
            $new_state = 'Offline'; // Default start
            
            // Cari baris yang mengandung 'Phase State' atau 'State'
            if (preg_match('/Phase state\s*:\s*([a-zA-Z0-9_-]+)/i', $detail_raw, $matches)) {
                $raw_state = strtolower($matches[1]); // working/los/dying-gasp
                
                if (strpos($raw_state, 'working') !== false) $new_state = 'Online';
                elseif (strpos($raw_state, 'operational') !== false) $new_state = 'Online';
                elseif (strpos($raw_state, 'los') !== false) $new_state = 'LOS';
                elseif (strpos($raw_state, 'dying') !== false) $new_state = 'Power Fail';
                elseif (strpos($raw_state, 'offline') !== false) $new_state = 'Offline';
            } else {
                // Fallback ke data lama jika regex gagal
                $new_state = $data['state'];
            }

            // --- PARSING DETAIL ---
            $sn = '-'; $type = '-'; $dist = '-';
            if(preg_match('/Serial number\s*:\s*(\S+)/i', $detail_raw, $m)) $sn = $m[1];
            if(preg_match('/Type\s*:\s*(\S+)/i', $detail_raw, $m)) $type = $m[1];
            if(preg_match('/Distance\s*:\s*(\d+)m/i', $detail_raw, $m)) $dist = $m[1];

            // --- PARSING SINYAL ---
            $rx = 'N/A';
            if (preg_match('/down\s+.*Rx\s*:\s*(-?[\d\.]+)/i', $power_raw, $m_rx)) {
                $rx = $m_rx[1];
            } elseif(preg_match('/Rx\s*:\s*(-?[\d\.]+)\(dbm\)/i', $power_raw, $m_rx2)) {
                $rx = $m_rx2[1];
            }

            // UPDATE DB
            $upd = $this->conn->prepare("UPDATE olt_onus SET sn=?, type=?, dbm=?, state=?, last_sync=NOW() WHERE id=?");
            $upd->execute([$sn, $type, $rx, $new_state, $id_onu]);

            echo json_encode([
                'status' => 'success',
                'interface' => $data['interface'],
                'state' => $new_state,
                'sn' => $sn,
                'type' => $type,
                'distance' => $dist,
                'rx_power' => $rx,
                'raw_debug' => substr($detail_raw, 0, 150) // Debug potong dikit
            ]);

        } catch (Exception $e) {
            echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
        }
        exit;
    }

    // --- 5. REBOOT ONU ---
    public function reboot_onu() {
        ini_set('display_errors', 0); error_reporting(0); while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        
        $id = $_GET['id'];
        $sql = "SELECT o.interface, olt.ip_address, olt.username, olt.password, olt.telnet_port FROM olt_onus o JOIN olts olt ON o.olt_id = olt.id WHERE o.id = ?";
        $stmt = $this->conn->prepare($sql); $stmt->execute([$id]); $data = $stmt->fetch();

        if ($data) {
            try {
                $telnet = new TelnetAPI();
                if ($telnet->connect($data['ip_address'], $data['username'], $data['password'], $data['telnet_port'])) {
                    // Gunakan Interface Lengkap (gpon-onu_...) untuk pon-onu-mng
                    $telnet->exec("conf t");
                    $telnet->exec("pon-onu-mng " . $data['interface']);
                    $telnet->exec("reboot");
                    $telnet->exec("yes");
                    $telnet->disconnect();
                    echo json_encode(['status' => 'success', 'message' => 'Reboot terkirim.']);
                } else { throw new Exception("Gagal Konek OLT"); }
            } catch (Exception $e) { echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]); }
        } else { echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']); }
        exit;
    }

    // --- 6. RESET FACTORY (FIX: FULL INTERFACE + HIT & RUN) ---
    public function reset_onu() {
        ini_set('display_errors', 0); error_reporting(0); while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        ini_set('default_socket_timeout', 3); 

        $id = $_POST['id'];
        $sql = "SELECT o.interface, olt.ip_address, olt.username, olt.password, olt.telnet_port FROM olt_onus o JOIN olts olt ON o.olt_id = olt.id WHERE o.id = ?";
        $stmt = $this->conn->prepare($sql); $stmt->execute([$id]); $data = $stmt->fetch();

        if (!$data) { echo json_encode(['status'=>'error', 'message'=>'Data tidak valid']); exit; }

        try {
            $telnet = new TelnetAPI();
            if (!$telnet->connect($data['ip_address'], $data['username'], $data['password'], $data['telnet_port'])) {
                throw new Exception("Gagal koneksi Telnet");
            }

            // Gunakan Interface Lengkap (gpon-onu_1/1/1:1)
            $telnet->exec("conf t");
            $telnet->exec("pon-onu-mng " . $data['interface']);
            
            try { $telnet->exec("restore factory"); } catch (Exception $e) {}
            
            @$telnet->disconnect();
            
            $this->conn->prepare("UPDATE olt_onus SET state='Offline (Resetting)', last_sync=NOW() WHERE id=?")->execute([$id]);
            echo json_encode(['status'=>'success', 'message'=>"Perintah RESET dikirim!"]);

        } catch (Exception $e) { echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]); }
        exit;
    }

    // --- 7. DELETE ONU (FIX: PARSING PON-OLT DENGAN BENAR) ---
    public function delete_onu() {
        ini_set('display_errors', 0); error_reporting(0); while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        $id = $_POST['id'];
        $sql = "SELECT o.interface, olt.ip_address, olt.username, olt.password, olt.telnet_port FROM olt_onus o JOIN olts olt ON o.olt_id = olt.id WHERE o.id = ?";
        $stmt = $this->conn->prepare($sql); $stmt->execute([$id]); $data = $stmt->fetch();

        if (!$data) { echo json_encode(['status'=>'error', 'message'=>'Data tidak valid']); exit; }

        // PARSING UNTUK DELETE
        // Data di DB: gpon-onu_1/1/12:5
        // Target: interface gpon-olt_1/1/12 (Ganti onu jadi olt, hapus ID setelah titik dua)
        
        $full_onu_iface = $data['interface'];
        $parts = explode(':', $full_onu_iface); 
        // $parts[0] = "gpon-onu_1/1/12"
        // $parts[1] = "5" (ID)

        if(count($parts) < 2) { echo json_encode(['status'=>'error', 'message'=>'Format Interface Salah']); exit; }

        // Ganti 'onu' jadi 'olt' pada bagian pertama
        $pon_olt_iface = str_replace('onu', 'olt', $parts[0]); // Jadi: gpon-olt_1/1/12
        $onu_id = $parts[1]; // Jadi: 5

        try {
            $telnet = new TelnetAPI();
            if (!$telnet->connect($data['ip_address'], $data['username'], $data['password'], $data['telnet_port'])) {
                throw new Exception("Gagal koneksi Telnet");
            }

            $telnet->exec("conf t");
            
            // Masuk Interface OLT (gpon-olt_...)
            $telnet->exec("interface " . $pon_olt_iface);
            
            // Hapus ONU ID
            $res = $telnet->exec("no onu " . $onu_id);
            
            if(stripos($res, 'Error') !== false) {
                $telnet->disconnect();
                throw new Exception("OLT Error: $res");
            }

            $telnet->exec("exit"); 
            $telnet->exec("exit"); 
            try { $telnet->exec("write"); } catch(Exception $w) {}
            $telnet->disconnect();

            $this->conn->prepare("DELETE FROM olt_onus WHERE id = ?")->execute([$id]);
            
            echo json_encode(['status'=>'success', 'message'=>"Modem berhasil DIHAPUS dari OLT & Database!"]);

        } catch (Exception $e) {
            echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
        }
        exit;
    }

    // --- 8. DATATABLES ---
    public function get_onu_ajax() {
        ini_set('display_errors', 0); error_reporting(0); while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        try {
            $id_olt = $_GET['id_olt'];
            $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
            $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
            $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
            $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
            $f_pon = isset($_GET['pon']) ? $_GET['pon'] : '';
            $f_status = isset($_GET['status']) ? $_GET['status'] : '';

            $sql_base = " FROM olt_onus o LEFT JOIN customers c ON (o.interface = c.onu_interface AND c.olt_id = o.olt_id) WHERE o.olt_id = ? ";
            $params = [$id_olt];

            if(!empty($f_pon)) { $sql_base .= " AND o.interface LIKE ? "; $params[] = $f_pon . ":%"; }
            if(!empty($f_status)) {
                if($f_status=='online') $sql_base .= " AND (o.state LIKE '%working%' OR o.state LIKE '%online%') ";
                elseif($f_status=='los') $sql_base .= " AND o.state LIKE '%los%' ";
                elseif($f_status=='offline') $sql_base .= " AND o.state LIKE '%offline%' ";
            }
            if(!empty($search)) {
                $sql_base .= " AND (o.interface LIKE ? OR o.sn LIKE ? OR c.name LIKE ? OR c.pppoe_user LIKE ?) ";
                $p = "%$search%"; $params[] = $p; $params[] = $p; $params[] = $p; $params[] = $p;
            }

            $count_q = $this->conn->prepare("SELECT COUNT(o.id) " . $sql_base);
            $count_q->execute($params);
            $recordsFiltered = $count_q->fetchColumn();

            $sql_final = "SELECT o.*, c.name as cust_name, c.pppoe_user " . $sql_base . " ORDER BY o.interface ASC LIMIT $start, $length";
            $stmt = $this->conn->prepare($sql_final);
            $stmt->execute($params);
            $data = $stmt->fetchAll();

            $formatted = [];
            foreach($data as $row) {
                $bg = 'badge-secondary';
                if(stripos($row['state'], 'working')!==false || stripos($row['state'], 'online')!==false) $bg = 'badge-success';
                elseif(stripos($row['state'], 'los')!==false) $bg = 'badge-danger';
                
                $dbm = $row['dbm']; $col_dbm = 'text-muted';
                if($dbm != 'N/A') {
                    $val = floatval($dbm);
                    if($val < -27) $col_dbm = 'text-danger font-weight-bold';
                    elseif($val < -24) $col_dbm = 'text-warning font-weight-bold';
                    else $col_dbm = 'text-success font-weight-bold';
                }

                $info_pel = '<span class="text-muted text-xs"><i>Unregistered</i></span>';
                if($row['cust_name']) { $info_pel = "<span class='text-primary font-weight-bold'>{$row['cust_name']}</span><br><small class='text-muted'>{$row['pppoe_user']}</small>"; }

                $btn = "<button class='btn btn-sm btn-info' onclick='detailOnu({$row['id']})'><i class='fas fa-search'></i> Detail</button>";

                $formatted[] = [ "0" => "<b>{$row['interface']}</b><br><small>{$row['sn']}</small>", "1" => $info_pel, "2" => "<span class='badge $bg'>{$row['state']}</span>", "3" => "<span class='$col_dbm'>$dbm</span>", "4" => $btn ];
            }
            echo json_encode(["draw"=>$draw, "recordsTotal"=>$recordsFiltered, "recordsFiltered"=>$recordsFiltered, "data"=>$formatted]);

        } catch (Exception $e) { echo json_encode(["error"=>$e->getMessage()]); }
        exit;
    }
    public function create() { require_once 'views/olt/create.php'; }
    public function store() { if ($_SERVER['REQUEST_METHOD'] == 'POST') { $sql = "INSERT INTO olts (name, ip_address, username, password, telnet_port) VALUES (?, ?, ?, ?, ?)"; $this->conn->prepare($sql)->execute([$_POST['name'], $_POST['ip_address'], $_POST['username'], $_POST['password'], $_POST['telnet_port']]); redirect('index.php?page=olt&action=index'); } }
    public function delete() { $id = $_GET['id']; $this->conn->prepare("DELETE FROM olts WHERE id=?")->execute([$id]); $this->conn->prepare("DELETE FROM olt_onus WHERE olt_id=?")->execute([$id]); redirect('index.php?page=olt&action=index'); }
}
?>
