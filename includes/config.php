<?php
/**
 * H4N5VS Mikrotik System Security
 * Configuration file
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting settings
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Application paths
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('DATA_PATH', ROOT_PATH . '/data');
define('LOGS_PATH', ROOT_PATH . '/logs');
define('API_PATH', ROOT_PATH . '/api');

// Create required directories if they don't exist
$directories = [DATA_PATH, LOGS_PATH];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Database settings (if applicable)
define('DB_HOST', 'localhost');
define('DB_NAME', 'h4n5vs');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application settings
define('APP_NAME', 'H4N5VS Mikrotik System Security');
define('APP_VERSION', '1.0.0');
define('APP_URL', '');

// API settings
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY'));

// Include functions file
require_once INCLUDES_PATH . '/functions.php';

// Setup environment variables from .env file if exists (useful for development)
if (file_exists(ROOT_PATH . '/.env')) {
    $env = parse_ini_file(ROOT_PATH . '/.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

// Load OpenAI API key from environment or session
if (empty(OPENAI_API_KEY) && isset($_SESSION['OPENAI_API_KEY'])) {
    putenv("OPENAI_API_KEY=" . $_SESSION['OPENAI_API_KEY']);
}