<?php
/**
 * H4N5VS Mikrotik System Security
 * API Endpoint: Set Active Router
 */

// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Include functions
require_once '../includes/auth.php';
require_once '../includes/database.php';

// Check if user is authenticated
if (!is_authenticated()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Check if router_id is provided
if (!isset($_POST['router_id']) || !is_numeric($_POST['router_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Router ID is required'
    ]);
    exit;
}

$router_id = (int)$_POST['router_id'];

// Get router information
$router = get_router($router_id, true);

if (!$router) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Router not found'
    ]);
    exit;
}

// Set as active router
if (set_active_router($router_id)) {
    // Update session variables
    $_SESSION['router_id'] = $router_id;
    $_SESSION['mikrotik_ip'] = $router['ip_address'];
    $_SESSION['mikrotik_port'] = $router['port'];
    $_SESSION['mikrotik_username'] = $router['username'];
    $_SESSION['mikrotik_password'] = $router['password'];
    $_SESSION['use_ssl'] = $router['use_ssl'] ? true : false;
    $_SESSION['router_connected'] = false; // Will be verified when dashboard loads
    
    if (!empty($router['model'])) {
        $_SESSION['router_model'] = $router['model'];
    }
    
    if (!empty($router['version'])) {
        $_SESSION['router_version'] = $router['version'];
    }
    
    // Log the action
    log_router_activity($router_id, 'info', "Router set as active: " . $router['name']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Router set as active successfully',
        'router' => [
            'id' => $router['id'],
            'name' => $router['name'],
            'model' => $router['model'] ?? 'Unknown',
            'version' => $router['version'] ?? 'Unknown'
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to set router as active'
    ]);
}