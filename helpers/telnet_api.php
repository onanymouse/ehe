<?php
class TelnetAPI {
    private $socket = NULL;
    private $timeout = 10;

    public function connect($host, $user, $pass, $port = 23) {
        $this->socket = @fsockopen($host, $port, $this->errno, $this->errstr, $this->timeout);
        
        if (!$this->socket) return false;

        // Set Timeout
        stream_set_timeout($this->socket, $this->timeout);

        // --- LOGIKA LOGIN SEDERHANA (MIRIP TESTER) ---
        
        // 1. Baca Banner Awal (Tunggu 1 detik)
        sleep(1);
        $this->read_buffer();

        // 2. Kirim Username
        fwrite($this->socket, "$user\r\n");
        sleep(1); // Jeda wajib untuk ZTE
        $this->read_buffer(); // Bersihkan buffer

        // 3. Kirim Password
        fwrite($this->socket, "$pass\r\n");
        sleep(2); // Jeda agak lama setelah password
        
        // 4. Cek Hasil Login
        $output = $this->read_buffer();
        
        // Jika ada tanda '>' atau '#', berarti sukses login
        if (strpos($output, '>') !== false || strpos($output, '#') !== false) {
            
            // Masuk Mode Enable & Matikan Paging (Agar output panjang tidak terpotong)
            fwrite($this->socket, "terminal length 0\r\n");
            sleep(1);
            $this->read_buffer();
            
            return true;
        }

        // Login Gagal
        $this->disconnect();
        return false;
    }

    public function exec($command) {
        if (!$this->socket) return "No Connection";
        
        // Kirim Perintah
        fwrite($this->socket, "$command\r\n");
        
        // Baca Output sampai selesai (Tunggu agak lama karena data ONU banyak)
        sleep(2); 
        
        // Baca loop sampai buffer habis
        $response = "";
        $start_time = time();
        
        while (!feof($this->socket)) {
            $chunk = fread($this->socket, 4096);
            $response .= $chunk;
            
            // Logika break: Jika buffer kosong atau ketemu prompt akhir '#'
            // Tapi karena fread blok, kita pakai timeout manual di loop
            if (strlen($chunk) == 0) break;
            
            // Jika ketemu prompt shell ZTE (biasanya diakhiri '#')
            if (substr(trim($chunk), -1) == '#') break;
            
            // Safety break 10 detik
            if ((time() - $start_time) > 10) break;
        }
        
        return $response;
    }

    public function disconnect() {
        if ($this->socket) {
            fwrite($this->socket, "exit\r\n");
            fclose($this->socket);
            $this->socket = NULL;
        }
    }

    // Helper membaca sisa buffer
    private function read_buffer() {
        $buffer = "";
        // Set non-blocking sebentar untuk menguras buffer
        stream_set_blocking($this->socket, 0);
        while ($chunk = fread($this->socket, 1024)) {
            $buffer .= $chunk;
        }
        // Kembalikan ke blocking mode
        stream_set_blocking($this->socket, 1);
        return $buffer;
    }
}
?>