/**
 * H4N5VS Mikrotik System Security
 * Charts and data visualization
 */

// Global chart configuration
Chart.defaults.color = 'rgba(214, 226, 255, 0.7)';
Chart.defaults.borderColor = 'rgba(10, 255, 10, 0.1)';
Chart.defaults.font.family = "'Roboto Mono', monospace";

/**
 * Create or update a line chart for connection tracking
 * @param {string} elementId - Canvas element ID
 * @param {object} data - Data for the chart
 */
function createConnectionChart(elementId, data) {
    const ctx = document.getElementById(elementId).getContext('2d');
    
    // Check if chart already exists
    if (window[elementId + 'Chart']) {
        // Update existing chart
        window[elementId + 'Chart'].data.labels = data.labels;
        window[elementId + 'Chart'].data.datasets[0].data = data.values;
        window[elementId + 'Chart'].update();
        return;
    }
    
    // Create new chart
    window[elementId + 'Chart'] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: data.label,
                data: data.values,
                borderColor: 'rgba(10, 255, 10, 1)',
                backgroundColor: 'rgba(10, 255, 10, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: false
                    }
                },
                y: {
                    display: true,
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: data.yAxisLabel
                    }
                }
            }
        }
    });
}

/**
 * Create or update a doughnut chart for attack distribution
 * @param {string} elementId - Canvas element ID
 * @param {object} data - Data for the chart
 */
function createAttackDistributionChart(elementId, data) {
    const ctx = document.getElementById(elementId).getContext('2d');
    
    // Check if chart already exists
    if (window[elementId + 'Chart']) {
        // Update existing chart
        window[elementId + 'Chart'].data.labels = data.labels;
        window[elementId + 'Chart'].data.datasets[0].data = data.values;
        window[elementId + 'Chart'].update();
        return;
    }
    
    // Colors for different attack types
    const attackColors = [
        'rgba(255, 53, 71, 0.7)',    // Red for high severity
        'rgba(255, 187, 51, 0.7)',   // Orange for medium severity
        'rgba(51, 181, 229, 0.7)',   // Blue
        'rgba(10, 255, 10, 0.7)',    // Green
        'rgba(153, 102, 255, 0.7)'   // Purple
    ];
    
    // Create new chart
    window[elementId + 'Chart'] = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: attackColors,
                borderColor: 'rgba(20, 24, 36, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15
                    }
                }
            },
            cutout: '70%'
        }
    });
}

/**
 * Create or update a bar chart
 * @param {string} elementId - Canvas element ID
 * @param {object} data - Data for the chart
 */
function createBarChart(elementId, data) {
    const ctx = document.getElementById(elementId).getContext('2d');
    
    // Check if chart already exists
    if (window[elementId + 'Chart']) {
        // Update existing chart
        window[elementId + 'Chart'].data.labels = data.labels;
        window[elementId + 'Chart'].data.datasets[0].data = data.values;
        window[elementId + 'Chart'].update();
        return;
    }
    
    // Create new chart
    window[elementId + 'Chart'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: data.label,
                data: data.values,
                backgroundColor: 'rgba(10, 255, 10, 0.5)',
                borderColor: 'rgba(10, 255, 10, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    display: true
                },
                y: {
                    display: true,
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: data.yAxisLabel
                    }
                }
            }
        }
    });
}

/**
 * Format numbers to be more readable
 * @param {number} num - Number to format
 * @returns {string} Formatted number
 */
function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}

/**
 * Format bytes to appropriate units
 * @param {number} bytes - Bytes to format
 * @returns {string} Formatted bytes
 */
function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
