<?php
/**
 * H4N5VS Mikrotik System Security
 * API endpoint for AI analysis
 */

// Set content type to JSON
header('Content-Type: application/json');

// Start session
session_start();

// Include required files
require_once '../includes/functions.php';
require_once '../includes/routeros_api.php';
require_once '../includes/ai_pattern_recognition.php';

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

// Process request
$action = isset($_GET['action']) ? $_GET['action'] : 'analyze';

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
        // Initialize AI engine
        $aiEngine = new AIPatternRecognition();
        
        // Process requested action
        switch ($action) {
            case 'analyze':
                // Get connection data
                $connections = $api->command('/ip/firewall/connection/print');
                
                // Get traffic statistics
                $trafficStats = [];
                $interfaces = $api->commandWithParams('/interface/print', ['stats' => 'true']);
                
                // Calculate traffic stats
                $trafficStats['total_rx'] = 0;
                $trafficStats['total_tx'] = 0;
                $trafficStats['current_rx'] = 0;
                $trafficStats['current_tx'] = 0;
                
                foreach ($interfaces as $interface) {
                    if (isset($interface['rx-byte']) && isset($interface['tx-byte'])) {
                        $trafficStats['total_rx'] += (int)$interface['rx-byte'];
                        $trafficStats['total_tx'] += (int)$interface['tx-byte'];
                        
                        // Try to identify WAN interface for current rate
                        if (isset($interface['name']) && (
                            strpos(strtolower($interface['name']), 'wan') !== false || 
                            strpos(strtolower($interface['name']), 'ether1') !== false ||
                            (isset($interface['comment']) && strpos(strtolower($interface['comment']), 'wan') !== false)
                        )) {
                            // For current rate, use additional command if available
                            $monitor = $api->commandWithParams('/interface/monitor-traffic', [
                                'interface' => $interface['name'],
                                'once' => 'true'
                            ]);
                            
                            if (!empty($monitor) && isset($monitor[0]['rx-bits-per-second']) && isset($monitor[0]['tx-bits-per-second'])) {
                                $trafficStats['current_rx'] += (int)$monitor[0]['rx-bits-per-second'];
                                $trafficStats['current_tx'] += (int)$monitor[0]['tx-bits-per-second'];
                            }
                        }
                    }
                }
                
                // Perform AI analysis
                $analysisResults = $aiEngine->analyzeTraffic($connections, $trafficStats);
                
                // Try advanced analysis with OpenAI if available
                $advancedAnalysis = $aiEngine->advancedAnalysis($analysisResults);
                if ($advancedAnalysis) {
                    $analysisResults['advanced_analysis'] = $advancedAnalysis;
                }
                
                // Return analysis results
                echo json_encode([
                    'success' => true,
                    'analysis' => $analysisResults,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                break;
                
            case 'learn':
                // Get data from request
                $requestData = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($requestData['threat_data']) || !isset($requestData['was_real_threat'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing required parameters']);
                    exit;
                }
                
                // Learn from threat
                $learnResult = $aiEngine->learnFromThreat(
                    $requestData['threat_data'],
                    $requestData['was_real_threat']
                );
                
                // Return result
                echo json_encode([
                    'success' => $learnResult,
                    'message' => $learnResult ? 'Learning data updated successfully' : 'Failed to update learning data',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                break;
                
            case 'metrics':
                // Get performance metrics
                $metrics = $aiEngine->getPerformanceMetrics();
                
                // Return metrics
                echo json_encode([
                    'success' => true,
                    'metrics' => $metrics,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                break;
                
            case 'malicious_ip':
                // Check if IP is provided
                if (!isset($_GET['ip'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing IP parameter']);
                    exit;
                }
                
                // Get IP info
                $ip = $_GET['ip'];
                $ipInfo = $aiEngine->checkMaliciousIP($ip);
                
                // Return IP info
                echo json_encode([
                    'success' => true,
                    'ip' => $ip,
                    'is_malicious' => $ipInfo !== false,
                    'info' => $ipInfo,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
                break;
        }
        
        // Disconnect from router
        $api->disconnect();
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