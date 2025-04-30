<?php
/**
 * H4N5VS Mikrotik System Security
 * Add New Router Page
 */

// Start session
session_start();

// Include functions
require_once 'includes/functions.php';
require_once 'includes/routeros_api.php';
require_once 'includes/auth.php';
require_once 'includes/database.php';

// Check if user is logged in
require_auth();

// Process form submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $router_data = [
        'name' => trim($_POST['name'] ?? ''),
        'ip_address' => trim($_POST['ip_address'] ?? ''),
        'port' => (int)($_POST['port'] ?? 8728),
        'username' => trim($_POST['username'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'use_ssl' => isset($_POST['use_ssl']) ? 1 : 0,
        'location' => trim($_POST['location'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
        'active' => isset($_POST['set_active']) ? 1 : 0
    ];
    
    // Validate inputs
    if (empty($router_data['name']) || empty($router_data['ip_address']) || 
        empty($router_data['username']) || empty($router_data['password'])) {
        $error_message = 'Name, IP Address, Username, and Password are required';
    } else {
        // Test the connection first
        $api = new RouterosAPI();
        $api->debug = false;
        $api->port = $router_data['port'];
        $api->ssl = $router_data['use_ssl'] ? true : false;
        
        try {
            // Try to connect to the router
            if ($api->connect($router_data['ip_address'], $router_data['username'], $router_data['password'])) {
                // Get system info for verification
                $resources = $api->command('/system/resource/print');
                if (!empty($resources)) {
                    $router_data['model'] = $resources[0]['board-name'] ?? 'Unknown';
                    $router_data['version'] = $resources[0]['version'] ?? 'Unknown';
                }
                
                // Disconnect
                $api->disconnect();
                
                // Record last connection time
                $router_data['last_connected'] = date('Y-m-d H:i:s');
                
                // Add router to database
                $router_id = add_router($router_data);
                
                if ($router_id) {
                    // If set as active, update session data
                    if ($router_data['active']) {
                        $_SESSION['router_id'] = $router_id;
                        $_SESSION['mikrotik_ip'] = $router_data['ip_address'];
                        $_SESSION['mikrotik_port'] = $router_data['port'];
                        $_SESSION['mikrotik_username'] = $router_data['username'];
                        $_SESSION['mikrotik_password'] = $router_data['password'];
                        $_SESSION['use_ssl'] = $router_data['use_ssl'] ? true : false;
                        $_SESSION['router_connected'] = true; // We just verified it
                        $_SESSION['router_model'] = $router_data['model'] ?? 'Unknown';
                        $_SESSION['router_version'] = $router_data['version'] ?? 'Unknown';
                    }
                    
                    // Log the action
                    log_router_activity($router_id, 'info', "Router {$router_data['name']} added to the system");
                    
                    $success_message = 'Router added successfully! Redirecting to router management...';
                    
                    // Redirect to manage routers page
                    header('Refresh: 2; URL=manage-routers.php');
                } else {
                    $error_message = 'Failed to add router to database';
                }
            } else {
                $error_message = 'Could not connect to the router. Please check your credentials and API settings. Error: ' . $api->getLastError();
            }
        } catch (Exception $e) {
            $error_message = 'Connection error: ' . $e->getMessage();
        }
    }
}

// Include the header
include_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Router - H4N5VS Mikrotik System Security</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/dark-theme.css" rel="stylesheet">
    <link href="assets/css/connection-indicator.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    
    <style>
        .form-card {
            border: 1px solid rgba(76, 175, 80, 0.3);
            margin-bottom: 20px;
        }
        
        .form-header {
            background-color: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-bottom: 1px solid rgba(76, 175, 80, 0.3);
        }
        
        .form-body {
            padding: 25px;
        }
        
        .form-footer {
            border-top: 1px solid rgba(76, 175, 80, 0.3);
            padding: 15px;
            text-align: right;
        }
        
        .router-icon-large {
            font-size: 4em;
            color: #4CAF50;
            text-align: center;
            margin: 20px 0;
        }
        
        .form-check-input:checked {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }
        
        .form-label {
            color: #aaa;
        }
        
        .input-group-text {
            background-color: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
            border-color: rgba(76, 175, 80, 0.3);
        }
        
        .form-control {
            background-color: rgba(0, 0, 0, 0.2);
            border-color: rgba(76, 175, 80, 0.3);
            color: #fff;
        }
        
        .form-control:focus {
            background-color: rgba(0, 0, 0, 0.3);
            border-color: #4CAF50;
            color: #fff;
        }
        
        .form-text {
            color: #777;
        }
        
        .btn-primary {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }
        
        .btn-primary:hover {
            background-color: #3e8e41;
            border-color: #3e8e41;
        }
        
        .btn-outline-secondary {
            color: #aaa;
            border-color: #aaa;
        }
        
        .btn-outline-secondary:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        
        .password-toggle {
            cursor: pointer;
            padding: 0 10px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header d-flex justify-content-between align-items-center px-3">
        <div class="header-brand">
            <h3 class="text-neon-green m-0">H4N5VS</h3>
            <div class="text-muted small">MIKROTIK SYSTEM Security</div>
        </div>
        
        <div class="d-flex align-items-center">
            <!-- Connection Status Indicator -->
            <div id="connectionStatusContainer" class="connection-status-container">
                <div class="connection-status-icon">
                    <img id="routerIcon" class="router-icon" src="assets/img/router-icon.svg" alt="Router">
                    <div class="signal-waves">
                        <div class="signal-wave-1"></div>
                        <div class="signal-wave-2"></div>
                        <div class="signal-wave-3"></div>
                    </div>
                </div>
                <span id="connectionStatusText" class="connection-status-text">Add Router</span>
            </div>
            
            <div class="notification-icon me-3">
                <i class="fas fa-bell"></i>
            </div>
            <div class="user-info">
                <i class="fas fa-user-circle me-1"></i>
                <span>Admin</span>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h4>H4N5VS</h4>
            <div>MIKROTIK SYSTEM Security</div>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="dashboard-new.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="manage-routers.php" class="active">
                    <i class="fas fa-network-wired"></i>
                    <span>Manage Routers</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-list-alt"></i>
                    <span>Logs</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-shield-alt"></i>
                    <span>Security</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="panel-header">
            <div class="panel-title">
                <i class="fas fa-plus-circle mr-2"></i> ADD NEW ROUTER
                <div class="float-end">
                    <a href="manage-routers.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Routers
                    </a>
                </div>
            </div>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Add Router Form -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card form-card">
                    <div class="form-header">
                        <h5 class="m-0">
                            <i class="fas fa-router me-2"></i>
                            Router Details
                        </h5>
                    </div>
                    
                    <div class="text-center router-icon-large">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    
                    <div class="form-body">
                        <form method="post" action="add-router.php">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Router Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-text">Give this router a meaningful name</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="location" class="form-label">Location</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        <input type="text" class="form-control" id="location" name="location" 
                                               value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
                                    </div>
                                    <div class="form-text">Optional - physical location of the router</div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <label for="ip_address" class="form-label">Router IP Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-server"></i></span>
                                        <input type="text" class="form-control" id="ip_address" name="ip_address" 
                                               value="<?php echo htmlspecialchars($_POST['ip_address'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-text">Enter the IP address of your Mikrotik router</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="port" class="form-label">API Port</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-anchor"></i></span>
                                        <input type="text" class="form-control" id="port" name="port" 
                                               value="<?php echo htmlspecialchars($_POST['port'] ?? '8728'); ?>" required>
                                    </div>
                                    <div class="form-text">Default: 8728, secure: 8729</div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-text">Enter the admin username for your router</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <span class="input-group-text password-toggle" onclick="togglePassword()">
                                            <i class="fas fa-eye" id="toggleIcon"></i>
                                        </span>
                                    </div>
                                    <div class="form-text">Enter the admin password for your router</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                                <div class="form-text">Optional - add any notes about this router</div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="use_ssl" name="use_ssl" value="1"
                                               <?php echo (isset($_POST['use_ssl'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="use_ssl">Use SSL Connection</label>
                                        <div class="form-text">Enable if your router uses SSL for API connections (port 8729)</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="set_active" name="set_active" value="1"
                                               <?php echo (isset($_POST['set_active'])) ? 'checked' : ''; ?> checked>
                                        <label class="form-check-label" for="set_active">Set as Active Router</label>
                                        <div class="form-text">Make this the currently active router for monitoring</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-footer">
                                <a href="manage-routers.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary ms-2">Add Router</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Function to toggle password visibility
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Simple animation for the connection status
        document.addEventListener('DOMContentLoaded', function() {
            const routerIcon = document.getElementById('routerIcon');
            const statusContainer = document.getElementById('connectionStatusContainer');
            
            statusContainer.classList.add('connecting');
            routerIcon.classList.add('active');
        });
    </script>
</body>
</html>