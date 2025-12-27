<?php
// FILE: helpers/TelnetPro.php
class TelnetPro {
    private $socket;
    private $timeout;
    private $prompt;

    public function __construct($host, $port = 23, $timeout = 10) {
        $this->timeout = $timeout;
        $this->socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        
        if (!$this->socket) {
            throw new Exception("Connection Failed: $errstr ($errno)");
        }
        stream_set_timeout($this->socket, $timeout);
    }

    public function login($user, $pass, $prompt = '#') {
        $this->prompt = $prompt;
        
        // Handle User Prompt
        $this->wait_for(['Username:', 'Login:', 'User name:']);
        $this->send($user);
        
        // Handle Password Prompt
        $this->wait_for(['Password:']);
        $this->send($pass);
        
        // Handle Success Shell
        $this->wait_for([$prompt, '>']);
        
        // Matikan paging (PENTING untuk ZTE)
        $this->send("terminal length 0");
        $this->wait_for($prompt);
    }

    public function exec($cmd) {
        // Bersihkan buffer sisa
        // $this->wait_for($this->prompt);
        
        $this->send($cmd);
        $out = $this->wait_for([$this->prompt, '#']);
        
        // Bersihkan output (Hapus baris pertama yg berisi command itu sendiri)
        $lines = explode("\n", $out);
        $clean = [];
        foreach($lines as $l) {
            $l = trim($l);
            if($l == $cmd || $l == $this->prompt || empty($l)) continue;
            if(substr($l, -1) == '#') continue; // Hapus prompt akhir
            $clean[] = $l;
        }
        return implode("\n", $clean);
    }

    public function disconnect() {
        if($this->socket) {
            $this->send("exit");
            fclose($this->socket);
        }
    }

    private function send($data) {
        fwrite($this->socket, $data . "\r\n");
    }

    private function wait_for($needles) {
        if(!is_array($needles)) $needles = [$needles];
        $buf = '';
        $start = time();
        
        while(!feof($this->socket)) {
            if((time() - $start) > $this->timeout) break; // Timeout safety
            
            $char = fgetc($this->socket);
            $buf .= $char;
            
            foreach($needles as $n) {
                if(substr($buf, -strlen($n)) === $n) return $buf;
            }
        }
        return $buf;
    }
}
