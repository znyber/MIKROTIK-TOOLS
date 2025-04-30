<?php
/**
 * H4N5VS Mikrotik System Security
 * API Endpoint: Connection Status Demo
 * This is a demo endpoint that simulates connection status for the Mikrotik router
 */

// Set content type to JSON
header('Content-Type: application/json');

// Generate demo data for connection status
$status = [
    'success' => true,
    'connected' => true,
    'status' => 'connected',
    'details' => [
        'message' => 'Connected to Mikrotik router',
        'uptime' => rand(3600, 86400 * 30), // Between 1 hour and 30 days in seconds
        'last_check' => date('Y-m-d H:i:s'),
        'api_version' => '6.4' . rand(0, 9),
        'device_info' => [
            'model' => 'hAP ac²',
            'serial' => 'HB8F' . rand(1000, 9999) . rand(1000, 9999),
            'firmware' => '6.48.6',
            'architecture' => 'arm'
        ]
    ],
    'connection_quality' => [
        'latency' => rand(1, 10) . 'ms',
        'signal_strength' => rand(80, 95) . '%',
        'stability' => 'excellent'
    ],
    'multi_router' => [
        'active' => true,
        'routers' => [
            [
                'id' => 1,
                'name' => 'Main Office Router',
                'status' => 'connected',
                'model' => 'hAP ac²'
            ],
            [
                'id' => 2,
                'name' => 'Branch Office',
                'status' => 'connected',
                'model' => 'RB750Gr3'
            ],
            [
                'id' => 3,
                'name' => 'Warehouse',
                'status' => 'connected',
                'model' => 'RB3011UiAS'
            ]
        ]
    ]
];

// Occasionally show a disconnected status to make the demo more realistic
if (rand(1, 20) === 1) {
    $status['connected'] = false;
    $status['status'] = 'disconnected';
    $status['details']['message'] = 'Connection to router failed';
    $status['connection_quality']['latency'] = 'N/A';
    $status['connection_quality']['signal_strength'] = 'N/A';
    $status['connection_quality']['stability'] = 'poor';
    
    // Randomly set one of the routers as disconnected
    $randomRouter = rand(0, 2);
    $status['multi_router']['routers'][$randomRouter]['status'] = 'disconnected';
}

// Return the demo data as JSON
echo json_encode($status);