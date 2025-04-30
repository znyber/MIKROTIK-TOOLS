<?php
/**
 * H4N5VS Mikrotik System Security
 * API endpoint for router logs
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

// Get filter parameters
$topics = isset($_GET['topics']) ? $_GET['topics'] : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    // For demo mode, use sample logs directly
    $useDemoMode = true; // Set to false for actual RouterOS API usage
    
    // Sample logs for demo mode
    $sampleLogs = [
        ['time' => '12:45:15', 'topics' => 'system', 'message' => 'System started monitoring interface ether1'],
        ['time' => '12:48:03', 'topics' => 'system,auth', 'message' => 'User admin logged in from 192.168.1.100'],
        ['time' => '12:51:22', 'topics' => 'system,interface', 'message' => 'Interface ether1 state changed to connected'],
        ['time' => '12:52:15', 'topics' => 'firewall,warning', 'message' => 'DDoS attack detected from 95.142.192.14, SYN flood'],
        ['time' => '12:55:33', 'topics' => 'firewall', 'message' => 'Firewall: IP 95.142.192.14 added to address-list "blacklist"'],
        ['time' => '13:01:19', 'topics' => 'system', 'message' => 'New node found: ID "MikroTik"'],
        ['time' => '13:18:02', 'topics' => 'security,warning', 'message' => 'Botnet communication detected from 192.168.1.25'],
        ['time' => '13:22:09', 'topics' => 'auth,warning', 'message' => 'Multiple failed login attempts from 192.168.1.110'],
        ['time' => '13:35:22', 'topics' => 'interface', 'message' => 'Interface ether1 state changed to disconnected']
    ];
    
    if (!$useDemoMode) {
        // Initialize RouterOS API for real usage
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
            // Build command parameters
            $params = ['limit' => $limit];
            
            if (!empty($topics)) {
                $params['topics'] = $topics;
            }
            
            // Get real logs from router
            $sampleLogs = $api->commandWithParams('/log/print', $params);
            
            // Disconnect from router
            $api->disconnect();
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Could not connect to the router']);
            exit;
        }
    }
    
    // Process and filter logs
    $processedLogs = [];
    
    foreach ($sampleLogs as $log) {
        // Skip if search is set and doesn't match message
        if (!empty($search) && stripos($log['message'] ?? '', $search) === false) {
            continue;
        }
        
        $processedLog = [
            'time' => $log['time'] ?? '',
            'topics' => $log['topics'] ?? '',
            'message' => $log['message'] ?? '',
            'type' => determineLogTypeByMessage($log['message'] ?? '')
        ];
        
        $processedLogs[] = $processedLog;
    }
    
    // Group logs by topics for summary
    $topicCounts = [];
    foreach ($processedLogs as $log) {
        $topics = explode(',', $log['topics']);
        foreach ($topics as $topic) {
            $topic = trim($topic);
            if (!empty($topic)) {
                if (!isset($topicCounts[$topic])) {
                    $topicCounts[$topic] = 0;
                }
                $topicCounts[$topic]++;
            }
        }
    }
    
    // Sort by most frequent topics
    arsort($topicCounts);
    
    // Prepare response
    $response = [
        'logs' => $processedLogs,
        'total' => count($processedLogs),
        'topic_summary' => $topicCounts,
        'filter' => [
            'topics' => $topics,
            'limit' => $limit,
            'search' => $search
        ]
    ];
    
    // Return JSON response
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}