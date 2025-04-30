<?php
/**
 * H4N5VS Mikrotik System Security
 * Mitigation Engine
 */

// Define path constants if not defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

/**
 * MitigationEngine class for implementing security mitigations
 */
class MitigationEngine {
    private $api;
    private $mitigation_history = [];
    private $openai;
    private $blocklist = [];
    private $active_mitigations = [];
    
    /**
     * Constructor
     * 
     * @param RouterosAPI $api RouterOS API instance
     * @param object $openai OpenAI integration instance (optional)
     */
    public function __construct($api, $openai = null) {
        $this->api = $api;
        $this->openai = $openai;
        
        // Load mitigation history and blocklists if exists
        $this->loadHistory();
        $this->loadBlocklist();
        $this->loadActiveMitigations();
    }
    
    /**
     * Load mitigation history from file
     * 
     * @return void
     */
    private function loadHistory() {
        $history_file = BASE_PATH . '/data/mitigation_history.json';
        if (file_exists($history_file)) {
            $this->mitigation_history = json_decode(file_get_contents($history_file), true) ?: [];
        }
    }
    
    /**
     * Save mitigation history to file
     * 
     * @return void
     */
    private function saveHistory() {
        $history_file = BASE_PATH . '/data/mitigation_history.json';
        file_put_contents($history_file, json_encode($this->mitigation_history, JSON_PRETTY_PRINT));
    }
    
    /**
     * Load blocklist from file
     * 
     * @return void
     */
    private function loadBlocklist() {
        $blocklist_file = BASE_PATH . '/data/blocklist.json';
        if (file_exists($blocklist_file)) {
            $this->blocklist = json_decode(file_get_contents($blocklist_file), true) ?: [];
        }
    }
    
    /**
     * Save blocklist to file
     * 
     * @return void
     */
    private function saveBlocklist() {
        $blocklist_file = BASE_PATH . '/data/blocklist.json';
        file_put_contents($blocklist_file, json_encode($this->blocklist, JSON_PRETTY_PRINT));
    }
    
    /**
     * Load active mitigations from file
     * 
     * @return void
     */
    private function loadActiveMitigations() {
        $mitigations_file = BASE_PATH . '/data/active_mitigations.json';
        if (file_exists($mitigations_file)) {
            $this->active_mitigations = json_decode(file_get_contents($mitigations_file), true) ?: [];
        }
    }
    
    /**
     * Save active mitigations to file
     * 
     * @return void
     */
    private function saveActiveMitigations() {
        $mitigations_file = BASE_PATH . '/data/active_mitigations.json';
        file_put_contents($mitigations_file, json_encode($this->active_mitigations, JSON_PRETTY_PRINT));
    }
    
    /**
     * Get available mitigation templates
     * 
     * @return array List of mitigation templates by threat type
     */
    public function getMitigationTemplates() {
        return [
            'UDP_FLOOD' => [
                'title' => 'UDP Flood Protection',
                'description' => 'Protects against UDP flood attacks by blocking source IPs and adding connection limits',
                'commands' => [
                    [
                        'title' => 'Block Source IP',
                        'command' => '/ip firewall filter add chain=forward action=drop protocol=udp src-address=37.8.8.8 comment="Blocked UDP flood source"',
                        'description' => 'Block all UDP traffic from attacking IP'
                    ],
                    [
                        'title' => 'Add to Blacklist',
                        'command' => '/ip firewall address-list add list=blacklist address=37.8.8.8 comment="UDP flood attacker"',
                        'description' => 'Add source IP to blacklist for persistent blocking'
                    ],
                    [
                        'title' => 'Add Connection Limit',
                        'command' => '/ip firewall filter add chain=forward action=drop protocol=udp connection-limit=10,32 comment="UDP flood protection"',
                        'description' => 'Add general UDP connection limiting'
                    ]
                ]
            ],
            'TCP_SYN_FLOOD' => [
                'title' => 'TCP SYN Flood Protection',
                'description' => 'Protects against SYN flood attacks by blocking source IPs and adding SYN packet limits',
                'commands' => [
                    [
                        'title' => 'Block Source IP',
                        'command' => '/ip firewall filter add chain=forward action=drop protocol=tcp tcp-flags=syn src-address=37.8.8.8 comment="Blocked SYN flood source"',
                        'description' => 'Block SYN packets from attacking IP'
                    ],
                    [
                        'title' => 'Add to Blacklist',
                        'command' => '/ip firewall address-list add list=blacklist address=37.8.8.8 comment="SYN flood attacker"',
                        'description' => 'Add source IP to blacklist for persistent blocking'
                    ],
                    [
                        'title' => 'Add SYN Protection',
                        'command' => '/ip firewall filter add chain=forward action=drop protocol=tcp tcp-flags=syn connection-limit=30,32 comment="SYN flood protection"',
                        'description' => 'Add general SYN flood protection'
                    ]
                ]
            ],
            'DNS_FLOOD' => [
                'title' => 'DNS Flood Protection',
                'description' => 'Protects against DNS amplification attacks',
                'commands' => [
                    [
                        'title' => 'Block Source IP',
                        'command' => '/ip firewall filter add chain=forward action=drop protocol=udp dst-port=53 src-address=37.8.8.8 comment="Blocked DNS flood source"',
                        'description' => 'Block DNS queries from attacking IP'
                    ],
                    [
                        'title' => 'Add to Blacklist',
                        'command' => '/ip firewall address-list add list=blacklist address=37.8.8.8 comment="DNS flood attacker"',
                        'description' => 'Add source IP to blacklist for persistent blocking'
                    ],
                    [
                        'title' => 'Add DNS Protection',
                        'command' => '/ip firewall filter add chain=forward action=drop protocol=udp dst-port=53 src-address-list=!allowed_dns comment="DNS flood protection"',
                        'description' => 'Add general DNS flood protection'
                    ]
                ]
            ],
            'BOTNET' => [
                'title' => 'Botnet Protection',
                'description' => 'Blocks botnet infected devices and C2 server communication',
                'commands' => [
                    [
                        'title' => 'Block Source IP',
                        'command' => '/ip firewall filter add chain=forward action=drop src-address=37.8.8.8 comment="Blocked botnet infected device"',
                        'description' => 'Block all traffic from infected device'
                    ],
                    [
                        'title' => 'Add to Botnet List',
                        'command' => '/ip firewall address-list add list=botnet address=37.8.8.8 comment="Botnet infected device"',
                        'description' => 'Add source IP to botnet list for monitoring'
                    ],
                    [
                        'title' => 'Block C2 Communication',
                        'command' => '/ip firewall filter add chain=forward action=drop dst-address=37.8.8.8 comment="Blocked botnet C2 server"',
                        'description' => 'Block all traffic to C2 server'
                    ]
                ]
            ],
            'BRUTE_FORCE' => [
                'title' => 'Brute Force Protection',
                'description' => 'Blocks IPs attempting brute force attacks',
                'commands' => [
                    [
                        'title' => 'Block Source IP',
                        'command' => '/ip firewall filter add chain=input action=drop src-address=37.8.8.8 comment="Blocked brute force attacker"',
                        'description' => 'Block all traffic from attacking IP'
                    ],
                    [
                        'title' => 'Add to Blacklist',
                        'command' => '/ip firewall address-list add list=blacklist address=37.8.8.8 comment="Brute force attacker"',
                        'description' => 'Add source IP to blacklist for persistent blocking'
                    ]
                ]
            ],
            'PORT_SCAN' => [
                'title' => 'Port Scan Protection',
                'description' => 'Blocks IPs conducting port scans',
                'commands' => [
                    [
                        'title' => 'Block Source IP',
                        'command' => '/ip firewall filter add chain=forward action=drop src-address=37.8.8.8 comment="Blocked port scanner"',
                        'description' => 'Block all traffic from scanning IP'
                    ],
                    [
                        'title' => 'Add to Blacklist',
                        'command' => '/ip firewall address-list add list=blacklist address=37.8.8.8 comment="Port scanner"',
                        'description' => 'Add source IP to blacklist for persistent blocking'
                    ],
                    [
                        'title' => 'Add Port Scan Detection',
                        'command' => '/ip firewall filter add chain=input action=drop protocol=tcp psd=21,3s,3,1 comment="Port scan detection"',
                        'description' => 'Add general port scan detection rule'
                    ]
                ]
            ],
            'DDOS' => [
                'title' => 'DDoS Protection',
                'description' => 'Protects against distributed denial of service attacks',
                'commands' => [
                    [
                        'title' => 'Block Source IP',
                        'command' => '/ip firewall filter add chain=forward action=drop src-address=37.8.8.8 comment="Blocked DDoS attacker"',
                        'description' => 'Block traffic from attacking IP'
                    ],
                    [
                        'title' => 'Add Connection Limit',
                        'command' => '/ip firewall filter add chain=forward action=drop connection-limit=100,32 comment="DDoS protection"',
                        'description' => 'Add connection limiting'
                    ],
                    [
                        'title' => 'Add SYN Protection',
                        'command' => '/ip firewall filter add chain=forward action=tarpit protocol=tcp tcp-flags=syn connection-limit=30,32 comment="Advanced DDoS protection"',
                        'description' => 'Add SYN flood protection as part of DDoS mitigation'
                    ],
                    [
                        'title' => 'Add Rate Limiting',
                        'command' => '/ip firewall filter add chain=forward action=drop connection-rate=100/1s,32 comment="Connection rate limiting"',
                        'description' => 'Add connection rate limiting'
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Mitigate a detected threat
     * 
     * @param array $threat Threat details
     * @param string $method Mitigation method (auto, block, limit, monitor)
     * @param array $params Additional parameters for mitigation
     * @return array Mitigation result
     */
    public function mitigateThreat($threat, $method = 'auto', $params = []) {
        $result = [
            'success' => false,
            'message' => '',
            'actions' => [],
            'threat' => $threat
        ];
        
        // Log the mitigation attempt
        log_activity('Attempting to mitigate threat: ' . ($threat['type'] ?? 'unknown'), 'security');
        
        // Determine the mitigation method if auto
        if ($method === 'auto') {
            $method = $this->determineMitigationMethod($threat);
        }
        
        // Apply the mitigation
        switch ($method) {
            case 'block':
                $result = $this->blockThreat($threat, $params);
                break;
                
            case 'limit':
                $result = $this->limitThreat($threat, $params);
                break;
                
            case 'monitor':
                $result = $this->monitorThreat($threat, $params);
                break;
                
            default:
                $result['message'] = 'Invalid mitigation method';
                break;
        }
        
        // If successful, add to history
        if ($result['success']) {
            $this->mitigation_history[] = [
                'timestamp' => time(),
                'threat' => $threat,
                'method' => $method,
                'actions' => $result['actions'],
                'result' => $result['message']
            ];
            
            // Save updated history
            $this->saveHistory();
            
            // Check if we need to add to active mitigations
            if ($method !== 'monitor') {
                $this->active_mitigations[] = [
                    'id' => uniqid('mit_'),
                    'timestamp' => time(),
                    'threat' => $threat,
                    'method' => $method,
                    'actions' => $result['actions'],
                    'status' => 'active'
                ];
                
                // Save active mitigations
                $this->saveActiveMitigations();
            }
        }
        
        return $result;
    }
    
    /**
     * Determine the best mitigation method for a threat
     * 
     * @param array $threat Threat details
     * @return string Mitigation method
     */
    private function determineMitigationMethod($threat) {
        $threat_type = $threat['type'] ?? '';
        $severity = $threat['severity'] ?? 'medium';
        
        // Use AI to determine method if available
        if ($this->openai !== null) {
            try {
                $recommendations = $this->openai->generateMitigationRecommendations([$threat]);
                
                if (isset($recommendations['immediate_actions'][0]['action_type'])) {
                    $ai_method = $recommendations['immediate_actions'][0]['action_type'];
                    
                    // Map AI action type to our methods
                    if ($ai_method === 'firewall' || $ai_method === 'addresslist') {
                        return 'block';
                    } elseif ($ai_method === 'ratelimit') {
                        return 'limit';
                    }
                }
            } catch (Exception $e) {
                log_activity('AI recommendation failed: ' . $e->getMessage(), 'error');
            }
        }
        
        // Fallback to rule-based method selection
        switch ($threat_type) {
            case 'bruteforce':
            case 'port_scan':
            case 'ddos':
            case 'botnet':
                return 'block';
                
            case 'traffic_anomaly':
            case 'connection_flood':
                return 'limit';
                
            case 'unusual_protocol':
            case 'unusual_connection':
            default:
                return $severity === 'high' ? 'block' : 'monitor';
        }
    }
    
    /**
     * Block a threat by adding to address list and firewall
     * 
     * @param array $threat Threat details
     * @param array $params Additional parameters
     * @return array Result of the blocking action
     */
    private function blockThreat($threat, $params = []) {
        $result = [
            'success' => false,
            'message' => '',
            'actions' => []
        ];
        
        // Get the source IP to block
        $source_ip = $threat['source_ip'] ?? '';
        if (empty($source_ip)) {
            $result['message'] = 'Cannot block threat: No source IP specified';
            return $result;
        }
        
        // Check if the IP is already blocked
        $already_blocked = false;
        foreach ($this->blocklist as $entry) {
            if ($entry['ip'] === $source_ip) {
                $already_blocked = true;
                break;
            }
        }
        
        if ($already_blocked) {
            $result['message'] = "IP $source_ip is already blocked";
            $result['success'] = true;
            return $result;
        }
        
        // Determine block duration
        $duration = isset($params['duration']) ? $params['duration'] : '1d';
        
        // Add to blocklist
        $this->blocklist[] = [
            'ip' => $source_ip,
            'reason' => $threat['type'] . ': ' . ($threat['description'] ?? ''),
            'timestamp' => time(),
            'duration' => $duration
        ];
        
        // Save the updated blocklist
        $this->saveBlocklist();
        
        // Add to RouterOS address list if connected
        if ($this->api && $this->api->connected) {
            try {
                // Add to address list
                $addressListParams = [
                    'address' => $source_ip,
                    'list' => 'h4n5vs_blacklist',
                    'comment' => $threat['type'] . ' ' . date('Y-m-d H:i:s')
                ];
                
                // Add timeout if specified
                if ($duration !== 'permanent') {
                    $addressListParams['timeout'] = $duration;
                }
                
                $this->api->commandWithParams('ip/firewall/address-list/add', $addressListParams);
                $result['actions'][] = "Added $source_ip to address list 'h4n5vs_blacklist'";
                
                // Create firewall rule if it doesn't exist
                $rules = $this->api->command('/ip/firewall/filter/print', [
                    '?comment' => 'H4N5VS Auto-Block Rule'
                ]);
                
                if (empty($rules)) {
                    // Create the rule
                    $this->api->commandWithParams('ip/firewall/filter/add', [
                        'chain' => 'input',
                        'src-address-list' => 'h4n5vs_blacklist',
                        'action' => 'drop',
                        'place-before' => '0',
                        'comment' => 'H4N5VS Auto-Block Rule'
                    ]);
                    $result['actions'][] = "Created firewall rule to block h4n5vs_blacklist";
                }
                
                $result['success'] = true;
                $result['message'] = "Successfully blocked IP $source_ip";
                
            } catch (Exception $e) {
                $result['message'] = 'Error blocking IP: ' . $e->getMessage();
                log_activity('Mitigation error: ' . $e->getMessage(), 'error');
            }
        } else {
            // Not connected to router
            $result['message'] = 'IP added to local blocklist but not applied to router (not connected)';
            $result['success'] = true;
        }
        
        return $result;
    }
    
    /**
     * Apply rate limiting to a threat
     * 
     * @param array $threat Threat details
     * @param array $params Additional parameters
     * @return array Result of the rate limiting action
     */
    private function limitThreat($threat, $params = []) {
        $result = [
            'success' => false,
            'message' => '',
            'actions' => []
        ];
        
        // Get the source IP to limit
        $source_ip = $threat['source_ip'] ?? '';
        if (empty($source_ip)) {
            $result['message'] = 'Cannot limit threat: No source IP specified';
            return $result;
        }
        
        // Determine rate limit
        $rate = isset($params['rate']) ? $params['rate'] : '1M/1M';
        
        // Apply rate limiting if connected to router
        if ($this->api && $this->api->connected) {
            try {
                // First check if already in the address list
                $address_entries = $this->api->command('/ip/firewall/address-list/print', [
                    '?address' => $source_ip,
                    '?list' => 'h4n5vs_ratelimit'
                ]);
                
                if (empty($address_entries)) {
                    // Add to address list
                    $this->api->commandWithParams('ip/firewall/address-list/add', [
                        'address' => $source_ip,
                        'list' => 'h4n5vs_ratelimit',
                        'comment' => $threat['type'] . ' ' . date('Y-m-d H:i:s')
                    ]);
                    $result['actions'][] = "Added $source_ip to address list 'h4n5vs_ratelimit'";
                }
                
                // Check if rate limit rule exists
                $rules = $this->api->command('/ip/firewall/filter/print', [
                    '?comment' => 'H4N5VS Rate Limit Rule'
                ]);
                
                if (empty($rules)) {
                    // Create the mangle rule for marking
                    $this->api->commandWithParams('ip/firewall/mangle/add', [
                        'chain' => 'prerouting',
                        'src-address-list' => 'h4n5vs_ratelimit',
                        'action' => 'mark-connection',
                        'new-connection-mark' => 'h4n5vs_limited_conn',
                        'passthrough' => 'yes',
                        'comment' => 'H4N5VS Rate Limit Marking'
                    ]);
                    $result['actions'][] = "Created connection marking rule for h4n5vs_ratelimit";
                    
                    // Create the simple queue
                    $this->api->commandWithParams('queue/simple/add', [
                        'name' => 'H4N5VS_RateLimit',
                        'target' => $source_ip,
                        'max-limit' => $rate,
                        'comment' => 'H4N5VS Auto-Rate Limit'
                    ]);
                    $result['actions'][] = "Created rate limit of $rate for $source_ip";
                } else {
                    // Update existing queue
                    $queues = $this->api->command('/queue/simple/print', [
                        '?comment' => 'H4N5VS Auto-Rate Limit'
                    ]);
                    
                    if (!empty($queues)) {
                        // Add the IP to target list if not already there
                        $queue_id = $queues[0]['.id'];
                        $current_target = $queues[0]['target'];
                        
                        if (strpos($current_target, $source_ip) === false) {
                            $new_target = $current_target . ',' . $source_ip;
                            $this->api->commandWithParams('queue/simple/set', [
                                '.id' => $queue_id,
                                'target' => $new_target
                            ]);
                            $result['actions'][] = "Added $source_ip to existing rate limit queue";
                        } else {
                            $result['actions'][] = "$source_ip already in rate limit queue";
                        }
                    } else {
                        // Create new queue
                        $this->api->commandWithParams('queue/simple/add', [
                            'name' => 'H4N5VS_RateLimit',
                            'target' => $source_ip,
                            'max-limit' => $rate,
                            'comment' => 'H4N5VS Auto-Rate Limit'
                        ]);
                        $result['actions'][] = "Created rate limit of $rate for $source_ip";
                    }
                }
                
                $result['success'] = true;
                $result['message'] = "Successfully applied rate limit to IP $source_ip";
                
            } catch (Exception $e) {
                $result['message'] = 'Error applying rate limit: ' . $e->getMessage();
                log_activity('Mitigation error: ' . $e->getMessage(), 'error');
            }
        } else {
            // Not connected to router
            $result['message'] = 'Cannot apply rate limiting (not connected to router)';
        }
        
        return $result;
    }
    
    /**
     * Monitor a threat without taking action
     * 
     * @param array $threat Threat details
     * @param array $params Additional parameters
     * @return array Result of the monitoring action
     */
    private function monitorThreat($threat, $params = []) {
        $result = [
            'success' => true,
            'message' => 'Threat is being monitored',
            'actions' => ['Added to monitoring list']
        ];
        
        // Get the source IP
        $source_ip = $threat['source_ip'] ?? 'unknown';
        
        // Add to monitoring list in router if connected
        if ($this->api && $this->api->connected && $source_ip !== 'unknown') {
            try {
                // Add to address list
                $this->api->commandWithParams('ip/firewall/address-list/add', [
                    'address' => $source_ip,
                    'list' => 'h4n5vs_monitoring',
                    'comment' => $threat['type'] . ' ' . date('Y-m-d H:i:s')
                ]);
                $result['actions'][] = "Added $source_ip to address list 'h4n5vs_monitoring'";
            } catch (Exception $e) {
                // Non-critical error, still consider monitoring started
                log_activity('Monitor action partial failure: ' . $e->getMessage(), 'warning');
            }
        }
        
        return $result;
    }
    
    /**
     * Remove a mitigation
     * 
     * @param string $mitigation_id Mitigation ID to remove
     * @return array Result of removal
     */
    public function removeMitigation($mitigation_id) {
        $result = [
            'success' => false,
            'message' => 'Mitigation not found',
            'actions' => []
        ];
        
        // Find the mitigation
        $mitigation = null;
        $mitigation_index = -1;
        
        foreach ($this->active_mitigations as $index => $mit) {
            if ($mit['id'] === $mitigation_id) {
                $mitigation = $mit;
                $mitigation_index = $index;
                break;
            }
        }
        
        if (!$mitigation) {
            return $result;
        }
        
        // Remove based on method
        $method = $mitigation['method'];
        $source_ip = $mitigation['threat']['source_ip'] ?? '';
        
        if (empty($source_ip)) {
            $result['message'] = 'Cannot remove mitigation: No source IP found';
            return $result;
        }
        
        // Remove from RouterOS if connected
        if ($this->api && $this->api->connected) {
            try {
                switch ($method) {
                    case 'block':
                        // Remove from address list
                        $address_entries = $this->api->command('/ip/firewall/address-list/print', [
                            '?address' => $source_ip,
                            '?list' => 'h4n5vs_blacklist'
                        ]);
                        
                        foreach ($address_entries as $entry) {
                            $this->api->command('/ip/firewall/address-list/remove', [
                                '.id' => $entry['.id']
                            ]);
                        }
                        
                        $result['actions'][] = "Removed $source_ip from address list 'h4n5vs_blacklist'";
                        break;
                        
                    case 'limit':
                        // Remove from address list
                        $address_entries = $this->api->command('/ip/firewall/address-list/print', [
                            '?address' => $source_ip,
                            '?list' => 'h4n5vs_ratelimit'
                        ]);
                        
                        foreach ($address_entries as $entry) {
                            $this->api->command('/ip/firewall/address-list/remove', [
                                '.id' => $entry['.id']
                            ]);
                        }
                        
                        // Update queue
                        $queues = $this->api->command('/queue/simple/print', [
                            '?comment' => 'H4N5VS Auto-Rate Limit'
                        ]);
                        
                        if (!empty($queues)) {
                            $queue_id = $queues[0]['.id'];
                            $current_target = $queues[0]['target'];
                            
                            // Remove IP from target list
                            $targets = explode(',', $current_target);
                            $targets = array_filter($targets, function($t) use ($source_ip) {
                                return trim($t) !== $source_ip;
                            });
                            
                            $new_target = implode(',', $targets);
                            
                            // Update or remove queue
                            if (!empty($new_target)) {
                                $this->api->commandWithParams('queue/simple/set', [
                                    '.id' => $queue_id,
                                    'target' => $new_target
                                ]);
                                $result['actions'][] = "Removed $source_ip from rate limit queue";
                            } else {
                                $this->api->command('/queue/simple/remove', [
                                    '.id' => $queue_id
                                ]);
                                $result['actions'][] = "Removed rate limit queue (empty)";
                            }
                        }
                        
                        $result['actions'][] = "Removed $source_ip from address list 'h4n5vs_ratelimit'";
                        break;
                        
                    case 'monitor':
                        // Remove from monitoring address list
                        $address_entries = $this->api->command('/ip/firewall/address-list/print', [
                            '?address' => $source_ip,
                            '?list' => 'h4n5vs_monitoring'
                        ]);
                        
                        foreach ($address_entries as $entry) {
                            $this->api->command('/ip/firewall/address-list/remove', [
                                '.id' => $entry['.id']
                            ]);
                        }
                        
                        $result['actions'][] = "Removed $source_ip from address list 'h4n5vs_monitoring'";
                        break;
                }
                
            } catch (Exception $e) {
                $result['message'] = 'Error removing mitigation: ' . $e->getMessage();
                log_activity('Mitigation removal error: ' . $e->getMessage(), 'error');
                return $result;
            }
        }
        
        // Remove from blocklist if present
        foreach ($this->blocklist as $index => $entry) {
            if ($entry['ip'] === $source_ip) {
                array_splice($this->blocklist, $index, 1);
                $this->saveBlocklist();
                break;
            }
        }
        
        // Remove from active mitigations
        array_splice($this->active_mitigations, $mitigation_index, 1);
        $this->saveActiveMitigations();
        
        $result['success'] = true;
        $result['message'] = "Successfully removed mitigation for IP $source_ip";
        
        return $result;
    }
    
    /**
     * Get mitigation history
     * 
     * @param int $limit Number of entries to return (0 for all)
     * @return array Mitigation history
     */
    public function getHistory($limit = 0) {
        // Sort by timestamp in descending order
        usort($this->mitigation_history, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        // Return all or limited history
        if ($limit > 0 && count($this->mitigation_history) > $limit) {
            return array_slice($this->mitigation_history, 0, $limit);
        }
        
        return $this->mitigation_history;
    }
    
    /**
     * Get active mitigations
     * 
     * @return array Active mitigations
     */
    public function getActiveMitigations() {
        return $this->active_mitigations;
    }
    
    /**
     * Get blocklist
     * 
     * @return array Blocklist
     */
    public function getBlocklist() {
        return $this->blocklist;
    }
    
    /**
     * Check if an IP is blocked
     * 
     * @param string $ip IP address to check
     * @return bool True if blocked, false otherwise
     */
    public function isBlocked($ip) {
        foreach ($this->blocklist as $entry) {
            if ($entry['ip'] === $ip) {
                return true;
            }
        }
        
        return false;
    }
}