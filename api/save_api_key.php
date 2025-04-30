<?php
/**
 * H4N5VS Mikrotik System Security
 * API endpoint for saving OpenAI API key
 */

// Set content type to JSON
header('Content-Type: application/json');

// Start session
session_start();

// Include required files
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Authentication required', 'success' => false]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed', 'success' => false]);
    exit;
}

// Get request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// Validate request data
if (!isset($data['api_key']) || empty(trim($data['api_key']))) {
    http_response_code(400);
    echo json_encode(['error' => 'API key is required', 'success' => false]);
    exit;
}

$apiKey = trim($data['api_key']);

// Basic validation that it looks like an OpenAI key (starts with "sk-")
if (strpos($apiKey, 'sk-') !== 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid API key format. OpenAI API keys typically start with "sk-"', 'success' => false]);
    exit;
}

try {
    // Store API key in environment variable
    putenv("OPENAI_API_KEY=$apiKey");
    
    // Also store in session for persistence during this session
    $_SESSION['OPENAI_API_KEY'] = $apiKey;
    
    // Log activity (don't log the actual API key)
    log_activity('OpenAI API key updated', 'info');
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'API key saved successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to save API key: ' . $e->getMessage(),
        'success' => false
    ]);
}