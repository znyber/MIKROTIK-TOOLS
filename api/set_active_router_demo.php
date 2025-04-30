<?php
/**
 * H4N5VS Mikrotik System Security
 * API Endpoint: Set Active Router Demo
 * This is a demo endpoint that simulates setting the active router
 */

// Set content type to JSON
header('Content-Type: application/json');

// Check if router_id is provided
if (!isset($_POST['router_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Router ID is required'
    ]);
    exit;
}

$routerId = intval($_POST['router_id']);

// Simulate changing the active router
echo json_encode([
    'success' => true,
    'message' => 'Active router changed successfully',
    'router_id' => $routerId
]);