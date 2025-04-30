<?php
/**
 * H4N5VS Mikrotik System Security
 * API endpoint for live system logs
 */

// Set content type to JSON
header('Content-Type: application/json');

// Start session
session_start();

// Include required files
require_once '../includes/functions.php';
require_once '../includes/routeros_api.php';

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
    // For demo purposes, we'll simulate live logs
    // In a real environment, this would connect to the RouterOS API
    
    // Define possible log types
    $logTypes = ['system', 'firewall', 'critical', 'error', 'warning', 'info', 'debug'];
    
    // Define possible log topics
    $logTopics = [
        'system' => ['system', 'router', 'dhcp', 'dns', 'hotspot', 'interface', 'ppp', 'script', 'vpn', 'wireless', 'update'],
        'firewall' => ['filter', 'nat', 'mangle', 'raw', 'connection', 'forward', 'drop', 'reject', 'accept'],
        'critical' => ['intrusion', 'attack', 'exploit', 'trojan', 'malware', 'botnet'],
        'error' => ['fail', 'timeout', 'overflow', 'reset', 'disconnect'],
        'warning' => ['attempt', 'limit', 'reach', 'threshold', 'slow'],
        'info' => ['connect', 'start', 'stop', 'login', 'logout', 'reboot', 'shutdown'],
        'debug' => ['trace', 'debug', 'packet', 'detail', 'test']
    ];
    
    // Define possible IP addresses
    $ipAddresses = [
        '192.168.1.' . rand(2, 254),
        '192.168.1.' . rand(2, 254),
        '10.0.0.' . rand(2, 254),
        '172.16.0.' . rand(2, 254),
        rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255)
    ];
    
    // Generate random log entries
    $logEntries = [];
    $entryCount = rand(8, 15);
    
    // Get timestamp range (last hour)
    $now = time();
    $oneHourAgo = $now - 3600;
    
    for ($i = 0; $i < $entryCount; $i++) {
        // Random timestamp within the last hour
        $timestamp = date('Y-m-d H:i:s', rand($oneHourAgo, $now));
        
        // Random log type
        $logType = $logTypes[array_rand($logTypes)];
        
        // Random topic from the selected type
        $topic = $logTopics[$logType][array_rand($logTopics[$logType])];
        
        // Random IP address
        $ipAddress = $ipAddresses[array_rand($ipAddresses)];
        
        // Generate log message based on type and topic
        $message = '';
        
        switch ($logType) {
            case 'system':
                $messages = [
                    "Router configuration changed by admin",
                    "System restart initiated",
                    "Interface $topic status changed",
                    "DNS server reconfigured",
                    "DHCP lease assigned to $ipAddress"
                ];
                $message = $messages[array_rand($messages)];
                break;
                
            case 'firewall':
                $messages = [
                    "Blocked connection from $ipAddress to port " . rand(1, 65535),
                    "New connection established from $ipAddress",
                    "NAT rule applied for traffic from $ipAddress",
                    "Firewall rule #" . rand(1, 20) . " matched for $ipAddress",
                    "Packet dropped by forward chain rule #" . rand(1, 20)
                ];
                $message = $messages[array_rand($messages)];
                break;
                
            case 'critical':
                $messages = [
                    "Possible $topic detected from $ipAddress",
                    "Large number of connections from $ipAddress detected",
                    "Multiple login failures from $ipAddress",
                    "Suspicious traffic pattern from $ipAddress",
                    "Port scan detected from $ipAddress"
                ];
                $message = $messages[array_rand($messages)];
                break;
                
            case 'error':
                $messages = [
                    "Connection $topic with $ipAddress",
                    "DNS lookup $topic for $ipAddress",
                    "Service restart after $topic",
                    "Authentication $topic from $ipAddress",
                    "DHCP server $topic"
                ];
                $message = $messages[array_rand($messages)];
                break;
                
            case 'warning':
                $messages = [
                    "CPU load reaching $topic",
                    "Memory usage above $topic",
                    "Connection $topic reached for $ipAddress",
                    "Login $topic from $ipAddress",
                    "Bandwidth $topic exceeded on interface"
                ];
                $message = $messages[array_rand($messages)];
                break;
                
            case 'info':
                $messages = [
                    "User admin $topic from $ipAddress",
                    "System $topic scheduled",
                    "Service $topic completed",
                    "Interface eth1 $topic",
                    "Backup $topic successfully"
                ];
                $message = $messages[array_rand($messages)];
                break;
                
            case 'debug':
                $messages = [
                    "$topic data for interface eth1",
                    "Connection $topic for $ipAddress",
                    "Routing $topic information",
                    "Firewall rule $topic details",
                    "Configuration $topic output"
                ];
                $message = $messages[array_rand($messages)];
                break;
        }
        
        // Add log entry
        $logEntries[] = [
            'timestamp' => $timestamp,
            'type' => $logType,
            'topic' => $topic,
            'message' => $message
        ];
    }
    
    // Sort log entries by timestamp (newest first)
    usort($logEntries, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    // Return response
    echo json_encode([
        'logs' => $logEntries,
        'count' => count($logEntries),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}