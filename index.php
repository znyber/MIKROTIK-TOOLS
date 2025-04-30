<?php
/**
 * H4N5VS Mikrotik System Security
 * Main entry point - redirects to login or dashboard
 */

// Start session
session_start();

// Include functions
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is already logged in
if (is_authenticated()) {
    // Check if Mikrotik config is set
    if (isset($_SESSION['mikrotik_ip']) && isset($_SESSION['mikrotik_username']) && isset($_SESSION['mikrotik_password'])) {
        // Redirect to new dashboard
        header('Location: dashboard-new.php');
        exit;
    } else {
        // Redirect to config page
        header('Location: config.php');
        exit;
    }
} else {
    // Redirect to login page
    header('Location: login.php');
    exit;
}
