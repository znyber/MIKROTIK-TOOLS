<?php
/**
 * H4N5VS Mikrotik System Security
 * API endpoint for active connections - Demo version
 */

// Set content type to JSON
header('Content-Type: application/json');

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

try {
    // Generate some random connection counts
    $tcp_connections = rand(80, 250);
    $udp_connections = rand(20, 80);
    $other_connections = rand(5, 20);
    $total_connections = $tcp_connections + $udp_connections + $other_connections;
    
    // Return JSON response
    echo json_encode([
        'total_connections' => $total_connections,
        'tcp_connections' => $tcp_connections,
        'udp_connections' => $udp_connections,
        'other_connections' => $other_connections,
        'connection_list' => [
            // Would include detailed connection list here in real version
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
