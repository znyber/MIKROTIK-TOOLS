<?php
/**
 * H4N5VS Mikrotik System Security
 * OpenAI Integration Class
 */

require_once __DIR__ . '/vendor/autoload.php';

use OpenAI\Client;

class OpenAIIntegration {
    private $api_key;
    private $client;
    
    public function __construct() {
        // Get API key from environment or config
        $this->api_key = getenv('OPENAI_API_KEY');
        if (!$this->api_key) {
            // Fall back to a config file if it exists
            if (file_exists(__DIR__ . '/config/api_keys.php')) {
                include __DIR__ . '/config/api_keys.php';
                $this->api_key = $openai_api_key ?? '';
            }
        }
        
        // Initialize OpenAI client if API key is available
        if ($this->api_key) {
            $this->client = OpenAI::client($this->api_key);
        }
    }
    
    /**
     * Check if the OpenAI integration is properly configured
     * @return bool True if configured, false otherwise
     */
    public function isConfigured() {
        return !empty($this->api_key);
    }
    
    /**
     * Analyze network traffic patterns for anomalies
     * @param array $trafficData Traffic data to analyze
     * @return array Analysis results
     */
    public function analyzeTrafficPatterns($trafficData) {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'OpenAI API not configured'
            ];
        }
        
        try {
            // Convert traffic data to a string format for the prompt
            $trafficString = json_encode($trafficData);
            
            // Create the completion request
            $response = $this->client->chat()->create([
                'model' => 'gpt-4o', // the newest OpenAI model is "gpt-4o" which was released May 13, 2024
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a network security expert analyzing traffic patterns for security threats. Analyze the provided traffic data and identify any anomalies, potential threats, or suspicious patterns. Focus on indicators of DDoS, port scanning, or botnet activity.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Analyze the following network traffic data for security threats and anomalies:\n\n" . $trafficString
                    ]
                ],
                'temperature' => 0.5,
            ]);
            
            return [
                'success' => true,
                'analysis' => $response->choices[0]->message->content,
                'model' => $response->model,
                'usage' => $response->usage->toArray()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'OpenAI API error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Analyze IP reputation and provide risk assessment
     * @param string $ip IP address to check
     * @return array Analysis results
     */
    public function analyzeIPReputation($ip) {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'OpenAI API not configured'
            ];
        }
        
        // First, we would normally query various IP reputation databases
        // For demo purposes, we'll simulate this with OpenAI
        
        try {
            $response = $this->client->chat()->create([
                'model' => 'gpt-4o', // the newest OpenAI model is "gpt-4o" which was released May 13, 2024
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a cybersecurity expert with deep knowledge of IP reputation systems. Based on the IP address provided, generate a plausible risk assessment as if you had queried actual reputation databases. Include a risk score (0-100), geographic information, and potential threat indicators.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Provide a detailed IP reputation analysis for: " . $ip
                    ]
                ],
                'temperature' => 0.7,
                'response_format' => ['type' => 'json_object']
            ]);
            
            // Parse the JSON response
            $analysisJson = json_decode($response->choices[0]->message->content, true);
            
            return [
                'success' => true,
                'ip' => $ip,
                'analysis' => $analysisJson,
                'model' => $response->model,
                'usage' => $response->usage->toArray()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'OpenAI API error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate mitigation recommendations for a specific threat
     * @param array $threatData Threat data
     * @return array Recommendations
     */
    public function generateMitigationRecommendations($threatData) {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'OpenAI API not configured'
            ];
        }
        
        try {
            // Convert threat data to a string format for the prompt
            $threatString = json_encode($threatData);
            
            $response = $this->client->chat()->create([
                'model' => 'gpt-4o', // the newest OpenAI model is "gpt-4o" which was released May 13, 2024
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a network security expert specializing in threat mitigation for Mikrotik routers. Generate specific, actionable mitigation recommendations for the provided threat data. Focus on RouterOS commands that can be executed to mitigate the threat.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Generate detailed mitigation recommendations for the following threat:\n\n" . $threatString
                    ]
                ],
                'temperature' => 0.5,
                'response_format' => ['type' => 'json_object']
            ]);
            
            // Parse the JSON response
            $recommendationsJson = json_decode($response->choices[0]->message->content, true);
            
            return [
                'success' => true,
                'recommendations' => $recommendationsJson,
                'model' => $response->model,
                'usage' => $response->usage->toArray()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'OpenAI API error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate a security report based on system data
     * @param array $systemData System data
     * @return array Report data
     */
    public function generateSecurityReport($systemData) {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'OpenAI API not configured'
            ];
        }
        
        try {
            // Convert system data to a string format for the prompt
            $systemString = json_encode($systemData);
            
            $response = $this->client->chat()->create([
                'model' => 'gpt-4o', // the newest OpenAI model is "gpt-4o" which was released May 13, 2024
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a network security analyst tasked with generating comprehensive security reports. Based on the provided system data, generate a detailed security assessment including risk analysis, vulnerability assessment, and improvement recommendations.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Generate a detailed security report based on the following system data:\n\n" . $systemString
                    ]
                ],
                'temperature' => 0.3,
            ]);
            
            return [
                'success' => true,
                'report' => $response->choices[0]->message->content,
                'model' => $response->model,
                'usage' => $response->usage->toArray()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'OpenAI API error: ' . $e->getMessage()
            ];
        }
    }
}