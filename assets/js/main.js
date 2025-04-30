/**
 * H4N5VS Mikrotik System Security
 * Main JavaScript file
 */

// Initialize on document load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Feather icons
    feather.replace();
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Setup current datetime display on dashboard
    updateDateTime();
    setInterval(updateDateTime, 1000);
    
    // Fetch initial data
    if (document.getElementById('system-info-container')) {
        fetchSystemInfo();
        fetchNetworkStats();
        fetchActiveConnections();
        fetchSecurityStatus();
        fetchLiveLogs();
        
        // Initialize network traffic chart if element exists
        if (document.getElementById('traffic-chart')) {
            initTrafficChart();
        }
        
        // Set up periodic data refresh
        setInterval(fetchSystemInfo, 10000); // Every 10 seconds
        setInterval(fetchNetworkStats, 5000); // Every 5 seconds
        setInterval(fetchActiveConnections, 5000); // Every 5 seconds
        setInterval(fetchSecurityStatus, 3000); // Every 3 seconds
        setInterval(fetchLiveLogs, 2000); // Every 2 seconds
        setInterval(updateTrafficChart, 5000); // Every 5 seconds
    }
    
    // Setup mitigate threat button action
    const mitigateBtn = document.getElementById('mitigate-threat-btn');
    if (mitigateBtn) {
        mitigateBtn.addEventListener('click', mitigateThreat);
    }
});

/**
 * Update the current date and time display
 */
function updateDateTime() {
    const datetimeElement = document.getElementById('current-datetime');
    if (datetimeElement) {
        const now = new Date();
        datetimeElement.textContent = now.toLocaleString('en-US', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        });
    }
}

/**
 * Fetch system information from the API
 */
function fetchSystemInfo() {
    const container = document.getElementById('system-info-container');
    if (!container) return;
    
    fetch('api/system_info_demo.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            container.innerHTML = `
                <div class="system-info-item">
                    <span class="system-info-label">Board Model:</span>
                    <span class="system-info-value">${data.board_name}</span>
                </div>
                <div class="system-info-item">
                    <span class="system-info-label">RouterOS Version:</span>
                    <span class="system-info-value">${data.version}</span>
                </div>
                <div class="system-info-item">
                    <span class="system-info-label">CPU Load:</span>
                    <span class="system-info-value">${data.cpu_load}%</span>
                </div>
                <div class="progress">
                    <div class="progress-bar progress-bar-cpu" role="progressbar" 
                         style="width: ${data.cpu_load}%" 
                         aria-valuenow="${data.cpu_load}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="system-info-item mt-3">
                    <span class="system-info-label">Memory Usage:</span>
                    <span class="system-info-value">${data.memory_used} / ${data.memory_total} MB</span>
                </div>
                <div class="progress">
                    <div class="progress-bar progress-bar-memory" role="progressbar" 
                         style="width: ${data.memory_percent}%" 
                         aria-valuenow="${data.memory_percent}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="system-info-item mt-3">
                    <span class="system-info-label">Uptime:</span>
                    <span class="system-info-value">${data.uptime}</span>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error fetching system info:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    Failed to load system information: ${error.message}
                </div>
            `;
        });
}

/**
 * Fetch network statistics from the API
 */
function fetchNetworkStats() {
    const container = document.getElementById('network-stats-container');
    if (!container) return;
    
    fetch('api/network_stats.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            let html = '';
            
            data.interfaces.forEach(interface => {
                html += `
                    <div class="network-stats-item">
                        <div class="interface-name">${interface.name}</div>
                        <div class="interface-stats">
                            <span class="stat-label">Rx</span>
                            <span class="stat-value">${interface.rx_rate}</span>
                        </div>
                        <div class="interface-stats">
                            <span class="stat-label">Tx</span>
                            <span class="stat-value">${interface.tx_rate}</span>
                        </div>
                        <div class="interface-stats">
                            <span class="stat-label">Total Rx</span>
                            <span class="stat-value">${interface.rx_total}</span>
                        </div>
                        <div class="interface-stats">
                            <span class="stat-label">Total Tx</span>
                            <span class="stat-value">${interface.tx_total}</span>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        })
        .catch(error => {
            console.error('Error fetching network stats:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    Failed to load network statistics: ${error.message}
                </div>
            `;
        });
}

/**
 * Fetch active connections from the API
 */
function fetchActiveConnections() {
    const container = document.getElementById('active-connections-container');
    if (!container) return;
    
    fetch('api/active_connections.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            container.innerHTML = `
                <div class="connections-count">
                    <span class="connections-count-number">${data.total_connections}</span>
                    <span class="connections-count-label">Active Connections</span>
                </div>
                
                <div class="connection-stats">
                    <div class="connection-stat-item">
                        <div class="connection-stat-value">${data.tcp_connections}</div>
                        <div class="connection-stat-label">TCP</div>
                    </div>
                    <div class="connection-stat-item">
                        <div class="connection-stat-value">${data.udp_connections}</div>
                        <div class="connection-stat-label">UDP</div>
                    </div>
                    <div class="connection-stat-item">
                        <div class="connection-stat-value">${data.other_connections}</div>
                        <div class="connection-stat-label">Other</div>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error fetching active connections:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    Failed to load connection information: ${error.message}
                </div>
            `;
        });
}

/**
 * Fetch security status from the API
 */
function fetchSecurityStatus() {
    const container = document.getElementById('security-status-container');
    const statusBadge = document.getElementById('security-status-badge');
    const threatsContainer = document.getElementById('threats-container');
    
    if (!container) return;
    
    fetch('api/security_status_demo.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Update security status badge
            if (statusBadge) {
                statusBadge.textContent = data.status.toUpperCase();
                statusBadge.className = 'security-status ' + data.status.toLowerCase();
            }
            
            // Update security status container
            container.innerHTML = `
                <div class="text-center mb-3">
                    <div class="status-icon ${data.status.toLowerCase()}">
                        <i data-feather="${data.status === 'secure' ? 'shield' : 'shield-off'}" 
                           class="feather-large ${data.status === 'secure' ? 'text-success' : 'text-danger'}"></i>
                    </div>
                    <h4 class="mt-3">${data.message}</h4>
                </div>
                
                <div class="security-stats">
                    <div class="system-info-item">
                        <span class="system-info-label">Firewall Rules:</span>
                        <span class="system-info-value">${data.firewall_rules}</span>
                    </div>
                    <div class="system-info-item">
                        <span class="system-info-label">Blacklisted IPs:</span>
                        <span class="system-info-value">${data.blacklisted_ips}</span>
                    </div>
                    <div class="system-info-item">
                        <span class="system-info-label">Detected Threats:</span>
                        <span class="system-info-value">${data.detected_threats}</span>
                    </div>
                    <div class="system-info-item">
                        <span class="system-info-label">Last Scan:</span>
                        <span class="system-info-value">${data.last_scan}</span>
                    </div>
                </div>
            `;
            
            // Replace feather icons
            feather.replace();
            
            // Update threats container if there are active threats
            if (threatsContainer && data.active_threats && data.active_threats.length > 0) {
                let threatsList = '';
                
                data.active_threats.forEach(threat => {
                    threatsList += `
                        <div class="threat-item">
                            <div class="threat-header">
                                <span class="threat-type">${threat.type.toUpperCase()} ATTACK</span>
                                <span class="threat-timestamp">${threat.timestamp}</span>
                            </div>
                            <div class="threat-details">
                                <div>Source IP: <span class="threat-source">${threat.source_ip}</span></div>
                                <div>Target: ${threat.target}</div>
                                <div>Connections: ${threat.connections}</div>
                                <div>Severity: ${threat.severity}</div>
                            </div>
                            <div class="threat-actions">
                                <button class="btn btn-sm btn-outline-secondary view-details-btn" 
                                        data-threat-id="${threat.id}">Details</button>
                                <button class="btn btn-sm btn-danger mitigate-btn" 
                                        data-threat-id="${threat.id}" 
                                        data-source-ip="${threat.source_ip}"
                                        data-threat-type="${threat.type}">Mitigate</button>
                            </div>
                        </div>
                    `;
                });
                
                threatsContainer.innerHTML = threatsList;
                
                // Add event listeners to the new buttons
                document.querySelectorAll('.mitigate-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        showThreatDetails(this.dataset.threatId, this.dataset.sourceIp, this.dataset.threatType);
                    });
                });
                
                document.querySelectorAll('.view-details-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        // Future expansion: view detailed threat info
                    });
                });
            } else if (threatsContainer) {
                threatsContainer.innerHTML = `
                    <div class="text-center py-5">
                        <i data-feather="shield" class="feather-large text-success mb-3"></i>
                        <h4>No Active Threats Detected</h4>
                        <p class="text-muted">System is actively monitoring for potential threats</p>
                    </div>
                `;
                feather.replace();
            }
            
            // Show threat alert modal if new threats detected
            if (data.new_threats && data.new_threats.length > 0) {
                showThreatAlert(data.new_threats[0]);
            }
        })
        .catch(error => {
            console.error('Error fetching security status:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    Failed to load security status: ${error.message}
                </div>
            `;
        });
}

/**
 * Fetch live logs from the API
 */
function fetchLiveLogs() {
    const container = document.getElementById('live-logs-container');
    if (!container) return;
    
    fetch('api/logs_demo.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            let html = '';
            
            data.logs.forEach(log => {
                html += `
                    <div class="log-entry">
                        <span class="log-time">${log.time}</span>
                        <span class="log-level-${log.level.toLowerCase()}">[${log.level}]</span>
                        <span class="log-message">${log.message}</span>
                    </div>
                `;
            });
            
            container.innerHTML = html;
            
            // Auto-scroll to the bottom of logs
            container.scrollTop = container.scrollHeight;
        })
        .catch(error => {
            console.error('Error fetching logs:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    Failed to load logs: ${error.message}
                </div>
            `;
        });
}

/**
 * Initialize network traffic chart
 */
function initTrafficChart() {
    const ctx = document.getElementById('traffic-chart').getContext('2d');
    
    // Create empty datasets
    const initialData = {
        labels: [],
        datasets: [
            {
                label: 'Download (Rx)',
                data: [],
                borderColor: 'rgba(10, 255, 10, 1)',
                backgroundColor: 'rgba(10, 255, 10, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            },
            {
                label: 'Upload (Tx)',
                data: [],
                borderColor: 'rgba(51, 181, 229, 1)', 
                backgroundColor: 'rgba(51, 181, 229, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }
        ]
    };
    
    // Generate time labels for the past 60 seconds
    for (let i = 0; i < 60; i++) {
        initialData.labels.push('');
        initialData.datasets[0].data.push(0);
        initialData.datasets[1].data.push(0);
    }
    
    // Create chart
    window.trafficChart = new Chart(ctx, {
        type: 'line',
        data: initialData,
        options: {
            responsive: true,
            animation: {
                duration: 0
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(10, 255, 10, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(214, 226, 255, 0.7)'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(10, 255, 10, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(214, 226, 255, 0.7)'
                    },
                    title: {
                        display: true,
                        text: 'Bandwidth (Mbps)',
                        color: 'rgba(214, 226, 255, 0.7)'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: 'rgba(214, 226, 255, 0.7)'
                    }
                }
            }
        }
    });
    
    // Immediately update chart with real data
    updateTrafficChart();
}

/**
 * Update the traffic chart with new data
 */
function updateTrafficChart() {
    if (!window.trafficChart) return;
    
    fetch('api/network_stats.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Add current time to labels
            const now = new Date();
            const timeLabel = now.getHours().toString().padStart(2, '0') + ':' + 
                             now.getMinutes().toString().padStart(2, '0') + ':' + 
                             now.getSeconds().toString().padStart(2, '0');
            
            // Sum all interface traffic for total network usage
            let totalRx = 0;
            let totalTx = 0;
            
            data.interfaces.forEach(interface => {
                // Parse numeric values from strings like "1.2 Mbps"
                const rxMatch = interface.rx_rate.match(/(\d+\.?\d*)/);
                const txMatch = interface.tx_rate.match(/(\d+\.?\d*)/);
                
                if (rxMatch && rxMatch[1]) {
                    totalRx += parseFloat(rxMatch[1]);
                }
                
                if (txMatch && txMatch[1]) {
                    totalTx += parseFloat(txMatch[1]);
                }
            });
            
            // Update chart data
            window.trafficChart.data.labels.push(timeLabel);
            window.trafficChart.data.datasets[0].data.push(totalRx);
            window.trafficChart.data.datasets[1].data.push(totalTx);
            
            // Remove oldest data point if more than 60 points
            if (window.trafficChart.data.labels.length > 60) {
                window.trafficChart.data.labels.shift();
                window.trafficChart.data.datasets[0].data.shift();
                window.trafficChart.data.datasets[1].data.shift();
            }
            
            // Update chart
            window.trafficChart.update();
        })
        .catch(error => {
            console.error('Error updating traffic chart:', error);
        });
}

/**
 * Show threat alert modal with details
 */
function showThreatAlert(threat) {
    const modal = new bootstrap.Modal(document.getElementById('threatAlertModal'));
    const detailsContainer = document.getElementById('threat-alert-details');
    const mitigateBtn = document.getElementById('mitigate-threat-btn');
    
    if (detailsContainer) {
        detailsContainer.innerHTML = `
            <div class="alert alert-danger">
                <h5>Threat Details:</h5>
                <ul>
                    <li><strong>Type:</strong> ${threat.type.toUpperCase()} ATTACK</li>
                    <li><strong>Source IP:</strong> ${threat.source_ip}</li>
                    <li><strong>Target:</strong> ${threat.target}</li>
                    <li><strong>Connections:</strong> ${threat.connections}</li>
                    <li><strong>Severity:</strong> ${threat.severity}</li>
                    <li><strong>Detected at:</strong> ${threat.timestamp}</li>
                </ul>
                <p>Do you want to automatically mitigate this threat?</p>
            </div>
        `;
    }
    
    // Set up the mitigation button with threat data
    if (mitigateBtn) {
        mitigateBtn.dataset.threatId = threat.id;
        mitigateBtn.dataset.sourceIp = threat.source_ip;
        mitigateBtn.dataset.threatType = threat.type;
    }
    
    // Show the modal
    modal.show();
}

/**
 * Show threat details for mitigation
 */
function showThreatDetails(threatId, sourceIp, threatType) {
    const modal = new bootstrap.Modal(document.getElementById('threatAlertModal'));
    const detailsContainer = document.getElementById('threat-alert-details');
    const mitigateBtn = document.getElementById('mitigate-threat-btn');
    
    if (detailsContainer) {
        detailsContainer.innerHTML = `
            <div class="alert alert-danger">
                <h5>Mitigate Threat:</h5>
                <p>You are about to block IP <strong>${sourceIp}</strong> detected for <strong>${threatType}</strong> attack.</p>
                <p>This will add the IP to the blacklist and drop all traffic from this source.</p>
                <p>Proceed with mitigation?</p>
            </div>
        `;
    }
    
    // Set up the mitigation button with threat data
    if (mitigateBtn) {
        mitigateBtn.dataset.threatId = threatId;
        mitigateBtn.dataset.sourceIp = sourceIp;
        mitigateBtn.dataset.threatType = threatType;
    }
    
    // Show the modal
    modal.show();
}

/**
 * Mitigate the current threat
 */
function mitigateThreat() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('threatAlertModal'));
    const mitigateBtn = document.getElementById('mitigate-threat-btn');
    
    if (!mitigateBtn) return;
    
    const threatId = mitigateBtn.dataset.threatId;
    const sourceIp = mitigateBtn.dataset.sourceIp;
    const threatType = mitigateBtn.dataset.threatType;
    
    // Disable button and show loading state
    mitigateBtn.disabled = true;
    mitigateBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mitigating...`;
    
    // Send mitigation request
    fetch('includes/mitigation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `threatId=${threatId}&sourceIp=${sourceIp}&threatType=${threatType}&action=mitigate`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success message
            const detailsContainer = document.getElementById('threat-alert-details');
            if (detailsContainer) {
                detailsContainer.innerHTML = `
                    <div class="alert alert-success">
                        <h5>Mitigation Successful!</h5>
                        <p>${data.message}</p>
                        <ul>
                            <li>IP ${sourceIp} has been added to the blacklist</li>
                            <li>All connections from this IP have been terminated</li>
                            <li>Firewall rules have been updated</li>
                        </ul>
                    </div>
                `;
            }
            
            // Reset and close modal after 3 seconds
            setTimeout(() => {
                mitigateBtn.disabled = false;
                mitigateBtn.innerHTML = 'Mitigate Threat';
                modal.hide();
                
                // Refresh security status and threats
                fetchSecurityStatus();
            }, 3000);
        } else {
            throw new Error(data.message || 'Mitigation failed');
        }
    })
    .catch(error => {
        console.error('Error during mitigation:', error);
        
        // Show error message
        const detailsContainer = document.getElementById('threat-alert-details');
        if (detailsContainer) {
            detailsContainer.innerHTML = `
                <div class="alert alert-danger">
                    <h5>Mitigation Failed</h5>
                    <p>${error.message}</p>
                    <p>Please try again or mitigate manually.</p>
                </div>
            `;
        }
        
        // Reset button
        mitigateBtn.disabled = false;
        mitigateBtn.innerHTML = 'Retry Mitigation';
    });
}
