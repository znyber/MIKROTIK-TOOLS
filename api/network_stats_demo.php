<?php
/**
 * H4N5VS Mikrotik System Security
 * API endpoint for network statistics - Demo version
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
    // Generate sample network stats data
    $interfaces = [
        [
            'name' => 'ether1',
            'status' => 'up',
            'rx_rate' => rand(5, 50) . ' Mbps',
            'tx_rate' => rand(1, 20) . ' Mbps',
            'rx_total' => rand(10, 50) . ' GB',
            'tx_total' => rand(5, 25) . ' GB'
        ],
        [
            'name' => 'ether2',
            'status' => 'up',
            'rx_rate' => rand(1, 10) . ' Mbps',
            'tx_rate' => rand(0, 5) . ' Mbps',
            'rx_total' => rand(2, 10) . ' GB',
            'tx_total' => rand(1, 5) . ' GB'
        ],
        [
            'name' => 'sfp1',
            'status' => 'up',
            'rx_rate' => rand(10, 100) . ' Mbps',
            'tx_rate' => rand(5, 50) . ' Mbps',
            'rx_total' => rand(50, 150) . ' GB',
            'tx_total' => rand(25, 75) . ' GB'
        ]
    ];
    
    // Return JSON response
    echo json_encode([
        'interfaces' => $interfaces,
        'total_rx' => rand(15, 160) . ' Mbps',
        'total_tx' => rand(5, 75) . ' Mbps',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
