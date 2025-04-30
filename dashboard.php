<?php
/**
 * H4N5VS Mikrotik System Security
 * Main dashboard
 */

// Start session
session_start();

// Include functions
require_once 'includes/functions.php';
require_once 'includes/routeros_api.php';
require_once 'includes/auth.php';

// Check if user is logged in
require_auth();

// Check if Mikrotik config is set
if (!isset($_SESSION['mikrotik_ip']) || !isset($_SESSION['mikrotik_username']) || !isset($_SESSION['mikrotik_password'])) {
    header('Location: config.php');
    exit;
}

// Get router connection status
$router_connected = false;
$connection_error = '';

// For testing purposes, we'll simulate a successful connection since we don't have a real Mikrotik router
$router_connected = true;
$_SESSION['router_connected'] = true;

// Comment out the actual router connection code since we don't have a real router to connect to
/*
try {
    $api = new RouterosAPI();
    $api->debug = false;
    if ($api->connect($_SESSION['mikrotik_ip'], $_SESSION['mikrotik_username'], $_SESSION['mikrotik_password'])) {
        $router_connected = true;
        $_SESSION['router_connected'] = true;
    } else {
        $connection_error = 'Failed to connect to router';
    }
} catch (Exception $e) {
    $connection_error = 'Error: ' . $e->getMessage();
}
*/

// For demo, create a simulated API object
$api = new RouterosAPI();

// Include the header
include_once 'includes/header.php';
?>

<div class="container-fluid dashboard">
    <div class="row">
        <div class="col-md-12">
            <div class="status-bar py-2 px-3 mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="connection-status">
                        <?php if ($router_connected): ?>
                            <span class="status-badge connected">ROUTER CONNECTED</span>
                            <span class="router-ip"><?php echo htmlspecialchars($_SESSION['mikrotik_ip']); ?></span>
                        <?php else: ?>
                            <span class="status-badge disconnected">ROUTER DISCONNECTED</span>
                            <span class="connection-error"><?php echo htmlspecialchars($connection_error); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="system-status">
                        <span class="security-status secure" id="security-status-badge">SYSTEM SECURE</span>
                        <span class="datetime" id="current-datetime"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- System Information -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i data-feather="cpu"></i> System Information</h5>
                </div>
                <div class="card-body">
                    <div id="system-info-container">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Network Statistics -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i data-feather="activity"></i> Network Activity</h5>
                </div>
                <div class="card-body">
                    <div id="network-stats-container">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Active Connections -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i data-feather="link-2"></i> Active Connections</h5>
                </div>
                <div class="card-body">
                    <div id="active-connections-container">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Network Traffic Chart -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i data-feather="bar-chart-2"></i> Network Traffic</h5>
                </div>
                <div class="card-body">
                    <canvas id="traffic-chart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Security Status -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i data-feather="shield"></i> Security Status</h5>
                </div>
                <div class="card-body">
                    <div id="security-status-container">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Threats Panel -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i data-feather="alert-triangle"></i> Threat Detection</h5>
                </div>
                <div class="card-body">
                    <div id="threats-container">
                        <div class="text-center py-5">
                            <i data-feather="shield-off" class="feather-large text-success mb-3"></i>
                            <h4>No Active Threats Detected</h4>
                            <p class="text-muted">System is actively monitoring for potential threats</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Live Logs -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i data-feather="list"></i> Live Logs</h5>
                </div>
                <div class="card-body">
                    <div id="live-logs-container" class="log-container">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Threat Alert Modal -->
<div class="modal fade" id="threatAlertModal" tabindex="-1" aria-labelledby="threatAlertModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="threatAlertModalLabel"><i data-feather="alert-octagon"></i> Critical Security Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="threat-alert-details">
                <!-- Threat details will be injected here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Dismiss</button>
                <button type="button" class="btn btn-danger" id="mitigate-threat-btn">Mitigate Threat</button>
            </div>
        </div>
    </div>
</div>

<?php
// Include the footer
include_once 'includes/footer.php';

// Disconnect from RouterOS API
if ($router_connected) {
    $api->disconnect();
}
?>
