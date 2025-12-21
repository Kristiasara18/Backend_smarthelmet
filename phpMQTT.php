<?php
/**
 * phpMQTT class
 * Source: bluerhinos/phpMQTT
 */

class phpMQTT {

    private $socket;
    private $msgid = 1;
    public $keepalive = 10;
    public $address;
    public $port;
    public $clientid;
    public $connected = false;
    public $topics = [];

    function __construct($address, $port, $clientid) {
        $this->address  = $address;
        $this->port     = $port;
        $this->clientid = $clientid;
    }

    function connect($clean = true, $will = NULL, $username = NULL, $password = NULL) {
        $this->socket = fsockopen($this->address, $this->port, $errno, $errstr, 60);
        if (!$this->socket) return false;

        stream_set_timeout($this->socket, 5);
        $flags = 0x10;
        if ($clean) $flags |= 0x02;

        $payload  = $this->string($this->clientid);
        if ($username) {
            $flags |= 0x80;
            $payload .= $this->string($username);
        }
        if ($password) {
            $flags |= 0x40;
            $payload .= $this->string($password);
        }

        $var  = chr(0).chr(4)."MQTT".chr(4).chr($flags).chr(0).chr($this->keepalive);
        $cmd  = chr(0x10).$this->remainingLength(strlen($var.$payload)).$var.$payload;

        fwrite($this->socket, $cmd);
        $response = fread($this->socket, 4);
        if (strlen($response) < 4 || ord($response[3]) != 0) return false;

        $this->connected = true;
        return true;
    }

    function subscribe($topics) {
        $this->topics = $topics;
        $payload = "";
        foreach ($topics as $topic => $qos) {
            $payload .= $this->string($topic).chr($qos['qos']);
        }
        $cmd = chr(0x82).$this->remainingLength(strlen($payload)+2).chr(0).chr($this->msgid++).$payload;
        fwrite($this->socket, $cmd);
    }

    function proc() {
        if (!$this->connected) return false;
        $data = fread($this->socket, 1024);
        if (!$data) return false;

        $topicLength = (ord($data[2]) << 8) + ord($data[3]);
        $topic = substr($data, 4, $topicLength);
        $msg = substr($data, 4 + $topicLength);

        if (isset($this->topics[$topic]['function'])) {
            call_user_func($this->topics[$topic]['function'], $topic, $msg);
        }
        return true;
    }

    function close() {
        if ($this->socket) fclose($this->socket);
        $this->connected = false;
    }

    private function string($str) {
        return chr(strlen($str) >> 8).chr(strlen($str) & 0xFF).$str;
    }

    private function remainingLength($len) {
        $string = '';
        do {
            $digit = $len % 128;
            $len = intdiv($len, 128);
            if ($len > 0) $digit |= 0x80;
            $string .= chr($digit);
        } while ($len > 0);
        return $string;
    }
}
