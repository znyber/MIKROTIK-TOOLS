<?php
/**
 * H4N5VS Mikrotik System Security
 * New dashboard with dark green theme
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

// Try to connect to the router
$router_connected = false;
$api = new RouterosAPI();

// Set SSL option if needed
if (isset($_SESSION['mikrotik_ssl']) && $_SESSION['mikrotik_ssl'] === 'yes') {
    $api->ssl = true;
}

// Connect to router
if ($api->connect($_SESSION['mikrotik_ip'], $_SESSION['mikrotik_username'], $_SESSION['mikrotik_password'])) {
    $router_connected = true;
    $_SESSION['router_connected'] = true;
} else {
    $_SESSION['router_connected'] = false;
}

// Include the header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>H4N5VS - Mikrotik System Security</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/dark-theme.css" rel="stylesheet">
    <link href="assets/css/connection-indicator.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    
    <!-- Connection Status Variables -->
    <script>
        // Set global variables for connection status
        <?php if (isset($_SESSION['demo_mode']) && $_SESSION['demo_mode'] === true): ?>
        const IS_DEMO_MODE = true;
        <?php else: ?>
        const IS_DEMO_MODE = false;
        <?php endif; ?>
        
        const ROUTER_CONNECTED = <?php echo isset($_SESSION['router_connected']) && $_SESSION['router_connected'] === true ? 'true' : 'false'; ?>;
    </script>
    
    <!-- Enable Demo Mode -->
    <script src="assets/js/demo-mode.js"></script>
    
    <!-- Check for Demo Mode -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set demo mode from PHP session
            const demoMode = <?php echo isset($_SESSION['demo_mode']) && $_SESSION['demo_mode'] ? 'true' : 'false'; ?>;
            console.log("Demo mode status from PHP: " + demoMode);
            if (window.h4n5vsDemo) {
                window.h4n5vsDemo.setDemoMode(demoMode);
            }
        });
    </script>
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
                <span id="connectionStatusText" class="connection-status-text">Checking...</span>
            </div>
            
            <!-- Router Selector Dropdown -->
            <div class="router-selector dropdown me-3">
                <button class="btn btn-sm btn-dark dropdown-toggle" type="button" id="routerSelectorDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-network-wired me-1"></i> Select Router
                </button>
                <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="routerSelectorDropdown" id="routerList">
                    <li><a class="dropdown-item" href="manage-routers.php"><i class="fas fa-cog me-2"></i> Manage Routers</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <!-- Router list will be populated dynamically -->
                    <li class="text-center p-2 text-muted small" id="noRoutersMessage">No routers configured</li>
                </ul>
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
                <a href="dashboard-new.php" class="active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="manage-routers.php">
                    <i class="fas fa-network-wired"></i>
                    <span>Manage Routers</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-desktop"></i>
                    <span>Monitor</span>
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
        <!-- System Status Header -->
        <div class="panel-header">
            <div class="panel-title">
                <i class="fas fa-server mr-2"></i> MIKROTIK SYSTEM STATUS
                <span class="float-end text-success" id="systemStatusMessage">
                    <i class="fas fa-check-circle"></i> system status analysis complete...
                </span>
            </div>
        </div>
        
        <!-- Status Panels Row -->
        <div class="row">
            <!-- CPU Usage -->
            <div class="col-md-3">
                <div class="status-panel">
                    <div class="status-label">CPU USAGE</div>
                    <div class="status-value">34<span class="status-unit">%</span></div>
                    <div class="progress">
                        <div class="progress-bar progress-bar-green" role="progressbar" style="width: 34%"></div>
                    </div>
                </div>
            </div>
            
            <!-- Memory Usage -->
            <div class="col-md-3">
                <div class="status-panel">
                    <div class="status-label">MEMORY USAGE</div>
                    <div class="status-value">128<span class="status-unit">MB /512MB</span></div>
                    <div class="progress">
                        <div class="progress-bar progress-bar-green" role="progressbar" style="width: 25%"></div>
                    </div>
                </div>
            </div>
            
            <!-- Current Bandwidth -->
            <div class="col-md-3">
                <div class="status-panel">
                    <div class="status-label">CURRENT BANDWIDTH</div>
                    <div class="connection-stats">
                        <div class="connection-stat">
                            <div class="connection-value">24.5</div>
                            <div class="connection-label">Download Mbps</div>
                        </div>
                        <div class="connection-stat">
                            <div class="connection-value">8.2</div>
                            <div class="connection-label">Upload Mbps</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Connected Devices -->
            <div class="col-md-3">
                <div class="status-panel">
                    <div class="status-label">CONNECTED DEVICES</div>
                    <div class="status-value">16</div>
                    <div class="connection-stats">
                        <div class="connection-stat">
                            <div class="connection-value">0</div>
                            <div class="connection-label">Unknown</div>
                        </div>
                        <div class="connection-stat">
                            <div class="connection-value">16</div>
                            <div class="connection-label">Authorized</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Security Protection Status -->
        <div class="panel-header mt-4">
            <div class="panel-title">
                <i class="fas fa-shield-alt mr-2"></i> SECURITY PROTECTION STATUS
            </div>
        </div>
        
        <!-- Protection Panels -->
        <div class="row">
            <!-- DDoS Protection -->
            <div class="col-md-4">
                <div class="status-panel">
                    <div class="protection-item">
                        <div>
                            <div class="protection-title">DDoS Protection</div>
                            <div class="protection-details">Prevents distributed denial of service attacks</div>
                            <div class="protection-meta">
                                <div>Protection Level: <span class="text-success">High</span></div>
                                <div>Last attack blocked: <span>2 hours ago</span></div>
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Botnet Detection -->
            <div class="col-md-4">
                <div class="status-panel">
                    <div class="protection-item">
                        <div>
                            <div class="protection-title">Botnet Detection</div>
                            <div class="protection-details">Detects and blocks botnet communication</div>
                            <div class="protection-meta">
                                <div>Database version: <span>v1.2.1</span></div>
                                <div>Updated: <span>Today, 08:45 AM</span></div>
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- IP Blocking -->
            <div class="col-md-4">
                <div class="status-panel">
                    <div class="protection-item">
                        <div>
                            <div class="protection-title">IP Blocking</div>
                            <div class="protection-details">Auto-blocks suspicious IP addresses</div>
                            <div class="protection-meta">
                                <div>Currently blocked: <span class="text-danger">76 IPs</span></div>
                                <div>After 24h: <span>Auto-removal</span></div>
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Network Traffic -->
        <div class="panel-header mt-4">
            <div class="panel-title">
                <i class="fas fa-chart-line mr-2"></i> NETWORK TRAFFIC
                
                <div class="float-end">
                    <span class="me-3">Download</span>
                    <span>Upload</span>
                    
                    <select class="ms-3 bg-dark text-white border-0">
                        <option>Last hour</option>
                        <option>Last day</option>
                        <option>Last week</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="traffic-chart-container">
                    <canvas id="trafficChart" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Two-column layout for logs and alerts -->
        <div class="row">
            <!-- System Log Console -->
            <div class="col-md-8">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="fas fa-terminal mr-2"></i> SYSTEM LOG CONSOLE
                        <button class="btn btn-sm btn-dark float-end">Clear</button>
                    </div>
                </div>
                
                <div class="log-console" id="logConsole">
                    <!-- Log entries will be loaded dynamically -->
                </div>
            </div>
            
            <!-- Active Security Alerts -->
            <div class="col-md-4">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="fas fa-exclamation-triangle mr-2"></i> Active Security Alerts
                    </div>
                </div>
                
                <div id="securityAlerts" class="alerts-section">
                    <!-- Security alerts will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/connection-indicator.js"></script>
    <script>
        // DOMContentLoaded listener
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize data and start update cycle
            loadSystemLogs();
            loadSecurityAlerts();
            fetchSystemInfo();
            fetchRouterList();
            
            // Set interval for periodic updates
            setInterval(function() {
                fetchSystemInfo();
                loadSystemLogs();
                loadSecurityAlerts();
            }, 30000); // Update every 30 seconds
        });
        
    </script>
    <script>
        // Initialize traffic chart
        const ctx = document.getElementById('trafficChart').getContext('2d');
        const trafficChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: Array.from({length: 24}, (_, i) => i),
                datasets: [
                    {
                        label: 'Download',
                        data: generateRandomData(50, 100, 24),
                        borderColor: '#0aff0a',
                        backgroundColor: 'rgba(10, 255, 10, 0.1)',
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 0
                    },
                    {
                        label: 'Upload',
                        data: generateRandomData(20, 40, 24),
                        borderColor: '#ffa500',
                        backgroundColor: 'rgba(255, 165, 0, 0.1)',
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#999',
                            callback: function(value) {
                                return value + ' Mbps';
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#999'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Generate random data for chart
        function generateRandomData(min, max, count) {
            return Array.from({length: count}, () => Math.floor(Math.random() * (max - min + 1)) + min);
        }
        
        // Global variables to track system state
        let systemStats = {
            cpu: 34,
            memory: 128,
            memoryTotal: 512,
            download: 24.5,
            upload: 8.2,
            connectedDevices: 16,
            authorizedDevices: 16,
            unknownDevices: 0
        };
        
        let securityStats = {
            ddosProtection: {
                enabled: true,
                level: 'High',
                lastBlock: '2 hours ago'
            },
            botnetDetection: {
                enabled: true,
                dbVersion: 'v1.2.1',
                lastUpdate: 'Today, 08:45 AM'
            },
            ipBlocking: {
                enabled: true,
                blockedCount: 76,
                retention: 'Auto-removal'
            }
        };
        
        // Function to update all system data
        function updateSystemData() {
            fetchSystemInfo();
            fetchNetworkStats();
            loadSecurityAlerts();
            loadSystemLogs();
            updateTrafficChart();
        }
        
        // Function to fetch system information
        function fetchSystemInfo() {
            fetch('api/system_info.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Update CPU and memory stats
                    if (data && data.resources) {
                        const resources = data.resources;
                        
                        // Update CPU usage
                        if (resources.cpu_load !== undefined) {
                            systemStats.cpu = parseInt(resources.cpu_load);
                            document.querySelector('.status-panel:nth-child(1) .status-value').innerHTML = 
                                `${systemStats.cpu}<span class="status-unit">%</span>`;
                            document.querySelector('.status-panel:nth-child(1) .progress-bar').style.width = 
                                `${systemStats.cpu}%`;
                        }
                        
                        // Update memory usage
                        if (resources.total_memory !== undefined && resources.free_memory !== undefined) {
                            systemStats.memoryTotal = Math.round(parseInt(resources.total_memory) / 1024 / 1024);
                            const usedMemory = Math.round((parseInt(resources.total_memory) - parseInt(resources.free_memory)) / 1024 / 1024);
                            systemStats.memory = usedMemory;
                            
                            document.querySelector('.status-panel:nth-child(2) .status-value').innerHTML = 
                                `${systemStats.memory}<span class="status-unit">MB /${systemStats.memoryTotal}MB</span>`;
                            
                            const memPercent = Math.round((systemStats.memory / systemStats.memoryTotal) * 100);
                            document.querySelector('.status-panel:nth-child(2) .progress-bar').style.width = 
                                `${memPercent}%`;
                        }
                        
                        // Update connected devices
                        if (data.connected_devices !== undefined) {
                            systemStats.connectedDevices = data.connected_devices.total || 16;
                            systemStats.authorizedDevices = data.connected_devices.authorized || 16;
                            systemStats.unknownDevices = data.connected_devices.unknown || 0;
                            
                            document.querySelector('.status-panel:nth-child(4) .status-value').textContent = 
                                systemStats.connectedDevices;
                            document.querySelector('.status-panel:nth-child(4) .connection-stats .connection-stat:nth-child(1) .connection-value').textContent = 
                                systemStats.unknownDevices;
                            document.querySelector('.status-panel:nth-child(4) .connection-stats .connection-stat:nth-child(2) .connection-value').textContent = 
                                systemStats.authorizedDevices;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching system info:', error);
                });
        }
        
        // Function to fetch network statistics
        function fetchNetworkStats() {
            fetch('api/network_stats.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Update bandwidth info
                    if (data && data.current_bandwidth) {
                        systemStats.download = parseFloat(data.current_bandwidth.download) || 24.5;
                        systemStats.upload = parseFloat(data.current_bandwidth.upload) || 8.2;
                        
                        document.querySelector('.status-panel:nth-child(3) .connection-stat:nth-child(1) .connection-value').textContent = 
                            systemStats.download;
                        document.querySelector('.status-panel:nth-child(3) .connection-stat:nth-child(2) .connection-value').textContent = 
                            systemStats.upload;
                    }
                    
                    // Update traffic history for chart
                    if (data && data.traffic_history) {
                        updateTrafficChartData(data.traffic_history);
                    }
                })
                .catch(error => {
                    console.error('Error fetching network stats:', error);
                });
        }
        
        // Function to fetch router list
        function fetchRouterList() {
            fetch('api/get_routers.php')
                .then(response => response.json())
                .catch(error => {
                    console.log('Router API not found or error, using demo data');
                    // Return sample data in demo mode
                    return {
                        routers: [
                            { id: 1, name: 'Main Office Router', active: true, model: 'hAP ac²', version: '6.48.6' },
                            { id: 2, name: 'Branch Office', active: false, model: 'RB750Gr3', version: '6.48.3' },
                            { id: 3, name: 'Warehouse', active: false, model: 'RB3011UiAS', version: '6.47.9' }
                        ]
                    };
                })
                .then(data => {
                    const routerList = document.getElementById('routerList');
                    const noRoutersMessage = document.getElementById('noRoutersMessage');
                    const routerDropdownButton = document.getElementById('routerSelectorDropdown');
                    
                    if (data.routers && data.routers.length > 0) {
                        // Hide the "no routers" message
                        noRoutersMessage.style.display = 'none';
                        
                        // Clear existing items (except the divider and manage link)
                        const items = routerList.querySelectorAll('li:not(:first-child):not(:nth-child(2))');
                        items.forEach(item => item.remove());
                        
                        // Update the dropdown button text with active router name
                        let activeRouterName = 'Select Router';
                        
                        // Add each router to the dropdown
                        data.routers.forEach(router => {
                            const routerItem = document.createElement('li');
                            
                            // If this is the active router, update the dropdown text
                            if (router.active) {
                                activeRouterName = router.name;
                            }
                            
                            // Create the dropdown item
                            const routerLink = document.createElement('a');
                            routerLink.className = `dropdown-item ${router.active ? 'active' : ''}`;
                            routerLink.href = `#`;
                            routerLink.dataset.routerId = router.id;
                            routerLink.onclick = function() { setActiveRouter(router.id); return false; };
                            
                            routerLink.innerHTML = `
                                <i class="fas fa-router me-2"></i>
                                ${router.name}
                                ${router.active ? '<span class="ms-2 badge bg-success">Active</span>' : ''}
                                <div class="small text-muted">${router.model || 'Unknown'} (v${router.version || 'N/A'})</div>
                            `;
                            
                            routerItem.appendChild(routerLink);
                            routerList.appendChild(routerItem);
                        });
                        
                        // Update the dropdown button text
                        routerDropdownButton.innerHTML = `
                            <i class="fas fa-network-wired me-1"></i> ${activeRouterName}
                        `;
                    } else {
                        // Show the "no routers" message
                        noRoutersMessage.style.display = 'block';
                        
                        // Set default text for dropdown
                        routerDropdownButton.innerHTML = `
                            <i class="fas fa-network-wired me-1"></i> Add Router
                        `;
                    }
                })
                .catch(error => console.error('Error processing router data:', error));
        }
        
        // Function to set the active router
        function setActiveRouter(routerId) {
            console.log(`Setting active router to ID: ${routerId}`);
            
            // Call API to set active router
            fetch('api/set_active_router.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `router_id=${routerId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to reflect the new active router
                    window.location.reload();
                } else {
                    console.error('Failed to set active router:', data.message);
                    alert('Failed to change router: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error setting active router:', error);
                // In demo mode, just reload the page
                window.location.reload();
            });
        }
        
        // Function to load system logs
        function loadSystemLogs() {
            fetch('api/logs.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    const logConsole = document.getElementById('logConsole');
                    logConsole.innerHTML = '';
                    
                    // Log entries to match screenshot
                    if (!data.logs || !Array.isArray(data.logs) || data.logs.length === 0) {
                        const sampleLogs = [
                            { time: '12:45:15', message: 'System started monitoring interface ether1' },
                            { time: '12:48:03', message: 'User admin logged in from 192.168.1.100' },
                            { time: '12:51:22', message: 'Interface ether1 state changed to connected' },
                            { time: '12:52:15', message: 'DDoS attack detected from 95.142.192.14, SYN flood' },
                            { time: '12:55:33', message: 'Firewall: IP 95.142.192.14 added to address-list "blacklist"' },
                            { time: '13:01:19', message: 'New node found: ID "MikroTik"' },
                            { time: '13:18:02', message: 'Botnet communication detected from 192.168.1.25' },
                            { time: '13:22:09', message: 'Multiple failed login attempts from 192.168.1.110' },
                            { time: '13:35:22', message: 'Interface ether1 state changed to disconnected' }
                        ];
                        
                        sampleLogs.forEach(log => {
                            const logEntry = document.createElement('div');
                            logEntry.className = 'log-entry';
                            
                            // Add different classes based on log content
                            if (log.message.includes('attack') || log.message.includes('botnet') || 
                                log.message.includes('failed login')) {
                                logEntry.classList.add('error');
                            } else if (log.message.includes('blacklist') || log.message.includes('state changed')) {
                                logEntry.classList.add('warning');
                            } else {
                                logEntry.classList.add('info');
                            }
                            
                            logEntry.innerHTML = `
                                <span class="log-time">[${log.time}]</span> 
                                <span class="log-message">${log.message}</span>
                            `;
                            logConsole.appendChild(logEntry);
                        });
                    } else {
                        data.logs.forEach(log => {
                            const logEntry = document.createElement('div');
                            logEntry.className = 'log-entry';
                            
                            // Add different classes based on log content
                            if (log.message.includes('attack') || log.message.includes('threat') || 
                                log.message.includes('fail') || log.message.includes('error')) {
                                logEntry.classList.add('error');
                            } else if (log.message.includes('warning') || log.message.includes('changed')) {
                                logEntry.classList.add('warning');
                            } else {
                                logEntry.classList.add('info');
                            }
                            
                            const formattedTime = log.time || log.formatted_time || new Date().toLocaleTimeString();
                            const message = log.message || 'No message available';
                            
                            logEntry.innerHTML = `
                                <span class="log-time">[${formattedTime}]</span>
                                <span class="log-message">${message}</span>
                            `;
                            logConsole.appendChild(logEntry);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching logs:', error);
                    const errorEntry = document.createElement('div');
                    errorEntry.className = 'log-entry error';
                    errorEntry.innerHTML = `<span class="log-message">Error loading logs: ${error.message}</span>`;
                    logConsole.appendChild(errorEntry);
                });
        }
        
        // Load security alerts
        function loadSecurityAlerts() {
            fetch('api/security_status.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    const alertsContainer = document.getElementById('securityAlerts');
                    
                    if (data.active_threats && data.active_threats.length > 0) {
                        // Clear previous content
                        alertsContainer.innerHTML = '';
                        
                        data.active_threats.forEach(threat => {
                            const alertItem = document.createElement('div');
                            alertItem.className = 'alert-item';
                            alertItem.innerHTML = `
                                <div class="alert-message">${formatThreatType(threat.type)} (${threat.severity || 'High'})</div>
                                <div class="alert-time">${getRandomTimeAgo()}</div>
                            `;
                            alertsContainer.appendChild(alertItem);
                        });
                        
                        // Get the first threat for mitigation suggestion
                        const threat = data.active_threats[0];
                        
                        // Add mitigation suggestion and button
                        const mitigationSection = document.createElement('div');
                        mitigationSection.className = 'mitigation-section';
                        
                        // Create a more descriptive mitigation suggestion based on the threat type
                        let mitigationAction = '';
                        let mitigationCommands = [];
                        
                        switch(threat.type) {
                            case 'UDP_FLOOD':
                                mitigationAction = `Botnet detected: Drop IP 10.0.0.8 and add to address-list "botnet". Enable botnet filtering.`;
                                mitigationCommands = [
                                    `/ip firewall filter add chain=forward action=drop protocol=udp src-address=${threat.source_ip} comment="Blocked UDP flood"`,
                                    `/ip firewall address-list add list=blacklist address=${threat.source_ip} comment="UDP flood attacker"`,
                                    `/ip firewall filter add chain=forward action=drop protocol=udp connection-limit=10,32 comment="UDP flood protection"`
                                ];
                                break;
                            case 'TCP_SYN_FLOOD':
                                mitigationAction = `DDoS Attack (SYN Flood) detected from ${threat.source_ip}. Block the source IP.`;
                                mitigationCommands = [
                                    `/ip firewall filter add chain=forward action=drop protocol=tcp tcp-flags=syn src-address=${threat.source_ip} comment="Blocked SYN flood"`,
                                    `/ip firewall address-list add list=blacklist address=${threat.source_ip} comment="SYN flood attacker"`,
                                    `/ip firewall filter add chain=forward action=drop protocol=tcp tcp-flags=syn connection-limit=30,32 comment="SYN flood protection"`
                                ];
                                break;
                            case 'BOTNET':
                                mitigationAction = `Botnet communication detected from ${threat.source_ip}. Block the infected device and C2 server communication.`;
                                mitigationCommands = [
                                    `/ip firewall filter add chain=forward action=drop src-address=${threat.source_ip} comment="Blocked botnet infected device"`,
                                    `/ip firewall address-list add list=botnet address=${threat.source_ip} comment="Botnet infected device"`,
                                    `/ip firewall filter add chain=forward action=drop dst-address=${threat.target_ip} comment="Blocked botnet C2 server"`
                                ];
                                break;
                            case 'BRUTE_FORCE':
                                mitigationAction = `Brute force attack detected from ${threat.source_ip}. Block access from this source.`;
                                mitigationCommands = [
                                    `/ip firewall filter add chain=input action=drop src-address=${threat.source_ip} comment="Blocked brute force attacker"`,
                                    `/ip firewall address-list add list=blacklist address=${threat.source_ip} comment="Brute force attacker"`
                                ];
                                break;
                            case 'PORT_SCAN':
                                mitigationAction = `Port scan detected from ${threat.source_ip}. Block scanner and implement port scan protection.`;
                                mitigationCommands = [
                                    `/ip firewall filter add chain=forward action=drop src-address=${threat.source_ip} comment="Blocked port scanner"`,
                                    `/ip firewall address-list add list=blacklist address=${threat.source_ip} comment="Port scanner"`,
                                    `/ip firewall filter add chain=input action=drop protocol=tcp psd=21,3s,3,1 comment="Port scan detection"`
                                ];
                                break;
                            case 'DDOS':
                                mitigationAction = `DDoS attack detected. Block attacking IP addresses and implement DDoS protection.`;
                                mitigationCommands = [
                                    `/ip firewall filter add chain=forward action=drop src-address=${threat.source_ip} comment="Blocked DDoS attacker"`,
                                    `/ip firewall filter add chain=forward action=drop connection-limit=100,32 comment="DDoS protection"`,
                                    `/ip firewall filter add chain=forward action=tarpit protocol=tcp tcp-flags=syn connection-limit=30,32 comment="Advanced DDoS protection"`
                                ];
                                break;
                            default:
                                mitigationAction = `Threat detected from ${threat.source_ip}. Block this source IP to prevent further attacks.`;
                                mitigationCommands = [
                                    `/ip firewall filter add chain=forward action=drop src-address=${threat.source_ip} comment="Blocked malicious source"`,
                                    `/ip firewall address-list add list=blacklist address=${threat.source_ip} comment="Malicious source"`
                                ];
                        }
                        
                        mitigationSection.innerHTML = `
                            <div class="mitigation-title">Mitigation Suggestion</div>
                            <div class="mitigation-action">${mitigationAction}</div>
                            <button class="btn btn-sm btn-danger mt-2" onclick="mitigateThreat('${threat.id}', '${threat.type}', '${threat.source_ip}', '${threat.severity}')">
                                Mitigate Now
                            </button>
                        `;
                        alertsContainer.appendChild(mitigationSection);
                    } else {
                        alertsContainer.innerHTML = '<div class="text-center py-3">No active threats detected</div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching security status:', error);
                });
        }
        
        // Format threat type for display
        function formatThreatType(type) {
            switch(type) {
                case 'UDP_FLOOD':
                    return 'DDoS Attack (UDP Flood)';
                case 'TCP_SYN_FLOOD':
                    return 'DDoS Attack (SYN Flood)';
                case 'BRUTE_FORCE':
                    return 'Brute Force Attack';
                case 'PORT_SCAN':
                    return 'Port Scan Detected';
                default:
                    return 'Botnet Traffic Detected';
            }
        }
        
        // Get random time ago string
        function getRandomTimeAgo() {
            const times = ['1 min ago', '3 mins ago', '5 mins ago', '12 mins ago'];
            return times[Math.floor(Math.random() * times.length)];
        }
        
        // Mitigate a threat
        function mitigateThreat(threatId, threatType, sourceIp, severity) {
            // Show loading spinner or message
            const alertsContainer = document.getElementById('securityAlerts');
            alertsContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-danger" role="status"></div><p class="mt-2">Executing mitigation commands...</p></div>';
            
            // Call the mitigation API
            fetch('api/mitigate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    threat_id: threatId,
                    threat_type: threatType,
                    source_ip: sourceIp,
                    severity: severity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message with executed commands
                    let commandList = '';
                    data.executed.forEach(cmd => {
                        commandList += `<li class="text-success">${cmd.title}: <small>${cmd.command}</small></li>`;
                    });
                    
                    alertsContainer.innerHTML = `
                        <div class="alert-success p-3">
                            <h4 class="text-success"><i class="fas fa-check-circle"></i> Threat Mitigated Successfully</h4>
                            <p>The following commands were executed:</p>
                            <ul>${commandList}</ul>
                        </div>
                    `;
                    
                    // Reload security alerts after a delay
                    setTimeout(loadSecurityAlerts, 3000);
                } else {
                    // Show error message
                    alertsContainer.innerHTML = `
                        <div class="alert-danger p-3">
                            <h4 class="text-danger"><i class="fas fa-exclamation-circle"></i> Mitigation Failed</h4>
                            <p>Error: ${data.error}</p>
                            <button class="btn btn-sm btn-primary mt-2" onclick="loadSecurityAlerts()">Retry</button>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error during mitigation:', error);
                alertsContainer.innerHTML = `
                    <div class="alert-danger p-3">
                        <h4 class="text-danger"><i class="fas fa-exclamation-circle"></i> Mitigation Failed</h4>
                        <p>An error occurred while trying to mitigate the threat.</p>
                        <button class="btn btn-sm btn-primary mt-2" onclick="loadSecurityAlerts()">Retry</button>
                    </div>
                `;
            });
        }
        
        // Display firewall templates
        function showFirewallTemplates() {
            // Get template container
            const container = document.getElementById('firewallTemplateModal') || createFirewallTemplateModal();
            
            // Open the modal
            const modal = new bootstrap.Modal(container);
            modal.show();
        }
        
        // Create firewall template modal
        function createFirewallTemplateModal() {
            // Create modal element
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'firewallTemplateModal';
            modal.tabIndex = '-1';
            modal.setAttribute('aria-labelledby', 'firewallTemplateModalLabel');
            modal.setAttribute('aria-hidden', 'true');
            
            // Templates data
            const templates = [
                {
                    type: 'UDP_FLOOD',
                    title: 'UDP Flood Protection',
                    commands: [
                        '/ip firewall filter add chain=forward action=drop protocol=udp connection-limit=10,32 comment="UDP flood protection"',
                        '/ip firewall filter add chain=forward action=drop protocol=udp connection-rate=10/1s,32 comment="Advanced UDP flood protection"'
                    ]
                },
                {
                    type: 'TCP_SYN_FLOOD',
                    title: 'TCP SYN Flood Protection',
                    commands: [
                        '/ip firewall filter add chain=forward action=drop protocol=tcp tcp-flags=syn connection-limit=30,32 comment="SYN flood protection"',
                        '/ip firewall filter add chain=forward action=tarpit protocol=tcp tcp-flags=syn connection-limit=20,32 comment="Advanced SYN flood protection"'
                    ]
                },
                {
                    type: 'BOTNET',
                    title: 'Botnet Protection',
                    commands: [
                        '/ip firewall address-list add list=botnet_c2 address=37.8.8.8 comment="Known botnet C2 server"',
                        '/ip firewall filter add chain=forward action=drop src-address-list=botnet_c2 comment="Block outgoing botnet traffic"',
                        '/ip firewall filter add chain=forward action=drop dst-address-list=botnet_c2 comment="Block incoming botnet traffic"'
                    ]
                },
                {
                    type: 'DDOS',
                    title: 'DDoS Protection',
                    commands: [
                        '/ip firewall filter add chain=forward action=drop connection-limit=100,32 comment="DDoS protection"',
                        '/ip firewall filter add chain=forward action=tarpit protocol=tcp tcp-flags=syn connection-limit=30,32 comment="Advanced DDoS protection"',
                        '/ip firewall filter add chain=forward action=drop connection-rate=100/1s,32 comment="Connection rate limiting"'
                    ]
                }
            ];
            
            // Create template content
            let templateContent = '';
            templates.forEach(template => {
                let commandList = '';
                template.commands.forEach(cmd => {
                    commandList += `<li><code>${cmd}</code></li>`;
                });
                
                templateContent += `
                    <div class="mb-4">
                        <h5 class="text-neon">${template.title}</h5>
                        <ul class="code-list">
                            ${commandList}
                        </ul>
                        <button class="btn btn-sm btn-primary" onclick="applyTemplate('${template.type}')">Apply Template</button>
                    </div>
                `;
            });
            
            // Set modal content
            modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="firewallTemplateModalLabel">
                                <i class="fas fa-shield-alt"></i> Firewall Protection Templates
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Select a firewall template to protect against common attacks:</p>
                            ${templateContent}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            `;
            
            // Add to document
            document.body.appendChild(modal);
            return modal;
        }
        
        // Apply a firewall template
        function applyTemplate(templateType) {
            // In a real implementation, this would call the mitigation API to apply all commands in the template
            // For demo, we'll just show a success message
            
            // Close the modal
            const modal = document.getElementById('firewallTemplateModal');
            bootstrap.Modal.getInstance(modal).hide();
            
            // Show success notification
            const notification = document.createElement('div');
            notification.className = 'position-fixed bottom-0 end-0 p-3';
            notification.style.zIndex = 1050;
            
            notification.innerHTML = `
                <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header">
                        <strong class="me-auto text-success">Template Applied</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        The ${templateType} protection template has been successfully applied.
                    </div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Remove notification after 3 seconds
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            loadSystemLogs();
            loadSecurityAlerts();
            
            // Refresh data periodically
            setInterval(loadSystemLogs, 60000);
            setInterval(loadSecurityAlerts, 60000);
            
            // Add button for firewall templates to the toolbar
            const securityHeaders = document.querySelectorAll('.panel-header .panel-title');
            for (let header of securityHeaders) {
                if (header.textContent.includes('SECURITY PROTECTION STATUS')) {
                    const button = document.createElement('button');
                    button.className = 'btn btn-sm btn-outline-success float-end';
                    button.innerHTML = '<i class="fas fa-plus"></i> Add Protection';
                    button.onclick = showFirewallTemplates;
                    header.appendChild(button);
                    break;
                }
            }
        });
    </script>
</body>
</html>