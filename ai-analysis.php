<?php
// Include configuration file
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Check router connection
$router_connected = isset($_SESSION['router_ip']) && 
                    isset($_SESSION['router_user']) && 
                    isset($_SESSION['router_pass']);

// Set page title
$pageTitle = 'AI Analysis | H4N5VS Mikrotik System Security';

// Include header
include_once 'includes/header.php';

// Check OpenAI API key
$openai_key_set = !empty(getenv('OPENAI_API_KEY'));
?>

<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-brain mr-2"></i> AI Pattern Recognition</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group mr-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshAnalysis">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="exportData">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="analysisTimeRange" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="far fa-clock"></i> Last 24 hours
                </button>
                <div class="dropdown-menu" aria-labelledby="analysisTimeRange">
                    <a class="dropdown-item" href="#" data-range="1">Last hour</a>
                    <a class="dropdown-item" href="#" data-range="6">Last 6 hours</a>
                    <a class="dropdown-item active" href="#" data-range="24">Last 24 hours</a>
                    <a class="dropdown-item" href="#" data-range="72">Last 3 days</a>
                    <a class="dropdown-item" href="#" data-range="168">Last week</a>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$router_connected): ?>
    <div class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-triangle"></i> Router connection not configured. Please <a href="config.php" class="alert-link">configure your router</a> first.
    </div>
    <?php endif; ?>

    <?php if (!$openai_key_set): ?>
    <div class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-triangle"></i> OpenAI API key not configured. Some AI analysis features may be limited.
    </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-shield-alt"></i> Threat Overview
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="threat-score-circle position-relative mx-auto" style="width: 120px; height: 120px;">
                                <div class="position-absolute d-flex align-items-center justify-content-center w-100 h-100">
                                    <span class="h1 mb-0 counter" id="threatScore">0</span>
                                </div>
                                <canvas id="threatScoreChart" width="120" height="120"></canvas>
                            </div>
                            <p class="mt-2">Threat Score</p>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <div class="small text-muted">Critical Threats</div>
                                    <div class="h3 mb-0 counter text-danger" id="criticalThreats">0</div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted">High Threats</div>
                                    <div class="h3 mb-0 counter text-warning" id="highThreats">0</div>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted">Medium Threats</div>
                                    <div class="h3 mb-0 counter text-info" id="mediumThreats">0</div>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted">Low Threats</div>
                                    <div class="h3 mb-0 counter text-success" id="lowThreats">0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-chart-area"></i> Detection Trends
                </div>
                <div class="card-body">
                    <canvas id="detectionTrendChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-exclamation-triangle"></i> Detected Threats
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="threatsTable">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Type</th>
                                    <th>Severity</th>
                                    <th>Source IP</th>
                                    <th>Confidence</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="threatsList">
                                <tr class="text-center">
                                    <td colspan="7">Loading threat data...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> Attack Distribution
                </div>
                <div class="card-body">
                    <canvas id="attackDistributionChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-search"></i> Anomaly Detection
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped" id="anomaliesTable">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Type</th>
                                    <th>Severity</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody id="anomaliesList">
                                <tr class="text-center">
                                    <td colspan="4">Loading anomaly data...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-lightbulb"></i> AI Recommendations
                </div>
                <div class="card-body">
                    <div id="recommendationsList">
                        <p class="text-center">Loading recommendations...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-sliders-h"></i> AI Learning Settings
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="thresholdValue">Detection Threshold (Sensitivity)</label>
                        <input type="range" class="custom-range" min="0" max="1" step="0.05" id="thresholdValue" value="0.7">
                        <div class="d-flex justify-content-between">
                            <small>Higher Sensitivity</small>
                            <small id="thresholdDisplay">0.7</small>
                            <small>Higher Precision</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="learningMode" checked>
                            <label class="custom-control-label" for="learningMode">Automatic Learning Mode</label>
                        </div>
                        <small class="form-text text-muted">When enabled, the system will learn from new data and adjust detection patterns over time.</small>
                    </div>
                    <button class="btn btn-primary" id="saveAISettings">Save Settings</button>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-code"></i> Pattern Recognition Insights
                </div>
                <div class="card-body">
                    <h5>Recognized Patterns</h5>
                    <ul class="code-list" id="patternsList">
                        <li class="text-center">Loading pattern data...</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Threat Details Modal -->
<div class="modal fade" id="threatDetailsModal" tabindex="-1" role="dialog" aria-labelledby="threatDetailsTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="threatDetailsTitle">Threat Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="threatDetailsBody">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="mitigateButton"><i class="fas fa-shield-alt"></i> Mitigate Now</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize charts
    let threatScoreChart, detectionTrendChart, attackDistributionChart;
    
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize threat score chart
        const threatScoreCtx = document.getElementById('threatScoreChart').getContext('2d');
        threatScoreChart = new Chart(threatScoreCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [0, 100],
                    backgroundColor: ['rgba(255, 99, 132, 0.8)', 'rgba(200, 200, 200, 0.1)'],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '80%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    }
                }
            }
        });
        
        // Initialize detection trend chart
        const detectionTrendCtx = document.getElementById('detectionTrendChart').getContext('2d');
        detectionTrendChart = new Chart(detectionTrendCtx, {
            type: 'line',
            data: {
                labels: Array(24).fill(0).map((_, i) => {
                    const d = new Date();
                    d.setHours(d.getHours() - (23 - i));
                    return d.getHours() + ':00';
                }),
                datasets: [{
                    label: 'Threat Score',
                    data: Array(24).fill(0),
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
        
        // Initialize attack distribution chart
        const attackDistributionCtx = document.getElementById('attackDistributionChart').getContext('2d');
        attackDistributionChart = new Chart(attackDistributionCtx, {
            type: 'pie',
            data: {
                labels: ['DDoS', 'Port Scan', 'Brute Force', 'Suspicious', 'Other'],
                datasets: [{
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
        
        // Initialize threshold value display
        document.getElementById('thresholdValue').addEventListener('input', function() {
            document.getElementById('thresholdDisplay').textContent = this.value;
        });
        
        // Fetch AI analysis data
        fetchAIAnalysisData();
        
        // Set up refresh button
        document.getElementById('refreshAnalysis').addEventListener('click', function() {
            fetchAIAnalysisData();
        });
        
        // Set up threshold save button
        document.getElementById('saveAISettings').addEventListener('click', function() {
            const threshold = document.getElementById('thresholdValue').value;
            const learning = document.getElementById('learningMode').checked;
            
            // Send settings to server
            fetch('api/ai_settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    threshold: threshold,
                    learning_enabled: learning
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('AI settings saved successfully!');
                } else {
                    alert('Error saving AI settings: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error saving AI settings:', error);
                alert('Error saving AI settings. Please try again.');
            });
        });
        
        // Set up threat details modal
        document.body.addEventListener('click', function(e) {
            if (e.target.matches('.view-threat-btn') || e.target.closest('.view-threat-btn')) {
                const btn = e.target.matches('.view-threat-btn') ? e.target : e.target.closest('.view-threat-btn');
                const threatId = btn.getAttribute('data-id');
                
                // Fetch threat details
                fetch(`api/ai_analysis.php?action=get_threat&id=${threatId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayThreatDetails(data.threat);
                    } else {
                        document.getElementById('threatDetailsBody').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching threat details:', error);
                    document.getElementById('threatDetailsBody').innerHTML = '<div class="alert alert-danger">Error fetching threat details. Please try again.</div>';
                });
                
                $('#threatDetailsModal').modal('show');
            }
        });
        
        // Set up mitigation button
        document.getElementById('mitigateButton').addEventListener('click', function() {
            const threatId = this.getAttribute('data-id');
            
            // Check if threat ID is available
            if (!threatId) {
                alert('No threat selected for mitigation.');
                return;
            }
            
            // Send mitigation request
            fetch('api/mitigate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    threat_id: threatId,
                    auto_apply: false
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Display mitigation commands
                    let commandsHtml = '<h5>Mitigation Commands</h5><pre class="bg-dark text-light p-3">';
                    data.result.commands.forEach(cmd => {
                        let cmdStr = cmd.command;
                        for (const [key, value] of Object.entries(cmd.params)) {
                            cmdStr += ` ${key}=${value}`;
                        }
                        commandsHtml += cmdStr + '\n';
                    });
                    commandsHtml += '</pre>';
                    
                    // Add apply button
                    commandsHtml += `
                        <div class="alert alert-info">
                            <strong>Note:</strong> The above commands have been generated but not applied yet.
                        </div>
                        <button class="btn btn-danger btn-block" id="applyMitigationButton" data-id="${threatId}">
                            <i class="fas fa-shield-alt"></i> Apply Mitigation
                        </button>
                    `;
                    
                    document.getElementById('threatDetailsBody').innerHTML += commandsHtml;
                    
                    // Setup apply button handler
                    document.getElementById('applyMitigationButton').addEventListener('click', function() {
                        const threatId = this.getAttribute('data-id');
                        
                        // Send apply request
                        fetch('api/mitigate.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                threat_id: threatId,
                                auto_apply: true
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Mitigation applied successfully!');
                                $('#threatDetailsModal').modal('hide');
                                // Refresh data
                                fetchAIAnalysisData();
                            } else {
                                alert('Error applying mitigation: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error applying mitigation:', error);
                            alert('Error applying mitigation. Please try again.');
                        });
                    });
                } else {
                    alert('Error generating mitigation: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error generating mitigation:', error);
                alert('Error generating mitigation. Please try again.');
            });
        });
        
        // Set up time range selector
        document.querySelectorAll('.dropdown-menu a[data-range]').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const range = parseInt(this.getAttribute('data-range'));
                
                // Update button text
                let rangeText;
                if (range === 1) rangeText = 'Last hour';
                else if (range === 6) rangeText = 'Last 6 hours';
                else if (range === 24) rangeText = 'Last 24 hours';
                else if (range === 72) rangeText = 'Last 3 days';
                else if (range === 168) rangeText = 'Last week';
                
                document.getElementById('analysisTimeRange').textContent = rangeText;
                
                // Update active class
                document.querySelectorAll('.dropdown-menu a[data-range]').forEach(a => {
                    a.classList.remove('active');
                });
                this.classList.add('active');
                
                // Fetch data with new range
                fetchAIAnalysisData(range);
            });
        });
        
        // Auto-refresh every 5 minutes
        setInterval(fetchAIAnalysisData, 300000);
    });
    
    // Fetch AI analysis data
    function fetchAIAnalysisData(hourRange = 24) {
        // Show loading indicators
        document.getElementById('threatsList').innerHTML = '<tr class="text-center"><td colspan="7">Loading threat data...</td></tr>';
        document.getElementById('anomaliesList').innerHTML = '<tr class="text-center"><td colspan="4">Loading anomaly data...</td></tr>';
        document.getElementById('recommendationsList').innerHTML = '<p class="text-center">Loading recommendations...</p>';
        document.getElementById('patternsList').innerHTML = '<li class="text-center">Loading pattern data...</li>';
        
        // Fetch AI analysis data
        fetch(`api/ai_analysis.php?hours=${hourRange}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update threat score
                document.getElementById('threatScore').textContent = data.score.toFixed(0);
                updateThreatScoreChart(data.score);
                
                // Update threat counters
                document.getElementById('criticalThreats').textContent = data.threat_counts.critical || 0;
                document.getElementById('highThreats').textContent = data.threat_counts.high || 0;
                document.getElementById('mediumThreats').textContent = data.threat_counts.medium || 0;
                document.getElementById('lowThreats').textContent = data.threat_counts.low || 0;
                
                // Update detection trend chart
                updateDetectionTrendChart(data.trend_data);
                
                // Update attack distribution chart
                updateAttackDistributionChart(data.attack_distribution);
                
                // Update threats list
                updateThreatsList(data.threats);
                
                // Update anomalies list
                updateAnomaliesList(data.anomalies);
                
                // Update recommendations
                updateRecommendations(data.recommendations);
                
                // Update patterns list
                updatePatternsList(data.patterns);
                
                // Update threshold value
                document.getElementById('thresholdValue').value = data.settings.threshold;
                document.getElementById('thresholdDisplay').textContent = data.settings.threshold;
                
                // Update learning mode
                document.getElementById('learningMode').checked = data.settings.learning_enabled;
            } else {
                // Show error message
                document.getElementById('threatsList').innerHTML = `<tr class="text-center"><td colspan="7">Error: ${data.message}</td></tr>`;
                document.getElementById('anomaliesList').innerHTML = `<tr class="text-center"><td colspan="4">Error: ${data.message}</td></tr>`;
                document.getElementById('recommendationsList').innerHTML = `<div class="alert alert-danger">Error: ${data.message}</div>`;
                document.getElementById('patternsList').innerHTML = `<li>Error: ${data.message}</li>`;
            }
        })
        .catch(error => {
            console.error('Error fetching AI analysis data:', error);
            document.getElementById('threatsList').innerHTML = '<tr class="text-center"><td colspan="7">Error fetching data. Please try again.</td></tr>';
            document.getElementById('anomaliesList').innerHTML = '<tr class="text-center"><td colspan="4">Error fetching data. Please try again.</td></tr>';
            document.getElementById('recommendationsList').innerHTML = '<div class="alert alert-danger">Error fetching data. Please try again.</div>';
            document.getElementById('patternsList').innerHTML = '<li>Error fetching data. Please try again.</li>';
        });
    }
    
    // Update threat score chart
    function updateThreatScoreChart(score) {
        threatScoreChart.data.datasets[0].data = [score, 100 - score];
        
        // Set color based on score
        let color;
        if (score >= 80) {
            color = 'rgba(220, 53, 69, 0.8)'; // danger
        } else if (score >= 60) {
            color = 'rgba(255, 193, 7, 0.8)'; // warning
        } else if (score >= 30) {
            color = 'rgba(23, 162, 184, 0.8)'; // info
        } else {
            color = 'rgba(40, 167, 69, 0.8)'; // success
        }
        
        threatScoreChart.data.datasets[0].backgroundColor[0] = color;
        threatScoreChart.update();
    }
    
    // Update detection trend chart
    function updateDetectionTrendChart(trendData) {
        detectionTrendChart.data.labels = trendData.labels;
        detectionTrendChart.data.datasets[0].data = trendData.values;
        detectionTrendChart.update();
    }
    
    // Update attack distribution chart
    function updateAttackDistributionChart(distribution) {
        attackDistributionChart.data.labels = distribution.labels;
        attackDistributionChart.data.datasets[0].data = distribution.values;
        attackDistributionChart.update();
    }
    
    // Update threats list
    function updateThreatsList(threats) {
        if (threats.length === 0) {
            document.getElementById('threatsList').innerHTML = '<tr class="text-center"><td colspan="7">No threats detected in the selected time period.</td></tr>';
            return;
        }
        
        let html = '';
        threats.forEach(threat => {
            const date = new Date(threat.timestamp * 1000);
            const timeString = date.toLocaleString();
            
            let severityClass;
            switch (threat.severity) {
                case 'critical': severityClass = 'danger'; break;
                case 'high': severityClass = 'warning'; break;
                case 'medium': severityClass = 'info'; break;
                default: severityClass = 'success';
            }
            
            html += `
                <tr>
                    <td>${timeString}</td>
                    <td>${threat.type}</td>
                    <td><span class="badge badge-${severityClass}">${threat.severity}</span></td>
                    <td>${threat.source_ip || threat.target_ip || '-'}</td>
                    <td>${(threat.confidence * 100).toFixed(0)}%</td>
                    <td>${threat.description || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-info view-threat-btn" data-id="${threat.id}">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        document.getElementById('threatsList').innerHTML = html;
    }
    
    // Update anomalies list
    function updateAnomaliesList(anomalies) {
        if (anomalies.length === 0) {
            document.getElementById('anomaliesList').innerHTML = '<tr class="text-center"><td colspan="4">No anomalies detected in the selected time period.</td></tr>';
            return;
        }
        
        let html = '';
        anomalies.forEach(anomaly => {
            const date = new Date(anomaly.timestamp * 1000);
            const timeString = date.toLocaleString();
            
            let severityClass;
            switch (anomaly.severity) {
                case 'high': severityClass = 'warning'; break;
                case 'medium': severityClass = 'info'; break;
                default: severityClass = 'success';
            }
            
            html += `
                <tr>
                    <td>${timeString}</td>
                    <td>${anomaly.type}</td>
                    <td><span class="badge badge-${severityClass}">${anomaly.severity}</span></td>
                    <td>${anomaly.description || '-'}</td>
                </tr>
            `;
        });
        
        document.getElementById('anomaliesList').innerHTML = html;
    }
    
    // Update recommendations
    function updateRecommendations(recommendations) {
        if (recommendations.length === 0) {
            document.getElementById('recommendationsList').innerHTML = '<p class="text-center">No recommendations available for the current analysis.</p>';
            return;
        }
        
        let html = '<div class="list-group">';
        recommendations.forEach(rec => {
            let priorityClass;
            switch (rec.priority) {
                case 'critical': priorityClass = 'danger'; break;
                case 'high': priorityClass = 'warning'; break;
                case 'medium': priorityClass = 'info'; break;
                default: priorityClass = 'success';
            }
            
            html += `
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">
                            <span class="badge badge-${priorityClass} mr-2">${rec.priority}</span>
                            ${rec.action}
                        </h5>
                        <small>Related to: ${rec.related_to}</small>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        document.getElementById('recommendationsList').innerHTML = html;
    }
    
    // Update patterns list
    function updatePatternsList(patterns) {
        if (patterns.length === 0) {
            document.getElementById('patternsList').innerHTML = '<li class="text-center">No patterns have been recognized yet.</li>';
            return;
        }
        
        let html = '';
        patterns.forEach(pattern => {
            const date = new Date(pattern.learned_at * 1000);
            const timeString = date.toLocaleDateString();
            
            html += `<li><strong>${pattern.type}</strong> (learned: ${timeString})</li>`;
        });
        
        document.getElementById('patternsList').innerHTML = html;
    }
    
    // Display threat details in modal
    function displayThreatDetails(threat) {
        const date = new Date(threat.timestamp * 1000);
        const timeString = date.toLocaleString();
        
        let severityClass;
        switch (threat.severity) {
            case 'critical': severityClass = 'danger'; break;
            case 'high': severityClass = 'warning'; break;
            case 'medium': severityClass = 'info'; break;
            default: severityClass = 'success';
        }
        
        // Set modal title
        document.getElementById('threatDetailsTitle').innerHTML = `
            <span class="badge badge-${severityClass} mr-2">${threat.severity}</span>
            ${threat.type} Threat
        `;
        
        // Set mitigation button data
        document.getElementById('mitigateButton').setAttribute('data-id', threat.id);
        
        // Build details HTML
        let detailsHtml = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Time Detected:</strong> ${timeString}
                </div>
                <div class="col-md-6">
                    <strong>Confidence:</strong> ${(threat.confidence * 100).toFixed(0)}%
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <strong>Description:</strong> ${threat.description || 'No description available'}
                </div>
            </div>
        `;
        
        // Add IP information if available
        if (threat.source_ip || threat.target_ip) {
            detailsHtml += '<div class="row mb-3">';
            
            if (threat.source_ip) {
                detailsHtml += `
                    <div class="col-md-6">
                        <strong>Source IP:</strong> ${threat.source_ip}
                    </div>
                `;
            }
            
            if (threat.target_ip) {
                detailsHtml += `
                    <div class="col-md-6">
                        <strong>Target IP:</strong> ${threat.target_ip}
                    </div>
                `;
            }
            
            detailsHtml += '</div>';
        }
        
        // Add port information if available
        if (threat.source_port || threat.target_port) {
            detailsHtml += '<div class="row mb-3">';
            
            if (threat.source_port) {
                detailsHtml += `
                    <div class="col-md-6">
                        <strong>Source Port:</strong> ${threat.source_port}
                    </div>
                `;
            }
            
            if (threat.target_port) {
                detailsHtml += `
                    <div class="col-md-6">
                        <strong>Target Port:</strong> ${threat.target_port}
                    </div>
                `;
            }
            
            detailsHtml += '</div>';
        }
        
        // Add attack-specific details
        if (threat.type === 'ddos' && threat.attack_type) {
            detailsHtml += `
                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Attack Type:</strong> ${threat.attack_type}
                    </div>
                </div>
            `;
        } else if (threat.type === 'port_scan' && threat.port_count) {
            detailsHtml += `
                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Ports Scanned:</strong> ${threat.port_count}
                    </div>
                </div>
            `;
        } else if (threat.type === 'brute_force' && threat.attempt_count) {
            detailsHtml += `
                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Attempt Count:</strong> ${threat.attempt_count}
                    </div>
                </div>
            `;
        }
        
        // Add mitigations if available
        if (threat.mitigations && threat.mitigations.length > 0) {
            detailsHtml += `
                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Recommended Mitigations:</strong>
                        <ul>
            `;
            
            threat.mitigations.forEach(mitigation => {
                detailsHtml += `<li>${mitigation}</li>`;
            });
            
            detailsHtml += `
                        </ul>
                    </div>
                </div>
            `;
        }
        
        // Set modal body
        document.getElementById('threatDetailsBody').innerHTML = detailsHtml;
    }
</script>

<?php include_once 'includes/footer.php'; ?>