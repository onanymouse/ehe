<?php
// FILE: controllers/OltManagerProController.php
require_once 'helpers/TelnetPro.php';

class OltManagerProController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    private function json($data) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function index() {
        $olts = $this->conn->query("SELECT * FROM olts ORDER BY name ASC")->fetchAll();
        require_once 'views/olt_pro/index.php';
    }

    // =========================================================
    // 1. DATA PENDUKUNG (STATS & DATATABLES)
    // =========================================================
    public function get_stats() {
        $olt_id = $_POST['olt_id'];
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status LIKE '%work%' OR status LIKE '%oper%' THEN 1 ELSE 0 END) as online,
                    SUM(CASE WHEN status LIKE '%los%' THEN 1 ELSE 0 END) as los,
                    SUM(CASE WHEN status LIKE '%off%' OR status LIKE '%dying%' THEN 1 ELSE 0 END) as offline
                FROM olt_onus_pro WHERE olt_id = '$olt_id'";
        $stats = $this->conn->query($sql)->fetch(PDO::FETCH_ASSOC);
        $this->json(['status'=>'success', 'data'=>$stats]);
    }

    // [BARU] Load List Interface dari Database untuk Filter
    public function get_db_pons() {
        $id = $_POST['id'];
        $sql = "SELECT DISTINCT pon_port FROM olt_onus_pro WHERE olt_id = '$id' ORDER BY pon_port ASC";
        $rows = $this->conn->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        $this->json(['status'=>'success', 'data'=>$rows]);
    }

    public function get_table_data() {
        $olt_id = $_POST['olt_id'];
        $filter_pon = $_POST['filter_pon'] ?? ''; // Parameter Filter

        $draw = $_POST['draw'];
        $start = $_POST['start'];
        $length = $_POST['length'];
        $search = $_POST['search']['value'];

        $sql = "FROM olt_onus_pro WHERE olt_id = '$olt_id' ";
        
        // Filter Dropdown
        if(!empty($filter_pon)) {
            $sql .= " AND pon_port = '$filter_pon' ";
        }

        // Filter Pencarian
        if(!empty($search)) {
            $sql .= " AND (interface LIKE '%$search%' OR name LIKE '%$search%' OR sn LIKE '%$search%' OR description LIKE '%$search%') ";
        }

        $count_sql = "SELECT COUNT(*) " . $sql;
        $recordsFiltered = $this->conn->query($count_sql)->fetchColumn();
        $total_sql = "SELECT COUNT(*) FROM olt_onus_pro WHERE olt_id = '$olt_id'";
        $recordsTotal = $this->conn->query($total_sql)->fetchColumn();

        $sql .= " ORDER BY interface ASC LIMIT $start, $length ";
        $rows = $this->conn->query("SELECT * " . $sql)->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach($rows as $r) {
            $st = strtolower($r['status']);
            $bg = 'secondary';
            if(strpos($st, 'work')!==false) $bg = 'success';
            elseif(strpos($st, 'los')!==false) $bg = 'danger';
            elseif(strpos($st, 'dying')!==false) $bg = 'warning';

            $rx = $r['rx_power'];
            $rx_class = 'text-muted';
            if($rx != '-' && $rx != '') {
                $val = floatval($rx);
                if($val < -25) $rx_class = 'text-warning font-weight-bold';
                if($val < -27) $rx_class = 'text-danger font-weight-bold';
                if($val >= -25) $rx_class = 'text-success font-weight-bold';
            }

            $btn = "<button class='btn btn-info btn-sm btn-action' onclick='openDetail(".json_encode($r).")'><i class='fas fa-cog'></i> Detail</button>";

            $data[] = [
                "interface" => "<span class='font-weight-bold'>{$r['interface']}</span>",
                "name"      => "<b>{$r['name']}</b><br><small>{$r['description']}</small>",
                "sn"        => "{$r['sn']}<br><small>{$r['onu_type']}</small>",
                "status"    => "<span class='badge badge-$bg'>".strtoupper($st)."</span>",
                "rx_power"  => "<span class='$rx_class'>$rx</span>",
                "action"    => $btn
            ];
        }

        echo json_encode(["draw" => intval($draw), "recordsTotal" => intval($recordsTotal), "recordsFiltered" => intval($recordsFiltered), "data" => $data]);
        exit;
    }

    // =========================================================
    // 2. AUTO DISCOVERY (BACA HARDWARE VIA TELNET)
    // =========================================================
    public function get_pon_interfaces() {
        ob_start();
        $id = $_POST['id'];
        $olt = $this->conn->query("SELECT * FROM olts WHERE id='$id'")->fetch();
        try { 
            $t = new TelnetPro($olt['ip_address'] ?? $olt['host'], $olt['telnet_port'] ?? 23, 5); 
            $t->login($olt['username'], $olt['password']); 
            $raw = $t->exec("show card"); 
            $t->disconnect();

            $pons = [];
            foreach(explode("\n", $raw) as $line) {
                if(preg_match('/^\s*(\d+)\s+(\d+)\s+(\d+)\s+\w+\s+(\w+)\s+(\d+)/', trim($line), $m)){ 
                    $prefix = '';
                    if(strpos($m[4], 'GT') === 0) $prefix = 'gpon-olt';
                    if(strpos($m[4], 'ET') === 0) $prefix = 'epon-olt';
                    $ports = (int)$m[5];

                    if($prefix != '' && $ports > 0) {
                        for($i=1; $i<=$ports; $i++) $pons[] = "{$prefix}_{$m[1]}/{$m[2]}/{$m[3]}:{$i}";
                    }
                } 
            }
            natsort($pons);
            $this->json(['status'=>'success', 'data'=>array_values($pons)]);
        } catch(Exception $e){ $this->json(['status'=>'error', 'message'=>$e->getMessage()]); }
    }

    // =========================================================
    // 3. CORE SYNC (MULTI-OLT SAFE)
    // =========================================================
        // =========================================================
    // 3. CORE SYNC (DEBUG MODE ENABLED)
    // =========================================================
        // =========================================================
    // 3. CORE SYNC (METODE "RUNNING CONFIG" - ANTI ERROR)
    // =========================================================
    // =========================================================
    // 3. CORE SYNC (FIX: PARSING MULTI-LINE / TERPOTONG)
    // =========================================================
    public function sync_data() {
        ob_start();
        set_time_limit(300); 
        error_reporting(0);
        
        $id = $_POST['olt_id'];
        $pon_interface = $_POST['pon_interface']; 

        // Fix Format Interface (hapus gpon-olt_ jika ada, biar command clean)
        // OLT kadang suka format pendek: show gpon onu state 1/1/1
        $clean_pon = str_replace(['gpon-olt_', 'gpon-onu_'], '', $pon_interface); 
        // Hapus juga bagian port :16 kalau ada, kita butuh state per PON
        $clean_pon = explode(':', $clean_pon)[0];

        $olt = $this->conn->query("SELECT * FROM olts WHERE id='$id'")->fetch();
        
        try {
            $t = new TelnetPro($olt['ip_address'] ?? $olt['host'], $olt['telnet_port'] ?? 23);
            $t->login($olt['username'], $olt['password']);
            
            // 1. AMBIL STATUS (RAW)
            $raw_state = $t->exec("show gpon onu state $clean_pon");
            
            // 2. AMBIL SINYAL
            $raw_power = $t->exec("show pon power attenuation $clean_pon");

            // 3. AMBIL CONFIG (NAMA & DESC)
            $raw_config = $t->exec("show running-config interface $clean_pon");
            
            $t->disconnect();

            $onus = [];
            $live_interfaces = []; 

            // --- A. PARSING STATUS (TEKNIK GILAS RATA) ---
            // Ubah semua newline/tab/spasi ganda menjadi SATU SPASI tunggal
            // Output: 1/1/1:1 enable enable working 1(GPON) 1/1/1:2 ...
            $flat_state = preg_replace('/\s+/', ' ', $raw_state);

            // Regex Mencari: (Interface) (Admin) (OMCC) (Phase/Status)
            // Pola: 1/1/1:1 enable enable working
            if(preg_match_all('/(\d+\/\d+\/\d+:\d+)\s+\w+\s+\w+\s+(\w+)/', $flat_state, $matches, PREG_SET_ORDER)) {
                
                foreach($matches as $m) {
                    $short_iface = $m[1]; // 1/1/1:1
                    $st_raw = strtolower($m[2]); // working/los/offline
                    
                    $iface = "gpon-onu_" . $short_iface; // Standardisasi nama
                    $live_interfaces[] = $iface;

                    $st = 'offline';
                    if(strpos($st_raw,'work')!==false) $st='working';
                    elseif(strpos($st_raw,'oper')!==false) $st='working';
                    elseif(strpos($st_raw,'los')!==false) $st='los';
                    elseif(strpos($st_raw,'dying')!==false) $st='dying-gasp';

                    $onus[$iface] = [
                        'interface' => $iface, 
                        'pon_port' => "gpon-olt_".$clean_pon, // Grouping
                        'status' => $st,
                        'name' => '-', 'desc' => '-', 'sn' => '-', 'type' => '-', 'rx' => '-'
                    ];
                }
            }

            // JIKA KOSONG -> KIRIM DEBUG
            if(empty($onus)) {
                $this->json([
                    'status'=>'error', 
                    'message'=>'Data Kosong (Regex Meleset).',
                    'debug_raw' => substr($flat_state, 0, 500) // Kirim data yg sudah digilas
                ]);
            }

            // --- B. PARSING CONFIG (NAMA & DESC) ---
            $curr_iface = null;
            foreach(explode("\n", $raw_config) as $line) {
                $line = trim($line);
                if(strpos($line, 'interface gpon-onu_') !== false) {
                    if(preg_match('/(gpon-onu_\d+\/\d+\/\d+:\d+)/', $line, $m)) $curr_iface = $m[1];
                } 
                elseif($curr_iface && isset($onus[$curr_iface])) {
                    if(strpos($line, 'name ') === 0) $onus[$curr_iface]['name'] = trim(str_replace(['name ', '"'], '', $line));
                    elseif(strpos($line, 'description ') === 0) $onus[$curr_iface]['desc'] = trim(str_replace(['description ', '"'], '', $line));
                }
            }

            // --- C. PARSING POWER (GILAS JUGA) ---
            $flat_power = preg_replace('/\s+/', ' ', $raw_power);
            // Cari: gpon-onu_1/1/1:1 ... Rx:-20.5
            if(preg_match_all('/(gpon-onu_\d+\/\d+\/\d+:\d+).*?Rx:(-?[\d\.]+)/i', $flat_power, $matches_p, PREG_SET_ORDER)) {
                foreach($matches_p as $mp) {
                    $k = $mp[1];
                    if(isset($onus[$k])) $onus[$k]['rx'] = $mp[2];
                }
            }
            // Fallback Regex Power (Format lain: Rx :-20.5)
            if(preg_match_all('/(gpon-onu_\d+\/\d+\/\d+:\d+).*?Rx\s*:(-?[\d\.]+)/i', $flat_power, $matches_p2, PREG_SET_ORDER)) {
                foreach($matches_p2 as $mp) {
                    $k = $mp[1];
                    if(isset($onus[$k])) $onus[$k]['rx'] = $mp[2];
                }
            }

            // --- D. SIMPAN DB ---
            $stmt = $this->conn->prepare("
                INSERT INTO olt_onus_pro (olt_id, interface, pon_port, name, description, sn, onu_type, status, rx_power, last_sync)
                VALUES (:oid, :iface, :pon, :nm, :desc, :sn, :type, :st, :rx, NOW())
                ON DUPLICATE KEY UPDATE
                name=:nm, description=:desc, sn=:sn, onu_type=:type, status=:st, rx_power=:rx, last_sync=NOW()
            ");

            $count = 0;
            foreach($onus as $o) {
                $stmt->execute([
                    ':oid' => $id, ':iface' => $o['interface'], ':pon' => $o['pon_port'],
                    ':nm' => substr($o['name'],0,100), ':desc' => substr($o['desc'],0,200), 
                    ':sn' => $o['sn'], ':type' => $o['type'], ':st' => $o['status'], ':rx' => $o['rx']
                ]);
                $count++;
            }

            // Cleanup
            if(!empty($live_interfaces)) {
                $placeholders = implode(',', array_fill(0, count($live_interfaces), '?'));
                $pon_group = "gpon-olt_".$clean_pon;
                $sql_clean = "DELETE FROM olt_onus_pro WHERE olt_id = ? AND pon_port = ? AND interface NOT IN ($placeholders)";
                $params = array_merge([$id, $pon_group], $live_interfaces);
                $this->conn->prepare($sql_clean)->execute($params);
            }

            $this->json(['status'=>'success', 'message'=>"Sync OK. $count Data."]);

        } catch (Exception $e) { $this->json(['status'=>'error', 'message'=>$e->getMessage()]); }
    }
    // =========================================================
    // 4. AKSI KONTROL
    // =========================================================
    public function reboot_onu() { $this->do_action("reboot", false); }
    public function reset_onu() { $this->do_action("restore factory", true); }
    
    public function delete_onu() {
        ob_start();
        try {
            $id=$_POST['olt_id']; $iface=$_POST['interface'];
            $olt=$this->conn->query("SELECT * FROM olts WHERE id='$id'")->fetch();
            $parts=explode(':', str_replace('gpon-onu_','',$iface));
            $card="gpon-olt_".$parts[0]; $oid=$parts[1];

            $t=new TelnetPro($olt['ip_address'],$olt['telnet_port']); $t->login($olt['username'],$olt['password']);
            $t->exec("conf t"); $t->exec("interface $card"); $t->exec("no onu $oid");
            $t->exec("exit"); $t->exec("exit"); $t->disconnect();

            // Hapus dari DB
            $this->conn->prepare("DELETE FROM olt_onus_pro WHERE interface=? AND olt_id=?")->execute([$iface, $id]);
            $this->json(['status'=>'success', 'message'=>"ONU Terhapus Permanen!"]);
        } catch(Exception $e) { $this->json(['status'=>'error', 'message'=>$e->getMessage()]); }
    }

    public function push_tr069() {
        ob_start(); set_time_limit(60);
        $id=$_POST['olt_id']; $iface=$_POST['interface']; 
        $olt=$this->conn->query("SELECT * FROM olts WHERE id='$id'")->fetch();
        try {
            $t=new TelnetPro($olt['ip_address'],$olt['telnet_port']); $t->login($olt['username'],$olt['password']);
            $t->exec("conf t"); $t->exec("interface $iface");
            $t->exec("tcont 4 profile 1000M"); $t->exec("gemport 4 tcont 4");
            $t->exec("gemport 4 traffic-limit upstream UP1000M downstream DW1000M");
            $t->exec("service-port 4 vport 4 user-vlan 100 vlan 100"); $t->exec("exit");
            $t->exec("pon-onu-mng $iface");
            $t->exec("service TR609 gemport 4 vlan 100"); $t->exec("tr069-mgmt 1 state unlock");
            $t->exec("tr069-mgmt 1 acs http://acsamd.dataartasedaya.net.id:7547 validate basic username acs password acsadmin12345");
            $t->exec("tr069-mgmt 1 tag pri 4 vlan 100");
            $t->exec("exit"); $t->exec("end"); $t->exec("write"); $t->disconnect();
            $this->json(['status'=>'success', 'message'=>"Push Config TR069 Sukses!"]);
        } catch (Exception $e) { $this->json(['status'=>'error', 'message'=>$e->getMessage()]); }
    }

    private function do_action($cmd, $hit_run) {
        ob_start();
        try {
            $id=$_POST['olt_id']; $iface=$_POST['interface'];
            $olt=$this->conn->query("SELECT * FROM olts WHERE id='$id'")->fetch();
            $t=new TelnetPro($olt['ip_address'],$olt['telnet_port']); $t->login($olt['username'],$olt['password']);
            $t->exec("conf t"); 
            $res = $t->exec("pon-onu-mng $iface");
            if(strpos($res, 'Error')!==false) { $short=str_replace('gpon-onu_','',$iface); $t->exec("pon-onu-mng $short"); }
            if($hit_run){ try{$t->exec($cmd);}catch(Exception $e){} } else { $t->exec($cmd); $t->exec("yes"); }
            $t->disconnect();
            $this->json(['status'=>'success', 'message'=>"Perintah $cmd Berhasil!"]);
        } catch(Exception $e) { $this->json(['status'=>'error', 'message'=>$e->getMessage()]); }
    }
}
?>
