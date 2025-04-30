<?php
/**
 * H4N5VS Mikrotik System Security
 * API Endpoint: System Info Demo
 * This is a demo endpoint that simulates system information
 */

// Set content type to JSON
header('Content-Type: application/json');

// Generate demo system information
$systemInfo = [
    'success' => true,
    'cpu' => [
        'load' => rand(10, 50),
        'cores' => 4,
        'temperature' => rand(40, 60) . '°C',
        'frequency' => '1.2 GHz'
    ],
    'memory' => [
        'total' => 512, // MB
        'used' => rand(100, 300), // MB
        'percentage' => rand(20, 60),
        'free' => rand(200, 400) // MB
    ],
    'storage' => [
        'total' => 16384, // MB (16GB)
        'used' => rand(5000, 10000), // MB
        'percentage' => rand(30, 60),
        'free' => rand(6000, 11000) // MB
    ],
    'uptime' => [
        'seconds' => rand(3600, 2592000), // Between 1 hour and 30 days
        'formatted' => sprintf('%dd %dh %dm %ds', 
                             rand(0, 30), // days
                             rand(0, 23), // hours
                             rand(0, 59), // minutes
                             rand(0, 59)  // seconds
                     )
    ],
    'interfaces' => [
        [
            'name' => 'ether1',
            'type' => 'ethernet',
            'status' => 'up',
            'mac_address' => '00:0C:' . sprintf('%02X', rand(0, 255)) . ':' . 
                             sprintf('%02X', rand(0, 255)) . ':' . 
                             sprintf('%02X', rand(0, 255)) . ':' . 
                             sprintf('%02X', rand(0, 255)),
            'data_sent' => rand(1000000, 100000000), // Bytes
            'data_received' => rand(10000000, 1000000000) // Bytes
        ],
        [
            'name' => 'ether2',
            'type' => 'ethernet',
            'status' => 'up',
            'mac_address' => '00:0C:' . sprintf('%02X', rand(0, 255)) . ':' . 
                             sprintf('%02X', rand(0, 255)) . ':' . 
                             sprintf('%02X', rand(0, 255)) . ':' . 
                             sprintf('%02X', rand(0, 255)),
            'data_sent' => rand(100000, 10000000), // Bytes
            'data_received' => rand(1000000, 100000000) // Bytes
        ],
        [
            'name' => 'wlan1',
            'type' => 'wireless',
            'status' => 'up',
            'mac_address' => '00:0C:' . sprintf('%02X', rand(0, 255)) . ':' . 
                             sprintf('%02X', rand(0, 255)) . ':' . 
                             sprintf('%02X', rand(0, 255)) . ':' . 
                             sprintf('%02X', rand(0, 255)),
            'data_sent' => rand(100000, 10000000), // Bytes
            'data_received' => rand(1000000, 100000000), // Bytes
            'clients' => rand(1, 20),
            'channel' => rand(1, 13),
            'frequency' => '2.4 GHz'
        ]
    ],
    'version' => [
        'router_os' => '6.4' . rand(0, 9),
        'firmware' => '6.' . rand(40, 49) . '.' . rand(1, 9),
        'hardware' => 'hAP ac²',
        'architecture' => 'arm',
        'build_time' => date('Y-m-d H:i:s', time() - rand(86400, 2592000))
    ],
    'network' => [
        'download_speed' => rand(20, 100), // Mbps
        'upload_speed' => rand(5, 50), // Mbps
        'active_connections' => rand(50, 200),
        'packets_per_second' => rand(1000, 5000)
    ]
];

// Return system info as JSON
echo json_encode($systemInfo);