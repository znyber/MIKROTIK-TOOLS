<?php
/**
 * H4N5VS Mikrotik System Security
 * API endpoint for system information
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
        // Get system resources
        $resources = $api->command('/system/resource/print');
        
        // Get CPU load
        $cpuLoad = $api->command('/system/resource/cpu/print');
        
        // Get identity
        $identity = $api->command('/system/identity/print');
        
        // Get license
        $license = $api->command('/system/license/print');
        
        // Get health data (if available)
        $health = $api->command('/system/health/print');
        
        // Get connected PPP clients
        $pppClients = $api->command('/ppp/active/print');
        
        // Get connected hotspot users
        $hotspotUsers = $api->command('/ip/hotspot/active/print');
        
        // Get all interface statistics
        $interfaces = $api->commandWithParams('interface/print', ['stats' => 'true']);
        
        // Process ethernet interfaces for traffic data
        $wan = null;
        $lan = null;
        
        foreach ($interfaces as $interface) {
            // Look for WAN interface
            if (
                isset($interface['name']) && 
                (
                    strpos(strtolower($interface['name']), 'wan') !== false || 
                    strpos(strtolower($interface['name']), 'ether1') !== false ||
                    strpos(strtolower($interface['name']), 'pppoe-out') !== false ||
                    (isset($interface['comment']) && strpos(strtolower($interface['comment']), 'wan') !== false)
                )
            ) {
                $wan = $interface;
            }
            
            // Look for LAN interface
            if (
                isset($interface['name']) && 
                (
                    strpos(strtolower($interface['name']), 'lan') !== false || 
                    strpos(strtolower($interface['name']), 'ether2') !== false ||
                    strpos(strtolower($interface['name']), 'bridge') !== false ||
                    (isset($interface['comment']) && strpos(strtolower($interface['comment']), 'lan') !== false)
                )
            ) {
                $lan = $interface;
            }
        }
        
        // Prepare response data
        $systemInfo = [
            'status' => 'online',
            'identity' => $identity[0]['name'] ?? 'Mikrotik Router',
            'model' => $resources[0]['board-name'] ?? 'Unknown Model',
            'architecture' => $resources[0]['architecture-name'] ?? 'Unknown',
            'version' => $resources[0]['version'] ?? 'Unknown',
            'uptime' => $resources[0]['uptime'] ?? '0s',
            'cpu_model' => $resources[0]['cpu'] ?? 'Unknown CPU',
            'cpu_count' => $resources[0]['cpu-count'] ?? 1,
            'cpu_load' => $resources[0]['cpu-load'] ?? 0,
            'memory_total' => $resources[0]['total-memory'] ?? 0,
            'memory_used' => $resources[0]['total-memory'] - ($resources[0]['free-memory'] ?? 0),
            'memory_free' => $resources[0]['free-memory'] ?? 0,
            'disk_total' => $resources[0]['total-hdd-space'] ?? 0,
            'disk_used' => $resources[0]['total-hdd-space'] - ($resources[0]['free-hdd-space'] ?? 0),
            'disk_free' => $resources[0]['free-hdd-space'] ?? 0,
            'temperature' => isset($health[0]['temperature']) ? (float)$health[0]['temperature'] : null,
            'voltage' => isset($health[0]['voltage']) ? (float)$health[0]['voltage'] : null,
            'clients' => [
                'ppp' => count($pppClients),
                'hotspot' => count($hotspotUsers),
                'total' => count($pppClients) + count($hotspotUsers)
            ],
            'interfaces' => [
                'wan' => $wan ? [
                    'name' => $wan['name'],
                    'type' => $wan['type'] ?? 'ethernet',
                    'status' => isset($wan['running']) && $wan['running'] === 'true' ? 'up' : 'down',
                    'traffic' => [
                        'rx_byte' => isset($wan['rx-byte']) ? (int)$wan['rx-byte'] : 0,
                        'tx_byte' => isset($wan['tx-byte']) ? (int)$wan['tx-byte'] : 0,
                        'rx_packet' => isset($wan['rx-packet']) ? (int)$wan['rx-packet'] : 0,
                        'tx_packet' => isset($wan['tx-packet']) ? (int)$wan['tx-packet'] : 0
                    ]
                ] : null,
                'lan' => $lan ? [
                    'name' => $lan['name'],
                    'type' => $lan['type'] ?? 'ethernet',
                    'status' => isset($lan['running']) && $lan['running'] === 'true' ? 'up' : 'down',
                    'traffic' => [
                        'rx_byte' => isset($lan['rx-byte']) ? (int)$lan['rx-byte'] : 0,
                        'tx_byte' => isset($lan['tx-byte']) ? (int)$lan['tx-byte'] : 0,
                        'rx_packet' => isset($lan['rx-packet']) ? (int)$lan['rx-packet'] : 0,
                        'tx_packet' => isset($lan['tx-packet']) ? (int)$lan['tx-packet'] : 0
                    ]
                ] : null
            ],
            'cores' => []
        ];
        
        // Add CPU core data if available
        if (!empty($cpuLoad)) {
            foreach ($cpuLoad as $core) {
                $systemInfo['cores'][] = [
                    'id' => $core['.id'] ?? 0,
                    'load' => $core['load'] ?? 0
                ];
            }
        }
        
        // Disconnect from router
        $api->disconnect();
        
        // Return JSON response
        echo json_encode($systemInfo);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Could not connect to the router']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'status' => 'offline'
    ]);
}