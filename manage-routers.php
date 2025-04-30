<?php
/**
 * H4N5VS Mikrotik System Security
 * Router Management Page
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

// Process form submissions
$error_message = '';
$success_message = '';

// Delete router
if (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['router_id'])) {
    $router_id = (int)$_POST['router_id'];
    
    if (delete_router($router_id)) {
        $success_message = 'Router has been deleted successfully';
        
        // Clear session variables if they match this router
        if (isset($_SESSION['router_id']) && $_SESSION['router_id'] == $router_id) {
            unset($_SESSION['router_id']);
            unset($_SESSION['mikrotik_ip']);
            unset($_SESSION['mikrotik_port']);
            unset($_SESSION['mikrotik_username']);
            unset($_SESSION['mikrotik_password']);
            unset($_SESSION['use_ssl']);
        }
    } else {
        $error_message = 'Failed to delete router';
    }
}

// Set active router
if (isset($_POST['action']) && $_POST['action'] == 'set_active' && isset($_POST['router_id'])) {
    $router_id = (int)$_POST['router_id'];
    
    if (set_active_router($router_id)) {
        // Get the router data to update session
        $router = get_router($router_id, true);
        
        if ($router) {
            // Update session with the active router details
            $_SESSION['router_id'] = $router['id'];
            $_SESSION['mikrotik_ip'] = $router['ip_address'];
            $_SESSION['mikrotik_port'] = $router['port'];
            $_SESSION['mikrotik_username'] = $router['username'];
            $_SESSION['mikrotik_password'] = $router['password'];
            $_SESSION['use_ssl'] = $router['use_ssl'] ? true : false;
            $_SESSION['router_connected'] = false; // Will be verified on dashboard
            
            $success_message = 'Active router has been changed to ' . htmlspecialchars($router['name']);
        } else {
            $error_message = 'Failed to load router details';
        }
    } else {
        $error_message = 'Failed to set active router';
    }
}

// Test router connection
if (isset($_POST['action']) && $_POST['action'] == 'test' && isset($_POST['router_id'])) {
    $router_id = (int)$_POST['router_id'];
    $router = get_router($router_id, true);
    
    if ($router) {
        // Try to connect to the router
        $api = new RouterosAPI();
        $api->debug = false;
        $api->port = $router['port'];
        $api->ssl = $router['use_ssl'] ? true : false;
        
        if ($api->connect($router['ip_address'], $router['username'], $router['password'])) {
            // Connection successful
            $success_message = 'Connection to ' . htmlspecialchars($router['name']) . ' was successful!';
            
            // Get system info
            $resources = $api->command('/system/resource/print');
            $identity = $api->command('/system/identity/print');
            
            // Update router info in database
            $update_data = [
                'last_connected' => date('Y-m-d H:i:s')
            ];
            
            if (!empty($resources)) {
                $update_data['model'] = $resources[0]['board-name'] ?? 'Unknown';
                $update_data['version'] = $resources[0]['version'] ?? 'Unknown';
            }
            
            update_router($router_id, $update_data);
            
            // Disconnect
            $api->disconnect();
        } else {
            // Connection failed
            $error_message = 'Failed to connect to ' . htmlspecialchars($router['name']) . ': ' . $api->getLastError();
        }
    } else {
        $error_message = 'Router not found';
    }
}

// Get all routers
$routers = get_all_routers();

// Include the header
include_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Routers - H4N5VS Mikrotik System Security</title>
    
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
        .router-card {
            border: 1px solid rgba(76, 175, 80, 0.3);
            margin-bottom: 20px;
            position: relative;
        }
        
        .router-card.active {
            border: 2px solid #4CAF50;
            box-shadow: 0 0 15px rgba(76, 175, 80, 0.5);
        }
        
        .active-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: #4CAF50;
            color: #fff;
            padding: 5px 10px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .router-header {
            background-color: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-bottom: 1px solid rgba(76, 175, 80, 0.3);
        }
        
        .router-details {
            padding: 15px;
        }
        
        .router-details dl {
            margin-bottom: 0;
        }
        
        .router-details dt {
            color: #999;
        }
        
        .router-details dd {
            color: #fff;
            margin-bottom: 10px;
        }
        
        .router-actions {
            border-top: 1px solid rgba(76, 175, 80, 0.3);
            padding: 15px;
            text-align: right;
        }
        
        .icon-success {
            color: #4CAF50;
        }
        
        .icon-warning {
            color: #FF9800;
        }
        
        .icon-error {
            color: #F44336;
        }
        
        .btn-connect {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }
        
        .btn-connect:hover {
            background-color: #3e8e41;
            border-color: #3e8e41;
        }
        
        .btn-edit {
            background-color: #2196F3;
            border-color: #2196F3;
        }
        
        .btn-edit:hover {
            background-color: #0b7dda;
            border-color: #0b7dda;
        }
        
        .btn-test {
            background-color: #FF9800;
            border-color: #FF9800;
        }
        
        .btn-test:hover {
            background-color: #e68a00;
            border-color: #e68a00;
        }
        
        .btn-delete {
            background-color: #F44336;
            border-color: #F44336;
        }
        
        .btn-delete:hover {
            background-color: #da190b;
            border-color: #da190b;
        }
        
        .add-router-card {
            border: 2px dashed rgba(76, 175, 80, 0.5);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 30px;
            transition: all 0.3s ease;
        }
        
        .add-router-card:hover {
            border-color: #4CAF50;
            background-color: rgba(76, 175, 80, 0.1);
        }
        
        .add-icon {
            font-size: 3em;
            color: #4CAF50;
            margin-bottom: 15px;
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
                <span id="connectionStatusText" class="connection-status-text">Multi-Router Mode</span>
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
                <i class="fas fa-network-wired mr-2"></i> MANAGE MIKROTIK ROUTERS
                <div class="float-end">
                    <a href="add-router.php" class="btn btn-sm btn-success">
                        <i class="fas fa-plus"></i> Add New Router
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
        
        <!-- Routers Grid -->
        <div class="row">
            <?php if ($routers && count($routers) > 0): ?>
                <?php foreach ($routers as $router): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card router-card <?php echo $router['active'] ? 'active' : ''; ?>">
                            <?php if ($router['active']): ?>
                                <div class="active-badge">ACTIVE</div>
                            <?php endif; ?>
                            
                            <div class="router-header">
                                <h5 class="m-0">
                                    <i class="fas fa-router me-2"></i>
                                    <?php echo htmlspecialchars($router['name']); ?>
                                </h5>
                            </div>
                            
                            <div class="router-details">
                                <dl class="row">
                                    <dt class="col-sm-4">IP Address</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($router['ip_address']); ?>:<?php echo htmlspecialchars($router['port']); ?></dd>
                                    
                                    <dt class="col-sm-4">Model</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($router['model'] ?? 'Unknown'); ?></dd>
                                    
                                    <dt class="col-sm-4">Version</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($router['version'] ?? 'Unknown'); ?></dd>
                                    
                                    <dt class="col-sm-4">Location</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($router['location'] ?? 'Not specified'); ?></dd>
                                    
                                    <dt class="col-sm-4">Last Connected</dt>
                                    <dd class="col-sm-8">
                                        <?php if (!empty($router['last_connected'])): ?>
                                            <span class="icon-success"><i class="fas fa-check-circle me-1"></i></span>
                                            <?php echo htmlspecialchars($router['last_connected']); ?>
                                        <?php else: ?>
                                            <span class="icon-warning"><i class="fas fa-exclamation-circle me-1"></i></span>
                                            Never
                                        <?php endif; ?>
                                    </dd>
                                </dl>
                            </div>
                            
                            <div class="router-actions">
                                <?php if (!$router['active']): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="set_active">
                                        <input type="hidden" name="router_id" value="<?php echo $router['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-connect">
                                            <i class="fas fa-plug"></i> Set Active
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <a href="edit-router.php?id=<?php echo $router['id']; ?>" class="btn btn-sm btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                
                                <form method="post" class="d-inline ms-1">
                                    <input type="hidden" name="action" value="test">
                                    <input type="hidden" name="router_id" value="<?php echo $router['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-test">
                                        <i class="fas fa-vial"></i> Test
                                    </button>
                                </form>
                                
                                <form method="post" class="d-inline ms-1" onsubmit="return confirm('Are you sure you want to delete this router?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="router_id" value="<?php echo $router['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-delete">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Add Router Card -->
            <div class="col-md-6 col-lg-4">
                <a href="add-router.php" class="card add-router-card text-decoration-none">
                    <div class="add-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h5>Add New Router</h5>
                    <p class="text-muted">Connect another Mikrotik device to the security system</p>
                </a>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Simple animation for the connection status in multi-router mode
        document.addEventListener('DOMContentLoaded', function() {
            const routerIcon = document.getElementById('routerIcon');
            const statusContainer = document.getElementById('connectionStatusContainer');
            
            // Set multi-router mode appearance
            statusContainer.classList.add('connected');
            routerIcon.classList.add('active');
            
            // Add router count to status text if available
            <?php if ($routers): ?>
            document.getElementById('connectionStatusText').textContent = 'Multi-Router Mode (<?php echo count($routers); ?>)';
            <?php endif; ?>
        });
    </script>
</body>
</html>