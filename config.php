<?php
/**
 * H4N5VS Mikrotik System Security
 * Configuration page for Mikrotik router settings
 */

// Start session
session_start();

// Include functions
require_once 'includes/functions.php';
require_once 'includes/routeros_api.php';
require_once 'includes/auth.php';

// Check if user is logged in
require_auth();

$error_message = '';
$success_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mikrotik_ip = trim($_POST['mikrotik_ip'] ?? '');
    $mikrotik_port = trim($_POST['mikrotik_port'] ?? '8728');
    $mikrotik_username = trim($_POST['mikrotik_username'] ?? '');
    $mikrotik_password = $_POST['mikrotik_password'] ?? '';
    $use_ssl = isset($_POST['use_ssl']) ? true : false;
    
    // Validate inputs
    if (empty($mikrotik_ip) || empty($mikrotik_username) || empty($mikrotik_password)) {
        $error_message = 'All fields are required';
    } else {
        // Test actual connection to router
        try {
            $api = new RouterosAPI();
            $api->debug = false;
            $api->port = $mikrotik_port;
            $api->ssl = $use_ssl;
            
            // Aktifkan mode debug untuk melihat detail koneksi
            $api->debug = true;
            
            // Try to connect to the router
            if ($api->connect($mikrotik_ip, $mikrotik_username, $mikrotik_password)) {
                // Connection successful, save to session
                $_SESSION['mikrotik_ip'] = $mikrotik_ip;
                $_SESSION['mikrotik_port'] = $mikrotik_port;
                $_SESSION['mikrotik_username'] = $mikrotik_username;
                $_SESSION['mikrotik_password'] = $mikrotik_password;
                $_SESSION['use_ssl'] = $use_ssl;
                $_SESSION['router_connected'] = true; // Critical flag for dashboard
                
                // Get system info for verification
                $resources = $api->command('system/resource/print');
                if (!empty($resources)) {
                    $_SESSION['router_model'] = $resources[0]['board-name'] ?? 'Unknown';
                    $_SESSION['router_version'] = $resources[0]['version'] ?? 'Unknown';
                }
                
                // Disconnect
                $api->disconnect();
                
                // Log router configuration
                log_activity("Router configuration set for {$mikrotik_ip}");
                
                $success_message = 'Connection successful! Redirecting to dashboard...';
                
                // Redirect to the new dashboard
                header('Location: dashboard-new.php');
                exit;
            } else {
                // Peroleh detail error spesifik
                $error_detail = $api->getLastError();
                $error_message = 'Could not connect to the router. Please check your credentials and API settings. Error: ' . $error_detail;
                
                // Log error untuk troubleshooting
                log_activity("Connection failed to router {$mikrotik_ip}: {$error_detail}", 'error');
            }
        } catch (Exception $e) {
            $error_message = 'Connection error: ' . $e->getMessage();
        }
    }
}

// Include the header
include_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card config-card">
                <div class="card-header">
                    <h4><i data-feather="settings"></i> Router Configuration</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="config.php">
                        <div class="mb-3">
                            <label for="mikrotik_ip" class="form-label">Router IP Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i data-feather="server"></i></span>
                                <input type="text" class="form-control" id="mikrotik_ip" name="mikrotik_ip" 
                                       value="<?php echo htmlspecialchars($_SESSION['mikrotik_ip'] ?? ''); ?>" required>
                            </div>
                            <div class="form-text">Enter the IP address of your Mikrotik router</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="mikrotik_port" class="form-label">API Port</label>
                            <div class="input-group">
                                <span class="input-group-text"><i data-feather="anchor"></i></span>
                                <input type="text" class="form-control" id="mikrotik_port" name="mikrotik_port" 
                                       value="<?php echo htmlspecialchars($_SESSION['mikrotik_port'] ?? '8728'); ?>" required>
                            </div>
                            <div class="form-text">Enter the API port (default: 8728, secure: 8729)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="mikrotik_username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i data-feather="user"></i></span>
                                <input type="text" class="form-control" id="mikrotik_username" name="mikrotik_username" 
                                       value="<?php echo htmlspecialchars($_SESSION['mikrotik_username'] ?? ''); ?>" required>
                            </div>
                            <div class="form-text">Enter the admin username for your router</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="mikrotik_password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i data-feather="lock"></i></span>
                                <input type="password" class="form-control" id="mikrotik_password" name="mikrotik_password" required>
                            </div>
                            <div class="form-text">Enter the admin password for your router</div>
                        </div>
                        
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="use_ssl" name="use_ssl" value="1"
                                   <?php echo (isset($_SESSION['use_ssl']) && $_SESSION['use_ssl'] ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="use_ssl">Use SSL Connection</label>
                            <div class="form-text">Enable if your router uses SSL for API connections (port 8729)</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Connect to Router</button>
                            <a href="demo-login.php" class="btn btn-success">Use Demo Mode</a>
                            <a href="index.php" class="btn btn-outline-secondary">Back</a>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">Your credentials are stored only in your session for security reasons.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include the footer
include_once 'includes/footer.php';
?>
