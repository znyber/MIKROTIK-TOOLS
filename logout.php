<?php
/**
 * H4N5VS Mikrotik System Security
 * Logout page
 */

// Start session
session_start();

// Include functions
require_once 'includes/functions.php';

// Log the logout
if (isset($_SESSION['username'])) {
    log_activity("User {$_SESSION['username']} logged out");
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
