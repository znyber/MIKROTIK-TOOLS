<?php
/**
 * H4N5VS Mikrotik System Security
 * Utility functions
 */

// Define path constants if not defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('LOGS_PATH')) {
    define('LOGS_PATH', BASE_PATH . '/logs');
}

/**
 * Analyze firewall security based on rules
 * 
 * @param array $firewall_rules Rules from RouterOS
 * @return array Security analysis
 */
function analyzeFirewallSecurityRules($firewall_rules) {
    $security = [
        'score' => 0,
        'max_score' => 100,
        'issues' => [],
        'recommendations' => []
    ];
    
    // Check for basic ingress filtering
    $has_ingress_filter = false;
    foreach ($firewall_rules as $rule) {
        if (isset($rule['chain']) && $rule['chain'] === 'input' && 
            isset($rule['action']) && $rule['action'] === 'drop') {
            $has_ingress_filter = true;
            break;
        }
    }
    
    if (!$has_ingress_filter) {
        $security['issues'][] = 'No basic ingress filtering detected';
        $security['recommendations'][] = 'Add input chain drop rule for unauthorized traffic';
    } else {
        $security['score'] += 20;
    }
    
    // Calculate security score based on rules
    $security['score'] = min(100, $security['score']);
    
    return $security;
}

/**
 * Get security level based on score
 * 
 * @param int $score Security score
 * @return string Security level (low, medium, high)
 */
function getSecurityLevel($score) {
    if ($score < 40) {
        return 'low';
    } elseif ($score < 70) {
        return 'medium';
    } else {
        return 'high';
    }
}

/**
 * Get blocked IPs from firewall
 * 
 * @param array $address_list Address list from RouterOS
 * @return array Blocked IPs
 */
function getBlockedIPs($address_list) {
    $blocked = [];
    foreach ($address_list as $address) {
        if (isset($address['list']) && ($address['list'] === 'blacklist' || strpos($address['list'], 'block') !== false)) {
            $blocked[] = [
                'ip' => $address['address'],
                'comment' => $address['comment'] ?? '',
                'blocked_since' => $address['creation-time'] ?? ''
            ];
        }
    }
    return $blocked;
}

/**
 * Process security logs
 * 
 * @param array $logs Logs from RouterOS
 * @return array Processed logs with security events
 */
function processSecurityLogs($logs) {
    $security_events = [];
    $patterns = [
        'bruteforce' => '/login failure|authentication failure|failed login/i',
        'scan' => '/port scan|probe|nmap/i',
        'ddos' => '/DoS|DDoS|flood|attack/i'
    ];
    
    foreach ($logs as $log) {
        $event_type = 'other';
        foreach ($patterns as $type => $pattern) {
            if (isset($log['message']) && preg_match($pattern, $log['message'])) {
                $event_type = $type;
                break;
            }
        }
        
        $security_events[] = [
            'time' => $log['time'] ?? date('Y-m-d H:i:s'),
            'type' => $event_type,
            'message' => $log['message'] ?? '',
            'source' => isset($log['address']) ? $log['address'] : (isset($log['src-address']) ? $log['src-address'] : '')
        ];
    }
    
    return $security_events;
}

/**
 * Determine the type of log entry based on message content
 * 
 * @param string $message Log message
 * @return string Log type (system, auth, firewall, etc.)
 */
function determineLogTypeByMessage($message) {
    if (preg_match('/login|auth|user|password/i', $message)) {
        return 'auth';
    }
    if (preg_match('/firewall|filter|nat|mangle/i', $message)) {
        return 'firewall';
    }
    if (preg_match('/interface|ethernet|pppoe|vpn/i', $message)) {
        return 'network';
    }
    if (preg_match('/error|warning|critical|alert/i', $message)) {
        return 'error';
    }
    return 'system';
}

/**
 * Log activity
 * 
 * @param string $message Message to log
 * @param string $type Log type (system, auth, error, etc.)
 * @return bool Success
 */
function log_activity($message, $type = 'system') {
    $log_file = LOGS_PATH . '/' . $type . '_' . date('Y-m-d') . '.log';
    $log_entry = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    
    // Create logs directory if it doesn't exist
    if (!is_dir(LOGS_PATH)) {
        mkdir(LOGS_PATH, 0755, true);
    }
    
    return file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Get router connection
 * 
 * @return RouterosAPI|false RouterOS API instance or false on failure
 */
function get_router_connection() {
    // Check if connection parameters are in session
    if (!isset($_SESSION['router_ip']) || !isset($_SESSION['router_user']) || !isset($_SESSION['router_pass'])) {
        log_activity('Router connection parameters not set', 'error');
        return false;
    }
    
    // Create API instance
    require_once INCLUDES_PATH . '/routeros_api.php';
    
    $options = [];
    
    // Set SSL option if configured
    if (isset($_SESSION['router_ssl']) && $_SESSION['router_ssl'] == 'yes') {
        $options['ssl'] = true;
    }
    
    // Set port if configured
    if (isset($_SESSION['router_port']) && !empty($_SESSION['router_port'])) {
        $options['port'] = (int)$_SESSION['router_port'];
    }
    
    // Create API instance with options
    $API = new RouterosAPI($options);
    
    // Set debug mode if needed
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $API->debug = true;
    }
    
    // Connect to router
    if ($API->connect($_SESSION['router_ip'], $_SESSION['router_user'], $_SESSION['router_pass'])) {
        log_activity('Connected to router ' . $_SESSION['router_ip'], 'system');
        return $API;
    } else {
        log_activity('Failed to connect to router: ' . $API->getLastError(), 'error');
        return false;
    }
}

/**
 * Format bytes to human readable format
 * 
 * @param int $bytes Bytes to format
 * @param int $precision Precision
 * @return string Formatted bytes
 */
function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Format time to human readable format
 * 
 * @param int $seconds Seconds to format
 * @return string Formatted time
 */
function format_time($seconds) {
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's';
    } elseif ($seconds < 86400) {
        return floor($seconds / 3600) . 'h ' . floor(($seconds % 3600) / 60) . 'm';
    } else {
        return floor($seconds / 86400) . 'd ' . floor(($seconds % 86400) / 3600) . 'h';
    }
}

/**
 * Format number with suffixes
 * 
 * @param int $number Number to format
 * @return string Formatted number
 */
function format_number($number) {
    $suffixes = ['', 'K', 'M', 'B', 'T'];
    $suffixIndex = 0;
    
    while ($number >= 1000 && $suffixIndex < count($suffixes) - 1) {
        $number /= 1000;
        $suffixIndex++;
    }
    
    return round($number, 1) . $suffixes[$suffixIndex];
}

/**
 * Check if a given IP is in private range
 * 
 * @param string $ip IP address to check
 * @return bool True if IP is private
 */
function is_private_ip($ip) {
    $privateRanges = [
        '10.0.0.0|10.255.255.255',     // 10.0.0.0/8
        '172.16.0.0|172.31.255.255',   // 172.16.0.0/12
        '192.168.0.0|192.168.255.255', // 192.168.0.0/16
        '169.254.0.0|169.254.255.255', // 169.254.0.0/16
        '127.0.0.0|127.255.255.255'    // 127.0.0.0/8
    ];
    
    $ip_long = ip2long($ip);
    
    if ($ip_long === false) {
        return false;
    }
    
    foreach ($privateRanges as $range) {
        list($start, $end) = explode('|', $range);
        if ($ip_long >= ip2long($start) && $ip_long <= ip2long($end)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get system info for dashboard
 * 
 * @return array System info
 */
function get_system_info($API) {
    $system_info = [
        'hostname' => 'Unknown',
        'version' => 'Unknown',
        'uptime' => 'Unknown',
        'cpu_load' => 0,
        'memory_usage' => 0,
        'total_memory' => 0,
        'free_memory' => 0,
        'hdd_usage' => 0,
        'total_hdd' => 0,
        'architecture' => 'Unknown',
        'board_name' => 'Unknown',
        'factory_software' => 'Unknown',
        'pppoe_clients' => 0,
        'hotspot_users' => 0
    ];
    
    // If API is not connected, return empty data
    if (!$API || !$API->isConnected()) {
        return $system_info;
    }
    
    try {
        // Get resource info
        $resources = $API->command('system/resource/print');
        if (isset($resources[0])) {
            $system_info['version'] = $resources[0]['version'] ?? 'Unknown';
            $system_info['uptime'] = $resources[0]['uptime'] ?? 'Unknown';
            $system_info['cpu_load'] = $resources[0]['cpu-load'] ?? 0;
            $system_info['total_memory'] = $resources[0]['total-memory'] ?? 0;
            $system_info['free_memory'] = $resources[0]['free-memory'] ?? 0;
            $system_info['memory_usage'] = $system_info['total_memory'] > 0 ? 
                round(($system_info['total_memory'] - $system_info['free_memory']) / $system_info['total_memory'] * 100, 1) : 0;
            $system_info['architecture'] = $resources[0]['architecture-name'] ?? 'Unknown';
            $system_info['board_name'] = $resources[0]['board-name'] ?? 'Unknown';
            $system_info['factory_software'] = $resources[0]['factory-software'] ?? 'Unknown';
        }
        
        // Get hostname
        $identity = $API->command('system/identity/print');
        if (isset($identity[0])) {
            $system_info['hostname'] = $identity[0]['name'] ?? 'Unknown';
        }
        
        // Get storage info
        $storage = $API->command('system/resource/disk/print');
        if (isset($storage[0])) {
            $system_info['total_hdd'] = $storage[0]['total'] ?? 0;
            $system_info['free_hdd'] = $storage[0]['free'] ?? 0;
            $system_info['hdd_usage'] = $system_info['total_hdd'] > 0 ? 
                round(($system_info['total_hdd'] - $system_info['free_hdd']) / $system_info['total_hdd'] * 100, 1) : 0;
        }
        
        // Count PPPoE clients
        $pppoe_active = $API->command('interface/pppoe-server/active/print');
        $system_info['pppoe_clients'] = count($pppoe_active);
        
        // Count hotspot users
        $hotspot_active = $API->command('ip/hotspot/active/print');
        $system_info['hotspot_users'] = count($hotspot_active);
        
    } catch (Exception $e) {
        log_activity('Error fetching system info: ' . $e->getMessage(), 'error');
    }
    
    return $system_info;
}

/**
 * Get network statistics for the dashboard
 * 
 * @param object $API RouterOS API instance
 * @return array Network statistics
 */
function get_network_stats($API) {
    $stats = [
        'interfaces' => [],
        'total_rx' => 0,
        'total_tx' => 0,
        'connection_count' => 0,
        'firewall_rules' => 0,
        'dhcp_leases' => 0,
        'dns_cache' => 0
    ];
    
    // If API is not connected, return empty data
    if (!$API || !$API->isConnected()) {
        return $stats;
    }
    
    try {
        // Get interfaces
        $interfaces = $API->command('interface/print');
        
        foreach ($interfaces as $interface) {
            if (isset($interface['name']) && isset($interface['type'])) {
                // Get interface statistics
                $if_stats = $API->command('interface/monitor-traffic', 
                                        'interface=' . $interface['name'], 
                                        'once=');
                
                if (isset($if_stats[0])) {
                    $stats['interfaces'][] = [
                        'name' => $interface['name'],
                        'type' => $interface['type'],
                        'running' => isset($interface['running']) ? ($interface['running'] === 'true') : false,
                        'disabled' => isset($interface['disabled']) ? ($interface['disabled'] === 'true') : false,
                        'rx_byte' => $if_stats[0]['rx-byte'] ?? 0,
                        'tx_byte' => $if_stats[0]['tx-byte'] ?? 0,
                        'rx_packet' => $if_stats[0]['rx-packet'] ?? 0,
                        'tx_packet' => $if_stats[0]['tx-packet'] ?? 0
                    ];
                    
                    // Add to totals
                    $stats['total_rx'] += $if_stats[0]['rx-byte'] ?? 0;
                    $stats['total_tx'] += $if_stats[0]['tx-byte'] ?? 0;
                }
            }
        }
        
        // Get connection count
        $connections = $API->command('ip/firewall/connection/print');
        $stats['connection_count'] = count($connections);
        
        // Get firewall rules count
        $firewall_rules = $API->command('ip/firewall/filter/print');
        $stats['firewall_rules'] = count($firewall_rules);
        
        // Get DHCP leases
        $dhcp_leases = $API->command('ip/dhcp-server/lease/print');
        $stats['dhcp_leases'] = count($dhcp_leases);
        
        // Get DNS cache entries
        $dns_cache = $API->command('ip/dns/cache/print');
        $stats['dns_cache'] = count($dns_cache);
        
    } catch (Exception $e) {
        log_activity('Error fetching network stats: ' . $e->getMessage(), 'error');
    }
    
    return $stats;
}

/**
 * Determine log type from content
 * 
 * @param string $log_content Log entry content
 * @return string Log type (info, warning, error, success)
 */
function determine_log_type($log_content) {
    $log_content = strtolower($log_content);
    
    if (strpos($log_content, 'error') !== false || 
        strpos($log_content, 'fail') !== false || 
        strpos($log_content, 'critical') !== false) {
        return 'error';
    } elseif (strpos($log_content, 'warning') !== false || 
              strpos($log_content, 'warn') !== false) {
        return 'warning';
    } elseif (strpos($log_content, 'success') !== false || 
              strpos($log_content, 'connected') !== false || 
              strpos($log_content, 'enabled') !== false) {
        return 'success';
    } else {
        return 'info';
    }
}

/**
 * Process security logs for analysis
 * 
 * @param array $logs Array of log entries
 * @return array Processed logs with security relevance scores
 */
function process_security_logs($logs) {
    $processed = [];
    $keywords = [
        'high' => ['attack', 'exploit', 'hack', 'bruteforce', 'malicious', 'blocked', 'filter', 'firewall', 'drop'],
        'medium' => ['warning', 'attempt', 'suspicious', 'unusual', 'multiple'],
        'low' => ['login', 'connect', 'disconnect', 'started', 'stopped']
    ];
    
    foreach ($logs as $log) {
        $message = strtolower($log['message'] ?? '');
        $severity = 'info';
        $score = 0;
        
        // Check for high severity keywords
        foreach ($keywords['high'] as $keyword) {
            if (strpos($message, $keyword) !== false) {
                $severity = 'high';
                $score += 3;
            }
        }
        
        // Check for medium severity keywords
        foreach ($keywords['medium'] as $keyword) {
            if (strpos($message, $keyword) !== false) {
                $severity = $severity === 'high' ? 'high' : 'medium';
                $score += 1;
            }
        }
        
        // Check for low severity keywords
        foreach ($keywords['low'] as $keyword) {
            if (strpos($message, $keyword) !== false) {
                $severity = $severity === 'high' || $severity === 'medium' ? $severity : 'low';
                $score += 0.5;
            }
        }
        
        $log['severity'] = $severity;
        $log['security_score'] = min($score, 10);
        $processed[] = $log;
    }
    
    return $processed;
}

/**
 * Analyze firewall security
 * 
 * @param array $rules Firewall rules array
 * @return array Analysis results
 */
function analyze_firewall_security($rules) {
    $analysis = [
        'score' => 0,
        'max_score' => 100,
        'findings' => [],
        'recommendations' => []
    ];
    
    // Initial score
    $analysis['score'] = 50;
    
    // Check if there are any rules
    if (empty($rules)) {
        $analysis['findings'][] = 'No firewall rules found';
        $analysis['recommendations'][] = 'Add basic firewall rules to protect your network';
        $analysis['score'] -= 30;
    } else {
        // Check for default drop rule
        $has_default_drop = false;
        $has_input_drop = false;
        $has_forward_drop = false;
        
        foreach ($rules as $rule) {
            if (isset($rule['chain']) && isset($rule['action'])) {
                if ($rule['chain'] === 'input' && $rule['action'] === 'drop' && 
                    (!isset($rule['comment']) || $rule['comment'] === 'default')) {
                    $has_input_drop = true;
                }
                if ($rule['chain'] === 'forward' && $rule['action'] === 'drop' && 
                    (!isset($rule['comment']) || $rule['comment'] === 'default')) {
                    $has_forward_drop = true;
                }
            }
        }
        
        $has_default_drop = $has_input_drop && $has_forward_drop;
        
        if (!$has_default_drop) {
            $analysis['findings'][] = 'No default drop rule found';
            $analysis['recommendations'][] = 'Add default drop rules for input and forward chains';
            $analysis['score'] -= 20;
        } else {
            $analysis['score'] += 10;
        }
        
        // Check for anti-spoofing rules
        $has_antispoofing = false;
        foreach ($rules as $rule) {
            if (isset($rule['comment']) && 
                (strpos(strtolower($rule['comment']), 'anti-spoofing') !== false || 
                 strpos(strtolower($rule['comment']), 'antispoofing') !== false)) {
                $has_antispoofing = true;
                break;
            }
        }
        
        if (!$has_antispoofing) {
            $analysis['findings'][] = 'No anti-spoofing protection found';
            $analysis['recommendations'][] = 'Add anti-spoofing rules to prevent IP spoofing attacks';
            $analysis['score'] -= 10;
        } else {
            $analysis['score'] += 5;
        }
        
        // Check for DDoS protection
        $has_ddos_protection = false;
        foreach ($rules as $rule) {
            if (isset($rule['comment']) && 
                (strpos(strtolower($rule['comment']), 'ddos') !== false || 
                 strpos(strtolower($rule['comment']), 'dos') !== false)) {
                $has_ddos_protection = true;
                break;
            }
        }
        
        if (!$has_ddos_protection) {
            $analysis['findings'][] = 'No DDoS protection rules found';
            $analysis['recommendations'][] = 'Add DDoS protection rules to mitigate potential attacks';
            $analysis['score'] -= 10;
        } else {
            $analysis['score'] += 5;
        }
        
        // Check rule count
        $rule_count = count($rules);
        if ($rule_count < 5) {
            $analysis['findings'][] = 'Very few firewall rules (' . $rule_count . ')';
            $analysis['recommendations'][] = 'Add more specific firewall rules to enhance security';
            $analysis['score'] -= 5;
        } elseif ($rule_count > 50) {
            $analysis['findings'][] = 'Large number of firewall rules (' . $rule_count . ')';
            $analysis['recommendations'][] = 'Consider consolidating and optimizing your firewall rules';
            // No score deduction for this, just a recommendation
        } else {
            $analysis['score'] += 5;
        }
    }
    
    // Ensure score is between 0 and 100
    $analysis['score'] = max(0, min(100, $analysis['score']));
    
    return $analysis;
}

/**
 * Get security level based on score
 * 
 * @param int $score Security score (0-100)
 * @return string Security level (critical, low, medium, high, excellent)
 */
function get_security_level($score) {
    if ($score < 20) {
        return 'critical';
    } elseif ($score < 40) {
        return 'low';
    } elseif ($score < 60) {
        return 'medium';
    } elseif ($score < 80) {
        return 'high';
    } else {
        return 'excellent';
    }
}

/**
 * Get blocked IPs list
 * 
 * @param object $API RouterOS API instance
 * @return array Blocked IPs list
 */
function get_blocked_ips($API) {
    $blocked_ips = [];
    
    // If API is not connected, return empty data
    if (!$API || !$API->isConnected()) {
        return $blocked_ips;
    }
    
    try {
        // Get address list entries
        $address_lists = $API->command('ip/firewall/address-list/print');
        
        foreach ($address_lists as $entry) {
            if (isset($entry['list']) && strtolower($entry['list']) === 'blacklist') {
                $blocked_ips[] = [
                    'address' => $entry['address'] ?? '',
                    'comment' => $entry['comment'] ?? '',
                    'timestamp' => strtotime($entry['creation-time'] ?? '0s')
                ];
            }
        }
        
    } catch (Exception $e) {
        log_activity('Error fetching blocked IPs: ' . $e->getMessage(), 'error');
    }
    
    return $blocked_ips;
}