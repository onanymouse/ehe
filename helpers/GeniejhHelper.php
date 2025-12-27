<?php
class GenieHelper {
    private $api_url;
    private $ui_user;
    private $ui_pass;
    public $last_error = '';

    public function __construct($server_data) {
        // Hapus spasi dan slash akhir (Jaga-jaga)
        $this->api_url = rtrim(str_replace(' ', '', $server_data['url']), '/');
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

    // FUNGSI LIST DENGAN LOGIKA SUKSES
    public function getDevicesServerSide($search = '', $skip = 0, $limit = 10) {
        
        // 1. PROJECTION (Kolom Data)
        // PENTING: Jangan di-encode! Biarkan koma sebagai koma.
        $proj = "_id,summary.serialNumber,summary.ip,summary.productClass,_lastInform"; 
        
        // 2. QUERY PARAMETER
        $params = "projection=" . $proj . "&skip=" . $skip . "&limit=" . $limit . "&sort=-_lastInform";

        // 3. LOGIKA PENCARIAN
        // Hanya tambahkan &query=... jika ada search
        if (!empty($search)) {
            // Encode keyword pencarian saja
            $k = rawurlencode($search);
            // Format JSON Hardcoded (Tanpa Spasi)
            $json_str = '%7B%22_id%22%3A%7B%22%24regex%22%3A%22' . $k . '%22%7D%7D';
            
            $params .= "&query=" . $json_str;
        }

        $full_url = $this->api_url . "/devices/?" . $params;

        return $this->request('GET', $full_url);
    }
    
    // FUNGSI DETAIL (Kita perbaiki projection-nya juga)
    public function getDeviceParams($deviceId) {
        // Daftar parameter yang mau diambil (Tanpa Spasi, Pisah Koma)
        $fields = [
            "InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress",
            "InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID",
            "InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.PreSharedKey",
            "InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.KeyPassphrase",
            "InternetGatewayDevice.WANDevice.1.WANOpticalInterfaceConfig.OpticalRxPower",
            "Device.OpticalInterface.1.OpticalRxPower",
            "InternetGatewayDevice.DeviceInfo.UpTime"
        ];
        
        $proj = implode(',', $fields);
        // KITA ENCODE PROJECTION KHUSUS DETAIL KARENA PANJANG & ADA TITIK
        // Tapi kita pakai rawurlencode biar aman
        $proj_enc = rawurlencode($proj);
        
        $url = $this->api_url . "/devices/$deviceId/?projection=" . $proj_enc;
        return $this->request('GET', $url);
    }

    public function reboot($deviceId) {
        $url = $this->api_url . "/devices/$deviceId/tasks?timeout=3000&connection_request";
        return $this->request('POST', $url, ['name' => 'reboot']);
    }
}
?>
