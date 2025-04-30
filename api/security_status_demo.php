<?php
/**
 * H4N5VS Mikrotik System Security
 * API Endpoint: Security Status Demo
 * This is a demo endpoint that simulates security status information
 */

// Set content type to JSON
header('Content-Type: application/json');

// Define threat types and severity levels
$threatTypes = [
    'ddos' => 'DDoS Attack',
    'port_scan' => 'Port Scan',
    'brute_force' => 'Brute Force',
    'ip_spoofing' => 'IP Spoofing',
    'dns_flood' => 'DNS Flood',
    'syn_flood' => 'SYN Flood',
    'botnet' => 'Botnet Activity'
];

$severityLevels = ['low', 'medium', 'high', 'critical'];
$sourceIPs = [];

// Generate some random IPs
for ($i = 0; $i < 10; $i++) {
    $sourceIPs[] = rand(1, 223) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 254);
}

// Add some known malicious IPs for realism
$sourceIPs[] = '203.0.113.54';
$sourceIPs[] = '198.51.100.23';
$sourceIPs[] = '192.0.2.78';

// Generate some demo threats
$activeThreats = [];
$threatCount = rand(0, 3);
for ($i = 0; $i < $threatCount; $i++) {
    $type = array_rand($threatTypes);
    $severity = $severityLevels[array_rand($severityLevels)];
    $sourceIP = $sourceIPs[array_rand($sourceIPs)];
    $timestamp = time() - rand(0, 3600);
    
    $threatDetails = [];
    switch ($type) {
        case 'ddos':
            $threatDetails = [
                'duration' => rand(1, 30) . ' minutes',
                'bandwidth' => rand(50, 500) . ' Mbps',
                'packet_rate' => rand(100000, 1000000) . ' pps',
                'attack_vector' => ['UDP', 'TCP SYN', 'ICMP'][array_rand(['UDP', 'TCP SYN', 'ICMP'])],
                'targeted_ports' => [rand(1, 1024), rand(1025, 65535)]
            ];
            break;
        default:
            $threatDetails = [
                'duration' => rand(1, 30) . ' minutes',
                'severity' => rand(1, 10) . '/10',
                'packets' => rand(1000, 100000)
            ];
    }
    
    // Generate affected systems
    $systems = ['Web Server', 'DNS Server', 'Mail Server', 'Database Server', 'Client Network', 'Firewall'];
    $affectedCount = rand(1, 3);
    $affectedSystems = [];
    for ($j = 0; $j < $affectedCount; $j++) {
        $affectedSystems[] = $systems[array_rand($systems)];
    }
    $affectedSystems = array_unique($affectedSystems);
    
    // Generate recommended actions
    $actions = [
        'Block source IP at the firewall',
        'Update firewall rules',
        'Enable rate limiting',
        'Contact ISP for upstream filtering',
        'Implement anti-DDoS services'
    ];
    shuffle($actions);
    $recommendedActions = array_slice($actions, 0, rand(2, 4));
    
    $activeThreats[] = [
        'id' => 'threat-' . uniqid(),
        'type' => $type,
        'name' => $threatTypes[$type],
        'source_ip' => $sourceIP,
        'timestamp' => date('Y-m-d H:i:s', $timestamp),
        'severity' => $severity,
        'status' => 'active',
        'details' => $threatDetails,
        'affected_systems' => $affectedSystems,
        'recommended_actions' => $recommendedActions
    ];
}

// Generate some mitigated threats
$mitigatedThreats = [];
$mitigatedCount = rand(1, 5);
for ($i = 0; $i < $mitigatedCount; $i++) {
    $type = array_rand($threatTypes);
    $severity = $severityLevels[array_rand($severityLevels)];
    $sourceIP = $sourceIPs[array_rand($sourceIPs)];
    $timestamp = time() - rand(3600, 86400);
    
    $threatDetails = [
        'duration' => rand(1, 30) . ' minutes',
        'severity' => rand(1, 10) . '/10',
        'packets' => rand(1000, 100000)
    ];
    
    // Generate affected systems
    $systems = ['Web Server', 'DNS Server', 'Mail Server', 'Client Network'];
    $affectedCount = rand(1, 2);
    $affectedSystems = [];
    for ($j = 0; $j < $affectedCount; $j++) {
        $affectedSystems[] = $systems[array_rand($systems)];
    }
    $affectedSystems = array_unique($affectedSystems);
    
    // Generate recommended actions
    $actions = [
        'Block source IP at the firewall',
        'Update firewall rules',
        'Enable rate limiting'
    ];
    
    $mitigatedThreats[] = [
        'id' => 'threat-' . uniqid(),
        'type' => $type,
        'name' => $threatTypes[$type],
        'source_ip' => $sourceIP,
        'timestamp' => date('Y-m-d H:i:s', $timestamp),
        'severity' => $severity,
        'status' => 'mitigated',
        'details' => $threatDetails,
        'affected_systems' => $affectedSystems,
        'recommended_actions' => $actions
    ];
}

// Generate blocked IPs
$blockedIPs = [];
$blockedIPCount = rand(30, 100);
for ($i = 0; $i < $blockedIPCount; $i++) {
    $blockedIPs[] = [
        'ip' => rand(1, 223) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 254),
        'reason' => ['Brute Force', 'Port Scan', 'DDoS Source', 'Malicious Activity'][array_rand(['Brute Force', 'Port Scan', 'DDoS Source', 'Malicious Activity'])],
        'time' => date('Y-m-d H:i:s', time() - rand(60, 86400)),
        'expires' => date('Y-m-d H:i:s', time() + rand(3600, 86400))
    ];
}

$securityStatus = [
    'success' => true,
    'status' => [
        'overall_status' => $threatCount > 0 ? 'threats_detected' : 'secure',
        'security_score' => rand(50, 100),
        'last_scan' => date('Y-m-d H:i:s', time() - rand(60, 3600)),
        'threats_detected' => $threatCount,
        'threats_mitigated' => $mitigatedCount,
        'protected_services' => rand(5, 15)
    ],
    'protections' => [
        'ddos_protection' => [
            'enabled' => true,
            'level' => ['Low', 'Medium', 'High'][array_rand(['Low', 'Medium', 'High'])],
            'last_attack' => date('Y-m-d H:i:s', time() - rand(3600, 604800)),
            'status' => 'active'
        ],
        'firewall' => [
            'enabled' => true,
            'rules_count' => rand(10, 50),
            'last_update' => date('Y-m-d H:i:s', time() - rand(3600, 604800)),
            'status' => 'active'
        ],
        'brute_force' => [
            'enabled' => true,
            'attempts_blocked' => rand(10, 100),
            'ips_blacklisted' => rand(5, 30),
            'status' => 'active'
        ],
        'ip_filtering' => [
            'enabled' => true,
            'blacklisted' => rand(10, 100),
            'whitelisted' => rand(5, 20),
            'status' => 'active'
        ],
        'botnet_protection' => [
            'enabled' => true,
            'database_version' => '2024.4.' . rand(1, 30),
            'connections_blocked' => rand(0, 50),
            'status' => 'active'
        ]
    ],
    'active_threats' => $activeThreats,
    'recent_mitigations' => $mitigatedThreats,
    'blocked_ips' => $blockedIPs
];

// Return the security status as JSON
echo json_encode($securityStatus);