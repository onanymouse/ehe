<?php
// FILE: controllers/AcsController.php
// VERSI: FINAL FIX (EJAAN & FORMAT KOMA)

class AcsController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function index() {
        if($_SESSION['role'] != 'admin') { header("Location: index.php"); exit; }
        $servers = $this->conn->query("SELECT * FROM acs_servers ORDER BY name ASC")->fetchAll();
        require_once 'views/acs/index.php';
    }

    public function manage_servers() {
        if($_SESSION['role'] != 'admin') { header("Location: index.php"); exit; }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = trim($_POST['name']); 
            $url = rtrim(str_replace(' ', '', $_POST['url']), '/');
            $user = trim($_POST['user']); $pass = trim($_POST['pass']);
            
            if(empty($name) || empty($url)) { echo "<script>alert('Lengkapi Data'); history.back();</script>"; exit; }
            
            $this->conn->prepare("INSERT INTO acs_servers (name, url, username, password) VALUES (?, ?, ?, ?)")->execute([$name, $url, $user, $pass]);
            echo "<script>alert('Server Berhasil Disimpan'); window.location='index.php?page=acs&action=index';</script>"; exit;
        }
    }

    // --- AJAX LIST ---
    public function ajax_list() {
        ini_set('display_errors', 0); error_reporting(0); while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        $server_id = isset($_POST['server_id']) ? $_POST['server_id'] : '';
        if(empty($server_id)) { echo json_encode(['draw'=>0, 'recordsTotal'=>0, 'recordsFiltered'=>0, 'data'=>[]]); exit; }

        $srv = $this->conn->query("SELECT * FROM acs_servers WHERE id='$server_id'")->fetch();
        if(!$srv) { echo json_encode(['error'=>'Server DB Not Found']); exit; }

        $api_url = rtrim(str_replace(' ', '', $srv['url']), '/');
        $user = $srv['username'];
        $pass = $srv['password'];

        // 1. PROJECTION (KOLOM DATA)
        // PERBAIKAN: Gunakan KOMA BIASA (,) jangan di-encode.
        // PERBAIKAN: _lastInform (Huruf L kecil, I besar).
        $proj = "_id,summary.serialNumber,summary.ip,summary.productClass,_lastInform"; 

        // 2. QUERY SEARCH (HANYA JIKA ADA SEARCH)
        $query_str = ""; 
        if (!empty($_POST['search']['value'])) {
            $k = rawurlencode($_POST['search']['value']);
            // Tambahkan &query=...
            $query_str = '&query=%7B%22_id%22%3A%7B%22%24regex%22%3A%22' . $k . '%22%7D%7D';
        } else {
             // JIKA KOSONG: KITA KIRIM QUERY "AMBIL SEMUA" (Sesuai Debug Test 4)
             // %7B%22_id%22%3A%7B%22%24exists%22%3Atrue%7D%7D
             $query_str = '&query=%7B%22_id%22%3A%7B%22%24exists%22%3Atrue%7D%7D';
        }

        // 3. RAKIT URL
        // PERBAIKAN: sort=-_lastInform (Huruf L kecil, I besar).
        // Struktur: /devices/?projection=...&limit=10&sort=...&query=...
        $target_url = $api_url . "/devices/?projection=" . $proj . "&limit=10&sort=-_lastInform" . $query_str;

        // --- CURL ---
        $ch = curl_init($target_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if($response === false) {
             echo json_encode(['draw'=>1, 'recordsTotal'=>0, 'recordsFiltered'=>0, 'data'=>[], 'error'=>"CURL Error: $err"]); exit;
        }

        if($http_code != 200) {
             // Tampilkan URL untuk debugging
             $msg = "HTTP $http_code. URL: $target_url";
             echo json_encode(['draw'=>1, 'recordsTotal'=>0, 'recordsFiltered'=>0, 'data'=>[], 'error'=>$msg]); exit;
        }

        $devices = json_decode($response, true);
        if(!$devices) {
             echo json_encode(['draw'=>1, 'recordsTotal'=>0, 'recordsFiltered'=>0, 'data'=>[], 'error'=>"Gagal Decode JSON."]); exit;
        }

        // --- FORMAT DATA ---
        $data = [];
        foreach($devices as $d) {
            $id_asli = $d['_id'] ?? '-';
            
            // SN
            $sn = '-';
            if(isset($d['summary']['serialNumber'])) $sn = $d['summary']['serialNumber'];
            elseif(isset($d['_id'])) $sn = $d['_id'];

            // IP & Model
            $ip = $d['summary']['ip'] ?? '-';
            $model = $d['summary']['productClass'] ?? 'Unknown';
            
            // Last Seen
            $last = '-';
            if(isset($d['_lastInform'])) {
                $time = strtotime($d['_lastInform']);
                $diff = time() - $time;
                
                if ($diff < 600) $badge = 'success';
                elseif ($diff < 3600) $badge = 'warning';
                else $badge = 'secondary';
                
                $last = "<span class='badge badge-$badge'>" . date('d M H:i', $time) . "</span>";
            }

            $col1 = "<b>$sn</b><br><small class='text-muted'>$model</small>";
            $col2 = "<b>$ip</b><br>$last";
            $col3 = "<button class='btn btn-sm btn-info' onclick='detailDevice(\"$id_asli\", \"$server_id\")'><i class='fas fa-search'></i> Detail</button>";

            $data[] = [$col1, $col2, $col3];
        }

        echo json_encode([
            "draw" => intval($_POST['draw'] ?? 1),
            "recordsTotal" => 10000, 
            "recordsFiltered" => 10000,
            "data" => $data
        ]);
        exit;
    }

    // --- FUNGSI DETAIL ---
    public function detail() {
        ini_set('display_errors', 0); error_reporting(0); while (ob_get_level()) ob_end_clean(); header('Content-Type: application/json');
        
        $server_id = $_GET['server_id'];
        $device_id = $_GET['id'];

        $srv = $this->conn->query("SELECT * FROM acs_servers WHERE id='$server_id'")->fetch();
        $api_url = rtrim(str_replace(' ', '', $srv['url']), '/');
        $user = $srv['username'];
        $pass = $srv['password'];

        // Detail Projection (Raw Encode)
        $proj = "InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress,InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID,InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.PreSharedKey,InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.KeyPassphrase,InternetGatewayDevice.WANDevice.1.WANOpticalInterfaceConfig.OpticalRxPower,Device.OpticalInterface.1.OpticalRxPower,InternetGatewayDevice.DeviceInfo.UpTime";
        $proj_enc = rawurlencode($proj);

        $target_url = $api_url . "/devices/" . rawurlencode($device_id) . "/?projection=" . $proj_enc;

        $ch = curl_init($target_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $res = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if(!$res || !isset($res[0])) { 
            echo json_encode(['status'=>'error', 'message'=>'Gagal ambil detail.']); exit; 
        }

        $p = $res[0];
        $clean = [
            'ssid' => $p['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][1]['SSID']['_value'] ?? '-',
            'wifi_pass' => $p['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][1]['PreSharedKey'][1]['PreSharedKey']['_value'] ?? ($p['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][1]['KeyPassphrase']['_value'] ?? '******'),
            'rx_power' => $p['InternetGatewayDevice']['WANDevice'][1]['WANOpticalInterfaceConfig']['OpticalRxPower']['_value'] ?? ($p['Device']['OpticalInterface'][1]['OpticalRxPower']['_value'] ?? 0),
            'uptime' => floor(($p['InternetGatewayDevice']['DeviceInfo']['UpTime']['_value'] ?? 0) / 3600) . " Jam"
        ];
        
        echo json_encode(['status'=>'success', 'data'=>$clean]);
        exit;
    }

    public function reboot() {
        ini_set('display_errors', 0); error_reporting(0); while (ob_get_level()) ob_end_clean(); header('Content-Type: application/json');
        
        $server_id = $_POST['server_id'];
        $device_id = $_POST['id'];
        
        $srv = $this->conn->query("SELECT * FROM acs_servers WHERE id='$server_id'")->fetch();
        $api_url = rtrim(str_replace(' ', '', $srv['url']), '/');
        
        $target_url = $api_url . "/devices/" . rawurlencode($_POST['id']) . "/tasks?timeout=3000&connection_request";
        
        $ch = curl_init($target_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['name' => 'reboot']));
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "{$srv['username']}:{$srv['password']}");
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if(curl_exec($ch) && $http_code < 300) echo json_encode(['status'=>'success']);
        else echo json_encode(['status'=>'error']);
        exit;
    }
}
?>
