<?php
/**
 * H4N5VS Mikrotik System Security
 * API endpoint for network statistics
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
        // Get all interface statistics
        $interfaces = $api->commandWithParams('interface/print', ['stats' => 'true']);
        
        // Get traffic monitor data (more accurate for current bandwidth)
        $trafficMonitor = $api->commandWithParams('interface/monitor-traffic', [
            'interface' => 'all', 
            'once' => 'true'
        ]);
        
        // Process interface data
        $networkStats = [
            'interfaces' => [],
            'total_rx' => 0,
            'total_tx' => 0,
            'current_rx' => 0,
            'current_tx' => 0
        ];
        
        // Process all interfaces
        foreach ($interfaces as $interface) {
            if (isset($interface['name']) && isset($interface['rx-byte']) && isset($interface['tx-byte'])) {
                // Skip disabled or non-running interfaces
                if (isset($interface['disabled']) && $interface['disabled'] === 'true') {
                    continue;
                }
                
                $isWan = false;
                $isLan = false;
                
                // Attempt to identify WAN and LAN interfaces
                if (
                    strpos(strtolower($interface['name']), 'wan') !== false || 
                    strpos(strtolower($interface['name']), 'ether1') !== false ||
                    strpos(strtolower($interface['name']), 'pppoe-out') !== false ||
                    (isset($interface['comment']) && strpos(strtolower($interface['comment']), 'wan') !== false)
                ) {
                    $isWan = true;
                }
                
                if (
                    strpos(strtolower($interface['name']), 'lan') !== false || 
                    strpos(strtolower($interface['name']), 'ether2') !== false ||
                    strpos(strtolower($interface['name']), 'bridge') !== false ||
                    (isset($interface['comment']) && strpos(strtolower($interface['comment']), 'lan') !== false)
                ) {
                    $isLan = true;
                }
                
                // Calculate current rates from monitor-traffic if available
                $rxRate = 0;
                $txRate = 0;
                
                foreach ($trafficMonitor as $monitor) {
                    if (isset($monitor['name']) && $monitor['name'] === $interface['name']) {
                        $rxRate = isset($monitor['rx-bits-per-second']) ? (int)$monitor['rx-bits-per-second'] : 0;
                        $txRate = isset($monitor['tx-bits-per-second']) ? (int)$monitor['tx-bits-per-second'] : 0;
                        break;
                    }
                }
                
                // Add interface to the list
                $networkStats['interfaces'][] = [
                    'name' => $interface['name'],
                    'type' => $interface['type'] ?? 'unknown',
                    'mac_address' => $interface['mac-address'] ?? '',
                    'rx_byte' => (int)$interface['rx-byte'],
                    'tx_byte' => (int)$interface['tx-byte'],
                    'rx_packet' => (int)($interface['rx-packet'] ?? 0),
                    'tx_packet' => (int)($interface['tx-packet'] ?? 0),
                    'rx_rate' => $rxRate, // Current rx rate in bits per second
                    'tx_rate' => $txRate, // Current tx rate in bits per second
                    'is_wan' => $isWan,
                    'is_lan' => $isLan,
                    'running' => isset($interface['running']) && $interface['running'] === 'true'
                ];
                
                // Add to totals
                $networkStats['total_rx'] += (int)$interface['rx-byte'];
                $networkStats['total_tx'] += (int)$interface['tx-byte'];
                
                // Add current rates for WAN interface
                if ($isWan) {
                    $networkStats['current_rx'] += $rxRate;
                    $networkStats['current_tx'] += $txRate;
                }
            }
        }
        
        // Get DHCP leases
        $dhcpLeases = $api->command('/ip/dhcp-server/lease/print');
        
        // Get PPP active connections
        $pppActive = $api->command('/ppp/active/print');
        
        // Get hotspot active users
        $hotspotActive = $api->command('/ip/hotspot/active/print');
        
        // Get connection tracking count
        $connectionCount = $api->commandWithParams('ip/firewall/connection/print', ['count-only' => 'true']);
        
        // Add client information
        $networkStats['clients'] = [
            'dhcp' => count($dhcpLeases),
            'ppp' => count($pppActive),
            'hotspot' => count($hotspotActive),
            'total' => count($dhcpLeases) + count($pppActive) + count($hotspotActive)
        ];
        
        // Add connection tracking
        $networkStats['connections'] = [
            'total' => is_numeric($connectionCount) ? (int)$connectionCount : count($api->command('/ip/firewall/connection/print'))
        ];
        
        // Disconnect from router
        $api->disconnect();
        
        // Return JSON response
        echo json_encode($networkStats);
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