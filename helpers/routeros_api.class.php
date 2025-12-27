<?php
/**
 * RouterOS API Class - Standard Version
 * Compatible with PHP 8.x and RouterOS v6/v7
 */
class RouterosAPI
{
    var $debug = false; // Set to true to see raw data
    var $connected = false;
    var $port = 8728;
    var $ssl = false;
    var $timeout = 3;
    var $attempts = 5;
    var $delay = 3;
    var $socket;
    var $error_no;
    var $error_str;
    public $error_msg;

    public function connect($ip, $login, $password)
    {
        for ($i = 1; $i <= $this->attempts; $i++) {
            $this->connected = false;
            $protocol = ($this->ssl ? 'ssl://' : '');
            $context = stream_context_create(['ssl' => ['ciphers' => 'ADH:ALL', 'verify_peer' => false, 'verify_peer_name' => false]]);
            
            // Debugging info
            if ($this->debug) echo "Mencoba koneksi ke $ip:$this->port...<br>";

            $this->socket = @stream_socket_client($protocol . $ip . ':' . $this->port, $this->error_no, $this->error_str, $this->timeout, STREAM_CLIENT_CONNECT, $context);
            
            if ($this->socket) {
                socket_set_timeout($this->socket, $this->timeout);
                // Step 1: Kirim Login Command
                $this->write('/login', false);
                $this->write('=name=' . $login, false);
                $this->write('=password=' . $password);
                
                // Step 2: Baca Respon Awal
                $response = $this->read(false);
                if (isset($response[0])) {
                    // Jika login langsung sukses (RouterOS lama atau user tanpa password)
                    if ($response[0] == '!done') {
                        if (!isset($response[1])) {
                            $this->connected = true;
                            break;
                        } else {
                            // Butuh Challenge (MD5)
                            if (isset($response[1]['ret'])) {
                                if ($this->debug) echo "Mengirim Challenge Response MD5...<br>";
                                $this->write('/login', false);
                                $this->write('=name=' . $login, false);
                                $this->write('=password=' . $password, false);
                                $this->write('=response=00' . md5(chr(0) . $password . pack('H*', $response[1]['ret'])));
                                
                                $response = $this->read(false);
                                if (isset($response[0]) && $response[0] == '!done') {
                                    $this->connected = true;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            sleep($this->delay);
        }
        return $this->connected;
    }

    public function disconnect()
    {
        if ($this->socket) {
            fclose($this->socket);
        }
        $this->connected = false;
    }

    public function comm($com, $arr = array())
    {
        $count = count($arr);
        $this->write($com, !($count > 0));
        $i = 0;
        foreach ($arr as $k => $v) {
            switch ($k[0]) {
                case "?": $el = "$k=$v"; break;
                case "~": $el = "$k~$v"; break;
                default: $el = "=$k=$v"; break;
            }
            $last = ($i++ == $count - 1);
            $this->write($el, $last);
        }
        return $this->read();
    }

    private function read($parse = true)
    {
        $RESPONSE = array();
        $received_done = false;
        while (true) {
            $byte = fread($this->socket, 1);
            if ($byte === false || $byte === '') { return $RESPONSE; } // Connection lost
            
            $BYTE = ord($byte);
            $LENGTH = 0;
            if ($BYTE & 128) {
                if (($BYTE & 192) == 128) $LENGTH = (($BYTE & 63) << 8) + ord(fread($this->socket, 1));
                else if (($BYTE & 224) == 192) $LENGTH = (($BYTE & 31) << 8 * 2) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
                else if (($BYTE & 240) == 224) $LENGTH = (($BYTE & 15) << 8 * 3) + (ord(fread($this->socket, 1)) << 8 * 2) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
                else $LENGTH = ord(fread($this->socket, 1));
            } else {
                $LENGTH = $BYTE;
            }
            
            $_ = "";
            if ($LENGTH > 0) {
                $retlen = 0;
                while ($retlen < $LENGTH) {
                    $toread = $LENGTH - $retlen;
                    $chunk = fread($this->socket, $toread);
                    $retlen += strlen($chunk);
                    $_ .= $chunk;
                }
            }
            if ($_ == "!done") $received_done = true;
            $RESPONSE[] = $_;
            if ($received_done) break;
        }
        if ($parse) $RESPONSE = $this->parseResponse($RESPONSE);
        return $RESPONSE;
    }

    private function write($command, $param2 = true)
    {
        if ($command) {
            $data = $command;
            $len = strlen($data);
            $cmd = "";
            if ($len < 0x80) $cmd .= chr($len);
            else if ($len < 0x4000) $cmd .= chr(($len >> 8) | 0x80) . chr($len & 0xFF);
            else if ($len < 0x200000) $cmd .= chr(($len >> 16) | 0xC0) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF);
            else if ($len < 0x10000000) $cmd .= chr(($len >> 24) | 0xE0) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF);
            else if ($len < 0x100000000) $cmd .= chr(0xF0) . chr(($len >> 24) & 0xFF) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF);
            $cmd .= $data;
            fwrite($this->socket, $cmd);
        }
        if ($param2) fwrite($this->socket, chr(0));
    }

    private function parseResponse($response)
    {
        if (isset($response['!trap'])) return "Error";
        $parsed = array();
        $CURRENT = null;
        foreach ($response as $x) {
            if (in_array($x, array('!fatal', '!re', '!trap'))) {
                if ($x == '!re') $CURRENT = &$parsed[];
                else $CURRENT = &$parsed['trap'][];
            } elseif ($x != '!done') {
                $matches = array();
                if (preg_match_all('/^=([^=]+)=(.*)$/', $x, $matches)) {
                    $CURRENT[$matches[1][0]] = $matches[2][0];
                }
            }
        }
        return $parsed;
    }
}
?>
