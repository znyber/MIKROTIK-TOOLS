<?php
/**
 * H4N5VS Mikrotik System Security
 * AI Pattern Recognition Class
 */

class AIPatternRecognition {
    private $openAI;
    private $thresholds;
    
    /**
     * Initialize AI Pattern Recognition
     */
    public function __construct() {
        // Load the OpenAI integration if available
        require_once __DIR__ . '/../openai_integration.php';
        
        // Initialize OpenAI integration
        $this->openAI = new OpenAIIntegration();
        
        // Set default thresholds
        $this->thresholds = [
            'ddos' => [
                'packet_rate' => 10000,  // packets per second
                'connection_count' => 1000,
                'bandwidth' => 100       // Mbps
            ],
            'port_scan' => [
                'ports_per_minute' => 100,
                'unique_ports' => 20
            ],
            'brute_force' => [
                'login_attempts' => 5,
                'timeframe' => 60        // seconds
            ]
        ];
    }
    
    /**
     * Analyze traffic for security threats
     * @param array $trafficData Data about traffic
     * @return array Analysis results
     */
    public function analyzeTraffic($trafficData) {
        $results = [
            'threats_detected' => [],
            'confidence' => 0,
            'recommendation' => ''
        ];
        
        // Basic threshold analysis
        if (isset($trafficData['packet_rate']) && $trafficData['packet_rate'] > $this->thresholds['ddos']['packet_rate']) {
            $results['threats_detected'][] = [
                'type' => 'ddos',
                'subtype' => 'packet_flood',
                'confidence' => 0.8,
                'evidence' => 'High packet rate detected: ' . $trafficData['packet_rate'] . ' pps'
            ];
            $results['confidence'] = max($results['confidence'], 0.8);
        }
        
        if (isset($trafficData['connection_count']) && $trafficData['connection_count'] > $this->thresholds['ddos']['connection_count']) {
            $results['threats_detected'][] = [
                'type' => 'ddos',
                'subtype' => 'connection_flood',
                'confidence' => 0.75,
                'evidence' => 'High connection count detected: ' . $trafficData['connection_count']
            ];
            $results['confidence'] = max($results['confidence'], 0.75);
        }
        
        // Use OpenAI for advanced analysis if available
        if ($this->openAI->isConfigured()) {
            $aiAnalysis = $this->openAI->analyzeTrafficPatterns($trafficData);
            
            if ($aiAnalysis['success']) {
                // Append AI-based analysis
                $results['ai_analysis'] = $aiAnalysis['analysis'];
                
                // Enhance confidence based on AI analysis
                if (!empty($results['threats_detected'])) {
                    $results['confidence'] += 0.1;
                    $results['confidence'] = min($results['confidence'], 0.95);
                }
            }
        }
        
        // Generate recommendations
        if (!empty($results['threats_detected'])) {
            $results['recommendation'] = $this->generateRecommendations($results['threats_detected']);
        }
        
        return $results;
    }
    
    /**
     * Perform advanced analysis using natural language patterns
     * @param array $data Security data to analyze
     * @return array Analysis results
     */
    public function advancedAnalysis($data) {
        $results = [
            'insights' => [],
            'potential_threats' => [],
            'confidence' => 0.5
        ];
        
        // Pattern matching for known attack signatures
        if (isset($data['logs']) && is_array($data['logs'])) {
            foreach ($data['logs'] as $log) {
                // Check for brute force patterns
                if (stripos($log, 'failed login') !== false || stripos($log, 'authentication failure') !== false) {
                    $results['potential_threats'][] = [
                        'type' => 'brute_force',
                        'evidence' => $log,
                        'confidence' => 0.65
                    ];
                    $results['confidence'] = max($results['confidence'], 0.65);
                }
                
                // Check for port scanning
                if (stripos($log, 'port scan') !== false || stripos($log, 'multiple ports') !== false) {
                    $results['potential_threats'][] = [
                        'type' => 'port_scan',
                        'evidence' => $log,
                        'confidence' => 0.7
                    ];
                    $results['confidence'] = max($results['confidence'], 0.7);
                }
                
                // Check for botnet communication
                if (stripos($log, 'botnet') !== false || stripos($log, 'command & control') !== false || stripos($log, 'c&c') !== false) {
                    $results['potential_threats'][] = [
                        'type' => 'botnet',
                        'evidence' => $log,
                        'confidence' => 0.8
                    ];
                    $results['confidence'] = max($results['confidence'], 0.8);
                }
            }
        }
        
        // Use OpenAI for advanced analysis if available
        if ($this->openAI->isConfigured()) {
            $aiAnalysis = $this->openAI->generateSecurityReport($data);
            
            if ($aiAnalysis['success']) {
                $results['ai_insights'] = $aiAnalysis['report'];
                
                // Enhance confidence based on AI analysis
                if (!empty($results['potential_threats'])) {
                    $results['confidence'] += 0.1;
                    $results['confidence'] = min($results['confidence'], 0.95);
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Learn from detected threats to improve future detection
     * @param array $threatData Data about the detected threat
     * @return bool Success status
     */
    public function learnFromThreat($threatData) {
        // Update thresholds based on threat data
        if (isset($threatData['type']) && isset($this->thresholds[$threatData['type']])) {
            $threatType = $threatData['type'];
            
            // Adaptive learning logic
            switch ($threatType) {
                case 'ddos':
                    if (isset($threatData['packet_rate']) && $threatData['packet_rate'] > 0) {
                        // Adjust threshold to 90% of the detected attack value
                        $this->thresholds['ddos']['packet_rate'] = min(
                            $this->thresholds['ddos']['packet_rate'],
                            $threatData['packet_rate'] * 0.9
                        );
                    }
                    break;
                    
                case 'port_scan':
                    if (isset($threatData['ports_scanned']) && $threatData['ports_scanned'] > 0) {
                        // Adjust threshold to 90% of the detected attack value
                        $this->thresholds['port_scan']['unique_ports'] = min(
                            $this->thresholds['port_scan']['unique_ports'],
                            $threatData['ports_scanned'] * 0.9
                        );
                    }
                    break;
                    
                case 'brute_force':
                    if (isset($threatData['login_attempts']) && $threatData['login_attempts'] > 0) {
                        // Adjust threshold to be more sensitive
                        $this->thresholds['brute_force']['login_attempts'] = min(
                            $this->thresholds['brute_force']['login_attempts'],
                            max(3, $threatData['login_attempts'] * 0.8)
                        );
                    }
                    break;
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get performance metrics of the AI system
     * @return array Metrics
     */
    public function getPerformanceMetrics() {
        return [
            'thresholds' => $this->thresholds,
            'openai_available' => $this->openAI->isConfigured(),
            'version' => '1.0'
        ];
    }
    
    /**
     * Generate recommendations based on detected threats
     * @param array $threats Detected threats
     * @return string Recommendations
     */
    private function generateRecommendations($threats) {
        $recommendations = [];
        
        foreach ($threats as $threat) {
            switch ($threat['type']) {
                case 'ddos':
                    $recommendations[] = 'Implement rate limiting on network interfaces';
                    $recommendations[] = 'Configure firewall to block source IP addresses';
                    $recommendations[] = 'Enable DDoS protection features on router';
                    break;
                    
                case 'port_scan':
                    $recommendations[] = 'Configure firewall to limit connection rates';
                    $recommendations[] = 'Block source IP addresses temporarily';
                    $recommendations[] = 'Consider implementing port knocking for sensitive services';
                    break;
                    
                case 'brute_force':
                    $recommendations[] = 'Implement progressive delays for login attempts';
                    $recommendations[] = 'Set up temporary IP blocking after failed attempts';
                    $recommendations[] = 'Enable two-factor authentication if available';
                    break;
                    
                case 'botnet':
                    $recommendations[] = 'Block communication to known command & control servers';
                    $recommendations[] = 'Scan network for infected devices';
                    $recommendations[] = 'Update firmware on all network devices';
                    break;
            }
        }
        
        // Remove duplicates and return as string
        return implode("\n", array_unique($recommendations));
    }
    
    /**
     * Check if an IP address is malicious
     * @param string $ip IP address to check
     * @return array Check results
     */
    public function checkMaliciousIP($ip) {
        $result = [
            'ip' => $ip,
            'is_malicious' => false,
            'confidence' => 0,
            'reason' => '',
            'details' => []
        ];
        
        // Use OpenAI for IP reputation analysis if available
        if ($this->openAI->isConfigured()) {
            $ipAnalysis = $this->openAI->analyzeIPReputation($ip);
            
            if ($ipAnalysis['success'] && isset($ipAnalysis['analysis'])) {
                // Extract relevant information from AI analysis
                $analysis = $ipAnalysis['analysis'];
                
                if (isset($analysis['risk_score']) && $analysis['risk_score'] > 60) {
                    $result['is_malicious'] = true;
                    $result['confidence'] = $analysis['risk_score'] / 100;
                    $result['reason'] = $analysis['primary_threat_indicator'] ?? 'High risk score';
                }
                
                $result['details'] = $analysis;
            }
        } else {
            // Fallback to basic checks
            // Check against reserved IP ranges
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                $result['is_malicious'] = false;
                $result['reason'] = 'Private or reserved IP range';
                $result['confidence'] = 0.9;
            }
        }
        
        return $result;
    }
}