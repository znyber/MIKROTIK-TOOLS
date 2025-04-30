<?php
/**
 * RouterOS API class by Denis Basta
 * Modified for H4N5VS Mikrotik System Security
 * Based on https://github.com/BenMenking/routeros-api
 */

class RouterosAPI {
    var $debug = false;
    var $error = false;
    var $error_msg = '';
    var $attempts = 5;
    var $connected = false;
    var $delay = 3;
    var $port = 8728;
    var $timeout = 3;
    var $socket;
    var $ssl = false;
    var $legacy = false;

    /**
     * Constructor
     *
     * @param array $options Options array
     */
    function __construct($options = array()) {
        if (isset($options['ssl'])) {
            $this->ssl = $options['ssl'];
            if ($this->ssl) {
                $this->port = 8729;
            }
        }
        if (isset($options['legacy'])) {
            $this->legacy = $options['legacy'];
        }
        if (isset($options['port'])) {
            $this->port = $options['port'];
        }
        if (isset($options['timeout'])) {
            $this->timeout = $options['timeout'];
        }
        if (isset($options['attempts'])) {
            $this->attempts = $options['attempts'];
        }
        if (isset($options['delay'])) {
            $this->delay = $options['delay'];
        }
    }

    /**
     * Connect to router
     *
     * @param string $ip Router IP
     * @param string $login RouterOS username
     * @param string $password RouterOS password
     * @return boolean
     */
    function connect($ip, $login, $password) {
        for ($ATTEMPT = 1; $ATTEMPT <= $this->attempts; $ATTEMPT++) {
            $this->connected = false;
            $this->debug('Connection attempt #' . $ATTEMPT . ' to ' . $ip . ':' . $this->port . '...');
            
            // Set socket transport protocol
            $transport = $this->ssl ? 'ssl' : 'tcp';
            
            // Set socket context for SSL connections
            $context = null;
            if ($this->ssl) {
                $context = stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ]
                ]);
            }
            
            // Establish connection
            if ($context) {
                $this->socket = @stream_socket_client(
                    $transport . '://' . $ip . ':' . $this->port,
                    $this->error_no,
                    $this->error_str,
                    $this->timeout,
                    STREAM_CLIENT_CONNECT,
                    $context
                );
            } else {
                $this->socket = @fsockopen($transport . '://' . $ip, $this->port, $this->error_no, $this->error_str, $this->timeout);
            }
            
            if ($this->socket) {
                socket_set_timeout($this->socket, $this->timeout);
                $this->write('/login');
                $RESPONSE = $this->read(false);
                if (isset($RESPONSE[0]) && $RESPONSE[0] == '!done') {
                    if (!isset($RESPONSE[1])) {
                        // Login without challenge
                        $this->write('/login', false);
                        $this->write('=name=' . $login, false);
                        $this->write('=password=' . $password);
                        $RESPONSE = $this->read(false);
                        if (isset($RESPONSE[0]) && $RESPONSE[0] == '!done') {
                            $this->connected = true;
                            break;
                        }
                    } else {
                        // Login with challenge
                        $chal = $this->decode_challenge($RESPONSE[1]);
                        $this->write('/login', false);
                        $this->write('=name=' . $login, false);
                        $this->write('=response=00' . md5(chr(0) . $password . pack('H*', $chal)));
                        $RESPONSE = $this->read(false);
                        if (isset($RESPONSE[0]) && $RESPONSE[0] == '!done') {
                            $this->connected = true;
                            break;
                        }
                    }
                }
                fclose($this->socket);
            }
            sleep($this->delay);
        }

        if ($this->connected) {
            $this->debug('Connected successfully!');
        } else {
            $this->debug('Error connecting to RouterOS: ' . $this->error_str);
            $this->error = true;
            $this->error_msg = 'Error connecting to RouterOS: ' . $this->error_str;
        }
        return $this->connected;
    }

    /**
     * Disconnect from router
     *
     * @return void
     */
    function disconnect() {
        if ($this->socket) {
            $this->write('/quit');
            fclose($this->socket);
            $this->connected = false;
            $this->debug('Disconnected from RouterOS');
        }
    }

    /**
     * Parse response from Router OS
     *
     * @param array $response Response data
     * @return array
     */
    function parse_response($response) {
        if (is_array($response)) {
            $result = [];
            $i = 0;
            if (isset($response[0]) && $response[0] == '!re') {
                unset($response[0]);
                foreach ($response as $value) {
                    if ($value == '!re') {
                        $i++;
                        continue;
                    }
                    if (preg_match_all('/^=(.*)=(.*)/', $value, $matches)) {
                        if (is_numeric($matches[2][0])) {
                            $result[$i][$matches[1][0]] = (int) $matches[2][0];
                        } else {
                            $result[$i][$matches[1][0]] = $matches[2][0];
                        }
                    }
                }
                return $result;
            } else {
                foreach ($response as $value) {
                    if (preg_match_all('/^=(.*)=(.*)/', $value, $matches)) {
                        if (is_numeric($matches[2][0])) {
                            $result[$matches[1][0]] = (int) $matches[2][0];
                        } else {
                            $result[$matches[1][0]] = $matches[2][0];
                        }
                    }
                }
                return $result;
            }
        }
        return [];
    }

    /**
     * Execute command
     *
     * @param string $command Command to execute
     * @return array
     */
    function command($command) {
        if (!$this->connected) {
            $this->error = true;
            $this->error_msg = 'Not connected to RouterOS';
            return [];
        }

        $args = func_get_args();
        array_shift($args);
        
        $this->write('/' . $command);
        foreach ($args as $value) {
            $this->write('=' . $value);
        }
        $response = $this->read();
        return $this->parse_response($response);
    }
    
    /**
     * Execute RouterOS command with params
     *
     * @param string $command Command to execute
     * @param array $params Command parameters
     * @return array
     */
    function commandWithParams($command, $params = []) {
        if (!$this->connected) {
            $this->error = true;
            $this->error_msg = 'Not connected to RouterOS';
            return [];
        }
        
        $this->write('/' . $command);
        foreach ($params as $key => $value) {
            $this->write('=' . $key . '=' . $value);
        }
        $response = $this->read();
        return $this->parse_response($response);
    }
    
    /**
     * Get command (alias for command method)
     * Used for compatibility with API calls
     *
     * @param string $command Command to execute
     * @return array
     */
    function getCommand() {
        $args = func_get_args();
        return call_user_func_array([$this, 'command'], $args);
    }

    /**
     * Read data from Router OS
     *
     * @param boolean $parse Parse the data? Default: true
     * @return array
     */
    function read($parse = true) {
        $RESPONSE = [];
        while (true) {
            // Read the first byte of input which gives us some or all of the length
            // of the remaining reply.
            $BYTE = ord(fread($this->socket, 1));
            $LENGTH = 0;
            
            // If the first byte is not an end marker, then get more bytes
            if ($BYTE != 0) {
                if (($BYTE & 128) == 128) {
                    // Bit 7 set. It's a multi-byte length
                    $BYTE = $BYTE & 127;
                    $LENGTH = ($BYTE << 8) + ord(fread($this->socket, 1));
                } else {
                    // Bit 7 not set. Single byte length
                    $LENGTH = $BYTE;
                }
                
                // Get the actual data
                $_ = '';
                for ($i = 0; $i < $LENGTH; $i++) {
                    $_ .= fread($this->socket, 1);
                }
                $RESPONSE[] = $_;
                $this->debug('>>> [' . $LENGTH . '] ' . $_);
            } else {
                // End of response marker
                break;
            }
        }
        
        if (empty($RESPONSE)) {
            $this->error = true;
            $this->error_msg = 'No data received from RouterOS';
        }
        
        return $RESPONSE;
    }

    /**
     * Write (send) data to Router OS
     *
     * @param string $str String to send
     * @param boolean $parse Parse the string? Default: true
     * @return boolean|void
     */
    function write($str, $parse = true) {
        if ($parse) {
            $str = explode("\n", $str);
            foreach ($str as $sub_str) {
                if (strlen($sub_str) > 0) {
                    $this->write_single($sub_str);
                }
            }
        } else {
            $this->write_single($str);
        }
    }

    /**
     * Write (send) a single string to Router OS
     *
     * @param string $str String to send
     * @return boolean
     */
    function write_single($str) {
        if (fwrite($this->socket, $this->encode_length(strlen($str)) . $str) === false) {
            $this->error = true;
            $this->error_msg = 'Failed to write to socket';
            return false;
        }
        $this->debug('<<< [' . strlen($str) . '] ' . $str);
        return true;
    }

    /**
     * Encode length for API communication
     *
     * @param integer $length Length to encode
     * @return string
     */
    function encode_length($length) {
        if ($length < 0x80) {
            $length = chr($length);
        } elseif ($length < 0x4000) {
            $length |= 0x8000;
            $length = chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x200000) {
            $length |= 0xC00000;
            $length = chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x10000000) {
            $length |= 0xE0000000;
            $length = chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length >= 0x10000000) {
            $length = chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }
        return $length;
    }

    /**
     * Decode challenge from RouterOS
     *
     * @param string $response Challenge response
     * @return string
     */
    function decode_challenge($response) {
        if (substr($response, 0, 5) == '=ret=') {
            $data = explode('=', $response);
            return $data[2];
        }
        return '';
    }

    /**
     * Output debug info
     *
     * @param string $text Debug text
     * @return void
     */
    function debug($text) {
        if ($this->debug) {
            echo date('Y-m-d H:i:s') . ' ' . $text . PHP_EOL;
        }
    }
    
    /**
     * Get last error message
     *
     * @return string
     */
    function getLastError() {
        return $this->error_msg;
    }
    
    /**
     * Check connection status
     *
     * @return boolean
     */
    function isConnected() {
        return $this->connected;
    }
}