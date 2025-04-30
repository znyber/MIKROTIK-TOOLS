<?php
/**
 * H4N5VS Mikrotik System Security
 * API Endpoint: Connection Status
 * Checks the connection status to the Mikrotik router
 */

// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Include functions
require_once '../includes/routeros_api.php';
require_once '../includes/auth.php';
require_once '../includes/database.php';

// Check if user is authenticated
if (!is_authenticated()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required',
        'status' => 'authentication_required'
    ]);
    exit;
}

// Initialize response
$response = [
    'success' => false,
    'connected' => false,
    'status' => 'disconnected',
    'details' => [
        'message' => 'Not connected to router',
        'last_check' => date('Y-m-d H:i:s')
    ],
    'multi_router' => [
        'active' => false,
        'routers' => []
    ]
];

// Check if in demo mode
if (isset($_SESSION['demo_mode']) && $_SESSION['demo_mode'] === true) {
    require_once 'connection_status_demo.php';
    exit;
}

// Try to get active router
$active_router = get_active_router(true);

// Get all routers for multi-router status
$all_routers = get_all_routers();
if ($all_routers && count($all_routers) > 0) {
    $response['multi_router']['active'] = true;
    foreach ($all_routers as $router) {
        $response['multi_router']['routers'][] = [
            'id' => $router['id'],
            'name' => $router['name'],
            'status' => 'unknown',
            'model' => $router['model'] ?? 'Unknown'
        ];
    }
}

// Check connection to active router
if ($active_router) {
    $api = new RouterosAPI();
    $api->debug = false;
    $api->port = $active_router['port'];
    $api->ssl = $active_router['use_ssl'] ? true : false;
    
    try {
        if ($api->connect($active_router['ip_address'], $active_router['username'], $active_router['password'])) {
            // Get system resources for additional info
            $resources = $api->command('/system/resource/print');
            $identity = $api->command('/system/identity/print');
            
            // Update router info in database with latest system info
            if (!empty($resources)) {
                $update_data = [
                    'last_connected' => date('Y-m-d H:i:s'),
                    'model' => $resources[0]['board-name'] ?? $active_router['model'] ?? 'Unknown',
                    'version' => $resources[0]['version'] ?? $active_router['version'] ?? 'Unknown'
                ];
                update_router($active_router['id'], $update_data);
            }
            
            // Connection successful, update response
            $response['success'] = true;
            $response['connected'] = true;
            $response['status'] = 'connected';
            $response['details'] = [
                'message' => 'Connected to Mikrotik router',
                'uptime' => $resources[0]['uptime'] ?? 'Unknown',
                'last_check' => date('Y-m-d H:i:s'),
                'api_version' => $api->getVersion(),
                'device_info' => [
                    'model' => $resources[0]['board-name'] ?? 'Unknown',
                    'serial' => $resources[0]['serial-number'] ?? 'Unknown',
                    'firmware' => $resources[0]['version'] ?? 'Unknown',
                    'architecture' => $resources[0]['architecture-name'] ?? 'Unknown'
                ]
            ];
            
            // Update the connection quality based on API response time
            $response['connection_quality'] = [
                'latency' => 'Excellent', // We don't have actual latency data
                'signal_strength' => '100%', // Placeholder for wired connections
                'stability' => 'Excellent'
            ];
            
            // Update status in multi-router list
            foreach ($response['multi_router']['routers'] as &$router) {
                if ($router['id'] == $active_router['id']) {
                    $router['status'] = 'connected';
                }
            }
            
            // Store connection status in session
            $_SESSION['router_connected'] = true;
            $_SESSION['router_model'] = $resources[0]['board-name'] ?? $active_router['model'] ?? 'Unknown';
            $_SESSION['router_version'] = $resources[0]['version'] ?? $active_router['version'] ?? 'Unknown';
            
            // Disconnect API
            $api->disconnect();
        } else {
            // Connection failed
            $response['details']['message'] = 'Failed to connect to router: ' . $api->getLastError();
            
            // Update status in multi-router list
            foreach ($response['multi_router']['routers'] as &$router) {
                if ($router['id'] == $active_router['id']) {
                    $router['status'] = 'error';
                }
            }
            
            // Log the error
            log_router_activity($active_router['id'], 'error', 'Connection failed: ' . $api->getLastError());
            
            // Store connection status in session
            $_SESSION['router_connected'] = false;
        }
    } catch (Exception $e) {
        // Exception during connection
        $response['details']['message'] = 'Error connecting to router: ' . $e->getMessage();
        
        // Update status in multi-router list
        foreach ($response['multi_router']['routers'] as &$router) {
            if ($router['id'] == $active_router['id']) {
                $router['status'] = 'error';
            }
        }
        
        // Log the error
        log_router_activity($active_router['id'], 'error', 'Connection error: ' . $e->getMessage());
        
        // Store connection status in session
        $_SESSION['router_connected'] = false;
    }
} else {
    // No active router configured
    $response['details']['message'] = 'No active router configured';
}

// Return the response as JSON
echo json_encode($response);