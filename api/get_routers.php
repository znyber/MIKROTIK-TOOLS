<?php
/**
 * H4N5VS Mikrotik System Security
 * API Endpoint: Get Routers
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

// Get all routers from database
$routers = get_all_routers();

if ($routers === false) {
    // If there was an error fetching routers
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch routers from database',
        'routers' => []
    ]);
} else {
    // Return the router list
    echo json_encode([
        'success' => true,
        'count' => count($routers),
        'routers' => $routers
    ]);
}