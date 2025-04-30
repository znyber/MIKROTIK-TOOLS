<?php
/**
 * H4N5VS Mikrotik System Security
 * Demo Login Page
 */

// Start session
session_start();

// Include functions
require_once 'includes/auth.php';

// Check if user is already logged in
if (is_authenticated()) {
    header('Location: dashboard-new.php');
    exit;
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Auto login for demo mode
    $_SESSION['authenticated'] = true;
    $_SESSION['username'] = 'demo';
    $_SESSION['user_role'] = 'demo';
    $_SESSION['last_activity'] = time();
    $_SESSION['demo_mode'] = true;
    
    // Set demo router configurations
    $_SESSION['routers'] = [
        1 => [
            'id' => 1,
            'name' => 'Main Office Router',
            'ip' => '192.168.1.1',
            'username' => 'admin',
            'model' => 'hAP ac²',
            'active' => true
        ],
        2 => [
            'id' => 2,
            'name' => 'Branch Office',
            'ip' => '192.168.2.1',
            'username' => 'admin',
            'model' => 'RB750Gr3',
            'active' => false
        ],
        3 => [
            'id' => 3,
            'name' => 'Warehouse',
            'ip' => '192.168.3.1',
            'username' => 'admin',
            'model' => 'RB3011UiAS',
            'active' => false
        ]
    ];
    
    // Set active router
    $_SESSION['active_router_id'] = 1;
    
    // Set fake Mikrotik credentials for demo mode
    $_SESSION['mikrotik_ip'] = '192.168.1.1';
    $_SESSION['mikrotik_username'] = 'admin';
    $_SESSION['mikrotik_password'] = 'demo123';
    
    // Log the access for demo mode
    error_log("Demo login accessed");
    
    header('Location: dashboard-new.php');
    exit;
}

// Display the login page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Login - H4N5VS Mikrotik System Security</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .demo-alert {
            margin-bottom: 20px;
            padding: 15px;
            border-left: 5px solid #FFC107;
            background-color: rgba(255, 193, 7, 0.1);
        }
        
        .demo-title {
            color: #FFC107;
            margin-bottom: 10px;
        }
        
        .demo-features {
            margin-top: 15px;
        }
        
        .demo-features li {
            margin-bottom: 8px;
        }
        
        .demo-badge {
            position: absolute;
            top: -15px;
            right: -15px;
            background-color: #FFC107;
            color: #000;
            padding: 5px 10px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 12px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }
        
        .login-box {
            position: relative;
            border: 2px solid rgba(255, 193, 7, 0.5);
        }
    </style>
</head>
<body class="login-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="login-box">
                    <div class="demo-badge">DEMO MODE</div>
                    <h1 class="text-center mb-3">H4N5VS</h1>
                    <h2 class="text-center mb-4">Mikrotik System Security</h2>
                    
                    <div class="demo-alert">
                        <h5 class="demo-title"><i data-feather="info"></i> Demo Mode Information</h5>
                        <p>You are accessing the system in <strong>Demo Mode</strong>, which simulates the functionality without requiring actual Mikrotik hardware.</p>
                        
                        <div class="demo-features">
                            <h6>Features Available in Demo Mode:</h6>
                            <ul>
                                <li>Dashboard with simulated metrics and alerts</li>
                                <li>Multi-router management interface</li>
                                <li>AI-powered threat detection simulation</li>
                                <li>Mitigation recommendations</li>
                                <li>All graphical interfaces and visualizations</li>
                            </ul>
                        </div>
                        
                        <p class="mt-3 mb-0"><small>Note: No actual router connections will be made in demo mode.</small></p>
                    </div>
                    
                    <form method="post" action="demo-login.php">
                        <input type="hidden" name="demo_mode" value="on">
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning">CONTINUE TO DEMO</button>
                            <a href="login.php" class="btn btn-outline-secondary mt-2">RETURN TO LOGIN</a>
                        </div>
                    </form>
                    
                    <div class="system-info mt-4 text-center">
                        <p>ADVANCED NETWORK PROTECTION SYSTEM</p>
                        <p class="version">V1.0.0</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            feather.replace();
        });
    </script>
</body>
</html>