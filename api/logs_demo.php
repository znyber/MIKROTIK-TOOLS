<?php
/**
 * H4N5VS Mikrotik System Security
 * API Endpoint: Logs Demo
 * This is a demo endpoint that simulates log data
 */

// Set content type to JSON
header('Content-Type: application/json');

// Sample log messages
$logMessages = [
    [
        'time' => date('Y-m-d H:i:s', time() - rand(60, 3600)),
        'level' => 'info',
        'source' => 'system',
        'message' => 'System started successfully'
    ],
    [
        'time' => date('Y-m-d H:i:s', time() - rand(60, 3600)),
        'level' => 'info',
        'source' => 'connection',
        'message' => 'Connection established with router ID #1'
    ],
    [
        'time' => date('Y-m-d H:i:s', time() - rand(60, 3600)),
        'level' => 'warning',
        'source' => 'security',
        'message' => 'Multiple failed login attempts detected from IP 192.168.1.' . rand(2, 254)
    ],
    [
        'time' => date('Y-m-d H:i:s', time() - rand(60, 3600)),
        'level' => 'info',
        'source' => 'config',
        'message' => 'Configuration updated for firewall rules'
    ],
    [
        'time' => date('Y-m-d H:i:s', time() - rand(60, 3600)),
        'level' => 'error',
        'source' => 'connections',
        'message' => 'Connection dropped for client 10.0.0.' . rand(2, 254)
    ],
    [
        'time' => date('Y-m-d H:i:s', time() - rand(60, 3600)),
        'level' => 'warning',
        'source' => 'traffic',
        'message' => 'Unusual traffic pattern detected on interface ether1'
    ],
    [
        'time' => date('Y-m-d H:i:s', time() - rand(60, 3600)),
        'level' => 'info',
        'source' => 'user',
        'message' => 'User admin logged in successfully'
    ],
    [
        'time' => date('Y-m-d H:i:s', time() - rand(60, 3600)),
        'level' => 'error',
        'source' => 'dhcp',
        'message' => 'DHCP server failed to assign IP to client mac 00:11:22:33:44:' . sprintf('%02X', rand(0, 255))
    ],
    [
        'time' => date('Y-m-d H:i:s', time() - rand(60, 3600)),
        'level' => 'warning',
        'source' => 'security',
        'message' => 'Potential port scan detected from IP 203.0.113.' . rand(1, 254)
    ],
    [
        'time' => date('Y-m-d H:i:s', time() - rand(60, 3600)),
        'level' => 'info',
        'source' => 'system',
        'message' => 'CPU usage spike detected, now at ' . rand(50, 95) . '%'
    ]
];

// Sort logs by time (newest first)
usort($logMessages, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

// Return the logs as JSON
echo json_encode([
    'success' => true,
    'logs' => $logMessages
]);