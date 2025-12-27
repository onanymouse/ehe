<?php
class GenieHelper {
    private $api_url;
    private $ui_user;
    private $ui_pass;
    public $last_error = '';

    public function __construct($server_data) {
        $this->api_url = rtrim(trim($server_data['url']), '/');
        $this->ui_user = trim($server_data['username']);
        $this->ui_pass = trim($server_data['password']);
    }

    private function request($method, $url_full, $data = null) {
        $ch = curl_init($url_full);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->ui_user:$this->ui_pass");
        curl_setopt($ch, CURLOPT_TIMEOUT, 20); 
        curl_setopt($ch, CURLOPT_FAILONERROR, false);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $this->last_error = "CURL Error: $curl_err";
            return null;
        }

        if ($http_code >= 200 && $http_code < 300) {
            return json_decode($response, true);
        } else {
            $this->last_error = "HTTP $http_code: " . strip_tags(substr($response, 0, 150)); 
            return null;
        }
    }

    public function getDevicesServerSide($search = '', $skip = 0, $limit = 10) {
        
        // --- JURUS PAKSA: ABAIKAN SEARCH ---
        // Kita tidak peduli user ngetik apa, kita kirim URL Polos (TEST 1)
        // Tujuannya agar data PASTI MUNCUL dulu.
        
        // Projection: Ambil kolom wajib (URL Encoded Manual)
        // summary.serialNumber, summary.ip, summary.productClass, _lastInform
        $proj = "summary.serialNumber%2Csummary.ip%2Csummary.productClass%2C_lastInform"; 
        
        // PARAMETER TANPA QUERY
        // Persis seperti Test 1: projection, skip, limit, sort
        $params = "projection=" . $proj . "&skip=" . $skip . "&limit=" . $limit . "&sort=-_lastInform";
        
        $full_url = $this->api_url . "/devices/?" . $params;

        return $this->request('GET', $full_url);
    }
    
    // ... (Fungsi Lain Tetap) ...
    public function getDeviceParams($deviceId) {
        $proj = "InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress%2CInternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID%2CInternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.PreSharedKey%2CInternetGatewayDevice.LANDevice.1.WLANConfiguration.1.KeyPassphrase%2CInternetGatewayDevice.WANDevice.1.WANOpticalInterfaceConfig.OpticalRxPower%2CDevice.OpticalInterface.1.OpticalRxPower%2CInternetGatewayDevice.DeviceInfo.UpTime";
        $url = $this->api_url . "/devices/$deviceId/?projection=" . $proj;
        return $this->request('GET', $url);
    }

    public function reboot($deviceId) {
        $url = $this->api_url . "/devices/$deviceId/tasks?timeout=3000&connection_request";
        return $this->request('POST', $url, ['name' => 'reboot']);
    }
}
?>
