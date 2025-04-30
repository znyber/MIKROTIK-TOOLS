<?php
/**
 * H4N5VS Mikrotik System Security
 * Authentication functions
 */

// Fungsi ini langsung diimplementasikan di sini
// tidak perlu dipanggil terpisah
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is authenticated
 *
 * @return bool True if authenticated, false otherwise
 */
function is_authenticated() {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

/**
 * Require authentication or redirect to login
 */
function require_auth() {
    if (!is_authenticated()) {
        // If not authenticated, redirect to login page
        header('Location: login.php');
        exit;
    }
}

/**
 * Authenticate user
 *
 * @param string $username Username
 * @param string $password Password
 * @return bool True if authentication successful, false otherwise
 */
function authenticate($username, $password) {
    // In a real application, you would validate against a database
    // For demo purposes, hard-coded credentials
    if ($username === 'admin' && ($password === 'h4n5vs' || $password === 'h4n5vs_admin')) {
        $_SESSION['authenticated'] = true;
        $_SESSION['logged_in'] = true; // Tambahkan ini untuk kompatibilitas
        $_SESSION['username'] = $username;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['last_activity'] = time();
        
        // Set demo mode flag for testing without a real router
        if (isset($_POST['demo_mode']) && $_POST['demo_mode'] === 'on') {
            $_SESSION['demo_mode'] = true;
        } else {
            $_SESSION['demo_mode'] = false;
        }
        
        return true;
    }
    
    return false;
}

/**
 * Logout user
 */
function logout() {
    // Unset all session variables
    $_SESSION = [];
    
    // If it's desired to kill the session, also delete the session cookie.
    // Note: This will destroy the session, and not just the session data!
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Finally, destroy the session
    session_destroy();
}

/**
 * Check if session is expired
 * 
 * @param int $max_lifetime Maximum session lifetime in seconds
 * @return bool True if session is expired, false otherwise
 */
function is_session_expired($max_lifetime = 3600) {
    if (!isset($_SESSION['last_activity'])) {
        return true;
    }
    
    $inactive_time = time() - $_SESSION['last_activity'];
    return $inactive_time > $max_lifetime;
}

/**
 * Update last activity timestamp
 */
function update_last_activity() {
    $_SESSION['last_activity'] = time();
}