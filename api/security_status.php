<?php
/**
 * H4N5VS Mikrotik System Security
 * API endpoint for security status information
 */

// Set content type to JSON
header('Content-Type: application/json');

// Start session
session_start();

// Include required files
require_once '../includes/functions.php';
require_once '../includes/routeros_api.php';
require_once '../includes/detection_engine.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Check if Mikrotik config is set
if (!isset($_SESSION['mikrotik_ip']) || !isset($_SESSION['mikrotik_username']) || !isset($_SESSION['mikrotik_password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Router configuration missing']);
    exit;
}

try {
    // Initialize RouterOS API
    $api = new RouterosAPI();
    $api->debug = false;
    
    // Set port and SSL if available in session
    if (isset($_SESSION['mikrotik_port'])) {
        $api->port = $_SESSION['mikrotik_port'];
    }
    
    if (isset($_SESSION['use_ssl'])) {
        $api->ssl = $_SESSION['use_ssl'];
    }
    
    // Connect to router
    if ($api->connect($_SESSION['mikrotik_ip'], $_SESSION['mikrotik_username'], $_SESSION['mikrotik_password'])) {
        // For demo mode, use sample data
        
        // Sample threats
        $threats = [
            [
                'id' => 'threat-001',
                'type' => 'TCP_SYN_FLOOD',
                'source_ip' => '95.142.192.14',
                'target_ip' => '192.168.1.1',
                'severity' => 'critical',
                'timestamp' => time() - 300,
                'details' => 'SYN flood attack detected with high packet rate'
            ],
            [
                'id' => 'threat-002',
                'type' => 'BOTNET',
                'source_ip' => '192.168.1.25',
                'target_ip' => '45.33.22.11',
                'severity' => 'high',
                'timestamp' => time() - 600,
                'details' => 'Botnet communication pattern detected to known C2 server'
            ]
        ];
        
        // Sample firewall rules
        $firewallRules = [
            ['chain' => 'input', 'action' => 'accept', 'protocol' => 'tcp', 'dst-port' => '22', 'comment' => 'Allow SSH'],
            ['chain' => 'input', 'action' => 'accept', 'protocol' => 'tcp', 'dst-port' => '80', 'comment' => 'Allow HTTP'],
            ['chain' => 'input', 'action' => 'accept', 'protocol' => 'tcp', 'dst-port' => '443', 'comment' => 'Allow HTTPS'],
            ['chain' => 'input', 'action' => 'drop', 'comment' => 'Drop all other input']
        ];
        
        // Sample address lists
        $addressLists = [
            ['list' => 'blacklist', 'address' => '95.142.192.14', 'comment' => 'SYN flood attacker', 'creation-time' => '2h12m14s'],
            ['list' => 'blacklist', 'address' => '77.88.32.45', 'comment' => 'Port scanner', 'creation-time' => '10h2m33s'],
            ['list' => 'botnet', 'address' => '192.168.1.25', 'comment' => 'Botnet infected device', 'creation-time' => '45m8s']
        ];
        
        // Include functions.php if not already included
        if (!function_exists('analyzeFirewallSecurityRules')) {
            require_once __DIR__ . '/../includes/functions.php';
        }
        
        // Analyze firewall rules
        $securityAnalysis = analyzeFirewallSecurityRules($firewallRules);
        
        // Create security status response
        $securityStatus = [
            'status' => 'secure',  // Default status
            'threats' => $threats,
            'threat_count' => count($threats),
            'security_score' => $securityAnalysis['score'],
            'security_level' => getSecurityLevel($securityAnalysis['score']),
            'security_recommendations' => $securityAnalysis['recommendations'] ?? [],
            'firewall_rules_count' => count($firewallRules),
            'address_lists_count' => count($addressLists),
            'blocked_ips' => getBlockedIPs($addressLists),
            'recent_security_events' => processSecurityLogs($logs)
        ];
        
        // Update security status based on threats
        if (count($threats) > 0) {
            $securityStatus['status'] = 'threats_detected';
            
            // Check for critical threats
            foreach ($threats as $threat) {
                if ($threat['severity'] === 'critical') {
                    $securityStatus['status'] = 'critical';
                    break;
                }
            }
        }
        
        // Disconnect from router
        $api->disconnect();
        
        // Return JSON response
        echo json_encode($securityStatus);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Could not connect to the router']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}

// This function was moved to includes/functions.php to avoid duplication

// These functions were moved to includes/functions.php to avoid duplication