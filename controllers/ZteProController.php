<?php
// FILE: controllers/ZteProController.php
require_once 'helpers/TelnetPro.php';

// Cek Library Mikrotik
if(file_exists('helpers/routeros_api.class.php')) {
    require_once 'helpers/routeros_api.class.php';
} elseif(file_exists('libs/routeros_api.class.php')) {
    require_once 'libs/routeros_api.class.php';
}

class ZteProController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    private function send_json($data) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function index() {
        $olts = $this->conn->query("SELECT * FROM olts ORDER BY name ASC")->fetchAll();
        require_once 'views/zte_pro/index.php';
    }

    // =========================================================
    // 1. LIST MODEM (VERSI "JARING IKAN" - ANTI KOSONG)
    // =========================================================
    public function get_active_onus() {
        ob_start();
        error_reporting(0); // Matikan error agar JSON bersih
        set_time_limit(60);
        
        $id = $_POST['id'];
        $pon_interface = $_POST['pon_interface']; 

        $olt = $this->conn->query("SELECT * FROM olts WHERE id='$id'")->fetch();
        
        try {
            $t = new TelnetPro($olt['ip_address'] ?? $olt['host'], $olt['telnet_port'] ?? 23);
            $t->login($olt['username'], $olt['password']);
            
            // AMBIL STATUS & POWER
            $raw_state = $t->exec("show gpon onu state $pon_interface");
            $raw_power = $t->exec("show pon power attenuation $pon_interface");
            
            $t->disconnect();

            $onus = [];

            // A. PARSING STATUS (CARI POLA INTERFACE DI BARIS MANAPUN)
            $lines = explode("\n", $raw_state);
            foreach($lines as $line) {
                // Bersihkan karakter aneh
                $line = trim(preg_replace('/[\x00-\x1F\x7F]/', ' ', $line));
                if(empty($line)) continue;

                // Cari pola angka: 1/2/1:1 (Tidak peduli ada gpon-onu_ atau tidak)
                if(preg_match('/(\d+\/\d+\/\d+:\d+)/', $line, $m)) {
                    $short_iface = $m[1]; // 1/1/1:1
                    $full_iface = "gpon-onu_" . $short_iface;
                    
                    // Cari Status (Kata Kunci)
                    $line_lower = strtolower($line);
                    $st = 'offline'; 
                    
                    if(strpos($line_lower, 'working')!==false) $st = 'working';
                    elseif(strpos($line_lower, 'oper')!==false) $st = 'working';
                    elseif(strpos($line_lower, 'online')!==false) $st = 'working';
                    elseif(strpos($line_lower, 'los')!==false) $st = 'los';
                    elseif(strpos($line_lower, 'dying')!==false) $st = 'power-fail';
                    
                    $onus[$short_iface] = [
                        'interface' => $full_iface,
                        'state' => $st,
                        'rx' => '-'
                    ];
                }
            }

            // B. PARSING SINYAL
            $lines_p = explode("\n", $raw_power);
            foreach($lines_p as $line) {
                // Cari angka Interface dan nilai Rx di baris yang sama
                if(preg_match('/(\d+\/\d+\/\d+:\d+)/', $line, $m_if) && preg_match('/Rx\s*[:]\s*(-?[\d\.]+)/i', $line, $m_rx)) {
                    $k = $m_if[1];
                    if(isset($onus[$k])) {
                        $onus[$k]['rx'] = $m_rx[1];
                    }
                }
            }

            // C. JIKA MASIH KOSONG -> DEBUG MODE
            if(empty($onus)) {
                $this->send_json([
                    'status' => 'success', 
                    'data' => [], 
                    'debug_raw' => substr($raw_state, 0, 1000) // Kirim sample teks asli
                ]);
            } else {
                // D. FORMAT OUTPUT FINAL
                $data = [];
                foreach($onus as $onu) {
                    $st = $onu['state'];
                    // Warna
                    $bg = 'secondary';
                    if($st=='working') $bg = 'success';
                    if($st=='los') $bg = 'danger';
                    if($st=='power-fail') $bg = 'warning';

                    $rx = $onu['rx'];
                    $cls_rx = 'text-muted';
                    if($rx != '-') {
                        $val = (float)$rx;
                        $cls_rx = ($val < -25) ? 'text-danger font-weight-bold' : 'text-success font-weight-bold';
                        $rx .= " dBm";
                    }

                    $real_iface = $onu['interface'];
                    
                    // Tombol Aksi Lengkap
                    $btn = "<div class='btn-group'>";
                    $btn .= "<button class='btn btn-info btn-xs' onclick='cekDetail(\"$real_iface\", \"$id\")' title='Lihat Detail'><i class='fas fa-info-circle'></i></button>";
                    $btn .= "<button class='btn btn-warning btn-xs' onclick='rebootOnu(\"$real_iface\", \"$id\")' title='Reboot'><i class='fas fa-sync'></i></button>";
                    $btn .= "<button class='btn btn-danger btn-xs' onclick='resetOnu(\"$real_iface\", \"$id\")' title='Reset'><i class='fas fa-history'></i></button>";
                    $btn .= "<button class='btn btn-dark btn-xs' onclick='deleteOnu(\"$real_iface\", \"$id\")' title='Hapus'><i class='fas fa-trash'></i></button>";
                    $btn .= "</div>";

                    $data[] = [
                        'interface' => $real_iface,
                        'status'    => "<span class='badge badge-$bg'>".strtoupper($st)."</span>",
                        'signal'    => "<span class='$cls_rx'>$rx</span>",
                        'action'    => $btn
                    ];
                }
                $this->send_json(['status'=>'success', 'data'=>array_values($data)]);
            }

        } catch (Exception $e) { $this->send_json(['status'=>'error', 'message'=>$e->getMessage()]); }
    }

    // =========================================================
    // 2. CEK DETAIL POPUP (NAMA, SN, DLL)
    // =========================================================
    public function check_signal() {
        ob_start();
        $olt_id = $_POST['olt_id'];
        $interface = $_POST['interface']; 
        $olt = $this->conn->query("SELECT * FROM olts WHERE id='$olt_id'")->fetch();
        
        try {
            $t = new TelnetPro($olt['ip_address'] ?? $olt['host'], $olt['telnet_port'] ?? 23);
            $t->login($olt['username'], $olt['password']);
            
            $detail_raw = $t->exec("show gpon onu detail-info $interface");
            $power_raw = $t->exec("show pon power attenuation $interface");
            $t->disconnect();

            $info = ['name'=>'-', 'desc'=>'-', 'sn'=>'-', 'type'=>'-', 'rx'=>'N/A', 'tx'=>'N/A'];

            // Regex Flexible (Name: atau Name= atau Name)
            if(preg_match('/Name\s*[:=]\s*(.*)/i', $detail_raw, $m)) $info['name'] = trim($m[1]);
            if(preg_match('/Description\s*[:=]\s*(.*)/i', $detail_raw, $m)) $info['desc'] = trim($m[1]);
            if(preg_match('/Serial\s*number\s*[:=]\s*(.*)/i', $detail_raw, $m)) $info['sn'] = trim($m[1]);
            if(preg_match('/Type\s*[:=]\s*(.*)/i', $detail_raw, $m)) $info['type'] = trim($m[1]);
            
            // Sinyal
            if(preg_match('/Rx\s*[:]\s*(-?[\d\.]+)/i', $power_raw, $m)) $info['rx'] = $m[1];
            if(preg_match('/Tx\s*[:]\s*(-?[\d\.]+)/i', $power_raw, $m)) $info['tx'] = $m[1];

            $this->send_json(['status'=>'success', 'data'=>$info]);

        } catch (Exception $e) { $this->send_json(['status'=>'error', 'message'=>$e->getMessage()]); }
    }
    
    // =========================================================
    // 4. FUNGSI BARU: PUSH TR069
    // =========================================================
    public function push_tr069() {
        ob_start();
        set_time_limit(60); // Script agak panjang
        
        $olt_id = $_POST['olt_id'];
        $interface = $_POST['interface']; // gpon-onu_1/1/1:15
        
        $olt = $this->conn->query("SELECT * FROM olts WHERE id='$olt_id'")->fetch();
        if(!$olt) $this->send_json(['status'=>'error', 'message'=>'OLT Not Found']);

        try {
            $t = new TelnetPro($olt['ip_address'] ?? $olt['host'], $olt['telnet_port'] ?? 23);
            $t->login($olt['username'], $olt['password']);
            
            // Masuk Mode Config
            $t->exec("conf t");
            
            // 1. Config Interface Mode (TCONT, GEMPORT)
            $res = $t->exec("interface $interface");
            if(strpos($res, 'Error') !== false) throw new Exception("Gagal masuk interface $interface");
            
            $t->exec("tcont 4 profile 1000M");
            $t->exec("gemport 4 tcont 4");
            $t->exec("gemport 4 traffic-limit upstream UP1000M downstream DW1000M");
            $t->exec("service-port 4 vport 4 user-vlan 100 vlan 100");
            $t->exec("exit");

            // 2. Config ONU Manager Mode (TR069 Service)
            $t->exec("pon-onu-mng $interface");
            $t->exec("service TR069 gemport 4 vlan 100"); // Typo di request bapak TR609 atau TR069? Saya ikuti script bapak TR609
            $t->exec("tr069-mgmt 1 state unlock");
            $t->exec("tr069-mgmt 1 acs http://24.24.12.10:7547 validate basic username acs password acs123");
            $t->exec("tr069-mgmt 1 tag pri 4 vlan 100");
            $t->exec("exit");

            // 3. Save
            $t->exec("end");
            $t->exec("write");
            
            $t->disconnect();
            
            $this->send_json(['status'=>'success', 'message'=>"Sukses Push TR069 ke $interface"]);

        } catch (Exception $e) {
            $this->send_json(['status'=>'error', 'message'=>$e->getMessage()]);
        }
    }

    // =========================================================
    // 3. FUNGSI PENDUKUNG (Register, Router, Config)
    // =========================================================

    // Scan Modem Baru
    public function scan_uncfg() {
        ob_start();
        $id = $_POST['id'];
        $olt = $this->conn->query("SELECT * FROM olts WHERE id='$id'")->fetch();
        try {
            $t = new TelnetPro($olt['ip_address'] ?? $olt['host'], $olt['telnet_port'] ?? 23, 5); 
            $t->login($olt['username'], $olt['password']);
            $raw = $t->exec("show gpon onu uncfg");
            $t->disconnect();
            $data = [];
            foreach(explode("\n", $raw) as $line) {
                if(strpos($line, 'gpon-onu') !== false) {
                    $parts = preg_split('/\s+/', trim($line));
                    if(count($parts) >= 2) {
                        $data[] = ['interface'=>$parts[0], 'sn'=>$parts[1], 
                        'action'=>"<button class='btn btn-success btn-sm' onclick='prepareRegister(\"{$parts[1]}\", \"{$parts[0]}\", \"$id\")'>REG</button>"];
                    }
                }
            }
            $this->send_json(['status'=>'success', 'data'=>$data]);
        } catch (Exception $e) { $this->send_json(['status'=>'error', 'message'=>$e->getMessage()]); }
    }

    // Config Register
    public function register() {
        ob_start(); set_time_limit(60);
        $olt_id = $_POST['olt_id']; $new_id = $_POST['next_id']; $pon_master = $_POST['pon_master']; 
        $sn = $_POST['sn']; $name = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['nama']); $desc = $_POST['desc'];
        $vlan_hsi = $_POST['vlan_hsi']; $vlan_hot = $_POST['vlan_hot']; $onu_type = $_POST['onu_type'];
        $user_ppp = $_POST['user_pppoe'] ?? ''; $pass_ppp = $_POST['pass_pppoe'] ?? '';
        $create_mikrotik = isset($_POST['create_mikrotik']) && $_POST['create_mikrotik'] == '1';

        try {
            if($create_mikrotik) {
                $router_id = $_POST['router_id']; $profile = $_POST['profile_pppoe'];
                $r = $this->conn->query("SELECT * FROM routers WHERE id='$router_id'")->fetch();
                $API = new RouterosAPI(); $API->port = !empty($r['port']) ? (int)$r['port'] : 8728;
                if ($API->connect($r['ip_address'], $r['username'], $r['password'])) {
                    $cek = $API->comm("/ppp/secret/print", ["?name" => $user_ppp]);
                    if(is_array($cek) && count($cek) > 0 && isset($cek[0]['name'])) { $API->disconnect(); throw new Exception("User Mikrotik Ada!"); }
                    $API->comm("/ppp/secret/add", ["name"=>$user_ppp, "password"=>$pass_ppp, "profile"=>$profile, "service"=>"pppoe", "comment"=>"$onu_type $name"]);
                    $API->disconnect();
                } else { throw new Exception("Login Mikrotik Gagal"); }
            }

            $olt = $this->conn->query("SELECT * FROM olts WHERE id='$olt_id'")->fetch();
            $t = new TelnetPro($olt['ip_address'] ?? $olt['host'], $olt['telnet_port'] ?? 23);
            $t->login($olt['username'], $olt['password']);
            $pon_onu_full = str_replace('olt', 'onu', $pon_master) . ":" . $new_id;
            
            $check = $t->exec("show gpon onu detail-info $pon_onu_full");
            if(strpos($check, 'Type:')!==false || strpos($check, 'SN:')!==false) { $t->disconnect(); throw new Exception("ID Terisi!"); }

            $cmds = ["conf t"];
            if ($onu_type == 'FIBERHOME') {
                $cmds[] = "interface $pon_master"; $cmds[] = "onu $new_id type ALL sn $sn"; $cmds[] = "exit";
                $cmds[] = "interface $pon_onu_full"; $cmds[] = "name $name"; $cmds[] = "description $desc";
                $cmds[] = "tcont 1 name TR069 profile 1000M"; $cmds[] = "gemport 1 tcont 1"; $cmds[] = "gemport 1 traffic-limit downstream DW1000M";
                $cmds[] = "service-port 1 vport 1 user-vlan $vlan_hsi vlan $vlan_hsi"; $cmds[] = "service-port 12 vport 1 user-vlan $vlan_hot vlan $vlan_hot"; 
                $cmds[] = "exit";
                $cmds[] = "pon-onu-mng $pon_onu_full"; $cmds[] = "service TR069 gemport 1 vlan $vlan_hsi"; $cmds[] = "service PPPoE gemport 1 vlan $vlan_hot";
                $cmds[] = "voip protocol sip"; $cmds[] = "vlan port veip_1 mode hybrid def-vlan $vlan_hsi"; $cmds[] = "vlan port veip_1 vlan $vlan_hsi";
                $cmds[] = "switchport-bind switch_0/1 iphost 1"; $cmds[] = "switchport-bind switch_0/1 iphost 2";
                $cmds[] = "ip-host 2 dhcp-enable enable ping-response enable traceroute-response enable";
                $cmds[] = "vlan-filter-mode iphost 1 tag-filter vlan-filter untag-filter discard"; $cmds[] = "vlan-filter-mode iphost 2 tag-filter vlan-filter untag-filter discard";
                $cmds[] = "vlan-filter iphost 2 pri 2 vlan $vlan_hsi"; $cmds[] = "veip 1 port udp 1232 host 2";
                $cmds[] = "tr069-mgmt 1 state unlock"; $cmds[] = "tr069-mgmt 1 acs http://24.24.12.10:7547 validate basic username acs password acs123";
                $cmds[] = "tr069-mgmt 1 tag pri 2 vlan $vlan_hsi"; $cmds[] = "exit";
            } else {
                $cmds[] = "interface $pon_master"; $cmds[] = "onu $new_id type ZTE-F609 sn $sn"; $cmds[] = "exit";
                $cmds[] = "interface $pon_onu_full"; $cmds[] = "name $name"; $cmds[] = "description $desc";
                $cmds[] = "tcont 1 profile 1000M"; $cmds[] = "tcont 2 profile 1000M";
                $cmds[] = "gemport 1 tcont 1"; $cmds[] = "gemport 1 traffic-limit upstream UP1000M downstream DW1000M";
                $cmds[] = "gemport 2 tcont 2"; $cmds[] = "gemport 2 traffic-limit upstream UP1000M downstream DW1000M";
                $cmds[] = "service-port 1 vport 1 user-vlan $vlan_hsi vlan $vlan_hsi"; $cmds[] = "service-port 2 vport 2 user-vlan $vlan_hot vlan $vlan_hot";
                $cmds[] = "exit";
                $cmds[] = "pon-onu-mng $pon_onu_full"; $cmds[] = "service HSI gemport 1 vlan $vlan_hsi"; $cmds[] = "service HOTSPOT gemport 2 vlan $vlan_hot";
                $cmds[] = "wan-ip 1 mode pppoe username $user_ppp password $pass_ppp vlan-profile PPPoE host 1";
                $cmds[] = "wan 1 service internet host 1";
                $cmds[] = "security-mgmt 212 state enable mode forward ingress-type wan protocol web";
                $cmds[] = "exit";
            }
            $cmds[] = "end"; $cmds[] = "write";
            foreach($cmds as $cmd) { $t->exec($cmd); }
            $t->disconnect();
            $this->send_json(['status'=>'success', 'message'=>"Config $onu_type Sukses!"]);
        } catch (Exception $e) { $this->send_json(['status'=>'error', 'message'=>$e->getMessage()]); }
    }

    // --- AKSI (REBOOT/RESET/DELETE) ---
    public function reboot_onu() { ob_start(); $this->do_action("reboot", false); }
    public function reset_onu() { ob_start(); $this->do_action("restore factory", true); }
    public function delete_onu() {
        ob_start();
        try {
            $olt_id = $_POST['olt_id']; $interface = $_POST['interface'];
            $olt = $this->conn->query("SELECT * FROM olts WHERE id='$olt_id'")->fetch();
            $parts = explode(':', $interface); $pon_iface = str_replace('onu', 'olt', $parts[0]); $onu_id = $parts[1]; 
            $t = new TelnetPro($olt['ip_address'], $olt['telnet_port']);
            $t->login($olt['username'], $olt['password']);
            $t->exec("conf t"); $t->exec("interface $pon_iface"); $t->exec("no onu $onu_id");
            $t->exec("exit"); $t->exec("exit"); 
            $t->disconnect();
            $this->conn->prepare("DELETE FROM olt_onus WHERE interface = ? AND olt_id = ?")->execute([$interface, $olt_id]);
            $this->send_json(['status'=>'success', 'message'=>"Terhapus!"]);
        } catch(Exception $e) { $this->send_json(['status'=>'error', 'message'=>$e->getMessage()]); }
    }
    
    private function do_action($cmd, $hit_run) {
        try {
            $olt_id = $_POST['olt_id']; $interface = $_POST['interface'];
            $olt = $this->conn->query("SELECT * FROM olts WHERE id='$olt_id'")->fetch();
            $t = new TelnetPro($olt['ip_address'], $olt['telnet_port']);
            $t->login($olt['username'], $olt['password']);
            $t->exec("conf t"); 
            
            // Auto Fallback Interface
            $res = $t->exec("pon-onu-mng $interface");
            if(strpos($res, 'Error')!==false) {
                $short = str_replace('gpon-onu_', '', $interface);
                $t->exec("pon-onu-mng $short");
            }

            if($hit_run) { try{$t->exec($cmd);}catch(Exception $e){} } else { $t->exec($cmd); $t->exec("yes"); }
            $t->disconnect();
            $this->send_json(['status'=>'success', 'message'=>"Perintah terkirim!"]);
        } catch(Exception $e) { $this->send_json(['status'=>'error', 'message'=>$e->getMessage()]); }
    }

    // --- Helper (Router, Profile, Slot) ---
    public function get_routers() {
        ob_start();
        $routers = $this->conn->query("SELECT id, name, ip_address FROM routers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $this->send_json(['status'=>'success', 'data'=>$routers]);
    }

    public function get_profiles() {
        ob_start();
        if(file_exists('helpers/routeros_api.class.php')) require_once 'helpers/routeros_api.class.php';
        try {
            $rid = $_POST['router_id'];
            $r = $this->conn->query("SELECT * FROM routers WHERE id='$rid'")->fetch();
            $API = new RouterosAPI();
            $API->port = !empty($r['port']) ? (int)$r['port'] : 8728; 
            if ($API->connect($r['ip_address'], $r['username'], $r['password'])) {
                $profiles = $API->comm("/ppp/profile/print");
                $API->disconnect();
                $data = [];
                foreach($profiles as $p) { if(isset($p['name'])) $data[] = ['name' => $p['name']]; }
                $this->send_json(['status'=>'success', 'data'=>$data]);
            } else { throw new Exception("Gagal Login Mikrotik"); }
        } catch (Exception $e) { $this->send_json(['status'=>'error', 'message'=>$e->getMessage()]); }
    }

    public function get_pon_details() {
        ob_start();
        try {
            $olt_id = $_POST['olt_id']; $interface = $_POST['interface'];
            $olt = $this->conn->query("SELECT * FROM olts WHERE id='$olt_id'")->fetch();
            $tmp = explode(':', $interface); $pon_onu_base = $tmp[0]; 
            $pon_olt_master = str_replace('onu', 'olt', $pon_onu_base); 
            $t = new TelnetPro($olt['ip_address'] ?? $olt['host'], $olt['telnet_port'] ?? 23);
            $t->login($olt['username'], $olt['password']);
            $state = $t->exec("show gpon onu state $pon_olt_master");
            $t->disconnect();
            $used_ids = [];
            foreach(explode("\n", $state) as $l) { if(preg_match('/\:(\d+)\s+/', $l, $m)) $used_ids[] = intval($m[1]); }
            $next_id = 0; for($i=1; $i<=128; $i++) { if(!in_array($i, $used_ids)) { $next_id = $i; break; } }
            if($next_id == 0) throw new Exception("Port Penuh!");
            $this->send_json(['status'=>'success', 'pon_master'=>$pon_olt_master, 'next_interface'=>$pon_onu_base.":".$next_id, 'next_id'=>$next_id]);
        } catch (Exception $e) { $this->send_json(['status'=>'error', 'message'=>$e->getMessage()]); }
    }
    
    // --- Get Interfaces (DB + Show Card) ---
    public function get_pon_interfaces() {
        ob_start(); $id = $_POST['id'];
        $sql = "SELECT DISTINCT SUBSTRING_INDEX(interface, ':', 1) as pon FROM olt_onus WHERE olt_id = '$id' ORDER BY id ASC";
        $rows = $this->conn->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        $pons = [];
        if(count($rows)>0) { foreach($rows as $r) { $pon = str_replace('onu', 'olt', $r); if(strpos($pon, 'olt')!==false) $pons[$pon]=1; } }
        if(empty($pons)) {
            $olt=$this->conn->query("SELECT * FROM olts WHERE id='$id'")->fetch();
            try { $t=new TelnetPro($olt['ip_address'],$olt['telnet_port'],5); $t->login($olt['username'],$olt['password']); $raw=$t->exec("show card"); $t->disconnect();
                foreach(explode("\n",$raw) as $line){ if(preg_match('/^\s*(\d+)\s+(\d+)\s+(\d+)\s+\w+\s+(\w+)\s+(\d+)/',trim($line),$m)){ $ports=(int)$m[5]; if($ports>0&&strpos($m[4],'GT')===0) for($i=1;$i<=$ports;$i++) $pons["gpon-olt_{$m[1]}/{$m[2]}/{$m[3]}:{$i}"]=1; } }
            } catch(Exception $e){}
        }
        $result=array_keys($pons); natsort($result);
        $this->send_json(['status'=>'success','data'=>array_values($result)]);
    }
}
?>