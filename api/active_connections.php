<?php
/**
 * H4N5VS Mikrotik System Security
 * API endpoint for active connections
 */

// Set content type to JSON
header('Content-Type: application/json');

// Start session
session_start();

// Include required files
require_once '../includes/functions.php';
require_once '../includes/routeros_api.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Check if Mikrotik config is set
if (!isset($_SESSION['mikrotik_ip']) || !isset($_SESSION['mikrotik_username']) || !isset($_SESSION['mikrotik_password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Router configuration missing']);
    exit;
}

try {
    // Initialize RouterOS API
    $api = new RouterosAPI();
    $api->debug = false;
    
    // Set port and SSL if available in session
    if (isset($_SESSION['mikrotik_port'])) {
        $api->port = $_SESSION['mikrotik_port'];
    }
    
    if (isset($_SESSION['use_ssl'])) {
        $api->ssl = $_SESSION['use_ssl'];
    }
    
    // Connect to router
    if ($api->connect($_SESSION['mikrotik_ip'], $_SESSION['mikrotik_username'], $_SESSION['mikrotik_password'])) {
        // Get all active connections
        $connections = $api->getCommand('/ip/firewall/connection/print');
        
        // Get PPP active connections for user details
        $pppActive = $api->getCommand('/ppp/active/print');
        
        // Get hotspot active users for additional user details
        $hotspotActive = $api->getCommand('/ip/hotspot/active/print');
        
        // Get DHCP leases for hostname lookup
        $dhcpLeases = $api->getCommand('/ip/dhcp-server/lease/print');
        
        // Build hostname lookup table
        $ipToHostname = [];
        foreach ($dhcpLeases as $lease) {
            if (isset($lease['address']) && isset($lease['host-name'])) {
                $ipToHostname[$lease['address']] = $lease['host-name'];
            }
        }
        
        // Process connections data
        $activeConnections = [
            'total' => count($connections),
            'connections' => [],
            'stats' => [
                'tcp' => 0,
                'udp' => 0,
                'icmp' => 0,
                'other' => 0,
                'established' => 0,
                'time_wait' => 0,
                'close_wait' => 0,
                'fin_wait' => 0,
                'last_ack' => 0,
                'syn_sent' => 0,
                'syn_recv' => 0
            ],
            'top_sources' => [],
            'top_destinations' => [],
            'top_ports' => []
        ];
        
        // Source and destination counters
        $sourceCount = [];
        $destCount = [];
        $portCount = [];
        
        // Process all connections
        foreach ($connections as $conn) {
            // Count protocol stats
            if (isset($conn['protocol'])) {
                $protocol = strtolower($conn['protocol']);
                
                if ($protocol === 'tcp') {
                    $activeConnections['stats']['tcp']++;
                    
                    // Count TCP state
                    if (isset($conn['tcp-state'])) {
                        $state = strtolower($conn['tcp-state']);
                        $stateKey = str_replace('-', '_', $state);
                        
                        if (isset($activeConnections['stats'][$stateKey])) {
                            $activeConnections['stats'][$stateKey]++;
                        }
                    }
                } elseif ($protocol === 'udp') {
                    $activeConnections['stats']['udp']++;
                } elseif ($protocol === 'icmp') {
                    $activeConnections['stats']['icmp']++;
                } else {
                    $activeConnections['stats']['other']++;
                }
            } else {
                $activeConnections['stats']['other']++;
            }
            
            // Count sources
            if (isset($conn['src-address'])) {
                $srcIp = $conn['src-address'];
                if (strpos($srcIp, ':') !== false) {
                    $srcIp = explode(':', $srcIp)[0];
                }
                
                if (!isset($sourceCount[$srcIp])) {
                    $sourceCount[$srcIp] = 0;
                }
                $sourceCount[$srcIp]++;
            }
            
            // Count destinations
            if (isset($conn['dst-address'])) {
                $dstIp = $conn['dst-address'];
                if (strpos($dstIp, ':') !== false) {
                    $dstIp = explode(':', $dstIp)[0];
                }
                
                if (!isset($destCount[$dstIp])) {
                    $destCount[$dstIp] = 0;
                }
                $destCount[$dstIp]++;
            }
            
            // Count destination ports
            if (isset($conn['dst-port'])) {
                $port = $conn['dst-port'];
                
                if (!isset($portCount[$port])) {
                    $portCount[$port] = 0;
                }
                $portCount[$port]++;
            }
            
            // Add connection to the list (limit to the first 50 to avoid overwhelming)
            if (count($activeConnections['connections']) < 50) {
                $srcHostname = '';
                $dstHostname = '';
                
                // Look up hostnames
                if (isset($conn['src-address'])) {
                    $srcIp = $conn['src-address'];
                    if (strpos($srcIp, ':') !== false) {
                        $srcIp = explode(':', $srcIp)[0];
                    }
                    
                    if (isset($ipToHostname[$srcIp])) {
                        $srcHostname = $ipToHostname[$srcIp];
                    }
                }
                
                if (isset($conn['dst-address'])) {
                    $dstIp = $conn['dst-address'];
                    if (strpos($dstIp, ':') !== false) {
                        $dstIp = explode(':', $dstIp)[0];
                    }
                    
                    if (isset($ipToHostname[$dstIp])) {
                        $dstHostname = $ipToHostname[$dstIp];
                    }
                }
                
                $connection = [
                    'id' => $conn['.id'] ?? '',
                    'protocol' => $conn['protocol'] ?? 'unknown',
                    'src-address' => $conn['src-address'] ?? '',
                    'dst-address' => $conn['dst-address'] ?? '',
                    'src-port' => $conn['src-port'] ?? '',
                    'dst-port' => $conn['dst-port'] ?? '',
                    'tcp-state' => $conn['tcp-state'] ?? '',
                    'bytes' => (isset($conn['bytes']) ? (int)$conn['bytes'] : 0),
                    'packets' => (isset($conn['packets']) ? (int)$conn['packets'] : 0),
                    'src-hostname' => $srcHostname,
                    'dst-hostname' => $dstHostname,
                    'timeout' => $conn['timeout'] ?? '',
                    'connection-type' => $conn['connection-type'] ?? ''
                ];
                
                $activeConnections['connections'][] = $connection;
            }
        }
        
        // Sort and limit the top sources, destinations, and ports
        arsort($sourceCount);
        arsort($destCount);
        arsort($portCount);
        
        // Add top 10 sources
        $count = 0;
        foreach ($sourceCount as $ip => $numConn) {
            $hostname = isset($ipToHostname[$ip]) ? $ipToHostname[$ip] : '';
            
            $activeConnections['top_sources'][] = [
                'ip' => $ip,
                'connections' => $numConn,
                'hostname' => $hostname
            ];
            
            $count++;
            if ($count >= 10) {
                break;
            }
        }
        
        // Add top 10 destinations
        $count = 0;
        foreach ($destCount as $ip => $numConn) {
            $hostname = isset($ipToHostname[$ip]) ? $ipToHostname[$ip] : '';
            
            $activeConnections['top_destinations'][] = [
                'ip' => $ip,
                'connections' => $numConn,
                'hostname' => $hostname
            ];
            
            $count++;
            if ($count >= 10) {
                break;
            }
        }
        
        // Add top 10 ports
        $count = 0;
        foreach ($portCount as $port => $numConn) {
            // Try to determine service name for common ports
            $service = '';
            switch ($port) {
                case '21': $service = 'FTP'; break;
                case '22': $service = 'SSH'; break;
                case '23': $service = 'Telnet'; break;
                case '25': $service = 'SMTP'; break;
                case '53': $service = 'DNS'; break;
                case '80': $service = 'HTTP'; break;
                case '110': $service = 'POP3'; break;
                case '443': $service = 'HTTPS'; break;
                case '3389': $service = 'RDP'; break;
                case '8291': $service = 'WinBox'; break;
                case '8728': $service = 'API'; break;
                case '8729': $service = 'API-SSL'; break;
            }
            
            $activeConnections['top_ports'][] = [
                'port' => $port,
                'connections' => $numConn,
                'service' => $service
            ];
            
            $count++;
            if ($count >= 10) {
                break;
            }
        }
        
        // Add PPP and hotspot user information
        $activeConnections['ppp_users'] = [];
        foreach ($pppActive as $user) {
            if (isset($user['name']) && isset($user['address'])) {
                $activeConnections['ppp_users'][] = [
                    'name' => $user['name'],
                    'address' => $user['address'],
                    'service' => $user['service'] ?? '',
                    'caller-id' => $user['caller-id'] ?? '',
                    'uptime' => $user['uptime'] ?? ''
                ];
            }
        }
        
        $activeConnections['hotspot_users'] = [];
        foreach ($hotspotActive as $user) {
            if (isset($user['user']) && isset($user['address'])) {
                $activeConnections['hotspot_users'][] = [
                    'user' => $user['user'],
                    'address' => $user['address'],
                    'mac-address' => $user['mac-address'] ?? '',
                    'uptime' => $user['uptime'] ?? '',
                    'bytes-in' => isset($user['bytes-in']) ? (int)$user['bytes-in'] : 0,
                    'bytes-out' => isset($user['bytes-out']) ? (int)$user['bytes-out'] : 0
                ];
            }
        }
        
        // Disconnect from router
        $api->disconnect();
        
        // Return JSON response
        echo json_encode($activeConnections);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Could not connect to the router']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}