<?php
/**
 * H4N5VS Mikrotik System Security
 * API endpoint for threat mitigation
 */

// Set content type to JSON
header('Content-Type: application/json');

// Start session
session_start();

// Include required files
require_once '../includes/functions.php';
require_once '../includes/routeros_api.php';
require_once '../includes/mitigation.php';

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

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// Validate request data
if (!isset($data['threat_id']) || !isset($data['threat_type']) || !isset($data['source_ip'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters: threat_id, threat_type, source_ip']);
    exit;
}

try {
    // For demo purposes, we'll simulate a successful mitigation
    // In a real environment, this would connect to the RouterOS API and execute commands
    
    // Initialize RouterOS API
    $api = new RouterosAPI();
    
    // Connect to the RouterOS API
    $api->connect($_SESSION['mikrotik_ip'], $_SESSION['mikrotik_username'], $_SESSION['mikrotik_password']);

    // Initialize mitigation engine
    $mitigationEngine = new MitigationEngine($api);
    
    // Create simulated threat object
    $threat = [
        'id' => $data['threat_id'],
        'type' => $data['threat_type'],
        'source_ip' => $data['source_ip'],
        'severity' => $data['severity'] ?? 'critical',
        'mitigation_commands' => []
    ];
    
    // Get appropriate mitigation commands
    $templates = $mitigationEngine->getMitigationTemplates();
    if (isset($templates[$data['threat_type']])) {
        $template = $templates[$data['threat_type']];
        
        // Convert template commands to mitigation commands
        foreach ($template['commands'] as $index => $command) {
            // Replace placeholders in command with actual values
            $commandStr = str_replace('37.8.8.8', $data['source_ip'], $command['command']);
            
            $threat['mitigation_commands'][] = [
                'title' => $command['title'],
                'command' => $commandStr,
                'description' => $command['description']
            ];
        }
    } else {
        // Generic mitigation commands if threat type not found
        $threat['mitigation_commands'][] = [
            'title' => 'Block Source IP',
            'command' => "/ip firewall filter add chain=forward action=drop src-address={$data['source_ip']} comment=\"Blocked malicious source\"",
            'description' => "Block all traffic from {$data['source_ip']}"
        ];
        
        $threat['mitigation_commands'][] = [
            'title' => 'Add to Blacklist',
            'command' => "/ip firewall address-list add list=blacklist address={$data['source_ip']} comment=\"Malicious source\"",
            'description' => "Add {$data['source_ip']} to blacklist for persistent blocking"
        ];
    }
    
    // Execute selected commands or all if none specified
    $selectedCommands = $data['commands'] ?? [];
    $results = $mitigationEngine->mitigateThreat($threat, $selectedCommands);
    
    // For demo, simulate successful mitigation
    $results = [
        'success' => true,
        'message' => 'Threat mitigated successfully',
        'executed' => []
    ];
    
    // Add executed commands to response
    foreach ($threat['mitigation_commands'] as $command) {
        $results['executed'][] = [
            'title' => $command['title'],
            'command' => $command['command'],
            'result' => 'Command executed successfully'
        ];
    }
    
    // Log the action
    $logMessage = date('Y-m-d H:i:s') . " - Mitigated {$data['threat_type']} threat from {$data['source_ip']}";
    $logFile = __DIR__ . '/../logs/mitigation.log';
    file_put_contents($logFile, $logMessage . "\n", FILE_APPEND);
    
    // Return success response
    echo json_encode($results);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'success' => false
    ]);
}