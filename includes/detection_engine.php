<?php
/**
 * H4N5VS Mikrotik System Security
 * Detection Engine for security threats
 */

class DetectionEngine {
    private $api;
    private $thresholds;
    
    /**
     * Initialize detection engine
     * 
     * @param RouterosAPI $api RouterOS API instance
     */
    public function __construct($api) {
        $this->api = $api;
        
        // Initialize detection thresholds
        $this->thresholds = [
            'udp_flood' => [
                'packets_per_second' => 800,  // Packets per second
                'connection_count' => 100,    // Connection count from single IP
            ],
            'tcp_syn_flood' => [
                'syn_packets_per_second' => 500,  // SYN packets per second
                'syn_connection_ratio' => 0.8,    // Ratio of SYN to total connections
            ],
            'dns_flood' => [
                'queries_per_second' => 200,      // DNS queries per second
                'query_ratio' => 0.6,             // Ratio of DNS to total traffic
            ],
            'http_flood' => [
                'requests_per_second' => 100,     // HTTP requests per second
                'request_ratio' => 0.7,           // Ratio of HTTP to total traffic
            ],
            'brute_force' => [
                'login_attempts' => 10,           // Login attempts per minute
                'failure_ratio' => 0.8,           // Ratio of failed to total attempts
            ],
            'port_scan' => [
                'ports_per_minute' => 50,         // Ports scanned per minute
                'scan_ratio' => 0.7,              // Ratio of single-packet connections
            ],
            'botnet' => [
                'c2_server_check' => true,        // Check for known C2 servers
                'irregular_traffic_pattern' => true, // Check for irregular traffic patterns
            ],
            'malware' => [
                'suspicious_connections' => 5,    // Number of suspicious connections
                'known_signatures' => true,       // Check for known malware signatures
            ]
        ];
    }
    
    /**
     * Detect all possible security threats
     * 
     * @return array List of detected threats
     */
    public function detectThreats() {
        $threats = [];
        
        try {
            // Get all necessary data for detection
            $connections = $this->getConnections();
            $connectionStats = $this->getConnectionStats($connections);
            $trafficStats = $this->getTrafficStats();
            $loginLogs = $this->getLoginLogs();
            $knownC2Servers = $this->getKnownC2Servers();
            $systemStatus = $this->getSystemStatus();
            $processes = $this->getProcesses();
            
            // Run detection algorithms
            $udpFloodResult = $this->detectUDPFlood($connections, $connectionStats);
            if ($udpFloodResult['detected']) {
                $threats[] = $udpFloodResult['threat'];
            }
            
            $tcpSynFloodResult = $this->detectTCPSYNFlood($connections, $connectionStats);
            if ($tcpSynFloodResult['detected']) {
                $threats[] = $tcpSynFloodResult['threat'];
            }
            
            $dnsFloodResult = $this->detectDNSFlood($connections, $connectionStats);
            if ($dnsFloodResult['detected']) {
                $threats[] = $dnsFloodResult['threat'];
            }
            
            $httpFloodResult = $this->detectHTTPFlood($connections, $trafficStats);
            if ($httpFloodResult['detected']) {
                $threats[] = $httpFloodResult['threat'];
            }
            
            $bruteForceResult = $this->detectBruteForce($loginLogs);
            if ($bruteForceResult['detected']) {
                $threats[] = $bruteForceResult['threat'];
            }
            
            $portScanResult = $this->detectPortScan($connections, $connectionStats);
            if ($portScanResult['detected']) {
                $threats[] = $portScanResult['threat'];
            }
            
            $botnetResult = $this->detectBotnet($connections, $knownC2Servers);
            if ($botnetResult['detected']) {
                $threats[] = $botnetResult['threat'];
            }
            
            $malwareResult = $this->detectMalware($systemStatus, $processes);
            if ($malwareResult['detected']) {
                $threats[] = $malwareResult['threat'];
            }
            
            // Check for DDoS (combination of flood attacks)
            $ddosResult = $this->detectDDoS([
                $udpFloodResult, 
                $tcpSynFloodResult, 
                $dnsFloodResult, 
                $httpFloodResult
            ]);
            
            if ($ddosResult['detected']) {
                $threats[] = $ddosResult['threat'];
            }
        } catch (Exception $e) {
            // Log error
            error_log("Error in detection engine: " . $e->getMessage());
        }
        
        return $threats;
    }
    
    /**
     * Get active connections from Mikrotik
     * 
     * @return array List of active connections
     */
    private function getConnections() {
        try {
            // In real implementation, get connections from RouterOS API
            $connections = $this->api->command('/ip/connection/print');
            return $connections;
        } catch (Exception $e) {
            // Return empty array in case of error
            return [];
        }
    }
    
    /**
     * Get connection statistics
     * 
     * @param array $connections List of connections
     * @return array Connection statistics
     */
    private function getConnectionStats($connections) {
        $stats = [
            'total' => count($connections),
            'tcp' => 0,
            'udp' => 0,
            'icmp' => 0,
            'other' => 0,
            'tcp_states' => [],
            'source_ips' => [],
            'destination_ports' => [],
            'packet_rates' => []
        ];
        
        foreach ($connections as $conn) {
            // Count by protocol
            if (isset($conn['protocol'])) {
                $protocol = strtolower($conn['protocol']);
                
                if ($protocol === 'tcp') {
                    $stats['tcp']++;
                    
                    // Count TCP states
                    if (isset($conn['tcp-state'])) {
                        $state = $conn['tcp-state'];
                        if (!isset($stats['tcp_states'][$state])) {
                            $stats['tcp_states'][$state] = 0;
                        }
                        $stats['tcp_states'][$state]++;
                    }
                } elseif ($protocol === 'udp') {
                    $stats['udp']++;
                } elseif ($protocol === 'icmp') {
                    $stats['icmp']++;
                } else {
                    $stats['other']++;
                }
            } else {
                $stats['other']++;
            }
            
            // Count source IPs
            if (isset($conn['src-address'])) {
                $srcIp = $conn['src-address'];
                if (strpos($srcIp, ':') !== false) {
                    // Extract IP without port
                    $srcIp = explode(':', $srcIp)[0];
                }
                
                if (!isset($stats['source_ips'][$srcIp])) {
                    $stats['source_ips'][$srcIp] = 0;
                }
                $stats['source_ips'][$srcIp]++;
            }
            
            // Count destination ports
            if (isset($conn['dst-port'])) {
                $port = $conn['dst-port'];
                if (!isset($stats['destination_ports'][$port])) {
                    $stats['destination_ports'][$port] = 0;
                }
                $stats['destination_ports'][$port]++;
            }
            
            // Estimate packet rates
            if (isset($conn['packets'])) {
                $srcIp = isset($conn['src-address']) ? $conn['src-address'] : 'unknown';
                if (strpos($srcIp, ':') !== false) {
                    $srcIp = explode(':', $srcIp)[0];
                }
                
                $protocol = isset($conn['protocol']) ? strtolower($conn['protocol']) : 'unknown';
                
                $key = $srcIp . ':' . $protocol;
                if (!isset($stats['packet_rates'][$key])) {
                    $stats['packet_rates'][$key] = 0;
                }
                $stats['packet_rates'][$key] += intval($conn['packets']);
            }
        }
        
        return $stats;
    }
    
    /**
     * Get traffic statistics
     * 
     * @return array Traffic statistics
     */
    private function getTrafficStats() {
        try {
            // Get interface statistics from RouterOS API
            $interfaces = $this->api->command('/interface/print');
            $stats = [
                'total_rx' => 0,
                'total_tx' => 0,
                'interfaces' => []
            ];
            
            foreach ($interfaces as $interface) {
                if (isset($interface['name'])) {
                    // Get specific interface stats with monitor-traffic command
                    $trafficData = $this->api->commandWithParams('/interface/monitor-traffic', [
                        'interface' => $interface['name'],
                        'once' => 'true'
                    ]);
                    
                    if (!empty($trafficData) && isset($trafficData[0])) {
                        $rxBytes = isset($trafficData[0]['rx-bits-per-second']) ? intval($trafficData[0]['rx-bits-per-second']) : 0;
                        $txBytes = isset($trafficData[0]['tx-bits-per-second']) ? intval($trafficData[0]['tx-bits-per-second']) : 0;
                        
                        $stats['total_rx'] += $rxBytes;
                        $stats['total_tx'] += $txBytes;
                        
                        $stats['interfaces'][$interface['name']] = [
                            'rx' => $rxBytes,
                            'tx' => $txBytes
                        ];
                    }
                }
            }
            
            return $stats;
        } catch (Exception $e) {
            // Return empty stats in case of error
            return [
                'total_rx' => 0,
                'total_tx' => 0,
                'interfaces' => []
            ];
        }
    }
    
    /**
     * Get login logs
     * 
     * @return array Login logs
     */
    private function getLoginLogs() {
        try {
            // Get login logs from RouterOS API with topics filter
            $logs = $this->api->commandWithParams('/log/print', [
                'topics' => 'system,auth,critical',
                'limit' => '50'
            ]);
            
            // Filter logs related to login
            $loginLogs = [];
            foreach ($logs as $log) {
                if (isset($log['message']) && 
                    (stripos($log['message'], 'login') !== false || 
                     stripos($log['message'], 'user') !== false || 
                     stripos($log['message'], 'auth') !== false)) {
                    $loginLogs[] = $log;
                }
            }
            
            return $loginLogs;
        } catch (Exception $e) {
            // Return empty array in case of error
            return [];
        }
    }
    
    /**
     * Get known C2 servers
     * 
     * @return array Known Command & Control servers
     */
    private function getKnownC2Servers() {
        // In real implementation, get from database or API
        // For now, use a static list
        return [
            '185.125.190.36',
            '103.15.178.125',
            '95.214.27.56',
            '192.42.116.41',
            '91.219.237.244'
        ];
    }
    
    /**
     * Get system status
     * 
     * @return array System status information
     */
    private function getSystemStatus() {
        try {
            // Get system resources from RouterOS API
            $resources = $this->api->command('/system/resource/print');
            return $resources[0] ?? [];
        } catch (Exception $e) {
            // Return empty array in case of error
            return [];
        }
    }
    
    /**
     * Get running processes
     * 
     * @return array List of running processes
     */
    private function getProcesses() {
        try {
            // Get process list from RouterOS API
            $processes = $this->api->command('/system/process/print');
            return $processes;
        } catch (Exception $e) {
            // Return empty array in case of error
            return [];
        }
    }
    
    /**
     * Detect UDP flood attack
     * 
     * @param array $connections List of connections
     * @param array $connectionStats Connection statistics
     * @return array Detection result with threat details if detected
     */
    private function detectUDPFlood($connections, $connectionStats) {
        $result = [
            'detected' => false,
            'threat' => null
        ];
        
        // Check for UDP flood indicators
        $udpCount = $connectionStats['udp'] ?? 0;
        $totalCount = $connectionStats['total'] ?? 0;
        
        if ($udpCount > 0 && $totalCount > 0) {
            $udpRatio = $udpCount / $totalCount;
            
            // Check for high UDP packet rates
            $highPacketRateSources = [];
            foreach ($connectionStats['packet_rates'] ?? [] as $key => $rate) {
                list($srcIp, $protocol) = explode(':', $key);
                
                if ($protocol === 'udp' && $rate > $this->thresholds['udp_flood']['packets_per_second']) {
                    $highPacketRateSources[$srcIp] = $rate;
                }
            }
            
            // Check for high connection counts from single sources
            $highConnectionSources = [];
            foreach ($connectionStats['source_ips'] ?? [] as $srcIp => $count) {
                if ($count > $this->thresholds['udp_flood']['connection_count']) {
                    $highConnectionSources[$srcIp] = $count;
                }
            }
            
            // If we have both high packet rates and connection counts from the same source
            $potentialAttackers = array_intersect_key($highPacketRateSources, $highConnectionSources);
            
            if (!empty($potentialAttackers)) {
                // Get the source with highest packet rate
                arsort($potentialAttackers);
                $sourceIp = key($potentialAttackers);
                $packetRate = reset($potentialAttackers);
                
                $result['detected'] = true;
                $result['threat'] = [
                    'id' => 'UDP-FLOOD-' . time(),
                    'type' => 'UDP_FLOOD',
                    'source_ip' => $sourceIp,
                    'target_ip' => $this->getTargetIP($connections, $sourceIp),
                    'severity' => 'critical',
                    'details' => "Detected UDP flood attack with {$packetRate} packets per second",
                    'detection_time' => date('Y-m-d H:i:s'),
                    'mitigated' => false,
                    'mitigation_commands' => $this->getMitigationCommands('UDP_FLOOD', $sourceIp)
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Detect TCP SYN flood attack
     * 
     * @param array $connections List of connections
     * @param array $connectionStats Connection statistics
     * @return array Detection result with threat details if detected
     */
    private function detectTCPSYNFlood($connections, $connectionStats) {
        $result = [
            'detected' => false,
            'threat' => null
        ];
        
        // Check for SYN flood indicators
        $synCount = $connectionStats['tcp_states']['syn-sent'] ?? 0;
        $tcpCount = $connectionStats['tcp'] ?? 0;
        
        if ($synCount > 0 && $tcpCount > 0) {
            $synRatio = $synCount / $tcpCount;
            
            if ($synRatio > $this->thresholds['tcp_syn_flood']['syn_connection_ratio']) {
                // Check for sources with high SYN counts
                $synSources = [];
                
                foreach ($connections as $conn) {
                    if (isset($conn['protocol']) && strtolower($conn['protocol']) === 'tcp' &&
                        isset($conn['tcp-state']) && $conn['tcp-state'] === 'syn-sent') {
                        
                        $srcIp = $conn['src-address'] ?? 'unknown';
                        if (strpos($srcIp, ':') !== false) {
                            $srcIp = explode(':', $srcIp)[0];
                        }
                        
                        if (!isset($synSources[$srcIp])) {
                            $synSources[$srcIp] = 0;
                        }
                        $synSources[$srcIp]++;
                    }
                }
                
                if (!empty($synSources)) {
                    arsort($synSources);
                    $sourceIp = key($synSources);
                    $synCount = reset($synSources);
                    
                    if ($synCount > $this->thresholds['tcp_syn_flood']['syn_packets_per_second']) {
                        $result['detected'] = true;
                        $result['threat'] = [
                            'id' => 'SYN-FLOOD-' . time(),
                            'type' => 'TCP_SYN_FLOOD',
                            'source_ip' => $sourceIp,
                            'target_ip' => $this->getTargetIP($connections, $sourceIp),
                            'severity' => 'critical',
                            'details' => "Detected TCP SYN flood attack with {$synCount} SYN packets per second",
                            'detection_time' => date('Y-m-d H:i:s'),
                            'mitigated' => false,
                            'mitigation_commands' => $this->getMitigationCommands('TCP_SYN_FLOOD', $sourceIp)
                        ];
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Detect DNS flood attack
     * 
     * @param array $connections List of connections
     * @param array $connectionStats Connection statistics
     * @return array Detection result with threat details if detected
     */
    private function detectDNSFlood($connections, $connectionStats) {
        $result = [
            'detected' => false,
            'threat' => null
        ];
        
        // Check for DNS flood indicators (port 53)
        $dnsPortCount = $connectionStats['destination_ports']['53'] ?? 0;
        $udpCount = $connectionStats['udp'] ?? 0;
        
        if ($dnsPortCount > 0 && $udpCount > 0) {
            $dnsRatio = $dnsPortCount / $udpCount;
            
            if ($dnsRatio > $this->thresholds['dns_flood']['query_ratio'] && 
                $dnsPortCount > $this->thresholds['dns_flood']['queries_per_second']) {
                
                // Check for sources with high DNS query counts
                $dnsSources = [];
                
                foreach ($connections as $conn) {
                    if (isset($conn['protocol']) && strtolower($conn['protocol']) === 'udp' &&
                        isset($conn['dst-port']) && $conn['dst-port'] === '53') {
                        
                        $srcIp = $conn['src-address'] ?? 'unknown';
                        if (strpos($srcIp, ':') !== false) {
                            $srcIp = explode(':', $srcIp)[0];
                        }
                        
                        if (!isset($dnsSources[$srcIp])) {
                            $dnsSources[$srcIp] = 0;
                        }
                        $dnsSources[$srcIp]++;
                    }
                }
                
                if (!empty($dnsSources)) {
                    arsort($dnsSources);
                    $sourceIp = key($dnsSources);
                    $queryCount = reset($dnsSources);
                    
                    $result['detected'] = true;
                    $result['threat'] = [
                        'id' => 'DNS-FLOOD-' . time(),
                        'type' => 'DNS_FLOOD',
                        'source_ip' => $sourceIp,
                        'target_ip' => $this->getTargetIP($connections, $sourceIp),
                        'severity' => 'critical',
                        'details' => "Detected DNS flood attack with {$queryCount} queries per second",
                        'detection_time' => date('Y-m-d H:i:s'),
                        'mitigated' => false,
                        'mitigation_commands' => $this->getMitigationCommands('DNS_FLOOD', $sourceIp)
                    ];
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Detect HTTP flood attack
     * 
     * @param array $connections List of connections
     * @param array $trafficStats Traffic statistics
     * @return array Detection result with threat details if detected
     */
    private function detectHTTPFlood($connections, $trafficStats) {
        $result = [
            'detected' => false,
            'threat' => null
        ];
        
        // Check for HTTP flood indicators (ports 80, 443, 8080)
        $httpPorts = ['80', '443', '8080'];
        $httpSources = [];
        
        foreach ($connections as $conn) {
            if (isset($conn['protocol']) && strtolower($conn['protocol']) === 'tcp' &&
                isset($conn['dst-port']) && in_array($conn['dst-port'], $httpPorts)) {
                
                $srcIp = $conn['src-address'] ?? 'unknown';
                if (strpos($srcIp, ':') !== false) {
                    $srcIp = explode(':', $srcIp)[0];
                }
                
                if (!isset($httpSources[$srcIp])) {
                    $httpSources[$srcIp] = 0;
                }
                $httpSources[$srcIp]++;
            }
        }
        
        if (!empty($httpSources)) {
            arsort($httpSources);
            $sourceIp = key($httpSources);
            $requestCount = reset($httpSources);
            
            if ($requestCount > $this->thresholds['http_flood']['requests_per_second']) {
                $result['detected'] = true;
                $result['threat'] = [
                    'id' => 'HTTP-FLOOD-' . time(),
                    'type' => 'HTTP_FLOOD',
                    'source_ip' => $sourceIp,
                    'target_ip' => $this->getTargetIP($connections, $sourceIp),
                    'severity' => 'critical',
                    'details' => "Detected HTTP flood attack with {$requestCount} requests per second",
                    'detection_time' => date('Y-m-d H:i:s'),
                    'mitigated' => false,
                    'mitigation_commands' => $this->getMitigationCommands('HTTP_FLOOD', $sourceIp)
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Detect brute force attack
     * 
     * @param array $loginLogs List of login logs
     * @return array Detection result with threat details if detected
     */
    private function detectBruteForce($loginLogs) {
        $result = [
            'detected' => false,
            'threat' => null
        ];
        
        if (empty($loginLogs)) {
            return $result;
        }
        
        // Check for brute force indicators
        $loginAttempts = [];
        $failedLogins = [];
        
        foreach ($loginLogs as $log) {
            if (isset($log['src-address'])) {
                $srcIp = $log['src-address'];
                
                if (!isset($loginAttempts[$srcIp])) {
                    $loginAttempts[$srcIp] = 0;
                    $failedLogins[$srcIp] = 0;
                }
                
                $loginAttempts[$srcIp]++;
                
                if (isset($log['success']) && $log['success'] === false) {
                    $failedLogins[$srcIp]++;
                }
            }
        }
        
        // Check for multiple failed login attempts
        foreach ($loginAttempts as $srcIp => $attempts) {
            $failed = $failedLogins[$srcIp] ?? 0;
            
            if ($attempts >= $this->thresholds['brute_force']['login_attempts']) {
                $failRatio = $failed / $attempts;
                
                if ($failRatio >= $this->thresholds['brute_force']['failure_ratio']) {
                    $result['detected'] = true;
                    $result['threat'] = [
                        'id' => 'BRUTE-FORCE-' . time(),
                        'type' => 'BRUTE_FORCE',
                        'source_ip' => $srcIp,
                        'target_ip' => 'N/A',
                        'severity' => 'warning',
                        'details' => "Detected brute force login attack with {$failed} failed login attempts",
                        'detection_time' => date('Y-m-d H:i:s'),
                        'mitigated' => false,
                        'mitigation_commands' => $this->getMitigationCommands('BRUTE_FORCE', $srcIp)
                    ];
                    break;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Detect port scan activity
     * 
     * @param array $connections List of connections
     * @param array $connectionStats Connection statistics
     * @return array Detection result with threat details if detected
     */
    private function detectPortScan($connections, $connectionStats) {
        $result = [
            'detected' => false,
            'threat' => null
        ];
        
        // Check for port scan indicators
        $singlePacketConnections = [];
        
        foreach ($connections as $conn) {
            if (isset($conn['packets']) && intval($conn['packets']) === 1) {
                $srcIp = $conn['src-address'] ?? 'unknown';
                if (strpos($srcIp, ':') !== false) {
                    $srcIp = explode(':', $srcIp)[0];
                }
                
                if (!isset($singlePacketConnections[$srcIp])) {
                    $singlePacketConnections[$srcIp] = [
                        'count' => 0,
                        'ports' => []
                    ];
                }
                
                $singlePacketConnections[$srcIp]['count']++;
                
                if (isset($conn['dst-port'])) {
                    $singlePacketConnections[$srcIp]['ports'][] = $conn['dst-port'];
                }
            }
        }
        
        // Check for sources accessing multiple ports with single packets
        foreach ($singlePacketConnections as $srcIp => $data) {
            $uniquePorts = count(array_unique($data['ports']));
            
            if ($uniquePorts >= $this->thresholds['port_scan']['ports_per_minute']) {
                $result['detected'] = true;
                $result['threat'] = [
                    'id' => 'PORT-SCAN-' . time(),
                    'type' => 'PORT_SCAN',
                    'source_ip' => $srcIp,
                    'target_ip' => $this->getTargetIP($connections, $srcIp),
                    'severity' => 'warning',
                    'details' => "Detected port scan activity with {$uniquePorts} ports probed",
                    'detection_time' => date('Y-m-d H:i:s'),
                    'mitigated' => false,
                    'mitigation_commands' => $this->getMitigationCommands('PORT_SCAN', $srcIp)
                ];
                break;
            }
        }
        
        return $result;
    }
    
    /**
     * Detect botnet activity
     * 
     * @param array $connections List of connections
     * @param array $knownC2Servers List of known Command & Control servers
     * @return array Detection result with threat details if detected
     */
    private function detectBotnet($connections, $knownC2Servers) {
        $result = [
            'detected' => false,
            'threat' => null
        ];
        
        // Check for connections to known C2 servers
        if ($this->thresholds['botnet']['c2_server_check'] && !empty($knownC2Servers)) {
            foreach ($connections as $conn) {
                $dstIp = $conn['dst-address'] ?? '';
                if (strpos($dstIp, ':') !== false) {
                    $dstIp = explode(':', $dstIp)[0];
                }
                
                if (in_array($dstIp, $knownC2Servers)) {
                    $srcIp = $conn['src-address'] ?? 'unknown';
                    if (strpos($srcIp, ':') !== false) {
                        $srcIp = explode(':', $srcIp)[0];
                    }
                    
                    $result['detected'] = true;
                    $result['threat'] = [
                        'id' => 'BOTNET-' . time(),
                        'type' => 'BOTNET',
                        'source_ip' => $srcIp,
                        'target_ip' => $dstIp,
                        'severity' => 'critical',
                        'details' => "Detected botnet communication with known C2 server",
                        'detection_time' => date('Y-m-d H:i:s'),
                        'mitigated' => false,
                        'mitigation_commands' => $this->getMitigationCommands('BOTNET', $srcIp, $dstIp)
                    ];
                    break;
                }
            }
        }
        
        // Check for irregular traffic patterns if not already detected
        if (!$result['detected'] && $this->thresholds['botnet']['irregular_traffic_pattern']) {
            // Implementation would detect unusual traffic patterns
            // For simplicity, we'll use a simplified approach
        }
        
        return $result;
    }
    
    /**
     * Detect malware on connected devices
     * 
     * @param array $systemStatus System status information
     * @param array $processes List of running processes
     * @return array Detection result with threat details if detected
     */
    private function detectMalware($systemStatus, $processes) {
        $result = [
            'detected' => false,
            'threat' => null
        ];
        
        // In a real implementation, this would analyze system behavior for malware indicators
        // For simplicity, we'll use a simplified approach
        $suspiciousProcesses = [];
        
        foreach ($processes as $process) {
            $name = $process['name'] ?? '';
            $suspiciousNames = ['trojan', 'malware', 'miner', 'cryptojack', 'backdoor'];
            
            foreach ($suspiciousNames as $suspiciousName) {
                if (stripos($name, $suspiciousName) !== false) {
                    $suspiciousProcesses[] = $name;
                    break;
                }
            }
        }
        
        if (!empty($suspiciousProcesses)) {
            $processNames = implode(', ', $suspiciousProcesses);
            
            $result['detected'] = true;
            $result['threat'] = [
                'id' => 'MALWARE-' . time(),
                'type' => 'MALWARE',
                'source_ip' => 'local',
                'target_ip' => 'N/A',
                'severity' => 'critical',
                'details' => "Detected suspicious processes: {$processNames}",
                'detection_time' => date('Y-m-d H:i:s'),
                'mitigated' => false,
                'mitigation_commands' => $this->getMitigationCommands('MALWARE')
            ];
        }
        
        return $result;
    }
    
    /**
     * Detect DDoS attack (combination of flood attacks)
     * 
     * @param array $floodResults Results from various flood detection functions
     * @return array Detection result with threat details if detected
     */
    private function detectDDoS($floodResults) {
        $result = [
            'detected' => false,
            'threat' => null
        ];
        
        $detectedFloods = [];
        $sourceIps = [];
        
        foreach ($floodResults as $floodResult) {
            if ($floodResult['detected']) {
                $detectedFloods[] = $floodResult['threat']['type'];
                $sourceIps[] = $floodResult['threat']['source_ip'];
            }
        }
        
        // If multiple flood attacks detected, consider it a DDoS
        if (count($detectedFloods) >= 2) {
            $floodTypes = implode(', ', $detectedFloods);
            $uniqueSourceIps = array_unique($sourceIps);
            
            $result['detected'] = true;
            $result['threat'] = [
                'id' => 'DDOS-' . time(),
                'type' => 'DDOS',
                'source_ip' => implode(', ', array_slice($uniqueSourceIps, 0, 3)) . (count($uniqueSourceIps) > 3 ? '...' : ''),
                'target_ip' => 'Multiple',
                'severity' => 'critical',
                'details' => "Detected DDoS attack combining multiple flood types: {$floodTypes}",
                'detection_time' => date('Y-m-d H:i:s'),
                'mitigated' => false,
                'mitigation_commands' => $this->getMitigationCommands('DDOS', $uniqueSourceIps)
            ];
        }
        
        return $result;
    }
    
    /**
     * Get target IP for a source
     * 
     * @param array $connections List of connections
     * @param string $sourceIp Source IP address
     * @return string Target IP address
     */
    private function getTargetIP($connections, $sourceIp) {
        $targets = [];
        
        foreach ($connections as $conn) {
            $src = $conn['src-address'] ?? '';
            if (strpos($src, ':') !== false) {
                $src = explode(':', $src)[0];
            }
            
            if ($src === $sourceIp && isset($conn['dst-address'])) {
                $dst = $conn['dst-address'];
                if (strpos($dst, ':') !== false) {
                    $dst = explode(':', $dst)[0];
                }
                
                if (!isset($targets[$dst])) {
                    $targets[$dst] = 0;
                }
                $targets[$dst]++;
            }
        }
        
        if (empty($targets)) {
            return 'Unknown';
        }
        
        arsort($targets);
        return key($targets);
    }
    
    /**
     * Get mitigation commands for a threat
     * 
     * @param string $threatType Type of threat
     * @param string $sourceIp Source IP address
     * @param string $targetIp Target IP address (optional)
     * @return array Mitigation commands
     */
    private function getMitigationCommands($threatType, $sourceIp = null, $targetIp = null) {
        $commands = [];
        
        switch ($threatType) {
            case 'UDP_FLOOD':
                $commands[] = [
                    'title' => 'Block UDP from source IP',
                    'command' => "/ip firewall filter add chain=forward action=drop protocol=udp src-address={$sourceIp} comment=\"Blocked UDP flood\"",
                    'description' => "Add firewall rule to block all UDP traffic from {$sourceIp}"
                ];
                $commands[] = [
                    'title' => 'Add to blacklist',
                    'command' => "/ip firewall address-list add list=blacklist address={$sourceIp} comment=\"UDP flood attacker\"",
                    'description' => "Add {$sourceIp} to blacklist for persistent blocking"
                ];
                $commands[] = [
                    'title' => 'Enable UDP flood protection',
                    'command' => "/ip firewall filter add chain=forward action=drop protocol=udp connection-limit=10,32 comment=\"UDP flood protection\"",
                    'description' => "Add general UDP flood protection (limits connections per source)"
                ];
                break;
                
            case 'TCP_SYN_FLOOD':
                $commands[] = [
                    'title' => 'Block SYN packets from source IP',
                    'command' => "/ip firewall filter add chain=forward action=drop protocol=tcp tcp-flags=syn src-address={$sourceIp} comment=\"Blocked SYN flood\"",
                    'description' => "Add firewall rule to block all SYN packets from {$sourceIp}"
                ];
                $commands[] = [
                    'title' => 'Add to blacklist',
                    'command' => "/ip firewall address-list add list=blacklist address={$sourceIp} comment=\"SYN flood attacker\"",
                    'description' => "Add {$sourceIp} to blacklist for persistent blocking"
                ];
                $commands[] = [
                    'title' => 'Enable SYN flood protection',
                    'command' => "/ip firewall filter add chain=forward action=drop protocol=tcp tcp-flags=syn connection-limit=30,32 comment=\"SYN flood protection\"",
                    'description' => "Add general SYN flood protection (limits SYN connections per source)"
                ];
                break;
                
            case 'DNS_FLOOD':
                $commands[] = [
                    'title' => 'Block DNS queries from source IP',
                    'command' => "/ip firewall filter add chain=forward action=drop protocol=udp dst-port=53 src-address={$sourceIp} comment=\"Blocked DNS flood\"",
                    'description' => "Add firewall rule to block all DNS queries from {$sourceIp}"
                ];
                $commands[] = [
                    'title' => 'Add to blacklist',
                    'command' => "/ip firewall address-list add list=blacklist address={$sourceIp} comment=\"DNS flood attacker\"",
                    'description' => "Add {$sourceIp} to blacklist for persistent blocking"
                ];
                $commands[] = [
                    'title' => 'Enable DNS flood protection',
                    'command' => "/ip firewall filter add chain=forward action=drop protocol=udp dst-port=53 connection-limit=5,32 comment=\"DNS flood protection\"",
                    'description' => "Add general DNS flood protection (limits DNS connections per source)"
                ];
                break;
                
            case 'HTTP_FLOOD':
                $commands[] = [
                    'title' => 'Block HTTP requests from source IP',
                    'command' => "/ip firewall filter add chain=forward action=drop protocol=tcp dst-port=80,443 src-address={$sourceIp} comment=\"Blocked HTTP flood\"",
                    'description' => "Add firewall rule to block all HTTP requests from {$sourceIp}"
                ];
                $commands[] = [
                    'title' => 'Add to blacklist',
                    'command' => "/ip firewall address-list add list=blacklist address={$sourceIp} comment=\"HTTP flood attacker\"",
                    'description' => "Add {$sourceIp} to blacklist for persistent blocking"
                ];
                $commands[] = [
                    'title' => 'Enable HTTP flood protection',
                    'command' => "/ip firewall filter add chain=forward action=drop protocol=tcp dst-port=80,443 connection-limit=20,32 comment=\"HTTP flood protection\"",
                    'description' => "Add general HTTP flood protection (limits HTTP connections per source)"
                ];
                break;
                
            case 'BRUTE_FORCE':
                $commands[] = [
                    'title' => 'Block access from source IP',
                    'command' => "/ip firewall filter add chain=input action=drop src-address={$sourceIp} comment=\"Blocked brute force attacker\"",
                    'description' => "Add firewall rule to block all access from {$sourceIp}"
                ];
                $commands[] = [
                    'title' => 'Add to blacklist',
                    'command' => "/ip firewall address-list add list=blacklist address={$sourceIp} comment=\"Brute force attacker\"",
                    'description' => "Add {$sourceIp} to blacklist for persistent blocking"
                ];
                break;
                
            case 'PORT_SCAN':
                $commands[] = [
                    'title' => 'Block source IP',
                    'command' => "/ip firewall filter add chain=forward action=drop src-address={$sourceIp} comment=\"Blocked port scanner\"",
                    'description' => "Add firewall rule to block all traffic from {$sourceIp}"
                ];
                $commands[] = [
                    'title' => 'Add to blacklist',
                    'command' => "/ip firewall address-list add list=blacklist address={$sourceIp} comment=\"Port scanner\"",
                    'description' => "Add {$sourceIp} to blacklist for persistent blocking"
                ];
                break;
                
            case 'BOTNET':
                $commands[] = [
                    'title' => 'Block infected device',
                    'command' => "/ip firewall filter add chain=forward action=drop src-address={$sourceIp} comment=\"Blocked botnet infected device\"",
                    'description' => "Add firewall rule to block all traffic from infected device"
                ];
                
                if ($targetIp) {
                    $commands[] = [
                        'title' => 'Block C2 server',
                        'command' => "/ip firewall filter add chain=forward action=drop dst-address={$targetIp} comment=\"Blocked botnet C2 server\"",
                        'description' => "Add firewall rule to block all traffic to C2 server"
                    ];
                    $commands[] = [
                        'title' => 'Add C2 to blacklist',
                        'command' => "/ip firewall address-list add list=botnet address={$targetIp} comment=\"Botnet C2 server\"",
                        'description' => "Add {$targetIp} to botnet list for persistent blocking"
                    ];
                }
                
                $commands[] = [
                    'title' => 'Isolate infected device',
                    'command' => "/ip firewall filter add chain=forward action=drop src-address={$sourceIp} comment=\"Isolated botnet infected device\"",
                    'description' => "Isolate infected device from the network"
                ];
                break;
                
            case 'MALWARE':
                $commands[] = [
                    'title' => 'Reset Router',
                    'command' => "/system reset-configuration no-defaults=yes keep-users=yes",
                    'description' => "Reset router configuration to remove malware (backup first!)"
                ];
                
                $commands[] = [
                    'title' => 'Update RouterOS',
                    'command' => "/system package update check-for-updates",
                    'description' => "Check for and install updates to patch security vulnerabilities"
                ];
                
                $commands[] = [
                    'title' => 'Enable Secure Mode',
                    'command' => "/ip settings set secure-mode=yes",
                    'description' => "Enable secure mode to prevent unauthorized access"
                ];
                break;
                
            case 'DDOS':
                if (is_array($sourceIp)) {
                    foreach ($sourceIp as $ip) {
                        $commands[] = [
                            'title' => "Block {$ip}",
                            'command' => "/ip firewall filter add chain=forward action=drop src-address={$ip} comment=\"Blocked DDoS attacker\"",
                            'description' => "Add firewall rule to block all traffic from {$ip}"
                        ];
                        $commands[] = [
                            'title' => "Add {$ip} to blacklist",
                            'command' => "/ip firewall address-list add list=blacklist address={$ip} comment=\"DDoS attacker\"",
                            'description' => "Add {$ip} to blacklist for persistent blocking"
                        ];
                    }
                } else {
                    $commands[] = [
                        'title' => 'Enable DDoS protection',
                        'command' => "/ip firewall filter add chain=forward action=drop connection-limit=100,32 comment=\"DDoS protection\"",
                        'description' => "Add general DDoS protection (limits connections per source)"
                    ];
                }
                
                $commands[] = [
                    'title' => 'Enable advanced DDoS protection',
                    'command' => "/ip firewall filter add chain=forward action=tarpit protocol=tcp tcp-flags=syn connection-limit=30,32 comment=\"Advanced DDoS protection\"",
                    'description' => "Enable advanced DDoS protection to trap attackers (tarpit)"
                ];
                break;
        }
        
        return $commands;
    }
}