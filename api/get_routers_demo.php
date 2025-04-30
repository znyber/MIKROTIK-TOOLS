<?php
/**
 * H4N5VS Mikrotik System Security
 * API Endpoint: Get Routers Demo
 * This is a demo endpoint that simulates router list data
 */

// Set content type to JSON
header('Content-Type: application/json');

// Generate demo router list
$routers = [
    [
        'id' => 1,
        'name' => 'Main Office Router',
        'ip' => '192.168.1.1',
        'username' => 'admin',
        'model' => 'hAP ac²',
        'active' => true,
        'status' => 'connected'
    ],
    [
        'id' => 2,
        'name' => 'Branch Office',
        'ip' => '192.168.2.1',
        'username' => 'admin',
        'model' => 'RB750Gr3',
        'active' => false,
        'status' => 'connected'
    ],
    [
        'id' => 3,
        'name' => 'Warehouse',
        'ip' => '192.168.3.1',
        'username' => 'admin',
        'model' => 'RB3011UiAS',
        'active' => false,
        'status' => 'connected'
    ]
];

// Set active router
$activeRouter = 1;

// Return the router list as JSON
echo json_encode([
    'success' => true,
    'routers' => $routers,
    'active_router' => $activeRouter
]);